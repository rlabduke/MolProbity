#!/usr/bin/env bash

#This script runs a test suite for MolProbity's dependencies across a small number of PDBs.
#It's meant to excercise most of the major functions.

#The test suite:

# 1A2P.pdb   Protein, no flips
# 4NPD.pdb   Protein, has many alternates
# 1UBQ.pdb   Protein, small, OK, ubiquitous
# 2V8O.pdb   Protein, exercises omegalyze
# 3KAT.pdb   Protein, small, low-res
# 4HUM.pdb   Protein, small, many errors, exercises CaBLAM

# 1VC7.pdb   RNA with many errors
# 4PRF.pdb   (same) RNA with very few errors
# 1EHZ.pdb   RNA, moderate number of known errors

#ISSUES: no NMR structure to test nuclear hydrogen bond lengths, no CDL structure


(#parens scopes variables


home=$(pwd)

rm -r $home/new
mkdir $home/new

pdbs="1A2P
1EHZ" #1UBQ.pdb 1VC7.pdb 2V8O.pdb 3KAT.pdb 4HUM.pdb 4NPD.pdb 4PRF.pdb

#may need to unloop for simple_molprobity's flags, and function-ize some of the loop
for each in $pdbs
do
    echo "Processing $each"
    cd $home/new
    $home/simple_molprobity.sh $home/pdbtestfiles/$each.pdb &> log.$each # redirect version that captures all output
    mv log.$each $home/new/test_$each
done


#maybe should re-loop to do work-then-compare-later?
#if ref directory exists:
#diff --brief $home/ref/test_$each $home/new/test_$each

#else:
#mv new ref


) #Trailing parenthesis to limit scope of script vars
