#!/usr/bin/env python

import sys

"""The purpose of this script is to identify PDBs with improper use of the element field.  As of May 2015, the MolProbity server bug reports are dominated by users with crashes resulting from misused/abused element fields (columns 77 and 78).  This script identifies {{{{SOMETHING RELATED TO THIS PROBLEM}}}. """


#Example stolen shamelessly from internet (http://deposit.rcsb.org/adit/docs/pdb_atom_format.html#ATOM)
'''
Example: 

         1         2         3         4         5         6         7         8
12345678901234567890123456789012345678901234567890123456789012345678901234567890
ATOM    145  N   VAL A  25      32.433  16.336  57.540  1.00 11.92      A1   N  
ATOM    146  CA  VAL A  25      31.132  16.439  58.160  1.00 11.85      A1   C  
ATOM    147  C   VAL A  25      30.447  15.105  58.363  1.00 12.34      A1   C  
ATOM    148  O   VAL A  25      29.520  15.059  59.174  1.00 15.65      A1   O  
ATOM    149  CB AVAL A  25      30.385  17.437  57.230  0.28 13.88      A1   C  
ATOM    150  CB BVAL A  25      30.166  17.399  57.373  0.72 15.41      A1   C  
ATOM    151  CG1AVAL A  25      28.870  17.401  57.336  0.28 12.64      A1   C  
ATOM    152  CG1BVAL A  25      30.805  18.788  57.449  0.72 15.11      A1   C  
ATOM    153  CG2AVAL A  25      30.835  18.826  57.661  0.28 13.58      A1   C  
ATOM    154  CG2BVAL A  25      29.909  16.996  55.922  0.72 13.25      A1   C  
'''

if(len(sys.argv) != 2):
    print "usage: filter_improper_element_column.py [name of pdb] .  Return value nonzero if PDB is bad."

structure = open(sys.argv[1])

for eachLine in structure:
    pass

#we're good - no errors
exit(False)
