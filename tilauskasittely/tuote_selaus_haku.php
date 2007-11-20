<?php
	
	
	if (file_exists("../inc/parametrit.inc")) {
		require ("../inc/parametrit.inc");
		require ("../verkkokauppa/ostoskori.inc");
		$kori_polku = "../verkkokauppa/ostoskori.php";
	}
	else {
		require ("parametrit.inc");
		require ("ostoskori.inc");
		$kori_polku = "ostoskori.php";
	}

	echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";

	//	Tarkastetaan käsitelläänkö lisätietoja
	$query = "describe sarjanumeron_lisatiedot";
	$sarjatestres = mysql_query($query);

	if (mysql_error() == "") {
		$sarjanumeronLisatiedot = "OK";
	}
	else {
		$sarjanumeronLisatiedot = "";
	}

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

	$query    = "select * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	if (is_numeric($ostoskori)) {
		echo "<table><tr><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value='poistakori'>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("Tyhjennä ostoskori")."'>
				</form>";
		echo "</td><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value=''>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("Näytä ostoskori")."'>
				</form>";
		echo "</td></tr></table>";
	}
	elseif ($kukarow["kesken"] != 0 and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "T" or $laskurow["tila"] == "A" or $laskurow["tila"] == "S")) {

		if ($kukarow["extranet"] != "") {
			$toim_kutsu = "EXTRANET";
		}

		echo "	<form method='post' action='tilaus_myynti.php'>
				<input type='hidden' name='toim' value='$toim_kutsu'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";
	}
	
	// Tarkistetaan tilausrivi
	if ($tee == 'TI' and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);

		if (mysql_num_rows($laskures) == 0) {
			echo "<font class='error'>Sinulla ei ole avointa tilausta!</font><br>";
		}
		else {

			// tilauksen tiedot
			$laskurow = mysql_fetch_array($laskures);

			if (is_numeric($ostoskori)) {
				echo "<font class='message'>Lisätään tuotteita ostoskoriin $ostoskori.</font><br>";
			}
			else {
				echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";
			}

			// Käydään läpi formin kaikki rivit
			foreach ($tilkpl as $yht_i => $kpl) {

				if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

					// haetaan tuotteen tiedot
					$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
					$tuoteres = mysql_query($query);

					if (mysql_num_rows($tuoteres) == 0) {
						echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei löydy!</font><br>";
					}
					else {
						// tuote löytyi ok, lisätään rivi
						$trow = mysql_fetch_array($tuoteres);

						$ytunnus         = $laskurow["ytunnus"];
						$kpl             = (float) $kpl;
						$tuoteno         = $trow["tuoteno"];
						$toimaika 	     = $laskurow["toimaika"];
						$kerayspvm	     = $laskurow["kerayspvm"];
						$hinta 		     = "";
						$netto 		     = "";
						$ale 		     = "";
						$alv		     = "";
						$var			 = "";
						$varasto 	     = "";
						$rivitunnus		 = "";
						$korvaavakielto	 = "";
						$varataan_saldoa = "";
						$myy_sarjatunnus = $tilsarjatunnus[$yht_i];
						
						if ($tilpaikka[$yht_i] != '') {
							$paikka	= $tilpaikka[$yht_i];
						}
						else {
							$paikka	= "";
						}

						// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
						if (is_numeric($ostoskori)) {
							lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
							$kukarow["kesken"] = "";
						}
						elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
							require ("../tilauskasittely/lisaarivi.inc");
						}
						else {
							require ("lisaarivi.inc");
						}

						echo "<font class='message'>Lisättiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

						//Hanskataan sarjanumerollisten tuotteiden lisävarusteet
						if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
							require("sarjanumeron_lisavarlisays.inc");

							lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
						}
					} // tuote ok else
				} // end kpl > 0
			} // end foreach
		} // end tuotelöytyi else

		echo "<br>";

		$trow			 = "";
		$ytunnus         = "";
		$kpl             = "";
		$tuoteno         = "";
		$toimaika 	     = "";
		$kerayspvm	     = "";
		$hinta 		     = "";
		$netto 		     = "";
		$ale 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$varataan_saldoa = "";
		$myy_sarjatunnus = "";
		$paikka			 = "";
		$tee 			 = "";
	}


	$kentat	= "tuote.tuoteno,toim_tuoteno,tuote.nimitys,tuote.osasto,tuote.try,tuote.tuotemerkki";
	$nimet	= "Tuotenumero,Toim tuoteno,Nimitys,Osasto,Tuoteryhmä,Tuotemerkki";

	$jarjestys = "tuote.tuoteno";

	$array = split(",", $kentat);
	$arraynimet = split(",", $nimet);

	$lisa  = "";
	$ulisa = "";

	$count = count($array);

	/*

	match againstit ei toimi!!! Kokeile hakusanalla prt firmassa allr

	if (strlen($haku[0]) > 0) {
		$lisa .= " and match (tuote.tuoteno) against ('$haku[0]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[1]) > 0) {
		$lisa .= " and toim_tuoteno like '%$haku[1]%' ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[2]) > 0) {
		$lisa .= " and match (tuote.nimitys) against ('$haku[2]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}

	for ($i=3; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}
	*/

	for ($i=0; $i<=$count; $i++) {
		
		if (strlen($haku[$i]) > 0 && $i == 0) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0 && $i == 1) {
			
			//Otetaan konserniyhtiöt hanskaan
			$query	= "	SELECT distinct tuoteno
						FROM tuotteen_toimittajat
						WHERE yhtio = '$kukarow[yhtio]' 
						and toim_tuoteno like '%".$haku[$i]."%'
						LIMIT 500";
			$pres = mysql_query($query) or pupe_error($query);
			
			$toimtuotteet = "";
			
			while($prow = mysql_fetch_array($pres)) {
				$toimtuotteet .= "'".$prow["tuoteno"]."',";
			}
			
			$toimtuotteet = substr($toimtuotteet, 0, -1);
			
			if ($toimtuotteet != "") {
				$lisa .= " and tuote.tuoteno in ($toimtuotteet) ";		
			}
			
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0 && $i == 2) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}

	if ($poistetut != "") {
		$poislisa  = "";
		$poischeck = "CHECKED";
	}
	else {
		$poislisa  = " HAVING (tuote.status not in ('P','X') or saldo > 0) ";
		$poischeck = "";
	}
	
	/*
	if ($poistuvat != "" or (! isset($submit) and $yhtiorow['poistuvat_tuotteet'] == 'X')) {
		$kohtapoislisa  = "";
		$kohtapoischeck = "CHECKED";
	}
	else {
		$kohtapoislisa  = " and status != 'X' ";
		$kohtapoischeck = "";
	}
	*/
	
	
	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "") {
		$avainlisa = " and avainsana.jarjestys < 10000";
	}
	else {
		$avainlisa = "";
	}

	//Otetaan konserniyhtiöt hanskaan
	$query	= "	SELECT GROUP_CONCAT(distinct concat(\"'\",yhtio,\"'\")) yhtiot
				from yhtio
				where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
	$pres = mysql_query($query) or pupe_error($query);
	$prow = mysql_fetch_array($pres);

	$yhtiot		= "";
	$konsyhtiot = "";

	$yhtiot = "yhtio in (".$prow["yhtiot"].")";
	$konsyhtiot = explode(",", str_replace("'","", $prow["yhtiot"]));

	echo "<table><tr>
			<form action = '$PHP_SELF?toim_kutsu=$toim_kutsu' method = 'post'>";
	echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

	echo "<th nowrap valign='top'>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[0]$ulisa'>".t("$arraynimet[0]")."</a><br>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[1]$ulisa'>".t("$arraynimet[1]")."</a></th>";

	echo "<th nowrap valign='top'><br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[2]$ulisa'>".t("$arraynimet[2]")."</a></th>";

	echo "<th nowrap valign='top'>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[3]$ulisa'>".t("$arraynimet[3]")."</a><br>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[4]$ulisa'>".t("$arraynimet[4]")."</a></th>";

	echo "<th nowrap valign='top'>";
	echo "<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("$arraynimet[5]")."</a>";


	if ($kukarow["extranet"] == "") {
		echo "<br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("Näytä poistuvat")." / ".t("lisätiedot")."</a>";
	}
	echo "</th>";

	echo "</tr><tr>";


	echo "<td nowrap valign='top'>";
	echo "<input type='text' size='10' name = 'haku[0]' value = '$haku[0]'><br>";
	echo "<input type='text' size='10' name = 'haku[1]' value = '$haku[1]'>";
	echo "</td>";

	echo "<td nowrap valign='top'>";
	echo "<input type='text' size='10' name = 'haku[2]' value = '$haku[2]'>";
	echo "</td>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','OSASTO_')."
				WHERE avainsana.yhtio = '$kukarow[yhtio]'
				and avainsana.laji = 'OSASTO'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top'><select name='haku[3]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[3] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select><br>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]'
				and avainsana.laji='TRY'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='haku[4]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[4] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select></td>";


	$query = "	SELECT distinct tuotemerkki
				FROM tuote use index (yhtio_tuotemerkki)
				WHERE yhtio='$kukarow[yhtio]'
				and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top'>";
	echo "<select name='haku[5]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[5] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0]</option>";
	}

	echo "</select><br>";

	if ($kukarow["extranet"] == "") {
		//echo t("Poistuvat")."<input type='checkbox' name='poistuvat' value='X' $kohtapoischeck>";
		echo t("Poistetut").": <input type='checkbox' name='poistetut' $poischeck> ";
	}

	echo t("Lisätiedot").": <input type='checkbox' name='lisatiedot' $lisacheck> ";
	echo "</td>";


	echo "<td class='back' valign='bottom' nowrap><input type='Submit' name='submit' value = '".t("Etsi")."'></td></form></tr>";
	echo "</table><br>";

	// Ei listata mitään jos käyttäjä ei ole tehnyt mitään rajauksia
	if($lisa == "") {
		if (file_exists("../inc/footer.inc")) {
			require ("../inc/footer.inc");
		}
		else {
			require ("footer.inc");
		}

		exit;
	}
	
	$query = "	SELECT 
				ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
				tuote.tuoteno, 
				tuote.nimitys, 
				tuote.osasto, 
				tuote.try, 
				tuote.myyntihinta,
				tuote.nettohinta, 
				tuote.aleryhma, 
				tuote.status, 
				tuote.ei_saldoa, 
				tuote.yksikko,
				(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
				tuote.sarjanumeroseuranta,
				tuote.status,
				(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo				
				FROM tuote use index (tuoteno, nimitys)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				$lisa
				$poislisa
				ORDER BY tuote.tuoteno
				LIMIT 500";
	$result = mysql_query($query) or pupe_error($query);
			
	if (mysql_num_rows($result) > 0) {
		
		$rows = array();
		
		// Rakennetaan array ja laitetaan korvaavat mukaan
		while ($mrow = mysql_fetch_array($result)) {
			if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
				$query = "	SELECT
							'$mrow[tuoteno]' korvaavat,
							tuote.tuoteno, 
							tuote.nimitys, 
							tuote.osasto, 
							tuote.try, 
							tuote.myyntihinta,
							tuote.nettohinta, 
							tuote.aleryhma, 
							tuote.status, 
							tuote.ei_saldoa, 
							tuote.yksikko,
							(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
							tuote.sarjanumeroseuranta,
							tuote.status,
							(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo
							FROM korvaavat
							JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
							WHERE korvaavat.yhtio = '$kukarow[yhtio]'
							and korvaavat.id = '$mrow[korvaavat]'
							$poislisa 
							ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
				$kores = mysql_query($query) or pupe_error($query);
				
				$ekakorva = "";
						
				while ($krow = mysql_fetch_array($kores)) {
					if ($ekakorva == "") $ekakorva = $krow["tuoteno"];
					if(!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
				}								
			}
			else {
				$rows[$mrow["tuoteno"]] = $mrow;
			}
		}
		
		
		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";

		if ($lisatiedot != "") {
			echo "<th>".t("Toim Tuoteno")."</th>";
		}

		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Try")."</th>";
		echo "<th>".t("Hinta")."</th>";
		echo "<th>".t("Aleryhmä")."</th>";

		if ($lisatiedot != "" and $kukarow["extranet"] == "") {
			echo "<th>".t("Nettohinta")."</th>";
			echo "<th>".t("Status")."</th>";
		}

		echo "<th>".t("Myytävissä")."</th>";

        if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
			echo "<th></th>";
		}
		echo "</tr>";

		$edtuoteno = "";

		$yht_i = 0; // tää on meiän indeksi

		echo "<form action='$PHP_SELF' name='lisaa' method='post'>";
		echo "<input type='hidden' name='haku[0]' value = '$haku[0]'>";
		echo "<input type='hidden' name='haku[1]' value = '$haku[1]'>";
		echo "<input type='hidden' name='haku[2]' value = '$haku[2]'>";
		echo "<input type='hidden' name='haku[3]' value = '$haku[3]'>";
		echo "<input type='hidden' name='haku[4]' value = '$haku[4]'>";
		echo "<input type='hidden' name='haku[5]' value = '$haku[5]'>";
		echo "<input type='hidden' name='tee' value = 'TI'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		//Sarjanumeroiden lisätietoja varten
		if ($sarjanumeronLisatiedot == "OK" and @include('sarjanumeron_lisatiedot_popup.inc')) {		
			echo js_popup();	
		}
		else {
			$sarjanumeronLisatiedot = "";
		}
		
		$divit = "";

		foreach($rows as $row) {

			echo "<tr>";

			if (strtoupper($row["status"]) == "P") {
				$vari = "tumma";
			}
			else {
				$vari = "";
			}

			$lisakala = "";

			if ($row["korvaavat"] == $edtuoteno) {
				$lisakala = "* ";

				if ($vari == "") {
					$vari = 'spec';
				}
			}

			// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
			if ($row["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.nimitys nimitys
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
							and (tilausrivi_myynti.tunnus is null)
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);
				
				// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
				if ($row["sarjanumeroseuranta"] == "S") {
					$nimitys = "";
				
					if(mysql_num_rows($sarjares) > 0) {
						$nimitys .= "<table width='100%' valign='top'>";

						while ($sarjarow = mysql_fetch_array($sarjares)) {
							if($sarjarow["nimitys"] != "") {
								$nimitys .= "<tr><td valign='top'>$sarjarow[nimitys]</td></tr>";
							}
							else {
								$nimitys .= "<tr><td valign='top'>$row[nimitys]</td></tr>";
							}
						}
					
						$nimitys .= "</table>";
					
						$row["nimitys"] = $nimitys;
					}
				}				
			}
			
			if(!isset($originaalit)) {
				$orginaaalit = table_exists("tuotteen_orginaalit");
			}
			
			$linkkilisa = "";
			
			//	Liitetään originaalitietoja
			if($orginaaalit === true) {
				$id = md5(uniqid());
				
				$query = "	SELECT * 
							FROM tuotteen_orginaalit
							WHERE yhtio = '{$kukarow["yhtio"]}' and tuoteno = '{$row["tuoteno"]}'";
				$orgres = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($orgres)>0) {
					$linkkilisa = "<div id='$id' class='popup' style='width: 300px'>
					<table width='300px' align='center'>
					<caption><font class='head'>Tuotteen originaalit</font></caption>
					<tr>
						<th>".t("Tuotenumero")."</th>
						<th>".t("Merkki")."</th>						
						<th>".t("Hinta")."</th>
					</tr>";

					while($orgrow = mysql_fetch_array($orgres)) {

						$linkkilisa .= "<tr>
								<td>{$orgrow["orig_tuoteno"]}</td>
								<td>{$orgrow["merkki"]}</td>
								<td>{$orgrow["orig_hinta"]}</td>								
							</tr>";
					}
					
					$linkkilisa .= "</table></div>";
					
					if($kukarow["extranet"] != "") {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='pics/lullacons/info.png' height='13'></a>";
					}
					else {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='../pics/lullacons/info.png' height='13'></a>";
					}
				}				
			}
			
			if ($kukarow["extranet"] != "") {
				echo "<td valign='top' class='$vari'>$lisakala $row[tuoteno]$linkkilisa</td>";
			}
			else {
				echo "<td valign='top' class='$vari'><a href='../tuote.php?tuoteno=$row[tuoteno]&tee=Z'>$lisakala $row[tuoteno]</a>$linkkilisa</td>";
			}
			echo "<td valign='top' class='$vari'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";

			if ($lisatiedot != "") {
				echo "<td valign='top' class='$vari'>$row[toim_tuoteno]</td>";
			}

			echo "<td valign='top' class='$vari'>$row[osasto]</td>";
			echo "<td valign='top' class='$vari'>$row[try]</td>";

			$myyntihinta = $row["myyntihinta"]. " $yhtiorow[valkoodi]";

			// jos kyseessä on extranet asiakas yritetään näyttää kaikki hinnat oikeassa valuutassa
			if ($kukarow["extranet"] != "") {

				$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
				$oleasres = mysql_query($query) or pupe_error($query);
				$oleasrow = mysql_fetch_array($oleasres);

				if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

					$myyntihinta = "$row[myyntihinta] $yhtiorow[valkoodi]";

					$query = "	select *
								from hinnasto
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and valkoodi = '$oleasrow[valkoodi]'
								and laji = ''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
								limit 1";
					$olhires = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($olhires) == 1) {
						$olhirow = mysql_fetch_array($olhires);
						$myyntihinta = "$olhirow[hinta] $olhirow[valkoodi]";
					}
					else {
						$query = "select * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
						$olhires = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($oleasres) == 1) {
							$olhirow = mysql_fetch_array($olhires);
							$myyntihinta = yhtioval($row["myyntihinta"], $olhirow["kurssi"]). " $oleasrow[valkoodi]";
						}
					}
				}

			}
			else {
				$query = "	SELECT distinct valkoodi, maa 
							from hinnasto
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laji = ''
							order by maa, valkoodi";
				$hintavalresult = mysql_query($query) or pupe_error($query);

				while ($hintavalrow = mysql_fetch_array($hintavalresult)) {

					// katotaan onko tuotteelle valuuttahintoja
					$query = "	SELECT *
								from hinnasto
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and valkoodi = '$hintavalrow[valkoodi]'
								and maa = '$hintavalrow[maa]'
								and laji = ''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
								limit 1";
					$hintaresult = mysql_query($query) or pupe_error($query);

					while ($hintarow = mysql_fetch_array($hintaresult)) {
						$myyntihinta .= "<br>$hintarow[maa]: $hintarow[hinta] $hintarow[valkoodi]";
					}
				}
			}

			echo "<td valign='top' class='$vari' align='right'>$myyntihinta</td>";
			echo "<td valign='top' class='$vari'>$row[aleryhma]</td>";

			if ($lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<td valign='top' class='$vari'>$row[nettohinta]</td>";
				echo "<td valign='top' class='$vari'>$row[status]</td>";
			}

			$edtuoteno = $row["korvaavat"];

			if ($row['ei_saldoa'] != '' and $kukarow["extranet"] == "") {
				echo "<td valign='top' class='green'>".t("Saldoton")."</td>";
			}
			elseif ($kukarow["extranet"] != "") {

				$query = "	select *
							from tuoteperhe
							join tuote on tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno and ei_saldoa = ''
							where tuoteperhe.yhtio = '$kukarow[yhtio]' and isatuoteno = '$row[tuoteno]' and tyyppi in ('','P')";
				$isiresult = mysql_query($query) or pupe_error($query);

				// katotaan paljonko on myytävissä
				$kokonaismyytavissa = 0;

				if ($row['ei_saldoa'] == '') {
					foreach($konsyhtiot as $yhtio) {
						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, $yhtio, "", "", "", "", $laskurow["toim_maa"]);
						$kokonaismyytavissa += $myytavissa;
					}
				}

				$lapset=mysql_num_rows($isiresult);
				$oklapset=0;

				if ($lapset > 0) {
					while ($isirow = mysql_fetch_array($isiresult)) {
						$lapsikokonaismyytavissa = 0;
						foreach($konsyhtiot as $yhtio) {
							list($lapsisaldo, $lapsihyllyssa, $lapsimyytavissa) = saldo_myytavissa($isirow["tuoteno"], "", 0, $yhtio, "", "", "", "", $laskurow["toim_maa"]);
							$lapsikokonaismyytavissa += $lapsimyytavissa;
						}
						if ($lapsikokonaismyytavissa > 0) {
							$oklapset++;
						}
					}
				}

				if ($lapset > 0 and $lapset == $oklapset and ($row['ei_saldoa'] != '' or $kokonaismyytavissa > 0)) {
					echo "<td valign='top' class='green'>".t("On")."</td>";
				}
				elseif ($lapset > 0 and $lapset <> $oklapset) {
					echo "<td valign='top' class='red'>".t("Ei")."</td>";
				}
				elseif ($kokonaismyytavissa > 0 or $row['ei_saldoa'] != '') {
					echo "<td valign='top' class='green'>".t("On")."</td>";
				}
				else {
					echo "<td valign='top' class='red'>".t("Ei")."</td>";
				}
			}
			elseif ($row["sarjanumeroseuranta"] == "S") {

				if (is_resource($sarjares) and mysql_num_rows($sarjares)) {
					mysql_data_seek($sarjares, 0);
				}

				echo "<td valign='top' $csp><table width='100%'>";

				while ($sarjarow = mysql_fetch_array($sarjares)) {
					
					if ($sarjanumeronLisatiedot == "OK") {
						list($divitx, $text_output, $kuvalisa_bin, $hankintahinta, $tuotemyyntihinta) = sarjanumeronlisatiedot_popup($sarjarow["tunnus"], '', 'popup', '', '');
						$divit .= $divitx;
					}

					echo "<tr>
							<td class='$vari' onmouseout=\"popUp(event,'$sarjarow[tunnus]')\" onmouseover=\"popUp(event,'$sarjarow[tunnus]')\" nowrap>
							<a href='sarjanumeroseuranta.php?tuoteno_haku=$row[tuoteno]&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a>
							</td>";

					if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
						echo "<td valign='top' class='$vari' nowrap>";
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$sarjarow[tunnus]'>";
						echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
						echo "</td>";
						$yht_i++;
					}
					echo "</tr>";

				}
				echo "</table>";

				if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
					echo "<td valign='top' align='right' class='$vari' nowrap>";
					echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
					echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
					echo "<input type='submit' value = '".t("Lisää")."'>";
					echo "</td>";
					$yht_i++;
				}

				echo "</td>";
			}
			else {

				if ($laskurow["toim_maa"] != '') {
					$sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
				}

				// Käydään läpi tuotepaikat
				if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								$sallitut_maat_lisa
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio 
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.$yhtiot
								and tuote.tuoteno = '$row[tuoteno]'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";		
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								$sallitut_maat_lisa
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.$yhtiot
								and tuote.tuoteno = '$row[tuoteno]'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				$varresult = mysql_query($query) or pupe_error($query);

				echo "<td valign='top'>";

				if (mysql_num_rows($varresult) > 0) {

					echo "<table width='100%'>";

					// katotaan jos meillä on tuotteita varaamassa saldoa joiden varastopaikkaa ei enää ole olemassa...
					list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], "ORVOT");
					$orvot *= -1;

					while ($saldorow = mysql_fetch_array ($varresult)) {

						list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], '', $saldorow["era"]);

						//	Listataan vain varasto jo se ei ole kielletty
						if($sallittu === TRUE) {
							// hoidetaan pois problematiikka jos meillä on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
							if ($orvot > 0) {
								if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
							    	// poistaan orpojen varaamat tuotteet tältä paikalta
							    	$myytavissa = $myytavissa - $orvot;
							    	$orvot = 0;
								}
								elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
							    	// poistetaan niin paljon orpojen saldoa ku voidaan
							    	$orvot = $orvot - $myytavissa;
							    	$myytavissa = 0;
								}
							}

							echo "<tr>
									<td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
									<td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." $row[yksikko]</td>
									</tr>";
						}
					}
					echo "</table></td>";
				}
				echo "</td>";
			}

			if (($row["sarjanumeroseuranta"] == "" or $row["sarjanumeroseuranta"] == "E"  or $row["sarjanumeroseuranta"] == "F" or $kukarow["extranet"] != "") and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {
				echo "<td valign='top' align='right' class='$vari' nowrap>";
				echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
				echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
				echo "<input type='submit' value = '".t("Lisää")."'>";
				echo "</td>";
				$yht_i++;
			}


			echo "</tr>";
		}

		echo "</form>";
		echo "</table>";

		//sarjanumeroiden piilotetut divit
		echo $divit;
	}
	else {
		echo t("Yhtään tuotetta ei löytynyt")."!";
	}

	if(mysql_num_rows($result) == 500) {
		echo "<br><br><font class='message'>".t("Löytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
	}

	if (file_exists("../inc/footer.inc")) {
		require ("../inc/footer.inc");
	}
	else {
		require ("footer.inc");
	}
?>
