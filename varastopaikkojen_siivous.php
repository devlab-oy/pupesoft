<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Varastopaikkojen seuranta")."</font><hr>";

	if ($tee == 'CLEAN') {

		echo t("Poistetaan").": ".count($valittu)." ".t("varastopaikkaa!")."!<br>";

		if (count($valittu) != 0) {
			foreach ($valittu as $rastit) {
				//Otetaan tuotenumero talteen
				$query = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tunnus = '$rastit'";
				$presult = mysql_query($query) or die($query);
				$tuoterow = mysql_fetch_array($presult);

				//Poistetaan nollapaikka
				$query = "	DELETE FROM tuotepaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$rastit'
							and saldo=0";
				$result = mysql_query($query) or pupe_error($query);

				//Tehdään tapahtuma
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tuoterow[tuoteno]',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'poistettupaikka',
							selite 		= '".t("Poistettiin tuotepaikka")." $tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$result = mysql_query($query) or pupe_error($query);

				//Katsotaan onko oletuspaikka ok
				$query = "select sum(1) kaikkipaikat, sum(if(oletus!='',1,0)) oletuspaikat from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]'";
				$presult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($presult) > 0) {
					$prow = mysql_fetch_array($presult);

					if ($prow["kaikkipaikat"] > 0 and $prow["oletuspaikat"] == 0) {
						$query = "update tuotepaikat set oletus='X' where yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]' ORDER BY hyllyalue LIMIT 1";
						$bresult = mysql_query($query) or pupe_error($query);
					}
				}
			}

			echo t("Valitut tuotepaikat poistettu")."!<br><br>";
		}
		else {
			echo t("Et valinnut yhtään paikkaa poistettavaksi")."!<br><br>";
		}

		$tee = "";
	}
	
	if ($tee == 'CLEANRIKKINAISET') {
		
		$query = "	SELECT tuoteno, concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, min(tunnus) mintunnus, group_concat(tunnus order by tunnus) tunnukset, count(*) lukumaara, sum(saldo) saldo
					FROM tuotepaikat 
					WHERE yhtio='$kukarow[yhtio]' 
					GROUP BY tuoteno, paikka
					HAVING lukumaara > 1
					ORDER BY tuoteno";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {
			
			echo t("Korjataan tuotepaikkoja").":<br>";
			
			while ($lrow = mysql_fetch_array($result)) {
				
				echo "Korjataan tuotepaikka: $lrow[tuoteno] $lrow[paikka]<br>";
			
				//Otetaan tuotenumero talteen
				$query = "	UPDATE tuotepaikat
				 			SET saldo='$lrow[saldo]'
							WHERE yhtio='$kukarow[yhtio]' 
							and tunnus = '$lrow[mintunnus]'";
				$presult = mysql_query($query) or pupe_error($query);				
				
				//Poistetaan nollapaikka
				$query = "	DELETE FROM tuotepaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus in ($lrow[tunnukset])
							and tunnus != '$lrow[mintunnus]'";
				$presult = mysql_query($query) or pupe_error($query);
				
				//Tehdään tapahtuma
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$lrow[tuoteno]',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'poistettupaikka',
							selite 		= '".t("Poistettiin tuotepaikka")." $lrow[paikka]',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$presult = mysql_query($query) or pupe_error($query);
			}
		}

		$tee = "";
	}
	
	if ($tee == 'CLEANOLETUKSET') {

		$query = "	SELECT tuoteno, sum(if(oletus!='', 1, 0)) oletukset, min(tunnus) mintunnus, group_concat(tunnus order by tunnus) tunnukset
					FROM tuotepaikat 
					WHERE yhtio = '$kukarow[yhtio]' 
					GROUP BY tuoteno
					HAVING oletukset != 1
					ORDER BY tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			
			echo t("Korjataan oletuspaikkoja").":<br>";
			
			while ($lrow = mysql_fetch_array($result)) {
				
				echo "Korjataan oletuspaikka: $lrow[tuoteno]<br>";
			
				if ($lrow["oletukset"] == 0) {
					$query = "	UPDATE tuotepaikat
					 			SET oletus  = 'X'
								WHERE yhtio = '$kukarow[yhtio]' 
								and tunnus  = '$lrow[mintunnus]'";
					$presult = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE tuotepaikat
					 			SET oletus  = ''
								WHERE yhtio = '$kukarow[yhtio]' 
								and tunnus in ($lrow[tunnukset])";
					$presult = mysql_query($query) or pupe_error($query);

					$query = "	UPDATE tuotepaikat
					 			SET oletus  = 'X'
								WHERE yhtio = '$kukarow[yhtio]' 
								and tunnus  = '$lrow[mintunnus]'";
					$presult = mysql_query($query) or pupe_error($query);
				}
			}
		}

		$tee = "";
	}
	
	if ($tee == 'LISTAAOLETUKSET') {
		
		$query = "	SELECT tuoteno, sum(if(oletus!='', 1, 0)) oletukset 
					FROM tuotepaikat 
					WHERE yhtio='$kukarow[yhtio]' 
					GROUP BY tuoteno
					HAVING oletukset != 1
					ORDER BY tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th>";
			echo "<th>".t("Oletuspaikkojen määrä")."</th></tr>";

			echo "<form method='POST' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='CLEANOLETUKSET'>";

			$saldolliset = array();

			while ($lrow = mysql_fetch_array($result)) {
				echo "<td><a href='tuote.php?tee=Z&tuoteno=$lrow[tuoteno]'>$lrow[tuoteno]</a></td><td>$lrow[oletukset]</td></tr>";
			}

			echo "<tr><tdclass='back'><br><br></td></tr>";

			echo "</table><br><br>";
			echo "<input type='submit' value='".t("Korjaa oletuspaikat")."'></form>";
			echo "</table><br><br>";
		}
		else {
			echo t("Yhtään tuotetta ei löytynyt")."!<br><br>";
			$tee = "";
		}
	}
		
	if ($tee == 'LISTAARIKKINAISET') {
		
		$query = "	SELECT tuoteno, concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, min(tunnus) mintunnus, group_concat(tunnus order by tunnus) tunnukset, count(*) lukumaara, sum(saldo) saldo
					FROM tuotepaikat 
					WHERE yhtio='$kukarow[yhtio]' 
					GROUP BY tuoteno, paikka
					HAVING lukumaara > 1
					ORDER BY tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Saldo")."</th>";
			echo "<th>".t("Varastopaikka")."</th>";
			echo "<th>".t("Duplikaattien määrä")."</th></tr>";

			echo "<form method='POST' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='CLEANRIKKINAISET'>";

			$saldolliset = array();

			while ($lrow = mysql_fetch_array($result)) {
				echo "<td><a href='tuote.php?tee=Z&tuoteno=$lrow[tuoteno]'>$lrow[tuoteno]</a></td><td>".asana('nimitys_',$lrow['tuoteno'],$lrow['nimitys'])."</td><td>$lrow[saldo]</td><td>$lrow[paikka]</td><td>$lrow[lukumaara]</td></tr>";
			}

			echo "<tr><td colspan='7' class='back'><br><br></td></tr>";

			echo "</table><br><br>";
			echo "<input type='submit' value='".t("Korjaa tuotepaikat")."'></form>";
			echo "</table><br><br>";
		}
		else {
			echo t("Yhtään tuotetta ei löytynyt")."!<br><br>";
			$tee = "";
		}
	}

	if ($tee == 'LISTAA') {
		$lisaa  = "";

		if ($osasto != '') {
			$lisaa .= " and tuote.osasto = '$osasto' ";
		}
		if ($tuoryh != '') {
			$lisaa .= " and tuote.try = '$tuoryh' ";
		}


		if ($varasto == 'EI') {
			$lisaa .= " and varastopaikat.tunnus is null ";
		}
		elseif ($varasto != '') {
			$lisaa .= " and varastopaikat.tunnus = '$varasto' ";
		}

		if ($vva != '' and $kka != '' and $ppa != '') {
			$lisaa .= "and tuotepaikat.saldoaika <= '$vva-$kka-$ppa'";
		}

		$lisaa2 = "";

		if ($miinus != '' and $plus == '') {
			$lisaa2 = "<=";
		}
		if ($plus != '' and $miinus == '') {
			$lisaa2 = ">=";
		}
		if ($plus !='' and $miinus != '') {
			$lisaa2 = "<>";
		}
		if ($plus =='' and $miinus == '') {
			$lisaa2 = "=";
		}
		if ($vainmiinus != '') {
			$lisaa2 = "<";
		}


		if ($ahyllyalue != '') {
			$apaikka = strtoupper(sprintf("%-05s",$ahyllyalue)).strtoupper(sprintf("%05s",$ahyllynro)).strtoupper(sprintf("%05s",$ahyllyvali)).strtoupper(sprintf("%05s",$ahyllytaso));
			$lisaa .= " and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >= '$apaikka' ";
		}

		if ($lhyllyalue != '') {
			$lpaikka = strtoupper(sprintf("%-05s",$lhyllyalue)).strtoupper(sprintf("%05s",$lhyllynro)).strtoupper(sprintf("%05s",$lhyllyvali)).strtoupper(sprintf("%05s",$lhyllytaso));
			$lisaa .= " and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <= '$lpaikka' ";
		}

		$query = "	SELECT tuotepaikat.*, tuote.nimitys, concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, varastopaikat.nimitys varasto, tuotepaikat.tunnus paikkatun,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
					FROM tuotepaikat
					LEFT JOIN tuote ON tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno
					LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					WHERE
					tuotepaikat.yhtio='$kukarow[yhtio]'
					and tuotepaikat.saldo $lisaa2 0
					$lisaa
					ORDER BY sorttauskentta, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>".t("Del")."</th><th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Saldo")."</th>";
			echo "<th>".t("Saldoaika")."</th>";
			echo "<th>".t("Varastopaikka")."</th>";
			echo "<th>".t("Varasto")."</th></tr>";


			echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function toggleAll(toggleBox) {

					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox') {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}

				//-->
				</script>";


			echo "<form method='POST' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='CLEAN'>";

			$saldolliset = array();

			while ($lrow = mysql_fetch_array($result)) {
				echo "<tr>";

				if ($lrow["saldo"] == 0.00) {
					echo "<td><input type='checkbox' value='$lrow[paikkatun]' name='valittu[]'></td>";
				}
				else {
					$saldolliset[] = $lrow["paikkatun"];
					echo "<td></td>";
				}

				echo "<td><a href='tuote.php?tee=Z&tuoteno=$lrow[tuoteno]'>$lrow[tuoteno]</a></td><td>".asana('nimitys_',$lrow['tuoteno'],$lrow['nimitys'])."</td><td>$lrow[saldo]</td><td>".substr($lrow["saldoaika"],0,10)."</td><td>$lrow[paikka]</td><td>$lrow[varasto]</td></tr>";
			}

			echo "<tr><td colspan='7' class='back'><br><br></td></tr>";

			echo "<tr><td><input type='checkbox' name='chbox' onclick='toggleAll(this)'></td><td colspan='6'>Ruksaa kaikki</td></tr>";

			echo "</table><br><br>";
			echo "<input type='submit' value='".t("Mitätöi valitut tuotepaikat")."'></form>";
			echo "</table><br><br>";

			echo "<form method='POST' action='inventointi_listat.php'>";
			echo "<input type='hidden' name='tee' value='TULOSTA'>";

			$saldot = "";
			foreach($saldolliset as $saldo) {
				$saldot .= "$saldo,";
			}
			$saldot = substr($saldot,0,-1);

			echo "<input type='hidden' name='saldot' value='$saldot'>";
			echo "<input type='hidden' name='tila' value='SIIVOUS'>";
			echo "<input type='submit' value='".t("Luo saldollisista inventointilista")."'></form>";
		}
		else {
			echo t("Yhtään tuotetta ei löytynyt")."!<br><br>";
			$tee = "";
		}
	}

	if ($tee == "") {
		//Käyttöliittymä

		echo "<table><form name='piiri' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='LISTAA'>";

		echo "<tr><th>".t("Alkuvarastopaikka:")."</th>
				<td><input type='text' size='6' name='ahyllyalue'>
				<input type='text' size='6' name='ahyllynro'>
				<input type='text' size='6' name='ahyllyvali'>
				<input type='text' size='6' name='ahyllytaso'>
				</td></tr>";

		echo "<tr><th>".t("Loppuvarastopaikka:")."</th>
				<td><input type='text' size='6' name='lhyllyalue'>
				<input type='text' size='6' name='lhyllynro'>
				<input type='text' size='6' name='lhyllyvali'>
				<input type='text' size='6' name='lhyllytaso'>
				</td></tr>";

		echo "<tr><th>".t("Näytä vain paikat joiden saldo on muuttunut ennen päivämäärää (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'>-<input type='text' name='kka' value='$kka' size='3'>-<input type='text' name='vva' value='$vva' size='6'></td>
				</tr>";

		echo "<tr><th>".t("Valitse varasto").":</th>";
		echo "<td><select name='varasto'>
			<option value=''>".t("Näytä kaikki")."</option>
			<option value='EI'>".t("Näytä paikat jotka ei kuulu mihinkään varastoon")."</option>";

		$query  = "	SELECT *
					FROM varastopaikat
					WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares)) {


			$sel='';
			if ($varow['tunnus']==$varasto) $sel = 'selected';

			echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Näytä vain miinus-saldolliset")."</th><td><input type='checkbox' name='vainmiinus'></td>";
		echo "<tr><th>".t("Näytä myös miinus-saldolliset")."</th><td><input type='checkbox' name='miinus'></td>";
		echo "<tr><th>".t("Näytä myös plus-saldolliset")."</th><td><input type='checkbox' name='plus'></td>";

		echo "<tr><th>".t("Osasto")."</th><td>";

		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','OSASTO_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
					ORDER BY avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='osasto'>";
		echo "<option value=''>".t("Näytä kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr>
				<tr><th>".t("Tuoteryhmä")."</th><td>";

		//Tehdään osasto & tuoteryhmä pop-upit
		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','TRY_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
					ORDER BY avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='tuoryh'>";
		echo "<option value=''>".t("Näytä kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuoryh == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select></td><td class='back'><input type='submit' value='".t("Hae")."'></td></tr>";
		echo "</form>";
		

		echo "<tr><td class='back'><br></tr>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='LISTAARIKKINAISET'>";
		echo "<tr><th>".t("Listaa tuotteet joilla on virheellisiä varastopaikkoja")."</th><td></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr>";
		echo "</form>";
		
		echo "<tr><td class='back'><br></tr>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='LISTAAOLETUKSET'>";
		echo "<tr><th>".t("Listaa tuotteet joilla on virheellisiä oletuspaikkoja")."</th><td></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr>";
		echo "</form>";
		
		echo "</table>";
	}

	require ("inc/footer.inc");

?>