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
  REMOTEPASS=${10}
  REMOTEREMDIR=${11}
  REMOTELOCALDIR=${12}
fi

# Tehdäänkö mysql-backuppi vai ei.
if [ ! -z ${13} ]; then
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

# Jos /home:n alta löytyy tmp-kansio, niin käytetään sitä
if [ -d "/home/tmp" ]; then
  TMPBACKUPDIR="/home/tmp/pupe_backup"
else
  TMPBACKUPDIR="/tmp/pupe_backup"
fi

# Jos temppikansio löytyy, niin dellataan
if [ -d ${TMPBACKUPDIR} ]; then
  rm -rf ${TMPBACKUPDIR}
fi

# Oletuksena säästetään 30 backuppia
if [ -z ${BACKUPPAIVAT} ]; then
  BACKUPPAIVAT=30
fi

# Onnistuiko ecryptaus
ENCRYPT_EXIT=0

function encrypt_file {

  if [[ -z $1 || -z $2 ]]; then
    echo "Funktio tarvitsee ekaksi parametriksi salausavaimen ja toiseksi parametriksi salattavan filen nimen"
    ENCRYPT_EXIT=1
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
    MCRYPT_EXIT=$?

    # Tässä talteen sama salaus käyttäen openssl:ää.
    #openssl aes-256-cbc -in ${F_TIEDOSTO} -out ${F_TIEDOSTO}.nc -pass file:"${F_TEMP_SALAUSAVAIN}"

    # Dellataan salausavain file
    rm -f "${F_TEMP_SALAUSAVAIN}"

    if [[ MCRYPT_EXIT -ne 0 ]]; then
      # Poistetaan salattu tiedosto
      rm -f "${F_TIEDOSTO}.nc"

      echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
      echo "Encrypt ${F_TIEDOSTO} FAILED!"
      ENCRYPT_EXIT=1
    else
      # Poistetaan salaamaton tiedosto
      rm -f "${F_TIEDOSTO}"

      # Päivitetään salatun tiedoston oikeudet kuntoon
      chmod 664 "${F_TIEDOSTO}.nc"

      echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
      echo ": Encrypt done."
      ENCRYPT_EXIT=0
    fi
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

  mkdir ${TMPBACKUPDIR}

  if [ ! -d ${TMPBACKUPDIR} ]; then
    echo
    echo "ERROR! Hakemistoa ${TMPBACKUPDIR} ei löydy!"
    echo
    exit
  fi

  # Siirrytään temppidirriin
  cd ${TMPBACKUPDIR}

  # Lukitaan taulut, Flushataan binlogit, Otetaan masterin positio ylös, Kopioidaan mysql kanta ja lopuksi vapautetaan taulut.
  mysql -u ${DBKAYTTAJA} ${DBKANTA} --password=${DBSALASANA} -e "FLUSH STATUS; FLUSH TABLES WITH READ LOCK; FLUSH LOGS; SHOW MASTER STATUS; system cp -R ${MYSQLPOLKU}${DBKANTA}/ ${TMPBACKUPDIR}/; UNLOCK TABLES;" > ${TMPBACKUPDIR}/backup-binlog.info

  # Jos backup onnistui!
  if [[ $? -eq 0 ]]; then

    echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
    echo ": Database copy done."

    # Kopsataan tämän backupin logipositio myös kantazipin sisälle
    cp -f ${TMPBACKUPDIR}/backup-binlog.info ${TMPBACKUPDIR}/${DBKANTA}/backup-binlog.info

    # Kopsataan tämän backupin logipositio aina backup dirikkaan -0000 nimellä.
    cp -f ${TMPBACKUPDIR}/backup-binlog.info ${TMPBACKUPDIR}/backup-binlog-0000.info
    mv -f ${TMPBACKUPDIR}/backup-binlog-0000.info ${BACKUPDIR}/backup-binlog-0000.info

    # Pakataan failit
    tar -cf ${TMPBACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2  -C ${TMPBACKUPDIR}/${DBKANTA} .

    # Jos pakkaus onnistui!
    if [[ $? -eq 0 ]]; then

      echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
      echo ": Database bzip2 done."

      # Salataan tiedosto
      if [ ! -z "${SALAUSAVAIN}" ]; then
        encrypt_file "${SALAUSAVAIN}" "${TMPBACKUPDIR}/${FILENAME}"

        if [[ ${ENCRYPT_EXIT} -eq 0 ]]; then
           FILENAME="${FILENAME}.nc"
        fi
      fi

      # Siirretään pakattu backuppi backupkansioon
      mv -f ${TMPBACKUPDIR}/${FILENAME} ${BACKUPDIR}/${FILENAME}
    else
      # Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
      rm -f ${TMPBACKUPDIR}/${FILENAME}
      echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
      echo ": Database bzip2 FAILED!"
      fi
  else
    echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
    echo ": Database copy FAILED!"
  fi

  # Dellataan pois tempit
  rm -rf ${TMPBACKUPDIR}
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

  mkdir ${TMPBACKUPDIR}

  tar -cf ${TMPBACKUPDIR}/${FILENAME} --use-compress-prog=pbzip2 ${BACKUPFILET}

  # Jos pakkaus epäonnistui! Poistetaan rikkinäinen tiedosto.
  if [[ $? -ne 0 ]]; then
    rm -f ${TMPBACKUPDIR}/${FILENAME}
    echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
    echo ": Config files copy FAILED."
  else
    echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
    echo ": Config files bzip2 done."

    # Salataan tiedosto
    if [ ! -z "${SALAUSAVAIN}" ]; then
      encrypt_file "${SALAUSAVAIN}" "${TMPBACKUPDIR}/${FILENAME}"

      if [[ ${ENCRYPT_EXIT} -eq 0 ]]; then
         FILENAME="${FILENAME}.nc"
      fi
    fi

    # Siirretään backupkansioon
    mv -f ${TMPBACKUPDIR}/${FILENAME} ${BACKUPDIR}/${FILENAME}
  fi

  # Dellataan pois tempit
  rm -rf ${TMPBACKUPDIR}

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
      rm -f ${REMOTELOCALDIR}/${DBKANTA}-binlog-*
    fi

    rm -f ${REMOTELOCALDIR}/linux-backup-*

    # Siirretään tämä
    if $MYSQLBACKUP; then
      cp ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}* ${REMOTELOCALDIR}
    fi

    cp ${BACKUPDIR}/linux-backup-${FILEDATE}* ${REMOTELOCALDIR}

    umount ${REMOTELOCALDIR}
  fi
fi

# Pidetäänkö kaikki backupit eri serverillä
if [ ! -z "${EXTRABACKUP}" -a "${EXTRABACKUP}" == "SSH" ]; then
  REMOTESKEY=""

  # Onko käyttäjätunnuksen mukana annettu ssh-avain?
  # Muodossa "/home/kala/.ssh/id_rsa kayttajatunnus"
  echo ${REMOTEUSER} | grep " " > /dev/null

  if [[ $? = 0 ]]; then
    KEYUSR=(${REMOTEUSER})

    REMOTESKEY="-i ${KEYUSR[0]}"
    REMOTEUSER=${KEYUSR[1]}
  fi

  # Siirretään failit remoteserverille
  if $MYSQLBACKUP; then
    scp ${REMOTESKEY} ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}* ${REMOTEUSER}@${REMOTEHOST}:${REMOTEREMDIR}
  fi

  scp ${REMOTESKEY} ${BACKUPDIR}/linux-backup-${FILEDATE}* ${REMOTEUSER}@${REMOTEHOST}:${REMOTEREMDIR}

  # Siivotaan vanhat backupit pois remoteserveriltä
  ssh ${REMOTESKEY} ${REMOTEUSER}@${REMOTEHOST} "find ${REMOTEREMDIR} -type f -mtime +${BACKUPPAIVAT} -delete";

  # Pidetään master serverillä vain uusin backuppi
  BACKUPPAIVAT=1
fi

#Siirretäänkö tuorein backuppi myös ftp-serverille jos sellainen on konffattu
if [ ! -z "${EXTRABACKUP}" -a "${EXTRABACKUP}" == "FTP" ]; then
  if $MYSQLBACKUP; then
    ncftpput -u ${REMOTEUSER} -p ${REMOTEPASS} ${REMOTEHOST} ${REMOTEREMDIR} ${BACKUPDIR}/${DBKANTA}-backup-${FILEDATE}*
  fi

  ncftpput -u ${REMOTEUSER} -p ${REMOTEPASS} ${REMOTEHOST} ${REMOTEREMDIR} ${BACKUPDIR}/linux-backup-${FILEDATE}*
fi

# Siivotaan vanhat backupit pois
find ${BACKUPDIR} -type f -follow -mtime +${BACKUPPAIVAT} -delete

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": Backup done."
echo

# Vapautetaan lukko
flock -u 9
