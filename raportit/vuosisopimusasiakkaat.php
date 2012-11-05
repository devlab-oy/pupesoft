<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require "../inc/parametrit.inc";
	require "tulosta_vuosisopimusasiakkaat.inc";
	require_once 'tulosta_vuosisopimusasiakkaat_excel.inc';

	if($asiakas_tarkistus == 1) {
		$ajax_params = array(
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

		$asiakkaat = hae_asiakkaat($ajax_params);

		echo json_encode(count($asiakkaat));

		exit;
	}

	echo "<font class='head'>".t('Vuosisopimusasiakkaat' , $kieli)."</font><hr>";

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
		echo "<font class='error'>".t('VALITSE TULOSTIN' , $kieli)."!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta" and $raja == "") {
		echo "<font class='error'>".t('RAJA PUUTTUU' , $kieli)."!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta" and (!checkdate($alkukk, $alkupp, $alkuvv) or !checkdate($loppukk, $loppupp, $loppuvv))) {
		echo "<font class='error'>".t('PVM RAJAT PUUTTUU, TAI NE ON VIRHEELLISET', $kieli)."!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta") {

		// haetaan aluksi sopivat asiakkaat
		// viimeisen 12 kuukauden myynti pitää olla yli $rajan
		echo "<font class='message'>".t('Haetaan sopivia asiakkaita' , $kieli)." (".t('myynti', $kieli)." $alkupvm - $loppupvm ".t('yli' , $kieli)." $raja)... ";

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
			$tilaukset_ilman_tuoteryhmia = hae_tilaukset($params, $asiakas['tunnus'], 'ilman_tuoteryhmia');
			
			$summa_array = array(
				'sumkpled' => 0,
				'sumkplva' => 0,
				'sumed' => 0,
				'sumva' => 0,
			);
			foreach($tilaukset_ilman_tuoteryhmia as $tilaus) {
				$summa_array['sumkpled'] += $tilaus['kpled'];
				$summa_array['sumkplva'] += $tilaus['kplva'];
				$summa_array['sumed'] += $tilaus['ed'];
				$summa_array['sumva'] += $tilaus['va'];
			}

			$tilaukset_tuoteryhmilla = hae_tilaukset($params, $asiakas['tunnus'], '');

			$summa_array2 = array(
				'sumkpled' => 0,
				'sumkplva' => 0,
				'sumed' => 0,
				'sumva' => 0,
			);
			foreach ($tilaukset_tuoteryhmilla as $tilaus) {
				$summa_array2['sumkpled'] += $tilaus['kpled'];
				$summa_array2['sumkplva'] += $tilaus['kplva'];
				$summa_array2['sumed'] += $tilaus['ed'];
				$summa_array2['sumva'] += $tilaus['va'];
			}

			$data_array[] = array(
				'asiakasrow' => $asiakas,
				'tilaukset_ilman_try' => $tilaukset_ilman_tuoteryhmia,
				'summat_ilman_try' => $summa_array,
				'tilaukset_try' => $tilaukset_tuoteryhmilla,
				'summat_try' => $summa_array2,
			);
		}

		kasittele_tilaukset($data_array, htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $komento, $params, $generoi_excel, $kieli);

		echo "<br>".t('Kaikki valmista' , $kieli).".</font>";

	} // end tee == tulosta

	if ($tee == '') {

		if (!isset($alkupp))  $alkupp  = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkukk))  $alkukk  = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkuvv))  $alkuvv  = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));

		if (!isset($loppupp)) $loppupp = date("d");
		if (!isset($loppukk)) $loppukk = date("m");
		if (!isset($loppuyy)) $loppuvv = date("Y");

		echo "<font class='message'>".t('Jos asiakkaalla tai sen myyjällä ei ole sähköpostia, raportit lähetetään sähköpostiin tai tulostetaan haluamaasi tulostimeen riippuen tulostimen valinnasta' , $kieli).".</font><br><br>";

		echo "<form name='vuosiasiakkaat_form' method='post'>";
		echo "<input type='hidden' name='tee' value='tulosta'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type ='hidden' name='muutparametrit' value='$muutparametrit'>";

		echo "<table>";
		echo "<tr><th>".t('Valitse tulostin',$kieli).":</th>";
		echo "<td><select name='komento'>";
		echo "<option value=''>".t('Ei kirjoitinta',$kieli)."</option>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow = mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t('Syötä ostoraja' , $kieli).":</th>";
		echo "<td><input type='text' name='raja' value='10000' size='10'> $yhtiorow[valkoodi] valitulla ajanjaksolla</td></tr>";
		echo "<tr><th>".t('Generoi excel tiedosto').":</th><td><input type='checkbox' name='generoi_excel' /></td></tr>";
		echo "<tr>";
		echo "<th>".t('Lähetä sähköpostit', $kieli).":</th>";
		echo "<td>
				<input type='radio' name='laheta_sahkopostit' value='ajajalle'>".t('Ohjelman ajajalle' , $kieli)."<br>
				<input type='radio' name='laheta_sahkopostit' value='asiakkaalle'>".t('Asiakkaalle',$kieli)."<br>
				<input type='radio' name='laheta_sahkopostit' value='asiakkaan_myyjalle'>".t('Asiakkaan myyjälle',$kieli)."<br>
			</td>";
		echo "</tr>";
		echo "<tr><th>".t('Asiakasnumero',$kieli).":</th>";
		echo "<td><input type='text' name='ytunnus' size='10'> ".t('aja vain tämä asiakas',$kieli)." (".t('tyhjä',$kieli)."=".t('kaikki',$kieli).")</td></tr>";
		echo "<tr><th>".t('Alku päivämäärä',$kieli).":</th>";
		echo "<td>";
		echo "<input type='text' name='alkupp' value='$alkupp' size='10'>";
		echo "<input type='text' name='alkukk' value='$alkukk' size='10'>";
		echo "<input type='text' name='alkuvv' value='$alkuvv' size='10'> pp kk vvvv</td></tr>";
		echo "<tr><th>".t('Loppu päivämäärä',$kieli).":</th>";
		echo "<td>";
		echo "<input type='text' name='loppupp' value='$loppupp' size='10'>";
		echo "<input type='text' name='loppukk' value='$loppukk' size='10'>";
		echo "<input type='text' name='loppuvv' value='$loppuvv' size='10'> pp kk vvvv</td></tr>";
		echo "</table>";

		echo "<br><input type='submit' value='".t('Tulosta',$kieli)."' onclick='if(tarkista()){document.vuosiasiakkaat_form.submit();} else{return false;}'></form>";

		ob_start();
		?>
	<script>
				function tarkista() {
					var ok = true;

					if(!tarkista_tulostin()) {
						ok = false;
					}
					
					if(!tarkista_lahettaja() && ok != false) {
						ok = false;
					}
					return ok;
				}

				function tarkista_tulostin() {
					if(($('input[name=laheta_sahkopostit]:checked').val() == 'asiakkaalle' || $('input[name=laheta_sahkopostit]:checked').val() == 'asiakkaan_myyjalle') && $('select[name=komento]').val() != 'email') {
						alert('Asiakkaalle tai asiakkaan myyjälle raportteja lähetettäessä pitää tulostimeksi olla valittuna email');
						return false;
					}
					else {

						return true;
					}
				}

				function tarkista_lahettaja() {
					if($('input[name=laheta_sahkopostit]:checked').val() == 'asiakkaalle') {
						var ok;
						$.ajax({
							type: 'POST',
							url: 'vuosisopimusasiakkaat.php?asiakas_tarkistus=1&no_head=yes',
							data: {
								'ytunnus': '',
								'asiakasid': '',
								'raja': $('input[name=raja]').val(),
								'alkuvv': $('input[name=alkuvv]').val(),
								'alkukk': $('input[name=alkukk]').val(),
								'alkupp': $('input[name=alkupp]').val(),
								'loppuvv': $('input[name=loppuvv]').val(),
								'loppukk': $('input[name=loppukk]').val(),
								'loppupp': $('input[name=loppupp]').val()
							},
							success: function(data) {
								ok = confirm('Sähköposteja olisi lähdössä '+data+' kappaletta. Oletko varma, että haluat lähettää?');
							},
							async:false
						});

						if(ok) {
							return true;
						}
						else {
							return false;
						}
					}
					else {
						return true;
					}
				}
	</script>
		<?php

		$js = ob_get_clean();

		echo $js;
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

		$query = "	SELECT asiakas.tunnus, asiakas.email asiakas_email, asiakas.ytunnus, asiakas.asiakasnro, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp, sum(arvo) arvo, kuka.eposti myyja_eposti
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

		$asiakkaat = array();
		while($row = mysql_fetch_assoc($result)) {
			$asiakkaat[] = $row;
		}

		return $asiakkaat;
	}

	function hae_tilaukset($params, $asiakas_tunnus, $tyyppi) {
		global $kukarow;

		if($tyyppi == 'ilman_tuoteryhmia') {
			$select = "tuote.osasto,";
			$group = "osasto";
			$order = "osasto";
		}
		else {
			$select = "tuote.osasto, tuote.try,";
			$group = "osasto, try";
			$order = "osasto, try";
		}
		$query = "	SELECT {$select},
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
						GROUP BY {$group}
						HAVING va != 0 OR ed != 0 OR kplva != 0 OR kpled != 0
						ORDER BY {$order}";
		$result = pupe_query($query);

		$tilaukset = array();
		while($row = mysql_fetch_assoc($result)) {
			if($row['osasto'] < 10000) {
				if($tyyppi == 'ilman_tuoteryhmia') {
					$tryre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
					$tryro = mysql_fetch_array($tryre);
					$row['asananumero'] = $row['osasto'];
					$row['tryro_selitetark'] = $tryro["selitetark"];
				}
				else {
					$tryre = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
					$tryro = mysql_fetch_array($tryre);
					$row['asananumero'] = $row['try'];
					$row['tryro_selitetark'] = $tryro["selitetark"];

					$osre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
					$osrow = mysql_fetch_array($osre);

					$row['osasto_selite'] = $osrow['selitetark'];
				}

				$tilaukset[] = $row;
			}
		}
		return $tilaukset;
	}

	function kasittele_tilaukset($data_array, $laheta_sahkopostit, $komento, $params, $generoi_excel, $kieli) {
		global $kukarow;

		if($generoi_excel == 'on') {
			$tiedostot = generoi_excel_tiedostot($data_array, $params, $kieli);
		}
		else {
			$tiedostot = generoi_pdf_tiedostot($data_array, $params, $kieli);
		}

		if($komento == 'email') {
			switch ($laheta_sahkopostit) {
				case 'ajajalle':
					$email = $kukarow['eposti'];
					luo_zip_ja_laheta($tiedostot, $email);
					break;

				case 'asiakkaalle':
					foreach($data_array as $data) {
						$email = $data['asiakasrow']['asiakas_email'];
						$tiedosto = $data['tiedosto'];

						laheta_email($email, array($tiedosto));

						unlink($tiedosto);
					}
					break;

				case 'asiakkaan_myyjalle':
					foreach($data_array as $data) {
						$email = $data['myyja_eposti'];
						luo_zip_ja_laheta($tiedostot, $email);
					}
					break;

				default:
					$email = $kukarow['eposti'];
					luo_zip_ja_laheta($tiedostot, $email);

					break;
			}
		}
		else {
			tulosta_raportit($tiedostot, $komento);
		}
	}

	function generoi_pdf_tiedostot(&$data_array, $params, $kieli) {
		global $pdf, $asiakasrow, $yhtiorow, $sivu, $norm, $pieni, $pvm, $alkuvv, $alkukk, $alkupp, $loppuvv, $loppukk, $loppupp, $kala, $sivu, $lask, $sumkpled, $sumkplva, $sumed, $sumva, $asiakas_numero;

		$alkuvv = $params['alkuvv'];
		$alkukk = $params['alkukk'];
		$alkupp = $params['alkupp'];
		$loppuvv = $params['loppuvv'];
		$loppukk = $params['loppukk'];
		$loppupp = $params['loppupp'];

		$pdf_tiedostot = array();
		$i = 0;
		foreach ($data_array as &$data) {
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

			$firstpage = rivi_kaikki($firstpage, 'osasto', $data['tilaukset_ilman_try']);

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

			$firstpage = rivi_kaikki($firstpage , '' , $data['tilaukset_try']);
			
			$sumkpled = $data['summat_try']['sumkpled'];
			$sumkplva = $data['summat_try']['sumkplva'];
			$sumed = $data['summat_try']['sumed'];
			$sumva = $data['summat_try']['sumva'];
			$asiakas_numero = $data['asiakasrow']['tunnus'];
			// kirjotetaan footer ja palautetaan luodun tiedoston polku
			$pdf_tiedostot[] = loppu($firstpage);

			$data['tiedosto'] = $pdf_tiedostot[$i];

			$i++;
		}

		return $pdf_tiedostot;
	}

	function generoi_excel_tiedostot(&$data_array, $params, $kieli) {
		global $yhtiorow;
		$excel_tiedostot = array();
		$i = 0;
		foreach ($data_array as &$data) {
			$temp_data = array(
				'osastoittain' => $data['tilaukset_ilman_try'],
				'tuoteryhmittain' => $data['tilaukset_try']
			);
			$excel = new vuosisopimus_asiakkaat_excel();
			$excel->set_kieli($kieli);
			$excel->set_asiakas($data['asiakasrow']);
			$excel->set_yhtiorow($yhtiorow);
			$excel->set_rajaus_paivat(array(
				'alkupaiva' => $params['alkupp'] . '.' . $params['alkukk'] . '.' . $params['alkuvv'],
				'loppupaiva' => $params['loppupp'] . '.' . $params['loppukk'] . '.' .$params['loppuvv'],
			));
			$excel->set_tilausrivit($temp_data);
			$excel->set_summat_osastoittain($data['summat_ilman_try']);
			$excel->set_summat_tuoteryhmittain($data['summat_try']);

			$excel_tiedosto_nimi = $excel->generoi();
			$excel_tiedostot[] = '/tmp/' . $excel_tiedosto_nimi;
			$data['tiedosto'] = $excel_tiedostot[$i];

			unset($excel);
			$i++;
		}

		return $excel_tiedostot;
	}

	function luo_zip_ja_laheta($tiedostot, $email_address) {
		global $yhtiorow, $kieli;
		
		$maaranpaa = '/tmp/Ostoseuranta_raportit.zip';
		$ylikirjoita = true;//ihan varmuuden vuoks
		if(luo_zip($tiedostot, $maaranpaa, $ylikirjoita)) {
			//lähetetään email
			laheta_email($email_address, array($maaranpaa));

			//poistetaan zippi
			unlink($maaranpaa);
		}
		else {
			echo t("Zipin luominen epäonnistui", $kieli);
		}

		//poistetaan pdf tiedostot
		foreach($tiedostot as $tiedosto) {
			unlink($tiedosto);
		}
	}

	function laheta_email($email_address, array $liitetiedostot_path = array()) {
		global $yhtiorow, $kieli;


		$params = array(
			"to"		 => $email_address,
			"subject"	 => $yhtiorow['nimi'] . " - " . t('Ostotilaus' , $kieli) . ' ' . date("d.m.Y"),
			"ctype"		 => "html",
			"body"		 => t('Liitteenä löytyy ostoseuranta raportit' , $kieli),
			"attachements" => array()
		);

		foreach ($liitetiedostot_path as $liitetiedosto_path) {
			$mime_type = mime_content_type($liitetiedosto_path);
			$mime_type = explode('/' , $mime_type);
			if(stristr(mime_content_type($liitetiedosto_path), 'pdf')) {
				$ctype = 'pdf';
			}
			else if(stristr(mime_content_type($liitetiedosto_path), 'xls')) {
				$ctype = 'excel';
			}
			else {
				$ctype = '';
			}
			$liitetiedosto =  array(
				'filename' => $liitetiedosto_path,
				'newfilename' => t('Ostotilaus', $kieli) . '.' .$mime_type[1],
				'ctype' => $ctype,
			);

			$params['attachements'][] = $liitetiedosto;
		}
		pupesoft_sahkoposti($params);
	}

	function tulosta_raportit($tiedostot, $komento) {
		global $kieli;
		
		echo t("Tulostetaan asiakkaan ostoseuranta tulostimeen {$komento}", $kieli);

		foreach ($tiedostot as $tiedosto) {
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