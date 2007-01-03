<?php
	///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');
	echo "<font class='head'>".t("Intrastat")." ".t("$tapa")."-".t("tarkistus ruudulle").":</font><hr>";


	if ($tee == "tulosta") {

		//tuoti vai vienti
		if ($tapa == "tuonti") {
			// kohdistettu X kun viety varastoon..
			$where = " lasku.kohdistettu='X' and tila = 'K' and lasku.vienti='F' ";
		}
		elseif ($tapa == "vienti") {
			$where = " tila='U' and alatila='X' and lasku.vienti='E' ";
		}
		else {
			echo t("Koita nyt p‰‰tt‰‰, haluutko tuontia vai vienti‰")."!";
			exit;
		}

		//t‰ss‰ tulee sitten nimiketietueet
		$query = "	SELECT
					tuote.tullinimike1,
					lasku.maa_lahetys,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					lasku.maa_maara,
					lasku.laskunro,
					tuote.tuoteno,
					lasku.kauppatapahtuman_luonne,
					lasku.kuljetusmuoto,
					round(sum(tilausrivi.kpl),0) kpl,
					tullinimike.su_vientiilmo su,";

		if ($tapa == 'vienti') {
			$query .= "	if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
						if(round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta
						FROM lasku use index (yhtio_tila_tapvm)";
		}
		else {
			$query .= "	if(round(sum((tilausrivi.kpl*tilausrivi.hinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.kpl*tilausrivi.hinta/lasku.summa)*lasku.bruttopaino),0), 1) paino,
						if(round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta
						FROM lasku use index (yhtio_tila_mapvm)";
		}

		// tehd‰‰n kauniiseen muotoon annetun kauden eka ja vika pvm
		$vva = date("Y",mktime(0, 0, 0, $kk, 1, $vv));
		$kka = date("m",mktime(0, 0, 0, $kk, 1, $vv));
		$ppa = date("d",mktime(0, 0, 0, $kk, 1, $vv));
		$vvl = date("Y",mktime(0, 0, 0, $kk+1, 0, $vv));
		$kkl = date("m",mktime(0, 0, 0, $kk+1, 0, $vv));
		$ppl = date("d",mktime(0, 0, 0, $kk+1, 0, $vv));

		$query .= "	JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
					LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
					WHERE $where
					and lasku.kauppatapahtuman_luonne != '999'
					and lasku.yhtio='$kukarow[yhtio]'";

		if ($tapa == 'vienti') {
			$query .= "	and tapvm>='$vva-$kka-$ppa'
						and tapvm<='$vvl-$kkl-$ppl'";
		}
		else {
			$query .= "	and mapvm>='$vva-$kka-$ppa'
						and mapvm<='$vvl-$kkl-$ppl'";
		}

		$query .= "	GROUP BY tuote.tullinimike1, lasku.maa_lahetys, alkuperamaa, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne
					ORDER BY laskunro, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$lask 		= 1;
		$nim 		= "";
		$arvoyht 	= 0;
		$virhe 		= 0;

		echo "<table>";

		echo "<tr><th>#</th><th>".t("Tullinimike")."</th><th>".t("Alkuper‰maa")."</th><th>".t("L‰hetysmaa")."</th><th>".t("M‰‰r‰maa")."</th><th>".t("Kuljetusmuoto")."</th><th>".t("Kauppat. luonne")."</th><th>".t("Tilastoarvo")."</th><th>".t("Paino")."</th><th>".t("2-paljous")."</th><th>".t("2-paljous m‰‰r‰")."</th><th>".t("Laskutusarvo")."</th></tr>";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";

			echo "<td>$lask</td>";							//j‰rjestysnumero
			echo "<td>$row[tullinimike1]</td>";				//Tullinimike CN


			if ($tapa == "tuonti") {
				echo "<td>$row[alkuperamaa]</td>";			//alkuper‰maa
			}
			else {
				echo "<td></td>";
			}

			if ($tapa == "tuonti") {
				echo "<td>$row[maa_lahetys]</td>";			//l‰hetysmaa
			}
			else {
				echo "<td></td>";
			}

			if ($tapa == "vienti") {
				echo "<td>$row[maa_maara]</td>";			//m‰‰r‰maa
			}
			else {
				echo "<td></td>";
			}

			echo "<td>$row[kuljetusmuoto]</td>";	//kuljetusmuoto
			echo "<td>$row[kauppatapahtuman_luonne]</td>";	//kauppatapahtuman luonne

			echo "<td>$row[rivihinta]</td>";		//tilastoarvo
			echo "<td>$row[paino]</td>";			//nettopaino

			if ($row["su"] != '') {
				echo "<td>$row[su]</td>"; 			//2 paljouden lajikoodi
				echo "<td>$row[kpl]</td>";			//2 paljouden m‰‰r‰
			}
			else {
				echo "<td></td>"; 					//2 paljouden lajikoodi
				echo "<td></td>";					//2 paljouden m‰‰r‰
			}
			echo "<td>$row[rivihinta]</td>";		//nimikkeen laskutusarvo

			echo "</tr>";

			$lask++;
			$arvoyht += $row["rivihinta"];
		}


		echo "<tr>";
		echo "<th colspan='7'>".t("Yhteens‰").":</td>";

		$maara = $lask-1;

		echo "<th>$arvoyht</th>";			//laskutusarvo yhteens‰
		echo "</tr>";

		echo "</table>";
	}

	//K‰yttˆliittym‰
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if ($tapa == "tuonti") {
		$sel1 = "SELECTED";
	}
	if ($tapa == "vienti") {
		$sel2 = "SELECTED";
	}

	if (!isset($kk))
		$kk = date(m);
	if (!isset($vv))
		$vv = date(Y);

	echo "<input type='hidden' name='tee' value='tulosta'>";
	echo "<tr><th>".t("Kumpi")."</th><td colspan='2'><select name='tapa'><option value='tuonti' $sel1>".t("Tuonti")."</option><option value='vienti' $sel2>".t("Vienti")."</option></select></td></tr>";	echo "<tr><th>".t("Syˆt‰ p‰iv‰m‰‰r‰ (kk-vvvv)")."</th>";

	echo "	<td><input type='text' name='kk' value='$kk' size='3'></td>
			<td><input type='text' name='vv' value='$vv' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";


	require("../inc/footer.inc");
?>