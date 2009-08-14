<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page sumarizes the results of some operation by displaying
    a lab notebook entry. Afterwards, it will allow the user to pageReturn().
    
    This is a generic endpoint for most of the MolProbity tasks.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class fetch_eds_autofix_done_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   labbookEntry    the labbook entry number
*/
function display($context)
{
    $labbook = openLabbook();
    $num = $context['labbookEntry'];

    echo $this->pageHeader($labbook[$num]['title']);
    
    //echo formatLabbookEntry($labbook[$num]);
    echo $labbook[$num]['entry']; // avoid date stamp, title clutter
    
    //echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
    echo "<p>" . makeEventForm("onAutoFixMapLoaded");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";

    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onEditNotebook
############################################################################
/**
* Documentation for this function.
*/
function onEditNotebook($arg)
{
    pageCall("notebook_edit.php", array('entryNumber' => $arg));
}
#}}}########################################################################

#{{{ onAutoFixMapLoaded
############################################################################
/**
* Documentation for this function.
*/
function onAutoFixMapLoaded()
{
    if($_REQUEST['cmd'] == 'Continue >')
        pageGoto("autofix_setup.php");
    else
        pageReturn();
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
