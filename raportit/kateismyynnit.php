<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Käteismyynnit")." $myy:</font><hr>";
	
	if ($tee != '') {

		//Jos halutaa failiin
		if ($printteri != '') {
			$vaiht = 1;
		}
		else {
			$vaiht = 0;
		}


		$kassat = "";
		$lisa   = "";
		$lisa2  = "";

		if (is_array($kassakone)) {
			foreach($kassakone as $var) {
				$kassat .= "'".$var."',";
			}
			$kassat = substr($kassat,0,-1);
			$kassat = " avainsana.selite in ($kassat) ";
		}

		if($muutkassat != '') {
			if ($kassat != '') {
				$kassat = " and (".$kassat." or avainsana.selite is null)";
			}
			else {
				$kassat = " and avainsana.selite is null ";
			}
		}
		elseif($kassat != '') {
			$kassat = " and ".$kassat;
		}
		else {
			$kassat = " and avainsana.selite = 'EI NAYTETA KASSAKONEITA EIHAN' ";
		}


		if ($myyjanro > 0) {
			$query = "	SELECT tunnus
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]'
						and myyja = '$myyjanro'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			$lisa = " and lasku.myyja='$row[tunnus]' ";
		}
		elseif ($myyja != '') {
			$lisa = " and lasku.laatija='$myyja' ";
		}


		$lisa2 = " and lasku.vienti in (";

		if ($koti == 'KOTI' or ($koti=='' and $ulko=='')) {
			$lisa2 .= "''";
		}

		if ($ulko == 'ULKO') {
			if ($koti == 'KOTI') {
				$lisa2 .= ",";
			}
			$lisa2 .= "'K','E'";
		}
		$lisa2 .= ") ";


		//Haetaan käteislaskut
		$query = "	SELECT lasku.nimi, lasku.ytunnus, lasku.laskunro, lasku.tunnus, lasku.summa, lasku.laskutettu, lasku.tapvm,
					if(kuka.kassamyyja is null or kuka.kassamyyja='', 'Muut', kuka.kassamyyja) kassa, kuka.nimi myyja,
					if(!isnull(avainsana.selitetark),avainsana.selitetark, 'Muut') kassanimi
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen!=''
					LEFT JOIN kuka ON lasku.laskuttaja=kuka.kuka and lasku.yhtio=kuka.yhtio
					LEFT JOIN avainsana ON avainsana.yhtio=lasku.yhtio and avainsana.laji='KASSA' and avainsana.selite=kuka.kassamyyja
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and tapvm >= '$vva-$kka-$ppa'
					and tapvm <= '$vvl-$kkl-$ppl'
					and lasku.tila	= 'U'
					and lasku.alatila = 'X'
					$lisa
					$lisa2
					$kassat
					ORDER BY kassa, laskunro";
		$result = mysql_query($query) or pupe_error($query);


		echo "	<table><tr>
				<th nowrap>".t("Kassa")."</th>
				<th nowrap>".t("Asiakas")."</th>
				<th nowrap>".t("Ytunnus")."</th>
				<th nowrap>".t("Laskunumero")."</th>
				<th nowrap>".t("Pvm")."</th>
				<th nowrap>$yhtiorow[valkoodi]</th></tr>";


		if ($vaiht == 1) {
			//kirjoitetaan  faili levylle..
			$filenimi = "/tmp/KATKIRJA.txt";
			$fh = fopen($filenimi, "w+");

			$ots  = t("Käteismyynnin päiväkirja")." $yhtiorow[nimi] $ppa.$kka.$vva-$ppl.$kkl.$vvl\n\n";
			$ots .= sprintf ('%-20.20s', t("Kassa"));
			$ots .= sprintf ('%-25.25s', t("Asiakas"));
			$ots .= sprintf ('%-10.10s', t("Y-tunnus"));
			$ots .= sprintf ('%-12.12s', t("Laskunumero"));
			$ots .= sprintf ('%-12.12s', t("Pvm"));
			$ots .= sprintf ('%-13.13s', "$yhtiorow[valkoodi]");
			$ots .= "\n";
			$ots .= "---------------------------------------------------------------------------------------\n";
			fwrite($fh, $ots);
			$ots = chr(12).$ots;
		}

		$rivit = 1;
		$yhteensa = 0;
		$kassayhteensa = 0;

		while ($row = mysql_fetch_array($result)) {

			if ($edkassa != $row["kassa"] and $edkassa != '') {
				echo "<tr>";
				echo "<th colspan='5'>$edkassanimi yhteensä:</th>";
				echo "<th align='right'>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</th></tr>";

				if ($vaiht == 1) {
					$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteensä").":");
					$prn .= "............................................";
					$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
					$prn .= "\n\n";

					fwrite($fh, $prn);
					$rivit++;
				}
				$kassayhteensa = 0;
			}

			$edkassa 	 = $row["kassa"];
			$edkassanimi = $row["kassanimi"];

			echo "<tr>";
			echo "<td>$row[kassanimi]</td>";
			echo "<td>".substr($row["nimi"],0,23)."</td>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[laskunro]</td>";
			echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
			echo "<td align='right'>".str_replace(".",",",$row['summa'])."</td></tr>";

			if ($vaiht == 1) {
				if ($rivit >= 60) {
					fwrite($fh, $ots);
					$rivit = 1;
				}
				$prn  = sprintf ('%-20.20s', 	$row["kassanimi"]);
				$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
				$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
				$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
				$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
				$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
				$prn .= "\n";

				fwrite($fh, $prn);
				$rivit++;
			}
			$yhteensa += $row["summa"];
			$kassayhteensa += $row["summa"];
		}

		if ($edkassa != '') {
			echo "<tr>";
			echo "<th colspan='5'>$edkassa yhteensä:</th>";
			echo "<th align='right'>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</th></tr>";

			if ($vaiht == 1) {
				$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteensä").":");
				$prn .= "............................................";
				$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
				$prn .= "\n\n";
				fwrite($fh, $prn);
			}

			$kassayhteensa = 0;
		}

		if ($katsuori != '') {
			//Haetaan kassatilille laitetut suoritukset
			$query = "	SELECT suoritus.nimi_maksaja nimi, tiliointi.summa, lasku.laskutettu
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tiliointi use index (tositerivit_index) ON tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.tilino='$yhtiorow[kassa]'
						JOIN suoritus use index (tositerivit_index) ON suoritus.yhtio=tiliointi.yhtio and suoritus.ltunnus=tiliointi.aputunnus
						LEFT JOIN kuka ON lasku.laatija=kuka.kuka and lasku.yhtio=kuka.yhtio
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila	= 'X'
						and lasku.laskutettu >= '$vva-$kka-$ppa'
						and lasku.laskutettu <= '$vvl-$kkl-$ppl'
						ORDER BY lasku.laskunro";
			$result = mysql_query($query) or pupe_error($query);
			
			$kassayhteensa = 0;

			while ($row = mysql_fetch_array($result)) {

				echo "<tr>";
				echo "<td>".t("Käteissuoritus")."</td>";
				echo "<td>".substr($row["nimi"],0,23)."</td>";
				echo "<td>$row[ytunnus]</td>";
				echo "<td>$row[laskunro]</td>";
				echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
				echo "<td align='right'>".str_replace(".",",",$row['summa'])."</td></tr>";

				if ($vaiht == 1) {
					if ($rivit >= 60) {
						fwrite($fh, $ots);
						$rivit = 1;
					}
					$prn  = sprintf ('%-20.20s', 	t("Käteissuoritus"));
					$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
					$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
					$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
					$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
					$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
					$prn .= "\n";

					fwrite($fh, $prn);
					$rivit++;
				}
				$yhteensa += $row["summa"];
				$kassayhteensa += $row["summa"];
			}
			if ($kassayhteensa != 0) {
				echo "<tr>";
				echo "<th colspan='5'>".t("Käteissuoritukset yhteensä").":</th>";
				echo "<th align='right'>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</th></tr>";

				if ($vaiht == 1) {
					$prn  = sprintf ('%-35.35s', 	t("Käteissuoritukset yhteensä").":");
					$prn .= "............................................";
					$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
					$prn .= "\n\n";
					fwrite($fh, $prn);
				}

				$kassayhteensa = 0;
			}
		}
		echo "<tr><th colspan='5'>".t("Kaikki kassat yhteensä").":</th><th align='right'>".str_replace(".",",",sprintf('%.2f',$yhteensa))."</th></tr>";
		echo "</table>";

		if ($vaiht == 1) {
			$prn  = sprintf ('%-35.35s', 	t("Yhteensä").":");
			$prn .= "............................................";
			$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$yhteensa)));
			$prn .= "\n";
			fwrite($fh, $prn);

			fclose($fh);

			//haetaan tilausken tulostuskomento
			$query   = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
			$kirres  = mysql_query($query) or pupe_error($query);
			$kirrow  = mysql_fetch_array($kirres);
			$komento = $kirrow['komento'];

			$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --no-header --columns=1 --margin=0 --borders=0 $filenimi");

			// itse print komento...
			$line = exec("$komento $filenimi.ps");

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f $filenimi.ps");
		}

	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka))
		$kka = date("m");
	if (!isset($vva))
		$vva = date("Y");
	if (!isset($ppa))
		$ppa = date("d");


	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<tr><th>".t("Syötä myyjänumero")."</th><td colspan='3'><input type='text' size='10' name='myyjanro' value='$myyjanro'>";

	$query = "	SELECT tunnus, kuka, nimi, myyja
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimi";
	$yresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("TAI valitse käyttäjä")."</th><td colspan='3'><select name='myyja'>";
	echo "<option value='' >".t("Kaikki")."</option>";

	while($row = mysql_fetch_array($yresult)) {
		$sel = "";

		if ($row['kuka'] == $myyja) {
			$sel = 'selected';
		}

		echo "<option value='$row[kuka]' $sel>($row[kuka]) $row[nimi]</option>";
	}
	echo "</select></td></tr>";


	echo "<tr><td class='back'><br></td></tr>";


	$query  = "	SELECT *
				FROM avainsana 
				WHERE yhtio='$kukarow[yhtio]' 
				and laji='KASSA' 
				order by selite";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$sel='';
		if ((!isset($kassakone) and $tee=="") or $kassakone[$varow["selite"]] != '') $sel = 'CHECKED';
		echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='kassakone[$varow[selite]]' value='$varow[selite]' $sel> $varow[selitetark]</td></tr>";
	}

	$sel='';
	if ((!isset($muutkassat) and $tee=="") or $muutkassat != '') $sel = 'CHECKED';
	echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='muutkassat' value='MUUT' $sel>".t("Muut kassat")."</td></tr>";

	$sel='';
	if ((!isset($katsuori) and $tee=="") or $katsuori != '') $sel = 'CHECKED';
	echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='katsuori' value='MUUT' $sel>".t("Käteissuoritukset")."</td></tr>";

	echo "<tr><td class='back'><br></td></tr>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><td colspan='5' class='back'><br></td>";

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td></tr>";
	
	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	$chk1 = '';
	$chk2 = '';

	if ($koti == 'KOTI')
		$chk1 = "CHECKED";

	if ($ulko == 'ULKO')
		$chk2 = "CHECKED";

	if ($chk1 == '' and $chk2 == '') {
		$chk1 = 'CHECKED';
	}


	echo "<tr><th>".t("Kotimaan myynti")."</th>
			<td colspan='3'><input type='checkbox' name='koti' value='KOTI' $chk1></td></tr>";

	echo "<tr><th>".t("Vienti")."</th>
			<td colspan='3'><input type='checkbox' name='ulko' value='ULKO' $chk2></td></tr>";

	$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
	$kires = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Valitse tulostuspaikka").":</th>";

	echo "<td colspan='3'><select name='printteri'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow=mysql_fetch_array($kires)) {
		$select = '';

		if ($kirow["tunnus"] == $printteri)
			$select = "SELECTED";

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");
?>