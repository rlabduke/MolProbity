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

if(modelDataExists($model, "clash.data"))
{
    $file = "$model[dir]/$model[prefix]clash.data";
    $clash = loadClashlist($file);
}
if(modelDataExists($model, "rama.data"))
{
    $file = "$model[dir]/$model[prefix]rama.data";
    $rama = loadRamachandran($file);
    $num_rama_res = count($rama);
    $num_rama_outliers = count(findRamaOutliers($rama));
    if($num_rama_res > 0)   $pct_rama_outliers = round( 100*$num_rama_outliers/$num_rama_res, 1 );
    else                    $pct_rama_outliers = 0;
}
if(modelDataExists($model, "rota.data"))
{
    $file = "$model[dir]/$model[prefix]rota.data";
    $rota = loadRotamer($file);
    $num_rota_res = count($rota);
    $num_rota_outliers = count(findRotaOutliers($rota));
    if($num_rota_res > 0)   $pct_rota_outliers = round( 100*$num_rota_outliers/$num_rota_res, 1 );
    else                    $pct_rota_outliers = 0;
}
if(modelDataExists($model, "cbdev.data"))
{
    $file = "$model[dir]/$model[prefix]cbdev.data";
    $cbdev = loadCbetaDev($file);
    $num_cb_res = count($cbdev);
    $num_cb_outliers = count(findCbetaOutliers($cbdev));
    if($num_cb_res > 0)     $pct_cb_outliers = round( 100*$num_cb_outliers/$num_cb_res, 1 );
    else                    $pct_cb_outliers = 0;
}

/*
if(isset($model['parent'])) // this model is derived from an uploaded one
{
    echo "You can COMPARE THIS MODEL TO ITS PREDECESOR to see how much it's been improved.\n";
}
else // this model is an original upload
{
    echo "You can fix some of these problems automatically by letting Reduce OPTIMIZE HYDROGRENS AND FLIP ASN/GLN/HIS.\n";
}
*/
############################################################################
?>

<h2>Quality summary</h2>
<table width='100%' border='0'>
<tr><td>Clashscore:</td><?php if(isset($clash)) echo "<td>$clash[scoreAll] <small>(all)</small></td><td>$clash[scoreBlt40] <small>(B&lt;40)</small></td>"; else echo "<td>?</td><td></td>"; ?></tr>
<tr><td>Ramachandran outliers:</td><?php if(isset($rama)) echo "<td>$num_rama_outliers</td><td>($pct_rama_outliers%)</td>"; else echo "<td>?</td><td></td>"; ?></tr>
<tr><td>Rotamer outliers:</td><?php if(isset($rota)) echo "<td>$num_rota_outliers</td><td>($pct_rota_outliers%)</td>"; else echo "<td>?</td><td></td>"; ?></tr>
<tr><td>C&beta; deviations:</td><?php if(isset($cbdev)) echo "<td>$num_cb_outliers</td><td>($pct_cb_outliers%)</td>"; else echo "<td>?</td><td></td>"; ?></tr>
</table>
<hr />

<h2>Multi-criterion display</h2>
This single display presents all of the core validation criteria at once,
allowing you to identify clusters of problems in the structure.
<p><?php
if(modelDataExists($model, "multi.kin")) echo linkModelKin($model, "multi.kin", "Kinemage");
else echo "<i>Multi-criterion kinemage has not been generated.</i>";
?></p>
<p><?php
if(modelDataExists($model, "multi.chart") && modelDataExists($model, "multiall.chart"))
{
    $badurl = "viewtext.php?$_SESSION[sessTag]&file=$model[dir]/$model[prefix]multi.chart&mode=html";
    $allurl = "viewtext.php?$_SESSION[sessTag]&file=$model[dir]/$model[prefix]multiall.chart&mode=html";
    echo "<b>Chart:</b> <a href='$badurl' target='_blank'>Bad residues</a> | <a href='$allurl' target='_blank'>All residues</a>";
}
else echo "<i>Multi-criterion charts have not been generated.</i>";
?></p>
<hr />

<h2>Additional displays</h2>
<p><ul>
<?php
    if(modelDataExists($model, "clash.data")) echo "<li>".linkModelDownload($model, "clash.data", "Clash list")."</li>\n";
    if(modelDataExists($model, "aac.kin")) echo "<li>".linkModelKin($model, "aac.kin", "All-atom contacts")."</li>\n";
    if(modelDataExists($model, "rama.kin")) echo "<li>".linkModelKin($model, "rama.kin", "Kinemage Ramachandran plot")."</li>\n";
    if(modelDataExists($model, "rama.pdf")) echo "<li>".linkModelDownload($model, "rama.pdf", "PDF Ramachandran plot")."</li>\n";
    if(modelDataExists($model, "cb2d.kin")) echo "<li>".linkModelKin($model, "cb2d.kin", "2-D C&beta; deviations")."</li>\n";
    if(modelDataExists($model, "cb3d.kin")) echo "<li>".linkModelKin($model, "cb3d.kin", "3-D C&beta; deviations")."</li>\n";
?>
</ul></p>
<hr />

<?php
echo "<p><a href='analyze_tab.php?$_SESSION[sessTag]'>Return to analysis page</a></p>\n";
echo mpPageFooter();
?>
