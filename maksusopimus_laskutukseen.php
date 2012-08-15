<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "maksusopimus_laskutukseen.php") !== FALSE) {

		// DataTables päälle
		$pupe_DataTables = "maksusopparit";

		require("inc/parametrit.inc");
	}

	if (!function_exists("ennakkolaskuta")) {
		function ennakkolaskuta($tunnus) {
			global $kukarow, $yhtiorow;

			///* Etsitään laskun kaikki tiedot jolle maksusopimus on tehty *///
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$tunnus' and tila in ('L','N','R') and alatila != 'X'";
			$stresult = pupe_query($query);

			if (mysql_num_rows($stresult) == 0) {
				echo "Otsikkoa '$tunnus' ei löytynyt, tai se on väärässä tilassa.";
				return 0;
			}

			$laskurow = mysql_fetch_array($stresult);

			if ($debug==1) echo t("Perusotsikko löytyi")." $laskurow[nimi]<br>";

			// Onko sopimuksella vielä jotain ennakkolaskutettavaa
			$query = "	SELECT yhtio
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = 0";
			$posres = pupe_query($query);

			if (mysql_num_rows($posres) <= 1) {
				echo t("VIRHE: Tilauksella ei ole ennakkolaskutettavia positioita!")."<br>";
				return 0;
			}

			//	tarkistetaan että meillä on jotain järkevää laskutettavaa
			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = 0
						ORDER BY tunnus
						LIMIT 1";
			$posres = pupe_query($query);
			$posrow = mysql_fetch_array($posres);

			if ($debug==1) echo t("Löydettiin maksupositio")." $posrow[tunnus], $posrow[osuus] %, $posrow[maksuehto]<br>";

			if ($posrow["summa"] <= 0 or $posrow["maksuehto"] == 0 or (int) $posrow["tunnus"] == 0) {
				echo t("VIRHE: laskutusposition summa on nolla tai sen alle. Korjaa tämä!")."<br>";
				return 0;
			}

			// Tilausrivin kommentti-kenttään menevä kommentti
			$query = "	SELECT
						sum(if (uusiotunnus > 0, 1, 0)) laskutettu,
						count(*) yhteensa
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'";
			$abures = pupe_query($query);
			$aburow = mysql_fetch_array($abures);

			$lahteva_lasku = ($aburow["laskutettu"] + 1)."/".$aburow["yhteensa"];

			// tehdään vanhasta laskusta 1:1 kopio...
			$query = "INSERT INTO lasku SET ";
			for ($i=0; $i<mysql_num_fields($stresult); $i++) {

				// paitsi tilaan laitetaan N
				if (mysql_field_name($stresult,$i)=='tila') {
					$query .= "tila='N',";
				}
				elseif (mysql_field_name($stresult,$i)=='alatila') {
					$query .= "alatila='',";
				}
				elseif (mysql_field_name($stresult,$i)=='ketjutus') {
					$query .= "ketjutus='o',";
				}
				elseif (mysql_field_name($stresult,$i)=='tilaustyyppi') {
					if (strtoupper($laskurow[$i]) == "A") {
						$query .= "tilaustyyppi='A',";
					}
					else {
						$query .= "tilaustyyppi='L',";
					}
				}
				// laatijaksi klikkaaja
				elseif (mysql_field_name($stresult,$i)=='laatija') {
					$query .= "laatija='$kukarow[kuka]',";
				}
				elseif (mysql_field_name($stresult,$i)=='eilahetetta') {
					$query .= "eilahetetta='',";
				}
				// keräysaika, luontiaika ja toimitusaikaan now
				elseif (mysql_field_name($stresult,$i)=='kerayspvm' or
						mysql_field_name($stresult,$i)=='luontiaika' or
						mysql_field_name($stresult,$i)=='toimaika') {
					$query .= mysql_field_name($stresult,$i)."=now(),";
				}
				// nämä kentät tyhjennetään
				elseif (mysql_field_name($stresult,$i)=='kapvm' or
						mysql_field_name($stresult,$i)=='tapvm' or
						mysql_field_name($stresult,$i)=='olmapvm' or
						mysql_field_name($stresult,$i)=='summa' or
						mysql_field_name($stresult,$i)=='kasumma' or
						mysql_field_name($stresult,$i)=='hinta' or
						mysql_field_name($stresult,$i)=='kate' or
						mysql_field_name($stresult,$i)=='arvo' or
						mysql_field_name($stresult,$i)=='maksuaika' or
						mysql_field_name($stresult,$i)=='lahetepvm' or
						mysql_field_name($stresult,$i)=='viite' or
						mysql_field_name($stresult,$i)=='laskunro' or
						mysql_field_name($stresult,$i)=='mapvm' or
						mysql_field_name($stresult,$i)=='tilausvahvistus' or
						mysql_field_name($stresult,$i)=='viikorkoeur' or
						mysql_field_name($stresult,$i)=='tullausnumero' or
						mysql_field_name($stresult,$i)=='laskutuspvm' or
						mysql_field_name($stresult,$i)=='laskuttaja' or
						mysql_field_name($stresult,$i)=='laskutettu' or
						mysql_field_name($stresult,$i)=='lahetepvm' or
						mysql_field_name($stresult,$i)=='maksaja' or
						mysql_field_name($stresult,$i)=='maksettu' or
						mysql_field_name($stresult,$i)=='maa_maara' or
						mysql_field_name($stresult,$i)=='kuljetusmuoto' or
						mysql_field_name($stresult,$i)=='kauppatapahtuman_luonne' or
						mysql_field_name($stresult,$i)=='sisamaan_kuljetus' or
						mysql_field_name($stresult,$i)=='sisamaan_kuljetusmuoto' or
						mysql_field_name($stresult,$i)=='poistumistoimipaikka' or
						mysql_field_name($stresult,$i)=='vanhatunnus' or
						mysql_field_name($stresult,$i)=='poistumistoimipaikka_koodi') {
					$query .= mysql_field_name($stresult,$i)."='',";
				}
				// maksuehto tulee tältä positiolta
				elseif (mysql_field_name($stresult,$i)=='maksuehto') {
					$query .= "maksuehto ='$posrow[maksuehto]',";
				}
				// erpcm voi tulla tältä positiolta
				elseif (mysql_field_name($stresult,$i)=='erpcm') {
					if ($posrow["erpcm"] != '0000-00-00') {
						$query .= "erpcm ='$posrow[erpcm]',";
					}
					else {
						$query .= "erpcm ='0000-00-00',";
					}
				}
				elseif (mysql_field_name($stresult,$i)=='clearing') {
					$query .= "clearing ='ENNAKKOLASKU',";
				}
				elseif (mysql_field_name($stresult,$i)=='jaksotettu') {
					// Käännetän ennakkolaskun jaksotettukenttä negatiiviseksi jotta me löydetään ne yksiselitteisesti,
					// mutta kuitenkin niin, etteivät ne sekoitu maksusopimuksen alkuperäisiin tilauksiin
					$query .= "jaksotettu ='".($laskurow[$i]*-1)."',";
				}
				// ja kaikki muut paitsi tunnus sellaisenaan
				elseif (mysql_field_name($stresult,$i)!='tunnus') {
					$query .= mysql_field_name($stresult,$i)."='".$laskurow[$i]."',";
				}
			}

			$query = substr($query,0,-1);
			$stresult = pupe_query($query);
			$id = mysql_insert_id();

			// tehdään vanhan laskun lisätiedoista 1:1 kopio...
			$query = "	SELECT *
						FROM laskun_lisatiedot
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$tunnus'";
			$lisatiedot_result = pupe_query($query);
			$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

			$query = "INSERT INTO laskun_lisatiedot SET ";

			for ($i = 0; $i < mysql_num_fields($lisatiedot_result); $i++) {
				if (mysql_field_name($lisatiedot_result, $i) == 'laatija') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='$kukarow[kuka]',";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) == 'luontiaika') {
					$query .= mysql_field_name($lisatiedot_result, $i)."=now(),";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) == 'otunnus') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='$id',";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) != 'tunnus') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='".$lisatiedot_row[$i]."',";
				}
			}

			$query = substr($query, 0, -1);
			$lisatiedot_result = pupe_query($query);

			// tehdään vanhan laskun työmääräystidoista 1:1 kopio...
			$query = "	SELECT *
						FROM tyomaarays
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$tunnus'";
			$lisatiedot_result = pupe_query($query);
			$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

			$query = "INSERT INTO tyomaarays SET ";

			for ($i = 0; $i < mysql_num_fields($lisatiedot_result); $i++) {
				if (mysql_field_name($lisatiedot_result, $i) == 'laatija') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='$kukarow[kuka]',";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) == 'luontiaika') {
					$query .= mysql_field_name($lisatiedot_result, $i)."=now(),";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) == 'otunnus') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='$id',";
				}
				elseif (mysql_field_name($lisatiedot_result, $i) != 'tunnus') {
					$query .= mysql_field_name($lisatiedot_result, $i)."='".$lisatiedot_row[$i]."',";
				}
			}

			$query = substr($query, 0, -1);
			$lisatiedot_result = pupe_query($query);


			if ($debug==1) echo t("Perustin laskun")." $laskurow[nimi] $id<br>";

			$query_ale_lisa = generoi_alekentta('M');

			// Lasketaan maksusopimuksen arvo verokannoittain jotta voidaan laskuttaa ennakot oikeissa alveissa
			// ja lisätään ennakkolaskutusrivi laskulle, vain jaksotetut rivit!
			$query = "	SELECT
						sum(if (tilausrivi.jaksotettu=lasku.jaksotettu, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) jaksotettavaa
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and (tilausrivi.varattu+tilausrivi.jt) > 0 and tilausrivi.jaksotettu=lasku.jaksotettu
						WHERE lasku.yhtio 		= '$kukarow[yhtio]'
						and lasku.jaksotettu  	= '$tunnus'
						GROUP by lasku.jaksotettu";
			$result = pupe_query($query);
			$sumrow = mysql_fetch_array($result);

			$query = "	SELECT
						sum(if (tilausrivi.jaksotettu=lasku.jaksotettu, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) summa,
						if (tilausrivi.alv >= 600 or tilausrivi.alv < 500, tilausrivi.alv, 0) alv
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and (tilausrivi.varattu + tilausrivi.jt) > 0 and tilausrivi.jaksotettu = lasku.jaksotettu)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu = '$tunnus'
						GROUP BY lasku.jaksotettu, alv";
			$sresult = pupe_query($query);
			$tot = 0;

			//	Lasku voi mennä myös kaukomaille, joten haetaan tämän asiakkaan kieli..
			$query = "SELECT kieli from asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$kielires = pupe_query($query);
			$kielirow = mysql_fetch_array($kielires);

			if ($kielirow["kieli"] == "") {
				$kielirow["kieli"]="fi";
			}

			$nimitys 		= t($posrow["kuvaus"], $kielirow["kieli"]);
			$rivikommentti 	= t("Ennakkolasku", $kielirow["kieli"])." $lahteva_lasku ".t("tilaukselle", $kielirow["kieli"])." $tunnus ".t("Osuus", $kielirow["kieli"])." ".round($posrow["osuus"],2)."% ";

			if ($posrow["lisatiedot"] != "") {
				$rivikommentti .= "\n ".$posrow["lisatiedot"];
			}

			if (mysql_num_rows($sresult) == 0) {
				echo "<font class = 'error'>".t("VIRHE: Ennakkolaskulla ei ole yhtään jaksotettua tilausriviä!")." $tunnus</font><br>";
				echo "<font class = 'message'>".t("Käy tekemässä ennakkolasku manuaalisesti. Ennakkolaskulle perustetun laskun tunnus on")." $id</font><br>";
				echo "<font class = 'message'>".t("Ennakkolaskutuksen tuotenumero on")." $yhtiorow[ennakkomaksu_tuotenumero]</font><br><br>";

				$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, laatija, laadittu) values
							('0', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '$rivikommentti', '$kukarow[kuka]', now())";
				$addtil = pupe_query($query);
			}
			else {
				while($row = mysql_fetch_array($sresult)) {

					// $summa on verollinen tai veroton riippuen yhtiön myyntihinnoista
					$summa = $row["summa"]/$sumrow["jaksotettavaa"] * $posrow["summa"];

					$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, laatija, laadittu) values
								('$summa', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '$rivikommentti', '$kukarow[kuka]', now())";
					$addtil = pupe_query($query);

					if ($debug==1) echo t("Lisättiin ennakkolaskuun rivi")." $summa $row[alv] otunnus $id<br>";

					$tot += $summa;
				}

				echo "<font class = 'message'>".t("Tehtiin ennakkolasku tilaukselle")." $tunnus ".t("tunnus").": $id ".t("osuus").": $posrow[osuus]% ".t("summa").": $tot</font><br>";
			}

			// Päivitetään positiolle tämän laskun tunnus
			$query = "UPDATE maksupositio set uusiotunnus='$id' where tunnus='$posrow[tunnus]'";
			$result = pupe_query($query);

			// merkataan tässä vaiheessa luotu ennakkomaksu-tilaus toimitetuksi
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika=now(), kerattyaika=now()
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$id'";
			$result = pupe_query($query);

			// ja päivitetään luotu ennakkomaksu-tilaus laskutusjonoon
			$query = "	UPDATE lasku
						set tila='L', alatila='D'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$id'";
			$result = pupe_query($query);

			return $id;
		}
	}

	if (!function_exists("loppulaskuta")) {
		function loppulaskuta($tunnus) {
			global $kukarow, $yhtiorow;

			///* Tutkitaan alkuperäisten tilausten tiloja *///
			$query = "	SELECT sum(if (tila='L' and alatila = 'J',1,0)) toimitus_valmis, sum(if (tila='R',1,0)) rojekti, count(*) kaikki, max(tunnus) vikatunnus
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu = '$tunnus' and tila != 'D' and tila in ('L','N','R')";
			$stresult = pupe_query($query);

			if (mysql_num_rows($stresult) == 0) {
				echo "<font class='error'>Otsikkoa '$tunnus' ei löytynyt, tai se on väärässä tilassa.</font><br><br>";
				return 0;
			}
			else {
				$row = mysql_fetch_array($stresult);
				if ($row["kaikki"] - ($row["toimitus_valmis"] + $row["rojekti"]) <> 0) {
					echo "<font class='error'>Laskutussopimuksella on kaikki tilaukset oltava toimitettuna ennen loppulaskutusta.</font><br><br>";
					return 0;
				}
				$vikatunnus = $row["vikatunnus"];
			}

			// tarkistetaan että meillä on jotain järkevää laskutettavaa
			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = ''
						ORDER BY tunnus
						LIMIT 1";
			$posres = pupe_query($query);

			if (mysql_num_rows($posres) == 1) {
				$posrow = mysql_fetch_array($posres);
			}
			else {
				echo "<font class='error'>".t("VIRHE: Viimeinen maksupositio puuttuu! Loppulaskutus ei onnistu.")."</font><br><br>";
				return 0;
			}

			if ($debug==1) echo t("Löydettiin maksupositio")." $posrow[tunnus], $posrow[osuus] %, $posrow[maksuehto]<br>";

			// Tilausrivin kommentti-kenttään menevä kommentti
			$query = "	SELECT
						sum(if (uusiotunnus > 0, 1, 0)) laskutettu,
						count(*) yhteensa
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'";
			$abures = pupe_query($query);
			$aburow = mysql_fetch_array($abures);

			$lahteva_lasku = ($aburow["laskutettu"] + 1)."/".$aburow["yhteensa"];

			// varmistetaan että laskutus näyttäisi olevan OK!!
			if ($aburow["yhteensa"] - $aburow["laskutettu"] > 1) {
				echo "<font class='error'>".t("VIRHE: Loppulaskutus ei onnistu koska positioita on jäljellä enemmän kuin yksi!")."</font><br><br>";
				return 0;
			}

			//	Tarkastetaan että meillä on ok maksuehto loppulaskutukseen!!!
			$apuqu = "	SELECT *
						from maksuehto
						where yhtio    = '$kukarow[yhtio]'
						and tunnus     = '$posrow[maksuehto]'
						and jaksotettu = ''";
			$meapu = pupe_query($apuqu);

			$erlisa = "";

			if (mysql_num_rows($meapu) == 1) {
				$meapurow = mysql_fetch_array($meapu);
			}
			else {
				echo "<font class='error'>".t("VIRHE: Maksuposition maksuehto puuttuu!")."</font><br><br>";
				return 0;
			}

			if ($meapurow["erapvmkasin"] != "" and $posrow["erpcm"] == "0000-00-00") {
				echo "<font class='error'>".t("VIRHE: Loppulaskun maksuehdon eräpäivä puuttuu")."!!!</font><br><br>";
				return 0;
			}
			elseif ($meapurow["erapvmkasin"] != "") {
				$erlisa = " erpcm = '$posrow[erpcm]',";
			}

			echo "<font class = 'message'>".t("Loppulaskutetaan tilaus")." $tunnus<br></font><br>";

			//Lasketaan paljonko ollaan jo laskutettu ja millä verokannoilla
			$query = "	SELECT round(sum(rivihinta * if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) laskutettu, tilausrivi.alv
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and kpl <> 0 and uusiotunnus > 0 and tilausrivi.tuoteno='$yhtiorow[ennakkomaksu_tuotenumero]'
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu = '".($tunnus*-1)."'
						GROUP BY alv";
			$sresult = pupe_query($query);

			//	Haetaan asiakkaan kieli niin hekin ymmärtävät..
			$query = "SELECT kieli from asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$kielires = pupe_query($query);
			$kielirow = mysql_fetch_array($kielires);

			if ($kielirow["kieli"] == "") {
				$kielirow["kieli"]="fi";
			}

			$nimitys 		= t($posrow["kuvaus"], $kielirow["kieli"]);
			$rivikommentti 	= t("Ennakkolaskutuksen hyvitys", $kielirow["kieli"])." $lahteva_lasku. ";

			if ($posrow["lisatiedot"] != "") {
				$rivikommentti .= "\n ".$posrow["lisatiedot"];
			}

			while ($row = mysql_fetch_array($sresult)) {

				$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika, laatija, laadittu)
							values  ('$row[laskutettu]', 'N', '-1', '-1', '$vikatunnus', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '$rivikommentti', '$kukarow[kuka]', now(), '$kukarow[kuka]', now(), '$kukarow[kuka]', now())";
				$addtil = pupe_query($query);

				if ($debug==1) echo t("Loppulaskuun lisättiin ennakkolaskun hyvitys")." -$row[laskutettu] alv $row[alv]% otunnus $vimppa<br>";
			}

			// Päivitetään positiolle laskutustunnus
			$query = "UPDATE maksupositio set uusiotunnus='$vikatunnus' where tunnus = '$posrow[tunnus]'";
			$result = pupe_query($query);

			// Alkuperäinen tilaus/tilaukset menee laskutukseen
			$query = "	UPDATE lasku
						SET maksuehto 	= '$posrow[maksuehto]',
						clearing 		= 'loppulasku',
						ketjutus 		= 'o',
						$erlisa
						alatila 		= 'D'
						WHERE yhtio 	= '$kukarow[yhtio]'
						and jaksotettu 	= '$tunnus'
						and tila		!= 'R'";
			$result = pupe_query($query);

			//	Merkataan projekti valmiiksi
			$query = "	UPDATE lasku
						SET alatila 	= 'B'
						WHERE yhtio 	= '$kukarow[yhtio]'
						and jaksotettu 	= '$tunnus'
						and tila		= 'R'";
			$result = pupe_query($query);

			$query = "	SELECT group_concat(distinct tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu 	= '$tunnus'";
			$lres = pupe_query($query);
			$lrow = mysql_fetch_array($lres);

			return $lrow["tunnukset"];
		}
	}

	//Käyttöliittymä
	if (strpos($_SERVER['SCRIPT_NAME'], "maksusopimus_laskutukseen.php") !== FALSE) {
		$query = "	SELECT nimitys
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
		$tresult = pupe_query($query);

		if (mysql_num_rows($tresult) == 0) die(t("VIRHE: Yhtiöllä EI OLE ennakkolaskutustuotetta, sopimuslaskutusta ei voida toteuttaa!"));
		echo "<font class='head'>".t("Sopimuslaskutus").":</font><hr><br>";


		if ($tee == "ennakkolaskuta") {
			ennakkolaskuta($tunnus);
			$tee = "";
		}

		if ($tee == "ennakkolaskuta_kaikki") {
			// seuraava positio on tämä siis
			$query = "	SELECT count(*)-1 as ennakko_kpl
						FROM maksupositio
						JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
						WHERE maksupositio.yhtio 	 = '$kukarow[yhtio]'
						and maksupositio.otunnus 	 = '$tunnus'
						and maksupositio.uusiotunnus = 0
						ORDER BY maksupositio.tunnus";
			$rahres = pupe_query($query);
			$posrow = mysql_fetch_array($rahres);

			for($ie=0; $ie < $posrow["ennakko_kpl"]; $ie++) {
				//tehdään ennakklasku
				ennakkolaskuta($tunnus);
			}
			$tee = "";
		}


		if ($tee == "loppulaskuta") {
			loppulaskuta($tunnus);
			$tee = "";
		}

		if ($tee == "") {

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(msg){
							if (confirm(msg)) {
								return true;
							}
							else {
								skippaa_tama_submitti = true;
								return false;
							}
						}
					</SCRIPT>";

			$query = "	SELECT
						lasku.jaksotettu jaksotettu,
						concat_ws(' ',lasku.nimi, lasku.nimitark) nimi,
						sum(if (maksupositio.uusiotunnus > 0 and uusiolasku.tila='L' and uusiolasku.alatila='X', 1, 0)) laskutettu_kpl,
						sum(if (maksupositio.uusiotunnus = 0, 1, 0)) tekematta_kpl,
						count(*) yhteensa_kpl,
						sum(if (maksupositio.uusiotunnus = 0 or (maksupositio.uusiotunnus > 0 and uusiolasku.alatila!='X'), maksupositio.summa,0)) laskuttamatta,
						sum(if (maksupositio.uusiotunnus > 0 and uusiolasku.tila='L' and uusiolasku.alatila='X', maksupositio.summa, 0)) laskutettu,
						sum(maksupositio.summa) yhteensa
						FROM lasku
						JOIN maksupositio ON maksupositio.yhtio = lasku.yhtio and maksupositio.otunnus = lasku.tunnus
						JOIN maksuehto ON maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.jaksotettu != ''
						LEFT JOIN lasku uusiolasku ON maksupositio.yhtio = uusiolasku.yhtio and maksupositio.uusiotunnus = uusiolasku.tunnus
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu > 0
						and lasku.tila in ('L','N','R','A') and lasku.alatila != 'X'
						GROUP BY jaksotettu, nimi
						HAVING yhteensa_kpl > laskutettu_kpl
						ORDER BY jaksotettu desc";
			$result = pupe_query($query);

			pupe_DataTables(array(array($pupe_DataTables, 7, 8)));

			echo "<table class='display dataTable' id='$pupe_DataTables'>";

			echo "<thead>";
			echo "<tr>
					<th>".t("Tilaus")."</th>
					<th>".t("Asiakas")."</th>
					<th>".t("Erä")."</th>
					<th>".t("Laskuttamatta")."</th>
					<th>".t("Laskutettu")."</th>
					<th>".t("Yhteensä")."</th>
					<th>".t("Seuraava positio")."</th>
					<th style='visibility:hidden;'></th>
				</tr>";

			echo "<tr>
					<td><input type='text' class='search_field' name='search_tilaus'></td>
					<td><input type='text' class='search_field' name='search_asiakas'></td>
					<td><input type='text' class='search_field' name='search_era'></td>
					<td><input type='text' class='search_field' name='search_laskuttamatta'></td>
					<td><input type='text' class='search_field' name='search_laskutettu'></td>
					<td><input type='text' class='search_field' name='search_yhteensa'></td>
					<td><input type='text' class='search_field' name='search_seuraava'></td>
					<td style='visibility:hidden;'></td>
				</tr>";

			echo "</thead>";
			echo "<tbody>";

			while ($row = mysql_fetch_array($result)) {
				// seuraava positio on tämä siis
				$query = "	SELECT maksupositio.*, maksuehto.teksti, maksuehto.teksti
							FROM maksupositio
							JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
							WHERE maksupositio.yhtio = '$kukarow[yhtio]'
							and maksupositio.otunnus = '$row[jaksotettu]'
							and maksupositio.uusiotunnus = 0
							ORDER BY maksupositio.tunnus
							LIMIT 1";
				$rahres = pupe_query($query);
				$posrow = mysql_fetch_array($rahres);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$row[jaksotettu]'
							ORDER BY tunnus
							LIMIT 1";
				$rahres = pupe_query($query);
				$laskurow = mysql_fetch_array($rahres);

				$query = "	SELECT group_concat(tunnus SEPARATOR '<br>') tunnukset
							FROM lasku
							WHERE yhtio 	= '$kukarow[yhtio]'
							and jaksotettu  = '$row[jaksotettu]'
							and tila in ('L','N','R')";
				$rahres = pupe_query($query);
				$laskurow2 = mysql_fetch_array($rahres);

				echo "<tr>";
				echo "<td valign='top'>$laskurow2[tunnukset]</td>";
				echo "<td valign='top'>$row[nimi]</td>";
				echo "<td valign='top'>$row[laskutettu_kpl] / $row[yhteensa_kpl]</td>";
				echo "	<td valign='top' align='right'>$row[laskuttamatta]</td>
						<td valign='top' align='right'>$row[laskutettu]</td>
						<td valign='top' align='right'>$row[yhteensa]</td>
						<td>
						<table>
						<tr><td>".t("Osuus").":</td><td>$posrow[osuus]%</td></tr>
						<tr><td>".t("Summa").":</td><td>$posrow[summa] $laskurow[valkoodi]</td></tr>
						<tr><td>".t("Lisätiedot").":</td><td>$posrow[lisatiedot]</td></tr>
						<tr><td>".t("Ohje").":</td><td>$posrow[ohje]</td></tr>
						</table>
						</td>";

				// loppulaskutetaan maksusopimus
				if ($row["yhteensa_kpl"] - $row["laskutettu_kpl"] <= 1) {
					// tarkastetaan onko kaikki jo toimitettu ja tämä on good to go
					$query = "	SELECT
								sum(if (lasku.tila='L' and lasku.alatila IN ('J','X'),1,0)) tilaok,
								sum(if (tilausrivi.toimitettu='',1,0)) toimittamatta,
								count(*) toimituksia
								FROM lasku
								JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.jaksotettu=lasku.jaksotettu and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'P')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.jaksotettu = '$row[jaksotettu]' and tila in ('L','N','R')
								GROUP BY lasku.jaksotettu";
					$tarkres = pupe_query($query);
					$tarkrow = mysql_fetch_array($tarkres);

					if ($tarkrow["tilaok"] <> $tarkrow["toimituksia"] or $tarkrow["toimittamatta"] > 0) {
						echo "<td class='back'><font class='error'>".t("Ei valmis")."</font></td>";
					}
					else {
						$msg = t("Oletko varma, että haluat LOPPULASKUTTAA tilauksen")." $row[jaksotettu]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: ".t_tunnus_avainsanat($posrow, "teksti", "MAKSUEHTOKV");

						echo "	<td class='back'>
								<form method='post' onSubmit='return verify(\"$msg\");'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
								<input type='hidden' name='tee' value='loppulaskuta'>
								<input type='submit' name = 'submit' value='".t("Loppulaskuta")."'>
								</form>
								</td>";
					}
				}
				elseif ($row["tekematta_kpl"] > 1) {
					// muuten tämä on vain ennakkolaskutusta
					$msg = t("Oletko varma, että haluat tehdä ennakkolaskun tilaukselle").": $row[jaksotettu]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: ".t_tunnus_avainsanat($posrow, "teksti", "MAKSUEHTOKV");

					echo "<td class='back'>";

					echo "<form method='post' name='case' enctype='multipart/form-data'  autocomplete='off' onSubmit = 'return verify(\"$msg\");'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
							<input type='hidden' name='tee' value='ennakkolaskuta'>
							<input type='submit' name = 'submit' value='".t("Laskuta")."'>
							</form><br>";

					// muuten tämä on vain ennakkolaskutusta
					$msg = t("Oletko varma, että haluat tehdä kaikki ennakkolaskut tilaukselle").": $row[jaksotettu]";

					echo "<form method='post' name='case' enctype='multipart/form-data'  autocomplete='off' onSubmit = 'return verify(\"$msg\");'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
							<input type='hidden' name='tee' value='ennakkolaskuta_kaikki'>
							<input type='submit' name = 'submit' value='".t("Laskuta kaikki ennakot")."'>
							</form>";

					echo "</td>";
				}
				else {
					echo "<td class='back'><font class='error'>".t("Ei valmis")."</font></td>";
				}

				echo "</tr>";
			}

			echo "</tbody>";
			echo "</table>";
		}

		require("inc/footer.inc");
	}
?>