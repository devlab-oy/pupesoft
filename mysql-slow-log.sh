#!/bin/bash

SLOWLOG=$1
SLOWKAYTTAJA=$2
SLOWSALASANA=$3

#Ekotetaan hitaat kyselyt
if [ -f $SLOWLOG ]; then
	echo
	echo "Pupesoft slowlog:"
	mysqldumpslow $SLOWLOG
	echo -n > $SLOWLOG	
	mysqladmin -u $SLOWKAYTTAJA --password="$SLOWSALASANA" flush-logs
fi
