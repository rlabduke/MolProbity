<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file monitors background jobs and refreshes periodically.
    
INPUTS are via $_SESSION['bgjob']

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
    mpSessReadOnly();

#{{{ fmtTime - format a long time into minutes and seconds
############################################################################
/**
* Documentation for this function.
*/
function fmtTime($sec)
{
    if($sec <= 60)
        return "$sec seconds";
    else
        return floor($sec/60)." minutes and ".($sec%60)." seconds";
}
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
if(isset($_REQUEST['abort']) && $_REQUEST['abort'] == $_SESSION['bgjob']['processID'])
{
    // Sometimes jobs die due to seg fault or PHP syntax error.
    // Thus, the isRunning flag remains set forever, causing the UI to "hang".
    // However, posix_kill() will return failure, b/c the job no longer exists.
    // So, we try to kill the job and proceed, with the assumption that it worked.
    posix_kill($_SESSION['bgjob']['processID'], 9); // 9 --> KILL
    mpSessReadOnly(false);
    unset($_SESSION['bgjob']['processID']);
    $_SESSION['bgjob']['endTime']   = time();
    $_SESSION['bgjob']['isRunning'] = false;
    // It no longer makes sense to continue -- needed vars may be undefined.
    // All we can do is return to the main page!
    $_SESSION['bgjob']['whereNext'] = "home_tab.php?$_SESSION[sessTag]";
}

if($_SESSION['bgjob']['isRunning'])
{
    // A simple counter to make sure browsers think each reload
    // is a "unique" page...
    $count      = $_REQUEST['count']+1;
    $ellapsed   = time() - $_SESSION['bgjob']['startTime'];
    $url        = "job_progress.php?$_SESSION[sessTag]&count=$count";
    // Refresh once quickly to get list of tasks displayed, then at given rate
    $rate       = ($count == 1 ? 2 : $_SESSION['bgjob']['refreshRate']);
    // Slow down if this is a long job
    if($ellapsed > 30 && $rate < 5)         $rate = 5;  // after 30 sec, refresh every 5 sec
    elseif($ellapsed > 120 && $rate < 10)   $rate = 10; // after 2 min, refresh every 10 sec
    elseif($ellapsed > 1200 && $rate < 30)  $rate = 30; // after 20 min, refresh every 30 sec
    
    $refresh    = "$rate; $url";
    echo mpPageHeader("Job is running...", "none", $refresh);
    echo "<p><center>\n";
    //echo "<img src='img/pbar-anim.gif'><br>\n";
    echo "<table border='0'><tr><td>\n";
    echo "<img src='img/1ubq-spin.gif'></td><td>\n";
    @readfile("$_SESSION[dataDir]/progress");
    echo "</td></tr></table></center>\n";
    echo "<p><small>Your job has been running for ".fmtTime($ellapsed).".</small>\n";
    echo "<br><small>If this page doesn't update after $rate seconds, <a href='$url'>click here</a>.</small>\n";
    //if($ellapsed > 60 && isset($_SESSION['bgjob']['processID']))
    if(isset($_SESSION['bgjob']['processID']))
        echo "<br><small>If needed, you can <a href='$url&abort={$_SESSION[bgjob][processID]}'>abort this job</a>.\n";
    echo mpPageFooter();
}
else
{
    $url        = $_SESSION['bgjob']['whereNext'];
    $refresh    = "3; $url";
      echo mpPageHeader("Job is finished", "none", $refresh);
    //echo mpPageHeader("Job is finished");
    echo "<p><center>Your job ran for ".fmtTime($_SESSION['bgjob']['endTime'] - $_SESSION['bgjob']['startTime']).".\n";
    //echo "<br><form action='$url' method='post'><input type='submit' value='Continue'></form></a>\n";
    echo "<p><table border='0'><tr><td>\n";
    @readfile("$_SESSION[dataDir]/progress");
    echo "</td></tr></table></center>\n";
      echo "<p><small>If nothing happens, <a href='$url'>click here</a>.<small>\n";
    echo mpPageFooter();
}
############################################################################
?>
