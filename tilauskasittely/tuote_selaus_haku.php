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

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

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
	elseif ($kukarow["kesken"] != 0) {
		if ($kukarow["extranet"] != "") {
			$toim_kutsu = "EXTRANET";
		}

		echo "	<form method='post' action='tilaus_myynti.php'>
				<input type='hidden' name='toim' value='$toim_kutsu'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form>";
	}


	$query    = "select * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

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

			// käydään läpi formin kaikki rivit
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

	$jarjestys = "sorttauskentta";

	$array = split(",", $kentat);
	$arraynimet = split(",", $nimet);

	$lisa = "";
	$ulisa = "";

	$count = count($array);

	for ($i=0; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0 && $i <= 1) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
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
		$poislisa  = " and status != 'P' ";
		$poischeck = "";
	}

	if ($kukarow["extranet"] != "") {
		$avainlisa = " and jarjestys < 10000";
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

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[0]$ulisa'>".t("$arraynimet[0]")."</a>";
	echo "<br><input type='text' size='10' name = 'haku[0]' value = '$haku[0]'>";
	echo "</th>";

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[1]$ulisa'>".t("$arraynimet[1]")."</a>";
	echo "<br><input type='text' size='10' name = 'haku[1]' value = '$haku[1]'>";
	echo "</th>";

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[2]$ulisa'>".t("$arraynimet[2]")."</a>";
	echo "<br><input type='text' size='10' name = 'haku[2]' value = '$haku[2]'>";
	echo "</th>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]'
				and laji='OSASTO'
				$avainlisa
				ORDER BY jarjestys, selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[3]$ulisa'>".t("$arraynimet[3]")."</a>";
	echo "<br><select name='haku[3]'>";
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
	echo "</select></th>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]'
				and laji='TRY'
				$avainlisa
				ORDER BY jarjestys, selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[4]$ulisa'>".t("$arraynimet[4]")."</a>";
	echo "<br><select name='haku[4]'>";
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
	echo "</select></th>";


	$query = "	SELECT distinct tuotemerkki
				FROM tuote use index (yhtio_tuotemerkki)
				WHERE yhtio='$kukarow[yhtio]'
				$poislisa
				and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<th><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("$arraynimet[5]")."</a>";
	echo "<br><select name='haku[5]'>";
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

	echo "</select></th>";

	if ($kukarow["extranet"] == "") {
		echo "<th>P";
		echo "<br><input type='checkbox' name='poistetut' $poischeck>";
		echo "</th>";
	}

	echo "<td class='back' valign='bottom'><input type='Submit' value = '".t("Etsi")."'></td></form></tr>";
	echo "</table><br>";

	// Ei listata mitään jos käyttäjä ei ole tehnyt mitään rajauksia
	if($lisa == "") {
		exit;
	}

	if ($kukarow["extranet"] == "") {
		$query = "	SELECT valitut.sorttauskentta, tuote_wrapper.tuoteno, tuote_wrapper.nimitys, valitut.toim_tuoteno, tuote_wrapper.osasto, tuote_wrapper.try, tuote_wrapper.myyntihinta,
					tuote_wrapper.nettohinta, tuote_wrapper.aleryhma, tuote_wrapper.status, tuote_wrapper.ei_saldoa, tuote_wrapper.yksikko,
					valitut.sarjatunnus, valitut.sarjanumero, valitut.sarjayhtio,
					valitut.toimitiedot
					FROM tuote tuote_wrapper,
					(	SELECT if(korvaavat.id>0,(select tuoteno from korvaavat korva2 where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),tuote.tuoteno) sorttauskentta,
						ifnull(korvaavat.tuoteno, tuote.tuoteno) tuoteno,
						sarjanumeroseuranta.tunnus sarjatunnus, sarjanumeroseuranta.sarjanumero sarjanumero, sarjanumeroseuranta.yhtio sarjayhtio,
						group_concat(concat(toimi.tyyppi_tieto,'##',tuotteen_toimittajat.liitostunnus)) toimitiedot,
						group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno
						FROM tuote
						LEFT JOIN sarjanumeroseuranta ON sarjanumeroseuranta.$yhtiot and tuote.tuoteno=sarjanumeroseuranta.tuoteno and sarjanumeroseuranta.ostorivitunnus>0 and sarjanumeroseuranta.myyntirivitunnus=0
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
						LEFT JOIN toimi ON 	toimi.yhtio         	= tuotteen_toimittajat.yhtio
											and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
											and toimi.tyyppi        = 'S'
											and toimi.tyyppi_tieto != ''
											and toimi.edi_palvelin != ''
											and toimi.edi_kayttaja != ''
											and toimi.edi_salasana != ''
											and toimi.edi_polku    != ''
											and toimi.oletus_vienti in ('C','F','I')
						LEFT JOIN korvaavat ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$lisa
						$poislisa
						GROUP BY 1,2,3,4,5
						ORDER BY $jarjestys
					) valitut
					WHERE tuote_wrapper.yhtio = '$kukarow[yhtio]'
					and valitut.tuoteno = tuote_wrapper.tuoteno
					LIMIT 500";
		$miinus = 6;
	}
	else {
		$query = "	SELECT valitut.sorttauskentta, tuote_wrapper.tuoteno, tuote_wrapper.nimitys, valitut.toim_tuoteno, tuote_wrapper.myyntihinta,
					tuote_wrapper.aleryhma, tuote_wrapper.ei_saldoa, tuote_wrapper.yksikko,
					valitut.sarjatunnus, valitut.sarjanumero, valitut.sarjayhtio
					FROM tuote tuote_wrapper,
					(	SELECT if(korvaavat.id>0,(select tuoteno from korvaavat korva2 where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),tuote.tuoteno) sorttauskentta,
						ifnull(korvaavat.tuoteno, tuote.tuoteno) tuoteno,
						sarjanumeroseuranta.tunnus sarjatunnus, sarjanumeroseuranta.sarjanumero sarjanumero, sarjanumeroseuranta.yhtio sarjayhtio,
						group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno
						FROM tuote
						LEFT JOIN sarjanumeroseuranta ON sarjanumeroseuranta.$yhtiot and tuote.tuoteno=sarjanumeroseuranta.tuoteno and sarjanumeroseuranta.ostorivitunnus>0 and sarjanumeroseuranta.myyntirivitunnus=0
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
						LEFT JOIN korvaavat ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.myyntihinta > 0
						$lisa
						$poislisa
						GROUP BY 1,2,3,4,5
						ORDER BY $jarjestys
					) valitut
					WHERE tuote_wrapper.yhtio = '$kukarow[yhtio]'
					and valitut.tuoteno = tuote_wrapper.tuoteno
					LIMIT 500";
		$miinus = 5;
	}
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table>";
		echo "<tr>";

		for ($i=1; $i < mysql_num_fields($result)-$miinus; $i++) {
			echo "<th>".t(mysql_field_name($result,$i))."</th>";
		}

       	echo "<th>".t("myytävissä")."</th>";

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
		if (file_exists("sarjanumeron_lisatiedot_popup.inc")) {
			require("sarjanumeron_lisatiedot_popup.inc");
		}

		if (function_exists("js_popup")) {
			echo js_popup(50);
		}
		$divit = "";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";

			if (strtoupper($row["status"]) == "P") {
				$vari = "tumma";
			}
			else {
				$vari = "";
			}

			$lisakala = "";
			if ($row["sarjatunnus"] == 0 and $row["sorttauskentta"] == $edtuoteno) {
				$lisakala = "* ";
				if ($vari == "") {
					$vari = 'spec';
				}
			}

			for ($i=1; $i < mysql_num_fields($result)-$miinus; $i++) {
				if (mysql_field_name($result,$i) == "tuoteno" and $kukarow["extranet"] == "") {
					echo "<td class='$vari'><a href='../tuote.php?tuoteno=$row[$i]&tee=Z'>$lisakala $row[$i]</a></td>";
				}
				elseif (mysql_field_name($result,$i) == "tuoteno" and $kukarow["extranet"] != "") {
					echo "<td class='$vari'>$lisakala $row[$i]</td>";
				}
				else {
					echo "<td class='$vari'>$row[$i]</td>";
				}
			}

			$edtuoteno = $row["sorttauskentta"];


			if ($row["sarjatunnus"] > 0 and $kukarow["extranet"] == "") {

				if (function_exists("sarjanumeronlisatiedot_popup")) {
					$divit .= sarjanumeronlisatiedot_popup ($row["sarjatunnus"], $row["sarjayhtio"]);
				}

				$query = "	SELECT *
							FROM sarjanumeroseuranta
							WHERE yhtio	= '$row[sarjayhtio]'
							and tuoteno	= '$row[tuoteno]'
							and tunnus	= '$row[sarjatunnus]'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$varasto = kuuluukovarastoon($sarrow["hyllyalue"], $sarrow["hyllynro"], "", $sarrow["yhtio"]);

				$query = "	SELECT varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.tyyppi, yhtio.nimi
							FROM varastopaikat
							JOIN yhtio ON varastopaikat.yhtio=yhtio.yhtio
							WHERE varastopaikat.yhtio='$sarrow[yhtio]' and varastopaikat.tunnus='$varasto'";
				$varresult = mysql_query($query) or pupe_error($query);
				$varastorivi = mysql_fetch_array($varresult);

				if ($sarrow["yhtio"] != $kukarow["yhtio"]) {
					$nimilisa = $varastorivi["nimi"].", ";
				}
				else {
					$nimilisa = "";
				}

				echo "<td class='$vari' onmouseout=\"popUp(event,'$row[sarjatunnus]')\" onmouseover=\"popUp(event,'$row[sarjatunnus]')\">$nimilisa $varastorivi[nimitys]: $row[sarjanumero]&nbsp;</td>";

				if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
					echo "<td class='$vari' nowrap align='right'>";

					$toimitiedot	= explode(',', $row["toimitiedot"]);
					$toimitiedot	= explode('##',$toimitiedot[0]);
					$tyyppi_tieto	= $toimitiedot[0];
					$liitostunnus	= $toimitiedot[1];

					if ($row["sarjayhtio"] == $kukarow["yhtio"] or ($row["sarjayhtio"] != $kukarow["yhtio"] and $tyyppi_tieto == $row["sarjayhtio"])) {

						if ($row["sarjayhtio"] != $kukarow["yhtio"] and $tyyppi_tieto == $row["sarjayhtio"]) {
							echo "<input type='hidden' name='tilpaikka[$yht_i]' value = '@@@$liitostunnus####'>";
						}

						echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'>";
						echo "<input type='submit' value = '".t("Lisää")."'>";
					}

					$yht_i++;

					echo "</td>";
				}
			}
			elseif ($row['ei_saldoa'] != '' and $kukarow["extranet"] == "") {
				echo "<td class='green'>".t("Saldoton")."</td>";
			}
			elseif ($row['ei_saldoa'] != '' and $kukarow["extranet"] != "") {
				echo "<td class='green'>".t("On")."</td>";
			}
			else {

				if ($kukarow["extranet"] != "") {

					// katotaan paljonko on myytävissä
					$saldo = 0;

					foreach($konsyhtiot as $yhtio) {
						$saldo += saldo_myytavissa($row["tuoteno"], "", 0, $yhtio);
					}

					if ($saldo > 0) {
						echo "<td class='green'>".t("On")."</td>";
					}
					else {
						echo "<td class='red'>".t("Ei")."</td>";
					}
				}
				else {
					// käydään katotaan tuotteen varastot
					$query = "	SELECT varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.tyyppi, varastopaikat.yhtio
								FROM tuotepaikat
								JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio and
								concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
								WHERE tuotepaikat.$yhtiot and tuotepaikat.tuoteno='$row[tuoteno]'
								group by 1,2
								order by nimitys";
					$varresult = mysql_query($query) or die($query);

					echo "<td><table width='100%'>";

					while ($varastorivi = mysql_fetch_array($varresult)) {
						// katotaan sen varaston saldo
						$saldo = saldo_myytavissa($row["tuoteno"], "KAIKKI", $varastorivi["tunnus"], $varastorivi["yhtio"]);

						if ($varastorivi["tyyppi"] != "") {
							$vartyyppi = "($varastorivi[tyyppi])";
						}
						else {
							$vartyyppi = "";
						}

						if ($saldo != 0) {
							echo "<tr><td class='$vari' nowrap>$varastorivi[nimitys] $vartyyppi</td><td class='$vari' align='right' nowrap>$saldo $row[yksikko]</td></tr>";
						}
					}

					echo "</table></td>";
				}
			}

			if ($row["sarjatunnus"] == 0 and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {

				echo "<td class='$vari' nowrap>";
				echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
				echo "<input type='text' size='7' name='tilkpl[$yht_i]'>";
				echo "<input type='submit' value = '".t("Lisää")."'>";
				$yht_i++;

				echo "</td>";
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
