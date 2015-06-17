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
    echo $this->pageHeader("Analyze all-atom contacts and geometry");

    //{{{ Script to set default choices based on model properties.
?><script language='JavaScript'>
<!--
var selectionHasH = true

function hideKinOpts()
{
    var block = document.getElementById('kin_opts')
    if(document.forms[0].doKinemage.checked) block.style.display = 'block'
    else block.style.display = 'none'
}

/*function hideChartOpts()
{
    var block = document.getElementById('chart_opts')
    if(document.forms[0].doCharts.checked) block.style.display = 'block'
    else block.style.display = 'none'
}*/

function setAnalyses(doAAC, hasProtein, hasNucAcid, isBig)
{
    selectionHasH = doAAC

    document.forms[0].kinClashes.checked        = doAAC
    document.forms[0].kinHbonds.checked         = doAAC
    if(!doAAC) // turn these off only
    {
        document.forms[0].kinContacts.checked       = doAAC && !isBig
    }
    //document.forms[0].chartClashlist.checked    = doAAC

    document.forms[0].kinRama.checked           = hasProtein
    document.forms[0].kinRota.checked           = hasProtein
    document.forms[0].kinCBdev.checked          = hasProtein
    //document.forms[0].chartRama.checked         = hasProtein
    //document.forms[0].chartRota.checked         = hasProtein
    //document.forms[0].chartCBdev.checked        = hasProtein

    document.forms[0].kinBaseP.checked          = hasNucAcid
    //document.forms[0].chartBaseP.checked        = hasNucAcid
}

// Try to make sure we have H if we're doing AAC
function checkSettingsBeforeSubmit()
{
    var doAAC = (document.forms[0].kinClashes.checked
        || document.forms[0].kinHbonds.checked
        || document.forms[0].kinContacts.checked
        //|| document.forms[0].chartClashlist.checked
        );

    if(!selectionHasH && doAAC)
    {
        return window.confirm("The file you choose may not have all its H atoms added."
        +" All-atom contacts requires all H atoms to function properly."
        +" Do you want to proceed anyway?")
    }
    else return true; // OK to submit
}
// -->
</script>
<?php
    //}}} Script to set default choices based on model properties.

    if(count($_SESSION['ensembles']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['ensID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];

        echo makeEventForm("onRunAnalysis");
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
<h5 class='nospaceafter'><label><input type='checkbox' name='doKinemage' value='1' checked onclick='hideKinOpts()'> Multi-criterion kinemage</label></h5>
    <div class='indent' id='kin_opts'>
    <label><input type='checkbox' name='kinClashes' value='1'> Clashes</label>
    <br><label><input type='checkbox' name='kinHbonds' value='1'> Hydrogen bonds</label>
    <br><label><input type='checkbox' name='kinContacts' value='1'> van der Waals contacts</label>
    <p><label><input type='checkbox' name='kinRama' value='1'> Ramachandran plots</label>
    <br><label><input type='checkbox' name='kinRota' value='1'> Rotamer evaluation</label>
    <br><label><input type='checkbox' name='kinCBdev' value='1'> C&beta; deviations</label>
    <br><label><input type='checkbox' name='kinBaseP' value='1'> RNA sugar pucker analysis</label>
    <p><label><input type='checkbox' name='kinAltConfs' value='1'> Alternate conformations</label>
    <br><label><input type='checkbox' name='kinBfactor' value='1'> Models colored by B-factors</label>
    <br><label><input type='checkbox' name='kinOccupancy' value='1'> Models colored by occupancy</label>
    <br><label><input type='checkbox' name='kinRibbons' value='1'> Ribbons</label>
    </div>
<h5 class='nospaceafter'><label><input type='checkbox' name='doRamaPDF' value='1' checked> Multi-model Ramachandran plot (PDF)</label></h5>
<!--
<h5 class='nospaceafter'><label><input type='checkbox' name='doMultiGraph' value='1'> Multi-criterion graph [ALPHA TEST]</label></h5>
-->
<h5 class='nospaceafter'><label><input type='checkbox' name='doMultiModelChart' value='1'> Multi-criterion kinemage chart [ALPHA TEST]</label></h5>
</div>

<?php
        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Run programs to perform these analyses &gt;' onclick='return checkSettingsBeforeSubmit()'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
        // Rather than trying to put this in onload(), we'll do it after the form is defined.
        if($jsOnLoad)
            echo "<script language='JavaScript'>\n<!--\n$jsOnLoad\n// -->\n</script>\n";
?>
<hr>
<div class='help_info'>
<h4>Analyze all-atom contacts and geometry</h4>
<i>TODO: Help text about analysis goes here</i>
</div>
<?php
    }
    else
    {
        echo "No ensembles are available. Please <a href='".makeEventURL("onCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
        echo makeEventForm("onReturn");
        echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
    }

    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onRunAnalysis
############################################################################
/**
* Documentation for this function.
*/
function onRunAnalysis()
{
    $req = $_REQUEST;
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

        // The chartXXX vars aren't defined in this interface (yet), but they don't hurt anything...
        if($req['kinClashes'] || $req['kinHbonds'] || $req['kinContacts'] || $req['chartClashlist'])
            mpLog("aacgeom-aac:Generataing all-atom contact data of some type");
        if($req['kinRama'] || $req['chartRama'])    mpLog("aacgeom-rama:Doing Ramachandran analysis");
        if($req['kinRota'] || $req['chartRota'])    mpLog("aacgeom-rota:Doing rotamer analysis");
        if($req['kinCBdev'] || $req['chartCBdev'])  mpLog("aacgeom-cbdev:Doing C-beta deviation analysis");
        if($req['kinBaseP'] || $req['chartBaseP'])  mpLog("aacgeom-basep:Validating base-phosphate distances vs sugar puckers");

        // doMultiGraph hasn't been renamed to doCharts yet...
        if($req['doKinemage'])      mpLog("aacgeom-mkin:Multi-criterion validation kinemage");
        //if($req['doCharts'])        mpLog("aacgeom-mchart:Multi-criterion validation chart");
        if($req['doMultiGraph'])    mpLog("aacgeom-mchart:Multi-criterion validation chart");

        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/ens_aacgeom.php", "generic_done.php", 5);
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
