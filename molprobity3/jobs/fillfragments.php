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
echo $gap_start." ".$gap_end."\n";

if ((preg_match("/[0-9]*/", $gap_start)) && (preg_match("/[0-9]*/", $gap_end))) {
  $fragfiller_args = $gap_start."-".$gap_end;
  //$gapCount += 1;
}

echo $pdb."\n";

// Set up progress message
$tasks['jiffiloop'] = "Fill gaps with <code>java -jar jiffiloop.jar</code>";
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
$kinout = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$kinpath;
$kinurl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$kinpath;
// have to scan MP_DIR_MODELS directory for all outputted loop files.
$gapCount = 0;
$pdbFiles = listDir($pdbDir);
$pdbEntries = "<p>You can now use the following links to download PDB files with each filled gap.</p>\n";
foreach ($pdbFiles as $pdbName) {
    //echo "For $pdbName\n";
    if (preg_match("/".$modelID.".*\.[0-9]*-[0-9]*\.pdb/", $pdbName)) {
        //echo "Found $pdbName\n";
        $filledPdbFile = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$pdbName;        
        $pdburl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$pdbName;
        $pdbEntries .= "<p>File <a href='$pdburl'>$pdbName</a> (".formatFilesize(filesize($filledPdbFile)).").</p>\n";
        $gapCount += 1;
    }
}
//$pdbout = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$pdbpath;
//$pdburl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$pdbpath;
$entry = "Jiffiloop was run on $model[pdb] to fill $gapCount backbone gap(s).\n";
//$entry .= "<p>You can now <a href='$pdburl'>download the annotated PDB file</a> (".formatFilesize(filesize($pdb)).").</p>\n";
$entry .= $pdbEntries;
$entry .= "<p>A kinemage of all of the fragments is ready for viewing in KiNG: ".linkKinemage($modelID.'.kin')."</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Added fragments to get $modelID.kin and $modelID.",
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
