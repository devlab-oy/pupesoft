<?php

if (strpos($_SERVER['SCRIPT_NAME'], "manuaalinen_suoritusten_kohdistus")  !== FALSE) {
	require ("../inc/parametrit.inc");
}

require_once("../inc/tilinumero.inc");

function kopioitiliointipaittain($tunnus, $type = '') {

	global $kukarow;

	// jos type yks etsitään aputunnuksella
	if ($type == 1) {
		$query = "SELECT * FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and aputunnus = '$tunnus'";
	}
	else {
		$query = "SELECT * FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
	}
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo "Tiliöintirivi kateissa systeemivirhe!";
		exit;
	}

	$tiliointirow = mysql_fetch_array($result);
	$query = "INSERT INTO tiliointi SET ";

	for ($i = 0; $i < mysql_num_fields($result); $i++) {

		if (mysql_field_name($result, $i) == 'laatija') {
			$query .= "laatija = '$kukarow[kuka]', ";
		}
		elseif (mysql_field_name($result, $i) == 'laadittu') {
			$query .= "laadittu = now(), ";
		}
		elseif (mysql_field_name($result, $i) == 'tapvm') {
			$query .= "tapvm = now(), ";
		}
		elseif (mysql_field_name($result, $i) == 'summa') {
			$query .= "summa = summa * -1, ";
		}
		elseif (mysql_field_name($result, $i) != 'tunnus') {
			$query .= mysql_field_name($result,$i) . " = '" . $tiliointirow[$i] . "', ";
		}

	}

	$query = substr($query,0,-2);
	$result = mysql_query($query) or pupe_error($query);
}

if ($tila == "muokkaasuoritusta") {

	if ($saamis == $kassa or $saamis == "" or $kassa == "") {
		echo "<font class='error'>Virheellisesti valittu kassa-/saamistili!</font><br><br>";
	}
	else {
		// laitetaan ensiksi kassatiliöinti pointtaamaan saamistiliöintiin
		$query = "UPDATE tiliointi set aputunnus = '$saamis' where yhtio = '$kukarow[yhtio]' and tunnus = '$kassa'";
		$result = mysql_query($query) or pupe_error($query);

		// sitten laitetaan suoritus pointtaamaan saamistiliöintiin
		$query = "UPDATE suoritus set ltunnus = '$saamis' where yhtio = '$kukarow[yhtio]' and tunnus = '$suoritus_tunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$tila = "vaihdasuorituksentili";

}

if ($tila == "vaihdasuorituksentili") {
	$myyntisaamiset = 0;

	// katotaan löytyykö tili
	$query = "select tilino from tili where yhtio='$kukarow[yhtio]' and tilino='$vastatili'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t("Virheellinen vastatilitieto")."!";
		exit;
	}

	$query = "	SELECT *
				FROM suoritus
				WHERE tunnus = '$suoritus_tunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {

		$suoritus = mysql_fetch_array($result);

		// katotaan löytyykö tiliöinti
		$query = "	SELECT tunnus
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$suoritus[ltunnus]' AND korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		// tiliöintiä ei löydy tai halutaan mennä runkslaamaan
		if (mysql_affected_rows() == 0 or $vaihdasuorituksentiliointitunnuksia != "") {

			// katotaan onko se ylikirjattu
			$query = "	SELECT *
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]' AND
						tunnus = '$suoritus[ltunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			if ($rivi = mysql_fetch_array($result)) {

				// löydettiin dellattu tiliöinti, annetaan käyttäjän valita saamistili ja kassatili tälle suoritukselle...
				// listataan kaikki tositteen validit tapahtumat
				$query = "	SELECT tiliointi.*, tili.nimi
							FROM tiliointi
							LEFT JOIN tili on (tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino)
							WHERE tiliointi.yhtio = '$kukarow[yhtio]' AND
							tiliointi.ltunnus = '$rivi[ltunnus]' AND
							tiliointi.korjattu = ''";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					echo "<font class='error'>Tällä suorituksella ei ole tiliöintejä! ($suoritus[nimi_maksaja] $suoritus[summa] $suoritus[valkoodi]) Lisää se tositteelle saamis- ja kassatili ja kokeile uudestaan!</font>";
					echo " (<a href='../muutosite.php?tee=E&tunnus=$rivi[ltunnus]'>Muokkaa tositetta</a>)<br><br>";
				}
				else {
					echo "<font class='message'>Tällä suorituksella ei ole saamis-/kassatiliä! ($suoritus[nimi_maksaja] $suoritus[summa] $suoritus[valkoodi])<br>";
					echo "Valitse suorituksen tiliöinnit listalta: </font>";
					echo " (<a href='../muutosite.php?tee=E&tunnus=$rivi[ltunnus]'>Muokkaa tositetta</a>)<br><br>";

					echo "<table>";
					echo "<tr>";
					echo "<th>saamis</th>";
					echo "<th>kassa</th>";
					echo "<th>tilino</th>";
					echo "<th>kustp</th>";
					echo "<th>kohde</th>";
					echo "<th>projekti</th>";
					echo "<th>selite</th>";
					echo "<th>summa</th>";
					echo "<th>alv</th>";
					echo "<th>tapvm</th>";
					echo "<th>laatija</th>";
					echo "</tr>";

					echo "<form method='post'>";
					echo "<input type='hidden' name='tila' value='muokkaasuoritusta'>";
					echo "<input type='hidden' name='vastatili' value='$vastatili'>";
//					echo "<input type='hidden' name='tunnus' value='$tunnus'>";
					echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas_tunnus'>";
					echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";
					echo "<input type='hidden' name='suoritus_tunnus' value='$suoritus_tunnus'>";

					while ($tilioinnit = mysql_fetch_array($result)) {
						echo "<tr>";
						$chk="";
						if ($tilioinnit["tunnus"] == $suoritus["ltunnus"]) $chk = "checked";
						echo "<td><input type='radio' name='saamis' value='$tilioinnit[tunnus]' $chk></td>";
						$chk="";
						if ($tilioinnit["aputunnus"] == $suoritus["ltunnus"]) $chk = "checked";
						echo "<td><input type='radio' name='kassa' value='$tilioinnit[tunnus]' $chk></td>";
						echo "<td>$tilioinnit[tilino] / $tilioinnit[nimi]</td>";
						echo "<td>$tilioinnit[kustp]</td>";
						echo "<td>$tilioinnit[kohde]</td>";
						echo "<td>$tilioinnit[projekti]</td>";
						echo "<td>$tilioinnit[selite]</td>";
						echo "<td>$tilioinnit[summa]</td>";
						echo "<td>$tilioinnit[alv]</td>";
						echo "<td>$tilioinnit[tapvm]</td>";
						echo "<td>$tilioinnit[laatija] @ $tilioinnit[laadittu]</td>";
						echo "</tr>";
					}
					echo "</table>";

					echo "<br><input type='submit' value='Päivitä suoritus'>";
					echo "</form>";

					// tähän loppuu tämä rundi
					exit;
				}
			}
			else {
				echo "<font class='error'>".t("Emme löydä tälle suoritukselle mitään kirjanpitotapahtumia. Ei voida jatkaa")."!</font><br><br>";
			}
		}
		else {
			// Muutetaan tiliöinti
			$query = "	UPDATE tiliointi
						SET tilino = '$vastatili'
						WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$suoritus[ltunnus]' AND korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	else {
		echo "<font class='error'>".t("Suoritus kateissa, tiliä ei voida vaihtaa")."!</font><br><br>";
	}

	$tila = "kohdistaminen";
}

if ($tila == 'tee_kohdistus') {
	// Tehdään error tsekit
	$query = "LOCK TABLES yriti READ, yhtio READ, tili READ, lasku WRITE, suoritus WRITE, tiliointi WRITE, tiliointi as tiliointi2 WRITE, sanakirja WRITE";
	$result = mysql_query($query) or pupe_error($query);

	// haetaan suorituksen tiedot
	$query = "	SELECT suoritus.tunnus tunnus,
				suoritus.asiakas_tunnus asiakas_tunnus,
				suoritus.tilino tilino,
				suoritus.summa summa,
				suoritus.valkoodi valkoodi,
				suoritus.kurssi kurssi,
				suoritus.asiakas_tunnus asiakastunnus,
				suoritus.kirjpvm maksupvm,
				suoritus.ltunnus ltunnus,
				suoritus.nimi_maksaja nimi_maksaja,
				suoritus.viesti,
				yriti.oletus_rahatili kassatilino,
				tiliointi.tilino myyntisaamiset_tilino,
				yhtio.myynninkassaale kassa_ale_tilino,
				yhtio.pyoristys pyoristys_tilino,
				yhtio.myynninvaluuttaero myynninvaluuttaero_tilino
				FROM suoritus
				JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
				JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio and tiliointi.tunnus = suoritus.ltunnus and tiliointi.korjattu = '')
				JOIN tiliointi AS tiliointi2 ON (tiliointi2.yhtio = suoritus.yhtio and tiliointi2.aputunnus = tiliointi.tunnus and tiliointi2.korjattu = '')
				JOIN yhtio ON (yhtio.yhtio = suoritus.yhtio)
				WHERE suoritus.yhtio = '$kukarow[yhtio]' and
				suoritus.tunnus = '$suoritus_tunnus' and
				suoritus.ltunnus != 0 and
				suoritus.kohdpvm = '0000-00-00'";
	$result = mysql_query($query) or pupe_error($query);

	// tehdään nätimpi errorihandlaus
	if (mysql_num_rows($result) == 0) {
	
		echo "<font class='error'>".t("Suoritus katosi")."!</font><br>";
		
		$tila 	= 'kohdistaminen';
		$query 	= "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		$suoritus = mysql_fetch_array($result);
		
		$laskutunnukset = "";
		$laskutunnuksetkale = "";

		// $lasku_tunnukset[]
		if (is_array($lasku_tunnukset)){
			for ($i=0;$i<sizeof($lasku_tunnukset);$i++) {
				if($i!=0) $laskutunnukset=$laskutunnukset . ",";
				$laskutunnukset=$laskutunnukset . "$lasku_tunnukset[$i]";
			}
		}
		else {
			$laskutunnukset = 0;
		}

		// $lasku_tunnukset_kale[]
		if (is_array($lasku_tunnukset_kale)) {
			for ($i=0;$i<sizeof($lasku_tunnukset_kale);$i++) {
				if($i!=0) $laskutunnuksetkale=$laskutunnuksetkale . ",";
				$laskutunnuksetkale=$laskutunnuksetkale . "$lasku_tunnukset_kale[$i]";
			}
		}
		else {
			$laskutunnuksetkale = 0;
		}

		// Tarkistetaan muutama asia
		if ($laskutunnukset == 0 and $laskutunnuksetkale == 0) {
			echo "<font class='error'>".t("Olet kohdistamassa, mutta et ole valinnut mitään kohdistettavaa")."!</font><br>";

			$tila 	= 'kohdistaminen';
			$query 	= "UNLOCK TABLES";
			$result = mysql_query($query) or pupe_error($query);
		}

		if ($osasuoritus == 1) {
			if (sizeof($lasku_tunnukset) != 1) {
				echo "<font class='error'>".t("Jos osasuoritus, pitää valita vain ja ainoastaan yksi lasku")."</font><br>";
				
				$tila 	= 'kohdistaminen';
				$query 	= "UNLOCK TABLES";
				$result = mysql_query($query) or pupe_error($query);
			}
			if (sizeof($lasku_tunnukset_kale) > 0) {
				echo "<font class='error'>".t("Jos osasuoritus, ei voi valita kassa-alennusta")."</font><br>";
				
				$tila 	= 'kohdistaminen';
				$query 	= "UNLOCK TABLES";
				$result = mysql_query($query) or pupe_error($query);
			}
		
			
			//Haetaan osasuoritettava lasku
			if (strtoupper($suoritus['valkoodi']) != strtoupper($yhtiorow['valkoodi'])) {
				$query = "SELECT summa_valuutassa-saldo_maksettu_valuutassa summa ";
			}
			else {
				$query = "SELECT summa-saldo_maksettu summa ";
			}
			
			$query .= "	FROM lasku
						WHERE tunnus 	= '$laskutunnukset'
						and  mapvm		= '0000-00-00'";
			$jaresult = mysql_query($query) or pupe_error($query);
			$jarow = mysql_fetch_array($jaresult);

			if($suoritus["summa"] < 0 and $jarow["summa"] < 0) {
	  			$jaljella = round($jarow["summa"]-$suoritus["summa"]);
			}
			else {
				$jaljella = round($suoritus["summa"]-$jarow["summa"]);				
			}
			
			if ($jaljella > 0) {
				echo "<font class='error'>".t("Et voi osasuorittaa, jos jäjellä on positiivinen summa")."!</font><br>";
				
				$tila 	= 'kohdistaminen';
				$query 	= "UNLOCK TABLES";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		
		$query = "	SELECT * FROM suoritus
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$suoritus_tunnus' and
					ltunnus != 0 and
					kohdpvm = '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suoritus katosi!")."</font><br>";
			
			$tila 	= 'kohdistaminen';
			$query 	= "UNLOCK TABLES";
			$result = mysql_query($query) or pupe_error($query);
		}

		$errorrow = mysql_fetch_array ($result);

		$query = "	SELECT * FROM yriti
					WHERE yhtio = '$errorrow[yhtio]' and
					tilino = '$errorrow[tilino]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suorituksen tilinumero ei löydy! Käy lisäämässä tili yhtiölle!")."</font><br>";
			
			$tila 	= 'kohdistaminen';
			$query 	= "UNLOCK TABLES";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	SELECT * FROM tiliointi
					WHERE yhtio = '$errorrow[yhtio]' and
					tunnus = '$errorrow[ltunnus]' and
					korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suorituksen saamiset-tiliöinnit eivät löydy! Vaihda suorituksen saamiset-tiliä!")."</font><br>";
			
			$tila 	= 'kohdistaminen';
			$query 	= "UNLOCK TABLES";
			$result = mysql_query($query) or pupe_error($query);
		}

		$errorrow = mysql_fetch_array ($result);

		$query = "	SELECT * FROM tiliointi
					WHERE yhtio = '$errorrow[yhtio]' and
					aputunnus = '$errorrow[tunnus]' and
					korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suorituksen raha-tiliöinnit eivät löydy!")."</font><br>";
			
			$tila 	= 'kohdistaminen';
			$query 	= "UNLOCK TABLES";
			$result = mysql_query($query) or pupe_error($query);
		}
	}	
}

if ($tila == 'tee_kohdistus') {
	
	// Errortsekit on tehty, nyt kohdistetaan
	if (trim($suoritus["viesti"]) != "") $suoritus["viesti"] = " / $suoritus[viesti]";
	
	// otetaan talteen, jos suorituksen kassatilillä on kustannuspaikka.. tarvitaan jos suoritukselle jää saldoa
	$query = "	SELECT * 
				FROM tiliointi 
				WHERE aputunnus	= '$suoritus[ltunnus]' 
				and yhtio		= '$kukarow[yhtio]' 
				and tilino		= '$suoritus[kassatilino]' 
				and korjattu	= ''";
	$result = mysql_query($query) or pupe_error($query);
	$apurow = mysql_fetch_array($result);
	$apukustp = $apurow["kustp"];

	// haetaan laskujen tiedot
	$laskujen_summa=0;

	if ($osasuoritus == 1) {
		//*** Tässä yritetään hoitaa osasuoritus mahdollisimman elegantisti ***

		//Haetaan osasuoritettava lasku
		$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 0 AS alennus, tunnus, vienti_kurssi
					FROM lasku
					WHERE tunnus 	= '$laskutunnukset'
					and  mapvm		= '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Osasuoritettava lasku katosi! (joku maksoi sen sinua ennen?)")."</font><br>";
			exit;
		}
		$lasku = mysql_fetch_array($result);

		$ltunnus			= $lasku["tunnus"];
		$maksupvm			= $suoritus["maksupvm"];
		$suoritussumma		= $suoritus["summa"];
		$suoritussummaval	= $suoritus["summa"];

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

		$query_korko = "SELECT viikorkopros * $suoritussumma * (if(to_days('$maksupvm')-to_days(erpcm) > 0, to_days('$maksupvm')-to_days(erpcm), 0))/36500 korkosumma 
						from lasku 
						WHERE tunnus='$ltunnus'";
		$result_korko = mysql_query($query_korko) or die ("Kysely ei onnistu $query_korko <br>" . mysql_error());
		$korko_row = mysql_fetch_array($result_korko);
		
		if ($korko_row['korkosumma'] > 0) {
			$korkosumma = $korko_row['korkosumma'];
		}
		else {
			$korkosumma = 0;
		}
		
		// Aloitetaan kirjanpidon kirjaukset
		// Kassatili
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, summa_valuutassa, valkoodi, ltunnus, selite, kustp)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritussumma, $suoritussummaval, '$suoritus[valkoodi]', '$ltunnus','Manuaalisesti kohdistettu suoritus (osasuoritus) $suoritus[viesti]','$apukustp')";
		$result = mysql_query($query) or pupe_error($query);

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $lasku["vienti_kurssi"],2);

		// Myyntisaamiset
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myyntisaamiset_tilino]', -1 * $suoritussumma, -1 * $suoritussummaval, '$suoritus[valkoodi]', 'Manuaalisesti kohdistettu suoritus (osasuoritus) $suoritus[viesti]')";
		$result = mysql_query($query) or pupe_error($query);

		// Suoritetaan valuuttalaskua
		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$valuuttaero = round($suoritussummaval * $lasku["vienti_kurssi"], 2) - round($suoritussummaval * $suoritus["kurssi"],2);

			// Tuliko valuuttaeroa?
			if (abs($valuuttaero) >= 0.01) {
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
		            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myynninvaluuttaero_tilino]', $valuuttaero, 'Manuaalisesti kohdistettu suoritus (osasuoritus) $suoritus[viesti]')";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "	UPDATE lasku
						SET saldo_maksettu_valuutassa=saldo_maksettu_valuutassa+$suoritussummaval
						WHERE tunnus=$ltunnus
						AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// Jos tämän suorituksen jälkeen ei enää jää maksettavaa valuutassa
			if ($lasku["summa_valuutassa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}
		else {
			//jos tämän suorituksen jälkeen ei enää jää maksettavaa niin merkataan lasku maksetuksi
			if ($lasku["summa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}

		$query = "	UPDATE lasku
					SET viikorkoeur = '$korkosumma', saldo_maksettu=saldo_maksettu+$suoritussumma $lisa
					WHERE tunnus	= $ltunnus
					AND yhtio		= '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		//Merkataan suoritus käytetyksi ja yliviivataan sen tiliöinnit
		$query = "UPDATE suoritus SET kohdpvm=now(), summa=0 WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
		$query = "SELECT aputunnus, ltunnus FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti1 kateissa " . $suoritus["tunnus"]);
		}
		$tiliointi = mysql_fetch_array ($result);

		$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti2 kateissa " . $suoritus["tunnus"]);
		}

        $query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
        $result = mysql_query($query) or pupe_error($query);

        $query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
        $result = mysql_query($query) or pupe_error($query);
	}
	else {
		//*** Tässä käsitellään tavallinen suoritus ***
		$laskujen_summa = 0;

		if($laskutunnukset != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, 
						summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 
						0 AS alennus, 
						0 AS alennus_valuutassa, 
						tunnus, 
						vienti_kurssi, 
						tapvm, 
						valkoodi,
						summa as alkup_summa,
						summa_valuutassa as alkup_summa_valuutassa
						FROM lasku WHERE tunnus IN ($laskutunnukset)
						and yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != sizeof($lasku_tunnukset)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset)."'</font><br>";
				
				$query = "UNLOCK TABLES";
				$result = mysql_query($query) or pupe_error($query);
				exit;
			}

			while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+=$lasku["summa"];
				$laskujen_summa_valuutassa	+=$lasku["summa_valuutassa"];
			}
		}

		// Alennukset
		if($laskutunnuksetkale != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, 
						summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 
						kasumma AS alennus, 
						kasumma_valuutassa AS alennus_valuutassa, 
						tunnus, 
						vienti_kurssi, 
						tapvm, 
						valkoodi,
						summa as alkup_summa,
						summa_valuutassa as alkup_summa_valuutassa
						FROM lasku WHERE tunnus IN ($laskutunnuksetkale)
						AND yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != sizeof($lasku_tunnukset_kale)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset_kale)."'</font><br>";
				exit;
			}

		    while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+= $lasku["summa"] - $lasku["alennus"];
				$laskujen_summa_valuutassa	+= $lasku["summa_valuutassa"] - $lasku["alennus_valuutassa"];
			}
		}

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa_valuutassa,2);

			echo "<font class='message'>".t("Tilitapahtumalle jää pyöristyksen jälkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}
		else {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa,2);

			echo "<font class='message'>".t("Tilitapahtumalle jää pyöristyksen jälkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}

		//Jos heittoa ja kirjataan kassa-alennuksiin etsitään joku sopiva lasku (=iso summa)
		if($kaatosumma != 0 and $pyoristys_virhe_ok == 1) {
			echo "<font class='message'>".t("Kirjataan kassa-aleen")."</font> ";

			$query = "	SELECT tunnus, laskunro
						FROM lasku
						WHERE tunnus IN ($laskutunnukset,$laskutunnuksetkale)
						AND yhtio = '$kukarow[yhtio]'
						ORDER BY summa desc
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kaikki laskut katosivat")." (sys err)</font<br>";
				exit;
			}
			else {
				$kohdistuslasku = mysql_fetch_array($result);
			}
		}

		// Tiliöidään myyntisaamiset
		if (is_array($laskut)) {

			$kassaan = 0;
			$kassaan_valuutassa = 0;
			
			foreach ($laskut as $lasku) {

				// lasketaan korko
				$ltunnus			= $lasku["tunnus"];
				$maksupvm			= $suoritus["maksupvm"];
				$suoritussumma		= $suoritus["summa"];
				$suoritussummaval	= $suoritus["summa"];

				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

				$query_korko = "SELECT viikorkopros * $suoritussumma * (if(to_days('$maksupvm')-to_days(erpcm) > 0, to_days('$maksupvm')-to_days(erpcm), 0))/36500 korkosumma from lasku WHERE tunnus='$ltunnus'";
				$result_korko = mysql_query($query_korko) or die ("Kysely ei onnistu $query_korko <br>" . mysql_error());
				$korko_row = mysql_fetch_array($result_korko);
				
				if ($korko_row['korkosumma'] > 0) {
					$korkosumma = $korko_row['korkosumma'];
				}
				else {
					$korkosumma = 0;
				}

				//Kohdistammeko pyöristykset ym:t tähän?
			 	if($kaatosumma != 0 and $pyoristys_virhe_ok == 1 and $lasku["tunnus"] == $kohdistuslasku["tunnus"]) {
			 		echo "<font class='message'>".t("Sijoitin lisäkassa-alen laskulle").": $kohdistuslasku[laskunro]</font> ";

					if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
						$lasku["alennus_valuutassa"] = round($lasku["alennus_valuutassa"] - $kaatosumma,2);

						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus_valuutassa] $suoritus[valkoodi]</font> ";
					}
					else {
						$lasku["alennus"] = round($lasku["alennus"] - $kaatosumma,2);

						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus] $suoritus[valkoodi]</font> ";
					}

					$kaatosumma = 0;
			 	}

				// Tehdään valuuttakonversio kassa-alennukselle
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$lasku["alennus"] = round($lasku["alennus_valuutassa"] * $suoritus["kurssi"],2);
				}

				// Mahdollinen kassa-alennus
				if($lasku["alennus"] != 0) {
					// Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
					$totkasumma = 0;
					$totkasumma_valuutassa = 0;

					#Etsitään myynti-tiliöinnit
					
					// jos yhtiön toimipaikka löytyy, otetaan alvtilinumero tämän takaa jos se löytyy
					if ($lasku["yhtio_toimipaikka"] != '' and $yhtiorow["toim_alv"] != '') {
						$query = "	SELECT summa, vero, kustp, kohde, projekti, summa_valuutassa, valkoodi
									FROM tiliointi use index (tositerivit_index)
									WHERE ltunnus	= '$lasku[tunnus]'
									and yhtio 		= '$kukarow[yhtio]'
									and tapvm 		= '$lasku[tapvm]'
									and abs(summa) <> 0
									and tilino	   <> '$yhtiorow[myyntisaamiset]'
									and tilino	   <> '$yhtiorow[konsernimyyntisaamiset]'
									and tilino	   <> '$yhtiorow[toim_alv]'
									and tilino	   <> '$yhtiorow[varasto]'
									and tilino	   <> '$yhtiorow[varastonmuutos]'
									and tilino	   <> '$yhtiorow[pyoristys]'
									and tilino	   <> '$yhtiorow[myynninkassaale]'
									and tilino	   <> '$yhtiorow[factoringsaamiset]'
									and korjattu 	= ''";
						$yresult = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	SELECT summa, vero, kustp, kohde, projekti, summa_valuutassa, valkoodi
									FROM tiliointi use index (tositerivit_index)
									WHERE ltunnus	= '$lasku[tunnus]'
									and yhtio 		= '$kukarow[yhtio]'
									and tapvm 		= '$lasku[tapvm]'
									and abs(summa) <> 0
									and tilino	   <> '$yhtiorow[myyntisaamiset]'
									and tilino	   <> '$yhtiorow[konsernimyyntisaamiset]'
									and tilino	   <> '$yhtiorow[alv]'
									and tilino	   <> '$yhtiorow[varasto]'
									and tilino	   <> '$yhtiorow[varastonmuutos]'
									and tilino	   <> '$yhtiorow[pyoristys]'
									and tilino	   <> '$yhtiorow[myynninkassaale]'
									and tilino	   <> '$yhtiorow[factoringsaamiset]'
									and korjattu 	= ''";
						$yresult = mysql_query($query) or pupe_error($query);
					}

					if (mysql_num_rows($yresult) == 0) {
						echo "<font class='error'>".t("En löytänyt laskun myynnin vientejä! Alv varmaankin heittää")."</font> ";

						$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, vero)
									VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', '$lasku[alennus]', '$lasku[alennus_valuutassa]', '$lasku[valkoodi]', 'Manuaalisesti kohdistettu suoritus (alv ongelma) $suoritus[viesti]', '0')";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {

						$isa = 0;

						while ($tiliointirow = mysql_fetch_array($yresult)) {
							// Kuinka paljon on tämän viennin osuus
							$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) * -1 / $lasku["alkup_summa"] * $lasku["alennus"],2);
							$summa_valuutassa = round($tiliointirow['summa_valuutassa'] * (1+$tiliointirow['vero']/100) * -1 / $lasku["alkup_summa_valuutassa"] * $lasku["alennus_valuutassa"],2);
												
							if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
								//$alv:ssa on alennuksen alv:n maara
								$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
								$alv_valuutassa = round($summa_valuutassa - $summa_valuutassa / (1 + ($tiliointirow['vero'] / 100)),2);
								//$summa on alviton alennus
								$summa -= $alv;
								$summa_valuutassa -= $alv_valuutassa;
							}

							// Kuinka paljon olemme kumulatiivisesti tiliöineet
							$totkasumma += $summa + $alv;
							$totkasumma_valuutassa += $summa_valuutassa + $alv_valuutassa;

							$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, vero, kustp, kohde, projekti)
										VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', $summa, $summa_valuutassa, '$tiliointirow[valkoodi]', 'Manuaalisesti kohdistettu suoritus $suoritus[viesti]', '$tiliointirow[vero]', '$tiliointirow[kustp]', '$tiliointirow[kohde]', '$tiliointirow[projekti]')";
							$result = mysql_query($query) or pupe_error($query);
							$isa = mysql_insert_id ($link);

							if ($tiliointirow['vero'] != 0) {

								// jos yhtiön toimipaikka löytyy, otetaan alvtilinumero tämän takaa jos se löytyy 
								if ($lasku["yhtio_toimipaikka"] != '' and $yhtiorow["toim_alv"] != '') {
									$query = "	INSERT into tiliointi set
												yhtio 				= '$kukarow[yhtio]',
												ltunnus 			= '$lasku[tunnus]',
												tilino 				= '$yhtiorow[toim_alv]',
												tapvm 				= '$suoritus[maksupvm]',
												summa 				= $alv,
												summa_valuutassa	= $alv_valuutassa,
												valkoodi			= '$tiliointirow[valkoodi]',
												vero 				= '',
												selite 				= '$selite',
												lukko 				= '1',
												laatija 			= '$kukarow[kuka]',
												laadittu 			= now(),
												aputunnus 			= $isa";
									$xresult = mysql_query($query) or pupe_error($query);								
								}
								else {
									$query = "	INSERT into tiliointi set
												yhtio 				= '$kukarow[yhtio]',
												ltunnus 			= '$lasku[tunnus]',
												tilino 				= '$yhtiorow[alv]',
												tapvm 				= '$suoritus[maksupvm]',
												summa 				= $alv,
												summa_valuutassa	= $alv_valuutassa,
												valkoodi			= '$tiliointirow[valkoodi]',
												vero 				= '',
												selite 				= '$selite',
												lukko 				= '1',
												laatija 			= '$kukarow[kuka]',
												laadittu 			= now(),
												aputunnus 			= $isa";
									$xresult = mysql_query($query) or pupe_error($query);
								}
							}
						}

						//Hoidetaan mahdolliset pyöristykset
						$heitto = $totkasumma - $lasku["alennus"];
						$heitto_valuutassa = $totkasumma_valuutassa - $lasku["alennus_valuutassa"];

						if (abs($heitto) >= 0.01) {
							echo "<font class='message'>".t("Kassa-alvpyöristys")." $heitto</font> ";

							$query = "	UPDATE tiliointi SET 
										summa = summa - $totkasumma + $lasku[alennus],
										summa_valuutassa = summa_valuutassa - $totkasumma_valuutassa + $lasku[alennus]
										WHERE tunnus = '$isa' and yhtio = '$kukarow[yhtio]'";
							$xresult = mysql_query($query) or pupe_error($query);

							$isa = 0;
						}
					}
				}

				// Tehdään valuuttakonversio kassasuoritukselle
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$suoritettu_kassaan = round(($lasku["summa_valuutassa"] - $lasku["alennus_valuutassa"]) * $suoritus["kurssi"], 2);
					$suoritettu_kassaan_valuutassa = $lasku["summa_valuutassa"] - $lasku["alennus_valuutassa"];
				}
				else {
					$suoritettu_kassaan = $lasku["summa"] - $lasku["alennus"];
					$suoritettu_kassaan_valuutassa = $lasku["summa"] - $lasku["alennus"];
				}

				// Kassatili
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, summa_valuutassa, valkoodi, ltunnus, selite, kustp)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritettu_kassaan, $suoritettu_kassaan_valuutassa, '$lasku[valkoodi]', $lasku[tunnus], 'Manuaalisesti kohdistettu suoritus $suoritus[viesti]','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);

				// Lasketaan summasummarum paljonko ollaan tiliöity kassaan
				$kassaan += $suoritettu_kassaan;
				$kassaan_valuutassa += $suoritettu_kassaan_valuutassa;

				// Myyntisaamiset
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myyntisaamiset_tilino]', -1 * $lasku[summa], -1 * $lasku[summa_valuutassa], '$lasku[valkoodi]', 'Manuaalisesti kohdistettu suoritus $suoritus[viesti]')";
				$result = mysql_query($query) or pupe_error($query);

				// Tuliko valuuttaeroa?
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {

					$valero = round($lasku["summa"] - $suoritettu_kassaan - $lasku["alennus"], 2);
					
					if (abs($valero) >= 0.01) {
						$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
				            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myynninvaluuttaero_tilino]', $valero, 'Manuaalisesti kohdistettu suoritus $suoritus[viesti]')";
						$result = mysql_query($query) or pupe_error($query);
					}
				}

				$query = "	UPDATE lasku
							SET mapvm = '$suoritus[maksupvm]',
							viikorkoeur = '$korkosumma', 
							saldo_maksettu = 0, 
							saldo_maksettu_valuutassa = 0
							WHERE tunnus = $lasku[tunnus]
							AND yhtio	 = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "UPDATE suoritus SET kohdpvm=now(), summa=$kaatosumma WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
			$query = "SELECT aputunnus, ltunnus, summa, summa_valuutassa, valkoodi FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				die ("Tiliöinti1 kateissa " . $suoritus["tunnus"]);
			}
			$tiliointi = mysql_fetch_array ($result);

			$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				die ("Tiliöinti2 kateissa " . $suoritus["tunnus"]);
			}

            $query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
            $result = mysql_query($query) or pupe_error($query);

            $query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
            $result = mysql_query($query) or pupe_error($query);

			// Jääkö suoritukselle vielä saldoa
			$erotus = round($tiliointi["summa"] + $kassaan, 2);
			$erotus_valuutassa = round($tiliointi["summa_valuutassa"] + $kassaan_valuutassa, 2);

			if ($erotus != 0) {
				//Myyntisaamiset
				$query = "INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,summa_valuutassa,valkoodi,selite) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[myyntisaamiset_tilino]', $erotus, $erotus_valuutassa, '$tiliointi[valkoodi]', 'Käsin syötetty suoritus')";
				$result = mysql_query($query) or pupe_error($query);
				$ttunnus = mysql_insert_id($link);

				//Kassatili
				$query = "INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,summa_valuutassa,valkoodi,selite,aputunnus,lukko,kustp) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[kassatilino]',$erotus * -1, $erotus_valuutassa * -1, '$tiliointi[valkoodi]', 'Käsin syötetty suoritus',$ttunnus,'1','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);

				// Päivitetään osoitin
				$query = "UPDATE suoritus SET ltunnus = '$ttunnus', kohdpvm = '0000-00-00' WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}

	echo "<br><font class='message'>".t("Kohdistus onnistui").".</font><br>";

	$query = "UNLOCK TABLES";
	$result = mysql_query($query) or pupe_error($query);

	$tila			= "suorituksenvalinta";
	$asiakas_tunnus = $suoritus["asiakas_tunnus"];
}

if ($tila == 'suorituksenvalinta') {
	$query = "	SELECT concat(summa,if(valkoodi!='$yhtiorow[valkoodi]', valkoodi,'')) summa, viite, viesti,tilino,maksupvm,kirjpvm,nimi_maksaja, asiakas_tunnus, tunnus
				FROM suoritus
				WHERE yhtio ='$kukarow[yhtio]' AND kohdpvm='0000-00-00' and asiakas_tunnus='$asiakas_tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 1) {
		echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen (suorituksen valinta)")."</font><hr>";
		echo "<table><tr><th>Valitse</th>";

		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		echo "</tr>";

		echo "<form action = '$PHP_SELF?tila=kohdistaminen' method = 'post'>";
		echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas_tunnus'>";
		echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";

		$r=1;
		while ($suoritus=mysql_fetch_array ($result)) {			
			
			if ($yhtiorow['tilikausi_alku'] <= $suoritus['kirjpvm']) {			
				echo "<tr class='aktiivi'><td>";
				echo "<input type='radio' name='suoritus_tunnus' value='$suoritus[tunnus]' ";

				if (mysql_num_rows($result)==$r) echo "checked";
				$r++;

				echo "></td>";
			}
			else {
				echo "<tr class='aktiivi'><td><font class='error'>".t("Tilikausi lukittu")."</font></td>";
			}
			

			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				echo "<td>$suoritus[$i]</td>";
			}

			echo "</tr>";

		}

		echo "</table>";
		echo "<br><input type='submit' value='".t("Kohdista")."'></form>";
	}
	else {
		if (mysql_num_rows($result) == 1) {
			$tila='kohdistaminen';
			$suoritus=mysql_fetch_array ($result);
			$suoritus_tunnus=$suoritus['tunnus'];
		}
		else {
			echo "<font class='message'>".t("Asiakkaalle ei ole muita suorituksia")."</font><br>";
			$tila='';
		}
	}
}

if ($tila == 'kohdistaminen') {

	echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen (laskujen valinta)")."</font><hr>";

	$query = "	SELECT suoritus.summa,
				suoritus.valkoodi valkoodi,
				concat(viite, viesti) tieto,
				suoritus.tilino,
				maksupvm,
				kirjpvm,
				nimi_maksaja,
				asiakas_tunnus,
				suoritus.tunnus,
				tiliointi.tilino ttilino,
				asiakas.nimi,
				asiakas.ytunnus,
				yriti.oletus_selvittelytili,
				asiakas.konserniyhtio
				FROM suoritus
				JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio and tiliointi.tunnus = suoritus.ltunnus)
				LEFT JOIN asiakas ON (asiakas.yhtio = suoritus.yhtio and asiakas.tunnus = suoritus.asiakas_tunnus)
				LEFT JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
				WHERE suoritus.yhtio = '$kukarow[yhtio]'
				and suoritus.tunnus = '$suoritus_tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$suoritus = mysql_fetch_array($result);
	
	$asiakas_tunnus 	= $suoritus['asiakas_tunnus'];
	$suoritus_summa		= $suoritus['summa'];
	$suoritus_tunnus 	= $suoritus['tunnus'];
	$suoritus_ttilino 	= $suoritus['ttilino'];
	$valkoodi 			= $suoritus['valkoodi'];

	//Näytetään suorituksen tiedot!
	echo "<font class='message'>Asiakas: $suoritus[nimi] ($suoritus[ytunnus])</font><br>";

	echo "<table>";

	echo "<tr>";
	echo "<th>nimi</th>";
	echo "<th>summa</th>";
	echo "<th>valkoodi</th>";
	echo "<th>tieto</th>";
	echo "<th>tilino</th>";
	echo "<th>maksupvm</th>";
	echo "<th>kirjpvm</th>";
	echo "<th>suorituksen saamisettili</th>";
	echo "<th></th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>$suoritus[nimi_maksaja]</td>";
	echo "<td align='right'>$suoritus[summa]</td>";
	echo "<td>$suoritus[valkoodi]</td>";
	echo "<td>$suoritus[tieto]</td>";
	echo "<td>$suoritus[tilino]</td>";
	echo "<td>$suoritus[maksupvm]</td>";
	echo "<td>$suoritus[kirjpvm]</td>";

	$sel1 = '';
	$sel2 = '';
	$sel3 = '';
	$sel4 = '';
	$sel5 = '';

	if ($suoritus['ttilino'] == $yhtiorow["myyntisaamiset"]) {
		$sel1 = "selected";
	}
	if ($suoritus['ttilino'] == $yhtiorow['factoringsaamiset']) {
		$sel2 = "selected";
	}
	if ($suoritus['ttilino'] == $yhtiorow["selvittelytili"]) {
		$sel3 = "selected";
	}
	if ($suoritus['ttilino'] == $suoritus["oletus_selvittelytili"]) {
		$sel4 = "selected";
	}
	if ($suoritus['ttilino'] == $yhtiorow["konsernimyyntisaamiset"]) {
		$sel5 = "selected";
	}

	echo "<form action = '$PHP_SELF' method = 'post'>";
	echo "<input type='hidden' name='tunnus' value='$suoritus[tunnus]'>";
	echo "<input type='hidden' name='tila' value='vaihdasuorituksentili'>";
	echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas_tunnus'>";
	echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";
	echo "<input type='hidden' name='suoritus_tunnus' value='$suoritus_tunnus'>";

	echo "<td><select name='vastatili' onchange='submit();'>";
	echo "<option value='$yhtiorow[myyntisaamiset]' $sel1>"		.t("Myyntisaamiset").		" ($yhtiorow[myyntisaamiset])</option>";
	echo "<option value='$yhtiorow[factoringsaamiset]' $sel2>"	.t("Factoringsaamiset").	" ($yhtiorow[factoringsaamiset])</option>";
//	echo "<option value='$yhtiorow[selvittelytili]' $sel3>"		.t("Selvittelytili").		" ($yhtiorow[selvittelytili])</option>";

	if ($suoritus["oletus_selvittelytili"] != "") {
		echo "<option value='$suoritus[oletus_selvittelytili]' $sel4>".t("Pankkitilin selvittelytili")." ($suoritus[oletus_selvittelytili])</option>";
	}
	if ($suoritus['konserniyhtio'] != "") {
		echo "<option value='$yhtiorow[konsernimyyntisaamiset]' $sel5>".t("Konsernimyyntisaamiset")." ($yhtiorow[konsernimyyntisaamiset])</option>";
	}
	echo "</select></td>";
	echo "<td>";
	if ($kukarow["taso"] == 3) {
		echo "<input type='checkbox' name='vaihdasuorituksentiliointitunnuksia'>";
	}
	else {
		echo "<input type='hidden' name='vaihdasuorituksentiliointitunnuksia' value=''>";
	}
	echo "</td>";
	echo "</form>\n\n";

	echo "</tr>";

	echo "</table>";

	$pyocheck='';
	$osacheck='';
	if ($osasuoritus == '1') $osacheck = 'checked';
	if ($pyoristys_virhe_ok == '1') $pyocheck = 'checked';

	echo "<form method = 'post' name='summat'>";
	echo "<table>";
	echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='0.0' readonly></td>";
	echo "<th>".t("Erotus")."</th><td><input type='text' name='jaljella' value='$suoritus_summa' readonly></td></tr>";
	echo "</table>";
	echo "</form>";

	//Näytetään laskut!
	$kentat = 'summa, kasumma, laskunro, erpcm, kapvm, viite, ytunnus';
	$kentankoko = array(10,10,15,10,10,15);
	$array = split(",", $kentat);
	$count = count($array);
	$lisa='';
	$ulisa='';

	for ($i=0; $i<=$count; $i++) {
	  // tarkastetaan onko hakukentässä jotakin
	  if (strlen($haku[$i]) > 0) {
	    $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
	    $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
	  }
	}
	if (strlen($ojarj) > 0) {
	  $jarjestys = $array[$ojarj];
	}
	else {
	  $jarjestys = 'erpcm';
	}

	// Etsitään ytunnuksella
	$query  = "SELECT ytunnus FROM asiakas WHERE tunnus = '$asiakas_tunnus' AND ytunnus != '' AND yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if ($ytunnusrow=mysql_fetch_array($result)) {
		$ytunnus = $ytunnusrow[0];
	}
	else {
		echo "<font class='head'>".t("Asiakkaalta ei löydy y-tunnusta")." $query</font>";
		exit;
	}

	if (strtoupper($suoritus['valkoodi']) != strtoupper($yhtiorow['valkoodi'])) {
		$query = "SELECT summa_valuutassa-saldo_maksettu_valuutassa summa, kasumma_valuutassa kasumma, ";
	}
	else {
		$query = "SELECT summa-saldo_maksettu summa, kasumma, ";
	}

	$query .= " laskunro, erpcm, kapvm, viite, ytunnus, lasku.tunnus
				FROM lasku USE INDEX (yhtio_tila_mapvm)
	           	WHERE yhtio  = '$kukarow[yhtio]'
				and tila     = 'U'
				and mapvm    = '0000-00-00'
				and valkoodi = '$valkoodi'
				and (ytunnus = '$ytunnus' or nimi = '$asiakas_nimi' or liitostunnus = '$asiakas_tunnus')
				$lisa
				ORDER BY $jarjestys";
	$result = mysql_query($query) or pupe_error($query);

	echo "<form action = '$PHP_SELF?tila=$tila&suoritus_tunnus=$suoritus_tunnus&asiakas_tunnus=$asiakas_tunnus&asiakas_nimi=$asiakas_nimi' method = 'post'>";
	echo "<table><tr><th colspan='2'></th>";

	for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th><a href='$PHP_SELF?suoritus_tunnus=$suoritus_tunnus&asiakas_tunnus=$asiakas_tunnus&asiakas_nimi=$asiakas_nimi&tila=$tila&ojarj=".$i.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
	}

	echo "<th></th></tr>";
	echo "<tr><th>L</th><th>K</th>";

	for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
	  echo "<td><input type='text' size='$kentankoko[$i]' name='haku[$i]' value='$haku[$i]'></td>";
	}

	echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";
	echo"</form>";
	
	echo "<form action = '$PHP_SELF?tila=tee_kohdistus' method = 'post' onSubmit='return validate(this)'>";
	$laskucount=0;

	if ($asiakas_nimi != '') echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";

	while ($maksurow = mysql_fetch_array ($result)) {

	  $query = "SELECT count(*) maara from tiliointi where tiliointi.yhtio = '$kukarow[yhtio]' and tiliointi.ltunnus = '$maksurow[tunnus]' and tilino = '$suoritus_ttilino'";
	  $cresult = mysql_query($query) or pupe_error($query);
	  $maararow = mysql_fetch_array ($cresult);

		if ($maararow['maara'] > 0) {
		  	$laskucount++;
			$lasku_tunnus = $maksurow['tunnus'];
			$bruttokale = $maksurow['summa']-$maksurow['kasumma'];

			echo "<tr class='aktiivi'><th>";
			echo "<input type='checkbox' name='lasku_tunnukset[]' value='$lasku_tunnus' onclick='javascript:paivita1(this)'>";
			echo "<input type='hidden' name='lasku_summa' value='$maksurow[summa]'>";
			echo "</th><th>";
			echo "<input type='checkbox' name='lasku_tunnukset_kale[]' value='$lasku_tunnus' onclick='javascript:paivita2(this)'>";
			echo "<input type='hidden' name='lasku_kasumma' value='$bruttokale'>";
			echo "</th>";
			$errormessage = "";
		}
		else {
			echo "<tr><th></th><th></th>";
			$errormessage = "<font class='message'>".t('Väärä saamisettili')." ($suoritus_ttilino)</font>";
		}

		echo "<td align='right'>$maksurow[summa]</td>";
		
		if ($maksurow["kasumma"] != 0) {
			echo "<td align='right'>$maksurow[kasumma]</td>";
		}
		else {
			echo "<td align='right'></td>";
		}
		
		echo "<td><a href='../muutosite.php?tee=E&tunnus=$maksurow[tunnus]'>$maksurow[laskunro]</a></td>";
		echo "<td>".tv1dateconv($maksurow["erpcm"])."</td>";
		echo "<td>".tv1dateconv($maksurow["kapvm"])."</td>";
		echo "<td>$maksurow[viite]</td>";
		echo "<td>$maksurow[ytunnus]</td>";
		echo "<th></th>";
		echo "<td class='back'>$errormessage</td>";
		echo "</tr>\n";
	}

	echo "<input type='hidden' name='suoritus_tunnus' value='$suoritus_tunnus'>";
	echo "</th></tr>";
	echo "<tr><th colspan='10'> ".t("L = lasku ilman kassa-alennusta K = lasku kassa-alennuksella")."</th></tr>";
	echo "</table>";
	echo "<table>";
	echo "<tr><th>".t("Kirjaa erotus kassa-aleen")."</th><td><input type='checkbox' name='pyoristys_virhe_ok' value='1' $pyocheck></td>";
	echo "<th>".t("Osasuorita lasku")."</th><td><input type='checkbox' name='osasuoritus' value='1' $osacheck onclick='javascript:osasuo(this)'></td></tr>";
	echo "</table>";
	if ($yhtiorow['tilikausi_alku'] <= $suoritus['kirjpvm']) {
		echo "<br><input type='submit' value='".t("Kohdista")."'>";
	}	
	else {
		echo "<br><font class='error'>".t("Tilikausi lukittu")."</font>";
	}
	echo "</form>\n";

	echo "<script language='JavaScript'><!--
		function paivita1(checkboxi) {";

	if ($laskucount==1)
	     echo "
			if(checkboxi==document.forms[3].elements['lasku_tunnukset[]']) {
	       		document.forms[3].elements['lasku_tunnukset_kale[]'].checked=false;
	    	}";
	else {
		echo "
			for(i=0;i<document.forms[3].elements['lasku_tunnukset[]'].length;i++) {
	      		if(checkboxi==document.forms[3].elements['lasku_tunnukset[]'][i]) {
	         		document.forms[3].elements['lasku_tunnukset_kale[]'][i].checked=false;
	      		}
	    	}";
	}
	echo "
	  		paivitaSumma();
		}

		function paivita2(checkboxi) {";

	if ($laskucount==1) {
	     echo "
			if(checkboxi==document.forms[3].elements['lasku_tunnukset_kale[]']) {
	       		document.forms[3].elements['lasku_tunnukset[]'].checked=false;
	    	}";
	}
	else {
		echo "
			for(i=0;i<document.forms[3].elements['lasku_tunnukset_kale[]'].length;i++) {
	      		if(checkboxi==document.forms[3].elements['lasku_tunnukset_kale[]'][i]) {
	        		document.forms[3].elements['lasku_tunnukset[]'][i].checked=false;
	      		}
	    	}";
	}
	echo "	paivitaSumma();
		}

		function paivitaSumma() {
	  		var i;
	  		var summa=0.0;";

	if ($laskucount == 1) {
	     echo "
			if(document.forms[3].elements['lasku_tunnukset[]'].checked) {
	        	summa+=1.0*document.forms[3].lasku_summa.value;
	      	}
	      	if(document.forms[3].elements['lasku_tunnukset_kale[]'].checked) {
	        		summa+=1.0*document.forms[3].lasku_kasumma.value;
	      	}";
	}
	else {
		echo "
			for(i=0;i<document.forms[3].elements['lasku_tunnukset[]'].length;i++) {
	      		if(document.forms[3].elements['lasku_tunnukset[]'][i].checked) {
	        		summa+=1.0*document.forms[3].lasku_summa[i].value;
	      		}
	    	}
	    	for(i=0;i<document.forms[3].elements['lasku_tunnukset_kale[]'].length;i++) {
	      		if(document.forms[3].elements['lasku_tunnukset_kale[]'][i].checked) {
	        		summa+=1.0*document.forms[3].lasku_kasumma[i].value;
	      		}
	    	}";
	}

	echo "	document.forms[1].summa.value=Math.round(summa*100)/100;
			
			if($suoritus_summa < 0 && summa < 0) {
	  			document.forms[1].jaljella.value=Math.round((summa-($suoritus_summa))*100)/100;
			}
			else {
				document.forms[1].jaljella.value=Math.round(($suoritus_summa-summa)*100)/100;				
			}
		}

		function osasuo(form) {
			if(document.forms[3].osasuoritus.checked) {
	   			if(document.forms[1].jaljella.value > 0) {
	   				alert('".t("Et voi osasuorittaa, jos jäjellä on positiivinen summa")."');
					document.forms[3].osasuoritus.checked = false;
	   				return false;
	   			}
			}
		}

		function validate(form) {
			var maara=0;
			var kmaara=0;";

	if ($laskucount>1) {
		echo "
			for(i=0;i<document.forms[3].elements['lasku_tunnukset[]'].length;i++) {
				if(document.forms[3].elements['lasku_tunnukset[]'][i].checked) {
	        		maara+=1.0;
				}
	    	}
	    	for(i=0;i<document.forms[3].elements['lasku_tunnukset_kale[]'].length;i++) {
	      		if(document.forms[3].elements['lasku_tunnukset_kale[]'][i].checked) {
	        		kmaara+=1.0;
	      		}
	    	}
			
			maara = maara + kmaara;";
	}
	if ($laskucount==1) {
		echo "
			if(document.forms[3].elements['lasku_tunnukset[]'].checked) {
	        	maara=1;
	    	}
	    	if(document.forms[3].elements['lasku_tunnukset_kale[]'].checked) {
	       		kmaara=1;
	    	}
	
			maara = maara + kmaara;";
	}

	echo "	if(document.forms[3].osasuoritus.checked) {
				if (kmaara!=0) {
					alert ('".t("Jos osasuoritus, ei voi valita kassa-alennusta")."');
					return false;
				}
				if (maara!=1) {
					alert ('".t("Jos osasuoritus, pitää valita vain ja ainoastaan yksi lasku")."! ' + maara + ' valittu');
					return false;
				}
			}

			if ((maara==0) == true) {
				alert('".t("Jotta voit kohdistaa, on ainakin yksi lasku valittava. Jos mitään kohdistettavaa ei löydy, klikkaa menusta Manuaalikohdistus päästäksesi takaisin alkuun")."');
				return false;
			}

			var jaljella=document.forms[1].jaljella.value;
			var kokolasku=document.forms[1].summa.value
			var suoritus_summa=$suoritus_summa;

			if(suoritus_summa==0) {
				return true;
			}

			if (document.forms[3].osasuoritus.checked == false) {
				var alennusprosentti = Math.round(100*(1-(suoritus_summa/kokolasku)));

				if(jaljella < 0 && document.forms[3].pyoristys_virhe_ok.checked == true) {
					if(confirm('Haluatko varmasti antaa '+alennusprosentti+'% alennuksen?\\nAlennus: '+(-1.0*jaljella)+' $yhtiorow[valkoodi] \\n\\nHUOM: Alennus kirjataan kassa-alennukseen!')==1) {
						return true;
					} 
					else {
						return false;
					}
				}
				else if(jaljella < 0) {
					if(confirm('Haluatko varmasti antaa '+alennusprosentti+'% alennuksen?\\nAlennus: '+(-1.0*jaljella)+' $yhtiorow[valkoodi] \\n\\nHUOM: Alennus vähentää myyntisaatavia!')==1) {
						return true;
					} 
					else {
						return false;
					}
				} 
			}
			return true;
		}
	-->
	</script>";
}

if ($tila == '') {
	echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen")."</font><hr>";

	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tila' value=''>";

	$query = "	SELECT distinct suoritus.tilino,
				nimi,
				yriti.valkoodi
				FROM suoritus use index (yhtio_kohdpvm)
				JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
				WHERE suoritus.yhtio = '$kukarow[yhtio]'
				AND kohdpvm = '0000-00-00'
				ORDER BY nimi";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("Näytä vain suoritukset tililtä")."</th>";
	echo "<td><select name='tilino' onchange='submit()'>";
	echo "<option value=''>".t("Kaikki")."</option>\n";

	while ($row = mysql_fetch_array($result)) {
		$sel = '';
		if ($tilino == $row[0]) $sel = 'selected';
		echo "<option value='$row[0]' $sel>$row[nimi] ".tilinumero_print($row['tilino'])." $row[valkoodi]</option>\n";
	}
	echo "</select></td></tr>";

	$query = "	SELECT distinct valkoodi
				FROM suoritus use index (yhtio_kohdpvm)
				WHERE yhtio = '$kukarow[yhtio]'
				AND kohdpvm = '0000-00-00'
				ORDER BY valkoodi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Näytä vain suoritukset valuutassa")."</th>";
	echo "<td><select name='valuutta' onchange='submit()'>";
	echo "<option value=''>".t("Kaikki")."</option>\n";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel = "";
		if ($valuutta == $vrow[0]) $sel = "selected";
		echo "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
	}

	echo "</select></td></tr>";

	$query = "	SELECT distinct asiakas.maa
				FROM suoritus use index (yhtio_kohdpvm)
				JOIN asiakas ON asiakas.yhtio=suoritus.yhtio and suoritus.asiakas_tunnus=asiakas.tunnus
				WHERE suoritus.asiakas_tunnus<>0
				AND suoritus.yhtio = '$kukarow[yhtio]'
				AND suoritus.kohdpvm = '0000-00-00'
				AND suoritus.ltunnus != 0
				ORDER BY asiakas.maa";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Näytä vain suoritukset maasta")."</th>";
	echo "<td><select name='maa' onchange='submit()'>";
	echo "<option value='' >".t("Kaikki")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel = "";
		if ($maa == $vrow[0]) $sel = "selected";
		echo "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[0])."</option>";
	}

	echo "</select></td><tr>";
	echo "</table>";
	echo "</form><br>";

	$lisa = "";

	if ($tilino != "") {
		$lisa .= " and suoritus.tilino = '$tilino' ";
	}

	if ($valuutta != "") {
		$lisa .= " and suoritus.valkoodi = '$valuutta' ";
	}

	if ($maa != "") {
		$lisa .= " and asiakas.maa = '$maa' ";
	}

	$query = "	SELECT suoritus.asiakas_tunnus tunnus, 
				min(asiakas.ytunnus) ytunnus,
				min(asiakas.nimi) nimi, 
				min(asiakas.nimitark) nimitark,					
				min(asiakas.osoite) osoite,
				min(asiakas.postitp) postitp,
				min(asiakas.toim_nimi) toim_nimi, 
				min(asiakas.toim_nimitark) toim_nimitark,
				min(asiakas.toim_osoite) toim_osoite,
				min(asiakas.toim_postitp) toim_postitp,
				count(suoritus.asiakas_tunnus) maara, 
				sum(if(suoritus.viite>0, 1,0)) viitteita
				FROM suoritus use index (yhtio_kohdpvm)
				JOIN asiakas ON asiakas.yhtio=suoritus.yhtio and suoritus.asiakas_tunnus=asiakas.tunnus
				WHERE suoritus.asiakas_tunnus<>0
				AND suoritus.yhtio = '$kukarow[yhtio]'
				AND suoritus.kohdpvm = '0000-00-00'
				AND suoritus.ltunnus!=0
				$lisa
				GROUP BY suoritus.asiakas_tunnus
				ORDER BY asiakas.nimi";
	$result = mysql_query($query) or pupe_error($query);

	echo "	<table>
			<tr>
			<th>".t("Ytunnus")."</th>
			<th>".t("Nimi")."</th>
			<th>".t("Postitp")."</th>
			<th>".t("Suorituksia")."</th>
			<th>".t("Viitteellisiä")."<br>".t("suorituksia")."</th>
			<th>".t("Avoimia")."<br>".t("laskuja")."
			</tr>";

	while ($asiakas = mysql_fetch_array($result)) {

		// Onko asiakkaalla avoimia laskuja???
		$query = "	SELECT COUNT(*) maara
					FROM lasku USE INDEX (yhtio_tila_mapvm)
					WHERE yhtio ='$kukarow[yhtio]'
					and mapvm = '0000-00-00'
					and tila = 'U'
					and (ytunnus = '$asiakas[ytunnus]' or nimi = '$asiakas[nimi]' or liitostunnus = '$asiakas[tunnus]')";
		$lresult = mysql_query($query) or pupe_error($query);
		$lasku = mysql_fetch_array ($lresult);

		echo "<tr class='aktiivi'>
				<td valign='top'>$asiakas[ytunnus]</td>
				<td valign='top'>$asiakas[nimi] $asiakas[nimitark]";
				
		if ($asiakas["nimi"] != $asiakas["toim_nimi"]) {
			echo "<br>$asiakas[toim_nimi] $asiakas[toim_nimitark]";
		}
		
		echo "	</td>";
				
		echo "	<td valign='top'>$asiakas[postitp]";
		
		if ($asiakas["postitp"] != $asiakas["toim_postitp"]) {
			echo "<br>$asiakas[toim_postitp]</td>";
		}						
		echo "	<td valign='top'>$asiakas[maara]</td>
				<td valign='top'>$asiakas[viitteita]</td>
				<td valign='top'>$lasku[maara]</td>";

		echo "<form action='$PHP_SELF' method='POST'>";
		echo "<input type='hidden' name='tila' value='suorituksenvalinta'>";
		echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas[tunnus]'>";
		echo "<input type='hidden' name='asiakas_nimi' value='$asiakas[nimi]'>";
		echo "<td class='back' valign='top'><input type='submit' value='".t("Valitse")."'></td>";
		echo "</form>";

		echo "</tr>";
	}

	echo "</table>";
}

require("../inc/footer.inc");

?>