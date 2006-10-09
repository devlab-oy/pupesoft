<?php

$debug = 0;
$edi_ulos = "\n";

// jos meillä ei ole sessiota ja meillä on komentoriviparametri annettuna
if (trim($argv[1])!='') {

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// ja parametri komentoriviltä
	$filename=$argv[1];
	// ja toinen parametri komentoriviltä
	$edi_tyyppi=$argv[2];

	$edimyyja = 195; // en nyt jaksa tehdä tätä hienommin.. kenelle myyjälle annetaan editilauksen myynnit (kukarow tunnus)

	$query   = "select * from kuka where tunnus='$edimyyja'";
	$result  = mysql_query($query) or die($query);
	$kukarow = mysql_fetch_array($result);
}

$edimyyja = $kukarow['tunnus'];

// nollataan kaikki laskun muuttujat (nämä samat pitää tehdä whilen lopussa)
unset($arow);
$editilaus_tila       = "N"; // laskun tila
$editilaus_tyyppi     = "L"; // tilausrivi tyyppi
$id                   = "";
$edi_kommentti        = "";
$vastaustapa          = "";
$maksaja              = "";
$viite                = "";
$viitetxt             = "";
$editilaus_otsikko_ok = "";
$suunnistus           = "";
$tnimi                = "";
$tpostitp             = "";
$tpostino             = "";
$tosoite              = "";
$lopnumero            = "";
$lopnimi              = "";
$loppostitp           = "";
$loppostino           = "";
$loposoite            = "";
$lopviesti            = "";

// nollataan kaikki rivin muuttujat (nämä samat pitää tehdä tuote insertin jälkeen)
$tuoteerror    = 1; // oletetaan aina ettei  tuotetta löydy
$tuoteerrortxt = "";
$kpl           = "";
$rivinumero    = "";
$kommentti     = "";
$tuoteno       = "";
$olinsapi      = "";
$olinvarasto   = "";
$edi_nimitys   = "";

// fiilataan parametrejä joita tarvitaan require fileissä
$tulos_ulos = "edi"; // tää on ftp-send.incissä

// tarvitaan $filename
$filename = trim($filename);
if ($filename == "") die('Tiedostonimi puuttui!\n');

if (!$fd = fopen($filename, "r")) die("Filen '$filename' ".t("avaus epäonnistui")."!\n");

$toimpvm = date(Y)."-".date(n)."-".date(j);
$vastaustapa = "";

if ($edi_tyyppi == "futursoft") {
	$edi_subj = "Sisääntuleva Futursoft-tilaus";
	$edi_laatija = "FuturSoft";
}
else {
	$edi_subj = t("Sisääntuleva EDI-tilaus");
	$edi_laatija = "EDI";
}

$edi_ulos .= "$edi_subj\n";

while (!feof ($fd)) {

	$tietue = fgets($fd, 4096);

	// tää on futurikeisi
	if ($edi_tyyppi == "futursoft") {

		if (substr($tietue,0,1) != '*') {
			list($tunnus,$tieto)  = explode(':',$tietue);
		}
		else {
			$tunnus=$tietue;
		}

		$tunnus = trim($tunnus);
		$tieto  = trim($tieto);

		if (substr($tunnus,0,15) == '*RE  OSTOTILRIV') {
			$tunnus='*RE  OSTOTILRIV'; // Poistetaan häiritsevä nro
		}

	}
	else {
		$tunnus = substr($tietue,0,9);
		$tieto  = trim(substr($tietue,10));
	}

	// tehdään kaikille tiedoille 7 -> 8 bit ääkköskonversio..
	$from   = array ('{','}','|','[',']','\\',chr(179)); // 7bit
	$to     = array ('ä','å','ö','Ä','Å','Ö','|'); // 8bit
	$tieto  = str_replace($from, $to, $tieto);

	switch ($tunnus) {
		case 'ICHG_RCPT':
		case 'OSTOTIL.OT_TOIMITTAJANRO':
		//Tilauksen vastaanottaja
			$edi_ulos .= t("Tilaus yritykselle")." '" . $tieto .  "' ";
			$tieto = substr($tieto, 4, 9) * 1;

			// Arwidson häkki Arwidson Baltia varten
			if ($tieto == 0) $tieto = 1066781;
			// Arwidson häkki Arwidson Automotivea varten
			if ($tieto == 1066781) $tieto = 7495288;

			$query = "SELECT * FROM yhtio WHERE ytunnus='$tieto'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) == 0) {
				$edi_ulos .= "\n".t("Vastaanottavaa yritystä ei löydy tunnuksella")." '$tieto'\n";
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
				// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

			$kukarow['yhtio'] = $yhtiorow['yhtio']; // Tämä on vaarallista, mutta tätä ilman ei mikän toimi!
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

			// jos TRM ni ei kai mitää spessua
			if (trim($type[1]) == "TRM") {

			}

			// Pupesoft tilaustyyppi
			if ($tieto == '1') {
				$edi_ulos .= t("Laskua ei saa ketjuttaa")."!\n";
				// tyyppi yks on pupesoft <-> pupesoft suoratoimitustilaus, silloin ei ketjuteta laskua ikinä
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

		case 'OBGMINUMB' :
		case 'OSTOTIL.OT_NRO' :
		//Asiakkkaan tilausnumero
			$viitetxt = $tieto;
			$viite    = $tieto. ' '; // laitetaan asiakkaan tilausnumero talteen myös tilausviite-kenttään
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

		case 'ONADBY_PC' :
		//Asiakkaan OVTtunnus
			$tieto = trim(substr($tieto, 0, 17));

			if (!is_array($arow)) {
				$edi_ulos .= t("Tilaava asiakas")." $tieto -> ";

				$query = "SELECT * FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and toim_ovttunnus='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi]\n";
				}
			}

			if (!is_array($arow)) {
				$query = "SELECT * FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ovttunnus='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					$edi_ulos .= t("Asiakas ei löydy tunnuksella")." '$tieto'\n";
				}
				else {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi]\n";
				}
			}

			break;

		case 'ONADBY_RF' :
		case 'OSTOTIL.OT_ASIAKASNRO' :
			//Asiakkaan meidän asiakasnumero
			if(!is_array($arow)) {

				if (substr($tieto, 0, 4) == 'IT @') {
					$tieto = substr($tieto, 4, 10) * 1;
				}

				$edi_ulos .= t("Tilaava asiakas")." $tieto -> ";

				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and asiakasnro='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					$edi_ulos .= t("Asiakas ei löydy tunnuksella")." '$tieto'\n";
				}
				else {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[nimi]\n";
				}
			}
			break;

		case 'ONADPL_PC' :
			// Asiakkaan joku toimitus ovttunnus tms... tää ylikirjottaa kaiken...
			if ($tieto != "") {

				$type = split("@", $tieto);
				$tieto = trim($type[0]);

				$edi_ulos .= t("Tilaava toim_asiakas")." $tieto -> ";

				$query = "	SELECT * FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and toim_ovttunnus='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 1) {
					$arow = mysql_fetch_array($result);
					$vastaustapa .= $arow['tilausvahvistus'];
					$edi_ulos .= "$arow[toim_nimi]\n";
				}

			}

			break;

		// tää on lopullisen asiaakkaan tiedot
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

		// tässä alkaa toimitusasiakkaan tiedot
		case 'ONADDP_PC' :
		// toimitusasiakkaan ovttunnus
			$type = split("@", $tieto);
			$tnumero = trim($type[0]);
			break;

		case 'ONADDP_NA' :
		case 'OSTOTIL.OT_TOIMITUS_YRITYS' :
		// toimitusasiakkaan nimi
			$tnimi = $tieto;
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

		//Tilaus päättyy. Aika tehdä se kantaan
		case 'OHDR__END' :
		case '*RE OSTOTIL' :

			// lisätään otsikko vain jos asiakas löytyi
			if (!is_array($arow)) {
				$edi_ulos .= t("Tilaukselta ei löytynyt asiakastietoja")."!\n";
				$editilaus_otsikko_ok = "EI";
			}
			else {
				$editilaus_otsikko_ok = "KYLLA";

				// haetaan tomitustavan oletusmaksajan tiedot
				$apuqu = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$arow[toimitustapa]'";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$maksaja = $apuro['merahti'];

				// jos asiakkalla ei ole toimitusosoitetietoja, kopsataan laskutustiedot
				if ($arow['toim_nimi'] == "") {
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

				$query = "INSERT into lasku SET
							alatila 		   					= '',
							alv 			   					= '$arow[alv]',
							chn				   					= '$arow[chn]',
							comments 		   					= '$edi_kommentti',
							kerayspvm 		   					= '$toimpvm',
							ketjutus		   					= '$arow[ketjutus]',
							laatija 		   					= '$edi_laatija',
							laskutusvkopv	   					= '$arow[laskutusvkopv]',
							luontiaika		   					=  now(),
							maa 			   					= '$arow[maa]',
							maksuehto 		   					= '$arow[maksuehto]',
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
							viitetxt		   					= '$viitetxt',
							aktiivinen_kuljetus					= '$suunnistus',
							kuljetusmuoto						= '$arow[kuljetusmuoto]',
							kauppatapahtuman_luonne				= '$arow[kauppatapahtuman_luonne]',
							maa_maara							= '$arow[maa_maara]'";
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

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsiä tuote tuotenumerolla
			if ($tuoteno == "" and $tieto != "") {

				//Poistetaan siellä mahdollisesti majaava ylimääräinen viiva
				if (substr($tieto, 2, 1) == '-') {
					$tieto = substr($tieto, 0, 2) . substr($tieto, 3, 15);
				}
				//Poistetaan spacet
				$tieto = str_replace(" ", "", $tieto);

				$query = "SELECT * FROM tuote WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lisätään tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Tuote")." '$tieto' ".t("ei löydy")."!\n";
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
			// tämän rivin asiakkaan oma tuotenumero
			$type = split("@", $tieto);
			$olinsapi    = trim($type[0]);
			$olinvarasto = trim($type[2]); // kolmannessa segmentissä tulee meidän varastopaikka jos lähettäjä tietää sen
			break;

		case 'OLINEN_NU' :
			// tuotteen EAN-koodi

			// jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsiä tuote EAN koodilla
			if ($tuoteno == "" and $tieto != "") {

				$query = "SELECT * FROM tuote USE INDEX (yhtio_eankoodi_index) WHERE yhtio='$kukarow[yhtio]' and eankoodi='$tieto'";
				$result = mysql_query($query) or die($query);

				if (mysql_num_rows($result) == 0) {
					///* Lisätään tilaukselle dummytuote ja kommentti *///
					$edi_ulos .= t("Tuote")." '$tieto' ".t("ei löydy")."!\n";
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
			$edi_nimitys = $type[4]; // rivin neljännessä segmentissä tulee oikee info
			break;

		case 'OLIN21_QT' :
		case 'OSTOTILRIV.OTR_TILATTUMAARA' :
			//Tilattava määrä
			$tieto = str_replace ( ",", ".", $tieto);
			$kpl = (float) abs(substr($tieto,0,16)); // abs() ettei asiakkaat pysty tekemään hyvityksiä!

			$edi_ulos .= t("tilattiin")." $kpl ".t("kpl").". ";
			break;

		case 'OLIN__END' :
		case '*RE  OSTOTILRIV' :

			if ($editilaus_otsikko_ok == "KYLLA") {

		 	// jos tuote on löytynyt ok
				if ($tuoteerror == 0) {

					// halutaan $tuoteno
					// $kpl kappaletta
					// $trow jossa on tuotten tiedot
					$hinta 		= "";
					$netto 		= "";
					$ale 		= "";
					$var		= "";
					$alv		= "";
					$toimaika 	= $laskurow["toimaika"];
					$kerayspvm	= $laskurow["kerayspvm"];
					$paikka		= $olinvarasto;
					$varasto 	= "";
					$kukarow["kesken"] = $laskurow["tunnus"];

					// jos terminaalitoimitus niin ohitetaan kaikki saldozekit, sekä laitetaan kommenttiriville asiakkaan tuotenumero ja nimitys
					if ($editilaus_tila == "Z") {
						$var = "Y";
						$kommentti = "$olinsapi#$edi_nimitys";
					}

					$kutsuja = "EDITILAUS";

					require ("lisaarivi.inc");

					$edi_ulos .= "\n";

				} // end jos tuote on löydetty oikein
				else {

					// tuotenumero ei löytynyt, tehdään dummyrivi..

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

			// nollataan kaikki rivin muuttujat (nämä samat pitää tehdä filen alussa)
			$tuoteerror    = 1; // oletetaan aina ettei  tuotetta löydy
			$tuoteerrortxt = "";
			$kpl           = "";
			$rivinumero    = "";
			$kommentti     = "";
			$tuoteno       = "";
			$olinsapi      = "";
			$olinvarasto   = "";
			$edi_nimitys   = "";

			break;

		case 'OMSG__END' :
		case '*IE' :

			// jos meilla on otsikko voidaan laittaa sen valmiiksi
			if ($editilaus_otsikko_ok == "KYLLA") {

				// Tilaus merkataan valmiiksi
				$query = "UPDATE lasku SET alatila='A' WHERE tunnus='$id' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or die($query);

				// haetaan tilauksen tiedot
				$query    = "select * from lasku where tunnus='$id' and yhtio='$kukarow[yhtio]'";
				$result   = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_array($result);

				// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
				$kukarow["kesken"] = $laskurow["tunnus"];

				// jos terminaalitoimitus niin EI tehdä tilaus-valmista...
				if ($editilaus_tila != "Z") {

					if ($yhtiorow['jt_automatiikka'] != '') {
						//lisätään tilaukseen ne jt-rivit jotka riittävät kaikille
						require ("jtautomatiikka.inc");

						$edi_ulos .= "\n";
						$edi_ulos .= jtautomatiikka($arow["tunnus"], "EDI", $laskurow["tunnus"]);
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

					$edi_ulos .= "$tilaus_valmis_ulos\n";
					$edi_ulos .= "$tilaus_valmis_message\n";
					$edi_ulos .= "\n".t("Merkittiin tilaus valmiiksi").": $id.\n\n";
				}
				else {
					$edi_ulos .= "\n".t("Terminaalitoimitus vastaanotettu").": $id.\n\n";
				}

			}
			else {
				$edi_ulos .= "\n".t("Otsikko oli virheellinen tilausta/rivejä ei lisätty")."!\n\n";
			}

			// nollataan kaikki laskun muuttujat (kaikki nämä samat pitää nollata filen alussa)
			unset($arow);
			$editilaus_tila       = "N"; // laskun tila
			$editilaus_tyyppi     = "L"; // tilausrivi tyyppi
			$id                   = "";
			$edi_kommentti        = "";
			$vastaustapa          = "";
			$maksaja              = "";
			$viite                = "";
			$viitetxt             = "";
			$editilaus_otsikko_ok = "";
			$suunnistus           = "";
			$tnimi                = "";
			$tpostitp             = "";
			$tpostino             = "";
			$tosoite              = "";
			$lopnumero            = "";
			$lopnimi              = "";
			$loppostitp           = "";
			$loppostino           = "";
			$loposoite            = "";
			$edi_nimitys          = "";
			$olinsapi             = "";
			$olinvarasto          = "";
			$lopviesti            = "";

			break;
	}
}

// lähetetään meili
if ($yhtiorow["alert_email"] != "") {

	$edi_ulos = strip_tags($edi_ulos);

	$bound   = uniqid(time()."_") ;
	$kutsu   = $yhtiorow["yhtio"]."-ediorder.txt";

	$header  = "From: <mailer@pupesoft.com>\n";
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

	$handle  = fopen($filename, "r");
	$sisalto = fread($handle, filesize($filename));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\n";

	$boob = mail($yhtiorow["alert_email"],  "$edi_subj - $yhtiorow[nimi]", $content, $header);

}

?>
