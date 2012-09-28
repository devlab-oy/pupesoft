#!/bin/bash

BACKUPDIR=$1
DBKANTA=$2
DBKAYTTAJA=$3
DBSALASANA=$4
BACKUPPAIVAT=$5

# Katsotaan, etta parametrit on annettu
if [ -z $BACKUPDIR ] || [ -z $DBKANTA ] || [ -z $DBKAYTTAJA ] || [ -z $DBSALASANA ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-dump.sh backup.kansio tietokanta kantakayttaja kantasalasana backuplukumaara"
	echo "Esim: pupe-dump.sh /backup/pupesoft-backup pupesoft kayttajanimi salasana 30"
	echo
	exit
fi

# Katsotaan, etta hakemisto loytyy
if [ ! -d $BACKUPDIR ]; then
	echo
	echo "ERROR! Hakemistoa $BACKUPDIR ei loydy!"
	echo
	exit
fi

# Oletuksena saastetaan 30 backuppia
if [ -z $BACKUPPAIVAT ]; then
	BACKUPPAIVAT=30
fi

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Backup $DBKANTA."

FILEDATE=`date "+%Y-%m-%d"`
FILENAME="${DBKANTA}-backup-${FILEDATE}.sql.bz2"

# tehdaan mysqldump ja pakataan se
mysqldump --lock-all-tables --flush-logs --master-data -u ${DBKAYTTAJA} --password=${DBSALASANA} ${DBKANTA} | pbzip2 > ${BACKUPDIR}/${FILENAME}

# siivotaan yli 30pv vanhat pois
find ${BACKUPDIR} -type f -mtime +${BACKUPPAIVAT} -delete

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Backup done."
