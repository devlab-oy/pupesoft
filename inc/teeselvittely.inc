<?php

	$vientiselite = str_replace ("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
	$tpv = substr($pvm,0,2);
	$tpk = substr($pvm,2,2);
	$tpp = substr($pvm,4,2);

	if ($tpv < 1000) $tpv += 2000;

	// Onko jotain kirjattavaa (t‰m‰ toimii jopa vastaviennin tapauksessa)?
	if (round($maara,2) != 0) {

		// Kirjoitetaan lasku, jos sellaista ei ole viel‰ tehty
		if ($laskuid == 0) {

			$query = "	INSERT into lasku set
						yhtio = '$yritirow[yhtio]',
						tapvm = '$tpv-$tpk-$tpp',
						tila = 'X',
						laatija = 'tiliote',
						luontiaika = now()";

			$result = mysql_query($query) or pupe_error($query);
			$laskuid = mysql_insert_id ($link);
			echo "<font class='message'>*** ".t("Tositteen otsikko luotu")." ***</font><br>";
		}

		// Tehd‰‰n vastakirjaus
		if ($tkesken == 0 and $laskuid != 0) {
			$query = "	INSERT into tiliointi set
		                yhtio = '$yritirow[yhtio]',
		                ltunnus = '$laskuid',
		                tilino = '$yritirow[oletus_rahatili]',
		                kustp = '',
		                tapvm = '$tpv-$tpk-$tpp',
		                summa = '$vastavienti',
		                vero = '0',
		                lukko = '',
						selite = 'Vastavienti rahatilille',
		                laatija = 'tiliote',
		                laadittu = now()";
			$result = mysql_query($query) or pupe_error($query);
			$vastavienti = 0;
			$laskuid = 0;

			echo "<font class='message'>*** ".t("Vastavienti luotu")." ($yritirow[oletus_rahatili]) ***</font><br>";
		}

		// Tehd‰‰n normaali kirjaus
		if ($laskuid != 0) {
			unset($tiliointisaantorow);
			require "tiliotesaannot.inc";

			// Luottokunta tai vastaava jaettava
			if ($tiliointisaantorow['tilino2'] != 0) {

				$luottokuntatieto = str_replace(".", "", $luottokuntatieto);
				$luottokuntatieto = str_replace(",", ".", $luottokuntatieto);
				$kanta = (float) substr($luottokuntatieto,0,strpos($luottokuntatieto, " "));
				$palkkio = (float) trim(substr($luottokuntatieto,-1*strpos($luottokuntatieto, " ")));

				$query = "INSERT into tiliointi set
			                yhtio = '$yritirow[yhtio]',
			                ltunnus = '$laskuid',
			                tilino = '$tiliointisaantorow[tilino2]',
			                kustp = '$tiliointisaantorow[kustp2]',
			                tapvm = '$tpv-$tpk-$tpp',
			                summa = $palkkio * -1,
			                vero = '0',
			                lukko = '',
							selite = '$vientiselite',
			                laatija = 'tiliote',
			                laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

				$query = "INSERT into tiliointi set
			                yhtio = '$yritirow[yhtio]',
			                ltunnus = '$laskuid',
			                tilino = '$tiliointisaantorow[tilino]',
			                kustp = '$tiliointisaantorow[kustp]',
			                tapvm = '$tpv-$tpk-$tpp',
			                summa = $kanta * -1,
			                vero = '0',
			                lukko = '',
							selite = '$vientiselite',
			                laatija = 'tiliote',
			                laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

			}
			else {
				$query = "INSERT into tiliointi set
		                yhtio = '$yritirow[yhtio]',
		                ltunnus = '$laskuid',
		                tilino = '$tiliointisaantorow[tilino]',
		                kustp = '$tiliointisaantorow[kustp]',
		                tapvm = '$tpv-$tpk-$tpp',
		                summa = $maara * -1,
		                vero = '0',
		                lukko = '',
						selite = '$vientiselite',
		                laatija = 'tiliote',
		                laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}

	        // Merkataan t‰m‰ kirjaus tiliotteelle
	        $query= "UPDATE tiliotedata SET kasitelty = now(), tiliointitunnus=" . mysql_insert_id ($link) . " WHERE tunnus='$tiliotedataid'";
			$dummyresult = mysql_query($query) or pupe_error($query);

			// Merkataan k‰sitellyiksi & lis‰t‰‰n linkki
			$vastavienti += $maara;

			echo "<font class='message'>*** ".t("tiliˆinti luotu")." ($tiliotesaantotilino) ***</font><br>";
		}
	}

?>
