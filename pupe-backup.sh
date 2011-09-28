#!/bin/bash

BACKUPDIR=$1
DBKANTA=$2
DBKAYTTAJA=$3
DBSALASANA=$4
BACKUPPAIVAT=$5

# Katsotaan, onko salausavain sy�tetty
if [ ! -n $6 ]; then
	SALAUSAVAIN=$6
fi

# Katsotaan, ett� parametrit on annettu
if [ -z ${BACKUPDIR} ] || [ -z ${DBKANTA} ] || [ -z ${DBKAYTTAJA} ] || [ -z ${DBSALASANA} ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-backup.sh backup.kansio tietokanta kantak�ytt�j� kantasalasana backuplukum��r�"
	echo "Esim: pupe-backup.sh /backup/pupesoft-backup pupesoft kayttajanimi salasana 30"
	echo
	exit
fi

# Katsotaan, ett� hakemisto l�ytyy
if [ ! -d ${BACKUPDIR} ]; then
	echo
	echo "ERROR! Hakemistoa ${BACKUPDIR} ei l�ydy!"
	echo
	exit
fi

# Oletuksena s��stet��n 30 backuppia
if [ -z ${BACKUPPAIVAT} ]; then
	BACKUPPAIVAT=30
fi

FILEDATE=$(date "+%Y-%m-%d")
FILENAME="${DBKANTA}-backup-${FILEDATE}.bz2"
MYSQLPOLKU=$(mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -sN -e "show variables like 'datadir'"|cut -f 2)

if [ ! -d ${MYSQLPOLKU}${DBKANTA} ]; then
	echo
	echo "ERROR! Mysql-hakemistoa ${MYSQLPOLKU}${DBKANTA} ei l�ydy!"
	echo
	exit
fi

mkdir /tmp/${DBKANTA}

if [ ! -d /tmp/${DBKANTA} ]; then
	echo
	echo "ERROR! Hakemistoa /tmp/${DBKANTA} ei l�ydy!"
	echo
	exit
fi

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Backup ${DBKANTA}."

# Siirryt��n temppidirriin
cd /tmp/${DBKANTA}

# Lukitaan taulut, Flushataan binlogit, Otetaan masterin positio yl�s, Kopioidaan mysql kanta ja lopuksi vapautetaan taulut.
mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -e "FLUSH TABLES WITH READ LOCK; FLUSH LOGS; SHOW MASTER STATUS; system cp -R ${MYSQLPOLKU}${DBKANTA}/ /tmp/; UNLOCK TABLES;" > /tmp/${DBKANTA}/pupesoft-backup.info

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Copy done."

# Pakataan failit
tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 *

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Bzip2 done."

if [ ! -n ${SALAUSAVAIN} ]; then
	checkcrypt=`mcrypt --unlink --key ${SALAUSAVAIN} --quiet ${BACKUPDIR}/${FILENAME}`

	if [[ $? != 0 ]]; then
		echo "Salaus ${BACKUPDIR}/${FILENAME} ei onnistunut!"
		echo
	fi
fi

# Dellataan pois tempit
rm -rf /tmp/${DBKANTA}

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - Copy config files."

# Backupataan Pupeasenukseen liittyv�t asetuskset
PUPEPOLKU=`dirname $0|cut -d "/" -f 2-`
FILENAME="linux-backup-${FILEDATE}.bz2"
BACKUPFILET=""

# Siirryt��n roottiin, koska tar ei tallenna absoluuttisia polkuja
cd /

# Pakataan t�rke�t tiedostot
if [ -f "etc/ssh/sshd_config" ]; then
	BACKUPFILET="${BACKUPFILET} etc/ssh/sshd_config"
fi

if [ -f "etc/rc.local" ]; then
	BACKUPFILET="${BACKUPFILET} etc/rc.local"
fi

if [ -f "etc/dhcp/dhcpd.conf" ]; then
	BACKUPFILET="${BACKUPFILET} etc/dhcp/dhcpd.conf"
fi

if [ -f "etc/vtund.conf" ]; then
	BACKUPFILET="${BACKUPFILET} etc/vtund.conf"
fi

if [ -f "etc/samba/smb.conf" ]; then
	BACKUPFILET="${BACKUPFILET} etc/samba/smb.conf"
fi

if [ -f "etc/cups/" ]; then
	BACKUPFILET="${BACKUPFILET} etc/cups/*"
fi

if [ -f "etc/cups/lpoptions" ]; then
	BACKUPFILET="${BACKUPFILET} etc/cups/lpoptions"
fi

if [ -f "etc/my.cnf" ]; then
	BACKUPFILET="${BACKUPFILET} etc/my.cnf"
fi

if [ -f "root/.forward" ]; then
	BACKUPFILET="${BACKUPFILET} root/.forward"
fi

if [ -f "etc/hosts" ]; then
	BACKUPFILET="${BACKUPFILET} etc/hosts"
fi

if [ -f "etc/sysconfig/network" ]; then
	BACKUPFILET="${BACKUPFILET} etc/sysconfig/network"
fi

if [ -f "etc/crontab" ]; then
	BACKUPFILET="${BACKUPFILET} etc/crontab"
fi

if [ -d etc/mail/ ]; then
	BACKUPFILET="${BACKUPFILET} etc/mail/*"
fi

if [ -d etc/httpd/conf/ ]; then
	BACKUPFILET="${BACKUPFILET} etc/httpd/conf/*"
fi

tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2  ${PUPEPOLKU}/inc/salasanat.php etc/cron.* ${BACKUPFILET}

# Siivotaan vanhat backupit pois
find ${BACKUPDIR} -mtime +${BACKUPPAIVAT} -delete

echo -n `date "+%Y-%m-%d %H:%M:%S"`
echo " - All done."
echo