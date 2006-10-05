<?php

if ($tee == "FILE") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
		$timeparts = explode(" ",microtime());
		$starttime = $timeparts[1].substr($timeparts[0],1);

		list($name,$ext) = split("\.", $_FILES['userfile']['name']);

		if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV" || strtoupper($ext) == "XLS")) {
			die ("<font class='error'><br>".t("Ainoastaa .txt, .xls tai .csv tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0 ) {
			die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
		}

		$file=fopen($_FILES['userfile']['tmp_name'],"r") or die ("".t("Tiedoston avaus epäonnistui")."!");

		// luetaan tiedosto alusta loppuun...
		//ekalla rivillä on otsikot joten luetaan se tässä pois
		$rivi = fgets($file, 4096);
		
		$rivi = fgets($file, 4096);

		$lask = 1;
		
		while (!feof($file)) {

			$tila='';
			$valinta='';

			// luetaan rivi tiedostosta..
			$poista	  = array("'", "\\","\"");
			$rivi	  = str_replace($poista,"",$rivi);
			$rivi	  = explode("\t", trim($rivi));
			
			$asiakas 	= $rivi[2];
			$yhtio   	= $rivi[3];
			
			$suunpvm 	= "";			
			$suun 		= "";
			if (strpos(trim($rivi[5]), "/") !== FALSE) {
				$suun = explode("/", trim($rivi[5]));
			}
			if (strpos(trim($rivi[5]), ".") !== FALSE) {
				$suun = explode(".", trim($rivi[5]));
			}
			
			if((int) $suun[2] < 1000) {
				$suun[2] += 2000;
			}
						
			if (checkdate(sprintf('%02d',$suun[1]),sprintf('%02d',$suun[0]),sprintf('%04d',$suun[2]))) {
				$suunpvm = sprintf('%04d',$suun[2])."-".sprintf('%02d',$suun[1])."-".sprintf('%02d',$suun[0]);
			}
													
			$kampanjat 	= $rivi[6];
		
			$kaytypvm 	= "";	
			$kayty		= "";		
			if (strpos(trim($rivi[5]), "/")  !== FALSE) {
				$kayty = explode("/", trim($rivi[7]));
			}
			if (strpos(trim($rivi[5]), ".")  !== FALSE) {
				$kayty = explode(".", trim($rivi[7]));
			}
			
			if((int) $kayty[2] < 1000) {
				$kayty[2] += 2000;
			}
									
			if (checkdate(sprintf('%02d',$kayty[1]),sprintf('%02d',$kayty[0]),sprintf('%04d',$kayty[2]))) {
				$kaytypvm = sprintf('%04d',$kayty[2])."-".sprintf('%02d',$kayty[1])."-".sprintf('%02d',$kayty[0]);
			}			
			
			$kilsat = $rivi[8];
			
			$lahto = $rivi[9];			
			
			if (strlen($lahto) == 1 or strlen($lahto) == 2) {
				$lahto = sprintf('%02d',$lahto).":00:00";	
			}
			if (strlen($lahto) == 5) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":00";
			}
			if (strlen($lahto) == 8) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":".sprintf('%02d',substr($lahto,6,2));
			}
			if (strlen($lahto) > 8) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":".sprintf('%02d',substr($lahto,6,2));
			}
			
			
			$paluu = $rivi[10];
			if (strlen($paluu) == 1 or strlen($paluu) == 2) {
				$paluu = sprintf('%02d',$paluu).":00:00";	
			}
			if (strlen($paluu) == 5) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":00";
			}
			if (strlen($paluu) == 8) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":".sprintf('%02d',substr($paluu,6,2));
			}
			if (strlen($paluu) > 8) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":".sprintf('%02d',substr($paluu,6,2));
			}
			
			$pvrahat	= $rivi[11];
			$kommentit	= $rivi[12];
			
			$virhe = 0;			
						
			if (trim($kaytypvm) == '' and trim($suunpvm) != '') {
				$kumpi = "Viikkosuunnitelma";
				$kpvm  = $suunpvm;
				if ($kommentit == '') {
					$kommentit = "$kukarow[nimi] ".t("suunnittelee käyvänsä asiakkaan luona")." $pvm.";				
				}
			}
			elseif (trim($kaytypvm) == '') {
				echo "VIRHE: Rivillä $lask. ".t("Päivämäärä puuttui tai se oli virheellinen")."!<br>";
				$virhe = 1;
			}			
			
			if (trim($kaytypvm) != '') {
				$kumpi = "Asiakaskäynti";
				$kpvm  = $kaytypvm;
			}
			elseif (trim($suunpvm) == '') {
				echo "VIRHE: Rivillä $lask. ".t("Päivämäärä puuttui tai se oli virheellinen")."!<br>";
				$virhe = 1;
			}			
			
			if ($lahto == '') {
				$lahto = "00:00:00";
			}
			if ($paluu == '') {
				$paluu = "00:00:00";
			}
				
			$query = "	SELECT yhtio
						FROM asiakas
						WHERE
						yhtio 			= '$yhtio'							
						and ytunnus 	= '$asiakas'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0) {
				echo t("VIRHE: Rivillä")." $lask. ".t("Asiakasta ei löydy")."!<br>";
				$virhe = 1;
			}
			
						
						
			if ($virhe == 0) {		
				$query = "	SELECT yhtio
							FROM kalenteri
							WHERE
							yhtio 			= '$yhtio'
							and kuka 		= '$kukarow[kuka]'
							and pvmalku 	= '$kpvm $lahto'
							and pvmloppu 	= '$kpvm $paluu'
							and asiakas 	= '$asiakas'
							and tapa 		= '$kumpi'
							and tyyppi		= 'kalenteri'";
				$result = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($result) == 0) {
					
					if ($kumpi == "Asiakaskäynti") {
						
						$query = "	SELECT tunnus
									FROM kalenteri
									WHERE
									yhtio 					= '$yhtio'
									and kuka 				= '$kukarow[kuka]'
									and left(pvmalku,10) 	= '$suunpvm'
									and left(pvmloppu,10) 	= '$suunpvm'
									and asiakas 			= '$asiakas'
									and tapa 				= 'Viikkosuunnitelma'
									and tyyppi				= 'kalenteri'";
						$result = mysql_query($query) or pupe_error($query);
						
						if (mysql_num_rows($result) != 0) { 
							
							$prow = mysql_fetch_array($result);
							
							$query = "	UPDATE kalenteri
										SET										
										tyyppi 		= 'memo'
										WHERE yhtio = '$yhtio'
										and tunnus 	= '$prow[tunnus]'";
							$result = mysql_query($query) or pupe_error($query);
							
						
						}			
					}
															
					$query = "	INSERT INTO kalenteri
								SET
								yhtio 		= '$yhtio',
								kuka 		= '$kukarow[kuka]',
								pvmalku 	= '$kpvm $lahto',
								pvmloppu 	= '$kpvm $paluu',
								asiakas 	= '$asiakas',
								kentta01 	= '$kommentit',
								tapa 		= '$kumpi',
								kentta02	= '$kampanjat',
								kentta03	= '$kilsat',
								kentta04	= '$pvrahat',
								kokopaiva	= 'x',
								tyyppi 		= 'kalenteri'";
					$result = mysql_query($query) or pupe_error($query);
				}
				elseif ($kumpi == "Asiakaskäynti") {
					$query = "	UPDATE kalenteri
								SET
								yhtio 		= '$yhtio',
								kuka 		= '$kukarow[kuka]',
								pvmalku 	= '$kpvm $lahto',
								pvmloppu 	= '$kpvm $paluu',
								asiakas 	= '$asiakas',
								kentta01 	= '$kommentit',
								tapa 		= '$kumpi',
								kentta02	= '$kampanjat',
								kentta03	= '$kilsat',
								kentta04	= '$pvrahat',
								tyyppi 		= 'kalenteri'
								WHERE yhtio = '$yhtio'
								and kuka 	= '$kukarow[kuka]'
								and pvmalku = '$kpvm $lahto'
								and pvmloppu= '$kpvm $paluu'
								and asiakas = '$asiakas'
								and tapa 	= '$kumpi'
								and tyyppi	= 'kalenteri'";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					echo "".t("VIRHE: Rivillä")." $lask. ".t("Tieto on jo viikkosuunnitelmassasi")."!<br>";	
				}
			}											
		
			$lask++;
			
			$rivi = fgets($file, 4096);
		} // end while eof
		
		if ($kumpi != '') {
					
			$kaladate = explode('-', $suunpvm);
			$kausi = $kaladate[0].sprintf('%02d',date("W", mktime(0, 0, 0, $kaladate[1], $kaladate[2], $kaladate[0])));
			
			$vstk = $kumpi;
			
			if ($vstk == 'Viikkosuunnitelma') {
				$sel1 = "CHECKED";
			}
			if ($vstk == 'Asiakaskäynti') {
				$sel2 = "CHECKED";
			}
		}



		fclose($file);
		
		$tee = "";
		
	}
	else {
		$tee = "";
	}
}

if ($tee == 'VALITSE_TIEDOSTO') {

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='FILE'>
			<input type='hidden' name='vaiht' value='$vaiht'>
			<input type='hidden' name='kausi' value='$kausi'>
			<input type='hidden' name='vstk' value='$vstk'>
			<font class='message'>".t("Tiedostomuoto").":</font><br><br>";
	
	
	echo "	<table>
			<tr><th colspan='5'>".t("Suunnitelma")."</th><th colspan='7'>".t("Toteutuneet")."</th></tr>
			<tr><td>".t("Paikka")."</td><td>".t("Asiakas")."</td><td>".t("Yhtiöt")."</td><td>".t("Nimi")."</td><td>".t("Pvm")."</td>				
			<td>".t("Kampanjat")."</td><td>".t("PvmKäyty")."</td><td>".t("Km")."</td><td>".t("Lähtö")."</td><td>".t("Paluu")."</td><td>".t("PvRaha")."</td><td>".t("Kommentit")."</td>				
			</tr>
			</table>";
			
			
	echo "	<br>
			<table>										
			<tr>
			<th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("Läheta")."'></td>
			</tr>
			</table>
			</form>";
}
