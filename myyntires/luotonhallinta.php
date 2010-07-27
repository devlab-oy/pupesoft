<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luotonhallinta")."</font><hr>";

	echo "<script>";
	echo "function toggleAll(toggleBox) {";
	echo "	var currForm = toggleBox.form;";
	echo "	var isChecked = toggleBox.checked;";
	echo "	for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {";
	echo "		if (currForm.elements[elementIdx].type == 'checkbox') {";
	echo "			currForm.elements[elementIdx].checked = isChecked;";
	echo "		}";
	echo "	}";
	echo "}";
	echo "</script>";

	if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
		// k‰sitell‰‰n luottorajoja per ytunnus
		$kasittely_periaate = "asiakas.ytunnus";
	}
	else {
		// k‰sitell‰‰n luottorajoja per asiakas
		$kasittely_periaate = "asiakas.tunnus";
	}

	if (isset($muutparametrit)) {
		$muut = explode('#', $muutparametrit);
		$pvm_alku = $muut[0];
		$pvm_loppu = $muut[1];
		$minimi_myynti = $muut[2];
		$luottorajauksia = $muut[3];
	}

	$asiakasrajaus = "";
	$muutparametrit = "$pvm_alku#$pvm_loppu#$minimi_myynti#$luottorajauksia";

	if ($ytunnus != '') {

		require ("inc/asiakashaku.inc");

		if ($ytunnus == '') {
			echo "<br><br>";
			$tee = "";
		}
		else {
			if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
				$asiakasrajaus = " and asiakas.ytunnus = '$ytunnus' ";
			}
			else {
				$asiakasrajaus = " and asiakas.tunnus = '$asiakasid' ";
			}
			$luottorajauksia = "Z";
		}
	}

	$update_message = array();
	$raja_select = array();

	if (!isset($pvm_alku)) {
		$pvm_alku = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
	}
	if (!isset($pvm_loppu)) {
		$pvm_loppu = date("Y-m-d");
	}
	if (!isset($minimi_myynti)) {
		$minimi_myynti = 0;
	}
	if (isset($luottorajauksia)) {
		$raja_select[$luottorajauksia] = "selected";
	}
	if (!isset($muutosprosentti)) {
		$muutosprosentti = 10;
	}
	if ($luottorajauksia == "A" and (float) $muutosprosentti == 0) {
		echo "<font class='error'>Virheellinen muutosprosentti</font>";
		$tee = "";
	}

	echo "<form name='haku' action='luotonhallinta.php' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='1'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Myynti")." ".t("Alkupvm")."</th>";
	echo "<td><input type='date' required name='pvm_alku' value='$pvm_alku'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Myynti")." ".t("Loppupvm")."</th>";
	echo "<td><input type='date' required name='pvm_loppu' value='$pvm_loppu'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Minimi myynti")." $yhtiorow[valkoodi]</th>";
	echo "<td><input type='number' name='minimi_myynti' value='$minimi_myynti'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Luottoraja rajauksia")."</th>";
	echo "<td><select name='luottorajauksia'>";
	echo "<option value='A' $raja_select[A]>".t("N‰yt‰ asiakkaat, joiden luottoraja eroaa myynnist‰ yli muutosprosentin")."</option>";
	echo "<option value='B' $raja_select[B]>".t("N‰yt‰ asiakkaat, jotka ovat ylitt‰neet luottorajan")."</option>";
	echo "<option value='C' $raja_select[C]>".t("N‰yt‰ asiakkaat, joilla ei ole luottorajaa, mutta myynti‰")."</option>";
	echo "<option value='D' $raja_select[D]>".t("N‰yt‰ asiakkaat, joilla on luottoraja, mutta ei myynti‰")."</option>";
	echo "<option value='E' $raja_select[E]>".t("N‰yt‰ asiakkaat, jotka ovat myyntikiellossa")."</option>";
	echo "<option value='F' $raja_select[F]>".t("N‰yt‰ asiakkaat, jotka ovat ylitt‰neet luottorajan, mutta eiv‰t ole myyntikiellossa")."</option>";
	echo "<option value='Z' $raja_select[Z]>".t("N‰yt‰ kaikki asiakkaat")."</option>";
	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Muutosprosentti")."</th>";
	echo "<td><input type='number' name='muutosprosentti' value='$muutosprosentti'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Vain asiakas")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'><input type='hidden' name='asiakasid' value='$asiakasid'>$asiakasrow[nimi]</td>";
	echo "</tr>";

    echo "</table>";

	echo "<br>";

	echo "<input type='submit' value='".t("N‰yt‰")."'>";
	echo "</form>";

	echo "<br>";
	echo "<br>";

	// p‰ivitet‰‰n asiakkaat
	if ($tee == "2") {

		foreach ($luottoraja as $ytunnus => $summa) {

			$summa = trim($summa);

			// Katsotaan onko tiedot muuttuneet
			if ($summa == $alkuperainen_luottoraja[$ytunnus] and $myyntikielto[$ytunnus] == $alkuperainen_myyntikielto[$ytunnus]) {
				continue;
			}

			// Katsotaan, ett‰ tiedot ovat oikein
			if (!is_numeric($summa)) {
				continue;
			}

			// Varmistetaan checkboxinkin value
			if ($myyntikielto[$ytunnus] != "K" and $myyntikielto[$ytunnus] != "") {
				continue;
			}

			$summa = (float) $summa;
			$ytunnus = mysql_real_escape_string($ytunnus);

			$query = "	UPDATE asiakas SET
						myyntikielto = '$myyntikielto[$ytunnus]',
						luottoraja = '$summa'
						WHERE asiakas.yhtio = '$kukarow[yhtio]'
						AND $kasittely_periaate = '$ytunnus'";
			$asiakasres = mysql_query($query) or pupe_error($query);

			if (mysql_affected_rows() != 0) {
				$update_message[$ytunnus] = t("Asiakastiedot p‰ivitetty!");
			}
		}

		$tee = "1";

	}

	// n‰ytet‰‰n asiakkaat
	if ($tee == "1") {

		// haetaan kaikki yrityksen asiakkaat
		$query  = "	SELECT $kasittely_periaate ytunnus,
					group_concat(distinct tunnus) liitostunnukset,
					group_concat(distinct nimi ORDER BY nimi SEPARATOR '<br>') nimi,
					group_concat(distinct toim_nimi ORDER BY nimi SEPARATOR '<br>') toim_nimi,
					min(luottoraja) luottoraja,
					min(myyntikielto) myyntikielto,
					min(ytunnus) tunniste
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji != 'P'
					$asiakasrajaus
					GROUP BY 1";
		$asiakasres = mysql_query($query) or pupe_error($query);

		echo "<form name='paivitys' action='luotonhallinta.php' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='2'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";

		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Laskutus nimi")."</th>";
		echo "<th>".t("Toimitus nimi")."</th>";
		echo "<th>".t("Myynti")." $yhtiorow[valkoodi]</th>";
		echo "<th>".t("Luottoraja")." $yhtiorow[valkoodi]</th>";
		echo "<th>".t("Myyntikielto")."</th>";
		echo "</tr>";

		while ($asiakasrow = mysql_fetch_array($asiakasres)) {

			// haetaan asiakkaan myynnit halutulta ajalta
			$query = "	SELECT ifnull(sum(summa), 0) summa
						FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						AND lasku.tila = 'U'
						AND lasku.liitostunnus IN ($asiakasrow[liitostunnukset])
						AND lasku.tapvm >= '$pvm_alku'
						AND lasku.tapvm <= '$pvm_loppu'";
			$myyntires = mysql_query($query) or pupe_error($query);
			$myyntirow = mysql_fetch_array($myyntires);

			// Ei n‰ytet‰ jos ei olla yli minimi myynnin
			if (($luottorajauksia != "D" and $luottorajauksia != "Z" and $myyntirow["summa"] == 0) or ($myyntirow["summa"] < $minimi_myynti)) {
				continue;
			}

			// N‰yt‰ asiakkaat, joiden luottorajaa tulisi tarkistaa (myynnin/luottorajan ero yli muutosprosentin)
			if ($luottorajauksia == "A" and (abs(100 - ($myyntirow["summa"] / $asiakasrow["luottoraja"] * 100)) < $muutosprosentti or $asiakasrow["luottoraja"] == 0)) {
				continue;
			}

			// N‰yt‰ asiakkaat, jotka ovat ylitt‰neet luottorajan
			if ($luottorajauksia == "B" and ($myyntirow["summa"] < $asiakasrow["luottoraja"] or $asiakasrow["luottoraja"] == 0)) {
				continue;
			}

			// N‰yt‰ asiakkaat, joilla ei ole luottorajaa, mutta myynti‰
			if ($luottorajauksia == "C" and ($asiakasrow['luottoraja'] != 0 or $myyntirow["summa"] == 0)) {
				continue;
			}

			// N‰yt‰ asiakkaat, joilla on luottoraja, mutta ei myynti‰
			if ($luottorajauksia == "D" and ($asiakasrow['luottoraja'] == 0 or $myyntirow["summa"] != 0)) {
				continue;
			}

			// N‰yt‰ asiakkaat, jotka ovat myyntikiellossa
			if ($luottorajauksia == "E" and $asiakasrow['myyntikielto'] != "K") {
				continue;
			}

			// N‰yt‰ asiakkaat, jotka ovat ylitt‰neet luottorajan, mutta eiv‰t ole myyntikiellossa
			if ($luottorajauksia == "F" and ($myyntirow["summa"] < $asiakasrow["luottoraja"] or $asiakasrow['myyntikielto'] == "K" or $asiakasrow["luottoraja"] == 0)) {
				continue;
			}

			if ($asiakasrow["myyntikielto"] != "") {
				$chk = "CHECKED";
			}
			else {
				$chk = "";
			}

			echo "<tr class='aktiivi'>";
			echo "<td>$asiakasrow[tunniste]</td>";
			echo "<td>$asiakasrow[nimi]</td>";
			echo "<td>$asiakasrow[toim_nimi]</td>";
			echo "<td align='right'>$myyntirow[summa]</td>";
			echo "<td align='right'><input style='text-align:right' type='text' name='luottoraja[$asiakasrow[ytunnus]]' value='$asiakasrow[luottoraja]' size='11'></td>";
			echo "<td align='right'><input type='checkbox' name='myyntikielto[$asiakasrow[ytunnus]]' value='K' $chk></td>";
			echo "<td class='back'><font class='error'>".$update_message[$asiakasrow["ytunnus"]]."</font></td>";
			echo "</tr>";

			// V‰litet‰‰n alkuper‰iset arvot, ettei p‰ivitet‰ asiakasta suotta
			echo "<input type='hidden' name='alkuperainen_luottoraja[$asiakasrow[ytunnus]]' value='$asiakasrow[luottoraja]'>";
			echo "<input type='hidden' name='alkuperainen_myyntikielto[$asiakasrow[ytunnus]]' value='$asiakasrow[myyntikielto]'>";
			echo "<input type='hidden' name='muutparametrit' value='$muutparametrit'>";
		}

		echo "<tr><td class='back' colspan='6' align='right'>Ruksaa kaikki &raquo; <input type='checkbox' name='ruksaakaikki' onclick='toggleAll(this)'></td></tr>";
		echo "</table>";

		echo "<input type='submit' value='".t("P‰ivit‰ luottorajat")."'>";

		echo "</form>";
	}

	require ("inc/footer.inc");

?>