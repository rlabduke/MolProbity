<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the standard Reduce -build command on an existing
    model in this session and creates a new model entry for the Reduced file.
    
INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    makeFlipkin     true if the user wants a Flipkin made

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
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
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];

// Set up progress message
$tasks['create'] = "Create a new model entry";
$tasks['reduce'] = "Add H with <code>reduce -build</code>";
if($_SESSION['bgjob']['makeFlipkin']) $tasks['flipkin'] = "Create Asn/Gln and His <code>flipkin</code> kinemages";

setProgress($tasks, 'reduce');
$id = reduceBuild($modelID, $pdb);

$_SESSION['bgjob']['newModel'] = $id;
$tasks['create'] .= " ($id)"; // shows ID of new model in progress info

if($_SESSION['bgjob']['makeFlipkin'])
{
    setProgress($tasks, 'flipkin');
    $model = $_SESSION['models'][$id];
    makeFlipkin("$model[dir]/$model[pdb]",
        "$model[dir]/$model[prefix]flipnq.kin",
        "$model[dir]/$model[prefix]fliphis.kin");
    $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    if(!file_exists($outpath)) mkdir($outpath, 0777);
    makeFlipkin($_SESSION['dataDir'].'/'.MP_DIR_MODELS."/$model[pdb]",
        "$outpath/$model[prefix]flipnq.kin",
        "$outpath/$model[prefix]fliphis.kin");
}

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
