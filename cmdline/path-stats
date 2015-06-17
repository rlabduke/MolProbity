#!/usr/bin/env php
<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Takes the outut of path-extract and condenses it to page-page links and stats.

    e.g. path-extract user_paths.log
        | path-stats
        | java -cp ~/javadev/chiropraxis/chiropraxis.jar chiropraxis.dezymer.PageSpacer
        > tmp.kin && open tmp.kin

INPUTS / SWITCHES (via $_SERVER['argv']):
    inFile          a log file to operate on (optional)

OUTPUTS / RESULTS:
    a list of page-page connections and their timing

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpInitEnvirons();       // use std PATH, etc.
    //mpStartSession(true);   // create session dir
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// First argument is the name of this script...
if(is_array($_SERVER['argv'])) foreach(array_slice($_SERVER['argv'], 1) as $arg)
{
    if(!isset($inFile))         $inFile = $arg;
    else                        die("Too many or unrecognized arguments: '$arg'\n");
}

if(isset($inFile))  $in = fopen($inFile, 'r');
else                $in = STDIN;

$all_uids   = array(); // set of all UIDs
$link_hits  = array(); // [page1:page2] -> count of hits
$link_time  = array(); // [page1:page2] -> sum of times
$link_uids  = array(); // [page1:page2] -> set of UIDs for that link

while(!feof($in))
{
    $line = trim(fgets($in));
    if($line == "") continue;
    $f = explode(':', $line);
    $uid = $f[0];
    $page1 = $f[1];
    $page2 = $f[2];
    $link = $page1.':'.$page2;
    $time = $f[3] + 0;
    
    $all_uids[$uid]         = $uid;
    $link_hits[$link]       += 1;
    $link_time[$link]       += $time;
    $link_uids[$link][$uid] = $uid;
}
fclose($in);

echo "#page1:page2:total_hits:frac_sess:mean_time\n";
foreach($link_hits as $link => $hits)
{
    $time = $link_time[$link];
    $uids = count($link_uids[$link]);
    echo $link.':'.$hits.':'.round($uids / count($all_uids), 3).':'.round($time / $hits, 1)."\n";
}
############################################################################
// Clean up and go home
//mpDestroySession(); // only call this if we created one
?>
