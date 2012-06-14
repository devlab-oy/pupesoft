<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	require("inc/parametrit.inc");

	if ($tee == "lataa_tiedosto") {
		readfile("/tmp/".basename($tmpfilenimi));
		exit;
	}

	if (!isset($alvp) or $alvp == "") $alvp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
	if (!isset($alvv) or $alvv == "") $alvv = date("Y");
	if (!isset($alvk) or $alvk == "") $alvk = date("m");

	if (!isset($plvp) or $plvp == "") $plvp = 1;
	if (!isset($plvv) or $plvv == "") $plvv = date("Y");
	if (!isset($plvk) or $plvk == "") $plvk = date("m");

	echo "<font class='head'>".t("Basware-siirto")."</font><hr>\n";

	echo "<br>\n";

	echo "<form name='tosite' method='post' autocomplete='off'>\n";
	echo "<input type='hidden' name='tee' value='TEEAINEISTO'>\n";
	echo "<table>";

	echo "<th valign='top'>".t("Alkukausi")."</th>
			<td><select name='plvv'>";

	$sel = array();
	$sel[$plvv] = "SELECTED";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {

		if (!isset($sel[$i])) {
			$sel[$i] = "";
		}

		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	echo "</select>";

	$sel = array();
	$sel[$plvk] = "SELECTED";

	echo "<select name='plvk'>";

	for ($opt = 1; $opt <= 12; $opt++) {
		$opt = sprintf("%02d", $opt);

		if (!isset($sel[$opt])) {
			$sel[$opt] = "";
		}

		echo "<option $sel[$opt] value = '$opt'>$opt</option>";
	}

	echo "</select>";

	$sel = array();
	$sel[$plvp] = "SELECTED";

	echo "<select name='plvp'>";

	for ($opt = 1; $opt <= 31; $opt++) {
		$opt = sprintf("%02d", $opt);

		if (!isset($sel[$opt])) {
			$sel[$opt] = "";
		}

		echo "<option $sel[$opt] value = '$opt'>$opt</option>";
	}

	echo "</select></td></tr>";

	echo "<tr>
		<th valign='top'>".t("Loppukausi")."</th>
		<td><select name='alvv'>";

	$sel = array();
	$sel[$alvv] = "SELECTED";

	for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	echo "</select>";

	$sel = array();
	$sel[$alvk] = "SELECTED";

	echo "<select name='alvk'>";

	for ($opt = 1; $opt <= 12; $opt++) {
		$opt = sprintf("%02d", $opt);

		if (!isset($sel[$opt])) {
			$sel[$opt] = "";
		}

		echo "<option $sel[$opt] value = '$opt'>$opt</option>";
	}

	echo "</select>";

	$sel = array();
	$sel[$alvp] = "SELECTED";

	echo "<select name='alvp'>";

	for ($opt = 1; $opt <= 31; $opt++) {
		$opt = sprintf("%02d", $opt);

		if (!isset($sel[$opt])) {
			$sel[$opt] = "";
		}

		echo "<option $sel[$opt] value = '$opt'>$opt</option>";
	}

	echo "</select></td></tr>";

	echo "<tr><th valign='top'>".t("tai koko tilikausi")."</th>";

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku DESC";
	$vresult = pupe_query($query);

	echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_assoc($vresult)) {
		$sel="";
		if ($tkausi == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
	}
	echo "</select></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Luo aineisto")."'></form><br><br>";

	if ($tee == "TEEAINEISTO") {
		if ($plvk == '' or $plvv == '') {
			$plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
			$plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
		}

		if ((int) $tkausi > 0) {
			$query = "	SELECT tilikausi_alku, tilikausi_loppu
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
			$result = pupe_query($query);
			$tkrow = mysql_fetch_assoc($result);

			$plvv = substr($tkrow['tilikausi_alku'], 0, 4);
			$plvk = substr($tkrow['tilikausi_alku'], 5, 2);
			$plvp = substr($tkrow['tilikausi_alku'], 8, 2);

			$alvv = substr($tkrow['tilikausi_loppu'], 0, 4);
			$alvk = substr($tkrow['tilikausi_loppu'], 5, 2);
			$alvp = substr($tkrow['tilikausi_loppu'], 8, 2);
		}

		// Tarkistetaan viel‰ p‰iv‰m‰‰r‰t
		if (!checkdate($plvk, $plvp, $plvv)) {
			echo "<font class='error'>".t("VIRHE: Alkup‰iv‰m‰‰r‰ on virheellinen")."!</font><br>";
			$tee = "";
		}

		if (!checkdate($alvk, $alvp, $alvv)) {
			echo "<font class='error'>".t("VIRHE: Loppup‰iv‰m‰‰r‰ on virheellinen")."!</font><br>";
			$tee = "";
		}
	}

	if ($tee == "TEEAINEISTO") {

		$tiedostonimi = "basw_siirto-$kukarow[yhtio]-".date("YmdHis").".csv";

		$toot = fopen("/tmp/".$tiedostonimi, "w");

		echo "<table>";
		echo "<tr>
			<th>",t("Alkup‰iv‰m‰‰r‰"),"</th>
			<td>{$plvp}</td>
			<td>{$plvk}</td>
			<td>{$plvv}</td>
			</tr>\n";
		echo "<tr>
			<th>",t("Loppup‰iv‰m‰‰r‰"),"</th>
			<td>{$alvp}</td>
			<td>{$alvk}</td>
			<td>{$alvv}</td>
			</tr>\n";
		echo "</table><br>";

		$query = "	SELECT tiliointi.tilino, kustannuspaikka.koodi, sum(tiliointi.summa) summa
					FROM tiliointi
					LEFT JOIN kustannuspaikka ON tiliointi.yhtio = kustannuspaikka.yhtio and tiliointi.kustp = kustannuspaikka.tunnus
					WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm   >= '$plvv-$plvk-$plvp'
					and tiliointi.tapvm   <= '$alvv-$alvk-$alvp'
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

			echo "<tr><td>$alvv</td><td>$alvk</td><td>$trow[tilino]</td><td>$trow[koodi]</td><td></td><td align='right'>$trow[summa]</td></tr>";

			fwrite($toot, "$alvv;$alvk;$trow[tilino];$trow[koodi];;$trow[summa];\r\n");
		}

		echo "</table>";

		fclose($toot);

		echo "<br><br>".t("Tallenna aineisto").": ";
		echo "<form method='post' class='multisubmit'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$tiedostonimi'>";
		echo "<td><input type='submit' value='".t("Tallenna")."'></form>";

	}

	require ("inc/footer.inc");
?>