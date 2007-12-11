<?php

	echo "<font class='head'>".t("Toimittajan laskut")."</font><hr>";
		
	if ($tee == 'A') {
		// N‰ytet‰‰n toimittajan laskuja
		//Jos k‰ytet‰‰n $tunnusta niin k‰ytet‰‰n liitostunnusta
		//Jos taas p‰‰ss‰t‰‰n $ytunnusta niin haetaan sen perusteella
		 
		if (!isset($kausi)) $kausi = '';
		if (strlen($kausi) == 10) $laji = 'o';

		if ($laji == 'O') {
			$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark) nimi, postitp
						FROM toimi
						WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</b>";
			}
		 	$trow = mysql_fetch_array ($result);
			
			echo "<b>$trow[ytunnus]</b> $trow[nimi] $trow[postitp]<br><br>";
			
			$lisa='';
			
			if (!isset($kausi)) $kausi='';
			
			if ($kausi=='') {
				$alku = substr($yhtiorow["tilikausi_alku"],04) - 2;
				$lisa = "and tapvm >= '$alku' and tapvm <= '$yhtiorow[tilikausi_loppu]'";
				$kentta = "left(tapvm,4)";
			}
			
			if (strlen($kausi) == 4) {
				$lisa = "and tapvm >= '$kausi-01-01' and tapvm <= '$kausi-12-31'";
				$kentta = "left(tapvm,7)";
			}
			
			if (strlen($kausi) == 7) {
				$kk= (int) substr($kausi,-2);
				$vv= (int) substr($kausi,0,4);
				$kk++;
				
				if ($kk > 12) {
					$vv++;
					$kk = $kk-12;
				}
				
				$seuraava = $vv . "-" . $kk;
				
				$lisa = "and tapvm >= '$kausi-01' and tapvm < '$seuraava-01'";
				$kentta = "tapvm";
			}
			
			$query = "	SELECT $kentta ajalta, sum(summa) ostot, round(sum(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi)),2) $yhtiorow[valkoodi]
					  	FROM lasku
					  	WHERE yhtio='$kukarow[yhtio]' and liitostunnus = '$tunnus' $lisa
					  	and tila in ('H', 'M', 'P', 'Q', 'Y')
					  	GROUP BY 1";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</font>";
			}

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";
			
			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr class='aktiivi'>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i==0) {
						echo "<td><a href='$PHP_SELF?toim=toimittajahaku&tee=A&laji=O&tunnus=$tunnus&kausi=$trow[$i]'>$trow[$i]</a></td>";
					}
					else {
						echo "<td>$trow[$i]</td>";
					}
				}
				echo "</tr>";
			}
			echo "</table><br><br>";
			
			require ("footer.inc");
			exit;
		}
		elseif ($laji == 'K' or $laji == 'H' or $laji == 'M' or $laji == 'Y' or $laji == 'o') {
			
			if ($ytunnus != "") {
				//Jos taas p‰‰ss‰t‰‰n $ytunnusta niin haetaan sen perusteella
				$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark) nimi, postitp
							FROM toimi
							WHERE ytunnus 	= '$ytunnus' 
							and yhtio 		= '$kukarow[yhtio]'
							ORDER by nimi";
							
				$toimiliilisa = " and ytunnus = '$ytunnus' ";
			}
			else {
				//Jos k‰ytet‰‰n $tunnusta niin k‰ytet‰‰n liitostunnusta
				$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark) nimi, postitp
							FROM toimi
							WHERE tunnus 	= '$tunnus' 
							and yhtio 		= '$kukarow[yhtio]'";
							
				$toimiliilisa = " and liitostunnus = '$tunnus' ";
			}
			
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</b>";
			}
			else {
		 		while ($trow=mysql_fetch_array ($result)) {
					echo "<b>$trow[ytunnus]</b> $trow[nimi] $trow[postitp]<br>";
				}
				
				echo "<br>";
				
				echo "<a href='$PHP_SELF?toim=toimittajahaku&tee=A&laji=$laji&tunnus=$tunnus&limit=NO'>N‰yt‰ kaikki laskut</a><br><br>";
				
				$lisa = '';
			
				if ($laji=='M'){
					$lisa = " and tila < 'S'";
				}
				elseif ($laji != 'K') {
					$lisa = " and tila = '$laji'";
				}
			 
				if ($laji == 'o') $lisa = " and tapvm = '$kausi'";
			 
				$alku += 0;
				
				if ($limit != "NO") {
					$limlis = " LIMIT $alku, 50 ";
				}

				$query = "	SELECT tapvm, erpcm, summa, valkoodi, vienti, concat_ws(' ', viite, viesti) 'viite/viesti', ebid, tila, tunnus
						  	FROM lasku
						  	WHERE yhtio = '$kukarow[yhtio]' 
						  	$toimiliilisa
							$lisa
						  	and tila in ('H', 'M', 'P', 'Q', 'Y')  
						  	ORDER BY tapvm desc 
							$limlis";			
				$result = mysql_query($query) or pupe_error($query);
				$seraavako = mysql_num_rows($result);

				if (mysql_num_rows($result) == 0) {
				 	echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</font>";
				}

				echo "<table><tr>";
				echo "<th>".t("Tapvm/Erpvm")."</th>";
				echo "<th>".t("Summa")."</th>";
				echo "<th>".t("Valuutta")."</th>";
				echo "<th>".t("Viite/Viesti")."</th>";
				echo "<th>".t("Keikka")."</th>";
				echo "<th>".t("EBID")."</th>";
				echo "<th>".t("Tila/Vienti")."</th>";
				echo "</tr>";

				$yhteensa = 0;
				
				while ($trow = mysql_fetch_array ($result)) {
					echo "<tr class='aktiivi'>";
					
					if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
						echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."<br>".tv1dateconv($trow["erpcm"])."</td>";
					}
					else {
						echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</a><br>".tv1dateconv($trow["erpcm"])."</td>";
					}
					
					echo "<td valign='top' align='right'>$trow[summa]</td>";
					echo "<td valign='top'>$trow[valkoodi]</td>";
					echo "<td valign='top'>".$trow["viite/viesti"]."</td>";
					
					//tehd‰‰n keikka linkki
					echo "<td valign='top'>";
					if ($trow["vienti"] == "C" || $trow["vienti"] == "F" || $trow["vienti"] == "I") {
						$query = " SELECT keikka.laskunro, keikka.tunnus
						FROM lasku 
						JOIN lasku AS keikka on lasku.yhtio = keikka.yhtio and lasku.laskunro = keikka.laskunro and keikka.tila = 'K' and keikka.vanhatunnus = 0
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.vanhatunnus = '$trow[tunnus]'";
						
						$keikka_res = mysql_query($query) or pupe_error($query);
						$keikka_row = mysql_fetch_array($keikka_res);
						
						echo "<a href='tilauskasittely/selaa_tilauksia.php?toim=KEIKKA&tee=tilaus&tunnus=$keikka_row[tunnus]'>".$keikka_row['laskunro']."</a>";
					}
					echo "</td>";
					
					// tehd‰‰n lasku linkki
					echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";
					
					$laskutyyppi = $trow["tila"];
					require "inc/laskutyyppi.inc";
					echo "<td valign='top'>".t($laskutyyppi)."<br>".ostolaskun_vienti($trow["vienti"])."</td>";
					
					$yhteensa += $trow["summa"];						
				
					echo "</tr>";
				}
				
				echo "<tr><th>".t("Yhteens‰:")."</th><td align='right'>$yhteensa</td></tr>";
				echo "</table><br>";
				
				if ($limit != "NO") {
					if ($alku > 0) {
						$siirry = $alku - 50;
						echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&ytunnus=$ytunnus&alku=$siirry&laji=$laji'>".t("Edelliset")."</a> ";
					}
					else {
						echo t("Edelliset")." ";
					}
			
					if ($seraavako >= 50) {
						$siirry = $alku + 50;
						echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&ytunnus=$ytunnus&alku=$siirry&laji=$laji'>".t("Seuraavat")."</a> ";
						echo "<br><br>";
					}
				}
				require "footer.inc";
				exit;
			}
		}
		else {
			echo "Virheellinen laji!";
			require "footer.inc";
			exit;
		}
	}
		
	$lisat = '';
	
	if ($tee == 'S') { // S = selaussanahaku
		$lisat = "and toimi.selaus like '%" . $nimi . "%'";
	}

	if ($tee == 'N') { // N = nimihaku
		$lisat = "and toimi.nimi like '%" . $nimi . "%'";
	}

	if ($tee == 'Y') { // Y = yritystunnushaku
		$lisat = "and toimi.ytunnus = '$nimi'";
	}
	
	if ($lisat != '') {
		$query = "	SELECT toimi.tunnus, toimi.ytunnus, concat_ws(' ', toimi.nimi, toimi.nimitark) nimi, toimi.postitp, round(sum(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi)),2) avoimet
					FROM toimi
					LEFT JOIN lasku ON toimi.yhtio=lasku.yhtio and toimi.tunnus=lasku.liitostunnus and tila in ('H','M')
					WHERE toimi.yhtio='$kukarow[yhtio]' $lisat
					GROUP BY toimi.tunnus
					ORDER BY selaus";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</b>";
		}
		if (mysql_num_rows($result) > 40) {
			echo "<b>".t("Haulla lˆytyi liikaa toimittajia. Tarkenna hakua")."</b><br>";
			$tee = '';
		}
		else {
			echo "<table><tr>";
			
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			
			echo "<th>".t("valitse")."</th><th></th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<form action = 'raportit.php?toim=toimittajahaku' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>
						<input type='hidden' name='tee' value='A'>";
				
				for ($i=1; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				
				echo "<td><select name = 'laji'>
						<option value = 'K'>".t("Kaikki")."
						<option value = 'H'>".t("Hyv‰ksym‰tt‰")."
						<option value = 'M'>".t("Maksamatta")."
						<option value = 'Y'>".t("Maksettu")."
						<option value = 'O'>".t("Ostoraportti")."
						</select></td>
						<td><input type='Submit' value='".t("N‰yt‰")."'></td>
						</tr></form>";
			}
			echo "</table>";
		}
	}
	
	if ($tee == '') {
		echo "<form name = 'valinta' action = 'raportit.php' method='post'>
				<input type='hidden' name='toim' value='toimittajahaku'>
				<table>
				<tr>
				<th>".t("Valitse toimittaja")."</th>
				<td><input type = 'text' name = 'nimi'></td>
				<td><select name='tee'>
				<option value = 'N'>".t("Toimittajan nimi")."
				<option value = 'S'>".t("Toimittajan selaussana")."
				<option value = 'Y'>".t("Y-tunnus")."
				</select>
				</td>
				<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr></table></form>";
				
		$formi = 'valinta';
		$kentta = 'nimi';

		require "inc/footer.inc";
		exit;
	}
