<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for manipulating models.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/pdbstat.php');

#{{{ addModel - adds a model to the session and creates its directory
############################################################################
/**
* This is suitable for the traditional model addition, where the file
* to add already exists as a separate PDB.
* It *only* makes sense in the context of an active session.
* It may have to be modified to handle e.g. splitting a PDB into multiple models.
*   tmpPdb          the (temporary) file where the upload is stored.
*   origName        the name of the file on the user's system.
*   isCnsFormat     true if the user thinks he has CNS atom names
*   ignoreSegID     true if the user wants to never map segIDs to chainIDs
*
* It returns the model ID code.
*/
function addModel($tmpPdb, $origName, $isCnsFormat = false, $ignoreSegID = false)
{
    // Try stripping file extension
    if(preg_match('/^(.+)\.(pdb|xyz|ent)$/i', $origName, $m))
        $id = $m[1];
    else
        $id = $origName;
    
    // Make sure this is a unique name
    // FUNKY: Be careful here b/c HFS on OS X is not case-sensitive.
    // (It's case-PRESERVING.) This could screw up file naming.
    foreach($_SESSION['models'] as $k => $v) $lowercaseIDs[strtolower($k)] = $k;
    while(isset($lowercaseIDs[strtolower($id.$serial)])) $serial++;
    $id .= $serial;
    
    // Process file - this is the part that matters
    $infile     = $tmpPdb;
    $outname    = $id.'_clean.pdb'; // don't confuse user by re-using exact original PDB name
    $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    if(!file_exists($outpath)) mkdir($outpath, 0777);
    $outpath .= '/'.$outname;
    list($stats, $segmap) = preparePDB($infile, $outpath, $isCnsFormat, $ignoreSegID);
    
    // Create the model entry
    $_SESSION['models'][$id] = array(
        'id'        => $id,
        'prefix'    => $id.'-',
        'pdb'       => $outname,
        'stats'     => $stats,
        'history'   => 'Original file uploaded by user'
    );
    
    if($segmap) $_SESSION['models'][$id]['segmap'] = $segmap;
    
    return $id;
}
#}}}########################################################################

#{{{ duplicateModel - makes a copy of an existing model for modification
############################################################################
/**
* A simple way to duplicate an existing model and its PDB for modification
* The duplicate file will be registered as a new model,
* and its model ID will be returned.
*
* You'll probably want to change the 'history' key on your own.
*
* $inModelID    the ID for the model that the newly Reduced file will be derived from
* $suffix       naming suffix for the new model / PDB file.
*/
function duplicateModel($inModelID, $suffix)
{
    $inModel = $_SESSION['models'][$inModelID];
    
    // New model ID
    $id = $inModelID.$suffix;
    // Make sure this is a unique name
    // FUNKY: Be careful here b/c HFS on OS X is not case-sensitive.
    // (It's case-PRESERVING.) This could screw up file naming.
    foreach($_SESSION['models'] as $k => $v) $lowercaseIDs[strtolower($k)] = $k;
    while(isset($lowercaseIDs[strtolower($id.$serial)])) $serial++;
    $id .= $serial;
    
    // Create directory
    $modelDir = $_SESSION['dataDir'].'/'.$id;
    mkdir($modelDir, 0777);
    $modelURL = $_SESSION['dataURL'].'/'.$id;
    
    // Copy file
    //$outname    = $id.'.pdb';
    $outname    = $inModel['pdb'];
    $outpath    = $modelDir.'/'.$outname;
    copy("$inModel[dir]/$inModel[pdb]", $outpath);
    
    // Create the model entry
    $_SESSION['models'][$id] = array(
        'id'        => $id,
        'dir'       => $modelDir,
        'url'       => $modelURL,
        'prefix'    => $id.'-',
        'pdb'       => $outname,
        'stats'     => pdbstat($outpath),
        'parent'    => $inModelID,
        'history'   => "Derived from $inModelID by duplication",
        'isReduced' => $inModel['isReduced'],
        'isBuilt'   => $inModel['isBuilt']
    );
    
    return $id;
}
#}}}########################################################################

#{{{ preparePDB - cleans up a user PDB before use (fixes names, etc)
############################################################################
/**
* This function reworks a PDB to standardize it before MolProbity uses it.
* Because so many actions are taken, this function handles its own
* progress reporting via the setProgress() function.
*
* The following actions are taken, in order:
*   Linefeeds are converted to Unix (\n)
*   Old USER MOD records are removed (only the detailed ones from Reduce)
*   CNS atom names are auto-detected
*   SEG IDs without chain IDs are auto-detected
*   CNS atom names and SEG IDs are repaired by pdbcns
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
* $isCNS        true if the file is known to use CNS atom naming conventions
*               false if we should auto-detect CNS headers and/or atom names.
* $ignoreSegID  true if we should never use segIDs to create new chain IDs.
*               false if we should convert automatically, as needed.
*
* Returns the following:
* array(
*   statistics from pdbstat() for the fully prepared model,
*   the segID-to-chainID mapping string, or "" for none
* );
*
* So call the function something like this:
*   list($stats, $map) = preparePDB( ... );
*/
function preparePDB($inpath, $outpath, $isCNS = false, $ignoreSegID = false)
{
    // If no session is started, this should eval to NULL or '',
    // which will specify the system tmp directory.
    $tmp1   = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
    $tmp2   = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
    
    // List of tasks for running as a background job
    $tasks['scrublines'] = "Convert linefeeds to UNIX standard (\\n)";
    $tasks['stripusermod'] = "Strip out old USER MOD records from <code>reduce</code>";
    $tasks['pdbstat'] = "Analyze contents of PDB file";
    $tasks['segmap'] = "Convert segment IDs to chain IDs (if needed)";
    $tasks['cnsnames'] = "Convert CNS atom names to PDB standard (if needed)";
    $tasks['pdbstat2'] = "Re-analyze contents of final PDB file";
    
    // Process file - this is the part that matters
    // Convert linefeeds to UNIX standard (\n):
    setProgress($tasks, 'scrublines'); // updates the progress display if running as a background job
    exec("scrublines < $inpath > $tmp1");
    // Remove stale USER MOD records that will confuse us later
    // We won't know which flips are old and which are new!
    setProgress($tasks, 'stripusermod'); // updates the progress display if running as a background job
    exec("awk '\$0 !~ /^USER  MOD (Set|Single|Fix|Limit)/' $tmp1 > $tmp2");
    // Get PDB statistics so we know if we have CNS atom names
    setProgress($tasks, 'pdbstat'); // updates the progress display if running as a background job
    $stats = pdbstat($tmp2);
    // Try to determine if we need to make chain IDs from segment IDs
    setProgress($tasks, 'segmap'); // updates the progress display if running as a background job
    $segToChainMapping = trim(`cksegid.pl $tmp2`);
    // Old Reduce used segID for 'new ' flag for H's.
    if($ignoreSegID) $segToChainMapping = "";
    if($segToChainMapping == "")
    {
        // Don't need to do anything
    }
    elseif(preg_match("/ OK\$/", $segToChainMapping))
    {
        $parts = preg_split("/\\s+/", $segToChainMapping);
        $segToChainMapping = $parts[0];
    }
    else
    {
        echo "*** Unable to automagically correct XPLOR/CNS segIDs";
        $segToChainMapping = "";
    }
    // Run PDBCNS if we need to (tmp2 is most recent file):
    // - if we have CNS-style atom names
    // - if we have CNS-style records in the header (3 or more)
    // - if the user told us these were CNS coordinates
    setProgress($tasks, 'cnsnames'); // updates the progress display if running as a background job
    if(/*has CNS atom names or*/ $stats['fromcns'] >= 3 || $isCNS)
    {
        if($segToChainMapping == "")
        {
            exec("pdbcns -c $tmp2 > $tmp1");
        }
        else
        {
            exec("pdbcns -cm '$segToChainMapping' $tmp2 > $tmp1");
            $segToChainMapping = "";
        }
    }
    elseif($segToChainMapping != "")
    {
        // Do the remapping some other way
        remapSegIDs($tmp2, $tmp1, $segToChainMapping);
    }
    else // swap tmp1 and tmp2; now tmp1 holds most recent file
    {
        $t = $tmp1;
        $tmp1 = $tmp2;
        $tmp2 = $t;
    }
    // Copy to output pdb
    copy($tmp1, $outpath);

    // Clean up temp files
    unlink($tmp1);
    unlink($tmp2);

    setProgress($tasks, 'pdbstat2'); // updates the progress display if running as a background job
    $stats = pdbstat($outpath);
    setProgress($tasks, null); // all done
    return array( $stats, $segToChainMapping );
}
#}}}########################################################################

#{{{ reduceNoBuild - adds missing H without changing existing atoms
############################################################################
/**
* This is the "least invasive" way of adding required hydrogens for AAC.
* The input file or its ancestor should have already been passed thru preparePDB().
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
*/
function reduceNoBuild($inpath, $outpath, $ignoreSegID = false)
{
    // Add missing H's without trying to optimize or fix anything
    exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -keep -noadjust -his -allalt $inpath > $outpath");
}
#}}}########################################################################

#{{{ reduceBuild - adds H and optimizes H-bond networks
############################################################################
/**
* This is the standard, expert-system way of adding required hydrogens for AAC.
* The input file or its ancestor should have already been passed thru preparePDB(),
* and it may or may not have hydrogens added. The new file will be registered as
* a new model, and its model ID will be returned.
*
* $inModelID    the ID for the model that the newly Reduced file will be derived from
* $inpath       the full path and filename for the PDB file to be processed
*/
function reduceBuild($inModelID, $inpath)
{
    // New model ID just has a H appended
    $id = $inModelID."H";
    
    // Make sure this is a unique name
    while( isset($_SESSION['models'][$id.$serial]) )
        $serial++;
    $id .= $serial;
    
    // Create directory
    $modelDir = $_SESSION['dataDir'].'/'.$id;
    mkdir($modelDir, 0777);
    $modelURL = $_SESSION['dataURL'].'/'.$id;
    
    // Process file - this is the part that matters
    $outname    = $id.'.pdb';
    $outpath    = $modelDir.'/'.$outname;
    exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -build -allalt $inpath > $outpath");
    
    // Create the model entry
    $_SESSION['models'][$id] = array(
        'id'        => $id,
        'dir'       => $modelDir,
        'url'       => $modelURL,
        'prefix'    => $id.'-',
        'pdb'       => $outname,
        'stats'     => pdbstat($outpath),
        'parent'    => $inModelID,
        'history'   => "Derived from $inModelID by default Reduce -build",
        'isReduced' => true,
        'isBuilt'   => true
    );
    
    return $id;
}
#}}}########################################################################

#{{{ reduceFix - adds H with user-selected Asn/Gln/His flip states
############################################################################
/**
* This is the user-customized way of adding required hydrogens for AAC.
* The input file or its ancestor should have already been passed thru preparePDB(),
* and it may or may not have hydrogens added.
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
* $flippath     the file listing residues to fix and their orientations, in
*               the appropriate format for Reduce's -fix option
*/
function reduceFix($inpath, $outpath, $flippath)
{
    exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -build -fix $flippath -allalt $inpath > $outpath");
}
#}}}########################################################################

#{{{ decodeReduceUsermods - interpret USERMOD records from Reduced PDB files
############################################################################
/**
* Read in $file until we reach an ATOM record.
* Look for USER  MOD records that show Asn/Gln/His flips,
* or bad clashes in both orientations.
*
* Results are placed in a 2D array with the first index as
* the "column number" and the second as the "row number".
* It's a little backwards, but it makes sorting easy.
*
* i:  groupID        chain  res#  resID  <other values>
* 0:  A__43_HIS____  A      43   *HIS*
* 1:  A__88_LYS_NZ_  A      88    LYS
* 2:  B__13_ASN____  B      13    ASN
*
* Thus, the cell set off with stars is $changes[3][0]
* i is the index in the array, and isn't recorded as a column.
*
* groupID is needed to generate the fix file for Reduce later on.
* It's easier to record it here than regenerate it later.
*
* Columns and their meanings are as follows:
*
* $changes[0]      the full group ID string from the PDB USER MOD record
* $changes[1]      the chain ID, if any
* $changes[2]      the residue number
* $changes[3]      the residue ID (type), e.g. ASN, GLN, HIS, etc.
* $changes[4]      a simple text flag as to why we pulled this record
* $changes[5]      a longer explanation of [4]
* $changes[6]      the final score, after sc=
* $changes[7]          empty (no clash) or a ! (clash)
* $changes[8]      the original orientation score, after o=
* $changes[9]          empty (no clash) or a ! (clash)
* $changes[10]     the flipped orientation score, after f=
* $changes[11]         empty (no clash) or a ! (clash)
* $changes[12]     the flip category: either F, X, C, or K
*/
function decodeReduceUsermods($file)
{
    $fp = fopen($file, "r");
    $c      = 0;      // Track number of flips and clashes found (Counter)
    $nlines = 0;      // Track number of lines read, for debugging

    while( !feof($fp) and ($s = fgets($fp, 200)) and !eregi("^ATOM  ", $s) )
    {
        // Look for Asn, Gln, and His marked by Reduce
        #if( eregi("^USER  MOD........:......(ASN|GLN|HIS)", $s) )
        if(preg_match('/^USER  MOD (Set|Single).*?:.{6}(ASN|GLN|HIS)/i', $s))
        {
            // Break it down into colon-delimited fields.
            // There are four fields - Single/Set/Fix : Group ID : (FLIP) group : scores
            $field = explode(":", $s);

            // Most values can be done without knowing whether or not this is a flip.
            $changes[0][$c] = $field[1];
            /** Original, highly inefficient code! * /
            $changes[1][$c] = trim(eregi_replace("^(.).*$", "\\1", $field[1]));
            $changes[2][$c] = trim(eregi_replace("^.(....).*$", "\\1", $field[1]));
            $changes[3][$c] = trim(eregi_replace("^.....(....).*$", "\\1", $field[1]));
            // skip 4 and 5 for now
            $changes[6][$c] = trim(eregi_replace("^sc=(........).*$", "\\1", $field[3]));
            $changes[7][$c] = trim(eregi_replace("^sc=........([! ]).*$", "\\1", $field[3]));
            $changes[8][$c] = trim(eregi_replace("^sc=......... ..o=([^!]+)(!?),f=([^!]+)(!?).*$", "\\1", $field[3]));
            $changes[9][$c] = trim(eregi_replace("^sc=......... ..o=([^!]+)(!?),f=([^!]+)(!?).*$", "\\2", $field[3]));
            $changes[10][$c] = trim(eregi_replace("^sc=......... ..o=([^!]+)(!?),f=([^!\)]+)(!?).*$", "\\3", $field[3]));
            $changes[11][$c] = trim(eregi_replace("^sc=......... ..o=([^!]+)(!?),f=([^!]+)(!?).*$", "\\4", $field[3]));
            $changes[12][$c] = trim(eregi_replace("^sc=......... ([FXCK]).*$", "\\1", $field[3]));
            /** Original, highly inefficient code! */
            preg_match('/^(.)(....)(....)/', $field[1], $f1);
            $changes[1][$c] = trim($f1[1]);
            $changes[2][$c] = trim($f1[2]);
            $changes[3][$c] = trim($f1[3]);
            // skip 4 and 5 for now
            if(preg_match('/^sc=(........\d?)([! ]) +?([FXCK]?)\(o=([^!]+)(!?),f=([^!\)]+)(!?)\)?/', $field[3], $f3))
            {
                $changes[6][$c] = trim($f3[1]);
                $changes[7][$c] = trim($f3[2]);
                $changes[8][$c] = trim($f3[4]);
                $changes[9][$c] = trim($f3[5]);
                $changes[10][$c] = trim($f3[6]);
                $changes[11][$c] = trim($f3[7]);
                $changes[12][$c] = trim($f3[3]);
            }
            // For things who's score doesn't change when flipped (???)
            elseif(preg_match('/^sc=(........\d?)([! ]) +?([FXCK]?)\(180deg=([^!\)]+)(!?)\)?/', $field[3], $f3))
            {
                $changes[6][$c] = trim($f3[1]);
                $changes[7][$c] = trim($f3[2]);
                $changes[8][$c] = trim($f3[4]);
                $changes[9][$c] = trim($f3[5]);
                $changes[10][$c] = trim($f3[4]);
                $changes[11][$c] = trim($f3[5]);
                $changes[12][$c] = 'X';
            }
            else
            {
                echo "*** decodeReduceUsermods(): Couldn't process USER MOD correctly:\n";
                echo $s;
                continue;
            }
            
            // Set this flag so we can determine 4 and 5
            $didflip = eregi("^FLIP", $field[2]);

            switch($changes[12][$c])
            {
                case "F":
                    $changes[4][$c] = "FLIP";
                    if( ($changes[10][$c] - $changes[8][$c]) >= 2 )
                        { $changes[5][$c] = "Clear evidence for flip."; }
                    else
                        { $changes[5][$c] = "Some evidence recommending flip."; }
                    break;
                case "X":
                    $changes[4][$c] = "UNSURE";
                    $changes[5][$c] = "Score difference &lt; penalty function; no action taken.";
                    break;
                case "C":
                    if($didflip)
                    {
                        $changes[4][$c] = "CLS-FL";
                        $changes[5][$c] = "Both orientations clash but flip was preferred.";
                    }
                    else
                    {
                        $changes[4][$c] = "CLS-KP";
                        $changes[5][$c] = "Both orientations clash but original was preferred.";
                    }
                    break;
                case "K":
                    $changes[4][$c] = "KEEP";
                    $changes[5][$c] = "Original orientation was best.";
                    break;
                default:
                    // This occurs for a rare bug in Reduce as of 11.14.01
                    // sc= field may be too wide and displace this character.
                    // 5/7/04: This should never occur any more.
                    $changes[4][$c] = "???";
                    $changes[5][$c] = "Inconsistency in USER MOD records.";
            }

            $c++;
        }

        $nlines++;
    }

    fclose($fp);

    // If we found any NQH, sort the list.
    if( isset($changes))
    {
        // Sort table. BE SURE to list ALL COLUMNS here, or data will be scrambled!!!
        array_multisort($changes[0],  $changes[1],  $changes[2],  $changes[3],  $changes[4],
                        $changes[5],  $changes[6],  $changes[7],  $changes[8],  $changes[9],
                        $changes[10], $changes[11], $changes[12]);
    }

    return $changes;
}
#}}}########################################################################

#{{{ getPdbModel - retrieves a model from the Protein Data Bank
############################################################################
/**
* Retrieves the PDB file with the given code from www.rcsb.org
*   pdbcode     the 4-character code identifying the model
* Returns the name of a temporary file, or null if download failed.
*/
function getPdbModel($pdbcode)
{
    // I think the PDB website is picky about case
    $pdbcode = strtoupper($pdbcode);

    // Copy in the newly uploaded file:
    $src = fopen("http://www.rcsb.org/pdb/cgi/export.cgi/$pdbcode.pdb?format=PDB&pdbId=$pdbcode&compression=None", "rb");
    if($src)
    {
        $outpath = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
        $dst = fopen($outpath, "wb");

        for($buf = fread($src, 8192); !feof($src); $buf = fread($src, 8192))
        {
            fwrite($dst, $buf);
        }
        if(strlen($buf) > 0) { fwrite($dst, $buf); }

        fclose($src);
        fclose($dst);

        if( filesize($outpath) > 1000 ) return $outpath;
        else
        {
            if(file_exists($outpath)) unlink($outpath);
            return null;
        }
    }
    else return null;
}
#}}}########################################################################

#{{{ getNdbModel - retrieves a model from the Nucleic Acid Data Bank
############################################################################
/**
* Retrieves the PDB file with the given code from www.rcsb.org
*   pdbcode     the 4-character code identifying the model
* Returns the name of a temporary file, or null if download failed.
*/
function getNdbModel($code)
{
    // I think the NDB website is picky about case
    $code = strtoupper($code);
    
    // Fake POST a user query to the NDB homepage
    $ch = curl_init('http://ndbserver.rutgers.edu/servlet/IDSearch.NDBSearch1');
    $webpage = tempnam(MP_BASE_DIR."/tmp", "tmp_html_");
    $fp = fopen($webpage, "wb");
    
    curl_setopt($ch, CURLOPT_FILE, $fp);    // output to specified file
    curl_setopt($ch, CURLOPT_HEADER, 0);    // no header in output
    curl_setopt($ch, CURLOPT_POST, 1);      // perform HTTP POST operation
    curl_setopt($ch, CURLOPT_POSTFIELDS, "id=$code&Submit=Search&radiobutton=ndbid&site=production");
    
    curl_exec($ch); // download file
    curl_close($ch);
    fclose($fp);
    
    // Grep for this pattern:
    //<a href="ftp://ndbserver.rutgers.edu/NDB/coordinates/na-chiral-correct/pdb401d.ent.Z">Asymmetric Unit coordinates (pdb format
    $lines = file($webpage);
    foreach($lines as $line)
    {
        if(preg_match('/<a href="([^"]+)">Asymmetric Unit coordinates \(pdb format/', $line, $m))
        {
            $url = $m[1];
            break;
        }
    }
    unlink($webpage);
    if(!isset($url)) return null;
    
    // Download file...
    $src = fopen($url, "rb");
    if(! $src) return null;

    $Zfile = tempnam(MP_BASE_DIR."/tmp", "tmp_pdbZ_");
    $dst = fopen($Zfile, "wb");
    for($buf = fread($src, 8192); !feof($src); $buf = fread($src, 8192))
    {
        fwrite($dst, $buf);
    }
    if(strlen($buf) > 0) { fwrite($dst, $buf); }
    fclose($src);
    fclose($dst);

    if( filesize($Zfile) < 1000 )
    {
        if(file_exists($Zfile)) unlink($Zfile);
        return null;
    }
    
    // Use gunzip to decompress it
    // -S forces gunzip to recognize file by magic number rather than .Z ending
    $outpath = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
    exec("gunzip -c -S '' $Zfile > $outpath");
    unlink($Zfile);
    
    if( filesize($outpath) > 1000 ) return $outpath;
    else
    {
        if(file_exists($outpath)) unlink($outpath);
        return null;
    }
}
#}}}########################################################################

#{{{ remapSegIDs - translates segIDs into chain IDs for ATOM and HETATM records
############################################################################
/**
* Translates segIDs into chain IDs for ATOM and HETATM records.
* mapString is the same as Reduce's -segid: "ssss,c,ssss,c, ..."
* Note that all chains/segments are rendered in all uppercase, regardless of
* the original case in the PDB file, and space are converted to underscores.
*/
function remapSegIDs($inpath, $outpath, $mapString)
{
    // Create a segID -> chainID map for lookup
    $mapString = str_replace("_", " ", $mapString);
    $tmp = explode(',', $mapString);
    echo(count($tmp));
    print_r($tmp);
    for($i = 0; $i < count($tmp); $i += 2)
        $map[strtoupper($tmp[$i])] = strtoupper($tmp[$i+1]);
    print_r($map);
    
    $in = fopen($inpath, 'rb');
    $out = fopen($outpath, 'wb');
    while(!feof($in))
    {
        $s = fgets($in, 4096);
        $chainID = $map[ strtoupper(substr($s,72,4)) ];
        if((startsWith($s, "ATOM  ") || startsWith($s, "HETATM")) && strlen($chainID) == 1 )
        {
            fwrite($out, substr($s,0,21));
            fwrite($out, $chainID);
            fwrite($out, substr($s,22));
        }
        else fwrite($out, $s);
    }
    fclose($out);
    fclose($in);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
