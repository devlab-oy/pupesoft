#!/bin/bash

# Katotaan, että flock komento löytyy
command -v flock > /dev/null

if [[ $? != 0 ]]; then
  echo "Flock komentoa ei löydy!"
 # exit 1
fi

# Tehdään lukkofile
lock_file="/tmp/##crossdock_editilaus.sh-flock.lock"

exec 9> ${lock_file}

if ! flock -n 9 ; then
  echo "Siirto menossa!";
 # exit 1
else
  touch ${lock_file}
  chmod 666 ${lock_file}
fi

# Mistä local hakemistosta haetaan ja mihin local dirikkaan ne siirretään
mista_haetaan=$1 
minne_siirretaan=$2
pupepolku=`dirname $0`

# Katsotaan, että parametrit on annettu
if [ -z ${mista_haetaan} ] || [ -z ${minne_siirretaan} ]; then
  echo
  echo "ERROR! Pakollisia parametreja ei annettu!"
  echo
  echo 'Esim: crossdock_editilaus.sh "/home/kissa"  "/home/editilaus" '
  echo
  exit 1
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${mista_haetaan} ]; then
  echo
  echo "ERROR! Hakemistoa ${mista_haetaan} ei löydy!"
  echo
  exit
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${mista_haetaan}/done ]; then
  echo
  echo "ERROR! Hakemistoa ${mista_haetaan}/done ei löydy!"
  echo
  exit
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${minne_siirretaan} ]; then
  echo
  echo "ERROR! Hakemistoa ${minne_siirretaan} ei löydy!"
  echo
  exit
fi

# Loopataan läpi kaikki filet local hakemistosta ja lähetetään ne remote hakemistoon
for file in `find ${mista_haetaan} -maxdepth 1 -type f`
do
  # Kopioidaan tiedosto remote dirikkaan, jos siirto onnistuu siirretään local file ok-hakemistoon
  /usr/bin/php ${pupepolku}/tilauskasittely/splittaa_crossdock_editilaus.php ${file} ${minne_siirretaan}
  mv -f ${file} ${mista_haetaan}/done
done

# Vapautetaan lukko
flock -u 9
