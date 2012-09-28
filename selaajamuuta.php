<?php
	require "inc/parametrit.inc";

	js_popup();

	enable_ajax();

//	if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
//		require ("../inc/tilioinnin_toiminnot.inc");
//	}

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	echo "<font class='head'>".t("Tiliöinnin tarkastus")."</font><hr>";

	if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and $oikeurow['paivitys'] != 1) {
		echo "<font class='errpr'>".t("Yritit päivittää vaikka sinulla ei ole siihen oikeuksia")."</font>";
		exit;
	}

	if ($tunnus != 0) {
		$query = "	SELECT *, concat_ws(' ', tapvm, mapvm) laskunpvm
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query ($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$smlaskurow = mysql_fetch_assoc($result);
			$laskunpvm = $smlaskurow['laskunpvm'];
		}
		else {
			echo t("Lasku katosi")." $tunnus";
			exit;
		}
	}

	if ($laji == '') {
		$laji  = 'O';
	}

	if ($laji == 'M') {
		$selm  = 'SELECTED';
		$lajiv = "tila = 'U'";
	}

	if ($laji == 'O') {
		$selo  = 'SELECTED';
		$lajiv = "tila in ('H', 'Y', 'M', 'P', 'Q')";
	}

	if ($laji == 'MM') {
		$selmm = 'SELECTED';
		$lajiv = "tila = 'U'";
	}

	if ($laji == 'OM') {
		$selom = 'SELECTED';
		$lajiv = "tila = 'Y'";
	}

	if ($laji == 'X') {
		$selx  = 'SELECTED';
		$lajiv = "tila = 'X'";
	}

	// Mikä kuu/vuosi nyt on
	$year = date("Y");
	$kuu  = date("n");

	// Poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
	if ($vv == '') $vv = date("Y",mktime(0,0,0,$kuu,0,$year));
	if ($kk == '') $kk = date("n",mktime(0,0,0,$kuu,0,$year));
	if (strlen($kk) == 1) $kk = "0" . $kk;

	// Ylös hakukriteerit
	if ($viivatut != '') $viivacheck='checked';
	if ($iframe != '') $iframechk='checked';

	echo "<form name = 'valinta' method='post'>
			<table>
			<tr><th>".t("Anna kausi, muodossa kk-vvvv").":</th>
			<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
			<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
			<th>".t("Mitkä tositteet listataan").":</th>
			<td><select name='laji'>
			<option value='M' $selm>".t("myyntilaskut")."
			<option value='O' $selo>".t("ostolaskut")."
			<option value='MM' $selmm>".t("myyntilaskut maksettu")."
			<option value='OM' $selom>".t("ostolaskut maksettu")."
			<option value='X' $selx>".t("muut")."
			</select></td>
			<td><input type='checkbox' name = 'iframe' $iframechk> ".t("Näytä laskut")."</td>
			<td><input type='checkbox' name='viivatut' $viivacheck> ".t("Korjatut")."</td>
			<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr>
			</table>
			</form><br><br>";

	$formi = 'valinta';
	$kentta = 'kk';

	// Vasemmalle laskuluettelo
	if ($vv < 2000) $vv += 2000;
	$lvv=$vv;
	$lkk=$kk;
	$lkk++;

	if ($lkk > 12) {
		$lkk='01';
		$lvv++;
	}

	if ($laji == 'MM' or $laji == 'OM') {
		$pvmlisa = " and mapvm >= '$vv-$kk-01' and mapvm < '$lvv-$lkk-01' ";
	}
	else {
		$pvmlisa = " and tapvm >= '$vv-$kk-01' and tapvm < '$lvv-$lkk-01' ";
	}

	if ($iframe != '') echo "<div style='float: left; width: 55%; padding-right: 10px;'>";

	if ($jarj == '') $jarj = "nimi";

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				$pvmlisa
				and $lajiv
				ORDER BY $jarj, nimi, summa desc";
	$result = pupe_query($query);
	$loppudiv ='';

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font><br><br>";
	}
	else {

		if ($iframe != '') $divwi = "width: 100%;";
		else  $divwi = "";

		echo "<div id='vasen' style='height: 300px; overflow: auto; margin-bottom: 10px; $divwi'>";
		echo "<table style='$divwi'>";
		echo "<tr>";
		echo "<th><a href='$PHP_SELF?tee=$tee&tunnus=$tunnus&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=nimi'>".t("Nimi")."</a></th>";
		echo "<th><a href='$PHP_SELF?tee=$tee&tunnus=$tunnus&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=tapvm'>".t("Tapvm")."</a></th>";
		echo "<th><a href='$PHP_SELF?tee=$tee&tunnus=$tunnus&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=mapvm'>".t("Mapvm")."</a></th>";
		echo "<th><a href='$PHP_SELF?tee=$tee&tunnus=$tunnus&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=summa'>".t("Summa")."</a></th>";
		echo "<th><a href='$PHP_SELF?tee=$tee&tunnus=$tunnus&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=mapvm'>".t("valkoodi")."</a></th>";
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {
			echo "<tr>";

			$ero = "td";
			if ($trow['tunnus']==$tunnus) $ero = "th";

			$komm = "";
			if ($trow['comments'] != '') {
				$loppudiv .= "<div id='div_".$trow['tunnus']."' class='popup' style='width:250px'>";
				$loppudiv .= $trow["comments"]."<br></div>";

				$komm = " <a class='tooltip' id='$trow[tunnus]'><img src='pics/lullacons/alert.png'></a>";
			}

			if ($trow["nimi"] == "") {
				$trow["nimi"] = t("Ei nimeä");
			}

			echo "<$ero><a name='$trow[tunnus]' href='$PHP_SELF?tee=E&tunnus=$trow[tunnus]&iframe=$iframe&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut&jarj=$jarj#$trow[tunnus]'>$trow[nimi]</a>$komm</$ero>";
			echo "<$ero>".tv1dateconv($trow["tapvm"])."</$ero>";
			echo "<$ero>".tv1dateconv($trow["mapvm"])."</$ero>";
			echo "<$ero style='text-align: right;'>$trow[summa]</$ero>";
			echo "<$ero>$trow[valkoodi]</$ero>";
			echo "</tr>";
		}

		echo "</table>";
		echo "</div>";
	}

	if ($iframe != '') echo "<div style='height: 400px; overflow: auto; width: 100%;'>";

	if ($tee == 'P') {
		// Olemassaolevaa tiliöintiä muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tiliöintiä ei löydy")."! $query";

			require ("inc/footer.inc");
			exit;
		}

		$tiliointirow = mysql_fetch_assoc($result);

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
		$query = "	SELECT sum(summa) summa
					FROM tiliointi
					WHERE aputunnus = '$ptunnus'
					and yhtio = '$kukarow[yhtio]'
					and korjattu = ''
					GROUP BY aputunnus";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {
			$summarow = mysql_fetch_assoc($result);
			$summa += $summarow["summa"];

			$query = "	UPDATE tiliointi SET
						korjattu = '$kukarow[kuka]',
						korjausaika = now()
						WHERE aputunnus = '$ptunnus'
						and yhtio = '$kukarow[yhtio]'
						and korjattu = ''";
			$result = pupe_query($query);
		}

		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE tunnus = '$ptunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		$tee = "E";
	}

	if ($tee == 'U') {
		// Lisätään tiliöintirivi
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Laskua ei enää löydy! Systeemivirhe!");

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_assoc($result);

		$summa 			= str_replace ( ",", ".", $summa);
		$selausnimi 	= 'tili'; // Minka niminen mahdollinen popup on?
		$tositetila 	= $laskurow["tila"];
		$tositeliit 	= $laskurow["liitostunnus"];
		$kustp_tark		= $kustp;
		$kohde_tark		= $kohde;
		$projekti_tark	= $projekti;

		require ("inc/tarkistatiliointi.inc");

		$tiliulos = $ulos;

 		// Tarvitaan kenties tositenro
		if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

			if ($tositenro != 0) {
				$query = "	SELECT tosite
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							and ltunnus = '$tunnus'
							and tosite = '$tositenro'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {
					echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

					require ("inc/footer.inc");
					exit;
				}
			}
			else {
				// Tällä ei vielä ole tositenroa. Yritetään jotain
				// Tälle saamme tositenron ostoveloista
				if ($laskurow['tapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite
								FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]'
								and ltunnus = '$tunnus'
								and tapvm = '$tiliointipvm'
								and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
								and summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
						exit;
					}

					$tositerow = mysql_fetch_assoc ($result);
					$tositenro = $tositerow['tosite'];
				}

 				// Tälle saamme tositenron ostoveloista
				if ($laskurow['mapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite
								FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]'
								and ltunnus = '$tunnus'
								and tapvm = '$tiliointipvm'
								and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
								and summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
						exit;
					}

					$tositerow = mysql_fetch_assoc ($result);
					$tositenro = $tositerow['tosite'];
				}
			}
		}

		if ($ok != 1) {
			require ("inc/teetiliointi.inc");
		}

		$tee = "E";
	}

	if ($tee == 'E') {
		// Tositteen tiliöintirivit...
		require "inc/tiliointirivit.inc";
	}

	if ($iframe != '') echo "</div>";
	if ($iframe != '') echo "</div>";

	if ($iframe != '') echo "<div style='height: 710px; overflow: auto; width: 40%;'>";
	//Oikealla laskun kuva

	if ($smlaskurow["tunnus"] > 0) {

		if ($smlaskurow["tila"] == "U") {
			$url = $palvelin2."tilauskasittely/tulostakopio.php?otunnus=$smlaskurow[tunnus]&toim=LASKU&tee=NAYTATILAUS";
		}
		else {
			$urlit = ebid($smlaskurow["tunnus"], TRUE);
			$url = $urlit[0];
		}

		if ($iframe != '') echo "<iframe src='$url' style='width:100%; height: 710px; border: 0px; display: block;'></iFrame>";
		else echo "<br><br><a href='$url' target='Attachment'>".t("Näytä lasku")."</a>";
	}
	if ($iframe != '') echo "</div>";

	echo $loppudiv;

	echo "<div style='float: bottom;'>";
	require ("inc/footer.inc");
	echo "</div>";
?>