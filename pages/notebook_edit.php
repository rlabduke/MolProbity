<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users view and edit their lab notebooks.
    
    It is intended to be accessed via pageCall().
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class notebook_edit_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is an array with these keys:
*   entryNumber     (optional) the index for the entry to edit
* OUTPUTS (via Post):
*   labbookEditCmd  one of "Save" or "Don't save"
*   entryNumber     (same as was provided as input)
*   labbookEntry    the new entry (an array)
*/
function display($context)
{
    $labbook = openLabbook();
    
    // Either retrieve the old entry or create a new one
    if(isset($context['entryNumber']))
    {
        $entry = $labbook[  $context['entryNumber']  ];
        $entry['modtime'] = time();
    }
    else
    {
        $entry = newLabbookEntry();
    }
    
    // Start the page
    echo mpPageHeader("Edit notebook entry");
    
    // Make the form
    echo makeEventForm("onSaveEntry");
    echo "<p>" . formEditLabbook($entry);
    if(isset($context['entryNumber']))
    {
        echo "<input type='hidden' name='entryNumber' value='". $context['entryNumber'] ."'>\n";
    }
    
    echo "<p><input type='submit' name='labbookEditCmd' value='Save'>\n";
    echo "<input type='submit' name='labbookEditCmd' value=\"Don't save\">\n";
    echo "</form>\n";
    
    echo "<p><i>Hint: you can use HTML tags in your lab notebook entries.</i></p>\n";
    
    // End the page
    echo mpPageFooter();
}// end of display
#}}}########################################################################

#{{{ onSaveEntry
############################################################################
/**
* If the user requested to save changes, make sure we do that before returning.
*/
function onSaveEntry($arg, $req)
{
    // Did we get an edit request?
    if($req['labbookEditCmd'] == "Save")
    {
        $labbook = openLabbook();
        if(isset($req['entryNumber'])) // Replace an old entry
        {
            $entryNum = $req['entryNumber'];
            $labbook[ $entryNum ] = $req['labbookEntry'];
            mpLog("notebook-edit:User modified existing lab notebook entry");
        }
        else // Append the new entry
        {
            $entryNum = count($labbook);
            $labbook[ $entryNum ] = $req['labbookEntry'];
            mpLog("notebook-add:User added a new entry to the lab notebook");
        }
        saveLabbook($labbook);
    }
    
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
