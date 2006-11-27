<?php
	require ("../inc/parametrit.inc");

	if ($toim == "") {
		echo "<font class='head'>".t("Toimita ja laskuta tilaus").":</font><hr>";

		$alatilat = " 	and lasku.alatila in ('B','C','D') ";
		$vientilisa = " and lasku.vienti = '' ";
		$muutlisa = "	and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
						and tilausrivi.varattu  != 0";
	}
	elseif ($toim == "VAINLASKUTA") {
		echo "<font class='head'>".t("Laskuta tilaus").":</font><hr>";

		$alatilat = " 	and lasku.alatila='D' ";
		$vientilisa = " and lasku.vienti = '' ";
		$muutlisa = "	and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
						and tilausrivi.varattu  != 0";
	}
	else {
		echo "<font class='head'>".t("Tulosta vientilaskuja").":</font><hr>";

		$alatilat = " 	and lasku.alatila in ('E') ";
		$vientilisa = " and lasku.vienti != '' ";
 		$muutlisa = "	and tilausrivi.laskutettu = '' ";
	}


	if ($tee == 'NAYTATILAUS') {
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}

	if ($tee == 'MAKSUEHTO') {
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}


	if ($tee=='TOIMITA') {

		//k‰yd‰‰n kaikki ruksatut tilaukset l‰pi
		if (sizeof($tunnus) != 0) {

			$laskutettavat = "";

			foreach ($tunnus as $tun) {
				$laskutettavat .= "$tun,";
			}
			$laskutettavat = substr($laskutettavat,0,-1); // vika pilkku pois
		}

		//tarkistetaan ekaks ettei yksik‰‰n tilauksista ole jo toimitettu/laskutettu
		$query = "	SELECT yhtio
		            FROM tilausrivi
					WHERE otunnus in ($laskutettavat)
					and yhtio = '$kukarow[yhtio]'
					and laskutettu != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			// merkataan t‰ss‰ vaiheessa toimittamattomat rivi toimitetuiksi
			$query = "	UPDATE tilausrivi
						SET toimitettu='$kukarow[kuka]', toimitettuaika=now()
						WHERE otunnus in ($laskutettavat)
						and var not in ('P','J')
						and yhtio = '$kukarow[yhtio]'
						and keratty != ''
						and toimitettu = ''";
			$result = mysql_query($query) or pupe_error($query);

			//jap‰ivitet‰‰n laskujen otsikot laskutusjonoon
			$query = "	update lasku
						set alatila='D'
						where tunnus in ($laskutettavat)
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$tee 			= "TARKISTA";
			$laskutakaikki 	= "KYLLA";
			$silent		 	= "VIENTI";

			require("verkkolasku.php");

			//k‰yd‰‰n kaikki ruksatut maksusopimukset l‰pi
			if (sizeof($positiotunnus) != 0) {

				require("../maksusopimus_laskutukseen.php");

				foreach ($positiotunnus as $postun) {
					$query = "	SELECT count(*)-1 as ennakko_kpl
								FROM maksupositio
								JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
								WHERE maksupositio.yhtio ='$kukarow[yhtio]'
								and otunnus = '$postun'
								and uusiotunnus = 0
								ORDER BY maksupositio.tunnus";
					$rahres = mysql_query($query) or pupe_error($query);
					$posrow = mysql_fetch_array($rahres);

					for($ie=0; $ie < $posrow["ennakko_kpl"]; $ie++) {
						$laskutettavat = 0;
						echo "<br>";

						// Tehd‰‰n ennakkolasku
						$laskutettavat = ennakkolaskuta($postun);

						if ($laskutettavat > 0) {
							$tee 			= "TARKISTA";
							$laskutakaikki 	= "KYLLA";
							$silent		 	= "VIENTI";

							require("verkkolasku.php");
						}
					}


					$laskutettavat = 0;
					echo "<br>";

					// Ja loppulaskutus samaan syssyyn
					$laskutettavat = loppulaskuta($postun);

					if ($laskutettavat > 0) {
						$tee 			= "TARKISTA";
						$laskutakaikki 	= "KYLLA";
						$silent		 	= "VIENTI";

						require("verkkolasku.php");
					}
				}
			}

			echo "<br><br>";
        }
        else {
            echo t("VIRHE: Jokin rivi/lasku oli jo toimitettu tai laskutettu! Ei voida jatkaa!");
            echo "<br><br>";
        }
		$laskutettavat	= "";
		$otunnus		= "";
		$tee	 		= "";
	}

	if ($tee == "VALITSE") {

		$query = "	SELECT lasku.*,
					maksuehto.teksti meh,
					maksuehto.kassa_teksti mehka,
					maksuehto.itsetulostus,
					maksuehto.kateinen
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and lasku.tunnus = tilausrivi.otunnus
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and lasku.tunnus in ($tunnukset)
					$alatilat
					$vientilisa
					$muutlisa
					GROUP BY lasku.tunnus
					ORDER BY tunnus";
		$res   = mysql_query($query) or pupe_error($query);

 		// Tehd‰‰n valinta
		if (mysql_num_rows($res) > 0) {

			echo "<table>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='TOIMITA'>";

			//otetaan eka rivi ja k‰ytet‰‰n sit‰ otsikoiden tulostamiseen
			$ekarow = mysql_fetch_array($res);

			if ($ekarow["chn"] == '100') $ekarow["chn"] = t("Paperilasku");
			if ($ekarow["chn"] == '010') $ekarow["chn"] = t("eInvoice");
			if ($ekarow["chn"] == '020') $ekarow["chn"] = t("Vienti eInvoice");
			if ($ekarow["chn"] == '111') $ekarow["chn"] = t("Elma EDI-inhouse");

			echo "<tr><th>".t("Laskutus:")."</th><th>".t("Nimi")."</th><th>".t("Osoite")."</th><th>".t("Postino")."</th><th>".t("Postitp")."</th><th>".t("Maa")."</th><tr>";
			echo "<tr><td>$ekarow[ytunnus]</td><td>$ekarow[nimi]</td><td>$ekarow[osoite]</td><td>$ekarow[postino]</td><td>$ekarow[postitp]</td><td>$ekarow[maa]</td></tr>";
			echo "<tr><th>".t("Toimitus:")."</th><th>".t("Nimi")."</th><th>".t("Osoite")."</th><th>".t("Postino")."</th><th>".t("Postitp")."</th><th>".t("Maa")."</th><tr>";
			echo "<tr><td>$ekarow[chn]</td><td>$ekarow[toim_nimi]</td><td>$ekarow[toim_osoite]</td><td>$ekarow[toim_postino]</td><td>$ekarow[toim_postitp]</td><td>$ekarow[toim_maa]</td></tr>";
			echo "</table><br>";

			mysql_data_seek($res,0);

			echo t("Valitse toimitettavat tilaukset").":<br><table>";

			echo "<th>".t("Toimita")."</th>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<th>".t("Laadittu")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Maksuehto")."</th>";
			echo "<th>".t("Muokkaa tilausta")."</th>";
			echo "<th>".t("Laskuta kaikki positiot")."</th>";

			while ($row = mysql_fetch_array($res)) {
				$query = "	select sum(if(varattu>0,1,0)) veloitus, sum(if(varattu<0,1,0)) hyvitys, sum(if(hinta*varattu*(1-ale/100)=0 and var!='P' and var!='J',1,0)) nollarivi
							from tilausrivi
							where yhtio='$kukarow[yhtio]' and otunnus='$row[tunnus]'";
				$hyvre = mysql_query($query) or pupe_error($query);
				$hyvrow = mysql_fetch_array($hyvre);

				echo "<tr><td><input type='checkbox' name='tunnus[$row[tunnus]]' value='$row[tunnus]' checked></td>";

				echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&toim=$toim&tunnukset=$tunnukset&tunnus=$row[tunnus]'>$row[tunnus]</a></td>";
				echo "<td>$row[laatija]</td>";
				echo "<td>$row[luontiaika]</td>";

				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] == 0) {
					$teksti = "Veloitus";
				}
				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] > 0) {
					$teksti = "Veloitusta ja hyvityst‰";
				}
				if ($hyvrow["hyvitys"] > 0  and $hyvrow["veloitus"] == 0) {
					$teksti = "Hyvitys";
				}
				echo "<td>".t("$teksti")."</td>";
				echo "<td>$row[mehka] $row[meh]</td>";

				echo "<td><a href='tilaus_myynti.php?toim=PIKATILAUS&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$row[tunnus]'>".t("Pikatilaukseen")."</a></td>";

				if ($row["jaksotettu"] > 0) {
					echo "<td><input type='checkbox' name='positiotunnus[$row[tunnus]]' value='$row[tunnus]'></td>";
				}
				else {
					echo "<td>".t("Ei positioita")."</td>";
				}


				if ($hyvrow["nollarivi"] > 0) {
					echo "<td class='back'>&nbsp;<font class='error'>".t("Huom! Tilauksella on nollahintaisia rivej‰!")."</font></td>";
				}

				echo "</tr>";
			}
			echo "</table><br>";

			echo "<table>";

			///* Haetaan asiakkaan kieli *///
			$query = "	SELECT kieli
						FROM asiakas
						WHERE
						tunnus='$ekarow[liitostunnus]'
						AND yhtio ='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($result);

			if ($asrow["kieli"] != '') {
				$sel[$asrow["kieli"]] = "SELECTED";
			}
			elseif($toim == "VIENTI") {
				$sel["en"] = "SELECTED";
			}
			else {
				$sel[$yhtiorow["kieli"]] = "SELECTED";
			}

			echo "<tr><th>".t("Valitse kieli").":</th>";
			echo "<td><select name='kieli'>";
			echo "<option value='fi' $sel[fi]>".t("Suomi")."</option>";
			echo "<option value='se' $sel[se]>".t("Ruotsi")."</option>";
			echo "<option value='en' $sel[en]>".t("Englanti")."</option>";
			echo "<option value='de' $sel[de]>".t("Saksa")."</option>";
			echo "<option value='dk' $sel[dk]>".t("Tanska")."</option>";
			echo "</select></td></tr>";

			echo "<tr><th>".t("Valitse kirjoitin").":</th><td><select name='valittu_tulostin'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";


			//tulostetaan faili ja valitaan sopivat printterit
			if ($ekarow["varasto"] == '') {
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]'
							and printteri5 != ''
							order by alkuhyllyalue,alkuhyllynro
							limit 1";
			}
			else {
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$ekarow[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
			}
			$prires= mysql_query($query) or pupe_error($query);
			$prirow= mysql_fetch_array($prires);

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio = '$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			while ($kirrow = mysql_fetch_array($kirre)) {
				$sel = "";
				if (($kirrow["tunnus"] == $prirow["printteri5"] and $kukarow["kirjoitin"] == 0) or $kirow["tunnus"] == $kukarow["kirjoitin"]) {
					$sel = "SELECTED";
				}

				echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
			}

			echo "</select></td><td><input type='submit' value='".t("Laskuta")."'></form></td></tr>";
			echo "</table>";
		}
	}

	// meill‰ ei ole valittua tilausta
	if ($tee == "") {
		$formi	= "find";
		$kentta	= "etsi";

		// tehd‰‰n etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value=''>";
		echo t("Etsi asiakasta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		// GROUP BY pit‰‰‰ olla sama kun verkkolasku.php:ss‰ rivill‰†536
		$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
					lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					lasku.maksuehto,
					maksuehto.teksti meh,
					maksuehto.kassa_teksti mehka,
					group_concat(distinct lasku.tunnus) tunnukset,
					group_concat(distinct lasku.tunnus separator '<br>') tunnukset_ruudulle,
					count(distinct lasku.tunnus) tilauksia,
					count(tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and lasku.tunnus = tilausrivi.otunnus
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and chn	!= '999'
					$alatilat
					$vientilisa
					$muutlisa
					$haku
					GROUP BY lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
							lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
							lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
							lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn
					ORDER BY lasku.ytunnus, lasku.nimi";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";
			echo "<tr><th>".t("Tilaukset")."</th><th>".t("Asiakas")."</th><th>".t("Ytunnus")."</th><th>".t("Tilauksia")."</th><th>".t("Rivej‰")."</th><th>".t("Arvo")."</th><th>".t("Maksuehto")."</th><tr>";

			$arvoyhteensa = 0;
			$tilauksiayhteensa = 0;

			while ($tilrow = mysql_fetch_array($tilre)) {
				echo "	<tr>
						<td valign='top'>$tilrow[tunnukset_ruudulle]</td>
						<td valign='top'>$tilrow[ytunnus]</td>
						<td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td>
						<td valign='top'>$tilrow[tilauksia]</td>
						<td valign='top'>$tilrow[riveja]</td>
						<td valign='top' align='right'>$tilrow[arvo]</td>
						<td valign='top'>$tilrow[mehka] $tilrow[meh]</td>";

				echo "	<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='tee' value='VALITSE'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnukset' value='$tilrow[tunnukset]'>
						<td class='back' valign='top'><input type='submit' name='tila' value='".t("Valitse")."'></td>
						</tr></form>";

				$arvoyhteensa += $tilrow["arvo"];
				$tilauksiayhteensa += $tilrow["tilauksia"];

			}
			echo "</table>";

			if ($arvoyhteensa != 0) {
				echo "<br><table cellpadding='5'><tr>";
				echo "<th>".t("Tilausten arvo yhteens‰")." ($tilauksiayhteensa ".t("kpl")."): </th>";
				echo "<td>$arvoyhteensa $yhtiorow[valkoodi]</td>";
				echo "</tr></table>";
			}

		}
		else {
			echo "<font class='message'>".t("Yht‰‰n toimitettavaa ei lˆytynyt")."...</font>";
		}
	}

	require("../inc/footer.inc");
?>