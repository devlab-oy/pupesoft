<?php

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
		$tee = "";
	}
}

// Extranet keississä asiakasnumero tulee käyttäjän takaa
// Haetaan asiakkaan tunnuksella
if ($kukarow["extranet"] != '') {
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
elseif ($toim == "VALMISTAVARASTOON") {
	$otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
	$otsikko = t("Varastosiirto");
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
else {
	$otsikko = t("Myyntitilaus");
}

//korjataan hintaa ja aleprossaa
$hinta	= str_replace(',','.',$hinta);
$ale 	= str_replace(',','.',$ale);
$kpl 	= str_replace(',','.',$kpl);

// jos ei olla postattu mitään, niin halutaan varmaan tehdä kokonaan uusi tilaus..
if ($kukarow["extranet"] == "" and count($_POST) == 0 and $from != "LASKUTATILAUS") {
	$tila				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow["kesken"]	= '';

	//varmistellaan ettei vanhat kummittele...
	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

// asiakasnumero on annettu, etsitään tietokannasta...
if ($tee == "" and ($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0) or ($kukarow["extranet"] == "" and $toim == "PIKATILAUS" and ($syotetty_ytunnus != '' or $asiakasid != ''))) {

	if (substr($ytunnus,0,1) == "£") {
		$ytunnus = $asiakasid;
	}
	else {
		$ytunnus = $syotetty_ytunnus;
	}

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

	if ($asiakasid != '') {
		$nimi 			= $asiakasrow["nimi"];
		$nimitark 		= $asiakasrow["nimitark"];
		$osoite 		= $asiakasrow["osoite"];
		$postino 		= $asiakasrow["postino"];
		$postitp 		= $asiakasrow["postitp"];
		$maa 			= $asiakasrow["maa"];
		$tnimi 			= $asiakasrow["toim_nimi"];
		$tnimitark 		= $asiakasrow["toim_nimitark"];
		$tosoite 		= $asiakasrow["toim_osoite"];
		$tpostino 		= $asiakasrow["toim_postino"];
		$tpostitp 		= $asiakasrow["toim_postitp"];
		$toim_maa 		= $asiakasrow["toim_maa"];
		$verkkotunnus 	= $asiakasrow["verkkotunnus"];
		$poistumistoimipaikka_koodi       = $asiakasrow["poistumistoimipaikka_koodi"];
		$kuljetusmuoto                    = $asiakasrow["kuljetusmuoto"];
		$kauppatapahtuman_luonne          = $asiakasrow["kauppatapahtuman_luonne"];
		$aktiivinen_kuljetus_kansallisuus = $asiakasrow["aktiivinen_kuljetus_kansallisuus"];
		$aktiivinen_kuljetus              = $asiakasrow["aktiivinen_kuljetus"];
		$kontti                           = $asiakasrow["kontti"];
		$sisamaan_kuljetusmuoto           = $asiakasrow["sisamaan_kuljetusmuoto"];
		$sisamaan_kuljetus_kansallisuus   = $asiakasrow["sisamaan_kuljetus_kansallisuus"];
		$sisamaan_kuljetus                = $asiakasrow["sisamaan_kuljetus"];
		$maa_maara                        = $asiakasrow["maa_maara"];

		if($asiakasrow["spec_ytunnus"] != '') {
			$ytunnus 				= $asiakasrow["spec_ytunnus"];
			$asiakasrow["ytunnus"] 	= $asiakasrow["spec_ytunnus"];
		}

		if($asiakasrow["spec_tunnus"] != '') {
			$asiakasid 				= $asiakasrow["spec_tunnus"];
			$asiakasrow["tunnus"] 	= $asiakasrow["spec_tunnus"];
		}

		$toimvv = date("Y");
		$toimkk = date("m");
		$toimpp = date("d");

		$kervv = date("Y");
		$kerkk = date("m");
		$kerpp = date("d");

		$maksuehto 		= $asiakasrow["maksuehto"];
		$toimitustapa 	= $asiakasrow["toimitustapa"];

		// haetaan tomitustavan oletusmaksajan tiedot
		$apuqu = "	select *
					from toimitustapa use index (selite_index)
					where yhtio='$kukarow[yhtio]' and selite='$asiakasrow[toimitustapa]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$apuro = mysql_fetch_array($meapu);
		$maksaja = $apuro['merahti'];

		if ($kukarow["myyja"] == 0) {
			$myyja = $kukarow["tunnus"];
		}
		else {
			$myyja = $kukarow["myyja"];
		}

		$alv 			= $asiakasrow["alv"];
		$ovttunnus 		= $asiakasrow["ovttunnus"];
		$toim_ovttunnus = $asiakasrow["toim_ovttunnus"];
		$chn 			= $asiakasrow["chn"];
		$maksuteksti 	= $asiakasrow[""];
		$tilausvahvistus= $asiakasrow["tilausvahvistus"];
		$laskutusvkopv 	= $asiakasrow["laskutusvkopv"];
		$vienti 		= $asiakasrow["vienti"];
		$ketjutus 		= $asiakasrow["ketjutus"];
		$valkoodi 		= $asiakasrow["valkoodi"];


		//annetaan extranet-tilaukselle aina paras prioriteetti, tämä on hyvä porkkana.
		if ($kukarow["extranet"] != '') {
			$query  = "	SELECT distinct selite
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji = 'asiakasluokka' and selite != ''
						ORDER BY 1
						LIMIT 1";
			$prioresult = mysql_query($query) or pupe_error($query);
			$priorow = mysql_fetch_array($prioresult);

			$luokka 	= $priorow["selite"];
		}
		else {
			$luokka		= $asiakasrow["luokka"];
		}

		$erikoisale		= $asiakasrow["erikoisale"];

		$varasto = (int) $kukarow["varasto"];

	}
	else {
		//yhtiön oletusalvi!
		$xwquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$xwtres  = mysql_query($xwquery) or pupe_error($xwquery);
		$xwtrow  = mysql_fetch_array($xwtres);

		$alv = (float) $xwtrow["selite"];

		$ytunnus = "WEKAROTO";
		$varasto = (int) $kukarow["varasto"];
	}

	if ($valkoodi == '') {
		$valkoodi = $yhtiorow["valkoodi"]."##";
	}
	else {
		$query = "	SELECT nimi, kurssi
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					and nimi= '$valkoodi'";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1) {
			$vrow = mysql_fetch_array($vresult);

			$valkoodi = $vrow["nimi"]."##".$vrow["kurssi"];
		}
		else {
			$valkoodi = $yhtiorow["valkoodi"]."##";
		}
	}

	$jatka	= "JATKA";
	$tee	= "OTSIK";
	$override_ytunnus_check = "YES";

	require ("otsik.inc");
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] != 0) {

	if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
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
}

//tietyissä keisseissä tilaus lukitaan (ei syöttöriviä eikä muota muokkaa/poista-nappuloita)
$muokkauslukko = "";

if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
	$muokkauslukko = "LUKOSSA";
}



// Hyväksytään tajous ja tehdään tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS") {

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

	$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '');
	if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";

	$query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

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
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS") {

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

	while($srow = mysql_fetch_array($sres)) {
		if ($srow["varattu"] > 0) {
			$tunken = "myyntirivitunnus";
		}
		else {
			$tunken = "ostorivitunnus";
		}

		$query = "update sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and $tunken='$srow[tunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
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
if ($kukarow["extranet"] == "" and $tee == "LASKUTAMYYNTITILI") {
	$tilatapa = "LASKUTA";

	require ("laskuta_myyntitilirivi.inc");
}

// Laitetaan myyntitili takaisin lepäämään
if ($kukarow["extranet"] == "" and $tee == "LEPAAMYYNTITILI") {
	$tilatapa = "LEPAA";

	require ("laskuta_myyntitilirivi.inc");
}

if($tee == "MAKSUSOPIMUS") {
	require("maksusopimus.inc");
}

// Poistetaan tilaus
if ($tee == 'POISTA') {

	// poistetaan tilausrivit, mutta jätetään PUUTE rivit analyysejä varten...
	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var<>'P'";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan sarjanumerolinkit
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'";
	$sres = mysql_query($query) or pupe_error($query);

	while($srow = mysql_fetch_array($sres)) {
		if ($srow["varattu"] > 0) {
			$tunken = "myyntirivitunnus";
		}
		else {
			$tunken = "ostorivitunnus";
		}

		$query = "update sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and $tunken='$srow[tunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
	}

	//Poistetaan maksusuunnitelma
	$query = "DELETE from maksupositio WHERE yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE lasku SET tila='D', alatila='L', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

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

//Lisätään tän asiakkaan valitut JT-rivit tälle tilaukselle
if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen") {
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
}

// Tilaus valmis
if ($tee == "VALMIS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
		exit;
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

		//Tehdään asiakasmemotapahtuma
		 $kysely = "	INSERT INTO kalenteri
						SET tapa 		= 'Tarjous asiakkaalle',
						asiakas  	 	= '$laskurow[ytunnus]',
						liitostunnus	= '$laskurow[liitostunnus]',
						henkilo  		= '',
						kuka     		= '$kukarow[kuka]',
						yhtio    		= '$kukarow[yhtio]',
						tyyppi   		= 'Memo',
						pvmalku  		= now(),
						kentta01 		='Tarjous $laskurow[tunnus] tulostettu.\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]'";
 		$result = mysql_query($kysely) or pupe_error($kysely);

		 //Tehdään myyjälle muistutus
		 $kysely = "	INSERT INTO kalenteri
						SET
						asiakas  	 	= '$laskurow[ytunnus]',
						liitostunnus	= '$laskurow[liitostunnus]',
						kuka     		= '$kukarow[kuka]',
						yhtio    		= '$kukarow[yhtio]',
						tyyppi   		= 'Muistutus',
						tapa     		= 'Tarjous asiakkaalle',
						kentta01 		= 'Muista tarjous $laskurow[tunnus]!',
						kuittaus 		= 'K',
						pvmalku  		= date_add(now(), INTERVAL 7 day)";
		 $result = mysql_query($kysely) or pupe_error($kysely);

		$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	// Työmääräys valmis
	elseif ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		require("../tyomaarays/tyomaarays.inc");
	}
	// Siirtolista, myyntitili, valmistus valmis
	elseif ($kukarow["extranet"] == "" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI")) {
		require ("tilaus-valmis-siirtolista.inc");
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
			if($yhtiorow["tee_osto_myyntitilaukselta"] != '') {
				require("tilauksesta_ostotilaus.inc");

				$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'T');
				$tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"],'U');

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

if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS" and ($tee == "VAHINKO" or $tee == "LEPAA")) {
	require("../tyomaarays/tyomaarays.inc");
}

if ($kukarow["extranet"] == "" and $toim == "TARJOUS" and $tee == "SMS") {
	$kala = exec("echo \"Terveisiä $yhtiorow[nimi]\" | /usr/bin/gnokii --sendsms +358505012254 -r");
	echo "$kala<br>";
	$tee = "";
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or ($toim != "PIKATILAUS" and $laskurow["liitostunnus"] == ''))) {

	//Tämä jotta myös rivisyötön alkuhomma toimisi
	$tee = "OTSIK";

	if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA") {
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
}

//lisätään rivejä tiedostosta
if ($tee == 'mikrotila' or $tee == 'file') {

	if ($kukarow["extranet"] == "" and $toim == "SIIRTOLISTA") {
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
	}

	if ($laskurow["liitostunnus"] > 0) { // jos asiakasnumero on annettu
		echo "<table>";
		echo "<tr>";

		if ($kukarow["extranet"] == "") {
			echo "	<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='OTSIK'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tiedot_laskulta' value='YES'>
					<input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
					<td class='back'><input type='submit' ACCESSKEY='m' value='".t("Muuta Otsikkoa")."'></td>
					</form>";
		}

		// otetaan maksuehto selville.. jaksotus muuttaa asioita
		$query = " 	select *
					from maksuehto
					where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==1) {
			$maksuehtorow = mysql_fetch_array($result);

			if ($maksuehtorow['jaksotettu']!='') {
				echo "<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='MAKSUSOPIMUS'>
				<input type='hidden' name='toim' value='$toim'>
				<td class='back'><input type='Submit' value='".t("Maksusuunnitelma")."'></td>
				</form>";
			}
		}

		echo "	<form action='tuote_selaus_haku.php' method='post'>
				<input type='hidden' name='toim_kutsu' value='$toim'>
				<td class='back'><input type='submit' value='".t("Selaa tuotteita")."'></td>
				</form>";

		// aivan karseeta, mutta joskus pitää olla näin asiakasystävällinen... toivottavasti ei häiritse ketään
		if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "artr") {
			echo 	"<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<td class='back'><input type='submit' value='".t("Malliselain")."'></td>
					</form>";
		}

		if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='VAHINKO'>
					<input type='hidden' name='toim' value='$toim'>
					<td class='back'><input type='Submit' value='".t("Lisää vahinkotiedot")."'></td>
					</form>";
		}

		/*if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='SMS'>
					<input type='hidden' name='toim' value='$toim'>
					<td class='back'><input type='Submit' value='".t("Lähetä viesti")."'></td>
					</form>";
		}*/

		echo "<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='tee' value='mikrotila'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='toim' value='$toim'>";
		if ($toim != "VALMISTAVARASTOON") {
			echo "<td class='back'><input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'></td>";
		}
		else {
			echo "<td class='back'><input type='Submit' value='".t("Lue valmistusrivit tiedostosta")."'></td>";
		}

		echo "</form>";


		if ($kukarow["extranet"] == "" and $toim == "TARJOUS" and file_exists("osamaksusoppari.inc")) {
			echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='osamaksusoppari'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<td class='back'><input type='Submit' value='".t("Rahoituslaskelma")."'></td>
				</form>";
		}

		if ($kukarow["extranet"] == "" and $toim == "TARJOUS" and file_exists("vakuutushakemus.inc")) {
			echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='vakuutushakemus'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<td class='back'><input type='Submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'></td>
				</form>";
		}

		echo "</tr></table><br>\n";
	}
	
	//Oletetaan, että tilaus on ok, $tilausok muuttujaa summataan alempana jos jotain virheitä ilmenee
	$tilausok = 0;
	
	if ($laskurow["liitostunnus"] > 0 and ($laskurow["nimi"] == '' or $laskurow["osoite"] == '' or $laskurow["postino"] == '' or $laskurow["postitp"] == '')) {
		echo "<font class='error'>".t("VIRHE: Tilauksen laskutusosoitteen tiedot ovat puutteelliset")."!</font><br><br>";
		$tilausok++;
	}

	// kirjoitellaan otsikko
	echo "<table>";

	// tässä alotellaan koko formi.. tämä pitää kirjottaa aina
	echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='toim' value='$toim'>";

	if ($laskurow["liitostunnus"] > 0) { // jos asiakasnumero on annettu

		echo "<tr>";

		if ($toim == "VALMISTAVARASTOON") {
			echo "<th align='left'>".t("Varastot").":</th>";

			echo "<td>$laskurow[ytunnus] $laskurow[nimi]</td>";

			echo "<th align='left'> </th>";
			echo "<td></td>";
		}
		else {
			echo "<th align='left'>".t("Asiakas").":</th>";


			if ($kukarow["extranet"] == "") {
				echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$laskurow[ytunnus]'>$laskurow[ytunnus] $laskurow[nimi]</a><br>$laskurow[toim_nimi]</td>";
 			}
			else {
				echo "<td>$laskurow[ytunnus] $laskurow[nimi]<br>$laskurow[toim_nimi]</td>";
			}

			echo "<th align='left'>".t("Toimitustapa").":</th>";

			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = " and (extranet = 'K' or selite = '$laskurow[toimitustapa]') ";
			}

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]' $extralisa
						ORDER BY jarjestys,selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='toimitustapa' onchange='submit()'>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row["selite"] == $laskurow["toimitustapa"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selite]";
			}
			echo "</select></td>";
		}

		echo "</td></tr>";
		echo "<tr>";
		echo "<th align='left'>".t("Tilausnumero").":</th>";
		echo "<td>$kukarow[kesken]</td>";


		echo "<th>".t("Tilausviite").":</th><td>";
		echo "<input type='text' size='30' name='viesti' value='$laskurow[viesti]'><input type='submit' value='".t("Tallenna")."'></td></tr>\n";

		echo "<tr>";

		if ($toim != "VALMISTAVARASTOON") {
			echo "<th>".t("Tilausvahvistus").":</th>";
		}
		else {
			echo "<th> </th>";
		}

		if ($toim != "VALMISTAVARASTOON") {
			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = "  and selite not like '%E%' and selite not like '%O%' ";
				if ($kukarow['hinnat'] != 0) {
					$hinnatlisa = " and selite not like '1%' ";
				}
			}

			$query = "	SELECT selite, selitetark
						FROM avainsana use index (yhtio_laji_selite)
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TV' $extralisa $hinnatlisa
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='tilausvahvistus' onchange='submit()'>";
			echo "<option value=' '>".t("Ei Vahvistusta")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[0]== $laskurow["tilausvahvistus"]) $sel = 'selected';
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
		}
		else {
			echo "<td></td>";
		}

		if ($kukarow["extranet"] == "") {
			if ($toim != "VALMISTAVARASTOON") {
				echo "<th align='left'>".t("Myyjänro").":</th>";
			}
			else {
				echo "<th align='left'>".t("Laatija").":</th>";
			}

			echo "<td><input type='text' name='myyjanro' size='8'> tai ";
			echo "<select name='myyja' onchange='submit()'>";

			$query = "	SELECT tunnus, kuka, nimi, myyja
						FROM kuka use index (yhtio_myyja)
						WHERE yhtio = '$kukarow[yhtio]'
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
						WHERE l.tunnus='$kukarow[kesken]' and l.yhtio='$kukarow[yhtio]' and a.yhtio = l.yhtio and a.ytunnus = l.ytunnus";
			$faktaresult = mysql_query($query) or pupe_error($query);
			$faktarow = mysql_fetch_array($faktaresult);

			if ($toim != 'VALMISTAVARASTOON') {
				echo "<tr><th>".t("Asiakasfakta").":</th><td colspan='3'>";

				//jos asiakkaalla on luokka K niin se on myyntikiellossa ja siitä herjataan
				if ($faktarow["luokka"]== 'K') {
					echo "<font class='error'>".t("HUOM!!!!!! Asiakas on myyntikiellossa")."!!!!!<br></font>";
				}

				echo "$faktarow[fakta]&nbsp;</td></tr>\n";
			}
		}
		else {
			echo "</tr>";
		}
	}
	elseif ($kukarow["extranet"] == "") {
		// asiakasnumeroa ei ole vielä annettu, näytetään täyttökentät

		if ($kukarow["oletus_asiakas"] != 0) {
			$yt = $kukarow["oletus_asiakas"];
		}
		if ($kukarow["myyja"] != 0) {
			$my = $kukarow["myyja"];
		}

		echo "<tr>
			<th align='left'>".t("Asiakas")."</th>
			<td><input type='text' size='10' maxlength='10' name='syotetty_ytunnus' value='$yt'></td>
			</tr>";
		echo "<tr>
			<th align='left'>".t("Myyjänro")."</th>
			<td><input type='text' size='10' maxlength='10' name='myyjanro' value='$my'></td>
			</tr>";
	}

	echo "</table>";

	if ($kukarow['extranet'] == '' and $kukarow['kassamyyja'] == '' and $laskurow['liitostunnus'] > 0 and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {
		$sytunnus = $laskurow['ytunnus'];
		$eiliittymaa = 'ON';
		require ("../raportit/saatanat.php");

		if ($ylikolkyt > 0) {
			echo "<font class='error'>".t("HUOM!!!!!! Asiakkaalla on yli 30 päivää sitten erääntyneitä laskuja, olkaa ystävällinen ja ottakaa yhteyttä myyntireskontran hoitajaan")."!!!!!<br></font>";
		}
	}

	echo "<hr><br>";

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

	if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
		//Päivitetään isän perheid jotta voidaan lisätä lisää lisävarusteita
		$query = "	update tilausrivi set
					perheid2	= 0
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

			$tilausrivi  = mysql_fetch_array($result);

			// Poistetaan muokattava tilausrivi
			$query = "	DELETE from tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			// Tehdään pari juttua jos tuote on sarjanuerosaurannassa
			if($tilausrivi["sarjanumeroseuranta"] != '') {
				//Nollataan sarjanumero
				if ($tilausrivi["varattu"]+$tilausrivi["jt"] > 0) {
					$tunken = "myyntirivitunnus";
				}
				else {
					$tunken = "ostorivitunnus";
				}

				$query = "SELECT tunnus FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivi[tuoteno]' and $tunken='$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				//Pidetään sarjatunnus muistissa
				if ($tapa != "POISTA") {
					$myy_sarjatunnus = $sarjarow["tunnus"];
				}

				$query = "update sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivi[tuoteno]' and $tunken='$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);

			}

			// Poistetaan myös tuoteperheen lapset
			if ($tapa != "VAIHDA") {
				$query = "	DELETE from tilausrivi
							WHERE perheid 	= '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos muokkaamme tilausrivin paikkaa ja se on speciaalikeissi, S,T,V niin laitetaan $paikka-muuttuja kuntoon
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "S" and substr($paikka,0,3) != "@@@") {
				$paikka = "@@@".$tilausrivi["tilaajanrivinro"];
			}
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka,0,3) != "###") {
				$paikka = "###".$tilausrivi["tilaajanrivinro"];
			}
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "U" and substr($paikka,0,3) != "!!!") {
				$paikka = "!!!".$tilausrivi["tilaajanrivinro"];
			}


			//haetaan tuotteen alv matikkaa varten
			$query = "	SELECT alv, myyntihinta, nettohinta
						FROM tuote
						WHERE tuoteno = '$tilausrivi[tuoteno]' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			$tuoterow = mysql_fetch_array($tuoteresult);

			if ($tuoterow["alv"] != $tilausrivi["alv"] and $yhtiorow["alv_kasittely"] == "" and $tilausrivi["alv"] < 500) {
				$hinta = sprintf('%.2f',round($tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow["alv"]/100),2));
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

			if ($tilausrivi['hinta'] == '0.00') $hinta = '';

			if ($tapa == "MUOKKAA") {
				$var	= $tilausrivi["var"];

				//Jos lasta muokataan, niin säilytetään sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					echo "<input type='hidden' name='perheid' value = '$tilausrivi[perheid]'>";
				}

				//Jos lisävarustetta muokataan, niin säilytetään sen perheid2
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid2"] and $tilausrivi["perheid2"] != 0) {
					echo "<input type='hidden' name='perheid2' value = '$tilausrivi[perheid2]'>";
				}

				$tila	= "MUUTA";
			}
			elseif ($tapa == "JT") {
				$var 		= "J";

				if ($hyllyalue != '') {
					$paikka		= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "POISJTSTA") {
				$var 		= "";

				if ($hyllyalue != '') {
					$paikka		= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "VAIHDA") {
				$perheid	= $tilausrivi['perheid'];
				$perheid2	= $tilausrivi['perheid2'];
				$tila		= "";
				$var		= $tilausrivi["var"];
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
		echo "<input type='hidden' name='perheid' value = '$perheid'>";
		echo "<input type='hidden' name='perheid2' value = '$perheid2'>";
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

			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}

			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}

			if ($laskurow["varasto"] != 0) {
				$varasto = (int) $laskurow["varasto"];
			}

			//Ennakkotilauksen ja Tarjoukset eivät varaa saldoa
			if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tila"] == "V") {
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
	}

	//Syöttörivi
	if ($muokkauslukko == "") {
		require ("syotarivi.inc");
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
				<tr><th>".t("Nimitys")."</th><td align='right'>$tuote[nimitys]</td></tr>
				<tr><th>".t("Hinta")."</th><td align='right'>$tuote[myyntihinta] $yhtiorow[valkoodi]</td></tr>
				<tr><th>".t("Nettohinta")."</th><td align='right'>$tuote[nettohinta] $yhtiorow[valkoodi]</td></tr>";

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
				if (strtoupper($nimro['maa']) != strtoupper($yhtiorow['maakoodi'])) {
					$varastomaa = "<br>".strtoupper($nimro['maa']);
				}

				echo "<tr><th>".t("Saldo")." $nimro[nimitys] $oletus $varastomaa</th><td align='right'><font class='info'>$salrow[saldo]<br>- $srow[0]<br>---------<br></font>".sprintf("%01.2f",$salrow['saldo'] - $srow[0])."</td></tr>";
			}

			echo "</table>";
		}
	}

	// jos ollaan jo saatu tilausnumero aikaan listataan kaikki tilauksen rivit..
	if ((int) $kukarow["kesken"] != 0) {

		if ($toim == "TYOMAARAYS") {
			 $order 	= "ORDER BY tuotetyyppi, tuote.ei_saldoa DESC, tunnus DESC";
			 $tilrivity	= "'L'";
		}
		elseif ($toim == "TARJOUS") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'T'";
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'G'";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			$order = "ORDER BY tilausrivi.perheid desc, tunnus";
			$tilrivity	= "'V','W'";
		}
		else {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'L','E'";
		}

		// Tilausrivit
		$query  = "	SELECT tilausrivi.*,
					if (tuotetyyppi='K','Työ','Varaosa') tuotetyyppi,
					if(tilausrivi.perheid=0 and tilausrivi.perheid2=0, tilausrivi.tunnus, if(tilausrivi.perheid>0,tilausrivi.perheid,if(tilausrivi.perheid2>0,tilausrivi.perheid2,tilausrivi.tunnus))) as sorttauskentta,
					tuote.myyntihinta,
					tuote.kehahin,
					tuote.sarjanumeroseuranta
					FROM tilausrivi
					LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'
					and tilausrivi.tyyppi in ($tilrivity)
					$order";
		$result = mysql_query($query) or pupe_error($query);
		
		$rivilaskuri = mysql_num_rows($result);

		if ($rivilaskuri != 0) {
			$rivino = $rivilaskuri+1;

			echo "<br><table>";

			if ($toim != "TYOMAARAYS") {
				if ($toim == "VALMISTAVARASTOON") {
					echo "<tr><td class='back' colspan='10'>".t("Valmistusrivit").":</td></tr>";
				}
				else {
					echo "<tr><td class='back' colspan='10'>".t("Tilausrivit").":</td></tr>";
				}

				echo "<tr><th>".t("#")."</th>";

				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					echo "<th>".t("Nimitys")."</th>";
				}

				if($kukarow['extranet'] == '') {
					echo "	<th>".t("Paikka")."</th>";
				}

				echo "	<th>".t("Tuotenumero")."</th>
						<th>".t("Kpl")."</th>
						<th>".t("Var")."</th>
						<th>".t("Hinta")."</th>";

				if ($kukarow['hinnat'] == 0 or $kukarow['extranet'] == '') {
					echo "<th>".t("Ale%")."</th>";
				}

				echo "	<th>".t("Netto")."</th>
						<th>".t("Summa")."</th>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<th>".t("Kate")."</th>";
				}

				echo "	<th>".t("Alv")."</th>
						<td class='back'></td>
						<td class='back'></td>
						</tr>";
			}

			$bruttoyhteensa	= 0;
			$tuotetyyppi	= "";
			$varaosatyyppi	= "";
			$vanhaid 		= "KALA";

			$nettoyhteensa								= 0;
			$yhteensa									= 0;
			$kotimaan_varastot_yhteensa 				= 0;
			$ulkomaan_varastot_yhteensa 				= 0;
			$nettokotimaan_varastot_yhteensa 			= 0;
			$nettoulkomaan_varastot_yhteensa 			= 0;

			$veroton_nettoyhteensa						= 0;
			$veroton_yhteensa							= 0;
			$veroton_kotimaan_varastot_yhteensa 		= 0;
			$veroton_ulkomaan_varastot_yhteensa 		= 0;
			$veroton_nettokotimaan_varastot_yhteensa 	= 0;
			$veroton_nettoulkomaan_varastot_yhteensa 	= 0;

			$kate_nettoyhteensa							= 0;
			$kate_yhteensa								= 0;
			$kate_kotimaan_varastot_yhteensa 			= 0;
			$kate_ulkomaan_varastot_yhteensa 			= 0;
			$kate_nettokotimaan_varastot_yhteensa 		= 0;
			$kate_nettoulkomaan_varastot_yhteensa 		= 0;

			$toimitetaan_ulkomaailta 					= 0;

			while ($row = mysql_fetch_array($result)) {

				if ($toim == "TYOMAARAYS") {
					if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Työ') {
						$tuotetyyppi = 1;

						echo "<tr><td class='back' colspan='10'>".t("Työt").":</td></tr>";
						echo "<tr><th>".t("#")."</th>";

						if ($kukarow["resoluutio"] == 'I') {
							echo "<th>".t("Nimitys")."</th>";
						}

						echo "	<th>".t("Paikka")."</th>
								<th>".t("Tuotenumero")."</th>
								<th>".t("Kpl")."</th>
								<th>".t("Var")."</th>
								<th>".t("Hinta")."</th>
								<th>".t("Ale%")."</th>
								<th>".t("Netto")."</th>
								<th>".t("Summa")."</th>
								<th>".t("Alv")."</th>
								<td class='back'></td>
								<td class='back'></td>
								</tr>";
					}

					if ($varaosatyyppi == "" and $row["tuotetyyppi"] == 'Varaosa') {
						$varaosatyyppi = 1;

						if ($tuotetyyppi == 1) {
							echo "<tr><td class='back' colspan='10'><br></td></tr>";
						}

						echo "<tr><td class='back' colspan='10'>".t("Varaosat ja Tarvikkeet").":</td></tr>";
						echo "<tr><th>".t("#")."</th>";

						if ($kukarow["resoluutio"] == 'I') {
							echo "<th>".t("Nimitys")."</th>";
						}

						echo "	<th>".t("Paikka")."</th>
								<th>".t("Tuotenumero")."</th>
								<th>".t("Kpl")."</th>
								<th>".t("Var")."</th>
								<th>".t("Hinta")."</th>
								<th>".t("Ale%")."</th>
								<th>".t("Netto")."</th>
								<th>".t("Summa")."</th>
								<th>".t("Alv")."</th>
								<td class='back'></td>
								<td class='back'></td>
								</tr>";
					}
				}

				$rivino--;

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
					$row['varattu'] = $row['kpl'];
				}

				//Käännetään tän rivin hinta oikeeseen valuuttaan
				$row["hinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"]);

				// Tän rivin rivihinta
				$summa	= $row["hinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);

				// Tän rivin alviton rivihinta
				if ($yhtiorow["alv_kasittely"] == '') {
					$summa_alviton = $summa / (1+$row["alv"]/100);
				}
				else {
					$summa_alviton = $summa;
				}

				// Tän rivin kate
				$kate = 0;

				if ($row["ei_saldoa"] == "" and $kukarow['extranet'] == '' and $row["sarjanumeroseuranta"] != "" and $row["var"] != 'T') {

					$query = "select ostorivitunnus from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and myyntirivitunnus='$row[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					if ($sarjarow["ostorivitunnus"] != 0 and $sarjarow["ostorivitunnus"] != $row["tunnus"] and $row["varattu"]+$row["jt"] > 0) {

						$limitti = $row["varattu"]+$row["jt"];

						$query = "	select sum(rivihinta) rivihinta
									from tilausrivi
									where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and tunnus='$sarjarow[ostorivitunnus]'
									LIMIT $limitti";
						$sarjares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($sarjares) > 0) {
							$sarjarow = mysql_fetch_array($sarjares);

							$kate = $summa_alviton - $sarjarow["rivihinta"];
						}
					}
				}
				elseif ($row["ei_saldoa"] == "" and $kukarow['extranet'] == '') {
					$kate = $summa_alviton - ($row["kehahin"]*($row["varattu"]+$row["jt"]));
				}

				// Jos halutaan tulostaa tietyille extranettaajille bruttomyyntihintoja ruudulle
				$brutto = 0;
				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					$hinta = $row["myyntihinta"];

					require('alv.inc');

					$brutto = $hinta*($row["varattu"]+$row["jt"]);
				}

				if ($row["var"] == "P" or $row["var"] == "V") {
					$class = " class='spec' ";
				}
				elseif ($row["var"] == "J") {
					$class = " class='green' ";
				}
				else {
					//Jos rivi ei ole puute eikä jt niin tutkitaan sen hintaa ja maata josta se myydään
					//Suoratoimitusriveille joudumme tutkimaan hieman toimittajan kautta mistä maasta ne myydään
					if ($row["tilaajanrivinro"] > 0 and $row["var"] == "S") {
						$query = "	SELECT tyyppi_tieto, maa, maakoodi
									FROM toimi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$row[tilaajanrivinro]'";
						$sres1 = mysql_query($query) or pupe_error($query);
						$srow1 = mysql_fetch_array($sres1);
					}
					else {
						$srow1 = array();

						$srow1["tyyppi_tieto"] 	= $yhtiorow["yhtio"];
						$srow1["maa"] 			= $yhtiorow["maakoodi"];
					}

					// Luetaan varaston tiedoista missä maassa se sijaitsee
					$query = "	SELECT maa
								FROM varastopaikat
								WHERE yhtio = '$srow1[tyyppi_tieto]'
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))";
					$sres2  = mysql_query($query) or pupe_error($query);
					$srow2 = mysql_fetch_array($sres2);

					//Jos varastoalueen maakoodi on tyhjä niin käytetään toimittajan maakoodia
					if (trim($srow2["maa"]) == "") {
						$srow2["maa"] = trim($srow1["maakoodi"]);
					}

					//Jos toimittajan maakoodi on tyhjä niin käytetään yhtiön maakoodia
					if (trim($srow2["maa"]) == "") {
						$srow2["maa"] = trim($yhtiorow["maakoodi"]);
					}

					$class = '';

					// Summailaan tilauksen loppusummaa
					if ($row["netto"] != 'N') {
						$yhteensa 	   		+= $summa;			// lasketaan tilauksen loppusummaa ei N-nettorivit
						$veroton_yhteensa	+= $summa_alviton;	// lasketaan tilauksen veroton loppusumma ei N-nettorivit
						$kate_yhteensa 		+= $kate;			// lasketaan tilauksen katetta ei N-nettorivit

						//Kotimaiset varastot EI-nettorivit
						if(strtoupper($srow2["maa"]) == strtoupper(trim($yhtiorow["maakoodi"]))) {
							$kotimaan_varastot_yhteensa 		+= $summa;
							$veroton_kotimaan_varastot_yhteensa += $summa_alviton;
							$kate_kotimaan_varastot_yhteensa 	+= $kate;
						}
						//Ulkomaiset varastot EI-nettorivit
						else {
							$ulkomaan_varastot_yhteensa      	+= $summa;
							$veroton_ulkomaan_varastot_yhteensa	+= $summa_alviton;
							$kate_ulkomaan_varastot_yhteensa 	+= $kate;
						}
					}
					else {
						$nettoyhteensa      	+= $summa; 	// lasketaan tilauksen loppusummaa NETTORIVIT..
						$veroton_nettoyhteensa  += $summa_alviton; 	// lasketaan tilauksen veroton loppusumma NETTORIVIT..
						$kate_nettoyhteensa 	+= $kate;	// lasketaan tilauksen katetta NETTORIVIT..

						//Kotimaiset varastot nettorivit
						if(strtoupper($srow2["maa"]) == strtoupper(trim($yhtiorow["maakoodi"]))) {
							$nettokotimaan_varastot_yhteensa      		+= $summa;
							$veroton_nettokotimaan_varastot_yhteensa	+= $summa_alviton;
							$kate_nettokotimaan_varastot_yhteensa 		+= $kate;
						}
						//Ulkomaiset varastot nettorivit
						else {
							$nettoulkomaan_varastot_yhteensa      		+= $summa;
							$veroton_nettoulkomaan_varastot_yhteensa	+= $summa_alviton;
							$kate_nettoulkomaan_varastot_yhteensa 		+= $kate;
						}
					}

					//tutkitaan yleisesti onko käyttäjä tilannut tuotteita ulkomaassa sijaitsevasta varastosta
					if(strtoupper($srow2["maa"]) != strtoupper(trim($yhtiorow["maakoodi"]))) {
						$toimitetaan_ulkomaailta++;
					}

					$bruttoyhteensa += $brutto;
				}

				if ($row["hinta"] == 0.00) 	$row["hinta"] = '';
				if ($summa == 0.00) 		$summa = '';
				if ($row["ale"] == 0.00) 	$row["ale"] = '';
				if ($row["alv"] >= 500) 	$row["alv"] = t("M.V.");

				if ($row["hyllyalue"] == "") {
					$row["hyllyalue"] = "";
					$row["hyllynro"]  = "";
					$row["hyllyvali"] = "";
					$row["hyllytaso"] = "";
				}

				if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
					if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA') {
						echo "<tr><td class='back' colspan='10'><br></td></tr>";

						if ($row["perheid"] != 0 and $row["tyyppi"] == "W") {
							$class = " class='spec' ";
						}
					}
					elseif ($vanhaid == 'KALA' and $row["perheid"] != 0 and $row["tyyppi"] == "W") {
						$class = " class='spec' ";
					}
				}

				$vanhaid = $row["perheid"];

				if ($muokkauslukko == "") {
					require('tarkistarivi.inc');
				}

				// Tuoteperheiden lapsille ei näytetä rivinumeroa
				if ($row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0)) {
					
					if ($row["perheid"] == 0) {
						$pklisa = " and perheid2 = '$row[perheid2]'";
					}
					else {
						$pklisa = " and perheid = '$row[perheid]'";
					}
					
					$query = "	select yhtio 
								from tilausrivi 
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$kukarow[kesken]'
								$pklisa";
					$pkres = mysql_query($query) or pupe_error($query);										
					echo "<tr><td valign='top' rowspan='".mysql_num_rows($pkres)."'>$rivino</td>";
				}
				elseif($row["perheid"] == 0 and $row["perheid2"] == 0) {
					echo "<tr><td valign='top'>$rivino</td>";
				}

				// Tuotteen nimitys näytetään vain jos käyttäjän resoluution on iso
				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					if (strtolower($kukarow["kieli"]) != strtolower($yhtiorow["kieli"])) {
						$query = "	select selite 
									from tuotteen_avainsanat 
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laji	= 'nimitys_".$kukarow["kieli"]."'
									LIMIT 1";
						$pkres = mysql_query($query) or pupe_error($query);	

						if (mysql_num_rows($pkres) > 0) {
							$pkrow = mysql_fetch_array($pkres);
							$nimitys = $pkrow["selite"];
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
						echo "<td $class align='left' valign='top'></td>";
					}
				}
				elseif ($kukarow['extranet'] == '' and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'VAIHDA'>";
						echo "<td $class align='left' valign='top'>$paikat</td>";
						echo "</form>";
					}
					else {

						$query =	"select * from varastopaikat where yhtio='$kukarow[yhtio]'
									and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))
									and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))";
						$varastore = mysql_query($query) or pupe_error($query);
						$varastoro = mysql_fetch_array($varastore);

						if (strtoupper($varastoro['maa']) != strtoupper($yhtiorow['maakoodi'])) {
							echo "<td $class align='left' valign='top'><font color='#0000FF'>".strtoupper($varastoro['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
						}
						else {
							echo "<td $class align='left' valign='top'>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
						}
					}
				}
				elseif($kukarow['extranet'] == '') {
					echo "<td $class align='left' valign='top'></td>";
				}

				if($kukarow['extranet'] == '') {
					echo "	<td $class valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a>";
				}
				else {
					echo "	<td $class valign='top'>$row[tuoteno]";
				}

				// Näytetäänkö sarjanumerolinkki
				if ($row["sarjanumeroseuranta"] != "" and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {
					if ($row["sarjanumeroseuranta"] == "M" and $row["varattu"] < 0) {
						$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and ostorivitunnus='$row[tunnus]'";
					}
					else {
						$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and myyntirivitunnus='$row[tunnus]'";
					}
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					if ($sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&myyntirivitunnus=$row[tunnus]&from=$toim' style='color:00FF00'>sarjanro OK</font></a>)";
					}
					else {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&myyntirivitunnus=$row[tunnus]&from=$toim'>sarjanro</a>)";

						if ($laskurow['sisainen'] != '' or $laskurow['ei_lahetetta'] != '') {
							$tilausok++;
						}
					}
				}

				echo "</td>";

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and $row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right' valign='top'><input type='text' size='5' name='kpl' value='$row[varattu]'></td>";
					echo "</form>";
				}
				elseif ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right' valign='top'>".t("Laskutettu")."</td>";
					echo "</form>";
				}
				else {
					if ($row["var"] == 'J' or $row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
						$kpl_ruudulle = $row['jt'];
					}
					elseif($row["var"] == 'P') {
						$kpl_ruudulle = $row['tilkpl'];
					}
					else {
						$kpl_ruudulle = $row['varattu'];
					}

					echo "<td $class align='right' valign='top'>$kpl_ruudulle</td>";
				}

				echo "<td $class align='center' valign='top'>$row[var]</td>";

				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					echo "<td $class align='right' valign='top'>$bruttohinta</td>";
				}
				else {
					echo "<td $class align='right' valign='top'>$row[hinta]</td>";
					echo "<td $class align='right' valign='top'>$row[ale]</td>";
				}

				echo "	<td $class align='center' valign='top'>$row[netto]</td>";

				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					echo "	<td $class align='right' valign='top'>".sprintf('%.2f',$brutto)."</td>";
				}
				else {
					echo "	<td $class align='right' valign='top'>".sprintf('%.2f',$summa)."</td>";
				}

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "	<td $class align='right' valign='top' nowrap>".sprintf('%.2f',100*$kate/$summa_alviton)."%</td>";
				}
				
				echo "	<td $class align='right' valign='top'>$row[alv]</td>";

				if ($muokkauslukko == "") {

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'MUOKKAA'>
							<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Muokkaa")."'></td>
							</form>";

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'POISTA'>
							<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Poista")."'></td>
							</form>";

					if (($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0)) {
						
						if($laskurow["tila"] == "V") {
							$nappulanteksti = t("Lisää reseptiin");
						}
						else {
							$nappulanteksti = t("Lisää tuote");
						}
						
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='tila' value = 'LISAARESEPTIIN'>
								<input type='hidden' name='perheid' value = '$row[perheid]'>
								<input type='hidden' name='perheid2' value = '$row[perheid2]'>
								<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='$nappulanteksti'></td>
								</form>";
					}

					if ($row["var"] == "J" and $laskurow["alatila"] == "T") {
						if(saldo_myytavissa($row["tuoteno"], "", 0, "") >= $kpl_ruudulle) {
							echo "	<form action='$PHP_SELF' method='post'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='tila' value = 'MUUTA'>
									<input type='hidden' name='tapa' value = 'POISJTSTA'>
									<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Toimita")."'></td>
									</form>";
						}
					}


					if ($row["var"] == "P") {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'JT'>
								<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Jälkitoim")."'></td>
								</form>";
					}

					if ($saako_hyvaksya > 0) {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'OOKOOAA'>
								<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Hyväksy")."'></td>
								</form>";
					}
				}

				if ($varaosavirhe != '') {
					echo "<td class='back' valign='top'><font class='error'>$varaosavirhe</font></td>";
				}

				if ($row['kommentti'] != '') {
					$cspan="";
					if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
						$cspan=7;
					}
					elseif ($kukarow['hinnat'] == 0 and $kukarow['extranet'] != '') {
						$cspan=8;
					}
					elseif($kukarow["resoluutio"] == "I") {
						$cspan=9;
					}
					else{
						$cspan=8;
					}

					echo "</tr><tr><th colspan='2' valign='top'> * ".t("Kommentti").":</th><td colspan='$cspan' valign='top'>$row[kommentti]</td>";
				}

				$varaosavirhe = "";

				if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '') and ($row["var"] == "T" or $row["var"] == "U") and $riviok == 0) {
					//Tutkitaan tuotteiden lisävarusteita
					$query  = "	SELECT *
								FROM tuoteperhe
								JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
								WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno 	= '$row[tuoteno]'
								and tuoteperhe.tyyppi 		= 'L'
								order by tuoteperhe.tuoteno";
					$lisaresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($lisaresult) > 0 and $row["perheid2"] == 0) {
						echo "</tr>";

						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
								<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
								<input type='hidden' name='toim' 			value='$toim'>
								<input type='hidden' name='lisavarusteita' 	value='ON'>
								<input type='hidden' name='perheid' 		value='$row[perheid]'>
								<input type='hidden' name='perheid2' 		value='$row[tunnus]'>";

						$lislask = 0;

						while ($prow = mysql_fetch_array($lisaresult)) {

							echo "<tr><td class='back' valign='top'></td>";

							if ($kukarow["resoluutio"] == 'I') {
								echo "<td valign='top'>$prow[nimitys]</td>";
							}

							echo "<input type='hidden' name='tuoteno_array[$prow[tuoteno]]' value='$prow[tuoteno]'>";

							if ($row["var"] == "T") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='###$row[tilaajanrivinro]'>";
							}
							if ($row["var"] == "U") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='!!!$row[tilaajanrivinro]'>";
							}

							echo "<td valign='top'></td>";
							echo "<td valign='top'>$prow[tuoteno]</td>";
							echo "<td valign='top'><input type='text' name='kpl_array[$prow[tuoteno]]'   size='2' maxlength='8'		Style='{font-size: 8pt;}'></td>";
							echo "<td valign='top'><input type='text' name='var_array[$prow[tuoteno]]'   size='2' maxlength='1' 	Style='{font-size: 8pt;}'></td>
								  <td valign='top'><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12' 	Style='{font-size: 8pt;}'></td>
								  <td valign='top'><input type='text' name='ale_array[$prow[tuoteno]]'   size='5' maxlength='6' 	Style='{font-size: 8pt;}'></td>
								  <td valign='top'><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1' 	Style='{font-size: 8pt;}'></td>";

							$lislask++;

							if ($lislask == mysql_num_rows($lisaresult)) {
								echo "<td class='back' valign='top'><input type='submit' Style='{font-size: 8pt;}' value='".t("Lisää")."'></td>";
								echo "</form>";
							}

							echo "</tr>";
						}
					}
					elseif($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0 and $row["perheid2"] > 0) {
						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tila' value='LISLISAV'>
								<input type='hidden' name='rivitunnus' value='$row[tunnus]'>
								<td class='back' valign='top' nowrap><input type='submit' Style='{font-size: 8pt;}' value='".t("Lisää lisävarusteita")."'></td>
								</form>";
					}
				}
				echo "</tr>";
			}

			//kaikki yhteensä ARVO
			if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
				$arvo 					= $bruttoyhteensa;
				$arvo_kotimaa 			= $bruttoyhteensa;
				$arvo_ulkomaa 			= $bruttoyhteensa;

				$veroton_arvo 			= 0;
				$veroton_arvo_kotimaa 	= 0;
				$veroton_arvo_ulkomaa	= 0;

				$kate 					= 0;
				$kate_kotimaa 			= 0;
				$kate_ulkomaa			= 0;
			}
			else {
				$arvo 					= $yhteensa + $nettoyhteensa;
				$arvo_kotimaa 			= $kotimaan_varastot_yhteensa + $nettokotimaan_varastot_yhteensa;
				$arvo_ulkomaa			= $ulkomaan_varastot_yhteensa + $nettoulkomaan_varastot_yhteensa;

				$veroton_arvo 			= $veroton_yhteensa + $veroton_nettoyhteensa;
				$veroton_arvo_kotimaa 	= $veroton_kotimaan_varastot_yhteensa + $veroton_nettokotimaan_varastot_yhteensa;
				$veroton_arvo_ulkomaa	= $veroton_ulkomaan_varastot_yhteensa + $veroton_nettoulkomaan_varastot_yhteensa;

				$kate 					= $kate_yhteensa + $kate_nettoyhteensa;
				$kate_kotimaa 			= $kate_kotimaan_varastot_yhteensa + $kate_nettokotimaan_varastot_yhteensa;
				$kate_ulkomaa			= $kate_ulkomaan_varastot_yhteensa + $kate_nettoulkomaan_varastot_yhteensa;
			}

			$ycspan="";

			if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
				$ycspan=3;
			}
			elseif ($kukarow['hinnat'] == 0 and $kukarow['extranet'] != '') {
				$ycspan=4;
			}
			elseif($kukarow["resoluutio"] == "I") {
				$ycspan=5;
			}
			else{
				$ycspan=4;
			}

			//Jos myyjä on myymässä olkomaan varastoista liian pienellä summalla
			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				$ulkom_huom = "<font class='error'>".t("HUOM! Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $yhtiorow[valkoodi] --></font>";
			}
			else {
				$ulkom_huom = "";
			}

			//Jos myyjä ei ole extranetaaja ja hän myy useasta eri varastosta jotka sijaitsee useissa eri maissa niin näytetään maakohtaiset summat
			if ($kukarow['extranet'] == '' and $arvo_ulkomaa != 0) {
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<th colspan='4' align='right'>".t("Kotimaan varastoista").":</th>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo_kotimaa)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kotimaa/$arvo_kotimaa)."%</td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
					<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
					<th colspan='4' align='right'>".t("Ulkomaan varastoista").":</th>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_ulkomaa/$arvo_ulkomaa)."%</td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
			}

			echo "<tr>
				<td class='back' colspan='$ycspan'></td>
				<th colspan='4' align='right'>".t("Tilaus yhteensä").":</th>
				<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>";

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
				echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate/$arvo)."%</td>";
			}

			echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";


			//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
			if ($laskurow['erikoisale'] > 0 and $kukarow['hinnat'] != 1) {

				//Kaikki myynti josta lasketaan erikoisalennus
				$apu1 = (float) $laskurow['erikoisale']/100;	// erikoisale prosentti
				$apu2 = round($yhteensa*$apu1,2); 				// erikoisalen määrä
				$apu3 = round((1-$apu1)*$yhteensa,2);			// loppusumma

				//Pelkästään kotimaan myynti josta lasketaan erikoisalennus
				$kotimaa_apu1 = (float) $laskurow['erikoisale']/100;					// erikoisale prosentti
				$kotimaa_apu2 = round($kotimaan_varastot_yhteensa*$kotimaa_apu1,2); 	// erikoisalen määrä
				$kotimaa_apu3 = round((1-$kotimaa_apu1)*$kotimaan_varastot_yhteensa,2);	// loppusumma

				//Pelkästään ulkomaan myynti josta lasketaan erikoisalennus
				$ulkomaa_apu1 = (float) $laskurow['erikoisale']/100;					// erikoisale prosentti
				$ulkomaa_apu2 = round($ulkomaan_varastot_yhteensa*$ulkomaa_apu1,2); 	// erikoisalen määrä
				$ulkomaa_apu3 = round((1-$ulkomaa_apu1)*$ulkomaan_varastot_yhteensa,2);	// loppusumma


				//Verottomat summat
				//Kaikki myynti josta lasketaan erikoisalennus
				$veroton_apu1 = (float) $laskurow['erikoisale']/100;			// erikoisale prosentti
				$veroton_apu2 = round($veroton_yhteensa*$veroton_apu1,2); 		// erikoisalen määrä
				$veroton_apu3 = round((1-$veroton_apu1)*$veroton_yhteensa,2);	// loppusumma

				//Pelkästään kotimaan myynti josta lasketaan erikoisalennus
				$veroton_kotimaa_apu1 = (float) $laskurow['erikoisale']/100;									// erikoisale prosentti
				$veroton_kotimaa_apu2 = round($veroton_kotimaan_varastot_yhteensa*$veroton_kotimaa_apu1,2); 	// erikoisalen määrä
				$veroton_kotimaa_apu3 = round((1-$veroton_kotimaa_apu1)*$veroton_kotimaan_varastot_yhteensa,2);	// loppusumma

				//Pelkästään ulkomaan myynti josta lasketaan erikoisalennus
				$veroton_ulkomaa_apu1 = (float) $laskurow['erikoisale']/100;									// erikoisale prosentti
				$veroton_ulkomaa_apu2 = round($veroton_ulkomaan_varastot_yhteensa*$veroton_ulkomaa_apu1,2); 	// erikoisalen määrä
				$veroton_ulkomaa_apu3 = round((1-$veroton_ulkomaa_apu1)*$veroton_ulkomaan_varastot_yhteensa,2);	// loppusumma

				echo "<tr>
						<td class='back' colspan='$ycspan'></td>
						<th colspan='4' align='right'>".t("Erikoisalennus")." ($laskurow[erikoisale]%):</th>
						<td class='spec' align='right'>".sprintf("%.2f",$apu2)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'></td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

				//Kaikki yhteensä
				$kaikkiyhteensa 		= $apu3 + $nettoyhteensa;
				$kotimaa_kaikkiyhteensa = $kotimaa_apu3 + $nettokotimaan_varastot_yhteensa;
				$ulkomaa_kaikkiyhteensa = $ulkomaa_apu3 + $nettoulkomaan_varastot_yhteensa;


				$kate_kaikkiyhteensa 		 = $kate_yhteensa - $veroton_apu2 + $kate_nettoyhteensa;
				$kate_kotimaa_kaikkiyhteensa = $kate_kotimaan_varastot_yhteensa - $veroton_kotimaa_apu2 + $kate_nettokotimaan_varastot_yhteensa;
				$kate_ulkomaa_kaikkiyhteensa = $kate_ulkomaan_varastot_yhteensa - $veroton_ulkomaa_apu3 + $kate_nettoulkomaan_varastot_yhteensa;


				if ($kukarow['extranet'] == '' and $ulkomaa_kaikkiyhteensa != 0) {
					echo "<tr>
						<td class='back' colspan='$ycspan'></td>
						<th colspan='4' align='right'>".t("Yhteensä kotimaa").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$kotimaa_kaikkiyhteensa)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kotimaa_kaikkiyhteensa/$kotimaa_kaikkiyhteensa)."%</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					echo "<tr>
						<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
						<th colspan='4' align='right'>".t("Yhteensä ulkomaa").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$ulkomaa_kaikkiyhteensa)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_ulkomaa_kaikkiyhteensa/$ulkomaa_kaikkiyhteensa)."%</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}

				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<th colspan='4' align='right'>".t("Yhteensä").":</th>
					<td class='spec' align='right'>".sprintf("%.2f",$kaikkiyhteensa)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kaikkiyhteensa/$kaikkiyhteensa)."%</td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

			}
			else {
				$kaikkiyhteensa 		= $arvo;
				$kotimaa_kaikkiyhteensa = $arvo_kotimaa;
				$ulkomaa_kaikkiyhteensa = $arvo_ulkomaa;
			}

			//annetaan mahdollisuus antaa loppusumma joka jyvitetään riveille arvoosuuden mukaan
			if ($kukarow["extranet"] == "" and ($kukarow['kassamyyja'] != '' or $toim == "TARJOUS")) {

				if ($jyvsumma== '') {
					$jyvsumma='0.00';
				}



				if ($toim == "TARJOUS") {
					echo "<form name='valmis' action='tulostakopio.php' method='post'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='hidden' name='otunnus' value='$tilausnumero'>
							<th class='back' colspan='".(floor($ycspan/2))."' nowrap>".t("Näytä lomake").":</th>
							<td class='back' colspan='".(ceil($ycspan/2))."' nowrap>";

					echo "<select name='toim'>";
					echo "<option value='TARJOUS'>Tarjous</value>";
					echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
					echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
					echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
					echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
					echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</value>";
					echo "</select><input type='submit' value='".t("Näytä")."'>";
					echo "</td>";
					echo "</form>";
				}
				else {
					echo "<td class='back' colspan='$ycspan' nowrap></td>";
				}

				echo "	<form name='pyorista' action='$PHP_SELF' method='post' autocomplete='off'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='jyvita'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='arvo' value='$arvo'>
						<th colspan='4'>".t("Pyöristä loppusummaa").":</th>
						<td class='spec'><input type='text' size='7' name='jyvsumma' value='$jyvsumma'></td>
						<td class='spec'>$laskurow[valkoodi]</td>
						<td class='back' colspan='2'><input type='submit' value='".t("Jyvitä")."'></td>
						</tr>
						</form>";
			}

			echo "</table>";
		}
		else {
			$tilausok++;
		}

		// JT-rivikäyttöliittymä
		if ($estetaankomyynti == '' and $muokkauslukko == "" and $rivilaskuri == 0 and $laskurow["liitostunnus"] > 0 and $toim != "TYOMAARAYS" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and $toim != "TARJOUS") {
			//katotaan eka halutaanko asiakkaan jt-rivejä näkyviin
			$asjtq = "select tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]' and jtrivit = 1";
			$asjtapu = mysql_query($asjtq) or pupe_error($asjtq);

			if (mysql_num_rows($asjtapu) == 0) {

				echo "<br>";

				$toimittaja		= "";
				$toimittajaid	= "";

				$asiakasno 		= $laskurow["ytunnus"];
				$asiakasid		= $laskurow["liitostunnus"];

				$automaaginen 	= "";
				$tyyppi 		= "T";
				$tee			= "JATKA";
				$tuotenumero	= "";
				$toimi			= "";
				$tilaus_on_jo 	= "KYLLA";
				$superit		= "";

				if ($toim == 'SIIRTOLISTA') {
					$toimi = "JOO";
				}

				require ('jtselaus.php');
			}
	    }
	}

	// tulostetaan loppuun parit napit..
	if ((int) $kukarow["kesken"] != 0) {
		echo "<br><br><table width='100%'><tr>";

		if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LASKUTAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Laskuta valitut rivit")." *'>
					</form></td>";

			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LEPAAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Jätä myyntitili lepäämään")." *'>
					</form></td>";

		}


		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TYOMAARAYS") {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Työmääräys lepäämään")." *'>
					</form></td>";

		}

		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TARJOUS"  and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hyväksy tarjous")."'>
					</form>";


			echo "	<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYLKAATARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hylkää tarjous")."'>
					</form>";

			echo "	</td>";
		}

		echo "<td class='back' valign='top'>";

		//Näytetään tilaus valmis nappi
		if ($muokkauslukko == "" and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			// Jos myyjä myy todella pienellä summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
			$javalisa = "";
			if ($kukarow["extranet"] == "" and $ulkomaa_kaikkiyhteensa != 0 and $ulkomaa_kaikkiyhteensa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
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
					<form action='$PHP_SELF' method='post' $javalisa>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='VALMIS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";

				if($toimitetaan_ulkomaailta > 0) {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
				}

				if ($kukarow["extranet"] == "" and $kateinen == 'X' and $kukarow["kassamyyja"] != '') {
					echo t("Valitse kuittikopion tulostuspaikka").":<br>";
					echo "<select name='valittu_kopio_tulostin'>";
					echo "<option value=''>".t("Ei kirjoitinta")."</option>";

					$querykieli = "	select *
									from kirjoittimet
									where yhtio = '$kukarow[yhtio]'
									ORDER BY kirjoitin";
					$kires = mysql_query($querykieli) or pupe_error($querykieli);

					while ($kirow=mysql_fetch_array($kires)) {
						echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
					}

					echo "</select><br><br></td></tr><tr><td class='back'>";
				}

				echo "<input type='submit' ACCESSKEY='V' value='$otsikko ".t("valmis")."'>";
				echo "</form>";
			}
		}

		if ($muokkauslukko == "") {
			echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
								msg = '".t("Haluatko todella poistaa tämän tietueen?")."';
								return confirm(msg);
						}
				</SCRIPT>";

			echo "</td><td align='right' class='back' valign='top'>
					<form name='valmis' action='$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='POISTA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Mitätöi koko")." $otsikko *'>
					</form></td></tr></table>";
		}
	}
}

if (file_exists("../inc/footer.inc")) {
	require ("../inc/footer.inc");
}
else {
	require ("footer.inc");
}

?>