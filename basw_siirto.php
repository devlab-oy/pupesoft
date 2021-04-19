<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	if ($tee == "lataa_tiedosto") {
		readfile("/tmp/".basename($tmpfilenimi));
		exit;
	}

	echo "<font class='head'>".t("Basware-siirto")."</font><hr>\n";

	echo "<br>\n";

	echo "<form name='tosite' action='$PHP_SELF' method='post' autocomplete='off'>\n";
	echo "<input type='hidden' name='tee' value='TEEAINEISTO'>\n";
	echo "<table>";

	if (!isset($vv)) $vv = date("Y");
	if (!isset($kk)) $kk = date("n");

	echo "<tr>";
	echo "<th>".t("Valitse kausi")."</th>";
	echo "<td>";

	$sel = array();
	$sel[$vv] = "SELECTED";

	$vv_select = date("Y");

	echo "<select name='vv'>";
	for ($i = $vv_select; $i >= $vv_select-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}
	echo "</select>";

	$sel = array();
	$sel[$kk] = "SELECTED";

	echo "<select name='kk'>
			<option $sel[1] value = '1'>01</option>
			<option $sel[2] value = '2'>02</option>
			<option $sel[3] value = '3'>03</option>
			<option $sel[4] value = '4'>04</option>
			<option $sel[5] value = '5'>05</option>
			<option $sel[6] value = '6'>06</option>
			<option $sel[7] value = '7'>07</option>
			<option $sel[8] value = '8'>08</option>
			<option $sel[9] value = '9'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select>";
	echo "</td>";
	echo "</tr></table>";

	echo "<br><input type='submit' value='".t("Luo aineisto")."'></form><br><br>";


	if ($tee == "TEEAINEISTO") {

		$tiedostonimi = "basw_siirto-$kukarow[yhtio]-".date("YmdHis").".csv";

		$toot = fopen("/tmp/".$tiedostonimi, "w");

		$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
		$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

		$query = "	SELECT tiliointi.tilino, kustannuspaikka.koodi, sum(tiliointi.summa) summa
					FROM tiliointi
					LEFT JOIN kustannuspaikka ON tiliointi.yhtio = kustannuspaikka.yhtio and tiliointi.kustp = kustannuspaikka.tunnus
					WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm   >= '$startmonth'
					and tiliointi.tapvm   <= '$endmonth'
					GROUP BY 1,2
					ORDER BY 1,2";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";

		echo "<tr><th>$yhtiorow[nimi]</th><th>".t("Konsernikirjanpidon yhteenveto")."</th><th>".date("j.n.Y")."</th><th>".date("H:i:s")."</th><th>".t("Sivu")." 1</th><th></th></tr>";
		echo "<tr><th>".t("Vuosi")."</th><th>".t("Kausi")."</th><th>".t("Tili")."</th><th>".t("Kustannuspaikka")."</th><th>".t("Konsernitunnus")."</th><th>".t("Summa")."</th></tr>";

		fwrite($toot, $yhtiorow["nimi"].";".t("Konsernikirjanpidon yhteenveto").";".date("j.n.Y").";".date("H:i:s").";".t("Sivu")." 1;\r\n");
		fwrite($toot, t("Vuosi").";".t("Kausi").";".t("Tili").";".t("Kustannuspaikka").";".t("Konsernitunnus").";".t("Summa").";\r\n");

		while ($trow = mysql_fetch_assoc($result)) {

			$trow["summa"] = str_replace(".", ",", $trow["summa"]);

			echo "<tr><td>$vv</td><td>$kk</td><td>$trow[tilino]</td><td>$trow[koodi]</td><td></td><td align='right'>$trow[summa]</td></tr>";

			fwrite($toot, "$vv;$kk;$trow[tilino];$trow[koodi];;$trow[summa];\r\n");
		}

		echo "</table>";

		fclose($toot);

		echo "<br><br>".t("Tallenna aineisto").": ";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$tiedostonimi'>";
		echo "<td><input type='submit' value='".t("Tallenna")."'></form>";

	}

	require ("inc/footer.inc");
?>