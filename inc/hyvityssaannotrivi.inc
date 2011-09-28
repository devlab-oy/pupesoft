<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tuote_kentta") {
	
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value=''>".t("Ei Valintaa")."</option>"; 		
		$ulos .= "<option value='aleryhma' $sel[aleryhma]>".t("aleryhma")."</option>"; 			
		$ulos .= "<option value='alv' $sel[alv]>".t("alv")."</option>"; 				
		$ulos .= "<option value='eankoodi' $sel[eankoodi]>".t("eankoodi")."</option>"; 			
		$ulos .= "<option value='ei_saldoa' $sel[ei_saldoa]>".t("ei_saldoa")."</option>"; 			
		$ulos .= "<option value='halytysraja' $sel[halytysraja]>".t("halytysraja")."</option>"; 		
		$ulos .= "<option value='hinnastoon' $sel[hinnastoon]>".t("hinnastoon")."</option>"; 			
		$ulos .= "<option value='kehahin' $sel[kehahin]>".t("kehahin")."</option>"; 			
		$ulos .= "<option value='keraysvyohyke' $sel[keraysvyohyke]>".t("keraysvyohyke")."</option>"; 		
		$ulos .= "<option value='kuluprosentti' $sel[kuluprosentti]>".t("kuluprosentti")."</option>"; 		
		$ulos .= "<option value='malli' $sel[malli]>".t("malli")."</option>"; 				
		$ulos .= "<option value='mallitarkenne' $sel[mallitarkenne]>".t("mallitarkenne")."</option>"; 		
		$ulos .= "<option value='myyjanro' $sel[myyjanro]>".t("myyjanro")."</option>"; 			
		$ulos .= "<option value='myymalahinta' $sel[myymalahinta]>".t("myymalahinta")."</option>"; 		
		$ulos .= "<option value='myyntihinta' $sel[myyntihinta]>".t("myyntihinta")."</option>"; 		
		$ulos .= "<option value='myyntihinta_maara' $sel[myyntihinta_maara]>".t("myyntihinta_maara")."</option>"; 	
		$ulos .= "<option value='myynti_era' $sel[myynti_era]>".t("myynti_era")."</option>"; 			
		$ulos .= "<option value='nakyvyys' $sel[nakyvyys]>".t("nakyvyys")."</option>"; 			
		$ulos .= "<option value='nettohinta' $sel[nettohinta]>".t("nettohinta")."</option>"; 			
		$ulos .= "<option value='nimitys' $sel[nimitys]>".t("nimitys")."</option>"; 			
		$ulos .= "<option value='osasto' $sel[osasto]>".t("osasto")."</option>"; 				
		$ulos .= "<option value='ostajanro' $sel[ostajanro]>".t("ostajanro")."</option>"; 			
		$ulos .= "<option value='sarjanumeroseuranta' $sel[sarjanumeroseuranta]>".t("sarjanumeroseuranta")."</option>"; 
		$ulos .= "<option value='status' $sel[status]>".t("status")."</option>"; 				
		$ulos .= "<option value='suoratoimitus' $sel[suoratoimitus]>".t("suoratoimitus")."</option>"; 		
		$ulos .= "<option value='tahtituote' $sel[tahtituote]>".t("tahtituote")."</option>"; 			
		$ulos .= "<option value='try' $sel[try]>".t("try")."</option>"; 				
		$ulos .= "<option value='tuotemerkki' $sel[tuotemerkki]>".t("tuotemerkki")."</option>"; 		
		$ulos .= "<option value='tuoteno' $sel[tuoteno]>".t("tuoteno")."</option>"; 			
		$ulos .= "<option value='tuotepaallikko' $sel[tuotepaallikko]>".t("tuotepaallikko")."</option>";
		$ulos .= "<option value='tuotetyyppi' $sel[tuotetyyppi]>".t("tuotetyyppi")."</option>"; 		
		$ulos .= "<option value='varmuus_varasto' $sel[varmuus_varasto]>".t("varmuus_varasto")."</option>";	
		$ulos .= "<option value='vihahin' $sel[vihahin]>".t("vihahin")."</option>"; 			
		$ulos .= "<option value='yksikko' $sel[yksikko]>".t("yksikko")."</option>"; 			
		$ulos .= "<option value='yksin_kerailyalustalle' $sel[yksin_kerailyalustalle]>".t("yksin_kerailyalustalle")."</option>";
		$ulos .= "</select></td>";
		$jatko=0;
	}
	
	if (mysql_field_name($result, $i) == "palautuskielto") {
		
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 200).">";
		$ulos .= "<option value=''>".t("Ei Valintaa")."</option>"; 	
		$ulos .= "<option value='E' $sel[E]>".t("Ei Palautuskieltoa")."</option>"; 			
		$ulos .= "<option value='K' $sel[K]>".t("Palautuskielto")."</option>";
		$ulos .= "</select></td>";

		$jatko=0;
	}

?>