<?php

	require("../inc/parametrit.inc");

	// Otetaan jQuery mukaan
	echo "<script src='{$palvelin2}inc/jquery.min.js'></script>";

	// Salitaan vain numeroita ja piste/pilkku input kentiss�
	echo '<script language="javascript">
	$(document).ready(function() {
	    $("#vain_numeroita").keydown(function(event) {
	        // sallitaan backspace (8) ja delete (46)
	        if ( event.keyCode == 46 || event.keyCode == 8 ) {
	            // anna sen vaan tapahtua...
	        }
	        else {
	            // 48-57 on normin�pp�imist�n numerot, numpad numerot on 96-105, piste on 190 ja pilkku 188
	            if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) && event.keyCode != 188 && event.keyCode != 190) {
	                event.preventDefault();
	            }
	        }
	    });
		$("a.toggle_rivit").click(function(event) {
			event.preventDefault();
			$("tr.togglettava_rivi_"+$(this).attr("id")).toggle();
		});
	});
	</script>';

	echo "<font class='head'>".t("Valmistuksien suunnittelu")."</font><hr>";

	// org_rajausta tarvitaan yhdess� selectiss� joka trigger�i taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	// Ehdotetaan oletuksena ehdotusta ensikuun valmistuksille sek� siit� plus 3 kk
	if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
	if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
	if (!isset($kkl1)) $kkl1 = date("m", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
	if (!isset($vvl1)) $vvl1 = date("Y", mktime(0, 0, 0, date("m")+4, 0, date("Y")));

	// P�iv�m��r�tarkistus
	if (!checkdate($kka1, 1, $vva1)) {
		echo "<font class='error'>".t("Virheellinen alkup�iv�!")."</font><br>";
		$tee = "";
	}
	else {
		$nykyinen_alku  = date("Y-m-d", mktime(0, 0, 0, $kka1, 1, $vva1));
		$edellinen_alku = date("Y-m-d", mktime(0, 0, 0, $kka1, 1, $vva1-1));
	}

	if (!checkdate($kkl1, 1, $vvl1)) {
		echo "<font class='error'>".t("Virheellinen loppup�iv�!")."</font><br>";
		$tee = "";
	}
	else {
		$nykyinen_loppu  = date("Y-m-d", mktime(0, 0, 0, $kkl1+1, 0, $vvl1));
		$edellinen_loppu = date("Y-m-d", mktime(0, 0, 0, $kkl1+1, 0, $vvl1-1));
	}

	if ($nykyinen_alku > $nykyinen_loppu) {
		echo "<font class='error'>".t("Virheelliset kaudet!")." $nykyinen_alku > $nykyinen_loppu</font><br>";
		$tee = "";
	}

	// Muuttujia
	$ytunnus = isset($ytunnus) ? trim($ytunnus) : "";
	$toimittajaid = isset($toimittajaid) ? trim($toimittajaid) : "";

	// T�m� palauttaa tuotteen valmistuksen tiedot
	function teerivi($tuoteno, $valittu_toimittaja, $abc_rajaustapa) {

		// Kukarow ja p�iv�m��r�t globaaleina
		global $kukarow, $edellinen_alku, $edellinen_loppu, $nykyinen_alku, $nykyinen_loppu, $ryhmanimet;

		// Tehd��n kaudet p�iv�m��rist�
		$alku_kausi = substr(str_replace("-", "", $nykyinen_alku), 0, 6);
		$loppu_kausi = substr(str_replace("-", "", $nykyinen_loppu), 0, 6);

		// Haetaan tuotteen ABC luokka
		$query = "	SELECT abc_aputaulu.luokka
					FROM abc_aputaulu
					WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
					AND abc_aputaulu.tyyppi = '{$abc_rajaustapa}'
					AND abc_aputaulu.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$abcluokka = isset($ryhmanimet[$row['luokka']]) ? $ryhmanimet[$row['luokka']] : t("Ei tiedossa");

		// Haetaan tuotteen varastosaldo
		$query = "	SELECT ifnull(sum(tuotepaikat.saldo),0) saldo
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotepaikat.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$varastosaldo = $row['saldo'];

		// Haetaan tuotteen vuosikulutus (= myynti)
		$query = "	SELECT ifnull(sum(tilausrivi.kpl), 0) vuosikulutus
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.tuoteno = '{$tuoteno}'
					AND tilausrivi.toimitettuaika >= DATE_SUB(now(), INTERVAL 1 YEAR)";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$vuosikulutus = $row['vuosikulutus'];

		// Haetaan tuotteen valmistuksessa, ostettu, varattu sek� ennakkotilattu m��r�
		$query = "	SELECT
					ifnull(sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)), 0) tilattu,
					ifnull(sum(if(tilausrivi.tyyppi = 'L', tilausrivi.varattu, 0)), 0) varattu,
					ifnull(sum(if(tilausrivi.tyyppi = 'E', tilausrivi.varattu, 0)), 0) ennakko,
					ifnull(sum(if(tilausrivi.tyyppi IN ('V','W'), tilausrivi.varattu, 0)), 0) valmistuksessa
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi IN ('O', 'L', 'E', 'V', 'W')
					AND tilausrivi.tuoteno = '{$tuoteno}'
					AND tilausrivi.varattu != 0";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$tilattu = $row['tilattu'];
		$varattu = $row['varattu'];
		$ennakko = $row['ennakko'];
		$valmistuksessa = $row['valmistuksessa'];

		// Haetaan tuotteen toimittajatiedot
		$query = "	SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika) toimitusaika,
					if(tuotteen_toimittajat.pakkauskoko > 0, tuotteen_toimittajat.pakkauskoko, 1) pakkauskoko,
					tuotteen_toimittajat.toimittaja,
					toimi.nimi,
					toimi.tunnus
					FROM tuotteen_toimittajat
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus and toimi.tunnus = '{$valittu_toimittaja}')
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotteen_toimittajat.tuoteno = '{$tuoteno}'
					ORDER BY if(jarjestys = 0, 9999, jarjestys)
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$toimittajarow = mysql_fetch_assoc($result);
		}
		else {
			// Toimittajaa ei l�ydy -> alustetaan defaulttiarvot (lis�� t�h�n jos muutat query�)
			$toimittajarow = array(
							"toimitusaika" => 0,
							"pakkauskoko" => 1,
							"toimittaja" => "",
							"nimi" => t("Ei toimittajaa"),
							"tunnus" => 0,
							);
		}

		// Haetaan budjetoitu myynti
		$query = "	SELECT ifnull(sum(budjetti_tuote.maara), 0) maara
					FROM budjetti_tuote
					WHERE budjetti_tuote.yhtio = '{$kukarow["yhtio"]}'
					AND budjetti_tuote.kausi >= '{$alku_kausi}'
					AND budjetti_tuote.kausi <= '{$loppu_kausi}'
					AND budjetti_tuote.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$budjetoitu_myynti = $row['maara'];

		// Jos ei ole budjettia, otetaan edellisen kauden myynti ja k�ytet��n sit�
		if ($budjetoitu_myynti == 0) {
			$query = "	SELECT ifnull(sum(tilausrivi.kpl), 0) myynti_ed
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
						AND tilausrivi.tyyppi = 'L'
						AND tilausrivi.tuoteno = '{$tuoteno}'
						AND tilausrivi.laskutettuaika >= '{$edellinen_alku}'
						AND tilausrivi.laskutettuaika <= '{$edellinen_loppu}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$budjetoitu_myynti = $row["myynti_ed"];
		}

		// Lasketaan reaalisaldo
		$reaalisaldo = $varastosaldo + $tilattu + $valmistuksessa - $varattu - $ennakko;

		// Lasketaan riittop�iv�t
		$paivakulutus = round($vuosikulutus / 240, 6);
		$riittopv = ($paivakulutus == 0) ? t("Ei tiedossa") : floor($reaalisaldo / $paivakulutus);

		// Lasketaan m��r�ennuste (paljon kuluu toimittajan toimitusajan aikana + arvioitu myynti)
		$maaraennuste = ($paivakulutus * $toimittajarow['toimitusaika']) + $budjetoitu_myynti;

		// Lasketaan paljon kannattaisi valmistaa
		$valmistussuositus = round($maaraennuste - $reaalisaldo);

		// Py�ristet��n suositus yl�sp�in seuraavaan pakkauskokoon
		$valmistusmaara = round($valmistussuositus / $toimittajarow['pakkauskoko']) * $toimittajarow['pakkauskoko'];

		// Palautettava array
		$tuoterivi = array();
		$tuoterivi['reaalisaldo']		= $reaalisaldo;
		$tuoterivi['varastosaldo']		= $varastosaldo;
		$tuoterivi['tilattu']			= $tilattu;
		$tuoterivi['valmistuksessa']	= $valmistuksessa;
		$tuoterivi['varattu']			= $varattu;
		$tuoterivi['ennakko']			= $ennakko;
		$tuoterivi['budjetoitu_myynti'] = $budjetoitu_myynti;
		$tuoterivi['vuosikulutus']		= $vuosikulutus;
		$tuoterivi['paivakulutus']		= $paivakulutus;
		$tuoterivi['riittopv'] 			= $riittopv;
		$tuoterivi['maaraennuste']		= $maaraennuste;
		$tuoterivi['toimitusaika']		= $toimittajarow['toimitusaika'];
		$tuoterivi['valmistussuositus'] = $valmistussuositus;
		$tuoterivi['pakkauskoko']		= $toimittajarow['pakkauskoko'];
		$tuoterivi['valmistusmaara'] 	= $valmistusmaara;
		$tuoterivi['abcluokka']			= $abcluokka;

		return $tuoterivi;
	}

	// Jos saadaan muut parametrit tehd��n niist� muuttujat
	if (isset($muutparametrit)) {
		foreach (explode("##", $muutparametrit) as $muutparametri) {
			list($a, $b) = explode("=", $muutparametri);
			${$a} = $b;
		}
		$tee = "";
	}

	// Toimittajahaku
	if ($ytunnus != "" and $toimittajaid == "") {

		// Tehd��n muut parametrit
		$muutparametrit = "";
		unset($_POST["toimittajaid"]);

		foreach ($_POST as $key => $value) {
			$muutparametrit .= $key."=".$value."##";
		}

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid == 0) {
			$tee = "�L�MEEMIHINK��N";
		}
		else {
			$tee = "";
		}
	}

	if (isset($tee) and $tee == "TEE_VALMISTUKSET") {

		$edellinen_valmistuslinja = "X";

		// Sortataan array uudestaan, jos k�ytt�j� on vaihtanut valmistuslinjoja
		// Sortataan 2 dimensoinen array. Pit�� ensiksi tehd� sortattavista keyst� omat arrayt
		$apusort_jarj0 = $apusort_jarj1 = array();

		foreach ($valmistettavat_tuotteet as $apusort_key => $apusort_row) {
		    $apusort_jarj0[$apusort_key] = $apusort_row['valmistuslinja'];
			$apusort_jarj1[$apusort_key] = $apusort_row['riittopv'];
		}

		// Sortataan by valmistuslinja, riittopv
		array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_ASC, $valmistettavat_tuotteet);

		$lahde_varasto = mysql_real_escape_string($lahde_varasto);
		$kohde_varasto = mysql_real_escape_string($kohde_varasto);
		$valmistus_ajankohta = mysql_real_escape_string($valmistus_ajankohta);

		foreach ($valmistettavat_tuotteet as $tuoterivi) {

			$maara = (float) $tuoterivi["valmistusmaara"];
			$tuoteno = mysql_real_escape_string($tuoterivi["tuoteno"]);
			$valmistuslinja = mysql_real_escape_string($tuoterivi["valmistuslinja"]);
			$vakisin_hyvaksy = (isset($tuoterivi["hyvaksy"]) and $tuoterivi["hyvaksy"] != "") ? "H" : "";

			// Oikellisuustarkastus hoidetaan javascriptill�, ei voi tulla kun numeroita!
			if ($maara != 0) {

				if ($edellinen_valmistuslinja != $valmistuslinja) {

					$aquery = "	SELECT *
								FROM varastopaikat
								WHERE yhtio = '{$kukarow["yhtio"]}'
								and tunnus = '{$kohde_varasto}'";
					$vtresult = pupe_query($aquery);
					$vtrow = mysql_fetch_array($vtresult);

					$query = "	INSERT INTO lasku SET
								yhtio               = '{$kukarow["yhtio"]}',
								yhtio_nimi          = '{$yhtiorow["nimi"]}',
								yhtio_osoite        = '{$yhtiorow["osoite"]}',
								yhtio_postino       = '{$yhtiorow["postino"]}',
								yhtio_postitp       = '{$yhtiorow["postitp"]}',
								yhtio_maa           = '{$yhtiorow["maa"]}',
								maa					= '{$vtrow["maa"]}',
								nimi 				= '{$vtrow["nimitys"]}',
								nimitark			= '{$vtrow["nimi"]}',
								osoite				= '{$vtrow["osoite"]}',
								postino				= '{$vtrow["postino"]}',
								postitp				= '{$vtrow["postitp"]}',
								ytunnus				= '{$vtrow["nimitys"]}',
								toimaika            = '$valmistus_ajankohta',
								kerayspvm			= '$valmistus_ajankohta',
								laatija             = '{$kukarow["kuka"]}',
								luontiaika          = now(),
								tila                = 'V',
								kohde				= '{$valmistuslinja}',
								varasto				= '{$lahde_varasto}',
								clearing			= '{$kohde_varasto}',
								tilaustyyppi		= 'W',
								liitostunnus		= '9999999999'";
					$result = pupe_query($query);
					$otunnus = mysql_insert_id();

					$query = "	SELECT *
								FROM lasku
								WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
								AND lasku.tunnus = '{$otunnus}'";
					$result = pupe_query($query);
					$laskurow = mysql_fetch_assoc($result);

					echo "<font class='message'>".t("Valmistus")." $otunnus ".t("luotu").".</font><br>";

					$edellinen_valmistuslinja = $valmistuslinja;
				}

				$query = "	SELECT *
							FROM tuote
							WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
							AND tuote.tuoteno = '$tuoteno'";
				$result = pupe_query($query);
				$trow = mysql_fetch_assoc($result);

				$trow				= $trow;						// jossa on tuotteen kaikki tiedot
				$rivinumero			= "";							// kent�ss� on joko tilaajan rivinumero tai konserninsis�isiss� kaupoissa sis�inen toimittajanumero
				$laskurow			= $laskurow;					// jossa on laskun kaikki tiedot
				$kukarow["kesken"]	= $otunnus;						// jossa on k�ytt�j�ll� keskenoleva tilausnumero
				$kpl				= $maara;						// jossa on tilattu kappalem��r�
				$tuoteno			= $trow["tuoteno"];				// jossa on tilattava tuotenumero
				$toimaika			= $laskurow["toimaika"];		// arvioitu toimitusaika
				$kerayspvm			= $laskurow["toimaika"];		// toivottu ker�ysaika
				$hinta				= "";							// k�ytt�j�n sy�tt�m� hinta
				$netto				= "";							// k�ytt�j�n sy�tt�m� netto
				$ale				= "";							// k�ytt�j�n sy�tt�m� ale (generoidaan yhti�n parametreist�)
				$ale2				= "";							// k�ytt�j�n sy�tt�m� ale2 (generoidaan yhti�n parametreist�)
				$ale3				= "";							// k�ytt�j�n sy�tt�m� ale3 (generoidaan yhti�n parametreist�)
				$var				= $vakisin_hyvaksy;				// H,J,P varrit
				$varasto			= "";							// myyd��n vain t�st�/n�ist� varastosta
				$paikka				= "";							// myyd��n vain t�lt� paikalta
				$rivitunnus			= "";							// tietokannan tunnus jolle rivi lis�t��n
				$rivilaadittu		= "";							// vanhan rivin laadittuaika, s�ilytet��n se
				$korvaavakielto		= "";							// Jos erisuuri kuin tyhj� niin ei myyd� korvaavia
				$jtkielto			= "";							// Jos erisuuri kuin tyhj� niin ei laiteta JT:Seen
				$perhekielto		= "";							// Jos erisuuri kuin tyhj� niin ei etsit� ollenkaan perheit�
				$varataan_saldoa	= "";							// Jos == EI niin ei varata saldoa (tietyiss� keisseiss�), tai siis ei ainakan tehd� saldotsekki�
				$kutsuja			= "";							// Kuka t�t� skripti� kutsuu
				$myy_sarjatunnus	= "";							// Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
				$osto_sarjatunnus	= "";							// Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
				$jaksotettu			= "";							// Kuuluuko tilausrivi mukaan jaksotukseen
				$perheid			= "";							// Tuoteperheen perheid
				$perheid2			= "";							// Lis�varusteryhm�n perheid2
				$orvoteikiinnosta	= "";							// Meit� ei kiinnosta orvot jos t�m� ei ole tyhj�.
				$osatoimkielto		= "";							// Jos saldo ei riit� koko riville niin ei lis�t� rivi� ollenkaan
				$olpaikalta			= "";							// pakotetaan myym��n oletuspaikalta
				$tuotenimitys		= "";							// tuotteen nimitys jos nimityksen sy�t� on yhti�ll� sallittu
				$tuotenimitys_force	= "";							// tuotteen nimitys muutetaan systemitasolla

				require ("tilauskasittely/lisaarivi.inc");
			}
		}

		echo "<br><br>";
		$tee = "";
	}

	// Tehd��n raportti
	if (isset($ehdotusnappi) and $ehdotusnappi != "") {

		$tuote_where       = ""; // tuote-rajauksia
		$toimittaja_join   = ""; // toimittaja-rajauksia
		$toimittaja_select = ""; // toimittaja-rajauksia
		$abc_join          = ""; // abc-rajauksia
		$toggle_counter    = 0;

		if (isset($mul_osasto) and count($mul_osasto) > 0) {
			$tuote_where .= " and tuote.osasto in (".implode(",", $mul_osasto).")";
		}

		if (isset($mul_try) and count($mul_try) > 0) {
			$tuote_where .= " and tuote.try in (".implode(",", $mul_try).")";
		}

		if (isset($mul_tme) and count($mul_tme) > 0) {
			$tuote_where .= " and tuote.tuotemerkki in ('".implode("','", $mul_tme)."')";
		}

		if ($status != '') {
			$tuote_where .= " and tuote.status = '$status'";
		}
		else {
			$tuote_where .= " and tuote.status != 'P'";
		}

		if ($toimittajaid != '') {
			// Jos ollaan rajattu toimittaja, niin otetaan vain sen toimittajan tuotteet ja laitetaan mukaan selectiin
			$toimittaja_join = "JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid')";
			$toimittaja_select = "tuotteen_toimittajat.liitostunnus toimittaja";
		}
		else {
			// Jos toimittajaa ei olla rajattu, haetaan tuotteen oletustoimittaja subqueryll�
			$toimittaja_select = "(SELECT liitostunnus FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = ifnull(samankaltaiset.isatuoteno, tuote.tuoteno) ORDER BY if(jarjestys = 0, 9999, jarjestys), toimittaja LIMIT 1) toimittaja";
		}

		if ($abcrajaus != "") {

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			// katotaan JT:ss� olevat tuotteet ABC-analyysi� varten, koska ne pit�� includata aina!
			$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
						FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
							AND tuote.tuoteno = tilausrivi.tuoteno
							AND tuote.ei_saldoa = ''
							{$tuote_where})
						WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
						AND tilausrivi.tyyppi IN ('L','G')
						AND tilausrivi.var = 'J'
						AND tilausrivi.jt {$lisavarattu} > 0";
			$vtresult = pupe_query($query);
			$vrow = mysql_fetch_array($vtresult);

			$jt_tuotteet = "''";

			if ($vrow[0] != "") {
				$jt_tuotteet = $vrow[0];
			}

			// joinataan ABC-aputaulu
			$abc_join = " 	JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
							AND abc_aputaulu.tuoteno = tuote.tuoteno
							AND abc_aputaulu.tyyppi = '{$abcrajaustapa}'
							AND (luokka <= '{$abcrajaus}' or luokka_osasto <= '{$abcrajaus}' or luokka_try <= '{$abcrajaus}' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ({$jt_tuotteet}))) ";
		}
		else {
			$abc_join = "	LEFT JOIN abc_aputaulu ON (abc_aputaulu.yhtio = tuote.yhtio
							AND abc_aputaulu.tuoteno = tuote.tuoteno
							AND abc_aputaulu.tyyppi = '{$abcrajaustapa}')";
		}

		// Haetaan tehdyt valmistukset
		$query = "	SELECT
					lasku.kohde valmistuslinja,
					tilausrivi.tuoteno,
					tilausrivi.osasto,
					tilausrivi.try,
					tilausrivi.kpl+tilausrivi.varattu maara,
					DATE_FORMAT(lasku.luontiaika, GET_FORMAT(DATE, 'EUR')) pvm,
					lasku.alatila tila
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
						AND tilausrivi.otunnus = lasku.tunnus
						AND tilausrivi.tyyppi = 'W'
						AND tilausrivi.var != 'P')
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					AND lasku.tila = 'V'
					AND lasku.toimaika >= '{$nykyinen_alku}'
					AND lasku.toimaika <= '{$nykyinen_loppu}'
					ORDER BY lasku.kohde, lasku.toimaika, tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			// N�ytet��n tehdyt ja suunnitellut valmistukset
			$EDlinja = false;
			$valmistettu_yhteensa = 0;

			echo t("Valmistukset aikav�lill�").": $nykyinen_alku - $nykyinen_loppu <br>\n";
			echo t("Valmistuksia")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";

			echo "<table>";

			while ($row = mysql_fetch_assoc($res)) {

				// Valmistuslinja vaihtuu
				if ($row['valmistuslinja'] != $EDlinja or $EDlinja === false) {

					// Yhteens�rivi
					if ($EDlinja !== false) {
						echo "<tr>";
						echo "<th colspan='3'>".t("Yhteens�")."</th>";
						echo "<th colspan='3' style='text-align: right;'>$valmistettu_yhteensa</th>";
						echo "</tr>";
						$valmistettu_yhteensa = 0;
					}

					$valmistuslinja = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='{$row["valmistuslinja"]}'", "", "", "selitetark");
					$valmistuslinja = empty($valmistuslinja) ? t("Ei valmistuslinjaa") : $valmistuslinja;
					$toggle_counter++;

					echo "<tr>";
					echo "<td class='back' colspan='8'><font class='head'><br>$valmistuslinja &raquo; </font> <a href='#' class='toggle_rivit' id='$toggle_counter'>".t("N�yt� tuotteet")."</a></td>";
					echo "</tr>";

					echo "<tr class='togglettava_rivi_$toggle_counter' style='display: none;'>";
					echo "<th>".t("Tuotenumero")."</th>";
					echo "<th>".t("Osasto")."</th>";
					echo "<th>".t("Tuoteryhm�")."</th>";
					echo "<th>".t("M��r�")."</th>";
					echo "<th>".t("Pvm")."</th>";
					echo "<th>".t("Tila")."</th>";
					echo "</tr>";
				}

				$EDlinja = $row['valmistuslinja'];
				$valmistettu_yhteensa += $row["maara"];

				echo "<tr class='aktiivi togglettava_rivi_$toggle_counter' style='display: none;'>";
				echo "<td>{$row["tuoteno"]}</td>";
				echo "<td>{$row["osasto"]}</td>";
				echo "<td>{$row["try"]}</td>";
				echo "<td style='text-align: right;'>{$row["maara"]}</td>";
				echo "<td>{$row["pvm"]}</td>";

				$laskutyyppi = "V";
				$alatila = $row["tila"];
				require ("inc/laskutyyppi.inc");

				echo "<td>$laskutyyppi $alatila</td>";
				echo "</tr>";

			}

			echo "<tr>";
			echo "<th colspan='3'>".t("Yhteens�")."</th>";
			echo "<th colspan='3' style='text-align: right;'>$valmistettu_yhteensa</th>";
			echo "</tr>";

			echo "</table>";
		}
		else {
			echo t("Annetulle aikav�lille ei l�ydy valmistuksia.");
		}

		// Haetaan valmistettavat is�tuotteet, jotka osuvat hakuehtoihin
		// Jos tuotteella on samankaltaisia tuotteita, haetaan vain "samankaltaisuuden" is�tuotteet mukaan
		$query = "	SELECT
					ifnull(samankaltaiset.isatuoteno, tuote.tuoteno) tuoteno,
					ifnull(samankaltainen_tuote.nimitys, tuote.nimitys) nimitys,
					ifnull(samankaltainen_tuote.valmistuslinja, tuote.valmistuslinja) valmistuslinja,
					{$toimittaja_select}
					FROM tuote
					JOIN tuoteperhe ON (tuoteperhe.yhtio = tuote.yhtio
						AND tuoteperhe.isatuoteno = tuote.tuoteno
						AND tuoteperhe.tyyppi = 'R')
					LEFT JOIN tuoteperhe AS samankaltaiset ON (samankaltaiset.yhtio = tuote.yhtio
						AND samankaltaiset.tuoteno = tuote.tuoteno
						AND samankaltaiset.tyyppi = 'S')
					LEFT JOIN tuote AS samankaltainen_tuote ON (samankaltainen_tuote.yhtio = tuote.yhtio
						AND samankaltainen_tuote.tuoteno = samankaltaiset.isatuoteno)
					{$toimittaja_join}
					{$abc_join}
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.ei_saldoa = ''
					{$tuote_where}
					GROUP BY 1, 2, 3, 4";
		$res = pupe_query($query);

		echo "<br/><br/><font class='head'>".t("Ehdotetut valmistukset")."</font><br/><hr>";

		// Ker�t��n valmistettavien tuotteiden tiedot arrayseen
		$valmistettavat_tuotteet = array();

		while ($row = mysql_fetch_assoc($res)) {

			// Ker�t��n mahdolliset samankaltaiset yhteen arrayseen
			$kasiteltavat_tuotteet = array();
			$kasiteltavat_key = 0;

			// Haetaan tuotteen tiedot
			$kasiteltavat_tuotteet[$kasiteltavat_key] = teerivi($row["tuoteno"], $row["toimittaja"], $abcrajaustapa);
			$kasiteltavat_tuotteet[$kasiteltavat_key]["tuoteno"] = $row["tuoteno"];
			$kasiteltavat_tuotteet[$kasiteltavat_key]["nimitys"] = $row["nimitys"];
			$kasiteltavat_tuotteet[$kasiteltavat_key]["valmistuslinja"] = $row["valmistuslinja"];
			$kasiteltavat_tuotteet[$kasiteltavat_key]["isatuote"] = $row["tuoteno"];

			// Otetaan is�tuotteen pakkauskoko talteen, sill� sen perusteella tulee laskea "samankaltaisten" valmistusm��r�
			$isatuotteen_pakkauskoko = $kasiteltavat_tuotteet[$kasiteltavat_key]["pakkauskoko"];
			$kasiteltavat_tuotteet[$kasiteltavat_key]["isatuotteen_pakkauskoko"] = $isatuotteen_pakkauskoko;

			// Katsotaan onko kyseess� "samankaltainen" is�tuote ja haetaan lapsituotteiden infot
			$query = "	SELECT tuote.tuoteno,
						tuote.nimitys,
						tuote.valmistuslinja
						FROM tuoteperhe
						JOIN tuote USING (yhtio, tuoteno)
						WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
						AND tuoteperhe.isatuoteno = '{$row["tuoteno"]}'
						AND tuoteperhe.tyyppi = 'S'";
			$samankaltainen_result = pupe_query($query);

			if (mysql_num_rows($samankaltainen_result) > 0) {
				$samankaltaiset_tuotteet = "{$row["tuoteno"]} ";
			}
			else {
				$samankaltaiset_tuotteet = "";
			}

			while ($samankaltainen_row = mysql_fetch_assoc($samankaltainen_result)) {
				$kasiteltavat_key++;
				$kasiteltavat_tuotteet[$kasiteltavat_key] = teerivi($samankaltainen_row["tuoteno"], $row["toimittaja"], $abcrajaustapa);
				$kasiteltavat_tuotteet[$kasiteltavat_key]["tuoteno"] = $samankaltainen_row["tuoteno"];
				$kasiteltavat_tuotteet[$kasiteltavat_key]["nimitys"] = $samankaltainen_row["nimitys"];
				$kasiteltavat_tuotteet[$kasiteltavat_key]["valmistuslinja"] = $samankaltainen_row["valmistuslinja"];
				$kasiteltavat_tuotteet[$kasiteltavat_key]["isatuote"] = $row["tuoteno"];
				$kasiteltavat_tuotteet[$kasiteltavat_key]["isatuotteen_pakkauskoko"] = $isatuotteen_pakkauskoko;
				$samankaltaiset_tuotteet .= "{$samankaltainen_row["tuoteno"]} ";
			}

			// Loopataan k�sitellyt tuotteet ja lasketaan yhteens� valmistettava m��r�. Lis�ksi poistetaan arrayst� kaikki tuotteet, jota ei tule valmistaa
			$valmistettava_yhteensa = 0;
			foreach ($kasiteltavat_tuotteet as $key => $kasittelyssa) {
				if ($kasittelyssa["valmistussuositus"] <= 0) {
					unset($kasiteltavat_tuotteet[$key]);
				}
				else {
					$valmistettava_yhteensa += $kasittelyssa["valmistussuositus"];
				}
			}

			// Jos meille j�i jotain valmistettavaa
			if ($valmistettava_yhteensa != 0) {
				// Jos meill� oli joku poikkeava pakkauskoko tuotteelle, lasketaan valmistusm��r� uudestaan
				if ($isatuotteen_pakkauskoko != 1) {

					// Py�ristet��n koko samankaltaisten nippu yl�sp�in seuraavaan pakkauskokoon
					$samankaltaisten_valmistusmaara = round($valmistettava_yhteensa / $isatuotteen_pakkauskoko) * $isatuotteen_pakkauskoko;

					foreach ($kasiteltavat_tuotteet as $key => $kasittelyssa) {
						// Lasketaan paljonko t�m�n tuotteen valmistusmaara on koko valmistuksesta
						$kasiteltavat_tuotteet[$key]["valmistusmaara"] = round($kasittelyssa["valmistussuositus"] / $valmistettava_yhteensa * $samankaltaisten_valmistusmaara);
					}
				}

				// Lis�t��n k�sitellyt tuotteet valmistettavien tuotteiden arrayseen
				foreach ($kasiteltavat_tuotteet as $kasittelyssa) {
					$kasittelyssa["sisartuote"] = $samankaltaiset_tuotteet;
					$valmistettavat_tuotteet[] = $kasittelyssa;
				}
			}
		}

		// Loopataan l�pi tehty array

		if (count($valmistettavat_tuotteet) > 0) {

			// Sortataan 2 dimensoinen array. Pit�� ensiksi tehd� sortattavista keyst� omat arrayt
			$apusort_jarj0 = $apusort_jarj1 = $apusort_jarj2 = array();

			foreach ($valmistettavat_tuotteet as $apusort_key => $apusort_row) {
			    $apusort_jarj0[$apusort_key] = $apusort_row['valmistuslinja'];
				$apusort_jarj1[$apusort_key] = $apusort_row['isatuote'];
				$apusort_jarj2[$apusort_key] = $apusort_row['tuoteno'];
			}

			// Sortataan by valmistuslinja, riittopv
			array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_ASC, $apusort_jarj2, SORT_ASC, $valmistettavat_tuotteet);

			// Kootaan raportti
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='kohde_varasto' value='$kohde_varasto'>";
			echo "<input type='hidden' name='lahde_varasto' value='$lahde_varasto'>";
			echo "<input type='hidden' name='valmistus_ajankohta' value='$nykyinen_loppu'>";

			echo "<table>";

			$EDlinja = false;
			$valmistaja_header_piirretty = false;
			$formin_pointteri = 0;

			// loopataan tuotteet l�pi
			foreach ($valmistettavat_tuotteet as $tuoterivi) {

				// Valmistuslinja vaihtuu
				if ($tuoterivi['valmistuslinja'] != $EDlinja or $EDlinja === false) {
					$valmistaja_header = "<tr>";
					$valmistuslinja = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='{$tuoterivi["valmistuslinja"]}'", "", "", "selitetark");
					$valmistuslinja = empty($valmistuslinja) ? t("Ei valmistuslinjaa") : $valmistuslinja;
					$valmistaja_header .= "<td class='back' colspan='8'><font class='head'><br>$valmistuslinja</font></td>";
					$valmistaja_header .= "</tr>";
					$valmistaja_header .= "<tr>";
					$valmistaja_header .= "<th>".t("Tuotenumero")."</th>";
					$valmistaja_header .= "<th>".t("Nimitys")."</th>";
					$valmistaja_header .= "<th>".t("Sisar")."-<br>".t("tuotteet")."</th>";
					$valmistaja_header .= "<th>".t("ABC")."-<br>".t("luokka")."</th>";
					$valmistaja_header .= "<th>".t("Reaali")."-<br>".t("saldo")."</th>";
					$valmistaja_header .= "<th>".t("Valmistuksessa")."</th>";
					$valmistaja_header .= "<th>".t("Riitto Pv")."</th>";
					$valmistaja_header .= "<th>".t("Raaka")."-<br>".t("aine")." ".t("riitto")."</th>";
					$valmistaja_header .= "<th>".t("Vuosi")."-<br>".t("kulutus")."</th>";
					$valmistaja_header .= "<th>".t("Valmistus")."-<br>".t("suositus")."</th>";
					$valmistaja_header .= "<th>".t("Valmistus")."-".t("linja")."</th>";
					$valmistaja_header .= "<th></th>";
					$valmistaja_header .= "<th>".t("Valmistus")."-<br>".t("m��r�")."</th>";
					$valmistaja_header .= "</tr>";
					$valmistaja_header_piirretty = false;
				}

				$EDlinja = $tuoterivi['valmistuslinja'];

				// Pit�� s�ilytt�� table-headeria muuttujassa, sill� voi olla ett� valmistuslinjalle ei tule yht��n tuoterivi� ehdotukseen (eik� haluta piirt�� turhaa headeri�)
				if ($valmistaja_header_piirretty == false) {
					echo $valmistaja_header;
					$valmistaja_header_piirretty = true;
				}

				echo "<tr class='aktiivi'>";
				echo "<td>{$tuoterivi["tuoteno"]}</td>";
				echo "<td>{$tuoterivi["nimitys"]}</td>";
				echo "<td>{$tuoterivi["sisartuote"]}</td>";
				echo "<td>{$tuoterivi["abcluokka"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["reaalisaldo"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["valmistuksessa"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["riittopv"]}</td>";

				// Tarkistetaanko moneenko valmisteeseen meill� on raaka-aineita
				$raaka_aineiden_riitto = raaka_aineiden_riitto($tuoterivi["tuoteno"], (int) $lahde_varasto);
				echo "<td style='text-align: right;'>$raaka_aineiden_riitto</td>";

				echo "<td style='text-align: right;'>{$tuoterivi["vuosikulutus"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["valmistussuositus"]}</td>";

				echo "<td>";

				$result = t_avainsana("VALMISTUSLINJA");

				// jos avainsanoja on perustettu tehd��n dropdown
				if (mysql_num_rows($result) > 0) {

					echo "<select name='valmistettavat_tuotteet[$formin_pointteri][valmistuslinja]' tabindex='-1'>";
					echo "<option value = ''>".t("Ei valmistuslinjaa")."</option>";

					while ($srow = mysql_fetch_array($result)) {
						$sel = ($tuoterivi["valmistuslinja"] == $srow["selite"]) ? "selected" : "";
						echo "<option value='{$srow["selite"]}' $sel>{$srow["selitetark"]}</option>";
					}

					echo "</select>";
				}
				else {
					echo "$valmistuslinja";
					echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][valmistuslinja]' value='{$tuoterivi["valmistuslinja"]}'>";

				}

				echo "</td>";

				// Tehd��n Toggle-nappi, jolla voidaan n�ytt�� matikkainfo alla
				$toggle_counter++;
				echo "<td>";
				echo "<a href='#' style='text-decoration:none;' class='toggle_rivit' id='$toggle_counter'><img src='{$palvelin}pics/lullacons/info.png'></a>";
				echo "</td>";

				echo "<td style='text-align: right;'>";
				echo "<input size='8' style='text-align: right;' type='text' name='valmistettavat_tuotteet[$formin_pointteri][valmistusmaara]' value='{$tuoterivi["valmistusmaara"]}' id='vain_numeroita' tabindex='".($formin_pointteri+1)."'>";
				echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][tuoteno]' value='{$tuoterivi["tuoteno"]}'>";
				echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][riittopv]' value='{$tuoterivi["riittopv"]}'>";
				echo "</td>";
				echo "<td class='back'>";

				if ($raaka_aineiden_riitto < $tuoterivi["valmistusmaara"]) {
					echo "<font class='error'>".t("Raaka-aineiden saldo ei riit�")."!</font><br>";
					echo "<input type='checkbox' name='valmistettavat_tuotteet[$formin_pointteri][hyvaksy]'>";
					echo "<font class='errir'> ".t("Hyv�ksy v�kisin")."</font><br>";
				}

				echo "</td>";
				echo "</tr>";

				// Tehd��n yks hidden rivi t�h�n alle, jossa on kaikki luvut ja kaavat, jota on tehty valmistustarpeen laskemiseksi
				echo "<tr class='togglettava_rivi_$toggle_counter' style='display: none;'>";
				echo "<td colspan='4'>";

				echo "<table>";
				echo "<tr><td>".t("Reaalisaldo")."		</td><td>{$tuoterivi['reaalisaldo']}		</td></tr>";
				echo "<tr><td>".t("Varastosaldo")."		</td><td>{$tuoterivi['varastosaldo']}		</td></tr>";
				echo "<tr><td>".t("Tilattu")."			</td><td>{$tuoterivi['tilattu']}			</td></tr>";
				echo "<tr><td>".t("Valmistuksessa")."	</td><td>{$tuoterivi['valmistuksessa']}		</td></tr>";
				echo "<tr><td>".t("Varattu")."			</td><td>{$tuoterivi['varattu']}			</td></tr>";
				echo "<tr><td>".t("Ennakkotilaukset")."	</td><td>{$tuoterivi['ennakko']}			</td></tr>";
				echo "<tr><td>".t("Budjetoitu myynti")."</td><td>{$tuoterivi['budjetoitu_myynti']}	</td></tr>";
				echo "<tr><td>".t("Vuosikulutus")."		</td><td>{$tuoterivi['vuosikulutus']}		</td></tr>";
				echo "<tr><td>".t("P�iv�kulutus")."		</td><td>{$tuoterivi['paivakulutus']}		</td></tr>";
				echo "<tr><td>".t("Riitto p�iv�t")."	</td><td>{$tuoterivi['riittopv']}			</td></tr>";
				echo "<tr><td>".t("M��r�ennuste")."		</td><td>{$tuoterivi['maaraennuste']}		</td></tr>";
				echo "<tr><td>".t("Toimitusaika")."		</td><td>{$tuoterivi['toimitusaika']}		</td></tr>";
				echo "<tr><td>".t("Valmistussuositus")."</td><td>{$tuoterivi['valmistussuositus']}	</td></tr>";
				echo "<tr><td>".t("Pakkauskoko")."		</td><td>{$tuoterivi['pakkauskoko']}		</td></tr>";
				echo "<tr><td>".t("Is�n Pakkauskoko")."	</td><td>{$tuoterivi['isatuotteen_pakkauskoko']}</td></tr>";
				echo "<tr><td>".t("Valmistusm��r�")."	</td><td>{$tuoterivi['valmistusmaara']}		</td></tr>";
				echo "</table>";

				echo "</td><td colspan='8'>";

				echo t("Reaalisaldo")." = ".t("Varastosaldo")." + ".t("Tilattu")." + ".t("Valmistuksessa")." - ".t("Varattu")." - ".t("Ennakkotilaukset")."<br>";
				echo "{$tuoterivi["reaalisaldo"]} = {$tuoterivi["varastosaldo"]} + {$tuoterivi["tilattu"]} + {$tuoterivi["valmistuksessa"]} - {$tuoterivi["varattu"]} - {$tuoterivi["ennakko"]}<br><br>";
				echo t("P�iv�kulutus")." = round(".t("Vuosikulutus")." / 240)<br>";
				echo "{$tuoterivi["paivakulutus"]} = round({$tuoterivi["vuosikulutus"]} / 240)<br><br>";
				echo t("Riitto p�iv�t")." = floor(".t("Reaalisaldo")." / ".t("P�iv�kulutus").")<br>";
				echo "{$tuoterivi["riittopv"]} = floor({$tuoterivi["reaalisaldo"]} / {$tuoterivi["paivakulutus"]})<br><br>";
				echo t("M��r�ennuste")." = (".t("P�iv�kulutus")." * ".t("Toimitusaika").") + ".t("Budjetoitu myynti")."<br>";
				echo "{$tuoterivi["maaraennuste"]} = ({$tuoterivi["paivakulutus"]} * {$tuoterivi["toimitusaika"]}) + {$tuoterivi["budjetoitu_myynti"]}<br><br>";
				echo t("Valmistussuositus")." = round(".t("M��r�ennuste")." - ".t("Reaalisaldo").")<br>";
				echo "{$tuoterivi["valmistussuositus"]} = round({$tuoterivi["maaraennuste"]} - {$tuoterivi["reaalisaldo"]})<br><br>";
				echo t("Valmistusm��r�")." = round(".t("Valmistussuositus")." / ".t("Pakkauskoko").") * Pakkauskoko<br>";
				echo "{$tuoterivi["valmistusmaara"]} = round({$tuoterivi["valmistussuositus"]} / {$tuoterivi["isatuotteen_pakkauskoko"]}) * {$tuoterivi["isatuotteen_pakkauskoko"]}";

				echo "</td></tr>";

				$formin_pointteri++;
			}

			echo "</table>";

			echo "<br>";
			echo "<input type='hidden' name='tee' value='TEE_VALMISTUKSET' />";
			echo "<input type='submit' name='muodosta_valmistukset' value='".t('Muodosta valmistukset')."' />";
			echo "<br><br>";

			echo "</form>";
			$tee = "";
		}
		else {
			echo "<br><br>";
			echo "<font class='error'>".t("Antamallasi rajauksella ei l�ydy yht��n tuotetta ehdotukseen").".</font><br>";
			echo "<br>";
			$tee = "";
		}
	}

	// N�ytet��n k�ytt�liittym�
	if (!isset($tee) or $tee == "") {

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<table>";

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$kukarow["yhtio"]}'
					order by tyyppi, nimitys";
		$result = pupe_query($query);

		echo "<tr><th>".t("Valmisteiden kohdevarasto")."</th>";

		echo "<td><select name='kohde_varasto'>";

		while ($row = mysql_fetch_assoc($result)) {
			$sel = (isset($kohde_varasto) and $row['tunnus'] == $kohde_varasto) ? "selected" : "";
			echo "<option value='{$row["tunnus"]}' $sel>{$row["nimitys"]}</option>";
		}
		echo "</select></td></tr>";

		mysql_data_seek($result, 0);

		echo "<tr><th>".t("K�yt� raaka-aineita varastosta")."</th>";

		echo "<td><select name='lahde_varasto'>";
		echo "<option value=''>".t("K�yt� kaikista")."</option>";

		while ($row = mysql_fetch_assoc($result)) {
			$sel = (isset($lahde_varasto) and $row['tunnus'] == $lahde_varasto) ? "selected" : "";
			echo "<option value='{$row["tunnus"]}' $sel>{$row["nimitys"]}</option>";
		}
		echo "</select></td></tr>";

		echo "<tr>";
		echo "<th>".t("Tuoterajaus")."</th>";
		echo "<td>";

		$monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
		$monivalintalaatikot_normaali = array();
		require ("tilauskasittely/monivalintalaatikot.inc");

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuotteen status")."</th>";
		echo "<td>";
		echo "<select name='status'>";
		echo "<option value=''>".t("N�yt� kaikki")."</option>";

		$result = t_avainsana("S");

		while ($srow = mysql_fetch_array($result)) {
			$sel = (isset($status) and $status == $srow["selite"]) ? "selected" : "";
			echo "<option value = '$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

		echo "<select name='abcrajaus' onchange='submit()'>";
		echo "<option  value=''>".t("Valitse")."</option>";

		$teksti = "";
		for ($i=0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

			echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
		}

		$teksti = "";
		for ($i=0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

			echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
		}

		$teksti = "";
		for ($i=0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

			echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
		}

		$teksti = "";
		for ($i=0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

			echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
		}

		echo "</select>";

		echo "<tr><th>".t("Toimittaja")."</th><td>";
		if ($toimittajaid == "") {
			echo "<input type='text' size='20' name='ytunnus' value='$ytunnus'>";
		}
		else {
			$query = "	SELECT *
						from toimi
						where yhtio = '{$kukarow["yhtio"]}'
						and tunnus = '{$toimittajaid}'";
			$result = pupe_query($query);
			$toimittaja = mysql_fetch_assoc($result);

			echo "$toimittaja[nimi] $toimittaja[nimitark]";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		}
		echo "</td></tr>";

		echo "<tr>";
		echo "<th>".t("Alkup�iv�m��r� (kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='kka1' value='$kka1' size='5'>";
		echo "<input type='text' name='vva1' value='$vva1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Loppup�iv�m��r� (kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='kkl1' value='$kkl1' size='5'>";
		echo "<input type='text' name='vvl1' value='$vvl1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Suunnittele valmistus")."'></form>";
	}

	require ("inc/footer.inc");

?>