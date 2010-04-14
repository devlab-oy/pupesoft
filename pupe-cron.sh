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
