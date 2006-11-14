<?php

	//kaipaa sisään
	//$varasto = varaston tunnus josta myydään
	//$tilaus  = tilausnumero/tilausnumerot jota optimoidaan
	//palauttaa $kirjoitin jossa on parhaan kirjoittimen tunnnus
	

	//hateaan varaston tulostusalueet
	$query = "	SELECT *
				FROM varaston_tulostimet
				WHERE varasto	= '$varasto'
				and yhtio		= '$kukarow[yhtio]'
				ORDER BY prioriteetti, alkuhyllyalue";
	$turesult = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($turesult) > 0) {
	
		$pri 		= 0;
		$lis 		= "";
		$kirjoitin	= "";
		
		while ($turow = mysql_fetch_array($turesult)) {
			$lis .= "	sum(if(
						concat(lpad(upper('$turow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$turow[alkuhyllynro]')  ,5,'0')) <= concat(lpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0')) and
						concat(lpad(upper('$turow[loppuhyllyalue]') ,5,'0'),lpad(upper('$turow[loppuhyllynro]') ,5,'0')) >= concat(lpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0')),1,0)) '$turow[printteri]',";					
			$pri++;
		}
							
		$lis = substr($lis,0,-1);
		
		if ($lis != '') {
			$query = "	SELECT $lis
						FROM tilausrivi
						WHERE otunnus in ($tilaus)
						and yhtio = '$kukarow[yhtio]'";
			$tiresult = mysql_query($query) or pupe_error($query);
			$tirow = mysql_fetch_row($tiresult);
									
			for($ir = 0; $ir<$pri; $ir++) {
				if ($tirow[$ir] > 0) {
					$kirjoitin = mysql_field_name($tiresult,$ir);
					break;
				}
			}
		}
	}
	
?>