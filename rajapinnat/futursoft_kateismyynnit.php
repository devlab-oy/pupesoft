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
	$filename = '/tmp/KPAM_kateis(1327).xml';
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
		$kateismyynti_data = parsi_xml_tiedosto($xml);

		echo count($kateismyynti_data).' '.t("kateismyyntiä löytyi");

		$kasitellyt_kateismyynnit = kasittele_kateismyynnit_data($kateismyynti_data, $yhtio);

		echo $kasitellyt_kateismyynnit['tilausnumero_count'] . t(" tilausta luotiin ja niihin ") . $kasitellyt_kateismyynnit['tiliointi_count'] . t(" tiliöintiä");

		//Testaamista varten
		poista_tilaukset_ja_tilioinnit($kasitellyt_kateismyynnit, $yhtio);
	}
	die();

	function parsi_xml_tiedosto(SimpleXMLElement $xml) {
		//i ja indeksi on sitä varten, että aineistosta saadaan yhteen kuuluvat käteismyynti tiliöinnit eriytettyä muista
		$data = array();
		if ($xml !== FALSE) {
			$i = 1;
			$indeksi = 0;
			foreach($xml->LedgerJournalTable->LedgerJournalTrans as $kateismyynti) {
				$data[$indeksi][] = array(
					'siirtopaiva' => (string)$kateismyynti->TransDate,
					'tapahtumapaiva' => (string)$kateismyynti->DocumentDate,
					'tapahtuman_tunnus' => (string)$kateismyynti->Invoice,
					'tilinumero' => (string)$kateismyynti->AccountNum,
					'selite' => utf8_decode((string)$kateismyynti->Txt),
					'summa' => ((string)$kateismyynti->AmountCurDebit == '') ? (float)$kateismyynti->AmountCurCredit : (float)$kateismyynti->AmountCurDebit,
					'valkoodi' => (string)$kateismyynti->Currency,
					'kurssi' => (string)$kateismyynti->ExchRate,
					'alv_ryhma' => (string)$kateismyynti->TaxGroup,
					'alv' => ((string)$kateismyynti->TaxItemGroup == '') ? 0 : (string)$kateismyynti->TaxItemGroup,
					'alv_maara' => (string)$kateismyynti->FixedTaxAmount,
					'kustp' => (string)$kateismyynti->Dim2,
				);
				if($i % 2 == 0) {
					$indeksi++;
				}
				$i++;
			}
		}

		return $data;
	}

	function kasittele_kateismyynnit_data($kateismyynnit, $yhtio) {
		$kateismyynti_count = 0;
		$tilausnumero_count = 0;
		$tiliointi_idt = array();
		$tilausnumerot = array();
		foreach($kateismyynnit as $kateismyynnin_osat) {
			$tilausnumero = luo_myyntiotsikko($kateismyynnin_osat, $yhtio);

			if($tilausnumero) {
				$tilausnumerot[] = $tilausnumero;
				paivita_tilauksen_tiedot($tilausnumero, $kateismyynnin_osat, $yhtio);
				$tilausnumero_count++;

				foreach($kateismyynnin_osat as $kateismyynti) {
					$tiliointi_tunnus = tee_tiliointi($tilausnumero, $kateismyynti, $yhtio);
					if($tiliointi_tunnus) {
						$tiliointi_idt[] = $tiliointi_tunnus;
						$kateismyynti_count++;
					}
				}
			}
		}

		return array(
			'tiliointi_idt' => $tiliointi_idt,
			'kateismyynti_count' => $kateismyynti_count,
			'tilausnumerot' => $tilausnumerot,
			'tilausnumero_count' => $tilausnumero_count,
		);
	}

	function luo_myyntiotsikko($kateismyynnin_osat, $yhtio) {
		$asiakas = luo_kaato_asiakas($yhtio);

		$tilausnumero = luo_myyntitilausotsikko('', $asiakas['tunnus']);

		return $tilausnumero;
	}

	function luo_kaato_asiakas($yhtio) {
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$yhtio}'
					AND asiakasnro = 'kaato_asiakas'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			//kaato-asiakkaan maksuehdoksi laitetaan käteinen, koska tarvitsemme validin maksuehdon
			$query_maksuehto = "SELECT *
								FROM maksuehto
								WHERE yhtio = '{$yhtio}'
								AND teksti LIKE '%Käteinen%'
								ORDER BY luontiaika DESC
								LIMIT 1";
			$result_maksuehto = pupe_query($query_maksuehto);
			$maksuehto_row = mysql_fetch_assoc($result_maksuehto);
			//kaato-asiakkaan toimitustavaksi laitetaan nouto, koska tarvitsemme validin toimitustavan
			$query_toimitustapa = "	SELECT *
									FROM toimitustapa
									WHERE yhtio = '{$yhtio}'
									AND selite LIKE '%Nouto%'
									LIMIT 1";
			$result_toimitustapa = pupe_query($query_toimitustapa);
			$toimitustapa_row = mysql_fetch_assoc($result_toimitustapa);
			//kaato-asiakasta ei ole olemassa, luodaan kaato-asiakas
			$query2 = "	INSERT INTO asiakas
						SET yhtio = '{$yhtio}',
						nimi ='Kaato asiakas',
						asiakasnro = 'kaato_asiakas',
						maksuehto = '{$maksuehto_row['tunnus']}',
						toimitustapa = '{$toimitustapa_row['tunnus']}'
						laatija = 'konversio',
						luontiaika = NOW()";
			pupe_query($query2);
		}

		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}

	function paivita_tilauksen_tiedot($tilausnumero, $kateismyynti, $yhtio) {
		$query = "	UPDATE lasku
					SET tapvm = '".date('Y-m-d', strtotime($kateismyynti[0]['tapahtumapaiva']))."',
					summa = '{$kateismyynti[0]['summa']}',
					tila = 'U'
					WHERE yhtio = '{$yhtio}'
					AND tunnus = '{$tilausnumero}'";
		pupe_query($query);
	}

	function tee_tiliointi($tilausnumero, $kateismyynti, $yhtio) {
		$kassalipas = tarkista_tilinumero($kateismyynti['kustp'], $yhtio);

		if(!empty($kassalipas)) {
			$query = "	INSERT INTO tiliointi
						SET tilino = '{$kassalipas['kassa']}',
						selite = '{$kateismyynti['selite']}',
						tapvm = '{$kateismyynti['tapahtumapaiva']}',
						summa = '{$kateismyynti['summa']}',
						vero = '{$kateismyynti['alv']}',
						kustp = '{$kateismyynti['kustp']}',
						ltunnus = '{$tilausnumero}',
						yhtio = '{$yhtio}'";
			pupe_query($query);

			return mysql_insert_id();
		}
		return null;
	}

	function tarkista_tilinumero($kustannuspaikka, $yhtio) {
		$query = "	SELECT *
					FROM kassalipas
					WHERE yhtio = '{$yhtio}'
					AND kustp = '{$kustannuspaikka}'";
		$result = pupe_query($query);

		$kassalipas_row = mysql_fetch_assoc($result);
		if($kassalipas_row) {
			return $kassalipas_row;
		}
		else {
			return array();
		}
	}

	function poista_tilaukset_ja_tilioinnit($kasitellyt_tilaukset, $yhtio) {
		$query = "	DELETE
					FROM lasku
					WHERE yhtio = '{$yhtio}'
					AND tunnus IN (".implode(',', $kasitellyt_tilaukset['tilausnumerot']).")";
		echo $query;
		echo "<br/><br/>";
		//pupe_query($query);

		$query2 = "	DELETE
					FROM tiliointi
					WHERE yhtio = '{$yhtio}'
					AND tunnus IN (".implode(',', $kasitellyt_tilaukset['tiliointi_idt']).")";
		echo $query2;
		//pupe_query($query2);
	}
?>