<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page should display the results of our analysis to the user.

INPUTS (via Get or Post):
    paramName       description of parameter

OUTPUTS (via Post):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Analysis results");

// Load data files
$model = $_SESSION['models'][ $_SESSION['bgjob']['model'] ];

$cbdev  = loadCbetaDev("$model[dir]/$model[prefix]cbdev.data");
$rota   = loadRotamer("$model[dir]/$model[prefix]rota.data");
$rama   = loadRamachandran("$model[dir]/$model[prefix]rama.data");
$clash  = loadClashlist("$model[dir]/$model[prefix]clash.data");

// Print them out
echo "<pre>\n";     // fixed-width text that doesn't collapse spaces
print_r($cbdev);    // print Recursive (prints array, and arrays in it, etc)
//print_r($rota);
//print_r($rama);
//print_r($clash);
echo "\n</pre>\n";

// sessTag is what carries our session ID.
// Do a print_r($_SESSION) to see what's in there.
echo "<p><a href='analyze_tab.php?$_SESSION[sessTag]'>Done</a>";

############################################################################
?>

<!-- HTML code may want to go here... -->

<?php echo mpPageFooter(); ?>
