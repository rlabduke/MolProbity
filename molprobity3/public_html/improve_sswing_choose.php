<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Displays the choices made by SSWING for review by the user.
    
INPUTS (via $_SESSION['bgjob']):
    newModel        the ID of the model just added
    edmap           the map file name
    cnit            a set of CNIT codes for residues that were processed
    sswingChanges   the changes for pdbSwapCoords() produced by SSWING

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
$modelID = $_SESSION['bgjob']['newModel'];
$model = $_SESSION['models'][$modelID];
$cnit = $_SESSION['bgjob']['cnit'];

echo mpPageHeader("Review SSWING changes");
############################################################################
?>

<p>
Please examine the kinemage below to see the effects of changes made by SSWING.
Afterwards, please select which of the changes you would like to accept as-is.
Residues that are not selected will be restored to their original conformation.
</p>

<?php echo "<p>".linkModelKin($model, "sswing.kin")."</p>\n"; ?>

<form method='post' action='improve_sswing_done.php'>
<?php
echo postSessionID();
foreach($cnit as $res)
{
    echo "<br><input type='checkbox' name='cnit[$res]' value='$res' checked> $res\n";
}
?>
<p><center><input type='submit' name='cmd' value='Generate modified PDB file'>
</center>
</form>

<?php echo mpPageFooter(); ?>
