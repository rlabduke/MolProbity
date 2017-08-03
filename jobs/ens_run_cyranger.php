<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the cyrange program to split an ensemble file into core and not-core PDBs.

INPUTS (via $_SESSION['bgjob']):
    ensID           ID code for the ensemble to process

OUTPUTS (via $_SESSION['bgjob']):
    ensID           the ID of the ensemble just added
    labbbookEntry   the labbook entry number describing this action

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
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

# MAIN - the beginning of execution for this page
############################################################################
$oldEnsid = $_SESSION['bgjob']['ensID'];
$oldEns = $_SESSION['ensembles'][$oldEnsID];
$method = $_SESSION['bgjob']['method'];

$tasks['cyrange'] = "Create core and noncore PDB files with <code>CYRANGE</code>";
$tasks['notebook'] = "Add entry to lab notebook";

setProgress($tasks, 'cyrange'); // updates the progress display if running as a background job
//if($method == 'build')
//    $newEnsID = reduceEnsemble($oldEnsID, 'reduceBuild');
//elseif($method == 'nobuild')
//    $newEnsID = reduceEnsemble($oldEnsID, 'reduceNoBuild');
//else
//    $newEnsID = reduceEnsemble($oldEnsID /*, default mode */);
$newEnsembles = runCyrangerEnsemble($oldEnsid);
$coreEnsid = $newEnsembles[0];
$noncoreEnsid = $newEnsembles[1];
print "coreEnsID: ".$coreEnsid."\n";
print "noncoreEnsID: ".$noncoreEnsid."\n";

$coreEns = $_SESSION['ensembles'][$coreEnsid];
$noncoreEns = $_SESSION['ensembles'][$noncoreEnsid];
$_SESSION['lastUsedModelID'] = $coreEnsid; // this is now the current model

setProgress($tasks, 'notebook');
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$coreEns['pdb'];
$url = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$coreEns['pdb'];
$noncorepdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$noncoreEns['pdb'];
$noncoreurl = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$noncoreEns['pdb'];

$cyrange_count = countCyrangeChanges($pdb);

$entry = "Cyrange was run on all models of $oldEns[pdb] to determine the well-ordered residues. Two new ensembles have been created: $coreEns[pdb] and $noncoreEns[pdb].\n";
if($cyrange_count)
{
    $entry .= "$cyrange_count[core] core residues and $cyrange_count[noncore] non-core residues were found per model.\n";
}

$entry .= "<p>You can now download the <a href='$url'>core PDB file</a> (".formatFilesize(filesize($pdb)).") or the <a href='$noncoreurl'>non-core PDB file</a> (".formatFilesize(filesize($noncorepdb)).").</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Ran CYRANGE to get $coreEns[pdb] and $noncoreEns[pdb]",
    $entry,
    "$oldEnsid|$coreEnsid|$noncoreEnsid", // applies to old and new ensembles
    "cyrange",
    "add_h.png"
);

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
