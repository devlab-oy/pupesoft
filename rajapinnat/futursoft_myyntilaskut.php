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
				$myyntilasku_data = parsi_xml_tiedosto($xml, $yhtio);

				$kasitellyt_tilaukset = kasittele_myyntilasku_data($myyntilasku_data, $yhtio);

				echo $kasitellyt_tilaukset['tilausnumero_count'] . t(" tilausta luotiin ja niihin ") . $kasitellyt_tilaukset['tiliointi_count'] . t(" tiliöintiä");

				siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);

				//Testausta varten
				//poista_tilaukset_ja_tilioinnit($kasitellyt_tilaukset, $yhtio);
			}
			else {
				die(t("Tiedostoa ei ole olemassa"));
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
						if(stristr($tiedosto, 'myynti')) {
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

	function parsi_xml_tiedosto(SimpleXMLElement $xml, $yhtio) {
		$data = array();
		if ($xml !== FALSE) {
			foreach($xml->LedgerJournalTable->LedgerJournalTrans as $myyntilasku) {
				if($myyntilasku->AccountType == 'Cust') {
					//Myyntilasku otsikko
					$lasku_numero = (string)$myyntilasku->Invoice;
					$data[$lasku_numero] = array(
						'laskunro' => preg_replace( '/[^0-9]/', '', $lasku_numero),
						'asiakasnumero' => (string)$myyntilasku->AccountNum,
						'siirtopaiva' => (string)$myyntilasku->TransDate,
						'tapahtumapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->DocumentDate)),
						'asiakkaan_nimi' => utf8_decode((string)$myyntilasku->Txt),
						'summa' => ((string)$myyntilasku->AmountCurDebit == '') ? ((float)$myyntilasku->AmountCurCredit * -1) : (float)$myyntilasku->AmountCurDebit,
						'valuutta' => (string)$myyntilasku->Currency,
						'kurssi' => (float)$myyntilasku->ExchRate,
						'maksuehto' => konvertoi_maksuehto($myyntilasku->Payment, $yhtio),
						'erapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->Due)),
						'viite' => (string)$myyntilasku->PaymId,
					);
				}
				else if ($myyntilasku->AccountType == 'Ledger') {
					$data[$lasku_numero]['tilioinnit'][] = array(
						'tilinumero' => (string)$myyntilasku->AccountNum,
						'siirtopaiva' => (string)$myyntilasku->TransDate,
						'tapahtumapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->DocumentDate)),
						'asiakkaan_nimi' => utf8_decode((string)$myyntilasku->Txt),
						'summa' => ((string)$myyntilasku->AmountCurDebit == '') ? ((float)$myyntilasku->AmountCurCredit * -1) : (float)$myyntilasku->AmountCurDebit,
						'valuutta' => (string)$myyntilasku->Currency,
						'kurssi' => (string)$myyntilasku->ExchRate,
						'alv' => ((string)$myyntilasku->TaxItemGroup == '') ? 0 : (float)$myyntilasku->TaxItemGroup,
						'alv_maara' => (float)$myyntilasku->FixedTaxAmount,
						'kustp' => (string)$myyntilasku->Dim2,
						'maksuehto' => konvertoi_maksuehto($myyntilasku->Payment, $yhtio),
						'erapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->Due)),
						'viite' => (string)$myyntilasku->PaymId,
					);

					//laitetaan vero myös laskuotsikolle
					if(!isset($data[$lasku_numero]['alv'])) {
						$data[$lasku_numero]['alv'] = ((string)$myyntilasku->TaxItemGroup == '') ? 0 : (float)$myyntilasku->TaxItemGroup;
					}
				}
			}
		}

		return $data;
	}

	function konvertoi_maksuehto($maksuehto, $yhtio) {
		$maksuehto = (string)$maksuehto;
		
		if($yhtio == 'atarv') {
			//selitteellä ei tehdä mitään, debuggausta varten
			$maksuehto_array = array(
				'931' => array('id' => '1494','selite' => '14 pv -3% 60 pv netto'),
				'934' => array('id' => '1496','selite' => '60 pv netto'),
				'933' => array('id' => '1500','selite' => '45 pv netto'),
				'908' => array('id' => '1513','selite' => 'Lasku 30 pv'),
				'912' => array('id' => '1514','selite' => '14 pv -2% 30 pv netto'),
				'907' => array('id' => '1515','selite' => 'Lasku 21 pv'),
				'906' => array('id' => '1520','selite' => 'Lasku 14 pv'),
				'932' => array('id' => '1525','selite' => '30 pv -2% 60 pv netto'),
				'904' => array('id' => '1535','selite' => 'Lasku 7pv'),
				'936' => array('id' => '1538','selite' => '90 pv netto'),
				'901' => array('id' => '1551','selite' => 'Käteinen'),
				'909' => array('id' => '2019','selite' => 'Lasku 14 pv netto, 7 pv - 2 %'),
			);

			if(array_key_exists($maksuehto, $maksuehto_array)) {
				return (int)$maksuehto_array[$maksuehto]['id'];
			}
			else {
				echo t("Maksuehdolle").' '.$maksuehto.' '.t("ei löytynyt paria. Käytetään alkuperäistä maksuehtoa");
				return (int)$maksuehto;
			}
		}
		else {
			return (int)$maksuehto;
		}
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
				$tilausnumero_count++;

				foreach($myyntilasku['tilioinnit'] as $tiliointi) {
					$tiliointi_tunnukset = tee_tiliointi($tilausnumero, $tiliointi, $yhtio);
					if($tiliointi_tunnukset) {
						foreach($tiliointi_tunnukset as $tunnus) {
							$tiliointi_tunnukset[] = $tunnus;
							$tiliointi_count++;
							echo t("Tilaukselle").' '.$tilausnumero.' '.t("tehtiin tiliöinti").' '.$tunnus;
							echo "<br/>";
						}
					}
					else {
						echo t("Tilaukselle").' '.$tilausnumero.' '.t("EI TEHTY TILIÖINTIÄ");
						echo "<br/>";
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
		$yhtio_row = hae_yhtio($yhtio);

		$query = "	INSERT INTO lasku
					SET yhtio = '{$yhtio}',
					yhtio_nimi = '{$yhtio_row['nimi']}',
					yhtio_osoite = '{$yhtio_row['osoite']}',
					yhtio_postino = '{$yhtio_row['postino']}',
					yhtio_postitp = '{$yhtio_row['postitp']}',
					yhtio_maa = '{$yhtio_row['maa']}',
					nimi = '{$asiakas['nimi']}',
					osoite = '{$asiakas['osoite']}',
					postino = '{$asiakas['postino']}',
					postitp = '{$asiakas['postitp']}',
					maa = '{$asiakas['maa']}',
					toim_nimi = '{$asiakas['nimi']}',
					toim_osoite = '{$asiakas['osoite']}',
					toim_postino = '{$asiakas['postino']}',
					toim_postitp = '{$asiakas['postitp']}',
					toim_maa = '{$asiakas['maa']}',
					ytunnus = '{$asiakas['ytunnus']}',
					valkoodi = '{$myyntilasku['valuutta']}',
					summa = '{$myyntilasku['summa']}',
					summa_valuutassa = '{$myyntilasku['summa']}',
					vienti_kurssi = 1,
					laatija = 'futursoft',
					luontiaika = NOW(),
					viite = '{$myyntilasku['viite']}',
					laskunro = '{$myyntilasku['laskunro']}',
					maksuehto = '{$myyntilasku['maksuehto']}',
					tapvm = '{$myyntilasku['tapahtumapaiva']}',
					erpcm = '{$myyntilasku['erapaiva']}',
					lapvm = '{$myyntilasku['tapahtumapaiva']}',
					toimaika = '{$myyntilasku['tapahtumapaiva']}',
					kerayspvm = '{$myyntilasku['tapahtumapaiva']}',
					alv = '{$myyntilasku['alv']}',
					tila = 'U',
					alatila = 'X'";
		pupe_query($query);
		$tilausnumero = mysql_insert_id();

		//tehdään myyntisaamiset tiliöinti
		$myyntisaamiset_array = array(
			'tilinumero' => $yhtio_row['myyntisaamiset'],
			'tapahtumapaiva' => $myyntilasku['tapahtumapaiva'],
			'summa' => $myyntilasku['summa'],
			'alv' => $myyntilasku['alv'],
			'kustp' => 0,
		);
		tee_tiliointi($tilausnumero, $myyntisaamiset_array, $yhtio);

		if($asiakas['asiakasnro'] == 'kaato_asiakas') {

			if($tilausnumero) {
				$query = "	UPDATE lasku
							SET comments = '".t("Laskun asiakasta ei löytynyt, käytimme laskun konversioon väliaikaista kaato-asiakasta. Korjaa tämä lasku mahdollisimman pian. Alkuperäisen laskun tiedot löytyvät sisviesti2")."',
							sisviesti2 = '".@json_encode($myyntilasku)."'
							WHERE yhtio = '{$yhtio}'
							AND tunnus = '{$tilausnumero}'";
				pupe_query($query);
			}

			echo t("Laskulle").' '.$tilausnumero.' laskunumero '.$myyntilasku['laskunro'].' '.t("jouduttiin liittämään kaato-asiakas, koska laskun asiakasta ei löytynyt tietokannasta.");
			echo "<br/>";
			echo "<pre>";
			echo $myyntilasku['asiakkaan_nimi'];
			echo "<br/>";
			echo $myyntilasku['asiakasnumero'];
			echo "</pre>";
		}
		else {

			echo t("Laskulle").' '.$tilausnumero.' laskunumero '.$myyntilasku['laskunro'].' '.t("ei lisätty kaatoasiakasta.");
			echo "<br/>";
			echo "<pre>";
			echo $myyntilasku['asiakkaan_nimi'];
			echo "<br/>";
			echo $myyntilasku['asiakasnumero'];
			echo "</pre>";
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
						ytunnus = 'kaato_asiakas',
						maksuehto = '{$maksuehto_row['tunnus']}',
						toimitustapa = '{$toimitustapa_row['tunnus']}',
						laatija = 'konversio',
						luontiaika = NOW()";
			pupe_query($query2);
		}

		$result = pupe_query($query);
		
		return mysql_fetch_assoc($result);
	}

	function tee_tiliointi($tilausnumero, $tiliointi, $yhtio) {
		$tiliointi_tunnukset = array();
		$tili = tarkista_tilinumero($tiliointi['tilinumero'], $yhtio);

		if($tiliointi['summa'] == 139.11) {
			$shit = "fit";
		}

		if(!empty($tili)) {
			if(!empty($tiliointi['alv']) and !empty($tiliointi['alv_maara'])) {
				//tehdään alv tiliöinti ja tiliöinti - alv
				if($tiliointi['summa'] < 0) {
					$alviton_summa = $tiliointi['summa'] + $tiliointi['alv_maara'];
				}
				else {
					$alviton_summa = $tiliointi['summa'] - $tiliointi['alv_maara'];
				}

				$yhtio_row = hae_yhtio($yhtio);

				$query = "	INSERT INTO tiliointi
							SET tilino = '{$tiliointi['tilinumero']}',
							tapvm = '{$tiliointi['tapahtumapaiva']}',
							summa = '{$alviton_summa}',
							vero = '{$tiliointi['alv']}',
							kustp = '{$tiliointi['kustp']}',
							ltunnus = '{$tilausnumero}',
							laatija = 'futursoft',
							laadittu = NOW(),
							selite = '".t("Futursoft konversioajo")."',
							yhtio = '{$yhtio}'";
				pupe_query($query);

				$tiliointi_tunnukset[] = mysql_insert_id();

				if($tiliointi['summa'] < 0) {
					$alv_maara = $tiliointi['alv_maara'] * -1;
				}
				else {
					$alv_maara = $tiliointi['alv_maara'];
				}
				$query = "	INSERT INTO tiliointi
							SET tilino = '{$yhtio_row['alv']}',
							tapvm = '{$tiliointi['tapahtumapaiva']}',
							summa = '{$alv_maara}',
							vero = '{$tiliointi['alv']}',
							kustp = '{$tiliointi['kustp']}',
							ltunnus = '{$tilausnumero}',
							laatija = 'futursoft',
							laadittu = NOW(),
							selite = '".t("Futursoft konversioajo")."',
							yhtio = '{$yhtio}'";
				pupe_query($query);

				$tiliointi_tunnukset[] = mysql_insert_id();
			}
			else {
				//tehdään tiliöinti sellaisenaan
				$query = "	INSERT INTO tiliointi
							SET tilino = '{$tiliointi['tilinumero']}',
							tapvm = '{$tiliointi['tapahtumapaiva']}',
							summa = '{$tiliointi['summa']}',
							vero = '{$tiliointi['alv']}',
							kustp = '{$tiliointi['kustp']}',
							ltunnus = '{$tilausnumero}',
							laatija = 'futursoft',
							laadittu = NOW(),
							selite = '".t("Futursoft konversioajo")."',
							yhtio = '{$yhtio}'";
				pupe_query($query);

				$tiliointi_tunnukset[] = mysql_insert_id();
			}

			return $tiliointi_tunnukset;
		}
		else {
			echo t("Tilinumeroa").' '.$tiliointi['tilinumero'].' '.t("EI LÖYTYNYT");
			echo "<br/>";
			return null;
		}
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
		pupe_query($query);

		$query2 = "	DELETE
					FROM tiliointi
					WHERE yhtio = '{$yhtio}'
					AND tunnus IN (".implode(',', $kasitellyt_tilaukset['tiliointi_tunnukset']).")";
		pupe_query($query2);
	}

	function hae_yhtio($yhtio) {
		$query = "	SELECT *
					FROM yhtio
					WHERE yhtio = '{$yhtio}'";
		$result = pupe_query($query);

		if(mysql_num_rows($result) == 0) {
			return null;
		}

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