<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	
	require("../inc/parametrit.inc");
	require("tulosta_vuosisopimusasiakkaat.inc");

	echo "<font class='head'>Vuosisopimusasiakkaat</font><hr>";

	
	

	if ($ytunnus != "" and $asiakasid == "") {
			
		if($muutparametrit == '')
			$muutparametrit = "$komento#$raja#$emailok#$alkupp#$alkukk#$alkuvv#$loppupp#$loppukk#$loppuvv#";
		
		require ("../inc/asiakashaku.inc");
		
		//jos tulee yksi asiakas tieto
		if ($monta != 1) {
			$tee = '';
		}
		
	}
	
	if(isset($muutparametrit) and $tee != '' and $asiakasid != '')
			list($komento,$raja,$emailok,$alkupp,$alkukk,$alkuvv,$loppupp,$loppukk,$loppuvv) = explode('#', $muutparametrit);
	
	
	
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
		
		$edalkupvm  = date("Y-m-d", mktime(0, 0, 0, $alkukk,  $alkupp,  $alkuvv - 1));
		$edloppupvm = date("Y-m-d", mktime(0, 0, 0, $loppukk, $loppupp, $loppuvv - 1));

		//valittu asiakas
		if ($ytunnus != '' and $asiakasid != "") {
			$asnum = $ytunnus;
			echo "vain asiakas ytunnus: $ytunnus...<br> ";
			$aswhere = " and liitostunnus='$asiakasid' ";
		}
		else {
			$aswhere = "";
		}

		flush();
		
				
		$query = "	SELECT liitostunnus, sum(arvo) arvo
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					WHERE yhtio = '$kukarow[yhtio]' and
					tapvm >= '$alkuvv-$alkukk-$alkupp' and
					tapvm <= '$loppuvv-$loppukk-$loppupp' and
					tila = 'L' and
					alatila = 'X' $aswhere
					GROUP BY liitostunnus
					HAVING arvo > $raja
					ORDER BY liitostunnus";
		$result = mysql_query($query) or pupe_error($query);
		
	
		

		// laitetaan asikasnumerot mysql muotoon
		$asiakkaat = "";

		while ($row = mysql_fetch_array($result)) {
			$asiakkaat .= "'$row[liitostunnus]',";
		}

		$asiakkaat = substr($asiakkaat,0,-1); // vika pilkku pois

		echo "löytyi ".mysql_num_rows($result)." asiakasta.<br>";

		if (mysql_num_rows($result)==0) {
			echo "Ei voida jatkaa.</font>";
			exit;
		}

		echo "Haetaan myyntitiedot... ";

						
		$query = "SELECT lasku.liitostunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, osasto, try, lasku.ytunnus,
				sum(if(tapvm>='$alkuvv-$alkukk-$alkupp'   and tapvm<='$loppuvv-$loppukk-$loppupp',   tilausrivi.rivihinta, 0)) va,
				sum(if(tapvm>='$edalkupvm'                and tapvm<='$edloppupvm', tilausrivi.rivihinta, 0)) ed,
				sum(if(tapvm>='$alkuvv-$alkukk-$alkupp'   and tapvm<='$loppuvv-$loppukk-$loppupp',   tilausrivi.kpl, 0)) kplva,
				sum(if(tapvm>='$edalkupvm'                and tapvm<='$edloppupvm', tilausrivi.kpl, 0)) kpled
				FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
				JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.try > 0)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.liitostunnus in ($asiakkaat)
				and lasku.tapvm >= '$edalkupvm'
				and lasku.tila = 'L'
				and lasku.alatila = 'X'
				group by liitostunnus, osasto, try
				having va > 0 or ed > 0 or kplva > 0 or kpled > 0
				order by liitostunnus, osasto, try";
		$result = mysql_query($query) or pupe_error($query);

		echo "noin.<br><br>";

		$edasiakas = "";
		$edemail   = "";
		$sumkpled  = 0;
		$sumkplva  = 0;
		$sumed     = 0;
		$sumva     = 0;
		$sumindexi = 0;
		$laskuri   = 0;

		while ($row = mysql_fetch_array($result)) {
			
			// asiakas vaihtui!
			if ($edasiakas != $row["liitostunnus"]) {

				// jos ei olla ekalla rundilla
				if ($edasiakas != '') {

					// kirjotetaan yhteensärivi ja hoidetaan file eteenpäin
					loppu($firstpage);

					//tehdään uusi PDF
					unset($pdf);
					$pdf = new pdffile;
					$pdf->set_default('margin-top', 	0);
					$pdf->set_default('margin-bottom', 	0);
					$pdf->set_default('margin-left', 	0);
					$pdf->set_default('margin-right', 	0);

					// defaultteja layouttiin
					$kala = 575;
					$lask = 1;
					$sivu = 1;
				}

				$query = "SELECT email, ytunnus, asiakasnro from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$row[liitostunnus]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				// edellinen ytunnus ja email talteen
				$edasiakas = $row["liitostunnus"];
				$edemail   = $asrow["email"];				
				$edasiakasno = $asrow["asiakasno"];
				
				// uus pdf header
				$firstpage = alku();
			}

			// tehdään rivi
			rivi($firstpage);
		}
		
		// kirjotetaan vielä vika sivu ulos....
		loppu($firstpage);

		echo "<br>Kaikki valmista.</font>";

	} // end tee == tulosta

	if ($tee == '') {
		
		if (!isset($alkupp))  $alkupp  = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkukk))  $alkukk  = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
		if (!isset($alkuvv))  $alkuvv  = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));

		if (!isset($loppupp)) $loppupp = date("d");
		if (!isset($loppukk)) $loppukk = date("m");
		if (!isset($loppuyy)) $loppuvv = date("Y");

	

		echo "<font class='message'>Asiakkaille, joilla on sähköposti lähetetään viesti automaattisesti.<br>";
		echo "Muut ostoseurannat tulostetaan valitsemaasi tulostimeen.</font><br><br>";

		echo "<form method='post' action='$PHP_SELF'>";
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

		while ($kirow=mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}
		
		echo "</select></td></tr>";

		echo "<tr><th>Syötä ostoraja:</th>";
		echo "<td><input type='text' name='raja' value='10000' size='10'> $yhtiorow[valkoodi] valitulla ajanjaksolla</td></tr>";
		echo "<tr><th>Älä lähetä sähköposteja:</th>";
		echo "<td><input type='checkbox' name='emailok'> vain sähköpostittomat asiakkaat</td></tr>";
		echo "<tr><th>Asiakasnumero:</th>";
		echo "<td><input type='text' name='ytunnus' size='10'> aja vain tämä asiakas (tyhjä=kaikki)</td></tr>";
		echo "<tr><th>Alku päivämäärä:</th>";
		echo "<td>
		<input type='text' name='alkupp' value='$alkupp' size='10'>
		<input type='text' name='alkukk' value='$alkukk' size='10'>
		<input type='text' name='alkuvv' value='$alkuvv' size='10'> pp kk vvvv</td></tr>";
		echo "<tr><th>Loppu päivämäärä:</th>";
		echo "<td>
		<input type='text' name='loppupp' value='$loppupp' size='10'>
		<input type='text' name='loppukk' value='$loppukk' size='10'>
		<input type='text' name='loppuvv' value='$loppuvv' size='10'> pp kk vvvv</td></tr>";
		

		echo "</table>";

		echo "<br><input type='submit' value='Tulosta'></form>";
		
	}

	require ("../inc/footer.inc");

?>