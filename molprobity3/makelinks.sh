#!/bin/bash
#
# Restores some filesystem state options that are not
# preserved by Subversion.
#

# Set world-writable permisions on data/
chmod 777 public_html/data
touch public_html/data/molprobity.log
chmod 666 public_html/data/molprobity.log

# Set world-writable permisions on feedback/ and tmp/
chmod 777 feedback/
chmod 777 tmp/

# Create symlinks for executables
cd bin
# Nothing to do here...
cd macosx/
ln -s gawk-3.1.3 gawk
ln -s gawk-3.1.3 awk
cd ..
cd linux/
# Nothing to do here...
cd ..
cd ..
