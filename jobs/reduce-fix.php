<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the user-configured Reduce -build command on an existing
    model in this session and creates a new PDB in this model.
    
INPUTS (via $_SESSION['bgjob']):
    model           ID code for model to process
    doflip[]        an array of booleans, where the keys match the second index
                    in the data structure from decodeReduceUsermods()

OUTPUTS (via $_SESSION['bgjob']):
    model           ID code for model that was processed

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!
    
#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
$doflip = $_SESSION['bgjob']['doflip'];
$modelID = $_SESSION['bgjob']['model'];
$model = $_SESSION['models'][$modelID];
$pdb = "$model[dir]/$model[pdb]";

$changes = decodeReduceUsermods($pdb);

// If all changes were accepted, we will not need to re-run Reduce.
$rerun = false;
// Make a file of flip-noflip commands for Reduce
$flipfile = "$model[dir]/$model[prefix]fix.flips";
$fp = fopen($flipfile, "wb");
$n = count($changes[0]); // How many changes are in the table?
for($c = 0; $c < $n; $c++)
{
    if($doflip[$c]) fwrite($fp, "F:" . $changes[0][$c] . "\n");
    else            fwrite($fp, "O:" . $changes[0][$c] . "\n");

    // Expect checks for ones flipped originally; expect no check for ones not flipped.
    $expected = ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL");
    if($doflip[$c] != $expected) { $rerun = TRUE; }
}
fclose($fp);

if(! $rerun)
    setProgress(array("No additional changes made to model"), null);
else
{
    $tasks['reduce'] = "Add H with user-selected Asn/Gln/His flips using <code>reduce -fix</code>";
    setProgress($tasks, 'reduce');
    
    $outname = "$model[id]fixH.pdb";
    $outpath = "$model[dir]/$outname";
    
    // input should be from parent model or we'll be double flipped!
    $parentID = $model['parent'];
    $parent = $_SESSION['models'][$parentID];
    $parentPDB = "$parent[dir]/$parent[pdb]";
    if(file_exists($parentPDB))
    {
        reduceFix($parentPDB, $outpath, $flipfile);
        // new PDB is part of same model entry, like for Reduce no-build.
        if(filesize($outpath) > 0) $_SESSION['models'][$modelID]['pdb'] = $outname;
    }
    
    setProgress($tasks, null); // all done
}

############################################################################
// Clean up and go home
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
