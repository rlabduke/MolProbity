#!/usr/bin/env bash

helptext='This is help text'
hydrogen_position='-ecloud'
pdbfilepath=''

for i in $@
do
  case $i in
    -h|-help)
      echo $helptext
      return 1
      ;;
    -nuclear)
      hydrogen_position='-nuclear'
      ;;
  esac
  if [[ $i == *".pdb" ]]
  then
    pdbfilepath=$i
  fi
done

if [[ $pdbfilepath == '' ]]
then
  echo "No PDB file provided"
  echo $helptext
  return 1
fi

echo $hydrogen_position
echo $pdbfilepath

testscript_dir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#the above is taken from http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in/246128#246128
mptop_dir="$testscript_dir/.."
source "$mptop_dir/build/setpaths.sh"
#source the MP version of the phenix environment

return 0

#$tempdir = 

#get file from cmdline
$pdbfilepath = $1 #replaces upload or fetch, takes first argument only

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
#This is using reduce to strip H's
phenix.reduce -quiet -trim -allalt $pdbfilepath | '\$0 !~ /^USER  MOD/' > $tmp1
#This makes the thumbnail kinemage
prekin -cass -colornc $tmp1 > thumbnail.kin

#reduce proper
#-build should = -flip
phenix.reduce -quiet -build $tmp1 > $tmp2
#mmtbx.nqh_minimize requires 3 arguments, the third is just a tempdir for it to work in
#arguments are: inpath, outpath, temppath
mmtbx.nqh_minimize $tmp2 $tmp3 $tempplace
#flipkin calls, should we wish to add them later:
#$flip_params is $inpath (or '-s $inpath' if using segIDs)
###exec("flipkin $flip_params > $outpathAsnGln");
###exec("flipkin -h $flip_params > $outpathHis");

#Next server step is letting the user choose flips
#This reduce call should just make all flips
##Reduce Done##


