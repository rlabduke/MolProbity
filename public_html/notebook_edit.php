<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to edit one entry in their lab notebook.
    
INPUTS (via Get or Post):
    submitTo        the URL to submit form data to
    entryNumber     (optional) the index for the entry to edit

OUTPUTS (via Post):
    cmd             one of "Save" or "Don't save"
    entryNumber     (same as was provided as input)
    labbookEntry    the new entry (an array)

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
    mpSessReadOnly();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

$labbook = openLabbook();

// Either retrieve the old entry or create a new one
if(isset($_REQUEST['entryNumber']))
{
    $entry = $labbook[  $_REQUEST['entryNumber']  ];
    $entry['modtime'] = time();
}
else
{
    $entry = newLabbookEntry();
}

// Start the page
echo mpPageHeader("Edit notebook entry");

// Make the form
echo "<form method='post' action='$_REQUEST[submitTo]'>";
echo postSessionID();

echo "<p>" . formEditLabbook($entry);

if(isset($_REQUEST['entryNumber']))
{
    echo "<input type='hidden' name='entryNumber' value='". $_REQUEST['entryNumber'] ."'>\n";
}

echo "<p><input type='submit' name='cmd' value='Save'>\n";
echo "<input type='submit' name='cmd' value=\"Don't save\">\n";
echo "</form>\n";

echo "<p><i>Hint: you can use HTML tags in your lab notebook entries.</i></p>\n";

// End the page
echo mpPageFooter();
?>
