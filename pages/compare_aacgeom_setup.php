<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to set up MolProbity Compare. This was
    created by the novice PHP programmer King Bradley
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
  if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
  require_once(MP_BASE_DIR.'/lib/horizontal_chart_func.php');
  require_once(MP_BASE_DIR.'/lib/labbook.php');
class compare_aacgeom_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID     the model ID to analyze
*/
function display($context)
{
  echo $this->pageHeader("MolProbity Compare");
  
  if(count($_SESSION['models']) > 0)
  {
    // Choose a default model to select
    $lastUsedID = $context['modelID'];
    if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
    
    $raw_dir = $_SESSION['dataDir'].'/raw_data';
    // if raw_dir doesn't exists then aacgeom has not been run or perhaps charts
    // were not made. MolProbity Compare needs the charts (xxxx-multi.table)
    // check_multitable_inputs will ensure that there are at least two 
    // xxxx-multi.table, if not will return a string to display telling the 
    // user what to do.
    $check_value = $this->check_multitable_inputs($raw_dir);
    if(is_string($check_value))
      echo $check_value;
    elseif(is_array($check_value)) 
    {
      $aacgeom_models;
      // only add the pdb names (not '-multi.table')
      foreach($check_value as $table) {
        $pdb = substr($table, 0, strpos($table, '-mul')-strlen($table));
        $aacgeom_models[] = $pdb ;
      }
      if(isset($_SESSION['user_mpc_error'])) {
        echo "<p style=\"color:red\">$_SESSION[user_mpc_error]</p>";
        unset($_SESSION['user_mpc_error']);
      }
      // this should always return the same number of $models as $aacgeom_models
      $models;
      foreach($_SESSION['models'] as $id => $model) {
        $dot_pos = strpos($model['pdb'], '.')-strlen($model['pdb']);
        $clean_pos = strpos($model['pdb'], '_clean.')-strlen($model['pdb']);
        $pdbv3_pos = strpos($model['pdb'], '_pdbv3.')-strlen($model['pdb']);
        if(endsWith($model['pdb'], "_clean.pdb"))
          $pdb = substr($model['pdb'], 0, $clean_pos);
        elseif(endsWith($model['pdb'], "_pdbv3.pdb"))
          $pdb = substr($model['pdb'], 0, $pdbv3_pos);
        elseif(endsWith($model['pdb'], ".pdb"))
          $pdb = substr($model['pdb'], 0, $dot_pos);
        else $pdb = $model['pdb'];
        if(array_search($pdb, $aacgeom_models) !== false) $models[] = $model;
      }
      if($_SESSION['mpc_setup'] == 'choose_chain') {
        unset($_SESSION['mpc_setup']);
        $chains = $_SESSION['chain_array'];
        unset($_SESSION['chain_array']);
        $modelID = $_SESSION['mpc']['MPC_model_1chain'];
        echo "<h3>Select TWO chains to compare:</h3>\n";
        echo "<center><b>Chains for $modelID</b></center>\n";
        $form = get_choose_form($run = "onChooseChains",
          $array = $chains, $button_text = "Continue &gt;",
          $num_choice = 2, $choice_name = "Chain");
        echo $form;
      } elseif($_SESSION['mpc_setup'] == 'choose_improved') {
        unset($_SESSION['mpc_setup']);
        echo "<h3>Select the 'improved' model.</h3>\n";
        echo "<h4>Used to compute difference scores</h4>\n";
        $mess = "<center><table frame='box' width='40%'>\n  <tr>\n      <td>";
        $mess .= "NOTE: An 'improved model' needs to be chosen for the ";
        $mess .= "initial difference calculations. If you don't know the ";
        $mess .= "improved you can choose one at random, the 'improved model' ";
        $mess .= "can be changed later.</td>\n  </tr>\n</table>\n";
        echo $mess;
        $form = get_choose_form($run = "onChooseImproved",
          $array = $_SESSION['comparison_models'],
          $button_text = "Run MolProbity Compare &gt;",
          $num_choice = 1, $choice_name = "Improved");
        echo $form;
      } elseif($_SESSION['mpc_setup'] == 'choose_chains2') {
        unset($_SESSION['mpc_setup']);
        echo "<h3>Select the chains you would like to compare or compare the";
        echo " whole models.</h3>";
        $modelID1 = $_SESSION['mpc']['model_1'];
        $modelID2 = $_SESSION['mpc']['model_2'];
        
        // put model names on the chain choices
        $choices1;
        foreach($_SESSION['chain_array1'] as $choice) 
          $choices1[$choice] = "<b>$modelID1</b> Chain <b>$choice</b>";
        $choices1["all"] = "<b>Select ALL Chains</b>";
        $choices2;
        foreach($_SESSION['chain_array2'] as $choice) 
          $choices2[$choice] = "<b>$modelID2</b> Chain <b>$choice</b>";
        $choices2["all"] =  "<b>Select ALL Chains</b>";
        
        $form = get_choose_form($run = "onChooseChains2",
          $array = $choices1,
          $button_text = "Continue &gt;", $num_choice = 2, 
          $choice_name = "Chain", $array2 = $choices2);
        echo $form;
      } else {
        echo "<h3>Select TWO models or one model with at least TWO chains to compare:</h3>";
        $form = get_checkbox_form($run = "onChooseModel",
          $array = $models,
          $button_text = "Continue &gt;",
          $is_model = true);
        echo $form;
      }
      // Rather than trying to put this in onload(), we'll do it after the form is defined.
      if($jsOnLoad)
        echo "<script language='JavaScript'>\n<!--\n$jsOnLoad\n// -->\n</script>\n";
    }
?>
<hr>
<div class='help_info'>
<h4>MolProbity Compare</h4>
<i>TODO: Help text about editing goes here</i>
</div>
<?php
  }
  else
  {
    echo "No models are available. Please <a href='".makeEventURL("onCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
    echo makeEventForm("onReturn");
    echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
    
  }
  
  echo $this->pageFooter();
}
#}}}########################################################################

#{{{ check_multitable_inputs
function check_multitable_inputs($raw_dir)
{
  $return_home = "Please return home and run all-atom contact and geometric analysis ";
  $return_home .= "on at least two models before running MolProbliy Compare.\n";
  $return_home .= makeEventForm("onReturn");
  $return_home .= "\n<p><input type='submit' name='cmd' value='Return home &gt;'></p></form>\n";
  if(!file_exists($raw_dir)) {
    $ret = "Could not find any all-atom contact and geometric analysis tables.<br>\n";
    return $ret.$return_home;
  } else {
    // Find out what models have been put through aacgeom and
    // retrieve thier names in $tables_array
    $tables_array;
    if (is_dir($raw_dir)) {
      if ($dh = opendir($raw_dir)) {
        while (($file = readdir($dh)) !== false) {
          if(strpos($file, "multi.table") !== false) $tables_array[] = $file;
        }
        closedir($dh);
      }
    }
    $tables_count = count($tables_array);
    // MolProbity Compare needs at least 1 'multi.table(S)'
    if($tables_count = 0) { 
      $ret = "Could not find any all-atom contact and geometric analysis tables.<br>\n";
      return $ret.$return_home;
    } else return $tables_array;
  }
}
#}}}

#{{{ onChooseModel
############################################################################
/**
* Documentation for this function.
*/
function onChooseModel()
{
  $req = $_REQUEST;
  if($req['cmd'] == 'Cancel') {
    pageReturn();
    return;
  }
  
  // Otherwise, moving forward:
  // get selected models
  $comparison_models;
  foreach($req as $key => $pdb) {
    if(endsWith($pdb, ".pdb"))
      $name = substr($pdb, 0, strpos($pdb, ".pdb")-strlen($pdb));
    else $name = $pdb;
    if(strpos($key, "model_4_comp_") !== false) $comparison_models[$name] = $name;
  }
  $_SESSION['comparison_models'] = $comparison_models;
  // check to see if the user selected two models
  $model_num = count($comparison_models);
  if($model_num == 0){
    $s = "You didn't choose a model. Please choose either ONE or TWO models.";
    $_SESSION['user_mpc_error'] = $s;# displays $s on the page
  }
  elseif($model_num == 1) {
    $cm_keys = array_keys($comparison_models);
    $modelID = $comparison_models[$cm_keys[0]];
    $chain_array = $_SESSION['models'][$modelID]['stats']['chainids'];
    if(count($chain_array) == 1){
      $s = $comparison_models[0]." has only ".count($chain_array)." chain.<br>";
      $s .= "Please select a model with TWO or more chains or select TWO ";
      $s .= "models.";
      $s .= "<br>p<br>";
      $_SESSION['user_mpc_error'] = $s;# displays $s on the page
    }
    else {
      $_SESSION['mpc']['MPC_model_1chain'] = $modelID;
      $_SESSION['chain_array'] = $chain_array;
      $_SESSION['mpc_setup'] = 'choose_chain';
    }
  }
  elseif($model_num == 2) {
    $cm_keys = array_keys($comparison_models);
    $modelID1 = $comparison_models[$cm_keys[0]];
    $modelID2 = $comparison_models[$cm_keys[1]];
    $chain_array1 = $_SESSION['models'][$modelID1]['stats']['chainids'];
    $chain_array2 = $_SESSION['models'][$modelID2]['stats']['chainids'];
    $_SESSION['mpc']['model_1'] = $modelID1;
    $_SESSION['mpc']['model_2'] = $modelID2;
    $_SESSION['chain_array1'] = $chain_array1;
    $_SESSION['chain_array2'] = $chain_array2;
    $_SESSION['mpc_setup'] = 'choose_chains2';
  
  }
  elseif($model_num > 2) {
    $s = "You chose $model_num models. Please choose either ONE or TWO models.";
    $_SESSION['user_mpc_error'] = $s;# displays $s on the page
  }
  // launch background job
  //pageGoto("job_progress.php");
  //launchBackground(MP_BASE_DIR."/jobs/aacgeom.php", "generic_done.php", 5);
}
#}}}########################################################################

#{{{ onChooseChains
############################################################################
/*******************************************************************************
*
*  onChooseChains is used when the user selects one model. They can select 
*  to compare chains from within the model.
*
*******************************************************************************/
function onChooseChains()
{
  $req = $_REQUEST;
  if($req['cmd'] == 'Cancel') {
    pageReturn();
    return;
  }
  
  // Otherwise, moving forward:
  if(isset($req['Chain1']) & isset($req['Chain2'])) {
    $modelID = $_SESSION['mpc']['MPC_model_1chain'];
    unset($_SESSION['mpc']['MPC_model_1chain']);
    $chain1 = $req['Chain1'];
    $chain2 = $req['Chain2'];
    $table = check_chain_choice($chain1, $chain2, $modelID);
    if(is_string($table)) $_SESSION['user_mpc_error'] = $table;
    else {
      // get MolProbity parameter hierarchy
      $mparam_hierarchy = get_mparam_hierarchy($table, trim($modelID));
      $fasta = get_chain_sequences($mh1 = $mparam_hierarchy,
        $modelID1 = trim($modelID), $mh2=false, $modelID2=false, 
        $chain1 = $chain1, $chain2 = $chain2);
      $mph1 = split_mph_by_chain($mparam_hierarchy, $chain1);
      $mph2 = split_mph_by_chain($mparam_hierarchy, $chain2);
      $modelID1 = trim($modelID)."_$chain1";
      $modelID2 = trim($modelID)."_$chain2";
      // get MolProbity Compare hierarchy
      list($mph_side_by_side, $mph_diff, $low_identity) = get_molprobity_compare_table(
        $fasta = $fasta,
        $mph1 = $mph1, $modelID1 = $modelID1, $mph2 = $mph2,
        $modelID2 = $modelID2, $chain1 = $chain1, $chain2 = $chain2);
      $rawDir = $_SESSION['dataDir'].'/raw_data';
      if(!file_exists($rawDir)) echo "ERROR!!!";
      else
      {
        $mpc_table_name = $modelID1."_".$modelID2;
        $out1 = fopen($rawDir."/$mpc_table_name-table.mpc", 'wb');
        fwrite($out1, mpSerialize($mph_side_by_side));
        fclose($out1);
        $out2 = fopen($rawDir."/$mpc_table_name-table.mpcscores", 'wb');
        fwrite($out2, mpSerialize($mph_diff));
        fclose($out2);
        // echo "<pre>";
        // print_r($mparam_hierarchy);
        // echo "</pre>";
        $_SESSION['mpc']['model_1'] = $modelID1;
        $_SESSION['mpc']['model_2'] = $modelID2;
        $a  = array($modelID1 => $modelID1, $modelID2 => $modelID2);
        $_SESSION['comparison_models'] = $a;
        $_SESSION['mpc']['mpc_table_name'] = $mpc_table_name;
        $_SESSION['mpc_setup'] = 'choose_improved';
      }
    }
  }
}
#}}}########################################################################

# {{{ onChooseChains2
############################################################################
function onChooseChains2()
/*******************************************************************************
*
*  onChooseChains2 is used when the user selects two model. They can select 
*  to compare chains from two models or compare the whole of the two models.
*
*******************************************************************************/
{
  // rewturns to choose models view
  $req = $_REQUEST;
  if($req['cmd'] == 'Cancel') return;
  
  // otherwise, move forward
  // echo "<pre>";
  // print_r($req);
  // print_r($_SESSION['mpc']);
  // echo "</pre>";
  // get models and chains
  $chain_1 = $req['Chain1'];
  $chain_2 = $req['Chain2'];
  $modelID1_ = $_SESSION['mpc']['model_1'];
  $modelID2_ = $_SESSION['mpc']['model_2'];
  list($table1, $table2) = get_2_tables(
    $modelID_1 = $modelID1_, $modelID_2 = $modelID2_);
  // check to see if the user checked 'Select All Chains'
  if($chain_1 == 'all' || $chain_2 == 'all') {
    if($chain_1 != $chain_2) {
      $err = "ERROR! You cannot compare a whole structure to a chain. Choose ";
      $err .= "'Slect ALL Chains' for both models or two different chains.";
      $_SESSION['user_mpc_error'] = $err.$chain_2.$chain_1;
      return;
    }
    else {
      $chain_1 = false;
      $chain_2 = false;
    }
  }
  if(is_string($table)) {
    $_SESSION['user_mpc_error'] = $table;
    return;
  }
  else {
    $mph1 = get_mparam_hierarchy($table1, $modelID1_);
    $mph2 = get_mparam_hierarchy($table2, $modelID2_);
    // this will get the sequence of ALL chains in one sequence as we didn't
    // provide a chain
    if($chain_1 !== false && $chain_2 !== false) {
      $modelID1_ch = $modelID1_."_".$chain_1;
      $modelID2_ch = $modelID2_."_".$chain_2;
    } else {
      $modelID1_ch = $modelID1_;
      $modelID2_ch = $modelID2_;
    }
    $fasta = get_chain_sequences($mh1 = $mph1, 
      $modelID1 = $modelID1_ch,
      $mp2 = $mph2, $modelID2 = $modelID2_ch, 
      $chain1 = $chain_1, $chain2 = $chain_2);
    if($chain_1 !== false && $chain_2 !== false) {
      $mph_1 = split_mph_by_chain($mph1, $chain_1);
      $mph_2 = split_mph_by_chain($mph2, $chain_2);
    } else {
      $mph_1 = $mph1;
      $mph_2 = $mph2;
    }
    // echo "<pre>";
    // print_r($mph_1);
    // echo "</pre>";
    
    // get ksdssp info
    $pdb1 = $_SESSION['dataDir'].'/coordinates/'.$modelID1_.'.pdb';
    $pdb2 = $_SESSION['dataDir'].'/coordinates/'.$modelID2_.'.pdb';
    $ksdssp_1 = get_ksdssp($pdb1);
    $ksdssp_2 = get_ksdssp($pdb2);
    
    // get MolProbity Compare hierarchy
    // chain1 and chain2 == false in the following because mph1 and mph2 are
    // already split by chain. chain1 and chain2 != false whe we are comparing
    // two chains within the same model.
    list($mph_side_by_side, $mph_diff, $low_identity) = get_molprobity_compare_table(
      $fasta = $fasta,
      $mph1 = $mph_1, $modelID1 = $modelID1_ch, $mph2 = $mph_2,
      $modelID2 = $modelID2_ch, $chain1 = false, $chain2 = false, 
      $ksdssp1 = $ksdssp_1, $ksdssp2 = $ksdssp_2);
    $rawDir = $_SESSION['dataDir'].'/raw_data/';
    if(!file_exists($rawDir)) return "ERROR!!!";
    else
    {
      $mpc_table_name = $modelID1_ch."_".$modelID2_ch;
      $out1 = fopen($rawDir."/$mpc_table_name-table.mpc", 'wb');
      fwrite($out1, mpSerialize($mph_side_by_side));
      fclose($out1);
      $out2 = fopen($rawDir."/$mpc_table_name-table.mpcscores", 'wb');
      fwrite($out2, mpSerialize($mph_diff));
      fclose($out2);
      if($low_identity) {
        $identity = substr(strval($low_identity), 0, 5);
        $warning = "WARNING!!<br>The alignment of the chosen model's ";
        $warning .= "isequences has an identity of ~$identity%.<br>Please ";
        $warning .= "note that MolProbity Compare's analysis is ";
        $warning .= "meaningless for unrelated structures.";
        $_SESSION['user_mpc_error'] = $warning;
      }
      $a  = array($modelID1 => $modelID1, $modelID2 => $modelID2);
      $_SESSION['mpc']['model_1'] = $modelID1;
      $_SESSION['mpc']['model_2'] = $modelID2;
      $_SESSION['comparison_models'] = $a;
      $_SESSION['mpc']['mpc_table_name'] = $mpc_table_name;
      $_SESSION['mpc_setup'] = 'choose_improved';
    }
  }
}
# }}}########################################################################

#{{{ onChooseImproved
function onChooseImproved()
{
  $req = $_REQUEST;
  if($req['cmd'] == 'Cancel') {
    // pageReturn();
    return;
  }
  foreach($req as $key => $value) 
    if(strpos($key, "Improved") !== false) $improved_model = $value;
  if($improved_model == $_SESSION['mpc']['model_1']) {
    $improved_num = 1;
    $original_num = 2;
  }
  elseif($improved_model == $_SESSION['mpc']['model_2']) {
    $improved_num = 2;
    $original_num = 1;
  }
  else {
    $s = "Trouble matching improved model to existing models.";
    $s .= "<br>".$improved_model."<br>";
    $_SESSION['user_mpc_error'] = $s;
    return; // ERROR
  }
  $modelID1 = $_SESSION['mpc']['model_1'];
  $modelID2 = $_SESSION['mpc']['model_2'];
  $mpc_table_name = $_SESSION['mpc']['mpc_table_name'];
  $_SESSION['mpc']['improved_num'] = $improved_num;
  $_SESSION['mpc']['original_num'] = $original_num;
  $entry = get_mpc_labbook_entry($mpc_table_name);
  $num['labbookEntry'] = addLabbookEntry(
    $title = "MolProbity Compare: between $modelID1 AND $modelID2",
    $text = $entry,
    $model = "$modelID1-$modelID2",
    $keywords = "MPC",
    $thumbnail = "MPC.png");
  pageGoto("generic_done.php", $num);
}
#}}}

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
