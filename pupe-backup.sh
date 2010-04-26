#!/bin/bash

BACKUPDIR=$1
DBKANTA=$2
DBKAYTTAJA=$3
DBSALASANA=$4
BACKUPPAIVAT=$5

# Katsotaan, että parametrit on annettu
if [ -z $BACKUPDIR ] || [ -z $DBKANTA ] || [ -z $DBKAYTTAJA ] || [ -z $DBSALASANA ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-backup.sh backup.kansio tietokanta kantakäyttäjä kantasalasana backuplukumäärä"
	echo "Esim: pupe-backup.sh /backup/pupesoft-backup pupesoft kayttajanimi salasana 30"
	echo
	exit
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d $BACKUPDIR ]; then
	echo
	echo "ERROR! Hakemistoa $BACKUPDIR ei löydy!"
	echo
	exit
fi

# Oletuksena säästetään 30 backuppia
if [ -z $BACKUPPAIVAT ]; then
	BACKUPPAIVAT=30
fi

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Backup $DBKANTA."

FILEDATE=`date "+%Y-%m-%d"`
FILENAME="${DBKANTA}-backup-${FILEDATE}.bz2"

# Backupataan kaikki failit
mysqlhotcopy -q -u $DBKAYTTAJA --password=$DBSALASANA $DBKANTA /tmp

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Copy done."

# Siirrytään temppidirriin
cd /tmp/$DBKANTA

# Pakataan failit
tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 *

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Bzip2 done."

# Dellataan pois tempit
rm -rf /tmp/$DBKANTA

# Siivotaan vanhat backupit pois
find $BACKUPDIR -mtime +$BACKUPPAIVAT -delete

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - All done."
echo