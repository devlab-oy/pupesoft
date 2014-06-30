#!/bin/bash

hosti=`hostname`
underline=`tput -Txterm-color smul`
nounderline=`tput -Txterm-color rmul`
green=`tput -Txterm-color setaf 2`
red=`tput -Txterm-color setaf 1`
white=`tput -Txterm-color setaf 7`
normal=`tput -Txterm-color sgr0`

echo
echo "${green}${underline}Tervetuloa ${hosti} Pupesoft-narupalveluun!${nounderline}${normal}"
echo

if [ "`whoami`" = "root" ]; then
  echo "${red}Ei ole suositeltavaa, että ajat tämän root -käyttäjällä!${normal}"
  echo
  exit
fi

echo "Haetaan tietokantamuutokset.."

pupedir=`dirname $0`

# Katsotaan, onko parami syötetty
if [ ! -z $1 ]; then
  JATKETAAN=$1
fi

# Tutkitaan tietokantarakenne...
dumppi=`php ${pupedir}/dumppaa_mysqlkuvaus.php komentorivilta`

if [ -z "$dumppi" ]; then
  echo "${green}Tietokanta ajantasalla!${normal}"
  echo
  rm -f /tmp/_mysqlkuvays.sql
else
  echo -e $dumppi > /tmp/_mysqlkuvaus.tmp

  while read line
  do
    if [ -n "$line" ]; then
      echo $line
    fi
  done < "/tmp/_mysqlkuvaus.tmp"

  if [[ ! -z "${JATKETAAN}" && ("${JATKETAAN}" = "auto" || "${JATKETAAN}" = "autopupe") ]]; then

    if [[ "${JATKETAAN}" = "autopupe" ]]; then
      jatketaanko="e"
    else
      jatketaanko="k"
    fi
  else
    echo
    echo -n "${white}Tehdäänkö tietokantamuutokset (k/e)? ${normal}"
    read jatketaanko
  fi

  if [ "$jatketaanko" = "k" ]; then
    while read line
    do
      if [ -n "$line" ]; then
        eval $line
      fi
    done < "/tmp/_mysqlkuvaus.tmp"

    echo -n "${green}Tietokantamuutokset tehty!${normal}"
  else
    echo -n "${red}Tietokantamuutoksia ei tehty!${normal}"
  fi

  rm -f /tmp/_mysqlkuvays.sql
  rm -f /tmp/_mysqlkuvays.tmp
fi

if [[ ! -z "${JATKETAAN}" && ("${JATKETAAN}" = "auto" || "${JATKETAAN}" = "autopupe") ]]; then
  jatketaanko="k"
  echo
else
  echo
  echo
  echo -n "${white}Päivitetäänkö Pupesoft (k/e)? ${normal}"
  read jatketaanko
fi

if [ "$jatketaanko" = "k" ]; then
  cd $pupedir
  git fetch origin        # paivitetaan lokaali repo remoten tasolle
  git checkout .          # revertataan kaikki local muutokset

  BRANCHFILE="/home/devlab/pupe_branch"

  # Onko spessubranchi käytössä?
  if [[ -f "$BRANCHFILE" && -r "$BRANCHFILE" ]]; then
    pupebranch=$(cat $BRANCHFILE | tr -d '\n')

    git checkout $pupebranch
    git pull origin $pupebranch
  else
    git checkout master     # varmistetaan, etta on master branchi kaytossa
    git pull origin master  # paivitetaan master branchi
  fi

  git remote prune origin # poistetaan ylimääriset branchit

  NRFILE="/home/devlab/newrelic_trigger"

  # Onko triggeri käytössä?
  if [[ -x "$NRFILE" ]]; then
    eval $NRFILE
  fi

  echo "${green}Pupesoft päivitetty!${normal}"
else
  echo "${red}Pupesoftia ei päivitetty!${normal}"
fi

echo
echo "${green}Valmis!${normal}"
echo

###################################################################################
# Nain luodaan Pupe-installaatio:
# mkdir -p /var/www/html/pupesoft
# git clone git://github.com/devlab-oy/pupesoft.git /var/www/html/pupesoft/
###################################################################################
