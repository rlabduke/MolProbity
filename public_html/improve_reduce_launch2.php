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
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
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
// We're going to construct a lab notebook entry at the same time.
$changes = decodeReduceUsermods($pdb);
$rerun = false;
$n = count($changes[0]); // How many changes are in the table?
$autoflip = "<p>The following residues were flipped automatically by Reduce:\n<ul>\n";
$userflip = "<p>The following residues were flipped manually by the user:\n<ul>\n";
$userkeep = "<p>The following residues were NOT flipped, though Reduce recommended doing so:\n<ul>\n";
for($c = 0; $c < $n; $c++)
{
    // Expect checks for ones flipped originally; expect no check for ones not flipped.
    $expected = ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL");
    //if($doflip[$c] != $expected) { $rerun = true; }
    if($expected)
    {
        if($doflip[$c])
        {
            $autoflip .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
        }
        else
        {
            $userkeep .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
            $rerun = true;
        }
    }
    elseif($doflip[$c])
    {
        $userflip .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
    }
}
$autoflip .= "</ul>\n</p>\n";
$userflip .= "</ul>\n</p>\n";
$userkeep .= "</ul>\n</p>\n";

$entry = "Reduce was run on $model[parent] to add and optimize hydrogens, and optimize Asn, Gln, and His flips, yielding $modelID.\n";
if(strpos($autoflip, "<li>") !== false) $entry .= $autoflip;
if(strpos($userflip, "<li>") !== false) $entry .= $userflip;
if(strpos($userkeep, "<li>") !== false) $entry .= $userkeep;

// Go ahead and make the notebook entry inline -- this can't take more than 1-2 sec.
if($rerun)  $title = "Reduce -build with user overrides gives $modelID";
else        $title = "Reduce -build with default settings gives $modelID";
$_SESSION['models'][$modelID]['entry_reduce'] = addLabbookEntry(
    $title,
    $entry,
    $modelID,
    "auto"
);

// User requested changes; re-launch Reduce
if($rerun)
{
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['model']     = $_REQUEST['model'];
    $_SESSION['bgjob']['doflip']    = $_REQUEST['doflip'];
    
    mpLog("reduce-custom:User made changes to flips suggested by Reduce -build");
    
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
    mpLog("reduce-accept:User accepted all flips proposed by Reduce -build as-is");
    include(MP_BASE_DIR."/public_html/improve_reduce_done.php");
    die();
}
############################################################################
?>
