<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to choose a model to edit (e.g. remove chains)
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');
require_once(MP_BASE_DIR.'/lib/pdbstat.php');
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class editpdb_setup2_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* $context['modelID'] - the ID of the model the user will work with
*/
function display($context)
{
    echo mpPageHeader("Edit PDB file");
    
    $modelID = $context['modelID'];
    echo makeEventForm("onEditPDB");
    echo "<input type='hidden' name='modelID' value='$modelID'>\n";
    echo "<h3>Select editing operations to perform:</h3>";
    echo "<div class='indent'>\n";
    
    echo "<h5 class='nospaceafter'>Set parameters:</h5>";
    echo "<div class='indent'>\n";
    echo "<table border='0'>\n";
    echo "<tr><td>Resolution:</td><td><input type='text' name='resolution' value='".($_SESSION['models'][$modelID]['stats']['resolution']+0)."' size='4'> &Aring;</td></tr>\n";
    echo "</table>\n";
    echo "</div>\n"; // end indent
    
    echo "<h5 class='nospaceafter'>Remove unwanted chains:</h5>";
    echo "<div class='indent'>\n";
    foreach($_SESSION['models'][$modelID]['stats']['chainids'] as $chainID)
    {
        echo "<input type='checkbox' name='removechain[]' value='$chainID'> Remove chain $chainID<br>\n";
    }
    echo "</div>\n"; // end indent
    
    echo "</div>\n"; // end indent
    echo "<p><table width='100%' border='0'><tr>\n";
    echo "<td><input type='submit' name='cmd' value='Edit PDB file &gt;'></td>\n";
    echo "<td align='right'><input type='submit' name='cmd' value='Go back'></td>\n";
    echo "</tr></table></p></form>\n";
?>
<hr>
<div class='help_info'>
<h4>Editing PDB files</h4>
<i>TODO: Help text about editing goes here</i>
</div>
<?php

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onEditPDB
############################################################################
function onEditPDB($arg, $req)
{
    //if($req['cmd'] == 'Cancel')
    if($req['cmd'] == 'Go back')
    {
        pageGoto("editpdb_setup1.php");
        return;
    }
    
    // Otherwise, moving forward:
    $oldID = $req['modelID'];
    $oldModel = $_SESSION['models'][$oldID];
    $inpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$oldModel['pdb'];
    
    $newModel = createModel($oldID."_edit");
    $newID = $newModel['id'];
    $outpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$newModel['pdb'];

    $s = "";
    if(is_array($req['removechain']) && count($req['removechain']) > 0)
    {
        removeChains($inpath, $outpath, $req['removechain']);
        $s .= "<p>You created $newModel[pdb] by removing chain(s) ".implode(', ', $req['removechain'])." from $oldModel[pdb].\n";
        mpLog("editpdb:Removed chains from a PDB file");
    }
    else copy($inpath, $outpath);
    
    $resolu = $req['resolution']+0;
    $oldRes = $oldModel['stats']['resolution']+0;
    if($resolu && ($oldRes == 0 || $oldRes != $resolu) )
    {
        $remark2 = sprintf("REMARK   2                                                                      \nREMARK   2 RESOLUTION. %.2f ANGSTROMS.                                          \n", $resolu);
        replacePdbRemark($outpath, $remark2, 2);
        $s .= "<p>You manually set the resolution for $newModel[pdb].\n";
        mpLog("editpdb:Changed/set resolution for a PDB file");
    }

    $newModel['stats']      = pdbstat($outpath);
    $newModel['history']    = "Edited $oldModel[pdb]";
    $_SESSION['models'][$newID] = $newModel;
    $_SESSION['lastUsedModelID'] = $newID; // this is now the current model
    
    $details = describePdbStats($newModel['stats'], true);
    $s .= "<ul>\n";
    foreach($details as $detail) $s .= "<li>$detail</li>\n";
    $s .= "</ul>\n";
    
    $entrynum = addLabbookEntry(
        "Created PDB file $newModel[pdb]",
        $s,
        "$oldID|$newID",
        "auto",
        "scissors.png"
    );
    
    pageGoto("generic_done.php", array('labbookEntry' => $entrynum));
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
