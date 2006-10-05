<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0; 
	}
        if (mysql_field_name($result, $i) == "tyyppi") { //kustannuspaikat
		if ($trow[$i] == 'P') {
			$psel = "selected";
		}
		if ($trow[$i] == 'O') {     
			$osel = "selected"; 
		}
		$ulos = "<td><select name='$nimi'><option value='K'>".t("Kustannuspaikka")."";
		$ulos .= "<option value='O'" . $osel . ">".t("Kohde")."";
		$ulos .= "<option value='P'" . $psel . ">".t("Projekti")."";
                $ulos .= "</select></td>";
		$jatko = 0;
	}
        if (mysql_field_name($result, $i) == "kaytossa") { //ei käytössä
		$osel = '';
                if ($trow[$i] == 'E') {
			$osel = "selected";
		}
        $ulos = "<td><select name='$nimi'><option value='o'>".t("Kyllä")."";
		$ulos .= "<option value='E' " . $osel . ">".t("Ei")."";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
?>
