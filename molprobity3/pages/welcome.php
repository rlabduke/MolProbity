<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class welcome_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Welcome!", "welcome");
?>
<center><h2>MolProbity:<br>Macromolecular Structure Validation</h2></center>
<table border='0' width='100%'><tr valign='top'><td width='45%'>
<h3 class='nospaceafter'><?php echo "<a href='".makeEventURL("onNavBarGoto", "sitemap.php")."'>Site map</a>"; ?></h3>
<div class='indent'>Minimum-guidance interface for experienced users.</div>
<h3 class='nospaceafter'><?php echo "<a href='".makeEventURL("onNavBarGoto", "helper_xray.php")."'>Evaluate X-ray structure</a>"; ?></h3>
<div class='indent'>Typical steps for a published X-ray crystal structure
or one still undergoing refinement.</div>
<h3 class='nospaceafter'>Evaluate NMR structure</h3>
<div class='indent'>Typical steps for a published NMR ensemble
or one still undergoing refinement.</div>
<h3 class='nospaceafter'>Fix up structure</h3>
<div class='indent'>Rebuild the model to remove outliers
as part of the refinement cycle.</div>
<h3 class='nospaceafter'>Work with kinemages</h3>
<div class='indent'>Create and view interactive 3-D graphics
from your web browser.</div>
</td><td width='10%'><!-- horizontal spacer --></td><td width=='45%'>
<h3>Common questions:</h3>
<p><a href='help/about.html' target='_blank'>Cite MolProbity</a>: references for use in documents and presentations.</p>
<p><u>Installing Java</u>: how to make kinemage graphics work in your browser.</p>
<p><u>Lab notebook</u>: what's it for and how do I use it?</p>
<p><u>Adding hydrogens</u>: why are H necessary for steric evaluations?</p>
<p><u>My own MolProbity</u>: how can I run my own private MolProbity server?</p>
</td></tr></table>
<?php
    echo mpPageFooter();
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
