<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides a description of the PDB model the user just provided.
    
INPUTS (via $_SESSION['bgjob']):
    newModel        the ID of the model just added

OUTPUTS (via Post):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
$modelID = $_SESSION['bgjob']['newModel'];
$model = $_SESSION['models'][$modelID];
$pdbstats = $model['stats'];

############################################################################
if($model == null)
{
    // Start the page: produces <HTML>, <HEAD>, <BODY> tags
    echo mpPageHeader("Model retrieval failed", "upload");
    echo "For some reason, your file code not be uploaded or pulled from the network.\n";
    echo "Please check the PDB/NDB identifier code and try again.\n";
}
else // upload was OK
{
    // Start the page: produces <HTML>, <HEAD>, <BODY> tags
    echo mpPageHeader("Model $modelID added", "upload");

    $compnd = trim($pdbstats['compnd']);
    if($compnd != "")
    {
        echo "This compound is identified as the following:<br><b>$compnd</b>\n<p>";
    }
    
    echo "<ul>\n";
    
    // # of models, if any
    if($pdbstats['models'] > 1) echo "<li>This is an multi-model structure, probably from NMR, with <b>".$pdbstats['models']." distinct models</b> present.</li>\n";
    elseif(isset($pdbstats['resolution'])) echo "<li>This is a crystal structure at ".$pdbstats['resolution']." A resolution.</li>\n";
    
    // # of chains and residues
    echo "<li>".$pdbstats['chains']." chain(s) is/are present [".$pdbstats['unique_chains']." unique chain(s)]</li>\n";
    echo "<li>A total of ".$pdbstats['residues']." residues are present.</li>\n";
    
    // CA, sidechains, and H
    if($pdbstats['hbetas'] > 0 and $pdbstats['sidechains'] > 0) echo "<li>Mainchain, sidechains, and hydrogens are present.</li>\n";
    elseif($pdbstats['sidechains'] > 0) echo "<li>Mainchain and sidechains are present, but not hydrogens.</li>\n";
    elseif($pdbstats['nucacids'] == 0) echo "<li><b>Only C-alphas</b> are present.</li>\n";
    
    // RNA / DNA
    if($pdbstats['nucacids'] > 0) echo "<li>".$pdbstats['nucacids']." nucleic acid residues are present.\n";
    
    // Hets and waters
    if($pdbstats['hets'] > 0) echo "<li>".$pdbstats['hets']." hetero group(s) is/are present.</li>\n";
    
    // Crystallographic information
    if(isset($pdbstats['refiprog'])) echo "<li>Refinement was carried out in $pdbstats[refiprog].</li>\n";
    if(isset($pdbstats['refitemp'])) echo "<li>Data was collected at $pdbstats[refitemp] K.</li>\n";
    if(isset($pdbstats['rvalue']))
    {
        echo "<li>R = $pdbstats[rvalue]";
        if(isset($pdbstats['rfree'])) echo "; R<sub>free</sub> = $pdbstats[rfree]";
        echo "</li>\n";
    }
    
    echo "</ul>\n";
    ?>
    
    <p><table border='0' width='100%'><tr valign='top'>
    <td width='50%'>
        <b><?php echo "<a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID'>"; ?>Analyze <?php echo $modelID; ?> as-is</a></b>
        <br><small>This will give an accurate picture of the current status of this model,
        but will not take advantage of MolProbity's expert systems for automatically correcting common errors.
        For some types of analysis, missing hydrogens will have to be added,
        but H-bond networks will not be optimized and existing atoms will not be moved.
        You can always optimize this model later and then repeat the analysis.
        </small>
    </td><td width='50%'>
        <b><a href='improve_tab.php?<?php echo $_SESSION['sessTag']; ?>'>Optimize <?php echo $modelID; ?> before analyzing it</a></b>
        <br><small>This will apply our expert-system tools to automatically correcting any common errors in this model.
        Afterwards, you may apply the analysis tools to assess the model quality and identify remaining problems.
        </small>
    <td>
    </tr></table></p>
<?php
}
echo mpPageFooter();
?>
