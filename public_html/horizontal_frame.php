<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page to view tables encoded as PHP arrays.

INPUTS (via Get or Post):
    file            absolute path of the file to load

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/horizontal_table.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
// Security check on filename
$file = realpath($_REQUEST['file']);
if(!$file || !startsWith($file, realpath($_SESSION['dataDir'])))
{
    mpLog("security:Attempt to access '$file' as '$_REQUEST[file]'");
    die("Security failure: illegal file request '$_REQUEST[file]'");
}
$name = basename($file);
?>
<html>
<body>
<?php
echo get_hover_javascript();
$in = fopen($file, 'rb');
clearstatcache();
$data = fread($in, filesize($file));
$table = mpUnserialize($data);
fclose($in);
    if(isset($_REQUEST['table'])) 
        echo formatHorizontalTable($table['horiz_table']);
    if(isset($_REQUEST['key'])) 
        echo formatHorizontalKeys($table['horiz_table']);
?>
