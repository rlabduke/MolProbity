<?php # (jEdit options) :folding=explicit:collapseFolds=1:

/*****************************************************************************

  This file contains the functions used in creatinng both the multi-criterion
  horizontal view as well as the MolProbity Compare views and calculations.

*****************************************************************************/

// {{{ get_img_html; returns img html 
/**
* Returns image html to respond to the show/hide onmouseover java script seen 
* in the horizontal chart.
*   $num       connects onmouseover to the correct div
*   $img       the image to be displayed
**/
function get_img_html($img, $num, $txt = false)
{
  if($img === false) {
    $s  = "<a onmouseover=\"ShowContent('$num'); return true;\"";
    $s .= " onmouseout=\"HideContent('$num'); return true;\">$txt</a>\n";
  } else {
    $s  = "<img src = '$img' width=15px onmouseover=\"ShowContent('$num');";
    $s .= " return true;\" onmouseout=\"HideContent('$num'); return true;\">\n";
  }
  return $s;
}
// }}}

// {{{ get_div_html; returns a block of <div> html
/**
* Returns a block of <div> html that respond to the show/hide onmouseover 
* java script seen in the horizontal chart. 
*   $info       is info to be displayed in the div below $res
*   $num        the div id, also has res # info that is parsed out & set to $res
*   $param      bolded and underlined header that goes at the top of the div
**/
function get_div_html($info, $num, $param, $res=false)
{
  if($info == "-" & $param == "Clash") $info = "None!";
  if($res === false) 
    $res = "Residue: ".substr($num, 0, strpos($num, ":")-strlen($num));
  $content = "<center><b><u>$param</u></b><br>$res<br>$info</center>";
  $s = "            <div id=\"$num\" class='comment' style=\"width:250;";
  $s .= " position:absolute; display:none; background-color: #cccc99;";
  $s .= " border-style:solid; border-width:1px; padding: 5px;\">";
  $s .= "$content</div>\n";
  return $s;
}
// }}}

// {{{ vert_to_horiz; returns an array
/**
*  Returns an array where the keys are the headers in a xxxx-multi.table.
*  Values are arrays...OK, its complicated. Let me show you what is returned. 
*  
*  Array
*  (
*      [#] => Array
*          (
*              [A   1] => Array
*                  (
*                      [html] => A   1 
*                      [color] => 
*                  )
*
*  Where '#' is a header in xxxx-multi.table. [html] is the # value for the 
*  first element and if [color] is defined than means 'A   1' has a '#'
*  outlier. Obviously '#' is never an outlier! But 'clash > 0.4Œ' can be.
**/
function vert_to_horiz($table, $keep_HOH = false)
{
  $head_keys;
  # populates $header_array for only first array in $table['headers']
  $is_first = True;
  foreach($table['headers'] as $header) {
    foreach($header as $key => $cell) {
      if ($is_first) {
        $s = str_replace("</b>", "", str_replace("<b>", "", $cell['html']));
        $head_keys[$key] = strtolower($s);
      }
    }
    if ($is_first) $is_first = False;
  }
  $rows_array;
  foreach($table['rows'] as $row) {
    foreach($row as $key => $cell) {
      if ($row[1]['html'] != "HOH")
        $rows_array[$head_keys[$key]][] = array('html' => $cell['html'], 'color' => $cell['color']);
      elseif ($keep_HOH)
        $rows_array[$head_keys[$key]][] = array('html' => $cell['html'], 'color' => $cell['color']);
    }
  }
  foreach($rows_array['#'] as $key => $res_info)
    $key_resinfo[$key] = $res_info['html'];
  foreach($rows_array as $param => $param_array)
    foreach($param_array as $key => $res)
      $rows_array_resinfo[$param][trim($key_resinfo[$key])] = $res;
  return $rows_array_resinfo;
}
// }}}

// {{{ get_mparam_hierarchy; returns an array residue names as keys
/**
*  Returns an array with residue names as keys, these keys point to an array 
*  with molprobity parameter keys, these keys ponit to an array with two keys 
*  'html and 'color. 'html' is the text from the classic multi-criterion chart.
*  If 'color' is not blank than the molprobity parameter for the given residue
*  is an outlier. I.E.:
*    $mparam_hierarchy['A   1']['high b'] 
*      could output:
*    Array(html=>37.89, color=>)
*    
*    $table       from the xxxx-multi.table
*    $modelID     the model ID, i.e. 1ubqFH
**/
function get_mparam_hierarchy($table, $modelID)
{
  $rows_array = vert_to_horiz($table);
  $chain_array = $_SESSION['models'][$modelID]['stats']['chainids'];
  $header_array = array_keys($rows_array);
  foreach($rows_array['#'] as $res_name => $resinfo)#res_name looks like 'A  10'
    foreach($header_array as $header){
      $info_array = $rows_array[$header][$res_name];
      $mparam_hierarchy[$res_name][$header] = $info_array;
    }
  return $mparam_hierarchy;
}
// }}}

// {{{ standard_deviation; returns standard deviation float
function standard_deviation($aValues, $bSample = false)
{
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
    foreach ($aValues as $i) {
        $fVariance += pow($i - $fMean, 2);
    }
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    return (float) sqrt($fVariance);
}
// }}}

// {{{ get_avg_percent; returns array 
function get_avg_percent($ar, $percent=10)
/*******************************************************************************
*
*  Takes the given array, $ar, and takes its values, assumed to be int or float,
*  and finds the average and a percentage value according to the given $percent.
*  Returns an arrary with keys 'average', 'stddev, and 'percent_value'.
*  percent_value is a value in the array where $percent% of the data is above
*  that value.
*
*******************************************************************************/
{
  $sum = 0;
  foreach ($ar as $v) $sum += $v;
  $return_array = array('average' => round($sum/count($ar), 3));
  $a = array_values($ar);
  sort($a);
  $return_array['percent_value'] = $a[round(count($ar) - $percent*0.01*count($ar))];
  $return_array['stddev'] = round(standard_deviation($a), 3);
  // return $a;
  return $return_array;
}
// }}}

// {{{ remove_waters_from_multi
function remove_waters_from_multi($table)
{
  
}
// }}}

// {{{ get_horizontal_chart
function get_horizontal_chart($table, $b_percent = 10)
{
  $rows_array = vert_to_horiz($table);
  
  //{{{ Determine outliers START###########################
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E', 
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K', 
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' , 
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W', 
    'TYR' => 'Y');
  # $mp_params is used to populate the HTML horizontal table
  $nums;
  $chains = array();
  if(isset($rows_array["#"])) {
    foreach($rows_array["#"] as $key => $num) {
      $nums[] = $num['html'];
      $ch = substr($num['html'], 0, 1);
      if (!in_array($ch, $chains)) $chains[] = $ch; 
    }
  }
  $mp_params;
  if(isset($rows_array["clash &gt; 0.4&aring;"])) {
    foreach($rows_array["clash &gt; 0.4&aring;"] as $key => $res){
      if($res['color']) {
        $overlap = get_clash_overlap($res['html']);
        // if($overlap < 0.7) $clash_img = 'img/clash_outA.png';
        // elseif($overlap < 1.0) $clash_img = 'img/clash_outB.png';
        // elseif($overlap < 1.3) $clash_img = 'img/clash_outC.png';
        // else $clash_img = 'img/clash_outD.png';
        if($overlap < 0.7) $clash_img = 'img/clash_outA.png';
        // elseif($overlap < 1.0) $clash_img = 'img/clash_outB.png';
        elseif($overlap < 1.0) $clash_img = 'img/clash_outC.png';
        else $clash_img = 'img/clash_outD.png';
        $html = get_img_html($clash_img, $key.":clash");
      } else
        $html = get_img_html('img/no_out.png', $key.":clash");
      $html .= get_div_html($res['html'], $key.":clash", "Clash");
      $mp_params["clash &gt; 0.4&aring;"][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["ramachandran"])) {
    foreach($rows_array["ramachandran"] as $key => $res){
      if($res['color'])
        $html = get_img_html('img/rama_out.png', $key.":rama");
      else
        $html = get_img_html('img/no_out.png', $key.":rama");
      $html .= get_div_html($res['html'], $key.":rama", "Ramachanran");
      $mp_params["ramachandran"][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["rotamer"])) {
    foreach($rows_array["rotamer"] as $key => $res){
      if($res['color'])
        $html = get_img_html('img/rotamer_out.png', $key.":rot");
      else
        $html = get_img_html('img/no_out.png', $key.":rot");
      $html .= get_div_html($res['html'], $key.":rot", "Rotamer");
      $mp_params["rotamer"][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["c&beta; deviation"])) {
    foreach($rows_array["c&beta; deviation"] as $key => $res){
      if($res['color'])
        $html = get_img_html('img/cBd_out.png', $key.":cbd");
      else
        $html = get_img_html('img/no_out.png', $key.":cbd");
      $html .= get_div_html($res['html'], $key.":cbd", "c&beta; Deviation");
      $mp_params["c&beta; deviation"][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["bond angles."])) {
    foreach($rows_array["bond angles."] as $key => $res){
      if($res['color'])
        $html = get_img_html('img/ba_out.png', $key.":ba");
      else
        $html = get_img_html('img/no_out.png', $key.":ba");
      $html .= get_div_html($res['html'], $key.":ba", "Bond Angle");
      $mp_params["bond angles."][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["bond lengths."])) {
    foreach($rows_array["bond lengths."] as $key => $res){
      if($res['color'])
        $html = get_img_html('img/bl_out.png', $key.":bl");
      else
        $html = get_img_html('img/no_out.png', $key.":bl");
      $html .= get_div_html($res['html'], $key.":bl", "Bond Length");
      $mp_params["bond lengths."][$key]['img'] = $html; 
    }
  }
  if(isset($rows_array["#"])) {
    foreach($rows_array["#"] as $key => $num)
      $mp_params["#"][$key]['img'] = $num['html'];
  }
  $HOH_residues;
  if(isset($rows_array["res"])) {
    foreach($rows_array["res"] as $key => $res) {
      if ($res['html'] == "HOH") $HOH_residues[] = $key;
    }
  }
  $high_b;
  if(isset($rows_array["high b"])) {
    foreach($rows_array["high b"] as $key => $b) {
      if (!in_array($key, $HOH_residues)) $high_b[$key] = $b['html'];
    }
    $average_stddev = get_avg_percent($high_b, $b_percent);
    foreach($rows_array["high b"] as $key => $b) {
      // change image
      if ($high_b[$key] > $average_stddev['percent_value'])
        $html = get_img_html('img/hiB_out.png', $key.":hiB");
      else $html = get_img_html('img/no_out.png', $key.":hiB");
      $html .= get_div_html($high_b[$key], $key.":hiB", "High B");
      $mp_params["High $b_percent% B"][$key]['img'] = $html; 
    }
  }
  # turn 3-letter aa code into 1-letter aa code
  if(isset($rows_array["res"])) {
    foreach($rows_array["res"] as $key => $aa) {
      $div_id = $nums[$key].":n";
      $html = "<center><a onmouseover=\"ShowContent('$key:n'); return true;\"";
      $html .= "onmouseout=\"HideContent('$key:n'); return true;\">\n";
      if(array_key_exists($aa['html'], $aa_3_1)) 
        $symb = $aa_3_1[$aa['html']];
      elseif($aa['html'] == "HOH")
        $symb = "<img src = 'img/water.png' width=15px>";
      else $symb = "?";
      $html .= $symb."</a></center>\n";
      $html .= get_div_html("High B: ".$high_b[$key], $key.":n", $aa['html']);
      $mp_params["res"][$key]['img'] = $html;
    }
  }
  
  //}}} Determine outliers END###########################

  
  $s = "<html>\n<body>\n";
  $s .= "\n<table frame=void rules=all width = '100%'>\n";
  $heads;
  foreach($mp_params as $header => $res_num) {
    $s .= "    <tr>\n";
    if($header != "#") {
      $heads[] = $header;
      foreach($res_num as $key => $residue) { 
        $chain = substr($key, 0, 1);
        if (array_search($chain, $chains)%2 and $header == "res") $bgc = "#FFFFCC";
        else $bgc = "#FFFFFF";
        $s .= "        <td style='height:22.5px' bgcolor='$bgc'>".$residue['img']."</td>\n";
      }
    }
  }
  $s .= "    </tr>\n</table>\n";
  $s .= "</body>\n</html>";
  $k = "<html>\n<body>\n";
  $k .= "<table frame=void rules=all width = '100%'>\n";
  foreach($heads as $head) $k .= "  <tr><td style='height:22.5px'>".ucwords($head)."</td></tr>\n";
  $k .= "</table>\n</body>\n</html>";
  $k = str_replace("Res", "Residue", $k);
  $k = str_replace("&aring", "&Aring", $k);
  //return array("table" => $rows_array, "key" => $k);
  return array("table" => $s, "key" => $k, "p" => $chains);
  // return array("table" => $rows_array["res"], "key" => $nums);
}
//}}}

// {{{ get_horiz_frame
function get_horiz_frame($url)
{
  $s = "<html>\n<body>\n<table style=\"width:100%\">\n  ";
  $s .= "<tr><td style=\"width:125px\">\n";
  $s .= "         <iframe src=$url&key=t width='100%' height='250' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n";
  $s .= "      </td>\n      <td>\n";
  $s .= "         <iframe src=$url&table=t width='100%' height='250' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n</td></tr></table>\n</body>\n</html>";
  return $s;
}
// }}}

// {{{ check_chain_choice; return xxxx-multi.table if successful otherwise 
//     return error str

/**
*  Checks the user's chain choice when running MolProbity Compare on two
*  chains within the same pdb.
*
**/
function check_chain_choice($chain1, $chain2, $modelID)
{
  $s = "chain1 and chain2 are the same! Please choose two DIFFERENT chains.";
  if($chain1 == $chain2) return $s;
  else {
    // get aacgeom table
    $rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
    // this error should never hapen as we have already
    // checked that the user ran aacgeom. Just in cass though...
    $s = "ERROR: Cannot find raw data. Please report to the webmaster and ";
    $s .= "tell them that Bradley's code is broken.";
    if(!file_exists($rawDir)) return $s;
    else {
      $model = false;
      foreach($_SESSION['models'] as $key => $m)
        if($m['id'] == trim($modelID)) $model = $m;
      if($model !== false) {
        $file = "$rawDir/$model[prefix]multi.table";
        $in = fopen($file, 'rb');
        clearstatcache();
        $data = fread($in, filesize($file));
        $table = mpUnserialize($data);
        fclose($in);
        return $table;
      }
    }
  }
}
// }}}

// {{{ get_2_tables
function get_2_tables($modelID_1, $modelID_2)
{
  $rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
  // this error should never hapen as we have already
  // checked that the user ran aacgeom. Just in cass though...
  $s = "ERROR: Cannot find raw data. Please report to the webmaster and ";
  $s .= "tell them that Bradley's code is broken.";
  if(!file_exists($rawDir)) return $s;
  $model1 = false;
  $model2 = false;
  foreach($_SESSION['models'] as $key => $m) {
    if($m['id'] == trim($modelID_1)) $model1 = $m;
    if($m['id'] == trim($modelID_2)) $model2 = $m;
  }
  if($model1 !== false) {
    $file = "$rawDir/$model1[prefix]multi.table";
    $in = fopen($file, 'rb');
    clearstatcache();
    $data = fread($in, filesize($file));
    $table1 = mpUnserialize($data);
    fclose($in);
  } else $table1 = "Had trouble with \$model1";
  if($model2 !== false) {
    $file = "$rawDir/$model2[prefix]multi.table";
    $in = fopen($file, 'rb');
    clearstatcache();
    $data = fread($in, filesize($file));
    $table2 = mpUnserialize($data);
    fclose($in);
  } else $table1 = "Had trouble with \$model2";
  if($table1 !== false || $table2 !== false) return array($table1, $table2);
  else {
    $s = "Couldn't find the two tables for MPC.";
    return $s;
  }
}
// }}}

// {{{ get_chain_sequences;
/**
*  Returns a fasta formated string for the sequences of each chain in a 
*  mparam_hierarchy (mh1) IF a chain1 and chain2 are specified. Otherwise will 
*  take the whole sequence from the mparam_hierarchy, mh1 and mp2.
*  $mh is mparam_hierarchy
**/
function get_chain_sequences($mh1, $modelID1, $mh2=false, $modelID2=false, 
  $chain1=false, $chain2=false)
{
  $res_nums1 = array_keys($mh1);
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E',
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K',
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' ,
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W',
    'TYR' => 'Y');
  if($chain1 !== false & $chain2 !== false & $modelID2 !== false) 
    $chain_from_2 = true; //for when the user picks chains from different models
  if($chain1 !== false & $chain2 !== false & $modelID2 === false) {
    $chain_sequences;
    foreach(array($chain1, $chain2) as $chain) {
      $s = '';
      foreach($res_nums1 as $resnum) {
        if(substr($resnum, 0, 1) == $chain) {
          if(array_key_exists($mh1[$resnum]['res']['html'], $aa_3_1))
            $s .= $aa_3_1[$mh1[$resnum]['res']['html']];
          elseif($mh1[$resnum]['res']['html'] == "HOH") $s .= "*";
          else $s .= 'X';
        }
      }
      $chain_sequences[$chain][] = $s;
      $fasta .= ">".$modelID1."_".$chain."\n$s\n";
      }
    return $fasta;
  } else {
    $s = '';
    foreach($res_nums1 as $resnum) {
      if(array_key_exists($mh1[$resnum]['res']['html'], $aa_3_1))
        $aa = $aa_3_1[$mh1[$resnum]['res']['html']];
      elseif($mh1[$resnum]['res']['html'] == "HOH") $aa = "*";
      else $aa = 'X';
      if(substr($resnum, 0, 1) == $chain1 & $chain_from_2) $s .= $aa;
      elseif(!$chain_from_2) $s .= $aa;
    }
    $fasta = ">".$modelID1."\n$s\n";
    $res_nums2 = array_keys($mh2);
    $s = '';
    foreach($res_nums2 as $resnum) {
      if(array_key_exists($mh2[$resnum]['res']['html'], $aa_3_1))
        $aa = $aa_3_1[$mh2[$resnum]['res']['html']];
      elseif($mh1[$resnum]['res']['html'] == "HOH") $aa = "*";
      else $aa = 'X';
      if(substr($resnum, 0, 1) == $chain2 & $chain_from_2) $s .= $aa;
      elseif(!$chain_from_2) $s .= $aa;
    }
    $fasta .= ">".$modelID2."\n$s\n";
    return $fasta;
  }
}
//}}}

// {{{ get_molprobity_compare_table
function get_molprobity_compare_table($fasta, $mph1, $modelID1, $mph2,
  $modelID2, $chain1 = false, $chain2 = false, 
  $ksdssp1 = false, $ksdssp2 = false, $directory = false) 
/**
*  Returns a MolProbity Compare array that has info on each MolProbity parameter
*  for each residue being compared and how they compare to one another. This 
*  will align the given fasta file and make a MolProbity Compare table
*  based on that alignment.
*    $fasta: a string, in fasta format, with sequences from the two structures to be compared.
*    $mph1: MolProbity parameter hierarchy 1.
*    $modelID1: the model ID for model 1.
*    $mph2: MolProbity parameter hierarchy 2.
*    $modelID2: the model ID for model 2.
**/
{
  if($mph2 !== false && $modelID2 !== false)
    $model_2_name = $modelID1."_".$modelID2;
  else 
    $model_2_name = $modelID1;
  // save the sequences of the two chains in fasta format
  if($directory) { //!isset($_SESSION)) { //for the cmdline version
    $rawDir = $directory;
    $cleanup = true; 
  } else {
    $rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
    $cleanup = false; 
  }
  $fasta_file_name = $rawDir."/".$model_2_name;
  $aln_lines = align_fasta($fasta_file_name.".fasta", $fasta);
  $aln_array = array($fasta_file_name.".fasta", $fasta_file_name.".dnd", 
    $fasta_file_name.".aln");
  //for the cmdline version, deletes clustalw files in the current dir
  if($cleanup) delete_files($aln_array);
  
  $err = "ERROR: Problem aligning the sequences. Contact the webmaster and ";
  $err .= "include this error message.";
  if($aln_lines === false) return $err;
  else {
    // linearize aln file
    list($aln_seq1, $aln_seq2, $aln_code) = linearize_alignment($aln_lines,
      $modelID1, $modelID2);
    // get the % identity of the pairwise alignment
    $identity = (substr_count($aln_code, '*')/strlen($aln_code))*100;
    if($identity < 70) $low_identity = $identity;
    else $low_identity = false;
    // apply the alignment to mph 
    $mph1_aln = apply_align_2_mph($mph1, $aln_seq1);
    $mph2_aln = apply_align_2_mph($mph2, $aln_seq2);
    //after alignment these should be the same length
    if(count($mph1_aln) != count($mph2_aln))
      return "ERROR: The two MPH are not the same length.\n";
    $mph_sbs;
    $mph_diff = array('modelID1' => $modelID1, 'modelID2' => $modelID2);
    foreach(array_keys($mph1_aln) as $key) {
      if($mph1_aln[$key] != "-") $res_n1 = $mph1_aln[$key]['res']['html'];
      if($mph2_aln[$key] != "-") $res_n2 = $mph2_aln[$key]['res']['html'];
      foreach($mph1_aln[$key] as $param => $arr) {
        if($mph1_aln[$key] != "-") {
          $html1 = $mph1_aln[$key][$param]['html'];
          $color1 = $mph1_aln[$key][$param]['color'];
        } else {
          $html1 = "No Residue";
          $color1 = "";
        }
        if($mph2_aln[$key] != "-") {
          $html2 = $mph2_aln[$key][$param]['html'];
          $color2 = $mph2_aln[$key][$param]['color'];
        } else {
          $html2 = "No Residue";
          $color2 = "";
        }
        $param_values = array(
          'html1' => $html1,
          'color1' => $color1,
          'html2' => $html2,
          'color2' => $color2);
        $mph_sbs[$key][$param] = $param_values;
        $param_scores = extract_param_scores(
          $param = $param, 
          $html1 = $html1, 
          $html2 = $html2,
          $res1 = $res_n1,
          $res2 = $res_n2);
        $mph_diff[$key][$param] = $param_scores;
      }
    }
    //add ksdssp to $mph_sbs
    if($ksdssp1 !== false) {
      foreach($mph_sbs as $key => $res_info) {
        $ksdssp_assign1 = '-';
        foreach($ksdssp1 as $ksdssp_res) {
          $chain_num = $ksdssp_res['chain_num'];
          if(startsWith($res_info['#']['html1'], $chain_num))
            $ksdssp_assign1 = $ksdssp_res['ss'];
        }
        $ksdssp_assign2 = '-';
        foreach($ksdssp2 as $ksdssp_res) {
          $chain_num = $ksdssp_res['chain_num'];
          if(startsWith($res_info['#']['html2'], $chain_num))
            $ksdssp_assign2 = $ksdssp_res['ss'];
        }
        $mph_sbs[$key]['ksdssp'] = array(
                'html1' => $ksdssp_assign1,
                'color1' => '',
                'html2' => $ksdssp_assign2,
                'color2' => '');
      }
    }
    return array($mph_sbs, $mph_diff, $low_identity);
  }
}
//}}}

// {{{ delete_files
function delete_files($a)
/**
*
*  Deletes the file specified in $a;
*    $a, an array listing of the files to be deleted;
*
**/
{
  foreach($a as $path)
    unlink($path);
}
// }}}

// {{{ align_fasta
function align_fasta($abs_path_file_name, $fasta)
{
  // save the sequences in fasta format
  $handle = fopen($abs_path_file_name, 'w');
  fwrite($handle, $fasta);
  fclose($handle);
  // execute the alignment
  $status = run_clustal($abs_path_file_name);
  if($status === 0) {
    // get the alignment file
    $aln_file_name = str_replace(".fasta", ".aln", $abs_path_file_name);
    $handle = fopen($aln_file_name, 'r');
    $aln = fread($handle, filesize($aln_file_name));
    fclose($handle);
    $aln_lines = file($aln_file_name);
    return $aln_lines;
  } else {
    return false;
  }
}
// }}}

// {{{ run_clustal
function run_clustal($fasta_abs_path)
{
  exec("clustalw2 -INFILE=$fasta_abs_path", $output, $status);
  return $status;
}
// }}}

// {{{ linearize_alignment
function linearize_alignment($aln_lines, $modelID1, $modelID2)
{
  $aln_seq1 = '';
  $aln_seq2 = '';
  $aln_code = '';
  $seq_pos = false;
  foreach($aln_lines as $line_num => $line) {
    if(substr($line, 0, strlen($modelID1)) === $modelID1) {
      $seq = trim(str_replace($modelID1, '', $line));
      $aln_seq1 .= $seq;
      // gets the position of the start of the sequence for the aln_code line
      $seq_pos = strpos($line, $seq);
    }
    elseif(substr($line, 0, strlen($modelID2)) === $modelID2)
      $aln_seq2 .= trim(str_replace($modelID2, '', $line));
    elseif(substr($line, 0, 1) === " ")## && $seq_pos !== false)
      $aln_code .= trim(substr($line, $seq_pos), "\n\r");
  }
  return array($aln_seq1, $aln_seq2, $aln_code);
}
// }}}

// {{{ split_mph_by_chain
function split_mph_by_chain($mparam_hierarchy, $chain)
{
  $mph;
  foreach($mparam_hierarchy as $res_num => $a) {
    if(substr($res_num, 0, 1) == $chain)
      $mph[$res_num] = $a;
  }
  return $mph;
}
// }}}

// {{{ apply_align_2_mph
function apply_align_2_mph($mph, $aln_seq)
{
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E', 
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K', 
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' , 
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W', 
    'TYR' => 'Y');
  // put dashes in mph as dictated by the aligned sequences
  $mph_keys = array_keys($mph);
  $mph_aln;
  $i = 0;
  foreach(str_split($aln_seq) as $key => $res) {
    if($res == "-") $mph_aln[] = $res;
    else {//if($res == $aa_3_1[$mph[$mph_keys[$i]]['res']['html']]) {
      $mph_aln[] =  $mph[$mph_keys[$i]];
      $i++;
    } 
  }
  return $mph_aln;
}
// }}}

// {{{ get_mpc_sbs_chart; side-by-side chart
function get_mpc_sbs_chart($mpc_t, $model1_name, $model2_name,
  $return_mpc_array=false)
/*********************************************************
*  
*  MPC = MolProbity Compare
*  This function returns an array with two strings as follows:
*    array("table" => $c, "key" => $k)
*  The 'table' is the HTML required for the MPC side-by-side chart.
*  The 'key' is the HTML required for the key of the MPC side-by-side chart.
*  The function is required to construct the MPC side-by-side chart frame
*
*    $mpc_t is the MPC table
*    $model1_name is the name of model 1
*    $model2_name is the name of model 2
*  
*********************************************************/
{
  foreach($mpc_t as $key => $res) {
    $mparam_keys = array_keys($res);
    break;
  }
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E',
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K',
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' ,
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W',
    'TYR' => 'Y');
  $mpc_data;
  foreach($mpc_t as $key => $res) {
    // {{{ get mpc_data for the horizontal chart
    $chain_num1 = $res['#']['html1'];
    $chain_num2 = $res['#']['html2'];
    $mpc_data[$key]['model'][1] = $model1_name;
    $mpc_data[$key]['model'][2] = $model2_name;
    $mpc_data[$key]['#'][1] = $chain_num1;
    $mpc_data[$key]['#'][2] = $chain_num2;
    if($res['res']['html1'] == 'No Residue') $res_1 = '-';
    elseif(array_key_exists($res['res']['html1'], $aa_3_1))
      $res_1 = $aa_3_1[$res['res']['html1']];
    else $res_1 = '?';
    if($res['res']['html2'] == 'No Residue') $res_2 = '-';
    elseif(array_key_exists($res['res']['html2'], $aa_3_1))
      $res_2 = $aa_3_1[$res['res']['html2']];
    else $res_2 = '?';
    $mpc_data[$key]['res'][1] = $res_1;
    $mpc_data[$key]['res'][2] = $res_2;
    $mpc_data[$key]['res_full'][1] = $res['res']['html1'];
    $mpc_data[$key]['res_full'][2] = $res['res']['html2'];
    $mpc_data[$key]['high b'][1] = $res['high b']['html1'];
    $mpc_data[$key]['high b'][2] = $res['high b']['html2'];
    
    if($res['clash &gt; 0.4&aring;']['color1']) {
      $overlap = get_clash_overlap($res['clash &gt; 0.4&aring;']['html1']);
      #defines what color of triangle to put down based on degree of overlap
      if($overlap < 0.8) $clash_img = 'img/clashA_1.png';
      elseif($overlap < 1.2) $clash_img = 'img/clashB_1.png';
      else $clash_img = 'img/clashC_1.png';
      $html = get_img_html($clash_img, $chain_num1.":clash1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":clash1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['clash &gt; 0.4&aring;']['html1'], 
        $num = $chain_num1.":clash1",
        $param = "Clash");
      $c = $html;
    }
    $mpc_data[$key]['clash &gt; 0.4&aring;'][1] = $c;
    if($res['clash &gt; 0.4&aring;']['color2']) {
      $overlap = get_clash_overlap($res['clash &gt; 0.4&aring;']['html2']);
      #defines what color of triangle to put down based on degree of overlap
      if($overlap < 0.8) $clash_img = 'img/clashA_2.png';
      elseif($overlap < 1.2) $clash_img = 'img/clashB_2.png';
      else $clash_img = 'img/clashC_2.png';
      $html = get_img_html($clash_img, $chain_num1.":clash2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":clash2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['clash &gt; 0.4&aring;']['html2'], 
        $num = $chain_num1.":clash2",
        $param = "Clash");
      $c = $html;
    }
    $mpc_data[$key]['clash &gt; 0.4&aring;'][2] = $c;
    
    if($res['ramachandran']['color1']) {
      $html = get_img_html('img/mpc_out1.png', $chain_num1.":rama1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":rama1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['ramachandran']['html1'], 
        $num = $chain_num1.":rama1",
        $param = "Ramachandran"); 
      $c = $html;
    }
    $mpc_data[$key]['ramachandran'][1] = $c;
    if($res['ramachandran']['color2']) {
      $html = get_img_html('img/mpc_out2.png', $chain_num1.":rama2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":rama2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['ramachandran']['html2'], 
        $num = $chain_num1.":rama2",
        $param = "Ramachandran");
      $c = $html;
    }
    $mpc_data[$key]['ramachandran'][2] = $c;
    
    if($res['rotamer']['color1']) {
      $html = get_img_html('img/mpc_out1.png', $chain_num1.":rotamer1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":rotamer1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['rotamer']['html1'], 
        $num = $chain_num1.":rotamer1",
        $param = "Rotamer");
      $c = $html;
    }
    $mpc_data[$key]['rotamer'][1] = $c;
    if($res['rotamer']['color2']) {
      $html = get_img_html('img/mpc_out2.png', $chain_num1.":rotamer2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":rotamer2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else { 
      $html .= get_div_html($info = $res['rotamer']['html2'], 
        $num = $chain_num1.":rotamer2",
        $param = "Rotamer");
      $c = $html;
    }
    $mpc_data[$key]['rotamer'][2] = $c;
    
    if($res['c&beta; deviation']['color1']) {
      $html = get_img_html('img/mpc_out1.png', $chain_num1.":cB1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":cB1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['c&beta; deviation']['html1'], 
        $num = $chain_num1.":cB1",
        $param = "c&beta; deviation");
      $c = $html;
    }
    $mpc_data[$key]['c&beta; deviation'][1] = $c;
    if($res['c&beta; deviation']['color2']) {
      $html = get_img_html('img/mpc_out2.png', $chain_num1.":cB2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":cB2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['c&beta; deviation']['html2'], 
        $num = $chain_num1.":cB2",
        $param = "c&beta; deviation");
      $c = $html;
    }
    $mpc_data[$key]['c&beta; deviation'][2] = $c;
    
    if($res['bond lengths.']['color1']) {
      $html = get_img_html('img/mpc_out1.png', $chain_num1.":bl1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":bl1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['bond lengths.']['html1'], 
        $num = $chain_num1.":bl1",
        $param = "Bond Length");
      $c = $html;
    }
    $mpc_data[$key]['bond lengths.'][1] = $c;
    if($res['bond lengths.']['color2']) {
      $html = get_img_html('img/mpc_out2.png', $chain_num1.":bl2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":bl2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['bond lengths.']['html2'], 
        $num = $chain_num1.":bl2",
        $param = "Bond Length");
      $c = $html;
    }
    $mpc_data[$key]['bond lengths.'][2] = $c;
    
    if($res['bond angles.']['color1']) {
      $html = get_img_html('img/mpc_out1.png', $chain_num1.":ba1");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":ba1");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['bond angles.']['html1'], 
        $num = $chain_num1.":ba1",
        $param = "Bond Angle");
      $c = $html;
    }
    $mpc_data[$key]['bond angles.'][1] = $c;
    if($res['bond angles.']['color2']) {
      $html = get_img_html('img/mpc_out2.png', $chain_num1.":ba2");
      $bool = true;
    }else {
      $html = get_img_html('img/mpc_noout.png', $chain_num1.":ba2");
      $bool = false;
    }
    if($return_mpc_array) $c = $bool;
    else {
      $html .= get_div_html($info = $res['bond angles.']['html2'], 
        $num = $chain_num1.":ba2",
        $param = "Bond Angle");
      $c = $html;
    }
    $mpc_data[$key]['bond angles.'][2] = $c;
    
    //add ksdssp info
    if(isset($res['ksdssp'])) {
      $mpc_data[$key]['ksdssp'][1] = $res['ksdssp']['html1'];
      $mpc_data[$key]['ksdssp'][2] = $res['ksdssp']['html2'];
    }
    // }}}
  }
  $heads = array('clash &gt; 0.4&aring;', 'ramachandran', 'rotamer', 
    'c&beta; deviation', 'bond lengths.', 'bond angles.', 'res', 'ksdssp');
  $c = "<html>\n<body>\n<table frame=void rules=all width = '100%'>\n";
  $k = "<html>\n<body>\n<table frame=void rules=all width = '100%'>\n";
  $rk = array('res', 'ksdssp');
  foreach($heads as $head) {
    // if($head == 'res') ensures that this is only done once
    if(in_array($head, $rk)) {
      if($head == 'res') $pa = 'Res&nbsp;';
      elseif($head == 'ksdssp') $pa = 'ss&nbsp;';
      $res_key = "<table rules='rows' frame='void'><tr>";
      $res_key .= "<td rowspan='2'>$pa</td>";
      $res_key .= "<td>Model 1</td></tr><tr>";
      $res_key .= "<td>Model 2</td></tr></table>";
      $align = " align='right'";
    }
    else {
      $res_key = ucwords($head);
      $align = "";
    }
    $c .= "  <tr><td style='height:28px'$align>$res_key</td>\n";
    $k .= "  <tr><td style='height:28px'$align>$res_key</td></tr>\n";
    foreach($mpc_data as $key => $res) {
      // REMINDER: $rk = array('res', 'ksdssp')
      if(in_array($head, $rk) & $res[$head][1] !== $res[$head][2]) {
        $res1 = "<font color='red'>".$res[$head][1]."</font>";
        $res2 = "<font color='red'>".$res[$head][2]."</font>";
      } else {
        $res1 = $res[$head][1];
        $res2 = $res[$head][2];
      }
      // put <div> hovers on res ids
      if($head == 'res') {
        $res1 = get_img_html($img = false, $num = $res['#'][1].":resid1", $txt = $res1);
        $info1 = "Residue ID: ".$res['res_full'][1]."<br>High B: ".$res['high b'][1];
        $rescn1 = 'l';//$res['#'][1];
        $res1 .= get_div_html($info = $info1, $num = $res['#'][1].":resid1", 
          $param = "Residue");
        $res2 = get_img_html($img = false, $num = $res['#'][2].":resid2", $txt = $res2);
        $info2 = "Residue ID: ".$res['res_full'][2]."<br>High B: ".$res['high b'][2];
        $rescn2 = 'l';//$res['#'][2];
        $res2 .= get_div_html($info = $info2, $num = $res['#'][2].":resid2", 
          $param = "Residue");
      }
      $c .= "      <td><table><tr><td align='center'>$res1";
      $c .= "</td></tr><tr><td align='center'>$res2";
      $c .= "</td></tr></table></td>\n";
    }
    $c .= "  </tr>\n";
  }
  $k .= "</table>\n</body>\n</html>\n";
  $c .= "</table>\n</body>\n</html>\n";
  if($return_mpc_array) return $mpc_data;
  else return array("table" => $c, "key" => $k);
}
// }}}

// {{{ get_mpc_frame_sbs
function get_mpc_frame_sbs($url)
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
// }}}

// {{{ extract_param_scores
function extract_param_scores($param, $html1, $html2, $res1, $res2)
{
  // if $html == '-' $score = "0". if $html == 'No Residue' $score = $html
  if($param == 'clash &gt; 0.4&aring;') {
    if($html1 == '-') $score1 = 0;// {1}
    elseif($html1 == 'No Residue') $score1 = $html1;
    else {
      if(strpos($html1, "&Aring;")) 
        $score1 = substr($html1, 0, strpos($html1, "&Aring;"));
      else $score1 = $html1;
    }
    if($html2 == '-') $score2 = 0;// {1}
    elseif($html2 == 'No Residue') $score2 = $html2;
    else {
      if(strpos($html1, "&Aring;")) 
        $score2 = substr($html2, 0, strpos($html2, "&Aring;"));
      else $score2 = $html2;
    }
  }
  elseif($param == '#' || $param == 'res' || $param == 'high b') {
    $score1 = $html1;
    $score2 = $html2;
  }
  elseif($param == 'ramachandran') {
    // if $html# == 'No Residue' or '-', $score = $html
    if(startsWith($html1, 'OUTLIER')) $score1 = 1;// {11}
    elseif(startsWith($html1, 'Allowed')) $score1 = 0;
    elseif(startsWith($html1, 'Favored')) $score1 = 0;
    elseif($html1 == 'No Residue' || $html1 == '-') $score1 = $html1;
    else $score1 = 0;
    if(startsWith($html2, 'OUTLIER')) $score2 = 1;// {11}
    elseif(startsWith($html2, 'Allowed')) $score2 = 0;
    elseif(startsWith($html2, 'Favored')) $score2 = 0;
    elseif($html2 == 'No Residue' || $html2 == '-') $score2 = $html2;
    else $score2 = 0;
  }
  elseif($param == 'rotamer') {
    // if $html# == 'No Residue', $score = $html
    if($html1 == 'No Residue') $score1 = $html1;
    elseif($html1 == '-' & $res_n1 = 'ALA' || $res_n1 = 'GLY')
      $score1 = 0;// {21}
    elseif(strpos($html1, "%")) {
      $percent = substr($html1, 0, strpos($html1, "%"))+0;// {20}
      if($percent < 1) $score1 = 1;
      else $score1 = 0;
    } else {
      if($html1 < 1) $score1 = 1;
      else $score1 = 0;
    }
    if($html2 == 'No Residue') $score2 = $html2;
    elseif($html2 == '-' & $res_n2 = 'ALA' || $res_n2 = 'GLY')
      $score2 = 0;// {21}
    elseif(strpos($html2, "%")) {
      $percent = substr($html2, 0, strpos($html2, "%"))+0;// {20}
      if($percent < 1) $score2 = 1;
      else $score2 = 0;
    } else {
      if($html2 < 1) $score2 = 1;
      else $score2 = 0;
    }
  }
  elseif($param == 'c&beta; deviation') {
    // if $html# == 'No Residue' or '-', $score = $html
    if($html1 == 'No Residue' || $html1 == '-') $score1 = $html1;
    else {
      if(strpos($html1, "&Aring;"))
        $cb1 = substr($html1, 0, strpos($html1, "&Aring;"))+0;
      else $cb1 = $html1;
      if($cb1 < 0.25) $score1 = 0;// {29}
      else $score1 = $cb1;
    }
    if($html2 == 'No Residue' || $html2 == '-') $score2 = $html2;
    else {
      if(strpos($html2, "&Aring;"))
        $cb2 = substr($html2, 0, strpos($html2, "&Aring;"))+0;
      else $cb2 = $html2;
      if($cb2 < 0.25) $score2 = 0;// {29}
      else $score2 = $cb2;
    }
  }
  elseif($param == 'bond lengths.') {
    // if $html# == 'No Residue' $score = $html
    if($html1 == 'No Residue') $score1 = $html1;
    elseif($html1 == '-') $score1 = 0;// {38}
    else $score1 = extract_bond_sigma($html1);
    if($html2 == 'No Residue') $score2 = $html2;
    elseif($html2 == '-') $score2 = 0;// {38}
    else $score2 = extract_bond_sigma($html2);
  }
  elseif($param == 'bond angles.') {
    // if $html# == 'No Residue' or '-', $score = $html
    if($html1 == 'No Residue') $score1 = $html1;
    elseif($html1 == '-') $score1 = 0;// {51}
    else $score1 = extract_bond_sigma($html1);
    if($html2 == 'No Residue') $score2 = $html2;
    elseif($html2 == '-') $score2 = 0;// {51}
    else $score2 = extract_bond_sigma($html2);
  }
  return array($score1, $score2);
}
// }}}

// {{{ get_mpc_labbook_entry
function get_mpc_labbook_entry($mpc_table_name)
{
  $entry = "<h3>MolProbity Compare visualizations</h3>\n";
  $entry .= "<div class='indent'>\n";
  $entry .= "<table width='100%' border='0'>\n  <tr valign='center'>\n";
  $entry .= "    <td>\n".linkAnyFile("$mpc_table_name-table.mpc", "MPC",
    "img/MPC_sbs.png")."</td>\n";
  $entry .= "    <td>\n".linkAnyFile("$mpc_table_name-table.mpcscores", "MPC_scores",
    "img/MPC_changes.png")."</td>\n";
  $entry .= "</tr></table>\n";
  $entry .= "</div>\n";
  return $entry;
}
// }}}

// {{{ extract_bond_sigma
function extract_bond_sigma($html)
{
  $sig = strpos($html, "&sigma");
  $col = strpos($html, ":");
  if($sig === false & $col === false) return $html;//$sig; delete n
  return trim(substr($html, $col+1, strpos($html, '&')-$col-1));
}
// }}}

// {{{ get_mpc_frame_changes
function get_mpc_frame_changes($url)
{
  $s = "<html>\n<body>\n<table style=\"width:100%\">\n  ";
  $s .= "<tr><td style=\"width:135px\">\n";
  $s .= "         <iframe src=$url&key=t width='100%' height='250' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n";
  $s .= "      </td>\n      <td>\n";
  $s .= "         <iframe src=$url&table=t width='100%' height='250' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n</td></tr></table>\n";
  $s .= "<center>\n";
  $s .= "         <iframe src=$url&scores=t width='75%' height='225' align=center>\n";
  $s .= "          <p>Your browser does not support iframes.</p>\n";
  $s .= "        </iframe>\n</center>\n</body>\n</html>";
  return $s;
}
// }}}

// {{{ calculate_mpc_differences
function calculate_mpc_differences($table_mpcscores, $improved_model,
  $original_model)
/******************************************************************
*
*  returns an array of parameter 'scores' and differences for each residue
*
******************************************************************/
{
  $modelID1 = $table_mpcscores['modelID1'];
  $modelID2 = $table_mpcscores['modelID2'];
  // why do we need this??
  unset($table_mpcscores['modelID1']);
  unset($table_mpcscores['modelID2']);
  // set the improved and original models
  if($improved_model == $modelID1) $improved = 0;
  elseif($improved_model == $modelID2) $improved = 1;
  else return;
  if($original_model == $modelID1) $original = 0;
  elseif($original_model == $modelID2) $original = 1;
  else return;
  
  $diff_array;
  foreach($table_mpcscores as $key => $resarray) {
    $resname_improved = $resarray['res'][$improved];
    $resname_original = $resarray['res'][$original];
    $res_improved = $resarray['#'][$improved];
    $res_original = $resarray['#'][$original];
    $clash_improved = $resarray['clash &gt; 0.4&aring;'][$improved];
    $clash_original = $resarray['clash &gt; 0.4&aring;'][$original];
    $rama_improved = $resarray['ramachandran'][$improved];
    $rama_original = $resarray['ramachandran'][$original];
    $rot_improved = $resarray['rotamer'][$improved];
    $rot_original = $resarray['rotamer'][$original];
    $cb_improved = $resarray['c&beta; deviation'][$improved];
    $cb_original = $resarray['c&beta; deviation'][$original];
    $bl_improved = $resarray['bond lengths.'][$improved];
    $bl_original = $resarray['bond lengths.'][$original];
    $ba_improved = $resarray['bond angles.'][$improved];
    $ba_original = $resarray['bond angles.'][$original];
    $highb_improved = $resarray['high b'][$improved];
    $highb_original = $resarray['high b'][$original];
    $res_a['improved_resname'] = $resname_improved;
    $res_a['original_resname'] = $resname_original;
    $res_a['improved_resnum'] = $res_improved;
    $res_a['original_resnum'] = $res_original;
    $res_a['clash_improved'] = $clash_improved;
    $res_a['clash_original'] = $clash_original;
    $res_a['rama_improved'] = $rama_improved;
    $res_a['rama_original'] = $rama_original;
    $res_a['rotamer_improved'] = $rot_improved;
    $res_a['rotamer_original'] = $rot_original;
    $res_a['cB_improved'] = $cb_improved;
    $res_a['cB_original'] = $cb_original;
    $res_a['bl_improved'] = $bl_improved;
    $res_a['bl_original'] = $bl_original;
    $res_a['ba_improved'] = $ba_improved;
    $res_a['ba_original'] = $ba_original;
    $res_a['high_b_improved'] = $highb_improved;
    $res_a['high_b_original'] = $highb_original;
    if($clash_original === '-' || $clash_original === 'No Residue' || 
      $clash_improved === '-' || $clash_improved === 'No Residue')
      $res_a['clashD'] = '-';
    else $res_a['clashD'] = abs($clash_original) - abs($clash_improved);// {2}
    if($rama_original === '-' || $rama_original === 'No Residue' || 
      $rama_improved === '-' || $rama_improved === 'No Residue')
      $res_a['ramaD'] = '-';
    else $res_a['ramaD'] = abs($rama_original) - abs($rama_improved);// {12}
    if($rot_original === '-' || $rot_original === 'No Residue' || 
      $rot_improved === '-' || $rot_improved === 'No Residue') 
      $res_a['rotD'] = '-';
    else $res_a['rotD'] = abs($rot_original) - abs($rot_improved);// {21a}
    if($cb_original === '-' || $cb_original === 'No Residue' || 
      $cb_improved === '-' || $cb_improved === 'No Residue')
      $res_a['cbD'] = '-';
    else $res_a['cbD'] = abs($cb_original) - abs($cb_improved);// {30}
    if($bl_original === '-' || $bl_original === 'No Residue' || 
      $bl_improved === '-' || $bl_improved === 'No Residue')
      $res_a['blD'] = '-';
    else $res_a['blD'] = abs($bl_original) - abs($bl_improved);// {40}
    if($ba_original === '-' || $ba_original === 'No Residue' || 
      $ba_improved === '-' || $ba_improved === 'No Residue') 
      $res_a['baD'] = '-';
    else $res_a['baD'] = abs($ba_original) - abs($ba_improved);// {52}
    $diff_array[$res_improved."_".$res_original] = $res_a;
  }
  return $diff_array;
}
// }}}

// {{{ get_changes_overall
function get_changes_overall($diff_array, $return_array=null)
/******************************************************************
*
*  returns HTML for the MolProbity Compare overall scores table
*
******************************************************************/
{
  $html = "<table frame='void' rules='rows'>\n  <tr><td>";
  
  // {{{ initiate 'score' variables
  // red_targ = reduction target and actual_red = actual reduction
  $clash_red_targ = 0;
  $clash_actual_red = 0;
  $clash_overall = 0;
  $clash_num_original = 0;
  $clash_num_improved = 0;
  $rama_red_targ = 0;
  $rama_actual_red = 0;
  $rama_num_original = 0;
  $rama_num_improved = 0;
  $rama_overall = 0;
  $rot_red_targ = 0;
  $rot_actual_red = 0;
  $rot_overall = 0;
  $rot_num_original = 0;
  $rot_num_improved = 0;
  $cb_red_targ = 0;
  $cb_actual_red = 0;
  $cb_overall = 0;
  $cb_num_original = 0;
  $cb_num_improved = 0;
  $bl_red_targ = 0;
  $bl_actual_red = 0;
  $bl_overall = 0;
  $bl_num_original = 0;
  $bl_num_improved = 0;
  $ba_red_targ = 0;
  $ba_actual_red = 0;
  $ba_overall = 0;
  $ba_num_original = 0;
  $ba_num_improved = 0;
  // }}}

  foreach($diff_array as $ress => $array) {
    if($array["clashD"] !== "-") {
      if($array["clash_original"] !== 0) $sc = 1;
      else $sc = 0;
      $clash_num_original += $sc;
      if($array["clash_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $clash_num_improved += $sc;
      $clash_red_targ += $array["clash_original"];// {8}
      $clash_actual_red += $array["clashD"];// {9}
    }
    if($array["ramaD"] !== "-") {
      if($array["rama_original"] !== 0) $sc = 1;
      else $sc = 0;
      $rama_num_original += $sc;
      if($array["rama_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $rama_num_improved += $sc;
      $rama_red_targ += $array["rama_original"];// {15}
      $rama_actual_red += $array["ramaD"];// {35}
    }
    if($array["rotD"] !== "-") {
      if($array["rotamer_original"] !== 0) $sc = 1;
      else $sc = 0;
      $rot_num_original += $sc;
      if($array["rotamer_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $rot_num_improved += $sc;
      $rot_red_targ += $array["rotamer_original"];// {36}
      $rot_actual_red += $array["rotD"];// {46}
    }
    if($array["cbD"] !== "-") {
      if($array["cB_original"] !== 0) $sc = 1;
      else $sc = 0;
      $cb_num_original += $sc;
      if($array["cB_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $cb_num_improved += $sc;
      $cb_red_targ += $array["cB_original"];// {15}
      $cb_actual_red += $array["cbD"];// {47}
    }
    if($array["blD"] !== "-") {
      if($array["bl_original"] !== 0) $sc = 1;
      else $sc = 0;
      $bl_num_original += $sc;
      if($array["bl_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $bl_num_improved += $sc;
      $bl_red_targ += abs($array["bl_original"]);// {15}
      $bl_actual_red += $array["blD"];// {58}
    }
    if($array["baD"] !== "-") {
      if($array["ba_original"] !== 0) $sc = 1;
      else $sc = 0;
      $ba_num_original += $sc;
      if($array["ba_improved"] !== 0) $sc = 1;
      else $sc = 0;
      $ba_num_improved += $sc;
      $ba_red_targ += abs($array["ba_original"]);// {15}
      $ba_actual_red += $array["baD"];// {59}
    }
  }
  // {{{ calculate overall difference scores for each paarameter
  if($clash_red_targ === 0 & $clash_actual_red !== 0) {
    $clash_overall = "-5*";// {10a}
    $clash_info = "The original model had<br>";
    $clash_info .= "no clashes but the<br>";
    $clash_info .= "improved model does!";
  }
  elseif($clash_red_targ === 0 & $clash_actual_red === 0) {
    $clash_overall = "0*";// {10b}
    $clash_info = "The original and improved<br>";
    $clash_info .= "model have no clashes!";
  } else {
    $clash_overall = round($clash_actual_red/$clash_red_targ, 4);// {10}
    if($clash_overall < 0) {
      $clash_info = "The improved model has<br>";
      $clash_info .= "worse overall clashes<br>";
      $clash_info .= "than the original model!";
    } else {
      $p = $clash_overall*100;
      $clash_info = "Overall the clash<br>";
      $clash_info .= "severity decreased<br>";
      $clash_info .= "by $p%.";
    }
  }
  if($rama_red_targ === 0 & $rama_actual_red !== 0) {
    $rama_overall = "-5*";// {17}
    $rama_info = "The original model had no<br>";
    $rama_info .= "ramachandran outliers<br>";
    $rama_info .= "but the improved model does!";
  }
  elseif($rama_red_targ === 0 & $rama_actual_red === 0) {
    $rama_overall = "0*";// {18}
    $rama_info = "The original and improved<br>";
    $rama_info .= "model have no<br>";
    $rama_info .= "ramachandran outliers!";
  } else {
    $rama_overall = round($rama_actual_red/$rama_red_targ, 4);// {19}
    if($rama_overall < 0) {
      $rama_info = "The improved model has<br>";
      $rama_info .= "more ramachandran outliers<br>";
      $rama_info .= "than the original model!";
    } else {
      $p = $rama_overall*100;
      $rama_info = "Ramachandran outliers<br>";
      $rama_info .= "decreased by $p%.";
    }
  }
  if($rot_red_targ === 0 & $rot_actual_red !== 0) {
    $rot_overall = "-5*";// {26}
    $rot_info = "The original model had no<br>";
    $rot_info .= "rotamer outliers<br>";
    $rot_info .= "but the improved model does!";
  }
  elseif($rot_red_targ === 0 & $rot_actual_red === 0) {
    $rot_overall = "0*";// {27}
    $rot_info = "The original and improved<br>";
    $rot_info .= "model have no<br>";
    $rot_info .= "rotamer outliers!";
  } else {
    $rot_overall = round($rot_actual_red/$rot_red_targ, 4);// {28}
    if($rot_overall < 0) {
      $rot_info = "The improved model has<br>";
      $rot_info .= "more rotamer outliers<br>";
      $rot_info .= "than the original model!";
    } else {
      $p = $rot_overall*100;
      $rot_info = "Rotamer outliers<br>";
      $rot_info .= "decreased by $p%.";
    }
  }
  if($cb_red_targ === 0 & $cb_actual_red !== 0) {
    $cb_overall = "-5*";// {37}
    $cb_info = "The original model had<br>";
    $cb_info .= "no C&beta; deviation but<br>";
    $cb_info .= "the improved model does!";
  }
  elseif($cb_red_targ === 0 & $cb_actual_red === 0) {
    $cb_overall = "0*";// {38}
    $cb_info = "The original and improved<br>";
    $cb_info .= "model have no<br>";
    $cb_info .= "C&beta; deviations!";
  } else {
    $cb_overall = round($cb_actual_red/$cb_red_targ, 4);// {39}
    if($cb_overall < 0) {
      $cb_info = "The improved model has worse<br>";
      $cb_info .= "overall C&beta; deviations<br>";
      $cb_info .= " than the original model!";
    } else {
      $p = $cb_overall*100;
      $cb_info = "Overall the C&beta; deviation<br>";
      $cb_info .= "severity decreased by $p%.";
    }
  }
  if($bl_red_targ === 0 & $bl_actual_red !== 0) {
    $bl_overall = "-5*";// {48}
    $bl_info = "The original model had<br>";
    $bl_info .= "no bond length outliers<br>";
    $bl_info .= "but the improved model does!";
  }
  elseif($bl_red_targ === 0 & $bl_actual_red === 0) {
    $bl_overall = "0*";// {49}
    $bl_info = "The original and improved<br>";
    $bl_info .= "model have no bond<br>";
    $bl_info .= "length outliers!";
  } else {
    $bl_overall = round($bl_actual_red/$bl_red_targ, 4);// {50}
    if($bl_overall < 0) {
      $bl_info = "The improved model has<br>";
      $bl_info .= "more bond length outliers<br>";
      $bl_info .= "than the original model!";
    } else {
      $p = $bl_overall*100;
      $bl_info = "Overall bond length<br>";
      $bl_info .= "outliers decreased by $p%.";
    }
  }
  if($ba_red_targ === 0 & $ba_actual_red !== 0) {
    $ba_overall = "-5*";// {60}
    $ba_info = "The original model had<br>";
    $ba_info .= "no bond angle outliers<br>";
    $ba_info .= "but the improved model does!";
  }
  elseif($ba_red_targ === 0 & $ba_actual_red === 0) {
    $ba_overall = "0*";// {61}
    $ba_info = "The original and improved<br>";
    $ba_info .= "model have no bond<br>";
    $ba_info .= "angle outliers!";
  } else {
    $ba_overall = round($ba_actual_red/$ba_red_targ, 4);// {62}
    if($ba_overall < 0) {
      $ba_info = "The improved model has<br>";
      $ba_info .= "more bond angle outliers<br>";
      $ba_info .= "than the original model!";
    } else {
      $p = $ba_overall*100;
      $ba_info = "Overall bond angle<br>";
      $ba_info .= "outliers decreased by $p%.";
    }
  }
  // }}}
  if($clash_overall == "0*") $clash_bg = "" ;
  elseif($clash_overall < 0 || $clash_overall == "-5*")
    $clash_bg = " style='background-color:#FF6699'";
  if($rama_overall == "0*") $rama_bg = "";
  elseif($rama_overall < 0 || $rama_overall == "-5*")
    $rama_bg = " style='background-color:#FF6699'";
  if($rot_overall == "0*") $rot_bg = "";
  elseif($rot_overall < 0 || $rot_overall == "-5*")
    $rot_bg = " style='background-color:#FF6699'";
  if($cb_overall == "0*") $cb_bg = "";
  elseif($cb_overall < 0 || $cb_overall == "-5*")
    $cb_bg = " style='background-color:#FF6699'";
  if($bl_overall == "0*") $bl_bg = "";
  elseif($bl_overall < 0 || $bl_overall == "-5*")
    $bl_bg = " style='background-color:#FF6699'";
  if($ba_overall == "0*") $ba_bg = "";
  elseif($ba_overall < 0 || $ba_overall == "-5*")
    $ba_bg = " style='background-color:#FF6699'";
  $clash_td = $clash_bg.get_omo_html("clash");
  $rama_td = $rama_bg.get_omo_html("rama");
  $rot_td = $rot_bg.get_omo_html("rot");
  $cb_td = $cb_bg.get_omo_html("cb");
  $bl_td = $bl_bg.get_omo_html("bl");
  $ba_td = $ba_bg.get_omo_html("ba");
  $overall_array = array();
  $style = " style=\"width:200; position:absolute; display:none;";
  $style .= " background-color: #cccc99; border-style:solid;";
  $style .= " border-width:1px; padding: 5px;\"";
  $html = "<html>\n<body>\n";
  $html .= "<div id=\"clash\" class=\'comment\"$style>";
  $html .= "<center><b>Clash</b><br>$clash_info</center></div>\n\n";
  $html .= "<div id=\"rama\" class=\'comment\"$style>";
  $html .= "<center><b>Ramachandran</b><br>$rama_info</center></div>";
  $html .= "<div id=\"rot\" class=\'comment\"$style>";
  $html .= "<center><b>Rotamer</b><br>$rot_info</center></div>";
  $html .= "<div id=\"cb\" class=\'comment\"$style>";
  $html .= "<center><b>C&beta; deviation</b><br>$cb_info</center></div>";
  $html .= "<div id=\"bl\" class=\'comment\"$style>";
  $html .= "<center><b>Bond Length</b><br>$bl_info</center></div>";
  $html .= "<div id=\"ba\" class=\'comment\"$style>";
  $html .= "<center><b>Bond Angle</b><br>$ba_info</center></div>";
  // $html = get_div_html($info="info", $num="calsh", $param="param", $res="res");
  $html .= "<center>\n<table frame=void rules=all width = '90%'>\n";
  $html .= "  <tr><th colspan='7'>Overall Summary</th</tr>\n";
  $html .= "  <tr><th>Parameter</th>\n";
  $html .= "      <th>Reduction<br>Target</th>\n";
  $html .= "      <th>Actual<br>Reduction</th>\n";
  $html .= "      <th>Reduction<br>Ratio</th>\n";
  $html .= "      <th># of Original<br>Outliers</th>\n";
  $html .= "      <th># of Outliers<br>Eliminated</th>\n";
  $html .= "      <th># of Outliers<br>Remaining</th></tr>\n";
  $html .= "  <tr><td>Clash &gt; 0.4&Aring;</td>\n";
  $html .= "      <td>$clash_red_targ</td>\n";
  $html .= "      <td>$clash_actual_red</td>\n";
  $html .= "      <td$clash_td>$clash_overall</td>\n";
  $html .= "      <td>$clash_num_original</td>\n";
  $d = $clash_num_original - $clash_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$clash_num_improved</td></tr>\n";
  $overall_array['clash']['reduction_target'] = $clash_red_targ;
  $overall_array['clash']['actual_reduction'] = $clash_actual_red;
  $overall_array['clash']['reduction_ratio'] = $clash_overall;
  $overall_array['clash']['num_original'] = $clash_num_original;
  $overall_array['clash']['outliers_eliminated'] = $d;
  $overall_array['clash']['num_improved'] = $clash_num_improved;
  $html .= "  <tr><td>Ramachandran</td>\n      <td>$rama_red_targ</td>\n";
  $html .= "      <td>$rama_actual_red</td>\n";
  $html .= "      <td$rama_td>$rama_overall</td>\n";
  $html .= "      <td>$rama_num_original</td>\n";
  $d = $rama_num_original - $rama_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$rama_num_improved</td></tr>\n";
  $overall_array['rama']['reduction_target'] = $rama_red_targ;
  $overall_array['rama']['actual_reduction'] = $rama_actual_red;
  $overall_array['rama']['reduction_ratio'] = $rama_overall;
  $overall_array['rama']['num_original'] = $rama_num_original;
  $overall_array['rama']['outliers_eliminated'] = $d;
  $overall_array['rama']['num_improved'] = $rama_num_improved;
  $html .= "  <tr><td>Rotamer</td>\n      <td>$rot_red_targ</td>\n";
  $html .= "      <td>$rot_actual_red</td>\n";
  $html .= "      <td$rot_td>$rot_overall</td>\n";
  $html .= "      <td>$rot_num_original</td>\n";
  $d = $rot_num_original - $rot_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$rot_num_improved</td></tr>\n";
  $overall_array['rot']['reduction_target'] = $rot_red_targ;
  $overall_array['rot']['actual_reduction'] = $rot_actual_red;
  $overall_array['rot']['reduction_ratio'] = $rot_overall;
  $overall_array['rot']['num_original'] = $rot_num_original;
  $overall_array['rot']['outliers_eliminated'] = $d;
  $overall_array['rot']['num_improved'] = $rot_num_improved;
  $html .= "  <tr><td>C&beta; deviation</td>\n      <td>$cb_red_targ</td>\n";
  $html .= "      <td>$cb_actual_red</td>\n";
  $html .= "      <td$cb_td>$cb_overall</td>\n";
  $html .= "      <td>$cb_num_original</td>\n";
  $d = $cb_num_original - $cb_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$cb_num_improved</td></tr>\n";
  $overall_array['cb']['reduction_target'] = $cb_red_targ;
  $overall_array['cb']['actual_reduction'] = $cb_actual_red;
  $overall_array['cb']['reduction_ratio'] = $cb_overall;
  $overall_array['cb']['num_original'] = $cb_num_original;
  $overall_array['cb']['outliers_eliminated'] = $d;
  $overall_array['cb']['num_improved'] = $cb_num_improved;
  $html .= "  <tr><td>Bond Length</td>\n      <td>$bl_red_targ</td>\n";
  $html .= "      <td>$bl_actual_red</td>\n";
  $html .= "      <td$bl_td>$bl_overall</td>\n";
  $html .= "      <td>$bl_num_original</td>\n";
  $d = $bl_num_original - $bl_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$bl_num_improved</td></tr>\n";
  $overall_array['bl']['reduction_target'] = $bl_red_targ;
  $overall_array['bl']['actual_reduction'] = $bl_actual_red;
  $overall_array['bl']['reduction_ratio'] = $bl_overall;
  $overall_array['bl']['num_original'] = $bl_num_original;
  $overall_array['bl']['outliers_eliminated'] = $d;
  $overall_array['bl']['num_improved'] = $bl_num_improved;
  $html .= "  <tr><td>Bond Angle</td>\n      <td>$ba_red_targ</td>\n";
  $html .= "      <td>$ba_actual_red</td>\n";
  $html .= "      <td$ba_td>$ba_overall</td>\n";
  $html .= "      <td>$ba_num_original</td>\n";
  $d = $ba_num_original - $ba_num_improved;
  $html .= "      <td>".$d."</td>\n";
  $html .= "      <td>$ba_num_improved</td></tr>\n";
  $overall_array['ba']['reduction_target'] = $ba_red_targ;
  $overall_array['ba']['actual_reduction'] = $ba_actual_red;
  $overall_array['ba']['reduction_ratio'] = $ba_overall;
  $overall_array['ba']['num_original'] = $ba_num_original;
  $overall_array['ba']['outliers_eliminated'] = $d;
  $overall_array['ba']['num_improved'] = $ba_num_improved;
  $html .= "</table>\n<center>\n</body>\n</html>";
  if($return_array) return $overall_array;
  else return $html;
}

function get_omo_html($id)
/********************************************************
*
*  returns onmouseover htmml for hovering divS
*
********************************************************/
{
  $s  = " onmouseover=\"ShowContent('$id'); return true;\"";
  $s .= " onmouseout=\"HideContent('$id'); return true;\"";
  return $s;
}
// }}}

// {{{ get_changes_html
function get_changes_html($diff_array)
/******************************************************************
*
*  returns an array of HTML for the MolProbity Compare changes frame
*
******************************************************************/
{
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E',
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K',
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' ,
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W',
    'TYR' => 'Y');  
  $changes_array;
  foreach($diff_array as $res_pair => $res_array) {
    if(isset($aa_3_1[$res_array['improved_resname']]))
      $improved = $aa_3_1[$res_array['improved_resname']];
    elseif($res_array['improved_resname'] == "No Residue") $improved = "-";
    else $improved = "?";
    $info1 = "Redidue ID: $res_array[improved_resname]<br>High B: ";
    $info1 .= "$res_array[high_b_improved]";
    $s = "<a".get_omo_html($id = $res_pair.":i").">$improved</a>\n";
    $s .= get_div_html($info = $info1, $num = $res_pair.":i", 
      $param = "Residue", $res = "Residue: ".$res_array['improved_resnum']);
    $changes_array[$res_pair]['improved_res'] = $s;
    if(isset($aa_3_1[$res_array['original_resname']]))
      $original = $aa_3_1[$res_array['original_resname']];
    elseif($res_array['original_resname'] == "No Residue") $original = "-";
    else $original = "?";
    $info2 = "Redidue ID: $res_array[original_resname]<br>High B: ";
    $info2 .= "$res_array[high_b_original]";
    $s = "<a".get_omo_html($id = $res_pair.":o").">$original</a>\n";
    $s .= get_div_html($info = $info2, $num = $res_pair.":o",
      $param = "Residue", $res = "Residue: ".$res_array['original_resnum']);
    $changes_array[$res_pair]['original_res'] = $s;
    // CLASH
    if($res_array['clashD'] < 0 & $res_array['clash_original'] === 0) {
      $html = get_img_html('img/change_red.png', $res_pair.":clash");//{4}
      $inf = "Clash introduced!";
    } elseif($res_array['clashD'] < -0.15 & $res_array['clash_original'] > 0) {
      $html = get_img_html('img/change_salmon.png', $res_pair.":clash");// {5}
      $inf = "Clash increased!";
    } elseif($res_array['clashD'] === 0 & $res_array['clash_original'] > 0) {
      $html = get_img_html('img/change_equal.png', $res_pair.":clash");// {5b}
      $inf = "No clash change";
    } elseif($res_array['clashD'] > 0.15 & $res_array['clash_improved'] > 0) {
      $html = get_img_html('img/change_yellow.png', $res_pair.":clash");// {6}
      $inf = "Clash decreased!";
    } elseif($res_array['clashD'] > 0 & $res_array['clash_improved'] === 0) {
      $html = get_img_html('img/change_green.png', $res_pair.":clash");// {7}
      $inf = "Clash eliminated!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":clash");
      $inf = "-";
    }
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<br>Original clash: $res_array[clash_original]<br>";
    $inf .= "Improved clash: $res_array[clash_improved]";
    $html .= get_div_html($info = $inf, $num = $res_pair.":clash",
      $param = "clash", $res= $resh);
    $changes_array[$res_pair]["clash"] = $html;
    // RAMA
    if($res_array['ramaD'] == 1) {
      $html = get_img_html('img/change_green.png', $res_pair.":rama");// {13}
      $inf = "Rama outlier eliminated!";
    } elseif($res_array['ramaD'] === 0 & $res_array['rama_improved'] == 1) {
      $html = get_img_html('img/change_equal.png', $res_pair.":rama");// {14}
      $inf = "Rama outlier still present";
    } elseif($res_array['ramaD'] == -1) {
      $html = get_img_html('img/change_red.png', $res_pair.":rama");// {15}
      $inf = "Rama outlier introduced!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":rama");
      $inf = "-";
    }
    if($res_array["rama_original"] == 1) $or = "OUTLIER";
    else $or = $res_array["rama_original"];
    if($res_array["rama_improved"] == 1) $im = "OUTLIER";
    else $im = $res_array["rama_improved"];
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<table><tr><td>Original rama:</td><td>$or</td></tr>";
    $inf .= "<tr><td>Improved rama:</td><td>$im</td></tr></table>";
    $html .= get_div_html($info = $inf, $num = $res_pair.":rama",
      $param = "rama", $res= $resh);
    $changes_array[$res_pair]["rama"] = $html;
    // ROT
    if($res_array['rotD'] == 1) {
      $html = get_img_html('img/change_green.png', $res_pair.":rot");// {21b}
      $inf = "Rotamer outlier eliminated!";
    } elseif($res_array['rotD'] === 0 & $res_array['rot_improved'] == 1) {
      $html = get_img_html('img/change_equal.png', $res_pair.":rot");// {22}
      $inf = "Rotamer outlier still present";
    } elseif($res_array['rotD'] == -1) {
      $html = get_img_html('img/change_red.png', $res_pair.":rot");// {23}
      $inf = "Rotamer outlier introduced!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":rot");
      $inf = "-";
    }
    if($res_array["rotamer_original"] == 1) $or = "OUTLIER";
    else $or = $res_array["rotamer_original"];
    if($res_array["rotamer_improved"] == 1) $im = "OUTLIER";
    else $im = $res_array["rotamer_improved"];
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<table><tr><td>Original rotamer:</td><td>$or</td></tr>";
    $inf .= "<tr><td>Improved rotamer:</td><td>$im</td></tr></table>";
    $html .= get_div_html($info = $inf, $num = $res_pair.":rot",
      $param = "rot", $res= $resh);
    $changes_array[$res_pair]["rot"] = $html;
    // cB
    if($res_array['cbD'] < 0 & $res_array['cb_original'] < 0.25) {
      $html = get_img_html('img/change_red.png', $res_pair.":cb");// {30}
      $inf = "cB outlier introduced!";
    } elseif($res_array['cbD'] < 0 & $res_array['cb_original'] >= 0.25) {
      $html = get_img_html('img/change_salmon.png', $res_pair.":cb");// {31}
      $inf = "cB outlier increased!";
    } elseif($res_array['cbD'] === 0 & $res_array['cb_original'] >= 0.25) {
      $html = get_img_html('img/change_equal.png', $res_pair.":cb");// {31a}
      $inf = "cB outlier still present";
    } elseif($res_array['cbD'] > 0 & $res_array['cb_improved'] >= 0.25) {
      $html = get_img_html('img/change_yellow.png', $res_pair.":cb");// {34}
      $inf = "cB outlier decreased!";
    } elseif($res_array['cbD'] > 0 & $res_array['cb_improved'] < 0.25) {
      $html = get_img_html('img/change_green.png', $res_pair.":cb");// {34}
      $inf = "cB outlier eliminated!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":cb");
      $inf = "-";
    }
    if($res_array["cb_original"] >= 0.25) $or = "OUTLIER";
    else $or = $res_array["cb_original"];
    if($res_array["cb_improved"] >= 0.25) $im = "OUTLIER";
    else $im = $res_array["cb_improved"];
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<table><tr><td>Original cbamer:</td><td>$or</td></tr>";
    $inf .= "<tr><td>Improved cbamer:</td><td>$im</td></tr></table>";
    $html .= get_div_html($info = $inf, $num = $res_pair.":cb",
      $param = "cb", $res= $resh);
    $changes_array[$res_pair]["cb"] = $html;
    // bond length
    if($res_array['blD'] < 0 & $res_array['bl_original'] === 0) {
      $html = get_img_html('img/change_red.png', $res_pair.":bl");// {41}
      $inf = "bond length outlier introduced!";
    } elseif($res_array['blD'] < 0 & $res_array['bl_original'] != 0) {
      $html = get_img_html('img/change_salmon.png', $res_pair.":bl");// {42}
      $inf = "bond length outlier increased!";
    } elseif($res_array['blD'] === 0 & $res_array['bl_original'] != 0) {
      $html = get_img_html('img/change_equal.png', $res_pair.":bl");// {43}
      $inf = "bond length outlier still present";
    } elseif($res_array['blD'] > 0 & $res_array['bl_improved'] != 0) {
      $html = get_img_html('img/change_yellow.png', $res_pair.":bl");// {44}
      $inf = "bond length outlier decreased!";
    } elseif($res_array['blD'] > 0 & $res_array['bl_improved'] === 0) {
      $html = get_img_html('img/change_green.png', $res_pair.":bl");// {45}
      $inf = "bond length outlier eliminated!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":bl");
      $inf = "-";
    }
    if($res_array["bl_original"] != 0) $or = "OUTLIER";
    else $or = $res_array["bl_original"];
    if($res_array["bl_improved"] != 0) $im = "OUTLIER";
    else $im = $res_array["bl_improved"];
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<table><tr><td>Original bond length:</td><td>$or</td></tr>";
    $inf .= "<tr><td>Improved bond length:</td><td>$im</td></tr></table>";
    $html .= get_div_html($info = $inf, $num = $res_pair.":bl",
      $param = "bl", $res= $resh);
    $changes_array[$res_pair]["bl"] = $html;
    // bond angle
    if($res_array['baD'] < 0 & $res_array['ba_original'] === 0) {
      $html = get_img_html('img/change_red.png', $res_pair.":ba");// {53}
      $inf = "bond angle outlier introduced!";
    } elseif($res_array['baD'] < 0 & $res_array['ba_original'] != 0) {
      $html = get_img_html('img/change_salmon.png', $res_pair.":ba");// {54}
      $inf = "bond angle outlier increased!";
    } elseif($res_array['baD'] === 0 & $res_array['ba_original'] != 0) {
      $html = get_img_html('img/change_equal.png', $res_pair.":ba");// {55}
      $inf = "bond angle outlier still present";
    } elseif($res_array['baD'] > 0 & $res_array['ba_improved'] != 0) {
      $html = get_img_html('img/change_yellow.png', $res_pair.":ba");// {56}
      $inf = "bond angle outlier decreased!";
    } elseif($res_array['baD'] > 0 & $res_array['ba_improved'] === 0) {
      $html = get_img_html('img/change_green.png', $res_pair.":ba");// {57}
      $inf = "bond angle outlier eliminated!";
    } else {
      $html = get_img_html('img/change_none.png', $res_pair.":ba");
      $inf = "-";
    }
    if($res_array["ba_original"] != 0) $or = "OUTLIER";
    else $or = $res_array["ba_original"];
    if($res_array["ba_improved"] != 0) $im = "OUTLIER";
    else $im = $res_array["ba_improved"];
    $resh = "Original: $res_array[original_resnum]<br>";
    $resh .= "Improved: $res_array[improved_resnum]";
    $inf .= "<table><tr><td>Original bond length:</td><td>$or</td></tr>";
    $inf .= "<tr><td>Improved bond length:</td><td>$im</td></tr></table>";
    $html .= get_div_html($info = $inf, $num = $res_pair.":ba",
      $param = "ba", $res= $resh);
    $changes_array[$res_pair]["ba"] = $html;
  }
  return $changes_array;
}
// }}}

// {{{ get_mpc_changes_chart
function get_mpc_changes_chart($mpc_html, $improved_model, $original_model)
/*********************************************************
*  
*  MPC = MolProbity Compare
*  This function returns an array with two strings as follows:
*    array("table" => $c, "key" => $k)
*  The 'table' is the HTML required for the MPC changes chart.
*  The 'key' is the HTML required for the key of the MPC changes chart.
*  The function is required to construct the MPC changes chart frame
*
*    $mpc_html is the MPC changes HTML table
*    $improved_model is the name of the improved model
*    $original_model is the name of the original model
*  
*********************************************************/
{
  $aa_3_1 = array('ALA' => 'A', 'CYS' => 'C', 'ASP' => 'D', 'GLU' => 'E',
    'PHE' => 'F', 'GLY'  => 'G', 'HIS' => 'H', 'ILE' => 'I', 'LYS' => 'K',
    'LEU' => 'L', 'MET' => 'M', 'ASN' => 'N', 'PRO' => 'P', 'GLN' => 'Q' ,
    'ARG' => 'R', 'SER' => 'S', 'THR' => 'T', 'VAL' => 'V', 'TRP' => 'W',
    'TYR' => 'Y');  
  $param_html;
  foreach($mpc_html as $key => $params) {
    if(isset($params["clash"])) 
      $param_html["Clash &gt; 0.4&Aring;"][] = $params["clash"];
    if(isset($params["rama"])) 
      $param_html["Ramachandran"][] = $params["rama"];
    if(isset($params["rot"])) 
      $param_html["Rotamer"][] = $params["rot"];
    if(isset($params["cb"])) 
      $param_html["c&beta; deviation"][] = $params["cb"];
    if(isset($params["bl"])) 
      $param_html["Bond Length"][] = $params["bl"];
    if(isset($params["ba"])) 
      $param_html["Bond Angle"][] = $params["ba"];
    if(isset($params["original_res"]))
      $param_html["Original&nbsp;Residue"][] = $params["original_res"];
    if(isset($params["improved_res"]))
      $param_html["Improved&nbsp;Residue"][] = $params["improved_res"];
    if($improved != $original) $param_html["color"][] = " color='red'";
    else $param_html["color"][] = "";
  }
  $c = "<html>\n<body>\n<table frame=void rules=all width = '100%'>\n";
  $k = "<html>\n<body>\n<table frame=void rules=all width = '100%'>\n";
  $heads = array_keys($param_html);
  foreach($heads as $head) {
    if($head != "color") {
      $k .= "  <tr><td>$head</td></tr>\n";
      $c .= "  <tr><td>$head</td>\n";
      $i = 0;
      foreach($param_html[$head] as $html) {
        if($head == "Original&nbsp;Residue" || 
          $head == "Improved&nbsp;Residue") {
          if($param_html["color"][$i] == " color='red'") {
            $c .= "      <td><center><font".$param_html["color"][$i].">$html";
            $c .= "</font></center></td>\n";
          }
          else $c .= "      <td>$html</td>\n";
        } else $c .= "      <td>$html</td>\n";
        $i++;
      }
      $c .= "  </tr>\n";
    }
  }
  $k .= "</table>\n</body>\n</html>\n";
  $c .= "</table>\n</body>\n</html>\n";
  return array("table" => $c, "key" => $k);
      
}
// }}}

// {{{ get_checkbox_form
function get_checkbox_form($run, $array, $button_text, $is_model=false)
/**************************************************************
*
*  Returns html for a form with a list of checkboxes, the options are elements
*  in $array.
*
**************************************************************/
{
  $s = makeEventForm($run);
  $s .= "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
  $c = MP_TABLE_ALT1;
  foreach($array as $id => $model)
  {
    if($is_model === true) {
      $history = $model['history'];
      $pdb = $model['id'];
    } else {
      $history = $model;
      $pdb = $model;
    }
    // Alternate row colors:
    $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
    $s .= " <tr bgcolor='$c'>\n";
    $s .= "  <td><input type='checkbox' name='model_4_comp_$id' value='$pdb'</td>\n";
    $s .= "  <td><b>$pdb</b></td>\n";
    $s .= "  <td><small>$history</small></td>\n";
    $s .= " </tr>\n";
  }
  $s .= "</table></p>\n";
  
  $s .= "<p><table width='100%' border='0'><tr>\n";
  $s .= "<td><input type='submit' name='cmd' value='$button_text'></td>\n";
  $s .= "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
  $s .= "</tr></table></p></form>\n";
  return $s;
}
// }}}

// {{{ get_choose_form
function get_choose_form($run, $array, $button_text, $num_choice, $choice_name,
  $array2=false)
/**************************************************************
*
*  Returns html for a form with a where the user can choose $num_choice
*  elements in $array. The elements are listed $num_choice times in different
*  radiobutton groups. One can also specify $num_choice as 2 and $array2 as 
*  a second array to shoose from; i.e. chose one from $array and one from 
*  $array2.
*
*    $run, the name of the function to be ran upon submission
*    $array, the elements to choose from
*    $button_text, the text to go onto the submit button
*    $num_choice, the number of elements/choices to take from $array
*    $choice_name, the choice name
*
**************************************************************/
{
  if($array2 !== false & $num_choice !== 2) return "ERROR 66 in Bradley's code";
  $s = makeEventForm($run);
  $s .= "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
  $s .= " <tr>\n  ";
  $i = 1;
  while($i <= $num_choice) {
    if($num_choice > 1) $num = " $i";
    else $num = '';
    $s .= "<td colspan=2><center>".$choice_name.$num."</center></td>\n";
    $i++;
  }
  //$s .= "  <td colspan=2><center>$choice_name 2</center></td>\n";
  $s .= " </tr>\n";
  $c = MP_TABLE_ALT1;
  if($array2 === false) {
    foreach($array as $id => $choice) {
      // Alternate row colors:
      $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
      $s .= " <tr bgcolor='$c'>\n";
      $i = 1;
      while($i <= $num_choice) {
        $s .= "  <td width='25%' align='right'><input type='radio'";
        $s .= " name='$choice_name$i' value='$id'></td>\n";
        $s .= "  <td width='25%' align='left'><b>$choice</b></td>\n";
        $i++;
      }
      $s .= " </tr>\n";
    }
  } else {
    $keys1 = array_keys($array);
    $keys2 = array_keys($array2);
    if(count($keys1) > count($keys2)) $one_bigger_two = true;
    else $one_bigger_two = false;
    if($one_bigger_two === true) {
      $num_rows = count($keys1);
      $keys = $keys1;
    } else {
      $num_rows = count($keys2);
      $keys = $keys2;
    }
    $i = 0;
    while($i < count($keys)) {
      $choice1 = '';
      $radio1 = '';
      $choice2 = '';
      $radio2 = '';
      if($i < count($keys1)) {
        $choice1 = $array[$keys1[$i]];
        $radio1 = "<input type='radio' name='$choice_name"."1' value='".$keys1[$i]."'>";
      }
      if($i < count($keys2)) {
        $choice2 = $array2[$keys2[$i]];
        $radio2 = "<input type='radio' name='$choice_name"."2' value='".$keys2[$i]."'>";
      }
      $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
      $s .= " <tr bgcolor='$c'>\n";
      $s .= "  <td width='25%' align='right'>$radio1</td>\n";
      $s .= "  <td width='25%' align='left'>$choice1</td>\n";
      $s .= "  <td width='25%' align='right'>$radio2</td>\n";
      $s .= "  <td width='25%' align='left'>$choice2</td></tr>\n";
      $i++;
    }
  }

  $s .= "<p><table width='100%' border='0'><tr>\n";
  $s .= "<td><input type='submit' name='cmd' value='$button_text'></td>\n";
  $s .= "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
  $s .= "</tr></table></p></form>\n";
  return $s;
  
}
// }}}

// {{{ add_or_change_parameter
function add_or_change_parameter($params_2_change)
{
  $get = $_GET;
  foreach($params_2_change as $parameter => $value)
    $get[$parameter] = $value;
  $output = "?";
  $firstRun = true;
  foreach($get as $key=>$val) $output .= $key."=".urlencode($val)."&";
  return htmlentities($output);
}
// }}}

// {{{ get_ksdssp
function get_ksdssp($pdb)
/**************************************************************
*
*  Returns array, $ss_residues containing ksddsp info for all residues assigned
*  either helix or sheet for the given $pdb. This function parses the ksdssp
*  output.
*
**************************************************************/
{
  list($ksdssp, $status) = run_ksdssp($pdb);
  if($status === 0) {
    $ss_residues;
    foreach($ksdssp as $line) {
      if(startsWith($line, 'HELIX')) {
        $start = trim(substr($line, 20, 5));
        $end = trim(substr($line, 32, 5));
        $chain1 = substr($line, 19, 1);
        $chain2 = substr($line, 31, 1);
        if($chain1 == $chain2) {
          for($i = $start; $i <= $end; $i++) {
            $spaces = '';
            if(strlen(strval($i)) == 3) $spaces = ' ';
            elseif(strlen(strval($i)) == 2) $spaces = '  ';
            elseif(strlen(strval($i)) == 1) $spaces = '   ';
            $chain_num = $chain1.$spaces.$i;
            $ss_residues[] = array(
              'cahin' => $chain1, 
              '#' => $i, 
              'chain_num' => $chain_num,
              'ss' => 'H');
          }
        }
      }
      elseif(startsWith($line, 'SHEET')) {
        $start = trim(substr($line, 22, 4));
        $end = trim(substr($line, 33, 4));
        $chain1 = substr($line, 21, 1);
        $chain2 = substr($line, 32, 1);
        if($chain1 == $chain2) {// should always be true
          for($i = $start; $i <= $end; $i++) { 
            $spaces = '';
            if(strlen(strval($i)) == 3) $spaces = ' ';
            elseif(strlen(strval($i)) == 2) $spaces = '  ';
            elseif(strlen(strval($i)) == 1) $spaces = '   ';
            $chain_num = $chain1.$spaces.$i;
            $ss_residues[] = array(
              'cahin' => $chain1, 
              '#' => $i, 
              'chain_num' => $chain_num,
              'ss' => 'S');
          }
        }
      }
    }
    return $ss_residues;
  }
}
// }}}

// {{{ run_ksdssp
function run_ksdssp($pdb)
{
  $out = exec("ksdssp $pdb", $output, $status);
  return array($output, $status);
}
// }}}

// {{{ get_database_table
function get_database_table($mpc_t, $minus_unpaired_res = false)
/*********************************************************************
*
*  Returns an array where each elements represents two residues being compared.
*  This function is used on the cmdline when '-table' flag is an argument.
*  $mpc_t is the output of 'get_mpc_sbs_chart'
*  If $minus_unpaired_res = true then those residues that are unpaired in the
*  alignment will not be included in the table
*
*********************************************************************/
{
  $db_table = array();
  foreach($mpc_t as $res) {
    $arr = array();
    if($res['#'][1] == 'No Residue') {
      $arr['chain_1'] =  '-';
      $arr['resnum_1'] = 'No Residue';
    } else {
      $arr['chain_1'] =    substr($res['#'][1], 0, 1);
      $arr['resnum_1'] =   trim(substr($res['#'][1], 1));
    }
    if($res['#'][2] == 'No Residue') {
      $arr['chain_2'] =  '-';
      $arr['resnum_2'] = 'No Residue';
    } else {
      $arr['chain_2'] =    substr($res['#'][2], 0, 1);
      $arr['resnum_2'] =   trim(substr($res['#'][2], 1));
    }
    $arr['resid_1'] =    $res['res'][1];
    $arr['resid_2'] =    $res['res'][2];
    $arr['highb_1'] =    $res['high b'][1];
    $arr['highb_2'] =    $res['high b'][2];
    $arr['ksdssp_1'] =   $res['ksdssp'][1];
    $arr['ksdssp_2'] =   $res['ksdssp'][2];
    $arr['clash_1'] =    $res['clash &gt; 0.4&aring;'][1];
    $arr['clash_2'] =    $res['clash &gt; 0.4&aring;'][2];
    $arr['rama_1'] =     $res['ramachandran'][1];
    $arr['rama_2'] =     $res['ramachandran'][2];
    $arr['rota_1'] =     $res['rotamer'][1];
    $arr['rota_2'] =     $res['rotamer'][2];
    $arr['cb_1'] =       $res['c&beta; deviation'][1];
    $arr['cb_2'] =       $res['c&beta; deviation'][2];
    $arr['bl_1'] =       $res['bond lengths.'][1];
    $arr['bl_2'] =       $res['bond lengths.'][2];
    $arr['ba_1'] =       $res['bond angles.'][1];
    $arr['ba_2'] =       $res['bond angles.'][2];
    $arr['model_1'] =    $res['model'][1];
    $arr['model_2'] =    $res['model'][2];
    if($minus_unpaired_res) {
      if($res['#'][1] != 'No Residue' && $res['#'][2] != 'No Residue') 
        $db_table[] = $arr;
    } else $db_table[] = $arr;
  }
  return $db_table;
}
// }}}

// {{{ get_clash_overlap
function get_clash_overlap($clash_html)
/****************************************************************
*
* returns the clash overlap in angstroms from $clash_html
*
****************************************************************/
{
  return substr($clash_html, 0, strpos($clash_html, "&Aring"));
}
// }}}

?>
