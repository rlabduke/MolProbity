#!/usr/bin/env bash

#this script runs a regression test suite for MolProbity's dependencies
(#parens scopes variables


$home = pwd

mkdir $home/new

#pseudocode
for each in (1UBQ, ???, ???): #may need to unloop for simple_molprobity's flags, and function-ize some of the loop
cd $home/new
simple_molprobity.sh > log.$each # redirect version that captures all output
mv log.$each $home/new/test_$each

#maybe should re-loop to do work-then-compare-later?
if ref directory exists:
diff --brief $home/ref/test_$each $home/new/test_$each

else:
mv new ref

) #Trailing parenthesis to limit scope of script vars
