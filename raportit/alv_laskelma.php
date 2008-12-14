<?php
	function laskeveroja ($taso, $tulos) {
		global $kukarow, $startmonth, $endmonth;

		if ($tulos == '22' or $tulos == 'veronmaara' or $tulos == 'summa') { 
			$query = "SELECT group_concat(tilino) tilit
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%$taso%'";
			$tilires = mysql_query($query) or pupe_error($query);

			$vero = 0.0;

			$tilirow = mysql_fetch_array($tilires);

			if ($tilirow['tilit']!='') {
				$query = "SELECT round(sum(summa * if('$tulos'='22',22,vero) / 100), 2) veronmaara,
						sum(summa) summa,
				 		count(*) kpl
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						AND korjattu = '' 
						AND tilino in ($tilirow[tilit]) 
						AND tapvm >= '$startmonth' 
						AND tapvm <= '$endmonth'";
						
				$verores = mysql_query($query) or pupe_error($query);

				while ($verorow = mysql_fetch_array ($verores)) {
					//echo "$verorow[veronmaara] $verorow[summa] $verorow[kpl] / ";
					if ($tulos == '22') $tulos = 'veronmaara';
					$vero += $verorow[$tulos];
				}

			}
		}
		else {
			$vero = 0;
		}
		return sprintf('%.2f',$vero);
	}
	
	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

	// tehdään käyttöliittymä, näytetään aina
	echo "<form action = 'alv_laskelma.php' method='post'>";
	echo "<input type = 'hidden' name = 'tee' value = 'aja'>";
	echo "<table>";

	if (!isset($vv)) $vv = date("Y");
	if (!isset($kk)) $kk = date("n");

	echo "<tr>";
	echo "<th>".t("Valitse kausi")."</th>";
	echo "<td>";

	$sel = array();
	$sel[$vv] = "SELECTED";

	echo "<select name='vv'>";
	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}
	echo "</select>";

	$sel = array();
	$sel[$kk] = "SELECTED";

	echo "<select name='kk'>
			<option $sel[1] value = '1'>01</option>
			<option $sel[2] value = '2'>02</option>
			<option $sel[3] value = '3'>03</option>
			<option $sel[4] value = '4'>04</option>
			<option $sel[5] value = '5'>05</option>
			<option $sel[6] value = '6'>06</option>
			<option $sel[7] value = '7'>07</option>
			<option $sel[8] value = '8'>08</option>
			<option $sel[9] value = '9'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select>";
	echo "</td>";
	echo "</tr>";

	echo "<td class='back' style='text-align:bottom;'><input type = 'submit' value = '".t("Näytä")."'></td>";
	echo "</tr>";
	
	echo "</table>";

	echo "</form><br>";

	if ($tee == "aja") {

		$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
		$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));
		
	// 201-203 sääntö fi200
		
		$query = "SELECT group_concat(tilino) tilit
				FROM tili
				WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%fi200%'";
		$tilires = mysql_query($query) or pupe_error($query);

		$fi201 = 0.0;
		$fi202 = 0.0;
		$fi203 = 0.0;
		
		$tilirow = mysql_fetch_array($tilires);

		if ($tilirow['tilit']!='') {
			$query = "SELECT vero, round(sum(summa * vero / 100) * -1, 2) veronmaara, count(*) kpl
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]'
					AND korjattu = '' 
					AND tilino in ($tilirow[tilit]) 
					AND tapvm >= '$startmonth' 
					AND tapvm <= '$endmonth'
					AND vero > 0
					GROUP BY vero";
			$verores = mysql_query($query) or pupe_error($query);

			while ($verorow = mysql_fetch_array ($verores)) {
//				echo "$verorow[vero] $verorow[kpl] / ";
				switch ($verorow['vero']) {
					case 22 :
						$fi201 += $verorow['veronmaara'];
						break;
					case 17 :
						$fi202 += $verorow['veronmaara'];
						break;
					case 8 :
						$fi203 += $verorow['veronmaara'];
						break;
				}
			}
		}
		echo "<br><table>";
		echo "<tr><th colspan='2'>Vero kotimaan myynnistä verokannoittain</th></tr>";
		echo "<tr><td>201 22% :n vero</td><td align='right'>".sprintf('%.2f',$fi201)."</td></tr>";
		echo "<tr><td>202 17% :n vero</td><td align='right'>".sprintf('%.2f',$fi202)."</td></tr>";
		echo "<tr><td>203 8% :n vero</td><td align='right'>".sprintf('%.2f',$fi203)."</td></tr>";

	// 205 sääntö fi205

		$fi205 = laskeveroja('fi205','22');
		
		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>205 Vero tavaraostoista muista EU-maista</td><td align='right'>$fi205</td></tr>";

	// 206 sääntö fi206

		$fi206 = laskeveroja('fi206','veronmaara') + $fi205;
		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>206 Kohdekuukauden vähennettävä vero</td><td align='right'>$fi206</td></tr>";

	// 207 sääntö fi207

		$fi207 = 0.0;

		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>207 Edellisen kuukauden negatiivinen vero</td><td align='right'>$fi207</td></tr>";

	// 208 laskennallinen

		$fi208 = $fi201 + $fi202 + $fi203 + $fi205 - $fi206 - $fi207;

		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>208 Maksettava vero(+)/Seuraavalle kuukaudelle siirrettävä negatiivinen vero (-)</td><td align='right'>".sprintf('%.2f',$fi208)."</td></tr>";

	// 209 sääntö fi209

		$fi209 = laskeveroja('fi209','summa') * -1;

		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>209 Veroton liikevaihto</td><td align='right'>$fi209</td></tr>";

	// 210 sääntö fi210

		$fi210 = laskeveroja('fi210','summa') * -1;

		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>210 Tavaran myynti muihin EU-maihin </td><td align='right'>$fi210</td></tr>";

	// 211 sääntö fi205

		$fi211 = laskeveroja('fi205','summa');

		echo "<tr><th colspan='2'></th></tr>";
		echo "<tr><td>211 Tavaraostot muista EU-maista</td><td align='right'>$fi211</td></tr>";
		echo "</table>";
	}

	require("inc/footer.inc");

?>
