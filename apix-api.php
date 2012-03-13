#!/usr/bin/php
<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	$pupesoft_polku = dirname(__FILE__);

	// otetaan tietokanta connect
	require ($pupesoft_polku."/inc/connect.inc");

	if (!isset($verkkolaskut_in) or $verkkolaskut_in == "" or !is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) {
		// VIRHE: verkkolaskut_in-kansio ei ole määritelty!
		// Ei echota mitään, niin tän voi laittaa aina croniin
		exit;
	}

	function apix_receive ($apix_keys) {

		// Asetukset
		$software = "Pupesoft";
		$version  = "1.0";

		#$url = "https://test-terminal.apix.fi/receive";
		$url = "https://terminal.apix.fi/receive";

		$timestamp	= gmdate("YmdHis");

		// Muodostetaan apixin vaatima salaus ja url
		$digest_src = "$software+$version+".$apix_keys['apix_tunnus']."+".$timestamp."+".$apix_keys['apix_avain'];
		$dt	= substr(hash("sha256", $digest_src), 0, 64);
		$real_url = "$url?TraID={$apix_keys['apix_tunnus']}&t=$timestamp&soft=$software&ver=$version&d=SHA-256:$dt";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $real_url);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	// Haetaan api_keyt yhtion_parametreistä
	$sql_query = "	SELECT yhtion_parametrit.apix_tunnus, yhtion_parametrit.apix_avain, yhtio.nimi
					FROM yhtio
					JOIN yhtion_parametrit USING (yhtio)
					WHERE yhtion_parametrit.apix_tunnus != ''
					AND yhtion_parametrit.apix_avain != ''";
	$apix_result = mysql_query($sql_query) or die("Virhe SQL kyselyssä");

	while ($apix_keys = mysql_fetch_assoc($apix_result)) {

		while (($response = apix_receive($apix_keys)) != "") {

			// Randomstringi filenimiin
			$apix_nimi = md5(uniqid(mt_rand(), true));

			// Luodaan temppidirikka jonne työnnetään tän haun kaikki apixfilet
			$apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

			if (mkdir($apix_tmpdirnimi)) {

				$tiedosto = $apix_tmpdirnimi."/apix_laskupaketti.zip";
				$fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu luoda!");
				fwrite($fd, $response);

				$zip = new ZipArchive();

				if ($zip->open($tiedosto) === TRUE) {

					if ($zip->extractTo($apix_tmpdirnimi)) {

						// Loopataan tiedostot läpi
						for ($i = 0; $i < $zip->numFiles; $i++) {

							$file = $zip->getNameIndex($i);

							if (strtoupper(substr($file, -4)) == ".XML") {
								// Tämä on itse verkkolaskuaineisto
								rename($apix_tmpdirnimi."/".$file, $verkkolaskut_in."/apix_".$apix_nimi."_apix-$file");

								echo "Haettiin lasku yritykselle: {$apix_keys['nimi']}\n";
							}
							else {
								// Nämä ovat liitteitä
								rename($apix_tmpdirnimi."/".$file, $verkkolaskut_orig."/apix_".$apix_nimi."_apix-$file");
							}
						}
					}
				}

				fclose($fd);

				// Poistetaan apix-tmpdir
				exec("rm -rf $apix_tmpdirnimi");
			}
			else {
				echo "Virhe APIX-tiedoston purkamisessa!\n";
			}
		}
	}

