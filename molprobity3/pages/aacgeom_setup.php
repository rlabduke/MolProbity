<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to set up all-atom contact and geometric analyses
    It should be accessed by pageCall()
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new AACGeomSetupDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class AACGeomSetupDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID     the model ID to analyze
*/
function display($context)
{
    echo mpPageHeader("All-atom contact and geometric analyses");
    
    // Script to set default choices based on model properties.
?><script language='JavaScript'>
<!--
var userTouchedSettings = false
var selectionHasH       = true

function syncSubcontrols()
{
    // Enable / disable subsettings based on state of contact dots button
    // Notice radio buttons are accessed by name with a numeric index
    document.forms[0].showHbonds.disabled       = !document.forms[0].doContactDots.checked
    document.forms[0].showContacts.disabled     = !document.forms[0].doContactDots.checked
    document.forms[0].dotStyle[0].disabled      = !document.forms[0].doContactDots.checked
    document.forms[0].dotStyle[1].disabled      = !document.forms[0].doContactDots.checked
}

function userTouch()
{
    syncSubcontrols() // enables/disables subsettings
    userTouchedSettings = true
}

function userTouchAAC(obj)
{
    userTouch()
    if(obj.checked && !selectionHasH && !window.confirm("The file you choose does not appear to"+
    " have all its H atoms added. All-atom contacts requires all H atoms to function properly."))
    {
        obj.checked = false
        userTouch()
    }
}

function setAnalyses(doAAC, hasProtein, hasNucAcid, isBig)
{
    selectionHasH = doAAC
    
    if(userTouchedSettings)
    {
        // If user doesn't OK it, we should not do the following.
        // Actually, I don't think we want to do this after all.
    }
    
    document.forms[0].doClashlist.checked       = doAAC
    document.forms[0].doContactDots.checked     = doAAC
    document.forms[0].showContacts.checked      = !isBig
    
    document.forms[0].doRama.checked            = hasProtein
    document.forms[0].doRota.checked            = hasProtein
    document.forms[0].doCbDev.checked           = hasProtein
    
    document.forms[0].doBaseP.checked           = hasNucAcid
    
    syncSubcontrols() // enables/disables subsettings
    userTouchedSettings = false
}
// -->
</script><?php

    if(count($_SESSION['models']) > 0)
    {
        echo makeEventForm("onRunAnalysis");
        echo "<h3>Select a model to work with:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Determine which tasks should be selected by default,
            // and use an ONCLICK handler to set them.
            $doAAC = ($model['isReduced'] ? "true" : "false");
            $stats = $model['stats'];
            $hasProtein = ($stats['sidechains'] > 0 ? "true" : "false");
            $hasNucAcid = ($stats['nucacids'] > 0 ? "true" : "false");
            $pdbSize = filesize($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb']);
            $isBig = ($pdbSize > 1<<20 ? "true" : "false");
            
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($context['modelID'] == $id ? "checked" : "");
            echo "  <td><input type='radio' name='modelID' value='$id' onclick='setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)' $checked></td>\n";
            echo "  <td><b>$model[pdb]</b></td>\n";
            echo "  <td><small>$model[history]</small></td>\n";
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<h3>Choose which analyses to run:</h3>";
?>
<div class='indent'>
<h5>All-atom contact analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doClashlist' value='1' onclick='userTouchAAC(this)'> Clashscore and clash list</label>
    <br><label><input type='checkbox' name='doContactDots' value='1' onclick='userTouchAAC(this)'> Contacts dots</label>
    <div class='indent'>
        <label><input type='radio' name='dotStyle' value='2' onclick='return false'> Protein mode: mc-mc dots separate from sc-anything dots</label>
        <br><label><input type='radio' name='dotStyle' value='3'> RNA mode: mc-mc, mc-sc, and sc-sc dots all separate</label>
        <br><label><input type='checkbox' name='showHbonds' value='1' checked> Include dots for H-bonds</label>
        <br><label><input type='checkbox' name='showContacts' value='1' checked> Include dots for van der Waals contacts</label>
    </div>
    </div>
<h5>Protein geometry analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doRama' value='1' onclick='userTouch()'> Ramachandran plots</label>
    <br><label><input type='checkbox' name='doRota' value='1' onclick='userTouch()'> Rotamer evaluation</label>
    <br><label><input type='checkbox' name='doCbDev' value='1' onclick='userTouch()'> C&beta; deviations</label>
    </div>
<h5>Nucleic acid geometry analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doBaseP' value='1' onclick='userTouch()'> Base-phosphate perpendiculars</label>
    </div>
<h5>Multi-criterion kinemage</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doHalfBond' value='1'> Half-bond coloring</label>
    <br><label><input type='checkbox' name='doAtomBalls' value='1'> CPK-colored atom markers</label>
    </div>
</div>
<?php
        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Run programs to perform these analyses &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
?>
<script language="JavaScript">
<!--
// Rather than trying to put this in onload(), we'll do it after the form is defined.
syncSubcontrols()
// -->
</script>
<hr>
<div class='help_info'>
<h4>All-atom contact and geometric analyses</h4>
<i>TODO: Help text about analysis goes here</i>
</div>
<?php
    }
    else
    {
        echo "No models are available. Please <a href='".makeEventURL("onNavBarCall", "upload_pdb_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
        echo makeEventForm("onReturn");
        echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
        
    }
    
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onReturn
############################################################################
/**
* Documentation for this function.
*/
function onReturn($arg, $req)
{
    pageReturn();
}
#}}}########################################################################

#{{{ onRunAnalysis
############################################################################
/**
* Documentation for this function.
*/
function onRunAnalysis($arg, $req)
{
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }
    
    // Otherwise, moving forward:
    /*if(isset($req['modelID']) && isset($req['map']))
    {
        $ctx['modelID'] = $req['modelID'];
        $ctx['map']     = $req['map'];
        pageGoto("sswing_setup2.php", $ctx);
    }
    else
    {
        $ctx = getContext();
        if(isset($req['modelID']))  $ctx['modelID'] = $req['modelID'];
        if(isset($req['map']))      $ctx['map']     = $req['map'];
        setContext($ctx);
    }*/
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
