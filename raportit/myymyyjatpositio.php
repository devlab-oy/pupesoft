<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;
	$pupe_DataTables = "myymyyjat";

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myyjien rivimyynnit").":</font><hr>";

	// Käyttöliittymä
	if (!isset($alkukk))  $alkukk  = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
	if (!isset($alkuvv))  $alkuvv  = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
	if (!isset($loppukk)) $loppukk = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
	if (!isset($loppuvv)) $loppuvv = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
	$tee = isset($tee) ? $tee : "";
	$toimitetut = isset($toimitetut) ? $toimitetut : "";

	if ($toimitetut != "") {
		$loppukk = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")));
		$loppuvv = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")));
	}

	if (checkdate($alkukk, 1, $alkuvv) and checkdate($loppukk, 1, $loppuvv)) {
		// MySQL muodossa
		$pvmalku  = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
		$pvmloppu = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));
	}
	else {
		echo "<font class='error'>".t("Päivämäärävirhe")."!</font>";
		$tee = "";
	}

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Anna alkukausi (kk-vuosi)")."</th>";
	echo "	<td>
			<input type='text' name='alkukk' value='$alkukk' size='2'>-
			<input type='text' name='alkuvv' value='$alkuvv' size='5'>
			</td>";
	echo "</tr>";

	echo "<th>".t("Anna loppukausi (kk-vuosi)")."</th>";
	echo "	<td>
			<input type='text' name='loppukk' value='$loppukk' size='2'>-
			<input type='text' name='loppuvv' value='$loppuvv' size='5'>
			</td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";

	$rukchk = "";
	if ($toimitetut != '') $rukchk = "CHECKED";

	echo "<tr><th>".t("Myös toimitetut tilaukset")."</th>
			<td><input type='checkbox' name='toimitetut' value='JOO' $rukchk></td>";
	echo "</tr>";


	echo "</table>";
	echo "<br>";

	if ($tee != '') {

		// myynnit
		$query = "	SELECT tilausrivin_lisatiedot.positio myyja,
					ifnull(kuka.nimi, 'Ö-muu') nimi,
					date_format(tilausrivi.laskutettuaika,'%Y/%m') kausi,
					round(sum(tilausrivi.rivihinta),0) summa
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'L')
					JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
					LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = tilausrivin_lisatiedot.positio)
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					and lasku.tila    = 'L'
					and lasku.alatila = 'X'
					and lasku.tapvm >= '$pvmalku'
					and lasku.tapvm <= '$pvmloppu'
					GROUP BY myyja, nimi, kausi
					HAVING summa <> 0";


		if ($toimitetut  != '') {
			$query2 = "	SELECT tilausrivin_lisatiedot.positio myyja,
						ifnull(kuka.nimi, 'Ö-muu') nimi,
						date_format(now(),'%Y/%m') kausi,
						round(sum(tilausrivi.hinta),0) summa
						FROM lasku
						JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'L')
						JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
						LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = tilausrivin_lisatiedot.positio)
						WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
						and lasku.tila    = 'L'
						and lasku.alatila = 'D'
						GROUP BY myyja, nimi, kausi
						HAVING summa <> 0";

			$query = "($query) UNION ($query2)";

		}

		$result = pupe_query($query." ORDER BY myyja, nimi, kausi");

		$summa = array();
		$myyja_nimi = array();

		while ($row = mysql_fetch_array($result)) {
			$myyja_nimi[$row["myyja"]] = $row["nimi"];
			$summa[$row["myyja"]][$row["kausi"]] = $row["summa"];
		}

		$sarakkeet	= 0;
		$raja 		= '0000-00';
		$rajataulu 	= array();

		while ($raja < substr($pvmloppu, 0, 7)) {

			$vuosi = substr($pvmalku, 0, 4);
			$kk = substr($pvmalku, 5, 2);
			$kk += $sarakkeet;

			if ($kk > 12) {
				$vuosi++;
				$kk -= 12;
			}

			if ($kk < 10) $kk = '0'.$kk;

			$rajataulu[$sarakkeet] = "$vuosi/$kk";
			$sarakkeet++;
			$raja = $vuosi."-".$kk;
		}

		$sarakemaara = count($rajataulu)+2;

		// Piirretään headerit
		pupe_DataTables(array(array($pupe_DataTables, $sarakemaara, $sarakemaara)));

		echo "<table class='display dataTable' id='$pupe_DataTables'>";

		echo "<thead>";
		echo "<tr>";
		echo "<th>".t("Myyjä")."</th>";

		foreach ($rajataulu as $vvkk) {
			echo "<th>$vvkk</th>";
		}

		echo "<th>".t("Yhteensä")."</th>";
		echo "</tr>";
		echo "</thead>";

		// Piirretään itse data
		$yhteensa_summa_kausi = array();

		foreach ($summa as $myyja => $kausi_array) {

			echo "<tr class='aktiivi'>";
			echo "<td>$myyja_nimi[$myyja] ($myyja)</td>";

			$yhteensa_summa = 0;

			foreach ($rajataulu as $kausi) {

				if (!isset($yhteensa_summa_kausi[$kausi])) $yhteensa_summa_kausi[$kausi] = 0;

				$summa = isset($kausi_array[$kausi]) ? $kausi_array[$kausi] : "";

				$yhteensa_summa += $summa;

				$yhteensa_summa_kausi[$kausi] += $summa;

				echo "<td style='text-align:right;'>$summa</td>";
			}

			echo "<td style='text-align:right;'>$yhteensa_summa</td>";
			echo "</tr>";
		}

		// Piirretään yhteensärivi
		echo "<tfoot>";
		echo "<tr>";
		echo "<th>".t("Yhteensä summa")."</th>";

		$yhteensa_summa = 0;

		foreach ($rajataulu as $kausi) {
			$yhteensa_summa += $yhteensa_summa_kausi[$kausi];
			echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]</th>";

		}

		echo "<th style='text-align:right;'>$yhteensa_summa</th>";
		echo "</tr>";
		echo "</tfoot>";

		echo "</table>";

	}

	require ("inc/footer.inc");
