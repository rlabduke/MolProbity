<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to add hydrogens to one of their models.
    It should be accessed by pageCall()
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');

// This variable must be defined for index.php to work! Must match class below.
$delegate = new ReduceSetupDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class ReduceSetupDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID     the model ID to add H to
*   method      the means of adding H: nobuild or build
*/
function display($context)
{
    echo mpPageHeader("Add hydrogens");
    
    if(count($_SESSION['models']) > 0)
    {
        echo makeEventForm("onAddH");
        echo "<h3>Select a model to add H to:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
            if($model['isReduced'])
            {
                echo "  <td><span class='inactive'><b>$id</b></span></td>\n";
                echo "  <td><span class='inactive'><small>$model[history]</small></span></td>\n";
            }
            else
            {
                $checked = ($context['modelID'] == $id ? "checked" : "");
                echo "  <td><input type='radio' name='modelID' value='$id' $checked> <b>$id</b></td>\n";
                echo "  <td><small>$model[history]</small></td>\n";
            }
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<h3>Select a method of adding H:</h3>";
        echo "<p><table width='100%' border='0'>\n";
        $check1 = ($context['method'] == 'build' ? "checked" : "");
        $check2 = ($context['method'] == 'nobuild' ? "checked" : "");
        echo "<tr valign='top'><td><input type='radio' name='method' value='build' $check1> <b>Add &amp; Optimize</b><td>";
        //echo "<td><small>Add missing H, optimize H-bond networks, check for flipped Asn, Gln, His</small></td></tr>\n";
        echo "<td><small>Add missing H, optimize H-bond networks, check for flipped Asn, Gln, His";
        echo " (<code>Reduce -build</code>)\n";
        echo "<div class='inline_options'><b>Advanced options:</b>\n";
        echo "<label><input type='checkbox' name='makeFlipkin' value='1' checked>\n";
        echo "Make Flipkin kinemages illustrating any Asn, Gln, or His flips</label></div>\n";
        echo "</small></td></tr>\n";
        echo "<tr valign='top'><td><input type='radio' name='method' value='build' $check2> <b>Add missing ONLY</b><td>";
        echo "<td><small>Add missing H only, leave all other atoms alone (<code>Reduce -keep -noadjust -his</code>)</small></td></tr>\n";
        echo "</table></p>\n";

        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='&lt; Cancel adding H'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Start adding H &gt;'></td>\n";
        echo "</tr></table></p>\n";
    }
    else
    {
        // no models available
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

#{{{ onAddH
############################################################################
/**
* Documentation for this function.
*/
function onAddH($arg, $req)
{
    if($req['cmd'] == '< Cancel adding H')
    {
        pageReturn();
        return;
    }
    
    // Otherwise, moving forward:
    if(isset($req['modelID']) && isset($req['method']))
    {
    }
    else
    {
        $context = getContext();
        if(isset($req['modelID']))  $context['modelID'] = $req['modelID'];
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
