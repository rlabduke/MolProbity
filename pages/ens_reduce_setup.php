<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to add hydrogens to one of their models.
    It should be accessed by pageCall()
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class ens_reduce_setup_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   ensID       the ensemble ID to add H to
*   method      the means of adding H: nobuild or build
*/
function display($context)
{
    echo $this->pageHeader("Add hydrogens");

    if(count($_SESSION['ensembles']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['ensID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];

        echo makeEventForm("onAddH");
        echo "<h3>Select an ensemble to add H to:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['ensembles'] as $id => $ensemble)
        {
            // Use the first model of each ensemble as representative.
            $modelID = reset($ensemble['models']);
            $model = $_SESSION['models'][$modelID];
            $stats = $model['stats'];
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            if($ensemble['isReduced'])
            {
                echo "  <td></td>\n";
                echo "  <td><span class='inactive' title='Already has H added'><b>$ensemble[pdb]</b></span></td>\n";
                echo "  <td><span class='inactive'><small>$ensemble[history]</small></span></td>\n";
            }
            else
            {
                $stats = $model['stats'];
                $hasProtein = ($stats['sidechains'] > 0 ? "true" : "false");
                $hasNucAcid = ($stats['nucacids'] > 0 ? "true" : "false");
                $checked = ($lastUsedID == $id ? "checked" : "");
                echo "  <td><input type='radio' name='ensID' value='$id' $checked></td>\n";
                echo "  <td><b>$ensemble[pdb]</b></td>\n";
                echo "  <td><small>$ensemble[history]</small></td>\n";
            }
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<h3>Select a method of adding H:</h3>";
        echo "<p><table width='100%' border='0'>\n";
        // Starts with no default method checked. This is well-intentioned but annoying.
        //$check1 = ($context['method'] == 'build' ? "checked" : "");
        //$check2 = ($context['method'] == 'nobuild' ? "checked" : "");
        // Selects -NOBUILD by default unless the user changes it.
        if($context['method'] == 'build')   { $check1 = "checked"; $check2 = ""; }
        else                                { $check1 = ""; $check2 = "checked"; }
        echo "<tr valign='top'><td><input type='radio' name='method' value='build' $check1> <b>Asn/Gln/His flips</b><td>";
        echo "<td><small>Add missing H, optimize H-bond networks, check for flipped Asn, Gln, His";
        echo " (<code>Reduce -build</code>)\n";
        #echo "<div class='inline_options'><b>Advanced options:</b>\n";
        #echo "<label><input type='checkbox' name='makeFlipkin' id='makeFlipkin' value='1' checked>\n";
        #echo "Make Flipkin kinemages illustrating any Asn, Gln, or His flips</label></div>\n";
        echo "</small></td></tr>\n";
        echo "<tr><td colspan='2'>&nbsp;</td></tr>\n"; // vertical spacer
        echo "<tr valign='top'><td><input type='radio' name='method' value='nobuild' $check2> <b>No flips</b><td>";
        echo "<td><small>Add missing H, optimize H-bond networks, leave other atoms alone (<code>Reduce -nobuild</code>)</small></td></tr>\n";
        echo "</table></p>\n";

        echo "<h3>Select x-H bond-length:</h3>";
        echo "<p><table width='100%' border='0'>\n";
        if($context['method'] == 'ecloud') { $check1 = "checked"; $check2 = ""; }
        else                                { $check1 = ""; $check2 = "checked"; }
        echo "<tr valign='top'><td width='300'><input type='radio' name='blength' value='ecloud' $check1> <b>Electron-cloud x-H</b><td>";
        echo "<td><small>Use electron-cloud x-H bond lengths and vdW radii.\nIdeal for most cases, especially X-ray crystal structures.";
        echo "</small></td></tr>\n";
        echo "<tr><td colspan='2'>&nbsp;</td></tr>\n"; // vertical spacer
        echo "<tr valign='top' align='left'><td width='300'><input type='radio' name='blength' value='nuclear' $check2> <b>Nuclear x-H</b><td>";
        echo "<td><small>Use nuclear x-H bond lengths and vdW radii.\nIdeal for NMR, neutron diffraction, etc.</small></td></tr>\n";
        echo "</table></p>\n";

        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Start adding H &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
?>
<hr>
<div class='help_info'>
<h4>Adding hydrogens</h4>
<i>TODO: Help text about Reduce and adding hydrogens goes here</i>
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

#{{{ onAddH
############################################################################
/**
* Documentation for this function.
*/
function onAddH()
{
    $req = $_REQUEST;
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }

    // Otherwise, moving forward:
    if(isset($req['ensID']) && isset($req['method']) && isset($req['blength']))
    {
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob']['ensID']     = $req['ensID'];
        $_SESSION['bgjob']['method']    = $req['method'];
        $_SESSION['bgjob']['reduce_blength'] = $req['blength'];

        mpLog("reduce-ensemble:User ran default Reduce -$req[method] job on an ensemble");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/ens_reduce.php", "generic_done.php", 5);
    }
    else
    {
        $context = getContext();
        if(isset($req['ensID']))    $context['ensID'] = $req['ensID'];
        if(isset($req['method']))   $context['method'] = $req['method'];
        if(isset($req['blength']))   $context['blength'] = $req['blength'];
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
