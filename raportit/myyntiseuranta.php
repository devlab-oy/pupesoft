<?php

// käytetään slavea
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Myyntiseuranta")."</font><hr>";

// tutkaillaan saadut muuttujat
$tuoteosasto     = trim($tuoteosasto);
$tuoteryhma      = trim($tuoteryhma);
$asiakasosasto   = trim($asiakasosasto);
$asiakasryhma    = trim($asiakasryhma);
$asiakas         = trim($asiakas);
$toimittaja    	 = trim($toimittaja);

if ($tuoteosasto   == "") $tuoteosasto   = trim($tuoteosasto2);
if ($tuoteryhma    == "") $tuoteryhma    = trim($tuoteryhma2);
if ($asiakasosasto == "") $asiakasosasto = trim($asiakasosasto2);
if ($asiakasryhma  == "") $asiakasryhma  = trim($asiakasryhma2);

// hehe, näin on helpompi verrata päivämääriä
$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
$result = mysql_query($query) or pupe_error($query);
$row    = mysql_fetch_array($result);

if ($row["ero"] > 365) {
	echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
	$tee = "";
}

//haetaan tilauksista
if ($ajotapa == 'tilaus') {
	$tila		= 'L';
	$ouusio		= 'otunnus';
	$index		= 'yhtio_otunnus';
}
//haetaan laskuista
else {
	$tila		= 'U';
	$ouusio		= 'uusiotunnus';
	$index		= 'uusiotunnus_index';
}

// jos on joku toimittajajuttu valittuna, niin ei saa valita ku yhen yrityksen
if ($toimittaja != "" or $mukaan == "toimittaja") {
	if (count($yhtiot) != 1) {
		echo "<font class='error'>".t("Toimittajahauissa voi valita vain yhden yrityksen")."!</font><br>";
		$tee = "";
	}
//	$toimittaja = ""; // ei toimikkaan mysql 4.0ssa niin nollataan ettei kukaan saa tehtyä
}

// jos ei ole mitään yritystä valittuna ei tehdä mitään
if (count($yhtiot) == 0) {
	$tee = "";
}
else {
	$yhtio  = "";
	foreach ($yhtiot as $apukala) {
		$yhtio .= "'$apukala',";
	}
	$yhtio = substr($yhtio,0,-1);
}

// jos ei olla valittu mitään ei tehdä mitään
if ($tuoteryhma == "" and $tuoteosasto == "" and $asiakasosasto == "" and $asiakasryhma == "" and count($ruksit) == 0) {
	$tee = "";
}

// jos joku päiväkenttä on tyhjää ei tehdä mitään
if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
	$tee = "";
}

if ($tee == 'go') {

	// no hacking, please.
	$lisa   = "";
	$query  = "";
	$group  = "";
	$order  = "";
	$select = "";
	$gluku  = 0;

	$apu = array();

	foreach ($jarjestys as $arvo) {
		if (trim($arvo) != "") $apu[] = $arvo;
	}

	if (count($apu) == 0) {
		ksort($jarjestys);
	}
	else {
		asort($jarjestys);
	}

	$apu = array();

	foreach ($jarjestys as $i => $arvo) {
		if ($ruksit[$i] != "") $apu[] = $ruksit[$i];
	}

	foreach ($apu as $mukaan) {

		if ($mukaan == "ytunnus") {
			if ($group!="") $group .= ",asiakas.tunnus";
			else $group  .= "asiakas.tunnus";
			$select .= "concat_ws(' ', asiakas.ytunnus, asiakas.toim_ovttunnus, asiakas.toim_nimi) ytunnus, ";
			$order  .= "asiakas.ytunnus,";
			$gluku++;
		}

		if ($mukaan == "piiri") {
			if ($group!="") $group .= ",asiakas.piiri";
			else $group  .= "asiakas.piiri";
			$select .= "asiakas.piiri piiri, ";
			$order  .= "asiakas.piiri,";
			$gluku++;
		}

		if ($mukaan == "maa") {
			if ($group!="") $group .= ",asiakas.maa";
			else $group  .= "asiakas.maa";
			$select .= "asiakas.maa maa, ";
			$order  .= "asiakas.maa,";
			$gluku++;
		}

		if ($mukaan == "tuote") {
			if ($group!="") $group .= ",tuote.tuoteno";
			else $group  .= "tuote.tuoteno";
			$select .= "tuote.tuoteno tuoteno, ";
			$order  .= "tuote.tuoteno,";
			$gluku++;
		}

		if ($mukaan == "tuotemyyja") {
			if ($group!="") $group .= ",tuote.myyjanro";
			else $group  .= "tuote.myyjanro";
			$select .= "tuote.myyjanro tuotemyyja, ";
			$order  .= "tuote.myyjanro,";
			$gluku++;
		}

		if ($mukaan == "asiakasmyyja") {
			if ($group!="") $group .= ",asiakas.myyjanro";
			else $group  .= "asiakas.myyjanro";
			$select .= "asiakas.myyjanro asiakasmyyja, ";
			$order  .= "asiakas.myyjanro,";
			$gluku++;
		}

		if ($mukaan == "tuoteostaja") {
			if ($group!="") $group .= ",tuote.ostajanro";
			else $group  .= "tuote.ostajanro";
			$select .= "tuote.ostajanro tuoteostaja, ";
			$order  .= "tuote.ostajanro,";
			$gluku++;
		}

		if ($mukaan == "merkki") {
			if ($group!="") $group .= ",tuote.tuotemerkki";
			else $group  .= "tuote.tuotemerkki";
			$select .= "tuote.tuotemerkki merkki, ";
			$order  .= "tuote.tuotemerkki,";
			$gluku++;
		}

		if ($mukaan == "toimittaja") {
			if ($group!="") $group .= ",toimittaja";
			else $group  .= "toimittaja";
			$select .= "(select group_concat(distinct toimittaja) from tuotteen_toimittajat use index (yhtio_tuoteno) where tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno) toimittaja, ";
			$order  .= "toimittaja,";
			$gluku++;
		}

		if ($mukaan == "tilaustyyppi") {
			if ($group!="") $group .= ",lasku.clearing";
			else $group  .= "lasku.clearing";
			$select .= "lasku.clearing tilaustyyppi, ";
			$order  .= "lasku.clearing,";
			$gluku++;
		}

	}

	if ($asiakasosasto != "") {
		if ($group!="") $group .= ",asiakas.osasto";
		else $group .= "asiakas.osasto";
		$select .= "asiakas.osasto asos, ";
		$order  .= "asiakas.osasto+0,";
		$gluku++;
	}

	if ($asiakasryhma != "") {
		if ($group!="") $group .= ",asiakas.ryhma";
		else $group .= "asiakas.ryhma";
		$select .= "asiakas.ryhma asry, ";
		$order  .= "asiakas.ryhma+0,";
		$gluku++;
	}

	if ($tuoteosasto  != "") {
		if ($group!="") $group .= ",tuote.osasto";
		else $group .= "tuote.osasto";
		$select .= "tuote.osasto tuos, ";
		$order  .= "tuote.osasto+0,";
		$gluku++;
	}

	if ($tuoteryhma != "") {
		if ($group!="") $group .= ",tuote.try";
		else $group .= "tuote.try";
		$select .= "tuote.try tury, ";
		$order  .= "tuote.try+0,";
		$gluku++;
	}

	if ($asiakasryhma  == "kaikki") {
		$asiakasryhma = "";
		$asiakasryhmasel = "selected";
	}

	if ($asiakasosasto == "kaikki") {
		$asiakasosasto = "";
		$asiakasosastosel = "selected";
	}

	if ($tuoteryhma    == "kaikki") {
		$tuoteryhma = "";
		$tuoteryhmasel = "selected";
	}

	if ($tuoteosasto   == "kaikki") {
		$tuoteosasto = "";
		$tuoteosastosel = "selected";
	}

	if ($asiakasryhma  != "") $lisa .= " and asiakas.ryhma     = '$asiakasryhma' ";

	if ($asiakasosasto != "") $lisa .= " and asiakas.osasto    = '$asiakasosasto' ";

	if ($tuoteryhma != "" and strpos($tuoteryhma, "*") !== FALSE) {
		$lisa .= " and tuote.try like '".substr($tuoteryhma,0,-1)."%' ";
	}
	elseif ($tuoteryhma != "") {
		$lisa .= " and tuote.try = '$tuoteryhma' ";
	}

	if ($tuoteosasto   != "") $lisa .= " and tuote.osasto      = '$tuoteosasto' ";

	if ($toimittaja != "") {
		$query = "select tuoteno from tuotteen_toimittajat where yhtio in ($yhtio) and toimittaja='$toimittaja'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$lisa .= " and tilausrivi.tuoteno in (";
			while ($toimirow = mysql_fetch_array($result)) {
				$lisa .= "'$toimirow[tuoteno]',";
			}
			$lisa = substr($lisa,0,-1).")";
		}
		else {
			echo "<font class='error'>Toimittajan $toimittaja tuotteita ei löytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
			$toimittaja = "";
		}
	}

	if ($asiakas != "") {
		$query = "select group_concat(tunnus) from asiakas where yhtio in ($yhtio) and ytunnus = '$asiakas'";
		$result = mysql_query($query) or pupe_error($query);

		$asiakasrow = mysql_fetch_array($result);
		if (trim($asiakasrow[0]) != "") {
			$lisa .= " and lasku.liitostunnus in ($asiakasrow[0]) ";
		}
		else {
			echo "<font class='error'>Asiakasta $asiakas ei löytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
			$asiakas = "";
		}
	}

	$vvaa = $vva - '1';
	$vvll = $vvl - '1';

	$query = "SELECT $select
			sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl,0)) myykplnyt,
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) myykpled,
			sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) myyntinyt,
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)) myyntied,
			round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) /
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)),2) myyntiind,
			sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) katenyt,
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)) kateed,
			round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) /
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)),2) kateind,
			sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',
			tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100)
			,0)) nettokatenyt,
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',
			tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100)
			,0)) nettokateed,
			round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',
			tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100)
			,0)) /
			sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',
			tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100)
			,0)),2) nettokateind
			FROM lasku use index (yhtio_tila_tapvm)
			JOIN tilausrivi use index ($index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.$ouusio=lasku.tunnus and tilausrivi.tyyppi='L'
			LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
			LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
			LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
			WHERE lasku.yhtio in ($yhtio) and
			lasku.tila='$tila' and
			lasku.alatila='X' and
			((lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') or
			(lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl'))
			$lisa
			group by $group
			order by $order myyntinyt";

	// ja sitten ajetaan itte query
	if ($query != "") {

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 2000) {
			echo "<font class='message'>".t("Hakutulos oli liian suuri. Tee tarkempi rajaus")."!<br></font>";
			$query = "";
		}

	}

	if ($query != "") {

		echo "<table>";
		echo "<tr><td class='back'>".t("Kausi nyt")."</td><td class='back'>$vva-$kka-$ppa - $vvl-$kkl-$ppl</td></tr>";
		echo "<tr><td class='back'>".t("Kausi ed")."</td> <td class='back'>$vvaa-$kka-$ppa - $vvll-$kkl-$ppl</td></tr>";
		echo "</table><br>";

		echo "<table><tr>";

		// echotaan kenttien nimet
		for ($i=0; $i < mysql_num_fields($result); $i++) echo "<th>".t(mysql_field_name($result,$i))."</th>";

		echo "</tr>\n";

		$edluku = "x"; // katellaan muuttuuko joku arvo
		$myyntinyt = $myyntied = $katenyt = $kateed = $nettokatenyt = $nettokateed = $myyntikplnyt = $myyntikpled = 0;
		$totmyyntinyt = $totmyyntied = $totkatenyt = $totkateed = $totnettokatenyt = $totnettokateed = $totmyyntikplnyt = $totmyyntikpled = 0;

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			// echotaan kenttien sisältö
			for ($i=0; $i < mysql_num_fields($result); $i++) {

				// jos kyseessa on asiakasosasto, haetaan sen nimi
				if (mysql_field_name($result, $i) == "asos") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio in ($yhtio) and laji='ASIAKASOSASTO' and selite='$row[$i]' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['selitetark'];
					}
				}

				// jos kyseessa on asiakasryhma, haetaan sen nimi
				if (mysql_field_name($result, $i) == "asry") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio in ($yhtio) and laji='ASIAKASRYHMA' and selite='$row[$i]' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['selitetark'];
					}
				}

				// jos kyseessa on tuoteosasto, haetaan sen nimi
				if (mysql_field_name($result, $i) == "tuos") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio in ($yhtio) and laji='OSASTO' and selite='$row[$i]' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['selitetark'];
					}
				}

				// jos kyseessa on tuoteosasto, haetaan sen nimi
				if (mysql_field_name($result, $i) == "tury") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio in ($yhtio) and laji='TRY' and selite='$row[$i]' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['selitetark'];
					}
				}

				// jos kyseessa on ytunnus, haetaan sen nimi
				if (mysql_field_name($result, $i) == "ytunnus") {
					$query = "	SELECT nimi
								FROM asiakas
								WHERE yhtio in ($yhtio) and ytunnus='$row[$i]' and ytunnus!='' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['nimi'];
					}
				}

				// jos kyseessa on myyjä, haetaan sen nimi
				if (mysql_field_name($result, $i) == "tuotemyyja" or
					mysql_field_name($result, $i) == "asiakasmyyja") {
					$query = "	SELECT nimi
								FROM kuka
								WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['nimi'];
					}
				}

				// jos kyseessa on ostaja, haetaan sen nimi
				if (mysql_field_name($result, $i) == "tuoteostaja") {
					$query = "	SELECT nimi
								FROM kuka
								WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow= mysql_fetch_array($osre);
						$row[$i] = $row[$i] ." ". $osrow['nimi'];
					}
				}

				// jos kyseessa on toimittaja, haetaan nimi/nimet
				if (mysql_field_name($result, $i) == "toimittaja") {
					// fixataan mysql 'in' muotoon
					$toimittajat = "'".str_replace(",","','",$row[$i])."'";
					$query = "	SELECT group_concat(concat_ws('/',ytunnus,nimi)) nimi
								FROM toimi
								WHERE yhtio in ($yhtio) and ytunnus in ($toimittajat)";
					$osre = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($osre) == 1) {
						$osrow = mysql_fetch_array($osre);
						$row[$i] = $osrow['nimi'];
					}
				}

				// jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
				if ($gluku > 1) {

					if ($edluku != $row[0] and $edluku != 'x' and $mukaan != 'tuote') {

						$myyntiind = $kateind = $nettokateind = 0;
						if ($myyntied    <> 0) $myyntiind    = round($myyntinyt/$myyntied,2);
						if ($kateed      <> 0) $kateind      = round($katenyt/$kateed,2);
						if ($nettokateed <> 0) $nettokateind = round($nettokatenyt/$nettokateed,2);

						$apu = mysql_num_fields($result)-11;
						echo "<th colspan='$apu'>$edluku ".t("yhteensä")."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikplnyt))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikpled))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntinyt))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntied))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$katenyt))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$kateed))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokatenyt))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateed))."</th>";
						echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</th></tr>\n";
						echo "<tr>";

						$myyntikplnyt = "0";
						$myyntikpled  = "0";
						$myyntinyt    = "0";
						$myyntied     = "0";
						$katenyt      = "0";
						$kateed       = "0";
						$nettokatenyt = "0";
						$nettokateed  = "0";
					}
					$edluku = $row[0];
				}

				// hoidetaan pisteet piluiksi!!
				if (mysql_field_type($result,$i) == 'real') {
					$row[$i] = str_replace(".", ",", sprintf("%.02f",$row[$i]));
					echo "<td align='right'>$row[$i]</td>";
				}
				else {
					echo "<td>$row[$i]</td>";
				}
			}

			echo "</tr>\n";

			$myyntikplnyt    += $row['myykplnyt'];
			$myyntikpled     += $row['myykpled'];
			$myyntinyt       += $row['myyntinyt'];
			$myyntied        += $row['myyntied'];
			$katenyt         += $row['katenyt'];
			$kateed          += $row['kateed'];
			$nettokatenyt    += $row['nettokatenyt'];
			$nettokateed     += $row['nettokateed'];
			$totmyyntikplnyt += $row['myykplnyt'];
			$totmyyntikpled  += $row['myykpled'];
			$totmyyntinyt    += $row['myyntinyt'];
			$totmyyntied     += $row['myyntied'];
			$totkatenyt      += $row['katenyt'];
			$totkateed       += $row['kateed'];
			$totnettokatenyt += $row['nettokatenyt'];
			$totnettokateed  += $row['nettokateed'];

		}

		$apu = mysql_num_fields($result)-11;

		// jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
		if ($gluku > 1 and $mukaan != 'tuote') {

			$myyntiind = $kateind = $nettokateind = 0;
			if ($myyntied    <> 0) $myyntiind    = round($myyntinyt/$myyntied,2);
			if ($kateed      <> 0) $kateind      = round($katenyt/$kateed,2);
			if ($nettokateed <> 0) $nettokateind = round($nettokatenyt/$nettokateed,2);

  		  	echo "<tr><th colspan='$apu'>$edluku ".t("yhteensä")."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikplnyt))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikpled))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntinyt))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntied))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$katenyt))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$kateed))."</th>";
  		  	echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</th>";
			echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokatenyt))."</th>";
			echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateed))."</th>";
			echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</th></tr>\n";
		}

		$myyntiind = $kateind = $nettokateind = 0;
		if ($totmyyntied    <> 0) $myyntiind    = round($totmyyntinyt/$totmyyntied,2);
		if ($totkateed      <> 0) $kateind      = round($totkatenyt/$totkateed,2);
		if ($totnettokateed <> 0) $nettokateind = round($totnettokatenyt/$totnettokateed,2);

		echo "<tr><th colspan='$apu'>".t("Kaikki yhteensä")."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntikplnyt))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntikpled))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntinyt))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntied))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totkatenyt))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totkateed))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totnettokatenyt))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$totnettokateed))."</th>";
		echo "<th align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</th></tr>\n";

		echo "</table>";
	}
}


if ($lopetus == "") {
	//Käyttöliittymä
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");
	if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

	echo "<br>\n\n\n";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='go'>";

	// tässä on tämä "perusnäkymä" mikä tulisi olla kaikissa myynnin raportoinneissa..

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Valitse ajotapa:")."</th>";
	echo "<td><input type='radio' name='ajotapa' value='lasku' checked>".t("Laskuista")."</td>";
	echo "<td><input type='radio' name='ajotapa' value='tilaus'>".t("Tilauksista")."</td>";
	echo "<td class='back'>".t("(HUOM! Raportin tulos todennäköisesti eroaa ajotavasta riippuen)")."</td>";

	echo "</tr>";
	echo "</table><br>";

	$query = "	SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = mysql_query($query) or pupe_error($query);

	// voidaan valita listaukseen useita konserniyhtiöitä, jos käyttäjällä on "PÄIVITYS" oikeus tähän raporttiin
	if (mysql_num_rows($result) > 0 and $oikeurow['paivitys'] != "") {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Valitse yhtiö")."</th>";

		if (!isset($yhtiot)) $yhtiot = array();

		while ($row = mysql_fetch_array($result)) {
			$sel = "";

			if ($kukarow["yhtio"] == $row["yhtio"] and count($yhtiot) == 0) $sel = "CHECKED";
			if (in_array($row["yhtio"], $yhtiot)) $sel = "CHECKED";

			echo "<td><input type='checkbox' name='yhtiot[]' onchange='submit()' value='$row[yhtio]' $sel>$row[nimi]</td>";
		}

		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<input type='hidden' name='yhtiot[]' value='$kukarow[yhtio]'>";
	}

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Syötä asiakasosasto")."</th>";
	echo "<td><input type='text' name='asiakasosasto' size='10'></td>";

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio in ($yhtio) and laji='ASIAKASOSASTO'
				ORDER BY selite+0";
	$result = mysql_query($query) or pupe_error($query);

	echo "<td><select name='asiakasosasto2'>";
	echo "<option value=''>".t("Asiakasosasto")."</option>";
	echo "<option value='kaikki' $asiakasosastosel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($asiakasosasto == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td>";
	echo "<th>".t("ja/tai asiakasryhmä")."</th>";
	echo "<td><input type='text' name='asiakasryhma' size='10'></td>";

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio in ($yhtio) and laji='ASIAKASRYHMA'
				ORDER BY selite+0";
	$result = mysql_query($query) or pupe_error($query);

	echo "<td><select name='asiakasryhma2'>";
	echo "<option value=''>".t("Asiakasryhmä")."</option>";
	echo "<option value='kaikki' $asiakasryhmasel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($asiakasryhma == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>\n";

	echo "<tr>";
	echo "<th>".t("Syötä tuoteosasto")."</th>";
	echo "<td><input type='text' name='tuoteosasto' size='10'></td>";

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio in ($yhtio) and laji='OSASTO'
				ORDER BY selite+0";
	$result = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuoteosasto2'>";
	echo "<option value=''>".t("Tuoteosasto")."</option>";
	echo "<option value='kaikki' $tuoteosastosel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($tuoteosasto == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td>";
	echo "<th>".t("ja/tai tuoteryhmä")."</th>";
	echo "<td><input type='text' name='tuoteryhma' size='10'></td>";

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio in ($yhtio) and laji='TRY'
				ORDER BY selite+0";
	$result = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuoteryhma2'>";
	echo "<option value=''>".t("Tuoteryhmä")."</option>";
	echo "<option value='kaikki' $tuoteryhmasel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($tuoteryhma == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>\n";
	echo "</table><br>";

	// tähän loppuu "perusnäkymä"...

	echo "<table>
	<tr>
	<th>".t("Vain asiakas ytunnus")."</th>
	<td><input type='text' name='asiakas' value='$asiakas'></td>
	</tr>";

	echo "<tr>
	<th>".t("Vain toimittaja ytunnus")."</th>
	<td><input type='text' name='toimittaja' value='$toimittaja'></td>
	</tr>";

	echo "</table><br>";



	// lisärajaukset näkymä..
	echo "<table>
		<tr>
		<th>".t("Lisärajaus")."</th>
		<th>".t("Prio")."</th>
		<th> x</th>
		</tr>
		<tr>
		<th>".t("Listaa asiakkaittain")."</th>
		<td><input type='text' name='jarjestys[1]' size='2'></td>
		<td><input type='checkbox' name='ruksit[1]' value='ytunnus'></td><td class='back'>".t("(Rajaa ylhäältä mahdollisimman pieni ryhmä, muuten lista on todella pitkä)")."</td>
		</tr>
		<tr>
		<th>".t("Listaa tuotteittain")."</th>
		<td><input type='text' name='jarjestys[2]' size='2'></td>
		<td><input type='checkbox' name='ruksit[2]' value='tuote'></td><td class='back'>".t("(Rajaa ylhäältä mahdollisimman pieni ryhmä, muuten lista on todella pitkä)")."</td>
		</tr>
		<tr>
		<th>".t("Listaa piireittäin")."</th>
		<td><input type='text' name='jarjestys[3]' size='2'></td>
		<td><input type='checkbox' name='ruksit[3]' value='piiri'></td>
		</tr>
		<tr>
		<th>".t("Listaa tuotemyyjittäin")."</th>
		<td><input type='text' name='jarjestys[4]' size='2'></td>
		<td><input type='checkbox' name='ruksit[4]' value='tuotemyyja'></td>
		</tr>
		<tr>
		<th>".t("Listaa asiakasmyyjittäin")."</th>
		<td><input type='text' name='jarjestys[5]' size='2'></td>
		<td><input type='checkbox' name='ruksit[5]' value='asiakasmyyja'></td>
		</tr>
		<tr>
		<th>".t("Listaa tuoteostajittain")."</th>
		<td><input type='text' name='jarjestys[6]' size='2'></td>
		<td><input type='checkbox' name='ruksit[6]' value='tuoteostaja'></td>
		</tr>
		<tr>
		<th>".t("Listaa maittain")."</th>
		<td><input type='text' name='jarjestys[7]' size='2'></td>
		<td><input type='checkbox' name='ruksit[7]' value='maa'></td>
		</tr>
		<tr>
		<th>".t("Listaa merkeittäin")."</th>
		<td><input type='text' name='jarjestys[8]' size='2'></td>
		<td><input type='checkbox' name='ruksit[8]' value='merkki'></td>
		</tr>
		<tr>
		<th>".t("Listaa toimittajittain")."</th>
		<td><input type='text' name='jarjestys[9]' size='2'></td>
		<td><input type='checkbox' name='ruksit[9]' value='toimittaja'></td>
		</tr>
		<th>".t("Listaa tilaustyypeittäin")."</th>
		<td><input type='text' name='jarjestys[10]' size='2'></td>
		<td><input type='checkbox' name='ruksit[10]' value='tilaustyyppi'></td><td class='back'>".t("(Toimii vain jos ajat raporttia tilauksista)")."</td>
		</tr>
		</table><br>";


	// päivämäärärajaus
	echo "<table>";
	echo "<tr>
		<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr>\n
		<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td>
		</tr>\n";
	echo "</table>";

	echo "<br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";
}
else {
	// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
	$lopetus = str_replace('!!!!','?', $lopetus);
	$lopetus = str_replace('!!','&',  $lopetus);
	echo "<br><br>";
	echo "<a href='$lopetus'>".t("Palaa edelliseen näkymään")."</a>";
}

require ("../inc/footer.inc")

?>