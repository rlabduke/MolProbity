<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Displays the choices made by SSWING for review by the user.
    
INPUTS (via $_SESSION['bgjob']):
    newModel        the ID of the model just added

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Review SSWING changes");
echo "<a href='files_tab.php?$_SESSION[sessTag]'>Done</a>";
echo "<p><pre>";
print_r($_SESSION['bgjob']['all_changes']);
echo "</pre></p>";
############################################################################
?>
<?php echo mpPageFooter(); ?>
