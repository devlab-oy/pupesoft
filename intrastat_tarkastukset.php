<?php
	require('inc/parametrit.inc');
	echo "<font class='head'>Intrastat ilmoitus tarkastusajoja:</font><hr>";


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
			echo t("Koita nyt päättää, haluutko tuontia vai vientiä")."!";
			exit;
		}

		//tässä tulee sitten nimiketietueet
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
			$query .= "	if (round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi * 
						(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
						/ lasku.summa) * lasku.bruttopaino), 0) > 0.5, 
						round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi * 
						(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
						/ lasku.summa) * lasku.bruttopaino), 0), 1) as paino,
						if(round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta
						FROM lasku use index (yhtio_tila_mapvm)";
		}

		// tehdään kauniiseen muotoon annetun kauden eka ja vika pvm
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

		$ulos = "<table>";
		$ulos .= "<tr>";
		$ulos .= "<th>Laskunro</th>";
		$ulos .= "<th>Tuoteno</th>";
		$ulos .= "<th>Nimitys</th>";
		$ulos .= "<th>Tullinimike</th>";
		$ulos .= "<th>KT</th>";
		$ulos .= "<th>AM</th>";
		$ulos .= "<th>LM</th>";
		$ulos .= "<th>MM</th>";
		$ulos .= "<th>KM</th>";
		$ulos .= "<th>Rivihinta</th>";
		$ulos .= "<th>Paino</th>";
		$ulos .= "<th>Toinen paljous</th>";
		$ulos .= "<th>Kpl</th>";
		$ulos .= "<th>Virhe</th>";
		$ulos .= "</tr>";

		$bruttopaino = 0;
		$rivihinta = 0;
		$totkpl = 0;
		$virhe = 0;

		while ($row = mysql_fetch_array($result)) {
			/*
			$cn1 = $row["tullinimike1"];
			$cn2 = substr($row["tullinimike1"],0,6);
			$cn3 = substr($row["tullinimike1"],0,4);

			$query = "select cn, dm, su from tullinimike where cn='$cn1' and kieli = '$yhtiorow[kieli]'";
			$tulliresult1 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn2' and kieli = '$yhtiorow[kieli]'";
			$tulliresult2 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn3' and kieli = '$yhtiorow[kieli]'";
			$tulliresult3 = mysql_query($query) or pupe_error($query);

			$tullirow1 = mysql_fetch_array($tulliresult1);
			$tullirow2 = mysql_fetch_array($tulliresult2);
			$tullirow3 = mysql_fetch_array($tulliresult3);
			*/
			// tehdään tarkistukset

			// jos ollaan valittu vaan joku tietty tullinimike näytetään vaan se rivi sitte
			if ($cn == "" or $cn == $row["tullinimike1"]) {

				$toim = $tapa;
				require ("inc/intrastat_tarkistukset.inc");

				$ulos .= "<tr>";
				$ulos .= "<td>".$row["laskunro"]."</td>";
				$ulos .= "<td>".$row["tuoteno"]."</td>";
				$ulos .= "<td>".$row["nimitys"]."</td>";
				$ulos .= "<td>".$row["tullinimike1"]."</td>";
				$ulos .= "<td>".$row["kauppatapahtuman_luonne"]."</td>";
				$ulos .= "<td>".$row["alkuperamaa"]."</td>";
				$ulos .= "<td>".$row["maa_lahetys"]."</td>";
				$ulos .= "<td>".$row["maa_maara"]."</td>";
				$ulos .= "<td>".$row["kuljetusmuoto"]."</td>";
				$ulos .= "<td align='right'>".$row["rivihinta"]."</td>";
				$ulos .= "<td align='right'>".$row["paino"]."</td>";
				$ulos .= "<td>".$row["su"]."</td>";
				$ulos .= "<td align='right'>".$row["kpl"]."</td>";
				$ulos .= "<td><font class='error'>".$virhetxt."</font></td>";
				$ulos .= "</tr>";

				$bruttopaino += $row["paino"];
				$totsumma += $row["rivihinta"];
				$totkpl += $row["kpl"];

			}
		}

		$ulos .= "<tr>";
		$ulos .= "<th colspan='9'>Yhteensä:</th>";
		$ulos .= "<th>$totsumma</th>";
		$ulos .= "<th>$bruttopaino</th>";
		$ulos .= "<th></th>";
		$ulos .= "<th>$totkpl</th>";
		$ulos .= "<th></th>";
		$ulos .= "</tr>";
		$ulos .= "</table>";

		// echotaan koko taulukko
		echo $ulos;
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kk)) $kk = date(m);
	if (!isset($vv)) $vv = date(Y);

	if ($tapa == "tuonti") {
		$sel1 = "SELECTED";
	}
	if ($tapa == "vienti") {
		$sel2 = "SELECTED";
	}

	echo "<input type='hidden' name='tee' value='tulosta'>";

	echo "<tr><th>Kumpi</th><td colspan='2'><select name='tapa'><option value='tuonti' $sel1>Tuonti</option><option value='vienti' $sel2>Vienti</option></select></td></tr>";

	echo "<tr><th>Syötä päivämäärä (kk-vvvv)</th>
			<td><input type='text' name='kk' value='$kk' size='3'></td>
			<td><input type='text' name='vv' value='$vv' size='5'></td>";

	echo "<tr><th>Tullinimike (ei pakollinen)</th>
			<td colspan='2'><input type='text' name='cn' value='$cn'></td>";

	echo "<td class='back'><input type='submit' value='Aja tarkistus'></td></tr></table>";


	require("inc/footer.inc");
?>