<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to upload various files.

OUTPUTS (via Post):
    cmd             either "Upload this file", "Get this file",
                    or something else (guess intended action).
    uploadFile      the uploaded file (data in $_FILES['uploadFile'][...])
    isCnsFormat     true if the user thinks he has CNS atom names
    ignoreSegID     true if the user wants to never map segIDs to chainIDs
    pdbCode         the four-character PDB identifier (mixed case)
    get2FoFc        true if user wants map from EDS
    getFoFc         true if user wants map from EDS

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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

// Respond to requests for more/fewer options
if(isset($_REQUEST['moreOpts_pdbUpload']))
    $_SESSION['moreOpts']['pdbUpload'] = $_REQUEST['moreOpts_pdbUpload'];

echo mpPageHeader("Get input models", "upload");
?>


<!-- Form for uploading files or pulling from the PDB -->
<p>
<form method="post" enctype="multipart/form-data" action="upload_pdb_launch.php">
<?php echo postSessionID(); ?>
<table border="0" width="100%">
<tr align="left" valign="top"><td width="50%">
    <h3>Fetch file from network database</h3>
    <label>PDB ID code: <input type="text" name="pdbCode" size="4" maxlength="4"></label>
    <?php if($_SESSION['moreOpts']['pdbUpload'] || $_SESSION['moreOpts']['all']) { ?>
        <br><label><input type="checkbox" name="get2FoFc" value="1"> Get 2Fo-Fc map from <a href="http://fsrv1.bmc.uu.se/eds/" target="_blank">EDS</a>.</label>
        <br><label><input type="checkbox" name="getFoFc" value="1"> Get difference (Fo-Fc) map from <a href="http://fsrv1.bmc.uu.se/eds/" target="_blank">EDS</a>.</label>
    <?php } ?>
</td><td width="50%">
    <h3>Upload file from local disk</h3>
    <label>PDB-format file: <input type="file" name="uploadFile"></label>
    <?php if($_SESSION['moreOpts']['pdbUpload'] || $_SESSION['moreOpts']['all']) { ?>
        <br><label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
        <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
    <?php } ?>
</td></tr>
<tr align="left" valign="top"><td width="50%">
    <input type="submit" name="cmd" value="Get this file">
</td><td width="50%">
    <input type="submit" name="cmd" value="Upload this file">
</td></tr>
</table>
</form>

<p>
    <?php
    if(!$_SESSION['moreOpts']['all'])
    {
        if($_SESSION['moreOpts']['pdbUpload'])
            echo "<a href='upload_tab.php?$_SESSION[sessTag]&moreOpts_pdbUpload=0'>Fewer upload options</a> |\n";
        else
            echo "<a href='upload_tab.php?$_SESSION[sessTag]&moreOpts_pdbUpload=1'>More upload options</a> |\n";
    }
    ?>
    <a href="http://www.rcsb.org/pdb/" target="_blank">Protein Data Bank</a>
    | <a href="http://ndbserver.rutgers.edu/" target="_blank">Nucleic Acid Data Bank</a>
    | <a href="http://www.rcsb.org/pdb/docs/format/pdbguide2.2/guide2.2_frame.html" target="_blank">PDB file format</a>
</p>

<hr>
Upload electron density maps and NOE tables?


<!-- List of current models available -->
<?php if(count($_SESSION['models']) > 0) { ?>
<hr>
<h3>Models available for splitting:</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="2">
<?php
    $c = MP_TABLE_ALT1;
    foreach($_SESSION['models'] as $id => $model)
    {
        // Alternate row colors:
        $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
        echo " <tr bgcolor='$c' align='center'>\n";
        echo "  <td><b>$id</b></td>\n";
        echo "  <td><span class='inactive'>Split into chains</span></td>\n";
        echo "  <td><span class='inactive'>Split into MODELs</span></td>\n";
        echo "  <td><span class='inactive'>Split by alt. confs.</span></td>\n";
        echo " </tr>\n";
        echo " <tr bgcolor='$c'>\n";
        echo "  <td colspan='4'><i>$model[history]</i></td>\n";
        echo " </tr>\n";
    }
?>
</table>
<?php } ?>


<p>
<?php echo mpPageFooter(); ?>
