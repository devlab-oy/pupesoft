#!/bin/bash

# Määritellään värit
green=`tput -Txterm-color setaf 2`
red=`tput -Txterm-color setaf 1`
white=`tput -Txterm-color setaf 7`
normal=`tput -Txterm-color sgr0`

# Echo with date funkkari
function decho {
  echo "${white}$(date "+%d.%m.%Y @ %H:%M:%S"): ${green}$1${normal}"
}

# Error with color
function error {
  echo
  echo "${red}$1${normal}"
  echo
  exit 1
}

# Jos failure niin dellataan tempdir ja kuollaan
function failure {
  nice -n 19 rm -rf "${tmpdir}" &> /dev/null
  error "$1"
}

# Odotetaan, että joku palvelu käynnistyy tiettyyn tcp-porttiin
function wait_for_service {
  # Maksimiaika joka odotetaan palvelun käynnistymistä tai sulkemista
  MAXWAIT=30

  WAIT=0

  # Tsekataan, että palvelu on noussut ylös
  while ! nc -z localhost $1; do
    if [ "${WAIT}" -ge "${MAXWAIT}" ]; then
      error "Palvelu ei käynnistynyt porttiin $1"
      break
    fi

    sleep 1;
    WAIT=$((WAIT+1))
  done
}

# Tuetaan Linux ja Mac
if [[ $(uname) == "Linux" ]]; then

  # Linuxin tiedot
  mysql_path="/var/lib/mysql/"
  mysql_start="service mysqld start"
  mysql_stop="service mysqld stop"
  mysql_owner="mysql:mysql"
  make_temp="mktemp -d"

  # Linuxissa vaa roottina
  if [[ $(whoami) != "root" ]]; then
    error "Tämä on mahdollista vain roottina"
  fi

elif [[ $(uname) == "Darwin" ]]; then

  # Katsotaan onko macissa mysql asennettu
  command -v mysql.server &> /dev/null

  if [[ $? -ne 0 ]]; then
    error "MySQL-server ei asennettu!"
  fi

  # Macin tiedot
  mysql_path="/usr/local/var/mysql/"
  mysql_start="mysql.server start"
  mysql_stop="mysql.server stop"
  mysql_owner="$(whoami):admin"
  make_temp="mktemp -d -t testpupe"

else
  error "Tuntematon käyttis!"
fi

# Eka parametri pitää olla file, toka databasenimi, kolmas mysql root password
kasiteltava_backup=$1
destination_database=$2
mysql_root_password=$3

if [[ -z ${mysql_root_password} ]]; then
  echo
  echo -n "Anna MySQL root salasana: "

  read -s mysql_root_password
  echo
fi

mysql_sock=""
mysql_port=3306

# Neljäs optional parami on custom mysql-instanssin "nimi"
if [[ "$4" != "" ]]; then
  mysql_path="/var/lib/$4/"
  mysql_sock="--socket=/var/lib/$4/$4.sock"
  mysql_port=$(grep "port" /etc/$4.cnf | egrep -o "[0-9]*")
  mysql_start="mysqld_safe --defaults-file=/etc/$4.cnf"
  mysql_stop="kill $(ps aux | egrep '[m]ysqld .*'$4 | awk '{print $2}')"
fi

database_to="${mysql_path}${destination_database}"

if [[ -z ${kasiteltava_backup} || -z ${destination_database} || -z ${mysql_root_password} ]]; then
  error "Usage: extract_test_pupesoft.sh backup database password"
fi

if [[ ! -a ${kasiteltava_backup} ]]; then
  error "Tiedostoa '${kasiteltava_backup}' ei löydy!"
fi

mysql ${mysql_sock} --user=root --password=${mysql_root_password} -e "use mysql" &> /dev/null

if [[ $? -ne 0 ]]; then
  error "Virheellinen salasana!"
fi

echo
decho "Puretaan ${kasiteltava_backup} -> '${destination_database}'.."

# Onko tietokanta valmiiksi purettu?
ONKOSNAPSHOT=$(echo "${kasiteltava_backup}" | grep -o "_snapshot")

if [[ "${ONKOSNAPSHOT}" == "_snapshot" ]] ; then
  tmpdir=${kasiteltava_backup}
else
  # Tehdään temporary directory
  tmpdir=$(${make_temp})

  # Katsotaan osetaanko purkaa parallel
  command -v pbunzip2 > /dev/null

  if [[ $? -eq 0 ]]; then
    compress_prog="--use-compress-prog=pbunzip2"
  else
    compress_prog="--use-compress-prog=bunzip2"
  fi

  # Puretaan backup
  nice -n 19 tar -xf "${kasiteltava_backup}" ${compress_prog} -C "${tmpdir}"

  if [[ $? -ne 0 ]]; then
    failure "Purku epäonnistui!"
  fi
fi

if [[ -d ${database_to} ]]; then
  decho "Tietokanta '${destination_database}' löytyy jo, ylikirjoitetaan!"
fi

decho "Siirretään ${database_to}.."

# Varmistetaan, että meillä on dest dirikka
mkdir -p "${database_to}" &> /dev/null

# Stopataan mysql, moovataan db, chown ja mysql takas
${mysql_stop} >/dev/null &&
nice -n 19 rm -f "${database_to}/*" > /dev/null &&
nice -n 19 mv -f "${tmpdir}/"* "${database_to}/" > /dev/null &&
chown -R ${mysql_owner} "${database_to}" > /dev/null

if [[ $? -ne 0 ]]; then
  nice -n 19 rm -rf "${database_to}" &> /dev/null
  failure "Siirto epäonnistui!"
fi

${mysql_start} >/dev/null 2>&1 &

# Odotetaan, että mysql on käynnistynyt
wait_for_service ${mysql_port}

if [[ "${mysql_sock}" == "" ]]; then
  decho "Puhdistetaan '${destination_database}' tietokanta.."

  mysql --user=root --password=${mysql_root_password} "${destination_database}" 2> /dev/null << EOF

UPDATE yhtio set
nimi = concat('Testi ', nimi),
ytunnus = '123',
ovttunnus = '123',
email = 'development@devlab.fi',
kotitullauslupa = '',
tullin_asiaknro = '',
tullin_lupanro = '',
tullikamari = 0,
tullipaate = '',
tilastotullikamari = 0,
intrastat_sarjanro = '';

UPDATE yhtion_parametrit set
finvoice_senderpartyid = '',
finvoice_senderintermediator = '',
verkkotunnus_vas = '',
verkkotunnus_lah = '',
verkkosala_vas = '',
verkkosala_lah = '',
apix_tunnus = '',
apix_avain = '',
maventa_api_avain = '',
maventa_yrityksen_uuid = '',
maventa_ohjelmisto_api_avain = '',
admin_email = 'development@devlab.fi',
alert_email = 'development@devlab.fi',
talhal_email = 'development@devlab.fi',
sahkopostilasku_cc_email = '',
varauskalenteri_email = '',
tuotekopio_email = '',
jt_email = '',
edi_email = '',
extranet_kerayspoikkeama_email = '',
siirtolista_email = '',
changelog_email = ' ',
postittaja_email = 'development@devlab.fi',
kuvapankki_polku = '',
verkkolasku_lah = '';

UPDATE asiakas set
email = 'development@devlab.fi',
fax = '',
puhelin = '',
lasku_email = '',
talhal_email = '',
keraysvahvistus_email = if(keraysvahvistus_email != '', 'development@devlab.fi', '');

UPDATE toimi set
email = 'development@devlab.fi',
fax = '',
puhelin = '',
edi_palvelin = '',
edi_kayttaja = '',
edi_salasana = '';

UPDATE kuka set
kirjoitin = 0,
eposti = 'development@devlab.fi';

UPDATE lasku set
email = 'development@devlab.fi',
toim_email = 'development@devlab.fi';

UPDATE yhteyshenkilo set
email = 'development@devlab.fi';

UPDATE toimitustapa set
rahtikirjakopio_email = 'development@devlab.fi'
WHERE rahtikirjakopio_email != '';

UPDATE kirjoittimet set
komento = 'email';

UPDATE kalenteri set
kentta01 = 'Lorem ipsum',
kentta02 = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
WHERE tyyppi = 'uutinen';

UPDATE kalenteri set
kentta01 = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
WHERE asiakas != '';

DELETE FROM mail_servers;

EOF

  if [[ $? -ne 0 ]]; then
    nice -n 19 rm -rf "${database_to}" &> /dev/null
    failure "Puhdistus epäonnistui!"
  fi
fi

decho "Putsataan tmp -tiedostot.."

# Poistetaan tmpdirikka
nice -n 19 rm -rf "${tmpdir}"

decho "Valmis."
echo
