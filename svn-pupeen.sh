#!/bin/bash

hosti=`hostname`

echo
echo "Tervetuloa ${hosti} Pupesoft-narupalveluun"
echo "---------------------------------"
echo
echo "Haetaan tietokantamuutokset.."

pupedir=`dirname $0`

# Tutkitaan tietokantarakenne...
dumppi=`php ${pupedir}/dumppaa_mysqlkuvaus.php komentorivilta`

if [ -z "${dumppi}" ]; then
	echo " Tietokanta ajantasalla!"
	echo
	rm -f /tmp/_mysqlkuvays.sql
else

	echo -e $dumppi > /tmp/_mysqlkuvaus.tmp

	while read line
	do
		if [ -n "$line" ]; then
			echo $line

			echo -n "Tehdaanko muutos (k/e)? "
			read jatketaanko </dev/tty

			if [ $jatketaanko = "k" ]; then
				eval $line
				echo -n "Tietokantamuutos tehty!"
				echo
			else
				echo -n "Tietokantamuutosta ei tehty!"
				echo
			fi
		fi
	done < "/tmp/_mysqlkuvaus.tmp"

	rm -f /tmp/_mysqlkuvays.sql
	rm -f /tmp/_mysqlkuvays.tmp
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
# git clone git://github.com/devlab-oy/pupesoft.git /var/www/html/pupesoft/
###################################################################################