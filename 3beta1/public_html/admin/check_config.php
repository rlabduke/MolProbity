<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
Checks on prerequisites for running MolProbity and reports on their status.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/../..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR."/lib/core.php");
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpInitEnvirons();

#{{{ whichProg - makes sure that the given binary in on the PATH
############################################################################
/**
* Returns (boolean success, string path).
*/
function whichProg($progName)
{
    $result = `which '$progName'`;
    if($result == "" || preg_match("/ not found/", $result))
        return array(false, "");
    else
        return array(true, $result);
}
#}}}########################################################################

#{{{ testForProgs - calls whichProg() on required programs and formats result
############################################################################
/**
* Returns false on failure
*/
function testForProgs($progNameArray)
{
    $ok = true;
    echo "<ul>\n";
    foreach($progNameArray as $prog)
    {
        list($success, $path) = whichProg($prog);
        if(!$success)
        {
            $ok = false;
            echo "<li><b><font color=#990000><tt>$prog</tt>: not found. Add the appropriate path to MP_BIN_PATH.</font></b></li>\n";
        }
        else
        {
            echo "<li><tt>$prog</tt>: $path</li>\n";
        }
        
    }
    echo "</ul>\n";
    return $ok;
}
#}}}########################################################################

#{{{ printPath - does a formatted print of directories on the PATH
############################################################################
function printPath()
{
    // For some reason, getenv() doesn't see changes we make with putenv().
    $path = explode(':', mpGetPath());
    foreach($path as $pathEl) $result .= "<li><tt>$pathEl</tt></li>\n";
    echo "<ul>\n$result</ul>\n";
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

// A global marker for whether we've meet all the conditions.
$ok = true;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>MolProbity - check configuration</title>
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#000099" vlink="#000099" alink="#990000">
<hr><h2>Server information:</h2>
<ul>
<?php
    echo "<li>PHP version: ".PHP_VERSION."</li>\n";
    echo "<li>Operating system: ".PHP_OS."</li>\n";
    
    $magic = get_magic_quotes_gpc();
    if($magic)  echo "<li><b>Magic quotes GPC is enabled.</b> Disable it in <tt>/etc/php.ini</tt> or user-entered text may be corrupted.</li>\n";
    else        echo "<li>Magic quotes GPC is disabled - good.</li>\n";
    
    $cli = `php -r 'echo php_sapi_name();'`;
    if($cli == "cli")   echo "<li>CLI version of PHP installed - good.</li>\n";
    else                echo "<li><b>CLI version of PHP NOT installed.</b> Upgrade to a newer PHP (4.3.0+) before running MolProbity.</li>\n";
    
    if(ini_get('file_uploads'))
    {
        echo "<li>File uploads are enabled - good. Maximum size for file uploads will be the <b>minimum</b> of:\n";
        echo "<ul>\n";
        echo "<li>post_max_size: <b>".ini_get('post_max_size')."</b> (in /etc/php.ini or equivalent)</li>\n";
        echo "<li>upload_max_filesize: <b>".ini_get('upload_max_filesize')."</b> (in /etc/php.ini or equivalent)</li>\n";
        $memlim = ini_get('memory_limit');
        if($memlim > 0) echo "<li>/etc/php.ini - memory_limit: $memlim</li>\n";
        echo "<li>...and any <tt>LimitRequestBody</tt> directives in /etc/httpd/httpd.conf (for Apache web server, anyway).</li>\n";
        echo "</ul>\n</li>\n";
    }
    else echo "<li><b>File uploads not enabled!</b> Please set <tt>file_uploads</tt> to 1 in your php.ini file (usually in /etc/php.ini).</li>\n";
?>
</ul>

<hr><h2>Directories on your PATH:</h2>
    <?php printPath(); ?>

<hr><h2>External programs required by MolProbity:</h2>
    <?php $ok &= testForProgs(array("which", "rm", "du", "df", "zip", "gunzip",
        "php", "awk", "gawk", "perl", "java",
        "reduce", "prekin", "probe", "flipkin", "clashlist", "cluster",
        "pdbcns", "dang", "scrublines", "cksegid.pl")); ?>

</body>
</html>
