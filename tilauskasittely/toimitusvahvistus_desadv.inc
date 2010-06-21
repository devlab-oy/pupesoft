<?php

	/**
	* Despatch Advice Messages
	* DESADV D.00A
	* Version 0.2
	* toimitusvahvistus_stockmann.inc
	**/

	// tarvitaan tilauksien tunnukset muuttujassa $otunnukset
	// tarvitaan tilauksen tunnus muuttujassa $tunnus
	// printterin komento muuttujassa $oslapp
	// $kukarow[yhtio] jostain saadaan yhtio
	// $yhtiorow array josta saada lähettäjän tiedot

	// nyt on kaikki tiedot rahtikirjaa varten haettu..
	//
	// arrayt:
	// toitarow, lotsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
	// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
	//
	// muuttujat:
	// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
	// tunnukset - tälle rahtikirjalle kuuluvat tunnukset
	// mehto sisältää maksuehdon tiedot
	// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat


	//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
	if ($kollityht > 0 and $desadv_host != '' and $desadv_user != '' and $desadv_pass != '' and $desadv_path != '') {
		//	Haetaan otsikkotiedot pohjalle
		$query = "	SELECT lasku.*, laskun_lisatiedot.*,
					lasku.tunnus laskutunnus,
					asiakas.nimi asiakasnimi,
					asiakas.nimitark asiakasnimitark,
					asiakas.osoite asiakasosoite,
					asiakas.postitp asiakaspostitp,
					asiakas.postino asiakaspostino,
					asiakas.maa asiakasmaa,
					asiakas.toim_nimi asiakastoim_nimi,
					asiakas.toim_nimitark asiakastoim_nimitark,
					asiakas.ovttunnus asiakasovttunnus,
					asiakas.ovttunnus asiakastoim_ovttunnus,
					asiakas.toim_osoite asiakastoim_osoite,
					asiakas.toim_postitp asiakastoim_postitp,
					asiakas.toim_postino asiakastoim_postino,
					asiakas.toim_maa asiakastoim_maa
					FROM lasku
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus in ($otunnukset)
					ORDER BY lasku.tunnus";
		$desadv_laskures = mysql_query($query) or pupe_error($query);

		while ($desadv_laskurow = mysql_fetch_assoc($desadv_laskures)) {

			$query = "	SELECT *
						FROM rahtikirjat
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus in ($tunnukset)";
			$desadv_rakir_res = mysql_query($query) or pupe_error($query);
			$desadv_rakir_row = mysql_fetch_assoc($desadv_rakir_res);

			$desadv_message = '';
			$error 			= '';
			$x 				= 0; // yhteensä rivejä koko filessä

			// UNH - message header
			$desadv_message .= "UNH+$desadv_laskurow[laskutunnus]+DESADV:D:00A:UN'";
			$x++;

			// BGM (beginning of message) - identification data of despatch advice
			// 351 = despatch advice
			// document identifier? onko tämä laskun tunnus myös?
			$desadv_message .= "BGM+351+$desadv_laskurow[laskutunnus]+9'";
			$x++;

			// DTM - date of despatch advice
			// 0000-00-00 00:00:00
			// 202   YYMMDDHHMMSS

			list($date, $time) = explode(" ", $desadv_rakir_row['tulostettu']);
			list($year, $month, $day) = explode("-", $date);
			list($hour, $minute, $second) = explode(":", $time);
			$desadv_message .= "DTM+137:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 11 = despatch date/time
			$desadv_message .= "DTM+11:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 171 = tilauspäivä
			list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
			list($year, $month, $day) = explode("-", $date);
			list($hour, $minute, $second) = explode(":", $time);
			$desadv_message .= "DTM+171:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 171 = tilauspäivä
			list($date, $time) = explode(" ", $desadv_laskurow['toimaika']);
			list($year, $month, $day) = explode("-", $date);
			$desadv_message .= "DTM+69:$year$month$day:102'";
			$x++;

			// RFF - customer reference number
			// CR = customer reference number
			// The format of this reference number is nnn-llllll, where nnn is Stockmann's merchandise group and llllll is the actual Stockmann's order number
			if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
				$desadv_message .= "RFF+CR:$desadv_laskurow[asiakkaan_tilausnumero]'";
				$x++;
			}
			else {
				$error .= "Asiakkaan tilausnumero puuttui!<br>";
			}

			// RFF - waybill number
			// AAM = waybill number
			$desadv_message .= "RFF+AAM:$desadv_rakir_row[rahtikirjanro]'";
			$x++;

			// RFF - buyer's order number
			// CO = buyer's order number
			if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
				if (strpos($desadv_laskurow['asiakkaan_tilausnumero'], "-") !== FALSE) {
					// Stockmannille explodetaan
					list($group, $order) = explode("-", $desadv_laskurow['asiakkaan_tilausnumero']);
					$desadv_message .= "RFF+CO:$order'";
				}
				else {
					$desadv_message .= "RFF+CO:$desadv_laskurow[asiakkaan_tilausnumero]'";
				}
				$x++;
			}

			// NAD - identification of the seller
			// SE = seller
			// 100 = Finnish EDI identifier
			$desadv_message .= "NAD+SE+$yhtiorow[ovttunnus]:100++$yhtiorow[nimi]+$yhtiorow[osoite]+$yhtiorow[postitp]++$yhtiorow[postino]+$yhtiorow[maa]'";
			$x++;

			// CTA - contact information
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitostunnus = '$desadv_laskurow[liitostunnus]'
						AND nimi = '$desadv_laskurow[tilausyhteyshenkilo]'";
			$yhteyshenkiloresult = mysql_query($query) or pupe_error($query);
			$yhteyshenkiloasiakas = mysql_fetch_assoc($yhteyshenkiloresult);

			$yhteyshenkilo = '';
			$yhteyshenkilo_puh = '';

			if ($yhteyshenkiloasiakas['nimi'] != '') {
				$yhteyshenkilo = $yhteyshenkiloasiakas['nimi'];
				$yhteyshenkilo_puh = $yhteyshenkiloasiakas['puh'] != '' ? $yhteyshenkiloasiakas['puh'] : ($yhteyshenkiloasiakas['gsm'] != '' ? $yhteyshenkiloasiakas['gsm'] : '');
			}
			elseif ($desadv_laskurow['yhteyshenkilo_kaupallinen'] != '') {
				$query = "	SELECT *
							FROM yhteyshenkilo
							WHERE yhteyshenkilo.yhtio = '$kukarow[yhtio]'
							AND tunnus = '$desadv_laskurow[yhteyshenkilo_kaupallinen]'";
				$yhteyshenkilo_kaupallinen_res = mysql_query($query) or pupe_error($query);
				$yhteyshenkilo_kaupallinen_row = mysql_fetch_assoc($yhteyshenkilo_kaupallinen_res);

				$yhteyshenkilo = $yhteyshenkilo_kaupallinen_row['nimi'];
				$yhteyshenkilo_puh = $yhteyshenkilo_kaupallinen_row['puh'] != '' ? $yhteyshenkilo_kaupallinen_row['puh'] : ($yhteyshenkilo_kaupallinen_row['gsm'] != '' ? $yhteyshenkilo_kaupallinen_row['gsm'] : '');
			}
			elseif ($desadv_laskurow['yhteyshenkilo_tekninen'] != '') {
				$query = "	SELECT *
							FROM yhteyshenkilo
							WHERE yhteyshenkilo.yhtio = '$kukarow[yhtio]'
							AND tunnus = '$desadv_laskurow[yhteyshenkilo_tekninen]'";
				$yhteyshenkilo_tekninen_res = mysql_query($query) or pupe_error($query);
				$yhteyshenkilo_tekninen_row = mysql_fetch_assoc($yhteyshenkilo_tekninen_res);

				$yhteyshenkilo = $yhteyshenkilo_tekninen_row['nimi']; // max 35 merkkiä, R (riippuu maasta)
				$yhteyshenkilo_puh = $yhteyshenkilo_tekninen_row['puh'] != '' ? $yhteyshenkilo_tekninen_row['puh'] : ($yhteyshenkilo_tekninen_row['gsm'] != '' ? $yhteyshenkilo_tekninen_row['gsm'] : '');
			}

			// IC = information contact
			if ($yhteyshenkilo != '') {
				$desadv_message .= "CTA+IC+:$yhteyshenkilo'";
				$x++;

				// COM - communication contact
				if ($yhteyshenkilo_puh != '') {
					$desadv_message .= "COM+$yhteyshenkilo_puh:TE'";
					$x++;
				}
				else {
					$yhteyshenkilo_puh = $desadv_laskurow['puhelin'] != '' ? $desadv_laskurow['puhelin'] : ($desadv_laskurow['gsm'] != '' ? $desadv_laskurow['gsm'] : ($desadv_laskurow['tyopuhelin'] != '' ? $desadv_laskurow['tyopuhelin'] : '')); // max 15 merkkiä, C

					if ($yhteyshenkilo_puh != '') {
						$desadv_message .= "COM+$yhteyshenkilo_puh:TE'";
						$x++;
					}
				}
			}

			// NAD - identification of buyer
			if ($desadv_laskurow['asiakasovttunnus'] != '' and $desadv_laskurow['asiakasnimi'] != '') {
				$desadv_message .= "NAD+BY+$desadv_laskurow[asiakasovttunnus]:100++$desadv_laskurow[asiakasnimi] $desadv_laskurow[asiakasnimitark]+$desadv_laskurow[asiakasosoite]+$desadv_laskurow[asiakaspostitp]++$desadv_laskurow[asiakaspostino]+$desadv_laskurow[asiakasmaa]'";
				$x++;
			}
			else {
				$error .= "Asiakkaan ovt-tunnus / nimi puuttui!<br>";
			}

			// NAD - identification of the delivery party
			if ($desadv_laskurow['asiakastoim_nimi'] != '' and $desadv_laskurow['asiakastoim_ovttunnus'] != '') {
				$desadv_message .= "NAD+DP+$desadv_laskurow[asiakastoim_ovttunnus]:100++$desadv_laskurow[asiakastoim_nimi] $desadv_laskurow[asiakastoim_nimitark]+$desadv_laskurow[asiakastoim_osoite]+$desadv_laskurow[asiakastoim_postitp]++$desadv_laskurow[asiakastoim_postino]+$desadv_laskurow[asiakastoim_maa]'";
				$x++;

				$desadv_message .= "LOC+11+:::$desadv_laskurow[asiakastoim_postitp]'";
				$x++;
			}
			else {
				$error .= "Asiakkaan toim_ovt-tunnus / toim_nimi puuttui!<br>";
			}

			// NAD - identification of consignee
			if ($toitarow['toim_nimi'] != '' and $toitarow['toim_ovttunnus'] != '') {
				$desadv_message .= "NAD+CN+$toitarow[toim_ovttunnus]:100++$toitarow[toim_nimi] $toitarow[toim_nimitark]+$toitarow[toim_osoite]+$toitarow[toim_postitp]++$toitarow[toim_postino]+$toitarow[toim_maa]'";
				$x++;
			}
			else {
				$error .= "Toimitustavan toim_ovt-tunnus / toim_nimi puuttui!<br>";
			}


			if (trim($desadv_laskurow['toimitusehto']) != '') {
				// TOD - terms of delivery
				$desadv_message .= "TOD+6+Z2K+".substr($desadv_laskurow['toimitusehto'], 0, 3)."'";
				$x++;
			}

			// LOC - place/location identification
			// ei ole pupessa listausta UN/LOCODE
			$desadv_message .= "LOC+1'";
			$x++;

			// CPS - consignment packing sequence
			$desadv_message .= "CPS+1'";
			$x++;

			if (trim($desadv_rakir_row['viesti']) != '') {
				$desadv_message .= "FTX+GEN+++$desadv_rakir_row[viesti]'";
				$x++;
			}

			// PAC - number and type of packages
			$query = "	SELECT sum(kollit) kollit
						from rahtikirjat use index (otsikko_index)
						where yhtio = '$kukarow[yhtio]'
						and otsikkonro = '$desadv_laskurow[laskutunnus]'
						and rahtikirjanro = '$rahtikirjanro'";
			$desadv_kollitres = mysql_query($query) or pupe_error($query);
			$desadv_kollitrow = mysql_fetch_assoc($desadv_kollitres);

			$desadv_message .= "PAC+$desadv_kollitrow[kollit]++PK:6'";
			$x++;

			// LIN - line item
			$query = "	SELECT tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro, sum(tilausrivi.varattu) varattu
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
 						AND tilausrivi.otunnus = '$desadv_laskurow[laskutunnus]'
						GROUP BY tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro";
			$tuotteet_res = mysql_query($query) or pupe_error($query);

			$i = 1;

			while ($tuotteet_row = mysql_fetch_assoc($tuotteet_res)) {

				if ($tuotteet_row["eankoodi"] == "") {
					$error .= "Tuotteelta $tuotteet_row[tuoteno] puuttuu EAN-koodi<br>";
				}

				$desadv_message .= "LIN+$i++$tuotteet_row[eankoodi]:EN'";
				$x++;

				// PIA - additional product identification
				$desadv_message .= "PIA+5+".substr(str_replace(array("+",":","'"), " ", $tuotteet_row['tuoteno']), 0, 35).":SA'";
				$x++;

				// IMD - additional information of the article
				$desadv_message .= "IMD+F+8+:::".substr(str_replace(array("+",":","'"), " ", $tuotteet_row['nimitys']), 0, 256)."'";
				$x++;

				// QTY - quantity
				// PCE = piece
				// tarvitaan varmaan laittaa eri uniitteja???? UN/ECE recommendation number 20
				$desadv_message .= "QTY+12:".round($tuotteet_row['varattu'], 0).":PCE'";
				$x++;

				$i++;
			}

			// CNT - control total
			$desadv_message .= "CNT+2:$i'";
			$x++;

			// UNT - message trailer
			$x++;
			$desadv_message .= "UNT+$x+$desadv_laskurow[laskutunnus]'";

			$filenimi = "dataout/desadv_import-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

			//kirjoitetaan faili levylle..
			if (file_put_contents($filenimi, $desadv_message) === FALSE) {
				echo "<br/><font class='error'>",t("tiedoston kirjoitus EPÄONNISTUI")," ($filenimi)</font><br/>";
			}

			if ($error == '') {
				// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
				// palautetaan $palautus ja $syy
				$ftphost = $desadv_host;
				$ftpuser = $desadv_user;
				$ftppass = $desadv_pass;
				$ftppath = $desadv_path;
				$ftpfile = realpath($filenimi);
				
				require ("inc/ftp-send.inc");
			}
			else {
				echo "<br/><font class='error'>",t("Tapahtui virhe"),":<br>$error</font><br>";
			}
		}
	}
?>