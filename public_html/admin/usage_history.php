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
    ini_set('memory_limit', '2048M');
    $exec_time = time();

#{{{ class LogIter
############################################################################
class LogIter
{
    var $totalRecords = 0;
    var $uniqueSessions = 0;
    var $uniqueIPs = 0;
    var $actions = array();
    var $uniqueActions = array();
    
    var $endTime = 0;
    var $startTime = 1e99;
    
    var $platforms = array();
    var $browsers = array();
    var $combos = array();
    
    function LogIter($start, $end) //{{{
    {
        $bots = $this->listBots();
        $uSess = array();
        $uIPs = array();
        $tmpActions = array();
        
        $in = fopen(MP_BASE_DIR.'/feedback/molprobity.log', 'rb');
        if($in) while(!feof($in))
        {
            $s = trim(fgets($in, 4096));
            if($s == "") continue;
            // 5 forces $msgtext to not split on internal colons
            // $ip:$sess:$time:$msgcode[:$msgtext]
            $f = explode(':', $s, 5);
            $timestamp = $f[2] + 0;
            if($timestamp < $start) continue; // skip these
            elseif($timestamp > $end) break; // guaranteed chronological order
            // Assume IP addr. + sess. ID = globally unique ID
            $uid = $f[0].':'.$f[1];
            if($bots[$uid]) continue; // don't count bots and crawlers
            // Track browser usage
            if($f[3] == "browser-detect")
            {
                $br = recognizeUserAgent($f[4]);
                $this->platforms[ $br['platform'] ] += 1;
                $this->browsers[ $br['browser'] ] += 1;
                $this->combos[ $br['platform']." - ".$br['browser'] ] += 1;
            }
            // Track actual date range
            $this->totalRecords += 1;
            $this->startTime = min($timestamp, $this->startTime);
            $this->endTime = max($timestamp, $this->endTime);
            // Track unique IP numbers and session IDs
            $uSess[$f[1]] = 1;
            $uIPs[ $f[0]] = 1;
            // Track user actions
            $this->actions[$f[3]] += 1;
            $tmpActions[$f[3]][$f[1]] = 1;
        }
        fclose($in);
        
        $this->uniqueSessions = count($uSess);
        $this->uniqueIPs = count($uIPs);
        foreach($tmpActions as $action => $sesslist) $this->uniqueActions[$action] = count($sesslist);
        ksort($this->actions);
        ksort($this->uniqueActions);
        ksort($this->platforms);
        ksort($this->browsers);
        ksort($this->combos);
        //$this->endTime += (60*60*24);
    }//}}}

    function listBots() //{{{
    {
        if(!$GLOBALS['LogIter_allBotIDs'])
        {
            $bots = array();
            $in = fopen(MP_BASE_DIR.'/feedback/molprobity.log', 'rb');
            if($in) while(!feof($in))
            {
                $s = trim(fgets($in, 4096));
                if($s == "") continue;
                // 5 forces $msgtext to not split on internal colons
                // $ip:$sess:$time:$msgcode[:$msgtext]
                $f = explode(':', $s, 5);
                // Assume IP addr. + sess. ID = globally unique ID
                $uid = $f[0].':'.$f[1];
                if($f[3] == "browser-detect")
                {
                    $br = recognizeUserAgent($f[4]);
                    if($br['platform'] == "Bot/Crawler" || $br['platform'] == "Java" || $br['platform'] == "Unknown")
                        $bots[$uid] = 1;
                }
            }
            fclose($in);
            $GLOBALS['LogIter_allBotIDs'] =& $bots;
        }
        return $GLOBALS['LogIter_allBotIDs'];
    }//}}}
    
    function echoBrowserTable() //{{{
    {
        // Horizontal headers: platforms
        echo "<p><table cellspacing='4'><tr align='center'><td></td>";
        foreach($this->platforms as $p => $pCount) echo "<td><b>$p</b></td>";
        echo "<td bgcolor='#ffff99'><b><i>total</i></b></td></tr>\n";
        // Rows: browser types
        $total = 0;
        foreach($this->browsers as $b => $bCount)
        {
            echo "<tr align='center'><td><b>$b</b></td>";
            foreach($this->platforms as $p => $pCount) echo "<td>".$this->combos["$p - $b"]."</td>";
            echo "<td bgcolor='#ffff99'>$bCount</td></tr>\n";
            $total += $bCount;
        }
        // Horizontal footers: platform counts
        echo "<tr align='center' bgcolor='#ffff99'><td><b><i>total</i></b></td>";
        foreach($this->platforms as $p => $pCount) echo "<td>$pCount</td>";
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
    }//}}}
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

if(isset($_REQUEST['start_date']) && strtotime($_REQUEST['start_date']) != -1)
    $start_time = strtotime($_REQUEST['start_date']);
else $start_time = strtotime('1 Jan 2000');
if(isset($_REQUEST['end_date']) && strtotime($_REQUEST['end_date']) != -1)
    $end_time = strtotime($_REQUEST['end_date']);
else $end_time = strtotime('1 Jan 2030'); // past this we exceed the Unix timestamp

$log = new LogIter($start_time, $end_time);
if($start_time < $log->startTime) $start_time = $log->startTime;
if($end_time > $log->endTime)     $end_time   = $log->endTime+(60*60*24);

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
    echo "<br>".$log->totalRecords." records found in the log.\n";
    echo "<br>".$log->uniqueSessions." unique sessions active during this timeframe.\n";
    echo "<br>".$log->uniqueIPs." unique IP addresses active during this timeframe. This is an <i>estimate</i> of the unique users.\n";
    echo "<br><i>All known bots and crawlers have been omitted from these statistics.</i>\n";
    $log->echoBrowserTable();
    echo "<hr>\n";
    
    echo "<h3>Grand summary</h3>\n";
    echo "<i>Use checkboxes to select columns for detailed view, below.</i>\n";
    echo "<table cellspacing='4'>\n";
    echo "<tr><td></td><td><u>Action name</u></td><td><u>Number of times</u></td><td><u>% of sessions</u></td></tr>\n";
    $active_sessions = $log->uniqueSessions;
    $actions =& $log->actions;
    $unique_actions =& $log->uniqueActions;
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
            $sublog = new LogIter($time_range['start'], $time_range['end']);
            $active_sessions = $sublog->uniqueSessions;
            $actions =& $sublog->actions;
            $unique_actions =& $sublog->uniqueActions;
            echo "  <tr align='right' bgcolor='$color'><td align='left'>$time_range[range_text]</td>";
            echo "<td>".$sublog->uniqueIPs."</td>";
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
    echo "<hr>\n<small><center>Time elapsed: ".(time() - $exec_time)." sec";
    if(function_exists('memory_get_usage'))
    {
        echo " | Memory usage: ".formatFilesize(memory_get_usage())." / ".ini_get('memory_limit');
    }
    echo "</center></small>\n";
?>
</body>
</html>
