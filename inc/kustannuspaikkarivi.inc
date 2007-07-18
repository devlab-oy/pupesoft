<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0; 
	}
	
	if (mysql_field_name($result, $i) == "tyyppi") { //kustannuspaikat
		
		$psel = $ksel = $osel = "";
		if ($trow[$i] == 'P') {
			$psel = "selected";
		}
		if ($trow[$i] == 'O') {     
			$osel = "selected"; 
		}
		if ($trow[$i] == 'K') {     
			$ksel = "selected"; 
		}

		$ulos = "<td><select name='$nimi'><option value='K' $ksel>".t("Kustannuspaikka")."";
		$ulos .= "<option value='O'" . $osel . ">".t("Kohde")."";
		$ulos .= "<option value='P'" . $psel . ">".t("Projekti")."";
                $ulos .= "</select></td>";
		$jatko = 0;
	}

 	if (mysql_field_name($result, $i) == "kaytossa") { //ei käytössä
	
		$sel = $osel = "";
		
		$osel = '';
		if ($trow[$i] == 'E') {
			$osel = "selected";
		}
		else {
			$sel = "selected";
		}
		
        $ulos = "<td><select name='$nimi'><option value='o' $sel>".t("Kyllä")."";
		$ulos .= "<option value='E' " . $osel . ">".t("Ei")."";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
?>
