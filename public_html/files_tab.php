<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file displays all the files currently available in the user's session.
    
INPUTS (via Get or Post):
    paramName       description of parameter

OUTPUTS (via Post):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
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
/**
* Takes the output of listRecursive()
*/
function makeFileList($list, $basePath, $baseURL, $depth = 0)
{
    $s = '';
    $c = "#ffffff";
    foreach($list as $dir => $file)
    {
        if(is_array($file))
        {
            $s .= "<tr bgcolor='#ccccff'><td>";
            for($i = 0; $i < $depth; $i++) $s .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $s .= "<b>$dir/</b></td><td colspan='2'></td></tr>\n";
            $s .= makeFileList($file, "$basePath/$dir", "$baseURL/$dir", $depth+1);
        }
        else
        {
            $s .= "<tr bgcolor='$c'><td>";
            for($i = 0; $i < $depth; $i++) $s .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $s .= "<i>$file</i></td>".makeFileCommands("$basePath/$file", "$baseURL/$file")."</tr>\n";
        }

        $c == "#ffffff" ? $c = "#e8e8e8" : $c = "#ffffff"; // alternate row colors
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

echo "<table width='100%' border='0' cellspacing='0'>\n";
$list = listRecursive($_SESSION['dataDir']);
sortFilesAlpha($list);
echo makeFileList($list, $_SESSION['dataDir'], $_SESSION['dataURL']);
echo "</table>\n";

############################################################################
?>

<!-- HTML code may want to go here... -->

<?php echo mpPageFooter(); ?>
