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

    echo mpPageHeader("Compare models", "compare");
?>


<p>Things that should go on this page:
<ul>
<li>List of models that have already been run through analysis</li>
<li>Options for comparing them</li>
</ul>



<?php echo mpPageFooter(); ?>
