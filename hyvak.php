<?php

	if ($_REQUEST["tee"] == "haekeikka") {
		$_GET["ohje"] = "off";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}

	enable_ajax();

	if (!isset($tee)) 			$tee = "";
	if (!isset($tila)) 			$tila = "";
	if (!isset($keikalla)) 		$keikalla = "";
	if (!isset($kutsuja)) 		$kutsuja = "";
	if (!isset($tunnus))		$tunnus = "";
	if (!isset($nayta)) 		$nayta = "";
	if (!isset($iframe)) 		$iframe = "";
	if (!isset($iframe_id)) 	$iframe_id = "";
	if (!isset($naytalisa)) 	$naytalisa = "";
	if (!isset($ok)) 			$ok = "";
	if (!isset($toimittajaid)) 	$toimittajaid = "";
	if (!isset($livesearch_tee)) $livesearch_tee = "";

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	$lopetus = "{$palvelin2}hyvak.php////kutsuja=";

	if ($keikalla == "on") {
		//	T��ll� haetaan laskurow aika monta kertaa, joskus voisi tehd� recodea?
		$query = "	SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$abures = pupe_query($query);
		$laskurow = mysql_fetch_assoc($abures);

		//	Meill� on oikea toimittajaid
		if ($tee == "haekeikka") {

			echo "<br>";

			// Mihin t�m� on listattu Listataan
			//	Jos meill� ei ole viel� toimittajaa valitaan ensin se
			if ($toimittajaid != "" or $ytunnus != "") {
				$keikkamonta = 0;
				$hakutunnus = $ytunnus;
				$hakuid		= $toimittajaid;

				$PHP_SELF = "javascript:ajaxPost('kevyt_toimittajahaku', '{$palvelin2}hyvak.php?', 'keikka', '', '', '', 'post');";

				require ("inc/kevyt_toimittajahaku.inc");
			}

			if ($toimittajaid != "") {
				echo "<table><tr><th>".t("Valittu toimittaja")."</th><td>$toimittajarow[nimi] $toimittajarow[osoite] $toimittajarow[postino] $toimittajarow[postitp] (".tarkistahetu($toimittajarow["ytunnus"]).")</td><td class='back'><a href = 'javascript:sndReq(\"keikka\", \"{$palvelin2}hyvak.php?keikalla=on&tee=haekeikka&tunnus=$tunnus&toimittajaid=\");'>Vaihda toimittaja</a></td></tr></table><br>";

				//	Listataan keikat
				$query = "	SELECT comments, lasku.tunnus otunnus, lasku.laskunro keikka, sum(tilausrivi.rivihinta) varastossaarvo, count(distinct tilausrivi.tunnus) kpl, sum(if (kpl = 0, 0, 1)) varastossa
							FROM lasku
							LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'K'
							and lasku.alatila = ''
							and lasku.liitostunnus = '$toimittajaid'
							and lasku.vanhatunnus = 0
							GROUP BY lasku.laskunro
							ORDER BY lasku.luontiaika";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					//	Haetaan vientitieto..

					//	Kululaskun voi kohdistaa useaan keikkaan
					if (in_array($laskurow["vienti"], array("B","E","H"))) {
						echo "<form id='liita' action='hyvak.php?keikalla=on' method='post' autocomplete='off'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='tee' value = 'liita'>";
						echo "<input type='hidden' name='tunnus' value = '$tunnus'>";
						echo "<input type='hidden' name='toimittajaid' value = '$toimittajaid'>";
						echo "<table><tr><th>".t("Keikka")."</th><th>".t("Kommentit")."</th><th>".t("Rivej�")."/".t("varasossa")."</th><th>".t("Varastonarvo")."</th><th>".t("Summa")."</th></tr>";

						while($row = mysql_fetch_assoc($result)) {
							echo "<tr><td>$row[keikka]</td><td>$row[comments]</td><td align='right'>$row[kpl]/$row[varastossa]</td><td align='right'>$row[varastossaarvo]</td><td>";
							echo "<input type='text' name='liita[".$row["otunnus"]."][liitasumma]' value='$liitasumma' size='10'>";
							echo "</td></tr>";
						}
						echo "<tr><td class='back' colspan='5' align = 'right'><input type='submit' value='".t("Liit� keikkoihin")."'></td></tr></table></form>";
					}
					else {
						echo "<table><tr><th>".t("Keikka")."</th><th>".t("Kommentit")."</th><th>".t("Rivej�")."/".t("varasossa")."</th><th>".t("Varastonarvo")."</th></tr>";
						while($row = mysql_fetch_assoc($result)) {

							$query = "	SELECT sum(summa) summa
										from tiliointi
										where yhtio	= '$kukarow[yhtio]'
										and ltunnus	= '$tunnus'
										and tilino  = '$yhtiorow[alv]'
										and korjattu = ''";
							$alvires = pupe_query($query);
							$alvirow = mysql_fetch_assoc($alvires);
							$summa_kaytettavissa = round((float) $laskurow["summa"] - (float) $alvirow["summa"], 2);

							echo "<tr><td>$row[keikka]</td><td>$row[comments]</td><td align='right'>$row[kpl]/$row[varastossa]</td><td align='right'>$row[varastossaarvo]</td>";
							echo "<td class='back' colspan='5' align = 'right'>
									<form id='liita' action='hyvak.php?keikalla=on' method='post' autocomplete='off'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='tee' value = 'liita'>
									<input type='hidden' name='tunnus' value = '$tunnus'>
									<input type='hidden' name='toimittajaid' value = '$toimittajaid'>
									<input type='hidden' name='liita[".$row["otunnus"]."][liitasumma]' value='' size='10'>
									<input type='submit' value='".t("Liit� keikkaan")."'>
									</form>";
							echo "</td></tr>";
						}
						echo "</table>";
					}
				}
				else {
					echo "Ei avoimia keikkoja..<br>";
				}
			}
			else {
				echo "<form id='toimi' name = 'toimi' action='javascript:ajaxPost(\"toimi\", \"{$palvelin2}hyvak.php?tee=$tee\", \"keikka\", \"\", \"\", \"\", \"post\");' method='post' autocomplete='off'>";
				echo "<input type='hidden' name='tunnus' value = '$tunnus'>";
				echo "<input type='hidden' name='keikalla' value = 'on'>";
				echo "<input type='hidden' name='lopetus' value='$lopetus'>";
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Etsi toimittaja")."</th>";
				echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
				echo "<td class='back'><input type = 'submit' value ='".t("Hae")."'></td>";
				echo "</tr>";
				echo "</table>";
				echo "</form>";
			}

			die("</body></html>");
		}
		elseif ($toimittajaid > 0) {

			//	swapataan oikea tunnus talteen
			$o_tunnus 		= $tunnus;
			$silent 		= "JOO";
			$laskutunnus 	= $tunnus;
			$keikanalatila	= "";
			$tee_kululaskut = $tee;

			if ($tee == "liita") {
				if (count($liita) > 0) {
					foreach ($liita as $l => $v) {
						//	Otetaan originaali laskurow talteen..
						$olaskurow 		= $laskurow;
						$otunnus 		= $l;
						$liitasumma 	= $v["liitasumma"];

						if ($liitasumma <> 0 or !in_array($laskurow["vienti"], array("B","E","H"))) {
							require("tilauskasittely/kululaskut.inc");
						}

						//	Palautetaan Wanha
						$laskurow = $olaskurow;
					}
				}
			}
			else {
				//$tunnus = $otunnus;
				require("tilauskasittely/kululaskut.inc");
			}

			//	Palautetaan tunnus
			$tunnus = $o_tunnus;
			$tee = "";
		}
		else {
			echo "Kui s�� t�nne p��sit?!";
		}
	}

	if ($kutsuja != "MATKALASKU") echo "<font class='head'>".t('Hyv�ksytt�v�t laskusi')."</font><hr><br>";

	$onko_eka_hyvaksyja = FALSE;

	if ((int) $tunnus != 0) {
		$tunnus = mysql_real_escape_string($tunnus);

		$query = "	SELECT tilaustyyppi, hyvak1, h1time, hyvak2, h2time
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$tunnus'";
		$check_res = pupe_query($query);

		if (mysql_num_rows($check_res) == 1) {
			$check_row = mysql_fetch_assoc($check_res);

			if ($check_row['tilaustyyppi'] == "M" and $check_row['hyvak2'] == $kukarow['kuka'] and $check_row["h2time"] == "0000-00-00 00:00:00") {
				// Matkalaskuilla eka hyv�ksyj� on aina matkustaja itse joka ei saa korjata kirjanpitoa, vaan toka hyv�ksyj� vastaa tili�inneist�
				$onko_eka_hyvaksyja = TRUE;
				$eka_hyvaksyja = $check_row['hyvak2'];
			}
			elseif ($check_row['tilaustyyppi'] != "M" and $check_row['hyvak1'] == $kukarow['kuka'] and $check_row["h1time"] == "0000-00-00 00:00:00") {
				// jos ensimm�inen hyv�ksyj� on t�� k�ytt�j� ja ei olla viel� hyv�ksytty
				$onko_eka_hyvaksyja = TRUE;
				$eka_hyvaksyja = $check_row['hyvak1'];
			}

			// Matkalaskun eka hyv�ksyj�, eli matkustaja itse ei saa muuttaa tili�intej�
			if ($check_row['tilaustyyppi'] == "M" and $check_row['hyvak1'] == $kukarow['kuka'] and $check_row["h1time"] == "0000-00-00 00:00:00") {
				if ($kukarow['taso'] == 2 or $kukarow['taso'] == 3) $kukarow['taso'] = 1;
			}
		}
	}

	// halutaan n�hd� otsikko, tai ollaan eka hyv�ksyj� ja ei olla hyv�ksym�ss� laskua
	if ($tee == 'M' or ($onko_eka_hyvaksyja === TRUE and $tee != 'H' and $tee != 'D' and $tee != 'Z' and ($kukarow['taso'] == '2' or $kukarow["taso"] == '3'))) {
		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_assoc($result);

		if ($onko_eka_hyvaksyja) {
			$hyvak_apu_tee  = $tee;
			$hyvak_apu_tila = $tila;

			require ("inc/muutosite.inc");

			$tee  = $hyvak_apu_tee;
			$tila = $hyvak_apu_tila;
		}
	}

 	// Jaaha poistamme laskun!
	if ($tee == 'D' and $oikeurow['paivitys'] == '1') {

		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";

			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_assoc ($result);

		if ($trow["tapvm"] >= $yhtiorow["ostoreskontrakausi_alku"] or ($trow["tapvm"]=="0000-00-00" and $trow["laskutyyppi"] == "M")) {

			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Poisti laskun")."<br>" . $trow['comments'];

			// Ylikirjoitetaan tili�innit
			$query = "	UPDATE tiliointi SET
						korjattu = '$kukarow[kuka]',
						korjausaika = now()
						WHERE ltunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = ''";
			$result = pupe_query($query);

			// Merkataan lasku poistetuksi
			$query = "	UPDATE lasku SET
						alatila = 'H',
						tila = 'D',
						comments = '$komm'
						WHERE tunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			echo "<font class='error'>".sprintf(t('Poistit %s:n laskun tunnuksella %d.'), $trow['nimi'], $tunnus)."</font><br>";
		}
		else {
			echo "<font class='error'>".t('Laskua ei voitu poistaa, se kuuluu lukitulle tilikaudelle')."!</font><br><br>";
		}

		$tunnus = '';
		$tee = '';
	}

	if ($tee == "palauta") {

		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = pupe_query($query);
		if (mysql_num_rows($result) == 0) {
			die(t("Lasku kateissa"));
		}
		$lrow = mysql_fetch_assoc($result);

		if ($viesti == "" or (int) $hyvaksyja == 0) {

			if ($formilta == "true") {
				if ((int) $hyvaksyja == 0) echo "<font class='error'>".t("Valitse kenelle lasku palautetaan.")."</font><br>";

				if ($viesti == "") echo "<font class='error'>".t("Anna syy palautukseen.")."</font><br>";
			}

			$hyvaksyjat = "	<table>
							<tr>
								<th>".t("Kenelle lasku palautetaan")."</th>
							</tr>";


			//	Valitse kenelle palautetaan
			for ($i=1; $i<5; $i++) {
				$haika	= "h".$i."time";
				$haiku	= "hyvak".$i;

				if ($lrow[$haika] != "0000-00-00 00:00:00") {

					$query = "	SELECT nimi
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'
								and kuka = '{$lrow[$haiku]}'";
					$result = pupe_query($query);
					$krow = mysql_fetch_assoc($result);

					if ($hyvaksyja == $i) {
						$sel = "checked";
					}
					else {
						$sel = "";
					}

					$hyvaksyjat .= "<tr><td><input type='radio' name='hyvaksyja' value='$i' $sel> $i. $krow[nimi]</td></tr>";
				}
			}

			$hyvaksyjat .= "</table><br>";

			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tee' value='$tee'>
					<input type='hidden' name='formilta' value='true'>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<br>
					$hyvaksyjat
					<br>
					<table>
						<tr>
							<th>".t("Anna palautuksen syy")."</th>
						</tr>
						<tr>
							<td><input type='text' name = 'viesti' value='$viesti' size = '50'></td>
						</tr>
					</table>
					<input type = 'submit' value = '".t("Palauta")."'>
					</form>
					<br>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<input type = 'submit' value = '".t("Palaa laskun hyv�ksynt��n")."'>
					</form>";

			require("inc/footer.inc");
			exit;
		}
		else {

			//	Palautetaan takaisin edelliselle hyv�ksyj�lle
			if ((int) $hyvaksyja > 0) {
				$upd = "h{$hyvaksyja}time = '0000-00-00 00:00:00', hyvaksyja_nyt = '".$lrow["hyvak{$hyvaksyja}"]."'";
				$kukahyvak = $lrow["hyvak{$hyvaksyja}"];
			}
			else die("Hyv�ksyj� ei kelpaa");

			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukahyvak'";
			$result = pupe_query($query);
			$krow = mysql_fetch_assoc($result);

			// Merkataan lasku poistetuksi
			$query = "	UPDATE lasku SET
						$upd
						WHERE tunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			echo "<font class='message'>".t('Palauttettiin lasku k�ytt�j�lle')." $krow[nimi]</font><br>";

			//	L�hetet��n maili virheen merkiksi!
			mail($krow["eposti"], mb_encode_mimeheader("Hyv�ksym�si lasku palautettiin", "ISO-8859-1", "Q"), t("Hyv�ksym�si lasku toimittajalta %s, %s %s palautettiin korjattavaksi.\n\nSyy: %s \n\nPalauttaja:", $krow["kieli"], $lrow["nimi"], $lrow["summa"], $lrow["valkoodi"], $viesti)." $kukarow[nimi], $yhtiorow[nimi]", "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
			$tunnus = "";
			$tee = "";
		}
	}

	if ($tee == 'W' and $komm == '') {
		echo "<font class='error'>".t('Anna pys�ytyksen syy')."</font>";
		$tee = 'Z';
	}

	if ($tee == 'V' and $komm == '') {
		echo "<font class='error'>".t('Anna kommentti')."</font><br>";
		$tee = '';
	}

	// Lasku laitetaan holdiin k�ytt�liittym�
	if ($tee == 'Z') {
		$query = "	SELECT tunnus, tapvm, erpcm 'er�pvm', ytunnus, nimi, postitp, round(summa * vienti_kurssi, 2) 'kotisumma', summa, valkoodi, comments
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and
					yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";

			require ("inc/footer.inc");
			exit;
		}

		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		$trow = mysql_fetch_assoc($result);

		echo "<tr>";

		for ($i=1; $i<mysql_num_fields($result); $i++) {
			if ($i == mysql_num_fields($result)-1) {
				if ($trow[mysql_field_name($result, $i)] != "") {
					echo "<tr><td colspan='".mysql_num_fields($result)."'><font class='error'>".$trow[mysql_field_name($result,$i)]."</font></td>";
				}
			}
			else {
				echo "<td>".$trow[mysql_field_name($result,$i)]."</td>";
			}
		}

		echo "</tr></table>";

		echo "<br><table><tr><td>".t("Anna syy kierron pys�ytykselle")."</td>";
		echo "	<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='W'>
				<input type='hidden' name = 'nayta' value='$nayta'>
				<input type='hidden' name='tunnus' value='$trow[tunnus]'>";

		echo "	<td><input type='text' name='komm' size='25'><input type='Submit' value='".t("Pys�yt� laskun kierto")."'></td>
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
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";

			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_assoc($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];

		$query = "UPDATE lasku set comments = '$komm', alatila='H' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = pupe_query($query);

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
		$result = pupe_query($query);

		if (mysql_num_rows($result) !=1 ) {
			echo t('lasku kateissa') . "$tunnus</font>";

			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_assoc($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];

		$query = "UPDATE lasku set comments = '$komm' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = pupe_query($query);

		$tee = '';
	}

	// Hyv�ksynt�listaa muutetaan
	if ($tee == 'L') {
		$query = "	SELECT *
					FROM lasku
					WHERE tunnus='$tunnus' and
					yhtio = '$kukarow[yhtio]' and
					hyvaksyja_nyt='$kukarow[kuka]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus<br>".t('Paina reload nappia')."</font>";

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_assoc($result);

		if ($eka_hyvaksyja != $laskurow['hyvaksyja_nyt'] or $laskurow['hyvaksynnanmuutos'] == '') {
			echo pupe_eror('lasku on v��r�ss� tilassa');

			require ("inc/footer.inc");
			exit;
		}

		$query = "	UPDATE lasku SET
					hyvak2 = '$hyvak[2]',
					hyvak3 = '$hyvak[3]',
					hyvak4 = '$hyvak[4]',
					hyvak5 = '$hyvak[5]'
                    WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = pupe_query($query);

		$tee = '';

		echo "<font class='message'>" . t("Hyv�ksynt�j�rjestys").": $laskurow[hyvak1]";
		if ($hyvak[2] != '') echo " -&gt; $hyvak[2]";
		if ($hyvak[3] != '') echo " -&gt; $hyvak[3]";
		if ($hyvak[4] != '') echo " -&gt; $hyvak[4]";
		if ($hyvak[5] != '') echo " -&gt; $hyvak[5]";
		echo "</font><br><br>";
	}

	if ($tee == 'H') {
		// Lasku merkit��n hyv�ksytyksi, tehd��n timestamp ja p�ivitet��n hyvaksyja_nyt
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus' and
					hyvaksyja_nyt = '$kukarow[kuka]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) !=1 ) {
			echo t('lasku kateissa') . "$tunnus<br>".t('Paina reload nappia')."</font>";

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_assoc($result);

		//	Kun tehd��n matkalaskun ensimm�inen hyv�ksynt�..
		if ($laskurow["tilaustyyppi"] == "M" and $laskurow["h1time"]=="0000-00-00 00:00:00") {

			$query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$toimires = pupe_query($query);
			$toimirow = mysql_fetch_assoc($toimires);

			if ($toimirow["oletus_erapvm"] > 0) $erpaivia = $toimirow["oletus_erapvm"];
			else $erpaivia = 1;

			//	P�ivitet��n tapvm ja viesti laskulle
			$viesti = t("Matkalasku")." ".date("d").".".date("m").".".date("Y");
			$query = " UPDATE lasku set tapvm=now(), erpcm=DATE_ADD(now(), INTERVAL $erpaivia DAY), viesti ='$viesti' where yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
			$updres = pupe_query($query);

			//	P�ivitet��n tili�intien tapvm as well
			$query = " UPDATE tiliointi set tapvm=now() where yhtio = '$kukarow[yhtio]' and ltunnus='$tunnus'";
			$updres = pupe_query($query);

		}

		// Kuka hyv�ksyi??
		if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak1'] and $laskurow["h1time"]=="0000-00-00 00:00:00") {
			$kentta = "h1time";
			$laskurow['h1time'] = "99";
		}
		elseif ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak2'] and $laskurow["h2time"]=="0000-00-00 00:00:00") {
			$kentta = "h2time";
			$laskurow['h2time'] = "99";
		}
		elseif ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak3'] and $laskurow["h3time"]=="0000-00-00 00:00:00") {
			$kentta = "h3time";
			$laskurow['h3time'] = "99";
		}
		elseif ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak4'] and $laskurow["h4time"]=="0000-00-00 00:00:00") {
			$kentta = "h4time";
			$laskurow['h4time'] = "99";
		}
		elseif ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak5'] and $laskurow["h5time"]=="0000-00-00 00:00:00") {
			$kentta = "h5time";
			$laskurow['h5time'] = "99";
		}

		// Kuka hyv�ksyy seuraavaksi??
		if ($laskurow['h5time'] == '0000-00-00 00:00:00' and strlen(trim($laskurow['hyvak5'])) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak5'];
		}
		if ($laskurow['h4time'] == '0000-00-00 00:00:00' and strlen(trim($laskurow['hyvak4'])) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak4'];
		}
		if ($laskurow['h3time'] == '0000-00-00 00:00:00' and strlen(trim($laskurow['hyvak3'])) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak3'];
		}
		if ($laskurow['h2time'] == '0000-00-00 00:00:00' and strlen(trim($laskurow['hyvak2'])) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak2'];
		}
		if ($laskurow['h1time'] == '0000-00-00 00:00:00' and strlen(trim($laskurow['hyvak1'])) != 0) {
			$hyvaksyja_nyt = $laskurow['hyvak1'];
		}

		$mapvm = '0000-00-00'; // Laskua ei oletuksena merkata maksetuksi

		// T�m� ei olekaan lasku vaan hyv�ksynn�ss� oleva tosite!
		if ($laskurow['tila'] != 'X') {

			$tila = "H";
			$viesti = t("Seuraava hyv�ksyj� on")." '" .$hyvaksyja_nyt ."'";

	        if (strlen($hyvaksyja_nyt) == 0) {

				// Suoraveloitus merkit��n heti maksua odottavaksi
	        	if ($laskurow['suoraveloitus'] != '') {

	        		// Jotta tied�mme seuraavan tilan pit�� tutkia toimittajaa
	        		$tila = 'Q';
	        		$viesti = t("Suoraveloituslasku odottaa nyt suorituksen kuittausta");

					// #TODO eik� t�ss� pit�isi olla liitostunnus?!?
	        		$query = "	SELECT *
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								and ytunnus = '$laskurow[ytunnus]'";
					$result = pupe_query($query);

					// Toimittaja l�ytyi
					if (mysql_num_rows($result) > 0) {
						$toimirow = mysql_fetch_assoc($result);

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
			$viesti = t("Tosite on hyv�ksytty")."!";
			$tila = 'X';
		}

        $query = "	UPDATE lasku SET
					$kentta = now(),
					hyvaksyja_nyt = '$hyvaksyja_nyt',
					tila 	= '$tila',
			 		alatila = '',
					mapvm	= '$mapvm'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = pupe_query($query);

		echo "<br><font class='message'>'$laskurow[hyvaksyja_nyt]' ".t("hyv�ksyi laskun")." $viesti</font><br><br>";

		$tunnus = '';
		$tee = '';
	}

	if ($tee == 'P') {
		// Olemassaolevaa tili�inti� muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tili�inti� ei l�ydy")."! $query";

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

		// Etsit��n kaikki tili�intirivit, jotka kuuluvat t�h�n tili�intiin ja lasketaan niiden summa
		$query = "	SELECT sum(summa) summa
					FROM tiliointi
					WHERE aputunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]' and
					korjattu = ''
					GROUP BY aputunnus";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {
			$summarow = mysql_fetch_assoc($result);
			$summa += $summarow["summa"];

			$query = "	UPDATE tiliointi SET
						korjattu 	= '$kukarow[kuka]',
						korjausaika = now()
						WHERE aputunnus = '$ptunnus'
						and yhtio = '$kukarow[yhtio]'
						and korjattu = ''";
			$result = pupe_query($query);
		}

		$query = "	UPDATE tiliointi SET
					korjattu 	= '$kukarow[kuka]',
					korjausaika = now()
					WHERE tunnus = '$ptunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		$tee = "E"; // N�ytet��n milt� tosite nyt n�ytt��
	}

	if ($tee == 'U') {
		// Lis�t��n tili�intirivi
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Laskua ei en�� l�ydy! Systeemivirhe!");

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
		if (isset($kpexport) and $kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

			if ($tositenro != 0) {
				$query = "	SELECT tosite
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]' and
							ltunnus = '$tunnus' and
							tosite = '$tositenro'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {
					echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

					require ("inc/footer.inc");
					exit;
				}
			}
			else {
				// T�ll� ei viel� ole tositenroa. Yritet��n jotain
				// T�lle saamme tositenron ostoveloista
				if ($laskurow['tapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite
								FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]'
								and ltunnus = '$tunnus'
								and tapvm 	= '$tiliointipvm'
								and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
								and summa 	= round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
						exit;
					}

					$tositerow = mysql_fetch_assoc($result);
					$tositenro = $tositerow['tosite'];
				}

 				// T�lle saamme tositenron ostoveloista
				if ($laskurow['mapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite
								FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]'
								and ltunnus = '$tunnus'
								and tapvm 	= '$tiliointipvm'
								and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
								and summa 	= round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
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
	}

	if (strlen($tunnus) != 0) {

		// Lasku on valittu ja sit� tili�id��n
		$query = "	SELECT *,
					concat_ws('@', laatija, luontiaika) kuka,
					round(summa * vienti_kurssi, 2) kotisumma
					FROM lasku
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Lasku katosi")."</b><br>";

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_assoc($result);

		//	Tarkistetaan onko t�ll�ist� laskua t�lt� toimittajalta jo kierrossa
		if ($laskurow["laskunro"] != "") {
			$query = "	SELECT *,
						concat_ws('@', laatija, luontiaika) kuka,
						summa,
						valkoodi
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and liitostunnus='$laskurow[liitostunnus]'
						and tila IN ('H','M','P','Q','Y')
						and laskunro = '$laskurow[laskunro]'
						and laskunro != 0
						and tunnus != $laskurow[tunnus]";
			$tarkres = pupe_query($query);

			if (mysql_num_rows($tarkres) != 0) {
				echo "<br><font class = 'error'>".t("HUOM! Toimittajalta on saapunut jo lasku samalla laskunumerolla!")."</font><table>";

				while ($tarkrow = mysql_fetch_assoc($tarkres)) {
					echo "<tr><td class='back'>$tarkrow[summa] $tarkrow[valkoodi]</td><td class='back'>$tarkrow[kuka]</td><td class='back'><a href='muutosite.php?tee=E&tunnus=$tarkrow[tunnus]'>".t("Avaa lasku")."</a></td>";
				}
				echo "</table><br><br>";
			}
		}

		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Laatija/Laadittu")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Er�pvm/Kapvm")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[kuka]</td>";

		if (($kukarow['taso'] == '2' or $kukarow["taso"] == '3') and $onko_eka_hyvaksyja) {

			echo "<td><a href='$PHP_SELF?tee=M&tunnus=$tunnus";

			if (isset($iframe_id)) {
				echo "&iframe_id=$iframe_id";
			}
			elseif ($_POST['iframe_id'] != '') {
				echo "&iframe_id=".mysql_real_escape_string($_POST['iframe_id']);
			}

			if (isset($iframe) and $iframe == 'yes') {
				echo "&iframe=$iframe";
			}

			echo "'>".tv1dateconv($laskurow["tapvm"])."</a></td>";
		}
		else {
			echo "<td>".tv1dateconv($laskurow["tapvm"])."</td>";
		}

		if ($laskurow['suoraveloitus'] != '') {
			echo "<td><font class = 'error'>".t("Suoraveloitus")."</font></td>";
		}
		else {
			echo "<td>";

			echo tv1dateconv($laskurow["erpcm"]);
			if ($laskurow["kapvm"] != "0000-00-00") echo "<br>".tv1dateconv($laskurow["kapvm"]);

			echo "</td>";
		}

		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>".tarkistahetu($laskurow["ytunnus"])."</td>";
		echo "<td>$laskurow[nimi]</td>";
		echo "<td>$laskurow[postitp]</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Summa yhti�n valuutassa")."</th>";
		echo "<th>".t("Summa laskun valuutassa")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[kotisumma] $yhtiorow[valkoodi]</td>";
		echo "<td>$laskurow[summa] $laskurow[valkoodi]</td>";

		echo "<td>".ebid($laskurow["tunnus"]) . "</td>";

		// N�ytet��n poistonappi, jos se on sallittu ja lasku ei ole keikalla ja on avoimella tilikaudella
		$query = "SELECT laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$tunnus'";
		$keikres = pupe_query($query);

		if (($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') and $oikeurow['paivitys'] == '1' and mysql_num_rows($keikres) == 0 and $laskurow["tapvm"] >= $yhtiorow["ostoreskontrakausi_alku"]) {

			echo "	<td class='back'><form action = '$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='tee' value='D'>
					<input type='hidden' name = 'nayta' value='$nayta'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='Submit' value='".t("Poista lasku")."'>
					</form></td>";

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
							msg = '".t("Haluatko todella poistaa t�m�n laskun ja sen kaikki tili�innit?")."';
							return confirm(msg);
						}
					</SCRIPT>";
		}

		echo "</tr>";

		if (trim($laskurow["viite"]) != "" or trim($laskurow["viesti"]) != "") {
			echo "<tr>";
			echo "<th colspan='3'>".t("Viite / Viesti / Ohjeita pankille")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td colspan='3'>$laskurow[viite] $laskurow[viesti] $laskurow[sisviesti1]</td>";
			echo "</tr>";
		}

		$query = "	SELECT fakta
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$toimres = pupe_query($query);
		$toimrow = mysql_fetch_assoc($toimres);

		if (trim($toimrow["fakta"]) != "") {
			echo "<tr>";
			echo "<th colspan='3'>".t("Fakta")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td colspan='3'>$toimrow[fakta]</td>";
			echo "</tr>";
		}

		echo "</table>";

		if ($laskurow["comments"] != "") {
			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Kommentit")."</th></tr>";
			echo "<tr><td colspan='2'>$laskurow[comments]</td></tr>";
			echo "</table>";
		}

		echo "<br><table>";
		// Mahdollisuus antaa kommentti
		echo "	<form name='kommentti' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tee' value='V'>
				<input type='hidden' name = 'nayta' value='$nayta'>
				<input type='hidden' name='tunnus' value = '$tunnus'>
				<input type='hidden' name='iframe' value = '$iframe'>
				<input type='hidden' name='iframe_id' value = '$iframe_id'>";

		echo "	<tr>
				<th colspan='2'>".t("Lis�� kommentti")."</th>
				</tr>";

		echo "	<tr>
				<td><input type='text' name='komm' value='' size='50'></td>
				<td><input type='Submit' value='".t("Lis�� kommentti")."'></td>
				</tr>";

		echo "</form>";
		echo "</table>";

		echo "<br><table>";
		echo "<tr>";

		$avainsana_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite='Kuka' ");

		// katsotaan onko k�ytt�jien dynaaminenpuu luotu avainsanoihin ja laskun hyv�ksyja_nyt pit�� olla k�ytt�j�
		if (mysql_num_rows($avainsana_result) == 1) {
			if ($laskurow['hyvaksyja_nyt'] == $kukarow['kuka']) {
				$query = "	SELECT hyvaksyja_maksimisumma, hierarkia
				          	FROM kuka
				          	WHERE yhtio = '$kukarow[yhtio]'
							and hyvaksyja = 'o'
							and kuka = '$laskurow[hyvaksyja_nyt]'
				          	ORDER BY nimi";
				$vresult = pupe_query($query);
				$hierarkiarow = mysql_fetch_assoc($vresult);

				$loytyyko_esimies = '';
				$hierarkia = $hierarkiarow['hierarkia'];

				if ($hierarkiarow['hyvaksyja_maksimisumma'] > 0) {
					for ($i = 1; $i < 5; $i++) {

						if ($laskurow['hyvaksyja_nyt'] == $laskurow['hyvak'.$i]) {

							for ($x = ($i+1); $x < 6; $x++) {

								if ($laskurow['hyvak'.$x] != '') {
									$hyvaksyja_maksimisumma = $hierarkiarow['hyvaksyja_maksimisumma'];
									break;
								}
								else {
									unset($hyvaksyja_maksimisumma);
								}
							}
						}
					}
				}
				else {
					$hyvaksyja_maksimisumma = 0;
					$loytyyko_esimies = 'joo';
				}
			}
		}

		// Jos laskua hyvaksyy ensimm�inen henkil� ja laskulla on annettu mahdollisuus hyvksynt�listan muutokseen n�ytet��n se!";
		if ($onko_eka_hyvaksyja and $laskurow['hyvaksynnanmuutos'] != '') {

			echo "<td class='back' valign='top'><table>";

			$hyvak[1] = $laskurow['hyvak1'];
			$hyvak[2] = $laskurow['hyvak2'];
			$hyvak[3] = $laskurow['hyvak3'];
			$hyvak[4] = $laskurow['hyvak4'];
			$hyvak[5] = $laskurow['hyvak5'];

			echo "<form name='uusi' action = '$PHP_SELF' method='post'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tee' value='L'>
					<input type='hidden' name='nayta' value='$nayta'>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<input type='hidden' name='iframe' value = '$iframe'>
					<input type='hidden' name='iframe_id' value = '$iframe_id'>";

			echo "<tr><th colspan='2'>".t("Hyv�ksyj�t")."</th></tr>";

			$query = "	SELECT kuka, nimi
			          	FROM kuka
			          	WHERE yhtio = '$kukarow[yhtio]'
						and hyvaksyja = 'o'
						and kuka = '$eka_hyvaksyja'
			          	ORDER BY nimi";
			$vresult = pupe_query($query);
			$vrow = mysql_fetch_assoc($vresult);

			echo "<tr><td>1. $vrow[nimi]</td><td>";

			$query = "	SELECT kuka, nimi
			          	FROM kuka
			          	WHERE yhtio = '$kukarow[yhtio]'
						and hyvaksyja = 'o'
			          	ORDER BY nimi";
			$vresult = pupe_query($query);

			$ulos = '';

			echo "<tr><td>";

			// T�ytet��n 4 hyv�ksynt�kentt�� (ensinm�inen on jo k�yt�ss�)
			for ($i=2; $i<6; $i++) {

				while ($vrow=mysql_fetch_assoc($vresult)) {
					$sel = "";

					if ($hyvak[$i] == $vrow['kuka']) {
						$sel = "selected";

						if (isset($loytyyko_esimies) and $loytyyko_esimies == '' and isset($hyvaksyja_maksimisumma) and $hyvaksyja_maksimisumma != 0 and $hyvaksyja_maksimisumma > $laskurow['summa'] and $hierarkia != '') {
							$query = "	SELECT parent.tunnus tunnus
										FROM dynaaminen_puu AS node
										JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
										JOIN kuka ON (kuka.yhtio = node.yhtio AND kuka.hierarkia = parent.tunnus and kuka.kuka = '$vrow[kuka]')
										WHERE node.tunnus IN ($hierarkia)
										AND node.laji = 'kuka'
										AND node.yhtio = '$kukarow[yhtio]'
										AND parent.tunnus NOT IN ($hierarkia)
										AND parent.lft > 1
										GROUP BY parent.tunnus
										ORDER BY parent.lft";
							$esimies_result = pupe_query($query);

							if (mysql_num_rows($esimies_result) != 0) $loytyyko_esimies = 'joo';
						}
					}

					$ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]</option>";
				}

				// K�yd��n sama data l�pi uudestaan
				if (!mysql_data_seek ($vresult, 0)) {
					echo "mysql_data_seek failed!";

					require ("inc/footer.inc");
					exit;
				}

				echo "$i. <select name='hyvak[$i]'>
				      <option value=''>".t("Ei kukaan")."</option>
				      $ulos
				      </select><br>";
				$ulos = "";
			}

			echo "	</td>
					<td valign='top'><input type='Submit' value='".t("Muuta lista")."'></td>
					</tr></form>";

			echo "</table></td><td width='30px' class='back'></td>";
		}
		else {

			echo "<td class='back' valign='top'><table>";
			echo "<tr><th>".t("Hyv�ksyj�")."</th><th>".t("Hyv�ksytty")."</th><th></th></tr>";

			for ($i=1; $i<6; $i++) {
				$hyind = "hyvak".$i;
				$htind = "h".$i."time";

				if ($laskurow[$hyind] != '') {
					$query = "	SELECT kuka, nimi
					          	FROM kuka
					          	WHERE yhtio = '$kukarow[yhtio]'
								and kuka = '$laskurow[$hyind]'
					          	ORDER BY nimi";
					$vresult = pupe_query($query);
					$vrow = mysql_fetch_assoc($vresult);

					if (isset($loytyyko_esimies) and $loytyyko_esimies == '' and isset($hyvaksyja_maksimisumma) and $hyvaksyja_maksimisumma != 0 and $hyvaksyja_maksimisumma > $laskurow['summa'] and $hierarkia != '') {
						$query = "	SELECT parent.tunnus tunnus
									FROM dynaaminen_puu AS node
									JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
									JOIN kuka ON (kuka.yhtio = node.yhtio AND kuka.hierarkia = parent.tunnus and kuka.kuka = '$vrow[kuka]')
									WHERE node.tunnus IN ($hierarkia)
									AND node.laji = 'kuka'
									AND node.yhtio = '$kukarow[yhtio]'
									AND parent.tunnus NOT IN ($hierarkia)
									AND parent.lft > 1
									GROUP BY parent.tunnus
									ORDER BY parent.lft";
						$esimies_result = pupe_query($query);

						if (mysql_num_rows($esimies_result) != 0) $loytyyko_esimies = 'joo';
					}

					echo "<tr><td>$i. $vrow[nimi]</td><td>";

					if ($laskurow[$htind] != '0000-00-00 00:00:00') {
						echo tv1dateconv($laskurow[$htind], "P");
					}

					echo "</td><td>".t("Lukittu")."</td></tr>";
				}
			}

			echo "</table></td><td width='30px' class='back'></td>";
		}

		echo "</tr></table>";

		if (in_array($laskurow["vienti"], array("B","E","H","C","J","F","K","I","L"))) {
			enable_ajax();

			echo "<br><table>";
			echo "<tr><th>".t("Laskusta k�ytetty keikoilla")."</th><th>".t("Summa")."</th></tr>";

			$query = "	SELECT sum(summa) summa
						from tiliointi
						where yhtio	= '$kukarow[yhtio]'
						and ltunnus	= '$tunnus'
						and tilino  = '$yhtiorow[alv]'
						and korjattu = ''";
			$alvires = pupe_query($query);
			$alvirow = mysql_fetch_assoc($alvires);

			$jaljella = round((float) $laskurow["summa"] - (float) $alvirow["summa"], 2);

			$query = "	SELECT lasku.laskunro, keikka.nimi, lasku.summa, lasku.arvo, keikka.comments, keikka.vanhatunnus vanhatunnus, keikka.tunnus tunnus, keikka.liitostunnus toimittajaid, keikka.alatila
						FROM lasku
						JOIN lasku keikka ON keikka.yhtio = lasku.yhtio and keikka.laskunro = lasku.laskunro and keikka.tila = 'K'
						WHERE lasku.yhtio	= '$kukarow[yhtio]'
						and lasku.tila		= 'K'
						and lasku.vanhatunnus	= '$tunnus'
						HAVING vanhatunnus = 0";
			$apure = pupe_query($query);

			if (mysql_num_rows($apure) > 0) {
				while ($apurow = mysql_fetch_assoc($apure)) {

					if ($apurow["comments"] != "") $apurow["comments"] = "<i>($apurow[comments])</i>";

					if ($apurow["alatila"] != "X") {
						echo "<form name='poista' action = '$PHP_SELF' method='post'>
								<input type='hidden' name = 'keikalla' value = 'on'>
								<input type='hidden' name = 'tee' value = 'poista'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name = 'toimittajaid' value = '$apurow[toimittajaid]'>
								<input type='hidden' name = 'poistavienti' value = '$apurow[vienti]'>
								<input type='hidden' name = 'poistasumma' value = '$apurow[summa]'>
								<input type='hidden' name = 'otunnus' value = '$apurow[tunnus]'>
								<input type='hidden' name = 'tunnus' value = '$tunnus'>";

						echo "<tr><td>$apurow[laskunro] $apurow[nimi]$apurow[comments]</td><td>$apurow[summa]</td><td class='back'><input type='submit' value='".t("poista")."'></tr>";
						echo "</form>";
					}
					else {
						echo "<tr><td>$apurow[laskunro] $apurow[nimi]$apurow[comments]</td><td>$apurow[summa]</td><td class='back'>".t("Lukittu")."</tr>";
					}

					$jaljella -= $apurow["arvo"];
				}
			}
			else {
				echo "<tr><td colspan = '2'>".t("Laskua ei ole viel� kohdistettu")."</td></tr>";
			}

			$apurow = mysql_fetch_assoc($apure);

			// Roundataan...
			$jaljella = round($jaljella, 2);

			if ($jaljella > 0) {
				//	Vaihtoomaisuuslaskuille ei tarvitse hakea toimittajaa, sen me tied�mme jo!
				if (!in_array($laskurow["vienti"], array("B","E","H"))) {
					if ($toimittajaid == "") $toimittajaid = $laskurow["liitostunnus"];

					$toimilisa = "&toimittajaid=$toimittajaid";
				}
				else {
					$toimilisa = "";
				}

				$lisa = "<a id='uusi' href='javascript:sndReq(\"keikka\", \"{$palvelin2}hyvak.php?keikalla=on&tee=haekeikka&tunnus=$tunnus$toimilisa\");'>".t("Liit� keikkaan")."</a>";
			}
			else {
				$lisa = "";
			}

			echo "<tr><th>".t("J�ljell�")."</th><th>$jaljella $laskurow[valkoodi]</th><td class='back'>$lisa</td></tr>";

			echo "</table>";

			echo "<div id='keikka'></div>";

		}

		if ($ok != 1) {
			// Annetaan tyhj�t tiedot, jos rivi oli virheet�n
			$tili      = '';
			$kustp     = '';
			$kohde     = '';
			$projekti  = '';
			$summa     = '';
			$selite    = '';
			$vero      = alv_oletus();
		}

		// T�t� ei siis tehd� jos kyseess� on kevenetty versio
		if ($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') {

			// Tositteen tili�intirivit...
			require ("inc/tiliointirivit.inc");
			echo "<br><br>";

			if ($ok == 1) {

				if (!isset($loytyyko_esimies) or (isset($loytyyko_esimies) and trim($loytyyko_esimies) != '')) {
					echo "<form action = '$PHP_SELF' method='post'>
							<input type='hidden' name = 'tunnus' value='$tunnus'>
							<input type='hidden' name = 'tee' value='H'>
							<input type='Submit' value='".t("Hyv�ksy tili�inti ja lasku")."'>
							</form><br>";
				}
				else {
					echo "<font class='error'>",t("Hyv�ksytt�v�n laskun summa ylitt�� sallitun hyv�ksynn�n maksimisumman ja / tai sinun j�lkeesi hyv�ksyj�listalla ei ole esimiest�si"),"!</font><br /><br />";
				}
			}

			if ($laskurow["h1time"] != "0000-00-00 00:00:00") {
				echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='palauta'>
						<input type='hidden' name='tunnus' value='$tunnus'>
						<input type='Submit' value='".t("Palauta lasku edelliselle hyv�ksyj�lle")."'>
						</form><br>";
			}

			// N�ytet��n alv-erittely, jos on useita kantoja.
			if ($naytalisa != '') {
				$query = "	SELECT vero, sum(summa) veroton, round(sum(summa*vero/100),2) 'veron m��r�', round(sum(summa*(1+vero/100)),2) verollinen from tiliointi
							WHERE ltunnus = '$tunnus'
							and yhtio = '$kukarow[yhtio]'
							and korjattu = ''
							and tilino not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
							GROUP BY 1";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 1) {

					echo "<br><table><tr>";

					for ($i = 0; $i < mysql_num_fields($result); $i++) {
						echo "<th>" . t(mysql_field_name($result,$i))."</th>";
					}

					echo "</tr>";

					while ($tiliointirow = mysql_fetch_assoc($result)) {

						echo "<tr>";

						for ($i=0; $i<mysql_num_fields($result); $i++) {
							echo "<td>".$tiliointirow[mysql_field_name($result,$i)]."</td>";
						}

						echo "</tr>";
					}
					echo "</table>";
				}
			}
		}

		if ($kukarow['taso'] == 9) {

			// Kevennetyn k�ytt�liittym� alkaa t�st�
			echo "<table><tr>";

			if (!isset($loytyyko_esimies) or (isset($loytyyko_esimies) and trim($loytyyko_esimies) != '')) {
				echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name = 'tunnus' value='$tunnus'>
						<input type='hidden' name = 'tee' value='H'>
						<td class='back'><input type='Submit' value='".t("Hyv�ksy lasku")."'></td>
						</form>";
			}
			else {
				echo "<font class='error'>",t("Hyv�ksytt�v�n laskun summa ylitt�� sallitun hyv�ksynn�n maksimisumman ja sinun j�lkeesi hyv�ksyj�listalla ei ole esimiest�si"),"!</font><br />";
			}

			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='Z'>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<td class='back'><input type='Submit' value='".t("Pys�yt� laskun k�sittely")."'></td>
					</form>";

			echo "</tr></table><br>";
		}


		echo "<br><table><tr>";

		//	Onko kuva tietokannassa?
		$liitteet = ebid($laskurow["tunnus"], true);

		if (is_array($liitteet) and count($liitteet) > 0) {
			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name = 'tunnus' value='$tunnus'>
					<input type='hidden' name = 'iframe' value='yes'>
					<input type='hidden' name = 'nayta' value='$nayta'>
					<input type='hidden' name = 'tee' value = ''>
					<th>".t("Avaa lasku t�h�n ikkunaan")."</th>
					<td>";

			if (is_array($liitteet) and count($liitteet) == 1) {
				echo "<input type='hidden' name = 'iframe_id' value='$liitteet[0]'>
						<input type='Submit' value='".t("Avaa")."'>";
			}
			else {
				echo "<select name='iframe_id' onchange='submit();'>
						<option value=''>Valitse lasku</option>";

				$liicoint = 1;
				foreach($liitteet as $liite) {
					if ($iframe_id == $liite) $sel = "selected";
					else $sel = "";

					echo "<option value='$liite' $sel>".t("Liite")." $liicoint</option>";

					$liicoint++;
				}
				echo "</select>";
			}

			echo "</td></form>";
		}

		if ($iframe == 'yes') {
	  		echo "<td>
					<form action = '$PHP_SELF' method='post'>
	  				<input type='hidden' name = 'tunnus' value='$tunnus'>
					<input type='hidden' name='lopetus' value='$lopetus'>
	  				<input type='hidden' name = 'nayta' value='$nayta'>
	  				<input type='Submit' value='".t("Sulje lasku")."'>
					</form></td>";
		}

		echo "</tr></table>";

		if ($iframe == 'yes' and $iframe_id != '') {
			echo "<iframe src='$iframe_id' name='alaikkuna' width='100%' height='600px' align='bottom' scrolling='auto'></iframe>";
		}
	}
	elseif ($kutsuja == "") {

		// T�ll� ollaan, jos olemme vasta valitsemassa laskua
		if ($nayta == '') {
			$query = "	SELECT count(*) kpl
						FROM lasku
				 		WHERE hyvaksyja_nyt = '$kukarow[kuka]'
						and yhtio = '$kukarow[yhtio]'
						and alatila = 'H'
						and tila != 'D'
				  		ORDER BY erpcm";
			$result = pupe_query($query);
			$trow = mysql_fetch_assoc ($result);

			if ($trow["kpl"] > 0) {
				echo "<a href='$PHP_SELF?nayta=1'><font class='error'>". sprintf(t('Sinulla on %d pys�ytetty� laskua'), $trow["kpl"]) . "</font></a><br><br>";
			}
		}

		if ($nayta != '') $nayta = '';
		else $nayta = "and alatila != 'H'";

		// and alatila != 'M', keskener�isi� matkalaskuja ei n�ytet�
		$query = "	SELECT *, round(summa * vienti_kurssi, 2) kotisumma
				 	FROM lasku
				  	WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tila != 'D'
					and alatila != 'M'
					$nayta
				  	ORDER BY if (kapvm!='0000-00-00',kapvm,erpcm)";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Sinulla ei ole hyv�ksym�tt�mi� laskuja")."</b><br>";
			require ("inc/footer.inc");
			exit;
		}

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Er�pvm/Kapvm")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "<th>".t("Yhti�n valuutassa")."</th>";
		echo "<th>".t("Laskun valuutassa")."</th>";
		echo "<th>".t("Laskunro")."</th>";
		echo "<th>".t("Liitetty")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		if ($liitetaanko_editilaus_laskulle_hakemisto != '') echo "<th>",t("Vertailu"),"</th>";
		echo "<th>".t("Kustannuspaikka")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {

			$query = "	SELECT laskunro
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tila = 'K'
						AND vanhatunnus = '$trow[tunnus]'";
			$keikres = pupe_query($query);

			//	Onko liitetty jo keikkaan
			if (in_array($trow["vienti"], array("B", "C", "J", "E", "F", "K", "H", "I", "L"))) {
				if (mysql_num_rows($keikres) > 0) {
					$liitetty = "<font class='ok'>ON</font>";
				}
				else {
					$liitetty = "<font class='error'>EI</font>";
				}
			}
			else {
				$liitetty = "";
			}

			echo "<tr class='aktiivi'>";

			 // Eli vain tasolla 1/2/3 ja ensimm�iselle hyv�ksyj�lle.
			if (($kukarow["taso"] == '2' or $kukarow["taso"] == '3') and $onko_eka_hyvaksyja) {
				echo "<td valign='top'><a href='$PHP_SELF?tee=M&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</a></td>";
			}
			else {
				echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
			}

			echo "<td valign='top'>";

			echo tv1dateconv($trow["erpcm"]);
			if ($trow["kapvm"] != "0000-00-00") echo "<br>".tv1dateconv($trow["kapvm"]);

			echo "</td>";

			echo "<td valign='top'>".tarkistahetu($trow["ytunnus"])."</td>";

			if ($trow['comments'] != '') {
				echo "<td valign='top'>$trow[nimi]<br><font class='error'>$trow[comments]</font></td>";
			}
			else {
				echo "<td valign='top'>$trow[nimi]</td>";
			}

			echo "<td valign='top'>$trow[postitp]</td>";
			echo "<td valign='top' style='text-align: right;'>$trow[kotisumma] $yhtiorow[valkoodi]</td>";
			echo "<td valign='top' style='text-align: right;'>$trow[summa] $trow[valkoodi]</td>";
			echo "<td valign='top' style='text-align: right;'>$trow[laskunro]</td>";
			echo "<td valign='top' style='text-align: right;'>$liitetty</td>";

			// tehd��n lasku linkki
			echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";

			if ($liitetaanko_editilaus_laskulle_hakemisto != '') {
				echo "<td valign='top'>";

				list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $trow['tunnus']);

				if ($invoice != FALSE and $invoice != 'ei_loydy_edia') {
					if (count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
						$ok = 'ok';

						foreach ($loytyy_kummastakin as $tuoteno => $null) {
							if (substr($tuoteno, 0, 15) != "Ei_tuotekoodia_" and ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1)) {
								echo "<a href='laskujen_vertailu.php?laskunro=$trow[laskunro]&lopetus=hyvak.php////kutsuja=//'>",t("Eroja"),"</a>";
								$ok = '';
								break;
							}
						}

						if ($ok == 'ok') {
							echo "<font class='ok'>",t("OK"),"</font>";
						}
					}
					else {
						echo "<a href='laskujen_vertailu.php?laskunro=$trow[laskunro]&lopetus=hyvak.php////kutsuja=//'>",t("Eroja"),"</a>";
					}
				}
				elseif ($invoice == 'ei_loydy_edia') {
					echo "<font class='error'>".t("Tilaus ei l�ydy")."</font>";
				}
				else {
					echo "&nbsp;";
				}

				echo "</td>";
			}

			// kustannuspaikka n�kyviin asiakkaan toiveesta..
			$kustpq = "	SELECT kustp,
							(SELECT DISTINCT nimi
							FROM kustannuspaikka
							WHERE kustannuspaikka.yhtio=tiliointi.yhtio AND kustannuspaikka.tunnus=tiliointi.kustp) kustp2
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						AND ltunnus = '$trow[tunnus]'
						and kustp != 0
						ORDER BY tiliointi.tunnus LIMIT 1";

			$kustpres = pupe_query($kustpq);
			$kustprivi = mysql_fetch_assoc($kustpres);
			if (trim($kustprivi['kustp2']) =='') {
				echo "<td>".t("Kustannuspaikkaa ei ole sy�tetty")."</td>";
			}
			else {
				echo "<td>".$kustprivi['kustp2']."</td>";
			}

			echo "<td class='back' valign='top'>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value='$trow[tunnus]'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='submit' value='".t("Valitse")."'>
					</form>
				</td>";

			// ykk�s ja kakkos ja kolmos tason spessuja
			if ($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') {
				// Mahdollisuus laittaa lasku holdiin
				if ($trow['alatila'] != 'H') {
					echo "<td class='back' valign='top'>
							<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='tee' value='Z'>
							<input type='hidden' name='tunnus' value='$trow[tunnus]'>
							<input type='submit' value='".t("Pys�yt�")."'>
							</form>
						</td>";
				}
				else {
					echo "<td class='back' valign='top'>
							<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='tee' value='Z'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='tunnus' value='$trow[tunnus]'>
							<input type='Submit' value='".t("Lis�� kommentti")."'>
							</form>
						</td>";
				}

				if ($oikeurow['paivitys'] == '1' and mysql_num_rows($keikres) == 0 and $trow["tapvm"] >= $yhtiorow["ostoreskontrakausi_alku"]) {
					echo "<td class='back' valign='top'>
							<form action='$PHP_SELF' method='post' onSubmit='return verify()'>
							<input type='hidden' name='tee' value='D'>
							<input type='hidden' name='nayta' value='$nayta'>
							<input type='hidden' name='tunnus' value = '$trow[tunnus]'>
							<input type='Submit' value='".t("Poista")."'>
							</form>
						</td>";
				}
			}

			echo "</tr>";
		}
		echo "</table>";

		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
				function verify() {
					msg = '".t("Haluatko todella poistaa t�m�n laskun ja sen kaikki tili�innit?")."';
					return confirm(msg);
				}
				</SCRIPT>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
		require ("inc/footer.inc");
	}

?>
