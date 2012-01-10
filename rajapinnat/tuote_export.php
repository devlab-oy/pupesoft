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
			// esim. anvia
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
	
	// Haetaan pupesta tuotteen tiedot
	$query = "	SELECT tuote.*,
				try_fi.selitetark try_fi_nimi,
				try_se.selitetark try_se_nimi,
				try_en.selitetark try_en_nimi,
				osasto_fi.selitetark osasto_fi_nimi,
				osasto_se.selitetark osasto_se_nimi,
				osasto_en.selitetark osasto_en_nimi,
				ta_nimitys_se.selite nimi_swe, 
				ta_nimitys_en.selite nimi_eng,
				if(t1.jarjestys = 0 or t1.jarjestys is null, 999999, t1.jarjestys) t1j1,
				if(t2.jarjestys = 0 or t2.jarjestys is null, 999999, t2.jarjestys) t2j2,
				if(t3.jarjestys = 0 or t3.jarjestys is null, 999999, t3.jarjestys) t3j3,
				FROM tuote
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_se on tuote.yhtio = ta_kuvaus_se.yhtio and tuote.tuoteno = ta_kuvaus_se.tuoteno and ta_kuvaus_se.laji = 'kuvaus' and ta_kuvaus_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_en on tuote.yhtio = ta_kuvaus_en.yhtio and tuote.tuoteno = ta_kuvaus_en.tuoteno and ta_kuvaus_en.laji = 'kuvaus' and ta_kuvaus_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_se on tuote.yhtio = ta_lyhyt_se.yhtio and tuote.tuoteno = ta_lyhyt_se.tuoteno and ta_lyhyt_se.laji = 'lyhytkuvaus' and ta_lyhyt_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_en on tuote.yhtio = ta_lyhyt_en.yhtio and tuote.tuoteno = ta_lyhyt_en.tuoteno and ta_lyhyt_en.laji = 'lyhytkuvaus' and ta_lyhyt_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_se on tuote.yhtio = ta_mainos_se.yhtio and tuote.tuoteno = ta_mainos_se.tuoteno and ta_mainos_se.laji = 'mainosteksti' and ta_mainos_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_en on tuote.yhtio = ta_mainos_en.yhtio and tuote.tuoteno = ta_mainos_en.tuoteno and ta_mainos_en.laji = 'mainosteksti' and ta_mainos_en.kieli = 'en'
				LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio and try_fi.selite = tuote.try and try_fi.laji = 'try' and try_fi.kieli = 'fi')
				LEFT JOIN avainsana as try_se ON (try_se.yhtio = tuote.yhtio and try_se.selite = tuote.try and try_se.laji = 'try' and try_se.kieli = 'se')
				LEFT JOIN avainsana as try_en ON (try_en.yhtio = tuote.yhtio and try_en.selite = tuote.try and try_en.laji = 'try' and try_en.kieli = 'en')	
				LEFT JOIN avainsana as osasto_fi ON (osasto_fi.yhtio = tuote.yhtio and osasto_fi.selite = tuote.osasto and osasto_fi.laji = 'osasto' and osasto_fi.kieli = 'fi')
				LEFT JOIN avainsana as osasto_se ON (osasto_se.yhtio = tuote.yhtio and osasto_se.selite = tuote.osasto and osasto_se.laji = 'osasto' and osasto_se.kieli = 'se')
				LEFT JOIN avainsana as osasto_en ON (osasto_en.yhtio = tuote.yhtio and osasto_en.selite = tuote.osasto and osasto_en.laji = 'osasto' and osasto_en.kieli = 'en')		
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
				#AND tuote.nakyvyys != ''
				AND tuote.status != 'P'
				AND tuote.tuotetyyppi not in ('A','B')
				AND tuote.tuoteno != ''
				#AND t1.selite !=''
				#AND timestampdiff(MINUTE, tuote.muutospvm, now()) < 60
	 			ORDER BY t1j1, t2j2, t3j3, tuote.tuoteno
				";

	$res = pupe_query($query);

	// alustetaan arrayt
	$dnstuote = $dnsryhma = $dnstock = $dnsasiakas = $dnshinnasto = $dnslajitelma = array();

	// loopataan tuotteet
	while ($row = mysql_fetch_array($res)) {
		
		list(,,$myytavissa) = saldo_myytavissa($row["tuoteno"]);
		
		$dnstuote[] = array('tuoteno' => $row["tuoteno"], 'nimi' => $row["nimitys"], 
											'yksikko' => $row["yksikko"], 'myyntihinta' => $row["myyntihinta"],
											'ean' => $row["eankoodi"], 'myytavissa' => $myytavissa,
											'osasto' => $row["osasto"], 'try' => $row["try"],
											'nimi_swe' => $row["nimi_swe"],'nimi_eng' => $row["nimi_eng"],);
										
		$dnstock[] = array('tuoteno' => $row["tuoteno"], 'ean' => $row["eankoodi"], 'myytavissa' => $myytavissa,);

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
				#AND timestampdiff(MINUTE, muutospvm,now()) < 60
				";
	$res = pupe_query($query);
	
	// loopataan asiakkaat
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
				#AND timestampdiff(MINUTE, muutospvm,now()) < 60
				";
	$res = pupe_query($query);
	
	// loopataan hinnastot
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
						#AND timestampdiff(MINUTE, tuotteen_avainsanat.muutospvm,now()) < 60";
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
		require ("{$pupe_root_polku}/rajapinnat/tuotexml.inc");
		require ("{$pupe_root_polku}/rajapinnat/varastoxml.inc"); // Mahd ?
		require ("{$pupe_root_polku}/rajapinnat/ryhmaxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/asiakasxml.inc");
		require ("{$pupe_root_polku}/rajapinnat/hinnastoxml.inc"); // Mahd ?
		require ("{$pupe_root_polku}/rajapinnat/lajitelmaxml.inc");
		// mihin filuihin tulee se 65 minuuttia muutosaika ?
		// and timestampdiff(MINUTE, muutospvm,now()) <65
	}
	
	if (php_sapi_name() != 'cli') {
		require ("inc/footer.inc");
	}
?>