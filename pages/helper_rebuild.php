<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_rebuild_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Fix up structure", "helper_rebuild");
?>
    See our
    <b><a href='http://kinemage.biochem.duke.edu/teaching/workshop/CSHL2012/' target='_blank'>2012 Cold Spring Harbor tutorial on MolProbity</a></b>
    for instructions on using KiNG and <b>Coot</b> to rebuild structures. For a full list of available tutorials, please look
    <b><a href='http://kinemage.biochem.duke.edu/teaching/workshop/' target='_blank'>here.</a></b>
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
