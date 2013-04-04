<?php

	$DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

	if (php_sapi_name() != 'cli' and (!isset($ei_kayttoliittymaa) or $ei_kayttoliittymaa != "kylla")) {
		// ToimehtoTarkenne javascript funktio
		js_toimehtoTarkenne();

		// Voidaan esim. luoda lennossa uusia yhteyshenkilöitä
		js_open_yllapito();

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function merahti_oletus(kentta) {
					// Toimitustapa alasvetovalikon id
					var ttdd_id = document.getElementById(kentta);

					// Valitun toimitustavan tunnus
					var val = ttdd_id.options[ttdd_id.selectedIndex].id;

					// Haetaan valitun toimitustavan oletus merahti
					var merahti = document.getElementById('merahti_'+val).innerHTML;

					//Selectöidään oikea rahtisopparivalinta toisesta alasvetovalikosta
					var mrdd_id = document.getElementById('merahti');
					var mrdd_sel = mrdd_id.selectedIndex;

					// Katotaan onko rahtivapaa
					var rvcb_ch = document.getElementById('rahtivapaa').checked;

					if (merahti == 'K' && mrdd_sel != 1) {
						mrdd_id.options[1].selected = '1';
						alert('".t("HUOM: Käytettävä rahtisopimus vaihdettiin")."!');
					}
					else if (merahti != 'K' && mrdd_sel != 2 && rvcb_ch != true) {
						mrdd_id.options[2].selected = '1';
						alert('".t("HUOM: Käytettävä rahtisopimus vaihdettiin")."!');
					}
				}

				//-->
				</script>";
	}

	//tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
	if ($tee == '' or $kopioitava_otsikko != 0) {
		//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
		if ($tila == '' and !isset($jatka)) {
			$query = "	UPDATE kuka
						SET kesken = 0
						WHERE session = '$session'";
			$result = pupe_query($query);

			$kukarow['kesken'] 	= 0;
			$tilausnumero 		= 0;
		}
	}

	//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}

	// Halutaan vaihtaa asiakas tilauksella
	if ($tila == "vaihdaasiakas") {
		$query = "	UPDATE lasku
					SET liitostunnus 	= 0,
					ytunnus				= '',
					ovttunnus			= '',
					nimi				= '',
					nimitark			= '',
					osoite 				= '',
					postino 			= '',
					postitp				= '',
					maa   				= '',
					toim_ovttunnus 		= '',
					toim_nimi      		= '',
					toim_nimitark  		= '',
					toim_osoite    		= '',
					toim_postino  		= '',
					toim_postitp 		= '',
					toim_maa    		= '',
					laskutusvkopv		= '',
					chn					= '',
					verkkotunnus		= '',
					toimitustapa		= '',
					toimitustavan_lahto = ''
					WHERE yhtio 		= '$kukarow[yhtio]'
					and tunnus	 		= '$kukarow[kesken]'";
		$result = pupe_query($query);

		$query = "	UPDATE laskun_lisatiedot
					SET kolm_ovttunnus	= '',
					kolm_nimi   		= '',
					kolm_nimitark		= '',
					kolm_osoite  		= '',
					kolm_postino  		= '',
					kolm_postitp 		= '',
					kolm_maa    		= '',
					laskutus_nimi		= '',
					laskutus_nimitark	= '',
					laskutus_osoite		= '',
					laskutus_postino	= '',
					laskutus_postitp	= '',
					laskutus_maa		= ''
					WHERE yhtio 		= '$kukarow[yhtio]'
					and otunnus 		= '$kukarow[kesken]'";
		$result = pupe_query($query);

		$tila 		= '';
		$nimi 		= '';
		$asiakasid	= '';
		$from 		= 'vaihdaasiakas';
	}

	// Etsitään asiakasta
	if (($nimi != '' and !isset($jatka) and $asiakasid == 0 and !isset($vaihdatoim_asiakas)) or ($tnimi != '' and !isset($jatka) and isset($vaihdatoim_asiakas))) {

		//tehdään asiakashaku yhteensopivuus
		$ytunnus 		= $nimi;
		$muutparametrit = 0;
		$kutsuja 		= "otsik.inc";

		if (isset($vaihdatoim_asiakas) and $tnimi != "") {
			$ytunnus 		= $tnimi;
			$osoite 		= $tosoite;
			$postitp 		= $tpostitp;
			$postino		= $tpostino;
			$tila 			= "vaihdatoim_asiakas";
			$muutparametrit = $asiakasid;
			$kutsuja 		= "";
		}

		$asiakasid	= "";
		$ahlopetus 	= "tilauskasittely/tilaus_myynti.php////toim=$toim//tee=$tee//tilausnumero=$tilausnumero//mista=$mista//tila=$tila//from=ASIAKASYLLAPITO";
		$lause 		= "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

		require ("inc/asiakashaku.inc");

		if ($asiakasid == '' and $monta > 1) {
			//Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
			$tila = 'EI_NAYTETA';
		}
	}

	if ($tnimi == "" and !isset($jatka) and isset($vaihdatoim_asiakas)) {
		echo "<font class='message'>".t("Syötä hakuehto toimitusosoitteen nimi-kenttään.")."</font><br>";
	}

	if ($tila == "vaihdatoim_asiakas" and $asiakasid != 0) {

		$toim_asiakasid = $asiakasid;
		$asiakasid = $muutparametrit;
		$tila = "";

		if ($tilausnumero != 0) {
			$tiedot_laskulta = "YES";
		}
		else {
			$tiedot_laskulta = "";
		}

	}
	elseif ($tila == "vaihdatoim_asiakas" and $asiakasid == 0 and $monta == 0) {
		$toim_asiakasid = $asiakasid;
		$asiakasid = $muutparametrit;
		$tila = "";
	}

	if (isset($jatka) and $asiakasid != "") {
		if ($maksuehto == "") {
			echo "<font class='error'>".t("VIRHE: Maksuehtoa ei löydy kyseiselle maalle").".<br>".t("Tarkista laskutusosoitteen maa")."!</font><br>";
			$tee = "OTSIK";
			$tila = "";
			unset($jatka);
		}
		elseif ($maksuehto != "") {
			// haetaan maksuehdon tiedot tarkastuksia varten
			$tarkistus_apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
			$tarkistus_meapu = pupe_query($tarkistus_apuqu);

			if (mysql_num_rows($tarkistus_meapu) == 0) {
				echo "<font class='error'>".t("VIRHE: Maksuehtoa ei löydy kyseiselle maalle").".<br>".t("Tarkista laskutusosoitteen maa")."!</font><br>";
				$tee = "OTSIK";
				$tila = "";
				unset($jatka);
			}
		}

		list($toimitustapa,$eitarvitatassa) = explode("#",$toimitustapa);

		if ($toimitustapa == "") {
			echo "<font class='error'>".t("VIRHE: Toimitustapaa ei löydy kyseiselle maalle").".<br>".t("Tarkista toimitusosoitteen maa")."!</font><br>";
			$tee = "OTSIK";
			$tila = "";
			unset($jatka);
		}
		elseif ($toimitustapa != "") {
			$query = "	SELECT tunnus
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and selite  = '$toimitustapa'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("VIRHE: Toimitustapaa ei löydy kyseiselle maalle").".<br>".t("Tarkista toimitusosoitteen maa")."!</font><br>";
				$tee = "OTSIK";
				$tila = "";
				unset($jatka);
			}
		}

		if ($yhtiorow["tilauksen_yhteyshenkilot"] == 'P' and ($tilausyhteyshenkilo == '' and $yhteyshenkilo_tilaus == '') and in_array($toim, array("PROJEKTI", "TARJOUS", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "RIVISYOTTO", "REKLAMAATIO"))) {
			echo "<font class='error'>".t("VIRHE: Anna tilaukselle yhteyshenkilö")."!</font><br>";
			$tee = "OTSIK";
			$tila = "";
			unset($jatka);
		}
	}

	if (isset($jatka) and $maksaja == "" and $rahtivapaa != "") {
		echo "<font class='error'>".t("HUOM: Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella")."!</font><br>";
	}

	if (isset($jatka) and $asiakasid == "" and $override_ytunnus_check != 'YES') {
		echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu Y-tunnus")."!</font><br>";

		$tee	= "OTSIK";
		$tila 	= "";

		unset($jatka);
	}

	//	Tarkastetaan toimitustapa saadaanko laittaa jälkivaatimuksia
	if (isset($jatka)) {
		$query = "	SELECT tunnus
					FROM maksuehto
					WHERE yhtio ='$kukarow[yhtio]'
					AND tunnus = '$maksuehto'
					AND jv != ''";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			//	Denial by toimitustapa
			$query = "	SELECT tunnus
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and selite = '$toimitustapa'
						and jvkielto != ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				echo "<font class='error'>".t("VIRHE: Toimitustavalle on jälkivaatimuskielto")."</font><br>";
				$tee	= "OTSIK";
				$tila 	= "";

				unset($jatka);
			}

			//	Denial by maa
			$query = "SELECT maa FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
			$result = pupe_query($query);
			$vara = mysql_fetch_assoc($result);

			if ($vara["maa"] != "" and $vara["maa"] != $yhtiorow["maa"]) {
				echo "<font class='error'>".t("VIRHE: Jälkitoimitus ei sallittu myytäessä ulkomaille")."</font><br>";
				$tee	= "OTSIK";
				$tila 	= "";

				unset($jatka);
			}
		}
	}

	//	Tarkastetaan käsinsyötetty viite
	if (isset($jatka) and $kasinsyotetty_viite != "" and $yhtiorow["viitteen_kasinsyotto"] != "") {
		if (substr($kasinsyotetty_viite, 0, 2) != "RF" and tarkista_viite($kasinsyotetty_viite) === FALSE) {
			echo "<font class='error'>".t("VIRHE: Syötetty viite '%s' on väärin", $kieli, $kasinsyotetty_viite)."!</font><br>";
			$tee	= "OTSIK";
			unset($jatka);
		}
		elseif (substr($kasinsyotetty_viite, 0, 2) == "RF" and tarkista_rfviite($kasinsyotetty_viite) === FALSE) {
			echo "<font class='error'>".t("VIRHE: Syötetty RF-viite '%s' on väärin", $kieli, $kasinsyotetty_viite)."!</font><br>";
			$tee	= "OTSIK";
			unset($jatka);
		}
		else{
			$ketjutus = "o";
		}
	}
	else {
		$kasinsyotetty_viite = "";
	}

	$erpcm_virhe = ""; // apumuuttuja
	$erpcm_kasin = ""; // apumuuttuja
	$meapurow    = array();

	if ($maksuehto != "") {
		// haetaan maksuehdon tiedot tarkastuksia varten
		$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
		$meapu = pupe_query($apuqu);
		$meapurow = mysql_fetch_assoc($meapu);
	}
	else {
		$meapurow = array("erapvmkasin" => "");
	}

	if ($erpcmvv != "" or $erpcmkk != "" or $erpcmpp != "" or $meapurow["erapvmkasin"] != '') {

		$erpquery = "SELECT * from maksuehto where yhtio = '$kukarow[yhtio]' and erapvmkasin != '' and kaytossa = '' order by tunnus limit 1";
		$erpres = pupe_query($erpquery);

		$erpcmvv = (int) $erpcmvv;
		$erpcmkk = (int) $erpcmkk;
		$erpcmpp = (int) $erpcmpp;


		if ($erpcmvv != "" or $erpcmkk != "" or $erpcmpp != "") {
			if ($kukarow['extranet'] == '') {
				if ($erpcmvv < date("Y")) {
					echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
					$tee	= "OTSIK";
					unset($jatka);
					$erpcm_virhe = "X"; // apumuuttuja
					if ($kukarow['kesken'] != 0) {
						$tiedot_laskulta = "ok";
					}
				}
				elseif ($erpcmvv == date("Y") and ($erpcmkk < date("n") or $erpcmkk < date("m"))) {
					echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
					$tee	= "OTSIK";
					unset($jatka);
					$erpcm_virhe = "X"; // apumuuttuja
					if ($kukarow['kesken'] != 0) {
						$tiedot_laskulta = "ok";
					}
				}
				elseif ($erpcmvv == date("Y") and ($erpcmkk == date("n") or $erpcmkk == date("m")) and ($erpcmpp < date("j") or $erpcmpp < date("d"))) {
					echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
					$tee	= "OTSIK";
					unset($jatka);
					$erpcm_virhe = "X"; // apumuuttuja
					if ($kukarow['kesken'] != 0) {
						$tiedot_laskulta = "ok";
					}
				}
			}
		}

		if ($erpcm_virhe == '' and (!checkdate($erpcmkk, $erpcmpp, $erpcmvv) or mysql_num_rows($erpres) == 0)) {
			if (mysql_num_rows($erpres) == 0) {
				if ($kukarow['extranet'] != '') {
					echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
					exit;
				}
				else {
					echo "<font class='error'>".t("VIRHE: Yritykseltä ei löydy sopivaa maksuehtoa eräpäivän käsinsyöttöön")."!</font><br>";
				}

			}
			else {
				if ($kukarow['extranet'] != '') {
					echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
					exit;
				}
				else {
					echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
				}
			}
			$tee	= "OTSIK";
			unset($jatka);
			$erpcm_virhe = "X"; // apumuuttuja
			if ($kukarow['kesken'] != 0) {
				$tiedot_laskulta = "ok";
			}
		}
		else {
			$erpcm_kasin = "KYLLA";
		}
	}

	if (isset($jatka) and ($erikoisale > 100 or $erikoisale < 0)) {
		echo "<font class='error'>".t("Erikoisale pitää olla alle 100% yli 0%")."</font><br/><br/>";
		unset($jatka);
	}

	// tehdään oikeellisuus
	if ($toim == "YLLAPITO") {

		if ((count($yllapito_kk) == 0 or count($yllapito_pp) == 0) and isset($jatka)) {
			echo "<font class='error'>".t("VIRHE: Laskutusaikataulu on syötettävä")."!</font><br>";
			$tee = "OTSIK";
			unset($jatka);
			$erpcm_virhe = "X";
		}

		if ($yllapito_alkupp != "" or $yllapito_alkukk != "" or $yllapito_alkuvv != "") {

			$yllapito_alkupp = (int) $yllapito_alkupp;
			$yllapito_alkukk = (int) $yllapito_alkukk;
			$yllapito_alkuvv = (int) $yllapito_alkuvv;

			if (!checkdate($yllapito_alkukk, $yllapito_alkupp, $yllapito_alkuvv)) {
				echo "<font class='error'>".t("VIRHE: Syötetty alkupvm on virheellinen")."!</font><br>";
				$tee = "OTSIK";
				unset($jatka);
				$erpcm_virhe = "X";
			}
		}
		elseif (isset($yllapito_alkupp) and isset($yllapito_alkukk) and isset($yllapito_alkuvv) and $yllapito_alkupp == "" and $yllapito_alkukk == "" and $yllapito_alkuvv == "") {
			echo "<font class='error'>".t("VIRHE: Alkupvm on pakollinen tieto")."!</font><br>";
			$tee = "OTSIK";
			unset($jatka);
			$erpcm_virhe = "X";
		}

		if ($yllapito_loppupp != "" or $yllapito_loppukk != "" or $yllapito_loppuvv != "") {

			$yllapito_loppupp = (int) $yllapito_loppupp;
			$yllapito_loppukk = (int) $yllapito_loppukk;
			$yllapito_loppuvv = (int) $yllapito_loppuvv;

			// vertaillaan päiviä
			$pvmsyote = (int) date('Ymd',mktime(0,0,0,$yllapito_loppukk,$yllapito_loppupp,$yllapito_loppuvv));
			$pvmtoday = (int) date('Ymd'); // now

			if (!checkdate($yllapito_loppukk, $yllapito_loppupp, $yllapito_loppuvv) ) { //or $pvmsyote < $pvmtoday) {
				echo "<font class='error'>".t("VIRHE: Syötetty loppupvm on virheellinen")."!</font><br>";
				$tee = "OTSIK";
				unset($jatka);
				$erpcm_virhe = "X";
			}
		}

	}

	// Lukitaan rahtikirjaan vaikuttavat tiedot jos/kun rahtikirja on tulostettu
	if ($kukarow["kesken"] != 0) {
		$query = "	SELECT *
					FROM rahtikirjat
					WHERE yhtio 	= '$kukarow[yhtio]'
					AND otsikkonro 	= '$kukarow[kesken]'
					AND tulostettu != '0000-00-00 00:00:00'
					LIMIT 1";
		$rakre_chkres = pupe_query($query);
	}

	if ($tila == "" and !isset($jatka)) {
		if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
			echo "<font class='head'>".t("Työmääräyksen otsikko")."</font><hr>";
		}
		elseif ($toim == "REKLAMAATIO") {
			echo "<font class='head'>".t("Reklamaatio otsikko")."</font><hr>";
		}
		elseif ($toim == "EXTRANET_REKLAMAATIO") {
			echo "<font class='head'>".t("Extranet-Reklamaatio otsikko")."</font><hr>";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			echo "<font class='head'>".t("Valmistuksen otsikko")."</font><hr>";
		}
		elseif ($toim == "SIIRTOLISTA") {
			echo "<font class='head'>".t("Siirtolistan otsikko")."</font><hr>";
		}
		elseif ($toim == "MYYNTITILI") {
			echo "<font class='head'>".t("Myyntitilin otsikko")."</font><hr>";
			// Myyntitili oikeellisuustarkistus
			$query = "	SELECT tunnus
						FROM varastopaikat
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND alkuhyllyalue = '!!M'
						AND loppuhyllyalue = '!!M'
						AND tyyppi = 'E'";
			$myyntitili_result = pupe_query($query);

			if (mysql_num_rows($myyntitili_result) != 1) {
				echo t("VIRHE: Myyntitili-varastoa ei ole perustettu")."!";
				require("inc/footer.inc");
				exit;
			}
		}
		elseif ($toim == "TARJOUS") {
			echo "<font class='head'>".t("Tarjouksen otsikko")."</font><hr>";
		}
		elseif ($toim == "PROJEKTI") {
			echo "<font class='head'>".t("Projektin otsikko")."</font><hr>";
		}
		elseif ($toim == "YLLAPITO") {
			echo "<font class='head'>".t("Ylläpitosopimuksen otsikko")."</font><hr>";
		}
		elseif ($toim == "ENNAKKO" or $srow["tilaustyyppi"] == 'E' or $laskurow["tilaustyyppi"] == 'E') {
			echo "<font class='head'>".t("Ennakkotilauksen otsikko")."</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Myyntitilauksen otsikko")."</font><hr>";
		}

		echo "<form method='post' autocomplete='off' name='paaformi' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='tee' value='OTSIK'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='projektilla' value='$projektilla'>";
		echo "<input type='hidden' name='from' value='$from'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='mista' value='$mista'>";

		if ($asiakasid != "" or $kukarow["kesken"] != 0 or $kopioitava_otsikko != 0) {

			//Asiakas vaihtuu
			if ($from == "vaihdaasiakas" and $asiakasid != "") {
				// asiakashaussa pitää olla vakio columni kohdistettu, jonka arvo on O että saadaan OLETUS toimitustapa haettua asiakashaunkin yhteydessä
				$squery = "	SELECT *, 'O' kohdistettu
							FROM asiakas
							WHERE tunnus = '$asiakasid'";
				$assresult = pupe_query($squery);
				$assrow = mysql_fetch_assoc($assresult);

				if (!checkdate($toimkk,$toimpp,$toimvv)) {
					$toimpp = date("j");
					$toimkk = date("n");
					$toimvv = date("Y");
					$toimpvm = $toimvv."-".$toimkk."-".$toimpp;
				}
				else {
					$toimpvm = $toimvv."-".$toimkk."-".$toimpp;
				}

				if (!checkdate($kerkk,$kerpp,$kervv)) {
					$kerpp = date("j");
					$kerkk = date("n");
					$kervv = date("Y");
					$kerpvm = $kervv."-".$kerkk."-".$kerpp;
				}
				else {
				 	$kerpvm = $kervv."-".$kerkk."-".$kerpp;
				}

				if (trim($assrow["toim_nimi"]) == "") {
					$assrow["toim_nimi"]		= $assrow["nimi"];
					$assrow["toim_nimitark"]	= $assrow["nimitark"];
					$assrow["toim_osoite"]		= $assrow["osoite"];
					$assrow["toim_postino"]		= $assrow["postino"];
					$assrow["toim_postitp"]		= $assrow["postitp"];
					$assrow["toim_maa"]			= $assrow["maa"];
				}

				if (trim($assrow["laskutus_nimi"]) == "") {
					$assrow["laskutus_nimi"]	   = $assrow["nimi"];
				 	$assrow["laskutus_nimitark"]   = $assrow["nimitark"];
				 	$assrow["laskutus_osoite"]	   = $assrow["osoite"];
				 	$assrow["laskutus_postino"]	   = $assrow["postino"];
				 	$assrow["laskutus_postitp"]	   = $assrow["postitp"];
				 	$assrow["laskutus_maa"]		   = $assrow["maa"];
				}

				if($naytetaanko_tarjouksella != 'on' and $toim == 'TARJOUS') {
					$toimpvm = '0000-00-00';
				}

				$query = "	UPDATE lasku
							SET liitostunnus 	= '$assrow[tunnus]',
							ytunnus				= '$assrow[ytunnus]',
							ovttunnus			= '$assrow[ovttunnus]',
							nimi				= '$assrow[nimi]',
							nimitark			= '$assrow[nimitark]',
							osoite 				= '$assrow[osoite]',
							postino 			= '$assrow[postino]',
							postitp				= '$assrow[postitp]',
							maa   				= '$assrow[maa]',
							toim_ovttunnus 		= '$assrow[toim_ovttunnus]',
							toim_nimi      		= '$assrow[toim_nimi]',
							toim_nimitark  		= '$assrow[toim_nimitark]',
							toim_osoite    		= '$assrow[toim_osoite]',
							toim_postino  		= '$assrow[toim_postino]',
							toim_postitp 		= '$assrow[toim_postitp]',
							toim_maa    		= '$assrow[toim_maa]',
							laskutusvkopv		= '$assrow[laskutusvkopv]',
							chn					= '$assrow[chn]',
							verkkotunnus		= '$assrow[verkkotunnus]',
							toimitustapa		= '$assrow[toimitustapa]',
							maksuehto			= '$assrow[maksuehto]',
							tilausvahvistus		= '$assrow[tilausvahvistus]',
							piiri				= '$assrow[piiri]',
							rahtivapaa			= '$assrow[rahtivapaa]',
							toimaika 			= '$toimpvm',
							kerayspvm			= '$kerpvm',
							toimitusehto		= '$assrow[toimitusehto]'
							WHERE yhtio 		= '$kukarow[yhtio]'
							and tunnus	 		= '$kukarow[kesken]'
							and liitostunnus	= 0";
				$result = pupe_query($query);

				if (mysql_affected_rows() > 0) {
					echo "<font class='message'>".t("HUOM: Uudet otsikkotiedot haettu asiakasrekisteristä.")."</font><br>";
				}

				$query = "	UPDATE laskun_lisatiedot
							SET kolm_ovttunnus	= '$assrow[kolm_ovttunnus]',
							kolm_nimi   		= '$assrow[kolm_nimi]',
							kolm_nimitark		= '$assrow[kolm_nimitark]',
							kolm_osoite  		= '$assrow[kolm_osoite]',
							kolm_postino  		= '$assrow[kolm_postino]',
							kolm_postitp 		= '$assrow[kolm_postitp]',
							kolm_maa    		= '$assrow[kolm_maa]',
							laskutus_nimi		= '$assrow[laskutus_nimi]',
							laskutus_nimitark	= '$assrow[laskutus_nimitark]',
							laskutus_osoite		= '$assrow[laskutus_osoite]',
							laskutus_postino	= '$assrow[laskutus_postino]',
							laskutus_postitp	= '$assrow[laskutus_postitp]',
							laskutus_maa		= '$assrow[laskutus_maa]'
							WHERE yhtio 		= '$kukarow[yhtio]'
							and otunnus 		= '$kukarow[kesken]'";
				$result = pupe_query($query);

				$tiedot_laskulta = "YES";
			}

			if (substr($asiakasid,0,1) == "£") {

				$asiakasid = substr($asiakasid,1);

				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*,  lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$asiakasid'";

				$srow_from = "LASKU";
			}
			elseif ($asiakasid != "" and $tiedot_laskulta == "") {
				// asiakashaussa pitää olla vakio columni kohdistettu, jonka arvo on O että saadaan OLETUS toimitustapa haettua asiakashaunkin yhteydessä
				if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
					$puhlisa = " if (asiakas.puhelin!='', asiakas.puhelin, asiakas.gsm) kotipuh, asiakas.tyopuhelin tyopuh,";
				}
				else {
					$puhlisa = "";
				}

				$squery = "	SELECT asiakas.*, $puhlisa asiakas.tunnus liitostunnus, 'O' kohdistettu, '' kohde, '' kauppatapahtuman_luonne
							FROM asiakas
							WHERE tunnus = '$asiakasid'";

				$srow_from = "ASIAKAS";
			}
			elseif ($kopioitava_otsikko > 0) {
				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$kopioitava_otsikko'";

				$srow_from = "LASKU";
			}
			else {
				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$kukarow[kesken]'";

				$srow_from = "LASKU";
			}
			$sresult = pupe_query($squery);
			$srow = mysql_fetch_assoc($sresult);

			//	Ylikirjoitetaan oletusarvot otsikon tiedoilla!
			if ($otsikolta == "YES" and $from != "vaihdaasiakas") {
				foreach($_POST as $key => $val) {

					if ($key == "toimpp") 			$srow["toimaika"] = $_POST["toimvv"]."-".$_POST["toimkk"]."-".$_POST["toimpp"];
					elseif ($key == "kerpp") 		$srow["kerayspvm"] = $_POST["kervv"]."-".$_POST["kerkk"]."-".$_POST["kerpp"];
					elseif ($key == "tnimi") 		$srow["toim_nimi"] = $val;
					elseif ($key == "tnimitark") 	$srow["toim_nimitark"] = $val;
					elseif ($key == "tosoite") 		$srow["toim_osoite"] = $val;
					elseif ($key == "tpostino") 	$srow["toim_postino"] = $val;
					elseif ($key == "tpostitp") 	$srow["toim_postitp"] = $val;
					elseif ($key == "eilahe") 		$srow["eilahetetta"] = $val;
					elseif ($key == "maksaja") 		$srow["kohdistettu"] = $val;
					elseif ($key == "valkoodi") 	$srow["valkoodi"] = substr($val,0,strpos($val, "##"));
					elseif (!in_array($key, array("tila", "alatila"))) {
						$srow[$key] = $val;
					}
				}
			}

			if (isset($toim_asiakasid) and $toim_asiakasid != 0) {

				$toim_asiakasid = mysql_real_escape_string($toim_asiakasid);
				$toimasiakas_query = "SELECT * from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$toim_asiakasid'";
				$toimasiakas_result = pupe_query($toimasiakas_query);
				$toimasiakas_row = mysql_fetch_assoc($toimasiakas_result);

				if (trim($toimasiakas_row["toim_nimi"]) != "") {
					$srow["toim_nimi"]		= $toimasiakas_row["toim_nimi"];
					$srow["toim_nimitark"]	= $toimasiakas_row["toim_nimitark"];
					$srow["toim_osoite"]	= $toimasiakas_row["toim_osoite"];
					$srow["toim_postino"]	= $toimasiakas_row["toim_postino"];
					$srow["toim_postitp"]	= $toimasiakas_row["toim_postitp"];
					$srow["toim_maa"]		= $toimasiakas_row["toim_maa"];
				}
				else {
					$srow["toim_nimi"]		= $toimasiakas_row["nimi"];
					$srow["toim_nimitark"]	= $toimasiakas_row["nimitark"];
					$srow["toim_osoite"]	= $toimasiakas_row["osoite"];
					$srow["toim_postino"]	= $toimasiakas_row["postino"];
					$srow["toim_postitp"]	= $toimasiakas_row["postitp"];
					$srow["toim_maa"]		= $toimasiakas_row["maa"];
				}

				if ($tilausnumero == 0) {
					echo "<font class='message'>".t("HUOM: Tilauksen oletustiedot haettu laskutusasiakkaan asiakasrekisteristä.")."</font><br>";
				}
			}

		}

		//Tutkitaan onko tilauksella rivejä
		if ($kukarow["kesken"] != 0) {
			$query = "SELECT otunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
			$tempr = pupe_query($query);
			$templ = mysql_num_rows($tempr);
		}
		else {
			$templ = 0;
		}

		// jos meillä on jo alatila ja tila ei muokkailla niitä!
		if ($alatila == '' and isset($srow['alatila']) and $srow['alatila'] != '' and $srow_from == "LASKU") {
			$alatila = $srow['alatila'];
		}

		if ($ylatila == '' and isset($srow['tila']) and $srow['tila'] != '' and $srow_from == "LASKU") {
			$ylatila = $srow['tila'];
		}

		// katsotaan miten vienti ja ALV käsitellään
		$alv_velvollisuus = "";

		// jos meillä on lasku menossa ulkomaille
		if (isset($srow["maa"]) and $srow["maa"] != "" and $srow["maa"] != $yhtiorow["maa"]) {
			// tutkitaan ollaanko siellä alv-rekisteröity
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$srow[maa]' and vat_numero != ''";
			$alhire = pupe_query($alhqur);

			// ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
			if (mysql_num_rows($alhire) == 1) {

				$alhiro  = mysql_fetch_assoc($alhire);

				// haetaan maan oletusalvi
				$query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark='o' and selitetark_2='$srow[maa]'";
				$alhire = pupe_query($query);

				// jos ei löydy niin mennään erroriin
				if (mysql_num_rows($alhire) == 0) {
					echo "<font class='error'>".t("VIRHE: Oletus ALV-kantaa ei löydy asiakkaan maahan")." $srow[maa]! ".t("Ei voida jatkaa")."!</font><br>";
					exit;
				}
				else {
					$apuro  = mysql_fetch_assoc($alhire);
					// nämä tässä keisissä aina näin
					$alv    = $apuro["selite"];
					$vienti = "";
					$alv_velvollisuus = $alhiro["vat_numero"];
					echo "<input type='hidden' name='alv_velvollisuus' value='$alhiro[vat_numero]'>";
				}
			}
		}

		//jos käyttäjällä on oletusaiskas
		if ($kukarow["oletus_asiakas"] != 0 and $srow["nimi"] == '') {
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$extra_asiakas = mysql_fetch_assoc($result);
				$srow["nimi"]  = $extra_asiakas["ytunnus"];
			}
		}

		if (!isset($srow)) {
			$srow = array(
				"nimi" => "",
				"nimitark" => "",
				"osoite" => "",
				"postino" => "",
				"postitp" => "",
				"liitostunnus" => 0,
			);
		}

		echo "<table>";
		echo "<tr><td style='padding:0px;'>";

		echo "<table>";
		echo "<tr>
				<th colspan='2' align='left' valign='top'>".t("Ostaja").":</td></tr>";
		echo "<tr>
				<td valign='top'>".t("Nimi").": </td>
				<td><input type='text' name='nimi' size='35' value='$srow[nimi]'></td></tr>";
		echo "<tr>
				<td></td>
				<td><input type='text' name='nimitark' size='35' value='$srow[nimitark]'></td></tr>";
		echo "<tr>
				<td valign='top'>".t("Osoite").": </td>
				<td><input type='text' name='osoite' size='35' value='$srow[osoite]'></td></tr>";

		echo "<tr>
				<td valign='top'>".t("Postitp").": </td>
				<td><input type='text' name='postino' size='10' value='$srow[postino]'> <input type='text' name='postitp' size='21' value='$srow[postitp]'></td></tr>";

		if ($srow["liitostunnus"] > 0) {
			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = pupe_query($query);

			echo "<tr><td valign='top'> ".t("Maa").": </td>
					<td><select name='maa' onChange='submit();' ".js_alasvetoMaxWidth("maa", 200).">";

			while ($vrow = mysql_fetch_assoc($vresult)) {
				$sel="";
				if (strtoupper($srow["maa"]) == strtoupper($vrow["koodi"])) {
					$sel = "selected";
				}
				elseif ($srow["maa"] == "" and strtoupper($vrow["koodi"]) == strtoupper($yhtiorow["maa"])) {
					$sel = "selected";
				}
				echo "<option value = '".strtoupper($vrow["koodi"])."' $sel>".t($vrow["nimi"])."</option>";
			}

			echo "</select></td></tr>";

			if(isset($srow['ryhma'])) {
				echo "<tr>";
				echo "<td valign='top'>".t("Asiakasryhmä")."</td>";
				echo "<td>{$srow['ryhma']}</td>";
				echo "</tr>";
			}

			$query  = "	SELECT laji
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$srow[liitostunnus]'";
			$result = pupe_query($query);
			$eakas = mysql_fetch_assoc($result);

			if ($eakas["laji"] == 'H') {
				$ealisa = "asiakas!!!VAHITTAISMYYNTI!!!true";
			}
			else {
				$ealisa = "asiakas";
			}

			if (tarkista_oikeus("yllapito.php", $ealisa, "X")) {
				echo "<tr><td></td><td><a href='".$palvelin2."yllapito.php?toim=$ealisa&tunnus=$srow[liitostunnus]&lopetus=tilauskasittely/tilaus_myynti.php////toim=$toim//tee=$tee//tilausnumero=$tilausnumero//mista=$mista//tila=$tila//from=ASIAKASYLLAPITO//asiakasid=$asiakasid'>".t("Muuta asiakkaan tietoja")."</a></td></tr>";
			}
		}

		if ($templ > 0 and $asiakasid == '') {
			echo "<tr><td colspan='2'><br><font class='message'>".t("HUOM: Jos vaihdat asiakasta, tarkista hinnat ja alennukset")."!</font></td></tr>";
		}

		echo "</table>";
		echo "</td>";

		if (!isset($srow["liitostunnus"]) or $srow["liitostunnus"] == 0) {
			echo "</table><br>";
			if (is_array($oletus)){
				foreach($oletus as $o => $a) {
					echo "<input type='hidden' name='oletus[$o]' value='$a'>";
				}
			}

			echo "<input type='submit' value='".t("Hae")."'>";

			echo "</form>";

			$formi  = "paaformi";
			$kentta = "nimi";
		}
		else {
			echo "	<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='ylatila' 						 value = '$ylatila'>
					<input type='hidden' name='alatila' 						 value = '$alatila'>
					<input type='hidden' name='otsikolta' 						 value = 'YES'>
					<input type='hidden' name='laskutusvkopv' 					 value = '$srow[laskutusvkopv]'>
					<input type='hidden' name='ovttunnus' 						 value = '$srow[ovttunnus]'>
					<input type='hidden' name='toim_ovttunnus' 					 value = '$srow[toim_ovttunnus]'>
					<input type='hidden' name='kolm_ovttunnus' 					 value = '$srow[kolm_ovttunnus]'>
					<input type='hidden' name='chn' 							 value = '$srow[chn]'>
					<input type='hidden' name='verkkotunnus' 					 value = '$srow[verkkotunnus]'>
					<input type='hidden' name='ytunnus' 						 value = '$srow[ytunnus]'>
					<input type='hidden' name='kolm_maa' 						 value = '$srow[kolm_maa]'>
					<input type='hidden' name='tunnusnippu' 					 value = '$srow[tunnusnippu]'>
					<input type='hidden' name='projekti'						 value = '$srow[projekti]'>
					<input type='hidden' name='jaksotettu'	 					 value = '$srow[jaksotettu]'>
					<input type='hidden' name='tunnusnippu_tarjous' 			 value = '$srow[tunnusnippu_tarjous]'>
					<input type='hidden' name='kauppatapahtuman_luonne' 		 value = '$srow[kauppatapahtuman_luonne]'>
					<input type='hidden' name='asentaja' 						 value = '$asentaja'>
					<input type='hidden' name='tyojono' 						 value = '$tyojono'>
					<input type='hidden' name='liitostunnus' 					 value = '$srow[liitostunnus]'>";

			echo "<td valign='top' style='padding:0px;'>";
			echo "<table>";
			echo "<tr>
					<th colspan='2' align='left' valign='top'>".t("Toimitusosoite").":</td></tr>";
			echo "<tr>
					<td valign='top'> ".t("Nimi").": </td>
					<td><input type='text' name='tnimi' size='35' value='$srow[toim_nimi]'></td></tr>";
			echo "<tr>
					<td></td>
					<td><input type='text' name='tnimitark' size='35' value='$srow[toim_nimitark]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Osoite").": </td>
					<td><input type='text' name='tosoite' size='35' value='$srow[toim_osoite]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Postitp").": </td>
					<td><input type='text' name='tpostino' size='10' value='$srow[toim_postino]'> <input type='text' name='tpostitp' size='21' value='$srow[toim_postitp]'></td></tr>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = pupe_query($query);

			echo "<tr><td valign='top'> ".t("Maa").": </td>
					<td><select name='toim_maa' onChange='submit();' ".js_alasvetoMaxWidth("toim_maa", 200).">";

			while ($vrow=mysql_fetch_assoc($vresult)) {
				$sel="";
				if (strtoupper($srow["toim_maa"]) == strtoupper($vrow["koodi"])) {
					$sel = "selected";
				}
				elseif ($srow["toim_maa"] == "" and strtoupper($vrow["koodi"]) == strtoupper($yhtiorow["maa"])) {
					$sel = "selected";
				}
				echo "<option value = '".strtoupper($vrow["koodi"])."' $sel>".t($vrow["nimi"])."</option>";
			}

			echo "</select></td></tr>";

			echo "</table>";
			echo "</td>";

			echo "<td valign='top' style='padding:0px;'>";
			echo "<table>";
			echo "<tr>
					<th colspan='2' align='left' valign='top'>&nbsp; ".t("Laskun postitusosoite").":</td></tr>";
			echo "<tr>
					<td valign='top'> ".t("Nimi").": </td>
					<td><input type='text' name='laskutus_nimi' size='35' value='$srow[laskutus_nimi]'></td></tr>";
			echo "<tr>
					<td></td>
					<td><input type='text' name='laskutus_nimitark' size='35' value='$srow[laskutus_nimitark]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Osoite").": </td>
					<td><input type='text' name='laskutus_osoite' size='35' value='$srow[laskutus_osoite]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Postitp").": </td>
					<td><input type='text' name='laskutus_postino' size='10' value='$srow[laskutus_postino]'> <input type='text' name='laskutus_postitp' size='21' value='$srow[laskutus_postitp]'></td></tr>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = pupe_query($query);

			echo "<tr><td valign='top'> ".t("Maa").": </td>
					<td><select name='laskutus_maa' ".js_alasvetoMaxWidth("laskutus_maa", 200).">";

			while ($vrow=mysql_fetch_assoc($vresult)) {
				$sel="";
				if (strtoupper($srow["laskutus_maa"]) == strtoupper($vrow["koodi"])) {
					$sel = "selected";
				}
				elseif ($srow["laskutus_maa"] == "" and strtoupper($vrow["koodi"]) == strtoupper($yhtiorow["maa"])) {
					$sel = "selected";
				}
				echo "<option value = '".strtoupper($vrow["koodi"])."' $sel>".t($vrow["nimi"])."</option>";
			}

			echo "</select></td></tr>";

			echo "</table>";
			echo "</td>";

			echo "</tr>";

			echo "</table><br>";


			if ((($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") and $yhtiorow["rinnakkaisostaja_myynnissa"] == "") or $srow["vienti"] =='K') {
				echo "<table>";
				echo "<tr><td style='padding:0px;'>";
				echo "<table>";

				if ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") {
					echo "<tr><th colspan='2' align='left' valign='top'>&nbsp; ".t("Rinnakkaisostaja").":</th></tr>";
				}
				else {
					echo "<tr><th colspan='2' align='left' valign='top'>&nbsp; ".t("Asiamies").":</th></tr>";
				}

				echo "<tr>
						<td valign='top'> ".t("Nimi").": </td>
						<td><input type='text' name='kolm_nimi' size='35' value='$srow[kolm_nimi]'></td></tr>";
				echo "<tr>
						<td></td>
						<td><input type='text' name='kolm_nimitark' size='35' value='$srow[kolm_nimitark]'></td></tr>";
				echo "<tr>
						<td valign='top'>".t("Osoite").": </td>
						<td><input type='text' name='kolm_osoite' size='35' value='$srow[kolm_osoite]'></td></tr>";
				echo "<tr>
						<td valign='top'>".t("Postitp").": </td>
						<td><input type='text' name='kolm_postino' size='6' value='$srow[kolm_postino]'> <input type='text' name='kolm_postitp' size='21' value='$srow[kolm_postitp]'></td></tr>";
				echo "</table>";
				echo "</td>";

				if ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") {
					echo "<td style='padding:0px;'>";
					echo "<table>";
					echo "<tr>
							<td valign='top'> ".t("Sähköpostiosoite").": </td>
							<td>$srow[email]</td></tr>";
					echo "<tr>
							<td valign='top'>".t("Kotipuhelin")."</td>
							<td>$srow[puhelin]</td></tr>";
					echo "<tr>
							<td valign='top'>".t("Työpuhelin").": </td>
							<td>$srow[fax]</td></tr>";
					echo "<tr>
							<td valign='top'>&nbsp;</td>
							<td></td></tr>";
					echo "<tr>
							<td valign='top'></td>
							<td></td></tr>";
					echo "</table>";
					echo "</td>";
				}

				echo "</tr></table><br>";
			}

			echo "<table>";

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
				echo "<tr><th colspan='4'>".t("Työmääräyksen tiedot").":</th></tr>";
			}
			elseif ($toim == "REKLAMAATIO") {
				echo "<tr><th colspan='4'>".t("Reklamaation tiedot").":</th></tr>";
			}
			elseif ($toim == "EXTRANET_REKLAMAATIO") {
				echo "<tr><th colspan='4'>".t("EXtranet-Reklamaation tiedot").":</th></tr>";
			}
			elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><th colspan='4'>".t("Valmistuksen tiedot").":</th></tr>";

			}
			elseif ($toim == "SIIRTOLISTA") {
				echo "<tr><th colspan='4'>".t("Siirtolistan tiedot").":</th></tr>";

			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><th colspan='4'>".t("Myyntitilin tiedot").":</th></tr>";

			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><th colspan='4'>".t("Tarjouksen tiedot").":</th></tr>";

			}
			elseif ($toim == "PROJEKTI") {
				echo "<tr><th colspan='4'>".t("Projektin tiedot").":</th></tr>";

			}
			else {
				echo "<tr><th colspan='4'>".t("Tilauksen tiedot").":</th></tr>";
			}

			//Tutkitaan onko laskulla maksupositioita
			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio ='$kukarow[yhtio]'
						and otunnus ='$srow[jaksotettu]'";
			$maposres = pupe_query($query);

			echo "<tr><td>".t("Maksuehto").":</td>";

			if (mysql_num_rows($maposres) > 0) {
				echo "<td>".t("Maksusopimus")."</td>";
				echo "<input type='hidden' name='maksuehto' value = '$srow[maksuehto]'>";
			}
			else {

				echo "<td><select name='maksuehto'>";

				$query = "	SELECT *
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]'
							and kaytossa = ''
							and (sallitut_maat = '' or sallitut_maat like '%$srow[maa]%')
							order by jarjestys, teksti, tunnus";
				$mresult = pupe_query($query);

				while ($row = mysql_fetch_assoc($mresult)) {
					$sel = "";
					if ($row["tunnus"] == $srow["maksuehto"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[tunnus]' $sel>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</option>";
				}
				echo "</select></td>";
			}

			echo "<td>".t("Erikoisalennus").":</td>
				<td><input type='text' name='erikoisale' value='$srow[erikoisale]'></td>
				</tr>";

			// otetaan erpcm kannasta jos mitään ei ole syötetty ruudulle
			if ($srow["erpcm"] != "" and (int) $erpcmpp == 0 and (int) $ercpmkk == 0 and (int) $erpcmvv == 0 and $erpcm_virhe == "") {
				list($erpcmvv, $erpcmkk, $erpcmpp) = explode('-', $srow["erpcm"]);
			}
			else {
				$erpcm_kasin = "KYLLA";
			}

			// jos päivämäärä on nollaa laitetaan tyhjää
			if ((int) $erpcmpp == 0 and (int) $erpcmkk == 0 and (int) $erpcmvv == 0) {
				$erpcmpp = "";
				$erpcmkk = "";
				$erpcmvv = "";
			}

			echo "<tr><td>".t("Eräpäivä").":</td>
				<td>";

			if ($toim == "YLLAPITO" or mysql_num_rows($maposres) > 0) {
				echo t("Ei käytössä");
			}
			else {
				echo "	<input type='text' name='erpcmpp' value='$erpcmpp' size='3'>
						<input type='text' name='erpcmkk' value='$erpcmkk' size='3'>
						<input type='text' name='erpcmvv' value='$erpcmvv' size='6'>";
			}
			echo "</td>";

			// Valitun toimitustavan tietoja
			$apuqu = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite  = '$srow[toimitustapa]'";
			$meapu = pupe_query($apuqu);
			$toimtapa_apuro = mysql_fetch_assoc($meapu);

			// Tsekataan toimitusehtojuttuja
			$toimehto_tresult = t_avainsana("TOIMEHTO");

			$toimehtosel  = "";
			$toimehtosel1 = "";
			$toimehtosel2 = "";
			$toimehtosel3 = "";
			$toimehtoseltark  = "";
			$toimehtoseltark1 = "";
			$toimehtoseltark2 = "";
			$toimehtoseltark3 = "";
			$toimehtoseltark_3  = "";
			$toimehtoseltark_31 = "";
			$toimehtoseltark_32 = "";
			$toimehtoseltark_33 = "";

			// Valitaan sopivin toimitusehto
			while ($row = mysql_fetch_assoc($toimehto_tresult)) {

				// Tarkka mätsi
				if ($srow["toimitusehto"] != "" and strtoupper($srow["toimitusehto"]) == strtoupper($row["selite"]." ".$row["selitetark"])) {
					$toimehtosel1 = $row["tunnus"];
					$toimehtoseltark1 = $row["selitetark"];
					$toimehtoseltark_31 = $row["selitetark_3"];
				}

				// Alku mätsää
				if ($srow["toimitusehto"] != "" and strtoupper(substr($srow["toimitusehto"], 0, 3)) == strtoupper($row["selite"])) {
					$toimehtosel2 = $row["tunnus"];
					$toimehtoseltark2 = substr($srow["toimitusehto"], 4);
					$toimehtoseltark_32 = $row["selitetark_3"];
				}

				// Otetaan yhtiön oletus
				if ($srow["toimitusehto"] == "" and strtoupper($yhtiorow['oletus_toimitusehto']) == strtoupper($row["selite"]." ".$row["selitetark"])) {
					$toimehtosel3 = $row["tunnus"];
					$toimehtoseltark3 = $row["selitetark"];
					$toimehtoseltark_33 = $row["selitetark_3"];
				}

				if ($row["selitetark_3"] == "LAHETTAJAN_SOPIMUS") {
					echo "<div style='display: none; visibility: hidden;' id='merahti_toimitusehto_$row[tunnus]'>K</div>";
				}
				else {
					echo "<div style='display: none; visibility: hidden;' id='merahti_toimitusehto_$row[tunnus]'></div>";
				}
			}

			if ($toimehtosel1 != "") {
				$toimehtosel		= $toimehtosel1;
				$toimehtoseltark	= $toimehtoseltark1;
				$toimehtoseltark_3	= $toimehtoseltark_31;
			}
			elseif ($toimehtosel2 != "") {
				$toimehtosel		= $toimehtosel2;
				$toimehtoseltark	= $toimehtoseltark2;
				$toimehtoseltark_3	= $toimehtoseltark_32;
			}
			elseif ($toimehtosel3 != "") {
				$toimehtosel 		= $toimehtosel3;
				$toimehtoseltark 	= $toimehtoseltark3;
				$toimehtoseltark_3	= $toimehtoseltark_33;
			}

			$state_chk = '';

			if (is_resource($rakre_chkres) and mysql_num_rows($rakre_chkres) > 0) {
				$state_chk = 'disabled';
			}

			// haetaan oletus rahdinmaksaja
			$apu_rahdinmaksaja = $srow["kohdistettu"];

			if ($apu_rahdinmaksaja == "O") {

				// Onko toimitusehdon takana määritelty rahdinmaksaja? Tämä yliajaa toimitustavan takana olevan.
				if ($toimehtoseltark_3 == "LAHETTAJAN_SOPIMUS") {
					$apu_rahdinmaksaja = "K";
				}
				elseif ($toimehtoseltark_3 == "VASTAANOTTAJAN_SOPIMUS") {
					$apu_rahdinmaksaja = "";
				}
				else {
					$apu_rahdinmaksaja = $toimtapa_apuro['merahti'];
				}
			}

			$sel = array("K" => "", "V" => "");

			if ($apu_rahdinmaksaja == "") $apu_rahdinmaksaja = "V";
			$sel[$apu_rahdinmaksaja] = "selected";

			echo "<td>".t("Rahtisopimus").":</td>
					<td><select id='merahti' name='maksaja' {$state_chk}>
					<option value='O'>".t("Oletus")."</option>
					<option value='K' $sel[K]>".t("Käytetään lähettäjän rahtisopimusta")."</option>
					<option value=''  $sel[V]>".t("Käytetään vastaanottajan rahtisopimusta")."</option>
					</select>";

			// HUOM: jos varsinainen on disabloitu niin siirretään tieto hidddenissä
			if ($state_chk == 'disabled') {
				echo "<input type='hidden' name='maksaja' value='$apu_rahdinmaksaja'>";
			}

			echo "</td></tr>";

			if ($yhtiorow["viitteen_kasinsyotto"] != "") {
				echo "<tr><td>".t("Viitenumero").":</td><td>";
				echo "<input type='text' name='kasinsyotetty_viite' value='$srow[kasinsyotetty_viite]' size='20'>";
				echo "</td><td></td><td></td></tr>";
			}

			$query = "	SELECT tunnus, selite, merahti
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and (sallitut_maat = '' or sallitut_maat like '%$srow[toim_maa]%')
						and (extranet in ('','M') or selite = '$srow[toimitustapa]')
						ORDER BY jarjestys,selite";
			$tresult = pupe_query($query);

			while ($row = mysql_fetch_assoc($tresult)) {
				echo "<div style='display: none; visibility: hidden;' id='merahti_toimitustapa_$row[tunnus]'>$row[merahti]</div>";
			}

			mysql_data_seek($tresult, 0);

			echo "<tr><td>".t("Toimitustapa").":</td>";
			echo "<td><select id='toimitustapa' name='toimitustapa' onchange='merahti_oletus(\"toimitustapa\");' {$state_chk}>";

			while ($row = mysql_fetch_assoc($tresult)) {

				$sel = "";
				if ($row["selite"] == $srow["toimitustapa"]) {
					$sel = 'selected';
				}

				echo "<option id='toimitustapa_$row[tunnus]' value='$row[selite]' $sel>";
				echo t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV");
				echo "</option>";
			}
			echo "</select>";

			// HUOM: jos varsinainen on disabloitu niin siirretään tieto hidddenissä
			if ($state_chk == 'disabled') {
				echo "<input type='hidden' name='toimitustapa' value='$srow[toimitustapa]'>";
			}

			echo "</td>";

			//yhtiön oletusalvi!
			$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$wtres  = pupe_query($wquery);
			$wtrow  = mysql_fetch_assoc($wtres);

			echo "<td>".t("Alv").":</td>";

			if ($alv_velvollisuus != "") {
				echo " <td>".t("Verollinen myynti (ulkomaan VAT)")."
						<input type='hidden' name='alv' value='$alv'>
						</td></tr>";
			}
			elseif ($srow["vienti"] == '') {
				if (isset($srow)) {
					if ($srow['alv'] == 0) {
						$sel_veroton 	= 'selected';
						$sel_verollinen = '';
					}
					if ($srow['alv'] == $wtrow["selite"]) {
						$sel_verollinen = 'selected';
						$sel_veroton 	= '';
					}
				}

				echo " <td>
						<select name='alv'>
						<option value='$wtrow[selite]' $sel_verollinen>".t("Verollinen myynti")."</option>
						<option value='0' $sel_veroton>".t("Veroton myynti")."</option>
						</select>
						</td></tr>";
			}
			else {
				echo " <td>".t("Veroton myynti")."
						<input type='hidden' name='alv' value='0'>
						</td></tr>";
			}

			if ($kukarow['kesken'] == 0) {
				$toimpp = $kerpp = date("j");
				$toimkk = $kerkk = date("n");
				$toimvv = $kervv = date("Y");
			}
			else {
				list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
				list($kervv, $kerkk, $kerpp)    = explode('-', $srow["kerayspvm"]);
				$kerpp = substr($kerpp,0,2);
				$toimpp = substr($toimpp,0,2);
			}

			//voidaan tarvita jos asiakas on vaihdettu
			if ($toimvv == '') {
				$toimpp = date("j");
				$toimkk = date("n");
				$toimvv = date("Y");
			}
			if ($kervv == '') {
				$kerpp = date("j");
				$kerkk = date("n");
				$kervv = date("Y");
			}

			echo "<tr><td>".t("Keräysajankohta").": </td><td valign='middle'>";

			if ($toim == "YLLAPITO") {
				echo t("Ei käytössä");
			}
			else {
				echo "<input type='text' name='kerpp' value='$kerpp' size='3'><input type='text' name='kerkk' value='$kerkk' size='3'><input type='text' name='kervv' value='$kervv' size='6'>
						<input type='hidden' name='vkerayspvm' value='".substr($srow["kerayspvm"],0,10)."'>";

				if ($srow["keraysvko"] != '') {
					$keraysvko = date("W", strtotime($srow["kerayspvm"]));
				}
				else {
					$keraysvko = "";
				}

				echo t("Vko").": <input type='text' name='keraysvko' value='$keraysvko' size='2'>";

				echo "<select name='keraysvkopva'>";
				echo "<option value=''></option>";

				foreach ($DAY_ARRAY as $di => $day) {
					if ($srow["keraysvko"] == $di) {
						$sel = "SELECTED";
					}
					else {
						$sel = "";
					}

					echo "<option value='$di' $sel>$day</option>";
				}

				echo "</select>";
			}

			echo "</td>";

			if ($srow['vienti'] == '') {
				$sel1 = 'SELECTED';
				$sel2 = '';
				$sel3 = '';
				$vteksti=t("Kotimaa");
			}
			elseif ($srow['vienti'] == 'E') {
				$sel1 = '';
				$sel2 = 'SELECTED';
				$sel3 = '';
				$vteksti=t("Vienti EU");
			}
			elseif ($srow['vienti'] == 'K') {
				$sel1 = '';
				$sel2 = '';
				$sel3 = 'SELECTED';
				$vteksti=t("Vienti ei-EU");
			}

			echo "<td>".t("Vienti").":</td><td>";

			if ($srow["alv"] == 99.99) {
				echo "<input type='hidden' name='vienti' value=''>".t("Kotimaan marginaalimyyntiä");
			}
			elseif ($alv_velvollisuus != "") {
				echo "<input type='hidden' name='vienti' value=''>".t("Kotimaa")." ($srow[maa])";
			}
			else {
				echo "
					<select name='vienti'>
					<option value=''  $sel1>".t("Kotimaa")."</option>
					<option value='E' $sel2>".t("Vienti EU")."</option>
					<option value='K' $sel3>".t("Vienti ei-EU")."</option>
					</select>";

				$sel = $srow['kolmikantakauppa'] != "" ? " selected" : "";


				echo "<select name='kolmikantakauppa'>";
				echo "<option value=''>",t("Normaali myynti"),"</option>";
				echo "<option value='M'{$sel}>",t("Kolmikantakauppa"),"</option>";
				echo "</select>";
			}

			echo "</td></tr>";

			echo "<tr><td>".t("Toimitusajankohta").": </td><td valign='middle'>";

			if($toim == 'TARJOUS') {
				if(!checkdate($toimkk, $toimpp, $toimvv)) {
					//Kyseessä on tarjouksen toimitusajan piilottamiseen pdf:ltä käytettävä toiminnallisuus toimaika = 0000-00-00 jos se on haluttu piilottaa
					$naytetaanko_pdf_sel = "";
				}
				else {
					$naytetaanko_pdf_sel = "checked='CHECKED'";
				}
				echo t("Näytetäänkö tarjouksella")."<input type='checkbox' name='naytetaanko_tarjouksella' $naytetaanko_pdf_sel /><br/>";
			}

			if ($toim == "YLLAPITO") {
				echo t("Ei käytössä");
			}
			else {
				echo "<input type='text' name='toimpp' value='$toimpp' size='3'><input type='text' name='toimkk' value='$toimkk' size='3'><input type='text' name='toimvv' value='$toimvv' size='6'>
					<input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'>";

				if ($srow["toimvko"] != '') {
					$toimvko = date("W", strtotime($srow["toimaika"]));
				}
				else {
					$toimvko = "";
				}

				echo t("Vko").": <input type='text' name='toimvko' value='$toimvko' size='2'>";

				echo "<select name='toimvkopva'>";
				echo "<option value=''></option>";

				foreach ($DAY_ARRAY as $di => $day) {
					if ($srow["toimvko"] == $di) {
						$sel = "SELECTED";
					}
					else {
						$sel = "";
					}

					echo "<option value='$di' $sel>$day</option>";
				}

				echo "</select>";
			}

			echo "</td>";

			//jos käyttäjällä on myyjänro
			if ($kukarow["myyja"] != 0 and  (int) $srow["myyja"] == 0) {
				$myyjanro = $kukarow["myyja"];
			}
			else {
				$myyjanro = "";
			}

			echo "<td>".t("Myyjä")."</td>";
			echo "<td><select name='myyja'>";

			$query = "	SELECT DISTINCT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja
						FROM kuka
						JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
						WHERE kuka.yhtio = '$kukarow[yhtio]'
						AND (kuka.extranet = '' or kuka.tunnus='$srow[myyja]')
						ORDER BY kuka.nimi";
			$yresult = pupe_query($query);

			while ($row = mysql_fetch_assoc($yresult)) {

				$sel = "";
				if ($srow['myyja'] == '' or $srow['myyja'] == 0) {
					if ($row['nimi'] == $kukarow['nimi']) {
						$sel = 'selected';
					}
				}
				else {
					if ($row['tunnus'] == $srow['myyja']) {
						$sel = 'selected';
					}
				}
				echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			echo "</select></td></tr>";

			//	Jos halutaan niin voidaan liittää hieman yhteyshenkilöitä
			if ($yhtiorow["tilauksen_yhteyshenkilot"] == "K") {
				echo "<tr><td>".t("Tekninen yhteyshenkilö").":</td>
				<td><select id='yhteyshenkilo_tekninen' name='yhteyshenkilo_tekninen'>";

				$query = "	SELECT tunnus, if (titteli != '', concat_ws(', ' ,nimi, titteli), nimi) nimi
							FROM yhteyshenkilo
							WHERE yhtio = '$kukarow[yhtio]'
							and liitostunnus = '$srow[liitostunnus]'
							and tyyppi = 'A'
							ORDER BY nimi";
				$yhresult = pupe_query($query);

				echo "<option value=''>".t("Ei teknistä yhteyshenkilöä")."</option>";

				while($row = mysql_fetch_assoc($yhresult)) {
					$sel = "";
					if ($row["tunnus"] == $srow["yhteyshenkilo_tekninen"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
				}

				echo "</select> ";
				if (tarkista_oikeus("yllapito.php", "yhteyshenkilo", "X")) {
					echo " <a href='#' onclick=\"js_open_yllapito('yhteyshenkilo_tekninen','toim=yhteyshenkilo&laji=A&lukitse_avaimeen=$srow[liitostunnus]');\"><img src='{$palvelin2}pics/lullacons/add.png'>".t("Uusi yhteyshenkilö")."</a>";
				}
				echo "</td>";

				echo "<td>".t("Projektipaallikko").":</td>
					<td><select name='projektipaallikko'>";

				echo "<option value=''>".t("Ei projektipäällikköä")."</option>";

				$query = "	SELECT DISTINCT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
							FROM kuka
							JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
							WHERE kuka.yhtio  = '$kukarow[yhtio]'
							AND kuka.extranet = ''
							AND kuka.asema	  = 'PP'
							ORDER BY kuka.nimi";
				$yresult = pupe_query($query);

				while($row = mysql_fetch_assoc($yresult)) {
					$sel = "";
					if ($row["kuka"] == $srow["projektipaallikko"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
				}
				echo "</select>";

				echo "</td></tr>";

				echo "<tr><td>".t("Kaupallinen yhteyshenkilö").":</td>
				<td><select id='yhteyshenkilo_kaupallinen' name='yhteyshenkilo_kaupallinen'>";

				echo "	<option value=''>".t("Ei kaupallista käsittelijää")."</option>";
				mysql_data_seek($yhresult,0);

				while($row = mysql_fetch_assoc($yhresult)) {
					$sel = "";
					if ($row["tunnus"] == $srow["yhteyshenkilo_kaupallinen"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
				}

				echo "</select> ";
				if (tarkista_oikeus("yllapito.php", "yhteyshenkilo", "X")) {
					echo " <a href='#' onclick=\"js_open_yllapito('yhteyshenkilo_kaupallinen','toim=yhteyshenkilo&laji=A&lukitse_avaimeen=$srow[liitostunnus]');\"><img src='{$palvelin2}pics/lullacons/add.png'>".t("Uusi yhteyshenkilö")."</a>";
				}
				echo "</td>";

			}

			if ($yhtiorow["tilauksen_yhteyshenkilot"] == "K") {
				//	Fiksataan HTML
				echo "<td></td><td></td></tr>";
			}

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on työmääräys
				echo "<input type='hidden' name='tilaustyyppi' value='A'>";

				echo "<td>".t("Työmääräys")."</td>";
			}
			elseif ($toim == "REKLAMAATIO") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on työmääräys
				echo "<input type='hidden' name='tilaustyyppi' value='R'>";

				echo "<td>".t("Reklamaatio")."</td>";
			}

			elseif ($toim == "EXTRANET_REKLAMAATIO") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on työmääräys
				echo "<input type='hidden' name='tilaustyyppi' value='R'>";

				echo "<td>".t("Extranet-Reklamaatio")."</td>";
			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on tarjous
				echo "<input type='hidden' name='tilaustyyppi' value='T'>";

				echo "<td>".t("Tarjous")."</td>";
			}
			elseif ($toim == "PROJEKTI") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on tarjous
				echo "<input type='hidden' name='tilaustyyppi' value='P'>";

				echo "<td>".t("Projekti")."</td>";
			}
			elseif ($toim == "YLLAPITO") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on ylläpitosopimus
				echo "<input type='hidden' name='tilaustyyppi' value='0'>";

				echo "<td>".t("Ylläpitosopimus")."</td>";
			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on myyntitili niin laitetaan tilaustyypiksi M
				echo "<input type='hidden' name='tilaustyyppi' value='M'>";

				$query = "	SELECT tunnus
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and alkuhyllyalue = '!!M'
							and loppuhyllyalue = '!!M'";
				$tresult = pupe_query($query);
				$mrow = mysql_fetch_assoc($tresult);

				//pupesoftissa on myytitili aina varastoalueella ##M
				echo "<input type='hidden' name='clearing' value='$mrow[tunnus]'>";


				echo "<td>".t("Myyntitili")."</td>";
			}
			elseif ($toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on valmistus asiakkaalle niin laitetaan tilaustyypiksi V
				echo "<input type='hidden' name='tilaustyyppi' value='V'>";

				echo "<td>".t("Valmistus-Tilaus")."</td>";
			}
			elseif ($toim == "ENNAKKO" or $srow["tilaustyyppi"] == 'E') {
				//Tarvitaan ainakin Ennakkotilauksilla
				if ($srow["clearing"] != '') {
					echo "<input type='hidden' name='clearing' value='$srow[clearing]'>";
				}

				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<input type='hidden' name='tilaustyyppi' value='E'>";
				echo "<td>".t("Ennakkotilaus")."</td>";
			}
			elseif ($srow["tilaustyyppi"] != 'E' or $templ == 0) {

				//Tarvitaan ainakin tarjoustilauksilla
				if ($srow["clearing"] != '') {
					echo "<input type='hidden' name='clearing' value='$srow[clearing]'>";
				}

				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<td><select name='tilaustyyppi'>";

				$sel = array();
				$sel[$srow["tilaustyyppi"]] = "selected";

				echo "<option value='N' $sel[N]>".t("Normaalitilaus")."</option>";
				echo "<option value='E' $sel[E]>".t("Ennakkotilaus")."</option>";
				echo "<option value='T' $sel[T]>".t("Tarjoustilaus")."</option>";
				echo "<option value='2' $sel[2]>".t("Varastotäydennys")."</option>";
				echo "<option value='7' $sel[7]>".t("Tehdastilaus")."</option>";
				echo "<option value='S' $sel[S]>".t("Sarjatilaus")."</option>";
				echo "<option value='8' $sel[8]>".t("Muiden mukana")."</option>";
				echo "<option value='A' $sel[A]>".t("Työmääräys")."</option>";

				echo "</select></td>";
			}


			echo "<td>".t("Myy varastosta").":</td>";
			echo "<td><select name='varasto'><option value='0'>".t("Kaikista")."</option>";

			$varastot_array = array();
			$varastot_lisa_query = "";

			if ($kukarow["varasto"] != "") {
				$varastot_array = explode(",", $kukarow["varasto"]);
				$varastot_lista = mysql_real_escape_string($kukarow["varasto"]);
				$varastot_lisa_query = " and varastopaikat.tunnus in ($varastot_lista) ";
			}

			$query  = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						$varastot_lisa_query
						AND tyyppi != 'P'
						order by tyyppi, nimitys";
			$vares = pupe_query($query);

			while ($varow = mysql_fetch_assoc($vares)) {

				if ($varow["tyyppi"] == '' or in_array($varow["tunnus"], $varastot_array)) {

					$sel = '';

					// Jos kukarow varasto on vain YKSI varasto ja oletus varasto ei valittu, niin silloin valitaan $kukarow['varasto']
					if ($kukarow["kesken"] == 0 and $varow['tunnus'] == $kukarow['varasto'] and $srow['varasto'] == '') {
						$sel = 'selected';
					}
					// jos kukarow['oletus_varasto'] valittu ja myyntitilauksella ei ole varastoa valittuna, valitaan käyttäjän oletus varasto
					elseif ($kukarow["kesken"] == 0 and $yhtiorow['oletusvarasto_tilaukselle'] == 'O' and $varow['tunnus'] == $kukarow['oletus_varasto'] and $srow['varasto'] == '') {
						$sel = 'selected';
					}
					elseif ($varow['tunnus'] == $srow["varasto"]) {
						$sel = 'selected';
					}

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}
			}

			echo "</select></td></tr>";

			$tresult = t_avainsana("TV");

			echo "<tr><td>".t("Tilausvahvistus").":</td>";
			echo "<td><select name='tilausvahvistus' ".js_alasvetoMaxWidth($nimi, 300)."><option value=' '>".t("Ei vahvistusta")."</option>";

			while($row = mysql_fetch_assoc($tresult)) {
				$sel = "";
				if ($row["selite"]== $srow["tilausvahvistus"]) $sel = 'selected';
				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}
			echo "</select></td>";

			$eilah = '';
			$eiket = '';
			$sisah = '';
			$osath = '';
			$laskkielto = '';
			$jtki = "";
			$rahtivapaa_chk = '';

			if ($srow['eilahetetta'] != '') $eilah          = 'CHECKED';
			if ($srow['ketjutus']    != '') $eiket          = 'CHECKED';
			if ($srow['sisainen']    != '') $sisah          = 'CHECKED';
			if ($srow['rahtivapaa']  != '') $rahtivapaa_chk = 'CHECKED';
			if ($srow['chn'] == '999')      $laskkielto     = 'CHECKED';

			// Tarjouksia ei oletuksena osatoimiteta
			if ($srow['osatoimitus'] != '' or ((in_array($toim, array("TARJOUS", "PROJEKTI")) or $oletus["osatoimitus"] != "") and $kukarow["kesken"] == 0) or ($yhtiorow['myyntitilaus_osatoimitus'] == 'E' and $kukarow['kesken'] == 0 and in_array($toim, array("RIVISYOTTO", "PIKATILAUS")))) $osath = 'CHECKED';

			// Pitää olla myyntitilaus ja käyttäjällä oikeus laskuttaa tilaus
			if (($toim == "PIKATILAUS" or $toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or $toim =="REKLAMAATIO") and (tarkista_oikeus("valitse_laskutettavat_tilaukset.php") or
			tarkista_oikeus("valitse_laskutettavat_tilaukset.php", "VAINLASKUTA") or
			tarkista_oikeus("valitse_laskutettavat_tilaukset.php", "VIENTI") or
			tarkista_oikeus("verkkolasku.php"))) {
				echo "<td>".t("Laskuta heti")." (".t("sisäinen")."):</td><td><input type='checkbox' name='sisainen' $sisah></td>";
			}
			else {
				echo "<td></td><td></td>";
			}
			echo "</tr>";

			echo "<tr><td>".t("Keräysprioriteetti").":</td><td>";

			$tresult = t_avainsana("ASIAKASLUOKKA");

			echo "<select name='luokka'><option value=''>".t("Tyhjä")."</option>";

			while($row = mysql_fetch_assoc($tresult)) {
				$sel = "";

				if ($row["selite"] == $srow["luokka"]) $sel = 'selected';

				echo "<option value='$row[selite]' $sel>$row[selite]";
				if ($row["selitetark"] != "") echo " - $row[selitetark]";
				echo "</option>";
			}
			echo "</select>";

			echo "</td>";
			if ($toim != "MYYNTITILI") {
				echo "<td>".t("Suoraan laskutukseen").":</td><td><input type='checkbox' name='eilahe' $eilah></td>";
			}
			else {
				echo "<td></td><td></td>";
			}
			echo "</tr>";

			echo "<tr>";
			echo "<td>".t("Toimitusehto").":</td><td>";

			echo "<select name='toimitusehto' id = 'toimitusehto' onchange='toimehtoTarkenne(\"toimitusehto\"); merahti_oletus(\"toimitusehto\");' ".js_alasvetoMaxWidth("toimitusehto", 60).">";

			mysql_data_seek($toimehto_tresult, 0);

			echo "<option value=''></option>";

			while ($row = mysql_fetch_assoc($toimehto_tresult)) {
				$sel = "";
				if ($toimehtosel == $row["tunnus"]) $sel = "SELECTED";

				echo "<option value='$row[selite]' id='toimitusehto_$row[tunnus]' $sel>$row[selite] - $row[selitetark] $row[selitetark_2]</option>";
			}

			echo "</select>";
			echo " <input type='text' id='toimitusehtoLisa' name='kasitoimehto' value='$toimehtoseltark' size='20'>";
			echo "</td>";

			if ($toim != "MYYNTITILI") {
				echo "<td>".t("Tilauksesta oma lasku").":</td><td><input type='checkbox' name='ketjutus' $eiket></td>";
			}
			else {
				echo "<td></td><td></td>";
			}
			echo "</tr>";

			echo "<tr>";

			if ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") {
				echo "<td>".t("Vaihtokohteen toimitusehto")."</td><td>";

				$tresult = t_avainsana("TOIMEHTO");

				echo "<select name='toimitusehto2' id='toimitusehto2' onchange='toimehtoTarkenne(\"toimitusehto2\");' ".js_alasvetoMaxWidth("toimitusehto2", 60).">";

				$toimehtosel  = "";
				$toimehtosel1 = "";
				$toimehtosel2 = "";
				$toimehtosel3 = "";
				$toimehtoseltark  = "";
				$toimehtoseltark1 = "";
				$toimehtoseltark2 = "";
				$toimehtoseltark3 = "";

				// Valitaan sopivin toimitusehto
				while ($row = mysql_fetch_assoc($tresult)) {

					// Tarkka mätsi
					if ($srow["toimitusehto2"] != "" and strtoupper($srow["toimitusehto2"]) == strtoupper($row["selite"]." ".$row["selitetark"])) {
						$toimehtosel1 = $row["tunnus"];
						$toimehtoseltark1 = $row["selitetark"];
					}

					// Alku mätsää
					if ($srow["toimitusehto2"] != "" and strtoupper(substr($srow["toimitusehto2"], 0, 3)) == strtoupper($row["selite"])) {
						$toimehtosel2 = $row["tunnus"];
						$toimehtoseltark2 = substr($srow["toimitusehto2"], 4);
					}

					// Otetaan yhtiön oletus
					if ($srow["toimitusehto2"] == "" and strtoupper($yhtiorow['oletus_toimitusehto2']) == strtoupper($row["selite"]." ".$row["selitetark"])) {
						$toimehtosel3 = $row["tunnus"];
						$toimehtoseltark3 = $row["selitetark"];
					}
				}

				if ($toimehtosel1 != "") $toimehtosel = $toimehtosel1;
				elseif ($toimehtosel2 != "") $toimehtosel = $toimehtosel2;
				elseif ($toimehtosel3 != "") $toimehtosel = $toimehtosel3;

				mysql_data_seek($tresult, 0);

				echo "<option value=''></option>";

				while ($row = mysql_fetch_assoc($tresult)) {
					$sel = "";
					if ($toimehtosel == $row["tunnus"]) $sel = "SELECTED";

					echo "<option value='$row[selite]' $sel>$row[selite] - $row[selitetark] $row[selitetark_2]</option>";
				}

				echo "</select>";

				if ($toimehtoseltark1 != "") $toimehtoseltark = $toimehtoseltark1;
				elseif ($toimehtoseltark2 != "") $toimehtoseltark = $toimehtoseltark2;
				elseif ($toimehtoseltark3 != "") $toimehtoseltark = $toimehtoseltark3;

				echo " <input type='text' id='toimitusehto2Lisa' name='kasitoimehto2' value='$toimehtoseltark' size='20'>";
				echo "</td>";
			}
			else {
				echo "<td></td><td></td>";
			}

			if ($toim != "MYYNTITILI") {
				echo "<td>".t("Tilausta ei osatoimiteta").":</td><td><input type='checkbox' name='osatoimitus' $osath></td>";
			}
			else {
				echo "<td></td><td></td>";
			}
			echo "</tr>";
			echo "<tr><td>".t("Asiakkaan piiri").":</td><td>";

			$pres = t_avainsana("PIIRI");

			echo "<select name='piiri'>";

			while ($prow = mysql_fetch_assoc($pres)) {
				$chk = "";

				if ($prow["selite"] == $srow["piiri"]) {
					$chk = "SELECTED";
				}

				echo "<option value='$prow[selite]' $chk>$prow[selite] - $prow[selitetark]</option>";

			}
			echo "</select>";
			echo "</td>";

			$jtksel = array("Y" => "", "o" => "");
			$jtksel[$srow["jtkielto"]] = "SELECTED";

			echo "<td>".t("JT-käsittely").":</td><td>
					<select name='jtkielto' ".js_alasvetoMaxWidth("jtkielto", 200).">
					<option value = ''>".t("Normaali")."</option>
					<option value = 'Y' $jtksel[Y]>".t("Jälkitoimituksia ei yhdistetä")."</option>
					<option value = 'Z' $jtksel[Z]>".t("Ei osatoimituksia")."</option>
					<option value = 'o' $jtksel[o]>".t("Ei jälkitoimituksia")."</option>
					</select></td></tr>";

			echo "<tr>";

			//laskun valuuttakoodi
			if ($srow["valkoodi"] == '') {
				$srow["valkoodi"] = $yhtiorow["valkoodi"];
			}

			$query = "	SELECT nimi, kurssi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys";
			$vresult = pupe_query($query);

			echo "<td>".t("Valuutta").":</td><td><select name='valkoodi'>";

			while ($vrow = mysql_fetch_assoc($vresult)) {
				$sel="";
				if ($srow["valkoodi"] == $vrow['nimi']) {
					$sel = "selected";
				}

				echo "<option value = '$vrow[nimi]##$vrow[kurssi]' $sel>$vrow[nimi]</option>";
			}

			echo "</select></td>";

			echo "<td>",t("Rahtivapaa"),":</td>";
			echo "<td>";
			echo "<input type='checkbox' id='rahtivapaa' name='rahtivapaa' $rahtivapaa_chk {$state_chk}>";

			// HUOM: jos varsinainen on disabloitu niin siirretään tieto hidddenissä
			if ($state_chk == 'disabled') {
				echo "<input type='hidden' name='rahtivapaa' value='$srow[rahtivapaa]'>";
			}

			echo "</td>";
			echo "</tr>";

			# Lähetetyypin valinta
			$lahetetyypit = t_avainsana("LAHETETYYPPI");

			echo "<tr>";
			echo "<td>".t("Lähetetyyppi").":</td>";
			echo "<td><select name='lahetetyyppi' style='max-width: 300px'>";

			$lahetetyyppi = pupesoft_lahetetyyppi($laskurow['tunnus'], $srow['liitostunnus']);

			while ($vrow = mysql_fetch_assoc($lahetetyypit)) {
				$sel = "";
				if ($lahetetyyppi == $vrow['selite']) $sel = "selected";
				echo "<option value='{$vrow['selite']}' $sel>".$vrow['selitetark']."</option>";
			}

			echo "</select></td>";

			if ($yhtiorow['laskutuskielto'] != 0 and $toim != "MYYNTITILI") {
				echo "<td>".t("Laskutuskieltoon").":</td><td><input type='checkbox' name='laskutuskielto' $laskkielto></td>";
			}
			echo "</tr>";

			if ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS") {
				$labels = t_avainsana("LABEL");

				if (mysql_num_rows($labels) > 0) {

					echo "<tr>";
					echo "<td>",t("Luokitus"),":</td>";
					echo "<td><select name='label' id='label' style='max-width: 300px'>";
					echo "<option value=''>".t("Ei valintaa")."</option>";

					while ($labelrow = mysql_fetch_assoc($labels)) {
						$sel = $srow['label'] == $labelrow['tunnus'] ? " selected" : "";
						echo "<option value='{$labelrow['tunnus']}'{$sel}>{$labelrow['selitetark']}</option>";
					}

					echo "</select></td>";

					echo "<td></td><td></td></tr>";
				}
			}

			if ($toim == "TARJOUS" or $yhtiorow["splittauskielto"] == "E") {
				echo "<tr>";
			}
			if ($toim == "TARJOUS") {

				echo "<td>",t("Tarjouksen voimassaoloaika"),":</td><td>";

				if ($srow["olmapvm"] != "" and $srow["olmapvm"] != "0000-00-00") {
					list($voimaika_vv, $voimaika_kk, $voimaika_pp) = explode("-",$srow["olmapvm"]);
				}
				else {

					if ($yhtiorow["tarjouksen_voimaika"] != "") {
						$add_days = "+$yhtiorow[tarjouksen_voimaika] day";
					}
					else {
						$add_days = "now";
					}

					list($voimaika_pp, $voimaika_kk, $voimaika_vv) = explode("-",date("j-n-Y",strtotime($add_days)));
				}

				echo "	<input type='text' name='voimaika_pp' value='$voimaika_pp' size='3'>
						<input type='text' name='voimaika_kk' value='$voimaika_kk' size='3'>
						<input type='text' name='voimaika_vv' value='$voimaika_vv' size='6'>
						</td>";
			}
			elseif ($yhtiorow["splittauskielto"] == "E") {
				echo "<td></td><td></td>";
			}

			if ($yhtiorow["splittauskielto"] == "E") {
				$laskkielto = "";
				if (($yhtiorow["splittauskielto"] != '' and !isset($srow["splittauskielto"])) or $srow["splittauskielto"] != '') $laskkielto = 'CHECKED';

				echo "<td>".t("Splittauskielto").":</td><td><input type='checkbox' value='E' name='splittauskielto' $laskkielto></td>";
			}

			if ($toim == "TARJOUS" or $yhtiorow["splittauskielto"] == "E") {
				echo "</tr>";
			}

			if ($yhtiorow['lomakkeiden_allekirjoitus'] == 'X' and ($toim == "TARJOUS" or $toim == "RIVISYOTTO" or $toim == "PIKATILAUS")) {
				echo "<tr>";
				echo "<td>".t("Allekirjoittaja")."</td>";
				echo "<td><select name='allekirjoittaja'>";

				$query = "	SELECT DISTINCT kuka.tunnus, kuka.kuka, kuka.nimi
							FROM kuka
							JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
							WHERE kuka.yhtio  = '$kukarow[yhtio]'
							AND kuka.nimi    != ''
							AND kuka.extranet = ''
							ORDER BY kuka.nimi";
				$yresult = pupe_query($query);

				echo "<option value=''>".t("Valitse allekirjoittaja")."</option>";

				while ($row = mysql_fetch_assoc($yresult)) {
					$sel = "";
					if ($row['tunnus'] == $srow['allekirjoittaja']) {
						$sel = 'selected';
					}
					echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
				}
				echo "</select></td></tr>";
			}

			if (($toim == "RIVISYOTTO" or $toim == "PIKATILAUS") and ((trim($kantaasiakastunnus) != '' and trim($kantaasiakastunnus) != '0') or (trim($srow['kantaasiakastunnus']) != '' and trim($srow['kantaasiakastunnus']) != '0'))) {
				if ($srow['kantaasiakastunnus'] != '') $kantaasiakastunnus = $srow['kantaasiakastunnus'];

				echo "<tr><td>",t("Kanta-asiakastunnus"),"</td><td><input type='text' name='kantaasiakastunnus' value='$kantaasiakastunnus'></td><td>&nbsp;</td><td>&nbsp;</td></tr>";
			}

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or (($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") and $yhtiorow["tyomaaraystiedot_tarjouksella"] == "")) {
				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th colspan='4'>".t("Työmääräyksen lisätiedot").":</th></tr>";

				echo "	<tr>
						<td>".t("Työjono").":</td><td>";

				if (!isset($srow["tyojono"])) {
					// Onko käyttäjällä oletustyöjono
					$query = "	SELECT selite
								FROM avainsana
								WHERE yhtio 	= '$kukarow[yhtio]'
								and laji		= 'TYOM_TYOLINJA'
								and selitetark	= '$kukarow[kuka]'
								and selite     != ''";
					$yresult = pupe_query($query);

					if (mysql_num_rows($yresult) > 0) {
						$yrow = mysql_fetch_array($yresult);

						$srow["tyojono"] = $yrow["selite"];
					}
				}

				echo "<select name='tyojono'>";

				$vresult = t_avainsana("TYOM_TYOJONO");
				echo "<option value = '' >".t("Ei työjonoa")."</option>";

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$sel="";
					if ($srow["tyojono"] == $vrow['selite']) {
						$sel = "selected";
					}
					elseif ($vrow["selitetark_3"] == 'OLETUS' and !isset($srow["tyojono"])) {
						$sel = "selected";
					}

					echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
				}
				echo "</select>";

				echo "	</td>
						<td>".t("Työstatus").":</td><td>";

				echo "<select name='tyostatus'>";

				$vresult = t_avainsana("TYOM_TYOSTATUS");
				echo "<option value = '' >".t("Ei työstatusta")."</option>";

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$sel="";
					if ($srow["tyostatus"] == $vrow['selite']) {
						$sel = "selected";
					}
					elseif ($vrow["selitetark_3"] == 'OLETUS' and !isset($srow["tyostatus"])) {
						$sel = "selected";
					}

					echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
				}

				echo "</select>";
				echo "</td></tr>";

				echo "<tr><td>".t("Prioriteetti").":</td><td colspan=3>";
				echo "<select name='prioriteetti'>";

				$vresult = t_avainsana("TYOM_PRIORIT");
				echo "<option value = '' >".t("Ei prioriteettiä")."</option>";

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$sel="";
					if ($srow["prioriteetti"] == $vrow['selite']) {
						$sel = "selected";
					}
					elseif ($vrow["selitetark_3"] == 'OLETUS' and !isset($srow["prioriteetti"])) {
						$sel = "selected";
					}

					echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
				}

				echo "</select>";
				echo "</td></tr>";
			}
			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $srow["tilaustyyppi"] == "A") {
				//Täysin dynaaminen osio
				$al_res = t_avainsana("TYOM_TYOKENTAT","", "and avainsana.selitetark != ''");

				$al_lask = 0;

				while ($al_row = mysql_fetch_assoc($al_res)) {

					if ($al_lask >= 2) {
						echo "</tr>";
						$al_lask = 0;
					}

					if ($al_lask == 0) {
						echo "<tr>";
					}

					$kentta = $al_row["selite"];

					if (strtoupper($al_row["selitetark_2"]) == "TEXT") {

						if ($al_lask == 1) {
							echo "<td></td><td></td></tr><tr>";
						}

						echo "<td valign='top'>$al_row[selitetark]:</td><td colspan='3'><textarea name='$kentta' rows='6' cols='50'>$srow[$kentta]</textarea></td>";

						$al_lask++;
					}
					elseif (strtoupper($al_row["selitetark_2"]) == "DATE") {

						list($al_vv, $al_kk, $al_pp) = explode('-', $srow["$kentta"]);

						if ($al_kk == "00") 	$al_kk = date("m");
						if ($al_vv == "0000") 	$al_vv = date("Y");
						if ($al_pp == "00") 	$al_pp = date("d");

						echo "<td>$al_row[selitetark]:</td><td>
								<input type='text' name='".$kentta."pp' size='3' value='$al_pp'>
								<input type='text' name='".$kentta."kk' size='3' value='$al_kk'>
								<input type='text' name='".$kentta."vv' size='5' value='$al_vv'></td>";
					}
					elseif (strtoupper($al_row["selitetark_2"]) == "DROPDOWN") {
						$dropdown_arvot = explode("#", $al_row['selitetark_3']);

						echo "<td>{$al_row["selitetark"]}</td>";
						echo "<td><select name='$kentta'>";

						foreach ($dropdown_arvot as $arvo) {
							$sele = ($srow[$kentta] == $arvo) ? "SELECTED" : "";
							echo "<option value='$arvo' $sele>$arvo</option>";
						}

						echo "</select>";
						echo "</td>";
					}
					elseif ($kentta == "suorittaja") {

						$query = "	SELECT DISTINCT if (avainsana.selitetark is null,'b','a') sort, kuka.kuka, kuka.nimi
									FROM kuka
									JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
									LEFT JOIN avainsana ON (avainsana.yhtio=kuka.yhtio and avainsana.laji='TYOM_TYOLINJA' and avainsana.selitetark=kuka.kuka)
									WHERE kuka.yhtio  = '$kukarow[yhtio]'
									and kuka.extranet = ''
									ORDER BY sort, kuka.nimi";
						$yresult = pupe_query($query);

						echo "<td>$al_row[selitetark]:</td><td><select name='$kentta'>";
						echo "<option value=''>".t("Valitse")."</option>";

						$edg = "";

						while($row = mysql_fetch_assoc($yresult)) {
							$sel = "";

							if ($row['kuka'] == $srow[$kentta]) {
								$sel = 'selected';
							}

							if ($edg != $row["sort"] and $row["sort"] == "a") {
								echo "<optgroup label='".t("Asentajat")."'>";
							}
							elseif ($edg != $row["sort"] and $row["sort"] == "b") {
								echo "</optgroup>";
								echo "<optgroup label='".t("Muut")."'>";
							}

							echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";

							$edg = $row['sort'];
						}

						echo "</select></td>";
					}
					elseif ($kentta == "merkki") {

						echo "<td>$al_row[selitetark]:</td><td>";

						$vresult = t_avainsana("sarjanumeron_li","", "and avainsana.selite='MERKKI'");

						if (mysql_num_rows($vresult) > 0) {

							echo "<select name='merkki2'>";
							echo "<option value = 'eimenu'>".t("Valitse merkki")."</option>";

							$zxk = "";

							while ($vrow=mysql_fetch_assoc($vresult)) {
								$sel="";
								if ($srow["merkki"] == $vrow['selitetark']) {
									$sel = "selected";
									$zxk = "OK";
								}
								echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
							}

							if ($zxk == "OK") {
								$srow["merkki"] = "";
							}

							echo "</select> ".t("tai")." ";
						}

						echo "<input type='text' name='$kentta' size='15' value='$srow[$kentta]'></td>";
					}
					else {
						echo "<td>$al_row[selitetark]:</td><td><input type='text' name='$kentta' size='15' value='$srow[$kentta]'></td>";
					}

					$al_lask++;
				}

				if ($al_lask == 1) {
					echo "<td></td><td></td></tr>";
				}
			}

			if ($toim == "REKLAMAATIO" or (($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") and $yhtiorow["tyomaaraystiedot_tarjouksella"] == "")) {
				echo "	<tr>
						<td>".t("Työn kuvaus").":</td><td colspan='3'><textarea name='komm1' rows='6' cols='50'>$srow[komm1]</textarea></td>
						</tr>";
			}

			if ($toim == 'REKLAMAATIO') {
				echo "	<tr>
						<td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='komm2' rows='6' cols='50'>$srow[komm2]</textarea></td>
						</tr>";
			}

			if ($toim == "YLLAPITO") {
				echo "	<tr>
						<td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='sopimus_lisatietoja' rows='6' cols='50'>$srow[sopimus_lisatietoja]</textarea></td>
						</tr>";
			}

			echo "</table><br>";

			// tehdään ylläpitosopimista varten laskutusaikataulu taulu
			if ($toim == "YLLAPITO") {

				$query = "SELECT * from laskun_lisatiedot where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
				$sopapures = pupe_query($query);
				$sopapurow = mysql_fetch_assoc($sopapures);

				$disable = "";

				// jos alatila on X niin pitää tehdä hidden muuttujat ettei nollaannu
				if ($srow["alatila"] == "X") {
					$disable = "disabled";

					foreach (explode(',', $sopapurow["sopimus_kk"]) as $numi) {
						echo "<input type='hidden' name='yllapito_kk[]' value='$numi'>";
					}
					foreach (explode(',', $sopapurow["sopimus_pp"]) as $numi) {
						echo "<input type='hidden' name='yllapito_pp[]' value='$numi'>";
					}

				}

				$chk = array();
				foreach (explode(',', $sopapurow["sopimus_kk"]) as $numi) {
					$chk[$numi] = "checked";
				}

				if ($sopapurow["sopimus_kk"] == "") {
					for ($numi = 1; $numi < 13; $numi++) {
						$chk[$numi] = "checked";
					}
				}

				echo "<table>";
				echo "<tr><th colspan='2'>".t("Valitse ylläpitosopimuksen laskutusaikataulu").":</th></tr>";
				echo "<tr>";
				echo "<td style='padding:0px;'>";
				echo "<table><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[1] value='1'> ".t("Tammi")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[2] value='2'> ".t("Helmi")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[3] value='3'> ".t("Maalis")." </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[4] value='4'> ".t("Huhti")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[5] value='5'> ".t("Touko")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[6] value='6'> ".t("Kesä")." </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[7] value='7'> ".t("Heinä")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[8] value='8'> ".t("Elo")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[9] value='9'> ".t("Syys")." </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[10] value='10'> ".t("Loka")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[11] value='11'> ".t("Marras")." </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[12] value='12'> ".t("Joulu")." </td>";
				echo "</tr></table>";
				echo "</td>";
				echo "<td style='padding:0px;'>";

				$chk = array();
				foreach (explode(',', $sopapurow["sopimus_pp"]) as $numi) {
					$chk[$numi] = "checked";
				}

				echo "<table><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[1] value='1'> 1 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[2] value='2'> 2 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[3] value='3'> 3 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[4] value='4'> 4 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[5] value='5'> 5 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[6] value='6'> 6 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[7] value='7'> 7 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[8] value='8'> 8 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[9] value='9'> 9 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[10] value='10'> 10 </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[11] value='11'> 11 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[12] value='12'> 12 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[13] value='13'> 13 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[14] value='14'> 14 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[15] value='15'> 15 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[16] value='16'> 16 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[17] value='17'> 17 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[18] value='18'> 18 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[19] value='19'> 19 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[20] value='20'> 20 </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[21] value='21'> 21 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[22] value='22'> 22 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[23] value='23'> 23 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[24] value='24'> 24 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[25] value='25'> 25 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[26] value='26'> 26 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[27] value='27'> 27 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[28] value='28'> 28 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[29] value='29'> 29 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[30] value='30'> 30 </td>";
				echo "</tr><tr>";
				echo "<td colspan='10'><input name='yllapito_pp[]' $disable type='checkbox' $chk[31] value='31'> 31 / ".t("Kuukauden viimeinen päivä")." </td>";

				echo "</tr></table>";
				echo "</td>";
				echo "</tr>";
				echo "<tr><th colspan='4'>".t("Sopimuksen voimassaoloaika").":</th></tr>";
				echo "<tr>";

				if ($sopapurow["sopimus_alkupvm"] == "0000-00-00") $sopapurow["sopimus_alkupvm"] = "";
				if ($sopapurow["sopimus_loppupvm"] == "0000-00-00") $sopapurow["sopimus_loppupvm"] = "";

				if (!isset($yllapito_alkuvv) or !isset($yllapito_alkukk) or !isset($yllapito_alkupp))
					list($yllapito_alkuvv, $yllapito_alkukk, $yllapito_alkupp) = explode('-', $sopapurow["sopimus_alkupvm"]);

				if (!isset($yllapito_loppuvv) or !isset($yllapito_loppukk) or !isset($yllapito_loppupp))
					list($yllapito_loppuvv, $yllapito_loppukk, $yllapito_loppupp) = explode('-', $sopapurow["sopimus_loppupvm"]);

				echo "<td>Alkupäivä:</td>";
				echo "<td>";
				echo "<input type='text' name='yllapito_alkupp' value='$yllapito_alkupp' size='3'>";
				echo "<input type='text' name='yllapito_alkukk' value='$yllapito_alkukk' size='3'>";
				echo "<input type='text' name='yllapito_alkuvv' value='$yllapito_alkuvv' size='6'>";
				echo "</td>";
				echo "</tr><tr>";
				echo "<td>Loppupäivä:</td>";
				echo "<td>";
				echo "<input type='text' name='yllapito_loppupp' value='$yllapito_loppupp' size='3'>";
				echo "<input type='text' name='yllapito_loppukk' value='$yllapito_loppukk' size='3'>";
				echo "<input type='text' name='yllapito_loppuvv' value='$yllapito_loppuvv' size='6'>";
				echo "</td>";
				echo "</tr>";
				echo "</table><br>";

			}

			echo "<table>";

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
				echo "<tr><th colspan='3'>".t("Työmääräyksen kommentit").":</th></tr>";
			}
			elseif ($toim == "REKLAMAATIO") {
				echo "<tr><th colspan='3'>".t("Valmistuksen kommentit").":</th></tr>";
			}
			elseif ($toim == "EXTRANET_REKLAMAATIO") {
				echo "<tr><th colspan='3'>".t("Reklamaation kommentit").":</th></tr>";
			}
			elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><th colspan='3'>".t("Valmistuksen kommentit").":</th></tr>";
			}
			elseif ($toim == "SIIRTOLISTA") {
				echo "<tr><th colspan='3'>".t("Siirtolistan kommentit").":</th></tr>";
			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><th colspan='3'>".t("Myyntitilin kommentit").":</th></tr>";
			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><th colspan='3'>".t("Tarjouksen kommentit").":</th></tr>";
			}
			elseif ($toim == "PROJEKTI") {
				echo "<tr><th colspan='3'>".t("Projektin kommentit").":</th></tr>";
			}
			else {
				echo "<tr><th colspan='3'>".t("Tilauksen kommentit").":</th></tr>";
			}

			echo "<tr><td>".t("Tilausyhteyshenkilö").":</td>";

			$yhteysquery = "SELECT *
							FROM yhteyshenkilo
							where yhtio = '$kukarow[yhtio]'
							and liitostunnus = '$srow[liitostunnus]'
							and tyyppi = 'A'
							and tilausyhteyshenkilo != ''
							ORDER BY nimi";
			$yhteysresult = pupe_query($yhteysquery);

			echo "<td><input type='text' size='30' name='tilausyhteyshenkilo' value='$srow[tilausyhteyshenkilo]'><br>";

			echo "<select id='yhteyshenkilo_tilaus' name='yhteyshenkilo_tilaus'>";
			echo "<option value = ''>".t("Ei valintaa")."</option>";

			while ($yhteysrow = mysql_fetch_assoc($yhteysresult)) {

				$sela = "";

				if ($yhteysrow["nimi"] == $srow["tilausyhteyshenkilo"] or ($kukarow["kesken"] == 0 and $yhteysrow["oletusyhteyshenkilo"] != "")) {
					$sela = "SELECTED";
					$srow["tilausyhteyshenkilo"] = "";
				}

				echo "<option value = '$yhteysrow[nimi]' $sela>$yhteysrow[nimi]</option>";
			}

			echo "</select> ";

			if (tarkista_oikeus("yllapito.php", "yhteyshenkilo", "X")) {
				echo " <a href='#' onclick=\"js_open_yllapito('yhteyshenkilo_tilaus','toim=yhteyshenkilo&laji=A&lukitse_avaimeen=$srow[liitostunnus]&tyhthenkilo=K');\"><img src='{$palvelin2}pics/lullacons/add.png'>".t("Uusi yhteyshenkilö")."</a>";
			}

			echo "</td>";
			echo "<td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("lasku")."</td></tr>";

			echo "<tr><td>".t("Asiakkaan tilausnumero").":</td><td colspan = '1'><input type='text' size='43' name='asiakkaan_tilausnumero' value='$srow[asiakkaan_tilausnumero]'><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("lasku")."</td></tr>";

			echo "<tr><td>".t("Kohde").":</td><td colspan = '1'><input type='text' size='43' name='kohde' value='$srow[kohde]'></td><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("lasku")."</td></tr>";

			echo "<tr><td>".t("Tilausviite").":</td><td><input type='text' size='43' name='viesti' value='$srow[viesti]'></td><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("lasku")." + ".t("tarjous")."</td></tr>";

			echo "<tr><td>".t("Ohjausmerkki").":</td><td><input type='text' size='43' name='ohjausmerkki' value='$srow[ohjausmerkki]'></td><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("lähete")." + ".t("lasku")."</td></tr>";

			if (isset($srow["myynti_kommentti1"]) and $srow["myynti_kommentti1"] != "") {
				echo "<tr><td>".t("Kommentti")." 1:</td><td><textarea name='comments'   rows='2' cols='40'>$srow[myynti_kommentti1] $srow[comments]</textarea></td><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("tarjous")."</td></tr>";
			}
			else {
				echo "<tr><td>".t("Kommentti")." 1:</td><td><textarea name='comments'   rows='2' cols='40'>$srow[comments]</textarea></td><td>".t("tilausvahvistus")." + ".t("keräyslista")." + ".t("valmistus")." + ".t("työmääräys")." + ".t("lähete")." + ".t("tarjous")."</td></tr>";
			}

			echo "<tr><td>".t("Kommentti")." 2:</td><td><textarea name='sisviesti2' rows='2' cols='40'>$srow[sisviesti2]</textarea></td><td>".t("keräyslista")." + ".t("valmistus")."</td></tr>";
			echo "<tr><td>".t("Kommentti")." 3:</td><td><textarea name='sisviesti1' rows='2' cols='40'>$srow[sisviesti1]</textarea></td><td>".t("lasku")."</td></tr>";
			echo "<tr><td>".t("Sisäinen viesti").":</td><td><textarea name='sisviesti3' rows='2' cols='40'>$srow[sisviesti3]</textarea></td><td></td></tr>";
			echo "</table><br>";

			echo "<table>";
			echo "<tr><td class='back'><input type='submit' ACCESSKEY='J' name='jatka' value='".t("Jatka")."'></td>";
			echo "<td class='back'><input type='submit' name='vaihdatoim_asiakas' value='".t("Hae toimitusosoite")."'></td>";
			echo "</form>";

			if ($tilausnumero != "") {
				echo "<form method='post' autocomplete='off'>";
				echo "<input type='hidden' name='tee' value='OTSIK'>";
				echo "<input type='hidden' name='lopetus' value='$lopetus'>";
				echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='projektilla' value='$projektilla'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='mista' value='$mista'>";
				echo "<input type='hidden' name='tila' value='vaihdaasiakas'>";
				echo "<td class='back'><input type='submit' ACCESSKEY='A' value='".t("Vaihda asiakas")."'></td></form>";
			}

			echo "</tr></table>";
		}

		$formi  = "paaformi";
		$kentta = "nimi";
	}

	if (isset($jatka)) {

		//otetaan laskun Ytunnus talteen
		$apuqu = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
		$meapu = pupe_query($apuqu);
		$apuro = mysql_fetch_assoc($meapu);

		$apuval = $apuro["valkoodi"];
		$apukurssi = $apuro["vienti_kurssi"];

		//otetaan käyttöliittymästä tullut alv talteen
		$laskunalv = $alv;

		// HUOM: ALVIMUUTOKSESTA JOHTUEN:
		$alvmuuttui = abs((float) $apuro['alv'] - (float) $alv);

		// Tutkitaan onko ytunnus-, alvi- tai vienti-kenttä vaihtunut (Poislukien tarjoukset)
		if ($apuro["tila"] != "T" and (int) $kukarow["kesken"] != 0 and ($from == 'vaihdaasiakas' or $apuro['liitostunnus'] != $asiakasid or $alvmuuttui > 1 or $apuro['vienti'] != $vienti)) {

			// Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
			// Netotettuja rivejä ei päivitetä
			$query = "	SELECT *
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$kukarow[kesken]'
						and tuoteno != '$yhtiorow[laskutuslisa_tuotenumero]'";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {

				$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
				$tres  = pupe_query($query);
				$trow  = mysql_fetch_assoc($tres);

				$laskurow['ytunnus']      = $ytunnus;
				$laskurow['vienti']       = $vienti;
				$laskurow['alv']          = $laskunalv;
				$laskurow['liitostunnus'] = $asiakasid;
				$laskurow['maa']          = $maa;

				// Hinnat lasketaan uudestaan vain jos asiakas vaihdetaan
				// Ei esim jos vain alvi tai vientikenttää muutetaan
				if (($from == 'vaihdaasiakas' or $apuro["liitostunnus"] != $asiakasid) and $asiakasid > 0) {

					$paivitetaanko = TRUE;

					// hinnat päivitetään vain jos asiakkaalla on asiakashinta tai asiakasalennus
					if ($yhtiorow["vaihda_asiakas_hintapaiv"] == "A") {

						$haettavat_kentat = "hinta,hintaperuste,aleperuste";

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$haettavat_kentat .= ",ale{$alepostfix}";
						}

						$hinnat = alehinta($laskurow, $trow, $row['tilkpl'], '', '', '', $haettavat_kentat);

						$onko_asiakkaalla_alennuksia = FALSE;

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
								$onko_asiakkaalla_alennuksia = TRUE;
								break;
							}
						}

						// Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
						if (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE) {
							$paivitetaanko = FALSE;
						}
					}

					$ale = array();

					if ($row['netto'] == "" and $paivitetaanko) {
						$hinta 	= '';

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale{$alepostfix}"] = '';
						}
					}
					else {
						//Alvihinta korjataan vain jos yhtiöllä on verolliset myyntihinnat
						if ($row["alv"] != '' and $row["alv"] < 500 and $yhtiorow["alv_kasittely"] == "" ) {
							$hinta = hintapyoristys($row['hinta'] / (1+$row['alv']/100) * (1+$trow["alv"]/100));
						}
						else {
							$hinta = $row['hinta'];
						}

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale{$alepostfix}"] = $row["ale{$alepostfix}"];
						}
					}

					// haetaan tuotteelle asiakaskohtaisia hintoja..
					list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $row['tilkpl'], $row['netto'], $hinta, $ale);

					$uusihinta	= '';

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'uusiale'.$alepostfix} = '';
					}

					if ($hinta != $row['hinta']) {
						$uusihinta = $hinta;
					}
					else {
						$uusihinta = $row["hinta"];
					}

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						if ($ale["ale{$alepostfix}"] != $row["ale{$alepostfix}"]) {
							${'uusiale'.$alepostfix} = $ale["ale{$alepostfix}"];
						}
						else {
							${'uusiale'.$alepostfix} = $row["ale{$alepostfix}"];
						}
					}
				}
				else {
					//Asiakas ei vaihtunut hinnat pysyvät samoina, alvia joudumme ehkä korjaamaan
					if ($row["alv"] != '' and $row["alv"] < 500 and $yhtiorow["alv_kasittely"] == "") {
						$uusihinta = hintapyoristys($row['hinta'] / (1+$row['alv']/100) * (1+$trow["alv"]/100));
					}
					else {
						$uusihinta = $row['hinta'];
					}

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'uusiale'.$alepostfix} = $row["ale{$alepostfix}"];
					}
				}

				//lasketaan alvit
				if ($row["alv"] >= 600) {
					$alv = 600;
				}
				elseif ($row["alv"] >= 500) {
					$alv = 500;
				}
				else {
					$alv = '';
				}

				list($uusihinta, $alv) = alv($laskurow, $trow, $uusihinta, $alv, $alehinta_alv);

				$ale_query_insert_lisa = '';

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale_query_insert_lisa .= " ale{$alepostfix} = '".${'uusiale'.$alepostfix}."', ";
				}

				$query = "UPDATE tilausrivi set hinta='$uusihinta', {$ale_query_insert_lisa} netto='$netto', alv='$alv' where yhtio='$kukarow[yhtio]' and tunnus='$row[tunnus]'";
				$tres  = pupe_query($query);

				$netto		= '';
				$uusihinta	= '';
				$hinta		= '';
				$alv		= '';
				$kpl		= '';

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'uusiale'.$alepostfix} = '';
					${'ale'.$alepostfix} = '';
				}
			}
		}

		//otetaan käyttöliittymästä tullut alv talteen
		$alv = $laskunalv;

		//Erikoisalennuksen desimaalipilkku
		$erikoisale = str_replace (",", ".", $erikoisale);

		// ollaan annettu toimitusviikko
		$nyviikko = date("W");
		$nypaiva  = date("N");

		if ($toimvko != '') {

			$nyvuosi  = date("Y");

			$toimvkopva = $toimvkopva == '' ? 7 : $toimvkopva;

			if ($toimvko < $nyviikko) {
				$nyvuosi += 1;
			}

			$toimvko = strlen($toimvko) == 1 ? "0".$toimvko : $toimvko;

			$toimvv  = date("Y",strtotime("$nyvuosi-W$toimvko-$toimvkopva"));
			$toimkk	 = date("m",strtotime("$nyvuosi-W$toimvko-$toimvkopva"));
			$toimpp  = date("d",strtotime("$nyvuosi-W$toimvko-$toimvkopva"));

			$toimvko = $toimvkopva;
		}
		else {
			$toimvko	= "";
			$toimvkopva	= "";
		}

		if ($keraysvko != '') {

			$nyvuosi  = date("Y");

			$keraysvkopva = $keraysvkopva == '' ? 7 : $keraysvkopva;

			if ($keraysvko < $nyviikko) {
				$nyvuosi += 1;
			}

			$keraysvko = strlen($keraysvko) == 1 ? "0".$keraysvko : $keraysvko;

			$kervv  = date("Y",strtotime("$nyvuosi-W$keraysvko-$keraysvkopva"));
			$kerkk	= date("m",strtotime("$nyvuosi-W$keraysvko-$keraysvkopva"));
			$kerpp  = date("d",strtotime("$nyvuosi-W$keraysvko-$keraysvkopva"));

			$keraysvko = $keraysvkopva;
		}
		else {
			$keraysvko		= "";
			$keraysvkopva	= "";
		}

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";
			$postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			if (isset($ohjelmamoduli) and $ohjelmamoduli != "") {
				$postquery .= ",ohjelma_moduli = '$ohjelmamoduli'";
			}
			elseif (substr($toim, 0, 8) == "EXTRANET") {
				$postquery .= ",ohjelma_moduli = 'EXTRANET'";
			}
			else {
				$postquery .= ",ohjelma_moduli = 'PUPESOFT'";
			}

			$query2 = "INSERT into ";
			$postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			$query3 = "INSERT into ";
			$postquery3 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			//Kun tehdään uusi tilaus niin nollataan aina tilat ja alatilat
			$alatila = "";
			$ylatila = "";
		}
		else {
			// Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
			$query = "	UPDATE tilausrivi
						SET kerayspvm = '{$kervv}-{$kerkk}-{$kerpp}'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$kukarow[kesken]'
						and kerayspvm = '$vkerayspvm'";
			$result = pupe_query($query);

			$query = "	UPDATE tilausrivi
						SET toimaika = '{$toimvv}-{$toimkk}-{$toimpp}'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$kukarow[kesken]'
						and toimaika = '$vtoimaika'";
			$result = pupe_query($query);

			$query = "UPDATE ";
			$postquery = " WHERE tunnus = '$kukarow[kesken]'";


			//Tsekataan vielä, että olio varmasi löytyy, muuten lisätään se
			$apuqu = "SELECT yhtio from tyomaarays where yhtio='$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
			$mexapu = pupe_query($apuqu);

			if (mysql_num_rows($mexapu) == 0) {
				$query2 = "INSERT into ";
				$postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
			}
			else {
				$query2 = "UPDATE ";
				$postquery2 = " WHERE otunnus = '$kukarow[kesken]'";
			}

			//Tsekataan vielä, että olio varmasi löytyy, muuten lisätään se
			$apuqu = "SELECT yhtio from laskun_lisatiedot where yhtio='$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
			$mexapu = pupe_query($apuqu);

			if (mysql_num_rows($mexapu) == 0) {
				$query3 = "INSERT into ";
				$postquery3 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
			}
			else {
				$query3 = "UPDATE ";
				$postquery3 = " WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
			}
		}

		if (strlen(trim($tnimi)) == 0) {
			$tnimi     = $nimi;
			$tnimitark = $nimitark;
			$tosoite   = $osoite;
			$tpostino  = $postino;
			$tpostitp  = $postitp;
			$toim_maa  = $maa;
		}

		if (strlen(trim($laskutus_nimi)) == 0) {
			$laskutus_nimi 		= $nimi;
			$laskutus_nimitark 	= $nimitark;
			$laskutus_osoite 	= $osoite;
			$laskutus_postino 	= $postino;
			$laskutus_postitp 	= $postitp;
			$laskutus_maa 		= $maa;
		}

		if ($tilausyhteyshenkilo == '' and $yhteyshenkilo_tilaus != '') {
			$tilausyhteyshenkilo = $yhteyshenkilo_tilaus;
		}

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
		$meapu = pupe_query($apuqu);
		$meapurow = mysql_fetch_assoc($meapu);

		$kassalipas = "";

		// jos kyseessä oli käteinen
		if ($meapurow["kateinen"] != "") {
			// haetaan toimitustavan tiedot tarkastuksia varten
			$apuqu2 = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '$toimitustapa'";
			$meapu2 = pupe_query($apuqu2);
			$meapu2row = mysql_fetch_assoc($meapu2);

			// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
			if ($meapu2row["nouto"] == "") {
				$apuqu = "	SELECT *
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							AND nouto != ''
							ORDER BY jarjestys
							LIMIT 1";
				$meapu = pupe_query($apuqu);
				$apuro = mysql_fetch_assoc($meapu);

				$toimitustapa = $apuro['selite'];
			}
			$kassalipas = $kukarow["kassamyyja"];
		}

		// jos kyseessä oli käsin syötetty eräpäivä ja maksuehto on väärin laitetaan oikea maksuehto... hakee järjestyksessä ekan
		if ($erpcm_kasin == "KYLLA" and $meapurow["erapvmkasin"] == "" and $meapurow["jaksotettu"] == "") {

			$erpquery = "SELECT * from maksuehto where yhtio = '$kukarow[yhtio]' and erapvmkasin != '' and kaytossa = '' order by tunnus limit 1";
			$erpres = pupe_query($erpquery);
			$erprow = mysql_fetch_assoc($erpres);

			$maksuehto = $erprow["tunnus"]; // valitaan tää maksuehdoks
		}

		//	Tarkistetaan, että meillä on jaksotettu maksuehto jos lasku on jaksotettu, päivitetään joku jos näin ei ole
		if ($jaksotettu > 0 and $meapurow["jaksotettu"] == "") {

			$jaksquery = "SELECT * from maksuehto where yhtio = '$kukarow[yhtio]' and jaksotettu != '' and kaytossa = '' order by tunnus limit 1";
			$jaksres = pupe_query($jaksquery);
			$jaksrow = mysql_fetch_assoc($jaksres);

			// Tutkitaan laskun maksupositio siirretty jo laskutukseen
			$jaksotettu_check_query = "	SELECT maksuehto
										FROM maksupositio
										WHERE yhtio = '$kukarow[yhtio]'
										AND uusiotunnus = '$kukarow[kesken]'";
			$maposres = pupe_query($jaksotettu_check_query);

			// Jos on, täällä pitää olla jo oikea maksuehto, ei 'jaksotettu' -maksuehto
			if (mysql_num_rows($maposres) > 0) {
				$maposrow = mysql_fetch_array($maposres);
				$maksuehto = $maposrow["maksuehto"];
			}
			else {
				$maksuehto = $jaksrow["tunnus"]; // valitaan tää maksuehdoks
			}
		}

		// jos ollaan valittu rahdinmaksajaksi oletus, haetaan oletusmaksaja toimitusehdolta tai toimitustavalta
		if ($maksaja == "O") {
			$apuqu = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '$toimitustapa'";
			$meapu = pupe_query($apuqu);
			$apuro = mysql_fetch_assoc($meapu);

			$maksaja = $apuro['merahti'];

			// Onko toimitusehdon takana määritelty rahdinmaksaja? Tämä yliajaa toimitustavan takana olevan.
			$toimehto_tresult = t_avainsana("TOIMEHTO", "", " and trim(concat_ws(' ', selite, selitetark)) = '$toimitusehto' ");

			if (mysql_num_rows($toimehto_tresult) > 0) {
				$toimehto_row = mysql_fetch_assoc($toimehto_tresult);

				if ($toimehto_row["selitetark_3"] == "LAHETTAJAN_SOPIMUS") {
					$maksaja = "K";
				}
				elseif ($toimehto_row["selitetark_3"] == "VASTAANOTTAJAN_SOPIMUS") {
					$maksaja = "";
				}
			}
		}

		// Marginaalimyyntispecial
		if ($alv == 99.99) {
			// Tässä ceississä myydään vain kotimaanmyyntiä
			$vienti = "";
		}

		// liitostunnusfixaus
		if ((int) $liitostunnus == 0) $liitostunnus = $asiakasid;

		//jos oollaan haluttu laskutuskielto laitetaan chn kenttään 999
		if ($laskutuskielto != '') {
			//$srow['chn'] = '999';
			$chn = '999';
		}
		elseif ($liitostunnus != "") {
			$apuqu = "SELECT chn from asiakas where yhtio='$kukarow[yhtio]' and tunnus = '$liitostunnus'";
			$meapu = pupe_query($apuqu);
			$apuro = mysql_fetch_assoc($meapu);
			$chn = $apuro['chn'];
			if ($chn == '') {
				$chn = '100';
			}
		}

		if ($kasitoimehto != '') {
			$toimitusehto = trim($toimitusehto." ".$kasitoimehto);
		}

		if ($kasitoimehto2 != '') {
			$toimitusehto2 = trim($toimitusehto2." ".$kasitoimehto2);
		}

		// yhtiön tiedot
		$yhtionimi 			= $yhtiorow["nimi"];
		$yhtioosoite 		= $yhtiorow["osoite"];
		$yhtiopostino		= $yhtiorow["postino"];
		$yhtiopostitp		= $yhtiorow["postitp"];
		$yhtiomaa 			= $yhtiorow["maa"];
		$yhtioovttunnus 	= $yhtiorow["ovttunnus"];
		$yhtiokotipaikka	= $yhtiorow["kotipaikka"];
		$yhtiotoimipaikka	= $kukarow["toimipaikka"];
		$yhtioalv_tilino	= $yhtiorow["alv"];

		// poikkeava laskutusosoite
		if ($alv_velvollisuus != "") {

			// haetaan sen yhteystiedot
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero = '$alv_velvollisuus'";
			$alhire = pupe_query($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				$apualvrow  = mysql_fetch_assoc($alhire);
				$yhtionimi 		    = $apualvrow["nimi"];
				$yhtioosoite 	    = $apualvrow["osoite"];
				$yhtiopostino	    = $apualvrow["postino"];
				$yhtiopostitp	    = $apualvrow["postitp"];
				$yhtiomaa 		    = $apualvrow["maa"];
				$yhtioovttunnus  	= $apualvrow["vat_numero"];
				$yhtiokotipaikka 	= $apualvrow["kotipaikka"];
				$yhtiotoimipaikka	= $apualvrow["tunnus"]; // ylikirjoitetaan myös käyttäjän oletustoimipaikka
				$yhtioalv_tilino	= $apualvrow["toim_alv"];
			}
		}

		// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
		if ($maksaja == "" and $rahtivapaa != "") {
			$maksaja = "K";
		}

		//Otetaan valuutan kurssi
		list($valkoodi, $kurssi) = explode('##', $valkoodi);

		if ($apuval == $valkoodi) {
			$kurssi = $apukurssi;
		}

		if($toim != 'TARJOUS') {
			//Tarjous keisissä halutaan ohittaa tämä checki koska toimaika = 0000-00-00 merkkaa, että tarjousta ei haluta pdf:llä näkyviin
			//Päivämäärävertailu (toivottukeräyspvm ei saa olla suurempi kuin toivottutoimituspvm)
			if (($kerpp > $toimpp AND $kerkk >= $toimkk AND $kervv >= $toimvv) OR ($kerpp < $toimpp AND $kerkk > $toimkk AND $kervv >= $toimvv) OR ($kervv > $toimvv)) {
				$toimpp = $kerpp;
				$toimkk = $kerkk;
				$toimvv = $kervv;
				$toimvko = $keraysvko;
			}
		}

		if (($voimaika_vv == "" or !is_numeric($voimaika_vv)) or ($voimaika_kk == "" or !is_numeric($voimaika_kk)) or ($voimaika_pp == "" or !is_numeric($voimaika_pp))) {
			$voimaika_vv = "0000";
			$voimaika_kk = "00";
			$voimaika_pp = "00";
		}

		$ohjausmerkki = trim($ohjausmerkki);

		$prioriteettinro = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$luokka}'", "", "", "selitetark_3");

		// Default 9 myös kannassa, niin laitetaan tännekki 9 jos avainsanoista ei löydy prioa
		if (!is_numeric($prioriteettinro)) $prioriteettinro = 9;

		$label = !isset($label) ? "" : $label;
		$kolmikantakauppa = !isset($kolmikantakauppa) ? "" : $kolmikantakauppa;

		if($naytetaanko_tarjouksella != 'on' and $toim == "TARJOUS") {
			$toimvv = '0000';
			$toimkk = '00';
			$toimpp = '00';
		}

		$query .= "	lasku SET
					aktiivinen_kuljetus					= '',
					alatila 							= '$alatila',
					alv 								= '$alv',
					alv_tili							= '$yhtioalv_tilino',
					asiakkaan_tilausnumero				= '$asiakkaan_tilausnumero',
					chn									= '$chn',
					clearing							= '$clearing',
					comments 							= '$comments',
					eilahetetta 						= '$eilahe',
					lahetetyyppi 						= '$lahetetyyppi',
					erikoisale 							= '$erikoisale',
					erpcm								= '$erpcmvv-$erpcmkk-$erpcmpp',
					huolitsija 							= '$huolitsija',
					hyvaksynnanmuutos 					= '$luokka',
					prioriteettinro						= '$prioriteettinro',
					jakelu 								= '$jakelu',
					jaksotettu							= '$jaksotettu',
					jtkielto							= '$jtkielto',
					kassalipas							= '$kassalipas',
					kauppatapahtuman_luonne				= '$kauppatapahtuman_luonne',
					kerayspvm 							= '$kervv-$kerkk-$kerpp',
					keraysvko 							= '$keraysvko',
					ketjutus 							= '$ketjutus',
					kohde								= '$kohde',
					kohdistettu 						= '$maksaja',
					kolmikantakauppa					= '{$kolmikantakauppa}',
					kuljetus 							= '$kuljetus',
					kuljetusmuoto						= '',
					label								= '{$label}',
					laskutusvkopv 						= '$laskutusvkopv',
					liitostunnus						= '$liitostunnus',
					maa 								= '$maa',
					maksuehto 							= '$maksuehto',
					maksuteksti 						= '$maksuteksti',
					myyja 								= '$myyja',
					allekirjoittaja						= '$allekirjoittaja',
					nimi 								= '$nimi',
					nimitark 							= '$nimitark',
					ohjausmerkki						= '$ohjausmerkki',
					olmapvm								= '$voimaika_vv-$voimaika_kk-$voimaika_pp',
					osatoimitus							= '$osatoimitus',
					osoite 								= '$osoite',
					ovttunnus 							= '$ovttunnus',
					piiri 								= '$piiri',
					postino 							= '$postino',
					postitp 							= '$postitp',
					rahtivapaa							= '$rahtivapaa',
					sisainen 							= '$sisainen',
					sisviesti1 							= '$sisviesti1',
					sisviesti2							= '$sisviesti2',
					sisviesti3							= '$sisviesti3',
					splittauskielto						= '$splittauskielto',
					tilaustyyppi						= '$tilaustyyppi',
					tilausvahvistus 					= '$tilausvahvistus',
					tilausyhteyshenkilo					= '$tilausyhteyshenkilo',
					toimaika 							= '$toimvv-$toimkk-$toimpp',
					toimitusehto 						= '$toimitusehto',
					toimitustapa 						= '$toimitustapa',
					toimvko	 							= '$toimvko',
					toim_maa 							= '$toim_maa',
					toim_nimi 							= '$tnimi',
					toim_nimitark 						= '$tnimitark',
					toim_osoite 						= '$tosoite',
					toim_ovttunnus 						= '$toim_ovttunnus',
					toim_postino 						= '$tpostino',
					toim_postitp 						= '$tpostitp',
					tunnusnippu							= '$tunnusnippu',
					valkoodi 							= '$valkoodi',
					varasto 							= '$varasto',
					verkkotunnus						= '$verkkotunnus',
					vienti 								= '$vienti',
					vienti_kurssi						= '$kurssi',
					viesti 								= '$viesti',
					viikorkopros 						= '$yhtiorow[viivastyskorko]',
					yhtio 								= '$kukarow[yhtio]',
					yhtio_kotipaikka					= '$yhtiokotipaikka',
					yhtio_maa							= '$yhtiomaa',
					yhtio_nimi							= '$yhtionimi',
					yhtio_osoite						= '$yhtioosoite',
					yhtio_ovttunnus						= '$yhtioovttunnus',
					yhtio_postino						= '$yhtiopostino',
					yhtio_postitp						= '$yhtiopostitp',
					yhtio_toimipaikka					= '$yhtiotoimipaikka',
					ytunnus								= '$ytunnus'";

		if ($toim == "MYYNTITILI") {
			if ($ylatila=='') {
				$ylatila='G';
			}
			$tee = "";
		}
		elseif ($toim == "VALMISTAASIAKKAALLE") {
			if ($ylatila=='') {
				$ylatila='V';
			}
			$tee = "";
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
			if ($ylatila=='') {
				$ylatila='A';
			}
			$tee = "";
		}
		elseif ($toim == "REKLAMAATIO") {
			if ($ylatila=='') {
				$ylatila='C';
			}
			$tee = "";
		}
		elseif ($toim == "EXTRANET_REKLAMAATIO") {
			if ($ylatila=='') {
				$ylatila='C';
			}
			$tee = "";
		}
		elseif ($toim == "TARJOUS") {
			if ($ylatila=='') {
				$ylatila='T';
			}
			$tee = "";
		}
		elseif ($toim == "PROJEKTI") {
			if ($ylatila=='') {
				$ylatila='R';
			}
			$tee = "";
		}
		elseif ($toim == "WEBTILAUSTARJOUS") {
			if ($ylatila=='') {
				$ylatila='F';
			}
			$tee = "";
		}
		else {
			if ($ylatila=='') {
				$ylatila='N';
			}
			$tee = "";
		}

		if (($toim == "PROJEKTI" or $yhtiorow["splittauskielto"] == "K") and $kukarow["kesken"] > 0) {
			$abuq = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
			$abures = pupe_query($abuq);
			$originaali["lasku"] = mysql_fetch_assoc($abures);

			$abuq = "	SELECT *
						FROM laskun_lisatiedot
						WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$tilausnumero'";
			$abures = pupe_query($abuq);
			$originaali["laskun_lisatiedot"] = mysql_fetch_assoc($abures);
		}

		$query .= ", tila = '$ylatila'";
		$query .= $postquery;
		$result = pupe_query($query);
		$id = mysql_insert_id();

		// Jos erikoisalennus muuttuu
		if ($laskurow["erikoisale"] != $erikoisale) {
			// päivitetään erikoisale tilausriveille
			$erikoisale_query_otunnus = (int) $kukarow['kesken'] != 0 ? $kukarow['kesken'] : $id;
			$query_erikoisale = "	UPDATE tilausrivi
									SET erikoisale = '{$erikoisale}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$erikoisale_query_otunnus}'
									AND tuoteno not in ('{$yhtiorow["rahti_tuotenumero"]}','{$yhtiorow["jalkivaatimus_tuotenumero"]}')
									AND netto  != 'N'
									AND tyyppi != 'D'";
			$upd_erikoisale_res = pupe_query($query_erikoisale);
		}

		if ((int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE) {

			$query = "	UPDATE kuka
						SET kesken = '$id'
						WHERE yhtio = '$kukarow[yhtio]' AND
						kuka = '$kukarow[kuka]' AND
						session = '$session' AND
						session != ''";
			$result = pupe_query($query);

			$kukarow["kesken"] = $id;
			$tilausnumero = $id;

			//	jos tehtiin tarjouksesta uusi versio kopioidaan vanhat rivit uudeksi pohjaksi..
			//	Koska meillä on jo tässävaiheessa tunnusnippu, on tämän oltava uusi versio, eikä uusi tarjous
			if ($tunnusnippu > 0 and $toim == "TARJOUS" and $yhtiorow["tarjouksen_voi_versioida"] != "") {

				// Haetaan tämän nipun viimeisin tarjous
				$query = "	SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$tunnusnippu' and tunnus<'$kukarow[kesken]' and tila = 'T' order by tunnus desc LIMIT 1";
				$abures = pupe_query($query);
				$aburow = mysql_fetch_assoc($abures);

				//	Haetaan rivit ja kopioidaan ne uuteen tarjoukseen.. as they are..
				$query = "SELECT * from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and otunnus='$aburow[tunnus]'";
				$monistares = pupe_query($query);

				$korjaaPerheet=array();

				if (mysql_num_rows($monistares)>0) {
					$fields = mysql_field_name($monistares,0);

					for ($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta
						$fields .= ", ".mysql_field_name($monistares,$i);
					}

					while ($monistarow = mysql_fetch_assoc($monistares)) {

						$values = "";

						if ($values == "") {
							$values = "('".$monistarow["yhtio"]."'";
						}
						else {
							$values .= "), ('".$monistarow["yhtio"]."'";
						}

						for ($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta

							$fieldname = mysql_field_name($monistares,$i);

							switch (mysql_field_name($monistares,$i)) {
								case "otunnus";
									$values .= ", '$tilausnumero'";
									break;
								case "laatija";
									$values .= ", '$kukarow[kuka]'";
									break;
								case "luontiaika";
								case "laadittu";
									$values .= ", now()";
									break;
								case "kerayspvm";
									$values .= ", '$kervv-$kerkk-$kerpp'";
									break;
								case "toimaika";
									$values .= ", '$toimvv-$toimkk-$toimpp'";
									break;
								default;
									$values .= ", '".$monistarow[$fieldname]."'";
									break;
							}
						}

						$values .= ")";

						// 	Ja insertoidaan rivit kantaan
						$query = "	INSERT INTO tilausrivi ($fields) VALUES $values";
						$insres=pupe_query($query);
						$lisattyRivitunnus = mysql_insert_id();

						//	Otetaan korjattava perheid talteen
						if ($monistarow["perheid"] > 0 and $monistarow["perheid"] == $monistarow["tunnus"]) {
							$korjaaPerheet[$monistarow["perheid"]] = $lisattyRivitunnus;
						}

						//	Oliko meillä lisatietoja?
						$query = "SELECT * from tilausrivin_lisatiedot WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus='$monistarow[tunnus]'";
						$monistares2 = pupe_query($query);

						if (mysql_num_rows($monistares2)>0) {

							$fields2 = mysql_field_name($monistares2,0);

							for ($i=1; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
								$fields2 .= ", ".mysql_field_name($monistares2,$i);
							}

							$values2 ="";

							while ($monistarow2 = mysql_fetch_assoc($monistares2)) {

								if ($values2 == "") {
									$values2 = "('".$monistarow2["yhtio"]."'";
								}
								else {
									$values2 .= "), ('".$monistarow2["yhtio"]."'";
								}

								for ($i=1; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta

									$fieldname = mysql_field_name($monistares2,$i);

									switch (mysql_field_name($monistares2,$i)) {
										case "laatija";
											$values2 .= ", '$kukarow2[kuka]'";
											break;
										case "luontiaika";
											$values2 .= ", now()";
											break;
										case "tilausrivitunnus";
											$values2 .= ", '$lisattyRivitunnus'";
											break;
										default;
											$values2 .= ", '".$monistarow2[$fieldname]."'";
											break;
									}
								}
							}

							$values2 .= ")";

							// 	Ja insertoidaan rivit kantaan
							$query = "	INSERT INTO tilausrivin_lisatiedot ($fields2) VALUES $values2";
							$insres2=pupe_query($query);

						}

					}
				}

				//	Korjataan tuoteperheet jos tuli jotain korjattavaa
				if (count($korjaaPerheet) > 0) {
					foreach($korjaaPerheet as $wanhaPerhe => $uusiPerhe) {
						if ($wanhaPerhe > 0 and $uusiPerhe > 0) {
							$query = "	UPDATE tilausrivi SET
											perheid = $uusiPerhe
										WHERE  yhtio = '$kukarow[yhtio]' and otunnus='$tilausnumero' and perheid>0 and perheid=$wanhaPerhe";
							$monistares2 = pupe_query($query);
						}
					}
				}
			}
		}

		// varmistetaan, että meillä on kukarow
		if ((int) $kukarow["kesken"] != 0) {

			if ($tunnusnippu == "" and $toim == "PROJEKTI" or ($toim=="TARJOUS" and $yhtiorow["tarjouksen_voi_versioida"] != "") or (in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA")) and $yhtiorow["myyntitilaus_osatoimitus"] == "K")) {
				$query = "	UPDATE lasku
							SET tunnusnippu = '$kukarow[kesken]'
							WHERE tunnus = '$kukarow[kesken]' and tunnusnippu = 0";
				$result = pupe_query($query);

				if ($toim == "PROJEKTI") {
					// avataan projekti kirjanpitoon
					$query = " INSERT into kustannuspaikka set yhtio  = '$kukarow[yhtio]', nimi = '$id - $nimi', tyyppi = 'P', kaytossa = 'o', luontiaika = now(), laatija = '$kukarow[kuka]'";
					$result = pupe_query($query);
					$projekti = mysql_insert_id();

					$projektilla = $tilausnumero;
				}
			}

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO" or $toim == "TARJOUS" or $tilaustyyppi == "T" or $tilaustyyppi == "A") {
				//otetaan kantaa pyörän malliin
				if ($merkki2 != 'eimenu' and $merkki2 != '') {
					$merkki = $merkki2;
				}

				$query2 .= "tyomaarays SET
							yhtio			= '$kukarow[yhtio]',
							kotipuh 		= '$kotipuh',
							tyopuh 			= '$tyopuh',
							myyjaliike 		= '$myyjaliike',
							ostopvm 		= '$ostopvmvv-$ostopvmkk-$ostopvmpp',
							rekno 			= '$rekno',
							mittarilukema 	= '$mittarilukema',
							merkki 			= '$merkki',
							mallivari 		= '$mallivari',
							valmnro 		= '$valmnro',
							tuotu 			= '$tuotuvv-$tuotukk-$tuotupp',
							luvattu 		= '$luvattuvv-$luvattukk-$luvattupp',
							komm1 			= '$komm1',
							komm2 			= '$komm2',
							suorittaja 		= '$suorittaja',
							viite	 		= '$viite',
							tyojono			= '$tyojono',
							tyostatus		= '$tyostatus',
							prioriteetti	= '$prioriteetti',
							jalleenmyyja 	= '$jalleenmyyja',
							tehdas			= '$tehdas',
							tyoaika			= '$tyoaika',
							vikakoodi		= '$vikakoodi',
							takuunumero		= '$takuunumero',
							tyokoodi		= '$tyokoodi',
							hyvaksy			= '$hyvaksy',
							otunnus 		= '$kukarow[kesken]'";
				$query2 .= $postquery2;
				$result = pupe_query($query2);
			}

			if (in_array($toim, array("PROJEKTI", "RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "TARJOUS", "ENNAKKO", "EXTRANET", "YLLAPITO", "MYYNTITILI", "VALMISTAASIAKKAALLE", "WEBTILAUSTARJOUS","EXTRANET_REKLAMAATIO"))) {

				if (is_array($yllapito_kk)) {
					$sopimus_kk = implode(",", $yllapito_kk);
				}
				else {
					$sopimus_kk = "";
				}

				if (is_array($yllapito_pp)) {
					$sopimus_pp = implode(",", $yllapito_pp);
				}
				else {
					$sopimus_pp = "";
				}

				$query3 .= "laskun_lisatiedot SET
							yhtio						= '$kukarow[yhtio]',
							sopimus_kk					= '$sopimus_kk',
							sopimus_pp					= '$sopimus_pp',
							sopimus_alkupvm				= '$yllapito_alkuvv-$yllapito_alkukk-$yllapito_alkupp',
							sopimus_loppupvm			= '$yllapito_loppuvv-$yllapito_loppukk-$yllapito_loppupp',
							sopimus_lisatietoja			= '$sopimus_lisatietoja',
							kolm_ovttunnus				= '$kolm_ovttunnus',
							kolm_nimi					= '$kolm_nimi',
							kolm_nimitark				= '$kolm_nimitark',
							kolm_osoite					= '$kolm_osoite',
							kolm_postino				= '$kolm_postino',
							kolm_postitp				= '$kolm_postitp',
							kolm_maa					= '$kolm_maa',
							laskutus_nimi				= '$laskutus_nimi',
							laskutus_nimitark			= '$laskutus_nimitark',
							laskutus_osoite				= '$laskutus_osoite',
							laskutus_postino			= '$laskutus_postino',
							laskutus_postitp			= '$laskutus_postitp',
							laskutus_maa				= '$laskutus_maa',
							toimitusehto2				= '$toimitusehto2',
							projektipaallikko			= '$projektipaallikko',
							yhteyshenkilo_kaupallinen	= '$yhteyshenkilo_kaupallinen',
							yhteyshenkilo_tekninen		= '$yhteyshenkilo_tekninen',
							seuranta					= '$seuranta',
							projekti					= '$projekti',
							saate						= '$saate',
							tunnusnippu_tarjous			= '$tunnusnippu_tarjous',
							rivihintoja_ei_nayteta		= '$rivihintoja_ei_nayteta',
							kasinsyotetty_viite			= '$kasinsyotetty_viite',
							kantaasiakastunnus			= '$kantaasiakastunnus',
							otunnus 					= '$kukarow[kesken]'";
				$query3 .= $postquery3;
				$result = pupe_query($query3);
			}

			// Päivitetään rahtikirjatiedot jos ne on syötetty
			if ($alatila == "B" or $alatila == "D" or $alatila == "J" or $alatila == "E") {
				$query4 = "	UPDATE rahtikirjat
							SET toimitustapa = '$toimitustapa',
							merahti = '$maksaja'
							where yhtio = '$kukarow[yhtio]'
							and otsikkonro = '$kukarow[kesken]'
							and tulostettu = '0000-00-00 00:00:00'";
				$result = pupe_query($query4);
			}

			//	Tahdistetaan toimitukset
			if ($toim == "PROJEKTI" or $yhtiorow["splittauskielto"] == "K") {
				paivita_toimitukset($kukarow["kesken"], $originaali);
			}

		}
	}

	unset($alv);
