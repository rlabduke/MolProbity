<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Description...
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

    echo mpPageHeader("Analyze model quality");
    echo "<p>\n";
    echo mpTabBar("analyze");
?>


<!-- List of current models available -->
<?php if(count($_SESSION['models']) > 0) { ?>
<p>
<h3>Models available for analysis:</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="2">
<?php
    $c = "#ffffff";
    foreach($_SESSION['models'] as $id => $model)
    {
        // Alternate row colors:
        $c == "#ffffff" ? $c = "#e8e8e8" : $c = "#ffffff";
        echo " <tr bgcolor='$c' align='center'>\n";
        echo "  <td><b>$id</b></td>\n";
        echo "  <td><span class='inactive'>Optimize H and<br>find Asn/Gln/His flips</span></td>\n";
        echo "  <td><span class='inactive'>Refit sidechains<br>with SSWING</span></td>\n";
        echo "  <td><a href='analyze_setup.php?$_SESSION[sessTag]&model=$id'>Run analysis</a></td>\n";
        echo "  <td><span class='inactive'>View results<br>of analysis</span></td>\n";
        echo "  <td><span class='inactive'>Show all files</span></td>\n";
        echo " </tr>\n";
        echo " <tr bgcolor='$c'>\n";
        echo "  <td colspan='6'><i>$model[history]</i></td>\n";
        echo " </tr>\n";
    }
?>
</table>
<?php } ?>


<p>
<?php echo mpPageFooter(); ?>
