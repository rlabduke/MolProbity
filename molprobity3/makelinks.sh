#!/bin/bash
#
# Restores some filesystem state options that are not
# preserved by Subversion.
#

# Set world-writable permisions on data/
chmod 777 public_html/data
touch public_html/data/molprobity.log
chmod 666 public_html/data/molprobity.log

# Set world-writable permisions on tmp/
chmod 777 tmp/

# Create symlinks for executables
cd bin
ln -s pdbcns.010504.pl pdbcns
cd macosx/
ln -s dang.1.8.020529 dang
ln -s gawk-3.1.3 gawk
ln -s gawk-3.1.3 awk
ln -s prekin.6.36.040609.macOSX prekin
ln -s probe.2.9.030123.macosx probe
ln -s reduce.2.21.mod040509dcr.macOSX reduce
cd ..
cd linux/
ln -s prekin.6.36.040609.linux.i386.RH90.static prekin
ln -s reduce.2.21.mod040509dcr.linux.RH9 reduce
cd ..
cd ..
