<?php

	require ("../inc/parametrit.inc");

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}


	echo "<font class='head'>".t("Korjaa keräys").":</font><hr>";

	if ($tee == 'KORJAA') {

		$query = "	UPDATE tilausrivi
					SET keratty = '',
					kerattyaika = ''
					WHERE otunnus = '$tunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$query  = "	UPDATE lasku
					set alatila = 'A'
					where yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'
					and tila = 'L'
					and alatila = 'C'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_affected_rows() != 1) {
			pupe_error("".t("Keräämättömäksi merkkaaminen ei onnistu")."! $query");
			exit;
		}

		$tee = '';

		if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
			$logistiikka_yhtio = $konsernivarasto_yhtiot;
		}
	}

	// meillä ei ole valittua tilausta
	if ($tee == '') {

		$formi = "find";
		$kentta = "etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form><br><br>";

		$haku = '';
		if (is_string($etsi)) $haku = "and lasku.nimi LIKE '%".mysql_real_escape_string($etsi)."%'";
		if (is_numeric($etsi)) $haku = "and lasku.tunnus = '".mysql_real_escape_string($etsi)."'";

		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		$kkl = date("m");
		$vvl = date("Y");
		$ppl = date("d");

		$query = "	SELECT distinct lasku.yhtio yhtio, lasku.tunnus, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija
					from lasku
					join tilausrivi on (tilausrivi.yhtio = lasku.yhtio
										and tilausrivi.otunnus = lasku.tunnus
										and tilausrivi.var != 'J'
										and tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00'
										and tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59')
					where lasku.$logistiikka_yhtiolisa
					and lasku.tila = 'L'
					and lasku.alatila = 'C'
					$haku";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) != 0) {
			echo "<table>";
			echo "<tr>";
			echo "<th>Yhtio</th>";
			echo "<th>Tilaus</th>";
			echo "<th>Asiakas</th>";
			echo "<th>Laadittu</th>";
			echo "<th>Laatija</th>";
			echo "<tr>";
		}

		while ($tilrow = mysql_fetch_array($tilre)) {
			echo "<tr>";
			echo "<td>$tilrow[yhtio]</td>";
			echo "<td>$tilrow[tunnus]</td>";
			echo "<td>$tilrow[asiakas]</td>";
			echo "<td>$tilrow[laadittu]</td>";
			echo "<td>$tilrow[laatija]</td>";
			echo "<td class='back'><form method='post' action='$PHP_SELF'>
					<input type='hidden' name='tee' value='KORJAA'>
					<input type='hidden' name='lasku_yhtio' value='$tilrow[yhtio]'>
				  	<input type='hidden' name='tunnus' value='$tilrow[tunnus]'>
				  	<input type='submit' name='tila' value='".t("Korjaa")."'>
					</form></td>";
			echo "</tr>";
		}

		if (mysql_num_rows($tilre) != 0) {
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään korjattavaa tilausta ei löytynyt")."...</font>";
		}

	}

	require ("inc/footer.inc");

?>
