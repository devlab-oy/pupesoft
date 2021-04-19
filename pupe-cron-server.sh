#!/bin/bash

#"Ohje: /polku/pupesoftiin/pupe-cron-server.sh"
#"Esim: /var/www/html/pupesoft/pupe-cron-server.sh"

POLKU=`dirname $0`
BACKUPSAVEDAYS=$1

# Oletuksena s‰‰stet‰‰n 30 backuppia
if [ -z $BACKUPSAVEDAYS ]; then
	BACKUPSAVEDAYS=30
fi

# Tarkistetaan mysql-tietokannan taulut
cd ${POLKU};php check-tables.php

# P‰ivitet‰‰n pupesoftin valuuttakurssit
cd ${POLKU};php hae_valuutat_cron.php

# Siivotaan dataout dirikasta vanhat failit pois
touch ${POLKU}/dataout
find ${POLKU}/dataout -mtime +${BACKUPSAVEDAYS} -not -path '*/.svn*' -delete
