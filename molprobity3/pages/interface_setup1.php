<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to choose a model for analysis with Probe.
*****************************************************************************/
// Includes go here. For example:
//  require_once(MP_BASE_DIR.'/lib/labbook.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class interface_setup1_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
function display($context)
{
    echo mpPageHeader("Visualize interface contacts");
    
?><script language='JavaScript'>
<!--
function warnNoH(obj)
{
    if(!window.confirm("The file you chose may not have all its H atoms added."+
    " All-atom contacts requires all H atoms to function properly."))
    {
        obj.checked = false
    }
}
// -->
</script><?php

    if(count($_SESSION['models']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['modelID'];
        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
        
        // These lines create an HTML form that will call onChooseOptions() to be called
        // when the user clicks the submit button. onChooseOptions() is declared below.
        echo makeEventForm("onChooseOptions");
        echo "<h3>Select a model to work with:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            $checked = ($lastUsedID == $id ? "checked" : "");
            if($model['isReduced'])
            {
                echo "  <td><input type='radio' name='modelID' value='$id' $checked></td>\n";
                echo "  <td><b>$model[pdb]</b></td>\n";
                echo "  <td><small>$model[history]</small></td>\n";
            }
            else
            {
                echo "  <td><input type='radio' name='modelID' value='$id' onclick='warnNoH(this)'></td>\n";
                echo "  <td><span class='inactive' title='Already has H added'><b>$model[pdb]</b></span></td>\n";
                echo "  <td><span class='inactive'><small>$model[history]</small></span></td>\n";
            }
            echo " </tr>\n";
        }
        echo "</table></p>\n";
    
        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Choose Probe options &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";
        // Note the explicit </form> to end the form!
?>
<hr>
<div class='help_info'>
<h4>Visualizing interface contacts</h4>
<i>TODO: Help text about interfaces and Probe goes here</i>
</div>
<?php
    }
    else
    {
        echo "No models are available. Please <a href='".makeEventURL("onNavBarCall", "upload_pdb_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
        echo makeEventForm("onChooseOptions");
        echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
        
    }

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onChooseOptions
############################################################################
/**
* Launches Prekin when the user submits the form.
*/
function onChooseOptions($arg, $req)
{
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }
    
    // Otherwise, moving forward:
    if(isset($req['modelID']))
    {
        $ctx['modelID'] = $req['modelID'];
        pageGoto("interface_setup2.php", $ctx);
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
