<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Loppuneet tuotteet").":</font><hr>";

	if (isset($kk) or isset($pp) or isset($vv)) {
		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>".t("Päivämäärävirhe")."!</font><br>";
			$tee="";
		}
	}

	if ($tee != '') {
		$varastot = "";

		if (is_array($varastosta)) {
			foreach($varastosta as $var) {
				$varastot .= $var.",";
			}
			$varastot = substr($varastot,0,-1);
			$varastot = " and varastopaikat.tunnus in ($varastot) ";
		}

		$query = "	SELECT tuote.osasto, tuote.try, tuotepaikat.tuoteno, tuote.nimitys, tuotepaikat.saldoaika,
					concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka,
					tuote.yksikko, tuotepaikat.inventointiaika, tuote.tahtituote, tuote.hinnastoon, tuote.status,
					group_concat(tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
					group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja
					FROM tuotepaikat
					JOIN tuote ON tuote.yhtio=tuotepaikat.yhtio	and tuote.tuoteno=tuotepaikat.tuoteno
					LEFT JOIN varastopaikat
					ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno
					WHERE tuotepaikat.yhtio='$kukarow[yhtio]'
					and saldoaika>='$vv-$kk-$pp 00:00:00'
					and saldo<=0
					$varastot
					group by 1,2,3,4,5,6,7,8,9,10,11
					ORDER BY osasto, try, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 498) {
			// echo "<font class='message'>yli500tuotettaraj!</font><br>";
			// exit;
		}

		echo "	<table><tr><th>".t("Osasto")."</th><th>".t("Tuoteryhmä")."</th>
				<th nowrap>".t("Tuotenumero")."</th><th nowrap>".t("Nimitys")."</th>
				<th nowrap>".t("Loppunut")."</th>
				<th nowrap>".t("Tilauksessa")."</th><th nowrap>".t("Ensimmäinen toimitus")."</th>
				<th nowrap>".t("Varastopaikka")."</th><th nowrap>".t("Toimittajan tuoteno")."</th>
				<th nowrap>".t("Tähtituote")."</th><th nowrap>".t("Hinnastoon")."</th>
				<th nowrap>".t("Status")."</th><th nowrap>".t("Toimittaja")."</th></tr>";

		$rivit = 1;
		while ($row = mysql_fetch_array($result)) {
			//katsotaan onko tuotetta tilauksessa
			$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and varattu>0 and tyyppi='O'";
			$result1 = mysql_query($query) or pupe_error($query);
			$prow    = mysql_fetch_array($result1);

			echo "	<tr><td>$row[osasto]</td><td>$row[try]</td><td>$row[tuoteno]</td><td>".t_tuotteen_avainsanat($row, 'nimitys')."</td>
					<td>".substr($row["saldoaika"],0,10)."</td><td>$prow[varattu]</td><td>$prow[toimaika]</td>
					<td>$row[varastopaikka]</td><td>$row[toim_tuoteno]</td>
					<td>$row[tahtituote]</td><td>$row[hinnastoon]</td>
					<td>$row[status]</td><td>$row[toimittaja]</td></tr>";
		}
		echo "</table>";
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post'>";

	if (!isset($kk))
		$kk = date("m");
	if (!isset($vv))
		$vv = date("Y");
	if (!isset($pp))
		$pp = date("d")-1;

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp' value='$pp' size='3'></td>
			<td><input type='text' name='kk' value='$kk' size='3'></td>
			<td><input type='text' name='vv' value='$vv' size='5'></td></tr>";

	//valitaan varasto
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tyyppi, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vtresult)) {
		$sel = "";

		if ((!isset($varastosta[$vrow["tunnus"]]) and $tee == 'JATKA') or ($varastosta[$vrow["tunnus"]] != '')) {
			$sel = "CHECKED";
		}

		echo "<tr><th>".t("Valitse varasto:")."</th><td colspan='3'><input type='checkbox' name='varastosta[$vrow[tunnus]]' value='$vrow[tunnus]' $sel> $vrow[nimitys]</td></tr>";
	}

	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");
?>