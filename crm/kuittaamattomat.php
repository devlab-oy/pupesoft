<?php
	require ("../inc/parametrit.inc");

	if($tee == 'B') {   //Pitää lisätä uusi
		$query = "	SELECT kalenteri.asiakas asiakas, yhteyshenkilo.tunnus yhenkilo, kentta01, tapa
					FROM kalenteri
					LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus
					WHERE kalenteri.tunnus='$kaletunnus' and kalenteri.yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$prow = mysql_fetch_array ($result);

		$viesti = $prow["kentta01"] . " -- kuittasi --> " . $viesti;
		        
		$kysely = "	INSERT INTO kalenteri
					SET asiakas  = '$prow[asiakas]',
					henkilo  = '$prow[yhenkilo]',
					kuka     = '$kukarow[kuka]',
					yhtio    = '$kukarow[yhtio]',
					tyyppi   = 'Kuittaus',
					tapa     = '$prow[tapa]',
					kentta01 = '$viesti',
					kuittaus = '',
					pvmalku  = now()";
		$result = mysql_query($kysely) or pupe_error($kysely);
			
		$query = "	UPDATE kalenteri
					SET kuittaus=''
					WHERE tunnus='$kaletunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		$tee = "";
	}
	
	echo "<font class='head'>".t("Muistutukset")."</font><hr>";
	
	if($tee == 'A') {		
		$query = "	SELECT kalenteri.pvmalku, asiakas.nimi nimi, yhteyshenkilo.nimi nimi, kalenteri.kentta01 viesti, kalenteri.tapa tapa					
					FROM kalenteri					
					LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio					
					LEFT JOIN asiakas ON kalenteri.asiakas=asiakas.ytunnus and asiakas.yhtio=kalenteri.yhtio					
					WHERE kalenteri.tunnus='$kaletunnus' and kalenteri.yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";		
		echo "<tr>";		
		for ($i = 0; $i < mysql_num_fields($result); $i++) {			
			echo "<th>".t(mysql_field_name($result,$i))."</th>";
		}
		
		echo "</tr>";		
		$prow = mysql_fetch_array ($result);		
		echo "<tr>";		
		
		for ($i=0; $i<mysql_num_fields($result); $i++) {			
			echo "<td>$prow[$i]</td>";
		}	
		
		echo "</tr>";		
		echo "</table>";
		echo "<table>
				<form action='$PHP_SELF?tee=B&kuka=$kuka&kaletunnus=$kaletunnus' method='POST'>
				<tr><th align='left'>$KUITTAUS_TXT</td></th></tr>
				<tr><td>
				<textarea cols='83' rows='4' name='viesti' wrap='hard'>$muuta</textarea>
				</td></tr>
				<tr><td class='back'><input type='submit' value='".t("Kuittaa")."'></td></tr>
				</table></form>";
	}
	
	if($tee == "LISAAMUISTUTUS") {
		$ok = '';
	
		if ($ytunnus != '') {
			if (!isset($muutparametrit)) {
				$muutparametrit	= $valitut."#".$alku."#".$year."#".$kuu."#".$paiva."#".$tunnus ."#".$tapa."#".$viesti."#".$loppu."#".$ykuka;
			}		
			
			require ("../inc/asiakashaku.inc");
			
			if ($ytunnus == '') {
				exit;
			}
		}
		
		if ($ytunnus != '') {
			
			$muut = explode('#',$muutparametrit);
			
			$valitut = $muut[0];
			$alku 	 = $muut[1];
			$year 	 = $muut[2];
			$kuu 	 = $muut[3];
			$paiva 	 = $muut[4];
			$tunnus  = $muut[5];
			$tapa 	 = $muut[6];
			$viesti  = $muut[7];
			$loppu	 = $muut[8];
			$ykuka   = $muut[9];
			
			$query = "	SELECT yhtio
						FROM yhteyshenkilo
						WHERE yhtio = '$kukarow[yhtio]' 
						and liitostunnus = '$ytunnus'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0 or $yhtunnus != '') {
				$ok = "OK";
			}
			elseif ($yhtunnus == '') {				
				echo "<br><br><font class='message'>".t("Valitse yhteyshenkilö").":</font><br><br>";
				$tee = "MUISTUTUS";
				$yhteytta = 1;	
			}
		}
		elseif (isset($muutparametrit)) {
			$tee 	 = "MUISTUTUS";
			$muut 	 = explode('#',$muutparametrit);
			
			$valitut = $muut[0];
			$kello 	 = $muut[1];
			$year 	 = $muut[2];
			$kuu 	 = $muut[3];
			$paiva 	 = $muut[4];
			$tunnus  = $muut[5];
			$tapa 	 = $muut[6];
			$viesti  = $muut[7];
			$loppu	 = $muut[8];
			$ykuka   = $muut[9];
			
		}
		else {
			//syötetään siis ilman asiakasta
			$ok = "OK";
		}
		
		if ($ok == "OK") {
			if ($kuittaus == '') {
				$kuittaus = 'K';
			}
	
			$kysely = "	INSERT INTO kalenteri
						SET asiakas  = '$ytunnus',
						liitostunnus = '$asiakasid',
						henkilo  = '$yhtunnus',
						kuka     = '$ykuka',
						yhtio    = '$kukarow[yhtio]',
						tyyppi   = 'Muistutus',
						tapa     = '$tapa',
						kentta01 = '$viesti',
						kuittaus = '$kuittaus',
						pvmalku  = '$year-$kuu-$paiva'";
			$result = mysql_query($kysely) or pupe_error($kysely);
			
			echo t("Lisätty muistutus päivälle:")."  <b>$year-$kuu-$paiva</b><br><br>";
			
			if ($from != '') {
				
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$from?ytunnus=$ytunnus&yhtunnus=$yhtunnus'>";
				exit;
			}
			
			$ytunnus 	= '';
			$yhtunnus	= '';
			$ykuka		= '';
			$tapa		= '';
			$viesti		= '';
			$kuittaus	= '';			
			$tee 		= '';
		}		
	}
	
	
	if($tee == "MUISTUTUS") {
		
		$MONTH_ARRAY = array(1=>t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));
		$DAY_ARRAY   = array(1=>t('Maanantai'),t('Tiistai'),t('Keskiviikko'),t('Torstai'),t('Perjantai'),t('Lauantai'),t('Sunnuntai'));

	
		echo "<table>";
		echo "	<form action='$PHP_SELF' method='POST'>
				<input type='hidden' name='tee' value='LISAAMUISTUTUS'>
				<input type='hidden' name='from' value='$from'>";

		$kuk = date("n");
		$pva = date("d");

		echo "	<tr>
				<th colspan='4' align='left'>".t("Uusi muistutus:")."</th>
				</tr>
				<tr>
				<th>".t("Päivämäärä: ")."</th><td colspan='3'>";
		$i=1;
		echo "<select name='kuu'>";

		foreach($MONTH_ARRAY as $val) {
			if(sprintf('%02d',$i) == sprintf('%02d',$kuk) and $kuu == '') {
				$sel = "selected";
			}
			elseif(sprintf('%02d',$i) == sprintf('%02d',$kuu)) {
				$sel = "selected";
			}
			else {
				$sel = "";
			}
			echo "<option value='$i' $sel>$val</option>";
			$i++;
		}
		echo "	</select>";
		echo "<select name='paiva'>";
		
		for($i=1; $i<=31; $i++) {
			if(sprintf('%02d',$i) == sprintf('%02d',$pva) and $paiva == '') {
				$sel = "selected";
			}
			elseif(sprintf('%02d',$i) == sprintf('%02d',$paiva)) {
				$sel = "selected";
			}
			else {
				$sel = "";
			}
			
			echo "<option value='$i' $sel>$i.";
		}
		
		echo "</select>";

		if (!isset($year))	$year = date('Y');

		echo "<input size=6' type='text' name='year' value='$year'>";
		echo "	</td>
				</tr>
				<tr>
				<th>".t("Yhteydenottaja: ")."</th><td colspan='3'><select name='ykuka'>
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
			if ($row["kuka"] == $ykuka) {
				$sel = "SELECTED";
			}
			else {
				$sel = "";
			}
			
			echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
		}

		echo "</select></td></th>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'KALETAPA'
					ORDER BY selite";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th>".t("Tapa:")."</th><td><select name='tapa'>";

		while ($vrow=mysql_fetch_row($vresult)) {
			$sel="";
			if ($ktapa == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[0] $vrow[1]";
		}
		echo "</select></td>";

		echo "</tr>";
		
		if ($yhteytta == 1 or isset($yhtunnus)) {
			echo "<tr><th>".t("Asiakas").":</th><td><input type='text' name='ytunnus' value='$ytunnus'></td></tr>";
			
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE liitostunnus='$ytunnus'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			
			echo "<tr><th>".t("Yhteyshenkilö")."</th>";
			
			echo "<td><select name='yhtunnus'>
					<option value='0'>".t("Ei valintaa")."</option>";

			while ($row = mysql_fetch_array($result)) {				
				echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			
			echo "</select></td></tr>";
			
		}
		else {
			echo "<tr><th>Asiakas:</th><td><input type='text' name='ytunnus' value='$ytunnus'></td></tr>";
		}
		
		
		echo "<tr>		
				<th>".t("Viesti:")." </th><td colspan='3'>
				<textarea name='viesti' cols='70' rows='2' wrap='hard'>$viesti</textarea>
				</td>
				</tr>
				<tr>
				<th>
				".t("Ei kuittausta:")." </th><td colspan='3'><input type='checkbox' name='kuittaus'>
				<input type='submit' value='".t("Lisää")."'>
				</td>
				</tr>
				</form>";
		echo "</table>";
	}
	
	
	if ($tee == "") {
		echo "<a href='$PHP_SELF?tee=MUISTUTUS&ytunnus=$ytunnus&yhtunnus=$yhtunnus'>".t("Lisää muistutus")."</a><br><br>";
		
		echo "<table>";
		echo "<tr>";
		echo "<td>Näytä henkilön </td>";
		
		
		echo "	<form action='$PHP_SELF' method='POST'>
				
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
		
		if ($kuka == '') {
			$kuka = $kukarow["kuka"];
		}
	
	
		echo "</tr></table><br>"; 
	
	
	
		//* listataan muistutukset *///
		$query = "	SELECT kalenteri.tunnus, left(pvmalku,10) Päivämäärä, yhteyshenkilo.nimi Yhteyshenkilö, kalenteri.kentta01 Viesti, kalenteri.tapa Tapa, 
					if(kalenteri.asiakas=0,'',kalenteri.asiakas) ytunnus, kalenteri.henkilo yhtunnus, kalenteri.liitostunnus
					FROM kalenteri
					LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio
					where kalenteri.kuka = '$kuka'
					and kalenteri.tyyppi = 'Muistutus'
					and kuittaus		 = 'K' 
					and kalenteri.yhtio  = '$kukarow[yhtio]' 
					ORDER BY kalenteri.pvmalku desc";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) > 0) {
			echo "<table>";		
			echo "<tr>";		
			
			for ($i = 1; $i < mysql_num_fields($result)-2; $i++) {			
				echo "<th>".t(mysql_field_name($result,$i))."</th>";
			}		
			echo "<th>".t("Asiakas")."</th>";
			echo "</tr>";		
			
			while ($prow = mysql_fetch_array ($result)) {			
				
				unset($asrow);
				
				if ($prow["ytunnus"] != '') {
					$query = "	SELECT nimi
								FROM asiakas
								WHERE yhtio  = '$kukarow[yhtio]'
								and ytunnus='$prow[ytunnus]'";
					$asresult = mysql_query($query) or pupe_error($query);
					$asrow = mysql_fetch_array($asresult);
				}
				
				echo "<form action='$PHP_SELF?&tee=A&kaletunnus=$prow[tunnus]' method='post'><tr>";	
				
				for ($i=1; $i < mysql_num_fields($result)-2; $i++) {
					echo "<td>&nbsp;$prow[$i]&nbsp;</td>";
				}
				
				echo "<td><a href='asiakasmemo.php?ytunnus=$prow[ytunnus]&asiakasid=$prow[liitostunnus]&yhtunnus=$prow[yhtunnus]'>$prow[ytunnus] $asrow[nimi]</a></td>";
				
				echo "<td><input type='submit' value='".t("Kuittaa")."'></td>";			
				echo "</tr></form>";		
			}		
			
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään kuitattavaa muistutusta ei löydy")."!</font>";
		}
	}


require ("../inc/footer.inc");
?>