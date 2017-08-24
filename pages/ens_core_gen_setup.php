<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to generate the core and non-core parts of their ensemble.
    It should be accessed by pageCall()
*****************************************************************************/
//require_once(MP_BASE_DIR.'/lib/model.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class ens_core_gen_setup_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   ensID       the ensemble ID to analyze
*/
function display($context)
{
    echo $this->pageHeader("Split PDB in core and non-core files");

    if(count($_SESSION['ensembles']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['ensID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];

        echo makeEventForm("onCoreGen");
        echo "<h3>Select an ensemble to split into core and non-core:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['ensembles'] as $id => $ensemble)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($lastUsedID == $id ? "checked" : "");
            echo "  <td><input type='radio' name='ensID' value='$id' onclick='setAnalyses($doAAC, $hasProtein, $hasNucAcid, $isBig, $isLowRes)' $checked></td>\n";
            echo "  <td><b>$ensemble[pdb]</b></td>\n";
            echo "  <td><small>$ensemble[history]</small></td>\n";
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<h3>Select a method to use:</h3>";
        echo "<p><table width='100%' border='0'>\n";

        $check1 = ""; $check2 = "checked";
        echo "<tr valign='top'><td width='400'><input type='radio' name='method' value='cyrange' $check1> <b>Use <code>CYRANGE</code> to calculate core residues</b></td>";
        echo "<td><small>Calculate core residues using <code>CYRANGE</code> and split PDB into core/non-core residues\n</td>";
        echo "<tr><td colspan='2'>&nbsp;</td></tr>\n"; // vertical spacer
        echo "</table></p>\n";

        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Generate PDBs &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
?>
<script type='text/javascript'>
function setFlipkins(hasProtein)
{
    flipkin = document.getElementById("makeFlipkin");
    flipkin.checked = hasProtein;
    use_rename = document.getElementById("use_rename");
    use_rename.checked = false;
}

// This nifty function means we won't override other ONLOAD handlers
function windowOnload(f)
{
    var prev = window.onload;
    window.onload = function() { if(prev) prev(); f(); }
}

// On page load, find the selected model and sync us to its state
windowOnload(function() {
    var models = document.getElementsByName('modelID');
    for(var i = 0; i < models.length; i++)
    {
        if(models[i].checked) models[i].onclick();
    }
});
</script>
<hr>
<div class='help_info'>
<h4>Generating core/non-core PDB files</h4>
This option runs the CYRANGE program, developed by D.K. Kirchner and P. Güntert at Goethe University Frankfurt. Please cite D.K. Kirchner and P. Güntert, BMC Bioinformatics 2011, 12:170 if you make use of this feature.
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

#{{{ onCoreGen
############################################################################
/**
* Documentation for this function.
*/
function onCoreGen()
{
    $req = $_REQUEST;
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }

    // Otherwise, moving forward:
    if(isset($req['ensID']) && isset($req['method']))
    {
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob']['ensID']        = $req['ensID'];
        $_SESSION['bgjob']['method']       = $req['method'];

        if($req['method'] == 'cyrange')
        {
          mpLog("cyrange-ensemble:User ran CYRANGE on an ensemble");
          // launch background job
          pageGoto("job_progress.php");
          launchBackground(MP_BASE_DIR."/jobs/ens_run_cyranger.php", "generic_done.php", 5);
        }
    }
    else
    {
        $context = getContext();
        if(isset($req['ensID']))  $context['ensID'] = $req['ensID'];
        if(isset($req['method']))   $context['method'] = $req['method'];
        setContext($context);
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
