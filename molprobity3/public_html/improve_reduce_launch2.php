<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches Reduce -build in the background.
    
INPUTS (via Get or Post):
    model           ID code for model to process
    doflip[]        an array of booleans, where the keys match the second index
                    in the data structure from decodeReduceUsermods()

OUTPUTS (via $_SESSION['bgjob'])
    model           ID code for model to process
    doflip[]        an array of booleans, where the keys match the second index
                    in the data structure from decodeReduceUsermods()

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
$doflip     = $_REQUEST['doflip'];
$modelID    = $_REQUEST['model'];
$model      = $_SESSION['models'][$modelID];
$pdb        = "$model[dir]/$model[pdb]";


// If all changes were accepted, we will not need to re-run Reduce.
$changes = decodeReduceUsermods($pdb);
$rerun = false;
$n = count($changes[0]); // How many changes are in the table?
for($c = 0; $c < $n; $c++)
{
    // Expect checks for ones flipped originally; expect no check for ones not flipped.
    $expected = ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL");
    if($doflip[$c] != $expected) { $rerun = true; }
}

// User requested changes; re-launch Reduce
if($rerun)
{
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['model']     = $_REQUEST['model'];
    $_SESSION['bgjob']['doflip']    = $_REQUEST['doflip'];
    
    // launch background job
    launchBackground(MP_BASE_DIR."/jobs/reduce-fix.php", "improve_reduce_done.php?$_SESSION[sessTag]", 5);
    
    // include() status monitoring page
    include(MP_BASE_DIR."/public_html/job_progress.php");
    die();
}
// No changes to flip states; skip straight to end
else
{
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['model']     = $_REQUEST['model']; // done page expects this
    include(MP_BASE_DIR."/public_html/improve_reduce_done.php");
    die();
}
############################################################################
?>
