<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file displays all the files currently available in the user's session.
    
INPUTS (via Get or Post):
    showAll         if true, show all files instead of just archive packages

OUTPUTS (via Post):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ makeFileList - returns tables rows to display all the user's files
############################################################################
// Needed b/c we're already using the return value
$fileListColor = MP_TABLE_ALT1;

/**
* Takes the output of listRecursive()
*/
function makeFileList($list, $basePath, $baseURL, $depth = 0)
{
    global $fileListColor;
    if($depth === 0) $fileListColor = MP_TABLE_ALT1;

    $s = '';
    foreach($list as $dir => $file)
    {
        if(is_array($file))
        {
            $s .= "<tr bgcolor='".MP_TABLE_HIGHLIGHT."'><td>";
            for($i = 0; $i < $depth; $i++) $s .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $s .= "<b>$dir/</b></td><td colspan='2'></td></tr>\n";
            $s .= makeFileList($file, "$basePath/$dir", "$baseURL/$dir", $depth+1);
        }
        else
        {
            $s .= "<tr bgcolor='$fileListColor'><td>";
            for($i = 0; $i < $depth; $i++) $s .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $s .= "$file</td>".makeFileCommands("$basePath/$file", "$baseURL/$file")."</tr>\n";
            $fileListColor == MP_TABLE_ALT1 ? $fileListColor = MP_TABLE_ALT2 : $fileListColor = MP_TABLE_ALT1;
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
    $s .= "<td>".formatFilesize(filesize($path))."</td>";
    $s .= "<td><a href='$url'>Download</a></td>";
    return $s;
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("File management", "files");

if($_REQUEST['showAll'])
{
    echo "<table width='100%' border='0' cellspacing='0'>\n";
    $list = listRecursive($_SESSION['dataDir']);
    sortFilesAlpha($list);
    echo makeFileList($list, $_SESSION['dataDir'], $_SESSION['dataURL']);
    echo "</table>\n";
    echo "<p><a href='files_tab.php?$_SESSION[sessTag]&showAll=0'>Show models only</a></p>\n";
}
elseif(count($_SESSION['models']) > 0)
{
    echo "<p><b><a href='files_archive.php?$_SESSION[sessTag]&target=session'>Download all files from this session as a ZIP archive</a></b></p>\n";
    echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
    $c = MP_TABLE_ALT1;
    foreach($_SESSION['models'] as $id => $model)
    {
        // Alternate row colors:
        $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
        echo " <tr bgcolor='$c' align='center'>\n";
        echo "  <td align='left'><b>$id</b></td>\n";
        echo "  <td><a href='files_archive.php?$_SESSION[sessTag]&target=model&model=$id'>Download all model files as ZIP</a></td>\n";
        echo " </tr>\n";
        echo " <tr bgcolor='$c'>\n";
        echo "  <td colspan='2'><small>$model[history]</small></td>\n";
        echo " </tr>\n";
    }
    echo "</table></p>\n";
    echo "<p><a href='files_tab.php?$_SESSION[sessTag]&showAll=1'>Show all files</a></p>\n";
}
else
{
    echo "No models have been provided yet. Please <a href='upload_tab.php?$_SESSION[sessTag]'>get input models</a> first.";
    echo "<p><a href='files_tab.php?$_SESSION[sessTag]&showAll=1'>Show all files</a></p>\n";
}

############################################################################
?>

<!-- HTML code may want to go here... -->

<?php echo mpPageFooter(); ?>
