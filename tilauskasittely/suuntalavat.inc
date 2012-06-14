<?php
	$query  = "SELECT * FROM lasku WHERE tunnus = '{$otunnus}' AND yhtio = '{$kukarow['yhtio']}' AND tila = 'K'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_assoc($result);
	}
	else {
		echo "<font class='error'>",t("VIRHE: Tilausta ei enää löydy"),"!</font>";
		require('../inc/footer.inc');
		exit;
	}

	if (!isset($suuntalavat_ei_kayttoliittymaa) or $suuntalavat_ei_kayttoliittymaa != "KYLLA") {
		echo "	<script type=\"text/javascript\" charset=\"utf-8\">
					$(document).ready(function() {
						if ('$tee' == 'uusi') {
							// valitaan sscc-kenttä heti kun ollaan muokkaamassa, jotta voidaan lukea viivakoodi heti ilman klikkailuja
							$('#sscc').focus().select();
						}
					});
				</script>";
	}

	$virheviesti = array('sscc' => '',
						'alkuhyllyalue' => '',
						'alkuhyllynro' => '',
						'alkuhyllyvali' => '',
						'alkuhyllytaso' => '',
						'loppuhyllyalue' => '',
						'loppuhyllynro' => '',
						'loppuhyllyvali' => '',
						'loppuhyllytaso' => '',
						'tyyppi' => '',
						'keraysvyohyke' => '',
						'kaytettavyys' => '',
						'terminaalialue' => '',
						'korkeus' => '',
						'paino' => '',
						'usea_keraysvyohyke' => '',
						);

	if (!isset($sscc)) $sscc = '';
	if (!isset($alkuhyllyalue)) $alkuhyllyalue = '';
	if (!isset($alkuhyllynro)) $alkuhyllynro = '';
	if (!isset($alkuhyllyvali)) $alkuhyllyvali = '';
	if (!isset($alkuhyllytaso)) $alkuhyllytaso = '';
	if (!isset($loppuhyllyalue)) $loppuhyllyalue = '';
	if (!isset($loppuhyllynro)) $loppuhyllynro = '';
	if (!isset($loppuhyllyvali)) $loppuhyllyvali = '';
	if (!isset($loppuhyllynro)) $loppuhyllynro = '';
	if (!isset($loppuhyllytaso)) $loppuhyllytaso = '';
	if (!isset($tyyppi)) $tyyppi = '';
	if (!isset($keraysvyohyke)) $keraysvyohyke = '';
	if (!isset($kaytettavyys)) $kaytettavyys = '';
	if (!isset($terminaalialue)) $terminaalialue = '';
	if (!isset($korkeus)) $korkeus = 0;
	if (!isset($paino)) $paino = 0;
	if (!isset($suuntalavan_tunnus)) $suuntalavan_tunnus = '';
	if (!isset($usea_keraysvyohyke)) $usea_keraysvyohyke = '';


	if ($tee == 'peruutettu') {
		$query = "	UPDATE suuntalavat SET
					tila = '',
					paino = 0
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$suuntalavan_tunnus}'";
		$tila_res = pupe_query($query);

		$tee = '';
		$suuntalavan_tunnus = '';
	}

	if ($tee == 'poista') {

		$query = "	SELECT GROUP_CONCAT(DISTINCT saapuminen) saapumiset
					FROM suuntalavat_saapuminen
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND suuntalava = '{$suuntalavan_tunnus}'";
		$saapumiset_res = pupe_query($query);
		$saapumiset_row = mysql_fetch_assoc($saapumiset_res);

		$query = "	UPDATE tilausrivi SET
					suuntalava = 0
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tyyppi = 'O'
					AND suuntalava = '{$suuntalavan_tunnus}'
					AND uusiotunnus IN ({$saapumiset_row['saapumiset']})";
		$poista_result = pupe_query($query);

		$query = "DELETE FROM suuntalavat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$suuntalavan_tunnus}'";
		$poista_result = pupe_query($query);

		$query = "DELETE FROM suuntalavat_saapuminen WHERE yhtio = '{$kukarow['yhtio']}' AND suuntalava = '{$suuntalavan_tunnus}'";
		$poista_result = pupe_query($query);

		$tee = '';
		$suuntalavan_tunnus = '';
	}

	if ($tee == 'imaiserivit') {

		$query = "	UPDATE tilausrivi
					SET suuntalava = '{$suuntalavan_tunnus}'
					WHERE yhtio 	 = '{$kukarow['yhtio']}'
					AND uusiotunnus  = '{$otunnus}'
					AND tyyppi 		 = 'O'
					AND suuntalava   = 0
					AND varattu 	<> 0
					AND kpl			 = 0";
		$tila_res = pupe_query($query);

		$paivmaara = mysql_affected_rows();

		echo "<br><br>".t("Suuntalavaan liitettiin %s riviä keikalta", "", $paivmaara)."!<br><br>";

		if ($suuntalavat_ei_kayttoliittymaa == "KYLLA") {
			$tee = 'EIHALUTANAYTTAAMITAAN';
			$suuntalavan_tunnus = '';
		}
		else {
			$tee = '';
			$suuntalavan_tunnus = '';
		}
	}

	if ($tee == 'siirtovalmis') {

		$query = "	SELECT sscc, paino
					FROM suuntalavat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$suuntalavan_tunnus}'";
		$sscc_chk_res = pupe_query($query);
		$sscc_chk_row = mysql_fetch_assoc($sscc_chk_res);

		if ($sscc_chk_row['sscc'] == '' or $sscc_chk_row['sscc'] == '0') {
			echo "<font class='error'>",t("VIRHE: SSCC-koodi puuttuu"),"!</font><br /><br />";
		}
		else {

			if ($sscc_chk_row['paino'] == 0 and trim($suuntalavan_tunnus) != '' and trim($otunnus) != '') {

				$query = "	SELECT sum(tuote.tuotemassa) paino
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.uusiotunnus = '{$otunnus}'
							AND tilausrivi.suuntalava = '{$suuntalavan_tunnus}'
							AND tilausrivi.tyyppi = 'O'";
				$paino_res = pupe_query($query);
				$paino_row = mysql_fetch_assoc($paino_res);


				if ($paino_row['paino'] > 0) {
					$query = "UPDATE suuntalavat SET paino = '{$paino_row['paino']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$suuntalavan_tunnus}'";
					$update_res = pupe_query($query);
				}
			}

			$query = "	UPDATE suuntalavat SET
						tila = 'S'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$suuntalavan_tunnus}'";
			$tila_res = pupe_query($query);
		}

		if ($suuntalavat_ei_kayttoliittymaa == "KYLLA") {
			$tee = 'EIHALUTANAYTTAAMITAAN';
			$suuntalavan_tunnus = '';
		}
		else {
			$tee = '';
			$suuntalavan_tunnus = '';
		}
	}

	if ($tee == 'purettu') {
		$query = "	UPDATE suuntalavat SET
					tila = 'P'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$suuntalavan_tunnus}'";
		$tila_res = pupe_query($query);

		$tee = '';
		$suuntalavan_tunnus = '';
	}

	if ($tee == 'lisaa') {

		$tee = '';
		$error = " <img src='../pics/lullacons/stop.png' title='".t("Virhe")."' alt='".t("Virhe")."' />";

		if (trim($sscc) == '') {
			$virheviesti['sscc'] = $error;
			$tee = 'uusi';
		}

		if (trim($tyyppi) == '') {
			$virheviesti['tyyppi'] = $error;
			$tee = 'uusi';
		}

		if (trim($keraysvyohyke) == '') {
			$virheviesti['keraysvyohyke'] = $error;
			$tee = 'uusi';
		}

		if (trim($kaytettavyys) == '') {
			$virheviesti['kaytettavyys'] = $error;
			$tee = 'uusi';
		}

		if (trim($terminaalialue) == '') {
			$virheviesti['terminaalialue'] = $error;
			$tee = 'uusi';
		}

		if ($yhtiorow['varastopaikkojen_maarittely'] == 'M' and (trim($korkeus) == '' or $korkeus == 0 or !is_numeric($korkeus))) {
			$virheviesti['korkeus'] = $error;
			$tee = 'uusi';
		}
		elseif ($yhtiorow['varastopaikkojen_maarittely'] == '' and trim($korkeus) != '' and !is_numeric($korkeus)) {
			$virheviesti['korkeus'] = $error;
			$tee = 'uusi';
		}

		if (trim($paino) != '' and !is_numeric($paino)) {
			$virheviesti['paino'] = $error;
			$tee = 'uusi';
		}

		if ($tee == '') {

			$paino = str_replace(",", ".", $paino);
			$korkeus = str_replace(",", ".", $korkeus);

			if (trim($suuntalavan_tunnus) != '') {

				$query = "	SELECT keraysvyohyke
							FROM suuntalavat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$suuntalavan_tunnus}'";
				$keraysvyohyke_chk_res = pupe_query($query);
				$keraysvyohyke_chk_row = mysql_fetch_assoc($keraysvyohyke_chk_res);

				// jos vaihdetaan keräysvyöhykettä, nollataan suuntalavat kohdistetuilta tilausriveiltä johon suuntalava kuuluu
				// tämän jälkeen koitetaan päivittää suuntalavan tunnus uudestaan käyttäen apuna uutta valittua keräysvyöhykettä
				if ($keraysvyohyke_chk_row['keraysvyohyke'] != $keraysvyohyke) {

					$query = "	UPDATE tilausrivi SET 
								tilausrivi.suuntalava = 0
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.uusiotunnus = '{$otunnus}'
								AND tilausrivi.tyyppi = 'O'
								AND tilausrivi.kpl = 0
								AND tilausrivi.suuntalava = '{$suuntalavan_tunnus}'";
					$suuntalava_upd_res = pupe_query($query);

					if (trim($automaattinen_paivitys) == 'K') {
						$query = "	UPDATE tilausrivi
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke = '{$keraysvyohyke_chk_row['keraysvyohyke']}')
									SET tilausrivi.suuntalava = '{$suuntalavan_tunnus}'
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.uusiotunnus = '{$otunnus}'
									AND tilausrivi.suuntalava = '{$suuntalavan_tunnus}'
									AND tilausrivi.kpl = 0
									AND tilausrivi.tyyppi = 'O'";
						$keraysvyohyke_upd_res = pupe_query($query);
					}
				}

				$query = "	UPDATE suuntalavat SET
							sscc 				= '{$sscc}',
							alkuhyllyalue 		= '{$alkuhyllyalue}',
							alkuhyllynro 		= '{$alkuhyllynro}',
							alkuhyllyvali 		= '{$alkuhyllyvali}',
							alkuhyllytaso 		= '{$alkuhyllytaso}',
							loppuhyllyalue 		= '{$loppuhyllyalue}',
							loppuhyllynro 		= '{$loppuhyllynro}',
							loppuhyllyvali 		= '{$loppuhyllyvali}',
							loppuhyllytaso 		= '{$loppuhyllytaso}',
							tyyppi 				= '{$tyyppi}',
							keraysvyohyke 		= '{$keraysvyohyke}',
							kaytettavyys 		= '{$kaytettavyys}',
							terminaalialue 		= '{$terminaalialue}',
							usea_keraysvyohyke 	= '{$usea_keraysvyohyke}',
							korkeus 			= '{$korkeus}',
							paino 				= '{$paino}',
							muuttaja 			= '{$kukarow['kuka']}',
							muutospvm 			= now()
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus 	= '{$suuntalavan_tunnus}'";
				$update_result = pupe_query($query);
			}
			else {

				$query = "	INSERT INTO suuntalavat SET
							yhtio 				= '{$kukarow['yhtio']}',
							tila 				= '',
							sscc 				= '{$sscc}',
							alkuhyllyalue 		= '{$alkuhyllyalue}',
							alkuhyllynro 		= '{$alkuhyllynro}',
							alkuhyllyvali 		= '{$alkuhyllyvali}',
							alkuhyllytaso 		= '{$alkuhyllytaso}',
							loppuhyllyalue		= '{$loppuhyllyalue}',
							loppuhyllynro 		= '{$loppuhyllynro}',
							loppuhyllyvali 		= '{$loppuhyllyvali}',
							loppuhyllytaso 		= '{$loppuhyllytaso}',
							tyyppi 				= '{$tyyppi}',
							keraysvyohyke 		= '{$keraysvyohyke}',
							kaytettavyys 		= '{$kaytettavyys}',
							terminaalialue 		= '{$terminaalialue}',
							usea_keraysvyohyke 	= '{$usea_keraysvyohyke}',
							korkeus 			= '{$korkeus}',
							paino 				= '{$paino}',
							laatija 			= '{$kukarow['kuka']}',
							luontiaika 			= now(),
							muuttaja 			= '{$kukarow['kuka']}',
							muutospvm 			= now()";
				$insert_result = pupe_query($query);
				$uusi_suuntalavan_tunnus = mysql_insert_id();

				$query = "	INSERT INTO suuntalavat_saapuminen SET
							yhtio = '{$kukarow['yhtio']}',
							suuntalava = '{$uusi_suuntalavan_tunnus}',
							saapuminen = '{$otunnus}',
							laatija = '{$kukarow['kuka']}',
							luontiaika = now(),
							muutospvm = now(),
							muuttaja = '{$kukarow['kuka']}'";
				$insert_result = pupe_query($query);

				// päivitetään suuntalava automaattisesti niille riveille mihin se mätsää
				if (trim($automaattinen_paivitys) == 'K') {
					$query = "	UPDATE tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke = '{$keraysvyohyke}')
								SET tilausrivi.suuntalava = '{$uusi_suuntalavan_tunnus}'
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.uusiotunnus = '{$otunnus}'
								AND tilausrivi.suuntalava = 0
								AND tilausrivi.kpl = 0
								AND tilausrivi.tyyppi = 'O'";
					$tilausrivi_res = pupe_query($query);
				}
			}

			if ($paino == 0 and (trim($suuntalavan_tunnus) != '' or trim($uusi_suuntalavan_tunnus) != '')) {

				$suuntalavan_tunnus = trim($uusi_suuntalavan_tunnus) != '' ? $uusi_suuntalavan_tunnus : $suuntalavan_tunnus;

				$query = "	SELECT sum(tuote.tuotemassa) paino
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.uusiotunnus = '{$otunnus}'
							AND tilausrivi.suuntalava = '{$suuntalavan_tunnus}'
							AND tilausrivi.tyyppi = 'O'";
				$paino_res = pupe_query($query);
				$paino_row = mysql_fetch_assoc($paino_res);


				if ($paino_row['paino'] > 0) {
					$query = "UPDATE suuntalavat SET paino = '{$paino_row['paino']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$suuntalavan_tunnus}'";
					$update_res = pupe_query($query);
				}
			}

			$suuntalavan_tunnus = '';
		}

		if ($suuntalavat_ei_kayttoliittymaa == "KYLLA") {
			$tee = "eihalutamitankayttoliittymaapliis";
		}

	}

	if ($tee == 'vie_koko_suuntalava') {

		if ($yhtiorow['varastopaikkojen_maarittely'] == 'M') {

			// otetaan yksi suuntalavan tuotteen hyllyalue, jolla voidaan mätsätä sen mukaan uutta paikkaa suuntalavalle
			$query = "	SELECT hyllyalue
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND uusiotunnus = '{$otunnus}'
						AND tyyppi = 'O'
						AND suuntalava = '{$suuntalavan_tunnus}'
						AND kpl = 0";
			$hyllyalue_chk_res = pupe_query($query);
			$hyllyalue_chk_row = mysql_fetch_assoc($hyllyalue_chk_res);

			$query = "	SELECT suuntalavat.korkeus, pakkaus.leveys, pakkaus.syvyys, (suuntalavat.paino + pakkaus.paino) massa, keraysvyohyke.varasto, keraysvyohyke.tunnus as ker_tunnus
						FROM suuntalavat
						JOIN pakkaus ON (pakkaus.yhtio = suuntalavat.yhtio AND pakkaus.tunnus = suuntalavat.tyyppi)
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = suuntalavat.yhtio AND keraysvyohyke.tunnus = suuntalavat.keraysvyohyke)
						WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
						AND suuntalavat.tunnus = '{$suuntalavan_tunnus}'";
			$pakkaus_res = pupe_query($query);
			$pakkaus_row = mysql_fetch_assoc($pakkaus_res);

			$suuntalavan_tilavuus = $pakkaus_row['leveys'] * $pakkaus_row['korkeus'] * $pakkaus_row['syvyys'];
			$suuntalavan_massa = $pakkaus_row['massa'];

			$query = "	SELECT DISTINCT
						ABS(ORD(LEFT('{$hyllyalue_chk_row['hyllyalue']}', 1)) - ORD(LEFT(varaston_hyllypaikat.hyllyalue, 1))) etaisyys,
						(varaston_hyllypaikat.korkeus * varaston_hyllypaikat.leveys * varaston_hyllypaikat.syvyys) tilavuus,
						floor((varaston_hyllypaikat.korkeus * varaston_hyllypaikat.leveys * varaston_hyllypaikat.syvyys) / {$suuntalavan_tilavuus}) paljon_mahtuu_kpl,
						varaston_hyllypaikat.hyllyalue,
						varaston_hyllypaikat.hyllynro,
						varaston_hyllypaikat.hyllyvali,
						varaston_hyllypaikat.hyllytaso,
						tuotepaikat.tunnus
						FROM varaston_hyllypaikat
						JOIN tuotepaikat ON (tuotepaikat.yhtio = varaston_hyllypaikat.yhtio
											AND tuotepaikat.saldo = 0
											AND tuotepaikat.hyllyalue = varaston_hyllypaikat.hyllyalue
											AND tuotepaikat.hyllynro = varaston_hyllypaikat.hyllynro
											AND tuotepaikat.hyllyvali = varaston_hyllypaikat.hyllyvali
											AND tuotepaikat.hyllytaso = varaston_hyllypaikat.hyllytaso
											AND tuotepaikat.oletus = '')
						WHERE varaston_hyllypaikat.yhtio = '{$kukarow['yhtio']}'
						AND varaston_hyllypaikat.varasto = '{$pakkaus_row['varasto']}'
						AND varaston_hyllypaikat.korkeus >= '{$pakkaus_row['korkeus']}'
						AND varaston_hyllypaikat.leveys >= '{$pakkaus_row['leveys']}'
						AND varaston_hyllypaikat.syvyys >= '{$pakkaus_row['syvyys']}'
						AND varaston_hyllypaikat.minimi_korkeus <= '{$pakkaus_row['korkeus']}'
						AND varaston_hyllypaikat.minimi_leveys <= '{$pakkaus_row['leveys']}'
						AND varaston_hyllypaikat.minimi_syvyys <= '{$pakkaus_row['syvyys']}'
						AND varaston_hyllypaikat.maksimitaakka >= ({$suuntalavan_massa})
						AND varaston_hyllypaikat.keraysvyohyke = '{$pakkaus_row['ker_tunnus']}'
						GROUP BY 1,2,3,4,5,6,7
						HAVING paljon_mahtuu_kpl >= 1
						ORDER BY 1,2,3,4,5,6,7
						LIMIT 5";
			$loput_res = pupe_query($query);

			if (mysql_num_rows($loput_res) > 0) {
				echo "<font class='ok'>",t("Suuntalava mahtuu hyllypaikalle"),"</font>&nbsp;&nbsp;";

				echo "<form method='post'>";
				echo "<select name='suuntalavan_hyllypaikka'>";

				while ($loput_row = mysql_fetch_assoc($loput_res)) {
					echo "<option value='{$loput_row['hyllyalue']}#{$loput_row['hyllynro']}#{$loput_row['hyllyvali']}#{$loput_row['hyllytaso']}'>{$loput_row['hyllyalue']} {$loput_row['hyllynro']} {$loput_row['hyllyvali']} {$loput_row['hyllytaso']}</option>";
				}

				echo "</select>&nbsp;";

				echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
				echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
				echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
				echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
				echo "<input type='hidden' name='tee' value='' />";
				echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";
				echo "<input type='hidden' name='koko_suuntalava' value='X' />";
				echo "<input type='submit' value='",t("Vie koko suuntalava varastoon"),"' />";
				echo "</form>";

				echo "<br /<br />",t("tai"),"<br /><br />";
			}
			else {
				echo "<font class='error'>",t("Suuntalava ei mahdu varastoon"),"! ",t("Syötä suuntalavan hyllypaikka manuaalisesti"),".</font><br /><br />";
			}
		}

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='' />";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";
		echo "<input type='hidden' name='koko_suuntalava' value='X' />";

		echo "<table><tr>";
		echo "<th>",t("Syötä suuntalavalle hyllypaikka"),"</th>";
		echo "<td><input type='text' name='suuntalavan_hyllyalue' size='6' maxlength='5' value='' /> <input type='text' name='suuntalavan_hyllynro' size='6' maxlength='5' value='' /> <input type='text' name='suuntalavan_hyllyvali' size='6' maxlength='5' value='' /> <input type='text' name='suuntalavan_hyllytaso' size='6' maxlength='5' value='' /></td>";
		echo "</tr></table>";

		echo "<input type='submit' value='",t("Vie koko suuntalava varastoon"),"' />";
		echo "</form><br /><br />";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='' />";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
		echo "<input type='submit' value='",t("Takaisin"),"' />";
		echo "</form>";
	}


	if ($tee == 'uusi') {

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='tee' value='lisaa' />";

		if (is_numeric($suuntalavan_tunnus)) {
			$query = "	SELECT suuntalavat.* 
						FROM suuntalavat 
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
						WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}' 
						AND suuntalavat.tunnus = '{$suuntalavan_tunnus}'";
			$suuntalavat_result = pupe_query($query);
			$suuntalavat_row = mysql_fetch_assoc($suuntalavat_result);

			echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

			$sscc = $suuntalavat_row['sscc'];
			$alkuhyllyalue = $suuntalavat_row['alkuhyllyalue'];
			$alkuhyllynro = $suuntalavat_row['alkuhyllynro'];
			$alkuhyllyvali = $suuntalavat_row['alkuhyllyvali'];
			$alkuhyllytaso = $suuntalavat_row['alkuhyllytaso'];
			$loppuhyllyalue = $suuntalavat_row['loppuhyllyalue'];
			$loppuhyllynro = $suuntalavat_row['loppuhyllynro'];
			$loppuhyllyvali = $suuntalavat_row['loppuhyllyvali'];
			$loppuhyllytaso = $suuntalavat_row['loppuhyllytaso'];
			$tyyppi = $suuntalavat_row['tyyppi'];
			$keraysvyohyke = $suuntalavat_row['keraysvyohyke'];
			$kaytettavyys = $suuntalavat_row['kaytettavyys'];
			$terminaalialue = $suuntalavat_row['terminaalialue'];
			$usea_keraysvyohyke = $suuntalavat_row['usea_keraysvyohyke'];
			$korkeus = $suuntalavat_row['korkeus'];
			$paino = $suuntalavat_row['paino'];
		}
		else {
			echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>",t("Uusi suuntalava"),"</th></tr>";
		echo "<tr><th>SSCC-",t("koodi"),"</th>";
		echo "<td><input type='text' name='sscc' id='sscc' value='{$sscc}' />{$virheviesti['sscc']}<br /><font class='info'>",t("SSCC-koodi täytyy olla vähintää 7 merkkiä pitkä"),".</font></td></tr>";
		echo "<tr><th>",t("Varastopaikkaväli alku"),"</th><td><input type='text' name='alkuhyllyalue' size='6' maxlength='5' value='{$alkuhyllyalue}' />{$virheviesti['alkuhyllyalue']} <input type='text' name='alkuhyllynro' size='6' maxlength='5' value='{$alkuhyllynro}' />{$virheviesti['alkuhyllynro']} <input type='text' name='alkuhyllyvali' size='6' maxlength='5' value='{$alkuhyllynro}' />{$virheviesti['alkuhyllyvali']} <input type='text' name='alkuhyllytaso' size='6' maxlength='5' value='{$alkuhyllytaso}' />{$virheviesti['alkuhyllytaso']}</td></tr>";
		echo "<tr><th>",t("Varastopaikkaväli loppu"),"</th><td><input type='text' name='loppuhyllyalue' size='6' maxlength='5' value='{$loppuhyllyalue}' />{$virheviesti['loppuhyllyalue']} <input type='text' name='loppuhyllynro' size='6' maxlength='5' value='{$loppuhyllynro}' />{$virheviesti['loppuhyllynro']} <input type='text' name='loppuhyllyvali' size='6' maxlength='5' value='{$loppuhyllyvali}' />{$virheviesti['loppuhyllyvali']} <input type='text' name='loppuhyllytaso' size='6' maxlength='5' value='{$loppuhyllytaso}' />{$virheviesti['loppuhyllytaso']}</td></tr>";

		$query = "	SELECT *
					FROM pakkaus
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$pakkaus_result = pupe_query($query);

		echo "<tr><th>",t("Tyyppi"),"</th>";
		echo "<td><select name='tyyppi'><option value=''>",t("Valitse"),"</option>";

		while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {
			$sel = $tyyppi == $pakkaus_row['tunnus'] ? ' selected' : '';

			echo "<option value='{$pakkaus_row['tunnus']}'{$sel}>".t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV")."</option>";
		}

		echo "</select>{$virheviesti['tyyppi']}</td></tr>";

		echo "<tr><th>",t("Keräysvyöhyke"),"</th>";
		echo "<td><select name='keraysvyohyke'><option value=''>",t("Valitse"),"</option>";

		$query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
		$keraysvyohyke_result = pupe_query($query);

		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
			$sel = $keraysvyohyke == $keraysvyohyke_row['tunnus'] ? ' selected' : '';

			echo "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
		}

		echo "</select>{$virheviesti['keraysvyohyke']}</td></tr>";

		$sel_y = $kaytettavyys == 'Y' ? ' selected' : '';
		$sel_l = $kaytettavyys == 'L' ? ' selected' : '';

		echo "<tr><th>",t("Käytettävyys"),"</th>";
		echo "<td><select name='kaytettavyys'>";
		// echo "<option value=''>",t("Valitse"),"</option>";
		echo "<option value='Y'{$sel_y}>",t("Yksityinen"),"</option>";
		echo "<option value='L'{$sel_l}>",t("Yleinen"),"</option>";
		echo "</select>{$virheviesti['kaytettavyys']}</td></tr>";

		echo "<tr><th>",t("Terminaalialue"),"</th>";
		echo "<td><select name='terminaalialue'><option value=''>",t("Valitse"),"</option>";

		$terminaalialue_result = t_avainsana("TERMINAALIALUE");

		while ($terminaalialue_row = mysql_fetch_assoc($terminaalialue_result)) {
			$sel = $terminaalialue == $terminaalialue_row['selite'] ? ' selected' : '';

			echo "<option value='{$terminaalialue_row['selite']}'{$sel}>{$terminaalialue_row['selite']}</option>";
		}

		echo "</select>{$virheviesti['terminaalialue']}</td></tr>";

		if ($usea_keraysvyohyke != "") {
			$sel_sp = "SELECTED";
		}
		else {
			$sel_sp = "";
		}

		echo "<tr><th>".t("Sallitaanko suuntalavalle useilta %s erivyöhykkeiltä tuotteita",$kieli,'<br>')."</th>";
		echo "<td><select name='usea_keraysvyohyke'>";
		echo "<option value='' $sel_sp>".t("Ei Sallita")."</option>";
		echo "<option value='K' $sel_sp>".t("Sallitaan")."</option>";
		echo "</select>";
		echo "</td></tr>";

		echo "<tr><th>",t("Korkeus"),"</th><td><input type='text' name='korkeus' value='{$korkeus}' />{$virheviesti['korkeus']}</td></tr>";
		echo "<tr><th>",t("Paino"),"</th><td><input type='text' name='paino' value='{$paino}' />{$virheviesti['paino']}<br/><font class='info'>",t("Paino lasketaan automaattisesti tuotteiden takaa, jos paino jätetään nollaksi"),".</font></td></tr>";

		echo "<tr><th>",t("Automaattinen päivitys"),"</th>";
		echo "<td><select name='automaattinen_paivitys'>";
		echo "<option value=''>",t("Ei"),"</option>";
		echo "<option value='K'>",t("Kyllä"),"</option>";
		echo "</select>";
		echo "<br/><font class='info'>",t("Päivitetään suuntalava automaattisesti niille riveille mihin se kohdistuu"),".</font>";
		echo "</td></tr>";

		echo "<tr><td class='back' colspan='2'><input type='submit' value='",t("Lisää suuntalava"),"' /></td></tr>";
		echo "</table>";
		echo "</form>";
		echo "<br />";

		echo "<br />";

		$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND uusiotunnus = '{$otunnus}'
					AND tyyppi = 'O'
					AND kpl = 0
					AND suuntalava = '{$suuntalavan_tunnus}'";
		$voidaanko_poistaa_result = pupe_query($query);
		$voidaanko_poistaa_row = mysql_fetch_assoc($voidaanko_poistaa_result);

		if (trim($voidaanko_poistaa_row['tunnukset']) == '') {
			echo "<form method='post'>";
			echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
			echo "<input type='hidden' name='tee' value='poista'>";
			echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
			echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
			echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
			echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";
			echo "<input type='submit' value='",t("Poista suuntalava"),"'>";
			echo "</form>";
		}

		echo "<br />";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
		echo "<input type='hidden' name='tee' value=''>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
		echo "<input type='submit' value='",t("Takaisin suuntalavoihin"),"'>";
		echo "</form>";

		if ($toiminto != "") {
			echo "<hr>";
			echo "<form method='post'>";
			echo "<input type='hidden' name='toiminto' value=''>";
			echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
			echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
			echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
			echo "<input type='submit' value='",t("Takaisin saapumiselle"),"'>";
			echo "</form>";
		}
	}

	if ($tee == '') {

		echo "<br><table>";
		echo "<tr>";
		echo "<th>".t("saapuminen")."</th>";
		echo "<th>".t("ytunnus")."</th>";
		echo "<th>".t("nimi")."</th>";
		echo "</tr>";
		echo "<tr>
				<td>$laskurow[laskunro]</td>
				<td>$laskurow[ytunnus]</td>
				<td>$laskurow[nimi]</td>
				</tr>";
		echo "</table><br>";

		echo "<br />";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='uusi' />";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
		echo "<input type='submit' value='",t("Uusi suuntalava"),"' />";
		echo "</form>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='tulosta_sscc'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='' />";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='' />";
		echo "<input type='hidden' name='lopetus' value='{$palvelin2}tilauskasittely/keikka.php////tee=//toimittajaid={$toimittajaid}//toiminto=suuntalavat//otunnus={$otunnus}//ytunnus={$laskurow['ytunnus']}//suuntalavan_tunnus='>";
		echo "<input type='submit' value='",t("Tulosta SSCC-koodi"),"' />";
		echo "</form>";

		echo "<br /><br />";

		echo "<table>";
		echo "<tr><th>SSCC-",t("koodi"),"</th>";
		echo "<th>",t("Varastopaikkaväli"),"</th>";
		echo "<th>",t("Tyyppi"),"</th>";
		echo "<th>",t("Keräysvyohyke"),"</th>";
		echo "<th>",t("Käytettavyys"),"</th>";
		echo "<th>",t("Terminaalialue"),"</th>";
		echo "<th>",t("Korkeus"),"</th>";
		echo "<th>",t("Paino"),"</th>";
		echo "<th>",t("Tila"),"</th></tr>";

		$query = "	SELECT suuntalavat.*, if(suuntalavat.kaytettavyys = 'Y', 'Yksityinen', 'Yleinen') kaytettavyys, if(suuntalavat.tila = '', 0, if(suuntalavat.tila = 'S', 1, 2)) sorttaus
					FROM suuntalavat
					JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
					WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
					AND suuntalavat.tila != 'P'
					ORDER BY sorttaus";
		$suuntalavat_result = pupe_query($query);

		while ($suuntalavat_row = mysql_fetch_assoc($suuntalavat_result)) {
			echo "<tr>";

			if ($suuntalavat_row['tila'] == 'P') {
				echo "<td>{$suuntalavat_row['sscc']}</td>";
			}
			else {
				echo "<td><a href='?toimittajaid={$toimittajaid}&toiminto=suuntalavat&otunnus={$otunnus}&ytunnus={$laskurow['ytunnus']}&tee=uusi&suuntalavan_tunnus={$suuntalavat_row['tunnus']}'>{$suuntalavat_row['sscc']}</a></td>";
			}

			echo "<td>";
			if (trim($suuntalavat_row['alkuhyllyalue']) != '') {
				echo "{$suuntalavat_row['alkuhyllyalue']} {$suuntalavat_row['alkuhyllynro']} {$suuntalavat_row['alkuhyllyvali']} {$suuntalavat_row['alkuhyllytaso']} - {$suuntalavat_row['loppuhyllyalue']} {$suuntalavat_row['loppuhyllynro']} {$suuntalavat_row['loppuhyllyvali']} {$suuntalavat_row['loppuhyllytaso']}";
			}
			echo "</td>";
			echo "<td>";

			$query = "	SELECT *
						FROM pakkaus
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$suuntalavat_row['tyyppi']}'";
			$pakkaus_result = pupe_query($query);
			$pakkaus_row = mysql_fetch_assoc($pakkaus_result);

			echo t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV");
			echo "</td>";
			echo "<td>";

			$query = "	SELECT nimitys
						FROM keraysvyohyke
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus  = '{$suuntalavat_row['keraysvyohyke']}'";
			$keraysvyohyke_res = pupe_query($query);
			$keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res);

			echo $keraysvyohyke_row['nimitys'];

			echo "</td>";
			echo "<td>{$suuntalavat_row['kaytettavyys']}</td>";
			echo "<td>{$suuntalavat_row['terminaalialue']}</td>";
			echo "<td>{$suuntalavat_row['korkeus']}</td>";
			echo "<td>{$suuntalavat_row['paino']}</td>";

			echo "<td>";

			if ($suuntalavat_row['tila'] != '') {
				if ($suuntalavat_row['tila'] == 'S') {
					echo "<font class='ok'>",t("Siirtovalmis"),"!</font><br />";

					echo "<form method='post'>";
					echo "<input type='hidden' name='toiminto' value='tulosta'>";
					echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
					echo "<input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>";
					echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
					echo "<input type='hidden' name='keikkaid' value='{$laskurow['laskunro']}'>";
					echo "<input type='hidden' name='tunnus' value='{$laskurow['tunnus']}'>";
					echo "<input type='hidden' name='laskunro' value='{$laskurow['laskunro']}'>";
					echo "<input type='hidden' name='valitut_lavat' value='{$suuntalavat_row['tunnus']}'>";
					echo "<input type='hidden' name='nappikeikalle' value='menossa'>";
					echo "<input type='submit' value='",t("Tulosta purkulista"),"' />";
					echo "</form>";

					echo "<br /><form method='post'>";
					echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
					echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
					echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
					echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
					echo "<input type='hidden' name='tee' value='' />";
					echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
					echo "<input type='submit' value='",t("Vie varastoon"),"' />";
					echo "</form>";

					echo "<br /><form method='post'>";
					echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
					echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
					echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
					echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
					echo "<input type='hidden' name='tee' value='vie_koko_suuntalava' />";
					echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
					echo "<input type='submit' value='",t("Vie koko suuntalava varastoon"),"' />";
					echo "</form>";

					$query = "	SELECT GROUP_CONCAT(tilausrivi.tunnus) tunnukset
								FROM tilausrivi
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.uusiotunnus = '{$otunnus}'
								AND tilausrivi.suuntalava = '{$suuntalavat_row['tunnus']}'
								AND tilausrivi.tyyppi = 'O'
								AND kpl = 0";
					$onko_viety_varastoon_result = pupe_query($query);
					$onko_viety_varastoon_row = mysql_fetch_assoc($onko_viety_varastoon_result);


					if (trim($onko_viety_varastoon_row['tunnukset']) == '' and trim($onko_viety_varastoon_row['tunnukset']) != null) {
						echo "<br /><form method='post'>";
						echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
						echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
						echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
						echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
						echo "<input type='hidden' name='tee' value='purettu' />";
						echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
						echo "<input type='submit' value='",t("Merkkaa puretuksi"),"' />";
						echo "</form>";
					}
					else {
						echo "<br /><form method='post'>";
						echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
						echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
						echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
						echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
						echo "<input type='hidden' name='tee' value='peruutettu' />";
						echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
						echo "<input type='submit' value='",t("Peruuta / Tyhjennä suuntalava"),"' />";
						echo "</form>";
					}

					echo "<br><form method='post'>";
					echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
					echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
					echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
					echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
					echo "<input type='hidden' name='tee' value='imaiserivit' />";
					echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
					echo "<input type='submit' value='",t("Liitä kaikki suuntalavattomat rivit tähän suuntalavaan"),"' />";
					echo "</form>";
				}
				elseif ($suuntalavat_row['tila'] == 'P') {
					echo "<font class='ok'>",t("Purettu"),"!</font>";
				}
			}
			else {
				echo "<form method='post'>";
				echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
				echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
				echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
				echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
				echo "<input type='hidden' name='tee' value='siirtovalmis' />";
				echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
				echo "<input type='submit' value='",t("Siirtovalmis"),"' />";
				echo "</form>";

				echo "<br><form method='post'>";
				echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
				echo "<input type='hidden' name='toiminto' value='suuntalavat'>";
				echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
				echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
				echo "<input type='hidden' name='tee' value='imaiserivit' />";
				echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavat_row['tunnus']}' />";
				echo "<input type='submit' value='",t("Liitä kaikki suuntalavattomat rivit tähän suuntalavaan"),"' />";
				echo "</form>";
			}

			echo "</td>";

			echo "</tr>";
		}

		echo "</table><br />";

		if ($toiminto != "") {
			echo "<hr>";
			echo "<form action='?indexvas=1' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
			echo "<input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>";
			echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
			echo "<input type='hidden' name='keikkaid' value='{$laskurow['laskunro']}'>";
			echo "<input type='hidden' name='tunnus' value='{$laskurow['tunnus']}'>";
			echo "<input type='hidden' name='laskunro' value='{$laskurow['laskunro']}'>";
			echo "<input type='hidden' name='nappikeikalle' value='menossa'>";
			echo "<input type='hidden' name='toiminto' value='kohdista'>";
			echo "<input type='submit' value='",t("Takaisin saapumiselle"),"'>";
			echo "</form>";
		}
	}
