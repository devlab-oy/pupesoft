#!/bin/bash

if [[ ! -f /tmp/opuscapita.lock ]]; then

  touch /tmp/opuscapita.lock

  # Mistä remote hakemistosta haetaan viitteet/tiliotteet ja mihin local dirikkaan ne siirretään
  get_hostname="user@hostname"
  get_remote_dir="~/out/"
  get_local_dir="/home/opuscapita/tilioteviite/"

  # Mistä local hakemistosta haetaan maksuaineistot ja mihin remote dirikkaan ne siirretään
  send_hostname="user@hostname"
  send_remote_dir="~/in/"
  send_local_dir="/home/opuscapita/maksuaineisto/"
  send_local_dir_ok="/home/opuscapita/maksuaineisto/ok/"

  # Loopataan läpi kaikki filet remote hakemistosta ja haetaan ne local dirikkaan
  for file in `ssh ${get_hostname} "find ${get_remote_dir} -type f -maxdepth 1"`
  do
    # Kopioidaan tiedosto lokaalidirikkaan, jos siirto onnistuu poistetaan remote file
    scp ${get_hostname}:${file} ${get_local_dir} > /dev/null && ssh ${get_hostname} "rm -f ${file}" > /dev/null
  done

  # Loopataan läpi kaikki filet local hakemistosta ja lähetetään ne remote hakemistoon
  for file in `find ${send_local_dir} -type f -maxdepth 1`
  do
    # Kopioidaan tiedosto remote dirikkaan, jos siirto onnistuu siirretään local file ok-hakemistoon
    scp ${file} ${send_hostname}:${send_remote_dir} > /dev/null && mv ${file} ${send_local_dir_ok}
  done

  rm -f /tmp/opuscapita.lock

fi
