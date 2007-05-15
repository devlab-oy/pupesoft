<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require ("../inc/parametrit.inc");

	if ($toim == 'MYYNTI') {
		echo "<font class='head'>".t("Asiakkaan tuoteostot").":</font><hr>";
	}
	if ($toim == 'OSTO') {
		echo "<font class='head'>".t("Tilautut tuotteet").":</font><hr>";
	}


	if ($tee == 'NAYTATILAUS') {
		require ("naytatilaus.inc");
		echo "<hr>";
		$tee = "TULOSTA";
	}


	if ($ytunnus != '') {
		if ($toim == 'MYYNTI') {
			require ("../inc/asiakashaku.inc");
		}
		if ($toim == 'OSTO') {
			require ("../inc/kevyt_toimittajahaku.inc");
			$asiakasid = $toimittajarow['tunnus'];
		}
	}

	if ($ytunnus != '' or $tuoteno != '') {
		echo "<form method='post' action='$PHP_SELF' autocomplete='off'>
			<input type='hidden' name='ytunnus' value='$ytunnus'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='tuoteno' value='$tuoteno'>
			<input type='hidden' name='tee' value='TULOSTA'>";

		echo "<table>";

		if ($kka == '')
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if ($vva == '')
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if ($ppa == '')
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		if ($kkl == '')
			$kkl = date("m");
		if ($vvl == '')
			$vvl = date("Y");
		if ($ppl == '')
			$ppl = date("d");

		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table>";



		if ($jarj != ''){
			$jarj = "ORDER BY $jarj";
		}
		else {
			$jarj = "ORDER BY lasku.laskunro desc";
		}


		if ($toim == 'OSTO') {
			$tila = " ('O') ";
			$tyyppi = "O";
		}
		else {
			$tila = " ('L','N','U') ";
			$tyyppi =  "L";
		}

		$query = "	SELECT distinct tilausrivi.tunnus, otunnus tilaus, laskunro, ytunnus, if(nimi!=toim_nimi,concat(toim_nimi,'<br>(',nimi,')'), nimi) nimi, if(postitp!=toim_postitp,concat(toim_postitp,'<br>(',postitp,')'), postitp) postitp, tuoteno, REPLACE(kpl+varattu,'.',',') kpl, REPLACE(tilausrivi.hinta,'.',',') hinta, REPLACE(rivihinta,'.',',') rivihinta, lasku.toimaika, lasku.lahetepvm, lasku.tila, lasku.alatila
					FROM tilausrivi, lasku
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and lasku.yhtio=tilausrivi.yhtio
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.tila in $tila
					and tilausrivi.tyyppi = '$tyyppi'
					and tilausrivi.laadittu >='$vva-$kka-$ppa 00:00:00'
					and tilausrivi.laadittu <='$vvl-$kkl-$ppl 23:59:59'
					and lasku.tunnus=tilausrivi.otunnus ";

		if ($tuoteno != '') {
			$query .= " and tilausrivi.tuoteno='$tuoteno' ";
		}

		if ($ytunnus != '') {
			$query .= " and lasku.liitostunnus = '$asiakasid' ";
		}

		$query .= "$jarj";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {

			echo "<br><table border='0' cellpadding='2' cellspacing='1'>";
			echo "<tr>";
			
			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&tuoteno=$tuoteno&ytunnus=$ytunnus&asiakasid=$asiakasid&jarj=".mysql_field_name($result,$i)."'>".t(mysql_field_name($result,$i))."</a></th>";
			}
			
			echo "<th align='left'>".t("Tyyppi")."</th>";
			echo "</tr>";

			$kplsumma = 0;
			$hintasumma = 0;
			$rivihintasumma = 0;
			
			while ($row = mysql_fetch_array($result)) {

				$ero="td";
				if ($tunnus==$row['tilaus']) $ero="th";

				echo "<tr class='aktiivi'>";
				
				for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
					if (mysql_field_name($result,$i) == 'toimaika' or mysql_field_name($result,$i) == 'lahetepvm') {
						echo "<$ero>".tv1dateconv($row[$i],"pitka")."</$ero>";
					}
					else {
						echo "<$ero>$row[$i]</$ero>";
					}
					
				}

				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				$kplsumma += $row["kpl"];
				$hintasumma += $row["hinta"];
				$rivihintasumma += $row["rivihinta"];
				
				
				//tehdään selväkielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				echo "<$ero>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

				echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='tee' value='NAYTATILAUS'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnus' value='$row[tilaus]'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='tuoteno' value='$tuoteno'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='submit' value='".t("Näytä tilaus")."'></td></form>";

				echo "</tr>";
			}
			
			echo "<tr><td colspan='7'>".t("Yhteensä").":</td><td>$kplsumma</td><td>$hintasumma</td><td>$rivihintasumma</td><td colspan='2'></td></tr>";
			
			
			echo "</table>";
		}
		
		else {
			echo "".t("Ei ostettuja tuotteita")."...<br><br>";
		}
	}

	//Etsi-kenttä
	echo "<br><table><form action = '$PHP_SELF' method='post'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='ppa' value='$ppa'>
			<input type='hidden' name='kka' value='$kka'>
			<input type='hidden' name='vva' value='$vva'>
			<input type='hidden' name='ppl' value='$ppl'>
			<input type='hidden' name='kkl' value='$kkl'>
			<input type='hidden' name='vvl' value='$vvl'>";


	if ($toim == 'MYYNTI') {
		echo "<tr><th>".t("Asiakas").":</th>";
	}
	if ($toim == 'OSTO') {
		echo "<tr><th>".t("Toimittaja").":</th>";
	}

	echo "<td><input type='text' name='ytunnus' value='$ytunnus' size='20'></td></tr>
			<tr><th>".t("Syötä tuotenumero").":</th>
			<td><input type='text' name='tuoteno' value='$tuoteno' size='20'></td>
			<td><input type='hidden' name='tee' value='ETSI'>
			<input type='submit' value='".t("Etsi")."'></td></tr></form></table>";

	require ("../inc/footer.inc");
?>
