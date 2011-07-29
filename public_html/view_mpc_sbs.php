<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
  MolProbity Compare
  
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
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Viewing MPC side-by-side");
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
$modelID_1 = $_REQUEST['model1'];
$modelID_2 = $_REQUEST['model2'];
echo "<center>\n<table rules='all' cellpadding='5'>\n";
echo "  <tr><th>Model 1:</th>\n      <td>$modelID_1</td></tr>";
echo "  <tr><th>Model 2:</th>\n      <td>$modelID_2</td></tr>\n</table>\n";
echo "<hr width='40%'>\n";
echo "Model 1 Outlier: <img src='img/mpc_out1.png' width=15px><br>";
echo "Model 2 Outlier: <img src='img/mpc_out2.png' width=15px></center>";
$rawDir = $_SESSION['dataDir'].'/raw_data/';
if(!file_exists($rawDir)) return "ERROR: cannot find raw data directory.";
$file = realpath($_REQUEST['file']);
$url = "mpc_frame_sbs.php?$_SESSION[sessTag]&file=$file";
$url .= "&model1=$modelID_1&model2=$modelID_2";
echo get_mpc_frame_sbs($url);
echo "<center><a href='$url&table=t' target='_blank'>";
echo "Open chart in new window(tab)</a></center>";
echo mpPageFooter();
?>
