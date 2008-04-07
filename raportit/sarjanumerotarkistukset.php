<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Sarjanumeromyynnin tarkistusta").":</font><hr><br>";
	
	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		
		require ("naytatilaus.inc");
		
		echo "<br><br><br>";
	}
	
	
	if ($tee == "OSTOVAIHYVITYS") {
		$query  = "	SELECT *
					FROM tilausrivin_lisatiedot
					WHERE yhtio			 = '$kukarow[yhtio]'
					and tilausrivitunnus = '$rivitunnus'";
		$lisatied_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($lisatied_res) > 0) {
			$lisatied_row = mysql_fetch_array($lisatied_res);

			$query = "	UPDATE tilausrivin_lisatiedot
						SET osto_vai_hyvitys 	= '$osto_vai_hyvitys',
						muutospvm				= now(),
						muuttaja				= '$kukarow[kuka]'
						WHERE yhtio	= '$kukarow[yhtio]'
						and tilausrivitunnus = '$rivitunnus'
						and tunnus 	= '$lisatied_row[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "	INSERT INTO tilausrivin_lisatiedot
						SET yhtio 		 = '$kukarow[yhtio]',
						tilausrivitunnus = '$rivitunnus',
						osto_vai_hyvitys = '$osto_vai_hyvitys',
						luontiaika		 = now(),
						laatija 		 = '$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tee 		= "";
		$rivitunnus = "";
	}
	
	if ($tee == "") {
		if ($jarjestys_1 != "") $chk1 = "CHECKED";
		if ($jarjestys_2 != "") $chk2 = "CHECKED";
		if ($jarjestys_3 != "") $chk3 = "CHECKED";
		if ($jarjestys_4 != "") $chk4 = "CHECKED";
		if ($jarjestys_5 != "") $chk5 = "CHECKED";
		if ($jarjestys_6 != "") $chk6 = "CHECKED";
		
		if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($kkl)) $kkl = date("m");
		if (!isset($vvl)) $vvl = date("Y");
		if (!isset($ppl)) $ppl = date("d");

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<table>";
		echo "<tr>
			<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td valign='top'><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td valign='top'><input type='text' name='kka' value='$kka' size='3'></td>
			<td valign='top'><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>\n
			<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td valign='top'><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td valign='top'><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td valign='top'><input type='text' name='vvl' value='$vvl' size='5'></td>
			</tr>
			<tr>
			<th>".t("Myyydyt rivit joita ei olla ostettu")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_1' $chk1></td>
			</tr>
			<tr>
			<th>".t("Myyydyt rivit")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_2' $chk2></td>
			</tr>
			<tr>
			<th>".t("Myydyt rivit ilman sarjanumeroa")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_3' $chk3></td>
			</tr>
			<tr>
			<th>".t("Myyntipuolelta ostetut ja hyvitetyt rivit")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_4' $chk4></td>
			</tr>
			<tr>
			<th>".t("Myyntipuolelta ostetut ja hyvitetyt rivit ilman sarjanumeroa")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_5' $chk5></td>
			</tr>
			<tr>
			<th>".t("Ostopuolelta ostetut sarjanumerot")."</th>
			<td colspan='3'><input type='checkbox' name='jarjestys_6' $chk6></td>
			</tr>";
		echo "</table><br>";

		echo "<table>";
		echo "<tr><th colspan='7'>Hakukentät</th></tr>";
		echo "<tr><th>Myyntitilaus<br></th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta<br>Ostohinta</th><th>Myyntikate<br>Ostokate</th><th>O/H</th><th>Sarjanumero</th></tr>";

		echo "<tr>";
		echo "<td valign='top'><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'><br><input type='text' size='10' name='ostotilaus_haku' 			value='$ostotilaus_haku'></td>";
		echo "<td valign='top'><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
		echo "<td valign='top'><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
		echo "<td class='back'><input type='submit' value='Hae'></td>";
		echo "</tr>";
		echo "<tr><td class='back'><br></td></tr>";
		echo "</form>";
	}
	
	if (($jarjestys_1 != '' or $jarjestys_2 != '' or $jarjestys_3 != '' or $jarjestys_4 != '' or $jarjestys_5 != '' or $jarjestys_6 != '') and $tee == "") {
		
		function superlistaus ($tyyppi, $lisa1, $lisa2, $lisa3, $ostov) {
			global $PHP_SELF, $kukarow, $yhtiorow, $myyntitilaus_haku, $tuoteno_haku, $nimitys_haku, $ostotilaus_haku, $sarjanumero_haku, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $jarjestys_1, $jarjestys_2, $jarjestys_3, $jarjestys_4, $jarjestys_5, $jarjestys_6;
			
			echo "<tr><th>Myyntitilaus<br>Ostotilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta<br>Ostohinta</th><th>Myyntikate<br>Ostokate</th><th>O/H</th><th>Sarjanumero</th></tr>";

			if ($tyyppi == "myynti") {
				$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
							round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, 
							round(tilausrivi.kate/tilausrivi.kpl,2) kate, 
							round(ostorivi.kate/abs(ostorivi.kpl),2) osto_kate,
							ostorivi.tunnus ostotunnus, 
							if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, 
							sarjanumeroseuranta.sarjanumero sarjanumero, sarjanumeroseuranta.tunnus sarjatunnus,
							tilausrivi.kpl, myyntilasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys, 
							if(sarjanumeroseuranta.kaytetty='' or sarjanumeroseuranta.kaytetty is null, 'Uusi', 'Käytetty') kaytetty,
							tuote.kehahin,
							(select count(distinct sarjanumero) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.myyntirivitunnus=tilausrivi.tunnus) css
							FROM tilausrivi
							JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta='S'
							LEFT JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tuoteno=sarjanumeroseuranta.tuoteno and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sarjanumeroseuranta.yhtio and ostorivi.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku myyntilasku ON myyntilasku.yhtio=tilausrivi.yhtio and myyntilasku.tunnus=tilausrivi.otunnus
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							$lisa1
							$lisa2
							and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
							$lisa3
							order by tilausrivi.tunnus";
			}
			else {			
				$query = "	SELECT myyntirivi.otunnus, myyntirivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
							round(myyntirivi.rivihinta/myyntirivi.kpl,2) rivihinta, 
							round(myyntirivi.kate/myyntirivi.kpl,2) kate, 
							round(tilausrivi.kate/abs(tilausrivi.kpl),2) osto_kate, 
							tilausrivi.tunnus ostotunnus, 
							if(tilausrivi.tyyppi='O', tilausrivi.uusiotunnus, tilausrivi.otunnus) ostotilaus, 
							sarjanumeroseuranta.sarjanumero sarjanumero, sarjanumeroseuranta.tunnus sarjatunnus,
							tilausrivi.kpl, ostolasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys, 
							if(sarjanumeroseuranta.kaytetty='' or sarjanumeroseuranta.kaytetty is null, 'Uusi', 'Käytetty') kaytetty,
							tuote.kehahin,
							(select count(distinct sarjanumero) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.ostorivitunnus=tilausrivi.tunnus) css
							FROM tilausrivi
							JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta='S'
							LEFT JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tuoteno=sarjanumeroseuranta.tuoteno and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN tilausrivi myyntirivi ON myyntirivi.yhtio=sarjanumeroseuranta.yhtio and myyntirivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN lasku ostolasku ON ostolasku.yhtio=tilausrivi.yhtio and ostolasku.tunnus=tilausrivi.otunnus
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							$lisa1
							$lisa2
							and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
							$lisa3
							order by tilausrivi.tunnus";			
			}
			
			$vresult = mysql_query($query) or pupe_error($query);
				
			while ($vrow = mysql_fetch_array($vresult)) {	
				
				// Sarjanumeron ostohinta
				$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $vrow["myyntitunnus"]);
							
				echo "<tr>
						<td valign='top'><a name='$vrow[ostotunnus]'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a><br><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td>
						<td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td>
						<td valign='top' align='right'>$vrow[rivihinta]<br>".sprintf('%.2f', $ostohinta)."</td>";
				
																														
				
				if ($vrow["myyntitunnus"] > 0 and abs(round($vrow["rivihinta"]-$ostohinta,2) - $vrow["kate"]) > 0.01) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$ostohinta)."</font><br>";
				}
				/*
				elseif ($vrow["myyntitunnus"] > 0 and abs(round($vrow["rivihinta"]-$ostohinta,2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {			
					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font><br>";
					}
					else {
						echo "<td valign='top' align='right' nowrap>$vrow[kate]<br>";
					}
				}
				*/
				elseif ($vrow["myyntitunnus"] > 0 and $vrow["kate"] < 0) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate]</font><br>";
				}
				elseif($vrow["myyntitunnus"] > 0) {
					echo "<td valign='top' align='right' nowrap>$vrow[kate]<br>";
				}
				else {
					echo "<td valign='top' align='right' nowrap><br>";
				} 
				
				if ($vrow["osto_kate"] != 0) {
					echo "<font style='color: red;'>$vrow[osto_kate]</font></td>";
				}
				else {
					echo "</td>";
				}
				
				if ($ostov == "JOO") {
					$sel1 = $sel2 = "";
				
					if ($vrow["osto_vai_hyvitys"] == "O") {
					    $sel2 = "SELECTED";
					}
					else {
					    $sel1 = "SELECTED";
					}
				
					echo "<td><form action='$PHP_SELF?tee=OSTOVAIHYVITYS&rivitunnus=$vrow[ostotunnus]&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&jarjestys_1=$jarjestys_1&jarjestys_2=$jarjestys_2&jarjestys_3=$jarjestys_3&jarjestys_4=$jarjestys_4&jarjestys_5=$jarjestys_5&jarjestys_6=$jarjestys_6#$vrow[ostotunnus]' method='post'>
						<input type='hidden' name='myyntitilaus_haku'        value='$myyntitilaus_haku'>
						<input type='hidden' name='tuoteno_haku'             value='$tuoteno_haku'>
						<input type='hidden' name='nimitys_haku'             value='$nimitys_haku'>
						<input type='hidden' name='ostotilaus_haku'         value='$ostotilaus_haku'>
						<input type='hidden' name='sarjanumero_haku'         value='$sarjanumero_haku'>
						<select name='osto_vai_hyvitys' onchange='submit();'>
						<option value=''  $sel1>".("Hyvitys")."</option>
						<option value='O' $sel2>".("Osto")."</option>
						</select>
						</form></td>";
				}
				else {
					echo "<td></td>";
				}
								
				if ($vrow["sarjatunnus"] != "" and $vrow["css"] == abs($vrow["kpl"])) {
					echo "<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>$vrow[sarjanumero]</a><br>$vrow[kaytetty]</td></tr>";
				}
				else {
					if ($vrow["kpl"] > 0 and $tyyppi == "myynti") {
						$lisays = "myyntirivitunnus=$vrow[myyntitunnus]";
					}
					else {
						$lisays = "ostorivitunnus=$vrow[ostotunnus]";
					}
					echo "<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno=$vrow[tuoteno]&$lisays&from=KORJAA&lopetus=ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//jarjestys_1=$jarjestys_1//jarjestys_2=$jarjestys_2//jarjestys_3=$jarjestys_3//jarjestys_4=$jarjestys_4//jarjestys_5=$jarjestys_5//jarjestys_6=$jarjestys_6'>Sarjanumero</a><br>&nbsp;</tr>";
				}
			}
	
			echo "<tr><td class='back'><br><br></td></tr>";
		}
		
		
		$lisa2  = "";

		if ($ostotilaus_haku != "") {
			$lisa2 .= " and ostorivi.otunnus='$ostotilaus_haku' ";
		}

		if ($myyntitilaus_haku != "") {
			$lisa2 .= " and tilausrivi.otunnus='$myyntitilaus_haku' ";
		}
		
		if ($tuoteno_haku != "") {
			$lisa2 .= " and tilausrivi.tuoteno like '%$tuoteno_haku%' ";
		}

		if ($sarjanumero_haku != "") {
			$lisa2 .= " and sarjanumeroseuranta.sarjanumero like '%$sarjanumero_haku%' ";
		}

		if ($nimitys_haku != "") {
			$lisa2 .= " and tilausrivi.nimitys like '%$nimitys_haku%' ";
		}	
		
		if ($jarjestys_1 != "") {
			echo "<tr><th colspan='7'>Myyydyt sarjanumerot joita ei olla ollenkaan ostettu</th></tr>";
									
			$lisa1 = "and tilausrivi.tyyppi = 'L' and tilausrivi.kpl > 0";
			$lisa3 = "having ostotunnus is null";
			
			superlistaus ("myynti", $lisa1, $lisa2, $lisa3, "");	
		}
	
		if ($jarjestys_2 != "") {
			echo "<tr><th colspan='7'>Myyydyt sarjanumerot</th></tr>";
	
			$lisa1 = "and tilausrivi.tyyppi = 'L' and tilausrivi.kpl > 0";
			$lisa3 = "having sarjanumero is not null and ostotunnus is not null and kpl=css";
			
			superlistaus ("myynti", $lisa1, $lisa2, $lisa3, "");
		}
		
		if ($jarjestys_3 != "") {
			echo "<tr><th colspan='7'>Myydyt sarjanumerot ilman sarjanumeroa</th></tr>";
			
			$lisa1 = "and tilausrivi.tyyppi = 'L' and tilausrivi.kpl > 0";
			$lisa3 = "having sarjanumero is null or kpl!=css";
			
			superlistaus ("myynti", $lisa1, $lisa2, $lisa3, "");
		}

		if ($jarjestys_4 != "") {
			echo "<tr><th colspan='7'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot</th></tr>";
			
			$lisa1 = "and tilausrivi.tyyppi = 'L' and tilausrivi.kpl < 0";
			$lisa3 = "having sarjanumero is not null";
			
			superlistaus ("osto", $lisa1, $lisa2, $lisa3, "JOO");
		}

		if ($jarjestys_5 != "") {
			echo "<tr><th colspan='7'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot ilman sarjanumeroa</th></tr>";
			
			$lisa1 = "and tilausrivi.tyyppi = 'L' and tilausrivi.kpl < 0";
			$lisa3 = "having sarjanumero is null or abs(kpl)!=css";
			
			superlistaus ("osto", $lisa1, $lisa2, $lisa3, "JOO");
		}

		if ($jarjestys_6 != "") {
			echo "<tr><th colspan='7'>Ostopuolelta ostetut sarjanumrot</th></tr>";
			
			$lisa1 = "and tilausrivi.tyyppi = 'O' and tilausrivi.kpl > 0";
			$lisa3 = "";
			
			superlistaus ("osto", $lisa1, $lisa2, $lisa3, "");
		}
	}
	
	echo "</table><br><br>";
	
	require ("../inc/footer.inc");

?>
