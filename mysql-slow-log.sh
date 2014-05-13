#!/bin/bash

SLOWLOG=$1
SLOWKAYTTAJA=$2
SLOWSALASANA=$3

# Ekotetaan hitaat kyselyt
if [[ ! -z $SLOWLOG && -f $SLOWLOG ]]; then
  if [ -f /home/nagios/nagios-pupesoft.sh ]; then
        mysqldumpslow $SLOWLOG > /home/nagios/nagios-pupesoftslow.log 2> /dev/null
        chown nagios: /home/nagios/nagios-pupesoftslow.log
        chmod 600 /home/nagios/nagios-pupesoftslow.log
  else
      echo "Pupesoft slowlog:"
      mysqldumpslow $SLOWLOG
      echo
  fi

  echo -n > $SLOWLOG
    mysqladmin -u $SLOWKAYTTAJA --password="$SLOWSALASANA" flush-logs
fi
