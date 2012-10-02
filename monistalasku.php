<?php

if (!isset($tee)) $tee = '';
if (!isset($toim)) $toim = '';
if (!isset($ytunnus)) $ytunnus = '';
if (!isset($laskunro)) $laskunro = 0;
if (!isset($otunnus)) $otunnus = 0;
if (!isset($vain_monista)) $vain_monista = '';
if (!isset($tunnukset)) $tunnukset = '';
if (!isset($tunnus)) $tunnus = '';

if ($vain_monista == "") {
	require('inc/parametrit.inc');

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." {$tunnus}:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "ETSILASKU";
	}

	if ($toim == 'SOPIMUS') {
		echo "<font class='head'>".t("Monista sopimus")."</font><hr>";
	}
	elseif ($toim == 'TARJOUS') {
		echo "<font class='head'>".t("Monista tarjous")."</font><hr>";
	}
	elseif ($toim == 'TYOMAARAYS') {
		echo "<font class='head'>".t("Monista tyˆm‰‰r‰ys")."</font><hr>";
	}
	elseif ($toim == 'TILAUS') {
		echo "<font class='head'>".t("Monista tilaus")."</font><hr>";
	}
	elseif ($toim == 'ENNAKKOTILAUS') {
		echo "<font class='head'>".t("Monista ennakkotilaus")."</font><hr>";
	}
	elseif ($toim == 'OSTOTILAUS') {
		echo "<font class='head'>".t("Monista ostotilaus")."</font><hr>";
	}
	else {
		echo "<font class='head'>".t("Monista lasku")."</font><hr>";
	}
}

if ($tee == 'MONISTA' and count($monistettavat) == 0) {
	echo "<font class='error'>",t("Et valinnut yht‰‰n laskua monistettavaksi/hyvitett‰v‰ksi"),"</font><br>";
	$tee = "";
}

if ($toim == '' and $tee == 'MONISTA' and count($monistettavat) > 0) {

	foreach ($monistettavat as $lasku_x => $kumpi_x) {

		// T‰m‰ on hyvitett‰v‰ lasku
		$query = "	SELECT tunnus, clearing, vanhatunnus, liitostunnus, laskunro
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus 	= '{$lasku_x}'
					AND tila 	= 'U'
					AND alatila = 'X'";
		$chk_res = pupe_query($query);
		$chk_row = mysql_fetch_assoc($chk_res);

		// Onko asiakkalla panttitili
		$query = "	SELECT panttitili
					FROM asiakas
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$chk_row['liitostunnus']}'";
		$asiakas_panttitili_chk_res = pupe_query($query);
		$asiakas_panttitili_chk_row = mysql_fetch_assoc($asiakas_panttitili_chk_res);

		if ($kumpi_x == 'HYVITA') {
			// jos tilauksella on panttituotteita/sarjanumeroita pit‰‰ est‰‰, ett‰ hyvityst‰ ei saa en‰‰ hyvitt‰‰ (clearing=hyvitys)
			if ($chk_row['clearing'] == 'HYVITYS') {
				$query = "	SELECT tilausrivi.otunnus, tuote.panttitili, tuote.sarjanumeroseuranta, tuote.tuoteno, tilausrivi.varattu
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio 		= '{$kukarow['yhtio']}'
							and tilausrivi.tyyppi  		= 'L'
							AND tilausrivi.uusiotunnus 	= '{$chk_row['tunnus']}'";
				$chk_til_res = pupe_query($query);

				while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {
					if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $chk_til_row['panttitili'] != '') {
						echo "<font class='error'>",t("Et voi hyvitt‰‰ hyvityslaskua, jossa on panttitilillisi‰ tuotteita"),"! ({$lasku_x})</font><br>";
						$tee = "";
						break 2;
					}
					elseif ($chk_til_row["sarjanumeroseuranta"] != "") {
						echo "<font class='error'>",t("Et voi hyvitt‰‰ hyvityslaskua, jossa on sarjanumerollisisa tuotteita"),"! ({$lasku_x})</font><br>";
						$tee = "";
						break 2;
					}
				}
			}
			else {
				// jos tilauksella on panttituotteita/sarjanumeroita pit‰‰ tarkistaa, ett‰ ei anneta hyvitt‰‰ laskua joka on jo hyvitetty (vanhatunnus lˆytyy)
				$query = "	SELECT tunnus
							FROM lasku
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND vanhatunnus = '{$lasku_x}'
							AND clearing 	= 'HYVITYS'
							AND tila 		IN ('N', 'L')";
				$clearing_chk_res = pupe_query($query);

				// Lasku on jo hyvitetty
				if (mysql_num_rows($clearing_chk_res) > 0) {

					while ($clearing_chk_row = mysql_fetch_assoc($clearing_chk_res)) {
						$query = "	SELECT tuote.panttitili, tuote.sarjanumeroseuranta
									FROM tilausrivi
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									and tilausrivi.tyyppi  = 'L'
									AND tilausrivi.otunnus = '{$clearing_chk_row['tunnus']}'";
						$chk_til_res = pupe_query($query);

						while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {
							if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $chk_til_row['panttitili'] != '') {
								echo "<font class='error'>",t("Et voi hyvitt‰‰ tilausta, jossa on panttitilillisi‰ tuotteita ja joka on jo hyvitetty"),"! ({$lasku_x})</font><br>";
								$tee = "";
								break 3;
							}
							elseif ($chk_til_row["sarjanumeroseuranta"] != "") {
								echo "<font class='error'>",t("Et voi hyvitt‰‰ tilausta, jossa on sarjanumerollisia tuotteita ja joka on jo hyvitetty"),"! ({$lasku_x})</font><br>";
								$tee = "";
								break 3;
							}
						}
					}
				}

				if ($asiakas_panttitili_chk_row['panttitili'] == "K") {
					// Hyvitett‰v‰n laskun pantilliset rivit
					$query = "	SELECT tilausrivi.otunnus, tilausrivi.tuoteno, sum(tilausrivi.kpl) kpl
								FROM tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno and tuote.panttitili != '')
								WHERE tilausrivi.yhtio 		= '{$kukarow['yhtio']}'
								and tilausrivi.tyyppi  		= 'L'
								AND tilausrivi.uusiotunnus 	= '{$chk_row['tunnus']}'
								AND tilausrivi.kpl > 0
								GROUP BY 1, 2";
					$chk_til_res = pupe_query($query);

					if (mysql_num_rows($chk_til_res) > 0) {
						while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {

							// jos tilauksella on panttituotteita ja ollaan tekem‰ss‰ hyvityst‰, pit‰‰ katsoa, ett‰ alkuper‰isen veloituslaskun panttitili rivej‰ ei ole viel‰ k‰ytetty
							$query = "	SELECT sum(kpl) kpl
										FROM panttitili
										WHERE yhtio 			= '{$kukarow['yhtio']}'
								        AND asiakas 			= '{$chk_row['liitostunnus']}'
								        AND tuoteno 			= '{$chk_til_row['tuoteno']}'
								        AND myyntitilausnro 	= '{$chk_til_row['otunnus']}'
								        AND status 				= ''
								        AND kaytettypvm 		= '0000-00-00'
								        AND kaytettytilausnro 	= 0";
							$pantti_chk_res = pupe_query($query);
		                	$pantti_chk_row = mysql_fetch_assoc($pantti_chk_res);

							if ($chk_til_row['kpl'] != $pantti_chk_row['kpl']) {
								echo "<font class='error'>",t("Hyvitett‰v‰n laskun pantit on jo k‰ytetty"),"! ({$lasku_x})</font><br>";
								$tee = "";
								break 2;
							}
						}
					}
				}
			}
		}
		elseif ($asiakas_panttitili_chk_row['panttitili'] == "K" and $kumpi_x == 'MONISTA') {

			$query = "	SELECT tuote.panttitili
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno and tuote.panttitili != '')
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						and tilausrivi.tyyppi  = 'L'
						AND tilausrivi.uusiotunnus = '{$lasku_x}'";
			$chk_til_res = pupe_query($query);

			while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {

				$query = "	SELECT tunnus
							FROM lasku
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND vanhatunnus = '{$lasku_x}'
							AND clearing 	= 'HYVITYS'
							AND tila 		= 'U'
							AND alatila 	= 'X'";
				$clearing_chk_res = pupe_query($query);

				if (mysql_num_rows($clearing_chk_res) == 0) {
					echo "<font class='error'>",t("Et voi monistaa tilausta, jossa on panttitilillisi‰ tuotteita ja se on hyvitetty, mutta hyvityst‰ ei ole laskutettu"),"! ({$lasku_x})</font><br>";
					$tee = "";
					break 2;
				}
			}
		}
	}
}

if ($toim == 'TYOMAARAYS') {
	// Halutaanko saldot koko konsernista?
	$query = "	SELECT *
				FROM yhtio
				WHERE konserni = '{$yhtiorow['konserni']}'
				AND konserni != ''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		$yhtiot = array();

		while ($row = mysql_fetch_array($result)) {
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}
}
else {
	$yhtiot = array();
	$yhtiot[] = $kukarow["yhtio"];
}

if ($tee == '') {

	if ($toim == 'OSTOTILAUS') {
		if ($ytunnus != '') {
			require ("inc/kevyt_toimittajahaku.inc");
		}
	}
	else {
		if ($ytunnus != '') {
			require ("inc/asiakashaku.inc");
		}
	}

	if ($ytunnus != '') {
		$tee = "ETSILASKU";
	}
	else {
		$tee = "";
	}

	if ($laskunro > 0) {
		$tee = "ETSILASKU";
	}

	if ($otunnus > 0) {
		$tee = 'ETSILASKU';
	}
}

if ($tee == "mikrotila" or $tee == "file") {
	require ('tilauskasittely/mikrotilaus_monistalasku.inc');
}

if ($tee == "ETSILASKU") {
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	if ($toim != 'SOPIMUS') {
		echo "<form method='post' autocomplete='off'>
				<input type='hidden' name='toim' value='{$toim}'>
				<input type='hidden' name='asiakasid' value='{$asiakasid}'>
				<input type='hidden' name='tunnukset' value='{$tunnukset}'>
				<input type='hidden' name='tee' value='ETSILASKU'>";

		echo "<table>";

		echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
				<td><input type='text' name='kka' value='{$kka}' size='3'></td>
				<td><input type='text' name='vva' value='{$vva}' size='5'></td>
				</tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
				<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
				<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table><br>";
	}

	if ($tunnukset != '') {
		$where 	= " tila = 'U' and lasku.tunnus in ({$tunnukset}) ";
		$use 	= " ";
	}
	elseif ($laskunro > 0) {
		$where 	= " tila = 'U' and laskunro = '{$laskunro}' ";
		$use 	= " use index (lasno_index) ";
	}
	elseif ($otunnus > 0) {
		//katotaan lˆytyykˆ lasku ja sen kaikki tilaukset
		$query = "  SELECT laskunro
					FROM lasku
					WHERE tunnus = '{$otunnus}'
					and yhtio = '{$kukarow['yhtio']}'";
		$laresult = pupe_query($query);
		$larow = mysql_fetch_assoc($laresult);

		if ($toim == 'SOPIMUS') {
			$where 	= " tila = '0' and tunnus = '{$otunnus}' ";
			$use 	= " ";

		}
		elseif ($toim == 'TARJOUS') {
			$where 	= " tila in ('T','L','N') and tunnus = '{$otunnus}' ";
			$use 	= " ";
		}
		elseif ($toim == 'TYOMAARAYS') {
			$where 	= " tila in ('N','L','A') and tunnus = '{$otunnus}' ";
			$use 	= " ";
		}
		elseif ($toim == 'TILAUS') {
			$where 	= " tila in ('N','L') and tunnus = '{$otunnus}' ";
			$use 	= " ";
		}
		elseif ($toim == 'ENNAKKOTILAUS') {
			$where 	= " tila = 'E' and tunnus = '{$otunnus}' ";
			$use 	= " ";
		}
		elseif ($toim == 'OSTOTILAUS') {
			$where 	= " tila = 'O' and tunnus = '{$otunnus}' ";
			$use 	= " ";
		}
		else {
			if ($larow["laskunro"] > 0) {
				$where 	= " tila = 'U' and laskunro = '{$larow['laskunro']}' ";
				$use 	= " use index (lasno_index) ";
			}
			else {
				$where 	= " tila = 'U' and tunnus = '{$otunnus}' ";
				$use 	= " ";
			}
		}
	}
	else {
		if ($toim == 'SOPIMUS') {
			$where = "	tila = '0'
						and lasku.liitostunnus = '{$asiakasid}' ";
			$use 	= " ";
		}
		elseif ($toim == 'TARJOUS') {
			$where = "	tila in ('T','L','N')
						and lasku.liitostunnus = '{$asiakasid}' ";
			$use 	= " ";
		}
		elseif ($toim == 'TYOMAARAYS') {
			$where 	= " tila in ('N','L','A')
						and lasku.liitostunnus = '{$asiakasid}' ";
			$use 	= " ";
		}
		elseif ($toim == 'TILAUS') {
			$where 	= " tila in ('N','L')
						and lasku.liitostunnus = '{$asiakasid}' ";
			$use 	= " ";
		}
		elseif ($toim == 'ENNAKKOTILAUS') {
			$where 	= " tila = 'E'
						and lasku.liitostunnus = '{$asiakasid}' ";
			$use 	= " ";
		}
		elseif ($toim == 'OSTOTILAUS') {
			$where 	= " tila = 'O'
						and lasku.liitostunnus = '{$toimittajaid}' ";
			$use 	= " ";
		}
		else {
			$where = "	tila = 'U'
						and lasku.liitostunnus = '{$asiakasid}'
						and lasku.tapvm >='{$vva}-{$kka}-{$ppa} 00:00:00'
						and lasku.tapvm <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
			$use 	= " use index (yhtio_tila_liitostunnus_tapvm) ";
		}
	}

	// Etsit‰‰n muutettavaa tilausta
	$query = "	SELECT yhtio, tunnus 'tilaus', laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, summa, tapvm, laatija, tila, alatila
				FROM lasku {$use}
				WHERE {$where}
				AND yhtio in ('".implode("','", $yhtiot)."')
				ORDER BY tapvm, lasku.tunnus DESC
				LIMIT 100";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table>";
		echo "<tr>";

		if ($toim != '') {
			echo "<th>".t("Tilaus")."</th>";
		}

		echo "<th>".t("Laskunro")."</th>";
		echo "<th>".t("Asiakas")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Laatija")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "<th>".t("Toiminto")."</th>";

		if ($toim == '') {
			echo "<th>".t("Toiminnot")."</th>";
		}

		echo "<th>".t("N‰yt‰")."</th></tr>";

		echo "	<form method='post' autocomplete='off'>
				<input type='hidden' name='kklkm' value='1'>
				<input type='hidden' name='toim' value='{$toim}'>
				<input type='hidden' name='tee' value='MONISTA'>";

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			$ero = "td";

			if ($tunnus == $row['tilaus']) $ero = "th";

			echo "<tr class='aktiivi'>";

			if ($toim != '') {
				echo "<{$ero}>{$row['tilaus']}</{$ero}>";
			}
			echo "<{$ero}>{$row['laskunro']}</{$ero}>";
			echo "<{$ero}>{$row['asiakas']}</{$ero}>";
			echo "<{$ero}>{$row['ytunnus']}</{$ero}>";
			echo "<{$ero}>{$row['summa']}</{$ero}>";
			echo "<{$ero}>".tv1dateconv($row["tapvm"])."</{$ero}>";
			echo "<{$ero}>{$row['laatija']}</{$ero}>";

			$laskutyyppi = $row["tila"];
			$alatila	 = $row["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require ("inc/laskutyyppi.inc");

			echo "<{$ero} valign='top'>".t($laskutyyppi)." ".t($alatila)."</{$ero}>";
			echo "<{$ero} valign='top'>";

			$selmo = $selhy = $selre = "";
			if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'MONISTA') $selmo = "CHECKED";
			if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'HYVITA')  $selhy = "CHECKED";
			if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'REKLAMA') $selre = "CHECKED";

			if ($toim == '') {
				echo "<input type='radio' name='monistettavat[{$row['tilaus']}]' value='MONISTA' {$selmo}>".t("Monista")."<br>";
				echo "<input type='radio' name='monistettavat[{$row['tilaus']}]' value='HYVITA' {$selhy}>".t("Hyvit‰")."<br>";
				echo "<input type='radio' name='monistettavat[{$row['tilaus']}]' value='REKLAMA' {$selre}>".t("Reklamaatio")."<br>";
			}
			else {
				echo "<input type='checkbox' name='monistettavat[{$row['tilaus']}]' value='MONISTA' {$selmo}>".t("Monista")."<br>";
			}

			if ($toim == '') {
				$sel = "";
				if (isset($korjaaalvit[$row["tilaus"]]) and $korjaaalvit[$row["tilaus"]] != '') $sel = "CHECKED";

				echo "<{$ero} valign='top' nowrap>";
				echo "<input type='checkbox' name='korjaaalvit[{$row['tilaus']}]' value='on' {$sel}> ".t("Korjaa alvit")."<br>";

				// Katotaan ettei yksik‰‰n tuote ole sarjanumeroseurannassa, silloin ei voida turvallisesti laittaa suoraan laskutukseen
				$query = "	SELECT tuote.sarjanumeroseuranta
							FROM tilausrivi
							JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.sarjanumeroseuranta != '')
							WHERE tilausrivi.yhtio = '{$row['yhtio']}'
							AND tilausrivi.uusiotunnus = '{$row['tilaus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) == 0) {
					$sel = "";
					if (isset($suoraanlasku[$row["tilaus"]]) and $suoraanlasku[$row["tilaus"]] != '') {
						$sel = "CHECKED";
					}
					echo "<input type='checkbox' name='suoraanlasku[{$row['tilaus']}]' value='on' {$sel}> ".t("Suoraan laskutukseen")."<br>";
				}

				$sel = "";
				if (isset($sailytaprojekti[$row["tilaus"]]) and $sailytaprojekti[$row["tilaus"]] != '') {
					$sel = "CHECKED";
				}

				echo "<input type='checkbox' name='sailytaprojekti[{$row['tilaus']}]' value='on' {$sel}> ".t("S‰ilyt‰ projektitiedot")."<br>";

				if ($toim == '') {
					$sel = "";
					if (isset($sailytatyomaarays[$row["tilaus"]]) and $sailytatyomaarays[$row["tilaus"]] != '') {
						$sel = "CHECKED";
					}

					echo "<input type='checkbox' name='sailytatyomaarays[{$row['tilaus']}]' value='on' {$sel}> ".t("S‰ilyt‰ tyˆm‰‰r‰ystiedot")."<br>";
				}

				if ($toim == '' and $yhtiorow['rahti_hinnoittelu'] == '') {
					$sel = "";
					if (isset($korjaarahdit[$row["tilaus"]]) and $korjaarahdit[$row["tilaus"]] != '') {
						$sel = "CHECKED";
					}

					echo "<input type='checkbox' name='korjaarahdit[{$row['tilaus']}]' value='on' {$sel}> ".t("Laske rahtiveloitus uudestaan")."<br>";
				}


				echo "</{$ero}>";
			}

			echo "<{$ero} valign='top'><a href='?tunnus={$row['tilaus']}&tunnukset={$tunnukset}&asiakasid={$asiakasid}&otunnus={$otunnus}&laskunro={$laskunro}&ppa={$ppa}&kka={$kka}&vva={$vva}&ppl={$ppl}&kkl={$kkl}&vvl={$vvl}&tee=NAYTATILAUS&toim={$toim}'>".t("N‰yt‰")."</a></{$ero}>";
			echo "</tr>";
		}

		echo "</table><br>";
		echo "<input type='submit' value='".t("Monista")."'></form>";
	}
	else {
		echo t("Ei tilauksia")."...<br><br>";
	}
}

if ($tee == 'MONISTA') {

	// $tunnus joka on array joss on monistettavat laskut
	// $kklkm kopioiden m‰‰r‰
	// Jos hyvit‰ on 'on', niin silloin $kklkm t‰ytyy aina olla 1
	// $korjaaalvit array kertoo korjataanko kopioitavat tilauksen alvit
	// $suoraanlasku array sanoo ett‰ tilausta ei ker‰t‰ vaan se menee suoraan laskutusjonoon

	// Otetaan uudet tunnukset talteen
	$tulos_ulos = array();

	if (count($monistettavat) == 0) {
		echo "<font class='error'>Et valinnut yht‰‰n laskua monistettavaksi/hyvitett‰v‰ksi</font><br>";
		$tee = "";
	}

	foreach ($monistettavat as $lasku => $kumpi) {

		$alvik 			= "";
		$slask 			= "";
		$sprojekti  	= "";
		$koptyom		= "";
		$korjrahdit		= "";

		if (isset($korjaaalvit[$lasku]) and $korjaaalvit[$lasku] != '')  			$alvik		= "on";
		if (isset($suoraanlasku[$lasku]) and $suoraanlasku[$lasku] != '') 			$slask		= "on";
		if (isset($sailytaprojekti[$lasku]) and $sailytaprojekti[$lasku] != '') 	$sprojekti	= "on";
		if (isset($sailytatyomaarays[$lasku]) and $sailytatyomaarays[$lasku] != '')	$koptyom 	= "on";
		if (isset($korjaarahdit[$lasku]) and $korjaarahdit[$lasku] != '')			$korjrahdit	= "on";

		if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
			$kklkm = 1;
			echo t("Hyvitet‰‰n")." ";
		}
		else {
			echo t("Kopioidaan")." ";
		}

		if ($toim == 'SOPIMUS') {
			echo "{$kklkm} ".t("sopimus(ta)").".<br><br>";
		}
		elseif ($toim == 'TARJOUS') {
			echo "{$kklkm} ".t("tarjous(ta)").".<br><br>";
		}
		elseif ($toim == 'TYOMAARAYS') {
			echo "{$kklkm} ".t("tyˆm‰‰r‰ys(t‰)").".<br><br>";
		}
		elseif ($toim == 'TILAUS' or $toim == 'ENNAKKOTILAUS') {
			echo "{$kklkm} ".t("tilaus(ta)").".<br><br>";
		}
		elseif ($toim == 'OSTOTILAUS') {
			echo "{$kklkm} ".t("ostotilaus(ta)").".<br><br>";
		}
		else {
			echo "{$kklkm} ".t("lasku(a)").".<br><br>";
		}

		for ($monta = 1; $monta <= $kklkm; $monta++) {

			$query = "	SELECT *
						FROM lasku
						WHERE tunnus = '{$lasku}'
						AND yhtio IN ('".implode("','", $yhtiot)."')";
			$monistares = pupe_query($query);
			$monistarow = mysql_fetch_array($monistares);

			$squery = "	SELECT *
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$monistarow['liitostunnus']}'";
			$asiakres = pupe_query($squery);
			$asiakrow = mysql_fetch_array($asiakres);

			$fields = "yhtio";
			$values = "'$kukarow[yhtio]'";

			// Ei monisteta tunnusta
			for ($i = 1; $i < mysql_num_fields($monistares) - 1; $i++) {

				$fields .= ", ".mysql_field_name($monistares, $i);

				switch (mysql_field_name($monistares, $i)) {
					case 'ytunnus':
					case 'liitostunnus':
					case 'nimi':
					case 'nimitark':
					case 'osoite':
					case 'postino':
					case 'postitp':
					case 'toim_nimi':
					case 'toim_nimitark':
					case 'toim_osoite':
					case 'toim_postino':
					case 'toim_postitp':
					case 'yhtio_nimi':
					case 'yhtio_osoite':
					case 'yhtio_postino':
					case 'yhtio_postitp':
					case 'yhtio_maa':
					case 'yhtio_ovttunnus':
					case 'yhtio_kotipaikka':
					case 'yhtio_toimipaikka':
					case 'verkkotunnus':
					case 'myyja':
					case 'kassalipas':
					case 'ovttunnus':
					case 'toim_ovttunnus':
					case 'maa':
					case 'toim_maa':
						if ($kukarow["yhtio"] != $monistarow["yhtio"]) {
							$values .= ", ''";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
					break;
					case 'maksuehto':

						$query = "	SELECT tunnus, jv
									FROM maksuehto
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND kaytossa = ''
									AND (sallitut_maat = '' OR sallitut_maat LIKE '%{$monistarow['maa']}%')
									AND tunnus = '{$monistarow[$i]}'";
						$abures = pupe_query($query);

						$maksuehto_ok = TRUE;

						if (mysql_num_rows($abures) == 1) {
							$aburow = mysql_fetch_assoc($abures);

							if ($kumpi == 'HYVITA' and $aburow["jv"] != "") {
								// Ei laiteta j‰lkivaatimusta hyvityslaskulle
								$maksuehto_ok = FALSE;
							}
						}
						else {
							// Maksuehtoa ei en‰‰ lˆydy
							$maksuehto_ok = FALSE;
						}

						if ($maksuehto_ok) {
							$values .= ", '".$monistarow[$i]."'";
						}
						else {
							// Otetaan firman eka maksuehto
							$query = "	SELECT tunnus
										FROM maksuehto
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND kaytossa = ''
										AND (sallitut_maat = '' OR sallitut_maat LIKE '%{$monistarow['maa']}%')
										AND kateinen = ''
										AND jv = ''
										AND jaksotettu = ''
										AND erapvmkasin = ''
										ORDER BY jarjestys, teksti, tunnus
										LIMIT 1";
							$abures = pupe_query($query);
							$aburow = mysql_fetch_assoc($abures);

							$values .= ", '{$aburow['tunnus']}'";
						}
					break;
					case 'toimaika':
						if (($kumpi == 'HYVITA' or $kumpi == 'REKLAMA' or $yhtiorow["tilausrivien_toimitettuaika"] == 'X') and $toim != 'OSTOTILAUS') {
							$values .= ", '{$monistarow[$i]}'";
						}
						else {
							$values .= ", now()";
						}
						break;
					case 'kerayspvm':
					case 'luontiaika':
						$values .= ", now()";
						break;
					case 'alatila':
						if ($toim == 'SOPIMUS') {
							$values .= ", 'V'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'tila':
						if ($kumpi == 'REKLAMA') {
							$values .= ", 'C'";
						}
						elseif ($toim == 'SOPIMUS') {
							$values .= ", '0'";
						}
						elseif ($toim == 'TARJOUS') {
							$values .= ", 'T'";
						}
						elseif ($toim == 'TYOMAARAYS' or $koptyom == 'on') {
							$values .= ", 'A'";
						}
						elseif ($toim == 'OSTOTILAUS') {
							$values .= ", 'O'";
						}
						elseif ($toim == 'ENNAKKOTILAUS') {
							$values .= ", 'E'";
						}
						else {
							$values .= ", 'N'";
						}
						break;
					case 'tilaustyyppi':
						if ($kumpi == 'REKLAMA') {
							$values .= ", 'R'";
							break;
						}
						elseif ($toim == 'TYOMAARAYS' or $koptyom == 'on') {
							$values .= ", 'A'";
							break;
						}
						elseif ($toim == 'TARJOUS') {
							$values .= ", 'T'";
							break;
						}
						elseif ($toim == 'ENNAKKOTILAUS') {
							$values .= ", 'E'";
							break;
						}
					case 'tunnus':
					case 'tapvm':
					case 'kapvm':
					case 'erpcm':
					case 'suoraveloitus':
					case 'olmapvm':
					case 'summa':
					case 'summa_valuutassa':
					case 'kasumma':
					case 'kasumma_valuutassa':
					case 'hinta':
					case 'kate':
					case 'arvo':
					case 'arvo_valuutassa':
					case 'saldo_maksettu':
					case 'saldo_maksettu_valuutassa':
					case 'pyoristys':
					case 'pyoristys_valuutassa':
					case 'maksaja':
					case 'lahetepvm':
					case 'h1time':
					case 'lahetepvm':
					case 'laskuttaja':
					case 'laskutettu':
					case 'viite':
					case 'laskunro':
					case 'mapvm':
					case 'tilausvahvistus':
					case 'viikorkoeur':
					case 'tullausnumero':
					case 'kerayslista':
					case 'viikorkoeur':
					case 'noutaja':
					case 'jaksotettu':
					case 'factoringsiirtonumero':
					case 'laskutuspvm':
					case 'maksuaika':
					case 'maa_maara':
					case 'kuljetusmuoto':
					case 'kauppatapahtuman_luonne':
					case 'sisamaan_kuljetus':
					case 'sisamaan_kuljetusmuoto':
					case 'poistumistoimipaikka':
					case 'poistumistoimipaikka_koodi':
						$values .= ", ''";
						break;
					case 'clearing':
						if ($kumpi == 'HYVITA') {
							$values .= ", 'HYVITYS'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'vanhatunnus':
						if ($kumpi == 'HYVITA') {
							$values .= ", '{$lasku}'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'laatija':
						$values .= ", '{$kukarow['kuka']}'";
						break;
					case 'tunnusnippu':
						if ($sprojekti == "on") {
							$values .= ", '".$monistarow[$i]."'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'eilahetetta':
						if ($slask == 'on') {
							echo t("Tilaus laitetaan suoraan laskutusjonoon")."<br>";
							$values .= ", 'o'";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'alv':
						//Korjataanko laskun alvit
						if ($alvik == "on") {
							// katsotaan miten vienti ja ALV k‰sitell‰‰n
							$alv_velvollisuus = "";
							$uusi_alv = 0;

							// jos meill‰ on lasku menossa ulkomaille
							if (isset($asiakrow["maa"]) and $asiakrow["maa"] != "" and $asiakrow["maa"] != $yhtiorow["maa"]) {
								// tutkitaan ollaanko siell‰ alv-rekisterˆity
								$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$asiakrow[maa]' and vat_numero != ''";
								$alhire = pupe_query($alhqur);

								// ollaan alv-rekisterˆity, aina kotimaa myynti ja alvillista
								if (mysql_num_rows($alhire) == 1) {
									$alhiro  = mysql_fetch_assoc($alhire);

									// haetaan maan oletusalvi
									$query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark='o' and selitetark_2='$asiakrow[maa]'";
									$alhire = pupe_query($query);

									// jos ei lˆydy niin menn‰‰n erroriin
									if (mysql_num_rows($alhire) == 0) {
										echo "<font class='error'>".t("VIRHE: Oletus ALV-kantaa ei lˆydy asiakkaan maahan")." $asiakrow[maa]!</font><br>";
									}
									else {
										$apuro  = mysql_fetch_assoc($alhire);
										// n‰m‰ t‰ss‰ keisiss‰ aina n‰in
										$uusi_alv  		  = $apuro["selite"];
										$vienti 		  = "";
										$alv_velvollisuus = $alhiro["vat_numero"];
									}
								}
							}

							//yhtiˆn oletusalvi!
							$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
							$wtres  = pupe_query($wquery);
							$wtrow  = mysql_fetch_assoc($wtres);

							if ($alv_velvollisuus != "") {
								$uusi_alv = $uusi_alv;
							}
							elseif ($asiakrow["vienti"] == '') {

								if ($asiakrow['alv'] == 0) {
									$uusi_alv = 0;
								}

								if ($asiakrow['alv'] == $wtrow["selite"]) {
									$uusi_alv = $wtrow['selite'];
								}
							}
							else {
								$uusi_alv = 0;
							}

							$values .= ", '{$uusi_alv}'";

							echo t("Korjataan laskun ALVia").":  {$monistarow['alv']} --> {$uusi_alv}<br>";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'ketjutus':
						if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA' or $alvik == "on") {
							echo t("Hyvityst‰/ALV-korjausta ei ketjuteta")."<br>";
							$values .= ", 'x'";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'viesti':
						if ($kumpi == 'HYVITA' and $alvik == "on") {
							$values .= ", '".t("Hyvitet‰‰n ja tehd‰‰n ALV-korjaus laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
						}
						elseif ($kumpi == 'HYVITA') {
							$values .= ", '".t("Hyvitys laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
						}
						elseif ($kumpi == 'REKLAMA') {
							$values .= ", '".t("Reklamaatio laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
						}
						elseif($kumpi == 'MONISTA' and $alvik == "on") {
							$values .= ", '".t("ALV-korjaus laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'vienti_kurssi';
						// hyvityksiss‰ pidet‰‰n kurssi samana, tai jos korjataan rahtikuluja
						if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA' or ($toim == '' and $kumpi == 'MONISTA' and $korjrahdit == 'on')) {
							if ($monistarow[$i] == 0) {
								// Vanhoilla u-laskuilla ei ole vienti kurssia....
								$vienti_kurssi = @round($monistarow["arvo"] / $monistarow["arvo_valuutassa"], 9);

								$values .= ", '{$vienti_kurssi}'";
							}
							else {
								$values .= ", '".$monistarow[$i]."'";
							}
						}
						else {
							$vquery = "	SELECT kurssi
										FROM valuu
										WHERE yhtio = '{$kukarow['yhtio']}'
										and nimi	= '{$monistarow['valkoodi']}'";
							$vresult = pupe_query($vquery);
							$valrow = mysql_fetch_array($vresult);
							$values .= ", '{$valrow['kurssi']}'";
						}
						break;
					case 'kolmikantakauppa':
						if ($monistarow[$i] != "") {
							$values .= ", 'M'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'sisviesti1':
						if ($monistarow['kolmikantakauppa'] != "") {
							$values .= ", '".str_replace("VAT 0% Triangulation.\n", "", $monistarow['sisviesti1'])."'";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					default:
						$values .= ", '".$monistarow[$i]."'";
				}
			}

			$kysely  = "INSERT into lasku ({$fields}) VALUES ({$values})";
			$insres  = pupe_query($kysely);
			$utunnus = mysql_insert_id();

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$utunnus}'";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);

			$tulos_ulos[] = $utunnus;

			if ($toim == 'SOPIMUS') {
				echo t("Uusi sopimusnumero on")." {$utunnus}<br><br>";
			}
			else {
				echo t("Uusi tilausnumero on")." {$utunnus}<br><br>";
			}

			//	P‰ivitet‰‰n myˆs tunnusnippu jotta t‰t‰ voidaan versioida..
			if ($toim == "TARJOUS" and $yhtiorow["tarjouksen_voi_versioida"] != "") {
				$kysely = "	UPDATE lasku SET
							tunnusnippu = tunnus
							WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$utunnus}'";
				$updres = pupe_query($kysely);
			}

			if ($toim == "TARJOUS" and $monistarow["jaksotettu"] > 0) {

				// Oliko meill‰ maksusopparia?
				$query = "	SELECT *
							FROM maksupositio
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$monistarow['jaksotettu']}'";
				$sompmonres = pupe_query($query);

				if (mysql_num_rows($sompmonres) > 0) {

					while($sopmonrow = mysql_fetch_array($sompmonres)) {

						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for($i = 1; $i < mysql_num_fields($sompmonres) - 1; $i++) {

							$fields .= ", ".mysql_field_name($sompmonres,$i);

							switch (mysql_field_name($sompmonres,$i)) {
								case 'otunnus':
									$values .= ", '{$utunnus}'";
									break;
								default:
									$values .= ", '".$monistalisrow[$i]."'";
							}
						}

						$kysely  = "INSERT INTO maksupositio ({$fields}) VALUES ({$values})";
						$insres3 = pupe_query($kysely);
					}

					//	P‰ivitet‰‰n jaksotettu myˆs laskulle
					$kysely = "	UPDATE lasku SET
								jaksotettu = '{$utunnus}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$utunnus}'";
					$updres = pupe_query($kysely);
				}
			}

			//Kopioidaan otsikon lisatiedot
			$query = "	SELECT *
						FROM laskun_lisatiedot
						WHERE otunnus = '{$lasku}'
						AND yhtio = '{$monistarow['yhtio']}'";
			$monistalisres = pupe_query($query);

			if (mysql_num_rows($monistalisres) > 0) {
				$monistalisrow = mysql_fetch_array($monistalisres);

				$fields = "yhtio";
				$values = "'{$kukarow['yhtio']}'";

				// Ei monisteta tunnusta
				for($i = 1; $i < mysql_num_fields($monistalisres) - 1; $i++) {

					$fields .= ", ".mysql_field_name($monistalisres,$i);

					switch (mysql_field_name($monistalisres,$i)) {
						case 'otunnus':
							$values .= ", '{$utunnus}'";
							break;
						default:
							$values .= ", '".$monistalisrow[$i]."'";
					}
				}

				$kysely = "INSERT INTO laskun_lisatiedot ({$fields}) VALUES ({$values})";
				$insres2 = pupe_query($kysely);
			}

			if ($toim == 'TYOMAARAYS' or $koptyom == 'on' or $kumpi == 'REKLAMA') {

				if ($koptyom == 'on') {
					$query = "	SELECT DISTINCT otunnus AS tyomaarays
								FROM tilausrivi
								WHERE uusiotunnus = '{$lasku}'
								AND kpl <> 0
								AND tyyppi = 'L'
								AND yhtio = '{$monistarow['yhtio']}'
								ORDER BY tunnus
								LIMIT 1";
					$monistalisres = pupe_query($query);
					$monistalisrow = mysql_fetch_array($monistalisres);

					$tyomaarays = $monistalisrow["tyomaarays"];
				}
				else {
					$tyomaarays = $lasku;
				}

				//Kopioidaan otsikon tyˆm‰‰r‰ystiedot
				$query = "	SELECT *
							FROM tyomaarays
							WHERE otunnus = '{$tyomaarays}'
							AND yhtio = '{$monistarow['yhtio']}'";
				$monistalisres = pupe_query($query);
				$monistalisrow = mysql_fetch_array($monistalisres);

				$fields = "yhtio";
				$values = "'{$kukarow['yhtio']}'";

				for($i = 1; $i < mysql_num_fields($monistalisres); $i++) {

					$fields .= ", ".mysql_field_name($monistalisres,$i);

					switch (mysql_field_name($monistalisres,$i)) {
						case 'otunnus':
							$values .= ", '{$utunnus}'";
							break;
						default:
							$values .= ", '".$monistalisrow[$i]."'";
					}
				}

				$kysely = "INSERT INTO tyomaarays ({$fields}) VALUES ({$values})";
				$insres2 = pupe_query($kysely);
			}

			if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS' or $toim == 'OSTOTILAUS' or $toim == 'ENNAKKOTILAUS') {
				$query = "	SELECT *
							FROM tilausrivi
							WHERE otunnus = '{$lasku}'
							AND yhtio = '{$monistarow['yhtio']}'
							ORDER BY otunnus, tunnus";
			}
			else {
				$query = "	SELECT *
							FROM tilausrivi
							WHERE uusiotunnus = '{$lasku}'
							AND kpl <> 0
							AND tyyppi = 'L'
							AND yhtio = '{$monistarow['yhtio']}'
							ORDER BY otunnus, tunnus";
			}
			$rivires = pupe_query($query);

			while ($rivirow = mysql_fetch_array($rivires)) {
				$paikkavaihtu = 0;
				$uusikpl = 0;

				$pquery = "	SELECT tunnus
							FROM tuotepaikat
							WHERE yhtio = '{$monistarow['yhtio']}'
							AND tuoteno = '{$rivirow['tuoteno']}'
							AND hyllyalue =	'{$rivirow['hyllyalue']}'
							AND hyllynro = '{$rivirow['hyllynro']}'
							AND hyllyvali =	'{$rivirow['hyllyvali']}'
							AND hyllytaso =	'{$rivirow['hyllytaso']}'
							LIMIT 1";
				$presult = pupe_query($pquery);

				if (mysql_num_rows($presult) == 0) {
					$p2query = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
								FROM tuotepaikat
								WHERE yhtio = '{$monistarow['yhtio']}'
								AND tuoteno = '{$rivirow['tuoteno']}'
								AND oletus != ''
								LIMIT 1";
					$p2result = pupe_query($p2query);

					if (mysql_num_rows($p2result) == 1) {
						$paikka2row = mysql_fetch_array($p2result);
						$paikkavaihtu = 1;
					}
				}

				$rfields = "yhtio";
				$rvalues = "'{$kukarow['yhtio']}'";

				for ($i = 1; $i < mysql_num_fields($rivires) - 1; $i++) { // Ei tunnusta

					$rfields .= ", ".mysql_field_name($rivires, $i);

					switch (mysql_field_name($rivires, $i)) {
						case 'toimaika':
							if ($yhtiorow["tilausrivien_toimitettuaika"] == 'X' and $toim != 'OSTOTILAUS') {
								$rvalues .= ", '".$rivirow[$i]."'";
							}
							else {
								$rvalues .= ", now()";
							}
							break;
						case 'kerayspvm':
						case 'laadittu':
							$rvalues .= ", now()";
							break;
						case 'tunnus':
						case 'laskutettu':
						case 'laskutettuaika':
						case 'toimitettu':
						case 'toimitettuaika':
						case 'keratty':
						case 'kerattyaika':
						case 'kpl':
						case 'rivihinta':
						case 'rivihinta_valuutassa':
						case 'kate':
						case 'uusiotunnus':
						case 'jaksotettu':
							$rvalues .= ", ''";
							break;
						case 'kommentti':
							if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS' or $toim == 'OSTOTILAUS' or $toim == 'ENNAKKOTILAUS') {
								$rvalues .= ", '{$rivirow['kommentti']}'";
							}
							else {
								$rvalues .= ", ''";
							}
							break;
						case 'otunnus':
							$rvalues .= ", '{$utunnus}'";
							break;
						case 'laatija':
							$rvalues .= ", '{$kukarow['kuka']}'";
							break;
						case 'varattu':
							if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
								$uusikpl = ($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"]) * -1;
								$rvalues .= ", '{$uusikpl}'";

							}
							else {
								$uusikpl = ($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"]);
								$rvalues .= ", '{$uusikpl}'";
							}
							break;
						case 'tilkpl':
							if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
								$rvalues .= ", '".(($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"]) * -1)."'";
							}
							else {
								$rvalues .= ", '".($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"])."'";
							}
							break;
						case 'hyllyalue':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '{$paikka2row['hyllyalue']}'";
							}
							else {
								$rvalues .= ", '{$rivirow['hyllyalue']}'";
							}
							break;
						case 'hyllynro':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '{$paikka2row['hyllynro']}'";
							}
							else {
								$rvalues .= ", '{$rivirow['hyllynro']}'";
							}
							break;
						case 'hyllyvali':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '{$paikka2row['hyllyvali']}'";
							}
							else {
								$rvalues .= ", '{$rivirow['hyllyvali']}'";
							}
							break;
						case 'hyllytaso':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '{$paikka2row['hyllytaso']}'";
							}
							else {
								$rvalues .= ", '{$rivirow['hyllytaso']}'";
							}
							break;
						case 'alv':
							//Korjataanko tilausrivin alvit
							if ($alvik == "on") {
								$rvalues .= ", '{$uusi_alv}'";
								$rivirow['orig_alv'] = $rivirow[$i];
							}
							else {
								$rvalues .= ", '".$rivirow[$i]."'";
							}
							break;
						case 'tyyppi':
							// Tarjouskase
							if ($toim == 'TARJOUS') {
								$rvalues .= ", 'T'";
							}
							else {
								$rvalues .= ", '".$rivirow[$i]."'";
							}
							break;
						default:
							$rvalues .= ", '".$rivirow[$i]."'";
					}
				}

				$kysely = "INSERT INTO tilausrivi ({$rfields}) VALUES ({$rvalues})";
				$insres = pupe_query($kysely);
				$insid = mysql_insert_id();

				//Kopioidaan tilausrivin lisatiedot
				$query = "	SELECT *
							FROM tilausrivin_lisatiedot
							WHERE tilausrivitunnus = '{$rivirow['tunnus']}'
							and yhtio = '{$monistarow['yhtio']}'";
				$monistares2 = pupe_query($query);

				if (mysql_num_rows($monistares2) > 0) {
					$monistarow2 = mysql_fetch_array($monistares2);

					$kysely = "	INSERT INTO tilausrivin_lisatiedot
								SET yhtio 			= '{$kukarow['yhtio']}',
								laatija				= '{$kukarow['kuka']}',
								luontiaika 			= now(),
								tilausrivitunnus	= {$insid},";

					for ($i = 0; $i < mysql_num_fields($monistares2) - 1; $i++) { // Ei monisteta tunnusta
						switch (mysql_field_name($monistares2, $i)) {
							case 'yhtio':
							case 'laatija':
							case 'luontiaika':
							case 'tilausrivitunnus':
							case 'tiliointirivitunnus':
							case 'tilausrivilinkki':
							case 'toimittajan_tunnus':
							case 'tunnus':
							case 'muutospvm':
							case 'muuttaja':
								break;
							case 'osto_vai_hyvitys':
								if ($monistarow2[$i] == "O" and ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA')) {
									$kysely .= mysql_field_name($monistares2, $i)."='H',";
								}
								elseif ($monistarow2[$i] == "H" and ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA')) {
									$kysely .= mysql_field_name($monistares2, $i)."='O',";
								}
								else {
									$kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";
								}
								break;
							default:
								$kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";
						}
					}

					$kysely  = substr($kysely, 0, -1);
					$insres2 = pupe_query($kysely);
				}

				// Kopsataan sarjanumerot kuntoon jos tilauksella oli sellaisia
				if (($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') and $kukarow["yhtio"] == $monistarow["yhtio"]) {
					if ($rivirow["kpl"] > 0) {
						$tunken = "myyntirivitunnus";
						$tunken2 = "ostorivitunnus";
					}
					else {
						$tunken = "ostorivitunnus";
						$tunken2 = "myyntirivitunnus";
					}

					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$rivirow['tuoteno']}'
								AND {$tunken} = '{$rivirow['tunnus']}'
								AND {$tunken2} = 0";
					$sarjares = pupe_query($query);

					while ($sarjarow = mysql_fetch_array($sarjares)) {
						if ($uusikpl > 0) {
							$uusi_tunken = "myyntirivitunnus";
						}
						else {
							$uusi_tunken = "ostorivitunnus";
						}

						$query = "SELECT sarjanumeroseuranta FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$rivirow['tuoteno']}'";
						$sarjatuoteres = pupe_query($query);
						$sarjatuoterow = mysql_fetch_array($sarjatuoteres);

						if ($sarjatuoterow["sarjanumeroseuranta"] == "E" or $sarjatuoterow["sarjanumeroseuranta"] == "F" or $sarjatuoterow["sarjanumeroseuranta"] == "G") {
							$query = "	INSERT INTO sarjanumeroseuranta
										SET yhtio		= '{$kukarow['yhtio']}',
										tuoteno			= '{$rivirow['tuoteno']}',
										sarjanumero		= '{$sarjarow['sarjanumero']}',
										lisatieto		= '{$sarjarow['lisatieto']}',
										kaytetty		= '{$sarjarow['kaytetty']}',
										{$uusi_tunken}	= '{$insid}',
										takuu_alku 		= '{$sarjarow['takuu_alku']}',
										takuu_loppu		= '{$sarjarow['takuu_loppu']}',
										parasta_ennen	= '{$sarjarow['parasta_ennen']}',
										era_kpl			= '{$sarjarow['era_kpl']}',";

							if ($paikkavaihtu == 1) {
								$query .= "	hyllyalue   = '{$paikka2row['hyllyalue']}',
											hyllynro    = '{$paikka2row['hyllynro']}',
											hyllytaso   = '{$paikka2row['hyllytaso']}',
											hyllyvali   = '{$paikka2row['hyllyvali']}',";
							}
							else {
								$query .= "	hyllyalue   = '{$rivirow['hyllyalue']}',
											hyllynro    = '{$rivirow['hyllynro']}',
											hyllytaso   = '{$rivirow['hyllytaso']}',
											hyllyvali   = '{$rivirow['hyllyvali']}',";
							}

							$query .= "	laatija			= '{$kukarow['kuka']}',
										luontiaika		= now()";
							$sres = pupe_query($query);
						}
						else {
							//Tutkitaan lˆytyykˆ t‰llanen vapaa sarjanumero jo?
							$query = "	SELECT tunnus
										FROM sarjanumeroseuranta
										WHERE yhtio			= '{$kukarow['yhtio']}'
										AND tuoteno			= '{$rivirow['tuoteno']}'
										AND sarjanumero 	= '{$sarjarow['sarjanumero']}'
										AND {$uusi_tunken}	= 0
										LIMIT 1";
							$sarjares1 = pupe_query($query);

							if (mysql_num_rows($sarjares1) == 1) {
								$sarjarow1 = mysql_fetch_array($sarjares1);

								$query = "	UPDATE sarjanumeroseuranta
											SET {$uusi_tunken} = '{$insid}', ";

								if ($paikkavaihtu == 1) {
									$query .= "	hyllyalue   = '{$paikka2row['hyllyalue']}',
												hyllynro    = '{$paikka2row['hyllynro']}',
												hyllytaso   = '{$paikka2row['hyllytaso']}',
												hyllyvali   = '{$paikka2row['hyllyvali']}'";
								}
								else {
									$query .= "	hyllyalue   = '{$rivirow['hyllyalue']}',
												hyllynro    = '{$rivirow['hyllynro']}',
												hyllytaso   = '{$rivirow['hyllytaso']}',
												hyllyvali   = '{$rivirow['hyllyvali']}'";
								}

								$query .= "	WHERE tunnus 	= '{$sarjarow1['tunnus']}'
											AND yhtio		= '{$kukarow['yhtio']}'";
								$sres = pupe_query($query);
							}
							else {
								$query = "	INSERT INTO sarjanumeroseuranta
											SET yhtio		= '{$kukarow['yhtio']}',
											tuoteno			= '{$rivirow['tuoteno']}',
											sarjanumero		= '{$sarjarow['sarjanumero']}',
											lisatieto		= '{$sarjarow['lisatieto']}',
											kaytetty		= '{$sarjarow['kaytetty']}',
											{$uusi_tunken}	= '{$insid}',
											takuu_alku 		= '{$sarjarow['takuu_alku']}',
											takuu_loppu		= '{$sarjarow['takuu_loppu']}',
											parasta_ennen 	= '{$sarjarow['parasta_ennen']}',
											era_kpl			= '{$sarjarow['era_kpl']}',";

								if ($paikkavaihtu == 1) {
									$query .= "	hyllyalue   = '{$paikka2row['hyllyalue']}',
												hyllynro    = '{$paikka2row['hyllynro']}',
												hyllytaso   = '{$paikka2row['hyllytaso']}',
												hyllyvali   = '{$paikka2row['hyllyvali']}',";
								}
								else {
									$query .= "	hyllyalue   = '{$rivirow['hyllyalue']}',
												hyllynro    = '{$rivirow['hyllynro']}',
												hyllytaso   = '{$rivirow['hyllytaso']}',
												hyllyvali   = '{$rivirow['hyllyvali']}',";
								}

								$query .= "	laatija			= '{$kukarow['kuka']}',
											luontiaika		= now()";
								$sres = pupe_query($query);
							}
						}
					}
				}

				//tehd‰‰n alvikorjaus jos k‰ytt‰j‰ on pyyt‰nyt sit‰
				if ($alvik == "on" and $rivirow["hinta"] != 0) {

					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$monistarow['yhtio']}'
								AND tuoteno = '{$rivirow['tuoteno']}'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_array($tres);

					// Ohitetaan valuuttaproblematiikka
					$laskurow["vienti_kurssi"] = 1;

					$vanhahinta = $rivirow["hinta"];

					if ($yhtiorow["alv_kasittely"] == "") {

						if ($alv_velvollisuus != "") {
							$korj_alv = $uusi_alv;
						}
						else {
							$korj_alv = $trow["alv"];
						}

						$uusihinta = $rivirow['hinta'] / (1+$rivirow['orig_alv']/100) * (1+$korj_alv/100);

						if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
							$uusihinta = round($uusihinta, 6);
						}
						else {
							$uusihinta = round($uusihinta, $yhtiorow['hintapyoristys']);
						}
					}
					else {
						$uusihinta = $rivirow['hinta'];
					}

					list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, 1, '', $uusihinta, '');
					list($lis_hinta, $alehinta_alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

					if ($vanhahinta != $lis_hinta) {
						echo t("Korjataan hinta").": $trow[tuoteno], {$vanhahinta} --> {$lis_hinta},  $rivirow[alv] --> $alehinta_alv<br>";

						$query = "	UPDATE tilausrivi
									SET hinta = '{$lis_hinta}',
									alv 	  = '{$alehinta_alv}'
									where yhtio	= '{$kukarow['yhtio']}'
									and otunnus	= '{$utunnus}'
									and tunnus	= '{$insid}'";
						$tres = pupe_query($query);
					}
				}
			}

			//Korjataan perheid:t uusilla riveill‰
			$query = "	SELECT perheid, min(tunnus) uusiperheid
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$utunnus}'
						AND perheid != 0
						GROUP BY perheid";
			$copresult = pupe_query($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {
				$query = "	UPDATE tilausrivi
							SET perheid = '{$coprivirow['uusiperheid']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$utunnus}'
							AND perheid = '{$coprivirow['perheid']}'";
				$cores = pupe_query($query);
			}

			//Korjataan perheid2:t uusilla riveill‰
			$query = "	SELECT perheid2, min(tunnus) uusiperheid2
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$utunnus}'
						AND perheid2 != 0
						GROUP BY perheid2";
			$copresult = pupe_query($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {
				$query = "	UPDATE tilausrivi
							SET perheid2 = '{$coprivirow['uusiperheid2']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$utunnus}'
							AND perheid2 = '{$coprivirow['perheid2']}'";
				$cores = pupe_query($query);
			}


			// Korjataanko rahdit?
			if ($toim == '' and $kumpi == 'MONISTA' and $korjrahdit == 'on' and $monistarow['laskunro'] > 0 and $yhtiorow['rahti_hinnoittelu'] == '') {

				// Poistetaan virheelliset rahdit
				$query  = " UPDATE tilausrivi set tyyppi='D' where yhtio = '$kukarow[yhtio]' and otunnus='$utunnus' AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
				$addtil = pupe_query($query);

				$query   = "SELECT date_format(rahtikirjat.tulostettu, '%Y-%m-%d') tulostettu, group_concat(distinct lasku.tunnus) tunnukset
							FROM lasku, rahtikirjat, maksuehto
							WHERE lasku.yhtio 		= '$kukarow[yhtio]'
							and lasku.rahtivapaa 	= ''
							and lasku.kohdistettu 	= 'K'
							and lasku.yhtio 		= rahtikirjat.yhtio
							and lasku.tunnus 		= rahtikirjat.otsikkonro
							and lasku.yhtio 		= maksuehto.yhtio
							and lasku.maksuehto 	= maksuehto.tunnus
							AND lasku.tila 	 		= 'L'
							AND lasku.alatila		= 'X'
							AND lasku.laskunro		= '{$monistarow['laskunro']}'
							GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa, maksuehto.jv";
				$raresult  = pupe_query($query);

				while ($rahtirow = mysql_fetch_assoc($raresult)) {
					//haetaan ekan otsikon tiedot
					$query = "  SELECT lasku.*, maksuehto.jv
								FROM lasku, maksuehto
								WHERE lasku.yhtio ='$kukarow[yhtio]'
								AND lasku.tunnus in ($rahtirow[tunnukset])
								AND lasku.yhtio = maksuehto.yhtio
								AND lasku.maksuehto = maksuehto.tunnus
								ORDER BY lasku.tunnus
								LIMIT 1";
					$otsre = pupe_query($query);
					$laskurow = mysql_fetch_assoc($otsre);

					// haetaan rahdin hinta
					list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($rahtirow['tunnukset']);

					$query = "  SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
					$rhire = pupe_query($query);

					if ($rah_hinta > 0 and $virhe == 0 and mysql_num_rows($rhire) == 1) {

						$trow      = mysql_fetch_assoc($rhire);
						$otunnus   = $laskurow['tunnus'];
						$nimitys   = tv1dateconv($rahtirow['tulostettu'])." $laskurow[toimitustapa]";

						$ale_lisa_insert_query_1 = $ale_lisa_insert_query_2 = '';

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($rah_ale["ale{$alepostfix}"]) and $rah_ale["ale{$alepostfix}"] > 0) {
								$ale_lisa_insert_query_1 .= " ale{$alepostfix},";
								$ale_lisa_insert_query_2 .= " '".$rah_ale["ale{$alepostfix}"]."',";
							}
						}

						$query  = " INSERT INTO tilausrivi (laatija, laadittu, hinta, {$ale_lisa_insert_query_1} netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
									values ('automaatti', now(), '$rah_hinta', {$ale_lisa_insert_query_2} '$rah_netto', '1', '1', '$utunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$rah_alv', '')";
						$addtil = pupe_query($query);
					}
				}
			}

			if ($slask == "on") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus	= '{$utunnus}'";
				$result = pupe_query($query);
				$laskurow = mysql_fetch_array($result);

				$kukarow["kesken"] = $laskurow["tunnus"];

				require("tilauskasittely/tilaus-valmis.inc");
			}
		} # end for $monta
	}
	$tee = ''; //menn‰‰n alkuun
}

if ($tee == '' and $vain_monista == "") {
	//syˆtet‰‰n tilausnumero
	echo "<br><table>";
	echo "<form method = 'post'>";
	echo "<input type='hidden' name='toim' value='{$toim}'>";
	echo "<tr>";

	if ($toim == 'OSTOTILAUS') {
		echo "<th>".t("Toimittajan nimi")."</th>";
	}
	else {
		echo "<th>".t("Asiakkaan nimi")."</th>";
	}

	echo "<td><input type='text' size='10' name='ytunnus'></td></tr>";


	echo "<tr><th>".t("Tilausnumero")."</th><td><input type='text' size='10' name='otunnus'></td></tr>";

	if ($toim == '') {
		echo "<tr><th>".t("Laskunumero")."</th><td><input type='text' size='10' name='laskunro'></td></tr>";
	}

	echo "</table>";

	echo "<br><input type='submit' value='".t("Jatka")."'>";
	echo "</form>";

	if ($toim == '') {
		echo "<form method = 'post'>";
		echo "<input type='hidden' name='toim' value='{$toim}'>";
		echo "<input type='hidden' name='tee' value='mikrotila'>";
		echo "<br><input type='submit' value='".t("Lue monistettavat laskut tiedostosta")."'>";
		echo "</form>";
	}

	require ('inc/footer.inc');
}
