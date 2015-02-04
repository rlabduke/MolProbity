#!/usr/bin/env php
<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Processes a directory full of PDB files non-recursively and outputs
    a list of all the Ramachanadran scores

 -> We assume all files already have H's added! <-

INPUTS (via $_SERVER['argv']):
    the path to a directory; *.pdb will be processed

OUTPUTS:

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
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
$blength = "ecloud";
$clash_cutoff = -0.4;
if(is_array($_SERVER['argv'])) foreach(array_slice($_SERVER['argv'], 1) as $arg)
{
    $pos = strpos($arg,'=');
    if ($pos !== false) {
        $pieces = explode("=", $arg);
        if($pieces[0] == 'blength')      $blength = $pieces[1];
        if($pieces[0] == 'clash_cutoff') $clash_cutoff = $pieces[1];
    } else {
     $pdbFile = $arg;
    }
}
$bs = array("ecloud","nuclear");
if (! in_array($blength, $bs)) {
    die("blength must be either \"ecloud\" or \"nuclear\"\n");
}
if(! isset($pdbFile))
    die("Must provide at least one PDB file on the command line!\n");

if(is_file($pdbFile) && endsWith($pdbFile, ".pdb"))
{
    mpStartSession(true);
    $id = addModelOrEnsemble(
        $pdbFile,
        basename($pdbFile),
        false,
        true,
        true,
        false);

    $filename = basename($pdbFile);
    $model   =& $_SESSION['models'][$id];
    $pdbfile = $_SESSION['dataDir'].'/'.MP_DIR_MODELS."/$model[pdb]";
    $rawDir  = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
    if(!file_exists($rawDir)) mkdir($rawDir, 0777);
    $outfile = "$rawDir/$model[prefix]rama.data";

    // Run analysis; load data
    runClashscore($pdbfile, $outfile);
    $clsc = loadClashscore($outfile);

    foreach($clsc['lines_all'] as $line)
        echo $line;

    mpDestroySession();
} else {
    die("Must provide a file with a \"pdb\" extension.\n");
}
############################################################################
// Clean up and go home
?>
