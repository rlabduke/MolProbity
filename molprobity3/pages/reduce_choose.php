<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to choose Asn/Gln/His flips made by Reduce.
    It may re-run Reduce if the user overrides the default choices.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');
require_once(MP_BASE_DIR.'/lib/labbook.php');

// This variable must be defined for index.php to work! Must match class below.
$delegate = new ReduceChooseDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class ReduceChooseDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID         the ID of the model just added
*   showAllNQH      true if all Asn, Gln, and His residues should be listed
*
* OUTPUTS:
*   modelID         ID code for model to process
*   doflip[]        an array of booleans, where the keys match the second index
*                   in the data structure from decodeReduceUsermods()
*/
function display($context)
{
    echo mpPageHeader("Review flips");
    
    $id = $context['modelID'];
    $model = $_SESSION['models'][$id];
    $pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];
    
    $changes = decodeReduceUsermods($pdb);
    
    // Check to see if any cliques couldn't be solved by looking for scores = -9.9e+99
    // At the same time, check to see if anything at all was flipped...
    $didnt_solve = $did_flip = false;
    $n = count($changes[0]); // How many changes are in the table?
    for($c = 0; $c < $n; $c++)
    {
        if($changes[6][$c] == "-9.9e+99")
            $didnt_solve = true;
        if(!$did_flip && ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL"))
            $did_flip = true;
    }
    if($didnt_solve)
        echo '<p><center><div class="alert">Some H-bonding networks ("cliques") were too complex to optimize.
    If this is a problem, please download Reduce and run it with a higher <code>-limit</code> setting.</div></center>
    <p>
    ';
    
    $nqkin = $_SESSION['dataDir'].'/'.MP_DIR_KINS."/$model[prefix]flipnq.kin";
    $hiskin = $_SESSION['dataDir'].'/'.MP_DIR_KINS."/$model[prefix]fliphis.kin";
    if(file_exists($nqkin) && file_exists($hiskin))
    {
        echo "These Flipkin kinemages illustrate the changes Reduce made.\n";
        echo "Residues that were flipped are marked with stars (*) in the Views menu.\n";
        echo "<ul>\n";
        echo "<li>" . linkKinemage("$model[prefix]flipnq.kin") . "</li>\n";
        echo "<li>" . linkKinemage("$model[prefix]fliphis.kin") . "</li>\n";
        echo "</ul>\n";
        echo "<hr>\n";
    }
    
    echo makeEventForm("onRerunReduce");
    echo "<input type='hidden' name='modelID' value='$id'>\n";
    echo "Below is a list of changes made while adding hydrogens.\n";
    echo "Please leave selected the residues you would like to flip, and unselect those you wish not to flip.\n";
    
    if($context['showAllNQH'])  echo "(<a href='".makeEventURL("onShowAllNQH", false)."'>Show flipped Asn/Gln/His only</a>)";
    else                        echo "(<a href='".makeEventURL("onShowAllNQH", true)."'>Show all Asn/Gln/His</a>)";
    
    echo "<p>\n";
    if(! $did_flip && ! $context['showAllNQH'])
    {
        echo "Reduce didn't flip any groups while adding hydrogens to your file.\n";
        echo "This <b>may</b> indicate that all of the Asn's, Gln's, and His's in your structure are oriented correctly.\n";
    }
    else
    {
        echo "<table border='0' cellspacing='0' width='100%'>\n";
        echo "<tr bgcolor='".MP_TABLE_HIGHLIGHT."'>";
        echo "<td align='center'><b>Flip?</b></td>\n";
        echo "<td align='center'><b>Chain</b></td>\n";
        echo "<td align='right'><b>Res#</b></td>\n";
        echo "<td align='center'><b>Res ID</b></td>\n";
        echo "<td align='left'><b>Orig</b></td>\n";
        echo "<td align='left'><b>Flip</b></td>\n";
        echo "<td align='left'><b>Flip-Orig</b></td>\n";
        echo "<td align='left'><b>Code</b></td>\n";
        echo "<td align='left'><b>Explanation</b></td>\n";
        echo "</tr>\n";
        $color = MP_TABLE_ALT1;
        for($c = 0; $c < $n; $c++)
        {
            if($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL" || $context['showAllNQH'])
            {
                if($changes[4][$c] == "FLIP" or $changes[4][$c] == "CLS-FL") { $checked = "checked"; }
                else                                                         { $checked = "";        }
                
                echo "<tr bgcolor='$color'>\n";
                echo "<td align='center'><input type='checkbox' $checked name='doflip[$c]' value='1'></td>\n";
                echo "<td align='center'>" . $changes[1][$c] . "</td>\n";
                echo "<td align='right'>" . $changes[2][$c] . "</td>\n";
                echo "<td align='center'>" . $changes[3][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[8][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[10][$c] . "</td>\n";
                echo "<td align='left'>" . ($changes[10][$c] - $changes[8][$c]) . "</td>\n";
                echo "<td align='left'>" . $changes[4][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[5][$c] . "</td>\n";
                echo "</tr>\n";
                $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
            }
        }
        echo "</table>\n";
        echo "<p><input type='submit' name='cmd' value='Regenerate H, applying only selected flips &gt;'>\n";
        echo "<br><small>(If you didn't make any changes, we won't recalculate.)</small>\n";
        echo "</form>\n";
    }
    
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onRerunReduce
############################################################################
/**
* Documentation for this function.
*/
function onRerunReduce($arg, $req)
{
    $doflip     = $req['doflip'];
    $modelID    = $req['modelID'];
    $model      = $_SESSION['models'][$modelID];
    $pdb        = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];
    $url        = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];
    
    // If all changes were accepted, we will not need to re-run Reduce.
    // We're going to construct a lab notebook entry at the same time.
    $changes = decodeReduceUsermods($pdb);
    $rerun = false;
    $n = count($changes[0]); // How many changes are in the table?
    $autoflip = "<p>The following residues were flipped automatically by Reduce:\n<ul>\n";
    $userflip = "<p>The following residues were flipped manually by the user:\n<ul>\n";
    $userkeep = "<p>The following residues were NOT flipped, though Reduce recommended doing so:\n<ul>\n";
    for($c = 0; $c < $n; $c++)
    {
        // Expect checks for ones flipped originally; expect no check for ones not flipped.
        $expected = ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL");
        if($expected)
        {
            if($doflip[$c])
            {
                $autoflip .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
            }
            else
            {
                $userkeep .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
                $rerun = true;
            }
        }
        elseif($doflip[$c])
        {
            $userflip .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
        }
    }
    $autoflip .= "</ul>\n</p>\n";
    $userflip .= "</ul>\n</p>\n";
    $userkeep .= "</ul>\n</p>\n";
    
    $parent = $_SESSION['models'][ $model['parent'] ];
    $entry = "Reduce was run on $parent[pdb] to add and optimize hydrogens, and optimize Asn, Gln, and His flips, yielding $model[pdb].\n";
    $entry .= "<p>You can now <a href='$url'>download the optimized and annotated PDB file</a> (".formatFilesize(filesize($pdb)).").</p>\n";
    if(strpos($autoflip, "<li>") !== false) $entry .= $autoflip;
    if(strpos($userflip, "<li>") !== false) $entry .= $userflip;
    if(strpos($userkeep, "<li>") !== false) $entry .= $userkeep;
    
    // Go ahead and make the notebook entry inline -- this can't take more than 1-2 sec.
    if($rerun)  $title = "Adding H with Reduce -build and user overrides gives $model[pdb]";
    else        $title = "Adding H with Reduce -build using default settings gives $model[pdb]";
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
        $title,
        $entry,
        "$parent[id]|$modelID", // applies to both old and new model
        "auto"
    );
    $_SESSION['bgjob']['modelID']   = $_REQUEST['modelID'];
    $_SESSION['bgjob']['doflip']    = $_REQUEST['doflip'];
    
    // User requested changes; re-launch Reduce
    if($rerun)
    {
        mpLog("reduce-custom:User made changes to flips suggested by Reduce -build");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/reduce-fix.php", "reduce_done.php", 5);
    }
    // No changes to flip states; skip straight to end
    else
    {
        mpLog("reduce-accept:User accepted all flips proposed by Reduce -build as-is");
        pageGoto("reduce_done.php", $_SESSION['bgjob']);
    }
}
#}}}########################################################################

#{{{ onShowAllNQH
############################################################################
/**
* Documentation for this function.
*/
function onShowAllNQH($arg, $req)
{
    $ctx = getContext();
    $ctx['showAllNQH'] = $arg;
    setContext($ctx);
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
