#!/usr/bin/env php
<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Processes a directory full of PDB files non-recursively and outputs
    a list of all the Ramachandran/rotamer/C-beta dev./clash/RNA pperp/
    RNA suite/backbone geometry outliers
    
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
// 6. Unlimited memory for processing large files
    ini_set('memory_limit', -1);

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
    if(!isset($pdbFolder))
        $pdbFolder = $arg;
    else
        die("Too many or unrecognized arguments: '$arg'\n");
}

if(! isset($pdbFolder))
    die("No input directory specified.\n");
elseif(! is_dir($pdbFolder))
    die("Input directory '$pdbFolder' does not exist or is not a directory.\n");
    
// Loop through all PDBs in the provided directory
$h = opendir($pdbFolder);
while(($infile = readdir($h)) !== false)
{
    $infile = "$pdbFolder/$infile";
    if(is_file($infile) && endsWith($infile, ".pdb"))
    {
        $filename = basename($infile);
        $tmp = mpTempfile();
        $tmp2 = mpTempFile();
        
        // Run analysis; load data
        runClashlist($infile, $tmp);
        $clash = loadClashlist($tmp);
        runCbetaDev($infile, $tmp);
        $cbdev = loadCbetaDev($tmp);
        $badCbeta = findCbetaOutliers($cbdev);
        runRotamer($infile, $tmp);
        $rota = loadRotamer($tmp);
        $badRota = findRotaOutliers($rota);
        runRamachandran($infile, $tmp);
        $rama = loadRamachandran($tmp);
        runBasePhosPerp($infile, $tmp);
        $pperp = loadBasePhosPerp($tmp);
        $badPperp = findBasePhosPerpOutliers($pperp);
        runSuitenameReport($infile, $tmp);
        $suites = loadSuitenameReport($tmp);
        $badSuites = findSuitenameOutliers($suites);
        runValidationReport($infile, $tmp, "protein");
        runValidationReport($infile, $tmp2, "rna");
        $bonds = array_merge(loadValidationBondReport($tmp, "protein"), loadValidationBondReport($tmp2, "rna"));
        $angles = array_merge(loadValidationAngleReport($tmp, "protein"), loadValidationAngleReport($tmp2, "rna"));
        unlink($tmp);
        unlink($tmp2);
        
        // Clash scores
        echo "$filename:clashscore:$clash[scoreAll]\n";
        echo "$filename:clashscoreB<40:$clash[scoreBlt40]\n";
        foreach($clash['clashes'] as $res => $overlap)
            echo "$filename:clash:$res:$overlap\n";
        
        // Cbetas
        foreach($badCbeta as $res => $dist)
            echo "$filename:cbdev:$res:$dist\n";
        
        // Rotamers
        foreach($badRota as $res => $score)
            echo "$filename:rotamer:$res:$score\n";
        
        // Rama outliers - count each type
        foreach($rama as $r)
            if($r['eval'] == "OUTLIER") echo "$filename:ramachandran:$r[resName]:$r[type]\n";
        
        // PPerp outliers - indicate possible RNA sugar pucker problem
        foreach($badPperp as $res)
            echo "$filename:pperp:$res\n";
        
        // Suite outliers - indicate possible RNA suite backbone conformation problem.
        foreach($badSuites as $res => $suiteness)
            echo "$filename:suite:$res\n";
            
        // Geometry outliers - only shows the worst one of a residue, if it has > 4sigma outlier
        foreach($bonds as $cnit => $item) {
          //if($item['type'] == 'protein') {
            if($item['isOutlier']) {
              echo "$filename:bond(worst):$item[resName]:$item[measure]\n";
            }
          //}
        }
        foreach($angles as $cnit => $item) {
          //if($item['type'] == 'protein') {
            if($item['isOutlier']) {
              echo "$filename:angle(worst):$item[resName]:$item[measure]\n";
            }
          //}
        }
    }
}
closedir($h);

############################################################################
// Clean up and go home
?>
