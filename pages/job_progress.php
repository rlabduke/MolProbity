<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a job-progress-monitoring page.
    See also lib/core.php:launchBackground()
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class job_progress_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used; $_SESSION[bgjob] is used instead.
*/
function display($context)
{
    // Because we can't modify $_SESSION while a background job is running,
    // we have to "cheat" on our contract and use display() to take action.
    // This is not recommended for normal MolProbity pages!!
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
        //pageGoto("sitemap.php"); No, b/c we're already in display()
        $_SESSION['bgjob']['whereNext'] = "welcome.php";
        mpSaveSession();
    }

    $ellapsed = time() - $_SESSION['bgjob']['startTime'];
    if($_SESSION['bgjob']['isRunning'] && !$_SESSION['bgjob']['processID'] && $ellapsed > 5)
    {
        $url        = makeEventURL("onGoto", "welcome.php");
        $refresh    = "10; URL=$url";
        echo $this->pageHeader("Job failed to start");//, "none", $refresh);
        echo "<p>It appears that your background job failed to start.\n";
        echo "<br>This is probably due to a syntax error in one of the PHP scripts.\n";
        echo "<br>See the session error log for hints.\n";
        echo "<p><a href='$url'>Click here</a> to continue.\n";
        echo $this->pageFooter();
    }
    elseif($_SESSION['bgjob']['isRunning'])
    {
        // We have to be very careful to not overwrite $_SESSION while the
        // background job is running, or data could be lost!!!
        mpSessReadOnly(true);
        // A simple counter to make sure browsers think each reload
        // is a "unique" page...
        $count      = $_REQUEST['count']+1;
        // We use basename() to get "index.php" instead of the full path,
        // which is subject to corruption with URL forwarding thru kinemage.
        $url        = basename($_SERVER['PHP_SELF'])."?$_SESSION[sessTag]&count=$count";
        // Refresh once quickly to get list of tasks displayed, then at given rate
        $rate       = ($count == 1 ? 2 : $_SESSION['bgjob']['refreshRate']);
        // Slow down if this is a long job
        if($ellapsed > 30 && $rate < 5)         $rate = 5;  // after 30 sec, refresh every 5 sec
        elseif($ellapsed > 120 && $rate < 10)   $rate = 10; // after 2 min,  refresh every 10 sec
        elseif($ellapsed > 1200 && $rate < 30)  $rate = 30; // after 20 min, refresh every 30 sec

        $refresh    = "$rate; URL=$url";
        echo $this->pageHeader("Job is running...", "none", $refresh);
        echo "<p><center>\n";
        echo "<table border='0'><tr><td>\n";
        echo "<img src='img/2sod-anim.gif'></td><td>\n";
        @readfile("$_SESSION[dataDir]/".MP_DIR_SYSTEM."/progress");
        echo "</td></tr></table></center>\n";
        echo "<p><small>Your job has been running for ".$this->fmtTime($ellapsed).".</small>\n";
        echo "<br><small>If this page doesn't update after $rate seconds, <a href='$url'>click here</a>.</small>\n";
        if(isset($_SESSION['bgjob']['processID']))
            echo "<br><small>If needed, you can <a href='$url&abort={$_SESSION[bgjob][processID]}'>abort this job</a>.\n";
        echo $this->pageFooter();
    }
    ////////////////////////if job is not running
    ////////////////////////there was an ERROR
    ////////////////////////these if blocks create error explanation pages
    elseif($_SESSION['bgjob']['modelError'])
    {
        $url        = makeEventURL("onGoto", "welcome.php");
        $pdburlMODEL = "http://deposit.rcsb.org/adit/docs/pdb_atom_format.html#MODEL";
        $pdburlENDMDL = "http://deposit.rcsb.org/adit/docs/pdb_atom_format.html#ENDMDL";
        $refresh    = "10; URL=$url";
        echo $this->pageHeader("ERROR: MODEL/ENDMDL card mismatches");//, "none", $refresh);
        echo "<p>It appears that the PDB you provided has a formatting error.\n";
        echo "<br>MolProbity believes the formatting error has to do with mismatched MODEL and ENDMDL cards.\n\n";
        echo "<p>If you have a single-model structure, the <b>easiest solution is to remove the MODEL card</b>.\n\n";
        echo "<p>Each MODEL card must be uniquely numbered and have a matching ENDMDL card.\n";
        echo "<p>The most common error is 'MODEL 1' existing with no ENDMDL card.\n";
        echo "<br>For further info please see the <a href='$pdburlMODEL' target=\"_blank\">PDB's MODEL</a> and <a href='$pdburlENDMDL' target=\"_blank\">ENDMDL</a> documentation.\n\n";
        echo "<p>If you continue experiencing problems please contact us";
        echo "<br>using the feedback page which you can access on the left-hand";
        echo "<br>navigation bar from the main page.\n";
        echo "<p><a href='$url'>Click here</a> to return to the main page. \n";
        echo $this->pageFooter();
    }
    elseif($_SESSION['bgjob']['elementError'])
    {
        $url        = makeEventURL("onGoto", "welcome.php");
        $pdburl = "http://www.wwpdb.org/documentation/file-format/format33/sect9.html#ATOM";
        $refresh    = "10; URL=$url";
        echo $this->pageHeader("CCTBX job failed");//, "none", $refresh);
        echo "<p>It appears that the PDB you provided has a formatting error.\n";
        echo "<br>MolProbity believes the formatting error has to do with improper chemical names (a.k.a. element symbols)\n\n";
        echo "<p>Here is an example of PDB format:";
        echo "<pre>\n";
        echo "         1         2         3         4         5         6         7         8\n";
        echo "12345678901234567890123456789012345678901234567890123456789012345678901234567890\n";
        echo "ATOM     32  N  AARG A  -3      11.281  86.699  94.383  0.50 35.88           N\n";
        echo "ATOM     33  N  BARG A  -3      11.296  86.721  94.521  0.50 35.60           N\n";
        echo "ATOM     34  CA AARG A  -3      12.353  85.696  94.456  0.50 36.67           C\n";
        echo "ATOM     35  CA BARG A  -3      12.333  85.862  95.041  0.50 36.42           C\n";
        echo "ATOM     36  C  AARG A  -3      13.559  86.257  95.222  0.50 37.37           C\n";
        echo "ATOM     37  C  BARG A  -3      12.759  86.530  96.365  0.50 36.39           C\n";
        echo "ATOM     38  O  AARG A  -3      13.753  87.471  95.270  0.50 37.74           O\n";
        echo "ATOM     39  O  BARG A  -3      12.924  87.757  96.420  0.50 37.26           O\n";
        echo "ATOM     40  CB AARG A  -3      12.774  85.306  93.039  0.50 37.25           C\n";
        echo "ATOM     41  CB BARG A  -3      13.428  85.746  93.980  0.50 36.60           C\n";
        echo "ATOM     42  CG AARG A  -3      11.754  84.432  92.321  0.50 38.44           C\n";
        echo "ATOM     43  CG BARG A  -3      12.866  85.172  92.651  0.50 37.31           C\n";
        echo "ATOM     44  CD AARG A  -3      11.698  84.678  90.815  0.50 38.51           C\n";
        echo "ATOM     45  CD BARG A  -3      13.374  85.886  91.406  0.50 37.66           C\n";
        echo "HETATM   46 MG   MG  B 101      44.444  55.555  66.666  1.00 42.42          MG\n";
        echo "</pre><p>Chemical names must be in columns 77-78 of the PDB file, right justified (e.g. \" C\"). ";
        echo "<br>For further info please see the <a href='$pdburl' target=\"_blank\">PDB formatting</a> guide.\n";
        echo "<p><strong>To fix:</strong> please check your fomatting conforms to proper PDB";
        echo "<br>conventions and try again. If you continue experiencing problems please";
        echo "<br>contact us using the feedback page which you can access on the left-hand";
        echo "<br>navigation bar from the main page.\n";
        echo "<p><a href='$url'>Click here</a> to return to the main page.\n";
        echo $this->pageFooter();
    }
    elseif($_SESSION['bgjob']['cctbxError'])
    {
        $url        = makeEventURL("onGoto", "feedback_setup.php");
        $refresh    = "10; URL=$url";
        echo $this->pageHeader("CCTBX job failed");//, "none", $refresh);
        echo "<p>It appears that your CCTBX-powered job failed.\n";
        echo "<br>This is probably due to a syntax error in one of the CCTBX scripts!\n";
        echo "<br>See the session error log for hints, and please report the bug using the Feedback tool.\n";
        echo "<p><a href='$url'>Click here</a> to continue.\n";
        echo $this->pageFooter();
    }
    //////////////////////////////////////////////
    ///////////////Assuming nothing else went wrong, job is finished
    //////////////////////////////////////////////
    else
    {
        $url        = makeEventURL("onGoto", $_SESSION['bgjob']['whereNext'], $_SESSION['bgjob']);
        $refresh    = "3; URL=$url";
        echo $this->pageHeader("Job is finished", "none", $refresh);
        echo "<p><center>\n";
        echo "<p><table border='0'><tr><td>\n";
        @readfile("$_SESSION[dataDir]/".MP_DIR_SYSTEM."/progress");
        echo "</td></tr></table></center>\n";
        echo "<p><small>Your job ran for ".$this->fmtTime($_SESSION['bgjob']['endTime'] - $_SESSION['bgjob']['startTime']).".</small>\n";
        echo "<br><small>If nothing happens after 3 seconds, <a href='$url'>click here</a>.<small>\n";
        echo $this->pageFooter();
    }
}
#}}}########################################################################

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

}//end of class definition
?>
