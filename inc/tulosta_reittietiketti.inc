<?php

	if (!is_array($erat) and (!isset($ei_tulosteta) or !$ei_tulosteta)) {
		echo "<br /><font class='error'>",t("VIRHE: Keräyserää ei enää löydy"),"!</font><br />";

		require('inc/footer.inc');
		exit;
	}

	$otunnukset = implode(",", $erat['tilaukset']);

	$tilausrivit = '';
	$pakkaus_kirjaimet = array();

	$kerayseran_numero = 0;

	foreach ($erat as $era_key => $era_arr) {

		if ($era_key == 'pakkaukset') {

			$query = "	SELECT ifnull(max(nro), 0) AS nro 
						FROM kerayserat 
						WHERE yhtio = '{$kukarow['yhtio']}'";
			$nro_fetch_res = pupe_query($query);
			$nro_fetch_row = mysql_fetch_assoc($nro_fetch_res);

			$kerayseran_numero = $nro_fetch_row['nro'] + 1;

			foreach ($era_arr as $pakkaus_nro => $rarr) {

				foreach ($rarr as $juokseva_nro => $pakkaus) {

					$pakkaus_kirjain = chr((64+$juokseva_nro));

					$rivit = 0;
					$paino = 0;
					$tilavuus = 0;

					$kappaleet = 0;

					// emuloidaan transactioita mysql LOCK komennolla
					$query = "LOCK TABLES avainsana WRITE";
					$res   = pupe_query($query);

					$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
					$result = pupe_query($query);
					$row = mysql_fetch_assoc($result);

					$sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

					if (trim($row['selite']) == '') {

						// haetaan aluksi max perhe
						$query = "	SELECT max(perhe)+1 perhe
									FROM avainsana
									WHERE yhtio = '{$kukarow['yhtio']}'";
						$max_perhe_res = pupe_query($query);
						$max_perhe_row = mysql_fetch_assoc($max_perhe_res);

						$query = "	INSERT INTO avainsana SET 
									yhtio = '{$kukarow['yhtio']}',
									perhe = '{$max_perhe_row['perhe']}',
									kieli = '{$kukarow['kieli']}',
									laji = 'SSCC',
									nakyvyys = '',
									selite = '{$sscc}',
									selitetark = '',
									selitetark_2 = '',
									selitetark_3 = '',
									jarjestys = 0,
									laatija = '{$kukarow['kuka']}',
									luontiaika = now(),
									muutospvm = now(),
									muuttaja = '{$kukarow['kuka']}'";
						$insert_res = pupe_query($query);
					}
					else {
						$query = "UPDATE avainsana SET selite = '{$sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
						$update_res = pupe_query($query);
					}

					// poistetaan lukko
					$query = "UNLOCK TABLES";
					$res   = pupe_query($query);

					foreach ($pakkaus as $tilriv => $kpl) {
						$pakkaus_kirjaimet[$tilriv][$pakkaus_kirjain] = $kpl;
						$rivit += 1;
						$tilausrivit .= "$tilriv,";
						$kappaleet += $kpl;

						$query = "	SELECT round((tuote.tuotemassa * (tilausrivi.kpl+tilausrivi.varattu)), 2) as paino, round(((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) * (tilausrivi.kpl+tilausrivi.varattu)), 4) as tilavuus, tilausrivi.otunnus
									FROM tilausrivi
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.tunnus = '{$tilriv}'";
						$paino_res = pupe_query($query);
						$paino_row = mysql_fetch_assoc($paino_res);

						$paino += $paino_row['paino'];
						$tilavuus += $paino_row['tilavuus'];

						$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$paino_row['otunnus']}'";
						$res = pupe_query($query);
						$laskurow = mysql_fetch_assoc($res);

						// tila = 'K' tarkoittaa "Keräyksessä"
						$query = "	INSERT INTO kerayserat SET
									yhtio = '{$kukarow['yhtio']}',
									nro = {$kerayseran_numero},
									tila = 'K',
									sscc = {$sscc},
									otunnus = '{$laskurow['tunnus']}',
									tilausrivi = '{$tilriv}',
									pakkaus = '{$pakkaus_nro}',
									pakkausnro = '{$juokseva_nro}',
									kpl = '{$kpl}',
									laatija = '{$kukarow['kuka']}',
									luontiaika = now(),
									muuttaja = '{$kukarow['kuka']}',
									muutospvm = now()";
						$insert_res = pupe_query($query);
					} #end foreach $pakkaus

					if (!isset($ei_tulosteta) or !$ei_tulosteta) {
						$params = array(
							'tilriv' => $tilriv,
							'pakkaus_kirjain' => $pakkaus_kirjain,
							'sscc' => $sscc,
							'toimitustapa' => $laskurow['toimitustapa'],
							'rivit' => $rivit,
							'paino' => $paino,
							'tilavuus' => $tilavuus,
							'lask_nimi' => $laskurow['nimi'],
							'lask_nimitark' => $laskurow['nimitark'],
							'lask_osoite' => $laskurow['osoite'],
							'lask_postino' => $laskurow['postino'],
							'lask_postitp' => $laskurow['postitp'],
							'lask_viite' => $laskurow['viesti'],
							'lask_merkki' => $laskurow['sisviesti2'],
							'komento_reittietiketti' => $komento['reittietiketti'],
						);

						tulosta_reittietiketti($params);
					}
				} #end foreach $rarr
			} #end foreach $era_arr
		} #end if
	} #end foreach $erat

	if (!isset($ei_tulosteta) or !$ei_tulosteta) {
		if (trim($komento['reittietiketti']) != '') {
			echo t("Reittietiketti tulostuu"),"...<br />";
		}
		else {
			echo t("Reittietiketin tulostinta ei ole valittu. Reittietiketti ei tulostu"),"...<br />";
		}
	}
