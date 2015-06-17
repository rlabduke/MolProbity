This document contains a general description of the most useful cmdline
tools included in the MolProbity download package. 

Molprobity comes with a cmdline directory that contains a number of PHP 
scripts that various people have written.  Feel free to open up the 
scripts in an editor and take a look at them; at the top of most of 
them they have instructions on what sorts of arguments they take.  
Unfortunately, I don't think there's anyway to install just the cmdline 
tools; you need the whole MolProbity package to use them.  However, if 
there's specific aspects of our tools you need you could download the 
various individual programs from our website and just call them directly. 

These scripts allow users to run most of the different MolProbity
analyses with a command-line interface.  Many of the scripts can be run
in batch mode on a directory or set of files. To run the scripts, I think 
all you need is the cli PHP that you probably already installed.

The MOST IMPORTANT thing to remember before running most MolProbity analyses
is that your structure must have hydrogens.  Without hydrogens, the all-atom
contact analysis done by MolProbity will be incorrect.  Since most crystal 
structures do not include hydrogens, we have included two scripts for running our
hydrogen-adding program, Reduce. There are two reduce scripts included:
a reduce-build script, and a reduce-nobuild.  These two scripts take as input
two directory names, and will read all the PDB files in the first directory, and
output new hydrogen-containing PDB files in the second directory.
In addition to adding hydrogens, the reduce-build script automatically analyzes 
hydrogen bonding patterns to correct backwards Asn/Gln/His sidechains flips,
while reduce-nobuild does not flip any sidechains.

To run either of these two scripts, just do the following command.

[molprobity3dir]/cmdline/reduce-[build or nobuild] [pdbdirectory]
[output_pdbdirectory]

The other two most useful scripts are the online-analysis script,
and the residue-analysis script.  The oneline-analysis script generates
a colon-delimited output, where each line of the output corresponds to 
a pdb model, and contains most of the validation information from 
MolProbity (clashes, rama, rota, geometry, etc).  The residue-analysis 
script outputs a residue by residue validation analysis 
of a PDB.  So for each residue it will tell you if it has clashes, 
if it is a rama or rotamer outlier, etc.

To run the oneline-analysis, all you have to do is run the following command:

[molprobity3dir]/cmdline/oneline-analysis [pdbdirectory] > statsfile.txt

This command will run most of the validation tools in molprobity and
pipe the output to the statsfile.

The residue-analysis script takes PDB files directly, with the following
command:

[molprobity3dir]/cmdline/residue-analysis [pdb_file] [pdb_file2] ... > statsfile.txt

You can run this command with a wildcard rather than entering each PDB file
individually.  Be warned that running this command on a large number of files
seems to have memory issues and can be prone to crashing.

Please submit a bug report if you have any trouble or questions about running
the cmdline tools.
