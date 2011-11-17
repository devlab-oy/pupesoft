<?php

	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Etiketin tulostus")."</font><hr>";

	// Vakio lomake
	$formi  = 'formi';
	$kentta = 'tuoteno';

	echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='hae'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tuotenumero")."</th>";
	echo "<th>".t("KPL")."</th>";
	echo "<th>".t("Kirjoitin")."</th>";

	echo "<tr>";
	echo "<td><input type='text' name='tuoteno' size='20' maxlength='60' value='$tuoteno'></td>";
	echo "<td><input type='text' name='tulostakappale' size='3' value='$tulostakappale'></td>";
	echo "<td><select name='kirjoitin'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	$query = "	SELECT *
				FROM kirjoittimet
				WHERE yhtio = '$kukarow[yhtio]'
				and komento != 'email'
				order by kirjoitin";
	$kires = mysql_query($query) or pupe_error($query);

	while ($kirow = mysql_fetch_array($kires)) {
		if ($kirow['tunnus'] == $kirjoitin) $select = 'SELECTED';
		else $select = '';
		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}

	echo "</select></td>";

	echo "<td class='back'><input name='submit' type='submit' value='".t("Tulosta")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br>";

	// Virhetarkastukset
	if ($tee == "hae") {

		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '{$kukarow["yhtio"]}'
					and tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$trow = mysql_fetch_assoc($result);
			$tee = "jatka";
		}
		else {
			echo "<font class='error'>".t("Virhe: Tuotetta ei löydy")."</font><br>";
			$tee = "";
		}

		$tulostakappale = (int) $tulostakappale;

		if ($tulostakappale <= 0) {
			echo "<font class='error'>".t("Virhe: Syötä kappalemäärä")."</font><br>";
			$tee = "";
		}

		if ($kirjoitin == "") {
			echo "<font class='error'>".t("Virhe: Valitse kirjoitin")."</font><br>";
			$tee = "";
		}
	}

	if ($tee == "jatka") {

		$filenimi = "/tmp/sahantera_tulostus.txt";
		$hammastus = t_tuotteen_avainsanat($trow, 'HAMMASTUS');

		$out = chr(10).chr(10).chr(10).chr(10); 	// 5 riviltä aloitetaan tulostus
		$out .= sprintf ('%6s', ' ');				// 4 merkkiä alusta niin saadaan oikeaan kohtaan aloittamaan tulostus.
		$out .= sprintf ('%-9s', $trow["try"]);
		$out .= sprintf ('%1s', ' ');
		$out .= sprintf ('%-24s', round($trow["tuotekorkeus"],0)." x ".round($trow["tuoteleveys"],0)." x ".round($trow["tuotesyvyys"],2)); // pituus, leveys, paksuus = korkeus, leveys, syvyys
		$out .= sprintf ('%2s', ' ');
		$out .= sprintf ('%-8s', $hammastus);		// hammastus, avainsanoista. 2 vaihtoehtoa: (3/4) tuumakoko, (16) mm-koko, hampaita tuumalle
		$out .= chr(10).chr(10);
		$out .= chr(13);
		$out .= sprintf ('%16s', ' ');				// 16 merkkiä alusta niin saadaan "nimitys / tuoteno" oikeaan paikkaan.
		$out .= sprintf ('%-24s', $trow["tuoteno"]);
		$out .= chr(10).chr(13);
		$out .= chr(12);

		$boob = file_put_contents($filenimi, $out);

		if ($boob === FALSE) {
			echo "<font class='error'>".t("Virhe: Tiedoston kirjoittaminen ei onnistunut")."</font><br>";
			$tee = "";
		}
		else {
			$tee = "tulosta";
		}
	}

	if ($tee == "tulosta") {

		$query = "	SELECT komento, kirjoitin
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tunnus = '{$kirjoitin}'";
		$result = pupe_query($query);
		$krivi = mysql_fetch_assoc($result);

		for ($i = 0; $i < $tulostakappale; $i++) {
			// Tulostetaan kirjoittimelle
			$line = exec("{$krivi["komento"]} $filenimi");
		}

		echo t("Tulostetaan")." {$tulostakappale} ".t("tarraa")."...<br>";

		// dellataan tmp file
		unlink($filenimi);
	}

	require("inc/footer.inc");
