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
  rm -rf "${tmpdir}" &> /dev/null
  error "$1"
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
database_to="${mysql_path}${destination_database}"

if [[ -z ${kasiteltava_backup} || -z ${destination_database} || -z ${mysql_root_password} ]]; then
  error "Usage: extract_test_pupesoft.sh backup database password"
fi

if [[ ! -a ${kasiteltava_backup} ]]; then
  error "Tiedostoa '${kasiteltava_backup}' ei löydy!"
fi

mysql --user=root --password=${mysql_root_password} -e "use mysql" &> /dev/null

if [[ $? -ne 0 ]]; then
  error "Virheellinen salasana!"
fi

echo
decho "Puretaan ${kasiteltava_backup} -> '${destination_database}'.."

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
tar -xf "${kasiteltava_backup}" ${compress_prog} -C "${tmpdir}"

if [[ $? -ne 0 ]]; then
  failure "Purku epäonnistui!"
fi

if [[ -d ${database_to} ]]; then
  decho "Tietokanta '${destination_database}' löytyy jo, ylikirjoitetaan!"
fi

decho "Siirretään ${database_to}.."

# Varmistetaan, että meillä on dest dirikka
mkdir -p "${database_to}" &> /dev/null

# Stopataan mysql, moovataan db, chown ja mysql takas
${mysql_stop} > /dev/null &&
mv -f "${tmpdir}/"* "${database_to}/" > /dev/null &&
chown -R ${mysql_owner} "${database_to}" > /dev/null &&
${mysql_start} > /dev/null

if [[ $? -ne 0 ]]; then
  rm -rf "${database_to}" &> /dev/null
  failure "Siirto epäonnistui!"
fi

decho "Puhdistetaan '${destination_database}' tietokanta.."

mysql --user=root --password=${mysql_root_password} "${destination_database}" 2> /dev/null << EOF

UPDATE yhtio set
nimi = concat('Testi ', nimi),
ytunnus = '123',
ovttunnus = '123',
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
sahkopostilasku_cc_email = 'development@devlab.fi',
edi_email = 'development@devlab.fi',
varauskalenteri_email = '',
tuotekopio_email = '',
jt_email = '',
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

EOF

if [[ $? -ne 0 ]]; then
  rm -rf "${database_to}" &> /dev/null
  failure "Puhdistus epäonnistui!"
fi

decho "Putsataan tmp -tiedostot.."

# Poistetaan tmpdirikka
rm -rf "${tmpdir}"

decho "Valmis."
echo
