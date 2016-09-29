#!/bin/bash

PUPEDIR=`dirname $0`
PRIVATEDIR=$1

if [[ ! -d ${PUPEDIR} || ! -d ${PRIVATEDIR} ]]; then
  echo
  echo "ERROR! Hakemistoja ei löydy!"
  echo
  exit
fi

echo
echo "Päivitetään ${PRIVATEDIR}"

branchfile="/home/devlab/private_branch"

# Onko spessubranchi käytössä?
if [[ -f "${branchfile}" && -s "${branchfile}" ]]; then
  private_branch=$(cat ${branchfile} | tr -d '\n')
else
  private_branch="master"
fi

cd ${PRIVATEDIR} &&
git fetch origin &&                    # paivitetaan lokaali repo remoten tasolle
git checkout . &&                      # revertataan kaikki local muutokset
git checkout ${private_branch} &&      # varmistetaan, etta on master branchi kaytossa
git pull origin ${private_branch} &&   # paivitetaan master branchi
git remote prune origin &&             # poistetaan ylimääriset branchit
cp -Rf ${PRIVATEDIR}/* ${PUPEDIR}/

if [[ $? -eq 0 ]]; then
  echo
  echo "Private repository päivitetty!"
else
  echo
  echo "Private repository päivitys epäonnistui!"
fi

echo
