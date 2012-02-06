#!/bin/bash

#"Ohje: /polku/pupesoftiin/pupe-cron-server.sh"
#"Esim: /var/www/html/pupesoft/pupe-cron-server.sh"

POLKU=`dirname $0`
BACKUPSAVEDAYS=$1

# Oletuksena s��stet��n 30 backuppia
if [ -z $BACKUPSAVEDAYS ]; then
	BACKUPSAVEDAYS=30
fi

# Tarkistetaan mysql-tietokannan taulut
cd ${POLKU};php check-tables.php

# P�ivitet��n pupesoftin valuuttakurssit
cd ${POLKU};php hae_valuutat_cron.php

# Siivotaan dataout dirikasta vanhat failit pois
touch ${POLKU}/dataout
find ${POLKU}/dataout -mtime +${BACKUPSAVEDAYS} -not -path '*/.gitignore' -delete

# Siivotaan datain dirikasta vanhat failit pois
touch ${POLKU}/datain
find ${POLKU}/datain -mtime +${BACKUPSAVEDAYS} -not -path '*/Finvoice*' -not -path '*/*.xsd' -delete

# Jos Nagios on k�yt�ss�, niin tsekataan apachen fatalit errorit
if [ -f "/home/nagios/nagios-pupesoft.sh" ]; then
	fatalitvirheet=`grep -i fatal /var/log/httpd/*error_log`

	if [ -n "${fatalitvirheet}" ]; then
		echo "${fatalitvirheet}" >> /home/nagios/nagios-pupesoft.log
		chown nagios:apache /home/nagios/nagios-pupesoft.log
		chmod 660 /home/nagios/nagios-pupesoft.log
	fi
fi
