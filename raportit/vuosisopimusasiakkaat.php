<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require("../inc/parametrit.inc");
	require("tulosta_vuosisopimusasiakkaat.inc");

	echo "<font class='head'>Vuosisopimusasiakkaat</font><hr>";

	if ($tee == "tulosta" and $komento == "" and $asnum == "") {
		echo "<font class='error'>VALITSE TULOSTIN!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta" and $raja == "") {
		echo "<font class='error'>RAJA PUUTTUU!!!</font><br><br>";
		$tee = "";
	}

	if ($tee == "tulosta") {

		// haetaan aluksi sopivat asiakkaat
		// viimeisen 12 kuukauden myynti pitää olla yli $rajan

		echo "<font class='message'>Haetaan sopivia asiakkaita (myynti yli $raja)... ";

		if ($asnum != '') {
			echo "vain asiakas $asnum... ";
			$aswhere = " and ytunnus='$asnum' ";
		}
		else {
			$aswhere = "";
		}

		flush();

		$query = "	SELECT ytunnus, sum(arvo) arvo
					FROM lasku
					WHERE lasku.yhtio='$kukarow[yhtio]' and
					laskutettu >= date_sub(now(), interval 12 month) and
					tila='L' and
					alatila='X' $aswhere
					GROUP BY ytunnus
					HAVING arvo > $raja
					ORDER BY ytunnus";

		$result = mysql_query($query) or pupe_error($query);

		// laitetaan asikasnumerot mysql muotoon
		$asiakkaat = "";

		while ($row = mysql_fetch_array($result)) {
			$asiakkaat .= "'$row[ytunnus]',";
		}

		$asiakkaat = substr($asiakkaat,0,-1); // vika pilkku pois

		echo "löytyi ".mysql_num_rows($result)." asiakasta.<br>";

		if (mysql_num_rows($result)==0) {
			echo "Ei voida jatkaa.</font>";
			exit;
		}

		echo "Haetaan myyntitiedot... ";

		// jos ollaan syötetty poikkeava päivä, käytetään sitä
		if ($pvm != '') {
			// tämävuosi ja viimevuosi
			$vvt = substr($pvm, 0, 4);
			$vvv = substr($pvm, 0, 4) - 1;
			$now = $pvm;
			$ede = $vvv.substr($pvm, 4);
		}
		else {
			// tämävuosi ja viimevuosi
			$vvt = date("Y");
			$vvv = date("Y") - 1;
			$now = date("Y-m-d");
			$ede = $vvv.date("-m-d");
		}

		$query = "select lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, osasto, try,
				sum(if(tapvm>='$vvt-01-01' and tapvm<='$now', tilausrivi.rivihinta, 0)) va,
				sum(if(tapvm>='$vvv-01-01' and tapvm<='$ede', tilausrivi.rivihinta, 0)) ed,
				sum(if(tapvm>='$vvt-01-01' and tapvm<='$now', tilausrivi.kpl, 0)) kplva,
				sum(if(tapvm>='$vvv-01-01' and tapvm<='$ede', tilausrivi.kpl, 0)) kpled
				FROM tilausrivi, lasku
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and lasku.yhtio=tilausrivi.yhtio
				and lasku.tunnus=tilausrivi.otunnus
				and lasku.ytunnus in ($asiakkaat)
				and tyyppi='L'
				and tapvm >= '$vvv-01-01'
				and try > 0
				group by ytunnus, osasto, try
				having va > 0 or ed > 0 or kplva > 0 or kpled > 0
				order by ytunnus, osasto, try";
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
			if ($edasiakas != $row["ytunnus"]) {

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

				$query = "select email from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$row[ytunnus]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				// edellinen ytunnus ja email talteen
				$edasiakas = $row["ytunnus"];
				$edemail   = $asrow["email"];

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

		echo "<font class='message'>Asiakkaille, joilla on sähköposti lähetetään viesti automaattisesti.<br>";
		echo "Muut ostoseurannat tulostetaan valitsemaasi tulostimeen.</font><br><br>";

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='tulosta'>";

		echo "<table>";
		echo "<tr><th>Valitse tulostin:</th>";
		echo "<td><select name='komento'>";
		echo "<option value=''>Ei kirjoitinta</option>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow=mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>Syötä ostoraja:</th>";
		echo "<td><input type='text' name='raja' value='10000' size='10'> $yhtiorow[valkoodi] viimeiset 12kk</td></tr>";
		echo "<tr><th>Älä lähetä sähköposteja:</th>";
		echo "<td><input type='checkbox' name='emailok'> vain sähköpostittomat asiakkaat</td></tr>";
		echo "<tr><th>Asiakasnumero:</th>";
		echo "<td><input type='text' name='asnum' size='10'> aja vain tämä asiakas (tyhjä=kaikki)</td></tr>";
		echo "<tr><th>Poikkeava pvm:</th>";
		echo "<td><input type='text' name='pvm' size='10'> vvvv-kk-pp</td></tr>";
		echo "</table>";

		echo "<br><input type='submit' value='Tulosta'></form>";
	}

	require ("../inc/footer.inc");
?>
