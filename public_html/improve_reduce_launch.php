<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches Reduce -build in the background.
    
INPUTS (via Get or Post):
    model           ID code for model to process
    makeFlipkin     true if the user wants a Flipkin made

OUTPUTS (via $_SESSION['bgjob'])
    model           ID code for model to process
    makeFlipkin     true if the user wants a Flipkin made

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
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
$_SESSION['bgjob']['model']         = $_REQUEST['model'];
$_SESSION['bgjob']['makeFlipkin']   = $_REQUEST['makeFlipkin'];

// launch background job
launchBackground(MP_BASE_DIR."/jobs/reduce-build.php", "improve_reduce_choose.php?$_SESSION[sessTag]", 5);

// include() status monitoring page
include(MP_BASE_DIR."/public_html/job_progress.php");
die();
############################################################################
?>
