<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to set up options for a complex Probe run.
*****************************************************************************/
// Includes go here. For example:
    require_once(MP_BASE_DIR.'/lib/pdbstat.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class interface_setup2_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* $context['modelID'] - the ID of the model the user will work with
*/
function display($context)
{
    echo mpPageHeader("Visualize interface contacts");
    $modelID = $context['modelID'];
    $model = $_SESSION['models'][$modelID];
    
    // These lines create an HTML form that will call onRunProbe() to be called
    // when the user clicks the submit button. onRunProbe() is declared below.
    echo makeEventForm("onRunProbe");
    echo "<input type='hidden' name='modelID' value='$modelID'>\n";
    
    echo "<h3>Choose Probe options:</h3>";
?>
<table border='0' width='100%' cellspacing='0' cellpadding='4'><tr align='left' valign='top'>
<td>
<p>Mode of action: <select name="probe_mode">
    <option value="both" selected>both (src &lt;=&gt; targ)</option>
    <option value="once">once (src -&gt; targ)</option>
    <option value="self">self (src -&gt; src)</option>
    <option value="out">out (src -&gt; solvent)</option>
</select>
<p>Remove dots up to <select name="remove_dist">
    <option value="4" selected>4</option>
    <option value="3">3</option>
    <option value="2">2</option>
    <option value="1">1</option>
</select> bonds away
<p>Color dots <select name="color_by">
    <option value="gap" selected>by gap/overlap distance (cool to warm)</option>
    <option value="atom">by element (CPK colors)</option>
    <option value="base">by nucleic acid base type</option>
    <option value="gray">solid gray (for -out only)</option>
    <option value="">[suggest other solid colors for -out using Feedback]</option>
</select>
<p><input  type="checkbox" name="show_clashes" value="1" checked> Dots for clashes
<br><input type="checkbox" name="show_hbonds" value="1" checked> Dots for H-bonds
<br><input type="checkbox" name="show_vdw" value="1" checked> Dots for van der Waals contacts
<br><input type="checkbox" name="show_mc" value="1" checked> Mainchain-mainchain dots
<br><input type="checkbox" name="show_hets" value="1" checked> Dots to hets
<br><input type="checkbox" name="show_wat" value="1" checked> Dots to waters
<br><input type="checkbox" name="wat2wat" value="1"> Dots between waters
<br><input type="checkbox" name="alta" value="1" checked> 'A' conformation only
<br><input type="checkbox" name="blt40" value="1"> Atoms with B&lt;40 only
<br><input type="checkbox" name="ogt33" value="1"> Atoms with &gt;33% occupancy only
<br><input type='checkbox' name='drop_flag' value='1'> Non-selected atoms don't exist (-drop)
<br><input type="checkbox" name="elem_masters" value="1"> Masters for each element (C,H,O,...)
<p>Output file: <?php echo $model['prefix']; ?><input type="text" name="kin_suffix" value="interface" size=10 maxlength=20>.kin
<br><small>(Alphanumeric only, no spaces or symbols, &lt;20 chars)</small>
</td>
<!-- ##################################################################### -->
<td>
Just as a reminder:
<ul>
<?php
    $details = describePdbStats($model['stats'], true);
    foreach($details as $detail) echo "<li>$detail</li>\n";
?></ul>
<p><table border=1 width=100% cellspacing=0 cellpadding=4>
<tr align=center>
    <td></td>
    <td><b>"Src" pattern</b></td>
    <td><b>"Targ" pattern</b></td>
</tr>
<tr align=center>
    <td><b>Protein</b></small></td>
    <td><input type=checkbox name="src_prot"  value="protein" checked></td>
    <td><input type=checkbox name="targ_prot" value="protein" checked></td>
</tr>
<tr align=center>
    <td><b>DNA, RNA</b></small></td>
    <td><input type=checkbox name="src_nucacid"  value="dna,rna" checked></td>
    <td><input type=checkbox name="targ_nucacid" value="dna,rna" checked></td>
</tr>
<tr align=center>
    <td><b>Hets</b></td>
    <td><input type=checkbox name="src_hets"  value="het" checked></td>
    <td><input type=checkbox name="targ_hets" value="het" checked></td>
</tr>
<tr align=center>
    <td><b>Waters</b></td>
    <td><input type=checkbox name="src_waters"  value="water" checked></td>
    <td><input type=checkbox name="targ_waters" value="water" checked></td>
</tr>
<tr align=center>
    <td colspan=3 bgcolor=#999999>
        <b>- AND -</b>
        <br><small>(i.e., the logical intersection of the top half and the bottom half)</small>
    </td>
</tr>
<?php
foreach($model['stats']['chainids'] as $cid) {
    echo "<tr align='center'>\n";
    echo "<td><b>Chain &quot;$cid&quot;</b></td>\n";
    echo "<td><input type='checkbox' name='src_chains[$cid]' value='$cid'  checked></td>\n";
    echo "<td><input type='checkbox' name='targ_chains[$cid]' value='$cid'  checked></td>\n";
    echo "</tr>\n";
}
?>
</table>
</td>
</tr></table>


<p>Note that some steps are processor-intensive and may require several minutes to complete. Please be patient.
<?php
    echo "<p><table width='100%' border='0'><tr>\n";
    echo "<td><input type='submit' name='cmd' value='Run Probe &gt;'></td>\n";
    echo "<td align='right'><input type='submit' name='cmd' value='Go back'></td>\n";
    echo "</tr></table></p></form>\n";
    // Note the explicit </form> to end the form!
?>
<hr>
<div class='help_info'>
<h4>Visualizing interface contacts</h4>
<i>TODO: Help text about interfaces and Probe goes here</i>
</div>
<?php

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onRunProbe
############################################################################
/**
* Launches Prekin when the user submits the form.
*/
function onRunProbe($arg, $req)
{
    //if($req['cmd'] == 'Cancel')
    if($req['cmd'] == 'Go back')
    {
        pageGoto("interface_setup1.php");
        return;
    }
    
    // Otherwise, moving forward:
    $_SESSION['lastUsedModelID'] = $req['modelID']; // this is now the current model
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob'] = $req;
    
    mpLog("interface:Visualizing interface contacts with complex Probe run");
    // launch background job
    pageGoto("job_progress.php");
    launchBackground(MP_BASE_DIR."/jobs/interface-vis.php", "generic_done.php", 5);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
}//end of class definition
?>
