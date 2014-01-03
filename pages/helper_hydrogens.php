<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Welcome/start page for MP3, with hints for new users about how to proceed.
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class helper_hydrogens_delegate extends BasicDelegate {

#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo $this->pageHeader("About Updated Hydrogens", "helper_hydrogens");
?>
    <b>Phenix / MolProbity Hydrogen Parameter Update</b> <p>
<p>2013.02.01

<p>Lindsay Deis, Bryan Arendall, Vincent Chen, Jeff Headd, Michael Prisant,
Lizbeth Videau, and Jane Richardson, Duke University; Nigel Moriarty, LBNL; Vishal Verma, UNC

<p>The new distribution of Phenix (Adams 2010) incorporates major updates in the parameters and procedures for hydrogen atoms, providing consistency between phenix.refine and MolProbity and more correct treatment in each system across the range of usage needs.

<p>Most distances between bonded atoms were settled long ago to high accuracy, but, in the case of hydrogens, the values in common use often differ by as much as 20%.  This is primarily because X-ray diffraction sees the electron cloud, which for hydrogen has its center systematically displaced inward from the nuclear position by more than 0.1&Aring; (Stewart 1965; Iijima 1987; Coppens 1997).  In addition, a hydrogen's electron cloud is sometimes shifted by local non-covalent interactions such as H-bonding or tight packing; both systematic and local shifts can be seen in the figure.  The current effort optimizes allowance for the systematic effects, but does not treat environment-dependent distortions.

<div style="text-align: center;">
<br><img src='img/1yk4_Trp37_He1_Asp19_crop.jpg' width='428' height='180'>
<br><small>The difference peak for He1 (blue contours) is both shifted left along the bond to its parent N atom (the systematic effect) and also upward toward the line of the H-bond (an environmental effect). Note the H nuclear position, illustrated here by a grey stick.  1YK4 Trp 37, 0.69&Aring; resolution.</small>
</div>

<p>MolProbity and Reduce have positioned H atoms at the better-determined nuclear distances (Word 1999), while Phenix has used the X-ray suitable electron-cloud distances.  This difference in parameters affects user clashscores.  In addition, we do believe, along with Pauling (1960), that all-atom contacts would more appropriately be calculated at van der Waals radii centered on the hydrogen's electron cloud.  MolProbity needs more subcategories of H atom types, while all crystallographic software with libraries for each atom in each monomer type need correction of the typos and internal inconsistencies endemic to such systems.  The largest change needed for the Phenix electron-cloud positions inherited from ShelX through CCP4 is 0.03&Aring; (for O-H) and for the MolProbity nuclear positions is 0.04&Aring; (for tetrahedral N-H). However, each system has changes of up to ~0.17&Aring; for cases where it was applying what we now consider the wrong type of value.

<p>Packing analysis and validation both depend on the total system of hydrogen bondlengths, van der Waals radii, and the 0.4&Aring; threshold defined for clashes.  Several factors have convinced us that the current system in MolProbity is slightly too strict.  We have therefore re-examined the existing sources for x-H distance values and have undertaken new computational, database, and manual analyses to settle on a confirmed, best set of electron-cloud x-H distances for implementation in both MolProbity and Phenix.  This has involved sphere-fitting to electron densities calculated using quantum mechanics, examining high-resolution H difference-density peaks, and analyzing database distributions of nearest-neighbor atom-atom distances to re-optimize the associated van der Waals radii. In addition, we have compiled bondlengths from X-ray diffraction and neutron-diffraction small-molecule structures from the literature and from the Cambridge Structural Database. The various sources of experimental and theoretical data for x-H distances unfortunately are consistent within 0.01&Aring; only for the nuclear aliphatic C-H case, so that future research would still be desirable.  However, we judge that the values presented here are correct within 0.02-0.03&Aring;, nearly an order of magnitude better than the previous situation.  Happily, we find that the new parameters produce clashscores, which better tend to zero for the best structures at mid to high resolutions, and they do a slightly better job at determining sidechain NQH flips.

<p>We are currently implementing this change in both MolProbity and Phenix so that the two services will add hydrogens identically and in an appropriately application-specific fashion. The electron-cloud values will be the default in both systems due to the predominant use for X-ray crystallography. However, each will also include an option to use updated nuclear positions for neutron-diffraction refinement, for NMR structures, or by user choice, and MolProbity will use van der Waals radii tuned for each case when calculating all-atom contacts.

<p><b>References</b>
<p>Adams PD, et al. (2010) PHENIX: a comprehensive Python-based system for macromolecular structure solution. Acta Cryst. D66: 213-221.
<p>Coppens P (1997) X-ray Charge Densities and Chemical Bonding, Oxford University Press, NY, ISBN 0-19-509823-4.
<p>Iijima H, Dunbar JBJ, Marshall GR (1987) Calibration of effective van der Waals atomic contact radii for proteins and peptides. Proteins: Struct. Funct. Genet. 2: 330-339.
<p>Pauling L (1960) The Nature of the Chemical Bond, 3rd ed, Cornell University Press, Ithaca, ISBN 0-8014-0333-2.
<p>Stewart RF, Davidson ER, Simpson WT (1965) Coherent x-ray scattering for the hydrogen atom in the hydrogen molecule, J. Chem. Phys. 42: 3175-87.
<p>Word JM, Lovell SC, LaBean TH, et al. (1999) Visualizing and quantifying molecular goodness-of-fit: Small-probe contact dots with explicit hydrogen atoms, J. Mol. Biol. 285: 1711-33.
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
