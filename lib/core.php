<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Defines core functions for MolProbity pages.
    
    This file should be included by every top-level page in MolProbity.
    Furthermore, every top-level page should call either
    
        mpInitEnvirons()        (special pages like the job monitor)
             -OR-
        mpStartSession()        (most normal pages)
    
    in order to obtain all the usual resources that every page expects.
*****************************************************************************/
// Someone else MUST have defined this before including us!
if(!defined('MP_BASE_DIR')) die("MP_BASE_DIR is not defined.");
    
// Import all the constants we use
require_once(MP_BASE_DIR.'/config/config.php');

// Session handling functions
require_once(MP_BASE_DIR.'/lib/sessions.php');

#{{{ formatFilesize - human-readable file size with at least 2 sig. digits.
############################################################################
function formatFilesize($size)
{
        if( $size >= 10000000000 ) $size = round($size/1000000000, 0) . " GB";
    elseif( $size >= 1000000000 )  $size = round($size/1000000000, 1) . " GB";
    elseif( $size >= 10000000 )    $size = round($size/1000000, 0) . " MB";
    elseif( $size >= 1000000 )     $size = round($size/1000000, 1) . " MB";
    elseif( $size >= 10000 )       $size = round($size/1000, 0) . " KB";
    elseif( $size >= 1000 )        $size = round($size/1000, 1) . " KB";
    else $size = $size . " bytes";

    return $size;
}
#}}}########################################################################

#{{{ mpPageHeader - creates the first part of a standard MolProbity page
############################################################################
/**
* $title        the page title
* $refresh      a string like "5; something.php?foo=bar&bar=nil"
*               would refresh that page with those vars every 5 sec.
*/
function mpPageHeader($title, $refresh = "")
{
    $s = "";
    $s .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>MolProbity - '.$title.'</title>
    <link rel="StyleSheet" href="css/default.css" TYPE="text/css">
    <link rel="shortcut icon" href="favicon.ico">
';
    
    if($refresh != "")
        $s .= "    <meta http-equiv='refresh' content='$refresh'>\n";

    $s .= '</head>
<body>
<div class="mpheader">
    <div class="mplogo">
        <img src="img/small-logo.gif" alt="MolProbity logo">
    </div>
    <div class="head_links">
        <a href="http://kinemage.biochem.duke.edu" target="_blank">Richardson Lab</a>
    </div>
    <h1 class="page_title">'.$title.'</h1>
</div>
<br clear="all">
';
    return $s;
}
#}}}########################################################################

#{{{ mpPageFooter - creates the last part of a standard MolProbity page
############################################################################
function mpPageFooter()
{
    return '
<div class="mpfooter">
    <i>This page "generated" by:</i>
    <ul>
    <li>NIH Grant GM-15000, funding Richardson Lab research for over 34 years;</li>
    <li>NIH Grant GM-61302, funding RLab for over 3 years; and</li>
    <li>a <a href="http://www.hhmi.org/" target=_blank>HHMI</a> Predoctoral Fellowship to IWD.</li>
    </ul>
    <p><i>Please cite:</i>
    <br><div class="foot_cite">
        Simon C. Lovell, Ian W. Davis, W. Bryan Arendall III, Paul I. W. de Bakker, J. Michael Word,
        Michael G. Prisant, Jane S. Richardson, David C. Richardson (2003)
        <a href="http://kinemage.biochem.duke.edu/validation/valid.html" target=_blank>
        Structure validation by C-alpha geometry: phi, psi, and C-beta deviation.</a>
        Proteins: Structure, Function, and Genetics. <u>50</u>: 437-450.
    </div>
    <p><a href="help/credits.html" target=_blank>Software authors and other credits...</a>
</div>
<p><i>Internal reference '.MP_VERSION.'</i>
</body>
</html>
';
}
#}}}########################################################################

#{{{ mpTabBar - constructs the tab bar used on all the main pages
############################################################################
/**
* $active is one of
* 'notebook', 'home', 'upload', 'analyze', 'compare', 'finish'
*/
function mpTabBar($active)
{
    $tabs = array('notebook', 'home', 'upload', 'analyze', 'compare', 'finish');
    
    $s = "\n<table border=0 cellspacing=0 cellpadding=0><tr>\n";
    $s .= "    <td><img src='img/tabs/leftcap.gif'></td>\n";
    foreach($tabs as $tab)
    {
        if($tab == $active)
            $s .= "    <td><img src='img/tabs/$tab-a.gif'></td>\n";
        else
            $s .= "    <td><a href='{$tab}_tab.php?$_SESSION[sessTag]'><img src='img/tabs/$tab-i.gif' border='0'></a></td>\n";
    }
    $s .= "    <td><img src='img/tabs/rightcap.gif'></td>\n";
    $s .= "</tr></table>\n";
    return $s;
}
#}}}########################################################################

#{{{ launchBackground - start a job running in the background (ENDS SESSION)
############################################################################
/**
* Be warned -- this function ends the current session if one is started.
* You may re-open it, but it should be read only until the job is done.
* This function ONLY makes sense in the context of a session.
*
* This command is typically followed immediately by:
*   include(MP_BASE_DIR.'/public_html/job_progress.php');
*   die();
* However, the calling script could conceivably want to do additional
* cleanup afterwards, or possibly use a different page for monitoring.
*
* $script       Absolute path to a PHP script to run.
*               Command is "php -f $script ".session_id()
* $whereNext    URL of the page to load when done. Should include session ID.
* $delay        number of seconds to wait between refreshes
*/
function launchBackground($script, $whereNext, $delay = 5)
{
    if($_SESSION['bgjob']['isRunning']) return false;
    
    // No! Caller probably put some data in there already for this new job!
    #unset($_SESSION['bgjob']); // Clean up any old data
    
    $_SESSION['bgjob']['isRunning']     = true;
    $_SESSION['bgjob']['startTime']     = time();
    $_SESSION['bgjob']['refreshRate']   = $delay;
    $_SESSION['bgjob']['whereNext']     = $whereNext;
    
    $errlog = $_SESSION['dataDir']."/errors";
    
    // Make sure session variables are written to disk.
    // session_write_close() doesn't take effect until end of script
    mpSaveSession();
    
    // Save current dir so we can exec script in it's own dir.
    $pwd = getcwd();
    chdir(dirname($script));
    
    // Run the script in the background
    $cmd = "php -f $script '".session_id()."' >> $errlog 2>&1 &";
    exec($cmd);
    
    // Restore the current dir
    chdir($pwd);
}
#}}}########################################################################

#{{{ startsWith - tests whether haystack starts with needle
############################################################################
function startsWith($haystack, $needle)
{
    return (strncmp($haystack, $needle, strlen($needle)) == 0);
}
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
?>
