<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to bookmark their session or clean up their files.
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

    echo mpPageHeader("Save session", "savesession");
?>

<p>To make MolProbity more convenient, you can bookmark this page and return to it later.
We will do our best to preserve all your files, but the unexpected does sometimes happen --
so we recommend that you
<a href='files_tab.php?<?php echo $_SESSION['sessTag']; ?>'>download</a>
anything really important.
</p>

<p>If you're not going to use these files anymore, please
<a href='finish_tab.php?<?php echo $_SESSION['sessTag']; ?>'>log out</a>
instead.
We appreciate your help in freeing up disk space for other users.
</p>

<center><p>Your data will be kept until:
<br><b><?php echo formatDayTime( time() + mpSessTimeToLive(session_id()) ); ?></b>
</p></center>

<?php echo mpPageFooter(); ?>
