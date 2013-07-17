#!/bin/bash

DISABLED="$(lpstat -t | awk '/disabled/ { print $2 }')"

for PRINTER in ${DISABLED}
do
 cancel -a ${PRINTER}
 cupsenable ${PRINTER}
done