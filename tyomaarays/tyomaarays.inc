<?php

	if ($tee ==  "VALMIS" or $tee == "LEPAA") {
		//tulostetaan tilaus kun se on valmis
		$otunnus = $laskurow["tunnus"];

		if ($toim != "REKLAMAATIO") {
			$tulostimet[0] = "Tyˆm‰‰r‰ys";
			require_once ("tyomaarays/tulosta_tyomaarays.inc");
		}
		else {
			$tulostimet[0] = "Reklamaatio";
			require_once ("tilauskasittely/tulosta_lahete_kerayslista.inc");
		}

		if (count($komento) == 0) {
			echo "<font class='head'>$tulostimet[0]:</font><hr>";
			require("inc/valitse_tulostin.inc");
		}
		elseif ($kappaleet > 0) {
			if ($toim != "REKLAMAATIO" and $komento["Tyˆm‰‰r‰ys"] != "email" and $tyomtyyppi != 'Z') {
				$komento["Tyˆm‰‰r‰ys"] .= " -# $kappaleet ";
			}
			elseif ($komento["Reklamaatio"] != "email") {
				$komento["Reklamaatio"] .= " -# $kappaleet ";
			}
		}

		if ($laskurow["tila"] == 'U') {
			$where = " uusiotunnus='$laskurow[tunnus]' ";
		}
		else {
			$where = " otunnus='$laskurow[tunnus]' ";
		}

		// Haetaan asiakkaan tiedot
		$query = "  SELECT luokka,
					puhelin,
					if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro,
					asiakasnro as asiakasnro_aito,
					kerayserat,
					kieli
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);
		$asrow = mysql_fetch_array($result);

		// katotaan miten halutaan sortattavan
		if ($toim == "REKLAMAATIO") {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
			else $pjat_sortlisa = "";

		}
		else {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["tyomaarayksen_jarjestys"]);
			$order_sorttaus = $yhtiorow["tyomaarayksen_jarjestys_suunta"];

			if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
			else $pjat_sortlisa = "";
		}

		$query_ale_lisa = generoi_alekentta('M');

		//tyˆm‰‰r‰yksen rivit
		$query = "  SELECT tilausrivi.*,
					round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
					tuote.sarjanumeroseuranta,
					$sorttauskentta,
					if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi,
					if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
					tuote.sarjanumeroseuranta,
					tuote.eankoodi
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio 	= '$kukarow[yhtio]'
					and tilausrivi.tyyppi  != 'D'
					and tilausrivi.yhtio 	= tuote.yhtio
					and tilausrivi.tuoteno  = tuote.tuoteno
					ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		$riresult = pupe_query($query);

		$tilausnumeroita = $otunnus;

		//generoidaan rivinumerot
		$rivinumerot = array();

		$kal = 1;

		while ($row = mysql_fetch_array($riresult)) {
			$rivinumerot[$row["tunnus"]] = $kal;
			$kal++;
		}

		mysql_data_seek($riresult,0);

		// Aloitellaan l‰hetteen teko
		if ($toim != "REKLAMAATIO") {

			if ($tyomtyyppi == 'Z') {
				tulosta_tyomaaraystarra_zebra($laskurow, $komento[$tulostimet[0]], $kappaleet);
			}
			else {
				if ($toim == "SIIRTOTYOMAARAYS") {
					$tyyppi = "SISAINEN";
				}
				elseif ((isset($tyomtyyppi) and $tyomtyyppi == "O") or $kukarow['hinnat'] != 0) {
					$tyyppi = "O";
				}
				elseif (isset($tyomtyyppi) and $tyomtyyppi == "P") {
					$tyyppi = "P";
				}
				elseif (isset($tyomtyyppi) and $tyomtyyppi == "A") {
					$tyyppi = "";
				}
				else {
					$tyyppi = $yhtiorow["tyomaaraystyyppi"];
				}

				$params_tyomaarays = array( "asrow"           => $asrow,
											"boldi"           => $boldi,
											"edtuotetyyppi"   => "",
											"iso"             => $iso,
											"kala"            => 0,
											"kieli"           => $kieli,
											"komento"		  => $komento[$tulostimet[0]],
											"laskurow"        => $laskurow,
											"lineparam"       => $lineparam,
											"norm"            => $norm,
											"page"            => NULL,
											"pdf"             => NULL,
											"perheid"         => 0,
											"perheid2"        => 0,
											"pieni"           => $pieni,
											"pieni_boldi"     => $pieni_boldi,
											"rectparam"       => $rectparam,
											"returnvalue"     => 0,
											"rivinkorkeus"    => $rivinkorkeus,
											"rivinumerot"     => $rivinumerot,
											"row"             => NULL,
											"sivu"            => 1,
											"tee"             => $tee,
											"thispage"		  => NULL,
											"toim"            => $toim,
											"tots"			  => 0,
											"tyyppi"          => $tyyppi,);

				// Aloitellaan lomakkeen teko
				$params_tyomaarays = tyomaarays_alku($params_tyomaarays);

				if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "") {
					// Ekan sivun otsikot
					$params_tyomaarays['kala'] -= $params_tyomaarays['rivinkorkeus']*3;
					$params_tyomaarays = tyomaarays_rivi_otsikot($params_tyomaarays);
				}

				while ($row = mysql_fetch_assoc($riresult)) {
					$params_tyomaarays["row"] = $row;
					$params_tyomaarays = tyomaarays_rivi($params_tyomaarays);
				}

				if (($toim == 'TYOMAARAYS_ASENTAJA' or $toim == 'TYOMAARAYS') and $yhtiorow['tyomaarays_tulostus_lisarivit'] == 'L') {
					$params_tyomaarays["tots"] = 1;
					$params_tyomaarays = tyomaarays_loppu_lisarivit($params_tyomaarays);
				}
				else {
					$params_tyomaarays["tots"] = 1;
					$params_tyomaarays = tyomaarays_loppu($params_tyomaarays);
				}

				//tulostetaan sivu
				tyomaarays_print_pdf($params_tyomaarays);
			}
		}
		else {
			$tyyppi = "REKLAMAATIO";

			$params_kerayslista = array(
			'asrow'           	=> $asrow,
			'boldi'           	=> $boldi,
			'iso'             	=> $iso,
			'kala'            	=> 0,
			'kieli'           	=> $kieli,
			'komento'			=> '',
			'laskurow'        	=> $laskurow,
			'norm'            	=> $norm,
			'page'            	=> '',
			'paino'           	=> 0,
			'pdf'             	=> NULL,
			'perheid'         	=> 0,
			'perheid2'        	=> 0,
			'pieni'           	=> $pieni,
			'pieni_boldi'     	=> $pieni_boldi,
			'rectparam'       	=> $rectparam,
			'rivinkorkeus'    	=> $rivinkorkeus,
			'rivinumerot'    	=> $rivinumerot,
			'row'             	=> NULL,
			'sivu'            	=> 1,
			'tee'             	=> $tee,
			'thispage'			=> NULL,
			'tilausnumeroita' 	=> $tilausnumeroita,
			'toim'            	=> $toim,
			'tots'            	=> 0,
			'tyyppi'		  	=> $tyyppi,
			'kerayslistatyyppi'	=> '');


			// Aloitellaan ker‰yslistan teko
			$params_kerayslista = alku_kerayslista($params_kerayslista);

			while ($row = mysql_fetch_assoc($riresult)) {
				$params_kerayslista["row"] = $row;
				$params_kerayslista = rivi_kerayslista($params_kerayslista);
			}

			$params_kerayslista["tots"] = 1;
			$params_kerayslista = loppu_kerayslista($params_kerayslista);

			//tulostetaan sivu
			$params_kerayslista["komento"] = $komento[$tulostimet[0]];
			print_pdf_kerayslista($params_kerayslista);
		}
	}

	if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO") and $tee == 'LEPAA') {
		$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
		$result = pupe_query($query);

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';

		if ($lopetus != '') {
			lopetus($lopetus, "META");
		}
	}

	if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $tee == 'VALMIS') {
		$query  = "	UPDATE lasku
					SET tila='L'
					WHERE tunnus='$laskurow[tunnus]'
					and tila='A'
					and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		//Katotaan lˆytyykˆ tilausrivej‰
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and tyyppi != 'D'";
		$keres = pupe_query($query);

		//Yht‰‰n rivi‰ ei ole
		if(mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			// tilaus ei en‰‰ kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = pupe_query($query);
		}
		else {
			require("tilauskasittely/tilaus-valmis.inc");
		}
		$tee = "";
	}

	if ($toim == "REKLAMAATIO" and $tee == 'VALMIS') {
		$query  = "	UPDATE lasku
					SET tila = 'L'
					WHERE tunnus = '$laskurow[tunnus]'
					and tila = 'C'
					and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		//Katotaan lˆytyykˆ tilausrivej‰
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and tyyppi != 'D'";
		$keres = pupe_query($query);

		//Yht‰‰n rivi‰ ei ole
		if (mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			// tilaus ei en‰‰ kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = pupe_query($query);
		}
		else {
			require("tilauskasittely/tilaus-valmis.inc");
		}
		$tee = "";
	}

	if ($toim == "SIIRTOTYOMAARAYS" and $tee == 'VALMIS') {
		$query  = "	UPDATE lasku
					SET alatila = 'J'
					WHERE tunnus = '$laskurow[tunnus]'
					and tila = 'S'
					and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		//Katotaan lˆytyykˆ tilausrivej‰
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and tyyppi != 'D'";
		$keres = pupe_query($query);

		//Yht‰‰n rivi‰ ei ole
		if (mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			// tilaus ei en‰‰ kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = pupe_query($query);
		}
		else {
			require("tilauskasittely/tilaus-valmis-siirtolista.inc");
		}

		$tee = "";
	}

	if ($toim == "TYOMAARAYS" and $tee == 'HUOLTO' and $malli != '' and $huolto != '') {

		$query = "	SELECT tuoteno, maara
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]' and malli='$malli' and huolto='$huolto'";
		$result = mysql_query ($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/MiKrOtIlAuS-".md5(uniqid(mt_rand(), true)).".txt";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");

			while($row = mysql_fetch_array($result)) {
				fwrite($fh, "$row[tuoteno]\t$row[maara]\n");
			}
			fclose($fh);

			$_FILES['userfile']['tmp_name'] = $pdffilenimi;
			$_FILES['userfile']['size'] = 100;
			$_FILES['userfile']['name'] = $pdffilenimi;
			$tee = "file";
		}
		else {
			$tee = "";
		}
	}
	elseif ($toim == "TYOMAARAYS" and $tee == 'HUOLTO') {
		$tee = "";
	}

	if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $tee == "VAHINKO") {

		$polku = "kuvat/";

		if ($tila == "") {

			echo "<font class='head'>",t("Tyˆm‰‰r‰yksen")," $kukarow[kesken] ",t("vahinkotiedot"),":</font><hr>";

			$query = "	select *
						from vahinkotiedot
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$kukarow[kesken]'";
			$tresult = pupe_query($query);
			$row = mysql_fetch_array($tresult);


			echo "	<br><br><table>";
			echo "	<form method='post' enctype='multipart/form-data'>
					<input type='hidden' name='tee' value = 'VAHINKO'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='vahtunnus' value='$row[tunnus]'>
					<input type='hidden' name='tila' value = 'LISAA'>


					<tr><th>",t("Vak.yht. korjattava"),":</th><td><input type='text' name='vakuutus' size='38' value='$row[vakuutus]'></td></tr>
					<tr><th>",t("Tapahtuma"),":</th><td><input type='text' name='tapahtuma' size='38' value='$row[tapahtuma]'></td></tr>
					<tr><th>",t("Vahinkop‰iv‰"),":</th><td><input type='text' name='paivamaara' size='38' value='$row[paivamaara]'></td></tr>
					<tr><th>",t("Vastapuoli"),":</th><td><input type='text' name='vastapuoli' size='38' value='$row[vastapuoli]'></td></tr>
					<tr><th>",t("Tarkastaja"),":</th><td><input type='text' name='tarkastaja' size='38' value='$row[tarkastaja]'></td></tr>
					<tr><th>",t("Lupa/omavastuu"),":</th><td><input type='text' name='omavastuu_lupa' size='38' value='$row[omavastuu_lupa]'></td></tr>
					<tr><th>",t("Korjausaika"),":</th><td><input type='text' name='korjausaika' size='38' value='$row[korjausaika]'></td></tr>

					<tr><th>",t("Liit‰")," ",t("kuva")," 1:</th><td>$row[kuva1] <input name='userfile1' type='file'></td></tr>
					<tr><th>",t("Liit‰")," ",t("kuva")," 2:</th><td>$row[kuva2] <input name='userfile2' type='file'></td></tr>
					<tr><th>",t("Liit‰")," ",t("kuva")," 3:</th><td>$row[kuva3] <input name='userfile3' type='file'></td></tr>
					<tr><th>",t("Liit‰")," ",t("kuva")," 4:</th><td>$row[kuva4] <input name='userfile4' type='file'></td></tr>
					<tr><th>",t("Liit‰")," ",t("kuva")," 5:</th><td>$row[kuva5] <input name='userfile5' type='file'></td><td><input type='submit' value='Lis‰‰'></td></tr>
					</form>
					</table><br><br>";

					if ($row["kuva1"] != '')
						echo "<img src='kuvat/$row[kuva1]'><br>";

					if ($row["kuva2"] != '')
						echo "<img src='kuvat/$row[kuva2]'><br>";

					if ($row["kuva3"] != '')
						echo "<img src='kuvat/$row[kuva3]'><br>";

					if ($row["kuva4"] != '')
						echo "<img src='kuvat/$row[kuva4]'><br>";

					if ($row["kuva5"] != '')
						echo "<img src='kuvat/$row[kuva5]'>";

		}


		if ($tila == "LISAA") {

			if ($vahtunnus > 0) {
				$query = "	UPDATE vahinkotiedot
							SET
							vakuutus = '$vakuutus',
							tapahtuma = '$tapahtuma',
							paivamaara = '$paivamaara',
							vastapuoli = '$vastapuoli',
							tarkastaja = '$tarkastaja',
							omavastuu_lupa = '$omavastuu_lupa',
							korjausaika = '$korjausaika'
							WHERE
							yhtio = '$kukarow[yhtio]'
							and otunnus = '$kukarow[kesken]'
							and tunnus = '$vahtunnus'";
			}
			else {
				$query = "	INSERT into vahinkotiedot
							SET
							yhtio = '$kukarow[yhtio]',
							vakuutus = '$vakuutus',
							tapahtuma = '$tapahtuma',
							paivamaara = '$paivamaara',
							vastapuoli = '$vastapuoli',
							tarkastaja = '$tarkastaja',
							omavastuu_lupa = '$omavastuu_lupa',
							korjausaika = '$korjausaika',
							otunnus = '$kukarow[kesken]'";
			}
			$result = pupe_query($query);

			for ($i=1; $i<=5; $i++) {
				if (is_uploaded_file($_FILES['userfile'.$i]['tmp_name']) == TRUE) {

					$path_parts = pathinfo($_FILES['userfile'.$i]['name']);
					$name	= strtoupper($path_parts['filename']);
					$ext	= strtoupper($path_parts['extension']);

					if ($ext != "JPG" and $ext != "GIF") {
						echo "<font class='error'><br>".t("Ainoastaan .jpg tai .gif tiedostot sallittuja")."!</font>";
					}
					else {
						if ($_FILES['userfile'.$i]['size']==0){
							echo "<font class='error'><br>".t("Tiedosto oli tyhj‰")."!</font>";
						}
						else {
							move_uploaded_file($_FILES['userfile'.$i]['tmp_name'], $polku.$_FILES['userfile'.$i]['name']);

							$query = "	UPDATE vahinkotiedot
										SET
										kuva$i 	= '$name.$ext'
										WHERE
										yhtio 	= '$kukarow[yhtio]'
										and otunnus = '$kukarow[kesken]'";
							$result = mysql_query ($query) or pupe_error($query);
						}
					}
				}
			}
			$tee = "";
		}
	}

	if ($toim == "TYOMAARAYS" and $tee_tyomaarays == "MAARAAIKAISHUOLLOT") {
		//m‰‰r‰aikaishuollot
		echo "<table>";
		echo "<form method='post'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='HUOLTO'>";

		echo "<tr><th>Valitse malli:</th>";
		echo "<td><select name='malli'><option value='menu'>Valitse malli </option>";

		$query = "	SELECT distinct malli
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]'
					ORDER by malli";
		$result = mysql_query ($query) or pupe_error($query);

		while($trow = mysql_fetch_array($result)) {

			$sel = "";

			if ($laskurow["merkki"] == $trow["malli"] or $malli == $trow["malli"]) {
				$sel = "SELECTED";
				$malli = $trow["malli"];
			}

			echo "<option value='$trow[malli]' $sel>$trow[malli]</option>";
		}
		echo "</select></td>";

		if ($malli != '') {
			$query = "	SELECT distinct malli, huolto
						FROM huollot
						WHERE yhtio='$kukarow[yhtio]' and malli='$malli'
						ORDER by malli, huolto";
			$result = mysql_query ($query) or pupe_error($query);

			if(mysql_num_rows($result) != 0){
				echo "<td><select name='huolto'>";

				while($trow = mysql_fetch_array($result)) {

					$sel = "";

					if ($huolto == $trow["huolto"]) {
						$sel = "SELECTED";
					}

					echo "<option value='$trow[huolto]' $sel>$trow[huolto]</option>";
				}

				echo "</td>";
			}
			echo "</select></td><td><input type='Submit' value = 'Lis‰‰'></form></td></tr>";
		}
		else {
			echo "<td><input type='Submit' value = 'Valitse'></form></td></tr>";
		}

		echo "</table><br>";
	}
