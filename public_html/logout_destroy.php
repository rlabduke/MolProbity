<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page destroys all user data for the current session.
    
INPUTS (via Post ONLY):
    confirm       must be TRUE in order for the operation to proceed

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

# MAIN - the beginning of execution for this page
############################################################################
if($_POST['confirm'])
{
    mpDestroySession();
    mpLog("logout-session:User cleaned up all session files and left the site");
}

// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Thanks!");

############################################################################
?>

Thanks for using MolProbity!

<p><a href="index.php">Start over</a>

<?php echo mpPageFooter(); ?>
