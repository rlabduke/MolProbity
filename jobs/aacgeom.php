<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Runs all the all-atom contact and geometric analysis
    tasks -- CB deviation, clashes, Ramachandran, etc.

INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    (for other options, see lib/analyze.php::runAnalysis())

OUTPUTS (via $_SESSION['bgjob']):

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

# MAIN - the beginning of execution for this page
############################################################################
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];

$labbookEntry = runAnalysis($modelID, $_SESSION['bgjob']);

$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "All-atom contact and geometric analyses: $model[pdb]",
    $labbookEntry,
    $modelID,
    "auto"
);

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
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
