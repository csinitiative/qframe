#!/bin/sh

# Save the original path
ORIGINAL_PATH=`pwd`

# Change directory into the path where the script is located,
# then back down to the project root
cd `echo $0 | sed 's|tests\.sh$||'`
cd ..

# Set the QFRAME_ROOT environment variable before kicking off
# our tests...then kick em off
export QFRAME_ROOT=`pwd`
php $QFRAME_ROOT/test/runall.php