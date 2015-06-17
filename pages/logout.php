<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page warns the user before they log out.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class logout_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Log out &amp; end session", "logout");
?>
<p>We appreciate your help in freeing up disk space for other users.
By clicking the button below, you will <b>permanently delete</b> all the files you generated during this session.
Before logging out, you may wish to
<a href='<?php echo makeEventURL('onGoto', 'file_browser.php'); ?>'>download</a>
some of your files.
It is also possible to 
<a href='<?php echo makeEventURL('onGoto', 'save_session.php'); ?>'>save this session</a>
and return to do more work with these files later.

<p><form method="post" action="logout_destroy.php">
<?php echo postSessionID(); ?>
<input type="hidden" name="confirm" value="1">
<br>This action cannot be undone:
<input type="submit" name="cmd" value="Destroy all my files and log me out &gt;">
</form>
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
