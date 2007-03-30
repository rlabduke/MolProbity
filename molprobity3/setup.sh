#!/bin/bash
#
# Restores some filesystem state options that are not
# preserved by Subversion.
#

# Dumb check to make sure we're in the top-level MP directory
if [ ! -f setup.sh ]; then
    # Try to change to the right directory
    cd `dirname "$0"`
    # If that doesn't work, just fail
    if [ ! -f setup.sh ]; then
        echo
        echo "    You must run SETUP.SH from the main MolProbity directory!"
        echo
        exit
    fi
fi

# Create config file
if [ ! -f config/config.php ]; then
    cp config/config.php.sample config/config.php
else
    echo "config.php already exists; no changes made"
fi

# Create ssh_grid_workers file
if [ ! -f config/ssh_grid_workers.php ]; then
    cp config/ssh_grid_workers.php.sample config/ssh_grid_workers.php
else
    echo "ssh_grid_workers.php already exists; no changes made"
fi

# Set world-writable permisions on data/ and tmp/
chmod 777 public_html/data
chmod 777 tmp/

# Set world-writable permisions on feedback/
chmod 777 feedback/
touch feedback/molprobity.log
chmod 666 feedback/molprobity.log
touch feedback/user_paths.log
chmod 666 feedback/user_paths.log

# Create symlinks for executables
cd bin
# Nothing to do here...
cd macosx/
ln -s gawk awk
cd ..
cd linux/
# Nothing to do here...
cd ..
cd ..
