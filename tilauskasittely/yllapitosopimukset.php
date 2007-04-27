<?php

	// jos tullaan t‰‰lt‰ itsest‰ niin tarvitaan paramertit
	if (strpos($_SERVER['SCRIPT_NAME'], "yllapitosopimukset.php") !== FALSE) {
		require("../inc/parametrit.inc");
	}
	else {
		ob_start(); // ei echota mit‰‰‰n jos kutsutaan muualta!
	}

	echo "<font class='head'>".t("Yll‰pitosopimukset")."</font><hr>";

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

	if ($tee == "laskuta" and count($laskutapvm) > 0) {

		// haetaan funktio
		require ("kopioi_tilaus.inc");

		$laskuta_message = "";

		foreach ($laskutapvm as $pointteri => $tapahtumapvm) {

			$tilausnumero = $laskutatun[$pointteri];
			list($tapvmvv,$tapvmkk,$tapvmpp) = split("-", $tapahtumapvm);

			// monistetaan soppari
			$ok = kopioi_tilaus($tilausnumero);

			if ($ok !== FALSE) {

				$laskuta_message .= "<font class='message'>Monistetaan sopimus $tilausnumero ($tapvmpp.$tapvmkk.$tapvmvv)";

				// p‰ivitet‰‰n sopparipohjalle, ett‰ sit‰ on jo k‰yettty
				$query  = "	UPDATE lasku
							SET alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$tilausnumero'
							and tila    = '0'";
				$result = mysql_query($query) or pupe_error($query);

				// p‰ivitet‰‰n tila myyntitilaus valmis, suoraan laskutukseen (clearing on sopimus ja swift kent‰ss‰ on mik‰ soppari on kopsattu)
				$query  = "	UPDATE lasku
							SET tila = 'N',
							alatila = '',
							eilahetetta = 'o',
							clearing = 'sopimus',
							swift = '$tilausnumero',
							tilaustyyppi = ''
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$ok'
							and tila    = '0'";
				$result = mysql_query($query) or pupe_error($query);

				// tyyppi takasin L, merkataan rivit ker‰tyks ja toimitetuks
				$query = "	UPDATE tilausrivi
							SET tyyppi	   = 'L'
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus	= '$ok'
							and tyyppi  = '0'";
				$result = mysql_query($query) or pupe_error($query);

				// haetaan laskun tiedot
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$ok'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				$kukarow["kesken"] = $ok;

				// tilaus valmis
				require("tilaus-valmis.inc");

				// p‰ivitet‰‰n tila myyntitilaus valmis, suoraan laskutukseen (clearing on sopimus ja swift kent‰ss‰ on mik‰ soppari on kopsattu)
				$query  = "	UPDATE lasku
							SET tila = 'L',
							alatila = 'D',
							luontiaika = '$tapahtumapvm'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$ok'
							and tila = 'L'";
				$result = mysql_query($query) or pupe_error($query);

				// tyyppi takasin L, merkataan rivit ker‰tyks ja toimitetuks
				$query = "	UPDATE tilausrivi
							SET tyyppi	   = 'L',
							toimitettu     = '$kukarow[kuka]',
							toimitettuaika = now()
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus	= '$ok'
							and tyyppi = 'L'";
				$result = mysql_query($query) or pupe_error($query);

				// laskutetaan tilaus
				$laskutettavat  = $ok;
				$tee 			= "TARKISTA";
				$laskutakaikki 	= "KYLLA";
				$silent		 	= "KYLLA";

				$laskuta_message .= ", laskutetaan tilaus $ok p‰iv‰lle ".date("d.m.Y").".</font><br>";
				require("verkkolasku.php");

			}

		}

		echo "$laskuta_message<br>";

	}

	// n‰ytet‰‰n sopparit
	$query = "	SELECT *, lasku.tunnus laskutunnus,
				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa
				FROM lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and
										laskun_lisatiedot.otunnus = lasku.tunnus and
										laskun_lisatiedot.sopimus_alkupvm <= now() and
										(laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = '0')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = '0' and
				lasku.alatila in ('V','X')
				GROUP BY laskutunnus
				ORDER BY liitostunnus, sopimus_loppupvm, sopimus_alkupvm";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<form method='post' action='$PHP_SELF'>";
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
		$kirre = mysql_query($query) or pupe_error($query);

		while ($kirrow = mysql_fetch_array($kirre)) {
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
		echo "<th>sopimus</th>";
		echo "<th>ytunnus</th>";
		echo "<th>nimi</th>";
		echo "<th>sopimus alkupvm</th>";
		echo "<th>sopimus loppupvm</th>";
		echo "<th>laskutus kk</th>";
		echo "<th>laskutus pp</th>";
		echo "<th>arvo</th>";
		echo "<th>laskutettu</th>";
		echo "<th>laskuttamatta</th>";
		echo "</tr>";

		$pointteri = 0; // pointteri
		$cron_pvm = array(); // cronijobia varten
		$cron_tun = array(); // cronijobia varten
		$arvoyhteensa  = 0;
        $summayhteensa = 0;

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";
			echo "<td>$row[laskutunnus]</td>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[nimi] $row[toim_nimi]</td>";
			echo "<td>".tv1dateconv($row["sopimus_alkupvm"])."</td>";
			echo "<td>";
			// kaunistelua
			if ($row["sopimus_loppupvm"] == '0000-00-00') {
				echo "Toistaiseksi";
			}
			else {
				echo tv1dateconv($row["sopimus_loppupvm"]);
			}
			echo "</td>";
			echo "<td>";
			if (count(explode(',', $row["sopimus_kk"])) == 12) echo "Kaikki";
			else foreach (explode(',', $row["sopimus_kk"]) as $numi) echo "$numi. ";
			echo "</td>";
			echo "<td>";
			foreach (explode(',', $row["sopimus_pp"]) as $numi) echo "$numi. ";
			echo "</td>";
			echo "<td>$row[arvo]</td>";

			// katotaan montakertaa t‰‰ on laskutettu tai laskuttamatta
			$laskutettu = "";
			$laskuttamatta = "";

			// splitataan alku ja loppupvm omiin muuttujiin
			list($pvmloop_vv, $pvmloop_kk, $pvmloop_pp) = explode('-', $row["sopimus_alkupvm"]);
			list($yllapito_loppuvv, $yllapito_loppukk, $yllapito_loppupp) = explode('-', $row["sopimus_loppupvm"]);

			// p‰iv‰m‰‰r‰t inteiks
			$pvmalku  = (int) date('Ymd',mktime(0,0,0,$pvmloop_kk,$pvmloop_pp,$pvmloop_vv));
			$pvmloppu = (int) date('Ymd',mktime(0,0,0,$yllapito_loppukk,$yllapito_loppupp,$yllapito_loppuvv));

			// n‰ytt‰‰n vaan t‰h‰n p‰iv‰‰n asti
			if ($pvmloppu > date('Ymd') or $row["sopimus_loppupvm"] == '0000-00-00') {
				$pvmloppu = date('Ymd');
			}

			// for looppi k‰yd‰‰n l‰pi kaikki p‰iv‰t
			for ($pvm = $pvmalku; $pvm <= $pvmloppu; $pvm = (int) date('Ymd',mktime(0,0,0,$pvmloop_kk,$pvmloop_pp+1,$pvmloop_vv))) {

				// otetaan n‰‰ taas erikseen
				$pvmloop_pp = substr($pvm,6,2);
				$pvmloop_kk = substr($pvm,4,2);
				$pvmloop_vv = substr($pvm,0,4);

				if (in_array($pvmloop_kk, explode(',', $row["sopimus_kk"])) and in_array($pvmloop_pp, explode(',', $row["sopimus_pp"]))) {

					// katotaan ollaanko t‰m‰ lasku laskutettu
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio  = '$kukarow[yhtio]' and
								liitostunnus = '$row[liitostunnus]' and
								tila         = 'L' and
								alatila      = 'X' and
								luontiaika   = '$pvmloop_vv-$pvmloop_kk-$pvmloop_pp' and
								clearing     = 'sopimus' and
								swift        = '$row[laskutunnus]'";
					$chkres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($chkres) == 0) {
						$laskuttamatta .= "	<input type='checkbox' name='laskutapvm[$pointteri]' value='$pvmloop_vv-$pvmloop_kk-$pvmloop_pp'>
											<input type='hidden' name='laskutatun[$pointteri]' value='$row[laskutunnus]'>
											$pvmloop_pp.$pvmloop_kk.$pvmloop_vv<br>";

						// tehd‰‰n arrayt‰ cronijobia varten
						$cron_pvm[$pointteri] = "$pvmloop_vv-$pvmloop_kk-$pvmloop_pp";
						$cron_tun[$pointteri] = "$row[laskutunnus]";

						$pointteri++;
						
						$arvoyhteensa 	+= $row["arvo"];
						$summayhteensa 	+= $row["summa"];
					}
					else {
						$laskutettu .= "$pvmloop_pp.$pvmloop_kk.$pvmloop_vv<br>";
					}
				}

			}

			echo "<td>$laskutettu</td>";
			echo "<td>$laskuttamatta</td>";
			echo "</tr>";
		}

		echo "<tr><th colspan='9'>Valitse kaikki</th><th><input type='checkbox' name='lasku' onclick='toggleAll(this);'></th></tr>";

		echo "</table>";

		if ($arvoyhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Laskuttamatta arvo yhteens‰").": </th><td align='right'>$arvoyhteensa $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Laskuttamatta summa yhteens‰").": </th><td align='right'>$summayhteensa $yhtiorow[valkoodi]</td></tr>";
			echo "</table>";
		}

		echo "<br><input type='submit' value='Laskuta'>";
		echo "</form>";

	}
	else {
		echo "Ei yll‰pitosopimuksia.";
	}

	// jos tullaan t‰‰lt‰ itsest‰ niin n‰ytet‰‰n footer
	if (strpos($_SERVER['SCRIPT_NAME'], "yllapitosopimukset.php") !== FALSE) {
		require ("../inc/footer.inc");
	}
	else {
		ob_end_clean(); // ei echota mit‰‰‰n jos kutsutaan muualta!
	}

?>
