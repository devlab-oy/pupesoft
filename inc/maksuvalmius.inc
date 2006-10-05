<?php
	echo "<b>".t("Maksuvalmius")."</b><hr>";
	if ($tee == '') {
		echo "<form action = 'raportit.php' method='post'>
				<table><tr>
				<td>
				<input type = 'hidden' name = 'tee' value = '1'>
				<input type = 'hidden' name = 'toim' value = 'maksuvalmius'>
				".t("Maksuvalmius")."</td><td>
				<select name='aika'>
                      <option value = 'pv'>".t("Päivä")."
                      <option value = 'vi'>".t("Viikko")."
                      <option value = 'kk'>".t("Kuukausi")."
				</select></td>
				<td>".t("Konserni")."</td>
				<td><input type = 'checkbox' name = 'konserni'></td>
				<td><input type = 'submit' value = '".t("Näytä")."'></td>
				</tr></table></form>";
		exit;
	}

// Tehdään alkusiivous!
	$query = "SELECT konserni
				FROM yhtio
				WHERE yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 1) {
		$trow=mysql_fetch_array($result);
//		echo "Konserni on '$trow[konserni]'<br>";
	}
	else {
		echo "".t("Yhtiöitä löytyi monta tai ei lainkaan! Virhe!")."";
		exit;
	}

// Poistetaan vanhat tapahtumat

	$query = "DELETE from maksu
				WHERE (konserni='$trow[konserni]' or yhtio='$kukarow[yhtio]') and tyyppi != 'MU'";
	$result = mysql_query($query) or pupe_error($query);

// Lisätään uudet
// Onko meillä konserninäkökulma??
	if ($konserni == 'on') {
		$query = "SELECT yhtio, konserni
					FROM yhtio
					WHERE konserni = '$trow[konserni]' and konserni != ''";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) < 2) {
			echo "".t("Pyysit konserninäkökulmaa, mutta yritys ei ole konsernin osa").".<br>";
			exit;
		}
		else {
			echo "".t("Yrityksiä konserniin")." '$trow[konserni]' ".t("löytyi")." '". mysql_num_rows($result) . "'<br>";
		}

		while ($yrow=mysql_fetch_array ($result)) {
//Myyntireskontra
			$query = "SELECT if(kapvm > now(), kapvm,erpcm) olmapvm, sum(summa) summa, count(*)
						FROM lasku
						WHERE yhtio = '$yrow[yhtio]' and lasku.tila = 'U' and mapvm='0000-00-00'
						GROUP BY 1";
			$yresult = mysql_query($query) or pupe_error($query);

			while ($trow=mysql_fetch_array ($yresult)) {
				$query = "INSERT into maksu values (
							'$yrow[yhtio]',
							'$yrow[konserni]',
							'$kukarow[kuka]',
							'$trow[olmapvm]',
							'MY',
							'$trow[summa]',
							'Ostolaskuja',
							'',
							'')";
				$xresult = mysql_query($query) or pupe_error($query);
			}

//Ostoreskontra
			$query = "SELECT olmapvm, -1 * sum(summa * valuu.kurssi) summa, count(*)
						FROM lasku, valuu, yhtio
						WHERE lasku.yhtio = '$yrow[yhtio]' and valuu.yhtio = '$yrow[yhtio]' and
								lasku.valkoodi = valuu.nimi and lasku.tila in ('H', 'M', 'P','Q')
						GROUP BY olmapvm";
			$yresult = mysql_query($query) or pupe_error($query);

			while ($trow=mysql_fetch_array ($yresult)) {
				$query = "INSERT into maksu values (
							'$yrow[yhtio]',
							'$yrow[konserni]',
							'$kukarow[kuka]',
							'$trow[olmapvm]',
							'OS',
							'$trow[summa]',
							'Ostolaskuja',
							'',
							'')";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}
	}
	else {
		$inyhtio = $kukarow['yhtio'];
		$inkonserni = "";
//Myyntireskontra
		$query = "SELECT if(kapvm > now(), kapvm, erpcm) olmapvm, sum(summa) summa, count(*)
					FROM lasku
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'U'
						and mapvm='0000-00-00'
					GROUP BY 1";

		$result = mysql_query($query) or pupe_error($query);

//		echo "Laskuja '". mysql_num_rows($result) . "'<br>";

		while ($trow=mysql_fetch_array ($result)) {
			$query = "INSERT into maksu values (
						'$kukarow[yhtio]',
						 '',
						'$kukarow[kuka]',
						'$trow[olmapvm]',
						'MY',
						'$trow[summa]',
						'Ostolaskuja',
						'',
						'')";
			$xresult = mysql_query($query) or pupe_error($query);
		}

//Ostoreskontra
		$query = "SELECT olmapvm, -1 * sum(summa * valuu.kurssi) summa, count(*)
					FROM lasku, valuu
					WHERE lasku.yhtio = '$kukarow[yhtio]' and valuu.yhtio = '$kukarow[yhtio]' and
				      lasku.valkoodi = valuu.nimi and lasku.tila in ('H', 'M', 'P', 'Q')
					GROUP BY olmapvm";

		$result = mysql_query($query) or pupe_error($query);

//		echo "Laskuja '". mysql_num_rows($result) . "'<br>";

		while ($trow=mysql_fetch_array ($result)) {
			$query = "INSERT into maksu values (
						'$kukarow[yhtio]',
						 '',
						'$kukarow[kuka]',
						'$trow[olmapvm]',
						'OS',
						'$trow[summa]',
						'Ostolaskuja',
						'',
						'')";
			$xresult = mysql_query($query) or pupe_error($query);
		}
	}

	if ($aika == 'pv') {
		$tapa = 'tapvm';
	}
	if ($aika == 'vi') {
		$tapa = "YEARWEEK(tapvm,1)";
	}
	if ($aika == 'kk') {
		$tapa = "left(tapvm,7)";;
	}
	$query = "SELECT $tapa Aika, sum(if(tyyppi='MY',summa,0)) Myyntires,
						sum(if(tyyppi='OS',summa,0)) Ostores,
						sum(if(tyyppi='MU',-1 * summa,0)) Muu,
						sum(if(tyyppi='MU',-1 * summa,summa)) Yhteensa
				 FROM maksu
				 WHERE yhtio = '$kukarow[yhtio]' and maksettu <> 1
				 GROUP BY Aika
		         ORDER BY Aika";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
	echo "<th>".t("Kumulatiivinen")."</th>";
	echo "</tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			if ($i > 0) {
				if (($i == 2) && ($aika == 'pv')) { // Ostoreskontraan porautuminen
					echo "<td align = 'right'>
						<a href = raportit.php?toim=laskuhaku&tee=M&pvm=$trow[Aika]>$trow[$i]</td>";
				}
				else {
					echo "<td align = 'right'>";
					printf("%.2f", $trow[$i]);
					echo "</td>";
				}

			}
			else {
				echo "<td align = 'right'>$trow[$i]</td>";
			}
		}
		$kumulat += $trow['Yhteensa'];
		echo "<td align = 'right'>";
		printf("%.2f", $kumulat);
		echo "</td></tr>";
	}
	echo "</table>";
?>
