#!/usr/bin/env python

from __future__ import print_function
import sys

"""The purpose of this script is to identify PDBs with busted MODEL records.  As of May 2015, the MolProbity server has seen a spate of models, mostly marked as from MOLMOL, which misuse MODEL cards in such a way that Reduce goes haywire and produces indefinitely long output.  These structures are identified by A) having ATOM records that occur outside of MODEL records (in structures that are using MODEL records elsewhere), and possibly B) re-using the same MODEL number repeatedly. This script identifies the former problem. """

if(len(sys.argv) != 2):
    print("usage: filter_improper_MODEL_cards.py [name of pdb] .  This script ASSUMES the input has MODEL records - simply do not bother to run it otherwise! Return value nonzero if PDB is bad.")

structure = open(sys.argv[1])
inAMODEL = False

for eachLine in structure:
    card = eachLine[0:6]
    #print(card)
    if (card == "MODEL "):
        if (inAMODEL):
            print("ERROR: found a MODEL card while in a model record!")
            exit(True)
        else:
            inAMODEL = True
            #print("entered a MODEL")
    elif ((card == "ATOM  ") and not inAMODEL): #not sure if we care about HETATM
        print("ERROR: found an ATOM card while not in a model record!")
        exit(True)
    elif (card == "ENDMDL"):
        if (inAMODEL):
            inAMODEL = False
            #print("exited a MODEL")
        else:
            print("ERROR: found ENDMDL while not in a model record!")
            exit(True)

#we're good - no errors
exit(False)
