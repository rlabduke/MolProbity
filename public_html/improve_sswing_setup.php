<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Allows user to make some choices about how to run the analysis.
    
INPUTS (via Get or Post):
    model           ID code for model to process

OUTPUTS (via Post):
    model           ID code for model to process
    edmap           the map file name
    cnit            a set of CNIT codes for residues to process

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ makeMapChooser - creates a combo box for ED maps
############################################################################
/**
*/
function makeMapChooser($chooserName)
{
    $maps = $_SESSION['edmaps'];
    if(is_array($maps))
    {
        $s = "";
        $s .= "<select name='$chooserName'>\n";
        foreach($maps as $map)
            $s .= "  <option value='$map'>$map</option>\n";
        $s .= "</select>\n";
    }
    else
    {
        $s = "<span class='inactive'>No maps available (<a href='upload_tab.php?$_SESSION[sessTag]'>get maps</a>)</span>\n";
    }
    return $s;
}
#}}}########################################################################

#{{{ makeResidueChooser - creates a list of residues to choose from
############################################################################
/**
*/
function makeResidueChooser($modelID, $checkboxName)
{
    $model = $_SESSION['models'][$modelID];
    $all_res = listProteinResidues("$model[dir]/$model[pdb]");
    
    if(modelDataExists($model, "clash.data"))
    {
        $file = "$model[dir]/$model[prefix]clash.data";
        $clash = loadClashlist($file);
        $clash_outliers = array_keys(findClashOutliers($clash));
    }
    else $clash_outliers = array();
    
    if(modelDataExists($model, "rama.data"))
    {
        $file = "$model[dir]/$model[prefix]rama.data";
        $rama = loadRamachandran($file);
        $rama_outliers = array_keys(findRamaOutliers($rama));
    }
    else $rama_outliers = array();
    
    if(modelDataExists($model, "rota.data"))
    {
        $file = "$model[dir]/$model[prefix]rota.data";
        $rota = loadRotamer($file);
        $rota_outliers = array_keys(findRotaOutliers($rota));
    }
    else $rota_outliers = array();
    
    if(modelDataExists($model, "cbdev.data"))
    {
        $file = "$model[dir]/$model[prefix]cbdev.data";
        $cbdev = loadCbetaDev($file);
        $cb_outliers = array_keys(findCbetaOutliers($cbdev));
    }
    else $cb_outliers = array();
    
    // List of CNIT codes for all outlier residues
    $tmp = array_merge($clash_outliers, $rama_outliers, $rota_outliers, $cb_outliers);
    // Convert into a set (keys == values)
    $all_outliers = array();
    foreach($tmp as $res) $all_outliers[$res] = $res;
    
    $s = "<table width='100%' border='0'><tr valign='top'><td width='25%'>\n";
    $i = 0;
    $col = ceil(count($all_res)/4);
    foreach($all_res as $res)
    {
        if(++$i > $col)
        {
            $s .= "</td><td width='25%'>\n";
            $i = 1;
        }

        if(isset($all_outliers[$res]))
            $s .= "<br><input type='checkbox' name='{$checkboxName}[{$res}]' value='$res' checked><b>$res</b></input>\n";
        else
            $s .= "<br><input type='checkbox' name='{$checkboxName}[{$res}]' value='$res'>$res</input>\n";
    }
    $s .= "</td></tr></table>\n";
    return $s;
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Refit sidechains with SSWING", "improve");

$modelID = $_REQUEST['model'];
############################################################################
?>

<!-- <p>Reduce is a program for adding hydrogens to a Protein DataBank (PDB) molecular structure file.
Hydrogens are added in standardized geometry with combinatorial optimization of the orientations
of OH, SH, NH<sub>3</sub><sup>+</sup>, Met methyls, Asn and Gln sidechain amides, and His rings.
Both proteins and nucleic acids can be processed.
HET groups can also be processed as long as the atom connectivity is provided;
a slightly modified version of the connectivity table published by the PDB - reduce_het_dict.txt - is included.
The program is described in
<a href="http://www.ncbi.nlm.nih.gov:80/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;list_uids=9917408&amp;dopt=Abstract" target="_blank">Word, et al. (1999)</a>
"Asparagine and glutamine: using hydrogen atom contacts in the choice of sidechain amide orientation" J. Mol. Biol. <b>285</b>, 1733-1745.
</p>

<p>A stand-alone version of Reduce, along with the source code, can be obtained for free from
<a href="http://kinemage.biochem.duke.edu/software/reduce.php" target="_blank">kinemage.biochem.duke.edu</a>.
</p> -->

<form method='post' action='improve_sswing_launch.php'>
<?php
    echo postSessionID();
    echo "<input type='hidden' name='model' value='$modelID'>\n";
    echo "<br>Model: $modelID\n";
    echo "<br>CCP4 map: ".makeMapChooser("edmap")."\n";
    echo "<p>Residues:\n";
    echo makeResidueChooser($modelID, "cnit");
    echo "</p>\n";
?>
<p><input type='submit' name='cmd' value='Start refitting sidechains'>
</form>

<?php echo mpPageFooter(); ?>
