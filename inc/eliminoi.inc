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

		if ($blvk == 0) $blvk = 1;
		if ($blvp == 0) $blvp = 1;

		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					AND '$alvv-$blvk-$blvp' >= tilikausi_alku
					AND '$alvv-$blvk-$blvp' <= tilikausi_loppu";
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

	$tilikaudetrow = mysql_fetch_array($result);

	$lisa = '';  // Tiliöintien haku
	$lisa2 = ''; // Alkusaldon laskentaan

	if ($tkausi != 0) {
        echo "<font class='head'>".t("Eliminointi")." ".t("tilikaudelta")." ".tv1dateconv($tilikaudetrow["tilikausi_alku"])." - ".tv1dateconv($tilikaudetrow["tilikausi_loppu"])."</font><hr>";
		$lisa = "tiliointi.tapvm <= '$tilikaudetrow[tilikausi_loppu]' and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'";
		$lisa2 = "tiliointi.tapvm < '$tilikaudetrow[tilikausi_alku]'";
	}
	elseif ($alvk == 0) {
		echo "<font class='head'>".t("Eliminointi")." ".t("kalenterivuodelta")." $alvv</font><hr>";
		$lisa = "YEAR(tiliointi.tapvm) = '$alvv'";
		$lisa2 = "tiliointi.tapvm < '$alvv-01-01'";
	}
	elseif ($alvp == 0) {
		echo "<font class='head'>".t("Eliminointi")." ".t("kaudelta")." $alvv-$alvk</font><hr>";
		$lisa = "CONCAT_WS(' ',YEAR(tiliointi.tapvm),MONTH(tiliointi.tapvm)) = '$alvv $alvk'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-01'";
	}
	else {
		echo "<font class='head'>".t("Eliminointi")." ".t("päivältä")." $alvv-$alvk-$alvp</font><hr>";
		$lisa  = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-$alvp'";
	}

	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and tiliointi.kohde = '$kohde'";
		$lisa2 .= " and tiliointi.kohde = '$kohde'";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and tiliointi.projekti = '$proj'";
		$lisa2 .= " and tiliointi.projekti = '$proj'";
	}
	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and tiliointi.kustp = '$kustp'";
		$lisa2 .= " and tiliointi.kustp = '$kustp'";
	}

	if ($tili2 == 0) $tili2 = $tili;

	if (strlen(trim($tili)) > 0) {
		$lisa .= " and tiliointi.tilino >= '$tili' and tiliointi.tilino <= '$tili2'";
		$lisa2 .= " and tiliointi.tilino >= '$tili' and tiliointi.tilino <= '$tili2'";
	}

	//jos tili on valittu lasketaan sen alkusaldo
	if (strlen(trim($tili)) > 0) {
		$query = "	SELECT sum(summa) saldo
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'
					and $lisa2";
		$result   = mysql_query($query) or pupe_error($query);
		$alkurow  = mysql_fetch_array ($result);
		$kumsumma = $alkurow["saldo"];
	}

	$query = "	SELECT CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili,
				DATE_FORMAT(tiliointi.tapvm,'%d.%m.%Y') tapvm,
				tiliointi.kustp,
				tiliointi.kohde,
				tiliointi.projekti,
				CONCAT_WS(' ', selite, lasku.nimi) selite,
				tiliointi.summa,
				tiliointi.vero,
				tiliointi.ltunnus
				FROM tiliointi
				LEFT JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
				LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tili.tilino = tiliointi.tilino)
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				AND tiliointi.korjattu = ''
				AND $lisa
				ORDER BY tiliointi.tilino, tiliointi.tapvm, tiliointi.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table>";

		echo "<tr>";
		echo "<th>".t("tili")."</th>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("kustp")."</th>";
		echo "<th>".t("kohde")."</th>";
		echo "<th>".t("projekti")."</th>";
		echo "<th>".t("selite")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("vero")."</th>";
		echo "<th>".t("kum. summa")."</th>";
		echo "</tr>";

		//tilin alkusaldo
		if (strlen(trim($tili)) > 0) {
			echo "<tr>";
			echo "<td colspan='8'></td>";
			echo "<td align='right'>".sprintf("%.2f", $kumsumma)."</td>";
			echo "</tr>";
		}

		$summaplus = 0.0;
		$summamiinus = 0.0;
		$i = 0;
		$valitut = array();
		$vastis = array();
		$sorttaus = array();
		$tilirivi = array();

		while ($trow = mysql_fetch_array ($result)) {
			$tilirivi[$i] = $trow;
			$vastis[$i] = $trow["summa"];
			$sorttaus[$i] = abs($trow['summa']);
			$i++;
		}

		foreach ($tilirivi as $key => $t) {

			$summa = $t["summa"];

			// Onko arrayssa vastasummaa?
			$v = array_search($summa * -1, $vastis);

			if ($v > 0) {
				$valitut[$v] = $v;
				$valitut[$key] = $v;
				unset($vastis[$v]);
			}
		}

		echo count($valitut) . " eliminointia tehtiin!<br><br>";
		asort($sorttaus);

		foreach($sorttaus as $key => $crap) {

			$class = "";

			if (isset($valitut[$key])) {
				$class = "spec";
			}

			$trow = $tilirivi[$key];
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
							$query = "	SELECT nimi
										FROM kustannuspaikka
										WHERE tunnus = '$trow[$i]'";
							$vresult = mysql_query($query) or pupe_error($query);
							$vrow = mysql_fetch_array($vresult);
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

			$summa = $summaplus + $summamiinus;
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

	echo "<font class='head'>".t("Eliminoi")."</font><hr>
		<form name = 'valinta' action = 'raportit.php' method='post'>
		<input type = 'hidden' name = 'toim' value = 'eliminoi'>
		<input type = 'hidden' name = 'tee' value = 'X'>

		<table>
			<th>".t("Ajalta")."</th>
			<td><select name='alvv'>";

			for ($i = date("Y") + 1; $i >= date("Y")-6; $i--) {
				if ($i == date("Y")) {
					$sel = "selected";
				}
				else {
					$sel = "";
				}
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
			</select>

			<select name='alvk'>
			<option value = '0'>".t("koko kalenterivuosi")."</option>
			<option value = '1'>01</option>
			<option value = '2'>02</option>
			<option value = '3'>03</option>
			<option value = '4'>04</option>
			<option value = '5'>05</option>
			<option value = '6'>06</option>
			<option value = '7'>07</option>
			<option value = '8'>08</option>
			<option value = '9'>09</option>
			<option value = '10'>10</option>
			<option value = '11'>11</option>
			<option value = '12'>12</option>
			</select>

			<select name='alvp'>
			<option value = '0'>".t("koko kuukausi")."</option>
			<option value = '1'>01</option>
			<option value = '2'>02</option>
			<option value = '3'>03</option>
			<option value = '4'>04</option>
			<option value = '5'>05</option>
			<option value = '6'>06</option>
			<option value = '7'>07</option>
			<option value = '8'>08</option>
			<option value = '9'>09</option>
			<option value = '10'>10</option>
			<option value = '11'>11</option>
			<option value = '12'>12</option>
			<option value = '13'>13</option>
			<option value = '14'>14</option>
			<option value = '15'>15</option>
			<option value = '16'>16</option>
			<option value = '17'>17</option>
			<option value = '18'>18</option>
			<option value = '19'>19</option>
			<option value = '20'>20</option>
			<option value = '21'>21</option>
			<option value = '22'>22</option>
			<option value = '23'>23</option>
			<option value = '24'>24</option>
			<option value = '25'>25</option>
			<option value = '26'>26</option>
			<option value = '27'>27</option>
			<option value = '28'>28</option>
			<option value = '29'>29</option>
			<option value = '30'>30</option>
			<option value = '31'>31</option>
			</select></td>
			</tr>";

	echo "<tr><th>".t("tai koko tilikausi")."</th>";

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tkausi'>
		<option value='0'>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel = "";
		if ($trow[$i] == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr>
			<th>Vain tili</th>
			<td><input type = 'text' name = 'tili' value = '$tili'> - <input type = 'text' name = 'tili2' value = '$tili2'></td>
			</tr>";

	echo "<tr>
			<th>".t("Vain kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustp'>
		<option value=' '>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel = "";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Vain kohde")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'O'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td>
		<select name='kohde'>
		<option value=' '>".t("Ei valintaa")."</option>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr>
			<th>".t("Vain projekti")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'P'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td>
		<select name='proj'>
		<option value=' '>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel = "";
		if ($trow[$i] == $vrow[0]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr><th>".t("Älä näytä eliminoituja tiliöintejä")."</th><td><input type='checkbox' name='alanayta'></td></tr>";
	echo "<tr><th>".t("Excel muoto")."</th><td><input type='checkbox' name='excel'></td></tr>";
	echo "</table><br>
	      <input type = 'submit' value = '".t("Näytä")."'></form>";

	$formi = 'valinta';
	$kentta = 'tili';

?>