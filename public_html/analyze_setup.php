<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Allows user to make some choices about how to run the analysis.
    
INPUTS (via Get or Post):
    model           ID code for model to process

OUTPUTS (via Post):
    model           ID code for model to process
    opts[doRama]        a flag to create Ramachandran plots
    opts[doRota]        a flag to find bad rotamers
    opts[doCbeta]       a flag to make 2- and 3-D Cbeta deviation plots
    opts[doAAC]         a flag to make all-atom contact kinemages
    opts[doMultiKin]    a flag to make the multi-criterion kinemage
    opts[doMultiChart]  a flag to make the multi-criterion chart
    opts[doAll]         a flag to do all of the above

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


$model = $_REQUEST['model'];
############################################################################
?>

<form method='post' action='analyze_launch.php'>
<?php
    echo postSessionID();
    echo "<input type='hidden' name='model' value='$model'>\n";
?>
<p><table border='0' width='100%'>
<tr><td>
    <h3>Don't need H</h3>
    <input type='checkbox' name='opts[doRama]' value='1'>Ramachandran plots (protein backbone)
    <br><input type='checkbox' name='opts[doRota]' value='1'>Rotamer analysis (protein sidechain)
    <br><input type='checkbox' name='opts[doCbeta]' value='1'>C-beta deviation (protein sidechain)
</td><td>
    <h3>Do need H</h3>
    <input type='checkbox' name='opts[doAAC]' value='1'>All-atom contacts (any macromolecule)
    <br><input type='checkbox' name='opts[doMultiKin]' value='1'>Multi-criterion kinemage (protein)
    <br><input type='checkbox' name='opts[doMultiChart]' value='1'>Multi-criterion chart (protein)
</td></tr>
<tr><td colspan='2' align='center'>
    <input type='checkbox' name='opts[doAll]' value='1'>Do all of the above
</td></tr>
</table>
<p><input type='submit' name='cmd' value='Start analysis'>
</form>

<?php echo mpPageFooter(); ?>
