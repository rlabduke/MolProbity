<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches the analysis job in the background.
    
INPUTS (via Get or Post):
    model           ID code for model to process
    opts[]          an array of options for the background job

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
unset($_SESSION['bgjob']); // Clean up any old data
$_SESSION['bgjob']['model']     = $_REQUEST['model'];
$_SESSION['bgjob']['opts']      = $_REQUEST['opts'];

// launch background job
launchBackground(MP_BASE_DIR."/jobs/analyze.php", "analyze_display.php?$_SESSION[sessTag]&model=$_REQUEST[model]", 5);

// include() status monitoring page
include(MP_BASE_DIR."/public_html/job_progress.php");
die();
############################################################################
?>
