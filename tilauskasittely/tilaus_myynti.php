<?php
//	Ladataan javahelpperit

if (file_exists("../inc/parametrit.inc")) {
	require ("../inc/parametrit.inc");
}
else {
	require ("parametrit.inc");
}

if (file_exists("../inc/alvpopup.inc")) {
	require ("../inc/alvpopup.inc");
}
else {
	require ("alvpopup.inc");
}

if($yhtiorow["tilauksen_yhteyshenkilot"] == "K" or $yhtiorow["asiakkaan_kohde"] == "K") {
	js_yllapito();
}

if ((int) $luotunnusnippu > 0 and $tilausnumero == $kukarow["kesken"] and $kukarow["kesken"] > 0) {
	$query = "	UPDATE lasku
				SET tunnusnippu = tunnus
				where yhtio		= '$kukarow[yhtio]'
				and tunnus		= '$kukarow[kesken]'
				and tunnusnippu = 0";
	$result = mysql_query($query) or pupe_error($query);

	$valitsetoimitus = $toim;
}

// Vaihdetaan tietyn projektin toiseen toimitukseen
//	HUOM! tämä käyttää aktivointia joten tämä on oltava aika alussa!! (valinta on onchage submit rivisyötössä joten noita muita paremetreja ei oikein voi passata eteenpäin..)
if ((int) $valitsetoimitus > 0) {
	$tee 			= "AKTIVOI";
	$tilausnumero 	= $valitsetoimitus;
	$from 			= "VALITSETOIMITUS";


	$query = "select tila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
	$result = mysql_query($query) or pupe_error($query);
	$toimrow = mysql_fetch_array($result);

	if ($toimrow["tila"] == "L" or $toimrow["tila"] == "N") {
		if ($toim != "RIVISYOTTO" and $toim != "PIKATILAUS") $toim = "RIVISYOTTO";
	}
	elseif ($toimrow["tila"] == "T") {
		$toim = "TARJOUS";
	}
	elseif ($toimrow["tila"] == "A") {
		$toim = "TYOMAARAYS";
	}
	elseif ($toimrow["tila"] == "C") {
		$toim = "REKLAMAATIO";
	}
	elseif ($toimrow["tila"] == "V") {
		$toim = "VALMISTAASIAKKAALLE";
	}
	elseif ($toimrow["tila"] == "W") {
		$toim = "VALMISTAVARASTOON";
	}
	elseif ($toimrow["tila"] == "R") {
		$toim = "PROJEKTI";
	}
}
elseif(in_array($valitsetoimitus, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","VALMISTAVARASTOON","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
	$uusitoimitus = $valitsetoimitus;
}

if (($kukarow["extranet"] != '' and $toim != 'EXTRANET') or ($kukarow["extranet"] == "" and $toim == "EXTRANET")) {
	//aika jännä homma jos tänne jouduttiin
	exit;
}

// aktivoidaan saatu id
if ($tee == 'AKTIVOI') {
	// katsotaan onko muilla aktiivisena
	$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
	$result = mysql_query($query) or pupe_error($query);

	unset($row);

	if (mysql_num_rows($result) != 0) {
		$row=mysql_fetch_array($result);
	}

	if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
		echo "<font class='error'>".t("Tilaus on aktiivisena käyttäjällä")." $row[nimi]. ".t("Tilausta ei voi tällä hetkellä muokata").".</font><br>";

		// poistetaan aktiiviset tilaukset jota tällä käyttäjällä oli
		$query = "UPDATE kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		exit;
	}
	else {
		$query = "	UPDATE kuka
					SET kesken = '$tilausnumero'
					WHERE yhtio = '$kukarow[yhtio]' AND
					kuka = '$kukarow[kuka]' AND
					session = '$session'";
		$result = mysql_query($query) or pupe_error($query);

		// Näin ostataan valita pikatilaus
		if ($toim == "RIVISYOTTO" and isset($PIKATILAUS)) {
			$toim = "PIKATILAUS";
		}
		// Jos tullaan projektille pitää myös aktivoida $projektilla
		elseif ($toim == "PROJEKTI") {
			$projektilla = $tilausnumero;
		}
		elseif ($toim == "VALMISTAASIAKKAALLE" and $tilausnumero != "") {
			$tyyppiquery = "select tilaustyyppi from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
			$tyyppiresult = mysql_query($tyyppiquery) or pupe_error($tyyppiquery);

			if (mysql_num_rows($tyyppiresult) != 0) {
				$tyyppirow=mysql_fetch_array($tyyppiresult);

				if (strtoupper($tyyppirow['tilaustyyppi']) == 'W') {
					$toim = "VALMISTAVARASTOON";
				}
			}
			else {
				echo "<font class='error'>".t("Tilaus katosi")."!!!</font><br>";
				$tilausnumero = "";
			}
		}

		$kukarow['kesken'] 	 = $tilausnumero;

		//	Täällä muutetaan yleensä vain toimitusaikoja tms..
		if($from == "PROJEKTIKALENTERI") {
			$tee = "OTSIK";
			$query = "	SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow["yhtio"]}' and tunnus='{$kukarow["kesken"]}'";
			$tarkres = mysql_query($query) or pupe_error($query);
			$tarkrow = mysql_fetch_array($tarkres);
			$asiakasid = $tarkrow["liitostunnus"];
			$tiedot_laskulta = "YES";
		}
		else {
			$tee = "";
		}
	}
}

// jos ei olla postattu mitään, niin halutaan varmaan tehdä kokonaan uusi tilaus..
if ($kukarow["extranet"] == "" and count($_POST) == 0 and ($from != "LASKUTATILAUS" and $from != "VALITSETOIMITUS" and $from != "PROJEKTIKALENTERI")) {
	$tila				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow["kesken"]	= '';

	//varmistellaan ettei vanhat kummittele...
	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

// Extranet keississä asiakasnumero tulee käyttäjän takaa
if ($kukarow["extranet"] != '') {
	// Haetaan asiakkaan tunnuksella
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$extra_asiakas = mysql_fetch_array($result);
		$ytunnus 	= $extra_asiakas["ytunnus"];
		$asiakasid 	= $extra_asiakas["tunnus"];

		if ($kukarow["kesken"] != 0) {
			// varmistetaan, että tilaus on oikeasti kesken ja tälle asiakkaalle
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' AND
						tunnus = '$kukarow[kesken]' AND
						liitostunnus = '$asiakasid' AND
						tila = 'N'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$tilausnumero = $kukarow["kesken"];
			}
			else {
				$tilausnumero = "";
				$kukarow["kesken"] = "";
			}
		}
	}
	else {
		echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
		exit;
	}
}

//katsotaan että kukarow kesken, $tilausnumero ja $kukarow[kesken] stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
	echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
	exit;
}

if ((int) $valitsetoimitus_vaihdarivi > 0 and $tilausnumero == $kukarow["kesken"] and $kukarow["kesken"] > 0 and $toim != "TARJOUS") {
	// Vaihdetaan rivin otunnus
	$query = "	UPDATE tilausrivi
				SET otunnus = '$valitsetoimitus_vaihdarivi'
				WHERE yhtio 		= '$kukarow[yhtio]'
				and otunnus 		= '$edtilausnumero'
				and tunnus 			= '$rivitunnus'
				and uusiotunnus 	= 0
				and toimitettuaika 	= '0000-00-00 00:00:00'";
	$result = mysql_query($query) or pupe_error($query);

	$rivitunnus = "";
}

//jos jostain tullaan ilman $toim-muuttujaa
if ($toim == "") {
	$toim = "RIVISYOTTO";
}
elseif ($toim == "EXTRANET") {
	$otsikko = t("Extranet-Tilaus");
}
elseif ($toim == "TYOMAARAYS") {
	$otsikko = t("Työmääräys");
}
elseif ($toim == "REKLAMAATIO") {
	$otsikko = t("Reklamaatio");
}
elseif ($toim == "VALMISTAVARASTOON") {
	$otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
	$otsikko = t("Varastosiirto");
}
elseif ($toim == "SIIRTOTYOMAARAYS") {
	$otsikko = t("Sisäinen työmääräys");
}
elseif ($toim == "MYYNTITILI") {
	$otsikko = t("Myyntitili");
}
elseif ($toim == "VALMISTAASIAKKAALLE") {
	$otsikko = t("Asiakkaallevalmistus");
}
elseif ($toim == "TARJOUS") {
	$otsikko = t("Tarjous");
}
elseif ($toim == "PROJEKTI") {
	$otsikko = t("Projekti");
}
elseif ($toim == "YLLAPITO") {
	$otsikko = t("Ylläpitosopimus");
}
else {
	$otsikko = t("Myyntitilaus");
}

//korjataan hintaa ja aleprossaa
$hinta	= str_replace(',','.',$hinta);
$ale 	= str_replace(',','.',$ale);
$kpl 	= str_replace(',','.',$kpl);

// asiakasnumero on annettu, etsitään tietokannasta...
if ($tee == "" and (($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0) or ($kukarow["extranet"] == "" and ($syotetty_ytunnus != '' or $asiakasid != '')))) {

	if (substr($ytunnus,0,1) == "£") {
		$ytunnus = $asiakasid;
	}
	else {
		$ytunnus = $syotetty_ytunnus;
	}

	$kutsuja    = "otsik.inc";

	if (file_exists("../inc/asiakashaku.inc")) {
		require ("../inc/asiakashaku.inc");
	}
	else {
		require ("asiakashaku.inc");
	}

	// Ei näytetä tilausta jos meillä on asiakaslista ruudulla
	if ($monta != 1) {
		$tee = "SKIPPAAKAIKKI";
	}
}

//Luodaan otsikko
if ($tee == "" and ($toim == "PIKATILAUS" and ((int) $kukarow["kesken"] == 0 and ($tuoteno != '' or $asiakasid != '')) or ((int) $kukarow["kesken"] != 0 and $asiakasid != '' and $kukarow["extranet"] == "")) or ($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0)) {
	// Luodaan uusi myyntitilausotsikko
	if ($kukarow["extranet"] == "") {
		require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
	}
	else {
		require_once("luo_myyntitilausotsikko.inc");
	}

	$tilausnumero = luo_myyntitilausotsikko($asiakasid, $tilausnumero, $myyjanro);
	$kukarow["kesken"] = $tilausnumero;
	$kaytiin_otsikolla = "NOJOO!";
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] != 0) {

	if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "REKLAMAATIO")) {
		$query  = "	SELECT *
					FROM lasku, tyomaarays
					WHERE lasku.tunnus='$kukarow[kesken]'
					AND lasku.yhtio='$kukarow[yhtio]'
					AND tyomaarays.yhtio=lasku.yhtio
					AND tyomaarays.otunnus=lasku.tunnus
					AND lasku.tila != 'D'";
	}
	else {
		// pitää olla: siirtolista, sisäinen työmääräys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, ylläpitosopimus, projekti
		$query 	= "	SELECT *
					FROM lasku
					WHERE tunnus = '$kukarow[kesken]' AND yhtio = '$kukarow[yhtio]' AND tila in ('G','S','C','T','V','N','E','L','0','R') AND (alatila != 'X' or tila = '0')";
	}
	$result  	= mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<br><br><br>".t("VIRHE: Tilaustasi ei löydy tai se on mitätöity/laskutettu")."! ($kukarow[kesken])<br><br><br>";
		$query = "	UPDATE kuka
					SET kesken = 0
					WHERE yhtio = '$kukarow[yhtio]' AND
					kuka = '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
		exit;
	}

	$laskurow = mysql_fetch_array($result);
	if($yhtiorow["tilauksen_kohteet"] == "K") {
		$query 	= "	SELECT *
					from laskun_lisatiedot
					where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$lasklisatied_row  = mysql_fetch_array($result);
	}

	if ($yhtiorow["suoratoim_ulkomaan_alarajasumma"] > 0 and $laskurow["vienti_kurssi"] != 0) {
		$yhtiorow["suoratoim_ulkomaan_alarajasumma"] = round(laskuval($yhtiorow["suoratoim_ulkomaan_alarajasumma"], $laskurow["vienti_kurssi"]),0);
	}

	if ($laskurow["toim_maa"] == "") $laskurow["toim_maa"] = $yhtiorow['maa'];

	if ($laskurow['jtkielto'] != '') {
		$yhtiorow["puute_jt_oletus"] = "";
	}

}

//tietyissä keisseissä tilaus lukitaan (ei syöttöriviä eikä muota muokkaa/poista-nappuloita)
$muokkauslukko = $state = "";

//	Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
if ($laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
	$query 	= "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
	$abures = mysql_query($query) or pupe_error($query);
	$projektilask = (int) mysql_num_rows($abures);
}

if ($kukarow["extranet"] == "" and ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") or ($toim == "PROJEKTI" and $projektilask > 0) or ($toim == "TARJOUS" and $projektilla > 0) or $laskurow["alatila"] == "X") {
	$muokkauslukko 	= "LUKOSSA";
	$state 			= "DISABLED";
}

// Hyväksytään tajous ja tehdään tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS" and $muokkauslukko == "") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
		exit;
	}

	//Luodaan valituista riveistä suoraan normaali ostotilaus
	require("tilauksesta_ostotilaus.inc");

	$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'T');
	$tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"],'U');

	if ($tilauksesta_ostotilaus != '') echo "$tilauksesta_ostotilaus<br><br>";

	// katsotaan ollaanko tehty JT-supereita..
	require("jt_super.inc");

	$jtsuper = jt_super($kukarow["kesken"]);
	if ($jtsuper != '') echo "$jtsuper<br><br>";

	// Kopsataan valitut rivit uudelle myyntitilaukselle
	require("tilauksesta_myyntitilaus.inc");

	$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '', '', '', $perusta_projekti);
	if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";

	$query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	//	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hyväksytyiksi
	$query = "select tunnusnippu from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu]";
	$result = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);

		$query = "UPDATE lasku SET alatila='T' where yhtio='$kukarow[yhtio]' and tunnusnippu = $row[tunnusnippu] and tunnus!='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	$aika=date("d.m.y @ G:i:s", time());
	echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';
}

// Hylätään tarjous
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS" and $muokkauslukko == "") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
		exit;
	}

	$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan sarjanumerolinkit
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'";
	$sres = mysql_query($query) or pupe_error($query);

	while ($srow = mysql_fetch_array($sres)) {
		if ($srow["varattu"] < 0) {
			// dellataan koko rivi jos sitä ei ole vielä myyty
			$query = "DELETE from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
			$sarjares = mysql_query($query) or pupe_error($query);

			if (mysql_affected_rows() == 0) {
				// merkataan osorivitunnus nollaksi
				$query = "UPDATE sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			// merktaan myyntirivitunnus nollaks
			if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F") {
				$query = "	DELETE FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$srow[tuoteno]'
							and myyntirivitunnus = '$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	UPDATE sarjanumeroseuranta
							SET myyntirivitunnus = 0
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$srow[tuoteno]'
							and myyntirivitunnus = '$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
	}

	//	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hylätyiksi *** tämän voisi varmaan tehdä myös kaikki kerralla? ***
	$query = "SELECT tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
	$abures = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($abures) > 0) {
		while ($row = mysql_fetch_array($abures)) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
			$result = mysql_query($query) or pupe_error($query);

			//Nollataan sarjanumerolinkit
			$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio='$kukarow[yhtio]'
							and tilausrivi.otunnus='$row[tunnus]'";
			$sres = mysql_query($query) or pupe_error($query);

			while ($srow = mysql_fetch_array($sres)) {
				if ($srow["varattu"] < 0) {
					// dellataan koko rivi jos sitä ei ole vielä myyty
					$query = "DELETE from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
					$sarjares = mysql_query($query) or pupe_error($query);

					if (mysql_affected_rows() == 0) {
						// merkataan osorivitunnus nollaksi
						$query = "UPDATE sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
				}
				else {
					// merkataan myyntirivitunnus nollaks
					if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F") {
						$query = "	DELETE FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$srow[tuoteno]'
									and myyntirivitunnus = '$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	UPDATE sarjanumeroseuranta
									SET myyntirivitunnus = 0
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$srow[tuoteno]'
									and myyntirivitunnus = '$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
				}
			}
		}
	}

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	$aika=date("d.m.y @ G:i:s", time());
	echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';
}

// Laskutetaan myyntitili
if ($kukarow["extranet"] == "" and $tee == "LASKUTAMYYNTITILI" and $muokkauslukko == "") {
	$tilatapa = "LASKUTA";
	require ("laskuta_myyntitilirivi.inc");
}

// Laitetaan myyntitili takaisin lepäämään
if ($kukarow["extranet"] == "" and $tee == "LEPAAMYYNTITILI" and $muokkauslukko == "") {
	$tilatapa = "LEPAA";
	require ("laskuta_myyntitilirivi.inc");
}

if ($tee == "MAKSUSOPIMUS") {
	require("maksusopimus.inc");
}

if ($tee == "LISAAKULUT") {
	require("lisaa_kulut.inc");
}

if(in_array($jarjesta, array("moveUp", "moveDown")) and $rivitunnus > 0) {

	if($laskurow["tunnusnippu"] > 0 and $toim != "TARJOUS") {
		$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '{$laskurow["tunnusnippu"]}' and tila IN ('L','G','E','V','W','N','R','A') and tunnusnippu>0";
		$result = mysql_query($query) or pupe_error($query);
		$toimrow = mysql_fetch_array($result);

		$tunnarit = "$toimrow[tunnukset]";
	}
	else {
		$tunnarit = $kukarow["kesken"];
	}
	$query = "	SELECT jarjestys, tunnus
				FROM tilausrivin_lisatiedot
				WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus='$rivitunnus'";
	$abures = mysql_query($query) or pupe_error($query);
	$aburow = mysql_fetch_array($abures);

	if($jarjesta == "moveUp") {
		$ehto = "and jarjestys<$aburow[jarjestys]";
		$j = "desc";
	}
	elseif($jarjesta == "moveDown") {
		$ehto = "and jarjestys>$aburow[jarjestys]";
		$j = "asc";
	}

	$query = "	SELECT jarjestys, tilausrivin_lisatiedot.tunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus $ehto
	 			WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tyyppi !='D' and otunnus IN ($tunnarit) and (perheid=0 or perheid=tilausrivi.tunnus)
				ORDER BY jarjestys $j
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);
	$kohderow = mysql_fetch_array($result);

	if($kohderow["jarjestys"]>0 and $kohderow["tunnus"] != $rivitunnus) {
		//	Kaikki OK vaihdetaan data päikseen
		$query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$kohderow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$aburow[tunnus]'";
		$updres=mysql_query($query) or pupe_error($query);

		$query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$aburow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kohderow[tunnus]'";
		$updres=mysql_query($query) or pupe_error($query);
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! riviä ei voi siirtää!")."</font><br>";
	}

	$tyhjenna 	= "JOO";
}

// Poistetaan tilaus
if ($tee == 'POISTA' and $muokkauslukko == "") {

	// poistetaan tilausrivit, mutta jätetään PUUTE rivit analyysejä varten...
	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var<>'P'";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan sarjanumerolinkit ja dellataan ostorivit
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta, tilausrivin_lisatiedot.tilausrivilinkki
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'";
	$sres = mysql_query($query) or pupe_error($query);

	while ($srow = mysql_fetch_array($sres)) {
		if ($srow["sarjanumeroseuranta"] != "") {
			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
				// merktaan siirtolistatunnus nollaks
				$query = "UPDATE sarjanumeroseuranta set siirtorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			elseif ($srow["varattu"] < 0) {
				// dellataan koko rivi jos sitä ei ole vielä myyty
				$query = "DELETE from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 0) {
					// merkataan osorivitunnus nollaksi
					$query = "UPDATE sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}
			else {
				// merktaan myyntirivitunnus nollaks
				if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F") {
					$query = "	DELETE FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}
		}

		// Onko tätä ostotilauksella?
		if ($srow["tilausrivilinkki"] > 0) {
			$query = "	UPDATE tilausrivi
						SET tyyppi = 'D'
						WHERE yhtio 	= '$kukarow[yhtio]'
						and tunnus  	= '$srow[tilausrivilinkki]'
						and tyyppi 		= 'O'
						and uusiotunnus = 0";
			$siirtores = mysql_query($query) or pupe_error($query);
		}
	}

	//Poistetaan maksusuunnitelma
	$query = "DELETE from maksupositio WHERE yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
	$result = mysql_query($query) or pupe_error($query);

	//Poistetaan rahtikrijat
	$query = "DELETE from rahtikirjat WHERE yhtio='$kukarow[yhtio]' and otsikkonro='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE lasku SET tila='D', alatila='L', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	if($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $toim != "TARJOUS" and $toim != "PROJEKTI") {

		$aika = date("d.m.y @ G:i:s", time());

		echo "<font class='message'>".t("Osatoimitus")." ($aika) $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";

		if($projektilla > 0 and ($laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"])) {
			$tilausnumero = $laskurow["tunnusnippu"];

			//	Hypätään takaisin otsikolle
			echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";

			if($projektilla > 0) {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
			}
			else {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=$toim&valitsetoimitus=$tilausnumero'>";
			}

			exit;
		}
		else {
			$tee				= '';
			$tilausnumero		= '';
			$laskurow			= '';
			$kukarow['kesken']	= '';
		}
	}
	else {

		if ($kukarow["extranet"] == "") {
			echo "<font class='message'>".t("Tilaus")." $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";
		}

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';

		if ($kukarow["extranet"] != "") {
			echo "<font class='head'>$otsikko</font><hr><br><br>";
			echo "<font class='message'>".t("Tilauksesi poistettiin")."!</font><br><br>";

			$tee = "SKIPPAAKAIKKI";
		}
	}

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
		$lopetus = str_replace('////','?', $lopetus);
		$lopetus = str_replace('//','&',  $lopetus);

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
		exit;
	}
}

//Lisätään tän asiakkaan valitut JT-rivit tälle tilaukselle
if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $muokkauslukko == "") {
	$tilaus_on_jo 	= "KYLLA";

	require("jtselaus.php");

	$tyhjenna 	= "JOO";
	$tee 		= "";
}

//Tyhjenntään syöttökentät
if (isset($tyhjenna)) {
	$tuoteno	= '';
	$kpl		= '';
	$var		= '';
	$hinta		= '';
	$netto		= '';
	$ale		= '';
	$rivitunnus	= '';
	$kommentti	= '';
	$kerayspvm	= '';
	$toimaika	= '';
	$paikka		= '';
	$paikat		= '';
	$alv		= '';
	$perheid 	= '';
	$perheid2  	= '';
	$tuotenimitys = '';
	$rivinumero = '';
}

if ($tee == "VALMIS" and $kateinen != '' and ($kukarow['kassamyyja'] != '' or $kukarow['dynaaminen_kassamyynti'] != '') and $kukarow['extranet'] == '') {

	if ($kassamyyja_kesken != 'ei' and !isset($seka)) {

		$query_maksuehto = " SELECT *
							 FROM maksuehto
							 WHERE yhtio='$kukarow[yhtio]' and kateinen != '' and kaytossa = '' and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$laskurow[maa]%')";
		$maksuehtores = mysql_query($query_maksuehto) or pupe_error($query_maksuehto);

		if (mysql_num_rows($maksuehtores) > 1) {
			echo "<table><tr><th>".t("Maksutapa").":</th>";

			while ($maksuehtorow = mysql_fetch_array($maksuehtores)) {
				echo "<form action='' method='post'>";
				echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='tee' value='VALMIS'>";
				echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
				echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
				echo "<input type='hidden' name='kateinen' value='$kateinen'>";
				echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
				echo "<td><input type='submit' value='{$maksuehtorow['teksti']} {$maksuehtorow['kassa_teksti']}'></td>";
				echo "</form>";
			}
	
			/*
			echo "<form action='' method='post'>";
			echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
			echo "<input type='hidden' name='seka' value='X'>";
			echo "<input type='hidden' name='kateinen' value='$kateinen'>";
			echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
			echo "<td><input type='submit' value='Seka'></td>";
			echo "</form></tr>";
			*/

			echo "</table>";
	
			exit;
		}
		else {
			echo "<form action='' method='post'>";
			echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
			echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
			echo "<input type='hidden' name='kateinen' value='$kateinen'>";
			echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
			echo "</form></tr>";
		}
		echo "</table>";
	}
/*
	elseif ($kassamyyja_kesken == 'ei' and $seka == 'X') {

		echo "<table><form action='' name='laskuri' method='post'>";

		echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tee' value='VALMIS'>";
		echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
		echo "<input type='hidden' name='kateinen' value='$kateinen'>";		
		echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
		echo "<input type='hidden' name='seka' id='seka' value='X'>";

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					function update_summa(kaikkiyhteensa) {
						
						kateinen = Number(document.getElementById('kateismaksu').value.replace(\",\",\".\"));
						pankki = Number(document.getElementById('pankkikortti').value.replace(\",\",\".\"));
						luotto = Number(document.getElementById('luottokortti').value.replace(\",\",\".\"));

						summa = kaikkiyhteensa - (kateinen + pankki + luotto);
						
						summa = Math.round(summa*100)/100;
						
						if (summa == 0 && (document.getElementById('kateismaksu').value != '' || document.getElementById('pankkikortti').value != '' || document.getElementById('luottokortti').value != '')) {
							summa = 0.00;
							document.getElementById('hyvaksy_nappi').disabled = false;
							document.getElementById('seka').value = 'kylla';
						} else {
							document.getElementById('hyvaksy_nappi').disabled = true;							
						}
						
						document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>'; 
					}
				-->
				</script>";

		echo "<tr><th>".t("Laskun loppusumma")."</th><td align='right'>$kaikkiyhteensa</td><td>$laskurow[valkoodi]</td></tr>";
		
		echo "<tr><td>".t("Käteisellä")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";

		echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyväksy")."' disabled></td></tr>";

		echo "</form><br><br>";

		$formi = "laskuri";
		$kentta = "kateismaksu";

		exit;
	}
*/	

} elseif ($tee == "VALMIS" and $kassamyyja_kesken == 'ei' and ($kukarow['kassamyyja'] != '' or $kukarow['dynaaminen_kassamyynti'] != '') and $kukarow['extranet'] == '') {
	
	$query_maksuehto = "UPDATE lasku SET maksuehto='$maksutapa', kassalipas='$kertakassa' WHERE yhtio='$kukarow[yhtio]' AND tunnus='$kukarow[kesken]'";
	$maksuehtores = mysql_query($query_maksuehto) or pupe_error($query_maksuehto);
	
}

// Tilaus valmis
if ($tee == "VALMIS" and ($muokkauslukko == "" or $toim == "PROJEKTI")) {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font><br>";
		exit;
	}

	// Tsekataan jos ollaan tehty asiakkaallevalmistus jossa ei ole yhtään valmistettavaa riviä
	$msiirto = "";

	if ($toim == "VALMISTAASIAKKAALLE") {
		$query = "	SELECT yhtio
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$kukarow[kesken]'
					and tyyppi in ('W','M','V')
					and varattu > 0";
		$sres  = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sres) == 0) {
			echo "<font class='message'> ".t("Ei valmistettavaa. Valmistus siirrettiin myyntipuolelle")."! </font><br>";
			
			if ($laskurow["alatila"] == "") {
				$utila = "N";
				$atila = "";
			}
			elseif ($laskurow["alatila"] == "J") {
				$utila = "N";
				$atila = "J";
			}
			else {
				$utila = "L";
				$atila = $laskurow["alatila"];
			}
						
			$query  = "	UPDATE lasku set
						tila 	= '$utila',
						alatila	= '$atila'
						where yhtio = '$kukarow[yhtio]'
						and tunnus = '$kukarow[kesken]'
						and tila = 'V'";
			$result = mysql_query($query) or pupe_error($query);

			$msiirto = "MYYNTI";
		}
	}

	// Tulostetaan tarjous
	if($kukarow["extranet"] == "" and $toim == "TARJOUS") {

		if (count($komento) == 0) {
			echo "<font class='head'>".t("Tarjous").":</font><hr><br>";

			$otunnus = $tilausnumero;
			$tulostimet[0] = "Tarjous";
			require("../inc/valitse_tulostin.inc");
		}

		require_once ('tulosta_tarjous.inc');
		tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli,  $tee);

		$query = "UPDATE lasku SET alatila='A' where yhtio='$kukarow[yhtio]' and alatila='' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		// Meillä voi olla versio..
		if($laskurow["tunnusnippu"] > 0) {
			$result = mysql_query($query) or pupe_error($query);

			$query  = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tunnus <= '$laskurow[tunnus]' and tila='T'";
			$result = mysql_query($query) or pupe_error($query);

			$tarjous = $laskurow["tunnusnippu"]."/".mysql_num_rows($result);
		}
		else {
			$tarjous = $laskurow["tunnus"];
		}

		//	Tarkastetaan onko käytössä tarjousseuranta vai, jos on näitä muistutuksia ei kirjata..
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'tilauskasittely/tarjousseuranta.php'
					and alanimi = ''";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result) == 0) {
			kalenteritapahtuma ("Memo", "Tarjous asiakkaalle", "Tarjous $tarjous tulostettu.\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]", $laskurow["liitostunnus"], "", $lasklisatied_row["yhteyshenkilo_tekninen"], $laskurow["tunnus"]);

			kalenteritapahtuma ("Muistutus", "Tarjous asiakkaalle", "Muista tarjous $tarjous", $laskurow["liitostunnus"], "K", $lasklisatied_row["yhteyshenkilo_tekninen"], $laskurow["tunnus"]);
		}

		// tilaus ei enää kesken...
		$query	= "UPDATE kuka set kesken=0 where yhtio='{$kukarow["yhtio"]}' and kuka='{$kukarow["kuka"]}'";
		$result = mysql_query($query) or pupe_error($query);

	}
	// Sisäinen työmääräys valmis
	elseif($kukarow["extranet"] == "" and $toim == "SIIRTOTYOMAARAYS") {
		require("../tyomaarays/tyomaarays.inc");
	}
	// Työmääräys valmis
	elseif ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "REKLAMAATIO")) {
		require("../tyomaarays/tyomaarays.inc");
	}

	// Siirtolista, myyntitili, valmistus valmis
	elseif ($kukarow["extranet"] == "" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") and $msiirto == "") {
		require ("tilaus-valmis-siirtolista.inc");
	}
	// Projekti, tällä ei ole mitään rivejä joten nollataan vaan muuttujat
	elseif ($toim == "PROJEKTI") {
		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';
	}
	// Myyntitilaus valmis
	else {
		//Jos käyttäjä on extranettaaja ja hän ostellut tuotteita useista eri maista niin laitetaan tilaus holdiin
		if ($kukarow["extranet"] != "" and $toimitetaan_ulkomaailta == "YES") {
			$kukarow["taso"] = 2;
		}

		//katotaan onko asiakkaalla yli 30 päivää vanhoja maksamattomia laskuja
		if ($kukarow['extranet'] != '' and ($kukarow['saatavat'] == 0 or $kukarow['saatavat'] == 2)) {
			$saaquery =	"SELECT
						lasku.ytunnus,
						sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 30, summa-saldo_maksettu, 0)) dd
						FROM lasku use index (yhtio_tila_mapvm)
						WHERE tila = 'U'
						AND alatila = 'X'
						AND mapvm = '0000-00-00'
						AND erpcm != '0000-00-00'
						AND lasku.ytunnus = '$laskurow[ytunnus]'
						AND lasku.yhtio = '$kukarow[yhtio]'
						GROUP BY 1
						ORDER BY 1";
			$saaresult = mysql_query($saaquery) or pupe_error($saaquery);
			$saarow = mysql_fetch_array($saaresult);

			//ja jos on niin ne siirretään tilaus holdiin
			if ($saarow['dd'] > 0) {
				$kukarow["taso"] = 2;
			}
		}

		// Extranetkäyttäjä jonka tilaukset on hyväksytettävä meidän myyjillä
		if ($kukarow["extranet"] != "" and $kukarow["taso"] == 2) {
			$query  = "	UPDATE lasku set
						tila = 'N',
						alatila='F'
						where yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);


			// tilaus ei enää kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

		}
		else {
			
			//Luodaan valituista riveistä suoraan normaali ostotilaus
			if($kukarow["extranet"] == "" and $yhtiorow["tee_osto_myyntitilaukselta"] != '') {
				require("tilauksesta_ostotilaus.inc");

				//	Jos halutaan tehdä tilauksesta ostotilauksia, niin tehdään kaikista ostotilaus
				if($tee_osto!="") {
					$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'KAIKKI');
				}
				else {
					$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'T');
					$tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"],'U');
				}

				if ($tilauksesta_ostotilaus != '') echo "$tilauksesta_ostotilaus<br><br>";
			}

			// katsotaan ollaanko tehty JT-supereita..
			require("jt_super.inc");
			$jt_super = jt_super($kukarow["kesken"]);

			if ($kukarow["extranet"] != "") {
				echo "$jt_super<br><br>";

				//Pyydetään tilaus-valmista olla echomatta mitään
				$silent = "SILENT";
			}

			// tulostetaan lähetteet ja tilausvahvistukset tai sisäinen lasku..
			require("tilaus-valmis.inc");
		}
	}

	// ollaan käsitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
	if($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $toim != "TARJOUS") {

		$aika=date("d.m.y @ G:i:s", time());
		echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

		if($projektilla > 0 and $laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"]) {
			$tilausnumero = $laskurow["tunnusnippu"];

			//	Päiviteään aina myös projektin aktiiviseksi jos se on ollut kesken
			$query = "	UPDATE lasku SET
							alatila = 'A'
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tunnusnippu > 0 and tila = 'R' and alatila= ''";
			$updres = mysql_query($query) or pupe_error($query);

			//	Hypätään takaisin otsikolle
			echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";

			echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";

			exit;
		}
		else {
			$tee				= '';
			$tilausnumero		= '';
			$laskurow			= '';
			$kukarow['kesken']	= '';
		}
	}
	else {
		if ($kukarow["extranet"] == "") {
			$aika=date("d.m.y @ G:i:s", time());
			echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

			if (($kukarow['kassamyyja'] != '' or $kukarow['dynaaminen_kassamyynti'] != '') and $kateinen != '' and $kukarow['extranet'] == '') {
				echo "	<script type='text/javascript' language='JavaScript'>
						<!--
							function update_summa(kaikkiyhteensa) {
								kateinen = document.getElementById('kateisraha').value.replace(\",\",\".\");
								summa = 0;
								if (document.getElementById('kateisraha').value != '') {
									summa = kateinen - kaikkiyhteensa;
								}
								summa = Math.round(summa*100)/100;
								document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>'; 
							}
						-->
						</script>";
				echo "<table><form name='laskuri'>";
				
				if (!isset($kateismaksu['kateinen']) or $kateismaksu['kateinen'] == '') {
					$yhteensa_teksti = t("Yhteensä");
				} else {
					$yhteensa_teksti = t("Käteinen");
					$kaikkiyhteensa = $kateismaksu['kateinen'];
				}
				
				echo "<tr><th>$yhteensa_teksti</th><td align='right'>$kaikkiyhteensa</td><td>$laskurow[valkoodi]</td></tr>";
				echo "<tr><th>".t("Annettu")."</th><td><input size='7' autocomplete='off' type='text' id='kateisraha' name='kateisraha' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
				echo "<tr><th>".t("Takaisin")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$laskurow[valkoodi]</td></tr>";
				echo "</form></table><br><br>";
				$formi = "laskuri";
				$kentta = "kateisraha";
			}
		}

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';

		if ($kukarow["extranet"] != "") {			
			echo "<font class='head'>$otsikko</font><hr><br><br>";
			echo "<font class='message'>".t("Tilaus valmis. Kiitos tilauksestasi")."!</font><br><br>";

			$tee = "SKIPPAAKAIKKI";
		}
	}

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
		$lopetus = str_replace('////','?', $lopetus);
		$lopetus = str_replace('//','&',  $lopetus);

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
		exit;
	}
}

if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "REKLAMAATIO") and ($tee == "VAHINKO" or $tee == "LEPAA")) {
	require("../tyomaarays/tyomaarays.inc");
}

//Voidaan tietyissä tapauksissa kopsata tästä suoraan uusi tilaus
if ($uusitoimitus != "") {
	$toim 				= $uusitoimitus;
	$kopioitava_otsikko = $laskurow["tunnusnippu"];
	$asiakasid 			= $laskurow["liitostunnus"];
	$tee 				= "OTSIK";
	$tiedot_laskulta	= "YES";
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or ($toim != "PIKATILAUS" and $laskurow["liitostunnus"] == ''))) {

	//Tämä jotta myös rivisyötön alkuhomma toimisi
	$tee = "OTSIK";

	if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
		require("otsik_siirtolista.inc");
	}
	else {
		require ('otsik.inc');
	}

	//Tässä halutaan jo hakea uuden tilauksen tiedot
	$query   	= "	SELECT *
					from lasku
					where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow   = mysql_fetch_array($result);

	$kaytiin_otsikolla = "NOJOO!";
}

//lisätään rivejä tiedostosta
if ($tee == 'mikrotila' or $tee == 'file') {

	if ($kukarow["extranet"] == "" and $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
		require('mikrotilaus_siirtolista.inc');
	}
	else {
		require('mikrotilaus.inc');
	}

	if($tee == 'Y') {
		$tee = "";
	}
}

// Tehdään rahoituslaskuelma
if ($tee == 'osamaksusoppari') {
	require('osamaksusoppari.inc');
}

// Tehdään vakuutushakemus
if ($tee == 'vakuutushakemus') {
	require('vakuutushakemus.inc');
}

if ($kukarow["extranet"] == "" and $tee == 'jyvita') {
	require("jyvita_riveille.inc");
}

// näytetään tilaus-ruutu...
if ($tee == '') {
	$focus = "tuotenumero";
	$formi = "tilaus";

	echo "<font class='head'>$otsikko</font><hr>";

	//katsotaan että kukarow kesken ja $kukarow[kesken] stemmaavat keskenään
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").". $tilausnumero / $kukarow[kesken]<br><br><br>";
		exit;
	}
	if ($kukarow['kesken'] != '0') {
		$tilausnumero=$kukarow['kesken'];
	}

	// Tässä päivitetään 'pikaotsikkoa' jos kenttiin on jotain syötetty
	if ($pikaotsikko=='TRUE' and $kukarow['extranet'] == '' and ($toimitustapa != '' or $tilausvahvistus != '' or $viesti != '' or $myyjanro != '' or $myyja != '' or $maksutapa != '')) {
		if ($myyjanro != '') {
			$apuqu = "	SELECT *
						from kuka use index (yhtio_myyja)
						where yhtio='$kukarow[yhtio]' and myyja='$myyjanro'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);

			if (mysql_num_rows($meapu)==1) {
				$apuro = mysql_fetch_array($meapu);
				$myyja = $apuro['tunnus'];
			}
			elseif (mysql_num_rows($meapu)>1) {
				echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
			}
		}

		if ($maksutapa != '') {
			$laskurow["maksuehto"] = $maksutapa;
		}

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);

		$kassalipas = "";

		if (mysql_num_rows($meapu) == 1 and $toimitustapa != '') {
			$meapurow = mysql_fetch_array($meapu);

			// jos kyseessä oli käteinen
			if ($meapurow["kateinen"] != "") {
				// haetaan toimitustavan tiedot tarkastuksia varten
				$apuqu2 = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
				$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);
				$meapu2row = mysql_fetch_array($meapu2);

				// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
				if ($meapu2row["nouto"] == "") {
					$apuqu = "SELECT * from toimitustapa where yhtio = '$kukarow[yhtio]' and nouto != '' order by jarjestys limit 1";
					$meapu = mysql_query($apuqu) or pupe_error($apuqu);
					$apuro = mysql_fetch_array($meapu);
					$toimitustapa = $apuro['selite'];

					echo "<font class='error'>".t("Toimitustapa on oltava nouto, koska maksuehto on käteinen")."!</font><br><br>";
				}
				
				$kassalipas = $kukarow["kassamyyja"];
			}
		}

		$query  = "	UPDATE lasku SET
					toimitustapa	= '$toimitustapa',
					viesti 			= '$viesti',
					tilausvahvistus = '$tilausvahvistus',
					myyja			= '$myyja',
					kassalipas		= '$kassalipas',
					maksuehto		= '$laskurow[maksuehto]'
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		//Haetaan laskurow uudestaan
		$query   	= "	SELECT *
						from lasku
						where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$laskurow   = mysql_fetch_array($result);

		if($yhtiorow["tilauksen_kohteet"] == "K") {
			$query 	= "	SELECT *
						from laskun_lisatiedot
						where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
			$result  	= mysql_query($query) or pupe_error($query);
			$lasklisatied_row  = mysql_fetch_array($result);
		}

	}

 	// jos asiakasnumero on annettu
	if ($laskurow["liitostunnus"] > 0) {
		if($yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI")) and 1==1) {
			$jarjlisa="<td class='back' width='10px'>&nbsp;</td>";
		}
		else {
			$jarjlisa="";
		}

		echo "<table>";
		echo "<tr>$jarjlisa";

		if ($kukarow["extranet"] == "") {
			echo "	<td class='back'><form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='OTSIK'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tiedot_laskulta' value='YES'>
					<input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
					<input type='submit' ACCESSKEY='m' value='".t("Muuta Otsikkoa")."'>
					</form></td>";
		}


		// JT-rivit näytetään vain jos siihen on oikeus!
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'tilauskasittely/jtselaus.php'
					and alanimi = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			
			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}
			
			$query  = "	SELECT count(*) kpl 
						from tilausrivi USE INDEX (yhtio_tyyppi_var)
						JOIN lasku USE INDEX (primary) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$laskurow[liitostunnus]')
						WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
						and tilausrivi.tyyppi 			in ('L','G')
						and tilausrivi.var 				= 'J'
						and tilausrivi.keratty 			= ''
						and tilausrivi.uusiotunnus 		= 0
						and tilausrivi.kpl 				= 0
						and tilausrivi.jt $lisavarattu	> 0";
			$jtapuresult = mysql_query($query) or pupe_error($query);
			$jtapurow = mysql_fetch_array($jtapuresult);

			if ($jtapurow["kpl"] > 0) {
				echo "	<td class='back'><form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

				if ($jt_kayttoliittyma == "kylla") {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value=''>
							<input type='submit' value='".t("Piilota JT-rivit")."'>";
				}
				else {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value='kylla'>
							<input type='submit' value='".t("Näytä JT-rivit")."'>";
				}
				echo "</form></td>";
			}
		}

		// otetaan maksuehto selville.. jaksotus muuttaa asioita
		$query = " 	select *
					from maksuehto
					where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==1) {
			$maksuehtorow = mysql_fetch_array($result);

			if ($maksuehtorow['jaksotettu']!='') {
				echo "<td class='back'><form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='MAKSUSOPIMUS'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='projektilla' value='$projektilla'>
				<input type='Submit' value='".t("Maksusuunnitelma")."'>
				</form>
				</td>
				";
			}
		}

		//	Tämä koko toiminto pitänee taklata paremmin esim. perheillä..
		if(file_exists("lisaa_kulut.inc")) {
			echo "<td class='back'>
					<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='LISAAKULUT'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Lisaa kulut")."'>
					</form>
				</td>";

			/*
			echo "<form action = '../tilausmemo.php' method='post'>
			<input type='hidden' name='tilaustunnus' value='$tilausnumero'>
			<td class='back'><input type='Submit' value='".t("Tilausmemo")."'></td>
			</form>";
			*/

		}

		if($toim == "TARJOUS") {
			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'tilauskasittely/tarjousseuranta.php'
						and alanimi = ''";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) > 0) {
				echo "<td class='back'>
						<form action = 'tarjousseuranta.php' method='post'>
							<input type='hidden' name='tarjous' value='$tilausnumero'>
							<input type='hidden' name='tee' value='TARJOUSKALENTERI'>
							<input type='hidden' name='lopetus' value='tilaus_myynti.php?toim=$toim&projektilla=$projektilla&valitsetoimitus=$tilausnumero'>
							<input type='Submit' value='".t("Tarjousseuranta")."'>
						</form>
					</td>";
			}			
		}

		echo "	<td class='back'>
					<form action='tuote_selaus_haku.php' method='post'>
						<input type='hidden' name='toim_kutsu' value='$toim'>
						<input type='submit' value='".t("Selaa tuotteita")."'>
					</form>
				</td>";

		// aivan karseeta, mutta joskus pitää olla näin asiakasystävällinen... toivottavasti ei häiritse ketään
		if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "artr") {
			echo 	"<td class='back'>
						<form action='../yhteensopivuus.php' method='post'>
						<input type='hidden' name='toim_kutsu' value='$toim'>
						<input type='submit' value='".t("Malliselain")."'>
						</form>
					</td>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == 'REKLAMAATIO')) {
			echo "	<td class='back'>
					<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='VAHINKO'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Lisää vahinkotiedot")."'>
					</form>
					</td>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == 'REKLAMAATIO')) {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tila' value='SYOTASMS'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<td class='back'><input type='Submit' value='".t("Lähetä tekstiviesti")."'></td>
					</form>";
		}

		echo "<td class='back'>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='mikrotila'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='projektilla' value='$projektilla'>";
		if ($toim != "VALMISTAVARASTOON") {
			echo "<input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'>";
		}
		else {
			echo "<input type='Submit' value='".t("Lue valmistusrivit tiedostosta")."'>";
		}

		echo "</form></td>";

		if ($kukarow["extranet"] == "" and ($toim == "PIKATILAUS" or $toim == "RIVISYOTTO") and $yhtiorow["rahtikirjojen_esisyotto"] == "M") {
			echo "<td class='back'>
					<form action='../rahtikirja.php' method='post'>
						<input type='hidden' name='tee' value=''>
						<input type='hidden' name='toim' value='lisaa'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='rahtikirjan_esisyotto' value='$toim'>
						<input type='hidden' name='id' value='$tilausnumero'>
						<input type='hidden' name='rakirno' value='$tilausnumero'>
						<input type='hidden' name='tunnukset' value='$tilausnumero'>
						<input type='Submit' value='".t("Rahtikirjan esisyöttö")."'>
					</form>
				</td>";
		}
		
		if($yhtiorow["myyntitilauksen_liitteet"] != "") {
			
			$queryoik = "SELECT tunnus from oikeu where nimi like '%liitetiedostot.php' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) == 1) {

				if($laskurow["tunnusnippu"] > 0) {
					$id = $laskurow["tunnusnippu"];
				}
				else {
					$id = $laskurow["tunnus"];
				}

				echo "<td class='back'>
						<form method='get' action='../liitetiedostot.php'>
							<input type='hidden' name='id' value='$id'>
							<input type='hidden' name='liitos' value='lasku'>
							<input type='hidden' name='lopetus' value='".urlencode("tilauskasittely/tilaus_myynti.php?toim=$toim&projektilla=$projektilla&valitsetoimitus=$tilausnumero")."'>
							<input type='submit' value='" . t('Tilauksen liitetiedostot')."'>
						</form>
					</td>";

			}			
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and file_exists("osamaksusoppari.inc")) {
			echo "<td class='back'>
					<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='osamaksusoppari'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Rahoituslaskelma")."'>
					</form>
				</td>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and file_exists("vakuutushakemus.inc")) {
			echo "<td class='back'>
					<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='vakuutushakemus'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'>
					</form>
				</td>";
		}

		echo "</tr></table><br>\n";
	}

	//Oletetaan, että tilaus on ok, $tilausok muuttujaa summataan alempana jos jotain virheitä ilmenee
	$tilausok = 0;
	$sarjapuuttuu = 0;

	$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);
	$meapurow = mysql_fetch_array($meapu);

	if ($laskurow["liitostunnus"] > 0 and $meapurow["kateinen"] == "" and ($laskurow["nimi"] == '' or $laskurow["osoite"] == '' or $laskurow["postino"] == '' or $laskurow["postitp"] == '')) {
		if ($toim != 'VALMISTAVARASTOON' and $toim != 'SIIRTOLISTA' and $toim != 'SIIRTOTYOMAARAYS' and $toim != 'TARJOUS') {
			echo "<font class='error'>".t("VIRHE: Tilauksen laskutusosoitteen tiedot ovat puutteelliset")."!</font><br><br>";
			$tilausok++;
		}
	}

	// tässä alotellaan koko formi.. tämä pitää kirjottaa aina
	echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='lopetus' value='$lopetus'>
			<input type='hidden' name='projektilla' value='$projektilla'>";

	// kirjoitellaan otsikko
	echo "<table>";

	// jos asiakasnumero on annettu
	if ($laskurow["liitostunnus"] > 0) {

		echo "<input type='hidden' name='pikaotsikko' value='TRUE'>";

		echo "<tr>$jarjlisa";

		if ($toim == "VALMISTAVARASTOON") {
			echo "<th align='left'>".t("Varastot").":</th>";

			echo "<td>$laskurow[ytunnus] $laskurow[nimi]</td>";

			echo "<th align='left'>&nbsp;</th>";
			echo "<td>&nbsp;</td>";
		}
		else {
			echo "<th align='left'>".t("Asiakas").":</th>";


			if ($kukarow["extranet"] == "") {
				echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$laskurow[ytunnus]&asiakasid=$laskurow[liitostunnus]&from=$toim'>$laskurow[ytunnus] $laskurow[nimi]</a><br>$laskurow[toim_nimi]</td>";
 			}
			else {
				echo "<td>$laskurow[ytunnus] $laskurow[nimi]<br>$laskurow[toim_nimi]</td>";
			}

			echo "<th align='left'>".t("Toimitustapa").":</th>";

			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = " and (extranet = 'K' or selite = '$laskurow[toimitustapa]') ";
			}
			else {
				$extralisa = " and (extranet = '' or selite = '$laskurow[toimitustapa]') ";
			}

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]' $extralisa
						and (sallitut_maat = '' or sallitut_maat like '%$laskurow[toim_maa]%')
						ORDER BY jarjestys,selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='toimitustapa' onchange='submit()' $state>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row["selite"] == $laskurow["toimitustapa"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>".asana('TOIMITUSTAPA_',$row['selite'])."";
			}
			echo "</select>";


			if ($kukarow["extranet"] == "") {
				$query = "	SELECT *
							FROM rahtisopimukset
							WHERE toimitustapa = '$laskurow[toimitustapa]'
							and  ytunnus = '$laskurow[ytunnus]'";
				$pahsopres = mysql_query($query) or pupe_error($query);
				$rahsoprow = mysql_fetch_array($pahsopres);

				if ($rahsoprow["tunnus"] > 0) {
					$ylisa = "&tunnus=$rahsoprow[tunnus]";
				}
				else {
					$ylisa = "&uusi=1&ytunnus=$laskurow[ytunnus]&toimitustapa=$laskurow[toimitustapa]";
					$rahsoprow["rahtisopimus"] = t("Lisää rahtisopimus");
				}

				echo " <a href='".$palvelin2."yllapito.php?toim=rahtisopimukset$ylisa&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>$rahsoprow[rahtisopimus]</a>";
			}

			echo "</td>";
		}

		echo "</tr>";
		echo "<tr>$jarjlisa";
		echo "<th align='left'>".t("Tilausnumero").":</th>";

		if ($laskurow["tunnusnippu"] > 0) {

			echo "<td><select Style=\"width: 200px; font-size: 8pt; padding: 0\" name='valitsetoimitus' onchange='submit();'>";

			// Listataan kaikki toimitukset ja liitetään tarjous mukaan jos se tiedetään
			$hakulisa = "";

			if($lasklisatied_row["tunnusnippu_tarjous"] > 0) {
				$hakulisa =" or (lasku.tunnusnippu = '$lasklisatied_row[tunnusnippu_tarjous]' and tila='T' and alatila='B')";
			}
			elseif($projektilla > 0 and $laskurow["tunnusnippu"] != $projektilla) {
				$hakulisa =" or lasku.tunnusnippu = '$projektilla'";
			}

			$vquery = " SELECT count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";

			$query = " 	SELECT tila, alatila, varastopaikat.nimitys varasto, lasku.toimaika, if(tila='T',if(tunnusnippu>0,concat(lasku.tunnusnippu,'/',($vquery)), concat(lasku.tunnusnippu,'/1')),lasku.tunnus) tilaus, lasku.tunnus tunnus
						FROM lasku
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (lasku.tunnusnippu = '$laskurow[tunnusnippu]' $hakulisa)
						and lasku.tila IN ('L','N','A','T','G','S','V','W','O','R')
						and if('$tila' = 'MUUTA', alatila != 'X', lasku.tunnus=lasku.tunnus)
						GROUP BY lasku.tunnus";
			$toimres = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($toimres) > 0) {

				while($row = mysql_fetch_array($toimres)) {

					$sel = "";
					if($row["tunnus"] == $kukarow["kesken"]) {
						$sel = "selected";
					}

					$laskutyyppi = $row["tila"];
					$alatila 	 = $row["alatila"];
				 	require ("../inc/laskutyyppi.inc");

					if($row["varasto"] == "") $row["varasto"] = "oletus";

					echo "<option value ='$row[tunnus]' $sel>".t("$laskutyyppi")." $row[tilaus] ".t("$alatila")." - $row[varasto]</option>";
				}
			}
			echo "<optgroup label='".t("Perusta uusi")."'>";

			if($toim == "TARJOUS" and $laskurow["alatila"] != "B") {
				echo "<option value='TARJOUS'>".T("Tarjouksen versio")."</option>";
			}
			else {

				if ($toim == "PIKATILAUS") {
					echo "<option value='PIKATILAUS'>".T("Toimitus")."</option>";
				}
				else {
					echo "<option value='RIVISYOTTO'>".T("Toimitus")."</option>";
				}

				if ($projektilla != '') {
					echo "<option value='TYOMAARAYS'>".T("Työmääräys")."</option>";
					echo "<option value='REKLAMAATIO'>".T("Reklamaatio")."</option>";
					echo "<option value='VALMISTAVARASTOON'>".T("Valmistus")."</option>";
					echo "<option value='SIIRTOLISTA'>".T("Siirtolista")."</option>";
				}
			}

			echo "</optgroup></select>";
		}
		elseif ($yhtiorow["myyntitilaus_osatoimitus"] == "K" and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS")) {
			echo "<td><select name='luotunnusnippu' onchange='submit();'>";
			echo "<option value =''>$kukarow[kesken]</option>";
			echo "<option value ='$kukarow[kesken]'>".t("Tee osatoimitus")."</option>";
			echo "</select></td>";
		}
		else {
			echo "<td>$kukarow[kesken]</td>";
		}

		echo "<th>".t("Tilausviite").":</th><td>";
		echo "<input type='text' size='30' name='viesti' value='$laskurow[viesti]' $state><input type='submit' value='".t("Tallenna")."' $state></td></tr>\n";

		echo "<tr>$jarjlisa";

		if ($toim != "VALMISTAVARASTOON") {
			echo "<th>".t("Tilausvahvistus").":</th>";
		}
		else {
			echo "<th> </th>";
		}

		if ($toim != "VALMISTAVARASTOON") {
			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = "  and avainsana.selite not like '%E%' and avainsana.selite not like '%O%' ";

				if ($kukarow['hinnat'] == 1) {
					$hinnatlisa = " and avainsana.selite not like '1%' ";
				}
			}

			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana use index (yhtio_laji_selite)
						".avain('join','TV_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'TV' $extralisa $hinnatlisa
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='tilausvahvistus' onchange='submit()' $state>";
			echo "<option value=' '>".t("Ei Vahvistusta")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[0]== $laskurow["tilausvahvistus"]) $sel = 'selected';
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
		}
		else {
			echo "<td>&nbsp;</td>";
		}

		if ($kukarow["extranet"] == "") {
			if ($toim != "VALMISTAVARASTOON") {
				echo "<th align='left'>".t("Myyjänro").":</th>";
			}
			else {
				echo "<th align='left'>".t("Laatija").":</th>";
			}

			echo "<td><input type='text' name='myyjanro' size='8' $state> tai ";
			echo "<select name='myyja' onchange='submit()' $state>";

			$query = "	SELECT tunnus, kuka, nimi, myyja, asema
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and (extranet = '' or tunnus='$laskurow[myyja]')
						ORDER BY nimi";
			$yresult = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($yresult)) {
				$sel = "";
				if ($laskurow['myyja'] == '' or $laskurow['myyja'] == 0) {
					if ($row['nimi'] == $kukarow['nimi']) {
						$sel = 'selected';
					}
				}
				else {
					if ($row['tunnus'] == $laskurow['myyja']) {
						$sel = 'selected';
					}
				}
				echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			echo "</select></td></tr>";

			$query = "	SELECT a.fakta, l.ytunnus, round(a.luottoraja,0) luottoraja, a.luokka
						FROM asiakas a, lasku l
						WHERE l.tunnus='$kukarow[kesken]' and l.yhtio='$kukarow[yhtio]' and a.yhtio = l.yhtio and a.tunnus = l.liitostunnus";
			$faktaresult = mysql_query($query) or pupe_error($query);
			$faktarow = mysql_fetch_array($faktaresult);

			if ($toim != 'VALMISTAVARASTOON') {
				echo "<tr>$jarjlisa<th>".t("Asiakasfakta").":</th><td colspan='3'>";

				//jos asiakkaalla on luokka K niin se on myyntikiellossa ja siitä herjataan
				if ($faktarow["luokka"]== 'K') {
					echo "<font class='error'>".t("HUOM!!!!!! Asiakas on myyntikiellossa")."!!!!!<br></font>";
				}

				echo "<strong>$faktarow[fakta]</strong>&nbsp;</td></tr>\n";
			}
		}
		else {
			echo "<input type='hidden' size='30' name='myyjanro' value='$laskurow[myyja]'>";
			
			echo "</tr>";
		}
	}
	elseif ($kukarow["extranet"] == "") {
		// asiakasnumeroa ei ole vielä annettu, näytetään täyttökentät
		if ($kukarow["oletus_asiakas"] != 0) {
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$extra_asiakas = mysql_fetch_array($result);
				$yt 	= $extra_asiakas["ytunnus"];
			}
		}

		if ($kukarow["myyja"] != 0) {
			$my = $kukarow["myyja"];
		}

		echo "<tr>$jarjlisa
			<th align='left'>".t("Asiakas")."</th>
			<td><input type='text' size='10' maxlength='10' name='syotetty_ytunnus' value='$yt'></td>
			</tr>";
		echo "<tr>$jarjlisa
			<th align='left'>".t("Myyjänro")."</th>
			<td><input type='text' size='10' maxlength='10' name='myyjanro' value='$my'></td>
			</tr>";
	}

	echo "</table>";


	//Näytetäänko asiakkaan saatavat!
	$query  = "	SELECT yhtio
				FROM tilausrivi
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus = '$kukarow[kesken]'";
	$numres = mysql_query($query) or pupe_error($query);

	if ($kukarow['extranet'] == '' and $kukarow['kassamyyja'] == '' and $laskurow['liitostunnus'] > 0 and ($kaytiin_otsikolla == "NOJOO!" or mysql_num_rows($numres) == 0) and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {

		echo "<br>";
		$sytunnus 	 = $laskurow['ytunnus'];
		$eiliittymaa = 'ON';

		require ("../raportit/saatanat.php");

		if ($ylikolkyt > 0) {
			echo "	<table>
					<tr>$jarjlisa
					<td class='back' align = 'left'><font class='error'>".t("HUOM!!!!!! Asiakkaalla on yli 30 päivää sitten erääntyneitä laskuja, olkaa ystävällinen ja ottakaa yhteyttä myyntireskontran hoitajaan")."!!!!!<br></font>$menuset</td>
					</tr>
					<tr>$jarjlisa
					<td class='back'><hr></td>
					</tr>
					</table>";
		}


		if ($yhtiorow["myyntitilaus_asiakasmemo"] == "K") {
			echo "<br>";
			$ytunnus	= $laskurow['ytunnus'];
			$asiakasid  = $laskurow['liitostunnus'];
			require ("../crm/asiakasmemo.php");
		}
	}

	echo "<br>";

	if ($smsnumero != "" and strlen("smsviesti") > 0) {

		if (strlen($smsviesti) > 160) {
			echo "<font class='error'>VIRHE: Tekstiviestin maksimipituus on 160 merkkiä!</font><br>";
			$tila = "SYOTASMS";
		}

		$smsnumero = str_replace ("-", "", $smsnumero);
		$ok = 1;

		// Käytäjälle lähetetään tekstiviestimuistutus
		if ($smsnumero != '' and strlen($smsviesti) > 0 and strlen($smsviesti) < 160 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {

			$smsviesti = urlencode($smsviesti);

			$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$smsnumero&viesti=$smsviesti");
			$smsviesti = urldecode($smsviesti);

			if (trim($retval) == "0") {
				$ok = 0;

				$kysely = "	INSERT INTO kalenteri
							SET tapa 		= '".t("Teksiviesti")."',
							asiakas  		= '$laskurow[ytunnus]',
							liitostunnus 	= '$laskurow[liitostunnus]',
							kuka     		= '$kukarow[kuka]',
							yhtio    		= '$kukarow[yhtio]',
							tyyppi   		= 'Memo',
							pvmalku  		= now(),
							kentta01 		= '$smsnumero\n$smsviesti',
							laatija			= '$kukarow[kuka]',
							luontiaika		= now()";
				$result = mysql_query($kysely) or pupe_error($kysely);
			}

		}

		if ($ok == 1) {
			echo "<font class='error'>VIRHE: Tekstiviestin lähetys epäonnistui! $retval</font><br><br>";
		}

		if ($ok == 0) {
			echo "<font class='message'>Tekstiviestimuistutus lehetetään!</font><br><br>";
		}
	}
	if ($tila == "SYOTASMS") {

		$query  = "	SELECT gsm
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus 	= '$laskurow[liitostunnus]'";
		$numres = mysql_query($query) or pupe_error($query);
		$asiakr = mysql_fetch_array($numres);

		if ($smsviesti == "") {
			$smsviesti = "\n\n\nTerv. ".$kukarow["nimi"]."\n".$yhtiorow["nimi"];
		}

		echo "<table>
				<tr>
					<th>Puh.</th>
					<td><input type='text' size='20' name='smsnumero' value='$asiakr[gsm]'></td>
				</tr>
				<tr>
					<th>Viesti</th>
					<td><textarea name='smsviesti' cols='45' rows='6' wrap='soft'>$smsviesti</textarea></td>
					<td class='back' valign='bottom'><input type='submit' value = 'Lähetä'></td>
				</tr>
			</table>
			<br>";
	}



	if($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		$tee_tyomaarays = "MAARAAIKAISHUOLLOT";
		//require('../tyomaarays/tyomaarays.inc');
	}

	//Kuitataan OK-var riville
	if ($kukarow["extranet"] == "" and $tila == "OOKOOAA") {
		$query = "	UPDATE tilausrivi
					SET var2 = 'OK'
					WHERE tunnus = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tapa 		= "";
		$rivitunnus = "";
	}

	if ($kukarow["extranet"] == "" and $tila == "LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS") {
		$query = "	UPDATE tilausrivin_lisatiedot
					SET osto_vai_hyvitys 	= '$osto_vai_hyvitys',
					muutospvm				= now(),
					muuttaja				= '$kukarow[kuka]'
					WHERE yhtio	= '$kukarow[yhtio]'
					and tilausrivitunnus = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tila 		= "";
		$rivitunnus = "";
		$rivitunnus = "";
	}

	//Muokataan tilausrivin lisätietoa
	if ($kukarow["extranet"] == "" and $tila == "LISATIETOJA_RIVILLE") {

		//	Mitä laitellaan??
		if(isset($asiakkaan_positio)) {
			$lisaalisa = " asiakkaan_positio = '$asiakkaan_positio',";
		}
		else {
			$lisaalisa = " positio = '$positio',";
		}

		$query = "	SELECT tilausrivi.tunnus, tuote.vaaditaan_kpl2, if(tilausrivin_lisatiedot.pituus>0, tilausrivi.hinta/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.hinta) yksikkohinta
					FROM tilausrivi use index (yhtio_otunnus)
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					and (tilausrivi.tunnus = '$rivitunnus' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$rivitunnus' and tilausrivin_lisatiedot.ei_nayteta = 'P') or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$rivitunnus' and tilausrivin_lisatiedot.ei_nayteta = 'P'))";
		$lapsires = mysql_query($query) or pupe_error($query);

		while($lapsi = mysql_fetch_array($lapsires)) {
			$query = "	UPDATE tilausrivin_lisatiedot
						SET $lisaalisa
						muutospvm				= now(),
						muuttaja				= '$kukarow[kuka]'
						WHERE yhtio			 = '$kukarow[yhtio]'
						and tilausrivitunnus = '$lapsi[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			//	Korjataan tuotteen yksikköhinta.. tuo em homma on se tapa jolla se kuuluisi tehdä, mutta nyt käytönnössä se on aika vaikea toteuttaa..
			if($lapsi["vaaditaan_kpl2"] == "P") {

				$query  = "	SELECT pituus
							FROM asiakkaan_positio
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tunnus = '$asiakkaan_positio'";
				$posres = mysql_query($query) or pupe_error($query);
				$posrow = mysql_fetch_array($posres);
				if((int)$posrow["pituus"] == 0) {
					$posrow["pituus"] = 1000;
				}
				$uhinta = round(($posrow["pituus"] * $lapsi["yksikkohinta"])/1000, $yhtiorow['hintapyoristys']);

				$query = "	UPDATE tilausrivi
							SET hinta = '$uhinta'
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '{$lapsi["tunnus"]}'";
				$updre = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tilausrivin_lisatiedot
							SET pituus 	= '{$posrow["pituus"]}',
							muutospvm	= now(),
							muuttaja	= '$kukarow[kuka]'
							WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus = '{$lapsi["tunnus"]}'";
				$updre = mysql_query($query) or pupe_error($query);

			}
		}

		$tila 		= "";
		$rivitunnus = "";
		$positio 	= "";
		$lisaalisa 	= "";
	}

	if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
		//Päivitetään isän perheid jotta voidaan lisätä lisää lisävarusteita
		if ($spessuceissi == "OK") {
			$xperheidkaks = -1;
		}
		else {
			$xperheidkaks =  0;
		}

		$query = "	UPDATE tilausrivi set
					perheid2	= $xperheidkaks
					where yhtio = '$kukarow[yhtio]'
					and tunnus 	= '$rivitunnus'
					LIMIT 1";
		$updres = mysql_query($query) or pupe_error($query);

		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
	}

	if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
		$tilatapa = "PAIVITA";

		require("laskuta_myyntitilirivi.inc");
	}

	// ollaan muokkaamassa rivin tietoja, haetaan rivin tiedot ja poistetaan rivi..
	if ($tila == 'MUUTA') {

		$query	= "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (PRIMARY)
					LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					and tilausrivi.tunnus  = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {

			$tilausrivi = mysql_fetch_array($result);

			// Poistetaan muokattava tilausrivi
			$query = "	DELETE FROM tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			// Tehdään pari juttua jos tuote on sarjanumeroseurannassa
			if ($tilausrivi["sarjanumeroseuranta"] != '') {
				//Nollataan sarjanumero
				if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
					$tunken = "siirtorivitunnus";
				}
				elseif ($tilausrivi["varattu"] < 0) {
					$tunken = "ostorivitunnus";
				}
				else {
					$tunken = "myyntirivitunnus";
				}

				$query = "	SELECT group_concat(tunnus) tunnukset
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilausrivi[tuoteno]'
							and $tunken = '$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				//Pidetään sarjatunnus muistissa
				if ($tapa != "POISTA") {
					$myy_sarjatunnus = $sarjarow["tunnukset"];
				}

				if ($tilausrivi["sarjanumeroseuranta"] == "E" or $tilausrivi["sarjanumeroseuranta"] == "F") {
					$query = "	DELETE FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tilausrivi[tuoteno]'
								and $tunken = '$tilausrivi[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE sarjanumeroseuranta
								SET $tunken = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tilausrivi[tuoteno]'
								and $tunken = '$tilausrivi[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}

			if ($tilausrivi["tilausrivilinkki"] > 0) {
				$query = "	UPDATE tilausrivi
							SET tyyppi = 'D'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tunnus  	= '$tilausrivi[tilausrivilinkki]'
							and tyyppi 		= 'O'
							and uusiotunnus = 0";
				$siirtores = mysql_query($query) or pupe_error($query);
			}

			// Poistetaan myös tuoteperheen lapset
			if ($tapa != "VAIHDA") {
				$query = "	DELETE FROM tilausrivi
							WHERE perheid 	= '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Poistetaan myös ttehdaslisävarusteet
			if ($tapa == "POISTA") {
				$query = "	DELETE FROM tilausrivi
							WHERE perheid2 	= '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos muokkaamme tilausrivin paikkaa ja se on speciaalikeissi, S,T,V niin laitetaan $paikka-muuttuja kuntoon
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "S" and substr($paikka,0,3) != "@@@") {
				$paikka = "@@@".$tilausrivi["toimittajan_tunnus"]."#".$tilausrivi["hyllyalue"]."#".$tilausrivi["hyllynro"]."#".$tilausrivi["hyllyvali"]."#".$tilausrivi["hyllytaso"];
			}

			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka,0,3) != "¡¡¡") {
				$paikka = "¡¡¡".$tilausrivi["toimittajan_tunnus"];
			}

			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "U" and substr($paikka,0,3) != "!!!") {
				$paikka = "!!!".$tilausrivi["toimittajan_tunnus"];
			}

			//haetaan tuotteen alv matikkaa varten
			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno = '$tilausrivi[tuoteno]' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			$tuoterow = mysql_fetch_array($tuoteresult);

			// jos meillä on lasku menossa ulkomaille
			if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {

				// tutkitaan ollaanko siellä alv-rekisteröity
				$query = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
				$alhire = mysql_query($query) or pupe_error($query);

				// ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
				if (mysql_num_rows($alhire) == 1) {
					$query = "SELECT * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and tuoteno='$tilausrivi[tuoteno]' limit 1";
					$alhire = mysql_query($query) or pupe_error($query);

					// ei löytynyt alvia, se on pakko löytyä
					if (mysql_num_rows($alhire) == 0) {
						$alehinta_alv        = -999.99; // tää on näin että tiedetään että kävi huonosti ja ei anneta lisätä tuotetta
						$alv                 = -999.99;
						$tuoterow["alv"]     = -999.99;
						$tilausrivi["alv"]   = -999.99;
						$tilausrivi["hinta"] = "0";
						$netto               = "";
						$hinta               = "0";
					}
					else {
						$alehi_alrow     = mysql_fetch_array($alhire);
						$alehinta_alv    = $alehi_alrow["alv"];
						$tuoterow["alv"] = $alehi_alrow["alv"];
					}
				}
			}


			if ($tuoterow["alv"] != $tilausrivi["alv"] and $yhtiorow["alv_kasittely"] == "" and $tilausrivi["alv"] < 500) {
				$hinta = sprintf("%.".$yhtiorow['hintapyoristys']."f",round($tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow['alv']/100),$yhtiorow['hintapyoristys']));
			}
			else {
				$hinta	= $tilausrivi["hinta"];
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
			}

			$tuoteno 	= $tilausrivi['tuoteno'];

			if ($tilausrivi["var"] == "J") {
				if ($yhtiorow["varaako_jt_saldoa"] == "") {
					$kpl = $tilausrivi['jt'];
				}
				else {
					$kpl = $tilausrivi['jt']+$tilausrivi['varattu'];
				}								
			}
			elseif ($tilausrivi["var"] == "S" or $tilausrivi["var"] == "T" or $tilausrivi["var"] == "U") {
				$kpl	= $tilausrivi['jt'];
			}
			elseif ($tilausrivi["var"] == "P") {
				$kpl	= $tilausrivi['tilkpl'];
			}
			else {
				$kpl	= $tilausrivi['varattu'];
			}

			$netto		= $tilausrivi['netto'];
			$ale		= $tilausrivi['ale'];
			$alv 		= $tilausrivi['alv'];
			$kommentti	= $tilausrivi['kommentti'];
			$kerayspvm	= $tilausrivi['kerayspvm'];
			$toimaika	= $tilausrivi['toimaika'];
			$hyllyalue	= $tilausrivi['hyllyalue'];
			$hyllynro	= $tilausrivi['hyllynro'];
			$hyllytaso	= $tilausrivi['hyllytaso'];
			$hyllyvali	= $tilausrivi['hyllyvali'];
			$rivinumero	= $tilausrivi['tilaajanrivinro'];
			$jaksotettu = $tilausrivi['jaksotettu'];
			$perheid2 	= $tilausrivi["perheid2"];

			if ($tilausrivi['hinta'] == '0.00') $hinta = '';

			if ($tapa == "MUOKKAA") {
				$var	= $tilausrivi["var"];

				//Jos lasta muokataan, niin säilytetään sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					$perheid = $tilausrivi["perheid"];
				}

				$tila		= "MUUTA";
			}
			elseif ($tapa == "JT") {
				$var 		= "J";

				if ($hyllyalue != '') {
					$paikka		= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "PUUTE") {
				$var 		= "P";

				if ($hyllyalue != '') {
					$paikka		= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "POISJTSTA") {
				$var 		= "";

				//Jos lasta muokataan, niin säilytetään sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					$perheid = $tilausrivi["perheid"];
				}

				if ($hyllyalue != '') {
					$paikka		= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "VAIHDA") {
				$perheid	= $tilausrivi['perheid'];
				$tila		= "";
				$var		= $tilausrivi["var"];
			}
			elseif ($tapa == "VAIHDAJAPOISTA") {
				$perheid	= "";
				$tila		= "";
				$paikka		= "";
			}
			elseif ($tapa == "POISTA") {
				$tuoteno	= '';
				$kpl		= '';
				$var		= '';
				$hinta		= '';
				$netto		= '';
				$ale		= '';
				$rivitunnus	= '';
				$kommentti	= '';
				$kerayspvm	= '';
				$toimaika	= '';
				$paikka		= '';
				$alv		= '';
				$perheid	= '';
				$perheid2	= '';
				$tilausrivilinkki	= '';
				$toimittajan_tunnus	= '';
			}
		}
	}

	//Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAARESEPTIIN" and $teeperhe == "OK") {
		$query = "	UPDATE tilausrivi
					SET perheid2 = '$isatunnus'
					WHERE yhtio  = '$kukarow[yhtio]'
					and tunnus   = '$isatunnus'";
		$presult = mysql_query($query) or pupe_error($query);
		$perheid2 = $isatunnus;
	}

	//Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAAKERTARESEPTIIN" and $teeperhe == "OK") {
		$query = "	UPDATE tilausrivi
					SET
					perheid	= '$isatunnus',
					tyyppi	= 'W'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$isatunnus'";
		$presult = mysql_query($query) or pupe_error($query);
		$perheid = $isatunnus;
	}

	//Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAAISAKERTARESEPTIIN") {
		if ($teeperhe == "OK") {
			$query = "	UPDATE tilausrivi
						SET
						perheid	= '$isatunnus',
						tyyppi	= 'W'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$isatunnus'";
			$presult = mysql_query($query) or pupe_error($query);
			$perheid = $isatunnus;
		}

		$perheid2 = "W";
	}

	if ($tuoteno != '') {
		$multi = "TRUE";

		if (file_exists("../inc/tuotehaku.inc")) {
			require ("../inc/tuotehaku.inc");
		}
		else {
			require ("tuotehaku.inc");
		}
	}

	//Lisätään rivi
	if ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array)) and $tila != "MUUTA" and $ulos == '') {

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//Käyttäjän syöttämä hinta ja ale ja netto, pitää säilöä jotta tuotehaussakin voidaan syöttää nämä
		$kayttajan_hinta	= $hinta;
		$kayttajan_ale		= $ale;
		$kayttajan_netto 	= $netto;
		$kayttajan_var		= $var;
		$kayttajan_kpl		= $kpl;
		$kayttajan_alv		= $alv;
		$kayttajan_paikka	= $paikka;
		$lisatty 			= 0;

		// Valmistuksissa haetaan perheiden perheitä mukaan valmistukseen!!!!!!
		if ($laskurow['tila'] == 'V' and $var != "W" and $yhtiorow["rekursiiviset_reseptit"] == "Y") {

			$kommentti_array = array();
			$lapsenlap_array = array();

			function rekursiivinen_resepti($pertuoteno, $perkpl) {
				global $kukarow, $tuoteno_array, $riikoko, $kpl_array, $kommentti_array, $lapsenlap_array;

				$query = "	SELECT tuoteno, kerroin
							FROM tuoteperhe
							WHERE isatuoteno = '$pertuoteno'
							and yhtio 		 = '$kukarow[yhtio]'
							and tyyppi		 = 'R'
							ORDER by tuoteno";
				$perheresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($perheresult) > 0) {
					while ($perherow = mysql_fetch_array($perheresult)) {
						$query = "	SELECT distinct isatuoteno
									FROM tuoteperhe
									WHERE isatuoteno = '$perherow[tuoteno]'
									and yhtio  		 = '$kukarow[yhtio]'
									and tyyppi 		 = 'R'
									ORDER by tuoteno";
						$perheresult2 = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($perheresult2) > 0) {

							//Tätä tuoteperhettä halutaan myydä
							if (!in_array(strtoupper($perherow["tuoteno"]), $tuoteno_array)) {

								$lt = strtoupper($perherow["tuoteno"]);

								$tuoteno_array[]		= $lt; // lisätään tuoteno arrayseen
								$kpl_array[$lt]			= round($perkpl * $perherow["kerroin"],2);
								$kommentti_array[$lt] 	= "Valmista $pertuoteno:n raaka-aineeksi $kpl_array[$lt] kappaletta.";
								$lapsenlap_array[$lt] 	= $lt;
								$riikoko++;
							}
							else {
								$lt = strtoupper($perherow["tuoteno"]);

								$kpl_array[$lt]		   += round($perkpl * $perherow["kerroin"],2);
								$kommentti_array[$lt]  .= "<br>Valmista $pertuoteno:n raaka-aineeksi ".round($perkpl * $perherow["kerroin"],2)." kappaletta.";
							}
						}
					}
				}
			}

			$riikoko = count($tuoteno_array);

			for($rii=0; $rii < $riikoko; $rii++) {

				if ($kpl != '' and !is_array($kpl_array) and $rii == 0) {
					$kpl_array[$tuoteno_array[$rii]] = $kayttajan_kpl;
				}

				rekursiivinen_resepti($tuoteno_array[$rii], $kpl_array[$tuoteno_array[$rii]]);
			}
		}

		foreach($tuoteno_array as $tuoteno) {
			$query	= "	select *
						from tuote
						where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				//Tuote löytyi
				$trow = mysql_fetch_array($result);

				//extranettajille ei myydä tuotteita joilla ei ole myyntihintaa
				if ($kukarow["extranet"] != '' and $trow["myyntihinta"] == 0) {
					$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
					$trow 	 = "";
					$tuoteno = "";
					$kpl	 = 0;
				}
				elseif ($kukarow["extranet"] != '' and trim($trow["vienti"]) != '') {

					// vientikieltokäsittely:
					// +maa tarkoittaa että myynti on kielletty tähän maahan
					// -maa tarkoittaa että ainoastaan tähän maahan saa myydä
					// eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa

					if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
						//ei saa myydä tähän maahan
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}

					if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
						//ei saa myydä tähän maahan
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}
				}
			}
			elseif ($kukarow["extranet"] != '') {
				$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
				$tuoteno = "";
				$kpl	 = 0;
			}
			else {
				//Tuotetta ei löydy, aravataan muutamia muuttujia
				$trow["alv"] = $laskurow["alv"];
			}


			if (checkdate($toimkka,$toimppa,$toimvva)) {
				$toimaika = $toimvva."-".$toimkka."-".$toimppa;
			}
			if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
				$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
			}
			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}
			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}


			if ($laskurow["varasto"] != 0) {
				$varasto = (int) $laskurow["varasto"];
			}

			//Ennakkotilauksen, Tarjoukset ja Ylläpitosopimukset eivät varaa saldoa
			if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
				$varataan_saldoa = "EI";
			}
			else {
				$varataan_saldoa = "";
			}

			//Tehdään muuttujaswitchit
			if (is_array($hinta_array)) {
				$hinta = $hinta_array[$tuoteno];
			}
			else {
				$hinta = $kayttajan_hinta;
			}

			if (is_array($ale_array)) {
				$ale = $ale_array[$tuoteno];
			}
			else {
				$ale = $kayttajan_ale;
			}

			if (is_array($netto_array)) {
				$netto = $netto_array[$tuoteno];
			}
			else {
				$netto = $kayttajan_netto;
			}

			if (is_array($var_array)) {
				$var = $var_array[$tuoteno];
			}
			else {
				$var = $kayttajan_var;
			}

			if (is_array($kpl_array)) {
				$kpl = $kpl_array[$tuoteno];
			}
			else {
				$kpl = $kayttajan_kpl;
			}

			if (is_array($alv_array)) {
				$alv = $alv_array[$tuoteno];
			}
			else {
				$alv = $kayttajan_alv;
			}

			if (is_array($paikka_array)) {
				$paikka = $paikka_array[$tuoteno];
			}
			else {
				$paikka = $kayttajan_paikka;
			}

			//Extranettaajat eivät voi hyvitellä itselleen tuotteita
			if ($kukarow["extranet"] != '') {
				$kpl = abs($kpl);
			}

			if (isset($kommentti_array[$tuoteno])) {
				$kommentti = $kommentti_array[$tuoteno];
			}

			if ($tuoteno != '' and $kpl != 0) {
				require ('lisaarivi.inc');
			}

			$hinta 	= '';
			$ale 	= '';
			$netto 	= '';
			$var 	= '';
			$kpl 	= '';
			$alv 	= '';
			$paikka	= '';
			$lisatty++;
		}

		if ($lisavarusteita == "ON" and $perheid2 > 0) {
			//Päivitetään isälle perheid2 jotta tiedetään, että lisävarusteet on nyt lisätty
			$query = "	UPDATE tilausrivi set
						perheid2	= '$perheid2'
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$perheid2'";
			$updres = mysql_query($query) or pupe_error($query);
		}

		$tuoteno			= '';
		$kpl				= '';
		$var				= '';
		$hinta				= '';
		$netto				= '';
		$ale				= '';
		$rivitunnus			= '';
		$kerayspvm			= '';
		$toimaika			= '';
		$alv				= '';
		$paikka 			= '';
		$paikat				= '';
		$kayttajan_hinta	= '';
		$kayttajan_ale		= '';
		$kayttajan_netto 	= '';
		$kayttajan_var		= '';
		$kayttajan_kpl		= '';
		$kayttajan_alv		= '';
		$perheid			= '';
		$perheid2			= '';
		$tuotenimitys		= '';
		$rivinumero			= '';
	}

	//Syöttörivi
	if ($muokkauslukko == "" and ($toim != "PROJEKTI" or $rivitunnus != 0) or $toim == "YLLAPITO") {
		if (file_exists("myyntimenu.inc") and in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TARJOUS"))) {

			/*
				Customoidut menuratkaisut onnistuu nyt myyntimenu.inc tiedostolla
				myyntimenu.inc tiedosto sisältään $myyntimenu array jonka perusteella rakennetaan valikko sekä suoritetaan kyselyt.

				Tuloksena on $ulos muuttuja joka liitetään syotarivi.inc tiedostossa tuoteno kenttään

				myyntimenut sisältää aina tuotehakuvalinnan jolla voi palata normaaliin tuotehakuun.

			*/


			//	Haetaan myyntimenu Array ja kysely Array
			//	Jos menutilaa ei ole laitetaan oletus
			if (!isset($menutila)) $menutila = "oletus";

			require("myyntimenu.inc");

			if ($tuoteno != "") $menutila = "haku";

			//suoritetaan kysely ja tehdään menut jos aihetta
			if (is_array($myyntimenu)) {

				//	Tehdään menuset
				$menuset = "<select name='menutila' onChange='submit()'>";
				foreach($myyntimenu as $key => $value){
					$sel = "";
					if($key == $menutila) {
						$sel = "SELECTED";
					}

					$menuset .= "<option value='$key' $sel>$value[menuset]</option>";
				}

				//	Jos ei olla myyntimenussa näytetään aina haku
				$sel = "";
				if(!isset($myyntimenu[$menutila])) {
					$sel = "SELECTED";
				}

				$menuset .= "<option value='haku' $sel>Tuotehaku</option>";
				$menuset .= "</select>";

				//	Tehdään paikka menusetille
				echo "		<table>
								<tr>$jarjlisa
									<td class='back' align = 'left'><font class='message'>".t("Lisää rivi").": </font></td><td class='back' align = 'left'>$menuset</td>
								</tr>
								<tr>$jarjlisa
									<td class='back'><hr></td>
								</tr>
							</table>";


				//	Tarkastetaan vielä, että menutila on määritelty ja luodaan lista
				if ($myyntimenu[$menutila]["query"] != "") {
					unset($ulos);

					// varsinainen kysely ja menu
					$query = " SELECT distinct(tuote.tuoteno), nimitys
								FROM tuote
								LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
								WHERE tuote.yhtio ='$kukarow[yhtio]' and ".$myyntimenu[$menutila]["query"];
								$tuoteresult = mysql_query($query) or pupe_error($query);

					$ulos = "<select style='width: 230px; font-size: 8pt; padding: 0' name='tuoteno' multiple ='TRUE'><option value=''>Valitse tuote</option>";

					if (mysql_num_rows($tuoteresult) > 0) {
						while($row=mysql_fetch_array($tuoteresult)) {
							$sel='';
							if($tuoteno==$row['tuoteno']) $sel='SELECTED';
							$ulos .= "<option value='$row[tuoteno]' $sel>$row[tuoteno] - ".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</option>";
						}
						$ulos .= "</select>";
					}
					else {
						echo "Valinnan antama haku oli tyhjä<br>";
					}
				}
				//	Jos haetaan niin ei ilmoitella turhia
				elseif($menutila != "haku" and $menutila != "") {
					echo "HUOM! Koitettiin hakea myyntimenua '$menutila' jota ei ollut määritelty!<br>";
				}

			}
			else {
				echo "HUOM! Koitettiin hakea myyntimenuja, mutta tiedot olivat puutteelliset.<br>";
			}
		}
		else {
			echo "<table><tr>$jarjlisa<td class='back'><font class='message'>".t("Lisää rivi").": </font></td></tr></table>";
		}

		require ("syotarivi.inc");
	}
	else {
		echo "</form></table>";
	}

	 // erikoisceisi, jos halutaan pieni tuotekysely tilaustaulussa...
	if ($tuoteno != '' and $kpl == '' and $kukarow['extranet'] == '') {
		$query	= "select * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {
			$tuote = mysql_fetch_array($result);

			//kursorinohjausta
			$kentta = 'kpl';

			echo "<br>
				<table>
				<tr>$jarjlisa<th>".t("Nimitys")."</th><td align='right'>".asana('nimitys_',$tuote['tuoteno'],$tuote['nimitys'])."</td></tr>
				<tr>$jarjlisa<th>".t("Hinta")."</th><td align='right'>$tuote[myyntihinta] $yhtiorow[valkoodi]</td></tr>
				<tr>$jarjlisa<th>".t("Nettohinta")."</th><td align='right'>$tuote[nettohinta] $yhtiorow[valkoodi]</td></tr>";

			$query = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$tres  = mysql_query($query) or pupe_error($query);

			while ($salrow = mysql_fetch_array($tres)) {
				$query =	"select * from varastopaikat where yhtio='$kukarow[yhtio]'
							and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$salrow[hyllyalue]'), 5, '0'),lpad(upper('$salrow[hyllynro]'), 5, '0'))
							and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$salrow[hyllyalue]'), 5, '0'),lpad(upper('$salrow[hyllynro]'), 5, '0')) ";
				$nimre = mysql_query($query) or pupe_error($query);
				$nimro = mysql_fetch_array($nimre);

				$query = "	select sum(varattu)
							from tilausrivi
							where hyllyalue='$salrow[hyllyalue]'
							and hyllynro='$salrow[hyllynro]'
							and hyllytaso='$salrow[hyllytaso]'
							and hyllyvali='$salrow[hyllyvali]'
							and yhtio='$kukarow[yhtio]'
							and tuoteno='$tuoteno'
							and tyyppi in ('L','G','V')
							and varattu>0";
				$sres  = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sres);

				$oletus='';
				if ($salrow['oletus']!='') {
					$oletus = "<br>(".t("oletusvarasto").")";
				}

				$varastomaa = '';
				if (strtoupper($nimro['maa']) != strtoupper($yhtiorow['maa'])) {
					$varastomaa = "<br>".strtoupper($nimro['maa']);
				}

				echo "<tr>$jarjlisa<th>".t("Saldo")." $nimro[nimitys] $oletus $varastomaa</th><td align='right'><font class='info'>$salrow[saldo]<br>- $srow[0]<br>---------<br></font>".sprintf("%01.2f",$salrow['saldo'] - $srow[0])."</td></tr>";
			}

			echo "</table>";
		}
	}

	// jos ollaan jo saatu tilausnumero aikaan listataan kaikki tilauksen rivit..
	if ((int) $kukarow["kesken"] != 0) {

		if (($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS") and $laskurow["tunnusnippu"] > 0 and $projektilla == "") {
			$tilrivity	= "'L','E'";

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio 	= '$kukarow[yhtio]'
						and tunnusnippu = '$laskurow[tunnusnippu]'";
			$result = mysql_query($query) or pupe_error($query);
			$toimrow = mysql_fetch_array($result);

			$tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) ";
		}
		elseif ($toim == "TYOMAARAYS") {
			$tilrivity	= "'L'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "REKLAMAATIO") {
			$tilrivity	= "'L'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "TARJOUS") {
			$tilrivity	= "'T'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS" or $toim == "MYYNTITILI") {
			$tilrivity	= "'G'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			$tilrivity	= "'L','V','W','M'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "PROJEKTI") {
			$tilrivity	= "'L','G','E','V','W'";

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila IN ('L','G','E','N','R','A') and tunnusnippu > 0";
			$result = mysql_query($query) or pupe_error($query);
			$toimrow = mysql_fetch_array($result);

			$tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) and (tilausrivi.perheid = tilausrivi.tunnus or tilausrivi.perheid = 0) and (tilausrivi.perheid2 = tilausrivi.tunnus or tilausrivi.perheid2 = 0)";
		}
		elseif ($toim == "YLLAPITO") {

			$tilrivity	= "'L','0'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		else {
			$tilrivity	= "'L','E'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}

		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

		// Tilausrivit
		$query  = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
					if (tuotetyyppi='K','Työ','Varaosa') tuotetyyppi,
					tuote.myyntihinta,
					tuote.kehahin,
					tuote.sarjanumeroseuranta,
					$sorttauskentta
					FROM tilausrivi use index (yhtio_otunnus)
					LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					$tunnuslisa
					and tilausrivi.tyyppi in ($tilrivity)
					ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);

		$rivilaskuri = mysql_num_rows($result);

		if ($rivilaskuri != 0) {

			if($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
				$rivino = 0;
			}
			else {
				$rivino = $rivilaskuri+1;
			}
			
			if ($yhtiorow["saldo_kasittely"] == "T") {
				if ($laskurow["kerayspvm"] != '0000-00-00') {
					$saldoaikalisa = date("Y-m-d");
				}
				else {
					$saldoaikalisa = date("Y-m-d");	
				}				
			}
			else {
				$saldoaikalisa = "";
			}

			echo "<br><table>";

			if ($toim == "VALMISTAVARASTOON") {
				echo "<tr>$jarjlisa<td class='back' colspan='5'><font class='message'>";
				echo t("Valmistusrivit").":";
				echo "</font></td></tr>";
			}
			else {
				echo "<tr>$jarjlisa<td class='back' colspan='5'><font class='message'>";

				echo t("Tilausrivit").":</font>";

				// jos meillä on yhtiön myyntihinnoissa alvit mukana ja meillä on alvillinen tilaus, annetaan mahdollisuus switchata listaus alvittomaksi
				if ($laskurow["alv"] != 0 and $toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {

					$sele = array();

					if ($tilausrivi_alvillisuus == "") {
						if ($yhtiorow["alv_kasittely"] == "") {
							// verolliset hinnat
							$tilausrivi_alvillisuus = "K";
						}
						else {
							// verottomat hinnat
							$tilausrivi_alvillisuus = "E";	
						}					
					}
																					
					if ($tilausrivi_alvillisuus == "E") {
						$sele["E"] = "checked";
					}
					else {
						$sele["K"] = "checked";
						$tilausrivi_alvillisuus = "";
					}

					echo "<form action='' method='post'>
 					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
 					<input type='hidden' name='tee' value='$tee'>
 					<input type='hidden' name='toim' value='$toim'>
 					<input type='hidden' name='lopetus' value='$lopetus'>
 					<input type='hidden' name='projektilla' value='$projektilla'>
 					<input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
 					<input type='hidden' name='asiakasid' value='$asiakasid'>";

					echo "<input type='radio' onclick='submit()' name='tilausrivi_alvillisuus' value='K' $sele[K]> ".t("Verolliset hinnat")." ";
					echo "<input type='radio' onclick='submit()' name='tilausrivi_alvillisuus' value='E' $sele[E]> ".t("Verottomat hinnat")." ";

					echo "</form>";
				}
				else {
					$tilausrivi_alvillisuus = "";
				}

				echo "</td></tr>";
			}

			echo "<tr>$jarjlisa<th>".t("#")."</th>";

			if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {
				echo "<th>".t("Tyyppi")."</th>";
			}

			if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
				echo "<th>".t("Nimitys")."</th>";
			}

			if($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '') {
				echo "	<th>".t("Paikka")."</th>";
			}

			echo "	<th>".t("Tuotenumero")."</th>
					<th>".t("Määrä")."</th>
					<th>".t("Var")."</th>";

			if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {

				echo "<th>".t("Netto")."</th>";

				echo "<th style='text-align:right;'>".t("Svh")."</th>";

				if ($kukarow['hinnat'] == 0) {
					echo "<th style='text-align:right;'>".t("Ale%")."</th>";
					echo "<th style='text-align:right;'>".t("Hinta")."</th>";
				}

				echo "	<th style='text-align:right;'>".t("Rivihinta")."</th>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<th style='text-align:right;'>".t("Kate")."</th>";
				}

				echo "	<th style='text-align:right;'>".t("Alv%")."</th>
						<td class='back'>&nbsp;</td>
						<td class='back'>&nbsp;</td>
						</tr>";
			}

			$bruttoyhteensa	= 0;
			$tuotetyyppi	= "";
			$varaosatyyppi	= "";
			$vanhaid 		= "KALA";
			$borderlask		= 0;
			$pknum			= 0;

			while ($row = mysql_fetch_array($result)) {

				// voidaan lukita tämä tilausrivi
				if ($row["uusiotunnus"] > 0 or $laskurow["tunnus"] != $row["otunnus"] or ($laskurow["tila"] == "V" and $row["toimitettuaika"] != '0000-00-00 00:00:00')) {
					$muokkauslukko_rivi = "LUKOSSA";
				}
				else {
					$muokkauslukko_rivi = "";
				}

				// Rivin tarkistukset
				if ($muokkauslukko == "" and $muokkauslukko_rivi == "") {
					require('tarkistarivi.inc');

					//tarkistarivi.inc:stä saadaan $trow jossa on select * from tuote
				}

				if ($toim == "TYOMAARAYS") {
					if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Työ') {
						$tuotetyyppi = 1;

						echo "<tr>$jarjlisa<td class='back' colspan='10'><br></td></tr>";
						echo "<tr>$jarjlisa<td class='back' colspan='10'><b>".t("Työt")."</b>:</td></tr>";
					}
				}

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
					$row['varattu'] = $row['kpl'];
				}

				//Käännetään tän rivin hinta oikeeseen valuuttaan
				$row["kotihinta"] = $row["hinta"];
				$row["hinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"]);


				// Tän rivin rivihinta
				$summa		= $row["hinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);
				$kotisumma	= $row["kotihinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);

				// Tän rivin alviton rivihinta
				if ($yhtiorow["alv_kasittely"] == '') {

					if ($row["alv"] >= 500) {
						$alvkapu = 0;
					}
					else {
						$alvkapu = $row["alv"];
					}

					$summa_alviton 		= $summa / (1+$alvkapu/100);
					$kotisumma_alviton 	= $kotisumma / (1+$alvkapu/100);
				}
				else {
					$summa_alviton 		= $summa;
					$kotisumma_alviton 	= $kotisumma;
				}

				if ($row["hinta"] == 0.00) 	$row["hinta"] = '';
				if ($summa == 0.00) 		$summa = '';
				if ($row["ale"] == 0.00) 	$row["ale"] = '';

				if ($row["hyllyalue"] == "") {
					$row["hyllyalue"] = "";
					$row["hyllynro"]  = "";
					$row["hyllyvali"] = "";
					$row["hyllytaso"] = "";
				}

				if ($row["var"] == "P") {
					$class = " class='spec' ";
				}
				elseif ($row["var"] == "J") {
					$class = " class='green' ";
				}
				elseif($yhtiorow["puute_jt_oletus"] == "H") {
					//	Tarkastetaan saldo ja informoidaan käyttäjää
					list(, , $tsek_myytavissa) = saldo_myytavissa($trow["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);
					
					if ($tsek_myytavissa < 0) {
						$class = " class='spec' ";
					}
					else {
						$class = '';
					}
				}
				else {
					$class = '';
				}

				if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {

					if ($row["tyyppi"] == "W") {
						$class = " class='spec' ";
					}

					if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA') {
						echo "<tr>$jarjlisa<td class='back' colspan='10'><br></td></tr>";
					}
				}


				if($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
					$rivino++;
				}
				else {
					$rivino--;
				}

				if($yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {

					$buttonit =  "
									<div align='center'><form action='$PHP_SELF#rivi_$rivino' name='siirra_$rivino' method='post'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' id='rivi_$rivino' name='jarjesta' value='$rivino'>";

					$buttonit .= "<img src='".$palvelin2."pics/noimage.gif' border='0' height = '10'>";
					
					if($rivino > 1) {
						$buttonit .= "	<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveUp'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png' border='0' height = '10' width = '10'></a><br>";
					}
					else {
						$buttonit .= "	<img src='".$palvelin2."pics/noimage.gif' border='0' height = '10'>";
					}

					if($rivilaskuri > $rivino) {
						$buttonit .= "	<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveDown'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png' border='0' height = '10' width = '10'></a>";
					}
					$buttonit .= "</form></div>";
				}
				else {
					$buttonit = "";
				}

				// Tuoteperheiden lapsille ei näytetä rivinumeroa
				if ($row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")))) {

					if (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						$pklisa = " and (perheid = '$row[tunnus]' or perheid2 = '$row[tunnus]')";
					}
					elseif ($row["perheid"] == 0) {
						$pklisa = " and perheid2 = '$row[perheid2]'";
					}
					else {
						$pklisa = " and (perheid = '$row[perheid]' or perheid2 = '$row[perheid]')";
					}

					$query = "	SELECT sum(if(kommentti != '',1,0)), count(*)
								FROM tilausrivi use index (yhtio_otunnus)
								WHERE yhtio = '$kukarow[yhtio]'
								$tunnuslisa
								$pklisa
								and tyyppi != 'D'";
					$pkres = mysql_query($query) or pupe_error($query);
					$pkrow = mysql_fetch_array($pkres);

					if (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						$query  = "	SELECT tuoteperhe.tunnus
									FROM tuoteperhe
									WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
									and tuoteperhe.isatuoteno 	= '$row[tuoteno]'
									and tuoteperhe.tyyppi 		= 'L'";
						$lisaresult = mysql_query($query) or pupe_error($query);
						$lisays = mysql_num_rows($lisaresult)+1;
					}
					else {
						$lisays = 0;
					}

					$pkrow[1] += $lisays;

					$pknum = $pkrow[0] + $pkrow[1];
					$borderlask = $pkrow[1];

					echo "<tr>";

					if ($jarjlisa != "") {
						echo "<td rowspan='$pknum' width='10' class='back'>$buttonit</td>";
					}

					$echorivino = $rivino;

					if ($yhtiorow['rivinumero_syotto'] != '') {
						if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
							$echorivino .= " ($row[tilaajanrivinro])";
						}
					}

					echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$echorivino</td>";
				}
				elseif($row["perheid"] == 0 and $row["perheid2"] == 0) {

					$echorivino = $rivino;

					if ($yhtiorow['rivinumero_syotto'] != '') {
						if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
							$echorivino .= " ($row[tilaajanrivinro])";
						}
					}

					echo "<tr>";

					if($row["kommentti"] != "") {
						if($jarjlisa != "") {
							echo "<td rowspan = '2' width='15' class='back'>$buttonit</td>";
						}

						echo "<td rowspan = '2' valign='top'>$echorivino";
					}
					else {
						if($jarjlisa != "") {
							echo "<td width='15' class='back'>$buttonit</td>";
						}

						echo "<td valign='top'>$echorivino";
					}

					if($toim != "TARJOUS") {
						if ($row["toimitettuaika"] == '0000-00-00 00:00:00' and $row["uusiotunnus"] == 0 and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
							$query = " 	SELECT lasku.tunnus
										FROM lasku
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.tunnusnippu = '$laskurow[tunnusnippu]'
										and lasku.tila IN ('L','N','A','T','G','S','V','W','O')
										and lasku.alatila != 'X'";
							$toimres = mysql_query($query) or pupe_error($query);

							if(mysql_num_rows($toimres) > 1) {
								echo "	<form action='$PHP_SELF' method='post'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='edtilausnumero' 	value = '$row[otunnus]'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<select name='valitsetoimitus_vaihdarivi' onchange='submit();'>";

								while($toimrow = mysql_fetch_array($toimres)) {
									$sel = "";
									if($toimrow["tunnus"] == $row["otunnus"]) {
										$sel = "selected";
									}

									echo "<option value ='$toimrow[tunnus]' $sel>$toimrow[tunnus]</option>";
								}

								echo "</select></form>";
							}
						}
					}

					echo "</td>";
					$borderlask		= 0;
					$pknum			= 0;
				}

				$classlisa = "";

				if($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
					$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
					$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

					$borderlask--;
				}
				elseif($borderlask == $pkrow[1] and $pkrow[1] > 0) {
					$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
					$class    .= " style='border-top: 1px solid;' ";

					$borderlask--;
				}
				elseif($borderlask == 1) {
					if ($row["kommentti"] != '') {
						$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
						$class    .= " style='font-style:italic; ' ";
					}
					else {
						$classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
						$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
					}

					$borderlask--;
				}
				elseif($borderlask > 0 and $borderlask < $pknum) {
					$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
					$class    .= " style='font-style:italic;' ";
					$borderlask--;
				}

				$vanhaid = $row["perheid"];

				if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {

					if($row["ei_nayteta"] == "") {
						//annetaan valita tilausrivin tyyppi
						echo "<td $class valign='top'>
								<form action='$PHP_SELF' method='post' name='lisatietoja'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE'>
								<select name='positio' onchange='submit();' $state>";

						$query = "	SELECT selite, selitetark
									FROM avainsana
									WHERE yhtio = '$kukarow[yhtio]' and laji = 'TRIVITYYPPI'
									ORDER BY jarjestys, selite";
						$tresult = mysql_query($query) or pupe_error($query);

						while($trrow = mysql_fetch_array($tresult)) {
							$sel = "";
							if ($trrow["selite"]==$row["positio"]) $sel = 'selected';
							echo "<option value='$trrow[selite]' $sel>$trrow[selitetark]</option>";
						}
						echo "</select></form>";


						if($yhtiorow['tilauksen_kohteet'] == 'K' and $lasklisatied_row["asiakkaan_kohde"] > 0) {
							$posq = "SELECT * FROM asiakkaan_positio WHERE yhtio = '$kukarow[yhtio]' and asiakkaan_kohde = '$lasklisatied_row[asiakkaan_kohde]'";
							$posres = mysql_query($posq) or pupe_error($posq);

							echo "	<form name='asiakkaan_positio_$rivino' action='$PHP_SELF' method='post'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE'>
									<select id='asiakkaan_positio_$row[tunnus]' name='asiakkaan_positio' onchange=\"yllapito('asiakkaan_positio&asiakkaan_kohde=$lasklisatied_row[asiakkaan_kohde]', this.id, 'asiakkaan_positio_$rivino');\" $state>
									<option value=''>Asiakkaalla ei ole positiota</option>";

							if(mysql_num_rows($posres) > 0) {
								$optlisa="";
								while($posrow = mysql_fetch_array($posres)) {
									$sel = "";
									if($posrow["tunnus"] == $row["asiakkaan_positio"]) {
										$sel = "SELECTED";
										$optlisa = "<option value='muokkaa#$row[asiakkaan_positio]'>Muokkaa asiakaan positiota</option>";
									}
									echo "<option value='$posrow[tunnus]' $sel>$posrow[tunnus] - $posrow[positio]</option>";
								}
							}
							echo "	<optgroup label='Toiminto'>
										<option value='uusi'>Luo uusi asiakkaan positio</option>
										$optlisa
									</optgroup>
									</select></form>";
						}
						elseif ($yhtiorow['tilauksen_kohteet'] == 'K') {
							echo "<font class='info'>".t("Kohdetta ei valittu")."</font>";
						}
						echo "</td>";

					}
					else {
						echo "<td $class>&nbsp;</td>";
					}
				}

				// Tuotteen nimitys näytetään vain jos käyttäjän resoluution on iso
				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					if (strtolower($kukarow["kieli"]) != strtolower($yhtiorow["kieli"])) {
						$query = "	select selite
									from tuotteen_avainsanat
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laji	= 'nimitys_".$kukarow["kieli"]."'
									and selite != ''
									LIMIT 1";
						$pkres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($pkres) > 0) {
							$pkrow = mysql_fetch_array($pkres);
							$nimitys = $pkrow["selite"];
						}
						else {
							$nimitys = $row["nimitys"];
						}
					}
					else {
						$nimitys = $row["nimitys"];
					}

					echo "<td $class align='left' valign='top'>$nimitys</td>";
				}

				if ($kukarow['extranet'] == '' and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {

					 if ($row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
						$tilatapa = "VALITSE";

						require('laskuta_myyntitilirivi.inc');
					}
					else {
						echo "<td $class align='left' valign='top'>&nbsp;</td>";
					}
				}
				elseif ($muokkauslukko_rivi == "" and $kukarow['extranet'] == '' and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {
					
						echo "	<td $class align='left' valign='top'>";
									
						//valitaan näytetävä lippu varaston tai yhtiön maanperusteella
						if ($selpaikkamaa != '' and $yhtiorow['varastopaikan_lippu'] != '') {										
											echo "<img src='../pics/flag_icons/gif/".strtolower($selpaikkamaa).".gif'>";
									}	
									
									
						echo "<form action='$PHP_SELF' method='post' name='paikat'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value='$lopetus'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<input type='hidden' name='tila' 			value = 'MUUTA'>
										<input type='hidden' name='tapa' 			value = 'VAIHDA'>
										$paikat
									</form>
								</td>";
								
					}
					else {
						
						
						if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
							echo "<td $class align='left' valign='top'><font class='error'><img src='../pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
						}
						elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
							echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
						}
						else {
							echo "<td $class align='left' valign='top'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
						}

						if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and !in_array($row["var"], array('P','J','S','T','U'))) {
					   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
					   					FROM sarjanumeroseuranta
					   					WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
					   					and myyntirivitunnus = '$row[tunnus]'
					   					LIMIT 1";
					   		$sarjares = mysql_query($query) or pupe_error($query);
					   		$sarjarow = mysql_fetch_array($sarjares);

							echo ", $sarjarow[era]";

							if ($trow["sarjanumeroseuranta"] == "F") {
								echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
							}
						}

						echo "</td>";
					}
				}
				elseif($muokkauslukko_rivi == "" and $kukarow['extranet'] == '') {
					if ($paikat != '') {
						echo "	<td $class align='left' valign='top'>
									<form action='$PHP_SELF' method='post'name='paikat'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<input type='hidden' name='tila' 			value = 'MUUTA'>
										<input type='hidden' name='tapa' 			value = 'VAIHDAJAPOISTA'>
										$paikat
									</form>
								</td>";
					}
					else {
						echo "<td $class align='left' valign='top'>&nbsp;</td>";
					}
				}
				elseif($kukarow['extranet'] == '') {
					
					if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
						echo "<td $class align='left' valign='top'><font class='error'><img src='../pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
					}
					elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
						echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
					}
					else {
						echo "<td $class align='left' valign='top'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
					}
					
					//echo "<td $class align='left' valign='top'><img src='../pics/flag_icons/gif/".strtolower($yhtiorow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
				}
				elseif ($kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
					
					if ($varow['maa'] != '' ) {
						echo "<td $class align='left' valign='top'><img src='".$palvelin2."flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
					}
					else {
						echo "<td $class align='left' valign='top'></td>";
					}
					
					
				}
				
				if($kukarow['extranet'] == '') {
					echo "<td $class valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a>";
				}
				else {
									
					echo "<td $class valign='top'>$row[tuoteno]";
				}

				// Näytetäänkö sarjanumerolinkki
				if (($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") and $row["varattu"] < 0)) and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {

					if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
						$tunken1 = "siirtorivitunnus";
						$tunken2 = "siirtorivitunnus";
					}
					elseif ($row["varattu"] < 0) {
						$tunken1 = "ostorivitunnus";
						$tunken2 = "myyntirivitunnus";
					}
					else {
						$tunken1 = "myyntirivitunnus";
						$tunken2 = "myyntirivitunnus";
					}

					if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T") {
						$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
									FROM sarjanumeroseuranta
									where yhtio	 = '$kukarow[yhtio]'
									and tuoteno	 = '$row[tuoteno]'
									and $tunken1 = '$row[tunnus]'";
					}
					else {
						$query = "	SELECT sum(era_kpl) kpl, min(sarjanumero) sarjanumero
									FROM sarjanumeroseuranta
									where yhtio	 = '$kukarow[yhtio]'
									and tuoteno	 = '$row[tuoteno]'
									and $tunken1 = '$row[tunnus]'";
					}
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					if ($sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&$tunken2=$row[tunnus]&from=$toim#".urlencode($sarjarow["sarjanumero"])."' style='color:00FF00'>".t("S:nro ok")."</font></a>)";
					}
					else {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&$tunken2=$row[tunnus]&from=$toim'>".t("S:nro")."</a>)";

						if ($laskurow['sisainen'] != '' or $laskurow['ei_lahetetta'] != '') {
							$sarjapuuttuu++;
							$tilausok++;
						}
					}
				}

				echo "</td>";

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and $row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right' valign='top' nowrap><input type='text' size='5' name='kpl' value='$row[varattu]'></td>";
					echo "</form>";
				}
				elseif ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right' valign='top' nowrap>".t("Laskutettu")."</td>";
					echo "</form>";
				}
				else {
					if ($row["var"] == 'J') {
						if ($yhtiorow["varaako_jt_saldoa"] == "") {
							$kpl_ruudulle = $row['jt'] * 1;
						}
						else {
							$kpl_ruudulle = ($row['jt']+$row['varattu']) * 1;
						}											
					}
					elseif ($row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
						$kpl_ruudulle = $row['jt'] * 1;
					}
					elseif($row["var"] == 'P') {
						$kpl_ruudulle = $row['tilkpl'] * 1;
					}
					else {
						$kpl_ruudulle = $row['varattu'] * 1;
					}


					if ($kpl_ruudulle < 0 and $row["sarjanumeroseuranta"] == "S") {

						echo "<td $class align='right' valign='top' nowrap>";

						$sel1 = $sel2 = "";

						if ($row["osto_vai_hyvitys"] == "O") {
							$sel2 = "SELECTED";
						}
						else {
							$sel1 = "SELECTED";
						}

						echo "	<form action='$PHP_SELF' method='post' name='ovaih'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS'>
								<select name='osto_vai_hyvitys' onchange='submit();'>
								<option value=''  $sel1>$kpl_ruudulle ".("Hyvitys")."</option>
								<option value='O' $sel2>$kpl_ruudulle ".("Osto")."</option>
								</select>
								</form></td>";
					}
					elseif ($kpl_ruudulle > 0 and $row["sarjanumeroseuranta"] == "S") {

						$query = "	SELECT sarjanumeroseuranta.kaytetty
									FROM sarjanumeroseuranta
									WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
									and sarjanumeroseuranta.myyntirivitunnus  = '$row[tunnus]'";
						$muutares = mysql_query($query) or pupe_error($query);
						$muutarow = mysql_fetch_array($muutares);

						if ($muutarow["kaytetty"] != "") {
							echo "<td $class align='right' valign='top' nowrap>";

							$sel1 = $sel2 = "";

							if ($row["osto_vai_hyvitys"] == "H") {
								$sel2 = "SELECTED";
							}
							else {
								$sel1 = "SELECTED";
							}

							echo "	<form action='$PHP_SELF' method='post' name='ovaih'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS'>
									<select name='osto_vai_hyvitys' onchange='submit();'>
									<option value=''  $sel1>$kpl_ruudulle ".("Myynti")."</option>
									<option value='H' $sel2>$kpl_ruudulle ".("Hyvitys")."</option>
									</select>
									</form></td>";

						}
						else {
							echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle</td>";
						}
					}
					else {
						echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle</td>";
					}
				}

				if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {
					$classvar = $class;
				}
				else {
					if ($classlisa != "") {
						$classvar = $classlisa;
					}
					else {
						$classvar = $class;
					}
				}

				echo "<td $classvar align='center' valign='top'>$row[var]&nbsp;</td>";


				if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {

					echo "<td $class align='center' valign='top'>$row[netto]&nbsp;</td>";

					$hinta = $row["hinta"];
					$netto = $row["netto"];
					$kpl   = $row["varattu"]+$row["jt"];
					
					// Tarvitaan jos laskun valuutta on eri kuin hinnaston valuutta					
					list(, , , , $alehinta_val) = alehinta($laskurow, $trow, $kpl, $netto, '', '');
					
					if ($alehinta_val != $laskurow["valkoodi"]) {
						$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
					}
					
					if ($yhtiorow["alv_kasittely"] == "") {
						// Oletuksena verolliset hinnat
						
						if ($tilausrivi_alvillisuus == "E") {
							// Halutaan alvittomat hinnat
							$alvillisuus_jako = 1 + $row["alv"] / 100;														
						}
						else {
							// Oletukset
							$alvillisuus_jako = 1;
						}
						
						$hinta    		= round($hinta / $alvillisuus_jako, $yhtiorow['hintapyoristys']);
						$summa    		= round($summa / $alvillisuus_jako, $yhtiorow['hintapyoristys']);
						$myyntihinta	= round(tuotteen_myyntihinta($laskurow, $trow, 1) / $alvillisuus_jako, $yhtiorow['hintapyoristys']);
					}
					else {
						// Oletuksena verottomat hinnat
						
						if ($tilausrivi_alvillisuus == "E") {
							// Oletukset
							$alvillisuus_kerto = 1;												
						}
						else {
							// Halutaan alvilliset hinnat
							$alvillisuus_kerto = 1 + $row["alv"] / 100;														
						}
						
						$hinta    		= round($hinta * $alvillisuus_kerto, $yhtiorow['hintapyoristys']);
						$summa    		= round($summa * $alvillisuus_kerto, $yhtiorow['hintapyoristys']);
						$myyntihinta	= round(tuotteen_myyntihinta($laskurow, $trow, 1) * $alvillisuus_kerto, $yhtiorow['hintapyoristys']);
					}

					$brutto   = $hinta * ($row["varattu"] + $row["jt"]);
					$kplhinta = $hinta * (1 - $row["ale"] / 100);
					
					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>$myyntihinta</td>";
					}
					else {

						if ($myyntihinta != $hinta) $myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $myyntihinta)." (".sprintf("%.".$yhtiorow['hintapyoristys']."f",$hinta).")";
						else $myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $myyntihinta);

						echo "<td $class align='right' valign='top'>$myyntihinta</td>";
						echo "<td $class align='right' valign='top'>".($row["ale"] * 1)."</td>";
						echo "<td $class align='right' valign='top'>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $kplhinta, 2)."</td>";
					}

					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>".sprintf("%.".$yhtiorow['hintapyoristys']."f",$brutto)."</td>";
					}
					else {
						echo "<td $class align='right' valign='top'>".sprintf("%.".$yhtiorow['hintapyoristys']."f",$summa)."</td>";
					}

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						// Tän rivin kate
						$kate = 0;

						if ($kukarow['extranet'] == '' and $row["sarjanumeroseuranta"] == "S") {

							if ($kpl > 0) {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
								$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);

								// Kate = Hinta - Ostohinta
								if ($kotisumma_alviton != 0) {
									$kate = sprintf('%.2f',100*($kotisumma_alviton - ($ostohinta * $kpl))/$kotisumma_alviton)."%";
								}
							}
							elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "O") {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

								// Kate = 0
								$kate = "0%";
							}
							elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "") {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ

								//Tähän hyvitysriviin liitetyt sarjanumerot
								$query = "	SELECT sarjanumero, kaytetty
											FROM sarjanumeroseuranta
											WHERE yhtio 		= '$kukarow[yhtio]'
											and ostorivitunnus 	= '$row[tunnus]'";
								$sarjares = mysql_query($query) or pupe_error($query);

								$ostohinta = 0;

								while($sarjarow = mysql_fetch_array($sarjares)) {

									// Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
									$query  = "	select tilausrivi.rivihinta/tilausrivi.kpl ostohinta
												FROM sarjanumeroseuranta
												JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
												WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
												and sarjanumeroseuranta.tuoteno 	= '$row[tuoteno]'
												and sarjanumeroseuranta.sarjanumero = '$sarjarow[sarjanumero]'
												and sarjanumeroseuranta.kaytetty 	= '$sarjarow[kaytetty]'
												and sarjanumeroseuranta.myyntirivitunnus > 0
												and sarjanumeroseuranta.ostorivitunnus   > 0
												ORDER BY sarjanumeroseuranta.tunnus
												LIMIT 1";
									$sarjares1 = mysql_query($query) or pupe_error($query);
									$sarjarow1 = mysql_fetch_array($sarjares1);

									$ostohinta += $sarjarow1["ostohinta"];
								}

								// Kate = Hinta - Alkuperäinen ostohinta
								if ($kotisumma_alviton != 0) {
									$kate = sprintf('%.2f',100 * ($kotisumma_alviton*-1 - $ostohinta)/$kotisumma_alviton)."%";
								}
							}
							else {
								$kate = "N/A";
							}
						}
						elseif ($kukarow['extranet'] == '' and $kotisumma_alviton != 0) {
							$kate = sprintf('%.2f',100*($kotisumma_alviton - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"])))/$kotisumma_alviton)."%";
						}

						echo "<td $class align='right' valign='top' nowrap>$kate</td>";
					}

					if ($classlisa != "") {
						$classx = $classlisa;
					}
					else {
						$classx = $class;
					}

					if ($row["alv"] >= 500) {
						echo "<td $classx align='right' valign='top' nowrap>".t("MV")."</td>";
					}
					else {
						echo "<td $classx align='right' valign='top' nowrap>".($row["alv"] * 1)."</td>";
					}
				}

				echo "<td class='back' valign='top' nowrap>";

				if ($varaosavirhe != '') {
					echo "<font class='error'>$varaosavirhe</font>";
				}
				if ($varaosakommentti != '') {
					echo "<font class='info'>$varaosakommentti</font>";
				}

				$varaosavirhe = "";
				$varaosakommentti = "";

				if (($muokkauslukko == "" and $muokkauslukko_rivi == "") or $toim == "YLLAPITO") {
					echo "<form action='$PHP_SELF' method='post' name='muokkaa'>
							<input type='hidden' name='toim' 			value = '$toim'>
							<input type='hidden' name='lopetus' 		value = '$lopetus'>
							<input type='hidden' name='projektilla' 	value = '$projektilla'>
							<input type='hidden' name='tilausnumero'	value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
							<input type='hidden' name='menutila' 		value = '$menutila'>
							<input type='hidden' name='tuotenimitys' 	value = '$row[nimitys]'>
							<input type='hidden' name='tila' 			value = 'MUUTA'>
							<input type='hidden' name='tapa' 			value = 'MUOKKAA'>
							<input type='Submit' value='".t("Muokkaa")."'>
							</form> ";

					echo "<form action='$PHP_SELF' method='post' name='poista'>
							<input type='hidden' name='toim' 			value = '$toim'>
							<input type='hidden' name='lopetus' 		value = '$lopetus'>
							<input type='hidden' name='projektilla' 	value = '$projektilla'>
							<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
							<input type='hidden' name='menutila'	 	value = '$menutila'>
							<input type='hidden' name='tila' 			value = 'MUUTA'>
							<input type='hidden' name='tapa' 			value = 'POISTA'>
							<input type='Submit' value='".t("Poista")."'>
							</form> ";

					if ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["perheid"] == 0)) and $toim == 'VALMISTAVARASTOON') {

						if ($row["perheid"] == 0) {
							$lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
						}

						echo "<form action='$PHP_SELF' method='post' name='lisaakertareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='tila' 			value = 'LISAAKERTARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid'	 		value = '$row[perheid]'>
								<input type='Submit' value='".t("Lisää raaka-aine")."'>
								</form>";


						echo "<form action='$PHP_SELF' method='post' name='lisaaisakertareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='tila' 			value = 'LISAAISAKERTARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid'	 		value = '$row[perheid]'>
								<input type='Submit' value='".t("Lisää valmiste")."'>
								</form>";
					}
					elseif (($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0) or (($toim == 'SIIRTOLISTA' or $toim == "SIIRTOTYOMAARAYS" or $toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and $row["perheid2"] == 0 and $row["perheid"] == 0)) {
						if ($row["perheid2"] == 0 and $row["perheid"] == 0) {
							$lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
						}

						if($laskurow["tila"] == "V") {
							$nappulanteksti = t("Lisää reseptiin");
						}
						else {
							$nappulanteksti = t("Lisää tuote");
						}

						echo "<form action='$PHP_SELF' method='post' name='lisaareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='tila'			value = 'LISAARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid' 		value = '$row[perheid]'>
								<input type='hidden' name='perheid2' 		value = '$row[perheid2]'>
								<input type='Submit' value='$nappulanteksti'>
								</form>";
					}

					if ($row["var"] == "J" and ($laskurow["alatila"] == "T" or $laskurow["alatila"] == "U")) {
						list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

						if($jtapu_myytavissa >= $kpl_ruudulle) {
							echo "<form action='$PHP_SELF' method='post' name='toimita'>
									<input type='hidden' name='toim' 			value = '$toim'>
									<input type='hidden' name='lopetus' 		value = '$lopetus'>
									<input type='hidden' name='projektilla' 	value = '$projektilla'>
									<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
									<input type='hidden' name='menutila' 		value = '$menutila'>
									<input type='hidden' name='tila' 			value = 'MUUTA'>
									<input type='hidden' name='tapa' 			value = 'POISJTSTA'>
									<input type='Submit' value='".t("Toimita")."'>
									</form> ";
						}
					}

					if ($row["var"] == "P" and $saako_jalkitoimittaa == 0 and $laskurow["jtkielto"] == "") {
						echo " <form action='$PHP_SELF' method='post' name='jalkitoimita'>
									<input type='hidden' name='toim' 			value = '$toim'>
									<input type='hidden' name='lopetus' 		value = '$lopetus'>
									<input type='hidden' name='projektilla' 	value = '$projektilla'>
									<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
									<input type='hidden' name='menutila' 		value = '$menutila'>
									<input type='hidden' name='tila' 			value = 'MUUTA'>
									<input type='hidden' name='tapa' 			value = 'JT'>
									<input type='Submit' value='".t("Jälkitoim")."'>
									</form> ";
					}

					if ($row["jt"] != 0 and $yhtiorow["puute_jt_oletus"] == "J") {
						echo "<form action='$PHP_SELF' method='post' name='puutetoimita'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero'	value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'MUUTA'>
								<input type='hidden' name='tapa' 			value = 'PUUTE'>
								<input type='Submit' value='".t("Puute")."'>
								</form> ";
					}

					if ($saako_hyvaksya > 0) {
						echo "<form action='$PHP_SELF' method='post' name='hyvaksy'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'OOKOOAA'>
								<input type='Submit' value='".t("Hyväksy")."'>
								</form> ";
					}
				}
				elseif($row["toimitettuaika"] != '0000-00-00 00:00:00') {
					echo "<font class='info'>".t("Toimitettu").": ".$row["toimitettuaika"]."</font>";
				}
				
				if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "SIIRTOTYOMAARAYS") and $riviok == 0) {
					//Tutkitaan tuotteiden lisävarusteita
					$query  = "	SELECT *
								FROM tuoteperhe
								JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
								WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno 	= '$row[tuoteno]'
								and tuoteperhe.tyyppi 		= 'L'
								order by tuoteperhe.tuoteno";
					$lisaresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($lisaresult) > 0 and ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						echo "</tr>";

						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off' name='lisavarusteet'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='lisavarusteita' 	value = 'ON'>
								<input type='hidden' name='perheid' 		value = '$row[perheid]'>
								<input type='hidden' name='perheid2' 		value = '$row[tunnus]'>";

						$lislask = 0;

						while ($prow = mysql_fetch_array($lisaresult)) {

							echo "<tr>$jarjlisa";

							if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") {
								echo "<td valign='top'>&nbsp;</td>";
							}

							if ($kukarow["resoluutio"] == 'I') {
								echo "<td valign='top'>".asana('nimitys_',$prow['tuoteno'],$prow['nimitys'])."</td>";
							}

							echo "<input type='hidden' name='tuoteno_array[$prow[tuoteno]]' value='$prow[tuoteno]'>";

							if ($row["var"] == "T") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='¡¡¡$row[toimittajan_tunnus]'>";
							}
							if ($row["var"] == "U") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='!!!$row[toimittajan_tunnus]'>";
							}

							echo "<td valign='top'>&nbsp;</td>";
							echo "<td valign='top'>$prow[tuoteno]</td>";
							echo "<td valign='top'><input type='text' name='kpl_array[$prow[tuoteno]]'   size='2' maxlength='8'		Style='font-size: 8pt; padding:0;'></td>";

							if ($toim != "SIIRTOTYOMAARAYS") {
								echo "	<td valign='top'><input type='text' name='var_array[$prow[tuoteno]]'   size='2' maxlength='1'></td>
										<td valign='top'><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1'></td>
										<td valign='top'><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12'></td>
								  		<td valign='top'><input type='text' name='ale_array[$prow[tuoteno]]'   size='5' maxlength='6'></td>";
							}

							$lislask++;

							if ($lislask == mysql_num_rows($lisaresult)) {
								echo "<td class='back' valign='top'><input type='submit' value='".t("Lisää")."'></td>";
								echo "</form>";
							}

							echo "</tr>";
						}
					}
					elseif($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0) {
						echo "<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off' name='lisaalisav'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='projektilla' value='$projektilla'>";

						if ($row["perheid2"] == 0 or ($row["var"] != "T" and $row["var"] != "U")) {
							echo "<input type='hidden' name='spessuceissi' value='OK'>";
						}

						echo "	<input type='hidden' name='tila' value='LISLISAV'>
								<input type='hidden' name='rivitunnus' value='$row[tunnus]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='submit' value='".t("Lisää lisävarusteita")."'>
								</form> ";
					}
				}

				echo "</td></tr>";

				if ($row['kommentti'] != '') {
					$cspan = 10;

					if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
						$cspan -= 6;
					}

					if ($kukarow['hinnat'] == 1) {
						$cspan--;
					}
					if($kukarow["resoluutio"] == "I") {
						$cspan++;
					}
					if($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {
						$cspan++;
					}

					echo "<tr>";

					if ($borderlask == 0 and $pknum > 1) {
						$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
						$kommclass2 = " style='border-bottom: 1px solid;'";
					}
					elseif($pknum > 0) {
						$kommclass1 = " style='border-right: 1px solid;'";
						$kommclass2 = " style='border-right: 1px solid;'";
					}
					else {
						$kommclass1 = "";
						$kommclass2 = "";
					}

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td $kommclass2>&nbsp;</td>";
					}

					echo "<td $kommclass1 colspan='$cspan' valign='top'>".t("Kommentti").":<br>".str_replace("\n", "<br>", $row["kommentti"])."</td>";

					echo "</tr>";
				}
			}

			if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {
				//Laskeskellaan tilauksen loppusummaa
				$alvquery = "	SELECT if(isnull(varastopaikat.maa) or varastopaikat.maa='', '$yhtiorow[maa]', varastopaikat.maa) maa, group_concat(tilausrivi.tunnus) rivit
								FROM tilausrivi
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = if(tilausrivi.var='S', if((SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro)!='', (SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro), tilausrivi.yhtio), tilausrivi.yhtio)
			                    and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
			                    and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi in ('L','T','G','E','W')
								$tunnuslisa
								GROUP BY 1
								ORDER BY 1";
				$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

				$summa 					= 0; 	// Tilauksen verollinen loppusumma tilauksen valuutassa
				$summa_eieri			= 0;	// Tilauksen verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo  					= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa
				$arvo_eieri				= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo				= 0;	// Tilauksen veroton loppusumma yhtiön valuutassa
				$kotiarvo_eieri			= 0;	// Tilauksen veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
				$kate					= 0;	// Tilauksen kate yhtiön valuutassa
				$kate_eieri				= 0;	// Tilauksen kate yhtiön valuutassa ilman erikoisalennusta
				$ostot					= 0;	// Tilauksen Ostot tilauksen valuutassa
				$ostot_eieri			= 0;	// Tilauksen Ostot tilauksen valuutassa ilman erikoisalennusta

				$summa_kotimaa 			= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa
				$summa_kotimaa_eieri 	= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo_kotimaa			= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa
				$arvo_kotimaa_eieri		= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo_kotimaa		= 0;	// Kotimaan toimitusten veroton loppusumma yhtiön valuutassa
				$kotiarvo_kotimaa_eieri	= 0;	// Kotimaan toimitusten veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
				$kate_kotimaa			= 0;	// Kotimaan toimitusten kate yhtiön valuutassa
				$kate_kotimaa_eieri		= 0;	// Kotimaan toimitusten kate yhtiön valuutassa ilman erikoisalennusta

				$summa_ulkomaa			= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa
				$summa_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo_ulkomaa			= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa
				$arvo_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo_ulkomaa		= 0;	// Ulkomaan toimitusten veroton loppusumma yhtiön valuutassa
				$kotiarvo_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
				$kate_ulkomaa			= 0;	// Ulkomaan toimitusten kate yhtiön valuutassa
				$kate_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten kate yhtiön valuutassa ilman erikoisalennusta

				// typekästätään koska joskus tulee spacena.. en tajua.
				$laskurow["erikoisale"] = (float) $laskurow["erikoisale"];

				while ($alvrow = mysql_fetch_array($alvresult)) {

					if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
						$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
						$hinta_myy = "(tuote.myyntihinta/$laskurow[vienti_kurssi])";
					}
					else {
						$hinta_riv = "tilausrivi.hinta";
						$hinta_myy = "tuote.myyntihinta";
					}


					if ($kukarow['hinnat'] == 1) {
						$lisat = "	$hinta_myy / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv,
									$hinta_myy / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) rivihinta,
									$hinta_myy / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv_ei_erikoisaletta,
									$hinta_myy / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) rivihinta_ei_erikoisaletta";
					}
					else {
						$lisat = "	if(tilausrivi.alv<500, $hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100), 0) alv,
									$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
									if(tilausrivi.alv<500, $hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) * (tilausrivi.alv/100), 0) alv_ei_erikoisaletta,
									$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) rivihinta_ei_erikoisaletta,
									tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) kotirivihinta,
									tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) kotirivihinta_ei_erikoisaletta";
					}

					$aquery = "	SELECT
								tuote.sarjanumeroseuranta,
								tuote.ei_saldoa,
								tuote.tuoteno,
								tuote.kehahin,
								tilausrivi.tunnus,
								tilausrivi.varattu+tilausrivi.jt varattu,
								tilausrivin_lisatiedot.osto_vai_hyvitys,
								$lisat
								FROM tilausrivi
								JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								$tunnuslisa
								and tilausrivi.tunnus in ($alvrow[rivit])";
					$aresult = mysql_query($aquery) or pupe_error($aquery);

					while($arow = mysql_fetch_array($aresult)) {
						$rivikate 		= 0;	// Rivin kate yhtiön valuutassa
						$rivikate_eieri	= 0;	// Rivin kate yhtiön valuutassa ilman erikoisalennusta

						if ($arow["sarjanumeroseuranta"] == "S") {
							//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
							if ($arow["varattu"] > 0) {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
								$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $arow["tunnus"]);

								// Kate = Hinta - Ostohinta
								$rivikate 		= $arow["kotirivihinta"] - ($ostohinta * $arow["varattu"]);
								$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - ($ostohinta * $arow["varattu"]);
							}
							elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "O") {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

								// Kate = 0
								$rivikate 		= 0;
								$rivikate_eieri = 0;

								$ostot		 += $arow["kotirivihinta"];
								$ostot_eieri += $arow["kotirivihinta_ei_erikoisaletta"];
							}
							elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "") {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ

								//Tähän hyvitysriviin liitetyt sarjanumerot
								$query = "	SELECT sarjanumero, kaytetty
											FROM sarjanumeroseuranta
											WHERE yhtio 		= '$kukarow[yhtio]'
											and ostorivitunnus 	= '$arow[tunnus]'";
								$sarjares = mysql_query($query) or pupe_error($query);

								$ostohinta = 0;

								while($sarjarow = mysql_fetch_array($sarjares)) {

									// Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
									$query  = "	select tilausrivi.rivihinta/tilausrivi.kpl ostohinta
												FROM sarjanumeroseuranta
												JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
												WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
												and sarjanumeroseuranta.tuoteno 	= '$arow[tuoteno]'
												and sarjanumeroseuranta.sarjanumero = '$sarjarow[sarjanumero]'
												and sarjanumeroseuranta.kaytetty 	= '$sarjarow[kaytetty]'
												and sarjanumeroseuranta.myyntirivitunnus > 0
												and sarjanumeroseuranta.ostorivitunnus   > 0
												ORDER BY sarjanumeroseuranta.tunnus
												LIMIT 1";
									$sarjares1 = mysql_query($query) or pupe_error($query);
									$sarjarow1 = mysql_fetch_array($sarjares1);

									$ostohinta += $sarjarow1["ostohinta"];
								}

								$rivikate 		= $arow["kotirivihinta"] - $ostohinta;
								$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - $ostohinta;
							}
							else {
								$rivikate 		= 0;
								$rivikate_eieri = 0;
							}
						}
						else {
							$rivikate 		= $arow["kotirivihinta"]  - (kehahin($arow["tuoteno"])*$arow["varattu"]);
							$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"]  - (kehahin($arow["tuoteno"])*$arow["varattu"]);
						}

						if ($arow['ei_saldoa'] == '' and $arow['varattu'] > 0) {
							if (trim(strtoupper($alvrow["maa"])) == trim(strtoupper($laskurow["toim_maa"]))) {
								$summa_kotimaa			+= $arow["rivihinta"]+$arow["alv"];
								$summa_kotimaa_eieri	+= $arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"];
								$arvo_kotimaa			+= $arow["rivihinta"];
								$arvo_kotimaa_eieri		+= $arow["rivihinta_ei_erikoisaletta"];
								$kotiarvo_kotimaa		+= $arow["kotirivihinta"];
								$kotiarvo_kotimaa_eieri	+= $arow["kotirivihinta_ei_erikoisaletta"];
								$kate_kotimaa			+= $rivikate;
								$kate_kotimaa_eieri		+= $rivikate_eieri;

							}
							else {
								$summa_ulkomaa			+= $arow["rivihinta"]+$arow["alv"];
								$summa_ulkomaa_eieri	+= $arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"];
								$arvo_ulkomaa			+= $arow["rivihinta"];
								$arvo_ulkomaa_eieri		+= $arow["rivihinta_ei_erikoisaletta"];
								$kotiarvo_ulkomaa		+= $arow["kotirivihinta"];
								$kotiarvo_ulkomaa_eieri	+= $arow["kotirivihinta_ei_erikoisaletta"];
								$kate_ulkomaa			+= $rivikate;
								$kate_ulkomaa_eieri		+= $rivikate_eieri;
							}
						}

						$summa			+= $arow["rivihinta"]+$arow["alv"];
						$summa_eieri	+= $arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"];
						$arvo			+= $arow["rivihinta"];
						$arvo_eieri		+= $arow["rivihinta_ei_erikoisaletta"];
						$kotiarvo		+= $arow["kotirivihinta"];
						$kotiarvo_eieri	+= $arow["kotirivihinta_ei_erikoisaletta"];
						$kate			+= $rivikate;
						$kate_eieri		+= $rivikate_eieri;
					}
				}

				//Jos myyjä on myymässä ulkomaan varastoista liian pienellä summalla
				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
					$ulkom_huom = "<font class='error'>".t("HUOM! Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $laskurow[valkoodi]</font>";
				}
				elseif ($kukarow["extranet"] != "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {

					$query = "SELECT ulkomaanlisa FROM toimitustapa where yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
					$ulklisres = mysql_query($query) or pupe_error($query);
					$ulklisrow = mysql_fetch_array($ulklisres);
					if ($ulklisrow['ulkomaanlisa'] > 0) {
						$ulkom_huom = "<font class='message'>".t("Olet tilaamassa ulkomaanvarastosta, rahtikulut nousevat")." ".round(laskuval($ulklisrow["ulkomaanlisa"],$laskurow["vienti_kurssi"]),0)." $laskurow[valkoodi] ".t("verran")." </font><br>";
					}
					else {
						$ulkom_huom = "";
					}

				}
				else {
					$ulkom_huom = "";
				}

				$ycspan=4;

				if($yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
					$ycspan++;
				}
				if ($kukarow['hinnat'] == 1) {
					$ycspan = $ycspan - 2;
				}
				if ($jarjlisa != "") {
					$ycspan--;
				}
				if($kukarow["resoluutio"] == "I") {
					$ycspan++;
				}
				if($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {
					$ycspan++;
				}
				if($kukarow["extranet"] != "" and $yhtiorow['varastopaikan_lippu'] != '') {
					$ycspan++;
				}

				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
					echo "<tr>$jarjlisa
							<td class='back' colspan='$ycspan'>&nbsp;</td>
							<th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_kotimaa_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa_eieri != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_kotimaa_eieri/($kotiarvo_kotimaa_eieri-$ostot_eieri))."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					echo "<tr>$jarjlisa
						<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
						<th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa_eieri != 0  and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa_eieri/($kotiarvo_ulkomaa_eieri-$ostot_eieri))."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}
				else {
					echo "<tr>$jarjlisa
							<td class='back' colspan='$ycspan'>&nbsp;</td>
							<th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_eieri != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) and $kotiarvo_eieri-$ostot_eieri != 0) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_eieri/($kotiarvo_eieri-$ostot_eieri))."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}

				if ($laskurow["erikoisale"] > 0 and $kukarow['hinnat'] == 0) {
					echo "<tr>$jarjlisa
						<td class='back' colspan='$ycspan'>&nbsp;</td>
						<th colspan='5' align='right'>".t("Erikoisalennus")." $laskurow[erikoisale]%:</th>
						<td class='spec' align='right'>".sprintf("%.2f", ($arvo_eieri-$arvo)*-1)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
						echo "<tr>$jarjlisa
								<td class='back' colspan='$ycspan'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
								<td class='spec' align='right' nowrap>".sprintf("%.2f",$arvo_kotimaa)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kotimaa/($kotiarvo_kotimaa-$ostot))."%</td>";
						}
						elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

						echo "<tr>$jarjlisa
							<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
							<th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa/($kotiarvo_ulkomaa-$ostot))."%</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
					}
					else {
						echo "<tr>$jarjlisa
								<td class='back' colspan='$ycspan'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
								<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate/($kotiarvo-$ostot))."%</td>";
						}
						elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
					}
				}

				// Etsitään asiakas
				$query = "	SELECT laskunsummapyoristys
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				//Käsin syötetty summa johon lasku pyöristetään
				if (abs($laskurow["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
					$summa = sprintf("%.2f",$laskurow["hinta"]);
				}

				//Jos laskun loppusumma pyöristetään lähimpään tasalukuun
				if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
					$summa = sprintf("%.2f",round($summa ,0));
				}

				echo "<tr>$jarjlisa
						<td class='back' colspan='$ycspan'>&nbsp;</td>
						<th colspan='5' align='right'>".t("Verollinen yhteensä").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$summa)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'>&nbsp;</td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";


				//annetaan mahdollisuus antaa loppusumma joka jyvitetään riveille arvoosuuden mukaan
				if ($kukarow["extranet"] == "" and (($yhtiorow["salli_jyvitys_myynnissa"] == "" and $kukarow['kassamyyja'] != '') or ($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] != '') or ($yhtiorow["salli_jyvitys_myynnissa"] == "K") or $toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI")) {

					echo "<tr>$jarjlisa";

					if ($jyvsumma == '') {
						$jyvsumma = '0.00';
					}

					$xcolspan = $ycspan-4;

					if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {
						echo "	<th colspan='2' nowrap>".t("Näytä lomake").":</th>
								<td colspan='2' nowrap>
								<form name='valmis' action='tulostakopio.php' method='post' name='tulostakopio'>
									<input type='hidden' name='otunnus' value='$tilausnumero'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='lopetus' value='$PHP_SELF////toim=$toim//tilausnumero=$tilausnumero//from=LASKUTATILAUS//lopetus=$lopetus//tee='>";

						echo "<select name='toim'>";

						if (file_exists("tulosta_tarjous.inc") and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI")) {
							echo "<option value='TARJOUS'>Tarjous</option>";
						}
						if (file_exists("tulosta_tilausvahvistus_pdf.inc")) {
							echo "<option value='TILAUSVAHVISTUS'>Tilausvahvistus</option>";
						}
						if (file_exists("tulosta_myyntisopimus.inc")) {
							echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</option>";
						}
						if (file_exists("tulosta_osamaksusoppari.inc")) {
							echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</option>";
						}
						if (file_exists("tulosta_luovutustodistus.inc")) {
							echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</option>";
						}
						if (file_exists("tulosta_vakuutushakemus.inc")) {
							echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</option>";
						}
						if (file_exists("../tyomaarays/tulosta_tyomaarays.inc")) {
							echo "<option value='TYOMAARAYS'>Työmäärys</option>";
						}
						if (file_exists("tulosta_rekisteriilmoitus.inc")) {
							echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</option>";
						}
						if ($toim == "PROJEKTI") {
							echo "<option value='TILAUSVAHVISTUS'>Tilausvahvistus</option>";
						}

						echo "		</select>
								<input type='submit' name='NAYTATILAUS' value='".t("Näytä")."'>
								<input type='submit' name='TULOSTA' value='".t("Tulosta")."'>
							</form>
							</td>
							<td class='back' colspan='$xcolspan'></td>";
					}
					else {
						echo "<td class='back' colspan='$ycspan' nowrap>&nbsp;</td>";
					}

					if (strlen(sprintf("%.2f",$summa)) > 7) {
						$koko = strlen(sprintf("%.2f",$summa));
					}
					else {
						$koko = '7';
					}

					if($toim != "PROJEKTI") {
						echo "	<th colspan='5'>".t("Pyöristä loppusummaa").":</th>
								<td class='spec'>
								<form name='pyorista' action='$PHP_SELF' method='post' autocomplete='off'>
										<input type='hidden' name='tilausnumero' value='$tilausnumero'>
										<input type='hidden' name='tee' value='jyvita'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
										<input type='hidden' name='projektilla' value='$projektilla'>";

						echo "	<input type='text' size='$koko' name='jysum' value='".sprintf("%.2f",$summa)."'  Style='font-size: 8pt; padding:0; text-align:right'></td>";

						if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right'>&nbsp;</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td>";

						echo "<td class='back' colspan='2'><input type='submit' value='".t("Jyvitä")."'></form></td>";

					}
					echo "</tr>";
				}
			}

			echo "</table>";

			if ($kukarow["extranet"] != "" and $arvo_ulkomaa != 0 and $ulkom_huom != '') {
				echo "$ulkom_huom";
			}
		}
		else {
			$tilausok++;
		}

		// JT-rivikäyttöliittymä
		if ($jt_kayttoliittyma == "kylla" and $laskurow["liitostunnus"] > 0 and $toim != "TYOMAARAYS" and $toim != "REKLAMAATIO" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and $toim != "TARJOUS") {

			//katotaan eka halutaanko asiakkaan jt-rivejä näkyviin
			$asjtq = "select jtrivit from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
			$asjtapu = mysql_query($asjtq) or pupe_error($asjtq);
			$asjtrow = mysql_fetch_array($asjtapu);

			if (mysql_num_rows($asjtapu) == 1 and $asjtrow["jtrivit"] == 0) {

				echo "<br>";

				$toimittaja		= "";
				$toimittajaid	= "";

				$asiakasno 		= $laskurow["ytunnus"];
				$asiakasid		= $laskurow["liitostunnus"];

				$automaaginen 	= "";
				$jarj	 		= "toimaika";
				$tee			= "JATKA";
				$tuotenumero	= "";
				$toimi			= "";
				$tilaus_on_jo 	= "KYLLA";
				$superit		= "";
				$varastosta		= array();

				if ($toim == 'SIIRTOLISTA') {
					$toimi = "JOO";
				}

				$query = "	SELECT *
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'";
				$vtresult = mysql_query($query) or pupe_error($query);

				while ($vrow = mysql_fetch_array($vtresult)) {
					if ($vrow["tyyppi"] != 'E' or $laskurow["varasto"] == $vrow["tunnus"]) {
						$varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
					}
				}

				if (mysql_num_rows($vtresult) != 0 and count($varastosta) != 0) {
					require ('jtselaus.php');
				}
				else {
					echo "<font class='message'>".t("Ei toimitettavia JT-rivejä!")."</font>";
				}

			}
	    }
	}

	// tulostetaan loppuun parit napit..
	if ((int) $kukarow["kesken"] != 0) {
		echo "<br><table width='100%'><tr>$jarjlisa";

		if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
			echo "	<td class='back'>
					<form name='laskuta' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LASKUTAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Laskuta valitut rivit")." *'>
					</form></td>";

			echo "	<td class='back'>
					<form name='lepaa' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LEPAAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Jätä myyntitili lepäämään")." *'>
					</form></td>";

		}


		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TYOMAARAYS") {
			echo "	<td class='back'>
					<form name='tlepaamaan' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Työmääräys lepäämään")." *'>
					</form></td>";

		}

        if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "REKLAMAATIO") {
			echo "	<td class='back'>
					<form name='rlepaamaan' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Reklamaatio lepäämään")." *'>
					</form></td>";

		}

		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TARJOUS"  and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			echo "<td class='back'>";

			//	Onko vielä optiorivejä?
			$query  = "	SELECT tilausrivin_lisatiedot.tunnus
						FROM lasku
						JOIN tilausrivi ON tilausrivi.otunnus = lasku.tunnus
						JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.positio = 'Optio'
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$kukarow[kesken]'";
			$optiotarkres = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($optiotarkres) == 0) {

				//	Käytetäänkö projekteja?
				$query = "select tunnus from oikeu where yhtio='$kukarow[yhtio]' and nimi='tilauskasittely/tilaus_myynti.php' and alanimi='PROJEKTI' LIMIT 1";
				$projektitarkres = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($projektitarkres) == 1 and $laskurow["tunnusnippu"] > 0) {
					$tarjouslisa=t("Perusta tilaukselle projekti").":<input type='checkbox' name='perusta_projekti'><br>";
				}
				else {
					$tarjouslisa="";
				}

				echo "	<form name='hyvaksy' action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						$tarjouslisa
						<input type='submit' value='".t("Hyväksy tarjous")."'>
						</form>";
			}
			else {
				echo t("Poista optiot ennen tilauksen tekoa")."<br><br>";
			}

			echo "	<br><form name='hylkaa' action='$PHP_SELF' method='post' onsubmit=\"return confirm('Oletko varma että haluat hylätä tarjouksen $kukarow[kesken]?')\">
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='HYLKAATARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hylkää tarjous")."'>
					</form>";

			echo "</td>";

		}

		//Näytetään tilaus valmis nappi
		if (($muokkauslukko == "" or $toim=="PROJEKTI" or $toim=="YLLAPITO") and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			echo "<td class='back' valign='top'>";

			// Jos myyjä myy todella pienellä summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
			$javalisa = "";

			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function ulkomaa_verify(){
								msg = '".t("Olet toimittamassa ulkomailla sijaitsevasta varastosta tuotteita")." $ulkomaa_kaikkiyhteensa $yhtiorow[valkoodi]! ".t("Oletko varma, että tämä on fiksua")."?';
								return confirm(msg);
						}
						</SCRIPT>";

				$javalisa = "onSubmit = 'return ulkomaa_verify()'";
			}

			// otetaan maksuehto selville.. käteinen muuttaa asioita
			$query = "	select *
						from maksuehto
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maksuehtorow = mysql_fetch_array($result);

			// jos kyseessä on käteiskauppaa
			$kateinen = "";
			if ($maksuehtorow['kateinen']!='') {
				$kateinen = "X";
			}

			if($maksuehtorow['jaksotettu'] != '') {
				$query = "	select yhtio
							from maksupositio
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$laskurow[jaksotettu]'";
				$jaksoresult = mysql_query($query) or pupe_error($query);
			}

			if ($laskurow['sisainen'] != '' and $maksuehtorow['jaksotettu'] != '') {
				echo "<font class='error'>".t("VIRHE: Sisäisellä laskulla ei voi olla maksusopimusta!")."</font>";
			}
			elseif ($maksuehtorow['jaksotettu'] != '' and mysql_num_rows($jaksoresult) == 0) {
				echo "<font class='error'>".t("VIRHE: Tilauksella ei ole maksusopimusta!")."</font>";
			}
			else {

				echo "
					<form name='kaikkyht' action='$PHP_SELF' method='post' $javalisa>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='VALMIS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='kaikkiyhteensa' value='".sprintf('%.2f',$summa)."'>";

				if($arvo_ulkomaa != 0) {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
				}
				else {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='NO'>";
				}

				if ($kukarow["extranet"] == "" and $kateinen == 'X' and ($kukarow["kassamyyja"] != '' or ($kukarow["dynaaminen_kassamyynti"] != "" or ($kukarow["dynaaminen_kassamyynti"] == "" and $yhtiorow["dynaaminen_kassamyynti"] != "")))) {

					if ($kukarow["dynaaminen_kassamyynti"] != "" or ($kukarow["dynaaminen_kassamyynti"] == "" and $yhtiorow["dynaaminen_kassamyynti"] != "")) {
						echo "</tr><tr><td class='back'>Kassalipas: <select name='kertakassa'><option value=''>".t("Ei kassaan")."</option>";

						$query  = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' AND laji='KASSA' ORDER BY selite";
						$vares = mysql_query($query) or pupe_error($query);

						while ($varow = mysql_fetch_array($vares)) {
							$sel='';
							
							if ($varow['selite'] == $laskurow["kassalipas"]) {
								$sel = 'selected';
							}
							
							echo "<option value='$varow[selite]' $sel>$varow[selitetark]</option>";
						}

						echo "</select></td></tr>";
					}

					echo "<tr><td class='back'>Tulostuspaikka: <select name='valittu_kopio_tulostin'>";
					echo "<option value=''>".t("Oletus")."</option>";

					$querykieli = "	SELECT *
									from kirjoittimet
									where yhtio = '$kukarow[yhtio]'
									ORDER BY kirjoitin";
					$kires = mysql_query($querykieli) or pupe_error($querykieli);

					while ($kirow=mysql_fetch_array($kires)) {
						echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
					}

					echo "</select>";


					echo "<br><br></td></tr><tr>$jarjlisa<td class='back'>";
				}

				if (($yhtiorow["tee_osto_myyntitilaukselta"] == "Z" or $yhtiorow["tee_osto_myyntitilaukselta"] == "X") and in_array($toim, array("PROJEKTI","RIVISYOTTO", "PIKATILAUS"))) {
					echo "<input type='submit' name='tee_osto' value='$otsikko ".t("valmis")." & ".t("Tee tilauksesta ostotilaus")."'><br>";
				}
				echo "<input type='hidden' name='kateinen' value='$kateinen'>";
				echo "<input type='submit' ACCESSKEY='V' value='$otsikko ".t("valmis")."'>";
				echo "</form>";
			}

			echo "</td>";
		}
		elseif($sarjapuuttuu > 0) {
			echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu sarjanumeroita!")."</font>";
		}


		//	Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
		if ($laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
			$query = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
			$abures = mysql_query($query) or pupe_error($query);

			$projektilask = mysql_num_rows($abures);
		}
		else {
			$projektilask = 0;
		}

		if ($muokkauslukko == "" and ($toim != "PROJEKTI" or ($toim == "PROJEKTI" and $projektilask == 0))) {
			echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
								msg = '".t("Haluatko todella poistaa tämän tietueen?")."';
								return confirm(msg);
						}
				</SCRIPT>";

			echo "<td align='right' class='back' valign='top'>
					<form name='mitatoikokonaan' action='$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='POISTA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Mitätöi koko")." $otsikko *'>
					</form></td>";
		}


		echo "</tr>";

		if ($lopetus != "") {

			$lopetus = str_replace('////','?', $lopetus);
			$lopetus = str_replace('//','&',  $lopetus);

			echo "<tr>$jarjlisa<td class='back'><form name='lopetusformi' action='$lopetus' method='post'>
					<input type='submit' value='".t("Siirry sinne mistä tulit")."'>
					</form></td></tr>";
		}

		echo "</table>";

	}
}


if (file_exists("../inc/footer.inc")) {
	require ("../inc/footer.inc");
}
else {
	require ("footer.inc");
}

?>
