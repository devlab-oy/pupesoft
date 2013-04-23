#!/bin/bash

YHTIO=$1
POLKU=`dirname $0`

# Katsotaan, ett‰ parametrit on annettu
if [ -z $YHTIO ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: /polku/pupesoftiin/pupe-cron.sh yhtio"
	echo "Esim: /var/www/html/pupesoft/pupe-cron.sh demo"
	echo
	exit
fi

# Teh‰‰n pupesoftin iltasiivo
cd ${POLKU};php iltasiivo.php $YHTIO

# K‰yd‰‰n luottoraja_t‰ynn‰ tilaukset l‰pi ja laukaistaan ne eteenp‰in jotka on ok
cd ${POLKU};php odottaa_suoritusta.php $YHTIO

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": ABC Aputaulujen rakennus."

# Rakennetaan Asiakas-ABC-analyysin aputaulut
cd ${POLKU}/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO rivia
cd ${POLKU}/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO myynti
cd ${POLKU}/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kate
cd ${POLKU}/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kpl

# Rakennetaan Tuote-ABC-analyysin aputaulut
cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO rivia
cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO myynti
cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kate
cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kpl
cd ${POLKU}/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kulutus

echo -n `date "+%d.%m.%Y @ %H:%M:%S"`
echo ": ABC Aputaulujen rakennus. Done!"
echo
