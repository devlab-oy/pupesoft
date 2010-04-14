#!/bin/bash

#"Ohje: /polku/pupesoftiin/pupe-cron-server.sh"
#"Esim: /var/www/html/pupesoft/pupe-cron-server.sh"

POLKU=`dirname $0`

# Tarkistetaan mysql-tietokannan taulut
cd ${POLKU};php check-tables.php

# P‰ivitet‰‰n pupesoftin valuuttakurssit
cd ${POLKU};php hae_valuutat_cron.php

# Siivotaan dataout dirikasta vanhat failit pois
find ${POLKU}/dataout -mtime +30 -not -path '*/.svn/*' -delete
