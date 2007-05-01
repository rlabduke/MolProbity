<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page sumarizes the results of some operation by displaying
    a lab notebook entry. Afterwards, it will allow the user to pageReturn().
    
    This page is like generic_done except that it prompts the user to
    download the modified PDB file.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class reduce_done_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array containing:
*   labbookEntry    the labbook entry number
*   modelID         the ID of the new Reduced model
*/
function display($context)
{
    $labbook = openLabbook();
    $num = $context['labbookEntry'];

    echo $this->pageHeader($labbook[$num]['title']);
    
    //echo formatLabbookEntry($labbook[$num]);
    echo $labbook[$num]['entry']; // avoid date stamp, title clutter
    
    //echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
    echo "<p>" . makeEventForm("onReturn");
    echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    
    $modelID = $context['modelID'];
    $model = $_SESSION['models'][$modelID];
    if($modelID && $model && $model['isReduced'] && $model['isUserSupplied'])
    {
        $url = makeEventURL('onDownload', $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb']);
?>
<script language='JavaScript'>
    function confirmPdbDownload()
    {
        if(window.confirm("Your PDB file has been changed.  Would you like to download the new coordinates now?"))
            window.location.href = "<?php echo $url; ?>"
    }
    
    // This nifty function means we won't override other ONLOAD handlers
    function windowOnload(f)
    {
        var prev = window.onload;
        window.onload = function() { if(prev) prev(); f(); }
    }
    
    windowOnload(confirmPdbDownload)
</script>
<?php
    }

    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onEditNotebook
############################################################################
/**
* Documentation for this function.
*/
function onEditNotebook($arg)
{
    pageCall("notebook_edit.php", array('entryNumber' => $arg));
}
#}}}########################################################################

#{{{ onDownload
############################################################################
/**
* FUNKY: This turns into a binary file download rather than an HTML page,
* and then calls die(), leaving the user on the original HTML page.
*
* This code has been shown to cause cancer in lab rats.
*/
function onDownload($file)
{
    // These lines may be required by Internet Explorer
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    // See PHP manual on header() for how this works.
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    mpReadfile($file);
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
