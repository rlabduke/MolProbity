#!/usr/bin/env python

import sys

"""The purpose of this script is to identify PDBs with improper use of the element field.  As of May 2015, the MolProbity server bug reports are dominated by users with crashes resulting from misused/abused element fields (columns 77 and 78).  This script identifies {{{{SOMETHING RELATED TO THIS PROBLEM}}}. """

if(len(sys.argv) != 2):
    print "usage: filter_improper_element_column.py [name of pdb] .  Return value nonzero if PDB is bad."

structure = open(sys.argv[1])

for eachLine in structure:
    pass

#we're good - no errors
exit(False)
