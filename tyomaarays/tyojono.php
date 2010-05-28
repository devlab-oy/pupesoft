<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Työjono").":</font><hr><br>";
	
	if (!isset($AIKA_ARRAY)) $AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");
	
	//kuukaudet ja päivät ja ajat
	if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=>'Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kesäkuu','Heinäkuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu');
	if (!isset($DAY_ARRAY)) $DAY_ARRAY = array("Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai");

	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	if ($tyostatus_muutos != '' and $tyomaarayksen_tunnus != '') {
		$tyostatus_muutos = mysql_real_escape_string($tyostatus_muutos);
		$tyomaarayksen_tunnus = (int) $tyomaarayksen_tunnus;

		$query = "	UPDATE tyomaarays SET
					tyostatus = '$tyostatus_muutos'
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$tyomaarayksen_tunnus'";
		$update_tyom_res = mysql_query($query) or pupe_error($query);
	}

	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}
	
	if ($yhtiorow["konserni"] != "") {
		echo t("Näytä konsernin työmäräykset").":<input type='checkbox' name='konserni' $chk onclick='submit();'><br><br>";
	}
	
	echo "<table>";	
	echo "<tr>";
			
	if (trim($konserni) != '') {
		echo "<th>".t("Yhtiö")."</th>";
	}
			
	echo "	<th>".t("Työmääräys")."<br>".t("Tilausviite")."</th>
			<th>".t("Ytunnus")."<br>".t("Asiakas")."</th>
			<th>".t("Työaika")."<br>".t("Työn suorittaja")."</th>
			<th>".t("Toimitetaan")."</th>
			<th>".t("Myyjä")."<br>".t("Tyyppi")."</th>
			<th>".t("Työjono")."<br>".t("Työstatus")."</th>
			<th>".t("Muokkaa")."</th>
			</tr>";
			
	echo "<tr>";
	
	if (trim($konserni) != '') {
		echo "<td></td>";
	}
	
	echo "<td valign='top'><input type='text' size='10'  name='myyntitilaus_haku'		value='$myyntitilaus_haku'><br>
							<input type='text' size='10' name='viesti_haku'				value='$viesti_haku'></td>";
	echo "<td valign='top'>	<input type='text' size='10' name='asiakasnumero_haku' 		value='$asiakasnumero_haku'><br>
							<input type='text' size='10' name='asiakasnimi_haku' 		value='$asiakasnimi_haku'></td>";
	echo "<td valign='top'><br>
							<input type='text' size='10' name='suorittaja_haku' 		value='$suorittaja_haku'><br></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'>";

	if (strtolower($toim) != 'tyomaarays_asentaja') {
		$tyojono_result = t_avainsana("TYOM_TYOJONO");

		echo "<select name='tyojono_haku' onchange='submit();'>";
		echo "<option value=''>",t("Oletus"),"</option>";

		while ($tyojono_row = mysql_fetch_assoc($tyojono_result)) {
			$sel = $tyojono_haku == $tyojono_row['selitetark'] ? ' SELECTED' : '';
			echo "<option value='$tyojono_row[selitetark]'$sel>$tyojono_row[selitetark]</option>";
		}

		echo "</select><br><br>";

		$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");

		echo "<select name='tyostatus_haku' onchange='submit();'>";
		echo "<option value=''>",t("Oletus"),"</option>";

		while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
			$sel = $tyostatus_haku == $tyostatus_row['selitetark'] ? ' SELECTED' : '';
			echo "<option value='$tyostatus_row[selitetark]'$sel>$tyostatus_row[selitetark]</option>";
		}

		echo "</select>";
	}
	else {
		echo "<input type='text' size='10' name='tyojono_haku' 			value='$tyojono_haku'><br>";
		echo "<input type='text' size='10' name='tyostatus_haku' 		value='$tyostatus_haku'></td>";
	}

	echo "<td valign='top'></td>";
	echo "<td valign='top' class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";
	
	$lisa   = "";
	$lisa2  = "";

	if ($myyntitilaus_haku != "") {
		$lisa .= " and lasku.tunnus='$myyntitilaus_haku' ";
	}
	
	if ($viesti_haku != "") {
		$lisa .= " and lasku.viesti like '%$viesti_haku%' ";
	}
	
	if ($asiakasnimi_haku != "") {
		$lisa .= " and lasku.nimi like '%$asiakasnimi_haku%' ";
	}

	if ($asiakasnumero_haku != "") {
		$lisa .= " and lasku.ytunnus like '$asiakasnumero_haku%' ";
	}

	if ($tyojono_haku != "") {
		$lisa .= " and a1.selitetark like '$tyojono_haku%' ";
	}
	
	if ($tyostatus_haku != "") {
		$lisa .= " and a2.selitetark like '$tyostatus_haku%' ";
	}
	
	if ($suorittaja_haku != "") {
		$lisa2 .= " HAVING suorittajanimi like '%$suorittaja_haku%' or asekalsuorittajanimi like '%$suorittaja_haku%' ";
	}
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";
		
		while ($row = mysql_fetch_array($result)) {	
			$konsernit .= " '".$row["yhtio"]."' ,";
		}		
		$konsernit = " lasku.yhtio in (".substr($konsernit, 0, -1).") ";			
	}
	else {
		$konsernit = " lasku.yhtio = '$kukarow[yhtio]' ";
	}
	
	// scripti balloonien tekemiseen
	js_popup();	

	$selectlisa = " group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', a4.selitetark_2, '##', kalenteri.tunnus, '##', a4.selitetark)) asennuskalenteri ";

	if (strtolower($toim) == 'tyomaarays_asentaja') {
		$selectlisa = " group_concat(concat(left(kalenteri.pvmalku,10), '##', left(kalenteri.pvmloppu,10), '##', a4.selitetark_2, '##', kalenteri.tunnus, '##', a4.selitetark)) asennuskalenteri ";
	}


	// Myyydyt sarjanumerot joita ei olla ollenkaan ostettu
	$query = "	SELECT 
				lasku.tunnus,
				lasku.viesti,
				lasku.nimi,
				lasku.tila, 
				lasku.alatila,
				lasku.tilaustyyppi,
				lasku.ytunnus,
				lasku.toimaika,
				tyomaarays.komm1,
				tyomaarays.komm2,
				tyomaarays.tyojono,
				tyomaarays.tyostatus,
				kuka.nimi myyja, 
				a1.selite tyojonokoodi, 
				a1.selitetark tyojono, 
				a2.selitetark tyostatus, 
				a2.selitetark_2 tyostatusvari, 
				yhtio.nimi yhtio, 
				yhtio.yhtio yhtioyhtio,
				a3.nimi suorittajanimi,
				group_concat(a4.selitetark) asekalsuorittajanimi,
				$selectlisa
				FROM lasku
				JOIN yhtio ON lasku.yhtio=yhtio.yhtio
				JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				LEFT JOIN avainsana a1 ON a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono
				LEFT JOIN avainsana a2 ON a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus
				LEFT JOIN kuka a3 ON a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja
				LEFT JOIN kalenteri ON kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus
				LEFT JOIN avainsana a4 ON a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka
				WHERE $konsernit
				and lasku.tila in ('A','L','N','S','C')
				and lasku.alatila != 'X'
				$lisa
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19
				$lisa2
				ORDER BY toimaika ";
	$vresult = mysql_query($query) or pupe_error($query);

	$tyomaarays_tunti_yhteensa = array();

	while ($vrow = mysql_fetch_array($vresult)) {	

		$laskutyyppi = $vrow["tila"];
		$alatila	 = $vrow["alatila"];
		
		//tehdään selväkielinen tila/alatila
		require "inc/laskutyyppi.inc";

		if ($vrow['tilaustyyppi'] != '' and $vrow['tila'] != 'C') {
			$laskutyyppi = $vrow['tilaustyyppi'];
			
			require "inc/laskutyyppi.inc";
		}

		echo "<tr>";
		
		if (trim($konserni) != '') {
			echo "<td>$vrow[yhtio]</td>";
		}
		
		if ($vrow["tila"] == "L" or $vrow["tila"] == "N") {
			$toimi = "RIVISYOTTO";
		}
		elseif ($vrow["tila"] == "T") {
			$toimi = "TARJOUS";
		}
		elseif ($vrow["tila"] == "S") {
			$toimi = "SIIRTOTYOMAARAYS";
		}
		elseif ($vrow["tila"] == "A") {
			$toimi = "TYOMAARAYS";
		}
		
		if (trim($vrow["komm1"]) != "") {
			echo "<div id='div_$vrow[tunnus]' class='popup' style='width:500px;'>";
			echo t("Työmääräys"),": $vrow[tunnus]<br><br>".str_replace("\n", "<br>", $vrow["komm1"]."<br>".$vrow["komm2"]);
			echo "</div>";		
			echo "<td valign='top' class='tooltip' id='$vrow[tunnus]'>$vrow[tunnus]</td>";
		}
		else {
			echo "<td valign='top'>$vrow[tunnus]<br>$vrow[viesti]</td>";
		}
		
		echo "<td valign='top'>$vrow[ytunnus]<br>$vrow[nimi]</td>";
		
		
		echo "<td valign='top'>";
		
		if ($vrow["asennuskalenteri"] != "") {
			
			$lopetusx = $lopetus;
			
			if ($lopetusx == "") $lopetusx = "$PHP_SELF////konserni=$konserni//toim=$toim";

			$pvm_array = array();

			foreach(explode(",", $vrow["asennuskalenteri"]) as $asekale) {
				list($a, $l, $s, $t, $s2) = explode("##", $asekale);

				if (strtolower($toim) == 'tyomaarays_asentaja') {

					list($alku_pvm, $alku_klo) = explode(" ", $a);
					list($loppu_pvm, $loppu_klo) = explode(" ", $l);

					$tuntimaara = 0;

					if (trim($alku_pvm) != trim($loppu_pvm)) {

						if ($kukarow['kuka'] == $s2 and !in_array($alku_pvm, $pvm_array[$s])) {
							// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
							date_default_timezone_set('UTC');

							$query = "	SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
										FROM kalenteri
										WHERE yhtio = '$kukarow[yhtio]'
										AND kuka = '$kukarow[kuka]'
										AND kentta02 = '$vrow[tunnus]'
										AND tyyppi = 'kalenteri'
										AND pvmalku like '$alku_pvm%'
										AND pvmloppu != '$loppu_pvm%'";
							$tunti_chk_res = mysql_query($query) or pupe_error($query);

							while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
								if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {

									list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
									list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

									list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

									if ($temp_tunnit != 0 or $temp_minuutit != 0) {
										$temp_minuutit = $temp_minuutit != 0 ? $temp_minuutit / 60 : 0;
										$tuntimaara += ($temp_tunnit+$temp_minuutit);
										$tyomaarays_tunti_yhteensa[$vrow['tunnus']] = $vrow['tunnus'];
									}
								}
							}

							list($url_vuosi, $url_kk, $url_pp) = explode("-", $alku_pvm);

							echo "<a href='".$palvelin2."crm/kalenteri.php?tyomaarays=$vrow[tunnus]&year=$url_vuosi&kuu=$url_kk&paiva=$url_pp&toim=$toim&lopetus=$lopetusx'>";
						}

						if ($tuntimaara != 0) {
							$tuntimaara = "(".str_replace(".",",",$tuntimaara)." h)";
						}
						else {
							$tuntimaara = '';
						}

						if (!in_array($alku_pvm, $pvm_array[$s])) {
							echo tv1dateconv($a, '', 'LYHYT')." $tuntimaara $s";
						
							if ($kukarow['kuka'] == $s2) {
								echo "</a>";
							}

							echo "<br>";
						}

						if ($kukarow['kuka'] == $s2 and !in_array($loppu_pvm, $pvm_array[$s])) {
							$query = "	SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
										FROM kalenteri
										WHERE yhtio = '$kukarow[yhtio]'
										AND kuka = '$kukarow[kuka]'
										AND tyyppi = 'kalenteri'
										AND kentta02 = '$vrow[tunnus]'
										AND pvmalku like '$loppu_pvm%'";
							$tunti_chk_res = mysql_query($query) or pupe_error($query);

							while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
								if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {

									list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
									list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

									list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

									if ($temp_tunnit != 0 or $temp_minuutit != 0) {
										$temp_minuutit = $temp_minuutit != 0 ? $temp_minuutit / 60 : 0;
										$tuntimaara += ($temp_tunnit+$temp_minuutit);
										$tyomaarays_tunti_yhteensa[$vrow['tunnus']] = $vrow['tunnus'];
									}
								}
							}

							list($url_vuosi, $url_kk, $url_pp) = explode("-", $loppu_pvm);
							echo "<a href='".$palvelin2."crm/kalenteri.php?tyomaarays=$vrow[tunnus]&year=$url_vuosi&kuu=$url_kk&paiva=$url_pp&toim=$toim&lopetus=$lopetusx'>";

							$pvm_array[$s][] = $loppu_pvm;
						}

						if ($tuntimaara != 0) {
							$tuntimaara = "(".str_replace(".",",",$tuntimaara)." h)";
						}
						else {
							$tuntimaara = '';
						}

						if (!in_array($alku_pvm, $pvm_array[$s])) {
							echo tv1dateconv($l, '', 'LYHYT')." $tuntimaara";
						}
						else {
							$s = $s2 = '';
						}
					}
					elseif (!in_array($alku_pvm, $pvm_array[$s]) and !in_array($loppu_pvm, $pvm_array[$s])) {

						if ($kukarow['kuka'] == $s2) {
							$query = "	SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
										FROM kalenteri
										WHERE yhtio = '$kukarow[yhtio]'
										AND kuka = '$kukarow[kuka]'
										AND tyyppi = 'kalenteri'
										AND kentta02 = '$vrow[tunnus]'
										AND pvmalku like '$alku_pvm%'
										AND pvmloppu like '$loppu_pvm%'";
							$tunti_chk_res = mysql_query($query) or pupe_error($query);

							while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
								if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {
									list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
									list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

									// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
									date_default_timezone_set('UTC');

									list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

									if ($temp_tunnit != 0 or $temp_minuutit != 0) {
										$temp_minuutit = $temp_minuutit != 0 ? $temp_minuutit / 60 : 0;
										$tuntimaara += ($temp_tunnit+$temp_minuutit);
										$tyomaarays_tunti_yhteensa[$vrow['tunnus']] = $vrow['tunnus'];
									}
								}
							}

							list($url_vuosi, $url_kk, $url_pp) = explode("-", $alku_pvm);
							echo "<a href='".$palvelin2."crm/kalenteri.php?tyomaarays=$vrow[tunnus]&year=$url_vuosi&kuu=$url_kk&paiva=$url_pp&toim=$toim&lopetus=$lopetusx'>";
						}

						if ($tuntimaara != 0) {
							$tuntimaara = "(".str_replace(".",",",$tuntimaara)." h)";
						}
						else {
							$tuntimaara = '';
						}

						echo tv1dateconv($a, '', 'LYHYT')." $tuntimaara";
					}
					else {
						$pvm_array[$s][] = $alku_pvm;
						continue;
					}

					$pvm_array[$s][] = $alku_pvm;
				}
				else {
					$klo_alku = substr($a, 11);
					$klo_loppu = substr($l, 11);

					list($ah, $am) = explode(":", $klo_alku);
					list($lh, $lm) = explode(":", $klo_loppu);

					// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
					date_default_timezone_set('UTC');

					list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

					if ($temp_tunnit != 0 or $temp_minuutit != 0) {
						$temp_minuutit = $temp_minuutit != 0 ? $temp_minuutit / 60 : 0;
						$tyomaarays_tunti_yhteensa[$vrow['tunnus']][$s] += ($temp_tunnit+$temp_minuutit);
					}

					echo "<a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]&tyotunnus=$t&tee=MUOKKAA&lopetus=$lopetusx'>".tv1dateconv($a, 'P')." - ".tv1dateconv($l, 'P');
				}

				echo " $s";
				if ($kukarow['kuka'] == $s2 and strtolower($toim) == 'tyomaarays_asentaja') {
					echo "</a>";
				}

				if ($s != '') echo "<br>";
			}
		}
		
		echo $vrow["suorittajanimi"];
		
		echo "</td>";
				
		if ($vrow["tyojono"] != "" and strtolower($toim) != 'tyomaarays_asentaja') {
			list($ankkuri_pp, $ankkuri_kk, $ankkuri_vv) = explode(".", tv1dateconv($vrow["toimaika"]));
			$ankkuri_pp = (strlen($ankkuri_pp) == 2 and $ankkuri_pp{0} == 0) ? $ankkuri_pp{1} : $ankkuri_pp;
			$ankkuri_kk = (strlen($ankkuri_kk) == 2 and $ankkuri_kk{0} == 0) ? $ankkuri_kk{1} : $ankkuri_kk;

			$ankkuri = "{$ankkuri_pp}_{$ankkuri_kk}_{$ankkuri_vv}";

			echo "<td valign='top'><a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]&toim=$toim&lopetus=$lopetus/SPLIT/$PHP_SELF////konserni=$konserni//toim=$toim#$ankkuri'>".tv1dateconv($vrow["toimaika"])."</a></td>";
		}
		else {
			echo "<td valign='top'>".tv1dateconv($vrow["toimaika"])."</td>";
		}
		
		echo "<td valign='top'>$vrow[myyja]<br>".t("$laskutyyppi")." ".t("$alatila")."</td>";
		
		if ($vrow["tyostatusvari"] != "") {
			$varilisa = "style='background-color: $vrow[tyostatusvari];'";
		}
		else {
			$varilisa = "";
		}
		
		echo "<td valign='top' $varilisa>$vrow[tyojono]<br>";

		if (strtolower($toim) != 'tyomaarays_asentaja') {
			$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");

			if (mysql_num_rows($tyostatus_result) > 0) {
				echo "<form method='post'>";
				echo "<input type='hidden' name='tyomaarayksen_tunnus' value='$vrow[tunnus]'>";
				echo "<input type='hidden' name='konserni' value='$konserni'>";
				echo "<input type='hidden' name='myyntitilaus_haku' value='$myyntitilaus_haku'>";
				echo "<input type='hidden' name='viesti_haku' value='$viesti_haku'>";
				echo "<input type='hidden' name='asiakasnimi_haku' value='$asiakasnimi_haku'>";
				echo "<input type='hidden' name='asiakasnumero_haku' value='$asiakasnumero_haku'>";
				echo "<input type='hidden' name='tyojono_haku' value='$tyojono_haku'>";
				echo "<input type='hidden' name='tyostatus_haku' value='$tyostatus_haku'>";
				echo "<input type='hidden' name='suorittaja_haku' value='$suorittaja_haku'>";
				echo "<input type='hidden' name='tyojono' value='$tyojono'>";

				echo "<select name='tyostatus_muutos' onchange='submit();'>";

				while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
					$sel = $vrow['tyostatus'] == $tyostatus_row['selitetark'] ? ' SELECTED' : '';
					echo "<option value='$tyostatus_row[selite]'$sel>$tyostatus_row[selitetark]</option>";
				}

				echo "</select></form>";
			}
		}
		else {
			echo "$vrow[tyostatus]";
		}

		echo "</td>";

		if ($vrow["yhtioyhtio"] != $kukarow["yhtio"]) {		
			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?user=$kukarow[kuka]&pass=$kukarow[salasana]&yhtio=$vrow[yhtioyhtio]&toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]'>".t("Muokkaa")."</a></td>";
		}
		else {

			if (strtolower($toim) == 'tyomaarays_asentaja' and $toimi != 'TYOMAARAYS_ASENTAJA') $toimi = 'TYOMAARAYS_ASENTAJA';

			if ($vrow['tila'] == 'C') {
				$toimi = 'REKLAMAATIO';
			}
			elseif ($vrow['tila'] == 'L' and $vrow['tilaustyyppi'] == 'A') {
				$toimi = $toimi != 'TYOMAARAYS_ASENTAJA' ? 'TYOMAARAYS' : 'TYOMAARAYS_ASENTAJA';
			}

			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]&tyojono=$tyojono'>".t("Muokkaa")."</a></td>";
		}
				
		echo "</tr>";
	}

	echo "</table><br><br>";

	if (count($tyomaarays_tunti_yhteensa) > 0) {
		if (strtolower($toim) != 'tyomaarays_asentaja') {
			echo "<table><tr><td class='back'>";
			echo "<table><tr><th colspan='2'>",t("Työmääräyksien tuntiyhteenveto"),"</th></tr>";

			$total_yht = 0;

			foreach ($tyomaarays_tunti_yhteensa as $tyom_id => $tyom_array) {
				$yht = 0;

				echo "<tr><td>$tyom_id</td><td><table width='100%'>";

				foreach ($tyom_array as $tyom_asentaja => $tyom_tunnit) {
					echo "<tr><td>$tyom_asentaja</td><td class='ok' align='right'><strong>$tyom_tunnit</strong></td></tr>";
					$yht += $tyom_tunnit;
				}

				echo "</td></table></td></tr>";
				echo "<tr><td class='tumma'>",t("Yhteensä")," ",t("Työmääräys")," $tyom_id</td><td class='tumma'>$yht</td></tr>";

				$total_yht += $yht;
			}

			echo "<tr><th>",t("Yhteensä"),"</th><th>$total_yht</th></tr>";
			echo "</table></td>";
		}

		echo "<td class='back'><table>";
		echo "<tr><th colspan='2'>",t("Työmääräyksien asentajien tuntiyhteenveto"),"</th></tr>";

		$total_yht = 0;

		foreach ($tyomaarays_tunti_yhteensa as $tyom_id => $tyom_array) {

			$query = "	SELECT kuka, right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
						FROM kalenteri
						WHERE yhtio = '$kukarow[yhtio]'
						AND kentta02 = '$tyom_id'
						AND tyyppi = 'kalenteri'";
			$tunti_chk_res = mysql_query($query) or pupe_error($query);

			$tyom_kuka = array();

			if (mysql_num_rows($tunti_chk_res) > 0) {

				while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
					list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
					list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

					// to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
					date_default_timezone_set('UTC');

					list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

					if ($temp_tunnit != 0 or $temp_minuutit != 0) {
						$temp_minuutit = $temp_minuutit != 0 ? $temp_minuutit / 60 : 0;

						$tunti_chk_row['kuka'] = t_avainsana("TYOM_TYOLINJA", '', " and avainsana.selitetark = '$tunti_chk_row[kuka]' ", "'$kukarow[yhtio]'", '', 'selitetark_2');
						$tyom_kuka[$tunti_chk_row['kuka']] += ($temp_tunnit+$temp_minuutit);
					}
				}
			}

			if (count($tyom_kuka) > 0) {
				echo "<tr><td>$tyom_id</td><td><table width='100%'>";

				$yht = 0;

				foreach ($tyom_kuka as $t_kuka => $t_tunnit) {
					echo "<tr><td>$t_kuka</td><td class='ok' align='right'><strong>",str_replace(".",",",$t_tunnit),"</strong></td></tr>";
					$yht += $t_tunnit;
				}

				echo "</td></table></td></tr>";
				echo "<tr><td class='tumma'>",t("Yhteensä")," ",t("Työmääräys")," $tyom_id</td><td class='tumma'>",str_replace(".",",",$yht),"</td></tr>";

				$total_yht += $yht;
			}
		}

		echo "<tr><th>",t("Yhteensä"),"</th><th>",str_replace(".",",",$total_yht),"</th></tr>";
		echo "</table></td></tr></table>";
	}

	echo "<br><br>";

	require ("../inc/footer.inc");

?>