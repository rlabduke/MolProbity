<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Defines string-handling and common formatting functions.
*****************************************************************************/

#{{{ startsWith - tests whether haystack starts with needle
############################################################################
function startsWith($haystack, $needle)
{
    return (strncmp($haystack, $needle, strlen($needle)) == 0);
}
#}}}########################################################################

#{{{ formatFilesize - human-readable file size with at least 2 sig. digits.
############################################################################
function formatFilesize($size)
{
        if( $size >= 10000000000 ) $size = round($size/1000000000, 0) . " Gb";
    elseif( $size >= 1000000000 )  $size = round($size/1000000000, 1) . " Gb";
    elseif( $size >= 10000000 )    $size = round($size/1000000, 0) . " Mb";
    elseif( $size >= 1000000 )     $size = round($size/1000000, 1) . " Mb";
    elseif( $size >= 10000 )       $size = round($size/1000, 0) . " Kb";
    elseif( $size >= 1000 )        $size = round($size/1000, 1) . " Kb";
    else $size = $size . " bytes";

    return $size;
}
#}}}########################################################################

#{{{ formatDayTime - formats the day and time from a Unix timestamp
############################################################################
function formatDayTime($time)
{
    return formatDayAdaptive($time) . " at " . formatTime($time);
}
#}}}########################################################################

#{{{ formatDayAdaptive - formats the day intelligently from a Unix timestamp
############################################################################
// TODO: This doesn't treat cases that straddle the new year...
function formatDayAdaptive($time)
{
    global $TIME_ZONES;
    $zone           = (isset($_SESSION['timeZone']) ? $_SESSION['timeZone'] : MP_DEFAULT_TIMEZONE);
    $zone_abbrev    = $TIME_ZONES[$zone]['abbrev'];
    $offset_sec     = $TIME_ZONES[$zone]['seconds'];
    
    $now            = time();
    $nowDate        = getdate($now+$offset_sec);
    $timeDate       = getdate($time+$offset_sec);
    if($nowDate['year'] == $timeDate['year'] && $nowDate['yday']+0 == $timeDate['yday']+0)
        return "Today";
    elseif($nowDate['year'] == $timeDate['year'] && $nowDate['yday']+0 == $timeDate['yday']+1)
        return "Yesterday";
    elseif($nowDate['year'] == $timeDate['year'] && $nowDate['yday']+0 > $timeDate['yday']+0 && $nowDate['yday']+0 < $timeDate['yday']+7)
        return gmdate("l", ($time+$offset_sec));
    else
        return gmdate("j M Y", ($time+$offset_sec));
}
#}}}########################################################################

#{{{ formatTime - formats the time only from a Unix timestamp
############################################################################
function formatTime($time)
{
    global $TIME_ZONES;
    $zone           = (isset($_SESSION['timeZone']) ? $_SESSION['timeZone'] : MP_DEFAULT_TIMEZONE);
    $zone_abbrev    = $TIME_ZONES[$zone]['abbrev'];
    $offset_sec     = $TIME_ZONES[$zone]['seconds'];
    return gmdate("g:ia", ($time+$offset_sec)) . " $zone_abbrev";
}
#}}}########################################################################

#{{{ formatHoursElapsed - displays time-to-live as "days, hours"
############################################################################
function formatHoursElapsed($secs)
{
    $one_hour   = 60*60;
    $one_day    = $one_hour*24;

    $days = floor($secs / $one_day);
    if($days >= 2)
        $msg = "$days days and ";
    elseif($days >= 1)
        $msg = "$days day and ";
    
    $secs -= $days * $one_day;
    $hours = floor($secs / $one_hour);
    if($hours >= 2)
        $msg .= "$hours hours";
    elseif($hours >= 1)
        $msg .= "$hours hour";
    else
        $msg .= "less than an hour";
        
    return $msg;
}
#}}}########################################################################

#{{{ formatMinutesElapsed - displays time-to-live as "days, hours, minutes"
############################################################################
function formatMinutesElapsed($secs)
{
    $one_minute = 60;
    $one_hour   = $one_minute*60;
    $one_day    = $one_hour*24;

    $days = floor($secs / $one_day);
    if($days >= 2)
        $msg = "$days days, ";
    elseif($days >= 1)
        $msg = "$days day, ";
    
    $secs -= $days * $one_day;
    $hours = floor($secs / $one_hour);
    if($hours >= 2)
        $msg .= "$hours hours and ";
    elseif($hours >= 1)
        $msg .= "$hours hour and ";
        
    $secs -= $hours * $one_hour;
    $minutes = floor($secs / $one_minute);
    if($minutes >= 2)
        $msg .= "$minutes minutes";
    elseif($minutes >= 1)
        $msg .= "$minutes minute";
    else
        $msg .= "less than one minute";
        
    return $msg;
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
?>
