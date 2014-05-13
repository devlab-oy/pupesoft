#!/bin/bash

#"Ohje: /polku/pupesoftiin/pupe-cron-server.sh"
#"Esim: /var/www/html/pupesoft/pupe-cron-server.sh"

POLKU=`dirname $0`
BACKUPSAVEDAYS=$1

# Oletuksena s��stet��n 30 backuppia
if [ -z $BACKUPSAVEDAYS ]; then
  BACKUPSAVEDAYS=30
fi

# Siirryt��n pupesoft hakemistoon varmuuden vuoksi
cd ${POLKU}

# Tarkistetaan mysql-tietokannan taulut
php ${POLKU}/check-tables.php

# P�ivitet��n pupesoftin valuuttakurssit
php ${POLKU}/hae_valuutat_cron.php

# Haetaan APIX -laskut
php ${POLKU}/apix-api.php

# Haetaan Maventa -laskut
php ${POLKU}/maventa-api.php

# Ajetaan verkkolaskut sis��n Pupesoftiin
php ${POLKU}/verkkolasku-in.php

# Siivotaan dataout dirikasta vanhat failit pois
touch ${POLKU}/dataout
find ${POLKU}/dataout -type f -mtime +${BACKUPSAVEDAYS} -not -path '*/.gitignore' -delete

# Siivotaan datain dirikasta vanhat failit pois
touch ${POLKU}/datain
find ${POLKU}/datain -type f -mtime +${BACKUPSAVEDAYS} -not -path '*/Finvoice*' -not -path '*/*.xsd' -not -path '*/*.xsl' -delete

# Jos Nagios on k�yt�ss�, niin tsekataan apachen fatalit errorit
if [ -f "/home/nagios/nagios-pupesoft.sh" ]; then
  fatalitvirheet=`grep -i fatal /var/log/httpd/*error_log`

  if [ -n "${fatalitvirheet}" ]; then
    echo "${fatalitvirheet}" >> /home/nagios/nagios-pupesoft.log
    chown nagios:apache /home/nagios/nagios-pupesoft.log
    chmod 660 /home/nagios/nagios-pupesoft.log
  fi
fi

# Enabloidaan kaikki disabloidut printterit
bash ${POLKU}/enable-printers.sh
