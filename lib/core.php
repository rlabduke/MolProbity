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
    
require_once(MP_BASE_DIR.'/config/config.php'); // Import all the constants we use
require_once(MP_BASE_DIR.'/lib/strings.php');
require_once(MP_BASE_DIR.'/lib/sessions.php');  // Session handling functions

#{{{ mpPageHeader - creates the first part of a standard MolProbity page
############################################################################
/**
* $title        the page title
* $active       determines the state of the navigation panel
*               "none" means no nav links will be present
*               see mpNavigationBar() for other choices
* $refresh      a string like "5; something.php?foo=bar&bar=nil"
*               would refresh that page with those vars every 5 sec.
*/
function mpPageHeader($title, $active = "none", $refresh = "")
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
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr><td colspan="2">
    <div class="pageheader">
        <img src="img/small-logo2.gif" alt="MolProbity logo">
        <h1>'.$title.'</h1>
    </div>
</td></tr>
';
    
    if($active == "none")
    {
        $s .= '<tr><td valign="top" colspan="2">
    <div class="pagecontent">
';
    }
    else
    {
        $s .= '<tr><td valign="top">
    <div class="leftnav">
' . mpNavigationBar($active) . '
    </div>
</td>
<td valign="top">
    <div class="pagecontent">
';
    }
    return $s;
}
#}}}########################################################################

#{{{ mpNavigationBar - creates the nav bar as part of the header
############################################################################
/**
* $active is one of ...
*/
function mpNavigationBar($active)
{
    $s = "";
    $s .= mpNavBar_format('home_tab.php', 'Intro & Help', ($active == 'home'));
    $s .= mpNavBar_format('upload_tab.php', 'Get input models', ($active == 'upload'));
    $s .= mpNavBar_format('analyze_tab.php', 'Analyze quality', ($active == 'analyze'));
    $s .= mpNavBar_format('', 'Improve models', ($active == 'improve'));
    $s .= mpNavBar_format('', 'Compare models', ($active == 'compare'));
    $s .= mpNavBar_format('files_tab.php', 'Download files', ($active == 'files'));
    $s .= mpNavBar_format('finish_tab.php', 'Log out', ($active == 'logout'));
    $s .= "<br />\n";
    $s .= mpNavBar_format('notebook_tab.php', 'Lab notebook', ($active == 'notebook'));
    $s .= mpNavBar_format('', 'Set preferences', ($active == 'preferences'));
    $s .= mpNavBar_format('finish_tab.php', 'Save session', ($active == 'savesession'));
    return $s;
}

function mpNavBar_format($page, $title, $isActive = false)
{
    if($page == '')
        return "<br />$title\n";
    elseif($isActive)
        return "<br /><a href='$page?$_SESSION[sessTag]'><b>$title</b></a>\n";
    else
        return "<br /><a href='$page?$_SESSION[sessTag]'>$title</a>\n";
}
#}}}########################################################################

#{{{ mpPageFooter - creates the last part of a standard MolProbity page
############################################################################
function mpPageFooter()
{
    return '
    </div>
</td></tr>
<tr><td colspan="2">
    <div class="pagefooter">
About <a href="">MolProbity</a>
| About <a href="http://kinemage.biochem.duke.edu" target="_blank">the Richardson Lab</a>
| Internal reference '.MP_VERSION.'
    </div>
</td></tr>
</table>
</body>
</html>
';
/*
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
*/
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

#{{{ listRecursive - lists a directories contents, and its subdirectories contents, etc.
############################################################################
/**
* Returns an array of file and/or directory names.
* File names will be strings, and directory names will be arrays with
* the key set to the name of the directory. Test with is_array().
* Keys for files are the same as the values (i.e. the file name).
* Returns FALSE on failure.
*/
function listRecursive($dir)
{
    if ($handle = opendir($dir))
    {
        $list = array();
        while(false !== ($file = readdir($handle)))
        {
            if ($file != "." && $file != "..")
            {
                $path = "$dir/$file";
                if(is_dir($path))
                {
                    $sublist = listRecursive($path);
                    if($sublist !== false)
                        $list[$file] = $sublist;
                }
                else    $list[$file] = $file;
            } 
        }
        closedir($handle);
        return $list;
    }
    else return false;
}
#}}}########################################################################

#{{{ sortFilesAlpha - sorts results of listRecursive() by name
############################################################################
function sortFilesAlpha($list)
{
    foreach($list as $el)
    {
        if(is_array($el))
            $el = sortFilesAlpha($el);
    }
    ksort($list);
    
    return $list;
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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
