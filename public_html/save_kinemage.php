<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Allows KiNG to write a kinemage to the session using POST.

INPUTS (via Get or Post):
    fileName        the (proposed) file name to save under
    fileContents    the contents of the file

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
    mpSessReadOnly(); // could occur while a background job is running...

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################

if(!isset($_REQUEST['fileName']) || !isset($_REQUEST['fileContents']))
    die("This page for file uploads only");

// First make sure this is a legal file name with no weird chars, ending in .kin
$outname = censorFileName($_REQUEST['fileName']);
if(!endsWith($outname, ".kin")) $outname .= ".kin";
// Now make sure we have a directory to put it in
$outpath = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
if(!file_exists($outpath)) mkdir($outpath, 0777);
// Finally, make sure we don't overwrite anything already in existance!
while(file_exists("$outpath/$outname".$serial)) $serial++;
$outpath = "$outpath/$outname".$serial;

$h = fopen($outpath, 'wb');
fwrite($h, $_REQUEST['fileContents']);
fclose($h);

$size = strlen($_REQUEST['fileContents']);
echo("OK name=$outpath; size=$size\n"); // msg for KiNG
mpLog("savekin:User saved a modified kinemage under $outpath; size=$size");
?>
