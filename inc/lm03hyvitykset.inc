<?php

	if (count($tilinoarray[$yritirow['tunnus']]) > 0) {

		foreach ($tilinoarray[$yritirow['tunnus']] as $hyvitystilino) {

			// haetaan kaikki laskut tälle tilille, ensin kaikki miinukset sitten summan mukaan desc
			$query = "	SELECT maksu_tili,
				 		left(concat_ws(' ', lasku.nimi, nimitark),30) nimi,
				 		left(concat_ws(' ', osoite, osoitetark),20) osoite,
				 		left(concat_ws(' ', postino, postitp),20) postitp,
				 		summa, lasku.valkoodi, viite, viesti,
						tilinumero, lasku.tunnus, sisviesti2,
						yriti.tilino ytilino, alatila, kasumma,
						lasku.tunnus laskutunnus,
						if (lasku.alatila = 'K', summa - kasumma, summa) maksettavasumma
			  			FROM lasku
						JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = maksu_tili)
			  			WHERE lasku.yhtio		= '$kukarow[yhtio]'
						and lasku.tila 			= 'P'
						and lasku.maa 			= '$kotimaa'
						and lasku.maksaja 		= '$kukarow[kuka]'
						and lasku.tilinumero	= '$hyvitystilino'
						and lasku.maksu_tili	= '$yritirow[tunnus]'
						and lasku.olmapvm 		= '$pvmrow[olmapvm]'
			  			ORDER BY if(summa < 0, 1, 2), summa desc";
			$nettolaskures = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nettolaskures) == 0) {
				echo "<font class='message'>".t("Sopivia laskuja ei löydy. This is bad, very bad")."!</font><br>";
				exit;
			}

			// Tällä lasketaan monta laskua tarvitaan mukaan
			$nettosumma_yhteensa = 0;

			// Tänne tallennetaan laskujen tunnukset
			$nettolaskujen_tunnukset = "";

			// Loopataan laskuja läpi, kunnes päästään plussalle
			while ($nettolaskurow = mysql_fetch_array($nettolaskures)) {

				$nettosumma_yhteensa += $nettolaskurow["maksettavasumma"];
				$nettolaskujen_tunnukset .= "$nettolaskurow[laskutunnus],";

				if ($nettosumma_yhteensa > 0) {
					break;
				}
			}

			// Vika pilkku pois
			$nettolaskujen_tunnukset = substr($nettolaskujen_tunnukset, 0, -1);

			// Haetaan netotettavat laskut, tehdään nettotietue vai tarvittavista laskuista
			$query = "	SELECT lasku.maksu_tili,
				 		left(concat_ws(' ', lasku.nimi, lasku.nimitark),30) nimi,
				 		left(concat_ws(' ', lasku.osoite, lasku.osoitetark),20) osoite,
				 		left(concat_ws(' ', lasku.postino, lasku.postitp),20) postitp,
				 		lasku.summa,
						lasku.valkoodi,
						lasku.viite,
						lasku.viesti,
						lasku.laskunro,
						lasku.tilinumero,
						lasku.tunnus,
						lasku.sisviesti2,
						yriti.tilino ytilino,
						lasku.alatila,
						lasku.kasumma,
						lasku.tunnus laskutunnus,
						if (lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) maksettavasumma
			  			FROM lasku
						JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = lasku.maksu_tili)
			  			WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tunnus in ($nettolaskujen_tunnukset)
			  			ORDER BY if(summa < 0, 1, 2), summa desc";
			$xresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($xresult) == 0) {
				echo "<font class='message'>".t("Sopivia laskuja ei löydy. This is bad, very bad")."!</font><br>";
				exit;
			}

			if (mysql_num_rows($xresult) > 99) {
				echo "<font class='message'>".t("Nettoriville tulee yli 100 tapahtumaa, maksuaineisto ei tue näin isoja nettomaksuja")."!</font><br>";
				exit;
			}

			// Näin tunnistamme ensimmäisen maksun
			$yritystilino = "";

			while ($laskurow = mysql_fetch_array ($xresult)) {

				// Muutamme hieman ensimmäisen maksun tietoja
				if ($yritystilino == "") {
					$laskutapahtuma			= '10';	// Maksutapahtuma (netotettu)
					$laskurow["viite"]		= '';	// Viitenroa ei sallita netotetulla tapahtumalla
					$laskurow["alatila"]	= '';	// Ei käteisalennusta netotetulla tapahtumalla
					$laskurow["summa"]		= $nettosumma_yhteensa;
					mysql_data_seek($xresult, 0);
				}
				else {
					$laskutapahtuma = '20';	// Erittelytapahtuman lasku

					// Lasku on hyvitystä
					if ($laskurow["summa"] < 0) {
						$laskurow["summa"]		= $laskurow["summa"] * -1;
						$laskurow["kasumma"]	= $laskurow["kasumma"] * -1;
						$laskutapahtuma			= '22'; // Erittelytapahtuman hyvityslasku
					}
				}

				$yritystilino	= $laskurow["ytilino"];
				$laskunimi1		= $laskurow["nimi"];
				$laskunimi2		= $laskurow["osoite"];
				$laskunimi3		= $laskurow["postitp"];

				// Maksetaan käteisalennuksella
				if ($laskurow["alatila"] == 'K') {
					$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
				}
				else {
					$laskusumma = $laskurow["summa"];
				}

				$laskutilno		= $laskurow["tilinumero"];
				$laskusis1		= $laskurow["tunnus"];
				$laskusis2		= $laskurow["sisviesti2"];
				$laskuvaluutta	= 1;
				$laskutyyppi	= 5; // Laskuviesti
				$laskuviesti	= $laskurow['viesti'];

				if ($laskurow['laskunro'] != 0 and $laskurow['laskunro'] != $laskurow['viesti']) {
	                $laskuviesti = (trim($laskuviesti) == "") ? $laskurow['laskunro'] : $laskuviesti." ".$laskurow['laskunro'];
	            }

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
					$totkpl += 1;
					$makssumma += $laskusumma;
					$totsumma += $laskusumma;
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
							SET tila = 'Q',
							popvm = '$popvm_nyt'
							WHERE tunnus = $laskurow[tunnus]";
				$uresult = mysql_query($query) or pupe_error($query);
			}
		}
	}

?>
