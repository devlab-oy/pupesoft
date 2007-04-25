<?php
	require ("inc/parametrit.inc");
	require ("inc/alvpopup.inc");
	require_once ("inc/tilinumero.inc");

	echo "<font class='head'>".t('Hyväksyttävät laskusi')."</font><hr>";

	if ($tee == 'M') {
		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow = mysql_fetch_array ($result);

		if ($trow['hyvak1'] == $kukarow['kuka']) {
			require ("inc/muutosite.inc");
		}
		else {
			echo "Tietosuojaongelma! Et ole oikea käyttäjä";
			exit;
		}
	}

 	// Jaaha poistamme laskun!
	if ($tee == 'D' and $oikeurow['paivitys'] == '1') {

		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow = mysql_fetch_array ($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Poisti laskun")."<br>" . $trow['comments'];

		// Ylikirjoitetaan tiliöinnit
		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE ltunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		// Merkataan lasku poistetuksi
		$query = "	UPDATE lasku SET
					alatila = 'H',
					tila = 'D',
					comments = '$komm'
					WHERE tunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='error'>".sprintf(t('Poistit %s:n laskun tunnuksella %d.'), $trow['nimi'],$tunnus)."</font><br>";

		$tunnus = '';
		$tee = '';
	}

	if ($tee == 'W' and $komm == '') {
		echo "<font class='error'>".t('Anna pysäytyksen syy')."</font>";
		$tee = 'Z';
	}

	if ($tee == 'V' and $komm == '') {
		echo "<font class='error'>".t('Anna kommentti')."</font>";
		$tee = '';
	}

	// Lasku laitetaan holdiin käyttöliittymä
	if ($tee == 'Z') {
		$query = "	SELECT tunnus, tapvm, erpcm 'eräpvm', ytunnus, nimi, postitp, round(summa * vienti_kurssi, 2) 'kotisumma', summa, valkoodi, comments
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		$trow = mysql_fetch_array ($result);

		echo "<tr>";

		for ($i=1; $i<mysql_num_fields($result); $i++) {
			if ($i == mysql_num_fields($result)-1) {
				if ($trow[$i] != "") {
					echo "<tr><td colspan='".mysql_num_fields($result)."'><font class='error'>$trow[$i]</font></td>";
				}
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}

		echo "</tr></table>";

		echo "<br><table><tr><td>".t("Anna syy kierron pysäytykselle")."</td>";
		echo "	<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='W'>
				<input type='hidden' name = 'nayta' value='$nayta'>
				<input type='hidden' name='tunnus' value='$trow[tunnus]'>";

		echo "	<td><input type='text' name='komm' size='25'><input type='Submit' value='".t("Pysäytä laskun kierto")."'></td>
				</form>
				</tr>
				</table>";

		require ("inc/footer.inc");
		exit;
	}

	// Lasku laitetaan holdiin
	if ($tee == 'W') {
		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow = mysql_fetch_array($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];

		$query = "UPDATE lasku set comments = '$komm', alatila='H' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tunnus = '';
		$tee = '';
	}

	// Laskua kommentoidaan
	if ($tee == 'V') {
		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) !=1 ) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow = mysql_fetch_array($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];

		$query = "UPDATE lasku set comments = '$komm' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tee = '';
	}

	// Hyväksyntälistaa muutetaan
	if ($tee == 'L') {
		$query = "	SELECT *
					FROM lasku
					WHERE tunnus='$tunnus' and
					yhtio = '$kukarow[yhtio]' and
					hyvaksyja_nyt='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus<br>".t('Paina reload nappia')."</font>";
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		if ($laskurow['hyvak1'] != $laskurow['hyvaksyja_nyt'] or $laskurow['hyvaksynnanmuutos'] == '') {
			echo pupe_eror('lasku on väärässä tilassa');
			exit;
		}

		$query = "	UPDATE lasku SET
					hyvak2='$hyvak[2]',
					hyvak3='$hyvak[3]',
					hyvak4='$hyvak[4]',
					hyvak5='$hyvak[5]'
                    WHERE yhtio = '$kukarow[yhtio]'  and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tee = '';

		echo "<font class='message'>" . t("Hyväksyntäjärjestys").": $laskurow[hyvak1]";
		if ($hyvak[2] != '') echo " -&gt; $hyvak[2]";
		if ($hyvak[3] != '') echo " -&gt; $hyvak[3]";
		if ($hyvak[4] != '') echo " -&gt; $hyvak[4]";
		if ($hyvak[5] != '') echo " -&gt; $hyvak[5]";
		echo "</font><br><br>";
	}

	if ($tee == 'H') {
		// Lasku merkitään hyväksytyksi, tehdään timestamp ja päivitetään hyvaksyja_nyt
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus' and
					hyvaksyja_nyt = '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) !=1 ) {
			echo t('lasku kateissa') . "$tunnus<br>".t('Paina reload nappia')."</font>";
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		// Kuka hyväksyi??
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak1']) {
			$kentta = "h1time";
			$laskurow['h1time'] = "99";
		}
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak2']) {
			$kentta = "h2time";
			$laskurow['h2time'] = "99";
		}
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak3']) {
			$kentta = "h3time";
			$laskurow['h3time'] = "99";
		}
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak4']) {
			$kentta = "h4time";
			$laskurow['h4time'] = "99";
		}
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak5']) {
			$kentta = "h5time";
			$laskurow['h5time'] = "99";
		}

		// Kuka hyväksyy seuraavaksi??
		if ($laskurow['h5time'] == '0000-00-00 00:00:00' and strlen($laskurow['hyvak5']) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak5'];
		}
		if ($laskurow['h4time'] == '0000-00-00 00:00:00' and strlen($laskurow['hyvak4']) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak4'];
		}
		if ($laskurow['h3time'] == '0000-00-00 00:00:00' and strlen($laskurow['hyvak3']) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak3'];
		}
		if ($laskurow['h2time'] == '0000-00-00 00:00:00' and strlen($laskurow['hyvak2']) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak2'];
		}
		if ($laskurow['h1time'] == '0000-00-00 00:00:00' and strlen($laskurow['hyvak1']) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak1'];
		}

		$mapvm = '0000-00-00'; // Laskua ei oletuksena merkata maksetuksi

		// Tämä ei olekaan lasku vaan hyväksynnässä oleva tosite!
		if ($laskurow['tila'] != 'X') {

			$tila = "H";
			$viesti = t("Seuraava hyväksyjä on")." '" .$hyvaksyja_nyt ."'";

	        if (strlen($hyvaksyja_nyt) == 0) {

				// Suoraveloitus merkitään heti maksua odottavaksi
	        	if ($laskurow['suoraveloitus'] != '') {

	        		// Jotta tiedämme seuraavan tilan pitää tutkia toimittajaa
	        		$tila='Q';
	        		$viesti = t("Suoraveloituslasku odottaa nyt suorituksen kuittausta");

					// #TODO eikö tässä pitäisi olla liitostunnus?!?
	        		$query = "	SELECT *
								FROM toimi
								WHERE yhtio='$kukarow[yhtio]' and
								ytunnus = '$laskurow[ytunnus]'";
					$result = mysql_query($query) or pupe_error($query);

					// Toimittaja löytyi
					if (mysql_num_rows($result) > 0) {
						$toimirow = mysql_fetch_array($result);

						if ($toimirow['oletus_suoravel_pankki'] > 0) {
							$tila = 'Y';
							$viesti = t("Suoraveloituslasku on merkitty suoritetuksi");
							$mapvm = $laskurow['erpcm'];
						}
					}
	        	}
	        	else {
					$tila = 'M';
					$viesti = t("Lasku on valmis maksettavaksi!");
				}
	        }
		}
		else {
			$viesti = t("Tosite on hyväksytty")."!";
			$tila = 'X';
		}

        $query = "	UPDATE lasku SET
					$kentta = now(),
					hyvaksyja_nyt = '$hyvaksyja_nyt',
					tila = '$tila',
			 		alatila = '',
					mapvm='$mapvm'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		echo "'$laskurow[hyvaksyja_nyt]' ".t("hyväksyi laskun")." $viesti<br><br>";

		$tunnus = '';
		$tee = '';
	}

	if ($tee == 'P') {
		// Olemassaolevaa tiliöintiä muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointi
					WHERE tunnus = '$rtunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tiliöintiä ei löydy")."! $query";
			exit;
		}

		$tiliointirow=mysql_fetch_array($result);

		$tili		= $tiliointirow['tilino'];
		$kustp		= $tiliointirow['kustp'];
		$kohde		= $tiliointirow['kohde'];
		$projekti	= $tiliointirow['projekti'];
		$summa		= $tiliointirow['summa'];
		$vero		= $tiliointirow['vero'];
		$selite		= $tiliointirow['selite'];
		$tositenro  = $tiliointirow['tosite'];

		$ok = 1;

		// Etsitään kaikki tiliöintirivit, jotka kuuluvat tähän tiliöintiin ja lasketaan niiden summa
		$query = "	SELECT sum(summa)
					FROM tiliointi
					WHERE aputunnus = '$rtunnus' and
					yhtio = '$kukarow[yhtio]' and
					korjattu = ''
					GROUP BY aputunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {
			$summarow = mysql_fetch_array($result);
			$summa += $summarow[0];

			$query = "	UPDATE tiliointi SET
						korjattu = '$kukarow[kuka]',
						korjausaika = now()
						WHERE aputunnus = '$rtunnus' and
						yhtio = '$kukarow[yhtio]' and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE tunnus = '$rtunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "E"; // Näytetään miltä tosite nyt näyttää
	}

	if ($tee == 'U') {
		// Lisätään tiliöintirivi

		$summa = str_replace ( ",", ".", $summa);
		$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?

		require ("inc/tarkistatiliointi.inc");

		$tiliulos = $ulos;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Laskua ei enää löydy! Systeemivirhe!");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

 		// Tarvitaan kenties tositenro
		if ($kpexport == 1 or strtoupper($yhtiorow['maakoodi']) != 'FI') {

			if ($tositenro != 0) {
				$query = "	SELECT tosite
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]' and
							ltunnus = '$tunnus' and
							tosite = '$tositenro'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");
					exit;
				}
			}
			else {
				// Tällä ei vielä ole tositenroa. Yritetään jotain

				 // Tälle saamme tositenron ostoveloista
				if ($laskurow['tapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$tunnus' and
								tapvm = '$tiliointipvm' and
								tilino = '$yhtiorow[ostovelat]' and
								summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");
						exit;
					}

					$tositerow = mysql_fetch_array ($result);
					$tositenro = $tositerow['tosite'];
				}

 				// Tälle saamme tositenron ostoveloista
				if ($laskurow['mapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$tunnus' and
								tapvm = '$tiliointipvm' and
								tilino = '$yhtiorow[ostovelat]' and
								summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");
						exit;
					}

					$tositerow = mysql_fetch_array ($result);
					$tositenro = $tositerow['tosite'];
				}
			}
		}

		if ($ok != 1) {
			require ("inc/teetiliointi.inc");
		}
	}

	if (strlen($tunnus) != 0) {

		// Lasku on valittu ja sitä tiliöidään
		$query = "	SELECT *,
					concat_ws('@', laatija, luontiaika) kuka, if(kapvm != '0000-00-00',
					concat_ws('<br>',kapvm,erpcm),erpcm) erpcmkapcm,
					round(summa * vienti_kurssi, 2) kotisumma
					FROM lasku
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Lasku katosi")."</b><br>";
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Laatija/Laadittu")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Eräpvm/Kapvm")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[kuka]</td>";

		if ($kukarow['taso'] == 2 and $laskurow['hyvak1'] == $kukarow['kuka']) {
			echo "<td><a href='$PHP_SELF?tee=M&tunnus=$tunnus'>$laskurow[tapvm]</a></td>";
		}
		else {
			echo "<td>$laskurow[tapvm]</td>";
		}

		if ($laskurow['suoraveloitus'] != '') {
			echo "<td><font class = 'error'>".t("Suoraveloitus")."</font></td>";
		}
		else {
			echo "<td>$laskurow[erpcmkapcm]</td>";
		}

		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[ytunnus]</td>";
		echo "<td>$laskurow[nimi]</td>";
		echo "<td>$laskurow[postitp]</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Summa yhtiön valuutassa")."</th>";
		echo "<th>".t("Summa laskun valuutassa")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[kotisumma] $yhtiorow[valkoodi]</td>";
		echo "<td>$laskurow[summa] $laskurow[valkoodi]</td>";

		if ($laskurow["ebid"] != "") {
			$ebid = $laskurow["ebid"];
			require ("inc/ebid.inc");
			echo "<td><a href='$url'>".t("Näytä lasku")."</a></td>";
		}
		else {
			echo "<td>".t("Paperilasku")."</td>";
		}

		// Näytetään poistonappi, jos se on sallittu
		if (($kukarow['taso'] == 1 or $kukarow['taso'] == 2) and $oikeurow['paivitys'] == '1') {

			echo "	<td class='back'><form action = '$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='tee' value='D'>
					<input type='hidden' name = 'nayta' value='$nayta'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='Submit' value='".t("Poista lasku")."'>
					</form></td>";

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
							msg = '".t("Haluatko todella poistaa tämän laskun ja sen kaikki tiliöinnit? Tämä voi olla kirjanpitorikos!")."';
							return confirm(msg);
						}
					</SCRIPT>";
		}

		echo "</tr>";

		if ($laskurow["h1time"] == "0000-00-00 00:00:00") $laskurow["h1time"] = "";
		if ($laskurow["h2time"] == "0000-00-00 00:00:00") $laskurow["h2time"] = "";
		if ($laskurow["h3time"] == "0000-00-00 00:00:00") $laskurow["h3time"] = "";
		if ($laskurow["h4time"] == "0000-00-00 00:00:00") $laskurow["h4time"] = "";
		if ($laskurow["h5time"] == "0000-00-00 00:00:00") $laskurow["h5time"] = "";

		if ($laskurow["hyvak1"] != "") {
			echo "<tr><th>".t("Hyväksyjät")."</th><th colspan='2'>".t("Hyväksytty")."</th></tr>";
			echo "<tr><td>1. $laskurow[hyvak1]</td><td colspan='2'>$laskurow[h1time]</td></tr>";
		}
		if ($laskurow["hyvak2"] != "") echo "<tr><td>2. $laskurow[hyvak2]</td><td colspan='2'>$laskurow[h2time]</td></tr>";
		if ($laskurow["hyvak3"] != "")echo "<tr><td>3. $laskurow[hyvak3]</td><td colspan='2'>$laskurow[h3time]</td></tr>";
		if ($laskurow["hyvak4"] != "")echo "<tr><td>4. $laskurow[hyvak4]</td><td colspan='2'>$laskurow[h4time]</td></tr>";
		if ($laskurow["hyvak5"] != "")echo "<tr><td>5. $laskurow[hyvak5]</td><td colspan='2'>$laskurow[h5time]</td></tr>";

		echo "</table>";

		if ($laskurow["comments"] != "") {
			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Kommentit")."</th></tr>";
			echo "<tr><td colspan='2'>$laskurow[comments]</td></tr>";
			echo "</table>";
		}

		echo "</td></tr></table>";

		// ykkös ja kakkostasolla voidaan antaa kommentti
		if ($kukarow['taso'] == 1 or $kukarow['taso'] == 2) {
			echo "<br><table>";
			// Mahdollisuus antaa kommentti
			echo "	<form name='kommentti' action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='V'>
					<input type='hidden' name = 'nayta' value='$nayta'>
					<input type='hidden' name='tunnus' value = '$tunnus'>";

			echo "	<tr>
					<th colspan='2'>Lisää kommentti</th>
					</tr>";

			echo "	<tr>
					<td><input type='text' name='komm' value='' size='50'></td>
					<td><input type='Submit' value='".t("Lisää kommentti")."'></td>
					</tr>";

			echo "</form>";
			echo "</table>";
		}

		echo "<br><table>";
		echo "<tr>";

		// Jos laskua hyvaksyy ensimmäinen henkilö ja laskulla on annettu mahdollisuus hyvksyntälistan muutokseen näytetään se!";
		if ($laskurow['hyvak1'] == $laskurow['hyvaksyja_nyt'] and $laskurow['hyvaksynnanmuutos'] != '') {

			echo "<td class='back' valign='top'><table>";

			$hyvak[1] = $laskurow['hyvak1'];
			$hyvak[2] = $laskurow['hyvak2'];
			$hyvak[3] = $laskurow['hyvak3'];
			$hyvak[4] = $laskurow['hyvak4'];
			$hyvak[5] = $laskurow['hyvak5'];

			echo "<form name='uusi' action = '$PHP_SELF' method='post'>
					 <input type='hidden' name='tee' value='L'>
					 <input type='hidden' name = 'nayta' value='$nayta'>
					 <input type='hidden' name='tunnus' value='$tunnus'><tr>";

			echo "<tr><th colspan='2'>".t("Hyväksyjät")."</th></tr>";

			$query = "SELECT kuka, nimi
			          FROM kuka
			          WHERE yhtio = '$kukarow[yhtio]' and hyvaksyja = 'o'
			          ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = '';

			echo "<tr><td>";

			// Täytetään 4 hyväksyntäkenttää (ensinmäinen on jo käytössä)
			for ($i=2; $i<6; $i++) {

				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if ($hyvak[$i] == $vrow['kuka']) {
						$sel = "selected";
					}
					$ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]";
				}

				// Käydään sama data läpi uudestaan
				if (!mysql_data_seek ($vresult, 0)) {
					echo "mysql_data_seek failed!";
					exit;
				}

				echo "<select name='hyvak[$i]'>
				      <option value=''>".t("Ei kukaan")."
				      $ulos
				      </select><br>";
				$ulos = "";
			}

			echo "	</td>
					<td valign='top'><input type='Submit' value='".t("Muuta lista")."'></td>
					</tr></form>";

			echo "</table></td><td width='30px' class='back'></td>";
		}

		echo "</tr></table>";

		if ($ok != 1) {
			// Annetaan tyhjät tiedot, jos rivi oli virheetön
			$tili      = '';
			$kustp     = '';
			$kohde     = '';
			$projekti  = '';
			$summa     = '';
			$selite    = '';
			$vero      = alv_oletus();
		}

		// Tätä ei siis tehdä jos kyseessä on kevenetty versio
		if ($kukarow['taso'] == 1 or $kukarow['taso'] == 2) {

			echo "<table>
					<tr>
					<th colspan='2'>".t("Tili")."</th>
					<th>".t("Tarkenne")."</th>
					<th>".t("Summa")."</th>
					<th>".t("Vero")."</th>
					<th>".t("Selite")."</th>
					<th>".t("Tee")."</th></tr>";

			// vaan kakkostasolla saa tehdä muutoksia
			if ($kukarow["taso"] == 2) {

				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and
							tyyppi = 'K' and
							kaytossa <> 'E'
							ORDER BY nimi";
				$result = mysql_query($query) or pupe_error($query);

				$ulos = "<select name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa")."";

				while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow['tunnus'] == $kustp) {
						$valittu = "selected";
					}
					$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
				}

				$ulos .= "</select><br>";

				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and
							tyyppi = 'O' and
							kaytossa <> 'E'
							ORDER BY nimi";
				$result = mysql_query($query) or pupe_error($query);

				$ulos .= "<select name = 'kohde'><option value = ' '>".t("Ei kohdetta")."";

				while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow['tunnus'] == $kohde) {
						$valittu = "selected";
					}
					$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
				}

				$ulos .= "</select><br>";

				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and
							tyyppi = 'P' and
							kaytossa <> 'E'
							ORDER BY nimi";
				$result = mysql_query($query) or pupe_error($query);

				$ulos .= "<select name = 'projekti'><option value = ' '>".t("Ei projektia")."";

				while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow['tunnus'] == $projekti) {
						$valittu = "selected";
					}
					$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
				}
				$ulos .= "</select>";

				echo "	<form name='uusi' action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value = 'U'>
						<input type='hidden' name = 'nayta' value='$nayta'>
						<input type='hidden' name='tunnus' value = '$tunnus'>";

				echo "<tr><td colspan='2'>";

				if ($tiliulos == '') {
					echo "<input type='text' name='tili' value = '$tili' size='12'>";
				}
				else {
					echo "$tiliulos";
				}
				echo "	</td>
						<td>
							$ulos
						</td>
						<td>
							<input type='text' name='summa' value = '$summa'>
						</td>";

				// Jos alvit on hardkoodattu tileihin (hui, kauheaa, mutta joku niin tahtoo), niin salasanat.php:ssä
				// on määritettävä $hardcoded_alv=1;
				if ($hardcoded_alv != 1) {
					echo "<td>".alv_popup('vero',$vero)."</td>";
				}
				else {
					echo "<td></td>";
				}

				echo "
						<td>
							<input type='text' name='selite' size = '8' value = '$selite'>
						</td>
						<td align='center'>
							$virhe <input type='Submit' value = '".t("Lisää")."'>
						</td>
					</tr></form>";
			}

			$formi = "uusi";
			$kentta = "tili";

			// Näytetään vanhat tiliöinnit muutosta varten
			$naytalisa = '';
			if ($nayta == '') $naytalisa = "and tapvm='" . $laskurow['tapvm'] . "'";

			$query = "	SELECT tiliointi.tunnus, tiliointi.tilino, tili.nimi, kustp, kohde, projekti, summa, vero, selite, lukko, tapvm
					  	FROM tiliointi
						LEFT JOIN tili USING  (yhtio,tilino)
					  	WHERE ltunnus = '$tunnus' and
						tiliointi.yhtio = '$kukarow[yhtio]' and
						korjattu = ''
						$naytalisa";
			$result = mysql_query($query) or pupe_error($query);

			while ($tiliointirow=mysql_fetch_array ($result)) {

				echo "<tr>";

				// Ei näytetä ihan kaikkea
				for ($i=1; $i<mysql_num_fields($result)-2 ; $i++) {

					 // huh mikä häkki, mutta tarkoituksena saada aikaan yksi kenttä
					if ($i == 3) {

						echo "<td>";

						// Meillä on kustannuspaikka
						if (strlen($tiliointirow[$i]) > 0) {
							$query = "	SELECT nimi
										FROM kustannuspaikka
										WHERE yhtio = '$kukarow[yhtio]' and
										tunnus = '$tiliointirow[$i]' and
										tyyppi = 'K'";
							$xresult = mysql_query($query) or pupe_error($query);
							$xrow = mysql_fetch_array ($xresult);
							echo "$xrow[nimi]/";
						}

						$i++;

						// Meillä on kohde
						if (strlen($tiliointirow[$i]) > 0) {
							$query = "	SELECT nimi
										FROM kustannuspaikka
										WHERE yhtio = '$kukarow[yhtio]' and
										tunnus = '$tiliointirow[$i]' and
										tyyppi = 'O'";
							$xresult = mysql_query($query) or pupe_error($query);
							$xrow = mysql_fetch_array ($xresult);
							echo "$xrow[nimi]/";
						}

						$i++;

						 // Meillä on projekti
						if (strlen($tiliointirow[$i]) > 0) {
							$query = "	SELECT nimi
										FROM kustannuspaikka
										WHERE yhtio = '$kukarow[yhtio]' and
										tunnus = '$tiliointirow[$i]' and
										tyyppi = 'P'";
							$xresult = mysql_query($query) or pupe_error($query);
							$xrow = mysql_fetch_array ($xresult);
							echo "$xrow[nimi]/";
						}
						echo "</td>";
					}
					else {
						echo "<td>$tiliointirow[$i]</td>";
					}
					if ($i == 6) {
						$totsum += $tiliointirow[$i];
					}

				}

				// Riviä saa muuttaa vaan jos taso 2
				if ($tiliointirow['lukko'] != 1 and $tiliointirow['tapvm'] == $laskurow['tapvm'] and $kukarow["taso"] == 2) {
					echo "	<form action = '$PHP_SELF' method='post'>
							<input type='hidden' name = 'nayta' value='$nayta'>
							<input type='hidden' name='tunnus' value = '$tunnus'>
							<input type='hidden' name='rtunnus' value = '$tiliointirow[tunnus]'>
							<input type='hidden' name='tee' value = 'P'>
							<input type='hidden' name = 'nayta' value='$nayta'>
							<td align='center'><input type='Submit' value = '".t("Muuta")."'></td>
							</tr></form>";
				}
				else {
					echo "	<td>".t("Tiliöinti on lukittu")."</td>
							</tr>";
				}
			}

			// Täsmääkö tiliöinti laskun kanssa??
			$yhtok = t("Tiliöinti kesken");
			$ok = 0;

			if (round($totsum,2) == 0) {
				$yhtok = "".t("Tiliöinti ok")."!";
				$ok = 1;
			}

			$totsum = sprintf("%.2f", $totsum * -1);
			$tila = '';

			if ($nayta == 'on') $tila = 'checked';

			echo "	<tr>
					<td colspan='2'>".t("Yhteensä")."</td>
					<td>$yhtok</td>
					<td>$totsum</td>
					<td></td>
					<td>Näytä kaikki</td>
					<form action = '$PHP_SELF' method='post' onchange='submit()'>
					<input type='hidden' name = 'tunnus' value='$tunnus'>
					<input type='hidden' name='tee' value = ''>
					<td><input type='checkbox' onclick='submit();' name = 'nayta' $tila></td>
					</form>
					</tr>";
			echo "</table>";

			// Onko tiliöinti ok?
			echo "<table><tr>";
			if ($ok == 1) {
				echo "<td class='back'><form action = '$PHP_SELF' method='post'>
							<input type='hidden' name = 'tunnus' value='$tunnus'>
							<input type='hidden' name = 'tee' value='H'>
							<input type='Submit' value='".t("Hyväksy tiliöinti ja lasku")."'>
							</form></td>";
			}

			// Näytetään alv-erittely, jos on useita kantoja.
			if ($naytalisa != '') {
				$query = "	SELECT vero, sum(summa) veroton, round(sum(summa*vero/100),2) 'veron määrä', round(sum(summa*(1+vero/100)),2) verollinen from tiliointi
							WHERE ltunnus = '$tunnus' and
							tiliointi.yhtio = '$kukarow[yhtio]' and
							korjattu='' and
							tilino not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkallaolevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]')
							GROUP BY 1";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 1) {

					echo "<td class='back'><table><tr>";

					for ($i = 0; $i < mysql_num_fields($result); $i++) {
						echo "<th>" . t(mysql_field_name($result,$i))."</th>";
					}

					echo "</tr>";

					while ($tiliointirow=mysql_fetch_array ($result)) {

						echo "<tr>";

						for ($i=0; $i<mysql_num_fields($result); $i++) {
							echo "<td>$tiliointirow[$i]</td>";
						}

						echo "</tr>";

					}

					echo "</table></td>";

				}
			}

			if ($iframe == "" and $laskurow["ebid"] != "") {
		  		echo "<form action = '$PHP_SELF' method='post'>
		  				<input type='hidden' name = 'tunnus' value='$tunnus'>
		  				<input type='hidden' name = 'iframe' value='yes'>
		  				<input type='hidden' name = 'nayta' value='$nayta'>
		  				<input type='hidden' name = 'tee' value = ''>
		  				<td class='back'><input type='Submit' value='".t("Avaa lasku tähän ikkunaan")."'></td>
						</form>";
			}
			elseif ($iframe == 'yes' and $laskurow["ebid"] != "") {
		  		echo "<form action = '$PHP_SELF' method='post'>
		  				<input type='hidden' name = 'tunnus' value='$tunnus'>
		  				<input type='hidden' name = 'iframe' value=''>
		  				<input type='hidden' name = 'nayta' value='$nayta'>
		  				<input type='hidden' name = 'tee' value = ''>
		  				<td class='back'><input type='Submit' value='".t("Sulje lasku")."'></td>
						</form>";
			}

			echo "</tr></table>";

		}

		if ($kukarow['taso'] == 9) {

			// Kevennetyn käyttöliittymä alkaa tästä
			echo "<table><tr>";

			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name = 'tunnus' value='$tunnus'>
					<input type='hidden' name = 'tee' value='H'>
					<td class='back'><input type='Submit' value='".t("Hyväksy lasku")."'></td>
					</form>";

			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='Z'>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<td class='back'><input type='Submit' value='".t("Pysäytä laskun käsittely")."'></td>
					</form>";

			if ($iframe == "" and $laskurow["ebid"] != "") {
		  		echo "<form action = '$PHP_SELF' method='post'>
		  				<input type='hidden' name = 'tunnus' value='$tunnus'>
		  				<input type='hidden' name = 'iframe' value='yes'>
		  				<input type='hidden' name = 'nayta' value='$nayta'>
		  				<input type='hidden' name = 'tee' value = ''>
		  				<td class='back'><input type='Submit' value='".t("Avaa lasku tähän ikkunaan")."'></td>
						</form>";
			}
			elseif ($iframe == 'yes' and $laskurow["ebid"] != "") {
		  		echo "<form action = '$PHP_SELF' method='post'>
		  				<input type='hidden' name = 'tunnus' value='$tunnus'>
		  				<input type='hidden' name = 'iframe' value=''>
		  				<input type='hidden' name = 'nayta' value='$nayta'>
		  				<input type='hidden' name = 'tee' value = ''>
		  				<td class='back'><input type='Submit' value='".t("Sulje lasku")."'></td>
						</form>";
			}

			echo "</tr></table><br>";
		}

		if ($iframe == 'yes' and $laskurow["ebid"] != "") {
			$ebid = $laskurow["ebid"];
			require ("inc/ebid.inc");
			echo "<iframe src='$url' name='alaikkuna' width='100%' height='60%' align='bottom' scrolling='auto'></iframe>";
		}

	}
	else {

		// Tällä ollaan, jos olemme vasta valitsemassa laskua
		if ($nayta == '') {
			$query = "	SELECT count(*) FROM lasku
				 		WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukarow[yhtio]' and alatila = 'H' and tila != 'D'
				  		ORDER BY erpcm";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array ($result);

			if ($trow[0] > 0) {
				echo "<a href='$PHP_SELF?nayta=1'><font class='error'>". sprintf(t('Sinulla on %d pysäytettyä laskua'), $trow[0]) . "</font></a><br><br>";
			}
		}

		if ($nayta != '') $nayta = '';
		else $nayta = "and alatila != 'H'";

		$query = "	SELECT *, if(kapvm!='0000-00-00',concat_ws('<br>',kapvm,erpcm),erpcm) erpcmkapcm, round(summa * vienti_kurssi, 2) kotisumma
				 	FROM lasku
				  	WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tila != 'D'
					$nayta
				  	ORDER BY if(kapvm!='0000-00-00',kapvm,erpcm)";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Sinulla ei ole hyväksymättömiä laskuja")."</b><br>";
			exit;
		}

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Eräpvm/Kapvm")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "<th>".t("Yhtiön valuutassa")."</th>";
		echo "<th>".t("Laskun valuutassa")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array ($result)) {

			echo "<tr>";

			 // Eli vain tasolla 1/2 ja ensimmäiselle hyväksyjälle.
			if (($kukarow['taso'] == '1' or $kukarow["taso"] == '2') and $trow['hyvak1'] == $kukarow['kuka']) {
				echo "<td valign='top'><a href='$PHP_SELF?tee=M&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</a></td>";
			}
			else {
				echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
			}

			echo "<td valign='top'>".tv1dateconv($trow["erpcmkapcm"])."</td>";
			echo "<td valign='top'>$trow[ytunnus]</td>";

			if ($trow['comments'] != '') {
				echo "<td valign='top'>$trow[nimi]<br><font class='error'>$trow[comments]</font></td>";
			}
			else {
				echo "<td valign='top'>$trow[nimi]</td>";
			}

			echo "<td valign='top'>$trow[postitp]</td>";
			echo "<td valign='top' style='text-align: right;'>$trow[kotisumma] $yhtiorow[valkoodi]</td>";
			echo "<td valign='top' style='text-align: right;'>$trow[summa] $trow[valkoodi]</td>";

			if ($trow["ebid"] != "") {
				$ebid = $trow["ebid"];
				require ("inc/ebid.inc");
				echo "<td valign='top'><a href='$url'>".t("Näytä lasku")."</a></td>";
			}
			else {
				echo "<td valign='top'>".t("Paperilasku")."</td>";
			}

			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value='$trow[tunnus]'>
					<td class='back' valign='top'><input type='Submit' value='".t("Valitse")."'></td>
					</form>";

			// ykkös ja kakkos tason spessuja
			if ($kukarow['taso'] == 1 or $kukarow['taso'] == 2) {

				// Mahdollisuus laittaa lasku holdiin
				if ($trow['alatila'] != 'H') {
					echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='Z'>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>
						<td class='back' valign='top'><input type='Submit' value='".t("Pysäytä")."'></td></form>";
				}
				else {
					echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='Z'>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>
						<td class='back' valign='top'><input type='Submit' value='".t("Lisää kommentti")."'></td></form>";
				}

				if ($oikeurow['paivitys'] == '1') {
					echo "<form action = '$PHP_SELF' method='post' onSubmit = 'return verify()'>
							 <input type='hidden' name='tee' value='D'>
							 <input type='hidden' name = 'nayta' value='$nayta'>
							 <input type='hidden' name='tunnus' value = '$trow[tunnus]'>
							 <td class='back' valign='top'><input type='Submit' value='".t("Poista")."'></td></form>";
				}
			}

			echo "</tr>";
		}
		echo "</table>";

		if ($oikeurow['paivitys'] == '1' and $kukarow['taso'] == 1) {
			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify() {
						msg = '".t("Haluatko todella poistaa tämän laskun ja sen kaikki tiliöinnit? Tämä voi olla kirjanpitorikos!")."';
							return confirm(msg);
					}
					</SCRIPT>";
		}

	}

	require ("inc/footer.inc");

?>