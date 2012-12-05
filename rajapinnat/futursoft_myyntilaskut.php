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
	$filename = '/tmp/KPAM_myynti(1332).xml';
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
		$myyntilasku_data = parsi_xml_tiedosto($xml);

		$kasitellyt_tilaukset = kasittele_myyntilasku_data($myyntilasku_data, $yhtio);

		echo $kasitellyt_tilaukset['tilausnumero_count'] . t(" tilausta luotiin ja niihin ") . $kasitellyt_tilaukset['tiliointi_count'] . t(" tiliöintiä");

		//Testausta varten
		poista_tilaukset_ja_tilioinnit($kasitellyt_tilaukset, $yhtio);
	}
	die();

	function parsi_xml_tiedosto(SimpleXMLElement $xml) {
		$data = array();
		if ($xml !== FALSE) {
			foreach($xml->LedgerJournalTable->LedgerJournalTrans as $myyntilasku) {
				if($myyntilasku->AccountType == 'Cust') {
					//Myyntilasku otsikko
					$lasku_numero = (string)$myyntilasku->Invoice;
					$data[$lasku_numero] = array(
						'asiakasnumero' => (string)$myyntilasku->AccountNum,
						'siirtopaiva' => (string)$myyntilasku->TransDate,
						'tapahtumapaiva' => (string)$myyntilasku->DocumentDate,
						'asiakkaan_nimi' => (string)$myyntilasku->Txt,
						'summa' => (float)$myyntilasku->AmountCurDebit,
						'valuutta' => (string)$myyntilasku->Currency,
						'kurssi' => (float)$myyntilasku->ExchRate,
						'maksuehto' => (string)$myyntilasku->Payment,
						'erapaiva' => (string)$myyntilasku->Due,
						'viite' => (string)$myyntilasku->PaymId,
					);
				}
				else if ($myyntilasku->AccountType == 'Ledger') {
					$data[$lasku_numero]['tilioinnit'][] = array(
						'tilinumero' => (string)$myyntilasku->AccountNum,
						'siirtopaiva' => (string)$myyntilasku->TransDate,
						'tapahtumapaiva' => (string)$myyntilasku->DocumentDate,
						'asiakkaan_nimi' => (string)$myyntilasku->Txt,
						'summa' => ((string)$myyntilasku->AmountCurDebit == '') ? (float)($myyntilasku->AmountCurCredit * -1) : (float)$myyntilasku->AmountCurDebit,
						'valuutta' => (string)$myyntilasku->Currency,
						'kurssi' => (string)$myyntilasku->ExchRate,
						'alv' => ((string)$myyntilasku->TaxItemGroup == '') ? 0 : (float)$myyntilasku->TaxItemGroup,
						'alv_maara' => (float)$myyntilasku->FixedTaxAmount,
						'kustp' => (string)$myyntilasku->Dim2,
						'maksuehto' => (string)$myyntilasku->Payment,
						'erapaiva' => (string)$myyntilasku->Due,
						'viite' => (string)$myyntilasku->PaymId,
					);
				}
			}
		}

		return $data;
	}

	function kasittele_myyntilasku_data($myyntilaskut, $yhtio) {
		$tilausnumero_count = 0;
		$tiliointi_count = 0;
		$tilausnumerot = array();
		$tiliointi_tunnukset = array();
		foreach($myyntilaskut as $myyntilasku) {
			$tilausnumero = luo_myyntiotsikko($myyntilasku, $yhtio);

			if($tilausnumero) {
				$tilausnumerot[] = $tilausnumero;
				paivita_tilauksen_tiedot($tilausnumero, $myyntilasku, $yhtio);
				$tilausnumero_count++;

				foreach($myyntilasku['tilioinnit'] as $tiliointi) {
					$tiliointi_tunnus = tee_tiliointi($tilausnumero,$tiliointi, $yhtio);
					if($tiliointi_tunnus) {
						$tiliointi_tunnukset[] = $tiliointi_tunnus;
						$tiliointi_count++;
					}
				}
			}
		}

		return array(
			'tilausnumero_count' => $tilausnumero_count,
			'tiliointi_count' => $tiliointi_count,
			'tilausnumerot' => $tilausnumerot,
			'tiliointi_tunnukset' => $tiliointi_tunnukset,
		);
	}

	function luo_myyntiotsikko($myyntilasku, $yhtio) {
		$asiakas = tarkista_asiakas_olemassa($myyntilasku['asiakasnumero'], $yhtio);

		if(!empty($asiakas)) {
			//asiakasta ei löytynyt juodumme käyttämään kaato-asiakasta
			$tilausnumero = luo_myyntitilausotsikko('', $asiakas['tunnus']);
			if($tilausnumero) {
				$query = "	UPDATE lasku
							SET comments = '".t("Laskun asiakasta ei löytynyt, käytimme laskun konversioon väliaikaista kaato-asiakasta. Korjaa tämä lasku mahdollisimman pian. Alkuperäisen laskun tiedot löytyvät sisviesti2")."',
								sisviesti2 = '".json_encode($myyntilasku)."'
							WHERE yhtio = '{$yhtio}'
							AND tunnus = '{$tilausnumero}'";
				pupe_query($query);
			}

			echo t("Laskulle").' '.$tilausnumero.' '.t("jouduttiin liittämään kaato-asiakas, koska laskun asiakasta ei löytynyt tietokannasta.");
			echo "<br/>";
		}
		else {
			$tilausnumero = luo_myyntitilausotsikko('', $asiakas['tunnus']);
		}

		if($tilausnumero == null) {
			$joonakse_debug = 1;
		}
		
		return $tilausnumero;
	}

	function tarkista_asiakas_olemassa($asiakasnumero, $yhtio) {
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$yhtio}'
					AND asiakasnro = '{$asiakasnumero}'
					AND laji != 'P'
					LIMIT 1";
		$result = pupe_query($query);
		if(mysql_num_rows($result) == 0) {
			//asiakasta ei olemassa, luomme kaato_asiakkaan
			$asiakas = luo_kaato_asiakas($yhtio);

			return $asiakas;
		}

		return mysql_fetch_assoc($result);
	}

	function luo_kaato_asiakas($yhtio) {
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$yhtio}'
					AND asiakasnro = 'kaato_asiakas'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			//kaato-asiakkaan maksuehdoksi laitetaan heti-maksuehto, koska tarvitsemme validin maksuehdon
			$query_maksuehto = "SELECT *
								FROM maksuehto
								WHERE yhtio = '{$yhtio}'
								AND teksti LIKE '%heti%'
								ORDER BY luontiaika DESC
								LIMIT 1";
			$result_maksuehto = pupe_query($query_maksuehto);
			$maksuehto_row = mysql_fetch_assoc($result_maksuehto);
			//kaato-asiakkaan toimitustavaksi laitetaan nouto, koska tarvitsemme validin toimitustavan
			$query_toimitustapa = "	SELECT *
									FROM toimitustapa
									WHERE yhtio = '{$yhtio}'
									AND selite LIKE '%Oletus%'
									LIMIT 1";
			$result_toimitustapa = pupe_query($query_toimitustapa);
			$toimitustapa_row = mysql_fetch_assoc($result_toimitustapa);
			//kaato-asiakasta ei ole olemassa, luodaan kaato-asiakas
			$query2 = "	INSERT INTO asiakas
						SET yhtio = '{$yhtio}',
						nimi ='Kaato asiakas',
						asiakasnro = 'kaato_asiakas',
						maksuehto = '{$maksuehto_row['tunnus']}',
						toimitustapa = '{$toimitustapa_row['tunnus']}',
						laatija = 'konversio',
						luontiaika = NOW()";
			pupe_query($query2);
		}

		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}

	function paivita_tilauksen_tiedot($tilausnumero, $myyntilasku, $yhtio) {
		$query = "	UPDATE lasku
					SET nimi = '{$myyntilasku['asiakkaan_nimi']}',
					tapvm = '{$myyntilasku['tapahtumapaiva']}',
					summa = '{$myyntilasku['summa']}',
					maksuehto = '{$myyntilasku['maksuehto']}',
					erpcm = '{$myyntilasku['erapaiva']}',
					viite = '{$myyntilasku['viite']}',
					tila = 'U'
					WHERE yhtio = '{$yhtio}'
					AND tunnus = '{$tilausnumero}'";
		pupe_query($query);
	}

	function tee_tiliointi($tilausnumero, $tiliointi, $yhtio) {
		$tili = tarkista_tilinumero($tiliointi['tilinumero'], $yhtio);

		if(!empty($tili)) {
			$query = "	INSERT INTO tiliointi
						SET tilino = '{$tiliointi['tilinumero']}',
						tapvm = '{$tiliointi['tapahtumapaiva']}',
						summa = '{$tiliointi['summa']}',
						vero = '{$tiliointi['alv']}',
						kustp = '{$tiliointi['kustp']}',
						ltunnus = '{$tilausnumero}',
						yhtio = '{$yhtio}'";
			pupe_query($query);

			return mysql_insert_id();
		}
		return null;
	}

	function tarkista_tilinumero($tilinumero, $yhtio) {
		$query = "	SELECT *
					FROM tili
					WHERE yhtio = '{$yhtio}'
					AND tilino = '{$tilinumero}'";
		$result = pupe_query($query);

		$tilinumero_row = mysql_fetch_assoc($result);
		if($tilinumero_row) {
			return $tilinumero_row;
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
//		echo $query;
//		echo "<br/><br/>";
		pupe_query($query);

		$query2 = "	DELETE
					FROM tiliointi
					WHERE yhtio = '{$yhtio}'
					AND tunnus IN (".implode(',', $kasitellyt_tilaukset['tiliointi_tunnukset']).")";
//		echo $query2;
//
		pupe_query($query2);
	}
?>