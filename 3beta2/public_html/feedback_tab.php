<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to compose an email to the MolProbity author.
    
INPUTS (via Get or Post):

OUTPUTS (via Post):
    senderName      The sender's real name
    senderEmail     The sender's email address, for replies
    inRegardTo      A hint about the subject of the email
    feedbackText    Body of the email

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ makeTemplateText - fills out some user info for email
############################################################################
/**
* Documentation for this function.
*/
function makeTemplateText()
{
    $fb_msg = "\n"
            . "\n"
            . "[PLACE YOUR COMMENTS HERE]\n"
            . "\n"
            . "==================================================\n"
            . "  USER / SERVER INFORMATION\n"
            . "==================================================\n"
            . "User IP       : " . getVisitorIP() . "\n"
            . "User browser  : $_SERVER[HTTP_USER_AGENT]\n"
            . "Session ID    : " . session_id() . "\n"
            . "\n"
            . "MolProb. ver. : " . MP_VERSION . "\n"
            . "Server name   : $_SERVER[SERVER_NAME]\n"
            . "Server time   : " . date("Y M d    H:i:s") . "\n"
            . "\n"
            . "==================================================\n"
            . "  ERRORS FROM THIS SESSION\n"
            . "==================================================\n";
    
    $errfile = $_SESSION['dataDir'] . "/errors";
    if(file_exists($errfile))
    {
        $a = file($errfile);
        foreach($a as $s) { $fb_msg .= $s; }
    }

    return wordwrap($fb_msg, 76);

}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Send email about MolProbity", "feedback");

############################################################################
?>

<p>
<form method='post' action='feedback_send.php'>
<?php echo postSessionID(); ?>
<table border='0' cellspacing='0'>
<tr>
    <td align='right'>My name is</td>
    <td align='left'><input type='text' name='senderName' value='' size='25' maxlength='100'></td>
</tr><tr>
    <td align='right'>My email address is</td>
    <td align='left'><input type='text' name='senderEmail' value='' size='25' maxlength='100'></td>
</tr><tr>
    <td align='right'>My comment regards</td>
    <td align='left'><select name='inRegardTo'>
        <option selected value="Bug Report">a bug or error in MolProbity</option>
        <option value="KiNG">the KiNG applet</option>
        <option value="JavaMage">the JavaMage applet</option>
        <option value="Tutorial">the online tutorial</option>
        <option value="User Manual">the other online documentation</option>
        <option value="Suggestion">a suggestion on how to improve MolProbity</option>
        <option value="General Feedback">(none of the above)</option>
    </select></td>
</tr><tr>
    <td align='left' colspan='2'>
        <textarea name='feedbackText' rows='15' cols='76'><?php echo makeTemplateText(); ?></textarea>
    </td>
</tr><tr>
    <td align='left'><input type='submit' name='cmd' value='Send email'></td>
    <td align='right'><input type='reset' name='cmd' value='Reset'></td>
</tr>
</table>
</form>
</p>

<?php echo mpPageFooter(); ?>
