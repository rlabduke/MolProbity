<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is an overall "site map" or super-index page for MolProbity3 experts.
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new SitemapDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class SitemapDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Site map", "home");

    echo "<p><a href='".makeEventURL("gotoNotebook")."'>Lab notebook</a></p>\n";

    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ gotoNotebook
############################################################################
/**
* Documentation for this function.
*/
function gotoNotebook($arg, $req)
{
    pageGoto("notebook_main.php");
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
