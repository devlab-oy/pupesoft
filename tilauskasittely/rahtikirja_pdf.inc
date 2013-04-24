<?php

	//	Jos t‰m‰ on osa "kokonaistoimitusta", merkataan alkuper‰inen tilausnumero alkuun
	if ($rakir_row["tunnusnippu"]>0) {
		foreach ($lotsikot as &$o) {
			if($o <> $rakir_row["tunnusnippu"]) {
				$o = "$rakir_row[tunnusnippu]/$o";
			}
		}
	}

	// onko t‰m‰ tyhj‰ rahtikirja (rahtikirja_custom.php), jos EI niin haetaan infot tilaukselta
	if (!isset($GLOBALS['tyhja'])) {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus in ($otunnukset)
					ORDER BY tunnus
					LIMIT 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow = mysql_fetch_array($tempr);

		// haetaan varaston osoitetiedot, k‰ytet‰‰n niit‰ l‰hetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$postirow[varasto]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow_varasto = mysql_fetch_array($tempr);

		// jos varastolle on annettu joku osoite, k‰ytet‰‰n sit‰
		if ($postirow_varasto["nimi"] != "") {
			$postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
			$postirow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
			$postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
			$postirow["yhtio_postino"]  = $postirow_varasto["postino"];
			$postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
			$postirow["yhtio_maa"]      = $postirow_varasto["maa"];
		}

		// jos meill‰ on printterin takana joku spessu osoitetieto niin k‰ytet‰‰n sen tietoja l‰hett‰j‰n tietoina
		if ($print["nimi"] != "") {
			$postirow["yhtio_nimi"]    = $print["nimi"];
			$postirow["yhtio_osoite"]  = $print["osoite"];
			$postirow["yhtio_postino"] = $print["postino"];
			$postirow["yhtio_postitp"] = $print["postitp"];
		}

		// l‰hett‰j‰n viite otetaan laskulta
		$viitelah = $lotsikot[0];
	}
	elseif ($osoitelappurow["toim_nimi"] != '') {
		// t‰nne tullaan, jos ollaan oltu rahtikirja_custom.phpss‰
		$postirow = $osoitelappurow;
		$rakir_row = $osoitelappurow;
	}
	else {
		$query = "	SELECT asiakas.*, rahtikirjat.merahti, rahtikirjat.rahtisopimus
					FROM asiakas
					JOIN rahtikirjat on rahtikirjat.yhtio = asiakas.yhtio and rahtikirjat.otsikkonro = '".($otsikkonro * -1)."'
					WHERE asiakas.yhtio	= '$kukarow[yhtio]'
					and asiakas.tunnus	= '$GLOBALS[asiakasid]'
					LIMIT 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$rakir_row = mysql_fetch_array($tempr);

		if (empty($rakir_row['toim_postitp'])) {
			$rakir_row['toim_postitp'] = $rakir_row['postitp'];
			$rakir_row['toim_postino'] = $rakir_row['postino'];
			$rakir_row['toim_osoite']  = $rakir_row['osoite'];
			$rakir_row['toim_nimi']    = $rakir_row['nimi'];
		}
	}

	if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

		$logyq = "	SELECT min(rahtikirjanro) rahtikirjanro
					FROM rahtikirjanumero
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytettyaika = '0000-00-00 00:00:00'";
		$logyres = pupe_query($logyq);
		$logyrow = mysql_fetch_assoc($logyres);

		$rahtikirjanro = $logyrow["rahtikirjanro"];

		$logyq = "	UPDATE rahtikirjanumero
					SET kayttaja = '$kukarow[kuka]',
					kaytettyaika = now()
					WHERE yhtio = '$kukarow[yhtio]'
					and rahtikirjanro = '$logyrow[rahtikirjanro]'";
		$logyres = pupe_query($logyq);
	}
	elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
		$rahtikirjanro = $lotsikot[0];
	}
	else {
		$rahtikirjanro = $rivi["rahtikirjanro"];
	}

	//tsekataan kuka maksaa rahdin ja laitetaan oikeat tiedot rahdinmaksajan ruutuun
	if ($rakir_row['muumaksaja'] != '') {
		$rahdinmaksaja = $rakir_row['muumaksaja'];
	}

	if ($rahdinmaksaja != 'L‰hett‰j‰') {
		$rahdinmaksajan_nr 	= $rakir_row['rahtisopimus'];
	}
	else {
		$rahdinmaksajan_nr 	= $toitarow['sopimusnro'];
	}

	// postiennakko
	if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
		$postiennakkomaara  = "$yhteensa $postirow[valkoodi]";
		if ($yhtiorow["pankkiiban1"] != '') {
			$postiennakkotilino = "$yhtiorow[pankkiiban1]";
		}
		else {
			$postiennakkotilino = "$yhtiorow[pankkitili1]";
		}
		$postiennakkobic	= "$yhtiorow[pankkiswift1]";
		$postiennakkoviite  = "$viite";
	}
	else {
		$postiennakkomaara  = "";
		$postiennakkotilino = "$yhtiorow[pankkitili1]";
		$postiennakkoviite  = "";
	}

	//PDF:n luonti ja defaultit
	define (__TRACE_ENABLED__, false);
	define (__DEBUG_ENABLED__, false);

	if (in_array("gd", get_loaded_extensions())) {
		require_once("barcode/barcode.php");
		require_once("barcode/debug.php");
		require_once("barcode/c39object.php");
	}
	require_once("pdflib/phppdflib.class.php");

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	//fontit
	$norm["height"] = 6;
	$norm["font"] = "Times-Roman";

	$kirj["height"] = 8;
	//$kirj["font"] = "Times-Roman";
	$kirj["font"] = "Times-Bold";

	$iso["height"] = 12;
	$iso["font"] = "Helvetica-Bold";

	$huge["height"] = 22;
	$huge["font"] = "Helvetica-Bold";

	// Tulostetaan oikea sivum‰‰r‰
	if ($yhtiorow["rahtikirjan_kopiomaara"] == 4) {
		$tulostakolli = 4;
	}
	else {
		$tulostakolli = 3;
	}

	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

		// tehd‰‰n pdf:n uusi sivu
		$firstpage = $pdf->new_page("a4");

		if ($toitarow['sopimusnro'] != '') {
			$toitarow['sopimusnro'] = sprintf("%06s",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitk‰
		}

		//tehd‰‰n viivakoodi joka 9 merkki‰ pitk‰.
		$viiva 	= $rahtikirjanro;

		//Duusataan viivakoodia
		$output   = "png";
		$width    = "180";
		$height   = "35";
		$xres     = "1";
		$font     = "2";
		$drawtext = "off";
		$border   = "on";
		$stretchtext = "off";
		$style    = BCS_ALIGN_CENTER;
		$style   |= ($output     == "png" ) ? BCS_IMAGE_PNG      : 0;
		$style   |= ($output     == "jpeg") ? BCS_IMAGE_JPEG     : 0;
		$style   |= ($border     == "on"  ) ? BCS_BORDER         : 0;
		$style   |= ($drawtext   == "on"  ) ? BCS_DRAW_TEXT      : 0;
		$style   |= ($stretchtext== "on"  ) ? BCS_STRETCH_TEXT   : 0;
		$style   |= ($negative   == "on"  ) ? BCS_REVERSE_COLOR  : 0;

		if (class_exists("c39object")) {
			//luodaan viivakoodiolio
			$obj = "";
			$obj = new c39object($width, $height, $style, $viiva);

			if ($obj) {
				$obj->SetFont($font);
				$obj->DrawObject($xres);

				//flushataan barcode ja saadaam filenimi johon se tallennettiin
				$nimi1 = $obj->FlushObject();

				//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
				$nimi2 = "/tmp/".md5(uniqid(rand(),true)).".png";

				passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);

				if ($fh = @fopen($nimi2, "r")) {
					$data = fread($fh, filesize($nimi2));
					fclose($fh);
					$image = $pdf->png_embed($data);

					if ($image) {
						// piirret‰‰n viivakoodi paprulle
						$pdf->image_place($image, 631, 300, $firstpage); // Y, X
					}
				}

				$pdf->draw_text(390, 625,  $viiva, $firstpage, $norm);

				unset($obj);

				//dellataan tmp filet kuleksimasta
				system("rm -f $nimi1 $nimi2");
			}
		}

		// sitten aletaan piirt‰m‰‰n itse PDF sis‰ltˆ‰
		$pdf->draw_text(302,  812,  "RAHTIKIRJA FRAKTSEDEL",							$firstpage, $iso);

		$apu_yhtiorow = array();

		// varmistetaan, ett‰ kopiossakin tulee oikean toimipaikan logo
		if ((int) $postirow["yhtio_toimipaikka"] != 0) {
			$query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$postirow[yhtio_toimipaikka]'
						and lasku_logo != ''";
			$result = mysql_query($query) or pupe_error("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_toimipaikkarow = mysql_fetch_array($result);
				$apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
		 	}
		}
		else {
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio = '$postirow[yhtio]'";
			$result = mysql_query($query) or pupe_error("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				$apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
			}
		}

		unset($data);

		if((int) $apu_yhtiorow["lasku_logo"] > 0) {
			$liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
			$data = $liite["data"];
			$isizelogo[0] = $liite["image_width"];
			$isizelogo[1] = $liite["image_height"];
			unset($liite);
		}
		elseif(file_exists($apu_yhtiorow["lasku_logo"])) {
			$filename = $apu_yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
		}

		if(isset($data) !== FALSE and $yhtiorow["oslapp_rakir_logo"] == '') {
			$image = $pdf->jfif_embed($data);

			if ($image) {
				$logoparam = array();

				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
					$logoparam['scale'] = 240 / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = 50  / $isizelogo[1];
				}

				$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $firstpage, $logoparam);
			}

			//osoitetiedot yms
			//toimittaja
			$pdf->draw_rectangle(769,60,752,300,											$firstpage, $rectparam);
			$pdf->draw_text(62,  763,  "L‰hett‰j‰ Avs‰ndare",								$firstpage, $norm);
			$pdf->draw_text(240, 763,  "Asiakasnro Kundnr",									$firstpage, $norm);
			$pdf->draw_text(65,  755,  $postirow["yhtio_nimi"],								$firstpage, $kirj);
			$pdf->draw_text(240, 755,  $toitarow['sopimusnro'],								$firstpage, $kirj);
			$pdf->draw_rectangle(752,60,740,300,											$firstpage, $rectparam);
			$pdf->draw_text(65,  743,  $postirow["yhtio_osoite"],							$firstpage, $kirj);
			$pdf->draw_rectangle(740,60,728,300,											$firstpage, $rectparam);
			$pdf->draw_text(65,  731,  $postirow["yhtio_postino"]."  ".$postirow["yhtio_postitp"]."  ".$postirow["yhtio_maa"]."    ".$yhtiorow["puhelin"],		$firstpage, $kirj);

			//vastaanottaja
			$pdf->draw_rectangle(713,60,696,300,											$firstpage, $rectparam);
			$pdf->draw_text(62,  707,  "Vastaanottaja Mottagare",							$firstpage, $norm);
			$pdf->draw_text(240, 707,  "Asiakasnro Kundnr",									$firstpage, $norm);
			$pdf->draw_text(65,  699,  $rakir_row["nimi"],									$firstpage, $kirj);
			$pdf->draw_text(240, 699,  $rakir_row['rahtisopimus'],							$firstpage, $kirj);
			$pdf->draw_rectangle(696,60,684,300,											$firstpage, $rectparam);
			$pdf->draw_text(65,  687,  $rakir_row["osoite"],								$firstpage, $kirj);
			$pdf->draw_rectangle(684,60,672,300,											$firstpage, $rectparam);
			$pdf->draw_text(65,  675,  $rakir_row["postino"]."  ".$rakir_row["postitp"]."  ".$rakir_row["maa"]."    ".$rakir_row["puhelin"],	$firstpage, $kirj);

			//toimitusosoite
			$pdf->draw_rectangle(657,60,640,300,												$firstpage, $rectparam);
			$pdf->draw_rectangle(640,60,628,300,												$firstpage, $rectparam);

			$pdf->draw_text(62,  651, "Tavaran toimitusosoite:",								$firstpage, $norm);
			$pdf->draw_text(65,  643,  $rakir_row['toim_nimi']." ".$rakir_row['toim_nimitark'],	$firstpage, $kirj);
			$pdf->draw_text(65,  631,  $rakir_row['toim_osoite'],								$firstpage, $kirj);

		}
		else {
			//osoitetiedot yms
		  	//toimittaja
		   	$pdf->draw_rectangle(810,60,777,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(62,  803,  "L‰hett‰j‰ Avs‰ndare",								$firstpage, $norm);
		   	$pdf->draw_text(240, 803,  "Asiakasnro Kundnr",									$firstpage, $norm);
		   	$pdf->draw_text(65,  782,  $postirow["yhtio_nimi"],  							$firstpage, $kirj);
		   	$pdf->draw_text(240, 782,  $toitarow['sopimusnro'],								$firstpage, $kirj);
		   	$pdf->draw_rectangle(777,60,755,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(65,  758,  $postirow["yhtio_osoite"],							$firstpage, $kirj);
		   	$pdf->draw_rectangle(755,60,733,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(65,  736,  $postirow["yhtio_postino"]."  ".$postirow["yhtio_postitp"]."  ".$postirow["yhtio_maa"]."    ".$yhtiorow["puhelin"],		$firstpage, $kirj);

		   	//vastaanottaja
		   	$pdf->draw_rectangle(733,60,711,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(62,  726,  "Vastaanottaja Mottagare",							$firstpage, $norm);
		   	$pdf->draw_text(240, 726,  "Asiakasnro Kundnr",									$firstpage, $norm);
		   	$pdf->draw_text(65,  714,  $rakir_row["nimi"],									$firstpage, $kirj);
		   	$pdf->draw_text(240, 714,  $rakir_row['rahtisopimus'],							$firstpage, $kirj);
		   	$pdf->draw_rectangle(711,60,689,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(65,  692,  $rakir_row["osoite"],								$firstpage, $kirj);
		   	$pdf->draw_rectangle(689,60,667,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(65,  670,  $rakir_row["postino"]."  ".$rakir_row["postitp"]."  ".$rakir_row["maa"]."    ".$rakir_row["puhelin"],	$firstpage, $kirj);

		   	//toimitusosoite
		   	$pdf->draw_rectangle(667,60,645,300,											$firstpage, $rectparam);
		   	$pdf->draw_rectangle(645,60,623,300,											$firstpage, $rectparam);
		   	$pdf->draw_text(62,  661, "Tavaran toimitusosoite",								$firstpage, $norm);
		   	$pdf->draw_text(65,  648,  $rakir_row['toim_nimi']." ".$rakir_row['toim_nimitark'],	$firstpage, $kirj);
		   	$pdf->draw_text(65,  626,  $rakir_row['toim_osoite'],							$firstpage, $kirj);
		}

		//oikeeta
		$pdf->draw_text(302,  803,  "L‰hetysp‰iv‰m‰‰r‰ Avs‰ndningsdatum",				$firstpage, $norm);
		$pdf->draw_text(302,  793,  date("d.m.Y"),										$firstpage, $kirj);
		$pdf->draw_text(302,  782,  "L‰hett‰j‰n viite Avs‰ndarens referens",			$firstpage, $norm);
		$pdf->draw_text(302,  772,  $viitelah,											$firstpage, $kirj);
		$pdf->draw_text(302,  761,  "Vastaanottajan viite Mottagarens referens",		$firstpage, $norm);

		if (!isset($GLOBALS['tyhja'])) {
			$query = "	SELECT group_concat(trim(viesti) SEPARATOR ', ') viesti
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and tunnus in ($otunnukset)
						order by tunnus ";
			$viestires = mysql_query($query) or pupe_error($query);
			$viestirow = mysql_fetch_array($viestires);
		}
		else {
			$viestirow = array(
				'viesti' => $viitevas,
			);
		}

		// Jos rivikommentti on pitk‰ niin rivit‰mme sen
		if ($pdf->strlen($viestirow["viesti"], $kirj) > 240) {
			$vie1 = pdf_substr($viestirow["viesti"], 240, $pdf, $kirj);
			$vie2 = pdf_substr(substr($viestirow["viesti"],strlen($vie1)), 240, $pdf, $kirj);

			$pdf->draw_text(302,  751,  $vie1, $firstpage, $kirj);
			$pdf->draw_text(302,  741,  $vie2, $firstpage, $kirj);
		}
		else {
			$pdf->draw_text(302,  751,  $viestirow["viesti"], $firstpage, $kirj);
		}

		//oikee
		if ($toitarow['rahdinkuljettaja'] == '') {
			$toitarow['rahdinkuljettaja'] = $toitarow['selite'];
		}

		$pdf->draw_rectangle(733,300,667,545,												$firstpage, $rectparam);
		$pdf->draw_text(302,  726,  "Rahdinkuljettaja ja / tai huolitsijaTransportfˆretag och / eller Speditˆr", $firstpage, $norm);
		$pdf->draw_text(302, 670,  $toitarow['rahdinkuljettaja'],							$firstpage, $kirj);

		//oikee
		//t‰h‰n viivakoodia
		$pdf->draw_rectangle(667,300,623,545,											$firstpage, $rectparam);

		//Lastauspaikka
		$pdf->draw_rectangle(623,60,535,300,											$firstpage, $rectparam);
		$pdf->draw_text(62,  617, "L‰htˆ- ja lastauspaikka",							$firstpage, $norm);
		$pdf->draw_text(65,  593,  $postirow["yhtio_postino"]."   ".strtoupper($postirow["yhtio_postitp"])."   ".strtoupper($postirow["yhtio_maa"]),		$firstpage, $kirj);
		$pdf->draw_text(62,  573, "M‰‰r‰paikan postinumero ja -toimipaikka",			$firstpage, $norm);
		$pdf->draw_text(65,  550,  $rakir_row["toim_postino"]." ".strtoupper($rakir_row["toim_postitp"])."   ".strtoupper($rakir_row["toim_maa"]), $firstpage, $iso);

		//oikeeta laitaa
		$pdf->draw_text(302,  617, "Toimituslauseke Leveransklausul",					$firstpage, $norm);
		$pdf->draw_text(302,  582, $postirow["toimitusehto"],							$firstpage, $kirj);
		$pdf->draw_rectangle(579,300,579,545,											$firstpage, $rectparam);
		$pdf->draw_text(302,  573, "Rahdinmaksaja Fraktbetalare",						$firstpage, $norm);
		$pdf->draw_text(302,  559, $rahdinmaksaja,										$firstpage, $kirj);
		$pdf->draw_text(450,  573, "Asiakasnro Kundnr",									$firstpage, $norm);
		$pdf->draw_text(450,  559, $rahdinmaksajan_nr,									$firstpage, $kirj);

		$pdf->draw_rectangle(557,300,557,545,											$firstpage, $rectparam);
		$pdf->draw_rectangle(557,300,535,420,											$firstpage, $rectparam);
		$pdf->draw_rectangle(535,300,535,545,											$firstpage, $rectparam);

		//kollitietot
		//yl‰rivi
		$pdf->draw_text(62,  529, "Merkki / nro",										$firstpage, $norm);
		$pdf->draw_text(62,  523, "M‰rke / nr",											$firstpage, $norm);
		$pdf->draw_rectangle(535,181,520,181,											$firstpage, $rectparam);
		$pdf->draw_text(183,  529, "Kolliluku ja -laji",								$firstpage, $norm);
		$pdf->draw_text(183,  523, "Kolliantal och -slag",								$firstpage, $norm);
		$pdf->draw_rectangle(535,242,520,242,											$firstpage, $rectparam);
		$pdf->draw_text(246,  529, "Sis‰ltˆ, ulkomitat ja VAK-merkinn‰t",				$firstpage, $norm);
		$pdf->draw_text(246,  523, "InnehÂll, yttermÂtt och ADR-anm‰rkningar",			$firstpage, $norm);
		$pdf->draw_rectangle(535,420,520,420,											$firstpage, $rectparam);
		$pdf->draw_text(422,  529, "Brutto kg",											$firstpage, $norm);
		$pdf->draw_rectangle(535,484,520,484,											$firstpage, $rectparam);
		$pdf->draw_text(486,  529, "Tilavuus, m3",										$firstpage, $norm);
		$pdf->draw_text(486,  523, "Tilavuus, m3",										$firstpage, $norm);

		foreach ($kilot as &$arvoround) {
			$arvoround = round($arvoround,2);
		}

		foreach ($kollit as &$arvoround) {
			$arvoround = round($arvoround);
		}

		foreach ($kuutiot as &$arvoround) {
			$arvoround = round($arvoround,4);
		}

		//kollit
		$pdf->draw_text(62, 510, $lotsikot[0],											$firstpage, $kirj);
		if ($astilnrot[0] != '') $pdf->draw_text(62, 502, t("Astilnro").": ".$astilnrot[0],	$firstpage, $kirj);

		$pdf->draw_text(183, 510, $kollit[0]."  ". $pakkaus[0],							$firstpage, $kirj);
		$pdf->draw_text(246, 510, trim($pakkauskuvaus[0]." ".$pakkauskuvaustark[0]),	$firstpage, $kirj);
		$pdf->draw_text(246, 500, $vakit[0]." ".$lisatiedot[0],							$firstpage, $kirj);
		$pdf->draw_text(422, 510, $kilot[0],											$firstpage, $kirj);
		$pdf->draw_text(486, 510, $kuutiot[0],											$firstpage, $kirj);

		$pdf->draw_text(62, 490, $lotsikot[1],											$firstpage, $kirj);
		if ($astilnrot[1] != '') $pdf->draw_text(62, 482, t("Astilnro").": ".$astilnrot[1],	$firstpage, $kirj);
		$pdf->draw_text(183, 490, $kollit[1]."  ". $pakkaus[1],							$firstpage, $kirj);
		$pdf->draw_text(246, 490, trim($pakkauskuvaus[1]." ".$pakkauskuvaustark[1]),	$firstpage, $kirj);
		$pdf->draw_text(246, 480, $vakit[1]." ".$lisatiedot[1],							$firstpage, $kirj);
		$pdf->draw_text(422, 490, $kilot[1],											$firstpage, $kirj);
		$pdf->draw_text(486, 490 , $kuutiot[1],											$firstpage, $kirj);


		$pdf->draw_text(62, 470, $lotsikot[2],											$firstpage, $kirj);
		if ($astilnrot[2] != '') $pdf->draw_text(62, 462, t("Astilnro").": ".$astilnrot[2],	$firstpage, $kirj);
		$pdf->draw_text(183, 470, $kollit[2]."  ". $pakkaus[2],							$firstpage, $kirj);
		$pdf->draw_text(246, 470, trim($pakkauskuvaus[2]." ".$pakkauskuvaustark[2]),	$firstpage, $kirj);
		$pdf->draw_text(246, 460, $vakit[2]." ".$lisatiedot[2],							$firstpage, $kirj);
		$pdf->draw_text(422, 470, $kilot[2],											$firstpage, $kirj);
		$pdf->draw_text(486, 470, $kuutiot[2],											$firstpage, $kirj);

		$pdf->draw_text(62, 450, $lotsikot[3],											$firstpage, $kirj);
		if ($astilnrot[3] != '') $pdf->draw_text(62, 442, t("Astilnro").": ".$astilnrot[3],	$firstpage, $kirj);
		$pdf->draw_text(183, 450, $kollit[3]."  ". $pakkaus[3],							$firstpage, $kirj);
		$pdf->draw_text(246, 450, trim($pakkauskuvaus[3]." ".$pakkauskuvaustark[3]),	$firstpage, $kirj);
		$pdf->draw_text(246, 440, $vakit[3]." ".$lisatiedot[3],							$firstpage, $kirj);
		$pdf->draw_text(422, 450, $kilot[3],											$firstpage, $kirj);
		$pdf->draw_text(486, 450, $kuutiot[3],											$firstpage, $kirj);

		$pdf->draw_text(62, 430, $lotsikot[4],											$firstpage, $kirj);
		if ($astilnrot[4] != '') $pdf->draw_text(62, 422, t("Astilnro").": ".$astilnrot[4],	$firstpage, $kirj);
		$pdf->draw_text(183, 430, $kollit[4]."  ". $pakkaus[4],							$firstpage, $kirj);
		$pdf->draw_text(246, 430, trim($pakkauskuvaus[4]." ".$pakkauskuvaustark[4]),	$firstpage, $kirj);
		$pdf->draw_text(246, 420, $vakit[4]." ".$lisatiedot[4],							$firstpage, $kirj);
		$pdf->draw_text(422, 430, $kilot[4],											$firstpage, $kirj);
		$pdf->draw_text(486, 430, $kuutiot[4],											$firstpage, $kirj);


		//alarivi
		$pdf->draw_text(183, 404 , "Kollit yht. Kolliantal tot.",						$firstpage, $norm);
		$pdf->draw_text(183, 390 , $kollityht,											$firstpage, $kirj);
		$pdf->draw_rectangle(410,181,388,242,											$firstpage, $rectparam);
		$pdf->draw_text(365, 404 , "Lavametrit Flakmeter",								$firstpage, $norm);
		$pdf->draw_text(365, 390 , $lavametri[0],										$firstpage, $norm);
		$pdf->draw_rectangle(410,363,388,420,											$firstpage, $rectparam);
		$pdf->draw_text(422, 404 , "Brutto yht. total., kg Rahditysp.  Fraktvikt",		$firstpage, $norm);
		$pdf->draw_text(422, 390 , $kilotyht,											$firstpage, $kirj);
		//v‰h‰ vedet‰‰ viivaa
		$pdf->draw_rectangle(410,60,410,545,											$firstpage, $rectparam);
		$pdf->draw_rectangle(388,60,388,545,											$firstpage, $rectparam);

		//kuljetusohjeita
		$pdf->draw_text(62, 382 , "Kuljetusohjeet Transportinstruktioner",				$firstpage, $norm);

		if ($postirow['tilausyhteyshenkilo'] != "") {
			$pdf->draw_text(62, 372 , "Tilausyhteyshenkilˆ:",							$firstpage, $kirj);
			$pdf->draw_text(135, 372 , $postirow['tilausyhteyshenkilo'],				$firstpage, $kirj);
		}

		$pdf->draw_paragraph(368, 62, 325, 410, $kuljetusohjeet,						$firstpage, $kirj);

		$pdf->draw_text(62, 311 , "Muut tiedot Till‰gsuppgifter",						$firstpage, $norm);

		if (is_resource($lisatiedres)) {
			$ahven = 311;

			while($lisatiedot = mysql_fetch_array($lisatiedres)) {
				if($lisatiedot["nimi"] != "") {
					$ahven -= 10;
					$pdf->draw_text(62, $ahven , trim($lisatiedot["nimi"]." ".$lisatiedot["puh"]),										$firstpage, $norm);
				}
			}
		}

		//laatikot
		$pdf->draw_text(305, 325 , "Lis‰t eriteltyn‰:",									$firstpage, $norm);
		$pdf->draw_rectangle(278,303,256,420,											$firstpage, $rectparam);
		$pdf->draw_text(305, 272 , "Muu palvelu",										$firstpage, $norm);
		$pdf->draw_rectangle(300,303,278,420,											$firstpage, $rectparam);
		$pdf->draw_text(305, 294 , "Jakelu",											$firstpage, $norm);
		$pdf->draw_rectangle(322,303,300,420,											$firstpage, $rectparam);
		$pdf->draw_text(305, 316 , "Nouto",												$firstpage, $norm);

		//oikee eli j‰kivaatimus
		$pdf->draw_rectangle(388,420,322,420,											$firstpage, $rectparam);
		$pdf->draw_text(422, 382 , "J‰lkivaatimus, maksuviite Bet.ref. Fˆr efterkrav",	$firstpage, $norm);
		$pdf->draw_text(422, 369 , $postiennakkoviite,									$firstpage, $kirj);
		$pdf->draw_rectangle(366,420,366,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 360 , "J‰lkivaatimus, tilinro Kontonr fˆr efterkrav", 	    $firstpage, $norm);
		$pdf->draw_text(422, 347 , $postiennakkotilino,									$firstpage, $kirj);
		$pdf->draw_rectangle(344,420,344,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 338 , "J‰lkivaat. Efterkrav",                        		$firstpage, $norm);
		$pdf->draw_text(422, 325 , $postiennakkomaara,									$firstpage, $kirj);
		$pdf->draw_rectangle(322,420,322,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 316 , "Rahti Frakt", 					             		$firstpage, $norm);
		$pdf->draw_rectangle(300,420,300,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 294 , "Lis‰t yht. Extra avgift tot.", 			       		$firstpage, $norm);
		$pdf->draw_rectangle(278,420,278,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 272 , "+ Alv. Moms",										$firstpage, $norm);
		//poikkiviiva
		$pdf->draw_rectangle(256,60,256,545,											$firstpage, $rectparam);
		//SFS
		$pdf->draw_text(25, 272 , "SFS",												$firstpage, $kirj);
		$pdf->draw_text(25, 262 , "5865",												$firstpage, $kirj);


		//varaumat
		$pdf->draw_text(62, 250 , "Varaumat FˆrbehÂll",									$firstpage, $norm);

		//alarivi
		$pdf->draw_rectangle(179,60,179,545,											$firstpage, $rectparam);
		$pdf->draw_text(62, 173 , "12 Nouto",											$firstpage, $norm);
		$pdf->draw_rectangle(179,112,146,164,											$firstpage, $rectparam);
		$pdf->draw_text(114, 173 , "14 Jakelu",											$firstpage, $norm);
		$pdf->draw_text(166, 173 , "27 LJP",											$firstpage, $norm);
		$pdf->draw_rectangle(179,216,146,268,											$firstpage, $rectparam);
		$pdf->draw_text(218, 173 , "26 VAK",											$firstpage, $norm);
		$pdf->draw_text(270, 173 , "23 Yks",											$firstpage, $norm);
		$pdf->draw_rectangle(179,320,146,372,											$firstpage, $rectparam);
		$pdf->draw_text(322, 173 , "40 Yks lkm",										$firstpage, $norm);
		$pdf->draw_text(374, 173 , "69 Lis‰maksu",										$firstpage, $norm);

		//oikee laita
		$pdf->draw_text(422, 250 , "K‰teinen yht. Kontant tot.",						$firstpage, $norm);
		$pdf->draw_rectangle(234,420,234,545,											$firstpage, $rectparam);
		$pdf->draw_text(422, 228 , "K‰teismaksu, pvm,",									$firstpage, $norm);
		$pdf->draw_text(422, 222 , "paikka ja kuittaus",								$firstpage, $norm);
		$pdf->draw_text(422, 173 , "Krnro, kotip. HRnr, hemort/LY-tunnus AS-signum",	$firstpage, $norm);


		//poikki ja pystyviivaa
		$pdf->draw_rectangle(146,60,146,545,											$firstpage, $rectparam);
		$pdf->draw_rectangle(256,420,146,420,			 								$firstpage, $rectparam);


		//allekirjoitukset
		$pdf->draw_text(62, 140 , "Kuljettajan luovutuskuittaus ja pvm. Chauffˆrens underskrift och dat", $firstpage, $norm);
		$pdf->draw_text(62, 134 , "Vastaanottajan allekirjoitus. Mottagarens underskift.", $firstpage, $norm);
		$pdf->draw_rectangle(146,242,55,242,											$firstpage, $rectparam);
		$pdf->draw_text(244, 140 , "Otettu kuljetettavaksi pvm ja kuljettajan vastaanottokuittaus",	$firstpage, $norm);
		$pdf->draw_text(244, 134 , "Chauffˆr, datum och underskrift",					$firstpage, $norm);
		$pdf->draw_rectangle(146,420,55,420,											$firstpage, $rectparam);
		$pdf->draw_text(422, 140 , "L‰hett‰j‰, pvm ja allekirjoitus",					$firstpage, $norm);
		$pdf->draw_text(422, 134 , "Avs‰ndare, datum och underskrift",					$firstpage, $norm);

		$pdf->draw_text(244, 42 , "Nimenselvennykset Namnfˆrtydliganden",				$firstpage, $kirj);

		//noita ulkokeh‰n viivoja ja v‰h‰ muuta
		$pdf->draw_rectangle(535,60,55,60,												$firstpage, $rectparam);
		$pdf->draw_text(25, 70 , $tulostuskpl,											$firstpage, $huge);
		if	($tulostuskpl == 1) $pdf->draw_text(25, 30 , "Kuljetusliike",				$firstpage, $kirj);
		if	($tulostuskpl == 2) $pdf->draw_text(25, 30 , "Kuljetusliike",				$firstpage, $kirj);
		if	($tulostuskpl == 3) $pdf->draw_text(25, 30 , "Vastaanottaja",				$firstpage, $kirj);
		if	($tulostuskpl == 4) $pdf->draw_text(25, 30 , "L‰hett‰j‰",					$firstpage, $kirj);

	}

	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
	fclose($fh);

	if ($kirjoitin == "email") {
		$liite = $pdffilenimi;
		$kutsu = "PDF-rahtikirja";
		require("inc/sahkoposti.inc");

	}
	elseif ($kirjoitin == 'PDF_RUUDULLE') {
		//Tyˆnnet‰‰n tuo pdf vaan putkeen!
		echo file_get_contents($pdffilenimi);
	}
	else {
		//itse print komento...
		$line = exec("$kirjoitin $pdffilenimi");
	}

	system("rm -f $pdffilenimi");

?>