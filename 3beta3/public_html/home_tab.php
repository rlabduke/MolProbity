<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page is the command center for MolProbity.
    It features instructions, flowcharts, documentation, etc.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    if(mpStartSession(true)) mpLog("new-session:New interactive user session started on the web");

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

<p><center><div class="alert">
This version of MolProbity is still undergoing rapid development
and is currently available for beta-testing ONLY.
For production work, please use the stable version at http://kinemage.biochem.duke.edu
</div></center>

<p>Welcome to MolProbity, a tool for assessing and improving the quality of macromolecular models.
<a href="upload_tab.php?<?php echo $_SESSION['sessTag']; ?>">Get started</a> by uploading a coordinate file,
or read below to learn more.

<p><small>
<a href="#flowchart">MolProbity flowchart</a> |
<!-- <a href="#info">How it works</a> | -->
<a href="#citation">Citation reference</a>
</small>

<hr>
<a name="flowchart"></a>
Click on a step in the flowchart to be taken there:
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


<!-- <hr><p>Things that should go on this page:
<ul>
<li>User manual</li>
<li>Tutorials</li>
<li>Link for sending email (Feedback)</li>
<li>Links to download Mage / Java</li>
<li>Java version detector?</li>
<li><?php
if(!$_SESSION['moreOpts']['all'])
    echo "<br><a href='home_tab.php?$_SESSION[sessTag]&moreOpts_all=1'>Always show all available options</a>\n";
else
    echo "<br><a href='home_tab.php?$_SESSION[sessTag]&moreOpts_all=0'>Hide extra options by default</a>\n";
?></li>
</ul> -->


<hr>
<a name="citation"></a>
<b>For protein work, please cite:</b>
<p>
Simon C. Lovell, Ian W. Davis, W. Bryan Arendall III, Paul I. W. de Bakker, J. Michael Word,
Michael G. Prisant, Jane S. Richardson, David C. Richardson (2003)
<a href="http://kinemage.biochem.duke.edu/validation/valid.html" target=_blank>
Structure validation by C-alpha geometry: phi, psi, and C-beta deviation.</a>
Proteins: Structure, Function, and Genetics. <u>50</u>: 437-450.
</p>

<p><b>For nucleic acid work, please cite:</b></p>
<p>
Ian W. Davis, Laura Weston Murray, Jane S. Richardson and David C. Richardson (2004)
<a href="http://kinemage.biochem.duke.edu/research/rna/rnarotamer.php" target=_blank>
MolProbity: structure validation and all-atom contact analysis for nucleic acids and their complexes.</a>
Nucleic Acids Research. <u>32</u>: W615-W619 (Web Server issue).
</p>

<p><b>Grants supporting this work:</b>
<ul>
<li>NIH Grant GM-15000, funding Richardson Lab research for over 34 years</li>
<li>NIH Grant GM-61302, funding RLab for over 3 years</li>
<li>A <a href="http://www.hhmi.org/" target=_blank>HHMI</a> Predoctoral Fellowship to IWD</li>
</ul>

<?php echo mpPageFooter(); ?>
