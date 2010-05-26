<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page that displays system info for debugging the session.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    //mpStartSession();
    mpInitEnvirons();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    mpSessReadOnly();

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
echo mpPageHeader("Get MolProbity");
?>

Thanks for your interest in MolProbity!
MolProbity is <b>free and open source</b> software distributed under a BSD-style license.
MolProbity is developed and maintained by the <b>Richardson laboratory</b> at Duke University
(<a href='http://kinemage.biochem.duke.edu'>http://kinemage.biochem.duke.edu</a>).

<p>It is possible to download MolProbity and install it on a computer running <b>Linux or Mac OS X</b>.
This is the preferred mode of use for (1) organizations with confidential data (e.g. Big Pharma),
(2) institutions with very heavy usage (e.g. structural genomics centers),
and (3) groups that need automated or scripted MolProbity runs.

<p>Installation <b>instructions</b> for MolProbity are provided, in the file named
<code>doc/installing.html</code>.


<?php
$file = "molprobity.tgz";
if(file_exists($file) && filesize($file) > 0)
{
    echo "<p><b>Download now: <a href='$file'>".basename($file)."</a></b>";
    echo ", ".formatFilesize(filesize($file));
    echo ", last updated ".date('j M Y', filemtime($file))."\n";
}
else
{
?>
<p><b>This server does not offer MolProbity downloads.</b>  Please see the main site at
<a href='http://kinemage.biochem.duke.edu'>http://kinemage.biochem.duke.edu</a>
for an up-to-date copy of the source code.
<?php
}
?>

<p>For the optional jiffiloop functionality, download the following file and untar it in
the lib/ directory.
<?php
$file = "jiffiloop.tgz";
if(file_exists($file) && filesize($file) > 0)
{
    echo "<p><b>Download now: <a href='$file'>".basename($file)."</a></b>";
    echo ", ".formatFilesize(filesize($file));
    echo ", last updated ".date('j M Y', filemtime($file))."\n";
}
?>
<?php echo mpPageFooter(); ?>
