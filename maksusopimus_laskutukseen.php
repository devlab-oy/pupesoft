<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "maksusopimus_laskutukseen.php") !== FALSE) {
		require("inc/parametrit.inc");
	}

	if (!function_exists("ennakkolaskuta")) {
		function ennakkolaskuta($tunnus) {
			global $kukarow, $yhtiorow;

			///* Etsitään laskun kaikki tiedot jolle maksusopimus on tehty *///
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$tunnus' and tila in ('L','N') and alatila != 'X'";
			$stresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($stresult) == 0) {
				echo "Otsikkoa '$tunnus' ei löytynyt, tai se on väärässä tilassa.";
				return 0;
			}
			$laskurow=mysql_fetch_array ($stresult);
			if ($debug==1) echo t("Perusotsikko löytyi")." $laskurow[nimi]<br>";


			// Onko supimuksella vielä jotain ennakkolaskutettavaa
			$query = "	SELECT yhtio
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = 0";
			$posres = mysql_query($query) or pupe_error($query);

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
			$posres = mysql_query($query) or pupe_error($query);
			$posrow = mysql_fetch_array($posres);

			if ($debug==1) echo t("Löydettiin maksupositio")." $posrow[tunnus], $posrow[osuus] %, $posrow[maksuehto]<br>";

			if ($posrow["summa"] <= 0 or $posrow["maksuehto"] == 0 or (int) $posrow["tunnus"] == 0) {
				echo $query." ".t("VIRHE: laskutusposition summa on nolla tai sen alle. Korjaa tämä!")."<br>";
				return 0;
			}

			// Tilausrivin kommentti-kenttään menevä kommentti
			$query = "	SELECT
						sum(if(uusiotunnus > 0, 1, 0)) laskutettu,
						count(*) yhteensa
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'";
			$abures = mysql_query($query) or pupe_error($query);
			$aburow = mysql_fetch_array($abures);

			$lahteva_lasku = ($aburow["laskutettu"] + 1)."/".$aburow["yhteensa"];

			// tehdään vanhasta laskusta 1:1 kopio...
			$query = "insert into lasku set ";
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
					$query .= "tilaustyyppi='L',";
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
						mysql_field_name($stresult,$i)=='erpcm' or
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
			$stresult = mysql_query($query) or pupe_error($query);
			$id = mysql_insert_id();

			if ($debug==1) echo t("Perustin laskun")." $laskurow[nimi] $id<br>";

			$query = "	SELECT nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($tresult);
			$nimitys = $trow["nimitys"];

			//Lasketaan maksusopimuksen arvo verokannoittain jotta voidaan laskuttaa ennakot oikeissa alveissa
			// ja lisätään ennakkolaskutusrivi laskulle, vain jaksotetut rivit!
			$query = "	SELECT
						sum(if(tilausrivi.jaksotettu=lasku.jaksotettu, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)) jaksotettavaa
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.jaksotettu=lasku.jaksotettu
						WHERE lasku.yhtio 		= '$kukarow[yhtio]'
						and lasku.jaksotettu  	= '$tunnus'
						GROUP by lasku.jaksotettu";
			$result = mysql_query($query) or pupe_error($query);
			$sumrow = mysql_fetch_array($result);

			$query = "	SELECT
						sum(if(tilausrivi.jaksotettu=lasku.jaksotettu, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)) summa,
						if(tilausrivi.alv>=500, tilausrivi.alv-500, tilausrivi.alv) alv
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.jaksotettu=lasku.jaksotettu
						WHERE lasku.yhtio 		= '$kukarow[yhtio]'
						and lasku.jaksotettu  	= '$tunnus'
						GROUP BY lasku.jaksotettu, alv";
			$sresult = mysql_query($query) or pupe_error($query);
			$tot = 0;

			if(mysql_num_rows($sresult) == 0) {
				echo "<font class = 'error'>".t("VIRHE: Ennakkolaskulla ei ole yhtään jaksotettua tilausriviä!")." $tunnus</font><br>";
				echo "<font class = 'message'>".t("Käy tekemässä ennakkolasku manuaalisesti. Ennakkolaskulle perustetun laskun tunnus on")." $id</font><br>";
				echo "<font class = 'message'>".t("Ennakkolaskutuksen tuotenumero on")." $yhtiorow[ennakkomaksu_tuotenumero]</font><br><br>";

				$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('0', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolasku")." $lahteva_lasku ".t("tilaukselle")." $tunnus ".t("Osuus")." $posrow[osuus]%')";
				$addtil = mysql_query($query) or pupe_error($query);

			}
			else {
				while($row = mysql_fetch_array($sresult)) {

					// $summa on verollinen tai veroton riippuen yhtiön myyntihinnoista
					$summa = $row["summa"]/$sumrow["jaksotettavaa"] * $posrow["summa"];

					$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values ('$summa', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolasku")." $lahteva_lasku ".t("tilaukselle")." $tunnus ".t("Osuus")." $posrow[osuus]%')";
					$addtil = mysql_query($query) or pupe_error($query);

					if ($debug==1) echo t("Lisättiin ennakkolaskuun rivi")." $summa $row[alv] otunnus $id<br>";

					$tot += $summa;
				}

				echo "<font class = 'message'>".t("Tehtiin ennakkolasku tilaukselle")." $tunnus ".t("tunnus").": $id ".t("osuus").": $posrow[osuus]% ".t("summa").": $tot</font><br>";
			}

			// Päivitetään positiolle tämän laskun tunnus
			$query = "update maksupositio set uusiotunnus='$id' where tunnus='$posrow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			// merkataan tässä vaiheessa luotu ennakkomaksu-tilaus toimitetuksi
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika=now(), kerattyaika=now()
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$id'";
			$result = mysql_query($query) or pupe_error($query);

			// ja päivitetään luotu ennakkomaksu-tilaus laskutusjonoon
			$query = "	update lasku
						set tila='L', alatila='D'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$id'";
			$result = mysql_query($query) or pupe_error($query);

			return $id;
		}
	}

	if (!function_exists("loppulaskuta")) {
		function loppulaskuta($tunnus) {
			global $kukarow, $yhtiorow;

			///* Tutkitaan alkuperäisen tilauksen tilaa *///
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$tunnus' and tila = 'L' and alatila = 'J'";
			$stresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($stresult) == 0) {
				echo "<font class='error'>Otsikkoa '$tunnus' ei löytynyt, tai se on väärässä tilassa.</font><br><br>";
				return 0;
			}

			// tarkistetaan että meillä on jotain järkevää laskutettavaa
			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = ''
						ORDER BY tunnus
						LIMIT 1";
			$posres = mysql_query($query) or pupe_error($query);
			$posrow = mysql_fetch_array($posres);

			if ($debug==1) echo t("Löydettiin maksupositio")." $posrow[tunnus], $posrow[osuus] %, $posrow[maksuehto]<br>";

			if ($posrow["summa"] <= 0 or $posrow["maksuehto"] == 0 or (int) $posrow["tunnus"] == 0) {
				echo "<font class='error'>".t("VIRHE: laskutusposition summa on nolla tai sen alle. Korjaa tämä!")."</font><br><br>";
				return 0;
			}

			// Tilausrivin kommentti-kenttään menevä kommentti
			$query = "	SELECT
						sum(if(uusiotunnus > 0, 1, 0)) laskutettu,
						count(*) yhteensa
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tunnus'";
			$abures = mysql_query($query) or pupe_error($query);
			$aburow = mysql_fetch_array($abures);

			$lahteva_lasku = ($aburow["laskutettu"] + 1)."/".$aburow["yhteensa"];

			// varmistetaan että laskutus näyttäisi olevan OK!!
			if($aburow["yhteensa"] - $aburow["laskutettu"] != 1) {
				echo "<font class='error'>".t("VIRHE: Koitetaan loppulaskuttaa mutta positioita on jäljellä enemmän kuin yksi!")."</font><br><br>";
				return 0;
			}

			echo "<font class = 'message'>".t("Loppulaskutetaan tilaus")." $tunnus<br></font><br>";

			$query = "	SELECT nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($tresult);
			$nimitys = $trow["nimitys"];

			//Lasketaan paljonko ollaan jo laskutettu ja millä verokannoilla
			$query = "	SELECT round(sum(rivihinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) laskutettu, tilausrivi.alv
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and kpl <> 0 and uusiotunnus > 0
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu = '".($tunnus*-1)."'
						GROUP BY alv";
			$sresult = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($sresult)) {
				$query  = "	insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika)
							values  ('$row[laskutettu]', 'N', '-1', '-1', '$tunnus', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolaskutuksen hyvitys")." $lahteva_lasku', '$kukarow[kuka]', now(), '$kukarow[kuka]', now())";
				$addtil = mysql_query($query) or pupe_error($query);

				if ($debug==1) echo t("Loppulaskuun lisättiin ennakkolaskun hyvitys")." -$row[laskutettu] alv $row[alv]% otunnus $vimppa<br>";
			}

			// Päivitetään positiolle laskutustunnus
			$query = "update maksupositio set uusiotunnus='$tunnus' where tunnus = '$posrow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			// Alkuperäinen tilaus/tilaukset menee laskutukseen
			$query = "	UPDATE lasku
						SET maksuehto = '$posrow[maksuehto]', clearing = 'loppulasku', ketjutus = 'o', alatila = 'D'
						WHERE yhtio 	= '$kukarow[yhtio]'
						and jaksotettu 	= '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	SELECT group_concat(distinct tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu 	= '$tunnus'";
			$lres = mysql_query($query) or pupe_error($query);
			$lrow = mysql_fetch_array($lres);

			return $lrow["tunnukset"];
		}
	}

	//Käyttöliittymä
	if (strpos($_SERVER['SCRIPT_NAME'], "maksusopimus_laskutukseen.php") !== FALSE) {
		$query = "	SELECT nimitys
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
		$tresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($tresult) == 0) die(t("VIRHE: Yhtiöllä EI OLE ennakkolaskutustuotetta, sopimuslaskutusta ei voida toteuttaa!"));
		echo "<font class='head'>".t("Sopimuslaskutus").":</font><hr><br>";


		if($tee == "ennakkolaskuta") {
			ennakkolaskuta($tunnus);
			$tee = "";
		}

		if($tee == "ennakkolaskuta_kaikki") {
			// seuraava positio on tämä siis
			$query = "	SELECT count(*)-1 as ennakko_kpl
						FROM maksupositio
						JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
						WHERE maksupositio.yhtio ='$kukarow[yhtio]'
						and otunnus = '$tunnus'
						and uusiotunnus = 0
						ORDER BY maksupositio.tunnus";
			$rahres = mysql_query($query) or pupe_error($query);
			$posrow = mysql_fetch_array($rahres);

			for($ie=0; $ie < $posrow["ennakko_kpl"]; $ie++) {
				//tehdään ennakklasku
				ennakkolaskuta($tunnus);
			}
			$tee = "";
		}


		if($tee == "loppulaskuta") {
			loppulaskuta($tunnus);
			$tee = "";
		}

		if ($tee == "sulje") {
			echo "ei osata viel, sorry";
			$tee = "";
		}


		if($tee == "") {

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(msg){
							return confirm(msg);
						}
					</SCRIPT>";

			$query = "	SELECT
						lasku.jaksotettu jaksotettu,
						concat_ws(' ',lasku.nimi, lasku.nimitark) nimi,
						sum(if(maksupositio.uusiotunnus > 0, 1,0)) laskutettu_kpl,
						count(*) yhteensa_kpl,
						sum(if(maksupositio.uusiotunnus = 0, maksupositio.summa,0)) laskuttamatta,
						sum(if(maksupositio.uusiotunnus > 0, maksupositio.summa,0)) laskutettu,
						sum(maksupositio.summa) yhteensa
						FROM lasku
						JOIN maksupositio ON maksupositio.yhtio = lasku.yhtio and maksupositio.otunnus = lasku.tunnus
						JOIN maksuehto ON maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.jaksotettu != ''
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu > 0
						GROUP BY jaksotettu, nimi
						ORDER BY jaksotettu desc";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";

			echo "	<th>".t("Tilaus")."</th>
					<th>".t("Asiakas")."</th>
					<th>".t("Erä")."</th>
					<th>".t("Laskuttamatta")."</th>
					<th>".t("Laskutettu")."</th>
					<th>".t("Yhteensä")."</th>
					<th>".t("Seuraava positio")."</th>";
			echo "</tr>";

			while($row = mysql_fetch_array($result)) {

				// seuraava positio on tämä siis
				$query = "	SELECT maksupositio.*, maksuehto.teksti
							FROM maksupositio
							JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
							WHERE maksupositio.yhtio = '$kukarow[yhtio]'
							and otunnus = '$row[jaksotettu]'
							and uusiotunnus = 0
							ORDER BY maksupositio.tunnus
							LIMIT 1";
				$rahres = mysql_query($query) or pupe_error($query);
				$posrow = mysql_fetch_array($rahres);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$row[jaksotettu]'
							ORDER BY tunnus
							LIMIT 1";
				$rahres = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($rahres);

				$query = "	SELECT group_concat(tunnus SEPARATOR '<br>') tunnukset
							FROM lasku
							WHERE yhtio 	= '$kukarow[yhtio]'
							and jaksotettu  = '$row[jaksotettu]'";
				$rahres = mysql_query($query) or pupe_error($query);
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
						<tr><td>Osuus:</td><td>$posrow[osuus]%</td></tr>
						<tr><td>Summa:</td><td>$posrow[summa] $laskurow[valkoodi]</td></tr>
						<tr><td>Lisätiedot:</td><td>$posrow[lisatiedot]</td></tr>
						<tr><td>Ohje:</td><td>$posrow[ohje]</td></tr>
						</table>";

				// loppulaskutetaan maksusopimus
				if($row["yhteensa_kpl"] - $row["laskutettu_kpl"] == 1) {
					// tarkastetaan onko kaikki jo toimitettu ja tämä on good to go
					$query = "	SELECT
								sum(if(lasku.tila='L' and lasku.alatila IN ('J','X'),1,0)) tilaok,
								sum(if(tilausrivi.toimitettu='',1,0)) toimittamatta,
								count(*) toimituksia
								FROM lasku
								JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.jaksotettu=lasku.jaksotettu and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'P'
								WHERE lasku.yhtio 		= '$kukarow[yhtio]'
								and lasku.jaksotettu 	= '$row[jaksotettu]'
								GROUP BY lasku.jaksotettu";
					$tarkres = mysql_query($query) or pupe_error($query);
					$tarkrow = mysql_fetch_array($tarkres);

					if($tarkrow["tilaok"] <> $tarkrow["toimituksia"] or $tarkrow["toimittamatta"] > 0) {
						echo "<td class='back'>Ei valmis</td>";
					}
					else {
						$msg = t("Oletko varma, että haluat LOPPULASKUTTAA tilauksen")." $row[jaksotettu]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: $posrow[teksti]";

						echo "	<form method='post' action='$PHP_SELF' onSubmit='return verify(\"$msg\");'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
								<input type='hidden' name='tee' value='loppulaskuta'>
								<td class='back'><input type='submit' name = 'submit' value='".t("Laskuta")."'></td>
								</form>";
					}
					echo "</tr>";

				}
				elseif($row["yhteensa_kpl"] - $row["laskutettu_kpl"] == 0) {
					// suljetaan projektia
					$msg = t("Oletko varma, että haluat sulkea projektin")." $row[tunnus]\\n";

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF' onSubmit='return verify(\"$msg\");'>
							<input type='hidden' name = 'toim' value='$toim'>
							<input type='hidden' name = 'tunnus' value='$row[jaksotettu]'>
							<input type='hidden' name = 'tee' value='sulje'>
							<input type='submit' name = 'submit' value='".t("Sulje projekti")."'>
							</form></td>";

					echo "</tr>";

				}
				else {
					// muuten tämä on vain ennakkolaskutusta
					$msg = t("Oletko varma, että haluat tehdä ennakkolaskun tilaukselle").": $row[jaksotettu]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: $posrow[teksti]";

					echo "<td class='back'><form method='post' name='case' action='$PHP_SELF' enctype='multipart/form-data'  autocomplete='off' onSubmit = 'return verify(\"$msg\");'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
							<input type='hidden' name='tee' value='ennakkolaskuta'>
							<input type='submit' name = 'submit' value='".t("Laskuta")."'>
							</form></td>";

					// muuten tämä on vain ennakkolaskutusta
					$msg = t("Oletko varma, että haluat tehdä kaikki ennakkolaskut tilaukselle").": $row[jaksotettu]";

					echo "<td class='back'><form method='post' name='case' action='$PHP_SELF' enctype='multipart/form-data'  autocomplete='off' onSubmit = 'return verify(\"$msg\");'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[jaksotettu]'>
							<input type='hidden' name='tee' value='ennakkolaskuta_kaikki'>
							<input type='submit' name = 'submit' value='".t("Laskuta kaikki ennakot")."'>
							</form></td>";


					echo "</tr>";
				}
			}
			echo "</table>";
		}

		require("inc/footer.inc");
	}
?>
