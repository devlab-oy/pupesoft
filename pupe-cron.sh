#!/bin/bash
YHTIO="demo"
POLKU="/var/www/html/pupesoft"

#Tarkistetaan mysql-tietokannan taulut
cd $POLKU;php check-tables.php
#P‰ivitet‰‰n pupesoftin valuuttakurssit
cd $POLKU;php hae_valuutat_cron.php
#Teh‰‰n pupesoftin iltasiivo
cd $POLKU;php iltasiivo.php $YHTIO
#Rakennetaan Asiakas-ABC-analyysin aputaulut
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO rivia
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO myynti
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kate
cd $POLKU/raportit/; php abc_asiakas_aputaulun_rakennus.php $YHTIO kpl
#Rakennetaan Tuote-ABC-analyysin aputaulut
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO rivia
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO myynti
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kate
cd $POLKU/raportit/; php abc_tuote_aputaulun_rakennus.php $YHTIO kpl
# Siivotaan dataout dirikasta vanhat failit pois
find $POLKU/dataout -mtime +30 -exec rm -f {} \;
