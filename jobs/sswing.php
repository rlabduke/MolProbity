<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs SSWING on a specified set of residues and calculates a
    resulting PDB file.
    
INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    edmap           the map file name
    cnit            a set of CNIT codes for residues to process
    fastSearch      true iff we should use SSwing's -f flag for faster searching

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added
    sswingChanges   the changes for pdbSwapCoords() produced by SSWING

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
    require_once(MP_BASE_DIR.'/lib/sswing.php');
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
$oldID = $_SESSION['bgjob']['modelID'];
$oldModel = $_SESSION['models'][$oldID];

$newModel = createModel($oldID."S");
$newModel['stats']      = $oldModel['stats'];
$newModel['parent']     = $oldID;
$newModel['history']    = "Derived from $oldModel[pdb] by SSwing";
$newModel['isReduced']  = $oldModel['isReduced'];
$newModel['isBuilt']    = $oldModel['isBuilt'];
$_SESSION['models'][ $newModel['id'] ] = $newModel;

$pdbin  = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$oldModel['pdb'];
$pdbout = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$newModel['pdb'];
$map    = $_SESSION['dataDir'].'/'.MP_DIR_EDMAPS.'/'.$_SESSION['bgjob']['edmap'];
$cnit   = $_SESSION['bgjob']['cnit'];

// Set up progress message
foreach($cnit as $res)
    $tasks[$res] = "Process $res with SSWING";
$tasks["combine"] = "Combine all changes and create kinemage";

$tmpdir = $_SESSION['dataDir'].'/'.MP_DIR_WORK;
if(!file_exists($tmpdir)) mkdir($tmpdir, 0777);

$all_changes = array();
foreach($cnit as $res)
{
    setProgress($tasks, $res);
    $changes = runSswing($pdbin, $map, $tmpdir, $res, ($_SESSION['bgjob']['fastSearch'] ? true : false));
    $all_changes = array_merge($all_changes, $changes);
}

setProgress($tasks, "combine");
pdbSwapCoords($pdbin, $pdbout, $all_changes);
$kindir = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
if(!file_exists($kindir)) mkdir($kindir, 0777);
makeSswingKin($pdbin, $pdbout, "$kindir/$newModel[prefix]sswing.kin", $cnit);

$_SESSION['bgjob']['newModel'] = $newModel['id'];
$_SESSION['bgjob']['sswingChanges'] = $all_changes;
setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
