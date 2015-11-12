This test directory contains a script that emulates very simple MolProbity sessions.  This allows it to:

1. Run a nearly-complete MolProbity session from command line, without bothering with the "server setup" steps and Apache or PHP-CLI
2. Generate timing data for the many steps inside a MolProbity run
3. Be used as a test for MolProbity's dependencies

# For testing

## Purpose
The script is useful to exercise MolProbity's major dependencies in a non-interactive environment.  In other words, you can just run the script and walk away, instead of babysitting a session in your browser, to test code changes to the underlying dependencies.

For testing, the script simply duplicates the command line calls that MolProbity's PHP uses.  If those calls change, they need to be updated in the test script as well.  

The PHP layer itself is not tested AT ALL.  As a corollary, outputs of the PHP layer (like the multicriterion chart) are not available via the testing interface.

##Use
simple_molprobity.sh MY.pdb will run the test script on your PDB, dumping results into a directory named after that PDB.  You can then compare that directory's contents across different MolProbity versions / dependency versions to look for differences.  Notice you should NOT compare results across machines - they vary with processor.

test_script_wrapper.sh will run simple_molprobity.sh on a suite of provided PDBs, manage the results, and present the names of different files, as appropriate.
