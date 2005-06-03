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
    if($result == "" || preg_match("/ not found/", $result) || preg_match("/^no $progName in/", $result))
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
    foreach($path as $pathEl)
    {
        if(is_dir($pathEl)) $result .= "<li><tt>$pathEl</tt></li>\n";
        else $result .= "<li><b><font color=#990000><tt>$pathEl</tt></font> - does not exist</b></li>\n";
    }
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
<center>
[ <a href='phpinfo.php'>PHP info</a>
| <a href='check_config.php'>Configuration check</a>
| <a href='show_sessions.php'>Live sessions</a>
| <a href='usage_history.php'>Usage history</a>
]
</center><hr>
<h2>Server information:</h2>
<ul>
<?php
    define("MINIMUM_ALLOWED_PHP_VERSION", "4.3.7");
    echo "<li>Current URL: http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]</li>\n";
    echo "<li>Operating system: ".PHP_OS."</li>\n";

    if(version_compare(PHP_VERSION, MINIMUM_ALLOWED_PHP_VERSION, "ge"))
        echo "<li>PHP version (webserver): ".PHP_VERSION." - fine.</li>\n";
    else
        echo "<li><b>PHP version (webserver): ".PHP_VERSION."</b> - upgrade to ".MINIMUM_ALLOWED_PHP_VERSION." or newer.</li>\n";
    
    $php_help = explode("\n", shell_exec("php -v"));
    preg_match('/PHP ([0-9.]+)/', $php_help[0], $m);
    if(version_compare($m[1], MINIMUM_ALLOWED_PHP_VERSION, "lt"))
        echo "<li><b>PHP version (cmd line): $php_help[0]</b> - upgrade to ".MINIMUM_ALLOWED_PHP_VERSION." or newer.</li>\n";
    elseif(version_compare($m[1], PHP_VERSION, "ne"))
        echo "<li><b>PHP version (cmd line): $php_help[0]</b> - warning: does not match webserver version (".PHP_VERSION.").</li>\n";
    else
        echo "<li>PHP version (cmd line): $php_help[0] - fine.</li>\n";
    $cli = `php -r 'echo php_sapi_name();'`;
    if($cli == "cli")   echo "<li>CLI version of PHP installed - good.</li>\n";
    else                echo "<li><b>CLI version of PHP NOT installed.</b> Upgrade to a newer PHP (".MINIMUM_ALLOWED_PHP_VERSION."+) before running MolProbity.</li>\n";
    
    if(ini_get('file_uploads'))
    {
        echo "<li>File uploads are enabled - good. Maximum size for file uploads will be the <i>minimum</i> of:\n";
        echo "<ul>\n";
        echo "<li>post_max_size: <b>".ini_get('post_max_size')."</b> (in /etc/php.ini or equivalent)</li>\n";
        echo "<li>upload_max_filesize: <b>".ini_get('upload_max_filesize')."</b> (in /etc/php.ini or equivalent)</li>\n";
        $memlim = ini_get('memory_limit');
        if($memlim > 0) echo "<li>/etc/php.ini - memory_limit: $memlim</li>\n";
        echo "<li>...and any <tt>LimitRequestBody</tt> directives in /etc/httpd/httpd.conf (for Apache web server, anyway).</li>\n";
        echo "</ul>\n</li>\n";
    }
    else echo "<li><b>File uploads not enabled!</b> Please set <tt>file_uploads</tt> to 1 in your php.ini file (usually in /etc/php.ini).</li>\n";

    if(ini_get('safe_mode'))
        echo "<li><b>Safe mode enabled.</b> MolProbity cannot run when safe mode is enabled.</li>\n";
    else
        echo "<li>Safe mode disabled - good.</li>\n";

    $magic = get_magic_quotes_gpc();
    if($magic)  echo "<li><b>Magic quotes GPC is enabled.</b> Disable it in <tt>/etc/php.ini</tt> or user-entered text may be corrupted.</li>\n";
    else        echo "<li>Magic quotes GPC is disabled - good.</li>\n";
    
    if(ini_get('display_errors'))
        echo "<li>PHP display_errors is enabled - good. This should make debugging easier.</li>\n";
    else
        echo "<li><b>PHP display_errors is disabled.</b> This makes it much harder to debug MolProbity, so check your webserver logs for PHP errors.</li>\n";
?>
</ul>

<hr><h2>Directories on your PATH:</h2>
    <?php printPath(); ?>

<hr><h2>External programs required by MolProbity:</h2>
    <?php $ok &= testForProgs(array("which", "rm", "du", "df",
        "zip", "gzip", "gunzip",
        "php", "awk", "gawk", "perl", "java",
        "reduce", "prekin", "probe", "flipkin", "clashlist", "cluster",
        "pdbcns", "dang", "scrublines", "cksegid.pl",
        "sswing", "sswingmkrotscrByPerl", "sswingpdb2rotscr",
        "genContour", "genScoreResult", "preGenScore",
        "noe-display")); ?>

<hr><h2>Version numbers for external programs:</h2>
<ul>
<?php
    
    // Reduce writes help on stderr
    $java_help = explode("\n", shell_exec("java -version 2>&1"));
    $reduce_help = explode("\n", shell_exec("reduce -help 2>&1"));
    preg_match('/version.+?\n/i', shell_exec("noe-display 2>&1"), $m);
    $noe_version = $m[0];

    echo "<li>Java (cmd line): $java_help[0]</li>\n";
    echo "<li>Prekin: ".exec("prekin -help")."</li>\n";
    echo "<li>Reduce: $reduce_help[0]</li>\n";
    echo "<li>Probe: ".exec("probe -version")."</li>\n";
    echo "<li>NOE-display: $noe_version</li>\n";
?>
</ul>

</body>
</html>
