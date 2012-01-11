<?php

	if (php_sapi_name() == 'cli') {
		$pupe_root_polku = dirname(dirname(__FILE__));
		require ("{$pupe_root_polku}/inc/connect.inc");
		require ("{$pupe_root_polku}/inc/functions.inc");
		$cli = true;

		if (trim($argv[1]) != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
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
	}
	else {
		require ("../inc/parametrit.inc");
		$verkkokauppatyyppi = "anvia";
		$cli = false;
	}

	// alustetaan arrayt
	$dnstuote = $dnsryhma = $dnstock = $dnsasiakas = $dnshinnasto = $dnslajitelma = array();

	// Haetaan pupesta tuotteen tiedot
	$query = "	SELECT tuote.*,
				ta_nimitys_se.selite nimi_swe,
				ta_nimitys_en.selite nimi_eng
				FROM tuote
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en'
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
				AND tuote.status != 'P'
				AND tuote.tuotetyyppi not in ('A','B')
				AND tuote.tuoteno != ''
				AND (tuote.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR) OR ta_nimitys_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR) OR ta_nimitys_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR))
	 			ORDER BY tuote.tuoteno";

	$res = pupe_query($query);

	// Pyöräytetään muuttuneet tuotteet läpi
	while ($row = mysql_fetch_array($res)) {
		// tehdään vain jos tuotteen tiedot muuttuu
		$dnstuote[] = array('tuoteno' => $row["tuoteno"], 'nimi' => $row["nimitys"],
											'yksikko' => $row["yksikko"], 'myyntihinta' => $row["myyntihinta"],
											'ean' => $row["eankoodi"], 'myytavissa' => $myytavissa,
											'osasto' => $row["osasto"], 'try' => $row["try"],
											'nimi_swe' => $row["nimi_swe"],'nimi_eng' => $row["nimi_eng"],);
	}

	// Haetaan NE tuotteet joita on käsitelty tunnin sisällä tilauksilla tai ostoilla
	$query =  "(SELECT tapahtuma.tuoteno, tuote.eankoodi FROM tapahtuma
				JOIN tuote on (tuote.yhtio = tapahtuma.yhtio and tuote.tuoteno = tapahtuma.tuoteno)
				WHERE tapahtuma.yhtio='{$kukarow["yhtio"]}' and tapahtuma.laadittu > DATE_SUB(now(), INTERVAL 1 HOUR))
				UNION
				(SELECT tilausrivi.tuoteno, tuote.eankoodi FROM tilausrivi
				JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
				WHERE tilausrivi.yhtio='{$kukarow["yhtio"]}' and tilausrivi.laadittu > DATE_SUB(now(), INTERVAL 1 HOUR))
				ORDER BY 1";
	$result = pupe_query($query);

	while ($row = mysql_fetch_assoc($result)) {
		list(,,$myytavissa) = saldo_myytavissa($row["tuoteno"]);
		$dnstock[] = array('tuoteno' => $row["tuoteno"], 'ean' => $row["eankoodi"], 'myytavissa' => $myytavissa,);
	}


	// Haetaan kaikki TRY ja OSASTO:t, niiden muutokset.
	$query = "	SELECT 	tuote.osasto,
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
				AND tuote.tuotetyyppi not in ('A','B')
				AND tuote.tuoteno != ''
				AND (try_fi.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
					OR try_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
					OR try_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
					OR osasto_fi.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
					OR osasto_se.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)
					OR osasto_en.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR))
				group by 1,2,3,4,5,6,7,8
				ORDER BY 1,2";

	$result = pupe_query($query);

	while ($row = mysql_fetch_assoc($result)) {

		$dnsryhma[$row["osasto"]][$row["try"]] = array('osasto' => $row["osasto"], 'try' => $row["try"],
											'osasto_fi' => $row["osasto_fi_nimi"], 'try_fi' => $row["try_fi_nimi"],
											'osasto_se' => $row["osasto_se_nimi"], 'try_se' => $row["try_se_nimi"],
											'osasto_en' => $row["osasto_en_nimi"], 'try_en' => $row["try_en_nimi"],
											);
	}

	// Haetaan kaikki asiakkaat
	$query = "	SELECT asiakas.*
				FROM asiakas
				WHERE asiakas.yhtio = '{$kukarow["yhtio"]}'
				AND asiakas.laji !='P'
				AND asiakas.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
	$res = pupe_query($query);

	// pyöräytetään asiakkaat läpi
	while ($row = mysql_fetch_array($res)) {
		$dnsasiakas[] =   array('nimi' => $row["nimi"],
								'osoite' => $row["osoite"], 'postino' => $row["postino"],
								'postitp' => $row["postitp"], 'email' => $row["email"],);
	}

	// Haetaan kaikki hinnastot
	$query = "	SELECT hinnasto.*
				FROM hinnasto
				WHERE hinnasto.yhtio = '{$kukarow["yhtio"]}'
				AND (hinnasto.minkpl = 0 and hinnasto.maxkpl = 0)
				AND hinnasto.laji !='O'
				AND hinnasto.maa in ('FI','')
				AND hinnasto.valkoodi in ('EUR','')
				AND hinnasto.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
	$res = pupe_query($query);

	// Tehdään hinnastot läpi
	while ($row = mysql_fetch_array($res)) {
		$dnshinnasto[] =   array('tuoteno' => $row["tuoteno"], 'selite' => $row["selite"], 'alkupvm' => $row["alkupvm"], 'loppupvm' => $row["loppupvm"], 'hinta' => $row["hinta"],);
	}

	// haetaan tuotteen variaatiot
	$query = "	SELECT distinct selite
				FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji = 'parametri_variaatio' ";
	$resselite = pupe_query($query);

	// loopataan variaatio-nimitykset
	while ($rowselite = mysql_fetch_assoc($resselite)) {

		$aliselect = "SELECT tuotteen_avainsanat.tuoteno, tuote.nimitys ,ta_nimitys_se.selite nimi_swe, ta_nimitys_en.selite nimi_eng, tuote.myyntihinta,tuote.eankoodi
						FROM tuotteen_avainsanat
						JOIN tuote on (tuote.yhtio = tuotteen_avainsanat.yhtio and tuote.tuoteno = tuotteen_avainsanat.tuoteno)
						LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on (tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se')
						LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on (tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en')
						WHERE tuotteen_avainsanat.yhtio='{$kukarow["yhtio"]}'
						AND tuotteen_avainsanat.selite = '{$rowselite["selite"]}'
						AND tuotteen_avainsanat.muutospvm > DATE_SUB(now(), INTERVAL 1 HOUR)";
		$alires = pupe_query($aliselect);

		while ($alirow = mysql_fetch_assoc($alires)) {

			$alinselect = " SELECT tuotteen_avainsanat.laji, tuotteen_avainsanat.selite
							FROM tuotteen_avainsanat
							WHERE tuotteen_avainsanat.yhtio='{$kukarow["yhtio"]}'
							AND tuotteen_avainsanat.laji != 'parametri_variaatio'
							AND tuotteen_avainsanat.laji like 'parametri_%'
							AND tuotteen_avainsanat.tuoteno = '{$alirow["tuoteno"]}'";
			$alinres = pupe_query($alinselect);
			$properties = array();

			while ($syvinrow = mysql_fetch_assoc($alinres)) {
				$properties[] = array(substr($syvinrow["laji"], 10) => $syvinrow["selite"]);
			}

			$dnslajitelma[$rowselite["selite"]][] = array(	'tuoteno' 	=> $alirow["tuoteno"],
															'nimitys'	=> $alirow["nimitys"],
															'nimi_swe'	=> $alirow["nimi_swe"],
															'nimi_eng'	=> $alirow["nimi_eng"],
															'variaatio' => $rowselite["selite"],
															'myyntihinta'	=> $alirow["myyntihinta"],
															'ean'		=> $alirow["eankoodi"],
															'parametrit'	=> $properties);

		}

	}

	if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "anvia") {

		if (isset($anvia_ftphost,$anvia_ftpuser,$anvia_ftppass,$anvia_ftppath)) {
			$ftphost = $anvia_ftphost;
			$ftpuser = $anvia_ftpuser;
			$ftppass = $anvia_ftppass;
			$ftppath = $anvia_ftppath;
		}
		
		$tulos_ulos = "";
		
		require ("{$pupe_root_polku}/rajapinnat/tuotexml.inc");
		require ("{$pupe_root_polku}/rajapinnat/varastoxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/ryhmaxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/asiakasxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/hinnastoxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/lajitelmaxml.inc");

	}

	if (php_sapi_name() != 'cli') {
		require ("inc/footer.inc");
	}
?>