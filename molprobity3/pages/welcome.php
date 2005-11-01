<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');
require_once(MP_BASE_DIR.'/lib/browser.php');

// It may be bad form, but we hijack functions from these pages to avoid
// duplicating the work they do. This must be done very carefully!
require_once(MP_BASE_DIR.'/pages/upload_setup.php');
require_once(MP_BASE_DIR.'/pages/file_browser.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class welcome_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Main page", "welcome");
    $this->displayWarnings($context);

    if(count($_SESSION['models']) > 0 && $_SESSION['lastUsedModelID'])
    {
        echo "<h5 class='welcome'>Suggested Tools (<a href='".makeEventURL("onNavBarGoto", "sitemap.php")."'>all tools</a>)</h5>\n";
        echo "<div class='indent'>\n";
        echo makeEventForm("onSetWorkingModel", null, true) . "\n";
        $this->displayModels($context);
        echo "</form>\n";
        
        if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
            $this->displayEnsembleTools($context);
        else
            $this->displayModelTools($context);
        
        echo "</div></div>\n<br>\n<div class='pagecontent'>\n";
        $this->displayEntries($context);
        echo "</div>\n<br>\n<div class='pagecontent'>\n";
        $this->displayFiles($context);
        echo "</div>\n<br>\n<div class='pagecontent'>\n";
    }
    
    $this->displayUpload($context);
    echo "</div>\n<br>\n<div class='pagecontent'>\n";
?>
<table border='0' width='100%'><tr valign='top'><td width='45%'>
<h3>Walk-thrus &amp; tutorials:</h3>
<p><b><?php echo "<a href='".makeEventURL("onNavBarGoto", "helper_xray.php")."'>Evaluate X-ray structure</a>"; ?>:</b>
Typical steps for a published X-ray crystal structure
or one still undergoing refinement.</p>
<p><b>Evaluate NMR structure:</b>
Typical steps for a published NMR ensemble
or one still undergoing refinement.</p>
<p><b>Fix up structure:</b>
Rebuild the model to remove outliers
as part of the refinement cycle.</p>
<p><b>Work with kinemages:</b>
Create and view interactive 3-D graphics
from your web browser.</p>
<p><b><?php echo "<a href='".makeEventURL("onNavBarGoto", "sitemap.php")."'>Site map</a>"; ?>:</b>
Minimum-guidance interface for experienced users.</p>


</td><td width='10%'><!-- horizontal spacer --></td><td width=='45%'>


<h3>Common questions:</h3>
<p><b><a href='help/about.html' target='_blank'>Cite MolProbity</a></b>:
    <small>Simon C. Lovell, Ian W. Davis, W. Bryan Arendall III, Paul I. W. de
    Bakker, J. Michael Word, Michael G. Prisant, Jane S. Richardson, David C. Richardson (2003)
    <a href="http://kinemage.biochem.duke.edu/validation/valid.html" target="_blank">Structure
    validation by C-alpha geometry: phi, psi, and C-beta deviation.</a>
    Proteins: Structure, Function, and Genetics. <b>50</b>: 437-450.</small></p>
<p><b><a href='help/java.html' target='_blank'>Installing Java</a></b>: how to make kinemage graphics work in your browser.</p>
<p><b>Lab notebook</b>: what's it for and how do I use it?</p>
<p><b>Adding hydrogens</b>: why are H necessary for steric evaluations?</p>
<p><b>My own MolProbity</b>: how can I run my own private MolProbity server?</p>
</td></tr></table>
<?php
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ displayWarnings - check browser, Java enabled
############################################################################
function displayWarnings($context)
{
    // 1. Check which browser the user is using...
    $bad_browsers = array('MSIE');
    $tested_browsers = array('Firefox', 'Safari');
    $br = recognizeUserAgent();
    if(in_array($br['browser'], $bad_browsers)) $err = "has bugs that keep it from working well with MolProbity";
    //elseif(! in_array($br['browser'], $tested_browsers)) $err = "has not been tested with MolProbity";
    
    if($err)
    {
        echo "<div class='alert'>\n";
        echo "Your browser, $br[browser] $br[version], $err.\n";
        echo "If pages display incorrectly or you experience other problems, you may want to \n";
        echo "try a browser like <a href='http://www.mozilla.org/' target='_blank'>Firefox</a> instead.\n";
        echo "</div><br>\n";
    }
    
    // 2. Check for Java being enabled.
    // This doesn't verify the version, but is better than nothing...
?><script language='JavaScript'>
<!--
    if(!navigator.javaEnabled())
    {
        document.writeln("<div class='alert'>");
        document.writeln("Java is not enabled -- you will not be able to use KiNG interactive graphics.");
        document.writeln("<br><a href='help/about.html' target='_blank'>Click here for help.</a>");
        document.writeln("</div><br>");
    }
// -->
</script>
<?php
}
#}}}########################################################################

#{{{ displayModels - lists available model(s) and ensemble(s), if any
############################################################################
function displayModels($context)
{
    if(count($_SESSION['models']) > 1)
    {
        echo "Currently working on: ";
        $submit_script = 'document.forms[0].elements("cmd").click();';
        echo "<select name='workingModel' onchange='$submit_script'>\n";
        foreach($_SESSION['ensembles'] as $id => $model)
        {
            if($_SESSION['lastUsedModelID'] == $id) $selected = "selected";
            else                                    $selected = "";
            echo "  <option value='$id' $selected>$model[pdb] &nbsp; &nbsp; &nbsp; $model[history]</option>\n";
        }
        foreach($_SESSION['models'] as $id => $model)
        {
            if($_SESSION['lastUsedModelID'] == $id) $selected = "selected";
            else                                    $selected = "";
            echo "  <option value='$id' $selected>$model[pdb] &nbsp; &nbsp; &nbsp; $model[history]</option>\n";
        }
        echo "</select>\n";
        echo "<input type='submit' name='cmd' value='Set'>\n";
    }
    elseif(count($_SESSION['models']) > 0)
    {
        $model = reset($_SESSION['models']);
        echo "Currently working on: <b>$model[pdb]</b>\n";
    }
}
#}}}########################################################################

#{{{ displayModelTools - lists links for all the different tools available
############################################################################
/**
* Tool list is also customized for the currently active PDB model.
*/
function displayModelTools($context)
{
    // We already know lastUsedModelID is set and refers to a model, not an ensemble
    $model = $_SESSION['models'][ $_SESSION['lastUsedModelID'] ];
    // "rel" (relevance) is 2 for major, 1 for minor, 0 for not shown.
    $tools = array(
        'reduce'    => array('desc' => 'Add hydrogens', 'page' => 'reduce_setup.php', 'rel' => 1, 'img' => 'add_h.png'),
        'aacgeom'   => array('desc' => 'Analyze all-atom contacts and geometry', 'page' => 'aacgeom_setup.php', 'rel' => 1, 'img' => 'clash_rama.png'),
        'geomonly'  => array('desc' => 'Analyze geometry without all-atom contacts', 'page' => 'aacgeom_setup.php', 'rel' => 1, 'img' => 'ramaplot.png'),
        'iface'     => array('desc' => 'Visualize interface contacts', 'page' => 'interface_setup1.php', 'rel' => 1, 'img' => 'barnase_barstar.png'),
        //'sswing'    => array('desc' => 'Refit sidechains', 'page' => 'sswing_setup1.php', 'rel' => 1, 'img' => ''),
        'makekins'  => array('desc' => 'Make simple kinemages', 'page' => 'makekin_setup.php', 'rel' => 1, 'img' => 'porin_barrel.png'),
        //'' => array('desc' => '', 'page' => '', 'rel' => 1, 'img' => ''),
    );
    
    // Reduce
    if($model['isReduced'])                 $tools['reduce']['rel'] = 0;
    elseif($model['stats']['has_most_H'])   $tools['reduce']['rel'] = 1;
    else                                    $tools['reduce']['rel'] = 2;
    
    // All-atom contact analysis, etc.
    // Suggest kin w/o H, suggest interface w/ H
    if($model['isReduced'] || $model['stats']['has_most_H'])
    {
        $tools['aacgeom']['rel'] = 2;
        $tools['iface']['rel'] = 2;
        $tools['geomonly']['rel'] = 0;
    }
    else
    {
        $tools['aacgeom']['rel'] = 0;
        $tools['iface']['rel'] = 0;
        $tools['makekins']['rel'] = 2;
    }
    
    $this->formatTools($tools);
}
#}}}########################################################################

#{{{ displayEnsembleTools - lists links for all the different tools available
############################################################################
/**
* Tool list is also customized for the currently active PDB model.
*/
function displayEnsembleTools($context)
{
    // We already know lastUsedModelID is set and refers to an ensemble, not a model
    $model = $_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ];
    // "rel" (relevance) is 2 for major, 1 for minor, 0 for not shown.
    $tools = array(
        'aacgeom'   => array('desc' => 'Analyze all-atom contacts and geometry', 'page' => 'ens_aacgeom_setup.php', 'rel' => 2, 'img' => 'clash_rama.png'),
    );
    
    $this->formatTools($tools);
}
#}}}########################################################################

#{{{ formatTools - helper function for display(Model/Ensemble)Tools
############################################################################
/**
* Prints tools and their icons from $major and $minor
* 'rel' (relevance) is 2 for major, 1 for minor, 0 for not shown.
* Normal size icons are 80 x 80; we shrink them to 40 x 40 to de-emphasize.
*/
function formatTools($tools)
{
    $major = array();
    $minor = array();
    foreach($tools as $item)
    {
        if($item['rel'] == 2)   $major[] = $item;
        if($item['rel'] == 1)   $minor[] = $item;
        // other cases are thrown away
    }
    
    echo "<table border='0' width='100%'>\n";
    // Using a secondary table makes it easier to align icons and text
    echo "<tr valign='top'><td><table border='0'>\n"; // start large icon column
    foreach($major as $item)
    {
        $a = "<a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        if($item['img'])    echo "<tr><td>$a<img src='img/$item[img]' alt='$img[desc]' border='0'></a></td>";
        else                echo "<tr><td></td>";
        echo "<td>$a$item[desc]</a></td></tr>\n";
    }
    echo "</table></td>\n";
    
    echo "<td><table border='0'>\n"; // end large; start small text column
    foreach($minor as $item)
    {
        $a = "<a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        if($item['img'])    echo "<tr><td>$a<img src='img/$item[img]' alt='$img[desc]' border='0' width='40' height='40'></a></td>";
        else                echo "<tr><td></td>";
        echo "<td>$a$item[desc]</a></td></tr>\n";
    }
    echo "</table></td></tr></table>\n"; // end tools columns
}
#}}}########################################################################

#{{{ displayFiles - lists most important files for current model
############################################################################
function displayFiles($context)
{
    $browser = new file_browser_delegate();
    
    if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
        $model = $_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ];
    else
        $model = $_SESSION['models'][ $_SESSION['lastUsedModelID'] ];
    
    $files = array(MP_DIR_MODELS.'/'.$model['pdb']);
    $files = array_merge($files, $model['primaryDownloads']);
    
    echo "<h5 class='welcome'>Popular Downloads (<a href='".makeEventURL('onNavBarCall', 'file_browser.php')."'>all downloads</a>)</h5>\n";
    echo "<div class='indent'>\n";
    echo "<table border='0' width='100%' cellspacing='0'>\n";
    $fileListColor = MP_TABLE_ALT1;
    foreach($files as $file)
    {
        echo "<tr bgcolor='$fileListColor'><td><small>".basename($file)."</small></td>";
        echo $browser->makeFileCommands($_SESSION['dataDir'].'/'.$file, $_SESSION['dataURL'].'/'.$file);
        echo "</tr>\n";
        $fileListColor == MP_TABLE_ALT1 ? $fileListColor = MP_TABLE_ALT2 : $fileListColor = MP_TABLE_ALT1;
    }
    echo "</table>\n";
    if(count($files) > 1) echo "<p><a href='".makeEventURL('onDownloadPopularZip')."'>Download these files as a ZIP archive</a></p>\n";
    
    echo "</div>\n"; // end indent
}
#}}}########################################################################

#{{{ displayEntries - lists notebook entries for the current model
############################################################################
function displayEntries($context)
{
    // FUNKY: We use this URL to take us to the lab notebook page, and each
    // header below appends an anchor (#entry123) onto the end of it...
    // To get back here, the user must manually return to the welcome page.
    $url = makeEventURL("onNavBarGoto", "notebook_main.php");

    $modelID = $_SESSION['lastUsedModelID'];
    if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
        $model = $_SESSION['ensembles'][$modelID];
    else
        $model = $_SESSION['models'][$modelID];
    
    $labbook = openLabbook();
    $labbook = array_reverse($labbook, true); // make reverse chronological
    echo "<h5 class='welcome'>Recently Generated Results (<a href='$url'>all results</a>)</h5>\n";
    //echo "<h5 class='welcome'>Recently Generated Results</h5>\n";
    echo "<div class='indent'>\n";
    echo "<table border='0' width='100%'>\n";
    $entry_count = 0;
    foreach($labbook as $num => $entry)
    {
        // Show all entries so user isn't confused when they switch models...
        //if(in_array($modelID, explode("|", $entry['model'])))
        {
            if(++$entry_count > 3) break;
            $title = $entry['title'];
            if($title == "") $title = "(no title)";
            echo "<tr><td><img src='img/$entry[thumbnail]' border='0' width='40' height='40'></td>";
            //echo "<td><a href='$url#entry$num'>$title</a></td>";
            echo "<td><a href='viewentry.php?$_SESSION[sessTag]&entry_num=$num' target='_blank'>$title</a></td>";
            echo "<td align='right'><i>".formatDayTimeBrief($entry['modtime'])."</i></td></tr>\n";
        }
    }
    echo "<tr><td colspan='3'><!-- vertical spacer --></td></tr>\n";
    echo "<tr><td></td>";
    if($entry_count > 3)    echo "<td><small><a href='$url'>[... more results ...]</a></small></td>";
    else                    echo "<td></td>";
    echo "<td align='right'><small><a href='$url'>[set time zone]</a></small></td></tr>\n";
    echo "</table>\n</div>\n";
}
#}}}########################################################################

#{{{ displayUpload - outputs the file upload/fetch controls
############################################################################
/**
* We use some clever JavaScript to show/hide the upload options in-line.
* For users without JavaScript, the link will function normally and take
* them to the upload/download page.
*
* That code is very clever and I'm quite fond of it, but I've also done
* similar things in the past by setting/clearing a flag in $context and
* simply reloading the page, as I do for e.g. file_browser.php.
* If the current version proves too incompatible, I could fall back to that one.
*/
function displayUpload($context)
{
    echo makeEventForm("onUploadOrFetch", null, true) . "\n"; 
    //echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onNavBarCall", "upload_setup.php")."'>more options</a>)</h5>";
    echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onNavBarCall", "upload_setup.php")."' onclick='toggleUploadOptions(); return false' id='upload_options_link'>more options</a>)</h5>";
?>
<script language='JavaScript'>
<!--
function toggleUploadOptions()
{
    var block = document.getElementById('upload_options_block')
    var link = document.getElementById('upload_options_link')
    if(block.style.display == 'none')
    {   
        block.style.display = 'block'
        link.innerHTML = 'hide options'
    }
    else
    {
        block.style.display = 'none'
        link.innerHTML = 'more options'
    }
}
// -->
</script>
<div class='indent'><table border='0' width='100%'>
<tr>
    <td width='40%' align='center'>PDB/NDB code</td>
    <td width='40%' align='center'>Upload
        <select name='uploadType'>
            <option value='pdb'>PDB file</option>
            <option value='kin'>kinemage</option>
            <option value='map'>ED map</option>
            <option value='hetdict'>het dict</option>
        </select></td>
    <td width='20%'></td>
</tr><tr>
    <td align='center'><input type="text" name="pdbCode" size="6" maxlength="10"></td>
    <td align='center'><input type="file" name="uploadFile"></td>
    <td align='center'><input type="submit" name="cmd" value="Upload/Fetch &gt;"></td>
</tr>
</table>
    <div style='display:none' id='upload_options_block'>
    <!-- We have to start a new table because you can't show/hide <tr>'s, at least not in Safari -->
        <table border='0' width='100%'><tr valign='top'>
            <td width='40%'><div class='inline_options'>
                <label><input type="checkbox" name="biolunit" value="1"> Biol. unit (PDB only)</label>
                <br><label><input type="checkbox" name="eds_2fofc" value="1"> Get 2Fo-Fc map from EDS</label>
                <br><label><input type="checkbox" name="eds_fofc" value="1"> Get Fo-Fc map from EDS</label>
            </div></td>
            <td width='40%'><div class='inline_options'>
                <label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
                <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
            </div></td>
            <td width='20%'></td>
        </tr></table>
    </div>
</div></form>
<?php
}
#}}}########################################################################

#{{{ onSetWorkingModel
############################################################################
function onSetWorkingModel($arg, $req)
{
    $_SESSION['lastUsedModelID'] = $req['workingModel'];
}
#}}}########################################################################

#{{{ onUploadOrFetch
############################################################################
/**
* FUNKY: This simulates being on the upload page and then calls the appropriate
* event handler depending on whether a file has been uploaded or not...
* Don't try this at home!
*/
function onUploadOrFetch($arg, $req)
{
    $hasUpload = isset($_FILES['uploadFile']) && $_FILES['uploadFile']['error'] != UPLOAD_ERR_NO_FILE;
    $hasFetch = isset($req['pdbCode']) && $req['pdbCode'] != "";
    
    pageCall("upload_setup.php"); // or else a later pageReturn() will screw us up!
    $upload_delegate = makeDelegateObject();

    if($hasUpload)
    {
        if($req['uploadType'] == 'pdb')         $upload_delegate->onUploadPdbFile($arg, $req);
        elseif($req['uploadType'] == 'kin')     $upload_delegate->onUploadKinemage($arg, $req);
        elseif($req['uploadType'] == 'map')     $upload_delegate->onUploadMapFile($arg, $req);
        elseif($req['uploadType'] == 'hetdict') $upload_delegate->onUploadHetDictFile($arg, $req);
    }
    elseif($hasFetch)
        $upload_delegate->onFetchPdbFile($arg, $req);
    else 
        $upload_delegate->onUploadPdbFile($arg, $req);
}
#}}}########################################################################

#{{{ onDownloadPopularZip
############################################################################
/**
* FUNKY: This turns into a binary file download rather than an HTML page,
* and then calls die(), leaving the user on the original HTML page.
*
* This code has been shown to cause cancer in lab rats.
*/
function onDownloadPopularZip($arg, $req)
{
    if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
        $model = $_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ];
    else
        $model = $_SESSION['models'][ $_SESSION['lastUsedModelID'] ];

    $files = array(MP_DIR_MODELS.'/'.$model['pdb']);
    $files = array_merge($files, $model['primaryDownloads']);
    
    $zipfile = makeZipForFiles($_SESSION['dataDir'], $files);
    // These lines may be required by Internet Explorer
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    // See PHP manual on header() for how this works.
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="' . $model['id'] . '.zip"');
    mpReadfile($zipfile);
    unlink($zipfile);
    die(); // don't output the HTML version of this page into that nice file!
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
