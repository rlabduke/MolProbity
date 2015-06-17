<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_kinemage_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Work with kinemages", "helper_kinemage");
?>

This page explains how <b>teachers and students</b> can use MolProbity
to look at 3-D pictures of proteins and nucleic acids.

<ol>
<li><b><?php echo "<a href='".makeEventURL("onCall", "upload_setup.php")."'>Choose a structure</a>"; ?>:</b>
    You will need to locate a model of the structure you're interested in.
    Publically available models are archived in the 
    <a href='http://www.pdb.org/' target='blank'>Protein Data Bank</a>,
    where they are identified by a <b>four character code</b>.
    For instance, 2HHB is a structure of hemoglobin, a molecule that transports oxygen in the blood.
    Once you know the code for the molecule you're interested in, MolProbity can fetch it for you.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "makekin_setup.php")."'>Make a kinemage</a>"; ?>:</b>
    "Kinemages" (kinetic images) are three-dimensional illustrations of molcular structure.
    MolProbity will make a variety of different kinds of pictures --
    ribbons are good for seeing the overall fold,
    but ball-and-stick is good for seeing details.
    <!-- to do: link to a Molikin tutorial for constructing more complex kins -->
</li>
<li><b>View your kinemage:</b>
    You can either view your kinemage directly in the web browser using KiNG (requires Java),
    or you can <a href='http://kinemage.biochem.duke.edu/software/mage.php' target='blank'>download Mage</a>
    or <a href='http://kinemage.biochem.duke.edu/software/king.php' target='blank'>KiNG</a>
    to your local machine and view the kinemage files there.
    If you're having trouble getting KiNG to work in the browser, see our
    <a href='help/java.html' target='_blank'>help page for Java</a>.
</li>
<li><b><?php echo "<a href='".makeEventURL("onGoto", "file_browser.php")."'>Download files</a>"; ?>:</b>
    Before you leave, you may want to download the files you've created.
    Click the triangle icons to open and close the different folders.
</li>
<li><b><?php echo "<a href='".makeEventURL("onGoto", "logout.php")."'>Log out</a>"; ?>:</b>
    This will permanenty remove your files from our server, freeing up space for other users.
</li>
</ol>
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
