<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Gives the user a chance to download their modified PDB file.
    
INPUTS (via $_SESSION['bgjob']):
    model           ID code for model that was processed

OUTPUTS (via Post):

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
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
echo mpPageHeader("Add H with Reduce -build", "improve");

$modelID = $_SESSION['bgjob']['model'];
$model = $_SESSION['models'][$modelID];
$pdb = "$model[dir]/$model[pdb]";
$url = "$model[url]/$model[pdb]";
############################################################################
?>

<p>Your old model, <b><?php echo $model['parent']; ?></b>, has been converted into a new model, <b><?php echo $modelID; ?></b>.
Hydrogens have been added and their positions have been optimized, along with the orientation of Asn, Gln, and His sidechains.
<?php
echo "You can now ";
echo "<a href='$url'>download the optimized and annotated PDB file</a> (".formatFilesize(filesize($pdb)).").";
?>
</p>

<hr/><?php
    $labbook = openLabbookWithEdit();
    $num = $model['entry_reduce'];
    echo formatLabbookEntry($labbook[$num]);
    echo "<p><a href='notebook_edit.php?$_SESSION[sessTag]&entryNumber=$num&submitTo=improve_reduce_done.php'>Edit notebook entry</a></p>\n";
?><hr/>

<p>Now that H placement and Asn/Gln/His flips have been optimized, you may want to
<?php echo "<a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID'>"; ?>run analysis on this model</a>
to see how it has improved.
</p>

<p><a href='improve_tab.php?<?php echo $_SESSION['sessTag']; ?>'>Return to "Improve Models" page</a>
</p>
<?php echo mpPageFooter(); ?>
