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

# Get absolute path of pupesoft install dir
pupedir=$(cd $(dirname ${0}) && echo $(pwd)/$line)
pupenextdir=${pupedir}/pupenext
salasanat=${pupedir}/inc/salasanat.php
environment="production"
jatketaan=
bundle=false

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

####################################################################################################
#### Pupesoft ######################################################################################
####################################################################################################

# Do git fetch to get status from origin
cd ${pupedir}
git fetch origin > /dev/null

# Onko spessubranchi käytössä?
if [[ -f "${branchfile}" && -s "${branchfile}" ]]; then
  pupebranch=$(cat ${branchfile} | tr -d '\n')
else
  pupebranch="master"
fi

# Get latest commit from local branch
OLD_HEAD=$(cd "${pupedir}" && git log -n 1 ${pupebranch} --pretty=format:"%H")

# Get latest commit from origin branch
NEWEST_HEAD=$(cd "${pupedir}" && git log -n 1 origin/${pupebranch} --pretty=format:"%H")

# Skipataan, jos ei ole muutoksia
if [[ ${OLD_HEAD} = ${NEWEST_HEAD} ]]; then
  jatketaanko="skip"
elif [[ "${jatketaan}" = "auto" || "${jatketaan}" = "autopupe" ]]; then
  jatketaanko="k"
else
  echo -n "${white}Päivitetäänkö Pupesoft (k/e)? ${normal}"
  read jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then
  branchfile="/home/devlab/pupe_branch"

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

  if [[ ${STATUS} -eq 0 ]]; then
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
  echo "${green}Pupesoft ajantasalla, ei pävitettävää!${normal}"
else
  echo "${red}Pupesoftia ei päivitetty!${normal}"
fi

####################################################################################################
#### Pupenext ######################################################################################
####################################################################################################

# Setataan rails env
export RAILS_ENV=${environment}

# Do git fetch to get status from origin
cd ${pupenextdir}
git fetch origin > /dev/null

# Get latest commit from local branch
OLD_HEAD=$(cd "${pupenextdir}" && git log -n 1 master --pretty=format:"%H")

# Get latest commit from origin branch
NEWEST_HEAD=$(cd "${pupenextdir}" && git log -n 1 origin/master --pretty=format:"%H")

# Skipataan, jos ei ole muutoksia
if [[ ${OLD_HEAD} = ${NEWEST_HEAD} ]]; then
  jatketaanko="skip"
elif [[ ! -z "${jatketaan}" && ("${jatketaan}" = "auto" || "${jatketaan}" = "autopupe") ]]; then
  jatketaanko="k"
else
  echo
  echo "HUOM: Tietokantamuutosten haku vaatii Pupenextin päivittämisen!"
  echo -n "${white}Päivitetäänkö Pupenext (k/e)? ${normal}"
  read jatketaanko
fi

if [[ "${jatketaanko}" = "k" ]]; then
  # Update app with git
  cd "${pupenextdir}" &&
  git fetch origin &&
  git checkout . &&
  git checkout master &&
  git pull origin master &&
  git remote prune origin

  # Save git exit status
  STATUS=$?

  # Check tmp dir
  if [ ! -d "${pupenextdir}/tmp" ]; then
    mkdir "${pupenextdir}/tmp"
  fi

  echo

  # Päivitys onnistui, bundlataan
  if [[ ${STATUS} -eq 0 ]]; then
    bundle=true
  else
    echo "${red}Pupenext päivitys epäonnistui!${normal}"
  fi
fi

# Run bundle + rake
if [[ ${bundle} = true ]]; then
  cd "${pupenextdir}" &&
  bundle --quiet &&
  bundle exec rake css:write &&
  bundle exec rake assets:precompile &&

  # Restart rails App
  touch "${pupenextdir}/tmp/restart.txt" &&
  chmod 777 "${pupenextdir}/tmp/restart.txt" &&

  # Restart Resque workers
  bundle exec rake resque:stop_workers &&
  TERM_CHILD=1 BACKGROUND=yes QUEUES=* bundle exec rake resque:work

  # Save bundle/rake exit status
  STATUS=$?

  if [[ ${STATUS} -eq 0 ]]; then
    echo "${green}Pupenext päivitetty!${normal}"
  else
    echo "${red}Pupenext päivitys epäonnistui!${normal}"
  fi
elif [[ "${jatketaanko}" = "skip" ]]; then
  echo "${green}Pupenext ajantasalla, ei päivitettävää!${normal}"
else
  echo "${red}Pupenextiä ei päivitetty!${normal}"
fi

####################################################################################################
#### Database changes ##############################################################################
####################################################################################################

cd "${pupenextdir}"
muutokset=$(bundle exec rake db:migrate:status | grep 'down\|Migration ID')
echo "${muutokset}" | grep 'down'

if [[ $? -eq 1 ]]; then
  echo "${green}Tietokanta ajantasalla, ei päivitettävää!${normal}"
else
  echo
  echo "${green}Tarvittavat tietokantamuutokset: ${normal}"

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
echo
