<?php

$debug = 0;
$edi_ulos = "\n";

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

// jos meillä ei ole sessiota ja meillä on komentoriviparametri annettuna
if ($php_cli) {
	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// ja parametri komentoriviltä
	$filename = $argv[1];

	if (trim($argv[2]) == 'futursoft' and substr(strtolower(basename($filename)), 0, 13) == 'magento-order') {
		$argv[2] = 'magento';
	}

	// ja toinen parametri komentoriviltä
	$edi_tyyppi	= $argv[2];

	// laitetaan pupe-rootti ja tilauskäsittely mukaan includepathiin
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname($argv[0])).PATH_SEPARATOR.dirname($argv[0]));
}

if (!function_exists('jt_toimita_editilaus')) {
	function jt_toimita_editilaus ($asiakasno, $asiakasid, $asiakasmaa) {
		global $yhtiorow, $kukarow, $edi_ulos, $php_cli;

		$automaaginen 			    	= "automaaginen";
		$tee 					    	= "JATKA";
		$oikeurow['paivitys'] 	    	= '1';
		$toimittaja				    	= "";
		$toimittajaid			    	= "";
		$jarj	 				    	= "tuoteno";
		$tuotenumero			    	= "";
		$tilaus					    	= "";
		$toimi					    	= "";
		$suoratoimit					= "";
		$tilaus_on_jo			    	= "KYLLA";
		$from_varastoon_inc 	    	= "editilaus_in.inc";
		$ei_tehdastoimitus_tuotteita	= "EI";
		$automaattinen_poiminta			= "JOO";

		if ($asiakasmaa != '') {
			$asiakasmaalisa = "and (varastopaikat.sallitut_maat like '%$asiakasmaa%' or varastopaikat.sallitut_maat = '')";
		}

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi  = ''
					$asiakasmaalisa";
		$vtresult = pupe_query($query);

		$varastosta = array();

		while ($vrow = mysql_fetch_array($vtresult)) {
			$varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
		}

		if ($asiakasid > 0 and count($varastosta) > 0) {
			require('jtselaus.php');
		}
	}
}

// nollataan kaikki laskun muuttujat (nämä samat pitää tehdä whilen lopussa)
unset($arow);
unset($arow_viite);
unset($arow_ostaja);
unset($arow_toimitus);
unset($arow_terminaali);
unset($yhtiorow);

$toimpvm 	 			= date("Y-m-d");
$vastaustapa 			= "";
$editilaus_tila       	= "N"; // laskun tila
$editilaus_alatila		= "";
$editilaus_tyyppi     	= "L"; // tilausrivi tyyppi
$id                   	= "";
$edi_kommentti        	= "";
$vastaustapa          	= "";
$maksaja              	= "";
$viite                	= "";
$asiakkaan_tilausnumero	= "";
$editilaus_otsikko_ok 	= "";
$suunnistus           	= "";
$tnimi                	= "";
$tnimitark            	= "";
$tpostitp             	= "";
$tpostino             	= "";
$tosoite              	= "";
$lnimi					= "";
$lnimitark				= "";
$lpostitp				= "";
$lpostino				= "";
$losoite				= "";
$lopnumero            	= "";
$lopnimi              	= "";
$loppostitp           	= "";
$loppostino           	= "";
$loposoite            	= "";
$ltoim_maa				= "";
$yhteyshenkilon_puhelin = "";
$lopviesti            	= "";
$ketjutus             	= "";
$editilaus_maksuehto	= "";
$editilaus_maksuteksti	= "";
$editilaus_toimitustapa = "";
$tilaus_hyvaksyntaan	= "";
$editilaus_rahtivapaa	= "";
$editilaus_tilaustyyppi = "";
$editilaus_eilahetetta	= "";
$editilaus_sisainen		= "";
$editilaus_kassalipas	= "";
$til_vienti				= "";
$verkkokauppavarasto	= "";
$verkkokauppavarasto_li	= "";
$editilaus_loppuhinta	= "";
$kieltolisa				= "";
$OT_ASIAKASNRO			= "";
$merkkitieto			= "";

// nollataan kaikki rivin muuttujat (nämä samat pitää tehdä tuote insertin jälkeen)
$tuoteerror      	= 1; // oletetaan aina ettei  tuotetta löydy
$tuoteerrortxt   	= "";
$kpl             	= "";
$rivinumero      	= "";
$kommentti       	= "";
$tuoteno         	= "";
$olinsapi        	= "";
$olinvarasto     	= "";
$edi_nimitys     	= "";
$sallitaanjt	 	= "";
$editilaus_hinta	= "";
$editilaus_alennus	= "";

// fiilataan parametrejä joita tarvitaan require fileissä
$tulos_ulos = "edi"; // tää on ftp-send.incissä

// tarvitaan $filename
$filename = trim($filename);
if ($filename == "") die('Tiedostonimi puuttui!\n');

// Mutetaan ISO-merkistöön jos UTF8:ia
$encoding = exec("file -b --mime-encoding $filename");

if (preg_match("/(utf\-8|binary)/i", $encoding)) {
	$convert = exec("iconv -c -f utf-8 -t ISO8859-1  $filename > $filename.ISO");
	rename($filename.".ISO", $filename);
}

if (!$fd = fopen($filename, "r")) die("Filen '$filename' ".t("avaus epäonnistui")."!\n");

//jotta filenimi ei varmasti katoaisi matkalla
$mailfile = $filename;

if ($edi_tyyppi == "futursoft") {
	$edi_subj = "Sisääntuleva Futursoft-tilaus";
	$edi_laatija = "FuturSoft";
}
elseif ($edi_tyyppi == "magento") {
	$edi_subj = "Sisääntuleva Verkkokauppa-tilaus";
	$edi_laatija = "Magento";
	$editilaus_rahtivapaa = "o";
}
elseif ($edi_tyyppi == "edifact911") {
	$edi_subj = "Sisääntuleva Orders 91.1 tilaus";
	$edi_laatija = "Orders911";

	// Orders 91.1 tulee ostajan tiedot ennen vastaanottajaa, joten meidän pitää heti tässä vaiheessa kaivaa jo vastaanottajan tiedot
	$order_tietosisalto = file_get_contents($filename);

	$order_tietosisalto = str_replace("\n","", $order_tietosisalto);
	$order_tietosisalto = explode("'", $order_tietosisalto);

	// Tallennetaan NAD+SE -segmentti, koska se tulee muuten liian myöhäisessä vaiheessa...
	$nadse_segmentti = "";

	foreach ($order_tietosisalto as $key => $value) {

		$value = trim($value);

		if ($value != "") {
			$edi_rivi .= $value."\n";

			if (substr($value, 0, 6) == "NAD+SE") {
				$nadse_segmentti = $value;
			}
		}
	}

	unset($order_tietosisalto);

	// Luodaan arraysta => tiedosto
	$tiedosto = "/tmp/ediorders911".uniqid(time()."_").".txt";
	file_put_contents($tiedosto, $edi_rivi);

	// avataan tiedosto uudestaan
	if (!$fd = fopen($tiedosto, "r")) die("Filen '$tiedosto' ".t("avaus epäonnistui")."!\n");

	//jotta filenimi ei varmasti katoaisi matkalla, tämä ylikirjoittaa edellisen mailfilen.
	$mailfile = $tiedosto;
}
else {
	$edi_subj = t("Sisääntuleva EIH 1.4 EDI-tilaus");
	$edi_laatija = "EDI";
}

$edi_ulos .= "$edi_subj\n";

while ($tietue = fgets($fd)) {

	// Tyhjät rivit skipataan
	if (trim($tietue) == "") continue;

	// tää on futurikeisi
	if ($edi_tyyppi == "futursoft" or $edi_tyyppi == "magento") {

		if (substr($tietue, 0, 1) != '*') {
			list($tunnus, $tieto)  = explode(':', $tietue);
		}
		else {
			$tunnus = $tietue;
			$tieto	= "";
		}

		$tunnus = trim($tunnus);
		$tieto  = trim($tieto);

		if (preg_match('/\*RE *OSTOTILRIV/', $tunnus)) {
			$tunnus = '*RE OSTOTILRIV'; // Poistetaan häiritsevä nro
		}
	}
	elseif ($edi_tyyppi == "edifact911"){
		$tunnus = substr($tietue, 0, 3);
		$tieto  = trim(substr($tietue, 4));
	}
	else {
		$tunnus = substr($tietue, 0, 9);
		$tieto  = trim(substr($tietue,10));
	}

	// tehdään kaikille tiedoille 7 -> 8 bit ääkköskonversio..
	$from   = array('{','}','|','[',']','\\',chr(179)); // 7bit
	$to     = array('ä','å','ö','Ä','Å','Ö','|'); 		// 8bit
	$orig_tieto = str_replace($from, $to, $tieto);
	$tieto  = str_replace($from, $to, pupesoft_cleanstring($tieto));

	// Jos kyseessä on UNH (ensimmäinen Orders 91.1 segmentti), niin etsitään tässä vaiheessa NAD SE
	if ($tunnus == "UNH") {
		$tunnus = "NADSE"; // Myyjä

		$nad_segmentit = explode("+", $nadse_segmentti);
		$nad_kentta = explode(":", $nad_segmentit[2]);
		$tieto = $nad_kentta[0];
	}

	// Jos kyseessä NAD segmentti, tarkennetaan mikä NAD on kyseessä ja laitetaan $tieto kuntoon
	if ($tunnus == "NAD") {
		switch (substr($tieto,0,2)) {
			case 'BY':
				$tunnus = "NADBY"; // Ostaja
				$nad_segmentit = explode("+", $tieto);
				$nad_kentat = explode(":", $nad_segmentit[1]);
				$tieto = $nad_kentat[0];
				break;
			case 'PL':
				$tunnus = "NADPL"; // Edelleenlaskutettava
				$nad_segmentit = explode("+", $tieto);
				$nad_kentat = explode(":", $nad_segmentit[1]);
				$tieto = $nad_kentat[0];
				break;
			case 'CN':
				$tunnus = "NADCN"; // Tavaran vastaanottaja
				$nad_segmentit = explode("+", $tieto);
				$nad_kentat = explode(":", $nad_segmentit[1]);
				$tieto = $nad_kentat[0];
				break;
			case 'DP':
				$tunnus = "NADDP"; // Toimitusosoite
				break;
		}
	}

	switch ($tunnus) {
		case 'ICHG_RCPT':
		case 'OSTOTIL.OT_TOIMITTAJANRO':
		case 'OSTOTIL.OT_OVTTUNNUS':
		case 'NADSE':
		//Tilauksen vastaanottaja
			$edi_ulos .= t("Tilaus yritykselle")." '$tieto' ";

			$query = "SELECT * FROM yhtio WHERE ovttunnus = '$tieto' and ovttunnus != ''";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) {
				// Kokeillaan tehdä ovttunnuksesta ytunnus
				$tieto2 = substr($tieto, 4, 8);     // etunollilla
				$tieto  = substr($tieto, 4, 8) * 1; // ilman etunollio

				// Häkki Örumia varten
				if ((int) $tieto == 0) $tieto = "20428100";

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$tieto', '$tieto2') and ytunnus != ''";
				$result = mysql_query($query) or die($query);
			}

			if (mysql_num_rows($result) != 1) {
				$edi_ulos .= "\n".t("Vastaanottavaa yritystä ei löydy tunnuksella")." '$tieto', '$tieto2', '$mailfile'\n";
				break;
			}

			$yhtiorow = mysql_fetch_array($result);
			$yhtiorow = hae_yhtion_parametrit($yhtiorow["yhtio"]);

			$kukarow['yhtio'] = $yhtiorow['yhtio']; // Tämä on vaarallista, mutta tätä ilman ei mikän toimi!
			$kukarow['kuka'] = $edi_laatija;

			// nollataan argv ja laitetaan ensimmäiseksi paratemetriksi yhtio, niin verkkolaskutus toimii!
			$argv 		= array();
			$argv[0] 	= "";
			$argv[1] 	= $kukarow["yhtio"];
			$editil_cli = TRUE;

			$edi_ulos .= "-> $yhtiorow[nimi] ($kukarow[yhtio])\n";
			break;

		case 'ONADBY_DO' :
		case 'OSTOTIL.OT_VAHVISTUS_FAKSILLA' :
		//Faxvastaus
			$tieto = substr($tieto,4,2);
			if ($tieto == 'FX' or $tieto == '1') {
				$vastaustapa .= 'F';
				$edi_ulos .= t("Pyytää Fax-tilausvahvistuksen").".\n";
			}
			break;

		case 'OSTOTIL.OT_TILAUSTYYPPI' :
		//Tilaustyyppi
			$editilaus_tilaustyyppi = trim($tieto);
			break;

		case 'OBGMIACKR' :
		//Edivastaus
			if ($tieto == "AB") {
				$vastaustapa .= 'E';
				$edi_ulos .= t("Pyytää EDI-tilausvahvistuksen").".\n";
			}
			if ($tieto == "S") {
				$vastaustapa .= 'S';
				$edi_ulos .= t("Pyytää sähköposti-tilausvahvistuksen").".\n";
			}
			if ($tieto == "F") {
				$vastaustapa .= 'F';
				$edi_ulos .= t("Pyytää fax-tilausvahvistuksen").".\n";
			}
			break;

		case 'OBGMITYPE' :

			$type = explode("@", $tieto);

			// jos terminaalitoimitus
			if (trim($type[1]) == "TKT") {
				$edi_ulos .= t("Terminaalitoimitus")." '$type[1]'\n";
				$editilaus_tila   = "Z";
				$editilaus_tyyppi = "Z";
			}
			else {
				$editilaus_tila   = "N";
				$editilaus_tyyppi = "L";
			}

			// jos TRM ni ei kai mitää spessua
			if (trim($type[1]) == "TRM") {

			}

			// Pupesoft tilaustyyppi
			if ($tieto == '1') {
				$edi_ulos .= t("Laskua ei saa ketjuttaa")."!\n";
				$ketjutus = "o";
			}
			else {
				$ketjutus = "";
			}
			break;

		case 'OLINSTART' :
		case 'OSTOTILRIV.OTR_RIVINRO' :
		//Rivinumero
			$rivinumero=$tieto;
			break;

		case 'OFTXDELTX' :
		case 'OSTOTIL.OT_TOIMITUSTAPA' :
		//Toimitustapa
			if (trim($tieto) != '') {
				$editilaus_toimitustapa = $tieto;
			}
			break;

		case 'OFTXGENTX' :
		//Kommentti
			$edi_kommentti .= $tieto . ' ';
			break;

		case 'ODTM2__DT' :
		case 'DTM' :
		// Toimituspäivä

			if ($edi_tyyppi == "edifact911") {
				$kentat = explode(":",$tieto);
				if ($kentat[0] == 2 and $kentat[2] == 102) {
					$toimpvm = tv3dateconv($kentat[1]);
				}
			}
			else {
				$type = explode("@", $tieto);

				// 102 on päivämäärä, 616 on viikkonumero
				if (trim($type[1]) == "102") {
					$tieto = trim($type[0]);
					$editilaus_toimitusvv = substr($tieto, 0, 4);
					$editilaus_toimituskk = substr($tieto, 4, 2);
					$editilaus_toimituspp = substr($tieto, 6, 2);
					if (checkdate($editilaus_toimituskk, $editilaus_toimituspp, $editilaus_toimitusvv)) {
						$toimpvm = "$editilaus_toimitusvv-$editilaus_toimituskk-$editilaus_toimituspp";
					}
				}
			}
			break;

		case 'OBGMINUMB' :
		case 'OSTOTIL.OT_NRO' :
		//Asiakkkaan tilausnumero
			$asiakkaan_tilausnumero = $tieto;
			$viite = $tieto. ' '; // laitetaan asiakkaan tilausnumero talteen myös tilausviite-kenttään
			break;

		case 'ORFFZZ1NU'  :
		// suunnistustieto ?
			$suunnistus = $tieto;
			break;

		case 'ORFFCT_NU'  :
		//Kommentteja
			$edi_kommentti .= $tieto . ' ';
			break;

		case 'ORFFCR_NU'  :
		//Kommentteja
			$merkkitieto = $tieto;
			break;

		case 'OSTOTIL.OT_MAKSUEHTO' :
		//Magento keississä maksuehdon koko nimi verkkokaupassa
			if ($edi_tyyppi == "magento" and $tieto != '') {

				if ($editilaus_maksuehto == "") {
					$query = "	SELECT *
								FROM maksuehto
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND teksti = '{$tieto}'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 1) {
						$editilaus_maksuehto_row = mysql_fetch_assoc($result);
						$editilaus_maksuehto	 = $editilaus_maksuehto_row['tunnus'];
					}
				}

				$editilaus_maksuteksti .= $tieto;
			}
			break;

		case 'ORFFABONU' :
		case 'OSTOTIL.OT_VIITTEEMME' :
		//Asiakkaan viite
			$viite .= $tieto. ' ';
			break;

		case 'ONADBY_RF' :
			if (substr($tieto, 0, 4) == 'IT @') {
				$tieto = substr($tieto, 4, 10) * 1;
			}
		case 'ONADCN_PC' :
			$type = explode("@", $tieto);
			$lopnumero = trim($type[0]);
		case 'ONADBY_PC' :
		case 'OSTOTIL.OT_ASIAKASNRO' :
		case 'ONADDP_PC' :
		case 'NADBY' :
		case 'NADPL' :
		case 'NADCN' :
			// ONADBY_PC - Ostajan osapuolen tunniste
			// ONADDP_PC - Toimitusosoitteen osapuolen tunniste
			// ONADCN_PC - Ostajan logistiikkakeskuksen tunniste (CN -tarkenne ei ole Itellan speksissä, joten tämä on itse pääteltyä!)
			// ONADBY_RF - Ostajan osapuolen viite (meidän asiakasnumero)
			// OSTOTIL.OT_ASIAKASNRO - Ostajan meidän asiakasnumero

			if ($edi_tyyppi != "edifact911") {
				$tieto = trim(substr($tieto, 0, 17));
			}

			if ($tieto == "") {
				break;
			}

			// Otetaan talteen
			$OT_ASIAKASNRO = $tieto;

			// Örum häkki
			if (!isset($yhtiorow) and strpos($palvelin, "orum.devlab.fi") !== FALSE) {
				$yhtiorow = hae_yhtion_parametrit("artr");

				// Copy pastettu ylhäältä koko setti. :(
				$kukarow['yhtio'] = $yhtiorow['yhtio']; // Tämä on vaarallista, mutta tätä ilman ei mikän toimi!
				$kukarow['kuka'] = $edi_laatija;

				// nollataan argv ja laitetaan ensimmäiseksi paratemetriksi yhtio, niin verkkolaskutus toimii!
				$argv 		= array();
				$argv[0] 	= "";
				$argv[1] 	= $kukarow["yhtio"];
				$editil_cli = TRUE;

				$edi_ulos .= "VASTAANOTTTAVAN YHTION TIETO PUUTTUU! -> $yhtiorow[nimi] ($kukarow[yhtio])\n";
			}

			if (!is_array($arow)) {
				$edi_ulos .= t("Tilaava asiakas")." $tieto -> ";

				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and toim_ovttunnus = '$tieto'
							and toim_ovttunnus not in ('','0')
							and laji not in ('R','P')";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
				}
			}

			if (!is_array($arow)) {
				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and ovttunnus = '$tieto'
							and ovttunnus not in ('','0')
							and laji not in ('R','P')";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
				}
			}

			if (($tunnus == "ONADBY_RF" or $tunnus == "OSTOTIL.OT_ASIAKASNRO") and !is_array($arow)) {
				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakasnro = '$tieto'
							and asiakasnro not in ('','0')
							and laji not in ('R','P')";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
				}
			}

			if (!is_array($arow)) {
				$edi_ulos .= t("Asiakas ei löydy tunnuksella")." '$tieto'\n";
			}

			// otetaan talteen kaikki eri arowt, valitaan paras lopuksi.
			if (is_array($arow) and $tunnus == 'ONADBY_RF') {
				$arow_viite = $arow;
				unset($arow);
			}
			elseif (is_array($arow) and $tunnus == 'ONADBY_PC') {
				$arow_ostaja = $arow;
				unset($arow);
			}
			elseif (is_array($arow) and $tunnus == 'ONADDP_PC') {
				$arow_toimitus = $arow;
				unset($arow);
			}
			elseif (is_array($arow) and $tunnus == 'ONADCN_PC') {
				$arow_terminaali = $arow;
				unset($arow);
			}

			break;

		case 'OSTOTIL.OT_YRITYS':
			$lnimitark = trim($tieto);
			break;

		case 'OSTOTIL.OT_KATUOSOITE':
			$losoite = trim($tieto);
			break;

		case 'OSTOTIL.OT_POSTITOIMIPAIKKA':
			$lpostitp = trim($tieto);
			break;

		case 'OSTOTIL.OT_POSTINRO':
			$lpostino = trim($tieto);
			break;

		case 'OSTOTIL.OT_YHTEYSHENKILO':
			$lnimi = trim($tieto);
			break;

		case 'ONADPL_PC' :
			// Asiakkaan joku toimitus ovttunnus tms... tää ylikirjottaa kaiken...
			if ($tieto != "") {

				$type = explode("@", $tieto);
				$tieto = trim($type[0]);

				$edi_ulos .= t("Tilaava toim_asiakas")." $tieto -> ";

				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and toim_ovttunnus = '$tieto'
							and toim_ovttunnus not in ('','0')
							and laji not in ('R','P')";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[toim_nimi]\n";
				}

			}

			break;

		case 'ONADCN_NA' :
		// lopullisen asiakkkaan nimi
			$lopnimi = $tieto;
			break;

		case 'ONADCN_SA' :
		// lopullisen asiakkkaan osoite
			$loposoite = $tieto;
			break;

		case 'ONADCN_CI' :
		// lopullisen asiakkkaan postitp
			$loppostitp = $tieto;
			break;

		case 'ONADCN_PO' :
		// lopullisen asiakkkaan postitn
			$loppostino = $tieto;
			break;

		case 'NADDP' :
		// NAD-DP segmentissä, tulee kerralla kaikki toimitusosoitteen tiedot
		// Toimitusosoitteen nimi-kentässä on kovakoodattu teksti "TAVARAN VASTAANOTTAJA" (by Servinet)
			$kentat = explode("+", $tieto);

			if ($kentat[3] != "TAVARAN VASTAANOTTAJA") {
				$tnimi = $kentat[3];
				$tnimitark = "";
				$tpostitp = $kentat[5];
				$tpostino = $kentat[7];
				$tosoite = $kentat[4];
			}
			break;

		case 'ONADDP_NA' :
		case 'OSTOTIL.OT_TOIMITUS_NIMI' :
			// toimitusasiakkaan nimi
			$tnimi = $tieto;
			break;

		case 'ONADDP_NB' :
		case 'OSTOTIL.OT_TOIMITUS_YRITYS' :
			// toimitusasiakkaan nimitark
			$tnimitark = $tieto;
			break;

		case 'ONADDP_SA' :
		case 'OSTOTIL.OT_TOIMITUS_KATUOSOITE' :
		// toimitusasiakkaan osoite
			$tosoite = $tieto;
			break;

		case 'ONADDP_PO' :
		case 'OSTOTIL.OT_TOIMITUS_POSTINRO' :
		// toimitusasiakkaan postinro
			$tpostino = $tieto;
			break;

		case 'ONADDP_CI' :
		case 'OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA' :
		// toimitusasiakkaan postitoimipaikka
			$tpostitp = $tieto;
			break;

		case 'OSTOTIL.OT_TOIMITUS_MAAKOODI' :
			// toimitusosoitteen maakoodi
			$ltoim_maa = substr(trim($tieto), 0, 2);
			break;

		case 'OSTOTIL.OT_YHTEYSHENKILONPUH' :
			// yhteyshenkilön puhelinnumero
			$yhteyshenkilon_puhelin = trim($tieto);
			break;

		case 'OSTOTIL.OT_MAKSETTU' :
			// jos Magenton tilaus on jo maksettu luottokortilla niin päivitetään maksuehto
			if ($edi_tyyppi == "magento" and ($tieto == 'processing' or $tieto == 'complete')) {
				$kukarow["kassamyyja"] = "";
				$editilaus_kassalipas = "";

				$query = "	SELECT tunnus
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]'
							AND kateinen = 'o'
							LIMIT 1";
				$editilaus_kateinen_res = pupe_query($query);
				$editilaus_kateinen_row = mysql_fetch_array($editilaus_kateinen_res);

				if (mysql_num_rows($editilaus_kateinen_res) == 1) {
					$editilaus_maksuehto = $editilaus_kateinen_row["tunnus"];

					$query = "	SELECT tunnus
								FROM kassalipas
								WHERE yhtio = '$kukarow[yhtio]'
								AND nimi = 'Verkkokauppa'";
					$kassalipas_res = pupe_query($query);

					if (mysql_num_rows($kassalipas_res) == 1) {
						$kassalipas_row = mysql_fetch_array($kassalipas_res);
						$kukarow["kassamyyja"] = $editilaus_kassalipas = $kassalipas_row["tunnus"];
					}
					else {
						$editilaus_maksuehto = "";
						$edi_ulos .= t("Verkkokaupan kassalipasta ei löytynyt")."!\n";
						$edi_ulos .= t("Tilaus on maksettu, mutta jäi avoimeksi")."!\n";
					}
				}
				else {
					$editilaus_maksuehto = "";
					$edi_ulos .= t("Luottokorttimaksuehtoa ei löytynyt")."!\n";
					$edi_ulos .= t("Tilaus on maksettu, mutta jäi avoimeksi")."!\n";
				}
			}

			if ($edi_tyyppi == "magento" and $tieto == 'pending_cashondelivery_asp') {
				// tämä on jälkivaatimus
				$kukarow["kassamyyja"] = "";
				$editilaus_kassalipas = "";

				$query = "	SELECT tunnus
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]'
							AND jv = 'o'
							ORDER BY jarjestys
							LIMIT 1";
				$editilaus_kateinen_res = pupe_query($query);
				$editilaus_kateinen_row = mysql_fetch_array($editilaus_kateinen_res);

				if (mysql_num_rows($editilaus_kateinen_res) == 1) {
					$editilaus_maksuehto = $editilaus_kateinen_row["tunnus"];
				}
				else {
					$editilaus_maksuehto = "";
					$edi_ulos .= t("Jälkivaatimus-maksuehtoa ei löytynyt")."!\n";
				}

				$query = "	SELECT selite
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							AND jvkielto = ''
							ORDER BY jarjestys
							LIMIT 1";
				$editilaus_kateinen_res = pupe_query($query);
				$editilaus_kateinen_row = mysql_fetch_array($editilaus_kateinen_res);

				if (mysql_num_rows($editilaus_kateinen_res) == 1) {
					$editilaus_toimitustapa = $editilaus_kateinen_row["selite"];
				}
				else {
					$editilaus_toimitustapa = "";
					$edi_ulos .= t("Jälkivaatimus-toimitustapaa ei löytynyt")."!\n";
				}
			}
			break;

		case 'OSTOTIL.OT_SUMMA' :
			// Kun tullaan magentosta ja meillä on laskun loppusumma
			// Tallennetaan tämä laskun 'hinta'-kenttään niin jos se jää riittävän lähelle totuutta niin pupe pyöristää loppusumman tähän
			if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $tieto != '') {
				$editilaus_loppuhinta = (float) trim($tieto);
			}
			break;

		case 'OSTOTIL.OT_VIITTEENNE':
			// halutaan futur-aineistosta tämä tieto sisviesti2-kenttään
			if ($edi_tyyppi == "futursoft") {
				$orig_tieto = pupesoft_cleanstring($orig_tieto, array(";"));
				$lopviesti = trim($orig_tieto);
			}
			break;

		//Tilaus päättyy. Aika tehdä se kantaan
		case 'OHDR__END' :
		case '*RE OSTOTIL' :
		case 'UNS':

			// Orders 91.1 tulee kaksi UNS segmenttiä (D ja S). D tarkoittaa otsikko päätty, S tarkoittaa sanoma päättyy.
			if ($edi_tyyppi == "edifact911" and substr($tieto, 0, 1) != "D") {
				break;
			}

			// valitaan paras arow!
			if (!is_array($arow)) {
				if (isset($arow_viite)) {
					$arow = $arow_viite;				// ONADBY_RF
				}
				elseif (isset($arow_terminaali)) {
					$arow = $arow_terminaali;			// ONADCN_PC
				}
				elseif (isset($arow_toimitus)) {
					$arow = $arow_toimitus;				// ONADDP_PC
				}
				elseif (isset($arow_ostaja)) {
					$arow = $arow_ostaja;				// ONADBY_PC
				}
			}

			// lisätään otsikko vain jos asiakas löytyi
			if (!is_array($arow)) {
				$edi_ulos .= t("Tilaukselta ei löytynyt asiakastietoja")."!\n";
				$editilaus_otsikko_ok = "EI";
			}
			elseif (!isset($yhtiorow)) {
				$edi_ulos .= t("Tilaukselta ei löytynyt yhtiötietoja")."!\n";
				$editilaus_otsikko_ok = "EI";
			}
			else {
				$editilaus_otsikko_ok = "KYLLA";

				// haetaan tomitustavan oletusmaksajan tiedot
				$apuqu = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$arow[toimitustapa]'";
				$meapu = pupe_query($apuqu);
				$apuro = mysql_fetch_array($meapu);

				$maksaja = $apuro['merahti'];

				// Verkkokauppatilauksissa luotamme tiedoston laskutustietoihin
				if ($edi_tyyppi == "magento") {
					$arow["nimi"]		= $lnimi;
					$arow["nimitark"]	= $lnimitark;
					$arow["osoite"]		= $losoite;
					$arow["postitp"]	= $lpostitp;
					$arow["postino"]	= $lpostino;
					$arow["maa"]		= $ltoim_maa;
					$arow["vienti"]		= "";

					$arow['laskutus_nimi'] 		= $lnimi;
					$arow['laskutus_nimitark'] 	= $lnimitark;
					$arow['laskutus_osoite']	= $losoite;
					$arow['laskutus_postitp'] 	= $lpostitp;
					$arow['laskutus_postino'] 	= $lpostino;
					$arow['laskutus_maa']		= $ltoim_maa;

					if ($arow["maa"] != $yhtiorow["maa"] and $arow["maa"] != "") {
						// katsotaan mimmosesta viennistä on kysymys
						$maa_eu_query = "	SELECT distinct eu
											FROM maat
											WHERE koodi = '$arow[maa]'";
						$maa_eu_res = pupe_query($maa_eu_query);
						$maa_eu_row = mysql_fetch_array($maa_eu_res);

						if ($maa_eu_row["eu"] == "") {
							$arow["vienti"] = "K";
						}
						else {
							$arow["vienti"] = "E";
						}
					}
				}

				if (trim($arow['laskutus_nimi']) == '') {
					$arow['laskutus_nimi'] 		= $arow['nimi'];
					$arow['laskutus_nimitark']	= $arow['nimitark'];
					$arow['laskutus_osoite']	= $arow['osoite'];
					$arow['laskutus_postitp']	= $arow['postitp'];
					$arow['laskutus_postino']	= $arow['postino'];
					$arow['laskutus_maa']		= $arow['maa'];
				}

				// jos asiakkalla ei ole toimitusosoitetietoja, kopsataan laskutustiedot
				if (trim($arow['toim_nimi']) == "") {
					$arow['toim_nimi']		= $arow['nimi'];
					$arow['toim_nimitark']	= $arow['nimitark'];
					$arow['toim_osoite']	= $arow['osoite'];
					$arow['toim_postitp']	= $arow['postitp'];
					$arow['toim_postino']	= $arow['postino'];
					$arow['toim_maa']		= $arow['maa'];
				}

				// jos tilauksessa on tullut poikkeava nimi, postino ja postitp otetaan kaikki tiedot tilaukselta (PAITSI JO KYSESSÄ ON KOONTIKULJETUSKEISSI)
				if (($tnimi != "" or ($tnimi == "" and $tnimitark != "")) and $tpostino != "" and $tpostitp != "" and $apuro["tulostustapa"] != "K" and $apuro["tulostustapa"] != "L") {

					if ($tnimi == "" and $tnimitark != "") {
						$tnimi = $tnimitark;
						$tnimitark = "";
					}

					$arow['toim_nimi']		= $tnimi;
					$arow['toim_nimitark']	= $tnimitark;
					$arow['toim_osoite']	= $tosoite;
					$arow['toim_postino']	= $tpostino;
					$arow['toim_postitp']	= $tpostitp;
				}

				// jos tilauksella ei ole maksuehtoa, käytetään asiakkaan oletusta
				if ($editilaus_maksuehto == '') {
					$editilaus_maksuehto = $arow["maksuehto"];
				}

				if (trim($editilaus_toimitustapa) != '') {
					$query = "	SELECT *
								FROM asiakkaan_avainsanat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tarkenne = '{$editilaus_toimitustapa}'
								AND laji = 'editilaus_toimitustapa'
								AND tarkenne != ''
								AND avainsana != ''
								AND liitostunnus = '{$arow['tunnus']}'
								ORDER BY muutospvm DESC
								LIMIT 1";
					$toimitustapa_chk_res = pupe_query($query);

					if (mysql_num_rows($toimitustapa_chk_res) == 1) {
						$toimitustapa_chk_row = mysql_fetch_assoc($toimitustapa_chk_res);
						$editilaus_toimitustapa = $toimitustapa_chk_row['avainsana'];
					}
					else {
						$edi_kommentti .= t("Asiakas pyysi toimitustapaa").": $editilaus_toimitustapa ";
						$editilaus_toimitustapa = '';
					}
				}

				// jos tilauksella ei ole toimitustapaa, käytetään asiakkaan oletusta
				if ($editilaus_toimitustapa == '') {
					$editilaus_toimitustapa = $arow["toimitustapa"];
				}

				// jos ei olla pakotettu rahtivapaata, niin otetaan asiakkaan oletus
				if ($editilaus_rahtivapaa == '') {
					$editilaus_rahtivapaa = $arow["rahtivapaa"];
				}

				// katsotaan magentotilauksilta tilaustyyppi
				if ($edi_tyyppi == "magento" AND $editilaus_tilaustyyppi != "") {
					// tyyppi 9, ohitetaan aina varastoprosessi ja aina sisäinen
					if ($editilaus_tilaustyyppi == "9") {
						$editilaus_eilahetetta = "o";
						$editilaus_sisainen = "o";
					}
					elseif ($editilaus_tilaustyyppi == 'K') {
						$editilaus_tila 	= 'N';
						$editilaus_alatila 	= '';
					}
				}

				// jos tilauksella on tullut lopullisen asikkaan tiedot, laitetaan ne talteen sisviesti ykköseen
				if ($lopnimi != "") {
					$arow["sisviesti1"] = "$lopnumero\n$lopnimi\n$loposoite\n$loppostino\n$loppostitp";

					// jos kyseessä on terminaalitoimitus
					if ($editilaus_tila == "Z") {
						$lopviesti = "$lopnumero#$lopnimi"; // laitetaan vielä numero ja nimi omaan paikkaan..
					}
				}

				// jos tilauksella oli toivottu, että laskua *EI SAA* ketjuttaa ni tehdään niin
				if ($ketjutus != "") {
					$arow["ketjutus"] = $ketjutus;
				}

				$edimyyja = "";

				// Haetaan vielä myyyjänumero
				$query = "	SELECT tunnus
							FROM kuka
							WHERE yhtio = '$kukarow[yhtio]'
							and kuka 	= 'EDI'";
				$meapu = pupe_query($query);

				if (mysql_num_rows($meapu) == 1) {
					$apuro = mysql_fetch_array($meapu);
					$edimyyja = $apuro['tunnus'];
				}
				elseif ($arow["myyjanro"] != "")  {
					$query = "	SELECT tunnus
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'
								and myyja 	= '$arow[myyjanro]'";
					$meapu = pupe_query($query);

					if (mysql_num_rows($meapu) == 1) {
						$apuro = mysql_fetch_array($meapu);
						$edimyyja = $apuro['tunnus'];
					}
				}

				if ($yhtiorow["splittauskielto"] == "E") {
					$splittauskielto = "E";
				}
				else {
					$splittauskielto = "";
				}

				$til_vienti = $arow["vienti"];

				$tilaustyyppilisa = '';

				if ($edi_tyyppi == 'futursoft' and ($editilaus_tilaustyyppi == 2 or $editilaus_tilaustyyppi == 3)) {
					$tilaustyyppilisa = $editilaus_tilaustyyppi;

					if ($editilaus_tilaustyyppi == 3) {
						$editilaus_tila 	= 'C';
						$editilaus_alatila 	= 'A';
						$tilaustyyppilisa 	= 'R';
					}
				}

				// Tallennetaan ohjelmamoduli
				$ohjelmamoduli = strtoupper($edi_tyyppi);

				// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
				if ($maksaja == "" and $editilaus_rahtivapaa != "") {
					$maksaja = "K";
				}

				$prioriteettinro = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$arow['luokka']}'", "", "", "selitetark_3");

				// Default 9 myös kannassa, niin laitetaan tännekki 9 jos avainsanoista ei löydy prioa
				if (!is_numeric($prioriteettinro)) $prioriteettinro = 9;

				// Oletuksena näin
				$ohjausmerkki = $lopviesti;

				if ($tilaustyyppilisa == 2) {
					$ohjausmerkki = 'VARASTOTÄYDENNYS';
				}
				elseif (trim($ohjausmerkki) != "") {
					$_ohjausmerkki = $ohjausmerkki;
			 		$_ohjausmerkki = str_replace(array(" ", ",", ".", "-", "/", "+", ":"), "", $_ohjausmerkki);
			 		$_ohjausmerkki = strtolower($_ohjausmerkki);
					$_ohjausmerkki = str_replace(array("ä", "Ä"), "a", $_ohjausmerkki);
					$_ohjausmerkki = str_replace(array("ö", "Ö"), "o", $_ohjausmerkki);

					// FRSTTT = varastotayd joka on minimissään se mikä hyväksytään varastotäydennys tekstinä
			 		if (metaphone($_ohjausmerkki, 6) == 'FRSTTT') {
						$ohjausmerkki 	  = 'VARASTOTÄYDENNYS';
						$tilaustyyppilisa = 2;
					}
				}

				// Lisätään otsikko
				$query = "	INSERT into lasku SET
							aktiivinen_kuljetus					= '$suunnistus',
							alatila 		   					= '$editilaus_alatila',
							alv 			   					= '$arow[alv]',
							alv_tili							= '',
							asiakkaan_tilausnumero		   		= '$asiakkaan_tilausnumero',
							chn				   					= '$arow[chn]',
							clearing							= '',
							comments 		   					= '$edi_kommentti',
							eilahetetta							= '$editilaus_eilahetetta',
							erikoisale		   					= '$arow[erikoisale]',
							erpcm								= '',
							hinta								= '$editilaus_loppuhinta',
							huolitsija 							= '',
							hyvaksynnanmuutos 					= '{$arow['luokka']}',
							jakelu 								= '',
							jaksotettu							= '',
							jtkielto							= '$arow[jtkielto]',
							kassalipas							= '$editilaus_kassalipas',
							kauppatapahtuman_luonne				= '$arow[kauppatapahtuman_luonne]',
							kerayspvm 		   					= '$toimpvm',
							keraysvko 							= '',
							ketjutus		   					= '$arow[ketjutus]',
							kohde								= '',
							kohdistettu 	   					= '$maksaja',
							kuljetus 							= '',
							kuljetusmuoto						= '$arow[kuljetusmuoto]',
							laatija 		   					= '$edi_laatija',
							laskutusvkopv	   					= '$arow[laskutusvkopv]',
							liitostunnus       					= '$arow[tunnus]',
							luontiaika		   					=  now(),
							maa 			   					= '$arow[maa]',
							maa_maara							= '$arow[maa_maara]',
							maksuehto 		   					= '$editilaus_maksuehto',
							maksuteksti 						= '$editilaus_maksuteksti',
							myyja 			   					= '$edimyyja',
							nimi 			   					= '$arow[nimi]',
							nimitark 		   					= '$arow[nimitark]',
							ohjausmerkki						= '{$ohjausmerkki}',
							olmapvm								= '',
							osatoimitus							= '',
							osoite 			   					= '$arow[osoite]',
							ovttunnus 		   					= '$arow[ovttunnus]',
							piiri								= '$arow[piiri]',
							postino 		   					= '$arow[postino]',
							postitp 		   					= '$arow[postitp]',
							prioriteettinro						= '{$prioriteettinro}',
							rahtivapaa							= '$editilaus_rahtivapaa',
							sisainen							= '$editilaus_sisainen',
							sisviesti1         					= '$arow[sisviesti1]',
							sisviesti2         					= '$lopviesti',
							sisviesti3							= '{$merkkitieto}',
							splittauskielto						= '$splittauskielto',
							tila 			   					= '$editilaus_tila',
							tilaustyyppi						= '$tilaustyyppilisa',
							tilausvahvistus    					= '$vastaustapa',
							tilausyhteyshenkilo					= '',
							toimaika 		   					= '$toimpvm',
							toimitusehto 	   					= '$arow[toimitusehto]',
							toimitustapa 	   					= '$editilaus_toimitustapa',
							toimvko	 							= '',
							toim_maa		   					= '$arow[toim_maa]',
							toim_nimi		   					= '$arow[toim_nimi]',
							toim_nimitark	   					= '$arow[toim_nimitark]',
							toim_osoite		   					= '$arow[toim_osoite]',
							toim_ovttunnus 	   					= '$arow[toim_ovttunnus]',
							toim_postino	   					= '$arow[toim_postino]',
							toim_postitp	   					= '$arow[toim_postitp]',
							tunnusnippu							= '',
							valkoodi		   					= '$yhtiorow[valkoodi]',
							varasto 							= '',
							verkkotunnus	   					= '$arow[verkkotunnus]',
							vienti			   					= '$til_vienti',
							vienti_kurssi						= '1',
							viesti 			   					= '$viite',
							viikorkopros 						= '$yhtiorow[viivastyskorko]',
							yhtio 			   					= '$kukarow[yhtio]',
							yhtio_kotipaikka					= '$yhtiorow[kotipaikka]',
							yhtio_maa							= '$yhtiorow[maa]',
							yhtio_nimi							= '$yhtiorow[nimi]',
							yhtio_osoite						= '$yhtiorow[osoite]',
							yhtio_ovttunnus						= '$yhtiorow[ovttunnus]',
							yhtio_postino						= '$yhtiorow[postino]',
							yhtio_postitp						= '$yhtiorow[postitp]',
							yhtio_toimipaikka					= '',
							ytunnus			   					= '$arow[ytunnus]',
							ohjelma_moduli						= '$ohjelmamoduli'";
				$result = mysql_query($query) or die($query);
				$id = mysql_insert_id();

				$query = "	INSERT INTO laskun_lisatiedot SET
							laskutus_nimi 		= '$arow[laskutus_nimi]',
							laskutus_nimitark 	= '$arow[laskutus_nimitark]',
							laskutus_osoite		= '$arow[laskutus_osoite]',
							laskutus_postitp	= '$arow[laskutus_postitp]',
							laskutus_postino	= '$arow[laskutus_postino]',
							laskutus_maa		= '$arow[laskutus_maa]',
							laatija				= '$edi_laatija',
							luontiaika			= now(),
							yhtio				= '$kukarow[yhtio]',
							otunnus				= '$id'";
				$lisatiedot_result = mysql_query($query) or die($query);

				if ($edi_tyyppi == 'futursoft' and $editilaus_tilaustyyppi == 3) {
					$query = "	INSERT INTO tyomaarays SET
								yhtio = '{$kukarow['yhtio']}',
								otunnus = '{$id}'";
					$tyomaarays_ins_res = mysql_query($query) or die($query);
				}

				$edi_ulos .= t("Tilausnumero")." $id\n\n";

				// haetaan tilauksen tiedot
				$query    = "SELECT * from lasku where tunnus='$id' and yhtio='$kukarow[yhtio]'";
				$result   = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_array($result);

				//vientikieltotarkistus
				$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
							FROM asiakas
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus  = '$laskurow[liitostunnus]'";
				$vieres = pupe_query($query);
				$vierow = mysql_fetch_array($vieres);

				if (isset($vierow) and $vierow["maa"] != "") {
					$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
				}
				else {
					$kieltolisa = "";
				}

				// Tallennetaan alkuperäinen aineisto tilauksen liitetiedostoksi
				$liitefile = file_get_contents($mailfile);
				$filesize  = strlen($liitefile);
				$filename  = basename($mailfile);
				$liitefile = mysql_real_escape_string($liitefile);
				$fileslite = t("Tilausnumero")." $id";

				$query = "	INSERT INTO liitetiedostot SET
							yhtio    			= '$kukarow[yhtio]',
							liitos   			= 'lasku',
							liitostunnus 		= '$id',
							data     			= '$liitefile',
							selite   			= '$fileslite',
							kieli				= '$yhtiorow[kieli]',
							filename 			= '$filename',
							filesize 			= '$filesize',
							filetype 			= 'text/plain',
							image_width			= '',
							image_height		= '',
							image_bits			= '',
							image_channels		= '',
							kayttotarkoitus		= 'EDI',
							jarjestys			= '1',
							laatija				= '$edi_laatija',
							luontiaika			= now()";
				$Xresult = pupe_query($query);

				// HUOM: Laitetaan messu myyntiin jos asiakkaan nimi meidän kannassa on eri kuin tilaukselta tullut nimi
				if ($lnimitark != "" and substr($lnimitark, 0, 7) != "Autoasi" and $arow["yhtio"] == "artr" and gethostname() != 'demo.devlab.fi') {

					$asnokonflikti = "";

					// Kauttalaskutus
					if (strtoupper($arow["toim_nimi"]) != strtoupper($lnimitark) and $arow["yhtio"] == "artr" and $arow["osasto"] == "6") {
						$asnokonflikti = $arow["toim_nimi"];
					}
					elseif (strtoupper($arow["nimi"]) != strtoupper($lnimitark)) {
						$asnokonflikti = $arow["nimi"];
					}

					if ($asnokonflikti != "") {
						// lähetetään meili
						$bound   = uniqid(time()."_") ;

						$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
						$header .= "MIME-Version: 1.0\n" ;
						$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

						$mail  = "--$bound\n";
						$mail .= "Content-Type: text/plain;charset=iso-8859-1\n" ;
						$mail .= "Content-Transfer-Encoding: quoted-printable\n";
						$mail .= "\n";
						$mail .= "Tilausnumero: $id\n";
						$mail .= "Pvm: ".date("d.m.Y")."\n\n";
						$mail .= "Asiakkaan nimi Futursoft-aineistossa: $lnimitark\n";
						$mail .= "Asiakasnumero Futursoft-aineistossa: $OT_ASIAKASNRO\n\n";
						$mail .= "Asiakkaan nimi Pupesoftissa: $asnokonflikti\n";
						$mail .= "Asiakasnumero Pupesoftissa: $arow[asiakasnro]\n";
						$mail .= "--$bound--\n";

						#$boob = mail("myynti@orum.fi,messages@devlab.fi", mb_encode_mimeheader("Asiakasnumero-konflikti Futursoft-tilauksen sisäänluvussa", "ISO-8859-1", "Q"), $mail, $header, "-f $yhtiorow[postittaja_email]");
					}
				}
			}

			break;

		case 'OLINSA_NU' :
		case 'OSTOTILRIV.OTR_TUOTEKOODI' :
		case 'PIA':
			//Tuotenumero

			if ($edi_tyyppi == "edifact911") {
				$kaikki_kentat = explode(":", $tieto);
				$kentta = explode("+", $kaikki_kentat[1]);
				$tieto = $kentta[1];
			}

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsiä tuote tuotenumerolla
			if ($tuoteno == "" and $tieto != "") {

				if ($edi_tyyppi != "magento" and $edi_tyyppi != "futursoft" and $edi_tyyppi != "edifact911" and $yhtiorow["yhtio"] == "artr") {
					// poistetaan jos kolmas merkki jos se on viiva
					if (substr($tieto, 2, 1) == '-') {
						$tieto = substr($tieto, 0, 2) . substr($tieto, 3, 15);
					}
				}

				// Poistetaan spacet
				if (in_array($yhtiorow["yhtio"], array("allr","mast"))) {
					$tieto = str_replace(" ", "", $tieto);
				}

				if (strpos($tieto, "@") !== FALSE) {
					list($tieto) = explode("@", $tieto);
					$tieto = trim($tieto);
				}

				// Spessukase
				if ($yhtiorow["yhtio"] == "artr") {
					// 1. Etsitään aktiivisia tuotteita kaikki spacet poistettuina
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$kukarow["yhtio"]}'
								and tuoteno = '{$tieto}'
								and status != 'P'
								{$kieltolisa}";
					$result = mysql_query($query) or die($query);

					if (mysql_num_rows($result) == 0) {
						// Laitetaan kolmanneksi merkiksi space...
						$tieto = substr($tieto, 0, 2)." ".substr($tieto, 2);

						// 2. Laitetaan kolmanneksi merkiksi space ja tsekataan löytyykö aktiivinen
						$query = "	SELECT *
									FROM tuote
									WHERE yhtio = '{$kukarow["yhtio"]}'
									and tuoteno = '{$tieto}'
									and status != 'P'
									{$kieltolisa}";
						$result = mysql_query($query) or die($query);

						if (mysql_num_rows($result) == 0) {
							// Poistetaan kolmannesta merkistä space...
							$tieto = substr($tieto, 0, 2).substr($tieto, 3);

							// 3. Poistetaan kolmannesta merkistä space ja tsekataan löytyykö poistettu
							$query = "	SELECT *
										FROM tuote
										WHERE yhtio = '{$kukarow["yhtio"]}'
										and tuoteno = '{$tieto}'
										{$kieltolisa}";
							$result = mysql_query($query) or die($query);

							if (mysql_num_rows($result) == 0) {
								// Laitetaan kolmanneksi merkiksi space...
								$tieto = substr($tieto, 0, 2)." ".substr($tieto, 2);

								// 4. Laitetaan kolmanneksi merkiksi space ja tsekataan löytyykö poistettu
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio = '{$kukarow["yhtio"]}'
											and tuoteno = '{$tieto}'
											{$kieltolisa}";
								$result = mysql_query($query) or die($query);

								if (mysql_num_rows($result) == 0) {
									// Jos ei millään löydy niin laitetaan ilman spaceja eteenpäin...
									$tieto = substr($tieto, 0, 2).substr($tieto, 3);
								}
							}
						}
					}
				}

				$query = "	SELECT *
							FROM tuote
							WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
							and tuote.tuoteno = '{$tieto}'
							{$kieltolisa}";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lisätään tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tieto' ".t("ei löydy")."!\n";
					$tuoteerrortxt .= " $tieto";
					$tuoteerror = 1;
				}
				else  {
					$trow = mysql_fetch_assoc($result);

					if ($trow['panttitili'] == 'K') {
						$query = "	SELECT *
									from tuoteperhe
									where tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
									and tuoteperhe.tuoteno = '{$trow["tuoteno"]}'
									and tuoteperhe.ohita_kerays != ''";
						$result = mysql_query($query) or die($query);

                        // Runkotuotteita ei saa tilata suoraan!
						if (mysql_num_rows($result) != 0) {
							///* Lisätään tilaukselle kommentti *///
							$edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tieto' ".t("on runko")."!\n";
							$tuoteerrortxt .= " $tieto";
							$tuoteno = $trow['tuoteno'];
							$tuoteerror = 2;
						}
						else {
							$edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $tieto ";
							$tuoteno = $trow['tuoteno'];
							$tuoteerror = 0; // tuote ok
						}
					}
					else {
						$edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $tieto ";
						$tuoteno = $trow['tuoteno'];
						$tuoteerror = 0; // tuote ok
					}
				}
			}
			break;

		case 'OLINSA_PI' :
			// tämän rivin asiakkaan oma tuotenumero
			$type = explode("@", $tieto);
			$olinsapi    = trim($type[0]);
			$olinvarasto = trim($type[2]); // kolmannessa segmentissä tulee meidän varastopaikka jos lähettäjä tietää sen
			break;

		case 'OLINEN_NU' :
		case 'LIN':
			// tuotteen EAN-koodi

			if ($edi_tyyppi == "edifact911") {
				$kaikki_kentat = explode("++", $tieto);
				$ean_kentta = explode(':', $kaikki_kentat[1]);
				$tieto = $ean_kentta[0];
				$rivinumero = $kaikki_kentat[0]; // Tästä segmentistä saadaan myös rivinumero
			}

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsiä tuote EAN koodilla
			if ($tuoteno == "" and $tieto != "") {

				$query = "	SELECT *
							FROM tuote USE INDEX (yhtio_eankoodi_index)
							WHERE yhtio = '$kukarow[yhtio]'
							and eankoodi = '$tieto'
							$kieltolisa
							ORDER BY tunnus";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lisätään tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Rivi")." $rivinumero EAN '$tieto' ".t("ei löydy")."!\n";
					$tuoteerrortxt .= " $tieto";
					$tuoteerror = 1;
				}
				else {
					$trow = mysql_fetch_array($result);
					$edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $trow[tuoteno] / EAN: $tieto ";
					$tuoteno = $trow['tuoteno'];
					$tuoteerror = 0; // tuote ok
				}
			}

			break;

		case 'OLIN8__IF' :
			// asiakkaan antama nimitys tuotteelle
			$type = explode("@", $tieto);
			$edi_nimitys = $type[4]; // rivin neljännessä segmentissä tulee oikee info
			break;

		case 'OLIN21_QT' :
		case 'OSTOTILRIV.OTR_TILATTUMAARA' :
		case 'QTY':

			if ($edi_tyyppi != "edifact911") {
				//Tilattava määrä
				$tieto = str_replace (",", ".", $tieto);
			}

			if ($edi_tyyppi == "magento" and (strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero']) or strtolower($tuoteno) == strtolower($yhtiorow['alennus_tuotenumero'])) and $tieto != '') {
				$kpl = (float) substr($tieto,0,16);
			}
			elseif ($edi_tyyppi == "edifact911") {
				$kentat = explode(":", $tieto);
				$kpl = (float) abs($kentat[1]);
			}
			else {
				$kpl = (float) abs(substr($tieto,0,16)); // abs() ettei asiakkaat pysty tekemään hyvityksiä!
			}

			$edi_ulos .= t("tilattiin")." $kpl ".t("kpl").".\n";
			break;

		case 'OSTOTILRIV.OTR_OSTOHINTA' :
			// Kun tullaan magentosta ja meillä on rahtikulu, niin otetaan hinta talteeen
			// TAI kun tullaan magentosta ja me luotetaan verkkokaupan hintoihin.
			if ($edi_tyyppi == "magento" and ((isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") or strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero'])) and $tieto != '') {
				// Tämä hinta on aina veroton, joten lasketaan vero päälle jos myyntihinnat ovat verollisia
				$editilaus_hinta = (float) trim($tieto);

				if ($yhtiorow["alv_kasittely"] == '') {
					$editilaus_hinta = round($editilaus_hinta * (1 + $laskurow["alv"]/100), 2);
				}
			}
			break;

		case 'OSTOTILRIV.OTR_ALENNUS' :
			// Kun tullaan magentosta ja meillä on alennus, niin otetaan alennus talteen jos me luotetaan verkkokaupan hintoihin.
			if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $tieto != '') {
				$editilaus_alennus = (float) trim($tieto);
			}
			break;

		case 'OSTOTILRIV.OTR_SALLITAANJT' :
			// Sallitaan JT-Rivit
			if ($tieto == '1') {
				$sallitaanjt = '1';
			}
			else {
				$sallitaanjt = '0';
			}

			break;

		case 'OSTOTILRIV.OTR_VIITE' :
			$rivikommentti = pupesoft_cleanstring($tieto);
			break;

		case 'OLIN__END' :
		case '*RE OSTOTILRIV' :
		case 'RFF':
		case 'PCD':
		case 'BGM':

			if ($edi_tyyppi == "edifact911") {
				$kentat = explode(":", $tieto);
				$kentat_bgm = explode("+", $tieto);
				// BGM kentässä tulee 'BGM+QQQ+ABCDEFGHIJKLM'
				// QQQ = arvot, 105=tilaus, 640=toimituskehoitus, 221=plokkitilaus/ennakkotilaus
				// ABCDEFGHIJKLM = tossa tulee myymälän tilausnumero, joka pitäisi olla sama kuin RFF+VN kentän arvo.

				if ($asiakkaan_tilausnumero == "" and $kentat_bgm[0] == '105' and $kentat_bgm[1] != "") {
					$asiakkaan_tilausnumero = $kentat_bgm[1];
					$viite = $kentat_bgm[1]. ' ';
					break;
				}

				// Orders 91.1 tulee kaksi RFF segmenttiä (VN ja WS). VN otsikon viite, joten ei liity tilausriveihin. WS on tilausrivin loppumisen merkki.
				if ($kentat[0] == "VN" and $asiakkaan_tilausnumero == "") {
					$asiakkaan_tilausnumero = $kentat[1];
					$viite = $kentat[1]. ' ';
					break;
				}


			}

			if ($editilaus_otsikko_ok == "KYLLA") {

		 		// jos tuote on löytynyt ok
				if ($tuoteerror == 0) {

					// halutaan $tuoteno
					// $kpl kappaletta
					// $trow jossa on tuotten tiedot
					$ytunnus    			= $laskurow["ytunnus"];
					$hinta 					= "";
					$netto 					= "";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'ale'.$alepostfix} = "";
					}

					$var					= "";
					$alv					= "";
					$toimaika 				= $laskurow["toimaika"];
					$kerayspvm				= $laskurow["kerayspvm"];
					$paikka					= "";
					$kukarow["kesken"] 		= $laskurow["tunnus"];
					$jtkielto 				= $laskurow['jtkielto'];
					$varasto 				= array();
					$kommentti				= $rivikommentti;
					$laskurow["varasto"]	= "";

					if ($edi_tyyppi == "magento") {
						// Nämä ovat ainoat varastot joista verkkokauppa saa myydä
						if ($verkkokauppavarasto == "") {
							$query	= "	SELECT GROUP_CONCAT(tunnus) tunnukset
										FROM varastopaikat
										WHERE yhtio = '$kukarow[yhtio]'
										AND prioriteetti > 0 AND tyyppi != 'P'";
							$pres = pupe_query($query);
							$prow = mysql_fetch_assoc($pres);

							if ($prow["tunnukset"] != "") {
								$verkkokauppavarasto 	= explode(",", $prow["tunnukset"]);
								$verkkokauppavarasto_li = " and varastopaikat.tunnus in ($prow[tunnukset]) ";
								$laskurow["varasto"] 	= $verkkokauppavarasto[0];
							}
						}

						$varasto = $verkkokauppavarasto;
					}
					elseif ($olinvarasto != "") {
						$varasto = $olinvarasto;
					}

					// jos terminaalitoimitus niin ohitetaan kaikki saldozekit, sekä laitetaan kommenttiriville asiakkaan tuotenumero ja nimitys
					if ($editilaus_tila == "Z") {
						$var = "Y";
						$kommentti = "$olinsapi#$edi_nimitys";
					}

					// jos tullaan magentosta ja hinta on tiedossa ja kyseessä on rahtikulu, niin uskalletaan luottaa magenton antamaan hintaan
					if ($edi_tyyppi == "magento" and ((isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") or strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero'])) and $editilaus_hinta > 0) {
						$hinta = $editilaus_hinta;
					}

					// jos tullaan magentosta ja alennus on tiedossa
					if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $editilaus_alennus > 0) {
						$ale1 = $editilaus_alennus;
					}

					// Magento-tilauksessa katsotaan onko tilattavalla tuotteella saldoa.
					// Jos tilattu kpl-määrä on suurempi kuin myytävissä oleva kpl-määrä, niin pakotetaan rivi hyväksytyksi
					if ($edi_tyyppi == "magento" and $tuoteno != '' and $kpl > 0 and $trow["ei_saldoa"] == "") {

						list($chk_saldo, $chk_hyllyssa, $chk_myytavissa, $chk_true) = saldo_myytavissa($tuoteno, "", $verkkokauppavarasto);

						// jos saldo ei riitä normivarastoista, pitää katsoa missä varastossa riittää ja myydään sieltä (koska jossain riittää, muuten tilaus ei olisi edes tullu tänne asti)
						if ($chk_myytavissa < $kpl) {

							$query = "	SELECT distinct varastopaikat.tunnus varasto, varastopaikat.prioriteetti
										FROM tuotepaikat
										JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
											AND varastopaikat.tyyppi != ''
											AND varastopaikat.prioriteetti > 0
											AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
											AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
											$verkkokauppavarasto_li)
										WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
										AND tuotepaikat.tuoteno = '$tuoteno'
										ORDER BY prioriteetti";
							$sres = pupe_query($query);

							$erikoisvarastojen_saldot = array();
							$erikois_i = 0;

							while ($erikoisasaldo_row = mysql_fetch_assoc($sres)) {
								list($chk_saldo, $chk_hyllyssa, $chk_myytavissa, $chk_true) = saldo_myytavissa($tuoteno, '', $erikoisasaldo_row["varasto"]);

								if ($chk_myytavissa >= $kpl) {
									$erikoisvarastojen_saldot[$erikois_i]["varasto"] = (int) $erikoisasaldo_row["varasto"];
									$erikoisvarastojen_saldot[$erikois_i]["myytavissa"] = (float) $chk_myytavissa;
									$erikoisvarastojen_saldot[$erikois_i]["prioriteetti"] = (int) $erikoisasaldo_row["prioriteetti"];
									$erikois_i++;
								}
							}

							if (count($erikoisvarastojen_saldot) > 0) {
								// Sortataan 2 dimensoinen array. pitää ensiksi tehdä sortattavista keystä omat arrayt
								$apusort_jarj0 = $apusort_jarj1 = array();
								foreach ($erikoisvarastojen_saldot as $apusort_key => $apusort_row) {
								    $apusort_jarj0[$apusort_key] = $apusort_row['prioriteetti'];
									$apusort_jarj1[$apusort_key] = $apusort_row['myytavissa'];
								}

								// sortataan by myytavissa, sorttauskenttä
								array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_DESC, $erikoisvarastojen_saldot);

								$varasto = $erikoisvarastojen_saldot[0]["varasto"]; // ekassa varastossa on pienin prioriteetti
								$laskurow["varasto"] = $varasto; // joudutaan spooffaamaan laskurow varasto

								// Tilausrivin systeemikommentti
								$kommentti .= t("HUOM: Tuote toimitetaan erikoisvarastosta!");
							}
							else {
								$var = "H";
							}
						}
					}

					$kutsuja = "EDITILAUS";

					// Jos meillä on täppä päällä, että asiakas haluaa kamat jälkitoimitukseen
					if ($sallitaanjt == '1') {
						$var 		= "J";
						$kommentti 	= ""; // HUOM: nollataan kommentti
					}

					if ($edi_tyyppi == 'futursoft' and $editilaus_tilaustyyppi == 3) {
						$kpl = abs($kpl) * -1;
					}

					require ("lisaarivi.inc");

					$edi_ulos .= "\n";

				} // end jos tuote on löydetty oikein
				else {

					if ($tuoteerror == 2)  {
					    // Tilausrivin systeemikommentti
    					$kommentti   .= t("Yritettiin tilata pelkkää runkoa").$tuoteerrortxt;
    					$virhetuoteno = $tuoteno;
					}
					else {
					    // Tilausrivin systeemikommentti
    					$kommentti   .= t("Yritettiin tilata tuntematonta tuotetta").$tuoteerrortxt;
    					$virhetuoteno = "TUNTEMATON";
					}

					$query = "	INSERT into tilausrivi set
								tilaajanrivinro = '$rivinumero',
								laatija 	= 'edi',
								laadittu 	= now(),
								yhtio 		= '$kukarow[yhtio]',
								tuoteno 	= '$virhetuoteno',
								var			= 'P',
								kpl 		= '0',
								alv			= '".alv_oletus()."',
								tilkpl 		= '$kpl',
								otunnus 	= '$id',
								tyyppi 		= '$editilaus_tyyppi',
								kommentti 	= '$kommentti'";
					$result = mysql_query($query) or die($query);
				}
			}

			// nollataan kaikki rivin muuttujat (nämä samat pitää tehdä filen alussa)
			$tuoteerror      	= 1; // oletetaan aina ettei  tuotetta löydy
			$tuoteerrortxt   	= "";
			$kpl             	= "";
			$rivinumero      	= "";
			$kommentti       	= "";
			$tuoteno         	= "";
			$olinsapi        	= "";
			$olinvarasto     	= "";
			$edi_nimitys     	= "";
			$sallitaanjt	 	= "";
			$editilaus_hinta	= "";
			$editilaus_alennus	= "";
			break;

		case 'OMSG__END' :
		case '*IE' :
		case 'UNT':

			// jos meilla on otsikko voidaan laittaa sen valmiiksi
			if ($editilaus_otsikko_ok == "KYLLA") {
				//tarkistetaan toimitustapa vakin takia
				$query = " 	SELECT *
				  			FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$id'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) > 0) {
					$query = "	SELECT vak_kielto
								FROM toimitustapa
								WHERE yhtio = '$kukarow[yhtio]'
								and selite = '$laskurow[toimitustapa]'";
					$result = mysql_query($query) or die($query);
					$toimtapatmp = mysql_fetch_array($result);

					if ($toimtapatmp['vak_kielto'] != '' and $toimtapatmp['vak_kielto'] != 'K') {
						$query = "	SELECT selite
									FROM toimitustapa
									WHERE yhtio = '$kukarow[yhtio]'
									AND selite = '$toimtapatmp[vak_kielto]'
									AND vak_kielto = ''";
						$result = pupe_query($query);

						if (mysql_num_rows($result) == 1) {
							$query = "UPDATE lasku SET toimitustapa = '$toimtapatmp[vak_kielto]' where yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
							$result = mysql_query($query) or die($query);
						}
					}
				}

				if ($editilaus_tilaustyyppi != 'K') {
					// Tilaus merkataan valmiiksi
					$query = "UPDATE lasku SET alatila='A' WHERE tunnus='$id' AND yhtio='$kukarow[yhtio]'";
					$result = mysql_query($query) or die($query);
				}

				// haetaan tilauksen tiedot
				$query    = "SELECT * FROM lasku WHERE tunnus='$id' AND yhtio='$kukarow[yhtio]'";
				$result   = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_array($result);

				// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
				$kukarow["kesken"] = $laskurow["tunnus"];
				$editilaus_lasku_tunnus = $laskurow["tunnus"];

				// jos terminaalitoimitus niin EI tehdä tilaus-valmista...
				if ($editilaus_tila != "Z" and $editilaus_tilaustyyppi != 3 and $editilaus_tilaustyyppi != 'K') {

					if (in_array($yhtiorow['jt_automatiikka'], array('Y','W','K'))) {
						//lisätään tilaukseen ne jt-rivit jotka riittävät kaikille
						$edi_ulos .= "\n";
						jt_toimita_editilaus($laskurow["ytunnus"], $laskurow["liitostunnus"], $laskurow["maa"]);
						$edi_ulos .= "\n";
					}

					$edi_ulos .= "\n";

					$silent = "SILENT";
					$tilausvalmiskutsuja = "EDITILAUS";

					require ("tilaus-valmis.inc");

					// jos magentosta tullut tilaus on jo maksettu, päivitetään se takaisin keräysjonoon (paitsi tilaustyyppi 9, jossa halutaan ohittaa varastoprosessi ja laskuttaa kaikki)
					if ($edi_tyyppi == "magento" and $editilaus_kassalipas != "" and $editilaus_tilaustyyppi != "9") {
						// Haetaan yrityksen ensimmäinen normivarasto. Tallennetaan tilaus tähän varastoon
						$varasto_query = "	SELECT tunnus
											from varastopaikat
											where yhtio = '$kukarow[yhtio]'
											and tyyppi = ''
											order by alkuhyllyalue
											limit 1";
						$varasto_res = pupe_query($varasto_query);
						$varasto_row = mysql_fetch_array($varasto_res);

						$query = "	SELECT hyllyalue, hyllynro
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									AND hyllyalue != ''
									AND hyllynro != ''
									AND otunnus = '$editilaus_lasku_tunnus'";
						$edi_varastopaikka_res = pupe_query($query);
						$edi_varastopaikka_row = mysql_fetch_array($edi_varastopaikka_res);

						$tulostusalue = onkotulostusalueita($edi_varastopaikka_row["hyllyalue"], $edi_varastopaikka_row["hyllynro"], $varasto_row['tunnus'], $kukarow['yhtio']);

						$vielisa = "";
						if ($til_vienti == "") $vielisa = "chn = '999', ";

						// Palautetaan tilaus takaisin "myyntitilaus keräysjonossa"-tilaan, lisätään päälle laskutuskielto varmuuden vuoksi (ei vientitilauksille)
						$query = "	UPDATE lasku SET
									mapvm 			= now(),
									tila 			= 'N',
									alatila 		= 'A',
									$vielisa
									varasto 		= '$varasto_row[tunnus]',
									sisviesti2 		= 'Verkkokauppatilaus! Tilauksen tietoja ei saa muuttaa.',
									tulostusalue 	= '$tulostusalue'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus  = '$editilaus_lasku_tunnus'";
						$magento_maksettu_res = pupe_query($query);

						// haetaan tilauksen tiedot
						$query    = "SELECT * FROM lasku WHERE tunnus = '$editilaus_lasku_tunnus' AND yhtio = '$kukarow[yhtio]'";
						$result   = mysql_query($query) or die($query);
						$laskurow = mysql_fetch_array($result);

						// pitää nollata toimitettuaika riveille
						$query = "	UPDATE tilausrivi SET
									toimitettu = '',
									toimitettuaika = ''
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = '$editilaus_lasku_tunnus'";
						$result = mysql_query($query) or die($query);

						// pitää nollata kerattyaika kaikille saldollisille tuotteille
						$query = "	UPDATE tilausrivi SET
									keratty = '',
									kerattyaika = ''
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = '$editilaus_lasku_tunnus'
									AND keratty != 'saldoton'";
						$result = mysql_query($query) or die($query);

						// ei haluta käsitellä tätä toista kertaa kassamyyntinä, koska se estää splittauksen
						$kukarow["kassamyyja"] = $editilaus_kassalipas = "";

						// kutsutaan uudestaan tilaukselle tilaus-valmis, niin saadaan splitattua se varastoihin
						$silent = "SILENT";
						$tilausvalmiskutsuja = "EDITILAUS";

						require ("tilaus-valmis.inc");
					}

					// Halutaanko tilaus hyväksynnän kautta!
					if ($tilaus_hyvaksyntaan != "") {
						$query = "	UPDATE lasku SET
									alatila = 'F'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$editilaus_lasku_tunnus'";
						$magento_maksettu_res = pupe_query($query);
					}

					$edi_ulos .= "$tilaus_valmis_ulos\n";
					$edi_ulos .= "$tilaus_valmis_message\n";
					$edi_ulos .= "\n".t("Merkittiin tilaus valmiiksi").": $editilaus_lasku_tunnus.\n\n";
				}
				elseif ($editilaus_tila == "Z") {
					$edi_ulos .= "\n".t("Terminaalitoimitus vastaanotettu").": $editilaus_lasku_tunnus.\n\n";
				}
				elseif ($editilaus_tilaustyyppi == 3) {
					$edi_ulos .= "\n".t("Reklamaatio vastaanotettu").": $editilaus_lasku_tunnus.\n\n";
				}
			}
			else {
				$edi_ulos .= "\n".t("Otsikko oli virheellinen tilausta/rivejä ei lisätty")."!\n\n";
			}

			// nollataan kaikki laskun muuttujat (kaikki nämä samat pitää nollata filen alussa)
			unset($arow);
			unset($arow_viite);
			unset($arow_ostaja);
			unset($arow_toimitus);
			unset($arow_terminaali);
			$toimpvm 	 			= date("Y-m-d");
			$vastaustapa 			= "";
			$editilaus_tila       	= "N"; // laskun tila
			$editilaus_alatila		= "";
			$editilaus_tyyppi     	= "L"; // tilausrivi tyyppi
			$id                   	= "";
			$edi_kommentti        	= "";
			$vastaustapa          	= "";
			$maksaja              	= "";
			$viite                	= "";
			$asiakkaan_tilausnumero	= "";
			$editilaus_otsikko_ok 	= "";
			$suunnistus           	= "";
			$tnimi                	= "";
			$tnimitark            	= "";
			$tpostitp             	= "";
			$tpostino             	= "";
			$tosoite              	= "";
			$lnimi					= "";
			$lnimitark				= "";
			$lpostitp				= "";
			$lpostino				= "";
			$losoite				= "";
			$lopnumero            	= "";
			$lopnimi              	= "";
			$loppostitp           	= "";
			$loppostino           	= "";
			$loposoite            	= "";
			$ltoim_maa				= "";
			$yhteyshenkilon_puhelin = "";
			$lopviesti            	= "";
			$ketjutus             	= "";
			$editilaus_maksuehto	= "";
			$editilaus_maksuteksti	= "";
			$editilaus_toimitustapa = "";
			$tilaus_hyvaksyntaan	= "";
			$editilaus_rahtivapaa	= "";
			$editilaus_tilaustyyppi = "";
			$editilaus_eilahetetta	= "";
			$editilaus_sisainen		= "";
			$editilaus_kassalipas	= "";
			$til_vienti				= "";
			$verkkokauppavarasto	= "";
			$verkkokauppavarasto_li	= "";
			$editilaus_loppuhinta	= "";
			$kieltolisa				= "";
			$OT_ASIAKASNRO			= "";
			break;
	}
}

// lähetetään meili
if (strpos($_SERVER['SCRIPT_NAME'], "tilaus_in.php") === FALSE and isset($yhtiorow) and $yhtiorow["edi_email"] != "") {

	$edi_ulos = strip_tags($edi_ulos);

	$bound   = uniqid(time()."_") ;
	$kutsu   = $yhtiorow["yhtio"]."-ediorder.txt";

	$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	$content  = "--$bound\n";
	$content .= "Content-Type: text/plain;charset=iso-8859-1\n" ;
	$content .= "Content-Transfer-Encoding: quoted-printable\n";
	$content .= $edi_ulos.t("Käsitelty tiedosto liitteenä").".\n\n";

	$content .= "--$bound\n";
	$content .= "Content-Type: text/plain; name=\"".$kutsu."\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: attachment; filename=\"".$kutsu."\"\n\n";

	$handle  = fopen($mailfile, "r");
	$sisalto = fread($handle, filesize($mailfile));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\n";
	$content .= "--$bound--\n";

	$boob = mail($yhtiorow["edi_email"], mb_encode_mimeheader($edi_subj." - $yhtiorow[nimi]", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
}
else {
	echo "\n";
	echo $filename;
	echo "\n";
	echo $edi_ulos;
	echo "\n";
}
