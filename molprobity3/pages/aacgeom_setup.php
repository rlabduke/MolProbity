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
    
    //{{{ Script to set default choices based on model properties.
?><script language='JavaScript'>
<!--
var selectionHasH = true

function syncSubcontrols()
{
    // Enable / disable subsettings based on state of contact dots button
    // Notice radio buttons are accessed by name with a numeric index
    var willMakeDots = document.forms[0].doAAC.checked && document.forms[0].doMultiKin.checked
    document.forms[0].showHbonds.disabled       = !willMakeDots
    document.forms[0].showContacts.disabled     = !willMakeDots
    document.forms[0].multiKinExtras.disabled   = !document.forms[0].doMultiKin.checked
    document.forms[0].multiChartSort.disabled   = !document.forms[0].doMultiChart.checked
}

function setAnalyses(doAAC, hasProtein, hasNucAcid, isBig)
{
    selectionHasH = doAAC
    
    document.forms[0].doAAC.checked             = doAAC
    document.forms[0].showContacts.checked      = !isBig
    
    document.forms[0].doRama.checked            = hasProtein
    document.forms[0].doRota.checked            = hasProtein
    document.forms[0].doCbDev.checked           = hasProtein
    
    document.forms[0].doBaseP.checked           = hasNucAcid
    
    syncSubcontrols() // enables/disables subsettings
}

// Try to make sure we have H if we're doing AAC
function checkSettingsBeforeSubmit()
{
    if(!  (document.forms[0].doSummaryStats.checked
        || document.forms[0].doMultiKin.checked
        || document.forms[0].doMultiChart.checked))
    {
        window.alert("Please choose at least one form of output.");
        return false; // don't submit
    }
    
    if(!selectionHasH && document.forms[0].doAAC.checked)
    {
        return window.confirm("The file you choose may not have all its H atoms added."
        +" All-atom contacts requires all H atoms to function properly."
        +" Do you want to proceed anyway?")
    }
    else return true; // OK to submit
}
// -->
</script><?php
    //}}} Script to set default choices based on model properties.

    if(count($_SESSION['models']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['modelID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
        
        $jsOnLoad = "syncSubcontrols()"; // cmd to run on page load -- may be changed below
        echo makeEventForm("onRunAnalysis", null, false, "checkSettingsBeforeSubmit()");
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
            $checked = ($lastUsedID == $id ? "checked" : "");
            echo "  <td><input type='radio' name='modelID' value='$id' onclick='setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)' $checked></td>\n";
            echo "  <td><b>$model[pdb]</b></td>\n";
            echo "  <td><small>$model[history]</small></td>\n";
            echo " </tr>\n";
            if($checked) $jsOnLoad = "setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)";
        }
        echo "</table></p>\n";
?>
<hr>
<h3>Choose which analyses to run:</h3>
<div class='indent'>
<h5>All-atom contact analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doAAC' value='1' onclick='syncSubcontrols()'> Clashscore, clash list, and/or contact dots (depending on output formats)</label>
    <div class='indent'>
        <label><input type='checkbox' name='showHbonds' value='1' checked> Show H-bonds in kinemage output</label>
        <br><label><input type='checkbox' name='showContacts' value='1' checked> Show van der Waals contacts in kinemage output</label>
    </div>
    </div>
<h5>Protein geometry analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doRama' value='1'> Ramachandran plots</label>
    <br><label><input type='checkbox' name='doRota' value='1'> Rotamer evaluation</label>
    <br><label><input type='checkbox' name='doCbDev' value='1'> C&beta; deviations</label>
    </div>
<h5>Nucleic acid geometry analysis</h5>
    <div class='indent'>
    <label><input type='checkbox' name='doBaseP' value='1'> Base-phosphate perpendiculars</label>
    </div>
</div>

<hr>
<h3>Choose output formats for requested analysis:</h3>
<div class='indent'>
    <label><input type='checkbox' name='doSummaryStats' value='1' checked> Summary statistics</label>
    <br><label><input type='checkbox' name='doMultiKin' value='1' checked onclick='syncSubcontrols()'> Visual/3-D: multi-criterion kinemage</label>
    <div class='indent'>
        <label><input type='checkbox' name='multiKinExtras' value='1' checked> Include rainbow ribbons, B-factor and occupancy colors, and alternate conformation markers.</label>
    </div>
    <label><input type='checkbox' name='doMultiChart' value='1' checked onclick='syncSubcontrols()'> Tabular: multi-criterion chart</label>
    <div class='indent'>
        <label>Sort by
        <select name='multiChartSort'>
            <option value='natural'>(natural order)</option>
            <option value='bad'>outliers first</option>
            <option value='clash'>worst clashes</option>
            <option value='rota'>worst rotamers</option>
            <option value='cbdev'>worst C&beta; deviations</option>
            <option value='rama'>Ramachandran outliers</option>
        </select></label>
    </div>
</div>
<?php
        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Run programs to perform these analyses &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
        // Rather than trying to put this in onload(), we'll do it after the form is defined.
        echo "<script language='JavaScript'>\n<!--\n$jsOnLoad\n// -->\n</script>\n";
?>
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
    if(isset($req['modelID']))
    {
        $_SESSION['lastUsedModelID'] = $req['modelID']; // this is now the current model
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob'] = $req;
        
        mpLog("aacgeom:Running all-atom contact and geometric analyses");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/aacgeom.php", "aacgeom_done.php", 5);
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
