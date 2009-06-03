<?php
	
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	//yhtiötä ei näytetä
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$ulos = "<td><select name='$nimi' onchange='submit();'>";
		
		$sel = array($trow[$i] => "SELECTED");
		
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TUOTEULK'";
		$sresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($sresult) == 0) {
			$ulos .= "<option value='nimitys_en' $sel[nimitys_en]>".t("Tuotteen englanninkielinen nimitys")."</option>";
			$ulos .= "<option value='nimitys_se' $sel[nimitys_se]>".t("Tuotteen ruotsinkielinen nimitys")."</option>";
			$ulos .= "<option value='nimitys_ee' $sel[nimitys_ee]>".t("Tuotteen eestinkielinen nimitys")."</option>";
			$ulos .= "<option value='nimitys_fr' $sel[nimitys_fr]>".t("Tuotteen ranskankielinen nimitys")."</option>";
			$ulos .= "<option value='tarratyyppi' $sel[tarratyyppi]>".t("Tuotteen tarratyyppi")."</option>";			
		}
		
		while ($srow = mysql_fetch_array($sresult)) {
			$ulos .= "<option value='$srow[0]' ".$sel[$srow[0]].">$srow[0] $srow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tuoteno") {
		if($lukitse_avaimeen != "") {
			$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
			$jatko = 0;
		}
		elseif(isset($alasveto[$i])) {
			$ulos = "<td>".$alasveto[$i]."</td>";
			$jatko = 0;
		}
	}
	
	if (mysql_field_name($result, $i) == "selite") {
			
		if($trow["laji"] == "tarratyyppi") {
			$sel=array($trow[$i] => "SELECTED");

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						AND laji = 'TARRATYYPPI'
						ORDER BY selite";
			$vresult = mysql_query($query) or pupe_error($query);
			
			if(mysql_num_rows($vresult)>0) {

				$ulos = "<td><select name='$nimi'>
								<option value=''>".t("Ei tilaustuotetarraa")."</option>";
				while ($vrow = mysql_fetch_array($vresult)) {
					$ulos .= "<option value='$vrow[selite]' ".$sel[$vrow["selite"]].">$vrow[selitetark]</option>";
				}
				$ulos .= "</select></td>";
			}
			
			$jatko = 0;
		}
		else {
			$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
		}
		
		$jatko = 0;
	}
	
	

?>
