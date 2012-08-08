<?php

	if (isset($_POST["tee"])) {
		if(isset($_POST["tee"]) and $_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (!isset($tee)) {
		$tee = "";
	}
	if (!isset($etsi)) {
		$etsi = "";
	}
	if (!isset($toim)) {
		$toim = "";
	}

	if ($tee == "lataa_tiedosto") {
		readfile("$pupe_root_polku/dataout/".basename($filenimi));
		exit;
	}

	if ($toim == "") {
		echo "<font class='head'>".t("Toimita ja laskuta tilaus").":</font><hr>";

		$alatilat = " 	and lasku.alatila in ('B','C','D') ";
		$vientilisa = " and lasku.vienti = '' ";
		$muutlisa = "	and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
						and tilausrivi.varattu  != 0";
	}
	elseif ($toim == "VAINLASKUTA") {
		echo "<font class='head'>".t("Laskuta tilaus").":</font><hr>";

		$alatilat = " 	and (lasku.alatila = 'D' or (lasku.alatila = 'C' and lasku.eilahetetta != ''))";
		$vientilisa = " and lasku.vienti = '' ";
		$muutlisa = "	and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
						and tilausrivi.varattu  != 0";
	}
	elseif ($toim == "VAINOMATLASKUTA") {
		echo "<font class='head'>".t("Laskuta tilaus").":</font><hr>";

		$alatilat = " 	and (lasku.alatila = 'D' or (lasku.alatila = 'C' and lasku.eilahetetta != ''))";
		$vientilisa = " and lasku.vienti = '' and (lasku.laatija = '$kukarow[kuka]' or lasku.myyja = '$kukarow[tunnus]')";
		$muutlisa = "	and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
						and tilausrivi.varattu  != 0";
	}
	elseif ($toim == "VIENTI") {
		echo "<font class='head'>".t("Tulosta vientilaskuja").":</font><hr>";

		$alatilat = " 	and lasku.alatila in ('E','D') ";
		$vientilisa = " and lasku.vienti != '' ";
 		$muutlisa = "	and tilausrivi.laskutettu = '' ";
	}
	else {
		exit;
	}

	if ($tee == 'NAYTATILAUS') {
		require ("raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}

	if ($tee == 'MAKSUEHTO') {
		require ("raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}

	$query_ale_lisa = generoi_alekentta('M');

	if ($tee == 'TOIMITA' and isset($maksutapa) and $maksutapa == 'seka') {

		echo "<table><form name='laskuri' method='post'>";

		//k‰yd‰‰n kaikki ruksatut tilaukset l‰pi
		if (count($tunnus) > 0) {
			$laskutettavat = "";
			foreach ($tunnus as $tun) {
				echo "<input type='hidden' name='tunnus[]' value='$tun'>";
				$laskutettavat .= "'$tun',";
			}
			$laskutettavat = substr($laskutettavat,0,-1); // vika pilkku pois
		}

		$query_rivi = "	SELECT lasku.valkoodi, lasku.maksuehto, lasku.hinta,
						sum(round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},$yhtiorow[hintapyoristys])) loppusumma
						FROM tilausrivi
						JOIN lasku ON (lasku.yhtio=tilausrivi.yhtio AND lasku.tunnus=tilausrivi.otunnus)
						WHERE var != 'J'
						and otunnus in ($laskutettavat)
						and tilausrivi.yhtio = '$kukarow[yhtio]'
						GROUP BY 1,2,3";
		$result = pupe_query($query_rivi);
		$laskurow = mysql_fetch_array($result);

		$query = "	SELECT laskunsummapyoristys
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]'
					and yhtio='$kukarow[yhtio]'";
		$asres = pupe_query($query);
		$asrow = mysql_fetch_array($asres);

		$summa = $laskurow["loppusumma"];

		//K‰sin syˆtetty summa johon lasku pyˆristet‰‰n
		if (abs($laskurow["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
			$summa = sprintf("%.2f",$laskurow["hinta"]);
		}

		//Jos laskun loppusumma pyˆristet‰‰n l‰himp‰‰n tasalukuun
		if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
			$summa = sprintf("%.2f",round($summa ,0));
		}

		$loppusumma = $summa;
		$valkoodi = $laskurow["valkoodi"];

		echo "<input type='hidden' name='tee' value='TOIMITA'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='kassalipas' value='$kassalipas'>";
		echo "<input type='hidden' name='vaihdakateista' value='KYLLA'>";
		echo "<input type='hidden' name='maksutapa' value='$laskurow[maksuehto]'>";

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					function update_summa(rivihinta) {

						kateinen = Number(document.getElementById('kateismaksu').value.replace(\",\",\".\"));
						pankki = Number(document.getElementById('pankkikortti').value.replace(\",\",\".\"));
						luotto = Number(document.getElementById('luottokortti').value.replace(\",\",\".\"));

						summa = rivihinta - (kateinen + pankki + luotto);

						summa = Math.round(summa*100)/100;

						if (summa == 0 && (document.getElementById('kateismaksu').value != '' || document.getElementById('pankkikortti').value != '' || document.getElementById('luottokortti').value != '')) {
							summa = 0.00;
							document.getElementById('hyvaksy_nappi').disabled = false;
						} else {
							document.getElementById('hyvaksy_nappi').disabled = true;
						}

						document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>';
					}
				-->
				</script>";

		echo "<tr>";
		echo "<th>".t("Laskun loppusumma")."</th>";
		echo "<td align='right'>$loppusumma</td>";
		echo "<td>$valkoodi</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>".t("K‰teisell‰")."</td>";
		echo "<td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$loppusumma\");'></td>";
		echo "<td>$valkoodi</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>".t("Pankkikortilla")."</td>";
		echo "<td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$loppusumma\");'></td>";
		echo "<td>$valkoodi</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>".t("Luottokortilla")."</td>";
		echo "<td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$loppusumma\");'></td>";
		echo "<td>$valkoodi</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Erotus")."</th>";
		echo "<td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td>";
		echo "<td>$valkoodi</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyv‰ksy")."' disabled></td>";
		echo "</tr>";

		echo "</form><br><br>";

		$formi = "laskuri";
		$kentta = "kateismaksu";

		exit;
	}

	if ($tee == 'TOIMITA') {

		//k‰yd‰‰n kaikki ruksatut tilaukset l‰pi
		if (count($tunnus) != 0) {

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
					and yhtio 		= '$kukarow[yhtio]'
					and laskutettu != ''
					and tyyppi		= 'L'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			// merkataan t‰ss‰ vaiheessa toimittamattomat rivi toimitetuiksi
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE otunnus  in ($laskutettavat)
						and var not in ('P','J')
						and yhtio 		= '$kukarow[yhtio]'
						and keratty    != ''
						and toimitettu  = ''
						and tyyppi      = 'L'";
			$result = pupe_query($query);

			if (isset($vaihdakateista) and $vaihdakateista == "KYLLA") {
				$katlisa = ", kassalipas = '$kassalipas', maksuehto = '$maksutapa'";
			}
			else {
				$katlisa = "";
			}

			//ja p‰ivitet‰‰n laskujen otsikot laskutusjonoon
			$query = "	UPDATE lasku
						set alatila = 'D'
						$katlisa
						where tunnus in ($laskutettavat)
						and yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			$tee 			= "TARKISTA";
			$laskutakaikki 	= "KYLLA";
			$silent		 	= "VIENTI";

			require("verkkolasku.php");

			// K‰yd‰‰n kaikki ruksatut maksusopimukset l‰pi
			if (isset($positiotunnus) and count($positiotunnus) > 0) {

				require("../maksusopimus_laskutukseen.php");

				foreach ($positiotunnus as $postun) {
					$query = "	SELECT count(*)-1 as ennakko_kpl
								FROM maksupositio
								JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
								WHERE maksupositio.yhtio 	 = '$kukarow[yhtio]'
								and maksupositio.otunnus 	 = '$postun'
								and maksupositio.uusiotunnus = 0
								ORDER BY maksupositio.tunnus";
					$rahres = pupe_query($query);
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

					// Katsotaan ennakkolaskujen tiloja ja tutkitaan voidaanko tehd‰ loppulaskutus
					$query = "	SELECT
								sum(if(maksupositio.uusiotunnus > 0 and uusiolasku.tila='L' and uusiolasku.alatila='X', 1, 0)) laskutettu_kpl,
								count(*) yhteensa_kpl,
								sum(if(maksupositio.uusiotunnus = 0 or (maksupositio.uusiotunnus > 0 and uusiolasku.alatila!='X'), 1, 0)) laskuttamatta
								FROM lasku
								JOIN maksupositio ON maksupositio.yhtio = lasku.yhtio and maksupositio.otunnus = lasku.tunnus
								JOIN maksuehto ON maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.jaksotettu != ''
								LEFT JOIN lasku uusiolasku ON maksupositio.yhtio = uusiolasku.yhtio and maksupositio.uusiotunnus=uusiolasku.tunnus
								WHERE lasku.yhtio 	 = '$kukarow[yhtio]'
								and lasku.jaksotettu = '$postun'";
					$postarkresult = pupe_query($query);
					$postarkrow = mysql_fetch_array($postarkresult);

					if($postarkrow["yhteensa_kpl"] - $postarkrow["laskutettu_kpl"] == 1) {
						$laskutettavat = 0;
						echo "<br>";

						// Ja loppulaskutus samaan syssyyn
						$laskutettavat = loppulaskuta($postun);

						if ($laskutettavat != "" and $laskutettavat != 0) {
							$tee 			= "TARKISTA";
							$laskutakaikki 	= "KYLLA";
							$silent		 	= "VIENTI";

							require("verkkolasku.php");
						}
					}
					elseif($postarkrow["laskuttamatta"] > 0) {
						echo t("Jokin ennakkolaskuista on laskuttamatta! Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $postun<br>";
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
		$tee	 		= "";
	}

	if ($tee == "VALITSE") {

		$query = "	SELECT
					if(lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
					lasku.tunnus,
					lasku.luontiaika,
					lasku.chn,
					lasku.ytunnus,
					lasku.nimi,
					lasku.osoite,
					lasku.postino,
					lasku.postitp,
					lasku.maa,
					lasku.toim_nimi,
					lasku.toim_osoite,
					lasku.toim_postino,
					lasku.toim_postitp,
					lasku.toim_maa,
					lasku.laskutusvkopv,
					lasku.rahtivapaa,
					lasku.toimitustapa,
					laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
					maksuehto.teksti meh,
					maksuehto.itsetulostus,
					maksuehto.kateinen,
					ifnull(kuka.nimi, lasku.laatija) kukanimi,
					lasku.valkoodi,
					lasku.liitostunnus,
					lasku.tila,
					lasku.vienti,
					lasku.alv,
					lasku.kohdistettu,
					lasku.jaksotettu,
					lasku.verkkotunnus,
					lasku.erikoisale,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
					FROM lasku use index (tila_index)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}' AND laskun_lisatiedot.laskutus_nimi != '' AND laskun_lisatiedot.otunnus = lasku.tunnus AND CONCAT(laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa) != CONCAT(lasku.nimi, lasku.osoite, lasku.postino, lasku.postitp, lasku.maa))
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi='L'
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
					LEFT JOIN kuka on kuka.yhtio = lasku.yhtio and kuka.kuka = lasku.laatija
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and lasku.tunnus in ($tunnukset)
					$alatilat
					$vientilisa
					$muutlisa
					GROUP BY ketjutuskentta, lasku.tunnus,lasku.luontiaika,lasku.chn,lasku.ytunnus,lasku.nimi,lasku.osoite,lasku.postino,lasku.postitp,lasku.maa,lasku.toim_nimi,lasku.toim_osoite,lasku.toim_postino,lasku.toim_postitp,lasku.toim_maa,lasku.laskutusvkopv,lasku.rahtivapaa,lasku.toimitustapa,
					laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
					maksuehto.teksti,maksuehto.itsetulostus,maksuehto.kateinen,kuka.nimi,lasku.valkoodi,lasku.liitostunnus,lasku.tila,lasku.vienti,lasku.alv,lasku.kohdistettu,lasku.jaksotettu,lasku.verkkotunnus,lasku.erikoisale
					ORDER BY ketjutuskentta, lasku.tunnus";
		$res = pupe_query($query);

		$kateinen = "";
		$maa = "";

 		// Tehd‰‰n valinta
		if (mysql_num_rows($res) > 0) {

			//p‰iv‰m‰‰r‰n tarkistus
			$tilalk = explode("-", $yhtiorow["tilikausi_alku"]);
			$tillop = explode("-", $yhtiorow["tilikausi_loppu"]);

			$tilalkpp = $tilalk[2];
			$tilalkkk = $tilalk[1]-1;
			$tilalkvv = $tilalk[0];

			$tilloppp = $tillop[2];
			$tillopkk = $tillop[1]-1;
			$tillopvv = $tillop[0];

			$tanaanpp = date("d");
			$tanaankk = date("m")-1;
			$tanaanvv = date("Y");

			echo "<form method='post' name='lasku' onSubmit = 'return verify()'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='TOIMITA'>";
			echo "<table>";

			//otetaan eka rivi ja k‰ytet‰‰n sit‰ otsikoiden tulostamiseen
			$ekarow = mysql_fetch_assoc($res);

			$toimitusselite = "";

			if ($ekarow["chn"] == '100') $toimitusselite = t("Paperilasku");
			if ($ekarow["chn"] == '010') $toimitusselite = t("eInvoice");
			if ($ekarow["chn"] == '020') $toimitusselite = t("Vienti eInvoice");
			if ($ekarow["chn"] == '111') $toimitusselite = t("Elma EDI-inhouse");
			if ($ekarow["chn"] == '666') $toimitusselite = t("S‰hkˆpostiin");
			if ($ekarow["chn"] == '667') $toimitusselite = t("Sis‰inen");

			echo "<tr>";
			echo "<th>".t("Ostaja:")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Osoite")."</th";
			echo "><th>".t("Postino")."</th>";
			echo "<th>".t("Postitp")."</th>";
			echo "<th>".t("Maa")."</th>";
			echo "<tr>";
			echo "<tr>";
			echo "<td>$ekarow[ytunnus]</td>";
			echo "<td>$ekarow[nimi]</td>";
			echo "<td>$ekarow[osoite]</td>";
			echo "<td>$ekarow[postino]</td>";
			echo "<td>$ekarow[postitp]</td>";
			echo "<td>$ekarow[maa]</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<th>".t("Toimitusosoite:")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Osoite")."</th>";
			echo "<th>".t("Postino")."</th>";
			echo "<th>".t("Postitp")."</th>";
			echo "<th>".t("Maa")."</th><tr>";
			echo "<tr><td>$toimitusselite</td>";
			echo "<td>$ekarow[toim_nimi]</td>";
			echo "<td>$ekarow[toim_osoite]</td>";
			echo "<td>$ekarow[toim_postino]</td>";
			echo "<td>$ekarow[toim_postitp]</td>";
			echo "<td>$ekarow[toim_maa]</td></tr>";

			if (trim($ekarow['laskutus_nimi']) != '') {
				echo "<tr>";
				echo "<th>".t("Laskutusosoite:")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Osoite")."</th>";
				echo "<th>".t("Postino")."</th>";
				echo "<th>".t("Postitp")."</th>";
				echo "<th>".t("Maa")."</th>";
				echo "<tr>";
				echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>$ekarow[laskutus_nimi] $ekarow[laskutus_nimitark]</td>";
				echo "<td>$ekarow[laskutus_osoite]</td>";
				echo "<td>$ekarow[laskutus_postino]</td>";
				echo "<td>$ekarow[laskutus_postitp]</td>";
				echo "<td>$ekarow[laskutus_maa]</td>";
				echo "</tr>";
			}

			echo "</table><br>";

			mysql_data_seek($res,0);

			// Onko yht‰‰n jaksotettua tilausta
			$jaksotettuja = FALSE;

			while ($row = mysql_fetch_assoc($res)) {
				if ($row["jaksotettu"] > 0) {
					$jaksotettuja = TRUE;
					break;
				}
			}

			mysql_data_seek($res,0);

			echo t("Valitse laskutettavat tilaukset").":<br><table>";

			echo "<th>".t("Laskuta")."</th>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Arvo")."<br>".t("Summa")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Laatija")."<br>".t("Laadittu")."</th>";
			echo "<th>".t("Laskutusp‰iv‰")."</th>";
			echo "<th>".t("Maksuehto")."</th>";
			echo "<th>".t("Toimitustapa")."</th>";
			echo "<th>".t("Muokkaa tilausta")."</th>";
			if ($jaksotettuja) echo "<th>".t("Laskuta kaikki positiot")."</th>";

			$maksu_positiot = array();

			while ($row = mysql_fetch_assoc($res)) {

				if (isset($edketjutus) and $edketjutus != $row["ketjutuskentta"]) {
					echo "<tr><td class='back' align='center' colspan='5'><hr></td><td class='back' align='center'><font class='info'>Lasku:</font></td><td class='back' colspan='3'><hr></td></tr>";
				}

				// jos yksikin on k‰teinen niin kaikki on k‰teist‰ (se hoidetaan jo ylh‰‰ll‰)
				if ($row["kateinen"] != "") $kateinen = "X";
				if ($row["maa"] != "") $maa = $row["maa"];

				$query = "	SELECT sum(if(varattu>0,1,0)) veloitus, sum(if(varattu<0,1,0)) hyvitys, sum(if(hinta*varattu*{$query_ale_lisa}=0 and var!='P' and var!='J',1,0)) nollarivi
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$row[tunnus]'
							and tyyppi  = 'L'";
				$hyvre = pupe_query($query);
				$hyvrow = mysql_fetch_array($hyvre);

				echo "<tr class='aktiivi'><td><input type='checkbox' name='tunnus[$row[tunnus]]' value='$row[tunnus]' checked></td>";

				echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&toim=$toim&tunnukset=$tunnukset&tunnus=$row[tunnus]'>$row[tunnus]</a></td>";
				echo "<td align='right'>$row[arvo]<br>$row[summa]</td>";

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

				echo "<td>$row[kukanimi]<br>".tv1dateconv($row["luontiaika"], "P")."</td>";

				$js_laskutusvkopv = $row["laskutusvkopv"];

				if ($row["laskutusvkopv"] == 0)		$teksti = t("Kaikki");
				elseif ($row["laskutusvkopv"] == 2)	$teksti = t("Maanantai");
				elseif ($row["laskutusvkopv"] == 3) $teksti = t("Tiistai");
				elseif ($row["laskutusvkopv"] == 4) $teksti = t("Keskiviikko");
				elseif ($row["laskutusvkopv"] == 5) $teksti = t("Torstai");
				elseif ($row["laskutusvkopv"] == 6) $teksti = t("Perjantai");
				elseif ($row["laskutusvkopv"] == 7) $teksti = t("Lauantai");
				elseif ($row["laskutusvkopv"] == 1) $teksti = t("Sunnuntai");
				elseif ($row["laskutusvkopv"] == 9) $teksti = t("Laskut l‰hetet‰‰n vain ohittamalla laskutusvkopv");
				elseif ($row["laskutusvkopv"] < 0) {

					if ($row["laskutusvkopv"] == -1) {
						// Kuukauden viimeinen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("vika", TRUE);
					}
					elseif ($row["laskutusvkopv"] == -2) {
						// Kuukauden ensimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("eka", TRUE);

						// Jos mentiin ohi, niin otetaan seuraavan kuun eka arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("eka", TRUE, 1);
						}
					}
					elseif ($row["laskutusvkopv"] == -3) {
						// Kuukauden keskimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("keski", TRUE);

						// Jos mentiin ohi, niin otetaan seuraavan kuun keskimm‰inen arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("keski", TRUE, 1);
						}
					}
					elseif ($row["laskutusvkopv"] == -4) {
						// Kuukauden keskimm‰inen ja viimeinen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("keski", TRUE);

						// Jos keskimm‰inen meni ohi, niin otetaan kuun vika arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("vika", TRUE);
						}
					}
					elseif ($row["laskutusvkopv"] == -5) {
						// Kuukauden ensimm‰inen ja keskimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("eka", TRUE);

						// Jos eka meni ohi, niin otetaan kuun keskimm‰inen arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("keski", TRUE);
						}

						// Jos keskimm‰inen meni ohi, niin otetaan seuraavan kuun eka arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("eka", TRUE, 1);
						}
					}

					$teksti = tv1dateconv($laskutusvkopv);
				}

				echo "<td>$teksti</td>";
				echo "<td>$row[meh]</td>";

				$rahti_hinta = "";

				if ($yhtiorow["rahti_hinnoittelu"] == "" and $row["rahtivapaa"] == "") {

					// haetaan rahtimaksu
					$rahtihinta_array = hae_rahtimaksu($row["tunnus"]);

					$rahtihinta_ale = array();

					if (is_array($rahtihinta_array)) {
						$rahtihinta = $rahtihinta_array['rahtihinta'];

						foreach ($rahtihinta_array['alennus'] as $ale_k => $ale_v) {
							$rahtihinta_ale[$ale_k] = $ale_v;
						}
					}
					else {
						$rahtihinta = 0;
					}

					// $query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
					// $rhire = pupe_query($query);
					// $trow  = mysql_fetch_array($rhire);

					// $netto = count($rahtihinta_ale) > 0 ? '' : 'N';

					// list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($row, $trow, '1', $netto, $rahtihinta, $rahtihinta_ale);
					// list($hinta, $alv) = alv($row, $trow, $lis_hinta, '', $alehinta_alv);

					// $lis_ale_kaikki['erikoisale'] = $row['erikoisale'];
					// $lis_ale_kaikki['netto'] = $netto;

					// $hinta *= generoi_alekentta_php($lis_ale_kaikki, 'M', 'kerto');

					$hinta = $rahtihinta;

					if ($row["kohdistettu"] == "K") {
						$rahti_hinta = "(" . (float) $hinta ." $row[valkoodi])";
					}
					else {
						$rahti_hinta = "(vastaanottaja)";
					}
				}

				echo "<td>$row[toimitustapa] $rahti_hinta</td>";

				echo "<td><a href='tilaus_myynti.php?toim=PIKATILAUS&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$row[tunnus]'>".t("Muokkaa")."</a></td>";

				//Tsekataan voidaanko antaa mahdollisuus laskuttaa kaikki maksupotitiot kerralla
				if ($jaksotettuja) {
					if ($row["jaksotettu"] > 0) {
						$query = "	SELECT
									sum(if(lasku.tila='L' and lasku.alatila IN ('J','X'),1,0)) tilaok,
									sum(if(tilausrivi.toimitettu='',1,0)) toimittamatta,
									count(*) toimituksia
									FROM lasku
									JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.jaksotettu=lasku.jaksotettu and tilausrivi.tyyppi = 'L' and tilausrivi.var != 'P'
									WHERE lasku.yhtio 		= '$kukarow[yhtio]'
									and lasku.jaksotettu 	= '$row[jaksotettu]'
									GROUP BY lasku.jaksotettu";
						$tarkres = pupe_query($query);
						$tarkrow = mysql_fetch_array($tarkres);
					}

					if ($row["jaksotettu"] > 0 and $tarkrow["toimittamatta"] == 0 and $tarkrow["toimituksia"] > 0 and !in_array($row["jaksotettu"], $maksu_positiot)) {
						//Pidet‰‰n muistissa mitk‰ maksusopparit me ollaan jo tulostettu ruudulle
						$maksu_positiot[] = $row["jaksotettu"];

						echo "<td>".t("Sopimus")." $row[jaksotettu]: <input type='checkbox' name='positiotunnus[$row[jaksotettu]]' value='$row[jaksotettu]'></td>";
					}
					elseif($row["jaksotettu"] > 0 and $tarkrow["toimittamatta"] == 0 and $tarkrow["toimituksia"] > 0 and in_array($row["jaksotettu"], $maksu_positiot)) {
						echo "<td>".t("Kuuluu sopimukseen")." $row[jaksotettu]</td>";
					}
					elseif($row["jaksotettu"] > 0 and $tarkrow["toimittamatta"] > 0) {
						echo "<td>".t("Ei valmis")."</td>";
					}
					else {
						echo "<td>".t("Ei positioita")."</td>";
					}
				}

				if ($hyvrow["nollarivi"] > 0) {
					echo "<td class='back'>&nbsp;<font class='error'>".t("HUOM: Tilauksella on nollahintaisia rivej‰!")."</font></td>";
				}

				if ($row["chn"] == "010") {
					//Varmistetaan, ett‰ meill‰ on verkkotunnus laskulla jos pit‰isi l‰hett‰‰ verkkolaskuja!
					if($row["verkkotunnus"] == "") {
						$query = "	SELECT verkkotunnus
									FROM asiakas
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$row[liitostunnus]' and verkkotunnus!=''";
						$asres = pupe_query($query);
						if(mysql_num_rows($asres) == 1) {
							$asrow = mysql_fetch_array($asres);
							$query = "	UPDATE lasku SET
							 				verkkotunnus = '$asrow[verkkotunnus]'
										WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]' and verkkotunnus=''";
							$upres = pupe_query($query);
						}
						else {
							echo "<td class='back'>&nbsp;<font class='message'>".t("VIRHE: Verkkotunnus puuttuu asiakkaalta ja laskulta!")."</font></td>";
						}
					}
				}

				echo "</tr>";

				$edketjutus = $row["ketjutuskentta"];
			}
			echo "</table><br>";

			echo "<table>";

			if ($kateinen == 'X') {

				echo "<tr><th>".t("Valitse kassalipas")."</th><td colspan='3'>";
				echo "<input type='hidden' name='vaihdakateista' value='KYLLA'>";

				$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]'";
				$kassares = pupe_query($query);

				echo "<select name='kassalipas'>";
				echo "<option value=''>".t("Ei kassalipasta")."</option>";

				$sel = "";

				while ($kassarow = mysql_fetch_array($kassares)) {
					if ($kukarow["kassamyyja"] == $kassarow["tunnus"]) {
						$sel = "selected";
					}
					elseif ($kassalipas == $kassarow["tunnus"]) {
						$sel = "selected";
					}

					echo "<option value='$kassarow[tunnus]' $sel>$kassarow[nimi]</option>";

					$sel = "";
				}
				echo "</select>";
				echo "</td></tr>";

				$query_maksuehto = "SELECT *
									FROM maksuehto
									WHERE yhtio='$kukarow[yhtio]'
									and kateinen != ''
									and kaytossa = ''
									and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$maa%')
									ORDER BY tunnus";
				$maksuehtores = pupe_query($query_maksuehto);

				if (mysql_num_rows($maksuehtores) > 1) {
					echo "<tr><th>".t("Maksutapa")."</th><td colspan='3'>";

					echo "<select name='maksutapa'>";

					while ($maksuehtorow = mysql_fetch_array($maksuehtores)) {

						$sel = "";

						if ($maksuehtorow["tunnus"] == $row["maksuehto"]) {
							$sel = "selected";
						}
						echo "<option value='$maksuehtorow[tunnus]' $sel>".t_tunnus_avainsanat($maksuehtorow, "teksti", "MAKSUEHTOKV")."</option>";
					}

					echo "<option value='seka'>".t("Seka")."</option>";
					echo "</select>";
					echo "</td></tr>";

				}
				else {
					$maksuehtorow = mysql_fetch_array($maksuehtores);
					echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
				}
			}

			///* Haetaan asiakkaan kieli *///
			$query = "	SELECT kieli
						FROM asiakas
						WHERE  yhtio ='$kukarow[yhtio]'
						AND tunnus='$ekarow[liitostunnus]'";
			$result = pupe_query($query);
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
			echo "<td colspan='3'><select name='kieli'>";
			echo "<option value='fi' $sel[fi]>".t("Suomi")."</option>";
			echo "<option value='se' $sel[se]>".t("Ruotsi")."</option>";
			echo "<option value='en' $sel[en]>".t("Englanti")."</option>";
			echo "<option value='de' $sel[de]>".t("Saksa")."</option>";
			echo "<option value='dk' $sel[dk]>".t("Tanska")."</option>";
			echo "<option value='ee' $sel[ee]>".t("Eesti")."</option>";
			echo "</select></td></tr>";

			// Haetaan laskutussaate jos chn on s‰hkˆposti
			if ($ekarow["chn"] == "666") {
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							AND laji = 'LASKUTUS_SAATE'
							AND kieli = '$asrow[kieli]'";
				$result = pupe_query($query);

				echo "<tr><th>".t("Valitse saatekirje").":</th>";
				echo "<td colspan='3'><select name='saatekirje'>";
				echo "<option value=''>".t("Ei saatetta")."</option>";

				while ($saaterow = mysql_fetch_array($result)) {
					echo "<option value='$saaterow[tunnus]'>$saaterow[selite]</option>";
				}

				echo "</select></td></tr>";
			}

			echo "<tr><th>".t("Tulosta lasku").":</th><td colspan='3'><select name='valittu_tulostin'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";

			//tulostetaan faili ja valitaan sopivat printterit
			if ($ekarow["varasto"] == 0) {
				$query = "	SELECT *
							from varastopaikat
							where yhtio='$kukarow[yhtio]'
							and printteri5 != ''
							order by alkuhyllyalue,alkuhyllynro
							limit 1";
			}
			else {
				$query = "	SELECT *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$ekarow[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
			}
			$prires= pupe_query($query);
			$prirow= mysql_fetch_array($prires);

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio = '$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = pupe_query($query);

			while ($kirrow = mysql_fetch_array($kirre)) {
				$sel = "";
				if (($yhtiorow["verkkolasku_lah"] == "" or $ekarow["chn"] == "667") and (($kirrow["tunnus"] == $prirow["printteri5"] and $kukarow["kirjoitin"] == 0) or $kirrow["tunnus"] == $kukarow["kirjoitin"])) {
					$sel = "SELECTED";
				}

				echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";

			if ($yhtiorow["sad_lomake_tyyppi"] == "T" and $ekarow["vienti"] == "K") {
				echo "<tr><th>".t("Tulosta SAD-lomake").":</th><td colspan='3'><select name='valittu_sadtulostin'>";

				echo "<option value=''>".t("Ei kirjoitinta")."</option>";

				mysql_data_seek($kirre,0);

				while ($kirrow = mysql_fetch_array($kirre)) {
					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				echo "</select></td></tr>";

				echo "<tr><th>".t("Tulosta SAD-lomakkeen lis‰sivut").":</th><td><select name='valittu_sadlitulostin'>";

				echo "<option value=''>".t("Ei kirjoitinta")."</option>";

				mysql_data_seek($kirre,0);

				while ($kirrow = mysql_fetch_array($kirre)) {
					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				echo "</select></td></tr>";
			}

			echo "<tr><th>".t("Syˆt‰ poikkeava laskutusp‰iv‰m‰‰r‰ (pp-kk-vvvv)").":</th>
					<td><input type='text' name='laskpp' value='' size='3'></td>
					<td><input type='text' name='laskkk' value='' size='3'></td>
					<td><input type='text' name='laskvv' value='' size='5'></td></tr>";

			if ($yhtiorow["myyntilaskun_erapvmlaskenta"] == "K") {
				echo "<tr><th>".t("Laske er‰p‰iv‰").":</th>
					<td colspan='3'><select name='erpcmlaskenta'>";
				echo "<option value=''>".t("Er‰p‰iv‰ lasketaan laskutusp‰iv‰st‰")."</option>";
				echo "<option value='NOW'>".t("Er‰p‰iv‰ lasketaan t‰st‰ hetkest‰")."</option>";
				echo "</select></td></tr>";
			}

			echo "</table>";
			echo "<br><input type='submit' value='".t("Laskuta")."'>";

			echo "<input type='hidden' name='laskutusviikonteksti' value = '$teksti'>";
			echo "<input type='hidden' name='laskutusviikonpaiva' value = '$js_laskutusvkopv'>";

			echo "</form>";

			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

						function verify(){

							var naytetaanko_herja = false
							var msg = '';

							var pp = document.lasku.laskpp;
							var kk = document.lasku.laskkk;
							var vv = document.lasku.laskvv;

							var laskutusviikonteksti = document.lasku.laskutusviikonteksti.value;
							var laskutusviikonpaiva = document.lasku.laskutusviikonpaiva.value;

							pp = Number(pp.value);
							kk = Number(kk.value)-1;
							vv = Number(vv.value);

							// Mik‰li ei syˆtet‰ mit‰‰n 3 kentt‰‰n niin oletetaan t‰t‰p‰iv‰‰ maksup‰iv‰ksi
							if (vv == 0 && pp == 0 && kk == -1) {
								var tanaanpp = $tanaanpp;
								var tanaankk = $tanaankk;
								var tanaanvv = $tanaanvv;

								var dateSyotetty = new Date(tanaanvv, tanaankk, tanaanpp);
								var pvmcheck = new Date(tanaanvv, tanaankk, tanaanpp);

								// Laitetaan yksi ylim‰‰r‰inen kuukausi niin saadaan tulostukseen oikea kk n‰kyviin
								var tanaanoikeakk = $tanaankk+1;
								if (tanaanoikeakk <10) {
									tanaanoikeakk = '0'+tanaanoikeakk;
								}
								var paivamaara = tanaanpp+'.'+tanaanoikeakk+'.'+tanaanvv;

							}
							else {
								// voidaan syˆtt‰‰ kentt‰‰ 2 pituinen vuosiarvo esim. 11 = 2011
								if (vv > 0 && vv < 1000) {
									vv = vv+2000;
								}

								var dateSyotetty = new Date(vv,kk,pp);
								var pvmcheck = new Date(vv,kk,pp);

								// Laitetaan yksi ylim‰‰r‰inen kuukausi niin saadaan tulostukseen oikea kk n‰kyviin
								var oikeakk = kk+1;
								if (oikeakk < 10) {
									oikeakk = '0'+oikeakk;
								}
								var paivamaara = pp+'.'+oikeakk+'.'+vv;
							}

							var dateTallaHet = new Date();
							var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

							var vertaa = pvmcheck.getDay(pvmcheck)+1;

							var tilalkpp = $tilalkpp;
							var tilalkkk = $tilalkkk;
							var tilalkvv = $tilalkvv;
							var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
							dateTiliAlku = dateTiliAlku.getTime();

							var tilloppp = $tilloppp;
							var tillopkk = $tillopkk;
							var tillopvv = $tillopvv;
							var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
							dateTiliLoppu = dateTiliLoppu.getTime();

							dateSyotetty = dateSyotetty.getTime();

							if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
								var msg = msg+'".t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ ei sis‰lly kuluvaan tilikauteen!")." ';
							}

							// ALERT errorit ennen confirmi‰, n‰in estet‰‰n ettei vahingossakaan p‰‰st‰ l‰pi.
							if (ero < 0) {
								var msg = msg+'".t("VIRHE: Laskua ei voi p‰iv‰t‰ tulevaisuuteen!")." ';
							}

							if (msg != '') {
								alert(msg);

								skippaa_tama_submitti = true;
								return false;
							}

							if (laskutusviikonpaiva > 0 && laskutusviikonpaiva < 9 ) {
								if (laskutusviikonpaiva != vertaa) {
									naytetaanko_herja = true;
									var msg = '".t("Asiakkaan normaali laskutusp‰iv‰ on")." '+laskutusviikonteksti+'. ".t("Haluatko varmasti laskuttaa")." '+paivamaara+'? ';
								}
							}
							else if (laskutusviikonpaiva < 0) {
								if (laskutusviikonteksti != paivamaara) {
									naytetaanko_herja = true;
									var msg = '".t("Asiakkaan normaali laskutusp‰iv‰ on")." '+laskutusviikonteksti+'. ".t("Haluatko varmasti laskuttaa")." '+paivamaara+'? ';
								}
							}
							else if (laskutusviikonpaiva == 9) {
								naytetaanko_herja = true;
								var msg = '".t("Asiakkaan normaali laskutusp‰iv‰ on")." '+laskutusviikonteksti+'. ".t("Haluatko varmasti laskuttaa")." '+paivamaara+'? ';
							}

							if (ero >= 2) {
								var msg = msg+'".t("Oletko varma, ett‰ haluat p‰iv‰t‰ laskun yli 2pv menneisyyteen?")." ';
								naytetaanko_herja = true;
							}

							if (naytetaanko_herja == true) {
								if (confirm(msg)) {
									return true;
								}
								else {
									skippaa_tama_submitti = true;
									return false;
								}
							}
						}
					</SCRIPT>";
		}
	}

	// meill‰ ei ole valittua tilausta
	if ($tee == "") {
		$formi	= "find";
		$kentta	= "etsi";

		// tehd‰‰n etsi valinta
		echo "<form name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value=''>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Etsi asiakasta")."</th>";
		echo "<td><input type='text' name='etsi'></td>";
		echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td>";
		echo "</tr></table>";
		echo "</form><br>";

		$haku='';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		if ($yhtiorow["koontilaskut_yhdistetaan"] == 'T') {
			$ketjutus_group = ", lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa ";
		}
		else {
			$ketjutus_group = "";
		}

		// GROUP BY pit‰‰ olla sama kun verkkolasku.php:ss‰ rivi ~1243
		// HUOM LISƒKSI laskutusviikonp‰iv‰ mukaan GROUP BY:hin!!!!
		$query = "	SELECT
					lasku.laskutusvkopv, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
					lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					lasku.maksuehto, lasku.chn,
					lasku.tila, lasku.alatila,
					maksuehto.teksti meh,
					max(lasku.tilaustyyppi) tilaustyyppi,
					group_concat(distinct lasku.tunnus) tunnukset,
					group_concat(distinct lasku.tunnus separator '<br>') tunnukset_ruudulle,
					count(distinct lasku.tunnus) tilauksia,
					count(tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
					FROM lasku use index (tila_index)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi='L'
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					LEFT JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and lasku.chn != '999'
					$alatilat
					$vientilisa
					$muutlisa
					$haku
					GROUP BY lasku.laskutusvkopv, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
					lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
					lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
					lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
					laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
					$ketjutus_group
					ORDER BY lasku.ytunnus, lasku.nimi";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";
			echo "<tr>
					<th>".t("Tilaukset")."</th>
					<th>".t("Asiakas")."</th>
					<th>".t("Tilauksia")."<br>".t("Rivej‰")."</th>
					<th>".t("Arvo")."</th>
					<th>".t("Maksuehto")."</th>
					<th>".t("Laskutusp‰iv‰")."<br>".t("Toimitus")."</th>
					<th>".t("Tila")."</th></tr>";

			$arvoyhteensa = 0;
			$summayhteensa = 0;
			$tilauksiayhteensa = 0;

			while ($tilrow = mysql_fetch_array($tilre)) {

				$laskutyyppi	= $tilrow["tila"];
				$alatila		= $tilrow["alatila"];

				//tehd‰‰n selv‰kielinen tila/alatila
				require "inc/laskutyyppi.inc";

				$toimitusselite = "";
				if ($tilrow["chn"] == '100') $toimitusselite = t("Paperilasku, tulostuspalvelu");
				if ($tilrow["chn"] == '010') $toimitusselite = t("Verkkolasku");
				if ($tilrow["chn"] == '020') $toimitusselite = t("Itella Pupevoice: Vienti-Verkkolasku");
				if ($tilrow["chn"] == '111') $toimitusselite = t("Itella EDI: EIH-1.4 s‰hkˆinen lasku");
				if ($tilrow["chn"] == '112') $toimitusselite = t("Pupesoft-Finvoice: Verkkolasku Pupesoftista-Pupesoftiin");
				if ($tilrow["chn"] == '666') $toimitusselite = t("S‰hkˆposti");
				if ($tilrow["chn"] == '667') $toimitusselite = t("Sis‰inen, k‰sitell‰‰n manuaalisesti");
				if ($tilrow["chn"] == '999') $toimitusselite = t("Laskutuskielto, laskutusta ei tehd‰");

				$teksti = "";

				if ($tilrow["laskutusvkopv"] == 0)	   $teksti = t("Kaikki");
				elseif ($tilrow["laskutusvkopv"] == 2) $teksti = t("Maanantai");
				elseif ($tilrow["laskutusvkopv"] == 3) $teksti = t("Tiistai");
				elseif ($tilrow["laskutusvkopv"] == 4) $teksti = t("Keskiviikko");
				elseif ($tilrow["laskutusvkopv"] == 5) $teksti = t("Torstai");
				elseif ($tilrow["laskutusvkopv"] == 6) $teksti = t("Perjantai");
				elseif ($tilrow["laskutusvkopv"] == 7) $teksti = t("Lauantai");
				elseif ($tilrow["laskutusvkopv"] == 1) $teksti = t("Sunnuntai");
				elseif ($tilrow["laskutusvkopv"] == 9) $teksti = t("Laskut l‰hetet‰‰n vain ohittamalla laskutusvkopv");
				elseif ($tilrow["laskutusvkopv"] < 0) {

					if ($tilrow["laskutusvkopv"] == -1) {
						// Kuukauden viimeinen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("vika", TRUE);
					}
					elseif ($tilrow["laskutusvkopv"] == -2) {
						// Kuukauden ensimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("eka", TRUE);

						// Jos mentiin ohi, niin otetaan seuraavan kuun eka arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("eka", TRUE, 1);
						}
					}
					elseif ($tilrow["laskutusvkopv"] == -3) {
						// Kuukauden keskimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("keski", TRUE);

						// Jos mentiin ohi, niin otetaan seuraavan kuun keskimm‰inen arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("keski", TRUE, 1);
						}
					}
					elseif ($tilrow["laskutusvkopv"] == -4) {
						// Kuukauden keskimm‰inen ja viimeinen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("keski", TRUE);

						// Jos keskimm‰inen meni ohi, niin otetaan kuun vika arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("vika", TRUE);
						}
					}
					elseif ($tilrow["laskutusvkopv"] == -5) {
						// Kuukauden ensimm‰inen ja keskimm‰inen arkip‰iv‰
						$laskutusvkopv = laskutuspaiva("eka", TRUE);

						// Jos eka meni ohi, niin otetaan kuun keskimm‰inen arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("keski", TRUE);
						}

						// Jos keskimm‰inen meni ohi, niin otetaan seuraavan kuun eka arkip‰iv‰
						if (date("Ymd") > (int) str_replace("-", "", $laskutusvkopv)) {
							$laskutusvkopv = laskutuspaiva("eka", TRUE, 1);
						}
					}

					$teksti = tv1dateconv($laskutusvkopv);
				}

				echo "	<tr class='aktiivi'>
						<td valign='top'>$tilrow[tunnukset_ruudulle]</td>
						<td valign='top'>$tilrow[ytunnus]<br>$tilrow[nimi] $tilrow[nimitark]</td>
						<td valign='top'>$tilrow[tilauksia]<br>$tilrow[riveja]</td>
						<td valign='top' align='right' nowrap>$tilrow[arvo]</td>
						<td valign='top'>$tilrow[meh]</td>
						<td valign='top'>$teksti<br>$toimitusselite</td>
						<td valign='top'>".t($alatila)."</td>";

				echo "	<td class='back' valign='top'>
						<form method='post' action='$palvelin2"."tilauskasittely/valitse_laskutettavat_tilaukset.php'>
						<input type='hidden' name='tee' value='VALITSE'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnukset' value='$tilrow[tunnukset]'>
						<input type='submit' name='tila' value='".t("Valitse")."'>
						</form>
						</td>
						</tr>";

				$arvoyhteensa 		+= $tilrow["arvo"];
				$summayhteensa 		+= $tilrow["summa"];
				$tilauksiayhteensa 	+= $tilrow["tilauksia"];

			}
			echo "</table>";

			if ($arvoyhteensa != 0) {
				echo "<br><table>";
				echo "<tr><th>".t("Tilausten arvo yhteens‰")." ($tilauksiayhteensa ".t("kpl")."): </th><td align='right'>$arvoyhteensa $yhtiorow[valkoodi]</td></tr>";
				echo "<tr><th>".t("Tilausten summa yhteens‰").": </th><td align='right'>$summayhteensa $yhtiorow[valkoodi]</td></tr>";
				echo "</table>";
			}
		}
		else {
			echo "<font class='message'>".t("Yht‰‰n toimitettavaa ei lˆytynyt")."...</font>";
		}
	}

	require ("../inc/footer.inc");
?>