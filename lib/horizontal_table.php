<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Formats horizontal tables stored as nested PHP arrays.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/core.php');

#{{{ get_hover_javascript - convert PHP array into HTML string
############################################################################
/**
*/
function get_hover_javascript()
{
    $str = <<<'EOD'
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
      cY = cY-75;
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

EOD;
   return $str;
}
#}}}

#{{{ getTableTD - convert PHP array into HTML string
############################################################################
/**
*/
function getTableTD($type, $resid, $html, $img=FALSE)
{
    $add = "";
    if($type == "sc Density" || $type == "Rotamer" || $type == "Bond angle")
        $add = ";border-bottom:2px solid #000000;";
    elseif($type == "AA Type") $add = ";text-align:center;";
    if($img) 
    {
        $a_i = "img src = 'img/$img' width=15px";
        $a_i_end = "";
    }
    else
    {
        $a_i = "a";
        $a_i_end = "$html</a>";
    }
    $s = "        <td style='height:22.5px$add'><$a_i onmouseover=";
    $s .= "\"ShowContent('$resid:$type'); return true;\" onmouseout=";
    $s .= "\"HideContent('$resid:$type'); return true;\">$a_i_end\n";
    $s .= "            <div id=\"$resid:$type\" class='comment'";
    $s .= " style=\"width:250; position:absolute; display:none; ";
    $s .= "background-color: #cccc99; border-style:solid; border-width:1px; ";
    $s .= "padding: 5px;\"><center><b><u>$type</u></b><br>Residue:$resid";
    if($type != "AA Type") $s .= "<br>$html";
    $s .= "</center>\n            </div>\n        </td>\n";
    return $s;
}
#}}}

#{{{ formatHorizontalKeys - convert PHP array into HTML string
############################################################################
/**
* $table        the data structure
*/
function formatHorizontalKeys($table)
{
    $s = "<table frame=void rules=all width = \"100%\">\n";
    $i = 0;
    foreach($table as $type => $residues)
    {
        $add = "";
        if($type == "sc Density" || $type == "Rotamer" || $type == "Bond angle") 
            $add = ";border-bottom:2px solid #000000;";
        $num_str_count = getNumStrCount($residues);
        if($i % 2 == 0)
            $s .= "  <tr style=\"background-color:#F2F2F2\"><td style='height:22.5px$add'>$type</td></tr>\n";
        else
            $s .= "  <tr><td style='height:22.5px$add'>$type</td></tr>\n";
        $i++;
    }
    // add aa and num
    $num_height = $num_str_count * 22.5;
    if($i % 2 == 0)
    {
        $s .= "  <tr style=\"background-color:#F2F2F2\"><td style='height:22.5px'>AA Type</td></tr>\n";
        $s .= "  <tr><td style='height:".$num_height."px'>Residue #</td></tr>\n";
    }
    else
    {
        $s .= "  <tr ><td style='height:22.5px'>AA Type</td></tr>\n";
        $s .= "  <tr style=\"background-color:#F2F2F2;height:".$num_height."px\"><td>Residue #</td></tr>\n";
    }
    $s .= "</table>\n";
    return $s;
}
#}}}

#{{{ splitNum - split number
############################################################################
/**
* $resid        residue id -- ' A  10 GLY'
*/
function splitNum($resid)
{
    $ss = substr($resid, 2, 5);
    $ss = trim($ss);
    $s = $ss[0];
    for($i = 1; $i < strlen($ss); $i++)
        $s .= "<br />".$ss[$i];
    return $s;
}
#}}}

#{{{ getNumStrCount
############################################################################
/**
* $residues        residues, wherer the key is the res id -- 'A  10 GLY'
*/
function getNumStrCount($residues)
{
    $max = 0;
    foreach($residues as $resid => $info)
    {
        $ss = substr($resid, 1, 5);
        $ss = trim($ss);
        if(strlen($ss) > $max) $max = strlen($ss);
    }
    return $max;
}
#}}}

#{{{ getAaType - get one letter residue type
############################################################################
/**
* $resid        residue id -- 'A  10 GLY'
*/
function getAaType($resid)
{
    $aas = array('ALA' => 'A',
                 'CYS' => 'C',
                 'ASP' => 'D',
                 'GLU' => 'E',
                 'PHE' => 'F',
                 'GLY' => 'G',
                 'HIS' => 'H',
                 'ILE' => 'I',
                 'LYS' => 'K',
                 'LEU' => 'L',
                 'MET' => 'M',
                 'ASN' => 'N',
                 'PRO' => 'P',
                 'GLN' => 'Q',
                 'ARG' => 'R',
                 'SER' => 'S',
                 'THR' => 'T',
                 'VAL' => 'V',
                 'TRP' => 'W',
                 'TYR' => 'Y',
                 'MSE' => 'M');
    $aa = substr($resid, -3);
    if(isset($aas[$aa])) return $aas[$aa];
    else return '?';
}
#}}}

#{{{ formatHorizontalTable - convert PHP array into HTML string
############################################################################
/**
* $table        the data structure
*/
function formatHorizontalTable($table)
{
    $s = "<table frame=void rules=all width = \"100%\">\n";
    $i = 0;
    foreach($table as $type => $residues)
    {
        $num_str_count = getNumStrCount($residues);
        if($i % 2 == 0)
            $s .= "  <tr style=\"background-color:#F2F2F2\">\n";
        else
            $s .= "  <tr>\n";
        if($i == 0) 
        {
            $nums = "  <tr*REPLACE*>\n";
            $aa_type = "  <tr*REPLACE*>\n";
        }
        foreach($residues as $resid => $info)
        {
            if($i == 0) 
            {
                $num_height = $num_str_count * 22.5;
                $num = splitNum($resid);
                $nums .= "       <td style='vertical-align:text-top;height:".$num_height."px;text-align:center'>$num</td>\n";
                $aa = getAaType($resid);
                $aa_type .= getTableTD("AA Type", $resid, $aa);
                // $aa_type .= "       <td style='height:22.5px;text-align:center'>$aa</td>\n";
            }
            $s .= getTableTD($type, $resid, $info['html'], $info['image']);
        }
        if($i == 0) 
        {
            $nums .= "  </tr>\n";
            $aa_type .= "  </tr>\n";
        }
        $s .= "  </tr>\n";
        $i++;
    }
    if($i % 2 != 0)
    {
        $nums = str_replace("*REPLACE*", " style=\"background-color:#F2F2F2\"", $nums);
        $aa_type = str_replace("*REPLACE*", "", $aa_type);
    }
    else
    {
        $nums = str_replace("*REPLACE*", "", $nums);
        $aa_type = str_replace("*REPLACE*", " style=\"background-color:#F2F2F2\"", $aa_type);
    }
    $s .= "$aa_type$nums</table>\n";
    return $s;
}
#}}}

#{{{ get_horiz_frame
function get_horiz_frame($url)
{
  $s = "<html>\n<body>\n<table style=\"width:100%\">\n  ";
  $s .= "<tr><td style=\"width:125px\">\n";
  $s .= "         <iframe src=$url&key=t width='100%' height='300' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n";
  $s .= "      </td>\n      <td>\n";
  $s .= "         <iframe src=$url&table=t width='100%' height='300' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n</td></tr></table>\n</body>\n</html>";
  return $s;
}
#}}}

