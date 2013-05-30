<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	$pupe_root_polku = dirname(dirname(__FILE__));
	require ("{$pupe_root_polku}/inc/connect.inc");
	require ("{$pupe_root_polku}/inc/functions.inc");
	require ("{$pupe_root_polku}/rajapinnat/magento_client.php");

	if (trim($argv[1]) != '') {
		$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
		$kukarow["extranet"] = "";
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
	}
	else {
		die ("Et antanut yhtiötä.\n");
	}

	if (trim($argv[2]) != '') {
		$verkkokauppatyyppi = trim($argv[2]);
	}
	else {
		die ("Et antanut verkkokaupan tyyppiä.\n");
	}

	if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "magento") {

		// Varmistetaan, että kaikki muuttujat on kunnossa
		if (empty($magento_api_edi) or empty($magento_api_url) or empty($magento_api_usr) or empty($magento_api_pas)) {
			exit;
		}

		// Testataan vielä, että yhteys toimii ennenkun ajellaan queryjä
		$magento_client = new MagentoClient($magento_api_url, $magento_api_usr, $magento_api_pas);
	}

	$ajetaanko_kaikki = (isset($argv[3]) and trim($argv[3]) != '') ? "YES" : "NO";

	// alustetaan arrayt
	$dnstuote = $dnsryhma = $dnstock = $dnsasiakas = $dnshinnasto = $dnslajitelma = array();

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa = "AND (tuote.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR) OR ta_nimitys_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR) OR ta_nimitys_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR))";
	}
	else {
		$muutoslisa = "";
	}

	// Haetaan pupesta tuotteen tiedot
	$query = "	SELECT tuote.tuoteno,
				tuote.nimitys,
				tuote.lyhytkuvaus,
				tuote.myyntihinta,
				tuote.yksikko,
				tuote.kuvaus,
				tuote.myymalahinta,
				tuote.eankoodi,
				tuote.osasto,
				tuote.try,
				tuote.alv,
				tuote.nakyvyys,
				tuote.tuotemassa,
				tuote.tunnus,
				ta_nimitys_se.selite nimi_swe,
				ta_nimitys_en.selite nimi_eng,
				try_fi.selitetark try_nimi
				FROM tuote
				LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio and try_fi.selite = tuote.try and try_fi.laji = 'try' and try_fi.kieli = 'fi')
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en'
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
				AND tuote.status != 'P'
				AND tuote.tuotetyyppi NOT in ('A','B')
				AND tuote.tuoteno != ''
				AND tuote.nakyvyys != ''
				$muutoslisa
	 			ORDER BY tuote.tuoteno";
	$res = pupe_query($query);

	// Pyöräytetään muuttuneet tuotteet läpi
	while ($row = mysql_fetch_array($res)) {

		// Jos yhtiön hinnat eivät sisällä alv:tä
		if ($yhtiorow["alv_kasittely"] != "" and $verkkokauppatyyppi != 'magento') {
			$myyntihinta					= hintapyoristys($row["myyntihinta"] * (1+($row["alv"]/100)));
			$myyntihinta_veroton 			= $row["myyntihinta"];

			$myymalahinta					= hintapyoristys($row["myymalahinta"] * (1+($row["alv"]/100)));
			$myymalahinta_veroton 			= $row["myymalahinta"];
		}
		else {
			$myyntihinta					= $row["myyntihinta"];
			$myyntihinta_veroton 			= hintapyoristys($row["myyntihinta"] / (1+($row["alv"]/100)));

			$myymalahinta					= $row["myymalahinta"];
			$myymalahinta_veroton 			= hintapyoristys($row["myymalahinta"] / (1+($row["alv"]/100)));
		}

		$dnstuote[] = array('tuoteno'				=> $row["tuoteno"],
							'nimi'					=> $row["nimitys"],
							'kuvaus'				=> $row["kuvaus"],
							'lyhytkuvaus'			=> $row["lyhytkuvaus"],
							'yksikko'				=> $row["yksikko"],
							'tuotemassa'			=> $row["tuotemassa"],
							'myyntihinta'			=> $myyntihinta,
							'myyntihinta_veroton'	=> $myyntihinta_veroton,
							'myymalahinta'			=> $myymalahinta,
							'myymalahinta_veroton'	=> $myymalahinta_veroton,
							'ean'					=> $row["eankoodi"],
							'osasto'				=> $row["osasto"],
							'try'					=> $row["try"],
							'try_nimi'				=> $row["try_nimi"],
							'alv'					=> $row["alv"],
							'nakyvyys'				=> $row["nakyvyys"],
							'nimi_swe'				=> $row["nimi_swe"],
							'nimi_eng'				=> $row["nimi_eng"],
							'tunnus'				=> $row['tunnus']
							);
	}

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa1 = "AND tapahtuma.laadittu > DATE_SUB(now(), INTERVAL 1 HOUR)";
		$muutoslisa2 = "AND tilausrivi.laadittu > DATE_SUB(now(), INTERVAL 1 HOUR)";
	}
	else {
		$muutoslisa1 = "";
		$muutoslisa2 = "";
	}

	// Haetaan saldot tuotteille, joille on tehty tunnin sisällä tilausrivi tai tapahtuma
	$query =  "(SELECT tapahtuma.tuoteno,
				tuote.eankoodi
				FROM tapahtuma
				JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
					AND tuote.tuoteno = tapahtuma.tuoteno
					AND tuote.status != 'P'
					AND tuote.tuotetyyppi NOT in ('A','B')
					AND tuote.tuoteno != ''
					AND tuote.nakyvyys != '')
				WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
				$muutoslisa1)

				UNION

				(SELECT tilausrivi.tuoteno,
				tuote.eankoodi
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
					AND tuote.tuoteno = tilausrivi.tuoteno
					AND tuote.status != 'P'
					AND tuote.tuotetyyppi NOT in ('A','B')
					AND tuote.tuoteno != ''
					AND tuote.nakyvyys != '')
				WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
				$muutoslisa2)

				ORDER BY 1";
	$result = pupe_query($query);

	while ($row = mysql_fetch_assoc($result)) {
		list(,,$myytavissa) = saldo_myytavissa($row["tuoteno"]);
		$dnstock[] = array(	'tuoteno'		=> $row["tuoteno"],
							'ean'			=> $row["eankoodi"],
							'myytavissa'	=> $myytavissa,
							);
	}

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa = "AND (try_fi.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
			OR try_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
			OR try_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
			OR osasto_fi.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
			OR osasto_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
			OR osasto_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR))";
	}
	else {
		$muutoslisa = "";
	}

	// Haetaan kaikki TRY ja OSASTO:t, niiden muutokset.
	$query = "	SELECT DISTINCT	tuote.osasto,
				tuote.try,
				try_fi.selitetark try_fi_nimi,
				try_se.selitetark try_se_nimi,
				try_en.selitetark try_en_nimi,
				osasto_fi.selitetark osasto_fi_nimi,
				osasto_se.selitetark osasto_se_nimi,
				osasto_en.selitetark osasto_en_nimi
				FROM tuote
				LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio and try_fi.selite = tuote.try and try_fi.laji = 'try' and try_fi.kieli = 'fi')
				LEFT JOIN avainsana as try_se ON (try_se.yhtio = tuote.yhtio and try_se.selite = tuote.try and try_se.laji = 'try' and try_se.kieli = 'se')
				LEFT JOIN avainsana as try_en ON (try_en.yhtio = tuote.yhtio and try_en.selite = tuote.try and try_en.laji = 'try' and try_en.kieli = 'en')
				LEFT JOIN avainsana as osasto_fi ON (osasto_fi.yhtio = tuote.yhtio and osasto_fi.selite = tuote.osasto and osasto_fi.laji = 'osasto' and osasto_fi.kieli = 'fi')
				LEFT JOIN avainsana as osasto_se ON (osasto_se.yhtio = tuote.yhtio and osasto_se.selite = tuote.osasto and osasto_se.laji = 'osasto' and osasto_se.kieli = 'se')
				LEFT JOIN avainsana as osasto_en ON (osasto_en.yhtio = tuote.yhtio and osasto_en.selite = tuote.osasto and osasto_en.laji = 'osasto' and osasto_en.kieli = 'en')
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
				AND tuote.status != 'P'
				AND tuote.tuotetyyppi NOT in ('A','B')
				AND tuote.tuoteno != ''
				AND tuote.nakyvyys != ''
				$muutoslisa
				ORDER BY 1, 2";
	$result = pupe_query($query);

	while ($row = mysql_fetch_assoc($result)) {

		$dnsryhma[$row["osasto"]][$row["try"]] = array(	'osasto'	=> $row["osasto"],
														'try'		=> $row["try"],
														'osasto_fi'	=> $row["osasto_fi_nimi"],
														'try_fi'	=> $row["try_fi_nimi"],
														'osasto_se'	=> $row["osasto_se_nimi"],
														'try_se'	=> $row["try_se_nimi"],
														'osasto_en' => $row["osasto_en_nimi"],
														'try_en'	=> $row["try_en_nimi"],
														);
	}

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa = "AND asiakas.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
	}
	else {
		$muutoslisa = "";
	}

	// Haetaan kaikki asiakkaat
	$query = "	SELECT asiakas.nimi,
				asiakas.osoite,
				asiakas.postino,
				asiakas.postitp,
				asiakas.email
				FROM asiakas
				WHERE asiakas.yhtio = '{$kukarow["yhtio"]}'
				AND asiakas.laji != 'P'
				$muutoslisa";
	$res = pupe_query($query);

	// pyöräytetään asiakkaat läpi
	while ($row = mysql_fetch_array($res)) {
		$dnsasiakas[] = array(	'nimi'		=> $row["nimi"],
								'osoite'	=> $row["osoite"],
								'postino'	=> $row["postino"],
								'postitp'	=> $row["postitp"],
								'email'		=> $row["email"],
								);
	}

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa = "AND hinnasto.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
	}
	else {
		$muutoslisa = "";
	}

	// Haetaan kaikki hinnastot ja alv
	$query = "	SELECT hinnasto.tuoteno,
				hinnasto.selite,
				hinnasto.alkupvm,
				hinnasto.loppupvm,
				hinnasto.hinta,
				tuote.alv
				FROM hinnasto
				JOIN tuote on (tuote.yhtio = hinnasto.yhtio
					AND tuote.tuoteno = hinnasto.tuoteno
					AND tuote.status != 'P'
					AND tuote.tuotetyyppi NOT in ('A','B')
					AND tuote.tuoteno != ''
					AND tuote.nakyvyys != '')
				WHERE hinnasto.yhtio = '{$kukarow["yhtio"]}'
				AND (hinnasto.minkpl = 0 AND hinnasto.maxkpl = 0)
				AND hinnasto.laji != 'O'
				AND hinnasto.maa IN ('FI', '')
				AND hinnasto.valkoodi in ('EUR', '')
				$muutoslisa";
	$res = pupe_query($query);

	// Tehdään hinnastot läpi
	while ($row = mysql_fetch_array($res)) {

		// Jos yhtiön hinnat eivät sisällä alv:tä
		if ($yhtiorow["alv_kasittely"] != "" and $verkkokauppatyyppi != 'magento') {
			$hinta					= hintapyoristys($row["hinta"] * (1+($row["alv"]/100)));
			$hinta_veroton 			= $row["hinta"];
		}
		else {
			$hinta		 			= $row["hinta"];
			$hinta_veroton			= hintapyoristys($row["hinta"] / (1+($row["alv"]/100)));
		}

		$dnshinnasto[] = array(	'tuoteno'				=> $row["tuoteno"],
								'selite'				=> $row["selite"],
								'alkupvm'				=> $row["alkupvm"],
								'loppupvm'				=> $row["loppupvm"],
								'hinta'					=> $hinta,
								'hinta_veroton'			=> $hinta_veroton,
								);
	}

	// haetaan tuotteen variaatiot
	$query = "	SELECT distinct selite
				FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'parametri_variaatio'
				AND trim(selite) != ''";
	$resselite = pupe_query($query);

	if ($ajetaanko_kaikki == "NO") {
		$muutoslisa = "AND tuotteen_avainsanat.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
	}
	else {
		$muutoslisa = "";
	}

	// Magentoon vain tuotteet joiden näkyvyys != ''
	$nakyvyys_lisa = ($verkkokauppatyyppi == 'magento') ? "AND tuote.nakyvyys != ''" : "";

	// loopataan variaatio-nimitykset
	while ($rowselite = mysql_fetch_assoc($resselite)) {

		$aliselect = "	SELECT
						tuotteen_avainsanat.tuoteno,
						tuote.tunnus,
						tuote.nimitys,
						tuote.kuvaus,
						tuote.lyhytkuvaus,
						tuote.tuotemassa,
						ta_nimitys_se.selite nimi_swe,
						ta_nimitys_en.selite nimi_eng,
						tuote.myyntihinta,
						tuote.myymalahinta,
						tuote.eankoodi,
						tuote.alv,
						tuote.nakyvyys,
						try_fi.selitetark try_nimi
						FROM tuotteen_avainsanat
						JOIN tuote on (tuote.yhtio = tuotteen_avainsanat.yhtio
							AND tuote.tuoteno = tuotteen_avainsanat.tuoteno
							AND tuote.status != 'P'
							AND tuote.tuotetyyppi NOT in ('A','B')
							AND tuote.tuoteno != ''
							$nakyvyys_lisa)
						LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio and try_fi.selite = tuote.try and try_fi.laji = 'try' and try_fi.kieli = 'fi')
						LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on (tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se')
						LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on (tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en')
						WHERE tuotteen_avainsanat.yhtio='{$kukarow['yhtio']}'
						AND tuotteen_avainsanat.selite = '{$rowselite['selite']}'
						{$muutoslisa}
						ORDER BY tuote.tuoteno";
		$alires = pupe_query($aliselect);

		while ($alirow = mysql_fetch_assoc($alires)) {

			$alinselect = " SELECT tuotteen_avainsanat.selite,
							avainsana.selitetark
							FROM tuotteen_avainsanat
							JOIN avainsana ON (avainsana.yhtio = tuotteen_avainsanat.yhtio
								AND avainsana.laji = 'PARAMETRI'
								AND avainsana.selite = SUBSTRING(tuotteen_avainsanat.laji, 11))
							WHERE tuotteen_avainsanat.yhtio='{$kukarow['yhtio']}'
							AND tuotteen_avainsanat.laji != 'parametri_variaatio'
							AND tuotteen_avainsanat.laji != 'parametri_variaatio_jako'
							AND tuotteen_avainsanat.laji like 'parametri_%'
							AND tuotteen_avainsanat.tuoteno = '{$alirow['tuoteno']}'
							ORDER by tuotteen_avainsanat.jarjestys, tuotteen_avainsanat.laji";
			$alinres = pupe_query($alinselect);
			$properties = array();

			while ($syvinrow = mysql_fetch_assoc($alinres)) {
				$properties[] = array(	"nimi" => $syvinrow["selitetark"],
				 						"arvo" => $syvinrow["selite"]);
			}

			// Jos yhtiön hinnat eivät sisällä alv:tä
			if ($yhtiorow["alv_kasittely"] != "" and $verkkokauppatyyppi != 'magento') {
				$myyntihinta					= hintapyoristys($alirow["myyntihinta"] * (1+($alirow["alv"]/100)));
				$myyntihinta_veroton 			= $alirow["myyntihinta"];

				$myymalahinta					= hintapyoristys($alirow["myymalahinta"] * (1+($alirow["alv"]/100)));
				$myymalahinta_veroton 			= $alirow["myymalahinta"];
			}
			else {
				$myyntihinta					= $alirow["myyntihinta"];
				$myyntihinta_veroton 			= hintapyoristys($alirow["myyntihinta"] / (1+($alirow["alv"]/100)));

				$myymalahinta					= $alirow["myymalahinta"];
				$myymalahinta_veroton 			= hintapyoristys($alirow["myymalahinta"] / (1+($alirow["alv"]/100)));
			}

			$dnslajitelma[$rowselite["selite"]][] = array(	'tuoteno' 				=> $alirow["tuoteno"],
															'tunnus'				=> $alirow["tunnus"],
															'nimitys'				=> $alirow["nimitys"],
															'kuvaus'				=> $alirow["kuvaus"],
															'lyhytkuvaus'			=> $alirow["lyhytkuvaus"],
															'tuotemassa'			=> $alirow["tuotemassa"],
															'nakyvyys'				=> $alirow["nakyvyys"],
															'try_nimi'				=> $alirow["try_nimi"],
															'nimi_swe'				=> $alirow["nimi_swe"],
															'nimi_eng'				=> $alirow["nimi_eng"],
															'myyntihinta'			=> $myyntihinta,
															'myyntihinta_veroton'	=> $myyntihinta_veroton,
															'myymalahinta'			=> $myymalahinta,
															'myymalahinta_veroton'	=> $myymalahinta_veroton,
															'ean'					=> $alirow["eankoodi"],
															'parametrit'			=> $properties);
		}

	}

	if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "magento") {

		$time_start = microtime(true);

		echo "Päivitetään Magento verkkokauppaa!\n";

		$magento_client = new MagentoClient($magento_api_url, $magento_api_usr, $magento_api_pas);

		// Kategoriat
		if (count($dnsryhma) > 0) {
			echo "Päivitetään tuotekategoriat\n";
			$count = $magento_client->lisaa_kategoriat($dnsryhma);
			echo "Päivitettiin $count kategoriaa\n";
		}

		// Tuotteet (Simple)
		if (count($dnstuote) > 0) {
			echo "Päivitetään simple tuotteet\n";
			$count = $magento_client->lisaa_simple_tuotteet($dnstuote);
			echo "Päivitettiin $count tuotetta (simple)\n";
		}

		// Tuotteet (Configurable)
		if (count($dnslajitelma) > 0) {
			echo "Päivitetään configurable tuotteet\n";
			$count = $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
			echo "Päivitettiin $count tuotetta (configurable)\n";
		}

		// Saldot
		if (count($dnstock) > 0) {
			echo "Päivitetään tuotteiden saldot\n";
			$count = $magento_client->paivita_saldot($dnstock);
			echo "Päivitettiin $count tuotteen saldot\n";
		}

		$time_end = microtime(true);
		$time = $time_end - $time_start;

		echo 'Magenton päivitys kesti '.$time.' sekuntia';
	}
	elseif (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "anvia") {

		if (isset($anvia_ftphost, $anvia_ftpuser, $anvia_ftppass, $anvia_ftppath)) {
			$ftphost = $anvia_ftphost;
			$ftpuser = $anvia_ftpuser;
			$ftppass = $anvia_ftppass;
			$ftppath = $anvia_ftppath;
		}
		else {
			$ftphost = "";
			$ftpuser = "";
			$ftppass = "";
			$ftppath = "";
		}

		$tulos_ulos = "";

		if (count($dnstuote) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/tuotexml.inc");
		}

		if (count($dnstock) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/varastoxml.inc");
		}

		if (count($dnsryhma) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/ryhmaxml.inc");
		}

		if (count($dnsasiakas) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/asiakasxml.inc");
		}

		if (count($dnshinnasto) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/hinnastoxml.inc");
		}

		if (count($dnslajitelma) > 0) {
			require ("{$pupe_root_polku}/rajapinnat/lajitelmaxml.inc");
		}
	}
