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
echo mpPageHeader("Viewing MPC changes");
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
$improved_num = $_REQUEST['improved_num'];
$original_num = $_REQUEST['original_num'];
$modelID_1 = $_REQUEST['model1'];
$modelID_2 = $_REQUEST['model2'];
$modelIDs = array(1 => $modelID_1, 2 => $modelID_2);
// get and change num values for the 'Change improved/original' link
if($improved_num == 1 & $original_num == 2) {
  $new_impr_num = 2;
  $new_orig_num = 1;
} elseif($improved_num == 2 & $original_num == 1) {
  $new_impr_num = 1;
  $new_orig_num = 2;
}
$param_change = array("improved_num" => $new_impr_num,
  "original_num" => $new_orig_num);
$switch_url = add_or_change_parameter($param_change);
$switch = "<table><tr><td><img src='img/switch.png' height='25px'></td>";
$switch .= "<td valign='middle'>Switch!</td></tr></table>";
$switch = "<img src='img/switch.png' height='25px'>";
$switch_link = "<a href='$switch_url'>$switch</a>";

// show key to canges frame
echo "<center>\n<table frame='void' rules='rows'>\n";
echo "  <tr><td>Improved Model:</td>\n";
echo "      <td>$modelIDs[$improved_num]</td>";
echo "      <td rowspan='2' valign='middle'>$switch_link</td></tr>\n";
echo "  <tr><td>Original Model:</td>\n";
echo "      <td>$modelIDs[$original_num]</td></tr>\n";
echo "  <tr><td>Outlier Eliminated:</td>\n";
echo "      <td colspan='2'><center><img src='img/change_green.png' width='25px'></center></td></tr>\n";
echo "  <tr><td>Outlier Severity Decreased:</td>\n";
echo "      <td colspan='2'><center><img src='img/change_yellow.png' width='25px'></center></td></tr>\n";
echo "  <tr><td>Outlier Introduced:</td>\n";
echo "      <td colspan='2'><center><img src='img/change_red.png' width='25px'></center></td></tr>\n";
echo "  <tr><td>Outlier Severity Increased:</td>\n";
echo "      <td colspan='2'><center><img src='img/change_salmon.png' width='25px'></center></td></tr>\n";
echo "</table>\n";
$rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
if(!file_exists($rawDir)) return "ERROR: cannot find raw data directory.";
$mpc_table_name = $_SESSION['mpc']['mpc_table_name'];
$url = "mpc_frame_changes.php?$_SESSION[sessTag]&file=$rawDir/$mpc_table_name";
$url .= "-table.mpcscores&model1=$modelID_1&model2=$modelID_2";
$url .= "&improved_num=$improved_num&original_num=$original_num";
echo get_mpc_frame_changes($url);
echo "<center><a href='$url&table=t' target='_blank'>Open chart in new window";
echo "(tab)</a></center>";
echo mpPageFooter();
?>
