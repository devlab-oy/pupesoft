#!/bin/bash

####################################################################################################
#### Functions #####################################################################################
####################################################################################################

function parse_salasanat {
  grep "^[ \t]*${1}[ \t]*=" ${salasanat} \
  | sed 's/^.*['\''"]\([^'\''"]*\)['\''"];/\1/' \
  | tail -1
}

function git_repo_uptodate {
  dir=$1
  branch=$2

  if [[ ! -d ${dir} ]]; then
    echo "${red}Hakemistoa '${dir}' ei löytynyt!${normal}"
    echo
    exit
  fi

  # Do git fetch to get current status from origin
  cd "${dir}" && git fetch origin --quiet 2>&1 > /dev/null
  EV1=$?

  # Get current branch
  symref=$(cd "${dir}" && git symbolic-ref --quiet HEAD)
  current_branch=${symref#refs/heads/}

  # If we are changing branches, we should always have changes
  if [[ ${current_branch} != ${branch} ]]; then
    return 1
  fi

  # Get latest commit from local branch
  OLD_HEAD=$(cd "${dir}" && git rev-parse --quiet --verify ${branch})
  EV2=$?

  # Get latest commit from origin branch
  NEWEST_HEAD=$(cd "${dir}" && git rev-parse --quiet --verify origin/${branch})
  EV3=$?

  if [[ ${EV1} -ne 0 || ${EV2} -ne 0 || ${EV3} -ne 0 ]]; then
    echo "${red}Git branchiä ei '${branch}' löytynyt '${dir}' reposta!${normal}"
    echo
    exit
  fi

  # Ei ole muutoksia
  if [[ ${OLD_HEAD} = ${NEWEST_HEAD} ]]; then
    return 0
  else
    return 1
  fi
}

function git_log_update {
  PUPESOFTHASH=$1
  PUPENEXTHASH=$2

  ${mysql_komento} -e "INSERT INTO git_paivitykset SET hash_pupesoft='${PUPESOFTHASH}', hash_pupenext='${PUPENEXTHASH}', ip='${USER_IP}', date=now()" &> /dev/null

  # Siirrytään pupekansioon
  cd ${pupedir}

  # Informoidaan käyttäjiä päivityksestä
  while read -r line
  do
    php pupesoft_changelog.php ${line}
  done < <(${mysql_komento%\-\-verbose} --skip-column-names -B -e "SELECT yhtio FROM yhtion_parametrit where changelog_email != ''" 2> /dev/null)
}

####################################################################################################
#### Preparation ###################################################################################
####################################################################################################

# Get absolute path of pupesoft install dir
pupedir=$(cd $(dirname ${0}) && echo $(pwd))
pupenextdir=/home/devlab/pupenext
salasanat=${pupedir}/inc/salasanat.php
branchfile="/home/devlab/pupe_branch"
branchfilepupenext="/home/devlab/pupenext_branch"
environment="production"
jatketaan=
bundle=false
hosti=$(hostname)
underline=$(tput -Txterm-color smul)
nounderline=$(tput -Txterm-color rmul)
green=$(tput -Txterm-color setaf 2)
red=$(tput -Txterm-color setaf 1)
white=$(tput -Txterm-color setaf 7)
normal=$(tput -Txterm-color sgr0)

# Logataan mitä päivitettiin ja mihin versioon
PUPESOFT_NEWHASH=""
PUPENEXT_NEWHASH=""
PUPESOFT_STATUS=0
PUPENEXT_STATUS=0
BUNDLER_STATUS=0

# Poikkeavan pupenext hakemiston voi antaa PUPENEXT_DIR environment muuttujassa
if [[ -n "${PUPENEXT_DIR}" ]]; then
  pupenextdir=${PUPENEXT_DIR}
fi

# Poikkeavan Pupesoft branchfilen voi antaa PUPESOFT_BRANCH_FILE environment muuttujassa
if [[ -n "${PUPESOFT_BRANCH_FILE}" ]]; then
  branchfile=${PUPESOFT_BRANCH_FILE}
fi

# Poikkeavan Pupenext branchfilen voi antaa PUPENEXT_BRANCH_FILE environment muuttujassa
if [[ -n "${PUPENEXT_BRANCH_FILE}" ]]; then
  branchfilepupenext=${PUPENEXT_BRANCH_FILE}
fi

echo
echo "${white}${underline}Tervetuloa ${hosti} Pupesoft-narupalveluun!${nounderline}${normal}"
echo

# Check we have Git
command -v git > /dev/null

if [[ $? != 0 ]]; then
  echo "${red}Git tulee olla asennettuna!${normal}"
  echo
  exit
fi

if [[ "$(whoami)" = "root" ]]; then
  echo "${red}Ei ole suositeltavaa, että ajat tämän root -käyttäjällä!${normal}"
  echo
  exit
fi

# Make sure we have rbenv environment
if [[ -d ~/.rbenv ]]; then
  # Add rbenv to path
  export PATH="$HOME/.rbenv/bin:$PATH"

  # Load rbenv
  eval "$(rbenv init -)"
else
  echo "${red}RBenv tulee olla asennettuna!${normal}"
  echo
  exit
fi

# Check we have Bundler
command -v bundle > /dev/null

if [[ $? != 0 ]]; then
  echo "${red}Bundler tulee olla asennettuna!${normal}"
  echo
  exit
fi

if [[ ! -d ${pupenextdir} ]]; then
  echo "${red}Pupenext asennusta ei löytynyt!${normal}"
  echo
  exit
fi

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

# Check tmp dir
if [ ! -d "${pupenextdir}/tmp" ]; then
  mkdir "${pupenextdir}/tmp"
fi

####################################################################################################
#### Pupesoft ######################################################################################
####################################################################################################

# Onko spessubranchi käytössä?
if [[ -n "${PUPESOFT_BRANCH}" ]]; then
  pupebranch=${PUPESOFT_BRANCH}
elif [[ -f "${branchfile}" && -s "${branchfile}" ]]; then
  pupebranch=$(cat ${branchfile} | tr -d '\n')
else
  pupebranch="master"
fi

# Katsotaan onko git hakemistossa muutoksia
git_repo_uptodate ${pupedir} ${pupebranch}

# Skipataan, jos ei ole muutoksia
if [[ $? -eq 0 ]]; then
  jatketaanko="skip"
elif [[ "${jatketaan}" = "auto" || "${jatketaan}" = "autopupe" ]]; then
  jatketaanko="k"
else
  echo "${green}Uudempi Pupesoft versio saatavilla!${normal}"
  echo
  echo -n "${white}Päivitetäänkö Pupesoft (k/e)? ${normal}"
  read jatketaanko

  # Päivitetään luettu arvo myös jatketaan-muuttujaan, niin pupenext seuraa käsi kädessä
  jatketaan=$jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then
  # Update app with git
  cd ${pupedir} &&
  git checkout . &&                 # revertataan kaikki local muutokset
  git checkout ${pupebranch} &&     # varmistetaan, etta on master branchi kaytossa
  git pull origin ${pupebranch} &&  # paivitetaan master branchi
  git remote prune origin           # poistetaan ylimääriset branchit

  # Save git exit status
  PUPESOFT_STATUS=$?

  if [[ ${PUPESOFT_STATUS} -eq 0 ]]; then
    if [ -n "$SSH_CLIENT" ] || [ -n "$SSH_TTY" ]; then
      USER_IP=$(who -m am i|awk '{ print $NF}'|sed -e 's/[\(\)]//g')
    else
      USER_IP='localhost'
    fi

    # Get new head
    PUPESOFT_NEWHASH=$(git rev-parse HEAD)
  fi

  if [[ ${PUPESOFT_STATUS} -eq 0 ]]; then
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
elif [[ "${jatketaanko}" = "skip" ]]; then
  echo "${green}Pupesoft ajantasalla, ei päivitettävää!${normal}"
else
  echo "${red}Pupesoftia ei päivitetty!${normal}"
fi

echo

####################################################################################################
#### Pupenext ######################################################################################
####################################################################################################

# Setataan rails env
export RAILS_ENV=${environment}

# Onko spessubranchi käytössä?
if [[ -n "${PUPENEXT_BRANCH}" ]]; then
  pupenextbranch=${PUPENEXT_BRANCH}
elif [[ -f "${branchfilepupenext}" && -s "${branchfilepupenext}" ]]; then
  pupenextbranch=$(cat ${branchfilepupenext} | tr -d '\n')
else
  pupenextbranch="master"
fi

# Katsotaan onko git hakemistossa muutoksia
git_repo_uptodate ${pupenextdir} ${pupenextbranch}

# Skipataan, jos ei ole muutoksia
if [[ $? -eq 0 ]]; then
  jatketaanko="skip"
elif [[ "${jatketaan}" = "auto" || "${jatketaan}" = "autopupe" ]]; then
  jatketaanko="k"
elif [[ -n ${jatketaan} ]]; then
  # Jos ollaan kysytty jo ylempänä, otetaan siitä vastaus
  jatketaanko=$jatketaan
else
  echo "${green}Uudempi Pupenext versio saatavilla!${normal}"
  echo
  echo -n "${white}Päivitetäänkö Pupenext (k/e)? ${normal}"
  read jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then
  # Update app with git
  cd "${pupenextdir}" &&
  git checkout . &&
  git checkout ${pupenextbranch} &&
  git pull origin ${pupenextbranch} &&
  git remote prune origin

  # Save git exit status
  PUPENEXT_STATUS=$?

  # Päivitys onnistui, bundlataan
  if [[ ${PUPENEXT_STATUS} -eq 0 ]]; then
    bundle=true

    # Get new head
    PUPENEXT_NEWHASH=$(git rev-parse HEAD)
  else
    echo "${red}Pupenext päivitys epäonnistui!${normal}"
  fi
fi

# Jos meillä on todella vanha Linux, pitää mennä vanhalla therubyracer versiolla
if [[ -f "/home/devlab/legacy_mode" ]]; then
  sed -i "s/ *gem 'therubyracer'$/  gem 'therubyracer', '~> 0.11.0'/" "${pupenextdir}/Gemfile"
fi

# Run bundle + rake
if [[ ${bundle} = true ]]; then
  # Katsotaan onko meillä Gemfile.lockissa mainittu bundler versio
  bundled_with=$(tail -1 ${pupenextdir}/Gemfile.lock | egrep -o '[0-9].+$')
  bundler_version=$(bundle -v | egrep -o '[0-9].+$')

  # Päivitetään bundler oikeaan versioon
  if [[ -n "${bundled_with}" && "${bundler_version}" != "${bundled_with}" ]]; then
    gem install bundler -v ${bundled_with}
    gem cleanup bundler
    rbenv rehash
  fi

  # Bundlataan Pupenext, kirjoitetaan CSS, käännetään ja putsatan assetsit
  cd "${pupenextdir}" &&
  (bundle check || bundle install) &&
  bundle clean &&
  bundle exec rake css:write &&
  bundle exec rake assets:precompile &&
  bundle exec rake assets:clean &&

  # Restart rails App
  touch "${pupenextdir}/tmp/restart.txt" &&
  chmod 777 "${pupenextdir}/tmp/restart.txt" &&

  # Write cron file (Skip this if we are updating a demo Pupesoft)
  DEMO=$(echo ${pupenextdir} | grep asiakasdemot)

  if [ -z $DEMO ]; then
    bundle exec whenever --update-crontab
  fi

  # Restart Resque workers
  bundle exec rake resque:stop_workers &&
  RAILS_ENV=${environment} TERM_CHILD=1 BACKGROUND=yes QUEUES=* bundle exec rake resque:work

  # Save bundle/rake exit status
  BUNDLER_STATUS=$?

  if [[ ${BUNDLER_STATUS} -eq 0 ]]; then
    # try loading rails app, head request only, wait 1 sec
    curl -m 1 -I "https://$(hostname)/pupenext" &> /dev/null

    echo "${green}Pupenext päivitetty!${normal}"
  else
    echo "${red}Pupenext päivitys epäonnistui!${normal}"
  fi
elif [[ "${jatketaanko}" = "skip" ]]; then
  echo "${green}Pupenext ajantasalla, ei päivitettävää!${normal}"
else
  echo "${red}Pupenextiä ei päivitetty!${normal}"
fi

# Jos jompi kumpi päivitettiin, niin tallennetaan kantaan
if [[ $PUPESOFT_NEWHASH ]] || [[ $PUPENEXT_NEWHASH ]]; then
  git_log_update "$PUPESOFT_NEWHASH" "$PUPENEXT_NEWHASH"
fi

echo

####################################################################################################
#### Database changes ##############################################################################
####################################################################################################

cd "${pupenextdir}"
muutokset=$(cd "${pupenextdir}" && bundle exec rake db:migrate:status | grep '^\s*down\|Migration ID')
echo "${muutokset}" | grep 'down' &> /dev/null

if [[ $? -eq 1 ]]; then
  echo "${green}Tietokanta ajantasalla, ei päivitettävää!${normal}"
else
  echo "${green}Tarvittavat tietokantamuutokset: ${normal}"
  echo
  echo "${muutokset}"
  echo

  if [[ "${jatketaan}" = "autopupe" ]]; then
    jatketaanko="e"
  elif [[ "${jatketaan}" = "auto" ]]; then
    jatketaanko="k"
  else
    echo -n "${white}Tehdäänkö tietokantamuutokset (k/e)? ${normal}"
    read jatketaanko
    echo
  fi

  if [[ "$jatketaanko" = "k" ]]; then
    (bundle check || bundle install) &&
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
echo
