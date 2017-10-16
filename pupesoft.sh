#!/bin/bash

# Pupesoft asennuksen tiedot
POLKU="/var/www/html/pupesoft/"

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

# Backupataan kanta
NOMYSQLBACKUP=""

# MySQL slow queries logfile
SLOWLOG=""

# Jos MySQL on toisella palvelimella tai pitää antaa custom MySQL portti
DBHOST=""

# Komennot
/bin/bash ${POLKU}pupe-backup.sh "$BACKUPDIR" "$BACKUPDB" "$BACKUPUSER" "$BACKUPPASS" "$BACKUPSAVEDAYS" "$SALAUSAVAIN" "$EXTRABACKUP" "$REMOTEHOST" "$REMOTEUSER" "$REMOTEPASS" "$REMOTEREMDIR" "$REMOTELOCALDIR" "$NOMYSQLBACKUP"
/bin/bash ${POLKU}pupe-cron.sh "$BACKUPDB" "$BACKUPUSER" "$BACKUPPASS" "$DBHOST"
/bin/bash ${POLKU}pupe-cron-server.sh "$BACKUPSAVEDAYS"
/bin/bash ${POLKU}mysql-slow-log.sh "$SLOWLOG" "$BACKUPUSER" "$BACKUPPASS"
