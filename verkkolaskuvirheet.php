<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	if ($_REQUEST["tee"] == "NAYTATILAUS") {
		$no_head = "yes";

		header("Content-type: text/xml");
		flush();
	}

	require ("inc/parametrit.inc");

	if ($_REQUEST["tee"] == "NAYTATILAUS") {
		$xml = urldecode($_REQUEST["xml"]);
		$xml = str_replace("<!DOCTYPE Finvoice SYSTEM \"", "<!DOCTYPE Finvoice SYSTEM \"$palvelin2", $xml);
		$xml = str_replace("<?xml-stylesheet type=\"text/xsl\" href=\"", "<?xml-stylesheet type=\"text/xsl\" href=\"$palvelin2", $xml);
		echo $xml;
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
		echo "<th>".t("Toiminto")."</th><th>".t("Ovttunnus")."<br>".t("Y-tunnus")."</th><th>".t("Toimittaja")."</th><th>".t("Laskunumero")."<br>".t("Maksutili")."<br>".t("Summa")."</th><th>".t("Pvm")."</th></tr><tr>";

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($verkkolaskuvirheet_vaarat."/".$file)) {
				unset($yhtiorow);
				unset($xmlstr);

				list($lasku_yhtio, $lasku_toimittaja) = verkkolasku_in($verkkolaskuvirheet_vaarat."/".$file, FALSE);

				if ($lasku_yhtio["yhtio"] == $kukarow["yhtio"]) {

					$valitutlaskut++;

					// Otetaan tarvittavat muuttujat t‰nnekin
					$xml = simplexml_load_string($xmlstr);

					if (strpos($file, "finvoice-") !== false) {
						require("inc/verkkolasku-in-finvoice.inc");

						$kumpivoice = "FINVOICE";
					}
					else {
						require("inc/verkkolasku-in-pupevoice.inc");

						$kumpivoice = "PUPEVOICE";
					}

					if ($kumpivoice == "PUPEVOICE") {
						$laskuttajan_osoite 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@eC059.3042.1')));
						$laskuttajan_postitp 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3164')));
						$laskuttajan_postino 	= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3251')));
						$laskuttajan_maa 		= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3207')));

						$laskuttajan_tilino 	= utf8_decode(array_shift($xml->xpath('Group2/FII[@e3035="BF"]/@eC078.3194')));
					}
					else {
						$laskuttajan_osoite 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerStreetName);
						$laskuttajan_postitp 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerTownName);
						$laskuttajan_postino 	= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->SellerPostCodeIdentifier);
						$laskuttajan_maa 		= utf8_decode($xml->SellerPartyDetails->SellerPostalAddressDetails->CountryCode);

						$laskuttajan_tilino		= utf8_decode($xml->SellerInformationDetails->SellerAccountDetails->SellerAccountID);

					}

					echo "<tr><td>";

					//Olisiko toimittaja sittenkin jossain (v‰‰rin perustettu)
					if ($lasku_toimittaja["tunnus"] == 0) {
						$siivottu = preg_replace('/\b(oy|ab|ltd)\b/i', '', strtolower($laskuttajan_nimi));
						$siivottu = preg_replace('/^\s*/', '', $siivottu);
						$siivottu = preg_replace('/\s*$/', '', $siivottu);

						$query = "	SELECT tunnus, nimi
									FROM toimi
									WHERE yhtio = '$yhtiorow[yhtio]'
									and nimi like '%$siivottu%'";
						$lahellaresult = mysql_query($query) or die ("$query<br><br>".mysql_error());
					}

					if ($lasku_toimittaja["tunnus"] == 0) {
						if (mysql_num_rows($lahellaresult) > 0) {

							echo "<form action='".$palvelin2."yllapito.php' method='post'>
									<input type = 'hidden' name = 'toim' value = 'toimi'>
									<select name='tunnus'>";

							while ($lahellarow = mysql_fetch_array($lahellaresult)) {
								echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi]";
							}

							echo "</select><input type='submit' value ='".t("P‰ivit‰ toimittaja")."'></form><br>";
						}

						echo "<form action='".$palvelin2."yllapito.php' method='post'>
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
								<input type='submit' value = '".t("Perusta toimittaja")."'></form><br>";
					}
					else {
						echo "<form action='$PHP_SELF' method='post'>
								<input type='hidden' name = 'tiedosto' value ='$file'>
								<input type='hidden' name = 'tapa' value ='U'>
								<input type='submit' value = '".t("K‰sittele uudestaan")."'></form><br>";
					}

					echo "<form action='$PHP_SELF' method='post'>
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
						echo "<form id='form_$valitutlaskut' name='form_$valitutlaskut' action='$PHP_SELF' method='post'>
							<input type='hidden' name = 'tee' value ='NAYTATILAUS'>
							<input type='hidden' name = 'xml' value ='".urlencode($xmlstr)."'>
							<input type='submit' value = '".t("N‰yt‰ lasku")."' onClick=\"js_openFormInNewWindow('form_$valitutlaskut', 'form_$valitutlaskut'); return false;\"></form>";
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