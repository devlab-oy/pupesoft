<?php

if (!$fd = fopen($filename, "r")) die("Filen '$filename' avaus epäonnistui!");

$edytunnus = "jokuarvovaan, että mennään ekalla luupilla jo sisään";

while (!feof ($fd)) {

	$rivi = fgets($fd);

	$poista	  	= array("\"", "'", "\\");
	$rivi	  	= str_replace($poista,"",$rivi);
	$rivi	  	= explode("\t", trim($rivi));

	$ytunnus	= trim($rivi[0]);
	$tuoteno	= trim($rivi[1]);
	$kpl	  	= str_replace(",", ".", $rivi[2]);
	$alv 		= str_replace(",", ".", $rivi[3]);
	$toimaika 	= $rivi[4];
	$hinta		= str_replace(",", ".", $rivi[5]);
	$ale	  	= str_replace(",", ".", $rivi[6]);
	$netto 	  	= strtoupper(trim($rivi[7]));
	$var  		= strtoupper(trim($rivi[8]));
	$kommentti	= trim($rivi[9]);

	if ($ytunnus != "" and $tuoteno != "") {

 		// asiakas vaihtuu, uusi otsikko
 		if ($ytunnus != $edytunnus) {
 
 			$query = "	UPDATE kuka
						SET kesken=0
						WHERE session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow['kesken'] 	= 0;
			$tilausnumero 		= 0;

			$query = "	SELECT tunnus 
 						FROM asiakas
 						WHERE yhtio = '$kukarow[yhtio]' and 
 						ytunnus = '$ytunnus' 
 						ORDER BY tunnus
 						LIMIT 1";
 			$result = mysql_query($query) or die($query);
 
 			if (mysql_num_rows($result) == 0) {
 				die ("virheellinen asiakasnumero tiedostossa! $ytunnus");
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

 		$edytunnus = $ytunnus;

	}

}

fclose ($fd);

?>
