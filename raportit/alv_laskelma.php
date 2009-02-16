<?php

	function alvlaskelma ($kk,$vv) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth;

		function laskeveroja ($taso, $tulos) {
			global $kukarow, $startmonth, $endmonth;

			if ($tulos == '22' or $tulos == 'veronmaara' or $tulos == 'summa') {
				$query = "SELECT group_concat(tilino) tilit
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%$taso%'";
				$tilires = mysql_query($query) or pupe_error($query);

				$vero = 0.0;

				$tilirow = mysql_fetch_array($tilires);
				
				$vainsuomi = '';
				if ($taso == 'fi206') $vainsuomi = "JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.maa in ('FI', '')";

				if ($tilirow['tilit']!='') {
					$query = "SELECT sum(round(tiliointi.summa * if('$tulos'='22',22,vero) / 100, 2)) veronmaara,
							sum(tiliointi.summa) summa,
					 		count(*) kpl
							FROM tiliointi
							$vainsuomi
							WHERE tiliointi.yhtio = '$kukarow[yhtio]'
							AND korjattu = ''
							AND tilino in ($tilirow[tilit])
							AND tiliointi.tapvm >= '$startmonth'
							AND tiliointi.tapvm <= '$endmonth'";

					$verores = mysql_query($query) or pupe_error($query);

					while ($verorow = mysql_fetch_array ($verores)) {
	//					echo "$verorow[veronmaara] $verorow[summa] $verorow[kpl] / ";
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

		echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

		if (isset($kk)) {

			$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
			$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

			// 201-203 sääntö fi200
			$query = "	SELECT group_concat(tilino) tilit
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%fi200%'";
			$tilires = mysql_query($query) or pupe_error($query);

			$fi201 = 0.0;
			$fi202 = 0.0;
			$fi203 = 0.0;

			$tilirow = mysql_fetch_array($tilires);

			if ($tilirow['tilit'] != '') {
				$query = "	SELECT vero, sum(round(summa * vero / 100 * -1, 2)) veronmaara, count(*) kpl
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

			// 205 sääntö fi205
			$fi205 = laskeveroja('fi205','22');

			// 206 sääntö fi206
			$fi206 = laskeveroja('fi206','veronmaara') + $fi205;

			// 207 sääntö fi207
			$fi207 = 0.0;

			// 208 laskennallinen
			$fi208 = $fi201 + $fi202 + $fi203 + $fi205 - $fi206 - $fi207;

			// 209 sääntö fi209
			$fi209 = laskeveroja('fi209','summa') * -1;

			// 210 sääntö fi210
			$fi210 = laskeveroja('fi210','summa') * -1;

			// 211 sääntö fi205
			$fi211 = laskeveroja('fi205','summa');

			if (strtoupper($yhtiorow["maa"]) == 'FI') {
				$uytunnus = tulosta_ytunnus($yhtiorow["ytunnus"]);
			}
			else {
				$uytunnus = $yhtiorow["ytunnus"];
			}

			echo "<br><table>";
			echo "<tr><th>Ilmoittava yritys</th><th>$uytunnus</th></tr>";
			echo "<tr><th>Ilmoitettava kausi</th><th>$startmonth</th></tr>";
			if (basename($_SERVER["SCRIPT_FILENAME"]) == 'alv_laskelma.php') {
				echo "<tr><th colspan='2'>Vero kotimaan myynnistä verokannoittain</th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi201&vv=$vv&kk=$kk'>201</a> 22% :n vero</td><td align='right'>".sprintf('%.2f',$fi201)."</td></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi202&vv=$vv&kk=$kk'>202</a> 17% :n vero</td><td align='right'>".sprintf('%.2f',$fi202)."</td></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi203&vv=$vv&kk=$kk'>203</a> 8% :n vero</td><td align='right'>".sprintf('%.2f',$fi203)."</td></tr>";
				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi205&vv=$vv&kk=$kk'>205</a> Vero tavaraostoista muista EU-maista</td><td align='right'>".sprintf('%.2f',$fi205)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi206&vv=$vv&kk=$kk'>206</a> Kohdekuukauden vähennettävä vero</td><td align='right'>".sprintf('%.2f',$fi206)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi207&vv=$vv&kk=$kk'>207</a> Edellisen kuukauden negatiivinen vero</td><td align='right'>".sprintf('%.2f',$fi207)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>208 Maksettava vero(+)/Seuraavalle kuukaudelle siirrettävä negatiivinen vero (-)</td><td align='right'>".sprintf('%.2f',$fi208)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi209&vv=$vv&kk=$kk'>209</a> Veroton liikevaihto</td><td align='right'>".sprintf('%.2f',$fi209)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi210&vv=$vv&kk=$kk'>210</a> Tavaran myynti muihin EU-maihin </td><td align='right'>".sprintf('%.2f',$fi210)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=erittele&ryhma=fi205&vv=$vv&kk=$kk'>211</a> Tavaraostot muista EU-maista</td><td align='right'>".sprintf('%.2f',$fi211)."</td></tr>";
			}
			else {
				echo "<tr><th colspan='2'>Vero kotimaan myynnistä verokannoittain</th></tr>";
				echo "<tr><td>201</a> 22% :n vero</td><td align='right'>".sprintf('%.2f',$fi201)."</td></tr>";
				echo "<tr><td>202 17% :n vero</td><td align='right'>".sprintf('%.2f',$fi202)."</td></tr>";
				echo "<tr><td>203 8% :n vero</td><td align='right'>".sprintf('%.2f',$fi203)."</td></tr>";
				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>205 Vero tavaraostoista muista EU-maista</td><td align='right'>".sprintf('%.2f',$fi205)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>206 Kohdekuukauden vähennettävä vero</td><td align='right'>".sprintf('%.2f',$fi206)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>207 Edellisen kuukauden negatiivinen vero</td><td align='right'>".sprintf('%.2f',$fi207)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>208 Maksettava vero(+)/Seuraavalle kuukaudelle siirrettävä negatiivinen vero (-)</td><td align='right'>".sprintf('%.2f',$fi208)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>209 Veroton liikevaihto</td><td align='right'>".sprintf('%.2f',$fi209)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>210 Tavaran myynti muihin EU-maihin </td><td align='right'>".sprintf('%.2f',$fi210)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>211 Tavaraostot muista EU-maista</td><td align='right'>".sprintf('%.2f',$fi211)."</td></tr>";
			}
			echo "</table><br>";

			$query = "	SELECT sum(tiliointi.summa) vero
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tilino = '$yhtiorow[alv]'
						AND tiliointi.tapvm >= '$startmonth'
						AND tiliointi.tapvm <= '$endmonth'";
			$verores = mysql_query($query) or pupe_error($query);
			$verorow = mysql_fetch_array ($verores);

			echo "<table>";
			echo "<tr><td>Tili $yhtiorow[alv] yhteensä</td><td align='right'>".sprintf('%.2f',$verorow['vero'])."</td></tr>";
			echo "<tr><td>Maksettava alv</td><td align='right'>".sprintf('%.2f',$fi208)."</td></tr>";
			echo "<tr><td>Erotus</td><td align='right'>".sprintf('%.2f',$verorow['vero'] - $fi208)."</td></tr>";
			echo "</table><br>";

			if (basename($_SERVER["SCRIPT_FILENAME"]) != 'alv_laskelma.php') {

				$ilmoituskausi = substr($startmonth,0,4).substr($startmonth,5,2);
				$file  = "000:VSRALVKK\n";
				$file .= "100:".date("dmY")."\n";
				$file .= "105:E03\n";
				$file .= "010:$uytunnus\n";
				$file .= "052:$ilmoituskausi\n";
				$file .= "098:1\n";
				$file .= "201:".round($fi201*100,0)."\n";
				$file .= "202:".round($fi202*100,0)."\n";
				$file .= "203:".round($fi203*100,0)."\n";
				$file .= "205:".round($fi205*100,0)."\n";
				$file .= "206:".round($fi206*100,0)."\n";
				$file .= "207:".round($fi207*100,0)."\n";
				$file .= "208:".round($fi208*100,0)."\n";
				$file .= "209:".round($fi209*100,0)."\n";
				$file .= "210:".round($fi210*100,0)."\n";
				$file .= "211:".round($fi211*100,0)."\n";
				$file .= "999:1\n";

				$filenimi = "VSRALVKK-$kukarow[yhtio]-".date("dmy-His").".txt";
				file_put_contents("dataout/".$filenimi, $file);

				echo "	<form method='post'>
							<input type='hidden' name='tee' value='lataa_tiedosto'>
							<input type='hidden' name='lataa_tiedosto' value='1'>
							<input type='hidden' name='kaunisnimi' value='".t("arvonlisaveroilmoitus")."-$ilmoituskausi.txt'>
							<input type='hidden' name='filenimi' value='$filenimi'>
							<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'>
						</form><br><br>";
			}
		}

		// tehdään käyttöliittymä, näytetään aina
		echo "<form method='post'><input type='hidden' name='tee' value ='VSRALVKK'>";
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

		echo "<td class='back' style='text-align:bottom;'><input type = 'submit' value = '".t("Näytä")."'></td>";
		echo "</tr>";

		echo "</table>";

		echo "</form><br>";
	}

	if (basename($_SERVER["SCRIPT_FILENAME"]) == 'alv_laskelma.php') {
		require("../inc/parametrit.inc");
		if ($tee=='erittele') {
			if (($ryhma == 'fi201') or ($ryhma == 'fi202') or ($ryhma == 'fi203'))
				$taso = 'fi200';
			else
				$taso = $ryhma;

			$query = "SELECT group_concat(tilino) tilit
				FROM tili
				WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%$taso'";

			$tilires = mysql_query($query) or pupe_error($query);
			$tilirow = mysql_fetch_array($tilires);
			$tili= $tilirow['tilit'];

			$alvv=$vv;
			$alvk=$kk;
			$alvp=0;
			echo "<table>";
			echo "<tr>";

			$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
			$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

			if (($ryhma == 'fi201') or ($ryhma == 'fi202') or ($ryhma == 'fi203'))
				$taso = 'fi200';
			else
				$taso = $ryhma;
				
			$tiliointilisa = '';
			$query = "SELECT group_concat(tilino) tilit
				FROM tili
				WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%$taso%'";

			$tilires = mysql_query($query) or pupe_error($query);
			$tilirow = mysql_fetch_array($tilires);
			if ($tilirow['tilit'] != '') {
				$tiliointilisa= "and tiliointi.tilino in (".$tilirow['tilit'].")";
				switch ($ryhma) {
					case 'fi201' :
						$tiliointilisa .= " and tiliointi.vero = '22' ";
						break;
					case 'fi202' :
						$tiliointilisa .= " and tiliointi.vero = '17' ";
						break;
					case 'fi203' :
						$tiliointilisa .= " and tiliointi.vero = '8' ";
						break;
				}
			}
			if ($ryhma == 'fi206') $tiliointilisa .= " and tiliointi.vero > 0 ";

			echo "<br><font class='head'>".t("Arvonlisäveroerittely kaudelta")." $alvv-$alvk</font><hr>";

			$query = "	SELECT if(lasku.maa = '', '$yhtiorow[maa]', lasku.maa) maa,
						if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
						tiliointi.vero,
						tiliointi.tilino,
						tili.nimi,
						sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) bruttosumma,
						sum(round(tiliointi.summa * if ('$ryhma'='fi205', 0.22, tiliointi.vero / 100), 2)) verot,
						sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + vero / 100), 2)) bruttosumma_valuutassa,
						sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * vero / 100, 2)) verot_valuutassa,
						count(*) kpl
						FROM tiliointi
						LEFT JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						$tiliointilisa
						GROUP BY maa, valuutta, vero, tilino, nimi
						ORDER BY maa, valuutta, vero, tilino, nimi";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			echo "<th valign='top'>" . t("Maa") . "</th>";
			echo "<th valign='top'>" . t("Val") . "</th>";
			echo "<th valign='top'>" . t("Vero") . "</th>";
			echo "<th valign='top'>" . t("Tili") . "</th>";
			echo "<th valign='top'>" . t("Nimi") . "</th>";
			echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
			echo "<th valign='top'>" . t("Verot") . "</th>";
			echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Kpl") . "</th>";
			echo "</tr>";

			$verosum = 0.0;
			$kplsum  = 0;
			$verotot = 0.0;
			$kpltot  = 0;
			$kantasum = 0.0;
			$kantatot = 0.0;

			while ($trow = mysql_fetch_array ($result)) {

				if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["maa"])) { // Vaihtuiko verokanta?
					echo "<tr>
							<td colspan='5' align='right'>".t("Yhteensä").":</td>
							<td align = 'right'>".sprintf('%.2f', $kantasum)."</td>
							<td align = 'right'>".sprintf('%.2f', $verosum)."</td>
							<td colspan='2'></td>
							<td align = 'right'>$kplsum</td></tr>";

					$verosum 	= 0.0;
					$kplsum 	= 0;
					$kantasum	= 0.0;
				}

				echo "<tr>";
				echo "<td valign='top'>$trow[maa]</td>";
				echo "<td valign='top'>$trow[valuutta]</td>";
				echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
				echo "<td valign='top'>$trow[tilino]</td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[bruttosumma]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[verot]</td>";

				if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
					echo "<td valign='top' align='right' nowrap>$trow[bruttosumma_valuutassa]</td>";
					echo "<td valign='top' align='right' nowrap>$trow[verot_valuutassa]</td>";
				}
				else {
					echo "<td valign='top' align='right'></td>";
					echo "<td valign='top' align='right'></td>";
				}

				echo "<td valign='top' align='right' nowrap>$trow[kpl]</td>";
				echo "</tr>";

				$verosum += $trow['verot'];
				$kplsum  += $trow['kpl'];
				$verotot += $trow['verot'];
				$kpltot  += $trow['kpl'];
				$kantasum += $trow['bruttosumma'];
				$kantatot += $trow['bruttosumma'];
				$edvero = $trow["vero"];
				$edmaa 	= $trow["maa"];
			}
			echo "<tr><td colspan='5' align='right'>".t("Yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $kantasum)."</td><td align = 'right'>".sprintf('%.2f', $verosum)."</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";

			if ($ryhma=='fi206') {
				$query = "SELECT group_concat(tilino) tilit
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%fi205%'";
				$tilires = mysql_query($query) or pupe_error($query);
				$tilirow = mysql_fetch_array($tilires);
				$vero = 0.0;
				if ($tilirow['tilit']!='') {
					$query = "SELECT sum(round(summa * 0.22, 2)) veronmaara
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							AND korjattu = ''
							AND tilino in ($tilirow[tilit])
							AND tapvm >= '$alkupvm'
							AND tapvm <= '$loppupvm'";
					$verores = mysql_query($query) or pupe_error($query);

					while ($verorow = mysql_fetch_array ($verores)) {
						$vero += $verorow['veronmaara'];
					}
				echo "<tr><td colspan='5' align='right'>".t("Vero tavaraostoista muista EU-maista").":</td><td></td><td align = 'right'>".sprintf('%.2f', $vero)."</td><td colspan='2'></td><td></td></tr>";
				$verotot+=$vero;
				}
			}
			echo "<tr><td colspan='5' align='right'>".t("Verokannat yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $kantatot)."</td><td align = 'right'>".sprintf('%.2f', $verotot)."</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";

			echo "</table><br>";
		}
		
		alvlaskelma($kk, $vv);

		require("inc/footer.inc");
	}

?>
