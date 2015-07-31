#!/bin/bash

####################################################################################################
#### Functions #####################################################################################
####################################################################################################

function parse_salasanat {
  grep "^[ \t]*${1}[ \t]*=" ${salasanat} \
  | sed 's/^.*['\''"]\([^'\''"]*\)['\''"];/\1/' \
  | tail -1
}

####################################################################################################
#### Preparation ###################################################################################
####################################################################################################

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

# Check we have Git
command -v git > /dev/null

if [[ $? != 0 ]]; then
  echo "${red}Install git first!${normal}"
  exit
fi

if [[ "$(whoami)" = "root" ]]; then
  echo "${red}Ei ole suositeltavaa, että ajat tämän root -käyttäjällä!${normal}"
  echo
  exit
fi

pupedir=$(dirname ${0})
pupenextdir=${pupedir}/pupenext
salasanat=${pupedir}/inc/salasanat.php
environment="production"
jatketaan=

if [[ ! -f ${salasanat} ]]; then
  echo "${red}Salasanat.php ei löytynyt!${normal}"
  echo
  exit
fi

dbhost=$(parse_salasanat '$dbhost')
dbuser=$(parse_salasanat '$dbuser')
dbpass=$(parse_salasanat '$dbpass')
dbname=$(parse_salasanat '$dbkanta')

# Katsotaan jos meillä on annettu poikkeava portti hostnamessa
host_array=(${dbhost//:/ })

if [[ -n "${host_array[1]}" ]]; then
  dbhost=${host_array[0]}
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
    -e | --environment )
      shift
      environment=$1
      ;;
    bundle )
      bundle=true
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

####################################################################################################
#### Pupesoft ######################################################################################
####################################################################################################

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

    # Informoidaan käyttäjiä päivityksestä
    while read -r line
    do
      php pupesoft_changelog.php ${line}
    done < <(${mysql_komento%\-\-verbose} --skip-column-names -B -e "SELECT yhtio FROM yhtion_parametrit where changelog_email != ''" 2> /dev/null)
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

####################################################################################################
#### Pupenext ######################################################################################
####################################################################################################

cd ${pupenextdir}

# Setataan rails env
export RAILS_ENV=${environment}

# Make sure we have rbenv environment
if [[ -d ~/.rbenv ]]; then
  # Add rbenv to path
  export PATH="$HOME/.rbenv/bin:$PATH"

  # Load rbenv
  eval "$(rbenv init -)"
fi

# Check we have Bundler
command -v bundle > /dev/null

if [[ $? != 0 ]]; then
  echo "${red}Install bundle first!${normal}"
  exit
fi

# Get required directories
current_dir=$(pwd)
dirname=$(dirname $0)
app_dir=$(cd "${dirname}" && pwd)

if [[ ! -z "${jatketaan}" && ("${jatketaan}" = "auto" || "${jatketaan}" = "autopupe") ]]; then
  jatketaanko="k"
else
  echo -n "${white}Päivitetäänkö Pupenext (Tietokantamuutosten haku vaatii Pupenextin päivittämisen) (k/e)? ${normal}"
  read jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then

  # Jos bundle on annettu parametreissä, niin bundlataan aina eikä tarvitse tsekata git-juttuja
  if [[ ${bundle} = true ]]; then
    OLD_HEAD=0
  else
    # Get old head
    OLD_HEAD=$(cd "${app_dir}" && git rev-parse HEAD)
  fi

  # Change to app directory
  cd "${app_dir}" &&

  # Jos bundle on annettu parametreissä, niin bundlataan aina eikä tarvitse tsekata git-juttuja
  if [[ ${bundle} = true ]]; then
    STATUS=0
    NEW_HEAD=1
  else
    # Update app with git
    git fetch origin &&
    git checkout . &&
    git checkout master &&
    git pull origin master &&
    git remote prune origin

    # Save git exit status
    STATUS=$?

    # Get new head
    NEW_HEAD=$(git rev-parse HEAD)
  fi

  # Check tmp dir
  if [ ! -d "${app_dir}/tmp" ]; then
    mkdir "${app_dir}/tmp"
  fi

  echo

  # Ei päivitettävää
  if [[ ${STATUS} -eq 0 && ${OLD_HEAD} = ${NEW_HEAD} ]]; then
    echo "${green}Pupenext ajantasalla, ei päivitettävää!${normal}"
  elif [[ ${STATUS} -eq 0 ]]; then
    # Run bundle + rake
    bundle --quiet &&
    bundle exec rake css:write &&
    bundle exec rake assets:precompile &&

    # Restart rails App
    touch "${app_dir}/tmp/restart.txt" &&
    chmod 777 "${app_dir}/tmp/restart.txt" &&

    # Restart Resque workers
    bundle exec rake resque:stop_workers &&
    TERM_CHILD=1 BACKGROUND=yes QUEUES=* bundle exec rake resque:work &&

    # Tehdään requesti Rails appiin, jotta latautuu valmiiksi seuraavaa requestiä varten
    # curl --silent --connect-timeout 1 --insecure "https://$(hostname -I)/pupenext" > /dev/null &&
    # curl --silent --connect-timeout 1 --insecure "https://$(hostname)/pupenext" > /dev/null

    if [[ ${STATUS} -eq 0 ]]; then
      echo "${green}Pupenext päivitetty!${normal}"
    else
      echo "${red}Rails päivitys/uudelleenkäynnistys epäonnistui!${normal}"
    fi
  else
    echo "${red}Pupenext päivitys epäonnistui!${normal}"
  fi
else
  echo "${red}Pupenextiä ei päivitetty!${normal}"
fi

####################################################################################################
#### Database changes ##############################################################################
####################################################################################################

echo "Haetaan tietokantamuutokset.."

muutokset=$(bundle exec rake db:migrate:status | grep 'down\|Migration ID')
echo "${muutokset}" | grep 'down'

if [[ $? -eq 1 ]]; then
  echo "${green}Tietokanta ajantasalla!${normal}"
else
  echo
  echo "${green}Tarvittavat muutokset: ${normal}"

  echo "${muutokset}"
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
    bundle exec rake db:migrate

    if [[ $? -eq 0 ]]; then
      echo "${green}Tietokantamuutokset tehty!${normal}"
    else
      echo "${red}Tietokantamuutoksien ajo epäonnistui!${normal}"
    fi
  else
    echo "${red}Tietokantamuutoksia ei tehty!${normal}"
  fi
fi

# Poistetaan rails env
unset RAILS_ENV
