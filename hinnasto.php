<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST['filenimi']) and $_POST['filenimi'] != '') {
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=".$_POST['kaunisnimi'].".zip");
	header("Content-Description: File Transfer");

	$filenimi = '/tmp/' . basename($_POST['filenimi']);
	readfile($filenimi);

    // tuhotaan edellinen
	unlink('/tmp/' . basename($filenimi));
	exit;
}

if (@include("inc/parametrit.inc"));
elseif (@include("parametrit.inc"));
else exit;

echo "<font class='head'>".t("Hinnastoajo")."</font><hr>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='kaikki'>";

// M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");

if (file_exists("tilauskasittely/monivalintalaatikot.inc")) {
	require("tilauskasittely/monivalintalaatikot.inc");
}
else {
	require("monivalintalaatikot.inc");
}

echo "<br><br>";
echo "<table>";

if ($kukarow['extranet'] == '') {
	echo "<tr><th>".t("Listaa kaikki tuotteet").":</th>
			<td><input type='checkbox' name='kl_hinnastoon'> (".t("muuten hinnastoon fl‰gi eri kuin E ja V").")</td></tr>";


	echo "<tr><th>".t("N‰yt‰ aleryhm‰n tunnus").":</th>
			<td><input type='checkbox' name='kl_alenimi'> (".t("muuten n‰ytet‰‰n aleryhm‰n nimi").")</td></tr>";
}


$sel[$hinnasto] = "SELECTED";

echo "<tr>
	<th>" .t('Muutosp‰iv‰m‰‰r‰') . "</th>
	<td>
		<input type='text' name='pp' value='$pp' size='3'>
		<input type='text' name='kk' value='$kk' size='3'>
		<input type='text' name='vv' value='$vv' size='5'>
		" . t('ppkkvvvv') . "
	</td>
	</tr>
	<tr>
	<th>" . t('Hinnastoformaatti') . "</th>
	<td>
		<select name='hinnasto'>
			<option value='futur' $sel[futur]>" . t('Futursoft') . "</option>
			<option value='automaster' $sel[automaster]>" . t('Automaster') . "</option>
			<option value='vienti' $sel[vienti]>" . t('Vientihinnasto') . "</option>
			<option value='tab' $sel[tab]>" . t('Tab eroteltu') . "</option>
		</select>
	</td>
	</tr>
</table>";

echo "<br>";
echo "<input type='submit' name='submitnappi' value='".t("L‰het‰")."'>";
echo "</form>";

// jos ollaan painettu submittia, tehd‰‰n rappa
if (isset($submitnappi)) {

	// kirjoitetaan tmp file
	$filenimi = t("hinnasto")."-".date("ymdHis").".txt";

	if (!$fh = fopen("/tmp/" . $filenimi, "w+")) {
		die("filen luonti ep‰onnistui!");
	}

	echo "<br><br><font class='message'>".t("Luodaan hinnastoa")."...</font>";

	// katsotaan mik‰ hinnastoformaatti
	if (file_exists("inc/hinnastorivi".basename($_POST["hinnasto"]).".inc")) {
		require("inc/hinnastorivi".basename($_POST["hinnasto"]).".inc");
	}
	else {
		require("hinnastorivi".basename($_POST["hinnasto"]).".inc");
	}

	if (file_exists("inc/ProgressBar.class.php")) {
		require("inc/ProgressBar.class.php");
	}
	else {
		require("ProgressBar.class.php");
	}

	$bar = new ProgressBar();

	if (isset($_POST['pp']) and isset($_POST['kk']) and isset($_POST['vv'])) {
		if (strlen(trim($_POST['vv'])) > 0 and strlen(trim($_POST['kk'])) > 0 and strlen(trim($_POST['pp'])) > 0) {
			$pvm = mysql_real_escape_string("$_POST[vv]-$_POST[kk]-$_POST[pp]");
			$lisa .= " and tuote.muutospvm >= '" . $pvm . "' ";
		}
	}

	// jos ei olla extranetissa niin otetaan valuuttatiedot yhtiolta
	// maa, valkoodi, ytunnus
	if (empty($kukarow['extranet'])) {
		$laskurowfake = array(
			'valkoodi' => $yhtiorow['valkoodi'],
			'maa'      => $yhtiorow['maa'],
			'ytunnus'  => $yhtiorow['ytunnus'],
		);
	}
	else {
		// otetaan valuuttatiedot oletus asiakkaalta
		$query = "SELECT maa, valkoodi, ytunnus from asiakas where tunnus='$kukarow[oletus_asiakas]' and yhtio ='$kukarow[yhtio]'";
		$res = pupe_query($query);

		// k‰ytet‰‰n t‰t‰ laskurowna
		$laskurowfake = mysql_fetch_assoc($res);
	}

	$query = "SELECT kurssi from valuu where nimi = '$laskurowfake[valkoodi]' and yhtio = '$kukarow[yhtio]'";
	$res = pupe_query($query);
	$kurssi = mysql_fetch_array($res);

	// asetetaan vienti kurssi
	$laskurowfake['vienti_kurssi'] = $kurssi['kurssi'];

	if ($kukarow['extranet'] == '') {
		if ($kl_hinnastoon != "") {
			$kl_lisa = " ";
		}
		else {
			$kl_lisa = " and tuote.hinnastoon not in ('v', 'e') ";
		}
	}
	else {
		$kl_lisa = " and tuote.hinnastoon != 'E' ";
	}

	$query = "	SELECT tuote.*, korvaavat.id
				FROM tuote
				LEFT JOIN korvaavat use index (yhtio_tuoteno) ON (tuote.tuoteno = korvaavat.tuoteno and tuote.yhtio = korvaavat.yhtio)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				$lisa
				$kl_lisa
				and ((tuote.vienti = '' or tuote.vienti like '%-$laskurowfake[maa]%' or tuote.vienti like '%+%')
				and tuote.vienti not like '%+$laskurowfake[maa]%')
				and tuote.tuotetyyppi NOT IN ('A', 'B')
				and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
				ORDER BY tuote.osasto+0, tuote.try+0";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<br><br><font class='message'>".t('Yht‰‰n tuotetta ei lˆytynyt hinnastoon.') . '</font><br />';
		exit;
	}

	$elements = mysql_num_rows($result); // total number of elements to process
	$bar->initialize($elements); // print the empty bar

	if ($kukarow["extranet"] != "" and function_exists("hinnastoriviotsikot")) {
		$ulos = hinnastoriviotsikot();
		fwrite($fh, $ulos);
	}

	while ($tuoterow = mysql_fetch_array($result)) {

		$ohitus = 0;

		if (!empty($kukarow['extranet'])) {
			$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
			$asiakastempres = mysql_query($query);
			$asiakastemprow = mysql_fetch_array($asiakastempres);

			$temp_laskurowwi = array();
			$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
			$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
			$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
			$temp_laskurowwi['maa']				= $asiakastemprow['maa'];

			$hinnat = alehinta($temp_laskurowwi, $tuoterow, 1, '', '', '', "hintaperuste,aleperuste");

			$onko_asiakkaalla_alennuksia = FALSE;

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
					$onko_asiakkaalla_alennuksia = TRUE;
					break;
				}
			}

			// Jos tuote n‰ytet‰‰n vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei lˆydy
			if ($tuoterow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
				$ohitus = 1;
			}
		}

		// tehd‰‰n yksi rivi
		if ($ohitus == 0) {
			$ulos = hinnastorivi($tuoterow, $laskurowfake);
			fwrite($fh, $ulos);
		}

		$bar->increase(); //calls the bar with every processed element
	}

	fclose($fh);

	// pakataan faili
	$cmd = "/usr/bin/zip -j /tmp/$kukarow[yhtio].$kukarow[kuka].zip /tmp/$filenimi";
	$palautus = exec($cmd);

    // poistetaan tmp file
	unlink('/tmp/' . $filenimi);

	$filenimi = "/tmp/$kukarow[yhtio].$kukarow[kuka].zip";

	echo "<br><br>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tallenna hinnasto tiedostoon")."</th>";
	echo "<td>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='filenimi' value='$filenimi'>";
	echo "<input type='hidden' name='kaunisnimi' value='".t("hinnasto")."'>";
	echo "<input type='submit' value='".t("Tallenna")."'>";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

if (@include("inc/footer.inc"));
elseif (@include("footer.inc"));
else exit;

?>