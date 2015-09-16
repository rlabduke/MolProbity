<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD> <TITLE>MolProbity Monitor</title> </HEAD>
<!-- Background white, links blue (unvisited), navy (visited), red (active) -->
<!-- This page runs a server monitor script at molprobityurl/monitor.php.  Optional argument ?refresh=X for a refresh rate. The served data help us maintain the main public server but are irrelevant for most users.  This page should have no interaction with the rest of the MolProbity install. -->

<BODY BGCOLOR="#FFFFFF">

<?php

if ( isset($_GET['refresh']) && count($_GET) > 0 ) {
        $refresh = $_GET['refresh'];
} else $refresh = 30;

$date_cmd = `date`;
$uptime_cmd = `uptime|cut --complement -c -10`;
$who_cmd  = `who`;
$uname_cmd = `uname -norvimp`;
$uname1_cmd = `uname -norvimp | cut -c -48`;
$uname2_cmd = `uname -norvimp | cut --complement -c -48`;
$free_cmd = `free -t -l -m | cut -c -40`;
$free0_cmd = `free -l -h|egrep -v ^Low\|^High\|^-/+`;
$uptime_cmd = `uptime|cut -c 10-`;
//$df_cmd = `df -h /home / /dev /run /run/lock /run/shm /run/user`;
$df_cmd = `df -h /home / /run /run/user`;
$df0_cmd = `df -h /home |grep /home`;
$ps_cmd = `ps -Huwww-data -o pid,ppid,c,%cpu,%mem,stime,time,cmd|egrep -v apache\|ps`;

date_default_timezone_set('America/New_York');
$flastmod = date("D, d-M-Y H:i:s T",filemtime("monitor.php") );

?>

<META http-equiv='refresh' content="<?php echo $refresh ?>"></META>

<?php

echo "<h1 align='center'>MolProbity Monitor</h1>\n";
echo "<p align='center'>$date_cmd (Refreshes every $refresh seconds)</p>\n";

//echo "<p align='center'>$uname1_cmd<br>$uname2_cmd</p>\n";
//echo "<p align='center'>$uptime_cmd<br>$df0_cmd</p>\n";
//echo "<p align='center'>$df0_cmd</p>\n";

echo "<hr width=500>";

echo "<p>MolProbity Disk Usage and Processes: </p><pre>\n";
echo $df0_cmd;
echo "</pre><pre>\n";
echo "$ps_cmd";
echo "</pre>\n";

echo "<p>System Uptime, Memory and Disks: </p><pre>\n";
echo "$uptime_cmd\n"; 
echo "</pre>\n<pre>\n";
echo "$free0_cmd\n";
echo "</pre>\n";
//
//echo "<p>Disks: </p><pre>\n";
echo "<pre>\n";
echo "$df_cmd";
echo "</pre>\n";

echo "<p>Users Logged in: </p><pre>\n";
echo "$who_cmd";
echo "</pre>\n";

echo "<hr width=500>\n";

echo "<p ALIGN='center'>\n";
echo "$uname1_cmd<br>\n$uname2_cmd<br><br>\n";
echo "<i> Script last modified: $flastmod </i>\n";
echo "</p>\n";

?>

</BODY>
</HTML>

