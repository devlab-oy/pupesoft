<?php

echo "TARIC Veroperusteet\n\n";

require ("/var/www/html/pupesoft/inc/connect.inc");
require ("/var/www/html/pupesoft/inc/functions.inc");


for($a = 1; $a < count($argv); $a++) {

	system("rm -f /tmp/opt/taric/data/*");
	chdir("/tmp/");
	system("unzip -o $argv[$a]");
	chdir("/tmp/opt/taric/data/");
	
	$handle=opendir('/tmp/opt/taric/data/');

	while ($tiedosto = readdir($handle)) {
		
		if ($tiedosto != "." and $tiedosto != "..") {
			$file = fopen($tiedosto,"r") or die ("Ei aukea!\n");
			
			$filenimi = split("/",$tiedosto);
			$lukumaar = count($filenimi);
			$laji 	  = $filenimi[$lukumaar-1];
			
			if (strtolower(substr($laji,0,2)) == "tp" and strtolower(substr($laji,0,3)) != "tp_") {
				$laji = "tp.dat";
			}
			
			echo "FAILIA KÄSITELLÄÄN: $laji\n";
			
			// luetaan tiedosto alusta loppuun...
			$rivi = fgets($file, 4096);
			$lask = 0;
			
			while (!feof($file)) {
						
				//Lisätään toimenpide
				if ($laji == 'tp.dat' or $laji == 'tp_l.dat') {
					$toimenpide_id 			= trim(substr($rivi,0,8));
					$toimenpidetyyppi 		= trim(substr($rivi,8,3)); 
					$duty_expr_id 			= trim(substr($rivi,11,2));
					$nimike 				= trim(substr($rivi,13,10));
					$lisakoodin_tyyppi 		= trim(substr($rivi,23,1));
					$lisakoodi 				= trim(substr($rivi,24,3));
					$kiintionumero 			= trim(substr($rivi,27,6));
					$maa_ryhma 				= trim(substr($rivi,33,4));
					
					$voim_alkupvm 			= trim(substr($rivi,37,8));
					$vva					= substr($voim_alkupvm,0,4);		
					$kka					= substr($voim_alkupvm,4,2);
					$ppa					= substr($voim_alkupvm,6,2);
					
					
					$maara1 				= trim(substr($rivi,45,10));
					$maara 					= substr($maara1,0,7).".".substr($maara1,7,3);
					
					$rahayksikko 			= trim(substr($rivi,55,3));
					$paljousyksikko 		= trim(substr($rivi,58,3));
					$paljousyksikko_muunnin = trim(substr($rivi,61,1));
					
					$voim_loppupvm 			= trim(substr($rivi,62,8));
					
					if ($voim_loppupvm == "99999999") {
						$voim_loppupvm = "99991231";
					}
					
					$vvl					= substr($voim_loppupvm,0,4);		
					$kkl					= substr($voim_loppupvm,4,2);
					$ppl					= substr($voim_loppupvm,6,2);	
				
					$query = "	INSERT INTO taric_veroperusteet
								SET laji 				= 'tp.dat',
								toimenpide_id 			= '$toimenpide_id',
								toimenpidetyyppi 		= '$toimenpidetyyppi',
								duty_expr_id 			= '$duty_expr_id',
								nimike	 				= '$nimike',
								lisakoodin_tyyppi 		= '$lisakoodin_tyyppi',
								lisakoodi 				= '$lisakoodi',
								kiintionumero		 	= '$kiintionumero',
								maa_ryhma 				= '$maa_ryhma',
								voim_alkupvm 			= '$vva-$kka-$ppa',
								maara 					= '$maara',
								rahayksikko 			= '$rahayksikko',
								paljousyksikko 			= '$paljousyksikko',
								paljousyksikko_muunnin 	= '$paljousyksikko_muunnin',
								voim_loppupvm 			= '$vvl-$kkl-$ppl'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					if ($laji == 'tp_l.dat') {
						//echo "Lisättiin: $laji, $kala tietuetta. toimenpide_id='$toimenpide_id' and voim_alkupvm='$vva-$kka-$ppa' and duty_expr_id='$duty_expr_id'\n";
					}
				
				}
				
				//Muutetaan toimenpidettä
				if ($laji == 'tp_m.dat') {
					$toimenpide_id 			= trim(substr($rivi,0,8));
					$toimenpidetyyppi 		= trim(substr($rivi,8,3)); 
					$duty_expr_id 			= trim(substr($rivi,11,2));
					$nimike 				= trim(substr($rivi,13,10));
					$lisakoodin_tyyppi 		= trim(substr($rivi,23,1));
					$lisakoodi 				= trim(substr($rivi,24,3));
					$kiintionumero 			= trim(substr($rivi,27,6));
					$maa_ryhma 				= trim(substr($rivi,33,4));
					
					$voim_alkupvm 			= trim(substr($rivi,37,8));
					$vva					= substr($voim_alkupvm,0,4);		
					$kka					= substr($voim_alkupvm,4,2);
					$ppa					= substr($voim_alkupvm,6,2);
					
					
					$maara1 				= trim(substr($rivi,45,10));
					$maara 					= substr($maara1,0,7).".".substr($maara1,7,3);
					
					$rahayksikko 			= trim(substr($rivi,55,3));
					$paljousyksikko 		= trim(substr($rivi,58,3));
					$paljousyksikko_muunnin = trim(substr($rivi,61,1));
					
					$voim_loppupvm 			= trim(substr($rivi,62,8));
					
					if ($voim_loppupvm == "99999999") {
						$voim_loppupvm = "99991231";
					}
					
					$vvl					= substr($voim_loppupvm,0,4);		
					$kkl					= substr($voim_loppupvm,4,2);
					$ppl					= substr($voim_loppupvm,6,2);	
				
					$query = "	UPDATE taric_veroperusteet
								SET toimenpidetyyppi 	= '$toimenpidetyyppi',
								nimike 					= '$nimike',
								lisakoodin_tyyppi 		= '$lisakoodin_tyyppi',
								lisakoodi 				= '$lisakoodi',
								kiintionumero 			= '$kiintionumero',
								maa_ryhma 				= '$maa_ryhma',
								maara 					= '$maara',
								rahayksikko 			= '$rahayksikko',
								paljousyksikko 			= '$paljousyksikko',
								paljousyksikko_muunnin 	= '$paljousyksikko_muunnin',
								voim_loppupvm 			= '$vvl-$kkl-$ppl'
								WHERE laji 				= 'tp.dat' 
								and toimenpide_id 		= '$toimenpide_id'
								and voim_alkupvm 		= '$vva-$kka-$ppa'
								and duty_expr_id 		= '$duty_expr_id'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					if ($kala != '1') {
						//echo "Päivitettiin: $laji, $kala tietuetta. toimenpide_id='$toimenpide_id' and voim_alkupvm='$vva-$kka-$ppa' and duty_expr_id='$duty_expr_id'\n";	
					}			
					else {
						//echo "Päivitettiin: $laji, $kala tietuetta. toimenpide_id='$toimenpide_id' and voim_alkupvm='$vva-$kka-$ppa' and duty_expr_id='$duty_expr_id'\n";
					}
				
				}
				
				//Poistetaan toimenpide
				if ($laji == 'tp_p.dat') {
					
					$toimenpide_id 			= trim(substr($rivi,0,8));
					$duty_expr_id 			= trim(substr($rivi,11,2));
					
					$voim_alkupvm 			= trim(substr($rivi,37,8));
					$vva					= substr($voim_alkupvm,0,4);		
					$kka					= substr($voim_alkupvm,4,2);
					$ppa					= substr($voim_alkupvm,6,2);
				
					$query = "	DELETE FROM taric_veroperusteet
								WHERE laji 			= 'tp.dat' 
								and toimenpide_id 	= '$toimenpide_id'
								and voim_alkupvm 	= '$vva-$kka-$ppa'
								and duty_expr_id 	= '$duty_expr_id'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					if ($kala != '1') {
						//echo "Poistettiin: $laji, $kala tietuetta. toimenpide_id='$toimenpide_id' and voim_alkupvm='$vva-$kka-$ppa' and duty_expr_id='$duty_expr_id'\n";	
					}			
					else {
						//echo "Poistettiin: $laji, $kala tietuetta. toimenpide_id='$toimenpide_id' and voim_alkupvm='$vva-$kka-$ppa//' and duty_expr_id='$duty_expr_id'\n";
					}
								
				
				}
				
				//Lisätään nimiketietue
				if ($laji == 'nim.dat') {
					$nimike 				= trim(substr($rivi,0,10));
					
					$voim_alkupvm 			= trim(substr($rivi,10,8));
					$vva					= substr($voim_alkupvm,0,4);		
					$kka					= substr($voim_alkupvm,4,2);
					$ppa					= substr($voim_alkupvm,6,2);
					
					$voim_loppupvm 			= trim(substr($rivi,18,8));
					
					if ($voim_loppupvm == "99999999") {
						$voim_loppupvm = "99991231";
					}
										
					$vvl					= substr($voim_loppupvm,0,4);		
					$kkl					= substr($voim_loppupvm,4,2);
					$ppl					= substr($voim_loppupvm,6,2);
					
					
					
					$query = "	INSERT INTO taric_veroperusteet
								SET laji 		= 'nim.dat',					
								nimike 			= '$nimike',			
								voim_alkupvm 	= '$vva-$kka-$ppa',
								voim_loppupvm 	= '$vvl-$kkl-$ppl'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					// echo "Lisättiin: $laji, $kala tietuetta.\n";

				}
				
				
				//Lisätään poissuljettujen maiden tietue
				if ($laji == 'exc.dat') {
					$toimenpide_id 			= trim(substr($rivi,0,8));
					$poissuljettu_maa		= trim(substr($rivi,8,4));
					
					$query = "	INSERT INTO taric_veroperusteet
								SET laji 		= 'exc.dat',
								toimenpide_id 	= '$toimenpide_id',					
								maa_ryhma 		= '$poissuljettu_maa'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					// echo "Lisättiin: $laji, $kala tietuetta.\n";

				}
				
				
				//Lisätään toimenpiteiden alaviitteet
				if ($laji == 'alav.dat') {
					$toimenpide_id 			= trim(substr($rivi,0,8));
					$alaviitteen_tyyppi		= trim(substr($rivi,8,2));
					$alaviitenumero			= trim(substr($rivi,10,3));
					
					$query = "	INSERT INTO taric_veroperusteet
								SET laji 			= 'alav.dat',
								toimenpide_id 		= '$toimenpide_id',
								alaviitteen_tyyppi 	= '$alaviitteen_tyyppi',
								alaviitenumero 		= '$alaviitenumero'";
					$result = mysql_query($query) or pupe_error($query);
					$kala = mysql_affected_rows();	
					
					// echo "Lisättiin: $laji, $kala tietuetta.\n";
				}
				
				$lask++;
							
				$rivi = fgets($file, 4096);
			
			} // end while eof
			
			echo "\n\n$lask tietuetta käsitelty\n\n";
		}
	}
	
	fclose($file);
	
	system("rm -f /tmp/opt/taric/data/*");

}

?>