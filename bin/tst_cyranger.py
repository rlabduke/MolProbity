#!/usr/bin/python
from cyranger import cyrange_results

cyrange_txt_404d = """
********************************************
              CYRANGE (v. 2.0)
 
    Copyright (c) 2002-13 Peter Guntert.
********************************************
*** FATAL ERROR: No amino acid residues in sequence.
"""

cyrange_txt_1q2n = """
********************************************
              CYRANGE (v. 2.0)
 
    Copyright (c) 2002-13 Peter Guntert.
********************************************
    PDB coordinate file "1q2n.pdb" read, 10 rigid conformers.
 
    Optimal range 7..36, 40..56: RMSD 0.29 A, 1 gap, 47 residues.
    """

cyrange_txt_5mpg = """
********************************************
              CYRANGE (v. 2.0)
 
    Copyright (c) 2002-13 Peter Guntert.
********************************************
    PDB coordinate file "5mpg.pdb" read, 20 rigid conformers.
 
    Optimal range 11..46, 55..90: RMSD 0.25 A, 1 gap, 72 residues.
    """

cyrange_txt_5x29 = """
********************************************
              CYRANGE (v. 2.0)
 
    Copyright (c) 2002-13 Peter Guntert.
********************************************
    PDB coordinate file "5x29.pdb" read, 16 rigid conformers.
 
    Optimal range A8..A65, B8..B65, C8..C65, D8..D65, E8..E65: RMSD 2.02 A, 4 gaps, 290 residues.
    
"""

def tst_cyrange_results():
  pdb404d_results = cyrange_results(cyrange_txt_404d)
  assert not pdb404d_results.is_core("", 1)
  assert pdb404d_results.is_empty()

  pdb1q2n_results = cyrange_results(cyrange_txt_1q2n)
  assert not pdb1q2n_results.is_empty()
  assert pdb1q2n_results.is_core("", 7)
  assert pdb1q2n_results.is_core("A", 7)
  assert pdb1q2n_results.is_core("B", 7) #oddly true since the cyrange results don't include chain
  assert not pdb1q2n_results.is_core("", 6)
  assert pdb1q2n_results.is_core("A", 36)

  pdb5mpg_results = cyrange_results(cyrange_txt_5mpg)
  assert pdb5mpg_results.is_core("", 11)
  assert pdb5mpg_results.is_core("", 26)
  assert pdb5mpg_results.is_core("", 46)
  assert pdb5mpg_results.is_core("", 55)
  assert pdb5mpg_results.is_core("", 85)
  assert pdb5mpg_results.is_core("", 90)
  assert not pdb5mpg_results.is_core("", 10)
  assert not pdb5mpg_results.is_core("", 47)
  assert not pdb5mpg_results.is_core("", 499)

  
  pdb5x29_results = cyrange_results(cyrange_txt_5x29)
  assert pdb5x29_results.is_core("A", 8)

  print("all tests passed")    
    
if __name__ == '__main__' :
  tst_cyrange_results()
  