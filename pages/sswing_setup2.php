<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to run SSWING.
    This page is for choosing the residues to refit;
    the previous one was for choosing model and map.
    If model and map are already specified, one can enter the SSwing loop
    here instead of at sswing_setup1.php, using pageCall().
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/analyze.php');

// This variable must be defined for index.php to work! Must match class below.
$delegate = new SSwingSetup2Delegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class SSwingSetup2Delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context MUST contain the following keys:
*   modelID     the model ID to add H to
*   map         the ED map to use
* Context MAY contain the following keys:
*   cnit[]      a map of "CNIT" residue names to on/off values
*               Residues marked on will be refit by SSwing.
*/
function display($context)
{
    echo mpPageHeader("Refit sidechains");
    $modelID = $context['modelID'];
    $model = $_SESSION['models'][$modelID];
    $all_res = listProteinResidues($_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb']);
    
    echo makeEventForm("onStartSSwing");
    echo "Input PDB file: <b>$model[pdb]</b>\n";
    echo "<br>Input map file: <b>$context[map]</b>\n";
    
    echo "<h3>Select residue(s) to refit:</h3>";
    echo "<table width='100%' border='0'><tr valign='top'><td width='20%'>\n";
    $i = 0;
    $col = ceil(count($all_res)/5);
    foreach($all_res as $res)
    {
        if(++$i > $col)
        {
            echo "</td><td width='20%'>\n";
            $i = 1;
        }
        echo "<br><input type='checkbox' name='cnit[$res]' value='$res'>$res</input>\n";
    }
    echo "</td></tr></table>\n";
    
    echo "<p><table width='100%' border='0'><tr>\n";
    echo "<td><input type='submit' name='cmd' value='Run SSwing to refit these residues &gt;'></td>\n";
    echo "<td align='right'><input type='submit' name='cmd' value='Go back'></td>\n";
    echo "</tr></table></p></form>\n";
    
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onStartSSwing
############################################################################
/**
* Documentation for this function.
*/
function onStartSSwing($arg, $req)
{
    if($req['cmd'] == 'Go back')
    {
        $c = getContext();
        $ctx['modelID'] = $c['modelID'];
        $ctx['map']     = $c['map'];
        pageGoto("sswing_setup1.php", $ctx);
        return;
    }
    
    $ctx = getContext();
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['modelID']   = $ctx['modelID'];
    $_SESSION['bgjob']['edmap']     = $ctx['map'];
    $_SESSION['bgjob']['cnit']      = $req['cnit'];
    
    mpLog("sswing:Launched SSWING to refit ".count($_req['cnit'])." residue(s)");
    // launch background job
    pageGoto("job_progress.php");
    launchBackground(MP_BASE_DIR."/jobs/sswing.php", "sitemap.php", 5);
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
