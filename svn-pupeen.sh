#!/bin/bash

echo
echo "Tervetuloa Pupesoft-narupalveluun"
echo "---------------------------------"
echo
echo -n "Haetaan tietokantamuutokset.."

pupedir=`dirname $0`
dumppi=`php ${pupedir}/dumppaa_mysqlkuvaus.php komentorivilta`

if [ -z "${dumppi}" ]; then
	echo " Tietokanta ajantasalla!"
	echo
else
	echo " Muutoksia loytyi!"
	echo
	echo -e ${dumppi}
	echo
	echo "HUOM: Tee tarvittavat tietokantamuutoket ennen kuin jatkat!"
	echo
fi

echo -n "Jatketaanko (k/e)? "
read jatketaanko
echo

if [ ${jatketaanko} = "k" ]; then
	echo "Paivitetaan Pupesoft..."
	echo
	cd ${pupedir}
	git checkout .             # revertataan kaikki local muutokset
	git pull origin master     # paivitetaan aina varmasti master branchi
else
	echo "Pupesoftia ei paivitetty!"
fi

echo
echo "Valmis!"
echo

###################################################################################
# Nain luodaan Pupe-installaatio:
# mkdir -p /var/www/html/pupesoft
# git clone git@github.com:devlab-oy/pupesoft.git /var/www/html/pupesoft/
###################################################################################