<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page should display the results of our analysis to the user.

INPUTS (via Get or Post):
    model           ID code for model to process

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
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
echo mpPageHeader("Analysis results");

$model = $_SESSION['models'][ $_REQUEST['model'] ];

echo "<p><a href='analyze_tab.php?$_SESSION[sessTag]'>Done</a>";
############################################################################
?>
<hr />
<h3>Multi-criterion display</h3>
This single display presents all of the core validation criteria at once,
allowing you to identify clusters of problems in the structure.
<p><ul>
<li>
    <?php echo linkModelKin($model, "multi.kin"); ?>
    <br/><i>All-atom contacts, Ramachandran &amp; rotamer outliers, C-beta deviations,
    and alternate conformations are highlighted in the 3-D structure.
    TODO: link to complete tutorial on multicrit displays.
    </i>
</li>
<li>
    <a href=''>A link to the multicrit chart</a>
    <br /><i>TODO: Jeremy Block is supposed to develop this chart.</i>
</li>
</ul></p>

<hr />
<h3>Ramachandran plots</h3>
The Ramachandran plot identifies protein residues in illegal backbone conformations,
based on the phi and psi dihedral angles.
TODO: link to more info about Ramachandran plots.
<p><ul>
<li>
    <?php echo linkModelKin($model, "rama.kin"); ?>
    <br/><i>Click points to identify; animate to see general, Gly, Pro, pre-Pro plots.
    Summary statistics printed in kinemage text window.
    </i>
</li>
<li>
    <?php echo "<a href='$model[url]/$model[prefix]rama.jpg' target='blank'><b>JPEG image</b></a>"; ?>
    <br /><i>All four plots on one page with outliers labeled.</i>
</li>
</ul></p>
<?php echo mpPageFooter(); ?>
