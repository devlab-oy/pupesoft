<?php

	if ($_POST["toim"] == "yhtion_parametrit") {
		$apucss = $_POST["css"];
		$apucsspieni = $_POST["css_pieni"];
		$apucssextranet = $_POST["css_extranet"];
		$apucssverkkokauppa = $_POST["css_verkkokauppa"];
	}
	else {
		unset($apucss);
		unset($apucsspieni);
		unset($apucssextranet);
	}

	if ($_POST["toim"] == "tuote") {
		$aputuotteen_parametrit = $_POST["tuotteen_parametrit"];
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "yllapito.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}
	
	if(!function_exists("vaihtoehdot")) {
		function vaihtoehdot($i, $taulu, $value, $text, $selected, $opts="") {
			global  $yhtiorow, $kukarow;

			if($opts["where"] != "") {
				$where = " and ".$opts["where"];
			}

			if($opts["perustauusi"] != "") {
				$pretext = t("Valitse jo perustettu listalta").":<br>";
				$posttext = "<br><br>".t("Tai perusta uusi").":<br><input type='text' name='t[{$i}_uusi]' value=''>";
			}

			$ulos = "$pretext<select name='t[$i]'>
						<option value=''>".t("Ei valintaa")."</option>";


			$query = "	SELECT distinct $value value, $text text
						FROM $taulu
						WHERE yhtio='$kukarow[yhtio]' and ($value != '' or $text != '') $where";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result)>0) {
				while($row = mysql_fetch_array($result)) {
					if($selected == $row["value"]) {
						$sel = "SELECTED";
					}
					else {
						$sel = "";
					}

					$ulos .= "<option value='$row[value]' $sel>$row[text]</option>\n\t";
				}
			}

			$ulos .= "</select>$posttext";

			return $ulos;
		}
	}

	//Jotta m‰‰ritelty rajattu n‰kym‰ olisi myˆs k‰yttˆoikeudellisesti tiukka
	$aputoim = $toim;
	list($toim, $alias_set, $rajattu_nakyma) = explode('!!!', $toim);


	// Tutkitaan v‰h‰n alias_settej‰ ja rajattua n‰kym‰‰
	$al_lisa = " and selitetark_2 = '' ";
	
	if ($alias_set != '') {
		if ($rajattu_nakyma != '') {
			$al_lisa = " and selitetark_2 = '$alias_set' ";
		}
		else {
			$al_lisa = " and (selitetark_2 = '$alias_set' or selitetark_2 = '') ";
		}
	}

	// pikkuh‰kki, ettei rikota css kentt‰‰
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucss)) {
		$t[$cssi] = $apucss;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucsspieni)) {
		$t[$csspienii] = $apucsspieni;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucssextranet)) {
		$t[$cssextraneti] = $apucssextranet;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucssverkkokauppa)) {
		$t[$cssverkkokauppa] = $apucssverkkokauppa;
	}
	if ($_POST["toim"] == "tuote" and isset($aputuotteen_parametrit)) {
		$t[$tuotteen_parametriti] = $aputuotteen_parametrit;
	}

	$rajauslisa	= "";

	require ("inc/$toim.inc");
	
	if($lukitse_laji == "MEHTOTXT") {
		$otsikko = "Maksuehdon k‰‰nnˆkset";
	}
	elseif($lukitse_laji == "MEHTOKATXT") {
		$otsikko = "Kassatekstin k‰‰nnˆkset";
	}
	
	if ($otsikko == "") {
		$otsikko = $toim;
	}
	if ($otsikko_nappi == "") {
		$otsikko_nappi = $toim;
	}
	
	echo "<font class='head'>".t("$otsikko")."</font><hr>";
	
	if ($from == "yllapito") {
		echo "
		<script LANGUAGE='JavaScript'>
			function resizeIframe(frameid, offset){

				var currentfr=window.parent.document.getElementById(frameid);
				if(!offset) offset=0;

				if (currentfr && !window.opera){

					var height = 100;

					try {
						height = currentfr.contentDocument.body.offsetHeight;
					}
					catch (err) {
						height = currentfr.Document.body.scrollHeight;
					}

					currentfr.height = height+offset+10;
					currentfr.style.height = height+offset+10;

					setTimeout(\"window.parent.document.getElementById('\"+frameid+\"').style.display='block';\", 300);

					//ns6 syntax
				}
			}

		</script><br>";
		
	}
	
	// Saako paivittaa
	if ($oikeurow['paivitys'] != '1') {
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa")."</b><br>";
			$uusi = '';
			exit;
		}
		if ($del == 1 or $del == 2) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
			exit;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
			exit;
		}
	}

	// Tietue poistetaan
	if ($del == 1) {

		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		$query = "	DELETE from $toim
					WHERE tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

		//	Jos poistetaan perheen osa palataan perheelle
		if($seuraavatunnus > 0) $tunnus = $seuraavatunnus;
		else $tunnus = 0;

		$seuraavatunnus = 0;

		// Siirryt‰‰n takaisin sielt‰ mist‰ tultiin
		if ($lopetus != '') {
			lopetus($lopetus, "META");
		}
	}

	if ($del == 2) {
		
		if (count($poista_check) > 0) {
			foreach ($poista_check as $poista_tunnus) {
				$query = "	SELECT *
							FROM $toim
							WHERE tunnus = '$poista_tunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($result);

				$query = "	DELETE from $toim
							WHERE tunnus='$poista_tunnus'";
				$result = mysql_query($query) or pupe_error($query);

				synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");
			}
		}					
	}

	// Jotain p‰ivitet‰‰n tietokontaan
	if ($upd == 1) {

		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);
				
		//	Tehd‰‰n muuttujista linkit jolla luomme otsikolliset avaimet!
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			if($t["{$i}_uusi"] != "") {
				$t[$i] = $t["{$i}_uusi"];
			}
			$t[mysql_field_name($result, $i)] = &$t[$i];
		}

		// Tarkistetaan
		$errori = '';
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {

			//P‰iv‰m‰‰r‰ spesiaali
			if (isset($tpp[$i])) {
				if ($tvv[$i] < 1000 and $tvv[$i] > 0) $tvv[$i] += 2000;

				$t[$i] = sprintf('%04d', $tvv[$i])."-".sprintf('%02d', $tkk[$i])."-".sprintf('%02d', $tpp[$i]);

				if(!@checkdate($tkk[$i],$tpp[$i],$tvv[$i]) and ($tkk[$i]!= 0 or $tpp[$i] != 0)) {
					$virhe[$i] = t("Virheellinen p‰iv‰m‰‰r‰");
					$errori = 1;
				}
			}

			// Tarkistetaan saako k‰ytt‰j‰ p‰ivitt‰‰ t‰t‰ kentt‰‰
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) == 0 and $rajattu_nakyma != '' and isset($t[$i])) {
				$virhe[$i] = t("Sinulla ei ole oikeutta p‰ivitt‰‰ t‰t‰ kentt‰‰");
				$errori = 1;
			}

			$tiedostopaate = "";

			$funktio = $toim."tarkista";

			if(!function_exists($funktio)) {
				require("inc/$funktio.inc");
			}

			if(function_exists($funktio)) {
				if ($toim == "tuote") {
					@$funktio($t, $i, $result, $tunnus, &$virhe, $trow, $tuotteen_parametrit_keys, $tuotteen_parametrit_vals);
				}
				else {
					@$funktio($t, $i, $result, $tunnus, &$virhe, $trow);
				}
			}

			if($virhe[$i] != "") {
				$errori = 1;
			}

			//	Tarkastammeko liitetiedoston?
			if(is_array($tiedostopaate) and is_array($_FILES["liite_$i"])) {
				$viesti = tarkasta_liite("liite_$i", $tiedostopaate);
				if($viesti !== true) {
					$virhe[$i] = $viesti;
					$errori = 1;
				}
			}
		}

		// Jos toimittaja/asiakas merkataan poistetuksi niin unohdetaan kaikki errortsekit...
		if (($toimtyyppi == "P" or $toimtyyppi == "PP" or $asiak_laji == "P") and $errori != '') {						
			unset($virhe);
			$errori = "";				
		}
		elseif ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivitt‰‰!")."</font>";
		}

		// Luodaan tietue
		if ($errori == "") {
			if ($tunnus == "") {
				// Taulun ensimm‰inen kentt‰ on aina yhtiˆ
				$query = "INSERT into $toim SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i])) {
						
						if(is_array($_FILES["liite_$i"])) {
							$id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result,$i), $t[$i]);
							if($id !== false) {
								$t[$i] = $id;
							}
						}
						
						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);

						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";
					}
				}				
			}
			// P‰ivitet‰‰n
			else {

				//	Jos poistettiin jokin liite, poistetaan se nyt
				if(is_array($poista_liite)) {
					foreach($poista_liite as $key => $val) {
						if($val > 0) {
							$delquery = " DELETE FROM liitetiedostot WHERE yhtio = '{$kukarow["yhtio"]}' and liitos = 'Yllapito' and tunnus = '$val'";
							$delres = mysql_query($delquery);
							if(mysql_affected_rows() == 1) {
								$t[$key] = "";
							}
						}
					}
				}

				// Taulun ensimm‰inen kentt‰ on aina yhtiˆ
				$query = "UPDATE $toim SET muuttaja='$kukarow[kuka]', muutospvm=now() ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i]) or is_array($_FILES["liite_$i"])) {

						if(is_array($_FILES["liite_$i"])) {
							$id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result,$i), $t[$i]);
							if($id !== false) {
								$t[$i] = $id;
							}
						}

						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);
						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";

					}
				}

				$query .= " where yhtio='$kukarow[yhtio]' and tunnus = $tunnus";
			}

			$result = mysql_query($query) or pupe_error($query);

			if ($tunnus == '') {
				$tunnus = mysql_insert_id();
				$wanha = "";
			}
			else {
				//	Javalla tieto ett‰ t‰t‰ muokattiin..
				$wanha = "P_";
			}

			if ($tunnus > 0 and isset($paivita_myos_avoimet_tilaukset) and $toim == "asiakas") {

				$query = "	SELECT *
							FROM asiakas
							WHERE tunnus = '$tunnus'
							and yhtio 	 = '$kukarow[yhtio]'";
				$otsikres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($otsikres) == 1) {
					$otsikrow = mysql_fetch_array($otsikres);

					$query = "	SELECT tunnus
								FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
								WHERE yhtio = '$kukarow[yhtio]'
								and (
										(tila IN ('L','N','R','V','E') AND alatila != 'X')
										OR
										(tila = 'T' AND alatila in ('','A'))
										OR
										(tila = '0')
									)
								and liitostunnus = '$otsikrow[tunnus]'
								and tapvm = '0000-00-00'";
					$laskuores = mysql_query($query) or pupe_error($query);

					while ($laskuorow = mysql_fetch_array($laskuores)) {
						
						if (trim($otsikrow["toim_nimi"]) == "") {
							$otsikrow["toim_nimi"]		= $otsikrow["nimi"];
							$otsikrow["toim_nimitark"]	= $otsikrow["nimitark"];
							$otsikrow["toim_osoite"]	= $otsikrow["osoite"];
							$otsikrow["toim_postino"]	= $otsikrow["postino"];
							$otsikrow["toim_postitp"]	= $otsikrow["postitp"];
							$otsikrow["toim_maa"]		= $otsikrow["maa"];
						}
						
						
						$query = "	UPDATE lasku
									SET ytunnus			= '$otsikrow[ytunnus]',
									ovttunnus			= '$otsikrow[ovttunnus]',
									nimi				= '$otsikrow[nimi]',
									nimitark			= '$otsikrow[nimitark]',
									osoite 				= '$otsikrow[osoite]',
									postino 			= '$otsikrow[postino]',
									postitp				= '$otsikrow[postitp]',
									maa   				= '$otsikrow[maa]',
									chn					= '$otsikrow[chn]',
									verkkotunnus		= '$otsikrow[verkkotunnus]',
									vienti				= '$otsikrow[vienti]',
									toim_ovttunnus 		= '$otsikrow[toim_ovttunnus]',
									toim_nimi      		= '$otsikrow[toim_nimi]',
									toim_nimitark  		= '$otsikrow[toim_nimitark]',
									toim_osoite    		= '$otsikrow[toim_osoite]',
									toim_postino  		= '$otsikrow[toim_postino]',
									toim_postitp 		= '$otsikrow[toim_postitp]',
									toim_maa    		= '$otsikrow[toim_maa]'
									WHERE yhtio 		= '$kukarow[yhtio]'
									and tunnus			= '$laskuorow[tunnus]'";
						$updaresult = mysql_query($query) or pupe_error($query);

						$query = "	UPDATE laskun_lisatiedot
									SET kolm_ovttunnus	= '$otsikrow[kolm_ovttunnus]',
									kolm_nimi   		= '$otsikrow[kolm_nimi]',
									kolm_nimitark		= '$otsikrow[kolm_nimitark]',
									kolm_osoite  		= '$otsikrow[kolm_osoite]',
									kolm_postino  		= '$otsikrow[kolm_postino]',
									kolm_postitp 		= '$otsikrow[kolm_postitp]',
									kolm_maa    		= '$otsikrow[kolm_maa]',
									laskutus_nimi   	= '$otsikrow[laskutus_nimi]',
									laskutus_nimitark	= '$otsikrow[laskutus_nimitark]',
									laskutus_osoite  	= '$otsikrow[laskutus_osoite]',
									laskutus_postino  	= '$otsikrow[laskutus_postino]',
									laskutus_postitp 	= '$otsikrow[laskutus_postitp]',
									laskutus_maa    	= '$otsikrow[laskutus_maa]'									
									WHERE yhtio 		= '$kukarow[yhtio]'
									and otunnus			= '$laskuorow[tunnus]'";
						$updaresult = mysql_query($query) or pupe_error($query);

					}
				}
			}

			//	T‰m‰ funktio tekee myˆs oikeustarkistukset!
			synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");
			
			if(in_array(strtoupper($toim), array("ASIAKAS", "ASIAKKAAN_KOHDE")) and $yhtiorow["dokumentaatiohallinta"] != "") {
				svnSyncMaintenanceFolders(strtoupper($toim), $tunnus, $trow);
			}
			
			
			// Siirryt‰‰n takaisin sielt‰ mist‰ tultiin
			if ($lopetus != '' and (isset($yllapitonappi) or isset($paivita_myos_avoimet_tilaukset))) {
				
				// Jotta urlin parametrissa voisi p‰‰ss‰t‰ toisen urlin parametreineen
				$lopetus = str_replace('////','?', $lopetus);
				$lopetus = preg_replace('/([^:])\/\//','\\1&',  $lopetus);
				

				if (strpos($lopetus, "?") === FALSE) {
					$lopetus .= "?";
				}
				else {
					$lopetus .= "&";
				}

				$lopetus .= "yllapidossa=$toim&yllapidontunnus=$tunnus";

				
				if (isset($paivita_myos_avoimet_tilaukset)) {
					$lopetus.= "&tiedot_laskulta=YES";
				}
				elseif (strpos($lopetus, "tilaus_myynti.php") !== FALSE and $toim == "asiakas") {
					$lopetus.= "&asiakasid=$tunnus";
				}
			
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
				exit;
			}
			
			if($ajax_menu_yp != "") {
				$suljeYllapito = $ajax_menu_yp;
			}
			
			if(substr($suljeYllapito, 0, 15) == "asiakkaan_kohde") {
				$query = "SELECT kohde from asiakkaan_kohde where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>suljeYllapito('$wanha$suljeYllapito','$tunnus','$tunnus - $aburow[kohde]');</SCRIPT>";
				exit;
			}

			if(substr($suljeYllapito, 0, 17) == "asiakkaan_positio") {
				$query = "SELECT positio from asiakkaan_positio where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>suljeYllapito('$wanha$suljeYllapito','$tunnus','$tunnus - $aburow[positio]');</SCRIPT>";
				exit;
			}

			if(substr($suljeYllapito, 0, 13) == "yhteyshenkilo") {
				$query = "SELECT nimi from yhteyshenkilo where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				die("<script LANGUAGE='JavaScript'>suljeYllapito('$wanha$suljeYllapito','$tunnus','$aburow[nimi]', '$ajax_menu_yp');</script></body></html>");
			}

			$uusi = 0;

			if (isset($yllapitonappi) and $lukossa != "ON" or isset($paluunappi)) {
				
				$tmp_tuote_tunnus  = 0;
				
				if ($toim == "tuote") {
					$tmp_tuote_tunnus  = $tunnus;					
				}
								
				$tunnus  = 0;
				$kikkeli = 0;
								
			}
			else {
				$kikkeli = 1;
			}
		}
	}

	// Rakennetaan hakumuuttujat kuntoon
	if (isset($hakukentat)) {
		$array = split(",", $hakukentat);
	}
	else {
		$array = split(",", $kentat);
	}

	$count = count($array);

	for ($i=0; $i<=$count; $i++) {
    	if (strlen($haku[$i]) > 0) {
			
			if (($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'asiakashinta') and trim($array[$i]) == 'asiakas') {
				
				if (!is_numeric($haku[$i])) {
					// haetaan laskutus-asiakas
					$ashak = "SELECT group_concat(tunnus) tunnukset FROM asiakas WHERE yhtio='$kukarow[yhtio]' and nimi like '%" . $haku[$i] . "%'";
					$ashakres = mysql_query($ashak) or pupe_error($ashak);
					$ashakrow = mysql_fetch_array($ashakres);
										
					if ($ashakrow["tunnukset"] != "") {
						$lisa .= " and " . $array[$i] . " in (" . $ashakrow["tunnukset"] . ")";						
					}
					else {
						$lisa .= " and " . $array[$i] . " = NULL ";							
					}
				}
				else {
					$lisa .= " and " . $array[$i] . " = '" . $haku[$i] . "'";
				}
			}
			elseif (($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'asiakashinta') and trim($array[$i]) == 'ytunnus') {
				
				if (!is_numeric($haku[$i])) {
					// haetaan laskutus-asiakas
					$ashak = "SELECT group_concat(distinct concat('\'',ytunnus,'\'')) tunnukset FROM asiakas WHERE yhtio='$kukarow[yhtio]' and (nimi like '%" . $haku[$i] . "%' or ytunnus like '%" . $haku[$i] . "%')";
					$ashakres = mysql_query($ashak) or pupe_error($ashak);
					$ashakrow = mysql_fetch_array($ashakres);
										
					if ($ashakrow["tunnukset"] != "") {
						$lisa .= " and " . $array[$i] . " in (" . $ashakrow["tunnukset"] . ")";
					}
					else {
						$lisa .= " and " . $array[$i] . " = NULL ";	
					}
				}
				else {
					$lisa .= " and " . $array[$i] . " = '" . $haku[$i] . "'";
				}
			}
			elseif ($haku[$i]{0} == "@") {
				$lisa .= " and " . $array[$i] . " = '" . substr($haku[$i], 1) . "'";
			}
			elseif (strpos($array[$i], "/") !== FALSE) {
				list($a, $b) = explode("/", $array[$i]);

				$lisa .= " and (".$a." like '%".$haku[$i]."%' or ".$b." like '%".$haku[$i]."%') ";
			}
			else {
				$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			}

			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
    	}
    }
	
    if (strlen($ojarj) > 0) {
    	$jarjestys = $ojarj." ";
    }
	
	//	S‰ilytet‰‰n ohjeen tila
	if($from == "yllapito") {
		$ulisa.="&ohje=off&from=$from&lukitse_avaimeen=$lukitse_avaimeen&lukitse_laji=$lukitse_laji";
	}
	
	//	Pidet‰‰n oletukset tallessa!
	if(is_array($oletus)){
		foreach($oletus as $o => $a) {
			$ulisa.="&oletus[$o]=$a";
		}
	}
	

	// Nyt selataan
	if ($tunnus == 0 and $uusi == 0 and $errori == '') {
		
		if ($toim == "asiakasalennus" or $toim == "asiakashinta") {
			print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function toggleAll(toggleBox) {

					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;
					var nimi = toggleBox.name;

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {											
						if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}

				function verifyMulti(){
					msg = '".t("Haluatko todella poistaa tietueet?")."';
					return confirm(msg);
				}
						
				//-->
				</script>";
		}
		
		if ($limit != "NO") {
			$limiitti = " LIMIT 350";
		}
		else {
			$limiitti = "";
		}

		$query = "SELECT " . $kentat . " FROM $toim WHERE yhtio = '$kukarow[yhtio]' $lisa $rajauslisa $prospektlisa";
        $query .= "$ryhma ORDER BY $jarjestys $limiitti";
		$result = mysql_query($query) or pupe_error($query);

		if ($toim != "yhtio" and $toim != "yhtion_parametrit") {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa";
			if ($liitostunnus) echo "&liitostunnus=$liitostunnus";
			echo "' method = 'post'>
					<input type = 'hidden' name = 'uusi' value = '1'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
					<input type = 'hidden' name = 'limit' value = '$limit'>
					<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
		}

		if (mysql_num_rows($result) >= 350) {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
					<input type = 'hidden' name = 'limit' value = 'NO'>
					<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("N‰yt‰ kaikki")."'></form>";
		}
		
		echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'nayta_poistetut' value = 'YES'>
				<input type = 'hidden' name = 'laji' value = '$laji'>
				<input type = 'submit' value = '".t("N‰yt‰ poistetut")."'></form>";
				
		if ($toim == "tuote" and $uusi != 1 and $errori == '' and $tmp_tuote_tunnus > 0) {
		
			$query = "	SELECT *
						FROM tuote
						WHERE tunnus = '$tmp_tuote_tunnus'";
			$nykyinenresult = mysql_query($query) or pupe_error($query);
			$nykyinentuote = mysql_fetch_array($nykyinenresult);

			$query = "	SELECT tunnus
						FROM tuote use index (tuoteno_index)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		< '$nykyinentuote[tuoteno]'
						ORDER BY tuoteno desc
						LIMIT 1";
			$noperes = mysql_query($query) or pupe_error($query);
			$noperow = mysql_fetch_array($noperes);

			
			echo "<form action = 'yllapito.php' method = 'post'>";
			echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
			echo "<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>";
			echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
			echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
			echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>";
			echo "<input type = 'hidden' name = 'tunnus' value = '".$noperow[tunnus]."'>";
			echo " <input type='submit' value='".t("Edellinen tuote")."'>";
			echo "</form>";

			$query = "	SELECT tunnus
						FROM tuote use index (tuoteno_index)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		> '$nykyinentuote[tuoteno]'
						ORDER BY tuoteno
						LIMIT 1";
			$yesres = mysql_query($query) or pupe_error($query);
			$yesrow = mysql_fetch_array($yesres);

			echo "<form action = 'yllapito.php' method = 'post'>";
			echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
			echo "<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>";
			echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
			echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
			echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>";
			echo "<input type = 'hidden' name = 'tunnus' value = '".$yesrow[tunnus]."'>";
			echo " <input type='submit' value='".t("Seuraava tuote")."'>";
			echo "</form>";
			
		}
			
		echo "	<br><br><table><tr class='aktiivi'>
				<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
				<input type = 'hidden' name = 'laji' value = '$laji'>";
		
		if($from == "yllapito") {
			for ($i = 1; $i < mysql_num_fields($result); $i++) {			
				if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {		
					echo "<th valign='top'>".t(mysql_field_name($result,$i))."</th>";
				}
			}
		}
		else {
			for ($i = 1; $i < mysql_num_fields($result); $i++) {			
				if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {		
					echo "<th valign='top'><a href='yllapito.php?toim=$aputoim&ojarj=".mysql_field_name($result,$i).$ulisa."&limit=$limit&nayta_poistetut=$nayta_poistetut&laji=$laji'>" . t(mysql_field_name($result,$i)) . "</a>";

					if 	(mysql_field_len($result,$i)>10) $size='15';
					elseif	(mysql_field_len($result,$i)<5)  $size='5';
					else	$size='10';

					// jos meid‰n kentt‰ ei ole subselect niin tehd‰‰n hakukentt‰
					if (strpos(strtoupper($array[$i]), "SELECT") === FALSE) {
						echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
					}
					echo "</th>";
				}
			}

			if (($toim == "asiakasalennus" or $toim == "asiakashinta") and $oikeurow['paivitys'] == 1) {
				echo "<th valign='top'>".t("Poista")."</th>";
			}
			
			echo "<td class='back' valign='bottom'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></form>";
			echo "</tr>";
			
		}
						
		if (($toim == "asiakasalennus" or $toim == "asiakashinta") and $oikeurow['paivitys'] == 1) {
			echo "<tr><form action='yllapito.php?ojarj=$ojarj$ulisa' name='ruksaus' method='post' onSubmit = 'return verifyMulti()'>
					    <input type = 'hidden' name = 'toim' value = '$aputoim'>
						<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
						<input type = 'hidden' name = 'limit' value = '$limit'>
						<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
						<input type = 'hidden' name = 'laji' value = '$laji'>
						<input type = 'hidden' name = 'del' value = '2'></tr>";
		}
		
		while ($trow = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>";
						
			if (($toim == "asiakas" and $trow["HIDDEN_laji"] == "P") or
				($toim == "toimi" and $trow["HIDDEN_tyyppi"] == "P") or
				(($toim == "yriti" or $toim == 'maksuehto') and $trow["HIDDEN_kaytossa"] == "E") or
				($toim == "tuote" and $trow["HIDDEN_status"] == "P")) {
					
				$fontlisa1 = "<font style='text-decoration: line-through'>";
				$fontlisa2 = "</font>";
			}
			else {
				$fontlisa1 = "";
				$fontlisa2 = "";
			}
			
			for ($i=1; $i < mysql_num_fields($result); $i++) {
				if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {
					if ($i == 1) {
						if (trim($trow[1]) == '' or (is_numeric($trow[1]) and $trow[1] == 0)) $trow[1] = "".t("*tyhj‰*")."";
						echo "<td valign='top'><a name='$trow[0]' href='yllapito.php?ojarj=$ojarj$ulisa&toim=$aputoim&tunnus=$trow[0]&limit=$limit&nayta_poistetut=$nayta_poistetut&laji=$laji'>$trow[1]</a></td>";
					}
					else {
						if (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int') {
							echo "<td valign='top' align='right'>$fontlisa1 $trow[$i] $fontlisa2</td>";
						}
						elseif (mysql_field_type($result,$i) == 'date') {
							echo "<td valign='top'>$fontlisa1 ".tv1dateconv($trow[$i])." $fontlisa2</td>";	
						}
						else {
							echo "<td valign='top'>$fontlisa1 $trow[$i] $fontlisa2</td>";
						}
					}
				}
			}
			
			if (($toim == "asiakasalennus" or $toim == "asiakashinta") and $oikeurow['paivitys'] == 1) {
				echo "<td><input type = 'checkbox' name = 'poista_check[]' value = '{$trow[0]}'></td>";
				
			}
			
			echo "</tr>";
		}
		
		if (($toim == "asiakasalennus" or $toim == "asiakashinta") and $oikeurow['paivitys'] == 1) {
			$span = mysql_num_fields($result)-2;
			echo "<tr>";
			echo "<td class='tumma'><input type = 'submit' value = '".t("Poista ruksatut tietueet")."'></td>";
			echo "<td class='tumma' colspan='$span' align='right'>".t("Ruksaa kaikki")."</td>";
			echo "<td class='tumma'><input type = 'checkbox' name = 'poi' onclick='toggleAll(this)'></td>";
			echo "</tr>";	
						
			echo "</form>";			
		}
		
		echo "</table>";
	}

	// Nyt n‰ytet‰‰n vanha tai tehd‰‰n uusi(=tyhj‰)
	if ($tunnus > 0 or $uusi != 0 or $errori != '') {
		if ($oikeurow['paivitys'] != 1) {
			echo "<b>".t("Sinulla ei ole oikeuksia p‰ivitt‰‰ t‰t‰ tietoa")."</b><br>";
		}
		
		if($ajax_menu_yp!="") {
			$ajax_post="ajaxPost('mainform', '{$palvelin2}yllapito.php?ojarj=$ojarj$ulisa#$tunnus' , 'ajax_menu_yp'); return false;";
		}
		
		echo "<form action = 'yllapito.php?ojarj=$ojarj$ulisa#$tunnus' name='mainform' id='mainform' method = 'post' autocomplete='off' enctype='multipart/form-data' onSubmit=\"$ajax_post\">";
		echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
		echo "<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>";
		echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
		echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
		echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
		echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
		echo "<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>";
		echo "<input type = 'hidden' name = 'upd' value = '1'>";

		// Kokeillaan geneerist‰
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);
		
		echo "<table><tr><td class='back' valign='top'>";

		if($from != "yllapito") {
			if($toim == "tuoteno") {
				$tuoteno = $trow["tuoteno"];
			}
			elseif($toim == "maksuehto") {
				$teksti = $trow["teksti"];
				$kateksti = $trow["kassa_teksti"];
			}			
		}
		
		echo "<table>";
		
		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

			$nimi = "t[$i]";

			if (isset($t[$i])) {
				$trow[$i] = $t[$i];
			}
			// Haetaan passatut oletukset arvoiksi!
			elseif($uusi == 1) {
				$trow[$i] = $oletus[mysql_field_name($result, $i)];
			}
			
			
			
			if (strlen($trow[$i]) > 35) {
				$size = strlen($trow[$i])+2;
			}
			elseif (mysql_field_len($result,$i)>10) {
				$size = '35';
			}
			elseif (mysql_field_len($result,$i)<5) {
				$size = '5';
			}
			else {
				$size = '10';
			}
						
			$maxsize = mysql_field_len($result,$i); // Jotta t‰t‰ voidaan muuttaa

			require ("inc/$toim"."rivi.inc");

			// N‰it‰ kentti‰ ei ikin‰ saa p‰ivitt‰‰ k‰yttˆliittym‰st‰
			if (mysql_field_name($result, $i) == "laatija" or
				mysql_field_name($result, $i) == "muutospvm" or
				mysql_field_name($result, $i) == "muuttaja" or
				mysql_field_name($result, $i) == "luontiaika") {
				$tyyppi = 2;
			}

			//Haetaan tietokantasarakkeen nimialias
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) > 0) {
				$al_row = mysql_fetch_array($al_res);

				if ($al_row["selitetark"] != '') {
					$otsikko = t($al_row["selitetark"]);
				}
				else {
					$otsikko = t(mysql_field_name($result, $i));
				}
			}
			else {
				switch (mysql_field_name($result,$i)) {
					case "printteri0":
						$otsikko = t("Ker‰yslista");
						break;
					case "printteri1":
						$otsikko = t("L‰hete");
						break;
					case "printteri2":
						$otsikko = t("Tuotetarrat");
						break;
					case "printteri3":
						$otsikko = t("Osoitelappu");
						break;
					case "printteri4":
						$otsikko = t("Rahtikirja A5");
						break;
					case "printteri5":
						$otsikko = t("Lasku");
						break;
					case "printteri6":
						$otsikko = t("Rahtikirja A4");
						break;
					case "printteri7":
						$otsikko = t("JV-lasku/-kuitti");
						break;
					default:
						$otsikko = t(mysql_field_name($result, $i));
				}

				if ($rajattu_nakyma != '') {
 					$ulos = "";
 					$tyyppi = 0;
 				}
			}

			// $tyyppi --> 0 rivi‰ ei n‰ytet‰ ollenkaan
			// $tyyppi --> 1 rivi n‰ytet‰‰n normaalisti
			// $tyyppi --> 1.5 rivi n‰ytet‰‰n normaalisti ja se on p‰iv‰m‰‰r‰kentt‰
			// $tyyppi --> 2 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, eik‰ sen arvoa p‰vitet‰
			// $tyyppi --> 3 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, mutta sen arvo p‰ivitet‰‰n
			// $tyyppi --> 4 rivi‰ ei n‰ytet‰ ollenkaan, mutta sen arvo p‰ivitet‰‰n
			// $tyyppi --> 5 liitetiedosto

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "<tr>";
				echo "<th align='left'>$otsikko</th>";
			}

			if ($jatko == 0) {
				if($yllapito_tarkista_oikeellisuus!="") {
					//	Tehd‰‰n tarkastuksia, T‰m‰ sallisi myˆs muiden tagien "oikeellisuuden" m‰‰rittelemisen suhteellisen helposti
					//	Jostainsyyst‰ multiline ei toimi kunnolla?
					$search = "/.*<(select)[^>]*>(.*)<\/select>.*/mi";
					preg_match($search, $ulos, $matches);
					if(strtolower($matches[1]) == "select") {
						$search = "/\s+selected\s*>/i";
						preg_match($search, $matches[2], $matches2);
						if(count($matches2)==0) {
							$ulos .= "<td class='back'><font class='error'>OBS!! '".$trow[$i]."'</font></td>";
						}
					}
				}
				echo $ulos;
			}
			elseif ($tyyppi == 1) {
				echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			}
			elseif ($tyyppi == 1.5) {
				$vva = substr($trow[$i],0,4);
				$kka = substr($trow[$i],5,2);
				$ppa = substr($trow[$i],8,2);

				echo "<td>
						<input type = 'text' name = 'tpp[$i]' value = '$ppa' size='3' maxlength='2'>
						<input type = 'text' name = 'tkk[$i]' value = '$kka' size='3' maxlength='2'>
						<input type = 'text' name = 'tvv[$i]' value = '$vva' size='5' maxlength='4'></td>";
			}
			elseif ($tyyppi == 2) {
				echo "<td>$trow[$i]</td>";
			}
			elseif($tyyppi == 3) {
				echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
			}
			elseif($tyyppi == 4) {
				echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
			}
			elseif($tyyppi == 5) {
				echo "<td>";

				if($trow[$i] > 0) {
					echo "<a href='view.php?id=".$trow[$i]."'>".t("N‰yt‰ liitetiedosto")."</a><input type = 'hidden' name = '$nimi' value = '$trow[$i]'> ".("Poista").": <input type = 'checkbox' name = 'poista_liite[$i]' value = '{$trow[$i]}'>";
				}
				else {
					echo "<input type = 'text' name = '$nimi' value = '$trow[$i]'>";
				}

				echo "<input type = 'file' name = 'liite_$i'></td>";
			}

			if (isset($virhe[$i])) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
			}

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "</tr>";
			}
		}
		echo "</table>";

		if ($uusi == 1) {
			$nimi = t("Perusta $otsikko_nappi");
		}
		else {
			$nimi = t("P‰ivit‰ $otsikko_nappi");
		}
		
		if ($ajax_menu_yp!="") {
			echo "<br><input type = 'submit' name='yllapitonappi' value = '$nimi' onClick=\"$ajax_post\">";
		}
		else {
			echo "<br><input type = 'submit' name='yllapitonappi' value = '$nimi'>";
		}

		if ($toim == "asiakas" and $uusi != 1) {
			echo "<br><input type = 'submit' name='paivita_myos_avoimet_tilaukset' value = '$nimi ".t("ja p‰ivit‰ tiedot myˆs avoimille tilauksille")."'>";
		}

		if($lukossa == "ON") {
			echo "<input type='hidden' name='lukossa' value = '$lukossa'>";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type = 'submit' name='paluunappi' value = '".t("Palaa avainsanoihin")."'>";
		}

		echo "</td>";
		echo "<td class='back' valign='top'>";
		
		if ($errori == '' and $toim == "sarjanumeron_lisatiedot") {
			@include ("inc/arviokortti.inc");
		}
		
		// Yll‰pito.php:n formi kiinni vasta t‰ss‰
		echo "</form>";
		
		if ($errori == '' and $uusi != 1) {
			include ("inc/yllapito_linkit.inc");
		}
				
		if ($errori == '' and $toim == "avainsana" and $from != "yllapito") {
			require ("inc/avainsanaperhe.inc");
		}

		if ($errori == '' and $uusi != 1 and $toim == "yhtio") {
			require ("inc/yhtion_toimipaikat.inc");
		}

		if ($errori == '' and ($toim == "toimi" or $toim == "asiakas")) {
			
			if ($toim == "asiakas") {
				$laji = "A";
			}
			elseif ($toim == "toimi") {
				$laji = "T";
			}

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='yhteyshenkilo' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);
			
			if(mysql_num_rows($res) > 0) echo "<iframe id='yhteyshenkilo_iframe' name='yhteyshenkilo_iframe' src='yllapito.php?toim=yhteyshenkilo&from=yllapito&laji=$laji&ohje=off&haku[8]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' scrolling='no' border='0' frameborder='0'></iFrame>";
						
			if ($toim == "asiakas") {
				include ("inc/asiakkaan_avainsanat.inc");
			}
		}

		if ($errori == '' and ($toim == "sarjanumeron_lisatiedot" or ($toim == "tuote" and $laji != "V") or (($toim == "avainsana") and (strtolower($laji) == "osasto" or strtolower($laji) == "try" or strtolower($laji) == "tuotemerkki")))) {
			require ("inc/liitaliitetiedostot.inc");
		}
		
		if ($from != "yllapito" and $errori == "") {
			if ($toim == "tuote" and $laji != "V") {
				
				$lukitse_avaimeen = $tuoteno;
									
				$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='tuotteen_toimittajat' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
				$res = mysql_query($queryoik) or pupe_error($queryoik);
				
				if(mysql_num_rows($res) > 0) echo "<iframe id='tuotteen_toimittajat_iframe' name='tuotteen_toimittajat_iframe' src='yllapito.php?toim=tuotteen_toimittajat&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' scrolling='no' border='0' frameborder='0'></iFrame>";
																						
				$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='tuotteen_avainsanat' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
				$res = mysql_query($queryoik) or pupe_error($queryoik);
				
				if(mysql_num_rows($res) > 0) echo "<iframe id='tuotteen_avainsanat_iframe' name='tuotteen_avainsanat_iframe' src='yllapito.php?toim=tuotteen_avainsanat&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' scrolling='no' border='0' frameborder='0'></iFrame>";
			}

			if ($toim == "maksuehto" and $laji != "V") {
								
				$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='avainsana' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
				$res = mysql_query($queryoik) or pupe_error($queryoik);
				
				if (mysql_num_rows($res) > 0) {
					
					//	Maksuehdon teksti
					$lukitse_laji = "MEHTOTXT";
					$lukitse_avaimeen = $teksti;
					
					echo "<iframe id='avainsanat_mehtotxt_iframe' name='avainsanat_mehtotxt_iframe' src='yllapito.php?toim=avainsana&from=yllapito&ohje=off&haku[2]=$lukitse_laji&haku[3]=$lukitse_avaimeen&lukitse_laji=$lukitse_laji&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' scrolling='no' border='0' frameborder='0'></iFrame>";
					
					if ($kateksti != "") {
						//	Maksuehdon kassateksti
						$lukitse_laji = "MEHTOKATXT";
						$lukitse_avaimeen = $kateksti;

						echo "<iframe id='avainsanat_mehtokatxt_iframe' name='avainsanat_mehtokatxt_iframe' src='yllapito.php?toim=avainsana&from=yllapito&ohje=off&haku[2]=$lukitse_laji&haku[3]=$lukitse_avaimeen&lukitse_laji=$lukitse_laji&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' scrolling='no' border='0' frameborder='0'></iFrame>";
						
					}
				}
			}
		}
		
		echo "</td></tr>";
		echo "</table>";
		
		
		// Tuotteen saa poistaa mik‰li sill‰ ei ole yht‰‰n tapahtumaa
		$sdtchk = "";
		
		if ($toim == "tuote") {			 			
			$query = "SELECT yhtio FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$sdtres = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($sdtres) == 0) {
				$query = "SELECT yhtio FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tyyppi!='D'";
				$sdtres = mysql_query($query) or pupe_error($query);
								
				if (mysql_num_rows($sdtres) == 0) {
					$sdtchk = "OK";
				}
			}
		}
		
		
		// M‰‰ritell‰‰n mit‰ tietueita saa poistaa
		if ($toim == "avainsana" or
			$toim == "tili" or
			$toim == "taso" or
			$toim == "asiakasalennus" or
			$toim == "asiakashinta" or
			$toim == "perusalennus" or
			$toim == "yhteensopivuus_tuote" or
			$toim == "toimitustapa" or
			$toim == "kirjoittimet" or
			$toim == "hinnasto" or
			$toim == "rahtimaksut" or
			$toim == "rahtisopimukset" or
			$toim == "etaisyydet" or
			$toim == "tuotteen_avainsanat" or
			$toim == "varaston_tulostimet" or
			$toim == "pakkaamo" or
			$toim == "autodata_tuote" or
			$toim == "extranet_kayttajan_lisatiedot" or
			($toim == "tuote" and $sdtchk == "OK") or
			($toim == "toimi" and $kukarow["taso"] == "3")) {

			// Tehd‰‰n "poista tietue"-nappi
			if ($uusi != 1 and $toim != "yhtio" and $toim != "yhtion_parametrit") {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
							function verify(){
									msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
									return confirm(msg);
							}
					</SCRIPT>";

				if ($rajattu_nakyma == '') {
					echo "<br><br>
						<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post' onSubmit = 'return verify()' enctype='multipart/form-data'>
						<input type = 'hidden' name = 'toim' value = '$aputoim'>
						<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
						<input type = 'hidden' name = 'limit' value = '$limit'>
						<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
						<input type = 'hidden' name = 'laji' value = '$laji'>
						<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
						<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
						<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>
						<input type = 'hidden' name = 'del' value ='1'>
						<input type='hidden' name='seuraavatunnus' value = '$seuraavatunnus'>
						<input type = 'submit' value = '".t("Poista $otsikko_nappi")."'></form>";
				}
			}
		}
	}
	elseif ($toim != "yhtio" and $toim != "yhtion_parametrit") {
		echo "<br>
				<form action = 'yllapito.php?ojarj=$ojarj$ulisa";
				if ($liitostunnus) echo "&liitostunnus=$liitostunnus";
				echo "' method = 'post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'ajax_menu_yp' value = '$ajax_menu_yp'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
				<input type = 'hidden' name = 'laji' value = '$laji'>
				<input type = 'hidden' name = 'uusi' value = '1'>
				<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
		
		
	}
	
	
	if ($from == "yllapito" and $toim == "yhteyshenkilo") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('yhteyshenkilo_iframe');</script>";
	}
	
	if($from == "yllapito" and $toim == "tuotteen_avainsanat") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('tuotteen_avainsanat_iframe');</script>";
	}
	
	if($from == "yllapito" and $toim == "tuotteen_toimittajat") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('tuotteen_toimittajat_iframe');</script>";
	}
	
	if($from == "yllapito" and $toim == "avainsana") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('avainsanat_mehtotxt_iframe');</script>";
		echo "<script LANGUAGE='JavaScript'>resizeIframe('avainsanat_mehtokatxt_iframe');</script>";		
	}
	elseif($from != "yllapito") {
		require ("inc/footer.inc");
	}
	
?>
