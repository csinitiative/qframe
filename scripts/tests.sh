#!/bin/sh

# Save the original path
ORIGINAL_PATH=`pwd`

# Change directory into the path where the script is located,
# then back down to the project root
cd `echo $0 | sed 's|tests\.sh$||'`
cd ..

# Set the REGQ_ROOT environment variable before kicking off
# our tests...then kick em off
export REGQ_ROOT=`pwd`
php $REGQ_ROOT/test/runall.php