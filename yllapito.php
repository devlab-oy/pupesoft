<?php

		if ($_POST["toim"] == "yhtion_parametrit") {
		$apucss = $_POST["css"];
		$apucsspieni = $_POST["css_pieni"];
		$apucssextranet = $_POST["css_extranet"];
		$apucssverkkokauppa = $_POST["css_verkkokauppa"];
	}
	else {
		unset($apucss);
		unset($apucsspieni);
		unset($apucssextranet);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "yllapito.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}

	//Jotta m‰‰ritelty rajattu n‰kym‰ olisi myˆs k‰yttˆoikeudellisesti tiukka
	$aputoim = $toim;
	list($toim, $alias_set, $rajattu_nakyma) = explode('!!!', $toim);


	// Tutkitaan v‰h‰n alias_settej‰ ja rajattua n‰kym‰‰
	$al_lisa = " and selitetark_2 = '' ";

	if ($alias_set != '') {
		if ($rajattu_nakyma != '') {
			$al_lisa = " and selitetark_2 = '$alias_set' ";
		}
		else {
			$al_lisa = " and (selitetark_2 = '$alias_set' or selitetark_2 = '') ";
		}
	}

	// pikkuh‰kki, ettei rikota css kentt‰‰
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucss)) {
		$t[$cssi] = $apucss;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucsspieni)) {
		$t[$csspienii] = $apucsspieni;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucssextranet)) {
		$t[$cssextraneti] = $apucssextranet;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucssverkkokauppa)) {
		$t[$cssverkkokauppa] = $apucssverkkokauppa;
	}

	$rajauslisa	= "";

	require ("inc/$toim.inc");

	if ($otsikko == "") {
		$otsikko = $toim;
	}
	if ($otsikko_nappi == "") {
		$otsikko_nappi = $toim;
	}

	echo "<font class='head'>".t("$otsikko")."</font><hr>";

	// Saako paivittaa
	if ($oikeurow['paivitys'] != '1') {
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa")."</b><br>";
			$uusi = '';
			exit;
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
			exit;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
			exit;
		}
	}

	// Tietue poistetaan
	if ($del == 1) {

		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		$query = "	DELETE from $toim
					WHERE tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

		//	Jos poistetaan perheen osa palataan perheelle
		if($seuraavatunnus > 0) $tunnus = $seuraavatunnus;
		else $tunnus = 0;

		$seuraavatunnus = 0;

		// Siirryt‰‰n takaisin sielt‰ mist‰ tultiin
		if ($lopetus != '') {
			// Jotta urlin parametrissa voisi p‰‰ss‰t‰ toisen urlin parametreineen
			$lopetus = str_replace('////','?', $lopetus);
			$lopetus = str_replace('//','&',  $lopetus);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
			exit;
		}
	}

	// Jotain p‰ivitet‰‰n tietokontaan
	if ($upd == 1) {
		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		//	Tehd‰‰n muuttujista linkit jolla luomme otsikolliset avaimet!
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			$t[mysql_field_name($result, $i)] = &$t[$i];
		}

		// Tarkistetaan
		$errori = '';
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {

			//P‰iv‰m‰‰r‰ spesiaali
			if (isset($tpp[$i])) {
				if ($tvv[$i] < 1000 and $tvv[$i] > 0) $tvv[$i] += 2000;

				$t[$i] = sprintf('%04d', $tvv[$i])."-".sprintf('%02d', $tkk[$i])."-".sprintf('%02d', $tpp[$i]);

				if(!checkdate($tkk[$i],$tpp[$i],$tvv[$i]) and ($tkk[$i]!= 0 or $tpp[$i] != 0)) {
					$virhe[$i] = t("Virheellinen p‰iv‰m‰‰r‰");
					$errori = 1;
				}
			}

			// Tarkistetaan saako k‰ytt‰j‰ p‰ivitt‰‰ t‰t‰ kentt‰‰
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) == 0 and $rajattu_nakyma != '' and isset($t[$i])) {
				$virhe[$i] = t("Sinulla ei ole oikeutta p‰ivitt‰‰ t‰t‰ kentt‰‰");
				$errori = 1;
			}

			$tiedostopaate = "";

			$funktio = $toim."tarkista";
			
			if(!function_exists($funktio)) {
				require("inc/$funktio.inc");
			}

			if(function_exists($funktio)) {
				$funktio($t, $i, $result, $tunnus, &$virhe, $trow);
			}

			if($virhe[$i] != "") {
				$errori = 1;
			}

			//	Tarkastammeko liitetiedoston?
			if(is_array($tiedostopaate) and is_array($_FILES["liite_$i"])) {
				$viesti = tarkasta_liite("liite_$i", $tiedostopaate);
				if($viesti !== true) {
					$virhe[$i] = $viesti;
					$errori = 1;
				}
			}
		}

		if ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivitt‰‰!")."</font>";
		}

		// Luodaan tietue
		if ($errori == "") {
			if ($tunnus == "") {
				// Taulun ensimm‰inen kentt‰ on aina yhtiˆ
				$query = "INSERT into $toim SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i])) {
						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);

						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";
					}
				}
			}
			// P‰ivitet‰‰n
			else {

				//	Jos poistettiin jokin liite, poistetaan se nyt
				if(is_array($poista_liite)) {
					foreach($poista_liite as $key => $val) {
						if($val > 0) {
							$delquery = " DELETE FROM liitetiedostot WHERE yhtio = '{$kukarow["yhtio"]}' and liitos = 'Yllapito' and tunnus = '$val'";
							$delres = mysql_query($delquery);
							if(mysql_affected_rows() == 1) {
								$t[$key] = "";
							}
						}
					}
				}

				// Taulun ensimm‰inen kentt‰ on aina yhtiˆ
				$query = "UPDATE $toim SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i]) or is_array($_FILES["liite_$i"])) {

						if(is_array($_FILES["liite_$i"])) {
							$id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result,$i), $t[$i]);
							if($id !== false) {
								$t[$i] = $id;
							}
						}

						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);
						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";

					}
				}

				$query .= " where tunnus = $tunnus";
			}
			$result = mysql_query($query) or pupe_error($query);

			if ($tunnus == '') {
				$tunnus = mysql_insert_id();
				$wanha = "";
			}
			else {
				//	Javalla tieto ett‰ t‰t‰ muokattiin..
				$wanha = "P_";
			}

			//	T‰m‰ funktio tekee myˆs oikeustarkistukset!
			synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

			// Siirryt‰‰n takaisin sielt‰ mist‰ tultiin
			if ($lopetus != '') {
				// Jotta urlin parametrissa voisi p‰‰ss‰t‰ toisen urlin parametreineen
				$lopetus = str_replace('////','?', $lopetus);
				$lopetus = str_replace('//','&',  $lopetus);

				if (strpos($lopetus, "?") === FALSE) {
					$lopetus .= "?";
				}
				else {
					$lopetus .= "&";
				}

				$lopetus .= "yllapidossa=$toim&yllapidontunnus=$tunnus";

				if (strpos($lopetus, "tilaus_myynti.php") !== FALSE and $toim == "asiakas") {
					$lopetus.= "&asiakasid=$tunnus";
				}

				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
				exit;
			}

			if(substr($suljeYllapito, 0, 15) == "asiakkaan_kohde") {
				$query = "SELECT kohde from asiakkaan_kohde where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>suljeYllapito('$wanha$suljeYllapito','$tunnus','$tunnus - $aburow[kohde]');</SCRIPT>";
				exit;
			}
			if(substr($suljeYllapito, 0, 17) == "asiakkaan_positio") {
				$query = "SELECT positio from asiakkaan_positio where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>suljeYllapito('$wanha$suljeYllapito','$tunnus','$tunnus - $aburow[positio]');</SCRIPT>";
				exit;
			}
			if(substr($suljeYllapito, 0, 22) == "yhteyshenkilo_tekninen"  or substr($suljeYllapito, 0, 25) == "yhteyshenkilo_kaupallinen") {
				$query = "SELECT nimi from yhteyshenkilo where tunnus = $tunnus";
				$result = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($result);
				js_yllapito();
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>suljeYllapito('$wanha$suljeYllapito','$tunnus','$aburow[nimi]');</SCRIPT>";
				exit;
			}


			$uusi 	 = 0;

			if (isset($yllapitonappi) and $lukossa != "ON" or isset($paluunappi)) {
				$tunnus  = 0;
				$kikkeli = 0;
			}
			else {
				$kikkeli = 1;
			}
		}
	}

	// Rakennetaan hakumuuttujat kuntoon
	if (isset($hakukentat)) {
		$array = split(",", $hakukentat);
	}
	else {
		$array = split(",", $kentat);
	}

	$count = count($array);

	for ($i=0; $i<=$count; $i++) {
    	if (strlen($haku[$i]) > 0) {

			if (strpos($array[$i], "/") !== FALSE) {
				list($a, $b) = explode("/", $array[$i]);

				$lisa .= " and (".$a." like '%".$haku[$i]."%' or ".$b." like '%".$haku[$i]."%') ";
			}
			else {
				$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			}

			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
    	}
    }
    if (strlen($ojarj) > 0) {
    	$jarjestys = $ojarj;
    }

	// Nyt selataan
	if ($tunnus == 0 and $uusi == 0 and $errori == '') {
		if ($limit != "NO") {
			$limiitti = " LIMIT 350";
		}
		else {
			$limiitti = "";
		}

		$query = "SELECT " . $kentat . " FROM $toim WHERE yhtio = '$kukarow[yhtio]' $lisa $rajauslisa";
        $query .= "$ryhma ORDER BY $jarjestys $limiitti";
		$result = mysql_query($query) or pupe_error($query);

		if ($toim != "yhtio" and $toim != "yhtion_parametrit") {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'uusi' value = '1'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'limit' value = '$limit'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
		}

		if (mysql_num_rows($result) >= 350) {
			echo "	<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
					<input type = 'hidden' name = 'toim' value = '$aputoim'>
					<input type = 'hidden' name = 'limit' value = 'NO'>
					<input type = 'hidden' name = 'laji' value = '$laji'>
					<input type = 'submit' value = '".t("N‰yt‰ kaikki")."'></form>";
		}

		echo "	<br><br><table><tr class='aktiivi'>
				<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'laji' value = '$laji'>";

		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th valign='top'><a href='yllapito.php?toim=$aputoim&ojarj=".mysql_field_name($result,$i).$ulisa."&limit=$limit&laji=$laji'>" . t(mysql_field_name($result,$i)) . "</a>";

			if 	(mysql_field_len($result,$i)>10) $size='15';
			elseif	(mysql_field_len($result,$i)<5)  $size='5';
			else	$size='10';

			// jos meid‰n kentt‰ ei ole subselect niin tehd‰‰n hakukentt‰
			if (strpos(strtoupper($array[$i]), "SELECT") === FALSE) {
				echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
			}
			echo "</th>";
		}

		echo "<td class='back' valign='bottom'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></form>";
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr class='aktiivi'>";
			for ($i=1; $i<mysql_num_fields($result); $i++) {
				if ($i == 1) {
					if (trim($trow[1]) == '') $trow[1] = "".t("*tyhj‰*")."";
					echo "<td valign='top'><a href='yllapito.php?ojarj=$ojarj$ulisa&toim=$aputoim&tunnus=$trow[0]&limit=$limit&laji=$laji'>$trow[1]</a></td>";
				}
				else {
					echo "<td valign='top'>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	// Nyt n‰ytet‰‰n vanha tai tehd‰‰n uusi(=tyhj‰)
	if ($tunnus > 0 or $uusi != 0 or $errori != '') {
		if ($oikeurow['paivitys'] != 1) {
			echo "<b>".t("Sinulla ei ole oikeuksia p‰ivitt‰‰ t‰t‰ tietoa")."</b><br>";
		}

		echo "<form action = 'yllapito.php?ojarj=$ojarj$ulisa' name='mainform' method = 'post' autocomplete='off' enctype='multipart/form-data'>";
		echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
		echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
		echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
		echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
		echo "<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>";
		echo "<input type = 'hidden' name = 'upd' value = '1'>";

		// Kokeillaan geneerist‰
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		echo "<table width='100%'><tr><td class='back' valign='top'>";
		echo "<table>";

		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

			$nimi = "t[$i]";

			if (isset($t[$i])) {
				$trow[$i] = $t[$i];
			}

			if 	(mysql_field_len($result,$i)>10) 	$size='35';
			elseif	(mysql_field_len($result,$i)<5)	$size='5';
			else	$size='10';

			$maxsize = mysql_field_len($result,$i); // Jotta t‰t‰ voidaan muuttaa

			require ("inc/$toim"."rivi.inc");

			// N‰it‰ kentti‰ ei ikin‰ saa p‰ivitt‰‰ k‰yttˆliittym‰st‰
			if (mysql_field_name($result, $i) == "laatija" or
				mysql_field_name($result, $i) == "muutospvm" or
				mysql_field_name($result, $i) == "muuttaja" or
				mysql_field_name($result, $i) == "luontiaika") {
				$tyyppi = 2;
			}

			//Haetaan tietokantasarakkeen nimialias
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) > 0) {
				$al_row = mysql_fetch_array($al_res);

				if ($al_row["selitetark"] != '') {
					$otsikko = t($al_row["selitetark"]);
				}
				else {
					$otsikko = t(mysql_field_name($result, $i));
				}
			}
			else {
				switch (mysql_field_name($result,$i)) {
					case "printteri1":
						$otsikko = t("L‰hete/Ker‰yslista");
						break;
					case "printteri2":
						$otsikko = t("Rahtikirja matriisi");
						break;
					case "printteri3":
						$otsikko = t("Osoitelappu");
						break;
					case "printteri4":
						$otsikko = t("Rahtikirja A5");
						break;
					case "printteri5":
						$otsikko = t("Lasku");
						break;
					case "printteri6":
						$otsikko = t("Rahtikirja A4");
						break;
					case "printteri7":
						$otsikko = t("JV-lasku/-kuitti");
						break;
					default:
						$otsikko = t(mysql_field_name($result, $i));
				}

				if ($rajattu_nakyma != '') {
 					$ulos = "";
 					$tyyppi = 0;
 				}
			}

			// $tyyppi --> 0 rivi‰ ei n‰ytet‰ ollenkaan
			// $tyyppi --> 1 rivi n‰ytet‰‰n normaalisti
			// $tyyppi --> 1.5 rivi n‰ytet‰‰n normaalisti ja se on p‰iv‰m‰‰r‰kentt‰
			// $tyyppi --> 2 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, eik‰ sen arvoa p‰vitet‰
			// $tyyppi --> 3 rivi n‰ytet‰‰n, mutta sit‰ ei voida muokata, mutta sen arvo p‰ivitet‰‰n
			// $tyyppi --> 4 rivi‰ ei n‰ytet‰ ollenkaan, mutta sen arvo p‰ivitet‰‰n
			// $tyyppi --> 5 liitetiedosto

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "<tr>";
				echo "<th align='left'>$otsikko</th>";
			}

			if ($jatko == 0) {
				if($yllapito_tarkista_oikeellisuus!="") {
					//	Tehd‰‰n tarkastuksia, T‰m‰ sallisi myˆs muiden tagien "oikeellisuuden" m‰‰rittelemisen suhteellisen helposti
					//	Jostainsyyst‰ multiline ei toimi kunnolla?
					$search = "/.*<(select)[^>]*>(.*)<\/select>.*/mi";
					preg_match($search, $ulos, $matches);
					if(strtolower($matches[1]) == "select") {
						$search = "/\s+selected\s*>/i";
						preg_match($search, $matches[2], $matches2);
						if(count($matches2)==0) {
							$ulos .= "<td class='back'><font class='error'>OBS!! '".$trow[$i]."'</font></td>";
						}
					}
				}
				echo $ulos;
			}
			elseif ($tyyppi == 1) {
				echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			}
			elseif ($tyyppi == 1.5) {
				$vva = substr($trow[$i],0,4);
				$kka = substr($trow[$i],5,2);
				$ppa = substr($trow[$i],8,2);

				echo "<td>
						<input type = 'text' name = 'tpp[$i]' value = '$ppa' size='3' maxlength='2'>
						<input type = 'text' name = 'tkk[$i]' value = '$kka' size='3' maxlength='2'>
						<input type = 'text' name = 'tvv[$i]' value = '$vva' size='5' maxlength='4'></td>";
			}
			elseif ($tyyppi == 2) {
				echo "<td>$trow[$i]</td>";
			}
			elseif($tyyppi == 3) {
				echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
			}
			elseif($tyyppi == 4) {
				echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
			}
			elseif($tyyppi == 5) {
				echo "<td>";

				if($trow[$i] > 0) {
					echo "<a href='view.php?id=".$trow[$i]."'>".t("N‰yt‰ liitetiedosto")."</a><input type = 'hidden' name = '$nimi' value = '$trow[$i]'> ".("Poista").": <input type = 'checkbox' name = 'poista_liite[$i]' value = '{$trow[$i]}'>";
				}
				else {
					echo "<input type = 'text' name = '$nimi' value = '$trow[$i]'>";
				}

				echo "<input type = 'file' name = 'liite_$i'></td>";
			}

			if (isset($virhe[$i])) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
			}

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "</tr>";
			}
		}
		echo "</table>";

		if ($uusi == 1) {
			$nimi = t("Perusta $otsikko_nappi");
		}
		else {
			$nimi = t("P‰ivit‰ $otsikko_nappi");
		}

		echo "<br><input type = 'submit' name='yllapitonappi' value = '$nimi'>";

		if($lukossa == "ON") {
			echo "<input type='hidden' name='lukossa' value = '$lukossa'>";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type = 'submit' name='paluunappi' value = '".t("Palaa avainsanoihin")."'>";
		}

		echo "</form>";

		// M‰‰ritell‰‰n mit‰ tietueita saa poistaa
		if ($toim == "avainsana" or
			$toim == "tili" or
			$toim == "taso" or
			$toim == "asiakasalennus" or
			$toim == "asiakashinta" or
			$toim == "perusalennus" or
			$toim == "yhteensopivuus_tuote" or
			$toim == "toimitustapa" or
			$toim == "kirjoittimet" or
			$toim == "hinnasto" or
			($toim == "toimi" and $kukarow["taso"] == "3")) {

			// Tehd‰‰n "poista tietue"-nappi
			if ($uusi != 1 and $toim != "yhtio" and $toim != "yhtion_parametrit") {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
							function verify(){
									msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
									return confirm(msg);
							}
					</SCRIPT>";

				if ($rajattu_nakyma == '') {
					echo "<br><br>
						<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post' onSubmit = 'return verify()' enctype='multipart/form-data'>
						<input type = 'hidden' name = 'toim' value = '$aputoim'>
						<input type = 'hidden' name = 'limit' value = '$limit'>
						<input type = 'hidden' name = 'laji' value = '$laji'>
						<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
						<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
						<input type = 'hidden' name = 'suljeYllapito' value = '$suljeYllapito'>
						<input type = 'hidden' name = 'del' value ='1'>
						<input type='hidden' name='seuraavatunnus' value = '$seuraavatunnus'>
						<input type = 'submit' value = '".t("Poista $otsikko_nappi")."'></form>";
				}
			}
		}

		echo "</td><td class='back' valign='top'>";

		if ($errori == '' and $toim == "tuote" and $laji != "V") {
			require ("inc/tuotteen_toimittajat.inc");
		}

		if ($errori == '' and $uusi != 1 and $toim == "yhtio") {
			require ("inc/yhtion_toimipaikat.inc");
		}

		if ($errori == '' and ($toim == "toimi" or $toim == "asiakas")) {
			require ("inc/toimittajan_yhteyshenkilo.inc");
			/*
			if ($toim == "asiakas") {
				require ("inc/asiakkaan_avainsanat.inc");
			}
			*/
		}

		if ($errori == '' and ($toim == "sarjanumeron_lisatiedot" or $toim == "tuote")) {
			require ("inc/liitaliitetiedostot.inc");
		}

		echo "</td></tr></table>";

	}
	elseif ($toim != "yhtio" and $toim != "yhtion_parametrit") {
		echo "<br>
				<form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
				<input type = 'hidden' name = 'toim' value = '$aputoim'>
				<input type = 'hidden' name = 'limit' value = '$limit'>
				<input type = 'hidden' name = 'laji' value = '$laji'>
				<input type = 'hidden' name = 'uusi' value = '1'>
				<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
	}

	require ("inc/footer.inc");
?>