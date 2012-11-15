<?php

/* Pupesoft -> DPD DELISprint datasiirto
 * rahtikirja_dpd_siirto.inc v0.8a 18.03.2008
 * Originaali: rahtikirja_dpd.inc
 * Kustomointi: Mikko Nousiainen mikko@datamonitor.fi
 *************************************************************/

echo "<br>kollityht: $kollityht<br><br>";

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	echo "dpd_delisprint_path: $dpd_delisprint_path<br>";

	if (($dpd_delisprint_host != "" and $dpd_delisprint_user != "" and $dpd_delisprint_pass != "" and $dpd_delisprint_path != "") or $dpd_delisprint_path != "") {

		echo "päästiin iffin sisälle.<br>";

		if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

			$logyq = "	SELECT min(rahtikirjanro) rahtikirjanro
						FROM rahtikirjanumero
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytettyaika = '0000-00-00 00:00:00'";
			$logyres = pupe_query($logyq);
			$logyrow = mysql_fetch_assoc($logyres);

			$rahtikirjanro = $logyrow["rahtikirjanro"];

			echo "rkirnro logy: $rahtikirjanro<br>";

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
			echo "rkirnro lotsikot: $rahtikirjanro<br>";
		}
		elseif ($rahtikirjanro == "") {
			$rahtikirjanro = $rivi["rahtikirjanro"];
			echo "rkirnro rivi: $rahtikirjanro<br>";
		}

		if ($phpnimi == "rahtikirja_custom.php") {
			$laskurow = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}

		//$apu_kollityht = $kollityht;
		if ($kollityht > 10) {
			die("Liikaa kolleja! (max 10 kpl)"); // kolleja ei saa olla yli 10 per toimitusrivi...
		}

		//	Luodaan meidän tiedostorakenne parcelOrder arrayhin ja pukataan se tonne aineistoon sitten laakista
		$parcelOrder=array();
		$parcelOrder["rahtikirjanro"] = $rahtikirjanro;
		$parcelOrder["tilausnumero"] = $lotsikot[0];

		/*
			Arvotaan lähetystapa
		*/

		// jos maksuehto on jv, laitetaan NP,NN,CD
		if ($rakir_row['jv'] != "") {
			$parcelOrder["lahetystapa"] = "NP,NN,CD";
		}
		else {
			$parcelOrder["lahetystapa"] = "NP";
		}

		//	Haetaan otsikkotiedot pohjalle
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset)
					ORDER BY tunnus limit 1";
		$laskures = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($laskures);

		for($i=0;$i<mysql_num_fields($laskures);$i++) {
			$r = mysql_field_name($laskures, $i);
			$v = $laskurow[$i];

			//	Korvaavuksia ja näppäilyä
			if($r=="asiakkaan_viite" and $v == "") {
				$v = $laskurow["viite"];
			}

			//	Poistetaan aina kaikki turhat merkit ja välilyönnit
			$parcelOrder[$r] = trim(str_replace(array("\r\n", "\r", "\n"), " ", $v));
		}

		$koko_summa = 0;

		//	Luotamme vahvasti siihen, että meillä on rakir_res tallella.. Täältä tulee jotain hyödyllistä kumminkin..
		for($i=0;$i<mysql_num_fields($rakir_res);$i++) {

			$r = mysql_field_name($rakir_res, $i);
			$v = $rakir_row[$r];

			if ($r == "jv" and $v != "") {
				$parcelOrder["jv_summa"] 		= $yhteensa;
				$parcelOrder["jv_valkoodi"] 	= $yhtiorow["valkoodi"];
				$parcelOrder["jv_pankkitili"] 	= $yhtiorow["pankkitili1"];
				$parcelOrder["jv_viite"] 		= $viite;
			}

			$parcelOrder[$r] = $v;
		}

		if (isset($parcelOrder["jv_summa"]) and trim($parcelOrder["jv_summa"]) != "") {
			$parcelOrder["kateinen"] = t("Käteinen");
		}
		else {
			$parcelOrder["kateinen"] = "";
			$parcelOrder["jv_summa"] = "";
			$parcelOrder["jv_valkoodi"] = "";
		}

		//	Haetaan asiakastiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$asres = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($asres);

		for($i=0;$i<mysql_num_fields($asres);$i++) {
			$r = mysql_field_name($asres, $i);
			$v = $asrow[$i];

			//	Korvaavuksia ja näppäilyä

			$parcelOrder["asiakas_".$r] = $v;
		}

		/*
		Jos meillä oli joku yhteyshenkilö haetaan sen tiedot myös! (Falceback asiakas)
		*/
		$query = "	SELECT nimi, titteli, email, puh, gsm, fax
					FROM laskun_lisatiedot
					JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
					WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
					and otunnus IN ($otunnukset)
					and yhteyshenkilo_tekninen > 0";
		$yhres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhres) == 0) {
			$query = "	SELECT nimi, titteli, email, puh, gsm, fax
						FROM laskun_lisatiedot
						JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen and yhteyshenkilo.tyyppi = 'A'
						WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
						and otunnus IN ($otunnukset)
						and yhteyshenkilo_tekninen > 0";
			$yhres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yhres) == 0) {
				$query = "	SELECT '2' O, '' nimi, '' titteli, email, puhelin puh, gsm, fax
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
				$yhres = mysql_query($query) or pupe_error($query);
			}
		}

		if (mysql_num_rows($yhres) > 0) {
			$yhrow = mysql_fetch_array($yhres);
			for($i=0;$i<mysql_num_fields($yhres);$i++) {
				$r = mysql_field_name($yhres, $i);
				$v = $yhrow[$i];

				//	Korvaavuksia ja näppäilyä
				$parcelOrder["yhteyshenkilo_".$r] = $v;
			}
		}

		$query ="	SELECT sum(kollit) kollit,
					sum(kilot) kilot,
					sum(kuutiot) kuutiot,
					sum(lavametri) lavametri,
					min(pakkauskuvaustark) pakkauskuvaustark
					from rahtikirjat use index (otsikko_index)
					where yhtio 		= '$kukarow[yhtio]'
					and otsikkonro 		= '$rahtikirjanro'
					and rahtikirjanro 	= '$rahtikirjanro'
					and pakkaus 		= '$pakkaus[0]'";
		echo "<br><pre>$query</pre><br><br>";
		//and pakkauskuvaus = '$row[selitetark]'";
		$rakires = mysql_query($query) or pupe_error($query);
		$rakirow = mysql_fetch_array($rakires);

		for($i=0;$i<mysql_num_fields($rakires);$i++) {
			$r = mysql_field_name($rakires, $i);
			$v = $rakirow[$i];

			//	Korvaavuksia ja näppäilyä
			if($r == "kilot") {
				$k = round(($rakirow[$i]/$rakirow["kollit"]), 1);
				if($rakirow["kollit"] == 2) {
					$v = str_replace('.', ',', $k)."/".str_replace('.', ',', $k);
				}
				else {
					$v = str_replace('.', ',', $k);
				}
			}

			$parcelOrder[$r] = $v;
		}

		//echo "<hr>data:<pre>".print_r($parcelOrder, true)."</pre>";

		//	Luodaan aineostoformaatti
		if(!is_array($delisprintFileFormat)) {

			########## Uusi muokattu formaatti ##########
			# Reference
			# Viite
			# Vastaanottajan yhteyshenkilö
			# Vastaanottajan nimi
			# Vastaanottajan lähiosoite
			# Vastaanottajan lähiosoite 2
			# Vastaanottajan postinumero
			# Vastaanottajan postitoimipaikka
			# Vastaanottajan maa
			# Vastaanottajan puhelinnumero
			# Pakkausten määrä
			# Lähetystapa
			# Loppusumma jos jälkivaatimus, muuten ei mitään
			# Valuutta jos jälkivaatimus, muuten ei mitään
			# Jos maksuehto on jälkivaatimus, laitetaan t("Käteinen")
			# Rahtikirjanro
			$delisprintFileFormat = array(
				"tilausnumero",
				"viite",
				"yhteyshenkilo_nimi",
				"toim_nimi",
				"toim_osoite",
				"toim_postino",
				"toim_postitp",
				"toim_maa",
				"yhteyshenkilo_puh",
				"kollit",
				"lahetystapa",
				"jv_summa",
				"jv_valkoodi",
				"kateinen",
				"rahtikirjanro",
			);

		}

		echo "<hr>data:<pre>".var_dump($delisprintFileFormat)."</pre><br>";

		unset($aineisto);

		foreach($delisprintFileFormat as $p) {
			$aineisto .= $parcelOrder[$p].";";

			// tehdään ylimääräinen ; koska formaatissa on lähiosoite 2 mitä ei ole pupessa
			if ($p == "toim_osoite") {
				$aineisto .= ";";
			}
		}

		echo "<br><hr>Aineisto:<br><pre>{$aineisto}</pre><br>";

		// Muunnetaan merkistö Windows ANSI
		iconv("ISO-8859-1", "Windows-1252", $aineisto);

		if (substr($dpd_delisprint_path,-1) != '/') {
			$dpd_delisprint_path .= '/';
		}

		$filenimi = $dpd_delisprint_path."dpdimport-".md5(uniqid(rand(),true)).".dat";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $aineisto) === FALSE) {
			echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")."</font><br>";
		}

		if ($dpd_delisprint_host != "" and $dpd_delisprint_user != "" and $dpd_delisprint_pass != "" and $dpd_delisprint_path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost = $dpd_delisprint_host;
			$ftpuser = $dpd_delisprint_user;
			$ftppass = $dpd_delisprint_pass;
			$ftppath = $dpd_delisprint_path;
			$ftpfile = realpath($filenimi);
			require ("inc/ftp-send.inc");
		}
	}
	else {
		echo "<br><font class='error'>".t("DPD aineiston luotiin tarvittavia parametrejä puuttuu!")."</font><br>";
	}

}

?>
