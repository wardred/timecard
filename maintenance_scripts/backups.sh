#!/bin/bash

# Assuming this is installed for the root user
# /root/.my.cnf needs the following lines:
# [mysqldump]
# user=TIMESHEET_USER
# password=TIMESHEET_PASSWORD

# Typically one would copy this file to /usr/local/sbin
# and run crontab -e to run this nightly, say at 1am or so.


BACKUP_DIR="/backups/"
MYSQL_BACKUP_DIR="$BACKUP_DIR/mysql_dumps"
CONFIG_FILES="/etc"
SITE_DIR="/sites/freegeek_timecard"
DATABASE="freegeek_timecard"
DUMP_FILE="$MYSQL_BACKUP_DIR/freegeek.sql.gz"

mkdir -p "${MYSQL_BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"
chmod 700 "${MYSQL_BACKUP_DIR}"
umask u=r,g-rwx,o-rwx "${BACKUP_DIR}" "${MSYQL_BACKUP_DIR}"

rsync -ra "${CONFIG_FILES}" "${BACKUP_DIR}"
rsync -ra "${SITE_DIR}" "${BACKUP_DIR}"

mysqldump --single-transaction "${DATABASE}" | gzip -c -9 > "${DUMP_FILE}"
