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
    while( isset($_SESSION['models'][$id.$serial]) )
        $serial++;
    $id .= $serial;
    
    // Create directory
    $modelDir = $_SESSION['dataDir'].'/'.$id;
    mkdir($modelDir, 0777);
    $modelURL = $_SESSION['dataURL'].'/'.$id;
    
    // Process file - this is the part that matters
    $infile     = $tmpPdb;
    $outname    = $id.'mp.pdb'; // don't confuse user by re-using exact original PDB name
    $outpath    = $modelDir.'/'.$outname;
    preparePDB($infile, $outpath, $isCnsFormat, $ignoreSegID);
    
    // Create the model entry
    $_SESSION['models'][$id] = array(
        'id'        => $id,
        'dir'       => $modelDir,
        'url'       => $modelURL,
        'prefix'    => $id.'-',
        'pdb'       => $outname,
        'stats'     => pdbstat($outpath),
        'history'   => 'Original file uploaded by user'
    );
    
    return $id;
}
#}}}########################################################################

#{{{ removeModel - unregisters a model from the session and deletes its data
############################################################################
/**
* Removes a model from the current session and destroys all its data.
* This only makes sense in the context of an established session.
* Only recommended for use in batch-processing scripts, because other
* data structures (model parent, lab notebook, etc) may reference this model.
*/
function removeModel($modelID)
{
    if(isset($_SESSION['models'][$modelID]))
    {
        $modelDir = $_SESSION['models'][$modelID]['dir'];
        // This actually seems to be most robust and portable... unlink() is very awkward
        exec("rm -rf '$modelDir'");
        unset($_SESSION['models'][$modelID]);
    }
}
#}}}########################################################################

#{{{ preparePDB - cleans up a user PDB before use (fixes names, etc)
############################################################################
/**
* This function reworks a PDB to standardize it before MolProbity uses it.
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
*/
function preparePDB($inpath, $outpath, $isCNS = false, $ignoreSegID = false)
{
    // If no session is started, this should eval to NULL or '',
    // which will specify the system tmp directory.
    $tmp1   = tempnam($_SESSION['dataDir'], "tmp_pdb_");
    $tmp2   = tempnam($_SESSION['dataDir'], "tmp_pdb_");
    
    // Process file - this is the part that matters
    // Convert linefeeds to UNIX standard (\n):
    exec("scrublines < $inpath > $tmp1");
    // Remove stale USER MOD records that will confuse us later
    // We won't know which flips are old and which are new!
    exec("awk '\$0 !~ /^USER  MOD (Set|Single|Fix|Limit)/' $tmp1 > $tmp2");
    // Get PDB statistics so we know if we have CNS atom names
    $stats = pdbstat($tmp2);
    // Try to determine if we need to make chain IDs from segment IDs
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
* $ignoreSegID  true if we should never use segIDs to create new chain IDs.
*               false if we should convert automatically, as needed.
*/
function reduceNoBuild($inpath, $outpath, $ignoreSegID = false)
{
    // Try to determine if we need to make chain IDs from segment IDs
    $segToChainMapping = trim(`cksegid.pl $inpath`);
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
    // Add missing H's without trying to optimize or fix anything
    $segmap = ($segToChainMapping == "" ? "" : "-segidmap '$segToChainMapping'");
    exec("reduce -quiet -limit".MP_REDUCE_LIMIT." $segmap -keep -noadjust -his -allalt $inpath > $outpath");
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
        'isReduced' => true
    );
    
    return $id;
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
        if( eregi("^USER  MOD........:......(ASN|GLN|HIS)", $s) )
        {
            // Break it down into colon-delimited fields.
            // There are four fields - Single/Set/Fix : Group ID : (FLIP) group : scores
            $field = explode(":", $s);

            // Most values can be done without knowing whether or not this is a flip.
            $changes[0][$c] = $field[1];
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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
