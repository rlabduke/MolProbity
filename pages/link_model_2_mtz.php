<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a template for a UI delegate page that lives in pages/
    You should change this comment to reflect what your page actually does.
    
    There's a tutorial in doc/extending/ that explains in more detail how
    to use this template to extend MolProbity.
*****************************************************************************/
// Includes go here. For example:
require_once(MP_BASE_DIR.'/lib/labbook.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
// The name of the class must match the name of the file, with ".php" taken off
// and "_delegate" appended. See makeDelegateObject() in lib/event_page.php
class link_model_2_mtz_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Make sure you say what $context is here. For example:
*
* Context is an array containing:
*   labbookEntry    the labbook entry number for adding this new model
*/
function display($context)
{
    $modelID = $context['modelID'];
    echo $this->pageHeader("Link mtz to $modelID");
    
    echo "<p>Please choose the mtz corresponding to $modelID";
    echo "<p>" . makeEventForm("onContinueLinkMtz");
    echo "\n<input type='hidden' name='modelID' value='$modelID'>\n";
    echo $this->getTable($modelID);
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n";
    echo "</form></p>\n";
    // Note the explicit </form> to end the form!

    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ getTable  -  makes html tables with buttons for each mtz found 
############################################################################
/**
* Documentation for this function.
*/
function getTable($modelID) {
    $xraydir = $_SESSION['dataDir'].'/'.MP_DIR_XRAYDATA;
    if(!file_exists($xraydir))
    {
        $s = "<div class=alert><strong>Cannot do rscc analysis; no xray data";
        $s.= " detected. Please go back to home anf upload an mtz file with";
        $s.= " amplitudes.\n</strong></div>\n<br />";
        return $s;
    }
    $s = "<table style='padding:5px';>\n  <tr style='background-color:#9999CC;'>";
    $s.= "<td>Link?</td>\n      <td> to $modelID";
    $s.= "</td>\n  </tr>\n";
    $handle = opendir($xraydir);
    $i = 0;
    while(false !== ($entry = readdir($handle)))
    {
        if(substr($entry, -4) != ".mtz") continue;
        if( $i % 2 == 0 ) $color = "background-color:#FFFFFF;";
        else $color = "background-color:#F0F0F0;";
        $s.= "  <tr style='$color'><td><input type='radio' checked name='mtz' value='$entry'></td>\n";
        $s.= "      <td>$entry</td>\n  </tr>\n";
        $i++;
    }
    $s.= "</table>";
    return $s;
}
#}}}########################################################################

#{{{ onContinueLinkMtz
############################################################################
/**
*/
function onContinueLinkMtz($modelID)
{
    $modelID = $_REQUEST['modelID'];
    $xrayDir    = $_SESSION['dataDir'].'/'.MP_DIR_XRAYDATA;
    if(isset($_REQUEST['mtz']))
        $_SESSION['models'][$modelID]['mtz_file'] = $xrayDir.'/'.$_REQUEST['mtz'];
    pageReturn();
    return;
    //pageCall("notebook_edit.php", array('entryNumber' => $arg));
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
}
?>
