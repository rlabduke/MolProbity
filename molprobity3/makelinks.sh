#!/bin/bash
#
# Restores some filesystem state options that are not
# preserved by Subversion.
#

# Set world-writable permisions on data/
chmod 777 public_html/data

# Create symlinks for executables
cd bin
ln -s pdbcns.010504.pl pdbcns
cd macosx/
ln -s dang.1.8.020529 dang
ln -s gawk-3.1.3 gawk
ln -s gawk-3.1.3 awk
ln -s prekin.6.25.030309.macOSX prekin
ln -s probe.2.9.030123.macosx probe
ln -s reduce.2.21.030604 reduce
cd ..
cd linux-rh73/
ln -s prekin.6.33.031124.linux.i386.RH90.static prekin
cd ..
cd ..
