<?php

	$date		= $year."-".sprintf('%02d',$month)."-".sprintf('%02d',$day);
	$dateloppu 	= $lyear."-".sprintf('%02d',$lmonth)."-".sprintf('%02d',$lday);
	
	$date2 		= $year.sprintf('%02d',$month).sprintf('%02d',$day);
	$dateloppu2 = $lyear.sprintf('%02d',$lmonth).sprintf('%02d',$lday);
	
	$mylmonth 	= sprintf('%02d',$lmonth);
	$mylday 	= sprintf('%02d',$lday); 
	
	//tarkistetaan, etta alku ja loppu ovat eri..
	if ($kello == $lkello && $date == $dateloppu) {
		echo "<br><br>".t("VIRHE: Alku- ja päättymisajankohta ovat samat")."!";
		exit;
	}
	if ($date2 > $dateloppu2) {
		echo "<br><br>".t("VIRHE: Päättymisjankohta on aikaisempi kuin alkamisajankohta")."!";
		exit;
	}
	
	//Tarkisetetaan päällekkäisyys konsernikohtaisesti
	$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);
	$konsernit = "";
	
	while ($row = mysql_fetch_array($result)) {	
		$konsernit .= " '".$row["yhtio"]."' ,";
	}		
	$konsernit = " and yhtio in (".substr($konsernit, 0, -1).") ";
	
	$query = "	select tunnus 
				from kalenteri
				where tapa    = '$toim' 
				and ((pvmloppu > '$year-$month-$day $kello' and pvmloppu < '$lyear-$mylmonth-$mylday $lkello') 
				or (pvmalku > '$year-$month-$day $kello' and pvmalku < '$lyear-$mylmonth-$mylday $lkello')
				or (pvmalku < '$year-$month-$day $kello' and pvmloppu > '$year-$month-$day $kello'))
				$konsernit";		
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) > 0) {
		echo "<br><br>".t("VIRHE: Päällekkäisiä tapahtumia")."!";
		exit;
	}
	
	if ($toim == "Sauna") {
		if ($kentta03 != '' && $kentta06 != '' && $kentta07 != '') {			
			$meili = "\n\n\n$kukarow[nimi] on varannut saunan ajalle:\n\n##################################################\n$day-$month-$year   Klo: $kello --> Klo: $lkello\n##################################################\n\n\n";
			$meili .= "Yhtiö:\n$kentta01\n";
			$meili .= "Osasto:\n$kentta02\n\n";
			$meili .= "Lisätiedot:\n$kentta05\n\n";
			$meili .= "Tilaisuus:\n$kentta03 $kentta04\n\n";
			$meili .= "Isäntä:\n$kentta06\n\n";
			$meili .= "Vieraat:\n$kentta07\n\n";
			$meili .= "Vieraslukumäärä:\n$kentta08\n\n\n";
			$meili .= "Ruokatoivomus:\n$kentta09\n";
			$meili .= "Juomatoivomus:\n$kentta10\n";
			
			$tulos = mail("$yhtiorow[varauskalenteri_email]", "Saunavaraus", $meili,"From: ".$kukarow["nimi"]."<".$kukarow["eposti"].">\nReply-To: ".$kukarow["nimi"]."<".$row["eposti"].">\n");			
		}
		else{
			echo "<br><br>Tarkista, ett&auml; kaikki tiedot on sy&ouml;tetty!";
			exit;
		}
	}
	if($toim == "Starcraft" || $toim == "Perämoottori 1" || $toim == "Perämoottori 2") {		
		$meili = "$kukarow[nimi] on varannut kohteen'".$toim."' ajalle:\n\n##################################################\n$day-$month-$year   Klo: $kello --> $lday-$lmonth-$lyear  Klo: $lkello\n##################################################\n\n\nViesti:\n$kentta05";
		$tulos = mail("$yhtiorow[varauskalenteri_email]", $toim, $meili,"From: ".$kukarow["nimi"]."<".$kukarow["eposti"].">\nReply-To: ".$kukarow["nimi"]."<".$row["eposti"].">\n");		
	}
	
	if($toim == "Kuorma-auto") {
		$meili = "$kukarow[nimi] on varannut kohteen '".$toim."' ajalle:\n\n##################################################\n$day-$month-$year   Klo: $kello --> $lday-$lmonth-$lyear  Klo: $lkello\n##################################################\n\n\nViesti:\n$kentta05";		
		$tulos = mail("$yhtiorow[varauskalenteri_email]", $toim, $meili,"From: ".$kukarow["nimi"]."<".$kukarow["eposti"].">\nReply-To: ".$kukarow["nimi"]."<".$row["eposti"].">\n");
	}	

	
	$query = "	INSERT into kalenteri SET 
				kuka 		= '$kukarow[kuka]',
				yhtio		= '$kukarow[yhtio]',		
				pvmalku		= '$year-$month-$day $kello',
				pvmloppu	= '$lyear-$lmonth-$lday $lkello',
				tapa 		= '$toim',
				kentta01	= '$kentta01',
				kentta02	= '$kentta02',
				kentta03	= '$kentta03',
				kentta04	= '$kentta04',
				kentta05	= '$kentta05',
				kentta06	= '$kentta06',
				kentta07	= '$kentta07',
				kentta08	= '$kentta08',
				kentta09	= '$kentta09',
				kentta10	= '$kentta10',
				tyyppi		= 'varauskalenteri'";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<br><br>".t("Tapahtuma lisätty varauskalenteriin")."!";
?>