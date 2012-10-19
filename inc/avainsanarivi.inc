<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "perhe") {

		// Perhetunnus
		if ($lukitse_avaimeen != "") {
			$trow[$i] = $perhe = $lukitse_avaimeen;
		}
		elseif ($trow[$i] == 0) {
			$query = "	SELECT max(perhe) perhe
			 			FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'";
			$pres = mysql_query($query) or pupe_error($query);
			$perse = mysql_fetch_array($pres);

			$trow[$i] = $perhe = ($perse["perhe"]+1);

			//Päivitetään tämä jo tässä vaiheessa
			if ($tunnus > 0) {
				$query = "	UPDATE avainsana
							SET perhe = '$perhe'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$pres = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			$perhe = $trow[$i];
		}

		$tyyppi = 4;
	}

	// Maksuehtojen js toimitustapojen kieliveriot
	if (mysql_field_name($result, $i) == "selite" and $lukitse_avaimeen != "" and ($lukitse_laji == "TOIMTAPAKV" or $lukitse_laji == "MAKSUEHTOKV" or $lukitse_laji == "PAKKAUSKV")) {
		$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite") {
		$avain_selite 	= "SELECTED";
	}

	if (mysql_field_name($result, $i) == "laji") {

		$al_laji 				= trim(strtoupper($trow[$i]));
		$lukitse_laji 			= trim(strtoupper($lukitse_laji));
		$avain_sel[$trow[$i]] 	= "SELECTED";

		$ulos = "<td><select name='$nimi' onchange='submit();'>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Tuotteiden avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "Y") 				$ulos .= "<option value='Y' $avain_sel[Y]>									".t("Yksikko")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TRY") 				$ulos .= "<option value='TRY' $avain_sel[TRY]>								".t("Tuoteryhmä")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "OSASTO") 			$ulos .= "<option value='OSASTO' $avain_sel[OSASTO]>						".t("Tuoteosasto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TUOTEMERKKI") 		$ulos .= "<option value='TUOTEMERKKI' $avain_sel[TUOTEMERKKI]>				".t("Tuotemerkki")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "MALLI") 			$ulos .= "<option value='MALLI' $avain_sel[MALLI]>							".t("Tuotemalli")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "MALLITARKENNE") 	$ulos .= "<option value='MALLITARKENNE' $avain_sel[MALLITARKENNE]>			".t("Tuotemallitarkenne")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "S") 				$ulos .= "<option value='S' $avain_sel[S]>									".t("Tuotteen status")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TUOTEULK") 			$ulos .= "<option value='TUOTEULK' $avain_sel[TUOTEULK]>					".t("Tuotteiden avainsanojen laji")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "VARASTOLUOKKA") 	$ulos .= "<option value='VARASTOLUOKKA' $avain_sel[VARASTOLUOKKA]>			".t("Varastoluokka")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "VARASTORYHMA") 		$ulos .= "<option value='VARASTORYHMA' $avain_sel[VARASTORYHMA]>			".t("Varastoryhmä")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "SARJANUMERON_LI") 	$ulos .= "<option value='SARJANUMERON_LI' $avain_sel[SARJANUMERON_LI]>		".t("Sarjanumeron lisätieto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "PARAMETRI") 		$ulos .= "<option value='PARAMETRI' $avain_sel[PARAMETRI]>					".t("Tuotteen parametri")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LISATIETO") 		$ulos .= "<option value='LISATIETO' $avain_sel[LISATIETO]>					".t("Tuotteen lisätieto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TARRATYYPPI") 		$ulos .= "<option value='TARRATYYPPI' $avain_sel[TARRATYYPPI]>				".t("Tuotteen tarratyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "VALMISTUSLINJA") 	$ulos .= "<option value='VALMISTUSLINJA' $avain_sel[VALMISTUSLINJA]>		".t("Tuotteen valmistuslinja")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "HAMMASTUS") 		$ulos .= "<option value='HAMMASTUS' $avain_sel[HAMMASTUS]>					".t("Tuotteen hammastus")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Asiakkaiden avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASLUOKKA") 	$ulos .= "<option value='ASIAKASLUOKKA' $avain_sel[ASIAKASLUOKKA]>			".t("Asiakasluokka")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASOSASTO") 	$ulos .= "<option value='ASIAKASOSASTO' $avain_sel[ASIAKASOSASTO]>			".t("Asiakasosasto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASRYHMA") 		$ulos .= "<option value='ASIAKASRYHMA' $avain_sel[ASIAKASRYHMA]>			".t("Asiakasryhma")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASTILA") 		$ulos .= "<option value='ASIAKASTILA' $avain_sel[ASIAKASTILA]>				".t("Asiakastila")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "PIIRI") 			$ulos .= "<option value='PIIRI' $avain_sel[PIIRI]>							".t("Asiakkaan piiri")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ASAVAINSANA") 		$ulos .= "<option value='ASAVAINSANA' $avain_sel[ASAVAINSANA]>				".t("Asiakkaan avainsanojen laji")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "EXTASAVAINSANA") 	$ulos .= "<option value='EXTASAVAINSANA' $avain_sel[EXTASAVAINSANA]>		".t("Extranet-asiakkaan avainsanojen laji")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LAANI") 			$ulos .= "<option value='LAANI' $avain_sel[LAANI]>							".t("Lääni")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KUNTA") 			$ulos .= "<option value='KUNTA' $avain_sel[KUNTA]>							".t("Kunta")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Yhtiön avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "TV") 				$ulos .= "<option value='TV' $avain_sel[TV]>								".t("Tilausvahvistus")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TOIMVAHVISTUS")		$ulos .= "<option value='TOIMVAHVISTUS' $avain_sel[TOIMVAHVISTUS]>			".t("Toimitusvahvistus")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KALETAPA") 			$ulos .= "<option value='KALETAPA' $avain_sel[KALETAPA]>					".t("CRM yhteydenottotapa")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "MYSQLALIAS") 		$ulos .= "<option value='MYSQLALIAS' $avain_sel[MYSQLALIAS]>				".t("Tietokantasarakkeen nimialias")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TOIMITUSTAPA_OS") 	$ulos .= "<option value='TOIMITUSTAPA_OS' $avain_sel[TOIMITUSTAPA_OS]>		".t("Toimitustapa ostolle (kuljetus)")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KUKAASEMA") 		$ulos .= "<option value='KUKAASEMA' $avain_sel[KUKAASEMA]>					".t("Käytäjän asema")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ALVULK") 			$ulos .= "<option value='ALVULK' $avain_sel[ALVULK]>						".t("Ulkomaan ALV%")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "ALV") 				$ulos .= "<option value='ALV' $avain_sel[ALV]>								".t("ALV%")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "SEURANTA") 			$ulos .= "<option value='SEURANTA' $avain_sel[SEURANTA]>					".t("Tilauksen seurantaluokka")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TOIMEHTO") 			$ulos .= "<option value='TOIMEHTO' $avain_sel[TOIMEHTO]>					".t("Toimitusehto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "HENKILO_OSASTO") 	$ulos .= "<option value='HENKILO_OSASTO' $avain_sel[HENKILO_OSASTO]>		".t("Henkilöosasto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KVERITTELY") 		$ulos .= "<option value='KVERITTELY' $avain_sel[KVERITTELY]>				".t("Kulunvalvonnan erittely")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KONEENOSA") 		$ulos .= "<option value='KONEENOSA' $avain_sel[KONEENOSA]>					".t("Koneenosa")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Varaston avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "KERAYSLISTA") 		$ulos .= "<option value='KERAYSLISTA' $avain_sel[KERAYSLISTA]>				".t("Keräyslista")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LAHETETYYPPI") 		$ulos .= "<option value='LAHETETYYPPI' $avain_sel[LAHETETYYPPI]>			".t("Lähetetyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "RAHTIKIRJA") 		$ulos .= "<option value='RAHTIKIRJA' $avain_sel[RAHTIKIRJA]>				".t("Rahtikirjatyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TOIMTAPAKV") 		$ulos .= "<option value='TOIMTAPAKV' $avain_sel[TOIMTAPAKV]>				".t("Toimitustavan kieliversio")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TERMINAALIALUE") 	$ulos .= "<option value='TERMINAALIALUE' $avain_sel[TERMINAALIALUE]>		".t("Varaston terminaalialue")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "SSCC")			 	$ulos .= "<option value='SSCC' $avain_sel[SSCC]>							".t("SSCC-koodi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "PAKKAUSKV") 		$ulos .= "<option value='PAKKAUSKV' $avain_sel[PAKKAUSKV]>					".t("Pakkaustiedon kieliversio")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Työmääräysten avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOJONO") 		$ulos .= "<option value='TYOM_TYOJONO' $avain_sel[TYOM_TYOJONO]>			".t("Työmääräysten työjono")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOSTATUS") 	$ulos .= "<option value='TYOM_TYOSTATUS' $avain_sel[TYOM_TYOSTATUS]>		".t("Työmääräysten työstatus")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOLINJA") 	$ulos .= "<option value='TYOM_TYOLINJA' $avain_sel[TYOM_TYOLINJA]>			".t("Työmääräysten työlinja")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOKENTAT") 	$ulos .= "<option value='TYOM_TYOKENTAT' $avain_sel[TYOM_TYOKENTAT]>		".t("Työmääräysten kentät")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TYOM_PRIORIT")	  	$ulos .= "<option value='TYOM_PRIORIT' $avain_sel[TYOM_PRIORIT]>			".t("Työmääräysten prioriteetti")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Viennin avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "KT") 				$ulos .= "<option value='KT' $avain_sel[KT]>								".t("Kauppatapahtuman luonne")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TULLI") 			$ulos .= "<option value='TULLI' $avain_sel[TULLI]>							".t("Poistumistoimipaikka")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KM") 				$ulos .= "<option value='KM' $avain_sel[KM]>								".t("Kuljetusmuoto")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "C") 				$ulos .= "<option value='C' $avain_sel[C]>									".t("CHN tietue")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Maksuehtojen avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "LASKUKUVAUS") 		$ulos .= "<option value='LASKUKUVAUS' $avain_sel[LASKUKUVAUS]>				".t("Maksuposition kuvaus")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KARHUVIESTI") 		$ulos .= "<option value='KARHUVIESTI' $avain_sel[KARHUVIESTI]>				".t("Karhuviesti")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "MAKSUEHTOKV")		$ulos .= "<option value='MAKSUEHTOKV' $avain_sel[MAKSUEHTOKV]>				".t("Maksuehdon kieliversio")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("CRM avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "CRM_ROOLI") 		$ulos .= "<option value='CRM_ROOLI' $avain_sel[CRM_ROOLI]>					".t("Yhteyshenkilön rooli")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "CRM_SUORAMARKKI") 	$ulos .= "<option value='CRM_SUORAMARKKI' $avain_sel[CRM_SUORAMARKKI]>		".t("Yhteyshenkilön suoramarkkinointitiedot")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Muut avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "VAKIOVIESTI") 		$ulos .= "<option value='VAKIOVIESTI' $avain_sel[VAKIOVIESTI]>				".t("Laskun vakioviesti")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "VAKIOVIESTI_TIL")	$ulos .= "<option value='VAKIOVIESTI_TIL' {$avain_sel['VAKIOVIESTI_TIL']}>	".t("Tilausvahvistuksen vakioviesti")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "KAANTALVVIESTI")	$ulos .= "<option value='KAANTALVVIESTI' $avain_sel[KAANTALVVIESTI]>		".t("Tilauksen/Laskun käänteisen verotuksen viesti")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LITETY") 			$ulos .= "<option value='LITETY' $avain_sel[LITETY]>						".t("Liitetiedostotyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TIL-LITETY") 		$ulos .= "<option value='TIL-LITETY' ".$avain_sel["TIL-LITETY"].">			".t("Tilauksen liitetiedostotyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "JAKELULISTA") 		$ulos .= "<option value='JAKELULISTA' $avain_sel[JAKELULISTA]>				".t("Email jakelulista")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LUETTELO") 			$ulos .= "<option value='LUETTELO' $avain_sel[LUETTELO]>					".t("Luettelotyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TRIVITYYPPI") 		$ulos .= "<option value='TRIVITYYPPI' $avain_sel[TRIVITYYPPI]>				".t("Tilausrivin tyyppi")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LASKUTUS_SAATE")	$ulos .= "<option value='LASKUTUS_SAATE' $avain_sel[LASKUTUS_SAATE]>		".t("Laskun sähköpostisaatekirje asiakkaalle")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "PALAUTUS_SAATE")	$ulos .= "<option value='PALAUTUS_SAATE' $avain_sel[PALAUTUS_SAATE]>		".t("Palautuksen sähköpostisaatekirje asiakkaalle")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "TV_LISATIETO") 		$ulos .= "<option value='TV_LISATIETO' $avain_sel[TV_LISATIETO]>			".t("Tilausvahvistuksen lisätiedot")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "INVEN_LAJI") 		$ulos .= "<option value='INVEN_LAJI' $avain_sel[INVEN_LAJI]>				".t("Inventoinnin laji")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "HAE_JA_SELAA") 		$ulos .= "<option value='HAE_JA_SELAA' $avain_sel[HAE_JA_SELAA]>			".t("Hae ja selaa -ohjelman parametrit")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "DYNAAMINEN_PUU") 	$ulos .= "<option value='DYNAAMINEN_PUU' $avain_sel[DYNAAMINEN_PUU]>		".t("Dynaamisen puun pääkategoriat")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "PUUTEKOMM") 		$ulos .= "<option value='PUUTEKOMM' $avain_sel[PUUTEKOMM]>					".t("Puuterivin kommentti")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "EXTRANET_TEEMA") 	$ulos .= "<option value='EXTRANET_TEEMA' $avain_sel[EXTRANET_TEEMA]>		".t("Extranet ulkoasuteema")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "SOPIMUS_KENTTA") 	$ulos .= "<option value='SOPIMUS_KENTTA' $avain_sel[SOPIMUS_KENTTA]>		".t("Ylläpitosopimuksen rivien kentät")."</option>";
		if ($lukitse_laji== "" or $lukitse_laji == "LABEL") 			$ulos .= "<option value='LABEL' {$avain_sel['LABEL']}>						".t("Myyntitilauksen luokitus")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Verkkokaupan avainsanat")."'>";
		if ($lukitse_laji== "" or $lukitse_laji == "VERKKOKAULINKKI") $ulos .= "<option value='VERKKOKAULINKKI' $avain_sel[VERKKOKAULINKKI]>".t("Verkkokauppalinkki")."</option>";
		if ($lukitse_laji== "") $ulos .= "</optgroup>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "nakyvyys") {
		if ($avain_sel["TRY"] == "SELECTED"  or $avain_sel["OSASTO"] == "SELECTED") {
			$sel = array();
			$sel[$trow[$i]] = "SELECTED";

			$ulos = "<td><select name = '$nimi'>";
			$ulos .= "<option value = ''>".t("Näytetään verkkokaupassa")."</option>";
			$ulos .= "<option value = 'E' $sel[E]>".t("Ei näytetä verkkokaupassa")."</option>";
			$ulos .= "</select></td>";
		}
		elseif ($avain_sel["TYOM_TYOKENTAT"] == "SELECTED") {
			$sel = array();
			$sel[$trow[$i]] = "SELECTED";

			$ulos = "<td><select name = '$nimi'>";
			$ulos .= "<option value = ''>".t("Näytetään työmääräyksellä")."</option>";
			$ulos .= "<option value = 'K' $sel[K]>".t("Näytetään työmääräyksellä ja laskulla")."</option>";
			$ulos .= "<option value = 'L' $sel[L]>".t("Näytetään laskulla")."</option>";
			$ulos .= "<option value = 'S' $sel[S]>".t("Sisäinen tieto")."</option>";
			$ulos .= "</select></td>";
		}
		else {
			$tyyppi = 0;
		}

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kieli") {

		$kieli = $trow[$i];

		$ulos = "<td><select name='$nimi'>";
		$sel  = "";

		//	näytetäään vain kielet joilla on
		if ($perhe > 0) {
			if ($lukitse_laji != "") {
				$laji = $lukitse_laji;
			}
			else {
				$laji = $al_laji;
			}

			$query = "	SELECT group_concat(kieli) kielet
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and perhe='$perhe' and laji = '$laji'";
			$res = mysql_query($query) or pupe_error($query);
			$kilerow = mysql_fetch_array($res);
			$kielet = $kilerow["kielet"];
		}

		$query  = "SHOW columns from sanakirja";
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

	if ($sel["KERAYSLISTA"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

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

	if ($avain_sel["RAHTIKIRJA"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

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

	if ($avain_sel["TARRATYYPPI"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		$ulos .= "<option value=''></option>";

		while ($file = readdir($handle)) {
			if (substr($file,0,16) == 'tilaustuotetarra') {
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

	if ($avain_sel["SARJANUMERON_LI"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

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

	if ($avain_sel["ALVULK"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark_2") {

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

	if (($avain_sel["KARHUVIESTI"] == "SELECTED" or $avain_sel["TV_LISATIETO"] == "SELECTED") and (mysql_field_name($result, $i) == "selite" or mysql_field_name($result, $i) == "selitetark_2" or mysql_field_name($result, $i) == "selitetark_3")) {
		$tyyppi = 0;
		$jatko  = 0;
	}

	if (($avain_sel["KARHUVIESTI"] == "SELECTED" or $avain_sel["TV_LISATIETO"] == "SELECTED" or $avain_sel['KAANTALVVIESTI'] == "SELECTED") and mysql_field_name($result, $i) == "selitetark") {
		$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (($avain_sel["OSASTO"] == "SELECTED"  or $avain_sel["TRY"] == "SELECTED") and mysql_field_name($result, $i) == "selitetark_3") {
		$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (($avain_sel['LASKUTUS_SAATE'] == "SELECTED" or $avain_sel['PALAUTUS_SAATE'] == "SELECTED" or $avain_sel['KAANTALVVIESTI'] == "SELECTED") and mysql_field_name($result, $i) == "selitetark_2") {
		$ulos = "<td><textarea rows=30 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (($avain_sel['VAKIOVIESTI'] == "SELECTED" or $avain_sel['VAKIOVIESTI_TIL']) and mysql_field_name($result, $i) == "selite") {
		$ulos = "<td><textarea rows=5 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	// Tuoteryhmissä voidaan antaa oletuskeräysprioriteetti selitetark_4 kenttään
	if ($avain_sel["TRY"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark_4") {

		$vresult = t_avainsana("ASIAKASLUOKKA");

		$ulos = "<td><select name = '$nimi'>";
		$ulos .= "<option value = ''>".t("Ei keräysprioriteettiä")."</option>";

		while ($row = mysql_fetch_assoc($vresult)) {
			$sel = ($row["selite"] == $trow[$i]) ? ' selected' : '';
			$ulos .= "<option value = '{$row["selite"]}'$sel>{$row["selite"]} - {$row["selitetark"]}</option>";
		}
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (($avain_sel["TYOM_PRIORIT"] == "SELECTED" or $avain_sel["TYOM_TYOJONO"] == "SELECTED" or $avain_sel["TYOM_TYOSTATUS"] == "SELECTED") and mysql_field_name($result, $i) == "selitetark_3") {

		if ($trow[$i] == 'OLETUS') {
			$sel = "SELECTED";
		}

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sel>".t("Ei oletus")."</option>";
		$ulos .= "<option value='OLETUS' $sel>".t("Oletus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if ($avain_sel['TYOM_TYOLINJA'] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

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

	if ($avain_sel['TYOM_TYOLINJA'] == "SELECTED" and mysql_field_name($result, $i) == "selitetark") {

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

	if ($avain_sel["RAHTIKIRJA"] == "SELECTED" and mysql_field_name($result, $i) == "selitetark_2") {

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

	if ($avain_sel['VARASTORYHMA'] == 'SELECTED' and mysql_field_name($result, $i) == "selitetark") {
		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'";
		$varasto_res = mysql_query($query) or pupe_error($query);

		$ulos .= "<td>";

		$varastot = explode(",", $trow[$i]);

		while ($varasto_row = mysql_fetch_assoc($varasto_res)) {
			$chk = in_array($varasto_row['tunnus'], $varastot) ? ' checked' : '';
			$ulos .= "<input type='checkbox' name='varastot_array[]' value='$varasto_row[tunnus]'$chk> $varasto_row[nimitys]<br/>";
		}

		$ulos .= "</td>";
		$jatko = 0;
	}

	if ($avain_sel["HAE_JA_SELAA"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {
		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos = "<td><select name = '$nimi'>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Hae ja selaa -ohjelmassa käytetään alkuperäistä ulkoasua")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Hae ja selaa -ohjelmassa käytetään uutta ulkoasua")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jarjestys") {
		if ($trow[$i] == "" and $ajarjestys != "") {
			$trow[$i] = $ajarjestys;
		}
	}
