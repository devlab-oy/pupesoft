<?php

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;

	if (@include("verkkokauppa/ostoskori.inc")) {
		$kori_polku = "../verkkokauppa/ostoskori.php";
	}
	elseif (@include("ostoskori.inc")) {
		$kori_polku = "ostoskori.php";

		if ($tultiin == "futur") {
			$kori_polku .= "?ostoskori=".$ostoskori."&tultiin=".$tultiin;
		}
	}
	else exit;

	// Liitetiedostot popup
	if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
		liite_popup("AK", $tuotetunnus, $width, $height);
	}
	else {
		liite_popup("JS");
	}

	if (function_exists("js_popup")) {
		echo js_popup(-100);
	}

	echo "<SCRIPT type='text/javascript'>
			<!--
				function sarjanumeronlisatiedot_popup(tunnus) {
					window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=800,height=600');
				}
			//-->
			</SCRIPT>";

	if (isset($toiminto) and $toiminto == "sarjanumeronlisatiedot_popup") {
		@include('sarjanumeron_lisatiedot_popup.inc');

		if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
			$hinnat = 'MY';
		}
		else {
			$hinnat = '';
		}

		list($divitx, , , ,) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');
		echo "$divitx";
		exit;
	}

	if ($verkkokauppa == "") {
		// selite 		= k‰ytet‰‰nkˆ uutta vai vanhaa ulkoasua
		// selitetark 	= n‰ytett‰v‰t monivalintalaatikot, jos tyhj‰‰, otetaan oletus alhaalla
		// selitetark_2 = mitk‰ n‰ytett‰vist‰ monivalintalaatikoista on normaaleja alasvetovalikoita
		$query = "	SELECT selite, selitetark, REPLACE(selitetark_2, ', ', ',') selitetark_2
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji = 'HAE_JA_SELAA'
					AND selite != ''";
		$hae_ja_selaa_result = pupe_query($query);
		$hae_ja_selaa_row = mysql_fetch_assoc($hae_ja_selaa_result);

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "<font class='head'>".t("TUOTEHAKU").":</font><br/><br/>";
		}
		else {
			echo "<font class='head'>".t("ETSI JA SELAA TUOTTEITA").":</font><hr>";
		}
	}

	if (!isset($toim_kutsu)) {
		$toim_kutsu = '';
	}

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

	$query    = "	SELECT *
					from lasku
					where tunnus = '$kukarow[kesken]'
					and yhtio	 = '$kukarow[yhtio]'";
	$result   = pupe_query($query);
	$laskurow = mysql_fetch_assoc($result);

	if ($verkkokauppa == "") {
		if (!isset($ostoskori)) {
			$ostoskori = '';
		}

		if (is_numeric($ostoskori)) {
			echo "<table><tr><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value='poistakori'>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("Tyhjenn‰ ostoskori")."'>
					</form>";
			echo "</td><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value=''>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("N‰yt‰ ostoskori")."'>
					</form>";
			echo "</td></tr></table>";
		}
		elseif ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {

			echo "	<form method='post' action='".$palvelin2."tilauskasittely/tilaus_osto.php'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<input type='hidden' name='tee' value='AKTIVOI'>
					<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
					<input type='submit' value='".t("Takaisin tilaukselle")."'>
					</form><br><br>";
		}
		elseif ($kukarow["kuka"] != "" and $laskurow["tila"] != "" and $laskurow["tila"] != "K" and $toim_kutsu != "") {

			if ($kukarow["extranet"] != "") {
				if ($yhtiorow['reklamaation_kasittely'] == 'U' and $toim == 'EXTRANET_REKLAMAATIO') {
					$toim_kutsu = "EXTRANET_REKLAMAATIO";
				}
				else {
					$toim_kutsu = "EXTRANET";
				}

				$tilauskasittely = "";
			}
			else {
				$tilauskasittely = "tilauskasittely/";
			}

			echo "	<form method='post' action='".$palvelin2.$tilauskasittely."tilaus_myynti.php'>
					<input type='hidden' name='toim' value='$toim_kutsu'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
					<input type='hidden' name='tyojono' value='$tyojono'>
					<input type='submit' value='".t("Takaisin tilaukselle")."'>
					</form><br><br>";
		}
	}

	if (!isset($tee)) {
		$tee = '';
	}

	// Tarkistetaan tilausrivi
	if (($tee == 'TI' or is_numeric($ostoskori)) and isset($tilkpl)) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		if ($kukarow["kesken"] != 0) {
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = pupe_query($query);
		}
		else {
			// Luodaan uusi myyntitilausotsikko
			if ($kukarow["extranet"] == "") {
				require_once("tilauskasittely/luo_myyntitilausotsikko.inc");

				if ($toim_kutsu != "") {
					$lmyytoim = $toim_kutsu;
				}
				else {
					$lmyytoim = "RIVISYOTTO";
				}

				$tilausnumero = luo_myyntitilausotsikko($lmyytoim, 0);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}
			else {
				require_once("luo_myyntitilausotsikko.inc");
				$tilausnumero = luo_myyntitilausotsikko("EXTRANET", $kukarow["oletus_asiakas"]);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}

			// haetaan avoimen tilauksen otsikko
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = pupe_query($query);
		}

		if ($kukarow["kesken"] != 0 and $laskures != '') {
			// tilauksen tiedot
			$laskurow = mysql_fetch_assoc($laskures);
		}

		if (is_numeric($ostoskori)) {
			echo "<font class='message'>".t("Lis‰t‰‰n tuotteita ostoskoriin")." $ostoskori.</font><br>";
		}
		else {
			echo "<font class='message'>".t("Lis‰t‰‰n tuotteita tilaukselle")." $kukarow[kesken].</font><br>";
		}

		// K‰yd‰‰n l‰pi formin kaikki rivit
		foreach ($tilkpl as $yht_i => $kpl) {

			$kpl = str_replace(',', '.', $kpl);

			if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0) or ($yhtiorow['reklamaation_kasittely'] == 'U' and $toim == 'EXTRANET_REKLAMAATIO' and (float) $kpl !=0)) {

				if ($yhtiorow['reklamaation_kasittely'] == 'U' and $toim == 'EXTRANET_REKLAMAATIO') {
					$kpl = abs($kpl)*-1;
				}

				// haetaan tuotteen tiedot
				$query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
				$tuoteres = pupe_query($query);

				if (mysql_num_rows($tuoteres) == 0) {
					echo "<font class='error'>".t("Tuotetta %s ei lˆydy","", $tiltuoteno[$yht_i])."!</font><br>";
				}
				else {
					// tuote lˆytyi ok, lis‰t‰‰n rivi
					$trow = mysql_fetch_assoc($tuoteres);

					$ytunnus         = $laskurow["ytunnus"];
					$kpl             = (float) $kpl;
					$kpl_echo 		 = (float) $kpl;
					$tuoteno         = $trow["tuoteno"];
					$yllapita_toim_stash = $toim;

					if ($toim_kutsu != "YLLAPITO") {
						$toimaika = $laskurow["toimaika"];
						$kerayspvm = $laskurow["kerayspvm"];
					}
					else {
						$toim = "YLLAPITO";
						$toimaika = "";
						$kerayspvm = "";
					}
					$hinta 		     = "";
					$netto 		     = "";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'ale'.$alepostfix} = "";
					}

					$alv		     = "";
					$var			 = "";
					$varasto 	     = $laskurow["varasto"];
					$rivitunnus		 = "";
					$korvaavakielto	 = "";
					$jtkielto 		 = $laskurow['jtkielto'];
					$varataan_saldoa = "";
					$myy_sarjatunnus = $tilsarjatunnus[$yht_i];
					$paikka			 = "";

					// Ennakkotilaukset, Tarjoukset, Yll‰pitosopimukset ja Valmistukset eiv‰t tee saldotsekki‰
					if (($verkkokauppa != "" and $verkkokauppa_saldotsk === FALSE) or $laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
						$varataan_saldoa = "EI";
					}

					// jos meill‰ on ostoskori muuttujassa numero, niin halutaan lis‰t‰ tuotteita siihen ostoskoriin
					if (is_numeric($ostoskori)) {
						lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
						$kukarow["kesken"] = "";
					}
					elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
						require ("../tilauskasittely/lisaarivi.inc");
					}
					else {
						require ("lisaarivi.inc");
					}

					$toim = $yllapita_toim_stash;
					echo "<font class='message'>".t("Lis‰ttiin")." $kpl_echo ".t_avainsana("Y", "", " and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ".t("tuotetta")." $tiltuoteno[$yht_i].</font><br>";

					//Hanskataan sarjanumerollisten tuotteiden lis‰varusteet
					if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
						require("sarjanumeron_lisavarlisays.inc");

						lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
					}
				} // tuote ok else
			} // end kpl > 0
		} // end foreach

		echo "<br>";

		$trow			 = "";
		$ytunnus         = "";
		$kpl             = "";
		$tuoteno         = "";
		$toimaika 	     = "";
		$kerayspvm	     = "";
		$hinta 		     = "";
		$netto 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$varataan_saldoa = "";
		$myy_sarjatunnus = "";
		$paikka			 = "";
		$tee 			 = "";

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = '';
		}
	}

	$jarjestys = "tuote.tuoteno";

	$lisa  				= "";
	$ulisa 				= "";
	$toimtuotteet 		= "";
	$origtuotteet 		= "";
	$poislisa_mulsel 	= "";
	$lisa_parametri		= "";
	$hinta_rajaus		= "";

	if (!isset($ojarj)) {
		$ojarj = '';
	}

	if (strlen($ojarj) > 0) {
		$ojarj = trim(mysql_real_escape_string($ojarj));

		if ($ojarj == 'tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'toim_tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'nimitys') {
			$jarjestys = 'tuote.nimitys';
		}
		elseif ($ojarj == 'osasto') {
			$jarjestys = 'tuote.osasto';
		}
		elseif ($ojarj == 'try') {
			$jarjestys = 'tuote.try';
		}
		elseif ($ojarj == 'hinta') {
			$jarjestys = 'tuote.myyntihinta';
		}
		elseif ($ojarj == 'nettohinta') {
			$jarjestys = 'tuote.nettohinta';
		}
		elseif ($ojarj == 'aleryhma') {
			$jarjestys = 'tuote.aleryhma';
		}
		elseif ($ojarj == 'status') {
			$jarjestys = 'tuote.status';
		}
		else {
			$jarjestys = 'tuote.tuoteno';
		}
	}

	if (!isset($poistetut)) {
		$poistetut = '';
	}

	if ($poistetut != "") {

		$poischeck = "CHECKED";
		$ulisa .= "&poistetut=checked";

		if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
			// N‰ytet‰‰n vain poistettuja tuotteita
			$poislisa  			= " and tuote.status in ('P','X')
									and (SELECT sum(saldo)
										 FROM tuotepaikat
										 JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio
										 and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										 and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										 and varastopaikat.tyyppi = '')
										 WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0 ";
			$hinta_rajaus  		= ($yhtiorow["yhtio"] == 'allr') ? " AND tuote.myymalahinta > tuote.myyntihinta " : " ";
			$poislisa_mulsel	= " and tuote.status in ('P','X') ";
		}
		else {
			$poislisa  		 	= "";
			//$poislisa_mulsel	= "";
		}
	}
	else {
		$poislisa  = " and (tuote.status not in ('P','X')
						or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
		//$poislisa_mulsel  = " and tuote.status not in ('P','X') ";
		$poischeck = "";
	}

	if (isset($extrapoistetut) and $extrapoistetut != "" and $kukarow["extranet"] != "" and $kukarow['asema'] == "NE") {
		$extrapoischeck = "CHECKED";
		$ulisa .= "&extrapoistetut=checked";
		$poislisa  		 	= "";
	}

	if (!isset($lisatiedot)) {
		$lisatiedot = '';
	}

	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
		$ulisa .= "&lisatiedot=checked";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
		// K‰ytet‰‰n alempana
		$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$oleasres = pupe_query($query);
		$oleasrow = mysql_fetch_assoc($oleasres);

		$query = "SELECT * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
		$olhires = pupe_query($query);
		$olhirow = mysql_fetch_assoc($olhires);

		// k‰ytt‰j‰n ma
		$oleasrow["varastomaa"] = $laskurow["toim_maa"];

		if ($oleasrow["varastomaa"] == "") {
			$oleasrow["varastomaa"] = $oleasrow["toim_maa"];
		}

		if ($oleasrow["varastomaa"] == "") {
			$oleasrow["varastomaa"] = $oleasrow["maa"];
		}

		if ($verkkokauppa != "") {

			if ($kukarow["kuka"] == "www") {
				$extra_poislisa = " and tuote.hinnastoon = 'W' ";
			}
			else {
				$extra_poislisa = " and tuote.hinnastoon in ('W','V') ";
			}

			$avainlisa = " and avainsana.nakyvyys = '' ";
		}
		else {
			$extra_poislisa = " and tuote.hinnastoon != 'E' ";
			$avainlisa = " and avainsana.jarjestys < 10000 ";
		}
	}
	else {
		$extra_poislisa = "";
		$avainlisa = "";
	}

	if (!isset($nimitys)) {
		$nimitys = '';
	}

	if (trim($nimitys) != '') {
		$nimitys = mysql_real_escape_string(trim($nimitys));
		$lisa .= " and tuote.nimitys like '%$nimitys%' ";
		$ulisa .= "&nimitys=$nimitys";
	}

	if (!isset($tuotenumero)) {
		$tuotenumero = '';
	}

	if (trim($tuotenumero) != '') {
		$tuotenumero = mysql_real_escape_string(trim($tuotenumero));

		if (isset($alkukoodilla) and $alkukoodilla != "") {
			$lisa .= " and tuote.tuoteno like '$tuotenumero%' ";
		}
		else {
			$lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
		}

		$ulisa .= "&tuotenumero=$tuotenumero";
	}

	if (!isset($toim_tuoteno)) {
		$toim_tuoteno = '';
	}

	if (trim($toim_tuoteno) != '') {
		$toim_tuoteno = mysql_real_escape_string(trim($toim_tuoteno));

		// Katsotaan lˆytyykˆ tuotenumero toimittajan vaihtoehtoisista tuotenumeroista
		$query = "	SELECT GROUP_CONCAT(DISTINCT toim_tuoteno_tunnus SEPARATOR ',') toim_tuoteno_tunnukset
					FROM tuotteen_toimittajat_tuotenumerot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tuoteno = '{$toim_tuoteno}'";
		$vaih_tuoteno_res = pupe_query($query);
		$vaih_tuoteno_row = mysql_fetch_assoc($vaih_tuoteno_res);

		$vaihtoehtoinen_tuoteno_lisa = $vaih_tuoteno_row['toim_tuoteno_tunnukset'] != '' ? " OR tunnus IN ('{$vaih_tuoteno_row['toim_tuoteno_tunnukset']}')" : "";

		//Otetaan konserniyhtiˆt hanskaan
		$query	= "	SELECT DISTINCT tuoteno
					FROM tuotteen_toimittajat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND (toim_tuoteno LIKE '%{$toim_tuoteno}%' $vaihtoehtoinen_tuoteno_lisa)
					LIMIT 500";
		$pres = pupe_query($query);

		while($prow = mysql_fetch_assoc($pres)) {
			$toimtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$toimtuotteet = substr($toimtuotteet, 0, -1);

		if ($toimtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
		}

		$ulisa .= "&toim_tuoteno=$toim_tuoteno";
	}

	if (!isset($alkuperaisnumero)) {
		$alkuperaisnumero = '';
	}

	if (trim($alkuperaisnumero) != '') {
		$alkuperaisnumero = mysql_real_escape_string(trim($alkuperaisnumero));

		$query	= "	SELECT distinct tuoteno
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					and orig_tuoteno like '%$alkuperaisnumero%'
					LIMIT 500";
		$pres = pupe_query($query);

		while($prow = mysql_fetch_assoc($pres)) {
			$origtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$origtuotteet = substr($origtuotteet, 0, -1);

		if ($origtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($origtuotteet) ";
		}

		$ulisa .= "&alkuperaisnumero=$alkuperaisnumero";
	}

	// vientikieltok‰sittely:
	// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
	// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa
	$kieltolisa = "";
	unset($vierow);

	if ($kukarow["kesken"] > 0) {
		$query  = "	SELECT if (toim_maa != '', toim_maa, maa) maa
					FROM lasku
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[kesken]'";
		$vieres = pupe_query($query);
		$vierow = mysql_fetch_assoc($vieres);
	}
	elseif ($verkkokauppa != "") {
		$vierow = array();

		if ($maa != "") {
			$vierow["maa"] = $maa;
		}
		else {
			$vierow["maa"] = $yhtiorow["maa"];
		}
	}
	elseif ($kukarow["extranet"] != "") {
		$query  = "	SELECT if (toim_maa != '', toim_maa, maa) maa
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[oletus_asiakas]'";
		$vieres = pupe_query($query);
		$vierow = mysql_fetch_assoc($vieres);
	}

	if (isset($vierow) and $vierow["maa"] != "") {
		$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
	}

	if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
		require("sarjanumeron_lisatiedot_popup.inc");
	}

	$orginaaalit = FALSE;

	if (table_exists("tuotteen_orginaalit")) {
		$query	= "	SELECT tunnus
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					LIMIT 1";
		$orginaaleja_res = pupe_query($query);

		if (mysql_num_rows($orginaaleja_res) > 0) {
			$orginaaalit = TRUE;
		}
	}

	if ($verkkokauppa == "") {

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "<div>";
		}

		echo "<form action = '?toim_kutsu=$toim_kutsu' method = 'post'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		if (!isset($tultiin)) {
			$tultiin = '';
		}

		if ($tultiin == "futur") {
			echo " <input type='hidden' name='tultiin' value='$tultiin'>";
		}

		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "<fieldset>";
			echo "<legend>",t("Pikahaku"),"</legend>";
		}

		echo "<table style='display:inline-table; padding-right:4px; padding-top:4px;' valign='top'>";

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "<tr><th>".t("Tuotenumero")."</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td>";
			echo "<th>".t("Toim tuoteno")."</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td>";

			if ($kukarow["extranet"] != "") {
				echo "<th>".t("Tarjoustuotteet")."</th>";
			}
			else {
				echo "<th>".t("Poistetut")."</th>";
			}
			echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td>";

			if ($kukarow["extranet"] != "" and $kukarow['asema'] == "NE") {
				echo "<th>".t("N‰yt‰ poistetut")."</th><td><input type='checkbox' name='extrapoistetut' id='extrapoistetut' $extrapoischeck></td>";
			}

			echo "</tr>";

			echo "<tr><th>".t("Nimitys")."</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td>";

			if ($orginaaalit) {
				echo "<th>".t("Alkuper‰isnumero")."</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td>";
			}
			else {
				echo "<th>&nbsp;</th><td>&nbsp;</td>";
			}

			echo "<th>".t("Lis‰tiedot")."</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td>";
			echo "</tr>";
		}
		else {
			echo "<tr><th>".t("Tuotenumero")."</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
			echo "<tr><th>".t("Toim tuoteno")."</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";

			if ($orginaaalit) {
				echo "<tr><th>".t("Alkuper‰isnumero")."</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td></tr>";
			}

			echo "<tr><th>".t("Nimitys")."</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
			if ($kukarow["extranet"] != "") {
				echo "<tr><th>".t("Tarjoustuotteet")."</th>";
			}
			else {
				echo "<tr><th>".t("Poistetut")."</th>";
			}
			echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
			echo "<tr><th>".t("Lis‰tiedot")."</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
		}

		echo "</table><br/>";

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "</fieldset>";

			echo "<fieldset>";
			echo "<legend>",t("Rajaa tuotteita"),"</legend>";
			echo "<span class='info'>",t("Aloita valitsemalla osasto / tuoteryhm‰"),"</span>";
		}

		echo "<br/>";

		// Monivalintalaatikot (osasto, try tuotemerkki...)
		// M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
		if (trim($hae_ja_selaa_row['selitetark']) != '') {
			$monivalintalaatikot = explode(",", $hae_ja_selaa_row['selitetark']);

			if (trim($hae_ja_selaa_row['selitetark_2'] != '')) {
				$monivalintalaatikot_normaali = explode(",", $hae_ja_selaa_row['selitetark_2']);
			}
			else {
				$monivalintalaatikot_normaali = array();
			}
		}
		else {
			// Oletus
			$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI", "MALLI/MALLITARK", "<br>DYNAAMINEN_TUOTE");
			$monivalintalaatikot_normaali = array();
		}

		require ("monivalintalaatikot.inc");

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "</fieldset>";
		}

		echo "<input type='Submit' name='submit_button' id='submit_button' value = '".t("Etsi")."'></form>";
		echo "&nbsp;<form><input type='submit' name='submit_button2' id='submit_button2' value = '".t("Tyhjenn‰")."'></form>";

		if ($hae_ja_selaa_row['selite'] == 'B') {
			echo "</div>";
		}
	}

	if ($verkkokauppa != "") {
		if ($osasto != "") {
			$lisa .= "and tuote.osasto = '$osasto' ";
			$ulisa .= "&osasto=$osasto";
		}
		if ($try != "") {
			$lisa .= "and tuote.try = '$try' ";
			$ulisa .= "&try=$try";
		}
		if ($tuotemerkki != "") {
			$lisa .= "and tuote.tuotemerkki = '$tuotemerkki' ";
			$ulisa .= "&tuotemerkki=$tuotemerkki";
		}
	}

	// Halutaanko saldot koko konsernista?
	$query = "	SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 and $yhtiorow["haejaselaa_konsernisaldot"] != "") {
		$yhtiot = array();

		while ($row = mysql_fetch_assoc($result)) {
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}

	if (isset($sort) and $sort != '') {
		$sort = trim(mysql_real_escape_string($sort));
	}

	if (!isset($sort)) {
		$sort = '';
	}

	if ($sort == 'asc') {
		$sort = 'desc';
		$edsort = 'asc';
	}
	else {
		$sort = 'asc';
		$edsort = 'desc';
	}

	if (!isset($submit_button)) {
		$submit_button = '';
	}

	if ($submit_button != '' and ($lisa != '' or $lisa_parametri != '')) {

		$tuotekyslinkki = "";

		if ($kukarow["extranet"] == "") {
			$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuote.php' LIMIT 1";
			$tarkres = pupe_query($query);

			if (mysql_num_rows($tarkres) > 0) {
				$tuotekyslinkki = "tuote.php";
			}
			else {
				$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuvar.php' LIMIT 1";
				$tarkres = pupe_query($query);

				if (mysql_num_rows($tarkres) > 0) {
					$tuotekyslinkki = "tuvar.php";
				}
				else {
					$tuotekyslinkki = "";
				}
			}
		}

		if (!function_exists("tuoteselaushaku_vastaavat_korvaavat")) {
			function tuoteselaushaku_vastaavat_korvaavat($tvk_taulu, $tvk_korvaavat, $tvk_tuoteno) {
				global $kukarow, $kieltolisa, $poislisa, $hinta_rajaus,$extra_poislisa;

				if ($tvk_taulu != "vastaavat") $kyselylisa = " and {$tvk_taulu}.tuoteno != '$tvk_tuoteno' ";
				else $kyselylisa = "";

				$query = "	SELECT
							'' tuoteperhe,
							{$tvk_taulu}.id {$tvk_taulu},
							tuote.tuoteno,
							tuote.nimitys,
							tuote.osasto,
							tuote.try,
							tuote.myyntihinta,
							tuote.myymalahinta,
							tuote.nettohinta,
							tuote.aleryhma,
							tuote.status,
							tuote.ei_saldoa,
							tuote.yksikko,
							tuote.tunnus,
							tuote.epakurantti25pvm,
							tuote.epakurantti50pvm,
							tuote.epakurantti75pvm,
							tuote.epakurantti100pvm,
							(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno ORDER BY tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
							tuote.sarjanumeroseuranta
							FROM {$tvk_taulu}
							JOIN tuote ON (tuote.yhtio={$tvk_taulu}.yhtio and tuote.tuoteno={$tvk_taulu}.tuoteno $hinta_rajaus)
							WHERE {$tvk_taulu}.yhtio = '$kukarow[yhtio]'
							and {$tvk_taulu}.id = '$tvk_korvaavat'
							$kyselylisa
							$kieltolisa
							$poislisa
							$extra_poislisa
							ORDER BY {$tvk_taulu}.jarjestys, {$tvk_taulu}.tuoteno";
				$kores = pupe_query($query);

				return $kores;
			}
		}

		if (!function_exists("tuoteselaushaku_tuoteperhe")) {
			function tuoteselaushaku_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows, $tyyppi = "P") {
				global $kukarow, $kieltolisa, $poislisa, $hinta_rajaus,$extra_poislisa;

				if (!in_array($tuoteno, $isat_array)) {
					$isat_array[] = $tuoteno;

					$query = "	SELECT
	 							'$esiisatuoteno' tuoteperhe,
								tuote.tuoteno korvaavat,
								tuote.tuoteno vastaavat,
								tuote.tuoteno,
								tuote.nimitys,
								tuote.osasto,
								tuote.try,
								tuote.myyntihinta,
								tuote.myymalahinta,
								tuote.nettohinta,
								tuote.aleryhma,
								tuote.status,
								tuote.ei_saldoa,
								tuote.yksikko,
								tuote.tunnus,
								tuote.epakurantti25pvm,
								tuote.epakurantti50pvm,
								tuote.epakurantti75pvm,
								tuote.epakurantti100pvm,
								(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
								tuote.sarjanumeroseuranta,
								tuoteperhe.tyyppi
								FROM tuoteperhe
								JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno $hinta_rajaus)
								WHERE tuoteperhe.yhtio 	  = '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno = '$tuoteno'
								AND tuoteperhe.tyyppi = '$tyyppi'
								$kieltolisa
								$poislisa
								$extra_poislisa
								ORDER BY tuoteperhe.tuoteno";
					$kores = pupe_query($query);

					while ($krow = mysql_fetch_assoc($kores)) {

						unset($krow["pjarjestys"]);

						$rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
						$kaikki_array[]	= $krow["tuoteno"];
					}
				}

				return array($isat_array, $kaikki_array, $rows);
			}
		}

		$query = "	SELECT
					if (tuote.tuoteno = '$tuotenumero', 1, if(left(tuote.tuoteno, length('$tuotenumero')) = '$tuotenumero', 2, 3)) jarjestys,
					ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
					ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'V' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') osaluettelo,
					ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
					ifnull((SELECT id FROM vastaavat use index (yhtio_tuoteno) where vastaavat.yhtio=tuote.yhtio and vastaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) vastaavat,
					tuote.tuoteno,
					tuote.nimitys,
					tuote.osasto,
					tuote.try,
					tuote.myyntihinta,
					tuote.myymalahinta,
					tuote.nettohinta,
					tuote.aleryhma,
					tuote.status,
					tuote.ei_saldoa,
					tuote.yksikko,
					tuote.tunnus,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
					tuote.sarjanumeroseuranta,
					tuote.status
					FROM tuote use index (tuoteno, nimitys)
					$lisa_parametri
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$kieltolisa
					$lisa
					$extra_poislisa
					$poislisa
					$hinta_rajaus
					ORDER BY jarjestys, $jarjestys $sort
					LIMIT 500";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			$rows = array();

			// Rakennetaan array ja laitetaan vastaavat ja korvaavat mukaan
			while ($mrow = mysql_fetch_assoc($result)) {

				if ($mrow["vastaavat"] != $mrow["tuoteno"]) {

					$kores = tuoteselaushaku_vastaavat_korvaavat("vastaavat", $mrow["vastaavat"], $mrow["tuoteno"]);

					if (mysql_num_rows($kores) > 0) {

						$vastaavamaara = mysql_num_rows($kores);

						while ($krow = mysql_fetch_assoc($kores)) {

							if (isset($vastaavamaara)) {
								$krow["vastaavamaara"] = $vastaavamaara;
								unset($vastaavamaara);
							}
							else {
								$krow["mikavastaava"] = $mrow["tuoteno"];
							}

							if (!isset($rows[$mrow["korvaavat"].$krow["tuoteno"]])) $rows[$mrow["korvaavat"].$krow["tuoteno"]] = $krow;
						}
					}
					else {
						$rows[$mrow["tuoteno"]] = $mrow;
					}
				}

				if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
					$kores = tuoteselaushaku_vastaavat_korvaavat("korvaavat", $mrow["korvaavat"], $mrow["tuoteno"]);

					if (mysql_num_rows($kores) > 0) {

						// Korvaavan is‰tuotetta ei listata uudestaan jos se on jo listattu vastaavaketjussa
						if (!isset($rows[$mrow["korvaavat"].$mrow["tuoteno"]])) $rows[$mrow["korvaavat"].$mrow["tuoteno"]] = $mrow;

						while ($krow = mysql_fetch_assoc($kores)) {

							$krow["mikakorva"] = $mrow["tuoteno"];

							if (!isset($rows[$mrow["korvaavat"].$krow["tuoteno"]])) $rows[$mrow["korvaavat"].$krow["tuoteno"]] = $krow;
						}
					}
					else {
						$rows[$mrow["tuoteno"]] = $mrow;
					}
				}

				if ($mrow["korvaavat"] == $mrow["tuoteno"] and $mrow["vastaavat"] == $mrow["tuoteno"]) {
					$rows[$mrow["tuoteno"]] = $mrow;

					if ($mrow["tuoteperhe"] == $mrow["tuoteno"]) {
						$riikoko 		= 1;
						$isat_array 	= array();
						$kaikki_array 	= array($mrow["tuoteno"]);

						for ($isa=0; $isa < $riikoko; $isa++) {
							list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'P');

							if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
								$riikoko = count($kaikki_array);
							}
						}
					}

					if ($mrow["osaluettelo"] == $mrow["tuoteno"]) {
						//$mrow["osaluettelo"] == $mrow["tuoteno"]
						$riikoko 		= 1;
						$isat_array 	= array();
						$kaikki_array 	= array($mrow["tuoteno"]);

						for ($isa=0; $isa < $riikoko; $isa++) {
							list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'V');

							if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
								$riikoko = count($kaikki_array);
							}
						}
					}
				}
			}

			if ($yhtiorow["saldo_kasittely"] == "T") {
				$saldoaikalisa = date("Y-m-d");
			}
			else {
				$saldoaikalisa = "";
			}

			if ($verkkokauppa != "") {
				echo avoin_kori();

				echo "<form id = 'lisaa' action=\"javascript:ajaxPost('lisaa', 'tuote_selaus_haku.php?', 'selain', false, true);\" name='lisaa' method='post' autocomplete='off'>";

				echo "<input type='hidden' name='submit_button' value = '1'>";
				echo "<input type='hidden' name='sort' value = '$edsort'>";
				echo "<input type='hidden' name='ojarj' value = '$ojarj'>";

				if ($osasto != "") {
					echo "<input type='hidden' name='osasto' value = '$osasto'>";
				}
				if ($try != "") {
					echo "<input type='hidden' name='try' value = '$try'>";
				}
				if ($tuotemerkki != "") {
					echo "<input type='hidden' name='tuotemerkki' value = '$tuotemerkki'>";
				}
			}
			else {
				echo "<form action='?submit_button=1&sort=$edsort&ojarj=$ojarj$ulisa' name='lisaa' method='post' autocomplete='off'>";
			}

			echo "<input type='hidden' name='tee' value = 'TI'>";
			echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
			echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

			if ($tultiin == "futur") {
				echo " <input type='hidden' name='tultiin' value='$tultiin'>";
			}

			if ($verkkokauppa == "") {
				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "<div>";
				}
				else {
					echo "<br/><br/>";
				}

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "<h3>";
				}

				if (count($mul_osasto) > 0) {

					$i = 0;

					foreach ($mul_osasto as $os) {

						if ($i != 0) echo "/ ";

						echo t_avainsana("OSASTO", "", " and avainsana.selite='$os'", "", "", "selitetark")." ";

						$i++;
					}
				}

				if (count($mul_try) > 0) {
					echo "&raquo; ";

					$i = 0;

					foreach ($mul_try as $try) {

						if ($i != 0) echo "/ ";

						echo t_avainsana("TRY", "", " and avainsana.selite='$try'", "", "", "selitetark")." ";

						$i++;
					}
				}

				if (count($mul_tme) > 0) {
					echo "&raquo; ";

					$i = 0;

					foreach ($mul_tme as $tme) {

						if ($i != 0) echo "/ ";

						echo t_avainsana("TUOTEMERKKI", "", " and avainsana.selite='$tme'", "", "", "selite")." ";

						$i++;
					}
				}

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "&raquo;  ".count($rows)." ",t("tuotetta")."</h3>";
				}
				else {
					echo "&raquo;  ".count($rows)." ",t("tuotetta")."<br/><br/>";
				}

				echo "<table>";
				echo "<tr>";

				echo "<td class='back'>&nbsp;</td>";
				echo "<th>&nbsp;</th>";

				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=tuoteno$ulisa'>".t("Tuoteno")."</a>";

				if ($lisatiedot != "") {
					echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=toim_tuoteno$ulisa'>".t("Toim Tuoteno");
				}

				echo "</th>";

				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=nimitys$ulisa'>".t("Nimitys")."</th>";
				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=osasto$ulisa'>".t("Osasto")."<br><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=try$ulisa'>".t("Try")."</th>";

				if ($kukarow['hinnat'] >= 0) {
					echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=hinta$ulisa'>".t("Hinta");

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=nettohinta$ulisa'>".t("Nettohinta");
				}

					echo "</th>";
				}

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=aleryhma$ulisa'>".t("Aleryhm‰")."<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=status$ulisa'>".t("Status")."</th>";
				}

				echo "<th>".t("Myyt‰viss‰")."</th>";

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<th>".t("Hyllyss‰")."</th>";
				}

				if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
					echo "<th>&nbsp;</th>";
				}

				echo "</tr>";
			}
			else {
				echo "<br/>";
				echo "<table style='width:100%;'>";
				echo "<tr>";

				echo "<th>".t("Tuotenro")."</th>";
				echo "<th>".t("Nimitys")."</th>";

				if ($kukarow["kuka"] != "www") {
					if ($kukarow['hinnat'] >= 0) {
						echo "<th>".t("Hinta")." / ";

						if ($yhtiorow["alv_kasittely"] != "") {
							echo t("ALV")." 0%";
						}
						else {
							echo t("Sis. ALV");
						}

						echo "</th>";
					}

					if ($kukarow["kuka"] != "www" and $verkkokauppa_saldotsk) {
						echo "<th>".t("Saldo")."</th>";
					}

					echo "<th>".t("Osta")."</th>";
				}

				echo "</tr>";
			}

			$yht_i 		= 0;
			$alask 		= 0;

			if ($verkkokauppa == "") {
				foreach ($rows as $ind => $row) {
					// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt‰
					if ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"]) and $row["osaluettelo"] == "") {
						$query	= "	SELECT sarjanumeroseuranta.*,
									sarjanumeroseuranta.tunnus sarjatunnus,
									tilausrivi_osto.tunnus osto_rivitunnus,
									tilausrivi_osto.perheid2 osto_perheid2,
									tilausrivi_osto.nimitys nimitys,
									lasku_myynti.nimi myynimi,
									tilausrivi_myynti.tyyppi,
									lasku_myynti.tunnus myytunnus
									FROM sarjanumeroseuranta
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
									LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
									LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
									WHERE sarjanumeroseuranta.yhtio in ('".implode("','", $yhtiot)."')
									and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
									and sarjanumeroseuranta.myyntirivitunnus != -1
									and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.tyyppi='T')
									and tilausrivi_osto.laskutettuaika != '0000-00-00'
									order by nimitys";
						$sarjares = pupe_query($query);

						// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt‰
						$sarjalask = 0;

						if (mysql_num_rows($sarjares) > 0) {

							while ($sarjarow = mysql_fetch_assoc($sarjares)) {
								$fnlina1 = "";

								if (($sarjarow["siirtorivitunnus"] > 0) or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

									if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
										$ztun = $sarjarow["osto_perheid2"];
									}
									else {
										$ztun = $sarjarow["siirtorivitunnus"];
									}

									$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
												FROM tilausrivi
												LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
												WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
									$siires = pupe_query($query);
									$siirow = mysql_fetch_array($siires);

									if ($siirow["tyyppi"] == "O") {
										// pultattu kiinni johonkin
										$fnlina1 = " <font class='message'>(".t("Varattu lis‰varusteena").": $siirow[tuoteno] <a href='".$palvelin2."tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."'>$siirow[sarjanumero]</a>)</font>";
									}
									elseif ($siirow["tyyppi"] == "G") {
										// jos t‰m‰ on jollain siirtolistalla
										$fnlina1 = " <font class='message'>(".t("Kesken siirtolistalla").": $siirow[otunnus])</font>";
									}
								}

								if ($sarjarow["nimitys"] != "") {
									$row["nimitys"] = $sarjarow["nimitys"];
								}

								if ($fnlina1 != "") {
									$row["nimitys"] = $sarjarow["nimitys"]."<br>".$fnlina1;
									// Sarjanumero on varattu, ei voi liitt‰ tilaukselle
									$row["sarjadisabled"] = TRUE;
								}

								if ($sarjarow["yhtio"] != $kukarow["yhtio"]) {
									$row["sarjanumero"] = $sarjarow["sarjanumero"]." ($sarjarow[yhtio])";
								}
								else {
									$row["sarjanumero"] = $sarjarow["sarjanumero"];
								}

								$row["sarjatunnus"] = $sarjarow["tunnus"];
								$row["sarjayhtio"] = $sarjarow["yhtio"];

								if ($sarjalask > 0) {
									$row["korvaavat"] = $ind.$sarjalask;
									array_splice($rows, $alask, 0, array($ind.$sarjalask => $row));
						 		}
								else {
									$rows[$ind] = $row;
								}

								$sarjalask++;
							}
						}
					}

					$alask++;
				}
			}

			$isan_kuva = '';
			foreach ($rows as &$row) {

				if ($kukarow['extranet'] != '' or $verkkokauppa != "") {
					$hae_ja_selaa_asiakas = (int) $kukarow['oletus_asiakas'];
				}
				else {
					$hae_ja_selaa_asiakas = (int) $laskurow['liitostunnus'];
				}

				if ($hae_ja_selaa_asiakas != 0) {
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$row[tuoteno]'";
					$tuotetempres = pupe_query($query);
					$temptrow = mysql_fetch_assoc($tuotetempres);

					$temp_laskurowwi = $laskurow;

					if (!is_array($temp_laskurowwi)) {
						$query = "	SELECT *
									FROM asiakas
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$kukarow[oletus_asiakas]'";
						$asiakastempres = pupe_query($query);
						$asiakastemprow = mysql_fetch_assoc($asiakastempres);

						$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
						$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
						$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
						$temp_laskurowwi['maa']				= $asiakastemprow['maa'];
					}

					$haettavat_kentat = "hinta,hintaperuste,aleperuste";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$haettavat_kentat .= ",ale{$alepostfix}";
					}

					$hinnat = alehinta($temp_laskurowwi, $temptrow, 1, '', '', '', $haettavat_kentat);

					$onko_asiakkaalla_alennuksia = FALSE;

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
							$onko_asiakkaalla_alennuksia = TRUE;
							break;
						}
					}

					// Jos tuote n‰ytet‰‰n vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei lˆydy
					if ($temptrow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
						continue;
					}
				}

				if (isset($row["sarjatunnus"]) and $row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and $verkkokauppa == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					if ($lisatiedot != "") {
						echo "<tr class='aktiivi'><td colspan='7' class='back'><br></td></tr>";
					}
					else {
						echo "<tr class='aktiivi'><td colspan='8' class='back'><br></td></tr>";
					}
				}

				echo "<tr class='aktiivi'>";

				if ($verkkokauppa == "" and isset($row["vastaavamaara"]) and $row["vastaavamaara"] > 0) {
					echo "<td style='border-top: 1px solid #555555; border-left: 1px solid #555555; border-bottom: 1px solid #555555; border-right: 1px solid #555555;' rowspan='{$row["vastaavamaara"]}' align='center'>V<br>a<br>s<br>t<br>a<br>a<br>v<br>a<br>t</td>";
				}
				elseif ($verkkokauppa == "" and !isset($row["mikavastaava"])) {
					echo "<td class='back'></td>";
				}

				$vari = "";

				if ($verkkokauppa == "" and isset($row["mikakorva"])) {
					$vari = 'spec';
					$row["nimitys"] .= "<br> * ".t("Korvaa tuotteen").": $row[mikakorva]";
				}

				if ($hae_ja_selaa_row['selite'] != 'B' and $verkkokauppa == "" and strtoupper($row["status"]) == "P") {
					$vari = "tumma";
					$row["nimitys"] .= "<br> * ".t("Poistuva tuote");
				}

				if ($yhtiorow['livetuotehaku_poistetut'] == 'Y' and ($row["epakurantti25pvm"] != 0000-00-00 or $row["epakurantti50pvm"] != 0000-00-00 or $row["epakurantti75pvm"] != 0000-00-00 or $row["epakurantti100pvm"] != 0000-00-00)) {
					$vari = 'spec';
				}

				$tuotteen_lisatiedot = tuotteen_lisatiedot($row["tuoteno"]);

				if (count($tuotteen_lisatiedot) > 0) {
					$row["nimitys"] .= "<ul>";
					foreach ($tuotteen_lisatiedot as $tuotteen_lisatiedot_arvo) {
						$row["nimitys"] .= "<li>$tuotteen_lisatiedot_arvo[kentta] &raquo; $tuotteen_lisatiedot_arvo[selite]</li>";
					}
					$row["nimitys"] .= "</ul>";
				}

				// Peek ahead
				$row_seuraava = current($rows);

				if (($row["tuoteperhe"] == $row["tuoteno"] and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"] and $row_seuraava["tuoteperhe"] != "") or
					($row["osaluettelo"] == $row["tuoteno"] and $row["osaluettelo"] != $row_seuraava["osaluettelo"] and $row_seuraava["osaluettelo"] != "")) {
					$classleft = "";
					$classmidl = "";
					$classrigh = "";
				}
				elseif ($row["tuoteperhe"] == $row["tuoteno"] or $row["osaluettelo"] == $row["tuoteno"]) {
					$classleft = "style='border-top: 1px solid #555555; border-left: 1px solid #555555;' ";
					$classmidl = "style='border-top: 1px solid #555555;' ";
					$classrigh = "style='border-top: 1px solid #555555; border-right: 1px solid #555555;' ";
				}
				elseif (($row["tuoteperhe"] != "" and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) or
						($row["osaluettelo"] != "" and $row["osaluettelo"] != $row_seuraava["osaluettelo"])) {
					$classleft = "style='border-bottom: 1px solid #555555; border-left: 1px solid #555555;' ";
					$classmidl = "style='border-bottom: 1px solid #555555;' ";
					$classrigh = "style='border-bottom: 1px solid #555555; border-right: 1px solid #555555;' ";
				}
				elseif ($row["tuoteperhe"] != '' or $row["osaluettelo"] != '') {
					$classleft = "style='border-left: 1px solid #555555;' ";
					$classmidl = "";
					$classrigh = "style='border-right: 1px solid #555555;' ";
				}
				else {
					$classleft = "";
					$classmidl = "";
					$classrigh = "";
				}

				if ($verkkokauppa == '') {
					// Onko liitetiedostoja
					$liitteet = liite_popup("TH", $row["tunnus"]);

					if ($liitteet) {
						$isan_kuva = 'lˆytyi';
					}
					else {
						$isan_kuva = '';
					}

					// jos ei lˆydet‰ kuvaa is‰tuotteelta, niin katsotaan ne lapsilta
					if (trim($liitteet) == '' and (trim($row["tuoteperhe"]) == trim($row["tuoteno"]) or trim($row["osaluettelo"]) == trim($row["tuoteno"])) and $isan_kuva != '') {

						if ($row["osaluettelo"] != "") {
							$tuoteperhe_tyyppi = "V";
						}
						else {
							$tuoteperhe_tyyppi = "P";
						}

						$query = "	SELECT tuote.tunnus
									FROM tuoteperhe
									JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno )
									WHERE tuoteperhe.yhtio 	  = '$kukarow[yhtio]'
									and tuoteperhe.isatuoteno = '$row[tuoteno]'
									and tuoteperhe.tyyppi = '$tuoteperhe_tyyppi'";
						$lapsires = pupe_query($query);

						while ($lapsirow = mysql_fetch_assoc($lapsires)) {
							// Onko lapsien liitetiedostoja
							$liitteet = liite_popup("TH", $lapsirow["tunnus"]);

							if ($liitteet != '') break;
						}
					}

					if ($liitteet != "") {
						echo "<td class='$vari' style='vertical-align: top;'>$liitteet</td>";
					}
					else {
						echo "<td class='$vari'></td>";
					}
				}


				$linkkilisa = "";

				//	Liitet‰‰n originaalitietoja
				if ($orginaaalit === TRUE) {
					$id = md5(uniqid());

					$query = "	SELECT *
								FROM tuotteen_orginaalit
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]'";
					$orgres = pupe_query($query);

					if (mysql_num_rows($orgres)>0) {
						$linkkilisa = "<div id='div_$id' class='popup' style='width: 300px'>
						<table width='300px' align='center'>
						<caption><font class='head'>".t("Tuotteen originaalit")."</font></caption>
						<tr>
							<th>".t("Tuotenumero")."</th>
							<th>".t("Merkki")."</th>
							<th>".t("Hinta")."</th>
						</tr>";

						while($orgrow = mysql_fetch_assoc($orgres)) {
							$linkkilisa .= "<tr>
									<td>$orgrow[orig_tuoteno]</td>
									<td>$orgrow[merkki]</td>
									<td>$orgrow[orig_hinta]</td>
								</tr>";
						}

						$linkkilisa .= "</table></div>";

						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
							$linkkilisa .= "&nbsp;&nbsp;<img class='tooltip' id='$id' src='pics/lullacons/info.png' height='13'>";
						}
						else {
							$linkkilisa .= "&nbsp;&nbsp;<img class='tooltip' id='$id' src='../pics/lullacons/info.png' height='13'>";
						}
					}
				}

				if ($verkkokauppa != "") {
					if ($row["toim_tuoteno"] != "" and $kukarow["kuka"] != "www") {
						$toimlisa = "<br>$row[toim_tuoteno]";
					}
					else {
						$toimlisa = "";
					}

					echo "<td valign='top' class='$vari' $classleft id='tno'>$row[tuoteno] $toimlisa</td>";
					echo "<td valign='top' class='$vari' $classmidl><a id='P3_$row[tuoteno]' href='javascript:sndReq(\"T_$row[tuoteno]\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno=$row[tuoteno]\", \"P3_$row[tuoteno]\")'>".t_tuotteen_avainsanat($row, 'nimitys')."</a>";
				}
				elseif ($kukarow["extranet"] != "" or $tuotekyslinkki == "") {
					echo "<td valign='top' class='$vari' $classleft>$row[tuoteno] $linkkilisa ";
				}
				else {
					echo "<td valign='top' class='$vari' $classleft><a href='../$tuotekyslinkki?tuoteno=".urlencode($row["tuoteno"])."&tee=Z&lopetus=$PHP_SELF////submit_button=1//toim_kutsu=$toim_kutsu//sort=$edsort//ojarj=$ojarj".str_replace("&","//",$ulisa)."'>$row[tuoteno]</a>$linkkilisa ";
				}

				if ($lisatiedot != "" and $verkkokauppa == "") {
					echo "<br>$row[toim_tuoteno]";
				}

				echo "</td>";

				if ($verkkokauppa == "") {
					echo "<td valign='top' class='$vari' $classmidl>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
				}

				if ($verkkokauppa == "") {
					echo "<td valign='top' class='$vari' $classmidl>$row[osasto]<br>$row[try]</td>";
				}

				if ($kukarow['hinnat'] >= 0 and ($verkkokauppa == "" or $kukarow["kuka"] != "www")) {

					$myyntihinta = hintapyoristys($row["myyntihinta"]). " $yhtiorow[valkoodi]";

					if ($kukarow["extranet"] != "" and $kukarow["naytetaan_asiakashinta"] != "") {
						$hinnat["erikoisale"] = 0;

						$myyntihinta_echotus = $hinnat["hinta"] * generoi_alekentta_php($hinnat, 'M', 'kerto');

						$myyntihinta = hintapyoristys($myyntihinta_echotus)." $laskurow[valkoodi]";
					}
					elseif ($kukarow["extranet"] != "") {
						// jos kyseess‰ on extranet asiakas yritet‰‰n n‰ytt‰‰ kaikki hinnat oikeassa valuutassa
						if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

							$myyntihinta = hintapyoristys($row["myyntihinta"])." $yhtiorow[valkoodi]";

							$query = "	SELECT *
										from hinnasto
										where yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and valkoodi = '$oleasrow[valkoodi]'
										and laji = ''
										and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
										order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
										limit 1";
							$olhires = pupe_query($query);

							if (mysql_num_rows($olhires) == 1) {
								$olhirow = mysql_fetch_assoc($olhires);
								$myyntihinta = hintapyoristys($olhirow["hinta"])." $olhirow[valkoodi]";
							}
							elseif ($olhirow["kurssi"] != 0) {
								$myyntihinta = hintapyoristys(yhtioval($row["myyntihinta"], $olhirow["kurssi"])). " $oleasrow[valkoodi]";
							}
						}
					}
					else {
						$query = "	SELECT distinct valkoodi, maa
									from hinnasto
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laji = ''
									order by maa, valkoodi";
						$hintavalresult = pupe_query($query);

						while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {

							// katotaan onko tuotteelle valuuttahintoja
							$query = "	SELECT *
										from hinnasto
										where yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and valkoodi = '$hintavalrow[valkoodi]'
										and maa = '$hintavalrow[maa]'
										and laji = ''
										and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
										order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
										limit 1";
							$hintaresult = pupe_query($query);

							while ($hintarow = mysql_fetch_assoc($hintaresult)) {
								$myyntihinta .= "<br>$hintarow[maa]: ".hintapyoristys($hintarow["hinta"])." $hintarow[valkoodi]";
							}
						}
					}

					echo "<td valign='top' class='$vari' align='right' $classmidl nowrap>";

					if ($hinta_rajaus != "") {
						echo '<font style="text-decoration:line-through;">'.hintapyoristys($row["myymalahinta"]).' '.$yhtiorow["valkoodi"].'</font></br>';
					}

					echo ($poistetut !="" and $kukarow["extranet"] != "") ? " <font class='green'>$myyntihinta</font>" : $myyntihinta;

					if ($lisatiedot != "" and $kukarow["extranet"] == "") {
						echo "<br>".hintapyoristys($row["nettohinta"])." $yhtiorow[valkoodi]";
					}

					echo "</td>";
				}

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<td valign='top' class='$vari' $classmidl>$row[aleryhma]<br>$row[status]</td>";
				}

				if ($verkkokauppa == "" or ($verkkokauppa != "" and $kukarow["kuka"] != "www" and $verkkokauppa_saldotsk)) {
					// Tuoteperheen is‰t, mutta ei sarjanumerollisisa isi‰ (Normi, Extranet ja Verkkokauppa)
					if ($row["tuoteperhe"] == $row["tuoteno"] and $row["sarjanumeroseuranta"] != "S") {
						// Extranet ja verkkokauppa
						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {

							$saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

							$kokonaismyytavissa = 0;

							foreach ($saldot as $varasto => $myytavissa) {
								$kokonaismyytavissa += $myytavissa;
							}

							if ($kokonaismyytavissa > 0) {
								echo "<td valign='top' class='$vari' $classrigh>";
								echo ($hinta_rajaus != "") ? "<font class='green'>".t("P‰‰varasto").": ".t("On")."</font>": "<font class='green'>".t("On")."</font>";
								echo "</td>";
							}
							else {
								echo "<td valign='top' class='$vari' $classrigh><font class='red'>".t("Ei")."</font></td>";
							}
						}
						// Normipupe
						else {
							$saldot = tuoteperhe_myytavissa($row["tuoteno"], "", "KAIKKI", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

							$classrighx = substr($classrigh, 0, -2)." padding: 0px;' ";

							echo "<td valign='top' class='$vari' $classrighx>";
							echo "<table style='width:100%;'>";

							$ei_tyhja = "";

							foreach ($saldot as $varaso => $saldo) {
								if ($saldo != 0) {
									$ei_tyhja = 'yes';
									echo "<tr class='aktiivi'><td class='$vari' nowrap>$varaso</td><td class='$vari' align='right' nowrap>".sprintf("%.2f", $saldo)." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";
								}
							}

							if ($ei_tyhja == '') {
								echo "<tr class='aktiivi'><td class='$vari' nowrap colspan='2'><font class='red'>".t("Tuote loppu")."</font></td></tr>";
							}

							echo "</table></td>";
						}
					}
					// Saldottomat tuotteet (Normi, Extranet ja Verkkokauppa)
					elseif ($row['ei_saldoa'] != '') {
						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
							echo "<td valign='top' class='$vari' $classrigh><font class='green'>".t("On")."</font></td>";
						}
						else {
							echo "<td valign='top' class='$vari' $classrigh><font class='green'>".t("Saldoton")."</font></td>";
						}
					}
					// Sarjanumerolliset tuotteet ja sarjanumerolliset is‰t (Normi, Extranet)
					elseif ($verkkokauppa == "" and ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"]) and $row["osaluettelo"] == "")) {
						if ($kukarow["extranet"] != "") {
							echo "<td valign='top' class='$vari' $classrigh>$row[sarjanumero] ";
						}
						else {
							echo "<td valign='top' class='$vari' $classrigh><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a> ";
						}

						if (!isset($row["sarjadisabled"]) and $row["sarjayhtio"] == $kukarow["yhtio"] and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
							echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
							echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
							echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
							$yht_i++;
						}

						echo "</td>";

						if ($lisatiedot != "" and $kukarow["extranet"] == "") {
							echo "<td class='$vari' $classrigh></td>";
						}
					}
					// Normaalit saldolliset tuotteet (Extranet ja Verkkokauppa)
					elseif ($kukarow["extranet"] != "" or $verkkokauppa != "") {

						$tulossalisa			= "";
						$tilauslisa				= "";
						$noutolisa 				= "";

						if ($verkkokauppa == "") {
							// Listataan noutovarastot, vain extranetiss‰.
							if (!isset($noutovarres)) {
								$query = "	SELECT *
											FROM varastopaikat
											WHERE yhtio = '$kukarow[yhtio]'
											AND maa 	= '{$oleasrow["varastomaa"]}'
											AND nouto 	= '1' AND tyyppi != 'P'
											ORDER BY tyyppi, nimitys";
								$noutovarres = pupe_query($query);
							}
							else {
								mysql_data_seek($noutovarres, 0);
							}

							if (mysql_num_rows($noutovarres) > 0) {

								while ($noutovarrow = mysql_fetch_assoc($noutovarres)) {
									list($noutosaldo, $noutohyllyssa, $noutomyytavissa) = saldo_myytavissa($row["tuoteno"], "", $noutovarrow["tunnus"], "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

									if ($noutomyytavissa > 0) {
										$noutolisa .= "<tr class='aktiivi'><td>".ucwords(strtolower($noutovarrow["nimitark"]))."</td><td><font class='green'>".t("On")."</font></td></tr>";
									}
								}
							}
						}

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

						if ($verkkokauppa == "" and ($row['status'] == 'A' or $row['status'] == '') and $myytavissa <= 0) {

							$tulossa_query = " 	SELECT min(toimaika) paivamaara
							 					FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												AND tuoteno = '$row[tuoteno]'
												AND varattu > 0
												AND tyyppi = 'O'
												AND toimaika >= curdate()";
							$tulossa_result = pupe_query($tulossa_query);
							$tulossa_row = mysql_fetch_assoc($tulossa_result);

							if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '') {
								$tulossalisa .= "<br/><br/>".t("TULOSSA")."<br/>";
								$tulossalisa .= t("Arvioitu saapumisp‰iv‰")." ".tv1dateconv($tulossa_row['paivamaara']);
							}
							else {
								$tulossa_query = " 	SELECT DATE_ADD(curdate(), INTERVAL (if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)+if(tuotteen_toimittajat.tilausvali > 0, tuotteen_toimittajat.tilausvali, toimi.oletus_tilausvali)) DAY) paivamaara,
													if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
								 					FROM tuotteen_toimittajat
													JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
													WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
													AND tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
													AND (tuotteen_toimittajat.toimitusaika+toimi.oletus_toimaika) > 0
													AND (tuotteen_toimittajat.tilausvali+toimi.oletus_tilausvali) > 0
													ORDER BY sorttaus
													LIMIT 1";
								$tulossa_result = pupe_query($tulossa_query);
								$tulossa_row = mysql_fetch_assoc($tulossa_result);

								if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '') {
									$tulossalisa .= "<br/><br/>".t("TULOSSA")."<br/>";
									$tulossalisa .= t("Arvioitu saapumisp‰iv‰")." ".tv1dateconv($tulossa_row['paivamaara']);
								}
							}
						}
						elseif ($verkkokauppa == "" and $row['status'] == 'T' and $myytavissa <= 0) {
							$query = "	SELECT if(tuotteen_toimittajat.tehdas_saldo_toimaika != 0, tuotteen_toimittajat.tehdas_saldo_toimaika, if (tuotteen_toimittajat.toimitusaika != 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)) toimaika,
										if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
										FROM tuotteen_toimittajat
										JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
										WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
										and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
										ORDER BY sorttaus
										LIMIT 1";
							$tulossa_result = pupe_query($query);
							$tulossa_row = mysql_fetch_assoc($tulossa_result);

							if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["toimaika"] > 0) {
								$tilauslisa .= "<font color='orange'>".t("TILAUSTUOTE")."</font>";
								$tilauslisa .= "<br/>".t("Arvioitu toimitusaika")." {$tulossa_row["toimaika"]} ".t("p‰iv‰‰");
							}
						}
						elseif ($verkkokauppa != "") {
							$tulossa_query = " 	SELECT IFNULL(MIN(toimaika),'') paivamaara
							 					FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												AND tuoteno = '$row[tuoteno]'
												AND varattu > 0
												AND tyyppi  = 'O'";
							$tulossa_result = pupe_query($tulossa_query);
							$tulossa_row = mysql_fetch_assoc($tulossa_result);

							if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '') {
								$tulossalisa = ", <font class='info'>".t("Saapuu")." ".tv1dateconv($tulossa_row['paivamaara'])."</font>";
							}
							elseif ((float) $myytavissa <= 0) {
								$query = "	SELECT toimitusaika
											FROM tuotteen_toimittajat
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[tuoteno]'
											and toimitusaika > 0
											LIMIT 1";
								$tulossa_result = pupe_query($query);
								$tulossa_row = mysql_fetch_assoc($tulossa_result);

								if ($tulossa_row["toimitusaika"] > 0) {
									$tulossalisa = ", <font class='info'>".t("Toimaika: %s pv.", $kieli, $tulossa_row["toimitusaika"])."</font>";
								}
							}
						}

						// Tehtaan saldo n‰ytet‰‰n vain jos $myytavissa < $yhtiorow['tehdas_saldo_alaraja'] sek‰ tuotteen_toimittajat.tehdas_saldo > 0
						if ($verkkokauppa == "" and $myytavissa < $yhtiorow['tehdas_saldo_alaraja'] and $yhtiorow['tehdas_saldo_alaraja'] > 0) {
							$query = "	SELECT tuotteen_toimittajat.tehdas_saldo,
										if(tuotteen_toimittajat.tehdas_saldo_toimaika != 0, tuotteen_toimittajat.tehdas_saldo_toimaika, if (tuotteen_toimittajat.toimitusaika != 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)) toimaika,
										if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
										FROM tuotteen_toimittajat
										JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
										WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
										and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
										and tuotteen_toimittajat.tehdas_saldo > 0
										ORDER BY sorttaus";
							$tehdassaldo_res = pupe_query($query);

							while ($tehdassaldo_row = mysql_fetch_assoc($tehdassaldo_res)) {
								$tulossalisa .= "<br/><br/>".t("Tehtaalla")." ".sprintf("%.2f", $tehdassaldo_row['tehdas_saldo'])." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite");
								$tulossalisa .= "<br/>".t("Arvioitu toimitusaika")." {$tehdassaldo_row['toimaika']} ".t("p‰iv‰‰");
							}
						}

						echo "<td valign='top' class='$vari' $classrigh>";

						if ($myytavissa > 0) {

							if ($verkkokauppa != "" and $verkkokauppa_saldoluku) {
								echo "<font class='green'>";
								echo $myytavissa;
								echo "</font>";
							}
							else {
								echo ($hinta_rajaus != "") ? "<font class='green'>".t("P‰‰varasto").": ".t("On")."</font>": "<font class='green'>".t("On")."</font>";
							}
						}
						elseif ($tilauslisa != "") {
							echo "$tilauslisa";
						}
						else {
							echo "<font class='red'>".t("Ei")."</font>";
						}

						if ($noutolisa != "") {
							echo "<br><br>".t("Noutovarastot").":<br><table style='width:100%;'>$noutolisa</table>";
						}

						echo "$tulossalisa</td>";
					}
					// Normaalit saldolliset tuotteet (Normi)
					else {

						$sallitut_maat_lisa = "";

						if ($laskurow["toim_maa"] != '') {
							$sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
						}

						// K‰yd‰‰n l‰pi tuotepaikat
						if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
							$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
										tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
										sarjanumeroseuranta.sarjanumero era,
										concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
										varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
							 			FROM tuote
										JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
										JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										$sallitut_maat_lisa
										and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
										and sarjanumeroseuranta.tuoteno = tuote.tuoteno
										and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
										and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
										and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
										and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
										and sarjanumeroseuranta.myyntirivitunnus = 0
										and sarjanumeroseuranta.era_kpl != 0
										WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
										and tuote.tuoteno = '$row[tuoteno]'
										GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
										ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
						}
						else {
							$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
										tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
										concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
										varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
							 			FROM tuote
										JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
										JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										$sallitut_maat_lisa
										and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
										and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
										WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
										and tuote.tuoteno = '$row[tuoteno]'
										ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
						}
						$varresult = pupe_query($query);

						$classrighx = substr($classrigh, 0, -2)." padding: 0px;' ";

						echo "<td valign='top' class='$vari' $classrighx>";
						echo "<table style='width:100%;'>";

						$loytyko = FALSE;
						$loytyko_normivarastosta = FALSE;
						$myytavissa_sum = 0;

						if (mysql_num_rows($varresult) > 0) {
							$hyllylisa = "";

							// katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
							list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
							$orvot *= -1;

							while ($saldorow = mysql_fetch_assoc($varresult)) {

								if (!isset($saldorow["era"])) $saldorow["era"] = "";

								list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

								//	Listataan vain varasto jo se ei ole kielletty
								if ($sallittu === TRUE) {
									// hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
									if ($orvot > 0) {
										if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
									    	// poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
									    	$myytavissa = $myytavissa - $orvot;
									    	$orvot = 0;
										}
										elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
									    	// poistetaan niin paljon orpojen saldoa ku voidaan
									    	$orvot = $orvot - $myytavissa;
									    	$myytavissa = 0;
										}
									}

									if ($myytavissa != 0 or ($lisatiedot != "" and $hyllyssa != 0)) {
										$id2 = md5(uniqid());

										echo "<tr>";
										echo "<td class='$vari' nowrap>";
										echo "<a class='tooltip' id='$id2'>$saldorow[nimitys]</a> $saldorow[tyyppi]";
										echo "<div id='div_$id2' class='popup' style='width: 300px'>($saldorow[hyllyalue]-$saldorow[hyllynro]-$saldorow[hyllyvali]-$saldorow[hyllytaso])</div>";
										echo "</td>";

										echo "<td class='$vari' align='right' nowrap>";

										if ($hae_ja_selaa_row['selite'] == 'B') {
											echo "<font class='green'>";
										}

										echo sprintf("%.2f", $myytavissa)." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite");

										if ($hae_ja_selaa_row['selite'] == 'B') {
											echo "</font>";
										}

										echo "</td></tr>";
									}

									if ($myytavissa > 0) {
										$loytyko = TRUE;
									}

									if ($myytavissa > 0 and $saldorow["varastotyyppi"] != "E") {
										$loytyko_normivarastosta = TRUE;
									}

									if ($lisatiedot != "" and $hyllyssa != 0) {
										$hyllylisa .= "	<tr class='aktiivi'>
														<td class='$vari' align='right' nowrap>".sprintf("%.2f", $hyllyssa)."</td>
														</tr>";
									}

									if ($saldorow["tyyppi"] != "E") {
										$myytavissa_sum += $myytavissa;
									}
								}
							}
						}

						if (($row['status'] == 'A' or $row['status'] == 'T') and (!$loytyko or $yhtiorow['haejaselaa_saapumispvm'] == "A" or ($yhtiorow['haejaselaa_saapumispvm'] == "B" and !$loytyko_normivarastosta))) {
							$tulossa_query = " 	SELECT
												t_myy.otunnus,
												tli_myy.suoraan_laskutukseen,
												l_myy.nimi,
												tilausrivi.jaksotettu,
												tilausrivi.toimaika paivamaara,
												sum(tilausrivi.varattu) tilattu
							 					FROM tilausrivi
												LEFT JOIN tilausrivin_lisatiedot tli_myy ON (tilausrivi.yhtio=tli_myy.yhtio AND tli_myy.tilausrivilinkki=tilausrivi.tunnus)
												LEFT JOIN tilausrivi t_myy ON (t_myy.yhtio = tli_myy.yhtio and t_myy.tunnus = tli_myy.tilausrivitunnus)
												LEFT JOIN lasku l_myy ON (t_myy.yhtio = l_myy.yhtio and t_myy.otunnus = l_myy.tunnus)
												WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
												AND tilausrivi.tuoteno = '$row[tuoteno]'
												AND tilausrivi.varattu > 0
												AND tilausrivi.tyyppi = 'O'
												AND tilausrivi.toimaika >= curdate()
												GROUP BY 1,2,3,4,5
												ORDER BY 1,2,3,4,5";
							$tulossa_result = pupe_query($tulossa_query);

							if (mysql_num_rows($tulossa_result) > 0) {
								while ($tulossa_row = mysql_fetch_assoc($tulossa_result)) {
									echo "<tr><td class='$vari' align='left' nowrap>";

									if ($tulossa_row["nimi"] != '') {
										if ($tulossa_row["suoraan_laskutukseen"] != "") {
											echo t("Suoratoimitus asiakkaalle").":<br>$tulossa_row[nimi]";
										}
										else {
											echo t("Tilattu asiakkaalle").":<br>$tulossa_row[nimi]";
										}
									}
									else {
										echo t("TULOSSA");
									}

									echo "</td>";
									echo "<td class='$vari' nowrap align='right'>$tulossa_row[tilattu] ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";

									if ($tulossa_row["jaksotettu"] == '1') $tarkkuus = "Vahvistettu";
									else $tarkkuus = "Arvioitu";

									echo "<tr><td class='$vari' colspan='2'>",t("$tarkkuus saapumisp‰iv‰")," ".tv1dateconv($tulossa_row['paivamaara'])."<hr></td></tr>";
								}
							}

							if ($row['status'] == 'A' and mysql_num_rows($tulossa_result) == 0) {
								$tulossa_query = " 	SELECT DATE_ADD(curdate(), INTERVAL (if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)+if(tuotteen_toimittajat.tilausvali > 0, tuotteen_toimittajat.tilausvali, toimi.oletus_tilausvali)) DAY) paivamaara,
													if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
								 					FROM tuotteen_toimittajat
													JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
													WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
													AND tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
													AND (tuotteen_toimittajat.toimitusaika+toimi.oletus_toimaika) > 0
													AND (tuotteen_toimittajat.tilausvali+toimi.oletus_tilausvali) > 0
													ORDER BY sorttaus
													LIMIT 1";
								$tulossa_result = pupe_query($tulossa_query);
								$tulossa_row = mysql_fetch_assoc($tulossa_result);

								if (mysql_num_rows($tulossa_result) > 0) {
									echo "<tr><td class='$vari' align='left' nowrap>",t("TULOSSA"),"</td><td class='$vari' nowrap align='right'></td></tr>";
									echo "<tr><td class='$vari' colspan='2'>",t("Arvioitu saapumisp‰iv‰")," ".tv1dateconv($tulossa_row['paivamaara'])."<hr></td></tr>";
								}
							}
						}

						if ($row['status'] == 'T' and (!$loytyko or $yhtiorow['haejaselaa_saapumispvm'] == "A" or ($yhtiorow['haejaselaa_saapumispvm'] == "B" and !$loytyko_normivarastosta)))  {
							$query = "	SELECT if(tuotteen_toimittajat.tehdas_saldo_toimaika != 0, tuotteen_toimittajat.tehdas_saldo_toimaika, if (tuotteen_toimittajat.toimitusaika != 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)) toimaika,
										if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
										FROM tuotteen_toimittajat
										JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
										WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
										and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
										ORDER BY sorttaus
										LIMIT 1";
							$tulossa_result = pupe_query($query);
							$tulossa_row = mysql_fetch_assoc($tulossa_result);

							if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["toimaika"] > 0) {
								echo "<tr><td class='$vari' align='left' nowrap><font color='orange'>",t("TILAUSTUOTE"),"</font></td><td class='$vari' nowrap align='right'>&nbsp;</td></tr>";
								echo "<tr><td class='$vari' colspan='2'>",t("Arvioitu toimitusaika")," {$tulossa_row["toimaika"]} ".t("p‰iv‰‰")."<hr></td></tr>";
							}
						}

						// Tehtaan saldo n‰ytet‰‰n vain jos (saldo normivarastoissa) $myytavissa_sum < $yhtiorow['tehdas_saldo_alaraja'] sek‰ tuotteen_toimittajat.tehdas_saldo > 0
						if ($myytavissa_sum < $yhtiorow['tehdas_saldo_alaraja'] and $yhtiorow['tehdas_saldo_alaraja'] > 0) {
							$query = "	SELECT tuotteen_toimittajat.tehdas_saldo,
										if(tuotteen_toimittajat.tehdas_saldo_toimaika != 0, tuotteen_toimittajat.tehdas_saldo_toimaika, if (tuotteen_toimittajat.toimitusaika != 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika)) toimaika,
										if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
										FROM tuotteen_toimittajat
										JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
										WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
										and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
										and tuotteen_toimittajat.tehdas_saldo > 0
										ORDER BY sorttaus";
							$tehdassaldo_res = pupe_query($query);

							while ($tehdassaldo_row = mysql_fetch_assoc($tehdassaldo_res)) {
								echo "<tr><td class='$vari' valign='top' align='left'>",t("Tehtaalla"),"</td><td class='$vari' nowrap align='right'>",sprintf("%.2f", $tehdassaldo_row['tehdas_saldo'])," ",t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite"),"</td></tr>";
								echo "<tr><td class='$vari' colspan='2'>",t("Arvioitu toimitusaika")," {$tehdassaldo_row['toimaika']} ".t("p‰iv‰‰"),"<hr></td></tr>";
							}
						}

						echo "</table></td>";

						if ($lisatiedot != "") {
							echo "<td valign='top' $classrigh class='$vari'>";

							if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

								echo "<table width='100%'>";
								echo "$hyllylisa";
								echo "</table></td>";
							}
							echo "</td>";
						}
					}
				}

				if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
					if (($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"] or $row["tyyppi"] == "V") and $row["osaluettelo"] == "") {
						echo "<td align='right' class='$vari' style='vertical-align: top;' nowrap>";
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
						echo "<input type='submit' value = '".t("Lis‰‰")."'>";
						echo "</td>";
						$yht_i++;
					}
					else {
						echo "<td align='right' class='$vari' style='vertical-align: top;' nowrap></td>";
					}
				}

				echo "</tr>";

				if ($verkkokauppa != "") {
					if (stripos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== FALSE) {
						echo "<tr><td colspan='6' class='back' style='padding:0px; margin:0px;height:0px;'><div id='T_$row[tuoteno]'></div></td></tr>";
					}
					else {
						echo "<tr id='T_$row[tuoteno]'></tr>";
					}
				}

				if (isset($row["sarjatunnus"]) and $row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and $verkkokauppa == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					list($kommentit, $text_output, $kuvalisa_bin, $ostohinta, $tuotemyyntihinta) = sarjanumeronlisatiedot_popup($row["sarjatunnus"], $row["sarjayhtio"], '', '', '100%', '');

					if ($lisatiedot != "") {
						echo "<tr class='aktiivi'><td class='back'>&nbsp;</td><td colspan='7'>$kommentit</td></tr>";
					}
					else {
						echo "<tr class='aktiivi'><td class='back'>&nbsp;</td><td colspan='6'>$kommentit</td></tr>";
					}
				}
			}

			echo "</table>";
			echo "</form>";

			if ($hae_ja_selaa_row['selite'] == 'B') {
				echo "</div>";
			}
		}
		else {
			echo "<br/>",t("Yht‰‰n tuotetta ei lˆytynyt"),"!";
		}

		if (mysql_num_rows($result) == 500) {
			echo "<br><br><font class='message'>".t("Lˆytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
		}
	}

	if ($verkkokauppa == "") {
		if (@include("inc/footer.inc"));
		elseif (@include("footer.inc"));
		else exit;
	}
