#!/bin/bash
#
# This pushes changes in public_html to DESTINATION
#
# CAUTION: It overwrites all files in destination directory.
# (It does not change the database.)
#
# CAUTION THE SECOND:
# This script uses rsync with the --del command.
# This means DESTINATION must point to the correct location.
# If one specified "/" as the destination, it would delete the root
# directory and replace EVERYTHING on the system with the few files
# for this website.

# Get the script's working directory and run from there rather
# than using the cwd, which could be anywhere.
# The trailing / after public_html is needed otherwise one
# creates a second public_html at the destination.

# If one expects to keep permissions and ownership on the files this must
# be run, CAREFULLY, with sudo.

SOURCE="$( cd "$(dirname "$0")" ; pwd -P )/public_html/"
DESTINATION="/sites/freegeek_timecard/public_html"
#DESTINATION="173.255.192.58:/sites/freegeek_timecard/public_html"

rsync -avrlpAog --exclude '*.swp' --del "${SOURCE}" "${DESTINATION}"
