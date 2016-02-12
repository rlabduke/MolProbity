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
// Current "internal reference" version number. Please DO NOT change.
define("MP_VERSION", "4.2"); // initialize MP version number (w/ svn revision # if exported from svn)

// Someone else MUST have defined this before including us!
if(!defined('MP_BASE_DIR')) die("MP_BASE_DIR is not defined.");
// If we don't do this, newer PHP defaults will flood us with "errors" about
// uninitiallized variables, keys not in arrays, etc. -- all the implicit PHP
// behaviors that this code counts on  ;)
error_reporting(E_ALL ^ E_NOTICE);

include_once(MP_BASE_DIR.'/config/config.php'); // Import all the constants we use
require_once(MP_BASE_DIR.'/config/defaults.php'); // Import all the constants we use
if(!defined('MP_DEFAULT_TIMEZONE'))
{
    require_once(MP_BASE_DIR.'/lib/timezones.php');
    guessDefaultTimezone();
}

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
* $refresh      a string like "5; URL=something.php?foo=bar&bar=nil"
*               would refresh that page with those vars every 5 sec.
*               Leaving off the URL= part works for most browsers, but not IE!
*/
function mpPageHeader($title, $active = "none", $refresh = "", $headContent = "")
{
    $s = "";
    $s .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>'.$title.' - MolProbity</title>
    <link rel="StyleSheet" href="css/default.css" TYPE="text/css">
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="ROBOTS" content="INDEX, NOFOLLOW">
';

    if($refresh != "")
        $s .= "    <meta http-equiv='refresh' content='$refresh'>\n";

    // Warn the user about bad events -- this could alternately be an alert div
    // as the very first item in pagecontent (at the end of this function).
    /*if($GLOBALS['badEventOccurred'])
        $s .= '<script language="JavaScript">
<!--
window.alert("You cannot use your browser\'s back button in MolProbity,"
    +" and you cannot have multiple windows of the same working session"
    +" (except for kinemage views, charts, and the like).");
-->
</script>
';*/

    if($headContent) $s .= "\n$headContent\n";

    $s .= '</head>
<body>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td width="150"><img src="img/small-logo5.gif" alt="MolProbity logo"></td>
    <td valign="bottom"><div class="pageheader">
        <h1>'.$title.'</h1>
    </div></td>
</tr>
';

    if($active == "none")
    {
        $s .= '<tr><td valign="top" colspan="2">
    <div class="pagecontent_alone">
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

    // Warn the user about bad events. (Alternative JavaScript version above.)
    if($GLOBALS['badEventOccurred'])
        $s .= "<div class='alert'>You cannot use your browser's back button in MolProbity,
            and you cannot have multiple windows of the same working session
            (except for kinemage views, charts, and the like).
            <br><br>Please continue on from here; no damage was done to your session.</div>\n";

    // Warn the user about using too much disk space.
    /* This doesn't work, b/c sometimes we're not in a session.
    *  It's also pretty expensive to run du every time somebody clicks something.
    if(mpSessSizeOnDisk(session_id()) > MP_SESSION_MAX_SIZE)
        $s .= "<div class='alert'>You have exceeded the allowed disk space for this session;
            please download your files and start a new session. If you continue to generate
            more files, <b>your session will be deleted</b>.</div>\n";
    */

    // Warn the user about e.g. the system going down.
    if(defined('MP_BANNER'))
        $s .= "<div class='banner'>".MP_BANNER."</div>\n";

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
    $s .= mpNavBar_goto('welcome.php', 'Main page', ($active == 'welcome'));
    $s .= "<div class='minornav'>".mpNavBar_goto('helper_hydrogens.php', 'About hydrogens', ($active == 'helper_hydrogens'))."</div>";
    $s .= "<div class='minornav'>".mpNavBar_goto('helper_xray.php', 'Evaluate X-ray', ($active == 'helper_xray'))."</div>";
    $s .= "<div class='minornav'>".mpNavBar_goto('helper_nmr.php', 'Evaluate NMR', ($active == 'helper_nmr'))."</div>";
    $s .= "<div class='minornav'>".mpNavBar_goto('helper_rebuild.php', 'Fix up structure', ($active == 'helper_rebuild'))."</div>";
    $s .= "<div class='minornav'>".mpNavBar_goto('helper_kinemage.php', 'Work with kins', ($active == 'helper_kinemage'))."</div>";
    $s .= "<br />\n";
    /*
    $s .= "<div class='minornav'>".mpNavBar_call('upload_setup.php', 'Input PDB files')."</div>";
    $s .= "<div class='minornav'>".mpNavBar_call('upload_setup.php', 'Input other files')."</div>";
    $s .= "<div class='minornav'>".mpNavBar_call('reduce_setup.php', 'Add hydrogens')."</div>";
    $s .= "<div class='minornav'>".mpNavBar_call('aacgeom_setup.php', 'All-atom contacts &amp; geometry')."</div>";
    $s .= "<div class='minornav'>".mpNavBar_call('interface_setup1.php', 'Interface contacts')."</div>";
    //$s .= "<div class='minornav'>".mpNavBar_call('sswing_setup1.php', 'Refit sidechains')."</div>";
    $s .= "<div class='minornav'>".mpNavBar_call('makekin_setup.php', 'Make simple kins')."</div>";
    $s .= "<br />\n";
    */
    $s .= mpNavBar_goto('file_browser.php', 'View &amp; download files', ($active == 'files'));
    $s .= mpNavBar_goto('notebook_main.php', 'Lab notebook', ($active == 'notebook'));
    //$s .= mpNavBar_goto('', 'Set preferences', ($active == 'preferences'));
    $s .= mpNavBar_goto('feedback_setup.php', 'Feedback &amp; bugs', ($active == 'feedback'));
    $s .= mpNavBar_goto('sitemap.php', 'Site map', ($active == 'sitemap'));
    $s .= "<br />\n";
    $s .= mpNavBar_goto('save_session.php', 'Save session', ($active == 'savesession'));
    $s .= mpNavBar_goto('logout.php', 'Log out', ($active == 'logout'));
    $s .= "<br />You are using ".round(100*mpSessSizeOnDisk(session_id())/MP_SESSION_MAX_SIZE);
    $s .= "% of your ".formatFilesize(MP_SESSION_MAX_SIZE)." of disk space.";
    return $s;
}

function mpNavBar_goto($page, $title, $isActive = false)
{
    if($page == '')
        return "$title<br />\n";
    elseif($isActive)
        return "<a href='".makeEventURL("onNavBarGoto", $page)."'><b>$title</b></a><br />\n";
    else
        return "<a href='".makeEventURL("onNavBarGoto", $page)."'>$title</a><br />\n";
}

function mpNavBar_call($page, $title)
{
    if($page == '')
        return "$title<br />\n";
    else
        return "<a href='".makeEventURL("onNavBarCall", $page)."'>$title</a><br />\n";
}
#}}}########################################################################

#{{{ mpPageFooter - creates the last part of a standard MolProbity page
############################################################################
function mpPageFooter()
{
    $segid_txt = '';
    $segid_status = $_SESSION['useSEGID'];
    if($segid_status)
    {
      $segid_txt = '| Using SEGIDs ';
    }
    $reduce_blength = $_SESSION['reduce_blength'];
    if ($reduce_blength == '') $reduce_blength = 'ecloud'; #default
    return '
    </div>
</td></tr>
<tr><td colspan="2">
    <div class="pagefooter">
About <a href="help/about.html" target="_blank">MolProbity</a>
| Website for <a href="http://kinemage.biochem.duke.edu" target="_blank">the Richardson Lab</a> '.
$segid_txt.'| Using '.$reduce_blength.' x-H
| Internal reference '.MP_VERSION.'
    </div>
</td></tr>
</table>
'.MP_TRACKING_CODE.'
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
    // Otherwise, end-of-script write can truncate the file to nothing
    // just as the background job is starting, leading to random failure (?)
    mpSessReadOnly(true);

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
    global $__progress_tasks__;
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
            fwrite($f, "<br><img src='img/clear_1x1.gif' width='16' height='16'> $task\n");
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
    global $__progress_tasks__;
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

#{{{ [DEPRECATED]  linkKinemage - dummy for linkAnyFile()
############################################################################
/**
* $fname    the kinemage filename, relative to MP_DIR_KINS
* $name     an optional name to use in place of the filename
*/
function linkKinemage($fname, $name = null)
{ return linkAnyFile($fname, $name); }
#}}}########################################################################

#{{{ linkAnyFile - creates a file open/download link tailored to this session
############################################################################
/**
* $fname    the filename, relative to __?__
* $name     an optional name to use in place of the filename
* $image    an optional relative or absolute URL to an icon image
*/
function linkAnyFile($fname, $name = null, $image = null)
{
    // Find the file of this name -- all names should actually be unique
    foreach(array(MP_DIR_MODELS, MP_DIR_KINS, MP_DIR_CHARTS, MP_DIR_EDMAPS, MP_DIR_TOPPAR, MP_DIR_RAWDATA) as $dir)
    {
        if(is_file("$_SESSION[dataDir]/$dir/$fname"))
        {
            $subdir = $dir;
            break;
        }
    }
    // Can't find it?  Check if it's a kinemage that was gzipped.
    if(!$subdir && is_file($_SESSION['dataDir'].'/'.MP_DIR_KINS.'/'.$fname.'.gz'))
    {
        $fname .= ".gz";
        $subdir = MP_DIR_KINS;
    }
    if(!$subdir) return;

    // Link the file
    $path = "$_SESSION[dataDir]/$subdir/$fname";
    $link = "$_SESSION[dataURL]/$subdir/$fname";
    $size = formatFilesize(filesize($path));
    $python_file = false;
    if($name == null) $name = $fname;

    // Choose the right action(s) -- see pages/file_browser.php for origin
    if(endsWith($fname, ".kin") || endsWith($fname, ".kin.gz"))
        $links = array(
            array('url' => "viewking.php?$_SESSION[sessTag]&url=$link", 'label' => "View in KiNG", 'blank' => true),
            array('url' => "$link", 'label' => "Download", 'blank' => false),
        );
    elseif(endsWith($fname, "multi.table"))
        $links = array(array('url' => "viewtable.php?$_SESSION[sessTag]&file=$path", 'label' => "View", 'blank' => true));
    elseif(endsWith($fname, "horiz.table"))
        $links = array(array('url' => "viewhoriztable.php?$_SESSION[sessTag]&file=$path", 'label' => "View", 'blank' => true));
    elseif(endsWith($fname, ".html"))
        $links = array(array('url' => "viewtext.php?$_SESSION[sessTag]&file=$path&mode=html", 'label' => "View", 'blank' => true));
    elseif(endsWith($fname, ".txt"))
        $links = array(array('url' => "viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain", 'label' => "View", 'blank' => true));
    elseif(endsWith($fname, ".pdf"))
        $links = array(array('url' => "$link", 'label' => "View", 'blank' => true));
    elseif(endsWith($fname, ".scm")){
        //$python_link=$link;
        $python_file = true;
        $python_link = preg_replace("/\.scm/",".py",$link);
        $python_path = preg_replace("/\.scm/",".py",$path);
        $python_size = formatFilesize(filesize($python_path));
        $links = array(
            array('url' => "$link", 'label' => "Scheme Script Download", 'blank' => false),
            array('url' => "$python_link", 'label' => "Python Script Download", 'blank' => false),
        );
    }
    elseif(endsWith($fname, ".py"))
        $links = array(array('url' => "$link", 'label' => "Python Script", 'blank' => false));
    else
        $links = array(array('url' => "$link", 'label' => "Download", 'blank' => false));

    $isFirst = true;
    $linkText = "";
    foreach($links as $link)
    {
        if($isFirst) {
            $isFirst = false;
            $linkText .= "<a href='$link[url]'".($link['blank'] ? " target='_blank'" : "").">$link[label]</a>";
            if($python_file) {
                $linkText .= " ($size)";
                $size = $python_size;
            }
        }
        else {
            $linkText .= " | ";
            $linkText .= "<a href='$link[url]'".($link['blank'] ? " target='_blank'" : "").">$link[label]</a>";
        }
    }

    if($image == null)
        $s = "<b>$name</b> ($size): $linkText";
    else
    {
        $link = reset($links);
        $s = "<a href='$link[url]'".($link['blank'] ? " target='_blank'" : "")."><img src='$image' alt='$name ($size)'></a><br>$linkText ($size)";
    }

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
    $outpath = mpTempfile("tmp_zip_");
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

#{{{ makeZipForFiles - packages selected files as a ZIP archive
############################################################################
/**
* Creates a ZIP archive file containing all the listed files.
* The archive is created as a temporary file and should be unlinked afterwards.
* The name of the temporary file is returned.
*   $basepath   the ZIP paths will be relative to this folder
*   $filelist   an array of file paths, specified relative to $basepath
*               both directories and files *should* be OK in here
*/
function makeZipForFiles($basepath, $filelist)
{
    SML_cout("\n\n makeZipForFiles \n\n");
    $outpath = mpTempfile("tmp_zip_");
    $cwd = getcwd();
    chdir($basepath);

    //SML hack: zip into better-named directory

    //first, modify $filelist by prepending some good name
    $nicename = zipSymlinkName();
    foreach ($filelist as &$value)
        $value = $nicename."/".$value;
    SML_cout(implode(' ', $filelist));
    SML_cout("\n\n\n");

    //next, make a symlink through that good name
    chdir("..");
    $lncommand = "ln -s ".$basepath." ".$nicename;
    exec($lncommand);
    SML_cout($lncommand);
    SML_cout("\n\n\n");

    // must compress to stdout b/c otherwise zip wants a .zip ending
    $zipcommand = "zip -qr - ".implode(' ', $filelist)." > $outpath";
    SML_cout("\n\n".$zipcommand."\n\n");
    exec($zipcommand);
    chdir($cwd); // go back to our original working dir
    return $outpath;
}
#}}}########################################################################

#{{{ makeZipForSession - packages all session files as a ZIP
############################################################################
/**
* Creates a ZIP archive file containing all the current files in the session.
* The archive is created in the main session directory, and any pre-existing
* archives created by makeZipForSession() are removed.
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

#{{{ zipSymlinkName - generates a uniquely named symlink for zip archives
############################################################################
/**
* Returns a string formatted YYYYMMDD_HHMM_molprobity_JOBHASH,
* year-month-day_24hr-minute_molprobity_job-hash-string.  This
* mangling of the molprobity job ID provides a usefully named faux
* folder to zip files through, so that when unzipping archives the
* files will land in a useful place instead of "tarbombing" into the
* current directory.
*/
function zipSymlinkName()
{
    SML_cout("zipSymlinkName");

    SML_cout("\n\n\n");

    $datestring = date("Ymd_Hi");
    $symlink_name = $datestring."_molprobity_"; //not finished
    SML_cout($_SESSION['sessTag']);
    SML_cout("\n\n\n");

    SML_cout($_SESSION['dataDir']);
    SML_cout("\n\n\n");

    SML_cout($_SESSION['dataUrl']);
    return "gobbledegook2";
}
#}}}########################################################################

#{{{ destructiveGZipFile - overwrites foo.bar with foo.bar.gz
############################################################################
function destructiveGZipFile($path)
{
    exec("gzip -f $path");  // -f to force overwrite
    clearstatcache();       // so we don't still think $path exists
    if(is_file($path))      // this *should* never be true...
        echo "destructiveGZipFile: $path was not overwritten\n";
}
#}}}########################################################################

#{{{ mpTempfile - creates a temp file in the site-wide temp folder
############################################################################
/**
* Note that the created file is NOT automatically removed at the end of
* the script;  you must unlink() it manually.
*/
function mpTempfile($prefix = 'tmp_misc_')
{
    #return tempnam(MP_BASE_DIR."/tmp", $prefix);
    return tempnam(MP_JOB_DATA_DIR."/tmp", $prefix);
}
#}}}########################################################################

#{{{ mpReadfile - replacement for broken readfile in PHP 5
############################################################################
/**
* Early releases of PHP 5 have a bug that keeps readfile() from
* delivering more than about 2Mb.
* Even in the 4.x releases used by Yahoo, there must be enough memory
* available for readfile() to load the whole thing in memory or it fails
* with a HTTP 500 code and no error message.
* Returns number of bytes read or false on failure.
*/
function mpReadfile($filepath)
{
    // Downloads can take a long time for big files, so PHP could time out.
    // According to notes in the manual, this should return the value from
    // the last call to set_time_limit() if it was called previously.
    $old_limit = ini_get('max_execution_time')+0;
    set_time_limit(0); // no limit
    // It's not enough to extend the time; we may need more memory too.
    // In theory we shouldn't, but I guess the garbage collector leaks.
    ini_set('memory_limit', -1); // no limit

    $chunksize = 1*(1024*1024); // how many bytes per chunk
    $buffer = '';
    $cnt = 0;
    $handle = fopen($filepath, 'rb');
    if($handle === false) return false;
    while(!feof($handle))
    {
        $buffer = fread($handle, $chunksize);
        echo $buffer;
        // This makes sure data gets pushed on thru to the user:
        ob_flush();
        flush();
        $cnt += strlen($buffer);
    }
    fclose($handle);

    set_time_limit($old_limit); // restore prev. limit
    return $cnt; // return num. bytes delivered like readfile() does.
}
#}}}########################################################################

#{{{ mpCopy - reimplementation of copy() in pure PHP
############################################################################
/**
* For some reason, this works
*   mpCopy("php://stdin", $outpath);
*
* but this doesn't (truncated file)
*   copy("php://stdin", $outpath);
*/
function mpCopy($inpath, $outpath)
{
    $in = fopen($inpath, 'rb');
    $out = fopen($outpath, 'wb');
    while(!feof($in))
    {
        $data = fread($in, 4096);
        fwrite($out, $data);
    }
    fclose($in);
    fclose($out);
}
#}}}########################################################################

#{{{ mpSerialize, mpUnserialize - replacements for broken (un)serialize
############################################################################
// In some versions of PHP, serialize and unserialize munge some floating-point
// values, in some cases converting them to Inf or NaN.
// This wreaks havoc on parts of MolProbity that use decimal values...
function mpSerialize($data)
{
    // pro: fast and standard; con: sometimes mangles floats
    #return serialize($data);
    // pro: fast, human-readable, and float-safe; con: uses somewhat (~20%?) more space
    return var_export($data, true);
    // pro: standards-based; con: done in pure PHP so slow as hell
    # ...PHP-JSON library call here...
}
function mpUnserialize($text)
{
    // pro: fast, standard, and safe; con: sometimes mangles floats
    #return unserialize($text);

    // pro: fast and float-safe; con: opens vulnerability for arbitrary code injection
    // The startsWith() call is just a sanity check, and is easily evaded:
    //  array(0 => exec('rm -rf /'), ...)
    //
    //***********************************************************************
    // It is critically important to ensure that users never have a chance to
    // overwrite files that will be unserialized, via upload or some editing function.
    //***********************************************************************
    //
    if(startsWith($text, 'array')) return eval("return $text;");
    else return false;

    // pro: standards-based and safe; con: done in pure PHP so slow as hell
    # ...PHP-JSON library call here...
}
#}}}########################################################################

#{{{ filesAreIdentical - checks two files to see if they're exactly the same
############################################################################
/**
* Only expected to work for text files, right now.
*/
function filesAreIdentical($path1, $path2)
{
    //$t = time();
    clearstatcache();
    if(filesize($path1) != filesize($path2)) return false;
    $h1 = @fopen($path1, 'rb');
    $h2 = @fopen($path2, 'rb');
    if($h1 === false || $h2 === false)
    {
        @fclose($h1);
        @fclose($h2);
        return false;
    }
    $areSame = true;
    /*while($areSame)
    {
        $c1 = fgetc($h1);
        $c2 = fgetc($h2);
        if($c1 !== $c2) $areSame = false;
        if($c1 === false) break;
    }*/
    // This version is ~20x faster than the character-by-charcter version.
    // (5 sec for 1JJ2 vs. 119 sec!!)
    while(!feof($h1))
    {
        $s1 = fgets($h1, 1024);
        $s2 = fgets($h2, 1024);
        if($s1 != $s2) { $areSame = false; break; }
    }
    fclose($h1);
    fclose($h2);
    //echo "File comparison took ".(time() - $t)." seconds\n";
    return $areSame;
}
//function someFunctionName() {}
#}}}########################################################################

#{{{ censorFileName - removes illegal and unusual characters from file name
############################################################################
/**
* Given a proposed file name, returns a sanitized file name without odd characters in it.
*
* allowedExt can be either a string or an array of strings that
* defines suitable extensions for the file.
* If the file name does not end in said extension, it is appended.
* The first one from the list is used if there are multiple.
*
* Extensions on uploaded files are a security concern, as an attacker
* could upload a PHP file to a publicly reachable directory.
* This essentially gives command-line access to the server as the Apache user.
*/
function censorFileName($origName, $allowedExt = null)
{
    // Remove illegal chars from the upload file name:
    // two or more dots or any non- alphanumeric/dash/dot/underscore
    $origName = preg_replace('/\.{2,}|[^-_.a-zA-Z0-9]+/', '_', $origName);
    // Remove multiple underscores, for consistency/aesthetics
    $origName = preg_replace('/_{2,}/', '_', $origName);
    if($origName == '') $origName = "null_name"; // I don't think this is possible...

    // Extension testing
    if($allowedExt != null)
    {
        if(!is_array($allowedExt))
            $allowedExt = array($allowedExt);
        if(preg_match('/\.('.implode('|', $allowedExt).')$/i', $origName))
            return $origName;
        else return "$origName.$allowedExt[0]";
    }
    else return $origName;
}
#}}}########################################################################

#{{{ microtimeSubtract - subtracts two strings from microtime(), returns float
############################################################################
/**
* Computes (a - b), where a and b are values from microtime().
* Returns the difference in seconds as a float.
* microtime() can't return a float value directly until PHP 5,
* and this seems to suffer from precision issues anyway.
*/
function microtimeSubtract($a, $b)
{
    $x = explode(' ', $a);
    $y = explode(' ', $b);
    return ($x[1] - $y[1]) + ($x[0] - $y[0]);
}
#}}}########################################################################

#{{{ is_elementerror - parses the error to see if it is an element format issue
############################################################################
/**
* parses the error to see if it is an element format issue
* $errfile should be MP_DIR_SYSTEM."/errors"
*/
function is_elementerror($errfile)
{
  //these error messages come from cctbx, mostly from cctbx_project/iotbx/pdb/xray_structure.h
    $s1 = "Conflicting scattering type symbols";
    $s2 = "Unknown scattering type";
    $s3 = "Unknown chemical element type";
    $s4 = "Unknown charge";
    $a = file($errfile);
    if(!file_exists($errfile)) return false;
    foreach($a as $s)
        {
            error_log("$s");
	    if(strpos($s,$s1) !== false) return true; 
            if(strpos($s,$s2) !== false) return true;
            if(strpos($s,$s3) !== false) return true;
	    if(strpos($s,$s4) !== false) return true;
         }
    return false;
}
#}}}########################################################################

#{{{ is_modelerror - parses the error to see if it is a MODEL/ENDMDL mismatch; SML 14 oct 15
############################################################################
/**
* parses the error to see if it is a MODEL/ENDMDL mismatch issue
* $errfile should be MP_DIR_SYSTEM."/errors"
*/
function is_modelerror($errfile)
{
    $s1 = "ENDMDL record missing at end of input";
    $a = file($errfile);
    if(!file_exists($errfile)) return false;
    foreach($a as $s)
        {
            error_log("$s");
	    if(strpos($s,$s1) !== false) return true; 
         }
    return false;
}
#}}}########################################################################


#{{{ a_function_definition - summary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
