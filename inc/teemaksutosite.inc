<?php

	// Kirjoitetaan laskulle maksutiliöinti
	$selite = str_replace ("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
	$tpv 	= substr($pvm,0,2);
	$tpk 	= substr($pvm,2,2);
	$tpp 	= substr($pvm,4,2);
	if ($tpv < 1000) $tpv += 2000;

		// Mikä on oikea ostovelkatili
		$query = "	SELECT tilino
					FROM tiliointi
					WHERE ltunnus 	= '$trow[tunnus]'
					and yhtio 		= '$yritirow[yhtio]'
					and tapvm 		= '$trow[tapvm]'
					and abs(summa + $trow[vietysumma]) <= 0.02
					and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
					and korjattu 	= ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Ostovelkatilin määrittely epäonnistui")."!</font>$query<br>";
			exit;
		}

		$ostovelkarow = mysql_fetch_array($result);

		// Kassa-ale
		if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {
			// Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
			echo "<font class='message'>".t("Kirjaan kassa-alennusta yhteensä")." $trow[vietykasumma]</font><br>";

			$totkasumma = 0;

			$query = "	SELECT *
						FROM tiliointi
						WHERE ltunnus 	= '$trow[tunnus]'
						and yhtio 		= '$yritirow[yhtio]'
						and tapvm 		= '$trow[tapvm]'
						and tilino not in ('$yhtiorow[ostovelat]','$yhtiorow[alv]','$yhtiorow[varasto]','$yhtiorow[varastonmuutos]', '$yhtiorow[matkalla_olevat]','$yhtiorow[konserniostovelat]','$yhtiorow[kassaale]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]')
						and korjattu 	= ''";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) == 0) {
				echo "<font class='error'>".t("Laskulla ei ole ostovelkatiliöintiä. Tämä on systeemivirhe")."!</font><br>";
				exit;
			}

			while ($tiliointirow = mysql_fetch_array ($yresult)) {
				// Kuinka paljon on tämän viennin osuus
				$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma'] * $trow['vietykasumma'],2);
				$alv = 0;

				echo "<font class='message'>".t("Kirjaan kassa-alennusta")." $summa</font><br>";

				if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
					$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
					$summa -= $alv;
				}

				$totkasumma += $summa + $alv;

				$query = "	INSERT into tiliointi set
							yhtio 		= '$yritirow[yhtio]',
							ltunnus 	= '$trow[tunnus]',
							tilino 		= '$yhtiorow[kassaale]',
							kustp 		= '$tiliointirow[kustp]',
							kohde 		= '$tiliointirow[kohde]',
							projekti 	= '$tiliointirow[projekti]',
							tapvm 		= '$tpv-$tpk-$tpp',
							summa 		= $summa * -1,
							vero 		= '$tiliointirow[vero]',
							lukko 		= '',
							laatija 	= 'tiliote',
							laadittu 	= now()";
				$xresult = mysql_query($query) or pupe_error($query);
				$isa = mysql_insert_id ($link); // Näin löydämme tähän liittyvät alvit....

				if ($tiliointirow['vero'] != 0) {

					$query = "	INSERT into tiliointi set
								yhtio 		= '$yritirow[yhtio]',
								ltunnus 	= '$trow[tunnus]',
								tilino 		= '$yhtiorow[alv]',
								tapvm 		= '$tpv-$tpk-$tpp',
								summa 		= $alv * -1,
								vero 		= '',
								selite 		= '$selite',
								lukko 		= '1',
								laatija 	= 'tiliote',
								laadittu 	= now(),
								aputunnus 	= $isa";
					$xresult = mysql_query($query) or pupe_error($query);
				}
			}

			//Hoidetaan mahdolliset pyöristykset
			if ($totkasumma != $trow['vietykasumma']) {
				echo "<font class='message'>".t("Joudun pyöristämään kassa-alennusta")."</font><br>";

				$query = "	UPDATE tiliointi
							SET summa = summa - $totkasumma + $trow[vietykasumma]
							WHERE tunnus = '$isa'
							and yhtio 	 = '$yritirow[yhtio]'";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}

		// Valuutta-ero
		if ($trow['vienti_kurssi'] != $kurssi) {

			$vesumma = ($maara*-1) - $trow['vietysumma'];

			if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {
				$vesumma = round(($maara*-1) - ($trow['vietysumma'] - $trow['vietykasumma']),2);
			}

			echo "<font class='message'>".t("Kirjaan valuuttaeroa yhteensä")." $vesumma</font><br>";

			$totvesumma = 0;

			$query = "	SELECT *
						FROM tiliointi
						WHERE ltunnus	= '$trow[tunnus]'
						and yhtio 		= '$yritirow[yhtio]'
						and tapvm 		= '$trow[tapvm]'
						and tilino not in ('$yhtiorow[ostovelat]','$yhtiorow[alv]','$yhtiorow[varasto]','$yhtiorow[varastonmuutos]','$yhtiorow[matkalla_olevat]','$yhtiorow[konserniostovelat]','$yhtiorow[kassaale]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]')
						and korjattu 	= ''";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) == 0) {
				echo "<font class='error'>".t("Laskulla ei ole ostovelkatiliöintiä. Tämä on systeemivirhe")."!</font><br>";
				exit;
			}

			while ($tiliointirow = mysql_fetch_array ($yresult)) {
				// Kuinka paljon on tämän viennin osuus
				$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma'] * $vesumma,2);

				echo "<font class='message'>".t("Kirjaan valuuttaeroa")." $summa</font><br>";

				if (round($summa,2) != 0) {
					$query = "	INSERT into tiliointi set
								yhtio 		= '$yritirow[yhtio]',
								ltunnus 	= '$trow[tunnus]',
								tilino 		= '$yhtiorow[valuuttaero]',
								kustp 		= '$tiliointirow[kustp]',
								kohde 		= '$tiliointirow[kohde]',
								projekti 	= '$tiliointirow[projekti]',
								tapvm 		= '$tpv-$tpk-$tpp',
								summa 		= $summa,
								vero 		= 0,
								lukko 		= '',
								laatija 	= 'tiliote',
								laadittu 	= now()";
					$xresult = mysql_query($query) or pupe_error($query);
					$isa = mysql_insert_id ($link);

					$totvesumma += $summa;
				}
			}

			//Hoidetaan mahdolliset pyöristykset
			if ($totvesumma != $vesumma) {
				echo "<font class='message'>".t("Joudun pyöristämään valuuttaeroa")."</font><br>";

				$query = "	UPDATE tiliointi
							SET summa = summa - $totvesumma + $vesumma
							WHERE tunnus = '$isa' and yhtio='$yritirow[yhtio]'";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}

		//Rahatili
		$query = "	INSERT into tiliointi set
					yhtio 		= '$yritirow[yhtio]',
					ltunnus 	= '$trow[tunnus]',
					tilino 		= '$yritirow[oletus_rahatili]',
					kustp 		= '',
					tapvm 		= '$tpv-$tpk-$tpp',
					summa 		= '$maara',
					vero 		= '0',
					lukko 		= '',
					laatija 	= 'tiliote',
					laadittu 	= now()";
		$result = mysql_query($query) or pupe_error($query);

		//Ostovelat
		$query = "	INSERT into tiliointi set
					yhtio 		= '$yritirow[yhtio]',
					ltunnus 	= '$trow[tunnus]',
					tilino		= '$ostovelkarow[tilino]',
					kustp 		= '',
					tapvm 		= '$tpv-$tpk-$tpp',
					summa 		= '$trow[vietysumma]',
					vero 		= '0',
					lukko 		= '',
					laatija 	= 'tiliote',
					laadittu 	= now()";
		$result = mysql_query($query) or pupe_error($query);

    $query = "UPDATE tiliotedata SET kasitelty = now(), tiliointitunnus=" . mysql_insert_id ($link) . " WHERE tunnus='$tiliotedataid'";
	$dummyresult = mysql_query($query) or pupe_error($query);

	// Merkataan käsitellyiksi & lisätään linkki
	echo "<font class='message'>*** ".t("laskuun lisätty maksutiliöinti")." ***</font><br>";

	//Lasku maksetuksi
	$kurssi += 0;

	if ($kurssi == 0) {
		$kurssi = 1;
	}

	$query = "	UPDATE lasku set tila = 'Y', maksu_kurssi = $kurssi, mapvm = '$tpv-$tpk-$tpp'
				WHERE tunnus = '$trow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	echo "<font class='message'>*** ".t("lasku suoritettu")." ***</font><br>"

?>