<?php

if (!$fd = fopen($filename, "r")) die("Filen '$filename' avaus epäonnistui!");

$edytunnus = "jokuarvovaan, että mennään ekalla luupilla jo sisään";

while (!feof ($fd)) {

	$rivi = fgets($fd);

	$poista	  	= array("\"", "'", "\\");
	$rivi	  	= str_replace($poista,"",$rivi);
	$rivi	  	= explode("\t", trim($rivi));

	$ytunnus		= trim($rivi[0]);
	$toim_ovttunnus	= trim($rivi[1]);
	$tuoteno		= trim($rivi[2]);
	$kpl	  		= str_replace(",", ".", $rivi[3]);
	$alv 			= str_replace(",", ".", $rivi[4]);
	$toimaika 		= $rivi[5];
	$hinta			= str_replace(",", ".", $rivi[6]);
	$ale	  		= str_replace(",", ".", $rivi[7]);
	$netto 	  		= strtoupper(trim($rivi[8]));
	$var  			= strtoupper(trim($rivi[9]));
	$kommentti		= trim($rivi[10]);
	

	$tarkytunnus = $ytunnus.$toim_ovttunnus;
	
	if ($ytunnus != "" and $tuoteno != "") {

 		// asiakas vaihtuu, uusi otsikko
 		if ($tarkytunnus != $edytunnus) {
 
 			$query = "	UPDATE kuka
						SET kesken=0
						WHERE session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow['kesken'] 	= 0;
			$tilausnumero 		= 0;

			if ($toim_ovttunnus != '') {
				$toim_lis = " and toim_ovttunnus='$toim_ovttunnus' ";
			}
			else {
				$toim_lis = "";
			}

			$query = "	SELECT tunnus 
 						FROM asiakas
 						WHERE yhtio = '$kukarow[yhtio]' 
						and ytunnus = '$ytunnus' 
						$toim_lis
 						ORDER BY tunnus
 						LIMIT 1";
 			$result = mysql_query($query) or die($query);
 
 			if (mysql_num_rows($result) == 0) {
 				die ("virheellinen asiakasnumero tiedostossa! ytunnus ='$ytunnus' $toim_lis");
 			}
 			$arow = mysql_fetch_array($result);
 			
			// Luodaan uusi myyntitilausotsikko
 			require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
						
			$id = luo_myyntitilausotsikko($arow["tunnus"]);
 		
 			echo "Tehtiin tilaus $id asiakkaalle $ytunnus<br>";

 			// haetaan tilauksen tiedot
 			$query    = "select * from lasku where tunnus='$id' and yhtio='$kukarow[yhtio]'";
 			$result   = mysql_query($query) or die($query);
 			$laskurow = mysql_fetch_array($result);		
 		}
 
 		$query = "	SELECT *
 					FROM tuote
 					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
 		$rarresult = mysql_query($query) or pupe_error($query);
 
 		if (mysql_num_rows($rarresult) == 1) {
 			$trow = mysql_fetch_array($rarresult);
 
 			if ($hinta == 0) {
 				$hinta = "";
 			}
 
 			//lisätään rivi
 			require ("lisaarivi.inc");
 		}

 		$edytunnus = $tarkytunnus;

	}

}

fclose ($fd);

?>
