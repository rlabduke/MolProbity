#!/usr/bin/env bash
(
#parens around the script should keep the phenix sourcing local to this script
#prevents this script from permanently affecting the terminal from which it runs
helptext="
This is a testing script for MolProbity.
It is broadly intended to produce text file outputs to be diff'd against
  reference data, or to be used to get timing data on Molprobity routines.

This script lacks some of the file parsing and preprocessing handled by the
  full MolProbity code. As a result, some options must be set by the user with
  commandline flags.

This script accepts a single PDB file with the .pdb extension.

This script creates a new directory named test_FILENAME in the working directory
  and outputs files to that directory. Old files of the same name will be
  overwritten.

Options
  -nuclear     sets hydrogen bond-lengths to use nuclear positions as in NMR
  -h -help     prints this help

"
hydrogen_position='' #ecloud by default
pdbfilepath=''

for i in $@
do
  case $i in
    -h|-help)
      printf "$helptext"
      return 1
      ;;
    -nuclear)
      hydrogen_position=' -nuclear'
      ;;
  esac
  if [[ $i == *".pdb" ]]
  then
    pdbfilepath=$i
  fi
done

if [[ $pdbfilepath == '' ]]
then
  printf "$helptext"
  echo "No .pdb file provided."
  return 1
elif [[ ! -e "$pdbfilepath" ]];
then
  printf "$helptext"
  echo "Could not find specified PDB file."
  return 1
fi

echo "sourcing MolProbity Phenix environment"
testscript_dir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#the above is taken from http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in/246128#246128
mptop_dir="$testscript_dir/.."
source "$mptop_dir/build/setpaths.sh"
#source the MP version of the phenix environment

#some programs like prekin are stored in different locations depending on the OS
#Find the OS and store the appropriate location
if [[ "$OSTYPE" == "linux-gnu" ]]; then
  osbin="bin/linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
  # Mac OSX
  osbin="bin/macosx"
elif [[ "$OSTYPE" == "cygwin" ]]; then
  # POSIX compatibility layer and Linux environment emulation for Windows
  osbin="bin/linux" #probably
elif [[ "$OSTYPE" == "msys" ]]; then
  # Lightweight shell and GNU utilities compiled for Windows (part of MinGW)
  echo "WARNING: You seem to be running this script under Windows, some features may not work"
  osbin="bin/linux"
elif [[ "$OSTYPE" == "win32" ]]; then
  echo "WARNING: You seem to be running this script under Windows, some features may not work"
elif [[ "$OSTYPE" == "freebsd"* ]]; then
  echo "WARNING: Your OS may not be supported"
else
  echo "WARNING: Your OS may not be supported"
fi

pdbcode=$(basename -s .pdb "$pdbfilepath")
tempdir="test_$pdbcode"
#if dir by this name does not exist, make it
if [ ! -d "$tempdir" ]; then
  mkdir "$tempdir"
fi

#This script shouldn't have to do remediation
#code preserved to help set up remediation elsewhere if needed
####prepare and clean PDB file: lib/model.php preparePDB()
####scrublines
###tr -d '\015' <$pdbfilepath > $tempdir/temp.pdb
####strip USER MODs
###awk '\$0 !~ /^USER  MOD (Set|Single|Fix|Limit)/' $tempdir/temp.pdb > $tmp2
####next step calls pdbstat(), determines file statistics like nuclear hydrogens, isBig, etc.
####we may have to set some of these by commandline options
####pdbcns runs here if pdbstat says there are cns atoms

#How do we get the correct phenix environment variables via MolProbity?
trimmedfile="$pdbcode.trim.pdb"
#This is using reduce to strip H's
echo "running reduce -trim"
phenix.reduce -quiet -trim -allalt "$pdbfilepath" | awk '$0 !~ /^USER  MOD/' > "$tempdir/$trimmedfile"
# this reduce commandline from lib/model.php in reduceNoBuild()

#This makes the thumbnail kinemage
$mptop_dir/$osbin/prekin -cass -colornc "$tempdir/$trimmedfile" > "$tempdir/thumbnail.kin"

#reduce proper
#-build should = -flip
reducedfile="$pdbcode.FH.pdb"
echo "running reduce -build (add Hs and do flips)"
phenix.reduce -quiet -build"$hydrogen_position" "$tempdir/$trimmedfile" > "$tempdir/$reducedfile"
# this from lib/model.php in reduceBuild()

#determine if reduce did any flips, run nqh_minimize if so:
anyflips=$(grep "USER  MOD" "$tempdir/$reducedfile" | grep FLIP)
if [[ ${#anyflips} -ne 0 ]];
then
  #run nqh_minimize
  minimizedfile="$pdbcode.FHreg.pdb"
  nqhtempdir="$tempdir/nqhtemp"
  if [ ! -d "$nqhtempdir" ]; then
    mkdir "$nqhtempdir"
  fi
  #mmtbx.nqh_minimize requires 3 arguments, the third is just a tempdir for it to work in
  #arguments are: inpath, outpath, temppath
  echo "running nqh_minimize"
  mmtbx.nqh_minimize "$tempdir/$reducedfile" "$tempdir/$minimizedfile" "$nqhtempdir"
  #this commandline from lib/model.php in regularizeNQH()
else
  #nqh_minimize breaks on files without flips
  #set filename for next steps and pass
  minimizedfile="$reducedfile"
fi

#flipkin calls, should we wish to add them later:
#$flip_params is $inpath (or '-s $inpath' if using segIDs)
###exec("flipkin $flip_params > $outpathAsnGln");
###exec("flipkin -h $flip_params > $outpathHis");

#Next server step is letting the user choose flips
#This reduce call should just make all flips
##Reduce Done##

##Start aacgeom.php functionality##
#runAnalysis() in lib/analyze.php actually handles most of this
echo "running ramalyze"
phenix.ramalyze $tempdir/$minimizedfile > $tempdir/$pdbcode.rama
#this from runRamachandran() in lib/analyze.php
#not running loadRamachandran because not making multichart
echo "making ramachandran kin"
java -Xmx512m -cp $mptop_dir/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -kinplot $tempdir/$minimizedfile > $tempdir/$pdbcode.rama.kin
#this from makeRamachandranKin($infile, $outfile) in lib/visualize.php
echo "making ramachandran pdf"
java -Xmx512m -cp $mptop_dir/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -pdf $tempdir/$minimizedfile $tempdir/$pdbcode.rama.pdf
#this from function makeRamachandranPDF($infile, $outfile) in lib/visualize.php

echo "running rotalyze"
phenix.rotalyze data_version=8000 $tempdir/$minimizedfile > $tempdir/$pdbcode.rota
#this from runRotamer($infile, $outfile) in lib/analyze.php

echo "running CBdev"
phenix.cbetadev $tempdir/$minimizedfile > $tempdir/$pdbcode.cbdev
#this from runCbetaDev($infile, $outfile) in lib/analyze.php
echo "making CBdev kinemage"
$mptop_dir/$osbin/prekin -cbdevdump $tempdir/$minimizedfile | java -cp $mptop_dir/lib/hless.jar hless.CBScatter > $tempdir/$pdbcode.cbdev.kin
#this from makeCbetaDevPlot($infile, $outfile) in lib/visualize.php

echo "running omegalyze"
phenix.omegalyze nontrans_only=False $tempdir/$minimizedfile > $tempdir/$pdbcode.omega
#this from runOmegalyze($infile, $outfile) in lib/analyze.php

echo "running CaBLAM"
phenix.cablam_validate output=text $tempdir/$minimizedfile > $tempdir/$pdbcode.cablam
#this from runCablam($infile, $outfile) in lib/analyze.php

echo "running pucker analysis"
$mptop_dir/$osbin/prekin -pperptoline -pperpdump $tempdir/$minimizedfile > $tempdir/$pdbcode.pucker
#this from runBasePhosPerp($infile, $outfile) in lib/analyze.php

echo "running suitename"
mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -report -pointIDfields 7 -altIDfield 6 > $tempdir/$pdbcode.suitename
#this from runSuitenameReport($infile, $outfile) in lib/analyze.php
echo "running suitestring"
mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -string -oneline -pointIDfields 7 -altIDfield 6 | fold -w 60 > $tempdir/$pdbcode.suitestring
#this from runSuitenameString($infile, $outfile) in lib/analyze.php
echo "making suitename kin"
mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -kinemage -pointIDfields 7 -altIDfield 6 > $tempdir/$pdbcode.suitename.kin
#this from makeSuitenameKin($infile, $outfile) in lib/visualize.php
)
