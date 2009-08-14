<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for manipulating models.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/pdbstat.php');

#{{{ createModel - returns a "model" data structure for a given name.
############################################################################
/**
* Creates a model data structure suitable for insertion into
* $_SESSION[models], but does not actually insert it.
* The primary purpose of this function is to encapsulate requirements
* for name- and prefix-uniqueness within the current session.
* Likewise, a name for the PDB file is created, but the file itself is NOT
* created and MP_DIR_MODELS may not even exist yet.
*
*   modelID     the desired model ID. A serial number may be appended.
*   pdbSuffix   a suffix to apply to the PDB filename. Usually "".
*
* returns: an array containing 'id', 'pdb', 'prefix'
*/
function createModel($modelID, $pdbSuffix = "")
{
    // Make sure this is a unique name among BOTH models AND ensembles.
    // FUNKY: Be careful here b/c HFS on OS X is not case-sensitive.
    // (It's case-PRESERVING.) This could screw up file naming.
    foreach($_SESSION['models'] as $k => $v) $lowercaseIDs[strtolower($k)] = $k;
    foreach($_SESSION['ensembles'] as $k => $v) $lowercaseIDs[strtolower($k)] = $k;
    
    // If this is true, we're going to need to differentiate this model from an existing one
    if(isset($lowercaseIDs[strtolower($modelID)])
    || file_exists($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$modelID.$pdbSuffix.".pdb"))
    {
        // If we're appending a number to an ID ending in a number, add an underscore!
        if(preg_match('/[0-9]$/', $modelID)) $modelID .= '_';
        $serial = 1;
        while(isset($lowercaseIDs[strtolower($modelID.$serial)])
        || file_exists($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$modelID.$serial.$pdbSuffix.".pdb"))
        {
            $serial++;
        }
        $modelID .= $serial;
    }
    
    // $modelID already has $serial in it (if needed)
    $outname = $modelID.$pdbSuffix.".pdb";
    
    // Create the model entry
    return array(
        'id'        => $modelID,
        'prefix'    => $modelID.'-',
        'pdb'       => $outname,
    );
}
#}}}########################################################################

#{{{ createEnsemble - returns a "ensemble" data structure for a given name.
############################################################################
/**
* Creates a model data structure suitable for insertion into
* $_SESSION[ensembles], but does not actually insert it.
* The primary purpose of this function is to encapsulate requirements
* for name- and prefix-uniqueness within the current session.
* Likewise, a name for the PDB file is created, but the file itself is NOT
* created and MP_DIR_MODELS may not even exist yet.
*
*   ensembleID      the desired ensemble ID. A serial number may be appended.
*
* returns: an array containing 'id', 'pdb', 'prefix'
*/
function createEnsemble($ensembleID)
{
    // Logic is exactly the same as above!
    return createModel($ensembleID);
}
#}}}########################################################################

#{{{ addModelOrEnsemble - adds an up/downloaded model to the session
############################################################################
/**
* This is suitable for the traditional model addition, where the file
* to add already exists as a separate PDB.
* It *only* makes sense in the context of an active session.
*   tmpPdb          the (temporary) file where the upload is stored.
*   origName        the name of the file on the user's system.
*   isCnsFormat     true if the user thinks he has CNS atom names
*   ignoreSegID     true if the user wants to never map segIDs to chainIDs
*   isUserSupplied  false if the file was fetched from a public database
*
* It returns either a model ID (for inputs with 0 or 1 MODEL records)
* or an ensemble ID (for multi-MODEL input)
* which of course then links to the individual model IDs.
*/
function addModelOrEnsemble($tmpPdb, $origName, $isCnsFormat = false, $ignoreSegID = false, $isUserSupplied = true)
{
    // Try stripping file extension
    if(preg_match('/^(.+)\.(pdb|xyz|ent)$/i', $origName, $m))
        $origID = $m[1];
    else
        $origID = $origName;
    
    // Process file to clean it up
    $tmp2 = mpTempfile("tmp_pdb_");
    $tmp3 = mpTempfile("tmp_pdb_");
    list($stats, $segmap) = preparePDB($tmpPdb, $tmp2, $isCnsFormat, $ignoreSegID);
    $stats = convertToPDBv3($tmp2, $tmp3);
    
    if($stats['models'] > 1) // NMR/theoretical with multiple models {{{
    {
        // Original task list set during preparePDB()
        $tasks = getProgressTasks();
        $tasks['splitNMR'] = "Split NMR models into separate PDB files";
        setProgress($tasks, "splitNMR");
        
        $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
        if(!file_exists($outpath)) mkdir($outpath, 0777);
        $splitModels = splitPdbModels($tmp2);
        unlink($tmp2);
        $idList = array();
        foreach($splitModels as $modelNum => $tmp3)
        {
            // Jane prefers the model number in front. This is good for kins,
            // so mdl can be ID'd, but bad for sorting multiple structures...
            $model = createModel(sprintf("m%02d_{$origID}", $modelNum));
            $id = $model['id'];
            // Better to keep the original model numbers hanging around:
            //$idList[] = $id;
            $idList[$modelNum] = $id;
            
            $file = $outpath.'/'.$model['pdb'];
            copy($tmp3, $file);
            unlink($tmp3);
            
            $model['stats']                 = pdbstat($file);
            $model['history']               = "Model $modelNum from file uploaded by user";
            $model['isUserSupplied']        = $isUserSupplied;
            if($segmap) $model['segmap']    = $segmap;
            
            // Create the model entry
            $_SESSION['models'][$id] = $model;
        }
        
        // Create the ensemble entry
        //$ensemble = createEnsemble("ens_$origID"); // why add the "ens_" and confuse people?
        $ensemble = createEnsemble($origID);
        $ensemble['models'] = $idList;
        $ensemble['history'] = "Ensemble of ".count($idList)." models uploaded by user";
        $ensemble['isUserSupplied'] = $isUserSupplied;
        
        $pdbList = array();
        foreach($idList as $modelNum => $id) $pdbList[$modelNum] = $outpath.'/'.$_SESSION['models'][$id]['pdb'];
        $joinedModel = joinPdbModels($pdbList);
        copy($joinedModel, $outpath.'/'.$ensemble['pdb']);
        unlink($joinedModel);
        
        // Create the ensemble entry
        $id = $ensemble['id'];
        $_SESSION['ensembles'][$id] = $ensemble;
        return $id;
    }//}}}
    else // "standard" x-ray structure with one model {{{
    {
        // If our cleaning procedure had no impact, then don't confuse by
        // changing the name. If it DID change something, then append "_clean".
        $append = "";
        if(!filesAreIdentical($tmpPdb, $tmp2)) $append .= "_clean";
        if(!filesAreIdentical($tmp2, $tmp3)) $append .= "_pdbv3";
        $model = createModel($origID, $append);
        //if(filesAreIdentical($tmpPdb, $tmp2))   $model = createModel($origID);        
        //else                                    $model = createModel($origID, "_clean");

    
        $model['stats']                 = $stats;
        $historyText = "";
        if (filesAreIdentical($tmpPdb, $tmp3))  $historyText  = 'Original file ';
        else                                    $historyText  = 'File (modified) ';
        if ($isUserSupplied)                    $historyText .= 'uploaded by user';
        else                                    $historyText .= 'downloaded from web';
        $model['history'] = $historyText;
        $model['isUserSupplied']        = $isUserSupplied;
        if($segmap) $model['segmap']    = $segmap;
        
        $id         = $model['id'];
        $outname    = $model['pdb'];
        $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
        if(!file_exists($outpath)) mkdir($outpath, 0777);
        $outpath .= '/'.$outname;
        copy($tmp3, $outpath);
        unlink($tmp3);   
        unlink($tmp2);
        
        // Create the model entry
        $_SESSION['models'][$id] = $model;
        return $id;
    }//}}}
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
    $tmp1   = mpTempfile("tmp_pdb_");
    $tmp2   = mpTempfile("tmp_pdb_");
    
    // List of tasks for running as a background job
    $tasks['scrublines'] = "Convert linefeeds to UNIX standard (\\n)";
    $tasks['stripusermod'] = "Strip out old USER MOD records from <code>reduce</code>";
    $tasks['pdbstat'] = "Analyze contents of PDB file";
    $tasks['segmap'] = "Convert segment IDs to chain IDs (if needed)";
    $tasks['cnsnames'] = "Convert CNS atom names to PDB standard (if needed)";
    $tasks['pdbstat2'] = "Re-analyze contents of final PDB file";
    //$tasks['remediate'] = "Convert PDB to version 3 format (if needed)";    
    
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
    //setProgress($tasks, 'remediate');
    //$v2atoms = $stats['v2atoms'];
    ////echo "pre-pdbconvert-number of v2atoms: ".$v2atoms."\n";
    //if($v2atoms > 0) {
    //    exec("remediator.pl $tmp1 > $tmp2");
    //    $t = $tmp1;
    //    $tmp1 = $tmp2;
    //    $tmp2 = $t;
    //}
    // Copy to output pdb
    copy($tmp1, $outpath);

    // Clean up temp files
    unlink($tmp1);
    unlink($tmp2);

    setProgress($tasks, 'pdbstat2'); // updates the progress display if running as a background job
    $stats = pdbstat($outpath);
    //$stats['v2atoms'] = $v2atoms; // have to reset number of v2atoms because post-convert pdbstat should count zero
    setProgress($tasks, null); // all done
    return array( $stats, $segToChainMapping );
}
#}}}########################################################################

#{{{ convertToPDBv3 - converts pdb file to v 3.0
############################################################################
function convertToPDBv3($inpath, $outpath) {
    $tasks = getProgressTasks();
    $tasks['remediate'] = "Convert PDB to version 3 format (if needed)";
    $tmp1   = mpTempfile("tmp_pdb_");
    
    setProgress($tasks, 'remediate');
    $stats = pdbstat($inpath);
    $v2atoms = $stats['v2atoms'];
    //echo "pre-pdbconvert-number of v2atoms: ".$v2atoms."\n";
    if($v2atoms > 0) {
        exec("remediator.pl $inpath > $tmp1");
        copy($tmp1, $outpath);
    } else {
        copy($inpath, $outpath);
    }
    
    // Clean up temp files
    unlink($tmp1);
    
    setProgress($tasks, null); // all done
    return $stats;
}
#}}}########################################################################

#{{{ splitModelsNMR [DEPRECATED] - an alias for splitPdbModels()
############################################################################
/**
* Returns an array of temp file names holding the split models.
* The models appear in order, and the keys of the array are the model numbers.
*/
function splitModelsNMR($infile)
{ return splitPdbModels($infile); }
#}}}########################################################################

#{{{ splitPdbModels - creates many PDBs from one multi-MODEL PDB file
############################################################################
/**
* Returns an array of temp file names holding the split models.
* The models appear in order, and the keys of the array are the model numbers.
* Unlike splitModelsNMR, this function handles CONECT records properly, and
* has minimal memory requirements.
*/
function splitPdbModels($infile)
{
    // Make places to store headers, footers, and ATOMs
    // FUNKY: this uses PHP references to juggle arrays. See the manual.
    $headers = array();     // REMARKs, etc.
    $footers = array();     // CONECTs, etc.
    $sink =& $headers;      // where lines are currently deposited
    
    // Part 1: extract headers and footers from the PDB file
    $keeplines = true;
    $in = fopen($infile,"rb");
    while(!feof($in))
    {
        $line = fgets($in);
        $start = substr($line, 0, 6);
        // Things before the first MODEL go in $headers,
        // and things after go in $footers.
        if($start == 'MODEL ')
        {
            $keeplines = false;
            $sink =& $footers;
        }
        elseif($start == 'ENDMDL')
        {
            $keeplines = true;
        }
        elseif($keeplines) $sink[] = $line;
    }
    fclose($in);
    
    // Part 2: write the non-(header, footer) parts to separate files.
    // Every model gets all headers and footers, plus its ATOMs, etc.
    $modelFiles = array();
    $keeplines = false;
    $in = fopen($infile,"rb");
    while(!feof($in))
    {
        $line = fgets($in);
        $start = substr($line, 0, 6);
        // Things before the first MODEL go in $headers,
        // and things after go in $footers.
        if($start == 'MODEL ')
        {
            $keeplines = true;
            $num = trim(substr($line, 5, 20)) + 0;
            
            $tmpFile = mpTempfile("tmp_pdb_");
            $modelFiles[$num] = $tmpFile;
            $out = fopen($tmpFile, "wb");
            foreach($headers as $h) fwrite($out, $h);
            fwrite($out, sprintf("REMARK  99 MODEL     %4d                                                       \n", $num));
        }
        elseif($start == 'ENDMDL')
        {
            $keeplines = false;
            
            fwrite($out, "REMARK  99 ENDMDL                                                               \n");
            foreach($footers as $f) fwrite($out, $f);
            fclose($out);
        }
        elseif($keeplines) fwrite($out, $line);
    }
    fclose($in);
    
    return $modelFiles;
}
#}}}########################################################################

#{{{ joinPdbModels - joins split PDBs into one multi-MODEL PDB file
############################################################################
/**
* Given an array of PDB format files, this function merges them back together
* into a reasonably coherent multi-model PDB file.
* The order of models in the array is taken as their desired order in the file.
* If the array keys would make sensible model numbers, they are used;
* else the models are numbered 1 to N.
* Returns a temp file name holding the joined models.
*/
function joinPdbModels($infiles)
{
    // Part 1: Scan all files to find all unique headers / footers
    $headers = array();     // REMARKs, etc.
    $footers = array();     // CONECTs, etc.
    
    foreach($infiles as $infile)
    {
        //Open PDB file, read line by line
        $in = fopen($infile,"rb");
        while(!feof($in))
        {
            // We decide based on record type rather than REMARK'd MODEL/ENDMDL
            // records b/c this could be used to join xtalographic data sets too.
            $line = fgets($in);
            if(preg_match('/^(ATOM|HETATM|TER|ANISOU|REMARK  99 MODEL|REMARK  99 ENDMDL)/', $line)) continue;
            elseif(preg_match('/^(CONECT|MASTER|END)/', $line))
                $footers[$line] = $line; // ensures each unique line appears only once
            else
                $headers[$line] = $line; // ensures each unique line appears only once
        }
        fclose($in);
    }
    
    // Part 2: Should we number models using the array keys?
    $useKeysAsModelNumbers = true;
    foreach($infiles as $key => $dummy)
    {
        $key = $key + 0;
        $useKeysAsModelNumbers =
            $useKeysAsModelNumbers && $key > 0 && is_int($key);
    }
    
    // Part 3: Re-scan all files and write their contents, in order.
    $tmpFile = mpTempfile("tmp_pdb_");
    $out = fopen($tmpFile, "wb");
    foreach($headers as $h) fwrite($out, $h);
    
    $i = 1;
    foreach($infiles as $key => $infile)
    {
        //Open PDB file, read line by line
        $in = fopen($infile,"rb");
        $modelNum = ($useKeysAsModelNumbers ? $key : $i);
        fwrite($out, sprintf("MODEL     %4d                                                                  \n", $modelNum));
        while(!feof($in))
        {
            // We decide based on record type rather than REMARK'd MODEL/ENDMDL
            // records b/c this could be used to join xtalographic data sets too.
            $line = fgets($in);
            if(preg_match('/^(ATOM|HETATM|TER|ANISOU)/', $line))
                fwrite($out, $line);
        }
        fwrite($out, "ENDMDL                                                                          \n");
        fclose($in);
        $i++;
    }
    
    foreach($footers as $f) fwrite($out, $f);
    fclose($out);
    
    return $tmpFile;
}
#}}}########################################################################

#{{{ convertModelsToChains - converts biol. unit MODELs to unique chain IDs
############################################################################
/**
* Takes a PDB biological unit file, where copies of the asym. unit are stored
* as MODEL records, and converts it into a "flat" PDB file by remapping the
* chain IDs.
* Returns a temp file with the remapped chain IDs.
*/
function convertModelsToChains($infile)
{
    // Generate a set of all possible usable IDs.
    // These will be removed as they're used.
    $possibleIDs = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz ";
    $unusedIDs = array();
    for($i = 0; $i < strlen($possibleIDs); $i++)
        $unusedIDs[ $possibleIDs{$i} ] = $possibleIDs{$i};
    
    // Maps old chain IDs to new chain IDs. Mappings are pulled from $unusedIDs.
    // This is cleared out every time a new MODEL is encountered.
    $idmap = array();
    $tmpFile = mpTempfile("tmp_pdb_");
    $out = fopen($tmpFile, "wb");
    $in = fopen($infile, "rb");
    while(!feof($in))
    {
        $line = fgets($in);
        if(preg_match('/^(ATOM|HETATM|TER|ANISOU)/', $line) && strlen($line) >= 22)
        {
            $cid = $line{21};
            if(!isset($idmap[$cid]))
            {
                if(isset($ununsedIDs[$cid]))    $idmap[$cid] = $unusedIDs[$cid];
                else                            $idmap[$cid] = reset($unusedIDs);
                unset($unusedIDs[ $idmap[$cid] ]);
            }
            $line{21} = $idmap[$cid];
            fwrite($out, $line);
        }
        elseif(preg_match('/^(MODEL|ENDMDL)/', $line))
        {
            $idmap = array();
            //echo "reset matching\n";
        }
        else fwrite($out, $line);
    }
    fclose($in);
    fclose($out);
    
    return $tmpFile;
}
#}}}########################################################################

#{{{ removeChains - deletes specified chains from a PDB file
############################################################################
/**
* Takes an array of chain IDs to delete. Blank may be given as _
*/
function removeChains($inpath, $outpath, $idsToRemove)
{
    $ids = "";
    foreach($idsToRemove as $id)
    {
        if($id == '_') $id = ' ';
        $ids .= $id;
    }
    
    if(strlen($ids) == 0)
    {
        copy($inpath, $outpath);
        return;
    }
    
    $in = fopen($inpath, 'rb');
    $out = fopen($outpath, 'wb');
    $regex = "/^(ATOM  |HETATM|TER   |ANISOU|SIGATM|SIGUIJ).{15}[$ids]/";
    while(!feof($in))
    {
        $s = fgets($in, 4096);
        if(! preg_match($regex, $s))
            fwrite($out, $s);
    }
    fclose($out);
    fclose($in);
}
#}}}########################################################################

#{{{ downgradePDB - converts a pdb to PDB v2.3
############################################################################
/**
* Converts a Pdb to PDB v2.3 format
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
*/
function downgradePDB($inpath, $outpath)
{
    exec("remediator.pl -oldout $inpath > $outpath");
}
#}}}########################################################################

#{{{ autoFix - correct misfit Leu residues
############################################################################
/**
* Correct misfit Leu residues
*
* $inpdb             the full filename for the PDB file to be processed
* $map               the full filename of the input map file
* $temp_dir          the directory where 'temporary files' we will access later will be written
* $_MP_BASE_DIR_     the molprobity base directory
* $autofix_out_log   the full filename for the output logfile. Will be overwritten.
* $modelOutpath      the full filename for the output pdb
*
* coot doesn't work well with hydrogens do some goofiness to temporarily remove them 
*
*/
function autoFix($inpdb, $map, $temp_dir, $_MP_BASE_DIR_, $autofix_out_log, $modelOutpath)
{
    $tmppdb = mpTempfile("tmp_pdb_");
    $tmppdbTrim = mpTempfile("tmp_pdb_trim_");
    exec("cp ".$inpdb." ". $tmppdb); 
    reduceTrim($inpdb, $tmppdbTrim); 
    exec("cp ".$tmppdbTrim." ".$inpdb);
    exec("vtlr_fixes_auto.pl -decoy $inpdb $map $temp_dir $_MP_BASE_DIR_ $modelOutpath > $autofix_out_log");
//echo "vtlr_fixes_auto.pl -decoy $inpdb $map $temp_dir $_MP_BASE_DIR_ $modelOutpath > $autofix_out_log"; 
    exec("cp ".$tmppdb." ".$inpdb);
    unlink($tmppdb);
    unlink($tmppdbTrim);
}
#}}}########################################################################

#{{{ reduceTrim - removes H from a PDB file
############################################################################
/**
* Removes all ATOM and HETATM records for hydrogens.
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
*/
function reduceTrim($inpath, $outpath)
{
    // USER  MOD is fatal to Coot and other programs, so we strip them ALL.
    exec("reduce -quiet -trim -allalt $inpath | awk '\$0 !~ /^USER  MOD/' > $outpath");
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
function reduceNoBuild($inpath, $outpath)
{
    // Add missing H's without trying to optimize or fix anything
    // $_SESSION[hetdict] is used to set REDUCE_HET_DICT environment variable,
    // so it doesn't need to appear on the command line here.
    // High penalty means no flips happen, but they must be considered to get networks right.
    // "-build" is these 3 plus -rotexoh:         /------------\
    //exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -oh -his -flip -pen9999 -keep -allalt $inpath > $outpath");
    exec("reduce -quiet -oh -his -flip -pen9999 -keep -allalt $inpath > $outpath");
}
#}}}########################################################################

#{{{ reduceBuild - adds H and optimizes H-bond networks
############################################################################
/**
* This is the standard, expert-system way of adding required hydrogens for AAC.
* The input file or its ancestor should have already been passed thru preparePDB().
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
*/
function reduceBuild($inpath, $outpath)
{
    // $_SESSION[hetdict] is used to set REDUCE_HET_DICT environment variable,
    // so it doesn't need to appear on the command line here.
    //exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -build -allalt $inpath > $outpath");
    exec("reduce -quiet -build -allalt $inpath > $outpath");
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
    // $_SESSION[hetdict] is used to set REDUCE_HET_DICT environment variable,
    // so it doesn't need to appear on the command line here.
    //exec("reduce -quiet -limit".MP_REDUCE_LIMIT." -build -fix $flippath -allalt $inpath > $outpath");
    exec("reduce -quiet -build -fix $flippath -allalt $inpath > $outpath");
}
#}}}########################################################################

#{{{ autoFixRerun - re-runs Autofix with user-selected flip 
############################################################################
/** 
* This is the user-customized way of fixing sidechains with AutoFix. 
* The input file or its ancestor should have already been passed thru preparePDB(),
* and it may or may not have hydrogens added.
*
* $partentPDB      the full filename for the original PDB.
* $autoFixPdb      the full filename for the original AutoFix modified PDB. 
*                  It will be overwritten. Grab the UserMOD records first.
* $autoFixUserMod  USER  MOD FIX records for fixed file
* $newcootScript   the file listing residues to fix and their orientations, in
*                  the appropriate format for coot 
*      
* function autoFixRerun($autoFixPdb, $newcootScript, $autoFixUserMod)
*/
function autoFixRerun($parentPDB, $autoFixPdb, $newcootScript, $autoFixUserMod)
{       
    exec("cp ".$autoFixPdb." ".$autoFixPdb."_default");
    $tmppdb = mpTempfile("tmp_pdb_");
    exec("cp ".$parentPDB." ". $tmppdb);
    reduceTrim($tmppdb, $parentPDB);
    exec("coot --no-guano -s ".$newcootScript." --no-graphics");
    exec("cat ".$autoFixUserMod." ".$autoFixPdb." > ".$tmppdb);
    exec("mv ".$tmppdb." ".$autoFixPdb);
}       
#}}}########################################################################

#{{{ reduceEnsemble - runs Reduce on all models to make a new ensemble
############################################################################
/**
* Calling this function creates N new models with hydrogens and then
* reassembles them into one new ensemble with H.
*
* Returns the ID of the newly minted ensemble.
*
* $ensID        the ensemble ID for the ensemble to reduce
* $reduceFunc   the function to run on ensemble members: one of
*   'reduceBuild', 'reduceNoBuild', or 'reduceTrim'
*/
function reduceEnsemble($ensID, $reduceFunc = 'reduceNoBuild')
{
    $ens        = $_SESSION['ensembles'][$ensID];
    $idList     = array();
    $pdbList    = array();
    
    foreach($ens['models'] as $modelNum => $modelID)
    {
        $oldModel   = $_SESSION['models'][$modelID];
        $newModel   = createModel($modelID."H");
        $outname    = $newModel['pdb'];
        $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
        if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
        $outpath    .= '/'.$outname;
        $inpath     = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$oldModel['pdb'];
        // FUNKY: calls the function whose name is stored in the variable $reduceFunc
        $reduceFunc($inpath, $outpath);
        
        $newModel['stats']          = pdbstat($outpath);
        $newModel['parent']         = $modelID;
        $newModel['history']        = "Derived from $oldModel[pdb] by $reduceFunc";
        $newModel['isUserSupplied'] = $oldModel['isUserSupplied'];
        $newModel['isReduced']      = ($reduceFunc != 'reduceTrim');
        $newModel['isBuilt']        = ($reduceFunc == 'reduceBuild');
        $_SESSION['models'][ $newModel['id'] ] = $newModel;
        
        $idList[$modelNum]  = $newModel['id'];
        $pdbList[$modelNum] = $outpath;
    }
    
    $ensemble = createEnsemble($ensID."H");
    $ensemble['models']     = $idList;
    $ensemble['history']    = "Ensemble of ".count($idList)." models derived from $ens[pdb] by $reduceFunc";
    $newModel['isReduced']  = ($reduceFunc != 'reduceTrim');
    $newModel['isBuilt']    = ($reduceFunc == 'reduceBuild');
    $ensemble['isUserSupplied'] = $ens['isUserSupplied'];
    
    $joinedModel = joinPdbModels($pdbList);
    copy($joinedModel, $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$ensemble['pdb']);
    unlink($joinedModel);
    
    // Create the ensemble entry
    $id = $ensemble['id'];
    $_SESSION['ensembles'][$id] = $ensemble;
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
        #if( eregi("^USER  MOD........:......(ASN|GLN|HIS)", $s) )
        if(preg_match('/^USER  MOD (Set|Single).*?:.{7}(ASN|GLN|HIS)/i', $s))
        {
            //echo "found user mod ".$s;
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
            
            // Better to group ins code with number than res type.
            // I don't *think* anything depended on the old behavior.
            // CNIT:       C  NNNNI  TTT
            preg_match('/^.(.)(.....)(...)/', $field[1], $f1);
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

#{{{ decodeAutoFixUsermods - interpret USERMOD records from AutoFix PDB files
############################################################################
/**
* Read in $autoFixStats until we reach an ATOM record.
* Look for (*) or (^) which mark records that were fixed or attempted respectively
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
*  is the index in the array, and isn't recorded as a column.
*
*  diff_rama is the sum of the ramachandran score differences for residues n-1, n and n+1   current cutoff is > -30   is accepted
*  diff_rota is the fractional change (old_rota_score-new_rota_score)/(old_rota_score)      current cutoff is >  -0.1 is accepted
*  diff_cb   is (new_cb-old_cb); cb is binned outliers ( >= 0.25 ) are designated as 0      current cutoff is <   0   is rejected
*  diff_clash is the straight difference in clashlist score (greatest overlap per residue)  current cutoff is >  -0.1 is rejected
*  diff_rotated is the difference in CHI1 for V,T,S,C and CHI2 for L                        current cutoff is >  10   is accepted
*    smaller rotations are only excepted if diff_rama > 10 and diff_rota > -0.1
* now, positive differences in rota and rama are good  * are fixed residues ^ are attempted
*
*   DIFF    : rama : rota :  cb  :clash :rot'd1:rot'd2:rot'd3:rot'd4:guan_a:         FLIP DECISION          :
* A  27 LEU*:  -5.3: 222.5:   0.0: 0.000:  51.8: 144.2:   0.0:   0.0:      :         FLIP ACCEPTED          :
*:    ORIG     : rama bin:  rota   (type) :  cb  :clash :chi1  :chi2  :chi3  :chi4  :
*              :  40.8(2):   0.2 (OUTLIER):   1.0: 0.000: 231.2:  24.2:   0.0:   0.0:         
*   :    FIXED : rama bin:  rota   (type) :  cb  :clash :chi1  :chi2  :chi3  :chi4  :
*              :  37.2(2):  44.7      (mt):   1.0: 0.000: 283.0: 168.4:   0.0:   0.0
* Columns and their meanings are as follows:
*
*   $field[0]
* $changes[0]      the CNIT marked with * or ^ where * are fixed residues ^ are attempted  (only * are listed in USER  MODs) in the _junk file all residues are listed
* $changes[1]      the chain ID, if any
* $changes[2]      the residue number
* $changes[3]      ins code
* $changes[4]      the residue ID (type), e.g. ASN, GLN, HIS, etc.
*
* $changes[5]   field[1]    diff_rama  score: the sum of the ramachandran score difference for residues n-1, n and n+1    current cutoff is > -30  is accepted
* $changes[6]   field[2]    diff_rota  score: the fractional change (old_rota_score-new_rota_score)/(old_rota_score)      current cutoff is > -0.1 is accepted
* $changes[7]   field[3]    diff_cb    score: binned outliers ( >= 0.25 ) are designated as 0                             current cutoff is <  0   is rejected
* $changes[8]   field[4]    diff_clash score: the straight difference in clashlist score ( greatest overlap per residue)  current cutoff is > -0.1 is rejected
* $changes[9]   field[5]    diff_probe: the straight difference in probe score 
* $changes[10]  field[6]    diff_hb:  the straight difference in sc_hb score from probe unformatted
* $changes[11]  field[7]    diff_rsc   score: the straight difference in rsc score                                        current cutoff is >  0   is accepted
* $changes[12]  field[8]    chi1 diff_rotated score: is the change in CHI1
* $changes[13]  field[9]    chi2 diff_rotated score: is the change in CHI2
* $changes[14]  field[10]   chi3 diff_rotated score: is the change in CHI3
* $changes[15]  field[11]   chi4 diff_rotated score: is the change in CHI4
*                             current cutoff is > 10 is accepted   *NOTE: smaller rotations are accepted if diff_rama < -10 and diff_rota < 0.1
* $changes[16]  field[12]   guan_flip score : a measure of the orientation of the gunadinium group of ARG (angle between gunadinium normals)                 current cutoff is within 30 of 180
* $changes[17]  field[13]   flip decision
* $changes[18]              shorter version of [17] 
*
* $changes[19]  field[15]   orig_rama   score 
* $changes[20]  field[16]   orig_rota   score 
* $changes[21]  field[17]   orig_cb     score 
* $changes[22]  field[18]   orig_clash  score
* $changes[23]  field[19]   orig_probe  score
* $changes[24]  field[20]   orig_hb     score
* $changes[25]  field[21]   orig_rsc    score
*
* $changes[26]  field[27]   fixed_rama  score 
* $changes[27]  field[28]   fixed_rota  score 
* $changes[28]  field[29]   fixed_cb    score 
* $changes[29]  field[30]   fixed_clash score
* $changes[30]  field[31]   fixed_probe score
* $changes[31]  field[32]   fixed_hb    score
* $changes[32]  field[33]   fixed_rsc   score
* $changes[33]              Score that caused rejection
* $changes[34][0]           Improvement log
*/
function decodeAutoFixUsermods($file,$pdb)
{
    $fp = fopen($file, "r");
    $c                 = 0;      // Track number of flips and clashes found (Counter)
    $origRotaOutlier   = 0;      // Track Rotamer Outliers  (OUTLIER)
    $fixedRotaOutlier  = 0;
    $origRamaOutlier   = 0;      // Track Ramachandran Outliers  (bin is (0))
    $fixedRamaOutlier  = 0;
    $origCbetaOutlier  = 0;      // Track Cbeta Outliers    (0.0)
    $fixedCbetaOutlier = 0;
    $nlines            = 0;      // Track number of lines read, for debugging

    while( !feof($fp) and ($s = fgets($fp, 300)) )
    {
        // Look for fixes marked by autoFix
        if(!eregi("^\#", $s) and (eregi("\*",substr($s,9,1)) || eregi("\^",substr($s,9,1))) ) 
        {
            // echo "found fixed or attempted record";
            // Break it down into colon-delimited fields.
            // There are 35 fields -  CNIT:diff_rama:diff_rota:diff_cb:diff_clash:diff_probe:diff_hb:diff_rsc:
            //                         diff_rotated(chi1):diff_rotated(chi2):diff_rotated(chi3):diff_rotated(chi4):
            //                         guan_angle: explanation:
            //                         ORIG:orig_rama:orig_rota:orig_cb:orig_clash:orig_probe:orig_hb:orig_rsc:chi1:chi2:chi3:chi4:
            //                         FIXED:fixed_rama:fixed_rota:fixed_cb:fixed_clash:fixed_probe:fixed_hb:fixed_rsc:chi1:chi2:chi3:chi4
            // A  27 LEU*:  -5.3: 222.5:   0.0: 0.000:  51.8: 144.2:   0.0:   0.0:      :         FLIP ACCEPTED          :
            //           :  40.8(2):   0.2 (OUTLIER):   1.0: 0.000: 231.2:  24.2:   0.0:   0.0:
            //           :  37.2(2):  44.7      (mt):   1.0: 0.000: 283.0: 168.4:   0.0:   0.0

            $field = explode(":", $s);

            // Most values can be done without knowing whether or not this is a flip.
            $changes[0][$c] = $field[0];  // CNIT
            
            // Better to group ins code with number than res type.
            // I don't *think* anything depended on the old behavior.
            // CNIT:       CNNNNITTT
            preg_match('/^(.)(....)(.)(...)(.)/', $field[0], $f1);
            $changes[1][$c]  = trim($f1[1]);   // chain
            $changes[2][$c]  = trim($f1[2]);   // resid
            $changes[3][$c] = $f1[3];          // ins code 
            $changes[4][$c]  = trim($f1[4]);   // type
            // skip 4 and 5 for now
                                         // DIFF
            $changes[5][$c]  = $field[1];   // rama
            $changes[6][$c]  = $field[2];   // rota
            $changes[7][$c]  = $field[3];   // cb 
            $changes[8][$c]  = $field[4];   // clash
            $changes[9][$c]  = $field[5];   // probe
            $changes[10][$c] = $field[6];   // hb
            $changes[11][$c] = $field[7];   // rsc
            $changes[12][$c] = $field[8];   // chi1
            $changes[13][$c] = $field[9];   // chi2 
            $changes[14][$c] = $field[10];  // chi3
            $changes[15][$c] = $field[11];  // chi4
            $changes[16][$c] = $field[12];  // guan_angle

            $changes[17][$c] = $field[13];  // flip decision
            $didflip = eregi("\*", $f1[5]);

    // orig quality stats
            $changes[19][$c] = $field[15];  // rama   [outlier has bin (0)]
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/\(0\)/', $field[15])) {  $origRamaOutlier++; }
            $changes[20][$c] = $field[16];  // rota   [outlier is marked (OUTLIER)]
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/\(OUTLIER\)/', $field[16])) {  $origRotaOutlier++; }
            $changes[21][$c] = $field[17];  // cbeta  [outlier is scored 0.0]
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/0.0/', $field[17])) {  $origCbetaOutlier++; }
            $changes[22][$c] = $field[18];  // clash  [outlier is < -0.4]
            $changes[23][$c] = $field[19];  // probe
            $changes[24][$c] = $field[20];  // hb 
            $changes[25][$c] = $field[21];  // rsc    [reject if < 0]

               if     (preg_match('/\(OUTLIER\)/', $field[16])) { $changes[18][$c] = "ROTA"; }
               elseif (preg_match('/\(0\)/', $field[15]))       { $changes[18][$c] = "RAMA"; }
               elseif (preg_match('/0.0/', $field[17]))         { $changes[18][$c] = "CBETA"; } 
               elseif ($field[18] > -0.4)                       { $changes[18][$c] = "CLASH"; }   
               else                                             { $changes[18][$c] = " "; }

    // fixed quality stats
            $changes[26][$c] = $field[27];  // rama
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/\(0\)/', $field[27])) {  $fixedRamaOutlier++; }
            $changes[27][$c] = $field[28];  // rota
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/\(OUTLIER\)/', $field[28])) {  $fixedRotaOutlier++; }
            $changes[28][$c] = $field[29];  // cbeta
               if (preg_match('/LEU/', $changes[4][$c]) and $didflip and preg_match('/0.0/', $field[29])) {  $fixedCbetaOutlier++; }
            $changes[29][$c] = $field[30];  // clash
            $changes[30][$c] = $field[31];  // probe
            $changes[31][$c] = $field[32];  // hb 
            $changes[32][$c] = $field[33];  // rsc

    // get score that caused rejection

            if     (preg_match('/ramachandran/', $field[13])) {  $changes[33][$c] = "Rama score ".$field[1];    }
            elseif (preg_match('/rotamer/', $field[13]))      {  $changes[33][$c] = "Rota score ".$field[2];    }
            elseif (preg_match('/C\(beta\)/', $field[13]))    {  $changes[33][$c] = "C(beta) score ".$field[3]; }
            elseif (preg_match('/clash/', $field[13]))        {  $changes[33][$c] = "Clash score ".$field[4];   }
            elseif (preg_match('/move/', $field[13]) and preg_match('/[VAL|THR]/', $changes[4][$c]))
               {  $changes[33][$c] = "Chi1 only moved ".$field[8]." degrees"; }
            elseif (preg_match('/move/', $field[13]) and preg_match('/LEU/', $changes[4][$c]))
               {  $changes[33][$c] = "Chi2 only moved ".$field[9]." degrees"; }
            elseif (preg_match('/rsc/', $field[13]))          {  $changes[33][$c] = "RSC score ".$field[7];      }
            elseif (preg_match('/Arg/', $field[13]) and preg_match('/ARG/', $changes[4][$c]))
               {  $changes[33][$c] = "The guanidinium only rotated ".$field[12]." degrees"; }
            else {  $changes[33][$c] = " "; }

            $changes[34][$c] = " ";

            $c++;
        }

        $nlines++;
    }
    
    $RamaImproved  = $origRamaOutlier  - $fixedRamaOutlier;       // Number of Ramachandran Outliers fixed
    $RotaImproved  = $origRotaOutlier  - $fixedRotaOutlier;       // Number of Rotamer Outliers fixed
    $CbetaImproved = $origCbetaOutlier - $fixedCbetaOutlier;      // Number of Cbeta Outliers fixed
//    $ClashImproved = $origClashscore   - $fixedClashscore;        // Improvement in Clashscore

        $improvementList = array();   

       if($RotaImproved > 0)
           $improvementList[] = "fix ".($RotaImproved)." rotamer outlier(s)";
       if($RamaImproved > 0)
           $improvementList[] = "fix ".($RamaImproved)." ramachandran outlier(s)";
       if($CbetaImproved > 0)
           $improvementList[] = "fix ".($CbetaImproved)." C(beta) outlier(s)";
//       if($ClashImproved > 0)
//           $improvementList[] = "improve your clashscore by ".($ClashImproved)." points";

   if(count($improvementList) > 0)
   {
       $improveText .= "<div class='feature'>By accepting the default fixes from AutoFix on this model, you will ";
       $improveText .= implode(" and ", $improvementList);
       $changes[34][0] = $improveText; 
   }

    fclose($fp);

    // If we found any fixes, sort the list.
    if( isset($changes))
    {
        // Sort table. BE SURE to list ALL COLUMNS here, or data will be scrambled!!!
        array_multisort($changes[0],  $changes[1],  $changes[2],  $changes[3],  $changes[4],
                        $changes[5],  $changes[6],  $changes[7],  $changes[8],  $changes[9],
                        $changes[10], $changes[11], $changes[12], $changes[13], $changes[14],
                        $changes[15], $changes[16], $changes[17], $changes[18], $changes[19],
                        $changes[20], $changes[21], $changes[22], $changes[23], $changes[24],
                        $changes[25], $changes[26], $changes[27], $changes[28], $changes[29],
                        $changes[30], $changes[31], $changes[32], $changes[33], $changes[34]);
    }

    return $changes;
}
#}}}########################################################################

#{{{ countReduceChanges - counts H found/std/added/removed/adjusted by Reduce
############################################################################
/**
* Returns an array with the following keys, or null if no USER MOD found.
*   found       number of H found in starting model (?)
*   std         number of existing bond lengths standardized (?)
*   add         number of H added to model in this pass (?)
*   rem         number of H removed from model in this pass (?)
*   adj         number of H repositioned by optimizations in this pass (?)
*/
function countReduceChanges($pdbfile)
{
    $in = fopen($pdbfile, 'rb');
    if($in) while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(preg_match('/^USER  MOD +reduce.+?found=(\d+).+?std=(\d+).+?add=(\d+).+?rem=(\d+).+?adj=(\d+)/', $s, $fields))
        {
            $ret = array(
                'found' => $fields[1],
                'std'   => $fields[2],
                'add'   => $fields[3],
                'rem'   => $fields[4],
                'adj'   => $fields[5]
            );
            break;
        }
    }
    fclose($in);
    return $ret;
}
#}}}########################################################################

#{{{ getPdbModel - retrieves a model from the Protein Data Bank
############################################################################
/**
* Retrieves the PDB file with the given code from www.rcsb.org
*   pdbcode     the 4-character code identifying the model
* Returns the name of a temporary file, or null if download failed.
*/
function getPdbModel($pdbcode, $biolunit = false)
{
    // I think the PDB website is picky about case
    $pdbcode = strtoupper($pdbcode);

    // Copy in the newly uploaded file:
    if($biolunit)
        //$src = "ftp://ftp.rcsb.org/pub/pdb/data/biounit/coordinates/all/".strtolower($pdbcode).".pdb1.gz";
        $src = "http://www.pdb.org/pdb/files/".strtolower($pdbcode).".pdb1.gz";
    else
        //$src = "http://www.rcsb.org/pdb/cgi/export.cgi/$pdbcode.pdb?format=PDB&pdbId=$pdbcode&compression=gz";
        $src = "http://www.pdb.org/pdb/files/".strtolower($pdbcode).".pdb.gz";
        
    $outpath = mpTempfile("tmp_pdb_");
    if(copy($src, $outpath) && filesize($outpath) > 1000)
    {
        $outpath2 = mpTempfile("tmp_pdb_");
        exec("gunzip -c < $outpath > $outpath2"); // can't just gunzip without a .gz ending
        unlink($outpath);
        // Convert MODELs to chain IDs
        if($biolunit)
        {
            $outpath = convertModelsToChains($outpath2);
            unlink($outpath2);
            return $outpath;
        }
        else return $outpath2;
    }
    else
    {
        if(file_exists($outpath)) unlink($outpath);
        return null;
    }
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
    $webpage = mpTempfile("tmp_html_");
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
    $Zfile = mpTempfile("tmp_pdbZ_");
    if(!copy($url, $Zfile) || filesize($Zfile < 1000))
    {
        if(file_exists($Zfile)) unlink($Zfile);
        return null;
    }
    
    // Use gunzip to decompress it
    // -S forces gunzip to recognize file by magic number rather than .Z ending
    $outpath = mpTempfile("tmp_pdb_");
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

#{{{ getEdsMtz - retrieves a mtz from the Electron Density Server at Uppsala
############################################################################
/**
* Retrieves a mtz for the given PDB code from the Uppsala EDS
*   pdbcode     the 4-character code identifying the model
* Returns the name of a temporary file, or null if download failed.
*/
function getEdsMtz($pdbcode)
{
    $pdbcode = strtolower($pdbcode);
    $pdbcode23 = substr($pdbcode, 1,2); 

    $server = 'eds.bmc.uu.se/eds';
    $url = 'http://'.$server.'/dfs/'.$pdbcode23.'/'.$pdbcode.'/'.$pdbcode.'_sigmaa.mtz';
    // Copy the file over the network in 8k chunks
    $in = fopen($url, 'r');
    $outpath = mpTempfile("tmp_map_");
    $out = fopen($outpath, 'w');
    if($in) while(!feof($in))
    {
        fwrite($out, fread($in, 8192));
    }
    else 
    {
        echo "No $type map available for $pdbcode.\n";
        return null;
    }
    fclose($out);
    fclose($in);

    return $outpath;
}
#}}}########################################################################

#{{{ getEdsMap - retrieves a map from the Electron Density Server at Uppsala
############################################################################
/**
* Retrieves a map for the given PDB code from the Uppsala EDS
*   pdbcode     the 4-character code identifying the model
*   format      omap, ccp4, cns, or ezd
*   type        2fofc or fofc
* Returns the name of a temporary file, or null if download failed.
*/
function getEdsMap($pdbcode, $format, $type)
{
    $pdbcode = strtolower($pdbcode);
    $pdbcode23 = substr($pdbcode, 1,2);

/*    
    // Create POST arguments to the HTML form
    $args = array(
        'pdbCode'       => $pdbcode,
        'page'          => 'create',
        'mapformat'     => $format,
        'maptype'       => $type
    );
    foreach($args as $key => $value) $postfields[] = urlencode($key) . '=' . urlencode($value);

    // Set CURL options
    //$server = 'fsrv1.bmc.uu.se';
    $server = 'eds.bmc.uu.se';
    $c = curl_init("http://$server/eds/cgi-bin/gen_maps_zip.pl");
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true); // don't write results to stdout
    //curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($c, CURLOPT_TIMEOUT, 60);
    //curl_setopt($c, CURLOPT_HEADER, true);
    //curl_setopt($c, CURLOPT_NOBODY, true);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $postfields));
    
    // Retrieve the web page from EDS
    $page = curl_exec($c);
    // Can't get these once we close the connection:
    $curlErrno = curl_errno($c);
    $curlError = curl_error($c);
    curl_close($c);

    // Check for errors and find the file name
    if($curlErrno)                                      // network failure
    {
        echo "CURL error: $curlError\n";
        return null;
    }
    if(preg_match('/error/i', $page))                   // no map/xtal data available
    {
        echo "No $type map available for $pdbcode.\n";
        return null;
    }
    if(!preg_match('/<a href="(.+?)">/i', $page, $m))    // unknown failure
    {
        echo "EDS page has no link on it!\n";
        return null;
    }
    $url = "http://{$server}{$m[1]}";
*/
    $server = 'eds.bmc.uu.se/eds';

    if     (eregi('2fofc', $type)) { $url = 'http://'.$server.'/dfs/'.$pdbcode23.'/'.$pdbcode.'/'.$pdbcode.'.omap'; }
    elseif (eregi('fofc',  $type)) { $url = 'http://'.$server.'/dfs/'.$pdbcode23.'/'.$pdbcode.'/'.$pdbcode.'_diff.omap'; }
    
    // Copy the file over the network in 8k chunks
    $in = fopen($url, 'r');
    $outpath = mpTempfile("tmp_map_");
    $out = fopen($outpath, 'w');
    if($in) while(!feof($in))
    {
        fwrite($out, fread($in, 8192));
    }
    else 
    {
        echo "No $type map available for $pdbcode.\n";
        return null;
    }
    fclose($out);
    fclose($in);
    
    return $outpath;
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
    #echo(count($tmp));
    #print_r($tmp);
    for($i = 0; $i < count($tmp); $i += 2)
        $map[strtoupper($tmp[$i])] = strtoupper($tmp[$i+1]);
    #print_r($map);
    
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

#{{{ replacePdbRemark - used for inserting REMARK  40
############################################################################
/**
* Inserts the given block of text into the named PDB file.
*/
function replacePdbRemark($inpath, $remarkText, $remarkNumber)
{
    // Make a copy of the starting PDB file into the tmp dir
    $outpath = $inpath;
    $inpath = mpTempfile("tmp_remark_");
    copy($outpath, $inpath);
    // Open input and output streams
    $in = fopen($inpath, 'r');
    $out = fopen($outpath, 'w');
    // Copy headers and remarks that precede ours...
    while(!feof($in))
    {
        $line = fgets($in, 1024);
        $start = substr($line, 0, 6);
        if($start == 'REMARK')
        {
            $num = substr($line, 7, 3) + 0;
            if($num >= $remarkNumber)
            {
                if($num == $remarkNumber)
                    $line = null; // mark line as written -- we don't want to write it later!
                break;
            }
            else
            {
                fwrite($out, $line);
                $line = null; // mark line as written
            }
        }
        elseif($start == 'USER  ' || $start == 'HEADER' || $start == 'OBSLTE' || $start == 'TITLE ' || $start == 'CAVEAT'
        || $start == 'COMPND' || $start == 'SOURCE' || $start == 'KEYWDS' || $start == 'EXPDTA' || $start == 'AUTHOR'
        || $start == 'REVDAT' || $start == 'SPRSDE' || $start == 'JRNL  ')
        {
            fwrite($out, $line);
            $line = null; // mark line as written
        }
        else break; // abort loop for any other type of record
    }
    // Write our remark
    fwrite($out, $remarkText);
    // Write the line that made us break, if applicable:
    if($line) fwrite($out, $line);
    // Copy remaining records, skipping any with our same remark number
    while(!feof($in))
    {
        $line = fgets($in, 1024);
        $start = substr($line, 0, 6);
        if($start == 'REMARK')
        {
            $num = substr($line, 7, 3) + 0;
            if($num == $remarkNumber) continue;
        }
        fwrite($out, $line);
    }
    // Close streams
    fclose($out);
    fclose($in);
    // Remove duplicate of original file
    unlink($inpath);
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
