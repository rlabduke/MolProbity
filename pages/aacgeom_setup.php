<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to set up all-atom contact and geometric analyses
    It should be accessed by pageCall()
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class aacgeom_setup_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID     the model ID to analyze
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

function hideChartOpts()
{
    var block = document.getElementById('chart_opts')
    if(document.forms[0].doCharts.checked) block.style.display = 'block'
    else block.style.display = 'none'
}

function hideMultiOpts()
{
  var block = document.getElementById('multi_opts')
  if(document.forms[0].chartMulti.checked) block.style.display = 'block'
  else block.style.display = 'none'
}

function hideOmegaOpts()
{
  var block = document.getElementById('omega_opts')
  if(document.forms[0].chartOmega.checked) block.style.display = 'block'
  else block.style.display = 'none'
}

//function hideLowResOpts()
//{
//  var block = document.getElementById('lowres_opts')
//  if(document.forms[0].doLowRes.checked) block.style.display = 'block'
//  else block.style.display = 'none'
//}

function setAnalyses(doAAC, hasProtein, hasNucAcid, isBig, isLowRes)
{
    selectionHasH = doAAC

    document.forms[0].kinClashes.checked        = doAAC
    document.forms[0].kinHbonds.checked         = doAAC
    document.forms[0].kinContacts.checked       = doAAC && !isBig
    document.forms[0].chartClashlist.checked    = doAAC

    document.forms[0].kinRama.checked           = hasProtein
    document.forms[0].kinRota.checked           = hasProtein
    document.forms[0].kinCBdev.checked          = hasProtein
    document.forms[0].kinGeom.checked           = (hasProtein || hasNucAcid)
    document.forms[0].kinOmega.checked          = hasProtein
    document.forms[0].kinCablamLow.checked      = (hasProtein && isLowRes)
  
    document.forms[0].chartRama.checked         = hasProtein
    document.forms[0].chartRota.checked         = hasProtein
    document.forms[0].chartCBdev.checked        = hasProtein
    document.forms[0].chartGeom.checked         = (hasProtein || hasNucAcid)
    document.forms[0].chartOmega.checked        = hasProtein
    document.forms[0].chartCablamLow.checked    = (hasProtein && isLowRes)

    document.forms[0].kinBaseP.checked          = hasNucAcid
    document.forms[0].kinSuite.checked          = hasNucAcid //This is now kinemage markup
    document.forms[0].kinSuiteHighD.checked     = false //This is a specialist visualization
    document.forms[0].chartBaseP.checked        = hasNucAcid
    document.forms[0].chartSuite.checked        = hasNucAcid

    //document.forms[0].chartCoot.checked         = !isBig
    document.forms[0].chartImprove.checked      = (hasProtein && doAAC)
    document.forms[0].chartMulti.checked        = (hasProtein || hasNucAcid)
    document.forms[0].chartNotJustOut.checked   = !isBig
    document.forms[0].chartAltloc.checked       = (hasProtein || hasNucAcid)

    //Low-resolution analyses, all end with Low
    //document.forms[0].doLowRes.checked          = (hasProtein && isLowRes)
    
// Low-res kinemage options are expected to expand in the future
//    document.forms[0].kinClashesLow.checked     = (doAAC && isLowRes)
//    document.forms[0].kinGeomLow.checked        = ((hasProtein || hasNucAcid) && isLowRes)

    
}

// Try to make sure we have H if we're doing AAC
function checkSettingsBeforeSubmit()
{
    var doAAC = (document.forms[0].kinClashes.checked
        || document.forms[0].kinHbonds.checked
        || document.forms[0].kinContacts.checked
        || document.forms[0].chartClashlist.checked);

    if(!selectionHasH && doAAC)
    {
        return window.confirm("The chosen file may be missing significant numbers of H atoms.\n"
        +"All-atom contacts may add missing H atoms to allow complete validation.\n"
        +"Cancel and add H atoms, or OK to proceed?")
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

        echo makeEventForm("onRunAnalysis");
        echo "<h3>Select a model to work with:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Determine which tasks should be selected by default,
            // and use an ONCLICK handler to set them.
            //$doAAC = ($model['isReduced'] ? "true" : "false");
            $stats = $model['stats'];
            $doAAC = ($stats['has_most_H'] || $model['isReduced'] ? "true" : "false");
            $hasProtein = ($stats['sidechains'] > 0 ? "true" : "false");
            $hasNucAcid = ($stats['nucacids'] > 0 ? "true" : "false");
            $pdbSize = filesize($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb']);
            $isBig = ($pdbSize > 1<<21 ? "true" : "false"); // 1<<20 = 2^20
            //$isLowRes = ($stats['resolution'] > 2.5 ? "true" : "false");
            $isLowRes = ($stats['resolution'] ? ($stats['resolution'] > 2.5 ? "true" : "false") : "true");

            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($lastUsedID == $id ? "checked" : "");
            echo "  <td><input type='radio' name='modelID' value='$id' onclick='setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig)' $checked></td>\n";
            echo "  <td><b>$model[pdb]</b></td>\n";
            echo "  <td><small>$model[history]</small></td>\n";
            echo " </tr>\n";
            if($checked) $jsOnLoad = "setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig, $isLowRes)";
        }
        echo "</table></p>\n";
?>
<hr>
<h3 class='nospaceafter'>Choose the outputs you want:</h3>
Default options have been selected based on the content of the submitted file.
<br>Follow the <a target="_blank" href="help/validation_options/validation_options.html"> <img src="img/helplink.jpg" alt="" title="General help"></a> symbols for more information on the validation options.
<div class='indent'>
<h5 class='nospaceafter'><label><input type='checkbox' name='doKinemage' value='1' checked onclick='hideKinOpts()'> 3-D kinemage graphics</label></h5>
    <div class='indent' id='kin_opts'>
    <label><b>Universal</b></label>
    <br><label><input type='checkbox' name='kinClashes' value='1'> Clashes</label> <a target="_blank" href="help/validation_options/validation_options.html#clashes"> <img src="img/helplink.jpg" alt="" title="Clash help"></a>
    <br><label><input type='checkbox' name='kinHbonds' value='1'> Hydrogen bonds</label> <a target="_blank" href="help/validation_options/validation_options.html#hbonds"><img src="img/helplink.jpg" alt="" title="H-bond help"></a>
    <br><label><input type='checkbox' name='kinContacts' value='1'> van der Waals contacts</label> <a target="_blank" href="help/validation_options/validation_options.html#vdwcontacts"><img src="img/helplink.jpg" alt="" title="vdw contact help"></a>
    <br><label><input type='checkbox' name='kinGeom' value='1'> Geometry evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#bondgeometry"><img src="img/helplink.jpg" alt="" title="Geometry help"></a>
    <p><label><b>Protein</b></label>
    <br><label><input type='checkbox' name='kinRama' value='1'> Ramachandran plots</label> <a target="_blank" href="help/validation_options/validation_options.html#ramachandran"><img src="img/helplink.jpg" alt="" title="Ramachandran help"></a>
    <br><label><input type='checkbox' name='kinRota' value='1'> Rotamer evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#rotamers"><img src="img/helplink.jpg" alt="" title="Rotamer help"></a>
    <br><label><input type='checkbox' name='kinCBdev' value='1'> C&beta; deviations</label> <a target="_blank" href="help/validation_options/validation_options.html#cbdev"><img src="img/helplink.jpg" alt="" title="CB deviation help"></a>
    <br><label><input type='checkbox' name='kinOmega' value='1'> Cis-Peptide evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#cispeptides"><img src="img/helplink.jpg" alt="" title="Cis-peptide help"></a>
    <br><label><input type='checkbox' name='kinCablamLow' value='1'> CaBLAM backbone markup</label> <a target="_blank" href="help/validation_options/validation_options.html#cablam"><img src="img/helplink.jpg" alt="" title="CaBLAM help"></a>
    <p><label><b>RNA</b></label>
    <br><label><input type='checkbox' name='kinBaseP' value='1'> RNA sugar pucker analysis</label> <a target="_blank" href="help/validation_options/validation_options.html#sugarpuckers"><img src="img/helplink.jpg" alt="" title="Sugar pucker help"></a>
    <br><label><input type='checkbox' name='kinSuite' value='1'> RNA backbone conformations</label> <a target="_blank" href="help/validation_options/validation_options.html#suites"><img src="img/helplink.jpg" alt="" title="Suite help"></a>
    <br><label><input type='checkbox' name='kinSuiteHighD' value='1'> RNA backbone conformations high dimension kinemage</label> <a target="_blank" href="help/validation_options/validation_options.html#suites"><img src="img/helplink.jpg" alt="" title="Suite help"></a>
    <p><label><b>Other options</b></label>
    <br><label><input type='checkbox' name='kinForceViews' value='1'> Make views of trouble spots even if it takes longer</label>
    <br><label><input type='checkbox' name='kinAltConfs' value='1'> Alternate conformations</label>
    <br><label><input type='checkbox' name='kinBfactor' value='1'> Model colored by B-factors</label>
    <br><label><input type='checkbox' name='kinOccupancy' value='1'> Model colored by occupancy</label>
    <br><label><input type='checkbox' name='kinRibbons' value='1'> Ribbons</label>
    </div>
<h5 class='nospaceafter'><label><input type='checkbox' name='doCharts' value='1' checked onclick='hideChartOpts()'> Charts, plots, and tables</label></h5>
    <div class='indent' id='chart_opts'>
    <label><b>Universal</b></label>
    <br><label><input type='checkbox' name='chartClashlist' value='1'> Clashes &amp; clashscore</label> <a target="_blank" href="help/validation_options/validation_options.html#clashes"> <img src="img/helplink.jpg" alt="" title="Clash help"></a>
    <br><label><input type='checkbox' name='chartGeom' value='1'> Geometry evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#bondgeometry"> <img src="img/helplink.jpg" alt="" title="Geometry help"></a>
    <p><label><b>Protein</b></label>
    <br><label><input type='checkbox' name='chartRama' value='1'> Ramachandran plots</label> <a target="_blank" href="help/validation_options/validation_options.html#ramachandran"> <img src="img/helplink.jpg" alt="" title="Ramachandran help"></a>
    <br><label><input type='checkbox' name='chartRota' value='1'> Rotamer evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#rotamers"> <img src="img/helplink.jpg" alt="" title="Rotamer help"></a>
    <br><label><input type='checkbox' name='chartCBdev' value='1'> C&beta; deviations</label> <a target="_blank" href="help/validation_options/validation_options.html#cbdev"> <img src="img/helplink.jpg" alt="" title="CB deviation help"></a>
    <br><label><input type='checkbox' name='chartOmega' value='1' checked onclick='hideOmegaOpts()'> Cis-Peptide evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#cispeptides"> <img src="img/helplink.jpg" alt="" title="Cis-peptide help"></a>
    <div class='closeindent' id='omega_opts'><label><input type='checkbox' name='chartOmegaForceStats' value='1'> Show cis-nonPro and twisted peptide statistics even if the model has none</label></div>
    <label><input type='checkbox' name='chartCablamLow' value='1'> CaBLAM backbone evaluation</label> <a target="_blank" href="help/validation_options/validation_options.html#cablam"> <img src="img/helplink.jpg" alt="" title="CaBLAM help"></a>
    <p><label><b>RNA</b></label>
    <br><label><input type='checkbox' name='chartBaseP' value='1'> RNA sugar pucker analysis</label> <a target="_blank" href="help/validation_options/validation_options.html#sugarpuckers"> <img src="img/helplink.jpg" alt="" title="Sugar pucker help"></a>
    <br><label><input type='checkbox' name='chartSuite' value='1'> RNA backbone conformations</label> <a target="_blank" href="help/validation_options/validation_options.html#suites"> <img src="img/helplink.jpg" alt="" title="Suite help"></a>
    <p><label><b>Other options</b></label>
    <br><label><input type='checkbox' name='chartHoriz' value='1'> Horizontal chart with real-space correlation data</label>
    <br><label><input type='checkbox' name='chartCoot' value='1'> Chart for use with Coot (may take a long time, but should take less than 1 hour) </label>
    <br><label><input type='checkbox' name='chartImprove' value='1'> Suggest / report on automatic structure fix-ups</label>
    <br><label><input type='checkbox' name='chartMulti' value='1' onclick='hideMultiOpts()'> Create html version of multi-chart</label>
    <div class='closeindent' id='multi_opts'>
    <label><input type='checkbox' name='chartNotJustOut' value='1'> List all residues in multi-chart, not just outliers</label>
    <br><label><input type='checkbox' name='chartAltloc' value='1'> Remove residue rows with ' ' altloc when other alternate(s) present</label>
    </div>
    </div>
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
        echo "No models are available. Please <a href='".makeEventURL("onCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
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
    if(isset($req['modelID']))
    {
        $_SESSION['lastUsedModelID'] = $req['modelID']; // this is now the current model
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob'] = $req;

        mpLog("aacgeom:Running all-atom contact and geometric analyses");

        if($req['kinClashes'] || $req['kinHbonds'] || $req['kinContacts'] || $req['chartClashlist'])
            mpLog("aacgeom-aac:Generataing all-atom contact data of some type");
        if($req['kinRama'] || $req['chartRama'])    mpLog("aacgeom-rama:Doing Ramachandran analysis");
        if($req['kinRota'] || $req['chartRota'])    mpLog("aacgeom-rota:Doing rotamer analysis");
        if($req['kinGeom'] || $req['chartGeom'])    mpLog("aacgeom-geom:Doing geometry analysis");
        if($req['kinCBdev'] || $req['chartCBdev'])  mpLog("aacgeom-cbdev:Doing C-beta deviation analysis");
        if($req['kinBaseP'] || $req['chartBaseP'])  mpLog("aacgeom-basep:Validating base-phosphate distances vs sugar puckers");
        if($req['kinSuite'] || $req['kinSuiteHighD'] || $req['chartSuite'])  mpLog("aacgeom-suite:Validating RNA backbone conformations");

        if($req['doKinemage'])      mpLog("aacgeom-mkin:Multi-criterion validation kinemage");
        if($req['doCharts'])        mpLog("aacgeom-mchart:Multi-criterion validation chart");
        $modelID = $_SESSION['bgjob']['modelID'];
        // $model   = $_SESSION['models'][$modelID];
        if($req['chartHoriz'] and !isset($_SESSION['models'][$modelID]['mtz_file']))
        {
            // check to see if there is one mtz. If so, then link it to the
            // model being analyzed automatically
            $xrayDir = $_SESSION['dataDir'].'/'.MP_DIR_XRAYDATA;
            if(file_exists($xrayDir))
            {
                $mtzs = array();
                $handle = opendir($xrayDir);
                while(false !== ($entry = readdir($handle)))
                {
                    if(substr($entry, -4) != ".mtz") continue;
                    $mtzs[] = $entry;
                }
                if(count($mtzs) == 1)
                {
                    $_SESSION['models'][$modelID]['mtz_file'] = $xrayDir.'/'.$mtzs[0];
                    // launch background job
                    pageGoto("job_progress.php");
                    launchBackground(MP_BASE_DIR."/jobs/aacgeom.php", "generic_done.php", 5);
                }
                else
                    pageCall("link_model_2_mtz.php", array('modelID' => $modelID));
            }
            else
                pageCall("link_model_2_mtz.php", array('modelID' => $modelID));
        }
        else
        {
            // launch background job
            pageGoto("job_progress.php");
            launchBackground(MP_BASE_DIR."/jobs/aacgeom.php", "generic_done.php", 5);
        }
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

}//end of aacgeom_setup_delegate class definition
?>
