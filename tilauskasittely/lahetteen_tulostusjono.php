<?php
	require ("../inc/parametrit.inc");

	echo "
		<script>
		var DH = 0;var an = 0;var al = 0;var ai = 0;if (document.getElementById) {ai = 1; DH = 1;}else {if (document.all) {al = 1; DH = 1;} else { browserVersion = parseInt(navigator.appVersion); if ((navigator.appName.indexOf('Netscape') != -1) && (browserVersion == 4)) {an = 1; DH = 1;}}} function fd(oi, wS) {if (ai) return wS ? document.getElementById(oi).style:document.getElementById(oi); if (al) return wS ? document.all[oi].style: document.all[oi]; if (an) return document.layers[oi];}
		function pw() {return window.innerWidth != null? window.innerWidth: document.body.clientWidth != null? document.body.clientWidth:null;}
		function mouseX(evt) {if (evt.pageX) return evt.pageX; else if (evt.clientX)return evt.clientX + (document.documentElement.scrollLeft ?  document.documentElement.scrollLeft : document.body.scrollLeft); else return null;}
		function mouseY(evt) {if (evt.pageY) return evt.pageY; else if (evt.clientY)return evt.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop); else return null;}
		function popUp(evt,oi) {if (DH) {var wp = pw(); ds = fd(oi,1); dm = fd(oi,0); st = ds.visibility; if (dm.offsetWidth) ew = dm.offsetWidth; else if (dm.clip.width) ew = dm.clip.width; if (st == \"visible\" || st == \"show\") { ds.visibility = \"hidden\"; } else {tv = mouseY(evt) + 20; lv = mouseX(evt) - (ew/4); if (lv < 2) lv = 2; else if (lv + ew > wp) lv -= ew/2; if (!an) {lv += 'px';tv += 'px';} ds.left = lv; ds.top = tv; ds.visibility = \"visible\";}}}
		</script>
	";

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
					left(min(lasku.toimaika),10) toimaika,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					lasku.tunnus otunnus,
					lasku.viesti,
					lasku.sisviesti2,
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
				
				if ($kukarow['resoluutio'] == 'I') {
					echo "<th>".t("Toimaika")."</th>";
				}
				
				echo "<th>".t("Riv")."</th>";
				echo "<th>".t("Tulosta")."</th>";
				echo "<th>".t("Näytä")."</th>";
				echo "</tr>";

				$keskenlask = 0;

				while ($tilrow = mysql_fetch_array($tilre)) {

					$keskenlask ++;
					//otetaan tämä muutuja talteen
					$tul_varastoon = $tilrow["varasto"];

					echo "<tr class='aktiivi'>";

					$ero="td";
					if ($tunnus==$tilrow['otunnus']) $ero="th";

					echo "<tr>";
					if(trim($tilrow["sisviesti2"]) != "") {
						echo "<div id='$tilrow[tunnus]' style='position:absolute; z-index:100; visibility:hidden; width:500px; background:#555555; color:#FFFFFF; border: 1px solid; padding:5px;'>";
						echo t("Lisätiedot").":<br>";
						echo $tilrow["sisviesti2"];
						echo "</div>";
						echo "<$ero valign='top'><a class='menu' onmouseout=\"popUp(event,'$tilrow[tunnus]')\" onmouseover=\"popUp(event,'$tilrow[otunnus]')\">$tilrow[t_tyyppi] $tilrow[prioriteetti]</a></$ero>";
					}
					else {
						echo "<$ero valign='top'>$tilrow[t_tyyppi] $tilrow[prioriteetti]</$ero>";
					}
					
					echo "<$ero valign='top'>$tilrow[varastonimi]</$ero>";
					echo "<$ero valign='top'>$tilrow[tunnus]</$ero>";
					echo "<$ero valign='top'>$tilrow[ytunnus]</$ero>";

					if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
						echo "<$ero valign='top'>$tilrow[nimi]</$ero>";
					}
					else {
						echo "<$ero valign='top'>$tilrow[toim_nimi]</$ero>";
					}

					echo "<$ero valign='top'>$tilrow[viesti]</$ero>";
					echo "<$ero valign='top'>".tv1dateconv($tilrow["kerayspvm"])."</$ero>";
					
					if ($kukarow['resoluutio'] == 'I') {
						echo "<$ero valign='top'>".tv1dateconv($tilrow["toimaika"])."</$ero>";
					}
					
					echo "<$ero valign='top'>$tilrow[riveja]</$ero>";

					echo "<$ero valign='top'><input type='checkbox' name='tulostukseen[]' value='$tilrow[otunnus]' CHECKED></$ero>";

					echo "<$ero valign='top'><a href='$PHP_SELF?toim=$toim&tilaukset=$tilaukset&vanha_tee2=VALITSE&tee2=NAYTATILAUS&tunnus=$tilrow[otunnus]'>".t("Näytä")."</a></$ero>";

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
					left(min(lasku.toimaika),10) toimaika,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ',') otunnus,
					count(distinct otunnus) tilauksia, count(*) riveja,
					GROUP_CONCAT(distinct(if(sisviesti2 != '', concat(lasku.tunnus,' - ', sisviesti2,'<br>'), NULL)) SEPARATOR '') ohjeet
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
			
			if ($kukarow['resoluutio'] == 'I') {
				echo "<th><a href='$PHP_SELF?toim=$toim&jarj=toimaika'>".t("Toimaika")."</th>";
			}
			
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

				echo "<tr class='aktiivi'>";
				if(trim($tilrow["ohjeet"]) != "") {
					echo "<div id='$tilrow[otunnus]' style='position:absolute; z-index:100; visibility:hidden; width:500px; background:#555555; color:#FFFFFF; border: 1px solid; padding:5px;'>";
					echo t("Lisätiedot").":<br>";
					echo $tilrow["ohjeet"];
					echo "</div>";
					echo "<$ero valign='top'><a class='menu' onmouseout=\"popUp(event,'$tilrow[otunnus]')\" onmouseover=\"popUp(event,'$tilrow[otunnus]')\">$tilrow[t_tyyppi] $tilrow[prioriteetti]</a></$ero>";
				}
				else {
					echo "<$ero valign='top'>$tilrow[t_tyyppi] $tilrow[prioriteetti]</$ero>";
				}
				
				echo "<$ero valign='top'>$tilrow[varastonimi]</$ero>";
				echo "<$ero valign='top'>$tilrow[ytunnus]</$ero>";

				if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
					echo "<$ero valign='top'>$tilrow[nimi]</$ero>";
				}
				else {
					echo "<$ero valign='top'>$tilrow[toim_nimi]</$ero>";
				}

				echo "<$ero valign='top'>".tv1dateconv($tilrow["kerayspvm"])."</$ero>";
				
				if ($kukarow['resoluutio'] == 'I') {
					echo "<$ero valign='top'>".tv1dateconv($tilrow["toimaika"])."</$ero>";
				}
				
				echo "<$ero valign='top'>$tilrow[toimitustapa]</$ero>";
				
				echo "<$ero valign='top'>".str_replace(',','<br>',$tilrow["otunnus"])."</$ero>";
				
				echo "<$ero valign='top'>$tilrow[riveja]</$ero>";

				if ($tilrow["tilauksia"] > 1) {
					echo "<$ero valign='top'></$ero>";

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' 			value='$toim'>";
					echo "<input type='hidden' name='tee2' 			value='VALITSE'>
							<input type='hidden' name='tilaukset'	value='$tilrow[otunnus]'>
							<$ero valign='top'><input type='submit' name='tila' 	value='".t("Valitse")."'></form></$ero>";

					echo "<$ero valign='top'></$ero>";
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

					echo "<$ero valign='top'><select name='valittu_tulostin'>";

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
					echo "<input type='hidden' name='tuvarasto' value='$tuvarasto'>";				
					echo "<input type='hidden' name='tumaa' 	value='$tumaa'>";
					echo "<input type='hidden' name='tutyyppi' value='$tutyyppi'>";
					echo "<input type='hidden' name='tutoimtapa' value='$tutoimtapa'>";										
					echo "<input type='hidden' name='etsi' value='$etsi'>";										
					echo "<input type='hidden' name='tee2' value='TULOSTA'>";
					echo "<input type='hidden' name='tulostukseen[]' value='$tilrow[otunnus]'>";
					echo "<$ero valign='top'><input type='submit' value='".t("Tulosta")."'></form></$ero>";

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='jarj' value='$jarj'>";
					echo "<input type='hidden' name='tuvarasto' value='$tuvarasto'>";				
					echo "<input type='hidden' name='tumaa' 	value='$tumaa'>";
					echo "<input type='hidden' name='tutyyppi' value='$tutyyppi'>";
					echo "<input type='hidden' name='tutoimtapa' value='$tutoimtapa'>";										
					echo "<input type='hidden' name='etsi' value='$etsi'>";										
					echo "<input type='hidden' name='tee2' value='NAYTATILAUS'>";
					echo "<input type='hidden' name='vanha_tee2' value=''>";
					echo "<input type='hidden' name='tunnus' value='$tilrow[otunnus]'>";
					echo "<$ero valign='top'><input type='submit' value='".t("Näytä")."'></form></$ero>";

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
