<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page displays statistics about the just-uploaded PDB model.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// This variable must be defined for index.php to work! Must match class below.
$delegate = new UploadPdbDoneDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class UploadPdbDoneDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   newModel        the ID of the model just added
*   labbookEntry    the labbook entry number for adding this new model
*   errorMsg        an error diagnosis from failed PDB upload
*/
function display($context)
{
    $modelID = $context['newModel'];
    $model = $_SESSION['models'][$modelID];
    $labbook = openLabbook();
    
    if($model == null)
    {
        if(isset($context['errorMsg']))
        {
            echo mpPageHeader("Model upload failed");
            echo "For some reason, your file could not be uploaded.\n<ul>\n";
            echo "<li>$context[errorMsg]</li>\n";
            echo "</ul>\n";
        }
        else
        {
            echo mpPageHeader("Model retrieval failed");
            echo "For some reason, your file could not be pulled from the network.\n<ul>\n";
            echo "<li>Check the PDB/NDB identifier code and try again.</li>\n";
            echo "<li>Check the PDB/NDB web site - their server may be down.</li>\n";
            echo "</ul>\n";
        }
        echo "<p>" . makeEventForm("onTryAgain");
        echo "<table border='0' width='100%'><tr>\n";
        echo "<td align='left'><input type='submit' name='cmd' value='&lt; Try again'></td>\n";
        echo "<td align='right'><input type='submit' name='cmd' value='Cancel upload &gt;'></td>\n";
        echo "</tr></table>\n</form></p>\n";
    }
    else // upload was OK
    {
        // Start the page: produces <HTML>, <HEAD>, <BODY> tags
        echo mpPageHeader("Model $modelID added");
    
        $num = $context['labbookEntry'];
        echo formatLabbookEntry($labbook[$num]);
        echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
        echo "<p>" . makeEventForm("onReturn");
        echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    }
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

#{{{ onEditNotebook
############################################################################
/**
* Documentation for this function.
*/
function onEditNotebook($arg, $req)
{
    pageCall("notebook_edit.php", array('entryNumber' => $arg));
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
