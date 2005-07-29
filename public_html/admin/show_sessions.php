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

function sortTTL($a, $b) { return $b['ttl'] - $a['ttl']; }
function sortSize($a, $b) { return $b['size'] - $a['size']; }

if($_REQUEST['cmd'] == "Destroy")
{
    mpSessDestroy($_REQUEST['target']);
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>MolProbity - show sessions</title>
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#000099" vlink="#000099" alink="#990000">
<center>
[ <a href='phpinfo.php'>PHP info</a>
| <a href='check_config.php'>Configuration check</a>
| <a href='show_sessions.php'>Live sessions</a>
| <a href='usage_history.php'>Usage history</a>
]
</center><hr>
<?php
    $sessList = mpEnumerateSessions();
    if(count($sessList) > 0)
    {
        if($_REQUEST['sort'] == 'ttl')      usort($sessList, 'sortTTL');
        elseif($_REQUEST['sort'] == 'size') usort($sessList, 'sortSize');
        else                                usort($sessList, 'sortTTL');
        
        
        echo "<table width=100%>\n";
        echo "<tr>\n";
        echo "<td><b>Session ID</b></td>\n";
        echo "<td><a href='".basename($_SERVER['PHP_SELF'])."?sort=ttl'><b>Last touched</b></a></td>\n";
        echo "<td><b>Time-to-live</b></td>\n";
        echo "<td><a href='".basename($_SERVER['PHP_SELF'])."?sort=size'><b>Size on disk</b></a></td>\n";
        echo "<td><!-- space for Debug cmd --></td>\n";
        echo "<td><!-- space for Enter cmd --></td>\n";
        echo "<td><!-- space for Destroy cmd --></td>\n";
        echo "</tr>\n";
        foreach($sessList as $sess)
        {
            echo "<tr>\n";
            echo "<td>$sess[id]</td>\n";
            echo "<td>".formatMinutesElapsed(MP_SESSION_LIFETIME - $sess['ttl'])." ago</td>\n";
            echo "<td>".formatHoursElapsed($sess['ttl'])."</td>\n";
            echo "<td>".formatFilesize($sess['size'])."</td>\n";
            echo "<td><a href='../viewdebug.php?".MP_SESSION_NAME."=$sess[id]' target='_blank'>Debug</a></td>\n";
            echo "<td><a href='../index.php?".MP_SESSION_NAME."=$sess[id]'>Enter</a></td>\n";
            // We use basename() to get "index.php" instead of the full path,
            // which is subject to corruption with URL forwarding thru kinemage.
            echo "<td><a href='".basename($_SERVER['PHP_SELF'])."?cmd=Destroy&target=$sess[id]'>Destroy</a></td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    else
        echo "<center><i>No live sessions found on disk.</i></center>\n";
?>
</body>
</html>
