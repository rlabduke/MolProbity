<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Runs all the analysis tasks -- CB deviation, clashes, Ramachandran, etc.

INPUTS (via $_SESSION['bgjob']):
    model           ID code for model to process
    opts[doRama]        a flag to create Ramachandran plots
    opts[doRota]        a flag to find bad rotamers
    opts[doCbeta]       a flag to make 2- and 3-D Cbeta deviation plots
    opts[doAAC]         a flag to make all-atom contact kinemages
    opts[doMultiKin]    a flag to make the multi-criterion kinemage
    opts[doMultiChart]  a flag to make the multi-criterion chart
    opts[doAll]         a flag to do all of the above

OUTPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
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
$modelID = $_SESSION['bgjob']['model'];
$model  = $_SESSION['models'][$modelID];
$infile = "$model[dir]/$model[pdb]";
$opts = $_SESSION['bgjob']['opts'];

////////////////////////////////////////////////////////////////////////////
// Check for hydrogens and add them if needed.
if(($opts['doAll'] || $opts['doAAC'] || $opts['doMultiChart'] || $opts['doMultiKin']) && (! $model['isReduced']))
{
    $outfile = $model['id']."nbH.pdb";
    $outpath = "$model[dir]/$outfile";
    reduceNoBuild($infile, $outpath);
    $_SESSION['models'][$modelID]['pdb'] = $outfile;
    $_SESSION['models'][$modelID]['isReduced'] = true;
    
    $modelID = $_SESSION['bgjob']['model'];
    $model  = $_SESSION['models'][$modelID];
    $infile = "$model[dir]/$model[pdb]";
}

////////////////////////////////////////////////////////////////////////////
// Data collection for multi-crit chart, etc.

// C-betas
if($opts['doAll'] || $opts['doCbeta'] || $opts['doMultiChart'])
{
    $outfile = "$model[dir]/$model[prefix]cbdev.data";
    runCbetaDev($infile, $outfile);
    $cbdev = loadCbetaDev($outfile);
}

// Rotamers
if($opts['doAll'] || $opts['doRota'] || $opts['doMultiChart'] || $opts['doMultiKin'])
{
    $outfile = "$model[dir]/$model[prefix]rota.data";
    runRotamer($infile, $outfile);
    $rota = loadRotamer($outfile);
}

// Ramachandran
if($opts['doAll'] || $opts['doRama'] || $opts['doMultiChart'] || $opts['doMultiKin'])
{
    $outfile = "$model[dir]/$model[prefix]rama.data";
    runRamachandran($infile, $outfile);
    $rama = loadRamachandran($outfile);
}

// Clashes
if($opts['doAll'] || $opts['doMultiChart'])
{
    $outfile = "$model[dir]/$model[prefix]clash.data";
    runClashlist($infile, $outfile);
    $clash = loadClashlist($outfile);
}

// Find all residues on the naughty list
// First index is 9-char residue name
// Second index is 'cbdev', 'rota', 'rama', or 'clash'
if($opts['doAll'] || $opts['doMultiChart'])
{
    $worst = findOutliers($cbdev, $rota, $rama, $clash);
    $_SESSION['models'][$modelID]['badRes'] = $worst;
}

////////////////////////////////////////////////////////////////////////////
// Kinemages and other visualizations

// Multi-criterion kinemage
if($opts['doAll'] || $opts['doMultiKin'])
{
    $outfile = "$model[dir]/$model[prefix]multi.kin";
    if(file_exists($outfile)) unlink($outfile);
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@kinemage 1\n@group {macromol.} dominant off\n");
    fclose($h);
    exec("prekin -append -nogroup -scope -show 'mc(white),sc(brown),hy(gray),ht(sky)' $infile >> $outfile");
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {waters} dominant off\n");
    fclose($h);
    exec("prekin -append -nogroup -scope -show 'wa(bluetint)' $infile >> $outfile");
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {Ca trace} dominant\n");
    fclose($h);
    exec("prekin -append -nogroup -scope -show 'ca(gray)' $infile >> $outfile");
    
    makeAltConfKin($infile, $outfile);
    makeBadRamachandranKin($infile, $outfile, $rama);
    makeBadRotamerKin($infile, $outfile, $rota);
    makeBadCbetaBalls($infile, $outfile);
    makeBadDotsVisible($infile, $outfile, true); // if false, don't write hb, vdw
}

// Ramachandran plots
if($opts['doAll'] || $opts['doRama'])
{
    makeRamachandranKin($infile, "$model[dir]/$model[prefix]rama.kin");
    //makeRamachandranImage($infile, "$model[dir]/$model[prefix]rama.jpg");
    //convertKinToPostscript("$model[dir]/$model[prefix]rama.kin");
}

// C-beta deviations
// In the future, we might use a custom lots kin here (e.g. with half-bond colors)
if($opts['doAll'] || $opts['doCbeta'])
{
    $outfile = "$model[dir]/$model[prefix]cb3d.kin";
    exec("prekin -lots $infile > $outfile");
    makeCbetaDevBalls($infile, $outfile);
    makeCbetaDevPlot($infile, "$model[dir]/$model[prefix]cb2d.kin");
}

// All-atom contacts
// We might also want to not calculate H-bonds or VDW dots
// In the future, we might use a custom lots kin here (e.g. with half-bond colors)
if($opts['doAll'] || $opts['doAAC'])
{
    $outfile = "$model[dir]/$model[prefix]aac.kin";
    exec("prekin -lots $infile > $outfile");
    makeSidechainDots($infile, $outfile);
    //$outfile = "$model[dir]/$model[prefix]aac-mc.kin";
    //exec("prekin -lots $infile > $outfile");
    makeMainchainDots($infile, $outfile);
}

/*********************
To compare:

    array_diff( array_keys($worst1), array_keys($worst2) ); // things fixed 1->2
    array_diff( array_keys($worst2), array_keys($worst1) ); // things broken 1->2

to find residues that are bad in one structure but not the other.
A detailed comparison can then be done between residues in:

    array_intersect( array_keys($worst1), array_keys($worst2) ); // things changed but not fixed

**********************
Alternately, you might do

    array_unique( array_merge(...keys...) )
    
and then do a comparison on each of the possible second keys using isset().
This would lend itself nicely to a tabular format...
*********************/
############################################################################
// Clean up and go home
$_SESSION['models'][$modelID]['isAnalyzed'] = true;
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
