<?php
	require ("inc/parametrit.inc");
	echo "<font class='head'>".t("Kulunvalvonta")."</font><hr>";
	
	//JOKAISELLA MYYNTITYKILLÄ ON OMAT TUNNUKSET, JOTEN TUON VIIVAKOODIN LÖYDÄMME KÄTEVÄSTI SUORAAN KUKAROWSTA
	if ($_GET['toim'] == 'MYYNTI') {
		$tee = "viivakoodi";
		$viivakoodi = md5($kukarow["tunnus"]);
	}
	
	//UUDEN ERITTELYMERKINNAN LISAYS TIETOKANTAAN
	if ($tee == "lisaa") {
		//summataan hours ja minutes tunnit muuttujaan
		$tunnit = ($hours*60)+$minutes;
		
		//tiedot kannasta
		$query  = "	SELECT * 
					FROM kuka 
					WHERE yhtio = '$yhtio' and kuka='$kuka'";
		$result = mysql_query($query)  or pupe_error($query);
			
		// fetchataan rivi
		$rivi = mysql_fetch_array($result);
		
		//lisataan uusi rivi kantaan erittelyksi samalle kellonajalle kuin itse sisäänkirjautuminen oli
		if ($tyoaika > 0 && ($tyoaika >= $tunnit) && ($otunnus != '' or $laatu != '') ) {
			$query  = "INSERT INTO kulunvalvonta (yhtio, kuka, aika, suunta, tyyppi,otunnus,minuuttimaara) 
					   VALUES('$rivi[yhtio]','$kuka','".date('Y-m-d H:i:s',$sisaanaika)."','','$laatu','$otunnus','$tunnit')";
			$result = mysql_query($query) or pupe_error($query);
			$tyoaika = $tyoaika-$tunnit;		

		}
		else
			echo "<font class='error'>" .t("VIRHE: ERITELTYJÄ TUNTEJA ENEMMÄN KUIN TEHTYJÄ TUNTEJA TAI PROJEKTIA EI OLE VALITTU.") . "</font>";

		$tee = "erittele";
	}	
	
	//MUOKATUN ERITTELYMERKINNÄN TALLENNUS TIETOKANTAAN			
	if ($tee == 'tallenna') {
		
		//JOS OLLAAN MYYJIÄ,JA MUOKATAAN AIKOJA, NIIN TARKISTELLAAN ETTEI MUOKATUT AJAT MENE PÄÄLLEKKÄIN
		if ($myynti == 'true') {
			//haetaan käyttäjän edelliset kirjaukset
			$query = "	SELECT unix_timestamp(aika) sisaan, (
							SELECT unix_timestamp(aika) 
							FROM kulunvalvonta kv
							WHERE kv.yhtio='$yhtio' and kv.kuka='$kuka' and kv.suunta='O' and kv.aika > kulunvalvonta.aika
							ORDER BY aika DESC
							LIMIT 1
							) ulos
						FROM kulunvalvonta 
						WHERE yhtio='$yhtio' and kuka='$kuka' and suunta='I' 
						ORDER BY aika
						DESC";
						
			$sisaan = mysql_query($query) or pupe_error($query);
					
			//puretaan muokattu aika muuttujiin
			$vuosi = substr($kirjautumisaika, 0,4);
			$kuukausi = substr($kirjautumisaika, 5,2);
			$paiva = substr($kirjautumisaika, 8,2);
 			$tunti = substr($kirjautumisaika, 11,2);
			$minuutti = substr($kirjautumisaika, 14,2);	
			
			//tehdään unix_timestamppi muokatusta ajasta
			$uusiaika = mktime($tunti,$minuutti,00,$kuukausi,$paiva,$vuosi);
			
			$paallekkainen = 'FALSE';
			
			while($kirjautumisaika = mysql_fetch_array($sisaan)) {
				
				
			
			
			
				echo "<br>kirjautumisaika: $kirjautumisaika[sisaan] $kirjautumisaika[ulos]";
			}
					
						
		}
		else {
			//otetaan hours ja minutes yhteen tunnit muuttujaan
			$tunnit = ($hours*60)+$minutes;
			
			//samoin otetaan tuohon muokatun_vanhat_tunnit muuttujaan noi lomakkeelta tulevat vanhat tunnit ja minuutit
			$muokatun_vanhat_tunnit = ($muokatun_vanhat_tunnit*60)+$muokatun_vanhat_minuutit;
			
			//PÄIVITETÄÄN JÄLJELLÄ OLEVA TYÖAIKA
			
			$tyoaika = $tyoaika - $tunnit + $muokatun_vanhat_tunnit;
						
			if ($tyoaika < 0) {
				echo "<font class='error'><br>" . t("VIRHE: TYOAIKASI ON JO ERITELTY TAI YRITÄT LISÄTÄ LIIKAA TUNTEJA.") . "<br></font>";
				
				//ASETETAAN TYOAIKA TAKAISIN SINNE MISSÄ SE OLI (MUUTETTIIN ALUSSA)
				$tyoaika = $tyoaika + $tunnit - $muokatun_vanhat_tunnit;
				$tee = "erittele";
			}
			else {
				
				//jos projekti ja tyon laatu on asetettu talletetaan normisti kantaan
				if ($otunnus != '' && $laatu != '') {
				
				
				$query = "	UPDATE kulunvalvonta 
							SET minuuttimaara='$tunnit', otunnus='$otunnus', tyyppi='$laatu' 
							WHERE tunnus='$tunnus'";
				$result = mysql_query($query) or pupe_error($query);
				
					if (!$result) 
						echo "<font class='error'><br>Tietokantavirhe tallennettaessa erittelyä tietokantaan!</font><br>";
					 
				}
				
				//jos laatu on jätetty tyhjäksi, talletetaan pelkkä otunnus ja tyhjätään tyyppi
				if ($laatu == '' && $otunnus != '') {
					$query = "	UPDATE kulunvalvonta 
								SET minuuttimaara='$tunnit', otunnus='$otunnus', tyyppi=''
								WHERE tunnus='$tunnus'";
					$result = mysql_query($query) or pupe_error($query);
					
						if (!$result) 
							echo "<font class='error'><br>Tietokantavirhe tallennettaessa erittelyä tietokantaan!</font><br>";
						
				}	
				
				//jos jötetty projekti tyhjäksi (ja laatu on asetettu) niin talletetaan laatu ja tyhjätään otunnus
				if ($laatu != '' && $otunnus == '') {
					$query = "	UPDATE kulunvalvonta 
								SET minuuttimaara='$tunnit', tyyppi='$laatu', otunnus=''
								WHERE tunnus='$tunnus'";
					$result = mysql_query($query) or pupe_error($query);
					
						if (!$result) 
							echo "<font class='error'><br>Tietokantavirhe tallennettaessa erittelyä tietokantaan!</font><br>";
				}
				
				
				$tee = "erittele";
				
					
				//jos joku koittaa tallettaa syöttämättä tietoja...
				if ($laatu == '' && $otunnus == '') { 
					
					//ASETETAAN TYOAIKA TAKAISIN SINNE MISSÄ SE OLI (MUUTETTIIN ALUSSA)
					$tyoaika = $tyoaika + $tunnit - $muokatun_vanhat_tunnit;
					
					echo "<br><br><font class='message'>".t("Syötä tiedot!")."</font>";
				}
			}
		}
	}
	

	if ($tee == "viivakoodi") {
		// viivakoodista tulee kukarow tunnuksen md5 summa		
				$query  = "	SELECT * 
							FROM kuka 
							WHERE md5(tunnus)='$viivakoodi'";  //TÄHÄN LOPUKSI md5(tunnus)=......
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) != 1) {
			echo "<font class='message'>".t("Virhe! Käyttäjää ei löytynyt!")."</font>";
			$tee = "";
		}
		else {
			$rivi = mysql_fetch_array($result);

			// haetaan käyttäjän vika kirjaus
			$query  = " SELECT * 
						FROM kulunvalvonta 
						WHERE yhtio = '$rivi[yhtio]' and kuka='$rivi[kuka]' and (suunta='O' or suunta='I') 
						ORDER BY aika 
						DESC LIMIT 1";		
			$result = mysql_query($query) or pupe_error($query);
			$kulu   = mysql_fetch_array($result);
		
			$erotus = 0;
			//tarkastetaan onko käyttäjä eritellyt edellisen uloskirjautumisen tunnit
			if($kulu["suunta"] == "O") {
				//haetaan tyoaika
 				$query = "	SELECT (unix_timestamp('$kulu[aika]')-unix_timestamp(aika))
							FROM kulunvalvonta
							WHERE yhtio = '$rivi[yhtio]' and kuka = '$rivi[kuka]' and suunta='I'
							ORDER BY aika DESC
							LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				$tehdyt_tyominuutit = @mysql_result($result,0)/60;
		
				
				//TARKISTETAAN ERITELTYJEN TUNTIEN MÄÄRÄ
				$query = "	SELECT sum(minuuttimaara)
							FROM kulunvalvonta
							WHERE yhtio = '$rivi[yhtio]' AND kuka = '$rivi[kuka]' AND suunta NOT IN ('I', 'O') AND aika = (
								SELECT aika 
								FROM kulunvalvonta 
								WHERE yhtio = '$rivi[yhtio]' AND kuka='$rivi[kuka]' and suunta='I' 
								ORDER BY aika DESC
								LIMIT 1) ";
				
				$result = mysql_query($query) or pupe_error($query);
				$kirjatut_tyominuutit = @mysql_result($result,0);
			
				$erotus = round(($tehdyt_tyominuutit - $kirjatut_tyominuutit),0);
			
				//JOS erotus on yli nollan niin tunteja on kirjaamatta, mennään erittelyyn ja asetetaan erittelemättömäksi tuo erotus
				if ($erotus > 0) {
					$tyoaika = round($erotus,0);
					$tee = "erittele";
				}
			
				
			}
			$kuka = $rivi['kuka'];
			$yhtio = $rivi['yhtio'];
			
			//JOS KAIKKI ERITTELYT ON HOIDETTU, NIIN PÄÄSTETÄÄN NORMAALISTI ETEENPÄIN, MUUTEN MENNÄÄN ERITTELYYN JATKAMAAN
			if ($_GET['toim'] == 'MYYNTI') {
				$tee = "napit";
				$myynti = "true";
			}
			if (round($erotus,0) == 0) {		
		
			   // tehdään selkokielinen suunta
			    if ($kulu["suunta"] == "I") $suunta = t("Sisällä");
			    else $suunta = t("Ulkona");

			   // näytetään käyttäjän tietoja
			   echo "<table>";
			   echo "<tr><th>".t("Nimi")."</th><td>$rivi[nimi]</td></tr>";
			   echo "<tr><th>".t("Tila")."</th><td>$suunta</td></tr>";
			   echo "<tr><th>".t("Kirjattu")."</th><td>$kulu[aika]</td></tr>";
			   echo "<tr><th>".t("Aika nyt")."</th><td>".date("Y-m-d H:i:s")."</td></tr>";
			   echo "</table>";
        	
			   // tehdään käyttöliittymänapit
			   echo "<br><font class='head'>".t("Valitse kirjaus")."</font><hr>";
        	
			   echo "<form name='napit' action='$PHP_SELF' method='post' autocomplete='off'>";
			   echo "<input type='hidden' name='tee' value='napit'>";
			   echo "<input type='hidden' name='kuka' value='$rivi[kuka]'>";
				echo "<input type='hidden' name='yhtio' value='$rivi[yhtio]'>";
        	   //myyjille tarkoitettu linkki jos urlissa on toimintona myynti (heitetään postina eteenpäin viivakoodin yhteydessä)
			   if ($_GET['toim'] == 'MYYNTI') {
        	   		//echo "<br><input type='submit' name='myynti' value='".t("Kirjaudu")."' style='font-size: 25px;'>";
			   }
			   else {
							   	 
			   	 // jos ollaan viimeks kirjattu ulos, niin näytetään vaan sisään nappeja
			   	 if ($kulu["suunta"] == "O" or $kulu['suunta'] == "")  {
			   		echo "<table>";
			   		echo "<tr><td width='200' class='back' valign='top'>";
			   		echo "<input type='submit' accesskey='1' name='normin' value='".t("Kirjaudu sisään")."' style='font-size: 25px;'><br>";
			   	 
			   		echo "</td><td class='back'>";
			   		//echo "<input type='submit' name='matkain' value='".t("Matka Sisään")."'><br>";
			   		//echo "<input type='submit' name='sickin' value='".t("Sairasloma Sisään")."'><br>";
			   		echo "</td></tr>";
			   		echo "</table>";
			   					   	
			   		$formi  = "napit";
			   		$kentta = "normin";
			   	 }
				
			   }

		  	  	// jos ollaan viimeks kirjattu sisään, niin näytetään vaan ulos nappeja
		  	  	if ($kulu["suunta"] == "I") {
		  	  	
		  	  	echo "<br>&nbsp;<hr><table>";
		  	  	echo "<tr><td width='200' class='back' valign='top'>";
		  	  	echo "<input type='submit' accesskey='1' name='normout' value='".t("Kirjaudu ulos")."' style='font-size: 25px;'><br>";
		  	  	echo "</td><td class='back'>";
		  	 
		 		//TULEVAISUUDEN KEHITTELYÄ?
				//echo "<input type='submit' name='matkaout' value='".t("Matka Ulos")."'><br>";
		  	  	//echo "<input type='submit' name='sickout' value='".t("Sairasloma Ulos")."'><br>";
		  	  	//echo "<input type='submit' name='lomaout' value='".t("Loma Ulos")."'><br>";

		  	  	echo "</td></tr>";
		  	  	echo "</table>";
          	
		  	  	$formi  = "napit";
		  	  	$kentta = "normout";
		  	  	}
						
				echo "<br><br><br>";	
				if ($_GET['toim'] != 'MYYNTI') {
					echo "<hr><input type='submit' name='peruuta' value='".t("Peruuta kirjaus")."'>";
				}
				echo "</form>";
			}
		
		}
	}

	if ($tee == "napit") {

		if ($peruuta == "") {
			// haetaan kukarow
			$query  = "	SELECT * 
						FROM kuka 
						WHERE yhtio = '$yhtio' and kuka='$kuka'"; 
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) != 1) {
				die ("Tiedot katosivat! $query");
			}
			
			// fetchataan rivi
			$rivi = mysql_fetch_array($result);
		
			
			
			
			//myynnin puoli saa itse asettaa sisään- ja ulostuloaikansa
			if ($myynti == "true" and ($_GET['toim'] == "MYYNTI" or $_POST['toim'] == 'MYYNTI')) {
				echo "<font class='message'>" .t("Anna aloitus- ja lopetusaika") . "<br>";
				$paiva = date('d');
				$kuukausi = date('m');
				$vuosi = date('Y');
				
				echo "<table><tr><th align='center' colspan='5'>" . t("Aloitusaika") . "<th>&nbsp;</th><th align='center' colspan='5'>". t("Lopetusaika") . "</th></tr>
				<tr align='center'><th>" . t("Päivä") . "</th><th>" . t("Kuukausi") . "</th><th>" . t("Vuosi") . "</th><th>" . t("Tunti") . "</th><th>" . t("Minuutti") . "</th><th>&nbsp;</th>
					<th>" . t("Päivä") . "</th><th>" . t("Kuukausi") . "</th><th>" . t("Vuosi") . "</th><th>" . t("Tunti") . "</th><th>" . t("Minuutti") . "</th>
				</tr>
				<tr align='center'>
					<form action='$PHP_SELF' name='myyntimies' method='post' >
					<td><input type='text' name='myyntisisaan_paiva' value='$paiva' size='4'></td>
					<td><input type='text' name='myyntisisaan_kuukausi' value='$kuukausi' size='4'></td>
					<td><input type='text' name='myyntisisaan_vuosi' value='$vuosi' size='4'></td>										
					<td><input type='text' name='myyntisisaan_tunti' size='4'></td>
					<td><input type='text' name='myyntisisaan_minuutti' size='4'></td>
					<td>--</td>
					<td><input type='text' name='myyntiulos_paiva' value='$paiva' size='4'></td>
					<td><input type='text' name='myyntiulos_kuukausi' value='$kuukausi' size='4'></td>
					<td><input type='text' name='myyntiulos_vuosi' value='$vuosi' size='4'></td>
					<td><input type='text' name='myyntiulos_tunti' size='4'></td>
					<td><input type='text' name='myyntiulos_minuutti' size='4'></td>
					<input type='hidden' name='tee' value='erittele'>
					<input type='hidden' name='kuka' value='$kuka'>
					<input type='hidden' name='yhtio' value='$yhtio'>
					<input type='hidden' name='myyja' value='yes' >
					<input type='hidden' name='toim' value='MYYNTI'>
				</tr>
				<tr><td class='back'><input type='submit' value='kirjaa'></td></tr>
				
				</form>
				</table>";
				
			//KÄYTTÄJÄN 20 viimeisintä kirjausta
			
			$query = "	SELECT * 
						FROM kulunvalvonta
						WHERE yhtio='$yhtio' and kuka='$kuka' and (suunta = 'I' OR suunta = 'O')
						ORDER BY aika 
						LIMIT 20";
			$result = mysql_query($query);
			echo "<br>oliskohan yhtiö tässä mikä: $yhtio";
			echo "<table>
				<tr><th width='200'>Sisäänkirjautumisaika</th><th width='200'>Uloskirjautumisaika</th></tr>";
				echo "<tr>";
				while($vanhat_kirjautumiset = mysql_fetch_array($result)) {
					if ($vanhat_kirjautumiset['suunta'] == 'I') {
						if($vanhat_kirjautumiset[tunnus] == $muokkaasisaantunnus) {
							echo "<td><form action='$PHP_SELF' method='post' name='muokkaus'>
									<input type='text' name='sisaankirjautumisaika' value='$vanhat_kirjautumiset[aika]'>
									<input type='hidden' name='vanhasisaankirjautumisaika' value='$vanhat_kirjautumiset[aika]'>
								  </td>";
							
						}
						else {
							echo "	<td>
								$vanhat_kirjautumiset[aika]
								<form action='$PHP_SELF' method='post' name='muokkaa'>
								<input type='hidden' name='muokkaasisaantunnus' value='$vanhat_kirjautumiset[tunnus]'>
								</td>";
						}
					}
					if($vanhat_kirjautumiset['suunta'] == 'O') {
						if($vanhat_kirjautumiset[tunnus] == $muokkaaulostunnus) {
							echo "	<td>
									<input type='text' name='uloskirjautumisaika' value='$vanhat_kirjautumiset[aika]'>
									</td><td>
									<input type='hidden' name='vanhauloskirjautumisaika' value='$vanhat_kirjautumiset[aika]'>
									<input type='hidden' name='toim' value='MYYNTI'>
									<input type='hidden' name='myynti' value='true'>
									<input type='hidden' name='kuka' value='$kuka'>
									<input type='hidden' name='yhtio' value='$yhtio'>
									<input type='hidden' name='tee' value='tallenna'									
									<input type='submit' value='tallenna'>
								  </form></td>";
								echo "</tr><tr>";
						}
						else {
							echo "	<td>$vanhat_kirjautumiset[aika]</td>
								<td>
								<input type='hidden' name='muokkaaulostunnus' value='$vanhat_kirjautumiset[tunnus]'>
								<input type='hidden' name='toim' value='MYYNTI'>
								<input type='hidden' name='myynti' value='true'>
								<input type='hidden' name='tee' value='napit'>
								<input type='hidden' name='kuka' value='$kuka'>
								<input type='hidden' name='yhtio' value='$yhtio'>
								<input class='back' type='submit' value='muokkaa'>
								</form></td>";
						echo "</tr><tr>";
						
						}
					}
				
				}
			echo "</table>";
			}
			// jos ollaan klikattu jotain kolmesta IN nappulasta suunta I muuten O
			if (($normin != "" or $matkain != "" or $sickin != "") AND $myynti == "") {
				$suunta = "I";
				echo "<font class='message'>".t("Olet kirjautunut sisään kello ")."";
				echo date('H:i') . "</font><br>";
				echo "<meta http-equiv='refresh' content='3'>";
				$tee = '';
			}
			if($normout != "") {
			$suunta = "O";
			$tee = 'erittele';  //erittelyyn
			}
			
			
			// katotaan mikä on kirjauksen tyyppi
			$tyyppi = "";
			if ($lomain  != "" or $lomaout  != "") $tyyppi = "L"; // loma
			if ($sickin  != "" or $sickout  != "") $tyyppi = "S"; // sairaus
			if ($matkain != "" or $matkaout != "") $tyyppi = "M"; // matka

			// lisätään tapahtuma kantaan jos ei olla myyntimiehiä (myyntimiehet on oma lukunsa)
			if($myynti == "") {
			$query  = "INSERT INTO kulunvalvonta (yhtio, kuka, aika, suunta, tyyppi) 
					   VALUES('$rivi[yhtio]','$kuka',now(),'$suunta','$tyyppi')";
			$result = mysql_query($query) or pupe_error($query);
			}
		}
		else 
			$tee = "";
	
    }
	

	//ERITTELYLISTAUS
	if($tee == 'erittele') {
		//käyttäjän tiedot kannasta
		$query  = "	SELECT * 
					FROM kuka 
					WHERE yhtio = '$yhtio' and kuka='$kuka'";
		$result = mysql_query($query) or pupe_error($query);
		
		// fetchataan rivi
		$rivi = mysql_fetch_array($result);
	
		//JOS ulosmenoaikaa ja sisaanmenoaikaa ei ole asetettu (eli ei olla myyntimiehiä) niin haetaan ne kannasta
		if ($myyja != 'yes' ){
		//otetaan kannasta sisaan ja uloskirjautumisajat
			$query  = "	SELECT unix_timestamp(aika) 
						FROM kulunvalvonta 
						WHERE yhtio = '$rivi[yhtio]' and kuka='$rivi[kuka]' and (suunta = 'I' OR suunta = 'O') and (tyyppi = '' or tyyppi = 'MYY')
						ORDER BY aika DESC LIMIT 2";	
						
			$result = mysql_query($query) or pupe_error($query);
			
			$ulosaika = @mysql_result($result,0);
			$sisaanaika = @mysql_result($result,1);
		
		}
		else {
			//Ollaan siis myyntimiehiä, tarkistetaan onko syotetyt vain numeroita väliltä 0-24 ja 0-59 ja  luodaan sopivat unix_timestampit näistä syötetyistä kellonajoista.
			
			if ((ctype_digit($myyntisisaan_tunti) and ctype_digit($myyntisisaan_minuutti) and ctype_digit($myyntiulos_tunti) and ctype_digit($myyntiulos_minuutti)) and ($myyntisisaan_tunti <= 24 and $myyntisisaantunti >= 0) and ($myyntisisaan_minuutti <= 59 and $myyntisisaan_minuutti >= 0) and ($myyntiulos_tunti <= 24 and $myyntiulostunti >= 0) and ($myyntiulos_minuutti <= 59 and $myyntiulos_minuutti >= 0)) {
				
					//luodaan unix_timestampit
					
				   	$sisaanaika =  mktime($myyntisisaan_tunti, $myyntisisaan_minuutti, 0 , $myyntisisaan_kuukausi,$myyntisisaan_paiva, $myyntisisaan_vuosi);
				   	$ulosaika = mktime($myyntiulos_tunti, $myyntiulos_minuutti, 0 , $myyntisisaan_kuukausi,$myyntisisaan_paiva, $myyntisisaan_vuosi);
					$aikanyt = mktime(date('H,i,s,m,d,Y'));
			
					// haetaan käyttäjän vika kirjaus
					$query  = " SELECT unix_timestamp(aika) 
								FROM kulunvalvonta 
								WHERE yhtio = '$rivi[yhtio]' and kuka='$rivi[kuka]' and suunta='O' order by aika desc limit 1";		
					$result = mysql_query($query) or pupe_error($query);
					$vikakirjaus   = @mysql_result($result,0);
				
					//tarkistetaan vielä ettei yritetä sorkkia tulevaisuutta ja ettei kannassa ole jo tallennusta tälle ajalle
					if ($sisaanaika < $aikanyt AND $ulosaika < $aikanyt AND $vikakirjaus <= $sisaanaika) {
								   
		   	      		 //tallennetaan nämä kantaan...
		   	      		 
		   	      		 $myyjasisaan = date('Y-m-d H:i:s',$sisaanaika);
		   	      		 $myyjaulos = date('Y-m-d H:i:s',$ulosaika);
				  		 $tyyppi = 'MYY';
		   	      		 //työn aloitus
		   	      		 $query  = "INSERT INTO kulunvalvonta (yhtio, kuka, aika, suunta, tyyppi) 
		   	      		 		   VALUES('$rivi[yhtio]','$kuka', '$myyjasisaan','I','$tyyppi')";
		   	      		 $result = mysql_query($query) or pupe_error($query);
           	      		 
		   	      		 //ja lopetus
		   	      		 $query  = "INSERT INTO kulunvalvonta (yhtio, kuka, aika, suunta, tyyppi) 
		   	      		 		   VALUES('$rivi[yhtio]','$kuka', '$myyjaulos','O','$tyyppi')";
		   	      		 $result = mysql_query($query) or pupe_error($query);
				   	}
					else {
			
						echo "<font class='error'>" . t("VIRHE,<BR>KOITIT SYÖTTÄÄ TULEVAISUUDESSA OLEVAN AJANKOHDAN<BR>TAI OLET JO SYÖTTÄNYT AJANKOHDALLE MERKINNÄN!") . "</font>";
						$tee = "";
						$myyja = "yes";
						$keskeyta = "true";
						$toim = "MYYNTI";
					}
			}
			else {
		
				echo "<font class='error'>" . t("VIRHE: SYÖTÄ TUNTEIHIN JA MINUUTTEIHIN VAIN NUMEROITA!") . "</font>";
				$tee = "";
				$myyja = "yes";
				$keskeyta = "true";
				$toim = "MYYNTI";
			}
		
		}
		
		
		//jos on keskeytetty (syötetty väärin) niin ei printata erittelyä
		if ($keskeyta != 'true') {
			
		
		
		//JOS TYOAIKAA ON JO LASKETTU (ERITTELYITA LISATTY), ESITETAAN ERITTELEMÄTTÖMIEN TUNTIEN MÄÄRÄ, MUUTEN (EKALLA)
		//KERRALLA NÄYTETÄÄN TYÖAIKA
		
		if (isset($tyoaika) && $tyoaika > 0) {
			echo  "<br /><font class='message'>".t("Erittelemättä: "). floor($tyoaika/60) . " tuntia " . $tyoaika%60 . " minuuttia<br />".t("Erittele:")." </font><br>";
		}
		else {
			if(!isset($tyoaika)) {
				$tyoaika = round((($ulosaika-$sisaanaika)/60));  //tyoaika minuutteina kun kirjaudutaan sisaan
				//$tyoaika = round(500); //debuggaus
				echo "<br><font class='message'>" . t("Työtä tehty ") . floor($tyoaika/60) . " tuntia " . $tyoaika%60 . " minuuttia<br>" . t("ole hyvä ja erittele:") . "</font>";
			}
						
		}
		if ((round($tyoaika,0)) <= 0)  {
			echo "<br><font class='message'>Tunnit eritelty, voit nyt sulkea selaimen tai jatkaa työskentelyä.</font><br>";
		}
	
		
		
		
		//TULOSTETAAN ERITTELYTAULUKKO
		echo "
		<table>
		<tr><th width='280'>Projekti</th><th>Työn laatu</th><th>Tunnit</th><th>Minuutit</th></tr>";
		
	
		//KYSELLÄÄN JO ERITELLYT RIVIT TIETOKANNASTA
		$query = "	SELECT * 
					FROM kulunvalvonta
					WHERE aika='".date('Y-m-d H:i:s',$sisaanaika)."' and kuka='$rivi[kuka]' and suunta!='I' and suunta!='O'";
	
		$result_eritellyt = mysql_query($query) or pupe_error($query);
		
		while($eritellyt = mysql_fetch_array($result_eritellyt)) {
	
			if($eritellyt["tunnus"] == $muokkaa) {
			
				//SELVITETÄÄN otunnusta ja laatua vastaava nimi ja asetetaan se oletukseksi selectiin 
				//(tai jos ei löydy niin laitetaan vain "valitse")
				
				$query = "	SELECT nimi, nimitark
							FROM lasku
							WHERE tunnus='$otunnus' LIMIT 1";
							
				$result = mysql_query($query) or pupe_error($query);
				
				$oletusprojekti = @mysql_result($result,0);
				
				if($oletusprojekti == "") 
					$oletusprojekti = "valitse";
				if ($tyyppi == "") {
						$oletuslaatu = "valitse";
				
				}
				else {
					$query = "	SELECT selitetark
								FROM avainsana
								WHERE selite='$tyyppi'";
							
							$result = mysql_query($query) or pupe_error($query);
				
							$oletuslaatu = @mysql_result($result,0);
				}
				
				
				echo "
				<tr>
					<form name='tallenna' action='$PHP_SELF' method='post'>
					  <td>
						  <select name='otunnus'>
						  <option selected=yes value='$otunnus'>$oletusprojekti</option>";
				

				//luetaan kannasta kaikkien projektien nimet selectlistaan
		   		$query = "	SELECT *
		   					FROM lasku
		   					WHERE yhtio = '$kukarow[yhtio]' and lasku.tila = 'R' and alatila!='X'";

		   		$result = mysql_query($query) or pupe_error($query);			

		   		while($prow = mysql_fetch_array($result)) {
		   			echo "<option value='$prow[tunnus]'>$prow[nimi] $prow[nimitark]</option>";
		   		}			
		   	
				echo "</select></td>";

		   		//avainsanat (tyon laatu) omaan selectlistaan
		   		$query = "	SELECT * 
		   					FROM avainsana
		   				  	WHERE yhtio='$kukarow[yhtio]' and laji = 'KVERITTELY'";

				$result = mysql_query($query) or pupe_error($query);

				echo "<td><select name='laatu'>
					  <option selected=yes value='$tyyppi'>$oletuslaatu</option>";

		   		while($kvrow = mysql_fetch_array($result)) {
		   			echo "<option value=$kvrow[selite]>$kvrow[selitetark]</option>";
		   		}
				echo "</select></td>";
				
				//selvitetään minuuttimääristä tunnit ja minuutit lomaketta varten
				$hours = floor(($eritellyt[minuuttimaara]/60));
				$minutes = $eritellyt[minuuttimaara]%60;
				

				echo "<td><input type='text' name='hours' size='2' value='$hours' /></td>";
				echo "<td><input type='text' name='minutes' size='2' value='$minutes'></td>";
				
				echo "<td class='back'><input type='submit' value='tallenna'>
						<input type='hidden' name='tee' value='tallenna'>
						<input type='hidden' name='tunnus' value='$eritellyt[tunnus]'>
						<input type='hidden' name='kuka' value='$kuka'>
						<input type='hidden' name='yhtio' value='$yhtio'>
						<input type='hidden' name='tyoaika' value='$tyoaika'>
						<input type='hidden' name='muokatun_vanhat_tunnit' value='$hours'>
						<input type='hidden' name='muokatun_vanhat_minuutit' value='$minutes'>";
				echo "</td>
					</form>
				</tr>";
			}
			
			else {
				
				//selvitetaan projektin nimi otunnuksen avulla
				$query = "	SELECT nimi, nimitark
							FROM lasku
							WHERE tunnus='$eritellyt[otunnus]' LIMIT 1";

				$result = mysql_query($query) or pupe_error($query);

				$projekti = @mysql_result($result,0);
					
				//selvitetään laadun tarkenne, jos annettu on tyhjä, niin ei turhaan haeta kannasta paskaa
				if ($eritellyt[tyyppi] == "") {
						$tyypin_tarkenne = "";
				}
				else {
				$query = "SELECT selitetark
							FROM avainsana
							WHERE selite='$eritellyt[tyyppi]'";
							
				$result = mysql_query($query);
				
				$tyypin_tarkenne = @mysql_result($result,0);
				}
				
				//selvitetään minuuttimääristä tunnit ja minuutit
				$hours = floor(($eritellyt[minuuttimaara]/60));
				$minutes = $eritellyt[minuuttimaara]%60;
				
				
				echo "
				<tr>
					<td>$projekti</td>
					<td>$tyypin_tarkenne</td>
					<td>$hours</td>
					<td>$minutes</td>
					
					<td class='back'>
						<form name='muokkaa' action='$PHP_SELF' method='post'>
							<input type='submit' value='muokkaa'>
							<input type='hidden' name='tee' value='erittele'>
							<input type='hidden' name='muokkaa' value='$eritellyt[tunnus]'>
							<input type='hidden' name='kuka' value='$rivi[kuka]'>
							<input type='hidden' name='yhtio' value='$rivi[yhtio]'>
							<input type='hidden' name='otunnus' value='$eritellyt[otunnus]'>
							<input type='hidden' name='tyyppi' value='$eritellyt[tyyppi]'>
							<input type='hidden' name='tyoaika' value='$tyoaika'>
						</form>
					</td>
				</tr>";
				
			}
		}
			  
	
			
	    	//LISÄÄ ERITTELYYN

		   	  echo "<tr><th>Lisää uusi</th><th>Työn laatu</th><th>Tunnit</th><th>Minuutit</th></tr>
			  		<tr>
			  	  <td>
			  			<form name='erittely' action='$PHP_SELF' method='post' autocomplete='off'>
			  			<select name='otunnus'>
			  		  	<option selected=yes value=''>valitse</option>";
			  		  	
             
             
			  //luetaan kannasta kaikkien projektien nimet selectlistaan
		   	  $query = "SELECT *
		   	  			FROM lasku
		   	  			WHERE yhtio = '$kukarow[yhtio]' and lasku.tila = 'R' and alatila!='X'
						ORDER BY tunnusnippu DESC";		
             
		   	  $result = mysql_query($query) or pupe_error($query);			
             
		   	  while($prow = mysql_fetch_array($result)) {
		   	  	echo "<option value='$prow[tunnus]'>$prow[tunnusnippu] $prow[nimi]</option>";
		   	  }			
		   	  echo "</select></td>";
             
		   	  //avainsanat (tyon laatu) omaan selectlistaan
		   	  $query = "SELECT * 
		   	  			FROM avainsana
		   	  		  	WHERE yhtio='$kukarow[yhtio]' and laji = 'KVERITTELY'";
             
			  $result = mysql_query($query) or pupe_error($query);
             
			  echo "<td>
			  		<select name='laatu'>
			  	  	<option selected=yes value=''>valitse</option>";
             
		   	  while($kvrow = mysql_fetch_array($result)) {
		   	  	echo "<option value=$kvrow[selite]>$kvrow[selitetark]</option>";
		   	  }
	   		  echo "	</select>
			  	  </td>";
			  echo "
			  	<td><input type='text' name='hours' size='2' /></td>
			  	<td><input type='text' name='minutes' size='2' /></td>
			  	<td class='back'>
			  		<input type='submit' value='Lisaa'>
			  		<input type='hidden' name='kuka' value='$rivi[kuka]'>
					<input type='hidden' name='yhtio' value='$rivi[yhtio]'>
			  		<input type='hidden' name='sisaanaika' value='$sisaanaika'>
			  		<input type='hidden' name='tyoaika' value='$tyoaika'>
			  		<input type='hidden' name='tee' value='lisaa'>";
				if($toim == 'MYYNTI')
					echo "<input type='hidden' name='toim' value='MYYNTI'>";
			  	echo "</td>
			  	</tr>";
         	
             
			  //nappuloiden tulostus
			  echo "
			  	<tr>
             
			  	<td>&nbsp;</td><td>&nbsp;</td>
			  	</tr>";			
             echo "</form>";
					  
			  
			  
			  echo "</table>"; //koko taulukon lopettava
		}	//IF($KESKEYTA != 'true') SULKU
			// kirjotetaan vähä feedbäkkiä käyttäjälle ruudulle
		 	 echo "<br><table>";
		 	 echo "<tr><th>".t("Käyttäjä")."</th><td>$rivi[nimi]</td></tr>";
		 	 echo "<tr><th>".t("Aika")."</th><td>".date("Y-m-d H:i:s")."</td></tr>";
		 	 echo "<tr><th>".t("Tyyppi")."</th><td>$normin $normout $sickin $sickout $matkain $matkaout $lomaout</td></tr>";
		 	 echo "</table>";
		
	}
	
	
	//jos ei muuta, niin pistetään alkuvalikkoa kehiin..
	if ($tee == "") {
		
		if ($_GET['toim'] == 'MYYNTI') {
			echo "<input type='hidden' name='toim' value='MYYNTI'>";
		}
		
		if ($toim == 'MYYNTI') {
			echo "<meta http-equiv='refresh' content='5;URL=kulunvalvonta.php?toim=MYYNTI'>";
		}
		else {
				
		echo "<br>";
		echo "<font class='head'>".t("Laita kortti lukijaan")."</font><br>";
		echo "<br>";
		echo "<form name='lukija' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='viivakoodi'>";
		echo "<input size='50' type='password' name='viivakoodi' value=''>";
		
		echo "</form>";		

		// kursorinohjausta
		$formi  = "lukija";
		$kentta = "viivakoodi";
		}
			
	}
	
	require ("inc/footer.inc");

?>