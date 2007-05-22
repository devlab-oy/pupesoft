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
						SET osto_vai_hyvitys = '$osto_vai_hyvitys'
						WHERE yhtio	= '$kukarow[yhtio]'
						and tilausrivitunnus = '$rivitunnus'
						and tunnus 	= '$lisatied_row[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "	INSERT INTO tilausrivin_lisatiedot
						SET yhtio = '$kukarow[yhtio]',
						tilausrivitunnus = '$rivitunnus',
						osto_vai_hyvitys = '$osto_vai_hyvitys',
						lisatty	= now(),
						lisannyt = '$kukarow[kuka]'";
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
		echo "<tr><th colspan='9'>Hakukentät</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";

		echo "<tr>";
		echo "<td valign='top'><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
		echo "<td valign='top'><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
		echo "<td valign='top'><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'></td>";
		echo "<td valign='top'><input type='text' size='10' name='ostotilaus_haku' 			value='$ostotilaus_haku'></td>";
		echo "<td valign='top'><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
		echo "<td class='back'><input type='submit' value='Hae'></td>";
		echo "</tr>";
		echo "<tr><td class='back'><br></td></tr>";
		echo "</form>";
	}
	
	if (($jarjestys_1 != '' or $jarjestys_2 != '' or $jarjestys_3 != '' or $jarjestys_4 != '' or $jarjestys_5 != '' or $jarjestys_6 != '')and $tee == "") {
		
		$lisa  = "";

		if ($ostotilaus_haku != "") {
			$lisa .= " and ostorivi.otunnus='$ostotilaus_haku' ";
		}

		if ($myyntitilaus_haku != "") {
			$lisa .= " and tilausrivi.otunnus='$myyntitilaus_haku' ";
		}
		
		if ($tuoteno_haku != "") {
			$lisa .= " and tilausrivi.tuoteno like '%$tuoteno_haku%' ";
		}

		if ($sarjanumero_haku != "") {
			$lisa .= " and (sm.sarjanumero like '%$sarjanumero_haku%' or so.sarjanumero like '%$sarjanumero_haku%') ";
		}

		if ($nimitys_haku != "") {
			$lisa .= " and tilausrivi.nimitys like '%$nimitys_haku%' ";
		}	
		
		if ($jarjestys_1 != "") {
			echo "<tr><th colspan='9'>Myyydyt sarjanumerot joita ei olla ollenkaan ostettu</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
			
			//Myyydyt sarjanumerot joita ei olla ollenkaan ostettu
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
						round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
						ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
						tilausrivi.kpl, myyntilasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
						LEFT JOIN lasku myyntilasku ON myyntilasku.yhtio=tilausrivi.yhtio and myyntilasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.kpl > 0
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						having sarjanumero is not null and ostotunnus is null
						order by sarjanumero";
			$vresult = mysql_query($query) or pupe_error($query);
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
		
				if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);
				
					$uusisarjahin = 0;
				
					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];
					
					}
				
					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);
				
					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
						<td></td>
						<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>$vrow[sarjanumero]</a></td>
						</tr>";
			}
	
			echo "<tr><td class='back'><br><br></td></tr>";
		}
	
		if ($jarjestys_2 != "") {
			//Myyydyt sarjanumerot
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
						round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
						ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
						tilausrivi.kpl, myyntilasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys,
						(select count(*) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.myyntirivitunnus=tilausrivi.tunnus) css
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
						LEFT JOIN lasku myyntilasku ON myyntilasku.yhtio=tilausrivi.yhtio and myyntilasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.kpl > 0
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						having sarjanumero is not null and ostotunnus is not null and kpl=css
						order by sarjanumero";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<tr><th colspan='9'>Myyydyt sarjanumerot</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
				if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);

					$uusisarjahin = 0;

					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];

					}

					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);

					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
						<td></td>
						<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>$vrow[sarjanumero]</a></td>
						</tr>";
			}

			echo "<tr><td class='back'><br><br></td></tr>";
		}
		
		if ($jarjestys_3 != "") {
			//Myydyt sarjanumerot ilman sarjanumeroa
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys, 
						round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
						ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
						tilausrivi.kpl, myyntilasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys,
						(select count(*) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.myyntirivitunnus=tilausrivi.tunnus) css
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
						LEFT JOIN lasku myyntilasku ON myyntilasku.yhtio=tilausrivi.yhtio and myyntilasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.kpl > 0
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						having sarjanumero is null or kpl!=css
						order by otunnus";
			$vresult = mysql_query($query) or pupe_error($query);
	
			echo "<tr><th colspan='9'>Myydyt sarjanumerot ilman sarjanumeroa</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
				if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);

					$uusisarjahin = 0;

					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];

					}

					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);

					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
						<td></td>
						<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno=$vrow[tuoteno]&myyntirivitunnus=$vrow[myyntitunnus]&from=KORJAA&lopetus=ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//jarjestys_1=$jarjestys_1//jarjestys_2=$jarjestys_2//jarjestys_3=$jarjestys_3//jarjestys_4=$jarjestys_4//jarjestys_5=$jarjestys_5//jarjestys_6=$jarjestys_6'>sarjanro</a></td>
						</tr>";
			}
	
			echo "<tr><td class='back'><br><br></td></tr>";
		}

		if ($jarjestys_4 != "") {
			//Ostetut ja hyvitetyt sarjanumerot			
			$query = "	SELECT myyntirivi.otunnus, myyntirivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
						round(myyntirivi.rivihinta/myyntirivi.kpl,2) rivihinta, round(myyntirivi.kate/myyntirivi.kpl,2) kate, round(tilausrivi.rivihinta/tilausrivi.kpl,2) ostohinta,
						tilausrivi.tunnus ostotunnus, if(tilausrivi.tyyppi='O', tilausrivi.uusiotunnus, tilausrivi.otunnus) ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
						tilausrivi.kpl, ostolasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi myyntirivi ON myyntirivi.yhtio=so.yhtio and myyntirivi.tunnus=so.myyntirivitunnus
						LEFT JOIN lasku ostolasku ON ostolasku.yhtio=tilausrivi.yhtio and ostolasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.kpl < 0
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						having sarjanumero is not null
						order by sarjanumero";
			$vresult = mysql_query($query) or pupe_error($query);
	
			echo "<tr><th colspan='9'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a name='$vrow[ostotunnus]'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
				if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);

					$uusisarjahin = 0;

					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];

					}

					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);

					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>";
				
				$sel1 = $sel2 = "";
				
				if ($vrow["osto_vai_hyvitys"] == "O") {
					$sel2 = "SELECTED";
				}
				else {
					$sel1 = "SELECTED";
				}
				
				echo "	<td><form action='$PHP_SELF?tee=OSTOVAIHYVITYS&rivitunnus=$vrow[ostotunnus]&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&jarjestys_1=$jarjestys_1&jarjestys_2=$jarjestys_2&jarjestys_3=$jarjestys_3&jarjestys_4=$jarjestys_4&jarjestys_5=$jarjestys_5&jarjestys_6=$jarjestys_6#$vrow[ostotunnus]' method='post'>
						<input type='hidden' name='myyntitilaus_haku'		value='$myyntitilaus_haku'>
						<input type='hidden' name='tuoteno_haku' 			value='$tuoteno_haku'>
						<input type='hidden' name='nimitys_haku' 			value='$nimitys_haku'>
						<input type='hidden' name='ostotilaus_haku' 		value='$ostotilaus_haku'>
						<input type='hidden' name='sarjanumero_haku' 		value='$sarjanumero_haku'>
						<select name='osto_vai_hyvitys' onchange='submit();' Style='font-size: 8pt; padding:0;'>
						<option value=''  $sel1>".("Hyvitys")."</option>
						<option value='O' $sel2>".("Osto")."</option>
						</select>
						</form></td>";
				
				echo "	<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>$vrow[sarjanumero] $vrow[kaytetty]</a></td>
						</tr>";
			}
	
			echo "<tr><td class='back'><br><br></td></tr>";
		}

		if ($jarjestys_5 != "") {
			//Ostetut ja hyvitetyt sarjanumerot ilman sarjanumeroa
			$query = "	SELECT myyntirivi.otunnus, myyntirivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
						round(myyntirivi.rivihinta/myyntirivi.kpl,2) rivihinta, round(myyntirivi.kate/myyntirivi.kpl,2) kate, round(tilausrivi.rivihinta/tilausrivi.kpl,2) ostohinta,
						tilausrivi.tunnus ostotunnus, if(tilausrivi.tyyppi='O', tilausrivi.uusiotunnus, tilausrivi.otunnus) ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
						tilausrivi.kpl, ostolasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys,
						(select count(*) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.ostorivitunnus=tilausrivi.tunnus) css
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi myyntirivi ON myyntirivi.yhtio=so.yhtio and myyntirivi.tunnus=so.myyntirivitunnus
						LEFT JOIN lasku ostolasku ON ostolasku.yhtio=tilausrivi.yhtio and ostolasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.kpl < 0
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						having sarjanumero is null or abs(kpl)!=css
						order by ostotilaus";
			$vresult = mysql_query($query) or pupe_error($query);
	
			echo "<tr><th colspan='9'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot ilman sarjanumeroa</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a name='$vrow[ostotunnus]'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]<br><font class='message'>$vrow[viesti]</font></td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
				if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);

					$uusisarjahin = 0;

					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];

					}

					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);

					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>";
				
				$sel1 = $sel2 = "";
				
				if ($vrow["osto_vai_hyvitys"] == "O") {
					$sel2 = "SELECTED";
				}
				else {
					$sel1 = "SELECTED";
				}
				
				echo "	<td><form action='$PHP_SELF?tee=OSTOVAIHYVITYS&rivitunnus=$vrow[ostotunnus]&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&jarjestys_1=$jarjestys_1&jarjestys_2=$jarjestys_2&jarjestys_3=$jarjestys_3&jarjestys_4=$jarjestys_4&jarjestys_5=$jarjestys_5&jarjestys_6=$jarjestys_6#$vrow[ostotunnus]' method='post'>
						<select name='osto_vai_hyvitys' onchange='submit();' Style='font-size: 8pt; padding:0;'>
						<option value=''  $sel1>".("Hyvitys")."</option>
						<option value='O' $sel2>".("Osto")."</option>
						</select>
						</form></td>";
					
				echo "	<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno=$vrow[tuoteno]&ostorivitunnus=$vrow[ostotunnus]&from=KORJAA&lopetus=ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//jarjestys_1=$jarjestys_1//jarjestys_2=$jarjestys_2//jarjestys_3=$jarjestys_3//jarjestys_4=$jarjestys_4//jarjestys_5=$jarjestys_5//jarjestys_6=$jarjestys_6'>sarjanro</a></td>
						</tr>";
			}

			echo "<tr><td class='back'><br><br></td></tr>";
		}

		if ($jarjestys_6 != "") {
			//Ostopuolelta ostetut sarjanumerot
			$query = "	SELECT myyntirivi.otunnus, myyntirivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
						round(myyntirivi.rivihinta/myyntirivi.kpl,2) rivihinta, round(myyntirivi.kate/myyntirivi.kpl,2) kate, round(tilausrivi.rivihinta/tilausrivi.kpl,2) ostohinta,
						tilausrivi.tunnus ostotunnus, if(tilausrivi.tyyppi='O', tilausrivi.uusiotunnus, tilausrivi.otunnus) ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
						tilausrivi.kpl, tilausrivin_lisatiedot.osto_vai_hyvitys
						FROM tilausrivi
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
						LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
						LEFT JOIN tilausrivi myyntirivi ON myyntirivi.yhtio=so.yhtio and myyntirivi.tunnus=so.myyntirivitunnus
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'O'
						$lisa
						and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
						order by sarjanumero";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<tr><th colspan='9'>Ostopuolelta ostetut sarjanumrot</th></tr>";
			echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>O/H</th><th>Sarjanumero</th></tr>";
	
			while ($vrow = mysql_fetch_array($vresult)) {	
				echo "<tr>
						<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
						<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td valign='top'>$vrow[nimitys]</td><td valign='top' align='right'>$vrow[rivihinta]</td><td valign='top' align='right'>$vrow[ostohinta]</td>";
				
				if ($vrow["rivihinta"] != "" and abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
					echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
				}
				elseif ($vrow["rivihinta"] != "" and abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] > 1) {
					//Haetaan nyt tämän myyntirivin kaikki ostorivit
					$query = "	SELECT ostorivitunnus
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$vrow[tuoteno]'
								and myyntirivitunnus	= '$vrow[myyntitunnus]'
								and ostorivitunnus		> 0";
					$ostosarjares = mysql_query($query) or pupe_error($query);

					$uusisarjahin = 0;

					while($ostosarjarow = mysql_fetch_array($ostosarjares)) {
						$query = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ostosarjarow[ostorivitunnus]'
									and laskutettuaika != '0000-00-00'
									and kpl > 0";
						$hinrivires = mysql_query($query) or pupe_error($query);
						$hinrivirow = mysql_fetch_array($hinrivires);
						$uusisarjahin += $hinrivirow["hinta"];

					}

					$uusisarjahin = round($uusisarjahin/$vrow["kpl"],2);

					if (round($vrow["rivihinta"]-$uusisarjahin,2) != $vrow["kate"]) {
						echo "<td valign='top' align='right' nowrap><font style='color: red;'>$vrow[kate] <> ".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
					}
					else {
						echo "<td valign='top' align='right'>$vrow[kate]</td>";
					}
				}
				elseif ($vrow["kate"] < 0) {
					echo "<td valign='top' align='right'><font style='color: red;'>$vrow[kate]</font></td>";
				}
				else {
					echo "<td valign='top' align='right'>$vrow[kate]</td>";
				}
		
				echo "	<td valign='top'><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotunnus]</a></td>
						<td></td>
						<td valign='top'><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>$vrow[sarjanumero]</a></td>
						</tr>";
			}
		}
	}
	
	echo "</table><br><br>";
	
	require ("../inc/footer.inc");

?>