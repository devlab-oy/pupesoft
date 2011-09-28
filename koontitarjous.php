<?php

	if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
		$_REQUEST["tee"] = $_POST["tee"] = $_GET["tee"] = "NAYTATILAUS";
	}

	if ($_REQUEST["tee"] == 'NAYTATILAUS' or $_POST["tee"] == 'NAYTATILAUS' or $_GET["tee"] == 'NAYTATILAUS') {
		$nayta_pdf = 1; //Generoidaan .pdf-file
		$ohje = 'off';
	}

	require ("inc/parametrit.inc");

	if (!isset($asiakasnimi)) $asiakasnimi = '';
	if (!isset($asiakasytunnus)) $asiakasytunnus = '';
	if (!isset($asiakasnro)) $asiakasnro = '';
	if (!isset($submit)) $submit = '';
	if (!isset($ppa)) $ppa = date("d");
	if (!isset($kka)) $kka = date("m");
	if (!isset($vva)) $vva = date("Y");
	if (!isset($ppl)) $ppl = date("d");
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");

	if ((isset($tee) and $tee == 'TULOSTA') or (isset($otunnukset_array) and $otunnukset_array != '')) {

		$tee = isset($tee) ? $tee : 'TULOSTA';

		$tulostimet[0] = 'Tarjous';
		if ($kappaleet > 0) {
			$komento["Tarjous"] .= " -# $kappaleet ";
		}

		if ((count($komento) == 0) and ($tee == 'TULOSTA')) {
			$otunnukset_array = urlencode(serialize($otunnukset_array));
			require("inc/valitse_tulostin.inc");
		}

		$otunnukset = '';

		$otunnukset_array = unserialize(urldecode($otunnukset_array));

		foreach ($otunnukset_array as $null => $otun) {
			$otunnukset .= $otun.",";
		}

		$otunnukset = substr($otunnukset, 0, -1);
		$mista = 'koontitarjous.php';

		require_once ("tilauskasittely/tulosta_tarjous.inc");

		tulosta_tarjous($otunnukset, $komento["Tarjous"], $kieli, $tee);
	}
	else {

		echo "<font class='head'>",t("Tulosta koontitarjous"),":</font><hr /><br />";

		if (isset($teekoontitarjous)) {
			echo "<font class='error'>",t("Valitse v‰hint‰‰n kaksi tarjousta"),"!</font><br /><br />";
		}

		echo "<form method='post'>";
		echo "<table>";
		echo "<tr><th>",t("Hae asiakas nimell‰"),"</th><td><input type='text' name='asiakasnimi' value='{$asiakasnimi}' /></td><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th>",t("Hae asiakas ytunnuksella"),"</th><td><input type='text' name='asiakasytunnus' value='{$asiakasytunnus}' /></td><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th>",t("Hae asiakas asiakasnumerolla"),"</th><td><input type='text' name='asiakasnro' value='{$asiakasnro}' /></td><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th>",t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)"),"</th>
				<td><input type='text' name='ppa' value='{$ppa}' size='3' />&nbsp;<input type='text' name='kka' value='{$kka}' size='3' />&nbsp;<input type='text' name='vva' value='{$vva}' size='5' /></td>
				</tr><tr><th>",t("loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)"),"</th>
				<td><input type='text' name='ppl' value='{$ppl}' size='3' />&nbsp;<input type='text' name='kkl' value='{$kkl}' size='3' />&nbsp;<input type='text' name='vvl' value='{$vvl}' size='5' /></td>
				<td class='back'><input type='submit' name='submit' value='",t("Etsi"),"'></td>";
		echo "</table>";
		echo "</form>";
	}

	if ($submit and (trim($asiakasnimi) != '' or trim($asiakasytunnus) != '' or trim($asiakasnro) != '')) {

		echo "<br /><br />";

		$wherelisa = '';

		if (isset($asiakasnimi) and trim($asiakasnimi) != '') {
			$wherelisa = " and asiakas.nimi like '%".mysql_real_escape_string(trim($asiakasnimi))."%' ";
		}

		if (isset($asiakasytunnus) and trim($asiakasytunnus) != '') {
			$wherelisa .= " and asiakas.ytunnus like '%".mysql_real_escape_string(trim($asiakasytunnus))."%' ";
		}

		if (isset($asiakasnro) and is_numeric(trim($asiakasnro))) {
			$wherelisa .= " and asiakas.asiakasnro like '%".(int) trim($asiakasnro)."%' ";
		}

		if (isset($valittuasiakas) and trim($valittuasiakas) != '') {
			$wherelisa .= " and asiakas.tunnus = '".(int) trim($asiakastunnus)."' ";
		}

		$query = "	SELECT nimi, nimitark, ytunnus, asiakasnro, tunnus
					FROM asiakas
					WHERE yhtio = '{$kukarow['yhtio']}'
					and laji != 'P'
					$wherelisa";
		$asiakasres = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>",t("Nimi"),"</th><th>",t("Ytunnus"),"</th><th>",t("Asiakasnumero"),"</th></tr>";

		if (mysql_num_rows($asiakasres) > 1) {
			while ($asiakasrow = mysql_fetch_assoc($asiakasres)) {
				echo "<form method='post'>";
				echo "<tr><td>{$asiakasrow['nimi']} {$asiakasrow['nimitark']}</td><td>{$asiakasrow['ytunnus']}</td><td>{$asiakasrow['asiakasnro']}</td><td><input type='submit' name='valittuasiakas' value='",t("Valitse"),"' /></td></tr>";
				echo "<input type='hidden' name='asiakasnimi' value='{$asiakasrow['nimi']}' />";
				echo "<input type='hidden' name='asiakasytunnus' value='{$asiakasrow['ytunnus']}' />";
				echo "<input type='hidden' name='asiakasnro' value='{$asiakasrow['asiakasnro']}' />";
				echo "<input type='hidden' name='asiakastunnus' value='{$asiakasrow['tunnus']}' />";
				echo "<input type='hidden' name='submit' value='ok' />";
				echo "</form>";
			}
		}
		else {
			$asiakasrow = mysql_fetch_assoc($asiakasres);

			echo "<tr><td>{$asiakasrow['nimi']} {$asiakasrow['nimitark']}</td><td>{$asiakasrow['ytunnus']}</td><td>{$asiakasrow['asiakasnro']}</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($asiakasres) == 1) {
			echo "<br />";

			$tarjouslisa = '';

			if (is_numeric($ppa) and is_numeric($kka) and is_numeric($vva) and is_numeric($ppl) and is_numeric($kkl) and is_numeric($vvl)) {
				$tarjouslisa .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";
			}

			$query_ale_lisa = generoi_alekentta('M');

			$query = "	SELECT lasku.tunnus, lasku.luontiaika, lasku.valkoodi,
						sum(round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.liitostunnus = '{$asiakasrow['tunnus']}'
						AND lasku.tila = 'T'
						AND lasku.alatila IN ('', 'A')
						$tarjouslisa
						GROUP BY lasku.tunnus
						LIMIT 100";
			$tarjousres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tarjousres) > 0) {

				echo "<font class='message'>",t("Tarjouksia lˆytyi")," ",mysql_num_rows($tarjousres)," ",t("kappaletta"),"! ",t("Vain ensimm‰iset 100 tarjousta n‰ytet‰‰n"),".</font><br /><br />";

				echo "<form method='post'>";
				echo "<table>";
				echo "<tr><th>",t("Tunnus"),"</th><th>",t("Summa"),"</th><th>",t("Luontiaika"),"</th><th>",t("Valitse"),"</th></tr>";

				while ($tarjousrow = mysql_fetch_assoc($tarjousres)) {

					$chk = '';

					if (isset($tunnukset) and in_array($tarjousrow['tunnus'], $tunnukset)) $chk = ' CHECKED';

					echo "<tr><td>{$tarjousrow['tunnus']}</td><td>{$tarjousrow['rivihinta']} {$tarjousrow['valkoodi']}</td><td>",tv1dateconv($tarjousrow['luontiaika'], "pitka"),"</td><td align='center'><input type='checkbox' name='otunnukset_array[]' value='{$tarjousrow['tunnus']}'{$chk} /></td></tr>";
				}

				echo "</table>";
				echo "<br /><input type='submit' name='teekoontitarjous' value='",t("Tee koontitarjous"),"' />";
				echo "<input type='hidden' name='asiakasnimi' value='{$asiakasnimi}' />";
				echo "<input type='hidden' name='asiakasytunnus' value='{$asiakasytunnus}' />";
				echo "<input type='hidden' name='asiakasnro' value='{$asiakasnro}' />";
				echo "<input type='hidden' name='mista' value='koontitarjous' />";
				echo "<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]' />";
				echo "</form>";


			}
			else {
				echo "<font class='message'>",t("Yht‰‰n tarjousta ei lˆytynyt"),".</font><br />";
			}
		}
	}

	require ("inc/footer.inc");

?>