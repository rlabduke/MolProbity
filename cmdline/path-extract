#!/usr/bin/env php
<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This script parses a file like feedback/user_paths.log and outputs a list
    of page-page links, one per line.
    
    e.g. path-extract user_paths.log
        | path-stats
        | java -cp ~/javadev/chiropraxis/chiropraxis.jar chiropraxis.dezymer.PageSpacer
        > tmp.kin && open tmp.kin

INPUTS / SWITCHES (via $_SERVER['argv']):
    inFile          a log file to operate on

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

if(!isset($inFile))         die("No input file specified.\n");
elseif(!is_file($inFile))   die("Input file '$inFile' does not exist.\n");

$last_page = array(); // last page a particular uid has visited
$last_time = array(); // last time a page was accessed

$in = fopen($inFile, 'r');
while(!feof($in))
{
    $line = trim(fgets($in));
    $f = explode(':', $line);
    $uid = $f[0].'~'.$f[1]; // IP + session ID
    $time = $f[2] + 0;
    $page = $f[3];
    if($page == 'job_progress.php') continue;
    
    if($last_page[$uid] && $last_page[$uid] != $page)
    {
        echo $uid.':'.$last_page[$uid].':'.$page.':'.($time - $last_time[$uid])."\n";
    }
    
    $last_page[$uid] = $page;
    $last_time[$uid] = $time;
}
fclose($in);
############################################################################
// Clean up and go home
//mpDestroySession(); // only call this if we created one
?>
