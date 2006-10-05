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

	echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";

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
						$var		     = "";
						$alv		     = "";
						$paikka		     = "";
						$varasto 	     = "";
						$rivitunnus		 = "";
						$korvaavakielto	 = "";
						$varataan_saldoa = "";

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
		$tee = "";
	}


	$kentat="tuote.tuoteno,toim_tuoteno,nimitys,osasto,try,tuotemerkki";
	$nimet="Tuotenumero,Toim tuoteno,Nimitys,Osasto,Tuoteryhmä,Tuotemerkki";

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
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' $avainlisa
				ORDER BY jarjestys";
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
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY' $avainlisa
				ORDER BY jarjestys";
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

	if($lisa == "") {
		exit;
	}

	if ($kukarow["extranet"] == "") {
		$query = "	SELECT  if(korvaavat.id>0,(select tuoteno from korvaavat korva2 where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),tuote.tuoteno) sorttauskentta,
					tuote.tuoteno, tuote.nimitys,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					tuote.osasto, tuote.try, tuote.myyntihinta, tuote.nettohinta, tuote.aleryhma, tuote.status, tuote.ei_saldoa, tuote.yksikko
					FROM tuote
					LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					LEFT join korvaavat ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
					WHERE tuote.yhtio='$kukarow[yhtio]'
					$lisa
					$poislisa
					GROUP BY 1,2,3,5,6,7,8,9,10,11
					ORDER by $jarjestys
					LIMIT 500";
	}
	else {
		$query = "	SELECT  if(korvaavat.id > 0,(select tuoteno from korvaavat korva2 where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),tuote.tuoteno) sorttauskentta,
					tuote.tuoteno, tuote.nimitys,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					tuote.myyntihinta, tuote.aleryhma, tuote.ei_saldoa
					FROM tuote
					LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					LEFT join korvaavat ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
					WHERE tuote.yhtio='$kukarow[yhtio]'
					and tuote.myyntihinta > 0
					$lisa
					$poislisa
					GROUP BY 1,2,3,5,6,7
					ORDER by $jarjestys
					LIMIT 500";
	}
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table>";
		echo "<tr>";

		for ($i=1; $i < mysql_num_fields($result)-2; $i++) {
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
		echo "<input type='hidden' name='tee' value = 'TI'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";

			if ($row["status"] == "P") {
				$vari = "tumma";
			}
			else {
				$vari = "";
			}

			$lisakala = "";
			if ($row["sorttauskentta"] == $edtuoteno) {
				$lisakala = "--> ";
			}

			for ($i=1; $i < mysql_num_fields($result)-2; $i++) {
				if ($i == 1 and $kukarow["extranet"] == "") {
					echo "<td class='$vari'><a href='../tuote.php?tuoteno=$row[$i]&tee=Z'>$lisakala $row[$i]</a></td>";
				}
				elseif ($i == 1 and $kukarow["extranet"] != "") {
					echo "<td class='$vari'>$lisakala $row[$i]</td>";
				}
				else {
					echo "<td class='$vari'>$row[$i]</td>";
				}
			}

			$edtuoteno = $row["sorttauskentta"];

			if ($row['ei_saldoa'] != '' and $kukarow["extranet"] == "") {
				echo "<td class='green'>".t("Saldoton")."</td>";
			}
			elseif ($row['ei_saldoa'] != '' and $kukarow["extranet"] != "") {
				echo "<td class='green'>".t("On")."</td>";
			}
			else {

				if ($kukarow["extranet"] != "") {
					$saldo = saldo_myytavissa($row["tuoteno"]);

					if ($saldo > 0) {
						echo "<td class='green'>".t("On")."</td>";
					}
					else {
						echo "<td class='red'>".t("Ei")."</td>";
					}
				}
				else {
					// käydään katotaan tuotteen varastot
					$query = "	SELECT varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.tyyppi
								FROM tuotepaikat
								JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio and
								concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
								WHERE tuotepaikat.yhtio='$kukarow[yhtio]' and tuotepaikat.tuoteno='$row[tuoteno]'
								group by 1,2
								order by nimitys";
					$varresult = mysql_query($query) or die($query);

					echo "<td><table width='100%'>";

					while ($varastorivi = mysql_fetch_array($varresult)) {
						// katotaan sen varaston saldo
						$saldo = saldo_myytavissa($row["tuoteno"], "KAIKKI", $varastorivi["tunnus"]);

						if ($varastorivi["tyyppi"] != "") {
							$vartyyppi = "($varastorivi[tyyppi])";
						}
						else {
							$vartyyppi = "";
						}

						if ($saldo != 0) {
							echo "<tr><td nowrap>$varastorivi[nimitys] $vartyyppi</td><td align='right' nowrap>$saldo $row[yksikko]</td></tr>";
						}
					}

					echo "</table></td>";
				}
			}

			if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
				if ($tuoteno == '' or $row["tuoteno"] == $tuoteno) {
					echo "<td class='$vari' nowrap>";

					echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
					echo "<input type='text' size='7' name='tilkpl[$yht_i]'>";
					echo "<input type='submit' value = '".t("Lisää")."'>";
					$yht_i++;

					echo "</td>";
				}
			}

			echo "</tr>";

		}

		echo "</form>";
		echo "</table>";
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
