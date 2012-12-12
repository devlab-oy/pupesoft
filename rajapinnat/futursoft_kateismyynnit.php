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

	//TESTAUSTA VARTEN
	$tiedosto_polku = '/tmp/KPAM_kateis(1334).xml';
	$yhtio = 'atarv';
	
	$futursoft_kansio = "/home/merca-autoasi/";
	$futursoft_kansio_valmis = "/home/merca-autoasi/ok/";
	$futursoft_kansio_error = "/home/merca-autoasi/error/";

//	$futursoft_kansio = "/tmp/merca-autoasi/";
//	$futursoft_kansio_valmis = "/tmp/merca-autoasi/ok/";
//	$futursoft_kansio_error = "/tmp/merca-autoasi/error/";

	if(!is_dir($futursoft_kansio)) {
		mkdir($futursoft_kansio);
	}
	if(!is_dir($futursoft_kansio_valmis)) {
		mkdir($futursoft_kansio_valmis);
	}
	if(!is_dir($futursoft_kansio_error)) {
		mkdir($futursoft_kansio_error);
	}

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
				$kateismyynti_data = parsi_xml_tiedosto($xml);

				echo count($kateismyynti_data).' '.t("kateismyyntiä löytyi");
				echo "<br/>";

				$kasitellyt_kateismyynnit = kasittele_kateismyynnit_data($kateismyynti_data, $yhtio);

				echo $kasitellyt_kateismyynnit['tilausnumero_count'] . t(" tilausta luotiin ja niihin ") . $kasitellyt_kateismyynnit['tiliointi_count'] . t(" tiliöintiä");

				siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);

				//Testaamista varten
				//poista_tilaukset_ja_tilioinnit($kasitellyt_kateismyynnit, $yhtio);
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
						if(stristr($tiedosto, 'kateis')) {
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

		exec('rm "'.$tiedosto_polku.'"');
	}

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
				$tilausnumero_count++;

				echo t("Käteislasku").' '.$tilausnumero.' '.t("luotiin");
				echo "<br/>";

				foreach($kateismyynnin_osat as $kateismyynti) {
					$tiliointi_tunnukset = tee_tiliointi($tilausnumero, $kateismyynti, $yhtio);
					if($tiliointi_tunnukset) {
						foreach($tiliointi_tunnukset as $tunnus) {
							$tiliointi_idt[] = $tunnus;
							$kateismyynti_count++;
						}

						echo t("Käteislaskulle").' '.$tilausnumero.' '.t("luotiin tiliöinti").' '.$tunnus;
						echo "<br/>";
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
					valkoodi = '{$kateismyynnin_osat[0]['valuutta']}',
					summa = '{$kateismyynnin_osat[0]['summa']}',
					laatija = 'futursoft',
					luontiaika = NOW(),
					tapvm = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
					tila = 'U',
					alatila = 'X'";
		pupe_query($query);

		return mysql_insert_id();
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

		return mysql_fetch_assoc($result);
	}

	function tee_tiliointi($tilausnumero, $kateismyynti, $yhtio) {
		$kassalipas = tarkista_tilinumero($kateismyynti['kustp'], $yhtio);
		$tiliointi_tunnukset = array();

		if(empty($kassalipas)) {
			if($yhtio == 'atarv') {
				//kustp 2000 on yleinen fail-safe kustannuspaikka
				$kassalipas = tarkista_tilinumero('2000', $yhtio);

				echo t("Kassalippasta kustannuspaikalle").' '.$kateismyynti['kustp'].' '.t("ei ole olemassa").' '.$kassalipas['kassa'].' '.t("tiliöinti tehdään yleiselle kassalippaalle kustp 2000 tili 18110");
				echo "<br/>";
			}
			else {
				echo t("Kassalippasta kustannuspaikalle").' '.$kateismyynti['kustp'].' '.t("ei ole olemassa").' '.$kassalipas['kassa'].' '.t("tiliöintiä ei voitu perustaa");
				echo "<br/>";

				return null;
			}
		}
		if(!empty($kateismyynti['alv']) and !empty($kateismyynti['alv_maara'])) {
			//tehdään alv tiliöinti ja tiliöinti - alv
			$alviton_summa = $kateismyynti['summa'] - $kateismyynti['alv_maara'];
			$yhtio_row = hae_yhtio($yhtio);

			$query = "	INSERT INTO tiliointi
						SET tilino = '{$kassalipas['kassa']}',
						tapvm = '{$kateismyynti['tapahtumapaiva']}',
						summa = '{$alviton_summa}',
						vero = '{$kateismyynti['alv']}',
						kustp = '{$kateismyynti['kustp']}',
						ltunnus = '{$tilausnumero}',
						laatija = 'futursoft',
						laadittu = NOW(),
						selite = '{$kateismyynti['selite']}',
						yhtio = '{$yhtio}'";
			pupe_query($query);

			$tiliointi_tunnukset[] = mysql_insert_id();

			$query = "	INSERT INTO tiliointi
						SET tilino = '{$yhtio_row['alv']}',
						tapvm = '{$kateismyynti['tapahtumapaiva']}',
						summa = '{$kateismyynti['alv_maara']}',
						vero = '{$kateismyynti['alv']}',
						kustp = '{$kateismyynti['kustp']}',
						ltunnus = '{$tilausnumero}',
						laatija = 'futursoft',
						laadittu = NOW(),
						selite = '".t("Alv tiliöinti")."',
						yhtio = '{$yhtio}'";
			pupe_query($query);

			$tiliointi_tunnukset[] = mysql_insert_id();
		}
		else {
			//tehdään kate tiliöinti
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

			$tiliointi_tunnukset[] = mysql_insert_id();
		}

		echo t("Tiliöinti laskulle").' '.$tilausnumero.' '.t("luotiin kassalipas:").' '.$kassalipas['kassa'];
		echo "<br/>";

		return $tiliointi_tunnukset;
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