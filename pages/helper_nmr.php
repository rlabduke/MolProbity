<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_nmr_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Evaluate NMR structure", "helper_nmr");
?>
<div class='feature'>
    We currently have no tutorials for NMR structures, but many of the principles of analyzing crystal structures still apply.
    See our
    <a href='http://kinemage.biochem.duke.edu/teaching/workshop/CSHL2012/' target='_blank'>2012 Cold Spring Harbor tutorial on MolProbity</a>
    as well as our <a href='<?php echo makeEventURL("onGoto", "helper_xray.php") ?>'>guide to analyzing X-ray structures</a>.
</div>

Use this guide to help you evaluate the reliablity of a structural model determined by Nuclear Magnetic Resonance (NMR) spectroscopy --
not all parts of all structures (published or not) are correct,
and the errors could impact the biological conclusion you wish to draw.

<ol>
<li><b><?php echo "<a href='".makeEventURL("onCall", "upload_setup.php")."'>Choose a structure</a>"; ?>:</b>
    You need the coordinates for your model in PDB format.
    You can upload a file directly from your computer, or search for one at the
    <a href='http://www.pdb.org/' target='blank'>Protein Data Bank</a> or the
    <a href='http://ndbserver.rutgers.edu/' target='blank'>Nucleic Acid Data Bank</a>.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "reduce_setup.php")."'>Add hydrogens (usually unnecessary)</a>"; ?>:</b>
    Explicit hydrogens are needed for many of MolProbity's analyses to function correctly, but
    structures determined by NMR almost always include them.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "aacgeom_setup.php")."'>Analyze sterics &amp; geometry for single models</a>"; ?>:</b>
    Most NMR structures are reported as ensembles of models.
    At the moment, MolProbity has limited tools for analyzing ensembles,
    but it can easily analyze each model individually.
    <p>Don't be daunted by the array of <b>options</b> here --
    MolProbity automatically selects appropriate settings for your structure,
    so it's not necessary for you to change them unless you want to.
    Running all the various analyses may take a few minutes, so please be patient.
    <p>The <b>all-atom contact</b> analysis is concerned with steric interactions inside the model.
    Non-(H)bonded atoms with substantial (e.g. &gt;0.4&Aring;) van der Waals overlap
    are errors in the model; the energetic cost is enormous relative the stability of a macromolecule.
    The clashscore is the number of these overlaps per 1000 atoms; smaller numbers are better.
    You can see the individual clashes in the multi-criterion kinemage as hot pink spikes,
    which is useful for seeing if the errors affect your region(s) of interest.
    <p>The <b>geometric</b> analyses are also good indicators of potential errors in the model.
    Ramachandran and rotamer outliers flag very unusual conformations of the protein backbone or sidechains, respectively.
    A few of these may be genuine, but in most cases they are mistakes.
    C&beta; deviations indicate net distortion of bond angles around the C&alpha;,
    which can be diagnostic of a backwards sidechain.
    For nucleic acids, the perpendicular distance from the base to the phosphate
    is strongly correlated with the pucker of the ribose ring, making it a useful aid to fitting.
    <p>Don't be dismayed -- <b>all structures have a few problems!</b>
    These tools can help structural biologists reduce the number of problems in their structures, however,
    and can help other scientists know whether a particular model offers a believable view of a specific area.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "ens_aacgeom_setup.php")."'>Analyze sterics &amp; geometry for the ensemble</a>"; ?>:</b>
    At the moment, MolProbity has limited tools for analyzing ensembles.
    It can produce multi-model multi-criterion kinemages, but not much else (yet).
    Feature requests to Jeremy Block (jeremy.block@duke.edu).
</li>
<li><b><?php echo "<a href='".makeEventURL("onGoto", "file_browser.php")."'>Download files</a>"; ?>:</b>
    Before you leave, you may want to download some of the files you've created,
    like the PDB file with hydrogens added, or the multi-criterion kinemage.
    You can also review the results you've obtained using the
    <?php echo "<a href='".makeEventURL("onGoto", "notebook_main.php")."'>lab notebook</a>"; ?>.
</li>
<li><b><?php echo "<a href='".makeEventURL("onGoto", "logout.php")."'>Log out</a>"; ?>:</b>
    This will permanenty remove your files from our server, freeing up space for other users.
</li>
</ol>
<?php
    echo $this->pageFooter();
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
