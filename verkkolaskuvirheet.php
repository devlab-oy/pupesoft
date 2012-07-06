<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
		$no_head = "yes";

		if (isset($_REQUEST["xml"])) {
			header("Content-type: text/xml");
		}

		if (isset($_REQUEST["pdf"])) {
			header("Content-type: application/pdf");
			header("Content-length: ".strlen(urldecode($_REQUEST["pdf"])));
			header("Content-Disposition: inline; filename=Pupesoft_lasku");
			header("Content-Description: Pupesoft_lasku");
		}

		flush();
	}

	require ("inc/parametrit.inc");

	if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["xml"])) {
		$xml = urldecode($_REQUEST["xml"]);

		$xml = str_replace("\"Finvoice.dtd\"", "\"{$palvelin2}datain/Finvoice.dtd\"", $xml);
		$xml = str_replace("\"Finvoice.xsl\"", "\"{$palvelin2}datain/Finvoice.xsl\"", $xml);

		$xml = str_replace("\"datain/Finvoice.dtd\"", "\"{$palvelin2}datain/Finvoice.dtd\"", $xml);
		$xml = str_replace("\"datain/Finvoice.xsl\"", "\"{$palvelin2}datain/Finvoice.xsl\"", $xml);

		echo $xml;
		exit;
	}

	if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["pdf"])) {
		$pdf = urldecode($_REQUEST["pdf"]);
		echo $pdf;
		exit;
	}

	// m‰‰ritell‰‰n polut
	if (!isset($verkkolaskut_in)) {
		$verkkolaskut_in = "/home/verkkolaskut";
	}
	if (!isset($verkkolaskut_reject)){
		$verkkolaskut_reject = "/home/verkkolaskut/reject";
	}
	if (!isset($verkkolaskut_error)) {
		$verkkolaskut_error = "/home/verkkolaskut/error";
	}

	$verkkolaskuvirheet_kasittele	= $verkkolaskut_in;
	$verkkolaskuvirheet_vaarat		= $verkkolaskut_error;
	$verkkolaskuvirheet_poistetut	= $verkkolaskut_reject;


	// ekotetaan javascripti‰ jotta saadaan pdf:‰t uuteen ikkunaan
	js_openFormInNewWindow();

	echo "<font class='head'>".t("Virheelliset verkkolaskut")."</font><hr>";

	if (!is_dir($verkkolaskuvirheet_poistetut) or !is_dir($verkkolaskuvirheet_vaarat) or !is_dir($verkkolaskuvirheet_kasittele)) {
		echo t("Kansioissa ongelmia").": $verkkolaskuvirheet_poistetut, $verkkolaskuvirheet_vaarat, $verkkolaskuvirheet_kasittele<br>";
		exit;
	}

	if (isset($tiedosto)) {
		if ($tapa == 'U') {
			rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
			echo "<font class='message'>".t("Tiedosto k‰sitell‰‰n uudestaan")."</font><br>";
		}

		if ($tapa == 'U_JA_P') {
			// P‰ivitet‰‰n toimittajan tunnus aineistoon, niin saadaan lasku reskontraan
			if ($kumpivoice == "FINVOICE") {
				$finkkari = simplexml_load_file($verkkolaskuvirheet_vaarat."/".$tiedosto);
				$finkkari->SellerPartyDetails->addChild('SellerPupesoftId', $tunnus);

				file_put_contents($verkkolaskuvirheet_vaarat."/".$tiedosto, $finkkari->asXML());
			}

			rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
			echo "<font class='message'>".t("Tiedosto k‰sitell‰‰n uudestaan")."</font><br>";
		}

		if ($tapa == 'P') {
			rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_poistetut."/".$tiedosto);
			echo "<font class='message'>".t("Tiedosto hyl‰ttiin")."</font><br>";
		}
	}

	$laskuri = 0;
	$valitutlaskut = 0;

	if ($handle = opendir($verkkolaskuvirheet_vaarat)) {

		require ("inc/verkkolasku-in.inc");

		echo "<table><tr>";
		echo "<th>".t("Vastaanottaja")."<br>".t("Yhtiˆ")."</th><th>".t("Toiminto")."</th><th>".t("Ovttunnus")."<br>".t("Y-tunnus")."</th><th>".t("Toimittaja")."</th><th>".t("Laskunumero")."<br>".t("Maksutili")."<br>".t("Summa")."</th><th>".t("Pvm")."</th></tr><tr>";

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($verkkolaskuvirheet_vaarat."/".$file)) {
				unset($yhtiorow);
				unset($xmlstr);

				list($lasku_yhtio, $lasku_toimittaja) = verkkolasku_in($verkkolaskuvirheet_vaarat."/".$file, FALSE);

				if ($lasku_yhtio["yhtio"] == $kukarow["yhtio"] or $lasku_yhtio["yhtio"] == "") {

					$valitutlaskut++;

					// Otetaan tarvittavat muuttujat t‰nnekin
					$xml = simplexml_load_string($xmlstr);

					// Katsotaan mit‰ aineistoa k‰pistell‰‰n
					if (strpos($file, "finvoice") !== false or strpos($file, "maventa") !== false or strpos($file, "apix") !== false) {
						require("inc/verkkolasku-in-finvoice.inc");
						$kumpivoice = "FINVOICE";
					}
					elseif (strpos($file, "teccominvoice") !== false){
						require("inc/verkkolasku-in-teccom.inc");
						$kumpivoice = "TECCOM";
					}
					elseif (strpos($file, "ORAO") !== false){
						require("inc/verkkolasku-in-unikko.inc");
						$kumpivoice = "ORAO";
					}
					else {
						require("inc/verkkolasku-in-pupevoice.inc");
						$kumpivoice = "PUPEVOICE";
					}

				 	$laskuttajan_osoite 	= "";
					$laskuttajan_postitp 	= "";
					$laskuttajan_postino 	= "";
					$laskuttajan_maa 		= "";
					$laskuttajan_tilino 	= "";

					if ($kumpivoice == "PUPEVOICE") {
						$laskuttajan_osoite 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@eC059.3042.1')));
						$laskuttajan_postitp 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3164')));
						$laskuttajan_postino 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3251')));
						$laskuttajan_maa 		= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3207')));

						$laskuttajan_tilino 	= utf8_decode(array_shift($xml->xpath('Group2/FII[@e3035="BF"]/@eC078.3194')));
					}
					elseif ($kumpivoice == "TECCOM") {
						$laskuttajan_osoite 	= utf8_decode($xml->InvoiceHeader->SellerParty->Address->Street1);
						$laskuttajan_postitp 	= utf8_decode($xml->InvoiceHeader->SellerParty->Address->City);
						$laskuttajan_postino 	= utf8_decode($xml->InvoiceHeader->SellerParty->Address->PostalCode);
						$laskuttajan_maa 		= utf8_decode($xml->InvoiceHeader->SellerParty->Address->CountryCode);
 						$laskuttajan_tilino		= $lasku_toimittaja["ultilno"];
						$laskuttajan_ovt		= $lasku_toimittaja["ovt_tunnus"];
						$laskuttajan_vat		= $lasku_toimittaja["ytunnus"];

					}
					elseif ($kumpivoice == "FINVOICE") {
						$laskuttajan_osoite 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerStreetName);
						$laskuttajan_postitp 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerTownName);
						$laskuttajan_postino 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerPostCodeIdentifier);
						$laskuttajan_maa 		= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->CountryCode);

						$laskuttajan_tilino		= utf8_decode($xml->SellerInformationDetails->SellerAccountDetails->SellerAccountID);
					}

					echo "<tr>";

					if ($lasku_yhtio["yhtio"] == $kukarow["yhtio"]) {
						echo "<td>$yhtio<br>$yhtiorow[nimi]</td>";
					}
					else {
						echo "<td>$yhtio<br>{$ostaja_asiakkaantiedot["nimi"]}<br><font class='error'>".t("HUOM: Laskun vastaanottaja ep‰selv‰")."!</font></td>";
					}

					echo "<td nowrap>";

					// Olisiko toimittaja sittenkin jossain (v‰‰rin perustettu)
					if ($lasku_toimittaja["tunnus"] == 0) {

						$laskuttajan_ovt  = trim($laskuttajan_ovt);
						$laskuttajan_vat  = trim($laskuttajan_vat);
						$laskuttajan_nimi = trim($laskuttajan_nimi);
						$laskuttajan_toimittajanumero = trim($laskuttajan_toimittajanumero);

						$toimittaja_array = array();

						if ($laskuttajan_ovt != "") {
							// 1 etsit‰‰n toimittaja ovttunnuksella
							$query  = "	SELECT *
										FROM toimi
										WHERE ovttunnus = '$laskuttajan_ovt'
										and ovttunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_ovt != "") {
							// 2 etsit‰‰n toimittaja ovt-tunnuksella ilman tarkenteita
							$yovt = substr($laskuttajan_ovt, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

							$query  = "	SELECT *
										FROM toimi
										WHERE ovttunnus = '$yovt'
										and ovttunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_vat != "") {
							// 3 etsit‰‰n toimittaja vat-numerolla
							$query  = "	SELECT *
										FROM toimi
										WHERE ovttunnus = '$laskuttajan_vat'
										and ovttunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_vat != "") {
							// 4 etsit‰‰n toimittaja vat-numerolla
							$query  = "	SELECT *
										FROM toimi
										WHERE ytunnus = '$laskuttajan_vat'
										and ytunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_ovt != "") {
							// 5 etsit‰‰n toimittaja ytunnuksella
							$yovt1 = substr(str_replace("0037", "", $laskuttajan_ovt), 0, 8); // mahdollisella etunollalla
							$yovt2 = (int) $yovt1; // ilman etunollaa

							$query  = "	SELECT *
										FROM toimi
										WHERE ytunnus in ('$yovt1', '$yovt2')
										and ytunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_vat != "") {
							$intvat = preg_replace("/[^0-9]/", "", $laskuttajan_vat);
							$intvat2 = (int) $intvat; // ilman etunollaa

							// 6 etsit‰‰n toimittaja vat-numerolla ilman FI-etuliitett‰
							$query  = "	SELECT *
										FROM toimi
										WHERE REPLACE(REPLACE(REPLACE(ytunnus,'FI',''),'fi',''),'-','') in ('$intvat', '$intvat2')
										and ytunnus not in ('','0')
										and yhtio = '$yhtiorow[yhtio]'
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_nimi != "") {
							$siivottu = preg_replace('/\b(oy|ab|ltd)\b/i', '', strtolower($laskuttajan_nimi));
							$siivottu = preg_replace('/^\s*/', '', $siivottu);
							$siivottu = preg_replace('/\s*$/', '', $siivottu);


							// 7 etsit‰‰n toimittaja nimell‰
							$query = "	SELECT *
										FROM toimi
										WHERE yhtio = '$yhtiorow[yhtio]'
										and nimi like '%$siivottu%'
										and nimi not in ('','0')
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}

						if ($laskuttajan_nimi != "") {

							$laskuttajan_nimi = str_replace(" ", "", $laskuttajan_nimi);

							// 8 etsit‰‰n IBAN-numerolla. (Maventa special: Jos laskulta puuttuu ytunnus, niin IBAN-tyˆnnetaan laskun nimi-kentt‰‰n...)
							$query = "	SELECT *
										FROM toimi
										WHERE yhtio = '$yhtiorow[yhtio]'
										and ultilno = '$laskuttajan_nimi'
										and ultilno not in ('','0')
										and tyyppi != 'P'";
							$result = pupe_query($query);

							while ($trow = mysql_fetch_assoc($result)) {
								$toimittaja_array[$trow["tunnus"]] = $trow;
							}
						}
					}

					if ($lasku_toimittaja["tunnus"] == 0) {
						if (count($toimittaja_array) > 0) {

							if ($kumpivoice == "FINVOICE") {
								echo t("Valitse toimittaja ja k‰sittele lasku uudestaan").":<br>";
								echo "<form method='post'>
										<input type='hidden' name = 'tiedosto' value='$file'>
										<input type='hidden' name = 'tapa' value='U_JA_P'>
										<input type='hidden' name = 'kumpivoice' value='$kumpivoice'>
										<select name='tunnus'>";

								foreach ($toimittaja_array as $lahellarow) {
									echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi] $lahellarow[nimitark]</option>";
								}

								echo "</select><input type='submit' value ='".t("K‰sittele uudestaan")."'></form><br><br>";
							}

							echo t("Muuta toimittajan tietoja").":<br>";
							echo "<form action='{$palvelin2}yllapito.php' method='post'>
									<input type = 'hidden' name = 'toim' value = 'toimi'>
									<select name='tunnus'>";

							foreach ($toimittaja_array as $lahellarow) {
								echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi] $lahellarow[nimitark]</option>";
							}

							echo "</select><input type='submit' value ='".t("P‰ivit‰")."'></form><br><br>";
						}

						echo t("Perusta uusi toimittaja").":<br>";
						echo "<form action='{$palvelin2}yllapito.php' method='post'>
								<input type = 'hidden' name = 'toim' value = 'toimi'>
								<input type = 'hidden' name = 'uusi' value = '1'>
								<input type = 'hidden' name = 't[1]' value = '$laskuttajan_nimi'>
								<input type = 'hidden' name = 't[3]' value = '$laskuttajan_osoite'>
								<input type = 'hidden' name = 't[5]' value = '$laskuttajan_postino'>
								<input type = 'hidden' name = 't[6]' value = '$laskuttajan_postitp'>
								<input type = 'hidden' name = 't[7]' value = '$laskuttajan_maa'>
								<input type = 'hidden' name = 't[20]' value = '$laskuttajan_tilino'>
								<input type = 'hidden' name = 't[59]' value = '$laskuttajan_vat'>
								<input type = 'hidden' name = 't[60]' value = '$laskuttajan_ovt'>
								<input type = 'hidden' name = 'lopetus' value = '".$palvelin2."verkkolaskuvirheet.php////'>
								<input type='submit' value = '".t("Perusta")."'></form><br><br>";
					}
					else {
						echo t("K‰sittele lasku uudestaan").":<br>";
						echo "<form method='post'>
								<input type='hidden' name = 'tiedosto' value ='$file'>
								<input type='hidden' name = 'tapa' value ='U'>
								<input type='submit' value = '".t("K‰sittele uudestaan")."'></form><br><br>";
					}

					echo t("Hylk‰‰ lasku").":<br>";
					echo "<form method='post'>
							<input type='hidden' name = 'tiedosto' value ='$file'>
							<input type='hidden' name = 'tapa' value ='P'>
							<input type='submit' value = '".t("Hylk‰‰")."'></form>";

					echo "</td>";

					echo "<td>$laskuttajan_ovt<br>$laskuttajan_vat</td>";
					echo "<td>$laskuttajan_nimi<br>$laskuttajan_osoite<br>$laskuttajan_postino<br>$laskuttajan_postitp<br>$laskuttajan_maa</td>";
					echo "<td>$laskun_numero<br>$laskuttajan_tilino<br>$laskun_summa_eur<br>";

					if ($kumpivoice == "PUPEVOICE") {
						$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
						$salasana		   = $yhtiorow['verkkosala_vas'];

						$timestamppi = gmdate("YmdHis")."Z";

						$urlhead = "http://www.verkkolasku.net";
						$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$laskun_ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

						$digest	 = md5($urlmain . "&" . $salasana);
						$url	 = $urlhead.$urlmain."&DIGEST=$digest";

						echo "<a href='$url' target='laskuikkuna'>". t('N‰yt‰ lasku')."</a>";
					}
					else {

						// Maventa- tai APIX-lasku, niin yritet‰‰n hakea laskun kuva mukaan
						if (preg_match("/(maventa|apix)_(.*?)_(maventa|apix)/", basename($file), $match)) {

							// Haetaan liitteet
							unset($liitefilet);
							$liitteet = exec("find $verkkolaskut_orig -name \"*{$match[1]}_{$match[2]}_{$match[1]}*\"", $liitefilet);

							if ($liitteet != "") {
								foreach ($liitefilet as $liitefile) {
									if (strtoupper(substr($liitefile, -4)) == ".PDF") {
										echo "<form id='form_1_$valitutlaskut' name='form_1_$valitutlaskut' method='post'>
											<input type='hidden' name = 'tee' value ='NAYTATILAUS'>
											<input type='hidden' name = 'pdf' value ='".urlencode(file_get_contents($liitefile))."'>
											<input type='submit' value = '".t("N‰yt‰ Pdf")."' onClick=\"js_openFormInNewWindow('form_1_$valitutlaskut', 'form_1_$valitutlaskut'); return false;\"></form>";
									}
								}
							}
						}

						echo "<form id='form_2_$valitutlaskut' name='form_2_$valitutlaskut' method='post'>
							<input type='hidden' name = 'tee' value ='NAYTATILAUS'>
							<input type='hidden' name = 'xml' value ='".urlencode($xmlstr)."'>
							<input type='submit' value = '".t("N‰yt‰ Finvoice")."' onClick=\"js_openFormInNewWindow('form_2_$valitutlaskut', 'form_2_$valitutlaskut'); return false;\"></form>";
					}

					echo "</td>";

					$tpp = substr($laskun_tapvm,6,2);
					$tpk = substr($laskun_tapvm,4,2);
					$tpv = substr($laskun_tapvm,0,4);

					echo "<td>".tv1dateconv($tpv."-".$tpk."-".$tpp)."</td>";
					echo "</tr>";
				}
			}
		}
		closedir($handle);
		echo "</table>";
	}

	if ($valitutlaskut == 0) {
		echo "<font class='message'>".t("Ei hyl‰ttyj‰ laskuja")."</font><br>";
	}

	require "inc/footer.inc";
?>