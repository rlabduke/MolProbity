<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the user-configured Reduce -build command on an existing
    model in this session and creates a new PDB in this model.

INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    doflip[]        an array of booleans, where the keys match the second index
                    in the data structure from decodeReduceUsermods()

OUTPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model that was processed

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
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
// 6. Record this PHP script's PID in case it needs to be killed.
    $_SESSION['bgjob']['processID'] = posix_getpid();
    mpSaveSession();

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
$nqh_regularize = $_SESSION['nqh_regularize'];
if(!$nqh_regularize)
{
  $modelID = $_SESSION['bgjob']['modelID'];
  $model = $_SESSION['models'][$modelID];
}
else
{
  $minModelID = $_SESSION['bgjob']['modelID'];
  $modelID = $_SESSION['models'][$minModelID]['modelID_pre_min'];
  $model = $_SESSION['models'][$modelID];
}
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];

$changes = decodeReduceUsermods($pdb);

// If all changes were accepted, we will not need to re-run Reduce.
$rerun = false;
// Make a file of flip-noflip commands for Reduce
$rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
if(!file_exists($rawDir)) mkdir($rawDir, 0777);
$flipfile = "$rawDir/$model[prefix]fix.flips";
$fp = fopen($flipfile, "wb");
$n = count($changes[0]); // How many changes are in the table?
for($c = 0; $c < $n; $c++)
{
    if($doflip[$c]) fwrite($fp, "F:" . $changes[0][$c] . "\n");
    else            fwrite($fp, "O:" . $changes[0][$c] . "\n");

    // Expect checks for ones flipped originally; expect no check for ones not flipped.
    $expected = ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL");
    if($doflip[$c] != $expected) { $rerun = true; }
}
fclose($fp);

if(! $rerun)
    setProgress(array("No additional changes made to model"), null);
else
{
    $tasks['reduce'] = "Add H with user-selected Asn/Gln/His flips using <code>reduce -fix</code>";
    $tasks['regularize'] = "Regularize flipped N/Q/H geometry with <code>CCTBX</code> (if necessary)";
    setProgress($tasks, 'reduce');

    $outname    = $model['pdb']; // Just overwrite the default Reduce -build one
    $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
    $outpath .= '/'.$outname;

    // input should be from parent model or we'll be double flipped!
    $parentID = $model['parent'];
    $parent = $_SESSION['models'][$parentID];
    $parentPDB = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$parent['pdb'];
    if(file_exists($parentPDB))
    {
        reduceFix($parentPDB, $outpath, $flipfile);
    }

    if($nqh_regularize)
    {
       #can we just overwrite the previous minimized version?
       setProgress($tasks, 'regularize');
       $flipInpath = $outpath;
       #$minModel = createModel($modelID."FH_reg");
       $minModelID = $_SESSION['bgjob']['modelID'];
       $minModel = $_SESSION['models'][$minModelID];
       $outname2 = $minModel['pdb'];
       $outpath2 = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
       $outpath2 .= '/'.$outname2;
       $temp = $_SESSION['dataDir'].'/tmp';
       if(file_exists($flipInpath))
       {
         regularizeNQH($flipInpath, $outpath2, $temp);
       }
       if(!file_exists($outpath2))
       {
         unset($_SESSION['bgjob']['processID']);
         $_SESSION['bgjob']['endTime']   = time();
         $_SESSION['bgjob']['isRunning'] = false;
         $_SESSION['bgjob']['cctbxError'] = true;
         die();
       }
    }
    setProgress($tasks, null); // all done
}

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
