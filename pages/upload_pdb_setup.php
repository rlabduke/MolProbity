<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to upload files.
    It should be accessed by pageCall()
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new UploadSetupDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class UploadSetupDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Input PDB files");
    
    echo makeEventForm("onUploadPdbFile", null, true) . "\n";
?>
<div class='side_options'>
    <b>Advanced options:</b>
    <br><label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
    <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
</div>
<h3>Upload model from local disk</h3>
<label>PDB file:
<input type="file" name="uploadFile"></label>
<br clear='all'><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
<hr>
<?php
    echo makeEventForm("onFetchPdbFile", null, true) . "\n";
?>
<h3>Fetch model from network database</h3>
<!-- Longer code field to allow NDB codes as well as PDB codes -->
<label>PDB / NDB ID code:
<input type="text" name="pdbCode" size="6" maxlength="10"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
<hr>
<h4>Upload model from local disk</h4>
<ul>
<li>This function allows you to upload a new macromolecular model from your computer's hard drive.</li>
<li>The file must be in <a href="http://www.rcsb.org/pdb/docs/format/pdbguide2.2/guide2.2_frame.html" target="_blank">PDB format</a>;
    other formats like mmCIF are not currently supported.</li>
<li>Files produced by the CNS refinement program have non-standard atom names.
    If your file comes from CNS or uses that naming convention, check the
    <i>File is from CNS refinement</i> box to have it automatically converted.</li>
<li>Some files use the segment ID to denote chains, rather than using the chain ID field.
    MolProbity can usually determine this automatically and correct for it,
    but if your file has "junk" in the segID field you should check <i>Ignore segID field</i>.</li>
</ul>

<h4>Fetch model from network database</h4>
<ul>
<li>This function allows you to retrieve a new macromolecular model from one of the common public databases.</li>
<li>You should know the ID code for the model you want. Codes are typically 4-10 alphanumeric characters.</li>
<li>Most publicly-available, experimentally-determined structures are deposited in the
    <a href="http://www.rcsb.org/pdb/" target="_blank">Protein Data Bank</a>.</li>
<li>Many RNA and DNA structures are available through the
    <a href="http://ndbserver.rutgers.edu/" target="_blank">Nucleic Acid Data Bank</a>.</li>
</ul>
<?php
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onReturn
############################################################################
/**
* Documentation for this function.
*/
function onReturn($arg, $req)
{
    pageReturn();
}
#}}}########################################################################

#{{{ onUploadPdbFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadPdbFile($arg, $req)
{
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
        return;
    }
    
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
        //echo $tmpfile;
        unlink($tmpfile);
        if($_FILES['uploadFile']['error'])
            $msg = "File upload failed with error code {$_FILES[uploadFile][error]}.";
        elseif($_FILES['uploadFile']['size'] <= 0)
            $msg = "File upload failed because of zero file size (no contents).";
        else
            $msg = "File upload failed for an unknown reason.";
        pageGoto("upload_pdb_done.php", array('errorMsg' => $msg));
    }
}
#}}}########################################################################

#{{{ onFetchPdbFile
############################################################################
/**
* Documentation for this function.
*/
function onFetchPdbFile($arg, $req)
{
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
        return;
    }
    
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['pdbCode']       = $req['pdbCode'];
    $_SESSION['bgjob']['isCnsFormat']   = false;
    $_SESSION['bgjob']['ignoreSegID']   = false;
    
    mpLog("pdb-fetch:User requested file ".$req['pdbCode']." from PDB/NDB");
    
    // launch background job
    pageGoto("job_progress.php");
    launchBackground(MP_BASE_DIR."/jobs/addmodel.php", "upload_pdb_done.php", 3);
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
