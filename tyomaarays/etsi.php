<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	include('../inc/parametrit.inc');

	echo "<font class='head'>".t("Etsi työmääräys").":</font><hr><br>";

	if ($tee == 'etsi') {
		echo "<table>";

		if ($vva != '' and $kka != '' and $ppa != '' and $vvl != '' and $kkl != '' and $ppl != ''){
			$muu1 = "lasku.luontiaika >= '$vva-$kka-$ppa' and lasku.luontiaika <= ";
			$muu2 = "$vvl-$kkl-$ppl";
		}

		if ($nimi != '') {
			$muu1 = "lasku.nimi LIKE ";
			$muu2 = "%".$nimi."%";
		}

		if ($rekno != '') {
			$muu1 = "tyomaarays.rekno LIKE ";
			$muu2 = "%".$rekno."%";
		}

		if ($eid != '') {
			$muu1 = "lasku.tunnus = ";
			$muu2 = $eid;
		}

		if ($asno != '') {
			$muu1 = "asiakas.asiakasnro = ";
			$muu2 = $asno;
		}

		if ($valmno != '') {
			$muu1 = "tyomaarays.valmnro LIKE ";
			$muu2 = "%".$valmno."%";
		}

		$squery = "	SELECT lasku.*, tyomaarays.*, lasku.tunnus laskutunnus
					FROM lasku
					JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
					JOIN asiakas ON asiakas.yhtio = tyomaarays.yhtio and asiakas.tunnus = lasku.liitostunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila in ('A','L','N')
					and lasku.tilaustyyppi = 'A'
					and $muu1 '$muu2'
					ORDER BY lasku.tunnus desc";
		$sresult = mysql_query($squery) or pupe_error($query);

		if (mysql_num_rows($sresult) > 0) {
			echo "<tr>
					<th>".t("Työmääräys").":</th>
					<th>".t("Nimi").":</th>
					<th>".t("Rekno").":</th>
					<th>".t("Päivämäärä").":</th>
					<th>".t("Työn kuvaus / Toimenpiteet").":</th>
					<th>".t("Muokkaa").":</th>
					<th>".t("Tulosta").":</th>
				 </tr>";

			while ($row = mysql_fetch_array($sresult)) {

				echo "<tr>
						<td valign='top'>$row[laskutunnus]</td>
						<td valign='top'>$row[nimi]</td>
						<td valign='top'>$row[rekno]</td>
						<td valign='top'>".tv1dateconv(substr($row["luontiaika"],0,10))."</td>
						<td>".str_replace("\n", "<br>", $row["komm1"])."".str_replace("\n", "<br>", $row["komm2"])."</td>";

				if ($row["alatila"] == '' or $row["alatila"] == 'A' or $row["alatila"] == 'B' or $row["alatila"] == 'C' or $row["alatila"] == 'J') {
					echo "<td valign='top'>
							<form method='post' action='../tilauskasittely/tilaus_myynti.php'>
							<input type='hidden' name='toim' value='TYOMAARAYS'>
							<input type='hidden' name='tilausnumero' value='$row[laskutunnus]'>
							<input type='submit' value = '".t("Muokkaa")."'></form></td>";
				}
				else {
					echo "<td></td>";
				}

				echo "<td valign='top'><form action = '../tilauskasittely/tulostakopio.php' method='post'>
						<input type='hidden' name='tee' value = 'ETSILASKU'>
						<input type='hidden' name='otunnus' value='$row[laskutunnus]'>
						<input type='hidden' name='toim' value='TYOMAARAYS'>
						<input type='submit' value = '".t("Tulosta")."'></form></td>";

				echo " </tr>";
			}
			echo "</table><br>";
		}
		else {
			echo t("Yhtään työmääräystä ei löytynyt annetuilla ehdoilla")."!<br>";
		}
	}

	echo "<table><tr><form method='post'><input type='hidden' name='tee' value='etsi'>";
	echo "<th colspan='4'>".t("Hae työmääräykset väliltä").":</th>";

	if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<tr>
		<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr>\n
		<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='Hae'></td></form></tr>";


	echo "<tr><form method='post'><input type='hidden' name='tee' value='etsi'>
		<th>".t("Asiakkaan nimi").":</th><td colspan='3'><input type='text' name='nimi' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post'><input type='hidden' name='tee' value='etsi'>
		<th>".t("Rekno").":</th><td colspan='3'><input type='text' name='rekno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post'><input type='hidden' name='tee' value='etsi'>
		<th>".t("Työmääräysno").":</th><td colspan='3'><input type='text' name='eid' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post'><input type='hidden' name='tee' value='etsi'>
		<th>".t("Asiakasnumero").":</th><td colspan='3'><input type='text' name='asno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post'><input type='hidden' name='tee' value='etsi'>
		<th>".t("Sarjanumero").":</th><td colspan='3'><input type='text' name='valmno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "</table>";

	require ("../inc/footer.inc");

?>