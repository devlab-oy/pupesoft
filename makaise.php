<?php
	// Tätä kutsutaan makai.php:stä, jos kysessä on ruotsalainen yhtiö. Siksi ei parametrejä

	echo "<font class='head'>".t("Pankkiaineistot")."</font><hr>";
	if (strtoupper($yhtiorow['maakoodi']) != 'SE') {
		echo "<font class='error'>".t("Tämä on vain ruotsalaisille yrityksille!")."</font>";
		exit;
	}

	// --- LM03 AINEISTO

	//Tutkitaan onko kotimaan aineistossa monta maksupäivää?
	$query = "	SELECT distinct(olmapvm)
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and tila = 'P' and
				maakoodi = 'se' and
				maksaja = '$kukarow[kuka]'
				ORDER BY 1";
	$pvmresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($pvmresult) != 0) {
		
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$nimi = "/tmp/bg-" . date("d.m.y.H.i.s") . ".txt";			
		}
		else {			
			$nimi = $yhtiorow['pankki_polku'] . "bg-" . date("d.m.y.H.i.s") . ".txt";
		}		
		
		$toot = fopen($nimi,"w+");
		
		if (!$toot) {
			echo t("En saanut tiedostoa auki! Tarkista polku yritykseltä!");
			exit;
		}
		
		echo "<font class='message'>".sprintf(t("Kotimaan maksujen tiedoston nimi on: %s"),$nimi)."</font><br>";
	}
	else {
		echo "<font class='message'>".t("Sopivia laskuja ei löydy")."</font>";
	}
	
	while ($pvmrow=mysql_fetch_array($pvmresult)) {
		echo "<font class='message'>".t("Maksupäivä").": $pvmrow[0]</font><br>";
		$makskpl = 0;
		$makssumma = 0;
		$tilinoarray=array();

		//Tutkitaan onko kotimaan aineistossa hyvityslaskuja
		$query = "	SELECT maksu_tili, tilinumero, nimi
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila = 'P' and
					maakoodi = 'se' and
					summa < 0 and
					maksaja = '$kukarow[kuka]' 
					and olmapvm = '$pvmrow[olmapvm]'
					GROUP BY maksu_tili, tilinumero";
	
		$result = mysql_query($query) or pupe_error($query);
	
		//Löytyykö hyvityksiä?
		if (mysql_num_rows($result) != 0) {
			echo "<font class='message'>".t("Tarkistan hyvityslaskut!")."</font><br>";
			while ($laskurow=mysql_fetch_array ($result)) {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and tila = 'P' and
							maakoodi = 'se' and
							maksaja = '$kukarow[kuka]' and
							tilinumero='$laskurow[tilinumero]' and
							maksu_tili='$laskurow[maksu_tili]' and
							olmapvm = '$pvmrow[olmapvm]'";
				$xresult = mysql_query($query) or pupe_error($query);
				$hyvityssumma=0;
				while ($hyvitysrow=mysql_fetch_array ($xresult)) {
					//Meneekö tilitys plussalle??
					if ($hyvitysrow['alatila'] == 'K') { // maksetaan käteisalennuksella
						$hyvityssumma += $hyvitysrow['summa'] - $hyvitysrow['kasumma'];
					}
					else {
						$hyvityssumma += $hyvitysrow['summa'];
					}
				}
				$hyvityssumma = round($hyvityssumma,2);
				$summaarray[$laskurow['maksu_tili']] [$laskurow['tilinumero']]=$hyvityssumma;
				$tilinoarray[$laskurow['maksu_tili']][$laskurow['tilinumero']]=$laskurow['tilinumero'];
				if ($hyvityssumma < 0.01) {
					echo "<font class='error'>$laskurow[nimi] ($laskurow[tilinumero]) ".t("tililtä")." $laskurow[maksu_tili] ".t("hyvitykset suuremmat kuin veloitukset. Koko aineisto hylätään")."</font><br>";
					exit;
				}
			}
			
			echo "<font class='message'>".t("Hyvitykset ok!")."</font><br>";
		}
		
		// --- LM03 AINEISTO MAKSUTILIT
		$query = "	SELECT yriti.tunnus, yriti.tilino, yriti.nimi nimi
					FROM lasku, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' and
					maakoodi = 'se' and
					yriti.tunnus = maksu_tili and
					yriti.yhtio = lasku.yhtio and
					maksaja = '$kukarow[kuka]' and
					olmapvm = '$pvmrow[olmapvm]'
					GROUP BY yriti.tilino";
		$yritiresult = mysql_query($query) or pupe_error($query);
				
		if (mysql_num_rows($yritiresult) != 0) {

			while ($yritirow=mysql_fetch_array ($yritiresult)) {
			
				require "inc/bginotsik.inc";
			
				//require "inc/lm03hyvitykset.inc"; //Täällä käistellään kaikki laskut joihin liittyy hyvityksiä
				
				// Yritämme nyt välittää maksupointterin $laskusis1:ssä --> $laskurow[9] --> lasku.tunnus
				$query = "	SELECT maksu_tili, viite, viesti, tilinumero,
					 		lasku.tunnus, sisviesti2, yriti.tilino, alatila, kasumma, summa
							FROM lasku, yriti
							WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' and
							maakoodi = 'se' and
							yriti.tunnus = maksu_tili and
							yriti.yhtio = lasku.yhtio and
							maksaja = '$kukarow[kuka]' and
							maksu_tili = $yritirow[tunnus] and
							olmapvm = '$pvmrow[olmapvm]'
							ORDER BY tilinumero, summa desc";
			
				$result = mysql_query($query) or pupe_error($query);
			
				while ($laskurow=mysql_fetch_array ($result)) {
					require "inc/bginrivi.inc";
					$makskpl += 1;
					if ($laskurow['alatila'] == 'K') { // maksetaan käteisalennuksella
						$makssumma += $laskurow['summa'] - $laskurow['kasumma'];
					}
					else {
						$makssumma += $laskurow['summa'];
					}
				}
				require "inc/bginsumma.inc";
				
				echo "<font class='message'>".sprintf(t("Tilin %s loppusumma on %f. Summa koostuu %d maksusta"), $yritirow['nimi'], $makssumma, $makskpl)."</font><br>";
				
				$query = "UPDATE lasku SET tila = 'Q'
				          WHERE lasku.yhtio = '$kukarow[yhtio]' 
				          and tila = 'P' 
				          and maakoodi = 'se' 
				          and maksaja = '$kukarow[kuka]' 
				          and maksu_tili='$yritirow[tunnus]' 
				          and olmapvm = '$pvmrow[olmapvm]'
				          ORDER BY yhtio, tila";
		
				$result = mysql_query($query) or pupe_error($query);
				$makskpl = 0;
				$makssumma = 0;
			}
	
			$makssumma = 0;
		}
		else {
			echo "<font class='error'>".t("Sopivia laskuja ei löydy")."</font>";
		}
	}
	if (is_resource($toot)) { 
		fclose($toot);
		
		//Laitetaan sähköpostiin jos yhtiorow pankki_polusta löytyy @-merkki
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$liite = $nimi;
			$kutsu = t("Maksuaineisto");
			$kukarow["eposti"] = $yhtiorow["pankki_polku"];
			$ctype = "TEXT";
			
			require("inc/sahkoposti.inc");
				
			if (substr($nimi,0,5) == "/tmp/" and $boob !== FALSE) {
				system("rm -f $nimi");
			}				
		}		
	}
	
	//----------- LUM3 AINEISTO --------------------------
	echo "<br><br><br><font class='head'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar</font><hr>";

	$query = "LOCK TABLES lasku write, yriti read, sanakirja write, valuu read";
	$result = mysql_query($query) or pupe_error($query);
		
	$query = "	SELECT maksu_tili, lasku.nimi, nimitark, osoite, osoitetark, maakoodi, postino, postitp,
				summa, lasku.valkoodi, viite, viesti, ultilno, lasku.tunnus, sisviesti2, yriti.tilino,
				maa, maakoodi,
				pankki1, pankki2, pankki3, pankki4,
				swift, alatila, kasumma,
				lasku.nimi, nimitark, osoite, ytunnus, kurssi
				FROM lasku, yriti, valuu
				WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' and
				maakoodi <> 'se' and
				yriti.tunnus = maksu_tili and
				yriti.yhtio = lasku.yhtio and
				valuu.yhtio = lasku.yhtio and
				valuu.nimi = lasku.valkoodi and
				maksaja = '$kukarow[kuka]'
				ORDER BY maksu_tili, lasku.valkoodi, ytunnus";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		 	echo "<font class='message'>".t("Sopivia laskuja ei löydy")."</font>";
	}
	else {
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$nimi = "/tmp/bgut-" . date("d.m.y.H.i.s") . ".txt";			
		}
		else {			
			$nimi = $yhtiorow['pankki_polku'] . "bgut-" . date("d.m.y.H.i.s") . ".txt";
		}
		
		$toot = fopen($nimi,"w+");
		if (!$toot) {
			echo t("En saanut tiedostoa auki! Tarkista polku yritykseltä!");
			exit;
		}
		$makskpl = 0;
		$makssumma = 0;
		$maksulk = 0;
		echo "<font class='message'>". sprintf(t("Ulkomaan maksujen tiedoston nimi on: %s"),$nimi) . "</font><br>";
		while ($laskurow=mysql_fetch_array ($result)) {

			if ($laskurow['maksu_tili'] != $edmaksu_tili) {
				if (strlen($edmaksu_tili) > 0) {
					echo "<font class='message'>".sprintf(t("Tilin %s loppusumma on %f. Summa koostuu %d maksusta"), $yritirow['nimi'], $makssumma, $makskpl)."</font><br>";
					require "inc/bgutsumma.inc";
					$makskpl = 0;
					$makssumma = 0;
					$maksulk = 0;
				}

				require "inc/bgutotsik.inc";
				$edmaksu_tili = $laskurow['maksu_tili'];
				$edvalkoodi = $laskurow['valkoodi'];
			}
			require "inc/bgutrivi.inc";
			$makskpl += 1;
			$makssumma += $laskusumma; //ilmestyy maagisesti bgutrivistä
			$maksulk += $ulklaskusumma;
		}
		echo "<font class='message'>".sprintf(t("Tilin %s loppusumma on %f. Summa koostuu %d maksusta"), $yritirow['nimi'], $makssumma, $makskpl)."</font><br>";
		
		require "inc/bgutsumma.inc";
		
		fclose($toot);
		
		//Laitetaan sähköpostiin jos yhtiorow pankki_polusta löytyy @-merkki
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$liite = $nimi;
			$kutsu = t("Ulkomaanmaksuaineisto");
			$kukarow["eposti"] = $yhtiorow["pankki_polku"];
			$ctype = "TEXT";
			
			require("inc/sahkoposti.inc");
				
			if (substr($nimi,0,5) == "/tmp/" and $boob !== FALSE) {
				system("rm -f $nimi");
			}
		}
		
		$query = "	UPDATE lasku 
					SET tila = 'Q'
					WHERE lasku.yhtio = '$kukarow[yhtio]' 
					and tila = 'P' 
					and maakoodi <> 'se' 
					and maksaja = '$kukarow[kuka]'
					ORDER BY yhtio,tila";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}
	require "inc/footer.inc";
?>
