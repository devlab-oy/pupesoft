<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Suoritukset")."</font><hr>";

	if ($tee == 'B') { // Suoritetaan lasku
//Etsitaan yrityksen pankkitili

		$query = "SELECT *
				  FROM yriti
				  WHERE yhtio = '$kukarow[yhtio]' and tunnus='$pankki'";
		$result = mysql_query($query) or pupe_error($query);

		$trow   = mysql_fetch_array ($result);

//Kirjataan kirjanpitoon

		$query = "SELECT *
				  FROM lasku
				  WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query) or pupe_error($query);
		$orow   = mysql_fetch_array ($result);

		$selite  = "Maksettu lasku $orow[nimi]";
		$vero    = 0;
		$tiliper = $trow['oletus_rahatili'];
		$tilian  = $yhtiorow['myyntisaamiset'];
		$summa	 = $orow['summa'];

		$ltunnus = $orow['tunnus'];

		$query = "INSERT into tiliointi set
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$ltunnus',
					tilino = '$tiliper',
					tapvm = '$pvm',
					summa = '$summa',
					vero = '$vero',
					selite = '$selite',
					lukko = '',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);

		$summa = $summa * -1;

		$query = "INSERT into tiliointi set
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$ltunnus',
					tilino = '$tilian',
					tapvm = '$pvm',
					summa = '$summa',
					vero = '$vero',
					selite = '$selite',
					lukko = '',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);

//Merkataan lasku maksetuksi

		$query = "UPDATE lasku
				SET maksaja='$kukarow[kuka]', mapvm='$pvm'
				WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Suoritettin lasku")." $orow[nimi] $orow[summa] ".t("ok")."!</font>";
		$tee='';
	}


	if ($tee == 'A') { // Näytetään asiakkaan laskuja nimen tai laskun summan perusteella
		$summa = $nimi + 0;
		if ($summa == 0) { // Siella oli nimi
			$query = "SELECT tunnus, laskunro, concat_ws(' ',nimi, nimitark) nimi, erpcm, summa
				FROM lasku
				WHERE nimi like '%" . $nimi . "%' and yhtio = '$kukarow[yhtio]' and tila = 'L' and
						maksaja='' and erpcm > '0000-00-00' ";
		}
		else {
			$query = "SELECT tunnus, laskunro, concat_ws(' ',nimi, nimitark) nimi, erpcm, summa
				FROM lasku
				WHERE summa  = $summa and yhtio = '$kukarow[yhtio]' and tila = 'L' and
						maksaja='' and erpcm > '0000-00-00'";
		}
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<br><b>".t("Haulla ei löytynyt yhtään laskua/asiakasta")."</b><br><br>";
			$tee='';
		}
		else {
			echo "<table><tr>";
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th align='left'>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<form action = '$PHP_SELF?tee=B&tunnus=$trow[tunnus]&pvm=".$vv."-".$kk."-".$pp."&pankki=$tili' method='post'><tr>";
				for ($i=1; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "<td><input type = 'submit' value = '".t("Suorita")."'></td></tr></form>";
			}
			echo "</table><br>";
		}
	}

	if ($tee == '') {

		if ($pp == '') {
			$pp = date(j);
			$kk = date(n);
			$vv = date(Y);
		}

		$query = "SELECT tunnus, nimi
					FROM yriti
					WHERE yhtio='$kukarow[yhtio]'";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<form name = 'valinta' action = '$PHP_SELF?tee=A' method='post'>";
		echo "<table>
				<tr><td>".t("Anna pankkitili, jolta maksetaan")."</td><td><select name='tili'>";
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tili == $srow[0]) {
				$sel = "selected";
			}
			echo "<option value='$srow[0]' $sel>$srow[1]</option>";
		}
		echo "</select></td></tr>
			<tr><td>".t("Valitse asiakas tai anna maksettu summa")."</td><td><input type = 'text' name = 'nimi' value = '$nimi'></td></tr>
			<tr><td>".t("Maksuajankohta")."</td><td>
				<input type='text' name='pp' size = '3' value = '$pp'>
				<input type='text' name='kk' size = '3' value = '$kk'>
				<input type='text' name='vv' size = '6' value = '$vv'></tr>
			<tr><td></td><td><input type = 'submit' value = '".t("Valitse")."'></td>
			</table></form>";

		$formi = 'valinta';
		$kentta = 'nimi';
		require "inc/footer.inc";
		exit;
	}
?>