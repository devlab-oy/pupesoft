<?php
	
	if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}
	
	echo "<font class='head'>".t("Muistutukset")."</font><hr>";

	if($tee == 'B') {
		if ($muuta != "") {
			$query = "	SELECT yhteyshenkilo.tunnus yhenkilo, kalenteri.*
						FROM kalenteri
						LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus
						WHERE kalenteri.tunnus	= '$kaletunnus' 
						and kalenteri.yhtio		= '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_array ($result);

			$viesti = $prow["kentta01"] . " -- $kukarow[nimi] ".t("kuittasi")." -- " . $muuta;
		        
			$kysely = "	INSERT INTO kalenteri
						SET asiakas  	= '$prow[asiakas]',
						liitostunnus 	= '$prow[liitostunnus]',
						henkilo  		= '$prow[yhenkilo]',
						kuka     		= '$kukarow[kuka]',
						yhtio    		= '$kukarow[yhtio]',
						tyyppi   		= 'Kuittaus',
						tapa     		= '$prow[tapa]',
						kentta01 		= '$viesti',
						kuittaus 		= '',
						pvmalku  		= now()";
			$result = mysql_query($kysely) or pupe_error($kysely);
			
			$query = "	UPDATE kalenteri
						SET kuittaus = ''
						WHERE tunnus	= '$kaletunnus' 
						and yhtio		= '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
	
			$tee = "";
		}
		else {
			$tee = "A";	
		}
	}
	
	if($tee == 'A') {		
		$query = "	SELECT yhteyshenkilo.nimi yhteyshenkilo, kalenteri.*		
					FROM kalenteri					
					LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio					
					WHERE kalenteri.tunnus	= '$kaletunnus'
					and kalenteri.yhtio		= '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";		
		
		echo "<tr>";		
		echo "<th>".t("Päivämäärä")."</th>";
		echo "<th>".t("Viesti")."</th>";
		echo "<th>".t("Tapa")."</th>";		
		echo "<th>".t("Asiakas")."</th>";
		echo "<th>".t("Yhteyshenkilö")."</th>";
		echo "</tr>";		
		
		$prow = mysql_fetch_array ($result);		
				
		if ($prow["liitostunnus"] > 0) {
			$query = "	SELECT nimi
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus	= '$prow[liitostunnus]'";
			$asresult = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($asresult);
			
			$aslisa = "<a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$prow[asiakas]&asiakasid=$prow[liitostunnus]'>$asrow[nimi]</a>";					
		}
		else {
			$aslisa = "";
		}
		
		echo "<tr>";	
		echo "<td>".tv1dateconv($prow["pvmalku"])."</td>";
		echo "<td>$prow[kentta01]</td>";
		echo "<td>$prow[tapa]</td>";
		echo "<td>$prow[asiakas] $aslisa</td>";
		echo "<td>$prow[yhteyshenkilo]</td>";				
		echo "</tr>";	
		
		echo "</table><br><br>";
		echo "<table>
				<form action='".$palvelin2."crm/kuittaamattomat.php?tee=B&kuka=$kuka&kaletunnus=$kaletunnus' method='POST'>
				<tr><th align='left'>".t("Kuittaus").":</td></th></tr>
				<tr><td>
				<textarea cols='83' rows='4' name='muuta' wrap='hard'>$muuta</textarea>
				</td></tr>
				<tr><td class='back'><input type='submit' value='".t("Kuittaa")."'></td></tr>
				</table></form>";
	}
	
	if($tee == "LISAAMUISTUTUS") {
		if ($viesti != "") {
			if ($kuittaus == '') {
				$kuittaus = 'K';
			}

			$kysely = "	INSERT INTO kalenteri
						SET kuka = '$kuka',
						yhtio    = '$kukarow[yhtio]',
						tyyppi   = 'Muistutus',
						tapa     = '$tapa',
						kentta01 = '$viesti',
						kuittaus = '$kuittaus',
						pvmalku  = '$mvva-$mkka-$mppa'";
			$result = mysql_query($kysely) or pupe_error($kysely);
		
			echo t("Lisätty muistutus päivälle:")."  <b>$year-$kuu-$paiva</b><br><br>";
			
			$kuka		= '';
			$tapa		= '';
			$viesti		= '';
			$kuittaus	= '';
			$tee 		= '';	
		}
		else {
			$tee 		= 'MUISTUTUS';	
		}	
	}
	
	
	if($tee == "MUISTUTUS") {
		echo "<table>";
		echo "	<form action='".$palvelin2."crm/kuittaamattomat.php' method='POST'>
				<input type='hidden' name='tee' value='LISAAMUISTUTUS'>
				<input type='hidden' name='from' value='$from'>";

		echo "<table width='620'>";
		
		echo "<tr><th colspan='3'>".t("Lisää muistutus")."</th>";
		echo "<tr><td colspan='3'><textarea cols='83' rows='3' name='viesti' wrap='hard'>$viesti</textarea></td></tr>";
		
			echo "	<tr>
				<th>".t("Yhteydenottaja: ")."</th>
				<td colspan='2'><select name='kuka'>
				<option value='$kukarow[kuka]'>".t("Itse")."</option>";

		$query = "	SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
					FROM kuka, oikeu
					WHERE kuka.yhtio	= '$kukarow[yhtio]'
					and oikeu.yhtio		= kuka.yhtio
					and oikeu.kuka		= kuka.kuka
					and oikeu.nimi		= 'crm/kalenteri.php' 
					and kuka.kuka 		<> '$kukarow[kuka]'
					ORDER BY kuka.nimi";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			if ($row["kuka"] == $kuka) {
				$sel = "SELECTED";
			}
			else {
				$sel = "";
			}

			echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
		}
		echo "</select></td></tr>";
	
		if (!isset($mkka))
			$mkka = date("m");
		if (!isset($mvva))
			$mvva = date("Y");
		if (!isset($mppa))
			$mppa = date("d");

		echo "<tr><th>".t("Muistutuspäivämäärä (pp-kk-vvvv)")."</th>
				<td colspan='2'><input type='text' name='mppa' value='$mppa' size='3'>-
				<input type='text' name='mkka' value='$mkka' size='3'>-
				<input type='text' name='mvva' value='$mvva' size='5'></td></tr>";
	
		if ($kuittaus == "E") {
			$sel = "CHECKED";
		}
		else {
			$sel = "";
		}
	
		echo"	<tr>
				<th>".t("Ei kuittausta:")." </th><td colspan='2'><input type='checkbox' name='kuittaus' value='E' $sel>
				</td>
				</tr>";
				
		echo "<tr><th>".t("Tapa:")."</th>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'KALETAPA'
					ORDER BY jarjestys, selite";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td colspan='2'><select name='tapa'>";

		while ($vrow=mysql_fetch_row($vresult)) {
			$sel="";

			if ($tapa == $vrow[1]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[1]' $sel>$vrow[1]";
		}

		echo "</select></td></tr>";

		echo "	<tr>
				<td colspan='3' align='right' class='back'>
				<input type='submit' value='".t("Tallenna")."'>
				</form>
				</td></tr>";
		echo "</table>";
	}
	
	
	if ($tee == "") {
		
		if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
			echo "<a href='".$palvelin2."crm/kuittaamattomat.php?tee=MUISTUTUS&ytunnus=$ytunnus&yhtunnus=$yhtunnus'>".t("Lisää muistutus")."</a><br><br>";
		}
	
		echo "<table>";
		
		
		if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
			echo "<tr>";
			echo "<td>Näytä henkilön </td>";
		
			echo "	<form action='".$palvelin2."crm/kuittaamattomat.php' method='POST'>
				
					<td><select name='kuka' onchange='submit()'>
					<option value='$kukarow[kuka]'>$kukarow[nimi]</option>";
		
			$query = "	SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
						FROM kuka, oikeu
						WHERE kuka.yhtio	= '$kukarow[yhtio]'
						and oikeu.yhtio		= kuka.yhtio
						and oikeu.kuka		= kuka.kuka
						and oikeu.nimi		= 'crm/kalenteri.php' 
						and kuka.tunnus <> '$kukarow[tunnus]'
						ORDER BY kuka.nimi";	
			$result = mysql_query($query) or pupe_error($query);
		
			while ($row = mysql_fetch_array($result)) {		
				$sel = '';		
			
				if($row["kuka"] == $kuka) {			
					$sel = 'SELECTED';
				}		
			
				echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
			}
		
			echo "</select></td><td> ".t("kuitattavat muistutukset").".</td></form>";
		
			echo "</tr></table><br>"; 
		}
	
		if ($kuka == '') {
			$kuka = $kukarow["kuka"];
		}
	
		
	
		//* listataan muistutukset *///
		$query = "	SELECT yhteyshenkilo.nimi yhteyshenkilo, kalenteri.*
					FROM kalenteri
					LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio
					where kalenteri.kuka = '$kuka'
					and kalenteri.tyyppi = 'Muistutus'
					and kuittaus		 = 'K' 
					and kalenteri.yhtio  = '$kukarow[yhtio]' 
					and left(kalenteri.tyyppi,7) != 'DELETED'
					ORDER BY kalenteri.pvmalku desc";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) > 0) {
			echo "<table>";		
			
			echo "<tr>";		
			echo "<th>".t("Päivämäärä")."</th>";
			echo "<th>".t("Viesti")."</th>";
			echo "<th>".t("Tapa")."</th>";		
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Yhteyshenkilö")."</th>";
			echo "</tr>";		
			
			while ($prow = mysql_fetch_array ($result)) {			
				
				unset($asrow);
				
				if ($prow["liitostunnus"] > 0) {
					$query = "	SELECT nimi
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus	= '$prow[liitostunnus]'";
					$asresult = mysql_query($query) or pupe_error($query);
					$asrow = mysql_fetch_array($asresult);
					
					$aslisa = "<a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$prow[asiakas]&asiakasid=$prow[liitostunnus]'>$asrow[nimi]</a>";				
				}
				else {
					$aslisa = "";
				}
				
				echo "<form action='".$palvelin2."crm/kuittaamattomat.php?&tee=A&kaletunnus=$prow[tunnus]' method='post'><tr>";	
				echo "<td>".tv1dateconv($prow["pvmalku"])."</td>";
				echo "<td>$prow[kentta01]</td>";
				echo "<td>$prow[tapa]</td>";
				echo "<td>$prow[asiakas] $aslisa</td>";
				echo "<td>$prow[yhteyshenkilo]</td>";				
				echo "<td><input type='submit' value='".t("Kuittaa")."'></td>";			
				echo "</tr></form>";		
			}		
			
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään kuitattavaa muistutusta ei löydy")."!</font>";
		}
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
		require ("../inc/footer.inc");
	}
?>