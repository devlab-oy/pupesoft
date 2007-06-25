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
 
 			$query = "	SELECT * 
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
 
 			// Lisätään otsikko
 			$query = "	INSERT into lasku SET
 						yhtio_nimi							= '$yhtiorow[nimi]',
 						yhtio_osoite						= '$yhtiorow[osoite]',
 						yhtio_postino						= '$yhtiorow[postino]',
 						yhtio_postitp						= '$yhtiorow[postitp]',
 						yhtio_maa							= '$yhtiorow[maa]',
 						yhtio_ovttunnus						= '$yhtiorow[ovttunnus]',
 						yhtio_kotipaikka					= '$yhtiorow[kotipaikka]',
 						alatila 		   					= '',
 						alv     		   					= '$arow[alv]',
 						chn	    		   					= '$arow[chn]',
 						comments 		   					= '',
 						kerayspvm 		   					=  now(),
 						ketjutus		   					= '$arow[ketjutus]',
 						laatija 		   					= '$kukarow[kuka]',
 						laskutusvkopv	   					= '$arow[laskutusvkopv]',
 						luontiaika		   					=  now(),
 						maa     		   					= '$arow[maa]',
 						maksuehto 		   					= '$arow[maksuehto]',
 						myyja 			   					= '$kukarow[myyja]',
 						nimi    		   					= '$arow[nimi]',
 						nimitark 		   					= '$arow[nimitark]',
 						osoite 			   					= '$arow[osoite]',
 						ovttunnus 		   					= '$arow[ovttunnus]',
 						postino 		   					= '$arow[postino]',
 						postitp 		   					= '$arow[postitp]',
 						tila    		   					= 'N',
 						tilausvahvistus    					= '$arow[tilausvahvistus]',
 						erikoisale		   					= '$arow[erikoisale]',
 						toim_maa		   					= '$arow[toim_maa]',
 						toim_nimi		   					= '$arow[toim_nimi]',
 						toim_nimitark	   					= '$arow[toim_nimitark]',
 						toim_osoite		   					= '$arow[toim_osoite]',
 						toim_ovttunnus 	   					= '$arow[toim_ovttunnus]',
 						toim_postino	   					= '$arow[toim_postino]',
 						toim_postitp	   					= '$arow[toim_postitp]',
 						toimaika 		   					=  now(),
 						kohdistettu 	   					= '',
 						toimitustapa 	   					= '$arow[toimitustapa]',
 						toimitusehto 	   					= '$arow[toimitusehto]',
 						verkkotunnus	   					= '$arow[verkkotunnus]',
 						valkoodi		   					= '$yhtiorow[valkoodi]',
 						liitostunnus       					= '$arow[tunnus]',
 						vienti			   					= '$arow[vienti]',
 						viesti 			   					= '',
 						sisviesti1         					= '$arow[sisviesti1]',
 						sisviesti2         					= '',
 						yhtio 			   					= '$kukarow[yhtio]',
 						ytunnus			   					= '$arow[ytunnus]',
 						viitetxt		   					= '',
 						aktiivinen_kuljetus					= '$arow[aktiivinen_kuljetus]',
 						kuljetusmuoto						= '$arow[kuljetusmuoto]',
 						kauppatapahtuman_luonne				= '$arow[kauppatapahtuman_luonne]',
 						maa_maara							= '$arow[maa_maara]'";
 			$result = mysql_query($query) or die($query);
 			$id = mysql_insert_id();
 		
 			echo "Tehtiin tilaus $id asiakkaalle $ytunnus<br>";

			$kukarow["kesken"] = $id;

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
