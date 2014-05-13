#!/bin/bash

DISABLED="$(lpstat -t 2> /dev/null | awk '/disabled/ { print $2 }')"

for PRINTER in ${DISABLED}
do  
 if [ -n ${PRINTER} ]; then
   cancel -a ${PRINTER}
   cupsenable ${PRINTER}
 fi
done
