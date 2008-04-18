<?php

	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>".t("Myöhässä olevat myyntitilaukset")."</font><hr>";
	
	if ($tee == 'NAYTATILAUS') {
			echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
			require ("naytatilaus.inc");
			echo "<br><br><br>";
			$tee = "HAE";
	}
	
		
	if ($tee == "HAE") {
		
		if ($suunta == '' or $suunta == "DESC") {
			$suunta = "ASC";
		}
		else {
			$suunta = "DESC";
		}
		
		
		echo "<table><tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Asiakas")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "<th>".t("Tilaus")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimike")."</th>";
		echo "<th>".t("Kpl")."</th>";
		echo "<th>".t("Yksikkö")."</th>";
		echo "<th>".t("Arvo")."</th>";
		echo "<th>".t("Myytävissä")."</th>";
		echo "<th><a href='?tee=HAE&haku=toimaika&suunta=$suunta&tunnus=$tunnus&myovv=$myovv&myokk=$myokk&myopp=$myopp&tuoteryhma=$tuoteryhma&kustannuspaikka=$kustannuspaikka'>".t("Toimitusaika")."</a></th>";
		echo "<th>".t("Tila")."</th>";
		echo "</tr>";
		
		if (($myovv == "" or !is_numeric($myovv)) or ($myokk == "" or !is_numeric($myokk)) or ($myopp == "" or !is_numeric($myopp))) {
			$myovv = "0000";
			$myokk = "00";
			$myopp = "00";
		}		
		
		$tryrajaus = "";		
		if ($tuoteryhma != '') {
			$tryrajaus = " and tuote.try = '$tuoteryhma' ";
		}
		
		$kusrajaus = "";
		if ($kustannuspaikka != '') {
			$kusrajaus = " and (asiakas.kustannuspaikka = '$kustannuspaikka' or tuote.kustp = '$kustannuspaikka') ";
		}		
		
		
		$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.postitp, lasku.tunnus, tilausrivi.tuoteno, tilausrivi.nimitys, sum(tilausrivi.tilkpl) myydyt, tilausrivi.yksikko,
					sum(tilausrivi.tilkpl * tilausrivi.hinta) arvo, lasku.toimaika, lasku.tila, lasku.alatila
					FROM lasku
					JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					JOIN tuote ON lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila IN ('L','N')
					and lasku.alatila != 'X'
					and lasku.toimaika <= '$myovv-$myokk-$myopp' 
					$tryrajaus
					$kusrajaus
					group by 1,2,3,4,5,6,8,10,11,12 
					ORDER BY lasku.toimaika $suunta";
		$result = mysql_query($query) or pupe_error($query);
		
		while ($tulrow = mysql_fetch_array($result)) {
			
			list(,, $myytavissa) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', '');
			
			if ($yhtiorow['saldo_kasittely'] != '') {
				list(,, $myytavissa_tul) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', $myovv."-".$myokk."-".$myopp);
			}
			
			
			$laskutyyppi = $tulrow["tila"];
			$alatila	 = $tulrow["alatila"];
			require ("inc/laskutyyppi.inc");
			
			echo "<tr class='aktiivi'>";
			echo "<td>$tulrow[ytunnus]</td>";
			echo "<td>$tulrow[nimi]</td>";
			echo "<td>$tulrow[postitp]</td>";
			echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&myovv=$myovv&myokk=$myokk&myopp=$myopp&tuoteryhma=$tuoteryhma&kustannuspaikka=$kustannuspaikka'>$tulrow[tunnus]</a></td>";
			echo "<td>$tulrow[tuoteno]</td>";
			echo "<td>$tulrow[nimitys]</td>";
			echo "<td>$tulrow[myydyt]</td>";
			echo "<td>$tulrow[yksikko]</td>";
			echo "<td>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $tulrow[arvo])."</td>";
			if ($yhtiorow['saldo_kasittely'] != '') {
				echo "<td>$myytavissa ($myytavissa_tul)</td>";
			}
			else {
				echo "<td>$myytavissa</td>";
			}
			echo "<td>".tv1dateconv($tulrow[toimaika])."</td>";
			
			if ($tulrow['tila'] == "L" and $tulrow['alatila'] == "D") {
				echo "<td><font class='OK'>".t($laskutyyppi)."<br>".t($alatila)."</font></td>";
			}
			else {
				echo "<td>".t($laskutyyppi)."<br>".t($alatila)."</td>";
			}
			
			echo "</tr>";
		}
		
		echo "</table>";
	}


	if ($myovv == '') {
		$myopp = date("j");
		$myokk = date("n");
		$myovv = date("Y");
	}
			
	echo "<form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value = 'HAE'>";
	echo "<table><tr>";
	echo "<th>".t("Anna toimituspäivä")."</th>";
	echo "<td><input type='text' name='myopp' value='$myopp' size='3'>";
	echo "<input type='text' name='myokk' value='$myokk' size='3'>";
	echo "<input type='text' name='myovv' value='$myovv' size='6'></td>";
	echo "</tr>";

	echo "<tr><th>".t("Valitse tuoteryhmä")."</th>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]'
				and avainsana.laji='TRY'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuoteryhma'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($tuoteryhma == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select></td></tr>";


	echo "<tr><th>".t("Valitse kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustannuspaikka'>";
	echo "<option value='' >".t("Ei valintaa")."</option>";		

	while ($vrow=mysql_fetch_array($vresult)) {
		if($kustannuspaikka == $vrow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
	}
	echo "</select></td>";

	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";
	echo "</form></table>";

	

	require ("../inc/footer.inc");

?>