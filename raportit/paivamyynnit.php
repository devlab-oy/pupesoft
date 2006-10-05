<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
require('../inc/parametrit.inc');

echo "<font class='head'>".t("Myynnit päivittäin osastoittain")."</font><hr>";

if ($tee != '') {

		$query 		= "select distinct osasto from asiakas where yhtio = '$kukarow[yhtio]' order by osasto";
		$result		= mysql_query($query) or pupe_error($query);

		// haetaan kaikki osasto arrayseen
		$osastot 	= array();

		while ($ressu = mysql_fetch_array($result)) {
				$osastot[] = $ressu['osasto'];
		}


		$query = "SELECT osasto, date_format(tapvm, '%j') pvm, tapvm, replace(sum(kate), '.', ',') kate, replace(sum(arvo), '.', ',') myynti
				from lasku use index (yhtio_tila_tapvm), asiakas
				where lasku.yhtio = '$kukarow[yhtio]'
				and asiakas.yhtio = lasku.yhtio
				and asiakas.tunnus = lasku.liitostunnus
				and tapvm >= '$vva-$kka-$ppa'
				and tapvm <= '$vva-$kkl-$ppl'
				and tila = 'u'
				and alatila = 'x'
				group by 1,2,3
				order by 1,2,3";
		$result = mysql_query($query) or pupe_error($query);

		//echo "$query";

		while ($ressu = mysql_fetch_array($result)) {
				$apu 		= (int) $ressu['pvm'];
				$osastoapu	= $ressu['osasto'];
				$kate[$osastoapu][$apu]	= $ressu['kate'];
				$tapvm[$apu]= $ressu['tapvm'];
				$myynt[$osastoapu][$apu]= $ressu['myynti'];
		}


		echo "<table>";

		echo "<tr>";
		echo "<th>".t("pvm")."</th>";

		foreach ($osastot as $osasto) {
				echo "<th>$osasto ".t("Myynti_vton")."</th><th>$osasto ".t("Kate")."</th>";
		}
		echo "</tr>";

		for ($i=1; $i<367; $i++) {

				echo "<tr>";
				echo "<td>";
				if (strlen($tapvm[$i])==0) echo "$i"; else  echo "$tapvm[$i]";
				echo "</td>";

				foreach ($osastot as $osasto) {
						echo "<td>".$myynt[$osasto][$i]."</td>";
						echo "<td>".$kate[$osasto][$i]."</td>";
				}
				echo "</tr>";
		}

		echo "</table>";
}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($vva))
		$vva = date("Y");
	$ppa = '01';
	$kka = '01';


	$kkl = '12';
	$vvl = date("Y");
	$ppl = '31';

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Anna vuosi").":</th>
			<td><input type='hidden' name='ppa' value='$ppa' size='0'>
			<input type='hidden' name='kka' value='$kka' size='0'>
			<input type='text' name='vva' value='$vva' size='5'></td></tr>
			<input type='hidden' name='ppl' value='$ppl' size='0'>
			<input type='hidden' name='kkl' value='$kkl' size='0'>
			<input type='hidden' name='vvl' value='$vvl' size='0'>";
	echo "<tr><td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

require ("../inc/footer.inc");
?>