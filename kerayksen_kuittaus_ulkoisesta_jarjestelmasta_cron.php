<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
	}

	date_default_timezone_set('Europe/Helsinki');

	if (trim($argv[1]) == '') {
		die ("Et antanut lähettävää yhtiötä!\n");
	}

	if (trim($argv[2]) == '') {
		die ("Et antanut luettavien tiedostojen polkua!\n");
	}

	// lisätään includepathiin pupe-root
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	ini_set("memory_limit", "2G");

	// otetaan tietokanta connect ja funktiot
	require("inc/connect.inc");
	require("inc/functions.inc");

	// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
	pupesoft_flock();

	$yhtio = mysql_escape_string(trim($argv[1]));
	$yhtiorow = hae_yhtion_parametrit($yhtio);

	// Haetaan kukarow
	$query = "	SELECT *
				FROM kuka
				WHERE yhtio = '{$yhtio}'
				AND kuka = 'admin'";
	$kukares = pupe_query($query);

	if (mysql_num_rows($kukares) != 1) {
		exit("VIRHE: Admin käyttäjä ei löydy!\n");
	}

	$kukarow = mysql_fetch_assoc($kukares);

	$path = trim($argv[2]);
	$path = strrpos($path, '/', -1) === false ? $path.'/' : $path;

	if ($handle = opendir($path)) {

		while (false !== ($file = readdir($handle))) {

			if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

			$path_parts = pathinfo($file);
			$ext = strtoupper($path_parts['extension']);

			if ($ext == 'XML') {

				$filehandle = fopen($path.$file, "r");
				$contents = fread($filehandle, filesize($path.$file));

				$xml = simplexml_load_string($contents);

				if (is_object($xml)) {

					if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType) and $xml->MessageHeader->MessageType == 'OutboundDeliveryConfirmation') {

						$otunnus = (int) $xml->CustPackingSlip->SalesId;
						$toimaika = $xml->CustPackingSlip->DeliveryDate;
						$toimitustavan_tunnus = (int) $xml->CustPackingSlip->TransportAccount;

						$tuotteiden_paino = 0;

						foreach ($xml->CustPackingSlip->Lines as $line) {

							$tilausrivin_tunnus = (int) $line->TransId;
							$eankoodi = mysql_escape_string($line->ItemNumber);
							$keratty = (float) $line->DeliveredQuantity;

							$query = "	UPDATE tilausrivi
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.eankoodi = '{$eankoodi}' AND tuote.tuoteno = tilausrivi.tuoteno)
										SET tilausrivi.keratty = '{$kukarow['kuka']}',
										tilausrivi.kerattyaika = '{$toimaika} 00:00:00',
										tilausrivi.toimitettu = '{$kukarow['kuka']}',
										tilausrivi.toimitettuaika = '{$toimaika} 00:00:00',
										tilausrivi.varattu = '{$keratty}'
										WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi.tunnus = '{$tilausrivin_tunnus}'";
							pupe_query($query);

							$query = "	SELECT SUM(tuote.tuotemassa) paino
										FROM tilausrivi
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
										WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi.tunnus = '{$tilausrivin_tunnus}'";
							$painores = pupe_query($query);
							$painorow = mysql_fetch_assoc($painores);

							$tuotteiden_paino += $painorow['paino'];
						}

						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$otunnus}'";
						$laskures = pupe_query($query);
						$laskurow = mysql_fetch_assoc($laskures);

						$query  = "	INSERT INTO rahtikirjat SET
									kollit 			= 1,
									kilot			= {$tuotteiden_paino},
									pakkaus 		= '',
									pakkauskuvaus 	= '',
									rahtikirjanro 	= '',
									otsikkonro 		= '{$otunnus}',
									tulostuspaikka 	= '{$laskurow['varasto']}',
									yhtio 			= '{$kukarow['yhtio']}',
									viesti			= ''";
						$result_rk = pupe_query($query);


						unlink($path.$file);
					}
				}
			}
		}

		closedir($handle);
	}

