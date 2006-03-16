<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
Summarizes MolProbity usage over a specified date range.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/../..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR."/lib/core.php");
    require_once(MP_BASE_DIR."/lib/browser.php");
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpInitEnvirons();
    
// Have to do this for big log files ... we'll need a better solution one day.
    ini_set('memory_limit', '128M');

#{{{ getRecords, selectRecords
############################################################################
/**
* Returns an array with one entry for each line in molprobity.log.
* Each entry is an array of the fields on that line, converted to numbers
* where appropriate.
*/
function getRecords($start, $end)
{
    $records = array();
    $in = fopen(MP_BASE_DIR.'/feedback/molprobity.log', 'rb');
    if($in) while(!feof($in))
    {
        $s = trim(fgets($in, 4096));
        if($s == "") continue;
        // 5 forces $msgtext to not split on internal colons
        // $ip:$sess:$time:$msgcode[:$msgtext]
        $f = explode(':', $s, 5);
        $f[2] += 0;
        if($start <= $f[2] && $f[2] <= $end)
            $records[] = $f;
    }
    fclose($in);
    return $records;
}

/** Returns records from getRecords() in the specified interval. */
function selectRecords($records, $start, $end)
{
    $newrec = array();
    foreach($records as $r)
    {
        if($start <= $r[2] && $r[2] <= $end)
            $newrec[] = $r;
    }
    return $newrec;
}
#}}}########################################################################

#{{{ removeBots
############################################################################
/** Returns records from getRecords() minus those caused by bots. */
function removeBots($records)
{
    $bots = array();
    foreach($records as $r)
    {
        // Assume IP addr. + sess. ID = globally unique ID
        $uid = $r[0].':'.$r[1];
        if($r[3] == "browser-detect")
        {
            $br = recognizeUserAgent($r[4]);
            if($br['platform'] == "Bot/Crawler" || $br['platform'] == "Java" || $br['platform'] == "Unknown")
                $bots[$uid] = 1;
        }
    }
    $newrec = array();
    foreach($records as $r)
    {
        // Assume IP addr. + sess. ID = globally unique ID
        $uid = $r[0].':'.$r[1];
        if(! $bots[$uid])
            $newrec[] = $r;
    }
    return $newrec;
}
#}}}########################################################################

#{{{ uniqueSessions, uniqueIPs, countActions, countActionsBySession
############################################################################
/** Number of unique session identifiers in log records. */
function uniqueSessions($records)
{
    $unique = array();
    foreach($records as $rec) $unique[$rec[1]] = 1;
    return count($unique);
}

/** Number of unique IP numbers in log records. */
function uniqueIPs($records)
{
    $unique = array();
    foreach($records as $rec) $unique[$rec[0]] = 0;
    return count($unique);
}

/** Array mapping action IDs to counts of their occurance. */
function countActions($records)
{
    $actions = array();
    foreach($records as $rec) $actions[$rec[3]] = $actions[$rec[3]]+1;
    ksort($actions);
    //asort($actions);
    //$actions = array_reverse($actions, true);
    return $actions;
}

/** Array mapping action IDs to counts of their occurance, max one per session. */
function countActionsBySession($records)
{
    $tmp = array();
    foreach($records as $rec) $tmp[$rec[3]][$rec[1]] = 1;

    $actions = array();
    foreach($tmp as $action => $sesslist) $actions[$action] = count($sesslist);
    ksort($actions);
    return $actions;
}
#}}}########################################################################

#{{{ divideIntoWeeks, divideIntoMonths, divideIntoYears
############################################################################
/** Returns an array of (start, end, range_text) entries for Sun-Sat divisions. */
function divideIntoWeeks($absStart, $absEnd)
{
    $divs = array();
    $secondsPerWeek = 60*60*24*7;
    // First $start is Sunday *before* $absStart
    $d = getdate($absStart);
    $start = mktime(0, 0, 0, $d['mon']+0, $d['mday']+(7-$d['wday']), $d['year']+0) - $secondsPerWeek;
    while($start < $absEnd)
    {
        $end = $start + $secondsPerWeek;
        $divs[] = array('start' => $start, 'end' => $end, 'range_text' => date('j M', $start)." - ".date('j M Y', $end));
        $start = $end;
    }
    return $divs;
}

/** Returns an array of (start, end, range_text) entries for month divisions. */
function divideIntoMonths($absStart, $absEnd)
{
    $divs = array();
    $d = getdate($absStart);
    $start = mktime(0, 0, 0, $d['mon']+0, 1, $d['year']+0);
    while($start < $absEnd)
    {
        $end = mktime(0, 0, 0, $d['mon']+1, 1, $d['year']+0);
        $divs[] = array('start' => $start, 'end' => $end, 'range_text' => date('M Y', $start));
        $start = $end;
        $d = getdate($start);
    }
    return $divs;
}

/** Returns an array of (start, end, range_text) entries for year divisions. */
function divideIntoYears($absStart, $absEnd)
{
    $divs = array();
    $d = getdate($absStart);
    $start = mktime(0, 0, 0, 1, 1, $d['year']+0);
    while($start < $absEnd)
    {
        $end = mktime(0, 0, 0, 1, 1, $d['year']+1);
        $divs[] = array('start' => $start, 'end' => $end, 'range_text' => date('Y', $start));
        $start = $end;
        $d = getdate($start);
    }
    return $divs;
}
#}}}########################################################################

#{{{ countBrowsers, echoBrowserTable
############################################################################
/**
* Three arrays mapping browser names to counts of their occurance:
*   by platform, by browser, by "platform - browser"
*/
function countBrowsers($records)
{
    $platforms = array();
    $browsers = array();
    $combos = array();
    foreach($records as $rec)
    {
        if($rec[3] == "browser-detect")
        {
            $br = recognizeUserAgent($rec[4]);
            $platforms[ $br['platform'] ] += 1;
            $browsers[ $br['browser'] ] += 1;
            $combos[ $br['platform']." - ".$br['browser'] ] += 1;
        }
    }
    ksort($platforms);
    ksort($browsers);
    ksort($combos);
    return array($platforms, $browsers, $combos);
}

function echoBrowserTable($records)
{
    list($platforms, $browsers, $combos) = countBrowsers($records);
    // Horizontal headers: platforms
    echo "<p><table cellspacing='4'><tr align='center'><td></td>";
    foreach($platforms as $p => $pCount) echo "<td><b>$p</b></td>";
    echo "<td bgcolor='#ffff99'><b><i>total</i></b></td></tr>\n";
    // Rows: browser types
    $total = 0;
    foreach($browsers as $b => $bCount)
    {
        echo "<tr align='center'><td><b>$b</b></td>";
        foreach($platforms as $p => $pCount) echo "<td>".$combos["$p - $b"]."</td>";
        echo "<td bgcolor='#ffff99'>$bCount</td></tr>\n";
        $total += $bCount;
    }
    // Horizontal footers: platform counts
    echo "<tr align='center' bgcolor='#ffff99'><td><b><i>total</i></b></td>";
    foreach($platforms as $p => $pCount) echo "<td>$pCount</td>";
    echo "<td>$total</td></tr>\n";
    echo "</table></p>\n";

    //echo "<p><small>Unknown browsers:\n<pre>\n";
    //foreach($records as $rec)
    //{
    //    if($rec[3] == "browser-detect")
    //    {
    //        $br = recognizeUserAgent($rec[4]);
    //        if($br['platform'] == "Unknown") echo $rec[4] . "\n";
    //    }
    //}
    //echo "</pre></small></p>\n";
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

if(isset($_REQUEST['start_date']) && strtotime($_REQUEST['start_date']) != -1)
    $start_time = strtotime($_REQUEST['start_date']);
else $start_time = strtotime('1 Jan 2000');
if(isset($_REQUEST['end_date']) && strtotime($_REQUEST['end_date']) != -1)
    $end_time = strtotime($_REQUEST['end_date']);
else $end_time = strtotime('1 Jan 2030'); // past this we exceed the Unix timestamp

$log = getRecords($start_time, $end_time);
$log = removeBots($log);
$times = array();
foreach($log as $record)
    $times[] = $record[2]+0;
sort($times);
if($start_time < reset($times)) $start_time = reset($times);
if($end_time > end($times))     $end_time   = end($times)+(60*60*24);

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>MolProbity - usage history</title>
    <style type='text/css'><!--
    table { font-size: smaller; }
    --></style>
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
    echo "<form method='post' enctype='multipart/form-data' action='".basename($_SERVER['PHP_SELF'])."'>\n";
    echo "Start date (inclusive): <input type='text' size='15' name='start_date' value='".date('j M Y', $start_time)."'>\n";
    echo "End date (inclusive): <input type='text' size='15' name='end_date' value='".date('j M Y', $end_time)."'>\n";
    echo "<input type='submit' name='cmd' value='Refresh'>\n";
    echo "<p>\n";
    echo "<br>".count($log)." records found in the log.\n";
    echo "<br>".uniqueSessions($log)." unique sessions active during this timeframe.\n";
    echo "<br>".uniqueIPs($log)." unique IP addresses active during this timeframe. This is an <i>estimate</i> of the unique users.\n";
    echo "<br><i>All known bots and crawlers have been omitted from these statistics.</i>\n";
    echoBrowserTable($log);
    echo "<hr>\n";
    
    echo "<h3>Grand summary</h3>\n";
    echo "<i>Use checkboxes to select columns for detailed view, below.</i>\n";
    echo "<table cellspacing='4'>\n";
    echo "<tr><td></td><td><u>Action name</u></td><td><u>Number of times</u></td><td><u>% of sessions</u></td></tr>\n";
    $active_sessions = uniqueSessions($log);
    $actions = countActions($log);
    $unique_actions = countActionsBySession($log);
    $detail_actions = array();
    $color = MP_TABLE_ALT1;
    foreach($actions as $action => $num)
    {
        $detail = $_REQUEST['detail_'.$action];
        if($detail) $detail_actions[] = $action;
        echo "  <tr align='right' bgcolor='$color'><td><input type='checkbox' name='detail_$action' value='1'".($detail ? ' checked' : '')."></td>";
        echo "<td align='left'>$action</td><td>$num</td><td>";
        if($num && $active_sessions)
            echo round(100.0 * $unique_actions[$action] / $active_sessions)."%";
        echo "</td></tr>\n";
        $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
    }
    echo "</table>\n";
    echo "<hr>\n";
    
    echo "<h3>Detailed view</h3>\n";
    echo "<i>Use checkboxes above to select columns for detailed breakdown.</i>\n";
    echo "<br>Divide into \n";
    echo "<select name='divide_into'>\n";
    echo "  <option value='years'".($_REQUEST['divide_into'] == 'years' ? ' selected' : '').">years</option>\n";
    echo "  <option value='months'".($_REQUEST['divide_into'] == 'months' ? ' selected' : '').">months</option>\n";
    echo "  <option value='weeks'".($_REQUEST['divide_into'] == 'weeks' ? ' selected' : '').">weeks</option>\n";
    echo "</select>\n";
    echo "<br><input type='checkbox' name='show_action_pct' value='1'".($_REQUEST['show_action_pct'] ? ' checked' : '')."> Show percentage of sessions for each action\n";
    echo "<p><input type='submit' name='cmd' value='Refresh'>\n";
    
    if($_REQUEST['divide_into'] == 'weeks')         $time_ranges = divideIntoWeeks($start_time, $end_time);
    elseif($_REQUEST['divide_into'] == 'months')    $time_ranges = divideIntoMonths($start_time, $end_time);
    elseif($_REQUEST['divide_into'] == 'years')     $time_ranges = divideIntoYears($start_time, $end_time);
    $show_action_pct = $_REQUEST['show_action_pct'];

    if(isset($time_ranges))
    {
        echo "<p><table cellspacing='2'>\n";
        $color = MP_TABLE_ALT1;
        $i = 0;
        foreach($time_ranges as $time_range)
        {
            // Repeat table headers every N rows
            if($i++ % 16 == 0)
            {
                echo "<tr align='right' bgcolor='$color'><td align='left'><u>Window</u></td>";
                echo "<td><u>Unique IPs</u></td>";
                foreach($detail_actions as $action)
                    echo "<td><u>$action</u></td>";
                echo "</tr>\n";
                $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
            }
            $sublog = selectRecords($log, $time_range['start'], $time_range['end']);
            $active_sessions = uniqueSessions($sublog);
            $actions = countActions($sublog);
            $unique_actions = countActionsBySession($sublog);
            echo "  <tr align='right' bgcolor='$color'><td align='left'>$time_range[range_text]</td>";
            echo "<td>".uniqueIPs($sublog)."</td>";
            foreach($detail_actions as $action)
            {
                echo "<td>".$actions[$action];
                if($show_action_pct && $actions[$action] && $active_sessions)
                    echo " (".round(100.0 * $unique_actions[$action] / $active_sessions)."%)";
                echo "</td>";
            }
            echo "</tr>\n";
            $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
        }
        echo "</table></p>\n";
    }
    
    echo "</form>\n";
    if(function_exists('memory_get_usage'))
    {
        echo "<hr><small><center>Memory usage: ".formatFilesize(memory_get_usage())." / ".ini_get('memory_limit')."</center></small>\n";
    }
?>
</body>
</html>
