<?php

if (isset($_REQUEST["tee"])) {
	if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}
else {

	echo "<font class='head'>".t("Ep‰kuranttiehdotus")."</font><hr>";

	// nollataan muuttujat
	$epakuranttipvm = "";
	$chk1 = "";
	$chk2 = "";
	$chk3 = "";
	$chk4 = "";

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
	if (!isset($taysraja)) $taysraja = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
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

	echo "<form name='epakurantti' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Valitse ehdotus").":</th>";
	echo "<td colspan='2'>";
	echo "<select name='tyyppi'>";
	echo "<option $chk1 value='25'>25% ep‰kuranttiehdotus</option>";
	echo "<option $chk2 value='puoli'>Puoliep‰kuranttiehdotus</option>";
	echo "<option $chk3 value='75'>75% ep‰kuranttiehdotus</option>";
	echo "<option $chk4 value='taysi'>T‰ysep‰kuranttiehdotus</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Tuoterajaus").":</th>";
	echo "<td colspan='2'>";
	echo "<select name='tuotetyyppi'>";
	echo "<option value=''>N‰yt‰ kaikki tuotteet</option>";
	echo "<option $tchk1 value='25'>N‰yt‰ vain 25% ep‰kurantit</option>";
	echo "<option $tchk2 value='puoli'>N‰yt‰ vain puoliep‰kurantit</option>";
	echo "<option $tchk3 value='75'>N‰yt‰ vain 75% ep‰kurantit</option>";
	echo "<option $tchk4 value='taysi'>N‰yt‰ vain t‰ysep‰kurantit</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Valitse alku- ja loppup‰iv‰").":</th>";
	echo "<td><input type='text' name='alkupvm'  value='$alkupvm'></td>";
	echo "<td><input type='text' name='loppupvm' value='$loppupvm'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna ep‰kuranttiusraja (kierto)").":</th>";
	echo "<td colspan='2'><input type='text' name='raja' value='$raja'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna t‰ysep‰kuranttisuuden alaraja pvm").":</th>";
	echo "<td colspan='2'><input type='text' name='taysraja' value='$taysraja'></td><td class='back'>(Tuote on pit‰nyt laittaa edelliselle ep‰kuranttiustasolle ennen t‰t‰ p‰iv‰‰, jotta ehdotetaan seuraavaan ep‰kuranttitasoon)</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Anna osasto ja/tai tuoteryhm‰").":</th>";
	echo "<td><input type='text' name='osasto' value='$osasto'></td>";
	echo "<td><input type='text' name='try'    value='$try'></td>";
	echo "</tr>";

	echo "</table>";
	echo "<br><input type='submit' name='subnappi' value='Aja raportti'>";
	echo "</form><br><br>";

	if ($subnappi != '') {

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>".t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta.")."</font><br>";
			exit;
		}

		$lisa = "";
		$msg  = "";

		if ($osasto != '') {
			$osasto = (int) $osasto;
			$lisa  .= "and tuote.osasto='$osasto' ";
			$msg   .= ", osasto $osasto";
		}

		if ($try != '') {
			$try   = (int) $try;
			$lisa .= "and tuote.try='$try' ";
			$msg  .= ", tuoteryhm‰ $try";
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
			$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm = '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "puoli") {
			$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "75") {
			$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm = '0000-00-00'";
		}

		if ($tuotetyyppi == "taysi") {
			$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm != '0000-00-00'";
		}

		// etsit‰‰n saldolliset tuotteet
		$query  = "	SELECT tuote.tuoteno, tuote.osasto, tuote.try, tuote.myyntihinta, tuote.nimitys, tuote.tahtituote, tuote.status, tuote.hinnastoon,
					round(if(epakurantti75pvm = '0000-00-00', if(epakurantti50pvm = '0000-00-00', if(epakurantti25pvm = '0000-00-00', kehahin, kehahin * 0.75), kehahin * 0.5), kehahin * 0.25), 6) kehahin,
					tuote.vihapvm, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, tuote.tuotemerkki, tuote.myyjanro
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					AND tuote.ei_saldoa = ''
					$epakuranttipvm
					$lisa";
		$result = mysql_query($query) or pupe_error($query);

		echo t("Lˆytyi")." ".mysql_num_rows($result)." ".t("sopivaa tuotetta.. Aloitellaan laskenta.")."<br><br>";

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
		$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("saldo")), $format_bold);
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

		if ($elements > 0) {
			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar('Lasketaan...');
			$bar->initialize($elements); // print the empty bar
		}

		$excelrivi++;
		$excelsarake = 0;

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

		while ($row = mysql_fetch_assoc($result)) {

			$bar->increase();

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
			$query  = "	SELECT date_format(ifnull(min(laadittu),'1970-01-01'),'%Y-%m-%d') min, date_format(ifnull(max(laadittu),'$row[vihapvm]'),'%Y-%m-%d') max 
						FROM tapahtuma 
						WHERE yhtio = '$kukarow[yhtio]' 
						AND tuoteno = '$row[tuoteno]' 
						AND laji = 'Tulo'";
			$tapres = mysql_query($query) or pupe_error($query);
			$taprow = mysql_fetch_assoc($tapres);

			// verrataan v‰h‰n p‰iv‰m‰‰ri‰. onpa vittumaista PHP:ss‰!
			list($vv1,$kk1,$pp1) = split("-",$taprow["max"]); //$saapunut
			list($vv2,$kk2,$pp2) = split("-",$alkupvm); //$alaraja
			list($vv3,$kk3,$pp3) = split("-",$epispvm); //$epaku1pv
			list($vv4,$kk4,$pp4) = split("-",$taysraja); //$epa2raja
			$saapunut = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
			$alaraja  = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
			$epaku1pv = (int) date('Ymd',mktime(0,0,0,$kk3,$pp3,$vv3));
			$epa2raja = (int) date('Ymd',mktime(0,0,0,$kk4,$pp4,$vv4));

			// t‰t‰ tuotetta on saapunut ennen myyntirajauksen alarajaa, joten otetaan k‰sittelyyn
			if (($saapunut < $alaraja) and (($tyyppi != '25' and $epaku1pv < $epa2raja) or ($tyyppi == '25'))) {

				$query = "	SELECT sum(saldo) saldo 
							FROM tuotepaikat 
							WHERE tuotepaikat.yhtio = '$kukarow[yhtio]' 
							and tuotepaikat.tuoteno = '$row[tuoteno]'";
				$saldores = mysql_query($query) or pupe_error($query);
				$saldorow = mysql_fetch_assoc($saldores);

				if ($saldorow['saldo'] == 0) {
					continue;
				}

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

				// lasketaan saldo
				$saldo = $saldorow["saldo"] - $ennrow["ennpois"];

				// lasketaan varaston kiertonopeus
				if ($saldo > 0) {
					$kierto = round(($myyrow["kpl"] + $kulrow["kpl"]) / $saldo, 2);
				}
				else {
					$kierto = 0;
				}

				// typecast
				$raja = (float) str_replace(",",".",$raja);

				if ($yhteensopivuus_table_check) {
					$query = "	SELECT count(yhteensopivuus_rekisteri.tunnus)
								FROM yhteensopivuus_tuote, yhteensopivuus_rekisteri
								WHERE yhteensopivuus_tuote.yhtio = yhteensopivuus_rekisteri.yhtio
								AND yhteensopivuus_tuote.atunnus = yhteensopivuus_rekisteri.autoid
								AND yhteensopivuus_tuote.yhtio = '$kukarow[yhtio]'
								AND yhteensopivuus_tuote.tuoteno = '$row[tuoteno]'";
					$yhteensopivuus_res = mysql_query($query) or pupe_error($query);
					$yhteensopivuus_row = mysql_fetch_assoc($yhteensopivuus_res);
				}

				// katellaan ollaanko alle rajan
				if ($kierto < $raja) {
					$worksheet->write($excelrivi, $excelsarake, $row['osasto']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['try']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['tuotemerkki']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, str_replace(".",",",$myyrow['kpl']+$kulrow['kpl']));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, str_replace(".",",",$saldo));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, str_replace(".",",",$kierto));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['tahtituote']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['status']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['hinnastoon']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $taprow['min']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $taprow['max']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, str_replace(".",",",$row['myyntihinta']));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, str_replace(".",",",$row['kehahin']));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $row['tuoteno']);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $toimittajarow['toimittaja']);
					$excelsarake++;

					if ($row['myyjanro'] > 0) {
						$worksheet->write($excelrivi, $excelsarake, $myyja_array[$row['myyjanro']]);
					}
					else {
						$worksheet->write($excelrivi, $excelsarake, '');
					}
					$excelsarake++;

					if ($yhteensopivuus_row[0] != 0) {
						$worksheet->write($excelrivi, $excelsarake, $yhteensopivuus_row[0]);
					}

					$excelsarake = 0;
					$excelrivi++;
				}
			} // end saapunut ennen alarajaa
		}

		$workbook->close();

		echo "<br/><br/>";
		echo "<table>";
		echo "<tr><th>".t("Tallenna raportti (xls)").":</th>";
		echo "<form method='post' action='$PHP_SELF'>";
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
