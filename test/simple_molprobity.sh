#!/usr/bin/env bash

helptext='This is help text'
hydrogen_position='' #ecloud by default
pdbfilepath=''

for i in $@
do
  case $i in
    -h|-help)
      echo $helptext
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
  echo "No .pdb file provided"
  echo $helptext
  return 1
elif [[ ! -e "$pdbfilepath" ]];
then
  echo "Could not find specified PDB file"
  echo $helptext
  return 1
fi

testscript_dir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#the above is taken from http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in/246128#246128
mptop_dir="$testscript_dir/.."
source "$mptop_dir/build/setpaths.sh"
echo "MP phenix env sourced for this terminal"
#source the MP version of the phenix environment

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
phenix.reduce -quiet -trim -allalt "$pdbfilepath" | awk '$0 !~ /^USER  MOD/' > "$tempdir/$trimmedfile"
# this reduce commandline from lib/model.php in reduceNoBuild()

#This makes the thumbnail kinemage
#prekin needs to be set in env
#prekin -cass -colornc "$tempdir/$trimmedfile" > "$tempdir/thumbnail.kin"

#reduce proper
#-build should = -flip
reducedfile="$pdbcode.FH.pdb"
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
