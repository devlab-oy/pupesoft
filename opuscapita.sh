#!/bin/bash

if [[ ! -f /tmp/opuscapita.lock ]]; then

  touch /tmp/opuscapita.lock

  # Mist� remote hakemistosta haetaan viitteet/tiliotteet ja mihin local dirikkaan ne siirret��n
  get_hostname="user@hostname"
  get_remote_dir="~/out/"
  get_local_dir="/home/opuscapita/tilioteviite/"

  # Mist� local hakemistosta haetaan maksuaineistot ja mihin remote dirikkaan ne siirret��n
  send_hostname="user@hostname"
  send_remote_dir="~/in/"
  send_local_dir="/home/opuscapita/maksuaineisto/"
  send_local_dir_ok="/home/opuscapita/maksuaineisto/ok/"

  # Loopataan l�pi kaikki filet remote hakemistosta ja haetaan ne local dirikkaan
  for file in `ssh ${get_hostname} "find ${get_remote_dir} -maxdepth 1 -type f"`
  do
    # Kopioidaan tiedosto lokaalidirikkaan, jos siirto onnistuu poistetaan remote file
    scp ${get_hostname}:${file} ${get_local_dir} > /dev/null && ssh ${get_hostname} "rm -f ${file}" > /dev/null
  done

  # Loopataan l�pi kaikki filet local hakemistosta ja l�hetet��n ne remote hakemistoon
  for file in `find ${send_local_dir} -maxdepth 1 -type f`
  do
    # Kopioidaan tiedosto remote dirikkaan, jos siirto onnistuu siirret��n local file ok-hakemistoon
    scp ${file} ${send_hostname}:${send_remote_dir} > /dev/null && mv ${file} ${send_local_dir_ok}
  done

  rm -f /tmp/opuscapita.lock

fi
