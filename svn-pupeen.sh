#!/bin/bash

hosti=$(hostname)
underline=$(tput -Txterm-color smul)
nounderline=$(tput -Txterm-color rmul)
green=$(tput -Txterm-color setaf 2)
red=$(tput -Txterm-color setaf 1)
white=$(tput -Txterm-color setaf 7)
normal=$(tput -Txterm-color sgr0)

echo
echo "${green}${underline}Tervetuloa ${hosti} Pupesoft-narupalveluun!${nounderline}${normal}"
echo

if [[ "$(whoami)" = "root" ]]; then
  echo "${red}Ei ole suositeltavaa, että ajat tämän root -käyttäjällä!${normal}"
  echo
  exit
fi

pupedir=$(dirname ${0})
salasanat=${pupedir}/inc/salasanat.php
jatketaan=

if [[ ! -f ${salasanat} ]]; then
  echo "${red}Salasanat.php ei löytynyt!${normal}"
  echo
  exit
fi

function parse_salasanat {
  grep "^[ \t]*${1}[ \t]*=" ${salasanat} \
  | sed 's/^.*['\''"]\([^'\''"]*\)['\''"];/\1/' \
  | tail -1
}

dbhost=$(parse_salasanat '$dbhost')
dbuser=$(parse_salasanat '$dbuser')
dbpass=$(parse_salasanat '$dbpass')
dbname=$(parse_salasanat '$dbkanta')

# Katsotaan jos meillä on annettu poikkeava portti hostnamessa
host_array=(${dbhost//:/ })

if [[ -n "${host_array[1]}" ]]; then
  dbport=${host_array[1]}
else
  dbport="3306"
fi

# Loopataan kaikki argumentit, jos ollaan yliajettu joku
while [[ "$1" != "" ]]; do

  case $1 in
    -h | --host )
      shift
      dbhost=$1
      ;;
    -P | --port )
      shift
      dbport=$1
      ;;
    -u | --user )
      shift
      dbuser=$1
      ;;
    -p | --password )
      shift
      dbpass=$1
      ;;
    -d | --database )
      shift
      dbname=$1
      ;;
    autopupe )
      jatketaan="autopupe"
      ;;
    auto )
      jatketaan="auto"
      ;;
  esac

  shift
done

# Testataan tietokantayhteys
mysql_komento="mysql --host=${dbhost} --user=${dbuser} --password=${dbpass} --database=${dbname} --port=${dbport} --verbose"
${mysql_komento} --execute="use ${dbname}" &> /dev/null

if [[ $? -ne 0 ]]; then
  echo "${red}Tietokantayhteys ei onnistu! Tarkista salasanat!${normal}"
  echo
  exit
fi

echo "Haetaan tietokantamuutokset.."

# Tutkitaan tietokantarakenne...
mysqlkuvaus_file="/tmp/_mysqlkuvaus.tmp"

php ${pupedir}/dumppaa_mysqlkuvaus.php 1> ${mysqlkuvaus_file} 2> /dev/null

if [[ ! -s ${mysqlkuvaus_file} ]]; then
  echo "${green}Tietokanta ajantasalla!${normal}"
else
  echo
  echo "${green}Tarvittavat muutokset: ${normal}"

  cat ${mysqlkuvaus_file}
  echo

  if [[ "${jatketaan}" = "autopupe" ]]; then
    jatketaanko="e"
  elif [[ "${jatketaan}" = "auto" ]]; then
    jatketaanko="k"
  else
    echo
    echo -n "${white}Tehdäänkö tietokantamuutokset (k/e)? ${normal}"
    read jatketaanko
  fi

  if [[ "$jatketaanko" = "k" ]]; then
    ${mysql_komento} < ${mysqlkuvaus_file} 2> /dev/null

    if [[ $? -eq 0 ]]; then
      echo "${green}Tietokantamuutokset tehty!${normal}"
    else
      echo "${red}Tietokantamuutoksien ajo epäonnistui!${normal}"
    fi
  else
    echo "${red}Tietokantamuutoksia ei tehty!${normal}"
  fi
fi

rm -f ${mysqlkuvaus_file}

if [[ "${jatketaan}" = "auto" || "${jatketaan}" = "autopupe" ]]; then
  jatketaanko="k"
  echo
else
  echo
  echo -n "${white}Päivitetäänkö Pupesoft (k/e)? ${normal}"
  read jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then
  branchfile="/home/devlab/pupe_branch"

  # Onko spessubranchi käytössä?
  if [[ -f "${branchfile}" && -s "${branchfile}" ]]; then
    pupebranch=$(cat ${branchfile} | tr -d '\n')
  else
    pupebranch="master"
  fi

  cd ${pupedir} &&
  git fetch origin &&               # paivitetaan lokaali repo remoten tasolle
  git checkout . &&                 # revertataan kaikki local muutokset
  git checkout ${pupebranch} &&     # varmistetaan, etta on master branchi kaytossa
  git pull origin ${pupebranch} &&  # paivitetaan master branchi
  git remote prune origin           # poistetaan ylimääriset branchit

  # Save git exit status
  STATUS=$?

  # Get new head
  NEW_HEAD=$(git rev-parse HEAD)

  if [[ ${STATUS} -eq 0 ]]; then
    if [ -n "$SSH_CLIENT" ] || [ -n "$SSH_TTY" ]; then
      USER_IP=$(who -m am i|awk '{ print $NF}'|sed -e 's/[\(\)]//g')
    else
      USER_IP=localhost
    fi

    ${mysql_komento} -e "INSERT INTO git_paivitykset SET hash='${NEW_HEAD}', ip='${USER_IP}', date=now()" &> /dev/null
  fi

  if [[ $? -eq 0 ]]; then
    echo
    echo "${green}Pupesoft päivitetty!${normal}"
  else
    echo
    echo "${red}Pupesoft päivitys epäonnistui!${normal}"
  fi

  nrfile="/home/devlab/newrelic_trigger"

  # Onko triggeri käytössä?
  if [[ -x "${nrfile}" ]]; then
    eval ${nrfile}
  fi
else
  echo "${red}Pupesoftia ei päivitetty!${normal}"
fi

echo
