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

	$folder 		= "/Users/sami/temp/unifaun/export";			// muuta näiden polut oikeiksi
	$folder_ok	 	= "/Users/sami/temp/unifaun/export/ok";	// muuta näiden polut oikeiksi
	$folder_error 	= "/Users/sami/temp/unifaun/export/error";		// muuta näiden polut oikeiksi

	if ($handle = opendir($folder)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($folder."/".$file)) {

				$tiedosto = $folder."/".$file;

				list($tilausnumero, $sscc_ulkoinen, $sscc, $timestamp) = explode(";", file_get_contents($tiedosto));

				$query = "	UPDATE kerayserat SET
							sscc_ulkoinen = '{$sscc_ulkoinen}',
							muutospvm = now(),
							muuttaja = 'cron'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND sscc = '{$sscc}'
							AND otunnus = '{$tilausnumero}'";
				$upd_res = pupe_query($query);

				unlink($folder."/".$file);
			}
		}
	}


