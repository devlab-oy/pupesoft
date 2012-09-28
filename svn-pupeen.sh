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

if [ -z "$dumppi" ]; then
	echo "Tietokanta ajantasalla!"
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

	echo -n "Tehdaanko tietokantamuutokset (k/e)? "
	read jatketaanko

	if [ "$jatketaanko" = "k" ]; then
		while read line
		do
			if [ -n "$line" ]; then
				eval $line
			fi
		done < "/tmp/_mysqlkuvaus.tmp"

		echo -n "Tietokantamuutokset tehty!"
		echo
	else
		echo -n "Tietokantamuutoksia ei tehty!"
		echo
	fi

	rm -f /tmp/_mysqlkuvays.sql
	rm -f /tmp/_mysqlkuvays.tmp
fi

echo -n "Paivitetaanko Pupesoft (k/e)? "
read jatketaanko

if [ "$jatketaanko" = "k" ]; then
	cd $pupedir
	git checkout .             			# revertataan kaikki local muutokset
	git fetch							# haetaan kaikki data
	git checkout sami/optiscan_tulostus		# varmistetaan, etta on master branchi kaytossa
	git pull origin sami/optiscan_tulostus	# paivitetaan master branchi
	echo "Pupesoft paivitetty!"
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