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
			
			$sel[$trow[$i]] = "SELECTED";

			$ulos = "<td><select name='$nimi' onchange='submit();'>";
			
			$ulos .= "<optgroup label='Tuotteiden avainsanat'>";
			$ulos .= "<option value='Y' {$sel['Y']}>									".t("Yksikko")."</option>";
			$ulos .= "<option value='TRY' {$sel['TRY']}>								".t("Tuoteryhmä")."</option>";
			$ulos .= "<option value='OSASTO' {$sel['OSASTO']}>							".t("Tuoteosasto")."</option>";
			$ulos .= "<option value='S' {$sel['S']}>									".t("Tuotteen status")."</option>";
			$ulos .= "<option value='TUOTEULK' {$sel['TUOTEULK']}>						".t("Tuotteiden avainsanojen laji")."</option>";
			$ulos .= "<option value='VARASTOLUOKKA' {$sel['VARASTOLUOKKA']}>			".t("Varastoluokka")."</option>";
			$ulos .= "<option value='OSASTO_SE' {$sel['OSASTO_SE']}>					".t("Ruotsinkielinen tuoteosasto")."</option>";
			$ulos .= "<option value='TRY_SE' {$sel['TRY_SE']}>							".t("Ruotsinkielinen tuoteryhmä")."</option>";
			$ulos .= "<option value='Y_SE' {$sel['Y_SE']}>								".t("Ruotsinkielinen yksikkö")."</option>";
			$ulos .= "<option value='S_SE' {$sel['S_SE']}>								".t("Ruotsinkielinen tuotteen status")."</option>";
			$ulos .= "<option value='SARJANUMERON_LI' {$sel['SARJANUMERON_LI']}>		".t("Sarjanumeron lisätieto")."</option>";
			
			$ulos .= "</optgroup>";

			$ulos .= "<optgroup label='Asiakkaiden avainsanat'>";
			$ulos .= "<option value='ASIAKASLUOKKA' {$sel['ASIAKASLUOKKA']}>			".t("Asiakasluokka")."</option>";
			$ulos .= "<option value='ASIAKASOSASTO' {$sel['ASIAKASOSASTO']}>			".t("Asiakasosasto")."</option>";
			$ulos .= "<option value='ASIAKASRYHMA' {$sel['ASIAKASRYHMA']}>				".t("Asiakasryhma")."</option>";
			$ulos .= "<option value='PIIRI' {$sel['PIIRI']}>							".t("Asiakkaan piiri")."</option>";
			$ulos .= "<option value='ASLUOKKA_SE' {$sel['ASLUOKKA_SE']}>				".t("Ruotsinkielinen asiakasluokka")."</option>";
			$ulos .= "<option value='ASOSASTO_SE' {$sel['ASOSASTO_SE']}>				".t("Ruotsinkielinen asiakasosasto")."</option>";
			$ulos .= "<option value='ASRYHMA_SE' {$sel['ASRYHMA_SE']}>					".t("Ruotsinkielinen asiakasryhma")."</option>";
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='Yhtiön avainsanat'>";
			$ulos .= "<option value='TYOM_TYOJONO' {$sel['TYOM_TYOJONO']}>				".t("Työmääräysten työjono")."</option>";
			$ulos .= "<option value='TYOM_TYOSTATUS' {$sel['TYOM_TYOSTATUS']}>			".t("Työmääräysten työstatus")."</option>";
			$ulos .= "<option value='TV' {$sel['TV']}>									".t("Tilausvahvistus")."</option>";
			$ulos .= "<option value='LAHETETYYPPI' {$sel['LAHETETYYPPI']}>				".t("Lähetetyyppi")."</option>";
			$ulos .= "<option value='KASSA' {$sel['KASSA']}>							".t("Kassa")."</option>";
			$ulos .= "<option value='KALETAPA' {$sel['KALETAPA']}>						".t("CRM yhteydenottotapa")."</option>";
			$ulos .= "<option value='MYSQLALIAS' {$sel['MYSQLALIAS']}>					".t("Tietokantasarakkeen nimialias")."</option>";
			$ulos .= "<option value='T' {$sel[T]}>										".t("Toimitustapa")."</option>";
			$ulos .= "<option value='TOIMITUSTAPA_OS' {$sel['TOIMITUSTAPA_OS']}>	".t("Toimitustapa ostolle (kuljetus)")."</option>";			
			$ulos .= "<option value='KUKAASEMA' {$sel['KUKAASEMA']}>					".t("Käytäjän asema")."</option>";
			$ulos .= "<option value='ALVULK' {$sel['ALVULK']}>							".t("Ulkomaan ALV%")."</option>";			
			$ulos .= "<option value='ALV' {$sel['ALV']}>								".t("ALV%")."</option>";
			$ulos .= "<option value='SEURANTA' {$sel['SEURANTA']}>						".t("Tilauksen seurantaluokka")."</option>";
			$ulos .= "<option value='TYOM_TYOLINJA' {$sel['TYOM_TYOLINJA']}>			".t("Työmääräysten työlinja")."</option>";			
			$ulos .= "<option value='pakkaus' {$sel['pakkaus']}>						".t("Pakkaus")."</option>";
			$ulos .= "<option value='TOIMEHTO' {$sel['TOIMEHTO']}>						".t("Toimitusehto")."</option>";
			$ulos .= "<option value='HENKILO_OSASTO' {$sel['HENKILO_OSASTO']}>			".t("Henkilöosasto")."</option>";
			$ulos .= "<option value='RAHTIKIRJA' {$sel['RAHTIKIRJA']}>					".t("Rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='KERAYSLISTA' {$sel['KERAYSLISTA']}>				".t("Keräyslista")."</option>";
			$ulos .= "<option value='PAKKAUSKUVAUS' {$sel['PAKKAUSKUVAUS']}>			".t("Pakkauskuvauksen tarkenne")."</option>";
			$ulos .= "<option value='TOIMITUSTAPA_SE' {$sel['TOIMITUSTAPA_SE']}>		".t("Ruotsinkielinen toimitustapa")."</option>";
			$ulos .= "<option value='LAHETETYYPPI_SE' {$sel['LAHETETYYPPI_SE']}>		".t("Ruotsinkielinen lähetetyyppi")."</option>";
			$ulos .= "<option value='TV_SE' {$sel['TV_SE']}>							".t("Ruotsinkielinen tilausvahvistus")."</option>";
			$ulos .= "<option value='RAHTIKIRJA_SE' {$sel['RAHTIKIRJA_SE']}>			".t("Ruotsinkielinen rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='PAKKAUS_SE' {$sel['PAKKAUS_SE']}>					".t("Ruotsinkielinen pakkaus")."</option>";
			
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='Viennin avainsanat'>";
			$ulos .= "<option value='KT' {$sel['KT']}>									".t("Kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='TULLI' {$sel['TULLI']}>							".t("Poistumistoimipaikka")."</option>";
			$ulos .= "<option value='KM' {$sel['KM']}>									".t("Kuljetusmuoto")."</option>";
			$ulos .= "<option value='KM_SE' {$sel['KM_SE']}>							".t("Ruotsinkielinen kuljetusmuoto")."</option>";
			$ulos .= "<option value='KT_SE' {$sel['KT_SE']}>							".t("Ruotsinkielinen kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='C' {$sel['C']}>									".t("CHN tietue")."</option>";			
			
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='Maksuehtojen avainsanat'>";
			$ulos .= "<option value='LASKUKUVAUS' {$sel['LASKUKUVAUS']}>				".t("Maksuposition kuvaus")."</option>";
			$ulos .= "<option value='KARHUVIESTI' {$sel['KARHUVIESTI']}>				".t("Karhuviesti")."</option>";	
			$ulos .= "<option value='MEHTOTXT_SE' {$sel['MEHTOTXT_SE']}>				".t("Ruotsinkielinen maksuehdon teksti")."</option>";
			$ulos .= "<option value='MEHTOKATXT_SE' {$sel['MEHTOKATXT_SE']}>			".t("Ruotsinkielinen maksuehdon kassateksti")."</option>";
			
			$ulos .= "</optgroup>";

			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='Muut avainsanat'>";
			$ulos .= "<option value='VAKIOVIESTI' {$sel['VAKIOVIESTI']}>				".t("Vakioviesti")."</option>";	
			$ulos .= "<option value='LITETY' {$sel['LITETY']}>							".t("Liitetiedostotyyppi")."</option>";
			$ulos .= "<option value='JAKELULISTA' {$sel['JAKELULISTA']}>				".t("Email jakelulista")."</option>";
			$ulos .= "<option value='TRIVITYYPPI' {$sel['TRIVITYYPPI']}>				".t("Tilausrivin tyyppi")."</option>";
						
			$ulos .= "</optgroup>";
			

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
