<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>",t("L‰hetysten seuranta"),"</font><hr>";

	if ((isset($ppalku) and trim($ppalku) == '') or (isset($kkalku) and trim($kkalku) == '') or (isset($vvalku) and trim($vvalku) == '')) {
		echo "<font class='error'>",t("VIRHE: Alkup‰iv‰m‰‰r‰ on virheellinen"),"!</font><br /><br />";
		$tee = "";
	}

	if ((isset($pploppu) and trim($pploppu) == '') or (isset($kkloppu) and trim($kkloppu) == '') or (isset($vvloppu) and trim($vvloppu) == '')) {
		echo "<font class='error'>",t("VIRHE: Loppup‰iv‰m‰‰r‰ on virheellinen"),"!</font><br /><br />";
		$tee = "";
	}

	if ($tee != "" and isset($asiakas) and trim($asiakas) == "" and isset($paikkakunta) and trim($paikkakunta) == "" and isset($tilausnumero) and trim($tilausnumero == "")) {
		echo "<font class='error'>",t("VIRHE: Asiakas / Paikkakunta / tilausnumero puuttuu"),"!</font><br /><br />";
		$tee = "";
	}

	if (!isset($asiakas)) $asiakas = "";
	if (!isset($paikkakunta)) $paikkakunta = "";
	if (!isset($tilausnumero)) $tilausnumero = "";
	if (!isset($ppalku)) $ppalku = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($kkalku)) $kkalku = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vvalku)) $vvalku = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($pploppu)) $pploppu = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($kkloppu)) $kkloppu = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vvloppu)) $vvloppu = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($tee)) $tee = "";

	echo "<form method='post' action=''>";
	echo "<table>";
	echo "<tr><th>",t("Etsi asiakas"),"</th><td><input type='text' name='asiakas' value='{$asiakas}' />&nbsp;</td>";
	echo "<th>",t("Paikkakunta"),"</th><td><input type='text' name='paikkakunta' value='{$paikkakunta}' /></td></tr>";
	echo "<tr><th>",t("Etsi tilausnumero"),"</th><td colspan='3'><input type='text' name='tilausnumero' value='{$tilausnumero}' /></td></tr>";
	echo "<tr><th>",t("P‰iv‰m‰‰r‰"),"</th><td colspan='3' style='vertical-align:middle;'>";
	echo "<input type='text' name='ppalku' value='{$ppalku}' size='3' />&nbsp;";
	echo "<input type='text' name='kkalku' value='{$kkalku}' size='3' />&nbsp;";
	echo "<input type='text' name='vvalku' value='{$vvalku}' size='5' />&nbsp;-&nbsp;";
	echo "<input type='text' name='pploppu' value='{$pploppu}' size='3' />&nbsp;";
	echo "<input type='text' name='kkloppu' value='{$kkloppu}' size='3' />&nbsp;";
	echo "<input type='text' name='vvloppu' value='{$vvloppu}' size='5' />&nbsp;-&nbsp;";
	echo "<input type='hidden' name='tee' value='hae' />";
	echo "<input type='submit' value='",t("Hae"),"' />";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";

	if ($tee == 'hae') {

		$ppalku = (int) $ppalku;
		$kkalku = (int) $kkalku;
		$vvalku = (int) $vvalku;

		$pploppu = (int) $pploppu;
		$kkloppu = (int) $kkloppu;
		$vvloppu = (int) $vvloppu;

		$nimilisa = trim($asiakas) != "" ? " AND (lasku.nimi LIKE ('%".mysql_real_escape_string($asiakas)."%') OR lasku.toim_nimi LIKE ('%".mysql_real_escape_string($asiakas)."%'))" : "";
		$postitplisa = trim($paikkakunta) != "" ? " AND (lasku.postitp LIKE ('%".mysql_real_escape_string($paikkakunta)."%') OR lasku.toim_postitp LIKE ('%".mysql_real_escape_string($paikkakunta)."%'))" : "";

		$query = "	SELECT TRIM(CONCAT(lasku.nimi, ' ', lasku.nimitark)) AS nimi, toimitustapa.selite AS toimitustapa, lahdot.pvm, group_concat(DISTINCT kerayserat.sscc) AS sscc
					FROM kerayserat
					JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus {$nimilisa} {$postitplisa})
					JOIN lahdot ON (lahdot.yhtio = kerayserat.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi = 'S' AND lahdot.pvm >= '{$vvalku}-{$kkalku}-{$ppalku}' AND lahdot.pvm <= '{$vvloppu}-{$kkloppu}-{$pploppu}')
					JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
					WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
					AND kerayserat.tila = 'R'
					GROUP BY 1,2,3
					ORDER BY 1,2,3 ";
		// echo "<pre>",str_replace("\t", "", $query),"</pre>";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			echo "<br /><br />";
			echo "<table>";

			while ($row = mysql_fetch_assoc($res)) {

				if ($row['sscc'] == "") continue;

				$query = "	SELECT IF(kerayserat.sscc_ulkoinen != 0, kerayserat.sscc_ulkoinen, kerayserat.sscc) AS sscc, pakkaus.pakkauskuvaus, lasku.ohjausmerkki, CONCAT(TRIM(CONCAT(lasku.toim_nimi, ' ', lasku.toim_nimitark)), ' ', lasku.toim_osoite, ' ', lasku.toim_postino, ' ', lasku.toim_postitp) AS osoite, ROUND((SUM(tuote.tuotemassa * kerayserat.kpl_keratty) + pakkaus.oma_paino), 1) AS kg
							FROM kerayserat
							JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND (kerayserat.sscc IN ({$row['sscc']}) OR kerayserat.sscc_ulkoinen IN ({$row['sscc']}))
							GROUP BY 1,2,3,4
							ORDER BY 1";
				// echo "<pre>",str_replace("\t", "", $query),"</pre>";
				$era_res = pupe_query($query);

				if (mysql_num_rows($era_res) > 0) {

					echo "<tr>";
					echo "<td class='back' colspan='5'><font class='message'>",tv1dateconv($row['pvm']),"</font></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<th>{$row['nimi']}</th>";
					echo "<th>{$row['toimitustapa']}</th>";
					echo "<th>",t("Kg"),"</th>";
					echo "<th>",t("Ohjausmerkki"),"</th>";
					echo "<th>",t("Toim.osoite"),"</th>";
					echo "</tr>";

					while ($era_row = mysql_fetch_assoc($era_res)) {
						echo "<tr>";
						echo "<td>{$era_row['sscc']}</td>";
						echo "<td>{$era_row['pakkauskuvaus']}</td>";
						echo "<td>{$era_row['kg']}</td>";
						echo "<td>{$era_row['ohjausmerkki']}</td>";
						echo "<td>{$era_row['osoite']}</td>";
						echo "</tr>";
					}

					echo "<tr><td class='back' colspan='5'>&nbsp;</td></tr>";
				}
			}

			echo "</table>";
		}
	}

	require ("inc/footer.inc");