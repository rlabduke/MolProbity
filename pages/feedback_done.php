<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page confirms to the user that they have sent a feedback email.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class feedback_done_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is the text of the message that was sent.
*/
function display($context)
{
    echo $this->pageHeader("Email sent", "feedback");
    echo "<p>Your email was successfully sent to the author(s) and maintainer(s) of MolProbity.\n";
    echo "You should receive a response, if needed, within a few days.</p>\n";

    echo "<p><div class='alert'>If you're having a problem or think you've found a bug, <strong>please do not delete the session </strong> associated with this report.  We need the input files to reproduce the bug, and we will take care of deleting those files later.  Your files will only be used for debugging purposes.  <br><br>We will contact you for the input files if you delete the session accidentally (assuming you provided your email).</div></p>\n";
    /*echo "if you don't mind the MolProbity maintainers seeing your data files,\n";
    echo "then please do NOT log out and erase your files.\n";
    echo "This will help us diagnose and correct the problem.</div></p>\n";*/

    echo "<hr><tt>\n";
    echo nl2br(htmlspecialchars($context));
    echo "</tt>\n";

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
