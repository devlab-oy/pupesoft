<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhtiötä!\n";
		exit;
	}

	require ("inc/salasanat.php");
	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$kukarow['yhtio'] = (string) $argv[1];
	$kukarow['kuka'] = 'cron';
	$kukarow['kieli'] = 'fi';

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	// kansiot laitetaan salasanat.php
	// $unifaun_fetch_folder 		= "/Users/sami/temp/unifaun/export";			// muuta näiden polut oikeiksi
	// $unifaun_fetch_folder_ok	= "/Users/sami/temp/unifaun/export/ok";	// muuta näiden polut oikeiksi

	// $folder_error 	= "/Users/sami/temp/unifaun/export/error";		// muuta näiden polut oikeiksi

	if ($handle = opendir($unifaun_fetch_folder)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($unifaun_fetch_folder."/".$file)) {

				$tiedosto = $unifaun_fetch_folder."/".$file;

				list($tilausnumero_sscc, $sscc_ulkoinen, , $timestamp, $_sscc) = explode(";", file_get_contents($tiedosto));

				list($tilausnumero, $sscc) = explode("_", $tilausnumero_sscc);

				$query = "	UPDATE kerayserat SET
							sscc_ulkoinen = '{$sscc_ulkoinen}',
							muutospvm = now(),
							muuttaja = 'cron'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND sscc = '{$sscc}'
							AND otunnus = '{$tilausnumero}'";
				$upd_res = pupe_query($query);

				rename($unifaun_fetch_folder."/".$file, $unifaun_fetch_folder_ok."/".$file);
			}
		}
	}


