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

					$ulos .= "<input type='hidden' name='$nimi' value='$perhe'><select name='tunnus' onchange=\"document.location='yllapito.php?ojarj=$ojarj$ulisa&toim=avainsana&tunnus='+this.options[this.options.SELECTEDIndex].value;\">";


					while ($prow = mysql_fetch_array($pres)) {

						$laji = $prow["laji"];

						$sel  = "";
						if ($prow["tunnus"] == $tunnus) {
							$sel = "SELECTED";
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
			
			$avain_sel[$trow[$i]] = "SELECTED";

			$ulos = "<td><select name='$nimi' onchange='submit();'>";
			
			$ulos .= "<optgroup label='".t("Tuotteiden avainsanat")."'>";
			$ulos .= "<option value='Y' {$avain_sel['Y']}>									".t("Yksikko")."</option>";
			$ulos .= "<option value='TRY' {$avain_sel['TRY']}>								".t("Tuoteryhmä")."</option>";
			$ulos .= "<option value='OSASTO' {$avain_sel['OSASTO']}>						".t("Tuoteosasto")."</option>";
			$ulos .= "<option value='S' {$avain_sel['S']}>									".t("Tuotteen status")."</option>";
			$ulos .= "<option value='TUOTEULK' {$avain_sel['TUOTEULK']}>					".t("Tuotteiden avainsanojen laji")."</option>";
			$ulos .= "<option value='VARASTOLUOKKA' {$avain_sel['VARASTOLUOKKA']}>			".t("Varastoluokka")."</option>";
			$ulos .= "<option value='OSASTO_SE' {$avain_sel['OSASTO_SE']}>					".t("Ruotsinkielinen tuoteosasto")."</option>";
			$ulos .= "<option value='TRY_SE' {$avain_sel['TRY_SE']}>						".t("Ruotsinkielinen tuoteryhmä")."</option>";
			$ulos .= "<option value='Y_SE' {$avain_sel['Y_SE']}>							".t("Ruotsinkielinen yksikkö")."</option>";
			$ulos .= "<option value='S_SE' {$avain_sel['S_SE']}>							".t("Ruotsinkielinen tuotteen status")."</option>";
			$ulos .= "<option value='SARJANUMERON_LI' {$avain_sel['SARJANUMERON_LI']}>		".t("Sarjanumeron lisätieto")."</option>";
			$ulos .= "</optgroup>";

			$ulos .= "<optgroup label='".t("Asiakkaiden avainsanat")."'>";
			$ulos .= "<option value='ASIAKASLUOKKA' {$avain_sel['ASIAKASLUOKKA']}>			".t("Asiakasluokka")."</option>";
			$ulos .= "<option value='ASIAKASOSASTO' {$avain_sel['ASIAKASOSASTO']}>			".t("Asiakasosasto")."</option>";
			$ulos .= "<option value='ASIAKASRYHMA' {$avain_sel['ASIAKASRYHMA']}>			".t("Asiakasryhma")."</option>";
			$ulos .= "<option value='PIIRI' {$avain_sel['PIIRI']}>							".t("Asiakkaan piiri")."</option>";
			$ulos .= "<option value='ASLUOKKA_SE' {$avain_sel['ASLUOKKA_SE']}>				".t("Ruotsinkielinen asiakasluokka")."</option>";
			$ulos .= "<option value='ASOSASTO_SE' {$avain_sel['ASOSASTO_SE']}>				".t("Ruotsinkielinen asiakasosasto")."</option>";
			$ulos .= "<option value='ASRYHMA_SE' {$avain_sel['ASRYHMA_SE']}>				".t("Ruotsinkielinen asiakasryhma")."</option>";
			$ulos .= "<option value='LASKT_EMAIL' {$avain_sel['LASKT_EMAIL']}>				".t("Laskutustiedot autom. sähköpostitukseen")."</option>";
			$ulos .= "<option value='LASKT_EMAIL_SOP' {$avain_sel['LASKT_EMAIL_SOP']}>		".t("Laskutustiedot autom. sähköpostitukseen (Sopimus)")."</option>";
			$ulos .= "<option value='ASAVAINSANA' {$avain_sel['ASAVAINSANA']}>				".t("Asiakkaan avainsanojen laji")."</option>";
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='".t("Yhtiön avainsanat")."'>";
			$ulos .= "<option value='TV' {$avain_sel['TV']}>								".t("Tilausvahvistus")."</option>";
			$ulos .= "<option value='LAHETETYYPPI' {$avain_sel['LAHETETYYPPI']}>			".t("Lähetetyyppi")."</option>";
			$ulos .= "<option value='KALETAPA' {$avain_sel['KALETAPA']}>					".t("CRM yhteydenottotapa")."</option>";
			$ulos .= "<option value='MYSQLALIAS' {$avain_sel['MYSQLALIAS']}>				".t("Tietokantasarakkeen nimialias")."</option>";
			$ulos .= "<option value='T' {$avain_sel[T]}>									".t("Toimitustapa")."</option>";
			$ulos .= "<option value='TOIMITUSTAPA_OS' {$avain_sel['TOIMITUSTAPA_OS']}>		".t("Toimitustapa ostolle (kuljetus)")."</option>";			
			$ulos .= "<option value='KUKAASEMA' {$avain_sel['KUKAASEMA']}>					".t("Käytäjän asema")."</option>";
			$ulos .= "<option value='ALVULK' {$avain_sel['ALVULK']}>						".t("Ulkomaan ALV%")."</option>";			
			$ulos .= "<option value='ALV' {$avain_sel['ALV']}>								".t("ALV%")."</option>";
			$ulos .= "<option value='SEURANTA' {$avain_sel['SEURANTA']}>					".t("Tilauksen seurantaluokka")."</option>";	
			$ulos .= "<option value='pakkaus' {$avain_sel['pakkaus']}>						".t("Pakkaus")."</option>";
			$ulos .= "<option value='TOIMEHTO' {$avain_sel['TOIMEHTO']}>					".t("Toimitusehto")."</option>";
			$ulos .= "<option value='HENKILO_OSASTO' {$avain_sel['HENKILO_OSASTO']}>		".t("Henkilöosasto")."</option>";
			$ulos .= "<option value='RAHTIKIRJA' {$avain_sel['RAHTIKIRJA']}>				".t("Rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='KERAYSLISTA' {$avain_sel['KERAYSLISTA']}>				".t("Keräyslista")."</option>";
			$ulos .= "<option value='PAKKAUSKUVAUS' {$avain_sel['PAKKAUSKUVAUS']}>			".t("Pakkauskuvauksen tarkenne")."</option>";
			$ulos .= "<option value='TOIMITUSTAPA_SE' {$avain_sel['TOIMITUSTAPA_SE']}>		".t("Ruotsinkielinen toimitustapa")."</option>";
			$ulos .= "<option value='LAHETETYYPPI_SE' {$avain_sel['LAHETETYYPPI_SE']}>		".t("Ruotsinkielinen lähetetyyppi")."</option>";
			$ulos .= "<option value='TV_SE' {$avain_sel['TV_SE']}>							".t("Ruotsinkielinen tilausvahvistus")."</option>";
			$ulos .= "<option value='RAHTIKIRJA_SE' {$avain_sel['RAHTIKIRJA_SE']}>			".t("Ruotsinkielinen rahtikirjatyyppi")."</option>";
			$ulos .= "<option value='PAKKAUS_SE' {$avain_sel['PAKKAUS_SE']}>				".t("Ruotsinkielinen pakkaus")."</option>";			
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='".t("Työmääräysten avainsanat")."'>";
			$ulos .= "<option value='TYOM_TYOJONO' {$avain_sel['TYOM_TYOJONO']}>			".t("Työmääräysten työjono")."</option>";
			$ulos .= "<option value='TYOM_TYOSTATUS' {$avain_sel['TYOM_TYOSTATUS']}>		".t("Työmääräysten työstatus")."</option>";
			$ulos .= "<option value='TYOM_TYOLINJA' {$avain_sel['TYOM_TYOLINJA']}>			".t("Työmääräysten työlinja")."</option>";	
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='".t("Viennin avainsanat")."'>";
			$ulos .= "<option value='KT' {$avain_sel['KT']}>								".t("Kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='TULLI' {$avain_sel['TULLI']}>							".t("Poistumistoimipaikka")."</option>";
			$ulos .= "<option value='KM' {$avain_sel['KM']}>								".t("Kuljetusmuoto")."</option>";
			$ulos .= "<option value='KM_SE' {$avain_sel['KM_SE']}>							".t("Ruotsinkielinen kuljetusmuoto")."</option>";
			$ulos .= "<option value='KT_SE' {$avain_sel['KT_SE']}>							".t("Ruotsinkielinen kauppatapahtuman luonne")."</option>";
			$ulos .= "<option value='C' {$avain_sel['C']}>									".t("CHN tietue")."</option>";						
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='".t("Maksuehtojen avainsanat")."'>";
			$ulos .= "<option value='LASKUKUVAUS' {$avain_sel['LASKUKUVAUS']}>				".t("Maksuposition kuvaus")."</option>";
			$ulos .= "<option value='KARHUVIESTI' {$avain_sel['KARHUVIESTI']}>				".t("Karhuviesti")."</option>";	
			$ulos .= "<option value='MEHTOTXT_SE' {$avain_sel['MEHTOTXT_SE']}>				".t("Ruotsinkielinen maksuehdon teksti")."</option>";
			$ulos .= "<option value='MEHTOKATXT_SE' {$avain_sel['MEHTOKATXT_SE']}>			".t("Ruotsinkielinen maksuehdon kassateksti")."</option>";
			$ulos .= "</optgroup>";
			
			$ulos .= "<optgroup label='".t("Muut avainsanat")."'>";
			$ulos .= "<option value='VAKIOVIESTI' {$avain_sel['VAKIOVIESTI']}>				".t("Vakioviesti")."</option>";	
			$ulos .= "<option value='LITETY' {$avain_sel['LITETY']}>						".t("Liitetiedostotyyppi")."</option>";
			$ulos .= "<option value='TIL-LITETY' {$avain_sel['TIL-LITETY']}>				".t("Tilauksen liitetiedostotyyppi")."</option>";
			$ulos .= "<option value='JAKELULISTA' {$avain_sel['JAKELULISTA']}>				".t("Email jakelulista")."</option>";
			$ulos .= "<option value='LUETTELO' {$avain_sel['LUETTELO']}>					".t("Luettelotyyppi")."</option>";
			$ulos .= "<option value='TRIVITYYPPI' {$avain_sel['TRIVITYYPPI']}>				".t("Tilausrivin tyyppi")."</option>";
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
					$sel = "SELECTED";
				}
				elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
					$sel = "SELECTED";
				}
				$ulos .= "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
			}
		}
    	
		$ulos .= "</select></td>";
    	
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite" and $sel["KERAYSLISTA"] == "SELECTED") {

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
	
	if (mysql_field_name($result, $i) == "selite" and $avain_sel["RAHTIKIRJA"] == "SELECTED") {

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

	if (mysql_field_name($result, $i) == "selite" and $avain_sel["SARJANUMERON_LI"] == "SELECTED") {

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
		
	if (mysql_field_name($result, $i) == "selitetark_2" and $avain_sel["ALVULK"] == "SELECTED") {

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
				$sel = "SELECTED";
			}
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
				$sel = "SELECTED";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if ($avain_sel["KARHUVIESTI"] == "SELECTED" and (mysql_field_name($result, $i) == "selite" or mysql_field_name($result, $i) == "selitetark_2" or mysql_field_name($result, $i) == "selitetark_3")) {
		$tyyppi = 0;
		$jatko  = 0;
	}
	
	if ($avain_sel["KARHUVIESTI"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark" or
		$avain_sel["OSASTO"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark_3" or
		$avain_sel["TRY"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark_3") {
			
		$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selite" and $avain_sel['TYOM_TYOLINJA'] == "SELECTED") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TYOM_TYOJONO'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Kaikki työjonot")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selitetark" and $avain_sel['TYOM_TYOLINJA'] == "SELECTED") {
		
		$query = "	SELECT distinct kuka, nimi
					FROM kuka
					WHERE yhtio = '$kukarow[yhtio]'
					and nimi != ''
					and extranet=''
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	// rahtikirjatyyppi
	if (mysql_field_name($result, $i) == "selitetark_2" and $avain_sel["RAHTIKIRJA"] == "SELECTED") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

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
