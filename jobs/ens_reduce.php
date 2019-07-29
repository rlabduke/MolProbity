<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the Reduce to add missing H without doing a full -build.

INPUTS (via $_SESSION['bgjob']):
    ensID           ID code for the ensemble to process
    method          either 'build' or 'nobuild'

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
$oldEnsID = $_SESSION['bgjob']['ensID'];
$oldEns = $_SESSION['ensembles'][$oldEnsID];
$method = $_SESSION['bgjob']['method'];

$reduce_blength = $_SESSION['bgjob']['reduce_blength'];
$_SESSION['reduce_blength'] = $reduce_blength;

$flags = '';

$reduce_method = $method;

// Set up progress message
if($reduce_blength == 'nuclear')
{
  $reduce_method = "$method -nuclear";
}
$tasks['reduce'] = "Add H with <code>reduce -$reduce_method</code>";
$tasks['notebook'] = "Add entry to lab notebook";

setProgress($tasks, 'reduce'); // updates the progress display if running as a background job
if($method == 'build')
    $newEnsID = reduceEnsemble($oldEnsID, 'reduceBuild');
elseif($method == 'nobuild')
    $newEnsID = reduceEnsemble($oldEnsID, 'reduceNoBuild');
else
    $newEnsID = reduceEnsemble($oldEnsID /*, default mode */);
$newEns = $_SESSION['ensembles'][$newEnsID];
$_SESSION['lastUsedModelID'] = $newEnsID; // this is now the current model

setProgress($tasks, 'notebook');
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$newEns['pdb'];
$url = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$newEns['pdb'];
// This should find the line for the first model, at least.
// Some USER MOD records may be munged by the model-joining process.
$hcount = countReduceChanges($pdb);

$entry = "Reduce was run on all models of $oldEns[pdb] to add and optimize missing hydrogens, resulting in $newEns[pdb].\n";
if($hcount)
{
    $entry .= "$hcount[found] hydrogens were found in the original model, and $hcount[add] hydrogens were added.\n";
    if($hcount['std']) $entry .= "$hcount[std] H were repositioned to standardize bond lengths.\n";
    if($hcount['adj']) $entry .= "The positions of $hcount[adj] hydrogens were adjusted to optimize H-bonding.\n";
}
$entry .= "Asn/Gln/His flips were ".($method == 'build' ? "" : "not")." optimized.\n";
if($reduce_blength == 'ecloud') $flags = 'electron-cloud';
else $flags = 'nuclear';
$entry .= "<p>Reduce placed hydrogens at $flags positions.\n";
$entry .= "<p>You can now <a href='$url'>download the annotated PDB file</a> (".formatFilesize(filesize($pdb)).").</p>\n";
$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Added H with -$method to get $newEns[pdb]",
    $entry,
    "$oldEnsID|$newEnsID", // applies to both old and new ensemble
    "auto",
    "add_h.png"
);

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
mpSaveSession();
?>
