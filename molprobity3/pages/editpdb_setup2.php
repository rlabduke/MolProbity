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
    if(is_array($req['removechain']) && count($req['removechain']) > 0)
    {
        $oldID = $req['modelID'];
        $oldModel = $_SESSION['models'][$oldID];
        $inpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$oldModel['pdb'];
        
        $newModel = createModel($oldID."_edit");
        $newID = $newModel['id'];
        $outpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$newModel['pdb'];
    
        removeChains($inpath, $outpath, $req['removechain']);
        $newModel['stats']      = pdbstat($outpath);
        $newModel['history']    = "Edited $oldModel[pdb] to remove chains";
        $_SESSION['models'][$newID] = $newModel;
        $_SESSION['lastUsedModelID'] = $newID; // this is now the current model
        
        $s .= "You created $newModel[pdb] by removing chain(s) ".implode(', ', $req['removechain'])." from $oldModel[pdb].\n";
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
        
        mpLog("editpdb:Removed chains from a PDB file");
        pageGoto("generic_done.php", array('labbookEntry' => $entrynum));
    }
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
