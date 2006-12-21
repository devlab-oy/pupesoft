<?php
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Vientitilauksen lisätietojen syöttö")."</font><hr>";

	if ($tee == 'L') {

		list($poistumistoimipaikka, $poistumistoimipaikka_koodi) = split("##", $poistumistoimipaikka, 2);

		$otunnus = substr($otunnus,0,-1);

		if ($aktiivinen_kuljetus_kansallisuus == '') {
			$aktiivinen_kuljetus_kansallisuus = $sisamaan_kuljetus_kansallisuus;
		}

		$aktiivinen_kuljetus_kansallisuus = strtoupper($aktiivinen_kuljetus_kansallisuus);
		$sisamaan_kuljetus_kansallisuus = strtoupper($sisamaan_kuljetus_kansallisuus);
		$maa_maara = strtoupper($maa_maara);


		$otunnukset = explode(',',$otunnus);

		foreach($otunnukset as $otun) {
			$query = "	SELECT sum(kilot) kilot
						FROM rahtikirjat
						WHERE otsikkonro = '$otun' and yhtio='$kukarow[yhtio]'";
			$result   = mysql_query($query) or pupe_error($query);
			$rahtirow = mysql_fetch_array ($result);

			$query = "	UPDATE lasku
						SET maa_maara 					= '$maa_maara',
						kauppatapahtuman_luonne 		= '$kauppatapahtuman_luonne',
						kuljetusmuoto 					= '$kuljetusmuoto',
						sisamaan_kuljetus 				= '$sisamaan_kuljetus',
						sisamaan_kuljetusmuoto  		= '$sisamaan_kuljetusmuoto',
						sisamaan_kuljetus_kansallisuus 	= '$sisamaan_kuljetus_kansallisuus',
						kontti  						= '$kontti',
						aktiivinen_kuljetus 			= '$aktiivinen_kuljetus',
						aktiivinen_kuljetus_kansallisuus= '$aktiivinen_kuljetus_kansallisuus',
						poistumistoimipaikka 			= '$poistumistoimipaikka',
						poistumistoimipaikka_koodi 		= '$poistumistoimipaikka_koodi',
						bruttopaino 					= '$rahtirow[kilot]',
						lisattava_era 					= '$lisattava_era',
						vahennettava_era 				= '$vahennettava_era'
						WHERE tunnus = '$otun' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			//päivitetään alatila vain jos tilaus ei vielä ole laskutettu
			$query = "	UPDATE lasku
						SET alatila = 'E'
						WHERE tunnus = '$otun' and tila='L' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);


		}

		$tee = '';
	}

	//toimittaja ja lasku on valittu. Nyt jumpataan.
	if ($tee == 'K') {

		echo "<table>";
		echo "<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='otunnus' value='$otunnus'>
				<input type='hidden' name='tee' value='L'>";

		$otunnus = substr($otunnus,0,-1);

		$query = "SELECT *
				  FROM lasku
				  WHERE tunnus in ($otunnus) and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array ($result);

		$query = "SELECT sum(kollit) kollit, sum(kilot) kilot
				  FROM rahtikirjat
				  WHERE otsikkonro in ($otunnus) and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$rahtirow = mysql_fetch_array ($result);

		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$result = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array ($result);

		// otetaan defaultit asiakkaalta jos laskulla ei ole mitään
		if ($laskurow["poistumistoimipaikka_koodi"]       == "") $laskurow["poistumistoimipaikka_koodi"]       = $asiakasrow["poistumistoimipaikka_koodi"];
		if ($laskurow["kuljetusmuoto"]                    ==  0) $laskurow["kuljetusmuoto"]                    = $asiakasrow["kuljetusmuoto"];
		if ($laskurow["kauppatapahtuman_luonne"]          ==  0) $laskurow["kauppatapahtuman_luonne"]          = $asiakasrow["kauppatapahtuman_luonne"];
		if ($laskurow["aktiivinen_kuljetus_kansallisuus"] == "") $laskurow["aktiivinen_kuljetus_kansallisuus"] = $asiakasrow["aktiivinen_kuljetus_kansallisuus"];
		if ($laskurow["aktiivinen_kuljetus"]              == "") $laskurow["aktiivinen_kuljetus"]              = $asiakasrow["aktiivinen_kuljetus"];
		if ($laskurow["kontti"]                           ==  0) $laskurow["kontti"]                           = $asiakasrow["kontti"];
		if ($laskurow["sisamaan_kuljetusmuoto"]           ==  0) $laskurow["sisamaan_kuljetusmuoto"]           = $asiakasrow["sisamaan_kuljetusmuoto"];
		if ($laskurow["sisamaan_kuljetus_kansallisuus"]   == "") $laskurow["sisamaan_kuljetus_kansallisuus"]   = $asiakasrow["sisamaan_kuljetus_kansallisuus"];
		if ($laskurow["sisamaan_kuljetus"]                == "") $laskurow["sisamaan_kuljetus"]                = $asiakasrow["sisamaan_kuljetus"];
		if ($laskurow["maa_maara"]                        == "") $laskurow["maa_maara"]                        = $asiakasrow["maa_maara"];

		echo "	<tr><td>6.  ".t("Kollimäärä").":</td>
				<td colspan='2'>$rahtirow[kollit]</td></tr>";

		echo "	<tr><td>17. ".t("Määrämaan koodi").":</td>
				<td colspan='2'><input type='text' name='maa_maara' size='2' value='$laskurow[maa_maara]'></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

		if ($laskurow["vienti"] == "K") {
			echo "	<tr><td>18. ".t("Sisämaan kuljetusväline").":</td>
					<td><input type='text' name='sisamaan_kuljetus' size='30' value='$laskurow[sisamaan_kuljetus]'></td>
					<td><input type='text' name='sisamaan_kuljetus_kansallisuus' size='2' value='$laskurow[sisamaan_kuljetus_kansallisuus]'></td>
					<td class='back'>".t("Pakollinen kenttä")."</td></tr>";

			echo "	<tr><td>26. ".t("Sisämaan kuljetusmuoto").":</td>
					<td colspan='2'><select NAME='sisamaan_kuljetusmuoto'>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
						ORDER BY jarjestys, selite";
			$result = mysql_query($query) or pupe_error($query);

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["sisamaan_kuljetusmuoto"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
			echo "</tr>";

			$chk1 = '';
			$chk2 = '';
			if($laskurow["kontti"] == 1) {
				$chk1 = 'checked';
			}
			if($laskurow["kontti"] == 0) {
				$chk2 = 'checked';
			}

			echo "	<tr><td>19. ".t("Kulkeeko tavara kontissa").":</td><td>Kyllä: <input type='radio' name='kontti' value='1' $chk1></td>
					<td>Ei: <input type='radio' name='kontti' value='0' $chk2></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

			echo "	<tr><td>21. ".t("Aktiivisen kuljetusvälineen tunnus ja kansalaisuus").":</td>
					<td><input type='text' name='aktiivinen_kuljetus' size='25' value='$laskurow[aktiivinen_kuljetus]'></td>
					<td><input type='text' name='aktiivinen_kuljetus_kansallisuus' size='2' value='$laskurow[aktiivinen_kuljetus_kansallisuus]'></td><td class='back'>Voidaan jättää tyhjäksi jos asiakas täyttää</td></tr>";
		}

		echo "	<tr><td>24. ".t("Kauppatapahtuman luonne").":</td>
				<td colspan='2'><select NAME='kauppatapahtuman_luonne'>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='KT'
					ORDER BY jarjestys, selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value=''>".t("Valitse")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $laskurow["kauppatapahtuman_luonne"]) {
				$sel = 'selected';
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
		echo "</tr>";


		echo "	<tr><td>25 ".t("Kuljetusmuoto rajalla").":</td>
				<td colspan='2'><select NAME='kuljetusmuoto'>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
					ORDER BY jarjestys, selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value=''>".t("Valitse")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $laskurow["kuljetusmuoto"]) {
				$sel = 'selected';
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
		echo "</tr>";

		if ($laskurow["vienti"] == "K") {
			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TULLI'
						ORDER BY selitetark";
			$vresult = mysql_query($query) or pupe_error($query);


			echo "<tr><td>29. ".t("Poistumistoimipaikka").":</td>";
			echo "<td colspan='2'><select name='poistumistoimipaikka'>";
			echo "<option value = '##'>".t("Ole hyvä ja valitse")."";

			while ($vrow = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($laskurow["poistumistoimipaikka_koodi"] == $vrow[0]) {
					$sel = "selected";
				}
				echo "<option value = '$vrow[1]##$vrow[0]' $sel>$vrow[1] $vrow[0]";
			}
			echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

			if ($laskurow["lisattava_era"] == 0) {
				$laskurow["lisattava_era"] = $yhtiorow["tulli_lisattava_era"];
			}
			if ($laskurow["vahennettava_era"] == 0) {
				$laskurow["vahennettava_era"] = $yhtiorow["tulli_vahennettava_era"];
			}

			echo "	<tr><td>28. ".t("Vähennettävä erä, ulkomaiset kustannukset")."</td><td colspan='2'><input type='text' name='vahennettava_era' size='25' value='$laskurow[vahennettava_era]'></td></tr>";
			echo "	<tr><td>28. ".t("Toimitusehdon mukainen lisättävä erä")."</td><td colspan='2'><input type='text' name='lisattava_era' size='25' value='$laskurow[lisattava_era]'></td></tr>";
		}

		echo "	<tr><td>35. ".t("Bruttopaino").":</td>
				<td colspan='2'>$rahtirow[kilot]</td>
				<input type='hidden' name='bruttopaino' value='$rahtirow[kilot]'></tr>";

		echo "<tr><td class='back'></td><td class='back'><input type='submit' value='".t("Päivitä tiedot")."'></td></tr>";
		echo "</form></table>";
	}

	// meillä ei ole valittua tilausta
	if ($tee == '') {

		$formi="find";
		$kentta="etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta (asiakkaan nimellä / tilausnumerolla)").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and tunnus='$etsi'";
		
		//listataan laskuttamattomat tilausket
		$query = "	select tunnus tilaus, nimi asiakas, luontiaika laadittu, laatija, vienti, erpcm, ytunnus, nimi, nimitark, postino, postitp, maksuehto, lisattava_era, vahennettava_era, ketjutus,
					maa_maara, kuljetusmuoto, kauppatapahtuman_luonne, sisamaan_kuljetus, sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, alatila
					from lasku
					where yhtio='$kukarow[yhtio]' and tila='L' and alatila in ('B','D','E')
					and (vienti='K' or vienti='E')
					$haku
					ORDER by 5,6,7,8,9,10,11,12,13,14";
		
		$tilre = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<td class='back' colspan='5'>".t("Tuoreet tilaukset")."</th>";
		echo "</tr>";

	 	if (mysql_num_rows($tilre) > 0) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($tilre)-18; $i++)
				echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";

			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Lisätiedot")."</th>";
			echo "</tr>";

			$lask = -1;
			$tunnukset 			= '';
			$ketjutus			= '';
			$erpcm				= '';
			$ytunnus			= '';
			$nimi				= '';
			$nimitark			= '';
			$postino			= '';
			$postitp			= '';
			$maksuehto			= '';
			$lisattava_era		= '';
			$vahennettava_era	= '';

			while ($tilrow = mysql_fetch_array($tilre))
			{
				$query = "	select sum(if(varattu>0,1,0))	veloitus, sum(if(varattu<0,1,0)) hyvitys
							from tilausrivi
							where yhtio='$kukarow[yhtio]' and otunnus='$tilrow[tilaus]'";
				$hyvre = mysql_query($query) or pupe_error($query);
				$hyvrow = mysql_fetch_array($hyvre);

				if ($ketjutus =='' and $erpcm==$tilrow["erpcm"] and $ytunnus==$tilrow["ytunnus"]
					and $nimi==$tilrow["nimi"] and $nimitark==$tilrow["nimitark"] and $postino==$tilrow["postino"]
					and $postitp==$tilrow["postitp"] and $maksuehto==$tilrow["maksuehto"]
					and $lisattava_era==$tilrow["lisattava_era"] and $vahennettava_era==$tilrow["vahennettava_era"]) {
					$tunnukset .= $tilrow["tilaus"].",";
					$lask++;
					echo "</tr>\n";
				}
				else {
					if ($lask >= 1) {
						echo "<form method='post' action='$PHP_SELF'><td class='back'>
							<input type='hidden' name='otunnus' value='$tunnukset'>
							<input type='hidden' name='tee' value='K'>
							<input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></td></form>";
					}
					$tunnukset = $tilrow["tilaus"].",";
					if ($lask != -1) {
						echo "</tr>\n";
					}
					$lask = 0;
				}



				echo "\n\n<tr>";

				for ($i=0; $i<mysql_num_fields($tilre)-18; $i++)
					echo "<td>$tilrow[$i]</td>";

				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] == 0) {
				 $teksti = "Veloitus";
				}
				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] > 0) {
				 $teksti = "Veloitusta ja hyvitystä";
				}
				if ($hyvrow["hyvitys"] > 0  and $hyvrow["veloitus"] == 0) {
				 $teksti = "Hyvitys";
				}
				echo "<td>$teksti</td>";
				
				if ($tilrow['alatila'] == 'E' and $tilrow['vienti'] == 'K' and $tilrow['maa_maara'] != '' and $tilrow['kuljetusmuoto'] != '' and $tilrow['kauppatapahtuman_luonne'] != '' and $tilrow['sisamaan_kuljetus'] != '' and $tilrow['sisamaan_kuljetusmuoto'] != '' and $tilrow['poistumistoimipaikka'] != '' and $tilrow['poistumistoimipaikka_koodi'] != '') {
					echo "<td><font color='#00FF00'>".t("OK")."</font></td>";
				}
				elseif ($tilrow['alatila'] == 'E' and $tilrow['vienti'] == 'E' and $tilrow['maa_maara'] != '' and $tilrow['kuljetusmuoto'] != '' and $tilrow['kauppatapahtuman_luonne'] != '') {
					echo "<td><font color='#00FF00'>".t("OK")."</font></td>";
				}
				else {
					echo "<td>".t("Kesken")."</td>";
				}

				echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='otunnus' value='$tilrow[tilaus],'>
						<input type='hidden' name='tee' value='K'>
						<input type='submit' name='tila' value='".t("Valitse")."'></td></form>";

				$ketjutus			= $tilrow["ketjutus"];
				$erpcm				= $tilrow["erpcm"];
				$ytunnus			= $tilrow["ytunnus"];
				$nimi				= $tilrow["nimi"];
				$nimitark			= $tilrow["nimitark"];
				$postino			= $tilrow["postino"];
				$postitp			= $tilrow["postitp"];
				$maksuehto			= $tilrow["maksuehto"];
				$lisattava_era		= $tilrow["lisattava_era"];
				$vahennettava_era	= $tilrow["vahennettava_era"];
			}

			if ($tunnukset != '' and $lask >= 1) {
				echo "<form method='post' action='$PHP_SELF'><td class='back'>
					<input type='hidden' name='otunnus' value='$tunnukset'>
					<input type='hidden' name='tee' value='K'>
					<input type='hidden' name='extra' value='K'>
					<input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></td></form>";

					$tunnukset = '';
			}
			echo "</tr>";
		}
		else {
			echo "<tr>";
			echo "<th colspan='5'>".t("Ei tuoreita tilauksia")."!</th>";
			echo "</tr>";
		}
		echo "</table><br>";

		if ($yhtiorow["kotitullauslupa"] != '') {
			//listataan myös laskutetut joille on jo syötetty tietoja, mutta ne ovat puutteelliset
			$query = "	select tunnus tilaus, nimi asiakas, luontiaika laadittu, laatija, vienti, erpcm, ytunnus, nimi, nimitark, postino, postitp, maksuehto, lisattava_era, vahennettava_era, ketjutus
						from lasku
						where yhtio='$kukarow[yhtio]' and tila='U' and tullausnumero != ''
						and (	(vienti='K' and (maa_maara = '' or kuljetusmuoto = '' or kauppatapahtuman_luonne = '' or sisamaan_kuljetus = '' or sisamaan_kuljetusmuoto = '' or poistumistoimipaikka = '' or poistumistoimipaikka_koodi = ''))
							or  (vienti='E' and (maa_maara = '' or kuljetusmuoto = '' or kauppatapahtuman_luonne = '' ))
						) and kauppatapahtuman_luonne != '999'
						$haku
						ORDER by 5,6,7,8,9,10,11,12,13,14";
			$tilre = mysql_query($query) or pupe_error($query);

			echo "<br><br>";
			echo "<table>";
			echo "<tr>";
			echo "<td class='back' colspan='6'>".t("Laskutetut tilaukset joilla on puutteelliset tiedot (korjattava ennen täydentävän ilmoituksen lähettämistä)")."</th>";
			echo "</tr>";

			if (mysql_num_rows($tilre) > 0) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($tilre)-10; $i++)
					echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";

				echo "<th>".t("Tyyppi")."</th>";
				echo "</tr>";

				$lask = -1;
				$tunnukset 			= '';
				$ketjutus			= '';
				$erpcm				= '';
				$ytunnus			= '';
				$nimi				= '';
				$nimitark			= '';
				$postino			= '';
				$postitp			= '';
				$maksuehto			= '';
				$lisattava_era		= '';
				$vahennettava_era	= '';

				while ($tilrow = mysql_fetch_array($tilre))
				{
					$query = "	select sum(if(kpl>0,1,0)) veloitus, sum(if(kpl<0,1,0)) hyvitys
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and uusiotunnus='$tilrow[tilaus]'";
					$hyvre = mysql_query($query) or pupe_error($query);
					$hyvrow = mysql_fetch_array($hyvre);


					if ($ketjutus =='' and $erpcm==$tilrow["erpcm"] and $ytunnus==$tilrow["ytunnus"]
						and $nimi==$tilrow["nimi"] and $nimitark==$tilrow["nimitark"] and $postino==$tilrow["postino"]
						and $postitp==$tilrow["postitp"] and $maksuehto==$tilrow["maksuehto"]
						and $lisattava_era==$tilrow["lisattava_era"] and $vahennettava_era==$tilrow["vahennettava_era"]) {
						$tunnukset .= $tilrow["tilaus"].",";
						$lask++;
						echo "</tr>\n";
					}
					else {
						if ($lask >= 1) {
							echo "<form method='post' action='$PHP_SELF'><td class='back'>
								<input type='hidden' name='otunnus' value='$tunnukset'>
								<input type='hidden' name='tee' value='K'>
								<input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></td></form>";
						}
						$tunnukset = $tilrow["tilaus"].",";
						if ($lask != -1) {
							echo "</tr>\n";
						}
						$lask = 0;
					}



					echo "\n\n<tr>";

					for ($i=0; $i<mysql_num_fields($tilre)-10; $i++)
						echo "<td>$tilrow[$i]</td>";

					if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] == 0) {
						$teksti = "".t("Veloitus")."";
					}
					if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] > 0) {
						$teksti = "".t("Veloitusta ja hyvitystä")."";
					}
					if ($hyvrow["hyvitys"] > 0  and $hyvrow["veloitus"] == 0) {
						$teksti = "".t("Hyvitys")."";
					}
					echo "<td>$teksti</td>";

						echo "<form method='post' action='$PHP_SELF'><td class='back'>
								<input type='hidden' name='otunnus' value='$tilrow[tilaus],'>
								<input type='hidden' name='tee' value='K'>
								<input type='submit' name='tila' value='".t("Valitse")."'></td></form>";

						$ketjutus			= $tilrow["ketjutus"];
						$erpcm				= $tilrow["erpcm"];
						$ytunnus			= $tilrow["ytunnus"];
						$nimi				= $tilrow["nimi"];
						$nimitark			= $tilrow["nimitark"];
						$postino			= $tilrow["postino"];
						$postitp			= $tilrow["postitp"];
						$maksuehto			= $tilrow["maksuehto"];
						$lisattava_era		= $tilrow["lisattava_era"];
						$vahennettava_era	= $tilrow["vahennettava_era"];
				}

				if ($tunnukset != '' and $lask >= 1) {
					echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='otunnus' value='$tunnukset'>
						<input type='hidden' name='tee' value='K'>
						<input type='hidden' name='extra' value='K'>
						<input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></td></form>";

						$tunnukset = '';
				}
				echo "</tr>";
			}
			else {
				echo "<tr>";
				echo "<th colspan='5'>".t("Ei puutteellisia tilauksia")."!</th>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	require "../inc/footer.inc";
?>
