<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to bookmark their session.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class save_session_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Save session", "savesession");
?>
<p>To make MolProbity more convenient, you can bookmark this page and return to it later.
We will do our best to preserve all your files, but the unexpected does sometimes happen --
so we recommend that you
<a href='<?php echo makeEventURL('onNavBarGoto', 'file_browser.php'); ?>'>download</a>
anything really important.
</p>

<p>If you're not going to use these files anymore, please
<a href='<?php echo makeEventURL('onNavBarGoto', 'logout.php'); ?>'>log out</a>
instead.
We appreciate your help in freeing up disk space for other users.
</p>

<center><p>Your data will be kept until:
<br><b><?php echo formatDayTime( time() + mpSessTimeToLive(session_id()) ); ?></b>
</p></center>
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
