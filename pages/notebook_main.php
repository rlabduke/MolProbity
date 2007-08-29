<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users view and edit their lab notebooks.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/labbook.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class notebook_main_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    mpLog("notebook-view:User visited Lab Notebook page");
    
    $labbook = openLabbook();
    echo $this->pageHeader("Lab notebook", "notebook");
    
    // Set time zone form
    echo makeEventForm("onSetTimezone");
    echo "<table border='0' width='100%'><tr>\n";
    echo "<td>Current time: " . formatTime(time()) . "</td>\n";
    echo "<td>" . timeZonePicker('timezone', $_SESSION['timeZone']);
    echo "<input type='submit' name='cmd' value='Set time zone'></td>\n";
    echo "</tr></table></form>\n";
    echo "</div>\n<br>\n<div class='pagecontent'>\n";
    
    // Notebook table of contents
    echo "<h3><a name='top'</a>Table of contents:</h3>\n";
    echo "<div class='indent'>\n";
    $this->printTOC($labbook);
    echo makeEventForm("onNotebookEdit");
    echo "<input type='submit' name='cmd' value='Create new entry'>\n</form>\n";
    echo "</div>\n";
    
    // Actual notebook entries
    foreach($labbook as $num => $entry)
    {
        $this->printEntry($num, $entry);
    }

    echo $this->pageFooter();
}// end of display+0
#}}}########################################################################

#{{{ printEntry - prints an entry along with controls
############################################################################
function printEntry($num, $entry)
{
    echo "<hr>\n";
    echo "<a name='entry$num'</a>";
    echo formatLabbookEntry($entry);
    echo "</hr>\n";
    echo "<p><a href='#top'>Top</a> | <a href='".makeEventURL("onNotebookEdit", $num)."'>Edit</a>\n";
}
#}}}########################################################################

#{{{ printTOC - prints a HTML table-of-contents for the lab notebook
############################################################################
function printTOC($book)
{
    if(count($book) == 0)
    {
        echo "<center><p><i>No entries have been made in the lab notebook.</i></p></center>\n";
    }
    else
    {
        echo "<table border='0' width='100%'>\n";
        foreach($book as $num => $entry)
        {
                $title = $entry['title'];
                if($title == "") $title = "(no title)";
                echo "<tr><td width='30'><img src='img/$entry[thumbnail]' border='0' width='20' height='20'></td>";
                echo "<td><a href='#entry$num'>$title</a></td>";
                echo "<td align='right'><i>".formatDayTimeBrief($entry['modtime'])."</i></td></tr>\n";
        }
        echo "</table>\n";
    }
}
#}}}########################################################################

#{{{ onSetTimezone - sets the users preferred time zone for time display
############################################################################
/**
* Documentation for this function.
*/
function onSetTimezone()
{
    $req = $_REQUEST;
    if(isset($req['timezone']))
    {
        $_SESSION['timeZone'] = $req['timezone'];
        mpLog("timezone:User specified timezone as ".$req['timezone']);
    }
}
#}}}########################################################################

#{{{ onNotebookEdit
############################################################################
/**
* $arg is the entry number to be edited
*/
function onNotebookEdit($arg)
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
