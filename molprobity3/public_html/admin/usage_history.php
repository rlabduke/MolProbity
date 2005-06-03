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
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpInitEnvirons();

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
    $in = fopen(MP_BASE_DIR.'/public_html/data/molprobity.log', 'rb');
    if($in) while(!feof($in))
    {
        $s = trim(fgets($in, 4096));
        if($s == "") continue;
        $f = explode(':', $s); // $ip:$sess:$time:$msgcode[:$msgtext]
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

if(isset($_REQUEST['start_date']) && strtotime($_REQUEST['start_date']) != -1)
    $start_time = strtotime($_REQUEST['start_date']);
else $start_time = strtotime('1 Jan 2000');
if(isset($_REQUEST['end_date']) && strtotime($_REQUEST['end_date']) != -1)
    $end_time = strtotime($_REQUEST['end_date']);
else $end_time = strtotime('1 Jan 2030');

$log = getRecords($start_time, $end_time);
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
    echo "<br>Divide into \n";
    echo "<select name='divide_into'>\n";
    echo "  <option value='years'".($_REQUEST['divide_into'] == 'years' ? ' selected' : '').">years</option>\n";
    echo "  <option value='months'".($_REQUEST['divide_into'] == 'months' ? ' selected' : '').">months</option>\n";
    echo "  <option value='weeks'".($_REQUEST['divide_into'] == 'weeks' ? ' selected' : '').">weeks</option>\n";
    echo "</select>\n";
    echo "<input type='checkbox' name='show_action_pct' value='1'".($_REQUEST['show_action_pct'] ? ' checked' : '')."> Show percentage of sessions for each action\n";
    echo "<br><input type='submit' name='cmd' value='Refresh'>\n";
    echo "<hr>\n";
    echo count($log)." records found in the log.<br>\n";
    echo uniqueSessions($log)." unique sessions active during this timeframe.<br>\n";
    echo uniqueIPs($log)." unique IP addresses active during this timeframe. This is an <i>estimate</i> of the unique users.<br>\n";
    
    echo "<p>Grand summary, use checkboxes for detailed view below:<br>\n<table cellspacing='4'>\n";
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
    echo "</table></p>\n";
    
    if($_REQUEST['divide_into'] == 'weeks')         $time_ranges = divideIntoWeeks($start_time, $end_time);
    elseif($_REQUEST['divide_into'] == 'months')    $time_ranges = divideIntoMonths($start_time, $end_time);
    elseif($_REQUEST['divide_into'] == 'years')     $time_ranges = divideIntoYears($start_time, $end_time);
    $show_action_pct = $_REQUEST['show_action_pct'];

    if(isset($time_ranges))
    {
        echo "<p><table cellspacing='4'>\n";
        echo "<tr align='right'><td align='left'><u>Window</u></td>";
        echo "<td><u>Unique IPs</u></td>";
        foreach($detail_actions as $action)
            echo "<td><u>$action</u></td>";
        echo "</tr>\n";
        $color = MP_TABLE_ALT1;
        foreach($time_ranges as $time_range)
        {
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
?>
</body>
</html>
