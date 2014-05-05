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
echo "Päivitetään ${PRIVATEDIR}"

cd ${PRIVATEDIR}
git fetch origin        # paivitetaan lokaali repo remoten tasolle
git checkout .          # revertataan kaikki local muutokset
git checkout master     # varmistetaan, etta on master branchi kaytossa
git pull origin master  # paivitetaan master branchi
git remote prune origin # poistetaan ylimääriset branchit

cp -Rf ${PRIVATEDIR}/* ${PUPEDIR}/

echo
echo "Valmis!"
echo
