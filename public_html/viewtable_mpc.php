<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page to view tables encoded as PHP arrays.

INPUTS (via Get or Post):
    file            absolute path of the file to load
    
  King Bradley

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/horizontal_chart_func.php');
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/sortable_table.php');
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

// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Viewing $name");
?>
<form>
<table border='0' width='100%'><tr>
<td align='left'><small>
    When finished, you should 
    <input type="button" value="close this window"
    language="JavaScript" onclick="self.close();">.
</small></td><td align='right'><small><i>
    Hint: Use File | Save As... to save a copy of this page.
</i></small></td>
</tr></table>
</form>
<hr>
<?php
$in = fopen($file, 'rb');
clearstatcache();
$data = fread($in, filesize($file));
$table = mpUnserialize($data);
fclose($in);

echo $table['prequel'];
echo "\n\n<h3><center>A diamond indicates that an outlier exists.</center></h3>\n\n";
$url_file = substr($file, strripos($file, "/")+1);
// song and dance to get the proper URL for the frame
// there is probably a better way to do this but I am unaware
$url = linkAnyFile($url_file, "horizontal chart frame");
$url = substr($url, strpos($url, "href"), strripos($url, "target")-strlen($url));
$url = substr($url, strpos($url, "'")+1, strripos($url, "'")-strlen($url));
$url = str_replace("viewtable.php", "horizontal_frame.php", $url);
echo "\n\n".get_horiz_frame($url);

echo "<center><a href='$url&table=t' target='_blank'>Open chart in new window(tab)</a></center>";

  // Debug version:
  //echo "<pre>";
  //print_r($table);
  //echo "</pre>\n";

echo mpPageFooter();
?>
