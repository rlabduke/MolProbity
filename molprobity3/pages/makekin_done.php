<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page presents the kinemage created by the user.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// This variable must be defined for index.php to work! Must match class below.
$delegate = new MakeKinDoneDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class MakeKinDoneDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   labbookEntry    the labbook entry number
*/
function display($context)
{
    echo mpPageHeader("All-atom contact and geometric analyses");

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
