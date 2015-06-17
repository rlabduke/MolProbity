<?php
/*****************************************************************
    Modified 1 July 2005 by Ian W. Davis
    Returns an array with keys 'browser', 'version', 'platform', 'aol'
    
    

    File name: browser.php
    Author: Gary White
    Last modified: November 10, 2003
    
    **************************************************************

    Copyright (C) 2003  Gary White
    
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details at:
    http://www.gnu.org/copyleft/gpl.html

    **************************************************************

    Browser class
    
    Identifies the user's Operating system, browser and version
    by parsing the HTTP_USER_AGENT string sent to the server
    
    Typical Usage:
    
        require_once($_SERVER['DOCUMENT_ROOT'].'/include/browser.php');
        $br = new Browser;
        echo "$br->Platform, $br->Name version $br->Version";
    
    For operating systems, it will correctly identify:
        Microsoft Windows
        Apple Macintosh
        Linux
        SGI
        Sun
        OS/2
        BeOS
        Java
        Bot/Crawler

    Anything not determined to be one of the above is considered to by Unix
    because most Unix based browsers seem to not report the operating system.
    The only known problem here is that, if a HTTP_USER_AGENT string does not
    contain the operating system, it will be identified as Unix. For unknown
    browsers, this may not be correct.
    
    For browsers, it should correctly identify all versions of:
        Amaya
        Galeon
        iCab
        Internet Explorer
            For AOL versions it will identify as Internet Explorer (AOL) and the version
            will be the AOL version instead of the IE version.
        Konqueror
        Lynx
        Mozilla
        Netscape Navigator/Communicator
        OmniWeb
        Opera
        Pocket Internet Explorer for handhelds
        Safari
        WebTV
*****************************************************************/

function recognizeUserAgent($agent = null)
{
    if($agent == null) $agent = $_SERVER['HTTP_USER_AGENT'];

    // initialize properties
    $bd['platform'] = "Unknown";
    $bd['browser'] = "Unknown";
    $bd['version'] = "Unknown";

    // find operating system
    if (preg_match("/win/i", $agent))
        $bd['platform'] = "Windows";
    elseif (preg_match("/mac/i", $agent))
        $bd['platform'] = "Apple Macintosh";
    elseif (preg_match("/linux/i", $agent))
        $bd['platform'] = "Linux";
    elseif (preg_match("/sgi|irix/i", $agent))
        $bd['platform'] = "SGI";
    elseif (preg_match("/sunos/i", $agent))
        $bd['platform'] = "Sun";
    elseif (preg_match("@OS/2@i", $agent))
        $bd['platform'] = "OS/2";
    elseif (preg_match("/BeOS/i", $agent))
        $bd['platform'] = "BeOS";
    elseif (preg_match("/java|jakarta/i", $agent))
        $bd['platform'] = "Java";
    elseif (preg_match('/bot|crawler|yahoo|ask|heritrix/i', $agent))
        $bd['platform'] = "Bot/Crawler";

    // test for Opera        
    if (preg_match("/opera/i",$agent)){
        $val = stristr($agent, "opera");
        if (preg_match("@/@", $val)){
            $val = explode("/",$val);
            $bd['browser'] = $val[0];
            $val = explode(" ",$val[1]);
            $bd['version'] = $val[0];
        }else{
            $val = explode(" ",stristr($val,"opera"));
            $bd['browser'] = $val[0];
            $bd['version'] = $val[1];
        }

    // test for WebTV
    }elseif(preg_match("/webtv/i",$agent)){
        $val = explode("/",stristr($agent,"webtv"));
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];
    
    // test for MS Internet Explorer version 1
    }elseif(preg_match("/microsoft internet explorer/i", $agent)){
        $bd['browser'] = "MSIE";
        $bd['version'] = "1.0";
        $var = stristr($agent, "/");
        if (preg_match("/308|425|426|474|0b1/", $var)){
            $bd['version'] = "1.5";
        }

    // test for NetPositive
    }elseif(preg_match("/NetPositive/", $agent)){
        $val = explode("/",stristr($agent,"NetPositive"));
        $bd['platform'] = "BeOS";
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];

    // test for MS Internet Explorer
    }elseif(preg_match("/msie/i",$agent) && !preg_match("/opera/i",$agent)){
        $val = explode(" ",stristr($agent,"msie"));
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];
    
    // test for MS Pocket Internet Explorer
    }elseif(preg_match("/mspie/i",$agent) || preg_match('/pocket/i', $agent)){
        $val = explode(" ",stristr($agent,"mspie"));
        $bd['browser'] = "MSPIE";
        $bd['platform'] = "WindowsCE";
        if (preg_match("/mspie/i", $agent))
            $bd['version'] = $val[1];
        else {
            $val = explode("/",$agent);
            $bd['version'] = $val[1];
        }
        
    // test for Galeon
    }elseif(preg_match("/galeon/i",$agent)){
        $val = explode(" ",stristr($agent,"galeon"));
        $val = explode("/",$val[0]);
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];
        
    // test for Konqueror
    }elseif(preg_match("/Konqueror/i",$agent)){
        $val = explode(" ",stristr($agent,"Konqueror"));
        $val = explode("/",$val[0]);
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];
        
    // test for iCab
    }elseif(preg_match("/icab/i",$agent)){
        $val = explode(" ",stristr($agent,"icab"));
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];

    // test for OmniWeb
    }elseif(preg_match("/omniweb/i",$agent)){
        $val = explode("/",stristr($agent,"omniweb"));
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];

    // test for Phoenix
    }elseif(preg_match("/Phoenix/i", $agent)){
        $bd['browser'] = "Phoenix";
        $val = explode("/", stristr($agent,"Phoenix/"));
        $bd['version'] = $val[1];
    
    // test for Firebird
    }elseif(preg_match("/firebird/i", $agent)){
        $bd['browser']="Firebird";
        $val = stristr($agent, "Firebird");
        $val = explode("/",$val);
        $bd['version'] = $val[1];
        
    // test for Firefox
    }elseif(preg_match("/Firefox/i", $agent)){
        $bd['browser']="Firefox";
        $val = stristr($agent, "Firefox");
        $val = explode("/",$val);
        $bd['version'] = $val[1];
        
    // test for Mozilla Alpha/Beta Versions
    }elseif(preg_match("/mozilla/i",$agent) && 
        preg_match("/rv:[0-9].[0-9][a-b]/i",$agent) && !preg_match("/netscape/i",$agent)){
        $bd['browser'] = "Mozilla";
        $val = explode(" ",stristr($agent,"rv:"));
        preg_match("/rv:[0-9].[0-9][a-b]/i",$agent,$val);
        $bd['version'] = str_replace("rv:","",$val[0]);
        
    // test for Mozilla Stable Versions
    }elseif(preg_match("/mozilla/i",$agent) &&
        preg_match("/rv:[0-9]\.[0-9]/i",$agent) && !preg_match("/netscape/i",$agent)){
        $bd['browser'] = "Mozilla";
        $val = explode(" ",stristr($agent,"rv:"));
        preg_match("/rv:[0-9]\.[0-9]\.[0-9]/i",$agent,$val);
        $bd['version'] = str_replace("rv:","",$val[0]);
    
    // test for Lynx & Amaya
    }elseif(preg_match("/libwww/i", $agent)){
        if (preg_match("/amaya/i", $agent)){
            $val = explode("/",stristr($agent,"amaya"));
            $bd['browser'] = "Amaya";
            $val = explode(" ", $val[1]);
            $bd['version'] = $val[0];
        } else {
            $val = explode("/",$agent);
            $bd['browser'] = "Lynx";
            $bd['version'] = $val[1];
        }
    
    // test for Safari
    }elseif(preg_match("/safari/i", $agent)){
        $bd['browser'] = "Safari";
        $bd['version'] = "";

    // test for Googlebot
    }elseif(preg_match("/googlebot/i", $agent)){
        $bd['browser'] = "Googlebot";
        $bd['version'] = "";

    // test for MSN bot
    }elseif(preg_match("/msnbot/i", $agent)){
        $bd['browser'] = "MSN bot";
        $bd['version'] = "";

    // remaining two tests are for Netscape
    }elseif(preg_match("/netscape/i",$agent)){
        $val = explode(" ",stristr($agent,"netscape"));
        $val = explode("/",$val[0]);
        $bd['browser'] = $val[0];
        $bd['version'] = $val[1];
    }elseif(preg_match("/mozilla/i",$agent) && !preg_match("/rv:[0-9]\.[0-9]\.[0-9]/i",$agent)){
        $val = explode(" ",stristr($agent,"mozilla"));
        $val = explode("/",$val[0]);
        $bd['browser'] = "Netscape";
        $bd['version'] = $val[1];
    }
    
    // clean up extraneous garbage that may be in the name
    $bd['browser'] = preg_replace("/[^a-z,A-Z]/", "", $bd['browser']);
    // clean up extraneous garbage that may be in the version        
    $bd['version'] = preg_replace("/[^0-9,.,a-z,A-Z]/", "", $bd['version']);
    
    // check for AOL
    if (preg_match("/AOL/i", $agent)){
        $var = stristr($agent, "AOL");
        $var = explode(" ", $var);
        $bd['aol'] = preg_replace("/[^0-9,.,a-z,A-Z]/", "", $var[1]);
    }
    
    return $bd;
}
?>
