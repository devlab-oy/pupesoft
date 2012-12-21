<?php

	//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}

	if ($tila == '') {
		$tila = 'Muuta';
	}

	//	Tehdään hieman oikeellisuustarkistuksia valmistuksesta.. "ei vielä hanskaa käytä kaikista valintaa"
	if ($toim == "VALMISTAVARASTOON" and isset($jatka) and $clearing != "" and $varasto != "") {
		//	Valmistuksia voi tehdä vain saman maan sisällä!
		$query  = "SELECT maa from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$clearing'";
		$abures = mysql_query($query) or pupe_error($query);
		$kohdev = mysql_fetch_array($abures);

		$query  = "SELECT maa from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
		$abures = mysql_query($query) or pupe_error($query);
		$lahdev = mysql_fetch_array($abures);

		if ($lahdev["maa"] != $kohdev["maa"]) {
			echo "<font class='error'>".t("Valmistus on mahdollista vain saman maan sisällä")."!!!</font><br><br>";
			$tila == "Muuta";
			unset($jatka);
		}
	}

	if ($tila == 'Muuta' and !isset($jatka)) {

		if ($toim == "SIIRTOLISTA") {
			echo "<font class='head'>".t("Siirtolistan otsikko")."</font><hr><br>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<font class='head'>".t("Valmistuksen otsikko")."</font><hr><br>";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			echo "<font class='head'>".t("Sisäinen työmääräys")."</font><hr><br>";
		}

		$qlisa = "";
		if ($toim == "SIIRTOTYOMAARAYS") {
			$qlisa = " LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus ";
		}

		if($kopioitava_otsikko > 0 or $kukarow["kesken"] > 0) {
			if ($kopioitava_otsikko > 0) {
				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokkaa
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$kopioitava_otsikko'";

				//	Tämä on tehtävä, jottemme vain päivitä vanhaa!
				$query = "	UPDATE kuka
							SET kesken = 0
							WHERE session = '$session'";
				$result = mysql_query($query) or pupe_error($query);

				$kukarow['kesken'] 	= 0;
				$tilausnumero 		= 0;

			}
			elseif ($kukarow["kesken"] != 0) {
				$squery = "	SELECT *, hyvaksynnanmuutos as luokka
							FROM lasku
							$qlisa
							WHERE tunnus = '$kukarow[kesken]'";
			}
			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);

			// jos meillä on jo alatila ja tila ei muokkailla niitä!
			$alatila="";
			$ylatila="";

			//	Jos meillä on kopioitu otsikko, ei kopioida alatilaa!
			if($kopioitava_otsikko == 0 and $srow['alatila'] != '') {
				$alatila=$srow['alatila'];
			}
			if ($srow['tila'] != '')    $ylatila=$srow['tila'];
		}

		echo "<table>";

		if ($toim == "SIIRTOLISTA") {
			echo "<tr><th>".t("Valitse lähdevarasto").":</th>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<tr><th>".t("Käytä raaka-aineita varastosta").":</th>";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			echo "<tr><th>".t("Valitse lähdevarasto").":</th>";
		}

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$vtresult = mysql_query($query) or pupe_error($query);

		echo "<form method='post'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tee' value='OTSIK'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='projektilla' value='$projektilla'>";
		echo "<input type='hidden' name='tunnusnippu' value='{$srow["tunnusnippu"]}'>";
		echo "<input type='hidden' name='ylatila' value='$ylatila'>";
		echo "<input type='hidden' name='alatila' value='$alatila'>";

		echo "<td><select name='varasto'>";

		if ($toim == "SIIRTOLISTA") {
			echo "<option value=''>".t("Siirrä kaikista")."</option>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<option value=''>".t("Käytä kaikista")."</option>";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			echo "<option value=''>".t("Käytä kaikista")."</option>";
		}

		while ($vrow = mysql_fetch_array($vtresult)) {
			$sel='';
			if ($vrow['tunnus']==$srow["varasto"]) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($vrow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = strtoupper($vrow['maa']);
			}

			echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
		}
		echo "</select></td></tr>";


		if ($toim != "SIIRTOTYOMAARAYS") {
			if ($toim == "SIIRTOLISTA") {
				echo "<tr><th>".t("Valitse kohdevarasto").":</th>";
			}
			elseif ($toim == "VALMISTAVARASTOON") {
				echo "<tr><th>".t("Valmisteiden kohdevarasto").":</th>";
			}

			echo "<td><select name='clearing'>";

			$query  = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
						ORDER BY tyyppi, nimitys";
			$vares = mysql_query($query) or pupe_error($query);

			while ($varow = mysql_fetch_array($vares)) {

				$sel = '';
				// Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan se
				if ($varow['tunnus'] == $srow["clearing"] or ((int) $kukarow["kesken"] == 0 and $varow['tunnus'] == $kukarow["oletus_varasto"])) {
					$sel = 'selected';
				}

				$varastomaa = '';
				if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
					$varastomaa = strtoupper($varow['maa']);
				}

				echo "<option value='$varow[tunnus]' $sel>$varow[maa] $varow[nimitys]</option>";
			}

			echo "</select></td></tr>";

			if ($toim == "VALMISTAVARASTOON") {
				echo "<tr><th>".t("Valmistuslinja").":</th>";
				echo "<td colspan='3'>";

				$valmistuslinja_result = t_avainsana("VALMISTUSLINJA");

				echo "<select name='valmistuslinja'>";
				echo "<option value = ''>".t("Ei valmistuslinjaa")."</option>";

				while ($valmistuslinja_row = mysql_fetch_array($valmistuslinja_result)) {
					$sel = ($valmistuslinja_row["selite"] == $srow["kohde"]) ? "selected" : "";
					echo "<option value='{$valmistuslinja_row["selite"]}' $sel>{$valmistuslinja_row["selitetark"]}</option>";
				}
				echo "</select>";
				echo "</td></tr>";
			}

			echo "<tr><th>".t("Nimi").":</th><td colspan='3'><input type='text' size='35' name='toim_nimi' value='$srow[toim_nimi]'></td>";
			echo "<tr><th>".t("Nimitark").":</th><td colspan='3'><input type='text' size='35' name='toim_nimitark' value='$srow[toim_nimitark]'></td>";
			echo "<tr><th>".t("Osoite").":</th><td colspan='3'><input type='text' size='35' name='toim_osoite' value='$srow[toim_osoite]'></td>";
			echo "<tr><th>".t("Postino").":</th><td colspan='3'><input type='text' size='8' name='toim_postino' value='$srow[toim_postino]'></td>";
			echo "<tr><th>".t("Postitp").":</th><td colspan='3'><input type='text' size='15' name='toim_postitp' value='$srow[toim_postitp]'></td>";
			echo "<tr><th>".t("Maa").":</th><td colspan='3'>$srow[toim_maa]</td>";
		}

		if ($kukarow['kesken'] == 0) {
			$toimpp = $kerpp = date("j");
			$toimkk = $kerkk = date("n");
			$toimvv = $kervv = date("Y");
		}
		else {
			list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
			list($kervv, $kerkk, $kerpp)    = explode('-', $srow["kerayspvm"]);
			$kerpp = substr($kerpp,0,2);
			$toimpp = substr($toimpp,0,2);

		}

		echo "<tr><th>".t("Toivottu keräysajankohta").": </th><td valign='middle'>
				<input type='text' name='kerpp' value='$kerpp' size='3'>
				<input type='text' name='kerkk' value='$kerkk' size='3'>
				<input type='text' name='kervv' value='$kervv' size='6'>
				<input type='hidden' name='vkerayspvm' value='".substr($srow["kerayspvm"],0,10)."'></td></tr>";

		if ($toim == "SIIRTOLISTA") {
			echo "<tr><th>".t("Toivottu toimitusajankohta").": </th>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<tr><th>".t("Toivottu valmistusajankohta").": </th>";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			echo "<tr><th>".t("Toivottu toimitusajankohta").": </th>";
		}

		echo "	<td valign='middle'>
				<input type='text' name='toimpp' value='$toimpp' size='3'>
				<input type='text' name='toimkk' value='$toimkk' size='3'>
				<input type='text' name='toimvv' value='$toimvv' size='6'>
				<input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td></tr>";

		if ($toim == "SIIRTOTYOMAARAYS" or $toim == "VALMISTAVARASTOON") {
			if ($toim == "VALMISTAVARASTOON" and $yhtiorow["kerataanko_valmistukset"] == "E") {
				echo "<input type='hidden' name='eilahe' value='o'>";
			}
			else {
				if ($srow['eilahetetta'] != '') $eilah = 'CHECKED';
				echo "<tr><th>".t("Ohita keräys")."</th><td><input type='checkbox' name='eilahe' $eilah></td></tr>";
			}
		}

		echo "<tr>";

		if ($toim == "SIIRTOLISTA") {
			echo "<th>".t("Keräysprioriteetti")."</th><td>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<th>".t("Valmistusprioriteetti")."</th><td>";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			echo "<th>".t("Asennusprioriteetti")."</th><td>";
		}

		//	Olisiko erikseen varastoluokkia?
		$tresult = t_avainsana("VARASTOLUOKKA");

		if(mysql_num_rows($tresult) == 0) {
			$tresult = t_avainsana("ASIAKASLUOKKA");
		}

		echo "<select name='luokka'><option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_array($tresult)) {
			$sel = "";
			if ($row["selite"] == $srow["luokka"]) $sel = 'selected';

			echo "<option value='$row[selite]' $sel>$row[selite] - $row[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr>";

		echo "<tr>";

		if ($toim == "SIIRTOLISTA") {
			echo "<th>".t("Toimitustapa")."</th><td>";

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and (sallitut_maat = '' or sallitut_maat like '%$srow[toim_maa]%')
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<select name='toimitustapa'>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row["selite"] == $srow["toimitustapa"]) $sel = 'selected';

				echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>";
			}
			echo "</select>";


			echo "</td></tr>";
		}

		echo "<tr><th>".t("Viite").":</th><td colspan='3'>
				<input type='text' size='53' name='viesti' value='$srow[viesti]'></td>";
		echo "</tr>";
		echo "<tr><th>".t("Kommentit").":</th><td colspan='3'><textarea name='comments' rows='2' cols='60'>$srow[comments]</textarea></td></tr>";
		echo "</table>";

		if ($toim == "SIIRTOTYOMAARAYS") {
			echo "<br><table>";
			echo "<tr><th colspan='4'>".t("Työmääräyksen tiedot")."</th></tr>";
			echo "<tr>
					<td>Rek. no:</td><td><input type='text' name='rekno' size='15' value='$srow[rekno]'></td>
					<td>Kotipuh:</td><td><input type='text' name='kotipuh' size='35' value='$srow[kotipuh]'></td></tr>";
			echo "<tr>
					<td>Valm. nro:</td><td><input type='text' name='valmnro' size='35' value='$srow[valmnro]'></td>
					<td>Työpuh:</td><td><input type='text' name='tyopuh' size='35' value='$srow[tyopuh]'></td></tr>";
			echo "<tr>
					<td>Mittarilukema:</td><td><input type='text' name='mittarilukema' size='35' value='$srow[mittarilukema]'></td>
					<td>Myyjäliike:</td><td><input type='text' name='myyjaliike' size='35' value='$srow[myyjaliike]'></td></tr>";

			if ($srow["ostopvm"] == "" or $srow["ostopvm"] == "0000-00-00") {
				$luvattupp = date("d");
				$luvattukk = date("m");
				$luvattuvv = date("Y");
			}
			else {
				list($ostopvmvv, $ostopvmkk, $ostopvmpp) = explode('-', $srow["ostopvm"]);
			}
			if ($srow["tuotu"] == "" or $srow["tuotu"] == "0000-00-00") {
				$tuotupp = date("d");
				$tuotukk = date("m");
				$tuotuvv = date("Y");
			}
			else {
				list($tuotuvv, $tuotukk, $tuotupp) = explode('-', $srow["tuotu"]);
			}

			if ($srow["luvattu"] == "" or $srow["luvattu"] == "0000-00-00") {
				$luvattupp = date("d");
				$luvattukk = date("m");
				$luvattuvv = date("Y");
			}
			else {
				list($luvattuvv, $luvattukk, $luvattupp) = explode('-', $srow["luvattu"]);
			}

			echo "<tr><td>Ostopvm:</td>
					<td>
					<input type='text' name='ostopvmpp' size='3' value='$ostopvmpp'>-
					<input type='text' name='ostopvmkk' size='3' value='$ostopvmkk'>-
					<input type='text' name='ostopvmvv' size='6' value='$ostopvmvv'>
					</td>
					<td>Merkki ja malli:</td><td><input type='text' name='merkki' size='11' value='$srow[merkki]'>&nbsp;";
			echo "<select name='merkki2'><option value='eimenu'>Valitse malli";


			$query = "	SELECT distinct malli
						FROM huollot order by malli";
			$result = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($result)){
				echo "<option value = '$row[0]'>$row[0]";
			}

			echo "</select></td></tr>";
			echo "<tr><td>Tuotu:</td>
					<td>
					<input type='text' name='tuotupp' size='3' value='$tuotupp'>-
					<input type='text' name='tuotukk' size='3' value='$tuotukk'>-
					<input type='text' name='tuotuvv' size='6' value='$tuotuvv'>
					</td>
					<td>Malli/Värikoodi:</td><td><input type='text' name='mallivari' size='35' value='$srow[mallivari]'></td></tr>";
			echo "<tr><td>Luvattu:</td><td>
					<input type='text' name='luvattupp' size='3' value='$luvattupp'>-
					<input type='text' name='luvattukk' size='3' value='$luvattukk'>-
					<input type='text' name='luvattuvv' size='6' value='$luvattuvv'>
					</td>
					<td>Työn suorittaja:</td><td><input type='text' name='suorittaja' size='35' maxlengtd='15' value='$srow[suorittaja]'></td></tr>";
			echo "<tr><td>".t("Työn kuvaus").":</td><td colspan='3'><textarea name='komm1' rows='4' cols='40'>$srow[komm1]</textarea></td></tr>";
			echo "<tr><td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='komm2' rows='4' cols='40'>$srow[komm2]</textarea></td></tr>";
			echo "</table><br>";
		}

		echo "<br><input type='submit' name='jatka' value='".t("Jatka")."'></form>";
	}

	if (isset($jatka)) {

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";
			$postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			$query2 = "INSERT into ";
			$postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
		}
		else {
			// Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
			$query = "	UPDATE tilausrivi
						SET kerayspvm = '$kervv-$kerkk-$kerpp'
						WHERE otunnus = '$kukarow[kesken]' and kerayspvm='$vkerayspvm' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET toimaika = '$toimvv-$toimkk-$toimpp'
						WHERE otunnus = '$kukarow[kesken]' and toimaika='$vtoimaika' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE ";
			$postquery = " WHERE tunnus = '$kukarow[kesken]'";

			$query2 = "UPDATE ";
			$postquery2 = " WHERE otunnus = '$kukarow[kesken]'";
		}

		$crlf = array("\r","\n"); // poistetaan rivinvaihdot kommentista
		$comments = str_replace($crlf, " ", $comments);

		$aquery = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$clearing'";
		$vtresult = mysql_query($aquery) or pupe_error($aquery);
		$vtrow = mysql_fetch_array($vtresult);

		if ($toim_nimi == '') {
			$toim_nimi = $vtrow['nimi'];
		}

		if ($toim_nimitark == '') {
			$toim_nimitark = $vtrow['nimitark'];
		}

		if ($toim_osoite == '') {
			$toim_osoite = $vtrow['osoite'];
		}

		if ($toim_postino == '') {
			$toim_postino = $vtrow['postino'];
		}

		if ($toim_postitp == '') {
			$toim_postitp = $vtrow['postitp'];
		}

		if ($toim_maa == '') {
			$toim_maa = $vtrow['maa'];
		}

		$query .= "	lasku SET
					yhtio_nimi			= '$yhtiorow[nimi]',
					yhtio_osoite		= '$yhtiorow[osoite]',
					yhtio_postino		= '$yhtiorow[postino]',
					yhtio_postitp		= '$yhtiorow[postitp]',
					yhtio_maa			= '$yhtiorow[maa]',
					yhtio_ovttunnus		= '$yhtiorow[ovttunnus]',
					yhtio_kotipaikka	= '$yhtiorow[kotipaikka]',
					yhtio_toimipaikka	= '$kukarow[toimipaikka]',
					clearing			= '$clearing',
					maa					= '$vtrow[maa]',
					nimi 				= '$vtrow[nimitys]',
					nimitark			= '$vtrow[nimi]',
					osoite				= '$vtrow[osoite]',
					postino				= '$vtrow[postino]',
					postitp				= '$vtrow[postitp]',
					toim_maa			= '$toim_maa',
					toim_nimi			= '$toim_nimi',
					toim_nimitark		= '$toim_nimitark',
					toim_osoite			= '$toim_osoite',
					toim_postino		= '$toim_postino',
					toim_postitp		= '$toim_postitp',
					toimaika 			= '".$toimvv."-".$toimkk."-".$toimpp."',
					kerayspvm 			= '".$kervv."-".$kerkk."-".$kerpp."',
					comments 			= '$comments',
					eilahetetta 		= '$eilahe',
					viesti 				= '$viesti',
					yhtio 				= '$kukarow[yhtio]',
					alatila 			= '$alatila',
					varasto 			= '$varasto',
					hyvaksynnanmuutos 	= '$luokka',
					tunnusnippu			= '$tunnusnippu',
					kohde				= '$valmistuslinja',
					toimitustapa		= '$toimitustapa'";

		if ($toim == "VALMISTAVARASTOON") {
			$query .= ",tilaustyyppi = 'W', tila='V', ytunnus='$vtrow[nimitys]', liitostunnus='9999999999'";

			$tee = "";
		}
		elseif ($toim == "SIIRTOLISTA") {
			$query .= ",tilaustyyppi = 'G', tila='G', ytunnus='SIIRTO', liitostunnus='9999999999'";

			// Siirtolistat aina lähettäjän rahtisopparilla
			$query .= ",kohdistettu = 'K'";

			if (isset($chn) and $chn == 'GEN') {
				$query .= ",chn = 'GEN'"; // tällä erotellaan "tulosta siirtolista"-kohdassa generoidut ja käsin tehdyt siirtolistat
			}

			$tee = "";
		}
		elseif ($toim == "SIIRTOTYOMAARAYS") {
			$query .= ",tilaustyyppi = 'S', tila='S', ytunnus='SISAINEN', liitostunnus='9999999999'";

			$tee = "";
		}

		$query .= $postquery;
		$result = mysql_query($query) or pupe_error($query);
		$id = mysql_insert_id();

		if (!isset($nokeskupdate) and (int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE and $session != "") {
			$query = "	UPDATE kuka
						SET kesken = '$id'
						WHERE yhtio = '$kukarow[yhtio]' AND
						kuka = '$kukarow[kuka]' AND
						session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow["kesken"] = $id;
			$tilausnumero = $id;
		}

		if ($toim == "SIIRTOTYOMAARAYS" and (int) $kukarow["kesken"] != 0) {
			//otetaan kantaa pyörän malliin
			if($merkki2 != 'eimenu'){
				$merkki = $merkki2;
			}

			$query2 .= "tyomaarays SET
						yhtio			= '$kukarow[yhtio]',
						kotipuh 		= '$kotipuh',
						tyopuh 			= '$tyopuh',
						myyjaliike 		= '$myyjaliike',
						ostopvm 		= '$ostopvmvv-$ostopvmkk-$ostopvmpp',
						rekno 			= '$rekno',
						mittarilukema 	= '$mittarilukema',
						merkki 			= '$merkki',
						mallivari 		= '$mallivari',
						valmnro 		= '$valmnro',
						tuotu 			= '$tuotuvv-$tuotukk-$tuotupp',
						luvattu 		= '$luvattuvv-$luvattukk-$luvattupp',
						komm1 			= '$komm1',
						komm2 			= '$komm2',
						suorittaja 		= '$suorittaja',
						viite	 		= '$viite',
						otunnus 		= '$kukarow[kesken]'";
			$query2 .= $postquery2;
			$result = mysql_query($query2) or pupe_error($query2);
		}

		if ($toim == 'SIIRTOLISTA' and $tilausnumero != '' and $varasto != '' and $clearing != '') {
			//tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
			if ($tee == '' or $kopioitava_otsikko != 0) {
				//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
				if ($tila == '' and !isset($jatka)) {
					$query = "	UPDATE kuka
								SET kesken=0
								WHERE session = '$session'";
					$result = mysql_query($query) or pupe_error($query);

					$kukarow['kesken'] 	= 0;
					$tilausnumero 		= 0;
				}
			}
		}
	}
?>
