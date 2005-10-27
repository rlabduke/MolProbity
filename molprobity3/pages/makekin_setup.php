<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to request a basic kinemage from Prekin.
*****************************************************************************/
// Includes go here. For example:
//  require_once(MP_BASE_DIR.'/lib/labbook.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class makekin_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
function display($context)
{
    echo mpPageHeader("Make simple kinemages");
    
    if(count($_SESSION['models']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['modelID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
        
        // These lines create an HTML form that will call onRunPrekin() to be called
        // when the user clicks the submit button. onRunPrekin() is declared below.
        echo makeEventForm("onRunPrekin");
        echo "<h3>Select a model to work with:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($lastUsedID == $id ? "checked" : "");
            echo "  <td><input type='radio' name='modelID' value='$id' $checked></td>\n";
            echo "  <td><b>$model[pdb]</b></td>\n";
            echo "  <td><small>$model[history]</small></td>\n";
            echo " </tr>\n";
        }
        echo "</table></p>\n";
    
        echo "<h3>Choose a type of kinemage to make:</h3>";
        echo "<table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        // Rib 'n' Het
        echo "<tr bgcolor='".MP_TABLE_ALT2."'><td><input type='radio' name='scriptName' value='ribnhet'></td>\n";
        echo "<td>Ribbon representation, colored by secondary structure (if present in PDB file)</td></tr>\n";
        // Lots
        echo "<tr bgcolor='".MP_TABLE_ALT1."'><td><input type='radio' name='scriptName' value='lots'></td>\n";
        echo "<td>Mainchain, sidechains, alpha carbon trace, hydrogens, hets, waters (color by sidechain/mainchain)</td></tr>\n";
        // Lots -- half bonds
        echo "<tr bgcolor='".MP_TABLE_ALT2."'><td><input type='radio' name='scriptName' value='halfbonds'></td>\n";
        echo "<td>Mainchain, sidechains, alpha carbon trace, hydrogens, hets, waters (<b>half-bond coloring</b>)</td></tr>\n";
        // CA -- SS
        echo "<tr bgcolor='".MP_TABLE_ALT1."'><td><input type='radio' name='scriptName' value='cass'></td>\n";
        echo "<td>Alpha carbon trace with disulfides and non-water hets</td></tr>\n";
        // MC -- HB
        echo "<tr bgcolor='".MP_TABLE_ALT2."'><td><input type='radio' name='scriptName' value='mchb'></td>\n";
        echo "<td>Mainchain and its hydrogen bonds</td></tr>\n";
        // AA/SC
        echo "<tr bgcolor='".MP_TABLE_ALT1."'><td><input type='radio' name='scriptName' value='aasc'></td>\n";
        echo "<td>Mainchain and sidechains, with amino acids grouped into sets</td></tr>\n";
        // NABA
        echo "<tr bgcolor='".MP_TABLE_ALT2."'><td><input type='radio' name='scriptName' value='naba'></td>\n";
        echo "<td>Nucleic acid bases grouped into sets and more</td></tr>\n";
    
        echo "</table>\n";
        echo "<p><label><input type='checkbox' name='rainbow' value='1'> Rainbow colors N-&gt;C / 5&apos;-&gt;3&apos; on mainchain</label>\n";
        echo "<br><label><input type='checkbox' name='cpkballs' value='1'> CPK-colored markers on non-carbons</label></p>\n";
        //echo "</p>\n"; // can't make the above work...
        
        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Make kinemage &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
        // Note the explicit </form> to end the form!
?>
<hr>
<div class='help_info'>
<h4>Making simple kinemages</h4>
<i>TODO: Help text about making kinemages goes here</i>
</div>
<?php
    }
    else
    {
        echo "No models are available. Please <a href='".makeEventURL("onNavBarCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
        echo makeEventForm("onRunPrekin");
        echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
        
    }

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onRunPrekin
############################################################################
/**
* Launches Prekin when the user submits the form.
*/
function onRunPrekin($arg, $req)
{
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }
    
    // Otherwise, moving forward:
    if(isset($req['modelID']) && isset($req['scriptName']))
    {
        $_SESSION['lastUsedModelID'] = $req['modelID']; // this is now the current model
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob'] = $req;
        
        mpLog("makekin:Creating simple kinemages from built-in Prekin script '$req[scriptName]'");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/makekin.php", "makekin_done.php", 3);
    }
    else
    {
        $context = getContext();
        if(isset($req['modelID']))      $context['modelID']     = $req['modelID'];
        if(isset($req['scriptName']))   $context['scriptName']  = $req['scriptName'];
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
