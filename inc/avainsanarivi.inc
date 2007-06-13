<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	if ($i == 1) {
		if ($trow[$i] == 'T') {
			$sel01 = "selected";
		}
		if ($trow[$i] == 'O') {
			$sel02 = "selected";
		}
		if ($trow[$i] == 'Y') {
			$sel03 = "selected";
		}
		if ($trow[$i] == 'C') {
			$sel04 = "selected";
		}
		if ($trow[$i] == 'ASIAKASLUOKKA') {
			$sel05 = "selected";
		}
		if ($trow[$i] == 'S') {
			$sel06 = "selected";
		}
		if ($trow[$i] == 'KM') {
			$sel07 = "selected";
		}
		if ($trow[$i] == 'KT') {
			$sel08 = "selected";
		}
		if ($trow[$i] == 'pakkaus') {
			$sel09 = "selected";
		}
		if ($trow[$i] == 'TV') {
			$sel10 = "selected";
		}
		if ($trow[$i] == 'TRY') {
			$sel11 = "selected";
		}
		if ($trow[$i] == 'OSASTO') {
			$sel12 = "selected";
		}
		if ($trow[$i] == 'KALETAPA') {
			$sel13 = "selected";
		}
		if ($trow[$i] == 'TUOTEULK') {
			$sel14 = "selected";
		}
		if ($trow[$i] == 'ASIAKASOSASTO') {
			$sel15 = "selected";
		}
		if ($trow[$i] == 'ASIAKASRYHMA') {
			$sel16 = "selected";
		}
		if ($trow[$i] == 'ALV') {
			$sel17 = "selected";
		}
		if ($trow[$i] == 'LAHETETYYPPI') {
			$sel18 = "selected";
		}
		if ($trow[$i] == 'TULLI') {
			$sel19 = "selected";
		}
		if ($trow[$i] == 'KASSA') {
			$sel20 = "selected";
		}
		if ($trow[$i] == 'HENKILO_OSASTO') {
			$sel21 = "selected";
		}
		if ($trow[$i] == 'PIIRI') {
			$sel22 = "selected";
		}
		if ($trow[$i] == 'TOIMEHTO') {
			$sel23 = "selected";
		}
		if ($trow[$i] == 'RAHTIKIRJA') {
			$sel24 = "selected";
		}
		if ($trow[$i] == 'MYSQLALIAS') {
			$sel25 = "selected";
		}
		if ($trow[$i] == 'LASKUKUVAUS') {
			$sel26 = "selected";
		}
		if ($trow[$i] == 'KERAYSLISTA') {
			$sel27 = "selected";
		}
		if ($trow[$i] == 'PAKKAUSKUVAUS') {
			$sel28 = "selected";
		}
		if ($trow[$i] == 'ALVULK') {
			$sel29 = "selected";
		}
		if ($trow[$i] == 'KUKAASEMA') {
			$sel30 = "selected";
		}
		if ($trow[$i] == 'SEURANTA') {
			$sel31 = "selected";
		}
		if ($trow[$i] == 'TRIVITYYPPI') {
			$sel32 = "selected";
		}
		if ($trow[$i] == 'OSASTO_SE') {
			$sel33 = "selected";
		}
		if ($trow[$i] == 'TRY_SE') {
			$sel34 = "selected";
		}
		if ($trow[$i] == 'TOIMITUSTAPA_SE') {
			$sel35 = "selected";
		}
		if ($trow[$i] == 'MEHTOTXT_SE') {
			$sel36 = "selected";
		}
		if ($trow[$i] == 'MEHTOKATXT_SE') {
			$sel37 = "selected";
		}
		if ($trow[$i] == 'ASOSASTO_SE') {
			$sel38 = "selected";
		}
		if ($trow[$i] == 'ASRYHMA_SE') {
			$sel39 = "selected";
		}
		if ($trow[$i] == 'LAHETETYYPPI_SE') {
			$sel40 = "selected";
		}
		if ($trow[$i] == 'TV_SE') {
			$sel41 = "selected";
		}
		if ($trow[$i] == 'ASLUOKKA_SE') {
			$sel42 = "selected";
		}
		if ($trow[$i] == 'KM_SE') {
			$sel43 = "selected";
		}
		if ($trow[$i] == 'KT_SE') {
			$sel44 = "selected";
		}
		if ($trow[$i] == 'RAHTIKIRJA_SE') {
			$sel45 = "selected";
		}
		if ($trow[$i] == 'Y_SE') {
			$sel46 = "selected";
		}
		if ($trow[$i] == 'S_SE') {
			$sel47 = "selected";
		}
		if ($trow[$i] == 'PAKKAUS_SE') {
			$sel48 = "selected";
		}
		if ($trow[$i] == 'SARJANUMERON_LI') {
			$sel49 = "selected";
		}
		if ($trow[$i] == 'TYOM_TYOJONO') {
			$sel50 = "selected";
		}
		if ($trow[$i] == 'TYOM_TYOSTATUS') {
			$sel51 = "selected";
		}
		if ($trow[$i] == 'JAKELULISTA') {
			$sel52 = "selected";
		}
		if ($trow[$i] == 'VAKIOVIESTI') {
			$sel53 = "selected";
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
		$ulos .= "<option value='TOIMEHTO'"       .$sel22.">".t("Toimitusehto")."</option>";
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

	if (mysql_field_name($result, $i) == "selite" and $sel18 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		$ulos .= "<option value=''></option>";

		while ($file = readdir($handle)) {
			if (substr($file,0,14) == 'tulosta_lahete') {
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

?>
