<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Myyntisaamisten kirjaus luottotappioiksi")."</font><hr>";

if ($tila == 'K') {
	$tpk = (int) $tpk;
	$tpp = (int) $tpp;
	$tpv = (int) $tpv;

	if ($tpv < 1000) $tpv += 2000;

	if (!checkdate($tpk, $tpp, $tpv)) {
		echo "<font class='error'>".t("Virheellinen tapahtumapvm")."</font><br>";
		$tila = 'N';
	}
}

if ($tila == 'K') {

	$query = "	SELECT lasku.*, tiliointi.ltunnus, tiliointi.tilino, tiliointi.summa, tiliointi.vero
				FROM lasku
				JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus AND tiliointi.tapvm = lasku.tapvm 
				AND tiliointi.tilino NOT IN ('$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[alv]'))
				WHERE lasku.yhtio		= '$kukarow[yhtio]'
				AND lasku.mapvm			= '0000-00-00'
				AND lasku.tila			= 'U'
				AND lasku.alatila		= 'X'
				AND lasku.liitostunnus	= '$liitostunnus'
				ORDER BY 1";
	$laskuresult = mysql_query($query) or pupe_error($query);

	while ($lasku = mysql_fetch_array($laskuresult)) {
		
		if ($lasku['tilino'] != $yhtiorow['myyntisaamiset'] and $lasku['tilino'] != $yhtiorow['factoringsaamiset'] and $lasku['tilino'] != $yhtiorow['konsernimyyntisaamiset']) {
			// Hoidetaan alv
			$alv = round($lasku['summa'] * $lasku['vero'] / 100, 2);

			$query = "	INSERT INTO tiliointi SET
						yhtio		= '$kukarow[yhtio]',
						ltunnus		= '$lasku[ltunnus]',
						tilino		= '$yhtiorow[luottotappiot]',
						kustp		= '$lasku[kustp]',
						kohde		= '$lasku[kohde]',
						projekti	= '$lasku[projekti]',
						tapvm 		= '$tpv-$tpk-$tpp',
						summa		= $lasku[summa] * -1,
						vero		= '$lasku[vero]',
						selite		= '$lasku[selite]',
						lukko		= '',
						tosite		= '$lasku[tosite]',
						laatija		= '$kukarow[kuka]',
						laadittu	= now()";
			$result = mysql_query($query) or pupe_error($query);
			$isa = mysql_insert_id ($link);

			// Tiliöidään alv
			if ($lasku['vero'] != 0) {
				
				// jos yhtiön toimipaikka löytyy, otetaan alvtilinumero tämän takaa jos se löytyy
				if ($lasku["yhtio_toimipaikka"] != '' and $yhtiorow["toim_alv"] != '') {
					$query = "	INSERT INTO tiliointi SET
								yhtio		= '$kukarow[yhtio]',
								ltunnus		= '$lasku[ltunnus]',
								tilino		= '$yhtiorow[toim_alv]',
								kustp		= '',
								kohde		= '',
								projekti	= '',
								tapvm		= '$tpv-$tpk-$tpp',
								summa		= $alv * -1,
								vero		= '',
								selite		= '$lasku[selite]',
								lukko		= '1',
								tosite		= '$lasku[tosite]',
								laatija		= '$kukarow[kuka]',
								laadittu	= now(),
								aputunnus	= '$isa'";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	INSERT INTO tiliointi SET
								yhtio		= '$kukarow[yhtio]',
								ltunnus		= '$lasku[ltunnus]',
								tilino		= '$yhtiorow[alv]',
								kustp		= '',
								kohde		= '',
								projekti	= '',
								tapvm		= '$tpv-$tpk-$tpp',
								summa		= $alv * -1,
								vero		= '',
								selite		= '$lasku[selite]',
								lukko		= '1',
								tosite		= '$lasku[tosite]',
								laatija		= '$kukarow[kuka]',
								laadittu	= now(),
								aputunnus	= '$isa'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}
		else {
			$query = "INSERT INTO tiliointi SET
					yhtio 		= '$kukarow[yhtio]',
					ltunnus		= '$lasku[ltunnus]',
					tilino		= '$lasku[tilino]',
					kustp		= '$lasku[kustp]',
					kohde		= '$lasku[kohde]',
					projekti	= '$lasku[projekti]',
					tapvm		= '$tpv-$tpk-$tpp',
					summa		= $lasku[summa] * -1,
					vero		= '',
					selite		= '$lasku[selite]',
					lukko		= '',
					tosite		= '$lasku[tosite]',
					laatija		= '$kukarow[kuka]',
					laadittu	= now()";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "UPDATE lasku set mapvm = '$tpv-$tpk-$tpp' where yhtio ='$kukarow[yhtio]' and tunnus = '$lasku[ltunnus]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	echo "<font class='message'>".t("Laskut on tiliöity luottotappioksi")."!</font><br><br>";
	$tila = "";
}

if ($tila == 'N') {

	$query = "	SELECT *, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl
				FROM lasku USE INDEX (yhtio_tila_mapvm)
				WHERE mapvm			= '0000-00-00'
				AND tila			= 'U'
				AND alatila			= 'X'
				AND yhtio			= '$kukarow[yhtio]'
				AND liitostunnus	= '$liitostunnus'
				GROUP BY liitostunnus
				ORDER BY ytunnus";
	$result = mysql_query($query) or pupe_error($query);
	$asiakas = mysql_fetch_array ($result);

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("asiakas")."</th>";
	echo "<th>".t("summa")."</th>";
	echo "<th>".t("kpl")."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>$asiakas[ytunnus]</td>";
	echo "<td>$asiakas[asiakas]</td>";
	echo "<td>$asiakas[summa]</td>";
	echo "<td>$asiakas[kpl]</td>";
	echo "</tr>";

	echo "</table>";

	echo "<br><font class='message'>".t("Erittely:")."</font><br>";
	echo "<table><tr>";

	$query = "	SELECT laskunro, tapvm, erpcm, summa-saldo_maksettu summa
				FROM lasku
				WHERE mapvm		= '0000-00-00'
				AND tila		= 'U'
				AND alatila		= 'X'
				AND yhtio		= '$kukarow[yhtio]'
				AND liitostunnus = '$liitostunnus'
				ORDER BY 1";
	$result = mysql_query($query) or pupe_error($query);

	echo "<tr>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Eräpvm")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "</tr>";

	while ($lasku = mysql_fetch_array ($result)) {
		echo "<tr>";
		echo "<td>$lasku[laskunro]</td>";
		echo "<td>".tv1dateconv($lasku["tapvm"])."</td>";
		echo "<td>".tv1dateconv($lasku["erpcm"])."</td>";
		echo "<td align='right'>$lasku[summa]</td>";
		echo "</tr>";
	}

	echo "</table><br>";

	echo "<form action = '$PHP_SELF' method = 'post' name='pvm'>";
	echo "<input type='hidden' name='tila' value='K'>";
	echo "<input type='hidden' name='liitostunnus' value='$liitostunnus'>";


	if (!isset($tpk)) $tpk = date("m");
	if (!isset($tpv)) $tpv = date("Y");
	if (!isset($tpp)) $tpp = date("d");

	echo "<table>";
	echo "<tr>";
	echo "<th colspan='2'>".t("Kirjaa luottotappioksi")."</th>";
	echo "</tr><tr>";
	echo "<td>".t("Päivämäärä")." ".t("pp-kk-vvvv")."</td>";
	echo "<td>
			<input type='text' name='tpp' maxlength='2' size='2' value='$tpp'>
			<input type='text' name='tpk' maxlength='2' size='2' value='$tpk'>
			<input type='text' name='tpv' maxlength='4' size='5' value='$tpv'></td>";
	echo "<td class='back'><input type='submit' value='".t("Luottotappio")."'></td>";
	echo "</tr>";
	echo "</table>";

	echo "</form>";

	$formi	='pvm';
	$kentta	='tpp';
}

if ($tila == "") {

	$query = "	SELECT *, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl
				FROM lasku USE INDEX (yhtio_tila_mapvm)
				WHERE mapvm		= '0000-00-00'
				AND tila		= 'U'
				AND alatila		= 'X'
				AND yhtio		= '$kukarow[yhtio]'
				AND liitostunnus != 0
				GROUP BY liitostunnus
				ORDER BY ytunnus";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("asiakas")."</th>";
	echo "<th>".t("summa")."</th>";
	echo "<th>".t("kpl")."</th>";
	echo "<th>".t("valitse")."</th>";
	echo "</tr>";

	while ($asiakas = mysql_fetch_array ($result)) {

		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tila' value='N'>";
		echo "<input type='hidden' name='liitostunnus' value='$asiakas[liitostunnus]'>";

		echo "<tr class='aktiivi'>";
		echo "<td>$asiakas[ytunnus]</td>";
		echo "<td>$asiakas[asiakas]</td>";
		echo "<td>$asiakas[summa]</td>";
		echo "<td>$asiakas[kpl]</td>";
		echo "<td><input type='submit' value='".t("Luottotappio")."'></td>";
		echo "</tr>";

		echo "</form>";

	}

	echo "</table>";
}

require ("../inc/footer.inc");

?>