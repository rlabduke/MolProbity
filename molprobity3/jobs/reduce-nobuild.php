<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the Reduce to add missing H without doing a full -build.
    It runs on model in this session and **replaces** its PDB file.
    
INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process

OUTPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    labbbookEntry   the labbook entry number describing this action

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
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
$tasks['reduce'] = "Add H with <code>reduce -keep -his</code>";
$tasks['notebook'] = "Add entry to lab notebook";

setProgress($tasks, 'reduce'); // updates the progress display if running as a background job
$outname = $model['id']."H_nobuild.pdb";
$outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
$outpath .= '/'.$outname;
reduceNoBuild($pdb, $outpath);
$_SESSION['models'][$modelID]['pdb'] = $outname;
$_SESSION['models'][$modelID]['isReduced'] = true;

setProgress($tasks, 'notebook');
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$outname;
$url = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$outname;
$entry = "Reduce was run on $modelID to add and optimize missing hydrogens.\n";
$entry .= "Existing hydrogens were not affected, and Asn/Gln/His flips were not optimized.\n";
$entry .= "<p>You can now <a href='$url'>download the annotated PDB file</a> (".formatFilesize(filesize($pdb)).").</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Missing H added to $modelID by Reduce -keep -his",
    $entry,
    $modelID,
    "auto"
);

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
