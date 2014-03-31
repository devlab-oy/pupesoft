#!/bin/bash

# Katotaan, että flock komento löytyy
command -v flock > /dev/null

if [[ $? != 0 ]]; then
  echo "Flock komentoa ei löydy!"
  exit 1
fi

# Tehdään lukkofile
lock_file="/tmp/##opuscapita.sh-flock.lock"

exec 9> ${lock_file}

if ! flock -n 9 ; then
  echo "Siirto menossa!";
  exit 1
else
  touch ${lock_file}
  chmod 666 ${lock_file}
fi

# Mistä remote hakemistosta haetaan viitteet/tiliotteet ja mihin local dirikkaan ne siirretään
get_hostname=$1      # user@hostname
get_remote_dir=$2    # ~/out/
get_local_dir=$3     # /home/opuscapita/tilioteviite/

# Mistä local hakemistosta haetaan maksuaineistot ja mihin remote dirikkaan ne siirretään
send_hostname=$4     # user@hostname
send_remote_dir=$5   # ~/in/
send_local_dir=$6    # /home/opuscapita/maksuaineisto/
send_local_dir_ok=$7 # /home/opuscapita/maksuaineisto/ok/

# Katsotaan, että parametrit on annettu
if [ -z ${get_hostname} ] || [ -z ${get_remote_dir} ] || [ -z ${get_local_dir} ] || [ -z ${send_hostname} ] || [ -z ${send_remote_dir} ] || [ -z ${send_local_dir} ] || [ -z ${send_local_dir_ok} ]; then
  echo
  echo "ERROR! Pakollisia parametreja ei annettu!"
  echo
  echo 'Esim: opuscapita.sh "user@hostname" "~/out/" "/home/opuscapita/tilioteviite/" "user@hostname" "~/in/" "/home/opuscapita/maksuaineisto/" "/home/opuscapita/maksuaineisto/ok/"'
  echo
  exit 1
fi

# Loopataan läpi kaikki filet remote hakemistosta ja haetaan ne local dirikkaan
for file in `ssh ${get_hostname} "find ${get_remote_dir} -maxdepth 1 -type f"`
do
  # Kopioidaan tiedosto lokaalidirikkaan, jos siirto onnistuu poistetaan remote file
  scp ${get_hostname}:${file} ${get_local_dir} > /dev/null && ssh ${get_hostname} "rm -f ${file}" > /dev/null
done

# Loopataan läpi kaikki filet local hakemistosta ja lähetetään ne remote hakemistoon
for file in `find ${send_local_dir} -maxdepth 1 -type f`
do
  # Kopioidaan tiedosto remote dirikkaan, jos siirto onnistuu siirretään local file ok-hakemistoon
  scp ${file} ${send_hostname}:${send_remote_dir} > /dev/null && mv ${file} ${send_local_dir_ok}
done

# Vapautetaan lukko
flock -u 9
