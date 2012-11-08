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

			$logyq = "	UPDATE rahtikirjanumero
						SET kayttaja = '$kukarow[kuka]',
						kaytettyaika = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and rahtikirjanro = '$logyrow[rahtikirjanro]'";
			$logyres = pupe_query($logyq);
		}
		elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
			$rahtikirjanro = $lotsikot[0];
		}
		else {
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

		$sanoma  = "Mercantile Group As;";
		$sanoma .= substr(trim($rakir_row['toim_nimi'].' '.$rakir_row['toim_nimitark']), 0, 30).";";
		$sanoma .= substr(trim($rakir_row['toim_osoite']), 0, 25).";";
		$sanoma .= substr(trim($rakir_row['toim_postino'].' '.$rakir_row['toim_postitp']), 0, 25).";";
		$sanoma .= substr(trim($rakir_row['toim_maa']), 0, 25).";";

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

		if (mysql_num_rows($yhres) > 0) {
			while ($yhrow = mysql_fetch_assoc($yhres)) {
				$sanoma .= substr(trim($yhrow['nimi']), 0, 25).";";
				$sanoma .= substr(trim($yhrow['puh']), 0, 25).";";
			}
		}
		else {
			$sanoma .= ";;";
		}

		$sanoma .= "{$kollityht};";
		$sanoma .= "{$pakkaus[0]};";
		$sanoma .= "EE0{$rahtikirjanro};";

		if ($hrx_path != '') {
			if (substr($hrx_path,-1) != '/') {
				$hrx_path .= '/';
			}
			$filenimi = $hrx_path."hrx-import-".md5(uniqid(rand(),true)).".dat";
		}
		else {
			$filenimi = "/tmp/hrx-import-".md5(uniqid(rand(),true)).".dat";
		}

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $sanoma) === FALSE) {
			echo "<br><font class='error'>",t("Tiedoston kirjoitus epäonnistui"),"!</font><br>";
		}

		if ($hrx_host != "" and $hrx_user != "" and $hrx_pass != "" and $hrx_path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			// $ftphost = $hrx_host;
			// $ftpuser = $hrx_user;
			// $ftppass = $hrx_pass;
			// $ftppath = $hrx_path;
			// $ftpfile = realpath($filenimi);
			// require ("inc/ftp-send.inc");
		}

	}
}
