<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require("../inc/parametrit.inc");
	require("tulosta_vuosisopimusasiakkaat.inc");
	require_once '../email.php';

	echo "<font class='head'>Vuosisopimusasiakkaat</font><hr>";

	if ($ytunnus != "" and $asiakasid == "") {
		if ($muutparametrit == '') {
			$muutparametrit = "$komento#$raja#$emailok#$alkupp#$alkukk#$alkuvv#$loppupp#$loppukk#$loppuvv#";
		}

		require ("inc/asiakashaku.inc");

		//jos tulee yksi asiakas tieto
		if ($monta != 1) {
			$tee = '';
		}
	}

	if (isset($muutparametrit) and $tee != '' and $asiakasid != '') {
		list($komento,$raja,$emailok,$alkupp,$alkukk,$alkuvv,$loppupp,$loppukk,$loppuvv) = explode('#', $muutparametrit);
	}

	if ($tee == "tulosta" and $komento == "" and $ytunnus == "") {
		echo "<font class='error'>VALITSE TULOSTIN!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta" and $raja == "") {
		echo "<font class='error'>RAJA PUUTTUU!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta" and (!checkdate($alkukk, $alkupp, $alkuvv) or !checkdate($loppukk, $loppupp, $loppuvv))) {
		echo "<font class='error'>PVM RAJAT PUUTTUU, TAI NE ON VIRHEELLISET!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta") {

		// haetaan aluksi sopivat asiakkaat
		// viimeisen 12 kuukauden myynti pitää olla yli $rajan
		echo "<font class='message'>Haetaan sopivia asiakkaita (myynti $alkupvm - $loppupvm yli $raja)... ";

		$params = array(
			'ytunnus' => $ytunnus,
			'asiakasid' => $asiakasid,
			'alkuvv' => $alkuvv,
			'alkukk' => $alkukk,
			'alkupp' => $alkupp,
			'loppuvv' => $loppuvv,
			'loppukk' => $loppukk,
			'loppupp' => $loppupp,
			'raja' => $raja
		);
		$asiakkaat = hae_asiakkaat($params);

		flush();

		$edalkupvm  = date("Y-m-d", mktime(0, 0, 0, $alkukk,  $alkupp,  $alkuvv - 1));
		$edloppupvm = date("Y-m-d", mktime(0, 0, 0, $loppukk, $loppupp, $loppuvv - 1));

		$params = array(
			'alkuvv' => $alkuvv,
			'alkukk' => $alkukk,
			'alkupp' => $alkupp,
			'loppuvv' => $loppuvv,
			'loppukk' => $loppukk,
			'loppupp' => $loppupp,
			'edalkupvm' => $edalkupvm,
			'edloppupvm' => $edloppupvm,
		);

		$data_array = array();
		foreach($asiakkaat as $asiakas) {
			$tilaukset_ilman_tuoteryhmia = hae_tilaukset_ilman_tuoteryhmia($params, $asiakas['tunnus']);
			
			$summa_array = array(
				'sumkpled' => 0,
				'sumkplva' => 0,
				'sumed' => 0,
				'sumva' => 0,
			);
			foreach($tilaukset_ilman_tuoteryhmia as $tilaus) {
				if($tilaus['osasto'] < 10000) {
					$summa_array['sumkpled'] += $tilaus['kpled'];
					$summa_array['sumkplva'] += $tilaus['kplva'];
					$summa_array['sumed'] += $tilaus['ed'];
					$summa_array['sumva'] += $tilaus['va'];
				}
			}

			$tilaukset_tuoteryhmilla = hae_tilaukset_tuoteryhmilla($params, $asiakas['tunnus']);

			$summa_array2 = array(
				'sumkpled' => 0,
				'sumkplva' => 0,
				'sumed' => 0,
				'sumva' => 0,
			);
			foreach ($tilaukset_tuoteryhmilla as $tilaus) {
				if($tilaus['osasto'] < 10000) {
					$summa_array2['sumkpled'] += $tilaus['kpled'];
					$summa_array2['sumkplva'] += $tilaus['kplva'];
					$summa_array2['sumed'] += $tilaus['ed'];
					$summa_array2['sumva'] += $tilaus['va'];
				}
			}

			$data_array[] = array(
				'asiakasrow' => $asiakas,
				'tilaukset_ilman_try' => $tilaukset_ilman_tuoteryhmia,
				'summat_ilman_try' => $summa_array,
				'tilaukset_try' => $tilaukset_tuoteryhmilla,
				'summat_try' => $summa_array2,
			);
		}

		kasittele_tilaukset($data_array, htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $komento, $params);

		echo "<br>Kaikki valmista.</font>";

	} // end tee == tulosta

	if ($tee == '') {

		if (!isset($alkupp))  $alkupp  = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkukk))  $alkukk  = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkuvv))  $alkuvv  = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));

		if (!isset($loppupp)) $loppupp = date("d");
		if (!isset($loppukk)) $loppukk = date("m");
		if (!isset($loppuyy)) $loppuvv = date("Y");

		echo "<font class='message'>Jos asiakkaalla tai sen myyjällä ei ole sähköpostia, raportit lähetetään sähköpostiin tai tulostetaan haluamaasi tulostimeen riippuen tulostimen valinnasta.</font><br><br>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='tulosta'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type ='hidden' name='muutparametrit' value='$muutparametrit'>";

		echo "<table>";
		echo "<tr><th>Valitse tulostin:</th>";
		echo "<td><select name='komento'>";
		echo "<option value=''>Ei kirjoitinta</option>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow = mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>Syötä ostoraja:</th>";
		echo "<td><input type='text' name='raja' value='10000' size='10'> $yhtiorow[valkoodi] valitulla ajanjaksolla</td></tr>";
		echo "<tr>";
		echo "<th>Lähetä sähköpostit:</th>";
		echo "<td>
				<input type='radio' name='laheta_sahkopostit' value='ajajalle'>Ohjelman ajajalle<br>
				<input type='radio' name='laheta_sahkopostit' value='asiakkaalle'>Asiakkaalle<br>
				<input type='radio' name='laheta_sahkopostit' value='asiakkaan_myyjalle'>Asiakkaan myyjälle<br>
			</td>";
		echo "</tr>";
		echo "<tr><th>Asiakasnumero:</th>";
		echo "<td><input type='text' name='ytunnus' size='10'> aja vain tämä asiakas (tyhjä=kaikki)</td></tr>";
		echo "<tr><th>Alku päivämäärä:</th>";
		echo "<td>";
		echo "<input type='text' name='alkupp' value='$alkupp' size='10'>";
		echo "<input type='text' name='alkukk' value='$alkukk' size='10'>";
		echo "<input type='text' name='alkuvv' value='$alkuvv' size='10'> pp kk vvvv</td></tr>";
		echo "<tr><th>Loppu päivämäärä:</th>";
		echo "<td>";
		echo "<input type='text' name='loppupp' value='$loppupp' size='10'>";
		echo "<input type='text' name='loppukk' value='$loppukk' size='10'>";
		echo "<input type='text' name='loppuvv' value='$loppuvv' size='10'> pp kk vvvv</td></tr>";
		echo "</table>";

		echo "<br><input type='submit' value='Tulosta'></form>";

	}

	function hae_asiakkaat($params) {
		global $kukarow;

		//valittu asiakas
		if ($params['ytunnus'] != '' and $params['asiakasid'] != "") {
			$asnum = $params['ytunnus'];
			echo "vain asiakas ytunnus: {$params['ytunnus']}...<br> ";
			$aswhere = "and lasku.liitostunnus = '{$params['asiakasid']}'";
		}
		else {
			$aswhere = "";
		}

		$query = "	SELECT asiakas.tunnus, asiakas.email, asiakas.ytunnus, asiakas.asiakasnro, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp, sum(arvo) arvo, kuka.eposti myyja_eposti
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
					JOIN kuka ON (kuka.yhtio = asiakas.yhtio AND kuka.myyja = asiakas.myyjanro)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and lasku.alatila = 'X'
					and lasku.tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'
					and lasku.tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}'
					and asiakas.myyjanro != 0
					$aswhere
					GROUP BY asiakas.tunnus
					HAVING arvo > {$params['raja']}";
		$result = mysql_query($query) or pupe_error($query);

		echo "löytyi ".mysql_num_rows($result)." asiakasta.<br>";

		$asiakkaat = array();
		while($row = mysql_fetch_assoc($result)) {
			$asiakkaat[] = $row;
		}

		return $asiakkaat;
	}

	function hae_tilaukset_ilman_tuoteryhmia($params, $asiakas_tunnus) {
		global $kukarow;

		$query = "	SELECT tuote.osasto,
						sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'		and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.rivihinta, 0)) va,
						sum(if (tapvm >= '{$params['edalkupvm']}'											and tapvm <= '{$params['edloppupvm']}', tilausrivi.rivihinta, 0)) ed,
						sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'		and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.kpl, 0)) kplva,
						sum(if (tapvm >= '{$params['edalkupvm']}'											and tapvm <= '{$params['edloppupvm']}', tilausrivi.kpl, 0)) kpled
						FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
						JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.try > 0)
						JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.liitostunnus = '{$asiakas_tunnus}'
						AND lasku.tapvm >= '{$params['edalkupvm']}'
						AND lasku.tila = 'L'
						AND lasku.alatila = 'X'
						GROUP BY osasto
						HAVING va != 0 OR ed != 0 OR kplva != 0 OR kpled != 0
						ORDER BY osasto";
		$result = pupe_query($query);

		$laskut = array();
		while($row = mysql_fetch_assoc($result)) {
			$laskut[] = $row;
		}
		return $laskut;
	}

	function hae_tilaukset_tuoteryhmilla($params, $asiakas_tunnus) {
		global $kukarow;

		$query = "	SELECT tuote.osasto, tuote.try,
						sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'		and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.rivihinta, 0)) va,
						sum(if (tapvm >= '{$params['edalkupvm']}'											and tapvm <= '{$params['edloppupvm']}', tilausrivi.rivihinta, 0)) ed,
						sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'		and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.kpl, 0)) kplva,
						sum(if (tapvm >= '{$params['edalkupvm']}'											and tapvm <= '{$params['edloppupvm']}', tilausrivi.kpl, 0)) kpled
						FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
						JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.try > 0)
						JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.liitostunnus = '{$asiakas_tunnus}'
						AND lasku.tapvm >= '{$params['edalkupvm']}'
						AND lasku.tila = 'L'
						AND lasku.alatila = 'X'
						GROUP BY osasto, try
						HAVING va != 0 OR ed != 0 OR kplva != 0 OR kpled != 0
						ORDER BY osasto, try";
		$result = pupe_query($query);

		$laskut = array();
		while($row = mysql_fetch_assoc($result)) {
			$laskut[] = $row;
		}

		return $laskut;
	}

	function kasittele_tilaukset($data_array, $laheta_sahkopostit, $komento, $params) {
		global $kukarow;

		$pdf_tiedostot = generoi_pdf_tiedostot($data_array, $params);

		switch ($laheta_sahkopostit) {
		case 'ajajalle':
			$email = $kukarow['eposti'];
			//ajajalle ja asiakaan_myyjalle lähetettäessä sähköpostilla, raportit halutaan zippiin ja yhteen emailiin. muilla tavoilla normi komennolla
			if($komento == 'email') {
				luo_zip_ja_laheta($pdf_tiedostot, $email);
			}
			else {
				tulosta_pdf_raportit($pdf_tiedostot, $komento);
			}
			break;
		case 'asiakkaalle':
			if($komento == 'email') {
				//laheta_raportit(htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $edemail, $edasiakasno, $pdffilenimi, $komento);
			}
			else {
				tulosta_pdf_raportit($pdf_tiedostot, $komento);
			}
			break;
		case 'asiakkaan_myyjalle':
			$edemail = $myyja_eposti;
			if($komento == 'email') {
				luo_zip_ja_laheta($pdf_tiedostot, $email);
			}
			else {
				tulosta_pdf_raportit($pdf_tiedostot, $komento);
			}
			break;
		default:
			//lähetetään ajajalle
			$edemail = $kukarow['eposti'];
			//ajajalle ja asiakaan_myyjalle lähetettäessä sähköpostilla, raportit halutaan zippiin ja yhteen emailiin. muilla tavoilla normi komennolla
			if($komento == 'email') {
				luo_zip_ja_laheta($pdf_tiedostot, $email);
			}
			else {
				tulosta_pdf_raportit($pdf_tiedostot, $komento);
			}
		}
	}

	function generoi_pdf_tiedostot($data_array, $params) {
		global $pdf, $asiakasrow, $yhtiorow, $sivu, $norm, $pieni, $pvm, $alkuvv, $alkukk, $alkupp, $loppuvv, $loppukk, $loppupp, $kala, $sivu, $lask, $sumkpled, $sumkplva, $sumed, $sumva;

		$alkuvv = $params['alkuvv'];
		$alkukk = $params['alkukk'];
		$alkupp = $params['alkupp'];
		$loppuvv = $params['loppuvv'];
		$loppukk = $params['lopukk'];
		$loppupp = $params['loppupp'];

		$pdf_tiedostot = array();
		foreach ($data_array as $data) {
			$pdf = new pdffile();
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);

			// defaultteja layouttiin
			$kala = 575;
			$lask = 1;
			$sivu = 1;

			$asiakasrow = $data['asiakasrow'];
			// kirjotetaan header
			$firstpage = alku("osasto");

			rivi_kaikki($firstpage, 'osasto', $data['tilaukset_ilman_try']);

			$sumkpled = $data['summat_ilman_try']['sumkpled'];
			$sumkplva = $data['summat_ilman_try']['sumkplva'];
			$sumed = $data['summat_ilman_try']['sumed'];
			$sumva = $data['summat_ilman_try']['sumva'];
			// kirjotetaan footer
			loppu($firstpage, "dontsend");

			// defaultteja layouttiin
			$kala = 575;
			$lask = 1;
			$sivu = 1;

			// uus pdf header
			$firstpage = alku();

			rivi_kaikki($firstpage , '' , $data['tilaukset_try']);
			
			$sumkpled = $data['summat_try']['sumkpled'];
			$sumkplva = $data['summat_try']['sumkplva'];
			$sumed = $data['summat_try']['sumed'];
			$sumva = $data['summat_try']['sumva'];
			// kirjotetaan footer ja palautetaan luodun tiedoston polku
			$pdf_tiedostot[] = loppu($firstpage);
		}

		return $pdf_tiedostot;
	}

	function luo_zip_ja_laheta($pdf_tiedostot, $email_address) {
		global $yhtiorow;
		
		$maaranpaa = '/tmp/Ostoseuranta_raportit.zip';
		$ylikirjoita = true;//ihan varmuuden vuoks
		if(luo_zip($pdf_tiedostot, $maaranpaa, $ylikirjoita)) {
			//lähetetään email
			laheta_email($email_address, array($maaranpaa));

			//poistetaan zippi
			unlink($maaranpaa);
		}
		else {
			echo "Zipin luominen epäonnistui";
		}

		//poistetaan pdf tiedostot
		foreach($pdf_tiedostot as $tiedosto) {
			unlink($tiedosto);
		}
	}

	function laheta_email($email_address, array $liitetiedostot_path = array()) {
		global $yhtiorow;

		$aihe = utf8_encode($yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"));
		$viesti = utf8_encode('Liitteenä löytyy ostoseuranta raportit zip-tiedostoon pakattuna.<br/><br/>');

		$email = new Email($aihe, $yhtiorow['postittaja_email']);
		$email->add_vastaanottaja($email_address);

		foreach ($liitetiedostot_path as $liitetiedosto_path) {
			$liitetiedosto = array(
				'filename' => 'Ostoseuranta_raportit.zip',
				'path' => $liitetiedosto_path,
				'mime' => mime_content_type($liitetiedosto_path)
			);

			$email->add_liitetiedosto($liitetiedosto);
		}
		
		$email->set_html_viesti($viesti);

		$email->laheta();
	}

	function tulosta_pdf_raportit($pdf_tiedostot, $komento) {
		echo "Tulostetaan asiakkaan _ASIAKASNUMEROTAHAN_ ostoseuranta tulostimeen {$komento}";

		foreach ($pdf_tiedostot as $tiedosto) {
			$line = exec($komento." ".$tiedosto);

			//poistetaan tulostettu tiedosto
			unlink($tiedosto);
		}
	}

	function luo_zip($tiedostot = array(), $maaranpaa = '', $ylikirjoita = false) {
		//if the zip file already exists and overwrite is false, return false
		if (file_exists($maaranpaa) && !$ylikirjoita) {
			return false;
		}
		//vars
		$validit_tiedostot = array();
		//if files were passed in...
		if (is_array($tiedostot)) {
			//cycle through each file
			foreach ($tiedostot as $tiedosto) {
				//make sure the file exists
				if (file_exists($tiedosto)) {
					$validit_tiedostot[] = $tiedosto;
				}
			}
		}
		//if we have good files...
		if (count($validit_tiedostot)) {
			//create the archive
			$zip = new ZipArchive();
			if ($zip->open($maaranpaa, $ylikirjoita ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			//add the files
			foreach ($validit_tiedostot as $tiedosto) {
				$zip->addFile($tiedosto, $tiedosto);
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
			//close the zip -- done!
			$zip->close();

			//check to make sure the file exists
			return file_exists($maaranpaa);
		}
		else {
			return false;
		}
	}

	require ("../inc/footer.inc");

?>