<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users view and edit their lab notebooks
    
INPUTS (via Get or Post):
    cmd             one of "Save" or "Set time zone"
    timezone        the abbreviation for the desired time zone
    
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

#{{{ printTOC - prints a HTML table-of-contents for the lab notebook
############################################################################
function printTOC($book)
{
    echo "<ul>\n";
    foreach($book as $num => $entry)
    {
        $title = $entry['title'];
        if($title == "") $title = "(no title)";
        echo "<li><a href='#entry$num'>$title</a> [".formatDayTime($entry['modtime'])."]</li>\n";
    }
    echo "</ul>\n";
}
#}}}########################################################################

#{{{ printEntry - prints an entry along with controls
############################################################################
function printEntry($num, $entry)
{
    echo "<hr>\n";
    echo "<a name='entry$num'>\n";
    echo formatLabbookEntry($entry);
    echo "</a>\n";
    echo "<p><a href='#top'>Top</a> | <a href='notebook_edit.php?$_SESSION[sessTag]&entryNumber=$num&submitTo=notebook_tab.php'>Edit</a>\n";
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

// Did we get a request to set the time zone?
if($_REQUEST['cmd'] == "Set time zone" && isset($_REQUEST['timezone']))
{
    $_SESSION['timeZone'] = $_REQUEST['timezone'];
}

// Load lab notebook data
$labbook = openLabbook();

// Did we get an edit request?
if($_REQUEST['cmd'] == "Save")
{
    if(isset($_REQUEST['entryNumber'])) // Replace an old entry
        $labbook[  $_REQUEST['entryNumber']  ] = $_REQUEST['labbookEntry'];
    else // Append the new entry
        $labbook[] = $_REQUEST['labbookEntry'];
    saveLabbook($labbook);
}

// Start the page
echo mpPageHeader("Lab notebook");
echo "<p>\n";
echo mpTabBar("notebook");
?>

<!-- Set time zone form -->
<form method="post" action="notebook_tab.php">
<div class="sidebar">
    <?php
        echo postSessionID();
        echo "Now: " . formatTime(time());
        echo "<br>\n";
        echo timeZonePicker('timezone', $_SESSION['timeZone']);
    ?>
    <br><input type="submit" name="cmd" value="Set time zone">
</div>
</form>

<!-- Notebook table of contents -->
<a name="top">
<?php printTOC($labbook); ?>
<form method='post' action='notebook_edit.php'>
<?php echo postSessionID(); ?>
<input type='hidden' name='submitTo' value='notebook_tab.php'>
<input type='submit' name='cmd' value='Create new entry'>
</form>
</a>
<br clear="all">

<!-- Actual notebook entries -->
<?php
    foreach($labbook as $num => $entry)
    {
        printEntry($num, $entry);
    }
?>

<?php echo mpPageFooter(); ?>
