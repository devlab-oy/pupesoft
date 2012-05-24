<?php

	require ("inc/parametrit.inc");

	if ($tee == "NAYTA") {
		echo "<font class='head'>Siivoa tilaukset-listaa:</font><hr>";

		require("raportit/naytatilaus.inc");
		echo "<br><br>";
		echo "<a href='javascript:history.back()'>Takaisin</a><br><br>";
		exit;
	}

	if ($tee == 'CLEAN') {
		if (count($valittutil) != 0) {
			foreach ($valittutil as $rastit) {
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa tilaus_siivo.php (1)")."<br>";

				$query = "	UPDATE lasku
							SET tila = 'D',
							alatila  = 'N',
							comments = '$komm'
							WHERE yhtio = '$kukarow[yhtio]'
							and tila    = 'N'
							and alatila = ''
							and tunnus  = '$rastit'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 1) {
					$query = "	UPDATE tilausrivi
								SET tyyppi  = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and var    != 'P'
								and otunnus = '$rastit'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if (count($valitturiv) != 0) {
			foreach ($valitturiv as $rastit) {
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa tilaus_siivo.php (2)")."<br>";

				$query = "	UPDATE lasku
							SET tila = 'D',
							alatila  = 'N',
							comments = '$komm'
							WHERE yhtio = '$kukarow[yhtio]'
							and tila in ('L','N')
							and alatila     != 'X'
							and tunnus = '$rastit'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 1) {
					$query = "	UPDATE tilausrivi
								SET tyyppi  = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and var    != 'P'
								and otunnus = '$rastit'";
					$result = mysql_query($query) or pupe_error($query);

				}
			}
		}

		if (count($jttilriv) != 0) {
			foreach ($jttilriv as $rastit) {
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa tilaus_siivo.php (3)")."<br>";

				$query = "	UPDATE lasku
							SET tila = 'D',
							alatila  = 'N',
							comments = '$komm'
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'N'
							and alatila	= 'T'
							and tunnus = '$rastit'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 1) {
					$query = "	UPDATE tilausrivi
								SET tyyppi  = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and var    != 'P'
								and otunnus = '$rastit'";
					$result = mysql_query($query) or pupe_error($query);

				}
			}
		}
	}

	echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].id.substring(0,5) == nimi) {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";


	echo "<font class='head'>Siivoa tilaukset-listaa:</font><hr>";

	//keskenolevat tilaukset
	$query = "	SELECT lasku.*,
				tilausrivi.otunnus otunnus, concat(if(kuka.kassamyyja!='', 'Kassa',''), ' ', if(extranet!='', 'Extranet','')) kassamyyja,
				if(lasku.luontiaika <= date_sub(now(),interval 30 day), 0, 1) kkorder,
				concat(if(lasku.luontiaika <= date_sub(now(),interval 30 day), 0, 1), if(lasku.vienti='', ' ', lasku.vienti), lasku.valkoodi) grouppi,
				concat(if(lasku.luontiaika <= date_sub(now(),interval 30 day), '".t("Yli 30 p�iv�� vanhat")."', '".t("Alle 30 p�iv�� vanhat")."'), ', ', if(lasku.vienti='', '".t("Kotimaan myynti")."', if(lasku.vienti='K','".t("Ei-EU vienti")."','".t("EU vienti")."')), ', ', lasku.valkoodi) grouppi_nimi
				FROM lasku
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.laatija=kuka.kuka
				LEFT JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila = 'N'
				and lasku.alatila = ''
				and lasku.luontiaika < date_sub(now(),interval 1 day)
				and otunnus is not null
				GROUP BY lasku.tunnus
				ORDER BY kkorder, lasku.vienti, lasku.valkoodi, lasku.luontiaika";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<form method='POST'>";
	echo "<input type='hidden' name='tee' value='CLEAN'>";
	echo "<tr><td colspan='8' class='back'>Keskenolevat tilaukset joilla on rivej� (".mysql_num_rows($res)."kpl):</td></tr>";

	$edgrouppi = "";
	$lask	   = 1;

	while ($laskurow = mysql_fetch_array($res)) {

		if ($laskurow["grouppi"] != $edgrouppi) {

			if ($edgrouppi != "") echo "<tr><td colspan='7' class='back'></td><td>Ruksaa yll�olevat:</td><td><input type='checkbox' name='$edgrouppi' onclick='toggleAll(this)'></td></tr>";

			echo "<tr><td colspan='9' class='back'>$laskurow[grouppi_nimi]</td></tr>";
			echo "<tr><th>Tunnus:</th><th>Tila:</th><th>Alatila:</th><th>Nimi:</th><th>Vienti:</th><th>Valuutta:</th><th>Kassa/Extranet:</th><th>Luontiaika:</th><th>Mit�t�i:</th></tr>";
		}

		$ero="td";
		if ($tunnus==$laskurow["tunnus"]) $ero="th";

		echo "<tr><$ero><a href='$PHP_SELF?tee=NAYTA&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></$ero>";
		echo "<$ero>$laskurow[tila]</$ero>";
		echo "<$ero>$laskurow[alatila]</$ero>";
		echo "<$ero>$laskurow[nimi]</$ero>";
		echo "<$ero>$laskurow[vienti]</$ero>";
		echo "<$ero>$laskurow[valkoodi]</$ero>";
		echo "<$ero>$laskurow[kassamyyja]</$ero>";
		echo "<$ero>".tv1dateconv($laskurow["luontiaika"], "P")."</$ero>";
		echo "<$ero><input type='checkbox' value='$laskurow[tunnus]' name='valittutil[]' id='$laskurow[grouppi]$lask'></$ero></tr>";

		$edgrouppi = $laskurow["grouppi"];
		$lask++;
	}
	echo "<tr><td colspan='7' class='back'></td><td>Ruksaa yll�olevat:</td><td><input type='checkbox' name='$edgrouppi' onclick='toggleAll(this)'></td></tr>";


	echo "<tr><td colspan='8' class='back'><br><br></td></tr>";

	//rivitt�m�t otsikot
	$query = "	SELECT lasku.*, concat(if(kuka.kassamyyja!='', 'Kassa',''), ' ', if(extranet!='', 'Extranet','')) kassamyyja
				FROM lasku use index (yhtio_tila_luontiaika)
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.laatija = kuka.kuka
				LEFT JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila in ('L','N')
				and alatila != 'X'
				and tilausrivi.tunnus is null
				ORDER BY lasku.luontiaika";
	$res = mysql_query($query) or pupe_error($query);

	echo "<tr><td colspan='8' class='back'>Rivitt�m�t otsikot (".mysql_num_rows($res)."kpl):</tr>";
	echo "<tr><th>Tunnus:</th><th>Tila:</th><th>Alatila:</th><th>Nimi:</th><th>Vienti:</th><th>Valuutta:</th><th>Kassa/Extranet:</th><th>Luontiaika:</th><th>Mit�t�i:</th></tr>";

	$lask = 1;

	while ($laskurow = mysql_fetch_array($res)) {

		$ero="td";
		if ($tunnus==$laskurow["tunnus"]) $ero="th";

		echo "<tr><$ero><a href='$PHP_SELF?tee=NAYTA&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></$ero>";
		echo "<$ero>$laskurow[tila]</$ero>";
		echo "<$ero>$laskurow[alatila]</$ero>";
		echo "<$ero>$laskurow[nimi]</$ero>";
		echo "<$ero>$laskurow[vienti]</$ero>";
		echo "<$ero>$laskurow[valkoodi]</$ero>";
		echo "<$ero>$laskurow[kassamyyja]</$ero>";
		echo "<$ero>$laskurow[luontiaika]</$ero>";
		echo "<$ero><input type='checkbox' value='$laskurow[tunnus]' name='valitturiv[]' id='EIRIV$lask'></$ero></tr>";

	}
	echo "<tr><td colspan='7' class='back'></td><td>Ruksaa yll�olevat:</td><td><input type='checkbox' name='EIRIV' onclick='toggleAll(this)'></td></tr>";


	echo "<tr><td colspan='8' class='back'><br><br></td></tr>";


	if ($yhtiorow["varaako_jt_saldoa"] == "") {
		$kpl 	= "jt";
	}
	else {
		$kpl 	= "jt+varattu";
	}

	//Odottaa JT-tuotteita
	$query = "	SELECT lasku.tunnus, lasku.tila, lasku.alatila, lasku.nimi, lasku.vienti, lasku.valkoodi, concat(if(kuka.kassamyyja!='', 'Kassa',''), ' ', if(extranet!='', 'Extranet','')) kassamyyja, lasku.luontiaika,
				count(tilausrivi.tunnus) tilausrivi1,
				sum(if(tilausrivi.var != 'P', 1, 0)) tilausrivi2,
				sum(if($kpl != 0 and tilausrivi.var in ('J','S'), 1, 0)) tilausrivi3
				FROM lasku use index (yhtio_tila_luontiaika)
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.laatija = kuka.kuka
				LEFT JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and tilausrivi.tyyppi != 'D'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila = 'N'
				and lasku.alatila = 'T'
				GROUP BY 1,2,3,4,5,6,7,8
				HAVING tilausrivi2 != tilausrivi3 or (tilausrivi1 > 0 and tilausrivi2 = 0)
				ORDER BY lasku.luontiaika";
	$res = mysql_query($query) or pupe_error($query);

	echo "<tr><td colspan='8' class='back'>Odottaa JT-tuotteita, mutta tilauksella ei JT rivej� (".mysql_num_rows($res)."kpl):</tr>";
	echo "<tr><th>Tunnus:</th><th>Tila:</th><th>Alatila:</th><th>Nimi:</th><th>Vienti:</th><th>Valuutta:</th>
			<th>Kassa/Extranet:</th><th>Luontiaika:</th>
			<th>Rivej�:</th><th>JT-rivej�:</th>
			<th>Mit�t�i:</th></tr>";

	$lask = 1;

	while ($laskurow = mysql_fetch_array($res)) {

		$ero="td";
		if ($tunnus==$laskurow["tunnus"]) $ero="th";

		echo "<tr><$ero><a href='$PHP_SELF?tee=NAYTA&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></$ero>";
		echo "<$ero>$laskurow[tila]</$ero>";
		echo "<$ero>$laskurow[alatila]</$ero>";
		echo "<$ero>$laskurow[nimi]</$ero>";
		echo "<$ero>$laskurow[vienti]</$ero>";
		echo "<$ero>$laskurow[valkoodi]</$ero>";
		echo "<$ero>$laskurow[kassamyyja]</$ero>";
		echo "<$ero>$laskurow[luontiaika]</$ero>";

		echo "<$ero>$laskurow[tilausrivi1]</$ero>";
		echo "<$ero>$laskurow[tilausrivi3]</$ero>";

		echo "<$ero><input type='checkbox' value='$laskurow[tunnus]' name='jttilriv[]' id='JTRIV$lask'></$ero></tr>";

	}
	echo "<tr><td colspan='7' class='back'></td><td colspan='3'>Ruksaa yll�olevat:</td><td><input type='checkbox' name='JTRIV' onclick='toggleAll(this)'></td></tr>";

	echo "</table><br><br>";
	echo "<input type='submit' value='".t("Mit�t�i valitut tilaukset")."'></form>";

	require("inc/footer.inc");
?>