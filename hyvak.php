<?php

	if ($_REQUEST["tee"] == "haekeikka") {
		$_GET["ohje"] = "off";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}

	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	$lopetus = "${palvelin2}hyvak.php////kutsuja=";

	require_once ("inc/tilinumero.inc");

	if ($keikalla == "on") {

		//	Täällä haetaan laskurow aika monta kertaa, joskus voisi tehdä recodea?
		$query = "	SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$abures = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($abures);

		//	Meillä on oikea toimittajaid
		if ($tee == "haekeikka") {

			echo "<br>";

			// Mihin tämä on listattu Listataan
			//	Jos meillä ei ole vielä toimittajaa valitaan ensin se
			if ($toimittajaid != "" or $ytunnus != "") {
				$keikkamonta = 0;
				$hakutunnus = $ytunnus;
				$hakuid		= $toimittajaid;

				$PHP_SELF = "javascript:ajaxPost('kevyt_toimittajahaku', 'hyvak.php', 'keikka', '', '', '', post');";

				require ("inc/kevyt_toimittajahaku.inc");
			}

			if ($toimittajaid != "") {
				echo "<table><tr><th>".t("Valittu toimittaja")."</th><td>$toimittajarow[nimi] $toimittajarow[osoite] $toimittajarow[postino] $toimittajarow[postitp] ($toimittajarow[ytunnus])</td><td class='back'><a href = 'javascript:sndReq(\"keikka\", \"hyvak.php?keikalla=on&tee=haekeikka&tunnus=$tunnus&toimittajaid=\");'>Vaihda toimittaja</a></td></tr></table><br>";

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
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					//	Haetaan vientitieto..

					//	Kululaskun voi kohdistaa useaan keikkaan
					if (in_array($laskurow["vienti"], array("B","E","H"))) {
						echo "<form id='liita' action='hyvak.php?keikalla=on' method='post' autocomplete='off'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='tee' value = 'liita'>";
						echo "<input type='hidden' name='tunnus' value = '$tunnus'>";
						echo "<input type='hidden' name='toimittajaid' value = '$toimittajaid'>";
						echo "<table><tr><th>".t("Keikka")."</th><th>".t("Kommentit")."</th><th>".t("Rivejä")."/".t("varasossa")."</th><th>".t("Varastonarvo")."</th><th>".t("Summa")."</th></tr>";

						while($row = mysql_fetch_array($result)) {
							echo "<tr><td>$row[keikka]</td><td>$row[comments]</td><td align='right'>$row[kpl]/$row[varastossa]</td><td align='right'>$row[varastossaarvo]</td><td>";
							echo "<input type='text' name='liita[".$row["otunnus"]."][liitasumma]' value='$liitasumma' size='10'>";
							echo "</td></tr>";
						}
						echo "<tr><td class='back' colspan='5' align = 'right'><input type='submit' value='".t("Liitä keikkoihin")."'></td></tr></table></form>";
					}
					else {
						echo "<table><tr><th>".t("Keikka")."</th><th>".t("Kommentit")."</th><th>".t("Rivejä")."/".t("varasossa")."</th><th>".t("Varastonarvo")."</th></tr>";
						while($row = mysql_fetch_array($result)) {

							$query = "	SELECT sum(summa) summa
										from tiliointi
										where yhtio	= '$kukarow[yhtio]'
										and ltunnus	= '$tunnus'
										and tilino  = '$yhtiorow[alv]'
										and korjattu = ''";
							$alvires = mysql_query($query) or pupe_error($query);
							$alvirow = mysql_fetch_array($alvires);
							$summa_kaytettavissa = round((float) $laskurow["summa"] - (float) $alvirow["summa"], 2);

							echo "<tr><td>$row[keikka]</td><td>$row[comments]</td><td align='right'>$row[kpl]/$row[varastossa]</td><td align='right'>$row[varastossaarvo]</td>";
							echo "<td class='back' colspan='5' align = 'right'>
									<form id='liita' action='hyvak.php?keikalla=on' method='post' autocomplete='off'>
									<input type='hidden' name='lopetus' value='$lopetus'>
									<input type='hidden' name='tee' value = 'liita'>
									<input type='hidden' name='tunnus' value = '$tunnus'>
									<input type='hidden' name='toimittajaid' value = '$toimittajaid'>
									<input type='hidden' name='liita[".$row["otunnus"]."][liitasumma]' value='' size='10'>
									<input type='submit' value='".t("Liitä keikkaan")."'>
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
				echo "<form id='toimi' name = 'toimi' action='javascript:ajaxPost(\"toimi\", \"hyvak.php?tee=$tee\", \"keikka\", \"\", \"\", \"\", \"post\");' method='post' autocomplete='off'>";
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
					foreach($liita as $l => $v) {
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
			echo "Kui sää tänne pääsit?!";
		}
	}

	if ($kutsuja != "MATKALASKU") echo "<font class='head'>".t('Hyväksyttävät laskusi')."</font><hr><br>";

	$onko_eka_hyvaksyja = FALSE;

	if ((int) $tunnus != 0) {
		$tunnus = mysql_real_escape_string($tunnus);

		$query = "	SELECT hyvak1, h1time
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$tunnus'";
		$check_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($check_res) == 1) {
			$check_row = mysql_fetch_array($check_res);

			// jos ensimmäinen hyväksyjä on tää käyttäjä ja ei olla vielä hyväksytty
			if ($check_row['hyvak1'] == $kukarow['kuka'] and $check_row["h1time"] == "0000-00-00 00:00:00") {
				$onko_eka_hyvaksyja = TRUE;
			}
		}
	}

	// halutaan nähdä otsikko, tai ollaan eka hyväksyjä ja ei olla hyväksymässä laskua
	if ($tee == 'M' or ($onko_eka_hyvaksyja === TRUE and $tee != 'H' and $tee != 'D' and ($kukarow['taso'] == '2' or $kukarow["taso"] == '3'))) {
		$query = "	SELECT *
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_array ($result);

		if ($trow['hyvak1'] == $kukarow['kuka']) {
			$hyvak_apu_tee = $tee;
			$hyvak_apu_tila = $tila;
			require ("inc/muutosite.inc");
			$tee = $hyvak_apu_tee;
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
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";

			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_array ($result);

		if ($trow["tapvm"] >= $yhtiorow["ostoreskontrakausi_alku"] or ($trow["tapvm"]=="0000-00-00" and $trow["laskutyyppi"] == "M")) {

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
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			die(t("Lasku kateissa"));
		}
		$lrow = mysql_fetch_array($result);

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
			for($i=1;$i<5;$i++) {
				$haika	= "h".$i."time";
				$haiku	= "hyvak".$i;

				if ($lrow[$haika] != "0000-00-00 00:00:00") {

					$query = "	SELECT nimi
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]' and kuka = '".$lrow[$haiku]."'";
					$result = mysql_query($query) or pupe_error($query);
					$krow = mysql_fetch_array($result);

					if ($hyvaksyja == $i) {
						$sel = "checked";
					}
					else {
						$sel = "";
					}
					$hyvaksyjat .= "
									<tr>
										<td><input type='radio' name='hyvaksyja' value='$i' $sel> $i. $krow[nimi]</td>
									</tr>";
				}
			}

			$hyvaksyjat .= "
			</table><br>";

			echo "
					<form action = '$PHP_SELF' method='post'>
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
					<input type = 'submit' value = '".t("Palaa laskun hyväksyntään")."'>
					</form>";

			require("inc/footer.inc");
			exit;
		}
		else {

			//	Palautetaan takaisin edelliselle hyväksyjälle
			if ((int) $hyvaksyja > 0) {
				$upd = "h{$hyvaksyja}time = '0000-00-00 00:00:00', hyvaksyja_nyt = '".$lrow["hyvak{$hyvaksyja}"]."'";
				$kukahyvak = $lrow["hyvak{$hyvaksyja}"];
			}
			else die("Hyväksyjä ei kelpaa");

			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukahyvak'";
			$result = mysql_query($query) or pupe_error($query);
			$krow = mysql_fetch_array($result);

			// Merkataan lasku poistetuksi
			$query = "	UPDATE lasku SET
						$upd
						WHERE tunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t('Palauttettiin lasku käyttäjälle')." $krow[nimi]</font><br>";

			//	Lähetetään maili virheen merkiksi!
			mail($krow["eposti"], "Hyväksymäsi lasku palautettiin", t("Hyväksymäsi lasku toimittajalta %s, %s %s palautettiin korjattavaksi.\n\nSyy: %s \n\nPalauttaja:", $krow["kieli"], $lrow["nimi"], $lrow["summa"], $lrow["valkoodi"], $viesti)." $kukarow[nimi], $yhtiorow[nimi]", "From: <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
			$tunnus = "";
			$tee = "";
		}
	}

	if ($tee == 'W' and $komm == '') {
		echo "<font class='error'>".t('Anna pysäytyksen syy')."</font>";
		$tee = 'Z';
	}

	if ($tee == 'V' and $komm == '') {
		echo "<font class='error'>".t('Anna kommentti')."</font><br>";
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

			require ("inc/footer.inc");
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

			require ("inc/footer.inc");
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

			require ("inc/footer.inc");
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

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		if ($laskurow['hyvak1'] != $laskurow['hyvaksyja_nyt'] or $laskurow['hyvaksynnanmuutos'] == '') {
			echo pupe_eror('lasku on väärässä tilassa');

			require ("inc/footer.inc");
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

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		//	Jos tehdään matkalaskun ensimmäinen hyväksyntä..
		if ($laskurow["tilaustyyppi"] == "M" and $laskurow["h1time"]=="0000-00-00 00:00:00") {

			$query = "select * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$toimires = mysql_query($query) or pupe_error($query);
			$toimirow = mysql_fetch_array($toimires);

			//	Päivitetään tapvm ja viesti laskulle
			$viesti = t("Matkalasku")." ".date("d").".".date("m").".".date("Y");
			$query = " update lasku set tapvm=now(), erpcm=DATE_ADD(now(), INTERVAL $toimirow[oletus_erapvm] DAY), viesti ='$viesti' where yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
			$updres = mysql_query($query) or pupe_error($query);

			//	Päivitetään tiliöintien tapvm as well
			$query = " update tiliointi set tapvm=now() where yhtio = '$kukarow[yhtio]' and ltunnus='$tunnus'";
			$updres = mysql_query($query) or pupe_error($query);

		}

		// Kuka hyväksyi??
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

		// Kuka hyväksyy seuraavaksi??
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

		echo "<br><font class='message'>'$laskurow[hyvaksyja_nyt]' ".t("hyväksyi laskun")." $viesti</font><br><br>";

		$tunnus = '';
		$tee = '';
	}

	if ($tee == 'P') {
		// Olemassaolevaa tiliöintiä muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tiliöintiä ei löydy")."! $query";

			require ("inc/footer.inc");
			exit;
		}

		$tiliointirow = mysql_fetch_array($result);

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
					WHERE aputunnus = '$ptunnus' and
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
						WHERE aputunnus = '$ptunnus' and
						yhtio = '$kukarow[yhtio]' and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE tunnus = '$ptunnus' and
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

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

 		// Tarvitaan kenties tositenro
		if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

			if ($tositenro != 0) {
				$query = "	SELECT tosite
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]' and
							ltunnus = '$tunnus' and
							tosite = '$tositenro'";
				$result = mysql_query($query) or pupe_error($query);

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

					$query = "	SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$tunnus' and
								tapvm = '$tiliointipvm' and
								tilino = '$yhtiorow[ostovelat]' and
								summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
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

						require ("inc/footer.inc");
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
					concat_ws('@', laatija, luontiaika) kuka,
					round(summa * vienti_kurssi, 2) kotisumma
					FROM lasku
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Lasku katosi")."</b><br>";

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

		//	Tarkistetaan onko tälläistä laskua tältä toimittajalta jo kierrossa
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
			$tarkres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tarkres) != 0) {
				echo "<br><font class = 'error'>".t("HUOM! Toimittajalta on saapunut jo lasku samalla laskunumerolla!")."</font><table>";

				while ($tarkrow = mysql_fetch_array($tarkres)) {
					echo "<tr><td class='back'>$tarkrow[summa] $tarkrow[valkoodi]</td><td class='back'>$tarkrow[kuka]</td><td class='back'><a href='muutosite.php?tee=E&tunnus=$tarkrow[tunnus]'>".t("Avaa lasku")."</a></td>";
				}
				echo "</table><br><br>";
			}
		}

		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Laatija/Laadittu")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Eräpvm/Kapvm")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$laskurow[kuka]</td>";

		if (($kukarow['taso'] == '2' or $kukarow["taso"] == '3') and $laskurow['hyvak1'] == $kukarow['kuka']) {

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

		echo "<td>".ebid($laskurow["tunnus"]) . "</td>";

		// Näytetään poistonappi, jos se on sallittu ja lasku ei ole keikalla ja on avoimella tilikaudella
		$query = "SELECT laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$tunnus'";
		$keikres = mysql_query($query) or pupe_error($query);

		if (($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') and $oikeurow['paivitys'] == '1' and mysql_num_rows($keikres) == 0 and $laskurow["tapvm"] >= $yhtiorow["ostoreskontrakausi_alku"]) {

			echo "	<td class='back'><form action = '$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='tee' value='D'>
					<input type='hidden' name = 'nayta' value='$nayta'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='Submit' value='".t("Poista lasku")."'>
					</form></td>";

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
							msg = '".t("Haluatko todella poistaa tämän laskun ja sen kaikki tiliöinnit?")."';
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
		$toimres = mysql_query($query) or pupe_error($query);
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
				<th colspan='2'>".t("Lisää kommentti")."</th>
				</tr>";

		echo "	<tr>
				<td><input type='text' name='komm' value='' size='50'></td>
				<td><input type='Submit' value='".t("Lisää kommentti")."'></td>
				</tr>";

		echo "</form>";
		echo "</table>";

		echo "<br><table>";
		echo "<tr>";

		// Jos laskua hyvaksyy ensimmäinen henkilö ja laskulla on annettu mahdollisuus hyvksyntälistan muutokseen näytetään se!";
		if ($laskurow['hyvak1'] == $laskurow['hyvaksyja_nyt'] and $laskurow['h1time'] == '0000-00-00 00:00:00' and $laskurow['hyvaksynnanmuutos'] != '') {

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

			echo "<tr><th colspan='2'>".t("Hyväksyjät")."</th></tr>";

			$query = "	SELECT kuka, nimi
			          	FROM kuka
			          	WHERE yhtio = '$kukarow[yhtio]'
						and hyvaksyja = 'o'
						and kuka = '$laskurow[hyvak1]'
			          	ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);

			echo "<tr><td>1. $vrow[nimi]</td><td>";

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

					require ("inc/footer.inc");
					exit;
				}

				echo "$i. <select name='hyvak[$i]'>
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
		else {

			echo "<td class='back' valign='top'><table>";
			echo "<tr><th>".t("Hyväksyjä")."</th><th>".t("Hyväksytty")."</th><th></th></tr>";

			for ($i=1; $i<6; $i++) {
				$hyind = "hyvak".$i;
				$htind = "h".$i."time";

				if ($laskurow[$hyind] != '') {
					$query = "	SELECT kuka, nimi
					          	FROM kuka
					          	WHERE yhtio = '$kukarow[yhtio]'
								and hyvaksyja = 'o'
								and kuka = '$laskurow[$hyind]'
					          	ORDER BY nimi";
					$vresult = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_array($vresult);

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
			echo "<tr><th>".t("Laskusta käytetty keikoilla")."</th><th>".t("Summa")."</th></tr>";

			$query = "	SELECT sum(summa) summa
						from tiliointi
						where yhtio	= '$kukarow[yhtio]'
						and ltunnus	= '$tunnus'
						and tilino  = '$yhtiorow[alv]'
						and korjattu = ''";
			$alvires = mysql_query($query) or pupe_error($query);
			$alvirow = mysql_fetch_array($alvires);

			$jaljella = round((float) $laskurow["summa"] - (float) $alvirow["summa"], 2);

			$query = "	SELECT lasku.laskunro, keikka.nimi, lasku.summa, lasku.arvo, keikka.comments, keikka.vanhatunnus vanhatunnus, keikka.tunnus tunnus, keikka.liitostunnus toimittajaid, keikka.alatila
						FROM lasku
						JOIN lasku keikka ON keikka.yhtio = lasku.yhtio and keikka.laskunro = lasku.laskunro and keikka.tila = 'K'
						WHERE lasku.yhtio	= '$kukarow[yhtio]'
						and lasku.tila		= 'K'
						and lasku.vanhatunnus	= '$tunnus'
						HAVING vanhatunnus = 0";
			$apure = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($apure)>0) {
				while($apurow = mysql_fetch_array($apure)) {

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
				echo "<tr><td colspan = '2'>".t("Laskua ei ole vielä kohdistettu")."</td></tr>";
			}

			$apurow = mysql_fetch_array($apure);

			// Roundataan...
			$jaljella = round($jaljella, 2);

			if ($jaljella > 0) {
				//	Vaihtoomaisuuslaskuille ei tarvitse hakea toimittajaa, sen me tiedämme jo!
				if (!in_array($laskurow["vienti"], array("B","E","H"))) {
					if ($toimittajaid == "") $toimittajaid = $laskurow["liitostunnus"];

					$toimilisa = "&toimittajaid=$toimittajaid";
				}
				else {
					$toimilisa = "";
				}

				$lisa = "<a id='uusi' href='javascript:sndReq(\"keikka\", \"hyvak.php?keikalla=on&tee=haekeikka&tunnus=$tunnus$toimilisa\");'>".t("Liitä keikkaan")."</a>";
			}
			else {
				$lisa = "";
			}

			echo "<tr><th>".t("Jäljellä")."</th><th>$jaljella $laskurow[valkoodi]</th><td class='back'>$lisa</td></tr>";

			echo "</table>";

			echo "<div id='keikka'></div>";

		}

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
		if ($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') {

			// Tositteen tiliöintirivit...
			require ("inc/tiliointirivit.inc");
			echo "<br><br>";

			if ($ok == 1) {
				echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name = 'tunnus' value='$tunnus'>
						<input type='hidden' name = 'tee' value='H'>
						<input type='Submit' value='".t("Hyväksy tiliöinti ja lasku")."'>
						</form><br>";
			}

			if ($laskurow["h1time"] != "0000-00-00 00:00:00") {
				echo "<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='palauta'>
						<input type='hidden' name='tunnus' value='$tunnus'>
						<input type='Submit' value='".t("Palauta lasku edelliselle hyväksyjälle")."'>
						</form><br>";
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

					echo "<br><table><tr>";

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

					echo "</table>";

				}
			}
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
					<th>".t("Avaa lasku tähän ikkunaan")."</th>
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

		$query = "	SELECT *, round(summa * vienti_kurssi, 2) kotisumma
				 	FROM lasku
				  	WHERE hyvaksyja_nyt = '$kukarow[kuka]'
					and yhtio = '$kukarow[yhtio]'
					and tila != 'D'
					$nayta
				  	ORDER BY if (kapvm!='0000-00-00',kapvm,erpcm)";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Sinulla ei ole hyväksymättömiä laskuja")."</b><br>";
			require ("inc/footer.inc");
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
		echo "<th>".t("Laskunro")."</th>";
		echo "<th>".t("Liitetty")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		if ($liitetaanko_editilaus_laskulle_hakemisto != '') echo "<th>",t("Vertailu"),"</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array ($result)) {

			$query = "	SELECT laskunro
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tila = 'K'
						AND vanhatunnus = '$trow[tunnus]'";
			$keikres = mysql_query($query) or pupe_error($query);

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

			echo "<tr>";

			 // Eli vain tasolla 1/2/3 ja ensimmäiselle hyväksyjälle.
			if (($kukarow["taso"] == '2' or $kukarow["taso"] == '3') and $trow['hyvak1'] == $kukarow['kuka']) {
				echo "<td valign='top'><a href='$PHP_SELF?tee=M&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</a></td>";
			}
			else {
				echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
			}

			echo "<td valign='top'>";

			echo tv1dateconv($trow["erpcm"]);
			if ($trow["kapvm"] != "0000-00-00") echo "<br>".tv1dateconv($trow["kapvm"]);

			echo "</td>";

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
			echo "<td valign='top' style='text-align: right;'>$trow[laskunro]</td>";
			echo "<td valign='top' style='text-align: right;'>$liitetty</td>";

			// tehdään lasku linkki
			echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";

			if ($liitetaanko_editilaus_laskulle_hakemisto != '') {
				echo "<td valign='top'>";

				list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $trow['tunnus']);

				if ($invoice != FALSE and $invoice != 'ei_loydy_edia') {
					if (count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
						$ok = 'ok';

						foreach ($loytyy_kummastakin as $tuoteno => $null) {
							if ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1) {
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
					echo "<font class='error'>".t("Tilaus ei löydy")."</font>";
				}
				else {
					echo "&nbsp;";
				}

				echo "</td>";
			}

			echo "<td class='back' valign='top'>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value='$trow[tunnus]'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='submit' value='".t("Valitse")."'>
					</form>
				</td>";

			// ykkös ja kakkos ja kolmos tason spessuja
			if ($kukarow['taso'] == '1' or $kukarow['taso'] == '2' or $kukarow["taso"] == '3') {
				// Mahdollisuus laittaa lasku holdiin
				if ($trow['alatila'] != 'H') {
					echo "<td class='back' valign='top'>
							<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='tee' value='Z'>
							<input type='hidden' name='tunnus' value='$trow[tunnus]'>
							<input type='submit' value='".t("Pysäytä")."'>
							</form>
						</td>";
				}
				else {
					echo "<td class='back' valign='top'>
							<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='tee' value='Z'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='tunnus' value='$trow[tunnus]'>
							<input type='Submit' value='".t("Lisää kommentti")."'>
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
					msg = '".t("Haluatko todella poistaa tämän laskun ja sen kaikki tiliöinnit?")."';
					return confirm(msg);
				}
				</SCRIPT>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
		require ("inc/footer.inc");
	}

?>
