<?php
	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if (!$php_cli) {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}
	else {
		if (trim($argv[1]) == '') {
			echo "Et antanut yhtiötä!\n";
			exit;
		}

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		$kukarow['yhtio'] = (string) $argv[1];
		$kukarow['kuka']  = 'cron';
		$kukarow['kieli'] = 'fi';

		if (trim($argv[2]) != '') {
			$kukarow['kuka'] = trim($argv[2]);
		}

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$query = "	SELECT *
					FROM keraysvyohyke
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND ulkoinen_jarjestelma = 'K'";
		$gen_ker_res_result = pupe_query($query);

		while ($gen_ker_row = mysql_fetch_assoc($gen_ker_res_result)) {

			// HUOM!!! FUNKTIOSSA TEHDÄÄN LOCK TABLESIT, LUKKOJA EI AVATA TÄSSÄ FUNKTIOSSA! MUISTA AVATA LUKOT FUNKTION KÄYTÖN JÄLKEEN!!!!!!!!!!
			$erat = tee_keraysera($gen_ker_row["tunnus"], $gen_ker_row["varasto"], FALSE);

			if (count($erat['tilaukset']) > 0) {

				// Tallennetaan keröyserä
				require('inc/tallenna_keraysera.inc');

				$otunnukset = implode(",", $erat['tilaukset']);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus IN ($otunnukset)";
				$res = pupe_query($query);
				$laskurow = mysql_fetch_assoc($res);

				$tilausnumeroita  	  = $otunnukset;
				$valittu_tulostin 	  = $gen_ker_row['printteri0'];
				$tullaan_kerayserasta = 'joo';
				$laskuja 			  = count($erat['tilaukset']);
				$lukotetaan 		  = FALSE;

				require("tilauskasittely/tilaus-valmis-tulostus.inc");
			}

			// lukitaan tableja
			$query = "UNLOCK TABLES";
			$result = pupe_query($query);

			if (count($erat['tilaukset']) > 0) {

				$komento['reittietiketti'] = $gen_ker_row['printteri8'];

				// Tulostetaan kollilappu
				require('inc/tulosta_reittietiketti.inc');

				if ($erat['keraysvyohyketiedot']['ulkoinen_jarjestelma'] == "K") {
					require("inc/kardex_send.inc");
				}
			}
		}
	}

?>