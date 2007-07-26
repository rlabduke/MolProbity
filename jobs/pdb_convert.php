<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is intended as a reference for MolProbity PHP background scripts.

INPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

OUTPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
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

# MAIN - the beginning of execution for this page
############################################################################
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];
$pdbDir    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$pdb = $pdbDir.'/'.$model['pdb'];

// Set up progress message
$tasks['convert'] = "Convert pdb with <code>PDBconverter -oldout</code>";
$tasks['notebook'] = "Add entry to lab notebook";

setProgress($tasks, 'convert'); // updates the progress display if running as a background job
if(!file_exists($pdbDir)) mkdir($pdbDir, 0777); // shouldn't ever happen, but might...
$outpdb = $pdbDir.'/'.$modelID."v23.pdb";
downgradePDB($pdb, $outpdb);

setProgress($tasks, 'notebook');
$outpdburl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$modelID."v23.pdb";

$entry = "PDBconverter was run on $model[pdb] to convert its atoms to PDB v2.3 format.\n";
$entry .= "<p>You can now <a href='$outpdburl'>download the converted PDB file</a> (".formatFilesize(filesize($outpdb)).").</p>\n";
$entry .= $pdbEntries;
//$entry .= "<p>A kinemage of all of the fragments is ready for viewing in KiNG: ".linkKinemage($modelID.'.kin')."</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Converted $model[pdb] to $modelID"."v23.pdb.",
    $entry,
    "$modelID|$newModel[id]", // applies to both old and new model
    "auto",
    "downgrade.gif"
);

setProgress($tasks, null);


############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
