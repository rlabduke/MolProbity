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
    echo mpPageHeader("Input other files");
    echo makeEventForm("onUploadKinemage", null, true) . "\n";
?>
<h3>Upload kinemage</h3>
<label>Kinemage file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
<hr>
<?php
    echo makeEventForm("onUploadMapFile", null, true) . "\n";
?>
<h3>Upload electron density map</h3>
<label>Map file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
<hr>
<?php
    echo makeEventForm("onUploadHetDictFile", null, true) . "\n";
?>
<h3>Upload het dictionary</h3>
<label>Heterogen dictionary file:
<input type="file" name="uploadFile"></label>
<br><table border='0' width='100%'><tr>
<td><input type="submit" name="cmd" value="Upload this file &gt;"></td>
<td align='right'><input type="submit" name="cmd" value="Cancel"></td>
</tr></table>
</form>
<hr>
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
    <?php
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onUploadKinemage
############################################################################
/**
* Documentation for this function.
*/
function onUploadKinemage($arg, $req)
{
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        $kinName = censorFileName($_FILES['uploadFile']['name']); // make sure no spaces, etc.
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
        else
        {
            if($_FILES['uploadFile']['error'])
                $msg = "File upload failed with error code {$_FILES[uploadFile][error]}.";
            elseif(file_exists($kinPath))
                $msg = "File upload failed because another file of the same name already exists.";
            elseif($_FILES['uploadFile']['size'] <= 0)
                $msg = "File upload failed because of zero file size (no contents).";
            else
                $msg = "File upload failed for an unknown reason.";
            pageGoto("upload_other_done.php", array('type' => 'kin', 'errorMsg' => $msg));
        }
    }
}
#}}}########################################################################

#{{{ onUploadMapFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadMapFile($arg, $req)
{
    if($req['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        $mapName = censorFileName($_FILES['uploadFile']['name']); // make sure no spaces, etc.
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
}
#}}}########################################################################

#{{{ onUploadHetDictFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadHetDictFile($arg, $req)
{
    if($req['cmd'] == "Cancel")
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
