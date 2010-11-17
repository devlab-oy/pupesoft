<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Tulosta inventointilista")."</font><hr>";


	echo "<form name='inve' action='$PHP_SELF' method='post' enctype='multipart/form-data' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='TULOSTA'>";


	// Monivalintalaatikot (osasto, try tuotemerkki...)
	// M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
	$monivalintalaatikot = array("OSASTO", "TRY");

	echo "<br><table>";
	echo "<tr><th>".t("Anna osasto")." ".t("ja/tai")." ".t("tuoteryhm‰").":</th><td nowrap>";

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "<tr><th>".t("Ja listaa vain myydyimm‰t:")."</th>
	<td><input type='text' size='6' name='top'> ".t("tuotetta").".
	</td></tr>";

	echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";

	echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>
	<td><input type='text' size='6' maxlength='5' name='ahyllyalue'>
	<input type='text' size='6' maxlength='5' name='ahyllynro'>
	<input type='text' size='6' maxlength='5' name='ahyllyvali'>
	<input type='text' size='6' maxlength='5' name='ahyllytaso'>
	</td></tr>";

	echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>
	<td><input type='text' size='6' maxlength='5' name='lhyllyalue'>
	<input type='text' size='6' maxlength='5' name='lhyllynro'>
	<input type='text' size='6' maxlength='5' name='lhyllyvali'>
	<input type='text' size='6' maxlength='5' name='lhyllytaso'>
	</td></tr>";

	echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";

	echo "<tr><th>".t("Anna toimittajanumero(ytunnus):")."</th>
	<td><input type='text' size='25' name='toimittaja'>
	</td></tr>";

	echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";

	echo "<tr><th>".t("Valitse tuotemerkki:")."</th>";

	$query = "	SELECT distinct tuotemerkki
				FROM tuote use index (yhtio_tuotemerkki)
				WHERE yhtio='$kukarow[yhtio]'
				$poislisa
				and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuotemerkki'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";
	while($srow = mysql_fetch_array ($sresult)){
		echo "<option value='$srow[0]'>$srow[0]</option>";
	}
	echo "</td></tr>";

	echo "<tr><td class='back'><br></td></tr>";

    echo "<tr><td class='back' colspan='2'>".t("tai inventoi raportin avulla")."...</th></tr>
		<tr><th>".t("Valitse raportti")."</th><td><select name='raportti'>
		<option value=''>".t("Valitse")."</option>
		<option value='vaarat'>".t("V‰‰r‰t saldot")."</option>
		<option value='loppuneet'>".t("Loppuneet tuotteet")."</option>
		<option value='negatiiviset'>".t("Kaikki miinus-saldolliset")."</option>
		</select>
		</td></tr>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");


	echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'>
		<input type='text' name='kka' value='$kka' size='3'>
		<input type='text' name='vva' value='$vva' size='5'></td>
		</tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'>
		<input type='text' name='kkl' value='$kkl' size='3'>
		<input type='text' name='vvl' value='$vvl' size='5'>";

	echo "<tr><td class='back'><br></td></tr>";
	echo "<tr><td class='back' colspan='2'>".t("Valitse ehdoista")."...</th></tr>";


	echo "<tr><th>".t("Listaa tuotteet:")."</th>";

	$sel1 = "";
	$sel2 = "";

	if ($arvomatikka == 'S') {
		$sel1 = "SELECTED";
	}
	elseif ($arvomatikka == 'N') {
		$sel2 = "SELECTED";
	}

	echo "<td><select name='arvomatikka'>";
	echo "<option value=''>".t("Kaikki")."</option>";
	echo "<option value='S' $sel1>".t("Saldolliset")."</option>";
	echo "<option value='N' $sel2>".t("Ei nolla saldollisia")."</option></select>";
	echo "</td></tr>";

	$sel1 = "";
	$sel2 = "";

	if ($naytasaldo == 'H') {
		$sel1 = "SELECTED";
	}
	elseif ($naytasaldo == 'S') {
		$sel2 = "SELECTED";
	}

	echo "<tr><th>".t("Tulosta hyllyss‰ oleva m‰‰r‰:")."</th>
	<td><select name='naytasaldo'>";
	echo "<option value=''>".t("Ei n‰ytet‰ m‰‰r‰‰")."</option>";
	echo "<option value='H' $sel1>".t("Hyllyss‰ oleva m‰‰r‰")."</option>";
	echo "<option value='S' $sel2>".t("Saldo")."</option></select>

	</td>
	</tr>";


	echo "<tr><th>".t("Listaa myˆs tuotteet jotka ovat inventoitu l‰hiaikoina:")."</th>
	<td><input type='checkbox' name='naytainvtuot'></td>
	</tr>";

	echo "<tr><th>".t("Listaa vain tuotteet joita ei ole inventoitu p‰iv‰m‰‰r‰n‰ tai sen j‰lkeen:")."</th>
		<td><input type='text' name='ippa' value='$ippa' size='3'>
		<input type='text' name='ikka' value='$ikka' size='3'>
		<input type='text' name='ivva' value='$ivva' size='5'></td>
	</tr>";

	echo "<tr><th>".t("Tuotteen status:")."</th>";

	$sel = "";

	if ($kertakassa == 'A') {
		$sel = "SELECTED";
	}

	echo "<td><select name='kertakassa'>";
	echo "<option value=''>".t("Kaikki tuotteet")."</option>";
	echo "<option value='A' $sel>".t("Ei listata poistettuja tuotteita")."</option>";

	echo "</td></tr>";

	echo "<tr><th>".t("J‰rjest‰ lista:")."</th>";

	$sel1 = "";
	$sel2 = "";
	$sel3 = "";

	if ($jarjestys == 'tuoteno') {
		$sel2 = "SELECTED";
	}
	elseif ($jarjestys == 'osastotrytuoteno') {
		$sel3 = "SELECTED";
	}
	else {
		$sel1 = "SELECTED";
	}

	echo "<td><select name='jarjestys'>";
	echo "<option value=''  $sel1>".t("Osoitej‰rjestykseen")."</option>";
	echo "<option value='tuoteno' $sel2>".t("Tuotenumeroj‰rjestykseen")."</option>";
	echo "<option value='osastotrytuoteno' $sel3>".t("Osasto/Tuoteryhm‰/Tuotenumeroj‰rjestykseen")."</option>";

	echo "</td></tr>";

	echo "<tr><th>",t("J‰rjest‰ sorttauskentt‰"),":</th>";

	$selsorttaus = array();

	if ($sorttauskentan_jarjestys1 == 'hyllytaso') {
		$selsorttaus['hyllytaso'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys1 == 'hyllynro') {
		$selsorttaus['hyllynro'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys1 == 'hyllyvali') {
		$selsorttaus['hyllyvali'] = "SELECTED";
	}
	else {
		$selsorttaus['hyllyalue'] = "SELECTED";
	}

	echo "<td><select name='sorttauskentan_jarjestys1'>";
	echo "<option value='hyllyalue'  $selsorttaus[hyllyalue]>".t("Hyllyalue")."</option>";
	echo "<option value='hyllynro' $selsorttaus[hyllynro]>".t("Hyllynro")."</option>";
	echo "<option value='hyllyvali' $selsorttaus[hyllyvali]>".t("Hyllyvali")."</option>";
	echo "<option value='hyllytaso' $selsorttaus[hyllytaso]>".t("Hyllytaso")."</option>";
	echo "</select>";

	$selsorttaus = array();

	if ($sorttauskentan_jarjestys2 == 'hyllytaso') {
		$selsorttaus['hyllytaso'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys2 == 'hyllyalue') {
		$selsorttaus['hyllyalue'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys2 == 'hyllyvali') {
		$selsorttaus['hyllyvali'] = "SELECTED";
	}
	else {
		$selsorttaus['hyllynro'] = "SELECTED";
	}

	echo "<select name='sorttauskentan_jarjestys2'>";
	echo "<option value='hyllyalue'  $selsorttaus[hyllyalue]>".t("Hyllyalue")."</option>";
	echo "<option value='hyllynro' $selsorttaus[hyllynro]>".t("Hyllynro")."</option>";
	echo "<option value='hyllyvali' $selsorttaus[hyllyvali]>".t("Hyllyvali")."</option>";
	echo "<option value='hyllytaso' $selsorttaus[hyllytaso]>".t("Hyllytaso")."</option>";
	echo "</select>";

	$selsorttaus = array();

	if ($sorttauskentan_jarjestys3 == 'hyllytaso') {
		$selsorttaus['hyllytaso'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys3 == 'hyllynro') {
		$selsorttaus['hyllynro'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys3 == 'hyllyalue') {
		$selsorttaus['hyllyalue'] = "SELECTED";
	}
	else {
		$selsorttaus['hyllyvali'] = "SELECTED";
	}

	echo "<select name='sorttauskentan_jarjestys3'>";
	echo "<option value='hyllyalue'  $selsorttaus[hyllyalue]>".t("Hyllyalue")."</option>";
	echo "<option value='hyllynro' $selsorttaus[hyllynro]>".t("Hyllynro")."</option>";
	echo "<option value='hyllyvali' $selsorttaus[hyllyvali]>".t("Hyllyvali")."</option>";
	echo "<option value='hyllytaso' $selsorttaus[hyllytaso]>".t("Hyllytaso")."</option>";
	echo "</select>";

	$selsorttaus = array();

	if ($sorttauskentan_jarjestys4 == 'hyllyalue') {
		$selsorttaus['hyllyalue'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys4 == 'hyllynro') {
		$selsorttaus['hyllynro'] = "SELECTED";
	}
	elseif ($sorttauskentan_jarjestys4 == 'hyllyvali') {
		$selsorttaus['hyllyvali'] = "SELECTED";
	}
	else {
		$selsorttaus['hyllytaso'] = "SELECTED";
	}

	echo "<select name='sorttauskentan_jarjestys4'>";
	echo "<option value='hyllyalue'  $selsorttaus[hyllyalue]>".t("Hyllyalue")."</option>";
	echo "<option value='hyllynro' $selsorttaus[hyllynro]>".t("Hyllynro")."</option>";
	echo "<option value='hyllyvali' $selsorttaus[hyllyvali]>".t("Hyllyvali")."</option>";
	echo "<option value='hyllytaso' $selsorttaus[hyllytaso]>".t("Hyllytaso")."</option>";
	echo "</select>";
	echo "</td>";

	echo "</tr>";

	echo "</table>";

	echo "<br><input type='submit' name='tulosta' value='".t("Aja")."'>";
	echo "</form><br><br>";

	// jos paat ykkˆseks niin ei koita muokata/tulostaa file‰, vaan ekottaa filen polun ja nimen
	$debug = "0";


	if ($tee == 'TULOSTA' and isset($tulosta)) {

		$tulostimet[0] = "Inventointi";
		if (count($komento) == 0) {
			require("inc/valitse_tulostin.inc");
		}

		if ($ippa != '' and $ikka != '' and $ivva != '') {
			$idate = $ivva."-".$ikka."-".$ippa." 00:00:00";
			$invaamatta = " and tuotepaikat.inventointiaika <= '$idate'";
		}

		$rajauslisa = "";

		// jos ollaan ruksattu nayta myˆs inventoidut
		if ($naytainvtuot == '') {
			$rajauslisa .= " and tuotepaikat.inventointiaika <= date_sub(now(),interval 14 day) ";
		}

		// jos ei haluta invata poistettuja tuotteita
		if ($kertakassa == 'A') {
			$rajauslisa .= " and tuote.status != 'P' ";
		}

		// jos ollaan ruksattu vain saldolliset tuotteet
		if ($arvomatikka == 'S') {
			$extra = " and tuotepaikat.saldo > 0 ";
		}
		elseif ($arvomatikka == 'N') {
			$extra = " and tuotepaikat.saldo != 0 ";
		}
		else {
			$extra = "";
		}

		$kutsu = "";

		$sorttauskentan_jarjestys = "";

		if ($sorttauskentan_jarjestys1 == '' or $sorttauskentan_jarjestys2 == '' or $sorttauskentan_jarjestys3 == '' or $sorttauskentan_jarjestys4 == '') {
			$sorttauskentan_jarjestys = "concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0'))";
		}
		else {
			$sorttauskentan_jarjestys = 'concat(';

			if ($sorttauskentan_jarjestys1 != '') {
				$sorttauskentan_jarjestys1 = mysql_real_escape_string($sorttauskentan_jarjestys1);
				$sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.$sorttauskentan_jarjestys1), 5, '0'),";
			}

			if ($sorttauskentan_jarjestys2 != '') {
				$sorttauskentan_jarjestys2 = mysql_real_escape_string($sorttauskentan_jarjestys2);
				$sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.$sorttauskentan_jarjestys2), 5, '0'),";
			}

			if ($sorttauskentan_jarjestys3 != '') {
				$sorttauskentan_jarjestys3 = mysql_real_escape_string($sorttauskentan_jarjestys3);
				$sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.$sorttauskentan_jarjestys3), 5, '0'),";
			}

			if ($sorttauskentan_jarjestys4 != '') {
				$sorttauskentan_jarjestys4 = mysql_real_escape_string($sorttauskentan_jarjestys4);
				$sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.$sorttauskentan_jarjestys4), 5, '0'),";
			}

			$sorttauskentan_jarjestys = substr($sorttauskentan_jarjestys, 0, -1);

			$sorttauskentan_jarjestys .= ')';
		}

		//hakulause, t‰m‰ on samam kaikilla vaihtoehdolilla  ja gorup by lauyse joka on sama kaikilla
		$select  = " tuote.tuoteno, tuote.sarjanumeroseuranta, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno, tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo,
		$sorttauskentan_jarjestys sorttauskentta";
		$groupby = " tuote.tuoteno, tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, varastopaikka, inventointiaika, tuotepaikat.saldo ";

		if (($tryt != '' or $osastot != '') or ($ahyllyalue != '' and $lhyllyalue != '') or ($toimittaja != '') or ($tuotemerkki != '')) {
			///* Inventoidaan *///

			//n‰ytet‰‰n vain $top myydyint‰ tuotetta
			if ($top > 0) {
				$kutsu .= " ".t("Top:").$top." ";
				//Rullaava 6 kuukautta taaksep‰in
				$kka = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
				$vva = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
				$ppa = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));

				$query = "	SELECT tuote.tuoteno, sum(rivihinta) summa
							FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = ''
							WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
							and tilausrivi.tyyppi 			= 'L'
							$lisa
							and tilausrivi.laskutettuaika	>= '$vva-$kka-$ppa'
							GROUP BY 1
							ORDER BY summa desc
							LIMIT $top";
				$tuotresult = mysql_query($query) or pupe_error($query);

				$tuotenumerot = "";

				while ($tuotrow = mysql_fetch_array($tuotresult)) {
					$tuotenumerot .= "'".$tuotrow["tuoteno"]."',";
				}
				$tuotenumerot = substr($tuotenumerot,0,-1);

				if ($tuotenumerot != '') {
					$tuotelisa = " and tuote.tuoteno in ($tuotenumerot) ";
				}
				else {
					$tuotelisa = "";
				}
			}

			$where = "";

			if ($tryt != '' or $osastot != '') {
				///* Inventoidaan osaston tai tuoteryhm‰n perusteella *///
				$kutsu .= " ".t("Osasto:")."$osastot ".t("Tuoteryhm‰:")."$tryt ";

				$yhtiotaulu = "tuote";
				$from 		= " FROM tuote use index (osasto_try_index) ";
				$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $rajauslisa $invaamatta $extra ";
				$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno ";

				$where		= " $lisa and tuote.ei_saldoa = '' $tuotelisa ";
			}

			if ($tuotemerkki != '') {
				///* Inventoidaan tuotemerkin perusteella *///
				$kutsu .= " ".t("Tuotemerkki").": $tuotemerkki ";

				if ($from == '') {
					$yhtiotaulu = "tuote";
					$from 		= " FROM tuote use index (osasto_try_index) ";
					$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $rajauslisa $invaamatta $extra ";
					$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno ";
				}
				$where		.= " and tuote.tuotemerkki = '$tuotemerkki' ";
			}

			if ($ahyllyalue != '' and $lhyllyalue != '') {
				///* Inventoidaan tietty varastoalue *///
				$apaikka = strtoupper(sprintf("%-05s",$ahyllyalue)).strtoupper(sprintf("%05s",$ahyllynro)).strtoupper(sprintf("%05s",$ahyllyvali)).strtoupper(sprintf("%05s",$ahyllytaso));
				$lpaikka = strtoupper(sprintf("%-05s",$lhyllyalue)).strtoupper(sprintf("%05s",$lhyllynro)).strtoupper(sprintf("%05s",$lhyllyvali)).strtoupper(sprintf("%05s",$lhyllytaso));

				$kutsu .= " ".t("Varastopaikat").": $apaikka - $lpaikka ";

				if ($from == '') {
					$yhtiotaulu = "tuotepaikat";
					$from 		= " FROM tuotepaikat ";
					$join 		= " JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = '' $tuotelisa ";
					$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuotepaikat.yhtio and tuotteen_toimittajat.tuoteno = tuotepaikat.tuoteno ";

						$where		= "	and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso),5, '0')) >=
										concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
										and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso),5, '0')) <=
										concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))
										and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $rajauslisa $invaamatta $extra ";
					}
					else {
						$join		.= "	and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >=
											concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
											and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <=
											concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))";
				}

			}

			if ($toimittaja != '') {
				///* Inventoidaan tietyn toimittajan tuotteet *///
				$kutsu .= " ".t("Toimittaja:")."$toimittaja ";

				if ($from == '') {
					$yhtiotaulu = "tuotteen_toimittajat";
					$from 		= " FROM tuotteen_toimittajat ";
					$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $rajauslisa $invaamatta $extra
									JOIN tuote on tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.ei_saldoa = '' $tuotelisa ";

					$where		= " and tuotteen_toimittajat.toimittaja = '$toimittaja'";
				}
				else {
					$join 		.= " JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno and tuotteen_toimittajat.toimittaja = '$toimittaja' ";
				}
				$lefttoimi = "";
			}

			if ($jarjestys == 'tuoteno') {
				$orderby = " tuoteno, sorttauskentta ";
			}
			elseif ($jarjestys == 'osastotrytuoteno') {
				$orderby = " osasto, try, tuoteno, sorttauskentta ";
			}
			else {
				$orderby = " sorttauskentta, tuoteno ";
			}

			$query = "	SELECT $select
						$from
						$join
						$lefttoimi
						WHERE $yhtiotaulu.yhtio	= '$kukarow[yhtio]'
						$where
						group by $groupby
						ORDER BY $orderby";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Ei lˆytynyt rivej‰")."</font><br><br>";
				$tee='';
			}
		}
		elseif($raportti != '') {
			///* Inventoidaan jonkun raportin avulla *///

			if ($raportti == 'loppuneet') {

				$kutsu = " ".t("Loppuneet tuotteet")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";

				if ($jarjestys == 'tuoteno') {
					$orderby = " tuoteno, sorttauskentta ";
				}
				elseif ($jarjestys == 'osastotrytuoteno') {
					$orderby = " osasto, try, tuoteno, sorttauskentta ";
				}
				else {
					$orderby = " sorttauskentta, tuoteno ";
				}

				$query = "	SELECT $select
							FROM tuotepaikat use index (saldo_index)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.saldoaika >= '$vva-$kka-$ppa 00:00:00'
							and tuotepaikat.saldoaika <= '$vvl-$kkl-$ppl 23:59:59'
							and tuotepaikat.saldo 	  <= 0
							$rajauslisa
							$invaamatta
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY $orderby";
				$saldoresult = mysql_query($query) or pupe_error($query);
            }

			if ($raportti == 'vaarat') {

				$kutsu = " ".t("V‰‰r‰t Saldot")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";

				if ($jarjestys == 'tuoteno') {
					$orderby = " tuotepaikat.tuoteno, sorttauskentta ";
				}
				elseif ($jarjestys == 'osastotrytuoteno') {
					$orderby = " osasto, try, tuoteno, sorttauskentta ";
				}
				else {
					$orderby = " sorttauskentta, tuotepaikat.tuoteno ";
				}


				$query = "	SELECT distinct $select
							FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
							JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tilausrivi.yhtio and tuotepaikat.tuoteno = tilausrivi.tuoteno
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tilausrivi.yhtio			= '$kukarow[yhtio]'
							and tilausrivi.tyyppi 			= 'L'
							and tilausrivi.laskutettuaika	>= '$vva-$kka-$ppa'
							and tilausrivi.laskutettuaika	<= '$vvl-$kkl-$ppl'
							and tilausrivi.tilkpl	   		<> tilausrivi.kpl
							and tilausrivi.var 		   		in ('H','')
							and tuotepaikat.hyllyalue 		= tilausrivi.hyllyalue
							and tuotepaikat.hyllynro  		= tilausrivi.hyllynro
							and tuotepaikat.hyllyvali		= tilausrivi.hyllyvali
							and tuotepaikat.hyllytaso		= tilausrivi.hyllytaso
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY $orderby";
				$saldoresult = mysql_query($query) or pupe_error($query);
			}

			if ($raportti == 'negatiiviset') {

				$kutsu = " ".t("Tuotteet miinus-saldolla")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";

				if ($jarjestys == 'tuoteno') {
					$orderby = " tuoteno, sorttauskentta ";
				}
				elseif ($jarjestys == 'osastotrytuoteno') {
					$orderby = " osasto, try, tuoteno, sorttauskentta ";
				}
				else {
					$orderby = " sorttauskentta, tuoteno ";
				}

				$query = "	SELECT $select
							FROM tuotepaikat use index (saldo_index)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.saldo 	  < 0
							$rajauslisa
							$invaamatta
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY $orderby";
				$saldoresult = mysql_query($query) or pupe_error($query);
            }


			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("N‰ill‰ ehdoilla ei nyt lˆytynyt mit‰‰n! Skarppaas v‰h‰n!")."</font><br><br>";
				$tee='';
			}
		}
		elseif ($tila == "SIIVOUS") {
				$query = "	SELECT $select
							FROM tuotepaikat use index (primary)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.tunnus in ($saldot)
							$rajauslisa
							$invaamatta
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY sorttauskentta, tuoteno";
				$saldoresult = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "<font class='error'>".t("Et syˆtt‰nyt mit‰‰n j‰rkev‰‰! Skarppaas v‰h‰n!")."</font><br><br>";
			$tee='';
		}
	}

	if ($tee == 'TULOSTA' and isset($tulosta)) {
		if (mysql_num_rows($saldoresult) > 0 ) {
			//kirjoitetaan  faili levylle..
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$filenimi = "/tmp/Inventointilista-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($filenimi, "w+");

			$pp = date('d');
			$kk = date('m');
			$vv = date('Y');
			$kello = date('H:i:s');

			//rivinleveys default
			$rivinleveys = 135;

			//haetaan inventointilista numero t‰ss‰ vaiheessa
			$query = "	SELECT max(inventointilista) listanro
						FROM tuotepaikat
						WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$lrow = mysql_fetch_array($result);

			$listanro = $lrow["listanro"]+1;
			$listaaika = date("Y-m-d H:i:s");

			$ots  = t("Inventointilista")." $kutsu  Listanumero: $listanro   $pp.$kk.$vv - $kello\n$yhtiorow[nimi]\n\n";
			$ots .= sprintf ('%-28.14s', 	t("Paikka"));
			$ots .= sprintf ('%-21.21s', 	t("Tuoteno"));
			$ots .= sprintf ('%-21.21s', 	t("Toim.Tuoteno"));
			$ots .= sprintf ('%-40.38s', 	t("Nimitys"));

			if ($naytasaldo == 'H') {
				$rivinleveys += 10;
				$ots .= sprintf ('%-10.10s',t("Hyllyss‰"));
				$katkoviiva = '__________';
			}
			elseif ($naytasaldo == 'S') {
				$rivinleveys += 10;
				$ots .= sprintf ('%-10.10s',t("Saldo"));
				$katkoviiva = '__________';
			}

			$ots .= sprintf ('%-7.7s',		t("M‰‰r‰"));
			$ots .= sprintf ('%-9.9s', 		t("Yksikkˆ"));
			$ots .= sprintf ('%-8.8s',	 	t("Tikpl"));
			$ots .= "\n";
			$ots .= "_______________________________________________________________________________________________________________________________________$katkoviiva\n\n";
			fwrite($fh, $ots);
			$ots = chr(12).$ots;

			$rivit = 1;

			while($tuoterow = mysql_fetch_array($saldoresult)) {

				// Joskus halutaan vain tulostaa lista, mutta ei oikeasti invata tuotteita
				if ($ei_inventointi == "") {
					//p‰ivitet‰‰n tuotepaikan listanumero ja listaaika
					$query = "	UPDATE tuotepaikat
								SET inventointilista	= '$listanro',
								inventointilista_aika	= '$listaaika'
								WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
								and tuoteno		= '$tuoterow[tuoteno]'
								and hyllyalue	= '$tuoterow[hyllyalue]'
								and hyllynro 	= '$tuoterow[hyllynro]'
								and hyllyvali 	= '$tuoterow[hyllyvali]'
								and hyllytaso 	= '$tuoterow[hyllytaso]'
								LIMIT 1";
					$munresult = mysql_query($query) or pupe_error($query);
				}

				if ($rivit >= 17) {
					fwrite($fh, $ots);
					$rivit = 1;
				}

				if ($naytasaldo != '') {

					//katotaan mihin varastooon tilausrivill‰ tuotepaikka kuuluu
					$rivipaikka = kuuluukovarastoon($tuoterow["hyllyalue"], $tuoterow["hyllynro"]);

					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto,
								varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and varastopaikat.tunnus = '$rivipaikka'
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoterow[tuoteno]'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";

					$sresult = mysql_query($query) or pupe_error($query);

					$rivipaikkahyllyssa 	= 0;
					$rivivarastohyllyssa	= 0;
					$rivipaikkasaldo 		= 0;
					$rivivarastosaldo 		= 0;

					if (mysql_num_rows($sresult) > 0) {
						while ($saldorow = mysql_fetch_array ($sresult)) {

							list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', '', $saldorow["era"]);

							if ($saldorow['hyllyalue'] == $tuoterow['hyllyalue'] and $saldorow['hyllynro'] == $tuoterow['hyllynro'] and $saldorow['hyllyvali'] == $tuoterow['hyllyvali'] and $saldorow['hyllytaso'] == $tuoterow['hyllytaso']){
								$rivipaikkahyllyssa  += $hyllyssa;
								$rivipaikkasaldo += $saldo;
							}

							$rivivarastohyllyssa += $hyllyssa;
							$rivivarastosaldo += $saldo;
						}
					}
				}
				else {
					$rivipaikkahyllyssa 	= 0;
					$rivivarastohyllyssa 	= 0;
					$rivipaikkasaldo 		= 0;
					$rivivarastosaldo 		= 0;
				}

				//katsotaan onko tuotetta tilauksessa
				$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]' and varattu>0 and tyyppi='O'";
				$result1 = mysql_query($query) or pupe_error($query);
				$prow    = mysql_fetch_array($result1);

				if ($tuoterow["inventointiaika"]=='0000-00-00 00:00:00') {
					$tuoterow["inventointiaika"] = t("Ei inventoitu");
				}

				$prn  = sprintf ('%-28.14s', 	$tuoterow["varastopaikka"]);
				$prn .= sprintf ('%-21.21s', 	$tuoterow["tuoteno"]);
				$prn .= sprintf ('%-21.21s', 	$tuoterow["toim_tuoteno"]);
				$prn .= sprintf ('%-40.38s', 	t_tuotteen_avainsanat($tuoterow, 'nimitys'));

				if ($naytasaldo == 'H') {
					if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
						$prn .= sprintf ('%-10.10s', $rivipaikkahyllyssa."(".$rivivarastohyllyssa.")");
					}
					else {
						$prn .= sprintf ('%-10.10s', $rivipaikkahyllyssa);
					}
				}
				elseif ($naytasaldo == 'S') {
					if ($rivipaikkasaldo != $rivivarastosaldo) {
						$prn .= sprintf ('%-10.10s', $rivipaikkasaldo."(".$rivivarastosaldo.")");
					}
					else {
						$prn .= sprintf ('%-10.10s', $rivipaikkasaldo);
					}
				}

				$prn .= sprintf ('%-7.7s', 	"_____");
				$prn .= sprintf ('%-9.9s', 	t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite"));
				$prn .= sprintf ('%-8.8d', 	$prow["varattu"]);

				if ($tuoterow["sarjanumeroseuranta"] != "") {
					$query = "	SELECT sarjanumeroseuranta.sarjanumero,
								sarjanumeroseuranta.siirtorivitunnus,
								tilausrivi_osto.nimitys,
								tilausrivi_osto.perheid2 osto_perheid2,
								tilausrivi_osto.tunnus osto_rivitunnus,
								sarjanumeroseuranta.tunnus,
								round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta,
								era_kpl
								FROM sarjanumeroseuranta
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
								LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
								WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno		= '$tuoterow[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus	!= -1
								and (	(sarjanumeroseuranta.hyllyalue		= '$tuoterow[hyllyalue]'
										 and sarjanumeroseuranta.hyllynro 	= '$tuoterow[hyllynro]'
										 and sarjanumeroseuranta.hyllyvali 	= '$tuoterow[hyllyvali]'
										 and sarjanumeroseuranta.hyllytaso 	= '$tuoterow[hyllytaso]')
									 or ('$tuoterow[oletus]' != '' and
										(	SELECT tunnus
											FROM tuotepaikat tt
											WHERE sarjanumeroseuranta.yhtio = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
											and sarjanumeroseuranta.hyllynro = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
								and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00')
								ORDER BY sarjanumero";
					$sarjares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($sarjares) > 0) {
						while ($sarjarow = mysql_fetch_array($sarjares)) {

							if ($sarjarow["nimitys"] == $tuoterow["nimitys"]) $sarjarow["nimitys"] = "";

							if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
								$ztun = $sarjarow["osto_perheid2"];
							}
							else {
								$ztun = $sarjarow["siirtorivitunnus"];
							}

							if ($ztun > 0) {
								$query = "	SELECT tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero
											FROM tilausrivi
											LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
											WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
								$siires = mysql_query($query) or pupe_error($query);
								$siirow = mysql_fetch_array($siires);

								$fnlina22 = " / Lis‰varusteena: $siirow[tuoteno] $siirow[sarjanumero]";
							}
							else {
								$fnlina22 = "";
							}

							$prn .= "\n";

							$prn .= sprintf ('%-28.28s', "");
							$prn .= sprintf ('%-42.42s', $sarjarow["sarjanumero"]);
							$prn .= sprintf ('%-74.74s', $sarjarow["nimitys"].$fnlina22);

							if ($rivit >= 17) {
								fwrite($fh, $ots);
								$rivit = 1;
							}
						}
					}
				}

				$prn .= "\n\n";
				$prn .= "_______________________________________________________________________________________________________________________________________$katkoviiva\n";


				fwrite($fh, $prn);
				$rivit++;
			}

			fclose($fh);

			//k‰‰nnet‰‰n kaunniksi

			if ($debug == '1') {
				echo "filenimi=$filenimi<br>";
			}
			else {
				system("a2ps -o ".$filenimi.".ps -r --medium=A4 --chars-per-line=$rivinleveys --no-header --columns=1 --margin=0 --borders=0 $filenimi");

				if ($komento["Inventointi"] == 'email') {

					system("ps2pdf -sPAPERSIZE=a4 ".$filenimi.".ps ".$filenimi.".pdf");

					$liite = $filenimi.".pdf";
					$kutsu = "Inventointilista";

					require("inc/sahkoposti.inc");
				}
				elseif ($komento["Inventointi"] != '') {
					// itse print komento...
					$line = exec("$komento[Inventointi] ".$filenimi.".ps");
				}

				echo "<font class='message'>".t("Inventointilista tulostuu!")."</font><br><br>";

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $filenimi");
				system("rm -f ".$filenimi.".ps");
				system("rm -f ".$filenimi.".pdf");
			}

			$tee = "";
		}
	}

	require ("inc/footer.inc");
?>
