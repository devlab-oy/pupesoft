<?php

	// Enabloidaan, että Apache flushaa kaiken mahdollisen ruudulle kokoajan.
	ini_set('zlib.output_compression', 0);
	ini_set('implicit_flush', 1);
	ob_implicit_flush(1);

	// Ladataan tiedosto
	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("inc/parametrit.inc");

	// Ladataan tai poistetaan tiedosto
	if (isset($tee) and ($tee == "lataa_tiedosto" or $tee == "poista_file")) {

		// Tarkistetaan eka, että tämä on tämän käyttäjän file
		// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT.LOG
		$filen_tiedot = explode("#", $datain_filenimi);
		$kuka = $filen_tiedot[1];
		$yhtio = $filen_tiedot[2];

		$datain_filenimi = basename($datain_filenimi);

		if ($kuka != $kukarow["kuka"] or $yhtio != $kukarow["yhtio"]) {
			echo "<font class='error'>".t("Virheellinen tiedostonimi")."!</font><br>";
		}
		elseif ($tee == "lataa_tiedosto") {
			readfile($pupe_root_polku."/datain/".$datain_filenimi);
			exit;
		}
		elseif ($tee == "poista_file") {
			unlink($pupe_root_polku."/datain/".$datain_filenimi);
			unlink($pupe_root_polku."/datain/".substr($datain_filenimi,0,-3)."ERR");
		}
	}

	echo "<font class='head'>".t("Datan sisäänluku")." ".t("eräajo")."</font><hr>";

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

			$alkuperainen_filenimi = $_FILES['userfile']['name'];
			$kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];

			if ($_FILES['userfile']['size'] == 0) {
				echo "<font class='error'>".t("Tiedosto on tyhjä")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$kasitellaan_tiedosto_tyyppi = strtoupper($path_parts['extension']);

			// Vain Excel!
			$return = tarkasta_liite("userfile", array("XLSX","XLS","DATAIMPORT"));

			if ($return !== TRUE) {
				echo "<font class='error'>$return</font>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/ssconvert") and $kasitellaan_tiedosto_tyyppi == "XLS") {
				echo "<font class='error'>".t("Gnumeric (ssconvert) ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/split")) {
				echo "<font class='error'>".t("Split komento ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (preg_match('/[^A-Za-z0-9. -\_]/', $alkuperainen_filenimi)) {
				echo "<font class='error'>".t("Tiedostonimessä kiellettyjä merkkejä").". ".t("Sallitut merkit").": A-Z 0-9</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			// Tehdään Excel -> CSV konversio
			if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "XLS") {

				$kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".DATAIMPORT";

				/** Määritellään importattavan tiedoston tyyppi. Kaikki vaihtoehdot saa komentoriviltä: ssconvert --list-importers **/
				$import_type = "--import-type=Gnumeric_Excel:excel";

				$return_string = system("/usr/bin/ssconvert --export-type=Gnumeric_stf:stf_csv $import_type ".escapeshellarg($kasiteltava_tiedosto_path)." ".escapeshellarg($kasiteltava_tiedosto_path_csv), $return);

				if ($return != 0 or strpos($return_string, "CRITICAL") !== FALSE) {
					echo "<font class='error'>".t("Tiedoston konversio epäonnistui")."!</font><br>\n";
					$kasitellaan_tiedosto = FALSE;
				}
				else {
					$kasitellaan_tiedosto_tyyppi = "DATAIMPORT";
				}

				// Poistetaan orig uploadfile
				unset($kasiteltava_tiedosto_path);

				// Otetaan uusi file muuttujaan
				$kasiteltava_tiedosto_path = $kasiteltava_tiedosto_path_csv;
			}

			// Tehdään XLSX -> CSV konversio
			if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "XLSX") {

				// Tallennetaan XLSX faili CSV muotoon
				$kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".DATAIMPORT";

				// pupeFileReader palauttaa tidostonimen
				$kasiteltava_tiedosto_path_csv = pupeFileReader($kasiteltava_tiedosto_path, "XLSX", $kasiteltava_tiedosto_path_csv);

				// Poistetaan orig uploadfile
				unset($kasiteltava_tiedosto_path);

				// Otetaan uusi file muuttujaan
				$kasiteltava_tiedosto_path   = $kasiteltava_tiedosto_path_csv;
				$kasitellaan_tiedosto_tyyppi = "DATAIMPORT";
			}

			// Generoidaan uusi käyttäjäkohtainen filenimi datain -hakemistoon. Konversion jälkeen filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT
			$kasiteltava_filenimi = "lue-data#".$kukarow["kuka"]."#".$kukarow["yhtio"]."#".$table."#".md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])."#".$alkuperainen_filenimi;
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
		if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "DATAIMPORT") {

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

			$montako_osaa = 0;

			// Loopataan läpi kaikki splitatut tiedostot
			if ($handle = opendir($kasiteltava_filepath)) {
			    while (false !== ($file = readdir($handle))) {

					// Tämä file tämän käyttäjän tämän session file
					if (substr($file, 0, strlen($kasiteltava_filenimi)) == $kasiteltava_filenimi) {

						// Jos kyseessä on eka file (loppuu "aa"), ei laiteta headeriä
						if (substr($file, -2) == "aa") {
							// Renametaan alkuperäiseksi plus DATAIMPORT pääte
							rename($file, $file.".DATAIMPORT");
						}
						else {
							// Keksitään temp file
							$temp_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

							// Concatenoidaan headerifile ja tämä file temppi fileen
							system("cat ".escapeshellarg($header_file)." ".escapeshellarg($file)." > ".escapeshellarg($temp_file));

							// Poistetaan alkuperäinen file
							unlink($file);

							// Renametaan temppifile alkuperäiseksi plus DATAIMPORT pääte
							rename($temp_file, $file.".DATAIMPORT");
						}

						$montako_osaa++;
					}
			    }
			    closedir($handle);
			}

			// Poistetaan headerifile
			unlink($header_file);

			if ($montako_osaa > 1) {
				echo "<font class='message'>".t("Tiedostosi")." $alkuperainen_filenimi ".t("jaettiin")." $montako_osaa ".t("osaan").".</font>\n";
				echo "<font class='message'>".t("Jokainen osa sisältää 10000 riviä").".</font><br><br>\n";
			}

			echo "<font class='message'>".t("Tiedosto siirretty käsittelyjonoon.")." ".t("Voit nyt poistua tästä ohjelmasta.")."</font><br>";
			echo "<font class='message'>".t("Tiedosto sisäänluetaan automaattisesti.")." ".t("Palaa tähän ohjelmaan nähdäksesi ajon tuloksen.")."</font><br><br>\n";

			// Laukaistaan itse sisäänajo
			exec("/usr/bin/php {$pupe_root_polku}/data_import_ajo.php > /dev/null 2>/dev/null &");

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

			if (substr($file, 0, 9) == "lue-data#" and substr($file, -11) == ".DATAIMPORT") {
				$tiedostoja_jonossa++;

				// Tämä on tämän käyttäjän file
				if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#") {
					$omia_tiedostoja_jonossa++;
				}
			}
	    }
	    closedir($handle);
	}

	if ($tiedostoja_jonossa > 0) {
		echo "<br>";
		echo "<font class='message'>".t("Sinulla")." ".t("on")." $omia_tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa käsittelyä").".</font><br>";
		echo "<font class='message'>".t("Palvelimella")." ".t("on")." ".t("yhteensä")." $tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa käsittelyä").".</font><br>";
		echo "<br>";
	}

	// Taulut, jota voidaan käsitellä
	$taulut = array(
		'abc_parametrit'                  => 'ABC-parametrit',
		'asiakas'                         => 'Asiakas',
		'asiakasalennus'                  => 'Asiakasalennukset',
		'asiakashinta'                    => 'Asiakashinnat',
		'asiakaskommentti'                => 'Asiakaskommentit',
		'asiakkaan_avainsanat'            => 'Asiakkaan avainsanat',
		'avainsana'                       => 'Avainsanat',
		'budjetti'                        => 'Budjetti',
		'etaisyydet'                      => 'Etäisyydet varastosta',
		'extranet_kayttajan_lisatiedot'   => 'Extranet-käyttäjän lisätietoja',
		'hinnasto'                        => 'Hinnasto',
		'kalenteri'                       => 'Kalenteritietoja',
		'kuka'                            => 'Käyttäjätietoja',
		'kustannuspaikka'                 => 'Kustannuspaikat',
		'lahdot'            			  => 'Lähdöt',
		'liitetiedostot'                  => 'Liitetiedostot',
		'maksuehto'                       => 'Maksuehto',
		'pakkaus'                         => 'Pakkaustiedot',
		'perusalennus'                    => 'Perusalennukset',
		'puun_alkio_asiakas'              => 'Asiakas-segmenttiliitokset',
		'puun_alkio_tuote'                => 'Tuote-segmenttiliitokset',
		'rahtikirjanumero'				  => 'LOGY-rahtikirjanumerot',
		'rahtimaksut'                     => 'Rahtimaksut',
		'rahtisopimukset'                 => 'Rahtisopimukset',
		'rekisteritiedot'                 => 'Rekisteritiedot',
		'sanakirja'                       => 'Sanakirja',
		'sarjanumeron_lisatiedot'         => 'Sarjanumeron lisätiedot',
		'taso'                            => 'Tilikartan rakenne',
		'tili'                            => 'Tilikartta',
		'todo'                            => 'Todo-lista',
		'toimi'                           => 'Toimittaja',
		'toimittajaalennus'               => 'Toimittajan alennukset',
		'toimittajahinta'                 => 'Toimittajan hinnat',
		'toimitustapa'                    => 'Toimitustavat',
		'toimitustavan_lahdot'            => 'Toimitustavan lähdöt',
		'tullinimike'                     => 'Tullinimikeet',
		'tuote'                           => 'Tuote',
		'tuotepaikat'                     => 'Tuotepaikat',
		'tuoteperhe'                      => 'Tuoteperheet',
		'tuotteen_alv'                    => 'Tuotteiden ulkomaan ALV',
		'tuotteen_avainsanat'             => 'Tuotteen avainsanat',
		'tuotteen_orginaalit'             => 'Tuotteiden originaalit',
		'tuotteen_toimittajat'            => 'Tuotteen toimittajat',
		'tuotteen_toimittajat_tuotenumerot' => 'Tuotteen toimittajan vaihtoehtoiset tuotenumerot',
		'vak'                             => 'VAK-tietoja',
		'varaston_hyllypaikat'            => 'Varaston hyllypaikat',
		'yhteyshenkilo'                   => 'Yhteyshenkilöt',
	);

	// Yhtiökohtaisia
	if ($kukarow['yhtio'] == 'mast') {
		$taulut['auto_vari']              = 'Autoväri-datat';
		$taulut['auto_vari_tuote']        = 'Autoväri-värikirja';
		$taulut['auto_vari_korvaavat']    = 'Autoväri-korvaavat';
	}

	if ($kukarow['yhtio'] == 'artr' or $kukarow['yhtio'] == 'allr') {
		$taulut['autodata']                        = 'Autodatatiedot';
		$taulut['autodata_tuote']                  = 'Autodata tuotetiedot';
		$taulut['yhteensopivuus_auto']             = 'Yhteensopivuus automallit';
		$taulut['yhteensopivuus_auto_2']           = 'Yhteensopivuus automallit 2';
		$taulut['yhteensopivuus_mp']               = 'Yhteensopivuus mp-mallit';
		$taulut['yhteensopivuus_rekisteri']        = 'Yhteensopivuus rekisterinumerot';
		$taulut['yhteensopivuus_tuote']            = 'Yhteensopivuus tuotteet';
		$taulut['yhteensopivuus_tuote_lisatiedot'] = 'Yhteensopivuus tuotteet lisätiedot';
		$taulut['rekisteritiedot_lisatiedot']      = 'Rekisteritiedot lisatiedot';
		$taulut['yhteensopivuus_valmistenumero']   = 'Yhteensopivuus valmistenumero';
	}

	// Taulut aakkosjärjestykseen
	asort($taulut);

	// Selectoidaan aktiivi
	$sel = array_fill_keys(array($table), " selected") + array_fill_keys(array_keys($taulut), '');

	echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='tee' value='file'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Valitse tietokannan taulu").":</th>";
	echo "<td>";
	echo "<select name='table' onchange='submit();'>";

	foreach ($taulut as $taulu => $nimitys) {
		echo "<option value='$taulu' {$sel[$taulu]}>".t($nimitys)."</option>";
	}

	echo "</select>";
	echo "</td>";
	echo "</tr>";

	if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
		echo "<tr><th>".t("Ytunnus-tarkkuus").":</th>
				<td><select name='ytunnustarkkuus'>
				<option value=''>".t("Päivitetään vain, jos Ytunnuksella löytyy yksi rivi")."</option>
				<option value='2'>".t("Päivitetään kaikki syötetyllä Ytunnuksella löytyvät asiakkaat")."</option>
				</select></td>
		</tr>";
	}

	if (in_array($table, array("puun_alkio_asiakas", "puun_alkio_tuote"))) {
		echo "	<tr><th>",t("Valitse liitos"),":</th>
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
		echo "<tr><th>".t("Segmentin valinta").":</th>
				<td><select name='segmenttivalinta'>
				<option value='1'>".t("Valitaan käytettäväksi asiakas-segmentin koodia")."</option>
				<option value='2'>".t("Valitaan käytettäväksi asiakas-segmentin tunnusta ")."</option>
				</select></td>
		</tr>";
		echo "<tr><th>".t("Asiakkaan valinta").":</th>
				<td><select name='asiakkaanvalinta'>
				<option value='2'>".t("Asiakas-sarakkeessa asiakkaan toim_ovttunnus")."</option>
				<option value='1'>".t("Asiakas-sarakkeessa asiakkaan tunnus")."</option>
				</select></td>
		</tr>";
	}

	if ($table == "extranet_kayttajan_lisatiedot") {
		echo "<tr><th>".t("Liitostunnus").":</th>
				<td><select name='liitostunnusvalinta'>
				<option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
				<option value='2'>".t("Liitostunnus-sarakkeessa käyttäjänimi")."</option>
				</select></td>
		</tr>";
	}

	echo "	<tr><th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' name='laheta' value='".t("Lähetä")."'></td>
		</tr>

		</table>
		</form>
		<br>";

	exec("/usr/bin/php {$pupe_root_polku}/data_import_ajo.php > /dev/null 2>/dev/null &");

	// Näytetään käyttäjän kaikki LOG filet
	$kasitelty = array();
	$kasitelty_i = 0;

	if ($files = scandir($pupe_root_polku."/datain")) {
	    foreach ($files as $file) {
			// Tämä file on valmis lue-data file
			if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#" and substr($file, -4) == ".LOG") {

				$log = file_get_contents($pupe_root_polku."/datain/".$file);

				// Tämä logi on jo käsitelty
				if (strpos($log, "## LUE-DATA-EOF ##") !== FALSE) {

					// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT.LOG
					$filen_tiedot = explode("#", $file);
					$kuka = $filen_tiedot[1];
					$taulu = $filen_tiedot[3];
					$orig_file = $filen_tiedot[5];

					$kasitelty[$kasitelty_i]["filename"] = $file;
					$kasitelty[$kasitelty_i]["errfilename"] = substr($file,0,-3)."ERR";
					$kasitelty[$kasitelty_i]["orig_file"] = $orig_file;
					$kasitelty[$kasitelty_i]["taulu"] = $taulut[$taulu];
					$kasitelty[$kasitelty_i]["aika"] = date("d.m.Y H:i:s", filemtime($pupe_root_polku."/datain/".$file));
					$kasitelty[$kasitelty_i]["lognimi"] = "$kuka-$taulu-".date("Ymd-His", filemtime($pupe_root_polku."/datain/".$file)).".txt";
					$kasitelty[$kasitelty_i]["errnimi"] = "$kuka-$taulu-".date("Ymd-His", filemtime($pupe_root_polku."/datain/".$file)).".xls";
					$kasitelty_i++;
				}
			}
	    }
	}

	if (count($kasitelty) > 0) {

		echo "<font class='head'>".t("Sinun käsitellyt ajot").":</font><hr>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tiedosto")."</th>";
		echo "<th>".t("Taulu")."</th>";
		echo "<th>".t("Käsitelty")."</th>";
		echo "<th>".t("Lokitiedosto")."</th>";
		echo "<th>".t("Virheelliset rivit")."</th>";
		echo "<th>".t("Poista lokitiedosto")."</th>";
		echo "</tr>";

		foreach ($kasitelty as $file) {
			echo "<tr class='aktiivi'>";
			echo "<td>{$file["orig_file"]}</td>";
			echo "<td>{$file["taulu"]}</td>";
			echo "<td>{$file["aika"]}</td>";
			echo "<td><form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='{$file["lognimi"]}'>";
			echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
			echo "<input type='submit' value='".t("Tallenna")."'>";
			echo "</form></td>";
			echo "<td><form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='{$file["errnimi"]}'>";
			echo "<input type='hidden' name='datain_filenimi' value='{$file["errfilename"]}'>";
			echo "<input type='submit' value='".t("Tallenna")."'>";
			echo "</form></td>";
			echo "<td><form method='post' onsubmit=\"return confirm('".t("Oletko varma, että haluat poistaa lokitiedoston?")."')\">";
			echo "<input type='hidden' name='tee' value='poista_file'>";
			echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
			echo "<input type='submit' value='".t("Poista")."'>";
			echo "</form></td>";
			echo "</tr>";
		}

		echo "</table>";
	}

	require("inc/footer.inc");
