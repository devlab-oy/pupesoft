<?php

	ini_set('zlib.output_compression', 0);

	// Kutsutaanko CLI:st‰
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	// T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta
	$useslave = 1;

	if (!$php_cli) {
		require("../inc/parametrit.inc");
	}
	else {
		require_once("../inc/functions.inc");
		require_once("../inc/connect.inc");

		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		$tyyppi 	  = "";
		$email_osoite = "";

		$supertee = "RAPORTOI";
	}

	if (!function_exists("force_echo")) {
		function force_echo($teksti) {
			global $kukarow, $yhtiorow;

			echo t($teksti)."<br>";
			ob_flush();
			flush();
		}
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	if (!isset($pp)) $pp = date("d");
	if (!isset($kk)) $kk = date("m");
	if (!isset($vv)) $vv = date("Y");

	$pp	= sprintf("%02d", trim($pp));
	$kk	= sprintf("%02d", trim($kk));
	$vv	= sprintf("%04d", trim($vv));

	// setataan
	$lisa = "";

	if (!$php_cli) {

		echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

		// piirrell‰‰n formi
		echo "<form name='formi' method='post' autocomplete='OFF'>";
		echo "<input type='hidden' name='supertee' value='RAPORTOI'>";

		$noautosubmit = TRUE;
		$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
		require ("tilauskasittely/monivalintalaatikot.inc");

		if (isset($osasto_tyhjat) and $osasto_tyhjat != "") {
			$rukOchk = "CHECKED";
		}
		else {
			$rukOchk = "";
		}

		if (isset($tuoteryhma_tyhjat) and $tuoteryhma_tyhjat != "") {
			$rukTchk = "CHECKED";
		}
		else {
			$rukTchk = "";
		}

		echo "<br><table>
			<tr>
			<th>".t("Listaa vain tuotteet, jotka ei kuulu mihink‰‰n osastoon")."</th>
			<td><input type='checkbox' name='osasto_tyhjat' value='tyhjat' $rukOchk></td>
			</tr>
			<tr>
			<th>".t("Listaa vain tuotteet, jotka ei kuulu mihink‰‰n tuoteryhm‰‰n")."</th>
			<td><input type='checkbox' name='tuoteryhma_tyhjat' value='tyhjat' $rukTchk></td>
			</tr></table>";

		echo "<br><table>";

		$epakur_chk1 = "";
		$epakur_chk2 = "";
		$epakur_chk3 = "";

		if (isset($epakur) and $epakur == 'kaikki') {
			$epakur_chk1 = ' selected';
		}
		elseif (isset($epakur) and $epakur == 'epakur') {
			$epakur_chk2 = ' selected';
		}
		elseif (isset($epakur) and $epakur == 'ei_epakur') {
			$epakur_chk3 = ' selected';
		}

		echo "<tr>";
		echo "<th valign=top>",t("Tuoterajaus"),":</th><td>";
		echo "<select name='epakur'>";
		echo "<option value='kaikki'$epakur_chk1>",t("N‰yt‰ kaikki tuotteet"),"</option>";
		echo "<option value='epakur'$epakur_chk2>",t("N‰yt‰ vain ep‰kurantit tuotteet"),"</option>";
		echo "<option value='ei_epakur'$epakur_chk3>",t("N‰yt‰ varastonarvoon vaikuttavat tuotteet"),"</option>";
		echo "</select>";
		echo "</td></tr>";

		echo "<tr>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";

		if (isset($tyyppi) and $tyyppi == "A") {
			$sel1 = "SELECTED";
		}
		elseif (isset($tyyppi) and $tyyppi == "B") {
			$sel2 = "SELECTED";
		}
		elseif (isset($tyyppi) and $tyyppi == "C") {
			$sel3 = "SELECTED";
		}
		elseif (isset($tyyppi) and $tyyppi == "D") {
			$sel4 = "SELECTED";
		}

		echo "<th valign=top>",t("Saldorajaus"),":</th>";
		echo "<td>
				<select name='tyyppi'>
				<option value='A' $sel1>".t("N‰ytet‰‰n tuotteet joilla on saldoa")."</option>
				<option value='B' $sel2>".t("N‰ytet‰‰n tuotteet joilla ei ole saldoa")."</option>
				<option value='C' $sel3>".t("N‰ytet‰‰n kaikki tuotteet")."</option>
				<option value='D' $sel4>".t("N‰ytet‰‰n miinus-saldolliset tuotteet")."</option>
				</select>
				</td>";
		echo "</tr>";

		echo "<tr>";

		$sel1 = "";
		$sel2 = "";

		if (isset($varatturajaus) and $varatturajaus == "O") {
			$sel1 = "SELECTED";
		}
		elseif (isset($varatturajaus) and $varatturajaus == "E") {
			$sel2 = "SELECTED";
		}

		echo "<th valign=top>",t("Varausrajaus"),":</th>";
		echo "<td>
				<select name='varatturajaus'>
				<option value=''>".t("Ei rajausta")."</option>
				<option value='O' $sel1>".t("N‰ytet‰‰n tuotteet joilla on varauksia")."</option>
				<option value='E' $sel2>".t("N‰ytet‰‰n tuotteet joilla ei ole varauksia")."</option>
				</select>
				</td>";
		echo "</tr>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if (isset($summaustaso) and $summaustaso == "S") {
			$sel1 = "SELECTED";
		}
		elseif (isset($summaustaso) and $summaustaso == "P") {
			$sel2 = "SELECTED";
		}
		elseif (isset($summaustaso) and $summaustaso == "T") {
			$sel3 = "SELECTED";
		}
		elseif (isset($summaustaso) and $summaustaso == "TRY") {
			$sel4 = "SELECTED";
		}
		elseif (isset($summaustaso) and $summaustaso == "TRYS") {
			$sel5 = "SELECTED";
		}
		
		echo "<tr>";
		echo "<th>".t("Summaustaso").":</th>";

		echo "<td>
				<select name='summaustaso'>
				<option value='S'   $sel1>".t("Varastonarvo varastoittain/tuotteittain")."</option>
				<option value='P'   $sel2>".t("Varastonarvo varastopaikoittain")."</option>
				<option value='T'   $sel3>".t("Varastonarvo tuotteittain")."</option>
				<option value='TRY' $sel4>".t("Varastonarvo tuoteryhmitt‰in")."</option>
				<option value='TRYS' $sel5>".t("Varastonarvo tuoteryhmitt‰in (saldo)")."</option>
				</select>
				</td>";
		echo "</tr>";

		echo "<tr><th>",t("Statusrajaus"),"</th>";

		$result = t_avainsana("S");

		echo "<td><select name='status'><option value=''>",t("Kaikki"),"</option>";

		while ($statusrow = mysql_fetch_assoc($result)) {

			$sel = '';

			if (isset($status) and $status == $statusrow['selite']) $sel = ' SELECTED';

			echo "<option value='$statusrow[selite]'$sel>$statusrow[selite] - $statusrow[selitetark]</option>";
		}

		echo "</select></td></tr>";

		$query  = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tyyppi, nimitys";
		$vares = pupe_query($query);

		echo "<tr>
				<th valign=top>".t('Varastorajaus').":</th>
		    <td>";

		$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

        while ($varow = mysql_fetch_assoc($vares)) {
			$sel = '';
			if (in_array($varow['tunnus'], $varastot)) {
				$sel = 'checked';
			}

			echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
		}

		echo "</td><td class='back' valign='top'>".t('Saat kaikki varastot jos et valitse mit‰‰n').".</td></tr>";

		echo "<tr>";
		echo "<th>Syˆt‰ vvvv-kk-pp:</th>";
		echo "<td><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
		echo "</tr>";

		if (!isset($alaraja)) $alaraja = "";
		if (!isset($ylaraja)) $ylaraja = "";

		echo "<tr>";
		echo "<th valign='top'>".t("Varastonarvorajaus").":</th>";
		echo "<td>".t("Alaraja").": <input type='text' name='alaraja' size='7' value='$alaraja'><br>".t("Yl‰raja").": <input type='text' name='ylaraja' size='7' value='$ylaraja'></td>";
		echo "</tr>";

		if (!isset($tuotteet_lista)) $tuotteet_lista = "";

		echo "<tr><th valign='top'>".t("Tuotelista").":</th><td><textarea name='tuotteet_lista' rows='5' cols='35'>$tuotteet_lista</textarea></td></tr>";

		echo "</table>";
		echo "<br>";

		echo "<input type='submit' value='Laske varastonarvot'>";
		echo "</form><br><br>";
	}

	// h‰rski oikeellisuustzekki
	if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

	$varastot2 = array();

	if (isset($supertee) and $supertee == "RAPORTOI" or ($php_cli and $argv[0] == 'varastonarvo-super.php' and $argv[1] != '')) {

		if ($php_cli and $argv[0] == 'varastonarvo-super.php' and $argv[1] != '' and $argv[2] != '') {

			$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);

			$query = "	SELECT *
						FROM yhtio
						WHERE yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 0) {
				echo "<b>K‰ytt‰j‰n yritys ei lˆydy! ($kukarow[yhtio])";
				exit;
			}

			$yhtiorow = mysql_fetch_assoc($result);

			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_assoc($result);

				if ($yhtion_parametritrow['hintapyoristys'] != 2 and $yhtion_parametritrow['hintapyoristys'] != 4 and $yhtion_parametritrow['hintapyoristys'] != 6) {
					$yhtion_parametritrow['hintapyoristys'] = 2;
				}

				// lis‰t‰‰n kaikki yhtiorow arrayseen
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

			$varastot = explode(",", $argv[2]);
			$email_osoite = mysql_real_escape_string($argv[3]);

			$epakur = 'kaikki';
			$tyyppi = 'A';
		}

		################## Jos summaustaso on paikka, otetaan paikat mukaan selectiin ##################
		$paikka_lisa1 = "";
		$paikka_lisa2 = "";

		if (isset($summaustaso) and $summaustaso == "P") {
			$paikka_lisa1 = ", 	tapahtuma.hyllyalue,
							  	tapahtuma.hyllynro,
							  	tapahtuma.hyllyvali,
							  	tapahtuma.hyllytaso";

			$paikka_lisa2 = ", tuotepaikat.hyllyalue,
							  	tuotepaikat.hyllynro,
							  	tuotepaikat.hyllyvali,
							  	tuotepaikat.hyllytaso";
		}

		##################  Sorttausj‰rjestykset ##################
		$order_lisa  	= "";
		$jarjestys_sel  = "";
		$jarjestys_join = "";

		// Tarkastetaan yhtion parametreista tuotteiden_jarjestys_raportoinnissa (V = variaation, koon ja varin mukaan)
		if ($yhtiorow['tuotteiden_jarjestys_raportoinnissa'] == 'V') {
			// Order by lisa
			$order_extra = 'variaatio, vari, koko';

			// queryyn muutoksia jos lajitellaan n‰in
			$jarjestys_sel = ", 	t1.selite as variaatio,
									t2.selite as vari,
									if(t3.jarjestys = 0 or t3.jarjestys is null, 999999, t3.jarjestys) koko";

			$jarjestys_join = "LEFT JOIN tuotteen_avainsanat t1 ON tuote.yhtio = t1.yhtio AND tuote.tuoteno = t1.tuoteno AND t1.laji = 'parametri_variaatio' AND t1.kieli = '{$yhtiorow['kieli']}'
								LEFT JOIN tuotteen_avainsanat t2 ON tuote.yhtio = t2.yhtio AND tuote.tuoteno = t2.tuoteno AND t2.laji = 'parametri_vari' AND t2.kieli = '{$yhtiorow['kieli']}'
								LEFT JOIN tuotteen_avainsanat t3 ON tuote.yhtio = t3.yhtio AND tuote.tuoteno = t3.tuoteno AND t3.laji = 'parametri_koko' AND t3.kieli = '{$yhtiorow['kieli']}'";
		}
		else {
			$order_extra = 'tuoteno';
		}

		################## laitetaan varastopaikkojen tunnukset mysql-muotoon ##################
		$varastontunnukset = "";

		if (!empty($varastot)) {
			$varastontunnukset = " AND varastopaikat.tunnus IN (".implode(",", $varastot).")";

			if ($summaustaso == "T" or $summaustaso == "TRY" or $summaustaso == "TRYS") {
				$order_lisa = "osasto, try, $order_extra";
			}
			else {
				$order_lisa = "varastonnimi, osasto, try, $order_extra";
			}
		}
		else {
			$order_lisa = "osasto, try, $order_extra";
		}

		################## tuoterajauksia ##################
		$tuote_lisa  = "";

		if (isset($epakur) and $epakur == 'epakur') {
			$tuote_lisa .= " AND (tuote.epakurantti100pvm != '0000-00-00' OR tuote.epakurantti75pvm != '0000-00-00' OR tuote.epakurantti50pvm != '0000-00-00' OR tuote.epakurantti25pvm != '0000-00-00') ";
		}
		elseif (isset($epakur) and $epakur == 'ei_epakur') {
			$tuote_lisa .= " AND tuote.epakurantti100pvm = '0000-00-00' ";
		}

		if (isset($tuotteet_lista) and $tuotteet_lista != '') {
			$tuotteet = explode("\n", $tuotteet_lista);
			$tuoterajaus = "";

			foreach($tuotteet as $tuote) {
				if (trim($tuote) != '') {
					$tuoterajaus .= "'".trim($tuote)."',";
				}
			}

			$tuote_lisa .= "and tuote.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
		}

		if (isset($status) and $status != '') {
			$tuote_lisa .= " and tuote.status = '".(string) $status."' ";
		}

		################## monivalintalaatikoiden rajaukset ##################
		if ($lisa != "") {
			$tuote_lisa .= $lisa;
		}

		################## varattu-rajaukset ##################
		if (isset($varatturajaus) and $varatturajaus != "") {
			$query = "	SELECT group_concat(distinct concat('\'',tuoteno,'\'')) varatut_tuotteet
						FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi in ('B','F','L','V','W')
						and tuoteno != ''
						and varattu != 0";
			$varares = pupe_query($query);
			$vararow = mysql_fetch_assoc($varares);

			$varatut_tuotteet = "''";

			if ($vararow["varatut_tuotteet"] != "") {
				$varatut_tuotteet = $vararow["varatut_tuotteet"];
			}

			// N‰ytet‰‰n vain varatut tuotteet
			if ($varatturajaus == "O") {
				$tuote_lisa .= " and tuote.tuoteno in ($varatut_tuotteet) ";
			}

			// N‰ytet‰‰n vain EI varatut tuotteet
			if ($varatturajaus == "E") {
				$tuote_lisa .= " and tuote.tuoteno not in ($varatut_tuotteet) ";
			}
		}

		################## lis‰ehtoja ##################
		$where_lisa  = "";

		if (isset($tuoteryhma_tyhjat) and $tuoteryhma_tyhjat == "tyhjat" and isset($osasto_tyhjat) and $osasto_tyhjat == "tyhjat") {
			$where_lisa .= "AND (try = '0' or osasto = '0') ";
		}
		elseif (isset($osasto_tyhjat) and $osasto_tyhjat == "tyhjat") {
			$where_lisa .= "AND osasto = '0' ";
		}
		elseif (isset($tuoteryhma_tyhjat) and $tuoteryhma_tyhjat == "tyhjat") {
			$where_lisa .= "AND try = '0' ";
		}

		################## Varaston tiedot ##################
		$varastolisa1 = " varastopaikat.nimitys varastonnimi, varastopaikat.tunnus varastotunnus, ";
		$varastolisa2 = " tmp_tuotepaikat.varastonnimi, tmp_tuotepaikat.varastotunnus, ";

		if ($summaustaso == 'T' or $summaustaso == 'TRY' or $summaustaso == "TRYS") {
			$varastolisa1 = "";
			$varastolisa3 = "";
		}

		if (!$php_cli) {
			force_echo("Haetaan k‰sitelt‰vien tuotteiden varastopaikat historiasta.");
		}

		if ($summaustaso == "TRYS") {
			$varastolisa1 = "t1.selite variaatiotieto23, group_concat(distinct concat('\'', tapahtuma.tuoteno ,'\'')) tuoteno, group_concat(distinct varastopaikat.nimitys) varastonnimi, group_concat(distinct varastopaikat.tunnus) varastotunnus,";
			$varastolisa3 = "t1.selite variaatiotieto23, group_concat(distinct concat('\'', tuotepaikat.tuoteno ,'\'')) tuoteno, group_concat(distinct varastopaikat.nimitys) varastonnimi, group_concat(distinct varastopaikat.tunnus) varastotunnus,";
			$lisa_grouppaus_muuttuja = " group by variaatiotieto23 ";
		}
		else {
			$varastolisa1 = "concat('\'', tapahtuma.tuoteno ,'\'') tuoteno,";
			$varastolisa3 = "concat('\'', tuotepaikat.tuoteno ,'\'') tuoteno,";
		}

		// haetaan kaikki distinct tuotepaikat ja tehd‰‰n temp table (t‰m‰ n‰ytt‰‰ ep‰tehokkaalta, mutta on testattu ja t‰m‰ _on_ nopein tapa joinata ja tehd‰ asia)
		$query = "	(SELECT DISTINCT
					$varastolisa1					
					tapahtuma.yhtio,
					tuote.try,
					tuote.osasto,
					tuote.tuotemerkki,
					tuote.yksikko,
					tuote.nimitys,
					tuote.kehahin,
					if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) kehahin_nyt,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					tuote.sarjanumeroseuranta,
					tuote.vihapvm
					$paikka_lisa1
					$jarjestys_sel
					FROM tapahtuma USE INDEX (yhtio_laadittu_hyllyalue_hyllynro)
					JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio AND tuote.tuoteno = tapahtuma.tuoteno AND tuote.ei_saldoa = '' $tuote_lisa)
					JOIN varastopaikat ON	(varastopaikat.yhtio = tapahtuma.yhtio
											AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0'))  <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
											AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
											$varastontunnukset)
					$jarjestys_join
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					AND tapahtuma.laadittu > '$vv-$kk-$pp 23:59:59'
					$where_lisa
					$lisa_grouppaus_muuttuja)
					UNION DISTINCT
					(SELECT DISTINCT
					$varastolisa3
					tuotepaikat.yhtio,
					tuote.try,
					tuote.osasto,
					tuote.tuotemerkki,
					tuote.yksikko,
					tuote.nimitys,
					tuote.kehahin,
					if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) kehahin_nyt,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					tuote.sarjanumeroseuranta,
					tuote.vihapvm
					$paikka_lisa2
					$jarjestys_sel
					FROM tuotepaikat USE INDEX (tuote_index)
					JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ei_saldoa = '' $tuote_lisa)
					JOIN varastopaikat ON	(varastopaikat.yhtio = tuotepaikat.yhtio
											AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0'))  <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
											AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
											$varastontunnukset)
					$jarjestys_join
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
					$where_lisa
					$lisa_grouppaus_muuttuja
					)					
					ORDER BY $order_lisa";
		$result = pupe_query($query);

		$elements = mysql_num_rows($result);

		if (!$php_cli) {
			echo t("Tuotteita/tuotepaikkoja"),": $elements<br>";
		}

		if ($summaustaso == 'TRY' or $summaustaso == "TRYS") {
			$query  = " SELECT distinct selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and kieli 	= '$yhtiorow[kieli]'
						and laji 	= 'TRY'";
			$try_result = pupe_query($query);

			$try_array = array();
			$try_array[0] = t("Ei tuoteryhm‰‰");

			while ($row = mysql_fetch_assoc($try_result)) {
				$try_array[$row["selite"]] = $row["selitetark"];
			}

			$query  = " SELECT distinct selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and kieli 	= '$yhtiorow[kieli]'
						and laji 	= 'OSASTO'";
			$osasto_result = pupe_query($query);

			$osasto_array = array();
			$osasto_array[0] = t("Ei osastoa");

			while ($row = mysql_fetch_assoc($osasto_result)) {
				$osasto_array[$row["selite"]] = $row["selitetark"];
			}
		}

		$lask   = 0;
		$varvo  = 0; // t‰h‰n summaillaan
		$bvarvo = 0; // bruttovarastonarvo

		if (@include('Spreadsheet/Excel/Writer.php')) {
			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			if ($php_cli and count($argv) > 0) {
				$excelnimi = "Varastonarvo_$vv-$kk-$pp.xls";
			}

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;
		}

		if (isset($workbook)) {
			$excelsarake = 0;

			if ($summaustaso != "T" and $summaustaso != "TRY" and $summaustaso != "TRYS") {
				$worksheet->writeString($excelrivi, $excelsarake, t("Varasto"), 		$format_bold);
				$excelsarake++;
			}

			if ($summaustaso == "P") {
				$worksheet->writeString($excelrivi, $excelsarake, t("Hyllyalue"), 		$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Hyllynro"), 		$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Hyllyvali"), 		$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Hyllytaso"), 		$format_bold);
				$excelsarake++;
			}

			if (isset($sel_tuotemerkki) and $sel_tuotemerkki != '') {
				$worksheet->writeString($excelrivi, $excelsarake, t("Tuotemerkki"), 	$format_bold);
				$excelsarake++;
			}

			$worksheet->writeString($excelrivi, $excelsarake, t("Osasto"), 				$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteryhm‰"), 			$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"), 			$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"), 			$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Yksikko"), 			$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Saldo"), 				$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Kehahin"), 			$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Varastonarvo"), 		$format_bold);
			$excelsarake++;
			if ("$vv-$kk-$pp" != date("Y-m-d")) {
				$worksheet->writeString($excelrivi, $excelsarake, t("Bruttovarastonarvo")." ".t("Arvio"), $format_bold);
			}
			else {
				$worksheet->writeString($excelrivi, $excelsarake, t("Bruttovarastonarvo"), 	$format_bold);
			}
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Kiertonopeus 12kk"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Viimeisin laskutus")."/".t("kulutus"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Ep‰kurantti 25%"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Ep‰kurantti 50%"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Ep‰kurantti 75%"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Ep‰kurantti 100%"), 	$format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, t("Viimeinen hankintap‰iv‰"), 	$format_bold);
			$excelrivi++;
			$excelsarake = 0;
		}

		if (!$php_cli) {
			echo "<a name='focus_tahan'>".t("Lasketaan varastonarvo")."...<br></a>";
			echo "<script LANGUAGE='JavaScript'>window.location.hash=\"focus_tahan\";</script>";

			if ($elements > 0) {
				require_once ('inc/ProgressBar.class.php');
				$bar = new ProgressBar();
				$bar->initialize($elements); // print the empty bar
			}
		}

		while ($row = mysql_fetch_assoc($result)) {

			$kpl = 0;
			$varaston_arvo = 0;
			$bruttovaraston_arvo = 0;
			$lask++;

			if (!$php_cli) {
				$bar->increase();
			}

			if ($summaustaso == 'T' or $summaustaso == 'TRY' or $summaustaso == "TRYS") {
				$mistavarastosta = $varastontunnukset;
			}
			else {
				$mistavarastosta = " and varastopaikat.tunnus = '$row[varastotunnus]' ";
			}

			// Jos tuote on sarjanumeroseurannassa niin varastonarvo lasketaan yksilˆiden ostohinnoista (ostetut yksilˆt jotka eiv‰t viel‰ ole laskutettu)
			if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {

				// jos summaustaso on per paikka, otetaan varastonarvo vain silt‰ paikalta
				if ($summaustaso == "P") {
					$summaus_lisa = "	and sarjanumeroseuranta.hyllyalue = '$row[hyllyalue]'
										and sarjanumeroseuranta.hyllynro  = '$row[hyllynro]'
										and sarjanumeroseuranta.hyllyvali = '$row[hyllyvali]'
										and sarjanumeroseuranta.hyllytaso = '$row[hyllytaso]'";
				}
				else {
					$summaus_lisa = "";
				}

				if ($row["sarjanumeroseuranta"] == "G") {
					# Haetaan vapaat er‰t varastosta ja lasketaan niiden saldot
					$query	= "		SELECT sarjanumeroseuranta.tunnus, sarjanumeroseuranta.era_kpl
									FROM sarjanumeroseuranta
									JOIN varastopaikat ON (varastopaikat.yhtio = sarjanumeroseuranta.yhtio
															and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
															and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
															$mistavarastosta)
									WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
									and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
									and sarjanumeroseuranta.ostorivitunnus>0
									and sarjanumeroseuranta.myyntirivitunnus=0
									$summaus_lisa
									HAVING era_kpl > 0";
					$vararvores = pupe_query($query);

					while ($vararvorow = mysql_fetch_assoc($vararvores)) {
						$varaston_arvo += sarjanumeron_ostohinta("tunnus", $vararvorow["tunnus"], "", "$vv-$kk-$pp 23:59:59")*$vararvorow["era_kpl"];
						$bruttovaraston_arvo = $varaston_arvo;
						$kpl += $vararvorow["era_kpl"]; // saldo
					}
				}
				else {
					$query	= "	SELECT sarjanumeroseuranta.tunnus, sarjanumeroseuranta.era_kpl era_kpl
								FROM sarjanumeroseuranta
								JOIN varastopaikat ON (varastopaikat.yhtio = sarjanumeroseuranta.yhtio
														and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
														and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
														$mistavarastosta)
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON (tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus)
								LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus != -1
								$summaus_lisa
								and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
								and tilausrivi_osto.laskutettuaika != '0000-00-00'";
					$vararvores = pupe_query($query);

					while ($vararvorow = mysql_fetch_assoc($vararvores)) {
						//	Jos meill‰ on er‰seurattu in-out arvoinen tuote, meid‰n pit‰‰
						$varaston_arvo += sarjanumeron_ostohinta("tunnus", $vararvorow["tunnus"], "", "$vv-$kk-$pp 23:59:59");
						$bruttovaraston_arvo = $varaston_arvo;
						$kpl++; // saldo
					}
				}
			}
			else {

				// jos summaustaso on per paikka, otetaan varastonarvo vain silt‰ paikalta
				if ($summaustaso == "P") {
					$summaus_lisa = "	and tuotepaikat.hyllyalue = '$row[hyllyalue]'
										and tuotepaikat.hyllynro = '$row[hyllynro]'
										and tuotepaikat.hyllyvali = '$row[hyllyvali]'
										and tuotepaikat.hyllytaso = '$row[hyllytaso]'";
				}
				else {
					$summaus_lisa = "";
				}

				$query = "	SELECT
							sum(tuotepaikat.saldo) saldo,
							sum(tuotepaikat.saldo*if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0)) varasto,
							sum(tuotepaikat.saldo*tuote.kehahin) bruttovarasto
							FROM tuotepaikat
							JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio and tuote.ei_saldoa = '')
							JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
													and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
													and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
													$mistavarastosta)
							WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno in ({$row[tuoteno]})
							$summaus_lisa";

				$vararvores = pupe_query($query);
				$vararvorow = mysql_fetch_assoc($vararvores);
				
				# query_dump($query);
				$kpl = (float) $vararvorow["saldo"];
				$varaston_arvo = (float) $vararvorow["varasto"];
				$bruttovaraston_arvo = (float) $vararvorow["bruttovarasto"];
			}

			// jos summaustaso on per paikka, otetaan varastonmuutos vain silt‰ paikalta
			if ($summaustaso == "P") {
				$summaus_lisa = "	and tapahtuma.hyllyalue = '$row[hyllyalue]'
									and tapahtuma.hyllynro = '$row[hyllynro]'
									and tapahtuma.hyllyvali = '$row[hyllyvali]'
									and tapahtuma.hyllytaso = '$row[hyllytaso]'";
			}
			else {
				$summaus_lisa = "";
			}

			// tuotteen muutos varastossa annetun p‰iv‰n j‰lkeen
			// jos samalle p‰iv‰lle on ep‰kuranttitapahtumia ja muita tapahtumia (esim. inventointi), niin bruttovarastonarvo heitt‰‰, koska ep‰kuranttitapahtuma on t‰llˆin p‰iv‰n eka tapahtuma (huom. 00:00:00)
			$query = "	SELECT
						sum(kpl * if(laji in ('tulo', 'valmistus'), kplhinta, hinta)) muutoshinta,
						sum(kpl * if(laji in ('tulo', 'valmistus'), kplhinta,
						if(tapahtuma.laadittu <= '$row[epakurantti100pvm] 00:00:00' or '$row[epakurantti100pvm]' = '0000-00-00',
							if(tapahtuma.laadittu <= '$row[epakurantti75pvm] 00:00:00' or '$row[epakurantti75pvm]' = '0000-00-00',
								if(tapahtuma.laadittu <= '$row[epakurantti50pvm] 00:00:00' or '$row[epakurantti50pvm]' = '0000-00-00',
									if(tapahtuma.laadittu <= '$row[epakurantti25pvm] 00:00:00' or '$row[epakurantti25pvm]' = '0000-00-00', hinta, hinta / 0.75), hinta / 0.5), hinta / 0.25), 0))) bmuutoshinta,
						sum(kpl) muutoskpl,
						tapahtuma.laadittu
			 			FROM tapahtuma use index (yhtio_tuote_laadittu)
						JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
												and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
												and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
												$mistavarastosta)
			 			WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
			 			and tapahtuma.tuoteno in ($row[tuoteno])
			 			and tapahtuma.laadittu > '$vv-$kk-$pp 23:59:59'
						$summaus_lisa
						GROUP BY tapahtuma.laadittu
						ORDER BY tapahtuma.laadittu DESC, tapahtuma.tunnus desc";
			$muutosres = pupe_query($query);

			$muutoskpl 		= $kpl;
			$muutoshinta 	= $varaston_arvo;
			$bmuutoshinta 	= $bruttovaraston_arvo;
			$edlaadittu 	= '';

			if (mysql_num_rows($muutosres) == 0) {
				$uusintapahtuma = "$vv-$kk-$pp 23:59:59";
			}
			else {
				$muutosrow = mysql_fetch_assoc($muutosres);

				$uusintapahtuma = $muutosrow["laadittu"];

				mysql_data_seek($muutosres, 0);
			}

			// Ep‰kurantit haetaan tapahtumista erikseen, koska niill‰ on hyllyalue, hyllynro, hyllytaso ja hyllyvali tyhj‰‰
			$query = "	SELECT sum($muutoskpl * hinta) muutoshinta
			 			FROM tapahtuma use index (yhtio_tuote_laadittu)
			 			WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
			 			and tapahtuma.tuoteno in ($row[tuoteno])
			 			and tapahtuma.laadittu >= '$uusintapahtuma'
						and tapahtuma.laji = 'Ep‰kurantti'";
			$epares = pupe_query($query);

			if (mysql_num_rows($epares) > 0) {
				$eparow = mysql_fetch_assoc($epares);

				// Ep‰kuranteissa saldo ei muutu!!! eli ei v‰hennet‰ $muutoskpl
				$muutoshinta += $eparow['muutoshinta'];
			}

			if (mysql_num_rows($muutosres) > 0) {
				while ($muutosrow = mysql_fetch_assoc($muutosres)) {

					if ($edlaadittu != '') {
						// Ep‰kurantit haetaan tapahtumista erikseen, koska niill‰ on hyllyalue, hyllynro, hyllytaso ja hyllyvali tyhj‰‰
						$query = "	SELECT sum($muutoskpl * hinta) muutoshinta
						 			FROM tapahtuma use index (yhtio_tuote_laadittu)
						 			WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						 			and tapahtuma.tuoteno in ($row[tuoteno])
						 			and tapahtuma.laadittu >= '$muutosrow[laadittu]'
									and tapahtuma.laadittu < '$edlaadittu'
									and tapahtuma.laji = 'Ep‰kurantti'";
						$epares = pupe_query($query);

						if (mysql_num_rows($epares) > 0) {
							$eparow = mysql_fetch_assoc($epares);

							// Ep‰kuranteissa saldo ei muutu!!! eli ei v‰hennet‰ $muutoskpl
							$muutoshinta += $eparow['muutoshinta'];
						}
					}

					// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
					$muutoskpl -= $muutosrow["muutoskpl"];

					// arvo historiassa: lasketaan nykyinen arvo - muutosarvo
					$muutoshinta -= $muutosrow["muutoshinta"];
					$bmuutoshinta -= $muutosrow["bmuutoshinta"];

					$edlaadittu = $muutosrow['laadittu'];
				}
			}

			if ($tyyppi == "C") {
				$ok = "GO";
			}
			elseif ($tyyppi == "A" and $muutoskpl != 0) {
				$ok = "GO";
			}
			elseif ($tyyppi == "B" and $muutoskpl == 0) {
				$ok = "GO";
			}
			elseif ($tyyppi == "D" and $muutoskpl < 0) {
				$ok = "GO";
			}
			else {
				$ok = "NO-GO";
			}

			if ($muutoshinta < $alaraja and $alaraja != '') {
				$ok = "NO-GO";
			}

			if ($muutoshinta > $ylaraja and $ylaraja != '') {
				$ok = "NO-GO";
			}

			if ($ok == "GO") {
				// summataan varastonarvoa
				$varvo += $muutoshinta;
				$bvarvo += $bmuutoshinta;
				$kehalisa = "";

				// sarjanumerollisilla tuotteilla ei ole keskihankintahintaa
				if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {
					if ($kpl == 0) {
						$kehasilloin = 0;
						$bkehasilloin = 0;
						$kehalisa = "~";
					}
					else {
						$kehasilloin = round($varaston_arvo / $kpl, 6); // lasketaan "kehahin"
						$bkehasilloin = $kehasilloin;
						$kehalisa = "~";
					}
				}
				else {
					// yritet‰‰n kaivaa listaan viel‰ sen hetkinen kehahin jos se halutaan kerran n‰hd‰
					$kehasilloin = $row["kehahin_nyt"];		// nykyinen kehahin
					$bkehasilloin = $row["kehahin"];		// brutto kehahin

					// ei suotta haeskella keharia jos ajetaan t‰lle p‰iv‰lle
					if (date("Y-m-d") != "$vv-$kk-$pp") {
						// katotaan mik‰ oli tuotteen viimeisin hinta annettuna p‰iv‰n‰ tai sitten sit‰ ennen
						$query = "	SELECT hinta
									FROM tapahtuma use index (yhtio_tuote_laadittu)
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = ($row[tuoteno])
									and laadittu <= '$vv-$kk-$pp 23:59:59'
									and laji NOT IN ('poistettupaikka','uusipaikka')
									ORDER BY laadittu desc, tunnus desc
									LIMIT 1";
						$ares = pupe_query($query);

						if (mysql_num_rows($ares) == 1) {
							// lˆydettiin keskihankintahinta tapahtumista k‰ytet‰‰n
							$arow = mysql_fetch_assoc($ares);
							$kehasilloin  = $arow["hinta"];
							$bkehasilloin = $arow["hinta"];
							$kehalisa = "";
						}
						else {
							// ei lˆydetty alasp‰in, kokeillaan kattoo l‰hin hinta ylˆsp‰in
							$query = "	SELECT hinta
										FROM tapahtuma use index (yhtio_tuote_laadittu)
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = in ($row[tuoteno])
										and laadittu > '$vv-$kk-$pp 23:59:59'
										and laji NOT IN ('poistettupaikka','uusipaikka')
										ORDER BY laadittu, tunnus
										LIMIT 1";
							$ares = pupe_query($query);

							if (mysql_num_rows($ares) == 1) {
								// lˆydettiin keskihankintahinta tapahtumista k‰ytet‰‰n
								$arow = mysql_fetch_assoc($ares);
								$kehasilloin  = $arow["hinta"];
								$bkehasilloin = $arow["hinta"];
								$kehalisa = "";
							}
							else {
								$kehalisa = "~";
							}
						}
					}
				}

				// jos summaustaso on per paikka, otetaan myynti ja kulutus vain silt‰ paikalta
				if ($summaustaso == "P") {
					$summaus_lisa = "	and tilausrivi.hyllyalue = '$row[hyllyalue]'
										and tilausrivi.hyllynro = '$row[hyllynro]'
										and tilausrivi.hyllyvali = '$row[hyllyvali]'
										and tilausrivi.hyllytaso = '$row[hyllytaso]'";
				}
				else {
					$summaus_lisa = "";
				}

				// Haetaan tuotteen myydyt kappaleet
				// Haetaan tuotteen kulutetut kappaleet
				$query  = "	SELECT
							ifnull(sum(if(tilausrivi.tyyppi='L', tilausrivi.kpl, 0)), 0) myykpl,
							ifnull(sum(if(tilausrivi.tyyppi='V', tilausrivi.kpl, 0)), 0) kulkpl,
							ifnull(date_format(max(tilausrivi.laskutettuaika), '%Y%m%d'), 0) laskutettuaika
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
													and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
													and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
													$mistavarastosta)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in ('L','V')
							and tilausrivi.tuoteno in ($row[tuoteno])
							and tilausrivi.laskutettuaika <= '$vv-$kk-$pp'
							and tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)
							$summaus_lisa";
				$xmyyres = pupe_query($query);
				$xmyyrow = mysql_fetch_assoc($xmyyres);

				// Viimeisin laskutusp‰iv‰m‰‰r‰
				$query = "	SELECT ifnull(date_format(max(laadittu), '%Y%m%d'), 0) laskutettuaika
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							WHERE yhtio  = '$kukarow[yhtio]'
							and tuoteno  in ($row[tuoteno])
							and laadittu > '{$xmyyrow['laskutettuaika']}'
							and laji 	 = 'laskutus'";
				$xmyyres = pupe_query($query);
				$xmyypvmrow = mysql_fetch_assoc($xmyyres);

				// Viimeisin kulutusp‰iv‰m‰‰r‰
				$query = "	SELECT ifnull(date_format(max(laadittu), '%Y%m%d'), 0) kulutettuaika
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							WHERE yhtio  = '$kukarow[yhtio]'
							and tuoteno  in ($row[tuoteno])
							and laadittu > '{$xmyyrow['laskutettuaika']}'
							and laji 	 = 'kulutus'";
				$xmyyres = pupe_query($query);
				$xkulpvmrow = mysql_fetch_assoc($xmyyres);

				$vikamykupaiva = max($xmyyrow['laskutettuaika'], $xmyypvmrow['laskutettuaika'], $xkulpvmrow['kulutettuaika']);

				if ($vikamykupaiva > 0)  {
					$vikamykupaiva = substr($vikamykupaiva,0,4)."-".substr($vikamykupaiva,4,2)."-".substr($vikamykupaiva,6,2);
				}
				else {
					$vikamykupaiva = "";
				}

				// lasketaan varaston kiertonopeus
				if ($muutoskpl > 0) {
					$kierto = round(($xmyyrow["myykpl"] + $xmyyrow["kulkpl"]) / $muutoskpl, 2);
				}
				else {
					$kierto = 0;
				}

				if (isset($workbook)) {

					if ($summaustaso != "T" and $summaustaso != "TRY" and $summaustaso != "TRYS") {
						$worksheet->writeString($excelrivi, $excelsarake, $row["varastonnimi"], 	$format_bold);
						$excelsarake++;
					}

					if ($summaustaso == "P") {
						$worksheet->writeString($excelrivi, $excelsarake, $row["hyllyalue"], 		$format_bold);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["hyllynro"], 		$format_bold);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["hyllyvali"], 		$format_bold);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["hyllytaso"], 		$format_bold);
						$excelsarake++;
					}

					if (isset($sel_tuotemerkki) and $sel_tuotemerkki != '') {
						$worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
						$excelsarake++;
					}

					$worksheet->writeString($excelrivi, $excelsarake, $row["osasto"]);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["try"]);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
					$tuotesarake = $excelsarake;
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["yksikko"]);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$muutoskpl));
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$kehasilloin));
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$muutoshinta));
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$bmuutoshinta));
					$excelsarake++;

					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kierto));
					$excelsarake++;

					$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($vikamykupaiva));
					$excelsarake++;

					if ($row['epakurantti25pvm'] != '0000-00-00') {
						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti25pvm']));
					}
					$excelsarake++;
					if ($row['epakurantti50pvm'] != '0000-00-00') {
						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti50pvm']));
					}
					$excelsarake++;
					if ($row['epakurantti75pvm'] != '0000-00-00') {
						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti75pvm']));
					}
					$excelsarake++;
					if ($row['epakurantti100pvm'] != '0000-00-00') {
						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti100pvm']));
					}
					$excelsarake++;

					$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row["vihapvm"]));
					$excelsarake++;

					$worksheet->writeString($excelrivi, $excelsarake, $kehalisa);

					$excelrivi++;

					// Kun otetaan tuotteittain niin ekotetaan laitteet!
					if ($summaustaso == "T" and $row["sarjanumeroseuranta"] == "S") {

						$query	= "	SELECT sarjanumeroseuranta.tunnus, sarjanumeroseuranta.era_kpl era_kpl, tilausrivi_osto.nimitys, sarjanumeroseuranta.sarjanumero
									FROM sarjanumeroseuranta
									JOIN varastopaikat ON (varastopaikat.yhtio = sarjanumeroseuranta.yhtio
															and concat(rpad(upper(alkuhyllyalue),  5, '0'), lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
															and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
															$mistavarastosta)
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON (tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus)
									LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
									WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
									and sarjanumeroseuranta.tuoteno in ($row[tuoteno])
									and sarjanumeroseuranta.myyntirivitunnus != -1
									$summaus_lisa
									and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00' or tilausrivi_myynti.laskutettuaika > '$vv-$kk-$pp')
									and tilausrivi_osto.laskutettuaika > '0000-00-00'
									and tilausrivi_osto.laskutettuaika <= '$vv-$kk-$pp'";
						$vararvores = pupe_query($query);

						while ($vararvorow = mysql_fetch_assoc($vararvores)) {

							$sarjanumeronarvo = sarjanumeron_ostohinta("tunnus", $vararvorow["tunnus"], "", "$vv-$kk-$pp 23:59:59");

							$worksheet->writeString($excelrivi, $tuotesarake, $vararvorow["sarjanumero"]);
							$worksheet->writeString($excelrivi, $tuotesarake+1, $vararvorow["nimitys"]);
							$worksheet->writeNumber($excelrivi, $tuotesarake+2, sprintf("%.02f",$sarjanumeronarvo));
							$excelrivi++;
						}
					}

					$excelsarake = 0;
				}

				if ($summaustaso == 'TRY' or $summaustaso == "TRYS") {
					$tryosind = "$row[osasto] - ".$osasto_array[$row["osasto"]]."###$row[try] - ".$try_array[$row["try"]];

					if (!isset($varastot2[$tryosind])) {
						$varastot2[$tryosind]["netto"]  = $muutoshinta;
						$varastot2[$tryosind]["brutto"] = $bmuutoshinta;
					}
					else {
						$varastot2[$tryosind]["netto"]  += $muutoshinta;
						$varastot2[$tryosind]["brutto"] += $bmuutoshinta;
					}
				}
				else {
					if (!isset($varastot2[$row["varastonnimi"]])) {
						$varastot2[$row["varastonnimi"]]["netto"]  = $muutoshinta;
						$varastot2[$row["varastonnimi"]]["brutto"] = $bmuutoshinta;
					}
					else {
						$varastot2[$row["varastonnimi"]]["netto"]  += $muutoshinta;
						$varastot2[$row["varastonnimi"]]["brutto"] += $bmuutoshinta;
					}
				}
			}
		}

		if (!$php_cli) {
			echo "<br>";
			echo "<table>";
			echo "<tr>";

			if ($summaustaso == 'TRY' or $summaustaso == "TRYS") {
				echo "<th>".t("Osasto")."</th>";
				echo "<th>".t("Ryhm‰")."</th>";
			}
			else {
				echo "<th>".t("Varasto")."</th>";
			}

			echo "<th>".t("Varastonarvo")."</th>";
			echo "<th>".t("Bruttovarastonarvo")."</th></tr>";

			ksort($varastot2);

			foreach ($varastot2 AS $varasto => $arvot) {
				echo "<tr>";

				if ($summaustaso == 'TRY' or $summaustaso == "TRYS") {
					list($osai, $tryi) = explode("###", $varasto);
					echo "<td>$osai</td>";
					echo "<td>$tryi</td>";
				}
				elseif ($summaustaso == 'T') {
					echo "<td>".t("Varastot")."</td>";
				}
				else {
					echo "<td>$varasto</td>";
				}

				foreach ($arvot AS $arvo) {
					if ($arvo != '') {
						echo "<td align='right'>".sprintf("%.2f",$arvo)."</td>";
					}
					else {
						echo "<td>&nbsp;</td>";
					}
				}
				echo "</tr>";
			}

			$cspan = 2;

			if ($summaustaso == 'TRY' or $summaustaso == "TRYS") {
				$cspan = 3;
			}

			echo "<tr><th>".t("Pvm")."</th><th colspan='$cspan'>".t("Yhteens‰")."</th></tr>";
			echo "<tr><td colspan='".($cspan-1)."'>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td>";
			echo "<td align='right'>".sprintf("%.2f",$bvarvo)."</td></tr>";
			echo "</table><br>";

			if ("$vv-$kk-$pp" != date("Y-m-d")) {
				echo "<font class='error'>",t("Huom. Bruttovarastonarvo on arvio"),"!</font><br/><br/>";
			}
		}
	}

	if (isset($workbook)) {
		$workbook->close();

		if (!$php_cli) {
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Varastonarvo.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<table>";
			echo "<tr><th>".t("Tallenna Excel-aineisto").":</th>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
			echo "</table><br>";
			echo "</form>";
		}
		else {
			$komento = 'email';

			// itse print komento...
			$liite = "/tmp/".$excelnimi;
			$kutsu = t("Varastonarvoraportti")." $vv-$kk-$pp";

			$ctype = "excel";
			$kukarow["eposti"] = $email_osoite;

			require("../inc/sahkoposti.inc");

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f /tmp/$excelnimi");
		}
	}

	if (!$php_cli) {
		require ("inc/footer.inc");
	}
?>