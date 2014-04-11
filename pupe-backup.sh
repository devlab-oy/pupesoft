#!/bin/bash

#Otetaan flock-locki
exec 9>/tmp/##pupesoft.sh-flock.lock

if ! flock -n 9  ; then
	echo "Backup on jo menossa!";
	exit 1
else
	touch /tmp/##pupesoft.sh-flock.lock
	chmod 666 /tmp/##pupesoft.sh-flock.lock
fi

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
	REMOTEPASS=substr($, 10, 1)
	REMOTEREMDIR=substr($, 11, 1)
	REMOTELOCALDIR=substr($, 12, 1)
fi

if [ ! -z substr($, 13, 1) ]; then
	S3BUCKET=substr($, 13, 1)
fi

# Tehdäänkö mysql-backuppi vai ei.
if [ ! -z substr($, 14, 1) ]; then
	MYSQLBACKUP=false;
else
	MYSQLBACKUP=true;
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

function encrypt_file {

	if [[ -z $1 || -z $2 ]]; then
		echo "Funktio tarvitsee ekaksi parametriksi salausavaimen ja toiseksi parametriksi salattavan filen nimen"
	else
		# Otetaan parametrit muuttujiin
		F_SALAUSAVAIN=$1
		F_TIEDOSTO=$2
		F_TEMP_SALAUSAVAIN="/tmp/salausavain"

		# Laitetaan salausavain fileen
		rm -f "${F_TEMP_SALAUSAVAIN}"
		echo "${F_SALAUSAVAIN}" > "${F_TEMP_SALAUSAVAIN}"

		# Salaus ei osaa ylikirjottaa output-tiedostoa, joten poistetaan se aluksi varmuuden vuoksi
		rm -f "${F_TIEDOSTO}.nc"

		# Salataan tiedosto käyttäen Rijndael-256 algoritmia
		mcrypt -a rijndael-256 -f ${F_TEMP_SALAUSAVAIN} --quiet ${F_TIEDOSTO}

		# Tässä talteen sama salaus käyttäen openssl:ää.
		#openssl aes-256-cbc -in ${F_TIEDOSTO} -out ${F_TIEDOSTO}.nc -pass file:"${F_TEMP_SALAUSAVAIN}"

		if [[ $? -ne 0 ]]; then
			# Poistetaan salattu tiedosto
			rm -f "${F_TIEDOSTO}.nc"

			echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
			echo "Encrypt ${F_TIEDOSTO} FAILED!"
		else
			# Poistetaan salaamaton tiedosto
			rm -f "${F_TIEDOSTO}"

			# Päivitetään salatun tiedoston oikeudet kuntoon
			chmod 664 "${F_TIEDOSTO}.nc"

			echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
			echo ": Encrypt done."
		fi

		# Dellataan salausavain file
		rm -f "${F_TEMP_SALAUSAVAIN}"
	fi
}

FILEDATE=$(date "+%Y-%m-%d_%H%M%S")

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Backup started."

if $MYSQLBACKUP; then

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

	# Siirrytään temppidirriin
	cd /tmp/${DBKANTA}

	# Lukitaan taulut, Flushataan binlogit, Otetaan masterin positio ylös, Kopioidaan mysql kanta ja lopuksi vapautetaan taulut.
	mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -e "FLUSH STATUS; FLUSH TABLES WITH READ LOCK; FLUSH LOGS; SHOW MASTER STATUS; system cp -R ${MYSQLPOLKU}${DBKANTA}/ /tmp/; UNLOCK TABLES;" > /tmp/${DBKANTA}/backup-binlog.info

	# Jos backup onnistui!
	if [[ $? -eq 0 ]]; then

		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Database copy done."

		# Pakataan failit
		tar -cf ${BACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 *

		# Jos pakkaus onnistui!
		if [[ $? -eq 0 ]]; then

			echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
			echo ": Database bzip2 done."

			# Salataan tiedosto
			if [ ! -z "${SALAUSAVAIN}" ]; then
				encrypt_file "${SALAUSAVAIN}" "${BACKUPDIR}/${FILENAME}"
			fi

			# Tämän backupin binlog-info
			binlog_new_log=$(< /tmp/${DBKANTA}/backup-binlog.info)

			# Tuorein binlog-info mikä meillä on tallella
			tmp_filename=`ls ${BACKUPDIR}/backup-binlog* | sort -r | head -1`
			binlog_last_log=$(< ${tmp_filename})

			# Tehtävän binlog backupin nimi
			binlog_backup="${DBKANTA}-binlog-${FILEDATE}.sql.bz2"

			# Regex, jolla löydetään filenimi ja filepositio
			binlog_regex="(mysql-bin\.[0-9]+).([0-9]+)"

			# Kaivetaan tämän backupin binlog ja logipositio info-filestä
			if [[ ${binlog_new_log} =~ ${binlog_regex} ]]; then
				binlog_new_file=${BASH_REMATCH[1]}
				binlog_new_position=${BASH_REMATCH[2]}
			fi

			# Kaivetaan edellisen backupin binlog ja logipositio info-filestä
			if [[ ${binlog_last_log} =~ ${binlog_regex} ]]; then
				binlog_last_file=${BASH_REMATCH[1]}
				binlog_last_position=${BASH_REMATCH[2]}
			fi

			# Jos löydettiin kaikki muuttujat
			if [[ ! -z ${binlog_new_file} && ! -z ${binlog_new_position} && ! -z ${binlog_last_file} && ! -z ${binlog_last_position} ]]; then

				# Siirrytään MySQL hakemistoon
				cd ${MYSQLPOLKU}

				# Katsotaan kaikki binlog filet edellisen ja tämän backupin välistä
				binlog_perl="print if (/^${binlog_last_file}\b/ .. /^${binlog_new_file}\b/)"
				binlog_all=`ls mysql-bin.* | sort | perl -ne "${binlog_perl}" | perl -ne 'chomp and print "$_ "'`

				# Jos löydettiin binlogifilet
				if [[ ! -z ${binlog_all} ]]; then

					# Tehdään binlogeista SQL-lausekkeita ja pakataan ne zippiin
					mysqlbinlog --start-position=${binlog_last_position} --stop-position=${binlog_new_position} ${binlog_all} | pbzip2 > ${BACKUPDIR}/${binlog_backup}

					# Jos pakkaus onnistui!
					if [[ $? -eq 0 ]]; then
						# Kopsataan tämän backupin logipositio paikalleen, että tiedetään ottaa tästä eteenpäin seuraavalla kerralla
						cp -f /tmp/${DBKANTA}/backup-binlog.info ${BACKUPDIR}/backup-binlog-${FILEDATE}.info

						echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
						echo ": Binlog bzip2 done."

						# Salataan tiedosto
						if [ ! -z "${SALAUSAVAIN}" ]; then
							encrypt_file "${SALAUSAVAIN}" "${BACKUPDIR}/${binlog_backup}"
						fi
					else
						# Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
						rm -f ${BACKUPDIR}/${binlog_backup}
						echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
						echo ": Binlog bzip2 FAILED!"
					fi
				else
					echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
					echo ": No binlogs found!"
				fi
			else
				echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
				echo ": Binlog info not found!"
			fi

			# Kopsataan tämän backupin logipositio aina backup dirikkaan samalle nimellä. Ylempänä kopsataan "oikea versio" päivämäärän kanssa.
			# Tämä siksi, että helpottaa ekalla kerralla kun backup ajetaan ja debuggia ongelmatilanteissa.
			cp -f /tmp/${DBKANTA}/backup-binlog.info ${BACKUPDIR}/backup-binlog-0000.info
		else
			# Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
			rm -f ${BACKUPDIR}/${FILENAME}
			echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
			echo ": Database bzip2 FAILED!"
	    fi
	else
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Database copy FAILED!"
	fi

	# Dellataan pois tempit
	rm -rf /tmp/${DBKANTA}
else
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": Databasea ei backupata!"
fi

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

if test -f "${PUPEPOLKU}/inc/salasanat.php" -a -r "${PUPEPOLKU}/inc/salasanat.php"; then
	BACKUPFILET="${BACKUPFILET} ${PUPEPOLKU}/inc/salasanat.php"
fi

if test -f "etc/ssh/sshd_config" -a -r "etc/ssh/sshd_config"; then
	BACKUPFILET="${BACKUPFILET} etc/ssh/sshd_config"
fi

if test -f "etc/rc.local" -a -r "etc/rc.local"; then
	BACKUPFILET="${BACKUPFILET} etc/rc.local"
fi

if test -f "etc/dhcp/dhcpd.conf" -a -r "etc/dhcp/dhcpd.conf"; then
	BACKUPFILET="${BACKUPFILET} etc/dhcp/dhcpd.conf"
fi

if test -f "etc/vtund.conf" -a -r "etc/vtund.conf"; then
	BACKUPFILET="${BACKUPFILET} etc/vtund.conf"
fi

if test -f "etc/samba/smb.conf" -a -r "etc/samba/smb.conf"; then
	BACKUPFILET="${BACKUPFILET} etc/samba/smb.conf"
fi

if test -f "etc/cups/" -a -r "etc/cups/"; then
	BACKUPFILET="${BACKUPFILET} etc/cups/*"
fi

if test -f "etc/cups/lpoptions" -a -r "etc/cups/lpoptions"; then
	BACKUPFILET="${BACKUPFILET} etc/cups/lpoptions"
fi

if test -f "etc/cups/printers.conf" -a -r "etc/cups/printers.conf"; then
	BACKUPFILET="${BACKUPFILET} etc/cups/printers.conf"
fi

if test -f "etc/my.cnf" -a -r "etc/my.cnf"; then
	BACKUPFILET="${BACKUPFILET} etc/my.cnf"
fi

if test -f "root/.forward" -a -r "root/.forward"; then
	BACKUPFILET="${BACKUPFILET} root/.forward"
fi

if test -f "etc/hosts" -a -r "etc/hosts"; then
	BACKUPFILET="${BACKUPFILET} etc/hosts"
fi

if test -f "etc/sysconfig/network" -a -r "etc/sysconfig/network"; then
	BACKUPFILET="${BACKUPFILET} etc/sysconfig/network"
fi

if test -f "etc/crontab" -a -r "etc/crontab"; then
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
		echo ": Config files copy FAILED."
	else
		echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
		echo ": Config files bzip2 done."

		# Salataan tiedosto
		if [ ! -z "${SALAUSAVAIN}" ]; then
			encrypt_file "${SALAUSAVAIN}" "${BACKUPDIR}/${FILENAME}"
		fi
	fi
else
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": Config files not found!"
fi

#Siirretäänkö tuorein backuppi myös sambaserverille jos sellainen on konffattu
if [ ! -z "${EXTRABACKUP}" -a "${EXTRABACKUP}" == "SAMBA" ]; then
	checksamba=`mount -t cifs -o username=${REMOTEUSER},password=${REMOTEPASS} //${REMOTEHOST}/${REMOTEREMDIR} ${REMOTELOCALDIR}`

	if [[ $? -ne 0 ]]; then
		echo "Sambamount ei onnistunut!"
		echo
	else
		#Poistetaan vanha backuppi
		if $MYSQLBACKUP; then
			rm -f ${REMOTELOCALDIR}/${DBKANTA}-backup-*
		fi

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
	if $MYSQLBACKUP; then
		scp ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}* ${REMOTEUSER}@${REMOTEHOST}:${REMOTEREMDIR}
	fi

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

	# Katsotaan mistä löytyy config file
	if [ -f "/home/devlab/secret/s3cfg" ]; then
		S3CONFIG="/home/devlab/secret/s3cfg"
	else
		S3CONFIG="/root/.s3cfg"
	fi

	s3cmd --config=${S3CONFIG} --no-progress --delete-removed sync ${BACKUPDIR}/ s3://${S3BUCKET}
	echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
	echo ": S3 copy done."
fi

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Backup done."
echo

# Vapautetaan lukko
flock -u 9
