#!/bin/bash

# Katotaan, että flock komento löytyy
command -v flock > /dev/null

if [[ $? != 0 ]]; then
  echo "Flock komentoa ei löydy!"
  exit 1
fi

# Tehdään lukkofile
lock_file="/tmp/##tiliote.sh-flock.lock"

exec 9> ${lock_file}

if ! flock -n 9 ; then
  echo "Tiliote ajo menossa!";
  exit 1
else
  touch ${lock_file}
  chmod 666 ${lock_file}
fi

# Parametrit
local_dir=${1%/}           # Mistä hakemistosta haetaan viitteet/tiliotteet (vika slash pois)
local_dir_ok=${2%/}        # Mihin hakemistoon siirretään käsittelyn jälkeen (vika slash pois)
pupesoft_dir=$(dirname $0) # Pupesoft root hakemisto

# Katsotaan, että parametrit on annettu
if [ -z ${local_dir} ] || [ -z ${local_dir_ok} ]; then
  echo
  echo "ERROR! Pakollisia parametreja ei annettu!"
  echo
  echo 'Esim: tiliote.sh "/home/tiliotteet" "/home/tiliotteet/done"'
  echo
  exit 1
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${local_dir} ]; then
  echo
  echo "ERROR! Hakemistoa ${local_dir} ei löydy!"
  echo
  exit 1
fi

# Katsotaan, että hakemisto löytyy
if [ ! -d ${local_dir_ok} ]; then
  echo
  echo "ERROR! Hakemistoa ${local_dir_ok} ei löydy!"
  echo
  exit 1
fi

for file in $(find "${local_dir}" -maxdepth 1 -type f)
do

  # Poistetaan polku filenamesta
  basefile=$(basename ${file})

  # Tehdään timestamp
  timestamp=$(date +%Y%d%m-%H%M%S)

  # Ajetaan tiliote sitään (huom eka parametri pitää olla "perl")
  /usr/bin/php "${pupesoft_dir}/tiliote.php" "perl" "${file}"

  # Siirretään tiedosto done hakemistoon
  mv -f "${file}" "${local_dir_ok}/${timestamp}_${basefile}"
done

# Vapautetaan lukko
flock -u 9
