#!/bin/bash

POLKU=`dirname $0`

DBKANTA=$1
DBKAYTTAJA=$2
DBSALASANA=$3
DBHOST=$4


# Katsotaan, ett‰ parametrit on annettu
if [ -z ${DBKANTA} ] || [ -z ${DBKAYTTAJA} ] || [ -z ${DBSALASANA} ]; then
  echo
  echo "ERROR! Pakollisia parametreja ei annettu!"
  echo
  echo "Ohje: /polku/pupesoftiin/pupe-cron.sh KANTA KAYTTAJA SALASANA"
  echo "Esim: /var/www/html/pupesoft/pupe-cron.sh pupesoft pupesoft pupe1"
  echo
  exit
fi

if [ -n "${DBHOST}" ]; then
  # Katsotaan jos meill‰ on annettu poikkeava portti hostnamessa
  host_array=(${DBHOST//:/ })

  if [ -n "${host_array[1]}" ]; then
    DBHOSTLISA="--host=${host_array[0]} --port=${host_array[1]}"
  else
    DBHOSTLISA="--host=${DBHOST}"
  fi
else
  DBHOSTLISA=""
fi

YHTIOT=`mysql ${DBHOSTLISA} --user=${DBKAYTTAJA} --password=${DBSALASANA} ${DBKANTA} -B -N -e "SELECT yhtio FROM yhtio"`

for YHTIO in $YHTIOT
do
  # Teh‰‰n pupesoftin iltasiivo
  cd ${POLKU};php iltasiivo.php $YHTIO

  # K‰yd‰‰n luottoraja_t‰ynn‰ tilaukset l‰pi ja laukaistaan ne eteenp‰in jotka on ok
  cd ${POLKU};php odottaa_suoritusta.php $YHTIO

  echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
  echo ": ABC Aputaulujen rakennus."

  # Rakennetaan Asiakas-ABC-analyysin aputaulut
  cd ${POLKU}/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO

  # Rakennetaan Tuote-ABC-analyysin aputaulut
  cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO
  cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kulutus

  echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
  echo ": ABC Aputaulujen rakennus. Done!"
  echo
done
