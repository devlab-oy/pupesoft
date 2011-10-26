<?php

if (isset($_REQUEST['tulosta_maksusopimus']) and is_numeric(trim($_REQUEST['tulosta_maksusopimus']))) {
	$nayta_pdf = 1;
	$ohje = 'off';
}

if (@include("../inc/parametrit.inc"));
elseif (@include("parametrit.inc"));
else exit;

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
	if (!isset(${'ale'.$alepostfix})) ${'ale'.$alepostfix} = "";
	if (!isset(${'ale_array'.$alepostfix})) ${'ale_array'.$alepostfix} = "";
}

if (!isset($alv)) 					$alv = "";
if (!isset($alv_array)) 			$alv_array = "";
if (!isset($asiakasid)) 			$asiakasid = "";
if (!isset($asiakasOnProspekti)) 	$asiakasOnProspekti = "";
if (!isset($eta_yhtio)) 			$eta_yhtio = "";
if (!isset($from)) 					$from = "";
if (!isset($hinta)) 				$hinta = "";
if (!isset($hinta_array)) 			$hinta_array = "";
if (!isset($jarjesta)) 				$jarjesta = "";
if (!isset($jt_kayttoliittyma)) 	$jt_kayttoliittyma = "";
if (!isset($jysum)) 				$jysum = "";
if (!isset($jyvsumma)) 				$jyvsumma = "";
if (!isset($kaytiin_otsikolla)) 	$kaytiin_otsikolla = "";
if (!isset($kerayskka)) 			$kerayskka = 0;
if (!isset($keraysppa)) 			$keraysppa = 0;
if (!isset($kerayspvm)) 			$kerayspvm = "";
if (!isset($keraysvva)) 			$keraysvva = 0;
if (!isset($kpl)) 					$kpl = "";
if (!isset($kpl2)) 					$kpl2 = "";
if (!isset($kpl_array)) 			$kpl_array = "";
if (!isset($kutsuja)) 				$kutsuja = "";
if (!isset($lead)) 					$lead = "";
if (!isset($lisavarusteita)) 		$lisavarusteita = "";
if (!isset($livesearch_tee)) 		$livesearch_tee = "";
if (!isset($lopetus)) 				$lopetus = "";
if (!isset($luotunnusnippu)) 		$luotunnusnippu = "";
if (!isset($maksutapa)) 			$maksutapa = "";
if (!isset($menutila)) 				$menutila = "";
if (!isset($myos_prospektit)) 		$myos_prospektit = "";
if (!isset($myy_sarjatunnus)) 		$myy_sarjatunnus = "";
if (!isset($netto_array)) 			$netto_array = "";
if (!isset($olpaikalta)) 			$olpaikalta = "";
if (!isset($osatoimkielto)) 		$osatoimkielto = "";
if (!isset($paikka)) 				$paikka = "";
if (!isset($paikka_array)) 			$paikka_array = "";
if (!isset($perheid)) 				$perheid = "";
if (!isset($pika_paiv_merahti)) 	$pika_paiv_merahti = "";
if (!isset($pkrow)) 				$pkrow = "";
if (!isset($projektilla)) 			$projektilla = "";
if (!isset($rahtihinta)) 			$rahtihinta = "";
if (!isset($ruutulimit)) 			$ruutulimit = "";
if (!isset($smsnumero)) 			$smsnumero = "";
if (!isset($syotetty_ytunnus)) 		$syotetty_ytunnus = "";
if (!isset($tapa)) 					$tapa = "";
if (!isset($tee)) 					$tee = "";
if (!isset($tiedot_laskulta)) 		$tiedot_laskulta = "";
if (!isset($tila)) 					$tila = "";
if (!isset($tilausrivilinkki)) 		$tilausrivilinkki = "";
if (!isset($toimaika)) 				$toimaika = "";
if (!isset($toimittajan_tunnus)) 	$toimittajan_tunnus = "";
if (!isset($toimkka)) 				$toimkka = 0;
if (!isset($toimppa)) 				$toimppa = 0;
if (!isset($toimvva)) 				$toimvva = 0;
if (!isset($trivtyrow)) 			$trivtyrow = "";
if (!isset($tuotenimitys)) 			$tuotenimitys = "";
if (!isset($tuotenimitys_force)) 	$tuotenimitys_force = "";
if (!isset($tuoteno)) 				$tuoteno = "";
if (!isset($tuoteno_array)) 		$tuoteno_array = "";
if (!isset($tyojono)) 				$tyojono = "";
if (!isset($ulos)) 					$ulos = "";
if (!isset($uusitoimitus)) 			$uusitoimitus = "";
if (!isset($valitsetoimitus)) 		$valitsetoimitus = "";
if (!isset($varaosakommentti)) 		$varaosakommentti = "";
if (!isset($varasto)) 				$varasto = "";
if (!isset($variaatio_tuoteno)) 	$variaatio_tuoteno = "";
if (!isset($var_array)) 			$var_array = "";
if (!isset($tilausrivi_alvillisuus)) $tilausrivi_alvillisuus = "";
if (!isset($valitsetoimitus_vaihdarivi)) $valitsetoimitus_vaihdarivi = "";
if (!isset($saako_liitaa_laskuja_tilaukseen)) $saako_liitaa_laskuja_tilaukseen = "";
if (!isset($omalle_tilaukselle)) $omalle_tilaukselle = '';

// Setataan lopetuslinkki, jotta p��semme takaisin tilaukselle jos k�yd��n jossain muualla
$tilmyy_lopetus = "{$palvelin2}tilauskasittely/tilaus_myynti.php////toim=$toim//projektilla=$projektilla//tilausnumero=$tilausnumero//ruutulimit=$ruutulimit//tilausrivi_alvillisuus=$tilausrivi_alvillisuus//mista=$mista";

if ($lopetus != "") {
	// Lis�t��n t�m� lopetuslinkkiin
	$tilmyy_lopetus = $lopetus."/SPLIT/".$tilmyy_lopetus;
}

if (isset($tulosta_maksusopimus) and is_numeric(trim($tulosta_maksusopimus))) {
	require('tulosta_maksusopimus.inc');
	tulosta_maksusopimus($kukarow, $yhtiorow, $laskurow, $kieli);
	exit;
}

if ($livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if ($yhtiorow["livetuotehaku_tilauksella"] == "K") {
	enable_ajax();
}

if ((int) $luotunnusnippu > 0 and $tilausnumero == $kukarow["kesken"] and $kukarow["kesken"] > 0) {
	$query = "	UPDATE lasku
				SET tunnusnippu = tunnus
				where yhtio		= '$kukarow[yhtio]'
				and tunnus		= '$kukarow[kesken]'
				and tunnusnippu = 0";
	$result = pupe_query($query);

	$valitsetoimitus = $toim;
}

// Vaihdetaan tietyn projektin toiseen toimitukseen
//	HUOM! t�m� k�ytt�� aktivointia joten t�m� on oltava aika alussa!! (valinta on onchage submit rivisy�t�ss� joten noita muita paremetreja ei oikein voi passata eteenp�in..)
if ((int) $valitsetoimitus > 0) {
	$tee 			= "AKTIVOI";
	$tilausnumero 	= $valitsetoimitus;
	$from 			= "VALITSETOIMITUS";


	$query = "	SELECT tila, tilaustyyppi
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$tilausnumero'";
	$result = pupe_query($query);
	$toimrow = mysql_fetch_assoc($result);

	if ($toimrow["tila"] == "A" or (($toimrow["tila"] == "L" or $toimrow["tila"] == "N") and $toimrow["tilaustyyppi"] == "A")) {
		$toim = (strtolower($asentaja) == 'tyomaarays_asentaja' or $toim == 'TYOMAARAYS_ASENTAJA') ? "TYOMAARAYS_ASENTAJA" : "TYOMAARAYS";
	}
	elseif ($toimrow["tila"] == "L" or $toimrow["tila"] == "N") {
		if ($toim != "RIVISYOTTO" and $toim != "PIKATILAUS") $toim = "RIVISYOTTO";
	}
	elseif ($toimrow["tila"] == "T") {
		$toim = "TARJOUS";
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
elseif (in_array($valitsetoimitus, array("ENNAKKO","TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","VALMISTAVARASTOON","SIIRTOLISTA","TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO","PROJEKTI"))) {
	$uusitoimitus = $valitsetoimitus;
}

if (($kukarow["extranet"] != '' and $toim != 'EXTRANET' and $toim != 'EXTRANET_REKLAMAATIO') or ($kukarow["extranet"] == "" and ($toim == "EXTRANET" or $toim == "EXTRANET_REKLAMAATIO"))) {
	//aika j�nn� homma jos t�nne jouduttiin
	exit;
}

// aktivoidaan saatu id
if ($tee == 'AKTIVOI') {
	// katsotaan onko muilla aktiivisena
	$query = "SELECT * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
	$result = pupe_query($query);

	unset($row);

	if (mysql_num_rows($result) != 0) {
		$row = mysql_fetch_assoc($result);
	}

	if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
		echo "<font class='error'>".t("Tilaus on aktiivisena k�ytt�j�ll�")." $row[nimi]. ".t("Tilausta ei voi t�ll� hetkell� muokata").".</font><br>";

		// poistetaan aktiiviset tilaukset jota t�ll� k�ytt�j�ll� oli
		$query = "UPDATE kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = pupe_query($query);

		exit;
	}
	else {
		$query = "	UPDATE kuka
					SET kesken = '$tilausnumero'
					WHERE yhtio = '$kukarow[yhtio]' AND
					kuka = '$kukarow[kuka]' AND
					session = '$session'";
		$result = pupe_query($query);

		// N�in ostataan valita pikatilaus
		if ($toim == "RIVISYOTTO" and isset($PIKATILAUS)) {
			$toim = "PIKATILAUS";
		}
		// Jos tullaan projektille pit�� my�s aktivoida $projektilla
		elseif ($toim == "PROJEKTI") {
			$projektilla = $tilausnumero;
		}
		elseif ($toim == "VALMISTAASIAKKAALLE" and $tilausnumero != "") {
			$tyyppiquery = "select tilaustyyppi from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
			$tyyppiresult = pupe_query($tyyppiquery);

			if (mysql_num_rows($tyyppiresult) != 0) {
				$tyyppirow=mysql_fetch_assoc($tyyppiresult);

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

		//	T��ll� muutetaan yleens� vain toimitusaikoja tms..
		if ($from == "PROJEKTIKALENTERI") {
			$tee = "OTSIK";
			$query = "	SELECT liitostunnus FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$tarkres = pupe_query($query);
			$tarkrow = mysql_fetch_assoc($tarkres);
			$asiakasid = $tarkrow["liitostunnus"];
			$tiedot_laskulta = "YES";
		}
		else {
			$tee = "";
		}
	}
}

// jos ei olla postattu mit��n, niin halutaan varmaan tehd� kokonaan uusi tilaus..
if ($kukarow["extranet"] == "" and count($_POST) == 0 and ($from != "ASIAKASYLLAPITO" and $from != "LASKUTATILAUS" and $from != "VALITSETOIMITUS" and $from != "PROJEKTIKALENTERI" and $from != "POSITIOSELAIN")) {
	$tila				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow["kesken"]	= '';

	//varmistellaan ettei vanhat kummittele...
	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = pupe_query($query);
}

// Extranet keississ� asiakasnumero tulee k�ytt�j�n takaa
if ($kukarow["extranet"] != '') {
	// Haetaan asiakkaan tunnuksella
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$extra_asiakas = mysql_fetch_assoc($result);
		$ytunnus 	= $extra_asiakas["ytunnus"];
		$asiakasid 	= $extra_asiakas["tunnus"];

		if ($toim == 'EXTRANET_REKLAMAATIO') {
			$ex_tila = "C";
		}
		else {
			$ex_tila = "N";
		}

		if ($kukarow["kesken"] > 0) {
			// varmistetaan, ett� TILAUS on oikeasti kesken ja t�lle asiakkaalle
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$kukarow[kesken]'
						AND liitostunnus = '$asiakasid'
						AND tila = '$ex_tila'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$tilausnumero = $kukarow["kesken"];
			}
			else {
				$tilausnumero 		= "";
				$kukarow["kesken"]  = "";
			}
		}
		else {
			// jos asiakkaalla jostakin syyst� kesken oleva tilausnumero on kadonnut, niin haetaan "Myyntitilaus kesken" oleva tilaus aktiiviseksi
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio 	 = '{$kukarow['yhtio']}'
						AND liitostunnus = '$asiakasid'
						AND tila 		 = '$ex_tila'
						AND alatila 	 = ''
						AND laatija 	 = '{$kukarow['kuka']}'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				$kesken_row = mysql_fetch_assoc($result);
				$tilausnumero = $kukarow['kesken'] = $kesken_row["tunnus"];

				$query = "	UPDATE kuka SET
							kesken = '$tilausnumero'
							WHERE yhtio   = '{$kukarow['yhtio']}'
							AND kuka 	  = '{$kukarow['kuka']}'
							AND extranet != ''";
				$result = pupe_query($query);
			}
			else {
				$tilausnumero = "";
				$kukarow["kesken"] = "";
			}
		}
	}
	else {
		echo t("VIRHE: K�ytt�j�tiedoissasi on virhe! Ota yhteys j�rjestelm�n yll�pit�j��n.")."<br><br>";
		exit;
	}
}

//katsotaan ett� kukarow kesken, $tilausnumero ja $kukarow[kesken] stemmaavat kesken��n
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
	echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("K�y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
	exit;
}

if ((int) $valitsetoimitus_vaihdarivi > 0 and $tilausnumero == $kukarow["kesken"] and $kukarow["kesken"] > 0 and $toim != "TARJOUS") {

	$query = "	(
					SELECT tunnus
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$edtilausnumero' and tunnus = '$rivitunnus' and uusiotunnus = 0 and toimitettuaika = '0000-00-00 00:00:00'
				)
				UNION
				(
					SELECT tunnus
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$edtilausnumero' and perheid>0 and perheid='$rivitunnus' and uusiotunnus = 0 and toimitettuaika = '0000-00-00 00:00:00'
				)";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		while ($aburow = mysql_fetch_assoc($result)) {
			// Vaihdetaan rivin otunnus
			$query = "	UPDATE tilausrivi
						SET otunnus = '$valitsetoimitus_vaihdarivi'
						WHERE yhtio 		= '$kukarow[yhtio]'
						and otunnus 		= '$edtilausnumero'
						and tunnus 			= '$aburow[tunnus]'
						and uusiotunnus 	= 0
						and toimitettuaika 	= '0000-00-00 00:00:00'";
			$updres = pupe_query($query);
		}
	}

	$rivitunnus = "";
}

//jos jostain tullaan ilman $toim-muuttujaa
if ($toim == "") {
	$toim = "RIVISYOTTO";
}

//korjataan hintaa ja aleprossaa
$hinta	= str_replace(',','.',$hinta);
$kpl 	= str_replace(',','.',$kpl);

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
	${'ale'.$alepostfix} = str_replace(',','.',${'ale'.$alepostfix});
}

//Ei olla pikatilauksella, mutta ollaan jostain syyst� kuitenkin ilman asiakasta ja halutaan nyt liitt�� se
if (isset($liitaasiakasnappi) and $kukarow["extranet"] == "") {
	$tee  = "OTSIK";
	$tila = "vaihdaasiakas";
}

//Jos yll�pidossa on luotu uusi asiakas
if (isset($from) and $from == "ASIAKASYLLAPITO" and $yllapidossa == "asiakas" and $yllapidontunnus != '') {
	$asiakasid 	= $yllapidontunnus;
}

// asiakasnumero on annettu, etsit��n tietokannasta...
if (($tee == "" or ($myos_prospektit == "TRUE" and $toim == "TARJOUS")) and (($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0) or ($kukarow["extranet"] == "" and ($syotetty_ytunnus != '' or $asiakasid != '')))) {

	if (substr($ytunnus,0,1) == "�") {
		$ytunnus = $asiakasid;
	}
	else {
		$ytunnus = $syotetty_ytunnus;
	}

	$kutsuja    = "otsik.inc";
	$ahlopetus 	= $tilmyy_lopetus."//from=ASIAKASYLLAPITO";

	if (@include("inc/asiakashaku.inc"));
	elseif (@include("asiakashaku.inc"));
	else exit;

	// Ei n�ytet� tilausta jos meill� on asiakaslista ruudulla
	if ($monta != 1) {
		$tee = "SKIPPAAKAIKKI";
	}
}

//Luodaan otsikko
if (($tee == "" and (($toim == "PIKATILAUS" and ((int) $kukarow["kesken"] == 0 and ($tuoteno != '' or $asiakasid != '')) or ((int) $kukarow["kesken"] != 0 and $asiakasid != '' and $kukarow["extranet"] == "")) or ($from == "CRM" and $asiakasid != ''))) or ($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0)) {

	if (@include("tilauskasittely/luo_myyntitilausotsikko.inc"));
	elseif (@include("luo_myyntitilausotsikko.inc"));
	else exit;

	$tilausnumero = luo_myyntitilausotsikko($toim, $asiakasid, $tilausnumero, $myyjanumero, '', $kantaasiakastunnus);
	$kukarow["kesken"] = $tilausnumero;
	$kaytiin_otsikolla = "NOJOO!";
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] > 0) {

	if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
		$query  = "	SELECT lasku.*, tyomaarays.*
					FROM lasku
					JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
					WHERE lasku.tunnus = '$kukarow[kesken]'
					AND lasku.yhtio	= '$kukarow[yhtio]'
					AND lasku.tila != 'D'";
	}
	else {
		// pit�� olla: siirtolista, sis�inen ty�m��r�ys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, yll�pitosopimus, projekti
		$query 	= "	SELECT *
					FROM lasku
					WHERE tunnus = '$kukarow[kesken]'
					AND yhtio = '$kukarow[yhtio]'
					AND tila in ('G','S','C','T','V','N','E','L','0','R')
					AND (alatila != 'X' or tila = '0')";
	}
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<br><br><br>".t("VIRHE: Tilaustasi ei l�ydy tai se on mit�t�ity/laskutettu")."! ($kukarow[kesken])<br><br><br>";

		$query = "	UPDATE kuka
					SET kesken = 0
					WHERE yhtio = '$kukarow[yhtio]'
					AND kuka = '$kukarow[kuka]'";
		$result = pupe_query($query);
		exit;
	}

	$laskurow = mysql_fetch_assoc($result);

	if ($yhtiorow["tilauksen_kohteet"] == "K") {
		$query 	= "	SELECT *
					FROM laskun_lisatiedot
					where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);
		$lasklisatied_row = mysql_fetch_assoc($result);
	}

	if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0 and $yhtiorow["suoratoim_ulkomaan_alarajasumma"] > 0) {
		$yhtiorow["suoratoim_ulkomaan_alarajasumma"] = round(laskuval($yhtiorow["suoratoim_ulkomaan_alarajasumma"], $laskurow["vienti_kurssi"]),0);
	}

	if ($laskurow["toim_maa"] == "") $laskurow["toim_maa"] = $yhtiorow['maa'];

	$toimtapa_kv = t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV");
}

if ($toim == "TARJOUS" or (isset($laskurow["tilaustyyppi"]) and $laskurow["tilaustyyppi"] == "T") or $toim == "PROJEKTI") {
	// ekotetaan javascripti� jotta saadaan pdf:�t uuteen ikkunaan
	js_openFormInNewWindow();
}

if ($toim == "EXTRANET") {
	$otsikko = t("Extranet-Tilaus");
}
elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
	$otsikko = t("Ty�m��r�ys");
}
elseif ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
	$otsikko = t("Reklamaatio");
}
elseif ($toim == "VALMISTAVARASTOON") {
	$otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
	$otsikko = t("Varastosiirto");
}
elseif ($toim == "SIIRTOTYOMAARAYS") {
	$otsikko = t("Sis�inen ty�m��r�ys");
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
	$otsikko = t("Yll�pitosopimus");
}
elseif ($toim == "ENNAKKO" or $laskurow["tilaustyyppi"] == "E") {
	$otsikko = t("Ennakkotilaus");
}
else {
	$otsikko = t("Myyntitilaus");
}

//tietyiss� keisseiss� tilaus lukitaan (ei sy�tt�rivi� eik� muota muokkaa/poista-nappuloita)
$muokkauslukko = $state = "";

//	Projekti voidaan poistaa vain jos meill� ei ole sill� mit��n toimituksia
if (isset($laskurow["tunnusnippu"]) and $laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
	$query 	= "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
	$abures = pupe_query($query);
	$projektilask = (int) mysql_num_rows($abures);
}

if ($oikeurow['paivitys'] != '1' or ($toim == "MYYNTITILI" and isset($laskurow["alatila"]) and $laskurow["alatila"] == "V") or ($toim == "PROJEKTI" and $projektilask > 0) or ($toim == "TARJOUS" and $projektilla > 0) or (isset($laskurow["alatila"]) and $laskurow["alatila"] == "X")) {
	if ($laskurow["tila"] != '0') {
		$muokkauslukko 	= "LUKOSSA";
	}
	$state = "DISABLED";
}

// Hyv�ksyt��n tarjous ja tehd��n tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS" and $muokkauslukko == "") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font>";
		exit;
	}

	//Luodaan valituista riveist� suoraan normaali ostotilaus
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

	$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '', '', '', $perusta_tilaustyyppi);
	if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";

	$query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = pupe_query($query);

	//	P�ivitet��n my�s muut tunnusnipun j�senet sympatian vuoksi hyv�ksytyiksi
	$query = "SELECT tunnusnippu from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu]";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);

		$query = "UPDATE lasku SET alatila='T' where yhtio='$kukarow[yhtio]' and tunnusnippu = $row[tunnusnippu] and tunnus!='$kukarow[kesken]'";
		$result = pupe_query($query);
	}

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = pupe_query($query);

	$aika=date("d.m.y @ G:i:s", time());
	echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';
}

// Hyl�t��n tarjous
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS" and $muokkauslukko == "") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font>";
		exit;
	}

	$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = pupe_query($query);

	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
	$result = pupe_query($query);

	//Nollataan sarjanumerolinkit
	vapauta_sarjanumerot($toim, $kukarow["kesken"]);

	//	P�ivitet��n my�s muut tunnusnipun j�senet sympatian vuoksi hyl�tyiksi *** t�m�n voisi varmaan tehd� my�s kaikki kerralla? ***
	$query = "SELECT tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
	$abures = pupe_query($query);

	if (mysql_num_rows($abures) > 0) {
		while ($row = mysql_fetch_assoc($abures)) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
			$result = pupe_query($query);

			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
			$result = pupe_query($query);

			//Nollataan sarjanumerolinkit
			vapauta_sarjanumerot($toim, $row["tunnus"]);
		}
	}

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = pupe_query($query);

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

// Laitetaan myyntitili takaisin lep��m��n
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

if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K" and $tee == "kululaskut") {
	echo "<font class='head'>".t("Kululaskut")."</font><hr>";
	require('kululaskut.inc');
}

if (in_array($jarjesta, array("moveUp", "moveDown")) and $rivitunnus > 0) {

	if ($laskurow["tunnusnippu"] > 0 and $toim != "TARJOUS") {
		$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila IN ('L','G','E','V','W','N','R','A') and tunnusnippu>0";
		$result = pupe_query($query);
		$toimrow = mysql_fetch_assoc($result);

		$tunnarit = "$toimrow[tunnukset]";
	}
	else {
		$tunnarit = $kukarow["kesken"];
	}
	$query = "	SELECT jarjestys, tunnus
				FROM tilausrivin_lisatiedot
				WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus='$rivitunnus'";
	$abures = pupe_query($query);
	$aburow = mysql_fetch_assoc($abures);

	if ($jarjesta == "moveUp") {
		$ehto = "and jarjestys<$aburow[jarjestys]";
		$j = "desc";
	}
	elseif ($jarjesta == "moveDown") {
		$ehto = "and jarjestys>$aburow[jarjestys]";
		$j = "asc";
	}

	$query = "	SELECT jarjestys, tilausrivin_lisatiedot.tunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus $ehto
	 			WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tyyppi !='D' and otunnus IN ($tunnarit) and (perheid=0 or perheid=tilausrivi.tunnus)
				ORDER BY jarjestys $j
				LIMIT 1";
	$result = pupe_query($query);
	$kohderow = mysql_fetch_assoc($result);

	if ($kohderow["jarjestys"]>0 and $kohderow["tunnus"] != $rivitunnus) {
		//	Kaikki OK vaihdetaan data p�ikseen
		$query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$kohderow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$aburow[tunnus]'";
		$updres=pupe_query($query);

		$query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$aburow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kohderow[tunnus]'";
		$updres=pupe_query($query);
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! rivi� ei voi siirt��!")."</font><br>";
	}

	$tyhjenna 	= "JOO";
}

// Poistetaan tilaus
if ($tee == 'POISTA' and $muokkauslukko == "" and $kukarow["mitatoi_tilauksia"] == "") {

	// tilausta mit�t�idess� laitetaan kaikki poimitut jt-rivit takaisin omille tilauksille
	$query = "	SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'JT')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$kukarow['kesken']}'";
	$jt_rivien_muisti_res = pupe_query($query);

	if (mysql_num_rows($jt_rivien_muisti_res) > 0) {
		$jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

		while ($jt_rivien_muisti_row = mysql_fetch_assoc($jt_rivien_muisti_res)) {
			$query = "	UPDATE tilausrivi SET
						otunnus = '{$jt_rivien_muisti_row['vanha_otunnus']}',
						var = 'J'
						$jt_saldo_lisa
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$jt_rivien_muisti_row['tunnus']}'";
			$jt_rivi_res = pupe_query($query);

			echo "<font class='message'>",t("J�lkitoimitus palautettiin tilaukselle")," $jt_rivien_muisti_row[vanha_otunnus], ",t("ota yhteys asiakaspalveluun"),".</font><br><br>";
		}
	}

	// poistetaan tilausrivit, mutta j�tet��n PUUTE rivit analyysej� varten...
	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var<>'P'";
	$result = pupe_query($query);

	//Nollataan sarjanumerolinkit ja dellataan ostorivit
	vapauta_sarjanumerot($toim, $kukarow["kesken"]);

	//Poistetaan maksusuunnitelma
	$query = "DELETE from maksupositio WHERE yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
	$result = pupe_query($query);

	// Poistetaan maksupositio pointteri
	$query = "UPDATE maksupositio set uusiotunnus = 0 where yhtio = '$kukarow[yhtio]' and uusiotunnus = '$kukarow[kesken]'";
	$result = pupe_query($query);

	//Poistetaan rahtikrijat
	$query = "DELETE from rahtikirjat WHERE yhtio='$kukarow[yhtio]' and otsikkonro='$kukarow[kesken]'";
	$result = pupe_query($query);

	$query = "UPDATE lasku SET tila='D', alatila='L', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit�t�i tilauksen")." ohjelmassa tilaus_myynti.php ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = pupe_query($query);

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = pupe_query($query);

	//Poistetaan asennuskalenterimerkinn�t
	$query = "	UPDATE kalenteri SET tyyppi = 'DELETEDasennuskalenteri' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'asennuskalenteri' and liitostunnus = '$kukarow[kesken]'";
	$result = pupe_query($query);

	if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and ($toim != "TARJOUS" or ($toim == "TARJOUS" and $laskurow["tunnusnippu"] != $laskurow["tunnus"])) and $toim != "PROJEKTI") {

		$aika = date("d.m.y @ G:i:s", time());

		if ($projektilla > 0 and ($laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"])) {

			echo "<font class='message'>".t("Osatoimitus")." ($aika) $kukarow[kesken] ".t("mit�t�ity")."!</font><br><br>";

			$tilausnumero = $laskurow["tunnusnippu"];

			//	Hyp�t��n takaisin otsikolle
			echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";

			if ($projektilla > 0) {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
			}
			else {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=$toim&valitsetoimitus=$tilausnumero'>";
			}
			exit;
		}
		elseif ($toim == "TARJOUS" and $laskurow["tunnusnippu"] > 0) {

			echo "<font class='message'>".t("Tarjous")." ($aika) $kukarow[kesken] ".t("mit�t�ity")."!</font><br><br>";

			$tilausnumero = $laskurow["tunnusnippu"];

			//	Hyp�t��n takaisin otsikolle
			echo "<font class='info'>".t("Palataan tarjoukselle odota hetki..")."</font><br>";

			$query = "	SELECT tunnus
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila = 'T' and alatila != 'X'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$row[tunnus]'>";

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
			echo "<font class='message'>".t("$otsikko")." $kukarow[kesken] ".t("mit�t�ity")."!</font><br><br>";
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
		lopetus($lopetus, "META");
	}
}

//Tyhjennt��n sy�tt�kent�t
if (isset($tyhjenna)) {

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		${'ale'.$alepostfix} = "";
		${'kayttajan_ale'.$alepostfix} = "";
		${'ale_array'.$alepostfix} = "";
	}

	$alv 				= "";
	$alv_array 			= "";
	$hinta 				= "";
	$hinta_array 		= "";
	$kayttajan_alv 		= "";
	$kayttajan_hinta 	= "";
	$kayttajan_kpl 		= "";
	$kayttajan_netto 	= "";
	$kayttajan_var 		= "";
	$kerayspvm 			= "";
	$kommentti 			= "";
	$kpl 				= "";
	$kpl_array 			= "";
	$netto 				= "";
	$netto_array 		= "";
	$paikat 			= "";
	$paikka 			= "";
	$paikka_array 		= "";
	$perheid 			= 0;
	$perheid2 			= 0;
	$rivinumero 		= "";
	$rivitunnus 		= 0;
	$toimaika 			= "";
	$tuotenimitys 		= "";
	$tuoteno 			= "";
	$var 				= "";
	$variaatio_tuoteno 	= "";
	$var_array 			= "";
	$sopimuksen_lisatieto1 = "";
	$sopimuksen_lisatieto2 = "";
	$omalle_tilaukselle = "";
}

if ($tee == "VALMIS" and in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS")) and $kateinen != '' and ($kukarow["kassamyyja"] != '' or (($kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "") and $kertakassa != '')) and $kukarow['extranet'] == '') {

	if (($kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "") and isset($kertakassa) and $kertakassa == 'EI_KASSAMYYNTIA') {
		$kassamyyja_kesken 	= "";
		$kateisohitus		= "X";
	}
	elseif (!isset($kassamyyja_kesken) and !isset($seka)) {

		$query_maksuehto = "	SELECT *
								FROM maksuehto
							 	WHERE yhtio = '$kukarow[yhtio]'
								and kateinen != ''
								and kaytossa = ''
								and (sallitut_maat = '' or sallitut_maat like '%$laskurow[maa]%')";
		$maksuehtores = pupe_query($query_maksuehto);

		if (mysql_num_rows($maksuehtores) > 1) {
			echo "<table><tr><th>".t("Maksutapa").":</th>";

			while ($maksuehtorow = mysql_fetch_assoc($maksuehtores)) {
				echo "<form action='' method='post'>";
				echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='mista' value='$mista'>";
				echo "<input type='hidden' name='tee' value='VALMIS'>";
				echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
				echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
				echo "<input type='hidden' name='kateinen' value='$kateinen'>";
				echo "<input type='hidden' name='valittu_kopio_tulostin' value='$valittu_kopio_tulostin'>";
				echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<td><input type='submit' value='".t_tunnus_avainsanat($maksuehtorow, "teksti", "MAKSUEHTOKV")."'></td>";
				echo "</form>";
			}

			echo "<form action='' method='post'>";
			echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='mista' value='$mista'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
			echo "<input type='hidden' name='seka' value='X'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='kateinen' value='$kateinen'>";
			echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
			echo "<td><input type='submit' value='".t("Useita maksutapoja")."'></td>";
			echo "</form>";

			echo "<form action='' method='post'>";
			echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='mista' value='$mista'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='kateinen' value='$kateinen'>";
			echo "<input type='hidden' name='kateisohitus' value='X'>";
			echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
			echo "<td><input type='submit' value='".t("Ei viel� laskuteta, siirr� tilaus ker�ykseen")."'></td>";
			echo "</form></tr>";

			echo "</table>";

			if (@include("inc/footer.inc"));
			elseif (@include("footer.inc"));
			exit;
		}
		else {
			// Menn��n laskun maksuehdolla, jos yhti�ll� ei ole useampia k�teismaksuehtoja
			$kassamyyja_kesken 	= "ei";
			$maksutapa 			= $laskurow["maksuehto"];
			$kateisohitus		= "";
		}
	}
	elseif ($kassamyyja_kesken == 'ei' and $seka == 'X') {
		$query_maksuehto = " SELECT *
							 FROM maksuehto
							 WHERE yhtio='$kukarow[yhtio]' and kateinen != '' and kaytossa = '' and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$laskurow[maa]%')";
		$maksuehtores = pupe_query($query_maksuehto);

		$maksuehtorow = mysql_fetch_assoc($maksuehtores);

		echo "<table><form action='' name='laskuri' method='post'>";

		echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='mista' value='$mista'>";
		echo "<input type='hidden' name='tee' value='VALMIS'>";
		echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
		echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
		echo "<input type='hidden' name='valittu_kopio_tulostin' value='$valittu_kopio_tulostin'>";
		echo "<input type='hidden' name='kateinen' value='$kateinen'>";
		echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
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

		echo "<tr><td>".t("K�teisell�")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";

		echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$laskurow[valkoodi]</td></tr>";
		echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyv�ksy")."' disabled></td></tr>";

		echo "</form><br><br>";

		$formi = "laskuri";
		$kentta = "kateismaksu";

		exit;
	}
}

if ($tee == 'PALAUTA_SIIVOTUT' and $kukarow['extranet'] != '') {
	$query = "	SELECT tilausrivi.tuoteno, tilausrivi.tilkpl, tilausrivi.kommentti, tilausrivi.tunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '$tilausnumero'
				AND tilausrivi.tyyppi != 'D'";
	$palauta_siivotut_res = pupe_query($query);

	while ($palauta_siivotut_row = mysql_fetch_assoc($palauta_siivotut_res)) {
		$tuoteno_array[] = $palauta_siivotut_row['tuoteno'];
		$kpl_array[$palauta_siivotut_row['tuoteno']] = $palauta_siivotut_row['tilkpl'];
		$kommentti_array[$palauta_siivotut_row['tuoteno']] = $palauta_siivotut_row['kommentti'];

		$query = "	UPDATE tilausrivi SET
					tyyppi = 'D'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$palauta_siivotut_row['tunnus']}'";
		$palauta_res = pupe_query($query);
	}

	$tee = '';
}

if ($tee == 'VALMIS' and $kukarow['extranet'] != '') {
	$query = "	SELECT tilausrivi.varattu
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '$tilausnumero'
				AND tilausrivi.tyyppi != 'D'";
	$varattu_check_res = pupe_query($query);
	$varattu_nollana = false;

	while ($varattu_check_row = mysql_fetch_assoc($varattu_check_res)) {
		if ($varattu_check_row['varattu'] == 0) $varattu_nollana = true;
	}

	if ($varattu_nollana) $tee = '';
}

if ($tee == "VALMIS" and $kassamyyja_kesken == 'ei' and ($kukarow["kassamyyja"] != '' or $kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "") and $kukarow['extranet'] == '' and $kateisohitus == "") {

	if ($kertakassa == '') {
		$kertakassa = $kukarow["kassamyyja"];
	}

	$query_maksuehto = "UPDATE lasku
						SET maksuehto 	= '$maksutapa',
						kassalipas 		= '$kertakassa'
						WHERE yhtio	= '$kukarow[yhtio]'
						AND tunnus	= '$kukarow[kesken]'";
	$maksuehtores = pupe_query($query_maksuehto);
}

// Tilaus valmis
if ($tee == "VALMIS" and ($muokkauslukko == "" or $toim == "PROJEKTI")) {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font><br>";
		exit;
	}

	// Tsekataan jos ollaan tehty asiakkaallevalmistus jossa ei ole yht��n valmistettavaa rivi�
	$msiirto = "";

	if ($toim == "VALMISTAASIAKKAALLE") {
		$query = "	SELECT yhtio
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$kukarow[kesken]'
					and tyyppi in ('W','M','V')
					and varattu > 0";
		$sres  = pupe_query($query);

		if (mysql_num_rows($sres) == 0) {
			echo "<font class='message'> ".t("Ei valmistettavaa. Valmistus siirrettiin myyntipuolelle")."! </font><br>";

			if ($laskurow["alatila"] == "") {
				$utila = "N";
				$atila = "";
			}
			elseif ($laskurow["alatila"] == "J") {
				$utila = "N";
				$atila = "A";
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
			$result = pupe_query($query);

			$msiirto = "MYYNTI";
		}
	}

	if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
		// Tulostetaan tarjous
		if (count($komento) == 0) {
			echo "<font class='head'>".t("Tarjous").":</font><hr><br>";

			$otunnus = $tilausnumero;
			$tulostimet[0] = "Tarjous";
			require("inc/valitse_tulostin.inc");
		}

		require_once ('tulosta_tarjous.inc');
		tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli,  $tee, '', $verolliset_verottomat_hinnat, $naytetaanko_rivihinta);

		$query = "UPDATE lasku SET alatila='A' where yhtio='$kukarow[yhtio]' and alatila='' and tunnus='$kukarow[kesken]'";
		$result = pupe_query($query);

		// Meill� voi olla versio..
		if ($laskurow["tunnusnippu"] > 0) {
			$result = pupe_query($query);

			$query  = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tunnus <= '$laskurow[tunnus]' and tila='T'";
			$result = pupe_query($query);

			$tarjous = $laskurow["tunnusnippu"]."/".mysql_num_rows($result);
		}
		else {
			$tarjous = $laskurow["tunnus"];
		}

		//	Tarkastetaan onko k�yt�ss� tarjousseuranta vai, jos on n�it� muistutuksia ei kirjata..
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'raportit/tilaushakukone.php'
					and alanimi = 'TARJOUSHAKUKONE'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {

			$mkk = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
			$mhh = " 10:00:00";

			kalenteritapahtuma("Muistutus", "Tarjous asiakkaalle", "Muista tarjous $tarjous\n\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]", $laskurow["liitostunnus"], "K", $lasklisatied_row["yhteyshenkilo_tekninen"], $laskurow["tunnus"], "'".$mkk.$mhh."'");
		}

		// Tilaus ei en�� kesken...
		$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = pupe_query($query);

	}
	elseif ($kukarow["extranet"] == "" and $toim == "SIIRTOTYOMAARAYS") {
		// Sis�inen ty�m��r�ys valmis
		require("tyomaarays/tyomaarays.inc");
	}
	elseif ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO")) {
		// Ty�m��r�ys valmis
		require("tyomaarays/tyomaarays.inc");
	}
	elseif ($kukarow["extranet"] == "" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") and $msiirto == "") {
		// Siirtolista, myyntitili, valmistus valmis
		require ("tilaus-valmis-siirtolista.inc");
	}
	elseif ($toim == "PROJEKTI") {
		// Projekti, t�ll� ei ole mit��n rivej� joten nollataan vaan muuttujat
		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';
	}
	elseif ($toim == "EXTRANET_REKLAMAATIO") {
		$query  = "	UPDATE lasku
					SET alatila = 'A'
					where yhtio = '$kukarow[yhtio]'
					and tunnus = '$kukarow[kesken]'
					and tila = 'C'
					and alatila = ''";
		$result = pupe_query($query);

		// tilaus ei en�� kesken...
		$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = pupe_query($query);

		if ($yhtiorow['reklamaation_kasittely'] == 'U') {
			$oslapp = "email";
			require("osoitelappu_pdf.inc");
		}
	}
	// Myyntitilaus valmis
	else {
		//Jos k�ytt�j� on extranettaaja ja h�n ostellut tuotteita useista eri maista niin laitetaan tilaus holdiin
		if ($kukarow["extranet"] != "" and $toimitetaan_ulkomaailta == "YES" and $kukarow["taso"] != 3) {
			$kukarow["taso"] = 2;
		}

		//katotaan onko asiakkaalla yli 30 p�iv�� vanhoja maksamattomia laskuja
		if ($kukarow['extranet'] != '' and ($kukarow['saatavat'] == 0 or $kukarow['saatavat'] == 2)) {
			$saaquery =	"SELECT
						lasku.ytunnus,
						sum(if (TO_DAYS(NOW())-TO_DAYS(erpcm) > 30, summa-saldo_maksettu, 0)) dd
						FROM lasku use index (yhtio_tila_mapvm)
						WHERE tila = 'U'
						AND alatila = 'X'
						AND mapvm = '0000-00-00'
						AND erpcm != '0000-00-00'
						AND lasku.ytunnus = '$laskurow[ytunnus]'
						AND lasku.yhtio = '$kukarow[yhtio]'
						GROUP BY 1
						ORDER BY 1";
			$saaresult = pupe_query($saaquery);
			$saarow = mysql_fetch_assoc($saaresult);

			//ja jos on niin ne siirret��n tilaus holdiin
			if ($saarow['dd'] > 0) {
				$kukarow["taso"] = 2;
			}
		}

		// Extranetk�ytt�j� jonka tilaukset on hyv�ksytett�v� meid�n myyjill�
		if ($kukarow["extranet"] != "" and $kukarow["taso"] == 2) {
			$query  = "	UPDATE lasku set
						tila = 'N',
						alatila='F'
						where yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = pupe_query($query);

			// tilaus ei en�� kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = pupe_query($query);

		}
		else {

			//Luodaan valituista riveist� suoraan normaali ostotilaus
			if (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) and $yhtiorow["tee_osto_myyntitilaukselta"] != '') {
				require("tilauksesta_ostotilaus.inc");

				//	Jos halutaan tehd� tilauksesta ostotilauksia, niin tehd��n kaikista ostotilaus
				if ($tee_osto != "") {
					$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'KAIKKI');

					// P�ivitet��n tilaukselle, ett� sit� ei osatoimiteta jos koko tilauksesta tehtiin ostotilaus
					$query  = "	UPDATE lasku set
								osatoimitus = 'o'
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$kukarow[kesken]'";
					$result = pupe_query($query);
				}
				else {
					$tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"],'T');
					$tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"],'U');
				}

				if ($tilauksesta_ostotilaus != '') echo "$tilauksesta_ostotilaus<br><br>";
			}

			if ($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '') {
				//	Voimme my�s tehd� tilaukselta suoraan valmistuksia!
				require("tilauksesta_valmistustilaus.inc");
				$tilauksesta_valmistustilaus = tilauksesta_valmistustilaus($kukarow["kesken"]);

				if ($tilauksesta_valmistustilaus != '') echo "$tilauksesta_valmistustilaus<br><br>";
			}

			// katsotaan ollaanko tehty JT-supereita..
			require("jt_super.inc");
			$jt_super = jt_super($kukarow["kesken"]);

			if ($kukarow["extranet"] != "") {
				echo "$jt_super<br><br>";

				//Pyydet��n tilaus-valmista olla echomatta mit��n
				$silent = "SILENT";
			}

			// tulostetaan l�hetteet ja tilausvahvistukset tai sis�inen lasku..
			require("tilaus-valmis.inc");
		}
	}

	// ollaan k�sitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
	if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $toim != "TARJOUS") {

		$aika=date("d.m.y @ G:i:s", time());
		echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

		if ($projektilla > 0 and $laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"]) {
			$tilausnumero = $laskurow["tunnusnippu"];

			//	P�ivite��n aina my�s projektin aktiiviseksi jos se on ollut kesken
			$query = "	UPDATE lasku SET
							alatila = 'A'
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tunnusnippu > 0 and tila = 'R' and alatila= ''";
			$updres = pupe_query($query);

			//	Hyp�t��n takaisin otsikolle
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

			if (($kukarow["kassamyyja"] != '' or $kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "") and $kateinen != '' and $kukarow['extranet'] == '' and $kateisohitus == "") {
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
					$yhteensa_teksti = t("Yhteens�");
				}
				else {
					$yhteensa_teksti = t("K�teinen");
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
			if ($toim == 'EXTRANET_REKLAMAATIO') {
				echo "<font class='head'>$otsikko</font><hr><br><br>";
				echo "<font class='message'>".t("Reklamaatio valmis. Palaamme asiaan")."!</font><br><br>";
			}
			else {
				echo "<font class='head'>$otsikko</font><hr><br><br>";
				echo "<font class='message'>".t("Tilaus valmis. Kiitos tilauksestasi")."!</font><br><br>";
			}
			$tee = "SKIPPAAKAIKKI";
		}
	}

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		lopetus($lopetus, "META");
	}
}

if ($kukarow["extranet"] == "" and ((($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and ($tee == "VAHINKO" or $tee == "LEPAA")) or ($toim == "REKLAMAATIO" and $tee == "LEPAA" and $yhtiorow['reklamaation_kasittely'] != 'U'))) {
	require("tyomaarays/tyomaarays.inc");
}

if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $tee == "LEPAA" and $yhtiorow['reklamaation_kasittely'] == 'U') {

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
	$result = pupe_query($query);

	echo "<font class='message'>".t("Reklamaatio: %s siirretty lep��m��n", '', $tilausnumero).".</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		lopetus($lopetus, "META");
	}
}

if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $tee == "ODOTTAA" and $yhtiorow['reklamaation_kasittely'] == 'U') {
	// Reklamaatio p�ivitet��n tilaan 'odottaa tuotteita'
	$query = "	UPDATE lasku set
				alatila = 'A'
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$tilausnumero'
				AND tila = 'C'";
	$result = pupe_query($query);

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
	$result = pupe_query($query);

	echo "<font class='message'>".t("Reklamaatio: %s siirretty odottamaan tuotteita", '', $tilausnumero).".</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		lopetus($lopetus, "META");
	}
}

if ($kukarow["extranet"] == "" and $toim == 'REKLAMAATIO' and $tee == 'VASTAANOTTO' and $yhtiorow['reklamaation_kasittely'] == 'U') {
	// Joka tarkoittaa ett� "Reklamaatio on vastaanotettu
	// t�m�n j�lkeen kun seuraavassa vaiheessa tullaan niin "Tulostetaan Purkulista"
	$query = "	UPDATE lasku set
				alatila = 'B'
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus 	= '$tilausnumero'
				AND tila 	= 'C'
				AND alatila = 'A'";
	$result = pupe_query($query);

	// katsotaan onko tilausrivit Unikko-j�rjestelm��n
	$query = "SELECT hyllyalue, hyllynro FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$tilausnumero}'";
	$varasto_chk_res = pupe_query($query);

	while ($varasto_chk_row = mysql_fetch_assoc($varasto_chk_res)) {
		$varasto_chk = kuuluukovarastoon($varasto_chk_row['hyllyalue'], $varasto_chk_row['hyllynro']);

		$query = "	SELECT ulkoinen_jarjestelma
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$varasto_chk}'";
		$ulkoinen_jarjestelma_res = pupe_query($query);
		$ulkoinen_jarjestelma_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

		if ($ulkoinen_jarjestelma_row['ulkoinen_jarjestelma'] == 'U') {

			$query = "UPDATE lasku SET rahtivapaa = 'X' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnumero}'";
			$rahtivapaa_upd_res = pupe_query($query);

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnumero}'";
			$laskurow_edi_res = pupe_query($query);
			$laskurow_edi = mysql_fetch_assoc($laskurow_edi_res);

			$query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow_edi['liitostunnus']}'";
			$asiakas_chk_res = pupe_query($query);
			$asiakas_chk_row = mysql_fetch_assoc($asiakas_chk_res);

			$query = "SELECT herminator FROM toimitustapa WHERE yhtio = '{$kukarow['yhtio']}' AND selite = '{$laskurow_edi['toimitustapa']}'";
			$toimitustapa_chk_res = pupe_query($query);
			$toimitustapa_chk_row = mysql_fetch_assoc($toimitustapa_chk_res);

			$query = "	SELECT *
						FROM laskun_lisatiedot
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$laskurow_edi['tunnus']}'";
			$lisatiedot_result = pupe_query($query);
			$laskun_lisatiedot = mysql_fetch_assoc($lisatiedot_result);

			$params = array(
				'laskurow' => $laskurow_edi,
				'lisatiedot' => $laskun_lisatiedot,
				'asiakasrow' => $asiakas_chk_row,
				'tilaustyyppi' => 3,
				'toimitustapa' => $toimitustapa_chk_row['herminator'],
				'toim' => $toim,
			);

			require('tilauskasittely/editilaus_out_futur.inc');

			if (editilaus_out_futur($params) === 0) {

				$query = "	UPDATE lasku SET
							tila = 'C',
							alatila = 'C',
							lahetepvm = now()
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tilausnumero}'";
				$upd_res = pupe_query($query);
			}

			break;
		}
	}

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
	$result = pupe_query($query);

	echo "<font class='message'>".t("Reklamaatio: %s kuitattu vastaanotetuksi", '', $tilausnumero).".</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';

	if ($kukarow["extranet"] == "" and $lopetus != '') {
		lopetus($lopetus, "META");
	}
}

//Voidaan tietyiss� tapauksissa kopsata t�st� suoraan uusi tilaus
if ($uusitoimitus != "") {

	if ($uusitoimitus == "VALMISTAVARASTOON" or $valitsetoimitus == "VALMISTAASIAKKAALLE") {
		$aquery = "	SELECT valmistukset.tunnus
					FROM lasku
					JOIN lasku valmistukset ON valmistukset.yhtio=lasku.yhtio and valmistukset.tunnusnippu=lasku.tunnusnippu and valmistukset.tila IN ('W','V')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus='$tilausnumero' and lasku.tunnusnippu>0";
		$ares = pupe_query($aquery);
		if (mysql_num_rows($ares) > 0) {
			$arow = mysql_fetch_assoc($ares);
			$kopioitava_otsikko = $arow["tunnus"];
		}
	}
	else {
		$kopioitava_otsikko = $laskurow["tunnusnippu"];
	}

	if ($kopioitava_otsikko > 0) {
		$toim 				= $uusitoimitus;
		$asiakasid 			= $laskurow["liitostunnus"];
		$tee 				= "OTSIK";
		$tiedot_laskulta	= "YES";
	}
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or ($toim != "PIKATILAUS" and !isset($laskurow["liitostunnus"])))) {

	//T�m� jotta my�s rivisy�t�n alkuhomma toimisi
	$tee = "OTSIK";

	if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
		require("otsik_siirtolista.inc");
	}
	else {
		require('otsik.inc');
	}

	//T�ss� halutaan jo hakea uuden tilauksen tiedot
	if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
		$query  = "	SELECT lasku.*, tyomaarays.*
					FROM lasku
					JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
					WHERE lasku.tunnus = '$kukarow[kesken]'
					AND lasku.yhtio	= '$kukarow[yhtio]'
					AND lasku.tila != 'D'";
	}
	else {
		// pit�� olla: siirtolista, sis�inen ty�m��r�ys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, yll�pitosopimus, projekti
		$query 	= "	SELECT *
					FROM lasku
					WHERE tunnus = '$kukarow[kesken]'
					AND yhtio = '$kukarow[yhtio]'
					AND tila in ('G','S','C','T','V','N','E','L','0','R')
					AND (alatila != 'X' or tila = '0')";
	}
	$result = pupe_query($query);
	$laskurow = mysql_fetch_assoc($result);

	if ($yhtiorow["tilauksen_kohteet"] == "K") {
		$query 	= "	SELECT *
					from laskun_lisatiedot
					where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= pupe_query($query);
		$lasklisatied_row  = mysql_fetch_assoc($result);
	}

	if ($toim == "ENNAKKO") {
		$toim = "RIVISYOTTO";
	}

	$kaytiin_otsikolla = "NOJOO!";
}

//lis�t��n rivej� tiedostosta
if ($tee == 'mikrotila' or $tee == 'file') {

	if ($kukarow["extranet"] == "" and $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
		require('mikrotilaus_siirtolista.inc');
	}
	else {
		require('mikrotilaus.inc');
	}

	if ($tee == 'Y') {
		$tee = "";
	}
}

if ($tee == 'mikrotila_matriisi' or $tee == 'file_matriisi') {

	require('mikrotilaus_matriisi.inc');

	if ($tee == 'Y') {
		$tee = "";
	}
}

// Tehd��n rahoituslaskuelma
if ($tee == 'osamaksusoppari') {
	require('osamaksusoppari.inc');
}

// Tehd��n vakuutushakemus
if ($tee == 'vakuutushakemus') {
	require('vakuutushakemus.inc');
}

// siirret��n tilauksella olevat tuotteet asiakkaan asiakashinnoiksi
if ($tee == "tuotteetasiakashinnastoon" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","TYOMAARAYS","PROJEKTI"))) {

	$query = "	SELECT tilausrivi.*,
				if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara
				FROM tilausrivi
				JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$tilausnumero}'
				AND tilausrivi.tyyppi != 'D'
				AND tilausrivi.var 	  != 'P'";
	$result = pupe_query($query);

	while ($tilausrivi = mysql_fetch_assoc($result)) {

		$hintapyoristys_echo = $tilausrivi["hinta"] * generoi_alekentta_php($tilausrivi, 'M', 'kerto');

		$query = "	SELECT *
					FROM asiakashinta
					where yhtio	 = '$kukarow[yhtio]'
					and tuoteno	 = '$tilausrivi[tuoteno]'
					and asiakas	 = '$laskurow[liitostunnus]'
					and hinta	 = round($hintapyoristys_echo * $tilausrivi[myyntihinta_maara], $yhtiorow[hintapyoristys])
					and valkoodi = '$laskurow[valkoodi]'";
		$chk_result = pupe_query($query);

		if (mysql_num_rows($chk_result) == 0) {
			$query = "	INSERT INTO asiakashinta SET
						yhtio		= '$kukarow[yhtio]',
						tuoteno		= '$tilausrivi[tuoteno]',
						asiakas		= '$laskurow[liitostunnus]',
						hinta		= round($hintapyoristys_echo * $tilausrivi[myyntihinta_maara], $yhtiorow[hintapyoristys]),
						valkoodi	= '$laskurow[valkoodi]',
						alkupvm		= now(),
						laatija		= '$kukarow[kuka]',
						luontiaika	= now(),
						muuttaja	= '$kukarow[kuka]',
						muutospvm	= now()";
			$insert_result = pupe_query($query);

			echo t("Lis�ttin tuote")." $tilausrivi[tuoteno] ".t("asiakkaan hinnastoon hinnalla").": ".hintapyoristys($hintapyoristys_echo)." $laskurow[valkoodi]<br>";
		}
		else {
			echo t("Tuote")." $tilausrivi[tuoteno] ".t("l�ytyi jo asiakashinnastosta").": ".hintapyoristys($hintapyoristys_echo)." $laskurow[valkoodi]<br>";
		}
		echo "<br>";
	}

	$tee = "";
}

if ($kukarow["extranet"] == "" and $tee == 'jyvita') {
	require("jyvita_riveille.inc");
}

//Lis�t��n t�n asiakkaan valitut JT-rivit t�lle tilaukselle
if (($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $muokkauslukko == "") or (($yhtiorow['jt_automatiikka'] == 'X' or $yhtiorow['jt_automatiikka'] == 'W') and (int) $kukarow['kesken'] != 0 and $kaytiin_otsikolla == "NOJOO!" and ($tee == '' or $tee == 'OTSIK') and $toim == 'EXTRANET')) {
	$tilaus_on_jo 	= "KYLLA";

	// Halutaan poimia heti kaikki jt-rivit extranet-tilauksille ensimm�isell� kerralla
	if (($yhtiorow['jt_automatiikka'] == 'X' or $yhtiorow['jt_automatiikka'] == 'W') and (int) $kukarow['kesken'] != 0 and $kaytiin_otsikolla == "NOJOO!" and ($tee == '' or $tee == 'OTSIK') and $toim == 'EXTRANET') {

		if (isset($laskurow["varasto"]) and (int) $laskurow["varasto"] > 0) {
			$varasto = array((int) $laskurow["varasto"]);
		}
		elseif (isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0) {
			$varasto = explode(",", $kukarow["varasto"]);
		}
		else {
			$asiakasmaa = $laskurow["toim_nimi"] == "" ? $laskurow["maa"] : $laskurow["toim_maa"];

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						AND (varastopaikat.sallitut_maat like '%$asiakasmaa%' or varastopaikat.sallitut_maat = '')";
			$vtresult = pupe_query($query);
			$vtrow = mysql_fetch_assoc($vtresult);

			$varasto = $vtrow['tunnukset'];
		}

		jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varasto, '', "tosi_automaaginen", "JATKA", "automaattinen_poiminta");

		$tyhjenna 	= "JOO";
		$tee 		= "";
	}
	else {
		require("jtselaus.php");

		$tyhjenna 	= "JOO";
		$tee 		= "";
	}
}

// n�ytet��n tilaus-ruutu...
if ($tee == '') {
	$focus = "tuotenumero";
	$formi = "tilaus";

	echo "<font class='head'>$otsikko</font><hr>";

	//katsotaan ett� kukarow kesken ja $kukarow[kesken] stemmaavat kesken��n
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("K�y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").". $tilausnumero / $kukarow[kesken]<br><br><br>";
		exit;
	}
	if ($kukarow['kesken'] != '0') {
		$tilausnumero = $kukarow['kesken'];
	}

	// T�ss� p�ivitet��n 'pikaotsikkoa' jos kenttiin on jotain sy�tetty ja arvoja vaihdettu
	if ($kukarow["kesken"] > 0 and (
		(isset($toimitustapa) and strpos($toimitustapa, "###") === FALSE and $toimitustapa != '' and $toimitustapa != $laskurow["toimitustapa"]) or
		(isset($toimitustapa) and strpos($toimitustapa, "###") !== FALSE and $toimitustapa != '' and $toimitustapa != $laskurow["toimitustapa"]."###".$laskurow["toimitustavan_lahto"]) or
		(isset($viesti) and $viesti != $laskurow["viesti"]) or
		(isset($tilausvahvistus) and $tilausvahvistus != $laskurow["tilausvahvistus"]) or
		(isset($myyjanro) and $myyjanro > 0) or
		(isset($myyja) and $myyja > 0 and $myyja != $laskurow["myyja"]) or
		(isset($maksutapa) and $maksutapa != ''))) {

		if ((int) $myyjanro > 0) {
			$apuqu = "	SELECT *
						from kuka use index (yhtio_myyja)
						where yhtio = '$kukarow[yhtio]'
						and myyja = '$myyjanro'
						AND myyja > 0";
			$meapu = pupe_query($apuqu);

			if (mysql_num_rows($meapu)==1) {
				$apuro = mysql_fetch_assoc($meapu);
				$myyja = $apuro['tunnus'];

				$pika_paiv_myyja = " myyja = '$myyja', ";
			}
			elseif (mysql_num_rows($meapu)>1) {
				echo "<font class='error'>".t("Sy�tt�m�si myyj�numero")." $myyjanro ".t("l�ytyi usealla k�ytt�j�ll�")."!</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Sy�tt�m�si myyj�numero")." $myyjanro ".t("ei l�ytynyt")."!</font><br><br>";
			}
		}
		elseif ((int) $myyja > 0) {
			$pika_paiv_myyja = " myyja = '$myyja', ";
		}

		if (strpos($toimitustapa, "###") !== FALSE) {
			list($toimitustapa, $toimitustavan_lahto) = explode("###", $toimitustapa);
		}
		else {
			$toimitustavan_lahto = "";
		}

		if ($maksutapa != '') {
			$laskurow["maksuehto"] = $maksutapa;
		}

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus  = '$laskurow[maksuehto]'";
		$meapu = pupe_query($apuqu);

		$kassalipas = "";

		if (mysql_num_rows($meapu) == 1 and $toimitustapa != '') {
			$meapurow = mysql_fetch_assoc($meapu);

			// jos kyseess� oli k�teinen
			if ($meapurow["kateinen"] != "") {
				// haetaan toimitustavan tiedot tarkastuksia varten
				$apuqu2 = "	SELECT *
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							AND selite  = '$toimitustapa'";
				$meapu2 = pupe_query($apuqu2);
				$meapu2row = mysql_fetch_assoc($meapu2);

				// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee j�rjestyksess� ekan
				if ($meapu2row["nouto"] == "") {
					$apuqu = "	SELECT *
								FROM toimitustapa
								WHERE yhtio = '$kukarow[yhtio]'
								AND nouto != ''
								ORDER BY jarjestys
								LIMIT 1";
					$meapu = pupe_query($apuqu);
					$apuro = mysql_fetch_assoc($meapu);

					$toimitustapa = $apuro['selite'];

					echo "<font class='error'>".t("Toimitustapa on oltava nouto, koska maksuehto on k�teinen")."!</font><br><br>";
				}

				$kassalipas = $kukarow["kassamyyja"];
			}
		}

		if ($kukarow["extranet"] != "") {
			$apuqu2 = "SELECT merahti from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
			$meapu2 = pupe_query($apuqu2);
			$meapu2row = mysql_fetch_assoc($meapu2);

			$pika_paiv_merahti = " kohdistettu = '$meapu2row[merahti]', ";
		}

		$query  = "	UPDATE lasku SET
					toimitustapa		= '$toimitustapa',
					toimitustavan_lahto	= '$toimitustavan_lahto',
					viesti 				= '$viesti',
					tilausvahvistus 	= '$tilausvahvistus',
					$pika_paiv_merahti
					$pika_paiv_myyja
					kassalipas 			= '$kassalipas',
					maksuehto			= '$laskurow[maksuehto]'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$kukarow[kesken]'";
		$result = pupe_query($query);

		//Haetaan laskurow uudestaan
		if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
			$query  = "	SELECT lasku.*, tyomaarays.*
						FROM lasku
						JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
						WHERE lasku.tunnus = '$kukarow[kesken]'
						AND lasku.yhtio	= '$kukarow[yhtio]'
						AND lasku.tila != 'D'";
		}
		else {
			// pit�� olla: siirtolista, sis�inen ty�m��r�ys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, yll�pitosopimus, projekti
			$query 	= "	SELECT *
						FROM lasku
						WHERE tunnus = '$kukarow[kesken]'
						AND yhtio = '$kukarow[yhtio]'
						AND tila in ('G','S','C','T','V','N','E','L','0','R')
						AND (alatila != 'X' or tila = '0')";
		}
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<br><br><br>".t("VIRHE: Tilaustasi ei l�ydy tai se on mit�t�ity/laskutettu")."! ($kukarow[kesken])<br><br><br>";

			$query = "	UPDATE kuka
						SET kesken = 0
						WHERE yhtio = '$kukarow[yhtio]'
						AND kuka = '$kukarow[kuka]'";
			$result = pupe_query($query);
			exit;
		}

		$laskurow = mysql_fetch_assoc($result);

		if ($yhtiorow["tilauksen_kohteet"] == "K") {
			$query 	= "	SELECT *
						from laskun_lisatiedot
						where otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
			$result  	= pupe_query($query);
			$lasklisatied_row  = mysql_fetch_assoc($result);
		}
	}

	if ((int) $lead != 0) {
		$query  = "	UPDATE kalenteri SET
					otunnus = 0
					WHERE yhtio	= '$kukarow[yhtio]'
					and tyyppi 	= 'Lead'
					and otunnus	= '$kukarow[kesken]'";
		$result = pupe_query($query);

		if ((int) $lead > 0) {
			$query  = "	UPDATE kalenteri SET
						otunnus = '$kukarow[kesken]'
						WHERE yhtio	= '$kukarow[yhtio]'
						and tyyppi 	= 'Lead'
						and tunnus	= '$lead'";
			$result = pupe_query($query);
		}
	}

 	// jos asiakasnumero on annettu
	if ($laskurow["liitostunnus"] > 0) {
		if ($yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO","PROJEKTI"))) {
			$jarjlisa = "<td class='back' width='10px'>&nbsp;</td>";
		}
		else {
			$jarjlisa = "";
		}

		if ($kukarow["extranet"] == "") {
			echo "	<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='tee' value='OTSIK'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tiedot_laskulta' value='YES'>
					<input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
					<input type='hidden' name='tyojono' value='$tyojono'>
					<input type='submit' ACCESSKEY='m' value='".t("Muuta Otsikkoa")."'>
					</form>";

			if ($toim == 'PIKATILAUS' or $toim == 'RIVISYOTTO') {
				if ($toim == 'PIKATILAUS') {
					$vaihdatoim = 'RIVISYOTTO';
					$vaihdaselite = t("Rivisy�tt��n");
				}
				else {
					$vaihdatoim = 'PIKATILAUS';
					$vaihdaselite = t("Pikatilaukseen");
				}

				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$vaihdatoim'>
						<input type='hidden' name='tee' value='AKTIVOI'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value='$mista'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='submit' value='".t("Vaihda")." $vaihdaselite'>
						</form>";
			}
		}

		// otetaan maksuehto selville.. jaksotus muuttaa asioita
		$query = " 	SELECT *
					from maksuehto
					where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result)==1) {
			$maksuehtorow = mysql_fetch_assoc($result);

			if ($maksuehtorow['jaksotettu']!='') {
				echo "	<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value='$mista'>
						<input type='hidden' name='tee' value='MAKSUSOPIMUS'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='Submit' value='".t("Maksusuunnitelma")."'>
						</form>";
			}
		}

		//	T�m� koko toiminto pit�nee taklata paremmin esim. perheill�..
		if (file_exists("lisaa_kulut.inc")) {
			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='tee' value='LISAAKULUT'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='Submit' value='".t("Lisaa kulut")."'>
					</form>";
		}

		echo "<form action='tuote_selaus_haku.php' method='post'>
				<input type='hidden' name='toim_kutsu' value='$toim'>
				<input type='hidden' name='tyojono' value='$tyojono'>
				<input type='submit' value='".t("Selaa tuotteita")."'>
				</form>";

		// aivan karseeta, mutta joskus pit�� olla n�in asiakasyst�v�llinen... toivottavasti ei h�iritse ket��n
		if ($kukarow["extranet"] == "" and ($kukarow["yhtio"] == "artr" or $kukarow['yhtio'] == 'orum')) {
			echo "<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("Malliselain")."'>
					</form>";
		}

		if ($kukarow["extranet"] == "" and $yhtiorow["vahinkotiedot_tyomaarayksella"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {

			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='tee' value='VAHINKO'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='Submit' value='".t("Lis�� vahinkotiedot")."'>
					</form>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == 'REKLAMAATIO') and isset($sms_palvelin) and $sms_palvelin != "" and isset($sms_user)  and $sms_user != "" and isset($sms_pass)  and $sms_pass != "") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='tila' value='SYOTASMS'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tyojono' value='$tyojono'>
					<input type='Submit' value='".t("L�het� tekstiviesti")."'>
					</form>";
		}

		echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='mikrotila'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='mista' value='$mista'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
				<input type='hidden' name='tyojono' value='$tyojono'>
				<input type='hidden' name='projektilla' value='$projektilla'>";

		if ($toim != "VALMISTAVARASTOON") {
			echo "<input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'>";
		}
		else {
			echo "<input type='Submit' value='".t("Lue valmistusrivit tiedostosta")."'>";
		}

		echo "</form>";

		if ($kukarow["extranet"] == "" and ($toim == "PIKATILAUS" or $toim == "RIVISYOTTO") and $yhtiorow["rahtikirjojen_esisyotto"] == "M") {
			echo "<form action='../rahtikirja.php' method='post'>
					<input type='hidden' name='tee' value=''>
					<input type='hidden' name='toim' value='lisaa'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='rahtikirjan_esisyotto' value='$toim'>
					<input type='hidden' name='id' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='rakirno' value='$tilausnumero'>
					<input type='hidden' name='tunnukset' value='$tilausnumero'>
					<input type='Submit' value='".t("Rahtikirjan esisy�tt�")."'>
					</form>";
		}

		if ($yhtiorow["myyntitilauksen_liitteet"] != "") {

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='liitetiedostot' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = pupe_query($queryoik);

			if (mysql_num_rows($res) > 0) {

				if ($laskurow["tunnusnippu"] > 0) {
					$id = $laskurow["tunnusnippu"];
				}
				else {
					$id = $laskurow["tunnus"];
				}

				echo "<form method='POST' action='".$palvelin2."yllapito.php?toim=liitetiedostot&from=tilausmyynti&ohje=off&haku[7]=@lasku&haku[8]=@$id&lukitse_avaimeen=$id&lukitse_laji=lasku'>
						<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
						<input type='hidden' name='toim_kutsu' value='$toim'>
						<input type='hidden' name='tyojono' value='$tyojono'>
						<input type='submit' value='" . t('Tilauksen liitetiedostot')."'>
						</form>";
			}
		}

		if ($kukarow["extranet"] == "" and $saako_liitaa_laskuja_tilaukseen == "") {
			//katsotaan onko sarjanumerolle liitetty kulukeikka
			$query  = "	SELECT *
						from lasku
						where yhtio		 = '$kukarow[yhtio]'
						and tila		 = 'K'
						and alatila		 = 'T'
						and liitostunnus = '$laskurow[tunnus]'
						and ytunnus 	 = '$laskurow[tunnus]'";
			$keikkares = pupe_query($query);

			unset($kulurow);
			unset($keikkarow);

			if (mysql_num_rows($keikkares) == 1) {
				$keikkarow = mysql_fetch_assoc($keikkares);
			}

			if (isset($keikkarow) and $keikkarow["tunnus"] > 0) {
				$keikkalisa = "<input type='hidden' name='otunnus' value='$keikkarow[tunnus]'>
								<input type='hidden' name='keikanalatila' value='T'>";
			}
			else {
				$keikkalisa = "<input type='hidden' name='luouusikeikka' value='OKMYYNTITILAUS'>
								<input type='hidden' name='liitostunnus' value='$tilausnumero'>";
			}

			if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K") {
				echo "<form method='POST' action='$PHP_SELF'>
						<input type='hidden' name='tee' value='kululaskut'>
						<input type='hidden' name='toiminto' value='kululaskut'>
						$keikkalisa
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value='$mista'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='submit' value='" . t('Liit� kululasku')."'>
						</form>";
			}
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow["myynti_asiakhin_tallenna"] == "K") and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","TYOMAARAYS","PROJEKTI"))) {
			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='tuotteetasiakashinnastoon'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='Submit' value='".t("Siirr� tuotteet asiakashinnoiksi")."'>
					</form>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and file_exists("osamaksusoppari.inc")) {
			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='osamaksusoppari'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='Submit' value='".t("Rahoituslaskelma")."'>
					</form>";
		}

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and file_exists("vakuutushakemus.inc")) {
			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='vakuutushakemus'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='Submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'>
					</form>";
		}

		$jt_oikeu_check = $kukarow['extranet'] != '' ? 'jtselaus.php' : 'tilauskasittely/jtselaus.php';

		// JT-rivit n�ytet��n vain jos siihen on oikeus!
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= '$jt_oikeu_check'
					and alanimi = ''";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			$query  = "	SELECT count(*) kpl
						from tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN lasku USE INDEX (primary) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$laskurow[liitostunnus]')
						WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
						and tilausrivi.tyyppi 			in ('L','G')
						and tilausrivi.var 				= 'J'
						and tilausrivi.keratty 			= ''
						and tilausrivi.uusiotunnus 		= 0
						and tilausrivi.kpl 				= 0
						and tilausrivi.jt $lisavarattu	> 0";
			$jtapuresult = pupe_query($query);
			$jtapurow = mysql_fetch_assoc($jtapuresult);

			if ($jtapurow["kpl"] > 0) {
				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='tyojono' value='$tyojono'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value='$mista'>";

				if (!isset($jt_kayttoliittyma) and $kukarow['extranet'] != '') {
					$jt_kayttoliittyma = 'kylla';
				}

				if ($jt_kayttoliittyma == "kylla") {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value=''>
							<input type='submit' value='".t("Piilota JT-rivit")."'>";
				}
				else {
					echo "	<input type='hidden' name='jt_kayttoliittyma' value='kylla'>
							<input type='submit' value='".t("N�yt� JT-rivit")."'>";
				}
				echo "</form>";
			}
		}

		// aivan karseeta, mutta joskus pit�� olla n�in asiakasyst�v�llinen... toivottavasti ei h�iritse ket��n
		if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "allr") {

			echo "<br>
					<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim' value='MP'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("MP-Selain")."'>
					</form>
					<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim' value='MO'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("Moposelain")."'>
					</form>
					<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim' value='MK'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("Kelkkaselain")."'>
					</form>
					<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim' value='MX'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("Crossiselain")."'>
					</form>
					<form action='../yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim' value='AT'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='submit' value='".t("ATV-Selain")."'>
					</form>";
		}

		if ($kukarow["extranet"] == "" and $yhtiorow["konserni"] == "makia") {

			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='mikrotila_matriisi'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value='$mista'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='tyojono' value='$tyojono'>
					<input type='hidden' name='projektilla' value='$projektilla'>";
			echo "<input type='Submit' value='".t("Lue tuotematriisi")."'>";
			echo "</form>";
		}

		if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO") {

			if (tarkista_oikeus("tyojono.php")) {

				if ($toim == 'TYOMAARAYS' or $toim == 'REKLAMAATIO') {
					$toim2 = '';
				}
				else {
					$toim2 = $toim;
				}

				echo "<form method='POST' action='".$palvelin2."tyomaarays/tyojono.php'>
						<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
						<input type='hidden' name='toim' value='$toim2'>
						<input type='hidden' name='indexvas' value='1'>
						<input type='hidden' name='tyojono' value='$tyojono'>
						<input type='submit' value='" . t('Ty�jono')."'>
						</form>";

				// Jos ty�jono on tyhj� niin otetaan se otsikolta
				if (!isset($tyojono) or $tyojono == "") {
					$tyojono_url = $laskurow["tyojono"];
				}
				else {
					$tyojono_url = $tyojono;
				}

				echo "<form method='POST' action='".$palvelin2."tyomaarays/asennuskalenteri.php?liitostunnus=$tilausnumero&tyojono=$tyojono_url#".date("j_n_Y")."'>
						<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
						<input type='hidden' name='toim' value='$toim2'>
						<input type='submit' value='" . t('Asennuskalenteri')."'>
						</form>";
			}

			if (tarkista_oikeus("tyom_tuntiraportti.php")) {
				echo "<form method='POST' action='".$palvelin2."raportit/tyom_tuntiraportti.php'>
						<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
						<input type='hidden' name='tee' value='raportoi'>
						<input type='hidden' name='tyojono' value='$tyojono'>
						<input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
						<input type='hidden' name='tyom_nro' value='$laskurow[tunnus]'>
						<input type='hidden' name='vva' value='".substr($laskurow['luontiaika'], 0, 4)."'>
						<input type='hidden' name='kka' value='".substr($laskurow['luontiaika'], 5, 2)."'>
						<input type='hidden' name='ppa' value='".substr($laskurow['luontiaika'], 8, 2)."'>
						<input type='hidden' name='vvl' value='".substr($laskurow['luontiaika'], 0, 4)."'>
						<input type='hidden' name='kkl' value='".substr($laskurow['luontiaika'], 5, 2)."'>
						<input type='hidden' name='ppl' value='".substr($laskurow['luontiaika'], 8, 2)."'>
						<input type='submit' value='" . t('Tuntiraportti')."'>
						</form>";
			}
		}

		//	Tarkistetaan, ettei asiakas ole prospekti, tarjoukselle voi liitt�� prospektiasiakkaan, josta voi tehd� suoraan tilauksen. Herjataan siis jos asiakas pit�� p�ivitt�� ja tarkistaa
		if ($toim != "TARJOUS") {
			$prosque = "	SELECT tunnus
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]' and laji='R'";
			$prosres = pupe_query($prosque);
			if (mysql_num_rows($prosres)==1) {
				$asiakasOnProspekti = "JOO";
				echo "<br><font class='error'>".t("HUOM!!! Asiakas on prospektiasiakas, tarkista asiakasrekisteriss� asiakkaan tiedot ja p�ivit� tiedot tilauksen otsikolle.")."</font>";
			}
		}

		echo "<br><br>\n";
	}

	//Oletetaan, ett� tilaus on ok, $tilausok muuttujaa summataan alempana jos jotain virheit� ilmenee
	$tilausok = 0;
	$sarjapuuttuu = 0;

	$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$meapu = pupe_query($apuqu);
	$meapurow = mysql_fetch_assoc($meapu);

	if ($laskurow["liitostunnus"] > 0 and $meapurow["kateinen"] == "" and ($laskurow["nimi"] == '' or $laskurow["osoite"] == '' or $laskurow["postino"] == '' or $laskurow["postitp"] == '')) {
		if ($toim != 'VALMISTAVARASTOON' and $toim != 'SIIRTOLISTA' and $toim != 'SIIRTOTYOMAARAYS' and $toim != 'TARJOUS') {
			echo "<font class='error'>".t("VIRHE: Tilauksen laskutusosoitteen tiedot ovat puutteelliset")."!</font><br><br>";
			$tilausok++;
		}
	}

	// t�ss� alotellaan koko formi.. t�m� pit�� kirjottaa aina
	echo "<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='mista' value='$mista'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='lopetus' value='$lopetus'>
			<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
			<input type='hidden' name='projektilla' value='$projektilla'>";

	// kirjoitellaan otsikko
	echo "<table>";

	// jos asiakasnumero on annettu
	if ($laskurow["liitostunnus"] > 0 or ($laskurow["liitostunnus"] == 0 and $kukarow["kesken"] > 0 and $toim != "PIKATILAUS")) {

		$query = "	SELECT fakta, round(luottoraja, 0) luottoraja, luokka, asiakasnro, osasto
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$laskurow[liitostunnus]'";
		$faktaresult = pupe_query($query);
		$faktarow = mysql_fetch_assoc($faktaresult);

		if ($GLOBALS['eta_yhtio'] != '' and ($koti_yhtio != $kukarow['yhtio'] or $faktarow['osasto'] != '6')) {
			unset($GLOBALS['eta_yhtio']);
		}

		echo "<tr>$jarjlisa";

		if ($toim == "VALMISTAVARASTOON") {
			echo "<th align='left'>".t("Varastot").":</th>";
			echo "<td>$laskurow[ytunnus] $laskurow[nimi]</td>";
			echo "<th align='left'>&nbsp;</th>";
			echo "<td>&nbsp;</td>";
		}
		else {
			echo "<th>".t("Ytunnus").":</th><td>";

			if ($laskurow["liitostunnus"] == 0) {
				echo "<input type='submit' name='liitaasiakasnappi' value='".t("Liit� asiakas")."'></td>";
			}
			else {
				echo "$laskurow[ytunnus] </td>";
			}

			echo "<th>".t("Asiakasnro").":</th><td>$faktarow[asiakasnro]</td>";
			echo "</tr>";

			echo "<tr>$jarjlisa";
			echo "<th align='left'>".t("Asiakas").":</th>";

			if ($kukarow["extranet"] == "") {
				echo "<td><a href='{$palvelin2}crm/asiakasmemo.php?ytunnus=$laskurow[ytunnus]&asiakasid=$laskurow[liitostunnus]&from=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$laskurow[nimi]</a>";
 			}
			else {
				echo "<td>$laskurow[nimi]";
			}

			if ($laskurow["toim_nimi"] != $laskurow["nimi"]) echo "<br>$laskurow[toim_nimi]";
			echo "</td>";

			echo "<th align='left'>".t("Toimitustapa").":</th>";

			if ($kukarow["extranet"] != "") {
				$query = "	(SELECT toimitustapa.*
							FROM toimitustapa
							WHERE toimitustapa.yhtio = '$kukarow[yhtio]' and (toimitustapa.extranet in ('K','M') or toimitustapa.selite = '$extra_asiakas[toimitustapa]')
							and (toimitustapa.sallitut_maat = '' or toimitustapa.sallitut_maat like '%$laskurow[toim_maa]%'))
							UNION
							(SELECT toimitustapa.*
							FROM toimitustapa
							JOIN asiakkaan_avainsanat ON toimitustapa.yhtio = asiakkaan_avainsanat.yhtio and toimitustapa.selite = asiakkaan_avainsanat.avainsana and asiakkaan_avainsanat.laji = 'toimitustapa' and asiakkaan_avainsanat.liitostunnus = '$laskurow[liitostunnus]'
							WHERE toimitustapa.yhtio = '$kukarow[yhtio]'
							and (toimitustapa.sallitut_maat = '' or toimitustapa.sallitut_maat like '%$laskurow[toim_maa]%'))
							ORDER BY jarjestys,selite";
			}
			else {
				$query = "	SELECT toimitustapa.*
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]' and (extranet in ('','M') or selite = '$laskurow[toimitustapa]')
							and (sallitut_maat = '' or sallitut_maat like '%$laskurow[toim_maa]%')
							ORDER BY jarjestys,selite";
			}

			$tresult = pupe_query($query);

			if ($kukarow["extranet"] != "" and mysql_num_rows($tresult) == 0) {
				echo t("VIRHE: K�ytt�j�tiedoissasi on virhe! Ota yhteys j�rjestelm�n yll�pit�j��n.")."<br><br>";
				exit;
			}

			echo "<td><select name='toimitustapa' onchange='submit()' $state>";

			// Otetaan toimitustavan tiedot ja k�ytet��n niit� l�pi tilausmyynnin!
			$tm_toimitustaparow = mysql_fetch_assoc($tresult);
			mysql_data_seek($tresult, 0);

			if ($laskurow["toimitustavan_lahto"] != '0000-00-00 00:00:00') {
				echo "<option value='$laskurow[toimitustapa]'>$toimtapa_kv - ".t("L�ht�")." ".tv1dateconv($laskurow["toimitustavan_lahto"], "PITKA")."</option>";
			}

			while ($row = mysql_fetch_assoc($tresult)) {

				$sel = "";
				if ($row["selite"] == $laskurow["toimitustapa"] and $laskurow["toimitustavan_lahto"] == '0000-00-00 00:00:00') {
					$sel = 'selected';
					$tm_toimitustaparow = $row;
				}

				$toimitustava_lahto = seuraava_lahtoaika($row['tunnus']);

				echo "<option value='$row[selite]###$toimitustava_lahto' $sel>";
				echo t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV");

				if ($toimitustava_lahto != "0000-00-00 00:00:00") {
					echo " - ".t("L�ht�")." ".tv1dateconv($toimitustava_lahto, "PITKA");
				}

				echo "</option>";
			}
			echo "</select>";

			if ($laskurow["rahtivapaa"] != "") {
				echo " (".t("Rahtivapaa").") ";
			}

			if ($kukarow["extranet"] == "") {
				//etsit��n l�ytyyk� rahtisopimusta
				$rahsoprow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"]);

				if ($rahsoprow > 0) {
					$ylisa = "&tunnus=$rahsoprow[tunnus]";
				}
				else {
					$ylisa = "&uusi=1&ytunnus=$laskurow[ytunnus]&toimitustapa=$laskurow[toimitustapa]";
					$rahsoprow["rahtisopimus"] = t("Rahtisopimus");
				}

				echo " <a href='{$palvelin2}yllapito.php?toim=rahtisopimukset$ylisa&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$rahsoprow[rahtisopimus]</a>";
			}

			echo "</td>";
		}

		echo "</tr>";
		echo "<tr>$jarjlisa";
		echo "<th align='left'>".t("Tilausnumero").":</th>";

		if ($laskurow["tunnusnippu"] > 0) {

			echo "<td><select name='valitsetoimitus' onchange='submit();' ".js_alasvetoMaxWidth("valitsetoimitus", 250).">";

			// Listataan kaikki toimitukset ja liitet��n tarjous mukaan jos se tiedet��n
			$hakulisa = "";

			if ($lasklisatied_row["tunnusnippu_tarjous"] > 0) {
				$hakulisa =" or (lasku.tunnusnippu = '$lasklisatied_row[tunnusnippu_tarjous]' and tila='T' and alatila='B')";
			}
			elseif ($projektilla > 0 and $laskurow["tunnusnippu"] != $projektilla) {
				$hakulisa =" or lasku.tunnusnippu = '$projektilla'";
			}

			//	Valmistuksissa ei anneta sotkea myynti�!
			if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
				$ptilat = "'V','W'";
			}
			else {
				$ptilat = "'L','N','A','T','G','S','O','R','E'";
			}

			$vquery = " SELECT count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";

			$query = " 	SELECT lasku.tila, lasku.alatila, varastopaikat.nimitys varasto, lasku.toimaika,
						if (lasku.tila='T',if (lasku.tunnusnippu>0,concat(lasku.tunnusnippu,'/',($vquery)), concat(lasku.tunnusnippu,'/1')),lasku.tunnus) tilaus,
						lasku.tunnus tunnus,
						lasku.tilaustyyppi
						FROM lasku
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (lasku.tunnusnippu = '$laskurow[tunnusnippu]' $hakulisa)
						and lasku.tila IN ($ptilat)
						and if ('$tila' = 'MUUTA', alatila != 'X', lasku.tunnus=lasku.tunnus)
						GROUP BY lasku.tunnus";
			$toimres = pupe_query($query);

			if (mysql_num_rows($toimres) > 0) {

				while($row = mysql_fetch_assoc($toimres)) {

					$sel = "";
					if ($row["tunnus"] == $kukarow["kesken"]) {
						$sel = "selected";
					}

					$laskutyyppi = $row["tila"];
					$alatila 	 = $row["alatila"];

				 	require ("inc/laskutyyppi.inc");

					$tarkenne = " ";

					if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
						$tarkenne = " (".t("Asiakkaalle").") ";
					}
					elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
						$tarkenne = " (".t("Varastoon").") ";
					}
					elseif(($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
						$tarkenne = " (".t("Reklamaatio").") ";
					}
					elseif(($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "A") {
						$laskutyyppi = "Ty�m��r�ys";
					}
					elseif($row["tila"] == "N" and $row["tilaustyyppi"] == "E") {
						$laskutyyppi = "Ennakkotilaus kesken";
					}

					if ($row["alatila"] == "X") $disabled = "DISABLED";
					else $disabled = "";

					echo "<option value ='$row[tunnus]' $sel $disabled>".t("$laskutyyppi")." $tarkenne $row[tilaus] ".t("$alatila")." $row[varasto]</option>";
				}
			}
			echo "<optgroup label='".t("Perusta uusi")."'>";

			if ($toim == "TARJOUS" and $laskurow["alatila"] != "B") {
				echo "<option value='TARJOUS'>".T("Tarjouksen versio")."</option>";
			}
			else {

				if ($yhtiorow["tilauksen_kohteet"] == "K") {
					if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
						echo "<option value='VALMISTAVARASTOON'>".T("Valmistus")."</option>";
					}
					else {
						echo "<option value='RIVISYOTTO'>".T("Toimitus")."</option>";
						echo "<option value='TYOMAARAYS'>".T("Ty�m��r�ys")."</option>";
						echo "<option value='REKLAMAATIO'>".T("Reklamaatio")."</option>";
						echo "<option value='SIIRTOLISTA'>".T("Siirtolista")."</option>";
					}
				}
				elseif ($laskurow["tilaustyyppi"] == "E") {
					echo "<option value='ENNAKKO'>".T("Ennakkotilaus")."</option>";
				}
				elseif ($toim == "PIKATILAUS") {
					echo "<option value='PIKATILAUS'>".T("Toimitus")."</option>";
				}
				else {
					echo "<option value='RIVISYOTTO'>".T("Toimitus")."</option>";
				}
			}

			echo "</optgroup></select>";
		}
		elseif ($yhtiorow["myyntitilaus_osatoimitus"] == "K" and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {
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

		if ($kukarow["extranet"] != "" and $kukarow["yhtio"] == 'orum') {
			echo "<th>&nbsp;</th>";
		}
		elseif ($toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
			echo "<th>".t("Tilausvahvistus").":</th>";
		}
		elseif (($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOLISTA") and $yhtiorow["varastosiirto_tilausvahvistus"] == "K") {
			echo "<th>".t("Siirtovahvistus").":</th>";
		}
		else {
			echo "<th>&nbsp;</th>";
		}

		if ($kukarow["extranet"] != "" and $kukarow["yhtio"] == 'orum') {
			echo "<td><input type='hidden' name='tilausvahvistus' value='$laskurow[tilausvahvistus]'>&nbsp;</td>";
		}
		elseif ($toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
			$extralisa = "";

			if ($kukarow["extranet"] != "") {
				$extralisa .= " and avainsana.selite not like '%E%' and avainsana.selite not like '%O%' ";

				if ($kukarow['hinnat'] == 1) {
					$extralisa .= " and avainsana.selite not like '1%' ";
				}
			}

			$tresult = t_avainsana("TV","", $extralisa);

    		echo "<td><select name='tilausvahvistus' onchange='submit();' ".js_alasvetoMaxWidth("tilausvahvistus", 250)." $state>";
   			echo "<option value=' '>".t("Ei Vahvistusta")."</option>";

   			while($row = mysql_fetch_assoc($tresult)) {
   				$sel = "";
   				if ($row["selite"]== $laskurow["tilausvahvistus"]) $sel = 'selected';
   				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
   			}
   			echo "</select></td>";

		}
		elseif (($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOLISTA") and $yhtiorow["varastosiirto_tilausvahvistus"] == "K") {
			echo "<td>".t("Kyll�")."</td>";
		}
		else {
			echo "<td>&nbsp;</td>";
		}

		if ($kukarow["extranet"] == "") {
			if ($toim != "VALMISTAVARASTOON") {
				echo "<th align='left'>".t("Myyj�nro").":</th>";
			}
			else {
				echo "<th align='left'>".t("Laatija").":</th>";
			}

			echo "<td><input type='text' name='myyjanro' size='8' $state> tai ";
			echo "<select name='myyja' onchange='submit();' $state>";

			$query = "	SELECT tunnus, kuka, nimi, myyja, asema
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and (extranet = '' or tunnus='$laskurow[myyja]')
						ORDER BY nimi";
			$yresult = pupe_query($query);

			while ($row = mysql_fetch_assoc($yresult)) {
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

			if (trim($faktarow["fakta"]) != "" and $toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
				echo "<tr>$jarjlisa<th>".t("Asiakasfakta").":</th><td colspan='3'>";
				echo "<strong>".wordwrap($faktarow["fakta"], 110, "<br>")."</strong>&nbsp;</td></tr>\n";
			}

			// Katsotaan onko liitetiedostoja
			$liitequery = "	SELECT tunnus, selite
							FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
							WHERE yhtio = '$kukarow[yhtio]'
							AND liitos = 'lasku'
							AND liitostunnus = '$laskurow[tunnus]'";
			$liiteres = pupe_query($liitequery);

			if (mysql_num_rows($liiteres) > 0) {
				$liitemaara = 1;

				echo "<tr>$jarjlisa<th>".t("Liitetiedostot").":</th><td colspan='3'>";

				while ($liiterow = mysql_fetch_array($liiteres)) {
					echo "<a href='{$palvelin2}view.php?id=$liiterow[tunnus]' target='Attachment'>".t("Liite")." $liitemaara $liiterow[selite]</a> ";
					$liitemaara++;
				}
				echo "</td></tr>";
			}

			if ($toim == 'TYOMAARAYS' or $toim == "TYOMAARAYS_ASENTAJA") {
				// Katsotaan onko kalenterimerkint�j�
				$query = "	SELECT left(kalenteri.pvmalku, 10) pvmalku_sort,
							kalenteri.pvmalku,
							kalenteri.pvmloppu,
							concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', kuka.nimi, '##', kuka.kuka) asennuskalenteri
							FROM  kalenteri
							LEFT JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
							WHERE kalenteri.yhtio = '$kukarow[yhtio]'
							and kalenteri.tyyppi = 'asennuskalenteri'
							and kalenteri.liitostunnus = '$kukarow[kesken]'";
				$liiteres = pupe_query($query);

				if (mysql_num_rows($liiteres) > 0) {

					echo "<tr>$jarjlisa<th>".t("Asennusty�t").":</th><td colspan='3'>";

					$asekal_distinct_chk = array();

					while ($liiterow = mysql_fetch_array($liiteres)) {

						list($asekal_alku, $asekal_loppu, $asekal_nimi, $asekal_kuka) = explode("##", $liiterow["asennuskalenteri"]);

						$asekal_atstamp = mktime(substr($asekal_alku,11,2), substr($asekal_alku,14,2), 0, substr($asekal_alku,5,2), substr($asekal_alku,8,2), substr($asekal_alku,0,4));
						$asekal_ltstamp = mktime(substr($asekal_loppu,11,2), substr($asekal_loppu,14,2), 0, substr($asekal_loppu,5,2), substr($asekal_loppu,8,2), substr($asekal_loppu,0,4));

						$kaletunnit[$nimi] += ($ltstamp - $atstamp)/60;

						if ($toim == 'TYOMAARAYS' or $toim == "TYOMAARAYS_ASENTAJA") {

							if ($asekal_distinct_chk[$asekal_kuka][$laskurow['tunnus']] == $liiterow['pvmalku_sort'] and substr($asekal_alku,5,2).substr($asekal_alku,8,2).substr($asekal_alku,0,4) == substr($asekal_loppu,5,2).substr($asekal_loppu,8,2).substr($asekal_loppu,0,4)) {
								continue;
							}

							echo "$asekal_nimi: ".tv1dateconv($asekal_alku, "", "LYHYT");

							if ($kukarow['kuka'] == $asekal_kuka) {

								// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
								date_default_timezone_set('UTC');

								$query = "	SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
											FROM kalenteri
											WHERE yhtio = '$kukarow[yhtio]'
											AND kuka = '$kukarow[kuka]'
											AND kentta02 = '$laskurow[tunnus]'
											AND pvmalku like '".substr($asekal_alku,0,4)."-".substr($asekal_alku,5,2)."-".substr($asekal_alku,8,2)."%'
											AND tyyppi = 'kalenteri'";
								$tunti_chk_res = pupe_query($query);

								$tunnit = 0;
								$minuutit = 0;
								$tuntimaara = '';

								while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
									if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {
										list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
										list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

										list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

										$tunnit += $temp_tunnit;
										$minuutit += $temp_minuutit;
									}
								}

								if ($tunnit != 0 or $minuutit != 0) {
									$minuutit = $minuutit / 60;
									$tuntimaara = " (".str_replace(".",",",($tunnit+$minuutit))."h)";
								}

								if ($tuntimaara != '') echo $tuntimaara;
							}

							if (substr($asekal_alku,5,2).substr($asekal_alku,8,2).substr($asekal_alku,0,4) != substr($asekal_loppu,5,2).substr($asekal_loppu,8,2).substr($asekal_loppu,0,4)) {
								echo " - ".tv1dateconv($asekal_loppu, "", "LYHYT");

								// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
								date_default_timezone_set('UTC');

								$query = "	SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
											FROM kalenteri
											WHERE yhtio = '$kukarow[yhtio]'
											AND kuka = '$kukarow[kuka]'
											AND kentta02 = '$laskurow[tunnus]'
											AND pvmloppu like '".substr($asekal_loppu,0,4)."-".substr($asekal_loppu,5,2)."-".substr($asekal_loppu,8,2)."%'
											AND tyyppi = 'kalenteri'";
								$tunti_chk_res = pupe_query($query);

								$tunnit = 0;
								$minuutit = 0;
								$tuntimaara = '';

								while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
									if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {
										list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
										list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

										list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

										$tunnit += $temp_tunnit;
										$minuutit += $temp_minuutit;
									}
								}

								if ($tunnit != 0 or $minuutit != 0) {
									$minuutit = $minuutit / 60;
									$tuntimaara = " (".str_replace(".",",",($tunnit+$minuutit))."h)";
								}

								if ($tuntimaara != '') echo $tuntimaara;
							}

							$asekal_distinct_chk[$asekal_kuka][$laskurow['tunnus']] = $liiterow['pvmalku_sort'];

							echo "<br>";
						}
						else {
							echo "$asekal_nimi: ".tv1dateconv($asekal_alku, "P")." - ".tv1dateconv($asekal_loppu, "P")."<br>";
						}
					}
					echo "</td></tr>";
				}
			}

			if ($toim == 'TARJOUS') {
				$kalequery = "	SELECT yhteyshenkilo.nimi yhteyshenkilo, kuka1.nimi nimi1, kuka2.nimi nimi2, kalenteri.*
								FROM kalenteri
								LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio
								LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=kalenteri.yhtio and kuka1.kuka=kalenteri.kuka)
								LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=kalenteri.yhtio and kuka2.kuka=kalenteri.myyntipaallikko)
								where kalenteri.liitostunnus = '$laskurow[liitostunnus]'
								and (kalenteri.otunnus = 0 or kalenteri.otunnus = '$kukarow[kesken]')
								and kalenteri.tyyppi = 'Lead'
								and kuittaus		 = 'K'
								and kalenteri.yhtio  = '$kukarow[yhtio]'
								and left(kalenteri.tyyppi,7) != 'DELETED'
								ORDER BY kalenteri.pvmalku desc";
				$kaleresult = pupe_query($kalequery);

				if (mysql_num_rows($kaleresult) > 0) {
					echo "<tr>$jarjlisa<th>".t("Leadit").":</th><td colspan='3'>";
					echo "<select name='lead' onchange='submit();'>";
					echo "<option value='-1'>".t("Ei leadia")."</option>";

					while ($kalerow = mysql_fetch_assoc($kaleresult)) {

						$sel = "";
						if ($kalerow["otunnus"] == $kukarow["kesken"]) {
							$sel = "selected";
						}

						echo "<option value='$kalerow[tunnus]' $sel>".substr($kalerow["kentta01"],0,60)."</option>";
					}

					echo "</select></td></tr>";
				}
			}
		}
		else {
			echo "<input type='hidden' size='30' name='myyja' value='$laskurow[myyja]'>";
			echo "</tr>";
		}
	}
	elseif ($kukarow["extranet"] == "") {
		// asiakasnumeroa ei ole viel� annettu, n�ytet��n t�ytt�kent�t
		if ($kukarow["oletus_asiakas"] != 0) {
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$extra_asiakas = mysql_fetch_assoc($result);
				$yt 	= $extra_asiakas["ytunnus"];
			}
		}

		if ($kukarow["myyja"] != 0) {
			$my = $kukarow["myyja"];
		}

		if ($toim == "PIKATILAUS") {
			echo "<tr>$jarjlisa
				<th align='left'>".t("Asiakas")."</th>
				<td><input type='text' size='10' name='syotetty_ytunnus' value='$yt'></td>
				<th align='left'>".t("Postitp")."</th>
				<td><input type='text' size='10' name='postitp' value='$postitp'></td>
				</tr>";
			echo "<tr>$jarjlisa
				<th align='left'>".t("Myyj�nro")."</th>
				<td><input type='text' size='10' name='myyjanumero' value='$my'></td>
				</tr>";
		}
	}

	echo "</table>";

	//N�ytet��nko asiakkaan saatavat!
	$query  = "	SELECT yhtio
				FROM tilausrivi
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus = '$kukarow[kesken]'";
	$numres = pupe_query($query);

	if ($kukarow['extranet'] == '' and ($kukarow['kassamyyja'] == '' or $kukarow['saatavat'] == '1') and $laskurow['liitostunnus'] > 0 and ($kaytiin_otsikolla == "NOJOO!" or mysql_num_rows($numres) == 0) and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {
		$sytunnus 	 	 = $laskurow['ytunnus'];
		$eiliittymaa 	 = "ON";
		$luottorajavirhe = "";
		$jvvirhe 		 = "";
		$ylivito 		 = "";
		$trattavirhe 	 = "";
		$laji 			 = "MA";

		if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
			$grouppaus = "ytunnus";
		}
		else {
			$grouppaus = "";
		}

		ob_start();
		require ("raportit/saatanat.php");
		$retval = ob_get_contents();
		ob_end_clean();

		if (trim ($retval) != "" and $kukarow['hinnat'] == 0) {
			echo "<br>$retval";
		}

		if ($luottorajavirhe != '') {
			echo "<br/>";
			echo "<font class='error'>",t("HUOM!!!!!! Luottoraja ylittynyt"),"!!!!!</font>";
			echo "<br/>";
		}

		if ($jvvirhe != '') {
			echo "<br/>";
			echo "<font class='error'>",t("HUOM!!!!!! T�m� on j�lkivaatimusasiakas"),"!!!!!</font>";
			echo "<br/>";
		}

		if ($ylivito > 0) {
			echo "<br/>";
			echo "<font class='error'>".t("HUOM!!!!!! Asiakkaalla on yli 15 p�iv�� sitten er��ntyneit� laskuja, olkaa yst�v�llinen ja ottakaa yhteytt� myyntireskontran hoitajaan")."!!!!!</font>";
			echo "<br/>";
		}

		if ($trattavirhe != '') {
			echo "<br/>";
			echo "<font class='error'>".t("HUOM!!!!!! Asiakkaalla on maksamattomia trattoja")."!!!!!<br></font>";
			echo "<br/>";
		}

		if ($yhtiorow["myyntitilaus_asiakasmemo"] == "K") {
			echo "<br>";
			$ytunnus	= $laskurow['ytunnus'];
			$asiakasid  = $laskurow['liitostunnus'];
			require ("crm/asiakasmemo.php");
		}
	}

	echo "<br>";

	$myyntikielto = '';

	// Tarkastetaan onko asiakas myyntikiellossa
	if ($laskurow['liitostunnus'] > 0) {
		$query = "	SELECT myyntikielto
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[liitostunnus]'";
		$myyntikielto_res = pupe_query($query);
		$myyntikielto_row = mysql_fetch_assoc($myyntikielto_res);

		if ($myyntikielto_row['myyntikielto'] == 'K') {
			if ($kukarow['extranet'] != '') {
				echo "<font class='error'>",t("Luottorajasi on t�ynn�, ota yhteys asiakaspalveluun"),".</font><br/>";
			}
			else {
				echo "<font class='error'>",t("Asiakas on myyntikiellossa"),"!</font><br/>";
			}

			$muokkauslukko = 'LUKOSSA';
			$myyntikielto = 'MYYNTIKIELTO';
		}
	}

	if ($smsnumero != "" and strlen("smsviesti") > 0) {

		if (strlen($smsviesti) > 160) {
			echo "<font class='error'>VIRHE: Tekstiviestin maksimipituus on 160 merkki�!</font><br>";
			$tila = "SYOTASMS";
		}

		$smsnumero = str_replace ("-", "", $smsnumero);
		$ok = 1;

		// K�yt�j�lle l�hetet��n tekstiviestimuistutus
		if ($smsnumero != '' and strlen($smsviesti) > 0 and strlen($smsviesti) < 160 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {

			$smsviesti = urlencode($smsviesti);

			$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$smsnumero&viesti=$smsviesti");
			$smsviesti = urldecode($smsviesti);

			if (trim($retval) == "0") {
				$ok = 0;

				if ($yhtiorow["kalenterimerkinnat"] == "") {
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
					$result = pupe_query($kysely);
				}

			}
		}

		if ($ok == 1) {
			echo "<font class='error'>VIRHE: Tekstiviestin l�hetys ep�onnistui! $retval</font><br><br>";
		}

		if ($ok == 0) {
			echo "<font class='message'>Tekstiviestimuistutus l�hetet��n!</font><br><br>";
		}
	}
	if ($tila == "SYOTASMS") {

		$query  = "	SELECT gsm
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus 	= '$laskurow[liitostunnus]'";
		$numres = pupe_query($query);
		$asiakr = mysql_fetch_assoc($numres);

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
					<td class='back' valign='bottom'><input type='submit' value = 'L�het�'></td>
				</tr>
			</table>
			<br>";
	}

	if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		$tee_tyomaarays = "MAARAAIKAISHUOLLOT";
		//require('tyomaarays/tyomaarays.inc');
	}

	//Kuitataan OK-var riville
	if (($kukarow["extranet"] == "" or $yhtiorow["korvaavat_hyvaksynta"] != "" or $vastaavienkasittely == "kylla") and $tila == "OOKOOAA") {
		$query = "	UPDATE tilausrivi
					SET var2 = 'OK'
					WHERE tunnus = '$rivitunnus'";
		$result = pupe_query($query);

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
		$result = pupe_query($query);

		$tila 		= "";
		$rivitunnus = "";
		$rivitunnus = "";
	}

	//Muokataan tilausrivin lis�tietoa
	if ($kukarow["extranet"] == "" and $tila == "LISATIETOJA_RIVILLE") {

		//	Mit� laitellaan??
		if (isset($asiakkaan_positio)) {
			$lisaalisa = " asiakkaan_positio = '$asiakkaan_positio',";
		}
		else {
			$lisaalisa = " positio = '$positio',";
		}

		$query = "	SELECT 	tilausrivi.tunnus, tuote.vaaditaan_kpl2,
							if (tilausrivin_lisatiedot.pituus>0, tilausrivi.hinta/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.hinta) yksikkohinta,
							if (tilausrivin_lisatiedot.pituus>0, tilausrivi.tilkpl/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.tilkpl) yksikkotilkpl,
							if (tilausrivin_lisatiedot.pituus>0, tilausrivi.varattu/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.varattu) yksikkovarattu
					FROM tilausrivi use index (yhtio_otunnus)
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					and (tilausrivi.tunnus = '$rivitunnus' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$rivitunnus' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))) or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$rivitunnus' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))))
					ORDER BY tunnus";
		$lapsires = pupe_query($query);

		while ($lapsi = mysql_fetch_assoc($lapsires)) {

			//	P�ivitet��n positio tai rivityyppi
			$query = "	UPDATE tilausrivin_lisatiedot
						SET $lisaalisa
						muutospvm				= now(),
						muuttaja				= '$kukarow[kuka]'
						WHERE yhtio			 = '$kukarow[yhtio]'
						and tilausrivitunnus = '$lapsi[tunnus]'";
			$result = pupe_query($query);

			//	Fiksataan m��r�� tai hinta
			if (in_array($lapsi["vaaditaan_kpl2"], array("P","K","M")) and (isset($asiakkaan_positio) or isset($pituus))) {

				//	Lasketaan kertoimet, ekana tulee aina perheen faija
				if (!isset($uusiPituus)) {
					if ($lapsi["vaaditaan_kpl2"] == "P") {
						$query  = "	SELECT pituus
									FROM asiakkaan_positio
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tunnus = '$asiakkaan_positio'";
						$posres = pupe_query($query);
						$posrow = mysql_fetch_assoc($posres);
						$uusiPituus = $posrow["pituus"];
					}
					elseif ($lapsi["vaaditaan_kpl2"] == "M") {
						$uusiPituus = $pituus;
					}

					//	Varmistetaan, ett� saadaan aina jotain lukuja
					if ( (int) $uusiPituus == 0) {
						$uusiPituus == 10000;
					}
				}

				if ($lapsi["vaaditaan_kpl2"] == "P") {
					$uhinta = hintapyoristys(($uusiPituus * $lapsi["yksikkohinta"])/1000);

					$query = "	UPDATE tilausrivi
								SET hinta = '$uhinta'
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lapsi[tunnus]'";
					$updre = pupe_query($query);
				}
				elseif ($lapsi["vaaditaan_kpl2"] == "K") {
					$uvarattu = $uusiPituus*($lapsi["yksikkovarattu"]/1000);
					$utilkpl = $uusiPituus*($lapsi["yksikkotilkpl"]/1000);

					$query = "	UPDATE tilausrivi
								SET varattu = '$uvarattu',
									tilkpl	= '$utilkpl'
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lapsi[tunnus]'";
					$updre = pupe_query($query);
					//echo $query."<br>";
				}

				/*
				//	Fiksataan hintaa
				if ($lapsi["vaaditaan_kpl2"] == "P") {
					$uhinta = hintapyoristys(($posrow["pituus"] * $lapsi["yksikkohinta"])/1000);

					$query = "	UPDATE tilausrivi
								SET hinta = '$uhinta'
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lapsi[tunnus]'";
					$updre = pupe_query($query);
				}
				elseif ($lapsi["vaaditaan_kpl2"] == "K") {
					$maarakerroin = $p;

					$query = "	UPDATE tilausrivi
								SET hinta = '$uhinta'
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lapsi[tunnus]'";
					$updre = pupe_query($query);
				}
				*/

				//	Tallennetaan t�m� pituus viel� lis�tietoihin
				$query = "	UPDATE tilausrivin_lisatiedot
							SET pituus 	= '$uusiPituus',
							muutospvm	= now(),
							muuttaja	= '$kukarow[kuka]'
							WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus = '$lapsi[tunnus]'";
				$updre = pupe_query($query);
			}
		}

		$tila 		= "";
		$rivitunnus = "";
		$positio 	= "";
		$lisaalisa 	= "";
	}

	if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
		//P�ivitet��n is�n perheid jotta voidaan lis�t� lis�� lis�varusteita
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
		$updres = pupe_query($query);

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
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {

			$tilausrivi = mysql_fetch_assoc($result);

			// Tehd��n pari juttua jos tuote on sarjanumeroseurannassa
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
				$sarjares = pupe_query($query);
				$sarjarow = mysql_fetch_assoc($sarjares);

				//Pidet��n sarjatunnus muistissa
				if ($tapa != "POISTA") {
					$myy_sarjatunnus = $sarjarow["tunnukset"];
				}
			}

			if ($tapa == "VAIHDA" and ($tilausrivi["sarjanumeroseuranta"] == "E" or $tilausrivi["sarjanumeroseuranta"] == "F" or $tilausrivi["sarjanumeroseuranta"] == "G")) {
				// Nollataan sarjanumerolinkit
				vapauta_sarjanumerot($toim, $kukarow["kesken"], " and tilausrivi.tunnus = '$rivitunnus' ");
			}

			// Poistetaan my�s tuoteperheen lapset
			if ($tapa != "VAIHDA") {

				// Nollataan sarjanumerolinkit lapsien ja is�n ja dellataan ostorivit
				vapauta_sarjanumerot($toim, $kukarow["kesken"], " and (tilausrivi.tunnus = '$rivitunnus' or tilausrivi.perheid = '$rivitunnus') ");

				$query = "	DELETE FROM tilausrivi
							WHERE perheid 	= '$rivitunnus'
							and tunnus	   != '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = pupe_query($query);
			}

			// Poistetaan my�s tehdaslis�varusteet
			if ($tapa == "POISTA") {

				// Nollataan sarjanumerolinkit ja dellataan ostorivit
				vapauta_sarjanumerot($toim, $kukarow["kesken"], " and tilausrivi.perheid2 	= '$rivitunnus' ");

				$query = "	DELETE FROM tilausrivi
							WHERE perheid2 	= '$rivitunnus'
							and tunnus	   != '$rivitunnus'
							and otunnus		= '$kukarow[kesken]'
							and yhtio		= '$kukarow[yhtio]'";
				$result = pupe_query($query);
			}

			// Poistetaan muokattava tilausrivi
			$query = "	DELETE FROM tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = pupe_query($query);

			// Jos muokkaamme tilausrivin paikkaa ja se on speciaalikeissi, S,T,V niin laitetaan $paikka-muuttuja kuntoon
			if (substr($tapa, 0, 6) != "VAIHDA" and $tilausrivi["var"] == "S" and substr($paikka,0,3) != "@@@") {
				$paikka = "@@@".$tilausrivi["toimittajan_tunnus"]."#".$tilausrivi["hyllyalue"]."#".$tilausrivi["hyllynro"]."#".$tilausrivi["hyllyvali"]."#".$tilausrivi["hyllytaso"];
			}

			if (substr($tapa, 0, 6) != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka,0,3) != "���") {
				$paikka = "���".$tilausrivi["toimittajan_tunnus"];
			}

			if (substr($tapa, 0, 6) != "VAIHDA" and $tilausrivi["var"] == "U" and substr($paikka,0,3) != "!!!") {
				$paikka = "!!!".$tilausrivi["toimittajan_tunnus"];
			}

			$tuoteno 	= $tilausrivi['tuoteno'];

			if (in_array($tilausrivi["var"], array('S','U','T','R', 'J'))) {
				if ($yhtiorow["varaako_jt_saldoa"] == "") {
					$kpl = $tilausrivi['jt'];
				}
				else {
					$kpl = $tilausrivi['jt']+$tilausrivi['varattu'];
				}
			}
			elseif ($tilausrivi["var"] == "P") {
				$kpl	= $tilausrivi['tilkpl'];
			}
			else {
				$kpl	= $tilausrivi['varattu'];
			}

			$query = "	SELECT *
						FROM tuote
						WHERE yhtio  = '$kukarow[yhtio]'
						and  tuoteno = '$tilausrivi[tuoteno]'";
			$aresult = pupe_query($query);
			$tuoterow = mysql_fetch_assoc($aresult);

			// Tutkitaan onko t�m� myyty ulkomaan alvilla
			list(,,,$tsek_alehinta_alv,) = alehinta($laskurow, $tuoterow, $kpl, '', '', '');

			if ($tsek_alehinta_alv > 0) {
				$tuoterow["alv"] = $tsek_alehinta_alv;
			}

			// jos k�yt�ss� on myyntihinnan poikkeava m��r�, kerrotaan hinta takaisin kuntoon.
			if ($tuoterow["myyntihinta_maara"] != 0) {
				$tilausrivi["hinta"] = $tilausrivi["hinta"] * $tuoterow["myyntihinta_maara"];
			}

			if ($tuoterow["alv"] != $tilausrivi["alv"] and $yhtiorow["alv_kasittely"] == "" and $tilausrivi["alv"] < 500) {
				$hinta = (float) $tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow["alv"]/100);
			}
			else {
				$hinta = (float) $tilausrivi["hinta"];
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
				$hinta	= hintapyoristys(laskuval($hinta, $laskurow["vienti_kurssi"]));
			}
			else {
				$hinta	= hintapyoristys($hinta);
			}

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = $tilausrivi["ale{$alepostfix}"];
			}

			$netto		= $tilausrivi['netto'];
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
			$sopimuksen_lisatieto1 = $tilausrivi["sopimuksen_lisatieto1"];
			$sopimuksen_lisatieto2 = $tilausrivi["sopimuksen_lisatieto2"];
			$omalle_tilaukselle = $tilausrivi['omalle_tilaukselle'];

			// useamman valmisteen reseptit...
			if ($tilausrivi['tyyppi'] == "W" and $tilausrivi["tunnus"] != $tilausrivi["perheid"]) {
				$perheid2 = -100;
			}

			if ($tilausrivi['hinta'] == '0.00') $hinta = '';

			// Tehdaslis�varusteperhe-keississ� muistetaan my�s valittu paikka
			if ($tapa != "VAIHDA" and $perheid2 > 0 and $hyllyalue != '') {
				$paikka = $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
			}

			if ($tapa == "MUOKKAA") {
				$var	= $tilausrivi["var"];

				//Jos lasta muokataan, niin s�ilytet��n sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					$perheid = $tilausrivi["perheid"];
				}

				$tila		= "MUUTA";
			}
			elseif ($tapa == "JT") {
				$var 		= "J";

				if ($hyllyalue != '') {
					$paikka	= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "PUUTE") {
				$var 		= "P";

				if ($hyllyalue != '') {
					$paikka	= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				}

				$tila		= "";
			}
			elseif ($tapa == "POISJTSTA") {
				$var 		= "";

				//Jos lasta muokataan, niin s�ilytet��n sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					$perheid = $tilausrivi["perheid"];
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
				if (substr($paikka,0,3) != "!!!" and substr($paikka,0,3) != "���" and substr($paikka,0,3) != "@@@") $paikka = "";
			}
			elseif ($tapa == "MYYVASTAAVA") {
				// tuoteno, m��r�, muut nollataan
				$tuoteno	= $vastaavatuoteno;
				$var		= '';
				$hinta		= '';
				$netto		= '';
				$rivitunnus	= 0;
				$paikka		= '';
				$alv		= '';
				$perheid	= 0;
				$perheid2			= 0;
				$tilausrivilinkki	= '';
				$toimittajan_tunnus	= '';
				// laitetaan tila tyhj�ksi ett� se menee suoraan tilausriviksi.
				$tila = "";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = '';
				}
			}
			elseif ($tapa == "POISTA") {

				if ($yhtiorow['jt_email'] != '' and $tilausrivi['positio'] == 'JT') {
					$kutsu = "";
					$subject = "";
					$content_body = "";

					$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$kutsu = "J�lkitoimitus";
					$subject = t("J�lkitoimitustuote poistettu");
					$content_body = $yhtiorow['nimi']."\n\n";

					$content_body .= "$kpl ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")." ".t("poistettu j�lkitoimituksesta tuotetta")." $tilausrivi[tuoteno] ".t("tilauksella")." $kukarow[kesken]\n\n\n";

					mail($yhtiorow['jt_email'], mb_encode_mimeheader($subject, "ISO-8859-1", "Q"), $content_body, $header, "-f $yhtiorow[postittaja_email]");
					echo t("L�hetettiin j�lkitoimitus-s�hk�posti")."...<br><br>";
				}

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = '';
				}

				$tuoteno	= '';
				$kpl		= '';
				$var		= '';
				$hinta		= '';
				$netto		= '';
				$rivitunnus	= 0;
				$kommentti	= '';
				$kerayspvm	= '';
				$toimaika	= '';
				$paikka		= '';
				$alv		= '';
				$perheid			= 0;
				$perheid2			= 0;
				$tilausrivilinkki	= '';
				$toimittajan_tunnus	= '';
			}
		}
	}

	//Lis�t��n tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAARESEPTIIN" and $teeperhe == "OK") {
		$query = "	UPDATE tilausrivi
					SET perheid2 = '$isatunnus'
					WHERE yhtio  = '$kukarow[yhtio]'
					and tunnus   = '$isatunnus'";
		$presult = pupe_query($query);
		$perheid2 = $isatunnus;
	}

	//Lis�t��n tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAAKERTARESEPTIIN" and $teeperhe == "OK") {
		$query = "	UPDATE tilausrivi
					SET
					perheid	= '$isatunnus',
					tyyppi	= 'W'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$isatunnus'";
		$presult = pupe_query($query);
		$perheid = $isatunnus;
	}

	//Lis�t��n tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAAISAKERTARESEPTIIN") {
		if ($teeperhe == "OK") {
			$query = "	UPDATE tilausrivi
						SET
						perheid	= '$isatunnus',
						tyyppi	= 'W'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$isatunnus'";
			$presult = pupe_query($query);
			$perheid = $isatunnus;
		}

		// useamman valmisteen reseptit...
		$perheid2 = -100;
	}

	if ($tuoteno != '') {
		$multi = "TRUE";

		if (@include("inc/tuotehaku.inc"));
		elseif (@include("tuotehaku.inc"));
		else exit;
	}

	//Lis�t��n rivi
	if ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array)) and $tila != "MUUTA" and $ulos == '' and ($variaatio_tuoteno == "" or (is_array($kpl_array) and array_sum($kpl_array) != 0))) {

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//K�ytt�j�n sy�tt�m� hinta ja ale ja netto, pit�� s�il�� jotta tuotehaussakin voidaan sy�tt�� n�m�

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
		}

		$kayttajan_hinta	= $hinta;
		$kayttajan_netto 	= $netto;
		$kayttajan_var		= $var;
		$kayttajan_kpl		= $kpl;
		$kayttajan_alv		= $alv;
		$kayttajan_paikka	= $paikka;
		$lisatty 			= 0;
		$hyvityssaanto_indeksi 				= 0;
		$hyvityssaanto_hinta_array 			= "";
		$hyvityssaanto_ale_array 			= "";
		$hyvityssaanto_kpl_array 			= "";
		$hyvityssaanto_kommentti_array 		= "";
		$hyvityssaanto_palautuskielto_array	= "";

		// Jos k�ytet��n reklamaatioiden hinnoittelus��nt�� ja k�ytt�j� ei ole v�kisinhyv�ksynyt rivi�
		if ($yhtiorow["reklamaation_hinnoittelu"] == "K" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") and $kayttajan_var != "H") {
			$hyvityssaanto_hinta_array = array();
			$hyvityssaanto_ale_array = array();
			$hyvityssaanto_kpl_array = array();
			$hyvityssaanto_kommentti_array = array();
			$hyvityssaanto_palautuskielto_array = array();

			$palautus = hae_hyvityshinta($laskurow["liitostunnus"], $tuoteno, $kpl);

			foreach ($palautus as $index => $arvot) {
				$tuoteno_array[] = $palautus[$index]["tuoteno"];
				$hyvityssaanto_hinta_array[$index][$tuoteno] = $palautus[$index]["hinta"];
				$hyvityssaanto_ale_array[$index][$tuoteno] = $palautus[$index]["ale"];
				$hyvityssaanto_kpl_array[$index][$tuoteno] = $palautus[$index]["kpl"] * -1;
				$hyvityssaanto_kommentti_array[$index][$tuoteno] = $palautus[$index]["kommentti"];
				$hyvityssaanto_palautuskielto_array[$index][$tuoteno] = $palautus[$index]["palautuskielto"];
			}
		}

		// Valmistuksissa haetaan perheiden perheit� mukaan valmistukseen!!!!!! (vain kun rivi lis�t��n $rivitunnus == 0)
		if ($laskurow['tila'] == 'V' and $var != "W" and $yhtiorow["rekursiiviset_reseptit"] == "Y" and (int) $rivitunnus == 0) {

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
				$perheresult = pupe_query($query);

				if (mysql_num_rows($perheresult) > 0) {
					while ($perherow = mysql_fetch_assoc($perheresult)) {
						$query = "	SELECT distinct isatuoteno
									FROM tuoteperhe
									WHERE isatuoteno = '$perherow[tuoteno]'
									and yhtio  		 = '$kukarow[yhtio]'
									and tyyppi 		 = 'R'
									ORDER by tuoteno";
						$perheresult2 = pupe_query($query);

						if (mysql_num_rows($perheresult2) > 0) {

							//T�t� tuoteperhett� halutaan myyd�
							if (!in_array(strtoupper($perherow["tuoteno"]), $tuoteno_array)) {

								$lt = strtoupper($perherow["tuoteno"]);

								$tuoteno_array[]		= $lt; // lis�t��n tuoteno arrayseen
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

			for ($rii=0; $rii < $riikoko; $rii++) {

				if ($kpl != '' and !is_array($kpl_array) and $rii == 0) {
					$kpl_array[$tuoteno_array[$rii]] = $kayttajan_kpl;
				}

				rekursiivinen_resepti($tuoteno_array[$rii], $kpl_array[$tuoteno_array[$rii]]);
			}
		}

		foreach ($tuoteno_array as $tuoteno) {

			$tuoteno = trim($tuoteno);

			$toimvva = (int) $toimvva;
			$toimkka = (int) $toimkka;
			$toimppa = (int) $toimppa;

			if (checkdate($toimkka,$toimppa,$toimvva)) {
				$toimaika = $toimvva."-".$toimkka."-".$toimppa;
			}

			$keraysvva = (int) $keraysvva;
			$kerayskka = (int) $kerayskka;
			$keraysppa = (int) $keraysppa;

			if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
				$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
			}

			if ($toim != "YLLAPITO") {
				if ($toimaika == "" or $toimaika == "0000-00-00") {
					$toimaika = $laskurow["toimaika"];
				}

				if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
					$kerayspvm = $laskurow["kerayspvm"];
				}
			}

			$varasto = $laskurow["varasto"];

			// Ennakkotilauksen, Tarjoukset ja Yll�pitosopimukset eiv�t varaa saldoa
			if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
				$varataan_saldoa = "EI";
			}
			else {
				$varataan_saldoa = "";
			}

			// Jos ei haluta JT-rivej�
			$jtkielto = $laskurow['jtkielto'];

			//Tehd��n muuttujaswitchit
			if (is_array($hyvityssaanto_hinta_array)) {
				$hinta = $hyvityssaanto_hinta_array[$hyvityssaanto_indeksi][$tuoteno];
			}
			elseif (is_array($hinta_array)) {
				$hinta = $hinta_array[$tuoteno];
			}
			else {
				$hinta = $kayttajan_hinta;
			}

			// hyvityss��nn�n alennus yliajaa k�ytt�j�n sy�tt�m�n alennuksen
			if (is_array($hyvityssaanto_ale_array)) {
				$ale1 = $hyvityssaanto_ale_array[$hyvityssaanto_indeksi][$tuoteno];

				for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = 0;
				}
			}
			else {
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					if (is_array(${'ale_array'.$alepostfix})) {
						${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
					}
					else {
						${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
					}
				}
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

			if (is_array($hyvityssaanto_kpl_array)) {
				$kpl = $hyvityssaanto_kpl_array[$hyvityssaanto_indeksi][$tuoteno];
			}
			elseif (is_array($kpl_array)) {
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

			if ($kukarow["extranet"] != '' and $toim == "EXTRANET_REKLAMAATIO") {
				$kpl = abs($kpl)*-1;
			}
			elseif ($kukarow["extranet"] != '') {
				$kpl = abs($kpl);
			}

			if (is_array($hyvityssaanto_kommentti_array)) {
				$kommentti = $hyvityssaanto_kommentti_array[$hyvityssaanto_indeksi][$tuoteno];
			}
			elseif (isset($kommentti_array[$tuoteno])) {
				$kommentti = $kommentti_array[$tuoteno];
			}

			if (is_array($hyvityssaanto_palautuskielto_array)) {
				$hyvityssaannon_palautuskielto =  $hyvityssaanto_palautuskielto_array[$hyvityssaanto_indeksi][$tuoteno];
			}
			else {
				$hyvityssaannon_palautuskielto = "";
			}


			$query	= "	SELECT *
						from tuote
						where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				//Tuote l�ytyi
				$trow = mysql_fetch_assoc($result);

				//extranettajille ei myyd� tuotteita joilla ei ole myyntihintaa
				if ($kukarow["extranet"] != '' and $trow["myyntihinta"] == 0 and $trow['ei_saldoa'] == '') {
					$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!")."<br>";
					$trow 	 = "";
					$tuoteno = "";
					$kpl	 = 0;
				}

				if ($kukarow["extranet"] != '' and trim($trow["vienti"]) != '') {

					// vientikieltok�sittely:
					// +maa tarkoittaa ett� myynti on kielletty t�h�n maahan
					// -maa tarkoittaa ett� ainoastaan t�h�n maahan saa myyd�
					// eli n�ytet��n vaan tuotteet jossa vienti kent�ss� on tyhj�� tai -maa.. ja se ei saa olla +maa

					if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
						//ei saa myyd� t�h�n maahan
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!")."<br>";
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}

					if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
						//ei saa myyd� t�h�n maahan
						$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!")."<br>";
				 		$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}
				}

				if ($trow['hinnastoon'] == 'V' and $toim != "SIIRTOLISTA" and $toim != 'VALMISTAVARASTOON') {
					//	katsotaan l�ytyyko asiakasalennus / asikakashinta

					// Reklamaatiolla ei huomioida kappalem��riin sidottuja alennuksia
					if ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
						$alemaara = 99999999999;
					}
					else {
						$alemaara = $kpl;
					}

					$hinnat = alehinta($laskurow, $trow, $alemaara, '', '', '',"hintaperuste,aleperuste");

					$onko_asiakkaalla_alennuksia = FALSE;

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
							$onko_asiakkaalla_alennuksia = TRUE;
							break;
						}
					}

					// Jos tuote n�ytet��n vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei l�ydy
					if (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE) {

						if ($kukarow['extranet'] != '') {
							$varaosavirhe .= t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!")."<br>";
						}
						else {
							$varaosavirhe .= t("VIRHE: Tuotetta ei saa myyd� t�lle asiakkaalle!")."<br>";
						}

						$trow 	 = "";
						$tuoteno = "";
						$kpl	 = 0;
						$kielletty++;
					}
				}
			}
			elseif ($kukarow["extranet"] != '') {
				$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!")."<br>";
				$tuoteno = "";
				$kpl	 = 0;
			}
			else {
				//Tuotetta ei l�ydy, aravataan muutamia muuttujia
				$trow["alv"] = $laskurow["alv"];
			}

			if ($tuoteno != '' and $kpl != 0) {
				require ('lisaarivi.inc');
			}

			$hinta 	= '';
			$netto 	= '';
			$var 	= '';
			$kpl 	= '';
			$alv 	= '';
			$paikka	= '';
			$hyvityssaanto_indeksi++;
			$lisatty++;

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}

		// Jos t�m� on suoratoimitusrivi p�ivitet��n sille tallenettu toimitettuaika
		// $lisatty_tun ja $lisatied_row tulee lisaarivi.inc:st�...
		if ($lisatty_tun > 0 and $lisatied_row["suoraan_laskutukseen"] != "" and $lisatied_row["tilausrivilinkki"] > 0) {
			//Tutkitaan l�ytyyk� ostorivi ja sen toimitettuaika
		   	$query = "	SELECT tilausrivin_lisatiedot.suoratoimitettuaika
		   				FROM tilausrivi
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
		   				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi  = 'O'
		   				and tilausrivi.tunnus  = '$tilausrivi[tilausrivilinkki]'
						and tilausrivin_lisatiedot.suoratoimitettuaika != '0000-00-00'";
			$suoratoimresult = pupe_query($query);

			if ($suoratoimrow = mysql_fetch_assoc($suoratoimresult)) {
				$toimitettuaika = " '$suoratoimrow[suoratoimitettuaika]' ";
			}
			else {
				$toimitettuaika = " now() ";
			}

			$toimquery = "	UPDATE tilausrivi
							SET keratty 	= '$kukarow[kuka]',
							kerattyaika 	= $toimitettuaika,
							toimitettu 		= '$kukarow[kuka]',
							toimitettuaika 	= $toimitettuaika
							WHERE yhtio = '$kukarow[yhtio]'
							AND otunnus = '$kukarow[kesken]'
							AND tunnus  = '$lisatty_tun'";
			$toimupdres = pupe_query($toimquery);
		}

		if ($lisavarusteita == "ON" and $perheid2 > 0) {
			//P�ivitet��n is�lle perheid2 jotta tiedet��n, ett� lis�varusteet on nyt lis�tty
			$query = "	UPDATE tilausrivi set
						perheid2	= '$perheid2'
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$perheid2'";
			$updres = pupe_query($query);
		}

		if ($tapa == "VAIHDA" and $perheid2 > 0 and $kayttajan_paikka != "" and substr($kayttajan_paikka,0,3) != "���" and substr($kayttajan_paikka,0,3) != "!!!") {
			//P�ivitet��n tehdaslis�varusteille kanssa sama varastopaikka kuin is�tuotteelle
			$p2paikka = explode("#", $kayttajan_paikka);

			$query = "	UPDATE tilausrivi set
						hyllyalue = '$p2paikka[0]',
						hyllynro = '$p2paikka[1]',
						hyllyvali = '$p2paikka[2]',
						hyllytaso = '$p2paikka[3]'
						where yhtio  = '$kukarow[yhtio]'
						and perheid2 = '$perheid2'";
			$updres = pupe_query($query);
		}

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = "";
			${'ale_array'.$alepostfix} = "";
			${'kayttajan_ale'.$alepostfix} = "";
		}

		$alv 				= "";
		$alv_array 			= "";
		$hinta 				= "";
		$hinta_array 		= "";
		$kayttajan_alv 		= "";
		$kayttajan_hinta	= "";
		$kayttajan_kpl 		= "";
		$kayttajan_netto 	= "";
		$kayttajan_var 		= "";
		$kerayspvm 			= "";
		$kommentti 			= "";
		$kpl 				= "";
		$kpl_array 			= "";
		$netto 				= "";
		$netto_array 		= "";
		$paikat 			= "";
		$paikka 			= "";
		$paikka_array 		= "";
		$perheid 			= 0;
		$perheid2	 		= 0;
		$rivinumero 		= "";
		$rivitunnus 		= 0;
		$toimaika 			= "";
		$tuotenimitys 		= "";
		$tuoteno 			= "";
		$tuoteno_array 		= "";
		$var 				= "";
		$var_array 			= "";
		$sopimuksen_lisatieto1 = "";
		$sopimuksen_lisatieto2 = "";
		if (!isset($lisaa_jatka)) $variaatio_tuoteno = "";
		$omalle_tilaukselle = "";
	}

	//Sy�tt�rivi
	if ($muokkauslukko == "" and ($toim != "PROJEKTI" or $rivitunnus != 0) or $toim == "YLLAPITO") {
		if (file_exists("myyntimenu.inc") and in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TARJOUS"))) {

			/*
				Customoidut menuratkaisut onnistuu nyt myyntimenu.inc tiedostolla
				myyntimenu.inc tiedosto sis�lt��n $myyntimenu array jonka perusteella rakennetaan valikko sek� suoritetaan kyselyt.

				Tuloksena on $ulos muuttuja joka liitet��n syotarivi.inc tiedostossa tuoteno kentt��n

				myyntimenut sis�lt�� aina tuotehakuvalinnan jolla voi palata normaaliin tuotehakuun.

			*/


			//	Haetaan myyntimenu Array ja kysely Array
			//	Jos menutilaa ei ole laitetaan oletus
			if (!isset($menutila)) $menutila = "oletus";

			require("myyntimenu.inc");

			if ($tuoteno != "") $menutila = "haku";

			//suoritetaan kysely ja tehd��n menut jos aihetta
			if (is_array($myyntimenu)) {

				//	Tehd��n menuset
				$menuset = "<select name='menutila' onChange='submit()'>";
				foreach($myyntimenu as $key => $value){
					$sel = "";
					if ($key == $menutila) {
						$sel = "SELECTED";
					}

					$menuset .= "<option value='$key' $sel>$value[menuset]</option>";
				}

				//	Jos ei olla myyntimenussa n�ytet��n aina haku
				$sel = "";
				if (!isset($myyntimenu[$menutila])) {
					$sel = "SELECTED";
				}

				$menuset .= "<option value='haku' $sel>".t("Tuotehaku")."</option>";
				$menuset .= "</select>";

				//	Tehd��n paikka menusetille
				echo "		<table>
								<tr>$jarjlisa
									<td class='back' align = 'left'><font class='head'>".t("Lis�� rivi").": </font></td><td class='back' align = 'left'>$menuset</td>
								</tr>
								<tr>$jarjlisa
									<td class='back'><hr></td>
								</tr>
							</table>";


				//	Tarkastetaan viel�, ett� menutila on m��ritelty ja luodaan lista
				if ($myyntimenu[$menutila]["query"] != "") {
					unset($ulos);

					// varsinainen kysely ja menu
					$query = " SELECT DISTINCT(tuote.tuoteno), nimitys
								FROM tuote
								LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno and tuote.status != 'P'
								WHERE tuote.yhtio ='$kukarow[yhtio]'
								AND ".$myyntimenu[$menutila]["query"];
					$tuoteresult = pupe_query($query);

					$ulos = "<select name='tuoteno'  multiple='TRUE' size='6' style='width: 350px;><option value=''>Valitse tuote</option>";

					if (mysql_num_rows($tuoteresult) > 0) {
						while ($row = mysql_fetch_assoc($tuoteresult)) {
							$sel='';
							if ($tuoteno==$row['tuoteno']) $sel='SELECTED';
							$ulos .= "<option value='$row[tuoteno]' $sel>$row[tuoteno] - ".t_tuotteen_avainsanat($row, 'nimitys')."</option>";
						}
						$ulos .= "</select>";
					}
					else {
						echo "Valinnan antama haku oli tyhj�<br>";
					}
				}
				//	Jos haetaan niin ei ilmoitella turhia
				elseif ($menutila != "haku" and $menutila != "") {
					echo "HUOM! Koitettiin hakea myyntimenua '$menutila' jota ei ollut m��ritelty!<br>";
				}

			}
			else {
				echo "HUOM! Koitettiin hakea myyntimenuja, mutta tiedot olivat puutteelliset.<br>";
			}
		}
		else {
			echo "<table><tr>$jarjlisa<td class='back'><font class='head'>".t("Lis�� rivi").": </font></td></tr></table>";
		}

		require ("syotarivi.inc");
	}
	else {
		echo "</form></table>";
	}

	 // erikoisceisi, jos halutaan PIENITUOTEKYSELY tilaustaulussa, mutta emme halua n�ytt�� niit� kun lis�t��n lis�varusteita
	if ((($tuoteno != '' or (is_array($tuoteno_array) and count($tuoteno_array) > 1)) and $kpl == '' and $kukarow['extranet'] == '') or ($toim == "REKLAMAATIO" and isset($trow['tuoteno']) and $trow['tuoteno'] != '' and $kukarow['extranet'] == '')) {
		
		if ($toim == "REKLAMAATIO" and $tuoteno == '') {
			$tuoteno_lisa = $trow['tuoteno'];
		}
		else if (is_array($tuoteno_array)) {
			$tuoteno_lisa = implode("','", $tuoteno_array);
		}
		else {
			$tuoteno_lisa = $tuoteno;
		}

		$query	= "	SELECT *
					from tuote
					where tuoteno IN ('{$tuoteno_lisa}')
					and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {

			while ($tuote = mysql_fetch_assoc($result)) {
				//kursorinohjausta
				if (($toim == "REKLAMAATIO" and $tuoteno == '') or (is_array($tuoteno_array) and count($tuoteno_array) > 1)) {
					$kentta = 'tuoteno';
				}
				else {
					$kentta = 'kpl';
				}

				echo "<br>
					<table>
					<tr class='aktiivi'>$jarjlisa<th colspan='2'>".t_tuotteen_avainsanat($tuote, 'nimitys')."</th></tr>
					<tr class='aktiivi'>$jarjlisa<th>",t("Tuoteno"),"</th><td>{$tuote['tuoteno']}</td></tr>
					<tr class='aktiivi'>$jarjlisa<th>".t("Hinta")."</th><td align='right'>".hintapyoristys($tuote['myyntihinta'])." $yhtiorow[valkoodi]</td></tr>";

				if ($tuote["nettohinta"] != 0) {
					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Nettohinta")."</th><td align='right'>".hintapyoristys($tuote['nettohinta'])." $yhtiorow[valkoodi]</td></tr>";
					if ($tuote["myyntihinta_maara"] != 0) {
						echo "<tr class='aktiivi'>$jarjlisa<th>".t("Nettohinta")." $tuote[myyntihinta_maara] $tuote[yksikko]</th><td align='right'>".hintapyoristys($tuote['nettohinta'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
					}
				}

				if ($tuote["myymalahinta"] != 0) {
					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Myymalahinta")."</th><td align='right'>".hintapyoristys($tuote['myymalahinta'])." $yhtiorow[valkoodi]</td></tr>";
					if ($tuote["myyntihinta_maara"] != 0) {
						echo "<tr class='aktiivi'>$jarjlisa<th>".t("Myymalahinta")." $tuote[myyntihinta_maara] $tuote[yksikko]</th><td align='right'>".hintapyoristys($tuote['myymalahinta'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
					}
				}

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

					$epakurpantti = "";

					if ($tuote['epakurantti100pvm'] != '0000-00-00') {
						$tuote['kehahin'] = 0;
						$epakurpantti = "(".t("T�ysep�kurantti").")";
					}
					elseif ($tuote['epakurantti75pvm'] != '0000-00-00') {
						$tuote['kehahin'] = round($tuote['kehahin'] * 0.25, 6);
						$epakurpantti = "(".t("75% Ep�kurantti").")";
					}
					elseif ($tuote['epakurantti50pvm'] != '0000-00-00') {
						$tuote['kehahin'] = round($tuote['kehahin'] * 0.5,  6);
						$epakurpantti = "(".t("Puoliep�kurantti").")";
					}
					elseif ($tuote['epakurantti25pvm'] != '0000-00-00') {
						$tuote['kehahin'] = round($tuote['kehahin'] * 0.75, 6);
						$epakurpantti = "(".t("25% Ep�kurantti").")";
					}

					if ($kukarow["yhtio"] == "srs") {
						echo "<tr class='aktiivi'>$jarjlisa<th>".t("Hinta 25% katteella")."</th><td align='right'>".hintapyoristys($tuote['kehahin'] / 0.75)." $yhtiorow[valkoodi]</td></tr>";
					}

					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Keskihankintahinta")." $epakurpantti</th><td align='right'>".hintapyoristys($tuote['kehahin'])." $yhtiorow[valkoodi]</td></tr>";

					if ($tuote["myyntihinta_maara"] != 0) {
						echo "<tr class='aktiivi'>$jarjlisa<th>".t("Keskihankintahinta")." $epakurpantti $tuote[myyntihinta_maara] $tuote[yksikko]</th><td align='right'>".hintapyoristys($tuote['kehahin'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
					}
				}

				$query_ale_select_lisa = generoi_alekentta_select('erikseen', 'M');

				//haetaan viimeisin hinta mill� asiakas on tuotetta ostanut
				$query =	"	SELECT tilausrivi.hinta, tilausrivi.otunnus, tilausrivi.laskutettuaika, {$query_ale_select_lisa} lasku.tunnus
								FROM tilausrivi
						   		JOIN lasku ON lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = tilausrivi.otunnus and lasku.ytunnus = '$laskurow[ytunnus]' and lasku.ovttunnus = '$laskurow[ovttunnus]' and lasku.alatila = 'X'
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi = 'L'
								and tilausrivi.kpl != 0
								and tilausrivi.tuoteno = '{$tuote['tuoteno']}'
								ORDER BY tilausrivi.tunnus desc
								LIMIT 1";
				$viimhintares = pupe_query($query);

				if (mysql_num_rows($viimhintares) != 0) {
					$viimhinta = mysql_fetch_assoc($viimhintares);

					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Viimeisin hinta")."</th><td align='right'>".hintapyoristys($viimhinta["hinta"])." $yhtiorow[valkoodi]</td></tr>";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						echo "<tr class='aktiivi'>{$jarjlisa}<th>".t("Viimeisin alennus")."{$alepostfix}</th><td align='right'>{$viimhinta["ale{$alepostfix}"]} %</td></tr>";
					}

					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Tilausnumero")."</th><td align='right'><a href='{$palvelin2}raportit/asiakkaantilaukset.php?tee=NAYTA&toim=MYYNTI&tunnus=$viimhinta[tunnus]&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$viimhinta[otunnus]</a></td></tr>";
					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Laskutettu")."</th><td align='right'>".tv1dateconv($viimhinta["laskutettuaika"])."</td></tr>";
				}

				$query = "SELECT * from tuotepaikat where yhtio = '$kukarow[yhtio]' and tuoteno = '{$tuote['tuoteno']}'";
				$tres  = pupe_query($query);
				$apu_onkomitaan = 0;

				while ($salrow = mysql_fetch_assoc($tres)) {
					$query = "	SELECT *
								FROM varastopaikat
								WHERE yhtio = '$kukarow[yhtio]'
								AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$salrow[hyllyalue]'), 5, '0'),lpad(upper('$salrow[hyllynro]'), 5, '0'))
								AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$salrow[hyllyalue]'), 5, '0'),lpad(upper('$salrow[hyllynro]'), 5, '0'))
								order by prioriteetti, nimitys";
					$nimre = pupe_query($query);
					$nimro = mysql_fetch_assoc($nimre);

					list(,,$apu_myytavissa) = saldo_myytavissa($tuote['tuoteno'], '', $nimro["tunnus"]);

					$oletus = '';
					if ($salrow['oletus'] != '') {
						$oletus = "(".t("oletusvarasto").")";
					}

					$varastomaa = '';
					if (strtoupper($nimro['maa']) != strtoupper($yhtiorow['maa'])) {
						$varastomaa = "(".strtoupper($nimro['maa']).")";
					}

					if ($apu_myytavissa != 0) {
						echo "<tr class='aktiivi'>$jarjlisa<th>$nimro[nimitys] $oletus $varastomaa</th><td align='right'>".sprintf("%01.2f", $apu_myytavissa)." $trow[yksikko]</td></tr>";
						$apu_onkomitaan++;
					}
				}

				if ($apu_onkomitaan == 0) {
					echo "<tr class='aktiivi'>$jarjlisa<th>".t("Myyt�viss�")."</th><td><font class='error'>".t("Tuote loppu")."</font></td></tr>";
				}

				echo "</table>";
			}
		}
	} #end if erkoisceisi

	// jos ollaan jo saatu tilausnumero aikaan listataan kaikki tilauksen rivit..
	if ((int) $kukarow["kesken"] != 0) {

		// tarkistetaan kuuluuko kaikki reklamaation rivit samaan varastoon
		if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] == 'U') {

			$varasto_chk_array = array();

			$query = "SELECT hyllyalue, hyllynro FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$kukarow['kesken']}'";
			$varasto_chk_res = pupe_query($query);

			while ($varasto_chk_row = mysql_fetch_assoc($varasto_chk_res)) {
				// Mihin varastoon
				$varasto_chk = kuuluukovarastoon($varasto_chk_row["hyllyalue"], $varasto_chk_row["hyllynro"]);
				$varasto_chk_array[$varasto_chk] = $varasto_chk;
			}

			if (count($varasto_chk_array) > 1) {
				echo t("VIRHE: Tuotteet eiv�t kuulu samaan varastoon"),"!<br>";
				$tilausok++;
			}
		}

		if (($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $laskurow["tunnusnippu"] > 0 and $projektilla == "") {
			$tilrivity	= "'L','E'";

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio 	= '$kukarow[yhtio]'
						and tunnusnippu = '$laskurow[tunnusnippu]'";
			$result = pupe_query($query);
			$toimrow = mysql_fetch_assoc($result);

			$tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) ";
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
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
			$result = pupe_query($query);
			$toimrow = mysql_fetch_assoc($result);

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

		$tilauksen_jarjestys = $yhtiorow['tilauksen_jarjestys'];

		if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and $toim == "TARJOUS") or $toim == "PROJEKTI") {
			$sorttauslisa = "tuotetyyppi, ";
		}
		elseif ($toim == 'EXTRANET') {
			$sorttauslisa = "tilausrivin_lisatiedot.positio, ";
		}
		else {
			if ($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5') {
				$sorttauslisa = "tilausrivi.perheid $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.perheid2 $yhtiorow[tilauksen_jarjestys_suunta],";
			}
			else {
				$sorttauslisa = "";
			}
		}

		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

		if (isset($ruutulimit) and $ruutulimit > 0) {
			list($ruutulimitalk, $ruutulimitlop) = explode("##", $ruutulimit);

			$limitlisa = "LIMIT ".($ruutulimitalk-1).", $ruutulimitlop";
		}
		else {
			$limitlisa = "";
		}

		$query  = "	SELECT count(*) rivit, count(distinct otunnus) otunnukset
					FROM tilausrivi use index (yhtio_otunnus)
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					$tunnuslisa
					and tilausrivi.tyyppi in ($tilrivity)";
		$ruuturesult = pupe_query($query);
		$ruuturow = mysql_fetch_assoc($ruuturesult);

		$rivilaskuri = $ruuturow["rivit"];

		// Tilausrivit
		$query  = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
					if (tilausrivi.laskutettuaika!='0000-00-00', kpl, varattu) varattu,
					if (tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
					tuote.myyntihinta,
					round(if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0),6) kehahin,
					tuote.kehahin kehahin_kurantti,
					tuote.sarjanumeroseuranta,
					tuote.vaaditaan_kpl2,
					tuote.yksikko,
					tuote.status,
					$sorttauskentta
					FROM tilausrivi use index (yhtio_otunnus)
					LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					$tunnuslisa
					and tilausrivi.tyyppi in ($tilrivity)
					ORDER BY tilausrivi.otunnus, $sorttauslisa sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus
					$limitlisa";
		$result = pupe_query($query);

		if ($rivilaskuri > 0) {
			if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
				if (isset($ruutulimit) and $ruutulimit > 0) {
					$rivino = $ruutulimit-1;
				}
				else {
					$rivino = 0;
				}
			}
			else {
				if (isset($ruutulimit) and $ruutulimit > 0) {
					$rivino = $rivilaskuri-($ruutulimit-1)+1;
				}
				else {
					$rivino = $rivilaskuri+1;
				}
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

			$vakquery = "	SELECT ifnull(group_concat(DISTINCT tuote.tuoteno), '') vaktuotteet
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.vakkoodi not in ('','0'))
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'
							AND tilausrivi.tyyppi = 'L'
							AND tilausrivi.var NOT IN ('P', 'J')";
			$vakresult = pupe_query($vakquery);
			$vakrow = mysql_fetch_assoc($vakresult);

			if ($vakrow['vaktuotteet'] != '') {
				if ($kukarow['extranet'] == '') {
					// jos vak-toimituksissa halutaan k�ytt�� vaihtoehtoista toimitustapaa
					if ($tm_toimitustaparow['vak_kielto'] != '' and $tm_toimitustaparow['vak_kielto'] != 'K') {

						$query = "	SELECT tunnus
									FROM toimitustapa
									WHERE yhtio = '$kukarow[yhtio]'
									AND selite = '$tm_toimitustaparow[vak_kielto]'
									AND vak_kielto = ''";
						$vak_check_res = pupe_query($query);

						// CHECK! vaihtoehtoisen toimitustavan t�ytyy sallia vak-tuotteiden toimitus
						if (mysql_num_rows($vak_check_res) == 1) {
							$query = "	UPDATE lasku SET
										toimitustapa = '$tm_toimitustaparow[vak_kielto]'
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus = '$laskurow[tunnus]'";
							$toimtapa_update_res = pupe_query($query);

							echo "<br><font class='error'>".t("HUOM: T�m� toimitustapa ei salli VAK-tuotteita")."! ($toimtapa_kv)</font><br>";
							echo "<font class='error'>$toimtapa_kv ".t("toimitustavan VAK-tuotteet toimitetaan vaihtoehtoisella toimitustavalla")." $tm_toimitustaparow[vak_kielto].</font> ";

							echo "<form name='tilaus' method='post'>";
							echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
							echo "<input type='hidden' name='mista' value='$mista'>";
							echo "<input type='hidden' name='toim' value='$toim'>";
							echo "<input type='hidden' name='tee' value='$tee'>";
							echo "<input type='submit' name='tyhjenna' value='".t("OK")."'>";
							echo "</form>";
							echo "<br/><br/>";
						}
						else {
							echo "<font class='error'>".t("VIRHE: T�m� toimitustapa ei salli VAK-tuotteita")."! ($vakrow[vaktuotteet])</font><br>";
							echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
						}
						$tilausok++;
					}
					elseif ($tm_toimitustaparow['vak_kielto'] == 'K') {
						echo "<font class='error'>".t("VIRHE: T�m� toimitustapa ei salli VAK-tuotteita")."! ($vakrow[vaktuotteet])</font><br>";
						echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
						$tilausok++;
					}
				}
				else {
					if ($tm_toimitustaparow['vak_kielto'] == 'K' or ($tm_toimitustaparow['vak_kielto'] != '' and $tm_toimitustaparow['nouto'] == '')) {
						echo "<font class='error'>".t("VIRHE: T�m� toimitustapa ei salli VAK-tuotteita")."! ($vakrow[vaktuotteet])</font><br>";
						echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
						$tilausok++;
					}
				}
			}

			echo "<br><table>";

			// Sarakem��r� ruudulla
			$sarakkeet = 0;
			$sarakkeet_alku = 0;

			// Sarakkeiden otsikot
			$headerit = "<tr>$jarjlisa<th>#</th>";
			$sarakkeet++;

			if ($toim == "TARJOUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $laskurow["tilaustyyppi"] == "T" or ($yhtiorow['tilauksen_kohteet'] == 'K' and in_array($toim, array("RIVISYOTTO")))) {
				$trivityyppi_result = t_avainsana("TRIVITYYPPI");

				if (mysql_num_rows($trivityyppi_result) > 0) {
					$headerit .= "<th>".t("Tyyppi")."</th>";
					$sarakkeet++;
				}
			}
			elseif ($yhtiorow['tilauksen_kohteet'] == 'K') {
				$headerit .= "<th>".t("Tyyppi")."</th>";
				$sarakkeet++;
			}

			if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
				$headerit .= "<th>".t("Nimitys")."</th>";
				$sarakkeet++;
			}

			if (($toim != "TARJOUS" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and (($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) or $yhtiorow['varastopaikan_lippu'] != '')) {
				$headerit .= "<th>".t("Paikka")."</th>";
				$sarakkeet++;
			}

			$headerit .= "<th>".t("Tuotenumero")."</th><th>".t("M��r�")."</th><th>".t("Var")."</th>";
			$sarakkeet += 3;

			if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {

				$headerit .= "<th>".t("Netto")."</th>";
				$sarakkeet++;

				if ($kukarow['hinnat'] >= 0) {
					$headerit .= "<th style='text-align:right;'>".t("Svh")."</th>";
					$sarakkeet++;
				}

				if ($kukarow['hinnat'] == 0) {
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$headerit .= "<th style='text-align:right;'>".t("Ale{$alepostfix}%")."</th>";
						$sarakkeet++;
					}

					$headerit .= "<th style='text-align:right;'>".t("Hinta")."</th>";
					$sarakkeet++;
				}

				$sarakkeet_alku = $sarakkeet;

				if ($kukarow['hinnat'] >= 0) {
					$headerit .= "<th style='text-align:right;'>".t("Rivihinta")."</th>";
					$sarakkeet++;
				}

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) or ($kukarow['extranet'] == '' and $kukarow["naytetaan_katteet_tilauksella"] == "O")) {

					if ($kukarow['naytetaan_katteet_tilauksella'] == "O") {
						$headerit .= "<th style='text-align:right;'>".t("Kehahinta")."</th>";
					}
					else {
					$headerit .= "<th style='text-align:right;'>".t("Kate")."</th>";
					}

					$sarakkeet++;
				}

				$headerit .= "<th style='text-align:right;'>".t("Alv%")."</th><td class='back'>&nbsp;</td>";
				$sarakkeet++;
			}
			else {
				$sarakkeet_alku = $sarakkeet;
			}
			$headerit .= "</tr>";

			if ($toim == "VALMISTAVARASTOON") {
				echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet' nowrap>";
				echo "<font class='head'>".t("Valmistusrivit").":</font>";
			}
			else {
				// jos meill� on yhti�n myyntihinnoissa alvit mukana ja meill� on alvillinen tilaus, annetaan mahdollisuus switchata listaus alvittomaksi
				if ($laskurow["alv"] != 0 and $toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON" and $kukarow['extranet'] == '') {
					echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet' nowrap>";
					echo "<font class='head'>".t("Tilausrivit").":</font>";

					$sele = array("K" => "", "E" => "");

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
						$tilausrivi_alvillisuus = "K";
					}

					echo "<form action='".$palvelin2."tilauskasittely/tilaus_myynti.php' method='post'>
 							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='mista' value='$mista'>
 							<input type='hidden' name='tee' value='$tee'>
 							<input type='hidden' name='toim' value='$toim'>
 							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
 							<input type='hidden' name='projektilla' value='$projektilla'>
 							<input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
						 	".t("Verolliset hinnat").": <input type='radio' onclick='submit();' name='tilausrivi_alvillisuus' value='K' $sele[K]>
						 	".t("Verottomat hinnat").": <input type='radio' onclick='submit();' name='tilausrivi_alvillisuus' value='E' $sele[E]>
							</form>";
				}
				else {
					echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet' nowrap>";
					echo "<font class='head'>".t("Tilausrivit").":</font>";
					$tilausrivi_alvillisuus = "";
				}
			}

			if ($rivilaskuri > 25) {

				echo "<form action='".$palvelin2."tilauskasittely/tilaus_myynti.php' method='post'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value='$mista'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
						 ".t("N�yt� rivit").": <select name='ruutulimit' onchange='submit();'>
						<option value=''>".t("Kaikki")."</option>";

				$ruuarray = array();
				$ruulask1 = 0;
				$ruulask2 = 1;
				$ruulask3 = 0;



				for ($ruulask1 = 0; $ruulask1<$rivilaskuri; $ruulask1++) {

					if ($ruulask2 == 25) {

						if ($ruutulimit == (($ruulask3+1)."##".($ruulask1+1-$ruulask3))) $ruutusel = "SELECTED";
						else $ruutusel = "";

						echo "<option value='".($ruulask3+1)."##".($ruulask1+1-$ruulask3)."' $ruutusel>".($ruulask3+1)." - ".($ruulask1+1)."</option>";

						$ruulask2 = 0;
						$ruulask3 = $ruulask1+1;
					}

					$ruulask2++;
				}

				echo "</select></form>";
			}

			echo "</td></tr>";

			$tuotetyyppi				= "";
			$positio_varattu			= "";
			$varaosatyyppi				= "";
			$vanhaid 					= "KALA";
			$borderlask					= 0;
			$pknum						= 0;
			$erikoistuote_tuoteperhe 	= array();
			$tuoteperhe_kayty 			= '';
			$edotunnus 					= 0;
			$tuotekyslinkki				= "";
			$tuotekyslinkkilisa			= "";

			if ($kukarow["extranet"] == "") {
				$query = "	SELECT tunnus, alanimi
							from oikeu
							where yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'tuote.php'
							ORDER BY alanimi
							LIMIT 1";
				$tarkres = pupe_query($query);

				if (mysql_num_rows($tarkres) > 0) {

					$tuotekyslinkki = "tuote.php";

					$tarkrow = mysql_fetch_assoc($tarkres);

					if ($tarkrow["alanimi"] != "") {
						$tuotekyslinkkilisa = "toim=$tarkrow[alanimi]&";
					}
				}
				else {
					$query = "	SELECT tunnus
								from oikeu
								where yhtio	= '$kukarow[yhtio]'
								and kuka	= '$kukarow[kuka]'
								and nimi	= 'tuvar.php'
								LIMIT 1";
					$tarkres = pupe_query($query);

					if (mysql_num_rows($tarkres) > 0) {
						$tuotekyslinkki = "tuvar.php";
					}
					else {
						$tuotekyslinkki = "";
					}
				}
			}

			if ($toim == 'EXTRANET' and $kukarow['extranet'] != '') {
				$query = "	SELECT extranet_tilaus_varaa_saldoa
							FROM asiakas
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$laskurow['liitostunnus']}'";
				$ext_tilaus_var_chk = pupe_query($query);
				$ext_tilaus_var_row = mysql_fetch_assoc($ext_tilaus_var_chk);

				$ei_saldoa_varausaika = '';

				if ($ext_tilaus_var_row['extranet_tilaus_varaa_saldoa'] != 'X') {
					if ($ext_tilaus_var_row['extranet_tilaus_varaa_saldoa'] == '') {
						if ($yhtiorow['extranet_tilaus_varaa_saldoa'] != '') {
							$ei_saldoa_varausaika = $yhtiorow['extranet_tilaus_varaa_saldoa'];
						}
					}
					else {
						$ei_saldoa_varausaika = $ext_tilaus_var_row['extranet_tilaus_varaa_saldoa'];
					}
				}
			}

			while ($row = mysql_fetch_assoc($result)) {

				$vastaavattuotteet = 0;

				if (strpos($row['sorttauskentta'], '����������������������') !== FALSE) {
					$erikoistuote_tuoteperhe[$row['perheid']] = $row['sorttauskentta'];
				}

				// voidaan lukita t�m� tilausrivi
				if ($row["uusiotunnus"] > 0 or $laskurow["tunnus"] != $row["otunnus"] or ($laskurow["tila"] == "V" and $row["kpl"] != 0)) {
					$muokkauslukko_rivi = "LUKOSSA";
				}
				else {
					$muokkauslukko_rivi = "";
				}

				// Rivin tarkistukset
				if ($muokkauslukko == "" and $muokkauslukko_rivi == "") {
					require('tarkistarivi.inc');

					//tarkistarivi.inc:st� saadaan $trow jossa on select * from tuote
				}

				if ($edotunnus == 0 or $edotunnus != $row["otunnus"]) {
					if ($edotunnus > 0) echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
					if ($ruuturow["otunnukset"] > 1) echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'>".t("Toimitus").": $row[otunnus]</td></tr>";
					echo $headerit;
				}

				$edotunnus = $row["otunnus"];

				if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and $toim == "TARJOUS") or $toim == "PROJEKTI") {
					if ($tuotetyyppi == "" and $row["tuotetyyppi"] == '2 Ty�t') {
						$tuotetyyppi = 1;

						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='head'>".t("Ty�t")."</font>:</td></tr>";
					}
				}
				elseif ($toim == 'EXTRANET' and $kukarow['extranet'] != '') {
					if ($positio_varattu == '' and $row['positio'] == 'Ei varaa saldoa') {
						$positio_varattu = 1;

						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='head'>",t("Umpeutuneet tilausrivit"),"</font>:</td></tr>";
						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='message'>",t("Pahoittelumme! Alla olevien tilausrivien varausajat ovat umpeutuneet");

						if ($ei_saldoa_varausaika != '') {
							echo " (",t("varausaika")," $ei_saldoa_varausaika ",t("tuntia"),")";
						}

						echo "</font></td></tr>";
					}
				}

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
					$row['varattu'] = $row['kpl'];
				}

				//K��nnet��n t�n rivin hinta oikeeseen valuuttaan
				$row["kotihinta"] = $row["hinta"];

				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
					$row["hinta"] = hintapyoristys(laskuval($row["hinta"], $laskurow["vienti_kurssi"]));
				}

				// T�n rivin rivihinta
				$summa 		= $row["hinta"] * ($row["varattu"]+$row["jt"]) * generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');
				$kotisumma	= $row["kotihinta"] * ($row["varattu"] + $row["jt"]) * generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');

				// T�n rivin alviton rivihinta
				if ($yhtiorow["alv_kasittely"] == '') {

					// Jos meill� on marginaalimyynti�/k��nteinen alv
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

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					if ($row["ale{$alepostfix}"] == 0.00) $row["ale{$alepostfix}"] = '';
				}

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
				elseif ($yhtiorow["puute_jt_oletus"] == "H") {
					//	Tarkastetaan saldo ja informoidaan k�ytt�j��
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
					else {
						$class = "";
					}

					if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA') {
						echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
					}
				}

				if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
					$rivino++;
				}
				else {
					$rivino--;
				}

				if ($muokkauslukko_rivi == "" and $yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS","PIKATILAUS","RIVISYOTTO","VALMISTAASIAKKAALLE","SIIRTOLISTA","TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "PROJEKTI"))) {

					$buttonit =  "<div align='center'><form action='$PHP_SELF#rivi_$rivino' name='siirra_$rivino' method='post'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='ruutulimit' value='$ruutulimit'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='mista' value='$mista'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
									<input type='hidden' name='menutila' value='$menutila'>
									<input type='hidden' id='rivi_$rivino' name='jarjesta' value='$rivino'>";

					$buttonit .= "<img src='".$palvelin2."pics/noimage.gif' border='0' height = '10'>";

					if ($rivino > 1) {
						$buttonit .= "	<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveUp'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png' border='0' height = '10' width = '10'></a><br>";
					}
					else {
						$buttonit .= "	<img src='".$palvelin2."pics/noimage.gif' border='0' height = '10'>";
					}

					if ($rivilaskuri > $rivino) {
						$buttonit .= "	<a href='#' onClick=\"getElementById('rivi_$rivino').value='moveDown'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png' border='0' height = '10' width = '10'></a>";
					}
					$buttonit .= "</form></div>";
				}
				else {
					$buttonit = "";
				}

				// Tuoteperheiden lapsille ei n�ytet� rivinumeroa
				if ($tilauksen_jarjestys != 'M' and $tuoteperhe_kayty != $row['perheid'] and (($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '5' or ($tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '0' and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta']))) or $row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U"))))) {

					if (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						$pklisa = " and (perheid = '$row[tunnus]' or perheid2 = '$row[tunnus]' or tunnus = '$row[tunnus]')";
					}
					elseif ($row["perheid"] == 0) {
						$pklisa = " and perheid2 = '$row[perheid2]'";
					}
					else {
						$pklisa = " and (perheid = '$row[perheid]' or perheid2 = '$row[perheid]')";
					}

					$query = "	SELECT sum(if(kommentti != '' or ('$GLOBALS[eta_yhtio]' != '' and '$koti_yhtio' = '$kukarow[yhtio]') or $vastaavattuotteet = 1, 1, 0)), count(*)
								FROM tilausrivi use index (yhtio_otunnus)
								WHERE yhtio = '$kukarow[yhtio]'
								$tunnuslisa
								$pklisa
								and tyyppi != 'D'";
					$pkres = pupe_query($query);
					$pkrow = mysql_fetch_row($pkres);

					if ($row["perheid2"] == -1) {
						$query  = "	SELECT tuoteperhe.tunnus
									FROM tuoteperhe
									WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
									and tuoteperhe.isatuoteno 	= '$row[tuoteno]'
									and tuoteperhe.tyyppi 		= 'L'";
						$lisaresult = pupe_query($query);
						$lisays = mysql_num_rows($lisaresult);
					}
					else {
						$lisays = 0;
					}

					$pkrow[1] += $lisays;

					$pknum = $pkrow[0] + $pkrow[1];
					$borderlask = $pkrow[1];

					echo "<tr>";

					if ($jarjlisa != "") {
						echo "<td rowspan='$pknum' width='10' class='back'>$buttonit </td>";
					}

					$echorivino = $rivino;

					if ($yhtiorow['rivinumero_syotto'] != '') {
						if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
							$echorivino .= " &raquo; ($row[tilaajanrivinro])";
						}
					}

					if ($toim != "TARJOUS") {
						if ($muokkauslukko_rivi == "" and $row["toimitettuaika"] == '0000-00-00 00:00:00' and $row["uusiotunnus"] == 0 and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
							$query = " 	SELECT lasku.tunnus
										FROM lasku
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.tunnusnippu = '$laskurow[tunnusnippu]'
										and lasku.tila IN ('L','N','A','T','G','S','V','W','O')
										and lasku.alatila != 'X'";
							$toimres = pupe_query($query);

							if (mysql_num_rows($toimres) > 1) {
								$echorivino .= " &raquo; <form action='$PHP_SELF' method='post'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='mista' 			value = '$mista'>
										<input type='hidden' name='edtilausnumero' 	value = '$row[otunnus]'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<select name='valitsetoimitus_vaihdarivi' onchange='submit();'>";

								while($toimrow = mysql_fetch_assoc($toimres)) {
									$sel = "";
									if ($toimrow["tunnus"] == $row["otunnus"]) {
										$sel = "selected";
									}

									$echorivino .= "<option value ='$toimrow[tunnus]' $sel>$toimrow[tunnus]</option>";
								}

								$echorivino .= "</select></form>";
							}
						}
						elseif ($muokkauslukko_rivi != ""and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
							$echorivino .= " &raquo; $row[otunnus]";
						}
					}

					// jos tuoteperheit� ei pidet� yhdess�, ei tehd� rowspannia eik� bordereita
					if ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') {
						$pknum = 0;
						$borderlask = 0;

						if ($row["kommentti"] != "" or ($GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio'])) {
							echo "<td valign='top' rowspan = '2' $class>$echorivino</td>";
						}
						else {
							echo "<td valign='top' $class>$echorivino</td>";
						}
					}
					else {
						if ($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5')) {
							$tuoteperhe_kayty = $row['perheid'];
						}
						echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;'>$echorivino</td>";
					}
				}
				// normirivit tai jos tuoteperheit� ei pidet� yhdess�, n�ytet��n lapsille rivinumerot
				elseif ($row["perheid"] == 0 and $row["perheid2"] == 0 or ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') or (($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4') and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta'] and $tuoteperhe_kayty != $row['perheid'])) {

					$echorivino = $rivino;

					if ($yhtiorow['rivinumero_syotto'] != '') {
						if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
							$echorivino .= " &raquo; ($row[tilaajanrivinro])";
						}
					}

					echo "<tr>";

					if ($row["kommentti"] != "" or (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio']) or $vastaavattuotteet == 1) {
						if ($jarjlisa != "") {
							echo "<td rowspan = '2' width='15' class='back'>$buttonit</td>";
						}

						echo "<td $class rowspan = '2' valign='top'>$echorivino";
					}
					elseif ($tilauksen_jarjestys == '1' and $row['perheid'] != 0) {
						echo "<td $class>&nbsp;</td>";
					}
					else {
						if ($jarjlisa != "") {
							echo "<td width='15' class='back'>$buttonit</td>";
						}

						echo "<td  $class valign='top'>$echorivino";
					}

					if ($toim != "TARJOUS") {
						if ($muokkauslukko_rivi == "" and $row["toimitettuaika"] == '0000-00-00 00:00:00' and $row["uusiotunnus"] == 0 and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
							$query = " 	SELECT lasku.tunnus
										FROM lasku
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.tunnusnippu = '$laskurow[tunnusnippu]'
										and lasku.tila IN ('L','N','A','T','G','S','V','W','O')
										and lasku.alatila != 'X'";
							$toimres = pupe_query($query);

							if (mysql_num_rows($toimres) > 1) {
								echo " &raquo; <form action='$PHP_SELF' method='post'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='mista' 			value = '$mista'>
										<input type='hidden' name='edtilausnumero' 	value = '$row[otunnus]'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<select name='valitsetoimitus_vaihdarivi' onchange='submit();'>";

								while($toimrow = mysql_fetch_assoc($toimres)) {
									$sel = "";
									if ($toimrow["tunnus"] == $row["otunnus"]) {
										$sel = "selected";
									}

									echo "<option value ='$toimrow[tunnus]' $sel>$toimrow[tunnus]</option>";
								}

								echo "</select></form>";
							}
						}
						elseif ($muokkauslukko_rivi != ""and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
							echo " &raquo; $row[otunnus]";
						}
					}

					echo "</td>";
					$borderlask		= 0;
					$pknum			= 0;
				}

				$classlisa = "";

				if (isset($pkrow[1]) and $borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
					$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
					$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

					$borderlask--;
				}
				elseif (isset($pkrow[1]) and $borderlask == $pkrow[1] and $pkrow[1] > 0) {
					$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
					$class    .= " style='border-top: 1px solid;' ";

					$borderlask--;
				}
				elseif ($borderlask == 1) {
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
				elseif ($borderlask > 0 and $borderlask <= $pknum) {
					$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
					$class    .= " style='font-style:italic;' ";
					$borderlask--;
				}

				$vanhaid 	  = $row["perheid"];
				$trivityyulos = "";

				if ($toim == "TARJOUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $laskurow["tilaustyyppi"] == "T" or ($yhtiorow['tilauksen_kohteet'] == 'K' and in_array($toim, array("RIVISYOTTO")))) {
					if ($muokkauslukko_rivi == "" and $row["ei_nayteta"] == "") {
						if (mysql_num_rows($trivityyppi_result) > 0) {
							//annetaan valita tilausrivin tyyppi
							$trivityyulos .= "	<form action='$PHP_SELF' method='post' name='lisatietoja'>
												<input type='hidden' name='toim' value='$toim'>
												<input type='hidden' name='lopetus' value='$lopetus'>
												<input type='hidden' name='ruutulimit' value='$ruutulimit'>
												<input type='hidden' name='projektilla' value='$projektilla'>
												<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
												<input type='hidden' name='mista' value = '$mista'>
												<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
												<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
												<input type='hidden' name='menutila' value='$menutila'>
												<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE'>
												<select name='positio' onchange='submit();' $state>";

							mysql_data_seek($trivityyppi_result, 0);

							while($trrow = mysql_fetch_assoc($trivityyppi_result)) {
								$sel = "";
								if ($trrow["selite"]==$row["positio"]) $sel = 'selected';
								$trivityyulos .= "<option value='$trrow[selite]' $sel>$trrow[selitetark]</option>";
							}
							$trivityyulos .= "</select></form>";
						}
					}
					elseif (mysql_num_rows($trivityyppi_result) > 0 or $yhtiorow['tilauksen_kohteet'] == 'K') {
						$trivityyulos = "&nbsp;";
					}
				}

				if ($muokkauslukko_rivi == "" and $yhtiorow['tilauksen_kohteet'] == 'K' and in_array($toim, array("TARJOUS", "RIVISYOTTO", "PIKATILAUS", "PROJEKTI"))) {

					$valitse_positio_return_url = urlencode("tilauskasittely/tilaus_myynti.php////toim=$toim//lopetus=$lopetus//ruutulimit=$ruutulimit//projektilla=$projektilla//tilausnumero=$tilausnumero//mista=$mista//rivitunnus=$row[tunnus]//rivilaadittu=$row[laadittu]//menutila=$menutila//tila=LISATIETOJA_RIVILLE//asiakkaan_positio");

					if ($lasklisatied_row["asiakkaan_kohde"] > 0) {
						$posq = "	SELECT asiakkaan_positio.positio, concat_ws(' - ', asiakkaan_kohde.kohde, asiakkaan_positio.positio) positio_tarkenne
									FROM asiakkaan_positio
									LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=asiakkaan_positio.yhtio and asiakkaan_kohde.tunnus=asiakkaan_positio.asiakkaan_kohde
									WHERE asiakkaan_positio.yhtio = '$kukarow[yhtio]' and asiakkaan_positio.tunnus = '$row[asiakkaan_positio]'";
						$posres = pupe_query($posq);
						$info = "";
						if (mysql_num_rows($posres) == 1) {
							$posrow = mysql_fetch_assoc($posres);
							$info = "<img src='$palvelin2/pics/lullacons/info-grey-bg.png' class='info' style='float: right; border: 1px solid; margin: 2px; vertical-align: top;' onclick=\"popUp(event, 'positioselain_DIV', 0, 0, '../positioselain.php?tee=nayta_positio&positio=$row[asiakkaan_positio]');\">";
						}
						else {
							$posrow["positio"] = "Valitse positio";
						}

						$trivityyulos .= "<button class='valinta' onclick=\"popUp(event, 'positioselain_DIV', 0, 0, '../positioselain.php?kohde=$lasklisatied_row[asiakkaan_kohde]&valitse_positio_return_url=$valitse_positio_return_url'); return false;\" title='$posrow[positio_tarkenne]' $state>$posrow[positio]</button>$info";
					}
					else {
						$trivityyulos .= t("Valitse kohde otsikolta");
					}
				}
				elseif ($muokkauslukko_rivi == "" and $yhtiorow['tilauksen_kohteet'] == 'K' and $row["tyyppi"] == "W") {

					//	Voidaan manuaalisesti m��ritell� tuotteen pituus
					if ($row["vaaditaan_kpl2"] == "M") {
						$trivityyulos .= "	<form action='$PHP_SELF' method='post' name='lisatietoja'>
												<input type='hidden' name='toim' value='$toim'>
												<input type='hidden' name='lopetus' value='$lopetus'>
												<input type='hidden' name='ruutulimit' value='$ruutulimit'>
												<input type='hidden' name='projektilla' value='$projektilla'>
												<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
												<input type='hidden' name='mista' value = '$mista'>
												<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
												<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
												<input type='hidden' name='menutila' value='$menutila'>
												<input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE'>
												<input type='hidden' name='paivita_pituus' value = 'TRUE'>
												<input type='text' name='pituus' value = '$row[pituus]' size='5'>mm
											</form>";
					}
					elseif ($row["vaaditaan_kpl2"] == "P") {
						$valitse_positio_return_url = urlencode("tilauskasittely/tilaus_myynti.php?toim=$toim&lopetus=$lopetus&ruutulimit=$ruutulimit&projektilla=$projektilla&tilausnumero=$tilausnumero&mista=$mista&rivitunnus=$row[tunnus]&rivilaadittu=$row[laadittu]&menutila=$menutila&tila=LISATIETOJA_RIVILLE&asiakkaan_positio");

						$posq = "	SELECT asiakkaan_positio.positio, concat_ws(' - ', asiakkaan_kohde.kohde, asiakkaan_positio.positio) positio_tarkenne
									FROM asiakkaan_positio
									LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=asiakkaan_positio.yhtio and asiakkaan_kohde.tunnus=asiakkaan_positio.asiakkaan_kohde
									WHERE asiakkaan_positio.yhtio = '$kukarow[yhtio]' and asiakkaan_positio.tunnus = '$row[asiakkaan_positio]' and asiakkaan_positio.tunnus";
						$posres = pupe_query($posq);

						if (mysql_num_rows($posres) == 1) {
							$posrow = mysql_fetch_assoc($posres);
						}
						else {
							$posrow["positio"] = "Valitse positio";
						}


						$trivityyulos .= "<button onclick=\"popUp(event, 'positioselain_DIV', 0, 0, '../positioselain.php?kohde=$lasklisatied_row[asiakkaan_kohde]&valitse_positio_return_url=$valitse_positio_return_url'); return false;\" title='$posrow[positio_tarkenne]' $state>$posrow[positio]</button>";
						$trivityyulos .= "<img src='$palvelin2/pics/lullacons/info-grey-bg.png' class='info' style='float: right; border: 1px solid; margin: 2px; vertical-align: top;' onclick=\"popUp(event, 'positioselain_DIV', 0, 0, '../positioselain.php?tee=nayta_positio&positio=$row[asiakkaan_positio]');\">";
					}
					else {
						$trivityyulos .= "&nbsp;";
					}
				}
				elseif ($yhtiorow['tilauksen_kohteet'] == "K") {
					$trivityyulos .= "&nbsp;";
				}

				if ($trivityyulos != "") {
					echo "<td $class valign='top'>$trivityyulos</td>";
				}

				// Tuotteen nimitys n�ytet��n vain jos k�ytt�j�n resoluution on iso
				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					echo "<td $class align='left' valign='top'>".t_tuotteen_avainsanat($row, "nimitys")."</td>";
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
				elseif (($toim != "TARJOUS" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $muokkauslukko_rivi == "" and ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {

						echo "	<td $class align='left' valign='top' nowrap>";

						//valitaan n�ytet�v� lippu varaston tai yhti�n maanperusteella
						if ($selpaikkamaa != '' and $yhtiorow['varastopaikan_lippu'] != '') {
							echo "<img src='../pics/flag_icons/gif/".strtolower($selpaikkamaa).".gif'>";
						}

						echo "<form action='$PHP_SELF' method='post' name='paikat'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='mista' 			value = '$mista'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
										<input type='hidden' name='tuotenimitys' 	value = '$row[nimitys]'>
										<input type='hidden' name='menutila' 		value = '$menutila'>
										<input type='hidden' name='tila' 			value = 'MUUTA'>
										<input type='hidden' name='tapa' 			value = 'VAIHDA'>
										$paikat
									</form>";
					}
					else {

						if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
							echo "<td $class align='left' valign='top' nowrap><font class='error'><img src='../pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) </font>";
						}
						elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
							echo "<td $class align='left' valign='top' nowrap><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) </font>";
						}
						else {
							if (trim($row['hyllyalue']) == '' and trim($row['hyllynro']) == '' and trim($row['hyllyvali']) == '' and trim($row['hyllytaso']) == '' and trim($selpaikkamyytavissa) == '') {
								echo "<td $class align='left' valign='top' nowrap> ";
								if ($row['var'] == 'U' or $row['var'] == 'T') echo t("Suoratoimitus");
							}
							else {
								echo "<td $class align='left' valign='top' nowrap> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) ";
							}
						}

						if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and !in_array($row["var"], array('P','J','S','T','U'))) {
					   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
					   					FROM sarjanumeroseuranta
					   					WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
					   					and myyntirivitunnus = '$row[tunnus]'
					   					LIMIT 1";
					   		$sarjares = pupe_query($query);
					   		$sarjarow = mysql_fetch_assoc($sarjares);

							echo ", $sarjarow[era]";

							if ($trow["sarjanumeroseuranta"] == "F") {
								echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
							}
						}
					}

					if ($toim == "SIIRTOLISTA") {
						list(,, $kohde_myyssa) = saldo_myytavissa($row["tuoteno"], '', $laskurow["clearing"]);

						if ($kohde_myyssa != 0) echo "<br>".t("Kohdevarastossa")." ($kohde_myyssa)";
					}

					echo "</td>";
				}
				elseif (($toim != "TARJOUS" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $muokkauslukko_rivi == "" and ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'))) {
					if ($paikat != '') {
						echo "	<td $class align='left' valign='top'>
									<form action='$PHP_SELF' method='post'name='paikat'>
										<input type='hidden' name='toim' 			value = '$toim'>
										<input type='hidden' name='lopetus' 		value = '$lopetus'>
										<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
										<input type='hidden' name='projektilla' 	value = '$projektilla'>
										<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
										<input type='hidden' name='mista' 			value = '$mista'>
										<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
										<input type='hidden' name='rivilaadittu'	value = '$row[laadittu]'>
										<input type='hidden' name='tuotenimitys' 	value = '$row[nimitys]'>
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
				elseif (($toim != "TARJOUS" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] == '') {

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
				elseif (($toim != "TARJOUS" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {

					if ($varow['maa'] != '' ) {
						echo "<td $class align='left' valign='top'><img src='".$palvelin2."flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
					}
					else {
						echo "<td $class align='left' valign='top'></td>";
					}
				}

				if ($kukarow['extranet'] == '' and $tuotekyslinkki != "") {
					echo "<td $class valign='top'><a href='{$palvelin2}$tuotekyslinkki?".$tuotekyslinkkilisa."tee=Z&tuoteno=".urlencode($row["tuoteno"])."&toim_kutsu=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$row[tuoteno]</a>";
				}
				else {
					echo "<td $class valign='top'>$row[tuoteno]";
				}

				// N�ytet��nk� sarjanumerolinkki
				if (($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "V" or (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") and $row["varattu"] < 0)) and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {

					if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
						$tunken1 = "siirtorivitunnus";
						$tunken2 = "siirtorivitunnus";
					}
					elseif (($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") and $row["tyyppi"] != "V") {
						// Valmisteet
						$tunken1 = "ostorivitunnus";
						$tunken2 = "ostorivitunnus";
					}
					elseif (($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") and $row["tyyppi"] == "V" ) {
						// Raaka-aineet
						$tunken1 = "myyntirivitunnus";
						$tunken2 = "myyntirivitunnus";
					}
					elseif ($row["varattu"] < 0) {
						$tunken1 = "ostorivitunnus";
						$tunken2 = "myyntirivitunnus";
					}
					else {
						$tunken1 = "myyntirivitunnus";
						$tunken2 = "myyntirivitunnus";
					}

					if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "V") {
						$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
									FROM sarjanumeroseuranta
									where yhtio	 = '$kukarow[yhtio]'
									and tuoteno	 = '$row[tuoteno]'
									and $tunken1 = '$row[tunnus]'";

						$snro_ok = t("S:nro ok");
						$snro	 = t("S:nro");
					}
					else {
						$query = "	SELECT sum(era_kpl) kpl, min(sarjanumero) sarjanumero
									FROM sarjanumeroseuranta
									where yhtio	 = '$kukarow[yhtio]'
									and tuoteno	 = '$row[tuoteno]'
									and $tunken1 = '$row[tunnus]'";

						$snro_ok = t("E:nro ok");
						$snro	 = t("E:nro");
					}
					$sarjares = pupe_query($query);
					$sarjarow = mysql_fetch_assoc($sarjares);

					if ($muokkauslukko_rivi == "" and $sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
						echo " (<a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($row["tuoteno"])."&$tunken2=$row[tunnus]&from=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS#".urlencode($sarjarow["sarjanumero"])."' style='color:#00FF00;'>$snro_ok</font></a>)";
					}
					elseif ($muokkauslukko_rivi == "") {
						echo " (<a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($row["tuoteno"])."&$tunken2=$row[tunnus]&from=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$snro</a>)";

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
					if (in_array($row["var"], array('S','U','T','R', 'J'))) {
						if ($yhtiorow["varaako_jt_saldoa"] == "") {
							$kpl_ruudulle = $row['jt'] * 1;
						}
						else {
							$kpl_ruudulle = ($row['jt']+$row['varattu']) * 1;
						}
					}
					elseif ($row["var"] == 'P' or ($kukarow['extranet'] != '' and $row['positio'] == 'Ei varaa saldoa')) {
						$kpl_ruudulle = $row['tilkpl'] * 1;
					}
					else {
						$kpl_ruudulle = $row['varattu'] * 1;
					}


					if ($muokkauslukko_rivi == "" and $kpl_ruudulle < 0 and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "G")) {

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
								<input type='hidden' name='ruutulimit' value='$ruutulimit'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='mista' value = '$mista'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
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
						$muutares = pupe_query($query);
						$muutarow = mysql_fetch_assoc($muutares);

						if ($muokkauslukko_rivi == "" and $muutarow["kaytetty"] != "") {
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
									<input type='hidden' name='ruutulimit' value='$ruutulimit'>
									<input type='hidden' name='projektilla' value='$projektilla'>
									<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
									<input type='hidden' name='mista' value = '$mista'>
									<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
									<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
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
					elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
						echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle".strtolower($row["yksikko"])."</td>";
					}
					else {
						echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle</td>";
					}
				}

				if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {
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

				if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {

					echo "<td $class align='center' valign='top'>$row[netto]&nbsp;</td>";

					$hinta = $row["hinta"];
					$netto = $row["netto"];
					$kpl   = $row["varattu"]+$row["jt"];

					if ($yhtiorow["alv_kasittely"] == "") {

						// Oletuksena verolliset hinnat ja ei k��nnetty� arvonlis�verovelvollisuutta
						if ($tilausrivi_alvillisuus == "E" and $row["alv"] < 600) {
							$alvillisuus_jako = 1 + $row["alv"] / 100;
						}
						else {
							// Oletukset
							$alvillisuus_jako = 1;
						}

						$hinta    		= hintapyoristys($hinta / $alvillisuus_jako);
						$summa    		= hintapyoristys($summa / $alvillisuus_jako);
						$myyntihinta	= hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1) / $alvillisuus_jako);
					}
					else {
						// Oletuksena verottomat hinnat tai k��nnetty arvonlis�verovelvollisuus
						if ($tilausrivi_alvillisuus == "E" or $row["alv"] >= 600) {
							// Oletukset
							$alvillisuus_kerto = 1;
						}
						else {
							// Halutaan alvilliset hinnat
							$alvillisuus_kerto = 1 + $row["alv"] / 100;
						}

						$hinta    		= hintapyoristys($hinta * $alvillisuus_kerto);
						$summa    		= hintapyoristys($summa * $alvillisuus_kerto);
						$myyntihinta	= hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1) * $alvillisuus_kerto);
					}

					$kplhinta = $hinta * generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');

					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>$myyntihinta</td>";
					}
					elseif ($kukarow['hinnat'] == 0) {

						if ($myyntihinta != $hinta) $myyntihinta = hintapyoristys($myyntihinta)." (".hintapyoristys($hinta).")";
						else $myyntihinta = hintapyoristys($myyntihinta);

						echo "<td $class align='right' valign='top'>$myyntihinta</td>";

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							echo "<td {$class} align='right' valign='top'>",($row["ale{$alepostfix}"] * 1),"</td>";
						}

						echo "<td $class align='right' valign='top'>".hintapyoristys($kplhinta, 2)."</td>";
					}

					if ($kukarow['hinnat'] == 1) {
						echo "<td $class align='right' valign='top'>".hintapyoristys($myyntihinta * ($row["varattu"] + $row["jt"]))."</td>";
					}
					elseif ($kukarow['hinnat'] == 0) {
						echo "<td $class align='right' valign='top'>".hintapyoristys($summa)."</td>";
					}

					if (($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) or ($kukarow['extranet'] == '' and $kukarow["naytetaan_katteet_tilauksella"] == "O") ) {
						// T�n rivin kate
						$kate = 0;

						if ($kukarow['extranet'] == '' and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U")) {

							if ($kpl > 0) {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on myynti�
								$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);

								// Kate = Hinta - Ostohinta
								if ($kotisumma_alviton != 0) {
									$kate = sprintf('%.2f',100*($kotisumma_alviton - ($ostohinta * $kpl))/$kotisumma_alviton)."%";
								}
								elseif (($ostohinta * $kpl) != 0) {
									$kate = "-100.00%";
								}
							}
							elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "O") {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on OSTOA

								// Kate = 0
								$kate = "0%";
							}
							elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "") {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on HYVITYST�

								//T�h�n hyvitysriviin liitetyt sarjanumerot
								$query = "	SELECT sarjanumero, kaytetty
											FROM sarjanumeroseuranta
											WHERE yhtio 		= '$kukarow[yhtio]'
											and ostorivitunnus 	= '$row[tunnus]'";
								$sarjares = pupe_query($query);

								$ostohinta = 0;

								while($sarjarow = mysql_fetch_assoc($sarjares)) {

									// Haetaan hyvitett�vien myyntirivien kautta alkuper�iset ostorivit
									$query  = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
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
									$sarjares1 = pupe_query($query);
									$sarjarow1 = mysql_fetch_assoc($sarjares1);

									$ostohinta += $sarjarow1["ostohinta"];
								}

								// Kate = Hinta - Alkuper�inen ostohinta
								if ($kotisumma_alviton != 0) {
									$kate = sprintf('%.2f',100 * ($kotisumma_alviton * -1 - $ostohinta)/$kotisumma_alviton)."%";
								}
								else {
									$kate = "100.00%";
								}
							}
							else {
								$kate = "N/A";
							}
						}
						elseif ($kukarow['extranet'] == '') {
							if ($kotisumma_alviton != 0) {
								if ($kukarow['naytetaan_katteet_tilauksella'] == 'O') {
									$kate = hintapyoristys($row['kehahin']);
								}
								else {
									$kate = sprintf('%.2f',100*($kotisumma_alviton - ($row["kehahin"]*($row["varattu"]+$row["jt"])))/$kotisumma_alviton)."%";
								}
							}
							elseif ($row["kehahin"] != 0 and ($row["varattu"]+$row["jt"]) > 0) {
								$kate = "-100.00%";
							}
							elseif ($row["kehahin"] != 0 and ($row["varattu"]+$row["jt"]) < 0) {
								$kate = "100.00%";
							}
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
						echo "<td $classx align='right' valign='top' nowrap>";
						if ($row["alv"] >= 600) {
							echo t("K.V.");
					}
					else {
							echo t("M.V.");
						}
						echo "</td>";
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

				if ((((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0) and $kukarow['extranet'] != '') or $kukarow['extranet'] == '') and ($muokkauslukko == "" and $muokkauslukko_rivi == "") or $toim == "YLLAPITO") {
					if ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $row['positio'] != 'JT')) {
						echo "<form action='$PHP_SELF' method='post' name='muokkaa'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero'	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu'	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tuotenimitys' 	value = '$row[nimitys]'>
								<input type='hidden' name='tila' 			value = 'MUUTA'>
								<input type='hidden' name='tapa' 			value = 'MUOKKAA'>
								<input type='Submit' value='".t("Muokkaa")."'>
								</form> ";

						echo "<form action='$PHP_SELF' method='post' name='poista'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu'	value = '$row[laadittu]'>
								<input type='hidden' name='menutila'	 	value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'MUUTA'>
								<input type='hidden' name='tapa' 			value = 'POISTA'>
								<input type='Submit' value='".t("Poista")."'>
								</form> ";
					}

					if ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0) and $toim == 'VALMISTAVARASTOON' and $kukarow['extranet'] == '') {

						if ($row["perheid"] == 0) {
							$lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
						}

						echo "<form action='$PHP_SELF' method='post' name='lisaakertareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='tila' 			value = 'LISAAKERTARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid'	 		value = '$row[perheid]'>
								<input type='Submit' value='".t("Lis�� raaka-aine")."'>
								</form>";


						echo "<form action='$PHP_SELF' method='post' name='lisaaisakertareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='tila' 			value = 'LISAAISAKERTARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid'	 		value = '$row[perheid]'>
								<input type='Submit' value='".t("Lis�� valmiste")."'>
								</form>";
					}
					elseif ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0) or (($toim == 'SIIRTOLISTA' or $toim == "SIIRTOTYOMAARAYS" or $toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") and $row["perheid2"] == 0 and $row["perheid"] == 0)) and $kukarow['extranet'] == '') {
						if ($row["perheid2"] == 0 and $row["perheid"] == 0) {
							$lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
						}

						if ($laskurow["tila"] == "V") {
							$nappulanteksti = t("Lis�� reseptiin");
						}
						else {
							$nappulanteksti = t("Lis�� tuote");
						}

						echo "<form action='$PHP_SELF' method='post' name='lisaareseptiin'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='tila'			value = 'LISAARESEPTIIN'>
								$lisax
								<input type='hidden' name='isatunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='perheid' 		value = '$row[perheid]'>
								<input type='hidden' name='perheid2' 		value = '$row[perheid2]'>
								<input type='Submit' value='$nappulanteksti'>
								</form>";
					}

					if ($row["var"] == "J" and $selpaikkamyytavissa >= $kpl_ruudulle and $kukarow['extranet'] == '') {
						echo "<form action='$PHP_SELF' method='post' name='toimita'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu'	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'MUUTA'>
								<input type='hidden' name='tapa' 			value = 'POISJTSTA'>
								<input type='Submit' value='".t("Toimita")."'>
								</form> ";
					}

					if ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0) and $row["var"] == "P" and $saako_jalkitoimittaa == 0 and $laskurow["jtkielto"] != "o" and $row["status"] != 'P' and $row["status"] != 'X') {

						echo " <form action='$PHP_SELF' method='post' name='jalkitoimita'>
									<input type='hidden' name='toim' 			value = '$toim'>
									<input type='hidden' name='lopetus' 		value = '$lopetus'>
									<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
									<input type='hidden' name='projektilla' 	value = '$projektilla'>
									<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
									<input type='hidden' name='mista' 			value = '$mista'>
									<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
									<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
									<input type='hidden' name='menutila' 		value = '$menutila'>
									<input type='hidden' name='tila' 			value = 'MUUTA'>
									<input type='hidden' name='tapa' 			value = 'VAIHDAJAPOISTA'>
									<input type='hidden' name='var' 			value = 'J'>
									<input type='Submit' value='".t("J�lkitoim")."'>
									</form> ";
					}

					if ($row["jt"] != 0 and $yhtiorow["puute_jt_oletus"] == "J") {
						echo "<form action='$PHP_SELF' method='post' name='puutetoimita'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero'	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'MUUTA'>
								<input type='hidden' name='tapa' 			value = 'VAIHDAJAPOISTA'>
								<input type='hidden' name='var' 			value = 'P'>
								<input type='Submit' value='".t("Puute")."'>
								</form> ";
					}

					if ($saako_hyvaksya > 0) {
						echo "<form action='$PHP_SELF' method='post' name='hyvaksy'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'OOKOOAA'>
								<input type='Submit' value='".t("Hyv�ksy")."'>
								</form> ";
					}
				}
				elseif ($row["laskutettuaika"] != '0000-00-00') {
					echo "<font class='info'>".t("Laskutettu").": ".tv1dateconv($row["laskutettuaika"])."</font>";
				}
				elseif ($row["toimitettuaika"] != '0000-00-00 00:00:00') {
					echo "<font class='info'>".t("Toimitettu").": ".tv1dateconv($row["toimitettuaika"], "P")."</font>";
				}

				if ($muokkauslukko_rivi == "" and $kukarow["extranet"] == "" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "SIIRTOTYOMAARAYS") and $riviok == 0) {
					//Tutkitaan tuotteiden lis�varusteita
					$query  = "	SELECT *
								FROM tuoteperhe
								JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
								WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno 	= '$row[tuoteno]'
								and tuoteperhe.tyyppi 		= 'L'
								order by tuoteperhe.tuoteno";
					$lisaresult = pupe_query($query);

					if (mysql_num_rows($lisaresult) > 0 and ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
						echo "</tr>";

						echo "	<form action='$PHP_SELF' method='post' autocomplete='off' name='lisavarusteet'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='lisavarusteita' 	value = 'ON'>
								<input type='hidden' name='perheid2' 		value = '$row[tunnus]'>";

						$lislask = 0;

						while ($prow = mysql_fetch_assoc($lisaresult)) {

							echo "<tr>$jarjlisa";

							if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T") {
								echo "<td valign='top'>&nbsp;</td>";
							}

							if ($kukarow["resoluutio"] == 'I') {
								echo "<td valign='top'>".t_tuotteen_avainsanat($prow, 'nimitys')."</td>";
							}

							echo "<input type='hidden' name='tuoteno_array[$prow[tuoteno]]' value='$prow[tuoteno]'>";

							if ($row["var"] == "T") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='���$row[toimittajan_tunnus]'>";
							}
							if ($row["var"] == "U") {
								echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='!!!$row[toimittajan_tunnus]'>";
							}

							echo "<td valign='top'>&nbsp;</td>";
							echo "<td valign='top'>$prow[tuoteno]</td>";
							echo "<td valign='top' align='right'><input type='text' name='kpl_array[$prow[tuoteno]]' size='2' maxlength='8'></td>";

							echo "	<td valign='top'><input type='text' name='var_array[$prow[tuoteno]]'   size='2' maxlength='1'></td>
									<td valign='top'><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1'></td>
									<td valign='top'><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12'></td>";

							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								echo "<td valign='top'><input type='text' name='ale_array{$alepostfix}[$prow[tuoteno]]' size='5' maxlength='6'></td>";
							}

							$lislask++;

							if ($lislask == mysql_num_rows($lisaresult)) {
								echo "<td class='back' valign='top'><input type='submit' value='".t("Lis��")."'></td>";
								echo "</form>";
							}

							echo "</tr>";
						}
					}
					elseif ($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0) {
						echo "<form action='$PHP_SELF' method='post' autocomplete='off' name='lisaalisav'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='mista' value = '$mista'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
								<input type='hidden' name='projektilla' value='$projektilla'>";

						if ($row["perheid2"] == 0 or ($row["var"] != "T" and $row["var"] != "U")) {
							echo "<input type='hidden' name='spessuceissi' value='OK'>";
						}

						echo "	<input type='hidden' name='tila' value='LISLISAV'>
								<input type='hidden' name='rivitunnus' value='$row[tunnus]'>
								<input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
								<input type='hidden' name='menutila' value='$menutila'>
								<input type='submit' value='".t("Lis�� lis�varusteita")."'>
								</form> ";
					}
				}

				echo "</td></tr>";

				if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio']) {
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
								AND tuoteno = '{$row['tuoteno']}'";
					$tres_eta = pupe_query($query);
					$trow_eta = mysql_fetch_assoc($tres_eta);

					list($lis_hinta_eta, $lis_netto_eta, $lis_eta_ale_kaikki, $alehinta_alv_eta, $alehinta_val_eta) = alehinta($laskurow, $trow_eta, $kpl_ruudulle, '', '', '', '', $GLOBALS['eta_yhtio']);

					$row['kommentti'] .= "\n".t("Hinta").": ".hintapyoristys($lis_hinta_eta);

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$row['kommentti'] .= "\n".t("Ale")."{$alepostfix}: ".($lis_eta_ale_kaikki["ale{$alepostfix}"]*1)."%";
					}

					$row['kommentti'] .= "\n".t("Alv").": ".($row['alv']*1)."%";

					$hintapyoristys_echo = $lis_hinta_eta;

					foreach ($lis_eta_ale_kaikki as $val) {
						$hintapyoristys_echo *= (1 - ($val / 100));
					}

					$row['kommentti'] .= "\n".t("Rivihinta").": ".hintapyoristys($hintapyoristys_echo * $kpl_ruudulle);
				}

				if ($row['kommentti'] != '' or $vastaavattuotteet == 1) {

					echo "<tr>";

					if ($borderlask == 0 and $pknum > 1) {
						$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
						$kommclass2 = " style='border-bottom: 1px solid;'";
					}
					elseif ($pknum > 0) {
						$kommclass1 = " style='border-right: 1px solid;'";
						$kommclass2 = " ";
					}
					else {
						$kommclass1 = "";
						$kommclass2 = " ";
					}

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						// $cspan++; // T�ll�ist� muuttujaa ei ole koko filess�!
					}

					echo "<td $kommclass1 colspan='".($sarakkeet-1)."' valign='top'>";

					if ($row['kommentti'] != '') {
						echo t("Kommentti").":<br><font class='message'>".str_replace("\n", "<br>", $row["kommentti"])."</font><br>";
					}

					// t�h�n se taulu
					echo $vastaavat_html;

					echo "</td>";
					echo "<td class='back' valign='top' nowrap></td>";
					echo "</tr>";

				}
			}

			if ($kukarow['hinnat'] != -1 and $toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
				// Laskeskellaan tilauksen loppusummaa (mit�t�idyt ja raaka-aineet eiv�t kuulu jengiin)
				$alvquery = "	SELECT if (isnull(varastopaikat.maa) or varastopaikat.maa='', '$yhtiorow[maa]', varastopaikat.maa) maa, group_concat(tilausrivi.tunnus) rivit
								FROM tilausrivi
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = if (tilausrivi.var='S', if ((SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro)!='', (SELECT tyyppi_tieto FROM toimi WHERE yhtio = tilausrivi.yhtio and tunnus = tilausrivi.tilaajanrivinro), tilausrivi.yhtio), tilausrivi.yhtio)
			                    and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
			                    and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi in ($tilrivity)
								and tilausrivi.tyyppi not in ('D','V','M')
								$tunnuslisa
								GROUP BY 1
								ORDER BY 1";
				$alvresult = pupe_query($alvquery);

				$summa 					= 0; 	// Tilauksen verollinen loppusumma tilauksen valuutassa
				$summa_eieri			= 0;	// Tilauksen verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo  					= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa
				$arvo_eieri				= 0;	// Tilauksen veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo				= 0;	// Tilauksen veroton loppusumma yhti�n valuutassa
				$kotiarvo_eieri			= 0;	// Tilauksen veroton loppusumma yhti�n valuutassa ilman erikoisalennusta
				$kate					= 0;	// Tilauksen kate yhti�n valuutassa
				$kate_eieri				= 0;	// Tilauksen kate yhti�n valuutassa ilman erikoisalennusta
				$ostot					= 0;	// Tilauksen Ostot tilauksen valuutassa
				$ostot_eieri			= 0;	// Tilauksen Ostot tilauksen valuutassa ilman erikoisalennusta

				$summa_kotimaa 			= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa
				$summa_kotimaa_eieri 	= 0;	// Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo_kotimaa			= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa
				$arvo_kotimaa_eieri		= 0;	// Kotimaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo_kotimaa		= 0;	// Kotimaan toimitusten veroton loppusumma yhti�n valuutassa
				$kotiarvo_kotimaa_eieri	= 0;	// Kotimaan toimitusten veroton loppusumma yhti�n valuutassa ilman erikoisalennusta
				$kate_kotimaa			= 0;	// Kotimaan toimitusten kate yhti�n valuutassa
				$kate_kotimaa_eieri		= 0;	// Kotimaan toimitusten kate yhti�n valuutassa ilman erikoisalennusta

				$summa_ulkomaa			= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa
				$summa_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
				$arvo_ulkomaa			= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa
				$arvo_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
				$kotiarvo_ulkomaa		= 0;	// Ulkomaan toimitusten veroton loppusumma yhti�n valuutassa
				$kotiarvo_ulkomaa_eieri	= 0;	// Ulkomaan toimitusten veroton loppusumma yhti�n valuutassa ilman erikoisalennusta
				$kate_ulkomaa			= 0;	// Ulkomaan toimitusten kate yhti�n valuutassa
				$kate_ulkomaa_eieri		= 0;	// Ulkomaan toimitusten kate yhti�n valuutassa ilman erikoisalennusta

				// typek�st�t��n koska joskus tulee spacena.. en tajua.
				$laskurow["erikoisale"] = (float) $laskurow["erikoisale"];

				while ($alvrow = mysql_fetch_assoc($alvresult)) {

					if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
						$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
						$hinta_myy = "(tuote.myyntihinta/$laskurow[vienti_kurssi])";
					}
					else {
						$hinta_riv = "tilausrivi.hinta";
						$hinta_myy = "tuote.myyntihinta";
					}

					if ($kukarow['hinnat'] == 1) {
						$lisat = "	$hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv,
									$hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) rivihinta,
									$hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv_ei_erikoisaletta,
									$hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) rivihinta_ei_erikoisaletta";
					}
					else {

						$query_ale_lisa = generoi_alekentta('M');
						$query_ale_lisa_ei_erik = generoi_alekentta('M', '', 'ei_erikoisale');

						$lisat = "	if (tilausrivi.alv<500, {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * (tilausrivi.alv/100), 0) alv,
									{$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} rivihinta,
									if (tilausrivi.alv<500, {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa_ei_erik} * (tilausrivi.alv/100), 0) alv_ei_erikoisaletta,
									{$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa_ei_erik} rivihinta_ei_erikoisaletta,
									tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} kotirivihinta,
									tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa_ei_erik} kotirivihinta_ei_erikoisaletta";
					}

					$aquery = "	SELECT
								tuote.sarjanumeroseuranta,
								tuote.ei_saldoa,
								tuote.tuoteno,
								round(if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0),6) kehahin,
								tuote.kehahin kehahin_kurantti,
								tilausrivi.tunnus,
								tilausrivi.varattu+tilausrivi.jt varattu,
								tilausrivin_lisatiedot.osto_vai_hyvitys,
								{$lisat}
								FROM tilausrivi
								JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio='{$kukarow['yhtio']}'
								{$tunnuslisa}
								and tilausrivi.tunnus in ({$alvrow['rivit']})";
					$aresult = pupe_query($aquery);

					while ($arow = mysql_fetch_assoc($aresult)) {
						$rivikate 		= 0;	// Rivin kate yhti�n valuutassa
						$rivikate_eieri	= 0;	// Rivin kate yhti�n valuutassa ilman erikoisalennusta

						if ($arow["sarjanumeroseuranta"] == "S" or $arow["sarjanumeroseuranta"] == "U") {
							//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on myynti�
							if ($arow["varattu"] > 0) {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on myynti�
								$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $arow["tunnus"]);

								// Kate = Hinta - Ostohinta
								$rivikate 		= $arow["kotirivihinta"] - ($ostohinta * $arow["varattu"]);
								$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - ($ostohinta * $arow["varattu"]);
							}
							elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "O") {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on OSTOA

								// Kate = 0
								$rivikate 		= 0;
								$rivikate_eieri = 0;

								$ostot		 += $arow["kotirivihinta"];
								$ostot_eieri += $arow["kotirivihinta_ei_erikoisaletta"];
							}
							elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "") {
								//Jos tuotteella yll�pidet��n in-out varastonarvo ja kyseess� on HYVITYST�

								//T�h�n hyvitysriviin liitetyt sarjanumerot
								$query = "	SELECT sarjanumero, kaytetty
											FROM sarjanumeroseuranta
											WHERE yhtio 		= '$kukarow[yhtio]'
											and ostorivitunnus 	= '$arow[tunnus]'";
								$sarjares = pupe_query($query);

								$ostohinta = 0;

								while ($sarjarow = mysql_fetch_assoc($sarjares)) {

									// Haetaan hyvitett�vien myyntirivien kautta alkuper�iset ostorivit
									$query  = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
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
									$sarjares1 = pupe_query($query);
									$sarjarow1 = mysql_fetch_assoc($sarjares1);

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
							$rivikate 		= $arow["kotirivihinta"]  - ($arow["kehahin"]*$arow["varattu"]);
							$rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"]  - ($arow["kehahin"]*$arow["varattu"]);
						}

						if ($arow['varattu'] > 0) {
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

						$summa			+= hintapyoristys($arow["rivihinta"]+$arow["alv"]);
						$summa_eieri	+= hintapyoristys($arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"]);
						$arvo			+= hintapyoristys($arow["rivihinta"]);
						$arvo_eieri		+= hintapyoristys($arow["rivihinta_ei_erikoisaletta"]);
						$kotiarvo		+= hintapyoristys($arow["kotirivihinta"]);
						$kotiarvo_eieri	+= hintapyoristys($arow["kotirivihinta_ei_erikoisaletta"]);
						$kate			+= $rivikate;
						$kate_eieri		+= $rivikate_eieri;
					}
				}

				// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
				if ($arvo_eieri != '' and abs($arvo_eieri) > 0) {
					if (abs($arvo_eieri) > 9999999999.99) {
						echo "<font class='error'>",t("VIRHE: liian iso loppusumma"),"!</font><br>";
						$tilausok++;
					}
				}

				//Jos myyj� on myym�ss� ulkomaan varastoista liian pienell� summalla
				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
					$ulkom_huom = "<font class='error'>".t("HUOM! Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $laskurow[valkoodi]</font>";
				}
				elseif ($kukarow["extranet"] != "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
					if ($tm_toimitustaparow['ulkomaanlisa'] > 0) {
						$ulkom_huom = "<font class='message'>".t("Olet tilaamassa ulkomaanvarastosta, rahtikulut nousevat")." ".round(laskuval($tm_toimitustaparow["ulkomaanlisa"], $laskurow["vienti_kurssi"]),0)." $laskurow[valkoodi] ".t("verran")." </font><br>";
					}
					else {
						$ulkom_huom = "";
					}
				}
				else {
					$ulkom_huom = "";
				}

				if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
					echo "<tr>$jarjlisa
							<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
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
						<td class='back' colspan='".($sarakkeet_alku-5)."' align='right'>$ulkom_huom</td>
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
							<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
							<th colspan='5' align='right'>".t("Veroton yhteens�").":</th>
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
						<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
						<th colspan='5' align='right'>".t("Erikoisalennus")." $laskurow[erikoisale]%:</th>
						<td class='spec' align='right'>".sprintf("%.2f", ($arvo_eieri-$arvo)*-1)."</td>";

					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
					}

					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
						echo "<tr>$jarjlisa
								<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
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
							<td class='back' colspan='".($sarakkeet_alku-5)."' align='right'>$ulkom_huom</td>
							<th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
							<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*$kate_ulkomaa/($kotiarvo_ulkomaa-$ostot))."%</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
					}
					else {
						echo "<tr>$jarjlisa
								<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Veroton yhteens�").":</th>
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

				// Etsit��n asiakas
				$query = "	SELECT laskunsummapyoristys
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$asres = pupe_query($query);
				$asrow = mysql_fetch_assoc($asres);

				//K�sin sy�tetty summa johon lasku py�ristet��n
				if (abs($laskurow["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
					$summa = sprintf("%.2f",$laskurow["hinta"]);
				}

				//Jos laskun loppusumma py�ristet��n l�himp��n tasalukuun
				if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
					$summa = sprintf("%.2f",round($summa ,0));
				}

				echo "<tr>$jarjlisa
						<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
						<th colspan='5' align='right'>".t("Verollinen yhteens�").":</th>";

				echo "<td class='spec' align='right'>".sprintf("%.2f",$summa)."</td>";

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td class='spec' align='right'>&nbsp;</td>";
				}

				echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

				$as_que = "	SELECT rahtivapaa_alarajasumma
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$laskurow[liitostunnus]'
							AND rahtivapaa_alarajasumma > 0";
				$as_res = pupe_query($as_que);

				$rahtivapaa_alarajasumma = 0;

				if (mysql_num_rows($as_res) == 1) {
					$as_row = mysql_fetch_assoc($as_res);
					$rahtivapaa_alarajasumma = (float) $as_row["rahtivapaa_alarajasumma"];
				}
				else {
					$rahtivapaa_alarajasumma = (float) $yhtiorow["rahtivapaa_alarajasumma"];
				}

				if (isset($summa) and (float) $summa != 0) {
					$kaikkiyhteensa = yhtioval($summa, $laskurow["vienti_kurssi"]); // k��nnet��n yhteens�summa yhti�valuuttaan
				}
				else {
					$kaikkiyhteensa = 0;
				}

				if (($kaikkiyhteensa > $rahtivapaa_alarajasumma and $rahtivapaa_alarajasumma != 0) or $laskurow["rahtivapaa"] != "") {
					echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Rahtikulu").":</th><td class='spec' align='right'>0.00</td>";
					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right'>&nbsp;</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}
				elseif ($yhtiorow["rahti_hinnoittelu"] == "P" or $yhtiorow["rahti_hinnoittelu"] == "o") {

					// haetaan rahtimaksu
					// hae_rahtimaksu-funktio palauttaa arrayn, jossa on rahtimatriisin hinta ja alennus
					// mahdollinen alennus (i.e. asiakasalennus) tulee dummy-tuotteelta, joka voi olla sy�tettyn� toimitustavan taakse
					$rahtihinta_array = hae_rahtimaksu($laskurow["tunnus"]);

					$rahtihinta_ale_kaikki_array = array();

					// rahtihinta tulee rahtimatriisista yhti�n kotivaluutassa ja on verollinen, jos myyntihinnat ovat verollisia, tai veroton, jos myyntihinnat ovat verottomia (huom. yhti�n parametri alv_kasittely)
					if (is_array($rahtihinta_array)) {
						$rahtihinta = $rahtihinta_array['rahtihinta'];

						foreach ($rahtihinta_array['alennus'] as $ale_key => $ale_val) {
							$rahtihinta_ale_kaikki_array[$ale_key] = $ale_val;
						}
					}
					else {
						$rahtihinta = 0;
					}

					if ($rahtihinta != 0) {

						// haetaan rahtituotteen tiedot
						$query = "	SELECT *
									FROM tuote
									WHERE yhtio = '$kukarow[yhtio]'
									AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
						$rahti_trow_res = pupe_query($query);
						$rahti_trow  = mysql_fetch_assoc($rahti_trow_res);

						$netto = count($rahtihinta_ale_kaikki_array) > 0 ? '' : 'N';

						// muutetaan rahtihinta laskun valuuttaan, koska rahtihinta tulee matriisista aina yhti�n kotivaluutassa
						if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
							$rahtihinta = laskuval($rahtihinta, $laskurow["vienti_kurssi"]);
						}

						list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rahti_trow, '1', $netto, $rahtihinta, $rahtihinta_ale_kaikki_array);
						list($hinta, $alv) = alv($laskurow, $rahti_trow, $lis_hinta, '', $alehinta_alv);

						// muutetaan rahtihinta laskun valuuttaan, koska alehinta-funktio palauttaa aina hinnan yhti�n kotivaluutassa
						if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
							$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
						}

						$rahtihinta = $hinta;

						foreach ($lis_ale_kaikki as $key => $val) {
							$rahtihinta *= (1 - ($val / 100));
						}

						// jos yhti�n tuotteiden myyntihinnat ovat arvonlis�verottomia ja lasku on verollinen, lis�t��n rahtihintaan arvonlis�vero
						if ($yhtiorow['alv_kasittely'] != '' and $laskurow['alv'] != 0) {
							$rahtihinta = $rahtihinta * (1 + ($alv / 100));
						}
					}

					echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Rahtikulu")." ",t("verollinen");

					if (is_array($rahtihinta_ale_kaikki_array) and count($rahtihinta_ale_kaikki_array) > 0) {
						foreach ($rahtihinta_ale_kaikki_array as $key => $val) {
							echo " ($key $val %)";
						}
					}

					echo ":</th><td class='spec' align='right'>".sprintf("%.2f",$rahtihinta)."</td>";
					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right'>&nbsp;</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

					echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Loppusumma").":</th><td class='spec' align='right'>".sprintf("%.2f",$summa+$rahtihinta)."</td>";
					if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
						echo "<td class='spec' align='right'>&nbsp;</td>";
					}
					echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
				}

				//annetaan mahdollisuus antaa loppusumma joka jyvitet��n riveille arvoosuuden mukaan
				if ($kukarow["extranet"] == "" and (($yhtiorow["salli_jyvitys_myynnissa"] == "" and $kukarow['kassamyyja'] != '') or ($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] != '') or ($yhtiorow["salli_jyvitys_myynnissa"] == "K") or $toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI")) {

					echo "<tr>$jarjlisa";

					if ($jyvsumma == '') {
						$jyvsumma = '0.00';
					}

					if ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {
						echo "	<th colspan='2' nowrap>".t("N�yt�").":</th>
								<td colspan='2' nowrap>
								<form action='tulostakopio.php' method='post' name='tulostaform_tmyynti' id='tulostaform_tmyynti'>
								<input type='hidden' name='otunnus' value='$tilausnumero'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='tee' value='TULOSTA'>
								<input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=LASKUTATILAUS'>";

						echo "<select name='toim'>";

						if (file_exists("tulosta_tarjous.inc") and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI")) {
							echo "<option value='TARJOUS'>".t("Tarjous")."</option>";

							$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='' and nimi='tilauskasittely/tulostakopio.php' and alanimi='TARJOUS!!!VL' LIMIT 1";
							$tarkres = pupe_query($query);

							if (mysql_num_rows($tarkres) > 0) {
								echo "<option value='TARJOUS!!!VL'>".("Tarjous VL")."</option>";
							}

							$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='' and nimi='tilauskasittely/tulostakopio.php' and alanimi='TARJOUS!!!BR' LIMIT 1";
							$tarkres = pupe_query($query);

							if (mysql_num_rows($tarkres) > 0) {
								echo "<option value='TARJOUS!!!BR'>".t("Tarjous BR")."</option>";
							}
						}
						if (file_exists("tulosta_tilausvahvistus_pdf.inc")) {
							echo "<option value='TILAUSVAHVISTUS'>".t("Tilausvahvistus")."</option>";
						}
						if (file_exists("tulosta_myyntisopimus.inc")) {
							echo "<option value='MYYNTISOPIMUS'>".t("Myyntisopimus")."</option>";

							$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='' and nimi='tilauskasittely/tulostakopio.php' and alanimi='MYYNTISOPIMUS!!!VL' LIMIT 1";
							$tarkres = pupe_query($query);

							if (mysql_num_rows($tarkres) > 0) {
								echo "<option value='MYYNTISOPIMUS!!!VL'>".t("Myyntisopimus VL")."</option>";
							}

							$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='' and nimi='tilauskasittely/tulostakopio.php' and alanimi='MYYNTISOPIMUS!!!BR' LIMIT 1";
							$tarkres = pupe_query($query);

							if (mysql_num_rows($tarkres) > 0) {
								echo "<option value='MYYNTISOPIMUS!!!BR'>".t("Myyntisopimus BR")."</option>";
							}
						}
						if (file_exists("tulosta_osamaksusoppari.inc")) {
							echo "<option value='OSAMAKSUSOPIMUS'>".t("Osamaksusopimus")."</option>";
						}
						if (file_exists("tulosta_luovutustodistus.inc")) {
							echo "<option value='LUOVUTUSTODISTUS'>".t("Luovutustodistus")."</option>";
						}
						if (file_exists("tulosta_vakuutushakemus.inc")) {
							echo "<option value='VAKUUTUSHAKEMUS'>".t("Vakuutushakemus")."</option>";
						}
						if (file_exists("../tyomaarays/tulosta_tyomaarays.inc")) {
							echo "<option value='TYOMAARAYS'>".t("Ty�m��r�ys")."</option>";
						}
						if (file_exists("tulosta_rekisteriilmoitus.inc")) {
							echo "<option value='REKISTERIILMOITUS'>".t("Rekister�inti-ilmoitus")."</option>";
						}
						if ($toim == "PROJEKTI") {
							echo "<option value='TILAUSVAHVISTUS'>".t("Tilausvahvistus")."</option>";
						}

						echo "</select>
							<input type='submit' value='".t("N�yt�")."' onClick=\"js_openFormInNewWindow('tulostaform_tmyynti', 'tulosta_myynti'); return false;\">
							<input type='submit' value='".t("Tulosta")."' onClick=\"js_openFormInNewWindow('tulostaform_tmyynti', 'samewindow'); return false;\">
							</form>
							</td>";

						if ($sarakkeet_alku-9 > 0) {
							echo "<td class='back' colspan='".($sarakkeet_alku-9)."'></td>";
						}
					}
					else {
						echo "<td class='back' colspan='".($sarakkeet_alku-5)."' nowrap>&nbsp;</td>";
					}

					if (strlen(sprintf("%.2f",$summa)) > 7) {
						$koko = strlen(sprintf("%.2f",$summa));
					}
					else {
						$koko = '7';
					}

					if ($toim != "PROJEKTI") {
						echo "	<th colspan='5'>".t("Py�rist� loppusummaa").":</th>
								<td class='spec'>
								<form name='pyorista' action='$PHP_SELF' method='post' autocomplete='off'>
										<input type='hidden' name='tilausnumero' value='$tilausnumero'>
										<input type='hidden' name='mista' 		value = '$mista'>
										<input type='hidden' name='tee' 		value = 'jyvita'>
										<input type='hidden' name='toim' 		value = '$toim'>
										<input type='hidden' name='lopetus' 	value = '$lopetus'>
										<input type='hidden' name='ruutulimit' 	value = '$ruutulimit'>
										<input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>
										<input type='hidden' name='projektilla' value='$projektilla'>";

						if ($laskurow["hinta"] != 0 and (($yhtiorow["alv_kasittely"] == "" and abs($jysum - $summa) <= .50) or ($yhtiorow["alv_kasittely"] != "" and abs($jysum - $arvo) <= .50))) {
							$jysum = $laskurow["hinta"];
						}
						elseif ($tilausrivi_alvillisuus != 'E') {
							$jysum = $summa;
						}
						else {
							$jysum = $arvo;
						}

						echo "<input type='text' size='$koko' name='jysum' value='".sprintf("%.2f",$jysum)."' Style='text-align:right' $state></td>";

						if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right'>&nbsp;</td>";
						}

						echo "<td class='spec'>$laskurow[valkoodi]</td>";

						echo "<td class='back' colspan='2'><input type='submit' value='".t("Jyvit�")."' $state></form></td>";

					}
					echo "</tr>";
				}

				if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K") {
					$kulusumma = liitettyjen_kululaskujen_summa($laskurow["tunnus"]);

					if ($kulusumma != 0) {
						echo "<tr>$jarjlisa
								<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
								<th colspan='5' align='right'>".t("Liitetyt kululaskut").":</th>
								<td class='spec' align='right'>".sprintf("%.2f",$kulusumma)."</td>";

						if ($kukarow['extranet'] == '' and $kotiarvo_eieri != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) and $kotiarvo_eieri-$ostot_eieri != 0) {
							echo "<td class='spec' align='right' nowrap>".sprintf("%.2f",100*($kate-$kulusumma)/($kotiarvo-$ostot))."%</td>";
						}
						elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
							echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
						}

						echo "<td class='spec'>$yhtiorow[valkoodi]</td></tr>";
					}
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

		// JT-rivik�ytt�liittym�
		if ($jt_kayttoliittyma == "kylla" and $laskurow["liitostunnus"] > 0 and $toim != "TYOMAARAYS" and $toim != "TYOMAARAYS_ASENTAJA" and $toim != "REKLAMAATIO" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and $toim != "TARJOUS") {

			//katotaan eka halutaanko asiakkaan jt-rivej� n�kyviin
			$asjtq = "SELECT jtrivit from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
			$asjtapu = pupe_query($asjtq);
			$asjtrow = mysql_fetch_assoc($asjtapu);

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
				$vtresult = pupe_query($query);

				while ($vrow = mysql_fetch_assoc($vtresult)) {
					if ($vrow["tyyppi"] != 'E' or $laskurow["varasto"] == $vrow["tunnus"]) {
						$varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
					}
				}

				if (mysql_num_rows($vtresult) != 0 and count($varastosta) != 0) {
					if ($kukarow['extranet'] != '') {
						echo "<font class='head'>",t("Sinun j�lkitoimitusrivisi"),":</font><br/>";
					}
					require ('jtselaus.php');
				}
				else {
					echo "<font class='message'>".t("Ei toimitettavia JT-rivej�!")."</font>";
				}

			}
	    }
	}

	// tulostetaan loppuun parit napit..
	if ((int) $kukarow["kesken"] != 0 and (!isset($ruutulimit) or $ruutulimit == 0)) {
		echo "<br><table width='100%'><tr>$jarjlisa";

		if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
			echo "	<td class='back' valign='top'>
					<form name='laskuta' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LASKUTAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='submit' value='* ".t("Laskuta valitut rivit")." *'>
					</form></td>";

			echo "	<td class='back' valign='top'>
					<form name='lepaa' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LEPAAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='submit' value='* ".t("J�t� myyntitili lep��m��n")." *'>
					</form></td>";

		}

		if ($kukarow["extranet"] == "" and $muokkauslukko == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {
			echo "	<td class='back' valign='top'>
					<form name='tlepaamaan' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='submit' value='* ".t("Ty�m��r�ys lep��m��n")." *'>
					</form></td>";
		}

        if ($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "REKLAMAATIO") {
			echo "<td class='back' valign='top'>
					<form name='rlepaamaan' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='submit' value='* ".t("Reklamaatio lep��m��n")." *'>
					</form></td>";
		}

		if ($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TARJOUS"  and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			echo "<td class='back' valign='top'>";

			//	Onko viel� optiorivej�?
			$query  = "	SELECT tilausrivin_lisatiedot.tunnus
						FROM lasku
						JOIN tilausrivi ON  tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
						JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.positio = 'Optio'
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$kukarow[kesken]'";
			$optiotarkres = pupe_query($query);

			if (mysql_num_rows($optiotarkres) == 0) {

				if ($laskurow["tunnusnippu"] > 0 and (tarkista_oikeus("tilaus_myynti.php", "PROJEKTI") or tarkista_oikeus("tilaus_myynti.php", "TYOMAARAYS"))) {

					$tarjouslisa = "<font class='message'>".t("Perusta tilauksesta").":</font><br>
									<select name='perusta_tilaustyyppi'>";

					$tarjouslisa_normi = $tarjouslisa_projekti = $tarjouslisa_tyomaarays = "";

					$tarjouslisa_normi .= "<option value=''>".t("Normaalitilaus")."</option>";

					if (tarkista_oikeus("tilaus_myynti.php", "PROJEKTI")) {
						$tarjouslisa_projekti .= "<option value='PROJEKTI'>".t("Projekti")."</option>";
					}

					if (tarkista_oikeus("tilaus_myynti.php", "TYOMAARAYS")) {
						$tarjouslisa_tyomaarays .= "<option value='TYOMAARAYS'>".t("Ty�m��r�ys")."</option>";
					}

					if ($yhtiorow["hyvaksy_tarjous_tilaustyyppi"] == "T") {
						$tarjouslisa .= $tarjouslisa_tyomaarays.$tarjouslisa_normi.$tarjouslisa_projekti;
					}
					elseif ($yhtiorow["hyvaksy_tarjous_tilaustyyppi"] == "P") {
						$tarjouslisa .= $tarjouslisa_projekti.$tarjouslisa_normi.$tarjouslisa_tyomaarays;
					}
					else {
						$tarjouslisa .= $tarjouslisa_normi.$tarjouslisa_projekti.$tarjouslisa_tyomaarays;
					}

					$tarjouslisa .= "</select><br><br>";
				}
				else {
					$tarjouslisa = "";
				}

				echo "	<form name='hyvaksy' action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value = '$mista'>

						$tarjouslisa
						<input type='submit' value='".t("Hyv�ksy tarjous")."'>
						</form>";
			}
			elseif (mysql_num_rows($optiotarkres) > 0) {
				echo t("Poista optiot ennen tilauksen tekoa")."<br><br>";
			}

			echo "	<br>
					<br>
					<form name='hylkaa' action='$PHP_SELF' method='post' onsubmit=\"return confirm('Oletko varma ett� haluat hyl�t� tarjouksen $kukarow[kesken]?')\">
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='HYLKAATARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='submit' value='".t("Hylk�� tarjous")."'>
					</form>";

			echo "</td>";

		}

		//N�ytet��n tilaus valmis nappi
		if (($muokkauslukko == "" or $toim == "PROJEKTI" or $toim == "YLLAPITO") and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0 and $asiakasOnProspekti != "JOO") {

			// Jos myyj� myy todella pienell� summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
			$javalisa = "";

			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function ulkomaa_verify(){
								msg = '".t("Olet toimittamassa ulkomailla sijaitsevasta varastosta tuotteita")." $ulkomaa_kaikkiyhteensa $yhtiorow[valkoodi]! ".t("Oletko varma, ett� t�m� on fiksua")."?';
								return confirm(msg);
						}
						</SCRIPT>";

				$javalisa = "onSubmit = 'return ulkomaa_verify()'";
			}

			echo "<td class='back' valign='top'>";

			// otetaan maksuehto selville.. k�teinen muuttaa asioita
			$query = "	SELECT *
						from maksuehto
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = pupe_query($query);
			$maksuehtorow = mysql_fetch_assoc($result);

			// jos kyseess� on k�teiskauppaa
			$kateinen = "";

			if ($maksuehtorow['kateinen']!='') {
				$kateinen = "X";
			}

			if ($maksuehtorow['jaksotettu'] != '') {
				$query = "	SELECT yhtio
							FROM maksupositio
							WHERE yhtio = '$kukarow[yhtio]'
							AND otunnus = '$laskurow[jaksotettu]'";
				$jaksoresult = pupe_query($query);
			}

			if ($laskurow['sisainen'] != '' and $maksuehtorow['jaksotettu'] != '') {
				echo "<font class='error'>".t("VIRHE: Sis�isell� laskulla ei voi olla maksusopimusta!")."</font>";
			}
			elseif ($maksuehtorow['jaksotettu'] != '' and mysql_num_rows($jaksoresult) == 0) {
				echo "<font class='error'>".t("VIRHE: Tilauksella ei ole maksusopimusta!")."</font>";
			}
			elseif ($kukarow["extranet"] == "" and $toim == 'REKLAMAATIO' and $yhtiorow['reklamaation_kasittely'] == 'U') {

				if ($mista == 'keraa') {
					echo "<td class='back' valign='top'>
							<form method='post' action='keraa.php'>
							<input type='hidden' name='id' value = '$tilausnumero'>
							<input type='hidden' name='toim' value = 'VASTAANOTA_REKLAMAATIO'>
							<input type='hidden' name='lasku_yhtio' value = '$kukarow[yhtio]'>
							<input type='submit' name='tila' value = '".t("Takaisin Hyllytykseen")."'>";
					echo "</form></td>";
				}
				else {
					echo "<td class='back' valign='top'>
							<form name='rlepaamaan' action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
							<input type='hidden' name='projektilla' value='$projektilla'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='mista' value = '$mista'>";

					if ($mista == 'vastaanota' and ($laskurow["alatila"] == "A" or $laskurow["alatila"] == "B" or $laskurow["alatila"] == "C")) {
						echo "<input type='hidden' name='tee' value='VASTAANOTTO'>";
						echo "<input type='submit' value='* ".t("Reklamaatio Vastaanotettu")." *'>";
					}
					elseif ($mista != 'vastaanota' and ($laskurow["alatila"] == "" or $laskurow["alatila"] == "A")) {
						echo "<input type='hidden' name='tee' value='ODOTTAA'>";
						echo "<input type='submit' value='* ".t("Reklamaatio Odottaa Tuotteita saapuvaksi")." *'>";
					}

					echo "</form></td>";
				}
			}
			elseif ($toim != 'REKLAMAATIO' or $yhtiorow['reklamaation_kasittely'] != 'U') {

				echo "
					<form name='kaikkyht' action='$PHP_SELF' method='post' $javalisa>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='VALMIS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='hidden' name='rahtipainohinta' value='$rahtihinta'>
					<input type='hidden' name='kaikkiyhteensa' value='".sprintf('%.2f',$summa)."'>";

				if ($arvo_ulkomaa != 0) {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
				}
				else {
					echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='NO'>";
				}

				echo "<input type='hidden' name='kateinen' value='$kateinen'>";

				if (($kateinen == "X" and $kukarow["kassamyyja"] != "") or $laskurow["sisainen"] != "") {
					$laskelisa = " / ".t("Laskuta")." $otsikko";
				}
				else {
					$laskelisa = "";
				}

				echo "<input type='submit' ACCESSKEY='V' value='$otsikko ".t("valmis")."$laskelisa'>";

				if ($kukarow["extranet"] == "" and ($yhtiorow["tee_osto_myyntitilaukselta"] == "Z" or $yhtiorow["tee_osto_myyntitilaukselta"] == "Q") and in_array($toim, array("PROJEKTI","RIVISYOTTO", "PIKATILAUS"))) {
					echo "<input type='submit' name='tee_osto' value='$otsikko ".t("valmis")." & ".t("Tee tilauksesta ostotilaus")."'> ";
				}

				if (in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS")) and $kukarow["extranet"] == "" and $kateinen == 'X' and ($kukarow["kassamyyja"] != '' or $kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "")) {

					if (($kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "")) {
						echo "<br><br>".t("Valitse kassalipas").":<br>
								<select name='kertakassa'>
								<option value='EI_KASSAMYYNTIA'>".t("Ei kassamyynti�")."</option>";

						$query = "	SELECT *
									FROM kassalipas
									WHERE yhtio = '$kukarow[yhtio]'
									ORDER BY nimi";
						$vares = pupe_query($query);

						while ($varow = mysql_fetch_assoc($vares)) {
							$sel='';

							if ($varow["tunnus"] == $laskurow["kassalipas"]) {
								$sel = 'selected';
							}

							echo "<option value='$varow[tunnus]' $sel>$varow[nimi]</option>";
						}

						echo "</select> ";
					}

					echo "<br><br>".t("Kuittikopio").":<br><select name='valittu_kopio_tulostin'>";
					echo "<option value=''>".t("Ei kuittikopiota")."</option>";

					$querykieli = "	SELECT *
									FROM kirjoittimet
									WHERE yhtio = '$kukarow[yhtio]'
									ORDER BY kirjoitin";
					$kires = pupe_query($querykieli);

					while ($kirow = mysql_fetch_assoc($kires)) {
						echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
					}

					echo "</select>";
				}

				echo "</form>";
			}

			echo "</td>";
		}
		elseif ($sarjapuuttuu > 0) {
			echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu sarjanumeroita!")."</font>";
		}

		if ($kukarow['extranet'] != '' and $laskurow["liitostunnus"] > 0 and $tilausok != 0 and $rivilaskuri > 0) {
			$query = "	SELECT tilausrivi.varattu
						FROM tilausrivi
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '$tilausnumero'";
			$varattu_check_res = pupe_query($query);

			$varattu_nollana = false;

			while ($varattu_check_row = mysql_fetch_assoc($varattu_check_res)) {
				if ($varattu_check_row['varattu'] == 0) $varattu_nollana = true;
			}

			if ($varattu_nollana) {
				echo "<td class='back' valign='top'>";
				echo "
					<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='tee' value='PALAUTA_SIIVOTUT'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='hidden' name='takaisin' value = '$takaisin'>";
				echo "<input type='submit' value='",t("Palauta tilaukselle"),"'>";
				echo "</form>";
				echo "</td>";
			}
		}

		//	Projekti voidaan poistaa vain jos meill� ei ole sill� mit��n toimituksia
		if ($laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
			$query = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
			$abures = pupe_query($query);

			$projektilask = mysql_num_rows($abures);
		}
		else {
			$projektilask = 0;
		}

		if (($muokkauslukko == "" or $myyntikielto != '') and ($toim != "PROJEKTI" or ($toim == "PROJEKTI" and $projektilask == 0)) and $kukarow["mitatoi_tilauksia"] == "") {
			echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
								msg = '".t("Haluatko todella poistaa t�m�n tietueen?")."';
								return confirm(msg);
						}
				</SCRIPT>";

			echo "<td align='right' class='back' valign='top'>
					<form name='mitatoikokonaan' action='$PHP_SELF' method='post' onSubmit = 'return verify();'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='ruutulimit' value = '$ruutulimit'>
					<input type='hidden' name='projektilla' value='$projektilla'>
					<input type='hidden' name='tee' value='POISTA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='mista' value = '$mista'>
					<input type='submit' value='* ".t("Mit�t�i koko")." $otsikko *'>
					</form></td>";
		}

		echo "</tr>";

		if ($kukarow['extranet'] != "" and $kukarow['hyvaksyja'] != '') {
			echo "	<tr>
						<td align='left' class='back' valign='top'>
						<form action='tulostakopio.php' method='post' name='tulostakopio'>
						<input type='hidden' name='otunnus' value='$tilausnumero'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='mista' value = '$mista'>
						<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
						<input type='hidden' name='toim' value='TILAUSVAHVISTUS'>
						<input type='hidden' name='tee' value='NAYTATILAUS'>
						<input type='hidden' name='extranet_tilausvahvistus' value='1'>
						<input type='submit' name='NAYTATILAUS' value='".t("N�yt� Tilausvahvistus")."'>
						</form>
						</td>
					</tr>";
		}

		echo "</table>";

	}
}

if (@include("inc/footer.inc"));
elseif (@include("footer.inc"));
else exit;

?>