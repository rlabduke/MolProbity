<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a nice file/folder browser with collapsible folders.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class file_browser_delegate extends BasicDelegate {

    // Needed for makeFileList() b/c we're already using the return value
    var $fileListColor;

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array with these keys:
*   isExpanded          an array of absolute dir names mapped to booleans
*/
function display($context)
{
    echo $this->pageHeader("View &amp; download files", "files");

    $list = listRecursive($_SESSION['dataDir']);
    $list = sortFilesAlpha($list);
    $this->displayDownloadForm($list, $context['isExpanded']);

    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ displayDownloadForm - also used by welcome.php
############################################################################
/**
* list              like the output of core :: listRecursive()
* isExpanded        an array of absolute dir names mapped to booleans
*/
function displayDownloadForm($list, $isExpanded)
{
    echo makeEventForm('onDownloadMarkedZip');
    echo "<a name='filelist'></a>\n"; // allows open/close links to snap us to here
    echo "<table width='100%' border='0' cellspacing='0'>\n";
    echo "<tr bgcolor='".MP_TABLE_HIGHLIGHT."'>";
    echo "<td width='48'></td>";
    echo "<td><b>File name</b></td>";
    echo "<td><b>Size</b></td>";
    echo "<td colspan='3' align='center'><b>View...</b></td>";
    echo "<td align='right'><b>Download</b></td>";
    echo "</tr>\n";
    echo $this->makeFileList($list, $_SESSION['dataDir'], $_SESSION['dataURL'], $isExpanded);

    echo "<tr filetreedepth='0'><td colspan='2'>";
    echo "<a href='#' onclick='checkAll(true); return false;'>Check all</a>\n";
    echo "- <a href='#' onclick='checkAll(false); return false;'>Clear all</a>\n";
    echo "</td><td colspan='4' align='right'>";
    echo "<input type='submit' name='cmd' value='Download checked files and folders as a ZIP archive'>\n";
    echo "</td></tr>\n";

    echo "</table>\n";
    echo "</form>\n";

?><script language='JavaScript'>
<!--
function checkAll(checkSetting)
{
    // This works even when embedded in a multi-form document
    var boxes = document.getElementsByName("zipfiles[]")
    for(var i = 0; i < boxes.length; i++) boxes[i].checked = checkSetting
    
    // This only works for the simple file-browser page
    /*for(var i = 0; i < document.forms[0].elements.length; i++)
    {
        if(document.forms[0].elements[i].type == "checkbox")
        {
            document.forms[0].elements[i].checked = checkSetting
        }
    }*/
}

function expando(linkNode)
{
    // Find the TR that contains this link (up to TD, then up to TR):
    var trNode = linkNode.parentNode.parentNode;
    var trDepth = parseInt(trNode.getAttribute('filetreedepth'));
    
    // Check current expansion state, based on icon:
    var folderRE = /closedfolder\.gif$/;
    var folderImg = linkNode.firstChild;
    if(folderRE.test(folderImg.src.toString()))
    {
        folderImg.src = "img/openfolder.gif";
        var setDisplay = '';
    }
    else
    {
        folderImg.src = "img/closedfolder.gif";
        var setDisplay = 'none';
    }
    
    // Iterate over rows until we  hit one at our level:
    var next = trNode;
    while(true)
    {
        var next = next.nextSibling;
        if(!next) break;
        if(next.nodeType != 1) continue; // text node, etc; not an element
        var nextDepth = parseInt(next.getAttribute('filetreedepth'));
        if(nextDepth <= trDepth) break;
        // on/off controlled by arrow icon of dir:
        next.style.display = setDisplay;
        // on/off controlled as a toggle, row by row:
        //if(next.style.display)
        //{
        //    if(nextDepth == trDepth+1)
        //        next.style.display = '';
        //}
        //else
        //{
        //    next.style.display = 'none';
        //}
    }
}
// -->
</script>
<?php
}
#}}}########################################################################

#{{{ makeFileList - returns tables rows to display all the user's files
############################################################################
/**
* Takes the output of listRecursive()
* $isExpanded has absolute directory names as its keys and booleans as values.
*/
function makeFileList($list, $basePath, $baseURL, $isExpanded, $depth = 0, $hidden = false)
{
    if($depth === 0) $this->fileListColor = MP_TABLE_ALT1;

    $s = '';
    foreach($list as $dir => $file)
    {
        // With new JS toggles, the rows don't reliably alternate color in any given state
        //$s .= "<tr bgcolor='".$this->fileListColor."'";
        $s .= "<tr bgcolor='".MP_TABLE_ALT1."'";
        $s .= " filetreedepth='$depth'";
        if($hidden) $s .= " style='display:none;'";
        $s .= ">"; // end of the <TR>
        $s .= "<td><input type='checkbox' name='zipfiles[]' value='$basePath/".(is_array($file) ? $dir : $file)."'></td><td>";
        $this->fileListColor == MP_TABLE_ALT1 ? $this->fileListColor = MP_TABLE_ALT2 : $this->fileListColor = MP_TABLE_ALT1;
        if(is_array($file))
        {
            // Using #filelist in here means we snap back to the table on page reload,
            // rather than having to scroll all the way back down... (matters more for welcome.php)
            $s .= "<img src='img/clear_1x1.gif' width='".(16*$depth)."' height='1'>";
            if($isExpanded["$basePath/$dir"])
            {
                $s .= "<a class='file_browser' href='".makeEventURL("onFolderClose", "$basePath/$dir")."#filelist' onclick='expando(this); return false;'>";
                $s .= "<img src='img/openfolder.gif'> ";
                $s .= "<b>$dir</b></a></td><td colspan='4'></td></tr>\n";
                $s .= $this->makeFileList($file, "$basePath/$dir", "$baseURL/$dir", $isExpanded, $depth+1, $hidden);
            }
            else
            {
                $s .= "<a class='file_browser' href='".makeEventURL("onFolderOpen", "$basePath/$dir")."#filelist' onclick='expando(this); return false;'>";
                $s .= "<img src='img/closedfolder.gif'> ";
                $s .= "<b>$dir</b></a></td><td colspan='4'></td></tr>\n";
                $s .= $this->makeFileList($file, "$basePath/$dir", "$baseURL/$dir", $isExpanded, $depth+1, true);
            }
        }
        else
        {
            // 15 lines up file names with directories: 10px for dir icon + magic fudge factor
            $s .= "<img src='img/clear_1x1.gif' width='".(16*$depth + 15)."' height='1'>";
            $s .= "<small>$file</small></td>".$this->makeFileCommands("$basePath/$file", "$baseURL/$file")."</tr>\n";
        }
    }
    return $s;
}
#}}}########################################################################

#{{{ makeFileCommands - returns N table cells to act on the file
############################################################################
function makeFileCommands($path, $url)
{
    $s = '';
    $s .= "<td><small>".formatFilesize(filesize($path))."</small></td>";
    $lcPath = strtolower($path);
    // Kinemages
    if(endsWith($lcPath, ".kin"))
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td></td>";
        //$s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=kin' target='_blank'>highlighted</a></small></td>";
        //$s .= "<td><small><a href='viewking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a> | <a href='viewngl.php?$_SESSION[sessTag]&url=$url' target='_blank'>in NGL</a></small></td>";
        $s .= "<td><small><a href='viewngl.php?$_SESSION[sessTag]&url=$url' target='_blank'>in NGL</a></small></td>";
    }
    // Compressed kinemages
    elseif(endsWith($lcPath, ".kin.gz"))
    {
        $s .= "<td></td>";
        $s .= "<td></td>";
        //$s .= "<td><small><a href='viewking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a> | <a href='viewngl.php?$_SESSION[sessTag]&url=$url' target='_blank'>in NGL</a></small></small></td>";
        $s .= "<td><small><a href='viewngl.php?$_SESSION[sessTag]&url=$url' target='_blank'>in NGL</a></small></small></td>";
    }
    // PDB files with H
    elseif(endsWith($path, "H.pdb")) // have to use $path b/c the capital letter matters!
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td><small><a href='download_trimmed.php?$_SESSION[sessTag]&file=$path'>without H</a></small></td>";
        //$s .= "<td><small><a href='viewpdbking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a></small></td>";
    }
    elseif(endsWith($path, ".pdb")) // have to use $path b/c the capital letter matters!
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td></td>";
        //$s .= "<td><small><a href='viewpdbking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a></small></td>";
    }
    // PHP-encoded-array chart or table (e.g. the multicriterion chart)
    elseif(endsWith($lcPath, ".table"))
    {
        $s .= "<td></td>";
        $s .= "<td><small><a href='viewtable.php?$_SESSION[sessTag]&file=$path' target='_blank'>as table</a></small></td>";
        $s .= "<td></td>";
    }
    // HTML
    elseif(endsWith($lcPath, ".html"))
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=html' target='_blank'>as HTML</a></small></td>";
        $s .= "<td></td>";
    }
    // Binary results files
    elseif(endsWith($lcPath, ".pdf"))
    {
        $s .= "<td></td>";
        $s .= "<td></td>";
        $s .= "<td></td>";
    }
    // Binary archive files
    elseif(endsWith($lcPath, ".gz") || endsWith($lcPath, ".tgz") || endsWith($lcPath, ".zip"))
    {
        $s .= "<td></td>";
        $s .= "<td></td>";
        $s .= "<td></td>";
    }
    // The default: assume plain text...
    else
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td></td>";
        $s .= "<td></td>";
    }
    $s .= "<td align='right'><small><a href='$url'><img src='img/download.gif'> Download</a></small></td>";
    return $s;
}
#}}}########################################################################

#{{{ onFolderOpen, onFolderClose
############################################################################
function onFolderOpen($arg)
{
    $context = getContext();
    $context['isExpanded'][$arg] = true;
    setContext($context);
}

function onFolderClose($arg)
{
    $context = getContext();
    $context['isExpanded'][$arg] = false;
    setContext($context);
}
#}}}########################################################################

#{{{ onDownloadMarkedZip
############################################################################
/**
* FUNKY: This turns into a binary file download rather than an HTML page,
* and then calls die(), leaving the user on the original HTML page.
*
* This code has been shown to cause cancer in lab rats.
*/
function onDownloadMarkedZip()
{
    // Input files come with absolute paths, so we have to check them against
    // our session directory to avoid security holes!
    $basedir = realpath($_SESSION['dataDir']);
    $files = array();
    foreach($_REQUEST['zipfiles'] as $file)
    {
        $file = realpath($file);
        if(!$file || !startsWith($file, $basedir)) continue;
        $files[] = substr($file, strlen($basedir)+1);
    }
    //print_r($files);
    
    $zipfile = makeZipForFiles($basedir, $files);
    // These lines may be required by Internet Explorer
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    // See PHP manual on header() for how this works.
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="molprobity.zip"');
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
