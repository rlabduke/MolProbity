<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is an overall "site map" or super-index page for MolProbity3 experts.
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new SitemapDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class SitemapDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Site map", "home");

    echo "<p><a href='".makeEventURL("onNavBarGoto", "notebook_main.php")."'>Lab notebook</a></p>\n";
    echo "<hr>\n";
    $this->displayPdbUploadForm();
    echo "<hr>\n";
    $this->displayPdbFetchForm();
    echo "<hr>\n";

    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ displayPdbUploadForm - writes HTML for the PDB upload form
############################################################################
function displayPdbUploadForm()
{
    echo "<p>\n" . makeEventForm("onUploadPdbFile", null, true) . "\n";
?>
<div class='options'>
    <b>Advanced options:</b>
    <br><label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
    <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
</div>
<h3>Upload model from local disk</h3>
<label>PDB-format file:
<input type="file" name="uploadFile"></label>
<br><input type="submit" name="cmd" value="Upload this file">
<br clear='all'/>
</form>
</p>
<?php
}
#}}}########################################################################

#{{{ onUploadPdbFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadPdbFile($arg, $req)
{
    // Don't try running shell cmds, etc on the uploaded file directly b/c
    // it's name could have space, .. , or other illegal chars in it!
    $tmpfile = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
    if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
    &&  move_uploaded_file($_FILES['uploadFile']['tmp_name'], $tmpfile))
    {
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob']['tmpPdb']        = $tmpfile;
        $_SESSION['bgjob']['origName']      = $_FILES['uploadFile']['name'];
        $_SESSION['bgjob']['isCnsFormat']   = $req['isCnsFormat'];
        $_SESSION['bgjob']['ignoreSegID']   = $req['ignoreSegID'];
        
        mpLog("pdb-upload:User uploaded a PDB file model from local disk");
        
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/addmodel.php", "upload_pdb_done.php", 3);
    }
    else
    {
        echo $tmpfile;
        unlink($tmpfile);
        /*
        if($_FILES['uploadFile']['error'])
            failMsg("File upload failed with error code {$_FILES[uploadFile][error]}.");
        elseif($_FILES['uploadFile']['size'] <= 0)
            failMsg("File upload failed because of zero file size (no contents).");
        else
            failMsg("File upload failed for an unknown reason.");
        */
    }
}
#}}}########################################################################

#{{{ displayPdbFetchForm - writes HTML for the PDB/NDB retrieve form
############################################################################
function displayPdbFetchForm()
{
    echo "<p>\n" . makeEventForm("onFetchPdbFile", null, true) . "\n";
?>
<div class='options'>
    <b>[XXX] Advanced options:</b>
    <br><label><input type="checkbox" name="get2FoFc" value="1"> Get 2Fo-Fc map from EDS.</label>
    <br><label><input type="checkbox" name="getFoFc" value="1"> Get difference (Fo-Fc) map from EDS.</label>
</div>
<h3>Fetch model from network database</h3>
<!-- Longer code field to allow NDB codes as well as PDB codes -->
<label>PDB/NDB ID code:
<input type="text" name="pdbCode" size="6" maxlength="10"></label>
<br><input type="submit" name="cmd" value="Fetch this file">
</form>
</p>
<?php
}
#}}}########################################################################

#{{{ onFetchPdbFile
############################################################################
/**
* Documentation for this function.
*/
function onFetchPdbFile($arg, $req)
{
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
