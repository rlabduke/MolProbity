<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to compose an email to the MolProbity author.
    
INPUTS (via Get or Post):
    senderName      The sender's real name
    senderEmail     The sender's email address, for replies
    inRegardTo      A hint about the subject of the email
    feedbackText    Body of the email

OUTPUTS (via Post):

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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
$inregardto  = $_REQUEST['inRegardTo'];
$sendername  = $_REQUEST['senderName'];
$senderemail = $_REQUEST['senderEmail'];

$fb_to = MP_EMAIL_AUTHOR.",".MP_EMAIL_WEBMASTER;
$fb_subj = "MolProbity feedback: $inregardto";
$fb_hdrs = "From: " . MP_EMAIL_WEBMASTER
    . "\nReply-To: $sendername<$senderemail>"
    . "\nX-Mailer: PHP_MolProbity3"
    . "\nReturn-Path: " . MP_EMAIL_WEBMASTER;

$fb_msg = "\n"
    . "User name     : $_REQUEST[senderName]\n"
    . "User email    : $_REQUEST[senderEmail]\n"
    . "\n\n"
    . wordwrap($_REQUEST['feedbackText'], 76);

$ok = mail($fb_to, $fb_subj, $fb_msg, $fb_hdrs);
mpLog("feedback:Sent to $fb_to with subject $inregardto; success=$ok");

if($ok)
{
    // Start the page: produces <HTML>, <HEAD>, <BODY> tags
    echo mpPageHeader("Email sent", "feedback");
    echo "<p>Your email was successfully sent to the author(s) and maintainer(s) of MolProbity.\n";
    echo "You should receive a response, if needed, within a few days.</p>\n";
}
else
{
    // Start the page: produces <HTML>, <HEAD>, <BODY> tags
    echo mpPageHeader("Email not sent", "feedback");
    echo "<p><div class='alert'>It appears that our email system is not functioning properly.\n";
    echo "Your message could not be sent.\n";
    echo "You can email the author directly at <a href='mailto:".MP_EMAIL_AUTHOR."'>".MP_EMAIL_AUTHOR."</a>.</div></p>\n";
}

echo "<hr><p><pre>\n";
echo "To: $fb_to\n";
echo htmlspecialchars($fb_hdrs)."\n";
echo "Subject: $fb_subj\n\n";
echo htmlspecialchars($fb_msg)."\n";
echo "</pre></p>\n";

############################################################################
?>

<?php echo mpPageFooter(); ?>
