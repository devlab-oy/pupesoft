<?php

	$query = "	SELECT otunnus, tilausrivi, sscc, sscc_ulkoinen, kpl, tunnus
				FROM kerayserat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND nro 	= '{$kerayseran_numero}'
				AND tila    = 'X'";
	$kardex_res = pupe_query($query);

	if (mysql_num_rows($kardex_res) > 0) {

		$sisalto   = "";
		$kardex_ok = TRUE;

		while ($kardex_row = mysql_fetch_assoc($kardex_res)) {

			$query = "	SELECT toimitustapa
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$kardex_row['otunnus']}'";
			$lahto_chk_res = pupe_query($query);
			$lahto_chk_row = mysql_fetch_assoc($lahto_chk_res);

			// haetaan toimitustavan tiedot
			$query = "	SELECT rahtikirja
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite  = '{$lahto_chk_row['toimitustapa']}'";
			$toitares = pupe_query($query);
			$toitarow = mysql_fetch_assoc($toitares);

			$kardex_sscc_sisalto = $kardex_row['sscc'];

			if (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") OR
				($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {

				// Jos Unifaun on käytössä niin ulkoinen SSCC ei saa olla tyhjä
				if ((is_numeric($kardex_row['sscc_ulkoinen']) and (int) $kardex_row['sscc_ulkoinen'] == 0) or (!is_numeric($kardex_row['sscc_ulkoinen']) and (string) $kardex_row['sscc_ulkoinen'] == "")) {
					$kardex_ok = FALSE;
					break;
				}

				$kardex_sscc_sisalto = $kardex_row['sscc_ulkoinen'];
			}

			$query = "	SELECT tunnus, tuoteno, nimitys, hyllyalue, CONCAT(hyllynro, '-', hyllyvali, '-', hyllytaso) AS hyllypaikka
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$kardex_row['tilausrivi']}'";
			$tilriv_tiedot_res = pupe_query($query);
			$tilriv_tiedot_row = mysql_fetch_assoc($tilriv_tiedot_res);

			// index 0, tilaustyyppi = aina 4
			// index 1, sscc
			// index 2, tuoteno
			// index 3, nimitys
			// index 4, kpl
			// index 5, hyllypaikka
			// index 6, keräyserärivin tunnus

			$kardex_row['kpl'] = str_replace(".", ",", $kardex_row['kpl']);

			$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$tilriv_tiedot_row["hyllyalue"]}", $yhtiorow['varastontunniste']) : $tilriv_tiedot_row["hyllyalue"];

			$hyllypaikka = "{$hyllyalue_value}-{$tilriv_tiedot_row['hyllypaikka']}";

			$sisalto .= "4;{$kardex_sscc_sisalto};{$tilriv_tiedot_row['tuoteno']};{$tilriv_tiedot_row['nimitys']};-{$kardex_row['kpl']};{$hyllypaikka};{$kardex_row['tunnus']}\r\n";
		}

		if ($kardex_ok) {

			// Remmataan faili $kardex_sscc-kansiosta jos se on siellä
			if (file_exists($kardex_sscc."/".$kerayseran_numero.".txt")) {
				rename($kardex_sscc."/".$kerayseran_numero.".txt", $kardex_sscc."/ok/".$kerayseran_numero.".txt");
			}

			$kardexnimi = "/tmp/kardex-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($kardexnimi, "w+");
			fputs($fh, $sisalto);
			fclose($fh);

			$ftphost = $kardex_host;
			$ftpuser = $kardex_user;
			$ftppass = $kardex_pass;
			$ftppath = $kardex_path;
			$ftpport = $kardex_port;
		    $ftpfail = $kardex_fail;
			$ftpsucc = $kardex_succ;
			$ftpfile = $kardexnimi;

			require('inc/ftp-send.inc');
		}
		elseif ($kardex_sscc != "") {
			// Katotaan, ettei tätä ole jo siirretty uudelleenkäsiteltävien joukkoon
			if (!file_exists($kardex_sscc."/".$kerayseran_numero.".txt")) {
				// Työnnetään uudelleenkäsiteltävien joukkoon jos ulkoinen SSCC puuttuu
				$kardexnimi = $kardex_sscc."/".$kerayseran_numero.".txt";
				$fh = fopen($kardexnimi, "w+");
				fputs($fh, $kerayseran_numero);
				fclose($fh);
			}
		}
	}
