#!/bin/bash

if [ $# -eq 1 ]
then
    nproc="--nproc=$1"
else
    nproc=""
fi

# checking Python version
PYV=`python -c 'import sys; print(hex(sys.hexversion))'`
if (( "$PYV" <= "0x2070000" ))
then
  if type "python2.7" > /dev/null
  then
    shopt -s expand_aliases
    alias python="python2.7"
  else
    echo you are using python version:
    python --version
    echo "this MolProbity configure script requires Python 2.7 or newer";
    exit
  fi
fi

echo ++++++++++ creating build directories ...
if [ ! -d modules ]; then mkdir modules; fi
#if [ ! -d build ]; then mkdir build; fi
cd modules
if [ ! -d chem_data ]; then mkdir chem_data; fi

cd chem_data
git clone --depth 1 https://github.com/phenix-project/geostd.git
git clone --depth 1 https://github.com/rlabduke/mon_lib.git
git clone --depth 1 https://github.com/rlabduke/rotarama_data.git
git clone --depth 1 https://github.com/rlabduke/cablam_data.git

if [ ! -d rama_z ]; then mkdir rama_z; fi
wget -O rama_z/top8000_rama_z_dict.pkl https://github.com/rlabduke/reference_data/raw/master/Top8000/rama_z/top8000_rama_z_dict.pkl

#back to top
cd ../..

wget -O bootstrap.py https://github.com/cctbx/cctbx_project/raw/master/libtbx/auto_build/bootstrap.py

python bootstrap.py --builder=molprobity --use-conda $nproc

echo ++++++++++ MolProbity configure.sh finished.
echo ++++++++++ Run molprobity/setup.sh to complete installation.
