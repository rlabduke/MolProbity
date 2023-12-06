<?php

// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class get_molprobity_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Get MolProbity", "get_molprobity");
?>
Thanks for your interest in MolProbity!
MolProbity is <b>free and open source</b> software distributed under a BSD-style license.
MolProbity is developed and maintained by the <b>Richardson laboratory</b> at Duke University
(<a href='http://kinemage.biochem.duke.edu'>http://kinemage.biochem.duke.edu</a>).

<p>It is possible to download MolProbity and install it on a computer running <b>Linux or Mac OS X</b>.
This is the preferred mode of use for (1) organizations with confidential data (e.g. Big Pharma),
(2) institutions with very heavy usage (e.g. structural genomics centers),
and (3) groups that need automated or scripted MolProbity runs.</p>

<p>If you use MolProbity in the course of your research, please cite:
<br>
<div class='indent'>Christopher J. Williams, Jeffrey J. Headd, Nigel W. Moriarty,
Michael G. Prisant, Lizbeth L. Videau, Lindsay N. Deis, Vishal Verma, Daniel A. Keedy,
Bradley J. Hintze, Vincent B. Chen, Swati Jain, Steven M. Lewis, Bryan W. Arendall 3rd,
Jack Snoeyink, Paul D. Adams, Simon C. Lovell, Jane S. Richardson, and David C. Richardson (2018)
MolProbity: More and better reference data for improved all-atom structure validation.
Protein Science <u>27</u>: 293-315.</div>
<br>
A complete list of appropriate citations can be
found <a href='help/about.html' target='_blank'>here</a>.</p>

<p><b>MolProbity is now on GitHub!</b>
The GitHub site is <a href='https://github.com/rlabduke/MolProbity' target="_blank">https://github.com/rlabduke/MolProbity</a>. You can look at the README there, or follow these instructions:
<div align="center" style="font-size:20px">
To build MolProbity from the latest version, first acquire the builder script:
<br>
wget -O install_via_bootstrap.sh https://github.com/rlabduke/MolProbity/raw/master/install_via_bootstrap.sh
<br><br>
Run 'install_via_bootstrap.sh 4' inside the directory you want to serve as the top level for MolProbity,
<br>'4' is the number of processors to use during compiling. You may change this value.
<br><br>
Run molprobity/setup.sh
</div>
<p><b>Further Notes:</b>
MolProbity requires the installation of the Java Runtime Environment (JRE) as well as the PHP 5 (CLI and HTTP server).  We strongly advise against exposing your local installation to whole internet traffic. Research your own platform target documentation for specific instructions regarding these additional requirements.
<p><b>Other Questions?</b>
Contact us at molprobity.bugreports@gmail.com

<?php
$file = "jiffiloop.tgz";
if(file_exists($file) && filesize($file) > 0)
{
    echo "<p>For the optional jiffiloop functionality, download the following ";
    echo " file and untar it in the lib/ directory.";
    echo "<p><b>Download now: <a href='$file'>".basename($file)."</a></b>";
    echo ", ".formatFilesize(filesize($file));
    echo ", last updated ".date('j M Y', filemtime($file))."\n";
}
    echo $this->pageFooter();
}
#}}}########################################################################

}//end of class definition
?>
