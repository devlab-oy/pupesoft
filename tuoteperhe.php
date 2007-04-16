<?php
	require ("inc/parametrit.inc");

	if ($toim == "PERHE") {
		echo "<font class='head'>".t("Tuoteperheet")."</font><hr>";
		$hakutyyppi = "P";
	}
	elseif ($toim == "LISAVARUSTE") {
		echo "<font class='head'>".t("Tuotteen lis‰varusteet")."</font><hr>";
		//$hakutyyppi = "'LT','LO','LR','LW'";
		$hakutyyppi = "L";
	}
	else {
		echo "<font class='head'>".t("Tuotereseptit")."</font><hr>";
		$hakutyyppi = "R";
	}
	
	if ($tee == 'TALLENNAFAKTA') {
		$query = "UPDATE tuoteperhe SET fakta = '' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "UPDATE tuoteperhe SET fakta = '$fakta' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' ORDER BY isatuoteno, tuoteno LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "<br><br><font class='message'>".t("Faktatieto tallennettu")."!</font><br>";
		
		$tee = '';
	}
	
	if ($tee == 'TALLENNAESITYSMUOTO') {
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
				
				echo "<tr><th>".t("Syˆt‰ is‰ jolle perhe kopioidaan").": </th><td><input type='text' name='kop_isatuo' value='$kop_isatuo' size='20'></td>";
				
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
				
				echo "<br><br><font class='message'>".t("Tuoteperhe kopioitu")."!</font><br>";
				$hakutuoteno = $kop_isatuo;
				$isatuoteno  = $kop_isatuo;
				$tee 		 = "";
			}
			else {
				echo "<br><br><font class='error'>".t("Tuotetta ei lˆydy j‰rjestelm‰st‰ tai tuotteella on jo perhe")."!</font><br>";
				$tee = "";
			}
		}
	}
			
	if ($tee != "KOPIOI") {
		echo "<br><table>";
		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>
				<input type='hidden' name='toim' value='$toim'>
			<tr>";
			
		if ($toim == "PERHE") {
			echo "<th>".t("Etsi tuoteperhett‰").": </th>";
		}
		elseif ($toim == "LISAVARUSTE") {
			echo "<th>".t("Etsi tuotteen lis‰vausteita").": </th>";
		}
		else{
			echo "<th>".t("Etsi tuoteresepti‰").": </th>";
		}		  		  
			
		echo "<td class='back'><input type='text' name='hakutuoteno' value='$hakutuoteno' size='20'></td>
			<td class='back'><input type='submit' value='".t("Etsi")."'></td>
			</tr>
			</form>";
		echo "</table>";
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
				//katsotaan ettei t‰m‰ isa/lapsi kombinaatio ole jo olemassa
				$query  = "	select * 
							from tuoteperhe 
							where isatuoteno 	= '$isatuoteno' 
							and tuoteno 		= '$tuoteno' 
							and yhtio 			= '$kukarow[yhtio]' 
							and tyyppi 			= '$hakutyyppi'";
				$result = mysql_query($query) or pupe_error($query);
				
				//Jostunnus on erisuuri kuin tyhj‰ niin ollan p‰ivitt‰m‰ss‰ olemassa olevaaa kombinaatiota
				if (mysql_num_rows($result) > 0 and $tunnus == "") {
					echo "<font class='message'>".t("T‰m‰ tuoteperhekombinaatio on jo olemassa, sit‰ ei voi lis‰t‰ toiseen kertaan")."</font><br>";
					$ok = 0;
				}				
			}
			if ($ok == 1) {
				//tarkistetaan tuotteiden olemassaolo
				$error = '';

				$query = "select * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $isatuoteno ".t("ei ole tuoterekisteriss‰, rivi‰ ei voida lis‰t‰")."!</font><br>";

				$query = "select * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $tuoteno ".t("ei ole tuoterekisteriss‰, rivi‰ ei voida lis‰t‰")."!</font><br>";

				if ($error == '') {
					$kerroin 		= str_replace(',', '.', $kerroin);
					$hintakerroin 	= str_replace(',', '.', $hintakerroin);
					$alekerroin 	= str_replace(',', '.', $alekerroin);
													
					//lis‰t‰‰n rivi...
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
			echo t("Tuoteperheen is‰ ei voi olla sek‰ is‰ ett‰ lapsi samassa perhess‰")."!<br>";
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
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei lˆydy mist‰‰n tuoteperheest‰")."!</font><br>";				
					echo "<br><font class='message'>".t("Perusta uusi tuoteperhe tuotteelle").": $hakutuoteno</font><br>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<br><font class='error'>".t("Lis‰varusteita ei ole m‰‰ritelty tuotteelle")." $hakutuoteno!</font><br>";				
					echo "<br><font class='message'>".t("Lis‰‰ lis‰varuste tuotteelle").": $hakutuoteno</font><br>";
				}
				else{
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei lˆydy mist‰‰n tuotereseptist‰")."!</font><br>";				
					echo "<br><font class='message'>".t("Lis‰‰ raaka-aine valmisteelle").": $hakutuoteno</font><br>";
				}
				
				
				echo "<table>";
				echo "<th>".t("Tuoteno")."</th><th>".t("M‰‰r‰kerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th><td class='back'></td></tr>";
				
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
			
					<td class='back'><input type='submit' value='".t("Lis‰‰ rivi")."'></td></form></tr>";
				echo "</table>";
			}
			elseif (mysql_num_rows($result)==1) {
				$row = mysql_fetch_array($result);
				$isatuoteno	= $row['isatuoteno'];
				
				//is‰tuotteen checkki
				$error = "";
				$query = "select * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);
	
				if (mysql_num_rows($res)==0)
					$error="<font class='error'>(".t("Tuote ei en‰‰ rekisteriss‰")."!)</font>";
	
				echo "<br><table>";
				echo "<tr>";
				
				if ($toim == "PERHE") {
					echo "<th>".t("Tuoteperheen is‰tuote").": </th>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				else {
					echo "<th>".t("Tuotereseptin valmiste").": </th>";
				}
				
				echo "<th>".t("Esitysmuoto").": </th>";
				
				echo "</td><td class='back'></td></tr>";
				echo "<tr><td>$isatuoteno $error</td><td>";
				
				
				$query = "SELECT fakta, ei_nayteta FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = mysql_query($query) or pupe_error($query);
				$faktarow = mysql_fetch_array($ressu);
				
				
				if ($faktarow["ei_nayteta"] == "") {
					$sel1 = "SELECTED";
				}
				elseif($faktarow["ei_nayteta"] == "E") {
					$sel2 = "SELECTED";
				}
				
				echo "<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='toim' value='$toim'>
				  		<input type='hidden' name='tee' value='TALLENNAESITYSMUOTO'>
				  		<input type='hidden' name='tunnus' value='$prow[tunnus]'>
				  		<input type='hidden' name='isatuoteno' value='$isatuoteno'>	
				  		<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
				
				echo "	<select name='ei_nayteta'>
						<option value='' $sel1>".t("Kaikki rivit n‰yet‰‰n")."</option>
						<option value='E' $sel2>".t("Lapsirivej‰ ei n‰ytet‰")."</option>
						</select>";
				
				echo "</td><td class='back'>  				
					  <input type='submit' value='".t("Tallenna")."'>
					  </form></td></tr>";
				
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
					echo "<th>".t("Lis‰varusteiden faktat").": </th></tr>";
				}
				else {
					echo "<th>".t("Reseptin faktat").": </th></tr>";
				}
				
				echo "<td><textarea cols='35' rows='7' name='fakta'>$faktarow[fakta]</textarea></td>";
				
				echo "<td class='back'>  				
					  <input type='submit' value='".t("Tallenna")."'>
					  </td></form>";		
				echo "</tr></table><br>";
				
				
				echo "<table><tr>";
				
				if ($toim == "PERHE") {
					echo "<th>".t("Lapset")."</th><th>".t("Nimitys")."</th><th>".t("M‰‰r‰kerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Lis‰varusteet")."</th><th>".t("Nimitys")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
				else {
					echo "<th>".t("Raaka-aineet")."</th><th>".t("Nimitys")."</th><th>".t("M‰‰r‰kerroin")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
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
					//Tarkistetaan lˆytyyko tuote en‰‰ rekisterist‰
					$query = "select * from tuote where tuoteno='$prow[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$res1   = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($res1)==0) { 
						$error="<font class='error'>(".t("Tuote ei en‰‰ rekisteriss‰")."!)</font>";
					}
					else {
						$error = "";
						$tuoterow = mysql_fetch_array($res1);
					
						//Tehd‰‰n muuttujat jotta voidaan tarvittaessa kopioida resepti
						$kop_tuoteno[$tuoterow['tuoteno']] = $prow['tuoteno'];
						$kop_kerroin[$tuoterow['tuoteno']] = $prow['kerroin'];
						$kop_hinkerr[$tuoterow['tuoteno']] = $prow['hintakerroin'];
						$kop_alekerr[$tuoterow['tuoteno']] = $prow['alekerroin'];										
						$kop_fakta[$tuoterow['tuoteno']] = $prow['fakta'];										
					}
					
					$lapsiyht = $tuoterow['kehahin']*$prow['kerroin'];
					$resyht += $lapsiyht;
					
					if ($tunnus != $prow["tunnus"]) {												
						echo "<tr><td>$prow[tuoteno] $error</td><td>".asana('nimitys_',$prow['tuoteno'],$tuoterow['nimitys'])."</td>";
						
						if ($toim != "LISAVARUSTE") {
							echo "<td>$prow[kerroin]</td>";
						}
						
						if ($toim == "PERHE") {														
							echo"<td>$prow[hintakerroin]</td><td>$prow[alekerroin]</td>";
						}
						
							
						echo "<td>$tuoterow[kehahin]</td><td>".round($lapsiyht,4)."</td>";
						
																									
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
						
						echo "<td>$tuoterow[kehahin]</td><td>".round($lapsiyht,4)."</td>";
						echo "<td class='back'><input type='submit' value='".t("P‰ivit‰")."'></td></form></tr>";
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
							
					echo "	<th align='right'>".t("Yhteens‰").":</th>
							<th>".round($resyht,4)."</th>";
					
					echo "	<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>
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
			echo "<br><font class='error'>".t("Tuotenumeroa")." $tchk ".t("ei lˆydy")."!</font><br>";
		}				
	}
	
	require ("inc/footer.inc");
	
?>
