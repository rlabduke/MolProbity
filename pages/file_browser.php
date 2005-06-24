<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a nice file/folder browser with collapsible folders.
*****************************************************************************/
// Needed for makeFileList() b/c we're already using the return value
$fileListColor = MP_TABLE_ALT1;

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class file_browser_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array with these keys:
*   isExpanded          an array of absolute dir names mapped to booleans
*/
function display($context)
{
    echo mpPageHeader("View &amp; download files", "files");
    echo "<table width='100%' border='0' cellspacing='0'>\n";
    echo "<tr bgcolor='".MP_TABLE_HIGHLIGHT."'>";
    echo "<td><b>File name</b></td>";
    echo "<td><b>Size</b></td>";
    echo "<td colspan='2' align='center'><b>View...</b></td>";
    echo "<td align='right'><b>Download</b></td>";
    $list = listRecursive($_SESSION['dataDir']);
    $list = sortFilesAlpha($list);
    echo $this->makeFileList($list, $_SESSION['dataDir'], $_SESSION['dataURL'], $context['isExpanded']);
    echo "</table>\n";
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ makeFileList - returns tables rows to display all the user's files
############################################################################
/**
* Takes the output of listRecursive()
* $isExpanded has absolute directory names as its keys and booleans as values.
*/
function makeFileList($list, $basePath, $baseURL, $isExpanded, $depth = 0)
{
    global $fileListColor;
    if($depth === 0) $fileListColor = MP_TABLE_ALT1;

    $s = '';
    foreach($list as $dir => $file)
    {
        $s .= "<tr bgcolor='$fileListColor'><td>";
        $fileListColor == MP_TABLE_ALT1 ? $fileListColor = MP_TABLE_ALT2 : $fileListColor = MP_TABLE_ALT1;
        if(is_array($file))
        {
            $s .= "<img src='img/clear_1x1.gif' width='".(16*$depth)."' height='1'>";
            if($isExpanded["$basePath/$dir"])
            {
                $s .= "<a href='".makeEventURL("onFolderClose", "$basePath/$dir")."'>";
                $s .= "<img src='img/openfolder.gif'></a> ";
                $s .= "<b>$dir</b></td><td colspan='4'></td></tr>\n";
                $s .= $this->makeFileList($file, "$basePath/$dir", "$baseURL/$dir", $isExpanded, $depth+1);
            }
            else
            {
                $s .= "<a href='".makeEventURL("onFolderOpen", "$basePath/$dir")."'>";
                $s .= "<img src='img/closedfolder.gif'></a> ";
                $s .= "<b>$dir</b></td><td colspan='4'></td></tr>\n";
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
        //$s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=kin' target='_blank'>highlighted</a></small></td>";
        $s .= "<td><small><a href='viewking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a></small></td>";
    }
    // Compressed kinemages
    elseif(endsWith($lcPath, ".kin.gz"))
    {
        $s .= "<td></td>";
        $s .= "<td><small><a href='viewking.php?$_SESSION[sessTag]&url=$url' target='_blank'>in KiNG</a></small></td>";
    }
    // PHP-encoded-array chart or table (e.g. the multicriterion chart)
    elseif(endsWith($lcPath, ".table"))
    {
        $s .= "<td></td>";
        $s .= "<td><small><a href='viewtable.php?$_SESSION[sessTag]&file=$path' target='_blank'>as table</a></small></td>";
    }
    // HTML
    elseif(endsWith($lcPath, ".html"))
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=html' target='_blank'>as HTML</a></small></td>";
    }
    // Binary results files
    elseif(endsWith($lcPath, ".pdf"))
    {
        $s .= "<td></td>";
        $s .= "<td></td>";
    }
    // Binary archive files
    elseif(endsWith($lcPath, ".gz") || endsWith($lcPath, ".tgz") || endsWith($lcPath, ".zip"))
    {
        $s .= "<td></td>";
        $s .= "<td></td>";
    }
    // The default: assume plain text...
    else
    {
        $s .= "<td><small><a href='viewtext.php?$_SESSION[sessTag]&file=$path&mode=plain' target='_blank'>plain text</a></small></td>";
        $s .= "<td></td>";
    }
    $s .= "<td align='right'><small><a href='$url'><img src='img/download.gif'> Download</a></small></td>";
    return $s;
}
#}}}########################################################################

#{{{ onFolderOpen, onFolderClose
############################################################################
function onFolderOpen($arg, $req)
{
    $context = getContext();
    $context['isExpanded'][$arg] = true;
    setContext($context);
}

function onFolderClose($arg, $req)
{
    $context = getContext();
    $context['isExpanded'][$arg] = false;
    setContext($context);
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
