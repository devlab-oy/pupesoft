#!/bin/bash

SLOWLOG=$1
SLOWKAYTTAJA=$2
SLOWSALASANA=$3

#Ekotetaan hitaat kyselyt
if [ -f $SLOWLOG ]; then
	echo
	echo "Pupesoft slowlog:"
	echo
	mysqldumpslow $SLOWLOG
	mysqladmin -u $SLOWKAYTTAJA --password="$SLOWSALASANA" flush-logs
fi
