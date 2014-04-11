#!/bin/bash

POLKU=`dirname $0`

DBKANTA=$1
DBKAYTTAJA=$2
DBSALASANA=$3
DBHOST=$4


# Katsotaan, että parametrit on annettu
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
	DBHOSTLISA=" -h ${DBHOST} "
else
	DBHOSTLISA=""
fi

YHTIOT=`mysql ${DBHOSTLISA} -u ${DBKAYTTAJA} --password=${DBSALASANA} ${DBKANTA} -B -N -e "SELECT yhtio FROM yhtio"`

for YHTIO in $YHTIOT
do
	# Tehään pupesoftin iltasiivo
	cd ${POLKU};php iltasiivo.php $YHTIO

	# Käydään luottoraja_täynnä tilaukset läpi ja laukaistaan ne eteenpäin jotka on ok
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
