<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_REQUEST["tee"])) {
	if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}
else {

	echo "<font class='head'>".t("Ep‰kuranttiehdotus")."</font><hr>";

	// nollataan muuttujat
	$epakuranttipvm = "";
	$tuote_epa_rajaus = "";
	$chk1 = "";
	$chk2 = "";
	$chk3 = "";
	$chk4 = "";

	if (!isset($tyyppi)) $tyyppi  = "";
	if (!isset($tuotetyyppi)) $tuotetyyppi  = "";

	if ($tyyppi == '25') $chk1 = "selected";
	if ($tyyppi == 'puoli') $chk2 = "selected";
	if ($tyyppi == '75') $chk3 = "selected";
	if ($tyyppi == 'taysi') $chk4 = "selected";

	if ($tuotetyyppi == '25') $tchk1 = "selected";
	if ($tuotetyyppi == 'puoli') $tchk2 = "selected";
	if ($tuotetyyppi == '75') $tchk3 = "selected";
	if ($tuotetyyppi == 'taysi') $tchk4 = "selected";

	// defaultteja
	if (!isset($alkupvm))  $alkupvm  = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
	if (!isset($loppupvm)) $loppupvm = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
	if (!isset($taysraja)) $taysraja = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
	if (!isset($raja))     $raja = "0.5";

	// errorcheckej‰
	if (!checkdate(substr($alkupvm,5,2), substr($alkupvm,8,2), substr($alkupvm,0,4))) {
		echo "<font class='error'>".t("Virheellinen p‰iv‰m‰‰r‰")." $alkupvm!</font><br><br>";
		unset($subnappi);
	}

	if (!checkdate(substr($loppupvm,5,2), substr($loppupvm,8,2), substr($loppupvm,0,4))) {
		echo "<font class='error'>".t("Virheellinen p‰iv‰m‰‰r‰")." $loppupvm!</font><br><br>";
		unset($subnappi);
	}

	if (!checkdate(substr($taysraja,5,2), substr($taysraja,8,2), substr($taysraja,0,4))) {
		echo "<font class='error'>".t("Virheellinen p‰iv‰m‰‰r‰")." $taysraja!</font><br><br>";
		unset($subnappi);
	}

	if ($tyyppi == "25" and $tuotetyyppi != "") {
		echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
		unset($subnappi);
	}
	elseif ($tyyppi == "puoli" and ($tuotetyyppi == "puoli" or $tuotetyyppi == "75" or $tuotetyyppi == "taysi")) {
		echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
		unset($subnappi);
	}
	elseif ($tyyppi == "75" and ($tuotetyyppi == "75" or $tuotetyyppi == "taysi")) {
		echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
		unset($subnappi);
	}

	echo "<form name='epakurantti' method='post' autocomplete='off'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Valitse ehdotus").":</th>";
	echo "<td>";
	echo "<select name='tyyppi'>";
	echo "<option $chk1 value='25'>25% ".t("ep‰kuranttiehdotus")."</option>";
	echo "<option $chk2 value='puoli'>".t("Puoliep‰kuranttiehdotus")."</option>";
	echo "<option $chk3 value='75'>75% ".t("ep‰kuranttiehdotus")."</option>";
	echo "<option $chk4 value='taysi'>".t("T‰ysep‰kuranttiehdotus")."</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Tuoterajaus").":</th>";
	echo "<td>";
	echo "<select name='tuotetyyppi'>";
	echo "<option value=''>".t("N‰yt‰ kaikki tuotteet")."</option>";
	echo "<option $tchk1 value='25'>".t("N‰yt‰ vain 25% ep‰kurantit")."</option>";
	echo "<option $tchk2 value='puoli'>".t("N‰yt‰ vain puoliep‰kurantit")."</option>";
	echo "<option $tchk3 value='75'>".t("N‰yt‰ vain 75% ep‰kurantit")."</option>";
	echo "<option $tchk4 value='taysi'>".t("N‰yt‰ vain t‰ysep‰kurantit")."</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Valitse alku- ja loppup‰iv‰").":</th>";
	echo "<td><input type='text' name='alkupvm'  value='$alkupvm'> - <input type='text' name='loppupvm' value='$loppupvm'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna ep‰kuranttiusraja (kierto)").":</th>";
	echo "<td><input type='text' name='raja' value='$raja'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna ep‰kuranttitason alaraja pvm").":</th>";
	echo "<td><input type='text' name='taysraja' value='$taysraja'><br>".t("Tuote on pit‰nyt laittaa edelliselle ep‰kuranttiustasolle ennen t‰t‰ p‰iv‰‰, jotta ehdotetaan seuraavaan ep‰kuranttitasoon")."<br>".t("Rajaus ei koske 25% ep‰kuranttiehdotusta")."</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna osasto ja/tai tuoteryhm‰").":</th>";
	echo "<td nowrap>";

	$monivalintalaatikot = array("OSASTO", "TRY");
	$monivalintalaatikot_normaali = array();

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo "<br><input type='submit' name='subnappi' value='".t("Aja raportti")."'>";
	echo "</form><br><br>";

	if (isset($subnappi) and $subnappi != '') {

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>".t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta.")."</font><br>";
			exit;
		}

		$msg  = "";

		if (isset($lisa) and $lisa != '') {
			if (count($mul_osasto) > 0) {
				$msg .= " ".t("Osasto").": ";

				foreach ($mul_osasto as $osasto) {
					$msg .= "$osasto, ";
				}

				$msg = substr($msg, 0, -2);
			}

			if (count($mul_try) > 0) {
				$msg .= " ".t("Tuoteryhm‰").": ";

				foreach ($mul_try as $try) {
					$msg .= "$try, ";
				}

				$msg = substr($msg, 0, -2);
			}
		}

		if ($tyyppi == '25') {
			// 25ep‰kurantteja etsitt‰ess‰ tuote ei saa olla puoli eik‰ t‰ysiep‰kurantti
			$epakuranttipvm = "and epakurantti25pvm='0000-00-00' and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
			echo "<font class='message'>".t("25% ep‰kuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
		}

		if ($tyyppi == 'puoli') {
			// puoliep‰kurantteja etsitt‰ess‰ tuote ei saa olla puoli eik‰ t‰ysiep‰kurantti
			$epakuranttipvm = "and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
			echo "<font class='message'>".t("Puoliep‰kuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
		}

		if ($tyyppi == '75') {
			// 75ep‰kurantteja etsitt‰ess‰ tuote ei saa olla puoli eik‰ t‰ysiep‰kurantti
			$epakuranttipvm = "and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
			echo "<font class='message'>".t("75% ep‰kuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
		}

		if ($tyyppi == 'taysi') {
			// t‰ysiep‰kurantteja etsitt‰ess‰ tuotteen pit‰‰ olla puoliep‰kurantti mutta ei t‰ysep‰kurantti
			$epakuranttipvm = "and epakurantti100pvm='0000-00-00'";
			echo "<font class='message'>".t("T‰ysiep‰kuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
		}

		if ($tuotetyyppi == "25") {
			$tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm = '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "puoli") {
			$tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "75") {
			$tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "taysi") {
			$tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm != '0000-00-00'";
		}

		// etsit‰‰n saldolliset tuotteet
		$query  = "	SELECT tuote.tuoteno,
					tuote.osasto,
					tuote.try,
					tuote.myyntihinta,
					tuote.nimitys,
					tuote.tahtituote,
					tuote.status,
					tuote.hinnastoon,
					round(if(epakurantti75pvm = '0000-00-00', if(epakurantti50pvm = '0000-00-00', if(epakurantti25pvm = '0000-00-00', kehahin, kehahin * 0.75), kehahin * 0.5), kehahin * 0.25), 6) kehahin,
					tuote.vihapvm,
					epakurantti25pvm,
					epakurantti50pvm,
					epakurantti75pvm,
					tuote.tuotemerkki,
					tuote.myyjanro,
					sum(saldo) saldo
					FROM tuote
					JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					AND tuote.ei_saldoa = ''
					AND tuote.tuotetyyppi NOT IN ('A', 'B')
					$epakuranttipvm
					$tuote_epa_rajaus
					$lisa
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
					HAVING saldo != 0
					ORDER BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		echo "<br><font class='message'>".t("Lˆytyi")." ".mysql_num_rows($result)." ".t("sopivaa tuotetta. Lasketaan ehdotus.")."</font><br>";

		flush();

		$yhteensopivuus_table_check = table_exists("yhteensopivuus_tuote");

		$elements = mysql_num_rows($result); // total number of elements to process

		//keksit‰‰n failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi 	 = 0;
		$excelsarake = 0;

		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("osasto")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("try")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuotemerkki")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("m‰‰r‰")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("myyt‰viss‰")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("kierto")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tahtituote")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("status")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("hinnastoon")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("eka saapuminen")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("vika saapuminen")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("hinta")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("kehahin")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("nimitys")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("toimittaja")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("myyja")), $format_bold);
		$excelsarake++;

		if ($yhteensopivuus_table_check) {
			$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("yhteensopivuus")), $format_bold);
			$excelsarake++;
		}

		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("ep‰kurantti 25 pvm")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("ep‰kurantti 50 pvm")), $format_bold);
		$excelsarake++;
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("ep‰kurantti 75 pvm")), $format_bold);
		$excelsarake++;

		if ($elements > 0) {
			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar();
			$bar->initialize($elements); // print the empty bar
		}

		$excelrivi++;
		$excelsarake = 0;
		$laskuri 	 = 0;

		$myyja_array = array();

		$query = "	SELECT myyja, nimi
					FROM kuka
					WHERE yhtio = '$kukarow[yhtio]'
					AND myyja > 0
					AND extranet = ''";
		$myyjares = mysql_query($query) or pupe_error($query);

		while ($myyjarow = mysql_fetch_assoc($myyjares)) {
			$myyja_array[$myyjarow['myyja']] = $myyjarow['nimi'];
		}

		list($vv2,$kk2,$pp2) = explode("-", $alkupvm);	// $alaraja (myyntirajauksen alku pvm)
		list($vv4,$kk4,$pp4) = explode("-", $taysraja);	// $epa2raja (pvm jolloin tuote on laitettu edelliselle ep‰kurtasolle)

		$alaraja  = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
		$epa2raja = (int) date('Ymd',mktime(0,0,0,$kk4,$pp4,$vv4));

		while ($row = mysql_fetch_assoc($result)) {

			$bar->increase();

			$epispvm = "0000-00-00";

			if ($row["epakurantti75pvm"] != "0000-00-00") {
				$epispvm = $row["epakurantti75pvm"];
			}
			elseif ($row["epakurantti50pvm"] != "0000-00-00") {
				$epispvm = $row["epakurantti50pvm"];
			}
			elseif ($row["epakurantti25pvm"] != "0000-00-00") {
				$epispvm = $row["epakurantti25pvm"];
			}

			// jos meill‰ on tuotteen vihapvm k‰ytet‰‰n sit‰, muuten eka from 70s...
			if ($row["vihapvm"] == "0000-00-00") $row["vihapvm"] = '1970-01-01';

			// haetaan eka ja vika saapumisp‰iv‰
			$query  = "	SELECT
						date_format(ifnull(min(laadittu),'1970-01-01'),'%Y-%m-%d') min,
						date_format(ifnull(max(laadittu),'1970-01-01'),'%Y-%m-%d') max
						FROM tapahtuma
						WHERE yhtio = '$kukarow[yhtio]'
						AND tuoteno = '$row[tuoteno]'
						AND laji in ('tulo', 'valmistus')";
			$tapres = mysql_query($query) or pupe_error($query);
			$taprow = mysql_fetch_assoc($tapres);

			// verrataan v‰h‰n p‰iv‰m‰‰ri‰. onpa vittumaista PHP:ss‰!
			list($vv1,$kk1,$pp1) = explode("-", $taprow["max"]);	// $saapunut (viimeisen tulon pvm)
			$saapunut = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));

			list($vv3,$kk3,$pp3) = explode("-", $epispvm);			// $epaku1pv (viimeisin ep‰kurantti pvm)
			$epaku1pv = (int) date('Ymd',mktime(0,0,0,$kk3,$pp3,$vv3));

			// Jos tuotetta on tullut myyntirajauksen aikana, ei ehdota sit‰ ep‰kurantiksi.
			// Lis‰ksi jos kyseess‰ on joku muu kuin 25% ep‰kuranttiajo, pit‰‰ viimeisin ep‰kuranttipvm olla pienempi kuin t‰ysep‰kuranttisuuden alaraja pvm
 			if (($saapunut < $alaraja) and ($epaku1pv < $epa2raja or $tyyppi == '25')) {

				$query = "	SELECT group_concat(distinct tuotteen_toimittajat.toimittaja separator '/') toimittaja
							FROM tuotteen_toimittajat
							WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
							and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'";
				$toimittajares = mysql_query($query) or pupe_error($query);
				$toimittajarow = mysql_fetch_assoc($toimittajares);

				// haetaan tuotteen myydyt kappaleet
				$query  = "	SELECT ifnull(sum(kpl),0) kpl
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'L'
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika >= '$alkupvm'
							and laskutettuaika <= '$loppupvm'";
				$myyres = mysql_query($query) or pupe_error($query);
				$myyrow = mysql_fetch_assoc($myyres);

				// haetaan tuotteen kulutetut kappaleet
				$query  = "	SELECT ifnull(sum(kpl),0) kpl
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'V'
							and tuoteno = '$row[tuoteno]'
							and toimitettuaika >= '$alkupvm'
							and toimitettuaika <= '$loppupvm'";
				$kulres = mysql_query($query) or pupe_error($query);
				$kulrow = mysql_fetch_assoc($kulres);

				// haetaan tuotteen ennakkopoistot
				$query  = "	SELECT ifnull(sum(varattu),0) ennpois
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and tyyppi = 'L'
							and varattu <> 0";
				$ennres = mysql_query($query) or pupe_error($query);
				$ennrow = mysql_fetch_assoc($ennres);

				// lasketaan saldo (myyt‰viss‰)
				$saldo = $row["saldo"] - $ennrow["ennpois"];

				// lasketaan varaston kiertonopeus
				if ($saldo > 0) {
					$kierto = round(($myyrow["kpl"] + $kulrow["kpl"]) / $saldo, 2);
				}
				else {
					$kierto = 0;
				}

				// typecast
				$raja = (float) str_replace(",",".", $raja);

				if ($yhteensopivuus_table_check) {
					$query = "	SELECT count(yhteensopivuus_rekisteri.tunnus) maara
								FROM yhteensopivuus_tuote, yhteensopivuus_rekisteri
								WHERE yhteensopivuus_tuote.yhtio = yhteensopivuus_rekisteri.yhtio
								AND yhteensopivuus_tuote.atunnus = yhteensopivuus_rekisteri.autoid
								AND yhteensopivuus_tuote.yhtio = '$kukarow[yhtio]'
								AND yhteensopivuus_tuote.tuoteno = '$row[tuoteno]'";
					$yhteensopivuus_res = mysql_query($query) or pupe_error($query);
					$yhteensopivuus_row = mysql_fetch_assoc($yhteensopivuus_res);
				}

				// katellaan ollaanko alle rajan
				if ($kierto < $raja or ($raja == 0 and $kierto <= 0)) {

					$laskuri++;

					$worksheet->writeString($excelrivi, $excelsarake, $row['osasto']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['try']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['tuotemerkki']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$myyrow['kpl']+$kulrow['kpl']));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$saldo));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$kierto));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['tahtituote']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['status']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['hinnastoon']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $taprow['min']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $taprow['max']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$row['myyntihinta']));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$row['kehahin']));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $toimittajarow['toimittaja']);
					$excelsarake++;

					if ($row['myyjanro'] > 0 and isset($myyja_array[$row['myyjanro']])) {
						$worksheet->writeString($excelrivi, $excelsarake, $myyja_array[$row['myyjanro']]);
						$excelsarake++;
					}
					else {
						$worksheet->writeString($excelrivi, $excelsarake, '');
						$excelsarake++;
					}

					if ($yhteensopivuus_table_check) {
						$worksheet->writeString($excelrivi, $excelsarake, $yhteensopivuus_row["maara"]);
						$excelsarake++;
					}

					if ($row['epakurantti25pvm'] == '0000-00-00') $row['epakurantti25pvm'] = "";
					if ($row['epakurantti50pvm'] == '0000-00-00') $row['epakurantti50pvm'] = "";
					if ($row['epakurantti75pvm'] == '0000-00-00') $row['epakurantti75pvm'] = "";

					$worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti25pvm']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti50pvm']);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti75pvm']);
					$excelsarake++;

					$excelsarake = 0;
					$excelrivi++;
				}
			} // end saapunut ennen alarajaa
		}

		$workbook->close();

		echo "<br/>";
		echo "<font class='message'>".t("Ehdotuksessa %s tuotetta.", "", $laskuri)."</font>";
		echo "<br/>";
		echo "<br/>";

		echo "<table>";
		echo "<tr><th>".t("Tallenna raportti (xls)").":</th>";
		echo "<form method='post' class='multisubmit'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Epakuranttiraportti.xls'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
		echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";

	}

	// kursorinohjausta
	$formi  = "epakurantti";
	$kentta = "osasto";

	require ("../inc/footer.inc");
}
