<?php

if (isset($tee)) {

	$alvv = (int) $alvv;
	$alvk = (int) $alvk;
	$alvp = (int) $alvp;
	$tkausi = (int) $tkausi;

	// Tutkitaan ensiksi, mille tilikaudelle pyydettävä lista löytyy, jos lista on sopiva
	$blvk = 0;
	$blvp = 0;

	if ($tkausi == 0) {
		$blvk = $alvk;
		$blvp = $blvk;
		if ($blvk==0) $blvk=1;
		if ($blvp==0) $blvp=1;
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and '$alvv-$blvk-$blvp' >= tilikausi_alku and '$alvv-$blvk-$blvp' <= tilikausi_loppu";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if (mysql_num_rows($result) != 1) {
		echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei löytynyt")."</font>";
		exit;
	}

	$tilikaudetrow=mysql_fetch_array($result);

	$lisa = '';
	$lisa2 = '';
	$nimi = "Eliminointi";
	$alku = "CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili, DATE_FORMAT(tiliointi.tapvm,'%d.%m.%Y') tapvm";
	$jarj = "tiliointi.tilino, tiliointi.tapvm, tiliointi.summa";

	if ($tkausi != '0') {
        echo "<font class='message'>$nimi ".t("tilikaudelta")." $tilikaudetrow[tilikausi_alku] - $tilikaudetrow[tilikausi_loppu]</font><hr>";
		$lisa  = "tiliointi.tapvm <= '$tilikaudetrow[tilikausi_loppu]' and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'";
		$lisa2 = "tiliointi.tapvm < '$tilikaudetrow[tilikausi_alku]'"; //hassua hassua
	}
	elseif ($alvk == '0') {
		echo "<font class='message'>$nimi ".t("kalenterivuodelta")." $alvv</font><hr>";
		$lisa  = "YEAR(tiliointi.tapvm) = '$alvv'";
		$apualvv = $alvv + 1;
		$lisa2 = "tiliointi.tapvm < '$apualvv-01-01'";
	}
	elseif ($alvp == '0') {
		echo "<font class='message'>$nimi ".t("kaudelta")." $alvv-$alvk</font><hr>";
		$lisa  = "CONCAT_WS(' ',YEAR(tiliointi.tapvm),MONTH(tiliointi.tapvm)) = '$alvv $alvk'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-01'";
	}
	else {
		echo "<font class='message'>$nimi ".t("päivältä")." $alvv-$alvk-$alvp</font><hr>";
		$lisa  = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-$alvp'";
	}

	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and tiliointi.kohde = '" . $kohde . "'";
		$lisa2 .= " and tiliointi.kohde = '" . $kohde . "'";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and tiliointi.projekti = '" . $proj . "'";
		$lisa2 .= " and tiliointi.projekti = '" . $proj . "'";
	}
	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and tiliointi.kustp = '" . $kustp . "'";
		$lisa2 .= " and tiliointi.kustp = '" . $kustp . "'";
	}

	if ($tili2 == 0) $tili2 = $tili;

	if (strlen(trim($tili)) > 0) {
		$lisa .= " and tiliointi.tilino >= '" . $tili . "' and  tiliointi.tilino <= '" . $tili2 . "'";
		$lisa2 .= " and tiliointi.tilino >= '" . $tili . "' and  tiliointi.tilino <= '" . $tili2 . "'";
	}

	//jos tili on valittu lasketaan sen alkusaldo
	if (strlen(trim($tili)) > 0) {
		$query = "	SELECT sum(summa) saldo
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu=''
					and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'
					and $lisa2";
		$result   = mysql_query($query) or pupe_error($query);
		$alkurow  = mysql_fetch_array ($result);
		$kumsumma = $alkurow["saldo"];
	}

	$query = "	SELECT $alku , tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, CONCAT_WS(' ', selite, lasku.nimi) selite, tiliointi.summa, tiliointi.vero, tiliointi.ltunnus
				FROM tiliointi, tili, lasku
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tili.yhtio = '$kukarow[yhtio]' and
					tiliointi.ltunnus = lasku.tunnus and
					tiliointi.tilino = tili.tilino and
					tiliointi.korjattu='' and
					$lisa
				ORDER BY $jarj";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		if (strlen(trim($tili)) > 0) echo "<th>".t("kum. summa")."</th>";
		echo "</tr>";

		//tilin alkusaldo
		if (strlen(trim($tili)) > 0) {
			echo "<tr><td colspan='";
			echo mysql_num_fields($result)-1;
			echo "'></td>";
			echo "<td align='right'>".sprintf("%.2f",$kumsumma)."</td></tr>";
		}

		$summaplus = 0.0;
		$summamiinus = 0.0;
		$i=0;
		while ($trow=mysql_fetch_array ($result)) {
			$tilirivi[$i] 	= $trow;
			$sorttaus[$i] 	= $trow['summa'];
			$i++;
		}
		
		$vastis = $sorttaus;
		
		foreach ($sorttaus as $key => $summa) {
						
			//	Onko arrayssa vastasummaa?
			$v = array_search($summa*-1, $vastis);
			if($v > 0) {
				$valitut[$v] = $v;
				$valitut[$key] = $v;				
				unset($vastis[$v]);
			}
		}

		echo sizeof($valitut) . " eliminointia tehtiin!<br>";

		for ($key=0; $key<sizeof($sorttaus); $key++) {

			$class = "";

			if (isset($valitut[$key])) {
				$class = "spec";
			}

			$trow=$tilirivi[$key];
			//echo "<td>'$key','$sorttaus[$key]'</td>";

			// katotaan saadaanko näyttää eliminoitu rivi:
			if (!isset($valitut[$key]) or $alanayta == "") {

				echo "<tr class='aktiivi'>";

				for ($i=0; $i<mysql_num_fields($result)-1; $i++) {

					if ($i == 0) {

						if ($edtrow[$i] == $trow[$i]) { // Vaihtuiko joku??
							$trow[$i] = "&nbsp";
						}
						else {
							if ($eka != 0) {
								echo "<td></td><td></td><td></td><td></td><td></td><td></td><td align = 'right'>";
								printf("%.2f", $summa);
								echo "</td><td>*</td>";

								if (strlen(trim($tili)) > 0) {
									echo "<td></td>";
								}

								echo "</tr><tr>";
								$summa = 0;
							}
							else {
								$eka = 1;
							}
							$edtrow[0] = $trow[0];
						}
					}

					if ($i > 5) {
						if ($i == 6) {

							echo "<td class='$class' align='right' nowrap>";

							if($excel=='') { // tehdään linkki jos ei haluta excel muodossa
								echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'><font $class>";
								echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
								echo "</font></a>";
							}
							else {
								echo "<font $class>".str_replace(".",",",sprintf("%.2f", $trow[$i]))."</font>";
							}
							echo "</td>";

							if ($trow[$i] < 0 )
								$summamiinus += $trow[$i];
							else
								$summaplus += $trow[$i];

						 }
						 else {
							echo "<td class='$class' align = 'right' nowrap>";
							echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
							echo "</td>";
						 }

					}
					else {
						// Selväkieliset tarkenteet
						if (($i > 1) and ($i < 5) and ($trow[$i] > 0)) {
							$query = "SELECT nimi
										FROM kustannuspaikka WHERE tunnus = '$trow[$i]'";
							$vresult = mysql_query($query) or pupe_error($query);
							$vrow=mysql_fetch_array($vresult);
							$trow[$i] = $vrow[0];
						}
						if (($i == 5) and ($excel!='')) $trow[$i]=str_replace("<br>"," ",$trow[$i]);

						echo "<td class='$class'>$trow[$i]</td>";
					}
				}

				if (strlen(trim($tili)) > 0) {
					//lasketaan ja echotaan kumulatiivinen summa
					$kumsumma += $trow["summa"];
					echo "<td class='$class' align='right' nowrap>".str_replace(".",",",sprintf("%.2f",$kumsumma))."</td>";
				}

				echo "</tr>";
			}
		}

		// Jos halutaan excel muotoa niin ei tehdä tätä..
		if ($excel=='') {
			echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Negatiiviset yhteensä")."</td>";
			echo "<td align = 'right' nowrap>";
			printf("%.2f", $summamiinus);
			echo "</td><td>*</td>";

			if (strlen(trim($tili)) > 0) {
				echo "<td></td>";
			}

			echo "</tr>";

			echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Positiiviset yhteensä")."</td>";
			echo "<td align = 'right' nowrap>";
			printf("%.2f", $summaplus);
			echo "</td><td>*</td>";

			if (strlen(trim($tili)) > 0) {
				echo "<td></td>";
			}

			echo "</tr>";

			$summa += $summaplus + $summamiinus;
			echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Yhteensä")."</td>";
			echo "<td align = 'right'>";
			printf("%.2f", $summa);
			echo "</td><td>*</td>";

			if (strlen(trim($tili)) > 0) {
				echo "<td></td>";
			}

			echo "</tr>";
		}

		echo "</table><br><br><br>";
	}
	else {
		echo "<font class='error'>Ei tapahtumia</font><br><br><br>";
	}
	unset($tee);
}

if (!isset($tee)) {
	echo "<b>".t("Eliminoi")."</b><hr>
			<form name = 'valinta' action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'eliminoi'>
			<input type = 'hidden' name = 'tee' value = 'X'>
			<table>
			<th>".t("Ajalta")."</th>
			<td><select name='alvv'>
			";

			for ($i = date("Y")+1; $i >= date("Y")-6; $i--) {
				if ($i == date("Y")) $sel = "selected";
				else $sel = "";
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
			</select>
			<select name='alvk'>
			<option value = '0'>".t("koko kalenterivuosi")."
			<option value = '1'>01
			<option value = '2'>02
			<option value = '3'>03
			<option value = '4'>04
			<option value = '5'>05
			<option value = '6'>06
			<option value = '7'>07
			<option value = '8'>08
			<option value = '9'>09
			<option value = '10'>10
			<option value = '11'>11
			<option value = '12'>12
			</select>
			<select name='alvp'>
			<option value = '0'>".t("koko kuukausi")."
			<option value = '1'>01
			<option value = '2'>02
			<option value = '3'>03
			<option value = '4'>04
			<option value = '5'>05
			<option value = '6'>06
			<option value = '7'>07
			<option value = '8'>08
			<option value = '9'>09
			<option value = '10'>10
			<option value = '11'>11
			<option value = '12'>12
			<option value = '13'>13
			<option value = '14'>14
			<option value = '15'>15
			<option value = '16'>16
			<option value = '17'>17
			<option value = '18'>18
			<option value = '19'>19
			<option value = '20'>20
			<option value = '21'>21
			<option value = '22'>22
			<option value = '23'>23
			<option value = '24'>24
			<option value = '25'>25
			<option value = '26'>26
			<option value = '27'>27
			<option value = '28'>28
			<option value = '29'>29
			<option value = '30'>30
			<option value = '31'>31
			</select></td>
			</tr>";

	echo "<tr><th>".t("tai koko tilikausi")."</th>";
 	$query = "SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";
	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow[tunnus]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr>
			<th>Vain tili</th>
			<td><input type = 'text' name = 'tili' value = ''> - <input type = 'text' name = 'tili2' value = ''></td>
			</tr>";

	echo "<tr><th>".t("Vain kustannuspaikka")."</th>";
	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain kohde")."</th>";
 	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "<td><select name='kohde'><option value=' '>".t("Ei valintaa")."";
	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain projekti")."</th>";
	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Älä näytä eliminoituja tiliöintejä")."</th><td><input type='checkbox' name='alanayta'></td></tr>";
	echo "<tr><th>".t("Excel muoto")."</th><td><input type='checkbox' name='excel'></td></tr>";
	echo "</table><br>
	      <input type = 'submit' value = '".t("Näytä")."'></form>";
	$formi = 'valinta';
	$kentta = 'tili';
}

?>
