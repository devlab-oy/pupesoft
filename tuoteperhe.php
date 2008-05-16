<?php
	require ("inc/parametrit.inc");

	if ($toim == "PERHE") {
		echo "<font class='head'>".t("Tuoteperheet")."</font><hr>";
		$hakutyyppi = "P";
	}
	elseif ($toim == "LISAVARUSTE") {
		echo "<font class='head'>".t("Tuotteen lisävarusteet")."</font><hr>";
		//$hakutyyppi = "'LT','LO','LR','LW'";
		$hakutyyppi = "L";
	}
	else {
		echo "<font class='head'>".t("Tuotereseptit")."</font><hr>";
		$hakutyyppi = "R";
	}
	
	if ($tee == 'TALLENNAFAKTA') {
		$query = "UPDATE tuoteperhe SET fakta = '', fakta2 = '', omasivu = '' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "UPDATE tuoteperhe SET fakta = '$fakta', fakta2 = '$fakta2', omasivu = '$omasivu'  WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' ORDER BY isatuoteno, tuoteno LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		echo "<br><br><font class='message'>".t("Reseptin tiedot tallennettu")."!</font><br>";
		
		$query = "UPDATE tuoteperhe SET ei_nayteta = '$ei_nayteta' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "<br><br><font class='message'>".t("Esitysmuoto tallennettu")."!</font><br>";
		
		$tee = '';	
	}
				
	if ($tee == "KOPIOI") {
	
		if ($kop_isatuo == "") {
				echo "<br><br>";
				echo "<table>";
				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='KOPIOI'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
				
				echo "<tr><th>";
				
				if ($toim == "PERHE") {
					echo t("Syötä isä jolle perhe kopioidaan");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Syötä tuote jolle lisävarusteet kopioidaan");
				}
				else {
					echo t("Syötä valmiste jolle resepti kopioidaan");
				}
				
				echo ": </th><td><input type='text' name='kop_isatuo' value='$kop_isatuo' size='20'></td>";
				
				foreach($kop_tuoteno as $tuoteno) {					
										
					echo "<input type='hidden' name='kop_tuoteno[$tuoteno]' value='$kop_tuoteno[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_kerroin[$tuoteno]' value='$kop_kerroin[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_hinkerr[$tuoteno]' value='$kop_hinkerr[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_alekerr[$tuoteno]' value='$kop_alekerr[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_fakta[$tuoteno]' value='$kop_fakta[$tuoteno]'>"; 
				}
																								
				echo "<td><input type='submit' value='".t("Kopioi")."'></td></tr>";			
				echo "</form>";	
				echo "</table>";
		}
		else {
		
			$query  = "	select tuote.*, tuoteperhe.isatuoteno isa
						from tuote
						LEFT JOIN tuoteperhe ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.isatuoteno and tuoteperhe.tyyppi='$hakutyyppi'
						where tuote.tuoteno 	= '$kop_isatuo' 
						and tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.status 	   NOT IN ('P','X')
						HAVING isa is null";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 1) {
												
				foreach($kop_tuoteno as $tuoteno) {						
					if ($tuoteno != $kop_isatuo) {
						$query = "	INSERT into	tuoteperhe set 
									isatuoteno	= '$kop_isatuo',
									tuoteno 	= '$kop_tuoteno[$tuoteno]',
									kerroin 	= '$kop_kerroin[$tuoteno]',
									hintakerroin= '$kop_hinkerr[$tuoteno]',
									alekerroin 	= '$kop_alekerr[$tuoteno]',
									fakta	 	= '$kop_fakta[$tuoteno]',
									yhtio 		= '$kukarow[yhtio]',
									laatija		= '$kukarow[kuka]',
									luontiaika	= now(),
									tyyppi 		= '$hakutyyppi'";
						$result = mysql_query($query) or pupe_error($query);
					}
				}
				
				echo "<br><br><font class='message'>";
				
				if ($toim == "PERHE") {
					echo t("Tuoteperhe kopioitu");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Lisävarusteet kopioitu");
				}
				else {
					echo t("Resepti kopioitu");
				}
				
				echo "!</font><br>";
				
				$hakutuoteno = $kop_isatuo;
				$isatuoteno  = $kop_isatuo;
				$tee 		 = "";
			}
			else {
				echo "<br><br><font class='error'>";
				
				if ($toim == "PERHE") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo perhe");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo lisävarusteita");
				}
				else {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo resepti");
				}
				
				echo "!</font><br>";
				$tee = "";
			}
		}
	}
			
	if ($tee != "KOPIOI") {
		
		if($hakutuoteno2 != "") $hakutuoteno = $hakutuoteno2;
		
		echo "<br><table>";
		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>
				<input type='hidden' name='toim' value='$toim'>
			<tr>";
			
		if ($toim == "PERHE") {
			echo "<th>".t("Etsi tuoteperhettä").": </th>";
		}
		elseif ($toim == "LISAVARUSTE") {
			echo "<th>".t("Etsi tuotteen lisävausteita").": </th>";
		}
		else{
			echo "<th>".t("Etsi tuotereseptiä").": </th>";
		}		  		  

		echo "<td><input type='text' name='hakutuoteno' value='$hakutuoteno' size='20'></td></tr>";
		
		//	Haetaan tuotetta jos sellainen on annettu
		if($hakutuoteno!="") {

			echo "<tr><td class='back'><br></td></tr>";
			
			if ($tee != 'LISAA') {
				$tuoteno=$hakutuoteno;
			}
			
			$kutsuja="tuoteperhe.php";
			require_once "inc/tuotehaku.inc";

			//on vaan löytynyt 1 muuten tulis virhettä ja ulosta
			if ($varaosavirhe != "") {
				echo "<tr><td colspan='2'>$varaosavirhe</td></tr>";
				$tee="SKIPPAA";
			}
			elseif($ulos != "") {
				echo "<tr><td class='back' colspan='2'>$ulos</td><td class='back'><input type='submit' value='".("valitse")."'</td></tr>";
			}
		}

		echo "</table></form>";
	}
									
	if ($tee == 'LISAA') {
		
		echo "<br>";
				
		if (trim($isatuoteno) != trim($tuoteno)) {
			$ok = 1;
			
			$query  = "	select * 
						from tuoteperhe 
						where isatuoteno 	= '$isatuoteno' 
						and yhtio 			= '$kukarow[yhtio]' 
						and tyyppi 			= '$hakutyyppi'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) > 0) {														
				//katsotaan ettei tämä isa/lapsi kombinaatio ole jo olemassa
				$query  = "	select * 
							from tuoteperhe 
							where isatuoteno 	= '$isatuoteno' 
							and tuoteno 		= '$tuoteno' 
							and yhtio 			= '$kukarow[yhtio]' 
							and tyyppi 			= '$hakutyyppi'";
				$result = mysql_query($query) or pupe_error($query);
				
				//Jostunnus on erisuuri kuin tyhjä niin ollan päivittämässä olemassa olevaaa kombinaatiota
				if (mysql_num_rows($result) > 0 and $tunnus == "") {
					echo "<font class='message'>".t("Tämä tuoteperhekombinaatio on jo olemassa, sitä ei voi lisätä toiseen kertaan")."</font><br>";
					$ok = 0;
				}				
			}
			
			if ($ok == 1) {
				//tarkistetaan tuotteiden olemassaolo
				$error = '';

				$query = "select * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $isatuoteno ".t("ei ole tuoterekisterissä, riviä ei voida lisätä")."!</font><br>";

				$query = "select * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $tuoteno ".t("ei ole tuoterekisterissä, riviä ei voida lisätä")."!</font><br>";

				if ($error == '') {
					$kerroin 		= str_replace(',', '.', $kerroin);
					$hintakerroin 	= str_replace(',', '.', $hintakerroin);
					$alekerroin 	= str_replace(',', '.', $alekerroin);
													
					//lisätään rivi...
					if ($kerroin == '') {
						$kerroin = '1';
					}
					if ($hintakerroin == '') {
						$hintakerroin = '1';
					}
					if ($alekerroin == '') {
						$alekerroin = '1';
					}
	
					if ($tunnus == "") {
						$query = "	INSERT INTO ";
						$postq = " , laatija	= '$kukarow[kuka]',
								 	 luontiaika	= now()";
					}
					else { 
						$query = " 	UPDATE ";
						$postq = " 	, muuttaja='$kukarow[kuka]', 
									muutospvm=now()
									WHERE tunnus='$tunnus' ";
					}
					
					$query  .= "	tuoteperhe set 
									isatuoteno	= '$isatuoteno',
									tuoteno 	= '$tuoteno',
									kerroin 	= '$kerroin',
									hintakerroin= '$hintakerroin',
									alekerroin 	= '$alekerroin',
									yhtio 		= '$kukarow[yhtio]',
									tyyppi 		= '$hakutyyppi',
									ei_nayteta	= '$ei_nayteta'
									$postq";
					$result = mysql_query($query) or pupe_error($query);
					
					$tunnus = "";
					$tee 	= "";
				}
				else {
					echo "$error<br>";
					$tee = "";
				}
			}
		}
		else {
			echo t("Tuoteperheen isä ei voi olla sekä isä että lapsi samassa perhessä")."!<br>";
		}
		$tee = "";
	}
	
	if ($tee == 'POISTA') {
		//poistetaan rivi..
		$query  = "delete from tuoteperhe where tunnus='$tunnus' and yhtio='$kukarow[yhtio]' and tyyppi='$hakutyyppi'";
		$result = mysql_query($query) or pupe_error($query);
		$tunnus = '';
		$tee = "";
	}

	if (($hakutuoteno != '' or $isatuoteno != '') and $tee == "") {
		$lisa = "";
		$tchk = "";
									
		if ($isatuoteno != '') {
			$lisa = " and isatuoteno='$isatuoteno'";
			$tchk = $isatuoteno;
		}
		else {
			$lisa = " and isatuoteno='$hakutuoteno'";
			$tchk = $hakutuoteno;
		}
		
		
		$query  = "	select tuoteno
					from tuote 
					where yhtio = '$kukarow[yhtio]' 
					and tuoteno = '$tchk'";
		$result = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($result) == 1) {				
			$query  = "	select distinct isatuoteno 
						from tuoteperhe 
						where yhtio = '$kukarow[yhtio]' 
						$lisa
						and tyyppi = '$hakutyyppi'
						order by isatuoteno, tuoteno";
			$result = mysql_query($query) or pupe_error($query);
	
			if (mysql_num_rows($result)==0 and ($hakutuoteno != '' or $isatuoteno != '')) {
				if ($toim == "PERHE") {
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei löydy mistään tuoteperheestä")."!</font><br>";				
					echo "<br><font class='message'>".t("Perusta uusi tuoteperhe tuotteelle").": $hakutuoteno</font><br>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<br><font class='error'>".t("Lisävarusteita ei ole määritelty tuotteelle")." $hakutuoteno!</font><br>";				
					echo "<br><font class='message'>".t("Lisää lisävaruste tuotteelle").": $hakutuoteno</font><br>";
				}
				else{
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei löydy mistään tuotereseptistä")."!</font><br>";				
					echo "<br><font class='message'>".t("Lisää raaka-aine valmisteelle").": $hakutuoteno</font><br>";
				}
				
				
				echo "<table>";
				echo "<th>".t("Tuoteno")."</th><th>".t("Määräkerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th><td class='back'></td></tr>";
				
				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnus' value='$zrow[tunnus]'>
						<input type='hidden' name='tee' value='LISAA'>
						<input type='hidden' name='isatuoteno' value='$hakutuoteno'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";					
								
				echo "<tr>
					<td><input type='text' name='tuoteno' size='20'></td>
					<td><input type='text' name='kerroin' size='20'></td>
					<td><input type='text' name='hintakerroin' size='20'></td>
					<td><input type='text' name='alekerroin' size='20'></td>
			
					<td class='back'><input type='submit' value='".t("Lisää rivi")."'></td></form></tr>";
				echo "</table>";
			}
			elseif (mysql_num_rows($result)==1) {
				$row = mysql_fetch_array($result);
				$isatuoteno	= $row['isatuoteno'];
				
				//isätuotteen checkki
				$error = "";
				$query = "select * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
	
				if (mysql_num_rows($res)==0) {
					echo "<font class='error'>".t("Tuote ei enää rekisterissä")."!)</font><br>";
				}
				else {
					$isarow=mysql_fetch_array($res);
				}
				
				echo "<br><table>";
				echo "<tr>";
				
				if ($toim == "PERHE") {
					echo "<th>".t("Tuoteperheen isätuote").": </th>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				else {
					echo "<th>".t("Tuotereseptin valmiste").": </th>";
				}
				
				echo "<tr><td>$isatuoteno - $isarow[nimitys]</td></tr></table><br>";
				
				$query = "SELECT ei_nayteta FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and ei_nayteta != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = mysql_query($query) or pupe_error($query);
				$faktarow = mysql_fetch_array($ressu);
				
				if ($faktarow["ei_nayteta"] == "") {
					$sel1 = "SELECTED";
				}
				elseif($faktarow["ei_nayteta"] == "E") {
					$sel2 = "SELECTED";
				}
				
				echo "<table><form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
				  		<input type='hidden' name='tee' value='TALLENNAESITYSMUOTO'>
				  		<input type='hidden' name='tunnus' value='$prow[tunnus]'>
				  		<input type='hidden' name='isatuoteno' value='$isatuoteno'>	
				  		<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
				
				echo "<tr><th>".t("Esitysmuoto").": </th></tr>";
				echo "<tr><td>";
				echo "	<select name='ei_nayteta'>
						<option value='' $sel1>".t("Kaikki rivit näyetään")."</option>
						<option value='E' $sel2>".t("Lapsirivejä ei näytetä")."</option>
						</select></td>";
				
				$query = "SELECT omasivu FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and omasivu != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = mysql_query($query) or pupe_error($query);
				$faktarow = mysql_fetch_array($ressu);
				
				if($toim == "RESEPTI") {
					if($faktarow["omasivu"] != "") {
						$sel1 = "";
						$sel2 = "SELECTED";
					}
					else {
						$sel1 = "SELECTED";
						$sel2 = "";	
					}
					
					//echo "faktarow".$faktarow["omasivu"];
					
					echo "<tr><th>".t("Reseptin tulostus").": </th></tr>";
					echo "<tr><td>";
					echo "	<select name='omasivu'>
							<option value='' $sel1>".t("Resepti tulostetaan normaalisti")."</option>
							<option value='X' $sel2>".t("Resepti tulostetaan omalle sivulle")."</option>
							</select></td>";
				}
				
				echo "</table><br>";
				
				echo "<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
				  		<input type='hidden' name='tee' value='TALLENNAFAKTA'>
				  		<input type='hidden' name='tunnus' value='$prow[tunnus]'>
				  		<input type='hidden' name='isatuoteno' value='$isatuoteno'>	
				  		<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
	
				echo "<table>";
				echo "<tr>";
				
				if ($toim == "PERHE") {
					echo "<th>".t("Tuoteperheen faktat").": </th></tr>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Lisävarusteiden faktat").": </th></tr>";
				}
				else {
					echo "<th>".t("Reseptin faktat").": </th></tr>";
				}
				
				$query = "SELECT fakta FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and fakta != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = mysql_query($query) or pupe_error($query);
				$faktarow = mysql_fetch_array($ressu);
				
				echo "<td><textarea cols='35' rows='7' name='fakta'>$faktarow[fakta]</textarea></td>";
				
				if($toim == "RESEPTI") {
					
					$query = "SELECT fakta2 FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and fakta2 != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
					$ressu = mysql_query($query) or pupe_error($query);
					$faktarow = mysql_fetch_array($ressu);
					
					echo "</tr><tr>";
					echo "<th>".t("Yhdistämisen lisätiedot").": </th></tr>";
					echo "<td><textarea cols='35' rows='4' name='fakta2'>$faktarow[fakta2]</textarea></td>";
				}
				echo "<td class='back'>  				
					  <input type='submit' value='".t("Tallenna")."'>
					  </td></form>";		
				echo "</tr></table><br>";
				
				
				echo "<table><tr>";
				
				if ($toim == "PERHE") {
					echo "<th>".t("Lapset")."</th><th>".t("Nimitys")."</th><th>".t("Määräkerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Lisävarusteet")."</th><th>".t("Nimitys")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
				else {
					echo "<th>".t("Raaka-aineet")."</th><th>".t("Nimitys")."</th><th>".t("Määräkerroin")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
													
				$query = "	SELECT * 
							FROM tuoteperhe 
							WHERE isatuoteno = '$row[isatuoteno]' 
							and yhtio = '$kukarow[yhtio]'
							and tyyppi = '$hakutyyppi'
							order by isatuoteno, tuoteno";
				$res   = mysql_query($query) or pupe_error($query);
				
				$resyht = 0;
				
				$kop_tuoteno = array();
				$kop_kerroin = array();
				$kop_hinkerr = array(); 
				$kop_alekerr = array();
				$kop_fakta = array();
				
				while ($prow = mysql_fetch_array($res)) {
					//Tarkistetaan löytyyko tuote enää rekisteristä
					$query = "select * from tuote where tuoteno='$prow[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$res1   = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($res1)==0) { 
						$error="<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
					}
					else {
						$error = "";
						$tuoterow = mysql_fetch_array($res1);
					
						//Tehdään muuttujat jotta voidaan tarvittaessa kopioida resepti
						$kop_tuoteno[$tuoterow['tuoteno']] = $prow['tuoteno'];
						$kop_kerroin[$tuoterow['tuoteno']] = $prow['kerroin'];
						$kop_hinkerr[$tuoterow['tuoteno']] = $prow['hintakerroin'];
						$kop_alekerr[$tuoterow['tuoteno']] = $prow['alekerroin'];										
						$kop_fakta[$tuoterow['tuoteno']] = $prow['fakta'];										
					}
					
					$lapsiyht = $tuoterow['kehahin']*$prow['kerroin'];
					$resyht += $lapsiyht;
					
					if ($tunnus != $prow["tunnus"]) {												
						echo "<tr class='aktiivi'><td>$prow[tuoteno] $error</td><td>".asana('nimitys_',$prow['tuoteno'],$tuoterow['nimitys'])."</td>";
						
						if ($toim != "LISAVARUSTE") {
							echo "<td>$prow[kerroin]</td>";
						}
						
						if ($toim == "PERHE") {														
							echo"<td>$prow[hintakerroin]</td><td>$prow[alekerroin]</td>";
						}
						
							
						echo "<td>$tuoterow[kehahin]</td><td>".round($lapsiyht,6)."</td>";
						
																									
						echo "<form action='$PHP_SELF' method='post'>
								<td class='back'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$prow[tunnus]'>
								<input type='hidden' name='isatuoteno' value='$isatuoteno'>	
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>					
								<input type='submit' value='".t("Muuta")."'>
								</td></form>";
							
							
						echo "<form action='$PHP_SELF' method='post'>
								<td class='back'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tee' value='POISTA'>
								<input type='hidden' name='tunnus' value='$prow[tunnus]'>
								<input type='hidden' name='isatuoteno' value='$isatuoteno'>	
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>					
								<input type='submit' value='".t("Poista")."'>
								</td></form>";
							
						echo "</tr>";
					}
					elseif ($tunnus == $prow["tunnus"]) {			
						$query  = "	SELECT * 
									FROM tuoteperhe
									WHERE yhtio = '$kukarow[yhtio]' 
									and tunnus = '$tunnus'
									and tyyppi = '$hakutyyppi'";
						$zresult = mysql_query($query) or pupe_error($query);
						$zrow = mysql_fetch_array($zresult);
						
						
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$zrow[tunnus]'>
								<input type='hidden' name='tee' value='LISAA'>
								<input type='hidden' name='isatuoteno' value='$zrow[isatuoteno]'>
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";					
						
							
						echo "<tr>
							<td><input type='text' name='tuoteno' size='20' value='$zrow[tuoteno]'></td>
							<td></td>";
							
						if ($toim != "LISAVARUSTE") {	
							echo "	<td><input type='text' name='kerroin' size='10' value='$zrow[kerroin]'></td>";
						}
						
						if ($toim == "PERHE") {	
							echo "	<td><input type='text' name='hintakerroin' size='10' value='$zrow[hintakerroin]'></td>
									<td><input type='text' name='alekerroin' size='10' value='$zrow[alekerroin]'></td>";
						}
						
						echo "<td>$tuoterow[kehahin]</td><td>".round($lapsiyht,6)."</td>";
						echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td></form></tr>";
					}					
				}
				
				if ($tunnus == "") {
					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='LISAA'>
							<input type='hidden' name='isatuoteno' value='$row[isatuoteno]'>
							<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
							
																			
					echo "<tr>
							<td><input type='text' name='tuoteno' size='20'></td>
							<td></td>";
							
					if ($toim != "LISAVARUSTE") {
						echo "<td><input type='text' name='kerroin' size='10'></td>";
					}
					
					if ($toim == "PERHE") {
						echo "<td><input type='text' name='hintakerroin' size='10'></td>
								<td><input type='text' name='alekerroin' size='10'></td>";
					}
							
					echo "	<th align='right'>".t("Yhteensä").":</th>
							<th>".round($resyht,6)."</th>";
					
					echo "	<td class='back'><input type='submit' value='".t("Lisää")."'></td>
							</form>
							</tr>";
				}
				echo "</table>";
				
				echo "<br><br>";
				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='KOPIOI'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
				
				
				foreach($kop_tuoteno as $tuoteno) {					
										
					echo "<input type='hidden' name='kop_tuoteno[$tuoteno]' value='$kop_tuoteno[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_kerroin[$tuoteno]' value='$kop_kerroin[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_hinkerr[$tuoteno]' value='$kop_hinkerr[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_alekerr[$tuoteno]' value='$kop_alekerr[$tuoteno]'>"; 
					echo "<input type='hidden' name='kop_fakta[$tuoteno]' value='$kop_fakta[$tuoteno]'>"; 
				}
																								
				echo "<input type='submit' value='".t("Kopioi")."'>";			
				echo "</form>";
				
			}
			else {
				echo "<table>";
				
				while($row = mysql_fetch_array($result)) {
					$query = "	select * 
								from tuoteperhe 
								where isatuoteno = '$row[isatuoteno]' 
								and yhtio = '$kukarow[yhtio]'
								and tyyppi = '$hakutyyppi'
								order by isatuoteno, tuoteno";
					$res   = mysql_query($query) or pupe_error($query);
					
					echo "<tr><td><a href='$PHP_SELF?toim=$toim&isatuoteno=$row[isatuoteno]&hakutuoteno=$row[isatuoteno]'>$row[isatuoteno]</a></td>";
					
					while($prow = mysql_fetch_array($res)) {
						echo "<td>$prow[tuoteno]</td>";
					}				
					
					echo "</tr>";
				}
				echo "</table>";
			}
		}
		else {
			echo "<br><font class='error'>".t("Tuotenumeroa")." $tchk ".t("ei löydy")."!</font><br>";
		}				
	}
	
	require ("inc/footer.inc");
	
?>
