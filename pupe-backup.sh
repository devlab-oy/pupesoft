#!/bin/bash

BACKUPDIR=$1
DBKANTA=$2
DBKAYTTAJA=$3
DBSALASANA=$4
BACKUPPAIVAT=$5

# Katsotaan, ett‰ parametrit on annettu
if [ -z ${BACKUPDIR} ] || [ -z ${DBKANTA} ] || [ -z ${DBKAYTTAJA} ] || [ -z ${DBSALASANA} ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-backup.sh backup.kansio tietokanta kantak‰ytt‰j‰ kantasalasana backuplukum‰‰r‰"
	echo "Esim: pupe-backup.sh /backup/pupesoft-backup pupesoft kayttajanimi salasana 30"
	echo
	exit
fi

# Katsotaan, ett‰ hakemisto lˆytyy
if [ ! -d ${BACKUPDIR} ]; then
	echo
	echo "ERROR! Hakemistoa ${BACKUPDIR} ei lˆydy!"
	echo
	exit
fi

# Oletuksena s‰‰stet‰‰n 30 backuppia
if [ -z ${BACKUPPAIVAT} ]; then
	BACKUPPAIVAT=30
fi

FILEDATE=$(date "+%Y-%m-%d")
FILENAME="${DBKANTA}-backup-${FILEDATE}.bz2"
MYSQLPOLKU=$(mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -sN -e "show variables like 'datadir'"|cut -f 2)

if [ ! -d ${MYSQLPOLKU}${DBKANTA} ]; then
	echo
	echo "ERROR! Mysql-hakemistoa ${MYSQLPOLKU}${DBKANTA} ei lˆydy!"
	echo
	exit
fi

mkdir /tmp/${DBKANTA}

if [ ! -d /tmp/${DBKANTA} ]; then
	echo
	echo "ERROR! Hakemistoa /tmp/${DBKANTA} ei lˆydy!"
	echo
	exit
fi

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Backup ${DBKANTA}."

# Siirryt‰‰n temppidirriin
cd /tmp/${DBKANTA}

# Lukitaan taulut, Flushataan binlogit, Otetaan masterin positio ylˆs, Kopioidaan mysql kanta ja lopuksi vapautetaan taulut.
mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -e "FLUSH TABLES WITH READ LOCK; FLUSH LOGS; SHOW MASTER STATUS; system cp -R ${MYSQLPOLKU}${DBKANTA}/ /tmp/; UNLOCK TABLES;" > /tmp/${DBKANTA}/pupesoft-backup.info

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Copy done."

# Pakataan failit
tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 *

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Bzip2 done."

# Dellataan pois tempit
rm -rf /tmp/${DBKANTA}

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Copy config files."

# Backupataan Pupeasenukseen liittyv‰t asetuskset
PUPEPOLKU=`dirname $0|cut -d "/" -f 2-`
FILENAME="linux-backup-${FILEDATE}.bz2"

# Siirryt‰‰n roottiin, koska tar ei tallenna absoluuttisia polkuja
cd /

# Pakataan t‰rke‰t tiedostot
tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 etc/ssh/sshd_config etc/httpd/conf/* etc/cups/printers.conf etc/cups/lpoptions etc/my.cnf root/.forward etc/hosts etc/sysconfig/network etc/mail/* etc/crontab ${PUPEPOLKU}/inc/salasanat.php etc/cron.*

# Siivotaan vanhat backupit pois
find ${BACKUPDIR} -mtime +${BACKUPPAIVAT} -delete

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - All done."
echo