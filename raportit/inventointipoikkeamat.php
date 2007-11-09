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
				$lisa .= " and tuotepaikat.inventointipoikkeama <= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0 and substr($prosmuutos,0,1) == '+') {
				$prosmuutos   = (int) $prosmuutos;
				$lisa .= " and tuotepaikat.inventointipoikkeama >= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0) {
				$prosmuutos   = (int) $prosmuutos;
				$lisa .= " and (inventointipoikkeama <= '-$prosmuutos' or tuotepaikat.inventointipoikkeama >= '$prosmuutos') ";
			}

			if ($kplmuutos <> 0) {
				$lisa .= " and abs(tapahtuma.kpl) >= abs('$kplmuutos') ";
			}
			
			if ($sarjat != "") {
				$lisa .= " and tuote.sarjanumeroseuranta='S' ";
			}
			
			if ($vararvomuu != "") {
				$lisa .= " HAVING arvo != 0 ORDER BY arvo";
			}
			else {
				$lisa .= " ORDER BY sorttauskentta";	
			}

			$query = "	SELECT tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, 
						tuotepaikat.inventointiaika, tuotepaikat.inventointipoikkeama, tapahtuma.selite, tapahtuma.kpl, tapahtuma.tunnus ttunnus, tapahtuma.hinta,
						(tapahtuma.hinta*tapahtuma.kpl) arvo,
						left(tapahtuma.laadittu, 10) tapvm,
						(SELECT group_concat(toim_tuoteno) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno) as toim_tuoteno,
						concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta
						FROM tuote
						JOIN tapahtuma ON tapahtuma.yhtio = tuote.yhtio and tapahtuma.laji='Inventointi' and tapahtuma.tuoteno=tuote.tuoteno
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tapahtuma.yhtio and tuotepaikat.tuoteno=tapahtuma.tuoteno and tapahtuma.selite like concat('%',tuotepaikat.hyllyalue,'-',tuotepaikat.hyllynro,'-',tuotepaikat.hyllyvali,'-',tuotepaikat.hyllytaso,'%')						
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						and tapahtuma.laadittu >= '$vva-$kka-$ppa 00:00:00'
						and tapahtuma.laadittu <= '$vvl-$kkl-$ppl 23:59:59'
						$lisa";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Yht‰‰n tuotetta ei lˆytynyt")."!</font><br><br>";
				$tee  = '';
				$tila = '';
			}
			elseif ($tila != 'tulosta'){
				echo "<table>";
				
				echo "<tr>";
				echo "<th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Inventointiaika")."</th><th>".t("Kpl")."</th><th>".t("Poikkeamaprosentti")." %</th>";
				echo "</tr>";

				while ($tuoterow = mysql_fetch_array($saldoresult)) {
					echo "<tr><th colspan='5'>$tuoterow[tuoteno]</th></tr>";
					
					echo "<td>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys'])."</td><td>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td><td>".tv1dateconv($tuoterow[inventointiaika], "P")."</td><td>$tuoterow[kpl]</td><td>$tuoterow[inventointipoikkeama]</td></tr>";
					
					echo "<tr><td colspan='5'>$tuoterow[selite]</td></tr>";
					
					$query = "	SELECT sum(tiliointi.summa) summa
							FROM lasku use index (yhtio_tila_tapvm)
								JOIN tiliointi ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and tiliointi.korjattu='' and tiliointi.tilino='$yhtiorow[varasto]'
								WHERE lasku.yhtio	= '$kukarow[yhtio]'
								and lasku.tila     	= 'X'
								and lasku.tapvm     = '$tuoterow[tapvm]'
								and lasku.viite    	= '$tuoterow[ttunnus]'";
					$kpitores = mysql_query($query) or pupe_error($query);
					$kpitorow = mysql_fetch_array($kpitores);
					
					echo "<tr><td>".t("Varastonmuutos").": ".sprintf('%.2f', $tuoterow["kpl"]*$tuoterow["hinta"])."</td><td colspan='4'>".t("Kirjanpito").": ".sprintf('%.2f', $kpitorow["summa"])."</td></tr>";
					echo "<tr style='height: 5px;'></tr>";
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

		echo "<tr><th>".t("Listaa vain sarjanumerolliset tuotteet")."</th>
				<td colspan='3'><input type='checkbox' name='sarjat' $sel></td></tr>";
		
		echo "<tr><th>".t("Listaa vain varastonarvoon vaikuttaneet inventoinnit")."</th>
				<td colspan='3'><input type='checkbox' name='vararvomuu' $sel></td></tr>";

		echo "<tr><td class='back'><br><input type='submit' value='".t("Aja raportti")."'></td></tr></form></table>";
	}

	require ("../inc/footer.inc");
?>
