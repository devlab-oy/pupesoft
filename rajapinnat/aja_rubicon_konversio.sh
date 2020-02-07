#!/bin/bash

# aja näin:
# bash aja_rubicon_konversio.sh < /dev/null &>> /root/rubicon_konversio_output.txt &

# Settaa polut OIKEIN!
PUPESOFT="/home/devlab/pupesoft"
MERCARAJAPINNAT="/home/devlab/mercantile_private/rajapinnat"

# Varmista, että salasanat.php:ssä on SAMAT arvot kun näissä muuttujissa!
DATABASENIMI="rubicon"
DATABASEHOST="localhost"
DATABASEUSER="rubicon"
DATABASEPASS="veglujMjYFXOm7LIP6vRA63YDzTV"

# Tehdään funktio jolla voi kopsata databaseja
function copy_database {

  if [ $(whoami) != "root" ]; then
    echo "Funktio futaa vaa roottina"
    return 1
  fi

  if [[ -z $1 || -z $2 ]]; then
    echo "copy_database from_databasenimi to_databasenimi"
    echo "esim. copy_database orum orumstatic"
    return 1
  fi

  from_base=$1
  to_base=$2

  database_from="/var/lib/mysql/$1"
  database_to="/var/lib/mysql/$2"

  if [ ! -d ${database_from} ]; then
    echo "Tietokantaa ${from_base} ei löydy"
    return 1
  fi

  if [ -d ${database_to} ]; then
    echo "Tietokanta ${to_base} on jo olemassa"
    return 1
  fi

  echo "`date` -- Kopioidaan ${from_base} -> ${to_base}"

  service mysqld stop &&
  cp -R ${database_from} ${database_to} &&
  chown -R mysql: ${database_to} &&
  service mysqld start

  if [[ $? -ne 0 ]]; then
    echo "`date` -- Kopiointi epäonnistui"
    return 1
  else
    echo "`date` -- Kopiointi valmis"
    return 0
  fi
}

# Tehää nää ni kaadutaan heti jos nää muuttujat on väärin
cd ${PUPESOFT} &&
cd ${MERCARAJAPINNAT} &&

# Testataan database connectio
mysql --user=${DATABASEUSER} --password=${DATABASEPASS} --host=${DATABASEHOST} -e "use ${DATABASENIMI}" &&

# Varmuudenvuoksi takas rajapinnat dirikkaan
cd ${MERCARAJAPINNAT} &&

echo "`date` ** arkistoidaan artr **" &&
php ${PUPESOFT}/arkistoi.php artr 2011-05-01 &&

echo "`date` ** arkistoidaan atarv **" &&
php ${PUPESOFT}/arkistoi.php atarv 2011-05-01 &&

echo "`date` ** tuotepaikkasiivous **" &&
php ${MERCARAJAPINNAT}/rubicon_tuotepaikkasiivous.php ${PUPESOFT} &&

echo "`date` ** vaihda tuotenumerot **" &&
php ${PUPESOFT}/vaihda_tuoteno.php atarv ${MERCARAJAPINNAT}/vaihda_tuoteno.txt &&

echo "`date` ** konversio_varasto **" &&
mysql --user=${DATABASEUSER} --password=${DATABASEPASS} --host=${DATABASEHOST} ${DATABASENIMI} < ${MERCARAJAPINNAT}/rubicon_konversio_varasto.sql &&

echo "`date` ** asiakkaan luottorajat **" &&
php ${MERCARAJAPINNAT}/rubicon_asiakkaan_luottorajat.php ${PUPESOFT} &&

echo "`date` ** asiakkaat **" &&
php ${MERCARAJAPINNAT}/rubicon_asiakkaat.php ${PUPESOFT} &&

echo "`date` ** toimittajat **" &&
php ${MERCARAJAPINNAT}/rubicon_toimittajat.php ${PUPESOFT} &&

echo "`date` ** maksuehdot **" &&
php ${MERCARAJAPINNAT}/rubicon_maksuehdot.php ${PUPESOFT} &&

echo "`date` ** kayttajahallinta **" &&
php ${MERCARAJAPINNAT}/rubicon_kayttajahallinta.php ${PUPESOFT} &&

echo "`date` ** myyntihistoria **" &&
php ${MERCARAJAPINNAT}/rubicon_myyntihistoria_tilausrivit.php ${PUPESOFT} &&

echo "`date` ** ostohistoria **" &&
php ${MERCARAJAPINNAT}/rubicon_ostohistoria_tilausrivit.php ${PUPESOFT} &&

echo "`date` ** ale netto **" &&
php ${MERCARAJAPINNAT}/rubicon_ale_netto_konversio.php ${PUPESOFT} &&

echo "`date` ** konversio mysql **" &&
mysql --user=${DATABASEUSER} --password=${DATABASEPASS} --host=${DATABASEHOST} ${DATABASENIMI} < ${MERCARAJAPINNAT}/rubicon_konversio.sql &&

echo "`date` ** toimipaikan parametrit **" &&
php ${MERCARAJAPINNAT}/rubicon_yhtion_toimipaikan_parametrit.php ${PUPESOFT} &&

echo "`date` ** liiketoimintakauppa **" &&
php ${MERCARAJAPINNAT}/rubicon_liiketoimintakauppa.php ${PUPESOFT} &&

echo "`date` ** liiketoimintakauppa lasku **" &&
php ${MERCARAJAPINNAT}/rubicon_liiketoimintakauppalasku.php ${PUPESOFT} &&

# echo "`date` ** poista_yritys atarv **" &&
# php ${MERCARAJAPINNAT}/rubicon_poista_yritys.php ${PUPESOFT} atarv &&

echo "`date` ** check-tables **" &&
php ${PUPESOFT}/check-tables.php --verbose

if [[ $? -ne 0 ]]; then
  echo "`date` ** Konversio FEILAS!! **"
else
  echo "`date` ** Valmis! **"
fi
