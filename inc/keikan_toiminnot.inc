<?php

	function laske_hinta($otunnus, $tunnus, $suoratoimitukset = '') {
		global $kukarow, $yhtiorow;

		// haetaan keikkaotsikon tiedot
		$query    = "SELECT * FROM lasku WHERE tunnus = '{$otunnus}' AND yhtio = '{$kukarow['yhtio']}'";
		$result   = pupe_query($query);
		$laskurow = mysql_fetch_array($result);

		// katsotaan onko t‰lle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
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
			$kulurow = array(); // muuten tyhj‰‰
		}

		// jos ollaan liitetty jo vaihto-omaisuuslasku, k‰ytet‰‰n sen vientikentt‰‰
		if ($kulurow["vienti"] != "") $laskurow["vienti"] = $kulurow["vienti"];

		$alvti = " if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) ";

		// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
		if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

			$simualv = round(100 * (($kulurow["summa"]/$kulurow["arvo"])-1),2);

			if (in_array($simualv, array(8,12,17,22))) {
				$alvti = $simualv;
				$alvit = $simualv;
			}
			else {
				$alvit = "if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv)";
			}

			if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
				// tutkitaan ollaanko siell‰ alv-rekisterˆity
				$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='{$kukarow['yhtio']}' and maa='{$laskurow['maa']}' and vat_numero != ''";
				$alhire = pupe_query($alhqur);

				// ollaan alv-rekisterˆity
				if (mysql_num_rows($alhire) == 1) {
					$alvit = "tuotteen_alv.alv";
					$alvti = " ifnull(tuotteen_alv.alv, 0) ";
				}
			}
		}
		else {
			$alvit = 0;
		}

		if ($suoratoimitukset == "vaan") {
			$tilaajanrivinro = " and tilausrivi.tilaajanrivinro > 0 ";
		}
		else {
			$tilaajanrivinro = '';
		}

		$lisa2 = " and tilausrivi.tunnus = '{$tunnus}' ";

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
					FROM lasku
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O' {$tilaajanrivinro} {$lisa2}
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
					LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '{$laskurow['maa']}')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.liitostunnus = '{$laskurow['liitostunnus']}'
					and lasku.tila in ('O','K','D')
					GROUP BY tilausrivi.tunnus
					ORDER BY tilausrivi.perheid2, tilausrivi.tunnus";
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

			echo json_encode('ok');
		}
	}
	elseif ($_POST['ajax_toiminto'] == 'kohdista_rivi') {
		list($valittutunnus, $valittuotunnus) = explode("###", $_POST['valittu']);

		$otunnus = $_POST['otunnus'];

		$suuntalava_querylisa = '';

		if ($yhtiorow['suuntalavat'] == 'S') {
			$suuntalavan_tunnus = $_POST['suuntalavan_tunnus'];

			if ($suuntalavan_tunnus > 0) {
				$suuntalava_querylisa = ", suuntalava = $suuntalavan_tunnus ";
			}
			else {
				// toistaiseksi ei p‰ivitet‰ suuntalavaa tilausriville kohdistuksen yhteydess‰ automaattisesti
				// $suuntalava = hae_suuntalava($otunnus, $valittutunnus);
				// $suuntalava_querylisa = ", suuntalava = $suuntalava ";
			}
		}

		$updatelisa = $otunnus == 0 ? ", suuntalava = 0, kerattyaika = '0000-00-00 00:00:00', keratty = '' " : "$suuntalava_querylisa , kerattyaika = now(), keratty = '{$kukarow['kuka']}' ";

		$query = "	UPDATE tilausrivi use index (yhtio_otunnus) SET 
					uusiotunnus = '{$otunnus}' 
					$updatelisa
					WHERE yhtio = '{$kukarow['yhtio']}' 
					AND otunnus IN ('{$valittuotunnus}','{$otunnus}') 
					AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
		$result = pupe_query($query);

		// Tuotteet p‰ivitet‰‰n kohdistuksissa automaattisesti oikeille suuntalavoille, mik‰li sellainen on jo perustettu (suuntalavan m‰‰riteltyjen varastopaikkojen sek‰ ker‰ysvyˆhykkeen mukaan)
		if ($yhtiorow['suuntalavat'] == 'S' and $otunnus > 0) {
			if ($suuntalavan_tunnus > 0) {
				$data = array('suuntalava' => $suuntalavan_tunnus);
				echo json_encode($data);
			}
			else {
				// toistaiseksi ei setata suuntalavaa dropdowniin kohdistuksen yhteydess‰ automaattisesti
				// $data = array('suuntalava' => $suuntalava);
				$data = array('suuntalava' => 0);
				echo json_encode($data);
			}
		}
	}
	elseif ($_POST['ajax_toiminto'] == 'kohdista_koko_sivu' or $_POST['ajax_toiminto'] == 'ala_kohdista_mitaan') {

		if ($_POST['ajax_toiminto'] == 'ala_kohdista_mitaan') {
			$tunnus = $_POST['tunnus'];

			$query = "	UPDATE tilausrivi USE INDEX (PRIMARY) SET 
						uusiotunnus = 0, 
						kerattyaika = '0000-00-00 00:00:00', 
						suuntalava = 0,
						keratty = '' 
						WHERE tunnus = {$tunnus} 
						AND yhtio = '{$kukarow['yhtio']}'";
			$result = pupe_query($query);
		}
		else if ($_POST['ajax_toiminto'] == 'kohdista_koko_sivu') {
			$otunnus = $_POST['otunnus'];

			list($valittutunnus, $valittuotunnus) = explode("###", $_POST['valittu']);

			$query = "	UPDATE tilausrivi USE INDEX (yhtio_otunnus) SET 
						uusiotunnus = '{$otunnus}', 
						kerattyaika = now(), 
						keratty = '{$kukarow['kuka']}'
						WHERE yhtio = '{$kukarow['yhtio']}' 
						AND otunnus IN ('{$valittuotunnus}','{$otunnus}') 
						AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
			$result = pupe_query($query);
		}
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_ale') {
		$ale  = str_replace(",",".", $_POST['ale']);
		$tunnus = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];
		$suoratoimitukset = $_POST['suoratoimitukset'];

		$query  = "	UPDATE tilausrivi SET 
					ale1 = '{$ale}' 
					WHERE yhtio = '{$kukarow['yhtio']}' 
					AND tunnus = '{$tunnus}' 
					AND ale1 <> '{$ale}'";
		$result = pupe_query($query);

		$hinnat = laske_hinta($otunnus, $tunnus, $suoratoimitukset);

		$data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_hinta') {

		// otetaan muuttujiin talteen tarpeellisia tietoja
		$hinta  = str_replace(",",".", $_POST['hinta']);
		$tunnus = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];
		$suoratoimitukset = $_POST['suoratoimitukset'];

		// p‰ivitet‰‰n haluttu hinta tilausriville
		$query  = "UPDATE tilausrivi set hinta='{$hinta}' where yhtio='{$kukarow['yhtio']}' and tunnus='{$tunnus}' and hinta<>'{$hinta}'";
		$result = pupe_query($query);

		$hinnat = laske_hinta($otunnus, $tunnus, $suoratoimitukset);

		$data = array('hinta' => $hinta, 'rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_kpl') {
		$kpl = str_replace(",",".", $_POST['kpl']);
		$tunnus = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];
		$suoratoimitukset = $_POST['suoratoimitukset'];

		$query  = "	SELECT kpl, varattu, tuoteno
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}'";
		$result = pupe_query($query);
		$rivi	= mysql_fetch_assoc($result);
	
		// jos t‰t‰ rivi‰ ei ole viel‰ viety varastoon ni p‰ivitet‰‰n varattu kentt‰‰
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

		$hinnat = laske_hinta($otunnus, $tunnus, $suoratoimitukset);

		$data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta'], 'ulkkpl' => (float) $hinnat['ulkkpl']);

		echo json_encode($data);
	}
	elseif ($_POST['ajax_toiminto'] == 'rivi_kpl_splittaus') {
		$tunnus = $_POST['tunnus'];
		$otunnus = $_POST['otunnus'];
		$suoratoimitukset = $_POST['suoratoimitukset'];
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

		$hinnat = laske_hinta($otunnus, $uuden_rivin_id, $suoratoimitukset);

		$rivi['rivihinta'] = $hinnat['rivihinta'];
		$rivi['alerivihinta'] = $hinnat['alerivihinta'];
		$rivi['tunnus'] = $uuden_rivin_id;
		$rivi['varattu'] = $uuden_rivin_kpl;
		$rivi['toimaika'] = tv1dateconv($rivi['toimaika']);
		$rivi['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite");

		echo json_encode($rivi);
	}

	exit;