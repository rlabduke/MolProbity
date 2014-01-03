<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_cctbx_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("About CCTBX and MolProbity4", "helper_cctbx");
?>
    <b>MolProbity4, now powered by CCTBX</b> <p>
<p>2014.01.03

<p>Jeff Headd, Jane Richardson, and Dave Richardson, Duke University

<p>Beginning with version 4.1, MolProbity4 nows uses validation routines from The Computational Crystallography Toolbox <a style=\"color: #66FFFF\" href='http://cctbx.sourceforge.net' target='_blank'>(CCTBX)</a> (Grosse-Kunstleve 2002), which comprises
   the open-source components of the <a style=\"color: #66FFFF\" href='http://www.phenix-online.org' target='_blank'>Phenix</a> (Adams 2010) package.
<p>There are numerous benefits to this change, including:<br>
<ul>
<li>
    <p>Consistent validation calculations with the Phenix equivalents, including
    clashscore, rotamer and Ramachandran statistics, and C&beta; deviation calculations.</p>
</li>
<li>
    <p>Expanded bond and angle validation, which now includes side-chain analysis.
    The target values and expected deviations are also consistent between MolProbity4 and Phenix.</p>
</li>
<li>
    <p>Alternate conformations are now treated where appropriate, and are listed in the multicriterion chart
    and included in the multicriterion kinemage.</p>
</li>
<li>
    <p>N/Q/H residues that are flipped during hydrogen addition/optimization are now geometry-regularized using a CCTBX-powered routine.
    This regularization puts a harmonic restraint on the starting position of each side-chain atom from C&beta; outward,
    maintaining the same general position of each atom, while restoring more reasonable bond and angle geometry after reassigning
    atom positions. Final models available for download now contain these optimized atom positions, providing users with more geometrically reasonable models.
    We do, however, still recommend that users run addition cycles of refinement following N/Q/H correction/optimization before final deposition. </p>
</li>
</ul>

<p><b>References</b>
<p>Adams PD, et al. (2010) PHENIX: a comprehensive Python-based system for macromolecular structure solution. Acta Cryst. D66: 213-221.
<p>Grosse-Kunstleve RW, Sauter NK, Moriarty NW, Adams PD (2002) The Computational Crystallography Toolbox: crystallographic algorithms in a reusable software framework. J. App. Cryst. 35: 126-136.
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
