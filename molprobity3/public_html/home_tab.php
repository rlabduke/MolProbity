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

    echo mpPageHeader("MolProbity Home", "home");
?>

<p>
<map name="GraffleExport">
	<area shape=poly coords="65,15,128,15,141,33,128,51,65,51,51,33,65,15" href="upload_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=poly coords="218,15,281,15,294,33,281,51,218,51,204,33,218,15" href="upload_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=rect coords="24,150,141,276" href="analyze_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=rect coords="204,150,321,213" href="improve_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=poly coords="204,273,204,244,263,240,321,244,321,273,263,276,204,273,204,244,263,248,321,244" href="improve_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=rect coords="24,303,141,339" href="compare_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=rect coords="204,366,321,420" href="files_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=poly coords="33,408,33,379,92,375,150,379,150,408,92,411,33,408,33,379,92,383,150,379" href="files_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=poly coords="114,111,114,82,173,78,231,82,231,111,173,114,114,111,114,82,173,86,231,82" href="upload_tab.php?<?php echo $_SESSION['sessTag']; ?>">
	<area shape=rect coords="357,305,443,333" href="notebook_tab.php?<?php echo $_SESSION['sessTag']; ?>">
</map>
<image src="img/flowchart.gif" usemap="#GraffleExport">

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
