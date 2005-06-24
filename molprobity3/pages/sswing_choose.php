<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to decide on sidechain refits done by SSwing.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/sswing.php');
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class sswing_choose_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context must contain the following keys:
*   newModel        the ID of the model just added
*   cnit            a set of CNIT codes for residues that were processed
*   sswingChanges   the changes for pdbSwapCoords() produced by SSWING
*/
function display($context)
{
    echo mpPageHeader("Review SSwing changes");
    
    $modelID = $context['newModel'];
    $model = $_SESSION['models'][$modelID];
    $cnit = $context['cnit'];
?><p>
Please examine the kinemage below to see the effects of changes made by SSWING.
Afterwards, please select which of the changes you would like to accept as-is.
Residues that are not selected will be restored to their original conformation.
</p><?php
    echo "<p>".linkKinemage("$model[prefix]sswing.kin")."</p>\n";
    echo makeEventForm("onMakeFinalPDB")."<p>";
    foreach($cnit as $res)
        echo "<br><input type='checkbox' name='cnit[$res]' value='$res' checked> $res\n";
    echo "</p><p><input type='submit' name='cmd' value='Generate modified PDB file &gt;'></p>\n";
    echo "</form>\n";
    
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onMakeFinalPDB
############################################################################
/**
* Documentation for this function.
*/
function onMakeFinalPDB($arg, $req)
{
    $context = getContext();
    $newModel   = $_SESSION['models'][$context['newModel']];
    $oldModel   = $_SESSION['models'][$newModel['parent']];
    $newPDB     = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$newModel['pdb'];
    $oldPDB     = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$oldModel['pdb'];
    $url        = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$newModel['pdb'];
    
    // Put our money where our mouth is and calculate that new PDB file
    $all_changes = $context['sswingChanges'];
    $usercnit = $req['cnit'];
    // Remove changes for residues that weren't selected
    foreach($all_changes as $k => $v)
    {
        $res = substr($k, 0, 9);
        if(!isset($usercnit[$res])) unset($all_changes[$k]);
        else $changed_res[$res] = $res; // used below for the lab notebook
    }
    // Make PDB file
    pdbSwapCoords($oldPDB, $newPDB, $all_changes);

    // Make up the lab notebook entry
    $text = "The following residues were automatically refit by SSWING, creating $newModel[pdb] from $oldModel[pdb]:\n<ul>\n";
    foreach($changed_res as $res) $text .= "<li>$res</li>\n";
    $text .= "</ul>\n";
    $text .= "<p>You can now <a href='$url'>download the optimized and annotated PDB file</a> (".formatFilesize(filesize($newPDB)).").</p>\n";
    $entryNum = addLabbookEntry("SSWING refitting creates $newModel[pdb]", $text, "$oldModel[id]|$newModel[id]", 'auto');

    $ctx = array('labbookEntry' => $entryNum);
    pageGoto("sswing_done.php", $ctx);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
