<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');
require_once(MP_BASE_DIR.'/lib/browser.php');

// It may be bad form, but we hijack functions from these pages to avoid
// duplicating the work they do. This must be done very carefully!
require_once(MP_BASE_DIR.'/pages/upload_pdb_setup.php');
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
    echo mpPageHeader("Welcome!", "welcome");
    echo "<center><h2>MolProbity:<br>Macromolecular Structure Validation</h2></center>\n";
    //echo mpPageHeader("MolProbity:<br>Macromolecular Structure Validation", "welcome");
    $this->displayWarnings($context);
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
<?php $this->displayModels($context); ?>
</table></form>


<?php
    if(count($_SESSION['models']) > 0 && $_SESSION['lastUsedModelID'])
    {
        echo "<hr>\n";
        if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
            $this->displayEnsembleTools($context);
        else
            $this->displayModelTools($context);
        
        echo "<hr>\n";
        $this->displayFiles($context);
        echo "<hr>\n";
        $this->displayEntries($context);
        echo "<hr>\n";
    }
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
        echo "<tr>";
        echo "<td colspan='2'>Working model: ";
        $submit_script = 'document.forms[0].elements("cmd")[1].click();';
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
        echo "</td><td><input type='submit' name='cmd' value='Set'></td><td></td></tr>\n";
    }
    elseif(count($_SESSION['models']) > 0)
    {
        $model = reset($_SESSION['models']);
        echo "<tr><td colspan='4'>Working model: $model[pdb]</td></tr>\n";
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
    $minor = array(
        'upload'    => array('desc' => 'Input map / het dict / kin files', 'page' => 'upload_other_setup.php', 'img' => ''),
        'reduce'    => array('desc' => 'Add hydrogens', 'page' => 'reduce_setup.php', 'img' => 'add_h.png'),
        'aacgeom'   => array('desc' => 'All-atom contacts and geometry', 'page' => 'aacgeom_setup.php', 'img' => 'clash_rama.png'),
        'geomonly'  => array('desc' => 'Geometry analysis only', 'page' => 'aacgeom_setup.php', 'img' => 'ramaplot.png'),
        'iface'     => array('desc' => 'Visualize interface contacts', 'page' => 'interface_setup1.php', 'img' => 'barnase_barstar.png'),
        //'sswing'    => array('desc' => 'Refit sidechains', 'page' => 'sswing_setup1.php', 'img' => ''),
        'makekins'  => array('desc' => 'Make simple kinemages', 'page' => 'makekin_setup.php', 'img' => 'porin_barrel.png'),
        //'' => array('desc' => '', 'page' => '', 'img' => ''),
    );
    $major = array();
    
    // Reduce
    if($model['isReduced']) unset($minor['reduce']);
    elseif($model['stats']['has_most_H']) {} // stay put
    else { $major['reduce'] = $minor['reduce']; unset($minor['reduce']); }
    
    // All-atom contact analysis
    if($model['isReduced'] || $model['stats']['has_most_H'])
    {
        $major['aacgeom'] = $minor['aacgeom'];
        unset($minor['aacgeom']);
        unset($minor['geomonly']);
    }
    else
    {
        //$major['geomonly'] = $minor['geomonly'];
        unset($minor['aacgeom']);
        //unset($minor['geomonly']);
    }
    
    // Suggest kin w/o H, suggest interface w/ H
    if($model['isReduced'] || $model['stats']['has_most_H'])
    {
        $major['iface'] = $minor['iface'];
        unset($minor['iface']);
    }
    else
    {
        $major['makekins'] = $minor['makekins'];
        unset($minor['makekins']);
    }
    
    
    echo "<table border='0' width='100%'>\n";
    echo "<tr valign='top'><td>\n"; // start large icon column
    foreach($major as $item)
    {
        echo "<a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        if($item['img']) echo "<img src='img/$item[img]' alt='$img[desc]' border='0' align='middle'> ";
        echo "$item[desc]</a><br>\n";
    }
    echo "</td>\n";
    
    echo "<td>\n"; // end large; start small text column
    foreach($minor as $item)
    {
        echo "<p><a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        echo "$item[desc]</a></p>\n";
    }
    echo "</td></tr></table>\n"; // end tools columns
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
    /*
    $minor = array(
        'upload'    => array('desc' => 'Input map / het dict / kin files', 'page' => 'upload_other_setup.php', 'img' => ''),
        'reduce'    => array('desc' => 'Add hydrogens', 'page' => 'reduce_setup.php', 'img' => 'add_h.png'),
        'aacgeom'   => array('desc' => 'All-atom contacts and geometry', 'page' => 'aacgeom_setup.php', 'img' => 'clash_rama.png'),
        'geomonly'  => array('desc' => 'Geometry analysis only', 'page' => 'aacgeom_setup.php', 'img' => 'ramaplot.png'),
        'iface'     => array('desc' => 'Visualize interface contacts', 'page' => 'interface_setup1.php', 'img' => 'barnase_barstar.png'),
        'sswing'    => array('desc' => 'Refit sidechains', 'page' => 'sswing_setup1.php', 'img' => ''),
        'makekins'  => array('desc' => 'Make simple kinemages', 'page' => 'makekin_setup.php', 'img' => 'porin_barrel.png'),
        //'' => array('desc' => '', 'page' => '', 'img' => ''),
    );
    $major = array();
    
    // Reduce
    if($model['isReduced']) unset($minor['reduce']);
    elseif($model['stats']['has_most_H']) {} // stay put
    else { $major['reduce'] = $minor['reduce']; unset($minor['reduce']); }
    
    // All-atom contact analysis
    if($model['isReduced'] || $model['stats']['has_most_H'])
    {
        $major['aacgeom'] = $minor['aacgeom'];
        unset($minor['aacgeom']);
        unset($minor['geomonly']);
    }
    else
    {
        //$major['geomonly'] = $minor['geomonly'];
        unset($minor['aacgeom']);
        //unset($minor['geomonly']);
    }
    
    // Suggest kin w/o H, suggest interface w/ H
    if($model['isReduced'] || $model['stats']['has_most_H'])
    {
        $major['iface'] = $minor['iface'];
        unset($minor['iface']);
    }
    else
    {
        $major['makekins'] = $minor['makekins'];
        unset($minor['makekins']);
    }
    */
    $minor = array(
        'upload'    => array('desc' => 'Input map / het dict / kin files', 'page' => 'upload_other_setup.php', 'img' => ''),
    );
    $major = array(
        'aacgeom'   => array('desc' => 'All-atom contacts and geometry', 'page' => 'ens_aacgeom_setup.php', 'img' => 'clash_rama.png'),
    );
    
    
    echo "<table border='0' width='100%'>\n";
    echo "<tr valign='top'><td>\n"; // start large icon column
    foreach($major as $item)
    {
        echo "<a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        if($item['img']) echo "<img src='img/$item[img]' alt='$img[desc]' border='0' align='middle'> ";
        echo "$item[desc]</a><br>\n";
    }
    echo "</td>\n";
    
    echo "<td>\n"; // end large; start small text column
    foreach($minor as $item)
    {
        echo "<p><a href='".makeEventURL("onNavBarCall", $item['page'])."'>";
        echo "$item[desc]</a></p>\n";
    }
    echo "</td></tr></table>\n"; // end tools columns
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
    
    echo "<h3 class='nospaceafter'>Popular downloads for $model[pdb]:</h3>\n";
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
    echo "<h3 class='nospaceafter'>Lab notebook entries for $model[pdb]:</h3>\n";
    echo "<ul>\n";
    foreach($labbook as $num => $entry)
    {
        if(in_array($modelID, explode("|", $entry['model'])))
        {
            $title = $entry['title'];
            if($title == "") $title = "(no title)";
            echo "<li><a href='$url#entry$num'>$title</a> [".formatDayTime($entry['modtime'])."]</li>\n";
        }
    }
    echo "</ul>\n";
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
    $hasUpload = isset($_FILES['uploadFile']) && $_FILES['uploadFile']['error'] != UPLOAD_ERR_NO_FILE;
    $hasFetch = isset($req['pdbCode']) && $req['pdbCode'] != "";
    
    if(startsWith($req['cmd'], 'Set'))
    {
        $_SESSION['lastUsedModelID'] = $req['workingModel'];
    }
    // The default action when cmd is not set, as in IE6 when you just hit Return
    elseif(startsWith($req['cmd'], 'Go') || $hasUpload || $hasFetch)
    {
        pageCall("upload_pdb_setup.php"); // or else a later pageReturn() will screw us up!
        $upload_delegate = makeDelegateObject();
        if($hasUpload)
            $upload_delegate->onUploadPdbFile($arg, $req);
        elseif($hasFetch)
            $upload_delegate->onFetchPdbFile($arg, $req);
        else 
            $upload_delegate->onUploadPdbFile($arg, $req);
    }
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
