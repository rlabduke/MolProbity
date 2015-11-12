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


mp_test=$(pwd)

if [ -d $mp_test/new ]; then
    rm -r $mp_test/new
fi

mkdir $mp_test/new

#don't ask me why we are using this bizarre syntax for this list...
pdbs="1A2P
1EHZ
1UBQ
1VC7
2V8O
3KAT
4HUM
4NPD
4PRF"

#may need to unloop for simple_molprobity's flags, and function-ize some of the loop
for each in $pdbs
do
    echo "Processing $each"
    cd $mp_test/new
    $mp_test/simple_molprobity.sh $mp_test/pdbtestfiles/$each.pdb &> log.$each # redirect version that captures all output
    mv log.$each $mp_test/new/test_$each
done

if [ ! -d $mp_test/ref ]; then
  mv $mp_test/new $mp_test/ref
else
    for each in $(ls $mp_test/ref/)
    do
	echo $each
	if [ -d $mp_test/new/$each ]; then
	    diff --brief $mp_test/ref/$each $mp_test/new/$each
	else
	    echo "ERROR: directory $mp_test/ref/$each exists but not $mp_test/new/$each"
	fi
    done
fi

echo "If there are differences, examine manually?"

) #Trailing parenthesis to limit scope of script vars
