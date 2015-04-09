<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Brief guide to validation options for users
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_validation_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("About Updated Hydrogens", "helper_hydrogens");
?>

<?php
    echo $this->pageFooter();
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
