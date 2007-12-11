<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Laskujen suoritus")."</font><hr>";

	// Meitä on kutsuttu tiliotteelta!
	if ($tiliote == 'Z') {
		echo "<form action = 'tilioteselailu.php' method='post'>
			<input type='hidden' name=mtili value='$mtili'>
			<input type='hidden' name=tee   value='Z'>
			<input type='hidden' name='pvm' value='$mav-$mak-$map'>
			<input type='Submit' value='".t("Palaa tiliotteelle")."'></form>";
	}

	if ($tee == 'V') {
		$summa = str_replace ( ",", ".", $summa);
	}

	if ($tee == 'W') {
		// Tarkistetaan oliko syötteet tilinvalinnasta oiekin....
		if ($mav < 1000) $mav += 2000;
		$val = checkdate($mak, $map, $mav);

		if (!$val) {
			echo "<font class='error'>".t("Virheellinen maksupvm")."</font><br>";
			$tee = '';
		}
	}

	if ($tee == 'V') {
		// Lasku on valittu ja sitä tiliöidään (suoritetaan)
		$query = "	SELECT *
                    FROM yriti
                   	WHERE yhtio='$kukarow[yhtio]' and tunnus = '$mtili'";
		$result = mysql_query($query) or pupe_error($query);
		$yritirow = mysql_fetch_array($result);

		if (strlen($yritirow[0]) == 0) {
			echo "<br>".t("Maskutilin tilitiedot kateissa")."<br> $query";
			exit;
		}

		$query = "	SELECT ytunnus, nimi, postitp, summa, kasumma,
					round(summa * vienti_kurssi, 2) 'vietysumma',
					round(kasumma * vienti_kurssi, 2) 'vietykasumma',
					ebid, tunnus, alatila, vienti_kurssi, tapvm, valkoodi
					FROM lasku
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]' and tila='Q'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Lasku katosi, tai sen on joku jo suorittanut")."!</font><br>";
			$tee = "W";
		}
	}

	if ($tee == 'V') {
		$laskurow = mysql_fetch_array($result);

		//Lasketaan kursssi
		if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
			if ($laskurow['alatila'] != 'K')
				$kurssi = $summa / $laskurow['summa'];
			else
				$kurssi = $summa / ($laskurow['summa']-$laskurow['kasumma']);
		}
		else {
			$kurssi = 1;
			if ($laskurow['alatila'] != 'K')
				$summa = $laskurow['summa'];
			else
				$summa = $laskurow['summa']-$laskurow['kasumma'];
		}

		$rahasumma = (float) $summa;
		$kurssi    = (float) $kurssi;

		if ($rahasumma == 0 and $laskurow["summa"] != 0) {
			echo "<font class='error'>".t("Et antanut maksettua summaa")."!</font><br>";
			$tee = 'W';
		}
	}

	if ($tee == 'V') {
		if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
			echo "<font class='message'>".t("Valuuttakurssi")." " . round($kurssi,6) . " (". round(1/$kurssi,6) . ")</font><br>";
		}

		// Ollaan yhteensopivia vanhan koodin kanssa
		$laskurow['maksusumma'] = round($laskurow['summa'] * $kurssi,2);
		$laskurow['maksukasumma'] = round($laskurow['kasumma'] * $kurssi,2);

		// Mikä on oikea ostovelkatili
		$query = "	SELECT tilino
					FROM tiliointi
					WHERE ltunnus	= '$tunnus'
					and yhtio 		= '$kukarow[yhtio]'
					and tapvm		= '$laskurow[tapvm]'
					and abs(summa + $laskurow[vietysumma]) <= 0.02
					and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
					and korjattu	= ''";
		$xresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($xresult) != 1) {
			echo "<font class='error'>".t("Ostovelkatilin määrittely epäonnistui")."!</font>$query<br>";
			exit;
		}

		$ostovelkarow = mysql_fetch_array($xresult);

		// Kassa-ale
		if ($laskurow['alatila'] == 'K' and $laskurow['maksukasumma'] != 0) {
			// Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
			echo "<font class='message'>".t("Kirjaan kassa-alennusta yhteensä")." $laskurow[maksukasumma]</font><br>";

			$totkasumma = 0;

			$query = "	SELECT *
						FROM tiliointi
						WHERE ltunnus	= '$tunnus'
						and yhtio 		= '$kukarow[yhtio]'
						and tapvm 		= '$laskurow[tapvm]'
						and tilino not in ('$yhtiorow[ostovelat]','$yhtiorow[alv]','$yhtiorow[varasto]','$yhtiorow[varastonmuutos]', '$yhtiorow[matkalla_olevat]','$yhtiorow[konserniostovelat]','$yhtiorow[kassaale]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]')
						and korjattu 	= ''";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) == 0) {
				echo "<font class='error'>".t("Laskulla ei ole ostovelkatiliöintiä. Tämä on systeemivirhe")."!</font><br>";
				exit;
			}

			while ($tiliointirow = mysql_fetch_array ($yresult)) {
				// Kuinka paljon on tämän viennin osuus
				$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $laskurow['maksukasumma'],2);
				$alv = 0;

				echo "<font class='message'>".t("Kirjaan kassa-alennusta")." $summa</font><br>";

				if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
					$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
					$summa -= $alv;
				}

				$totkasumma += $summa + $alv;

				$query = "	INSERT into tiliointi set
							yhtio 		= '$kukarow[yhtio]',
							ltunnus 	= '$laskurow[tunnus]',
							tilino 		= '$yhtiorow[kassaale]',
							kustp 		= '$tiliointirow[kustp]',
							kohde 		= '$tiliointirow[kohde]',
							projekti 	= '$tiliointirow[projekti]',
							tapvm 		= '$mav-$mak-$map',
							summa 		= $summa * -1,
							vero 		= '$tiliointirow[vero]',
							lukko 		= '',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$xresult = mysql_query($query) or pupe_error($query);
				$isa = mysql_insert_id ($link); // Näin löydämme tähän liittyvät alvit....

				if ($tiliointirow['vero'] != 0) {

					$query = "	INSERT into tiliointi set
								yhtio 		= '$kukarow[yhtio]',
								ltunnus 	= '$laskurow[tunnus]',
								tilino 		= '$yhtiorow[alv]',
								tapvm 		= '$mav-$mak-$map',
								summa 		= $alv * -1,
								vero 		= '',
								selite 		= '$selite',
								lukko 		= '1',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now(),
								aputunnus	= $isa";
					$xresult = mysql_query($query) or pupe_error($query);
				}
			}

			//Hoidetaan mahdolliset pyöristykset
			if ($totkasumma != $laskurow['maksukasumma']) {
				echo "<font class='message'>".t("Joudun pyöristämään kassa-alennusta")."</font><br>";

				$query = "	UPDATE tiliointi
							SET summa = summa - $totkasumma + $laskurow[maksukasumma]
							WHERE tunnus = '$isa'
							and yhtio 	 = '$kukarow[yhtio]'";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}

		// Valuutta-ero
		if ($laskurow['vienti_kurssi'] != $kurssi) {

			$vesumma = $rahasumma - $laskurow['vietysumma'];

			if ($laskurow['alatila'] == 'K'  and $laskurow['maksukasumma'] != 0) {
				$vesumma = round($rahasumma - ($laskurow['vietysumma'] - $laskurow['maksukasumma']),2);
			}

			echo "<font class='message'>".t("Kirjaan valuuttaeroa yhteensä")." $vesumma</font><br>";

			$totvesumma = 0;

			$query = "	SELECT *
						FROM tiliointi
						WHERE ltunnus	= '$tunnus'
						and yhtio 		= '$kukarow[yhtio]'
						and tapvm 		= '$laskurow[tapvm]'
						and tilino not in ('$yhtiorow[ostovelat]','$yhtiorow[alv]','$yhtiorow[varasto]','$yhtiorow[varastonmuutos]','$yhtiorow[matkalla_olevat]','$yhtiorow[konserniostovelat]','$yhtiorow[kassaale]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]')
						and korjattu 	= ''";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) == 0) {
				echo "<font class='error'>".t("Laskulla ei ole ostovelkatiliöintiä. Tämä on systeemivirhe")."!</font><br>";
				exit;
			}

			while ($tiliointirow = mysql_fetch_array ($yresult)) {
				// Kuinka paljon on tämän viennin osuus
				$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $vesumma,2);

				echo "<font class='message'>".t("Kirjaan valuuttaeroa")." $summa</font><br>";

				if (round($summa,2) != 0) {
					$query = "	INSERT into tiliointi set
								yhtio 		= '$kukarow[yhtio]',
								ltunnus 	= '$laskurow[tunnus]',
								tilino 		= '$yhtiorow[valuuttaero]',
								kustp 		= '$tiliointirow[kustp]',
								kohde 		= '$tiliointirow[kohde]',
								projekti 	= '$tiliointirow[projekti]',
								tapvm 		= '$mav-$mak-$map',
								summa 		= $summa,
								vero 		= 0,
								lukko 		= '',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now()";
					$xresult = mysql_query($query) or pupe_error($query);
					$isa = mysql_insert_id ($link);

					$totvesumma += $summa;
				}
			}

			//Hoidetaan mahdolliset pyöristykset
			if ($totvesumma != $vesumma) {
				echo "<font class='message'>".t("Joudun pyöristämään valuuttaeroa")."</font><br>";

				$query = "	UPDATE tiliointi
							SET summa = summa - $totvesumma + $vesumma
							WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}

		// Ostovelat
		$query = "	INSERT into tiliointi set
					yhtio 		= '$kukarow[yhtio]',
					ltunnus 	= '$laskurow[tunnus]',
					tilino 		= '$ostovelkarow[tilino]',
					tapvm 		= '$mav-$mak-$map',
					summa 		= '$laskurow[vietysumma]',
					vero 		= 0,
					lukko 		= '',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$xresult = mysql_query($query) or pupe_error($query);

		// Rahatili
		if ($selvittely == 'on') {
			if ($yritirow["oletus_selvittelytili"] != "") {
				$rahatili = $yritirow["oletus_selvittelytili"];
			}
			else {
				$rahatili = $yhtiorow['selvittelytili'];
			}
		}
		else {
			$rahatili = $yritirow['oletus_rahatili'];
		}

		$query = "	INSERT into tiliointi set
					yhtio 		= '$kukarow[yhtio]',
					ltunnus 	= '$laskurow[tunnus]',
					tilino 		= '$rahatili',
					tapvm 		= '$mav-$mak-$map',
					summa 		= -1 * $rahasumma,
					vero 		= 0,
					lukko 		= '',
					laatija 	= '$kukarow[kuka]',
					laadittu	= now()";
		$xresult = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE lasku set
					tila = 'Y',
					mapvm = '$mav-$mak-$map',
					maksu_kurssi = $kurssi
					WHERE tunnus='$tunnus'";
		$xresult = mysql_query($query) or pupe_error($query);

		echo "<br><table><tr>";
		for ($i = 0; $i < 8; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		for ($i=0; $i<8; $i++) {
			if (mysql_field_name($result,$i) == 'ebid') {
				// tehdään lasku linkki
				echo "<td>".ebid($laskurow['tunnus']) ."</td>";
			}
			else {
				echo "<td>$laskurow[$i]</td>";
			}
		}

		echo "</tr></table><br>";

		echo "<font class='head'>".t("Tiliöinti")."</font><hr><table>";

		// Näytetään tiliöinnit
		$query = "	SELECT tiliointi.tilino, tili.nimi, ku.nimi kustp, ko.nimi kohde, pr.nimi projekti, tapvm, summa, vero
					FROM tiliointi
					LEFT JOIN tili USING  (yhtio,tilino)
					LEFT JOIN kustannuspaikka as ku ON ku.yhtio='$kukarow[yhtio]' and ku.tunnus = tiliointi.kustp
					LEFT JOIN kustannuspaikka as ko ON ko.yhtio='$kukarow[yhtio]' and ko.tunnus = tiliointi.kohde
					LEFT JOIN kustannuspaikka as pr ON pr.yhtio='$kukarow[yhtio]' and pr.tunnus = tiliointi.projekti
					WHERE ltunnus = '$tunnus' and tiliointi.yhtio = '$kukarow[yhtio]' and
					korjattu = ''
					ORDER BY tapvm";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result) ; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		$kokokirjaus=0;

		while ($tiliointirow=mysql_fetch_array ($result)) {
			echo "<tr>";
			echo "<td align='right'>$tiliointirow[tilino]</td>";
			echo "<td>$tiliointirow[nimi]</td>";
			echo "<td>$tiliointirow[kustp]</td>";
			echo "<td>$tiliointirow[kohde]</td>";
			echo "<td>$tiliointirow[projekti]</td>";
			echo "<td>".tv1dateconv($tiliointirow["tapvm"])."</td>";
			echo "<td align='right'>$tiliointirow[summa]</td>";
			echo "<td align='right'>$tiliointirow[vero]</td>";

			$kokokirjaus += $tiliointirow['summa'];
			echo "</tr>";
		}
		echo "</table>";
		echo "<font class='message'>".t("Tosite yhteensä")." ". round($kokokirjaus,2) . "</font>";
		echo "<br><br>";

		echo "<table><tr>";
		echo "<form action = '$PHP_SELF' method='post'>
		        <input type='hidden' name='kurssi' value='$kurssi'>
				<input type='hidden' name='tee' value='W'>
				<input type='hidden' name='tiliote' value='$tiliote'>
				<input type='hidden' name='mav' value = '$mav'>
				<input type='hidden' name='mak' value = '$mak'>
				<input type='hidden' name='map' value = '$map'>
				<input type='hidden' name='mtili' value = $mtili>
				<input type='hidden' name='tunnus' value = $trow[0]>
				<td class='back'><input type='Submit' value='".t("Suorita lisää")."'></td></form>";

		echo "<form action = 'muutosite.php' method='post'>
				<input type='hidden' name='tee' value='E'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<td class='back'><input type='Submit' value='".t("Korjaa tiliöinti")."'></td></tr></form></table>";
	}

	if ($tee == "W") {
		// Tällä ollaan, jos olemme vasta valitsemassa laskua
		// Jos meitä on kutsuttu tiliotteella niin meillä voisi olla summakin
		$summahakuok=0;

		if ($tiliotesumma != 0) {
			$query = "	SELECT tunnus, nimi, tapvm, round((summa - if(alatila='K', kasumma, 0)) * vienti_kurssi, 2) kotisumma, concat_ws(' ',summa - if(alatila='K', kasumma, 0),valkoodi, if(alatila='K', '(K)','')) summa, ebid, valkoodi
					  	FROM lasku
					  	WHERE yhtio = '$kukarow[yhtio]' and maksu_tili='$mtili' and tila='Q' and round((summa - if(alatila='K', kasumma, 0)) * vienti_kurssi, 2) = '$tiliotesumma'
					  	ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0)
				 echo "<font class='message'>".t("Summalla ei löytynyt laskua")." $tiliotesumma</font><br>";
			else
				$summahakuok=1;
		}

		if ($tiliotesumma == 0 or $summahakuok == 0) {
			$query = "	SELECT tunnus, nimi, tapvm, round((summa - if(alatila='K', kasumma, 0)) * vienti_kurssi, 2) 'kotisumma', concat_ws(' ',summa - if(alatila='K', kasumma, 0),valkoodi, if(alatila='K', '(K)','')) summa, ebid, valkoodi
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and maksu_tili='$mtili' and tila='Q'
						ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				 echo "<font class='error'>".t("Tilillä")." ($mtili) ".t("ei ole suoritusta odottavia laskuja")."</font><br>";
			}
		}

		echo "<table><tr>";

		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
       	}

		echo "<th>".t('Summa')."</font></th><th>".t("Suoritus")."<br>".t("selvittelytililtä")."</th><th></th></tr>";

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr class='aktiivi'>";
			echo "<td valign='top'>$trow[nimi]</td>";
			echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td valign='top' align='right'>$trow[kotisumma]</td>";
			echo "<td valign='top' align='right'>$trow[summa]</td>";

			// tehdään lasku linkki
			echo "<td>".ebid($trow['tunnus']) ."</td>";

			echo "<td><form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='V'>
					<input type='hidden' name='tiliote' value='$tiliote'>";

			if ($trow['valkoodi'] != $yhtiorow['valkoodi'])
					echo "<input type='text' name='summa' value='$summa' size=8>";

			echo "</td>
				<td><INPUT TYPE='checkbox' NAME='selvittely' CHECKED></td>
				<td>
					<input type='hidden' name='mav' value = '$mav'>
					<input type='hidden' name='mak' value = '$mak'>
					<input type='hidden' name='map' value = '$map'>
					<input type='hidden' name='mtili' value = $mtili>
					<input type='hidden' name='tunnus' value = $trow[tunnus]>
					<input type='Submit' value='".t("Suorita")."'></td></tr></form>";
		}
		echo "</table>";
	}

	if ($tee == '') {
		// Tällä ollaan, jos olemme vasta valitsemassa pankkitiliä
		$query = "	SELECT tunnus, nimi, tilino
                    FROM yriti
                 	WHERE yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("hmm, yrityksellä ei ole yhtään pankkitiliä")."</font><br>";
		}

		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "<th>".t("Maksupvm")."</th><th></th></tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";

			for ($i=1; $i<mysql_num_fields($result); $i++) {
				echo "<td>$trow[$i]</td>";
			}

			echo "<form action = '$PHP_SELF?tee=W' method='post'><td>
					<input type='text' name='map' maxlength='2' size=3 value='$map'>
					<input type='text' name='mak' maxlength='2' size=3 value='$mak'>
					<input type='text' name='mav' maxlength='4' size=5 value='$mav'></td>";

			$trow[0]=rawurlencode ($trow[0]);

			echo "<td>
					<input type='hidden' name='mtili' value = $trow[0]>
					<input type='Submit' value='".t("Valitse")."'></td></tr></form>";
		}

		echo "</table>";
	}

	require "inc/footer.inc";

?>