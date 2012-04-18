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
						$('#tee').val('vaihdatoimittaja');

						var valitse = $(this).attr('valitse');

						if (valitse == 'asn') {
							var asn = $(this).attr('id');
							$('#formi').attr('action', '?valitse=asn&asn_numero='+asn+'&lopetus={$PHP_SELF}////tee=').submit();
						}
						else {
							var tilausnumero = $(this).attr('id');
							$('#formi').attr('action', '?valitse=ostolasku&tilausnumero='+tilausnumero+'&lopetus={$PHP_SELF}////tee=').submit();
						}
					});

					$('.hintapoikkeavuusbutton').click(function(){
						$('#tee').val('hintavertailu');
						$('#kolliformi').submit();
					});

					$('.etsibutton').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#asn_rivi').val(rivitunniste);
						$('#kolliformi').submit();
					});

					$('.etsibutton_osto').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#lasku').val(rivitunniste);
						$('#kolliformi').submit();
					});

					$('.vahvistabutton').click(function(){
						$('#tee').val('vahvistakolli');
						$('#kolliformi').attr('action', '?').submit();
					});

					$('.poistakohdistus').click(function(){
						$('#tee').val('poistakohdistus');
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
	if (!isset($asn_rivi)) $asn_rivi = '';

	if ($tee == 'hintavertailu') {

		if (!isset($komento)) {

			$otunnus = $lasku;
			$tila = $valitse;

			$tulostimet[0] = t("Hintapoikkeavuus");

			require("inc/valitse_tulostin.inc");
		}

		$valitse = $tila;
		$lasku = $otunnus;

		if (@include_once("pdflib/phppdflib.class.php"));
		else include_once("phppdflib.class.php");

		$norm["height"] 		= 10;
		$norm["font"] 			= "Times-Roman";

		$pieni["height"] 		= 8;
		$pieni["font"] 			= "Times-Roman";

		$boldi["height"] 		= 10;
		$boldi["font"] 			= "Times-Bold";

		$pieni_boldi["height"] 	= 8;
		$pieni_boldi["font"] 	= "Times-Bold";

		$iso["height"] 			= 14;
		$iso["font"] 			= "Helvetica-Bold";

		$rectparam["width"] 	= 0.3;
		$rivinkorkeus			= 15;

		$pdf = new pdffile;
		$pdf->set_default('margin-top', 	0);
		$pdf->set_default('margin-bottom', 	0);
		$pdf->set_default('margin-left', 	0);
		$pdf->set_default('margin-right', 	0);

		$thispage = $pdf->new_page("a4");

		$kala = 815;

		$pdf->draw_text(310, $kala, t("Hintavertailu", $kieli), $thispage, $iso);

		$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND asn_numero = '{$lasku}' AND laji = 'tec'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$query = "SELECT nimi, nimitark FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND toimittajanro = '{$row['toimittajanumero']}'";
		$toimires = pupe_query($query);
		$toimirow = mysql_fetch_assoc($toimires);

		$toimittajan_nimi = trim($toimirow['nimi'].' '.$toimirow['nimitark']);

		$kala -= 40;

		$pdf->draw_text(50, $kala, t("Toimittaja", $kieli).": {$toimirow['nimi']} {$toimirow['nimitark']}", $thispage, $boldi);

		mysql_data_seek($result, 0);

		$kala -= ($rivinkorkeus * 2);

		$pdf->draw_rectangle($kala + 10, 40, $kala - 5, 580, $thispage, $rectparam);

		$pdf->draw_text(50, $kala, t("Tuoteno", $kieli), $thispage, $boldi);
		$pdf->draw_text(200, $kala, t("Kpl", $kieli), $thispage, $boldi);
		$pdf->draw_text(260, $kala, t("Hinta", $kieli), $thispage, $boldi);
		$pdf->draw_text(340, $kala, t("Rivihinta", $kieli), $thispage, $boldi);
		$pdf->draw_text(425, $kala, t("Hintaero", $kieli), $thispage, $boldi);
		$pdf->draw_text(500, $kala, t("Rivihintaero", $kieli), $thispage, $boldi);

		$kala -= 25;

		while ($row = mysql_fetch_assoc($result)) {

			if ($kala <= 150) {
				$thispage = $pdf->new_page("a4");

				$kala = 815;

				$pdf->draw_text(310, $kala, t("Hintavertailu", $kieli), $thispage, $iso);

				$kala -= 40;
				$pdf->draw_text(50, $kala, t("Toimittaja", $kieli).": {$toimirow['nimi']} {$toimirow['nimitark']}", $thispage, $boldi);

				$kala -= ($rivinkorkeus * 2);

				$pdf->draw_rectangle($kala + 10, 40, $kala - 5, 580, $thispage, $rectparam);

				$pdf->draw_text(50, $kala, t("Tuoteno", $kieli), $thispage, $boldi);
				$pdf->draw_text(200, $kala, t("Kpl", $kieli), $thispage, $boldi);
				$pdf->draw_text(260, $kala, t("Hinta", $kieli), $thispage, $boldi);
				$pdf->draw_text(340, $kala, t("Rivihinta", $kieli), $thispage, $boldi);
				$pdf->draw_text(425, $kala, t("Hintaero", $kieli), $thispage, $boldi);
				$pdf->draw_text(500, $kala, t("Rivihintaero", $kieli), $thispage, $boldi);

				$kala -= 25;
			}

			$query = "SELECT tuoteno, nimitys, varattu + kpl AS kpl, hinta FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
			$rivires = pupe_query($query);
			$rivirow = mysql_fetch_assoc($rivires);

			$hintaero = round($row['keikkarivinhinta'] - $rivirow['hinta'], $yhtiorow['hintapyoristys']);
			$rivihintaero = round(($row['keikkarivinhinta'] * $row['kappalemaara']) - ($rivirow['hinta'] * $rivirow['kpl']), $yhtiorow['hintapyoristys']);

			if ($hintaero == 0 and $rivihintaero == 0) continue;

			$pdf->draw_text(50, $kala, $rivirow['tuoteno'], $thispage, $norm);
			$pdf->draw_text(200, $kala, $rivirow['kpl'], $thispage, $norm);
			$pdf->draw_text(260, $kala, round($rivirow['hinta'], $yhtiorow["hintapyoristys"]), $thispage, $norm);
			$pdf->draw_text(340, $kala, round($rivirow['hinta'] * $rivirow['kpl'], $yhtiorow["hintapyoristys"]), $thispage, $norm);
			$pdf->draw_text(425, $kala, $hintaero, $thispage, $norm);
			$pdf->draw_text(500, $kala, $rivihintaero, $thispage, $norm);

			$kala -= $rivinkorkeus;

			$pdf->draw_text(50, $kala, $rivirow['nimitys'], $thispage, $norm);

			$pdf->draw_rectangle($kala - 7, 40, $kala - 7, 580, $thispage, $rectparam);

			$kala -= ($rivinkorkeus + 5);
		}

		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/".t("Hintavertailu")."-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);

		if (!is_array($komento)) {
			$komentoulos = array($komento);
		}
		else {
			$komentoulos = $komento;
		}

		foreach ($komentoulos as $komento) {

			// itse print komento...
			if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
				$liite = $pdffilenimi;
				$content_body = "";

				echo t("Hintapoikkeavuus-raportti tulostuu")."...<br>";

				$kutsu = t("Hintapoikkeavuus-raportti", $kieli).' '.$toimittajan_nimi;

				if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
					$kutsu .= " ".t("Hintapoikkeavuus-raportti", $kieli).' '.$toimittajan_nimi;
				}

				include("inc/sahkoposti.inc");
			}
			elseif ($komento != '' and $komento != 'edi') {
				echo t("Hintapoikkeavuus-raportti tulostuu")."...<br>";
				$line = exec("{$komento} {$pdffilenimi}", $output, $returnvalue);
			}
		}

		// poistetaan tmp file samantien kuleksimasta...
		system("rm -f {$pdffilenimi}");
	}

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

			$query = "SELECT toimittajanro FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$toimittajaid}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			list($nro, $valitse) = explode("##", $muutparametrit);

			if ($valitse == 'asn') {
				$asn_numero = (int) $nro;

				$query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND asn_numero = '{$asn_numero}'";
				$res = pupe_query($query);
			}
			else {
				$tilausnumero = (int) $nro;

				$query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tilausnumero = '{$tilausnumero}'";
				$res = pupe_query($query);
			}

			$tee = '';
			$tila = 'ok';
		}

		if ($tila == '') {

			if ($valitse == 'asn') {
				$action = "&muutparametrit={$asn_numero}##{$valitse}";
			}
			else {
				$action = "&muutparametrit={$tilausnumero}##{$valitse}";
			}

			echo "<form method='post' action='?tee={$tee}{$action}'>";
			echo "<table>";
			echo "<tr><th>",t("Etsi toimittajaa")," (",t("nimi")," / ",t("ytunnus"),")</th><td><input type='text' name='nimi' value='' />&nbsp;<input type='submit' value='",t("Etsi"),"' /></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'vahvistakolli') {

		if ($valitse == 'asn') {
			$kolli = mysql_real_escape_string($kolli);
			$wherelisa = "AND paketintunniste = '{$kolli}'";
		}
		else {
			$wherelisa = "AND asn_numero = '".mysql_real_escape_string($lasku)."'";
		}

		$paketin_rivit 		= array();
		$paketin_tunnukset 	= array();
		$rtuoteno			= array();
		$laskuttajan_toimittajanumero = "";

		$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' {$wherelisa}";
		$kollires = pupe_query($query);

		$i = 0;

		while ($kollirow = mysql_fetch_assoc($kollires)) {

			if ($valitse == 'asn') {

				$toimittaja = $kollirow['toimittajanumero'];
				$asn_numero = $kollirow['asn_numero'];
				$paketin_tunnukset[] = $kollirow['tunnus'];
				
				// Otetaan ASN-sanomalta tilausrivi(e)n tunnus ja laitetaan $paketin_rivit muuttujaan
				if (strpos($kollirow['tilausrivi'], ",") !== false) {
					foreach (explode(",", $kollirow['tilausrivi']) as $tunnus) {
						$paketin_rivit[] = $tunnus;
					}
				}
				else {
					$paketin_rivit[] = $kollirow['tilausrivi'];
				}
				
				// Haetaan tuotteen lapset jotka ovat runkoveloituksia
				$query = "	SELECT group_concat(concat('\"', tuoteperhe.tuoteno, '\"')) lapset
							FROM tuoteperhe
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND isatuoteno = '{$kollirow["tuoteno"]}'
							AND ohita_kerays != ''";
				$result = pupe_query($query);
				$lapset = mysql_fetch_assoc($result);
				
				// Lapsia lˆytyi, t‰m‰ on is‰tuote 
				if ($lapset["lapset"] != NULL) {
					// Haetaan tilausnumerot joilla t‰m‰ tuote on
					$query = "	SELECT group_concat(otunnus) tilaukset
								FROM tilausrivi 
								WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}' 
								AND tilausrivi.tunnus in ({$kollirow["tilausrivi"]})";
					$result = pupe_query($query);
					$tilaukset = mysql_fetch_assoc($result);
				
					// Haetaan t‰m‰n is‰tuotteen lapsituotteiden tunnukset
					$query = " 	SELECT tunnus 
								FROM tilausrivi
								WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
								AND tilausrivi.otunnus in ({$tilaukset["tilaukset"]})
								AND tilausrivi.tuoteno in ({$lapset["lapset"]})"; 
					$result = pupe_query($query);
					
					while ($rivi = mysql_fetch_assoc($result)) {
						$paketin_rivit[] = $rivi["tunnus"];
					}				
				}
				
				$sscc_paketti_tunnus = $kollirow["paketintunniste"];
			}
			else {
				$query = "	UPDATE asn_sanomat SET
							status = 'X'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$kollirow['tunnus']}'";
				$updateres = pupe_query($query);

				$rtuoteno[$i]['tuoteno'] 			= $kollirow['toim_tuoteno'];
				$rtuoteno[$i]['tuoteno2'] 			= $kollirow['toim_tuoteno2'];
				$rtuoteno[$i]['ostotilausnro'] 		= $kollirow['tilausnumero'];
				$rtuoteno[$i]['tilaajanrivinro'] 	= $kollirow['tilausrivinpositio'];
				$rtuoteno[$i]['kpl'] 				= $kollirow['kappalemaara'];
				$rtuoteno[$i]['hinta'] 				= $kollirow['hinta'];
				$rtuoteno[$i]['ale1'] 				= $kollirow['ale1'];
				$rtuoteno[$i]['ale2'] 				= $kollirow['ale2'];
				$rtuoteno[$i]['ale3'] 				= $kollirow['ale3'];
				$rtuoteno[$i]['lisakulu'] 			= $kollirow['lisakulu'];
				$rtuoteno[$i]['kulu'] 				= $kollirow['kulu'];
				$rtuoteno[$i]['kauttalaskutus']		= "";

				$laskuttajan_toimittajanumero = $kollirow['toimittajanumero'];

				$i++;
			}
		}

		if ($valitse != 'asn' and count($rtuoteno) > 0 and $laskuttajan_toimittajanumero != "") {
			$query = "	SELECT tunnus
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laskunro = '{$lasku}'";
			$tunnus_fetch_res = pupe_query($query);
			$tunnus_fetch_row = mysql_fetch_assoc($tunnus_fetch_res);

			$tunnus = $tunnus_fetch_row['tunnus'];

			$query = "	SELECT *
						FROM toimi
						WHERE yhtio = '{$kukarow['yhtio']}'
						and toimittajanro = '{$laskuttajan_toimittajanumero}'
						and tyyppi != 'P'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$trow = mysql_fetch_assoc($result);

				require('inc/verkkolasku-in-luo-keikkafile.inc');

				verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
			}
		}

		if ($valitse == 'asn' and count($paketin_rivit) > 0) {

			$query = "SELECT GROUP_CONCAT(tuoteno) AS tuotenumerot FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN (".implode(",", $paketin_rivit).")";
			$tuotenores = pupe_query($query);
			$tuotenorow = mysql_fetch_assoc($tuotenores);

			$paketin_tuotteet = explode(",", $tuotenorow['tuotenumerot']);
			
			require('inc/asn_kohdistus.inc');

			asn_kohdista_suuntalava($toimittaja, $asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $sscc_paketti_tunnus);

		}

		$tee = '';
	}

	if ($tee == 'poistakohdistus') {

		$kolli = mysql_real_escape_string($kolli);
		$wherelisa = "AND paketintunniste = '{$kolli}'";

		$query = "SELECT * from asn_sanomat where yhtio = '{$kukarow["yhtio"]}' {$wherelisa}";
		$kollires = pupe_query($query);
		$rivi = mysql_fetch_assoc($kollires);

		// poistetaan STATUS-t‰pp‰
		$query = "UPDATE asn_sanomat SET status ='', tilausrivi='' WHERE yhtio = '{$kukarow['yhtio']}' {$wherelisa}";
		$kollires = pupe_query($query);

		$tee = '';
	}

	if ($tee == 'uusirivi') {

		$var = 'O';

		if (trim($tilausnro) == '' or $tilausnro == 0) {

			$asn_rivi = (int) $asn_rivi;

			$query = "SELECT tilausnumero FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$tilausnro = $row['tilausnumero'];
		}

		$tilausnro = (int) $tilausnro;

		$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnro}'";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			$laskurow = mysql_fetch_assoc($res);

			$query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$tuoteno}'";
			$result = pupe_query($query);
			$trow = mysql_fetch_assoc($result);

			list($hinta,,,) = alehinta_osto($laskurow, $trow, $kpl);

			//pidet‰‰n kaikki muuttujat tallessa
			$muut_siirrettavat = $asn_rivi."!°!".$toimittaja."!°!".$tilausnro."!°!".$tuoteno."!°!".$tilaajanrivinro."!°!".$kpl."!°!".$valitse;

			$rivinotunnus = $tilausnro;
			$toimaika = date('Y')."-".date('m')."-".date('d');

			echo t("Tee uusi rivi").":<br>";

			require('tilauskasittely/syotarivi_ostotilaus.inc');
			require('inc/footer.inc');
			exit;
		}
		else {
			$error = t("Ostotilausta").' '.$tilausnro.' '.t("ei lˆydy")."!";
			$tee = 'etsi';
		}
	}

	//poistetaan rivi, n‰ytet‰‰n lista
	if ($tee == 'TI' and isset($tyhjenna)) {
		$tee = 'etsi';
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

			$tmp_rivitunnus = $rivitunnus;

			if ($kpl != 0) {
				require ('tilauskasittely/lisaarivi.inc');
			}

			$rivitunnus = $tmp_rivitunnus;

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

		$tee = 'etsi';
	}

	//korjataan siirrett‰v‰t muuttujat taas talteen
	if (isset($muut_siirrettavat)) {
		list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl, $valitse) = explode("!°!", $muut_siirrettavat);
	}

	if ($tee == 'kohdista_tilausrivi') {

		if (count($tunnukset) == 0) {
			$error = t("Halusit kohdistaa, mutta et valinnut yht‰‰n rivi‰")."!";
			$tee = 'etsi';
		}
		else {
			// typecastataan formista tulleet tunnukset stringeist‰ inteiksi
			$tunnukset = array_map('intval', $tunnukset);
			$poista_tilausrivi = array_map('intval', $poista_tilausrivi);
			$ostotilauksella_tilaajanrivinro = array_map('intval',$ostotilauksella_tilaajanrivinro);
			// otetaan ostotilausrivin kpl m‰‰r‰, splitataan ja menn‰‰ eteenp‰in...
			// T‰m‰ pit‰‰ sitten jollain tavalla muuttaa paremmaksi, t‰m‰ on versio 1.0
			$kpl_maara_ostolla = $ostotilauksella_kpl[$tunnukset[0]];

			if ($valitse == 'asn') {

				// haetaan ASN-sanomalta kpl m‰‰r‰
				$hakuquery = "SELECT * from asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
				
				$hakures = pupe_query($hakuquery);
				$asn_row_haku = mysql_fetch_assoc($hakures);

				$asn_kpl_tilaukselta = $asn_row_haku["kappalemaara"];
				$erotus = $kpl_maara_ostolla - $asn_kpl_tilaukselta;
				
				if ($erotus != 0) {
					// tehd‰‰n splitti
					// haetaan ostotilauksen rivitiedot kyseiselle riville.
					$query = "SELECT * from tilausrivi where yhtio='{$kukarow["yhtio"]}' and tunnus='{$tunnukset[0]}'";

					$ostores = pupe_query($query);
					$ostotilausrivirow = mysql_fetch_assoc($ostores);
					
					// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
					$query = "	UPDATE tilausrivi SET
								varattu = '{$asn_kpl_tilaukselta}',
								tilkpl	= '{$asn_kpl_tilaukselta}'
								WHERE yhtio = '{$kukarow["yhtio"]}'
								AND tunnus = '{$ostotilausrivirow["tunnus"]}'";
					$upres = pupe_query($query);
					
					// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
					$query = "	INSERT INTO tilausrivi SET
								yhtio 		= '$ostotilausrivirow[yhtio]',
								tyyppi		= '$ostotilausrivirow[tyyppi]',
								toimaika	= '$ostotilausrivirow[toimaika]',
								kerayspvm	= '$ostotilausrivirow[kerayspvm]',
								otunnus		= '$ostotilausrivirow[otunnus]',
								tuoteno		= '$ostotilausrivirow[tuoteno]',
								try			= '$ostotilausrivirow[try]',
								osasto		= '$ostotilausrivirow[osasto]',
								nimitys		= '$ostotilausrivirow[nimitys]',
								yksikko		= '$ostotilausrivirow[yksikko]',
								tilkpl		= '$erotus',
								varattu		= '$erotus',
								hinta		= '$ostotilausrivirow[hinta]',
								laatija		= '$ostotilausrivirow[laatija]',
								laadittu	= '$ostotilausrivirow[laadittu]',
								hyllyalue	= '$ostotilausrivirow[hyllyalue]',
								hyllynro	= '$ostotilausrivirow[hyllynro]',
								hyllytaso	= '$ostotilausrivirow[hyllytaso]',
								hyllyvali	= '$ostotilausrivirow[hyllyvali]',
								tilaajanrivinro = '$ostotilausrivirow[tilaajanrivinro]'";

					$inskres = pupe_query($query);
				}

				$query = "UPDATE asn_sanomat SET tilausrivi = '".implode(",", $tunnukset)."', muuttaja ='{$kukarow["kuka"]}', muutospvm = now() WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
				$updres = pupe_query($query);
				
				// p‰ivitet‰‰n t‰ss‰ vaiheessa tilaukselle tilaajanrivipositio t‰lle uudelle riville, mik‰li ollaan poistamassa samalla vanha.
				if ($poista_tilausrivi["0"] != 0) {
					$updatequery2 = "UPDATE tilausrivi set tilaajanrivinro ='{$poista_tilausrivi[0]}' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$asn_row_haku["tilausnumero"]}' and tunnus = '".implode(",", $tunnukset)."'";
					pupe_query($updatequery2);
				}
				
				$query = "SELECT paketintunniste, asn_numero FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
				$res = pupe_query($query);
				$row = mysql_fetch_assoc($res);

				$kolli = $row['paketintunniste'];
			}
			else {
				$query = "UPDATE asn_sanomat SET tilausrivi = '".implode(",", $tunnukset)."', muuttaja ='{$kukarow["kuka"]}', muutospvm = now() WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnus}'";
				$updres = pupe_query($query);
			}

			$tee = 'nayta';
		}
	}

	if ($tee == 'etsi') {

		if ($valitse == 'asn') {
			if (isset($asn_rivi) and strpos($asn_rivi, '##') !== false) {
				list($asn_rivi, , $tilaajanrivinro) = explode('##', $asn_rivi); // ei otetan linkist‰ tuoteno:a koska jos siin‰ on v‰li niin hajoilee

				$asn_rivi = (int) $asn_rivi;

				$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
				$result = pupe_query($query);
				$asn_row = mysql_fetch_assoc($result);

				if ($asn_row["toimittajanumero"] == "123067") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty_tuoteno = substr($asn_row["toim_tuoteno"], 0, -3);
					$jatkettu_tuoteno = $lyhennetty_tuoteno."090";
					
					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno =" in ('$orgtuote','$lyhennetty_tuoteno','$jatkettu_tuoteno' $toinen_tuoteno)";
				}
				elseif ($asn_row["toimittajanumero"] == "123453") {
					$suba = substr($asn_row["toim_tuoteno"],0,3);
					$subb = substr($asn_row["toim_tuoteno"],3);
					$tuote = $suba."-".$subb;
					$yhteen = $asn_row["toim_tuoteno"];
					
					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('$tuote','$yhteen' $toinen_tuoteno) ";
				}
				elseif ($asn_row["toimittajanumero"] == "123178") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty = substr($asn_row["toim_tuoteno"],3);

					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = substr($asn_row["toim_tuoteno2"],3);
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				elseif ($asn_row["toimittajanumero"] == "123084") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty = ltrim($asn_row["toim_tuoteno"],'0');
					
					if ($asn_row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = ltrim($asn_row["toim_tuoteno2"],'0');
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				else {
					
					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('{$asn_row["toim_tuoteno"]}' $toinen_tuoteno) ";
				}

				$query = "	SELECT tt.tuoteno ttuoteno, tt.toim_tuoteno, tuote.tuoteno tuoteno 
							FROM tuotteen_toimittajat as tt
							JOIN toimi on (toimi.tunnus = tt.liitostunnus and toimi.yhtio = tt.yhtio and toimi.toimittajanro='{$asn_row["toimittajanumero"]}' and tt.toim_tuoteno {$poikkeus_tuoteno} and toimi.tyyppi !='P')
							JOIN tuote on (tuote.yhtio = toimi.yhtio and tuote.tuoteno = tt.tuoteno and tuote.status !='P')
							WHERE tt.yhtio='{$kukarow['yhtio']}'";
				$result = pupe_query($query);
				$apurivi = mysql_fetch_assoc($result);

				if ($apurivi["tuoteno"] !="") {
					$tuoteno =  $apurivi['tuoteno'];
				}
				else {
					$tuoteno =  $asn_row['toim_tuoteno'];
				}

			}

			// pakotetaan tuoteno asn_sanomasta. V‰lilyˆnnit tekee kiusaa
			// if (!isset($tuoteno)) $tuoteno =  $asn_row['toim_tuoteno'];
			if (!isset($toimittaja) and isset($asn_row)) $toimittaja = $asn_row['toimittajanumero'];
			if (!isset($tilausnro) and isset($asn_row)) $tilausnro = $asn_row['tilausnumero'];
			if (!isset($kpl) and isset($asn_row)) $kpl = $asn_row['kappalemaara'];
		}
		else {
			if (isset($lasku) and strpos($lasku, '##') !== false) {
				list($lasku, $tuoteno, $tilaajanrivinro, $toimittaja, $kpl, $rivitunnus, $tilausnumero) = explode('##', $lasku);

				$tilausnro = $tilausnumero;
			}
		}

		echo "<form method='post' action='?tee=etsi&valitse={$valitse}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&lopetus={$lopetus}'>";

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

		echo "<form method='post' action='?tee=uusirivi&valitse={$valitse}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}&lopetus={$lopetus}'>";
		echo "<input type='submit' value='",t("Tee uusi tilausrivi"),"' />";
		echo "</form>";
		echo "<br />";

		if (trim($toimittaja) != '' or trim($tilausnro) != '' or trim($tuoteno) != '' or trim($tilaajanrivinro) != '' or trim($kpl) != '') {
			echo "<br /><hr /><br />";

			if (isset($error)) echo "<font class='error'>{$error}</font><br /><br />";

			echo "<form method='post' id='kohdista_tilausrivi_formi' action='?tee=kohdista_tilausrivi&rivitunnus={$rivitunnus}&valitse={$valitse}&lasku={$lasku}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}'>";
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
			echo "<th>",t("Poista"),"</th>";
			echo "</tr>";

			$toimittaja = (int) $toimittaja;

			$query = "SELECT tunnus FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND toimittajanro = '{$toimittaja}' and tyyppi !='P'";
			
			$toimires = pupe_query($query);
			$toimirow = mysql_fetch_assoc($toimires);

			$tilaajanrivinrolisa = trim($tilaajanrivinro) != '' ? " and tilausrivi.tilaajanrivinro = ".(int) $tilaajanrivinro : '';
			$tilausnrolisa = trim($tilausnro) != '' ? " and tilausrivi.otunnus = ".(int) $tilausnro : '';
			$tuoteno_valeilla = str_replace(' ','_',$tuoteno);
			$tuoteno_ilman_valeilla =str_replace(' ','',$tuoteno);
			$tuotenolisa = trim($tuoteno) != '' ? " and (tuoteno like '".mysql_real_escape_string($tuoteno)."%' or tuoteno like '".mysql_real_escape_string($tuoteno_valeilla)."' or tuoteno like '".mysql_real_escape_string($tuoteno_ilman_valeilla)."')" : '';
			$kpllisa = trim($kpl) != '' ? " and varattu = ".(float) $kpl : '';

			$query = "	SELECT tilausrivi.*, if(tilausrivi.uusiotunnus = 0, '', tilausrivi.uusiotunnus) AS uusiotunnus
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'O' AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.kpl = 0 {$tilaajanrivinrolisa}{$tilausnrolisa}{$tuotenolisa}{$kpllisa})
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila IN ('O', 'K')
						AND lasku.alatila != 'X'
						AND lasku.liitostunnus = '{$toimirow['tunnus']}'
						ORDER BY tilausrivi.tunnus, tilausrivi.uusiotunnus, lasku.tunnus";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {

				// katsotaan ettei rivi‰ ole jo kohdistettu muuhun asn riviin
				$query = "SELECT tunnus FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND toimittajanumero = '{$toimittaja}' AND tilausrivi LIKE '%{$row['tunnus']}%'";
				$chkres = pupe_query($query);

				if (mysql_num_rows($chkres) > 0) continue;

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
					echo "<td align='center'>";
					echo "<input type='checkbox' name='tunnukset[]' class='tunnukset' value='{$row['tunnus']}' />";
					echo "<input type='hidden' name='ostotilauksella_kpl[{$row['tunnus']}]' value='{$row['varattu']}' />";
					echo "<input type='hidden' name='ostotilauksella_tilaajanrivinro[{$row['tunnus']}]' value='{$row['tilaajanrivinro']}' />";
					echo "</td>";
				}
				echo "<td><input type='checkbox' name='poista_tilausrivi[]' class='tunnukset' value='{$row['tunnus']}' /></td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'nayta') {

		if ($valitse == 'asn') {
			$kolli = mysql_real_escape_string($kolli);

			$query = "	SELECT asn_sanomat.toimittajanumero, 
						asn_sanomat.toim_tuoteno, 
						asn_sanomat.tilausrivinpositio, 
						asn_sanomat.kappalemaara, 
						asn_sanomat.status, 
						asn_sanomat.tilausnumero,
						tilausrivi.tuoteno,
						tilausrivi.otunnus, 
						tilausrivi.tilkpl as tilattu,
						if(tilausrivi.ale1 = 0, '', tilausrivi.ale1) AS alennus
						FROM asn_sanomat
						JOIN tilausrivi ON (tilausrivi.yhtio = asn_sanomat.yhtio AND tilausrivi.tunnus IN (asn_sanomat.tilausrivi))
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi!='P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						AND asn_sanomat.tilausrivi != ''
						AND asn_sanomat.laji = 'asn'
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);

			echo "<form method='post' action='?valitse={$valitse}&lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//kolli={$kolli}//valitse={$valitse}' id='kolliformi'>";
			echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
			echo "<input type='hidden' id='kolli' name='kolli' value='{$kolli}' />";
			echo "<input type='hidden' id='asn_rivi' name='asn_rivi' value='' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Laskun numero"),"</th>";
			echo "<th>",t("Ostotilausnro"),"</th>";
			echo "<th>",t("Tuotenro"),"</th>";
			echo "<th>",t("Toimittajan"),"<br />",t("Tuotenro"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Tilattu kpl"),"</th>";
			echo "<th>",t("Asn-kpl"),"</th>";
			echo "<th>",t("Alennukset"),"</th>";
			echo "<th>",t("Status"),"</th>";
			echo "<th class='back'>&nbsp;</th>";
			echo "</tr>";

			$ok = 0;

			while ($row = mysql_fetch_assoc($result)) {

				$ok++;

				echo "<tr>";

				$query = "SELECT nimitys FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$row['tuoteno']}' and status !='P'";
				$tuoteres = pupe_query($query);
				$tuoterow = mysql_fetch_assoc($tuoteres);

				$row['nimitys'] = $tuoterow['nimitys'];

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$row['otunnus']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilattu']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";
				echo "<td><font class='ok'>",t("Ok"),"</font></td>";
				echo "<td class='back'></td>";
				echo "</tr>";
			}

			$virhe = 0;

			$query = "	SELECT asn_sanomat.toimittajanumero, 
						asn_sanomat.toim_tuoteno,
						asn_sanomat.toim_tuoteno2, 
						asn_sanomat.tilausrivinpositio, 
						asn_sanomat.kappalemaara, 
						asn_sanomat.status, 
						asn_sanomat.tilausnumero,
						toimi.tunnus AS toimi_tunnus, asn_sanomat.tunnus AS asn_tunnus
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						AND asn_sanomat.tilausrivi = ''
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);


			while ($row = mysql_fetch_assoc($result)) {

				$virhe++;

				echo "<tr>";

				if ($row["toimittajanumero"] == "123067") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty_tuoteno = substr($row["toim_tuoteno"], 0, -3);
					$jatkettu_tuoteno = $lyhennetty_tuoteno."090";
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno =" in ('$orgtuote','$lyhennetty_tuoteno','$jatkettu_tuoteno' $toinen_tuoteno)";
				}
				elseif ($row["toimittajanumero"] == "123453") {
					$suba = substr($row["toim_tuoteno"],0,3);
					$subb = substr($row["toim_tuoteno"],3);
					$tuote = $suba."-".$subb;
					$yhteen = $row["toim_tuoteno"];
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('$tuote','$yhteen' $toinen_tuoteno) ";
				}
				elseif ($row["toimittajanumero"] == "123178") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty = substr($row["toim_tuoteno"],3);

					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = substr($row["toim_tuoteno2"],3);
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				elseif ($row["toimittajanumero"] == "123084") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty = ltrim($row["toim_tuoteno"],'0');
					
					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = ltrim($row["toim_tuoteno2"],'0');
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				else {
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('$row[toim_tuoteno]' $toinen_tuoteno) ";
				}


				$query = "	SELECT tt.tuoteno 
							FROM tuotteen_toimittajat as tt
							JOIN tuote on (tuote.yhtio=tt.yhtio and tt.tuoteno = tuote.tuoteno and tuote.status !='P') 
							WHERE tt.yhtio = '{$kukarow['yhtio']}' 
							AND tt.toim_tuoteno {$poikkeus_tuoteno} 
							AND tt.liitostunnus = '{$row['toimi_tunnus']}'";
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

					$query = "	SELECT tuoteno, uusiotunnus, tilaajanrivinro 
								FROM tilausrivi 
								WHERE yhtio = '{$kukarow['yhtio']}' 
								AND otunnus = '{$row['tilausnumero']}' 
								AND tuoteno = '{$row['tuoteno']}' 
								AND tilaajanrivinro = '{$row['tilausrivinpositio']}' 
								AND tyyppi = 'O'";
					$tilres = pupe_query($query);


					if (mysql_num_rows($tilres) > 0) {
						$tilrow = mysql_fetch_assoc($tilres);
						// $row['nimitys'] = $tilrow['nimitys'];
						$row['uusiotunnus'] = $tilrow['uusiotunnus'];
						$row['tilausrivinpositio'] = $tilrow['tilaajanrivinro'];
					}
					else {
						$row['uusiotunnus'] = 0;
					}
				}
				else {
					$row['tuoteno'] = '';
					$row['nimitys'] = t("Tuntematon tuote");
				}

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilattu']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";

				echo "<td><font class='error'>",t("Virhe"),"</font></td>";

				echo "<td class='back'>";
				if ($row['uusiotunnus'] == 0) echo "<input type='button' class='etsibutton' id={$row['asn_tunnus']}##{$row['tuoteno']}##{$row['tilausrivinpositio']}' value='",t("Etsi"),"' />";
				echo "</td>";

				echo "</tr>";
			}

			if ($ok and !$virhe) {
				echo "<tr><th colspan='10' class='back'><input type='button' class='vahvistabutton' value='",t("Vahvista"),"' /></th></tr>";
				if ($valitse == "asn") {
					echo "<tr><th colspan='10' class='back'><input type='button' class='poistakohdistus' value='",t("Poista Kohdistus"),"' /></th></tr>";
				}
			}

			echo "</table>";
			echo "</form>";
		}
		else {

			echo "<form method='post' action='?valitse={$valitse}&lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//lasku={$lasku}//valitse={$valitse}' id='kolliformi'>";
			echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
			echo "<input type='hidden' id='lasku' name='lasku' value='{$lasku}' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
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

			$query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND laskunro = '{$lasku}'";
			$laskures = pupe_query($query);

			if (mysql_num_rows($laskures) == 0) {
				$query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND comments = '{$lasku}'";
				$laskures = pupe_query($query);
			}

			$laskurow = mysql_fetch_assoc($laskures);

			$query = "	SELECT asn_sanomat.toimittajanumero,
						asn_sanomat.toim_tuoteno,
						asn_sanomat.tilausrivinpositio,
						asn_sanomat.status,
						asn_sanomat.tilausnumero,
						asn_sanomat.kappalemaara,
						asn_sanomat.tilausrivi,
						asn_sanomat.hinta,
						asn_sanomat.keikkarivinhinta,
						asn_sanomat.tunnus,
						toimi.asn_sanomat
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.asn_numero LIKE '%{$lasku}'
						AND asn_sanomat.laji = 'tec'
						#AND asn_sanomat.tilausrivi != ''
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);

			$ok = $virhe = 0;
			$hintapoikkeavuus = false;

			while ($row = mysql_fetch_assoc($result)) {

				$query = "	SELECT tuotteen_toimittajat.tuoteno 
							FROM tuotteen_toimittajat 
							JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}' 
							AND tuotteen_toimittajat.toim_tuoteno = '{$row['toim_tuoteno']}' 
							AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) > 0) {
					$ttrow = mysql_fetch_assoc($res);

					$row['tuoteno'] = $ttrow['tuoteno'];

					$query = "	SELECT nimitys
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$ttrow['tuoteno']}'
								AND status != 'P'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_assoc($tres);

					$row['nimitys'] = $trow['nimitys'];
				}

				if ($row['tilausrivi'] != '') {
					$query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$row['tilausrivi']})";
					$hinta_chk_res = pupe_query($query);
					$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

					$row['tilausnumero'] = $hinta_chk_row['otunnus'];
				}

				echo "<tr>";
				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilausrivinpositio']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td align='right'>{$row['hinta']}</td>";
				#echo "<td>&nbsp;</td>";

				echo "<td>";
				if ($row['tilausrivi'] != '') {
					echo "<font class='ok'>Ok</font>"; 
					$ok++;

					if ($row['keikkarivinhinta'] != $hinta_chk_row['hinta']) {
						echo "<br /><font class='error'>",t("Hintapoikkeavuus"),"</font>";
						$hintapoikkeavuus = true;
					}
				}
				elseif ($row['tilausrivi'] == '' and $row['tilausrivinpositio'] != '') {

					$query = "	SELECT tilausrivi.tilkpl, tilausrivi.otunnus 
								FROM lasku 
								LEFT JOIN tilausrivi ON (
									tilausrivi.yhtio = lasku.yhtio AND 
									tilausrivi.otunnus = lasku.tunnus AND 
									tilausrivi.tilaajanrivinro = '{$row['tilausrivinpositio']}' AND 
									tilausrivi.tuoteno = '{$row['toim_tuoteno']}'
								)
								WHERE lasku.yhtio = '{$kukarow['yhtio']}'
								AND lasku.liitostunnus = '{$laskurow['liitostunnus']}'
								AND lasku.tila = 'O'
								AND lasku.alatila IN ('', 'A')";
					$kpl_chk_res = pupe_query($query);
					$kpl_row = mysql_fetch_assoc($kpl_chk_res);
					
					if ($kpl_row["tilkpl"] != $row["kappalemaara"]) {
						echo "<font color='orange'>Kpl ongelma <br>tilauksella {$kpl_row["tilkpl"]} <br>sanomassa {$row["kappalemaara"]}</font>";
					}
					if ($kpl_row["otunnus"] != 0 and ($row['tilausnumero'] != $kpl_row["otunnus"])) {
						echo "<font color='scarlet'>Sanoman ostotilausnro ja <br>laskun ostotilausnro ei t‰sm‰‰<br> Tilaus: {$kpl_row["otunnus"]} <br>sanoma:{$row['tilausnumero']}</font>";
					}
				}
				else {
					echo "<font class='error'>",t("Virhe"),"</font>";
					$virhe++;
				}
				echo "</td>";

				echo "<td class='back'>";
				if ($row['tilausrivi'] == '') echo "<input type='button' class='etsibutton_osto' id='{$lasku}##{$row['tuoteno']}##{$row['tilausrivinpositio']}##{$row['toimittajanumero']}##{$row['kappalemaara']}##{$row['tunnus']}##{$row['tilausnumero']}' value='",t("Etsi"),"' />";
				echo "</td>";
				echo "</tr>";
			}

			if ($ok and !$virhe) {
				if ($hintapoikkeavuus) {
					echo "<tr><th colspan='10'><input type='button' class='hintapoikkeavuusbutton' value='",t("Hintapoikkeavuus raportti"),"' /></th></tr>";
				}

				echo "<tr><th colspan='10'><input type='button' class='vahvistabutton' value='",t("Vahvista"),"' /></th></tr>";
			}

			echo "</table>";
			echo "</form>";

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
				$query = "	SELECT 	toimi.ytunnus,
									toimi.nimi,
									toimi.nimitark,
									toimi.osoite,
									toimi.osoitetark,
									toimi.postino,
									toimi.postitp,
									toimi.maa,
									toimi.swift,
									asn_sanomat.asn_numero,
									asn_sanomat.paketintunniste,
									asn_sanomat.toimittajanumero,
									asn_sanomat.status,
									count(asn_sanomat.tunnus) AS rivit,
									sum(if(asn_sanomat.tilausrivi != '', 1, 0)) AS ok
							FROM asn_sanomat
							JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
							WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
							AND asn_sanomat.laji = 'asn'
							GROUP BY asn_sanomat.paketinnumero, asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
							ORDER BY asn_sanomat.asn_numero, asn_sanomat.paketintunniste";
				$result = pupe_query($query);

				echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=asn&tee=' id='formi'>";
				echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
				echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
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
				$ed_toimittaja = '';
				$naytetaanko_toimittajabutton = true;

				while ($row = mysql_fetch_assoc($result)) {

					// n‰ytet‰‰n vain vialliset rivit
					if ($row["rivit"] == $row["ok"] and $row["status"] == "X") {
						continue;
					}

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
					$ed_toimittaja = $row['toimittajanumero'];
				}

				if (mysql_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
					echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='{$ed_asn}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
				}

				echo "</table>";
				echo "</form>";
			}
			else {

				$query = "	SELECT 	toimi.ytunnus, 
									toimi.nimi, 
									toimi.nimitark, 
									toimi.osoite, 
									toimi.osoitetark, 
									toimi.postino, 
									toimi.postitp, 
									toimi.maa, 
									toimi.swift,
									asn_sanomat.asn_numero as tilausnumero, 
									asn_sanomat.paketintunniste, 
									asn_sanomat.toimittajanumero,
									count(asn_sanomat.tunnus) AS rivit,
									sum(if(asn_sanomat.tilausrivi != '', 1, 0)) AS ok
							FROM asn_sanomat
							JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
							WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
							AND asn_sanomat.laji = 'tec'
							AND asn_sanomat.status != 'X'
							GROUP BY asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
							ORDER BY toimi.nimi, toimi.ytunnus";
				$result = pupe_query($query);

				echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=ostolasku&tee=' id='formi'>";
				echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
				echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
				echo "<input type='hidden' id='lasku' name='lasku' value='' />";
				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Ytunnus"),"</th>";
				echo "<th>",t("Nimi"),"</th>";
				echo "<th>",t("Osoite"),"</th>";
				echo "<th>",t("Swift"),"</th>";
				echo "<th>",t("Ostolaskunro"),"</th>";
				echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
				echo "<th>&nbsp;</th>";
				echo "</tr>";

				$ed_toimittaja = '';
				$ed_tilausnumero = '';
				$naytetaanko_toimittajabutton = true;

				while ($row = mysql_fetch_assoc($result)) {

					if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

						if ($naytetaanko_toimittajabutton) {
							echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
						}

						echo "<tr><td colspan='8' class='back'>&nbsp;</td></tr>";
					}

					echo "<tr>";
					echo "<td>{$row['ytunnus']}</td>";
					echo "<td>{$row['nimi']}</td>";
					echo "<td>{$row['osoite']} {$row['postino']} {$row['postitp']} {$row['maa']}</td>";
					echo "<td>{$row['swift']}</td>";
					echo "<td>{$row['tilausnumero']}</td>";
					echo "<td>{$row['ok']} / {$row['rivit']}</td>";
					echo "<td><input type='button' class='ostolaskubutton' id='{$row['tilausnumero']}' value='",t("Valitse"),"' /></td>";
					echo "</tr>";

					if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
						$naytetaanko_toimittajabutton = false;
					}

					$ed_toimittaja = $row['toimittajanumero'];
					$ed_tilausnumero = $row['tilausnumero'];
				}

				if (mysql_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
					echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
				}

				echo "</table>";
				echo "</form>";
			}
		}
	}

	require ("inc/footer.inc");