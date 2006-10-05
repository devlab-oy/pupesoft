<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require('../inc/parametrit.inc');

echo "<font class='head'>".t("Asiakkaan ostot toimittajittain")."</font><hr>";

$debug = 0;

if ($tee == "" or $tee == "JATKA") {
	if ($debug == 1) {
		echo "debug1 tee = $tee | asiakasid = $asiakasid | asiakasno = $asiakasno | toimittajaid = $toimittajaid | ytunnus = $ytunnus | toimittaja = $toimi<br>";
	}
	
	if (isset($muutparametrit)) {
		list($vva,$kka,$ppa,$vvl,$kkl,$ppl,$ytunnus,$asiakasno,$toimi) = explode('#', $muutparametrit);
	}

	$muutparametrit = $vva."#".$kka."#".$ppa."#".$vvl."#".$kkl."#".$ppl."#".$ytunnus."#".$asiakasno."#".$toimi."#";

	if ($vva != '' and $kka != '' and $ppa != '' and $vvl != '' and $kkl != '' and $ppl != '' and $ytunnus != '' and $asiakasno != '') {
		if ($ytunnus != '' and !isset($ylatila)) {			

			require("../inc/kevyt_toimittajahaku.inc");

			if ($ytunnus != '') {
				$tee = "JATKA";	
			}
		}
		elseif($ytunnus != '' and isset($ylatila)) {
			$tee = "JATKA";
		}
		else {
			$tee = "";
		}
		$toimi = $ytunnus;
	}
	elseif ($tee != '') {
		echo "<font class='error'>".t("Jonkun kentän tiedot puuttuu, ei voida ajaa raporttia")."</font><br>";
		$tee = "";
	}
	
	if ($debug == 1) {
		echo "debug2 tee = $tee | asiakasid = $asiakasid | asiakasno = $asiakasno | toimittajaid = $toimittajaid | ytunnus = $ytunnus | toimittaja = $toimi<br>";
	}

	$muutparametrit = $vva."#".$kka."#".$ppa."#".$vvl."#".$kkl."#".$ppl."#".$ytunnus."#".$asiakasno."#".$toimi."#";

	if ($asiakasno != '' and $tee == "JATKA" and $toimittajaid != '') {
		$muutparametrit .= $ytunnus;

		if ($asiakasid == "") {
			$ytunnus = $asiakasno;
		}

		require("../inc/asiakashaku.inc");

		if ($ytunnus != '') {
			$tee = "JATKA";
			$asiakasno = $ytunnus;
			$ytunnus = $toimi;	
		}
		else {
			$asiakasno = $ytunnus;
			$ytunnus = $toimi;

			$tee = "";
		}
	}
	if ($debug == 1) {
		echo "debug3 tee = $tee | asiakasid = $asiakasid | asiakasno = $asiakasno | toimittajaid = $toimittajaid | ytunnus = $ytunnus | toimittaja = $toimi<br>";
	}
}

if ($debug == 1) {
	echo "debug4 tee = $tee | asiakasid = $asiakasid | asiakasno = $asiakasno | toimittajaid = $toimittajaid | ytunnus = $ytunnus | toimittaja = $toimi<br>";
}


if ($tee != '' and $asiakasid != "" and $toimittajaid != "" and $ytunnus != '' and $toimi != '') {
		
		$query = "	SELECT b.tuoteno, b.nimitys, sum(a.kpl) kpl, round(sum(a.rivihinta*((a.alv/100)+1)),2) summa, round(sum(a.rivihinta),2) summa2
					FROM tilausrivi a,
					tuote b,
					tuotteen_toimittajat d,
					lasku c
					WHERE a.yhtio = '$kukarow[yhtio]' and c.tapvm >= '$vva-$kka-$ppa'
					and c.tapvm <= '$vvl-$kkl-$ppl'
					and c.tila = 'U'
					and c.alatila = 'X'
					and a.yhtio = b.yhtio
					and a.yhtio = c.yhtio
					and a.yhtio = d.yhtio
					and c.tunnus = a.uusiotunnus
					and a.tuoteno = b.tuoteno
					and a.tuoteno = d.tuoteno
					and c.liitostunnus = '$asiakasid'
					and d.liitostunnus = '$toimittajaid'
					GROUP BY 1
					ORDER BY 1";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0 ) {

			echo "<table>
					<tr>
					<th>".t("Asiakas").":</th>
					<th>$asiakasno</th>
					<th>".t("Toimittaja").":</th>
					<th>$toimi</th>
					</tr>
					<tr>
					<th>".t("Tuotenumero")."</th>
					<th>".t("Nimitys")."</th>
					<th>".t("KPL")."</th>
					<th>".t("Summa veroll")."</th>
					<th>".t("Summa vton")."</th>
					</tr>";

			while ($row = mysql_fetch_array($result)) {

				echo "<tr><td>$row[tuoteno]</td><td>$row[nimitys]</td><td>$row[kpl]</td><td>$row[summa]</td><td>$row[summa2]</td></tr>";
			}
			echo "</table>";
		}
		else {
			echo "<br>".t("EI OSUMIA, TARKISTA SYÖTETYT RAJAUKSET")."!<br>";
		}
}

//Käyttäliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<input type='hidden' name='tee' value='JATKA'>
			<input type='hidden' name='toimittajaid' value='$toimittajaid'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>";
	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3'>
			<input type='text' name='vva' value='$vva' size='5'></td></tr>
			<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'>
			<input type='text' name='kkl' value='$kkl' size='3'>
			<input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
			if ($tee == '' and $toimittajaid == '') {
				echo "<tr><th>".t("Anna asiakas")."</th>
					<td><input type='text' name='asiakasno' value='$asiakasno' size='20'></td></tr>
					<tr><th>".t("Anna toimittaja")."</th>
					<td><input type='text' name='ytunnus' value='$toimi' size='20'></td></tr>";
			}
			else {
				echo "<tr><th>".t("Asiakas")."</th>
					<td>$asiakasno</td></tr>
					<tr><th>".t("Toimittaja")."</th>
					<td>$toimi</td></tr>
					<input type='hidden' name='asiakasno' value='$asiakasno'>
					<input type='hidden' name='ytunnus' value='$toimi'>";
			}

	echo "<tr><td class='back'><input type='submit' value='".t("Hae")."'></td></tr></table>";

require ("../inc/footer.inc");

?>