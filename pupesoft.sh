#!/bin/bash

exec 9>/tmp/##pupesoft.sh-flock.lock

if ! flock -n 9  ; then
	echo "Backup on jo menossa!";
	exit 1
else
	chmod 666 /tmp/##pupesoft.sh-flock.lock
fi

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

# Komennot
/bin/bash ${POLKU}pupe-backup.sh "$BACKUPDIR" "$BACKUPDB" "$BACKUPUSER" "$BACKUPPASS" "$BACKUPSAVEDAYS" "$SALAUSAVAIN" "$EXTRABACKUP" "$REMOTEHOST" "$REMOTEUSER" "$REMOTEPASS" "$REMOTEREMDIR" "$REMOTELOCALDIR" "$S3BUCKET"
/bin/bash ${POLKU}pupe-cron.sh "$YHTIO"
/bin/bash ${POLKU}pupe-cron-server.sh "$BACKUPSAVEDAYS"

# Jos haluat seurata hitaita kyselyitä
#SLOWLOG="/var/lib/mysql/mysqld-slow.log"
#sh ${POLKU}mysql-slow-log.sh "$SLOWLOG" "$BACKUPUSER" "$BACKUPPASS"