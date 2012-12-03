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
	$yhtio = 'artr';

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
		foreach($myyntilaskut as $myyntilasku) {
			$tilausnumero = luo_myyntitilausotsikko('', $myyntilasku['asiakasnumero']);

			if($tilausnumero) {
				paivita_tilauksen_tiedot($tilausnumero, $myyntilasku, $yhtio);
				$tilausnumero_count++;

				foreach($myyntilasku['tilioinnit'] as $tiliointi) {
					$success = tee_tiliointi($tilausnumero,$tiliointi, $yhtio);
					if($success) {
						$tiliointi_count++;
					}
				}
			}
		}

		return array(
			'tilausnumero_count' => $tilausnumero_count,
			'tiliointi_count' => $tiliointi_count,
		);
	}

	function paivita_tilauksen_tiedot($tilausnumero, $myyntilasku, $yhtio) {
		$query = "	UPDATE lasku
					SET nimi = '{$myyntilasku['asiakkaan_nimi']}'
					tapvm = '{$myyntilasku['tapahtumapaiva']}',
					summa = '{$myyntilasku['summa']}',
					maksuehto = '{$myyntilasku['maksuehto']}',
					erpcm = '{$myyntilasku['erapaiva']}',
					viite = '{$myyntilasku['viite']}',
					tila = 'U',
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

			return true;
		}
		return false;
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
?>