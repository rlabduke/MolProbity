<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page displays statistics about the just-uploaded PDB model.
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new UploadOtherDoneDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class UploadOtherDoneDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   type            the type of file uploaded. One of 'map', ...
*   errorMsg        an error diagnosis from failed PDB upload
*   mapName         the name of the just-added map file (map upload only)
*/
function display($context)
{
    if(isset($context['errorMsg']))
    {
        echo mpPageHeader("File upload failed");
        echo "For some reason, your file could not be uploaded.\n<ul>\n";
        echo "<li>$context[errorMsg]</li>\n";
        echo "</ul>\n";
        echo "<p>" . makeEventForm("onTryAgain");
        echo "<table border='0' width='100%'><tr>\n";
        echo "<td align='left'><input type='submit' name='cmd' value='&lt; Try again'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel upload &gt;'></td>\n";
        echo "</tr></table>\n</form></p>\n";
        echo mpPageFooter();
    }
    else // upload was OK
    {
        $type = $context['type'];
        if($type == 'map')          $this->displayMap($context);
        elseif($type == 'hetdict')  $this->displayHetDict($context);
    }
}
#}}}########################################################################

#{{{ displayMap
############################################################################
function displayMap($context)
{
    echo mpPageHeader("Map $context[mapName] added");
    echo "<p>The following electron density maps are now available:\n";
    echo "<ul>\n";
    foreach($_SESSION['edmaps'] as $map)
    {
        $mapPath = "$_SESSION[dataDir]/$map";
        echo "<li><b>$map</b> (".formatFilesize(filesize($mapPath)).")</li>\n";
    }
    echo "</ul>\n</p>\n";
    echo "<p>" . makeEventForm("onReturn");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ displayHetDict
############################################################################
function displayHetDict($context)
{
    echo mpPageHeader("Custom het dictionary added");
    echo "<p>Your custom heterogen dictionary will be used for all future work in this session.</p>\n";
    echo "<p>" . makeEventForm("onReturn");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onReturn
############################################################################
/**
* Documentation for this function.
*/
function onReturn($arg, $req)
{
    pageReturn();
}
#}}}########################################################################

#{{{ onTryAgain
############################################################################
/**
* Documentation for this function.
*/
function onTryAgain($arg, $req)
{
    if($req['cmd'] == '< Try again')
        pageGoto("upload_setup.php");
    else
        pageReturn();
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
