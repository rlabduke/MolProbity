<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page is the command center for MolProbity.
    It features instructions, flowcharts, documentation, etc.
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
    mpStartSession(true);

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

// Respond to requests for more/fewer options
if(isset($_REQUEST['moreOpts_all']))
    $_SESSION['moreOpts']['all'] = $_REQUEST['moreOpts_all'];

    echo mpPageHeader("Home page", "home");
?>

<p>An outline for how MolProbity 3 works:
<br><img src="img/flowchart.gif">

<!-- Control for global "verbosity" of options in forms -->
<hr>
<?php
if(!$_SESSION['moreOpts']['all'])
    echo "<br><a href='home_tab.php?$_SESSION[sessTag]&moreOpts_all=1'>Always show all available options</a>\n";
else
    echo "<br><a href='home_tab.php?$_SESSION[sessTag]&moreOpts_all=0'>Hide extra options by default</a>\n";
?>


<hr><p>Things that should go on this page:
<ul>
<li>User manual</li>
<li>Tutorials</li>
<li>Link for sending email (Feedback)</li>
<li>Links to download Mage / Java</li>
<li>Disk space available</li>
<li>Java version detector?</li>
<li>Better flowchart describing how to use MolProbity</li>
</ul>

<?php echo mpPageFooter(); ?>
