<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "perhe") {

		if(${$nimi} > 0) $tunnus = ${$nimi};
		
		if($tunnus > 0) {	
			$query = "select perhe, laji, jarjestys from avainsana where yhtio = '{$kukarow["yhtio"]}' and tunnus = '$tunnus'";
			$pres = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_array($pres);
			$perhe = $prow["perhe"];
			$laji = $prow["laji"];				
			$ajarjestys = $prow["jarjestys"];
			
			//	Jos perhe on nolla niin skipataan koko juttu, näinollen ei sekoiteta vanhoja tietoja!
			if($perhe > 0) {
				$ulos = "<td>";

				//	Onko gruupissa jo useampia tietueita?
				$query = "	SELECT tunnus, selite, laji, kieli
				 			FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and perhe = '$perhe'
							ORDER BY jarjestys, kieli, selite";
				$pres = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($pres) > 1) {

					$lukossa = "ON";

					$ulos .= "<input type='hidden' name='$nimi' value='$perhe'><select name='tunnus' onchange=\"document.location='yllapito.php?ojarj=$ojarj$ulisa&toim=avainsana&tunnus='+this.options[this.options.selectedIndex].value;\">";


					while ($prow = mysql_fetch_array($pres)) {

						$laji = $prow["laji"];

						$sel  = "";
						if ($prow["tunnus"] == $tunnus) {
							$sel = "selected";
						}
						else {
							$seuraavatunnus = $prow["tunnus"];
						}

						$ulos .= "<option value='{$prow["tunnus"]}' $sel>{$prow["kieli"]} - {$prow["selite"]}</option>";
					}

					$ulos .= "</select>";

				}

				$ulos .= "&nbsp;	<a href='#' onclick='document.location=\"yllapito.php?ojarj=$ojarj$ulisa&toim=avainsana&uusi=1&perhe=$perhe&lukossa=ON&laji=$laji&ajarjestys=$ajarjestys\"'><img src='pics/lullacons/doc-option-add.png'></a></td>";
				$jatko = 0;
			}
			else {
				$tyyppi = 0;
			}
		}
		else {			
			//	Arvotaan uusi perhetunnus
			if($perhe == 0) {
				$tyyppi = 4;
				$query = "	SELECT max(perhe) perhe
				 			FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'";
				$pres = mysql_query($query) or pupe_error($query);
				$perse = mysql_fetch_array($pres);
				$trow[$i] = $perhe = ($perse["perhe"]+1);
			}
			else {
				$ulos = "<td><input type='hidden' name = '$nimi' value='$perhe'><input type='hidden' name = 'perhe' value='$perhe'></td>";
				$jatko = 0;
				$trow[$i] = $perhe;
			}
		}
	}
	

	if (mysql_field_name($result, $i) == "laji") {
		
		//	Lisätään perheeseen joten tätä listaa ei näytetä
		if($lukossa == "") {
			
			if($laji != "") {
				$trow[$i] == $laji;
			}
			
			if (strtoupper($trow[$i]) == 'T') {
				$sel01 = "selected";
			}
			if (strtoupper($trow[$i]) == 'O') {
				$sel02 = "selected";
			}
			if (strtoupper($trow[$i]) == 'Y') {
				$sel03 = "selected";
			}
			if (strtoupper($trow[$i]) == 'C') {
				$sel04 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASIAKASLUOKKA') {
				$sel05 = "selected";
			}
			if (strtoupper($trow[$i]) == 'S') {
				$sel06 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KM') {
				$sel07 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KT') {
				$sel08 = "selected";
			}
			if (strtoupper($trow[$i]) == 'PAKKAUS') {
				$sel09 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TV') {
				$sel10 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TRY') {
				$sel11 = "selected";
			}
			if (strtoupper($trow[$i]) == 'OSASTO') {
				$sel12 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KALETAPA') {
				$sel13 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TUOTEULK') {
				$sel14 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASIAKASOSASTO') {
				$sel15 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASIAKASRYHMA') {
				$sel16 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ALV') {
				$sel17 = "selected";
			}
			if (strtoupper($trow[$i]) == 'LAHETETYYPPI') {
				$sel18 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TULLI') {
				$sel19 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KASSA') {
				$sel20 = "selected";
			}
			if (strtoupper($trow[$i]) == 'HENKILO_OSASTO') {
				$sel21 = "selected";
			}
			if (strtoupper($trow[$i]) == 'PIIRI') {
				$sel22 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TOIMEHTO') {
				$sel23 = "selected";
			}
			if (strtoupper($trow[$i]) == 'RAHTIKIRJA') {
				$sel24 = "selected";
			}
			if (strtoupper($trow[$i]) == 'MYSQLALIAS') {
				$sel25 = "selected";
			}
			if (strtoupper($trow[$i]) == 'LASKUKUVAUS') {
				$sel26 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KERAYSLISTA') {
				$sel27 = "selected";
			}
			if (strtoupper($trow[$i]) == 'PAKKAUSKUVAUS') {
				$sel28 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ALVULK') {
				$sel29 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KUKAASEMA') {
				$sel30 = "selected";
			}
			if (strtoupper($trow[$i]) == 'SEURANTA') {
				$sel31 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TRIVITYYPPI') {
				$sel32 = "selected";
			}
			if (strtoupper($trow[$i]) == 'OSASTO_SE') {
				$sel33 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TRY_SE') {
				$sel34 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TOIMITUSTAPA_SE') {
				$sel35 = "selected";
			}
			if (strtoupper($trow[$i]) == 'MEHTOTXT_SE') {
				$sel36 = "selected";
			}
			if (strtoupper($trow[$i]) == 'MEHTOKATXT_SE') {
				$sel37 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASOSASTO_SE') {
				$sel38 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASRYHMA_SE') {
				$sel39 = "selected";
			}
			if (strtoupper($trow[$i]) == 'LAHETETYYPPI_SE') {
				$sel40 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TV_SE') {
				$sel41 = "selected";
			}
			if (strtoupper($trow[$i]) == 'ASLUOKKA_SE') {
				$sel42 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KM_SE') {
				$sel43 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KT_SE') {
				$sel44 = "selected";
			}
			if (strtoupper($trow[$i]) == 'RAHTIKIRJA_SE') {
				$sel45 = "selected";
			}
			if (strtoupper($trow[$i]) == 'Y_SE') {
				$sel46 = "selected";
			}
			if (strtoupper($trow[$i]) == 'S_SE') {
				$sel47 = "selected";
			}
			if (strtoupper($trow[$i]) == 'PAKKAUS_SE') {
				$sel48 = "selected";
			}
			if (strtoupper($trow[$i]) == 'SARJANUMERON_LI') {
				$sel49 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TYOM_TYOJONO') {
				$sel50 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TYOM_TYOSTATUS') {
				$sel51 = "selected";
			}
			if (strtoupper($trow[$i]) == 'JAKELULISTA') {
				$sel52 = "selected";
			}
			if (strtoupper($trow[$i]) == 'VAKIOVIESTI') {
				$sel53 = "selected";
			}
			if (strtoupper($trow[$i]) == 'TYOM_TYOLINJA') {
				$sel54 = "selected";
			}
			if (strtoupper($trow[$i]) == 'KARHUVIESTI') {
				$sel55 = "selected";
			}
			if (strtoupper($trow[$i]) == 'VARASTOLUOKKA') {
				$sel56 = "selected";
			}
			if (strtoupper($trow[$i]) == 'LITETY') {
				$sel57 = "selected";
			}

			$ulos = "<td><select name='$nimi' onchange='submit();'>";

			$ulos .= "<option value='T'"			  .$sel01.">".t("Toimitustapa")."</option>";
			$ulos .= "<option value='Y'"			  .$sel03.">".t("Yksikko")."</option>";
			$ulos .= "<option value='C'"			  .$sel04.">".t("CHN tietue")."</option>";
			$ulos .= "<option value='ASIAKASLUOKKA'"  .$sel05.">".t("Asiakasluokka")."</option>";
			$ulos .= "<option value='ASIAKASOSASTO'"  .$sel15.">".t("Asiakasosasto")."</option>";
			$ulos .= "<option value='ASIAKASRYHMA'"   .$sel16.">".t("Asiakasryhma")."</option>";
			$ulos .= "<option value='S'"			  .$sel06.">".t("Tuotteen status")."</option>";
			$ulos .= "<option value='KM'"			  .$sel07.">".t("Kuljetusmuoto")."</option>";
			$ulos .= "<option value='KT'"			  .$sel08.">".t("Kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='pakkaus'"		  .$sel09.">".t("Pakkaus")."</option>";
			$ulos .= "<option value='TV'"			  .$sel10.">".t("Tilausvahvistus")."</option>";
			$ulos .= "<option value='TRY'"			  .$sel11.">".t("Tuoteryhmä")."</option>";
			$ulos .= "<option value='OSASTO'"		  .$sel12.">".t("Tuoteosasto")."</option>";
			$ulos .= "<option value='KALETAPA'"		  .$sel13.">".t("CRM yhteydenottotapa")."</option>";
			$ulos .= "<option value='TUOTEULK'"		  .$sel14.">".t("Tuotteiden avainsanojen laji")."</option>";
			$ulos .= "<option value='ALV'"			  .$sel17.">".t("ALV%")."</option>";
			$ulos .= "<option value='LAHETETYYPPI'"	  .$sel18.">".t("Lähetetyyppi")."</option>";
			$ulos .= "<option value='TULLI'"		  .$sel19.">".t("Poistumistoimipaikka")."</option>";
			$ulos .= "<option value='KASSA'"		  .$sel20.">".t("Kassa")."</option>";
			$ulos .= "<option value='HENKILO_OSASTO'" .$sel21.">".t("Henkilöosasto")."</option>";
			$ulos .= "<option value='PIIRI'"          .$sel22.">".t("Asiakkaan piiri")."</option>";
			$ulos .= "<option value='TOIMEHTO'"       .$sel23.">".t("Toimitusehto")."</option>";
			$ulos .= "<option value='RAHTIKIRJA'"     .$sel24.">".t("Rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='MYSQLALIAS'"     .$sel25.">".t("Tietokantasarakkeen nimialias")."</option>";
			$ulos .= "<option value='LASKUKUVAUS'"    .$sel26.">".t("Maksuposition kuvaus")."</option>";
			$ulos .= "<option value='KERAYSLISTA'"    .$sel27.">".t("Keräyslista")."</option>";
			$ulos .= "<option value='PAKKAUSKUVAUS'"  .$sel28.">".t("Pakkauskuvauksen tarkenne")."</option>";
			$ulos .= "<option value='ALVULK'"         .$sel29.">".t("Ulkomaan ALV%")."</option>";
			$ulos .= "<option value='KUKAASEMA'"      .$sel30.">".t("Käytäjän asema")."</option>";
			$ulos .= "<option value='SEURANTA'"       .$sel31.">".t("Tilauksen seurantaluokka")."</option>";
			$ulos .= "<option value='TRIVITYYPPI'"    .$sel32.">".t("Tilausrivin tyyppi")."</option>";
			$ulos .= "<option value='OSASTO_SE'"   	  .$sel33.">".t("Ruotsinkielinen tuoteosasto")."</option>";
			$ulos .= "<option value='TRY_SE'"   	  .$sel34.">".t("Ruotsinkielinen tuoteryhmä")."</option>";
			$ulos .= "<option value='TOIMITUSTAPA_SE'".$sel35.">".t("Ruotsinkielinen toimitustapa")."</option>";
			$ulos .= "<option value='MEHTOTXT_SE'"	  .$sel36.">".t("Ruotsinkielinen maksuehdon teksti")."</option>";
			$ulos .= "<option value='MEHTOKATXT_SE'"  .$sel37.">".t("Ruotsinkielinen maksuehdon kassateksti")."</option>";
			$ulos .= "<option value='ASOSASTO_SE'"	  .$sel38.">".t("Ruotsinkielinen asiakasosasto")."</option>";
			$ulos .= "<option value='ASRYHMA_SE'"	  .$sel39.">".t("Ruotsinkielinen asiakasryhma")."</option>";
			$ulos .= "<option value='LAHETETYYPPI_SE'".$sel40.">".t("Ruotsinkielinen lähetetyyppi")."</option>";
			$ulos .= "<option value='TV_SE'"		  .$sel41.">".t("Ruotsinkielinen tilausvahvistus")."</option>";
			$ulos .= "<option value='ASLUOKKA_SE'"    .$sel42.">".t("Ruotsinkielinen asiakasluokka")."</option>";
			$ulos .= "<option value='KM_SE'"	      .$sel43.">".t("Ruotsinkielinen kuljetusmuoto")."</option>";
			$ulos .= "<option value='KT_SE'"	      .$sel44.">".t("Ruotsinkielinen kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='RAHTIKIRJA_SE'"  .$sel45.">".t("Ruotsinkielinen rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='Y_SE'" 		  .$sel46.">".t("Ruotsinkielinen yksikkö")."</option>";
			$ulos .= "<option value='S_SE'" 		  .$sel47.">".t("Ruotsinkielinen tuotteen status")."</option>";
			$ulos .= "<option value='PAKKAUS_SE'" 	  .$sel48.">".t("Ruotsinkielinen pakkaus")."</option>";
			$ulos .= "<option value='SARJANUMERON_LI'".$sel49.">".t("Sarjanumeron lisätieto")."</option>";
			$ulos .= "<option value='TYOM_TYOJONO'"   .$sel50.">".t("Työmääräysten työjono")."</option>";
			$ulos .= "<option value='TYOM_TYOSTATUS'" .$sel51.">".t("Työmääräysten työstatus")."</option>";
			$ulos .= "<option value='JAKELULISTA'" 	  .$sel52.">".t("Email jakelulista")."</option>";
			$ulos .= "<option value='VAKIOVIESTI'"    .$sel53.">".t("Vakioviesti")."</option>";	
			$ulos .= "<option value='TYOM_TYOLINJA'"  .$sel54.">".t("Työmääräysten työlinja")."</option>";			
			$ulos .= "<option value='KARHUVIESTI'"    .$sel55.">".t("Karhuviesti")."</option>";	
			$ulos .= "<option value='VARASTOLUOKKA'"  .$sel56.">".t("Varastoluokka")."</option>";
			$ulos .= "<option value='LITETY'"  		  .$sel57.">".t("Liitetiedostotyyppi")."</option>";								
			$ulos .= "</select></td>";
			$jatko = 0;			
		}
		else {
			
			if($laji != "")	{
				$trow[$i] = $laji;
			}
			
			$tyyppi = 3;
		}
	}
	
	if (mysql_field_name($result, $i) == "kieli") {
		
		$kieli = $trow[$i];
		
		$ulos = "<td><select name='$nimi'>";
		$sel  = "";

		//	näytetäään vain kielet joilla on 
		if($perhe > 0) {
			$query = "	SELECT group_concat(kieli) kielet FROM avainsana WHERE yhtio = '{$kukarow["yhtio"]}' and perhe='$perhe'";
			$res = mysql_query($query) or pupe_error($query);
			$kilerow = mysql_fetch_array($res);
			$kielet = $kilerow["kielet"];
		}
    	
		$query  = "show columns from sanakirja";
		$fields =  mysql_query($query);

		while ($apurow = mysql_fetch_array($fields)) {
			if (strlen($apurow[0]) == 2 and (strpos($kielet,$apurow[0]) === false or $apurow[0] == $trow[$i])) {
				$sel = "";
				if ($trow[$i] == $apurow[0]) {
					$sel = "selected";
				}
				elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
					$sel = "selected";
				}
				$ulos .= "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
			}
		}
    	
		$ulos .= "</select></td>";
    	
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite" and $sel27 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		while ($file = readdir($handle)) {
			if (strpos($file,"kerayslista") !== FALSE) {
				$sel = "";

				if ($file == $trow[$i]) {
					$sel = "SELECTED";
				}

				$ulos .= "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selite" and $sel24 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		$ulos .= "<option value=''></option>";

		while ($file = readdir($handle)) {
			if (substr($file,0,10) == 'rahtikirja') {
				$sel = "";

				if ($file == $trow[$i]) {
					$sel = "SELECTED";
				}

				$ulos .= "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite" and $sel49 == "selected") {

		$selitteet = array(	'ANKKURIVINSSI','JAAKAAPPI','KONEISTUS','KUVATYYPPI','LAATU','MATERIAALI','MERKKI','MOOTTORINMERKKI','MOOTTORINOHJAUS',
							'PILSSIPUMPPU','SIJAINTI','TIIKKISARJA','TOIMITUSKULUT','TYYNYSARJA','TYYPPI','UUNI','VALONHEITIN','VARIRUNKO','VESSA',
							'RUNKOTYYPPI', 'SPRINKLERI','KILPI');

		$ulos  = "<td><select name='$nimi'>";
		
		foreach ($selitteet as $selite) {
			if (strtoupper($selite) == strtoupper($trow[$i])) {
				$sel = "SELECTED";
			}
			else {
				$sel = "";
			}
			
			$ulos .= "<option value='$selite' $sel>$selite</option>";
			
		}
		
		$ulos .= "</select></td>";
		$jatko = 0;
	}
		
	if (mysql_field_name($result, $i) == "selitetark_2" and $sel29 == "selected") {

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		$ulos .= "<option value = '' ></option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if ($sel55 == "selected" and (mysql_field_name($result, $i) == "selite" or mysql_field_name($result, $i) == "selitetark_2" or mysql_field_name($result, $i) == "selitetark_3")) {
		$tyyppi = 0;
		$jatko  = 0;
	}
	
	if ($sel55 == "selected" and mysql_field_name($result, $i) == "selitetark" or
		$sel12 == "selected" and mysql_field_name($result, $i) == "selitetark_3" or
		$sel11 == "selected" and mysql_field_name($result, $i) == "selitetark_3") {
		$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
		$jatko = 0;
	}

	// rahtikirjatyyppi
	if (mysql_field_name($result, $i) == "selitetark_2" and $sel24 == "selected") {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name = '$nimi'>";
		$ulos .= "<option value = ''>".t("Oletus")."</option>";
		$ulos .= "<option value = '1' $sel[1]>".t("Tulostintyyppi")." - ".t("Laser A4")."</option>";
		$ulos .= "<option value = '2' $sel[2]>".t("Tulostintyyppi")." - ".t("Laser A5")."</option>";
		$ulos .= "<option value = '3' $sel[3]>".t("Tulostintyyppi")." - ".t("Matriisi")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jarjestys") {	
		if($trow[$i] == "" and $ajarjestys != "") {
			$trow[$i] = $ajarjestys;
		}
	}

?>
