<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Gives the user a chance to download their modified PDB file.
    
INPUTS (via $_SESSION['bgjob']):
    newModel        the ID of the model just added
    edmap           the map file name
    cnit            a set of CNIT codes for residues that were processed
    sswingChanges   the changes for pdbSwapCoords() produced by SSWING

INPUTS (via Post):
    cnit            an array of the residues the user actually wants changed
    
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/sswing.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Refit sidechains with SSWING", "improve");

$modelID = $_SESSION['bgjob']['newModel'];
$model =& $_SESSION['models'][$modelID];

// PDB file name hasn't been updated yet. Time to do that:
$oldPDB = "$model[dir]/$model[pdb]";
// Model name is different that parent PDB, so we know this file does not exist yet
$model['pdb'] = "$model[id].pdb"; // also updated in SESSION b/c this is a reference
$newPDB = "$model[dir]/$model[pdb]";
$url = "$model[url]/$model[pdb]";

// Put our money where our mouth is and calculate that new PDB file
$all_changes = $_SESSION['bgjob']['sswingChanges'];
$usercnit = $_REQUEST['cnit'];
// Remove changes for residues that weren't selected
foreach($all_changes as $k => $v)
{
    $res = substr($k, 0, 9);
    if(!isset($usercnit[$res])) unset($all_changes[$k]);
}
// Make PDB file
pdbSwapCoords($oldPDB, $newPDB, $all_changes);

############################################################################
?>

<p>Your old model, <b><?php echo $model['parent']; ?></b>, has been converted into a new model, <b><?php echo $modelID; ?></b>.
<?php echo count($usercnit); ?> sidechains have been automatically refit by SSWING.
<?php
echo "You can now ";
echo "<a href='$url'>download the optimized file</a> (".formatFilesize(filesize($newPDB)).").";
?>
</p>

<p>Now that sidechain positions have been optimized, you may want to
<?php echo "<a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID'>"; ?>run analysis on this model</a>
to see how it has improved.
</p>

<p><a href='improve_tab.php?<?php echo $_SESSION['sessTag']; ?>'>Return to "Improve Models" page</a>
</p>
<?php echo mpPageFooter(); ?>
