<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Defines string-handling and common formatting functions.
*****************************************************************************/

#{{{ mpSendEmail - sends an email msg by either SMTP or sendmail
############################################################################
/**
* Returns true on success, false on failure.
*/
function mpSendEmail($senderName, $senderAddr, $subject, $body)
{
    if(defined("MP_SMTP_SERVER"))
        return mpSendEmailViaSmtp($senderName, $senderAddr, $subject, $body);
    else
        return mpSendEmailViaMail($senderName, $senderAddr, $subject, $body);
}
#}}}########################################################################

#{{{ mpSendEmailViaSmtp
############################################################################
function mpSendEmailViaSmtp($senderName, $senderAddr, $subject, $body)
{
    require_once(MP_BASE_DIR.'/lib/class.phpmailer.php');
    $mail = new PHPMailer();
    $mail->PluginDir = MP_BASE_DIR.'/lib/'; // to find SMTP class; needs trailing slash
    
    $mail->IsSMTP();
    $mail->Host = MP_SMTP_SERVER;
    
    if(defined("MP_SMTP_USER"))
    {
        $mail->From = MP_SMTP_USER;
        $mail->FromName = "MolProbity3"; // otherwise this says "Root User"
    }
    $mail->AddReplyTo($senderAddr, $senderName);
    $mail->AddAddress(MP_EMAIL_AUTHOR);
    $mail->AddAddress(MP_EMAIL_WEBMASTER);
    //if(defined("MP_EMAIL_WEBMASTER2")) {
      $mail->AddAddress(MP_EMAIL_WEBMASTER2);
    //}
    
    $mail->Subject = $subject;
    $mail->Body = $body;
    //$mail->WordWrap = 72;
    
    return $mail->Send();
}
#}}}########################################################################

#{{{ mpSendEmailViaMail
############################################################################
function mpSendEmailViaMail($senderName, $senderAddr, $subject, $body)
{
    $fb_to = MP_EMAIL_AUTHOR.",".MP_EMAIL_WEBMASTER.",".MP_EMAIL_WEBMASTER2;
    $fb_hdrs = "From: " . MP_EMAIL_WEBMASTER
        . "\nReply-To: $senderName<$senderAddr>"
        . "\nX-Mailer: PHP_MolProbity3"
        . "\nReturn-Path: " . MP_EMAIL_WEBMASTER;
    
    // Mail messages MUST use \r\n for new lines!!
    //$fb_to      = str_replace("\n", "\r\n", $fb_to);      Cannot have newlines
    //$fb_subj    = str_replace("\n", "\r\n", $fb_subj);    Cannot have newlines
    $fb_to      = trim($fb_to);
    $fb_subj    = trim($subject);
    $rn_hdrs    = str_replace("\n", "\r\n", $fb_hdrs);
    $rn_msg     = str_replace("\n", "\r\n", $body);
    
    return mail($fb_to, $fb_subj, $rn_msg, $rn_hdrs);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

?>
