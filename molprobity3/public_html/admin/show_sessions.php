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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

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
<table width=100%>
<tr>
<td><b>Session ID</b></td>
<td><b>Last touched</b></td>
<td><b>Time-to-live</b></td>
<td><b>Size on disk</b></td>
<td><!-- space for "Enter" cmd --></td>
<td><!-- space for "Destroy" cmd --></td>
</tr>
<?php
    $sessList = mpEnumerateSessions();
    foreach($sessList as $sess)
    {
        echo "<tr>\n";
        echo "<td>$sess[id]</td>\n";
        echo "<td>".formatMinutesElapsed(MP_SESSION_LIFETIME - $sess['ttl'])." ago</td>\n";
        echo "<td>".formatHoursElapsed($sess['ttl'])."</td>\n";
        echo "<td>".formatFilesize($sess['size'])."</td>\n";
        echo "<td><a href='../home_tab.php?".MP_SESSION_NAME."=$sess[id]'>Enter</a></td>\n";
        echo "<td><a href='$_SERVER[PHP_SELF]?cmd=Destroy&target=$sess[id]'>Destroy</a></td>\n";
        echo "</tr>\n";
    }
?>
</table>
</body>
</html>
