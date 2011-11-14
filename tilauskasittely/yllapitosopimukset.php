<?php

	// jos tullaan t��lt� itsest� niin tarvitaan paramertit
	if (strpos($_SERVER['SCRIPT_NAME'], "yllapitosopimukset.php") !== FALSE) {
		require("../inc/parametrit.inc");

		echo "<font class='head'>".t("Yll�pitosopimukset")."</font><hr>";

		js_popup();

		echo " <SCRIPT TYPE='text/javascript' LANGUAGE='JavaScript'>
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,5) == nimi) {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";
	}
	else {
		ob_start(); // ei echota mit���n jos kutsutaan muualta!
	}

	if ($tee == "laskuta" and count($laskutapvm) > 0) {
		$poikkeus = "ei";
		// haetaan funktio
		require ("kopioi_tilaus.inc");

		$laskuta_message = "";

		if (checkdate($laskkk, $laskpp, $laskvv)) {
			$poikkeus = "joo";
			$poikkeuspvm = $laskpp.".".$laskkk.".".$laskvv;
		}

		foreach ($laskutapvm as $pointteri => $tapahtumapvm) {

			$tilausnumero = $laskutatun[$pointteri];

			list($tapvmvv,$tapvmkk,$tapvmpp) = explode("-", $tapahtumapvm);

			//	Haetaan sopimuskausi ja kommentti
			$query = "	SELECT lasku.viesti,
						laskun_lisatiedot.sopimus_kk,
						laskun_lisatiedot.sopimus_pp,
						laskun_lisatiedot.sopimus_alkupvm,
						laskun_lisatiedot.sopimus_loppupvm
			 			FROM lasku
						LEFT JOIN laskun_lisatiedot ON lasku.yhtio = laskun_lisatiedot.yhtio and lasku.tunnus = laskun_lisatiedot.otunnus
						WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
						and lasku.tunnus = '$tilausnumero'";
			$result = pupe_query($query);
			$soprow = mysql_fetch_assoc($result);

			$laskutus_kk = explode(",", $soprow["sopimus_kk"]);
			$laskutus_pp = explode(",", $soprow["sopimus_pp"]);

			// Luodaan array jossa on sopimuksen t�n vuoden kaikki laskutukset
			$laskutuspaivat = array();

			foreach ($laskutus_kk as $laskk) {

				$kkvikapva = date("t",mktime(0, 0, 0, $laskk, 1, $tapvmvv));

				foreach ($laskutus_pp as $laspp) {

					if ($laspp > $kkvikapva and !array_search($tapvmvv."-".sprintf("%02d", $laskk)."-".sprintf("%02d", $kkvikapva), $laskutuspaivat)) {
						$laskutuspaivat[] = $tapvmvv."-".sprintf("%02d", $laskk)."-".sprintf("%02d", $kkvikapva);
					}
					else {
						$laskutuspaivat[] = $tapvmvv."-".sprintf("%02d", $laskk)."-".sprintf("%02d", $laspp);
					}
				}
			}

			$tama_laskutus = array_search($tapahtumapvm, $laskutuspaivat);
			$soplaskmaara = count($laskutuspaivat)-1;


			// Onko t�m� t�n vuoden eka ja vika sopparilasku
			if ($tama_laskutus == 0 and $tama_laskutus == $soplaskmaara) {
				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$tama_laskutus]);

				$ed_alku = date("Y-m-d", mktime(0, 0, 0, $kk, $pp+1, $vv-1));
				$se_lopp  = date("Y-m-d", mktime(0, 0, 0, $kk, $pp-1, $vv+1));
			}
			// Jos t�m� t�n vuoden eka sopparilasku, niin edellinen on viime vuoden vika
			elseif ($tama_laskutus == 0) {
				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$soplaskmaara]);
				$ed_alku = date("Y-m-d", mktime(0, 0, 0, $kk, $pp+1, $vv-1));

				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$tama_laskutus+1]);
				$se_lopp  = date("Y-m-d", mktime(0, 0, 0, $kk, $pp-1, $vv));
			}
			// Jos t�m� t�n vuoden vika sopparilasku, niin seuraava on ens vuoden eka
			elseif ($tama_laskutus == $soplaskmaara) {
				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$tama_laskutus-1]);
				$ed_alku = date("Y-m-d",mktime(0, 0, 0, $kk, $pp+1, $vv));

				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[0]);
				$se_lopp = date("Y-m-d",mktime(0, 0, 0, $kk, $pp-1, $vv+1));
			}
			// T�m� ei ole t�n vuoden eka eik� vika sopparilasku
			else {
				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$tama_laskutus-1]);
				$ed_alku = date("Y-m-d",mktime(0, 0, 0, $kk, $pp+1, $vv));

				list($vv,$kk,$pp) = explode("-", $laskutuspaivat[$tama_laskutus+1]);
				$se_lopp = date("Y-m-d",mktime(0, 0, 0, $kk, $pp-1, $vv));
			}

			// Edellinen kausi on siis "$ed_alku" - "$tapahtumapvm" ja seuraava on "$tapahtumapvm" - "$se_lopp"
			list($vv,$kk,$pp) = explode("-", $tapahtumapvm);
			$ed_lopp = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));

			// Onko seuraava tai edellinen sopimuskauden ulkopuolella?
			if ($soprow["sopimus_alkupvm"] != "0000-00-00" and str_replace("-", "", $ed_alku) < str_replace("-", "", $soprow["sopimus_alkupvm"])) {
				$ed_alku = $soprow["sopimus_alkupvm"];
			}

			if ($soprow["sopimus_alkupvm"] != "0000-00-00" and str_replace("-", "", $ed_lopp) < str_replace("-", "", $soprow["sopimus_alkupvm"])) {
				$ed_lopp = $soprow["sopimus_alkupvm"];
			}

			if ($soprow["sopimus_loppupvm"] != "0000-00-00" and str_replace("-", "", $se_lopp) > str_replace("-", "", $soprow["sopimus_loppupvm"])) {
				$se_lopp = $soprow["sopimus_loppupvm"];
			}

			unset($from);
			unset($to);

			//	Korjataan kommentti
			$from[]	= "/%ed/";
			$to[]	= tv1dateconv($ed_alku)." - ".tv1dateconv($ed_lopp);

			$from[]	= "/%se/";
			$to[]	= tv1dateconv($tapahtumapvm)." - ".tv1dateconv($se_lopp);

			//	Jos ei kirjoiteta oikein, poistetaan muuttuja
			$from[]	= "/%[\w]{2}/";
			$to[]	= "";

			// monistetaan soppari
			$ok = kopioi_tilaus($tilausnumero, array(	"viesti" => array("from" => $from, "to" => $to),
														"comments" => array("from" => $from, "to" => $to),
														"sisviesti1" => array("from" => $from, "to" => $to),
														"sisviesti2" => array("from" => $from, "to" => $to),
													),
												array(	"kommentti" => array("from" => $from, "to" => $to)
													),
												$tapahtumapvm
								);

			if ($ok !== FALSE) {

				$laskuta_message .= "<font class='message'>".t("Monistetaan sopimus")." $tilausnumero ($tapvmpp.$tapvmkk.$tapvmvv)";

				// p�ivitet��n sopparipohjalle, ett� sit� on jo k�yettty
				$query  = "	UPDATE lasku
							SET alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$tilausnumero'
							and tila    = '0'";
				$result = pupe_query($query);

				// p�ivitet��n tila myyntitilaus valmis, suoraan laskutukseen (clearing on sopimus ja swift kent�ss� on mik� soppari on kopsattu)
				$query  = "	UPDATE lasku
							SET tila 		= 'N',
							alatila 		= '',
							eilahetetta		= 'o',
							clearing		= 'sopimus',
							swift			= '$tilausnumero',
							tilaustyyppi	= ''
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tunnus  	= '$ok'
							and tila    	= '0'";
				$result = pupe_query($query);

				// tyyppi takasin L, merkataan rivit ker�tyks ja toimitetuks
				$query = "	UPDATE tilausrivi
							SET tyyppi	   = 'L'
							WHERE yhtio		= '$kukarow[yhtio]'
							and otunnus		= '$ok'
							and tyyppi  	= '0'";
				$result = pupe_query($query);

				// haetaan laskun tiedot
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$ok'";
				$result = pupe_query($query);
				$laskurow = mysql_fetch_assoc($result);

				$kukarow["kesken"] = $ok;

				// tilaus valmis
				require("tilaus-valmis.inc");

				// p�ivitet��n tila myyntitilaus valmis, suoraan laskutukseen (clearing on sopimus ja swift kent�ss� on mik� soppari on kopsattu)
				$query  = "	UPDATE lasku
							SET tila 	= 'L',
							alatila 	= 'D',
							luontiaika 	= '$tapahtumapvm'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$ok'
							and tila = 'L'";
				$result = pupe_query($query);

				// tyyppi takasin L, merkataan rivit ker�tyks ja toimitetuks
				$query = "	UPDATE tilausrivi
							SET tyyppi	   = 'L',
							toimitettu     = '$kukarow[kuka]',
							toimitettuaika = now()
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus	= '$ok'
							and tyyppi = 'L'";
				$result = pupe_query($query);

				if ($jatakesken != "JOO") {
					// laskutetaan tilaus
					$laskutettavat  = $ok;
					$tee 			= "TARKISTA";
					$laskutakaikki 	= "KYLLA";
					$silent		 	= "KYLLA";
					
					if ($poikkeus == "joo") {
						$laskuta_message .= ", ".t("laskutetaan tilaus")." $ok ".t("p�iv�lle")." ".$poikkeuspvm.".</font><br>";
					}
					else {
						$laskuta_message .= ", ".t("laskutetaan tilaus")." $ok ".t("p�iv�lle")." ".date("d.m.Y").".</font><br>";
					}
					
					require("verkkolasku.php");
				}
				else {
					$laskuta_message .= ", ".t("lasku j�tettiin avoimeksi").".</font><br>";
				}
			}
		}

		echo "$laskuta_message<br>";

	}

	$query_ale_lisa = generoi_alekentta('M');

	// n�ytet��n sopparit
	$query = "	SELECT *,
				concat_ws('<br>', lasku.ytunnus, concat_ws(' ',lasku.nimi,lasku.nimitark), if (lasku.nimi!=lasku.toim_nimi, concat_ws(' ',lasku.toim_nimi,lasku.toim_nimitark), NULL), if (lasku.postitp!=lasku.toim_postitp, lasku.toim_postitp, NULL)) nimi,
				lasku.tunnus laskutunnus,
				round(sum(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
				round(sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
				FROM lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and
										laskun_lisatiedot.otunnus = lasku.tunnus and
										laskun_lisatiedot.sopimus_alkupvm <= date_add(now(), interval 1 month) and
										(laskun_lisatiedot.sopimus_loppupvm >= date_sub(now(), interval 1 month) or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = '0')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = '0' and
				lasku.alatila in ('V','X')
				GROUP BY laskutunnus
				ORDER BY liitostunnus, sopimus_loppupvm, sopimus_alkupvm";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<form method='post' action='$PHP_SELF' name='yllapitosopimus' onSubmit = 'return verify()'>";
		echo "<input type='hidden' name='tee' value='laskuta'>";

		echo "<table>";
		echo "<tr><th>".t("Laskun kieli").":</th>";
		echo "<td><select name='kieli'>";
		echo "<option value='fi' $sel[fi]>".t("Suomi")."</option>";
		echo "<option value='se' $sel[se]>".t("Ruotsi")."</option>";
		echo "<option value='en' $sel[en]>".t("Englanti")."</option>";
		echo "<option value='de' $sel[de]>".t("Saksa")."</option>";
		echo "<option value='dk' $sel[dk]>".t("Tanska")."</option>";
		echo "</select></td></tr>";

		echo "<tr><th>".t("Laskutulostin").":</th><td><select name='valittu_tulostin'>";
		echo "<option value=''>".t("Ei kirjoitinta")."</option>";

		//tulostetaan faili ja valitaan sopivat printterit
		$query = "	SELECT *
					FROM kirjoittimet
					WHERE
					yhtio = '$kukarow[yhtio]'
					ORDER by kirjoitin";
		$kirre = pupe_query($query);

		while ($kirrow = mysql_fetch_assoc($kirre)) {
			$sel = "";
			if ($kirrow["tunnus"] == $kukarow["kirjoitin"]) {
				$sel = "SELECTED";
			}
			echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo "<br><table>";

		echo "<tr>";
		echo "<th>".t("sopimus")."</th>";
		echo "<th>".t("ytunnus")."</th>";
		echo "<th>".t("nimi")."</th>";
		echo "<th>".t("alkupvm")."</th>";
		echo "<th>".t("loppupvm")."</th>";
		echo "<th>".t("kk")."</th>";
		echo "<th>".t("pv")."</th>";
		echo "<th>".t("arvo")."</th>";
		echo "<th>".t("laskutettu")." (laskunro)</th>";
		echo "<th>".t("laskuttamatta")."</th>";
		echo "</tr>";

		$pointteri = 0; // pointteri
		$cron_pvm = array(); // cronijobia varten
		$cron_tun = array(); // cronijobia varten
		$arvoyhteensa  = 0;
        $summayhteensa = 0;

		while ($row = mysql_fetch_assoc($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td valign='top'>$row[laskutunnus]</td>";
			echo "<td valign='top'>$row[ytunnus]</td>";
			echo "<td valign='top'>$row[nimi]</td>";
			echo "<td valign='top'>".tv1dateconv($row["sopimus_alkupvm"])."</td>";
			echo "<td valign='top'>";

			// kaunistelua
			if ($row["sopimus_loppupvm"] == '0000-00-00') {
				echo t("Toistaiseksi");
			}
			else {
				echo tv1dateconv($row["sopimus_loppupvm"]);
			}

			echo "</td>";
			echo "<td valign='top'>";
			if (count(explode(',', $row["sopimus_kk"])) == 12) echo "Kaikki";
			else foreach (explode(',', $row["sopimus_kk"]) as $numi) echo "$numi. ";
			echo "</td>";
			echo "<td valign='top'>";
			foreach (explode(',', $row["sopimus_pp"]) as $numi) echo "$numi. ";
			echo "</td>";
			echo "<td valign='top'>$row[arvo] $row[valkoodi]</td>";

			// katotaan montakertaa t�� on laskutettu tai laskuttamatta
			$laskutettu = "";
			$laskutettu_vika = "";
			$laskuttamatta = "";

			// splitataan alku ja loppupvm omiin muuttujiin
			list($pvmloop_vv, $pvmloop_kk, $pvmloop_pp) = explode('-', $row["sopimus_alkupvm"]);
			list($yllapito_loppuvv, $yllapito_loppukk, $yllapito_loppupp) = explode('-', $row["sopimus_loppupvm"]);

			// p�iv�m��r�t inteiks
			$pvmalku  = (int) date('Ymd',mktime(0,0,0,$pvmloop_kk,$pvmloop_pp,$pvmloop_vv));
			$pvmloppu = (int) date('Ymd',mktime(0,0,0,$yllapito_loppukk,$yllapito_loppupp,$yllapito_loppuvv));

			// n�ytt��n vaan t�h�n p�iv��n asti
			if ($pvmloppu > date('Ymd') or $row["sopimus_loppupvm"] == '0000-00-00') {
				$pvmloppu = date('Ymd');
			}

			// Nollataan
			unset($ruksatut_paivat);

			// for looppi k�yd��n l�pi kaikki p�iv�t
			for ($pvm = $pvmalku; $pvm <= $pvmloppu; $pvm = (int) date('Ymd',mktime(0,0,0,$pvmloop_kk,$pvmloop_pp+1,$pvmloop_vv))) {

				// otetaan n�� taas erikseen
				$pvmloop_pp = substr($pvm,6,2);
				$pvmloop_kk = substr($pvm,4,2);
				$pvmloop_vv = substr($pvm,0,4);

				// Jos sopparille on valittu liian iso p�iv�:
				if (!isset($ruksatut_paivat[$pvmloop_kk])) {
					$ruksatut_paivat[$pvmloop_kk] = array();
					$kkvikapva = date("t",mktime(0, 0, 0, $pvmloop_kk, 1, $pvmloop_vv));

					foreach (explode(',', $row["sopimus_pp"]) as $ruksattu_paiva) {
						if ($ruksattu_paiva > $kkvikapva) {
							$ruksatut_paivat[$pvmloop_kk][$kkvikapva] = $kkvikapva;
						}
						else {
							$ruksatut_paivat[$pvmloop_kk][$ruksattu_paiva] = $ruksattu_paiva;
						}
					}
				}

				if (in_array($pvmloop_kk, explode(',', $row["sopimus_kk"])) and in_array($pvmloop_pp, $ruksatut_paivat[$pvmloop_kk])) {

					// katotaan ollaanko t�m� lasku laskutettu
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio  	 = '$kukarow[yhtio]'
								and liitostunnus = '$row[liitostunnus]'
								and tila         = 'L'
								and alatila      in ('X','D')
								and luontiaika   = '$pvmloop_vv-$pvmloop_kk-$pvmloop_pp'
								and clearing     = 'sopimus'
								and swift        = '$row[laskutunnus]'";
					$chkres = pupe_query($query);

					if (mysql_num_rows($chkres) == 0) {
						$laskuttamatta .= "	<input type='checkbox' name='laskutapvm[$pointteri]' value='$pvmloop_vv-$pvmloop_kk-$pvmloop_pp'>
											<input type='hidden' name='laskutatun[$pointteri]' value='$row[laskutunnus]'>
											$pvmloop_pp.$pvmloop_kk.$pvmloop_vv<br>";

						// tehd��n arrayt� cronijobia varten
						$cron_pvm[$pointteri] = "$pvmloop_vv-$pvmloop_kk-$pvmloop_pp";
						$cron_tun[$pointteri] = "$row[laskutunnus]";

						$pointteri++;

						$arvoyhteensa 	+= $row["arvo"];
						$summayhteensa 	+= $row["summa"];
					}
					else {
						$chkrow = mysql_fetch_assoc($chkres);
						$laskutettu .= "$pvmloop_pp.$pvmloop_kk.$pvmloop_vv ($chkrow[laskunro])<br>";
						$laskutettu_vika = "$pvmloop_pp.$pvmloop_kk.$pvmloop_vv ($chkrow[laskunro])";
					}
				}
			}

			$classname = '';
			if ($laskutettu != '') {
				$classname = 'tooltip';
			}

			echo "<td nowrap class='$classname' id='$row[laskutunnus]'>$laskutettu_vika ";

			if ($classname == 'tooltip') {
				echo "<div id='div_$row[laskutunnus]' class='popup'>";
				echo "$laskutettu";
				echo "</div>";
			}

			echo "</td>";
			echo "<td valign='top' nowrap>$laskuttamatta</td>";
			echo "</tr>";
		}

		echo "<tr><th colspan='9'>".t("Valitse kaikki")."</th><th><input type='checkbox' name='lasku' onclick='toggleAll(this);'></th></tr>";
		echo "</table>";

		if ($arvoyhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Laskuttamatta arvo yhteens�").": </th><td align='right'>$arvoyhteensa $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Laskuttamatta summa yhteens�").": </th><td align='right'>$summayhteensa $yhtiorow[valkoodi]</td></tr>";
			echo "<tr>";
			echo "</table>";
		}
		
		echo "<br>";		
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Sy�t� Poikkeava Laskutusp�iv�m��r� (Pp-Kk-Vvvv)")."</th>";
		echo "<td>	<input type='text' name='laskpp' value='' size='3'>
					<input type='text' name='laskkk' value='' size='3'>
					<input type='text' name='laskvv' value='' size='5'>";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>".t("�l� aja laskutusta").":</th>";
		echo "<td><input type='checkbox' name='jatakesken' value='JOO'></td>";
		echo "</tr>";
		echo "<tr><td class='back'></td><td class='back'><input type='submit' value='".t("Laskuta")."'></td></tr>";
		echo "</table>";

		//p�iv�m��r�n tarkistus
		$tilalk = explode("-", $yhtiorow["tilikausi_alku"]);
		$tillop = explode("-", $yhtiorow["tilikausi_loppu"]);

		$tilalkpp = $tilalk[2];
		$tilalkkk = $tilalk[1]-1;
		$tilalkvv = $tilalk[0];

		$tilloppp = $tillop[2];
		$tillopkk = $tillop[1]-1;
		$tillopvv = $tillop[0];

		$tanaanpp = date("d");
		$tanaankk = date("m");
		$tanaanvv = date("Y");


		echo "</form>";
		
		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

					function verify(){

						var naytetaanko_herja = false
						var msg = '';

						var pp = document.yllapitosopimus.laskpp;
						var kk = document.yllapitosopimus.laskkk;
						var vv = document.yllapitosopimus.laskvv;


						pp = Number(pp.value);
						kk = Number(kk.value);
						vv = Number(vv.value);

						// Mik�li ei sy�tet� mit��n 3 kentt��n niin oletetaan t�t�p�iv�� maksup�iv�ksi
						if (vv == 0 && pp == 0 && kk == 0) {
							var tanaanpp = $tanaanpp;
							var tanaankk = $tanaankk;
							var tanaanvv = $tanaanvv;
							var falsekk = tanaankk-1;

							var dateSyotetty = new Date(tanaanvv, falsekk, tanaanpp);
							
						}
						else {
							// voidaan sy�tt�� kentt�� 2 pituinen vuosiarvo esim. 11 = 2011
							if (vv > 0 && vv < 1000) {
								vv = vv+2000;
							}
							var falsekk = kk-1;

							var dateSyotetty = new Date(vv,falsekk,pp);

						}

						var dateTallaHet = new Date();
						var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

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
							var msg = msg+'".t("VIRHE: Sy�tetty p�iv�m��r� ei sis�lly kuluvaan tilikauteen!")." ';
						}

						// ALERT errorit ennen confirmi�, n�in estet��n ettei vahingossakaan p��st� l�pi.
						if (ero < 0) {
							var msg = msg+'".t("VIRHE: Laskua ei voi p�iv�t� tulevaisuuteen!")."';
						}

						if (msg != '') {
							if (alert(msg)) {
								return false;
							}
							else {
								return false;
							}
						}

						if (ero >= 2) {
							var msg = msg+'".t("Oletko varma, ett� haluat p�iv�t� laskun yli 2pv menneisyyteen?")." ';
							naytetaanko_herja = true;
						}

						if (naytetaanko_herja == true) {
							return confirm(msg);
						}
					}
				</SCRIPT>";

	}
	else {
		echo t("Ei yll�pitosopimuksia").".";
	}

	// jos tullaan t��lt� itsest� niin n�ytet��n footer
	if (strpos($_SERVER['SCRIPT_NAME'], "yllapitosopimukset.php") !== FALSE) {
		require ("inc/footer.inc");
	}
	else {
		ob_end_clean(); // ei echota mit���n jos kutsutaan muualta!
	}

?>