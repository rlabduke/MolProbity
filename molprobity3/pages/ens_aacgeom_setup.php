<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to set up all-atom contact and geometric analyses
    for multi-model ensembles.
    It should be accessed by pageCall()
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class ens_aacgeom_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   ensID       the ensemble ID to analyze
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
    var on = document.forms[0].doMultiKin.checked
    document.forms[0].doAAC.disabled        = !on
    document.forms[0].doRama.disabled       = !on
    document.forms[0].doRota.disabled       = !on
    document.forms[0].doCbDev.disabled      = !on
    document.forms[0].doBaseP.disabled      = !on
    document.forms[0].doRibbons.disabled    = !on
    document.forms[0].doBFactor.disabled    = !on
    document.forms[0].doOccupancy.disabled  = !on
    
    on = on & document.forms[0].doAAC.checked
    document.forms[0].showHbonds.disabled   = !on
    document.forms[0].showContacts.disabled = !on
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
    if(!selectionHasH && document.forms[0].doAAC.checked)
    {
        return window.confirm("The file you choose may not have all its H atoms added."
        +" All-atom contacts requires all H atoms to function properly."
        +" Do you want to proceed anyway?")
    }
    else return true; // OK to submit
}
// -->
</script>
<div class='alert'>
<center><h3>ALPHA TEST</h3></center>
Not suitable for use by the general public.
</div>
<?php
    //}}} Script to set default choices based on model properties.

    if(count($_SESSION['ensembles']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['ensID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
        
        $jsOnLoad = "syncSubcontrols()"; // cmd to run on page load -- may be changed below
        echo makeEventForm("onRunAnalysis", null, false, "checkSettingsBeforeSubmit()");
        echo "<h3>Select an ensemble to work with:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['ensembles'] as $id => $ensemble)
        {
            // Determine which tasks should be selected by default,
            // and use an ONCLICK handler to set them.
            // Use the first model of each ensemble as representative.
            $modelID = reset($ensemble['models']);
            $model = $_SESSION['models'][$modelID];
            $stats = $model['stats'];
            $doAAC = ($stats['has_most_H'] || $model['isReduced'] ? "true" : "false");
            $hasProtein = ($stats['sidechains'] > 0 ? "true" : "false");
            $hasNucAcid = ($stats['nucacids'] > 0 ? "true" : "false");
            $pdbSize = filesize($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$ensemble['pdb']);
            $isBig = ($pdbSize > 1<<20 ? "true" : "false");
            
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($lastUsedID == $id ? "checked" : "");
            echo "  <td><input type='radio' name='ensID' value='$id' onclick='setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)' $checked></td>\n";
            echo "  <td><b>$ensemble[pdb]</b></td>\n";
            echo "  <td><small>$ensemble[history]</small></td>\n";
            echo " </tr>\n";
            if($checked) $jsOnLoad = "setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)";
        }
        echo "</table></p>\n";
?>
<hr>
<h3>Choose which analyses to run:</h3>
<div class='indent'>
<h5 class='nospaceafter'><label><input type='checkbox' name='doMultiKin' value='1' checked onclick='syncSubcontrols()'> Multi-criterion kinemage</label></h5>
    <div class='indent'>
    <label><input type='checkbox' name='doAAC' value='1' onclick='syncSubcontrols()'> All-atom contact dots</label>
    <div class='indent'>
        <label><input type='checkbox' name='showHbonds' value='1' checked> Show H-bonds in kinemage output</label>
        <br><label><input type='checkbox' name='showContacts' value='1' checked> Show van der Waals contacts in kinemage output</label>
    </div>
    <label><input type='checkbox' name='doRama' value='1'> Ramachandran plots</label>
    <br><label><input type='checkbox' name='doRota' value='1'> Rotamer evaluation</label>
    <br><label><input type='checkbox' name='doCbDev' value='1'> C&beta; deviations</label>
    <br><label><input type='checkbox' name='doBaseP' value='1'> Base-phosphate perpendiculars</label>
    
    <p><label><input type='checkbox' name='doRibbons' value='1'> Ribbons colored by B-factor</label>
    <br><label><input type='checkbox' name='doBFactor' value='1'> Sticks colored by B-factor</label>
    <br><label><input type='checkbox' name='doOccupancy' value='1'> Sticks colored by occupancy</label>
    </div>
<h5 class='nospaceafter'><label><input type='checkbox' name='doMultiGraph' value='1' checked> Multi-criterion graph</label></h5>
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
        echo "No ensembles are available. Please <a href='".makeEventURL("onNavBarCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
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
    if(isset($req['ensID']))
    {
        $_SESSION['lastUsedModelID'] = $req['ensID']; // this is now the current "model"
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob'] = $req;
        
        mpLog("aacgeom:Running all-atom contact and geometric analyses");
        if($req['doAAC'])   mpLog("aacgeom-aac:Generataing all-atom contact data of some type");
        if($req['doRama'])  mpLog("aacgeom-rama:Doing Ramachandran analysis");
        if($req['doRota'])  mpLog("aacgeom-rota:Doing rotamer analysis");
        if($req['doCbDev']) mpLog("aacgeom-cbdev:Doing C-beta deviation analysis");
        if($req['doBaseP']) mpLog("aacgeom-basep:Validating base-phosphate distances vs sugar puckers");
        
        //if($req['doSummaryStats'])  mpLog("aacgeom-sumary:AAC/geometry validation summary");
        if($req['doMultiKin'])      mpLog("aacgeom-mkin:Multi-criterion validation kinemage");
        if($req['doMultiGraph'])    mpLog("aacgeom-mgraph:Multi-criterion validation graph kinemage");
        //if($req['doRemark42'])      mpLog("aacgeom-remark42:Generating REMARK 42 for PDB file");
        
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/ens_aacgeom.php", "aacgeom_done.php", 5);
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
