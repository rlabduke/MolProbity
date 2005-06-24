<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to send email about bugs/features in MolProbity.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class feedback_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Feedback &amp; bugs", "feedback");
    echo makeEventForm("onFeedbackSend");
?>
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
        <option selected value="Bug report">a bug or error in MolProbity</option>
        <option value="Suggestion">a suggestion on how to improve MolProbity</option>
        <option value="KiNG">the KiNG graphics applet</option>
        <option value="Tutorial">the online tutorial</option>
        <option value="Documentation">the other online documentation</option>
        <option value="Local server setup">installing or configuring a local copy of MolProbity</option>
        <option value="General feedback">(none of the above)</option>
    </select></td>
</tr><tr>
    <td align='left' colspan='2'>
        <textarea name='feedbackText' rows='15' cols='76'><?php echo $this->makeTemplateText(); ?></textarea>
    </td>
</tr><tr>
    <td align='left'><input type='submit' name='cmd' value='Send email &gt;'></td>
    <td align='right'><input type='reset' name='cmd' value='Reset'></td>
</tr>
</table>
</form>
<?php
    echo mpPageFooter();
}
#}}}########################################################################

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
            . "User IP       : $_SESSION[userIP]\n"
            . "User browser  : $_SERVER[HTTP_USER_AGENT]\n"
            . "Session ID    : " . session_id() . "\n"
            . "Session URL   : http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?$_SESSION[sessTag]\n"
            . "\n"
            . "MolProb. ver. : " . MP_VERSION . "\n"
            . "Server name   : $_SERVER[SERVER_NAME]\n"
            . "Server time   : " . date("Y M d    H:i:s") . "\n"
            . "\n"
            . "==================================================\n"
            . "  ERRORS FROM THIS SESSION\n"
            . "==================================================\n";
    
    $errfile = $_SESSION['dataDir']."/".MP_DIR_SYSTEM."/errors";
    if(file_exists($errfile))
    {
        $a = file($errfile);
        foreach($a as $s) { $fb_msg .= $s; }
    }

    //return wordwrap($fb_msg, 76); -- not needed; we wordwrap before sending
    return $fb_msg;

}
#}}}########################################################################

#{{{ onFeedbackSend
############################################################################
/**
* Documentation for this function.
*/
function onFeedbackSend($arg, $req)
{
    $inregardto  = $req['inRegardTo'];
    $sendername  = $req['senderName'];
    $senderemail = $req['senderEmail'];
    
    $fb_to = MP_EMAIL_AUTHOR.",".MP_EMAIL_WEBMASTER;
    $fb_subj = "MolProbity feedback: $inregardto";
    $fb_hdrs = "From: " . MP_EMAIL_WEBMASTER
        . "\nReply-To: $sendername<$senderemail>"
        . "\nX-Mailer: PHP_MolProbity3"
        . "\nReturn-Path: " . MP_EMAIL_WEBMASTER;
    
    $fb_msg = "\n"
        . "User name     : $req[senderName]\n"
        . "User email    : $req[senderEmail]\n"
        . "\n\n"
        . wordwrap($req['feedbackText'], 76);
    
    // Write a local copy of the email in case sendmail isn't working
    $msg_text = "To: $fb_to\n$fb_hdrs\nSubject: $fb_subj\n\n$fb_msg\n";
    $tmpfile = tempnam(MP_BASE_DIR.'/feedback', 'email_');
    chmod($tmpfile, 0666 & ~MP_UMASK); // tempnam gets wrong permissions sometimes?
    $h = fopen($tmpfile, 'wb');
    fwrite($h, $msg_text);
    fclose($h);
    
    // Mail messages MUST use \r\n for new lines!!
    //$fb_to      = str_replace("\n", "\r\n", $fb_to);      Cannot have newlines
    //$fb_subj    = str_replace("\n", "\r\n", $fb_subj);    Cannot have newlines
    $fb_to      = trim($fb_to);
    $fb_subj    = trim($fb_subj);
    $rn_hdrs    = str_replace("\n", "\r\n", $fb_hdrs);
    $rn_msg     = str_replace("\n", "\r\n", $fb_msg);
    
    $ok = mail($fb_to, $fb_subj, $rn_msg, $rn_hdrs);
    mpLog("feedback:Sent to $fb_to with subject $inregardto; success=$ok");
    
    pageGoto("feedback_done.php", $msg_text);
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
