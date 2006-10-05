<?php

if ($tee == "file") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
		$timeparts = explode(" ",microtime());
		$starttime = $timeparts[1].substr($timeparts[0],1);

		list($name,$ext) = split("\.", $_FILES['userfile']['name']);

		if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV")) {
			die ("<font class='error'><br>".t("Ainoastaa .txt tai .csv tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0) {
			die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
		}

		$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

		// luetaan tiedosto alusta loppuun...
		$rivi = fgets($file, 4096);
		
		$tunnukset 		= "";
		$korjaaalvit 	= array();
		$monistettavat	= array();
		$suoraanlasku 	= array();

		while (!feof($file)) {

			$tila='';
			$valinta='';

			// luetaan rivi tiedostosta..
			$poista	  	= array("'", "\\", " ");
			$rivi	  	= str_replace($poista,"",$rivi);
			$rivi	  	= explode("\t", trim($rivi));
			
			$lasku = trim($rivi[0]);
			$alvik = trim($rivi[1]);
			$hyvit = trim($rivi[2]);
			$slask = trim($rivi[3]); 
																																																	
			
			$query = "	SELECT tunnus
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' 
						and laskunro= '$lasku'
						and tila	= 'U'
						and alatila	= 'X'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				
				$row = mysql_fetch_array($result);
				
				$tunnukset .= "$row[tunnus],";			
					
				if ($hyvit != '') {
					$monistettavat[$row["tunnus"]] = "HYVITA";
				}
				else {
					$monistettavat[$row["tunnus"]] = "MONISTA";
				}
				
				$korjaaalvit[$row["tunnus"]]  = $alvik;				
				$suoraanlasku[$row["tunnus"]] = $slask;
			}
						
			$rivi = fgets($file, 4096);
		} // end while eof
		
		$tunnukset	= substr($tunnukset,0,-1);
		$lasku 		= "";
		$alvik 		= "";
		$hyvit 		= "";
		$slask 		= "";

		fclose($file);
	}
	$tee = "ETSILASKU";
}

if ($tee == 'mikrotila') {
	echo "<font class='head'>".t("Lue monistettavia laskuja tiedostosta")."</font><hr>";
	
	echo "	<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='file'>
			<font class='message'>".t("Tiedostomuoto").":</font><br><br>
			<table border='0' cellpadding='3' cellspacing='2'>
			<tr><th colspan='7'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
			<tr>";
			
	echo "	<td>".t("Laskunro")."</td><td>".t("Korjaa alvit")."</td><td>".t("Hyvitä")."</td><td>".t("Suoraan laskutukseen")."</td>";
	
	echo "	</tr>
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
