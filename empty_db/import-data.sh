#!/usr/bin/env bash
mysqlimport --ignore-lines=1 --lines-terminated-by='\n' --fields-terminated-by=',' --fields-enclosed-by='"' --verbose --delete --local -uroot  timecard users.csv
mysqlimport --ignore-lines=1 --lines-terminated-by='\n' --fields-terminated-by=',' --fields-enclosed-by='"' --verbose --delete --local -uroot  timecard timecards.csv
