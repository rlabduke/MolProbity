<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page should display the results of our analysis to the user.

INPUTS (via Get or Post):
    model           ID code for model to process

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
echo mpPageHeader("Analysis results", "analyze");

$modelID = $_REQUEST['model'];
$model = $_SESSION['models'][$modelID];

// Count *number* of outliers for each class
$worst = $_SESSION['models'][$modelID]['badRes'];
$cbdev_outliers = count($_SESSION['models'][$modelID]['badCbeta']);
$rota_outliers = count($_SESSION['models'][$modelID]['badRota']);
$rama_outliers = count($_SESSION['models'][$modelID]['badRama']);
$clash_outliers = count($_SESSION['models'][$modelID]['badClash']);

echo "<p><a href='analyze_tab.php?$_SESSION[sessTag]'>Done</a>\n";
echo "<hr />\n";
if(isset($model['parent'])) // this model is derived from an uploaded one
{
    echo "You can COMPARE THIS MODEL TO ITS PREDECESOR to see how much it's been improved.\n";
}
else // this model is an original upload
{
    echo "You can fix some of these problems automatically by letting Reduce OPTIMIZE HYDROGRENS AND FLIP ASN/GLN/HIS.\n";
}
############################################################################
?>
<hr />
<h2>Multi-criterion display</h2>
This single display presents all of the core validation criteria at once,
allowing you to identify clusters of problems in the structure.
<p><ul>
<li>
    <?php if(modelDataExists($model, "multi.kin")) { ?>
        <?php echo linkModelKin($model, "multi.kin"); ?>
        <br/><i>All-atom contacts, Ramachandran &amp; rotamer outliers, C-beta deviations,
        and alternate conformations are highlighted in the 3-D structure.
        TODO: link to complete tutorial on multicrit displays.
        </i>
    <?php } else { ?>
        <i>Multi-criterion kinemage has not been generated.</i>
    <?php } ?>
</li>
<li>
    <?php if(modelDataExists($model, "multi.chart")) { ?>
        <a href=''>A link to the multicrit chart</a>
        <br /><i>TODO: Jeremy Block is supposed to develop this chart.
        A brief summary of the number of outliers and percentages should appear here.
        </i>
    <?php } else { ?>
        <i>Multi-criterion chart has not been generated.</i>
    <?php } ?>
</li>
</ul></p>

<?php if(modelDataExists($model, "multi.chart")) readfile("$model[dir]/$model[prefix]multi.chart"); ?>

<hr />
The multi-criterion display presents results of all of the validation tests.
The kinemages below show the same information in different ways or in more detail.
<hr />

<h3>All-atom contacts</h3>
TODO: link to more info about all-atom contacts.
<p><ul>
<?php if(modelDataExists($model, "aac.kin")) { ?>
    <li>
        <b><?php echo $clash_outliers; ?> clashing residues</b>
    </li>
    <li>
        <?php echo linkModelKin($model, "aac.kin"); ?>
        <br/><i>Sidechain clashes are most common in models of proteins;
        mainchain clashes are most common in models of RNA.
        </i>
    </li>
<?php } else { ?>
    <li><i>All-atom contacts have not been generated.</i></li>
<?php } ?>
</ul></p>

<h3>Ramachandran plots</h3>
The Ramachandran plot identifies protein residues in illegal backbone conformations,
based on the phi and psi dihedral angles.
TODO: link to more info about Ramachandran plots.
<p><ul>
<?php if(modelDataExists($model, "rama.kin") && modelDataExists($model, "rama.pdf")) { ?>
    <li>
        <b><?php echo $rama_outliers; ?> outliers</b>
    </li>
    <li>
        <?php echo linkModelKin($model, "rama.kin"); ?>
        <br/><i>Click points to identify; animate to see general, Gly, Pro, pre-Pro plots.
        Summary statistics printed in kinemage text window.
        </i>
    </li>
    <li>
        <?php echo linkModelDownload($model, "rama.pdf"); ?>
        <br /><i>You can also use Mage or KiNG to generate customized PostScript
        or PDF renderings of the Ramachandran kinemage.
        </i>
    </li>
<?php } else { ?>
    <li><i>Ramachandran plots have not been generated.</i></li>
<?php } ?>
</ul></p>

<h3>Rotamers</h3>
Refer to the multi-criterion displays to identify bad rotamers.
TODO: link to more info about rotamers.
<?php
    if(modelDataExists($model, "rota.data"))
    {
        echo "<ul><li><b>$rota_outliers outliers</b></li></ul>\n";
    }
?>

<h3>C-beta deviations</h3>
TODO: link to more info about C-beta deviations
<p><ul>
<?php if(modelDataExists($model, "cb2d.kin") && modelDataExists($model, "cb3d.kin")) { ?>
    <li>
        <b><?php echo $cbdev_outliers; ?> outliers</b>
    </li>
    <li>
        <?php echo linkModelKin($model, "cb2d.kin"); ?>
        <br/><i>2-D representation plots deviations for whole molecule as scatter around the ideal C-beta position.
        </i>
    </li>
    <li>
        <?php echo linkModelKin($model, "cb3d.kin"); ?>
        <br/><i>3-D representation in which deviations are represented by gold balls.
        Larger balls represent larger deviations; each is centered at the ideal C-beta position.
        </i>
    </li>
<?php } else { ?>
    <li><i>C-beta deviation plots have not been generated.</i></li>
<?php } ?>
</ul></p>
<?php echo mpPageFooter(); ?>
