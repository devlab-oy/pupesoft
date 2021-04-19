<?php
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Tulosta keräyserätarroja")."</font><hr>";

	if ($tee == 'uudet') {
		$komento = $kirjoitin;
		require('inc/tulosta_keraysaineistotarrat_tec.inc');
	}
	elseif ($tee == 'vanhat') {
		$komento = $kirjoitinvan;
		require('inc/tulosta_keraysaineistotarrat_tec.inc');
	}

	if ($tee == '') {
		$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.toim_nimi, count(tilausrivi.tunnus) riveja, sum(tilausrivi.tilkpl) myyntieria
					FROM lasku
					JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'Z'
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'Z'
					and lasku.alatila = 'A'
					GROUP BY 1,2,3
					ORDER BY 1,2,3";
		$tarrares = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Tulostamattomat")."</font><br><br>";

		if (mysql_num_rows($tarrares) > 0) {
			echo "<form action = '$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='tee' value='uudet'>";
			echo "<table>";
			echo "<tr><th>".t("Ytunnus")."</th><th>".t("Nimi")."</th><th>".t("Toim.Nimi")."</th><th>".t("Rivejä")."</th><th>".t("Myyntieriä")."</th></tr>";

			$yhtriveja	= 0;
			$yhteria	= 0;

			while ($tarrarow = mysql_fetch_array($tarrares)) {
				echo "<tr><td>$tarrarow[ytunnus]</td><td>$tarrarow[nimi]</td><td>$tarrarow[toim_nimi]</td><td>$tarrarow[riveja]</td><td>$tarrarow[myyntieria]</td></tr>";

				$yhtriveja += $tarrarow['riveja'];
				$yhteria += $tarrarow['myyntieria'];

			}
			echo "<tr><td colspan='3'>".t("Yhteensä").":</td><td>$yhtriveja</td><td>$yhteria</td></tr>";
			echo "</table><br>";

			$query = "	SELECT *
						from kirjoittimet
						where yhtio = '$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);

			echo "<select name='kirjoitin'>";
			echo "<option value='$kirow[komento]'>".t("Valitse kirjoitin")."</option>";

			while ($kirow = mysql_fetch_array($kires)) {
				if ($kirow['komento']==$kirjoitin) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[komento]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select><input type='Submit' value='".t("Tulosta nämä")."'>";
			echo "</form><br>";
		}
		else {
			echo t("Ei tulostamattomia keräyserätarroja");
		}

		echo "<br><br><font class='head'>".t("Tulostetut keräyserätarrat")."</font><hr>";

		$query = "	SELECT DATE_FORMAT(lasku.luontiaika, '%Y-%m-%d %H:%i') luontiaika, lasku.toimaika, lasku.ytunnus, lasku.nimi, lasku.toim_nimi, group_concat(lasku.tunnus) laskutunnukset, count(tilausrivi.tunnus) riveja, sum(tilausrivi.tilkpl) myyntieria
					FROM lasku
					JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'Z'
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'Z'
					and lasku.alatila = 'X'
					and lasku.luontiaika >= date_sub(now(), INTERVAL 30 DAY)
					GROUP BY 1,2,3,4,5
					ORDER BY 1 DESC,2 DESC,3,4,5";
		$tarrares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tarrares) > 0) {

			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='vanhat'>";

			echo "<table>";
			echo "<tr><th>".t("Vastaanotettu")."</th><th>".t("Toimitusaika")."</th><th>".t("Ytunnus")."</th><th>".t("Nimi")."</th><th>".t("Toim.Nimi")."</th><th>".t("Rivejä")."</th><th>".t("Myyntieriä")."</th><th>".t("Tulosta")."</th></tr>";

			$yhtriveja	= 0;
			$yhteria	= 0;

			$query = "	SELECT *
						from kirjoittimet
						where yhtio = '$kukarow[yhtio]'";
			$kires2 = mysql_query($query) or pupe_error($query);

			while ($tarrarow = mysql_fetch_array($tarrares)) {
				echo "<tr><td>".tv1dateconv($tarrarow["luontiaika"], "P")."</td><td>".tv1dateconv($tarrarow["toimaika"])."</td><td>$tarrarow[ytunnus]</td><td>$tarrarow[nimi]</td><td>$tarrarow[toim_nimi]</td><td align='right'>$tarrarow[riveja]</td><td align='right'>$tarrarow[myyntieria]</td>";
				echo "<td align='center'><input type='checkbox' name='vanhatunnukset[]' value='$tarrarow[laskutunnukset]'></td></tr>";
			}

			echo "</table><br>";

			echo "<select name='kirjoitinvan'>";
			echo "<option value='$kirow2[komento]'>".t("Valitse kirjoitin")."</option>";

			while ($kirow2 = mysql_fetch_array($kires2)) {
				if ($kirow2['komento'] == $kirjoitinvan) $select = 'SELECTED';
				else $select = '';

				echo "<option value='$kirow2[komento]' $select>$kirow2[kirjoitin]</option>";
			}

			echo "</select>";
			echo "<input type='submit' value='".t("Tulosta valitut")."'>";
			echo "</form>";
		}
		else {
			echo t("Ei tulostettuja keräyserätarroja");
		}
	}

	require("inc/footer.inc");
?>