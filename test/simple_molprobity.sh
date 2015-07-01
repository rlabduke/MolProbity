#!/usr/bin/env bash

#$tempdir = 

#get file from cmdline
$pdbfilepath = $1 #replaces upload or fetch, takes first argument only

#prepare and clean PDB file: lib/model.php preparePDB()
#scrublines
tr -d '\015' <$pdbfilepath > $tempdir/temp.pdb
#strip USER MODs
awk '\$0 !~ /^USER  MOD (Set|Single|Fix|Limit)/' $tempdir/temp.pdb > $tmp2
#next step calls pdbstat(), determines file statistics like nuclear hydrogens, isBig, etc.
#we may have to set some of these by commandline options
#pdbcns runs here if pdbstat says there are cns atoms



#reduce


