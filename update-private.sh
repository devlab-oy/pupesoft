#!/bin/bash

PUPEDIR=`dirname $0`
PRIVATEDIR=$1

if [ ! -d ${PUPEDIR} ] || [ ! -d ${PRIVATEDIR} ]; then
	echo
	echo "ERROR! Hakemistoja ei löydy!"
	echo
	exit
fi

echo
echo "Paivitetaan ${PRIVATEDIR}"

cd ${PRIVATEDIR}
git checkout .             # revertataan kaikki local muutokset
git pull origin master     # paivitetaan aina varmasti master branchi

cp -Rf ${PRIVATEDIR}/* ${PUPEDIR}/

echo
echo "Valmis!"
echo
