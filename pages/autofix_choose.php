<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows the user to choose fixes made by AutoFix. 
    It may re-run AutoFix if the user overrides the default choices.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/model.php');
require_once(MP_BASE_DIR.'/lib/labbook.php');
require_once(MP_BASE_DIR.'/lib/analyze.php');
require_once(MP_BASE_DIR.'/lib/core.php');

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class autofix_choose_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context may contain the following keys:
*   modelID         the ID of the model just added
*   showAllRes      true if all Residues we tried to fix should be listed
*
* OUTPUTS:
*   modelID         ID code for model to process
*   dofix[]         an array of booleans, where the keys match the second index
*                   in the data structure from decodeAutoFixUsermods()
*/
function display($context)
{
    echo $this->pageHeader("Review Leu fixes");
    
    $id = $context['modelID'];
    $model = $_SESSION['models'][$id];
    $modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    $pdb = $modelDir.'/'.$model['pdb'];

    $dataDir    = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
        if(!file_exists($dataDir)) mkdir($dataDir, 0777); // shouldn't happen
    $autoFixStats = $dataDir."/$model[parent]_stats";

//    if(file_exists($autoFixStats) and file_exists($pdb))

    $changes = decodeAutoFixUsermods($autoFixStats,$pdb);  // change the input to the stats table instead of the pdb (USER  MOD)
    $n = count($changes[0]); // How many changes are in the table?
    if ($n > 0) { $did_fix = true; }  
    
    $fixkin = $_SESSION['dataDir'].'/'.MP_DIR_KINS."/$model[parent]_autoFlip.kin";

    if(file_exists($fixkin))
    {
        echo "This Fixkin kinemage illustrates fixes made by AutoFix.\n";
        echo "Residues that AutoFix <i>suggested</i> adjusting are listed in the Views menu.\n";
        echo "<ul>\n";
        echo "<li>" . linkKinemage("$model[parent]_autoFlip.kin") . "</li>\n";
        echo "</ul>\n";
        echo "<hr>\n";
    }

    $buttonScm = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA."/$model[parent]_button.scm";

    if(file_exists($buttonScm))
    {
        echo "This Scheme file can be loaded into coot and will allow you to interactively make the changes attempted by AutoFix.\n";
        echo "<ul>\n";
        echo "<li>" . linkAnyFile("$model[parent]_button.scm", "$model[parent]_button.scm") . "</li>\n";
        echo "</ul>\n";
        echo "<i>Open this in Coot0.1.2 or later using Calculate | Run Script...</i>\n"; 
        echo "<hr>\n";
     }
    
    
    echo makeEventForm("onRerunAutofix");
    echo "<input type='hidden' name='modelID' value='$id'>\n";
    if(! $did_fix && ! $context['showAllRes'])
    {
        echo "AutoFix didn't fix any Leucines in your file.\n";
        echo "This <b>may</b> indicate that all of the Leucines in your structure are oriented correctly.\n";
    
        echo "<p><input type='submit' name='cmd' value='Continue &gt;'>\n";
    }
    else
    {
        echo "Below is a list of changes suggested by AutoFix.\n<br>";
        echo "Please leave selected the residues you would like to fix, and unselect those you wish not to fix.\n<br>";
        if($context['showAllRes'])  echo "(<a href='".makeEventURL("onShowAllRes", false)."'>Show fixed Leu residues only</a>)";
        else                        echo "(<a href='".makeEventURL("onShowAllRes", true)."'>Show all Residues AutoFix attempted to adjust</a>)";
    
        echo "<p><table border='0' cellspacing='0' width='100%'>\n";
        echo "<tr bgcolor='".MP_TABLE_HIGHLIGHT."'>";
        echo "<td align='center'><b>Fix?</b></td>\n";
        echo "<td align='center'><b>Chain</b></td>\n";
        echo "<td align='left'><b>Res#</b></td>\n";
        echo "<td align='center'><b>Res ID</b></td>\n";
        echo "<td align='left'><b>Code</b></td>\n";
        echo "<td align='center'><b>Rotamer score <i>(before)</i></b></td>\n";
        echo "<td align='center'><b>Rotamer score <i>(after)</i></b></td>\n";
        echo "<td align='center'><b>Explanation</b></td>\n";
        if($context['showAllRes'])        echo "<td align='left'><b>Score leading to rejection</b></td>\n";
        echo "</tr>\n";
        $color = MP_TABLE_ALT1;
        for($c = 0; $c < $n; $c++)
        {
            if( ( $changes[4][$c] == "LEU" && (eregi('FLIP ACCEPTED', $changes[17][$c])) ) || $context['showAllRes']) // later maybe add the other residues here
            {
                if($changes[4][$c] == "LEU" && (eregi('FLIP ACCEPTED', $changes[17][$c]))) { $checked = "checked"; }
                else                                                         { $checked = "";        }
                
                echo "<tr bgcolor='$color'>\n";
                echo "<td align='center'><input type='checkbox' $checked name='$dofix[$c]' value='1'></td>\n";
                echo "<td align='center'>" . $changes[1][$c]  . "</td>\n";
                echo "<td align='left'>"   . $changes[2][$c]  . "</td>\n";
                echo "<td align='center'>" . $changes[4][$c]  . "</td>\n";
                echo "<td align='left'>"   . $changes[18][$c] . "</td>\n";  //code
                echo "<td align='center'>" . $changes[20][$c] . "</td>\n";  
                echo "<td align='center'>" . $changes[27][$c] . "</td>\n";  
                echo "<td align='center'>" . $changes[17][$c] . "</td>\n";
                if($context['showAllRes'])    { echo "<td align='center'>" . $changes[33][$c] . "</td>\n";  }
                echo "</tr>\n";
                $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
            }
        }

        echo "</table>\n";
        
        $improveText .= $changes[34][0]."\n";

        $improveTextFile = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA."/$model[parent]_autofix_improvement.html";

        if (file_exists($improveTextFile)) 
        { 
           $in = fopen($improveTextFile,"rb");
           while(!feof($in))
           {
              if ( eregi("div", $improveText) ) { $improveText .= "and ".fgets($in); }
              else { $improveText = "<div class='feature'>By accepting the default fixes from AutoFix on this model, you will ".fgets($in); }
           }
           fclose($in);
        }
        if ( eregi("div", $improveText) )
        {
           $improveText .= "</div>";
           echo $improveText;
        }

        echo "<p><input type='submit' name='cmd' value='Regenerate structure, applying only selected fixes &gt;'>\n";
        echo "<br><small>(If you didn't make any changes, we won't recalculate.)</small>\n";
   } 
    echo $this->pageFooter();
}
#}}}########################################################################

#{{{ onRerunAutoFix
############################################################################
/**
* Documentation for this function.
*/
function onRerunAutoFix()
{
    $req        = $_REQUEST;
    $dofix      = $req['dofix'];
    $modelID    = $req['modelID'];
    $model      = $_SESSION['models'][$modelID];
    $pdb        = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];
    $url        = $_SESSION['dataURL'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];
    
    // If all changes were accepted, we will not need to re-run Reduce.
    // We're going to construct a lab notebook entry at the same time.

    $dataDir    = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
    $autoFixStats = $dataDir."/$model[parent]_stats";

    $changes = decodeAutoFixUsermods($autoFixStats,$pdb); 
    $rerun = false;
    $n = count($changes[0]); // How many changes are in the table?
    $autofix  = "<p>The following residues were fixed automatically by AutoFix:\n<ul>\n";
    $userfix  = "<p>The following residues were fixed manually by the user:\n<ul>\n";
    $userkeep = "<p>The following residues were NOT flipped, though AutoFix recommended doing so:\n<ul>\n";
    for($c = 0; $c < $n; $c++)
    {
        // Expect checks for ones fixed originally; expect no check for ones not fixed.
        $expected = eregi("FLIP ACCEPTED", $changes[17][$c]);
        if($expected)
        {
            if($dofix[$c])
            {
                $autofix .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[4][$c]}</li>\n";
            }
            else
            {
                $userkeep .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[4][$c]}</li>\n";
                $rerun = true;
            }
        }
        elseif($dofix[$c])
        {
            $userfix  .= "<li>{$changes[1][$c]} {$changes[2][$c]} {$changes[3][$c]}</li>\n";
            $rerun = true;
        }
    }
    $autofix  .= "</ul>\n</p>\n";
    $userfix  .= "</ul>\n</p>\n";
    $userkeep .= "</ul>\n</p>\n";
   
    $parent = $model[parent].".pdb";
    $model = $_SESSION['models'][$modelID];
    $map = $mapDir.'/'.$_SESSION['lastUsedED'];

    $entry = "Autofix was run on $parent using $_SESSION[lastUsedED] to identify potentially misfit Leu residues.\n";
    //$entry .= "<p>Reduce used <a href=http://kinemage.biochem.duke.edu/software/reduce.php> reduce_wwPDB_het_dict.txt </a> as the het dictonary.\n";
    $entry .= "<p>You can now download the <a href='$pdb'> fixed PDB file </a>.</p>\n";

    $fixkin = $_SESSION['dataDir'].'/'.MP_DIR_KINS."/$model[parent]_autoFlip.kin";

    if(file_exists($fixkin))
    {
        $entry .= "<p>This Fixkin kinemage illustrates fixes made by AutoFix.\n";
        $entry .= "Residues that AutoFix <i>suggested</i> adjusting are listed in the Views menu.\n";
        $entry .= "<ul>\n";
        $entry .= "<li>" . linkKinemage("$model[parent]_autoFlip.kin") . "</li>\n";
        $entry .= "</ul></p>\n";
    }

    $buttonScm = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA."/$model[parent]_button.scm";

    if(file_exists($buttonScm))
    {
        $entry .= "<p>This Scheme file can be loaded into coot and will allow you to interactively make the changes attempted by AutoFix.\n";
        $entry .= "<ul>\n";
        $entry .= "<li>" . linkAnyFile("$model[parent]_button.scm", "$model[parent]_button.scm") . "</li>\n";
        $entry .= "</ul></p>\n";
        $entry .= "<p><ul><i>Open this in Coot0.1.2 or later using Calculate | Run Script...</i>\n";
        $entry .= "</ul></p>\n";
    }

    if(strpos($autofix,  "<li>") !== false) $entry .= $autofix;
    if(strpos($userfix,  "<li>") !== false) $entry .= $userfix;
    if(strpos($userkeep, "<li>") !== false) $entry .= $userkeep;
    
    // Go ahead and make the notebook entry inline -- this can't take more than 1-2 sec.
    if($rerun)  $title = "Fixed Leu residues with user overrides to get $model[pdb]";
    else        $title = "Fixed Leu residues with AutoFix to get $model[pdb]";
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
        $title,
        $entry,
        "$parent[id]|$modelID", // applies to both old and new model
        "auto",
        "leu_fix.png"
    );
    $_SESSION['bgjob']['modelID']   = $_REQUEST['modelID'];
    $_SESSION['bgjob']['dofix']     = $_REQUEST['dofix'];
    
    // User requested changes; re-launch AutoFix
    if($rerun)
    {
        setProgress($tasks, 'autoFix');
        mpLog("autofix-custom:User made changes to fixes suggested by AutoFix");
        // launch background job
        pageGoto("job_progress.php");
        launchBackground(MP_BASE_DIR."/jobs/autofix-fix.php", "autofix_done.php", 5);
    }
    // No changes to flip states; skip straight to end
    else
    {
        mpLog("autofix-accept:User accepted all fixes proposed by AutoFix as-is");
        pageGoto("autoFix_done.php", $_SESSION['bgjob']);
    }

setProgress($tasks, 'null'); 

//pageGoto("welcome.php");
}
#}}}########################################################################

#{{{ onShowAllRes
############################################################################
/**
* Documentation for this function.
*/
function onShowAllRes($arg)
{
    //$ctx = getContext();
    //$ctx['showAllNRes] = $arg;
    //setContext($ctx);
    setContext('showAllRes', $arg);
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
