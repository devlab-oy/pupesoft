<?php
	///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
	$useslave = 1;
	require ("../inc/parametrit.inc");
	echo "<font class='head'>".t("Inventointipoikkeamat").":</font><hr>";

	if ($tee == 'Y') {

		if ($tila == 'tulosta') {
			$tulostimet[0] = "Inventointipoikkeamat";
			if (count($komento) == 0) {
				require("../inc/valitse_tulostin.inc");
			}
		}

		//$prosmuutos   = (int) $prosmuutos;
		$kplmuutos = (int) $kplmuutos;
		if ((int) $prosmuutos == 0 and $kplmuutos == 0) {
			$kplmuutos = 1;
		}

		if ((int) $prosmuutos <> 0 or $kplmuutos <> 0) {

			$lisa = ""; // no hacking

			if ((int) $prosmuutos < 0 and substr($prosmuutos,0,1) == '-') {
				$prosmuutos   = (int) $prosmuutos;
				$lisa = " and inventointipoikkeama <= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0 and substr($prosmuutos,0,1) == '+') {
				$prosmuutos   = (int) $prosmuutos;
				$lisa = " and inventointipoikkeama >= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0) {
				$prosmuutos   = (int) $prosmuutos;
				$lisa = " and (inventointipoikkeama <= '-$prosmuutos' or inventointipoikkeama >= '$prosmuutos') ";
			}

			if ($kplmuutos <> 0) {
				$lisa = " and abs(tapahtuma.kpl) >= abs('$kplmuutos') ";
			}

			$query = "	SELECT tuote.tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso, nimitys, yksikko, inventointiaika, inventointipoikkeama, selite, tapahtuma.kpl,
						group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
						concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
						FROM tuote
						JOIN tuotepaikat USING (yhtio, tuoteno)
						JOIN tapahtuma ON tapahtuma.yhtio = tuote.yhtio
						and tapahtuma.laji='Inventointi'
						and tapahtuma.tuoteno=tuote.tuoteno
						and tapahtuma.laadittu=tuotepaikat.inventointiaika
						and tapahtuma.selite like concat('%',hyllyalue,'-',hyllynro,'-',hyllyvali,'-',hyllytaso,'%')
						LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						and inventointiaika >= '$vva-$kka-$ppa 00:00:00'
						and inventointiaika <= '$vvl-$kkl-$ppl 23:59:59'
						$lisa
						GROUP BY 1,2,3,4,5,6,7,8,9,10,11
						ORDER BY sorttauskentta";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Yht‰‰n tuotetta ei lˆytynyt")."!</font><br><br>";
				$tee  = '';
				$tila = '';
			}
			elseif ($tila != 'tulosta'){
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Inventointiaika")."</th><th>".t("Kpl")."</th><th>".t("Poikkeamaprosentti")." %</th><th>".t("Selite")."</th>";
				echo "</tr>";


				while ($tuoterow = mysql_fetch_array($saldoresult)) {
					echo "<tr>";
					echo "<td>$tuoterow[tuoteno]</td><td>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys'])."</td><td>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td><td>$tuoterow[inventointiaika]</td><td>$tuoterow[kpl]</td><td>$tuoterow[inventointipoikkeama]</td><td>$tuoterow[selite]</td>";
					echo "</tr>";
				}
				echo "</table>";
			}

		}
		else {
			echo "<font class='error'>".t("Et syˆtt‰nyt mit‰‰n j‰rkev‰‰! Skarppaas v‰h‰n")."!</font><br><br>";
			$tee  = '';
			$tila = '';
		}

		if ($tila == 'tulosta') {
			$tee = 'TULOSTA';
		}
	}
	if ($tee == "TULOSTA") {
		if (mysql_num_rows($saldoresult) > 0 ) {
			if ($prosmuutos == 0) {
				$muutos = $kplmuutos;
				$yks = t("yks");
			}
			else {
				$muutos = $prosmuutos;
				$yks = "%";
			}
			//kirjoitetaan  faili levylle..
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$filenimi = "/tmp/Inventointilista-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($filenimi, "w+");

			$pp = date('d');
			$kk = date('m');
			$vv = date('Y');

			$ots  = "".t("Inventointipoikkeamalista, poikkeama ")." $muutos $yks $pp.$kk.$vv $yhtiorow[nimi]\n\n";
			$ots .= sprintf ('%-14.14s', 	t("Paikka"));
			$ots .= sprintf ('%-21.21s', 	t("Tuoteno"));
			$ots .= sprintf ('%-21.21s', 	t("Toim.Tuoteno"));
			$ots .= sprintf ('%-10.10s',	t("Poikkeama"));
			$ots .= sprintf ('%-9.9s', 		t("Yksikkˆ"));
			$ots .= sprintf ('%-20.20', 	t("Inv.pvm"));
			$ots .= "\n";
			$ots .= "-------------------------------------------------------------------------------------------------------\n\n";
			fwrite($fh, $ots);
			$ots = chr(12).$ots;

			$rivit = 1;
			while ($row = mysql_fetch_array($saldoresult)) {
				if ($rivit >= 19) {
					fwrite($fh, $ots);
					$rivit = 1;
				}
				if ($yks == '%') {
					$row["yksikko"] = "%";
					$row["kpl"] = $row["inventointipoikkeama"];
				}

				//katsotaan onko tuotetta tilauksessa
				$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and varattu>0 and tyyppi='O'";
				$result1 = mysql_query($query) or pupe_error($query);
				$prow    = mysql_fetch_array($result1);

				if ($row["inventointiaika"]=='0000-00-00 00:00:00') {
					$row["inventointiaika"] = t("Ei inventoitu");
				}

				$prn  = sprintf ('%-14.14s', 	$row["hyllyalue"]." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]);
				$prn .= sprintf ('%-21.21s', 	$row["tuoteno"]);
				$prn .= sprintf ('%-21.21s', 	$row["toim_tuoteno"]);
				$prn .= sprintf ('%-10.10s',	$row["kpl"]);
				$prn .= sprintf ('%-9.9s', 		$row["yksikko"]);
				$prn .= sprintf ('%-15.15s', 	$row["inventointiaika"]);
				$prn .= "\n\n";
				fwrite($fh, $prn);
				$rivit++;
			}

			fclose($fh);

			//k‰‰nnet‰‰n kaunniksi
			$line = exec("a2ps -o ".$filenimi.".ps -r --medium=A4 --chars-per-line=105 --no-header --columns=1 --margin=0 --borders=0 $filenimi");
			//itse print komento...
			$line2 = exec("$komento[Inventointipoikkeamat] ".$filenimi.".ps");
			
			echo "<br>".t("Inventointipoikkeamalista tulostuu")."!<br><br>";

			$tee = '';

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f ".$filenimi.".ps");
		}
	}

	if ($tee == '') {
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='Y'>";

		echo "<table><tr>";
		echo "<th>".t("Valitse toiminto")."</th><td colspan='3'>
				<select name='tila'>
				<option value='inventoi'>".t("N‰yt‰ ruudulla")."</option>
				<option value='tulosta'>".t("Tulosta inventointipoikkeamalista")."</option>
				</select></td></tr>";

		echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";


		echo "<tr><th>".t("Listaa tuotteet joilla poikkeamaprosentti on v‰hint‰‰n")."</th>
				<td colspan='3'><input type='text' size='15' name='prosmuutos' value='$prosmuutos' size='3'> ".t("prosenttia")."</td><td class='back'>".t("Lis‰tyt tuotteet + merkill‰ ja v‰hennetyt tuotteet - merkill‰, tai absoluuttinen.")."</td></tr>";

		echo "<tr><th>".t("Listaa tuotteet joiden kappalem‰‰r‰ on muuttunut v‰hint‰‰n")."</th>
				<td colspan='3'><input type='text' size='15' name='kplmuutos' value='$kplmuutos' size='3'> ".t("kappaletta")."</td></tr>";

		echo "<tr><td class='back'><br><input type='submit' value='".t("Aja raportti")."'></td></tr></form></table>";
	}

	require ("../inc/footer.inc");
?>
