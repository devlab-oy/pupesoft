#!/bin/bash

underline=`tput -Txterm-color smul`
nounderline=`tput -Txterm-color rmul`
green=`tput -Txterm-color setaf 2`
red=`tput -Txterm-color setaf 1`
white=`tput -Txterm-color setaf 7`
normal=`tput -Txterm-color sgr0`

COMMAND=$1
TARGET=$2

echo
echo "${green}${underline}Pupesoft Extranet -siirto${nounderline}${normal}"
echo

# Katsotaan, että parametrit on annettu
if [ -z ${COMMAND} ] || [ -z ${TARGET} ]; then
  echo "${red}ERROR! Pakollisia parametreja ei annettu!${normal}"
  echo
  echo "Ohje: exranet_siirto.sh siirtotyyppi kohdehakemisto [lisää_filejä] ..."
  echo
  echo "Esim: extranet_siirto.sh cp /path/to/extranet"
  echo "Esim: extranet_siirto.sh scp user@host.example.com:/path/to/extranet"
  echo "Esim: extranet_siirto.sh cp /path/to/extranet /copy/one/more/file/from/here /and/one/here"
  echo
  exit
fi

if [[ ${COMMAND} != "cp" ]] && [[ ${COMMAND} != "scp" ]]; then
  echo "${red}ERROR! Komento joko cp tai scp!${normal}"
  echo
  exit
fi

# Kahen ekan parametrin jälkeen loput parametrit on filejä, jotka pitää kopsata
args_array=( $@ )
unset args_array[1]
unset args_array[0]
EXTRA_FILES=${args_array[@]}

# Siirrytään Pupesoft roottiin
extranet_directory=`dirname $0`
cd ${extranet_directory}/..

# Kopioidaan nämä hakemistot rakenteineen, ei saa olla perässä /-merkkiä tai ainoastaan kansion
# sisältö kopioidaan
DIRECTORIES="barcode
excel_reader
js
pdflib
pics
validation
viivakoodi
DataTables
css
pupe_xslx_template"

# Kopioidaan näiden hakemistojen sisältö extranet roottiin
CONTENT="extranet/*"

# Kopioidaan nämä tiedostot extranet roottiin
FILES="extranet_tarjoukset_ja_ennakot.php
hinnasto.php
inc/asiakashaku.inc
inc/connect.inc
inc/footer.inc
inc/ftp-send.inc
inc/functions.inc
inc/generoiviite.inc
inc/hinnastoriviautomaster.inc
inc/hinnastorivifutur.inc
inc/hinnastorivitab.inc
inc/hinnastorivivienti.inc
inc/jquery-ui.js
inc/jquery.min.js
inc/laskutyyppi.inc
inc/parametrit.inc
inc/ProgressBar.class.php
inc/pupeExcel.inc
inc/sahkoposti.inc
inc/tuotehaku.inc
indexvas.php
index.php
ylaframe.php
pikavalinnat.php
korvaavat.class.php
rajapinnat/logmaster/logmaster-functions.php
raportit/asiakasinfo.php
raportit/naytatilaus.inc
raportit/osasto_tuotemerkeittain.php
raportit/saatanat.php
tilauskasittely/editilaus_out_futur.inc
tilauskasittely/jtselaus.php
tilauskasittely/laskealetuudestaan.inc
tilauskasittely/laskutus.inc
tilauskasittely/lisaarivi.inc
tilauskasittely/luo_myyntitilausotsikko.inc
tilauskasittely/mikrotilaus.inc
tilauskasittely/monivalintalaatikot.inc
tilauskasittely/osoitelappu_pdf.inc
tilauskasittely/otsik.inc
tilauskasittely/pupesoft_ediout.inc
tilauskasittely/syotarivi.inc
tilauskasittely/tarkistarivi.inc
tilauskasittely/tee_jt_tilaus.inc
tilauskasittely/teeulasku.inc
tilauskasittely/tilauksesta_myyntitilaus.inc
tilauskasittely/tilauksesta_ostotilaus.inc
tilauskasittely/tilaus-valmis-valitsetila.inc
tilauskasittely/tilaus-valmis.inc
tilauskasittely/tilaus_myynti.php
tilauskasittely/tilausvahvistus-*
tilauskasittely/tulosta_lahete*
tilauskasittely/tulosta_lasku.inc
tilauskasittely/tulosta_ostotilaus.inc
tilauskasittely/tulosta_tilausvahvistus_pdf.inc
tilauskasittely/tulostakopio.php
tilauskasittely/tuote_selaus_haku.php
vastaavat.class.php
verkkokauppa/ostoskori-kysely.php
verkkokauppa/ostoskori.inc
verkkokauppa/ostoskori.php
verkkokauppa/tyhjenna_ostoskorit.php
tiedostokirjasto.php
tiedostofunkkarit.inc
asiakasvalinta.inc
extranet_tyomaaraykset.php
extranet_laiterekisteri.php
nayta_tyomaarayksen_tapahtumat.php
tyomaarays/tulosta_tyomaarays.inc
huoltopyynto_pdf.inc
popup.js"

# Yhdistä muuttujat ja vaihda rivinvaihdot spaceiksi
ALL_FILES=`echo ${DIRECTORIES} ${CONTENT} ${FILES} ${EXTRA_FILES} | tr '\n' ' '`

# Kopiointikomento
${COMMAND} -r ${ALL_FILES} ${TARGET}

if [[ $? -eq 0 ]]; then
  echo "Extranet siirretty hakemistoon: ${white}${TARGET}${normal}"
else
  echo
  echo "${red}ERROR! Extranet siirto epäonnistui hakemistoon: ${white}${TARGET}${normal}"
fi

echo
