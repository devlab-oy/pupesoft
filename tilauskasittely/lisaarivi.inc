<?php

	// Tarvitsemme:
	// $trow				--> jossa on tuotteen kaikki tiedot
	// $rivinumero			--> kent‰ss‰ on joko tilaajan rivinumero tai konserninsis‰isiss‰ kaupoissa sis‰inen toimittajanumero
	// $laskurow			--> jossa on laskun kaikki tiedot
	// $kukarow[kesken]		--> jossa on k‰ytt‰j‰ll‰ keskenoleva tilausnumero
	// $kpl					--> jossa on tilattu kappalem‰‰r‰
	// $tuoteno				--> jossa on tilattava tuotenumero
	// $toimaika			--> arvioitu toimitusaika
	// $kerayspvm			--> toivottu ker‰ysaika
	// $hinta				--> k‰ytt‰j‰n syˆtt‰m‰ hinta
	// $netto				--> k‰ytt‰j‰n syˆtt‰m‰ netto
	// $ale1,2,3,4,5...		--> k‰ytt‰j‰n syˆtt‰m‰ ale
	// $var					--> H,J,P varrit
	// $varasto				--> myyd‰‰n vain t‰st‰/n‰ist‰ varastosta
	// $paikka				--> myyd‰‰n vain t‰lt‰ paikalta
	// $rivitunnus			--> tietokannan tunnus jolle rivi lis‰t‰‰n
	// $rivilaadittu		--> vanhan rivin laadittuaika, s‰ilytet‰‰n se
	// $korvaavakielto		--> Jos erisuuri kuin tyhj‰ niin ei myyd‰ korvaavia
	// $jtkielto			--> Jos erisuuri kuin tyhj‰ niin ei laiteta JT:Seen
	// $perhekielto			--> Jos erisuuri kuin tyhj‰ niin ei etsit‰ ollenkaan perheit‰
	// $varataan_saldoa		--> Jos == EI niin ei varata saldoa (tietyiss‰ keisseiss‰), tai siis ei ainakan tehd‰ saldotsekki‰
	// $kutsuja				--> Kuka t‰t‰ skripti‰ kutsuu
	// $myy_sarjatunnus		--> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
	// $osto_sarjatunnus	--> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
	// $jaksotettu			--> Kuuluuko tilausrivi mukaan jaksotukseen
	// $perheid				--> Tuoteperheen perheid
	// $perheid2			--> Lis‰varusteryhm‰n perheid2
	// $orvoteikiinnosta	--> Meit‰ ei kiinnosta orvot jos t‰m‰ ei ole tyhj‰.
	// $osatoimkielto		--> Jos saldo ei riit‰ koko riville niin ei lis‰t‰ rivi‰ ollenkaan
	// $olpaikalta			--> pakotetaan myym‰‰n oletuspaikalta
	// $tuotenimitys		--> tuotteen nimitys jos nimityksen syˆtˆ on yhtiˆll‰ sallittu
	// $tuotenimitys_force	--> tuotteen nimitys muutetaan systemitasolla

	// Palauttaa arrayn $lisatyt_rivit1 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si myyntihaarassa
	// Palauttaa arrayn $lisatyt_rivit2 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si puute-jt-osto-haarassa

	if (!isset($rivitunnus)) $rivitunnus = 0;
	else $rivitunnus = (int) $rivitunnus;

	if (!isset($perheid)) 	$perheid = 0;
	else $perheid = (int) 	$perheid;

	if (!isset($perheid2)) 	$perheid2 = 0;
	else $perheid2 = (int) 	$perheid2;

	if (!isset($rivilaadittu)) 		$rivilaadittu = "";
	if (!isset($kommentti)) 		$kommentti = "";
	if (!isset($jaksotettu)) 		$jaksotettu = "";
	if (!isset($korvaavakielto))	$korvaavakielto = "";
	if (!isset($orvoteikiinnosta)) 	$orvoteikiinnosta = "";
	if (!isset($jtspec)) 			$jtspec = "";
	if (!isset($paikka)) 			$paikka = "";
	if (!isset($perhekielto)) 		$perhekielto = "";

	if ($toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely_tuoteperhe'] == 'U') {
		$perhekielto = "Y";
	}

	unset($korvaavan_hinta_ylaraja_halvin_alehinta);
	unset($korvaavan_hinta_ylaraja_halvin_hinta);
	unset($korvaavan_hinta_ylaraja_halvin_ale);
	unset($korvaavan_hinta_ylaraja_halvin_netto);

	if (!function_exists("tuoteperhe_reku")) {
		function tuoteperhe_reku($esiisatuoteno, $isatuoteno, $tuoteperhe, $kaikkituotteet, $perhelisa, $kieltolisa, $lisaarivi_debug) {
			global $yhtiorow, $kukarow;

			//Haetaan tuoteperheen lapset ja lis‰t‰‰n ne $tuoteperhe ja $kaikkituotteet arraysiin
			$query = "	SELECT tuoteperhe.*
						FROM tuoteperhe
						JOIN tuote ON tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
						WHERE tuoteperhe.yhtio = '$kukarow[yhtio]'
						and tuoteperhe.isatuoteno = '$isatuoteno'
						$perhelisa
						$kieltolisa
						ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
			$perheresult = pupe_query($query);

			while ($perherow = mysql_fetch_assoc($perheresult)) {

				$perherow["tuoteno"] = strtoupper($perherow["tuoteno"]);

				if ($lisaarivi_debug==1) echo "Tuoteperheen lapsi: $perherow[tuoteno]<br>";

				//T‰t‰ tuoteperhett‰ halutaan myyd‰
				if (!in_array($perherow["tuoteno"], $tuoteperhe)) $tuoteperhe[] = $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
				if (!in_array($perherow["tuoteno"], $kaikkituotteet)) $kaikkituotteet[] = $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
			}

			return array($tuoteperhe, $kaikkituotteet);
		}
	}

	if (!function_exists("tuoteperhe_reku2")) {
		function tuoteperhe_reku2($tuoteno, $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array) {
			global $yhtiorow, $kukarow;

			$perhearray = array();
			$tuoteno 	= strtoupper($tuoteno);

			if (in_array($tuoteno, $isat_array)) {
				//echo "FUULI! TEET IKUISEN LUUPIN!!!!!!!!<br>";
			}
			else {
				$isat_array[] = $tuoteno;

				// Haetaan tuoteperheen lapset ja lis‰t‰‰n ne $isa_array arrayseen
				$query = "	SELECT tuoteperhe.*
							FROM tuoteperhe
							JOIN tuote ON tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
							WHERE tuoteperhe.yhtio = '$kukarow[yhtio]'
							and tuoteperhe.isatuoteno = '$tuoteno'
							$perhelisa
							$kieltolisa
							ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
				$perheresult = pupe_query($query);

				while ($perherow = mysql_fetch_assoc($perheresult)) {
					$perherow["tuoteno"] = strtoupper($perherow["tuoteno"]);

					$kaikki_array[]		= $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
					$kerroin_array[]	= $tuoteno."#!°!#".$perherow["kerroin"];
					$ohita_kerays_array[] = $perherow['ohita_kerays'];

					$mikaisa = $perherow["tuoteno"];
					$tmp_kaikki_array = $kaikki_array;
					$tmp_kerroin_array = $kerroin_array;

					krsort($tmp_kaikki_array);
					krsort($tmp_kerroin_array);

					$perherow["kerroin"] = 1;

					// Lasketaan kerroin rekursiivisesti taaksep‰in
					foreach ($tmp_kerroin_array as $ke_ind => $ke_kerr) {
						list($ker_isa, $ke_ker) = explode("#!°!#", $ke_kerr);

						if ($ke_ker <= 0) {
							$ke_ker = 1;
						}

						if ($mikaisa == $tmp_kaikki_array[$ke_ind]) {
							$mikaisa = $ker_isa;
							$perherow["kerroin"] *= $ke_ker;
						}
					}

					$perhearray[] = $perherow;
				}
			}
			return array($perhearray, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array);
		}
	}

	// Onko p‰‰ss‰tty varasto josta myyd‰‰n
	if (isset($varasto) and is_array($varasto) and count($varasto) > 0) {
		$varasto = $varasto;
	}
	elseif (isset($varasto) and (int) $varasto > 0) {
		$varasto = array((int) $varasto);
	}
	elseif (isset($laskurow["varasto"]) and (int) $laskurow["varasto"] > 0) {
		$varasto = array((int) $laskurow["varasto"]);
	}
	elseif (isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0) {
		$varasto = explode(",", $kukarow["varasto"]);
	}
	else {
		unset($varasto);
	}

	if (isset($varasto) and $lisaarivi_debug == 1) echo "Myyd‰‰n varastoista: ",var_dump($varasto),"<br>";

	//katotaan oletuspaikka ja myyd‰‰n tietenkin sielt‰ jos niin halutaan
	if ($olpaikalta != '') {
		$olquery = "SELECT concat_ws('#!°!#',hyllyalue,hyllynro,hyllyvali,hyllytaso) olpaikka
					FROM tuotepaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuoteno = '$trow[tuoteno]'
					AND oletus != ''
					LIMIT 1";
		$olpares = pupe_query($olquery);
		$olparow = mysql_fetch_assoc($olpares);

		$paikka	= $olparow["olpaikka"];
	}

	// P‰iv‰m‰‰rien oikeellisuustsekki
	list($aikatsk_v, $aikatsk_k , $aikatsk_p) = explode("-", $toimaika);

	$aikatsk_k = (int) $aikatsk_k;
	$aikatsk_p = (int) $aikatsk_p;
	$aikatsk_v = (int) $aikatsk_v;

	if ((int) $aikatsk_k == 0 or (int) $aikatsk_p == 0 or (int) $aikatsk_v == 0 or !checkdate($aikatsk_k, $aikatsk_p, $aikatsk_v)) {
		$toimaika = date("Y-m-d");
		if ($toim == "YLLAPITO") {
			$toimaika = "0000-00-00"; // Yll‰pitosoppareissa toimaika tarkoittaa sopimuksen poikkeavaa alkup‰iv‰‰ ja nolla on default
		}
	}

	list($aikatsk_v, $aikatsk_k , $aikatsk_p) = explode("-", $kerayspvm);

	$aikatsk_k = (int) $aikatsk_k;
	$aikatsk_p = (int) $aikatsk_p;
	$aikatsk_v = (int) $aikatsk_v;

	if ((int) $aikatsk_k == 0 or (int) $aikatsk_p == 0 or (int) $aikatsk_v == 0 or !checkdate($aikatsk_k, $aikatsk_p, $aikatsk_v)) {
		$kerayspvm = date("Y-m-d");
		if ($toim == "YLLAPITO") {
			$kerayspvm = "0000-00-00"; // Yll‰pitosoppareissa kerayspvm tarkoittaa sopimuksen poikkeavaa loppup‰iv‰‰ ja nolla on default
		}
	}

	// Jos yhtiˆn saldok‰sittely on ajasta riippuvainen
	if ($yhtiorow["saldo_kasittely"] == "T") {
		$saldoaikalisa = $kerayspvm;
	}
	else {
		$saldoaikalisa = "";
	}

	// Muutetaan pilkut pisteiksi
	$kpl	= str_replace(',', '.', $kpl);
	$hinta	= str_replace(',', '.', $hinta);

	if ($laskurow['tila'] == 'O' or $laskurow['tila'] == 'K') {
		for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
			${'alkupera_ale'.$alepostfix} = ${'ale'.$alepostfix};
		}
	}
	else {
		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
			${'alkupera_ale'.$alepostfix} = ${'ale'.$alepostfix};
		}
	}

	// Alkuper‰iset tiedot talteen
	$alkupera_trow	= $trow;
	$alkupera_kpl 	= $kpl;
	$alkupera_hinta = $hinta;
	$alkupera_netto = $netto;
	$alkupera_alv 	= $alv;

	$saldoyhteensa			 = 0; 					// t‰h‰n summaillaan saldoa yhteens‰
	$lapsiyhteensa			 = 0;
	$korvaavan_isaa_myyty	 = 0;
	$perheid_lisaarivi		 = 0;
	$lop					 = "";
	$tuoteperhe				 = array(); 				// Tuoteperheiden lapset menev‰t t‰nne
	$kaikkituotteet			 = array(); 				// t‰h‰n arrayseen lis‰t‰‰n kaikki tuotteet ketjusta
	$kaikkituotteet[]		 = strtoupper($tuoteno); // ekaksi lis‰t‰‰n ketjuun kysytty tuote
	$isa_array 				 = array();
	$var					 = strtoupper($var); 	// varri isoks
	$netto					 = strtoupper($netto); 	// netto isoks
	$myyntierahuomatus 		 = FALSE;
	$ostoerahuomatus 		 = FALSE;
	$myy_erikoistoim_ok		 = TRUE;
	$org_kpl				 = "";
	$suoratoimitus_splittaus = FALSE;
	$tlisatied_lisa			 = "";

	// n‰ihin arraysiin tulee sitten myyt‰viss‰ olevat tuotteet, kappaleet ja varastopaikat
	$myy_tuoteno 	= array();
	$myy_kpl 		= array();
	$myy_hyllyalue 	= array();
	$myy_hyllynro 	= array();
	$myy_hyllytaso 	= array();
	$myy_hyllyvali 	= array();
	$myy_varasto 	= array();
	$myy_erikoistoimitus = array();
	$myy_kpl_myyty 	= array();
	$myy_hintakerr 	= array();
	$myy_alekerroi 	= array();
	$myy_era 		= array();
	$myy_tyyppi		= array();
	$myy_lapsi_ein 	= array();
	$myy_ohita_kerays = array();
	$lisatyt_rivit1 = array();
	$lisatyt_rivit2 = array();
	$korkieltokommi = array();
	$myyntierahuom	= array();
	$ostoerahuom	= array();

	// debuggia varten, laita 1 jos haluut messuja
	$lisaarivi_debug = 0;

	if ($laskurow['tila'] == 'O' or $laskurow['tila'] == 'K') {
		$rivityyppi = 'O';	// Ostotilausrivi
	}
	elseif ($laskurow["tilaustyyppi"] == 'E') {
		$rivityyppi = 'E'; // Ennakkotilausrivi
	}
	elseif ($laskurow["tila"] == 'T') {
		$rivityyppi = 'T'; // Tarjousrivi
	}
	elseif ($laskurow["tila"] == 'G') {
		$rivityyppi = 'G';	// Siirtolistatilausrivi
	}
	elseif ($laskurow["tila"] == 'S') {
		$rivityyppi = 'G';	// Sis‰inen tyˆm‰‰r‰ysrivi
	}
	elseif ($laskurow["tila"] == 'V' and $perheid2 == -100) {
		$rivityyppi = 'W';	// Valmistustilausrivi (Valmiste)
	}
	elseif ($laskurow["tila"] == 'V') {
		$rivityyppi = 'V';	// Valmistustilausrivi
	}
	elseif ($laskurow["tila"] == 'Z') {
		$rivityyppi = 'Z';	// Terminaalitoimitus
	}
	elseif ($laskurow["tila"] == 'B') {
		$rivityyppi = 'B';	// Ostoskori
	}
	elseif ($laskurow["tila"] == 'F') {
		$rivityyppi = 'F';	// Tallennettu web-tilaus/-tarjous
	}
	elseif ($laskurow["tila"] == '0') {
		$rivityyppi = '0';	// Soppari
	}
	else {
		$rivityyppi = 'L'; // Myyntitilausrivi
	}

	if ($kpl > 0 and (($trow['myynti_era'] > 0 and $yhtiorow['myyntiera_pyoristys'] == 'K') or $trow['minimi_era'] > 0) and ($rivityyppi == 'L' or $rivityyppi == 'E') and $rivitunnus == 0 and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS' or $toim == 'EXTRANET' or ($kutsuja == "EDITILAUS" and $edi_tyyppi != "magento"))) {
		$org_kpl = $kpl;

		if ($kpl < $trow['minimi_era']) {
			$kpl = $trow['minimi_era'];
		}

		if ($yhtiorow['myyntiera_pyoristys'] == 'K') {
			if ($kpl <= $trow['myynti_era']) {
				$kpl = $trow['myynti_era'];
			}
			else {
				$kokonaisluku = round($kpl / $trow['myynti_era']);

				$test = $kokonaisluku * $trow['myynti_era'];

				if ($kpl > $test) {
					$test = ($kokonaisluku + 1) * $trow['myynti_era'];
				}

				$kpl = (float) $test;
			}
		}

		if ($org_kpl != $kpl) $myyntierahuomatus = TRUE;
	}

	if ($kpl > 0 and $rivityyppi == 'O' and $yhtiorow['ostoera_pyoristys'] == 'K') {
		$org_kpl = $kpl;

		// haetaan tuotteen toimittajan takaata pakkauskoko tai jotain
		// Otetaan $trow laajemmin, tuotteen_toimittaja tiedot ja 2 avainsanaa
		$query = "	SELECT tuote.*, tt.toim_tuoteno, tt.osto_era, tt.pakkauskoko, tt.ostohinta, tt.valuutta, tt.osto_alv, ta1.selite pakkaus2, ta2.selite pakkaus3
					FROM tuote
					JOIN tuotteen_toimittajat as tt on (tt.yhtio = tuote.yhtio and tuote.tuoteno = tt.tuoteno and tt.liitostunnus = '$laskurow[liitostunnus]')
					LEFT JOIN tuotteen_avainsanat as ta1 on (ta1.yhtio=tuote.yhtio and ta1.tuoteno=tuote.tuoteno and ta1.laji='pakkauskoko2' )
					LEFT JOIN tuotteen_avainsanat as ta2 on (ta2.yhtio=tuote.yhtio and ta2.tuoteno=tuote.tuoteno and ta2.laji='pakkauskoko3')
					WHERE tuote.yhtio='{$kukarow["yhtio"]}'
					AND tuote.tuoteno = '{$trow["tuoteno"]}'";
		$ttresult = pupe_query($query);
		$ttrow = mysql_fetch_assoc($ttresult);

		if ($kpl <= $ttrow['osto_era']) {
			$kpl = $ttrow['osto_era'];
		}
		else {

			$jaannos = 0;
			$pakkauskoko = 0;

			$ttrow["pakkaus3"] !="" ? $pakkausarray[] = $ttrow["pakkaus3"] : "";
			$ttrow["pakkaus2"] !="" ? $pakkausarray[] = $ttrow["pakkaus2"] : "";
			$pakkausarray[] = $ttrow["osto_era"]; // Oletus jos ei muuta lˆydy.

			foreach ($pakkausarray as $pak) {
				(float) $jaannos = fmod($kpl,$pak);

				if ($jaannos == 0 or $kpl > $pak) {
					$pakkauskoko = $pak;
					break;
				}
				if ($kpl > $pak) $pakkauskoko = $pak;
			}

			if ($jaannos != 0) {
				// pyˆristet‰‰n pakkauskokokoon
				$kpl = ceil($kpl / $pakkauskoko) * $pakkauskoko;
			}

		}

		if ($org_kpl != $kpl) $ostoerahuomatus = TRUE;
	}

	$toimittamatta = $kpl; //N‰in paljon ollaan toimittamassa

	if ($lisaarivi_debug == 1) echo "Tilattu m‰‰r‰: $toimittamatta<br>";

	// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
	if (substr($paikka,0,3) == "°°°") {
		$var 		 		= "T";
		$toimittajan_tunnus	= substr($paikka, 3);
		$paikka		 		= "#!°!#";
	}
	elseif ($var == "T" and substr($paikka,0,3) != "°°°" and substr($paikka,0,3) != "!!!") {
		$var 				= "";
		$toimittajan_tunnus	= "";
	}

	if (substr($paikka,0,3) == "!!!") {
		$var 		 		= "U";
		$toimittajan_tunnus	= substr($paikka, 3);
		$paikka		 		= "";
	}
	elseif ($var == "U" and substr($paikka,0,3) != "!!!") {
		$var 				= "";
		$toimittajan_tunnus	= "";
	}

	// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
	if (substr($paikka,0,3) == "°°V") {
		$var 		 		= "R";
		$paikka		 		= substr($paikka, 3);
	}
	elseif ($var == "R" and substr($paikka,0,3) != "°°V" and substr($paikka,0,3) != "°°V") {
		$var 				= "";
		$toimittajan_tunnus	= "";
	}

	$perhelisa = "";

	//Haetaan myˆs tuoteperheet tai reseptit, t‰ss‰ parametrit niille.
	if ($laskurow['tila'] == 'V' and $var != "W") {
		$perhelisa = " and tuoteperhe.tyyppi='R' ";
	}
	elseif ($var == "W" and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
		// Asiaakkaallevalmistus ja halutaan myyd‰ rivi varastosta
		$perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
		$var 		= "";

		if ($perheid == 0 or $perheid == $rivitunnus) {
			$rivityyppi = 'L'; // Myyntitilausrivi
		}
	}
	else {
		$perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
	}

	// vientikieltok‰sittely:
	// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
	// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa
	$kieltolisa = "";
	$uusin_korvaava_tuote = '';

	if ($laskurow["toim_maa"] != "" and $laskurow["tila"] != 'V') {
		$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$laskurow[toim_maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$laskurow[toim_maa]%' ";
	}

	//Tutkitaan onko t‰m‰ hyvitysrivi
	if ((float) $kpl < 0 and trim($var) == "") {
		//Laitetaan varriksi Y jotta ei tarvitse menn‰ myyntihaaraan
		$var = "Y";
	}

	// Jos varastosiirto tehd‰‰n saman varaston sis‰ll‰, niin siiret‰‰n aina oletupaikalle
	// Eli otetaan tuote joltain muulta paikalta kuin oletuspaikalta
	if ($laskurow["tila"] == 'G' and $laskurow["clearing"] == $laskurow["varasto"]) {
		$notfromvarasto = " and tuotepaikat.oletus = '' ";
	}
	else {
		$notfromvarasto = "";
	}

	// Jos skripti (kululaskut.inc) p‰‰ss‰‰ tilausrivilinkin, niin laitetaan se mukaan kyselyyn
	if ($tilausrivilinkki > 0) {
		$tlisatied_lisa = " , tilausrivilinkki = '".mysql_real_escape_string($tilausrivilinkki)."' ";
	}

	if ($rivityyppi != 'O' and trim($var) == "" and $trow["tuoteno"] != "") {

		// Katsotaan onko maksuehto j‰lkivaatimus, koska silloin ei toimiteta erikoisvarastoista vaikka erikoistoimitus_alarajasumma ylittyisi
		if (!isset($jvtsekres)) {
			$apuqu = "	SELECT tunnus
						FROM maksuehto
						WHERE yhtio	= '$kukarow[yhtio]'
						AND tunnus	= '$laskurow[maksuehto]'
						AND jv 	   != ''";
			$jvtsekres = pupe_query($apuqu);

			if (mysql_num_rows($jvtsekres) > 0) {
				$myy_erikoistoim_ok = FALSE;
			}
		}

		// Vientiasiakkaille ei myyd‰ erikoisvarastoista jos toimitustapa on nouto
		if (!isset($tntsekres)) {
			$apuqu = "	SELECT tunnus
						FROM toimitustapa
						WHERE yhtio	= '$kukarow[yhtio]'
						AND selite	= '$laskurow[toimitustapa]'
						AND nouto  != ''";
			$tntsekres = pupe_query($apuqu);

			if (mysql_num_rows($tntsekres) > 0) {
				$myy_erikoistoim_ok = FALSE;
			}
		}

		if ($lisaarivi_debug==1) echo "Menn‰‰n myyntihaaraan: $tuoteno<br>";

		// Etsit‰‰n korvaavia tuotteita
		if ($trow["ei_saldoa"] == "" and $korvaavakielto == "" and $rivityyppi == 'L') {

			// Tutkitaan ensin saako t‰lle asiakkaalle myyd‰ korvaavia t‰st‰ tuoteryhm‰st‰
			$query = " SELECT *
						from korvaavat_kiellot
						where yhtio = '$kukarow[yhtio]'
						and ytunnus in ('$laskurow[ytunnus]','*')
						and osasto in ('$trow[osasto]','*')
						and try in ('$trow[try]','*')";
			$kkres = pupe_query($query);

			if ($kk_row = mysql_fetch_assoc($kkres)) {
				// Ei myyd‰ mit‰‰n korvaavia
				if ($kk_row['laji'] == 'K') {
					$korvaavat_kiellot = "KIELTO";
				}
				// Korvaavia tuotteidenta myyd‰‰n, mik‰li yksitt‰ist‰ korvaavaa lˆytyy tilattu m‰‰r‰‰
				elseif ($kk_row['laji'] == 'Q') {
					$korvaavat_kiellot = "UNIIKKI";
					$kaikkituotteet = array(); // Nollataan kaikkituotteet array
				}
				// Korvaavia ja Vastaavia tuotteita myyd‰‰n, mik‰li yksitt‰ist‰ tuotetta lˆytyy tilattu m‰‰r‰‰
				elseif ($kk_row['laji'] == 'X') {
					$korvaavat_kiellot = "VASTAAVAT";
					$kaikkituotteet = array();	// Nollataan kaikkituotteet array
				}
				// Korvaavia tuotteita myyd‰‰n, mik‰li korvaavuusketjusta lˆytyy tilattu m‰‰r‰
				else {
					$korvaavat_kiellot = "";
				}
			}
			else {
				$korvaavat_kiellot = "";
			}

			// Otetaan alkuper‰isen tuotteen hinta talteen
			list($korva_isa_hinta, , $korva_isa_ale_kaikki, , ) = alehinta($laskurow, $trow, $kpl, '', '', '', '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));

			// Tehd‰‰n homma siten, ett‰ etsit‰‰n kaikki korvaavat jo t‰ss‰ vaiheessa arrayseen
			// Haetaan korvaavuusketju tuotteelle
			require 'korvaavat.class.php';
			$korvaavat = new Korvaavat($tuoteno);

			// Korvaavuusketjun j‰rjestyksess‰ pienemp‰‰n p‰in, eli p‰‰tuotetta kohden
			$options = array('korvaavuusketjun_jarjestys' => $yhtiorow['korvaavuusketjun_jarjestys']);
			$korvaavat_tuotteet = $korvaavat->tuotteet($options);

			// Haetaan vastaavuusketjut korvaavat ketjun p‰‰tuotteella
			require 'vastaavat.class.php';
			$vastaavat = new Vastaavat($korvaavat->paatuote());

			// Tsekataan onko useampia vastaavuusketjuja
			if (count($vastaavat->ketjut()) > 1) {
				echo "<font class='error'>Tuote ({$korvaavat->paatuote()}) lˆytyi useasta vastaavuusketjusta, k‰ytet‰‰n ensimm‰ist‰.</font>";
			}

			// Vastaavat tuotteet array
			$vastaavat_tuotteet = array();
			$kommentti_lisa = "";

			// Vastaavien automaattinen myynti, eli haetaanko vastaavat
			if ($korvaavat_kiellot == "VASTAAVAT") {
				$options = array('vastaavuusketjun_jarjestys' => $yhtiorow['vastaavuusketjun_jarjestys']);
				$vastaavat_tuotteet = $vastaavat->tuotteet($options);
			}

			// Yhdistet‰‰n korvaavat ja vastaavat (ja vaihtoehtoiset?) ketjut
			$tuote_ketju = array_merge($korvaavat_tuotteet, $vastaavat_tuotteet);

			// Tuotteen korvaavat ja vastaavat ketju
			foreach ($tuote_ketju as $krow2) {

				list(, , $krmyytavissa) = saldo_myytavissa($krow2["tuoteno"], $jtspec, '', '', '', '', '', '', '', $saldoaikalisa);
				list($korva_hinta, $korva_netto, $korva_ale_kaikki, , ) = alehinta($laskurow, $krow2, $kpl, '', '', '', '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));

				// Kommenti jos tuote on loppu, joka tilanteessa?
				if ($krmyytavissa == 0) {
					$kommentti_lisa = t("Tuote {$krow2[tuoteno]} on tilap‰isesti loppu. ");
				}

				$if_ale_korva_isa_chk1 = $korva_isa_hinta;
				$if_ale_korva_isa_chk2 = $korva_hinta;

				$korva_isa_ale_kaikki['netto'] = $netto;
				$korva_isa_ale_kaikki['erikoisale'] = $laskurow['erikoisale'];
				$if_ale_korva_isa_chk1 *= generoi_alekentta_php($korva_isa_ale_kaikki, 'M', 'kerto');

				$korva_ale_kaikki['netto'] = $netto;
				$korva_ale_kaikki['erikoisale'] = $laskurow['erikoisale'];
				$if_ale_korva_isa_chk2 *= generoi_alekentta_php($korva_ale_kaikki, 'M', 'kerto');

				// Ei saa myyd‰ korvaavia tuotteita
				if ($korvaavat_kiellot == "KIELTO") {
					if ($krmyytavissa > 0) {
						$korkieltokommi[] = strtoupper($krow2["tuoteno"]." ($krow2[myyntihinta])");
					}
				}

				// Korvaavia tuotteita myyd‰‰n, mik‰li yksitt‰ist‰ tuotetta lˆytyy tilattu m‰‰r‰ (UNIIKKI)
				// Korvaavia ja vastaavia tuotteita myyd‰‰n mik‰li yksitt‰ist‰ tuotetta lˆytyy tilattu m‰‰r‰ (VASTAAVAT)
				elseif ($korvaavat_kiellot == "UNIIKKI" or $korvaavat_kiellot == "VASTAAVAT") {

					// Yksitt‰ist‰ tuotetta oltava enemm‰n kuin tilattu m‰‰r‰
					if ($krmyytavissa >= $kpl) {

						// Lis‰t‰‰n vain yksi lˆydetty tuote myyt‰v‰ksi
						if (count($kaikkituotteet) <= 1) {

							// Vastaavista kommentti riville
							if ($krow2[tyyppi] == 'vastaava') {
								$kommentti_lisa .= t("Toimitetaan vastaava tuote $krow2[tuoteno].");
							}

							// Lis‰t‰‰n tuote
							$kaikkituotteet[] = strtoupper($krow2["tuoteno"]);
						}
					}
				}

				// Asiakas saa aina halvemman hinnan mukaan tuotteen, mikâli alkuperâinen tuote on P toistuva tai puute.
				elseif ($yhtiorow["korvaavan_hinta_ylaraja"] == 'MIN') {

					// T‰h‰n hintaan asiakas saisi tuotteen
					if ($korva_hinta > 0) {
						$korvaavan_hinta_ylaraja_hinta = $korva_hinta * generoi_alekentta_php($korva_ale_kaikki, 'M', 'kerto');
					}
					else {
						unset($korvaavan_hinta_ylaraja_hinta);
					}

					// Otetaan koko ketjun halvin hinta/alennus talteen (ei poistetut tuotteet)
					if (isset($korvaavan_hinta_ylaraja_hinta) and $krow2["status"] != "P" and (!isset($korvaavan_hinta_ylaraja_halvin_alehinta) or $korvaavan_hinta_ylaraja_halvin_alehinta > $korvaavan_hinta_ylaraja_hinta)) {
						$korvaavan_hinta_ylaraja_halvin_alehinta = $korvaavan_hinta_ylaraja_hinta;
						$korvaavan_hinta_ylaraja_halvin_hinta = $korva_hinta;
						$korvaavan_hinta_ylaraja_halvin_ale = $korva_ale_kaikki;
						$korvaavan_hinta_ylaraja_halvin_netto = $korva_netto;
					}

				 	if ($krmyytavissa > 0 and !in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) {
						$kaikkituotteet[] = strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
					}
				}
				elseif ($yhtiorow["korvaavan_hinta_ylaraja"] != '' and $yhtiorow["korvaavan_hinta_ylaraja"] != 'MIN' and ($if_ale_korva_isa_chk1) != 0 and (($if_ale_korva_isa_chk2) / ($if_ale_korva_isa_chk1)) > (1+($yhtiorow["korvaavan_hinta_ylaraja"]/100))) {
					//Korvaavien myynti kielletty koska korvaava tuote on liian kallis
					if ($krmyytavissa > 0) {
						$korkieltokommi[] = strtoupper($krow2["tuoteno"]." ($krow2[myyntihinta])");
					}
				}
				else {
					//Yritet‰‰n myyd‰ korvaava tuote
					$korvattiin_korvaavalla = "HIIOHEI!";

					if (!in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) $kaikkituotteet[]=strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
				}

				$uusin_korvaava_tuote = $krow2['tuoteno'];
			}

			if (count($korkieltokommi) == 1) {
				$korkieltokommi = t("Voit tilata korvaavaa tuotetta").": ".$korkieltokommi[0];
			}
			elseif (count($korkieltokommi) > 1) {
				$korkieltokommi = t("Voit tilata korvaavia tuotteita").": ".implode(", ",$korkieltokommi);
			}
		} // end etsit‰‰nkˆ korvaavia

		// Haetaan tuoteperheet, jos $paikka on erisuuri kuin tyhj‰ niin vaihdetaan vain jonkun tuotteen paikkaa eik‰ p‰ivitet‰ tuoteperheit‰
		// Tai jos muokataan tuoteperheen lasta ei menn‰ uudestaan t‰h‰n haaraan
		if ($perheid == 0 and $perhekielto == "") {

			foreach ($kaikkituotteet as $isatuoteno) {
				$riikoko = 1;
				$isa_array[] = $isatuoteno;

				for($isa=0; $isa < $riikoko; $isa++) {
					list($tuoteperhe, $kaikkituotteet) = tuoteperhe_reku($isatuoteno, $isa_array[$isa], $tuoteperhe, $kaikkituotteet, $perhelisa, $kieltolisa, $lisaarivi_debug);

					// Ollaanko rekursiivisia vai ei
					if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
						$isa_array 	= array_merge($isa_array, $tuoteperhe);
						$isa_array 	= array_unique($isa_array);
						$riikoko 	= count($isa_array);
					}
				}

				if (count($tuoteperhe) == 0 and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V' and $rivityyppi == "V") {
					// Asiakkaallevalmistuskeissi, resepit‰ ei lˆydy joten teemme t‰st‰ myyntirivin
					$rivityyppi = 'L'; // Myyntitilausrivi
				}
			}
		}

		//Kaikkien arrayden indeksien ‰iti
		$i = 0;

		// Sitten k‰yd‰‰n kaikkien tuotteiden saldot l‰pi kaikilta varastopaikoilta
		foreach ($kaikkituotteet as $tuoteno) {

			$query = "	SELECT ei_saldoa, status
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoteno'";
			$zres = pupe_query($query);
			$zrow = mysql_fetch_assoc($zres);

			if ($zrow["ei_saldoa"] == "") {

				if ($orvoteikiinnosta != '') {
					$orvot = 0;
				}
				else {
					// katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
					list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', $laskurow["toim_maa"], $saldoaikalisa);

					$orvot *= -1;
				}

				// K‰yd‰‰n l‰pi tuotepaikat
				if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and ($paikka != "EIERAAVIELA#!°!#EIEI")) {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								$notfromvarasto
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				else {

					$limit = "";

					if (isset($varasto) and !is_array($varasto) and $rivityyppi == 'E') {
						$limit = " LIMIT 1 ";
					}

					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, '' era
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue), 5, '0'),lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								$notfromvarasto
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta
								$limit";
				}

				$zres = pupe_query($query);

				//N‰hd‰‰n vaivaa jottaa saadaan paikat sortattua oletuspaikka ensin ja sitten myyt‰viss‰olevan m‰‰r‰n mukaan laskevassa j‰rjestyksess‰
				$zzrow 		= array();
				$zzrowlask 	= 0;

				while ($zrow = mysql_fetch_assoc($zres)) {

					// Saldolliset tuotteet
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, $jtspec, '', '', $zrow["hyllyalue"], $zrow["hyllynro"], $zrow["hyllyvali"], $zrow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $zrow["era"]);

					if ($lisaarivi_debug==1) echo "Saldo_myyt‰viss‰ funktio sanoo $tuoteno: $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $saldo, $hyllyssa, $myytavissa kappaletta (Er‰: $zrow[era]) PVM: $saldoaikalisa<br>";

					// Tutkitaan myyd‰‰nkˆ tietty‰ jo varattua lis‰varustetta, silloin annetaan AINA riitt‰v‰ saldo
					if ($perheid2 > 0 and $perheid2 != $rivitunnus and strtoupper($zrow["hyllyalue"]."#!°!#".$zrow["hyllynro"]."#!°!#".$zrow["hyllyvali"]."#!°!#".$zrow["hyllytaso"]) == strtoupper($paikka)) {
						$myytavissa = $toimittamatta;
					}

					// hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
					if ($orvot > 0) {
						if ($myytavissa >= $orvot) {
					 		// poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
					 		$myytavissa = $myytavissa - $orvot;
					 		$orvot = 0;
						}
						elseif ($orvot > $myytavissa) {
					 		// poistetaan niin paljon orpojen saldoa ku voidaan
					 		$orvot = $orvot - $myytavissa;
					 		$myytavissa = 0;
						}
					}

					if ($zrow['varasto'] == $kukarow['oletus_varasto'] and $zrow['oletus'] != '') {
						// K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 1
						$zrow['oletus'] = 3;
					}
					elseif ($zrow['varasto'] == $kukarow['oletus_varasto']) {
						// K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 2
						$zrow['oletus'] = 2;
					}
					elseif ($zrow['oletus'] != '') {
						// K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 1 jos k‰ytt‰j‰lle ei oo oletusvarastoa, muuten prio 3
						$zrow['oletus'] = 1;
					}

					$zzrow[$zzrowlask]["myytavissa"]	= $myytavissa;
					$zzrow[$zzrowlask]["ei_saldoa"]		= $zrow["ei_saldoa"];
					$zzrow[$zzrowlask]["oletus"]		= $zrow["oletus"];
					$zzrow[$zzrowlask]["varasto"]		= $zrow["varasto"];
					$zzrow[$zzrowlask]["era"]			= $zrow["era"];
					$zzrow[$zzrowlask]["varastomaa"]	= $zrow["varastomaa"];
					$zzrow[$zzrowlask]["varastotyyppi"]	= $zrow["varastotyyppi"];
					$zzrow[$zzrowlask]["hyllyalue"]		= $zrow["hyllyalue"];
					$zzrow[$zzrowlask]["hyllynro"]		= $zrow["hyllynro"];
					$zzrow[$zzrowlask]["hyllyvali"]		= $zrow["hyllyvali"];
					$zzrow[$zzrowlask]["hyllytaso"]		= $zrow["hyllytaso"];
					$zzrow[$zzrowlask]['erikoistoimitus_alarajasumma']  = $zrow['erikoistoimitus_alarajasumma'];

					if ($lisaarivi_debug==1) echo "Myyt‰viss‰ $tuoteno paikalta $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $myytavissa kappaletta<br>";

					// tilatulla tuotteella oli saldoa.. laitetaan lippu p‰‰lle, ett‰ huomataan osatoimituskeisi oikein..
					if ($tuoteno == $alkupera_trow["tuoteno"]) {
						$korvaavan_isaa_myyty = 1; // laitetaan lippu p‰‰lle ett‰ tiedet‰‰n ett‰ meneekˆ tilattua tuotetta ja korvaavaa tuotetetta samalla tilauksella
					}

					$zzrowlask++;
				}

				if (!function_exists('zzrowcmp')) {
					function zzrowcmp($a, $b) {
						if (strcmp($a["oletus"], $b["oletus"]) < 0) {
							return 1;
						}
						elseif (strcmp($a["oletus"], $b["oletus"]) > 0) {
							return -1;
						}
						elseif ($a["myytavissa"] < $b["myytavissa"]) {
							return 1;
						}
						elseif ($a["myytavissa"] > $b["myytavissa"]) {
							return -1;
						}
						else {
							return 0;
						}
					}
				}

				if (!function_exists('chk_varaston_saldo')) {
					function chk_varaston_saldo($arr, $kpl) {
						/*
						- Pit‰iskˆ eka kattoa lˆytyykˆ kaikki joltain yhdelt‰ paikalta?
						- Katotaan miss‰ oletuspaikka, riitt‰‰kˆ oletuspaikan varaston saldo?
						- Riitt‰‰kˆ jonkun toisen varaston saldo?
						- Jos ei niin splitataan.
						*/

						$chk_yksi_paikka = FALSE;

						// Pit‰iskˆ eka kattoa lˆytyykˆ kaikki joltain yhdelt‰ paikalta?
						foreach ($arr as $indx => $chk_row) {
							if ($chk_row['myytavissa'] >= $kpl) {
								unset($arr[$indx]);
								array_unshift($arr, $chk_row);
								$chk_yksi_paikka = TRUE;
								break;
							}
						}

						if (!$chk_yksi_paikka) {

							$chk_oletus_var = '';

							reset($arr);

							// Katotaan miss‰ oletuspaikka
							$chk_oletus_var = $arr[0]['varasto'];

							$chk_saldo = 0;
							$checked_saldo = false;

							// riitt‰‰kˆ oletuspaikan varaston saldo?
							foreach ($arr as $indx => $chk_row) {

								if ($chk_row['varasto'] == $chk_oletus_var) {
									$chk_saldo += $chk_row['myytavissa'];

									if ($chk_saldo >= $kpl) {
										$checked_saldo = true;
										break;
									}
								}
							}

							reset($arr);

							if ($checked_saldo) {
								foreach ($arr as $indx => $chk_row) {
									if ($chk_row['varasto'] == $chk_oletus_var) {
										unset($arr[$indx]);
										array_unshift($arr, $chk_row);
									}
								}
							}
							else {

								// Riitt‰‰kˆ jonkun toisen varaston saldo?
								$chk_distinct_varasto = array();

								// loopataan distinct varasto
								foreach ($arr as $indx => $chk_row) {
									if (!isset($chk_distinct_varasto[$chk_row['varasto']])) {
										$chk_distinct_varasto[$chk_row['varasto']] = $chk_row['varasto'];
									}
								}

								reset($arr);

								unset($chk_distinct_varasto[$chk_oletus_var]);

								foreach ($chk_distinct_varasto as $varasto) {

									$varaston_saldo = 0;
									$varasto_set = '';

									foreach ($arr as $indx => $chk_row) {
										if ($varasto == $chk_row['varasto']) {
											$varaston_saldo += $chk_row['myytavissa'];

											if ($varaston_saldo >= $kpl) {
												$varasto_set = $chk_row['varasto'];
												break;
											}
										}
									}

									if ($varasto_set != '') {
										reset($arr);

										foreach ($arr as $indx => $chk_row) {
											if ($chk_row['varasto'] == $varasto_set) {
												unset($arr[$indx]);
												array_unshift($arr, $chk_row);
											}
										}
									}
								}
							}
						}

						return $arr;
					}
				}

				// sortataan varastopaikkojen saldot by ostajan maa
				$oma_maa = array();
				$muu_maa = array();

				foreach ($zzrow as $zzrow_rivi) {
					// jos lˆydet‰‰n varasto toimitusoosoitteen maasta, k‰ytet‰‰n sit‰ eka
					if ($laskurow["toim_maa"] == $zzrow_rivi["varastomaa"] and $laskurow["toim_maa"] != "" and $zzrow_rivi["varastomaa"] != "") {
						$oma_maa[] = $zzrow_rivi;
					}
					else {
						$muu_maa[] = $zzrow_rivi;
					}
				}

				// sortataan ostajan maan varastopaikat saldo j‰rkk‰‰n
				usort($oma_maa, "zzrowcmp");
				// sortataan muiden maiden varastopaikat saldo j‰rkk‰‰n
				usort($muu_maa, "zzrowcmp");

				if ($yhtiorow['varaston_splittaus'] == 'K') {
					// sortataan ostajan maan varastopaikat saldo j‰rkk‰‰n
					$oma_maa = chk_varaston_saldo($oma_maa, $kpl);

					// sortataan muiden maiden varastopaikat saldo j‰rkk‰‰n
					$muu_maa = chk_varaston_saldo($muu_maa, $kpl);
				}

				// yhdistet‰‰n arrayt
				$zzrow = array_merge($oma_maa, $muu_maa);

				if (count($zzrow) > 0) {

					// Jos tuotteella on tuotepaikkoja
					foreach ($zzrow as $zrow) {

						if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or ($zrow["myytavissa"] > 0 and (($rivityyppi == 'L' and $kukarow['kassamyyja'] == '' and $myy_erikoistoim_ok and $zrow['varastotyyppi'] == 'E' and $zrow['erikoistoimitus_alarajasumma'] > 0 and (float) $trow['myyntihinta'] >= (float) $zrow['erikoistoimitus_alarajasumma']) or $zrow["varastotyyppi"] == '' or $zrow["varastotyyppi"] == 'V' or (is_array($varasto) and in_array($zrow["varasto"], $varasto) and $laskurow["varasto"] == 0) or $laskurow["varasto"] == $zrow["varasto"]))) {

							if ($lisaarivi_debug == 1) echo "Myyd‰‰n $tuoteno paikalta $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $zrow[myytavissa] kappaletta<br>";

							// lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
							$myy_tuoteno[$i]   = strtoupper($tuoteno);
							$myy_kpl[$i]       = $zrow["myytavissa"];
							$myy_hyllyalue[$i] = $zrow["hyllyalue"];
							$myy_hyllynro[$i]  = $zrow["hyllynro"];
							$myy_hyllyvali[$i] = $zrow["hyllyvali"];
							$myy_hyllytaso[$i] = $zrow["hyllytaso"];
							$myy_varasto[$i]   = $zrow["varasto"];
							$myy_era[$i] 	   = $zrow["era"];

							if (!in_array(strtoupper($tuoteno), $tuoteperhe)) {

								// Sis‰‰ntulevissa EDI-tilauksissa tehdastilaustuotteita ei saa tilata
								if (isset($kutsuja) and $kutsuja == "EDITILAUS" and $zrow['status'] == 'T') {
									$myy_tyyppi[$i] = "TEHDASTILAUSTUOTE";
								}
								else {
									$myy_tyyppi[$i] = "NORMAALI";
								}

								$saldoyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien isien saldoa yhteens‰.
							}
							else {
								$myy_tyyppi[$i] = "LAPSITUOTE";
								$lapsiyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien lapsien saldoa yhteens‰.
							}

							// jos halutaan myyd‰ erikoisvarastosta ja alarajasumma on syˆtetty ja tuotteen myyntihinta ylitt‰‰ alarajasumman
							if ($rivityyppi == 'L' and $kukarow['kassamyyja'] == '' and $myy_erikoistoim_ok and $zrow['varastotyyppi'] == 'E' and $zrow['erikoistoimitus_alarajasumma'] > 0 and (float) $trow['myyntihinta'] >= (float) $zrow['erikoistoimitus_alarajasumma']) {
								$myy_erikoistoimitus[$i] = true;
							}
							else {
								$myy_erikoistoimitus[$i] = false;
							}

							//Kasvatetaan indeksi‰
							$i++;
						}
					} // end foreach tuotepaikat
				}
				elseif ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI") {
					// Jos tuotteella taas ei ole ollenkaan tuotepaikkoja ja myyd‰‰n kuitenkin (eli ei piitata saldoista)

					if ($lisaarivi_debug==1) echo "Tuotteella $tuoteno ei ole yht‰‰n tuotepaikkaa! Myyd‰‰n silti koska saldoista ei nyt piitata!<br>";

					$myy_tuoteno[$i]   = strtoupper($tuoteno);
					$myy_kpl[$i]       = 99999999;
					$myy_hyllyalue[$i] = "";
					$myy_hyllynro[$i]  = "";
					$myy_hyllyvali[$i] = "";
					$myy_hyllytaso[$i] = "";
					$myy_varasto[$i]   = "";
					$myy_era[$i] 	   = "";

					if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
						// Sis‰‰ntulevissa EDI-tilauksissa tehdastilaustuotteita ei saa tilata
						if (isset($kutsuja) and $kutsuja == "EDITILAUS" and $zrow['status'] == 'T') {
							$myy_tyyppi[$i] = "TEHDASTILAUSTUOTE";
						}
						else {
							$myy_tyyppi[$i] = "NORMAALI";
						}

						$saldoyhteensa += $toimittamatta; // summaillaan kaikkien isien saldoa yhteens‰.
					}
					else {
						$myy_tyyppi[$i] = "LAPSITUOTE";
						$lapsiyhteensa += $toimittamatta; // summaillaan kaikkien lapsien saldoa yhteens‰.
					}

					//Kasvatetaan ‰iti-indeksi‰
					$i++;
				}
			}
			else {
				// Saldottomat tuotteet
				// lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
				$myy_tuoteno[$i]   = strtoupper($tuoteno);
				$myy_kpl[$i]       = 99999999;
				$myy_hyllyalue[$i] = "";
				$myy_hyllynro[$i]  = "";
				$myy_hyllyvali[$i] = "";
				$myy_hyllytaso[$i] = "";
				$myy_varasto[$i]   = "";
				$myy_era[$i] 	   = "";

				if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
					$myy_tyyppi[$i] = "NORMAALI";
					$saldoyhteensa += $toimittamatta; // summaillaan kaikkien isien saldoa yhteens‰.
					if ($lisaarivi_debug==1) echo "$tuoteno on is‰tuote/normituote<br>";
				}
				else {
					$myy_tyyppi[$i] = "LAPSITUOTE";
					$lapsiyhteensa += $toimittamatta; // summaillaan kaikkien lapsien saldoa yhteens‰.
					if ($lisaarivi_debug==1) echo "$tuoteno on lapsituote<br>";
				}

				//Kasvatetaan ‰iti-indeksi‰
				$i++;
			}
		} // end foreach myyt‰v‰t tuotteet

		if ($lisaarivi_debug==1) echo "Kaikkiaan myyt‰viss‰: $saldoyhteensa kappaletta<br>";

		//tutkitaan ensin saako t‰lle asiakkaalle myyd‰ osatoimituksen t‰st‰ tuoteryhm‰st‰
		$query = " SELECT yhtio
					from korvaavat_kiellot
					where yhtio = '$kukarow[yhtio]'
					and ytunnus in ('$laskurow[ytunnus]','*')
					and osasto in ('$trow[osasto]','*')
					and try in ('$trow[try]','*')
					and laji in ('O', 'Q')";
		$kkres = pupe_query($query);

		// jos result on nolla, saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi
		if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or (mysql_num_rows($kkres) == 0 and $osatoimkielto == "") or $saldoyhteensa >= $toimittamatta) {

			if ($lisaarivi_debug==1) echo "Saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi. Saldoa $saldoyhteensa Toimittamatta $toimittamatta<br>";

			// nyt meill‰ on $myy_ arrayperheess‰ kaikki tuotteet ja varastopaikat jolta voidaan myyd‰ jotain..
			// loopataan kaikki tuotteet l‰pi

			// Myyd‰‰n tietylt‰ paikalta
			if ($paikka != '') {
				$paikat = explode('#!°!#',$paikka);

				if ($lisaarivi_debug==1) echo "Halutaan myyd‰ paikalta: $paikat[0]-$paikat[1]-$paikat[2]-$paikat[3]<br>";

				foreach ($myy_tuoteno as $i => $tuoteno) {
					if ($yhtiorow["puute_jt_oletus"] == "O" or
						($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G" and ($myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1] == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i]) or
						(($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $myy_era[$i] == $paikat[4] and ($myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1] == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i])) {

						//Otetaan t‰m‰
						$myy_tuoteno 	= array();
						$myy_kpl 		= array();
						$myy_hyllyalue 	= array();
						$myy_hyllynro 	= array();
						$myy_hyllytaso 	= array();
						$myy_hyllyvali 	= array();
						$myy_varasto 	= array();
						$myy_kpl_myyty 	= array();
						$myy_era		= array();

						$myy_tuoteno[]   = strtoupper($tuoteno);
						$myy_kpl[] 		 = $toimittamatta;
						$myy_hyllyalue[] = $paikat[0];
						$myy_hyllynro[]  = $paikat[1];
						$myy_hyllyvali[] = $paikat[2];
						$myy_hyllytaso[] = $paikat[3];
						$myy_era[] 		 = $paikat[4];

						break;
					}
				}
			}

			// Myyd‰‰n tietyst‰ varastosta
			if (isset($varasto) and is_array($varasto) and $paikka == '') {

				if ($lisaarivi_debug == 1) echo "Halutaan myyd‰ varastoista ", var_dump($varasto).".<br>";

				foreach ($myy_tuoteno as $i => $tuoteno) {
					if (!in_array($myy_varasto[$i], $varasto) and $myy_varasto[$i] != "") {

						// jos halutaan myyd‰ erikoisvarastosta (josta ei myyd‰ automaattisesti eli tyyppi E), niin ei poisteta sit‰ arraysta
						if ($myy_erikoistoimitus[$i]) {
							continue;
						}

						if ($myy_tyyppi[$i] == "NORMAALI" or $myy_tyyppi[$i] == "TEHDASTILAUSTUOTE") {
							$saldoyhteensa -= $myy_kpl[$i];
						}
						else {
							$lapsiyhteensa -= $myy_kpl[$i];
						}

						unset($myy_tuoteno[$i]);
						unset($myy_kpl[$i]);
						unset($myy_hyllyalue[$i]);
						unset($myy_hyllynro[$i]);
						unset($myy_hyllyvali[$i]);
						unset($myy_hyllytaso[$i]);
						unset($myy_varasto[$i]);
						unset($myy_tyyppi[$i]);
						unset($myy_era[$i]);
					}
				}
			}

			foreach ($myy_tuoteno as $i => $tuoteno) {
				//T‰ss‰ loopataan vain is‰/normaleja tuotteita
				if ($lisaarivi_debug==1) echo "Loopataan $tuoteno, {$myy_tyyppi[$i]}, $toimittamatta<br>";

				if ($myy_tyyppi[$i] == "NORMAALI") {
					// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
					if ($toimittamatta > 0) {

						if ($lisaarivi_debug==1) echo "Normaalituote $tuoteno, toimittamatta $toimittamatta<br>";

						if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or $myy_kpl[$i] >= $toimittamatta) {
							$myy_kpl[$i] = $toimittamatta; // jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
						}

						if (isset($myy_kpl_myyty[$i]) and $myy_kpl[$i]) {
							$myy_kpl_myyty[$i] += $myy_kpl[$i];
						}

						// Status on lis‰t‰‰n is‰ ja perhe jos virheit‰ ei tule
						$perheok 	= 0;

						// Is‰ halutaan nyt ainakin myyd‰
						$myynama 	= array();
						$myynama[] 	= $i;

						if (count($tuoteperhe) > 0) {

							$riikoko 		= 1;
							$isat_array 	= array();
							$kaikki_array 	= array($tuoteno);
							$kerroin_array 	= array($tuoteno."#!°!#1");
							$perhearray 	= array();
							$ohita_kerays_array = array();

							for ($isa = 0; $isa < $riikoko; $isa++) {
								list($perherow, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array) = tuoteperhe_reku2($kaikki_array[$isa], $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array);
								$perhearray	= array_merge($perhearray, $perherow);

								if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
									$riikoko = count($kaikki_array);
								}
							}

							foreach ($perhearray as $perherow) {

								$lapsitoimittamatta = $perherow["kerroin"]*$myy_kpl[$i];

								foreach ($myy_tuoteno as $j => $lapsituoteno) {
									// Tuoteperheiss‰ voi olla samaa tuotetta monta kertaa, siksi and !isset($myy_hintakerr[$j]), muuten tuote on jo k‰sitelty...
									if (strtoupper($lapsituoteno) == strtoupper($perherow["tuoteno"]) and $myy_tyyppi[$j] == "LAPSITUOTE" and !isset($myy_hintakerr[$j])) {

										// laitetaan lasten kertoimet kuntoon
										$myy_hintakerr[$j] = $perherow["hintakerroin"];
										$myy_alekerroi[$j] = $perherow["alekerroin"];
										$myy_lapsi_ein[$j] = $perherow["ei_nayteta"];
										$myy_ohita_kerays[$j] = $perherow['ohita_kerays'];

										// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
										if ($lapsitoimittamatta > 0 and (($myy_kpl[$j] - $myy_kpl_myyty[$j]) > 0 or $yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI")) {

											if (($myy_kpl[$j] - $myy_kpl_myyty[$j] >= $lapsitoimittamatta) or $yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI") {
												// jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
												// ja splitataan loput omaan paikaan arrayhin jotta voidaan sit myyd‰ ne seuraavalla kiekalla jos on tarvis
												$myy_tuoteno[]	 = $myy_tuoteno[$j];
												$myy_kpl[]		 = $myy_kpl[$j] - $lapsitoimittamatta;
												$myy_hyllyalue[] = $myy_hyllyalue[$j];
												$myy_hyllynro[]	 = $myy_hyllynro[$j];
												$myy_hyllyvali[] = $myy_hyllyvali[$j];
												$myy_hyllytaso[] = $myy_hyllytaso[$j];
												$myy_varasto[]	 = $myy_varasto[$j];
												$myy_tyyppi[]	 = $myy_tyyppi[$j];
												$myy_era[] 	 	 = $myy_era[$j];

												$myy_kpl[$j] = $lapsitoimittamatta;
											}

											$myy_kpl_myyty[$j] += $myy_kpl[$j];

											$myynama[] = $j;

											// hoidellaan saldojen summailua kuntoon
											$lapsitoimittamatta -= $myy_kpl[$j];
										}
									}
								}

								if ($lapsitoimittamatta > 0) {
									//t‰m‰n lapsen saldo ei riit‰
									$perheok++;
								}
							}
						}

						//Jos homma on ok niin lis‰t‰‰n rivi
						if ($perheok == 0) {

							foreach($myynama as $indeksi) {
								//K‰sinsyˆtetyt hinnat ja alennukset eiv‰t p‰de korvaaviin tuotteisiin, eik‰ tuoteperheiden lapsiin
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"])) {
									$hinta	= "";

									for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
										${'ale'.$alepostfix} = "";
									}

									$netto	= "";
									$alv 	= "";
								}

								// Haetaan k‰sitelt‰v‰n tuotteen tiedot
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio='$kukarow[yhtio]' and tuoteno='$myy_tuoteno[$indeksi]'";
								$tres = pupe_query($query);
								$trow = mysql_fetch_assoc($tres);

								$ale_kaikki_array = array();

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									$ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
								}

								list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $myy_kpl[$indeksi], $netto, $hinta, $ale_kaikki_array, '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));

								list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);

								// jos ollaan laittamassa muuta tuotetta kun mit‰ tilattiin, lis‰t‰‰n rivikommentti
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"]) and $myy_tyyppi[$indeksi] == "NORMAALI") {

									// Haluan tarkistaa alkuper‰isen hinnan ennen korvausta, niin saadaan se halvempi hinta k‰yttˆˆn.
									if ($yhtiorow['korvaavan_hinta_ylaraja'] == 'MIN') {
										$lis_hinta = $korvaavan_hinta_ylaraja_halvin_hinta;
										$lis_ale_kaikki = $korvaavan_hinta_ylaraja_halvin_ale;
										$lis_netto = $korvaavan_hinta_ylaraja_halvin_netto;
									}

									// Tilausrivin systeemikommentti
									if (stripos($kommentti, t("HUOM: Korvaa tuotteen")." $alkupera_trow[tuoteno]") === FALSE) {
										$kommentti .= " ".t("HUOM: Korvaa tuotteen")." $alkupera_trow[tuoteno]";
									}

									// Kommentin lis‰kommentit, esim. vastaavista
									$kommentti .= "<br>". $kommentti_lisa;

									// oletetaan, ett‰ meill‰ on $edi_tyyppi muuttuja viritetty editilaus_in.inc failista
									if ((int) $rivinumero != 0 and $edi_tyyppi != "" and $korvaavan_isaa_myyty == 1) {
										$rivinumero = 0; // jos myyd‰‰n korvaava tuote ja ollaan myyty jo tilattuakin tuotetta, niin ei pistet‰ samaa rivinumeroa..
									}
								}

								if ($yhtiorow['huomautetaanko_poistuvasta'] == 'K' and ($trow['status'] == 'X' or $trow['status'] == 'P')) {
									// Tilausrivin systeemikommentti
									if (stripos($kommentti, t("HUOM:")." $alkupera_trow[tuoteno] ".t("on poistuva tuote!")) === FALSE) {
										$kommentti .= " ".t("HUOM:")." $alkupera_trow[tuoteno] ".t("on poistuva tuote!");
									}
								}

								if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
									$edi_ulos .= " --> ".t("Myyd‰‰n")." $myy_tuoteno[$indeksi] ".t("paikalta")." $myy_hyllyalue[$indeksi]-$myy_hyllynro[$indeksi]-$myy_hyllyvali[$indeksi]-$myy_hyllytaso[$indeksi]: $myy_kpl[$indeksi] ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

									for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
										$edi_ulos .= t("Ale")."$alepostfix ".$lis_ale_kaikki['ale'.$alepostfix]."% ";
									}

									$edi_ulos .= t("Hinta")." $lis_hinta $aperuste ".t("ALV")." $lis_alv% $rivikomm\n";
									if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
								}

								// Tilatut ja varatut kappaleet
								$tilattu = $myy_kpl[$indeksi];
								$varattu = $myy_kpl[$indeksi];

								if ($yhtiorow['myyntiera_pyoristys'] == 'K' and $kutsuja == "EDITILAUS" and $edi_tyyppi != "magento" and $myyntierahuomatus) {
									if (strpos($kommentti, t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on")) === FALSE) {
										$kommentti .= " ".t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on").": $trow[myynti_era]";
									}
								}

								// Jos hinta ja alekertoimet on setattu
								if (isset($myy_hintakerr[$indeksi])) {
									if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
										$lis_hinta = round($lis_hinta * $myy_hintakerr[$indeksi], 6);
									}
									else {
										$lis_hinta = round($lis_hinta * $myy_hintakerr[$indeksi], $yhtiorow['hintapyoristys']);
									}
								}

								if (isset($myy_alekerroi[$indeksi])) {
									if ((int) $myy_alekerroi[$indeksi] == -1) {

										for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
											$lis_ale_kaikki['ale'.$alepostfix] = 100.00;
										}

									}
									else {
										for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
											$lis_ale_kaikki['ale'.$alepostfix] = round($lis_ale_kaikki['ale'.$alepostfix] * $myy_alekerroi[$indeksi], $yhtiorow['hintapyoristys']);
										}
									}
								}

								// Jos ei varata saldoa niin ei varata saldoa
								if ($varataan_saldoa == "EI" and $rivityyppi == 'G') {
									$varattu = 0;
								}

								if ($yhtiorow['rivinumero_syotto'] != '' and $kutsuja != "EDITILAUS") {
									if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
										$query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
										$triviresult = pupe_query($query);
										$trivinrow = mysql_fetch_assoc($triviresult);

										$rivinumero = $trivinrow["trivinro"]+1;
										$tilrivinumero = $rivinumero;
									}
									else {
										$rivinumero = $tilrivinumero;
									}
								}

								// jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
								if (trim($tuotenimitys_force) != "") {
									$trow["nimitys"] = addslashes($tuotenimitys_force);
								}
								elseif ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and trim($tuotenimitys) != "") {
									// lis‰t‰‰n saatu nimitys kaikille tuotteille
									$trow["nimitys"] = addslashes($tuotenimitys);
								}

								// Onko tuotteella staattinen tilausrivikommentti
								if ($trow["tilausrivi_kommentti"] != "" and $rivitunnus == 0) {
									// Tilausrivin systeemikommentti
									if (stripos($kommentti, $trow["tilausrivi_kommentti"]) === FALSE) {
										if (trim($kommentti) != "")	$kommentti = "\n".$kommentti;
										$kommentti = trim($trow["tilausrivi_kommentti"].$kommentti);
									}
								}

								// jos meill‰ on vanha laadittuaika, niin k‰ytet‰‰ sit‰
								if ($rivilaadittu != "" and $rivitunnus != 0) {
									$vanharivilaadittu = "'$rivilaadittu'";
								}
								else {
									$vanharivilaadittu = "now()";
								}

								$query_ale_lrivi = "";

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									$query_ale_lrivi .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
								}

								$query_erikoisale_lisa = trim($lis_netto) != 'N' ? " erikoisale = '{$laskurow['erikoisale']}', " : '';

								$query = "	INSERT into tilausrivi set
											hyllyalue 		= '$myy_hyllyalue[$indeksi]',
											hyllynro 		= '$myy_hyllynro[$indeksi]',
											hyllytaso 		= '$myy_hyllytaso[$indeksi]',
											hyllyvali 		= '$myy_hyllyvali[$indeksi]',
											tilaajanrivinro = '$rivinumero',
											laatija 		= '$kukarow[kuka]',
											laadittu 		= $vanharivilaadittu,
											yhtio 			= '$kukarow[yhtio]',
											tuoteno 		= '$myy_tuoteno[$indeksi]',
											varattu 		= '$varattu',
											yksikko 		= '$trow[yksikko]',
											kpl 			= '0',
											kpl2			= '$kpl2',
											tilkpl 			= '$tilattu',
											{$query_ale_lrivi}
											{$query_erikoisale_lisa}
											alv 			= '$lis_alv',
											netto			= '$lis_netto',
											hinta 			= '$lis_hinta',
											kerayspvm 		= '$kerayspvm',
											otunnus 		= '$kukarow[kesken]',
											tyyppi 			= '$rivityyppi',
											toimaika 		= '$toimaika',
											kommentti 		= '$kommentti',
											var 			= '',
											try				= '$trow[try]',
											osasto			= '$trow[osasto]',
											perheid			= '$perheid',
											perheid2		= '$perheid2',
											tunnus 			= '$rivitunnus',
											nimitys 		= '$trow[nimitys]',
											jaksotettu		= '$jaksotettu'";

								if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
									$query .= ", keratty = 'saldoton', kerattyaika = now()";
								}

								if ($yhtiorow['reklamaation_hinnoittelu'] == 'K' and ($toim == "REKLAMAATIO"  or $toim == "EXTRANET_REKLAMAATIO") and $hyvityssaannon_palautuskielto == "X") {
									$query .= ", var2 = 'PK'";
								}

								$result = pupe_query($query);
								$lisatty_tun = mysql_insert_id();

								$trivtyrow = array("selite" => "");

								@include("trivityyppivalinta.inc");

								$query = "	SELECT *
											FROM tilausrivin_lisatiedot
											WHERE yhtio			 = '$kukarow[yhtio]'
											and tilausrivitunnus = '$lisatty_tun'";
								$lisatied_res = pupe_query($query);

								if (!isset($myy_erikoistoimitus[$indeksi])) {
									$myy_erikoistoimitus[$indeksi] = "";
								}

								if (!isset($myy_lapsi_ein[$indeksi])) {
									$myy_lapsi_ein[$indeksi] = "";
								}

								$tlisatied_lisa2 = "";

								if ($toim == "YLLAPITO") {
									$tlisatied_lisa2 .= ", sopimuksen_lisatieto1 = '".mysql_real_escape_string($sopimuksen_lisatieto1)."', sopimuksen_lisatieto2 = '".mysql_real_escape_string($sopimuksen_lisatieto2)."'";
								}

								$tlisatied_lisa2 .= in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) ? ", omalle_tilaukselle = '".mysql_real_escape_string($omalle_tilaukselle)."'" : '';

								if ($laskurow["tila"] == 'G' and isset($kohde_alue) and $kohde_alue != "") {
									$tlisatied_lisa2 .= ", kohde_hyllyalue='".mysql_real_escape_string($kohde_alue)."' , kohde_hyllynro='".mysql_real_escape_string($kohde_nro)."' , kohde_hyllyvali='".mysql_real_escape_string($kohde_vali)."' , kohde_hyllytaso='".mysql_real_escape_string($kohde_taso)."'";
								}

								if (mysql_num_rows($lisatied_res) > 0) {
									$lisatied_row = mysql_fetch_assoc($lisatied_res);

									$query = "	UPDATE tilausrivin_lisatiedot
												SET
												positio = if (positio = '', '$trivtyrow[selite]', positio),
												toimittajan_tunnus	= '$toimittajan_tunnus',
												muutospvm			= now(),
												muuttaja			= '$kukarow[kuka]',
												erikoistoimitus_myynti = '$myy_erikoistoimitus[$indeksi]',
												ohita_kerays = '{$myy_ohita_kerays[$indeksi]}'
												{$tlisatied_lisa}
												{$tlisatied_lisa2}
												WHERE yhtio			 = '$kukarow[yhtio]'
												and tilausrivitunnus = '$lisatty_tun'";
									$result = pupe_query($query);
								}
								else {
									if ($yhtiorow["tilauksen_jarjestys"] == "M") {
										$jarjestys = $lisatty_tun;
									}
									else {
										$jarjestys = 0;
									}

									$query = "	INSERT INTO tilausrivin_lisatiedot
												SET yhtio				= '$kukarow[yhtio]',
												positio 				= '$trivtyrow[selite]',
												toimittajan_tunnus		= '$toimittajan_tunnus',
												ei_nayteta 				= '$myy_lapsi_ein[$indeksi]',
												tilausrivitunnus		= '$lisatty_tun',
												erikoistoimitus_myynti 	= '$myy_erikoistoimitus[$indeksi]',
												ohita_kerays			= '{$myy_ohita_kerays[$indeksi]}',
												vanha_otunnus			= '$kukarow[kesken]',
												jarjestys				= '$jarjestys',
												luontiaika				= now(),
												laatija 				= '$kukarow[kuka]'
												{$tlisatied_lisa}
												{$tlisatied_lisa2}";

									if ($trow["sarjanumeroseuranta"] == "S" and $varattu < 0) {
										$query .= " , osto_vai_hyvitys='O' ";
									}

									$result = pupe_query($query);
								}

								$lisatyt_rivit1[] = $lisatty_tun;

								if ($myyntierahuomatus) {
									$myyntierahuom[] = $lisatty_tun;
								}

								if ($ostoerahuomatus) {
									$ostoerahuom[] = $lisatty_tun;
								}

								if ($myy_tyyppi[$indeksi] == "NORMAALI") {
									// lapsille oikea perheid
									$perheid_lisaarivi = $lisatty_tun;

									// hoidellaan saldojen summailua kuntoon
									$toimittamatta -= $myy_kpl[$indeksi];
								}

								if (($trow["sarjanumeroseuranta"] == "S" or $trow["sarjanumeroseuranta"] == "T" or $trow["sarjanumeroseuranta"] == "U" or $trow["sarjanumeroseuranta"] == "V") and $myy_tyyppi[$indeksi] == "NORMAALI" and $myy_sarjatunnus != '') {
									if ($varattu > 0) {
										$tunken = "myyntirivitunnus";
									}
									else {
										$tunken = "ostorivitunnus";
									}

									$query = "	SELECT sarjanumeroseuranta.tunnus, sarjanumeroseuranta.kaytetty
												FROM sarjanumeroseuranta
												LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.$tunken
												LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus
												WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
												and sarjanumeroseuranta.tunnus	IN ($myy_sarjatunnus)
												and sarjanumeroseuranta.tuoteno = '$myy_tuoteno[$indeksi]'
												and (sarjanumeroseuranta.myyntirivitunnus = 0 or lasku.tila='T')
												ORDER BY sarjanumeroseuranta.tunnus
												LIMIT ". (int) abs($varattu);
									$sres = pupe_query($query);

									while ($srow = mysql_fetch_assoc($sres)) {
										$query = "	UPDATE sarjanumeroseuranta
													SET $tunken 		 = '$lisatty_tun'
													WHERE yhtio			 = '$kukarow[yhtio]'
													and tunnus			 = '$srow[tunnus]'
													and tuoteno			 = '$myy_tuoteno[$indeksi]'";
										$sarjares = pupe_query($query);

										if ($srow["kaytetty"] == 'K') {
											$query = "	UPDATE tilausrivi
														SET alv=alv+500
														WHERE yhtio = '$kukarow[yhtio]'
														and tunnus = '$lisatty_tun'
														and alv < 500";
											$sarjares = pupe_query($query);
										}
									}

									$myy_sarjatunnus = "";
								}

								if ($rivityyppi != "T" and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $myy_era[$indeksi] != "") {

									if ($varattu > 0) {
										$tunken = "myyntirivitunnus";

										$query = "	SELECT *
													FROM sarjanumeroseuranta
													WHERE yhtio		= '$kukarow[yhtio]'
													and tuoteno		= '$myy_tuoteno[$indeksi]'
													and hyllyalue	= '$myy_hyllyalue[$indeksi]'
													and hyllynro 	= '$myy_hyllynro[$indeksi]'
													and hyllytaso 	= '$myy_hyllytaso[$indeksi]'
													and hyllyvali 	= '$myy_hyllyvali[$indeksi]'
													and sarjanumero = '$myy_era[$indeksi]'";
										$lisa_res = pupe_query($query);
										$lisa_row = mysql_fetch_assoc($lisa_res);

										$oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
									}
									else {
										$tunken = "ostorivitunnus";
										$oslisa = "";
									}

									$query = "	INSERT into sarjanumeroseuranta
												SET yhtio 		= '$kukarow[yhtio]',
												tuoteno			= '$myy_tuoteno[$indeksi]',
												lisatieto 		= '$lisa_row[lisatieto]',
												$tunken 		= '$lisatty_tun',
												$oslisa
												kaytetty		= '$lisa_row[kaytetty]',
												era_kpl			= '',
												laatija			= '$kukarow[kuka]',
												luontiaika		= now(),
												takuu_alku 		= '$lisa_row[takuu_alku]',
												takuu_loppu		= '$lisa_row[takuu_loppu]',
												parasta_ennen	= '$lisa_row[parasta_ennen]',
												hyllyalue 		= '$myy_hyllyalue[$indeksi]',
												hyllynro 		= '$myy_hyllynro[$indeksi]',
												hyllytaso 		= '$myy_hyllytaso[$indeksi]',
												hyllyvali 		= '$myy_hyllyvali[$indeksi]',
												sarjanumero 	= '$myy_era[$indeksi]'";
									$lisa_res = pupe_query($query);
								}

								if ($myy_tyyppi[$indeksi] == "LAPSITUOTE") {
									//P‰ivitet‰‰n is‰n perhetunnus
									if ($rivityyppi == 'V') {

										$query = "	SELECT tuoteno
													FROM tilausrivi
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus	= $perheid_lisaarivi
													and tyyppi	= 'V'";
										$lisa_res = pupe_query($query);
										$lisa_row = mysql_fetch_assoc($lisa_res);

										if ($lisatied_row["porauskuvio"] == "M" or (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"]))) {
											$valmistetyyppi = "M";

											$query = "	UPDATE tilausrivin_lisatiedot
														SET porauskuvio	= '$valmistetyyppi'
														WHERE yhtio			 = '$kukarow[yhtio]'
														and tilausrivitunnus = '$perheid_lisaarivi'";
											$result = pupe_query($query);
										}
										else {
											$valmistetyyppi = "W";
										}

										//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='$valmistetyyppi' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and tyyppi='V'";
										$result = pupe_query($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
										$result = pupe_query($query);
									}
									else {
										//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
										$query = "UPDATE tilausrivi SET hyllyalue='$myy_hyllyalue[$indeksi]' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
										$result = pupe_query($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
										$result = pupe_query($query);
									}
								}

								$rivitunnus			= 0;
								$kommentti 			= "";
								$tuotenimitys 		= "";
								$tuotenimitys_force = "";
								$omalle_tilaukselle = "";

							} // T‰ll‰ rundilla myydyt
						} // Jos tuoteperhett‰ lˆytyi riitt‰v‰sti tai myytiin pelk‰‰ is‰‰
					} // Jos vil‰ on toimittamatta kappaleita
				} // end if toimittamatta > 0
			} // end foreach myy_tuoteno
		} // end saadaanko myyd‰ osatoimitus
	}

	// jos tultiin ulos loopista, ett‰ meill‰ on toimittamattomia rivej‰, tehd‰‰n niist‰ puuterivi
	// tai jos tullaan niin, ett‰ var-kent‰ss‰ on J tai P tai jotain
	if ($toimittamatta > 0 or trim($var) != '' or $rivityyppi == 'O') {

		if ($lisaarivi_debug==1) echo "<br>Menn‰‰n puutehaaraan: $tuoteno hinta $hinta toimittamatta: $toimittamatta var: $var laskuntila: $laskurow[tila]<br>";

		//Tyhjennet‰‰n apumuuttuja
		if ($var == "Y")	{
			$var = "";
		}

		$puu_tuoteno 	= array();
		$puu_kpl 		= array();
		$puu_hintake	= array();
		$puu_alekerr	= array();
		$puu_ein 		= array();
		$puu_var		= array();

		if ($rivityyppi == 'O' and $rivitunnus > 0 and isset($suoratoimitus_rivitun) and $suoratoimitus_rivitun > 0) {
			// Kappalem‰‰r‰‰ ei voida lis‰t‰
			if ($toimittamatta >= $suoratoimitus_rivikpl) {
				$toimittamatta = $suoratoimitus_rivikpl;
			}
			else {
				// Pienennt‰‰n suoratoimitusostorivin m‰‰r‰‰, eli joudumme splittaamaan myyntirivin samassa suhteessa
				$suoratoimitus_splittaus = TRUE;
				// Pakotetaan loput omalle riville
				$var = "LOJ#!°!#$suoratoimitus_rivikpl";
			}
		}

		if (substr($var,0,8) == "LOJ#!°!#" or substr($var,0,8) == "LOP#!°!#" or substr($lop,0,8) == "LOP#!°!#") {

			if ($lisaarivi_debug == 1) echo "Laitetaan loput rivit jtksi tai puutteeksi (".substr($var,0,4).") (".substr($lop,0,4).")<br>";

			// Lis‰t‰‰n syˆtetyt ja laitetaan loput JT:seen tai puutteiksi
			$puu_tuoteno[] 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
			$puu_kpl[] 		= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_ein[]		= "";
			$puu_var[]		= $var;

			if (substr($var,0,8) == "LOJ#!°!#" and substr($var, 8) - $toimittamatta > 0) {
				$puu_tuoteno[] 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
				$puu_kpl[] 		= substr($var, 8) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_ein[]		= "";
				$puu_var[]		= "";
			}
			elseif (substr($lop,0,8) == "LOP#!°!#" and substr($lop, 8) - $toimittamatta > 0) {
				$puu_tuoteno[] 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
				$puu_kpl[] 		= substr($lop, 8) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_ein[]		= "";
				$puu_var[]		= "P";
			}
		}
		else {
			if ($lisaarivi_debug == 1) echo "Kaikki puutteeksi var $var<br>";

			$puu_tuoteno[] 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
			$puu_kpl[]	 	= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_ein[]		= "";
			$puu_var[]		= $var;
		}

		if ($rivityyppi != 'O' and $perheid == 0 and $perhekielto == "") {

			$riikoko 		= 1;
			$isat_array 	= array();
			$kaikki_array 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? array($uusin_korvaava_tuote) : array($alkupera_trow["tuoteno"]);
			$kerroin_array 	= (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? array($uusin_korvaava_tuote."#!°!#1") : array($alkupera_trow["tuoteno"]."#!°!#1");
			$perhearray 	= array();
			$ohita_kerays_array = array();

			for ($isa=0; $isa < $riikoko; $isa++) {
				list($perherow, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array) = tuoteperhe_reku2($kaikki_array[$isa], $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array);
				$perhearray	= array_merge($perhearray, $perherow);

				// Ollaanko rekursiivisia vai ei
				if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
					$riikoko = count($kaikki_array);
				}
			}

			if (count($perhearray) == 0 and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V' and $rivityyppi == "V") {
				// Asiakkaallevalmistuskeissi, resepit‰ ei lˆydy joten teemme t‰st‰ myyntirivin
				$rivityyppi = 'L'; // Myyntitilausrivi
			}

			foreach ($perhearray as $perherow) {
				$puu_tuoteno[]	= $perherow["tuoteno"];
				$puu_kpl[] 		= $toimittamatta*$perherow["kerroin"];
				$puu_hintake[]	= $perherow["hintakerroin"];
				$puu_alekerr[]	= $perherow["alekerroin"];
				$puu_ein[] 		= $perherow["ei_nayteta"];
				$puu_var[]		= $var;
			}
		}

		foreach ($puu_tuoteno as $i => $tuoteno) {
			$kpl = $puu_kpl[$i];
			$var = $puu_var[$i];

			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno = '$tuoteno' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = pupe_query($query);

			if (mysql_num_rows($tuoteresult) == 0) {
				// tuotetta ei lˆydy, lis‰t‰‰n silti tuoteno kantaan?
				$trow["tuoteno"] = $kaikkituotteet[0];
			}
			else {
				$trow = mysql_fetch_assoc($tuoteresult);
			}

			if ($yhtiorow['rivinumero_syotto'] != '') {
				if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
					$query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
					$triviresult = pupe_query($query);
					$trivinrow = mysql_fetch_assoc($triviresult);

					$rivinumero = $trivinrow["trivinro"]+1;
				}
				else {
					$rivinumero = $tilrivinumero;
				}
			}

			// Jos ollaan lis‰‰m‰ss‰ alkuper‰ist‰ tuotetta niin otetaan takaisin k‰ytt‰j‰n syˆtetyt alkuper‰iset hinnat
			if ($rivityyppi != 'O' and strtoupper($trow["tuoteno"]) == strtoupper($alkupera_trow["tuoteno"])) {
				$hinta	= $alkupera_hinta;

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = ${'alkupera_ale'.$alepostfix};
				}

				$netto	= $alkupera_netto;
				$alv	= $alkupera_alv;
			}
			elseif ($rivityyppi != 'O' and $i > 0) {
				//K‰sinsyˆteyt hinnat nollataan kun lis‰t‰‰n tuoteperheiden lapsia
				$hinta	= "";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = "";
				}

				$netto	= "";
				$alv	= "";

				if ($var != "S") {
					$rivinumero = "";
				}
			}

			if ($rivityyppi == 'O') {
				$ale_kaikki_array = array();

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					$ale_kaikki_array['ale'.$alepostfix] = ${'alkupera_ale'.$alepostfix};
				}

				list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_val) = alehinta_osto($laskurow, $trow, $kpl, "", $hinta, $ale_kaikki_array);
			}

			// jos k‰ytˆss‰ on hintakertoimet, niin muutetaan yksikkˆ kuntoon
			if ($rivityyppi == 'O' and $trow["myyntihinta_maara"] != 0 and $lis_hinta != 0) {
				$lis_hinta = round($lis_hinta / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
			}

			if ($rivityyppi != 'O') {
				$ale_kaikki_array = array();

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
				}

				if ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
					$alemaara = 99999999999;
				}
				else {
					$alemaara = $kpl;
				}

				list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $alemaara, $netto, $hinta, $ale_kaikki_array,'','', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));
				list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);
			}

			if ($rivityyppi != 'O') {
				switch ($var) {
					case 'J' : // J‰lkitoimitukseen
					case 'R' : // Valmistetaan asiakkaalle
					case 'U' : // Tilataan toimittajalta asiakkaalle
					case 'T' : // Tilataan toimittajalta
						// Yhtiˆn parametri: varaako JT-rivi saldoja
						if ($yhtiorow["varaako_jt_saldoa"] == "") {
							$varattu 	= 0;
							$jt 		= $kpl;
						}
						else {
							$varattu 	= $kpl;
							$jt 		= 0;
						}
						break;
					case 'P' : // Puute
						$varattu 	= 0;
						$jt 		= 0;
						break;
					case 'H' : // Pakkohyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'O' : // Ookooaus-hyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'V' : // Tehdaslis‰varuste
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case '' :
						$varattu 	= 0;
						$jt 		= 0;

						// Sirtolistoissa defaultti on JT jos saldo ei riit‰. (jos yhtio ei v‰lit‰ saldoista ei v‰litet‰ t‰‰ll‰k‰‰n..)
						// Tai jos yhtiˆlle on konffattu, ett‰ se haluaa ylimemen‰t jteiksi
						// Sis‰isiss‰ tyˆm‰‰r‰ykiss‰ ei haluta JT-rivej‰
						if ((($rivityyppi == 'G' and $yhtiorow["puute_jt_oletus"] != "H") or (($yhtiorow["puute_jt_oletus"] == "J" or $yhtiorow["puute_jt_oletus"] == "B") and $kpl > 0)) and $laskurow["tila"] != 'S' and $jtkielto != "o") {
							if ($yhtiorow["varaako_jt_saldoa"] == "") {
								$varattu 	= 0;
								$jt 		= $kpl;
							}
							else {
								$varattu 	= $kpl;
								$jt 		= 0;
							}

							$var	= "J";
						}
						// Terminaalitoimitukset tai ostoskorit; var tyhj‰‰ ja varattu normisti
						elseif ($rivityyppi == 'Z' or $rivityyppi == 'B' or $rivityyppi == 'E' or $rivityyppi == 'F') {
							$var 	= "";
							$varattu = $kpl;
						}
						// Halutaan myyd‰ saldoista juurikaan mit‰‰n v‰litt‰m‰tt‰!
						elseif ($yhtiorow["puute_jt_oletus"] == "H") {
							$var 	= "";
							$varattu = $kpl;
						}
						// Hyvitysrivit menee normaaleina
						elseif ($kpl < 0) {
							$var 	= "";
							$varattu = $kpl;
						}
						// Normaalisti kaikki menee puutteeksi.
						else {
							$var 	= "P";
						}
						break;
					default :
						echo t("VIRHE: Virheellinen arvo kent‰ss‰ hyvaksy")." '$var'";
				}

				if (trim($var) == 'P') {

					// Onko customi uutekommenttia
					$puurivires = t_avainsana("PUUTEKOMM");

					if ($kutsuja == "EDITILAUS" and $trow['status'] == 'T') {
						if (stripos($kommentti, t("Tilaustuote.")) === FALSE) {
							// Tilausrivin systeemikommentti
							$kommentti .= " ".t("Tilaustuote.");
						}
					}
					elseif (mysql_num_rows($puurivires) > 0) {

						$puurivirow = mysql_fetch_assoc($puurivires);

						if (stripos($kommentti, $puurivirow["selite"]) === FALSE) {
							// Tilausrivin systeemikommentti
							$kommentti .= " ".$puurivirow["selite"];
						}
					}
					elseif ($kutsuja == "EDITILAUS") {
						if (stripos($kommentti, t("Tuote Loppu.")) === FALSE) {
							// Tilausrivin systeemikommentti
							$kommentti .= " ".t("Tuote Loppu.");
						}
					}
				}
			}
			else {
				$var 		= "";
				$varattu 	= $kpl;
			}

			$p_hyllyalue	= "";
			$p_hyllynro		= "";
			$p_hyllyvali	= "";
			$p_hyllytaso	= "";

			if ($paikka != '') {
				//K‰ytt‰j‰n syˆtt‰m‰ paikka
				$paikat = explode('#!°!#',$paikka);

				$p_hyllyalue	= $paikat[0];
				$p_hyllynro		= $paikat[1];
				$p_hyllyvali	= $paikat[2];
				$p_hyllytaso	= $paikat[3];
			}
			else {
				// haetaan viel‰ tuotteen oletuspaikka puuteriville, jotta seuranta ois helpompaa
				//jos laskurow[varasto] on setattu niin yritet‰‰n lˆyt‰‰ paikka siit‰ varastosta
				$query = "	SELECT tuotepaikat.*, varastopaikat.tunnus varastotunnus
							FROM tuotepaikat
							LEFT JOIN varastopaikat ON tuotepaikat.yhtio = varastopaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue) ,5,'0'),lpad(upper(alkuhyllynro) ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							where tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = '$trow[tuoteno]'
							ORDER BY tuotepaikat.oletus DESC";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {

					// Yritet‰‰n lˆyt‰‰ paras varastopaikka
					$loydetytpaikat = FALSE;

					// lˆytyykˆ syˆtetty $varasto (muttujassa tulee myˆs $laskurow[varasto] tai $kukarow["varasto"] jos $varastoa ei olla eksplisiittisesti syˆtetty)?
					if (isset($varasto) and is_array($varasto)) {
						while ($olerow = mysql_fetch_assoc($result)) {
							if (in_array($olerow["varastotunnus"], $varasto)) {
								$p_hyllyalue	= $olerow["hyllyalue"];
								$p_hyllynro		= $olerow["hyllynro"];
								$p_hyllyvali	= $olerow["hyllyvali"];
								$p_hyllytaso	= $olerow["hyllytaso"];

								$loydetytpaikat = TRUE;
								break;
							}
						}
					}

					// lˆytyykˆ $kukarow["oletus_varasto"]?
					if (!$loydetytpaikat and $kukarow["oletus_varasto"] > 0) {
						mysql_data_seek($result,0);

						while ($olerow = mysql_fetch_assoc($result)) {
							if ($olerow["varastotunnus"] == $kukarow["oletus_varasto"]) {
								$p_hyllyalue	= $olerow["hyllyalue"];
								$p_hyllynro		= $olerow["hyllynro"];
								$p_hyllyvali	= $olerow["hyllyvali"];
								$p_hyllytaso	= $olerow["hyllytaso"];

								$loydetytpaikat = TRUE;
								break;
							}
						}
					}

					//Jos ei lˆytynyt niin myyd‰‰n oletuspaikalta
					if (!$loydetytpaikat) {
						mysql_data_seek($result,0);
						$olerow = mysql_fetch_assoc($result);

						$p_hyllyalue	= $olerow["hyllyalue"];
						$p_hyllynro		= $olerow["hyllynro"];
						$p_hyllyvali	= $olerow["hyllyvali"];
						$p_hyllytaso	= $olerow["hyllytaso"];
					}
				}
			}

			if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
				if ($var == "J") {
					$edi_ulos .= " --> ".t("JT")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$edi_ulos .= t("Ale")."{$alepostfix} ".$lis_ale_kaikki['ale'.$alepostfix]." ";
					}

					$edi_ulos .= t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
				}
				elseif ($var == "P") {
					$edi_ulos .= " --> ".t("PUUTE")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$edi_ulos .= t("Ale")."{$alepostfix} ".$lis_ale_kaikki['ale'.$alepostfix]." ";
					}

					$edi_ulos .= t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
				}

				if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
			}


			// Jos hinta ja alekertoimet on setattu
			if ($puu_hintake[$i] != "X") {
				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					$lis_hinta = round($lis_hinta * $puu_hintake[$i], 6);
				}
				else {
					$lis_hinta = round($lis_hinta * $puu_hintake[$i], $yhtiorow['hintapyoristys']);
				}
			}

			if ($puu_alekerr[$i] != "X") {
				if ((int) $puu_alekerr[$i] == -1) {
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$lis_ale_kaikki['ale'.$alepostfix] = 100.00;
					}

				}
				else {
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$lis_ale_kaikki['ale'.$alepostfix] = round($lis_ale_kaikki['ale'.$alepostfix] * $puu_alekerr[$i], $yhtiorow['hintapyoristys']);
					}
				}
			}

			if (!is_array($korkieltokommi) and $korkieltokommi != '') {
				// Tilausrivin systeemikommentti
				if (stripos($kommentti, $korkieltokommi) === FALSE) {
					$kommentti .= " ".$korkieltokommi;
				}
			}

			$trow["tuoteno"] = strtoupper($trow["tuoteno"]);

			// jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
			if (trim($tuotenimitys_force) != "") {
				$trow["nimitys"] = addslashes($tuotenimitys_force);
			}
			elseif (($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" or $toim == "HAAMU" or $trow["tuoteno"] == "99") and trim($tuotenimitys) != "") {
				// lis‰t‰‰n saatu nimitys kaikille tuotteille
				$trow["nimitys"] = addslashes($tuotenimitys);
			}

			// Onko tuotteella staattinen tilausrivikommentti
			if ($trow["tilausrivi_kommentti"] != "" and $rivitunnus == 0) {
				// Tilausrivin systeemikommentti
				if (stripos($kommentti, $trow["tilausrivi_kommentti"]) === FALSE) {
					if (trim($kommentti) != "")	$kommentti = "\n".$kommentti;
					$kommentti = trim($trow["tilausrivi_kommentti"].$kommentti);
				}
			}

			if ($rivitunnus == 0 and ($trow["status"] == "P" or $trow["status"] == "X")) {
				// Tilausrivin systeemikommentti
				if (stripos($kommentti, t("Poistunut tuote")) === FALSE) {
					if (trim($kommentti) != "")	$kommentti .= "\n";
					$kommentti .= t("Poistunut tuote");
				}
			}

			// Jos ollaan tultu editilauksesta ja meill‰ on puute/jt, katsotaan onko t‰lle tuotteelle vastaavia ja pistet‰‰n ne kommenttiin
			if ($kutsuja == "EDITILAUS" and ($var == "J" or $var == "P")) {
				//vastaavat tuotteet
				$query  = "	SELECT *
							FROM vastaavat
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$trow[tuoteno]'
							LIMIT 1";
				$ediresult = pupe_query($query);

				if (mysql_num_rows($ediresult) > 0) {

					$edivastaavattuotteet = "";
					$vrow = mysql_fetch_assoc($ediresult);

					$query = "	SELECT *
								FROM vastaavat
								WHERE yhtio = '$kukarow[yhtio]'
								AND id = '$vrow[id]'
								AND tuoteno <> '$trow[tuoteno]'
								ORDER BY jarjestys, tuoteno";
					$edi2result = pupe_query($query);

					while ($edirow = mysql_fetch_assoc($edi2result)) {

						list(, , $vmyytavissa) = saldo_myytavissa($edirow["tuoteno"]);

						if ($vmyytavissa > 0) {
							$tvsql = "	SELECT *
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$edirow[tuoteno]'";
							$tvresult = pupe_query($tvsql);
							$tvrow = mysql_fetch_assoc($tvresult);
							$edivastaavattuotteet .= "$edirow[tuoteno] (".hintapyoristys($tvrow["myyntihinta"])." $laskurow[valkoodi] $vmyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")."), ";
						}
					}

					if ($edivastaavattuotteet != "") {

						if ($kommentti != "") {
							 $edivastaavattuotteet_alku = "\n";
						}
						else {
							$edivastaavattuotteet_alku = "";
						}

						$edivastaavattuotteet = $edivastaavattuotteet_alku.t("Voit tilata vastaavaa tuotetta")." ".$edivastaavattuotteet;

						// poistetaan viimeinen pilkku ja lis‰t‰‰n piste siisteyden lis‰‰miseksi.
						$kommentti .= substr($edivastaavattuotteet, 0, -2).".";
					}
				}
			}

			if ($yhtiorow['myyntiera_pyoristys'] == 'K' and $kutsuja == "EDITILAUS" and $edi_tyyppi != "magento" and $myyntierahuomatus) {
				if (strpos($kommentti, t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on")) === FALSE) {
					$kommentti .= " ".t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on").": $trow[myynti_era]";
				}
			}

			// jos meill‰ on vanha laadittuaikaa, niin k‰ytet‰‰ sit‰
			if ($rivilaadittu != "" and $rivitunnus != 0) {
				$vanharivilaadittu = "'$rivilaadittu'";
			}
			else {
				$vanharivilaadittu = "now()";
			}

			if ($var == "U" or $var == "T") {
				$p_hyllyalue	= "";
				$p_hyllynro		= "";
				$p_hyllynro		= "";
				$p_hyllytaso	= "";
			}

			// jos tuote on poistettu niin sit‰ ei voi laittaa j‰lkitoimitukseen
			if ($var == 'J' and ($trow["status"] == "P" or $trow["status"] == "X")) {
				$var = 'P';
				$varattu = 0;
			}

			$query_insert_ale_lisa = '';

			if ($rivityyppi == 'O') {
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					$query_insert_ale_lisa .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
				}
			}
			else {
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$query_insert_ale_lisa .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
				}
			}

			####
			if ($yhtiorow["korvaavusketjun_puutekasittely"] == "K") {
				$query = "SELECT id
			              FROM korvaavat
			              WHERE yhtio = '{$kukarow['yhtio']}'
			              AND tuoteno = '{$trow['tuoteno']}'
			              order by if(korvaavat.jarjestys = 0, 9999, korvaavat.jarjestys), korvaavat.tuoteno
			              limit 1";
		        $presult = pupe_query($query);
		        $prow = mysql_fetch_assoc($presult);

		        $query = "SELECT *
			              FROM korvaavat
			              JOIN tuote ON korvaavat.yhtio=tuote.yhtio AND korvaavat.tuoteno=tuote.tuoteno
			              WHERE korvaavat.yhtio = '{$kukarow['yhtio']}'
			              AND id = '{$prow['id']}'
			              order by if(korvaavat.jarjestys = 0, 9999, korvaavat.jarjestys), korvaavat.tuoteno
			              limit 1";
		        $tresult = pupe_query($query);
		        $korvaava_row = mysql_fetch_assoc($tresult);

		        $kommentti .= " HUOM! Korvaa tuotteen {$alkupera_trow['tuoteno']}";

		        $trow = $korvaava_row;
			}
			####

			$query_erikoisale_lisa = trim($lis_netto) != 'N' ? " erikoisale = '{$laskurow['erikoisale']}', " : '';

			// lis‰t‰‰n puuterivi
			$query = "	INSERT into tilausrivi set
						hyllyalue 		= '$p_hyllyalue',
						hyllynro 		= '$p_hyllynro',
						hyllyvali 		= '$p_hyllyvali',
						hyllytaso 		= '$p_hyllytaso',
						tilaajanrivinro = '$rivinumero',
						laatija 		= '$kukarow[kuka]',
						laadittu 		= $vanharivilaadittu,
						yhtio 			= '$kukarow[yhtio]',
						tuoteno 		= '$trow[tuoteno]',
						varattu 		= '$varattu',
						yksikko 		= '$trow[yksikko]',
						kpl 			= '0',
						kpl2			= '$kpl2',
						tilkpl 			= '$kpl',
						jt				= '$jt',
						{$query_insert_ale_lisa}
						{$query_erikoisale_lisa}
						alv 			= '$lis_alv',
						netto			= '$lis_netto',
						hinta 			= '$lis_hinta',
						kerayspvm 		= '$kerayspvm',
						otunnus 		= '$kukarow[kesken]',
						tyyppi 			= '$rivityyppi',
						toimaika 		= '$toimaika',
						kommentti 		= '$kommentti',
						var 			= '$var',
						try				= '$trow[try]',
						osasto			= '$trow[osasto]',
						perheid			= '$perheid',
						perheid2		= '$perheid2',
						tunnus 			= '$rivitunnus',
						nimitys 		= '$trow[nimitys]',
						jaksotettu		= '$jaksotettu'";

			if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
				$query .= ", kerattyaika = now()";
			}

			if ($yhtiorow['reklamaation_hinnoittelu'] == 'K' and ($toim == "REKLAMAATIO"  or $toim == "EXTRANET_REKLAMAATIO") and $hyvityssaannon_palautuskielto == "X") {
				$query .= ", var2 = 'PK'";
			}

			$result = pupe_query($query);
			$lisatty_tun = mysql_insert_id();

			if ($suoratoimitus_splittaus and $rivitunnus == $lisatty_tun) {
				// P‰ivitet‰‰n suoratoimitus-myyntitilausrivin m‰‰r‰t
				$query = "	UPDATE tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.tilausrivilinkki = '$lisatty_tun')
							SET tilausrivi.tilkpl	= '$varattu',
							tilausrivi.varattu 		= if(tilausrivi.varattu != 0, $varattu, 0),
							tilausrivi.jt 			= if(tilausrivi.jt != 0, $varattu, 0)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tunnus = '$suoratoimitus_rivitun'";
				$result = pupe_query($query);
			}
			elseif ($suoratoimitus_splittaus and $rivitunnus != $lisatty_tun) {
				// Splitataan suoratoimitus-myyntitilausrivi
				$query = "	SELECT varattu, jt
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$suoratoimitus_rivitun'";
				$result = pupe_query($query);
				$suora_row = mysql_fetch_assoc($result);

				if ($suora_row["jt"] != 0) {
					$pkentat = array("tilausrivi.tilkpl"=> $varattu, "tilausrivi.jt"=> $varattu, "tilausrivin_lisatiedot.tilausrivilinkki"=> $lisatty_tun);
				}
				else {
					$pkentat = array("tilausrivi.tilkpl"=> $varattu, "tilausrivi.varattu"=> $varattu, "tilausrivin_lisatiedot.tilausrivilinkki"=> $lisatty_tun);
				}

				$uusi_suoratoimrivi = kopioi_tilausrivi($suoratoimitus_rivitun, $pkentat);
				$suoratoimitus_splittaus = FALSE;
			}

			$trivtyrow = array("selite" => "");
			@include("trivityyppivalinta.inc");

			$query = "	SELECT *
						FROM tilausrivin_lisatiedot
						WHERE yhtio			 = '$kukarow[yhtio]'
						and tilausrivitunnus = '$lisatty_tun'";
			$lisatied_res = pupe_query($query);

			$tlisatied_lisa2 = "";

			if ($toim == "YLLAPITO") {
				$tlisatied_lisa2 .= ", sopimuksen_lisatieto1 = '$sopimuksen_lisatieto1', sopimuksen_lisatieto2 = '$sopimuksen_lisatieto2'";
			}

			$tlisatied_lisa2 .= in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) ? ", omalle_tilaukselle = '".mysql_real_escape_string($omalle_tilaukselle)."'" : '';

			if ($laskurow["tila"] == 'G' and isset($kohde_alue) and $kohde_alue != "") {
				$tlisatied_lisa2 .= ", kohde_hyllyalue='$kohde_alue' , kohde_hyllynro='$kohde_nro' , kohde_hyllyvali='$kohde_vali' , kohde_hyllytaso='$kohde_taso'";
			}

			if (mysql_num_rows($lisatied_res) > 0) {
				$lisatied_row = mysql_fetch_assoc($lisatied_res);

				$query = "	UPDATE tilausrivin_lisatiedot
							SET
							positio = if (positio = '', '$trivtyrow[selite]', positio),
							toimittajan_tunnus	= '$toimittajan_tunnus',
							muutospvm			= now(),
							muuttaja			= '$kukarow[kuka]'
							{$tlisatied_lisa}
							{$tlisatied_lisa2}
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tilausrivitunnus = '$lisatty_tun'";
				$result = pupe_query($query);
			}
			else {
				if ($yhtiorow["tilauksen_jarjestys"] == "M") {
					$jarjestys = $lisatty_tun;
				}
				else {
					$jarjestys = 0;
				}
				$query = "	INSERT INTO tilausrivin_lisatiedot
							SET yhtio			= '$kukarow[yhtio]',
							positio 			= '$trivtyrow[selite]',
							toimittajan_tunnus	= '$toimittajan_tunnus',
							tilausrivitunnus	= '$lisatty_tun',
							jarjestys			= '$jarjestys',
							vanha_otunnus		= '$kukarow[kesken]',
							ei_nayteta			= '$puu_ein[$i]',
							luontiaika			= now(),
							laatija 			= '$kukarow[kuka]'
							{$tlisatied_lisa}
							{$tlisatied_lisa2}";

				if ($trow["sarjanumeroseuranta"] == "S" and $kpl < 0) {
					$query .= " , osto_vai_hyvitys='O' ";
				}

				$result = pupe_query($query);
			}

			if ($var == "H") {
				$lisatyt_rivit1[] = $lisatty_tun;
			}
			else {
				$lisatyt_rivit2[] = $lisatty_tun;
			}

			if ($myyntierahuomatus) {
				$myyntierahuom[] = $lisatty_tun;
			}
			if ($ostoerahuomatus) {
				$ostoerahuom[] = $lisatty_tun;
			}

			if ($i == 0) {
				// lapsille oikea perheid
				$perheid_lisaarivi = $lisatty_tun;

				if ($varattu+$jt != 0 and ($myy_sarjatunnus != "" or $osto_sarjatunnus != "") and strtoupper($trow["tuoteno"]) == strtoupper($alkupera_trow["tuoteno"])) {

					if ($osto_sarjatunnus != "" or $varattu+$jt < 0) {
						$tunken = "ostorivitunnus";

						if ($osto_sarjatunnus != "") {
							$myy_sarjatunnus = $osto_sarjatunnus;
						}
					}
					else {
						$tunken = "myyntirivitunnus";
					}

					$query = "	SELECT yhtio, lisatieto, sarjanumero, kaytetty, tunnus
								FROM sarjanumeroseuranta
								WHERE tunnus 	IN ($myy_sarjatunnus)
								and tuoteno		= '$trow[tuoteno]'
								ORDER BY tunnus
								LIMIT ". (int) abs($varattu+$jt);
					$sres = pupe_query($query);

					while ($srow = mysql_fetch_assoc($sres)) {
						if ($var == "S") {
							// jos syˆtet‰‰n sarjanumertollinen tuote suoratoimitukseen niin liitet‰‰n t‰m‰ myyntirivi siihen kiinni
							$query = "	INSERT INTO sarjanumeroseuranta
										SET
										yhtio				= '$kukarow[yhtio]',
										tuoteno				= '$trow[tuoteno]',
										lisatieto			= 'Suoratoimitetaan yhtiˆst‰: $srow[yhtio]. $srow[lisatieto]',
										sarjanumero			= '$srow[sarjanumero]',
										kaytetty			= '$srow[kaytetty]',
										myyntirivitunnus 	= '$lisatty_tun'";
							$sarjares = pupe_query($query);
						}
						else {
							$query = "	UPDATE sarjanumeroseuranta
										SET $tunken 		 = '$lisatty_tun'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tunnus			 = '$srow[tunnus]'
										and tuoteno			 = '$trow[tuoteno]'
										and $tunken 		 = 0";
							$sarjares = pupe_query($query);
						}


						if ($srow["kaytetty"] == 'K') {
							$query = "	UPDATE tilausrivi
										SET alv=alv+500
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus = '$lisatty_tun'
										and alv < 500";
							$sarjares = pupe_query($query);
						}
					}

					$osto_sarjatunnus = "";
					$myy_sarjatunnus = "";
				}
			}

			if (count($puu_tuoteno) > 1 and $rivityyppi != 'O' and $lop == "") {
				//P‰ivitet‰‰n is‰n perhetunnus
				if ($rivityyppi == 'V') {
					$query = "	SELECT tuoteno
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= $perheid_lisaarivi
								and tyyppi	= 'V'";
					$lisa_res = pupe_query($query);
					$lisa_row = mysql_fetch_assoc($lisa_res);

					if ($lisatied_row["porauskuvio"] == "M" or (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"]))) {
						$valmistetyyppi = "M";

						$query = "	UPDATE tilausrivin_lisatiedot
									SET porauskuvio	= '$valmistetyyppi'
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tilausrivitunnus = '$perheid_lisaarivi'";
						$result = pupe_query($query);
					}
					else {
						$valmistetyyppi = "W";
					}

					//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='$valmistetyyppi' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi";
					$result = pupe_query($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
					$result = pupe_query($query);
				}
				else {
					//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
					$query = "UPDATE tilausrivi SET hyllyalue='$p_hyllyalue' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
					$result = pupe_query($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
					$result = pupe_query($query);
				}
			}

			$rivitunnus			= 0;
			$kommentti 			= "";
			$tuotenimitys 		= "";
			$tuotenimitys_force = "";
			$omalle_tilaukselle = "";
			$rivinumero			= "";
			$tilrivinumero		= "";
		}
	}
?>