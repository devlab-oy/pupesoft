<?php
	require ("inc/parametrit.inc");


	echo "<font class='head'>Osto ja myyntireskontran tarkistus</font><hr>";

	if ($tee=='X') {
		$loppu = date("Y-m-d",mktime(0, 0, 0, $kk+1, '1', $vv));
		if ($valinta=='O') {
			echo "<font class='message'>Tarkistetaan ostolaskujen kirjaukset ostovelkoihin</font><br>";
			flush();
			$query = "SELECT lasku.tapvm, lasku.nimi, round(lasku.summa * vienti_kurssi,2) lsumma, tiliointi.summa * -1 tsumma, lasku.tunnus, lasku.mapvm 
				FROM lasku use index (yhtio_tila_tapvm)
				LEFT JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and
						lasku.tapvm=tiliointi.tapvm and tiliointi.tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]') and korjattu=''
				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
						lasku.tapvm < '$loppu' and lasku.tila in ('H', 'M', 'P','Q', 'Y')";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Vastaava tiliöinti</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if ($tapahtuma['lsumma'] != $tapahtuma['tsumma']) {
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[lsumma]</td><td>$tapahtuma[tsumma]";
					if ($tapahtuma['tapvm'] == $tapahtuma['mapvm']) echo "<font class='error'>*</font>";
					echo "</td></tr>";
				}
			}
			echo "</table>";
			echo "<font class='message'>Tähdellä merkityillä on tapahtuma- ja maksupäivä sama. Virhe voi johtua siitä</font><br>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();			

			echo "<font class='message'>Tarkistetaan ostolaskujen maksujen kirjaukset ostovelkoihin</font><br>";
			flush();
			$query = "SELECT lasku.tapvm, lasku.nimi, round(lasku.summa * vienti_kurssi,2) lsumma, tiliointi.summa tsumma, lasku.tunnus, lasku.mapvm
				FROM lasku use index (yhtio_tila_tapvm)
				LEFT JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and
						lasku.mapvm=tiliointi.tapvm and tiliointi.tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]') and korjattu=''
				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
						lasku.tapvm < '$loppu' and lasku.tila = 'Y'";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Vastaava tiliöinti</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if ($tapahtuma['lsumma'] != $tapahtuma['tsumma']) {
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[lsumma]</td><td>$tapahtuma[tsumma]";
					if ($tapahtuma['tapvm'] == $tapahtuma['mapvm']) echo "<font class='error'>*</font>";
					echo "</td></tr>";
				}
			}
			echo "</table>";
			echo "<font class='message'>Tähdellä merkityillä on tapahtuma- ja maksupäivä sama. Virhe voi johtua siitä</font><br>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();

			echo "<font class='message'>Tarkistetaan ostotositteiden loppusummat</font><br>";
			flush();
			$query = "SELECT lasku.tunnus, tiliointi.tapvm, lasku.nimi, lasku.summa, sum(tiliointi.summa) ssumma 
						FROM lasku use index (yhtio_tila_tapvm), tiliointi use index (tositerivit_index)
						WHERE lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and korjattu='' and
								lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
								lasku.tapvm < '$loppu' and lasku.tila in ('H', 'M', 'P','Q', 'Y')
						GROUP BY 1,2";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Tositteen heitto</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if (round($tapahtuma['ssumma'],2) != 0.00) 
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[summa]</td><td>$tapahtuma[ssumma]</td></tr>";
			}
			echo "</table>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();
		}
		if ($valinta=='M') {
			echo "<font class='message'>Tarkistetaan myyntilaskujen kirjaukset myyntisaamisiin</font><br>";
			flush();
			$query = "SELECT lasku.tapvm, lasku.nimi, lasku.summa lsumma, sum(tiliointi.summa) tsumma, saldo_maksettu, lasku.tunnus, lasku.mapvm
				FROM lasku use index (yhtio_tila_tapvm)
				LEFT JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and
						lasku.tapvm=tiliointi.tapvm and tiliointi.tilino in ('$yhtiorow[myyntisaamiset]','$yhtiorow[factoringsaamiset]','$yhtiorow[konsernimyyntisaamiset]') and korjattu='' and lasku.summa = tiliointi.summa
				LEFT JOIN maksuehto ON maksuehto.tunnus=lasku.maksuehto and maksuehto.kateinen=''
				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
						lasku.tapvm < '$loppu' and lasku.tila = 'U' and lasku.alatila='X'
				GROUP BY lasku.tunnus";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Vastaava tiliöinti</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if ($tapahtuma['lsumma'] != $tapahtuma['tsumma']) { 
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[lsumma]</td><td>$tapahtuma[tsumma]";
					if ($tapahtuma['tapvm'] == $tapahtuma['mapvm']) echo "<font class='error'>*</font>";
					if ($tapahtuma['saldo_maksettu'] != 0) echo "<font class='error'> osasuoritus!</font>";
					echo "</td></tr>";
				}
			}
			echo "</table>";
			echo "<font class='message'>Tähdellä merkityillä on tapahtuma- ja maksupäivä sama. Virhe voi johtua siitä</font><br>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();			

			echo "<font class='message'>Tarkistetaan myyntilaskujen maksujen kirjaukset myyntisaamisiin</font><br>";
			flush();
			$query = "SELECT lasku.tapvm, lasku.nimi, lasku.summa lsumma, sum(tiliointi.summa) * -1 tsumma, lasku.tunnus, lasku.mapvm
				FROM lasku use index (yhtio_tila_tapvm)
				LEFT JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and
						lasku.tapvm!=tiliointi.tapvm and tiliointi.tilino in ('$yhtiorow[myyntisaamiset]','$yhtiorow[factoringsaamiset]','$yhtiorow[konsernimyyntisaamiset]') and korjattu=''
				LEFT JOIN maksuehto ON maksuehto.tunnus=lasku.maksuehto and maksuehto.kateinen=''
				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
						lasku.tapvm < '$loppu' and lasku.tila = 'U' and lasku.alatila='X' and mapvm != '0000-00-00'
				GROUP BY lasku.tunnus";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Vastaava tiliöinti</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if ($tapahtuma['lsumma'] != $tapahtuma['tsumma']) {
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[lsumma]</td><td>$tapahtuma[tsumma]";
					if ($tapahtuma['tapvm'] == $tapahtuma['mapvm']) echo "<font class='error'>*</font>";
					echo "</td></tr>";
				}
			}
			echo "</table>";
			echo "<font class='message'>Tähdellä merkityillä on tapahtuma- ja maksupäivä sama. Virhe voi johtua siitä</font><br>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();

			echo "<font class='message'>Tarkistetaan myyntilaskutositteiden loppusummat</font><br>";
			flush();
			$query = "SELECT lasku.tunnus, tiliointi.tapvm, lasku.nimi, lasku.summa, sum(tiliointi.summa) ssumma 
				FROM lasku use index (yhtio_tila_tapvm),
				tiliointi use index (tositerivit_index)
				WHERE lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and korjattu='' and
					lasku.yhtio = '$kukarow[yhtio]' and lasku.tapvm >='$vv-$kk-01' and
						lasku.tapvm < '$loppu' and lasku.tila = 'U' and lasku.alatila='X'
				GROUP BY 1,2";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table>";
			echo "<tr><th>Nimi</th><th>Tapvm</th><th>Laskun summa</th><th>Tositteen heitto</th></tr>";
			while ($tapahtuma = mysql_fetch_array($result)) {
				if (round($tapahtuma['ssumma'],2) != 0.00) 
					echo "<tr><td>$tapahtuma[nimi]</td><td><a href='muutosite.php?tee=E&tunnus=$tapahtuma[tunnus]'>$tapahtuma[tapvm]</a></td><td>$tapahtuma[summa]</td><td>$tapahtuma[ssumma]</td></tr>";
			}
			echo "</table>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();
/*		
			echo "<font class='message'>Tarkistetaan tositteilta syyt tapahtumiin</font><br>";
			flush();
			$query = "select tiliointi.laatija,tiliointi.tapvm ttapvm, korjattu, korjausaika, tiliointi.summa tsumma, lasku.summa lsumma, lasku.tila, lasku.alatila, lasku.tapvm ltapvm, lasku.mapvm, tiliointi.selite
				from tiliointi
				left join lasku on lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
				where tiliointi.yhtio = '$kukarow[yhtio]' and tiliointi.tapvm >='2005-07-11' and
						tiliointi.tapvm < '2005-07-12' and tilino='$yhtiorow[myyntisaamiset]'
				order by tiliointi.summa";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>Tarkistettavia tapahtumia on " .mysql_num_rows($result) . "</font><br>";
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";
			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "<td>";
				if ($trow['tila'] == 'U') { 
					if (($trow['ttapvm'] == $trow['ltapvm']) and ($trow['lsumma'] == $trow['tsumma'])) {
						echo "Kirjattiin uusi lasku";
					}
					if (($trow['ttapvm'] == $trow['mapvm']) and ($trow['lsumma'] == -1 * $trow['tsumma'])) {
						echo "Maksettiin lasku";
					}
				}
				if ($trow['tila'] == 'X') {
					if ($trow['laatija'] == 'automaatti') {
						echo "Väärin maksettu lasku";
					}
				}
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<font class='message'>Done!</font><br><br>";
			flush();
*/			
		} 
	}
	if ($tee == '') {
		// mikä kuu/vuosi nyt on
		$year = date("Y");
		$kuu  = date("n");
		// poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
		$ek_vv = date("Y",mktime(0,0,0,$kuu,0,$year));
		$ek_kk = date("n",mktime(0,0,0,$kuu,0,$year));

		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value = 'X'>
				<table>
				<tr>
				<th>Anna kausi kk vvvv</th>
				<td><input type = 'text' name = 'kk' value='$ek_kk' size=2> 
				<input type = 'text' name = 'vv' value='$ek_vv' size=4></td>
				</tr>
				<th>Mitä tarkistetaan:</th>
				<td><input type = 'radio' name = 'valinta' value='M' checked> Myyntilaskut<br>
				<input type = 'radio' name = 'valinta' value='O'> Ostolaskut</td>
				</tr>
				<tr>
				<td></td><td><input type = 'submit' value = 'Tarkista'></td>
				</tr>
				</table>
				</form>";
		$formi = 'valinta';
		$kentta = 'kk';
	}
	require("inc/footer.inc");
?>
