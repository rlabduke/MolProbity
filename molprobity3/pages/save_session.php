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
    // FUNKY: this breaks the general rule of display() not modifying session data.
    // Set session lifetime to a longer value.
    // Will be overwritten each session restart (i.e. when we leave this page).
    mpSessSetTTL(session_id(), MP_SESSION_LIFETIME_EXT);
    
    
    echo $this->pageHeader("Save session", "savesession");
?>
<p>To make MolProbity more convenient, you can bookmark this page and return to it later.
We will do our best to preserve all your files, but the unexpected does sometimes happen --
so we recommend that you
<a href='<?php echo makeEventURL('onGoto', 'file_browser.php'); ?>'>download</a>
anything really important.
</p>

<p>If you're not going to use these files anymore, please
<a href='<?php echo makeEventURL('onGoto', 'logout.php'); ?>'>log out</a>
instead.
We appreciate your help in freeing up disk space for other users.
</p>

<center><p>Your data will be kept until:
<br><b><?php echo formatDayTime( time() + mpSessTimeToLive(session_id()) ); ?></b>
</p></center>
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
