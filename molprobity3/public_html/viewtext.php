<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page to view text or HTML files

INPUTS (via Get or Post):
    file            absolute path of the file to load
    mode            one of 'plain', 'html', or 'kin'

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
    mpSessReadOnly();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Security check on filename
$file = realpath($_REQUEST['file']);
if(!$file || !startsWith($file, $_SESSION['dataDir']))
{
    mpLog("security:Attempt to access '$file' as '$_REQUEST[file]'");
    die("Security failure: illegal file request '$_REQUEST[file]'");
}
$name = basename($file);

// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Viewing $name");
?>
<form>
<table border='0' width='100%'><tr>
<td align='left'><small>
    When finished, you should close this window:
    <input type="button" value="Close"
    language="JavaScript" onclick="self.close();">
</small></td><td align='right'><small><i>
    Hint: Use File | Save As... to save a copy of this page.
</i></small></td>
</tr></table>
</form>
<hr>
<?php
$mode = $_REQUEST['mode'];
if($mode == 'kin')
{
    passthru("java -cp ".MP_BASE_DIR."/public_html/king.jar king.core.KinfileTokenizer -css < $file");
}
elseif($mode == 'html')
{
    readfile($file);
}
else // plain and/or mis-specified (default)
{
    echo "<pre>";
    //readfile($file);
    $h = fopen($file, 'rb');
    while(!feof($h)) echo htmlspecialchars(fgets($h, 4096));
    fclose($h);
    echo "</pre>\n";
}

echo mpPageFooter();
?>
