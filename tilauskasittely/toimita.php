<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Toimita tilaus").":</font><hr>";

	if ($tee == 'maksu') {
		if ($seka == '') {
			$tee == 'P';
		}
	}

	if($tee=='P') {

		// jos kyseessä ei ole nouto tai noutajan nimi on annettu, voidaan merkata tilaus toimitetuksi..
		if (($nouto != 'yes') or ($noutaja != '')) {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]',
						toimitettuaika = now() 
						WHERE otunnus = '$otunnus' 
						and var not in ('P','J') 
						and yhtio = '$kukarow[yhtio]' 
						and keratty != ''
						and tyyppi = 'L'";
			$result = mysql_query($query) or pupe_error($query);

			if (isset($vaihdakateista) and $vaihdakateista == "KYLLA") {
				$katlisa = ", kassalipas = '$kassalipas', maksuehto = '$maksutapa'";
			}
			else {
				$katlisa = "";
			}

			$query = "	UPDATE lasku 
						set alatila = 'D', 
						noutaja = '$noutaja'
						$katlisa
						WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// jos kyseessä on käteismyyntiä, tulostetaaan käteislasku
			$query  = "SELECT * from lasku, maksuehto where lasku.tunnus='$otunnus' and lasku.yhtio='$kukarow[yhtio]' and maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto";
			$result = mysql_query($query) or pupe_error($query);
			$tilrow = mysql_fetch_array($result);

			// jos kyseessä on käteiskauppaa ja EI vientiä, laskutetaan ja tulostetaan tilaus..
			if ($tilrow['kateinen']!='' and $tilrow["vienti"]=='') {
				
				//tulostetaan käteislasku...				
				$laskutettavat	= $otunnus;
				$tee 			= "TARKISTA";
				$laskutakaikki 	= "KYLLA";
				$silent		 	= "KYLLA";
				
				if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == "") {
					$valittu_tulostin = $kukarow["kirjoitin"];
				}
				elseif($valittu_tulostin == "") {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}
								
				require ("verkkolasku.php");
			}

			$id=0;
		}
		else {
			$id=$otunnus;
			$virhe="<font class='error'>".t("Noutajan nimi on syötettävä")."!</font><br><br>";
		}
	}

	if ($id=='') $id=0;

	// meillä ei ole valittua tilausta
	if ($id=='0') {
		$formi="find";
		$kentta="etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		$query = "	select distinct otunnus
					from tilausrivi, lasku, toimitustapa
					where tilausrivi.yhtio='$kukarow[yhtio]' 
					and lasku.yhtio='$kukarow[yhtio]' 
					and lasku.tunnus=tilausrivi.otunnus 
					and lasku.tila='L' 
					and (lasku.alatila='C' or alatila='B') 
					and toimitustapa.selite=lasku.toimitustapa 
					and toimitustapa.nouto!='' 
					and toimitettu='' 
					and keratty!='' 
					and vienti=''";
		$tilre = mysql_query($query) or pupe_error($query);

		while ($tilrow = mysql_fetch_array($tilre)) {
			// etsitään sopivia tilauksia
			$query = "	SELECT lasku.tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, maksuehto.teksti maksuehto, toimitustapa, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija, toimaika
						FROM lasku
						LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio AND maksuehto.tunnus = lasku.maksuehto)
						WHERE lasku.tunnus='$tilrow[0]' and tila='L' $haku and lasku.yhtio='$kukarow[yhtio]' and (alatila='C' or alatila='B') ORDER by laadittu desc";
			$result = mysql_query($query) or pupe_error($query);

			//piirretään taulukko...
			if (mysql_num_rows($result)!=0) {
				while ($row = mysql_fetch_array($result)) {
					// piirretään vaan kerran taulukko-otsikot
					if ($boob=='') {
						$boob='kala';
						echo "<table>";
						echo "<tr>";
						for ($i=0; $i<mysql_num_fields($result); $i++)
							echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
						echo "</tr>";
					}

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result); $i++)
						if (mysql_field_name($result,$i) == 'laadittu' or mysql_field_name($result,$i) == 'toimaika') {
							echo "<td>".tv1dateconv($row[$i])."</td>";
						}
						else {
							echo "<td>$row[$i]</td>";
						}

					echo "<form method='post' action='$PHP_SELF'><td class='back'>
						  <input type='hidden' name='id' value='$row[0]'>
						  <input type='submit' name='tila' value='".t("Toimita")."'></td></tr></form>";
				}
			}
		}

		if ($boob!='')
			echo "</table>";
		else
			echo "<font class='message'>".t("Yhtään toimitettavaa tilausta ei löytynyt")."...</font>";
	}



	if($id != '0') {
		$query = "	SELECT *, concat_ws(' ',lasku.nimi, nimitark) nimi,  
					lasku.osoite, concat_ws(' ', lasku.postino, lasku.postitp) postitp, 
					toim_osoite, concat_ws(' ', toim_postino, toim_postitp) toim_postitp,
					lasku.tunnus laskutunnus
					FROM lasku, maksuehto
					WHERE lasku.tunnus='$id' and lasku.yhtio='$kukarow[yhtio]' and tila='L' and (alatila='C' or alatila='B')
					and maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0){
			die(t("Tilausta")." $id ".t("ei voida toimittaa, koska kaikkia tilauksen tietoja ei löydy! Uuuuuuuhhhhhhh")."!");
		}

		$row    = mysql_fetch_array($result);

		echo "<table>";
		echo "<tr><th>" . t("Tilaus") ."</th><td>$row[laskutunnus]</td></tr>";	
		echo "<tr><th>" . t("Asiakas") ."</th><td>$row[nimi]<br>$row[toim_nimi]</td></tr>";
		echo "<tr><th>" . t("Laskutusosoite") ."</th><td>$row[osoite], $row[postitp]</td></tr>";
		echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$row[toim_osoite], $row[toim_postitp]</td></tr>";
		echo "<tr><th>" . t("Maksuehto") ."</th><td>$row[teksti]</td></tr>";	
		echo "<tr><th>" . t("Toimitustapa") ."</th><td>$row[toimitustapa]</td></tr>";		
		echo "</table><br><br>";

		if ($row["valkoodi"] != '' and trim(strtoupper($row["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $row["vienti_kurssi"] != 0) {
			$hinta_riv = "(tilausrivi.hinta/$row[vienti_kurssi])";
		}
		else {
			$hinta_riv = "tilausrivi.hinta";
		}

		$lisa = " 	$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$row[erikoisale]-(tilausrivi.ale*$row[erikoisale]/100))/100)) rivihinta, 
					(tilausrivi.varattu+tilausrivi.kpl) kpl ";

		$query = "	SELECT concat_ws(' ',hyllyalue, hyllynro, hyllytaso, hyllyvali) varastopaikka, concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno, varattu, concat_ws('@',keratty,kerattyaika) keratty, tilausrivi.tunnus, var, $lisa
					FROM tilausrivi, tuote
					WHERE tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and var!='J' and otunnus = '$id' and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY varastopaikka";

		$result = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($result);

		echo "	<table cellpadding='2' cellspacing='1' border='0'>
				<tr>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Tuoteno")."</th>
				<th>".t("Kpl")."</th>
				<th>".t("Kerätty")."</th>
				</tr>";


		while($rivi = mysql_fetch_array($result)) {
			if ($rivi['var']=='P') $rivi['varattu']=t("*puute*");

			echo "<tr><td>$rivi[varastopaikka]</td>
					<td>$rivi[tuoteno]</td>
					<td>$rivi[varattu]</td>
					<td>$rivi[keratty]</td>
					</tr>";
		}

		echo "</table><br>";

		$query = "SELECT * FROM toimitustapa WHERE yhtio='$kukarow[yhtio]' AND selite='$row[toimitustapa]'";
		$tores = mysql_query($query) or pupe_error($query);
		$toita = mysql_fetch_array($tores);

		echo "<form name = 'rivit' method='post' action='$PHP_SELF'>
				<input type='hidden' name='otunnus' value='$id'>
				<input type='hidden' name='tee' value='P'>";


		if ($toita['nouto'] != '' and $row['kateinen'] != '') {

			echo "<table><tr><th>".t("Valitse kassalipas")."</th><td>";

			$query = "SELECT * FROM kassalipas WHERE yhtio='{$kukarow['yhtio']}'";
			$kassares = mysql_query($query) or pupe_error($query);
			
			echo "<input type='hidden' name='noutaja' value=''>";
			echo "<input type='hidden' name='vaihdakateista' value='KYLLA'>";
			echo "<select name='kassalipas'>";
			echo "<option value=''>".t("Ei kassalipasta")."</option>";

			$sel = "";
						
			while ($kassarow = mysql_fetch_array($kassares)) {
				if ($kassalipas == $kassarow["tunnus"]) {
					$sel = "selected";
				}
				
				echo "<option value='{$kassarow['tunnus']}' $sel>{$kassarow['nimi']}</option>";
				
				$sel = "";
			}
			echo "</select>";
			echo "</td></tr>";

			$query_maksuehto = "SELECT *
								FROM maksuehto
								WHERE yhtio='$kukarow[yhtio]' 
								and kateinen != '' 
								and kaytossa = '' 
								and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$row[maa]%') 
								ORDER BY tunnus";
			$maksuehtores = mysql_query($query_maksuehto) or pupe_error($query_maksuehto);

			if (mysql_num_rows($maksuehtores) > 1) {
				echo "<table><tr><th>".t("Maksutapa")."</th><td>";

				echo "<select name='maksutapa'>";

				while ($maksuehtorow = mysql_fetch_array($maksuehtores)) {
					
					$sel = "";
					
					if ($maksuehtorow["tunnus"] == $row["maksuehto"]) {
						$sel = "selected";
					}
					echo "<option value='$maksuehtorow[tunnus]' $sel>{$maksuehtorow['teksti']} {$maksuehtorow['kassa_teksti']}</option>";
				}

				echo "</select>";
				echo "</td></tr></table>";
				
			}
			else {
				$maksuehtorow = mysql_fetch_array($maksuehtores);
				echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
			}
			echo "</table><br>";
		}
		
		if ($toita['nouto']!='' and $row['kateinen']=='') {
			// jos kyseessä on nouto jota *EI* makseta käteisellä, kysytään noutajan nimeä..	
			echo "<table><tr><th>".t("Syötä noutajan nimi")."</th></tr>";
			echo "<tr><td><input size='60' type='text' name='noutaja'></td></tr></table><br>";
			echo "<input type='hidden' name='nouto' value='yes'>";
			echo "<input type='hidden' name='kassalipas' value=''>";

			//kursorinohjausta
			$formi="rivit";
			$kentta="noutaja";
		}

		echo "$virhe";
		echo "<input type='submit' value='".t("Merkkaa toimitetuksi")."'></form>";
	}

	require "../inc/footer.inc";
?>
