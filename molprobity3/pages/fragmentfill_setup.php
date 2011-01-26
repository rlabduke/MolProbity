<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Setup page to input parameters into fragment filling tool.
    
*****************************************************************************/
// Includes go here. For example:
require_once(MP_BASE_DIR.'/lib/labbook.php');
// public_html/index.php has already included core.php, sessions.php, etc.
// so you don't need to include them explicitly here.

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
// The name of the class must match the name of the file, with ".php" taken off
// and "_delegate" appended. See makeDelegateObject() in lib/event_page.php
class fragmentfill_setup_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Make sure you say what $context is here. For example:
*
* Context is an array containing:
*   labbookEntry    the labbook entry number for adding this new model
*/
function display($context)
{
  echo $this->pageHeader("JiffiLoop options");
  
  if(count($_SESSION['models']) > 0)
  {
    // Choose a default model to select
    $lastUsedID = $context['modelID'];
    if(!$lastUsedID) $lastUsedID = $_SESSION['lastUsedModelID'];
    
    echo makeEventForm("onFillGaps");
    echo "<h3>Select a model to fill gaps:</h3>";
    echo "<p><table width='100%' border='0' cellspacing='0' cellpadding='2'>\n";
    $c = MP_TABLE_ALT1;
    foreach($_SESSION['models'] as $id => $model)
    {
      // Alternate row colors:
      $c == MP_TABLE_ALT1 ? $c = MP_TABLE_ALT2 : $c = MP_TABLE_ALT1;
      echo " <tr bgcolor='$c'>\n";
      echo "  <td><input type='radio' name='modelID' value='$id' checked></td>\n";
      echo "  <td><b>$model[pdb]</b></td>\n";
      echo "  <td><small>$model[history]</small></td>\n";
    }
    echo "</table></p>\n";
    ?>
    <p>
    <br>
    <p>Max number of fragments to return:<input type='text' name='num_fragments' size=5 maxlength=10 value='100'>
    <p>Simulate gap starting from residue number:<input type='text' name='gap_start' size=5 maxlength=10> to <input type='text' name='gap_end' size=5 maxlength=10>
    
    <p><input type="checkbox" name="tight_params" value="1" checked> Use narrow ranges around JiffiLoop parameters
    <p><input type="checkbox" name="keep_seq" value="1"> Keep original sequences of matching loops
    <p><input type="checkbox" name="nomatch" value="1" onclick="switchTextBox()"> Instead of matching gap size, return loops of length (<15):<input type='text' disabled name='nomatch_size' size=5 maxlength=5>

    <table width='100%' border='0'><tr>
    <p><td><input type='submit' name='cmd' value='Start filling gaps &gt;'></td>
    <td align='right'><input type='submit' name='cmd' value='Cancel'></td>
    </tr></table></p></form>
    
    <hr>
    <div class='help_info'>
    <h4>Filling gaps in protein backbone using JiffiLoop</h4>
    JiffiLoop attempts to fill gaps in protein backbone using the relative geometry of the two peptides surrounding a gap, using a fragment library
    derived from the Top5200 set of PDB files. JiffiLoop fills gaps up to 15 peptides long, with best results from < 8 peptide gaps.  .
    <ul>
    <li>"Max number of fragments to return" -- changes the maximum number of fragments to return.  Be careful, setting this value too high may result in the job
    running for a long time!</li>
    <li>"Simulate gap starting from residue number___to___" -- enter start and end residues to have JiffiLoop simulate a gap between those residues.
    <li>"Use narrow ranges around JiffiLoop parameters" -- returns fragments which match the end peptide geometry fairly closely.  
    Uncheck to get fragments with a wider geometry range.</li>
    <li>"Keep original sequences of matching loops" -- by default, all non-gly, pro residues are mutated to ala.  
    Check to retain the original sequence from the source PDBs.</li>
    <li>"Instead of matching gap size, return loops of length (< 15)" -- by default, gaps are filled with the appropriate number of peptides, based on the
    residue numbers of the endpoint peptides.  Check and fill in a desired size (< 15) to search for different length fragments.</li>
    </ul>
    </div>

<!-- Simple javascript function to turn off or on the 'nomatch_size' text box when 'nomatch' is checked-->
<SCRIPT type="text/javascript">
function switchTextBox() {
  if(document.forms[0].nomatch.checked) {
    document.forms[0].nomatch_size.disabled=false
  } else {
    document.forms[0].nomatch_size.disabled=true
  }
}
</SCRIPT>
    
    <?php
    }
    else
    {
      echo "No models are available. Please <a href='".makeEventURL("onCall", "upload_setup.php")."'>upload or fetch a PDB file</a> in order to continue.\n";
      echo makeEventForm("onChooseOptions");
      echo "<p><input type='submit' name='cmd' value='Cancel'></p></form>\n";
      
    }
		
    // Here's a sample page that displays a notebook entry.
    // The notebook entry number was specified in $context['labbookEntry']
    // This is a common way to display results of a background job.
    
    // Load and format the notebook entry:
    //$labbook = openLabbook();
    //$num = $context['labbookEntry'];
    //echo formatLabbookEntry($labbook[$num]);
    
    // This line makes a URL that, when clicked, will cause the onEditNotebook()
    // function to be called. It's declared below...
    //echo "<p><a href='".makeEventURL('onEditNotebook', $num)."'>Edit notebook entry</a></p>\n";
    
    // These lines create an HTML form that will call onReturn() to be called
    // when the user clicks the Continue > button. onReturn() is declared below.
    //echo "<p>" . makeEventForm("onReturn");
    //echo "<input type='submit' name='cmd' value='Continue &gt;'>\n</form></p>\n";
    // Note the explicit </form> to end the form!
    
    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onFillGaps
############################################################################
/**
* This function calls the notebook editor so the user can modify the notebook
* entry. Control is transfered to another page, namely, notebook_edit.php.
* When that page is done, it will call pageReturn(), and control will return
* to this class--display() will be called again to show the entry.
*
* This function gets called when the user clicks the link made by display()
*
* $arg contains the entry number of the notebook entry to edit. It was specified
* by the call to makeEventURL() that occurs in display(), above.
* $req is filled in with the usually info from the form submission, but
* we don't need to use it for anything here.
*/
function onFillGaps()
{
    $req = $_REQUEST;
    if($req['cmd'] == 'Cancel')
    {
        pageReturn();
        return;
    }
    if(isset($req['modelID']))
    {
        $_SESSION['lastUsedModelID'] = $req['modelID']; // this is now the current model
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob'] = $req;
        
        mpLog("makekin:Filling fragments in: '$req[modelID]'");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/fillfragments.php", "generic_done.php", 3);
    }
    else
    {
        $context = getContext();
        if(isset($req['modelID']))      $context['modelID']     = $req['modelID'];
        //if(isset($req['scriptName']))   $context['scriptName']  = $req['scriptName'];
        setContext($context);
    }
}
#}}}########################################################################

#{{{ onEditNotebook
############################################################################
/**
* This function calls the notebook editor so the user can modify the notebook
* entry. Control is transfered to another page, namely, notebook_edit.php.
* When that page is done, it will call pageReturn(), and control will return
* to this class--display() will be called again to show the entry.
*
* This function gets called when the user clicks the link made by display()
*
* $arg contains the entry number of the notebook entry to edit. It was specified
* by the call to makeEventURL() that occurs in display(), above.
* $req is filled in with the usually info from the form submission, but
* we don't need to use it for anything here.
*/
//function onEditNotebook($arg)
//{
//    pageCall("notebook_edit.php", array('entryNumber' => $arg));
//}
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
