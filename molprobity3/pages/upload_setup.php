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
    echo mpPageHeader("Upload / fetch files");
    
    echo makeEventForm("onReturn");
    echo "<center><input type='submit' name='cmd' value='&lt; Cancel upload'></center>\n</form>\n";

    echo "<hr>\n";
    $this->displayPdbUploadForm();
    echo "<hr>\n";
    $this->displayPdbFetchForm();
    echo "<hr>\n";
    $this->displayMapUploadForm();
    echo "<hr>\n";
    $this->displayHetDictUploadForm();
    echo "<hr>\n";
    ?>
<center><h3>Help &amp; Additional Information</h3></center>

<h4><a name='help_pdb_upload'>Upload model from local disk</a> <a href='#pdb_upload'>[^]</a></h4>
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

<h4><a name='help_pdb_fetch'>Fetch model from network database</a> <a href='#pdb_fetch'>[^]</a></h4>
<ul>
<li>This function allows you to retrieve a new macromolecular model from one of the common public databases.</li>
<li>You should know the ID code for the model you want. Codes are typically 4-10 alphanumeric characters.</li>
<li>Most publicly-available, experimentally-determined structures are deposited in the
    <a href="http://www.rcsb.org/pdb/" target="_blank">Protein Data Bank</a>.</li>
<li>Many RNA and DNA structures are available through the
    <a href="http://ndbserver.rutgers.edu/" target="_blank">Nucleic Acid Data Bank</a>.</li>
</ul>

<h4><a name='help_map_upload'>Upload electron density map</a> <a href='#map_upload'>[^]</a></h4>
<ul>
<li>This function allows you to upload electron density maps from your computer's hard drive.</li>
<li>If you need to obtain maps for models in the PDB, try the Uppsala
<a href="http://fsrv1.bmc.uu.se/eds/" target="_blank">Electron Density Server</a>.</li>
<li>Automatic sidechain rebuilding with SSWING requires CCP4 format maps.</li>
<li>"O" (BRIX or DSN6), XPLOR, or CCP4 format maps can be viewed in KiNG.</li>
</ul>

<h4><a name='help_hetdict_upload'>Upload het dictionary</a> <a href='#hetdict_upload'>[^]</a></h4>
<ul>
<li>This function allows you to upload a custom heterogen dictionary from your computer's hard drive.</li>
<li>Het dictionaries allow us to add hydrogens to various
    small-molecule ligands ("hets") that might accompany your structure.</li>
<li>We provide a default dictionary of common hets without you having to do anything.</li>
<li>Your uploaded dictionary will be merged with this default dictionary of common hets.</li>
<li>Uploading a new dictionary will replace the previous one.</li>
<li>Dictionaries must be in <a href='http://deposit.pdb.org/het_dictionary.txt' target='_blank'>PDB format</a>.</li>
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
<h3><a name='pdb_upload'>Upload model from local disk</a> <a href='#help_pdb_upload'>[?]</a></h3>
<label>PDB-format file:
<input type="file" name="uploadFile"></label>
<br><input type="submit" name="cmd" value="Upload this file &gt;">
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

#{{{ displayPdbFetchForm - writes HTML for the PDB/NDB retrieve form
############################################################################
function displayPdbFetchForm()
{
    echo "<p>\n" . makeEventForm("onFetchPdbFile", null, true) . "\n";
?>
<!--<div class='options'>
    <b>[XXX] Advanced options:</b>
    <br><label><input type="checkbox" name="get2FoFc" value="1"> Get 2Fo-Fc map from EDS.</label>
    <br><label><input type="checkbox" name="getFoFc" value="1"> Get difference (Fo-Fc) map from EDS.</label>
</div>-->
<h3><a name='pdb_fetch'>Fetch model from network database</a> <a href='#help_pdb_fetch'>[?]</a></h3>
<!-- Longer code field to allow NDB codes as well as PDB codes -->
<label>PDB / NDB ID code:
<input type="text" name="pdbCode" size="6" maxlength="10"></label>
<br><input type="submit" name="cmd" value="Fetch this file &gt;">
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

#{{{ displayMapUploadForm
############################################################################
function displayMapUploadForm()
{
    echo "<p>\n" . makeEventForm("onUploadMapFile", null, true) . "\n";
?>
<!--<div class='options'>
    <b>Advanced options:</b>
    <br>(Specify format?)
</div>-->
<h3><a name='map_upload'>Upload electron density map</a> <a href='#help_map_upload'>[?]</a></h3>
<label>Map file:
<input type="file" name="uploadFile"></label>
<br><input type="submit" name="cmd" value="Upload this file &gt;">
<br clear='all'/>
</form>
</p>
<?php
}
#}}}########################################################################

#{{{ onUploadMapFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadMapFile($arg, $req)
{
    $mapName = censorFileName($_FILES['uploadFile']['name']); // make sure no spaces, etc.
    $mapPath = "$_SESSION[dataDir]/$mapName";
    if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
    && !file_exists($mapPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $mapPath))
    {
        // Uploaded file probably has restrictive permissions
        chmod($mapPath, (0666 & ~MP_UMASK));
        $_SESSION['edmaps'][$mapName] = $mapName;
        mpLog("edmap-upload:User uploaded an electron density map file");
        pageGoto("upload_other_done.php", array('type' => 'map', 'mapName' => $mapName));
    }
    else
    {
        if($_FILES['uploadFile']['error'])
            $msg = "File upload failed with error code {$_FILES[uploadFile][error]}.";
        elseif(file_exists($mapPath))
            $msg = "File upload failed because another file of the same name already exists.";
        elseif($_FILES['uploadFile']['size'] <= 0)
            $msg = "File upload failed because of zero file size (no contents).";
        else
            $msg = "File upload failed for an unknown reason.";
        pageGoto("upload_other_done.php", array('type' => 'map', 'errorMsg' => $msg));
    }
}
#}}}########################################################################

#{{{ displayHetDictUploadForm
############################################################################
function displayHetDictUploadForm()
{
    echo "<p>\n" . makeEventForm("onUploadHetDictFile", null, true) . "\n";
?>
<h3><a name='hetdict_upload'>Upload het dictionary</a> <a href='#help_hetdict_upload'>[?]</a></h3>
<label>Heterogen dictionary file:
<input type="file" name="uploadFile"></label>
<br><input type="submit" name="cmd" value="Upload this file &gt;">
<br clear='all'/>
</form>
</p>
<?php
}
#}}}########################################################################

#{{{ onUploadHetDictFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadHetDictFile($arg, $req)
{
    // Remove the old het dictionary:
    if(isset($_SESSION['hetdict']))
    {
        unlink($_SESSION['hetdict']);
        unset($_SESSION['hetdict']);
    }
    
    $dictName = "user_het_dict.txt";
    $dictPath = "$_SESSION[dataDir]/$dictName";
    if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
    && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $dictPath))
    {
        // Uploaded file probably has restrictive permissions
        chmod($dictPath, (0666 & ~MP_UMASK));
        exec("echo >> $dictPath"); // adds a blank line
        exec("cat ".MP_REDUCE_HET_DICT." >> $dictPath"); // appends the std dict
        $_SESSION['hetdict'] = $dictName;
        mpLog("hetdict-upload:User uploaded an custom het dictionary file");
        pageGoto("upload_other_done.php", array('type' => 'hetdict'));
    }
    else
    {
        if($_FILES['uploadFile']['error'])
            $msg = "File upload failed with error code {$_FILES[uploadFile][error]}.";
        elseif($_FILES['uploadFile']['size'] <= 0)
            $msg = "File upload failed because of zero file size (no contents).";
        else
            $msg = "File upload failed for an unknown reason.";
        pageGoto("upload_other_done.php", array('type' => 'hetdict', 'errorMsg' => $msg));
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
