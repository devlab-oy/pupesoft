#!/bin/bash

# aja näin:
# bash aja_masi_rubicon_konversio.sh < /dev/null &>> /root/rubicon_masi_konversio_output.txt &

# Settaa polut OIKEIN!
PUPESOFT="/home/devlab/pupesoft"
MERCARAJAPINNAT="/home/devlab/mercantile_private/rajapinnat"

# Varmista, että salasanat.php:ssä on SAMAT arvot kun näissä muuttujissa!
DATABASENIMI="pupesoft"
DATABASEHOST="localhost"
DATABASEUSER="pupesoft"
DATABASEPASS="YwsB9yGvLW/oRwOhYoqoVZej+E49"

# Testataan polut
cd ${PUPESOFT} &&
cd ${MERCARAJAPINNAT} &&

# Testataan database connectio
mysql --user=${DATABASEUSER} \
      --password=${DATABASEPASS} \
      --host=${DATABASEHOST} ${DATABASENIMI} \
      --execute="use ${DATABASENIMI}" &&

# Poistetaan kaikki muut paitsi 'artr' ja 'atarv'
echo "`date` ** poista_yritys asifi kiilt merca mertr turva **" &&
php ${MERCARAJAPINNAT}/rubicon_poista_yritys.php ${PUPESOFT} asifi kiilt merca mertr turva &&

echo "`date` ** poistetaan turhat tuotepaikat **" &&
mysql --user=${DATABASEUSER} \
      --password=${DATABASEPASS} \
      --host=${DATABASEHOST} ${DATABASENIMI} \
      --execute="DELETE FROM tuotepaikat WHERE yhtio = 'atarv' AND saldo = 0" &&

echo "`date` ** liiketoimintakauppa **" &&
php ${MERCARAJAPINNAT}/rubicon_masi_liiketoimintakauppa.php ${PUPESOFT} &&

echo "`date` ** check-tables **" &&
php ${PUPESOFT}/check-tables.php --verbose

if [[ $? -ne 0 ]]; then
  echo "`date` ** Konversio FEILAS!! **"
else
  echo "`date` ** Valmis! **"
fi
