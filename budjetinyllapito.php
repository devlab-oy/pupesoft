<?php

if (isset($_REQUEST["tee"])) {
	if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

require ("inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}
else {

	echo "<font class='head'>",t("Budjetin yll‰pito"),"</font><hr>";

	if (is_array($luvut)) {
		$paiv = 0;
		$lisaa = 0;

		foreach ($luvut as $u_taso => $rivit) {
			foreach ($rivit as $u_tili => $solut) {
				foreach ($solut as $u_kausi => $solu) {

					$solu = str_replace (",", ".", $solu);

					if ($solu == '!' or (float) $solu != 0) {

						if ($solu == '!') $solu = 0;

						$solu = (float) $solu;

						$query = "	SELECT summa
									FROM budjetti
									WHERE yhtio  = '$kukarow[yhtio]'
									AND kausi 	 = '$u_kausi'
									AND taso 	 = '$u_taso'
									AND tili 	 = '$u_tili'
									AND kustp 	 = '$vkustp'
									AND kohde 	 = '$vkohde'
									AND projekti = '$vproj'";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {

							$budjrow = mysql_fetch_assoc($result);

							if ($budjrow['summa'] != $solu) {

								if ($solu == 0) {
									$query = "	DELETE FROM budjetti
												WHERE yhtio  = '$kukarow[yhtio]'
												AND kausi 	 = '$u_kausi'
												AND taso 	 = '$u_taso'
												AND tili 	 = '$u_tili'
												AND kustp 	 = '$vkustp'
												AND kohde 	 = '$vkohde'
												AND projekti = '$vproj'";
								}
								else {
									$query	= "	UPDATE budjetti SET
												summa = $solu,
												muuttaja = '$kukarow[kuka]',
												muutospvm = now()
												WHERE yhtio  = '$kukarow[yhtio]'
												AND kausi 	 = '$u_kausi'
												AND taso 	 = '$u_taso'
												AND tili 	 = '$u_tili'
												AND kustp 	 = '$vkustp'
												AND kohde 	 = '$vkohde'
												AND projekti = '$vproj'";
								}
								$result = mysql_query($query) or pupe_error($query);
								$paiv++;
							}
						}
						elseif ($solu != 0) {
							$query = "	INSERT INTO budjetti SET
										summa 		= $solu,
										yhtio 		= '$kukarow[yhtio]',
										kausi 		= '$u_kausi',
										taso 		= '$u_taso',
										tili 		= '$u_tili',
										kustp 		= '$vkustp',
										kohde 		= '$vkohde',
										projekti 	= '$vproj',
										laatija 	= '$kukarow[kuka]',
										luontiaika  = now()";
							$result = mysql_query($query) or pupe_error($query);
							$lisaa++;
						}
					}
				}
			}
		}

		echo "<font class='message'>".t("P‰ivitin")." $paiv. ".t("Lis‰sin")." $lisaa</font><br><br>";
	}

	if (isset($tyyppi)) {
		$sel1="";
		$sel2="";
		$sel3="";
		$sel4="";
		switch ($tyyppi) {
			case (1): $sel1 = 'selected';
			case (2): $sel2 = 'selected';
			case (3): $sel3 = 'selected';
			case (4): $sel4 = 'selected';
		}
	}

	echo "<form method='post'>";

	echo "<table>";

	echo "	<tr>
			<th>".t("Tyyppi")."</th>
			<td><select name = 'tyyppi'>
			<option value='4' $sel4>".t("Tuloslaskelma")."
			<option value='2' $sel2>".t("Vastattavaa")."
			<option value='1' $sel1>".t("Vastaavaa")."
			</select></td></tr>
			<tr>";

	echo "<tr><th>".t("Tilikausi");

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku desc";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "</th><td><select name='tkausi'>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel="";
		if ($tkausi == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
	}

	echo "</select></th></tr>";
	echo "<tr><th>".t("Kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'K'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustp'><option value='0'>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel="";
		if ($kustp == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] $vrow[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Kohde")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'O'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kohde'><option value='0'>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel="";
		if ($kohde == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Projekti")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'P'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='proj'><option value='0'>".t("Ei valintaa")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel="";
		if ($proj == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td></tr>";


	$sel = array();
	$sel[$rtaso] = "SELECTED";

	echo "<tr><th valign='top'>".t("Budjetointitaso")."</th>
			<td><select name='rtaso'>";

	$query = "	SELECT max(length(taso)) taso
				from taso
				where yhtio = '$kukarow[yhtio]'";
	$vresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_assoc($vresult);

	echo "<option value='TILI'>".t("Tili taso")."</option>\n";

	for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
		echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s",'',$i+1)."</option>\n";
	}

	echo "</select></td></tr>";

	echo "</table>";
	echo t("Budjettiluvun voi poistaa huutomerkill‰ (!)")."<br><br>";
	echo "<input type='submit' VALUE='".t("N‰yt‰/Tallenna")."'>";

	echo "</table>";

	if (isset($tkausi)) {
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tkausi'";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1) {
			$tilikaudetrow = mysql_fetch_array($vresult);
		}
	}

	if (is_array($tilikaudetrow)) {

		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = strtoupper($path_parts['extension']);

			if ($ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size'] == 0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
			}

			require_once ('excel_reader/reader.php');

			// ExcelFile
			$data = new Spreadsheet_Excel_Reader();

			// Set output Encoding.
			$data->setOutputEncoding('CP1251');
			$data->setRowColOffset(0);
			$data->read($_FILES['userfile']['tmp_name']);

			echo "<br /><br /><font class='message'>".t("Luetaan l‰hetetty tiedosto")."...<br><br></font>";

			$headers = array();
			$taulunrivit	= array();

			for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
				for ($excej = 3; $excej < 20; $excej++) {

					$nro = trim($data->sheets[0]['cells'][$excei][$excej]);

					if ((float) $nro != 0 or $nro == "!") {
						$taulunrivit[$data->sheets[0]['cells'][$excei][0]][$data->sheets[0]['cells'][$excei][1]][$excej-3] = $nro;
					}
				}
			}
		}
		else {
			unset($taulunrivit);
		}

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
			exit;
		}

		//keksit‰‰n failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi 	 = 0;
		$excelsarake = 0;

		$worksheet->writeString($excelrivi, $excelsarake, t("Tili / Taso"), $format_bold);
		$excelsarake++;

		$worksheet->writeString($excelrivi, $excelsarake, t("Nro"), $format_bold);
		$excelsarake++;

		$worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
		$excelsarake++;

		//Parametrit mihin t‰m‰ taulukko liittyy
		echo "<table>";
		echo "<tr><td class='back'></td>";

		$j = 0;
		$raja = '0000-00';
		$rajataulu = array();
		$budjetit = array();

		while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

			$vuosi = substr($tilikaudetrow['tilikausi_alku'], 0, 4);
			$kk = substr($tilikaudetrow['tilikausi_alku'], 5, 2);
			$kk += $j;

			if ($kk > 12) {
				$vuosi++;
				$kk -= 12;
			}

			if ($kk < 10) $kk = '0'.$kk;

			$raja = $vuosi."-".$kk;
			$rajataulu[$j] = $vuosi.$kk;

		 	echo "<th>$raja</th>";
		 	$j++;

			$worksheet->writeString($excelrivi, $excelsarake, $raja, $format_bold);
			$excelsarake++;

			// Haetaan budjetit
			$query = "	SELECT *
						from budjetti
						where yhtio		= '$kukarow[yhtio]'
						and kausi 		= '$vuosi$kk'
						and kustp		= '$kustp'
						and kohde		= '$kohde'
						and projekti	= '$proj'";
			$xresult = mysql_query($query) or pupe_error($query);

			while ($brow = mysql_fetch_array($xresult)) {
				$budjetit[(string) $brow["taso"]][(string) $brow["tili"]][(string) $brow["kausi"]] = $brow["summa"];
			}
		}

		echo "</tr>";

		$excelsarake = 0;
		$excelrivi++;

		$tasotyyppi = "U";
		$tilityyppi = "ulkoinen_taso";

		if ($tyyppi == 4) { // Sis‰inen tuloslaskelma!!!
			$tasotyyppi = "S";
			$tyyppi = 3;
			$tilityyppi = "sisainen_taso";
		}

		// Haetaan kaikki tasot ja rakennetaan tuloslaskelma-array
		$query = "	SELECT *
					FROM taso
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi 	= '$tasotyyppi'
					and LEFT(taso, 1) in (BINARY '$tyyppi')
					and taso != ''
					ORDER BY taso";
		$tasores = mysql_query($query) or pupe_error($query);

		while ($tasorow = mysql_fetch_assoc($tasores)) {

			// mill‰ tasolla ollaan (1,2,3,4,5,6)
			$tasoluku = strlen($tasorow["taso"]);

			// tasonimi talteen (rightp‰dd‰t‰‰n ÷:ll‰, niin saadaan oikeaan j‰rjestykseen)
			$apusort = str_pad($tasorow["taso"], 20, "÷");
			$tasonimi[$apusort] = $tasorow["nimi"];

			// pilkotaan taso osiin
			$taso = array();
			for ($i = 0; $i < $tasoluku; $i++) {
				$taso[$i] = substr($tasorow["taso"], 0, $i+1);
			}
		}

		// sortataan array indexin (tason) mukaan
		ksort($tasonimi);

		// loopataan tasot l‰pi
		foreach ($tasonimi as $key_c => $value) {

			$key = str_replace("÷", "", $key_c); // ÷-kirjaimet pois

			// tulostaan rivi vain jos se kuuluu rajaukseen
			if (strlen($key) <= $rtaso or $rtaso == "TILI") {

				$class = "";

				// laitetaan ykkˆs ja kakkostason rivit tummalla selkeyden vuoksi
				if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

				if ($rtaso == "TILI") {

					$class = "tumma";

					$query = "	SELECT tilino, nimi
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]'
								AND $tilityyppi = '$key'
								ORDER BY 1,2";
					$tiliresult = mysql_query($query) or pupe_error($query);

					while ($tilirow = mysql_fetch_assoc($tiliresult)) {

						$worksheet->writeString($excelrivi, $excelsarake, "TILI");
						$excelsarake++;

						$worksheet->writeString($excelrivi, $excelsarake, $tilirow['tilino']);
						$excelsarake++;

						$worksheet->writeString($excelrivi, $excelsarake, $tilirow['nimi']);
						$excelsarake++;

						echo "<tr><th nowrap>$tilirow[tilino] - $tilirow[nimi]</th>";

						for ($k = 0; $k < $j; $k++) {

							$nro = "";

							if (isset($taulunrivit["TILI"][$tilirow["tilino"]][$k])) {
								$nro = $taulunrivit["TILI"][$tilirow["tilino"]][$k];
							}
							elseif (isset($budjetit[$key][$tilirow["tilino"]][$rajataulu[$k]])) {
								$nro = $budjetit[$key][$tilirow["tilino"]][$rajataulu[$k]];
							}

							echo "<td align='right' nowrap><input type='text' name = 'luvut[$key][$tilirow[tilino]][$rajataulu[$k]]' value='$nro' size='10'></td>";

							$worksheet->write($excelrivi, $excelsarake, $nro);
							$excelsarake++;
						}

						echo "</tr>";

						$excelsarake = 0;
						$excelrivi++;
					}
				}

				$worksheet->writeString($excelrivi, $excelsarake, "TASO");
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $key);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $value);
				$excelsarake++;

				echo "<tr><th nowrap>$key - $value</th>";

				for ($k = 0; $k < $j; $k++) {

					$nro = "";

					if (isset($taulunrivit["TASO"][$key][$k])) {
						$nro = $taulunrivit["TASO"][$key][$k];
					}
					elseif (isset($budjetit[$key]["0"][$rajataulu[$k]])) {
						$nro = $budjetit[$key]["0"][$rajataulu[$k]];
					}

					echo  "<td class='$class' align='right' nowrap><input type='text' name = 'luvut[$key][0][$rajataulu[$k]]' value='$nro' size='10'></td>";

					$worksheet->write($excelrivi, $excelsarake, $nro);
					$excelsarake++;
				}

				echo "</tr>\n";

				$excelsarake = 0;
				$excelrivi++;

				// kakkostason j‰lkeen aina yks tyhj‰ rivi.. paitsi jos otetaan vain kakkostason raportti
				if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
					echo "<tr><td class='back'>&nbsp;</td></tr>";
				}

				if (strlen($key) == 1 and ($rtaso > 1 or $rtaso == "TILI")) {
					echo "<tr><td class='back'><br><br></td></tr>";
				}
			}

			$edkey = $key;
		}

		echo "</form></table><br>";

		$workbook->close();

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi.xls'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
		echo "<table>";
		echo "<tr><th>",t("Tallenna budjetti (xls)"),":</th>";
		echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
		echo "</table></form><br />";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='tee' value='file'>";
		echo "<input type='hidden' name='vkustp' value='$kustp'>";
		echo "<input type='hidden' name='vkohde' value='$kohde'>";
		echo "<input type='hidden' name='vproj' value='$proj'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tkausi' value='$tkausi'>";
		echo "<input type='hidden' name='rtaso' value='$rtaso'>";
		echo "<table>";
		echo "<tr><th>",t("Valitse tiedosto"),"</th><td><input type='file' name='userfile' /></td><td class='back'><input type='submit' value='",t("L‰het‰"),"' /></td></tr>";
		echo "</table>";
		echo "</form>";

	}

	require ("inc/footer.inc");
}

?>