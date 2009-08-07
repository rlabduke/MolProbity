<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_xray_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("Evaluate X-ray structure", "helper_xray");
?>
<div class='feature'>
    <b>For a more detailed tutorial</b>, including references, see our
    <a href='http://kinemage.biochem.duke.edu/teaching/csh/' target='_blank'>Cold Spring Harbor tutorial on MolProbity</a>!
</div>

Use this guide to help you evaluate the reliablity of a structural model determined by X-ray crystallography --
not all parts of all structures (published or not) are correct,
and the errors could impact the biological conclusion you wish to draw.

<ol>
<li><b><?php echo "<a href='".makeEventURL("onCall", "upload_setup.php")."'>Choose a structure</a>"; ?>:</b>
    You need the coordinates for your model in PDB format.
    You can upload a file directly from your computer, or search for one at the
    <a href='http://www.pdb.org/' target='blank'>Protein Data Bank</a> or the
    <a href='http://ndbserver.rutgers.edu/' target='blank'>Nucleic Acid Data Bank</a>.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "reduce_setup.php")."'>Add hydrogens (strongly recommended)</a>"; ?>:</b>
    Explicit hydrogens are needed for many of MolProbity's analyses to function correctly, and
    structures determined by crystallography almost never include them.
    In most cases, we also recommend letting MolProbity flip Asn, Gln, and His residues that are fit 180 degrees backwards;
    this will give the most reliable view of H-bonding and steric interactions.
    You'll have a chance to review and override those flips, of course.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "autofix_setup.php")."'>Fix Flipped Leu residues</a>"; ?>:</b>
    A class of decoy Leucine residues has been identified.  These residues have their terminal branch Flipped from a 
    sterically allowed rotamers,  MolProbity flips these Leu residues that are fit 180 degrees backwards;
    this will give the most reliable view of the Leucine conformations.
    You'll have a chance to review and override those flips, of course.
</li>
<li><b><?php echo "<a href='".makeEventURL("onCall", "aacgeom_setup.php")."'>Analyze sterics &amp; geometry</a>"; ?>:</b>
    Don't be daunted by the array of <b>options</b> here --
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

<p>Want to <?php echo "<a href='".makeEventURL("onGoto", "helper_rebuild.php")."'>fix some of the problems</a>"; ?> you've discovered here?

<!--
<ul>
<li>XXX-TODO: About coordinate files</li>
<li>XXX-TODO: About adding H, flips, and flipkins (Reduce)</li>
<li>XXX-TODO: About all-atom contacts</li>
<li>XXX-TODO: About Ramachandran/rotamer</li>
<li>XXX-TODO: About C-beta deviations</li>
<li>XXX-TODO: About base-P measurement</li>
<li>XXX-TODO: How to use multi-criterion kinemages</li>
</ul>
-->
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
