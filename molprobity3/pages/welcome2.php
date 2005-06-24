<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
require_once(MP_BASE_DIR.'/pages/upload_pdb_setup.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class welcome2_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    //echo mpPageHeader("Welcome!", "welcome2");
    //echo "<center><h2>MolProbity:<br>Macromolecular Structure Validation</h2></center>\n";
    echo mpPageHeader("MolProbity:<br>Macromolecular Structure Validation", "welcome2");
    echo makeEventForm("onAction", null, true) . "\n";
?>
<table border='0' width='100%'>
<tr><td align='center' width='35%'>PDB/NDB code</td><td align='center' width='35%'>Upload file</td><td width='10%'></td><td width='20%'></td></tr>
<tr>
    <td align='center'><input type="text" name="pdbCode" size="6" maxlength="10"></td>
    <td align='center'><input type="file" name="uploadFile"></td>
    <td><input type="submit" name="cmd" value="Go &gt;"></td>
    <td><?php echo "<a href='".makeEventURL("onNavBarCall", "upload_pdb_setup.php")."'>[more options]</a>"; ?></td>
</tr>
<tr><td height='12' colspan='4'><!-- vertical spacer --></td></tr>
<?php
    if(count($_SESSION['models']) > 0)
    {
        echo "<tr>";
        echo "<td colspan='2'>Working model: ";
        $submit_script = 'document.forms[0].elements("cmd")[1].click();';
        echo "<select name='workingModel' onchange='$submit_script'>\n";
        foreach($_SESSION['models'] as $id => $model)
        {
            if($_SESSION['lastUsedModelID'] == $id) $selected = "selected";
            else                                    $selected = "";
            echo "  <option value='$id' $selected>$model[pdb] &nbsp; &nbsp; &nbsp; $model[history]</option>\n";
        }
        echo "</select>\n";
        echo "</td><td><input type='submit' name='cmd' value='Set'></td><td></td></tr>\n";
        
        echo "<tr><td height='12' colspan='4'><!-- vertical spacer --></td></tr>\n";
        $this->displayTools($context);
    }
?>
</table></form>
<?php if(count($_SESSION['models']) > 0) echo "<hr>\n"; ?>


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

#{{{ displayTools - lists links for all the different tools available
############################################################################
/**
* Tool list is also customized for the currently active PDB model.
*/
function displayTools($context)
{
    $model = $_SESSION['models'][ $_SESSION['lastUsedModelID'] ];
    
    echo "<tr valign='top'><td colspan='2'>\n"; // start large icon column
    if(!$model['isReduced'])
        echo "<a href='".makeEventURL("onNavBarCall", "reduce_setup.php")."'><img src='img/add_h.png' alt='Add hydrogens' border='0' align='middle'> Add hydrogens</a><br>\n";
    if(!$model['isReduced']) // should be just a test for H instead
        echo "<a href='".makeEventURL("onNavBarCall", "aacgeom_setup.php")."'><img src='img/ramaplot.png' alt='Geometry analysis' border='0' align='middle'> Geometry analysis only</a><br>\n";
    else
        echo "<a href='".makeEventURL("onNavBarCall", "aacgeom_setup.php")."'><img src='img/clash_rama.png' alt='All-atom contacts and geometry' border='0' align='middle'> All-atom contacts &amp; geometry</a><br>\n";
    echo "</td>\n";
        
        
    echo "<td colspan='2'>\n"; // end large; start small text column
    echo "<a href='".makeEventURL("onNavBarCall", "upload_other_setup.php")."'>Input other files</a><br />\n";
    if($model['isReduced'])
        echo "<a href='".makeEventURL("onNavBarGoto", "reduce_setup.php")."'>Add hydrogens</a><br />\n";
    echo "<a href='".makeEventURL("onNavBarCall", "sswing_setup1.php")."'>Refit sidechains</a><br />\n";
    echo "<a href='".makeEventURL("onNavBarCall", "makekin_setup.php")."'>Make simple kinemages</a><br />\n";
    echo "<a href='".makeEventURL("onNavBarCall", "interface_setup1.php")."'>Visualize interface contacts</a><br />\n";
    //echo "<br />\n";
    echo "</td></tr>\n"; // end tools columns
}
#}}}########################################################################

#{{{ onAction
############################################################################
/**
* FUNKY: This simulates being on the upload page and then calls the appropriate
* event handler depending on whether a file has been uploaded or not...
* Don't try this at home!
*/
function onAction($arg, $req)
{
    if(startsWith($req['cmd'], 'Go'))
    {
        pageCall("upload_pdb_setup.php"); // or else a later pageReturn() will screw us up!
        $upload_delegate = makeDelegateObject();
        if(isset($_FILES['uploadFile']) && $_FILES['uploadFile']['error'] != UPLOAD_ERR_NO_FILE)
            $upload_delegate->onUploadPdbFile($arg, $req);
        else
            $upload_delegate->onFetchPdbFile($arg, $req);
    }
    elseif(startsWith($req['cmd'], 'Set'))
    {
        $_SESSION['lastUsedModelID'] = $req['workingModel'];
    }
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
