<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file displays all the files currently available in the user's session.
    
INPUTS (via Get or Post):
    target          either 'model' or 'session'
    model           the model ID, if target == 'model'

OUTPUTS (via Post):
    paramName       description of parameter

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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
$target = $_REQUEST['target'];
$modelID = $_REQUEST['model'];

if($target == 'model')
{
    $file = makeZipForModel($modelID);
    $url = "$_SESSION[dataURL]/$file";
    mpLog("archive-model:Archive file made of model $modelID");
}
elseif($target == 'session')
{
    $file = makeZipForSession();
    $url = "$_SESSION[dataURL]/$file";
    mpLog("archive-session:Archive file made for entire session");
}
else
{
    die("Unknown TARGET parameter.");
}
    
    
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Archive download", "files", "2; URL=$url");
############################################################################
?>

<p>Your download should begin automatically in a few moments.
If nothing happens, you can click to <a href='<?php echo $url; ?>'>download it manually</a>.</p>

<p><small>Unpack a ZIP file on Windows XP or Mac OS X by double-clicking.
On Linux, use the <code>unzip</code> command.</small></p>

<?php
echo "<p><a href='files_tab.php?$_SESSION[sessTag]'>Return to file manager</a></p>\n";
echo mpPageFooter();
?>
