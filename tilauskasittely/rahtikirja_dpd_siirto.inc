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
	else {
		//Tulostetaan standardi kolliosoitelappu
		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($tempr);
	}

	//$apu_kollityht = $kollityht;
	if ($kollityht > 10) {
		die("Liikaa kolleja! (max 10 kpl)"); // kolleja ei saa olla yli 10 per toimitusrivi...
	}

	//$kiloja = "1/2/3.5";
	//$express = 10;
	//$express = 12;
	//$jv = true;

	//print_r($laskurow);
	$query ="	SELECT sum(kollit) kollit,
				group_concat(kilot SEPARATOR '/') kilot,
				sum(kuutiot) kuutiot,
				sum(lavametri) lavametri,
				min(pakkauskuvaustark) pakkauskuvaustark
				from rahtikirjat use index (otsikko_index)
				where yhtio = '$kukarow[yhtio]'
				and otsikkonro = '$rahtikirjanro'
				and rahtikirjanro = '$rahtikirjanro'
				and pakkaus = '$pakkaus[0]'";
	//and pakkauskuvaus = '$row[selitetark]'";
	$tempr = mysql_query($query) or pupe_error($query);
	$rakirow = mysql_fetch_array($tempr);

	$kiloja = explode('/', $rakirow[kilot]);

	if (count($kiloja) != $kollit[0]) {
		die("Kollit ja painot ei täsmää! ".count($kiloja)."/".$kollit[0]."Kiloja = ".$rakirow[kilot]);
	}

	// Myöhemmin tarvitaan asiakastietoja, suoritetaan kysely
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus  = '$laskurow[liitostunnus]'";
	$tempr = mysql_query($query) or pupe_error($query);
	$asiakasrow = mysql_fetch_array($tempr);

	// luodaan DELISprint export-tiedoston rivi
	// ensin lähetystapa (oletus: Normal Parcel, NP)
	$lahTapa = "NP";

	// onko pienipaketti? EI TARVITA TÄLLÄ HETKELLÄ!
	/*
	$pieni = true;
	for ($i = 0; $i < count($kiloja); $i++) {
		if ((float) $kiloja[$i] > 3.5) {
			$pieni = false;
			break;
		}
	}

	if ($pieni == true) {
		$lahTapa = "KP";
	}
	*/

	// onko EXPRESS10 tai 12
	if (strpos($toitarow['selite'], "E10")) $lahTapa .= ",E10";
	if (strpos($toitarow['selite'], "E12")) $lahTapa .= ",E12";

	// jälkivaatimus?
	if ($rakir_row["jv"] != '' or $mehto['jv'] != '') $lahTapa .= ",NN";

	// Lähetystapa valmis, laitetaan rivin alkuun
	$rivi = $lahTapa.";";

	// Kollien määrä
	$rivi .= $kollityht.";";

	// paino, oletetaan, että aiemmin tarkistettu splittauksella, että syötettyjen painojen lukumäärä = syötetty kollimäärä, joten syötetään suoraan DELISprintille muodossa paino1/paino2/paino3/... jne.
	$rakirow[kilot] = str_replace('.', ',', $rakirow[kilot]); //pitää muuttaa pisteet pilkuiks!
	$rivi .= $rakirow[kilot].";";

	// Jv-summa (ei vielä implementoitu, mutta kenttä vaaditaan!)
	$rivi .= ";";

	// "Viite" = osoitetietoviite DELISprintille = ytunnus
	$rivi .= substr($asiakasrow["ytunnus"],0,20).";";

	// osoitetiedot
	$rivi .= substr($laskurow["toim_nimi"],0,40).";";
	$rivi .= substr($laskurow["toim_nimitark"],0,40).";";
	$rivi .= substr($laskurow["toim_osoite"],0,40).";";
	$rivi .= substr($laskurow["toim_osoite"],40,40).";";
	$rivi .= substr($laskurow["toim_maa"],0,10).";";
	$rivi .= substr($laskurow["toim_postino"],0,10).";";
	$rivi .= substr($laskurow["toim_postitp"],0,40).";";

	// asiakkaan yhteystiedot
	if ($laskurow["tilausyhteyshenkilo"] =="") {
		if (strpos($toitarow['selite'], "E10")) die("Asiakkaan yhteystiedot puuttuvat! Pakolliset EXPRESS-toimitustavalle!");
		if (strpos($toitarow['selite'], "E12")) die("Asiakkaan yhteystiedot puuttuvat! Pakolliset EXPRESS-toimitustavalle!");
		$rivi .= ";";
		$rivi .= sprintf("%s", substr($asiakasrow["puhelin"],0,25)).";";

	}
	else {
		$yhtTiedot = split("/", $laskurow["tilausyhteyshenkilo"]);
		$rivi .= substr($yhtTiedot[0],0,40).";";
		$rivi .= substr($yhtTiedot[1],0,25).";";
	}

	$rivi .= substr($asiakasrow["fax"],0,25).";";
	$rivi .= substr($asiakasrow["email"],0,40).";";
	$rivi .= substr($rahtikirjanro,0,35).";";
	$viitteenne = $laskurow["asiakkaan_tilausnumero"];

	if ($viitteenne == "") {
		$viitteenne = $laskurow["viesti"];
	}

	$rivi .= substr($viitteenne,0,30).";";

	if ($tilausnumeroita == "") {
		$tilausnumeroita = $laskurow["tunnus"];
	}

	$rivi .= substr($tilausnumeroita,0,30).";";
	$rivi .= substr($laskurow["viesti"],0,30).";";
	$rivi .= substr($laskurow["kohde"],0,30).";";

	// Muunnetaan merkistö Windows ANSI
	iconv("ISO-8859-1", "Windows-1252", $rivi);
	// Lisätään, jos tulostetaan useita rivejä tiedostoon (Jepulis, pitää olla Windows rivinvaihto CR\LF!)
	$rivit .= $rivi."\r\n";

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
	$fh = fopen($filenimi, "w");
	if (fwrite($fh, $rivit) === FALSE) {
		die("Tiedoston kirjoitus epäonnistui $filenimi");
	}
	fclose($fh);

	$filenimi='';

}

?>
