<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs Probe with complicated option sets.

INPUTS (via $_SESSION['bgjob']):
    modelID         model to be used for making kins
    a whole bunch of other options, see pages/interface_setup2.php

OUTPUTS (via $_SESSION['bgjob']):
    labbookEntry    the labbook entry number

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!
// 6. Record this PHP script's PID in case it needs to be killed.
    $_SESSION['bgjob']['processID'] = posix_getpid();
    mpSaveSession();

#{{{ getProbeFlags
############################################################################
function getProbeFlags($opt)
{
    // Set up command line flags
    $flags = "";
    
    // Dot removal distance (topological)
    $remove_flags = array("4" => "-4H", "3" => "-3", "2" => "-2", "1" => "-1");
    $flags .= " ".$remove_flags[$opt['remove_dist']];
    
    // Set up coloring scheme
    $color_schemes = array("gap" => "-gap", "atom" => "-atom", "base" => "-colorbase", "gray" => "-outcolor gray");
    $flags .= " ".$color_schemes[$opt['color_by']];
    
    // Check booleans
    if(! $opt['show_clashes'])  $flags .= " -noclashout";
    if(! $opt['show_hbonds'])   $flags .= " -nohbout";
    if(! $opt['show_vdw'])      $flags .= " -novdwout";
    if(  $opt['show_mc'])       $flags .= " -mc";
    if(! $opt['show_hets'])     $flags .= " -nohets";
    if(! $opt['show_wat'])      $flags .= " -nowaters";
    if(  $opt['wat2wat'])       $flags .= " -wat2wat";
    if(  $opt['drop_flag'])     $flags .= " -drop";
    if(  $opt['elem_masters'])  $flags .= " -elem";
    
    // Set mode of action:
    $modes = array("both" => "-both", "once" => "-once", "self" => "-self", "out" => "-out");
    $flags .= " ".$modes[$opt['probe_mode']];
    
    // Set flags common to both patterns:
    $pat_suffix = "";
    if($opt['alta'])    $pat_suffix .= " alta";
    if($opt['blt40'])   $pat_suffix .= " blt40";
    if($opt['ogt33'])   $pat_suffix .= " ogt33";
    
    // Calculate both patterns with respect to chains
    if(count($opt['src_chains']) > 0)   $src_chains = "chain".implode(',chain', $opt['src_chains']);
    else                                $src_chains = "";
    if(count($opt['targ_chains']) > 0)  $targ_chains = "chain".implode(',chain', $opt['targ_chains']);
    else                                $targ_chains = "";
    
    // Calculate both patterns with respect to protein/water/hets
    $allowedGroups = array('protein', 'dna', 'rna', 'water', 'het');
    
    // This version INcludes the various classes -- but some atoms are none of the above.
    //$src_groups = implode(',', array_intersect($allowedGroups, explode(',', implode(',', 
    //    array($opt['src_prot'], $opt['src_nucacid'], $opt['src_waters'], $opt['src_hets'])))));
    //$targ_groups = implode(',', array_intersect($allowedGroups, explode(',', implode(',', 
    //    array($opt['targ_prot'], $opt['targ_nucacid'], $opt['targ_waters'], $opt['targ_hets'])))));
    //$flags .= " '(".$src_chains.") (".$src_groups.") ".$pat_suffix."'";
    //    $flags .= " '(".$targ_chains.") (".$targ_groups.") ".$pat_suffix."'";
    
    // This version EXcludes the classes that are not selected, making it more robust.
    $src_groups = implode('|', array_diff($allowedGroups, explode(',', implode(',', 
        array($opt['src_prot'], $opt['src_nucacid'], $opt['src_waters'], $opt['src_hets'])))));
    $targ_groups = implode('|', array_diff($allowedGroups, explode(',', implode(',', 
        array($opt['targ_prot'], $opt['targ_nucacid'], $opt['targ_waters'], $opt['targ_hets'])))));
    
    // Set source pattern
    $flags .= " '(".$src_chains.") ".($src_groups == "" ? "" : "(not ($src_groups))")." ".$pat_suffix."'";
    
    // Set target pattern
    if($opt['probe_mode'] == "both" || $opt['probe_mode'] == "once")
    {
        $flags .= " '(".$targ_chains.") ".($targ_groups == "" ? "" : "(not ($targ_groups))")." ".$pat_suffix."'";
    }
    
    return $flags;
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

# MAIN - the beginning of execution for this page
############################################################################
$opt        = $_SESSION['bgjob'];
$modelID    = $opt['modelID'];
$model      = $_SESSION['models'][$modelID];
$modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$infile     = "$modelDir/$model[pdb]";

$kinDir     = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    if(!file_exists($kinDir)) mkdir($kinDir, 0777);
$chartDir   = $_SESSION['dataDir'].'/'.MP_DIR_CHARTS;
    if(!file_exists($chartDir)) mkdir($chartDir, 0777);

$kin_suffix = $opt['kin_suffix'];
if(!preg_match('/^[-._A-Za-z0-9]{1,20}$/', $kin_suffix)) $kin_suffix = "interface";
$outname    = $model['prefix'].$kin_suffix.".kin";
$outfile    = "$kinDir/$outname";
$list_out   = "$chartDir/$model[prefix]$kin_suffix.txt";


// Set up commands
$flags = getProbeFlags($opt);
$prekin_cmd1 = "prekin -lots $infile > $outfile";
$probe_cmd1 = "probe -quiet $flags $infile >> $outfile";
$probe_cmd2 = "probe -quiet -u $flags $infile | ProbingPROBE.pl > $list_out";

if($opt['output_type'] == 'both') $doProbe = $doList = true;
elseif($opt['output_type'] == 'kin') $doProbe = true;
elseif($opt['output_type'] == 'list') $doList = true;

if($doProbe)    $tasks['kin'] = "Make kinemage using Prekin";
if($doProbe)    $tasks['dots'] = "Calculate contact dots using Probe";
if($doList)     $tasks['list'] = "List atomic contacts found by Probe";
$tasks['notebook'] = "Make lab notebook entry";


// Actually run things
$entry = "";
if($doProbe)
{
    setProgress($tasks, 'kin'); // updates the progress display if running as a background job
    exec($prekin_cmd1);
    setProgress($tasks, 'dots'); // updates the progress display if running as a background job
    exec($probe_cmd1);
    $entry .= "<p>Your kinemage is ready for viewing in KiNG: ".linkKinemage($outname)."</p>\n";
    $entry .= "<p>The actual commands used were as follows:\n<ul>\n";
    $entry .= "<li><code>prekin -lots $model[pdb] > $outname</code></li>\n";
    $entry .= "<li><code>probe $flags $model[pdb] >> $outname</code></li>\n";
    $entry .= "</ul>\n";
}
if($doList)
{
    setProgress($tasks, 'list'); // updates the progress display if running as a background job
    exec($probe_cmd2);
    $entry .= "<p>The list of contacts is as follows:</p>\n";
    $entry .= "<p><pre>\n";
    $entry .= @file_get_contents($list_out);
    $entry .= "</pre></p>\n";
}


setProgress($tasks, 'notebook'); // updates the progress display if running as a background job
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Visualized interface contacts for $model[pdb]",
    $entry,
    $modelID,
    "auto",
    "barnase_barstar.png"
);

setProgress($tasks, null);
############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
