<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to fix flipped Leu residues in one of their models.
    It should be accessed by pageCall()
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');
require_once(MP_BASE_DIR.'/pages/upload_setup.php');  // need to get this working to avoid code duplication

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class autofix_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID     the model ID to add H to
*   map         the ED map to use
*/
function display($context)
{
    echo $this->pageHeader("Fix Flipped Leu Residues");
    
    if(count($_SESSION['models']) > 0 && count($_SESSION['edmaps']) > 0)
    {
        // Choose a default model to select
        $lastUsedID = $context['modelID'];
        $lastUsedED = $_SESSION['lastUsedED'];

        if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];

        echo makeEventForm("onAutoFix");
        echo "<h3>Select a model to fix:</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;
        foreach($_SESSION['models'] as $id => $model)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
                $stats = $model['stats'];
                $hasProtein = ($stats['sidechains'] > 0 ? "true" : "false");
                $hasNucAcid = ($stats['nucacids'] > 0 ? "true" : "false");
                $checked = ($lastUsedID == $id ? "checked" : "");
                echo "  <td><input type='radio' name='modelID' value='$id' $checked </td>\n";
                echo "  <td><b>$model[pdb]</b></td>\n";
                echo "  <td><small>$model[history]</small></td>\n";
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<h3>Select a map/mtz for density comparison :</h3>";
        echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
        $c = MP_TABLE_ALT1;

        foreach($_SESSION['edmaps'] as $map => $edmap)
        {
            // Alternate row colors:
            $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
            echo " <tr bgcolor='$c'>\n";
               $checked = ($lastUsedED  == $edmap ? "checked" : "");
               echo "  <td><input type='radio' name='map' value='$edmap' $checked></td>\n";
               echo "  <td><b>$edmap</b></td>\n";
               echo "  <td><small>  </small></td>\n"; // maybe add $edmap[history]
            echo " </tr>\n";
        }
        echo "</table></p>\n";

        echo "<p><table width='100%' border='0'><tr>\n";
        echo "<td><input type='submit' name='cmd' value='Start Fixing Leucines &gt;'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>\n";
        echo "</tr></table></p></form>\n";

        echo "<p>Mtz files with map coefficients are preferred because Real-space correlation calculations currently require an mtz</p>";
?>
<script type='text/javascript'>

// This nifty function means we won't override other ONLOAD handlers
function windowOnload(f)
{
    var prev = window.onload;
    window.onload = function() { if(prev) prev(); f(); }
}

// On page load, find the selected model and sync us to its state
windowOnload(function() {
    var models = document.getElementsByName('modelID');
    for(var i = 0; i < models.length; i++)
    {
        if(models[i].checked) models[i].onclick();
    }
});
</script>
<hr>
<div class='help_info'>
<h4>Fixing flipped Leucines</h4>
<i>TODO: Help text about AutoFix goes here</i>
</div>
<?php
    }
    elseif(count($_SESSION['models']) == 0) // not a typical route
    {
        echo "No models are available. Please <a href='".makeEventURL("onCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
        echo makeEventForm("onReturn");
        echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
        
    }
    else
    {
        echo "No electron density maps are available. Please upload a CCP4-format map or mtz with map coefficients in order to continue.\n";
        echo makeEventForm("onReturn");
        
        echo makeEventForm("onFetchEdsMap"); 
        echo "<div class='side_options'>";
        echo "    <b>Advanced options:</b>";
//        echo "    <br><label><input type='checkbox' name='biolunit' value='1'> Biol. unit (PDB only)</label>";
        echo "    <br><label><input type='checkbox' name='eds_mtz' value='1' checked> Get mtz from EDS</label>";
        echo "    <br><label><input type='checkbox' name='eds_2fofc' value='1' checked> Get 2Fo-Fc map from EDS</label>";
        echo "    <br><label><input type='checkbox' name='eds_fofc' value='1'> Get Fo-Fc map from EDS</label>";
        echo "</div>";
        echo "<h3>Fetch map from network database</h3>";
            // <!-- Longer code field to allow NDB codes as well as PDB codes -->
        echo "<label>PDBID code:";
        echo "<input type='text' name='pdbCode' size='6' maxlength='10'></label>";
        echo "<br clear='all'><table border='0' width='100%'><tr>";
        echo "<td><input type='submit' name='cmd' value='Retrieve this file &gt;'></td>";
        echo "</tr></table>";
        echo "</form>";
        echo "<br><div class='pagecontent_alone'>";


        echo makeEventForm("onUploadMapFile");
        echo "<h3>Upload electron density map</h3>";
        echo "<label>Map file:";
        echo "<input type='file' name='uploadFile'></label>";
        echo "<br><table border='0' width='100%'><tr>";
        echo "<td><input type='submit' name='cmd' value='Upload this file &gt;'></td>";
        echo "</tr></table>";

        echo makeEventForm("onUploadMtzFile");
        echo "<h3>Upload mtz with map coefficients</h3>";
        echo "<label>Mtz file:";
        echo "<input type='file' name='uploadFile'></label>";
        echo "<br><table border='0' width='100%'><tr>";
        echo "<td><input type='submit' name='cmd' value='Upload this file &gt;'></td>";

        echo "<td align='right'><input type='submit' name='cmd' value='Cancel'></td>";
        echo "</tr></table>";
        echo "</form>";

        echo "<p>Mtz files with map coefficients are preferred because Real-space correlation calculations currently require an mtz</p>";
    }
    
    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onAutoFix
############################################################################
/**
* Documentation for this function.
*/
function onAutoFix()
{
    $req = $_REQUEST;
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }
    
    // Otherwise, moving forward:
    if(isset($req['modelID']) && isset($req['map']))
    {
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob']['modelID']       = $req['modelID'];
        $_SESSION['bgjob']['edmap']         = $req['map'];
        
            mpLog("autoFix:User ran autoFix to refit misfit Leucines");
            // launch background job
            pageGoto("job_progress.php");
            launchBackground(MP_BASE_DIR."/jobs/autofix.php", "autofix_choose.php", 5);
    }
    else
    {
        $context = getContext();
        if(isset($req['modelID']))  $context['modelID'] = $req['modelID'];
        if(isset($req['map']))      $context['map']     = $req['map'];
        setContext($context);
    }
}
#}}}########################################################################

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
            $_SESSION['lastUsedED'] = $mapName;
            mpLog("edmap-upload:User uploaded an electron density map file");
            pageGoto("upload_autofix_map.php", array('type' => 'map', 'mapName' => $mapName));
        }
        else $this->doUploadError('map', $mapPath);
    }
}
#}}}########################################################################

#{{{ onUploadMtzFile
############################################################################
/**
* Documentation for this function.
*/
function onUploadMtzFile()
{
    if($_REQUEST['cmd'] == "Cancel")
    {
        pageReturn();
    }
    else
    {
        $mapName = censorFileName($_FILES['uploadFile']['name'],
            array('mtz', 'mtz.gz')); // make sure no spaces, etc.
        $mapPath = "$_SESSION[dataDir]/".MP_DIR_EDMAPS;
        if(!file_exists($mapPath)) mkdir($mapPath, 0777);
        $mapPath .= "/$mapName";
        if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
        && !file_exists($mapPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $mapPath))
        {
            // Uploaded file probably has restrictive permissions
            chmod($mapPath, (0666 & ~MP_UMASK));
            $_SESSION['edmaps'][$mapName] = $mapName;
            $_SESSION['lastUsedED'] = $mapName;
            mpLog("edmap-upload:User uploaded an electron density map file");
            pageGoto("upload_autofix_map.php", array('type' => 'map', 'mapName' => $mapName));
        }
        else $this->doUploadError('map', $mapPath);
    }
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
    $_SESSION['bgjob']['eds_mtz']       = $req['eds_mtz'];
    $_SESSION['bgjob']['eds_2fofc']     = $req['eds_2fofc'];
    $_SESSION['bgjob']['eds_fofc']      = $req['eds_fofc'];

    // logging is done in background job

    // launch background job
    pageGoto("job_progress.php");

    launchBackground(MP_BASE_DIR."/jobs/fetch-edsmap.php", "fetch_eds_autofix_done.php", 3);
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
