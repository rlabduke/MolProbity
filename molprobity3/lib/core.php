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
require_once(MP_BASE_DIR.'/lib/event_page.php');// MVC/events architecture

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
    <title>'.$title.' - MolProbity</title>
    <link rel="StyleSheet" href="css/default.css" TYPE="text/css">
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
';
    
    if($refresh != "")
        $s .= "    <meta http-equiv='refresh' content='$refresh'>\n";

    $s .= '</head>
<body>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td><img src="img/small-logo5.gif" alt="MolProbity logo"></td>
    <td><div class="pageheader">
        <h1>'.$title.'</h1>
    </div></td>
</tr>
';
    
    if($active == "none")
    {
        $s .= '<tr><td valign="top" colspan="2">
    <div class="pagecontent">
';
    }
    else
    {
        $s .= '<tr><td valign="top" width="150">
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
    //$s .= mpNavBar_goto('', 'Common uses', ($active == 'common'));
    //$s .= mpNavBar_call('', 'Crystallography');
    //$s .= "<br />\n";
    $s .= mpNavBar_goto('sitemap.php', 'Site map', ($active == 'sitemap'));
    $s .= mpNavBar_call('upload_setup.php', 'Upload / fetch files');
    $s .= mpNavBar_call('reduce_setup.php', 'Add hydrogens');
    $s .= "<br />\n";
    $s .= mpNavBar_goto('file_browser.php', 'View &amp; download files', ($active == 'files'));
    $s .= mpNavBar_goto('notebook_main.php', 'Lab notebook', ($active == 'notebook'));
    //$s .= mpNavBar_goto('', 'Set preferences', ($active == 'preferences'));
    //$s .= mpNavBar_goto('feedback_tab.php', 'Feedback &amp; Bugs', ($active == 'feedback'));
    $s .= mpNavBar_goto('save_session.php', 'Save session', ($active == 'savesession'));
    $s .= "<br />\n";
    $s .= mpNavBar_goto('logout.php', 'Log out', ($active == 'logout'));
    $s .= "<br />\n";
    $s .= "<br />You are using ".round(100*mpSessSizeOnDisk(session_id())/MP_SESSION_MAX_SIZE);
    $s .= "% of your ".formatFilesize(MP_SESSION_MAX_SIZE)." of disk space.";
    return $s;
}

function mpNavBar_goto($page, $title, $isActive = false)
{
    if($page == '')
        return "<br />$title\n";
    elseif($isActive)
        return "<br /><a href='".makeEventURL("onNavBarGoto", $page)."'><b>$title</b></a>\n";
    else
        return "<br /><a href='".makeEventURL("onNavBarGoto", $page)."'>$title</a>\n";
}

function mpNavBar_call($page, $title)
{
    if($page == '')
        return "<br /><span class='minornav'>$title</span>\n";
    else
        return "<br /><span class='minornav'><a href='".makeEventURL("onNavBarCall", $page)."'>$title</a></span>\n";
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
About <a href="help/about.html" target="_blank">MolProbity</a>
| About <a href="http://kinemage.biochem.duke.edu" target="_blank">the Richardson Lab</a>
| Internal reference '.MP_VERSION.'
    </div>
</td></tr>
</table>
</body>
</html>
';
}
#}}}########################################################################

#{{{ launchBackground - start a job running in the background (FREEZES SESSION)
############################################################################
/**
* Be warned -- this function makes the current session READ ONLY.
* The session data file must not be overwritten until the job ends.
* This function ONLY makes sense in the context of a session.
*
* This command does NOT automatically do a pageGoto("job_progress.php").
* You should do that manually BEFORE calling this function, or use another
* background job monitor with equivalent functionality.
* You can't pageGoto() AFTER calling this function b/c the session is frozen!
*
* $script       Absolute path to a PHP script to run.
*               Command is "php -f $script ".session_id()
* $whereNext    name of the next delegate script, for after the job
* $delay        number of seconds to wait between refreshes
*/
function launchBackground($script, $whereNext, $delay = 5)
{
    if($_SESSION['bgjob']['isRunning']) return false;
    
    // No! Caller probably put some data in there already for this new job!
    // This has to be done in the launch functions instead.
    //unset($_SESSION['bgjob']); // Clean up any old data
    
    // Remove old progress file
    $progress = "$_SESSION[dataDir]/".MP_DIR_SYSTEM."/progress";
    if(file_exists($progress)) unlink($progress);
    
    unset($_SESSION['bgjob']['processID']);
    $_SESSION['bgjob']['isRunning']     = true;
    $_SESSION['bgjob']['startTime']     = time();
    $_SESSION['bgjob']['refreshRate']   = $delay;
    $_SESSION['bgjob']['whereNext']     = $whereNext;
    
    $errlog = $_SESSION['dataDir']."/".MP_DIR_SYSTEM."/errors";
    
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

#{{{ setProgress, getProgressTasks - job status reporting for background jobs
############################################################################
$__progress_tasks__ = array();

/**
* Generates a listing of all the current tasks, indicating which ones
* are complete, which one is in progress, and which remain to be done.
*   tasks       the list of tasks being performed, in order
*   active      the index (string or numeric, depending on tasks) of the active task.
*               Setting this to null will result in all tasks being marked complete.
*/
function setProgress($tasks, $active)
{
    $__progress_tasks__ = $tasks; // make a record for later
    $f = fopen("$_SESSION[dataDir]/".MP_DIR_SYSTEM."/progress", "wb");
    $foundActive = false;
    if(is_array($tasks)) foreach($tasks as $index => $task)
    {
        if($index == $active)
        {
            fwrite($f, "<br><img src='img/recycle.png' width='16' height='16'> $task\n");
            $foundActive = true;
        }
        elseif($foundActive)
            fwrite($f, "<br><img src='img/blank16.png' width='16' height='16'> $task\n");
        else
            fwrite($f, "<br><img src='img/checkmark.png' width='16' height='16'> $task\n");
    }
    fclose($f);
}

/**
* Returns the latest set of tasks used in setProgress()
*/
function getProgressTasks()
{
    return $__progress_tasks__;
}    
#}}}########################################################################

#{{{ listDir - lists a directory's contents without recursion
############################################################################
/**
* Returns an array of file and/or directory names.
* Both will be strings, and will not include . or ..
* Paths will be relative to $dir, so you'll need to prepend that before using them.
* Returns FALSE on failure.
*/
function listDir($dir)
{
    if($handle = opendir($dir))
    {
        $list = array();
        while(false !== ($file = readdir($handle)))
        {
            if ($file != "." && $file != "..")
            {
                $list[] = $file;
            } 
        }
        closedir($handle);
        return $list;
    }
    else return false;
}
#}}}########################################################################

#{{{ listRecursive - lists a directory's contents, and its subdirectories' contents, etc.
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
    if($handle = opendir($dir))
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
/**
* The original $list is not modified.
* Directories and files are sorted separately -- directories always come first.
*/
function sortFilesAlpha($list)
{
    $d = array(); // dirs
    $f = array(); // files
    foreach($list as $key => $val)
    {
        if(is_array($val))
            $d[$key] = sortFilesAlpha($val);
        else
            $f[$key] = $val;
    }
    ksort($d);
    ksort($f);
    
    return array_merge($d, $f);
    
    // This version mixes files and directories
    /*foreach($list as $el)
    {
        if(is_array($el))
            $el = sortFilesAlpha($el);
    }
    ksort($list);
    
    return $list;*/
}
#}}}########################################################################

#{{{ modelDataExists - shortcut for checking on non-zero-size files existing
############################################################################
/**
* $model    a standard data structure describing the model from $_SESSION['model'][MODEL_ID]
* $suffix   the data file name, without the model prefix, but with the .foo ending
*/
function modelDataExists($model, $suffix)
{
    $name = "$model[prefix]$suffix";
    $file = "$model[dir]/$name";
    return (is_file($file) && filesize($file) > 0);
}
#}}}########################################################################

#{{{ linkModelKin - creates a kinemage open/download link tailored to this session
############################################################################
/**
* $model    a standard data structure describing the model from $_SESSION['model'][MODEL_ID]
* $suffix   the kinemage name, without the model prefix, but with the .kin ending
* $name     an optional name to use in place of the filename
*/
function linkModelKin($model, $suffix, $name = null)
{
    $fname = "$model[prefix]$suffix";
    $file = "$model[dir]/$fname";
    $link = "$model[url]/$fname";
    if($name == null) $name = $fname;
    $s = "";
    $s .= "<b>$name</b> (" . formatFilesize(filesize($file)) . "): ";
    $s .= "<a href='viewking.php?$_SESSION[sessTag]&url=$link' target='_blank'>View in KiNG</a> | ";
    $s .= "<a href='$link'>Download</a>";
    return $s;
}
#}}}########################################################################

#{{{ linkModelDownload - creates a file-download link tailored to this session
############################################################################
/**
* $model    a standard data structure describing the model from $_SESSION['model'][MODEL_ID]
* $suffix   the file name, without the model prefix, but with the .foo ending
* $name     an optional name to use in place of the filename
*/
function linkModelDownload($model, $suffix, $name = null)
{
    $fname = "$model[prefix]$suffix";
    $file = "$model[dir]/$fname";
    $link = "$model[url]/$fname";
    if($name == null) $name = $fname;
    $s = "";
    $s .= "<b>$name</b> (" . formatFilesize(filesize($file)) . "): ";
    $s .= "<a href='$link'>Download</a>";
    return $s;
}
#}}}########################################################################

#{{{ makeZipForFolder - packages all files as a ZIP archive
############################################################################
/**
* Creates a ZIP archive file containing all the files in the given folder.
* The archive is created as a temporary file and should be unlinked afterwards.
* The name of the temporary file is returned.
*/
function makeZipForFolder($inpath)
{
    $outpath = tempnam(MP_BASE_DIR."/tmp", "tmp_zip_");
    // Do the song and dance to get just the last dir of $inpath in the ZIP
    // instead of all the dirs, starting from the filesystem root (/).
    $inbase = basename($inpath);
    $indir = dirname($inpath);
    $cwd = getcwd();
    chdir($indir);
    // must compress to stdout b/c otherwise zip wants a .zip ending
    exec("zip -qr - $inbase > $outpath");
    chdir($cwd); // go back to our original working dir
    return $outpath;
}
#}}}########################################################################

#{{{ makeZipForModel/Session - packages all model/session files as a ZIP
############################################################################
/**
* Creates a ZIP archive file containing all the current files in the model.
* The archive is created in the main session directory, and any pre-existing
* archives created by makeZipForModel() or makeZipForSession() are removed.
* The name of the archive file is returned.
*/
function makeZipForModel($modelID)
{
    if(is_array($_SESSION['archives'])) foreach($_SESSION['archives'] as $archive)
        @unlink("$_SESSION[dataDir]/$archive");
    unset($_SESSION['archives']);
    
    $inpath = $_SESSION['models'][$modelID]['dir'];
    $tmppath = makeZipForFolder($inpath);
    $outname = "$modelID.zip";
    $outpath = "$_SESSION[dataDir]/$outname";
    copy($tmppath, $outpath);
    unlink($tmppath);
    
    $_SESSION['archives'][] = $outname;
    return $outname;
}

/**
* Creates a ZIP archive file containing all the current files in the session.
* The archive is created in the main session directory, and any pre-existing
* archives created by makeZipForModel() or makeZipForSession() are removed.
* The name of the archive file is returned.
*/
function makeZipForSession()
{
    if(is_array($_SESSION['archives'])) foreach($_SESSION['archives'] as $archive)
        @unlink("$_SESSION[dataDir]/$archive");
    unset($_SESSION['archives']);
    
    $inpath = $_SESSION['dataDir'];
    $tmppath = makeZipForFolder($inpath);
    $outname = "molprobity.zip";
    $outpath = "$_SESSION[dataDir]/$outname";
    copy($tmppath, $outpath);
    unlink($tmppath);
    
    $_SESSION['archives'][] = $outname;
    return $outname;
}
#}}}########################################################################

#{{{ censorFileName - removes illegal and unusual characters from file name
############################################################################
/**
* Documentation for this function.
*/
function censorFileName($origName)
{
    // Remove illegal chars from the upload file name:
    // two or more dots or any non- alphanumeric/dash/dot/underscore
    $origName = preg_replace('/\.{2,}|[^-_.a-zA-Z0-9]+/', '_', $origName);
    // Remove multiple underscores, for consistency/aesthetics
    $origName = preg_replace('/_{2,}/', '_', $origName);
    if($origName == '') $origName = "null_name"; // I don't think this is possible...
    return $origName;
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
