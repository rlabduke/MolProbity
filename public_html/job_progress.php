<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file monitors background jobs and refreshes periodically.
    
INPUTS are via $_SESSION['bgjob']

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
    mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
if($_SESSION['bgjob']['isRunning'])
{
    // A simple counter to make sure browsers think each reload
    // is a "unique" page...
    $count      = $_REQUEST['count']+1;
    $url        = "job_progress.php?$_SESSION[sessTag]&count=$count";
    $rate       = $_SESSION['bgjob']['refreshRate'];
    $refresh    = "$rate; $url";
    echo mpPageHeader("Job is running...", "none", $refresh);
    echo "<p>Your job has been running for ".(time() - $_SESSION['bgjob']['startTime'])." seconds.\n";
    echo "<p>If nothing happens for $rate seconds, <a href='$url'>click here</a>.\n";
    echo mpPageFooter();
}
else
{
    $url        = $_SESSION['bgjob']['whereNext'];
    $refresh    = "1; $url";
    echo mpPageHeader("Job is finished", "none", $refresh);
    echo "<p>Your job ran for ".($_SESSION['bgjob']['endTime'] - $_SESSION['bgjob']['startTime'])." seconds.\n";
    echo "<p>If nothing happens, <a href='$url'>click here</a>.\n";
    echo mpPageFooter();
}
############################################################################
?>
