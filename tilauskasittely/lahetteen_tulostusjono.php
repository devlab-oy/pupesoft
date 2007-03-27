<?php
	require ("../inc/parametrit.inc");

	if ($toim == 'SIIRTOLISTA') {
		$tila 		= "G";
		$lalatila	= "J";
		$tilaustyyppi = " and tilaustyyppi!='M' ";
	}
	elseif ($toim == 'SIIRTOTYOMAARAYS') {
		$tila 		= "S";
		$lalatila	= "J";
		$tilaustyyppi = " and tilaustyyppi='S' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		$tila 		= "G";
		$lalatila	= "J";
		$tilaustyyppi = " and tilaustyyppi='M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		$tila 		= "V";
		$lalatila	= "J";
		$tilaustyyppi = "";
	}
	else {
		$tila 		= "N";
		$lalatila	= "A";
		$tilaustyyppi = "";
	}

	if ($tee2 == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee2 = $vanha_tee2;
	}

	if ($tee2 == 'TULOSTA') {

		unset($tilausnumerorypas);

		if (isset($tulostukseen) and ($toim == 'VALMISTUS' or $toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS' or $toim == 'MYYNTITILI')) {
			$lask 	= 0;

			foreach ($tulostukseen as $tun) {
				$tilausnumerorypas[] = $tun;
				$lask++;
			}

			//ja niiden lukumäärä
			$laskuja = $lask;
		}
		elseif (isset($tulostukseen)) {
			$laskut	= "";
			$lask 	= 0;

			foreach ($tulostukseen as $tun) {
				$laskut .= "$tun,";
				$lask++;
			}

			//tulostettavat tilausket
			$tilausnumerorypas[] = substr($laskut,0,-1);
			//ja niiden lukumäärä
			$laskuja = $lask;
		}
		elseif(isset($tulostukseen_kaikki)) {
			$tilausnumerorypas = explode(',', $tulostukseen_kaikki);
		}

		if (is_array($tilausnumerorypas)) {

			$kerayslista = $yhtiorow["lahetteen_tulostustapa"];

			foreach($tilausnumerorypas as $tilausnumeroita) {
				// katsotaan, ettei tilaus ole kenelläkään auki ruudulla
				$query = "	SELECT *
							FROM kuka
							WHERE kesken in ($tilausnumeroita)
							and yhtio='$kukarow[yhtio]'";
				$keskenresult = mysql_query($query) or pupe_error($query);

				//jos kaikki on ok...
				if (mysql_num_rows($keskenresult)==0) {

					$query    = "	select *
									from lasku
									where tunnus in ($tilausnumeroita)
									and tila	= '$tila'
									and alatila	= '$lalatila'
									and yhtio	= '$kukarow[yhtio]'
									LIMIT 1";
					$result   = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) > 0) {

						$laskurow = mysql_fetch_array($result);

						if ($laskurow["tila"] == 'G' or $laskurow["tila"] == 'S') {
							$tilausnumero	= $laskurow["tunnus"];
							$tee			= "valmis";
							$tulostetaan	= "OK";

							require("tilaus-valmis-siirtolista.inc");

						}
						elseif ($laskurow["tila"] == 'V') {
							$tilausnumero	= $laskurow["tunnus"];
							$tulostetaan	= "OK";

							$toim_bck		= $toim;
							$toim 			= "VALMISTAVARASTOON";

							require("tilaus-valmis-siirtolista.inc");

							$toim 			= $toim_bck;
						}
						else {
							require("tilaus-valmis-tulostus.inc");
						}
					}
					else {
						echo "<font class='error'>".t("Keräyslista on jo tulostettu")."! ($tilausnumeroita)</font><br>";
					}
				}
				else {
					$keskenrow = mysql_fetch_array($keskenresult);
					echo t("Tilaus on kesken käyttäjällä").", $keskenrow[nimi], ".t("ota yhteyttä häneen ja käske hänen laittaa vähän vauhtia tähän touhuun")."!<br>";
					$tee2 = "";
				}
			}
		}
		else {
			echo "<font class='error'>".t("Et valinnut mitään tulostettavaa")."!</font><br>";
		}
		$tee2 = "";
	}

	// valiitaan keräysklöntin tilaukset jotka tulostetaan
	if ($tee2 == 'VALITSE') {

		//Haetaan sopivat tilaukset
		$query = "	SELECT lasku.tunnus, lasku.ytunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto,
					if(lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos) prioriteetti,
					if(min(lasku.clearing)='','N',if(min(lasku.clearing)='JT-TILAUS','J',if(min(lasku.clearing)='ENNAKKOTILAUS','E',''))) t_tyyppi,
					left(min(lasku.kerayspvm),10) kerayspvm,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					lasku.tunnus otunnus,
					lasku.viesti,
					count(*) riveja
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
					LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tunnus in ($tilaukset)
					$tilaustyyppi
					GROUP BY lasku.tunnus
					ORDER BY prioriteetti, kerayspvm";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre)==0) {
			$tee2 		= "";
			$tuytunnus 	= "";
			$tuvarasto	= "";
			$tumaa		= "";
		}
		else {
			// katsotaan, ettei tilaus ole kenelläkään auki ruudulla
			$query = "	SELECT *
						FROM kuka
						WHERE kesken in ($tilaukset)
						and yhtio='$kukarow[yhtio]'";
			$keskenresult = mysql_query($query) or pupe_error($query);

			//jos kaikki on ok...
			if (mysql_num_rows($keskenresult)==0) {

				if ($toim == 'SIIRTOLISTA') {
					echo "<font class='head'>".t("Tulosta siirtolista").":</font><hr>";
				}
				elseif ($toim == 'SIIRTOTYOMAARAYS') {
					echo "<font class='head'>".t("Tulosta sisäinen työmääräys").":</font><hr>";
				}
				elseif ($toim == 'VALMISTUS') {
					echo "<font class='head'>".t("Tulosta valmistuslista").":</font><hr>";
				}
				else {
					echo "<font class='head'>".t("Tulosta keräyslista").":</font><hr>";
				}


				echo "<table>";

				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='tee2' value='TULOSTA'>";

				echo "<tr>";
				echo "<th>".t("Pri")."</th>";
				echo "<th>".t("Varastoon")."</th>";
				echo "<th>".t("Tilaus")."</th>";
				echo "<th>".t("Asiakas")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Viite")."</th>";
				echo "<th>".t("Keräyspvm")."</th>";
				echo "<th>".t("Riv")."</th>";
				echo "<th>".t("Tulosta")."</th>";
				echo "<th>".t("Näytä")."</th>";
				echo "</tr>";

				$keskenlask = 0;

				while ($tilrow = mysql_fetch_array($tilre)) {

					$keskenlask ++;
					//otetaan tämä muutuja talteen
					$tul_varastoon = $tilrow["varasto"];

					echo "<tr>";

					$ero="td";
					if ($tunnus==$tilrow['otunnus']) $ero="th";

					echo "<tr>";
					echo "<$ero>$tilrow[t_tyyppi] $tilrow[prioriteetti]</$ero>";
					echo "<$ero>$tilrow[varastonimi]</$ero>";
					echo "<$ero>$tilrow[tunnus]</$ero>";
					echo "<$ero>$tilrow[ytunnus]</$ero>";

					if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
						echo "<$ero>$tilrow[nimi]</$ero>";
					}
					else {
						echo "<$ero>$tilrow[toim_nimi]</$ero>";
					}

					echo "<$ero>$tilrow[viesti]</$ero>";
					echo "<$ero>$tilrow[kerayspvm]</$ero>";
					echo "<$ero>$tilrow[riveja]</$ero>";

					echo "<$ero><input type='checkbox' name='tulostukseen[]' value='$tilrow[otunnus]' CHECKED></$ero>";

					echo "<$ero><a href='$PHP_SELF?toim=$toim&tilaukset=$tilaukset&vanha_tee2=VALITSE&tee2=NAYTATILAUS&tunnus=$tilrow[otunnus]'>".t("Näytä")."</a></$ero>";

					echo "</tr>";
				}
			}
			else {
				$keskenrow = mysql_fetch_array($keskenresult);
				echo t("Tilaus on kesken käyttäjällä").", $keskenrow[nimi], ".t("ota yhteyttä häneen ja käske hänen laittaa vähän vauhtia tähän touhuun")."!<br>";
				$tee2 = '';
			}

			if ($tee2 != '') {
				echo "</table><br>";

				//haetaan lähetteen oletustulostin
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$tul_varastoon'";
				$prires = mysql_query($query) or pupe_error($query);
				$prirow = mysql_fetch_array($prires);
				$kirjoitin = $prirow['printteri1'];

				$varasto = $tul_varastoon;
				$tilaus  = $tilaukset;

				require("varaston_tulostusalue.inc");

				echo "<form method='post' action='$PHP_SELF'>";

				$query = "	SELECT *
							FROM kirjoittimet
							WHERE
							yhtio='$kukarow[yhtio]'
							ORDER by kirjoitin";
				$kirre = mysql_query($query) or pupe_error($query);

				echo "<select name='valittu_tulostin'>";

				while ($kirrow = mysql_fetch_array($kirre)) {
					$sel = '';

					//tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
					if (($kirrow['tunnus'] == $kirjoitin and $kukarow['kirjoitin'] == 0) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				echo "</select><br><br>";
				echo "<input type='submit' name='tila' value='".t("Tulosta")."'></form>";
			}
		}
	}

	//valitaan keräysklöntti
	if ($tee2 == '') {

		if ($toim == 'SIIRTOLISTA') {
			echo "<font class='head'>".t("Tulosta siirtolista").":</font><hr>";
		}
		elseif ($toim == 'SIIRTOTYOMAARAYS') {
			echo "<font class='head'>".t("Tulosta sisäinen työmääräys").":</font><hr>";
		}
		elseif ($toim == 'VALMISTUS') {
			echo "<font class='head'>".t("Tulosta valmistuslista").":</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Tulosta keräyslista").":</font><hr>";
		}

		$formi	= "find";
		$kentta	= "etsi";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY nimitys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

		while ($row = mysql_fetch_array($result)){
			$sel = '';
			if (($row[0] == $tuvarasto) or ($kukarow['varasto'] == $row[0] and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row[0];
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != '' and yhtio = '$kukarow[yhtio]'
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)){
				$sel = '';
				if ($row[0] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row[0];
				}
				echo "<option value='$row[0]' $sel>$row[0]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$sela = $selb = $selc = "";

		if ($tutyyppi == "NORMAA") {
			$sela = "SELECTED";
		}
		if ($tutyyppi == "ENNAKK") {
			$selb = "SELECTED";
		}
		if ($tutyyppi == "JTTILA") {
			$selc = "SELECTED";
		}
		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("Näytä normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("Näytä ennakkotilausket")."</option>";
		echo "<option value='JTTILA' $selc>".t("Näytä jt-tilausket")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row[0];
			}
			echo "<option value='$row[0]' $sel>".asana('TOIMITUSTAPA_',$row[0])."</option>";
		}

		echo "</select></td>";

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.tunnus='$etsi'";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and yhtio = '$kukarow[yhtio]' and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		if($jarj != "") {
			$jarjx = " ORDER BY t_tyyppi desc, $jarj ";
		}
		else {
			$jarjx = " ORDER BY t_tyyppi desc, prioriteetti, kerayspvm ";
		}


		// Vain keräyslistat saa groupata
		if ($yhtiorow["lahetteen_tulostustapa"] == "K" and $yhtiorow["kerayslistojen_yhdistaminen"] == "Y") {
			if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
				$grouppi = "GROUP BY lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto, jvgrouppi, vientigrouppi";
			}
			else {
				//tänne on nyt toistaiseksi lisätty toim_ovttunnus. Pitää ehkä keksi jotain muuta
				$grouppi = "GROUP BY lasku.ytunnus, lasku.toim_ovttunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto, jvgrouppi, vientigrouppi";
			}
		}
		else {
			$grouppi = "GROUP BY lasku.tunnus";
		}

		$query = "	SELECT lasku.ytunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto, if(maksuehto.jv!='', lasku.tunnus, '') jvgrouppi, if(lasku.vienti!='', lasku.tunnus, '') vientigrouppi,
					if(lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos) prioriteetti,
					if(min(lasku.clearing)='','N',if(min(lasku.clearing)='JT-TILAUS','J',if(min(lasku.clearing)='ENNAKKOTILAUS','E',''))) t_tyyppi,
					left(min(lasku.kerayspvm),10) kerayspvm,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ',') otunnus,
					count(distinct otunnus) tilauksia, count(*) riveja
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
					LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
					LEFT JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = '$tila'
					and lasku.alatila = '$lalatila'
					$haku
					$tilaustyyppi
					$grouppi
					$jarjx";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre)==0) {
			echo "<br><br><font class='message'>".t("Tulostusjonossa ei ole yhtään tilausta")."...</font>";
		}
		else {
			echo "<br>";
			echo "<table>";
			echo "<tr>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=prioriteetti'>".t("Pri")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=varastonimi'>".t("Varastoon")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=lasku.ytunnus'>".t("Asiakas")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=lasku.nimi'>".t("Nimi")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=kerayspvm'>".t("Keräyspvm")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=toimitustapa'>".t("Toimitustapa")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=tilauksia'>".t("Til.")."</th>";
			echo "<th><a href='$PHP_SELF?toim=$toim&jarj=riveja'>".t("Riv")."</th>";
			echo "<th>".t("Tulostin")."</th>";
			echo "<th>".t("Tulosta")."</th>";
			echo "<th>".t("Näytä")."</th>";
			echo "</tr>";

			$tulostakaikki_tun = "";
			$edennakko = "";

			while ($tilrow = mysql_fetch_array($tilre)) {

				if ($edennakko != "" and $edennakko != $tilrow["t_tyyppi"] and $tilrow["t_tyyppi"] == "E") {
					echo "<tr><td colspan='11' class='back'><br></td></tr>";
				}

				$edennakko = $tilrow["t_tyyppi"];

				$ero="td";
				if ($tunnus==$tilrow['otunnus']) $ero="th";

				echo "<tr>";
				echo "<$ero>$tilrow[t_tyyppi] $tilrow[prioriteetti]</$ero>";
				echo "<$ero>$tilrow[varastonimi]</$ero>";
				echo "<$ero>$tilrow[ytunnus]</$ero>";

				if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
					echo "<$ero>$tilrow[nimi]</$ero>";
				}
				else {
					echo "<$ero>$tilrow[toim_nimi]</$ero>";
				}

				echo "<$ero>$tilrow[kerayspvm]</$ero>";
				echo "<$ero>$tilrow[toimitustapa]</$ero>";
				
				echo "<$ero>".str_replace(',','<br>',$tilrow["otunnus"])."</$ero>";
				
				echo "<$ero>$tilrow[riveja]</$ero>";

				if ($tilrow["tilauksia"] > 1) {
					echo "<$ero></$ero>";

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' 			value='$toim'>";
					echo "<input type='hidden' name='tee2' 			value='VALITSE'>
							<input type='hidden' name='tilaukset'	value='$tilrow[otunnus]'>
							<$ero><input type='submit' name='tila' 	value='".t("Valitse")."'></form></$ero>";

					echo "<$ero></$ero>";
					echo "</tr>";
				}
				else {
					//haetaan lähetteen oletustulostin
					$query = "	select *
								from varastopaikat
								where yhtio='$kukarow[yhtio]' and tunnus='$tilrow[varasto]'";
					$prires = mysql_query($query) or pupe_error($query);
					$prirow = mysql_fetch_array($prires);
					$kirjoitin = $prirow['printteri1'];

					$varasto = $tilrow["varasto"];
					$tilaus  = $tilrow["otunnus"];

					require("varaston_tulostusalue.inc");

					echo "<form method='post' action='$PHP_SELF'>";

					$query = "	SELECT *
								FROM kirjoittimet
								WHERE
								yhtio='$kukarow[yhtio]'
								ORDER by kirjoitin";
					$kirre = mysql_query($query) or pupe_error($query);

					echo "<$ero><select name='valittu_tulostin'>";

					while ($kirrow = mysql_fetch_array($kirre)) {
						$sel = '';

						//tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
						if (($kirrow['tunnus'] == $kirjoitin and $kukarow['kirjoitin'] == 0) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
							$sel = "SELECTED";
						}

						echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
					}

					echo "</select></$ero>";

					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='jarj' value='$jarj'>";
					echo "<input type='hidden' name='tee2' value='TULOSTA'>";
					echo "<input type='hidden' name='tulostukseen[]' value='$tilrow[otunnus]'>";
					echo "<$ero><input type='submit' value='".t("Tulosta")."'></form></$ero>";

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='jarj' value='$jarj'>";
					echo "<input type='hidden' name='tee2' value='NAYTATILAUS'>";
					echo "<input type='hidden' name='vanha_tee2' value=''>";
					echo "<input type='hidden' name='tunnus' value='$tilrow[otunnus]'>";
					echo "<$ero><input type='submit' value='".t("Näytä")."'></form></$ero>";

					echo "</tr>";
				}

				// Kerätään tunnukset tulosta kaikki-toimintoa varten
				$tulostakaikki_tun .= $tilrow["otunnus"].",";

			}
			echo "</table>";
			echo "<br>";

			echo "<table>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<tr><th colspan='2'>".t("Tulosta kaikki keräyslistat")."</th></tr>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			echo "<tr><td><select name='valittu_tulostin'>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				$sel = '';

				//tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
				if ($kirrow['tunnus'] == $kukarow['kirjoitin']) {
					$sel = "SELECTED";
				}

				echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
			}

			echo "</select></td>";

			$tulostakaikki_tun = substr($tulostakaikki_tun,0,-1);

			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='jarj' value='$jarj'>";
			echo "<input type='hidden' name='tee2' value='TULOSTA'>";
			echo "<input type='hidden' name='tulostukseen_kaikki' value='$tulostakaikki_tun'>";
			echo "<td><input type='submit' value='".t("Tulosta kaikki")."'></td></tr></form>";

			echo "</table>";

		}
	}

	require ("../inc/footer.inc");
?>