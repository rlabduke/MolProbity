<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is intended as a reference for MolProbity PHP background scripts.

INPUTS (via $_SESSION['bgjob']):
    modelID         model to be used for making kins
    scriptName      which Prekin 'script' to run

OUTPUTS (via $_SESSION['bgjob']):
    labbookEntry    the labbook entry number

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
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
$modelID    = $_SESSION['bgjob']['modelID'];
$model      = $_SESSION['models'][$modelID];
$modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$infile     = "$modelDir/$model[pdb]";
$scriptName = $_SESSION['bgjob']['scriptName'];
$rainbow    = $_SESSION['bgjob']['rainbow'];
$cpkballs    = $_SESSION['bgjob']['cpkballs'];

$kinDir     = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
$kinURL     = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
    if(!file_exists($kinDir)) mkdir($kinDir, 0777);
    
// Do a safety check on $scriptName:
switch($scriptName) {
    case "cass":
    case "mchb":
    case "aasc":
    case "lots":
    case "naba":
    case "ribnhet":
        $flag = "-" . $scriptName;
        break;
    case "halfbonds":
        $flag = "-lots";
        $rainbow = FALSE; // the two are incompatible
        break;
    default:
        $scriptName = "cass";
        $flag = "-cass";
        break;
}
// Do this after file name or it'll get junked up!
if($rainbow)    $flag .= " -colornc"; // Color ramp N --> C ?
if($cpkballs)   $flag .= " -show 'mc,sc,ht,at'";

$tasks['kin'] = "Make kinemage using <code>Prekin $flag</code>";
setProgress($tasks, 'kin'); // updates the progress display if running as a background job

$outfile = "$kinDir/$model[prefix]$scriptName.kin";
if($scriptName == "halfbonds")
    $cmd = "prekin $flag $infile | php -f ".MP_BASE_DIR."/lib/halfbonds.php > $outfile";
else
    $cmd = "prekin $flag $infile > $outfile";
exec($cmd);

// Lab notebook entry / results page
$entry = "";
$entry .= "<p>Your kinemage is ready for viewing in KiNG: ".linkKinemage("$model[prefix]$scriptName.kin")."</p>\n";

$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Simple kinemage: $model[pdb]",
    $entry,
    $modelID,
    "auto"
);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
