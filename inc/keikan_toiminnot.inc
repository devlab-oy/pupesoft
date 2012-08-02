<?php

	// haetaan keikkaotsikon tiedot
	$query    = "SELECT * FROM lasku WHERE tunnus = '{$keikan_otunnus}' AND yhtio = '{$kukarow['yhtio']}'";
	$result   = pupe_query($query);
	$laskurow = mysql_fetch_array($result);

	function laske_hinta($laskurow, $tunnus) {
		global $kukarow, $yhtiorow;

		// katsotaan onko tälle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
		$query = "	SELECT sum(summa) summa, sum(arvo) arvo, sum(abs(summa)) abssumma, valkoodi, vienti
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tila = 'K'
					and laskunro = '{$laskurow['laskunro']}'
					and vanhatunnus <> 0
					and vienti in ('C','F','I','J','K','L')
					GROUP BY valkoodi, vienti";
		$result = pupe_query($query);

		// jos on, haetaan liitettyjen laskujen
		if (mysql_num_rows($result) == 1) {
			$kulurow = mysql_fetch_array($result);
		}
		else {
			$kulurow = array("vienti" => "", "summa" => 0, "arvo" => 0, "valkoodi" => ""); // muuten tyhjää
		}

		// jos ollaan liitetty jo vaihto-omaisuuslasku, käytetään sen vientikenttää
		if ($kulurow["vienti"] != "") $laskurow["vienti"] = $kulurow["vienti"];

		$alvti = " if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) ";

		// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
		if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

			if ($kulurow["arvo"] != 0) $simualv = round(100 * (($kulurow["summa"]/$kulurow["arvo"])-1),2);
			else $simualv = 0;

			if (in_array($simualv, array(8,12,17,22))) {
				$alvti = $simualv;
				$alvit = $simualv;
			}
			else {
				$alvit = "if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv)";
			}

			if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
				// tutkitaan ollaanko siellä alv-rekisteröity
				$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='{$kukarow['yhtio']}' and maa='{$laskurow['maa']}' and vat_numero != ''";
				$alhire = pupe_query($alhqur);

				// ollaan alv-rekisteröity
				if (mysql_num_rows($alhire) == 1) {
					$alvit = "tuotteen_alv.alv";
					$alvti = " ifnull(tuotteen_alv.alv, 0) ";
				}
			}
		}
		else {
			$alvit = 0;
		}

		$query_ale_lisa = generoi_alekentta('O');

		$query = "	SELECT
					round((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl,
					round((tilausrivi.varattu+tilausrivi.kpl) * (tilausrivi.hinta * {$query_ale_lisa}) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),'{$yhtiorow['hintapyoristys']}') rivihinta,
					round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
											(1+(if ((SELECT max(kaytetty) kaytetty
													FROM sarjanumeroseuranta
													WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
													and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
													and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, {$alvit})/100)) ,'{$yhtiorow['hintapyoristys']}') alerivihinta
					FROM tilausrivi
					JOIN lasku ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and lasku.liitostunnus = '{$laskurow['liitostunnus']}' and lasku.tila in ('O','K','D')
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
					LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '{$laskurow['maa']}')
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					and tilausrivi.tunnus = '{$tunnus}'
					and tilausrivi.tyyppi = 'O'
					GROUP BY tilausrivi.tunnus";
		$hintojen_haku_res = pupe_query($query);
		$hintojen_haku_row = mysql_fetch_assoc($hintojen_haku_res);

		return $hintojen_haku_row;
	}

	if ($_POST['ajax_toiminto'] == 'suuntalava') {
		$tunnus = mysql_real_escape_string($_POST['tunnus']);
		$suuntalava = mysql_real_escape_string($_POST['suuntalava']);

		$query = "	SELECT tila
					FROM suuntalavat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$suuntalava}'";
		$tila_chk_res = pupe_query($query);
		$tila_chk_row = mysql_fetch_assoc($tila_chk_res);

		if ($tila_chk_row['tila'] == 'S') {
			echo json_encode('error');
		}
		else {
			$query = "	UPDATE tilausrivi SET
						suuntalava = '{$suuntalava}'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$tunnus}'";
			$update_res = pupe_query($query);

			$query = "	SELECT uusiotunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$tunnus}'";
			$otunnus_fetch_res = pupe_query($query);
			$otunnus_fetch_row = mysql_fetch_assoc($otunnus_fetch_res);

			$uusiotunnus = $otunnus_fetch_row['uusiotunnus'];

			if ($suuntalava != 0) {
				$query = "	SELECT saapuminen
							FROM suuntalavat_saapuminen
							WHERE yhtio = '{$kukarow['yhtio']}' 
							AND suuntalava = '{$suuntalava}' 
							AND saapuminen = '{$uusiotunnus}'";
				$fetch_res = pupe_query($query);

				if (mysql_num_rows($fetch_res) == 0) {
					$query = "	INSERT INTO suuntalavat_saapuminen SET
								yhtio = '{$kukarow['yhtio']}',
								suuntalava = '{$suuntalava}',
								saapuminen = '{$uusiotunnus}',
								laatija = '{$kukarow['kuka']}',
								luontiaika = now(),
								muutospvm = now(),
								muuttaja = '{$kukarow['kuka']}'";
					$insert_res = pupe_query($query);
				}
			}

			echo json_encode('ok');
		}
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_toimitettuaika') {
		$tunnus = mysql_real_escape_string($_POST['tunnus']);

		if ($rivi_taika_vv < 2000) $rivi_taika_vv += 2000;

		$query = "	UPDATE tilausrivin_lisatiedot SET
					suoratoimitettuaika = '$rivi_taika_vv-$rivi_taika_kk-$rivi_taika_pp'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tilausrivitunnus = '{$tunnus}'";
		$update_res = pupe_query($query);

		echo json_encode('ok');

	}
	elseif ($_POST['ajax_toiminto'] == 'kohdista_rivi') {
		list($valittutunnus, $valittuotunnus) = explode("###", $_POST['valittu']);

		$otunnus = (int) $_POST['otunnus'];

		$suuntalava_querylisa = '';

		if ($yhtiorow['suuntalavat'] == 'S') {

			$suuntalavan_tunnus = $_POST['suuntalavan_tunnus'];
			$suoratoimitusrivi  = $_POST['suoratoimitusrivi'];

			if (trim($suoratoimitusrivi) != "" and (trim($suuntalavan_tunnus) == "" or $suuntalavan_tunnus == 0)) {

				$query = "	SELECT suuntalavat.*
							FROM suuntalavat
							JOIN suuntalavat_saapuminen ON (
								suuntalavat.yhtio = suuntalavat_saapuminen.yhtio
								AND suuntalavat_saapuminen.saapuminen = '{$otunnus}'
								AND suuntalavat_saapuminen.saapuminen != 0
								AND suuntalavat.tunnus = suuntalavat_saapuminen.suuntalava)
							WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
							AND suuntalavat.tila = ''
							AND suuntalavat.sscc = 'Suoratoimitus'";
				$chk_suuntalava_res = pupe_query($query);
				$chk_suuntalava_row = mysql_fetch_assoc($chk_suuntalava_res);

				if (mysql_num_rows($chk_suuntalava_res) == 0) {

					$suuntalavan_tunnus = "";

					// Tehdään uusi suuntalava
					$tee = "lisaa";
					// $otunnus = $keikan_tunnus;
					$sscc = "Suoratoimitus";
					$tyyppi = 0;
					$keraysvyohyke = "Suoratoimitus";
					$usea_keraysvyohyke = 'K';
					$kaytettavyys = "Y";
					$terminaalialue = "Suoratoimitus";
					$korkeus = 0;
					$paino = 0;
					$alkuhyllyalue = "";
					$alkuhyllynro = "";
					$alkuhyllyvali = "";
					$alkuhyllytaso = "";
					$loppuhyllyalue = "";
					$loppuhyllynro = "";
					$loppuhyllyvali = "";
					$loppuhyllytaso = "";
					$automaattinen_paivitys = "S";
					$suuntalavat_ei_kayttoliittymaa = "KYLLA";

					require ("../tilauskasittely/suuntalavat.inc");

					$suuntalavan_tunnus = (int) $uusi_suuntalavan_tunnus;
				}
				else {
					$suuntalavan_tunnus = $chk_suuntalava_row['tunnus'];
				}
			}

			if ($suuntalavan_tunnus > 0) {
				$suuntalava_querylisa = ", suuntalava = $suuntalavan_tunnus ";
			}
			else {
				// toistaiseksi ei päivitetä suuntalavaa tilausriville kohdistuksen yhteydessä automaattisesti
				// $suuntalava = hae_suuntalava($otunnus, $valittutunnus);
				// $suuntalava_querylisa = ", suuntalava = $suuntalava ";
			}
		}

		$updatelisa = $otunnus == 0 ? ", suuntalava = 0, kerattyaika = '0000-00-00 00:00:00', keratty = '', erikoisale=0 " : "$suuntalava_querylisa , kerattyaika = now(), keratty = '{$kukarow['kuka']}', erikoisale = '{$laskurow['erikoisale']}' ";

		// Haetaan rivit ennen päivitystä
		$vanhathinnat = array();

		$updquery = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus IN ('{$valittuotunnus}','{$otunnus}')
						AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
		$selresult = pupe_query($updquery);

		while ($updrow = mysql_fetch_assoc($selresult)) {
			$vanhathinnat[$updrow["tunnus"]] = laske_hinta($laskurow, $updrow["tunnus"]);
		}

		// poistetaan myös netto = X, jos räplätään ruksia
		$query = "	UPDATE tilausrivi use index (yhtio_otunnus) SET
					uusiotunnus = '{$otunnus}',
					netto = ''
					$updatelisa
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND otunnus IN ('{$valittuotunnus}','{$otunnus}')
					AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
		$result = pupe_query($query);

		// Haetaan päivitetyt rivit
		$uudethinnat = array();
		mysql_data_seek($selresult, 0);

		while ($updrow = mysql_fetch_assoc($selresult)) {
			$uudethinnat[$updrow["tunnus"]] = laske_hinta($laskurow, $updrow["tunnus"]);
		}

		$data = array(
			'vanhat_hinnat' => $vanhathinnat,
			'uudet_hinnat' => $uudethinnat,
			'suuntalava' => 0,
			'suuntalava_sscc' => ''
		);

		// Tuotteet päivitetään kohdistuksissa automaattisesti oikeille suuntalavoille, mikäli sellainen on jo perustettu (suuntalavan määriteltyjen varastopaikkojen sekä keräysvyöhykkeen mukaan)
		if ($yhtiorow['suuntalavat'] == 'S' and $otunnus > 0) {
			if ($suuntalavan_tunnus > 0) {

				$otunnus = trim($otunnus);

				$data['suuntalava'] = $suuntalavan_tunnus;

				$query = "	SELECT suuntalavat.sscc, IFNULL(suuntalavat_saapuminen.saapuminen, '') AS saapuminen
							FROM suuntalavat
							LEFT JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
							WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
							AND suuntalavat.tunnus = '{$suuntalavan_tunnus}'";
				$sscc_fetch_res = pupe_query($query);
				$sscc_fetch_row = mysql_fetch_assoc($sscc_fetch_res);

				$data['suuntalava_sscc'] = $sscc_fetch_row['sscc'];

				if ((int) $suuntalavan_tunnus != 0 and $sscc_fetch_row['saapuminen'] == '') {
					$query = "	INSERT INTO suuntalavat_saapuminen SET
								yhtio = '{$kukarow['yhtio']}',
								suuntalava = '{$suuntalavan_tunnus}',
								saapuminen = '{$otunnus}',
								laatija = '{$kukarow['kuka']}',
								luontiaika = now(),
								muutospvm = now(),
								muuttaja = '{$kukarow['kuka']}'";
					$insert_res = pupe_query($query);
				}
			}
			else {
				// toistaiseksi ei setata suuntalavaa dropdowniin kohdistuksen yhteydessä automaattisesti
				// $data['suuntalava'] = $suuntalava;
				$data['suuntalava'] = 0;
			}
		}

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_ale') {
		$ale  			= str_replace(",",".", $_POST['ale']);
		$tunnus 		= $_POST['tunnus'];
		$otunnus 		= $_POST['otunnus'];
		$monesko_kentta = $_POST['monesko_kentta'];

		$query  = "	UPDATE tilausrivi SET
					ale{$monesko_kentta} = '{$ale}'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tunnus}'
					AND ale{$monesko_kentta} <> '{$ale}'";
		$result = pupe_query($query);

		$hinnat = laske_hinta($laskurow, $tunnus);

		$data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_hinta') {

		// otetaan muuttujiin talteen tarpeellisia tietoja
		$hinta  	= str_replace(",",".", $_POST['hinta']);
		$tunnus		= $_POST['tunnus'];
		$otunnus 	= $_POST['otunnus'];

		// päivitetään haluttu hinta tilausriville
		$query  = "UPDATE tilausrivi set hinta='{$hinta}' where yhtio='{$kukarow['yhtio']}' and tunnus='{$tunnus}' and hinta<>'{$hinta}'";
		$result = pupe_query($query);

		$hinnat = laske_hinta($laskurow, $tunnus);

		$data = array('hinta' => $hinta, 'rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_kpl') {
		$kpl 	 = str_replace(",",".", $_POST['kpl']);
		$tunnus  = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];

		$query  = "	SELECT kpl, varattu, tuoteno
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}'";
		$result = pupe_query($query);
		$rivi	= mysql_fetch_assoc($result);

		// jos tätä riviä ei ole vielä viety varastoon ni päivitetään varattu kenttää
		if ($rivi["kpl"] == 0 and $rivi["varattu"] != 0 and $kpl != 0) {
			$query = "	UPDATE tilausrivi
						set varattu = '{$kpl}'
						where yhtio = '{$kukarow['yhtio']}'
						and tunnus = '{$tunnus}'
						and varattu <> '{$kpl}'";
			$result = pupe_query($query);
		}

		if ($rivi["kpl"] == 0 and strtoupper($kpl) == "DEL") {
			$query = "	DELETE FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						and tunnus = '{$tunnus}'
						and tyyppi = 'O'";
			$result = pupe_query($query);

			//Nollataan sarjanumero
			if ($rivi["varattu"] > 0) {
				$tunken = "ostorivitunnus";
			}
			else {
				$tunken = "myyntirivitunnus";
			}

			$query = "	UPDATE sarjanumeroseuranta
						SET {$tunken} = 0
						WHERE yhtio = '{$kukarow['yhtio']}'
						and tuoteno = '{$rivi['tuoteno']}'
						and {$tunken} = '{$tunnus}'";
			$sarjares = pupe_query($query);
		}

		if ($kpl == 0 and $yhtiorow['suuntalavat'] == 'S') {
			$query = "UPDATE tilausrivi SET netto = 'X' WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}'";
			$result = pupe_query($query);
		}

		$hinnat = laske_hinta($laskurow, $tunnus);

		$data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta'], 'ulkkpl' => (float) $hinnat['ulkkpl']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_kpl_splittaus') {
		$tunnus = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];
		$uuden_rivin_kpl = $_POST['uuden_rivin_kpl'];

		$query = "	SELECT tilausrivi.*, GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno ORDER BY toim_tuoteno SEPARATOR ',') toim_tuoteno
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
					LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.tunnus = '{$tunnus}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) return false;

		$rivi = mysql_fetch_array($result);

		mysql_data_seek($result, 0);

		$query = "INSERT INTO tilausrivi SET ";

		for ($i = 0; $i < mysql_num_fields($result) - 1; $i++) {
			switch (mysql_field_name($result,$i)) {
				case 'varattu':
					$query .= " varattu = '{$uuden_rivin_kpl}',";
					break;
				case 'netto':

					if ($yhtiorow['suuntalavat'] == 'S') {
						$query .= " netto = 'X',";
					}
					else {
						$query .= " netto = '".$rivi[mysql_field_name($result, $i)]."',";
					}

					break;
				case 'laatija':
					$query .= " laatija = '{$kukarow['kuka']}',";
					break;
				case 'laadittu':
					$query .= " laadittu = now(),";
					break;
				case 'tunnus':
					break;
				default:
					$query .= mysql_field_name($result, $i)." = '".$rivi[mysql_field_name($result, $i)]."',";
			}
		}

		$query = substr($query, 0, -1);
		$result = pupe_query($query);

		$uuden_rivin_id = mysql_insert_id();

		$hinnat = laske_hinta($laskurow, $uuden_rivin_id);

		$rivi['rivihinta'] = $hinnat['rivihinta'];
		$rivi['alerivihinta'] = $hinnat['alerivihinta'];
		$rivi['tunnus'] = $uuden_rivin_id;
		$rivi['varattu'] = $uuden_rivin_kpl;
		$rivi['toimaika'] = tv1dateconv($rivi['toimaika']);
		$rivi['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='$rivi[yksikko]'", "", "", "selite");

		echo json_encode($rivi);
	}

	exit;