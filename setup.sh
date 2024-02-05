#!/bin/bash
#
# Restores some filesystem state options that are not
# preserved by Subversion.
#

#get arcitecture
MACHINE_TYPE=`uname -m`

# Dumb check to make sure we're in the top-level MP directory
if [ ! -f setup.sh ]; then
    # Try to change to the right directory
    cd `dirname "$0"`
    # If that doesn't work, just fail
    if [ ! -f setup.sh ]; then
        echo
        echo "    You must run SETUP.SH from the main MolProbity directory!"
        echo
        exit 1
    fi
fi

# Create config file
if [ ! -f config/config.php ]; then
    cp config/config.php.sample config/config.php
else
    echo "config.php already exists; no changes made"
fi

# Create custom php.ini file
if [ -f config/php.ini ]; then
    cp config/php.ini /etc/php.ini
    chown root:root /etc/php.ini
    chmod 644 /etc/php.ini
fi

# Create ssh_grid_workers file
if [ ! -f config/ssh_grid_workers.php ]; then
    cp config/ssh_grid_workers.php.sample config/ssh_grid_workers.php
else
    echo "ssh_grid_workers.php already exists; no changes made"
fi

# Set permisions on data/ and tmp/
echo ++++++++++ creating directories ...
mkdir -p public_html/data
mkdir -p public_html/data/tmp
mkdir -p feedback
mkdir -p tmp
echo ++++++++++ setting permisions ...
chown apache:apache public_html/data
chmod 770 public_html/data
chown apache:apache public_html/data/tmp
chmod 770 public_html/data/tmp
chmod 755 tmp/

# Set permisions on feedback/
chown apache:apache feedback/
chmod 770 feedback/
touch feedback/molprobity.log
chown apache:apache feedback/molprobity.log
chmod 664 feedback/molprobity.log
touch feedback/user_paths.log
chown apache:apache feedback/user_paths.log
chmod 664 feedback/user_paths.log

# Create symlinks for executables
cd bin
# Nothing to do here...
cd macosx/
[ -f awk ] || ln -s gawk awk
cd ..
cd linux/
# create proper symlinks according to architecture
if [ ${MACHINE_TYPE} == 'x86_64' ]; then
  ln -s prekin_64 prekin
  ln -s scrublines_64 scrublines
else
  ln -s prekin_32 prekin
  ln -s scrublines_32 scrublines
fi
cd ..
cd ..

# Try to find PHP
if ! type -p php > /dev/null; then
    echo
    echo "    Can't find PHP.  You must have PHP on your PATH to run MolProbity."
    echo
    exit 1
fi
php -C -f public_html/admin/check_config.php > tmp/check_config.html

# Run setup check and show results
if type -p open > /dev/null; then
    open tmp/check_config.html
elif type -p firefox > /dev/null; then
    firefox tmp/check_config.html
elif type -p mozilla > /dev/null; then
    mozilla tmp/check_config.html
else
    echo "Please open ./tmp/check_config.html in a web browser."
fi
