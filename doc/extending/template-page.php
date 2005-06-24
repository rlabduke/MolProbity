<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a template for a UI delegate page that lives in pages/
    You should change this comment to reflect what your page actually does.
    
    There's a tutorial in doc/extending/ that explains in more detail how
    to use this template to extend MolProbity.
*****************************************************************************/
// Includes go here. For example:
require_once(MP_BASE_DIR.'/lib/labbook.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
// The name of the class must match the name of the file, with ".php" taken off
// and "_delegate" appended. See makeDelegateObject() in lib/event_page.php
class template_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Make sure you say what $context is here. For example:
*
* Context is an array containing:
*   labbookEntry    the labbook entry number for adding this new model
*/
function display($context)
{
    echo mpPageHeader("NAME OF YOUR PAGE GOES HERE");
    
    // Here's a sample page that displays a notebook entry.
    // The notebook entry number was specified in $context['labbookEntry']
    // This is a common way to display results of a background job.

    // Load and format the notebook entry:
    $labbook = openLabbook();
    $num = $context['labbookEntry'];
    echo formatLabbookEntry($labbook[$num]);
    
    // This line makes a URL that, when clicked, will cause the onEditNotebook()
    // function to be called. It's declared below...
    echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
    
    // These lines create an HTML form that will call onReturn() to be called
    // when the user clicks the Continue > button. onReturn() is declared below.
    echo "<p>" . makeEventForm("onReturn");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    // Note the explicit </form> to end the form!

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onReturn
############################################################################
/**
* This function returns control back to the page that pageCall()'d to get here.
* This assumes that this page or its predecesor was accessed by pageCall()
* rather than pageGoto(). If that's not the case, you should be using
* pageGoto() here instead.
*
* This function gets called when the user submits the form made by display()
*
* Notice that $arg isn't used--the call to makeEventForm() didn't specify an arg
* $req is filled in with the usually info from the form submission, but
* we don't need to use it for anything here.
*/
function onReturn($arg, $req)
{
    pageReturn();
}
#}}}########################################################################

#{{{ onEditNotebook
############################################################################
/**
* This function calls the notebook editor so the user can modify the notebook
* entry. Control is transfered to another page, namely, notebook_edit.php.
* When that page is done, it will call pageReturn(), and control will return
* to this class--display() will be called again to show the entry.
*
* This function gets called when the user clicks the link made by display()
*
* $arg contains the entry number of the notebook entry to edit. It was specified
* by the call to makeEventURL() that occurs in display(), above.
* $req is filled in with the usually info from the form submission, but
* we don't need to use it for anything here.
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
