<?php

	// jos yritet‰‰n‰ valita molempia niin ei anneta mit‰‰n!
	if ($asia_ytunnus != "" and $toim_ytunnus != "") {
		$ytunnus = $toim_ytunnus = $asia_ytunnus = $asiakasid = $toimittajaid = $tee = "";
	}

	if ($ytunnus == "" and $asia_ytunnus == "" and $asiakasid != "") {
		$tee = $asiakasid = "";
	}

	if ($asia_ytunnus != '' or $asiakasid != '') {
		if ($asiakasid == "") $tee = "";
		if ($ytunnus == "") $ytunnus = $asia_ytunnus;
		require ("inc/asiakashaku.inc");
		$asia_ytunnus = $ytunnus;
	}

	if ($ytunnus == "" and $toim_ytunnus == "" and $toimittajaid != "") {
		$tee = $toimittajaid = "";
	}

	if ($toim_ytunnus != '' or $toimittajaid != '') {
		if ($toimittajaid == "") $tee = "";
		if ($ytunnus == "") $ytunnus = $toim_ytunnus;
		require ("inc/kevyt_toimittajahaku.inc");
		$toim_ytunnus = $ytunnus;
	}

	if ($tee == '') {
		echo "<font class='head'>".t("P‰‰- ja p‰iv‰kirja")."</font><hr>
				<form name = 'valinta' action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'paakirja'>
				<table><tr>
				<th>".t("Tyyppi")."</th>
				<td><select name='tee'>
				<option value = 'K'>".t("P‰‰kirja")."
				<option value = 'P'>".t("P‰iv‰kirja")."
				</select></td></tr>
				<th>".t("Ajalta")."</th>
				<td><select name='alvv'>
				";

				for ($i = date("Y")+1; $i >= date("Y")-6; $i--) {
					if ($i == date("Y")) $sel = "selected";
					else $sel = "";
					echo "<option value='$i' $sel>$i</option>";
				}

				echo "
				</select>
				<select name='alvk'>
				<option value = '0'>".t("koko kalenterivuosi")."
				<option value = '1'>01
				<option value = '2'>02
				<option value = '3'>03
				<option value = '4'>04
				<option value = '5'>05
				<option value = '6'>06
				<option value = '7'>07
				<option value = '8'>08
				<option value = '9'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select>
				<select name='alvp'>
				<option value = '0'>".t("koko kuukausi")."
				<option value = '1'>01
				<option value = '2'>02
				<option value = '3'>03
				<option value = '4'>04
				<option value = '5'>05
				<option value = '6'>06
				<option value = '7'>07
				<option value = '8'>08
				<option value = '9'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				<option value = '13'>13
				<option value = '14'>14
				<option value = '15'>15
				<option value = '16'>16
				<option value = '17'>17
				<option value = '18'>18
				<option value = '19'>19
				<option value = '20'>20
				<option value = '21'>21
				<option value = '22'>22
				<option value = '23'>23
				<option value = '24'>24
				<option value = '25'>25
				<option value = '26'>26
				<option value = '27'>27
				<option value = '28'>28
				<option value = '29'>29
				<option value = '30'>30
				<option value = '31'>31
				</select></td>
				</tr>";

		echo "<tr><th>".t("tai koko tilikausi")."</th>";
	 	$query = "SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow["tunnus"]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
		}
		echo "</select></td>";
		echo "</tr>";

		echo "<tr>
				<th>Vain tili</th>
				<td><input type = 'text' name = 'tili' value = ''> - <input type = 'text' name = 'tili2' value = ''></td>
				</tr>";

		echo "<tr><th>".t("Vain kustannuspaikka")."</th>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain kohde")."</th>";
	 	$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='kohde'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain projekti")."</th>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain asiakas")."</th><td><input type='text' name='asia_ytunnus' value='$asiakasrow[ytunnus]'> $asiakasrow[nimi] <input type='hidden' name='asiakasid' value='$asiakasid'></td></tr>";
		echo "<tr><th>".t("Vain toimittaja")."</th><td><input type='text' name='toim_ytunnus' value='$toimittajarow[ytunnus]'> $toimittajarow[nimi] <input type='hidden' name='toimittajaid' value='$toimittajaid'></td></tr>";
		echo "<tr><th>".t("Vain k‰sin viedyt")."</th><td><input type='checkbox' name='kasin'></td></tr>";
		echo "<tr><th>".t("Tee Excel")."</th><td><input type='checkbox' name='excel' value = 'YES'></td></tr>";		
		echo "</table><br>
		      <input type = 'submit' value = '".t("N‰yt‰")."'></form>";
		$formi = 'valinta';
		$kentta = 'tili';
		require "inc/footer.inc";
		exit;
	}

	$alvv = (int) $alvv;
	$alvk = (int) $alvk;
	$alvp = (int) $alvp;
	$tkausi = (int) $tkausi;
	
	// Tutkitaan ensiksi, mille tilikaudelle pyydett‰v‰ lista lˆytyy, jos lista on sopiva
	$blvk = 0;
	$blvp = 0;
	
	if ($tkausi == 0) {
		$blvk = $alvk;
		$blvp = $blvk;
		if ($blvk==0) $blvk=1;
		if ($blvp==0) $blvp=1;
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and '$alvv-$blvk-$blvp' >= tilikausi_alku and '$alvv-$blvk-$blvp' <= tilikausi_loppu";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
		$result = mysql_query($query) or pupe_error($query);
	}
	
	if (mysql_num_rows($result) != 1) {
		echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei lˆytynyt")."</font>";
		exit;
	}
	$tilikaudetrow=mysql_fetch_array($result);

	$lisa = '';
	$lisa2 = '';
	$nimi = "P‰‰kirja";
	$alku = "CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili, DATE_FORMAT(tiliointi.tapvm,'%d.%m.%Y') tapvm";
	$jarj = "tiliointi.tilino, tiliointi.tapvm, tiliointi.summa";

	if ($tee == 'P') { // Haluttiinkin p‰iv‰kirja
		$nimi = "P‰iv‰kirja";
		$alku = "DATE_FORMAT(tiliointi.tapvm,'%d.%m.%Y') tapvm, CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili";
		$jarj = "tiliointi.tapvm, tiliointi.tilino, tiliointi.summa";
	}

	if ($tkausi != '0') {
        echo "<font class='head'>$nimi ".t("tilikaudelta")." $tilikaudetrow[tilikausi_alku] - $tilikaudetrow[tilikausi_loppu]</font><hr>";
		$lisa  = "tiliointi.tapvm <= '$tilikaudetrow[tilikausi_loppu]' and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'";
		$lisa2 = "tiliointi.tapvm < '$tilikaudetrow[tilikausi_alku]'"; //hassua hassua
	}
	elseif ($alvk == '0') {
		echo "<font class='head'>$nimi ".t("kalenterivuodelta")." $alvv</font><hr>";
		$lisa  = "YEAR(tiliointi.tapvm) = '$alvv'";
		$apualvv = $alvv + 1;
		$lisa2 = "tiliointi.tapvm < '$apualvv-01-01'";
	}
	elseif ($alvp == '0') {
		echo "<font class='head'>$nimi ".t("kaudelta")." $alvv-$alvk</font><hr>";
		$lisa  = "CONCAT_WS(' ',YEAR(tiliointi.tapvm),MONTH(tiliointi.tapvm)) = '$alvv $alvk'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-01'";
	}
	else {
		echo "<font class='head'>$nimi ".t("p‰iv‰lt‰")." $alvv-$alvk-$alvp</font><hr>";
		$lisa  = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
		$lisa2 = "tiliointi.tapvm < '$alvv-$alvk-$alvp'";
	}

	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and tiliointi.kohde = '" . $kohde . "'";
		$lisa2 .= " and tiliointi.kohde = '" . $kohde . "'";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and tiliointi.projekti = '" . $proj . "'";
		$lisa2 .= " and tiliointi.projekti = '" . $proj . "'";
	}
	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and tiliointi.kustp = '" . $kustp . "'";
		$lisa2 .= " and tiliointi.kustp = '" . $kustp . "'";
	}

	if ($tili2 == 0) $tili2 = $tili;

	if (strlen(trim($tili)) > 0) {
		$lisa .= " and tiliointi.tilino >= '" . $tili . "' and  tiliointi.tilino <= '" . $tili2 . "'";
		$lisa2 .= " and tiliointi.tilino >= '" . $tili . "' and  tiliointi.tilino <= '" . $tili2 . "'";
	}

	if ($kasin != '') {
		$lisa .= "and lasku.tila='X'";
	}

	if ($asiakasid != "") {
		$lisa .= " and lasku.liitostunnus='$asiakasid' and tila in ('U','L') ";
	}

	if ($toimittajaid != "") {
		$lisa .= " and lasku.liitostunnus='$toimittajaid' and tila in ('H','Y','M','P','Q') ";
	}

	$query = "	SELECT $alku , kustp.nimi kustp, kohde.nimi kohde, projekti.nimi projekti, CONCAT_WS(' ', selite, lasku.nimi) selite, tiliointi.summa, vero, tiliointi.ltunnus, tiliointi.tilino xtilino
				FROM tiliointi
				LEFT JOIN tili ON tili.tilino = tiliointi.tilino and tili.yhtio = tiliointi.yhtio
				LEFT JOIN lasku ON lasku.tunnus = tiliointi.ltunnus
				LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio = kustp.yhtio and tiliointi.kustp = kustp.tunnus
				LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio = kohde.yhtio and tiliointi.kohde = kohde.tunnus
				LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio = projekti.yhtio and tiliointi.projekti = projekti.tunnus
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' 
				and tiliointi.korjattu = '' 
				and $lisa
				ORDER BY $jarj";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<table><tr>";
		
	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		
		if(isset($workbook)) {
			$worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
		}		
	}
	
	if (strlen(trim($tili)) > 0) {
		echo "<th>".t("kum. summa")."</th>";
		
		if(isset($workbook)) {
			$worksheet->write($excelrivi, $i+1, t("kum. summa"), $format_bold);
		}
	}
	
	if(isset($workbook)) {
		$excelrivi++;
	}
	echo "</tr>";

	$summaplus 		= 0.0;
	$summamiinus 	= 0.0;
	$summa 			= 0.0;
	$edtili			= "";

	while ($trow = mysql_fetch_array ($result)) {
		
		echo "<tr>";

		for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
			
			if ($i == 0) {				
				if ($edtrow[$i] == $trow[$i]) { // Vaihtuiko joku??
					$trow[$i] = " ";
				}
				else {
					if ($eka != 0) {
						echo "<td></td><td></td><td></td><td></td><td></td><td></td><td align = 'right'>";
						printf("%.2f", $summa);
						echo "</td><td>*</td>";

						if (strlen(trim($tili)) > 0) {
							echo "<td></td>";
						}
						echo "</tr><tr>";
																	
						$summa = 0;
					}
					else {
						$eka = 1;
					}
					$edtrow[0] = $trow[0];
				}
				
				//jos tili on valittu lasketaan sen alkusaldo
				if (strlen(trim($tili)) > 0 and $edtili != $trow["xtilino"]) {
					$query = "	SELECT sum(summa) saldo
								FROM tiliointi
								WHERE tiliointi.yhtio 	= '$kukarow[yhtio]'
								and tiliointi.korjattu	= ''
								and tiliointi.tapvm    >= '$tilikaudetrow[tilikausi_alku]'
								and tiliointi.tilino 	= '$trow[xtilino]'";
					$alkres	  = mysql_query($query) or pupe_error($query);					
					$alkurow  = mysql_fetch_array ($alkres);
					$kumsumma = $alkurow["saldo"];

					//tilin alkusaldo
					echo "<td colspan='".(mysql_num_fields($result)-2)."'></td>";
					echo "<td align='right'>".sprintf("%.2f",$kumsumma)."</td></tr>";
					echo "<tr>";
					
					if(isset($workbook)) {
						for ($a=0; $a<mysql_num_fields($result)-2; $a++) {
							$worksheet->write($excelrivi, $a, "");
						}
						$worksheet->write($excelrivi, $a+1, $kumsumma);
						
						$excelrivi++;
					}
				}
			}
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, $i, $trow[$i]);	
			}
		
			if ($i > 5) {
				if ($i == 6) {

					if ($trow[$i] < 0) {
						$class = " class='error' ";
					}
					else {
						$class = " class='message' ";
					}

					echo "<td align='right' $class>";

					echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'><font $class>";
					echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
					echo "</font></a>";
					echo "</td>";

					if ($trow[$i] < 0 )
						$summamiinus += $trow[$i];
					else
						$summaplus += $trow[$i];
						
					$summa += $trow[$i];

				 }
				 else {
					echo "<td align = 'right' nowrap>";
					echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
					echo "</td>";
				 }

			}
			else {
				if (($i == 5)) $trow[$i] = str_replace("<br>"," ",$trow[$i]);

				echo "<td>$trow[$i]</td>";
			}
		}
		
		if (strlen(trim($tili)) > 0) {
			//lasketaan ja echotaan kumulatiivinen summa
			$kumsumma += $trow["summa"];
			
			echo "<td align='right'>".str_replace(".",",",sprintf("%.2f",$kumsumma))."</td>";
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, $i+1, $kumsumma);
			}
		}
		
		
		echo "</tr>";
		
		if(isset($workbook)) {
			$excelrivi++;
		}
		
		$edtili = $trow["xtilino"];
	}
	
	echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td align = 'right' nowrap>";
	printf("%.2f", $summa);
	echo "</td><td>*</td>";

	if (strlen(trim($tili)) > 0) {
		echo "<td></td>";
	}

	echo "</tr>";
						
	echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Negatiiviset yhteens‰")."</td>";
	echo "<td align = 'right' nowrap>";
	printf("%.2f", $summamiinus);
	echo "</td><td>*</td>";

	if (strlen(trim($tili)) > 0) {
		echo "<td></td>";
	}

	echo "</tr>";

	echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Positiiviset yhteens‰")."</td>";
	echo "<td align = 'right' nowrap>";
	printf("%.2f", $summaplus);
	echo "</td><td>*</td>";

	if (strlen(trim($tili)) > 0) {
		echo "<td></td>";
	}

	echo "</tr>";

	$summa = $summaplus + $summamiinus;
	echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Yhteens‰")."</td>";
	echo "<td align = 'right' nowrap>";
	printf("%.2f", $summa);
	echo "</td><td>*</td>";

	if (strlen(trim($tili)) > 0) {
		echo "<td></td>";
	}

	echo "</tr>";

	echo "</table><br>";
?>
