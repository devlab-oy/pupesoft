<?php

	// Enabloidaan, että Apache flushaa kaiken mahdollisen ruudulle kokoajan.
	ini_set('zlib.output_compression', 0);
	ini_set('implicit_flush', 1);
	ob_implicit_flush(1);

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Datan sisäänluku")."</font><hr>";

	// Muuttujat
	$tee = isset($tee) ? trim($tee) : "";
	$table = isset($table) ? trim($table) : "";
	$laheta = isset($laheta) ? trim($laheta) : "";

	// Käsitellään file
	if ($tee == "file" and $laheta != "") {

		$kasitellaan_tiedosto = TRUE;
		$kasitellaan_tiedosto_tyyppi = "";

		if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			echo "<font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...</font><br><br>\n";

			$kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];

			if ($_FILES['userfile']['size'] == 0) {
				echo "<font class='error'>".t("Tiedosto on tyhjä")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$kasitellaan_tiedosto_tyyppi = strtoupper($path_parts['extension']);

			// Vain Excel tai validi CSV!
			$return = tarkasta_liite("userfile", array("XLSX","XLS","CSV"));

			if ($return !== TRUE) {
				echo "<font class='error'>".t("Väärä tiedostomuoto")." $kasitellaan_tiedosto_tyyppi !</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/ssconvert") and ($kasitellaan_tiedosto_tyyppi == "XLSX" or $kasitellaan_tiedosto_tyyppi == "XLS")) {
				echo "<font class='error'>".t("Gnumeric (ssconvert) ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/split")) {
				echo "<font class='error'>".t("Split komento ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			// Tehdään Excel -> CSV konversio
			if ($kasitellaan_tiedosto === TRUE and ($kasitellaan_tiedosto_tyyppi == "XLS" or $kasitellaan_tiedosto_tyyppi == "XLSX")) {

				$kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".csv";

				/** Määritellään importattavan tiedoston tyyppi. Kaikki vaihtoehdot saa komentoriviltä: ssconvert --list-importers **/
				if ($kasitellaan_tiedosto_tyyppi == "XLSX") {
					$import_type = "--import-type=Gnumeric_Excel:xlsx";
				}
				else {
					$import_type = "--import-type=Gnumeric_Excel:excel";
				}

				$return = system("/usr/bin/ssconvert --export-type=Gnumeric_stf:stf_csv $import_type ".escapeshellarg($kasiteltava_tiedosto_path)." ".escapeshellarg($kasiteltava_tiedosto_path_csv));

				if ($return === FALSE) {
					echo "<font class='error'>".t("Tiedoston konversio epäonnistui")."!</font><br>\n";
					$kasitellaan_tiedosto = FALSE;
				}
				else {
					$kasitellaan_tiedosto_tyyppi = "CSV";
				}

				// Poistetaan orig uploadfile
				unset($kasiteltava_tiedosto_path);

				// Otetaan uusi file muuttujaan
				$kasiteltava_tiedosto_path = $kasiteltava_tiedosto_path_csv;
			}

			// Generoidaan uusi käyttäjäkohtainen filenimi datain -hakemistoon. Konversion jälkeen filename on muotoa: lue-data#username#taulu#randombit#jarjestys.CSV
			$kasiteltava_filenimi = "lue-data#".$kukarow["kuka"]."#".$table."#".md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
			$kasiteltava_filepath = $pupe_root_polku."/datain/";
			$kasiteltava_kokonimi = $kasiteltava_filepath.$kasiteltava_filenimi;

			// Siirretään tiedosto datain -hakemistoon
			if (!rename($kasiteltava_tiedosto_path, $kasiteltava_kokonimi)) {
				echo "<font class='error'>".t("Tiedoston kopiointi epäonnistui")."! $kasiteltava_tiedosto_path &raquo; $kasiteltava_kokonimi</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}
		}
		else {
			echo "<font class='error'>".t("Et valinnut tiedostoa")."!</font><br>\n";
			$kasitellaan_tiedosto = FALSE;
		}

		// File saatu palvelimelle OK
		if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "CSV") {

			// Otetaan tiedostosta ensimmäinen rivi talteen, siinä on headerit
			$file = fopen($kasiteltava_kokonimi, "r") or die (t("Tiedoston avaus epäonnistui")."!");
			$header_rivi = fgets($file);
			fclose($file);

			// Laitetaan header fileen, koska filejen mergettäminen on nopeempaa komentoriviltä
			$header_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
			file_put_contents($header_file, $header_rivi);

			// Splitataan tiedosto 10000 rivin osiin datain -hakemistoon
			chdir($kasiteltava_filepath);
			system("/usr/bin/split -l 10000 ".escapeshellarg($kasiteltava_kokonimi)." ".escapeshellarg($kasiteltava_filenimi."#"));

			// Poistetaan alkuperäinen
			unlink($kasiteltava_kokonimi);

			// Loopataan läpi kaikki splitatut tiedostot
			if ($handle = opendir($kasiteltava_filepath)) {
			    while (false !== ($file = readdir($handle))) {

					// Tämä file tämän käyttäjän tämän session file
					if (substr($file, 0, strlen($kasiteltava_filenimi)) == $kasiteltava_filenimi) {

						// Jos kyseessä on eka file (loppuu "aa"), ei laiteta headeriä
						if (substr($file, -2) == "aa") {
							// Renametaan alkuperäiseksi plus CSV pääte
							rename($file, $file.".CSV");
						}
						else {
							// Keksitään temp file
							$temp_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

							// Concatenoidaan headerifile ja tämä file temppi fileen
							system("cat ".escapeshellarg($header_file)." ".escapeshellarg($file)." > ".escapeshellarg($temp_file));

							// Poistetaan alkuperäinen file
							unlink($file);

							// Renametaan temppifile alkuperäiseksi plus CSV pääte
							rename($temp_file, $file.".CSV");
						}
					}
			    }
			    closedir($handle);
			}

			// Poistetaan headerifile
			unlink($header_file);

			echo "<font class='message'>".t("Tiedosto laitettu käsittelyjonoon")."!</font><br><br>\n";
		}
		else {
			echo "<font class='error'>".t("Dataa ei käsitelty")."!</font><br><br>\n";
		}

	}

	// Katsotaan onko käyttäjällä tiedostoja käsittelyssä
	$tiedostoja_jonossa = 0;
	$omia_tiedostoja_jonossa = 0;

	if ($handle = opendir($pupe_root_polku."/datain")) {
	    while (false !== ($file = readdir($handle))) {
			// Tämä file on valmis lue-data file
			if (substr($file, 0, 9) == "lue-data#" and substr($file, -4) == ".CSV") {
				$tiedostoja_jonossa++;

				// Tämä on tämän käyttäjän file
				if (substr($file, 0, 10+strlen($kukarow["kuka"])) == "lue-data#{$kukarow["kuka"]}#") {
					$omia_tiedostoja_jonossa++;
				}
			}
	    }
	    closedir($handle);
	}

	if ($tiedostoja_jonossa > 0) {
		echo "<br>";
		echo "<font class='message'>Sinulla on $omia_tiedostoja_jonossa tiedostoa käsittelyssä.</font><br>";
		echo "<font class='message'>Yhteensä käsittelyssä on $tiedostoja_jonossa tiedostoa.</font><br>";
		echo "<br>";
	}

	$indx = array(
		'asiakas',
		'asiakasalennus',
		'asiakashinta',
		'asiakaskommentti',
		'asiakkaan_avainsanat',
		'abc_parametrit',
		'autodata',
		'autodata_tuote',
		'avainsana',
		'budjetti',
		'etaisyydet',
		'hinnasto',
		'kalenteri',
		'kustannuspaikka',
		'liitetiedostot',
		'maksuehto',
		'pakkaus',
		'perusalennus',
		'rahtimaksut',
		'rahtisopimukset',
		'rekisteritiedot',
		'sanakirja',
		'sarjanumeron_lisatiedot',
		'taso',
		'tili',
		'todo',
		'toimi',
		'toimitustapa',
		'tullinimike',
		'tuote',
		'tuotepaikat',
		'tuoteperhe',
		'tuotteen_alv',
		'tuotteen_avainsanat',
		'tuotteen_orginaalit',
		'tuotteen_toimittajat',
		'vak',
		'yhteensopivuus_auto',
		'yhteensopivuus_auto_2',
		'yhteensopivuus_mp',
		'yhteensopivuus_rekisteri',
		'yhteensopivuus_tuote',
		'yhteensopivuus_tuote_lisatiedot',
		'yhteyshenkilo',
		'varaston_hyllypaikat',
		'toimitustavan_lahdot',
		'kuka',
		'extranet_kayttajan_lisatiedot',
		'auto_vari',
		'auto_vari_tuote',
		'auto_vari_korvaavat'
	);

	$dynaamiset_avainsanat_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite != '' ");

	while ($dynaamiset_avainsanat_row = mysql_fetch_assoc($dynaamiset_avainsanat_result)) {
		$indx[] = 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite']);
	}

	$sel = array_fill_keys(array($table), " selected") + array_fill_keys($indx, '');

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action=''>";
	echo "<input type='hidden' name='tee' value='file'>";
	echo "<table>";
	echo "<tr>";
	echo "		<td>".t("Valitse tietokannan taulu").":</td>
				<td><select name='table' onchange='submit();'>
					<option value='asiakas' {$sel['asiakas']}>".t("Asiakas")."</option>
					<option value='asiakasalennus' {$sel['asiakasalennus']}>".t("Asiakasalennukset")."</option>
					<option value='asiakashinta' {$sel['asiakashinta']}>".t("Asiakashinnat")."</option>
					<option value='asiakaskommentti' {$sel['asiakaskommentti']}>".t("Asiakaskommentit")."</option>
					<option value='asiakkaan_avainsanat' {$sel['asiakkaan_avainsanat']}>".t("Asiakkaan avainsanat")."</option>
					<option value='abc_parametrit' {$sel['abc_parametrit']}>".t("ABC-parametrit")."</option>
					<option value='autodata' {$sel['autodata']}>".t("Autodatatiedot")."</option>
					<option value='autodata_tuote' {$sel['autodata_tuote']}>".t("Autodata tuotetiedot")."</option>
					<option value='avainsana' {$sel['avainsana']}>".t("Avainsanat")."</option>
					<option value='budjetti' {$sel['budjetti']}>".t("Budjetti")."</option>
					<option value='etaisyydet' {$sel['etaisyydet']}>".t("Etäisyydet varastosta")."</option>
					<option value='hinnasto' {$sel['hinnasto']}>".t("Hinnasto")."</option>
					<option value='kalenteri' {$sel['kalenteri']}>".t("Kalenteritietoja")."</option>
					<option value='kustannuspaikka' {$sel['kustannuspaikka']}>".t("Kustannuspaikat")."</option>
					<option value='liitetiedostot' {$sel['liitetiedostot']}>".t("Liitetiedostot")."</option>
					<option value='maksuehto' {$sel['maksuehto']}>".t("Maksuehto")."</option>
					<option value='pakkaus' {$sel['pakkaus']}>".t("Pakkaustiedot")."</option>
					<option value='perusalennus' {$sel['perusalennus']}>".t("Perusalennukset")."</option>
					<option value='rahtimaksut' {$sel['rahtimaksut']}>".t("Rahtimaksut")."</option>
					<option value='rahtisopimukset' {$sel['rahtisopimukset']}>".t("Rahtisopimukset")."</option>
					<option value='rekisteritiedot' {$sel['rekisteritiedot']}>".t("Rekisteritiedot")."</option>
					<option value='sanakirja' {$sel['sanakirja']}>".t("Sanakirja")."</option>
					<option value='sarjanumeron_lisatiedot' {$sel['sarjanumeron_lisatiedot']}>".t("Sarjanumeron lisätiedot")."</option>
					<option value='taso' {$sel['taso']}>".t("Tilikartan rakenne")."</option>
					<option value='tili' {$sel['tili']}>".t("Tilikartta")."</option>
					<option value='todo' {$sel['todo']}>".t("Todo-lista")."</option>
					<option value='toimi' {$sel['toimi']}>".t("Toimittaja")."</option>
					<option value='toimitustapa' {$sel['toimitustapa']}>".t("Toimitustapoja")."</option>
					<option value='tullinimike' {$sel['tullinimike']}>".t("Tullinimikeet")."</option>
					<option value='tuote' {$sel['tuote']}>".t("Tuote")."</option>
					<option value='tuotepaikat' {$sel['tuotepaikat']}>".t("Tuotepaikat")."</option>
					<option value='tuoteperhe' {$sel['tuoteperhe']}>".t("Tuoteperheet")."</option>
					<option value='tuotteen_alv' {$sel['tuotteen_alv']}>".t("Tuotteiden ulkomaan ALV")."</option>
					<option value='tuotteen_avainsanat' {$sel['tuotteen_avainsanat']}>".t("Tuotteen avainsanat")."</option>
					<option value='tuotteen_orginaalit' {$sel['tuotteen_orginaalit']}>".t("Tuotteiden originaalit")."</option>
					<option value='tuotteen_toimittajat' {$sel['tuotteen_toimittajat']}>".t("Tuotteen toimittajat")."</option>
					<option value='vak' {$sel['vak']}>".t("VAK-tietoja")."</option>
					<option value='yhteensopivuus_auto' {$sel['yhteensopivuus_auto']}>".t("Yhteensopivuus automallit")."</option>
					<option value='yhteensopivuus_auto_2' {$sel['yhteensopivuus_auto_2']}>".t("Yhteensopivuus automallit 2")."</option>
					<option value='yhteensopivuus_mp' {$sel['yhteensopivuus_mp']}>".t("Yhteensopivuus mp-mallit")."</option>
					<option value='yhteensopivuus_rekisteri' {$sel['yhteensopivuus_rekisteri']}>".t("Yhteensopivuus rekisterinumerot")."</option>
					<option value='yhteensopivuus_tuote' {$sel['yhteensopivuus_tuote']}>".t("Yhteensopivuus tuotteet")."</option>
					<option value='yhteensopivuus_tuote_lisatiedot' {$sel['yhteensopivuus_tuote_lisatiedot']}>".t("Yhteensopivuus tuotteet lisätiedot")."</option>
					<option value='yhteyshenkilo' {$sel['yhteyshenkilo']}>".t("Yhteyshenkilöt")."</option>
					<option value='kuka' {$sel['kuka']}>".t("Käyttäjätietoja")."</option>
					<option value='extranet_kayttajan_lisatiedot' {$sel['extranet_kayttajan_lisatiedot']}>".t("Extranet-käyttäjän lisätietoja")."</option>
					<option value='varaston_hyllypaikat' {$sel['varaston_hyllypaikat']}>".t("Varaston hyllypaikat")."</option>
					<option value='toimitustavan_lahdot' {$sel['toimitustavan_lahdot']}>".t("Toimitustavan lähdöt")."</option>";

	$dynaamiset_avainsanat_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite != '' ");
	$dynaamiset_avainsanat = '';

	if ($kukarow['yhtio'] == 'mast') {
		echo "<option value='auto_vari' $sel[auto_vari]>".t("Autoväri-datat")."</option>";
		echo "<option value='auto_vari_tuote' $sel[auto_vari_tuote]>".t("Autoväri-värikirja")."</option>";
		echo "<option value='auto_vari_korvaavat' $sel[auto_vari_korvaavat]>".t("Autoväri-korvaavat")."</option>";
	}

	while ($dynaamiset_avainsanat_row = mysql_fetch_assoc($dynaamiset_avainsanat_result)) {
		if ($table == 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite'])) {
			$dynaamiset_avainsanat = 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite']);
		}

		echo "<option value='puun_alkio_".strtolower($dynaamiset_avainsanat_row['selite'])."' ",$sel['puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite'])],">Dynaaminen_",strtolower($dynaamiset_avainsanat_row['selite']),"</option>";
	}

	echo "	</select></td></tr>";

	if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
		echo "<tr><td>".t("Ytunnus-tarkkuus").":</td>
				<td><select name='ytunnustarkkuus'>
				<option value=''>".t("Päivitetään vain, jos Ytunnuksella löytyy yksi rivi")."</option>
				<option value='2'>".t("Päivitetään kaikki syötetyllä Ytunnuksella löytyvät asiakkaat")."</option>
				</select></td>
		</tr>";
	}

	if (trim($dynaamiset_avainsanat) != '' and $table == $dynaamiset_avainsanat) {
		echo "	<tr><td>",t("Valitse liitos"),":</td>
				<td><select name='dynaamisen_taulun_liitos'>";

		if ($table == 'puun_alkio_asiakas') {
			echo "	<option value=''>",t("Asiakkaan tunnus"),"</option>
					<option value='ytunnus'>",t("Asiakkaan ytunnus"),"</option>
					<option value='toim_ovttunnus'>",t("Asiakkaan toimitusosoitteen ovttunnus"),"</option>
					<option value='asiakasnro'>",t("Asiakkaan asiakasnumero"),"</option>";
		}
		else {
			echo "	<option value=''>",t("Puun alkion tunnus"),"</option>
					<option value='koodi'>",t("Puun alkion koodi"),"</option>";
		}

		echo "</select></td></tr>";
	}

	if (in_array($table, array("asiakasalennus", "asiakashinta"))) {
		echo "<tr><td>".t("Segmentin valinta").":</td>
				<td><select name='segmenttivalinta'>
				<option value='1'>".t("Valitaan käytettäväksi asiakas-segmentin koodia")."</option>
				<option value='2'>".t("Valitaan käytettäväksi asiakas-segmentin tunnusta ")."</option>
				</select></td>
		</tr>";
	}

	if ($table == "extranet_kayttajan_lisatiedot") {
		echo "<tr><td>".t("Liitostunnus").":</td>
				<td><select name='liitostunnusvalinta'>
				<option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
				<option value='2'>".t("Liitostunnus-sarakkeessa käyttäjänimi")."</option>
				</select></td>
		</tr>";
	}

	echo "	<tr><td>".t("Valitse tiedosto").":</td>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' name='laheta' value='".t("Lähetä")."'></td>
		</tr>

		</table>
		</form>";

