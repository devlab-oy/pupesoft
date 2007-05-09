<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Sarjanumeromyynnin tarkistusta").":</font><hr><br>";
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='go'>";
	echo "<table>";
	echo "<tr>
		<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr>\n
		<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td><td class='back'><input type='submit' value='".t("Aja raportti")."'></td>
		</tr>\n";
	echo "</table>";
	echo "</form><br>";
	
	
	

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "go";
	}


	
	if ($tee == "go") {
				
		echo "<table>";
		echo "<tr><th colspan='8'>Myyydyt sarjanumerot joita ei olla ollenkaan ostettu</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
		
		echo "<form name='haku' action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value = 'go'>";
		echo "<input type='hidden' name='ppa' value = '$ppa'>";
		echo "<input type='hidden' name='vva' value = '$vva'>";
		echo "<input type='hidden' name='kka' value = '$kka'>";
		echo "<input type='hidden' name='ppl' value = '$ppl'>";
		echo "<input type='hidden' name='vvl' value = '$vvl'>";
		echo "<input type='hidden' name='kkl' value = '$kkl'>";
		echo "<tr>";
		echo "<td><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
		echo "<td><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
		echo "<td><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td><input type='text' size='10' name='ostotilaus_haku' 		value='$ostotilaus_haku'></td>";
		echo "<td><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
		echo "<td class='back'><input type='submit' value='Hae'></td>";
		echo "</tr>";
		echo "</form>";
		
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
		
		
		//Myyydyt sarjanumerot joita ei olla ollenkaan ostettu
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
					LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
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
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
		
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}
	
		echo "<tr><td class='back'><br><br></td></tr>";
	
	
		//Myyydyt sarjanumerot
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
					LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L'
					and tilausrivi.kpl > 0
					$lisa
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					having sarjanumero is not null and ostotunnus is not null
					order by sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th colspan='8'>Myyydyt sarjanumerot</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
	
		while ($vrow = mysql_fetch_array($vresult)) {	
			echo "<tr>
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}

		echo "<tr><td class='back'><br><br></td></tr>";
			
		//Myydyt sarjanumerot ilman sarjanumeroa
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys, 
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, sm.sarjanumero sarjanumero, sm.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
					LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sm.yhtio and ostorivi.tunnus=sm.ostorivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L'
					and tilausrivi.kpl > 0
					$lisa
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					having sarjanumero is null
					order by sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);
	
		echo "<tr><th colspan='8'>Myydyt sarjanumerot ilman sarjanumeroa</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
	
		while ($vrow = mysql_fetch_array($vresult)) {	
			echo "<tr>
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}
	
		echo "<tr><td class='back'><br><br></td></tr>";


		//Ostetut ja hyvitetyt sarjanumerot
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
					LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=so.yhtio and ostorivi.tunnus=so.ostorivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L'
					and tilausrivi.kpl < 0
					$lisa
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					having sarjanumero is not null
					order by sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);
	
		echo "<tr><th colspan='8'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
	
		while ($vrow = mysql_fetch_array($vresult)) {	
			echo "<tr>
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}
	
		echo "<tr><td class='back'><br><br></td></tr>";

		//Ostetut ja hyvitetyt sarjanumerot ilman sarjanumeroa
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys, 
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta sm ON tilausrivi.yhtio=sm.yhtio and tilausrivi.tuoteno=sm.tuoteno and tilausrivi.tunnus=sm.myyntirivitunnus
					LEFT JOIN sarjanumeroseuranta so ON tilausrivi.yhtio=so.yhtio and tilausrivi.tuoteno=so.tuoteno and tilausrivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=so.yhtio and ostorivi.tunnus=so.ostorivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L'
					and tilausrivi.kpl < 0
					$lisa
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					having sarjanumero is null
					order by sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);
	
		echo "<tr><th colspan='8'>Myyntipuolelta ostetut ja hyvitetyt sarjanumerot ilman sarjanumeroa</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
	
		while ($vrow = mysql_fetch_array($vresult)) {	
			echo "<tr>
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotilaus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}

		echo "<tr><td class='back'><br><br></td></tr>";

		//Ostopuolelta ostetut sarjanumrot
		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, ostorivi.tuoteno, ostorivi.nimitys, 
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, round(tilausrivi.kate/tilausrivi.kpl,2) kate, round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, ostorivi.uusiotunnus ostotilaus, so.sarjanumero sarjanumero, so.tunnus sarjatunnus,
					tilausrivi.kpl
					FROM tilausrivi ostorivi
					JOIN tuote on tuote.yhtio=ostorivi.yhtio and tuote.tuoteno=ostorivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta so ON ostorivi.yhtio=so.yhtio and ostorivi.tuoteno=so.tuoteno and ostorivi.tunnus=so.ostorivitunnus
					LEFT JOIN tilausrivi ON tilausrivi.yhtio=so.yhtio and tilausrivi.tunnus=so.myyntirivitunnus
					WHERE ostorivi.yhtio = '$kukarow[yhtio]'
					and ostorivi.tyyppi = 'O'
					$lisa
					and ostorivi.laskutettuaika >= '$vva-$kka-$ppa' and ostorivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					order by sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th colspan='8'>Ostopuolelta ostetut sarjanumrot</th></tr>";
		echo "<tr><th>Myyntitilaus</th><th>Tuoteno</th><th>Nimitys</th><th>Myyntihinta</th><th>Ostohinta</th><th>Kate</th><th>Ostotilaus</th><th>Sarjanumero</th></tr>";
	
		while ($vrow = mysql_fetch_array($vresult)) {	
			echo "<tr>
					<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[otunnus]'>$vrow[otunnus]</a></td>
					<td><a href='../tuote.php?tee=Z&tuoteno=$vrow[tuoteno]'>$vrow[tuoteno]</a></td><td>$vrow[nimitys]</td><td align='right'>$vrow[rivihinta]</td><td align='right'>$vrow[ostohinta]</td>";
				
			if (abs(round($vrow["rivihinta"]-$vrow["ostohinta"],2) - $vrow["kate"]) > 0.01 and $vrow["kpl"] == 1) {
				echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$vrow["ostohinta"])."</font></td>";
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
					echo "<td align='right' nowrap><font style='color: red;'>$vrow[kate] <>".sprintf('%.2f', $vrow["rivihinta"]-$uusisarjahin)."</font></td>";
				}
				else {
					echo "<td align='right'>$vrow[kate]</td>";
				}
			}
			elseif ($vrow["kate"] < 0) {
				echo "<td align='right'><font style='color: red;'>$vrow[kate]</font></td>";
			}
			else {
				echo "<td align='right'>$vrow[kate]</td>";
			}
		
			echo "	<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$vrow[ostotilaus]'>$vrow[ostotunnus]</a></td>
					<td><a href='../tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$vrow[tuoteno]&sarjanumero_haku=$vrow[sarjanumero]'>&nbsp;$vrow[sarjanumero]&nbsp;</a></td>
					</tr>";
		}
	
		echo "</table><br><br>";
	}
	require ("../inc/footer.inc");

?>