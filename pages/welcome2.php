<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// No, doing this breaks the link to this page!!
require_once(MP_BASE_DIR.'/pages/upload_pdb_setup.php'); // sets $delegate, then we overwrite it

// This variable must be defined for index.php to work! Must match class below.
$delegate = new Welcome2Delegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class Welcome2Delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Welcome!", "welcome2");
    echo "<center><h2>MolProbity:<br>Macromolecular Structure Validation</h2></center>\n";
?>
<table border='0' width='100%'>
<tr><td align='center' width='35%'>PDB/NDB code</td><td align='center' width='35%'>Upload file</td><td width='10%'></td><td width='20%'></td></tr>
<tr><?php echo makeEventForm("onGetPdbFile", null, true) . "\n"; ?>
    <td align='center'><input type="text" name="pdbCode" size="6" maxlength="10"></td>
    <td align='center'><input type="file" name="uploadFile"></td>
    <td><input type="submit" name="cmd" value="Go &gt;"></td>
    <td><?php echo "<a href='".makeEventURL("onNavBarCall", "upload_pdb_setup.php")."'>[more options]</a>"; ?></td>
</form></tr>
<tr><td height='12' colspan='4'><!-- vertical spacer --></td></tr>
<?php
    if(count($_SESSION['models']) > 0)
    {
        echo "<tr>";
        echo makeEventForm("onSetWorkingModel");
        echo "<td colspan='2'>Working model: ";
        echo "<select name='workingModel'>\n";
        foreach($_SESSION['models'] as $id => $model)
        {
            if($_SESSION['lastUsedModelID'] == $id) $selected = "selected";
            else                                    $selected = "";
            echo "  <option value='$id' $selected>$model[pdb] ... $model[history]</option>\n";
        }
        echo "</select>\n";
        echo "</td><td><input type='submit' name='cmd' value='Set &gt;'></td><td></td></form></tr>\n";
        
    }
?>
<tr><td height='12' colspan='4'><!-- vertical spacer --></td></tr>
</table>


<table border='0' width='100%'><tr valign='top'><td width='45%'>
<h3 class='nospaceafter'><?php echo "<a href='".makeEventURL("onNavBarGoto", "sitemap.php")."'>Site map</a>"; ?></h3>
<div class='indent'>Minimum-guidance interface for experienced users.</div>
<h3 class='nospaceafter'><?php echo "<a href='".makeEventURL("onNavBarGoto", "helper_xray.php")."'>Evaluate X-ray structure</a>"; ?></h3>
<div class='indent'>Typical steps for a published X-ray crystal structure
or one still undergoing refinement.</div>
<h3 class='nospaceafter'>Evaluate NMR structure</h3>
<div class='indent'>Typical steps for a published NMR ensemble
or one still undergoing refinement.</div>
<h3 class='nospaceafter'>Fix up structure</h3>
<div class='indent'>Rebuild the model to remove outliers
as part of the refinement cycle.</div>
<h3 class='nospaceafter'>Work with kinemages</h3>
<div class='indent'>Create and view interactive 3-D graphics
from your web browser.</div>
</td><td width='10%'><!-- horizontal spacer --></td><td width=='45%'>
<h3>Common questions:</h3>
<p><a href='help/about.html' target='_blank'>Cite MolProbity</a>: references for use in documents and presentations.</p>
<p><u>Installing Java</u>: how to make kinemage graphics work in your browser.</p>
<p><u>Lab notebook</u>: what's it for and how do I use it?</p>
<p><u>Adding hydrogens</u>: why are H necessary for steric evaluations?</p>
<p><u>My own MolProbity</u>: how can I run my own private MolProbity server?</p>
</td></tr></table>
<?php
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onGetPdbFile
############################################################################
/**
* FUNKY: This simulates being on the upload page and then calls the appropriate
* event handler depending on whether a file has been uploaded or not...
* Don't try this at home!
*/
function onGetPdbFile($req, $arg)
{
    pageCall("upload_pdb_setup.php"); // or else a later pageReturn() will screw us up!
    $upload_delegate = new UploadSetupDelegate();
    if(isset($_FILES['uploadFile']))    $upload_delegate->onUploadPdbFile($req, $arg);
    else                                $upload_delegate->onFetchPdbFile($req, $arg);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
