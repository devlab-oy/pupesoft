<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "viranomaisilmoitukset.php") === FALSE) {
		require("../inc/parametrit.inc");
	}

	if (!function_exists("alv_laskelma")) {
		function alvlaskelma ($kk,$vv) {
			global $yhtiorow, $kukarow, $startmonth, $endmonth;

			if (function_exists("laskeveroja")) die;

			function laskeveroja ($taso, $tulos) {
				global $yhtiorow, $kukarow, $startmonth, $endmonth;

				if ($tulos == '22' or $tulos == 'veronmaara' or $tulos == 'summa') {

					if ($taso == 'fi307') {
						$vainsuomi = "JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.maa in ('FI', '')";
					}
					else {
						$vainsuomi = '';
					}

					if ($taso == 'fi309') {
						$_309lisa 	 = " or alv_taso like '%fi300%' ";
						$vainveroton = " and tiliointi.vero = 0 ";
					}
					else {
						$_309lisa	 = "";
						$vainveroton = '';
					}

					$tuotetyyppilisa = '';

					if ($taso == 'fi312') {
						$tuotetyyppilisa = " AND tuote.tuotetyyppi = 'K' ";
						$taso = 'fi311';
					}
					elseif ($taso == 'fi311') {
						$tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
					}

					$query = "	SELECT ifnull(group_concat(if(alv_taso like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit300,
								ifnull(group_concat(if(alv_taso not like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]'
								and (alv_taso like '%$taso%' $_309lisa)";
					$tilires = mysql_query($query) or pupe_error($query);
					$tilirow = mysql_fetch_array($tilires);

					$vero = 0.0;

					if ($tilirow['tilit300'] != '' or $tilirow['tilitMUU'] != '') {

						if ($tuotetyyppilisa != '') {
							$query = "	SELECT round(sum(rivihinta),2) summa
										FROM lasku USE INDEX (yhtio_tila_tapvm)
										JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
										JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.tila = 'U'
										and lasku.tapvm >= '$startmonth'
										and lasku.tapvm <= '$endmonth'
										and lasku.vienti = 'E'";
						}
						else {
							$query = "	SELECT sum(round(tiliointi.summa * if('$tulos'='22', 22, vero) / 100, 2)) veronmaara,
										sum(tiliointi.summa) summa,
							 			count(*) kpl
										FROM tiliointi
										$vainsuomi
										WHERE tiliointi.yhtio = '$kukarow[yhtio]'
										AND korjattu = ''
										AND (";

							if ($tilirow["tilit300"] != "") $query .= "	(tilino in ($tilirow[tilit300]) $vainveroton)";
							if ($tilirow["tilit300"] != "" and $tilirow["tilitMUU"] != "") $query .= " or ";
							if ($tilirow["tilitMUU"] != "") $query .= "	 tilino in ($tilirow[tilitMUU])";

							$query .= "	)
										AND tiliointi.tapvm >= '$startmonth'
										AND tiliointi.tapvm <= '$endmonth'";
						}

						$verores = mysql_query($query) or pupe_error($query);

						while ($verorow = mysql_fetch_array ($verores)) {
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

			if (isset($kk) and $kk != '') {

				$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
				$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

				// 301-303 sääntö fi300
				$query = "	SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
							FROM tili
							WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%fi300%'";
				$tilires = mysql_query($query) or pupe_error($query);

				$fi3xx = array();

				$fi301 = 0.0;
				$fi302 = 0.0;
				$fi303 = 0.0;

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
								GROUP BY vero
								ORDER BY vero DESC";
					$verores = mysql_query($query) or pupe_error($query);

					while ($verorow = mysql_fetch_array ($verores)) {

						switch ($verorow['vero']) {
							case 22 :
								$fi301 += $verorow['veronmaara'];
								break;
							case 12 :
								$fi302 += $verorow['veronmaara'];
								break;
							case 8 :
								$fi303 += $verorow['veronmaara'];
								break;
							default:
								$fi3xx[$verorow['vero']] += $verorow['veronmaara'];
								break;
						}
					}
				}

				/******************************************
				* fi2xx vanharaportti	fi3xx uusiraportti
				*******************************************
				* fi201 = fi301
				* fi202 = fi302
				* fi203 = fi303
				* fi205 = fi305 ja fi306
				* fi206 = fi307
				* fi207 = poistettu uudesta raportista
				* fi209 = fi309 ja fi310
				* fi210 = fi311 ja fi312
				* fi211 = fi313 ja fi314
				******************************************/

				// 305 sääntö fi305
				$fi305 = laskeveroja('fi305','22');

				// 306 sääntö fi306
				$fi306 = laskeveroja('fi306','22');

				// 307 sääntö fi307
				$fi307 = laskeveroja('fi307','veronmaara') + $fi305 + $fi306;

				// 308 laskennallinen
				$fi308 = $fi301 + $fi302 + $fi303 + $fi305 + $fi306 - $fi307;

				// 309 sääntö fi309
				$fi309 = laskeveroja('fi309','summa') * -1;

				// 310 sääntö fi310
				$fi310 = laskeveroja('fi310','summa') * -1;

				// 311 sääntö fi311
				$fi311 = laskeveroja('fi311','summa');

				// 312 sääntö fi312
				$fi312 = laskeveroja('fi312','summa');

				// 313 sääntö fi313
				$fi313 = laskeveroja('fi305','summa');

				// 314 sääntö fi314
				$fi314 = laskeveroja('fi306','summa');

				if (strtoupper($yhtiorow["maa"]) == 'FI') {
					$uytunnus = tulosta_ytunnus($yhtiorow["ytunnus"]);
				}
				else {
					$uytunnus = $yhtiorow["ytunnus"];
				}

				echo "<br><table>";
				echo "<tr><th>",t("Ilmoittava yritys"),"</th><th>$uytunnus</th></tr>";
				echo "<tr><th>",t("Ilmoitettava kausi"),"</th><th>".substr($startmonth,0,4)."/".substr($startmonth,5,2)."</th></tr>";

				echo "<tr><th colspan='2'>",t("Vero kotimaan myynnistä verokannoittain"),"</th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi301&vv=$vv&kk=$kk'>301</a> ",t("22% :n vero"),"</td><td align='right'>".sprintf('%.2f',$fi301)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi302&vv=$vv&kk=$kk'>302</a> ",t("12% :n vero"),"</td><td align='right'>".sprintf('%.2f',$fi302)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi303&vv=$vv&kk=$kk'>303</a> ",t("8% :n vero"),"</td><td align='right'>".sprintf('%.2f',$fi303)."</td></tr>";

				foreach ($fi3xx as $fikey => $fival) {
					echo "<tr><td>xxx ".($fikey * 1).t("% :n vero"),"</td><td align='right'>".sprintf('%.2f',$fival)."</td></tr>";
				}

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi305&vv=$vv&kk=$kk'>305</a> ",t("Vero tavaraostoista muista EU-maista"),"</td><td align='right'>".sprintf('%.2f',$fi305)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi306&vv=$vv&kk=$kk'>306</a> ",t("Vero palveluostoista muista EU-maista"),"</td><td align='right'>".sprintf('%.2f',$fi306)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi307&vv=$vv&kk=$kk'>307</a> ",t("Kohdekuukauden vähennettävä vero"),"</td><td align='right'>".sprintf('%.2f',$fi307)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td>308 ",t("Maksettava vero")," / ",t("Palautukseen oikeuttava vero")," (-)</td><td align='right'>".sprintf('%.2f',$fi308)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi309&vv=$vv&kk=$kk'>309</a> ",t("0-verokannan alainen liikevaihto"),"</td><td align='right'>".sprintf('%.2f',$fi309)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi310&vv=$vv&kk=$kk'>310</a> ",t("Muu arvonlisäveroton liikevaihto"),"</td><td align='right'>".sprintf('%.2f',$fi310)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi311&vv=$vv&kk=$kk'>311</a> ",t("Tavaran myynti muihin EU-maihin"),"</td><td align='right'>".sprintf('%.2f',$fi311)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi312&vv=$vv&kk=$kk'>312</a> ",t("Palveluiden myynti muihin EU-maihin"),"</td><td align='right'>".sprintf('%.2f',$fi312)."</td></tr>";

				echo "<tr><th colspan='2'></th></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi305&vv=$vv&kk=$kk'>313</a> ",t("Tavaraostot muista EU-maista"),"</td><td align='right'>".sprintf('%.2f',$fi313)."</td></tr>";
				echo "<tr><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi306&vv=$vv&kk=$kk'>314</a> ",t("Palveluostot muista EU-maista"),"</td><td align='right'>".sprintf('%.2f',$fi314)."</td></tr>";
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
				echo "<tr><td>",t("Tili")," $yhtiorow[alv] ",t("yhteensä"),"</td><td align='right'>".sprintf('%.2f',$verorow['vero'])."</td></tr>";
				echo "<tr><td>",t("Maksettava alv"),"</td><td align='right'>".sprintf('%.2f',$fi308)."</td></tr>";
				echo "<tr><td>",t("Erotus"),"</td><td align='right'>".sprintf('%.2f',$verorow['vero'] - $fi308)."</td></tr>";
				echo "</table><br>";

				if (strpos($_SERVER['SCRIPT_NAME'], "viranomaisilmoitukset.php") !== FALSE) {
					$ilmoituskausi = str_replace("0", "", substr($startmonth,5,2));
					$ilmoitusvuosi = substr($startmonth,0,4);
					$file  = "000:VSRALVKK\n";
					$file .= "100:\n";
					$file .= "051:\n";
					$file .= "105:\n";
					$file .= "107:\n";
					$file .= "010:$uytunnus\n";
					$file .= "050:K\n";
					$file .= "052:$ilmoituskausi\n";
					$file .= "053:$ilmoitusvuosi\n";
					$file .= "301:".round($fi301*100,0)."\n";
					$file .= "302:".round($fi302*100,0)."\n";
					$file .= "303:".round($fi303*100,0)."\n";
					$file .= "305:".round($fi305*100,0)."\n";
					$file .= "306:".round($fi306*100,0)."\n";
					$file .= "307:".round($fi307*100,0)."\n";
					$file .= "308:".round($fi308*100,0)."\n";
					$file .= "309:".round($fi309*100,0)."\n";
					$file .= "310:".round($fi310*100,0)."\n";
					$file .= "311:".round($fi311*100,0)."\n";
					$file .= "312:".round($fi312*100,0)."\n";
					$file .= "313:".round($fi313*100,0)."\n";
					$file .= "314:".round($fi314*100,0)."\n";
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
			echo "<form method='post'><input type='hidden' name='tee' value ='VSRALVKK_UUSI'>";
			echo "<table>";

			if (!isset($vv)) $vv = date("Y");
			if (!isset($kk)) $kk = date("n");

			echo "<tr>";
			echo "<th>".t("Valitse kausi")."</th>";
			echo "<td>";

			$sel = array();
			$sel[$vv] = "SELECTED";

			$vv_select = date("Y") < 2010 ? 2010 : date("Y");

			echo "<select name='vv'>";
			for ($i = $vv_select; $i >= $vv_select-4; $i--) {
				if ($i < 2010) continue;
				echo "<option value='$i' $sel[$i]>$i</option>";
			}
			echo "</select>";

			$sel = array(1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => '', 10 => '', 11 => '', 12 => '');
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
	}

	if ($tee == 'VSRALVKK_UUSI_erittele') {

		/******************************************
		* fi2xx vanharaportti	fi3xx uusiraportti
		*******************************************
		* fi201 = fi301
		* fi202 = fi302
		* fi203 = fi303
		* fi205 = fi305 ja fi306
		* fi206 = fi307
		* fi207 = poistettu uudesta raportista
		* fi209 = fi309 ja fi310
		* fi210 = fi311 ja fi312
		* fi211 = fi313 ja fi314
		******************************************/

		$alvv			= $vv;
		$alvk			= $kk;
		$alvp			= 0;
		$tiliointilisa 	= '';
		$alkupvm  		= date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm 		= date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));
		$vainveroton 	= "";

		if ($ryhma == 'fi301' or $ryhma == 'fi302' or $ryhma == 'fi303') {
			$taso = 'fi300';
		}
		elseif ($ryhma == 'fi309' or $ryhma == 'fi310') {
			if ($ryhma == 'fi309') {
				$taso = "fi309%' or alv_taso like '%fi300";
			}
			else {
				$taso = "fi310%";
			}
			$vainveroton = " and tiliointi.vero = 0 ";
		}
		else {
			$taso = $ryhma;
		}

		$tuotetyyppilisa = '';

		if ($ryhma == 'fi312') {
			$tuotetyyppilisa = " AND tuote.tuotetyyppi = 'K' ";
			$taso = 'fi311';
		}
		elseif ($ryhma == 'fi311') {
			$tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
			$taso = 'fi311';
		}

		$query = "	SELECT ifnull(group_concat(if(alv_taso like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit300,
					ifnull(group_concat(if(alv_taso not like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%$taso'";
		$tilires = mysql_query($query) or pupe_error($query);
		$tilirow = mysql_fetch_array($tilires);

		if ($tilirow['tilit300'] != '' or $tilirow['tilitMUU'] != '') {

			echo "<table>";
			echo "<tr>";

			switch ($ryhma) {
				case 'fi301' :
					$tiliointilisa .= " and tiliointi.vero = '22' ";
					break;
				case 'fi302' :
					$tiliointilisa .= " and tiliointi.vero = '12' ";
					break;
				case 'fi303' :
					$tiliointilisa .= " and tiliointi.vero = '8' ";
					break;
			}

			if ($ryhma == 'fi307') $tiliointilisa .= " and tiliointi.vero > 0 ";

			echo "<br><font class='head'>".t("Arvonlisäveroerittely kaudelta")." $alvv-$alvk</font><hr>";

			$query = "	SELECT if(lasku.maa = '', '$yhtiorow[maa]', lasku.maa) maa,
						if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
						tiliointi.vero,
						tiliointi.tilino,
						tili.nimi,
						sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) bruttosumma,
						sum(round(tiliointi.summa * if (('$ryhma' = 'fi305' or '$ryhma' = 'fi306'), 0.22, tiliointi.vero / 100), 2)) verot,
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
						AND (";

			if ($tilirow["tilit300"] != "") $query .= "	(tiliointi.tilino in ($tilirow[tilit300]) $vainveroton)";
			if ($tilirow["tilit300"] != "" and $tilirow["tilitMUU"] != "") $query .= " or ";
			if ($tilirow["tilitMUU"] != "") $query .= " tiliointi.tilino in ($tilirow[tilitMUU])";

			$query .= "	)
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
							<td align = 'right'>".abs(sprintf('%.2f', $kantasum))."</td>
							<td align = 'right'>".abs(sprintf('%.2f', $verosum))."</td>
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
				echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk&tili=$trow[tilino]&alv=$trow[vero]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk'>$trow[tilino]</a></td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right' nowrap>",abs($trow['bruttosumma']),"</td>";
				echo "<td valign='top' align='right' nowrap>",abs($trow['verot']),"</td>";

				if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
					echo "<td valign='top' align='right' nowrap>",abs($trow['bruttosumma_valuutassa']),"</td>";
					echo "<td valign='top' align='right' nowrap>",abs($trow['verot_valuutassa']),"</td>";
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
			echo "<tr><td colspan='5' align='right'>".t("Yhteensä").":</td><td align = 'right'>".abs(sprintf('%.2f', $kantasum))."</td><td align = 'right'>".abs(sprintf('%.2f', $verosum))."</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";

			if ($ryhma == 'fi307') {
				$query = "	SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
							FROM tili
							WHERE yhtio = '$kukarow[yhtio]' and alv_taso in ('fi305', 'fi306')";
				$tilires = mysql_query($query) or pupe_error($query);
				$tilirow = mysql_fetch_array($tilires);

				$vero = 0.0;

				if ($tilirow['tilit'] != '') {
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
			echo "<tr><td colspan='5' align='right'>".t("Verokannat yhteensä").":</td><td align = 'right'>".abs(sprintf('%.2f', $kantatot))."</td><td align = 'right'>".abs(sprintf('%.2f', $verotot))."</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";

			echo "</table><br>";

			if ($ryhma == 'fi311' or $ryhma == 'fi312') {

				/*
				if(lasku.maa = '', '$yhtiorow[maa]', lasku.maa) maa,
							if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
							tiliointi.vero,
							tiliointi.tilino,
							tili.nimi,
							sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) bruttosumma,
							sum(round(tiliointi.summa * if (('$ryhma' = 'fi305' or '$ryhma' = 'fi306'), 0.22, tiliointi.vero / 100), 2)) verot,
							sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + vero / 100), 2)) bruttosumma_valuutassa,
							sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * vero / 100, 2)) verot_valuutassa,
							count(*) kpl
							FROM tiliointi
							LEFT JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
				*/

				/*
				GROUP BY maa, valuutta, vero, tilino, nimi
				ORDER BY maa, valuutta, vero, tilino, nimi";
				*/

				$query = "	SELECT if(lasku.maa = '', '$yhtiorow[maa]', lasku.maa) maa,
							if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
							(tilausrivi.alv / 100) vero,
							lasku.tunnus ltunnus,
							sum(round(rivihinta * (1 + tilausrivi.alv / 100), 2)) bruttosumma,
							sum(round(rivihinta * (tilausrivi.alv / 100), 2)) verot,
							sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + tilausrivi.alv / 100), 2)) bruttosumma_valuutassa,
							sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * tilausrivi.alv / 100, 2)) verot_valuutassa
							FROM lasku USE INDEX (yhtio_tila_tapvm)
							JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
							JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'U'
							and lasku.tapvm >= '$alkupvm'
							and lasku.tapvm <= '$loppupvm'
							and lasku.vienti = 'E'
							GROUP BY maa, valuutta, vero
							ORDER BY maa, valuutta, vero";
				$result = mysql_query($query) or pupe_error($query);

				if ($ryhma == 'fi311') {
					echo "<font class='head'>",t("Josta tavaramyyntiä"),":</font><hr>";
				}
				else {
					echo "<font class='head'>",t("Josta palvelumyyntiä"),":</font><hr>";
				}

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
				echo "</tr>";

				$verosum = 0.0;
				$kplsum  = 0;
				$verotot = 0.0;
				$kpltot  = 0;
				$kantasum = 0.0;
				$kantatot = 0.0;
				unset($edvero);
				unset($edmaa);

				while ($trow = mysql_fetch_array ($result)) {
					if ($trow['bruttosumma'] == 0) continue;
					if (isset($edvero) and ($edvero != $trow["vero"] or (isset($edmaa) and $edmaa != $trow["maa"]))) { // Vaihtuiko verokanta?
						echo "<tr>
								<td colspan='5' align='right'>".t("Yhteensä").":</td>
								<td align = 'right'>".sprintf('%.2f', $kantasum)."</td>
								<td align = 'right'>".sprintf('%.2f', $verosum)."</td>
								<td colspan='2'></td>
							  </tr>";

						$verosum 	= 0.0;
						$kplsum 	= 0;
						$kantasum	= 0.0;
					}

					$query = "	SELECT *
								FROM tiliointi
								JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
								WHERE tiliointi.yhtio = '$kukarow[yhtio]'
								AND tiliointi.ltunnus = $trow[ltunnus]
								AND tiliointi.tilino in ($tilirow[tilitMUU])";
					$tili_res = mysql_query($query) or pupe_error($query);
					$tili_row = mysql_fetch_assoc($tili_res);
					$trow['tilino'] = $tili_row['tilino'];
					$trow['nimi'] = $tili_row['nimi'];

					echo "<tr>";
					echo "<td valign='top'>$trow[maa]</td>";
					echo "<td valign='top'>$trow[valuutta]</td>";
					echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
					echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk&tili=$trow[tilino]&alv=$trow[vero]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk'>$trow[tilino]</a></td>";
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

				echo "<tr><td colspan='5' align='right'>".t("Yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $kantasum)."</td><td align = 'right'>".sprintf('%.2f', $verosum)."</td><td colspan='2'></td></tr>";
				echo "<tr><td colspan='5' align='right'>".t("Verokannat yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $kantatot)."</td><td align = 'right'>".sprintf('%.2f', $verotot)."</td><td colspan='2'></td></tr>";
				echo "</table><br/>";

			}
		}
	}

	alvlaskelma($kk, $vv);

	require("inc/footer.inc");

?>
