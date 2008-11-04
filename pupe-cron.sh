cd /var/www/html/pupesoft/;php check-tables.php
cd /var/www/html/pupesoft/;php hae_valuutat_cron.php
cd /var/www/html/pupesoft/;php iltasiivo.php demo
cd /var/www/html/pupesoft/raportit/; php abc_asiakas_aputaulun_rakennus.php demo rivia
cd /var/www/html/pupesoft/raportit/; php abc_asiakas_aputaulun_rakennus.php demo myynti
cd /var/www/html/pupesoft/raportit/; php abc_asiakas_aputaulun_rakennus.php demo kate
cd /var/www/html/pupesoft/raportit/; php abc_asiakas_aputaulun_rakennus.php demo kpl
cd /var/www/html/pupesoft/raportit/; php abc_tuote_aputaulun_rakennus.php demo rivia
cd /var/www/html/pupesoft/raportit/; php abc_tuote_aputaulun_rakennus.php demo myynti
cd /var/www/html/pupesoft/raportit/; php abc_tuote_aputaulun_rakennus.php demo kate
cd /var/www/html/pupesoft/raportit/; php abc_tuote_aputaulun_rakennus.php demo kpl
find /var/www/html/pupesoft/dataout -mtime +30 -exec rm -f {} \;
find /backup/ota-backup -mtime +30 -exec rm -f {} \;
/usr/bin/rdate -s time-a.nist.gov