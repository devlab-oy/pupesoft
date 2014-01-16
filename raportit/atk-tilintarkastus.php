<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	$tee = isset($tee) ? trim($tee) : "";
	$tositetyyppi_mukaan = isset($tositetyyppi_mukaan) ? trim($tositetyyppi_mukaan) : "";

	if ($tee == "lataa_tiedosto") {
		readfile("/tmp/$tmpfilenimi");
		unlink("/tmp/$tmpfilenimi");
		exit;
	}

	// tehd‰‰n valtiontilintarkastajille atk-tarkistusmateriaalia
	echo "<font class='head'>Atk-tilintarkastus</font><hr>";

	// default arvot yhtiorowlta
	if (!isset($alku))  $alku  = substr($yhtiorow['tilikausi_alku'],0,4)  - 1 . substr($yhtiorow['tilikausi_alku'],4);
	if (!isset($loppu)) $loppu = substr($yhtiorow['tilikausi_loppu'],0,4) - 1 . substr($yhtiorow['tilikausi_loppu'],4);

	if ($alku != '') {
		list($vv, $kk, $pp) = explode("-", $alku);
		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>Virheellinen alkupvm $alku</font><br><br>";
			$tee = "";
		}
	}

	if ($loppu != '') {
		list($vv, $kk, $pp) = explode("-", $loppu);
		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>Virheellinen loppupvm $loppu</font><br><br>";
			$tee = "";
		}
	}

	if ($tee == "raportti") {

		/* tiliˆinnit */

		// keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		$file1 = "tilioinnit-".md5(uniqid(rand(),true)).".txt";

		// avataan faili
		$fh = fopen("/tmp/".$file1, "w");

		// haetaan kaikki vuoden tapahtumat.. uh
		$query  = "	SELECT tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti,
					tiliointi.summa, tiliointi.vero, tiliointi.selite, tiliointi.created_by,
					tiliointi.laadittu, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.osoitetark, lasku.postino, lasku.postitp, lasku.alatila, lasku.tila
					FROM tiliointi
					JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
					where tiliointi.yhtio	= '$kukarow[yhtio]'
					and tiliointi.tapvm    >= '$alku'
					and tiliointi.tapvm    <= '$loppu'
					and tiliointi.korjattu	= ''";
		$result = pupe_query($query);

		while($row = mysql_fetch_array($result)) {

			$rivi  = sprintf("%-10.10s", $row['tapvm']);		// p‰iv‰m‰‰r‰ 10 merkki‰ (vvvv-kk-pp)
			$rivi .= sprintf("%6.6s",    $row['tilino']);		// tilinumero 6 merkki‰
			$rivi .= sprintf("%6.6s",    $row['kustp']);		// kustannuspaikka 6 merkki‰
			$rivi .= sprintf("%6.6s",    $row['kohde']);		// kohde 6 merkki‰
			$rivi .= sprintf("%6.6s",    $row['projekti']);		// projekti 6 merkki‰
			$rivi .= sprintf("%13.13s",  $row['summa']);		// summa 13 merkki‰ (desimaalierotin piste)
			$rivi .= sprintf("%7.7s",    $row['vero']);			// vero 7 merkki‰ (desimaalierotin piste)
			$rivi .= sprintf("%-50.50s", $row['selite']);		// selite 50 merkki‰
			$rivi .= sprintf("%-10.10s", $row['created_by']);		// created_byn nimi 10 merkki‰
			$rivi .= sprintf("%-19.19s", $row['laadittu']);		// laadittuaika 19 merkki‰ (vvvv-kk-pp hh:mm:ss)
			$rivi .= sprintf("%-15.15s", $row['ytunnus']);		// asiakkaan/toimittajan tunniste 15 merkki‰
			$rivi .= sprintf("%-45.45s", $row['nimi']);			// asiakkaan/toimittajan nimi 45 merkki‰
			$rivi .= sprintf("%-45.45s", $row['nimitark']);		// asiakkaan/toimittajan nimitarkenne 45 merkki‰
			$rivi .= sprintf("%-45.45s", $row['osoite']);		// asiakkaan/toimittajan osoite 45 merkki‰
			$rivi .= sprintf("%-45.45s", $row['osoitetark']);	// asiakkaan/toimittajan osoitetarkenne 45 merkki‰
			$rivi .= sprintf("%-15.15s", $row['postino']);		// asiakkaan/toimittajan postinumero 15 merkki‰
			$rivi .= sprintf("%-45.45s", $row['postitp']);		// asiakkaan/toimittajan postitoimipaikka 45 merkki‰

			if ($tositetyyppi_mukaan != "") {
				// Laitetaan tositetyyppi mukaan selkokielisen‰
				if ($row["tila"] == "U") {
					$rivi .= sprintf("%-11.11s", "Myynti");
				}
				elseif (in_array($row["tila"], array("H", "Y", "M", "P", "Q"))) {
					$rivi .= sprintf("%-11.11s", "Osto");
				}
				elseif ($row["tila"] == "X" and $row["alatila"] == "E") {
					$rivi .= sprintf("%-11.11s", "Epakurantti");
				}
				elseif ($row["tila"] == "X" and $row["alatila"] == "I") {
					$rivi .= sprintf("%-11.11s", "Inventointi");
				}
				elseif ($row["tila"] == "X") {
					$rivi .= sprintf("%-11.11s", "Muu tosite");
				}
				else {
					$rivi .= sprintf("%-11.11s", $row["tila"]);
				}
			}

			$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

			if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep‰onnistui!");
		}

		// suljetaan tiedosto
		fclose($fh);

		/* tilikartta */

		// keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		$file2 = "tilikartta-".md5(uniqid(rand(),true)).".txt";

		// avataan faili
		$fh = fopen("/tmp/".$file2, "w");

		// haetaan kaikki tilit
		$query  = "select * from tili where yhtio='$kukarow[yhtio]' order by tilino";
		$result = pupe_query($query);

		while($row = mysql_fetch_array($result)) {

			$rivi  = sprintf("%-6.6s",   $row['tilino']);		// tilinumero 6 merkki‰
			$rivi .= sprintf("%-35.35s", $row['nimi']);			// selite 35 merkki‰
			$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

			if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep‰onnistui!");
		}

		// suljetaan tiedosto
		fclose($fh);

		/* kustannuspaikat, projektit ja kohteet */

		// keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		$file3 = "kustprojkoht-".md5(uniqid(rand(),true)).".txt";

		// avataan faili
		$fh = fopen("/tmp/".$file3, "w");

		// haetaan kaikki kustannuspaikat
		$query  = "	SELECT *
					from kustannuspaikka
					where yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					ORDER BY tyyppi, koodi+0, koodi, nimi";
		$result = pupe_query($query);

		while($row = mysql_fetch_array($result)) {

			if ($row['tyyppi'] == 'K') {
				$tyyppi = "Kustannuspaikka";
			}
			elseif ($row['tyyppi'] == 'O') {
				$tyyppi = "Kohde";
			}
			elseif ($row['tyyppi'] == 'P') {
				$tyyppi = "Projekti";
			}
			else {
				$tyyppi = "";
			}

			$rivi  = sprintf("%-15.15s", $tyyppi);				// tyyppi 15 merkki‰
			$rivi .= sprintf("%11.11s",  $row['tunnus']);		// tunnus 11 merkki‰
			$rivi .= sprintf("%-35.35s", $row['nimi']);			// nimi 35 merkki‰
			$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

			if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep‰onnistui!");
		}

		// suljetaan tiedosto
		fclose($fh);

		// tehd‰‰n failista zippi
		chdir("/tmp");
		exec("/usr/bin/zip Tilintarkastus-{$kukarow["yhtio"]}.zip ".escapeshellarg($file1)." ".escapeshellarg($file2)." ".escapeshellarg($file3));
		unlink($file1);
		unlink($file2);
		unlink($file3);

		echo "<table>";
		echo "<tr><th>".t("Tallenna tulos").":</th>";
		echo "<form method='post' class='multisubmit'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
		echo "<input type='hidden' name='tmpfilenimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
		echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";

	}

	echo "<form name='vero' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='raportti'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>Anna alkupvm</th>";
	echo "<td><input type='text' name='alku' value='$alku'></td>";
	echo "</tr><tr>";
	echo "<th>Anna alkupvm</th>";
	echo "<td><input type='text' name='loppu' value='$loppu'></td>";
	echo "</tr><tr>";
	echo "<th>Lis‰‰ tositetyyppi ainestoon</th>";
	echo "<td><input type='checkbox' name='tositetyyppi_mukaan'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><input type='submit' value='Aja'>";
	echo "</form>";

	// kursorinohjausta
	$formi  = "vero";
	$kentta = "alku";

	require ("inc/footer.inc");
