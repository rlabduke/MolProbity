<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a sample delegate page for index.php that presents a UI
    and handles user input events.
    
    See public_html/index.php and lib/event_page.php for the mechanism.
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new Dummy2Delegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class Dummy2Delegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Documentation for this function.
*/
function display($context)
{
    echo mpPageHeader("MolProbity Home", "home");
    echo "<h2>Test page... Hello, world. Page 2 speaking.</h2>";
    echo "<p><a href='".makeEventURL("doIt", "now")."'>click me!</a></p>\n";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ doIt - a fake event handler
############################################################################
/**
* Documentation for this function.
*/
function doIt($arg, $req)
{
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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
