<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Allows user to make some choices about how to run the analysis.
    
INPUTS (via Get or Post):
    model           ID code for model to process

OUTPUTS (via Post):
    model           ID code for model to process
    opts[]          an array of options for the background job

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
echo mpPageHeader("Analyze model quality", "analyze");


$modelID = $_REQUEST['model'];
$model = $_SESSION['models'][$modelID];

// Respond to requests for more/fewer options
if(isset($_REQUEST['moreOpts_analyze']))
    $_SESSION['moreOpts']['analyze'] = $_REQUEST['moreOpts_analyze'];
############################################################################
?>

<p>Description of what is done...

<p>All-atom contact analysis relies on the presence of explicit hydrogen atoms in the model.
<?php if($model['isReduced']) { ?>
    Your file already has all of its H present, so no changes will be made.
<?php } else { ?>
    As necessary, Reduce will <b>automatically generate any H</b> that may be missing from your model.
    They will be optimized as much as possible without moving any pre-existing H or other atoms,
    but a better placement may be obtained by <a href='improve_tab.php?<?php echo $_SESSION['sessTag']; ?>'>running Reduce -build</a>.
<?php } ?>

<form method='post' action='analyze_launch.php'>
<?php
    echo postSessionID();
    echo "<input type='hidden' name='model' value='$modelID'>\n";
?>
<?php if($_SESSION['moreOpts']['analyze'] || $_SESSION['moreOpts']['all']) { ?>
    <p><table border='0' width='100%'>
    <tr><td>
        <h3>Don't need H</h3>
        <input type='checkbox' name='opts[doRama]' value='1'>Ramachandran plots <small>(protein backbone)</small>
        <br><input type='checkbox' name='opts[doRota]' value='1'>Rotamer analysis <small>(protein sidechain)</small>
        <br><input type='checkbox' name='opts[doCbeta]' value='1'>C-beta deviation <small>(protein sidechain)</small>
    </td><td>
        <h3>Do need H</h3>
        <input type='checkbox' name='opts[doAAC]' value='1'>All-atom contacts <small>(any macromolecule)</small>
        <br><input type='checkbox' name='opts[doMultiKin]' value='1'>Multi-criterion kinemage <small>(protein)</small>
        <br><input type='checkbox' name='opts[doMultiChart]' value='1'>Multi-criterion chart <small>(protein)</small>
    </td></tr>
    <tr><td colspan='2' align='center'>
        <input type='checkbox' name='opts[doAll]' value='1'>Do all of the above
    </td></tr>
    </table>
<?php } else {
    echo "<input type='hidden' name='opts[doAll]' value='1'>\n";
}
if(!$_SESSION['moreOpts']['all'])
{
    if($_SESSION['moreOpts']['analyze'])
        echo "<p><a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID&moreOpts_analyze=0'>Fewer options</a>\n";
    else
        echo "<p><a href='analyze_setup.php?$_SESSION[sessTag]&model=$modelID&moreOpts_analyze=1'>More options</a>\n";
}
?>
<p><input type='submit' name='cmd' value='Start analysis'>
</form>

<?php echo mpPageFooter(); ?>
