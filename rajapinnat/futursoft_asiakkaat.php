<?php

	error_reporting(E_ALL ^ E_NOTICE);
	ini_set("display_errors", 1);
	ini_set("log_errors", 0);

	if (file_exists("/var/www/html/pupesoft/inc/connect.inc")) {
		include("/var/www/html/pupesoft/inc/connect.inc");
		include("/var/www/html/pupesoft/inc/functions.inc");
		include("/var/www/html/pupesoft/tilauskasittely/luo_myyntitilausotsikko.inc");
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."/var/www/html/pupesoft".PATH_SEPARATOR."/usr/share/pear");
	}
	else {
		require("/Users/joonas/Dropbox/Sites/pupesoft/inc/connect.inc");
		require("/Users/joonas/Dropbox/Sites/pupesoft/inc/functions.inc");
		require("/Users/joonas/Dropbox/Sites/pupesoft/tilauskasittely/luo_myyntitilausotsikko.inc");
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."/Users/joonas/Dropbox/Sites/pupesoft".PATH_SEPARATOR."/usr/share/pear");
	}

	$yhtio = 'atarv';
	$futursoft_kansio = "/home/merca-autoasi/";
	$futursoft_kansio_valmis = "/home/merca-autoasi/ok/";
	$futursoft_kansio_error = "/home/merca-autoasi/error/";

	if(!is_dir($futursoft_kansio)) {
		mkdir($futursoft_kansio);
	}
	if(!is_dir($futursoft_kansio_valmis)) {
		mkdir($futursoft_kansio_valmis);
	}
	if(!is_dir($futursoft_kansio_error)) {
		mkdir($futursoft_kansio_error);
	}

	system('chmod -R 777 "'.$futursoft_kansio.'"');
	
	$kukarow = hae_kayttaja($yhtio);
	$kukarow["kuka"] = "konversio";

	$tiedostot = lue_tiedostot($futursoft_kansio);

	if(!empty($tiedostot)) {
		foreach($tiedostot as $tiedosto) {
			$tiedosto_polku = $futursoft_kansio.$tiedosto;
				if (file_exists($tiedosto_polku)) {
				$xml = simplexml_load_file($tiedosto_polku);
				if(!$xml) {
					//file read failure, siirretään tiedosto error kansioon
					siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_error);

					die(t("Tiedoston {$tiedosto_polku} lukeminen epäonnistui"));
				}
				$asiakas_data = parsi_xml_tiedosto($xml);

				$kasitellyt_asiakkaat = kasittele_asiakkaat_data($asiakas_data, $yhtio);

				echo $kasitellyt_asiakkaat['asiakas_count'] . t(" asiakas luotiin / päivitettiin");

				siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);
			}
			else {
				echo t("Tiedosto ei ole olemassa");
			}
		}
	}
	else {
		echo t("Yhtään tiedostoa ei löytynyt");
	}
	die();
	
	function lue_tiedostot($polku) {
		$tiedostot = array();
		if ($handle = opendir($polku)) {
			while (false !== ($tiedosto = readdir($handle))) {
				if ($tiedosto != "." && $tiedosto != "..") {
					if(is_file($polku.$tiedosto)) {
						if(stristr($tiedosto, 'customers')) {
							$tiedostot[] = $tiedosto;
						}
					}
				}
			}
			closedir($handle);
		}

		return $tiedostot;
	}

	function siirra_tiedosto_kansioon($tiedosto_polku, $kansio) {
		$tiedosto_array = explode('.', $tiedosto_polku);
		if(!empty($tiedosto_array)) {
			$tiedosto_array2 = explode('/',$tiedosto_array[0]);
			$hakemiston_syvyys = count($tiedosto_array2);

			$uusi_filename = $tiedosto_array2[$hakemiston_syvyys - 1].'_'.date('YmdHis').'.'.$tiedosto_array[1];
		}
		$komento = 'cp "'.$tiedosto_polku.'" "'.$kansio.$uusi_filename.'"';
		exec($komento);

		system('rm "'.$tiedosto_polku.'"');
	}

	function parsi_xml_tiedosto(SimpleXMLElement $xml) {
		$data = array();
		if ($xml !== FALSE) {
			foreach($xml->CustTable as $asiakas) {
				$data[] = array(
					'asiakasnumero' => (string)$asiakas->AccountNum,
					'nimi' => utf8_decode(htmlspecialchars_decode((string)$asiakas->Name)),
					'nimi_alias' => utf8_decode((string)$asiakas->NameAlias),
					'asiakas_ryhma' => (string)$asiakas->CustGroup,
					'valuutta' => (string)$asiakas->Currency,
					'kieli' => (string)$asiakas->LanguageId,
					'max_luotto' => (float)$asiakas->CreditMax,
					'ytunnus' => ((string)$asiakas->VATNum == '') ? '' : 'FI'.str_replace('-', '', (string)$asiakas->VATNum),
					'ovttunnus' => ((string)$asiakas->VATNum == '') ? '' : '0037'.str_replace('-', '', (string)$asiakas->VATNum),
					'osoite' => utf8_decode((string)$asiakas->Street),
					'postino' => (string)$asiakas->ZipCode,
					'kaupunki' => utf8_decode((string)$asiakas->City),
					'maa' => (string)$asiakas->Country,
					'puhelin' => (string)$asiakas->Phone,
					'fax' => (string)$asiakas->Telefax,
					'maksuehto' => (string)$asiakas->PaymTermId,
					'status' => (string)$asiakas->StatusCode,
					'vero_ryhma' => (string)$asiakas->TaxGroup,
					'dim' => (string)$asiakas->Dim1,
					'kustp' => (string)$asiakas->Dim2,
				);
			}
		}

		return $data;
	}

	function kasittele_asiakkaat_data($asiakkaat, $yhtio) {
		$asiakas_count = 0;
		foreach($asiakkaat as $asiakas) {
			paivita_tai_luo_asiakas($asiakas, $yhtio);
			$asiakas_count++;
		}

		return array(
			'asiakas_count' => $asiakas_count,
		);
	}

	function paivita_tai_luo_asiakas($asiakas, $yhtio) {
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$yhtio}'
					AND asiakasnro = '{$asiakas['asiakasnumero']}'
					AND ytunnus = '{$asiakas['ytunnus']}'
					AND laji != 'P'";
		$result = pupe_query($query);

		if(mysql_num_rows($result) > 0) {
			paivita_asiakas($asiakas, $yhtio, $result);
		}
		else {
			luo_asiakas($asiakas, $yhtio);
		}
	}

	function luo_asiakas($asiakas, $yhtio) {
		$maksuehto = tarkista_maksuehto($asiakas['maksuehto'], $yhtio);
		$toimitustapa = hae_toimitustapa($yhtio);
		$query = "	INSERT INTO asiakas
					SET yhtio = '{$yhtio}',
					ytunnus = '{$asiakas['ytunnus']}',
					ovttunnus = '{$asiakas['ovttunnus']}',
					nimi = '{$asiakas['nimi']}',
					osoite = '{$asiakas['osoite']}',
					postino = '{$asiakas['postino']}',
					postitp = '{$asiakas['kaupunki']}',
					maa = '{$asiakas['maa']}',
					kansalaisuus = '{$asiakas['maa']}',
					puhelin = '{$asiakas['puhelin']}',
					gsm = '{$asiakas['puhelin']}',
					fax = '{$asiakas['fax']}',
					kieli = '".strtolower($asiakas['kieli'])."',
					valkoodi = '{$asiakas['valuutta']}',
					maksuehto = '{$maksuehto['tunnus']}',
					toimitustapa = '{$toimitustapa['selite']}',
					luottoraja = '{$asiakas['max_luotto']}',
					kustannuspaikka = '{$asiakas['kustp']}',
					laatija = 'futur',
					luontiaika = NOW(),
					asiakasnro = '{$asiakas['asiakasnumero']}'";

		pupe_query($query);

		echo t("Asiakas").' '.$asiakas['nimi'].' '.$asiakas['asiakasnro'].' '.t("luotiin");
		echo "<br/>";
	}

	function paivita_asiakas($asiakas, $yhtio, $olemassa_oleva_result) {
		$olemassa_oleva_asiakas_row = mysql_fetch_assoc($olemassa_oleva_result);
		$maksuehto = tarkista_maksuehto($asiakas['maksuehto'], $yhtio);
		$toimitustapa = hae_toimitustapa($yhtio);
		$query = "	UPDATE asiakas
					SET ytunnus = '{$asiakas['ytunnus']}',
					ovttunnus = '{$asiakas['ovttunnus']}',
					nimi = '{$asiakas['nimi']}',
					osoite = '{$asiakas['osoite']}',
					postino = '{$asiakas['postino']}',
					postitp = '{$asiakas['kaupunki']}',
					maa = '{$asiakas['maa']}',
					kansalaisuus = '{$asiakas['maa']}',
					puhelin = '{$asiakas['puhelin']}',
					gsm = '{$asiakas['puhelin']}',
					fax = '{$asiakas['fax']}',
					kieli = '".strtolower($asiakas['kieli'])."',
					valkoodi = '{$asiakas['valuutta']}',
					maksuehto = '{$maksuehto['tunnus']}',
					toimitustapa = '{$toimitustapa['selite']}',
					luottoraja = '{$asiakas['max_luotto']}',
					kustannuspaikka = '{$asiakas['kustp']}',
					muuttaja = 'futursoft',
					muutospvm = NOW()
					WHERE yhtio = '{$yhtio}'
					AND tunnus = '{$olemassa_oleva_asiakas_row['tunnus']}'";
		pupe_query($query);

		echo t("Asiakas").' '.$asiakas['nimi'].' '.$asiakas['asiakasnro'].' '.t("päivitettiin");
		echo "<br/>";
	}

	function tarkista_maksuehto($maksuehto, $yhtio) {
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '{$yhtio}'
					AND tunnus = '{$maksuehto}'";
		$result = pupe_query($query);

		if(mysql_num_rows($result) > 0) {
			return mysql_fetch_assoc($result);
		}
		else {
			$query = "	SELECT *
						FROM maksuehto
						WHERE yhtio = '{$yhtio}'
						AND teksti LIKE '%heti%'
						LIMIT 1";
			$result = pupe_query($query);

			return mysql_fetch_assoc($result);
		}
	}

	function hae_toimitustapa($yhtio) {
		$query = "	SELECT *
					FROM toimitustapa
					WHERE yhtio = '{$yhtio}'
					AND selite LIKE '%Oletus%'
					LIMIT 1";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
	
	function hae_kayttaja($yhtio) {
		// Haetaan käyttäjän tiedot
		$query = "	SELECT *
					FROM kuka
					WHERE yhtio = '$yhtio'
					AND kuka = 'admin'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			die("User admin not found");
		}
		// Adminin oletus, mutta kuka konversio
		return mysql_fetch_assoc($result);
	}
?>