<?php

	if ($_POST["toim"] == "yhtion_parametrit") {
		$apucss = $_POST["css"];
		$apucsspieni = $_POST["css_pieni"];
		$apucssextranet = $_POST["css_extranet"];
		$apucssverkkokauppa = $_POST["css_verkkokauppa"];
		$apuwebseuranta = $_POST["web_seuranta"];
	}
	else {
		unset($apucss);
		unset($apucsspieni);
		unset($apucssextranet);
		unset($apucssverkkokauppa);
		unset($apuwebseuranta);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "yllapito.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}

	//Jotta m‰‰ritelty rajattu n‰kym‰ olisi myˆs k‰yttˆoikeudellisesti tiukka
	$aputoim = $toim;
	$toimi_array = explode('!!!', $toim);

	$toim = $toimi_array[0];
	if (isset($toimi_array[1])) $alias_set = $toimi_array[1];
	if (isset($toimi_array[2])) $rajattu_nakyma = $toimi_array[2];

	// Setataan muuttujat
	if (!isset($rajauslisa)) 		$rajauslisa = "";
	if (!isset($del)) 				$del = "";
	if (!isset($errori)) 			$errori = "";
	if (!isset($from)) 				$from = "";
	if (!isset($haku)) 				$haku = "";
	if (!isset($js_open_yp)) 		$js_open_yp = "";
	if (!isset($laji)) 				$laji = "";
	if (!isset($limit)) 			$limit = "";
	if (!isset($lisa)) 				$lisa = "";
	if (!isset($lopetus)) 			$lopetus = "";
	if (!isset($nayta_eraantyneet)) $nayta_eraantyneet = "";
	if (!isset($nayta_poistetut)) 	$nayta_poistetut = "";
	if (!isset($ojarj)) 			$ojarj = "";
	if (!isset($oletus)) 			$oletus = "";
	if (!isset($osuu)) 				$osuu = "";
	if (!isset($otsikko_lisatiedot))$otsikko_lisatiedot = "";
	if (!isset($otsikko_nappi)) 	$otsikko_nappi = "";
	if (!isset($prospektlisa)) 		$prospektlisa = "";
	if (!isset($ryhma)) 			$ryhma = "";
	if (!isset($tunnus)) 			$tunnus = "";
	if (!isset($ulisa)) 			$ulisa = "";
	if (!isset($upd)) 				$upd = "";
	if (!isset($uusi)) 				$uusi = "";
	if (!isset($uusilukko)) 		$uusilukko = "";
	if (!isset($alias_set)) 		$alias_set = "";
	if (!isset($rajattu_nakyma)) 	$rajattu_nakyma = "";
	if (!isset($lukossa)) 			$lukossa = "";

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
	if (isset($_POST["toim"]) and $_POST["toim"] == "yhtion_parametrit") {
		if (isset($apucss)) {
			$t[$cssi] = mysql_real_escape_string($apucss);
		}
		if (isset($apucsspieni)) {
			$t[$csspienii] = mysql_real_escape_string($apucsspieni);
		}
		if (isset($apucssextranet)) {
			$t[$cssextraneti] = mysql_real_escape_string($apucssextranet);
		}
		if (isset($apucssverkkokauppa)) {
			$t[$cssverkkokauppa] = mysql_real_escape_string($apucssverkkokauppa);
		}
		if (isset($apuwebseuranta)) {
			$t[$webseuranta] = mysql_real_escape_string($apuwebseuranta);
		}
	}

	require ("inc/$toim.inc");

	if ($otsikko == "") {
		$otsikko = $toim;
	}
	if ($otsikko_nappi == "") {
		$otsikko_nappi = $toim;
	}

	echo "<font class='head'>".t("$otsikko")."</font><hr>";

	if ($otsikko_lisatiedot != "") {
		echo $otsikko_lisatiedot;
	}

	// Kun tehd‰‰n p‰ivityksi‰ omasta ikkunasta
	js_open_yllapito();

	if ($from == "yllapito") {
		echo "
		<script LANGUAGE='JavaScript'>
			function resizeIframe(frameid, maxheight){

				try {
					currentfr=window.parent.document.getElementById(frameid);
				}
				catch (err) {
					currentfr=document.getElementById(frameid);
				}

				currentfr.height = 100;
				currentfr.style.height = 100;

				setTimeout(\"currentfr.style.display='block';\", 1);

				if (currentfr && !window.opera){

					var height = 100;

					try {
						height = currentfr.contentDocument.body.offsetHeight;
					}
					catch (err) {
						height = currentfr.Document.body.scrollHeight;
					}

					if (height > maxheight) {
						height = maxheight;
					}

					currentfr.height = height+20;
					currentfr.style.height = height+20;

					setTimeout(\"currentfr.style.display='block';\", 1);
				}
			}

		</script>";
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

		// Jos poistamme ifamesta tietoja niin p‰ivitet‰‰n varsinaisen tietueen muutospvm, jotta verkkokauppasiirto huomaa, ett‰ tietoja on muutettu
		if ($lukitse_avaimeen != "") {
			if ($toim == "tuotteen_avainsanat" or $toim == "tuotteen_toimittajat") {
				$query = "	UPDATE tuote
							SET muuttaja = '$kukarow[kuka]', muutospvm=now()
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$lukitse_avaimeen'";
				$result = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "liitetiedostot" and $lukitse_laji == "tuote") {
				$query = "	UPDATE tuote
							SET muuttaja = '$kukarow[kuka]', muutospvm=now()
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$lukitse_avaimeen'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

		//	Jos poistetaan perheen osa palataan perheelle
		if ($seuraavatunnus > 0) $tunnus = $seuraavatunnus;
		else $tunnus = 0;

		$seuraavatunnus = 0;
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
			if ($t["{$i}_uusi"] != "") {
				$t[$i] = $t["{$i}_uusi"];
			}
			$t[mysql_field_name($result, $i)] = &$t[$i];
		}

		// Tarkistetaan
		$errori = '';
		$virhe  = array();

		for ($i=1; $i < mysql_num_fields($result); $i++) {

			//P‰iv‰m‰‰r‰ spesiaali
			if (isset($tpp[$i])) {
				if ($tvv[$i] < 1000 and $tvv[$i] > 0) $tvv[$i] += 2000;

				$t[$i] = sprintf('%04d', $tvv[$i])."-".sprintf('%02d', $tkk[$i])."-".sprintf('%02d', $tpp[$i]);

				if (!@checkdate($tkk[$i],$tpp[$i],$tvv[$i]) and ($tkk[$i]!= 0 or $tpp[$i] != 0)) {
					$virhe[$i] = t("Virheellinen p‰iv‰m‰‰r‰");
					$errori = 1;
				}
			}

			// Tarkistetaan saako k‰ytt‰j‰ p‰ivitt‰‰ t‰t‰ kentt‰‰
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'MYSQLALIAS'
						and selite = '$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($al_res) == 0 and $rajattu_nakyma != '' and isset($t[$i])) {
				$virhe[$i] = t("Sinulla ei ole oikeutta p‰ivitt‰‰ t‰t‰ kentt‰‰");
				$errori = 1;
			}

			$tiedostopaate = "";

			$funktio = $toim."tarkista";

			if (!function_exists($funktio)) {
				require("inc/$funktio.inc");
			}

			if (function_exists($funktio)) {
				@$funktio($t, $i, $result, $tunnus, &$virhe, $trow);
			}

			if ($virhe[$i] != "") {
				$errori = 1;
			}

			//	Tarkastammeko liitetiedoston?
			if (is_array($tiedostopaate) and is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
				$viesti = tarkasta_liite("liite_$i", $tiedostopaate);

				if ($viesti !== true) {
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

						if (is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
							$id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result,$i), $t[$i]);

							if ($id !== false) {
								$t[$i] = $id;
							}
						}

						if (mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);

						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";
					}
				}
			}
			// P‰ivitet‰‰n
			else {

				//	Jos poistettiin jokin liite, poistetaan se nyt
				if (is_array($poista_liite)) {
					foreach($poista_liite as $key => $val) {
						if ($val > 0) {
							$delquery = " DELETE FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and liitos = 'Yllapito' and tunnus = '$val'";
							$delres = mysql_query($delquery);
							if (mysql_affected_rows() == 1) {
								$t[$key] = "";
							}
						}
					}
				}

				// Taulun ensimm‰inen kentt‰ on aina yhtiˆ
				$query = "UPDATE $toim SET muuttaja='$kukarow[kuka]', muutospvm=now() ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i]) or is_array($_FILES["liite_$i"])) {

						if (is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
							$id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result,$i), $t[$i]);
							if ($id !== false) {
								$t[$i] = $id;
							}
						}

						if (mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);
						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";

					}
				}

				$query .= " where yhtio='$kukarow[yhtio]' and tunnus = $tunnus";
			}

			$result = mysql_query($query) or pupe_error($query);

			if ($tunnus == '') {
				$tunnus = mysql_insert_id();
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
										(tila IN ('A','0'))
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

			if ($tunnus > 0 and isset($paivita_myos_avoimet_tilaukset) and $toim == "yhtio") {

				$query = "	SELECT *
							FROM yhtio
							WHERE tunnus = '$tunnus'
							and yhtio 	 = '$kukarow[yhtio]'";
				$otsikres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($otsikres) == 1) {
					$otsikrow = mysql_fetch_array($otsikres);

					$query = "	SELECT *
								FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
								WHERE yhtio = '$kukarow[yhtio]'
								and (
										(tila IN ('L','N','R','V','E') AND alatila != 'X')
										OR
										(tila = 'T' AND alatila in ('','A'))
										OR
										(tila IN ('A','0'))
									)
								and tapvm = '0000-00-00'";
					$laskuores = mysql_query($query) or pupe_error($query);

					while ($laskuorow = mysql_fetch_array($laskuores)) {

						$upda_yhtionimi 		= $otsikrow["nimi"];
						$upda_yhtioosoite 		= $otsikrow["osoite"];
						$upda_yhtiopostino		= $otsikrow["postino"];
						$upda_yhtiopostitp		= $otsikrow["postitp"];
						$upda_yhtiomaa 			= $otsikrow["maa"];
						$upda_yhtioovttunnus 	= $otsikrow["ovttunnus"];
						$upda_yhtiokotipaikka	= $otsikrow["kotipaikka"];
						$upda_yhtioalv_tilino	= $otsikrow["alv"];

						if ($laskuorow["maa"] != "" and $laskuorow["maa"] != $otsikrow["maa"]) {
							// tutkitaan ollaanko siell‰ alv-rekisterˆity
							$alhqur = "SELECT vat_numero from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskuorow[maa]' and vat_numero != ''";
							$alhire = mysql_query($alhqur) or pupe_error($alhqur);

							// ollaan alv-rekisterˆity, aina kotimaa myynti ja alvillista
							if (mysql_num_rows($alhire) == 1) {
								$alhiro = mysql_fetch_assoc($alhire);

								// haetaan maan oletusalvi
								$query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark='o' and selitetark_2='$laskuorow[maa]'";
								$alhire = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($alhire) == 1) {

									// haetaan sen yhteystiedot
									$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero = '$alhiro[vat_numero]'";
									$alhire = mysql_query($alhqur) or pupe_error($alhqur);

									if (mysql_num_rows($alhire) == 1) {
										$apualvrow  = mysql_fetch_assoc($alhire);

										$upda_yhtionimi 		= $apualvrow["nimi"];
										$upda_yhtioosoite 	    = $apualvrow["osoite"];
										$upda_yhtiopostino	    = $apualvrow["postino"];
										$upda_yhtiopostitp	    = $apualvrow["postitp"];
										$upda_yhtiomaa 		    = $apualvrow["maa"];
										$upda_yhtioovttunnus  	= $apualvrow["vat_numero"];
										$upda_yhtiokotipaikka 	= $apualvrow["kotipaikka"];
										$upda_yhtioalv_tilino	= $apualvrow["toim_alv"];
									}
								}
							}
						}

						$query = "	UPDATE lasku
									SET	yhtio_nimi		= '$upda_yhtionimi',
									yhtio_osoite		= '$upda_yhtioosoite',
									yhtio_postino		= '$upda_yhtiopostino',
									yhtio_postitp		= '$upda_yhtiopostitp',
									yhtio_maa			= '$upda_yhtiomaa',
									yhtio_ovttunnus		= '$upda_yhtioovttunnus',
									yhtio_kotipaikka	= '$upda_yhtiokotipaikka',
									alv_tili			= '$upda_yhtioalv_tilino'
									WHERE yhtio 		= '$kukarow[yhtio]'
									and tunnus			= '$laskuorow[tunnus]'";
						$updaresult = mysql_query($query) or pupe_error($query);
					}
				}
			}

			// Jos p‰ivit‰mme ifamesta tietoja niin p‰ivitet‰‰n varsinaisen tietueen muutospvm, jotta verkkokauppasiirto huomaa, ett‰ tietoja on muutettu
			if ($lukitse_avaimeen != "") {
				if ($toim == "tuotteen_avainsanat" or $toim == "tuotteen_toimittajat") {
					$query = "	UPDATE tuote
								SET muuttaja = '$kukarow[kuka]', muutospvm=now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$lukitse_avaimeen'";
					$result = mysql_query($query) or pupe_error($query);
				}
				elseif ($toim == "liitetiedostot" and $lukitse_laji == "tuote") {
					$query = "	UPDATE tuote
								SET muuttaja = '$kukarow[kuka]', muutospvm=now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$lukitse_avaimeen'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			//	T‰m‰ funktio tekee myˆs oikeustarkistukset!
			synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

			if (in_array(strtoupper($toim), array("ASIAKAS", "ASIAKKAAN_KOHDE")) and $yhtiorow["dokumentaatiohallinta"] != "") {
				svnSyncMaintenanceFolders(strtoupper($toim), $tunnus, $trow);
			}

			if ($lopetus != '' and (isset($yllapitonappi) or isset($paivita_myos_avoimet_tilaukset))) {
				$lopetus .= "//yllapidossa=$toim//yllapidontunnus=$tunnus";

				lopetus($lopetus, "META");
			}

			$uusi = 0;

			if (isset($yllapitonappi) and $lukossa != "ON" or isset($paluunappi)) {
				$tmp_tuote_tunnus  = $tunnus;
				$tunnus  = 0;
				$kikkeli = 0;
			}
			else {
				$kikkeli = 1;
			}
		}
	}

	if ($errori != '' and $_POST["toim"] == "yhtion_parametrit") {
		// jos tuli virhe, niin laitetaan takaisin css:t ilman mysql_real_escape_stringi‰
		if (isset($apucss)) {
			$t[$cssi] = $apucss;
		}
		if (isset($apucsspieni)) {
			$t[$csspienii] = $apucsspieni;
		}
		if (isset($apucssextranet)) {
			$t[$cssextraneti] = $apucssextranet;
		}
		if (isset($apucssverkkokauppa)) {
			$t[$cssverkkokauppa] = $apucssverkkokauppa;
		}
		if (isset($apuwebseuranta)) {
			$t[$webseuranta] = $apuwebseuranta;
		}
	}

	if ($errori == "" and ($del == 1 or $del == 2 or $upd == 1) and isset($js_open_yp) and $js_open_yp != "") {

		if ($toim == "perusalennus") {
			$query = "	SELECT ryhma value, concat_ws(' - ', ryhma, selite) text
						FROM perusalennus
						WHERE tunnus = '$tmp_tuote_tunnus'
						and yhtio 	 = '$kukarow[yhtio]'";
			$otsikres = mysql_query($query) or pupe_error($query);
			$otsikrow = mysql_fetch_assoc($otsikres);
		}
		elseif ($toim == "avainsana") {
			$query = "	SELECT selite value, concat_ws(' ', selite, selitetark) text
						FROM avainsana
						WHERE tunnus = '$tmp_tuote_tunnus'
						and yhtio 	 = '$kukarow[yhtio]'";
			$otsikres = mysql_query($query) or pupe_error($query);
			$otsikrow = mysql_fetch_assoc($otsikres);
		}
		elseif ($toim == "yhteyshenkilo") {
			$query = "	SELECT nimi value, nimi text
						FROM yhteyshenkilo
						WHERE tunnus = '$tmp_tuote_tunnus'
						and yhtio 	 = '$kukarow[yhtio]'";
			$otsikres = mysql_query($query) or pupe_error($query);
			$otsikrow = mysql_fetch_assoc($otsikres);
		}
		else {
			$otsikrow = array("value" => "", "text" => "");
		}

		echo "	<script LANGUAGE='JavaScript'>

				//	Paivitetaan ja valitaan select option
				var elementti = \"$js_open_yp\";
				var elementit = new Array();
				var ele;
				var newOpt;

				// Yhetyshekiloilla spessujuttu
				if (elementti.substring(0,14) == \"yhteyshenkilo_\") {
					elementit[0] = \"yhteyshenkilo_tekninen\";
					elementit[1] = \"yhteyshenkilo_kaupallinen\";
					elementit[2] = \"yhteyshenkilo_tilaus\";
				}
				else {
					elementit[0] = elementti;
				}

				for (ele in elementit) {
					newOpt = window.opener.document.createElement('option');
					newOpt.text = \"".$otsikrow["text"]."\";
					newOpt.value = \"".$otsikrow["value"]."\";

					sel = window.opener.document.getElementById(elementit[ele]);

					try {
						sel.add(newOpt, sel.options[1]);
					}
					catch(ex) {
						sel.add(newOpt, 1);
					}

					if (elementit[ele] == elementti) {
						//	Valitaan uusi arvo
						sel.selectedIndex = 1;
					}
				}

				window.close();

				</script>";
	}

	// Rakennetaan hakumuuttujat kuntoon
	if (isset($hakukentat)) {
		$array = explode(",", str_replace(" ", "", $hakukentat));
	}
	else {
		$array = explode(",", str_replace(" ", "", $kentat));
	}

	$count = count($array);

	for ($i=0; $i<=$count; $i++) {
		if (isset($haku[$i]) and strlen($haku[$i]) > 0) {

			if ($from == "" and ((($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'asiakashinta') and trim($array[$i]) == 'asiakas') or ($toim == 'yhteyshenkilo' and trim($array[$i]) == 'liitostunnus'))) {

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
			elseif ($from == "yllapito" and ($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'asiakashinta') and trim($array[$i]) == 'asiakas') {
				list($a, $b) = explode("/", $haku[$i]);

				if ((int) $a > 0) $a_lisa .= " asiakas = '$a' ";
				else $a_lisa = "";

				if ((is_numeric($b) and $b > 0) or (!is_numeric($b) and $b != "")) $b_lisa .= " ytunnus = '$b' ";
				else $b_lisa = "";

				if ($a_lisa != "" and $b_lisa != "") {
					$lisa .= " and ($a_lisa or $b_lisa) ";
				}
				elseif ($a_lisa != "") {
					$lisa .= " and $a_lisa ";
				}
				elseif ($b_lisa != "") {
					$lisa .= " and $b_lisa ";
				}
			}
			elseif ($from == "" and $toim == 'tuotteen_toimittajat' and trim($array[$i]) == 'nimi') {
				if (!is_numeric($haku[$i])) {
					// haetaan laskutus-asiakas
					$ashak = "SELECT group_concat(concat(\"'\",ytunnus,\"'\")) tunnukset FROM toimi WHERE yhtio='$kukarow[yhtio]' and nimi like '%" . $haku[$i] . "%'";
					$ashakres = mysql_query($ashak) or pupe_error($ashak);
					$ashakrow = mysql_fetch_array($ashakres);

					if ($ashakrow["tunnukset"] != "") {
						$lisa .= " and toimittaja in (" . $ashakrow["tunnukset"] . ")";
					}
					else {
						$lisa .= " and toimittaja = NULL ";
					}
				}
				else {
					$lisa .= " and toimittaja = '" . $haku[$i] . "'";
				}
			}
			elseif ($from == "" and ($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'asiakashinta') and trim($array[$i]) == 'ytunnus') {

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
				$lisa .= " and (";
				foreach (explode("/", $array[$i]) as $spl) $lisa .= "$spl like '%".$haku[$i]."%' or ";
				$lisa = substr($lisa, 0, -3).")";
			}
			else {
				$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			}

			$ulisa .= "&haku[$i]=".urlencode($haku[$i]);
    	}
    }

	//	S‰ilytet‰‰n ohjeen tila
	if ($from != "") {
		$ulisa .= "&ohje=off&from=$from&lukitse_avaimeen=".urlencode($lukitse_avaimeen)."&lukitse_laji=$lukitse_laji";
	}

	//	Pidet‰‰n oletukset tallessa!
	if (is_array($oletus)){
		foreach($oletus as $o => $a) {
			$ulisa.="&oletus[$o]=$a";
		}
	}

	// Nyt selataan
	if ($tunnus == 0 and $uusi == 0 and $errori == '') {

		if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") and $oikeurow['paivitys'] == 1) {
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

		if (strlen($ojarj) > 0) {

			list($ojar, $osuu) = explode("_", $ojarj);

	    	$jarjestys = "$ojar $osuu ";
	    }


		if ($osuu == '') {
			$osuu	= 'asc';
			$edosuu	= 'asc';
		}
		elseif ($osuu == 'desc') {
			$edosuu = 'asc';
		}
		else {
			$osuu 	= 'asc';
			$edosuu = 'desc';
		}

		$query = "SELECT " . $kentat . " FROM $toim WHERE yhtio = '$kukarow[yhtio]' $lisa $rajauslisa $prospektlisa";
        $query .= "$ryhma ORDER BY $jarjestys $limiitti";
		$result = mysql_query($query) or pupe_error($query);

		if ($toim != "yhtio" and $toim != "yhtion_parametrit" and $uusilukko == "") {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa";
			if ($liitostunnus) echo "&liitostunnus=$liitostunnus";
			echo "' method = 'post'>
					<input type = 'hidden' name = 'uusi' value = '1'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
					<input type = 'hidden' name = 'limit' value = '$limit'>
					<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
					<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
		}

		if (mysql_num_rows($result) >= 350) {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
					<input type = 'hidden' name = 'limit' value = 'NO'>
					<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
					<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("N‰yt‰ kaikki")."'></form>";
		}

		if ($toim == "asiakas" or $toim == "maksuehto" or $toim == "toimi" or $toim == "tuote" or $toim == "yriti" or $toim == "kustannuspaikka") {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
					<input type = 'hidden' name = 'limit' value = '$limit'>
					<input type = 'hidden' name = 'nayta_poistetut' value = 'YES'>
					<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("N‰yt‰ poistetut")."'></form>";
		}

		if ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
					<input type = 'hidden' name = 'limit' value = 'NO'>
					<input type = 'hidden' name = 'nayta_eraantyneet' value = 'YES'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("N‰yt‰ er‰‰ntyneet")."'></form>";
		}

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
			echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
			echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
			echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
			echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
			echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type = 'hidden' name = 'tunnus' value = '$noperow[tunnus]'>";
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
			echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
			echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
			echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
			echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
			echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type = 'hidden' name = 'tunnus' value = '$yesrow[tunnus]'>";
			echo " <input type='submit' value='".t("Seuraava tuote")."'>";
			echo "</form>";

		}

		echo "	<br><br><table><tr class='aktiivi'>
				<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
				<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
				<input type = 'hidden' name = 'laji' value = '$laji'>";

		if ($from != "" and mysql_num_rows($result) > 0) {
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {
					echo "<th valign='top'>".t(mysql_field_name($result,$i))."</th>";
				}
			}
		}
		elseif ($from == "") {
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {

					echo "<th valign='top'><a href='yllapito.php?toim=$aputoim&lopetus=$lopetus&ojarj=".($i+1)."_".$edosuu."$ulisa&limit=$limit&nayta_poistetut=$nayta_poistetut&nayta_eraantyneet=$nayta_eraantyneet&laji=$laji'>" . t(mysql_field_name($result,$i)) . "</a>";

					if 	(mysql_field_len($result,$i)>10) $size='15';
					elseif	(mysql_field_len($result,$i)<5)  $size='5';
					else	$size='10';

					// jos meid‰n kentt‰ ei ole subselect niin tehd‰‰n hakukentt‰
					if (strpos(strtoupper($array[$i]), "SELECT") === FALSE) {
						if (!isset($haku[$i])) $haku[$i] = "";

						echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
					}
					echo "</th>";
				}
			}

			if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") and $oikeurow['paivitys'] == 1) {
				echo "<th valign='top'>".t("Poista")."</th>";
			}

			echo "<td class='back' valign='bottom'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></form>";
			echo "</tr>";

		}

		if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") and $oikeurow['paivitys'] == 1) {
			echo "<tr><form action='yllapito.php?ojarj=$ojarj$ulisa' name='ruksaus' method='post' onSubmit = 'return verifyMulti()'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
					<input type = 'hidden' name = 'limit' value = '$limit'>
					<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
					<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'hidden' name = 'del' value = '2'></tr>";
		}

		while ($trow = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>";

			if (($toim == "asiakas" and $trow["HIDDEN_laji"] == "P") or
				($toim == "toimi" and $trow["HIDDEN_tyyppi"] == "P") or
				(($toim == "yriti" or $toim == 'maksuehto') and $trow["HIDDEN_kaytossa"] == "E") or
				($toim == "tuote" and $trow["HIDDEN_status"] == "P") or
				($toim == "kustannuspaikka" and $trow["HIDDEN_kaytossa"] == "E")) {

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
						if (trim($trow[1]) == '' or (is_numeric($trow[1]) and $trow[1] == 0)) $trow[1] = t("*tyhj‰*");

						echo "<td valign='top'><a name='$trow[0]' href='yllapito.php?ojarj=$ojarj$ulisa&toim=$aputoim&tunnus=$trow[0]&limit=$limit&nayta_poistetut=$nayta_poistetut&nayta_eraantyneet=$nayta_eraantyneet&laji=$laji";

						if ($lopetus == "") {
							echo "&lopetus=".$palvelin2."yllapito.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."//toim=$aputoim//limit=$limit//nayta_poistetut=$nayta_poistetut//nayta_eraantyneet=$nayta_eraantyneet//laji=$laji///$trow[0]";
						}
						else {
							echo "&lopetus=$lopetus";
						}

						echo "'>";

						if (mysql_field_name($result,$i) == 'liitedata') {

							if ($lukitse_laji == "tuote" and $lukitse_avaimeen > 0 and in_array($trow[1], array("image/jpeg","image/jpg","image/gif","image/png","image/bmp"))) {
								echo "<img src='".$palvelin2."view.php?id=$trow[0]' height='80px'><br>".t("Muokkaa liitett‰")."";
							}
							else {
								list($liitedata1, $liitedata2) = explode("/", $trow[1]);

								$path_parts = pathinfo($trow[4]);
								$ext = $path_parts['extension'];

								if (file_exists("pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico")) {
									echo "<img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico' height='80px'><br>".t("Muokkaa liitett‰");
								}
								elseif (file_exists("pics/tiedostotyyppiikonit/".strtoupper($ext).".ico")) {
									echo "<img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($ext).".ico' height='80px'><br>".t("Muokkaa liitett‰");
								}
								else {
									echo $trow[1]."<br>".t("Muokkaa liitett‰");
								}
							}
						}
						else {
							echo $trow[1];
						}

						echo "</a></td>";
					}
					else {
						if (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int') {
							echo "<td valign='top' style='text-align:right'>$fontlisa1 $trow[$i] $fontlisa2</td>";
						}
						elseif (mysql_field_name($result,$i) == 'koko') {
							echo "<td valign='top'>$fontlisa1 ".size_readable($trow[$i])." $fontlisa2</td>";
						}
						else {

							if (!function_exists("ps_callback")) {
								function ps_callback($matches) {
									return tv1dateconv($matches[0]);
								}
							}

							$trow[$i] = preg_replace_callback("/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/", "ps_callback", $trow[$i]);

							echo "<td valign='top'>$fontlisa1 $trow[$i] $fontlisa2</td>";
						}
					}
				}
			}

			if ($from == "" and ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") and $oikeurow['paivitys'] == 1) {
				echo "<td><input type = 'checkbox' name = 'poista_check[]' value = '$trow[0]'></td>";
			}

			echo "</tr>";
		}

		if ($from == "" and ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") and $oikeurow['paivitys'] == 1) {
			$span = mysql_num_fields($result)-2;
			echo "<tr>";
			echo "<td class='back'><input type = 'submit' value = '".t("Poista ruksatut tietueet")."'></td>";
			echo "<td class='back' colspan='$span' align='right'>".t("Ruksaa kaikki")."</td>";
			echo "<td class='back'><input type = 'checkbox' name = 'poi' onclick='toggleAll(this)'></td>";
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

		if ($from == "") {
			$ankkuri = "#$tunnus";
		}
		else {
			$ankkuri = "";
		}

		if ($toim == "lasku" or $toim == "laskun_lisatiedot") {
			echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
								msg = '".t("Oletko varma, ett‰ haluat muuttaa kirjanpitoaineiston tietoja j‰lkik‰teen")."?';
								return confirm(msg);
						}
				</SCRIPT>";

			$javalisasubmit = "onSubmit = 'return verify()'";
		}
		else {
			$javalisasubmit = "";
		}

		echo "<form action = 'yllapito.php?ojarj=$ojarj$ulisa$ankkuri' name='mainform' id='mainform' method = 'post' autocomplete='off' $javalisasubmit enctype='multipart/form-data'>";
		echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
		echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
		echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
		echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
		echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
		echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
		echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
		echo "<input type = 'hidden' name = 'upd' value = '1'>";

		// Kokeillaan geneerist‰
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		echo "<table><tr><td class='back' valign='top' style='padding: 0px;'>";

		echo "<table>";

		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

			$nimi = "t[$i]";

			if (isset($t[$i])) {
				$trow[$i] = $t[$i];
			}
			// Haetaan passatut oletukset arvoiksi!
			elseif ($uusi == 1) {
				if (isset($oletus[mysql_field_name($result, $i)])) {
					$trow[$i] = $oletus[mysql_field_name($result, $i)];
				}
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

			if (mysql_num_rows($al_res) > 0) {
				$al_row = mysql_fetch_array($al_res);

				if ($al_row["selitetark"] != '') {
					$otsikko = str_ireplace("(BR)", "<br>", t($al_row["selitetark"]));
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
			// $tyyppi --> 2 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, eik‰ sen arvoa p‰vitet‰ (rivi‰ ei n‰ytet‰ kun tehd‰‰n uusi)
			// $tyyppi --> 3 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, mutta sen arvo p‰ivitet‰‰n (rivi‰ ei n‰ytet‰ kun tehd‰‰n uusi)
			// $tyyppi --> 4 rivi‰ ei n‰ytet‰ ollenkaan, mutta sen arvo p‰ivitet‰‰n
			// $tyyppi --> 5 liitetiedosto

			if ($tyyppi == 1 or
				$tyyppi == 1.5 or
				($tyyppi == 2 and $tunnus!="") or
				($tyyppi == 3 and $tunnus!="")  or
				$tyyppi == 5) {
				echo "<tr>";
				echo "<th align='left'>$otsikko</th>";
			}

			if ($jatko == 0) {
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
			elseif ($tyyppi == 2 and $tunnus != "") {
				echo "<td>$trow[$i]</td>";
			}
			elseif ($tyyppi == 3 and $tunnus != "") {
				echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
			}
			elseif ($tyyppi == 4) {
				echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
			}
			elseif ($tyyppi == 5) {
				echo "<td>";

				if ($trow[$i] > 0) {
					echo "<a href='view.php?id=".$trow[$i]."' target='Attachment'>".t("N‰yt‰ liitetiedosto")."</a><input type = 'hidden' name = '$nimi' value = '$trow[$i]'> ".("Poista").": <input type = 'checkbox' name = 'poista_liite[$i]' value = '$trow[$i]'>";
				}
				else {
					echo "<input type = 'text' name = '$nimi' value = '$trow[$i]'>";
				}

				echo "<input type = 'file' name = 'liite_$i'></td>";
			}

			if (isset($virhe[$i])) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
			}

			if ($tyyppi == 1 or
				$tyyppi == 1.5 or
				($tyyppi == 2 and $tunnus!="") or
				($tyyppi == 3 and $tunnus!="")  or
				$tyyppi == 5) {
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

		echo "<br><input type = 'submit' name='yllapitonappi' value = '$nimi'>";

		if (($toim == "asiakas" or $toim == "yhtio") and $uusi != 1) {
			echo "<br><input type = 'submit' name='paivita_myos_avoimet_tilaukset' value = '$nimi ".t("ja p‰ivit‰ tiedot myˆs avoimille tilauksille")."'>";
		}

		if ($lukossa == "ON") {
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
		if ($errori == '' and $toim == "avainsana" and $from != "yllapito" and $from != "positioselain") {
			require ("inc/avainsanaperhe.inc");
		}

		if ($trow["tunnus"] > 0 and $errori == '' and $toim == "yhtio") {
			require ("inc/yhtion_toimipaikat.inc");
		}

		if ($trow["tunnus"] > 0 and $errori == '' and $toim == "lasku") {

			echo "<iframe id='laskun_lisatiedot_iframe' name='laskun_lisatiedot_iframe' src='yllapito.php?toim=laskun_lisatiedot&from=yllapito&ohje=off&haku[1]=$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
		}

		if ($trow["tunnus"] > 0 and $errori == '' and $toim == "asiakas") {

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'asiakasalennus' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='asiakasalennus_iframe' name='asiakasalennus_iframe' src='yllapito.php?toim=asiakasalennus&from=yllapito&ohje=off&haku[1]=$trow[tunnus]/$trow[ytunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'asiakashinta' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='asiakashinta_iframe' name='asiakashinta_iframe' src='yllapito.php?toim=asiakashinta&from=yllapito&ohje=off&haku[1]=$trow[tunnus]/$trow[ytunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'asiakaskommentti' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='asiakaskommentti_iframe' name='asiakaskommentti_iframe' src='yllapito.php?toim=asiakaskommentti&from=yllapito&ohje=off&haku[1]=$trow[ytunnus]&lukitse_avaimeen=$trow[ytunnus]' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'asiakkaan_avainsanat' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='asiakkaan_avainsanat_iframe' name='asiakkaan_avainsanat_iframe' src='yllapito.php?toim=asiakkaan_avainsanat&from=yllapito&ohje=off&haku[4]=$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
		}

		if ($trow["tunnus"] > 0 and $errori == '' and ($toim == "toimi" or $toim == "asiakas")) {

			if ($toim == "asiakas") {
				$laji = "A";
			}
			elseif ($toim == "toimi") {
				$laji = "T";
			}

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'yhteyshenkilo' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='yhteyshenkilo_iframe' name='yhteyshenkilo_iframe' src='yllapito.php?toim=yhteyshenkilo&from=yllapito&laji=$laji&ohje=off&haku[6]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
		}

		if ($trow["tunnus"] > 0 and $errori == '' and ($toim == "toimitustapa" or $toim == "maksuehto")) {

			if ($toim == "toimitustapa") {
				$laji = "TOIMTAPAKV";
			}
			elseif ($toim == "maksuehto") {
				$laji = "MAKSUEHTOKV";
			}

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi = 'avainsana' and kuka = '$kukarow[kuka]' and yhtio = '$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='avainsana_iframe' name='avainsana_iframe' src='yllapito.php?toim=avainsana&from=yllapito&lukitse_laji=$laji&ohje=off&haku[2]=@$laji&haku[3]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
		}

		if ($trow["tunnus"] > 0 and $errori == '' and ($toim == 'lasku' or $toim == 'asiakas' or $toim == "sarjanumeron_lisatiedot" or ($toim == "tuote" and $laji != "V") or (($toim == "avainsana") and (strtolower($laji) == "osasto" or strtolower($laji) == "try" or strtolower($laji) == "tuotemerkki")))) {
			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='liitetiedostot' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='liitetiedostot_iframe' name='liitetiedostot_iframe' src='yllapito.php?toim=liitetiedostot&from=yllapito&ohje=off&haku[7]=@$toim&haku[8]=@$tunnus&lukitse_avaimeen=$tunnus&lukitse_laji=$toim' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
		}

		if ($trow["tunnus"] > 0 and $errori == "" and $from != "yllapito" and $toim == "tuote" and $laji != "V") {

			$lukitse_avaimeen = urlencode($tuoteno);

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='tuotteen_toimittajat' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='tuotteen_toimittajat_iframe' name='tuotteen_toimittajat_iframe' src='yllapito.php?toim=tuotteen_toimittajat&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";

			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='tuotteen_avainsanat' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='tuotteen_avainsanat_iframe' name='tuotteen_avainsanat_iframe' src='yllapito.php?toim=tuotteen_avainsanat&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";

			// Jamppa s‰‰t‰‰
			$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='tuotteen_alkio' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) echo "<iframe id='tuotteen_alkio_iframe' name='tuotteen_alkio_iframe' src='yllapito.php?toim=tuotteen_alkio&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen&toim2=tuote' style='width: 600px; height: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
			// JAMPAN s‰‰tˆ loppuu
		}

		echo "</td></tr>";
		echo "</table>";

		// M‰‰ritell‰‰n mit‰ tietueita saa poistaa
		if ($toim == "tuotteen_alkio" or
			$toim == "avainsana" or
			$toim == "tili" or
			$toim == "taso" or
			$toim == "asiakasalennus" or
			$toim == "asiakashinta" or
			$toim == "perusalennus" or
			$toim == "yhteensopivuus_tuote" or
		   ($toim == "toimitustapa" and $poistolukko == "") or
			$toim == "kirjoittimet" or
			$toim == "hinnasto" or
			$toim == "rahtimaksut" or
			$toim == "rahtisopimukset" or
			$toim == "etaisyydet" or
			$toim == "tuotteen_avainsanat" or
			$toim == "varaston_tulostimet" or
			$toim == "pakkaamo" or
			$toim == "asiakaskommentti" or
			$toim == "yhteyshenkilo" or
			$toim == "autodata_tuote" or
			$toim == "tuotteen_toimittajat" or
			$toim == "extranet_kayttajan_lisatiedot" or
			$toim == "asiakkaan_avainsanat" or
			$toim == "tilikaudet" or
			($toim == "liitetiedostot" and $poistolukko == "") or
			($toim == "tuote" and $poistolukko == "") or
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
						<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
						<input type = 'hidden' name = 'limit' value = '$limit'>
						<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
						<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
						<input type = 'hidden' name = 'laji' value = '$laji'>
						<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
						<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
						<input type = 'hidden' name = 'del' value ='1'>
						<input type='hidden' name='seuraavatunnus' value = '$seuraavatunnus'>
						<input type = 'submit' value = '".t("Poista $otsikko_nappi")."'>
						</form>";
				}
			}
		}
	}
	elseif ($toim != "yhtio" and $toim != "yhtion_parametrit"  and $uusilukko == "" and $from == "") {
		echo "<br>
				<form action = 'yllapito.php?ojarj=$ojarj$ulisa";
				if ($liitostunnus) echo "&liitostunnus=$liitostunnus";
				echo "' method = 'post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
				<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
				<input type = 'hidden' name = 'laji' value = '$laji'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type = 'hidden' name = 'uusi' value = '1'>
				<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
	}


	if ((int) $tunnus == 0 and (int) $uusi == 0 and $errori == '') {
		$jcsmaxheigth = ", 300";
	}
	else {
		$jcsmaxheigth = "";
	}

	if ($from == "yllapito" and $toim == "laskun_lisatiedot") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('laskun_lisatiedot_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "yhteyshenkilo") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('yhteyshenkilo_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "tuotteen_avainsanat") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('tuotteen_avainsanat_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "tuotteen_toimittajat") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('tuotteen_toimittajat_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "liitetiedostot") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('liitetiedostot_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "asiakashinta") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('asiakashinta_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "asiakasalennus") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('asiakasalennus_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "perusalennus") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('perusalennus_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "asiakaskommentti") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('asiakaskommentti_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "asiakkaan_avainsanat") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('asiakkaan_avainsanat_iframe' $jcsmaxheigth);</script>";
	}

	if ($from == "yllapito" and $toim == "avainsana") {
		echo "<script LANGUAGE='JavaScript'>resizeIframe('avainsana_iframe' $jcsmaxheigth);</script>";
	}
	elseif ($from != "yllapito") {
		require ("inc/footer.inc");
	}

?>