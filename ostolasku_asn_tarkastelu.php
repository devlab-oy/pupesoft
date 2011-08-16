<?php

	require("inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript'>
				$(document).ready(function() {
					$('.kollibutton').click(function(){
						var kollitunniste = $(this).attr('id');
						$('#kolli').val(kollitunniste);
						$('#formi').submit();
					});

					$('.ostolaskubutton').click(function(){
						var lasku = $(this).attr('id');
						$('#lasku').val(lasku);
						$('#formi').submit();
					});

					$('.toimittajabutton').click(function(){
						var asn = $(this).attr('id');
						$('#tee').val('vaihdatoimittaja');
						$('#formi').attr('action', '?asn_numero='+asn+'&lopetus={$PHP_SELF}////tee=').submit();
					});

					$('.etsibutton').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#asn_rivi').val(rivitunniste);
						$('#kolliformi').submit();
					});

					$('.vahvistabutton').click(function(){
						$('#tee').val('vahvistakolli');
						$('#kolliformi').attr('action', '?').submit();
					});

					$('#kohdista_tilausrivi_formi').submit(function(){
						var kohdistettu = false;

						$('.tunnukset').each(function(){
							if ($(this).is(':checked')) {
								kohdistettu = true;
							}
						});

						if (kohdistettu) {
							var lopetus = $('#lopetus').val();
							lopetukset = lopetus.split('/SPLIT/');

							$('#lopetus').val(lopetukset[0]);
						}
					});
				});
			</script>";

	echo "<font class='head'>",t("Ostolasku / ASN-sanomien tarkastelu"),"</font><hr><br />";

	if (!isset($tee)) $tee = '';
	if (!isset($valitse)) $valitse = '';

	if ($tee == 'vaihdatoimittaja') {

		$tila = '';

		if (isset($nimi) and trim($nimi) != '') {

			//tehd‰‰n asiakas- ja toimittajahaku yhteensopivuus
			$ytunnus = $nimi;

			$lause = "<font class='head'>".t("Valitse toimittaja").":</font><hr><br>";
			require ("inc/kevyt_toimittajahaku.inc");

			if ($ytunnus == '' and $monta > 1) {
				//Lˆytyi monta sopivaa, n‰ytet‰‰n formi, mutta ei otsikkoa
				$tila = 'monta';
			}
			elseif ($ytunnus == '' and $monta < 1) {
				//yht‰‰n asiakasta ei lˆytynyt, n‰ytet‰‰n otsikko
				$tila = '';
			}
			else {
				//oikea asiakas on lˆytynyt
				$tunnus = $toimittajaid;
				$tila = 'ok';
			}
		}

		if (isset($toimittajaid) and trim($toimittajaid) != '') {

			$toimittajaid = (int) $toimittajaid;
			$asn_numero = (int) $muutparametrit;

			$query = "SELECT toimittajanro FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$toimittajaid}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND asn_numero = '{$asn_numero}'";
			$res = pupe_query($query);

			$tee = '';
			$tila = 'ok';
		}

		if ($tila == '') {
			echo "<form method='post' action='?tee={$tee}&muutparametrit={$asn_numero}'>";
			echo "<table>";
			echo "<tr><th>",t("Etsi toimittajaa")," (",t("nimi")," / ",t("ytunnus"),")</th><td><input type='text' name='nimi' value='' />&nbsp;<input type='submit' value='",t("Etsi"),"' /></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'vahvistakolli') {

		$kolli = mysql_real_escape_string($kolli);

		$paketin_rivit = array();
		$paketin_tunnukset = array();

		$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND paketintunniste = '{$kolli}'";
		$kollires = pupe_query($query);

		while ($kollirow = mysql_fetch_assoc($kollires)) {
			$toimittaja = $kollirow['toimittajanumero'];
			$asn_numero = $kollirow['asn_numero'];
			$paketin_tunnukset[] = $kollirow['tunnus'];

			if (strpos($kollirow['tilausrivi'], ",") !== false) {

				foreach (explode(",", $kollirow['tilausrivi']) as $tun) {
					$paketin_rivit[] = $tun;
				}
			}
			else {
				$paketin_rivit[] = $kollirow['tilausrivi'];
			}
		}

		$query = "SELECT GROUP_CONCAT(tuoteno) AS tuotenumerot FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN (".implode(",", $paketin_rivit).")";
		$tuotenores = pupe_query($query);
		$tuotenorow = mysql_fetch_assoc($tuotenores);

		$paketin_tuotteet = explode(",", $tuotenorow['tuotenumerot']);

		require('inc/asn_kohdistus.inc');

		asn_kohdista_suuntalava($toimittaja, $asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset);

		$tee = '';
	}

	if ($tee == 'uusirivi') {

		echo t("Tee uusi rivi").":<br>";

		$var = 'O';

		if (trim($tilausnro) == '' or $tilausnro == 0) {

			$asn_rivi = (int) $asn_rivi;

			$query = "SELECT tilausnumero FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$tilausnro = $row['tilausnumero'];
		}

		//pidet‰‰n kaikki muuttujat tallessa
		$muut_siirrettavat = $asn_rivi."!°!".$toimittaja."!°!".$tilausnro."!°!".$tuoteno."!°!".$tilaajanrivinro."!°!".$kpl;

		$rivinotunnus = $tilausnro;
		$toimaika = date('Y')."-".date('m')."-".date('d');

		require('tilauskasittely/syotarivi_ostotilaus.inc');
		require('inc/footer.inc');
		exit;

	}

	//poistetaan rivi, n‰ytet‰‰n lista
	if ($tee == 'TI' and isset($tyhjenna)) {
		$tee = 'etsi_tilausrivi';
	}

	//tarkastetaan tilausrivi
	if ($tee == 'TI') {
		// Parametreja joita tarkistarivi tarvitsee
		$laskurow["tila"] 	= "O";
		$toim_tarkistus 	= "EI";
		$prow["tuoteno"] 	= $tuoteno;
		$prow["var"]		= $rivinvar;
		$kukarow["kesken"]	= $rivinotunnus;

		$kpl 	= str_replace(',','.',$kpl);
		$hinta 	= str_replace(',','.',$hinta);

		for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
			${'ale'.$alepostfix} = str_replace(',','.', ${'ale'.$alepostfix});
		}

		if (checkdate($toimkka,$toimppa,$toimvva)) {
			$toimaika = $toimvva."-".$toimkka."-".$toimppa;
		}

		if ($hinta == "") {

			$toimittaja = (int) $toimittaja;

			$query = "	SELECT tunnus
						FROM toimi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND toimittajanro = '{$toimittaja}'";
			$toimires = pupe_query($query);
			$toimirow = mysql_fetch_assoc($toimires);

			$laskurow['liitostunnus'] = $toimirow['tunnus'];

			$query = "	SELECT *
						FROM tuotteen_toimittajat
						WHERE tuoteno = '{$prow['tuoteno']}'
						AND yhtio = '{$kukarow['yhtio']}'
						AND liitostunnus = '{$laskurow['liitostunnus']}'";
			$rarres1 = pupe_query($query);
			$hinrow1 = mysql_fetch_assoc($rarres1);

			$prow["hinta"] = $hinrow1["ostohinta"];
		}
		else {
			$prow["hinta"] = $hinta;
		}

		$multi = "";
		require("inc/tuotehaku.inc");
		$prow["tuoteno"] = $tuoteno;

		require('tilauskasittely/tarkistarivi_ostotilaus.inc');

		//n‰ytet‰‰n virhe ja annetaan mahis korjata se
		if (trim($varaosavirhe) != '' or trim($tuoteno) == "") {

			//rivien splittausvaihtoehtot n‰kyviin
			$automatiikka = 'ON';

			echo t("Muuta rivi‰"),":<br>";
			require('tilauskasittely/syotarivi_ostotilaus.inc');
			require('inc/footer.inc');
			exit;
		}

	}

	//rivi on tarkistettu ja se lisataan tietokantaan
	if ((isset($varaosavirhe) and trim($varaosavirhe) == '') and ($tee == "TI") and trim($tuoteno) != '') {

		$laskurow["tila"] = "O";
		$kukarow["kesken"] = $rivinotunnus;

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
		for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
			${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
		}

		$kayttajan_hinta	= $hinta;
		$kayttajan_netto 	= $netto;
		$kayttajan_var		= $var;
		$kayttajan_kpl		= $kpl;
		$kayttajan_alv		= $alv;

		foreach ($tuoteno_array as $tuoteno) {

			$query	= "	SELECT *
						FROM tuote
						WHERE tuoteno = '{$tuoteno}'
						and yhtio = '{$kukarow['yhtio']}'
						and ei_saldoa = ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				//Tuote lˆytyi
				$trow = mysql_fetch_array($result);
			}
			else {
				//Tuotetta ei lˆydy, arvataan muutamia muuttujia
				$trow["alv"] = $laskurow["alv"];
			}

			if (checkdate($toimkka,$toimppa,$toimvva)) {
				$toimaika = $toimvva."-".$toimkka."-".$toimppa;
			}
			if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
				$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
			}
			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}
			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}

			$varasto = $laskurow["varasto"];

			//Tehd‰‰n muuttujaswitchit
			if (is_array($hinta_array)) {
				$hinta = $hinta_array[$tuoteno];
			}
			else {
				$hinta = $kayttajan_hinta;
			}

			for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
				if (is_array(${'ale_array'.$alepostfix})) {
					${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
				}
				else {
					${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
				}
			}

			if (is_array($netto_array)) {
				$netto = $netto_array[$tuoteno];
			}
			else {
				$netto = $kayttajan_netto;
			}

			if (is_array($var_array)) {
				$var = $var_array[$tuoteno];
			}
			else {
				$var = $kayttajan_var;
			}

			if (is_array($kpl_array)) {
				$kpl = $kpl_array[$tuoteno];
			}
			else {
				$kpl = $kayttajan_kpl;
			}

			if (is_array($alv_array)) {
				$alv = $alv_array[$tuoteno];
			}
			else {
				$alv = $kayttajan_alv;
			}

			if ($kpl != 0) {
				require ('tilauskasittely/lisaarivi.inc');
			}

			$hinta 	= '';
			$netto 	= '';
			$var 	= '';
			$kpl 	= '';
			$alv 	= '';
			$paikka	= '';

			for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}

		if ($lisavarusteita == "ON" and $perheid2 > 0) {
			//P‰ivitet‰‰n is‰lle perheid jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
			$query = "	UPDATE tilausrivi set
						perheid2	= '{$perheid2}'
						where yhtio = '{$kukarow['yhtio']}'
						and tunnus 	= '{$perheid2}'";
			$updres = pupe_query($query);
		}

		$tee = 'etsi_tilausrivi';
	}

	//korjataan siirrett‰v‰t muuttujat taas talteen
	if (isset($muut_siirrettavat)) {
		list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl) = explode("!°!", $muut_siirrettavat);
	}

	if ($tee == 'kohdista_tilausrivi') {

		if (count($tunnukset) == 0) {
			$error = t("Halusit kohdistaa, mutta et valinnut yht‰‰n rivi‰")."!";
			$tee = 'etsi_tilausrivi';
		}
		else {
			// typecastataan formista tulleet tunnukset stringeist‰ inteiksi
			$tunnukset = array_map('intval', $tunnukset);

			$query = "UPDATE asn_sanomat SET tilausrivi = '".implode(",", $tunnukset)."' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$updres = pupe_query($query);

			$query = "SELECT paketintunniste FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$res = pupe_query($query);
			$row = mysql_fetch_assoc($res);

			$kolli = $row['paketintunniste'];
			$tee = 'nayta';
		}
	}

	if ($tee == 'etsi_tilausrivi') {
		if (isset($asn_rivi) and strpos($asn_rivi, '##') !== false) {
			list($asn_rivi, $tuoteno, $tilaajanrivinro) = explode('##', $asn_rivi);

			$asn_rivi = (int) $asn_rivi;

			$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$result = pupe_query($query);
			$asn_row = mysql_fetch_assoc($result);
		}

		if (!isset($toimittaja) and isset($asn_row)) $toimittaja = $asn_row['toimittajanumero'];
		if (!isset($tilausnro) and isset($asn_row)) $tilausnro = $asn_row['tilausnumero'];
		if (!isset($kpl) and isset($asn_row)) $kpl = $asn_row['kappalemaara'];

		echo "<form method='post' action='?tee=etsi_tilausrivi&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&lopetus={$lopetus}'>";

		echo "<table>";
		echo "<tr><th colspan='6'>",t("Etsi tilausrivi"),"</th></tr>";

		echo "<tr>";
		echo "<th>",t("Toimittaja"),"</th>";
		echo "<th>",t("Tilausnro"),"</th>";
		echo "<th>",t("Tuotenro"),"</th>";
		echo "<th>",t("Tilaajan rivinro"),"</th>";
		echo "<th>",t("Kpl"),"</th>";
		echo "<th>&nbsp;</th>";
		echo "</tr>";

		echo "<tr>";
		// echo "<td><input type='text' name='toimittaja' value='{$toimittaja}' /></td>";
		echo "<td>{$toimittaja}</td>";
		echo "<td><input type='text' name='tilausnro' value='{$tilausnro}' /></td>";
		echo "<td><input type='text' name='tuoteno' value='{$tuoteno}' /></td>";
		echo "<td><input type='text' name='tilaajanrivinro' value='{$tilaajanrivinro}' /></td>";
		echo "<td><input type='text' name='kpl' value='{$kpl}' /></td>";
		echo "<td><input type='submit' value='",t("Etsi"),"' /></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";

		echo "<br /><hr /><br />";

		echo "<form method='post' action='?tee=uusirivi&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}&lopetus={$lopetus}'>";
		echo "<input type='submit' value='",t("Tee uusi tilausrivi"),"' />";
		echo "</form>";
		echo "<br />";

		if (trim($toimittaja) != '' or trim($tilausnro) != '' or trim($tuoteno) != '' or trim($tilaajanrivinro) != '' or trim($kpl) != '') {
			echo "<br /><hr /><br />";

			if (isset($error)) echo "<font class='error'>{$error}</font><br /><br />";

			echo "<form method='post' id='kohdista_tilausrivi_formi' action='?tee=kohdista_tilausrivi&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}'>";
			echo "<input type='hidden' name='lopetus' id='lopetus' value='{$lopetus}' />";

			echo "<table>";
			echo "<tr><th colspan='5'>",t("Haun tulokset"),"</th><th><input type='submit' value='",t("Kohdista"),"' /></th></tr>";
			echo "<tr>";
			echo "<th>",t("Tilausnro"),"</th>";
			echo "<th>",t("Tuoteno"),"</th>";
			echo "<th>",t("Varattu")," / ",t("Kpl"),"</th>";
			echo "<th>",t("Keikka"),"</th>";
			echo "<th>",t("Keikan tila"),"</th>";
			echo "<th>",t("Kohdistus"),"</th>";
			echo "</tr>";

			$toimittaja = (int) $toimittaja;

			$query = "SELECT tunnus FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND toimittajanro = '{$toimittaja}'";
			$toimires = pupe_query($query);
			$toimirow = mysql_fetch_assoc($toimires);

			$tilaajanrivinrolisa = trim($tilaajanrivinro) != '' ? " and tilausrivi.tilaajanrivinro = ".(int) $tilaajanrivinro : '';
			$tilausnrolisa = trim($tilausnro) != '' ? " and tilausrivi.otunnus = ".(int) $tilausnro : '';
			$tuotenolisa = trim($tuoteno) != '' ? " and tuoteno = '".mysql_real_escape_string($tuoteno)."'" : '';
			$kpllisa = trim($kpl) != '' ? " and varattu = ".(float) $kpl : '';

			// $query = "	SELECT *, if(tilausrivi.uusiotunnus = 0, '', tilausrivi.uusiotunnus) AS uusiotunnus
			// 			FROM tilausrivi
			// 			#JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tila IN ('O', 'K') AND lasku.liitostunnus = '{$toimirow['tunnus']}')
			// 			WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
			// 			AND tilausrivi.tyyppi = 'O'
			// 			#AND uusiotunnus = 0
			// 			{$tilaajanrivinrolisa}
			// 			{$tilausnrolisa}
			// 			{$tuotenolisa}
			// 			{$kpllisa}";

			$query = "	SELECT tilausrivi.*, if(tilausrivi.uusiotunnus = 0, '', tilausrivi.uusiotunnus) AS uusiotunnus
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'O' AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.kpl = 0 {$tilaajanrivinrolisa}{$tilausnrolisa}{$tuotenolisa}{$kpllisa})
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila IN ('O', 'K')
						AND lasku.alatila != 'X'
						AND lasku.liitostunnus = '{$toimirow['tunnus']}'
						ORDER BY tilausrivi.uusiotunnus, lasku.tunnus";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {

				// $query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['otunnus']}'";
				// $chkres = pupe_query($query);
				// $chkrow = mysql_fetch_assoc($chkres);
				// 
				// if ($chkrow['liitostunnus'] != $toimirow['tunnus']) continue;

				echo "<tr>";
				echo "<td align='right'>{$row['otunnus']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td align='right'>{$row['varattu']} / {$row['kpl']}</td>";

				if ($row['uusiotunnus'] != '') {
					$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['uusiotunnus']}'";
					$keikkares = pupe_query($query);
					$keikkarow = mysql_fetch_assoc($keikkares);
					$row['uusiotunnus'] = $keikkarow['laskunro'];
				}

				echo "<td align='right'>$row[uusiotunnus]</td>";

				if ($row['uusiotunnus'] != '' and $row['kpl'] == 0) {
					echo "<td>",t("Rivi on kohdistettu"),"</td>";
					echo "<td>&nbsp;</td>";
				}
				elseif ($row['uusiotunnus'] != '' and $row['kpl'] != 0) {
					echo "<td>",t("Viety varastoon"),"</td>";
					echo "<td>&nbsp;</td>";
				}
				else {
					echo "<td>&nbsp;</td>";
					echo "<td align='center'><input type='checkbox' name='tunnukset[]' class='tunnukset' value='{$row['tunnus']}' /></td>";
				}

				echo "</tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'nayta') {

		if ($valitse == 'asn') {
			$kolli = mysql_real_escape_string($kolli);

			$query = "	SELECT asn_sanomat.toimittajanumero, asn_sanomat.toim_tuoteno, asn_sanomat.tilausrivinpositio, asn_sanomat.kappalemaara, asn_sanomat.status, asn_sanomat.tilausnumero,
						tilausrivi.tuoteno, if(tilausrivi.ale1 = 0, '', tilausrivi.ale1) AS alennus
						#tuotteen_toimittajat.tuoteno#, tuote.nimitys#, tilausrivi.tilaajanrivinro
						FROM asn_sanomat
						JOIN tilausrivi ON (tilausrivi.yhtio = asn_sanomat.yhtio AND tilausrivi.tunnus IN (asn_sanomat.tilausrivi))
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
						#JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = asn_sanomat.yhtio AND tuotteen_toimittajat.toim_tuoteno = asn_sanomat.toim_tuoteno AND tuotteen_toimittajat.liitostunnus = toimi.tunnus)
						#JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						AND asn_sanomat.tilausrivi != ''
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			#echo "<pre>",str_replace("\t","",$query),"</pre>";
			$result = pupe_query($query);

			echo "<form method='post' action='?lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//kolli={$kolli}' id='kolliformi'>";
			echo "<input type='hidden' id='tee' name='tee' value='etsi_tilausrivi' />";
			echo "<input type='hidden' id='kolli' name='kolli' value='{$kolli}' />";
			echo "<input type='hidden' id='asn_rivi' name='asn_rivi' value='' />";

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Ostotilausnro"),"</th>";
			echo "<th>",t("Tuotenro"),"</th>";
			echo "<th>",t("Toimittajan"),"<br />",t("Tuotenro"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Rivinro"),"</th>";
			echo "<th>",t("Kpl"),"</th>";
			echo "<th>",t("Alennukset"),"</th>";
			echo "<th>",t("Status"),"</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			$ok = 0;

			while ($row = mysql_fetch_assoc($result)) {

				$ok++;

				echo "<tr>";

				$query = "SELECT nimitys FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$row['tuoteno']}'";
				$tuoteres = pupe_query($query);
				$tuoterow = mysql_fetch_assoc($tuoteres);

				$row['nimitys'] = $tuoterow['nimitys'];

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilausrivinpositio']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";
				echo "<td>{$row['alennus']}</td>";

				echo "<td><font class='ok'>",t("Ok"),"</font></td>";

				echo "<td></td>";

				echo "</tr>";
			}

			$virhe = 0;

			$query = "	SELECT asn_sanomat.toimittajanumero, asn_sanomat.toim_tuoteno, asn_sanomat.tilausrivinpositio, asn_sanomat.kappalemaara, asn_sanomat.status, asn_sanomat.tilausnumero,
						toimi.tunnus AS toimi_tunnus, asn_sanomat.tunnus AS asn_tunnus
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						#AND asn_sanomat.status = ''
						AND asn_sanomat.tilausrivi = ''
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {

				$virhe++;

				echo "<tr>";

				$query = "SELECT tuoteno FROM tuotteen_toimittajat WHERE yhtio = '{$kukarow['yhtio']}' AND toim_tuoteno = '{$row['toim_tuoteno']}' AND liitostunnus = '{$row['toimi_tunnus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) > 0) {
					$ttrow = mysql_fetch_assoc($res);

					$row['tuoteno'] = $ttrow['tuoteno'];

					$query = "	SELECT nimitys 
								FROM tuote 
								WHERE yhtio = '{$kukarow['yhtio']}' 
								AND tuoteno = '{$ttrow['tuoteno']}'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_assoc($tres);

					$row['nimitys'] = $trow['nimitys'];

					$query = "SELECT nimitys, uusiotunnus, tilaajanrivinro FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$row['tilausnumero']}' AND tuoteno = '{$row['tuoteno']}' AND tyyppi = 'O'";
					$tilres = pupe_query($query);

					if (mysql_num_rows($tilres) > 0) {
						$tilrow = mysql_fetch_assoc($tilres);
						$row['nimitys'] = $tilrow['nimitys'];
						$row['uusiotunnus'] = $tilrow['uusiotunnus'];
						$row['tilaajanrivinro'] = $tilrow['tilaajanrivinro'];
					}
					else {
						$row['uusiotunnus'] = 0;
						$row['tilaajanrivinro'] = '';
					}
				}
				else {
					$row['tuoteno'] = '';
					$row['nimitys'] = t("Tuntematon tuote");
				}

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilausrivinpositio']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";
				echo "<td></td>";

				echo "<td><font class='error'>",t("Virhe"),"</font></td>";

				echo "<td>";
				if ($row['uusiotunnus'] == 0) echo "<input type='button' class='etsibutton' id='{$row['asn_tunnus']}##{$row['tuoteno']}##{$row['tilaajanrivinro']}' value='",t("Etsi"),"' />";
				echo "</td>";

				echo "</tr>";
			}

			if ($ok and !$virhe) {
				echo "<tr><th colspan='11'><input type='button' class='vahvistabutton' value='",t("Vahvista"),"' /></th></tr>";
			}

			echo "</table>";
			echo "</form>";
		}
		else {
			echo "lasku: $lasku<br>";

			$lasku = (int) $lasku;

			$query = "	SELECT data
						FROM liitetiedostot
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND liitos = 'lasku'
						AND liitostunnus = '{$lasku}'";
			$result = pupe_query($query);

			$row = mysql_fetch_assoc($result);
			$xml = simplexml_load_string($row['data']);

			$laskuttajan_toimittajanumero	= utf8_decode($xml->InvoiceHeader->SellerParty->PartyNumber);

			if (strtoupper($laskuttajan_toimittajanumero) == "ELRING") {
				$laskuttajan_toimittajanumero = "123312";
			}
			elseif (strtoupper($laskuttajan_toimittajanumero) == "BOSCH") {
				$laskuttajan_toimittajanumero = "123067";
			}
			elseif (strtoupper($laskuttajan_toimittajanumero) == "NISSENS") {
				$laskuttajan_toimittajanumero = "123403";
			}
			elseif ($laskuttajan_toimittajanumero == "112") {
				$laskuttajan_toimittajanumero = "123442";
			}

			$i				= 0;
			$rtuoteno		= array();
			$ealvi		 	= array();
			$aputuoteno		= "";
			$pituus			= "";

			if (count($xml->InvoiceDetail) > 0) {

				foreach ($xml->InvoiceDetail as $tuotetieto) {

					// tuotekoodi
					// Haetaan poikkeustapaksien k‰sittely
					if ($laskuttajan_toimittajanumero == 123085) {
						$rtuoteno[$i]["toim_tuoteno"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);
					}
					elseif ($laskuttajan_toimittajanumero == 123067) {
						$rtuoteno[$i]["toim_tuoteno"] = substr(utf8_decode($tuotetieto->ProductId->ProductNumber),0,10);
					}
					elseif ($laskuttajan_toimittajanumero == 123007) {
						$aputuoteno = utf8_decode($tuotetieto->ProductId->ProductNumber);
						$pituus 	= strlen(trim($aputuoteno));

						if ($pituus == 8 and strpos(' ',$aputuoteno) == 6) {
							$rtuoteno[$i]["toim_tuoteno"] = substr($aputuoteno,0,5);
						}
						else {
							$rtuoteno[$i]["toim_tuoteno"] = $aputuoteno;
						}
					}
					else { 
						$rtuoteno[$i]["toim_tuoteno"] = utf8_decode($tuotetieto->ProductId->ProductNumber);
					}

					$rtuoteno[$i]["ostotilausnro"] = (string) $tuotetieto->OrderRef->BuyerOrderNumber; // mihin t‰t‰ tarvitiin ?

					$rtuoteno[$i]["tilaajanrivinro"] = (string) $tuotetieto->PositionNumber;

					if ($rtuoteno[$i]["toim_tuoteno"] != '') {

						$rtuoteno[$i]["kpl"] = (float) str_replace(",", ".", $tuotetieto->Quantity);

						if (in_array($laskuttajan_toimittajanumero, array("123007","123442","123001"))) {
							$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalValue->Amount); // summa
						}
						else {
							$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalPrice->Amount); // summa
						}

						// jos rivihinta lˆytyy, niin k‰ytet‰‰n sit‰, muussa tapauksessa lasketaan
						if (isset($tuotetieto->UnitPrice->Amount) and $tuotetieto->UnitPrice->Amount != '') {
							$rtuoteno[$i]["hinta"] = (float) str_replace(",", ".", $tuotetieto->UnitPrice->Amount);
						}
						else {
							$rtuoteno[$i]["hinta"] = $rtuoteno[$i]["rivihinta"]  / $rtuoteno[$i]["kpl"] ; 
						}

						$rtuoteno[$i]["nimitys"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);

					}
					else {
						//	Poistetaan t‰m‰ tietue
						unset($rtuoteno[$i]);
						$i--;
					}

					$i++;
				}
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Ostotilausnro"),"</th>";
			echo "<th>",t("Tuotenro"),"</th>";
			echo "<th>",t("Toimittajan"),"<br />",t("Tuotenro"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Rivinro"),"</th>";
			echo "<th>",t("Kpl"),"</th>";
			echo "<th>",t("Hinta"),"</th>";
			#echo "<th>",t("Alennukset"),"</th>";
			echo "<th>",t("Status"),"</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			$query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$lasku}'";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);

			foreach ($rtuoteno as $tuote) {

				$query = "SELECT tuoteno FROM tuotteen_toimittajat WHERE yhtio = '{$kukarow['yhtio']}' AND toim_tuoteno = '{$tuote['toim_tuoteno']}' AND liitostunnus = '{$laskurow['liitostunnus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) > 0) {
					$ttrow = mysql_fetch_assoc($res);

					$tuote['tuoteno'] = $ttrow['tuoteno'];
				}

				echo "<tr>";
				echo "<td align='right'>{$laskuttajan_toimittajanumero}</td>";
				echo "<td align='right'>{$tuote['ostotilausnro']}</td>";
				echo "<td>{$tuote['tuoteno']}</td>";
				echo "<td>{$tuote['toim_tuoteno']}</td>";
				echo "<td>{$tuote['nimitys']}</td>";
				echo "<td align='right'>{$tuote['tilaajanrivinro']}</td>";
				echo "<td align='right'>{$tuote['kpl']}</td>";
				echo "<td align='right'>{$tuote['hinta']}</td>";
				#echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "</tr>";
			}

			echo "</table>";

		}
	}

	if ($tee == '') {

		if ($valitse == '') {

			echo "<form method='post' action='?tee='>";
			echo "<table><tr>";
			echo "<th>",t("Valitse"),"</th>";
			echo "<td><select name='valitse'>";
			echo "<option value='ostolasku'>",t("Ostolasku"),"</option>";
			echo "<option value='asn'>",t("ASN-sanomat"),"</option>";
			echo "</select></td>";
			echo "<td><input type='submit' value='",t("Hae"),"' /></td>";
			echo "</tr></table>";
			echo "</form>";

		}
		else {

			if ($valitse == 'asn') {
				$query = "	SELECT toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift,
							asn_sanomat.asn_numero, asn_sanomat.paketintunniste, asn_sanomat.toimittajanumero, 
							count(asn_sanomat.tunnus) AS rivit,
							sum(if(status != '', 1, 0)) AS ok
							FROM asn_sanomat
							JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
							WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
							GROUP BY asn_sanomat.paketinnumero, asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
							ORDER BY asn_sanomat.asn_numero, asn_sanomat.paketintunniste";
				$result = pupe_query($query);

				echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=asn&tee=' id='formi'>";
				echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
				echo "<input type='hidden' id='kolli' name='kolli' value='' />";
				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Ytunnus"),"</th>";
				echo "<th>",t("Nimi"),"</th>";
				echo "<th>",t("Osoite"),"</th>";
				echo "<th>",t("Swift"),"</th>";
				echo "<th>",t("ASN sanomanumero"),"</th>";
				echo "<th>",t("ASN kollinumero"),"</th>";
				echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
				echo "<th>&nbsp;</th>";
				echo "</tr>";

				$ed_asn = '';
				$naytetaanko_toimittajabutton = true;

				while ($row = mysql_fetch_assoc($result)) {

					if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

						if ($naytetaanko_toimittajabutton) {
							echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='{$ed_asn}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
						}

						echo "<tr><td colspan='8' class='back'>&nbsp;</td></tr>";
					}

					echo "<tr>";
					echo "<td>{$row['ytunnus']}</td>";

					echo "<td>{$row['nimi']}";
					if (trim($row['nimitark']) != '') echo " {$row['nimitark']}";
					echo "</td>";

					echo "<td>{$row['osoite']} ";
					if (trim($row['osoitetark']) != '') echo "{$row['osoitetark']} ";
					echo "{$row['postino']} {$row['postitp']} {$row['maa']}</td>";

					echo "<td>{$row['swift']}</td>";
					echo "<td align='right'>{$row['asn_numero']}</td>";
					echo "<td>{$row['paketintunniste']}</td>";
					echo "<td>{$row['ok']} / {$row['rivit']}</td>";
					echo "<td><input type='button' class='kollibutton' id='{$row['paketintunniste']}' value='",t("Valitse"),"' /></td>";
					echo "</tr>";

					if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
						$naytetaanko_toimittajabutton = false;
					}

					$ed_asn = $row['asn_numero'];
				}

				if (mysql_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
					echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='{$ed_asn}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
				}

				echo "</table>";
				echo "</form>";
			}
			else {

				$query = "	SELECT lasku.ytunnus,
							if(lasku.nimitark != '', concat(lasku.nimi, ' ', lasku.nimitark), lasku.nimi) AS nimi, 
							if(lasku.osoitetark != '', concat(lasku.osoite, ' ', lasku.osoitetark), lasku.osoite) AS osoite, 
							lasku.postino, lasku.postitp, lasku.maa, lasku.swift, lasku.laskunro,
							lasku2.tunnus, lasku.tunnus AS latunnus
							#count(tilausrivi.tunnus) AS rivit,
							#sum(if(status != '', 1, 0)) AS ok
							FROM lasku
							LEFT JOIN lasku AS lasku2 on (lasku2.yhtio = lasku.yhtio and lasku2.vanhatunnus = lasku.tunnus and lasku2.tila = 'K')
							JOIN liitetiedostot AS lt ON (lt.yhtio = lasku.yhtio AND lt.liitostunnus = lasku.tunnus AND lt.liitos = 'lasku' AND lt.kayttotarkoitus = 'TECCOM-INVOICE')
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tila IN ('H','M','P','Q','Y')
							AND lasku.laskunro != 0
							AND lasku.tapvm >= '{$yhtiorow['tilikausi_alku']}'
							GROUP BY 1,2,3,4,5,6,7,8,9,10
							HAVING lasku2.tunnus IS NULL
							ORDER BY lasku.ytunnus, lasku.nimi, lasku.laskunro";
				$result = pupe_query($query);

				echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=ostolasku&tee=' id='formi'>";
				echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
				echo "<input type='hidden' id='lasku' name='lasku' value='' />";
				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Ytunnus"),"</th>";
				echo "<th>",t("Nimi"),"</th>";
				echo "<th>",t("Osoite"),"</th>";
				echo "<th>",t("Swift"),"</th>";
				echo "<th>",t("Ostolaskunro"),"</th>";
				// echo "<th>",t("ASN kollinumero"),"</th>";
				// echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
				echo "<th>&nbsp;</th>";
				echo "</tr>";

				while ($row = mysql_fetch_assoc($result)) {

					echo "<tr>";
					echo "<td>{$row['ytunnus']}</td>";
					echo "<td>{$row['nimi']}</td>";
					echo "<td>{$row['osoite']} {$row['postino']} {$row['postitp']} {$row['maa']}</td>";
					echo "<td>{$row['swift']}</td>";
					echo "<td>{$row['laskunro']}</td>";
					echo "<td><input type='button' class='ostolaskubutton' id='{$row['latunnus']}' value='",t("Valitse"),"' /></td>";
					echo "</tr>";
				}

				echo "</table>";
				echo "</form>";
			}
		}
	}

	require ("inc/footer.inc");