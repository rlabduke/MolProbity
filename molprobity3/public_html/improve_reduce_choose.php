<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file displays the flips made by Reduce -build, along with the flipkins,
    and allows the user to select which (if any) s/he wants to override.
    
INPUTS (via $_SESSION['bgjob']):
    newModel        the ID of the model just added

INPUTS (via Get or Post):
    showAllNQH      true if all Asn, Gln, and His residues should be listed

OUTPUTS (via Post):
    model           ID code for model to process
    doflip[]        an array of booleans, where the keys match the second index
                    in the data structure from decodeReduceUsermods()

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Review flips");

$id = $_SESSION['bgjob']['newModel'];
$model = $_SESSION['models'][$id];
$pdb = "$model[dir]/$model[pdb]";

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
    echo "<b>Some H-bonding cliques were too complex to optimize.</b>\n<p>\n";
    
if(modelDataExists($model, "flipnq.kin") && modelDataExists($model, "fliphis.kin"))
{
    echo "These <code>flipkin</code> kinemages illustrate the changes Reduce made.\n";
    echo "Residues that were flipped are marked with star (*) in the Views menu.\n";
    echo "<ul>\n";
    echo "<li>" . linkModelKin($model, "flipnq.kin") . "</li>\n";
    echo "<li>" . linkModelKin($model, "fliphis.kin") . "</li>\n";
    echo "</ul>\n";
    echo "<hr>\n";
}

############################################################################
?>

<form method='post' action='improve_reduce_launch2.php'>
<?php
    echo postSessionID();
    echo "<input type='hidden' name='model' value='$id'>\n";
?>
Below is a list of changes made while adding hydrogens.
Please select (or leave selected) the residues you would like to flip, and
unselect those you wish not to flip.
<?php
    if($showAllNQH) echo "(<a href='improve_reduce_choose.php?$_SESSION[sessTag]&showAllNQH=0'>Show flipped Asn/Gln/His only</a>)";
    else            echo "(<a href='improve_reduce_choose.php?$_SESSION[sessTag]&showAllNQH=1'>Show all Asn/Gln/His</a>)";
    
    echo "<p>\n";
    if(! $did_flip && ! $showAllNQH) {
?>
    Reduce didn't flip any groups while adding hydrogens to your file.
    This <b>may</b> indicate that all of the Asn's, Gln's, and His's
    in your structure are correctly assigned.
<?php } else { ?>
    <table border='1'>
    <tr>
        <td align='center'><b>Flip?</b></td>
        <td align='center'><b>Chain</b></td>
        <td align='center'><b>Residue #</b></td>
        <td align='center'><b>Residue ID</b></td>
        <td align='center'><b>Original</b></td>
        <td align='center'><b>Flipped</b></td>
        <td align='center'><b>Flip. - Orig.</b></td>
        <td align='center'><b>Code</b></td>
        <td align='center'><b>Explanation</b></td>
    </tr>
    <?php
        for($c = 0; $c < $n; $c++)
        {
            if($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL" || $showAllNQH)
            {
                if($changes[4][$c] == "FLIP" or $changes[4][$c] == "CLS-FL") { $checked = "checked"; }
                else                                                         { $checked = "";        }
                
                echo "<tr>\n";
                echo "<td align='center'><input type='checkbox' $checked name='doflip[$c]' value='1'></td>\n";
                echo "<td align='left'>" . $changes[1][$c] . "</td>\n";
                echo "<td align='right'>" . $changes[2][$c] . "</td>\n";
                echo "<td align='center'>" . $changes[3][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[8][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[10][$c] . "</td>\n";
                echo "<td align='left'>" . ($changes[10][$c] - $changes[8][$c]) . "</td>\n";
                echo "<td align='left'>" . $changes[4][$c] . "</td>\n";
                echo "<td align='left'>" . $changes[5][$c] . "</td>\n";
               echo "</tr>\n";
            }
        }
    ?>
    </table>
<?php } ?>
<p><center><input type='submit' name='cmd' value='Regenerate H, applying only selected flips'>
<br><small>(If you didn't make any changes, we won't recalculate.)</small>
</center>
</form>
<?php echo mpPageFooter(); ?>
