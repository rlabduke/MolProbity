<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is intended as a reference for MolProbity PHP background scripts.

INPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

OUTPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
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

# MAIN - the beginning of execution for this page
############################################################################
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];
$pdbDir    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$pdb = $pdbDir.'/'.$model['pdb'];
$kinDir     = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
$kinURL     = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
    if(!file_exists($kinDir)) mkdir($kinDir, 0777);
$opt        = $_SESSION['bgjob'];
$gap_start    = $opt['gap_start'];
$gap_end      = $opt['gap_end'];
$num_fragments = $opt['num_fragments'];
$tight_params = $opt['tight_params'];
$keep_seq     = $opt['keep_seq'];
$nomatch      = $opt['nomatch'];
$nomatch_size = $opt['nomatch_size'];
//echo $gap_start." ".$gap_end."\n";
//echo preg_match("/[0-9]+/", $gap_start);

if ((preg_match("/[0-9]+/", $gap_start)) && (preg_match("/[0-9]+/", $gap_end))) {
  $fragfiller_args = $gap_start."-".$gap_end;
  //$gapCount += 1;
}
if (preg_match("/[0-9]+/", $num_fragments)) {
  $fragfiller_args = $fragfiller_args." -fragments ".$num_fragments;
} else {
  echo "Non-numeric value entered into fragment number\n";
}
if ($tight_params==1) $fragfiller_args = $fragfiller_args." -tighter";
if ($keep_seq    ==1) $fragfiller_args = $fragfiller_args." -sequence";
if ($nomatch     ==1) $fragfiller_args = $fragfiller_args." -nomatchsize ".$nomatch_size;
//echo $fragfiller_args."\n";

echo $pdb."\n";

// Set up progress message
$tasks['jiffiloop'] = "Fill gaps with <code>java -jar jiffiloop.jar ".$fragfiller_args."</code>";
$tasks['notebook'] = "Add entry to lab notebook";

setProgress($tasks, 'jiffiloop'); // updates the progress display if running as a background job
//$newModel = createModel($modelID."H");
//$outname = $newModel['pdb'];
//if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
$kinpath = $kinDir.'/'.$modelID.'.kin';
$pdbPrefix = $pdbDir.'/'.$modelID;
runJiffiloop($pdb, $pdbPrefix, $fragfiller_args);
//reduceNoBuild($pdb, $outpath);

setProgress($tasks, 'notebook');
//$kinout = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$kinpath;
//$kinurl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$kinpath;
// have to scan MP_DIR_MODELS directory for all outputted loop files.
$gapCount = 0;
$pdbFiles = listDir($pdbDir);
$pdbEntries = "<p>You can now use the following links to download multi-model PDB files for each filled gap.</p>\n";
foreach ($pdbFiles as $pdbName) {
    //echo "For $pdbName\n";
    if (preg_match("/".$modelID.".*\.[0-9]*-[0-9]*\.pdb/", $pdbName)) {
        //echo "Found $pdbName\n";
        $filledPdbFile = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$pdbName;        
        $pdburl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$pdbName;
        $pdbEntries .= "<p>File <a href='$pdburl'>$pdbName</a> (".formatFilesize(filesize($filledPdbFile)).").</p>\n";
        $gapCount += 1;
    } else if (preg_match("/.kin/", $pdbName)) { 
      // because JiffiLoop puts all output files in one directory, this moves the output kin to the kin directory.
      //echo "kin file name: ".$pdbName."\n";
      $origKinFile =  $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$pdbName;
      $newKinFile = $kinDir.'/'.$pdbName;
      rename($origKinFile, $newKinFile);
      $jiffiKin = $pdbName;
    }
}
//$pdbout = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$pdbpath;
//$pdburl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$pdbpath;
$entry = "Jiffiloop was run on $model[pdb]; $gapCount JiffiLoop PDB files were found. If there are no files shown here, JiffiLoop likely crashed; please contact the developers.\n";
//$entry .= "<p>You can now <a href='$pdburl'>download the annotated PDB file</a> (".formatFilesize(filesize($pdb)).").</p>\n";
$entry .= $pdbEntries;
$entry .= "<p>A kinemage of the fragments from this run is ready for viewing in KiNG: ".linkKinemage($jiffiKin)."</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Filled gaps in $modelID.",
    $entry,
    "$modelID|$newModel[id]", // applies to both old and new model
    "auto",
    "add_h.png"
);

setProgress($tasks, null);


############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
