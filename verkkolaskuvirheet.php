<?php
	
	/* VANHAT KOODIT KOMMENTOITU POIS 13.12.2013, uudet koodit tilalla! */
	
	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	/*$useslave = 1;

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

	// määritellään polut
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

	// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
	js_openFormInNewWindow();

	echo "<font class='head'>".t("Virheelliset verkkolaskut")."</font><hr>";

	if (!is_dir($verkkolaskuvirheet_poistetut) or !is_dir($verkkolaskuvirheet_vaarat) or !is_dir($verkkolaskuvirheet_kasittele)) {
		echo t("Kansioissa ongelmia").": $verkkolaskuvirheet_poistetut, $verkkolaskuvirheet_vaarat, $verkkolaskuvirheet_kasittele<br>";
		exit;
	}

	if (isset($tiedosto)) {
		if ($tapa == 'U') {
			rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
			echo "<font class='message'>".t("Tiedosto käsitellään uudestaan")."</font><br>";
		}

		if ($tapa == 'P') {
			rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_poistetut."/".$tiedosto);
			echo "<font class='message'>".t("Tiedosto hylättiin")."</font><br>";
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

					// Otetaan tarvittavat muuttujat tännekin
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

					//Olisiko toimittaja sittenkin jossain (väärin perustettu)
					if ($lasku_toimittaja["tunnus"] == 0) {
						$siivottu = preg_replace('/\b(oy|ab|ltd)\b/i', '', strtolower($laskuttajan_nimi)); */
						//$siivottu = preg_replace('/^\s*/', '', $siivottu);
						/*$siivottu = preg_replace('/\s*$/', '', $siivottu);

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

							echo "</select><input type='submit' value ='".t("Päivitä toimittaja")."'></form><br>";
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
								<input type='submit' value = '".t("Käsittele uudestaan")."'></form><br>";
					}

					echo "<form action='$PHP_SELF' method='post'>
							<input type='hidden' name = 'tiedosto' value ='$file'>
							<input type='hidden' name = 'tapa' value ='P'>
							<input type='submit' value = '".t("Hylkää")."'></form>";

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

						echo "<a href='$url' target='laskuikkuna'>". t('Näytä lasku')."</a>";
					}
					else {
						echo "<form id='form_$valitutlaskut' name='form_$valitutlaskut' action='$PHP_SELF' method='post'>
							<input type='hidden' name = 'tee' value ='NAYTATILAUS'>
							<input type='hidden' name = 'xml' value ='".urlencode($xmlstr)."'>
							<input type='submit' value = '".t("Näytä lasku")."' onClick=\"js_openFormInNewWindow('form_$valitutlaskut', 'form_$valitutlaskut'); return false;\"></form>";
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
		echo "<font class='message'>".t("Ei hylättyjä laskuja")."</font><br>";
	} */
	
	/* Uudet koodit 13.12.2013, näillä saadaan edes joitain "kadonneita" laskuja näkyville */
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Virheelliset verkkolaskut")."</font><hr><br><br>";
	
	/* Lisätty 17.12.2013, lisätty haku henkilön yrityksille, joidenka laskut näytetään */
	$query = "	SELECT yhtio.nimi, yhtio.yhtio, if(yhtio.jarjestys=0, 9999, yhtio.jarjestys) jarj
				FROM kuka
				JOIN yhtio ON yhtio.yhtio = kuka.yhtio
				WHERE kuka.kuka	= '$kukarow[kuka]'
				and kuka.extranet = ''
				ORDER BY jarj, yhtio.nimi";

	$result = mysql_query($query) or pupe_error($query);
	
	$i = 0;
	$yhtiot = array();
	
	/* Laitetaan kaikki yhtiot talteen yhteen taulukkoon */
	while ($siirto = mysql_fetch_assoc($result))
	{
		$yhtiot[$i] = $siirto['yhtio'];
		$i++;
	}
	/* Lisäys päättyy 17.12.2013 */
	
	/* Lisätty 13.12.2013, tietokanta haku jolla saadaan mahdollisesti kaikki virheelliset laskut näkyviin yhteen paikkaan */
	$query = "SELECT yhtio, yhtio_nimi, nimi, laskunro, tunnus, vanhatunnus, tila, alatila, viite, luontiaika, ytunnus, hyvaksyja_nyt
			  FROM lasku 
			  WHERE tila = 'H' 
			  AND yhtio != '' 
			  AND hyvaksyja_nyt = '' 
			  ORDER BY yhtio, laskunro";
	$result = mysql_query($query);
	
	$kierros = 0;
	$edellinen['toimittaja_nimi'] = '';
	$edellinen['yhtio'] = '';
	
	/* Käydään läpi ns. eksyneitä/virheellisiä laskuja */
	while ($virheelliset_laskut = mysql_fetch_assoc($result))
	{
		/* Käydään läpi henkilön yritykset */
		foreach($yhtiot as $yhtio)
		{
			if ($virheelliset_laskut['yhtio'] == $yhtio)
			{
				$onko = true;
				break;
			}
		}
		
		if (empty($virheelliset_laskut) and $kierros == 0)
		{
			echo "Virheellisiä laskuja ei ole!<br>";
			break;
		}
		else if ($kierros == 0 and $onko == true)  // Ekalla kierroksella
		{
			$edellinen['toimittaja_nimi'] = $virheelliset_laskut['nimi'];
			$edellinen['yhtio'] = $virheelliset_laskut['yhtio'];
			
			echo "<table>  
					<tr>
						<font class='head'>$virheelliset_laskut[yhtio_nimi]</font><hr>
					</tr>
					<tr>
						<td style='font-weight:bold'>Myyjä</td>
						<td style='font-weight:bold'>Ostaja</td>
						<td style='font-weight:bold'>Laskunumero</td>
						<td style='font-weight:bold'>Viite</td>
						<td style='font-weight:bold'>Luontiaika</td>
						<td style='font-weight:bold'>Löytyykö toimittajista</td>
					</tr>";
		}
		else if ($edellinen['yhtio'] != '' and $edellinen['yhtio'] != $virheelliset_laskut['yhtio'] and $onko == true)  // Listataan uuden yhtiön laskuja
		{
			echo "</table><br><br>
					<table>
						<tr>
							<font class='head'>$virheelliset_laskut[yhtio_nimi]</font><hr>
						</tr>
						<tr>
							<td style='font-weight:bold'>Myyjä</td>
							<td style='font-weight:bold'>Ostaja</td>
							<td style='font-weight:bold'>Laskunumero</td>
							<td style='font-weight:bold'>Viite</td>
							<td style='font-weight:bold'>Luontiaika</td>
							<td style='font-weight:bold'>Löytyykö toimittajista</td>
						</tr>";
		}
		
		if ($onko == true)
		{
			/* Listataan laskun tietoja ja katsotaan onko samantyyppisellä nimellä toimittajaa ko. yrityksellä */
			echo "<tr>
						<td>$virheelliset_laskut[yhtio_nimi]</td>
						<td>$virheelliset_laskut[nimi]</td>
						<td>$virheelliset_laskut[laskunro]</td>
						<td>$virheelliset_laskut[viite]</td>
						<td>$virheelliset_laskut[luontiaika]</td>
						<td>";
					
			$siivottu = preg_replace('/\b(oy|ab|ltd)\b/i', '', strtolower($virheelliset_laskut['nimi']));
			$siivottu = preg_replace('/^\s*/', '', $siivottu);
			$siivottu = preg_replace('/\s*$/', '', $siivottu);
			
			$query = "	SELECT tunnus, nimi
					FROM toimi
					WHERE yhtio = '$virheelliset_laskut[yhtio]'
					and nimi like '%$siivottu%'";
			$lahellaresult = mysql_query($query) or die ("$query<br><br>".mysql_error());
			
			/* Ilmoitetaan löytyykö yrityksestä samantyyppisellä nimellä toimittajaa */
			if (mysql_num_rows($lahellaresult) > 0)
			{
				$lahella = mysql_fetch_assoc($lahellaresult);
				echo "<font color='darkgreen'>$lahella[nimi] (tunnus: $lahella[tunnus])</font>";
			}
			else
			{
				echo "<font class='error'>EI LÖYDY</font>";
			}

			echo "</td></tr>";
		}
		
		$edellinen['toimittaja_nimi'] = $virheelliset_laskut['nimi'];
		$edellinen['yhtio'] = $virheelliset_laskut['yhtio'];
		$onko = false;
		$kierros++;
		
	}
	echo "</table>";
	
	require "inc/footer.inc";
?>