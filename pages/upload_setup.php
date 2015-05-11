<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to upload files.
    It should be accessed by pageCall()
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class upload_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Input PDB files");
    
    echo makeEventForm("onUploadPdbFile") . "\n";
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
</div><br><div class='pagecontent_alone'>
<?php
    echo makeEventForm("onFetchPdbFile") . "\n";
?>
<div class='side_options'>
    <b>Advanced options:</b>
    <br><label><input type="checkbox" name="biolunit" value="1"> Biol. unit (PDB only)</label>
    <br><label><input type="checkbox" name="eds_2fofc" value="1"> Get 2Fo-Fc map from EDS</label>
    <br><label><input type="checkbox" name="eds_fofc" value="1"> Get Fo-Fc map from EDS</label>
</div>
<h3>Fetch model from network database</h3>
<!-- Longer code field to allow NDB codes as well as PDB codes -->
<label>PDB / NDB ID code:
<input type="text" name="pdbCode" size="6" maxlength="10"></label>
<br clear='all'><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Retrieve this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
</div><br><div class='pagecontent_alone'>
<?php
    echo makeEventForm("onUploadKinemage") . "\n";
?>
<h3>Upload kinemage</h3>
<label>Kinemage file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
</div><br><div class='pagecontent_alone'>
<?php
    echo makeEventForm("onUploadMapFile") . "\n";
?>
<h3>Upload electron density map</h3>
<label>Map file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
</div><br><div class='pagecontent_alone'>
<?php
    echo makeEventForm("onUploadHetDictFile") . "\n";
?>
<h3>Upload het dictionary</h3>
<label>Heterogen dictionary file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
</div><br><div class='pagecontent_alone'>
<div class='help_info'>
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

<h4>Upload kinemage</h4>
<ul>
<li>This function allows you to upload kinemages from your computer's hard drive,
and then view them on-line using KiNG.</li>
<li>KiNG requires the <a href='http://www.java.com' target='_blank'>Java plugin<a>,
version 1.3 or newer. See the user manual for more details.</li>
<li>Kinemages are simple text files you can create using programs like
<a href='http://kinemage.biochem.duke.edu/software/prekin.php' target='_blank'>Prekin</a>.</li>
</ul>

<h4>Upload electron density map</h4>
<ul>
<li>This function allows you to upload electron density maps from your computer's hard drive.</li>
<li>If you need to obtain maps for models in the PDB, try the Uppsala
<a href="http://fsrv1.bmc.uu.se/eds/" target="_blank">Electron Density Server</a>.</li>
<li>Automatic sidechain rebuilding with SSWING requires CCP4 format maps.</li>
<li>"O" (BRIX or DSN6), XPLOR, or CCP4 format maps can be viewed in KiNG.</li>
</ul>

<h4>Upload het dictionary</h4>
<ul>
<li>This function allows you to upload a custom heterogen dictionary from your computer's hard drive.</li>
<li>Het dictionaries allow us to add hydrogens to various
    small-molecule ligands ("hets") that might accompany your structure.</li>
<li>We provide a default dictionary of common hets without you having to do anything.</li>
<li>Your uploaded dictionary will be merged with this default dictionary of common hets.</li>
<li>Uploading a new dictionary will replace the previous one.</li>
<li>Dictionaries must be in <a href='http://deposit.pdb.org/het_dictionary.txt' target='_blank'>PDB format</a>.</li>
</ul>
</div>
<?php
    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onUploadPdbFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadPdbFile()
{
    $req = $_REQUEST;
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
        return;
    }
    
    // Don't try running shell cmds, etc on the uploaded file directly b/c
    // it's name could have space, .. , or other illegal chars in it!
    $tmpfile = mpTempfile("tmp_pdb_");
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
        $error_codes = array(
            UPLOAD_ERR_INI_SIZE => 'upload size exceeded upload_max_filesize ('.formatFilesize(ini_get('upload_max_filesize')).') in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'upload size exceeded MAX_FILE_SIZE directive specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'the file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'no file was specified for upload'
        );
            
        $errno = $_FILES['uploadFile']['error'];
        if($errno && $error_codes[$errno])
            $msg = "File upload failed because ".$error_codes[$errno].".";
        elseif($errno)
            $msg = "File upload failed with unrecognized error code {$_FILES[uploadFile][error]}.";
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
function onFetchPdbFile()
{
    $req = $_REQUEST;
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
        return;
    }
    
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['pdbCode']       = $req['pdbCode'];
    $_SESSION['bgjob']['xray']          = $req['xray'];
    $_SESSION['bgjob']['isCnsFormat']   = false;
    $_SESSION['bgjob']['ignoreSegID']   = false;
    $_SESSION['bgjob']['biolunit']      = $req['biolunit'];
    $_SESSION['bgjob']['eds_2fofc']     = $req['eds_2fofc'];
    $_SESSION['bgjob']['eds_fofc']      = $req['eds_fofc'];
    
    mpLog("pdb-fetch:User requested file ".$req['pdbCode']." from PDB/NDB");
    
    // launch background job
    pageGoto("job_progress.php");
    launchBackground(MP_BASE_DIR."/jobs/addmodel.php", "upload_pdb_done.php", 3);
}
#}}}########################################################################

#{{{ onFetchEdsMap
############################################################################
/**
* Documentation for this function.
*/
function onFetchEdsMap()
{
    $req = $_REQUEST;
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
        return;
    }
    
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['pdbCode']       = $req['pdbCode'];
    $_SESSION['bgjob']['eds_2fofc']     = $req['eds_2fofc'];
    $_SESSION['bgjob']['eds_fofc']      = $req['eds_fofc'];
    
    // logging is done is background job
    
    // launch background job
    pageGoto("job_progress.php");
    launchBackground(MP_BASE_DIR."/jobs/fetch-edsmap.php", "generic_done.php", 3);
}
#}}}########################################################################

#{{{ onUploadKinemage
############################################################################
/**
* Documentation for this function.
*/
function onUploadKinemage()
{
    $req = $_REQUEST;
    if($_REQUEST['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        $kinName = censorFileName($_FILES['uploadFile']['name'], array('kin', 'kip')); // make sure no spaces, etc.
        $kinPath = "$_SESSION[dataDir]/".MP_DIR_KINS;
        if(!file_exists($kinPath)) mkdir($kinPath, 0777);
        $kinPath .= "/$kinName";
        if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
        && !file_exists($kinPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $kinPath))
        {
            // Uploaded file probably has restrictive permissions
            chmod($kinPath, (0666 & ~MP_UMASK));
            mpLog("kin-upload:User uploaded a kinemage file");
            pageGoto("upload_other_done.php", array('type' => 'kin', 'kinName' => $kinName));
        }
        else $this->doUploadError('kin', $kinPath);
    }
}
#}}}########################################################################

#{{{ onUploadXray
############################################################################
/**
* Documentation for this function.
*/
function onUploadXray()
{
    $req = $_REQUEST;
    if($_REQUEST['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        $xrayName = censorFileName($_FILES['uploadFile']['name'], array('mtz')); // make sure no spaces, etc.
        $xrayPath = "$_SESSION[dataDir]/".MP_DIR_XRAYDATA;
        if(!file_exists($xrayPath)) mkdir($xrayPath, 0777);
        $xrayPath .= "/$xrayName";
        $tmpfile = mpTempfile("tmp_mtz_");
        if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
        && !file_exists($xrayPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $tmpfile))
        {
            $tf = mtzFormatCorrect($tmpfile);
            if($tf) 
            {
                mpLog("mtz-upload:User uploaded an mtz file");
                copy($tmpfile, $xrayPath);
		unlink($tmpfile)
                $_SESSION['mtzs'][$xrayName] = $xrayName;
                pageGoto("upload_other_done.php", array('type' => 'xray', 'xrayName' => $xrayName));
            }
	    unlink($tmpfile)
            else $this->doUploadError('xray', $xrayPath);
        }
	unlink($tmpfile)
        else $this->doUploadError('xray', $xrayPath);
    }
}
#}}}########################################################################

# {{{ mtzFormatCorrect - checks mtz format
############################################################################
/**
* This function checks that the given mtz is the correct format
*   tmpMtz     the (temporary) file where the upload is stored.
*/
function mtzFormatCorrect($tmpMtz)
{
    $a = array();
    $pathToUtils = MP_BASE_DIR.'/bin/cctbx_utils.py';
    exec("libtbx.python $pathToUtils mtz_amplitudes_check $tmpMtz", $a);
    if($a[0] == "True") return TRUE;
    else return FALSE;
}
# }}}

#{{{ onUploadMapFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadMapFile()
{
    if($_REQUEST['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        // List of allowed map extensions taken from KiNG's EDMapPlugin
        $mapName = censorFileName($_FILES['uploadFile']['name'],
            array('map', 'omap', 'xmap', 'dn6', 'dsn6', 'ccp4', 'mbk', 'xplor', 'brix',
            'map.gz', 'omap.gz', 'xmap.gz', 'dn6.gz', 'dsn6.gz', 'ccp4.gz', 'mbk.gz', 'xplor.gz', 'brix.gz')); // make sure no spaces, etc.
        $mapPath = "$_SESSION[dataDir]/".MP_DIR_EDMAPS;
        if(!file_exists($mapPath)) mkdir($mapPath, 0777);
        $mapPath .= "/$mapName";
        if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
        && !file_exists($mapPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $mapPath))
        {
            // Uploaded file probably has restrictive permissions
            chmod($mapPath, (0666 & ~MP_UMASK));
            $_SESSION['edmaps'][$mapName] = $mapName;
            mpLog("edmap-upload:User uploaded an electron density map file");
            pageGoto("upload_other_done.php", array('type' => 'map', 'mapName' => $mapName));
        }
        else $this->doUploadError('map', $mapPath);
    }
}
#}}}########################################################################

#{{{ onUploadHetDictFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadHetDictFile()
{
    if($_REQUEST['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        // Remove the old het dictionary:
        if(isset($_SESSION['hetdict']))
        {
            unlink($_SESSION['hetdict']);
            unset($_SESSION['hetdict']);
        }
        
        $dictName = "user_het_dict.txt";
        $dictPath = "$_SESSION[dataDir]/".MP_DIR_TOPPAR;
        if(!file_exists($dictPath)) mkdir($dictPath, 0777);
        $dictPath .= "/$dictName";
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
        else $this->doUploadError('hetdict');
    }
}
#}}}########################################################################

#{{{ doUploadError
############################################################################
function doUploadError($type, $path = null)
{
    $error_codes = array(
        UPLOAD_ERR_INI_SIZE => 'upload size exceeded upload_max_filesize ('.formatFilesize(ini_get('upload_max_filesize')).') in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'upload size exceeded MAX_FILE_SIZE directive specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'the file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'no file was specified for upload'
    );
        
    $errno = $_FILES['uploadFile']['error'];
    if($errno && $error_codes[$errno])
        $msg = "File upload failed because ".$error_codes[$errno].".";
    elseif($errno)
        $msg = "File upload failed with unrecognized error code {$_FILES[uploadFile][error]}.";
    elseif($path != null && file_exists($path))
        $msg = "File upload failed because another file of the same name already exists.";
    elseif($_FILES['uploadFile']['size'] <= 0)
        $msg = "File upload failed because of zero file size (no contents).";
    else
        $msg = "File upload failed for an unknown reason.";
    pageGoto("upload_other_done.php", array('type' => $type, 'errorMsg' => $msg));
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
