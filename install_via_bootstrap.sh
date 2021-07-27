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
svn --quiet --non-interactive --trust-server-cert co https://svn.code.sf.net/p/geostd/code/trunk geostd
svn --quiet --non-interactive --trust-server-cert co https://github.com/rlabduke/mon_lib.git/trunk mon_lib
svn --quiet --non-interactive --trust-server-cert export https://github.com/rlabduke/reference_data.git/trunk/Top8000/Top8000_rotamer_pct_contour_grids rotarama_data
svn --quiet --non-interactive --trust-server-cert --force export https://github.com/rlabduke/reference_data.git/trunk/Top8000/Top8000_ramachandran_pct_contour_grids rotarama_data
svn --quiet --non-interactive --trust-server-cert co https://github.com/rlabduke/reference_data.git/trunk/Top8000/Top8000_cablam_pct_contour_grids cablam_data
svn --quiet --non-interactive --trust-server-cert co https://github.com/rlabduke/reference_data.git/trunk/Top8000/rama_z rama_z

#back to top
cd ../..

svn --quiet --non-interactive --trust-server-cert export https://github.com/cctbx/cctbx_project.git/trunk/libtbx/auto_build/bootstrap.py

python bootstrap.py --builder=molprobity --use-conda $nproc

source build/setpaths.sh
python modules/chem_data/cablam_data/rebuild_cablam_cache.py

echo ++++++++++ MolProbity configure.sh finished.
echo ++++++++++ Run molprobity/setup.sh to complete installation.
