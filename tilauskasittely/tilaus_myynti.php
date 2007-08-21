<?php
//	Ladataan javahelpperit
$pupejs=1;

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
		$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		exit;
	}
	else {
		$query = "update kuka set kesken='$tilausnumero' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		// Näin ostataan valita pikatilaus
		if ($toim == "RIVISYOTTO" and isset($PIKATILAUS)) {
			$toim = "PIKATILAUS";
		}
		// Jos tullaan projektille pitää myös aktioida $projektilla
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
			$asiakasid=$tarkrow["liitostunnus"];
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
	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

// Extranet keississä asiakasnumero tulee käyttäjän takaa
if ($kukarow["extranet"] != '') {
	// Haetaan asiakkaan tunnuksella
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$extra_asiakas = mysql_fetch_array($result);
		$ytunnus 	= $extra_asiakas["ytunnus"];
		$asiakasid 	= $extra_asiakas["tunnus"];

		if ($kukarow["kesken"] != 0) {
			$tilausnumero = $kukarow["kesken"];
		}
	}
	else {
		echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
		exit;
	}
}

//katsotaan että kukarow kesken, $tilausnumero ja $kukarow[kesken] stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
	echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
	exit;
}

// Vaihdetaan tietyn projektin toiseen toimitukseen
if ((int) $valitsetoimitus > 0) {
	$tee = "AKTIVOI";
	$tilausnumero = $valitsetoimitus;
	$from = "VALITSETOIMITUS";

	$query = "select tila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
	$result = mysql_query($query) or pupe_error($query);
	$toimrow = mysql_fetch_array($result);

	if ($toimrow["tila"] == "L" or $toimrow["tila"] == "N") {
		$toim = "RIVISYOTTO";
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

	$tilausnumero = luo_myyntitilausotsikko($asiakasid);
	$kukarow["kesken"] = $tilausnumero;
	$kaytiin_otsikolla = "NOJOO!";
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] != 0) {

	if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "REKLAMAATIO")) {
		$query  = "	select *
					from lasku, tyomaarays
					where lasku.tunnus='$kukarow[kesken]'
					and lasku.yhtio='$kukarow[yhtio]'
					and tyomaarays.yhtio=lasku.yhtio
					and tyomaarays.otunnus=lasku.tunnus";
	}
	else {
		$query 	= "	select *
					from lasku
					where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	}

	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow   = mysql_fetch_array($result);
	if($yhtiorow["tilauksen_kohteet"] == "K") {
		$query 	= "	select *
					from laskun_lisatiedot
					where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$lasklisatied_row  = mysql_fetch_array($result);
	}

	if ($yhtiorow["suoratoim_ulkomaan_alarajasumma"] > 0 and $laskurow["vienti_kurssi"] != 0) {
		$yhtiorow["suoratoim_ulkomaan_alarajasumma"] = round(laskuval($yhtiorow["suoratoim_ulkomaan_alarajasumma"], $laskurow["vienti_kurssi"]),0);
	}

	if ($laskurow["toim_maa"] == "") $laskurow["toim_maa"] = $yhtiorow['maa'];

}

//tietyissä keisseissä tilaus lukitaan (ei syöttöriviä eikä muota muokkaa/poista-nappuloita)
$muokkauslukko = $state = "";

//	Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
if ($laskurow["tunnusnippu"] > 0 and $toim=="PROJEKTI") {
	$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
	$abures = mysql_query($query) or pupe_error($query);
	$projektilask = (int) mysql_num_rows($abures);
}

if ($kukarow["extranet"] == "" and ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") or ($toim == "PROJEKTI" and $projektilask>0) or ($toim=="TARJOUS" and $projektilla>0) or $laskurow["alatila"] == "X") {
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
		$row=mysql_fetch_array($result);
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
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'";
	$sres = mysql_query($query) or pupe_error($query);

	while ($srow = mysql_fetch_array($sres)) {
		if ($srow["varattu"] < 0) {
			// dellataan koko rivi jos sitä ei ole vielä myyty
			$query = "delete from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
			$sarjares = mysql_query($query) or pupe_error($query);
			if (mysql_affected_rows() == 0) {
				// merkataan osorivitunnus nollaksi
				$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			// merktaan myyntirivitunnus nollaks
			$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and myyntirivitunnus='$srow[tunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
		}
	}

	//	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hylätyiksi *** tämän voisi varmaan tehdä myös kaikki kerralla? ***
	$query = "select tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
	$abures = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($abures) > 0) {
		while ($row = mysql_fetch_array($abures)) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
			$result = mysql_query($query) or pupe_error($query);

			//Nollataan sarjanumerolinkit
			$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio='$kukarow[yhtio]'
							and tilausrivi.otunnus='$row[tunnus]'";
			$sres = mysql_query($query) or pupe_error($query);

			while ($srow = mysql_fetch_array($sres)) {
				if ($srow["varattu"] < 0) {
					// dellataan koko rivi jos sitä ei ole vielä myyty
					$query = "delete from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
					$sarjares = mysql_query($query) or pupe_error($query);
					if (mysql_affected_rows() == 0) {
						// merkataan osorivitunnus nollaksi
						$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
				}
				else {
					// merktaan myyntirivitunnus nollaks
					$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and myyntirivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
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

if(in_array($jarjesta, array("moveUp", "moveDown", "first", "last")) and $rivitunnus>0) {

	if($laskurow["tunnusnippu"]>0 and $toim !="TARJOUS") {
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

	if($jarjesta == "moveUp") {
		$ehto = "and tunnus<$rivitunnus order by tunnus desc limit 1 ";
	}
	elseif($jarjesta == "moveDown") {
		$ehto = " and tunnus>$rivitunnus order by tunnus asc limit 1";
	}
	elseif($jarjesta == "first") {
		$ehto = "order by tunnus asc limit 1";
	}
	elseif($jarjesta == "last") {
		$ehto = "order by tunnus desc limit 1";
	}

	$query = "select tunnus from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tyyppi !='D' and otunnus IN ($tunnarit) $ehto";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	$kohde = $row["tunnus"];
	if($kohde>0 and $kohde != $rivitunnus) {
		$query = "select * from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and otunnus='{$kukarow["kesken"]}' and tunnus='$kohde'";
		$kohderes = mysql_query($query) or pupe_error($query);
		$kohderow = mysql_fetch_array($kohderes);
		$kohdeupd="";
		for ($i=1; $i < mysql_num_fields($kohderes)-1; $i++) {
			$kohdeupd .= mysql_field_name($kohderes,$i)."='".$kohderow[$i]."', ";
		}
		$kohdeupd 		= substr($kohdeupd, 0 ,-2);

		$query = "select * from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and otunnus='{$kukarow["kesken"]}' and tunnus='$rivitunnus'";
		$lahderes = mysql_query($query) or pupe_error($query);
		$lahderow = mysql_fetch_array($lahderes);
		$lahdeupd="";
		for ($i=1; $i < mysql_num_fields($lahderes)-1; $i++) {
			$lahdeupd .= mysql_field_name($lahderes,$i)."='".$lahderow[$i]."', ";
		}
		$lahdeupd 	= substr($lahdeupd,0 , -2);

		//	Kaikki OK vaihdetaan data päikseen
		$query = "UPDATE tilausrivi SET $kohdeupd WHERE yhtio = '$kukarow[yhtio]' and otunnus='{$kukarow["kesken"]}' and tunnus='$rivitunnus'";
		$updres=mysql_query($query) or pupe_error($query);

		$query = "UPDATE tilausrivi SET $lahdeupd WHERE yhtio = '$kukarow[yhtio]' and otunnus='{$kukarow["kesken"]}' and tunnus='$kohde'";
		$updres=mysql_query($query) or pupe_error($query);
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! riviä ei voi siirtää!")."</font><br>";
	}
}

// Poistetaan tilaus
if ($tee == 'POISTA' and $muokkauslukko == "") {

	// poistetaan tilausrivit, mutta jätetään PUUTE rivit analyysejä varten...
	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var<>'P'";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan sarjanumerolinkit
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'";
	$sres = mysql_query($query) or pupe_error($query);

	while ($srow = mysql_fetch_array($sres)) {
		if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
			// merktaan siirtolistatunnus nollaks
			$query = "update sarjanumeroseuranta set siirtorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
		}
		elseif ($row["varattu"] < 0) {
			// dellataan koko rivi jos sitä ei ole vielä myyty
			$query = "delete from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
			$sarjares = mysql_query($query) or pupe_error($query);
			if (mysql_affected_rows() == 0) {
				// merkataan osorivitunnus nollaksi
				$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			// merktaan myyntirivitunnus nollaks
			$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and myyntirivitunnus='$srow[tunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
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

	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	if($kuakrow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $toim!="TARJOUS" and $toim!="PROJEKTI") {

		$aika=date("d.m.y @ G:i:s", time());
		echo "<font class='message'>".t("Osatoimitus")." ($aika) $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";

		if($projektilla>0) {
			$tilausnumero		= $laskurow["tunnusnippu"];

			//	Hypätään takaisin otsikolle
			echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
			echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
			die();
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

	if ($kuakrow["extranet"] == "" and $lopetus != '') {
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
		$query = "	select yhtio
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$kukarow[kesken]'
					and tyyppi in ('W','M','V')
					and varattu  > 0";
		$sres  = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sres) == 0) {
			echo "<font class='message'> ".t("Ei valmistettavaa. Valmistus siirrettiin myyntipuolelle")."! </font><br>";

			$query  = "	update lasku set
						tila 	= 'N',
						alatila	= ''
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
		if($laskurow["tunnusnippu"]>0) {
			$result = mysql_query($query) or pupe_error($query);
			$query="select tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tunnus <= '$laskurow[tunnus]' and tila='T'";
			$result = mysql_query($query) or pupe_error($query);
			$tarjous=$laskurow["tunnusnippu"]."/".mysql_num_rows($result);
		}
		else {
			$tarjous=$laskurow["tunnus"];
		}

		kalenteritapahtuma ("Memo", "Tarjous asiakkaalle", "Tarjous $tarjous tulostettu.\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]", $laskurow["liitostunnus"], "", $lasklisatied_row["yhteyshenkilo_tekninen"], $laskurow["tunnus"]);

		kalenteritapahtuma ("Muistutus", "Tarjous asiakkaalle", "Muista tarjous $tarjous", $laskurow["liitostunnus"], "K", $lasklisatied_row["yhteyshenkilo_tekninen"], $laskurow["tunnus"]);

		// tilaus ei enää kesken...
		$query	= "update kuka set kesken=0 where yhtio='{$kukarow["yhtio"]}' and kuka='{$kukarow["kuka"]}'";
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
			$query  = "	update lasku set
						tila = 'N',
						alatila='F'
						where yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);


			// tilaus ei enää kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
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
	if($kuakrow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $toim!="TARJOUS") {

		$aika=date("d.m.y @ G:i:s", time());
		echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

		if($projektilla>0) {
			$tilausnumero		= $laskurow["tunnusnippu"];

			//	Hypätään takaisin otsikolle
			echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
			echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
			die();
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

	if ($kuakrow["extranet"] == "" and $lopetus != '') {
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

if ($kukarow["extranet"] == "" and $toim == "TARJOUS" and $tee == "SMS") {
	$kala = exec("echo \"Terveisiä $yhtiorow[nimi]\" | /usr/bin/gnokii --sendsms +358505012254 -r");
	echo "$kala<br>";
	$tee = "";
}

//Voidaan tietyissä tapauksissa kopstat tästä suoraan uusi tilaus
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
	$query   	= "	select *
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
		echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}
	if ($kukarow['kesken'] != '0') {
		$tilausnumero=$kukarow['kesken'];
	}

	// Tässä päivitetään 'pikaotsikkoa' jos kenttiin on jotain syötetty
	if ($toimitustapa != '' or $tilausvahvistus != '' or $viesti != '' or $myyjanro != '' or $myyja != '') {

		if ($myyjanro != '') {
			$apuqu = "	select *
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

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);

		if (mysql_num_rows($meapu) == 1 and $toimitustapa != '') {
			$meapurow = mysql_fetch_array($meapu);

			// jos kyseessä oli käteinen
			if ($meapurow["kateinen"] != "") {
				// haetaan toimitustavan tiedot tarkastuksia varten
				$apuqu2 = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
				$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);
				$meapu2row = mysql_fetch_array($meapu2);

				// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
				if ($meapu2row["nouto"] == "") {
					$apuqu = "select * from toimitustapa where yhtio = '$kukarow[yhtio]' and nouto != '' order by jarjestys limit 1";
					$meapu = mysql_query($apuqu) or pupe_error($apuqu);
					$apuro = mysql_fetch_array($meapu);
					$toimitustapa = $apuro['selite'];

					echo "<font class='error'>".t("Toimitustapa on oltava nouto, koska maksuehto on käteinen")."!</font><br><br>";
				}
			}
		}

		$query  = "	update lasku set
					toimitustapa	= '$toimitustapa',
					viesti 			= '$viesti',
					tilausvahvistus = '$tilausvahvistus',
					myyja			= '$myyja'
					where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		//Haetaan laskurow uudestaan
		$query   	= "	select *
						from lasku
						where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$laskurow   = mysql_fetch_array($result);

		if($yhtiorow["tilauksen_kohteet"] == "K") {
			$query 	= "	select *
						from laskun_lisatiedot
						where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
			$result  	= mysql_query($query) or pupe_error($query);
			$lasklisatied_row  = mysql_fetch_array($result);
		}

	}

 	// jos asiakasnumero on annettu
	if ($laskurow["liitostunnus"] > 0) {
		if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
			$jarjlisa="<td class='back' width='55px'>&nbsp;</td>";
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
					<input type='submit' ACCESSKEY='m' value='".t("Muuta Otsikkoa")."' Style='font-size: 8pt; padding:0;'>
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
			$query  = "	SELECT count(*) kpl from tilausrivi USE INDEX (yhtio_tyyppi_var)
						JOIN lasku USE INDEX (primary) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$laskurow[liitostunnus]')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','G')
						and tilausrivi.var = 'J'
						and tilausrivi.keratty = ''
						and tilausrivi.uusiotunnus = 0
						and tilausrivi.varattu = 0
						and tilausrivi.kpl = 0
						and tilausrivi.jt <> 0";
			$jtapuresult = mysql_query($query) or pupe_error($query);
			$jtapurow = mysql_fetch_array($jtapuresult);

			if ($jtapurow["kpl"] > 0) {
				if($kaytiin_otsikolla == "NOJOO!") {
					$class = 'tumma';
				}
				else {
					$class = 'back';
				}

				echo "	<td class='$class'><form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

				if ($jt_kayttoliittyma == "kylla") {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value=''>
							<input type='submit' value='".t("Piilota JT-rivit")."' Style='font-size: 8pt; padding:0;'>";
				}
				else {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value='kylla'>
							<input type='submit' value='".t("Näytä JT-rivit")."' Style='font-size: 8pt; padding:0;'>";
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
				<input type='Submit' value='".t("Maksusuunnitelma")."' Style='font-size: 8pt; padding:0;'>
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
						<input type='Submit' value='".t("Lisaa kulut")."' Style='font-size: 8pt; padding:0;'>
					</form>
				</td>";

			/*
			echo "<form action = '../tilausmemo.php' method='post'>
			<input type='hidden' name='tilaustunnus' value='$tilausnumero'>
			<td class='back'><input type='Submit' value='".t("Tilausmemo")."' Style='font-size: 8pt; padding:0;'></td>
			</form>";
			*/

		}

		echo "	<td class='back'>
					<form action='tuote_selaus_haku.php' method='post'>
						<input type='hidden' name='toim_kutsu' value='$toim'>
						<input type='submit' value='".t("Selaa tuotteita")."' Style='font-size: 8pt; padding:0;'>
					</form>
				</td>";

		// aivan karseeta, mutta joskus pitää olla näin asiakasystävällinen... toivottavasti ei häiritse ketään
		if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "artr") {
			echo 	"<td class='back'>
						<form action='../yhteensopivuus.php' method='post'>
						<input type='hidden' name='toim_kutsu' value='$toim'>
						<input type='submit' value='".t("Malliselain")."' Style='font-size: 8pt; padding:0;'>
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
						<input type='Submit' value='".t("Lisää vahinkotiedot")."' Style='font-size: 8pt; padding:0;'>
					</form>
					</td>";
		}

		/*if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='SMS'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<td class='back'><input type='Submit' value='".t("Lähetä viesti")."'></td>
					</form>";
		}*/

		echo "<td class='back'>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='mikrotila'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='projektilla' value='$projektilla'>";
		if ($toim != "VALMISTAVARASTOON") {
			echo "<input type='Submit' value='".t("Lue tilausrivit tiedostosta")."' Style='font-size: 8pt; padding:0;'>";
		}
		else {
			echo "<input type='Submit' value='".t("Lue valmistusrivit tiedostosta")."' Style='font-size: 8pt; padding:0;'>";
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
						<input type='Submit' value='".t("Rahtikirjan esisyöttö")."' Style='font-size: 8pt; padding:0;'>
					</form>
				</td>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and file_exists("osamaksusoppari.inc")) {
			echo "<td class='back'>
					<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='osamaksusoppari'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Rahoituslaskelma")."' Style='font-size: 8pt; padding:0;'>
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
						<input type='Submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."' Style='font-size: 8pt; padding:0;'>
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
				echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$laskurow[ytunnus]&asiakasid=$laskurow[liitostunnus]'>$laskurow[ytunnus] $laskurow[nimi]</a><br>$laskurow[toim_nimi]</td>";
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
			echo "</select></td>";
		}

		echo "</tr>";
		echo "<tr>$jarjlisa";
		echo "<th align='left'>".t("Tilausnumero").":</th>";

		if ($laskurow["tunnusnippu"] > 0 and ($projektilla > 0 or $toim == "TARJOUS")) {

			echo "<td><select Style=\"width: 230px; font-size: 8pt; padding: 0\" name='valitsetoimitus' onchange='submit();'>";

			// Listataan kaikki toimitukset ja liitetään tarjous mukaan jos se tiedetään
			$hakulisa = "";
			if($lasklisatied_row["tunnusnippu_tarjous"]>0) {
				$hakulisa =" or (lasku.tunnusnippu = '$lasklisatied_row[tunnusnippu_tarjous]' and tila='T' and alatila='B')";
			}
			elseif($projektilla>0 and $laskurow["tunnusnippu"]!=$projektilla) {
				$hakulisa =" or lasku.tunnusnippu = '$projektilla'";
			}

			$vquery="select count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";
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

					echo "<option value ='$row[tunnus]' $sel>".t("$laskutyyppi")." ".t("$alatila")." $row[tilaus] - $row[varasto]</option>";
				}
			}
			echo "<optgroup label='".t("Perusta uusi")."'>";
			if($toim == "TARJOUS" and $laskurow["alatila"] != "B") {
				echo "<option value='TARJOUS'>".T("Tarjouksen versio")."</option>";
			}
			else {
				echo "<option value='PIKATILAUS'>".T("Toimitus")."</option>";
				echo "<option value='TYOMAARAYS'>".T("Työmääräys")."</option>";
				echo "<option value='REKLAMAATIO'>".T("Reklamaatio")."</option>";
				echo "<option value='VALMISTAVARASTOON'>".T("Valmistus")."</option>";
				echo "<option value='SIIRTOLISTA'>".T("Siirtolista")."</option>";
			}

			echo "</optgroup></select>";
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
	}

	echo "<br>";

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
		$query  = "	SELECT *
					FROM tilausrivin_lisatiedot
					WHERE yhtio			 = '$kukarow[yhtio]'
					and tilausrivitunnus = '$rivitunnus'";
		$lisatied_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($lisatied_res) > 0) {
			$lisatied_row = mysql_fetch_array($lisatied_res);

			$query = "	UPDATE tilausrivin_lisatiedot
						SET osto_vai_hyvitys = '$osto_vai_hyvitys'
						WHERE yhtio	= '$kukarow[yhtio]'
						and tilausrivitunnus = '$rivitunnus'
						and tunnus 	= '$lisatied_row[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "	INSERT INTO tilausrivin_lisatiedot
						SET yhtio = '$kukarow[yhtio]',
						tilausrivitunnus = '$rivitunnus',
						osto_vai_hyvitys = '$osto_vai_hyvitys',
						lisatty	= now(),
						lisannyt = '$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tila 		= "";
		$rivitunnus = "";
		$positio 	= "";
		$lisaalisa 	= "";

	}



	//Muokataan tilausrivin litätietoa
	if ($kukarow["extranet"] == "" and $tila == "LISATIETOJA_RIVILLE") {

		//	Mitä laitellaan??
		if($asiakkaan_positio != "") {
			$lisaalisa = " asiakkaan_positio = '$asiakkaan_positio'";
		}
		else {
			$lisaalisa = " positio = '$positio'";
		}

		$query = "	SELECT tilausrivi.tunnus, tuote.vaaditaan_kpl2, if(tilausrivin_lisatiedot.pituus>0, tilausrivi.hinta/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.hinta) yksikkohinta
					FROM tilausrivi
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					and (tilausrivi.tunnus = '$rivitunnus' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$rivitunnus' and tilausrivin_lisatiedot.ei_nayteta = 'P') or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$rivitunnus' and tilausrivin_lisatiedot.ei_nayteta = 'P'))";
		$lapsires = mysql_query($query) or pupe_error($query);

		while($lapsi = mysql_fetch_array($lapsires)) {
			$query  = "	SELECT *
						FROM tilausrivin_lisatiedot
						WHERE yhtio			 = '$kukarow[yhtio]'
						and tilausrivitunnus = '$lapsi[tunnus]'";
			$lisatied_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($lisatied_res) > 0) {
				$lisatied_row = mysql_fetch_array($lisatied_res);

				$query = "	UPDATE tilausrivin_lisatiedot
							SET $lisaalisa
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tilausrivitunnus = '$lapsi[tunnus]'
							and tunnus = '$lisatied_row[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	INSERT INTO tilausrivin_lisatiedot
							SET yhtio	= '$kukarow[yhtio]',
							tilausrivitunnus = '$lapsi[tunnus]',
							$lisaalisa,
							lisatty		= now(),
							lisannyt 	= '$kukarow[kuka]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			//	Korjataan tuotteen yksikköhinta.. tuo em homma on se tapa jolla se kuuluisi tehdä, mutta nyt käytönnössä se on aika vaikea toteuttaa..
			if($lapsi["vaaditaan_kpl2"] == "P") {

				$query  = "	SELECT if(pituus=0,'1000',pituus) pituus
							FROM asiakkaan_positio
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tunnus = '$asiakkaan_positio'";
				$posres = mysql_query($query) or pupe_error($query);
				$posrow=mysql_fetch_array($posres);

				$uhinta = round(($posrow["pituus"] * $lapsi["yksikkohinta"])/1000, 2);

				$query = "	UPDATE tilausrivi SET hinta = '$uhinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '{$lapsi["tunnus"]}'";
				$updre = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tilausrivin_lisatiedot SET pituus = '{$posrow["pituus"]}' WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus = '{$lapsi["tunnus"]}'";
				$updre = mysql_query($query) or pupe_error($query);

			}
		}

		/*
		//	Jos vaihdettiin asiakkaan positio korjataan niiden tuotteiden määrä jotka saavat määrätiedot asiakkaan_positiolta
		if($asiakkaan_positio!='') {
			$query = "	SELECT tilausrivi.tunnus, if(tilausrivi.varattu>0,'varattu','jt') kentta, asiakkaan_positio.pituus kpl
						FROM tilausrivi
						JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = '$rivitunnus'
						JOIN asiakkaan_positio ON asiakkaan_positio.yhtio=tilausrivin_lisatiedot.yhtio and asiakkaan_positio.tunnus=tilausrivin_lisatiedot.asiakkaan_positio
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$kukarow[kesken]'
						and tuote.vaaditaan_kpl2 = 'P'
						and (tilausrivi.tunnus = '$rivitunnus' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$rivitunnus')
						or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$rivitunnus'))";
			$lapsires = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($lapsires)>0) {
				while($lapsi = mysql_fetch_array($lapsires)) {
					$ukpl = ($lapsi["isakpl"] * $lapsi["kpl"])/1000;
					//	Tämä on nyt proof of consept toteutus. Oikea tapa olisi poistaa rivi ja lisätä se uudestaan.
					$query = "	UPDATE tilausrivi
								SET $lapsi[kentta] = '$ukpl'
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tunnus = '$lapsi[tunnus]'";
					$updres = mysql_query($query) or pupe_error($query);

					//	Talletetaan tämä oikea pituus lisätietoihin
					$query = "	UPDATE tilausrivin_lisatiedot
								SET pituus = '$lapsi[kpl]'
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tilausrivitunnus = '$lapsi[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);

				}
			}
		}
		*/

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

		$query = "	update tilausrivi set
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

		$query	= "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (PRIMARY)
					LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
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
				if ($tilausrivi["varattu"] < 0) {
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

				// pitäisikö tässä poistaa myös rivit ostokeiseissä niinkuin POISTA ja HYLKAATARJOUS keisseissä
				$query = "	UPDATE sarjanumeroseuranta
							SET $tunken = 0
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilausrivi[tuoteno]'
							and $tunken = '$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}

			// Poistetaan myös tuoteperheen lapset
			if ($tapa != "VAIHDA") {
				$query = "	DELETE FROM tilausrivi
							WHERE perheid 	= '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos muokkaamme tilausrivin paikkaa ja se on speciaalikeissi, S,T,V niin laitetaan $paikka-muuttuja kuntoon
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "S" and substr($paikka,0,3) != "@@@") {
				$paikka = "@@@".$tilausrivi["tilaajanrivinro"]."#".$tilausrivi["hyllyalue"]."#".$tilausrivi["hyllynro"]."#".$tilausrivi["hyllyvali"]."#".$tilausrivi["hyllytaso"];
			}

			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka,0,3) != "¡¡¡") {
				$paikka = "¡¡¡".$tilausrivi["tilaajanrivinro"];
			}

			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "U" and substr($paikka,0,3) != "!!!") {
				$paikka = "!!!".$tilausrivi["tilaajanrivinro"];
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
				$query = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
				$alhire = mysql_query($query) or pupe_error($query);

				// ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
				if (mysql_num_rows($alhire) == 1) {
					$query = "select * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and tuoteno='$tilausrivi[tuoteno]' limit 1";
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
				$hinta = sprintf('%.2f',round($tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow['alv']/100),2));
			}
			else {
				$hinta	= $tilausrivi["hinta"];
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
			}

			$tuoteno 	= $tilausrivi['tuoteno'];

			if ($tilausrivi["var"] == "J" or $tilausrivi["var"] == "S" or $tilausrivi["var"] == "T" or $tilausrivi["var"] == "U") {
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
			}
		}
	}

	//Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAARESEPTIIN") {
		if ($teeperhe == "OK") {
			$query = "	UPDATE tilausrivi
						SET perheid2 = '$isatunnus'
						WHERE yhtio  = '$kukarow[yhtio]'
						and tunnus   = '$isatunnus'";
			$presult = mysql_query($query) or pupe_error($query);
			$perheid2 = $isatunnus;
		}
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
					$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!");
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
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!");
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}

					if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
						//ei saa myydä tähän maahan
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!");
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}
				}
			}
			elseif ($kukarow["extranet"] != '') {
				$varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!");
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
			$query = "	update tilausrivi set
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
	}

	//Syöttörivi
	if ($muokkauslukko == "" and ($toim != 'PROJEKTI' or $rivitunnus != 0)) {
		if (file_exists("myyntimenu.inc")) {

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

		if ($toim == "TYOMAARAYS") {
			$order 	= "ORDER BY sorttauskenttatyomaarays DESC, tunnus";
			$tilrivity	= "'L'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "REKLAMAATIO") {
			$order 	= "ORDER BY sorttauskentta DESC, tunnus";
			$tilrivity	= "'L'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "TARJOUS") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'T'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS" or $toim == "MYYNTITILI") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'G'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'L','V','W','M'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		elseif ($toim == "PROJEKTI") {
			$order = "ORDER BY sorttauskentta desc, tunnus";
			$tilrivity	= "'L','G','E','V','W'";

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila IN ('L','G','E','N','R','A') and tunnusnippu>0";
			$result = mysql_query($query) or pupe_error($query);
			$toimrow = mysql_fetch_array($result);

			$tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) and (perheid = tilausrivi.tunnus or perheid = 0) and (perheid2 = tilausrivi.tunnus or perheid2 = 0)";
		}
		elseif ($toim == "YLLAPITO") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'L','0'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}
		else {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'L','E'";
			$tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
		}

		//	Tämä ylikirjoittaa järjestyksen, näinollen tv printin rivit menee loogisessa järjestyksessä
		if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
			$order = "ORDER BY sorttauskentta asc, tunnus";
		}

		// Tilausrivit
		$query  = "	SELECT tilausrivi.*,
					if (tuotetyyppi='K','Työ','Varaosa') tuotetyyppi,
					if(tilausrivi.perheid=0 and tilausrivi.perheid2=0, tilausrivi.tunnus, if(tilausrivi.perheid>0,tilausrivi.perheid,if(tilausrivi.perheid2>0,tilausrivi.perheid2,tilausrivi.tunnus))) as sorttauskentta,
					if(tilausrivi.perheid=0 and tilausrivi.perheid2=0, if (tuotetyyppi='K','Työ','Varaosa'), if(tilausrivi.perheid>0,tilausrivi.perheid,if(tilausrivi.perheid2>0,tilausrivi.perheid2,tilausrivi.tunnus))) as sorttauskenttatyomaarays,
					tuote.myyntihinta,
					tuote.kehahin,
					tuote.sarjanumeroseuranta
					FROM tilausrivi
					LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					$tunnuslisa
					and tilausrivi.tyyppi in ($tilrivity)
					$order";
		$result = mysql_query($query) or pupe_error($query);

		$rivilaskuri = mysql_num_rows($result);

		if ($rivilaskuri != 0) {

			if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
				$rivino = 0;
			}
			else {
				$rivino = $rivilaskuri+1;
			}

			echo "<br><table>";


			if ($toim == "VALMISTAVARASTOON") {
				echo "<tr>$jarjlisa<td class='back'><font class='message'>";
				echo t("Valmistusrivit").":";
				echo "</font></td></tr>";
			}
			else {
				echo "<tr>$jarjlisa<td class='back' colspan='5'><font class='message'>";

				echo t("Tilausrivit").":</font>";

				// jos meillä on yhtiön myyntihinnoissa alvit mukana ja meillä on alvillinen tilaus, annetaan mahdollisuus switchata listaus alvittomaksi
				if ($yhtiorow["alv_kasittely"] == "" and $laskurow["alv"] != 0 and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {

					$sele = array();

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

			if($kukarow['extranet'] == '') {
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
				if ($laskurow["tila"] == "V" and $row["toimitettuaika"] != '0000-00-00 00:00:00') {
					$muokkauslukko_rivi = "LUKOSSA";
				}
				else {
					$muokkauslukko_rivi = "";
				}


				// Rivin tarkistukset
				if ($muokkauslukko == "" and $muokkauslukko_rivi == "") {
					require('tarkistarivi.inc');
				}

				//Hetaaan tilausrivin_lisatiedot
				$query  = "	SELECT *
							FROM tilausrivin_lisatiedot
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tilausrivitunnus = '$row[tunnus]'";
				$lisatied_res = mysql_query($query) or pupe_error($query);
				$lisatied_row = mysql_fetch_array($lisatied_res);

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
					if (array_sum(saldo_myytavissa($trow["tuoteno"]))<0) {
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
					if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA') {
						echo "<tr>$jarjlisa<td class='back' colspan='10'><br></td></tr>";

						if ($row["perheid"] != 0 and $row["tyyppi"] == "W") {
							$class = " class='spec' ";
						}
					}
					elseif ($vanhaid == 'KALA' and $row["perheid"] != 0 and $row["tyyppi"] == "W") {
						$class = " class='spec' ";
					}
				}

				if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
					$rivino++;
				}
				else {
					$rivino--;
				}


				// Tuoteperheiden lapsille ei näytetä rivinumeroa
				if ($row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")))) {

					if (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						$pklisa = " and (perheid = '$row[tunnus]' or perheid2 = '$row[tunnus]')";
					}
					elseif ($row["perheid"] == 0) {
						$pklisa = " and perheid2 = '$row[perheid2]'";
					}
					else {
						$pklisa = " and (perheid = '$row[perheid]' or perheid2 = '$row[perheid]')";
					}

					$query = "	select sum(if(kommentti != '',1,0)), count(*)
								from tilausrivi
								where yhtio = '$kukarow[yhtio]'
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
						echo "<td rowspan='$pknum' width='55' class='back'>&nbsp;</td>";
					}

					echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$rivino</td>";
				}
				elseif($row["perheid"] == 0 and $row["perheid2"] == 0) {
					if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {

						if($row["kommentti"] != "") {
							$buttonlisa = " rowspan='2' ";
						}
						else {
							$buttonlisa = "";
						}

						$buttonit =  "	<td class='back' $buttonlisa valign='top' width='55'>
											<form action='$PHP_SELF#rivi_$rivino' name='siirra_$rivino' method='post'>
												<input type='hidden' name='toim' value='$toim'>
												<input type='hidden' name='lopetus' value='$lopetus'>
												<input type='hidden' name='projektilla' value='$projektilla'>
												<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
												<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
												<input type='hidden' name='menutila' value='$menutila'>
												<input type='hidden' id='rivi_$rivino' name='jarjesta' value='$rivino'>";
						if($rivino > 1) {
							$buttonit .= "			<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveUp'; document.forms['siirra_$rivino'].submit();\"><img src='../pics/lullacons/arrow-single-up-green.png' border='0'></a>
													<a href='#' onClick=\"getElementById('rivi_$rivino').value='first'; document.forms['siirra_$rivino'].submit();\"><img src='../pics/lullacons/arrow-end-up-green.png' border='0'></a>";
						}
						else {
							$buttonit .= "			<img src='../pics/noimage.gif' width='25'>";

						}
						if($rivilaskuri > $rivino) {
							$buttonit .= "			<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveDown'; document.forms['siirra_$rivino'].submit();\"><img src='../pics/lullacons/arrow-single-down-green.png' border='0'></a>
													<a href='#' onClick=\"getElementById('rivi_$rivino').value='last'; document.forms['siirra_$rivino'].submit();\"><img src='../pics/lullacons/arrow-end-down-green.png' border='0'></a>";
						}
						$buttonit .= " 		</form>
										</td>";
					}
					else {
						$buttonit = "";
					}

					if($row["kommentti"] != "") {
						echo "<tr>$buttonit<td valign='top' rowspan='2'>$rivino</td>";
					}
					else {
						echo "<tr>$buttonit<td valign='top'>$rivino</td>";
					}

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

					if($lisatied_row["ei_nayteta"] == "") {
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
								<select name='positio' onchange='submit();' Style='font-size: 8pt; padding:0;' $state>";

						$query = "	SELECT selite, selitetark
									FROM avainsana
									WHERE yhtio = '$kukarow[yhtio]' and laji = 'TRIVITYYPPI'
									ORDER BY jarjestys, selite";
						$tresult = mysql_query($query) or pupe_error($query);

						while($trrow = mysql_fetch_array($tresult)) {
							$sel = "";
							if ($trrow["selite"]==$lisatied_row["positio"]) $sel = 'selected';
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
									<select id='asiakkaan_positio_$rivino' name='asiakkaan_positio' onchange=\"yllapito('asiakkaan_positio&asiakkaan_kohde=$lasklisatied_row[asiakkaan_kohde]', this.id,'asiakkaan_positio_$rivino');\" Style='font-size: 8pt; padding:0;' $state>
									<option value=''>Asiakkaalla ei ole positiota</option>";

							if(mysql_num_rows($posres) > 0) {
								$optlisa="";
								while($posrow = mysql_fetch_array($posres)) {
									$sel = "";
									if($posrow["tunnus"] == $lisatied_row["asiakkaan_positio"]) {
										$sel = "SELECTED";
										$optlisa = "<option value='muokkaa#$lisatied_row[asiakkaan_positio]'>Muokkaa asiakaan positiota</option>";
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
				elseif ($kukarow['extranet'] == '' and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {
						echo "	<td $class align='left' valign='top'>
									<form action='$PHP_SELF' method='post' name='paikat'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
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

						$query = "	select *
									from varastopaikat
									where yhtio='$kukarow[yhtio]'
									and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))
									and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))";
						$varastore = mysql_query($query) or pupe_error($query);
						$varastoro = mysql_fetch_array($varastore);

						if (strtoupper($varastoro['maa']) != strtoupper($yhtiorow['maa'])) {
							echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varastoro['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
						}
						else {
							echo "<td $class align='left' valign='top'>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
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
				elseif($kukarow['extranet'] == '') {
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

				if($kukarow['extranet'] == '') {
					echo "<td $class valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a>";
				}
				else {
					echo "<td $class valign='top'>$row[tuoteno]";
				}

				// Näytetäänkö sarjanumerolinkki
				if (($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T") and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {

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

					$query = "	select count(*) kpl, min(sarjanumero) sarjanumero
								from sarjanumeroseuranta
								where yhtio	 = '$kukarow[yhtio]'
								and tuoteno	 = '$row[tuoteno]'
								and $tunken1 = '$row[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					if ($sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&$tunken2=$row[tunnus]&from=$toim#$sarjarow[sarjanumero]' style='color:00FF00'>".t("S:nro ok")."</font></a>)";
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
					if ($row["var"] == 'J' or $row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
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

						if ($lisatied_row["osto_vai_hyvitys"] == "O") {
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
								<select name='osto_vai_hyvitys' onchange='submit();' Style='font-size: 8pt; padding:0;'>
								<option value=''  $sel1>$kpl_ruudulle ".("Hyvitys")."</option>
								<option value='O' $sel2>$kpl_ruudulle ".("Osto")."</option>
								</select>
								</form></td>";
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

					if ($kukarow["extranet"] != "") {
						require ("alehinta.inc");
						require ("alv.inc");
					}
					else {
						require ("inc/alehinta.inc");
						require ("tilauskasittely/alv.inc");
					}

					if ($alehinta_val != $laskurow["valkoodi"]) {
						$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
					}

					// halutaan alvittomat hinnat
					if ($tilausrivi_alvillisuus == "E") {
						$alvillisuus_jako = 1 + $row["alv"] / 100;
					}
					else {
						$alvillisuus_jako = 1;
					}

					$hinta    = round($hinta / $alvillisuus_jako, 2);
					$summa    = round($summa / $alvillisuus_jako, 2);
					$brutto   = $hinta * ($row["varattu"] + $row["jt"]);
					$kplhinta = $hinta * (1 - $row["ale"] / 100);

					$myyntihinta = round(tuotteen_myyntihinta($laskurow, $trow, $row["tuoteno"]) / $alvillisuus_jako, 2);

					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>$myyntihinta</td>";
					}
					else {
						if ($myyntihinta != $hinta) $myyntihinta = sprintf('%.2f', $myyntihinta, 2)." (".sprintf('%.2f',$hinta).")";
						else $myyntihinta = sprintf('%.2f', $myyntihinta, 2);
						echo "<td $class align='right' valign='top'>$myyntihinta</td>";
						echo "<td $class align='right' valign='top'>".($row["ale"] * 1)."</td>";
						echo "<td $class align='right' valign='top'>".sprintf('%.2f', $kplhinta, 2)."</td>";
					}

					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>".sprintf('%.2f',$brutto)."</td>";
					}
					else {
						echo "<td $class align='right' valign='top'>".sprintf('%.2f',$summa)."</td>";
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
									$kate = sprintf('%.2f',100*($kotisumma_alviton - $ostohinta)/$kotisumma_alviton)."%";
								}
							}
							elseif ($kpl < 0 and $trow["osto_vai_hyvitys"] == "O") {
								//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

								// Kate = 0
								$kate = "0%";
							}
							elseif ($kpl < 0 and $trow["osto_vai_hyvitys"] == "") {
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
							$kate = sprintf('%.2f',100*($kotisumma_alviton - ($row["kehahin"]*($row["varattu"]+$row["jt"])))/$kotisumma_alviton)."%";
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

				if ($muokkauslukko == "" and $muokkauslukko_rivi == "") {

					echo "	<td class='back' valign='top' nowrap>
								<form action='$PHP_SELF' method='post' name='muokkaa'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' name='tuotenimitys' value='$row[nimitys]'>
									<input type='hidden' name='tila' value = 'MUUTA'>
									<input type='hidden' name='tapa' value = 'MUOKKAA'>
									<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Muokkaa")."'>
								</form>
							</td>";

					echo "	<td class='back' valign='top' nowrap>
								<form action='$PHP_SELF' method='post' name='poista'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' name='tila' value = 'MUUTA'>
									<input type='hidden' name='tapa' value = 'POISTA'>
									<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Poista")."'>
								</form>
							</td>";

					if (($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0) or (($toim == 'SIIRTOLISTA' or $toim == "SIIRTOTYOMAARAYS" or $toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" ) and $row["perheid2"] == 0 and $row["perheid"] == 0)) {

						if ($row["perheid2"] == 0 and $row["perheid"] == 0) {
							$nappulanteksti = t("Lisää tuote");

							$plisax = "	<input type='hidden' name='teeperhe'  value = 'OK'>
										<input type='hidden' name='isatunnus' value = '$row[tunnus]'>";
						}
						elseif($laskurow["tila"] == "V") {
							$nappulanteksti = t("Lisää reseptiin");
							$plisax = "";
						}
						else {
							$nappulanteksti = t("Lisää tuote");
							$plisax = "";
						}

						echo "	<td class='back' valign='top' nowrap>
									<form action='$PHP_SELF' method='post' name='lisaareseptiin'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
										<input type='hidden' name='projektilla' value='$projektilla'>
										<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
										<input type='hidden' name='tila' value = 'LISAARESEPTIIN'>
										$plisax
										<input type='hidden' name='perheid' value = '$row[perheid]'>
										<input type='hidden' name='perheid2' value = '$row[perheid2]'>
										<input type='Submit' Style='font-size: 8pt; padding:0;' value='$nappulanteksti'>
									</form>
								</td>";
					}

					if ($row["var"] == "J" and ($laskurow["alatila"] == "T" or $laskurow["alatila"] == "U")) {
						list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"]);

						if($jtapu_myytavissa >= $kpl_ruudulle) {
							echo "	<td class='back' valign='top' nowrap>
										<form action='$PHP_SELF' method='post' name='toimita'>
											<input type='hidden' name='toim' value='$toim'>
											<input type='hidden' name='lopetus' value='$lopetus'>
											<input type='hidden' name='projektilla' value='$projektilla'>
											<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
											<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
											<input type='hidden' name='menutila' value='$menutila'>
											<input type='hidden' name='tila' value = 'MUUTA'>
											<input type='hidden' name='tapa' value = 'POISJTSTA'>
											<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Toimita")."'>
										</form>
									</td>";
						}
					}

					if ($row["var"] == "P" and $saako_jalkitoimittaa == 0) {
						echo "	<td class='back' valign='top' nowrap>
									<form action='$PHP_SELF' method='post' name='jalkitoimita'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
										<input type='hidden' name='projektilla' value='$projektilla'>
										<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
										<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
										<input type='hidden' name='menutila' value='$menutila'>
										<input type='hidden' name='tila' value = 'MUUTA'>
										<input type='hidden' name='tapa' value = 'JT'>
										<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Jälkitoim")."'>
									</form>
								</td>";
					}

					if ($row["jt"] != 0 and $yhtiorow["puute_jt_oletus"] == "J") {
						echo "	<td class='back' valign='top' nowrap>
									<form action='$PHP_SELF' method='post' name='puutetoimita'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
										<input type='hidden' name='projektilla' value='$projektilla'>
										<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
										<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
										<input type='hidden' name='menutila' value='$menutila'>
										<input type='hidden' name='tila' value = 'MUUTA'>
										<input type='hidden' name='tapa' value = 'PUUTE'>
										<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Puute")."'>
									</form>
								</td>";
					}

					if ($saako_hyvaksya > 0) {
						echo "	<td class='back' valign='top' nowrap>
									<form action='$PHP_SELF' method='post' name='hyvaksy'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='lopetus' value='$lopetus'>
										<input type='hidden' name='projektilla' value='$projektilla'>
										<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
										<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
										<input type='hidden' name='menutila' value='$menutila'>
										<input type='hidden' name='tila' value = 'OOKOOAA'>
										<input type='Submit' Style='font-size: 8pt; padding:0;' value='".t("Hyväksy")."'>
									</form>
								</td>";
					}
				}

				if ($varaosavirhe != '') {
					echo "<td class='back' valign='top'><font class='error'>$varaosavirhe</font></td>";
				}
				if ($varaosakommentti != '') {
					echo "<td class='back' valign='top'><font class='info'>$varaosakommentti</font></td>";
				}

				$varaosavirhe = "";
				$varaosakommentti = "";

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
								<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
								<input type='hidden' name='toim' 			value='$toim'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='lisavarusteita' 	value='ON'>
								<input type='hidden' name='perheid' 		value='$row[perheid]'>
								<input type='hidden' name='perheid2' 		value='$row[tunnus]'>";

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
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='¡¡¡$row[tilaajanrivinro]'>";
							}
							if ($row["var"] == "U") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='!!!$row[tilaajanrivinro]'>";
							}

							echo "<td valign='top'>&nbsp;</td>";
							echo "<td valign='top'>$prow[tuoteno]</td>";
							echo "<td valign='top'><input type='text' name='kpl_array[$prow[tuoteno]]'   size='2' maxlength='8'		Style='font-size: 8pt; padding:0;'></td>";

							if ($toim != "SIIRTOTYOMAARAYS") {
								echo "<td valign='top'><input type='text' name='var_array[$prow[tuoteno]]'   size='2' maxlength='1' 	Style='font-size: 8pt; padding:0;'></td>
								  	<td valign='top'><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12' 	Style='font-size: 8pt; padding:0;'></td>
								  	<td valign='top'><input type='text' name='ale_array[$prow[tuoteno]]'   size='5' maxlength='6' 	Style='font-size: 8pt; padding:0;'></td>
								  	<td valign='top'><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1' 	Style='font-size: 8pt; padding:0;'></td>";
							}

							$lislask++;

							if ($lislask == mysql_num_rows($lisaresult)) {
								echo "<td class='back' valign='top'><input type='submit' Style='font-size: 8pt; padding:0;' value='".t("Lisää")."'></td>";
								echo "</form>";
							}

							echo "</tr>";
						}
					}
					elseif($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0) {
						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off' name='lisaalisav'>
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
								<td class='back' valign='top' nowrap><input type='submit' Style='font-size: 8pt; padding:0;' value='".t("Lisää lisävarusteita")."'></td>
								</form>";
						echo "</tr>";
					}
					else {
						echo "</tr>";
					}
				}
				else {
					echo "</tr>";
				}

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
									tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) kotirivihinta,
									tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) kotirivihinta_ei_erikoisaletta";
					}

					$aquery = "	SELECT
								tuote.sarjanumeroseuranta,
								tuote.ei_saldoa,
								tuote.tuoteno,
								tuote.kehahin,
								tilausrivi.tunnus,
								tilausrivi.varattu+tilausrivi.jt varattu,
								$lisat
								FROM tilausrivi
								JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								$tunnuslisa
								and tilausrivi.tunnus in ($alvrow[rivit])";
					$aresult = mysql_query($aquery) or pupe_error($aquery);

					while($arow = mysql_fetch_array($aresult)) {
						$rivikate 		= 0;	// Rivin kate yhtiön valuutassa
						$rivikate_eieri	= 0;	// Rivin kate yhtiön valuutassa ilman erikoisalennusta

						if ($arow["sarjanumeroseuranta"] == "S") {
							//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
							$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $arow["tunnus"]);

							$rivikate 		= $arow["kotirivihinta"] - $ostohinta;
							$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - $ostohinta;

						}
						else {
							$rivikate 		= $arow["kotirivihinta"]  - ($arow["kehahin"]*$arow["varattu"]);
							$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"]  - ($arow["kehahin"]*$arow["varattu"]);
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

				if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
					$ycspan++;
				}
				if ($kukarow['hinnat'] == 1) {
					$ycspan = $ycspan - 2;
				}
				if($kukarow["resoluutio"] == "I") {
					$ycspan++;
				}
				if($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") {
					$ycspan++;
				}

				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
					echo "<tr>
							<td class='back' colspan='$ycspan'>&nbsp;</td>
							<th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_kotimaa_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa_eieri != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_kotimaa_eieri/$kotiarvo_kotimaa_eieri)."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					echo "<tr>
						<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
						<th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa_eieri != 0  and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa_eieri/$kotiarvo_ulkomaa_eieri)."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}
				else {
					echo "<tr>
							<td class='back' colspan='$ycspan'>&nbsp;</td>
							<th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_eieri)."</td>";

					if ($kukarow['extranet'] == '' and $kotiarvo_eieri != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_eieri/$kotiarvo_eieri)."%</td>";
					}
					elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}

				if ($laskurow["erikoisale"] > 0 and $kukarow['hinnat'] == 0) {
					echo "<tr>
						<td class='back' colspan='$ycspan'>&nbsp;</td>
						<th colspan='5' align='right'>".t("Erikoisalennus")." $laskurow[erikoisale]%:</th>
						<td class='spec' align='right'>".sprintf("%.2f", ($arvo_eieri-$arvo)*-1)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
						echo "<tr>
								<td class='back' colspan='$ycspan'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
								<td class='spec' align='right' nowrap>".sprintf("%.2f",$arvo_kotimaa)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kotimaa/$kotiarvo_kotimaa)."%</td>";
						}
						elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

						echo "<tr>
							<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
							<th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa/$kotiarvo_ulkomaa)."%</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
					}
					else {
						echo "<tr>
								<td class='back' colspan='$ycspan'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
								<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate/$kotiarvo)."%</td>";
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

				echo "<tr>
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

					if($yhtiorow["tilausrivien_jarjestaminen"]!="" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "REKLAMAATIO","PROJEKTI"))) {
						$xcolspan= $ycspan-5;
					}
					else {
						$xcolspan= $ycspan-4;
					}

					if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {
						echo "	<th colspan='2' nowrap>".t("Näytä lomake").":</th>
								<td colspan='2' nowrap>
								<form name='valmis' action='tulostakopio.php' method='post' name='tulostakopio'>
									<input type='hidden' name='otunnus' value='$tilausnumero'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='lopetus' value='$PHP_SELF////toim=$toim//tilausnumero=$tilausnumero//from=LASKUTATILAUS//lopetus=$lopetus//tee='>";

						echo "<select name='toim' Style='font-size: 8pt; padding:0;'>";

						if (file_exists("tulosta_tarjous.inc") and $toim == "TARJOUS") {
							echo "<option value='TARJOUS'>Tarjous</option>";
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
						if (file_exists("tulosta_vakuutushakemus.inc")) {
							echo "<option value='TYOMAARAYS'>Työmäärys</option>";
						}
						if (file_exists("tulosta_rekisteriilmoitus.inc")) {
							echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</option>";
						}
						if ($toim == "PROJEKTI") {
							echo "<option value='TILAUSVAHVISTUS'>Tilausvahvistus</option>";
						}

						echo "		</select>
								<input type='submit' name='NAYTATILAUS' value='".t("Näytä")."' Style='font-size: 8pt; padding:0;'>
								<input type='submit' name='TULOSTA' value='".t("Tulosta")."' Style='font-size: 8pt; padding:0;'>
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

					echo "<td class='back' colspan='2'><input type='submit' value='".t("Jyvitä")."' Style='font-size: 8pt; padding:0;'></form></td>
							</tr>";
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
				if(mysql_num_rows($projektitarkres)==1 and $laskurow["tunnusnippu"]>0) {
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
		if (($muokkauslukko == "" or $toim=="PROJEKTI") and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

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
						echo "<select name='kertakassa'><option value=''>".t("Ei kassaan")."</option>";

						$query  = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='KASSA' order by selite";
						$vares = mysql_query($query) or pupe_error($query);

						while ($varow = mysql_fetch_array($vares)) {
							$sel='';
							if ($varow['selite']==$kukarow["kassamyyja"]) $sel = 'selected';
							echo "<option value='$varow[selite]' $sel>$varow[selitetark]</option>";
						}

						echo "</select>";
					}

					echo "<select name='valittu_kopio_tulostin'>";
					echo "<option value=''>".t("Valitse kuittikopion tulostuspaikka")."</option>";

					$querykieli = "	select *
									from kirjoittimet
									where yhtio = '$kukarow[yhtio]'
									ORDER BY kirjoitin";
					$kires = mysql_query($querykieli) or pupe_error($querykieli);

					while ($kirow=mysql_fetch_array($kires)) {
						echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
					}

					echo "</select><br><br></td></tr><tr>$jarjlisa<td class='back'>";
				}

				if($yhtiorow["tee_osto_myyntitilaukselta"] == "Z" and in_array($toim, array("PROJEKTI","RIVISYOTTO", "PIKATILAUS"))) {
					echo "Tee riveistä ostotilaus:<input type='checkbox' name='tee_osto'><br>";
				}

				echo "<input type='submit' ACCESSKEY='V' value='$otsikko ".t("valmis")."'>";
				echo "</form>";
			}

			echo "</td>";
		}
		elseif($sarjapuuttuu > 0) {
			echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu sarjanumeroita!")."</font>";
		}


		//	Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
		if ($laskurow["tunnusnippu"] > 0 and $toim=="PROJEKTI") {
			$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
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
