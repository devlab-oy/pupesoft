#!/bin/bash

SLOWLOG=$1
SLOWKAYTTAJA=$2
SLOWSALASANA=$3

#Ekotetaan hitaat kyselyt
if [ -f $SLOWLOG ]; then
	echo
	echo "Pupesoft slowlog:"
	mysqldumpslow $SLOWLOG
	rm -f $SLOWLOG
	mysqladmin -u $SLOWKAYTTAJA --password="$SLOWSALASANA" flush-logs
fi
