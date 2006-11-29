<?php

	// Luetaan viitemaksuja tietokannasta...
	// Automaattikohdistetaan viitemaksuja...
	// Tehdään kohdistamattomista laskuja...

	#tietuetunnus on ensimmäinen merkki
	# 0 -> erätietue
	# 3 -> viitesiirto
	# 5 -> suoraveloitus
	# 9 -> summatietue
	$type = substr($tietue,0,1);

	if ($type == 0) {
		#print "Tietuetunnus => $type\n";
		#print "Aineston luontipv => ".substr($tietue,1,6)."\n";
		#print "Aineston luontiaika => ".substr($tietue,7,4)."\n";
		#print "Rahalaitostunnus => ".substr($tietue,11,2)."\n";
		#print "Laskuttajan palvelutunnus => ".substr($tietue,13,9)."\n";
		#print "Rahayksikön koodi => ".substr($tietue,22,1)."\n";
		#print "Varalla => ".substr($tietue,23,67)."\n";
	}
	elseif ($type == 3) {

		#viitesiirto
		#print "Oikaisutunnus => ".substr($tietue,87,1)."\n";
		#print "Välitystapa => ".substr($tietue,88,1)."\n";
		#print "Palautekoodi => ".substr($tietue,89,1)."\n";
		$account = substr($tietue,1,14);

		$payer_name = substr($tietue,63,12);

		$reference = ltrim(substr($tietue,43,20), '0');

		$sum = substr($tietue,77,10);
		$sum = $sum/100; #XXX summa on sentteinä, etunollatäytöllä.

		$currency_code = substr($tietue,75,1);

		if ($currency_code != 1) {
			$valkoodi = "MUU";
		}
		else {
			$valkoodi = "EUR";
		}

		# Muodossa vvkkpp
		$entry_date	  = "20".dateconv(substr($tietue,15,6));
		$payment_date = "20".dateconv(substr($tietue,21,6));

		$query = "SELECT yhtio FROM yriti WHERE tilino = '$account'";
		$result = mysql_query($query) or pupe_error($query);
		$company = mysql_fetch_array($result);

		if ($company["yhtio"] == "") {
			echo "<font class='error'>Tilille $account ei löytynyt yritystä.</font><br>";
			next;
		}

		#arkistointitunnus
		$query = "INSERT INTO suoritus (yhtio,tilino,nimi_maksaja,viite,summa,valkoodi,maksupvm,kirjpvm) VALUES ('$company[yhtio]','$account','$payer_name','$reference',$sum,'$valkoodi','$payment_date','$entry_date')";
		$result = mysql_query($query) or pupe_error($query);

	}
	elseif ($type == 9) {
		#print "Tietuetunnus => $type\n";
		#print "Tapahtumien kpl => ".substr($tietue,1,6)."\n";
		#print "Tapahtumien määrä => ".substr($tietue,7,11)."\n";
		#print "Oikaisutapahtumien kpl => ".substr($tietue,18,6)."\n";
		#print "Oikaisutapahtumien määrä => ".substr($tietue,24,11)."\n";
		#print "Varalla => ".substr($tietue,35,5)."\n";
		#print "done, insert/read/error: $insertcnt/$readcnt/$errcnt\n";
	}
	else {
		echo "<font class='error'>Tuntematon tietuetunnus $type</font><br>";
		$errcnt++;
		next;
	}
?>