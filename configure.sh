#! /bin/bash

if [ $# -eq 1 ]
then
    sf_user=$1
fi

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

svn --non-interactive --trust-server-cert co https://quiddity.biochem.duke.edu/svn/reduce/trunk reduce
svn --non-interactive --trust-server-cert co https://quiddity.biochem.duke.edu/svn/probe/trunk probe

cd ../build

python ../sources/cctbx_project/libtbx/configure.py mmtbx

make

source ../build/setpaths.sh

mmtbx.rebuild_rotarama_cache
