#!/bin/bash

# Typically one would copy this file to /usr/local/sbin
# and run crontab -e to run this nightly, say at closing.

mysql --execute 'UPDATE timecards SET time_out = now() WHERE time_out IS NULL'
