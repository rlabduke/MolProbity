#!/usr/bin/env php
<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
INPUTS / SWITCHES (via $_SERVER['argv']):
    inFile          a PDB file to operate on

OUTPUTS / RESULTS:
    Dump of rotamer info for debugging

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
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

$tmp = tempnam(".", "tmp_rota_");

runRotamer($inFile, $tmp);
$rota = loadRotamer($tmp);
unlink($tmp);
//print_r($rota);
$out = findRotaOutliers($rota);
print_r($out);


//runRamachandran($inFile, $tmp);
//$rama = loadRamachandran($tmp);
//unlink($tmp);
//print_r($rama);
//$out = findRamaOutliers($rama);
//print_r($out);

############################################################################
// Clean up and go home
//mpDestroySession(); // only call this if we created one
?>
