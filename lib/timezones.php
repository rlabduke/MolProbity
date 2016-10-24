<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*******************************************************************************
    Defines a global $TIME_ZONES that is an array keyed on time zone
    abbreviations. Each entry is an array itself, with these keys:
    'name'      the long name of the time zone, in English
    'abbrev'    a short abbreviation for the zone. Usually the same as this
                entry's key, except for CST/CDT and EST/EDT (see entries below).
    'hours'     offset from GMT/UTC in hours
    'seconds'   offset from GMT/UTC in seconds (for convenience)
*******************************************************************************/
#{{{ $TIME_ZONES - the array of time zone data
############################################################################
$TIME_ZONES = array(
    'A' => array('name' => 'Alpha Time Zone', 'abbrev' => 'A', 'hours' => 1, 'seconds' => 3600),
    'ACDT' => array('name' => 'Australian Central Daylight Time', 'abbrev' => 'ACDT', 'hours' => 10.5, 'seconds' => 37800),
    'ACST' => array('name' => 'Australian Central Standard Time', 'abbrev' => 'ACST', 'hours' => 9.5, 'seconds' => 34200),
    'ADT' => array('name' => 'Atlantic Daylight Time', 'abbrev' => 'ADT', 'hours' => -3, 'seconds' => -10800),
    'AEDT' => array('name' => 'Australian Eastern Daylight Time', 'abbrev' => 'AEDT', 'hours' => 11, 'seconds' => 39600),
    'AEST' => array('name' => 'Australian Eastern Standard Time', 'abbrev' => 'AEST', 'hours' => 10, 'seconds' => 36000),
    'AKDT' => array('name' => 'Alaska Daylight Time', 'abbrev' => 'AKDT', 'hours' => -8, 'seconds' => -28800),
    'AKST' => array('name' => 'Alaska Standard Time', 'abbrev' => 'AKST', 'hours' => -9, 'seconds' => -32400),
    'AST' => array('name' => 'Atlantic Standard Time', 'abbrev' => 'AST', 'hours' => -4, 'seconds' => -14400),
    'AWST' => array('name' => 'Australian Western Standard Time', 'abbrev' => 'AWST', 'hours' => 8, 'seconds' => 28800),
    'B' => array('name' => 'Bravo Time Zone', 'abbrev' => 'B', 'hours' => 2, 'seconds' => 7200),
    'BST' => array('name' => 'British Summer Time', 'abbrev' => 'BST', 'hours' => 1, 'seconds' => 3600),
    'C' => array('name' => 'Charlie Time Zone', 'abbrev' => 'C', 'hours' => 3, 'seconds' => 10800),
    'CDT_AU' => array('name' => 'Central Daylight Time (Australia)', 'abbrev' => 'CDT', 'hours' => 10.5, 'seconds' => 37800),
    'CDT' => array('name' => 'Central Daylight Time (North America)', 'abbrev' => 'CDT', 'hours' => -5, 'seconds' => -18000),
    'CEST' => array('name' => 'Central European Summer Time', 'abbrev' => 'CEST', 'hours' => 2, 'seconds' => 7200),
    'CET' => array('name' => 'Central European Time', 'abbrev' => 'CET', 'hours' => 1, 'seconds' => 3600),
    'CST_AU' => array('name' => 'Central Standard Time (Australia)', 'abbrev' => 'CST', 'hours' => 9.5, 'seconds' => 34200),
    'CST' => array('name' => 'Central Standard Time (North America)', 'abbrev' => 'CST', 'hours' => -6, 'seconds' => -21600),
    'CXT' => array('name' => 'Christmas Island Time', 'abbrev' => 'CXT', 'hours' => 7, 'seconds' => 25200),
    'D' => array('name' => 'Delta Time Zone', 'abbrev' => 'D', 'hours' => 4, 'seconds' => 14400),
    'E' => array('name' => 'Echo Time Zone', 'abbrev' => 'E', 'hours' => 5, 'seconds' => 18000),
    'EDT_AU' => array('name' => 'Eastern Daylight Time (Australia)', 'abbrev' => 'EDT', 'hours' => 11, 'seconds' => 39600),
    'EDT' => array('name' => 'Eastern Daylight Time (North America)', 'abbrev' => 'EDT', 'hours' => -4, 'seconds' => -14400),
    'EEST' => array('name' => 'Eastern European Summer Time', 'abbrev' => 'EEST', 'hours' => 3, 'seconds' => 10800),
    'EET' => array('name' => 'Eastern European Time', 'abbrev' => 'EET', 'hours' => 2, 'seconds' => 7200),
    'EST_AU' => array('name' => 'Eastern Standard Time (Australia)', 'abbrev' => 'EST', 'hours' => 10, 'seconds' => 36000),
    'EST' => array('name' => 'Eastern Standard Time (North America)', 'abbrev' => 'EST', 'hours' => -5, 'seconds' => -18000),
    'F' => array('name' => 'Foxtrot Time Zone', 'abbrev' => 'F', 'hours' => 6, 'seconds' => 21600),
    'G' => array('name' => 'Golf Time Zone', 'abbrev' => 'G', 'hours' => 7, 'seconds' => 25200),
    'GMT' => array('name' => 'Greenwich Mean Time', 'abbrev' => 'GMT', 'hours' => 0, 'seconds' => 0),
    'H' => array('name' => 'Hotel Time Zone', 'abbrev' => 'H', 'hours' => 8, 'seconds' => 28800),
    'HAA' => array('name' => 'Heure Advanc&#233; de l\'Atlantique', 'abbrev' => 'HAA', 'hours' => -3, 'seconds' => -10800),
    'HAC' => array('name' => 'Heure Advanc&#233; du Centre', 'abbrev' => 'HAC', 'hours' => -5, 'seconds' => -18000),
    'HADT' => array('name' => 'Hawaii-Aleutian Daylight Time', 'abbrev' => 'HADT', 'hours' => -9, 'seconds' => -32400),
    'HAE' => array('name' => 'Heure Advanc&#233; de l\'Est', 'abbrev' => 'HAE', 'hours' => -4, 'seconds' => -14400),
    'HAP' => array('name' => 'Heure Advanc&#233; du Pacifique', 'abbrev' => 'HAP', 'hours' => -7, 'seconds' => -25200),
    'HAR' => array('name' => 'Heure Advanc&#233; des Rocheuses', 'abbrev' => 'HAR', 'hours' => -6, 'seconds' => -21600),
    'HAST' => array('name' => 'Hawaii-Aleutian Standard Time', 'abbrev' => 'HAST', 'hours' => -10, 'seconds' => -36000),
    'HAT' => array('name' => 'Heure Advanc&#233; de Terre-Neuve', 'abbrev' => 'HAT', 'hours' => -2.5, 'seconds' => -9000),
    'HAY' => array('name' => 'Heure Advanc&#233; du Yukon', 'abbrev' => 'HAY', 'hours' => -8, 'seconds' => -28800),
    'HNA' => array('name' => 'Heure Normale de l\'Atlantique', 'abbrev' => 'HNA', 'hours' => -4, 'seconds' => -14400),
    'HNC' => array('name' => 'Heure Normale du Centre', 'abbrev' => 'HNC', 'hours' => -6, 'seconds' => -21600),
    'HNE' => array('name' => 'Heure Normale de l\'Est', 'abbrev' => 'HNE', 'hours' => -5, 'seconds' => -18000),
    'HNP' => array('name' => 'Heure Normale du Pacifique', 'abbrev' => 'HNP', 'hours' => -8, 'seconds' => -28800),
    'HNR' => array('name' => 'Heure Normale des Rocheuses', 'abbrev' => 'HNR', 'hours' => -7, 'seconds' => -25200),
    'HNT' => array('name' => 'Heure Normale de Terre-Neuve', 'abbrev' => 'HNT', 'hours' => -3.5, 'seconds' => -12600),
    'HNY' => array('name' => 'Heure Normale du Yukon', 'abbrev' => 'HNY', 'hours' => -9, 'seconds' => -32400),
    'I' => array('name' => 'India Time Zone', 'abbrev' => 'I', 'hours' => 9, 'seconds' => 32400),
    'IST' => array('name' => 'Irish Summer Time', 'abbrev' => 'IST', 'hours' => 1, 'seconds' => 3600),
    'K' => array('name' => 'Kilo Time Zone', 'abbrev' => 'K', 'hours' => 10, 'seconds' => 36000),
    'L' => array('name' => 'Lima Time Zone', 'abbrev' => 'L', 'hours' => 11, 'seconds' => 39600),
    'M' => array('name' => 'Mike Time Zone', 'abbrev' => 'M', 'hours' => 12, 'seconds' => 43200),
    'MDT' => array('name' => 'Mountain Daylight Time', 'abbrev' => 'MDT', 'hours' => -6, 'seconds' => -21600),
    'MESZ' => array('name' => 'Mitteleurop&#228;ische Sommerzeit', 'abbrev' => 'MESZ', 'hours' => 2, 'seconds' => 7200),
    'MEZ' => array('name' => 'Mitteleurop&#228;ische Zeit', 'abbrev' => 'MEZ', 'hours' => 1, 'seconds' => 3600),
    'MST' => array('name' => 'Mountain Standard Time', 'abbrev' => 'MST', 'hours' => -7, 'seconds' => -25200),
    'N' => array('name' => 'November Time Zone', 'abbrev' => 'N', 'hours' => -1, 'seconds' => -3600),
    'NDT' => array('name' => 'Newfoundland Daylight Time', 'abbrev' => 'NDT', 'hours' => -2.5, 'seconds' => -9000),
    'NFT' => array('name' => 'Norfolk (Island) Time', 'abbrev' => 'NFT', 'hours' => 11.5, 'seconds' => 41400),
    'NST' => array('name' => 'Newfoundland Standard Time', 'abbrev' => 'NST', 'hours' => -3.5, 'seconds' => -12600),
    'O' => array('name' => 'Oscar Time Zone', 'abbrev' => 'O', 'hours' => -2, 'seconds' => -7200),
    'P' => array('name' => 'Papa Time Zone', 'abbrev' => 'P', 'hours' => -3, 'seconds' => -10800),
    'PDT' => array('name' => 'Pacific Daylight Time', 'abbrev' => 'PDT', 'hours' => -7, 'seconds' => -25200),
    'PST' => array('name' => 'Pacific Standard Time', 'abbrev' => 'PST', 'hours' => -8, 'seconds' => -28800),
    'Q' => array('name' => 'Quebec Time Zone', 'abbrev' => 'Q', 'hours' => -4, 'seconds' => -14400),
    'R' => array('name' => 'Romeo Time Zone', 'abbrev' => 'R', 'hours' => -5, 'seconds' => -18000),
    'S' => array('name' => 'Sierra Time Zone', 'abbrev' => 'S', 'hours' => -6, 'seconds' => -21600),
    'T' => array('name' => 'Tango Time Zone', 'abbrev' => 'T', 'hours' => -7, 'seconds' => -25200),
    'U' => array('name' => 'Uniform Time Zone', 'abbrev' => 'U', 'hours' => -8, 'seconds' => -28800),
    'UTC' => array('name' => 'Coordinated Universal Time', 'abbrev' => 'UTC', 'hours' => 0, 'seconds' => 0),
    'V' => array('name' => 'Victor Time Zone', 'abbrev' => 'V', 'hours' => -9, 'seconds' => -32400),
    'W' => array('name' => 'Whiskey Time Zone', 'abbrev' => 'W', 'hours' => -10, 'seconds' => -36000),
    'WEST' => array('name' => 'Western European Summer Time', 'abbrev' => 'WEST', 'hours' => 1, 'seconds' => 3600),
    'WET' => array('name' => 'Western European Time', 'abbrev' => 'WET', 'hours' => 0, 'seconds' => 0),
    'WST' => array('name' => 'Western Standard Time', 'abbrev' => 'WST', 'hours' => 8, 'seconds' => 28800),
    'X' => array('name' => 'X-ray Time Zone', 'abbrev' => 'X', 'hours' => -11, 'seconds' => -39600),
    'Y' => array('name' => 'Yankee Time Zone', 'abbrev' => 'Y', 'hours' => -12, 'seconds' => -43200),
    'Z' => array('name' => 'Zulu Time Zone', 'abbrev' => 'Z', 'hours' => 0, 'seconds' => 0)
);
#}}}########################################################################

#{{{ guessDefaultTimezone - guesses the time zone of the server machine
############################################################################
function guessDefaultTimezone()
{
    //Check if the php.ini has a timezone. If not, use UTC.
    if(!ini_get('date.timezone'))
      {
        define("MP_DEFAULT_TIMEZONE", 'UTC');
        date_default_timezone_set('UTC');
        return;
      }
    //If php.ini does have a timezone, attempt to parse that timezone
    global $TIME_ZONES;
    $seconds = date('Z')+0;
    $abbrev = date('T');
    foreach($TIME_ZONES as $zone => $data)
    {
        if($data['seconds'] == $seconds && $data['abbrev'] == $abbrev)
        {
            define("MP_DEFAULT_TIMEZONE", $zone);
            break;
        }
    }
    if(!defined('MP_DEFAULT_TIMEZONE')) define("MP_DEFAULT_TIMEZONE", 'UTC');
}
#}}}########################################################################

#{{{ timeZonePicker - produces a HTML drop-box of timezone choices
############################################################################
/**
* Creates a <SELECT name='$varname'> form element with
* the specified zone pre-selected. 
*/
function timeZonePicker($varname, $zone = 'UTC')
{
    global $TIME_ZONES;
    
    $s = "<select name='$varname'>\n";
    foreach($TIME_ZONES as $key => $val)
    {
        if($key == $zone)
            $s .= "<option selected value='$key'>$val[name]</option>\n";
        else
            $s .= "<option value='$key'>$val[name]</option>\n";
    }
    $s .= "</select>\n";
    
    return $s;
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
?>
