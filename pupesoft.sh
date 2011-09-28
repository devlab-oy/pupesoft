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

# Komennot
sh ${POLKU}pupe-cron.sh $YHTIO
sh ${POLKU}pupe-cron-server.sh $BACKUPSAVEDAYS
sh ${POLKU}pupe-backup.sh $BACKUPDIR $BACKUPDB $BACKUPUSER $BACKUPPASS $BACKUPSAVEDAYS

# Jos haluat seurata hitaita kyselyitä
#SLOWLOG="/var/lib/mysql/mysqld-slow.log"
#sh ${POLKU}mysql-slow-log.sh $SLOWLOG $BACKUPUSER $BACKUPPASS