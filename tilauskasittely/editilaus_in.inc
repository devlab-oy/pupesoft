<?php

$debug = 0;
$edi_ulos = "\n";

// jos meill‰ ei ole sessiota ja meill‰ on komentoriviparametri annettuna
if (trim($argv[1]) != '') {

	if ($argc == 0) die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// ja parametri komentorivilt‰
	$filename = $argv[1];

	if (trim($argv[2]) == 'futursoft' and substr(strtolower(basename($filename)), 0, 13) == 'magento-order') {
		$argv[2] = 'magento';
	}

	// ja toinen parametri komentorivilt‰
	$edi_tyyppi	= $argv[2];

	// laitetaan pupe-rootti ja tilausk‰sittely mukaan includepathiin
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname($argv[0])).PATH_SEPARATOR.dirname($argv[0]));
}

if (!function_exists('jt_toimita_editilaus')) {
	function jt_toimita_editilaus ($asiakasno, $asiakasid, $asiakasmaa) {
		global $yhtiorow, $kukarow, $edi_ulos;

		$automaaginen 			= "automaaginen";
		$tee 					= "JATKA";
		$oikeurow['paivitys'] 	= '1';
		$toimittaja				= "";
		$toimittajaid			= "";
		$jarj	 				= "tuoteno";
		$tuotenumero			= "";
		$tilaus					= "";
		$toimi					= "";
		$superit				= "";
		$tilaus_on_jo			= "KYLLA";
		$from_varastoon_inc 	= "editilaus_in.inc";

		if ($asiakasmaa != '') {
			$asiakasmaalisa = "and (varastopaikat.sallitut_maat like '%$asiakasmaa%' or varastopaikat.sallitut_maat = '')";
		}

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi  = ''
					$asiakasmaalisa";
		$vtresult = mysql_query($query) or pupe_error($query);

		$varastosta = array();

		while ($vrow = mysql_fetch_array($vtresult)) {
			$varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
		}

		if ($asiakasid > 0 and count($varastosta) > 0) {
			require('jtselaus.php');
		}
	}
}

// nollataan kaikki laskun muuttujat (n‰m‰ samat pit‰‰ tehd‰ whilen lopussa)
unset($arow);
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
$lopviesti            	= "";
$ketjutus             	= "";
$editilaus_maksuehto	= "";
$tilaus_hyvaksyntaan	= "";
$editilaus_rahtivapaa	= "";
$editilaus_kassalipas	= "";
$editilaus_rahtikulu_hinta = "";

// nollataan kaikki rivin muuttujat (n‰m‰ samat pit‰‰ tehd‰ tuote insertin j‰lkeen)
$tuoteerror      = 1; // oletetaan aina ettei  tuotetta lˆydy
$tuoteerrortxt   = "";
$kpl             = "";
$rivinumero      = "";
$kommentti       = "";
$tuoteno         = "";
$olinsapi        = "";
$olinvarasto     = "";
$edi_nimitys     = "";
$edi_sarjanumero = "";
$edi_hinta       = "";
$korvaavakielto  = "";
$perhekielto     = "";
$sallitaanjt	 = "";

// fiilataan parametrej‰ joita tarvitaan require fileiss‰
$tulos_ulos = "edi"; // t‰‰ on ftp-send.inciss‰

// tarvitaan $filename
$filename = trim($filename);
if ($filename == "") die('Tiedostonimi puuttui!\n');

if (!$fd = fopen($filename, "r")) die("Filen '$filename' ".t("avaus ep‰onnistui")."!\n");

//jotta filenimi ei varmasti katoaisi matkalla
$mailfile = $filename;

$toimpvm 	 = date("Y-m-d");
$vastaustapa = "";

if ($edi_tyyppi == "futursoft") {
	$edi_subj = "Sis‰‰ntuleva Futursoft-tilaus";
	$edi_laatija = "FuturSoft";
}
elseif ($edi_tyyppi == "magento") {
	$edi_subj = "Sis‰‰ntuleva Verkkokauppa-tilaus";
	$edi_laatija = "Magento";
	$editilaus_rahtivapaa = "o";
}
else {
	$edi_subj = t("Sis‰‰ntuleva EDI-tilaus");
	$edi_laatija = "EDI";
}

$edi_ulos .= "$edi_subj\n";

while (!feof ($fd)) {

	$tietue = fgets($fd, 4096);

	// t‰‰ on futurikeisi
	if ($edi_tyyppi == "futursoft" or $edi_tyyppi == "magento") {

		if (substr($tietue,0,1) != '*') {
			list($tunnus, $tieto)  = explode(':',$tietue);
		}
		else {
			$tunnus = $tietue;
			$tieto	= "";
		}

		$tunnus = trim($tunnus);
		$tieto  = trim($tieto);

		if (substr($tunnus,0,15) == '*RE  OSTOTILRIV') {
			$tunnus='*RE  OSTOTILRIV'; // Poistetaan h‰iritsev‰ nro
		}

	}
	else {
		$tunnus = substr($tietue,0,9);
		$tieto  = trim(substr($tietue,10));
	}

	// tehd‰‰n kaikille tiedoille 7 -> 8 bit ‰‰kkˆskonversio..
	$from   = array ('{','}','|','[',']','\\',chr(179)); // 7bit
	$to     = array ('‰','Â','ˆ','ƒ','≈','÷','|'); // 8bit
	$tieto  = str_replace($from, $to, $tieto);

	switch ($tunnus) {
		case 'ICHG_RCPT':
		case 'OSTOTIL.OT_TOIMITTAJANRO':
		case 'OSTOTIL.OT_OVTTUNNUS':
		//Tilauksen vastaanottaja

			// Arwidson h‰kki Arwidson Automotivea varten
			if ($tieto == "003701066781") $tieto = "003707495288";

			$edi_ulos .= t("Tilaus yritykselle")." '" . $tieto .  "' ";

			$query = "SELECT * FROM yhtio WHERE ovttunnus='$tieto' and ovttunnus != ''";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) {
				// kokeillaan v‰‰nt‰‰ luvusta ytunnus
				$tieto2 = substr($tieto, 4, 8);     // etunollilla
				$tieto  = substr($tieto, 4, 8) * 1; // ilman etunollio

				// Arwidson h‰kki Arwidson Baltia varten
				if ($tieto == 0) $tieto = "07495288";
				// Arwidson h‰kki Arwidson Automotivea varten
				if ($tieto == 1066781) $tieto = "07495288";
				// Arwidson h‰kki ÷rumia varten
				if ((int) $tieto == 7495288) $tieto = "20428100";

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$tieto', '$tieto2') and ytunnus != ''";
				$result = mysql_query($query) or die($query);
			}

			if (mysql_num_rows($result) != 1) {
				$edi_ulos .= "\n".t("Vastaanottavaa yrityst‰ ei lˆydy tunnuksella")." '$tieto', '$tieto2', '$mailfile'\n";
				echo $edi_ulos;
				exit;
			}

			$yhtiorow = mysql_fetch_array($result);

			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query)
					or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				// lis‰t‰‰n kaikki yhtiorow arrayseen, niin ollaan taaksep‰inyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

			$kukarow['yhtio'] = $yhtiorow['yhtio']; // T‰m‰ on vaarallista, mutta t‰t‰ ilman ei mik‰n toimi!
			$kukarow['kuka'] = $edi_laatija;

			// nollataan argv ja laitetaan ensimm‰iseksi paratemetriksi yhtio, niin verkkolaskutus toimii!
			$argv = array();
			$argv[1] = $kukarow["yhtio"];

			$edi_ulos .= "-> $yhtiorow[nimi] ($kukarow[yhtio])\n";
			break;

		case 'ONADBY_DO' :
		case 'OSTOTIL.OT_VAHVISTUS_FAKSILLA' :
		//Faxvastaus
			$tieto = substr($tieto,4,2);
			if ($tieto == 'FX' or $tieto == '1') {
				$vastaustapa .= 'F';
				$edi_ulos .= t("Pyyt‰‰ Fax-tilausvahvistuksen").".\n";
			}
			break;

		case 'OBGMIACKR' :
		//Edivastaus
			if ($tieto == "AB") {
				$vastaustapa .= 'E';
				$edi_ulos .= t("Pyyt‰‰ EDI-tilausvahvistuksen").".\n";
			}
			if ($tieto == "S") {
				$vastaustapa .= 'S';
				$edi_ulos .= t("Pyyt‰‰ s‰hkˆposti-tilausvahvistuksen").".\n";
			}
			if ($tieto == "F") {
				$vastaustapa .= 'F';
				$edi_ulos .= t("Pyyt‰‰ fax-tilausvahvistuksen").".\n";
			}
			break;

		case 'OBGMITYPE' :

			$type = split("@", $tieto);

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

			// jos TRM ni ei kai mit‰‰ spessua
			if (trim($type[1]) == "TRM") {

			}

			// Pupesoft tilaustyyppi
			if ($tieto == '1') {
				$edi_ulos .= t("Laskua ei saa ketjuttaa")."!\n";
				// tyyppi yks on pupesoft <-> pupesoft suoratoimitustilaus, silloin ei ketjuteta laskua ikin‰
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
		//Toimituslauseke
			if (trim($tieto) != '') {
				$edi_kommentti .= t("Asiakas pyysi toimitustapaa").": $tieto ";
			}
			break;

		case 'OFTXGENTX' :
		//Kommentti
			$edi_kommentti .= $tieto . ' ';
			break;

		case 'ODTM2__DT' :
		// Toimitusp‰iv‰
			$type = split("@", $tieto);

			// 102 on p‰iv‰m‰‰r‰, 616 on viikkonumero
			if (trim($type[1]) == "102") {
				$tieto = trim($type[0]);
				$editilaus_toimitusvv = substr($tieto, 0, 4);
				$editilaus_toimituskk = substr($tieto, 4, 2);
				$editilaus_toimituspp = substr($tieto, 6, 2);
				if (checkdate($editilaus_toimituskk, $editilaus_toimituspp, $editilaus_toimitusvv)) {
					$toimpvm = "$editilaus_toimitusvv-$editilaus_toimituskk-$editilaus_toimituspp";
				}
			}

			break;

		case 'OBGMINUMB' :
		case 'OSTOTIL.OT_NRO' :
		//Asiakkkaan tilausnumero
			$asiakkaan_tilausnumero = $tieto;
			$viite    				= $tieto. ' '; // laitetaan asiakkaan tilausnumero talteen myˆs tilausviite-kentt‰‰n
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
			$edi_kommentti .= $tieto . ' ';
			break;

		case 'ORFFABONU' :
		case 'OSTOTIL.OT_VIITTEEMME' :
		//Asiakkaan viite
			$viite .= $tieto;
			break;

		case 'ONADCN_PC' :
			//Asiakkaan toim_OVTtunnus
			$tieto = trim(substr($tieto, 0, 17));

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
					$edi_ulos .= "$arow[nimi]\n";
				}
			}
			break;

		case 'ONADBY_PC' :
			//Asiakkaan OVTtunnus
			$tieto = trim(substr($tieto, 0, 17));

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
					$edi_ulos .= "$arow[nimi]\n";
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
					$edi_ulos .= "$arow[nimi]\n";
				}
			}

			if (!is_array($arow)) {
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
					$edi_ulos .= "$arow[nimi]\n";
				}
				else {
					$edi_ulos .= t("Asiakas ei lˆydy tunnuksella")." '$tieto'\n";
				}
			}

			break;

		case 'ONADBY_RF' :
		case 'OSTOTIL.OT_ASIAKASNRO' :
			//Asiakkaan meid‰n asiakasnumero
			if (substr($tieto, 0, 4) == 'IT @') {
				$tieto = substr($tieto, 4, 10) * 1;
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
					$edi_ulos .= "$arow[nimi]\n";
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
					$edi_ulos .= "$arow[nimi]\n";
				}
			}

			if (!is_array($arow)) {
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
					$edi_ulos .= "$arow[nimi]\n";
				}
				else {
					$edi_ulos .= t("Asiakas ei lˆydy tunnuksella")." '$tieto'\n";
				}

			}

			break;
		case 'OSTOTIL.OT_YRITYS':
			$lnimitark = $tieto;
			break;
		case 'OSTOTIL.OT_KATUOSOITE':
			$losoite = $tieto;
			break;
		case 'OSTOTIL.OT_POSTITOIMIPAIKKA':
			$lpostitp = $tieto;
			break;
		case 'OSTOTIL.OT_POSTINRO':
			$lpostino = $tieto;
			break;
		case 'OSTOTIL.OT_YHTEYSHENKILO':
			$lnimi = $tieto;
			break;
		case 'ONADPL_PC' :
			// Asiakkaan joku toimitus ovttunnus tms... t‰‰ ylikirjottaa kaiken...
			if ($tieto != "") {

				$type = split("@", $tieto);
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

		// t‰‰ on lopullisen asiaakkaan tiedot
		case 'ONADCN_PC' :
		// lopullisen asiakkaan ovttunnus
			$type = split("@", $tieto);
			$lopnumero = trim($type[0]);
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

		// t‰ss‰ alkaa toimitusasiakkaan tiedot
		case 'ONADDP_PC' :
		// toimitusasiakkaan ovttunnus
			$type = split("@", $tieto);
			$tnumero = trim($type[0]);
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
		case 'OSTOTIL.OT_MAKSETTU' :
			// jos Magenton tilaus on jo maksettu luottokortilla niin p‰ivitet‰‰n maksuehto
			if ($edi_tyyppi == "magento" and $tieto == 'processing') {
				$kukarow["kassamyyja"] = $editilaus_kassalipas = "";

				$query = "	SELECT tunnus
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]'
							AND kateinen = 'o'
							LIMIT 1";
				$editilaus_kateinen_res = mysql_query($query) or pupe_error($query);
				$editilaus_kateinen_row = mysql_fetch_array($editilaus_kateinen_res);

				if (mysql_num_rows($editilaus_kateinen_res) == 1) {
					$editilaus_maksuehto = $editilaus_kateinen_row["tunnus"];

					$query = "	SELECT tunnus
								FROM kassalipas
								WHERE yhtio = '$kukarow[yhtio]'
								AND nimi = 'Verkkokauppa'";
					$kassalipas_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($kassalipas_res) == 1) {
						$kassalipas_row = mysql_fetch_array($kassalipas_res);
						$kukarow["kassamyyja"] = $editilaus_kassalipas = $kassalipas_row["tunnus"];
					}
					else {
						$editilaus_maksuehto = "";
						$edi_ulos .= t("Verkkokaupan kassalipasta ei lˆytynyt")."!\n";
						$edi_ulos .= t("Tilaus on maksettu, mutta j‰i avoimeksi")."!\n";
					}
				}
				else {
					$editilaus_maksuehto = "";
					$edi_ulos .= t("Luottokorttimaksuehtoa ei lˆytynyt")."!\n";
					$edi_ulos .= t("Tilaus on maksettu, mutta j‰i avoimeksi")."!\n";
				}
			}
			break;
		//Tilaus p‰‰ttyy. Aika tehd‰ se kantaan
		case 'OHDR__END' :
		case '*RE OSTOTIL' :

			// lis‰t‰‰n otsikko vain jos asiakas lˆytyi
			if (!is_array($arow)) {
				$edi_ulos .= t("Tilaukselta ei lˆytynyt asiakastietoja")."!\n";
				$editilaus_otsikko_ok = "EI";
			}
			else {
				$editilaus_otsikko_ok = "KYLLA";

				// haetaan tomitustavan oletusmaksajan tiedot
				$apuqu = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$arow[toimitustapa]'";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$maksaja = $apuro['merahti'];

				// Verkkokauppatilauksissa luotamme tiedoston laskutustietoihin
				if ($edi_tyyppi == "magento") {
					$arow["nimi"] = $lnimi;
					$arow["nimitark"] = $lnimitark;
					$arow["osoite"] = $losoite;
					$arow["postitp"] = $lpostitp;
					$arow["postino"] = $lpostino;
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

				// jos tilauksessa on tullut poikkeava nimi, postino ja postitp otetaan kaikki tiedot tilaukselta
				if ($tnimi != "" and $tpostino != "" and $tpostitp != "") {
					$arow['toim_nimi']		= $tnimi;
					$arow['toim_nimitark']	= $tnimitark;
					$arow['toim_osoite']	= $tosoite;
					$arow['toim_postino']	= $tpostino;
					$arow['toim_postitp']	= $tpostitp;
				}

				// jos tilauksella ei ole maksuehtoa, k‰ytet‰‰n asiakkaan oletusta
				if ($editilaus_maksuehto == '') {
					$editilaus_maksuehto = $arow["maksuehto"];
				}

				// jos ei olla pakotettu rahtivapaata, niin otetaan asiakkaan oletus
				if ($editilaus_rahtivapaa == '') {
					$editilaus_rahtivapaa = $arow["rahtivapaa"];
				}

				// jos tilauksella on tullut lopullisen asikkaan tiedot, laitetaan ne talteen sisviesti ykkˆseen
				if ($lopnimi != "") {
					$arow["sisviesti1"] = "$lopnumero\n$lopnimi\n$loposoite\n$loppostino\n$loppostitp";

					// jos kyseess‰ on terminaalitoimitus
					if ($editilaus_tila == "Z") {
						$lopviesti = "$lopnumero#$lopnimi"; // laitetaan viel‰ numero ja nimi omaan paikkaan..
					}
				}

				// jos tilauksella oli toivottu, ett‰ laskua *EI SAA* ketjuttaa ni tehd‰‰n niin
				if ($ketjutus != "") {
					$arow["ketjutus"] = $ketjutus;
				}

				// Haetaan viel‰ myyyj‰numero
				$query = "	SELECT tunnus
							FROM kuka
							WHERE yhtio = '$kukarow[yhtio]'
							and kuka 	= 'EDI'";
				$meapu = mysql_query($query) or pupe_error($query);
				$apuro = mysql_fetch_array($meapu);
				$edimyyja = $apuro['tunnus'];


				// Lis‰t‰‰n otsikko
				$query = "	INSERT into lasku SET
							yhtio_nimi							= '$yhtiorow[nimi]',
							yhtio_osoite						= '$yhtiorow[osoite]',
							yhtio_postino						= '$yhtiorow[postino]',
							yhtio_postitp						= '$yhtiorow[postitp]',
							yhtio_maa							= '$yhtiorow[maa]',
							yhtio_ovttunnus						= '$yhtiorow[ovttunnus]',
							yhtio_kotipaikka					= '$yhtiorow[kotipaikka]',
							alatila 		   					= '$editilaus_alatila',
							alv 			   					= '$arow[alv]',
							chn				   					= '$arow[chn]',
							comments 		   					= '$edi_kommentti',
							kerayspvm 		   					= '$toimpvm',
							ketjutus		   					= '$arow[ketjutus]',
							laatija 		   					= '$edi_laatija',
							laskutusvkopv	   					= '$arow[laskutusvkopv]',
							luontiaika		   					=  now(),
							maa 			   					= '$arow[maa]',
							maksuehto 		   					= '$editilaus_maksuehto',
							myyja 			   					= '$edimyyja',
							nimi 			   					= '$arow[nimi]',
							nimitark 		   					= '$arow[nimitark]',
							osoite 			   					= '$arow[osoite]',
							ovttunnus 		   					= '$arow[ovttunnus]',
							postino 		   					= '$arow[postino]',
							postitp 		   					= '$arow[postitp]',
							tila 			   					= '$editilaus_tila',
							tilausvahvistus    					= '$vastaustapa',
							erikoisale		   					= '$arow[erikoisale]',
							toim_maa		   					= '$arow[toim_maa]',
							toim_nimi		   					= '$arow[toim_nimi]',
							toim_nimitark	   					= '$arow[toim_nimitark]',
							toim_osoite		   					= '$arow[toim_osoite]',
							toim_ovttunnus 	   					= '$arow[toim_ovttunnus]',
							toim_postino	   					= '$arow[toim_postino]',
							toim_postitp	   					= '$arow[toim_postitp]',
							toimaika 		   					= '$toimpvm',
							kohdistettu 	   					= '$maksaja',
							toimitustapa 	   					= '$arow[toimitustapa]',
							toimitusehto 	   					= '$arow[toimitusehto]',
							verkkotunnus	   					= '$arow[verkkotunnus]',
							valkoodi		   					= '$yhtiorow[valkoodi]',
							liitostunnus       					= '$arow[tunnus]',
							vienti			   					= '$arow[vienti]',
							viesti 			   					= '$viite',
							sisviesti1         					= '$arow[sisviesti1]',
							sisviesti2         					= '$lopviesti',
							yhtio 			   					= '$kukarow[yhtio]',
							ytunnus			   					= '$arow[ytunnus]',
							asiakkaan_tilausnumero		   		= '$asiakkaan_tilausnumero',
							aktiivinen_kuljetus					= '$suunnistus',
							kuljetusmuoto						= '$arow[kuljetusmuoto]',
							kauppatapahtuman_luonne				= '$arow[kauppatapahtuman_luonne]',
							maa_maara							= '$arow[maa_maara]',
							piiri								= '$arow[piiri]',
							rahtivapaa							= '$editilaus_rahtivapaa',
							kassalipas							= '$editilaus_kassalipas'";
				$result = mysql_query($query) or die($query);
				$id = mysql_insert_id();
				$edi_ulos .= t("Tilausnumero")." $id\n\n";

				// haetaan tilauksen tiedot
				$query    = "select * from lasku where tunnus='$id' and yhtio='$kukarow[yhtio]'";
				$result   = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_array($result);
			}

			break;

		case 'OLINSA_NU' :
		case 'OSTOTILRIV.OTR_TUOTEKOODI' :
			//Tuotenumero

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsi‰ tuote tuotenumerolla
			if ($tuoteno == "" and $tieto != "") {

				$kieltolisa = "";
				//vientikieltotarkistus
				if (isset($laskurow) and $laskurow["liitostunnus"] != 0) {
					$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
								FROM asiakas
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus  = '$laskurow[liitostunnus]'";
					$vieres = mysql_query($query) or pupe_error($query);
					$vierow = mysql_fetch_array($vieres);

					if (isset($vierow) and $vierow["maa"] != "") {
						$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
					}
				}

				if ($edi_tyyppi != "magento" and $edi_tyyppi != "futursoft" and $yhtiorow["yhtio"] == "artr") {
					// poistetaan jos kolmas merkki jos se on viiva
					if (substr($tieto, 2, 1) == '-') {
						$tieto = substr($tieto, 0, 2) . substr($tieto, 3, 15);
					}
				}

				// Poistetaan spacet
				if (in_array($yhtiorow["yhtio"], array("artr","allr","mast"))) {
					$tieto = str_replace(" ", "", $tieto);
				}

				$query = "SELECT * FROM tuote WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tieto' $kieltolisa";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Tuote")." '$tieto' ".t("ei lˆydy")."!\n";
					$tuoteerrortxt .= " $tieto";
					$tuoteerror = 1;
				}
				else {
					$trow = mysql_fetch_array($result);
					$edi_ulos .= t("Tuote")." $tieto ";
					$tuoteno = $trow['tuoteno'];
					$tuoteerror = 0; // tuote ok
				}
			}
			break;

		case 'OLINSA_PI' :
			// t‰m‰n rivin asiakkaan oma tuotenumero
			$type = split("@", $tieto);
			$olinsapi    = trim($type[0]);
			$olinvarasto = trim($type[2]); // kolmannessa segmentiss‰ tulee meid‰n varastopaikka jos l‰hett‰j‰ tiet‰‰ sen
			break;

		case 'OLINEN_NU' :
			// tuotteen EAN-koodi

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsi‰ tuote EAN koodilla
			if ($tuoteno == "" and $tieto != "") {

				$kieltolisa = "";
				//vientikieltotarkistus
				if (isset($laskurow) and $laskurow["liitostunnus"] != 0) {
					$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
								FROM asiakas
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus  = '$laskurow[liitostunnus]'";
					$vieres = mysql_query($query) or pupe_error($query);
					$vierow = mysql_fetch_array($vieres);

					if (isset($vierow) and $vierow["maa"] != "") {
						$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
					}
				}

				$query = "	SELECT *
							FROM tuote USE INDEX (yhtio_eankoodi_index)
							WHERE yhtio = '$kukarow[yhtio]'
							and eankoodi = '$tieto'
							$kieltolisa
							ORDER BY tunnus";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Tuote")." '$tieto' ".t("ei lˆydy")."!\n";
					$tuoteerrortxt .= " $tieto";
					$tuoteerror = 1;
				}
				else {
					$trow = mysql_fetch_array($result);
					$edi_ulos .= t("Tuote")." $tieto ";
					$tuoteno = $trow['tuoteno'];
					$tuoteerror = 0; // tuote ok
				}
			}

			break;

		case 'OLIN8__IF' :
			// asiakkaan antama nimitys tuotteelle
			$type = split("@", $tieto);
			$edi_nimitys = $type[4]; // rivin nelj‰nness‰ segmentiss‰ tulee oikee info
//			$edi_sarjanumero = $type[5]; // rivin viidenness‰ segmentiss‰ tulee tuotteen sarjanumeron tunnus jos asiakas sen tiesi???
			break;

		case 'OLINCONIV' :
			// asiakkaan antama hinta tuotteelle
			$type = split(" ", $tieto);
//			$edi_hinta = abs($type[0]);
			break;

		case 'OLIN21_QT' :
		case 'OSTOTILRIV.OTR_TILATTUMAARA' :
			//Tilattava m‰‰r‰
			$tieto = str_replace ( ",", ".", $tieto);
			
			if ($edi_tyyppi == "magento" and (strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero']) or strtolower($tuoteno) == strtolower($yhtiorow['alennus_tuotenumero'])) and $tieto != '') {
				$kpl = (float) substr($tieto,0,16);
			}
			else {
				$kpl = (float) abs(substr($tieto,0,16)); // abs() ettei asiakkaat pysty tekem‰‰n hyvityksi‰!
			}
			
			$edi_ulos .= t("tilattiin")." $kpl ".t("kpl").".\n";
			break;
		case 'OSTOTILRIV.OTR_RIVISUMMA' :
			// jos tullaan magentosta ja meill‰ on rahtikulu, niin otetaan se hinta talteeen
			if ($edi_tyyppi == "magento" and (strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero']) or strtolower($tuoteno) == strtolower($yhtiorow['alennus_tuotenumero'])) and $tieto != '') {
				$editilaus_rahtikulu_hinta = $tieto;
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
		case 'OLIN__END' :
		case '*RE  OSTOTILRIV' :

			if ($editilaus_otsikko_ok == "KYLLA") {

		 		// jos tuote on lˆytynyt ok
				if ($tuoteerror == 0) {

					// halutaan $tuoteno
					// $kpl kappaletta
					// $trow jossa on tuotten tiedot
					$ytunnus    		= $arow["ytunnus"];
					$hinta 				= "";
					$netto 				= "";
					$ale 				= "";
					$var				= "";
					$alv				= "";
					$toimaika 			= $laskurow["toimaika"];
					$kerayspvm			= $laskurow["kerayspvm"];
					$paikka				= "";
					$varasto 			= $olinvarasto;
					$kukarow["kesken"] 	= $laskurow["tunnus"];
					$jtkielto 			= $laskurow['jtkielto'];

					if ($ketjutus == 'o') {
						// kiellet‰‰n kaikki muutokset alkuper‰iseen tilausriviin, jos ketjutus = 'o' eli pupesoft --> pupesoft
						$korvaavakielto = 'KIELLETTY';
						$perhekielto    = 'KIELLETTY';
					}

					// jos terminaalitoimitus niin ohitetaan kaikki saldozekit, sek‰ laitetaan kommenttiriville asiakkaan tuotenumero ja nimitys
					if ($editilaus_tila == "Z") {
						$var = "Y";
						$kommentti = "$olinsapi#$edi_nimitys";
					}

					// jos meill‰ on sarjanumero ja hinta tiedossa niin uskalletaan luottaa asiakkaan antamaan hintaan
					if ($edi_sarjanumero != "" and $edi_hinta != "") {
						$hinta = $edi_hinta;
					}

					// jos tullaan magentosta ja hinta on tiedossa ja kyseess‰ on rahtikulu, niin uskalletaan luottaa magenton antamaan hintaan
					if ($edi_tyyppi == "magento" and (strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero']) or strtolower($tuoteno) == strtolower($yhtiorow['alennus_tuotenumero'])) and $editilaus_rahtikulu_hinta != '') {
						$hinta = $editilaus_rahtikulu_hinta;
					}

					// Magento-tilauksessa katsotaan onko tilattavalla tuotteella saldoa.
					// Jos tilattu kpl-m‰‰r‰ on suurempi kuin myyt‰viss‰ oleva kpl-m‰‰r‰, niin pakotetaan rivi hyv‰ksytyksi
					if ($edi_tyyppi == "magento" and $tuoteno != '' and $kpl > 0 and $trow["ei_saldoa"] == "") {
						list($chk_saldo, $chk_hyllyssa, $chk_myytavissa, $chk_true) = saldo_myytavissa($tuoteno);

						$laskurow["varasto"] = ""; // spooffaillaan laskurow varastoa verkkokaupan takia

						// jos saldo ei riit‰ normivarastoista, pit‰‰ katsoa miss‰ varastossa riitt‰‰ ja myyd‰‰n sielt‰ (koska jossain riitt‰‰, muuten tilaus ei olisi edes tullu t‰nne asti)
						if ($chk_myytavissa < $kpl) {
							$query = "	SELECT distinct varastopaikat.tunnus varasto, varastopaikat.prioriteetti
										FROM tuotepaikat
										JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
											AND varastopaikat.tyyppi != ''
											AND varastopaikat.prioriteetti > 0
											AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
											AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0')))
										WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
										AND tuotepaikat.tuoteno = '$tuoteno'
										ORDER BY prioriteetti";
							$sres = mysql_query($query) or pupe_error($query);

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
								// Sortataan 2 dimensoinen array. pit‰‰ ensiksi tehd‰ sortattavista keyst‰ omat arrayt
								$apusort_jarj0 = $apusort_jarj1 = array();
								foreach ($erikoisvarastojen_saldot as $apusort_key => $apusort_row) {
								    $apusort_jarj0[$apusort_key] = $apusort_row['prioriteetti'];
									$apusort_jarj1[$apusort_key] = $apusort_row['myytavissa'];
								}

								// sortataan by myytavissa, sorttauskentt‰
								array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_DESC, $erikoisvarastojen_saldot);

								$varasto = $erikoisvarastojen_saldot[0]["varasto"]; // ekassa varastossa on pienin prioriteetti
								$laskurow["varasto"] = $varasto; // joudutaan spooffaamaan laskurow varasto
							}
							else {
								$var = "H";
							}

							$kommentti = t("Huom: Tuote toimitetaan erikoisvarastosta!");
						}
					}

					$kutsuja = "EDITILAUS";

					//jos meill‰ on t‰pp‰ p‰‰ll‰, ett‰ asiakas haluaa kamat j‰lkitoimitukseen
					if ($sallitaanjt == '1') {
						$var = "J";
						$kommentti = ""; // Huom! nollataan kommentti
					}

					require ("lisaarivi.inc");

					// jos meill‰ on tiedossa tuotteen sarjanumero, lis‰t‰‰n se
					if ($edi_sarjanumero != "") {
						// etsit‰‰n sarjanumero
						$sarjaquery = "select * from sarjanumeroseuranta where tunnus='$edi_sarjanumero'";
						$sarjaresult = mysql_query($sarjaquery) or pupe_error($sarjaquery);

						if (mysql_num_rows($sarjaresult) == 1) {

							$sarjarow = mysql_fetch_array($sarjaresult);

							// liitet‰‰n t‰m‰ myyntirivi siihen kiinni (lis‰‰rivi palauttaa $lisatty_tun)
							$query = "	INSERT INTO sarjanumeroseuranta SET
										yhtio				= '$kukarow[yhtio]',
										tuoteno				= '$sarjarow[tuoteno]',
										lisatieto			= 'Suoratoimitustilaus $arow[nimi] --> $tnimi',
										sarjanumero			= '$sarjarow[sarjanumero]',
										lisatieto			= '$sarjarow[lisatieto]',
										kaytetty			= '$sarjarow[kaytetty]',
										myyntirivitunnus 	= '$lisatty_tun'";
							$sres = mysql_query($query) or pupe_error($query);
						}
					}

					$edi_ulos .= "\n";

				} // end jos tuote on lˆydetty oikein
				else {

					// tuotenumero ei lˆytynyt, tehd‰‰n dummyrivi..

					$kommentti = t("Yritettiin tilata tuntematonta tuotetta").$tuoteerrortxt;

					$query = "	INSERT into tilausrivi set
								tilaajanrivinro = '$rivinumero',
								laatija 	= 'edi',
								laadittu 	= now(),
								yhtio 		= '$kukarow[yhtio]',
								tuoteno 	= 'TUNTEMATON',
								var			= 'P',
								kpl 		= '0',
								alv			= '22',
								tilkpl 		= '$kpl',
								otunnus 	= '$id',
								tyyppi 		= '$editilaus_tyyppi',
								kommentti 	= '$kommentti'";
					$result = mysql_query($query) or die($query);
				}
			}

			// nollataan kaikki rivin muuttujat (n‰m‰ samat pit‰‰ tehd‰ filen alussa)
			$tuoteerror      = 1; // oletetaan aina ettei  tuotetta lˆydy
			$tuoteerrortxt   = "";
			$kpl             = "";
			$rivinumero      = "";
			$kommentti       = "";
			$tuoteno         = "";
			$olinsapi        = "";
			$olinvarasto     = "";
			$edi_nimitys     = "";
			$edi_sarjanumero = "";
			$edi_hinta       = "";
			$korvaavakielto  = "";
			$perhekielto     = "";
			$sallitaanjt	 = "";
			break;

		case 'OMSG__END' :
		case '*IE' :

			// jos meilla on otsikko voidaan laittaa sen valmiiksi
			if ($editilaus_otsikko_ok == "KYLLA") {
				//tarkistetaan toimitustapa vakin takia
				$query = " 	SELECT *
				  			from tilausrivi
							join tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi != ''
							where tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$id'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) > 0) {
					$query = "	SELECT vak_kielto
								from toimitustapa
								where yhtio = '$kukarow[yhtio]'
								and selite = '$laskurow[toimitustapa]'";
					$result = mysql_query($query) or die($query);
					$toimtapatmp = mysql_fetch_array($result);

					if ($toimtapatmp['vak_kielto'] != '' and $toimtapatmp['vak_kielto'] != 'K') {
						$query = "	SELECT selite
									FROM toimitustapa
									WHERE yhtio = '$kukarow[yhtio]'
									AND selite = '$toimtapatmp[vak_kielto]'
									AND vak_kielto = ''";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {
							$query = "UPDATE lasku SET toimitustapa = '$toimtapatmp[vak_kielto]' where yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
							$result = mysql_query($query) or die($query);
						}
					}
				}


				// Tilaus merkataan valmiiksi
				$query = "UPDATE lasku SET alatila='A' WHERE tunnus='$id' AND yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or die($query);

				// haetaan tilauksen tiedot
				$query    = "SELECT * FROM lasku WHERE tunnus='$id' AND yhtio='$kukarow[yhtio]'";
				$result   = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_array($result);

				// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
				$kukarow["kesken"] = $laskurow["tunnus"];
				$editilaus_lasku_tunnus = $laskurow["tunnus"];

				// jos terminaalitoimitus niin EI tehd‰ tilaus-valmista...
				if ($editilaus_tila != "Z") {

					if ($yhtiorow['jt_automatiikka'] == 'Y' or $yhtiorow['jt_automatiikka'] == 'W') {
						//lis‰t‰‰n tilaukseen ne jt-rivit jotka riitt‰v‰t kaikille
						$edi_ulos .= "\n";
						jt_toimita_editilaus($laskurow["ytunnus"], $laskurow["liitostunnus"], $laskurow["maa"]);
						$edi_ulos .= "\n";
					}

					// katsotaan ollaanko tehty JT-supereita..
					$silent = "SILENT";
					require("jt_super.inc");

					$edi_ulos .= "\n";
					$edi_ulos .= jt_super($laskurow["tunnus"]);
					$edi_ulos .= "\n";
					$edi_ulos .= "\n";

					$silent = "SILENT";
					require ("tilaus-valmis.inc");

					// jos magentosta tullut tilaus on jo maksettu, merkataan se maksetuksi
					if ($edi_tyyppi == "magento" and $editilaus_kassalipas != "") {
						// Haetaan yrityksen ensimm‰inen normivarasto. Tallennetaan tilaus t‰h‰n varastoon
						$varasto_query = "	SELECT tunnus
											from varastopaikat
											where yhtio='$kukarow[yhtio]'
											and tyyppi=''
											order by alkuhyllyalue
											limit 1";
						$varasto_res = mysql_query($varasto_query) or pupe_error($varasto_query);
						$varasto_row = mysql_fetch_array($varasto_res);

						$query = "	SELECT hyllyalue, hyllynro
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									AND hyllyalue != ''
									AND hyllynro != ''
									AND otunnus = '$editilaus_lasku_tunnus'";
						$edi_varastopaikka_res = mysql_query($query) or pupe_error($query);
						$edi_varastopaikka_row = mysql_fetch_array($edi_varastopaikka_res);

						$tulostusalue = onkotulostusalueita($edi_varastopaikka_row["hyllyalue"], $edi_varastopaikka_row["hyllynro"], $varasto_row['tunnus'], $kukarow['yhtio']);

						// Palautetaan tilaus takaisin "myyntitilaus ker‰ysjonossa"-tilaan, lis‰t‰‰n p‰‰lle laskutuskielto varmuuden vuoksi
						$query = "	UPDATE lasku SET
									mapvm = now(),
									tila = 'N',
									alatila = 'A',
									chn = '999',
									varasto = '$varasto_row[tunnus]',
									sisviesti2 = 'Verkkokauppatilaus! Tilauksen tietoja ei saa muuttaa.',
									tulostusalue = '$tulostusalue'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$editilaus_lasku_tunnus'";
						$magento_maksettu_res = mysql_query($query) or pupe_error($query);

						// haetaan tilauksen tiedot
						$query    = "SELECT * FROM lasku WHERE tunnus = '$editilaus_lasku_tunnus' AND yhtio = '$kukarow[yhtio]'";
						$result   = mysql_query($query) or die($query);
						$laskurow = mysql_fetch_array($result);

						// pit‰‰ nollata toimitettuaika riveille
						$query = "	UPDATE tilausrivi SET
									toimitettu = '',
									toimitettuaika = ''
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = '$editilaus_lasku_tunnus'";
						$result = mysql_query($query) or die($query);

						// pit‰‰ nollata kerattyaika kaikille saldollisille tuotteille
						$query = "	UPDATE tilausrivi SET
									keratty = '',
									kerattyaika = ''
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = '$editilaus_lasku_tunnus'
									AND keratty != 'saldoton'";
						$result = mysql_query($query) or die($query);

						// ei haluta k‰sitell‰ t‰t‰ toista kertaa kassamyyntin‰, koska se est‰‰ splittauksen
						$kukarow["kassamyyja"] = $editilaus_kassalipas = "";

						// kutsutaan uudestaan tilaukselle tilaus-valmis, niin saadaan splitattua se varastoihin
						$silent = "SILENT";
						require ("tilaus-valmis.inc");
					}

					// Halutaanko tilaus hyv‰ksynn‰n kautta!
					if ($tilaus_hyvaksyntaan != "") {
						$query = "	UPDATE lasku SET
									alatila = 'F'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$editilaus_lasku_tunnus'";
						$magento_maksettu_res = mysql_query($query) or pupe_error($query);
					}

					$edi_ulos .= "$tilaus_valmis_ulos\n";
					$edi_ulos .= "$tilaus_valmis_message\n";
					$edi_ulos .= "\n".t("Merkittiin tilaus valmiiksi").": $editilaus_lasku_tunnus.\n\n";
				}
				else {
					$edi_ulos .= "\n".t("Terminaalitoimitus vastaanotettu").": $editilaus_lasku_tunnus.\n\n";
				}

			}
			else {
				$edi_ulos .= "\n".t("Otsikko oli virheellinen tilausta/rivej‰ ei lis‰tty")."!\n\n";
			}

			// nollataan kaikki laskun muuttujat (kaikki n‰m‰ samat pit‰‰ nollata filen alussa)
			unset($arow);
			$toimpvm				= date("Y-m-d");
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
			$edi_nimitys          	= "";
			$olinsapi             	= "";
			$olinvarasto          	= "";
			$lopviesti            	= "";
			$edi_sarjanumero      	= "";
			$edi_hinta            	= "";
			$ketjutus             	= "";
			$editilaus_maksuehto	= "";
			$tilaus_hyvaksyntaan	= "";
			$editilaus_rahtivapaa	= "";
			$editilaus_kassalipas	= "";
			$editilaus_rahtikulu_hinta = "";

			break;
	}
}

// l‰hetet‰‰n meili
if ($yhtiorow["alert_email"] != "") {

	$edi_ulos = strip_tags($edi_ulos);

	$bound   = uniqid(time()."_") ;
	$kutsu   = $yhtiorow["yhtio"]."-ediorder.txt";

	$header  = "From: <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	$content  = "--$bound\n";
	$content .= "Content-Type: text/plain;charset=iso-8859-1\n" ;
	$content .= "Content-Transfer-Encoding: quoted-printable\n";
	$content .= $edi_ulos.t("K‰sitelty tiedosto liitteen‰").".\n\n";

	$content .= "--$bound\n";
	$content .= "Content-Type: text/plain; name=\"".$kutsu."\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: attachment; filename=\"".$kutsu."\"\n\n";

	$handle  = fopen($mailfile, "r");
	$sisalto = fread($handle, filesize($mailfile));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\n";

	$boob = mail($yhtiorow["alert_email"],  "$edi_subj - $yhtiorow[nimi]", $content, $header, "-f $yhtiorow[postittaja_email]");

}

?>
