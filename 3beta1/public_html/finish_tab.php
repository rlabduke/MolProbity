<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to clean up their files.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

    echo mpPageHeader("Finish session", "logout");
?>

<p>We appreciate your help in freeing up disk space for other users.
By clicking the button below, you will <b>permanently delete</b> all the files you generated during this session.
Before logging out, you may wish to
<a href='files_tab.php?<?php echo $_SESSION['sessTag']; ?>'>download</a>
some of your files.
It is also possible to 
<a href='savesess_tab.php?<?php echo $_SESSION['sessTag']; ?>'>save this session</a>
and return to do more work with these files later.

<p><form method="post" action="finish_destroy.php">
<?php echo postSessionID(); ?>
<input type="hidden" name="confirm" value="1">
<br>This action cannot be undone:
<input type="submit" name="cmd" value="Destroy all my files and log me out">
</form>

<?php echo mpPageFooter(); ?>
