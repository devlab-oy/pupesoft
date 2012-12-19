#!/bin/bash

BACKUPDIR=$1
DBKANTA=$2
DBKAYTTAJA=$3
DBSALASANA=$4
BACKUPPAIVAT=$5

# Katsotaan, onko salausavain syötetty
if [ ! -z $6 ]; then
	SALAUSAVAIN=$6
fi

if [ ! -z $7 ]; then
	EXTRABACKUP=$7
	REMOTEHOST=$8
	REMOTEUSER=$9
	REMOTEPASS=${10}
	REMOTEREMDIR=${11}
	REMOTELOCALDIR=${12}
fi

if [ ! -z ${13} ]; then
	S3BUCKET=${13}
fi

# Katsotaan, että parametrit on annettu
if [ -z ${BACKUPDIR} ] || [ -z ${DBKANTA} ] || [ -z ${DBKAYTTAJA} ] || [ -z ${DBSALASANA} ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-backup.sh backup.kansio tietokanta kantakäyttäjä kantasalasana backuplukumäärä"
	echo "Esim: pupe-backup.sh /backup/pupesoft-backup pupesoft kayttajanimi salasana 30"
	echo
	exit
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${BACKUPDIR} ]; then
	echo
	echo "ERROR! Hakemistoa ${BACKUPDIR} ei löydy!"
	echo
	exit
fi

# Oletuksena säästetään 30 backuppia
if [ -z ${BACKUPPAIVAT} ]; then
	BACKUPPAIVAT=30
fi

FILEDATE=$(date "+%Y-%m-%d")
FILENAME="${DBKANTA}-backup-${FILEDATE}.bz2"
MYSQLPOLKU=$(mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -sN -e "show variables like 'datadir'"|cut -f 2)

if [ ! -d ${MYSQLPOLKU}${DBKANTA} ]; then
	echo
	echo "ERROR! Mysql-hakemistoa ${MYSQLPOLKU}${DBKANTA} ei löydy!"
	echo
	exit
fi

mkdir /tmp/${DBKANTA}

if [ ! -d /tmp/${DBKANTA} ]; then
	echo
	echo "ERROR! Hakemistoa /tmp/${DBKANTA} ei löydy!"
	echo
	exit
fi

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Backup ${DBKANTA}."

# Siirrytään temppidirriin
cd /tmp/${DBKANTA}

# Lukitaan taulut, Flushataan binlogit, Otetaan masterin positio ylös, Kopioidaan mysql kanta ja lopuksi vapautetaan taulut.
mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -e "FLUSH TABLES WITH READ LOCK; FLUSH LOGS; SHOW MASTER STATUS; system cp -R ${MYSQLPOLKU}${DBKANTA}/ /tmp/; UNLOCK TABLES;" > /tmp/${DBKANTA}/pupesoft-backup.info

# Jos backup onnistui!
if [[ $? -eq 0 ]]; then

	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": Copy done."

	# Pakataan failit
	tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 *

	# Jos pakkaus onnistui!
	if [[ $? -eq 0 ]]; then

		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Bzip2 done."

		if [ ! -z "${SALAUSAVAIN}" ]; then

			# Laitetaan salausavain fileen
			echo "${SALAUSAVAIN}" > /root/salausavain

			# Mcrypt ei osaa ylikirjottaa tiedostoa, joten poistetaan varmuuden vuoksi tehtävä file
			rm -f "${BACKUPDIR}/${FILENAME}.nc"

			# Salataan backup käyttäen Rijndael-256 algoritmia ja poistetaan salaamaton versio jos salaus onnistuu
			checkcrypt=`mcrypt -a rijndael-256 -f /root/salausavain --unlink --quiet ${BACKUPDIR}/${FILENAME}`

			if [[ $? -ne 0 ]]; then
				echo "Salaus ${BACKUPDIR}/${FILENAME} ei onnistunut!"
				echo
			else
				# Päivitetään oikeudet kuntoon
				chmod 664 "${BACKUPDIR}/${FILENAME}.nc"
				echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
				echo ": Encrypt done."
			fi
		fi
	else
		# Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
		rm -f ${BACKUPDIR}/${FILENAME}
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Bzip2 FAILED!"
    fi
else
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": Database copy FAILED!"
fi

# Dellataan pois tempit
rm -rf /tmp/${DBKANTA}

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Copy config files."

# Backupataan Pupeasenukseen liittyvät asetuskset
PUPEPOLKU=`dirname $0|cut -d "/" -f 2-`
FILENAME="linux-backup-${FILEDATE}.bz2"
BACKUPFILET=""

# Siirrytään roottiin, koska tar ei tallenna absoluuttisia polkuja
cd /

# Pakataan tärkeät tiedostot
if ls -A etc/cron.* &> /dev/null; then
	BACKUPFILET="${BACKUPFILET} etc/cron.*"
fi

if [ -f "${PUPEPOLKU}/inc/salasanat.php" ]; then
	BACKUPFILET="${BACKUPFILET} ${PUPEPOLKU}/inc/salasanat.php"
fi

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

# Jos meillä on jotain pakattavaa, niin pakataan
if [ ! -z "${BACKUPFILET}" ]; then
	tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 ${BACKUPFILET}

	# Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
	if [[ $? -ne 0 ]]; then
		rm -f ${BACKUPDIR}/${FILENAME}
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Copy FAILED."		
	else
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Copy done."		
	fi
else
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": Nothing to copy!"			
fi

if [ ! -z "${SALAUSAVAIN}" ]; then

	# Mcrypt ei osaa ylikirjottaa tiedostoa, joten poistetaan varmuuden vuoksi tehtävä file
	rm -f "${BACKUPDIR}/${FILENAME}.nc"

	# Salataan backup käyttäen Rijndael-256 algoritmia ja poistetaan salaamaton versio jos salaus onnistuu
	checkcrypt=`mcrypt -a rijndael-256 -f /root/salausavain --unlink --quiet ${BACKUPDIR}/${FILENAME}`

	if [[ $? -ne 0 ]]; then
		echo "Salaus ${BACKUPDIR}/${FILENAME} ei onnistunut!"
		echo
	else
		# Päivitetään oikeudet kuntoon
		chmod 664 "${BACKUPDIR}/${FILENAME}.nc"
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Encrypt done."
	fi

	# Dellataan salausavain file
	rm -f /root/salausavain
fi

#Siirretäänkö tuorein backuppi myös sambaserverille jos sellainen on konffattu
if [ ! -z "${EXTRABACKUP}" -a "${EXTRABACKUP}" == "SAMBA" ]; then
	checksamba=`mount -t cifs -o username=${REMOTEUSER},password=${REMOTEPASS} //${REMOTEHOST}/${REMOTEREMDIR} ${REMOTELOCALDIR}`

	if [[ $? -ne 0 ]]; then
		echo "Sambamount ei onnistunut!"
		echo
	else
		#Poistetaan vanha backuppi
		rm -f ${REMOTELOCALDIR}/${DBKANTA}-backup-*
		rm -f ${REMOTELOCALDIR}/linux-backup-*

		# Siirretään tämä
		cp ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}* ${REMOTELOCALDIR}
		cp ${BACKUPDIR}/linux-backup-${FILEDATE}* ${REMOTELOCALDIR}

		umount ${REMOTELOCALDIR}
	fi
fi

#Pidetäänkö kaikki backupit eri serverillä
if [ ! -z "${EXTRABACKUP}" -a "${EXTRABACKUP}" == "SSH" ]; then
	# Siirretään failit remoteserverille
	scp ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}* ${REMOTEUSER}@${REMOTEHOST}:${REMOTEREMDIR}
	scp ${BACKUPDIR}/linux-backup-${FILEDATE}* ${REMOTEUSER}@${REMOTEHOST}:${REMOTEREMDIR}

	# Siivotaan vanhat backupit pois remoteserveriltä
	ssh ${REMOTEUSER}@${REMOTEHOST} "find ${REMOTEREMDIR} -type f -mtime +${BACKUPPAIVAT} -delete";

	# Pidetään master serverillä vain uusin backuppi
	BACKUPPAIVAT=1
fi

# Siivotaan vanhat backupit pois
find ${BACKUPDIR} -type f -mtime +${BACKUPPAIVAT} -delete

# Synkataan backuppi Amazon S3:een
if [ ! -z "${S3BUCKET}" ]; then
	s3cmd --no-progress --delete-removed sync ${BACKUPDIR}/ s3://${S3BUCKET}
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": S3 copy done."
fi

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Backup done."
echo