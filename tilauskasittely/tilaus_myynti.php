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
	//aika j‰nn‰ homma jos t‰nne jouduttiin
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
		echo "<font class='error'>".t("Tilaus on aktiivisena k‰ytt‰j‰ll‰")." $row[nimi]. ".t("Tilausta ei voi t‰ll‰ hetkell‰ muokata").".</font><br>";

		// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
		$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		exit;
	}
	else {
		$query = "update kuka set kesken='$tilausnumero' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		// N‰in ostataan valita pikatilaus
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

// Extranet keississ‰ asiakasnumero tulee k‰ytt‰j‰n takaa
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
		echo t("VIRHE: K‰ytt‰j‰tiedoissasi on virhe! Ota yhteys j‰rjestelm‰n yll‰pit‰j‰‰n.")."<br><br>";
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
	$otsikko = t("Tyˆm‰‰r‰ys");
}
elseif ($toim == "VALMISTAVARASTOON") {
	$otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
	$otsikko = t("Varastosiirto");
}
elseif ($toim == "SIIRTOTYOMAARAYS") {
	$otsikko = t("Sis‰inen tyˆm‰‰r‰ys");
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

// jos ei olla postattu mit‰‰n, niin halutaan varmaan tehd‰ kokonaan uusi tilaus..
if ($kukarow["extranet"] == "" and count($_POST) == 0 and $from != "LASKUTATILAUS") {
	$tila				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow["kesken"]	= '';

	//varmistellaan ettei vanhat kummittele...
	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

// asiakasnumero on annettu, etsit‰‰n tietokannasta...
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

	// Ei n‰ytet‰ tilausta jos meill‰ on asiakaslista ruudulla
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
		$sisviesti1		= $asiakasrow["sisviesti1"];

		//annetaan extranet-tilaukselle aina paras prioriteetti, t‰m‰ on hyv‰ porkkana.
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
		//yhtiˆn oletusalvi!
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

//tietyiss‰ keisseiss‰ tilaus lukitaan (ei syˆttˆrivi‰ eik‰ muota muokkaa/poista-nappuloita)
$muokkauslukko = "";

if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
	$muokkauslukko = "LUKOSSA";
}



// Hyv‰ksyt‰‰n tajous ja tehd‰‰n tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p‰ivit‰ nappia. N‰in ei saa tehd‰")."! </font>";
		exit;
	}

	//Luodaan valituista riveist‰ suoraan normaali ostotilaus
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

// Hyl‰t‰‰n tarjous
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p‰ivit‰ nappia. N‰in ei saa tehd‰")."! </font>";
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
		if ($srow["varattu"] < 0) {
			$tunken = "ostorivitunnus";
		}
		else {
			$tunken = "myyntirivitunnus";
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

// Laitetaan myyntitili takaisin lep‰‰m‰‰n
if ($kukarow["extranet"] == "" and $tee == "LEPAAMYYNTITILI") {
	$tilatapa = "LEPAA";

	require ("laskuta_myyntitilirivi.inc");
}

if($tee == "MAKSUSOPIMUS") {
	require("maksusopimus.inc");
}

// Poistetaan tilaus
if ($tee == 'POISTA') {

	// poistetaan tilausrivit, mutta j‰tet‰‰n PUUTE rivit analyysej‰ varten...
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
		if ($srow["varattu"] < 0) {
			$tunken = "ostorivitunnus";
		}
		else {
			$tunken = "myyntirivitunnus";
		}

		$query = "update sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and $tunken='$srow[tunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
	}

	//Poistetaan maksusuunnitelma
	$query = "DELETE from maksupositio WHERE yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE lasku SET tila='D', alatila='L', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit‰tˆi tilauksen")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	if ($kukarow["extranet"] == "") {
		echo "<font class='message'>".t("Tilaus")." $kukarow[kesken] ".t("mit‰tˆity")."!</font><br><br>";
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

//Lis‰t‰‰n t‰n asiakkaan valitut JT-rivit t‰lle tilaukselle
if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen") {
	$tilaus_on_jo 	= "KYLLA";

	require("jtselaus.php");

	$tyhjenna 	= "JOO";
	$tee 		= "";
}

//Tyhjennt‰‰n syˆttˆkent‰t
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
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p‰ivit‰ nappia. N‰in ei saa tehd‰")."! </font>";
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

		//Tehd‰‰n asiakasmemotapahtuma
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

		 //Tehd‰‰n myyj‰lle muistutus
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
	// Sis‰inen tyˆm‰‰r‰ys valmis
	elseif($kukarow["extranet"] == "" and $toim == "SIIRTOTYOMAARAYS") {
		require("../tyomaarays/tyomaarays.inc");
	}
	// Tyˆm‰‰r‰ys valmis
	elseif ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		require("../tyomaarays/tyomaarays.inc");
	}
	// Siirtolista, myyntitili, valmistus valmis
	elseif ($kukarow["extranet"] == "" and ($toim == "SIIRTOTYOMAARAYS" or $toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI")) {
		require ("tilaus-valmis-siirtolista.inc");
	}
	// Myyntitilaus valmis
	else {
		//Jos k‰ytt‰j‰ on extranettaaja ja h‰n ostellut tuotteita useista eri maista niin laitetaan tilaus holdiin
		if ($kukarow["extranet"] != "" and $toimitetaan_ulkomaailta == "YES") {
			$kukarow["taso"] = 2;
		}

		//katotaan onko asiakkaalla yli 30 p‰iv‰‰ vanhoja maksamattomia laskuja
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

			//ja jos on niin ne siirret‰‰n tilaus holdiin
			if ($saarow['dd'] > 0) {
				$kukarow["taso"] = 2;
			}
		}

		// Extranetk‰ytt‰j‰ jonka tilaukset on hyv‰ksytett‰v‰ meid‰n myyjill‰
		if ($kukarow["extranet"] != "" and $kukarow["taso"] == 2) {
			$query  = "	update lasku set
						tila = 'N',
						alatila='F'
						where yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);


			// tilaus ei en‰‰ kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

		}
		else {
			//Luodaan valituista riveist‰ suoraan normaali ostotilaus
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

				//Pyydet‰‰n tilaus-valmista olla echomatta mit‰‰n
				$silent = "SILENT";
			}

			// tulostetaan l‰hetteet ja tilausvahvistukset tai sis‰inen lasku..
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
	$kala = exec("echo \"Terveisi‰ $yhtiorow[nimi]\" | /usr/bin/gnokii --sendsms +358505012254 -r");
	echo "$kala<br>";
	$tee = "";
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or ($toim != "PIKATILAUS" and $laskurow["liitostunnus"] == ''))) {

	//T‰m‰ jotta myˆs rivisyˆtˆn alkuhomma toimisi
	$tee = "OTSIK";

	if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
		require("otsik_siirtolista.inc");
	}
	else {
		require ('otsik.inc');
	}

	//T‰ss‰ halutaan jo hakea uuden tilauksen tiedot
	$query   	= "	select *
					from lasku
					where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow   = mysql_fetch_array($result);
}

//lis‰t‰‰n rivej‰ tiedostosta
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


// Tehd‰‰n rahoituslaskuelma
if ($tee == 'osamaksusoppari') {
	require('osamaksusoppari.inc');
}

// Tehd‰‰n vakuutushakemus
if ($tee == 'vakuutushakemus') {
	require('vakuutushakemus.inc');
}


if ($kukarow["extranet"] == "" and $tee == 'jyvita') {
	require("jyvita_riveille.inc");
}


// n‰ytet‰‰n tilaus-ruutu...
if ($tee == '') {
	$focus = "tuotenumero";
	$formi = "tilaus";

	echo "<font class='head'>$otsikko</font><hr>";

	//katsotaan ett‰ kukarow kesken ja $kukarow[kesken] stemmaavat kesken‰‰n
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("K‰y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}
	if ($kukarow['kesken'] != '0') {
		$tilausnumero=$kukarow['kesken'];
	}

	// T‰ss‰ p‰ivitet‰‰n 'pikaotsikkoa' jos kenttiin on jotain syˆtetty
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
				echo "<font class='error'>".t("Syˆtt‰m‰si myyj‰numero")." $myyjanro ".t("lˆytyi usealla k‰ytt‰j‰ll‰")."!</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Syˆtt‰m‰si myyj‰numero")." $myyjanro ".t("ei lˆytynyt")."!</font><br><br>";
			}
		}

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$meapurow = mysql_fetch_array($meapu);

		// jos kyseess‰ oli k‰teinen
		if ($meapurow["kateinen"] != "") {
			// haetaan toimitustavan tiedot tarkastuksia varten
			$apuqu2 = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
			$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);
			$meapu2row = mysql_fetch_array($meapu2);

			// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee j‰rjestyksess‰ ekan
			if ($meapu2row["nouto"] == "") {
				$apuqu = "select * from toimitustapa where yhtio = '$kukarow[yhtio]' and nouto != '' order by jarjestys limit 1";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$toimitustapa = $apuro['selite'];
				echo "<font class='error'>".t("Toimitustapa on oltava nouto, koska maksuehto on k‰teinen")."!</font><br><br>";
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

		// aivan karseeta, mutta joskus pit‰‰ olla n‰in asiakasyst‰v‰llinen... toivottavasti ei h‰iritse ket‰‰n
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
					<td class='back'><input type='Submit' value='".t("Lis‰‰ vahinkotiedot")."'></td>
					</form>";
		}

		/*if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='SMS'>
					<input type='hidden' name='toim' value='$toim'>
					<td class='back'><input type='Submit' value='".t("L‰het‰ viesti")."'></td>
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
	
	//Oletetaan, ett‰ tilaus on ok, $tilausok muuttujaa summataan alempana jos jotain virheit‰ ilmenee
	$tilausok = 0;
	
	$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);
	$meapurow = mysql_fetch_array($meapu);
	
	if ($laskurow["liitostunnus"] > 0 and $meapurow["kateinen"] == "" and ($laskurow["nimi"] == '' or $laskurow["osoite"] == '' or $laskurow["postino"] == '' or $laskurow["postitp"] == '')) {
		if ($toim != 'VALMISTAVARASTOON' and $toim != 'SIIRTOLISTA' and $toim != 'SIIRTOTYOMAARAYS') {
			echo "<font class='error'>".t("VIRHE: Tilauksen laskutusosoitteen tiedot ovat puutteelliset")."!</font><br><br>";
			$tilausok++;
		}
	}

	// kirjoitellaan otsikko
	echo "<table>";

	// t‰ss‰ alotellaan koko formi.. t‰m‰ pit‰‰ kirjottaa aina
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
				
				if ($kukarow['hinnat'] == 1) {
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
				echo "<th align='left'>".t("Myyj‰nro").":</th>";
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

				//jos asiakkaalla on luokka K niin se on myyntikiellossa ja siit‰ herjataan
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
		// asiakasnumeroa ei ole viel‰ annettu, n‰ytet‰‰n t‰yttˆkent‰t

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
			<th align='left'>".t("Myyj‰nro")."</th>
			<td><input type='text' size='10' maxlength='10' name='myyjanro' value='$my'></td>
			</tr>";
	}

	echo "</table>";

	if ($kukarow['extranet'] == '' and $kukarow['kassamyyja'] == '' and $laskurow['liitostunnus'] > 0 and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {
		$sytunnus = $laskurow['ytunnus'];
		$eiliittymaa = 'ON';
		require ("../raportit/saatanat.php");

		if ($ylikolkyt > 0) {
			echo "<font class='error'>".t("HUOM!!!!!! Asiakkaalla on yli 30 p‰iv‰‰ sitten er‰‰ntyneit‰ laskuja, olkaa yst‰v‰llinen ja ottakaa yhteytt‰ myyntireskontran hoitajaan")."!!!!!<br></font>";
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

	if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
		//P‰ivitet‰‰n is‰n perheid jotta voidaan lis‰t‰ lis‰‰ lis‰varusteita
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

			// Tehd‰‰n pari juttua jos tuote on sarjanuerosaurannassa
			if($tilausrivi["sarjanumeroseuranta"] != '') {
				//Nollataan sarjanumero
				if ($tilausrivi["varattu"] < 0) {
					$tunken = "ostorivitunnus";
				}
				else {
					$tunken = "myyntirivitunnus";
				}

				$query = "SELECT tunnus FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivi[tuoteno]' and $tunken='$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				//Pidet‰‰n sarjatunnus muistissa
				if ($tapa != "POISTA") {
					$myy_sarjatunnus = $sarjarow["tunnus"];
				}

				$query = "update sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivi[tuoteno]' and $tunken='$tilausrivi[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);

			}

			// Poistetaan myˆs tuoteperheen lapset
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

				//Jos lasta muokataan, niin s‰ilytet‰‰n sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					echo "<input type='hidden' name='perheid' value = '$tilausrivi[perheid]'>";
				}

				//Jos lis‰varustetta muokataan, niin s‰ilytet‰‰n sen perheid2
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

	//Lis‰t‰‰n tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAARESEPTIIN") {
		
		if ($teeperhe == "OK") {
			$query = "	UPDATE tilausrivi
						SET perheid2 = '$isatunnus'
						WHERE yhtio  = '$kukarow[yhtio]'
						and tunnus   = '$isatunnus'";
			$presult = mysql_query($query) or pupe_error($query);
			$perheid2 = $isatunnus;
		}
		
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

	//Lis‰t‰‰n rivi
	if ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array)) and $tila != "MUUTA" and $ulos == '') {

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
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
				//Tuote lˆytyi
				$trow = mysql_fetch_array($result);

				//extranettajille ei myyd‰ tuotteita joilla ei ole myyntihintaa
				if ($kukarow["extranet"] != '' and $trow["myyntihinta"] == 0) {
					$varaosavirhe = t("VIRHE: Tuotenumeroa ei lˆydy j‰rjestelm‰st‰!");
					$trow 	 = "";
					$tuoteno = "";
					$kpl	 = 0;
				}
			}
			elseif ($kukarow["extranet"] != '') {
				$varaosavirhe = t("VIRHE: Tuotenumeroa ei lˆydy j‰rjestelm‰st‰!");
				$tuoteno = "";
				$kpl	 = 0;
			}
			else {
				//Tuotetta ei lˆydy, aravataan muutamia muuttujia
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

			//Ennakkotilauksen ja Tarjoukset eiv‰t varaa saldoa
			if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tila"] == "V") {
				$varataan_saldoa = "EI";
			}
			else {
				$varataan_saldoa = "";
			}

			//Tehd‰‰n muuttujaswitchit

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

			//Extranettaajat eiv‰t voi hyvitell‰ itselleen tuotteita
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
			//P‰ivitet‰‰n is‰lle perheid2 jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
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

	//Syˆttˆrivi
	if ($muokkauslukko == "") {
		if(file_exists("myyntimenu.inc")){
			
			/*
				Customoidut menuratkaisut onnistuu nyt myyntimenu.inc tiedostolla
				myyntimenu.inc tiedosto sis‰lt‰‰n $myyntimenu array jonka perusteella rakennetaan valikko sek‰ suoritetaan kyselyt.
				
				Tuloksena on $ulos muuttuja joka liitet‰‰n syotarivi.inc tiedostossa tuoteno kentt‰‰n
				
				myyntimenut sis‰lt‰‰ aina tuotehakuvalinnan jolla voi palata normaaliin tuotehakuun.
				
			*/	

			
			//	Haetaan myyntimenu Array ja kysely Array
			//	Jos menutilaa ei ole laitetaan oletus
			if(!isset($menutila)) $menutila = "oletus";
			require("myyntimenu.inc");
			
			//suoritetaan kysely ja tehd‰‰n menut jos aihetta
			if(is_array($myyntimenu)){

				//	Tehd‰‰n menuset				
				$menuset = "<select name='menutila' onChange='submit()'>";
				foreach($myyntimenu as $key => $value){
					$sel = "";
					if($key == $menutila) {
						$sel = "SELECTED";
					}

					$menuset .= "<option value='$key' $sel>$value[menuset]</option>";
				}				
				
				//	Jos ei olla myyntimenussa n‰ytet‰‰n aina haku
				$sel = "";
				if(!isset($myyntimenu[$menutila])) {
					$sel = "SELECTED";
				}
				
				$menuset .= "<option value='haku' $sel>Tuotehaku</option>";
				$menuset .= "</select>";
				
				//	Tehd‰‰n paikka menusetille
				echo "
							<table>
								<tr>
									<td class='back' align = 'left'><b>".t("Lis‰‰ rivi").": </b></td><td class='back' align = 'left'>$menuset</td>
								</tr>
							</table><hr>";
				
								
				//	Tarkastetaan viel‰, ett‰ menutila on m‰‰ritelty ja luodaan lista
				if($myyntimenu[$menutila]["query"] != "") {
					unset($ulos);
					
					// varsinainen kysely ja menu
					$query = " SELECT distinct(tuote.tuoteno), nimitys
								FROM tuote
								LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
								WHERE tuote.yhtio ='$kukarow[yhtio]' and ".$myyntimenu[$menutila]["query"];
								$tuoteresult = mysql_query($query) or pupe_error($query);

					$ulos = "<select Style=\"width: 230px; font-size: 8pt; padding: 0\" name='tuoteno' multiple ='TRUE'><option value=''>Valitse tuote</option>";

					if(mysql_num_rows($tuoteresult) > 0) {	
						while($row=mysql_fetch_array($tuoteresult)) {
							$sel='';
							if($tuoteno==$row['tuoteno']) $sel='SELECTED';
							$ulos .= "<option value='$row[tuoteno]' $sel>$row[tuoteno] - $row[nimitys]</option>";
						}
						$ulos .= "</select>";
					}
					else {
						echo "Valinnan antama haku oli tyhj‰<br>";
					}
				}
				//	Jos haetaan niin ei ilmoitella turhia
				elseif($menutila != "haku" and $menutila != "") {
					echo "HUOM! Koitettiin hakea myyntimenua '$menutila' jota ei ollut m‰‰ritelty!<br>";
				}
				
			}
			else {
				echo "HUOM! Koitettiin hakea myyntimenuja, mutta tiedot olivat puutteelliset.<br>";
			}				
		}
		else {
			echo "<b>".t("Lis‰‰ rivi").": </b><hr>";
		}
		
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
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS" or $toim == "MYYNTITILI") {
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
					if (tuotetyyppi='K','Tyˆ','Varaosa') tuotetyyppi,
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

				if ($kukarow['hinnat'] == 0) {
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

			while ($row = mysql_fetch_array($result)) {

				if ($toim == "TYOMAARAYS") {
					if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Tyˆ') {
						$tuotetyyppi = 1;

						echo "<tr><td class='back' colspan='10'>".t("Tyˆt").":</td></tr>";
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

				//K‰‰nnet‰‰n t‰n rivin hinta oikeeseen valuuttaan
				$row["kotihinta"] = $row["hinta"];
				$row["hinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"]);
				

				// T‰n rivin rivihinta
				$summa		= $row["hinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);
				$kotisumma	= $row["kotihinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);

				// T‰n rivin alviton rivihinta
				if ($yhtiorow["alv_kasittely"] == '') {
					$summa_alviton = $summa / (1+$row["alv"]/100);
					$kotisumma_alviton = $kotisumma / (1+$row["alv"]/100);
				}
				else {
					$summa_alviton = $summa;
					$kotisumma_alviton = $kotisumma;
				}
				
				if ($row["var"] == "P" or $row["var"] == "V") {
					$class = " class='spec' ";
				}
				elseif ($row["var"] == "J") {
					$class = " class='green' ";
				}
				else {
					$class = '';
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

				// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
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

				// Tuotteen nimitys n‰ytet‰‰n vain jos k‰ytt‰j‰n resoluution on iso
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
						echo "<td $class align='left' valign='top'></td>";
					}
				}
				elseif ($kukarow['extranet'] == '' and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'VAIHDA'>";
						echo "<td $class align='left' valign='top'>$paikat</td>";
						echo "</form>";
					}
					else {

						$query = "	select * 
									from varastopaikat 
									where yhtio='$kukarow[yhtio]'
									and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))
									and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$row[hyllyalue]'), 5, '0'),lpad(upper('$row[hyllynro]'), 5, '0'))";
						$varastore = mysql_query($query) or pupe_error($query);
						$varastoro = mysql_fetch_array($varastore);

						if (strtoupper($varastoro['maa']) != strtoupper($yhtiorow['maakoodi'])) {
							echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varastoro['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
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
					echo "<td $class valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a>";
				}
				else {
					echo "<td $class valign='top'>$row[tuoteno]";
				}

				// N‰ytet‰‰nkˆ sarjanumerolinkki
				if ($row["sarjanumeroseuranta"] != "" and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {
					
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
					
					$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and $tunken1='$row[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					
					if ($sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&$tunken2=$row[tunnus]&from=$toim' style='color:00FF00'>sarjanro OK</font></a>)";
					}
					else {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&$tunken2=$row[tunnus]&from=$toim'>sarjanro</a>)";

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

					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$kpl_ruudulle)."</td>";
				}

				echo "<td $class align='center' valign='top'>$row[var]</td>";
				
				if ($kukarow['hinnat'] == 1) {
					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$row["myyntihinta"])."</td>";
				}
				else {
					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$row["hinta"])."</td>";
					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$row["ale"])."</td>";
				}

				echo "<td $class align='center' valign='top'>$row[netto]</td>";

				if ($kukarow['hinnat'] == 1) {
					$hinta = $row["myyntihinta"];

					require('alv.inc');

					$brutto = $hinta*($row["varattu"]+$row["jt"]);
					
					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$brutto)."</td>";
				}
				else {
					echo "<td $class align='right' valign='top'>".sprintf('%.2f',$summa)."</td>";
				}

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					// T‰n rivin kate
					$kate = 0;

					if ($kukarow['extranet'] == '' and $row["sarjanumeroseuranta"] != "") {

						$query = "select ostorivitunnus from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and myyntirivitunnus='$row[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						if ($sarjarow["ostorivitunnus"] > 0 and $sarjarow["ostorivitunnus"] != $row["tunnus"] and $row["varattu"]+$row["jt"] > 0) {

							$limitti = $row["varattu"]+$row["jt"];

							$query = "	select sum(rivihinta) rivihinta
										from tilausrivi
										where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and tunnus='$sarjarow[ostorivitunnus]'
										LIMIT $limitti";
							$sarjares = mysql_query($query) or pupe_error($query);
							$sarjarow = mysql_fetch_array($sarjares);

							$kate = sprintf('%.2f',100*($kotisumma_alviton - $sarjarow["rivihinta"])/$kotisumma_alviton)."%";
						}
						else {
							$kate = "N/A";	
						}
					}
					elseif ($kukarow['extranet'] == '') {
						$kate = sprintf('%.2f',100*($kotisumma_alviton - ($row["kehahin"]*($row["varattu"]+$row["jt"])))/$kotisumma_alviton)."%";
					}
					
					echo "<td $class align='right' valign='top' nowrap>$kate</td>";
				}
				
				echo "<td $class align='right' valign='top'>$row[alv]</td>";

				if ($muokkauslukko == "") {

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='menutila' value='$menutila'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'MUOKKAA'>
							<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Muokkaa")."'></td>
							</form>";

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='menutila' value='$menutila'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'POISTA'>
							<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Poista")."'></td>
							</form>";

					if (($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0) or (($toim == 'SIIRTOLISTA' or $toim == "SIIRTOTYOMAARAYS") and $row["perheid2"] == 0 and $row["perheid"] == 0)) {
						
						if ($row["perheid2"] == 0 and $row["perheid"] == 0) {
							$nappulanteksti = t("Lis‰‰ tuote");
							
							$plisax = "	<input type='hidden' name='teeperhe'  value = 'OK'>
										<input type='hidden' name='isatunnus' value = '$row[tunnus]'>";	
						}
						elseif($laskurow["tila"] == "V") {
							$nappulanteksti = t("Lis‰‰ reseptiin");
							$plisax = "";
						}
						else {
							$nappulanteksti = t("Lis‰‰ tuote");
							$plisax = "";
						}
						
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='tila' value = 'LISAARESEPTIIN'>
								$plisax
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
									<input type='hidden' name='menutila' value='$menutila'>
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
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'JT'>
								<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("J‰lkitoim")."'></td>
								</form>";
					}

					if ($saako_hyvaksya > 0) {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='hidden' name='tila' value = 'OOKOOAA'>
								<td class='back' valign='top' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Hyv‰ksy")."'></td>
								</form>";
					}
				}

				if ($varaosavirhe != '') {
					echo "<td class='back' valign='top'><font class='error'>$varaosavirhe</font></td>";
				}

				if ($row['kommentti'] != '') {
					$cspan=8;
					
					if ($kukarow['hinnat'] == 1) {
						$cspan--;
					}
					if($kukarow["resoluutio"] == "I") {
						$cspan++;
					}

					echo "</tr><tr><th colspan='2' valign='top'> * ".t("Kommentti").":</th><td colspan='$cspan' valign='top'>$row[kommentti]</td>";
				}

				$varaosavirhe = "";

				if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '') and ($row["var"] == "T" or $row["var"] == "U") and $riviok == 0) {
					//Tutkitaan tuotteiden lis‰varusteita
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
								echo "<td class='back' valign='top'><input type='submit' Style='{font-size: 8pt;}' value='".t("Lis‰‰")."'></td>";
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
								<input type='hidden' name='menutila' value='$menutila'>
								<td class='back' valign='top' nowrap><input type='submit' Style='{font-size: 8pt;}' value='".t("Lis‰‰ lis‰varusteita")."'></td>
								</form>";
					}
				}
				echo "</tr>";
			}
		
			//Laskeskellaan tilauksen loppusummaa
			$alvquery = "	SELECT if(isnull(varastopaikat.maa) or varastopaikat.maa='', '$yhtiorow[maakoodi]', varastopaikat.maa) maa, group_concat(tilausrivi.tunnus) rivit
							FROM tilausrivi
							LEFT JOIN varastopaikat ON varastopaikat.yhtio = if(tilausrivi.var='S', if((SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro)!='', (SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro), tilausrivi.yhtio), tilausrivi.yhtio)
		                    and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
		                    and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) 
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$kukarow[kesken]'
							GROUP BY 1
							ORDER BY 1";
			$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
					
			$summa 					= 0; 	// Tilauksen verollinen loppusumma tilauksen valuutassa
			$summa_eieri			= 0;	// Tilauksen verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
			$arvo  					= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa
			$arvo_eieri				= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
			$kotiarvo				= 0;	// Tilauksen veroton loppusumma yhtiˆn valuutassa
			$kotiarvo_eieri			= 0;	// Tilauksen veroton loppusumma yhtiˆn valuutassa ilman erikoisalennusta
			$kate					= 0;	// Tilauksen kate yhtiˆn valuutassa
			$kate_eieri				= 0;	// Tilauksen kate yhtiˆn valuutassa ilman erikoisalennusta
			
			$summa_kotimaa 			= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa
			$summa_kotimaa_eieri 	= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
			$arvo_kotimaa			= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa
			$arvo_kotimaa_eieri		= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
			$kotiarvo_kotimaa		= 0;	// Kotimaan toimitusten veroton loppusumma yhtiˆn valuutassa	
			$kotiarvo_kotimaa_eieri	= 0;	// Kotimaan toimitusten veroton loppusumma yhtiˆn valuutassa ilman erikoisalennusta	
			$kate_kotimaa			= 0;	// Kotimaan toimitusten kate yhtiˆn valuutassa
			$kate_kotimaa_eieri		= 0;	// Kotimaan toimitusten kate yhtiˆn valuutassa ilman erikoisalennusta
			
			$summa_ulkomaa			= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa
			$summa_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
			$arvo_ulkomaa			= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa
			$arvo_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
			$kotiarvo_ulkomaa		= 0;	// Ulkomaan toimitusten veroton loppusumma yhtiˆn valuutassa
			$kotiarvo_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten veroton loppusumma yhtiˆn valuutassa ilman erikoisalennusta		
			$kate_ulkomaa			= 0;	// Ulkomaan toimitusten katee yhtiˆn valuutassa
			$kate_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten katee yhtiˆn valuutassa ilman erikoisalennusta
			
			while ($alvrow = mysql_fetch_array($alvresult)) {
				
				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
					$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
					$hinta_myy = "round(tuote.myyntihinta/$laskurow[vienti_kurssi], 2)";
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
				elseif ($alvrow["alv"] >= 500) {
					$lisat = "	'0' alv,
								$hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
								'0' alv_ei_erikoisaletta,
								$hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) rivihinta_ei_erikoisaletta,								
								tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) kotirivihinta,
								tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) kotirivihinta_ei_erikoisaletta";
				}
				else {
					$lisat = "	$hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100) alv,
								$hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
								$hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) * (tilausrivi.alv/100) alv_ei_erikoisaletta,
								$hinta_riv / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (1-tilausrivi.ale/100) rivihinta_ei_erikoisaletta,
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
							WHERE tilausrivi.otunnus = '$kukarow[kesken]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus in ($alvrow[rivit])";
				$aresult = mysql_query($aquery) or pupe_error($aquery);
				
				while($arow = mysql_fetch_array($aresult)) {					
					$rivikate 		= 0;	// Rivin kate yhtiˆn valuutassa
					$rivikate_eieri	= 0;	// Rivin kate yhtiˆn valuutassa ilman erikoisalennusta
					
					if ($arow["sarjanumeroseuranta"] != "") {

						$query = "select ostorivitunnus from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$arow[tuoteno]' and myyntirivitunnus='$arow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						if ($sarjarow["ostorivitunnus"] != 0 and $sarjarow["ostorivitunnus"] != $arow["tunnus"] and $arow["varattu"] > 0) {
							
							$limitti = (int) round($arow["varattu"],0);
							
							$query = "	select sum(rivihinta) rivihinta
										from tilausrivi
										where yhtio='$kukarow[yhtio]' and tuoteno='$arow[tuoteno]' and tunnus='$sarjarow[ostorivitunnus]'
										LIMIT $limitti";
							$sarjares = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($sarjares) > 0) {
								$sarjarow = mysql_fetch_array($sarjares);

								$rivikate 		= $arow["kotirivihinta"] - $sarjarow["rivihinta"];
								$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - $sarjarow["rivihinta"];
							}
						}
					}
					else {
						$rivikate 		= $arow["kotirivihinta"]  - ($arow["kehahin"]*$arow["varattu"]);
						$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"]  - ($arow["kehahin"]*$arow["varattu"]);
					}
					
					if (trim(strtoupper($alvrow["maa"])) == trim(strtoupper($yhtiorow["maakoodi"]))) {
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
						
			//Jos myyj‰ on myym‰ss‰ olkomaan varastoista liian pienell‰ summalla
			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				$ulkom_huom = "<font class='error'>".t("HUOM! Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $yhtiorow[valkoodi] --></font>";
			}
			else {
				$ulkom_huom = "";
			}

			$ycspan=3;

			if ($kukarow['hinnat'] == 1) {
				$ycspan--;
			}
			if($kukarow["resoluutio"] == "I") {
				$ycspan++;
			}
			
			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
				echo "<tr>
						<td class='back' colspan='$ycspan'></td>
						<th colspan='5' align='right'>".t("Kotimaan varastoista").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$arvo_kotimaa_eieri)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_kotimaa_eieri/$kotiarvo_kotimaa_eieri)."%</td>";
				}
				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
		
				echo "<tr>
					<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
					<th colspan='5' align='right'>".t("Ulkomaan varastoista").":</th>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa_eieri)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa_eieri/$kotiarvo_ulkomaa_eieri)."%</td>";
				}
				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
			}
			else {
				echo "<tr>
						<td class='back' colspan='$ycspan'></td>
						<th colspan='5' align='right'>".t("Veroton yhteens‰").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$arvo_eieri)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
					echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_eieri/$kotiarvo_eieri)."%</td>";
				}
				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";	
			}
			
			if ($laskurow["erikoisale"] > 0 and $kukarow['hinnat'] == 0) {
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<th colspan='5' align='right'>".t("Erikoisalennus")." $laskurow[erikoisale]%:</th>
					<td class='spec' align='right'>".sprintf("%.2f", ($arvo_eieri-$arvo)*-1)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
					echo "<td class='spec' align='right' nowrap></td>";
				}
				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
			
				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
					echo "<tr>
							<td class='back' colspan='$ycspan'></td>
							<th colspan='5' align='right'>".t("Kotimaan varastoista").":</th>
							<td class='spec' align='right' nowrap>".sprintf("%.2f",$arvo_kotimaa)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
						echo "<td class='spec' align='right'>".sprintf("%.2f",100*$kate_kotimaa/$kotiarvo_kotimaa)."%</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
		
					echo "<tr>
						<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
						<th colspan='5' align='right'>".t("Ulkomaan varastoista").":</th>
						<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa/$kotiarvo_ulkomaa)."%</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}
				else {
					echo "<tr>
							<td class='back' colspan='$ycspan'></td>
							<th colspan='5' align='right'>".t("Veroton yhteens‰").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
						echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate/$kotiarvo)."%</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";	
				}
			}
			
			 // Etsit‰‰n asiakas
			$query = "	SELECT laskunsummapyoristys
						FROM asiakas
						WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
			$asres = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($asres);
			
			if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
				$summa = round($summa ,0);
			}
			
			echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<th colspan='5' align='right'>".t("Verollinen yhteens‰").":</th>
					<td class='spec' align='right'>".sprintf("%.2f",$summa)."</td>";

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
				echo "<td class='spec' align='right'></td>";
			}
			echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
			
			
			//annetaan mahdollisuus antaa loppusumma joka jyvitet‰‰n riveille arvoosuuden mukaan
			if ($kukarow["extranet"] == "" and (
				   ($yhtiorow["salli_jyvitys_myynnissa"] == "" and $kukarow['kassamyyja'] != '')
				or ($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] != '')
				or ($yhtiorow["salli_jyvitys_myynnissa"] == "K")
				or $toim == "TARJOUS")) {

				if ($jyvsumma== '') {
					$jyvsumma='0.00';
				}
				
				if ($toim == "TARJOUS") {
					echo "<form name='valmis' action='tulostakopio.php' method='post'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='hidden' name='otunnus' value='$tilausnumero'>
							<th class='back' colspan='".(floor($ycspan/2))."' nowrap>".t("N‰yt‰ lomake").":</th>
							<td class='back' colspan='".(ceil($ycspan/2))."' nowrap>";

					echo "<select name='toim'>";
					echo "<option value='TARJOUS'>Tarjous</value>";
					echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
					echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
					echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
					echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
					echo "<option value='REKISTERIILMOITUS'>Rekisterˆinti-ilmoitus</value>";
					echo "</select><input type='submit' value='".t("N‰yt‰")."'>";
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
						<th colspan='5'>".t("Pyˆrist‰ loppusummaa").":</th>
						<td class='spec'><input type='text' size='7' name='jyvsumma' value='$jyvsumma'></td>
						<td class='spec'>$laskurow[valkoodi]</td>
						<td class='back' colspan='2'><input type='submit' value='".t("Jyvit‰")."'></td>
						</tr>
						</form>";
						
						//N‰ytet‰‰n toi edelinen summa
						if($ed_arvo != "") {
							echo "<tr>
									<td class='back' colspan='$ycspan' nowrap></td>
									<th colspan='5'><em>".t("Edellinen summa")."</em></th>
									<td class='spec'><em>$ed_arvo</em></td>
									<td class='spec'><em>$laskurow[valkoodi]</em></td>
								</tr>";
						}
			}

			echo "</table>";
		}
		else {
			$tilausok++;
		}

		// JT-rivik‰yttˆliittym‰
		if ($estetaankomyynti == '' and $muokkauslukko == "" and $rivilaskuri == 0 and $laskurow["liitostunnus"] > 0 and $toim != "TYOMAARAYS" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and $toim != "TARJOUS") {
			//katotaan eka halutaanko asiakkaan jt-rivej‰ n‰kyviin
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
					<input type='submit' value='* ".t("J‰t‰ myyntitili lep‰‰m‰‰n")." *'>
					</form></td>";

		}


		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TYOMAARAYS") {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Tyˆm‰‰r‰ys lep‰‰m‰‰n")." *'>
					</form></td>";

		}

		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TARJOUS"  and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hyv‰ksy tarjous")."'>
					</form>";


			echo "	<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYLKAATARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hylk‰‰ tarjous")."'>
					</form>";

			echo "	</td>";
		}

		echo "<td class='back' valign='top'>";

		//N‰ytet‰‰n tilaus valmis nappi
		if ($muokkauslukko == "" and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			// Jos myyj‰ myy todella pienell‰ summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
			$javalisa = "";
			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function ulkomaa_verify(){
								msg = '".t("Olet toimittamassa ulkomailla sijaitsevasta varastosta tuotteita")." $ulkomaa_kaikkiyhteensa $yhtiorow[valkoodi]! ".t("Oletko varma, ett‰ t‰m‰ on fiksua")."?';
								return confirm(msg);
						}
				</SCRIPT>";

				 $javalisa = "onSubmit = 'return ulkomaa_verify()'";
			}

			// otetaan maksuehto selville.. k‰teinen muuttaa asioita
			$query = "	select *
						from maksuehto
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maksuehtorow = mysql_fetch_array($result);

			// jos kyseess‰ on k‰teiskauppaa
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
				echo "<font class='error'>".t("VIRHE: Sis‰isell‰ laskulla ei voi olla maksusopimusta!")."</font>";
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
					<input type='hidden' name='kaikkiyhteensa' value='".sprintf('%.2f',$summa)."'>";

				if($arvo_ulkomaa != 0) {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
				}
				else {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='NO'>";	
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
								msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
								return confirm(msg);
						}
				</SCRIPT>";

			echo "</td><td align='right' class='back' valign='top'>
					<form name='valmis' action='$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='POISTA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Mit‰tˆi koko")." $otsikko *'>
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