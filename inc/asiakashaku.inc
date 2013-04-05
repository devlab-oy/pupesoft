<?php

// tarvitaan $ytunnus (jolla haetaan) tai $asiakasid (joka on asiakas.tunnus) ja $kukarow
// palautetaan $asiakasrow

if (isset($konserni) and trim($konserni) != '') {
	if ($konserni == "KONSERNIVARASTO") {
		if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
			$konsyhtiot = "yhtio in ($konsernivarasto_yhtiot) ";
		}
		else {
			$konsyhtiot = "yhtio = '$kukarow[yhtio]' ";
		}
	}
	else {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = pupe_query($query);
		$konsyhtiot = "";

		while ($row = mysql_fetch_array($result)) {
			$konsyhtiot .= " '".$row["yhtio"]."' ,";
		}
		$konsyhtiot = "yhtio in (".substr($konsyhtiot, 0, -1).") ";
	}
}
else {
	$konserni 	= "";
	$konsyhtiot = "yhtio = '$kukarow[yhtio]' ";
}

$ytunnus	 	= addslashes(trim($ytunnus));
$limit  		= 1000; // montako riviä on maksimi
$monta   		= 0;
$asiakasrajaus 	= "";

if (in_array($kutsuja, array("asiakasemo.php","kalenteri.php"))) {
	// Tässä haarassa myös prospektit näytetään
	if ($myos_poistetut == "") {
		$asiakasrajaus = " and asiakas.laji != 'P' ";
	}
	else {
		$asiakasrajaus = "";
	}
}
else {
	// Normaalisti prospekteja ei näytetä, mutta jos ollaan tarjouksella voidaa niitä katsoa
	if ($myos_prospektit == "TRUE" and $kutsuja == "otsik.inc" and $toim == "TARJOUS") {
		$asiakasrajaus = " and asiakas.laji not in ('P') ";
	}
	elseif ($myos_poistetut == "") {
		$asiakasrajaus = " and asiakas.laji not in ('P','R') ";
	}
	else {
		$asiakasrajaus = " and asiakas.laji != 'R' ";
	}
}

//	Jos käyttäjällä on valittu piirejä niin sallitaan vain ko. piirin/piirien hakeminen
if ($kukarow["piirit"] != "") {
	$asiakasrajaus .= " and asiakas.piiri IN ($kukarow[piirit]) ";
}

if (is_numeric($asiakasid) and $asiakasid > 0) {
	// Jos asiakaan tunnus on löydetty niin ei rajata "$asiakasrajaus":lla ollenkaan
	$query	= "	SELECT asiakas.*, yhtio.nimi yhtionimi
				FROM asiakas
				JOIN yhtio on asiakas.yhtio=yhtio.yhtio
				WHERE asiakas.$konsyhtiot
				and asiakas.tunnus = '$asiakasid'";
	$result = pupe_query($query);
	$monta  = mysql_num_rows($result);
}
elseif ($ytunnus != '') {
	if (substr($ytunnus,0,1) == "£") {
		// jos eka merkki £ etsitään tilausnumerolla
		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		$query  = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
					lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					concat('£',lasku.tunnus) ytunnus, concat('£',lasku.tunnus) tunnus, yhtio.nimi yhtionimi
					FROM lasku
					JOIN yhtio on lasku.yhtio=yhtio.yhtio
					WHERE lasku.$konsyhtiot
					and lasku.tunnus = '$ytunnus'";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);
	}

	if (substr($ytunnus,0,1) == "*") {
		// jos eka merkki on * etsitään laskuilta asiakastietoja nimellä

		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		// kokeillaan ihan asiakkaan nimellä laskulta
		$query	= "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
					lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
					FROM lasku use index (asiakasnimi)
					JOIN yhtio on lasku.yhtio=yhtio.yhtio
					WHERE lasku.$konsyhtiot
					and lasku.tila != 'D'
					and match (lasku.nimi) against ('$ytunnus*' IN BOOLEAN MODE)
					GROUP BY 1,2,3,4,5,6,7,8,9,10
					ORDER BY 1";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);

		if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
	}

	if ($monta == 0  and substr($ytunnus,0,1) == "#") {
		// jos eka merkki on # etsitään toimitusnimen perusteella

		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		// kokeillaan ihan asiakkaan nimellä laskulta
		$query	= "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
					lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
					FROM lasku use index (asiakastoim_nimi)
					JOIN yhtio on lasku.yhtio=yhtio.yhtio
					WHERE lasku.$konsyhtiot
					and lasku.tila != 'D'
					and match (lasku.toim_nimi) against ('$ytunnus*' IN BOOLEAN MODE)
					GROUP BY 1,2,3,4,5,6,7,8,9,10
					ORDER BY 1";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);

		if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
	}

	if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
		$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
					lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
					concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
					FROM lasku
					JOIN yhtio on lasku.yhtio=yhtio.yhtio
					JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
					WHERE lasku.$konsyhtiot
					and lasku.tila in ('A','L','N')
					and tyomaarays.rekno = '$ytunnus'
					GROUP BY 1,2,3,4,5,6,7,8,9,10
					ORDER BY 1
					LIMIT 50";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);

		if ($monta == 0) {
			$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
						lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
						concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
						FROM lasku
						JOIN yhtio on lasku.yhtio=yhtio.yhtio
						JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
						WHERE lasku.$konsyhtiot
						and lasku.tila in ('A','L','N')
						and tyomaarays.valmnro = '$ytunnus'
						GROUP BY 1,2,3,4,5,6,7,8,9,10
						ORDER BY 1
						LIMIT 50";
			$result = pupe_query($query);
			$monta  = mysql_num_rows($result);
		}
	}

	if ($monta == 0) {
		// exactihaku
		$query	= "	(SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and REPLACE(REPLACE(asiakas.ytunnus, '-', ''), '+', '') like '%".str_replace(array('-','+'), '', $ytunnus)."%'
						and asiakas.ytunnus!=''
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.asiakasnro = '$ytunnus'
						and asiakas.asiakasnro not in ('0','')
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.ovttunnus = '$ytunnus'
						and asiakas.ovttunnus!=''
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.toim_ovttunnus = '$ytunnus'
						and asiakas.toim_ovttunnus!=''
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi, '5' osuma
						FROM asiakas
						JOIN asiakkaan_avainsanat ON (asiakas.yhtio=asiakkaan_avainsanat.yhtio and asiakas.tunnus=asiakkaan_avainsanat.liitostunnus and asiakkaan_avainsanat.laji='kantaasiakastunnus')
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakkaan_avainsanat.avainsana = '$ytunnus'
						and asiakkaan_avainsanat.avainsana!=''
						$lajilisa $asiakasrajaus)
					ORDER BY nimi";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);
	}

	if ($monta == 0) {
		// kokeillaan ytunnuksen alulla
		$query	= "	SELECT asiakas.*, yhtio.nimi yhtionimi
					FROM asiakas
					JOIN yhtio on asiakas.yhtio=yhtio.yhtio
					WHERE asiakas.$konsyhtiot
					and asiakas.ytunnus like '$ytunnus%'
					and asiakas.ytunnus!=''
					$lajilisa
					$asiakasrajaus
					ORDER BY asiakas.nimi";
		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);
	}

	if ($monta == 0 and is_string($ytunnus)) {

		// ei rikota alkuperäistä ytunnus-muuttuja
		$ytunnus2 = $ytunnus;

		// mahdollistetaan asiakashaku monella eri sanalla
		if (strpos($ytunnus," ") !== FALSE) {
			$ytunnus2 = str_replace(" ", "%", $ytunnus);
		}

		$osoitelisa = '';

		if (trim($osoite) != '') {
			$osoitelisa = " and asiakas.osoite like '".mysql_real_escape_string($osoite)."%' ";
			$osoite_toim_lisa = " and asiakas.toim_osoite like '".mysql_real_escape_string($osoite)."%' ";
		}

		$postitplisa = '';

		// jos on annettu postitoimipaikka, niin lisätää se jokaiseen asiakashakuqueryyn ja ei tehdä erillistä postitoimipaikka unionia
		if (trim($postitp) != '') {
			$postitp2 = mysql_real_escape_string($postitp);
			$postitplisa = " and asiakas.postitp like '%$postitp2%' ";
			$postitp_toim_lisa = " and asiakas.toim_postitp like '%$postitp2%' ";
		}
		else {
			$postitp2 = $ytunnus;
		}

		$postinolisa = '';

		if (trim($postino) != '') {
			$postinolisa = " and asiakas.postino like '%".(int) $postino."%' ";
			$postino_toim_lisa = " and asiakas.toim_postino like '%".(int) $postino."%' ";
		}

		// arvailuhaku
		$query	= "	(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.nimi like '%$ytunnus2%'
						and asiakas.nimi!=''
						$osoitelisa
						$postitplisa
						$postinolisa
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.nimitark like '%$ytunnus2%'
						and asiakas.nimitark!=''
						$osoitelisa
						$postitplisa
						$postinolisa
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.toim_nimi like '%$ytunnus2%'
						and asiakas.toim_nimi!=''
						$osoite_toim_lisa
						$postitp_toim_lisa
						$postino_toim_lisa
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.toim_nimitark like '%$ytunnus2%'
						and asiakas.toim_nimitark!=''
						$osoite_toim_lisa
						$postitp_toim_lisa
						$postino_toim_lisa
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.asiakasnro like '%$ytunnus2%'
						and asiakas.asiakasnro not in ('0','')
						$osoitelisa
						$postitplisa
						$postinolisa
						$lajilisa $asiakasrajaus)
					UNION
					(SELECT asiakas.*, yhtio.nimi yhtionimi
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
						WHERE asiakas.$konsyhtiot
						and asiakas.ovttunnus like '%$ytunnus2%'
						and asiakas.ovttunnus!=''
						$osoitelisa
						$postitplisa
						$postinolisa
						$lajilisa $asiakasrajaus)";

		if (trim($postitplisa) == '') {
			$query .= "	UNION
						(SELECT asiakas.*, yhtio.nimi yhtionimi
							FROM asiakas
							JOIN yhtio on asiakas.yhtio=yhtio.yhtio
							WHERE asiakas.$konsyhtiot
							and asiakas.postitp like '%$postitp2%'
							and asiakas.postitp!=''
							$osoitelisa
							$postinolisa
							$lajilisa $asiakasrajaus)
						ORDER BY nimi";
		}

		$result = pupe_query($query);
		$monta  = mysql_num_rows($result);
	}
}
elseif ($asiakasnro != '') {
	$query = "	SELECT asiakas.*, yhtio.nimi yhtionimi
				FROM asiakas
				JOIN yhtio on asiakas.yhtio = yhtio.yhtio
				WHERE asiakas.$konsyhtiot
		 		AND asiakas.asiakasnro = '$asiakasnro'
				$asiakasrajaus";
	$result = pupe_query($query);
	$monta  = mysql_num_rows($result);
}

if ($monta == 0) {
	if ($ytunnus and strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
		echo "<font class='error'>".t("Asiakas")." ". tarkistahetu($ytunnus)." ".t("ei löydy")."!</font> ";
	}
	elseif ($asiakasnro and strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
		echo "<font class='error'>".t("Asiakas")." '$asiakasnro' ".t("ei löydy")."!</font> ";
	}

	if (($ytunnus or $asiakasnro) and strpos($PHP_SELF, "tilaus_myynti") === FALSE and $kutsuja != "yllapito.php") {
		echo "<form method='post'>";
		echo "<input type='hidden' name='myos_poistetut'  value='TRUE'>";
		echo "<input type='hidden' name='tee' 			  value='$tee'>";
		echo "<input type='hidden' name='konserni'		  value='$konserni'>";
		echo "<input type='hidden' name='toim' 			  value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
		echo "<input type='hidden' name='from' 	  		  value='$from'>";
		echo "<input type='hidden' name='tila' 			  value='$tila'>";
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='ytunnus'  		  value='$ytunnus'>";
		echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
		echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
		echo "<input type='hidden' name='alatila'		  value='$alatila'>";
		echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
		echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
		echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
		echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
		echo "<input type='hidden' name='tuoteno' 		  value='$tuoteno'>";
		echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";

		if (is_array($varastosta)) {
			foreach ($varastosta as $varatunnu) {
				echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
			}
		}

		echo "<input type='submit' value='".t("Etsi myös poistettuja asiakkaita")."'>";
		echo "</form>";
	}

	if (($ytunnus or $asiakasnro) and $kutsuja == "otsik.inc" and $toim == "TARJOUS") {
		echo "<form method='post'>";
		echo "<input type='hidden' name='myos_prospektit' value='TRUE'>";
		echo "<input type='hidden' name='tee' 			  value='$tee'>";
		echo "<input type='hidden' name='konserni'		  value='$konserni'>";
		echo "<input type='hidden' name='toim' 			  value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
		echo "<input type='hidden' name='from' 	  		  value='$from'>";
		echo "<input type='hidden' name='tila' 			  value='$tila'>";
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='ytunnus'  		  value='$ytunnus'>";
		echo "<input type='hidden' name='syotetty_ytunnus'value='$ytunnus'>";
		echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
		echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
		echo "<input type='hidden' name='alatila'		  value='$alatila'>";
		echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
		echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
		echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
		echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
		echo "<input type='hidden' name='tuoteno' 		  value='$tuoteno'>";
		echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";

		if (is_array($varastosta)) {
			foreach ($varastosta as $varatunnu) {
				echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
			}
		}

		echo "<input type='submit' value='".t("Etsi myös prospekti asiakkaita")."'>";
		echo "</form>";
	}

	if (strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
		echo "<br><br>";
	}

	$ytunnus = "";
	$asiakasnro = "";
}

if ($monta == 1) {
	$asiakasrow	= mysql_fetch_array($result);
	$ytunnus	= $asiakasrow['ytunnus'];

	if ($asiakasnro != "") {
		$asiakasnro  = $asiakasrow['asiakasnro'];
	}

	$asiakasid = $asiakasrow['tunnus'];

	if ($kutsuja == 'otsik.inc' and $asiakasrow['osuma'] == '5') {
		$query = "	SELECT avainsana
					FROM asiakkaan_avainsanat
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji = 'kantaasiakastunnus'
					AND liitostunnus = '$asiakasid'";
		$kantaasiakastunnus_result = pupe_query($query);
		$kantaasiakastunnus_row = mysql_fetch_assoc($kantaasiakastunnus_result);

		$kantaasiakastunnus = $kantaasiakastunnus_row['avainsana'];
	}
}

if ($monta > 1 and $monta <= $limit) {

	if ($kutsuja != "yllapito.php") {
		//tämä vain otsik.php:tä varten
		echo $lause;

		echo "<table border='0'>";
		echo "<tr class='aktiivi'>";

		if ($konserni != '') {
			echo "<th>".t("yhtiö")."</th>";
		}

		echo "<th>".t("ytunnus")."</th>
				<th>".t("asnro")."</th>
				<th>".t("lasknimi/toimnimi")."</th>
				<th colspan='3'>".t("laskosoite/toimosoite")."</th>
				<td class='back'></td></tr>";
	}

	while ($asiakas = mysql_fetch_array($result)) {

		if     ($asiakas['ytunnus']    != '') $numero = trim($asiakas['ytunnus']);
		elseif ($asiakas['asiakasnro'] != '') $numero = trim($asiakas['asiakasnro']);
		else    die("Tällä asiakkaalla ei ole mitään tietoja! Tee JOTAIN!");

		$brnim 		= '';
		$bros 		= '';
		$brpostino  = '';
		$brpostitp  = '';

		$asiakas['toim_nimi']		= trim($asiakas['toim_nimi']);
		$asiakas['toim_osoite']		= trim($asiakas['toim_osoite']);
		$asiakas['toim_postino']	= trim($asiakas['toim_postino']);
		$asiakas['toim_postitp']	= trim($asiakas['toim_postitp']);

		$asiakas['nimi']			= trim($asiakas['nimi']);
		$asiakas['osoite']			= trim($asiakas['osoite']);
		$asiakas['postino']			= trim($asiakas['postino']);
		$asiakas['postitp']			= trim($asiakas['postitp']);

		if ($kutsuja != "yllapito.php") {
			// jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
			if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"] or $asiakas["toim_nimitark"] != $asiakas["nimitark"]) and
				($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "" or $asiakas["toim_nimitark"] != "")) {
				$brnim     = '<br>'.$asiakas['toim_nimi']." ".$asiakas['toim_nimitark'];
				$bros      = '<br>'.$asiakas['toim_osoite'];
				$brpostino = '<br>'.$asiakas['toim_postino'];
				$brpostitp = '<br>'.$asiakas['toim_postitp'];
			}

			echo "<tr class='aktiivi'>";

			if ($konserni != '') {
				echo "<td>$asiakas[yhtionimi]</td>";
			}

			if ($asiakas["laji"] == "K" and $yhtiorow["yhtio"] == "artr") {
				$brnim .= "<br><font class='error'>".t("HUOM: Tämä on korjaamo-asiakas")."</font>";
			}

			echo "<td>$asiakas[ytunnus]</td>
					<td>$asiakas[asiakasnro]</td>
					<td>$asiakas[nimi] $asiakas[nimitark] $brnim</td>
					<td>$asiakas[osoite] $bros</td>
					<td>$asiakas[postino] $brpostino</td>
					<td>$asiakas[postitp] $brpostitp</td>";

			echo "<form method='post'>";
			echo "<td class='back'>";
			echo "<input type='hidden' name='asiakasid' 	  value='$asiakas[tunnus]'>";
			echo "<input type='hidden' name='tee' 			  value='$tee'>";
			echo "<input type='hidden' name='konserni'		  value='$konserni'>";
			echo "<input type='hidden' name='toim' 			  value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
			echo "<input type='hidden' name='from' 	  		  value='$from'>";
			echo "<input type='hidden' name='tila' 			  value='$tila'>";
			echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
			echo "<input type='hidden' name='ytunnus'  		  value='$numero'>";
			echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
			echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
			echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
			echo "<input type='hidden' name='alatila'		  value='$alatila'>";
			echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
			echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
			echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
			echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
			echo "<input type='hidden' name='tuoteno' 		  value='$tuoteno'>";
			echo "<input type='hidden' name='lasku_yhtio'	  value='$asiakas[yhtio]'>";
			echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";

			if (is_array($varastosta)) {
				foreach ($varastosta as $varatunnu) {
					echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
				}
			}

			if (is_array($oletus)){
				foreach($oletus as $o => $a) {
					echo "<input type='hidden' name='oletus[$o]' value='$a'>";
				}
			}

			echo "<input type='submit' name='valitse_asiakas' value='".t("Valitse")."'>";
			echo "</td>";
			echo "</form>";
			echo "</tr>";
		}
		else {
			// jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
			if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"]) and
				($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "")) {
				$brnim     = ', '.$asiakas['toim_nimi']." ".$asiakas['toim_nimitark'];
				$bros      = ' '.$asiakas['toim_osoite'];
				$brpostino = ' '.$asiakas['toim_postino'];
				$brpostitp = ' '.$asiakas['toim_postitp'];
			}

			$ulos  .= "<option value='$asiakas[ytunnus]'>$asiakas[ytunnus] $asiakas[nimi] $asiakas[osoite] $asiakas[postino] $asiakas[postitp] $brnim $bros $brpostitp $brpostitp</option>";
			$ulos2 .= "<option value='$asiakas[tunnus]'>$asiakas[ytunnus] $asiakas[nimi] $asiakas[nimitark] $asiakas[osoite] $asiakas[postino] $asiakas[postitp] $brnim $bros $brpostitp $brpostitp</option>";
		}
	}

	if ($kutsuja != "yllapito.php") {
		echo "</table><br>";

		if (($ytunnus or $asiakasnro) and strpos($PHP_SELF, "tilaus_myynti") === FALSE) {
			echo "<form method='post'>";
			echo "<input type='hidden' name='myos_poistetut'  value='TRUE'>";
			echo "<input type='hidden' name='tee' 			  value='$tee'>";
			echo "<input type='hidden' name='konserni'		  value='$konserni'>";
			echo "<input type='hidden' name='toim' 			  value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
			echo "<input type='hidden' name='from' 	  		  value='$from'>";
			echo "<input type='hidden' name='tila' 			  value='$tila'>";
			echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
			echo "<input type='hidden' name='ytunnus'  		  value='$ytunnus'>";
			echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
			echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
			echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
			echo "<input type='hidden' name='alatila'		  value='$alatila'>";
			echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
			echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
			echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
			echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
			echo "<input type='hidden' name='lasku_yhtio'	  value='$asiakas[yhtio]'>";
			echo "<input type='hidden' name='tuoteno' 		  value='$tuoteno'>";
			echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";

			if (is_array($varastosta)) {
				foreach ($varastosta as $varatunnu) {
					echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
				}
			}

			echo "<input type='submit' value='".t("Etsi myös poistettuja asiakkaita")."'>";
			echo "</form><br>";
		}
	}

	if (($ytunnus or $asiakasnro) and $kutsuja == "otsik.inc" and $toim == "TARJOUS") {
		echo "<form method='post'>";
		echo "<input type='hidden' name='myos_prospektit' value='TRUE'>";
		echo "<input type='hidden' name='tee' 			  value='$tee'>";
		echo "<input type='hidden' name='konserni'		  value='$konserni'>";
		echo "<input type='hidden' name='toim' 			  value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
		echo "<input type='hidden' name='from' 	  		  value='$from'>";
		echo "<input type='hidden' name='tila' 			  value='$tila'>";
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='ytunnus'  		  value='$ytunnus'>";
		echo "<input type='hidden' name='syotetty_ytunnus'value='$ytunnus'>";
		echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
		echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
		echo "<input type='hidden' name='alatila'		  value='$alatila'>";
		echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
		echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
		echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
		echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
		echo "<input type='hidden' name='tuoteno' 		  value='$tuoteno'>";
		echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";

		if (is_array($varastosta)) {
			foreach ($varastosta as $varatunnu) {
				echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
			}
		}

		echo "<input type='submit' value='".t("Etsi myös prospekti asiakkaita")."'>";
		echo "</form>";
	}


	$ytunnus = "";
}

if ($monta > $limit) {
	echo "<font class='error'>".t("Asiakashaulla löytyi")." $monta ".t("sopivaa asiakasta. Tarkenna hakuasi")."!</font><br><br>";
	$ytunnus = "";
}

if ($kutsuja == "otsik.inc" or $kutsuja == "asiakasemo.php" or $kutsuja == "kalenteri.php") {
	$query = "	SELECT yhtio
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'yllapito.php'
				and alanimi = 'asiakas!!!VAHITTAISMYYNTI!!!true&laji=H'
				and paivitys != ''";
	$oikeuresult1 = pupe_query($query);

	$query = "	SELECT yhtio
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'yllapito.php'
				and alanimi = 'asiakas'
				and paivitys != ''";
	$oikeuresult2 = pupe_query($query);

	if ($monta != 1 and (mysql_num_rows($oikeuresult1) > 0 or mysql_num_rows($oikeuresult2) > 0)) {

		//Annetaan mahdollisuus perustaa uusi asiakas
		echo "<table border='0'>";
		echo "<tr><td>".t("Oikeaa asiakasta ei löytynyt. Perusta uusi asiakas")."</td>";

		if (mysql_num_rows($oikeuresult1) > 0) {
			echo "<td class='back'>";
			echo "<form action='../yllapito.php' method='post'>";
			echo "<input type='hidden' name='lopetus' 	value='$ahlopetus'>";
			echo "<input type='hidden' name='toim'		value='asiakas!!!VAHITTAISMYYNTI!!!true'>";
			echo "<input type='hidden' name='laji'		value='H'>";
			echo "<input type='hidden' name='uusi'		value='1'>";
			echo "<input type='submit' value='".t("Uusi henkilöasiakas")."'>";
			echo "</form>";
			echo "</td>";
		}

		if (mysql_num_rows($oikeuresult2) > 0) {
			echo "<td class='back'>";
			echo "<form action='../yllapito.php' method='post'>";
			echo "<input type='hidden' name='lopetus' 	value='$ahlopetus'>";
			echo "<input type='hidden' name='toim'		value='asiakas'>";
			echo "<input type='hidden' name='laji'		value='Y'>";
			echo "<input type='hidden' name='uusi'		value='1'>";
			echo "<input type='submit' value='".t("Uusi yritysasiakas")."'>";
			echo "</form>";
			echo "</td>";
		}

		echo "</tr>";
		echo "</table><br>";
	}
}
