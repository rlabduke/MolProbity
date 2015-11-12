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
  -nuclear   sets hydrogen bond-lengths to use nuclear positions as in NMR
  -cdl       sets bond geometry validation to use Conformation Dependent Library
  -h -help   prints this help

"
hydrogen_position='ecloud' #ecloud by default
usecdl='False'
pdbfilepath=''

for i in $@
do
  case $i in
    -h|-help)
      printf "$helptext"
      return 1
      ;;
    -nuclear)
      hydrogen_position='nuclear'
      ;;
    -cdl)
      usecdl='True'
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
  exit 1
elif [[ ! -e "$pdbfilepath" ]];
then
  printf "$helptext"
  echo "Could not find specified PDB file."
  exit 1
fi

echo "sourcing MolProbity Phenix environment"
testscript_dir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#the above is taken from http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in/246128#246128
mptop_dir="$testscript_dir/.."
source "$mptop_dir/build/setpaths.sh"
#source the MP version of the phenix environment

#some programs like prekin are stored in different locations depending on the OS
#Find the OS and store the appropriate location
#if [[ "$OSTYPE" == "linux-gnu" ]]; then
if [[ "$OSTYPE" == "linux"* ]]; then #should catch more linux types
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

echo "###########################################"
echo "######MolProbity Step 1: Upload############"
echo "###########################################"
echo

#How do we get the correct phenix environment variables via MolProbity?
trimmedfile="$pdbcode.trim.pdb"
#This is using reduce to strip H's
echo "running reduce -trim"
time phenix.reduce -quiet -trim -allalt "$pdbfilepath" | awk '$0 !~ /^USER  MOD/' > "$tempdir/$trimmedfile"
# this reduce commandline from lib/model.php in reduceNoBuild()
echo -e "^time for reduce -trim\n\n"

echo "making a thumbnail kinemage"
#This makes the thumbnail kinemage
time $mptop_dir/$osbin/prekin -cass -colornc "$tempdir/$trimmedfile" > "$tempdir/thumbnail.kin"
echo -e "^time for making a thumbnail kinemage\n\n"

echo "###########################################"
echo "######MolProbity Step 2: Reduce+flips######"
echo "###########################################"
echo

#reduce proper
#-build should = -flip
reducedfile="$pdbcode.FH.pdb"
echo "running reduce -build (add Hs and do flips)"
if [[ $hydrogen_position == "nuclear" ]]; then
  time phenix.reduce -quiet -build -nuclear "$tempdir/$trimmedfile" > "$tempdir/$reducedfile"
else
  time phenix.reduce -quiet -build "$tempdir/$trimmedfile" > "$tempdir/$reducedfile"
fi
# this from lib/model.php in reduceBuild()
echo -e "^time for reduce -build\n\n"


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
  time mmtbx.nqh_minimize "$tempdir/$reducedfile" "$tempdir/$minimizedfile" "$nqhtempdir"
  #this commandline from lib/model.php in regularizeNQH()
  echo -e "^time for nqh_minimize\n\n"
else
  #nqh_minimize breaks on files without flips
  #set filename for next steps and pass
  echo -e "No flips, skipping nqh_minimize\n\n"
  minimizedfile="$reducedfile"
fi

#flipkin calls, should we wish to add them later:
#$flip_params is $inpath (or '-s $inpath' if using segIDs)
###exec("flipkin $flip_params > $outpathAsnGln");
###exec("flipkin -h $flip_params > $outpathHis");

#Next server step is letting the user choose flips
#This reduce call should just make all flips
##Reduce Done##

echo "###########################################"
echo "######MolProbity Step 3: Geometry##########"
echo "###########################################"
echo

##Start aacgeom.php functionality##
#runAnalysis() in lib/analyze.php actually handles most of this
echo "running ramalyze"
time phenix.ramalyze $tempdir/$minimizedfile > $tempdir/$pdbcode.rama
#this from runRamachandran() in lib/analyze.php
#not running loadRamachandran because not making multichart
echo -e "^time for ramalyze\n\n"

echo "making ramachandran kin"
time java -Xmx512m -cp $mptop_dir/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -kinplot $tempdir/$minimizedfile > $tempdir/$pdbcode.rama.kin
#this from makeRamachandranKin($infile, $outfile) in lib/visualize.php
echo -e "^time to make ramachandran kinemage\n\n"

echo "making ramachandran pdf"
time java -Xmx512m -cp $mptop_dir/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -pdf $tempdir/$minimizedfile $tempdir/$pdbcode.rama.pdf
#this from function makeRamachandranPDF($infile, $outfile) in lib/visualize.php
echo -e "^time to make ramachandran pdf\n\n"


echo "running rotalyze"
time phenix.rotalyze data_version=8000 $tempdir/$minimizedfile > $tempdir/$pdbcode.rota
#this from runRotamer($infile, $outfile) in lib/analyze.php
echo -e "^time to run rotalyze\n\n"


echo "running CBdev"
time phenix.cbetadev $tempdir/$minimizedfile > $tempdir/$pdbcode.cbdev
#this from runCbetaDev($infile, $outfile) in lib/analyze.php
echo -e "^time for CBdev C-beta deviation\n\n"

echo "making CBdev kinemage"
time $mptop_dir/$osbin/prekin -cbdevdump $tempdir/$minimizedfile | java -cp $mptop_dir/lib/hless.jar hless.CBScatter > $tempdir/$pdbcode.cbdev.kin
#this from makeCbetaDevPlot($infile, $outfile) in lib/visualize.php
echo -e "^time for CBdev C-beta deviation kinemage\n\n"


echo "running omegalyze"
time phenix.omegalyze nontrans_only=False $tempdir/$minimizedfile > $tempdir/$pdbcode.omega
#this from runOmegalyze($infile, $outfile) in lib/analyze.php
echo -e "^time to run omegalyze\n\n"


echo "running CaBLAM"
time phenix.cablam_validate output=text $tempdir/$minimizedfile > $tempdir/$pdbcode.cablam
#this from runCablam($infile, $outfile) in lib/analyze.php
echo -e "^time to run CaBLAM\n\n"


echo "running prekin pucker analysis"
time $mptop_dir/$osbin/prekin -pperptoline -pperpdump $tempdir/$minimizedfile > $tempdir/$pdbcode.pucker
#this from runBasePhosPerp($infile, $outfile) in lib/analyze.php
echo -e "^time for prekin pucker analysis\n\n"


echo "running suitename prep"
#This step is not authentic to MolProbity.
#Running mmtbx.mp_geo rna_backbone=True once here allows us to speed up the test
#  and allows us to capture the otherwise invisible intermediate output from mp_geo
time mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile > $tempdir/$pdbcode.suitename_midpoint
echo -e "^time for suitename midpoint\n\n"
echo "running suitename"
time phenix.suitename -report -pointIDfields 7 -altIDfield 6 < $tempdir/$pdbcode.suitename_midpoint > $tempdir/$pdbcode.suitename
#Original: mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -report -pointIDfields 7 -altIDfield 6 > $tempdir/$pdbcode.suitename
#this from runSuitenameReport($infile, $outfile) in lib/analyze.php
echo -e "^time for suitename\n\n"

echo "running suitestring"
time phenix.suitename -string -oneline -pointIDfields 7 -altIDfield 6 < $tempdir/$pdbcode.suitename_midpoint | fold -w 60 > $tempdir/$pdbcode.suitestring
#Original: mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -string -oneline -pointIDfields 7 -altIDfield 6 | fold -w 60 > $tempdir/$pdbcode.suitestring
#this from runSuitenameString($infile, $outfile) in lib/analyze.php
echo -e "^time for suitestring (alternate invocation of suitename)\n\n"

echo "making suitename kin"
time phenix.suitename -kinemage -pointIDfields 7 -altIDfield 6 < $tempdir/$pdbcode.suitename_midpoint > $tempdir/$pdbcode.suitename.kin
#Original: mmtbx.mp_geo rna_backbone=True pdb=$tempdir/$minimizedfile | phenix.suitename -kinemage -pointIDfields 7 -altIDfield 6 > $tempdir/$pdbcode.suitename.kin
#this from makeSuitenameKin($infile, $outfile) in lib/visualize.php
echo -e "^time to make suitename kin\n\n"


echo "running mp_geo bond geometry"
time mmtbx.mp_geo pdb=$tempdir/$minimizedfile out_file=$tempdir/$pdbcode.geom cdl=$usecdl outliers_only=False bonds_and_angles=True
#this from runValidationReport($infile, $outfile, $use_cdl) in lib/analyze.php
echo -e "^time to run mp_geo for bond geometry\n\n"
sort $tempdir/$pdbcode.geom > $tempdir/$pdbcode.geom.sorted
#by default, mp_geo produces unsorted output, this should render print order consistent


echo "running clashscore"
if [[ $hydrogen_position == 'nuclear' ]]; then
  echo "...in nuclear mode"
  time phenix.clashscore b_factor_cutoff=40 clash_cutoff=-0.4 nuclear=True $tempdir/$minimizedfile > $tempdir/$pdbcode.clash
else
  echo "...in ecloud mode"
  time phenix.clashscore b_factor_cutoff=40 clash_cutoff=-0.4 $tempdir/$minimizedfile > $tempdir/$pdbcode.clash
fi
#this from runClashscore($infile, $outfile, $blength="ecloud", $clash_cutoff=-0.4) in lib/analyze.php
echo -e "^time for clashscore\n\n"


#This should cover all of the datafile generation done by MolProbity
#This also covers the "extra" outputs MolProbity makes available, such as the rama pdf and the CBdev kin

#This does not, however, cover the "major" MolProbity outputs of the multicrit chart and multicrit kin
#The existing cmdline/multikin script generates the multicrit kin
#The existing cmdline/multichart script generates the multicrit chart in html format
#These additional scripts are in php, which amy not be suitable for all testing purposes
) #Trailing parenthesis to limit scope of script, see top of file
