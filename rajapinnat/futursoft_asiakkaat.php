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

//	if ($argv[1] == "" or $argv[2] == "") {
//		echo "\nUsage: php ".basename($argv[0])." yhtio tiedosto.txt\n\n";
//		die;
//	}

	// Parametrit
	$yhtio = pupesoft_cleanstring($argv[1]);
	$filename = $argv[2];

	//TESTAUSTA VARTEN
	$filename = '/tmp/customers.xml';
	$yhtio = 'atarv';

	// Haetaan yhtiön tiedot
	$yhtiorow = hae_yhtion_parametrit($yhtio);

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
	$kukarow = mysql_fetch_assoc($result);
	$kukarow["kuka"] = "konversio";

	if (file_exists($filename)) {
		$xml = simplexml_load_file($filename);
		$asiakas_data = parsi_xml_tiedosto($xml);

		$kasitellyt_asiakkaat = kasittele_asiakkaat_data($asiakas_data, $yhtio);

		echo $kasitellyt_asiakkaat['asiakas_count'] . t(" asiakas luotiin / päivitettiin");
	}
	else {
		echo t("Tiedosto ei ole olemassa");
	}
	die();

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
					'ytunnus' => str_replace('-', '', (string)$asiakas->VATNum),
					'osoite' => utf8_decode((string)$asiakas->Street),
					'postino' => (string)$asiakas->ZipCode,
					'kaupunki' => utf8_decode((string)$asiakas->City),
					'maa' => (string)$asiakas->Country,
					'puhelin' => (string)$asiakas->Phone,
					'fax' => (string)$asiakas->TeleFax,
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
?>