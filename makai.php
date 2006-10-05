<?php
	require "inc/parametrit.inc";
	
	if (strtoupper($yhtiorow['maakoodi']) == 'SE') {
		require "makaise.php";
		exit;
	}
	if (strtoupper($yhtiorow['maakoodi']) != 'FI') {
		echo "<font class='error'>".t("Yrityksen maakoodi ei ole sallittu")." (FI, SE) '$yhtiorow[maakoodi]'</font><br>";
		exit;
	}
	echo "<font class='head'>LM03-maksuaineisto</font><hr>";
	// Tarkistetaan yrityksen pankkitilit

	$query = "SELECT tilino, nimi, tunnus
			  FROM yriti
			  WHERE yhtio ='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	while ($row=mysql_fetch_array($result)) {
		if ((substr($row[0], 0, 1) >= 0) and (substr($row[0], 0, 1) <= '9')) {
			$pankkitili = $row[0];
			require "inc/pankkitilinoikeellisuus.php";

			if ($pankkitili == "") {
				echo "<font class='error'>Pankkitili $row[1], '$row[0]' on virheellinen</font><br>";
				exit;
			}
			else {
				if ($row[0] != $pankkitili) {
					$query = "UPDATE yriti SET tilino = '$pankkitili' WHERE tunnus = $row[2]";
					$xresult = mysql_query($query) or pupe_error($query);
					echo "Päivitin tilin $row[1]<br><br>";
				}
			}
		}
	}

	// --- LM03 AINEISTO

	//Tutkitaan onko kotimaan aineistossa monta maksupäivää?
	$query = "	SELECT distinct(olmapvm)
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' 
				and tila = 'P' 
				and maakoodi = 'fi' 
				and maksaja = '$kukarow[kuka]'
				ORDER BY 1";
	$pvmresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($pvmresult) != 0) {
		
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$nimi = "/tmp/lm03-" . date("d.m.y.H.i.s") . ".txt";			
		}
		else {			
			$nimi = $yhtiorow['pankki_polku'] . "lm03-" . date("d.m.y.H.i.s") . ".txt";
		}
		
		$toot = fopen($nimi,"w+");
		
		if (!$toot) {
			echo "En saanut tiedostoa auki! Tarkista polku yritykseltä!";
			exit;
		}
		echo "<font class='message'>Kotimaan maksujen tiedoston nimi on: $nimi</font><br>";
	}
	else {
		echo "<font class='message'>Sopivia laskuja ei löydy</font>";
	}
	//echo "$query<br>";
	while ($pvmrow=mysql_fetch_array($pvmresult)) {
		echo "<font class='message'>Maksupäivä: $pvmrow[0]</font><br>";
		$makskpl = 0;
		$makssumma = 0;
		$tilinoarray=array();

		//Tutkitaan onko kotimaan aineistossa hyvityslaskuja
		$query = "	SELECT maksu_tili, tilinumero, nimi
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' 
					and tila = 'P' 
					and maakoodi = 'fi' 
					and summa < 0 
					and maksaja = '$kukarow[kuka]' 
					and olmapvm = '$pvmrow[olmapvm]'
					GROUP BY maksu_tili, tilinumero";
	
		$result = mysql_query($query) or pupe_error($query);
	
		//Löytyykö hyvityksiä?
		if (mysql_num_rows($result) != 0) {
			echo "<font class='message'>Tarkistan hyvityslaskut!</font><br>";
			while ($laskurow=mysql_fetch_array ($result)) {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' 
							and tila = 'P' 
							and maakoodi = 'fi' 
							and maksaja = '$kukarow[kuka]' 
							and tilinumero='$laskurow[tilinumero]' 
							and maksu_tili='$laskurow[maksu_tili]' 
							and olmapvm = '$pvmrow[olmapvm]'";
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
					echo "<font class='error'>$laskurow[nimi] ($laskurow[tilinumero]) tililtä $laskurow[maksu_tili] hyvitykset suuremmat kuin veloitukset. Koko aineisto hylätään</font><br>";
					exit;
				}
			}
			
			echo "<font class='message'>Hyvitykset ok!</font><br>";
		}
		
		// --- LM03 AINEISTO MAKSUTILIT	
		$query = "	SELECT yriti.tunnus, yriti.tilino, yriti.nimi nimi
					FROM lasku, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' 
					and maakoodi = 'fi' 
					and yriti.tunnus = maksu_tili 
					and yriti.yhtio = lasku.yhtio 
					and maksaja = '$kukarow[kuka]' 
					and olmapvm = '$pvmrow[olmapvm]'
					GROUP BY yriti.tilino";
		$yritiresult = mysql_query($query) or pupe_error($query);
				
		if (mysql_num_rows($yritiresult) != 0) {

			while ($yritirow=mysql_fetch_array ($yritiresult)) {
			
				$yritystilino =  $yritirow['tilino'];
				$yrityytunnus =  $yhtiorow['ytunnus'];
				$maksupvm = $pvmrow['olmapvm'];
				$yritysnimi = $yhtiorow['nimi'];
				require "inc/lm03otsik.inc";
			
				require "inc/lm03hyvitykset.inc"; //Täällä käistellään kaikki laskut joihin liittyy hyvityksiä
				
				// Yritämme nyt välittää maksupointterin $laskusis1:ssä --> $laskurow[9] --> lasku.tunnus
				$query = "	SELECT maksu_tili,
							left(concat_ws(' ', lasku.nimi, nimitark),30),
							left(concat_ws(' ', osoite, osoitetark),20),
							left(concat_ws(' ', postino, postitp),20),
							summa, valkoodi, viite, viesti, tilinumero,
							lasku.tunnus, sisviesti2, yriti.tilino, alatila, kasumma
							FROM lasku, yriti
							WHERE lasku.yhtio = '$kukarow[yhtio]' 
							and tila = 'P' 
							and maakoodi = 'fi' 
							and yriti.tunnus = maksu_tili 
							and yriti.yhtio = lasku.yhtio 
							and maksaja = '$kukarow[kuka]' 
							and maksu_tili = $yritirow[tunnus] 
							and olmapvm = '$pvmrow[olmapvm]'
							ORDER BY tilinumero, summa desc";			
				$result = mysql_query($query) or pupe_error($query);
			
				while ($laskurow=mysql_fetch_array ($result)) {
					$laskutapahtuma = '10';
					$yritystilino =  $laskurow[11];
					$laskunimi1 = $laskurow[1];
					$laskunimi2 = $laskurow[2];
					$laskunimi3 = $laskurow[3];
					if ($laskurow[12] == 'K') { // maksetaan käteisalennuksella
						$laskusumma = $laskurow[4] - $laskurow[13];
					}
					else {
						$laskusumma = $laskurow[4];
					}
					$laskutilno = $laskurow[8];
					$laskusis1  = $laskurow[9];
					$laskusis2  = $laskurow[10];
			  	 	$laskutyyppi = 5;
			  	 	$laskuviesti = $laskurow[7];
			  	 	if (strlen($laskurow[6]) > 0) {
						$laskuviesti = sprintf ('%020s',$laskurow[6]); //Etunollatäyttö
			  	 		$laskutyyppi = 1;
			  	 	}
					require "inc/lm03rivi.inc";
					$makskpl += 1;
					$makssumma += $laskusumma;
				}
				require "inc/lm03summa.inc";
				
				echo "<font class='message'>Tilin $yritirow[nimi] loppusumma on $makssumma. Summa koostuu $makskpl maksusta</font><br>";
				
				$query = "UPDATE lasku SET tila = 'Q'
				          WHERE lasku.yhtio = '$kukarow[yhtio]' 
				          and tila = 'P' and maakoodi = 'fi' 
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
			echo "<b>Sopivia laskuja ei löydy</b>";
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

			// poistetaan vaan jos faili on tmp dirikassa ja sähköpostin lähetys onnistui
			if (substr($nimi,0,5) == "/tmp/" and $boob !== FALSE) {
				system("rm -f $nimi");
			}
		}	
	}
	//----------- LUM3 AINEISTO --------------------------
	echo "<br><br><br><font class='head'>LUM3-maksuaineisto</font><hr>";


	// Yritämme nyt välittää maksupointterin $laskusis1:ssä --> $laskurow[9] --> tunnus
	$query = "	SELECT maksu_tili,
				left(concat_ws(' ', lasku.nimi, nimitark),45),
				left(concat_ws(' ', osoite, osoitetark),45),
				left(concat_ws(' ', postino, postitp),45),
				summa,
				valkoodi,
				viite,
				viesti,
				ultilno,
				lasku.tunnus, sisviesti2, yriti.tilino,
				maa, maakoodi,
				pankki1, pankki2, pankki3, pankki4,
				swift, alatila, kasumma, kurssi
				FROM lasku, yriti, valuu
				WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'P' and
				maakoodi <> 'fi' and
				yriti.tunnus = maksu_tili and
				yriti.yhtio = lasku.yhtio and
				valuu.nimi = lasku.valkoodi and
				valuu.yhtio = lasku.yhtio and
				maksaja = '$kukarow[kuka]'
				ORDER BY maksu_tili, valkoodi, ytunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		 	echo "<font class='message'>Sopivia laskuja ei löydy</font>";
	}
	else {
			
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$nimi = "/tmp/lum3-" . date("d.m.y.H.i.s") . ".txt";			
		}
		else {			
			$nimi = $yhtiorow['pankki_polku'] . "lum3-" . date("d.m.y.H.i.s") . ".txt";
		}
		
		$toot = fopen($nimi,"w+");
		if (!$toot) {
			echo "En saanut tiedostoa auki! Tarkista polku yritykseltä!";
			exit;
		}
		echo "<font class='message'>Ulkomaan maksujen tiedoston nimi on: $nimi</font><br>";
		
		while ($laskurow=mysql_fetch_array ($result)) {
			$yritysnimi = strtoupper($yhtiorow ['nimi']);
			$yritysosoite = strtoupper($yhtiorow['osoite']);
			$yritystilino =  $laskurow[11];
			$laskunimi1 = $laskurow[1];
			$laskunimi2 = $laskurow[2];
			$laskunimi3 = $laskurow[3];
			if ($laskurow[19] == 'K') { // maksetaan käteisalennuksella
				$laskusumma = $laskurow[4] - $laskurow[20];
			}
			else {
				$laskusumma = $laskurow[4];
			}
			$laskuvaluutta = $laskurow[5];
			$laskutilino = $laskurow[8];
			$laskuaihe = $laskurow[7] . " " . $laskurow[9];
			$laskumaakoodi = $laskurow[13];
			$laskupankki1  = $laskurow[14];
			$laskupankki2  = $laskurow[15];
			$laskupankki3  = $laskurow[16];
			$laskupankki4  = $laskurow[17];
			$laskuswift = $laskurow[18];

			if (($laskurow[0] != $edmaksu_tili) || ($laskurow[5] != $edvalkoodi)) {
				if (strlen($edmaksu_tili) > 0) {
					require "inc/lum2summa.inc";
					$makskpl = 0;
					$makssumma = 0;
				}
				$yritystilino =  $laskurow[11];
				$yrityytunnus =  $yhtiorow['ytunnus'];
				require "inc/lum2otsik.inc";
				$edmaksu_tili = $laskurow[0];
				$edvalkoodi = $laskurow[5];
			}
			require "inc/lum2rivi.inc";
			$makskpl += 1;
			$makssumma += $laskusumma;
		}
		echo "<font class='message'>Aineiston loppusumma on $makssumma. Summa koostuu $makskpl laskusta</font><br>";
		
		require "inc/lum2summa.inc";
		
		fclose($toot);
		
		
		//Laitetaan sähköpostiin jos yhtiorow pankki_polusta löytyy @-merkki
		if (strpos($yhtiorow["pankki_polku"],"@") !== FALSE) {
			$liite = $nimi;
			$kutsu = t("Ulkomaanmaksuaineisto");
			$kukarow["eposti"] = $yhtiorow["pankki_polku"];
			$ctype = "TEXT";
			
			require("inc/sahkoposti.inc");	

			// poistetaan vaan jos faili on tmp dirikassa ja sähköpostin lähetys onnistui
		
			if (substr($nimi,0,5) == "/tmp/" and $boob !== FALSE) {
				system("rm -f $nimi");
			}
		}		
		
		$query = "	UPDATE lasku SET tila = 'Q'
					WHERE lasku.yhtio = '$kukarow[yhtio]' 
					and tila = 'P' 
					and maakoodi <> 'fi' 
					and maksaja = '$kukarow[kuka]'
				    ORDER BY yhtio, tila";
		$result = mysql_query($query) or pupe_error($query);
	}
	require "inc/footer.inc";
?>
