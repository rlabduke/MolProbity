<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');
require_once(MP_BASE_DIR.'/lib/browser.php');
require_once(MP_BASE_DIR.'/lib/model.php');
require_once(MP_BASE_DIR.'/lib/pdbstat.php');

// It may be bad form, but we hijack functions from these pages to avoid
// duplicating the work they do. This must be done very carefully!
require_once(MP_BASE_DIR.'/pages/upload_setup.php');
require_once(MP_BASE_DIR.'/pages/file_browser.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class welcome_delegate extends file_browser_delegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Main page", "welcome");

    // echo mpSerialize($_SESSION['models']);
    if(count($_SESSION['models']) > 0 && $_SESSION['lastUsedModelID'])
    {
        echo "<h5 class='welcome'>Suggested Tools (<a href='".makeEventURL("onGoto", "sitemap.php")."'>all tools</a>)</h5>\n";
        echo "<div class='indent'>\n";
        echo makeEventForm("onSetWorkingModel") . "\n";
        $this->displayModels($context);
        echo "</form>\n";

        if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
            $this->displayEnsembleTools($context);
        else
            $this->displayModelTools($context);

        echo "</div></div>\n<br>\n<div class='pagecontent'>\n";
    }

    // Entries / files should display if we have any entries.
    // We can have entries with no models -- e.g., uploading a het dictionary first thing.
    $labbook = openLabbook();
    if(count($labbook) > 0)
    {
        $this->displayEntries($context, $labbook);
        echo "<br>\n";
        //echo "</div>\n<br>\n<div class='pagecontent'>\n";
        //$this->displayFiles($context);
        //$this->displayFilesJS($context);
        $this->displayAllFiles($context);
        echo "</div>\n<br>\n<div class='pagecontent'>\n";
    }

    $this->displayUpload($context);
    echo "</div>\n<br>\n<div class='pagecontent'>\n";
?>
<table border='0' width='100%'><tr valign='top'><td width='45%'>
<h3>Walkthroughs, tutorials, and usage FAQs:</h3>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "helper_xray.php")."'>Evaluate X-ray structure</a>"; ?>:</b>
Typical steps for a published X-ray crystal structure
or one still undergoing refinement.</p>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "helper_nmr.php")."'>Evaluate NMR structure</a>"; ?>:</b>
Typical steps for a published NMR ensemble
or one still undergoing refinement.</p>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "helper_rebuild.php")."'>Fix up structure</a>"; ?>:</b>
Rebuild the model to remove outliers
as part of the refinement cycle.</p>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "helper_kinemage.php")."'>Work with kinemages</a>"; ?>:</b>
Create and view interactive 3-D graphics
from your web browser.</p>
<p><b><a target="_blank" href="help/reduce_guide/reduce_guide.html">Guide to Reduce options</a>:</b>
Learn about adding hydrogens to a structure for all-atom contact analysis.</p>
<p><b><a target="_blank" href="help/validation_options/validation_options.html">Guide to validation options</a>:</b>
Choose validations appropriate to a structure.</p>


<?php
//      <h3>What's new in 4.0:</h3><ul>
//      <li>Updated Reduce-added hydrogen lengths to be more consistent with other crystallography software.</li>
//      <li>New <a href='http://kinemage.biochem.duke.edu/databases/top8000.php' target='_blank'>Top8000</a> Ramachandran validation information.</li>
//      <li>Allows use of both electron cloud and nuclear x-H bond-lengths. Read more about this change <a href='".makeEventURL("onGoto", "helper_hydrogens.php")."'>here</a>.</li>
//      <li>Full support for 2-character CHAINIDs added. 4-character SEGIDs also supported in place of CHAINIDs.</li>
//      </ul>
    echo("<h3>What's new in 4.3.1:</h3><ul>
      <li>This versioned release is timed to correspond with the Phenix 1.11 official release, and is expected to work with the corresponding version of the cctbx project.</li>
      <li>Update to Omegalyze kinemage output - markup now offset from backbone to reduce overlap with other markups.</li>
      <li>Ramachandran analysis reports alternate conformations more comprehensively at the residue level. Ramachandran summary statistics are given for alternate A only where applicable.</li>
      <li>Reduce het dictionary updated - hydrogens now available for more ligands.</li>
      <li>Reduce no longer rotates methionine sidechain methyls.</li>
  </ul>
    <h3>What's new in 4.3:</h3><ul>
      <li>New NQH coordinate regularization method after flips in Reduce: a three-step process attempting to match existing coordinates replaces cctbx-based optimization</li>
      <li>Updates to Omegalyze kinemage output - markup defaults to 'off', separate colors for cis-pro (sea), cis-nonpro (lime), and all twisted (yellow).</li>
      <li>Improved error catching, with 'how to fix it' notes for common formatting errors</li>
	</ul>
    <h3>What's new in 4.2:</h3><ul>
      <li>Cis-peptide identification, markup and statistics now available.</li>
      <li>CaBLAM C&alpha;-based validation of protein backbone and secondary structure now available.</li>
      <li>Rotamer validation now uses <a href='http://kinemage.biochem.duke.edu/databases/top8000.php' target='_blank'>Top8000</a> rotamer distributions. Favored vs Allowed distinction introduced for rotamers.</li>
      <li>New coloring scheme in multicriterion chart encodes outlier severity at a glance.</li>
      <li><a target='_blank' href='help/validation_options/validation_options.html'>Online tutorial</a> explaining markup and methods</li>
      <li>We now use a conformation-dependent library (CDL) from Dunbrack and Karplus for geometry analysis if requested.
      </ul>
      <h3>What's new in 4.1:</h3><ul>
      <li>Validation analysis now powered by CCTBX. Also adds geometry regularization for N/Q/H flip corrections. Read more about this change <a href='".makeEventURL("onGoto", "helper_cctbx.php")."'>here</a>.</li>
      <li>Alternate conformations now handled for validation analysis.</li>
      </ul>
      </td><td width='10%'><!-- horizontal spacer --></td><td width=='45%'>");
?>

<h3>Citations, science, and technical FAQs:</h3>
<p><b><a href='help/about.html' target='_blank'>Cite MolProbity</a></b>:
    <small>Chen et al. (2010)
    <a href="http://kinemage.biochem.duke.edu/lab/papers.php" target="_blank">MolProbity:
    all-atom structure validation for macromolecular crystallography.</a>
    Acta Crystallographica D66:12-21.
    </p>
    <center>and/or</center>
<p>Davis et al. (2007)
    <a href="http://kinemage.biochem.duke.edu/lab/papers.php" target="_blank">MolProbity:
    all-atom contacts and structure validation for proteins and nucleic acids.</a>
    Nucleic Acids Research 35:W375-W383.
    </small></p>
<p><b><a href='help/about.html' target='_blank'>Cite KiNG</a></b>:
    <small>Chen et al. (2009)
    <a href="http://kinemage.biochem.duke.edu/lab/papers.php" target="_blank">KiNG (Kinemage, Next Generation):
    A versatile interactive molecular and scientific visualization program.</a>
    Protein Science 18:2403-2409.
    </small></p>
<p><b><a href='help/about.html' target='_blank'>Cite CCTBX</a></b>:
    <small>Grosse-Kunstleve et al. (2002)
    <a href="http://scripts.iucr.org/cgi-bin/paper?ks0118" target="_blank">The Computational Crystallography Toolbox:
    crystallographic algorithms in a reusable software framework.</a>
    J. Appl. Cryst. 35:126-136.
    </small></p>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "helper_hydrogens.php")."'>About hydrogens</a>"; ?>:</b>
Why have the hydrogen bondlengths changed?</p>
<p><b><a href='help/java.html' target='_blank'>Installing Java</a></b>: how to make kinemage graphics work in your browser.</p>
<p><b><?php echo "<a href='".makeEventURL("onGoto", "get_molprobity.php")."'>Download MolProbity</a>"; ?></b>: how can I run a private MolProbity server, or run from the command line?</p>
<p><small><i>NB: the back button doesn't work inside MolProbity</i></small></p><!-- by request of DCR -->
</td></tr></table>
<?php
    // These are too annoying to have at the top all the time
    $this->displayWarnings($context);

    echo $this->pageFooter();
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
        echo "<br><div class='alert'>\n";
        echo "Your browser, $br[browser] $br[version], $err.\n";
        echo "If pages display incorrectly or you experience other problems, you may want to \n";
        echo "try a browser like <a href='http://www.mozilla.org/' target='_blank'>Firefox</a> instead.\n";
        echo "</div><br>\n";
    }

    // 2. Check for Java being enabled.
    // This doesn't verify the version, but is better than nothing...
    // javaEnabled() doesn't work in Mozilla-type browsers on OS X, so we exclude them
    $bad_platforms = array('Apple Macintosh');
    $bad_browsers = array('Firefox', 'Mozilla', 'Netscape');
    if(!(in_array($br['platform'], $bad_platforms) && in_array($br['browser'], $bad_browsers)))
    {
?><script language='JavaScript'>
<!--
    if(!navigator.javaEnabled())
    {
        document.writeln("<br><div class='alert'>");
        document.writeln("It appears that Java is not enabled in your browser, so you may not be able to use KiNG interactive graphics.");
        document.writeln("<br><a href='help/java.html' target='_blank'>Click here for help.</a>");
        document.writeln("</div>");
    }
// -->
</script>
<?php

    }
}
#}}}########################################################################

#{{{ displayModels - lists available model(s) and ensemble(s), if any
############################################################################
function displayModels($context)
{

    // Warning about default being to trim hydrogens
    echo("<div class=alert>Due to the parameter adjustments to hydrogen bondlengths and van der Waals radii, the current default behavior for MolProbity is
      to remove hydrogens, if they are present, before analysis. Please re-add hydrogens using the \"Add hydrogens\" option below, where you will have the option
      to choose either the default electron-cloud position hydrogens (i.e. for crystal structures) or nuclear-position hydrogens (i.e. for neutron-diffraction structures or for NMR structures).
        </div>");

    if(count($_SESSION['models']) > 1)
    {
        // This works on Safari but not Firefox:
        //$submit_script = 'document.forms[0].elements("cmd").click();';
        // Either of these works on both:
        //$submit_script = 'document.forms[0].cmd.click();';
        $submit_script = 'document.forms[0].submit();';

        echo "Currently working on: ";
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
        'iface'     => array('desc' => 'Visualize interface contacts', 'handler' => 'onVisInterface', 'rel' => 1, 'img' => 'barnase_barstar.png'),
        //'sswing'    => array('desc' => 'Refit sidechains', 'page' => 'sswing_setup1.php', 'rel' => 1, 'img' => ''),
        'makekins'  => array('desc' => 'Make simple kinemages', 'page' => 'makekin_setup.php', 'rel' => 1, 'img' => 'porin_barrel.png'),
        'editpdb' => array('desc' => 'Edit PDB file', 'page' => 'editpdb_setup1.php', 'rel' => 1, 'img' => 'scissors.png'),
        'downgrade' => array('desc' => 'Downgrade file to PDBv2.3 format (for download only)', 'page' => 'pdb_convert_setup.php', 'rel' => 1, 'img' => 'downgrade.gif'),
        //'viewpdb' => array('desc' => 'View PDB in KiNG (beta)', 'page' => "viewpdbking.php?$_SESSION[sessTag]&url=".MP_DIR_KINS, 'rel' => 1, 'img' => 'pdb_icon.png'),
        //'' => array('desc' => '', 'page' => '', 'rel' => 1, 'img' => ''),
        'jiffiloop' => array('desc' => 'Fill gaps in protein backbone with JiffiLoop (beta test)', 'page' => 'fragmentfill_setup.php', 'rel' => 1, 'img' => 'jiffiloop.png'),
        'geomonly'  => array('desc' => 'Analyze geometry without all-atom contacts', 'page' => 'aacgeom_setup.php', 'rel' => 1, 'img' => 'ramaplot.png'),
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
    $ensemble = $_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ];
    // Use the first model of each ensemble as representative.
    $modelID = reset($ensemble['models']);
    $model = $_SESSION['models'][$modelID];

    // "rel" (relevance) is 2 for major, 1 for minor, 0 for not shown.
    $tools = array(
        'reduce'    => array('desc' => 'Add hydrogens', 'page' => 'ens_reduce_setup.php', 'rel' => 1, 'img' => 'add_h.png'),
        'coregen'  => array('desc' => 'Generate core ensemble', 'page' => 'ens_core_gen_setup.php', 'rel' => 2, 'img' => 'scissors.png'),
        'aacgeom'   => array('desc' => 'Analyze all-atom contacts and geometry (ensemble)', 'page' => 'ens_aacgeom_setup.php', 'rel' => 2, 'img' => 'clash_rama.png'),
        'biolunit'  => array('desc' => 'Biol. unit, not NMR', 'handler' => 'onConvertToBiolUnit', 'rel' => 1, 'img' => 'scissors.png'),
        'downgrade' => array('desc' => 'Downgrade file to PDBv2.3 format (for download only)', 'page' => 'pdb_convert_setup.php', 'rel' => 1, 'img' => 'downgrade.gif'),
    );

    // Reduce
    if($ensemble['isReduced'])
        $tools['reduce']['rel'] = 0;
    elseif($model['stats']['has_most_H'])
        $tools['reduce']['rel'] = 1;
    else
    {
        $tools['reduce']['rel'] = 2;
        //$tools['aacgeom']['rel'] = 1;
    }
    
    // core/non-core split
    if($ensemble['isCored'])
      $tools['coregen']['rel'] = 0;

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
        if($item['handler'])    $a = "<a href='".makeEventURL($item['handler'])."'>";
        else                    $a = "<a href='".makeEventURL("onCall", $item['page'])."'>";
        if($item['img'])    echo "<tr><td>$a<img src='img/$item[img]' alt='$img[desc]' border='0'></a></td>";
        else                echo "<tr><td></td>";
        echo "<td>$a$item[desc]</a></td></tr>\n";
    }
    echo "</table></td>\n";

    echo "<td><table border='0'>\n"; // end large; start small text column
    foreach($minor as $item)
    {
        if($item['handler'])    $a = "<a href='".makeEventURL($item['handler'])."'>";
        else                    $a = "<a href='".makeEventURL("onCall", $item['page'])."'>";
        if($item['img'])    echo "<tr><td>$a<img src='img/$item[img]' alt='$img[desc]' border='0' width='40' height='40'></a></td>";
        else                echo "<tr><td></td>";
        echo "<td>$a$item[desc]</a></td></tr>\n";
    }
    echo "</table></td></tr></table>\n"; // end tools columns
}
#}}}########################################################################

#{{{ [NOT USED] displayFiles - lists most important files for current model
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

    echo "<h5 class='welcome'>Popular Downloads (<a href='".makeEventURL('onCall', 'file_browser.php')."'>all downloads</a>)</h5>\n";
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

#{{{ [NOT USED] displayFilesJS - lists most important files for current model
############################################################################
function displayFilesJS($context)
{
    echo "<h5 class='welcome'>Popular Downloads (<a href='".makeEventURL('onCall', 'file_browser.php')."'>all downloads</a>)</h5>\n";
    echo "<div class='indent'>\n";

    $files = listRecursive($_SESSION['dataDir']);
    unset($files[MP_DIR_SYSTEM]);
    unset($files[MP_DIR_WORK]);
    unset($files[MP_DIR_RAWDATA]);
    echo "<table border='0' width='100%' cellspacing='0'><tr valign='top'>\n";
    foreach($files as $dirName => $dirContents)
    {
        echo "<td>\n";
        echo "<b>$dirName</b><br>\n";
        foreach($dirContents as $fileName => $fileContents)
        {
            if(is_array($fileContents)) continue; // not a file -- a directory
            echo "<a href='$_SESSION[dataURL]/$dirName/$fileName' onmouseover='showFileTools(this, \"$dirName/$fileName\", event)' onmouseout='hideFileTools()'>$fileName</a><br>";
        }
        echo "</td>\n";
    }
    echo "</tr></table>\n";
    //{{{ Magical mystery code that makes the popup menus work
?>
<div id='filetools' style='display:none; position:absolute; z-index:10; background:#bbe; padding:0.2em; border: 1px #006 dotted' onmouseover='showFileTools(null, null, event)' onmouseout='hideFileTools()'>Hello</div>
<script language='JavaScript'>
<!--
    var sessTag = <?php echo "\"".$_SESSION['sessTag']."\"\n"; ?>
    var dataDir = <?php echo "\"".$_SESSION['dataDir']."\"\n"; ?>
    var dataURL = <?php echo "\"".$_SESSION['dataURL']."\"\n"; ?>
    var timerID = 0
    var lastLink = null

    function showFileTools(link, fileName, event)
    {
        // MSIE is different...
        if(!event) event = window.event

        if(timerID != 0)
        {
            window.clearTimeout(timerID)
            timerID = 0
        }

        var tools = document.getElementById("filetools")
        if(link != null)// && link != lastLink)
        {
            var html = ""
            if(/\.kin$/.exec(fileName))
            {
                html += "<a href='viewking.php?"+sessTag+"&url="+dataURL+"/"+fileName+"' target='_blank'>View in KiNG</a><br>\n"
                html += "<a href='viewtext.php?"+sessTag+"&file="+dataDir+"/"+fileName+"&mode=plain' target='_blank'>View as text</a><br>\n"
            }
            else if(/\.kin\.gz$/.exec(fileName))
                html += "<a href='viewking.php?"+sessTag+"&url="+dataURL+"/"+fileName+"' target='_blank'>View in KiNG</a><br>\n"
            else if(/\.table$/.exec(fileName))
                html += "<a href='viewtable.php?"+sessTag+"&file="+dataDir+"/"+fileName+"' target='_blank'>View as table</a><br>\n"
            else if(/\.html$/.exec(fileName))
            {
                html += "<a href='viewtext.php?"+sessTag+"&file="+dataDir+"/"+fileName+"&mode=html' target='_blank'>View as HTML</a><br>\n"
                html += "<a href='viewtext.php?"+sessTag+"&file="+dataDir+"/"+fileName+"&mode=plain' target='_blank'>View as text</a><br>\n"
            }
            else if(/\.(gz|tgz|zip|pdf))$/.exec(fileName))
                html += "" // no-op
            else // the default: assume plain text
                html += "<a href='viewtext.php?"+sessTag+"&file="+dataDir+"/"+fileName+"&mode=plain' target='_blank'>View as text</a><br>\n"
            html += "<a href='"+dataURL+"/"+fileName+"'><img src='img/download.gif'> Download</a>"
            tools.innerHTML = html

            //var x = event.clientX
            //var y = event.clientY
            var x = findPosX(link) + link.offsetWidth + 4
            var y = findPosY(link) + (link.offsetHeight/2) - (tools.offsetHeight/2)
            tools.style.left = x+"px"
            tools.style.top = y+"px"
            tools.style.display = "block"

            lastLink = link
        }
    }
    function hideFileTools()
    {
        timerID = window.setTimeout("reallyHideFileTools()", 500)
    }
    function reallyHideFileTools()
    {
        var tools = document.getElementById("filetools")
        tools.style.display = "none"
        lastLink = null
    }

// Borrowed from http://www.quirksmode.org/js/findpos.html
function findPosX(obj)
{
    var curleft = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curleft += obj.offsetLeft
            obj = obj.offsetParent;
        }
    }
    else if (obj.x)
        curleft += obj.x;
    return curleft;
}
function findPosY(obj)
{
    var curtop = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curtop += obj.offsetTop
            obj = obj.offsetParent;
        }
    }
    else if (obj.y)
        curtop += obj.y;
    return curtop;
}
// -->
</script>
<?php
    //}}}
    echo "</div>\n"; // end indent
}
#}}}########################################################################

#{{{ displayAllFiles - lists files for download and viewing
############################################################################
function displayAllFiles($context)
{
    echo "<h5 class='welcome'>Popular Downloads (<a href='".makeEventURL('onCall', 'file_browser.php')."'>all downloads</a>)</h5>\n";
    echo "<div class='indent'>\n";

    $list = listRecursive($_SESSION['dataDir']);
    unset($list[MP_DIR_SYSTEM]);
    unset($list[MP_DIR_WORK]);
    unset($list[MP_DIR_RAWDATA]);
    $list = sortFilesAlpha($list);
    $this->displayDownloadForm($list, $context['isExpanded']);

    echo "</div>\n"; // end indent
}
#}}}########################################################################

#{{{ displayEntries - lists notebook entries for the current model
############################################################################
function displayEntries($context, $labbook)
{
    // FUNKY: We use this URL to take us to the lab notebook page, and each
    // header below appends an anchor (#entry123) onto the end of it...
    // To get back here, the user must manually return to the welcome page.
    // [This describes the old, commented out code.]
    $url = makeEventURL("onGoto", "notebook_main.php");

    $modelID = $_SESSION['lastUsedModelID'];
    if(isset($_SESSION['ensembles'][ $_SESSION['lastUsedModelID'] ]))
        $model = $_SESSION['ensembles'][$modelID];
    else
        $model = $_SESSION['models'][$modelID];

    //$labbook = openLabbook();
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
            if(++$entry_count > 2) break;
            $title = $entry['title'];
            if($title == "") $title = "(no title)";
            echo "<tr><td width='50'><img src='img/$entry[thumbnail]' border='0' width='40' height='40'></td>";
            //echo "<td><a href='$url#entry$num'>$title</a></td>";
            echo "<td><a href='viewentry.php?$_SESSION[sessTag]&entry_num=$num' target='_blank'>$title</a></td>";
            echo "<td align='right'><i>".formatDayTimeBrief($entry['modtime'])."</i></td></tr>\n";
        }
    }
    echo "<tr><td colspan='3'><!-- vertical spacer --></td></tr>\n";
    echo "<tr><td></td>";
    if($entry_count > 2)    echo "<td><small><a href='$url'>[... more results ...]</a></small></td>";
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
    //This block prints a warning if the server was recently rebooted (30 mins).  I assume putting this check here is acceptable.  SML
    $recent_reboot = False;
    $recent_limit = 1800; //1800 seconds in half an hour
    //This should be a function returning $recent_reboot but I don't know how in PHP
    //These if statements determine what system we are on
    $host_os = shell_exec("uname");
    if($host_os == "Linux\n") {
    	//on Linux, first column of /proc/uptime reports seconds of uptime
        $reboot_seconds = shell_exec("awk '{print $1}' /proc/uptime");
    	if($reboot_seconds < $recent_limit) {
	    $recent_reboot = True;
	}
    } elseif ($host_os == "Darwin\n") { //Mac
      	//on BSD/(Mac), sysctl kern.boottime reports boot time (POSIX time) in 5th column.  sed strips a comma.
	$mac_boot_seconds = shell_exec("sysctl kern.boottime | awk '{print $5}' | sed 's/,//g'");
	//This gets the current POSIX time
	$mac_now = shell_exec("date +%s");
	$reboot_seconds = $mac_now - $mac_boot_seconds;
    	if($reboot_seconds < $recent_limit) {
	    $recent_reboot = True;
	}
    } else { //give up on $recent_reboot
    $recent_reboot = False;
    }

    if($recent_reboot)
    {
        echo("<div class=alert><strong>Our MolProbity server was recently rebooted. We apologize for the loss of any jobs you might have had running. Reboots are sometimes necessary if the server is overloaded. If you were running large jobs (more than 10,000 atoms) or more than 2 jobs, please consider resubmitting them over a longer period to spread out the load. Thanks for helping us keep our MolProbity downtimes rare.</strong></div>");
    }

    echo makeEventForm("onUploadOrFetch") . "\n";
    //echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onCall", "upload_setup.php")."'>more options</a>)</h5>";
    echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onCall", "upload_setup.php")."' onclick='toggleUploadOptions(); return false' id='upload_options_link'>more options</a>)</h5>";
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
    <td>PDB/NDB code: <input type="text" name="pdbCode" size="6" maxlength="10"></td>
    <td>type:
        <select name='fetchType'>
            <option value='pdb'>PDB coords</option>
            <option value='pdb_xray'>PDB coords & xray data</option>
            <option value='biolunit'>Biol. unit (PDB only)</option>
            <option value='biolunit_xray'>Biol. unit & xray data (PDB only)</option>
            <option value='eds_2fofc'>2Fo-Fc map (EDS)</option>
            <option value='eds_fofc'>Fo-Fc map (EDS)</option>
        </select>
    </td>
    <td><input type="submit" name="cmd" value="Fetch &gt;"></td>
</tr><tr><td colspan='3'><hr></td></tr><tr>
    <td><input type="file" name="uploadFile"></td>
    <td>type:
        <select name='uploadType'>
            <option value='pdb'>PDB coords</option>
            <option value='xray'>xray data (mtz format)</option>
            <option value='kin'>kinemage</option>
            <option value='map'>ED map</option>
            <option value='hetdict'>het dict</option>
        </select>
    </td>
    <td><input type="submit" name="cmd" value="Upload &gt;"></td>
</tr>
</table>
    <div class='inline_options' style='display:none' id='upload_options_block'>
        <label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
        <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
    </div>
</div></form>
<?php

    //echo("<div class=alert><strong>The PDB has changed its file format from PDB v2.3 to v3 (see <a href='http://remediation.wwpdb.org/index.html' target='_blank'>here</a> for more info).
    //    We have upgraded our software to be compatible with the new format.
    //    Any uploaded files will be converted to PDB v3 if necessary; you will have an option to convert modified files back to PDB v2.3 if desired.
    //    <br><br>Please don't hesitate to report any bugs you may encounter; sorry for any inconvenience.</strong></div>");

//    echo("<div class=alert><strong>MolProbity4 structure validation now provides many of its validation metrics through CCTBX, the open-source component of the
//      <a style=\"color: #66FFFF\" href='http://www.phenix-online.org' target='_blank'>Phenix</a> crystallographic package. CCTBX allows for consistent validation results with Phenix,
//      as well as added functionality, such as geometry regularization of NQH flips. Read more about this change <a style=\"color: #66FFFF\" href='".makeEventURL("onGoto", "helper_cctbx.php")."'>here</a>.
//      <br><br>We have updated Reduce to add hydrogens at a length more consistent with electron-cloud positions, and accordingly
//      adjusted the Van der Waals radii in Probe to compensate for the change.  This will affect comparison of results calculated with older versions of MolProbity,
//      but generally results in lower clashscores. For analyses using nuclear-position hydrogens, you have the option of selecting nuclear x-H positions when adding hydrogens.
//      Read more about this change <a style=\"color: #66FFFF\" href='".makeEventURL("onGoto", "helper_hydrogens.php")."'>here</a>.
//      <p>Ramachandran scoring has also been updated to use new six-category distributions, derived from a larger
//      <a style=\"color: #66FFFF\" href='http://kinemage.biochem.duke.edu/databases/top8000.php' target='_blank'>Top8000</a> dataset of high quality PDB files.
//        <br><br>Please don't hesitate to report any <a style=\"color: #66FFFF\" href='".makeEventURL("onGoto", "feedback_setup.php")."'>bugs</a> you may encounter.
//        <br><br>If for some reason you need to use MolProbity4 version 4.02, which is now a retired legacy version, please go to <a style=\"color: #66FFFF\" href='http://rutile.biochem.duke.edu/' target='_blank'>http://rutile.biochem.duke.edu</a>.</strong></div>");
    echo("<div class=feature><b>Molprobity sites:</b>
      <br><a href='http://molprobity.biochem.duke.edu/' target='_blank'>Duke (US)</a> | <a href='http://molprobity.manchester.ac.uk/' target='_blank'>Manchester (UK)</a> | <a href='http://magma.research.duhs.duke.edu/~molprobity_beta/' target='_blank'>Beta (Recent developments; Unstable)</a>
      <br><a href='http://rutile.biochem.duke.edu/' target='_blank'>Legacy version 4.02</a> (Current Molprobity requires strict PDB format. 4.02 is more forgiving, but lacks recent features. Format repair is preferable.)
      <br>
      <br><b>Usage Guidelines:</b>
      <br>These web services are provided for analysis of individual structures, not batch runs.</div>");
      //("<div class=feature><b>Molprobity sites:</b>
      //<br>Duke mirror: <a href='http://molprobity.biochem.duke.edu/' target='_blank'>http://molprobity.biochem.duke.edu/index.php</a>
      //<br>Manchester mirror: <a href='http://molprobity.manchester.ac.uk/' target='_blank'>http://molprobity.manchester.ac.uk</a>
      //<br>Public beta: <a href='http://magma.research.duhs.duke.edu/~molprobity_beta/' target='_blank'>http://magma.research.duhs.duke.edu/~molprobity_beta</a>
      //<br>Legacy pre-CCTBX version: <a href='http://rutile.biochem.duke.edu/' target='_blank'>http://rutile.biochem.duke.edu</a>
      //(CCTBX, which powers this version of MolProbity (4.3), requires stricter adherence to PDB format. If you are having trouble with version 4.3 try using this MolProbity4 legacy version 4.02). Note that not all features are supported on legacy 4.02 and we suggest repairing your PDB format instead.
      //<br></div>");
}

#}}}########################################################################

#{{{ determineHttps
/**
* Intended to be a temporary function for automatically inserting the URL linking to the
* old nuclear position Molprobity server.  This assumes we're still using a system where
* the new version is at https:// and the old one is at http://
*/
function determineHttps()
{
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
    $oldUrl = 'http://';
    $oldUrl .= $_SERVER["SERVER_NAME"];
    return $oldUrl;
  }
  return "https://".$_SERVER["SERVER_NAME"];
}
#}}}

#{{{ displayUploadOld - outputs the file upload/fetch controls
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
function displayUploadOld($context)
{
    echo makeEventForm("onUploadOrFetch") . "\n";
    //echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onCall", "upload_setup.php")."'>more options</a>)</h5>";
    echo "<h5 class='welcome'>File Upload/Retrieval (<a href='".makeEventURL("onCall", "upload_setup.php")."' onclick='toggleUploadOptions(); return false' id='upload_options_link'>more options</a>)</h5>";
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
    <td align='center' width='50%'>PDB/NDB code: <input type="text" name="pdbCode" size="6" maxlength="10"></td>
    <td align='center' width='50%'><input type="file" name="uploadFile"></td>
</tr><tr>
    <td align='center'>
        <select name='fetchType'>
            <option value='pdb'>PDB/NDB file</option>
            <option value='biolunit'>Biol. unit (PDB only)</option>
            <option value='eds_2fofc'>2Fo-Fc map (EDS)</option>
            <option value='eds_fofc'>Fo-Fc map (EDS)</option>
        </select>
        <input type="submit" name="cmd" value="Fetch &gt;">
    </td>
    <td align='center'>
        <select name='uploadType'>
            <option value='pdb'>PDB file</option>
            <option value='kin'>kinemage</option>
            <option value='map'>ED map</option>
            <option value='hetdict'>het dict</option>
        </select>
        <input type="submit" name="cmd" value="Upload &gt;">
    </td>
</tr>
</table>
    <div style='display:none' id='upload_options_block'>
    <!-- We have to start a new table because you can't show/hide <tr>'s, at least not in Safari -->
        <table border='0' width='100%'><tr valign='top'>
            <td width='50%'><!-- <div class='inline_options'>
                <label><input type="checkbox" name="biolunit" value="1"> Biol. unit (PDB only)</label>
                <br><label><input type="checkbox" name="eds_2fofc" value="1"> Get 2Fo-Fc map from EDS</label>
                <br><label><input type="checkbox" name="eds_fofc" value="1"> Get Fo-Fc map from EDS</label>
            </div> --></td>
            <td width='50%'><div class='inline_options'>
                <label><input type="checkbox" name="isCnsFormat" value="1"> File is from CNS refinement</label>
                <br><label><input type="checkbox" name="ignoreSegID" value="1"> Ignore segID field</label>
            </div></td>
        </tr></table>
    </div>
</div></form>
<?php
}
#}}}########################################################################

#{{{ onSetWorkingModel
############################################################################
function onSetWorkingModel()
{
    $_SESSION['lastUsedModelID'] = $_REQUEST['workingModel'];
}
#}}}########################################################################

#{{{ onUploadOrFetch
############################################################################
/**
* FUNKY: This simulates being on the upload page and then calls the appropriate
* event handler depending on whether a file has been uploaded or not...
* Don't try this at home!
*/
function onUploadOrFetch()
{
    //$req = $_REQUEST;
    $hasUpload = isset($_FILES['uploadFile']) && $_FILES['uploadFile']['error'] != UPLOAD_ERR_NO_FILE;
    $hasFetch = isset($_REQUEST['pdbCode']) && $_REQUEST['pdbCode'] != "";

    pageCall("upload_setup.php"); // or else a later pageReturn() will screw us up!
    $upload_delegate = makeDelegateObject();

    if($hasUpload)
    {
        if($_REQUEST['uploadType'] == 'pdb')         $upload_delegate->onUploadPdbFile();
        elseif($_REQUEST['uploadType'] == 'xray')    $upload_delegate->onUploadXray();
        elseif($_REQUEST['uploadType'] == 'kin')     $upload_delegate->onUploadKinemage();
        elseif($_REQUEST['uploadType'] == 'map')     $upload_delegate->onUploadMapFile();
        elseif($_REQUEST['uploadType'] == 'hetdict') $upload_delegate->onUploadHetDictFile();
    }
    elseif($hasFetch)
    {
        if($_REQUEST['fetchType'] == 'pdb')          $upload_delegate->onFetchPdbFile();
        elseif($_REQUEST['fetchType'] == 'pdb_xray')
        {
            $_REQUEST['xray'] = 1;
            $upload_delegate->onFetchPdbFile();
        }
        elseif($_REQUEST['fetchType'] == 'biolunit_xray')
        {
            $_REQUEST['biolunit'] = 1;
            $_REQUEST['xray'] = 1;
            $upload_delegate->onFetchPdbFile();
        }
        elseif($_REQUEST['fetchType'] == 'biolunit')
        {
            $_REQUEST['biolunit'] = 1;
            $upload_delegate->onFetchPdbFile();
        }
        elseif($_REQUEST['fetchType'] == 'eds_2fofc')
        {
            $_REQUEST['eds_2fofc'] = 1;
            $upload_delegate->onFetchEdsMap();
        }
        elseif($_REQUEST['fetchType'] == 'eds_fofc')
        {
            $_REQUEST['eds_fofc'] = 1;
            $upload_delegate->onFetchEdsMap();
        }
    }
    else
        $upload_delegate->onUploadPdbFile();
}
#}}}########################################################################

#{{{ [NOT USED] onDownloadPopularZip
############################################################################
/**
* FUNKY: This turns into a binary file download rather than an HTML page,
* and then calls die(), leaving the user on the original HTML page.
*
* This code has been shown to cause cancer in lab rats.
*/
function onDownloadPopularZip()
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

#{{{ onVisInterface - allows us to skip file selection
############################################################################
function onVisInterface()
{
    if($_SESSION['lastUsedModelID'])
        pageCall("interface_setup2.php", array('modelID' => $_SESSION['lastUsedModelID']));
    else
        pageCall("interface_setup1.php");
}
#}}}########################################################################

#{{{ onConvertToBiolUnit - for uploaded multi-model files
############################################################################
function onConvertToBiolUnit()
{
    if($_SESSION['lastUsedModelID'])
    {
        $oldID      = $_SESSION['lastUsedModelID'];
        $model      = $_SESSION['ensembles'][$oldID];
        if(!$model) return;

        $modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
        $infile     = "$modelDir/$model[pdb]";
        $tmpfile    = convertModelsToChains($infile);

        $newModel = createModel("$oldID-biolunit");
        $newID = $newModel['id'];
        $newModel['stats'] = pdbstat($tmpfile);
        $newModel['history'] = 'Converted from ensemble to biological unit';
        $newModel['isUserSupplied'] = $model['isUserSupplied'];

        if(!file_exists($modelDir)) mkdir($modelDir, 0777);
        copy($tmpfile, "$modelDir/$newModel[pdb]");
        unlink($tmpfile);

        $_SESSION['models'][$newID] = $newModel;
        $_SESSION['lastUsedModelID'] = $newID;
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
