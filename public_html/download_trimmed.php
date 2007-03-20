<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Helper script to download a PDB file trimmed of its H.

INPUTS (via Get or Post):
    file            absolute path of the file to load

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
if(!$file || !startsWith($file, realpath($_SESSION['dataDir'])))
{
    mpLog("security:Attempt to access '$file' as '$_REQUEST[file]'");
    die("Security failure: illegal file request '$_REQUEST[file]'");
}
$tmp = mpTempfile('tmp_pdb_trim_');
reduceTrim($file, $tmp);
$name = basename($file);

### FUNKY: This turns into a binary file download rather than an HTML page,
### and then calls die(), leaving the user on the original HTML page.

// These lines may be required by Internet Explorer
header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
// See PHP manual on header() for how this works.
header('Content-Type: application/octet-stream');
header('Content-Length: '.filesize($tmp));
header('Content-Disposition: attachment; filename="'.$name.'"');
mpReadfile($tmp);
unlink($tmp);
// Don't output the HTML version of this page into that nice file,
// and don't wipe out the event links from the previous page.
die();

?>
