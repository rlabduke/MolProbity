#! /bin/bash

if [ $# -eq 1 ]
then
    sf_user=$1
fi
 
curl http://kinemage.biochem.duke.edu/molprobity/base.tar.gz -o base.tar.gz
tar zxf base.tar.gz

mkdir sources
mkdir build
cd sources

curl http://kinemage.biochem.duke.edu/molprobity/boost.tar.gz -o boost.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/scons.tar.gz -o scons.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/annlib.tar.gz -o annlib.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/annlib_adaptbx.tar.gz -o annlib_adaptbx.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/cbflib.tar.gz -o cbflib.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/ccp4io.tar.gz -o ccp4io.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/ccp4io_adaptbx.tar.gz -o ccp4io_adaptbx.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/chem_data.tar.gz -o chem_data.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/lapack_fem.tar.gz -o lapack_fem.tar.gz
curl http://kinemage.biochem.duke.edu/molprobity/tntbx.tar.gz -o tntbx.tar.gz

tar zxf boost.tar.gz
tar zxf scons.tar.gz
tar zxf annlib.tar.gz
tar zxf annlib_adaptbx.tar.gz
tar zxf cbflib.tar.gz
tar zxf ccp4io.tar.gz
tar zxf ccp4io_adaptbx.tar.gz
tar zxf chem_data.tar.gz
tar zxf lapack_fem.tar.gz
tar zxf tntbx.tar.gz

if [ -n "$sf_user" ]
then
    svn --non-interactive --trust-server-cert co https://$sf_user@svn.code.sf.net/p/cctbx/code/trunk cctbx_project
else
    svn --non-interactive --trust-server-cert co https://svn.code.sf.net/p/cctbx/code/trunk cctbx_project
fi

#svn --non-interactive --trust-server-cert co https://quiddity.biochem.duke.edu/svn/reduce/trunk reduce
#svn --non-interactive --trust-server-cert co https://quiddity.biochem.duke.edu/svn/probe/trunk probe
#svn --non-interactive --trust-server-cert co https://quiddity.biochem.duke.edu/svn/suitename

svn --non-interactive --trust-server-cert co https://github.com/rlabduke/probe.git/trunk probe
svn --non-interactive --trust-server-cert co https://github.com/rlabduke/reduce.git/trunk reduce
svn --non-interactive --trust-server-cert co https://github.com/rlabduke/suitename.git/trunk suitename

cd ../build

python ../sources/cctbx_project/libtbx/configure.py mmtbx

make

source ../build/setpaths.sh

#this configures all the rotamer and ramachandran contour files so rotalyze and ramalyze work.  They are downloaded as hiant text files, this line of code creates them as python pickles
mmtbx.rebuild_rotarama_cache

# git doesn't store empty directories so we're adding the the reqired empty directory here.
cd ..
mkdir -p public_html/data
mkdir -p public_html/data/tmp
mkdir -p feedback
mkdir -p tmp
