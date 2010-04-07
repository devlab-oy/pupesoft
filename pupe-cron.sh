#!/bin/bash

YHTIO=$1
POLKU=$2

# Katsotaan, että parametrit on annettu
if [ -z $YHTIO ] || [ -z $POLKU ]; then
	echo
	echo "ERROR! Pakollisia parametreja ei annettu!"
	echo
	echo "Ohje: pupe-cron.sh yhtio pupesoft-polku"
	echo "Esim: pupe-cron.sh demo /var/www/html/pupesoft"
	echo
	exit
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d $POLKU ]; then
	echo
	echo "ERROR! Hakemistoa $POLKU ei löydy!"
	echo
	exit
fi

# Tarkistetaan mysql-tietokannan taulut
cd $POLKU;php check-tables.php

# Päivitetään pupesoftin valuuttakurssit
cd $POLKU;php hae_valuutat_cron.php

# Tehään pupesoftin iltasiivo
cd $POLKU;php iltasiivo.php $YHTIO

# Rakennetaan Asiakas-ABC-analyysin aputaulut
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO rivia
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO myynti
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kate
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kpl

# Rakennetaan Tuote-ABC-analyysin aputaulut
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO rivia
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO myynti
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kate
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kpl

# Siivotaan dataout dirikasta vanhat failit pois
find $POLKU/dataout -mtime +30 -not -path '*/.svn/*' -delete
