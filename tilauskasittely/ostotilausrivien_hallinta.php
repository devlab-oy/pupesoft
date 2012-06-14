<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>",t("Toimittajan avoimet tilausrivit"),":</font><hr>";

	if (!isset($ytunnus)) $ytunnus = '';
	if (!isset($nayta_rivit)) $nayta_rivit = '';
	if (!isset($tee)) $tee = '';
	if (!isset($otunnus)) $otunnus = '';
	if (!isset($ojarj)) $ojarj = '';

	if ($ytunnus != '') {
		require ("inc/kevyt_toimittajahaku.inc");
	}

	if ($ytunnus == "") {
		// N�ytet��n muuten vaan sopivia tilauksia
		echo "<br><table>";
		echo "<form method = 'post'>";
		echo "<tr><th>",t("Toimittaja"),":</th><td><input type='text' size='10' name='ytunnus' value='{$ytunnus}'></td></tr>";
		echo "<tr><th>",t("N�yt�"),":</th>";

		$select = "";
		if ($nayta_rivit == 'vahvistamattomat') {
			$select = "selected";
		}

		echo "<td><select name='nayta_rivit'>";
		echo "<option value=''>",t("Kaikki avoimet rivit"),"</option>";
		echo "<option value='vahvistamattomat' {$select}>",t("Vain vahvistamattomia rivej�"),"</option></td>";
		echo "<td class='back'><input type='submit' value='",t("Etsi"),"'></td></tr>";
		echo "</form>";
		echo "</table><br>";

		echo t("tai");
		echo "<br><br>";

		echo "<table>";
		echo "<form method='post'>";
		echo "<input type='hidden' name='nayta_rivit' value='vahvistamattomat'>";
		echo "<tr><td class='back'><input type='submit' value='",t("N�yt� kaikkien toimittajien vahvistamattomat rivit"),"'></td></tr>";
		echo "</form>";
		echo "</table>";
	}

	if ($ytunnus == "" and $nayta_rivit == "vahvistamattomat") {
		$query = "	SELECT tilausrivi.otunnus, lasku.nimi, lasku.ytunnus
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.toimitettu = ''
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.kpl = 0
					AND tilausrivi.jaksotettu = 0
					GROUP BY tilausrivi.otunnus
					ORDER BY lasku.nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>",t("Toimittaja"),"</th><th>",t("Tilausnumero"),"</th></tr>";

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr><td>{$row['nimi']}</td><td><a href='?ytunnus={$row['ytunnus']}&otunnus={$row['otunnus']}&toimittajaid={$toimittajaid}&ojarj={$apu}'>{$row['otunnus']}</a></td></tr>";
			}

			echo "</table>";
		}
	}

	if ($ytunnus != '') {

		$query = "	SELECT max(lasku.tunnus) maxtunnus, GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ', ') tunnukset
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.uusiotunnus = 0 and tilausrivi.tyyppi = 'O')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.liitostunnus = '{$toimittajaid}'
					and lasku.tila = 'O'
					and lasku.alatila = 'A'
					HAVING tunnukset IS NOT NULL";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$tunnusrow = mysql_fetch_assoc($result);

			//Onko tietty tilaus valittu?
			if ($otunnus != "") {
				$tilaus_otunnukset = $otunnus;
			}
			else {
				$tilaus_otunnukset = $tunnusrow["tunnukset"];
			}

			$query = "	SELECT *
						FROM lasku
						WHERE tunnus = '{$tunnusrow['maxtunnus']}'";
			$aresult = pupe_query($query);
			$laskurow = mysql_fetch_assoc($aresult);

			$keikka = (int) $keikka;

			if ($tee == "KAIKKIPVM") {
				//P�ivitet��n rivien toimitusp�iv�t

				if ($keikka > 0) {
					$mitkakaikkipvm = " and uusiotunnus = {$keikka} and tyyppi = 'O' and kpl = 0 ";
				}
				else {
					$mitkakaikkipvm = " and otunnus in ({$tilaus_otunnukset}) and uusiotunnus=0 ";
				}

				$query = "	UPDATE tilausrivi
							SET toimaika = '{$toimvv}-{$toimkk}-{$toimpp}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							{$mitkakaikkipvm}";
				$result = pupe_query($query);
			}

			if ($tee == "PAIVITARIVI") {

				foreach ($toimaikarivi as $tunnus => $toimaika) {
					$query = "UPDATE tilausrivi SET toimaika = '{$toimaika}' WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O'";
					$result = pupe_query($query);
				}

				if ($keikka > 0) {
					$query = "UPDATE tilausrivi SET  jaksotettu = 0 WHERE yhtio = '{$kukarow['yhtio']}' and uusiotunnus = {$keikka} and tyyppi = 'O' and kpl = 0";
				}
				else {
					$query = "UPDATE tilausrivi SET  jaksotettu = 0 WHERE yhtio = '{$kukarow['yhtio']}' and otunnus in ({$t_otunnuksia}) and tyyppi = 'O' and uusiotunnus = 0";
				}

				$result = pupe_query($query);

				if (count($vahvistetturivi) > 0) {
					foreach($vahvistetturivi as $tunnus => $vahvistettu) {
						$query = "UPDATE tilausrivi SET  jaksotettu = '{$vahvistettu}' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O'";
						$result = pupe_query($query);
					}
				}

				if (isset($poista)) {
					foreach($poista as $tunnus => $kala) {
						$query = "UPDATE tilausrivi SET tyyppi = 'D' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O' and uusiotunnus = 0";
						$result = pupe_query($query);
					}
				}
			}
		}
	}

	if ($tee == "TULOSTAPDF") {
		$komento["Ostotilaus"] = "email";
		require("tulosta_vahvistamattomista_ostoriveista.inc");
	}

	if (isset($laskurow)) {
		echo "<table width='720' cellpadding='2' cellspacing='1' border='0'>";

		echo "<tr><th>",t("Ytunnus"),"</th><th colspan='2'>",t("Toimittaja"),"</th></tr>";
		echo "<tr><td>{$laskurow['ytunnus']}</td>
			<td colspan='2'>{$laskurow['nimi']} {$laskurow['nimitark']}<br> {$laskurow['osoite']}<br> {$laskurow['postino']} {$laskurow['postitp']}</td></tr>";

		echo "<tr><th>",t("Tila"),"</th><th>",t("Toimaika"),"</th><th>",t("Tilausnumerot"),"</th><td class='back'></td></tr>";
		echo "<tr><td>{$laskurow['tila']}</td><td>{$laskurow['toimaika']}</td><td>{$tunnusrow['tunnukset']}</td></tr>";
		echo "</table><br>";

		echo "	<table>
				<form method='post'>
				<input type='hidden' name='ytunnus' value = '{$ytunnus}'>
				<input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>";

		echo "<tr><th>",t("N�yt� tilaukset"),"</th><td>";
		echo "<select name='otunnus' onchange='submit();'>";
		echo "<option value=''>",t("N�yt� kaikki"),"</option>";

		$tunnukset = explode(',',$tunnusrow["tunnukset"]);

		foreach($tunnukset as $tunnus) {
			$sel = '';
			if ($otunnus == $tunnus) {
				$sel = "selected";
			}
			echo "<option value='{$tunnus}' {$sel}>{$tunnus}</option>";
		}
		echo "</select></td></tr>";

		$query = "	SELECT lasku.laskunro, lasku.tunnus
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' and kpl = 0 and uusiotunnus > 0)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.liitostunnus = '{$toimittajaid}'
					and lasku.tila = 'K'
					and lasku.alatila = ''
					GROUP BY 1
					ORDER BY 1";
		$result = pupe_query($query);

		echo "<tr><th>",t("N�yt� Saapuminen"),"</th>";
		echo "<td><select name='keikka' onchange='submit();'>";
		echo "<option value=''>",t("N�yt� kaikki"),"</option>";

		if (mysql_num_rows($result) > 0) {
			while ($keikkaselrow = mysql_fetch_assoc($result)) {
				$selkeikka = '';
				if ($keikka == $keikkaselrow['tunnus'] and $otunnus == "") {
					$selkeikka = "selected";
				}
				echo "<option value='{$keikkaselrow['tunnus']}' {$selkeikka}>{$keikkaselrow['laskunro']}</option>";
			}
		}

		echo "</select></td></tr>";

		echo "<tr><th>",t("N�yt�"),":</th>";

		$select = "";
		if ($nayta_rivit == 'vahvistamattomat') {
			$select = "selected";
		}

		echo "<td><select name='nayta_rivit' onchange='submit();'>";
		echo "<option value=''>",t("Kaikki avoimet rivit"),"</option>";
		echo "<option value='vahvistamattomat' {$select}>",t("Vain vahvistamattomia rivej�"),"</option></td>";

		echo "</form></td></tr></table><br>";

		echo "	<table>
				<form method='post'>
				<input type='hidden' name='ytunnus' value = '{$ytunnus}'>
				<input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
				<input type='hidden' name='otunnus' value = '{$otunnus}'>
				<input type='hidden' name='keikka' value = '{$keikka}'>
				<input type='hidden' name='tee' value = 'KAIKKIPVM'>";

		$toimpp = date("j");
		$toimkk = date("n");
		$toimvv = date("Y");

		echo "<tr><th>",t("P�ivit� rivien toimitusajat"),": </th><td valign='middle'>
				<input type='text' name='toimpp' value='{$toimpp}' size='3'>
				<input type='text' name='toimkk' value='{$toimkk}' size='3'>
				<input type='text' name='toimvv' value='{$toimvv}' size='6'></td>
				<td><input type='Submit' value='",t("P�ivit�"),"'></form></td></tr></table><br>";

		echo "	<table>
				<form method='post'>
				<input type='hidden' name='ytunnus' value = '{$ytunnus}'>
				<input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
				<input type='hidden' name='otunnus' value = '{$otunnus}'>
				<input type='hidden' name='keikka' value = '{$keikka}'>
				<input type='hidden' name='tee' value = 'TULOSTAPDF'>";

		echo "<tr><th>",t("Tulosta vahvistamattomat rivit"),": </th>
				<td><input type='Submit' value='",t("Tulosta"),"'></form></td></tr></table><br>";


		//Haetaan kaikki tilausrivit
		echo "<b>",t("Tilausrivit"),":</b><hr>";

		//Listataan tilauksessa olevat tuotteet
		$jarjestys = "tilausrivi.otunnus";

		if (strlen($ojarj) > 0) {
			$jarjestys = $ojarj;
		}

		$query_ale_lisa = generoi_alekentta('O');

		$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'O');

		if ((int) $keikka > 0 and $otunnus == "") {
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tuotteen_toimittajat.toim_tuoteno, tilausrivi.nimitys,
						concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
						hinta, {$ale_query_select_lisa} round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'{$yhtiorow['hintapyoristys']}') rivihinta,
						toimaika, tilausrivi.jaksotettu as vahvistettu, tilausrivi.uusiotunnus, tilausrivi.tunnus
						FROM tilausrivi
						LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='{$toimittajaid}'
						WHERE tilausrivi.uusiotunnus = {$keikka}
						and tilausrivi.yhtio = '{$kukarow['yhtio']}'
						and tilausrivi.kpl = 0
						and tilausrivi.tyyppi = 'O'
						ORDER BY {$jarjestys}";
		}
		else {
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tuotteen_toimittajat.toim_tuoteno, tilausrivi.nimitys,
						concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
						hinta, {$ale_query_select_lisa} round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'{$yhtiorow['hintapyoristys']}') rivihinta,
						toimaika, tilausrivi.jaksotettu as vahvistettu, tilausrivi.uusiotunnus, tilausrivi.tunnus
						FROM tilausrivi
						LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='{$toimittajaid}'
						WHERE otunnus in ({$tilaus_otunnukset})
						and tilausrivi.yhtio='{$kukarow['yhtio']}'
						and tilausrivi.uusiotunnus=0
						and tilausrivi.tyyppi='O'
						ORDER BY {$jarjestys}";
		}
		$presult = pupe_query($query);

		$rivienmaara = mysql_num_rows($presult);

		echo "<table border='0' cellspacing='1' cellpadding='2'><tr>";

		$miinus = 2;

		for ($i = 0; $i < mysql_num_fields($presult)-$miinus; $i++) {
			$apu = $i + 1;
			echo "<th align='left'><a href = '?ytunnus={$ytunnus}&otunnus={$otunnus}&toimittajaid={$toimittajaid}&ojarj={$apu}'>",t(mysql_field_name($presult,$i)),"</a></th>";
		}

		echo "<th align='left'>",t("poista"),"</th>";

		echo "</tr>";

		$yhteensa = 0;

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function toggleAll(toggleBox) {

					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;
					var nimi = toggleBox.name.substring(0,3);

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}

				//-->
				</script>";

		echo "	<form method='post'>
				<input type='hidden' name='ytunnus' value = '{$ytunnus}'>
				<input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
				<input type='hidden' name='otunnus' value = '{$otunnus}'>
				<input type='hidden' name='t_otunnuksia' value = '",trim($tilaus_otunnukset),"'>
				<input type='hidden' name='keikka' value = '{$keikka}'>
				<input type='hidden' name='tee' value = 'PAIVITARIVI'>";

		while ($prow = mysql_fetch_array($presult)) {

			if ($nayta_rivit == 'vahvistamattomat' and $prow["vahvistettu"] == 1) {
				continue;
			}

			$yhteensa += $prow["rivihinta"];

			echo "<tr class='aktiivi'>";

			for ($i=0; $i < mysql_num_fields($presult)-$miinus; $i++) {
				if (mysql_field_name($presult,$i) == 'tuoteno') {
					echo "<td><a href='../tuote.php?tee=Z&tuoteno=",urlencode($prow[$i]),"'>{$prow[$i]}</a></td>";
				}
				elseif(mysql_field_name($presult,$i) == 'toimaika') {
					echo "<td align='right'><input type='text' name='toimaikarivi[{$prow['tunnus']}]' value='{$prow[$i]}' size='10'></td>";
				}
				elseif (mysql_field_name($presult,$i) == 'vahvistettu') {
					$chekkis = "";
					if ($prow['vahvistettu'] == 1) {
						$chekkis = 'CHECKED';
					}
					echo "<td><input type='checkbox' name='vahvistetturivi[{$prow['tunnus']}]' value='1' {$chekkis}></td>";
				}
				else {
					echo "<td align='right'>{$prow[$i]}</td>";
				}
			}
			echo "<td>";
			if ($prow["uusiotunnus"] == 0) {
				echo "<input type='checkbox' name='poista[{$prow['tunnus']}]' value='Poista'>";
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "<tr>
				<td class='back' colspan='4' align='right'></td>
				<td colspan='3' class='spec'>",t("Tilauksen arvo"),":</td>
				<td align='right' class='spec'>",sprintf("%.2f",$yhteensa),"</td>
				<td align='right'>",t("Ruksaa kaikki"),":</td>
				<td><input type='checkbox' name='vahvist' onclick='toggleAll(this);'></td>
				<td><input type='checkbox' name='poist' onclick='toggleAll(this);'></td>
			</tr>";
		echo "<tr>
				<td class='back' colspan='8'></td>
				<td class='back' colspan='2' align='right'><input type='submit' value='",t("P�ivit� tiedot"),"'></td>
			</tr>";

		echo "</form></table>";
	}

	require ("inc/footer.inc");
