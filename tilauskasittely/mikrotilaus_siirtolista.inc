<?php

	if ($tee == "file") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {

			list($name,$ext) = split("\.", $_FILES['userfile']['name']);
	
			if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV")){
				die ("<font class='error'><br>".t("Ainoastaa .txt tai .csv tiedostot sallittuja")."!</font>");
			}
	
			if ($_FILES['userfile']['size']==0){
				die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
			}
	
			$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");
	
			// luetaan tiedosto alusta loppuun...
			$rivi = fgets($file, 4096);
	
			while (!feof($file)) {
				// luetaan rivi tiedostosta..
				$poista	  = array("'", "\\");
				$rivi	  = str_replace($poista,"",$rivi);
				$rivi	  = explode("\t", trim($rivi));
	
				$tuoteno  	= $rivi[0];
				$kpl  		= $rivi[1];
				$kommentti	= $rivi[2];
				$paikka		= $rivi[3];
							
							
				if (strtoupper($kpl) == "X") {
					//tilataan kaikki paikalla olevat, ei kuitenkaan varata niitä vielä
					$kpl  = 9999.99;
					$varataan_saldoa = "EI";
				}
								
				if($tuoteno != '' and $kpl > 0) {
					$query = "	SELECT *
								FROM tuote
								WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);		
									
					if(mysql_num_rows($result) == 1) {
	
						$trow = mysql_fetch_array($result);
	
						$tuoteno 	= $trow["tuoteno"];
						$toimaika 	= $laskurow["toimaika"];
						$kerayspvm 	= $laskurow["kerayspvm"];
						$varasto 	= $laskurow["varasto"];

						require('lisaarivi.inc');
							
						$tuoteno	= '';
						$kpl		= '';
						$var		= '';
						$hinta		= '';
						$netto		= '';
						$ale		= '';
						$rivitunnus	= '';
						$kommentti	= '';
						$kerayspvm	= '';
						$toimaika	= '';
						$paikka		= '';
						$alv		= 'X';				
					}
					else {
						echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
					}
				}
				$rivi = fgets($file, 4096);
			} // end while eof
	
			fclose($file);
			
			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
			$ale		= '';
			$rivitunnus	= '';
			$kommentti	= '';
			$kerayspvm	= '';
			$toimaika	= '';
			$paikka		= '';
			$alv		= 'X';
			$tee = "Y";
		}
		else {
			$tee = "Y";
		}
	}
	
	if ($tee == 'mikrotila') {
		
		echo "<font class='head'>".t("Siirtolista").":</font><hr><br>";
	
		echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='file'>
				<input type='hidden' name='toim' value='$toim'>	
				<font class='message'>".t("Tiedostomuoto").":</font><br><br>
	
				<table border='0' cellpadding='3' cellspacing='2'>
				<tr><th colspan='4'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("Kpl")."</td><td>".t("Kommentti")."</td><td>".t("Lähettävä varastopaikka")."</td></tr>
				</table>
	
				<br>
				<table>
				<tr>";	
		
		echo "	
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Läheta")."'></td>
				</tr>
				</table>
				</form>";
	}
?>