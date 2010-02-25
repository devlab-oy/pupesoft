<?php

	if (count($tilinoarray[$yritirow['tunnus']]) > 0) {

		foreach ($tilinoarray[$yritirow['tunnus']] as $hyvitystilino) {

			$query = "	SELECT maksu_tili,
				 		left(concat_ws(' ', lasku.nimi, nimitark),30) nimi,
				 		left(concat_ws(' ', osoite, osoitetark),20) osoite,
				 		left(concat_ws(' ', postino, postitp),20) postitp,
				 		summa, lasku.valkoodi, viite, viesti,
						tilinumero, lasku.tunnus, sisviesti2,
						yriti.tilino ytilino, alatila, kasumma
			  			FROM lasku, yriti
			  			WHERE lasku.yhtio 	= '$kukarow[yhtio]'
						and tila 			= 'P'
						and maa 			= '$kotimaa'
						and yriti.tunnus 	= maksu_tili
						and yriti.yhtio 	= lasku.yhtio
						and maksaja 		= '$kukarow[kuka]'
						and tilinumero		= '$hyvitystilino'
						and maksu_tili		= '$yritirow[tunnus]'
						and olmapvm 		= '$pvmrow[olmapvm]'
			  			ORDER BY summa desc";
			$xresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($xresult) == 0) {
				echo "<font class='message'>".t("Sopivia laskuja ei löydy. This is bad, very bad")."!</font><br>";
				exit;
			}

			// Näin tunnistamme ensimmäisen maksun
			$yritystilino = '';

			while ($laskurow = mysql_fetch_array ($xresult)) {
				// Muutamme hieman ensimmäisen maksun tietoja
				if ($yritystilino == '') {
					$laskutapahtuma = '10';			// Maksutapahtuma (netotettu)
					$laskurow["viite"] = '';		// Viitenroa ei sallita netotetulla tapahtumalla
					$laskurow["alatila"] = '';		// Ei käteisalennusta netotetulla tapahtumalla
					$laskurow["summa"] = $summaarray[$yritirow['tunnus']][$laskurow['tilinumero']];
					mysql_data_seek($xresult, 0);
				}
				else {
					$laskutapahtuma = '20';	// Erittelytapahtuman lasku

					// Lasku on hyvitystä
					if ($laskurow["summa"] < 0) {
						$laskurow["summa"] = $laskurow["summa"] * -1;
						$laskurow["kasumma"] = $laskurow["kasumma"] * -1;
						$laskutapahtuma = '22'; // Erittelytapahtuman hyvityslasku
					}
				}

				$yritystilino = $laskurow["ytilino"];
				$laskunimi1 = $laskurow["nimi"];
				$laskunimi2 = $laskurow["osoite"];
				$laskunimi3 = $laskurow["postitp"];

				// Maksetaan käteisalennuksella
				if ($laskurow["alatila"] == 'K') {
					$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
				}
				else {
					$laskusumma = $laskurow["summa"];
				}

				$laskutilno    = $laskurow["tilinumero"];
				$laskusis1     = $laskurow["tunnus"];
				$laskusis2     = $laskurow["sisviesti2"];
			 	$laskuviesti   = $laskurow["viesti"];
				$laskuvaluutta = 1;
				$laskutyyppi   = 5; // Laskuviesti

				// Laskuviesti:
				// 1 = viitenumero
				// 2 = laskun tiedot
				// 5 = 70 merkin pituinen vapaa viesti
				// 6 = pitkä viesti (oma tietue)
				// 7 = veroviesti (max. 70 merkkiä)

				//Sampo/Danske haluaa viestiin ainakin yhden merkin
				if (substr($laskurow['ytilino'], 0, 1) == '8' and strlen(trim($laskuviesti)) == 0) $laskuviesti = ';';

				if (strlen($laskurow["viite"]) > 0) {
					$laskuviesti = sprintf ('%020s', $laskurow["viite"]); // Etunollatäyttö
					$laskutyyppi = 1;
				}

				require ("inc/lm03rivi.inc");

				if ($laskutapahtuma == '10') {
					$makskpl += 1;
					$makssumma += $laskusumma;
				}

				// jos halutaan "pitkä viesti" (tänne ei toistaiseksi voi joutua mitenkään)
				if ($laskutyyppi == 6) {
					$viestitietue_viesti_1 = $laskutilno;			// Sampon speksissä pakollista laittaa tähän saajan tilinumero
					$viestitietue_viesti_2 = $laskurow["viite"];	// Viitenumero
					$viestitietue_viesti_3 = $laskurow["viesti"];	// Viesti
					$viestitietue_viesti_4 = "";
					$viestitietue_viesti_5 = "";

					require ("inc/lm03viesti.inc");
				}

				$query = "	UPDATE lasku
							SET tila = 'Q'
							WHERE tunnus = $laskurow[tunnus]";
				$uresult = mysql_query($query) or pupe_error($query);
			}
		}
	}

?>
