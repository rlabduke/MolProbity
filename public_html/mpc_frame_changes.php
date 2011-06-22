<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Bradley's horizontal view of the multi-criterion chart.
    This is the frame.

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
?>
<html>
<body>
<script type="text/javascript" language="JavaScript"><!-- 
//{{{javascript controlling hover divS
var cX = 0; var cY = 0; var rX = 0; var rY = 0; var clX = 0; var clY = 0;

function UpdateCursorPosition(e){ cX = e.pageX; cY = e.pageY; clX = e.clientX; clY = e.clientY;}
function UpdateCursorPositionDocAll(e){ cX = event.clientX; cY = event.clientY;}

if(document.all) { document.onmousemove = UpdateCursorPositionDocAll; }
else { document.onmousemove = UpdateCursorPosition; }

function AssignPosition(d) {
  if(self.pageYOffset) {
    rX = self.pageXOffset;
    rY = self.pageYOffset;
    }
  else if(document.documentElement && document.documentElement.scrollTop) {
      rX = document.documentElement.scrollLeft;
      rY = document.documentElement.scrollTop;
    }
  else if(document.body) {
      rX = document.body.scrollLeft;
      rY = document.body.scrollTop;
    }
  if(document.all) {
      cX += rX; 
      cY += rY;
    }
  if(clX > 580) {
      cX = cX-275;
    }
  if(clY > 90) {
      cY = cY-90;
    }
  d.style.left = (cX+10) + "px";
  d.style.top = (cY+10) + "px";
}
function HideContent(d) {
  if(d.length < 1) { return; }
  document.getElementById(d).style.display = "none";
}

function ShowContent(d) {
  if(d.length < 1) { return; }
  var dd = document.getElementById(d);
  AssignPosition(dd);
  dd.style.display = "block";
}

function ReverseContentDisplay(d) {
  if(d.length < 1) { return; }
  var dd = document.getElementById(d);
  AssignPosition(dd);
  if(dd.style.display == "none") { dd.style.display = "block"; }
  else { dd.style.display = "none"; }
}
//}}}
//--></script>
<?php
// $rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
// if(!file_exists($rawDir)) return "ERROR!!!";
// $file = $_SESSION['mpc']['mpc_table_name'];
$in = fopen($file, 'rb');
clearstatcache();
$data = fread($in, filesize($file));
$mpc_tablechanges = mpUnserialize($data);
fclose($in);
$improved_num = $_REQUEST['improved_num'];
$original_num = $_REQUEST['original_num'];
$diff_table = calculate_mpc_differences($table_mpcscores = $mpc_tablechanges,
  $improved_model = $_REQUEST["model$improved_num"],
  $original_model = $_REQUEST["model$original_num"]);
$overall_html = get_changes_overall($diff_table);
$diff_html = get_changes_html($diff_table);
$frame = get_mpc_changes_chart($mpc_html = $diff_html, 
  $improved_model = $_REQUEST["model$improved_num"],
  $original_model = $_REQUEST["model$original_num"]);
// echo "<pre>";
// print_r($diff_table);
// echo "</pre>";
// this page is the frame containing the horizontal multi_chart;
// if 'table=x' is in the URL then the chart is displayed, if 'key=x' is in the
// URL then the key for the chart is displayed.
if(isset($_REQUEST['key']))
  echo $frame['key'];
if(isset($_REQUEST['table']))
  echo $frame['table'];
if(isset($_REQUEST['scores']))
  echo $overall_html;
?>
</body>
</html>
