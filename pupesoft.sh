#!/bin/bash

# Pupesoft asennuksen tiedot
POLKU="/var/www/html/pupesoft/"
YHTIO="joni"

# Pupesoft varmistuksen tiedot
BACKUPDIR="/backup/pupe-backup"
BACKUPDB="pupesoft"
BACKUPUSER="pupe"
BACKUPPASS="pupe1"
BACKUPSAVEDAYS="30"

# Salausavain varmistuksen salaamiseen
SALAUSAVAIN=""

# Lisavarmistuksen tiedot
EXTRABACKUP=""
REMOTEHOST=""
REMOTEUSER=""
REMOTEPASS=""
REMOTEREMDIR=""
REMOTELOCALDIR=""

# Amazon S3 bucket
S3BUCKET=""

# Backupataan kanta
NOMYSQLBACKUP=""

# Mysql slow queries logfile
SLOWLOG=""

# Komennot
/bin/bash ${POLKU}pupe-backup.sh "$BACKUPDIR" "$BACKUPDB" "$BACKUPUSER" "$BACKUPPASS" "$BACKUPSAVEDAYS" "$SALAUSAVAIN" "$EXTRABACKUP" "$REMOTEHOST" "$REMOTEUSER" "$REMOTEPASS" "$REMOTEREMDIR" "$REMOTELOCALDIR" "$S3BUCKET" "$NOMYSQLBACKUP"
/bin/bash ${POLKU}pupe-cron.sh "$YHTIO"
/bin/bash ${POLKU}pupe-cron-server.sh "$BACKUPSAVEDAYS"
/bin/bash ${POLKU}mysql-slow-log.sh "$SLOWLOG" "$BACKUPUSER" "$BACKUPPASS"
