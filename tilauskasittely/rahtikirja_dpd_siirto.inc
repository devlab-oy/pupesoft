<?php

/* Pupesoft -> DPD DELISprint datasiirto
 * rahtikirja_dpd_siirto.inc v0.8a 18.03.2008
 * Originaali: rahtikirja_dpd.inc
 * Kustomointi: Mikko Nousiainen mikko@datamonitor.fi
 *************************************************************/

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	// tarvitaan tilauksen tunnus muutujassa $tunnus
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
	// mehto sisältää maksuehdon tiedot
	// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
	//
	// tulostetaan rahtikirja

	//Näin se oli muuallakin onkai se sitten oikein?
	$rahtikirjanro = $lotsikot[0];

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
	
	/*
		Arvotaan lähetystapa
	*/
	
	// onko EXPRESS10 tai 12
	if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
		$parcelOrder["lahetystapa"] = "NN";
	}
	elseif (strpos($toitarow['selite'], "E10")) {
		$parcelOrder["lahetystapa"] = "E10";
	}
	elseif (strpos($toitarow['selite'], "E12")) {
		$parcelOrder["lahetystapa"] = "E12";
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

		$parcelOrder[$r] = $v;
	}
	
	//	Luotamme vahvasti siihen, että meillä on rakir_res tallella.. Täältä tulee jotain hyödyllistä kumminkin..
	for($i=0;$i<mysql_num_fields($rakir_res);$i++) {

		$r = mysql_field_name($rakir_res, $i);
		$v = $rakir_row[$i];
		
		//	Korvaavuksia ja näppäilyä
		if($r=="jv" and $v != "") {
			$parcelOrder["jv_maara"] 		= $yhteensa;
			$parcelOrder["jv_valkoodi"] 	= $yhtiorow["valkoodi"];
			$parcelOrder["jv_pankkitili"] 	= $yhtiorow["pankkitili1"];
			$parcelOrder["jv_viite"] 		= $viite;
		}

		$parcelOrder[$r] = $v;
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
				JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen
				WHERE laskun_lisatiedot.yhtio='$kukarow[yhtio]' and otunnus IN ($otunnukset) and yhteyshenkilo_tekninen>0";
	$yhres = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($yhres) == 0) {
		$query = "	SELECT nimi, titteli, email, puh, gsm, fax
					FROM laskun_lisatiedot
					JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen
					WHERE laskun_lisatiedot.yhtio='$kukarow[yhtio]' and otunnus IN ($otunnukset) and yhteyshenkilo_tekninen>0";
		$yhres = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($yhres) == 0) {
			$query = "	SELECT '2' O, '' nimi, '' titteli, email, puhelin puh, gsm, fax
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$yhres = mysql_query($query) or pupe_error($query);
		}
	}
	if(mysql_num_rows($yhres) > 0) {
		$yhrow = mysql_fetch_array($yhres);
		for($i=0;$i<mysql_num_fields($yhres);$i++) {
			$r = mysql_field_name($yhres, $i);
			$v = $yhrow[$i];

			//	Korvaavuksia ja näppäilyä

			$parcelOrder["yhteyshenkilo_".$r] = $v;
		}
	}
	else {
		//	Falceback 
	}
	
	$query ="	SELECT sum(kollit) kollit,
				sum(kilot) kilot,
				sum(kuutiot) kuutiot,
				sum(lavametri) lavametri,
				min(pakkauskuvaustark) pakkauskuvaustark
				from rahtikirjat use index (otsikko_index)
				where yhtio = '$kukarow[yhtio]'
				and otsikkonro = '$rahtikirjanro'
				and rahtikirjanro = '$rahtikirjanro'
				and pakkaus = '$pakkaus[0]'";
	//and pakkauskuvaus = '$row[selitetark]'";
	$rakires = mysql_query($query) or pupe_error($query);
	$rakirow = mysql_fetch_array($rakires);
	
	/*
	if (count($kiloja) != $kollit[0]) {
		die("Kollit ja painot ei täsmää! ".count($kiloja)."/".$kollit[0]."Kiloja = ".$rakirow[kilot]);
	}
	*/
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

	// Muunnetaan merkistö Windows ANSI
	iconv("ISO-8859-1", "Windows-1252", $rivi);
	// Lisätään, jos tulostetaan useita rivejä tiedostoon (Jepulis, pitää olla Windows rivinvaihto CR\LF!)
	$rivit .= $rivi."\r\n";
	
	//	Luodaan aineostoformaatti
	if(!is_array($delisprintFileFormat)) {
		// Lähetystapa ; Paino (kg) ; Senders Reference: ; Order #: ; Nimi ; Osoite 1 ; Maa ; Postinr. ; Kaup. ; Yhteyshenkilö ; Puh. ; Fax ; Sähköposti ;
		$delisprintFileFormat = array(
										"lahetystapa",
										"kollit",
										"kilot",
										"rahtikirjanro",
										"asiakkaan_tilausnumero",
										"toim_nimi",
										"toim_osoite",
										"toim_maa",
										"toim_postino",
										"toim_postitp",										
										"yhteyshenkilo_nimi",
										"puh",
										"fax",
										"emai",
										"comments",										
									);
		
	}
	
	//echo "<hr>data:<pre>".print_r($delisprintFileFormat, true)."</pre>";

	unset($aineisto);
	foreach($delisprintFileFormat as $p) {
		if(isset($aineisto)) {
			$aineisto .=";";
		}
		$aineisto.=$parcelOrder[$p];
	}
	
	if ($delisprint_polku != '') {
		if (substr($delisprint_polku,-1) != '/') {
			$delisprint_polku .= '/';
		}
		$filenimi = $delisprint_polku."dpdimport-".md5(uniqid(rand(),true)).".dat";
	}
	else {
		$filenimi = "/tmp/dpdimport-".md5(uniqid(rand(),true)).".dat";
	}
	
	//kirjoitetaan faili levylle..
	if (file_put_contents($filenimi, $aineisto) === FALSE) {
		echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")."</font><br>";
	}
	else {
		echo "<br><font class='message'>".t("Tallennettiin DPD-aineisto")."</font><br>";
	}
}

?>
