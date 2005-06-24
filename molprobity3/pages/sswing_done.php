<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page sumarizes the results of running SSwing on a PDB file.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class sswing_done_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   labbookEntry    the labbook entry number for adding this new model
*/
function display($context)
{
    echo mpPageHeader("Add hydrogens");

    $labbook = openLabbook();
    $num = $context['labbookEntry'];
    echo formatLabbookEntry($labbook[$num]);
    echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
    echo "<p>" . makeEventForm("onReturn");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";

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

#{{{ onEditNotebook
############################################################################
/**
* Documentation for this function.
*/
function onEditNotebook($arg, $req)
{
    pageCall("notebook_edit.php", array('entryNumber' => $arg));
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
