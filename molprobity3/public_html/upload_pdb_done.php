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
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
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

// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Model $modelID added", "upload");

############################################################################

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

echo "</ul>\n";
?>

<p><table border='0' width='100%'><tr valign='top'>
<td width='50%'>
    <b><?php echo "<a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID'>"; ?>Analyze this model as-is</a></b>
    <br><small>This will give an accurate picture of the current status of this model,
    but will not take advantage of MolProbity's expert systems for automatically correcting common errors.
    For some types of analysis, missing hydrogens will have to be added,
    but H-bond networks will not be optimized and existing atoms will not be moved.
    You can always optimize this model later and then repeat the analysis.
    </small>
</td><td width='50%'>
    <b><a href='improve_tab.php?<?php echo $_SESSION['sessTag']; ?>'>Optimize this model before analyzing it</a></b>
    <br><small>This will apply our expert-system tools to automatically correcting any common errors in this model.
    Afterwards, you may apply the analysis tools to assess the model quality and identify remaining problems.
    </small>
<td>
</tr></table></p>

<?php echo mpPageFooter(); ?>
