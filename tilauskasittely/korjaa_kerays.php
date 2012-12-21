<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Korjaa keräys").":</font><hr>";

	$alatilalisa = "";

	if ($yhtiorow['kerayserat'] == 'K') {
		$alatilalisa = ",'B'";
	}

	if ($tee == 'KORJAA') {

		$query = "	SELECT tunnus
					FROM lasku
					where yhtio = '{$kukarow['yhtio']}'
					and tila	= 'L'
					and alatila in ('C','A' $alatilalisa)
					AND tunnus 	= '$tunnus'";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) > 0) {

			$query = "	UPDATE tilausrivi
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
						SET tilausrivi.keratty = '',
						tilausrivi.kerattyaika = ''
						WHERE tilausrivi.otunnus = '$tunnus'
						and tilausrivi.yhtio 	 = '{$kukarow['yhtio']}'
						AND tilausrivi.tyyppi 	!= 'D'
						AND tilausrivi.var not in ('P','J')";
			$result = pupe_query($query);

			$query  = "	UPDATE lasku
						SET alatila = 'A'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus 	= '$tunnus'
						AND tila 	= 'L'
						and alatila in ('C','A' $alatilalisa)";
			$result = mysql_query($query) or pupe_error($query);

			if ($yhtiorow['kerayserat'] == 'K') {
				$query  = "	DELETE FROM rahtikirjat
							WHERE yhtio    = '{$kukarow['yhtio']}'
							and otsikkonro = '$tunnus'";
				$query = pupe_query($query);

				$query = "	UPDATE kerayserat
							SET tila = 'K',
							ohjelma_moduli = 'PUPESOFT'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '$tunnus'";
				$tila_upd_res = pupe_query($query);
			}
		}

		$tee = '';
	}

	// meillä ei ole valittua tilausta
	if ($tee == '') {

		$formi = "find";
		$kentta = "etsi";

		// tehdään etsi valinta
		echo "<form name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form><br><br>";

		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		$haku = '';

		if (is_string($etsi)) {
			$haku = "and lasku.nimi LIKE '%".mysql_real_escape_string($etsi)."%'";
		}

		if (is_numeric($etsi)) {
			$haku = "and lasku.tunnus = '".mysql_real_escape_string($etsi)."'";

			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-14, date("Y")));
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-14, date("Y")));
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-14, date("Y")));
		}

		$kkl = date("m");
		$vvl = date("Y");
		$ppl = date("d");

		$query = "	SELECT distinct lasku.yhtio yhtio, lasku.tunnus, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
										and tilausrivi.otunnus = lasku.tunnus
										and tilausrivi.var != 'J'
										and tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00'
										and tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tila 	  = 'L'
					AND lasku.alatila in ('C','A' $alatilalisa)
					$haku";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Yhtio")."</th>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Laadittu")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<tr>";

			while ($tilrow = mysql_fetch_assoc($tilre)) {
				echo "<tr>";
				echo "<td>$tilrow[yhtio]</td>";
				echo "<td>$tilrow[tunnus]</td>";
				echo "<td>$tilrow[asiakas]</td>";
				echo "<td>$tilrow[laadittu]</td>";
				echo "<td>$tilrow[laatija]</td>";
				echo "<td class='back'><form method='post'>
						<input type='hidden' name='tee' value='KORJAA'>
						<input type='hidden' name='lasku_yhtio' value='$tilrow[yhtio]'>
					  	<input type='hidden' name='tunnus' value='$tilrow[tunnus]'>
					  	<input type='submit' name='tila' value='".t("Palauta tilaus keräyslista tulostettu tilaan")."'>
						</form></td>";
				echo "</tr>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään korjattavaa tilausta ei löytynyt")."...</font>";
		}
	}

	require ("inc/footer.inc");
