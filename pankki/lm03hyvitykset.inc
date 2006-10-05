<?php
if (count($tilinoarray[$yritirow['tunnus']]) > 0) {
	foreach ($tilinoarray[$yritirow['tunnus']] as $hyvitystilino) {
		$query = "SELECT maksu_tili,
			 left(concat_ws(' ', lasku.nimi, nimitark),30),
			 left(concat_ws(' ', osoite, osoitetark),20),
			 left(concat_ws(' ', postino, postitp),20),
			 summa, valkoodi, viite, viesti, tilinumero,
			 lasku.tunnus, sisviesti2, yriti.tilino, alatila, kasumma
		  FROM lasku, yriti
		  WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' and
			maakoodi = 'fi' and
			yriti.tunnus = maksu_tili and
			yriti.yhtio = lasku.yhtio and
			maksaja = '$kukarow[kuka]' and
			tilinumero='$hyvitystilino' and
			maksu_tili='$yritirow[tunnus]' and
			olmapvm = '$pvmrow[olmapvm]'
		  ORDER BY summa desc";
		
		$xresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($xresult) == 0) {
			echo "<font class='message'>".t("Sopivia laskuja ei löydy. This is bad, very bad")."!</font><br>";
			exit;
		}
		$yritystilino=''; // Näin tunnistamme ensimmäisen maksun
		while ($laskurow=mysql_fetch_array ($xresult)) {
			 	if ($yritystilino == '') { // Muutamme hieman ensimmäisen maksun tietoja
				//Viitenroa ei sallita
				$laskurow[6] =  '';
				$laskutapahtuma = '10'; //Maksutapahtuma
				$laskurow[12] = ''; //Ei käteisalennusta
				$laskurow[4] = $summaarray[$yritirow['tunnus']] [$laskurow['tilinumero']];
				mysql_data_seek($xresult,0);
			}
			else {
				$laskutapahtuma = '20'; //Erittely
				if ($laskurow[4] < 0) { //Se on nyt hyvitystä!
					$laskurow[4] = $laskurow[4] * -1;
					$laskurow[13] = $laskurow[13] * -1;
					$laskutapahtuma = '22'; //Hyvityserittely
				}
			}			
			$yritystilino =  $laskurow[11];
			$laskunimi1 = $laskurow[1];
			$laskunimi2 = $laskurow[2];
			$laskunimi3 = $laskurow[3];
			if ($laskurow[12] == 'K') { // maksetaan käteisalennuksella
				$laskusumma = $laskurow[4] - $laskurow[13];
			}
			else {
				$laskusumma = $laskurow[4];
			}
			$laskutilno = $laskurow[8];
			$laskusis1  = $laskurow[9];
			$laskusis2  = $laskurow[10];
			$laskuvaluutta = 1;
			 	$laskutyyppi = 5;
			 	$laskuviesti = $laskurow[7];
			 	if (strlen($laskurow[6]) > 0) {
				$laskuviesti = $laskurow[6];
			 		$laskutyyppi = 1;
			 	}
			require "lm03rivi.inc";
			if ($laskutapahtuma == '10') {
				$makskpl += 1;
				$makssumma += $laskusumma;
			}
			
			$query = "UPDATE lasku SET tila = 'Q'
						          WHERE tunnus=$laskurow[tunnus]";
			$uresult = mysql_query($query) or pupe_error($query);
		}
	}
}
