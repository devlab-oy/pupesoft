<?php

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	if (($hrx_host != "" and $hrx_user != "" and $hrx_pass != "" and $hrx_path != "") or $hrx_path != "") {

		/*
		Kenttien sisällöt ja kiinteät pituudet ovat:
		„Mercantile Group As“					x(19)
		Vastaanottajan  nimi	 				x(30)
		Vastaanottajan  lähiosoite				x(25)
		Vastaanottajan  lähiosoite-2				x(25)
		Vastaanottajan  postinumero + „ „ + postitoimipaikka 	x(25)
		„Eesti“							x(25)
		Vastaanottajan yhteyshenkilö				x(25)
		Vastaanottajan puhelinnumero				x(25)
		Pakkausten määrä					9(6)
		Yksikön selitys (CLL jos paketti, PLL jos lava)		x(3)
		„EE0“ + CMR-numero					x(10)
		*/

		if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

			$logyq = "	SELECT min(rahtikirjanro) rahtikirjanro
						FROM rahtikirjanumero
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytettyaika = '0000-00-00 00:00:00'";
			$logyres = pupe_query($logyq);
			$logyrow = mysql_fetch_assoc($logyres);

			$rahtikirjanro = $logyrow["rahtikirjanro"];

			if ($rahtikirjanro != "") {
				$logyq = "	UPDATE rahtikirjanumero
							SET kayttaja = '$kukarow[kuka]',
							kaytettyaika = now()
							WHERE yhtio = '$kukarow[yhtio]'
							and rahtikirjanro = '$logyrow[rahtikirjanro]'";
				$logyres = pupe_query($logyq);
			}
		}

		if ($rahtikirjanro == "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
			$rahtikirjanro = $lotsikot[0];
		}
		elseif ($rahtikirjanro == "") {
			$rahtikirjanro = $rivi["rahtikirjanro"];
		}

		if ($phpnimi == "rahtikirja_custom.php") {
			$laskurow = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}
		else {
			//	Haetaan otsikkotiedot pohjalle
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus IN ({$otunnukset})
						ORDER BY tunnus
						LIMIT 1";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);
		}

		$y_nimi = "Mercantile Group As";
		$v_nimi = substr(trim($rakir_row['toim_nimi'].' '.$rakir_row['toim_nimitark']), 0, 30);
		$v_osoite = substr(trim($rakir_row['toim_osoite']), 0, 50);
		$v_postino_postitp = substr(trim($rakir_row['toim_postino'].' '.$rakir_row['toim_postitp']), 0, 25);

		$v_maa = substr(t_maanimi($rakir_row['toim_maa'], $kukarow['kieli']), 0, 25);

		$sanoma  = str_pad($y_nimi, 19, " ");
		$sanoma .= str_pad($v_nimi, 30, " ");
		$sanoma .= str_pad($v_osoite, 50, " "); #kaksi kenttää yhdistetty samaksi (osoite 1 ja osoite 2)
		$sanoma .= str_pad($v_postino_postitp, 25, " ");
		$sanoma .= str_pad($v_maa, 25, " ");

		$query = "	SELECT nimi, titteli, email, puh, gsm, fax
					FROM laskun_lisatiedot
					JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = laskun_lisatiedot.yhtio AND yhteyshenkilo.tunnus = laskun_lisatiedot.yhteyshenkilo_tekninen AND yhteyshenkilo.tyyppi = 'A')
					WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
					AND otunnus IN ({$otunnukset})
					AND yhteyshenkilo_tekninen > 0
					LIMIT 1";
		$yhres = pupe_query($query);

		if (mysql_num_rows($yhres) == 0) {
			$query = "	SELECT nimi, titteli, email, puh, gsm, fax
						FROM laskun_lisatiedot
						JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = laskun_lisatiedot.yhtio AND yhteyshenkilo.tunnus = laskun_lisatiedot.yhteyshenkilo_kaupallinen AND yhteyshenkilo.tyyppi = 'A')
						WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
						AND otunnus IN ({$otunnukset})
						AND yhteyshenkilo_tekninen > 0
						LIMIT 1";
			$yhres = pupe_query($query);

			if (mysql_num_rows($yhres) == 0) {
				$query = "	SELECT '2' O, '' nimi, '' titteli, email, puhelin puh, gsm, fax
							FROM asiakas
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$laskurow['liitostunnus']}'";
				$yhres = pupe_query($query);
			}
		}

		$yh_nimi = "";
		$yh_puh = "";

		if (mysql_num_rows($yhres) > 0) {
			$yhrow = mysql_fetch_assoc($yhres);

			$yh_nimi = substr(trim($yhrow['nimi']), 0, 25);
			$yh_puh = substr(trim($yhrow['puh']), 0, 25);
		}

		$sanoma .= str_pad($yh_nimi, 25, " ");
		$sanoma .= str_pad($yh_puh, 25, " ");
		$sanoma .= str_pad($kollityht, 6, "0", STR_PAD_LEFT);
		$sanoma .= str_pad(substr(trim($pakkaus[0]), 0, 3), 3, " ");
		$sanoma .= str_pad(substr(trim($rahtikirjanro), 0, 10), 10, " ");

		if (substr($hrx_path,-1) != '/') {
			$hrx_path .= '/';
		}

		$filenimi = $hrx_path."hrx-import-".md5(uniqid(rand(),true)).".dat";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $sanoma) === FALSE) {
			echo "<br><font class='error'>",t("Tiedoston kirjoitus epäonnistui"),"!</font><br>";
		}

		if ($hrx_host != "" and $hrx_user != "" and $hrx_pass != "" and $hrx_path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost = $hrx_host;
			$ftpuser = $hrx_user;
			$ftppass = $hrx_pass;
			$ftppath = $hrx_path;
			$ftpfile = realpath($filenimi);
			require ("inc/ftp-send.inc");
		}

	 	# TULOSTETAAN LIPPULAPPUSET
		// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$laskurow['varasto']}'";
		$tempr = pupe_query($query);
		$postirow_varasto = mysql_fetch_array($tempr);

		if ($postirow_varasto['nimi'] != "") {
			$lahettaja_nimi = $postirow_varasto['nimi'];
			$lahettaja_nimitark = $postirow_varasto['nimitark'];
			$lahettaja_osoite = $postirow_varasto['osoite'];
			$lahettaja_postino = $postirow_varasto['postino'];
			$lahettaja_postitp = $postirow_varasto['postitp'];
			$lahettaja_maa = $postirow_varasto['maa'];
		}
		else {
			$lahettaja_nimi = $row['yhtio_nimi'];
			$lahettaja_nimitark = "";
			$lahettaja_osoite = $row['yhtio_osoite'];
			$lahettaja_postino = $row['yhtio_postino'];
			$lahettaja_postitp = $row['yhtio_postitp'];
			$lahettaja_maa = $row['yhtio_maa'];
		}

		for ($i = 1; $i <= $kollityht; $i++) {
			// Tulostetaan Pupen oma kollitarra
			$params = array(
	 			'tilriv' => "{$rahtikirjanro}",
	 			'toimitustapa' => $laskurow['toimitustapa'],
	 			'toim_nimi' => $rakir_row['toim_nimi'],
	 			'toim_nimitark' => $rakir_row['toim_nimitark'],
	 			'toim_osoite' => $rakir_row['toim_osoite'],
	 			'toim_postino' => $rakir_row['toim_postino'],
	 			'toim_postitp' => $rakir_row['toim_postitp'],
	 			'lahettaja_nimi' => $lahettaja_nimi,
	 			'lahettaja_nimitark' => $lahettaja_nimitark,
	 			'lahettaja_osoite' => $lahettaja_osoite,
	 			'lahettaja_postino' => $lahettaja_postino,
	 			'lahettaja_postitp' => $lahettaja_postitp,
	 			'lahettaja_maa' => $lahettaja_maa,
	 			'reittietikettitulostin' => $kirjoitin,
	 			'monesko_laatikko' => $i,
	 			'montako_laatikkoa_yht' => $kollityht,
	 			'pakkauskoodi' => $pakkaus[0],
	 		);

			tulosta_hrx($params);
		}
	}
}
