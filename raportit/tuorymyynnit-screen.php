<?php
///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Tuoteryhmien myynnit")."</font><hr>";

if (!isset($vv)) $vv = date("Y");
if (!isset($kk)) $kk = date("m");

echo "<form name='tuomy' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna vuosi ja kuukausi")."</th>";
echo "<td><input type='text' size='10' name='vv' value='$vv'><input type='text' size='10' name='kk' value='$kk'></td>";
echo "<td class='back'></td>";
echo "</tr><tr>";
echo "<th>".t("Anna osasto")."</th>";
echo "<td><input type='text' size='10' name='osasto' value='$osasto'> ".t("(tyhj‰ = kaikki)")."</td>";
echo "<td class='back'><input name='submit' type='submit' value='".t("Aja raportti")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

// jos meill‰ on onnistuneesti valittu asiakas
if ($submit!='' and $vv!='' and $kk!='') {

	// hardcoodataan v‰rej‰
	$cmyynti = "#ccccff";
	$ckate   = "#ff9955";
	$ckatepr = "#00dd00";
	$maxcol  = 12; // montako columnia n‰yttˆ on

	if ($osasto != "") {
		$where = "and osasto='$osasto'";
	}
	else {
		$where = "";
		$osasto = "kaikki";
	}

	$ayy = "$vv-$kk-01";
	$lyy = date("Y-m-01",mktime(0, 0, 0, $kk+1, 1, $vv));

	// tehd‰‰n asiakkaan ostot tuoteryhm‰st‰
	echo "<br><font class='message'>".t("Tuoteryhmien myynnit")." $vv-$kk ".t("osastolta")." $osasto (<font color='$cmyynti'>".t("myynti")."</font>/<font color='$ckate'>".t("kate")."</font>/<font color='$ckatepr'>".t("kateprosentti")."</font>)</font><hr>";



	$query  = "select
		osasto,
		try,
		round(sum(rivihinta),0) myynti,
		round(sum(kate),0) kate,
		round(sum(kpl),0) kpl,
		round(sum(kate)/sum(rivihinta)*100,1) katepro
		from tilausrivi use index (yhtio_tyyppi_laskutettuaika)
		where
		yhtio='$kukarow[yhtio]' and
		tyyppi='L' and
		laskutettuaika >= '$ayy' and
		laskutettuaika  < '$lyy'
		$where
		group by osasto, try";
	$result = mysql_query($query) or pupe_error($query);

	// otetaan suurin myynti talteen
	$maxeur=0;
	while ($sumrow = mysql_fetch_array($result)) {
		if ($sumrow['myynti']>$maxeur) $maxeur=$sumrow['myynti'];
		if ($sumrow['kate']>$maxeur) $maxeur=$sumrow['kate'];
	}

	// ja kelataan resultti alkuun
	if (mysql_num_rows($result)>0)
		mysql_data_seek($result,0);

	$col=1;
	echo "<table>\n";

	while ($sumrow = mysql_fetch_array($result)) {

		if ($col==1) echo "<tr>\n";

		// lasketaan pylv‰iden korkeus
		if ($maxeur>0) {
			$hmyynti  = round(50*$sumrow['myynti']/$maxeur,0);
			$hkate    = round(50*$sumrow['kate']/$maxeur,0);
			$hkatepro = round($sumrow['katepro']/2,0);
			if ($hkatepro>60) $hkatepro = 60;
		}
		else {
			$hmyynti = $hkate = $hkatepro = 0;
		}

		$pylvaat = "<table border='0' cellpadding='0' cellspacing='0'><tr>
		<td valign='bottom' align='center'><img src='../pics/blue.png' height='$hmyynti' width='12' alt='".t("Myynti")." $sumrow[myynti] $yhtiorow[valkoodi]'></td>
		<td valign='bottom' align='center'><img src='../pics/orange.png' height='$hkate' width='12' alt='".t("Kate")." $sumrow[kate] $yhtiorow[valkoodi]'></td>
		<td valign='bottom' align='center'><img src='../pics/green.png' height='$hkatepro' width='12' alt='".t("Katepros")." $sumrow[katepro] %'></td>
		</tr></table>";

		if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
		echo "<td valign='bottom' class='back'>";

		echo "<table width='60'>";
		echo "<tr><td nowrap align='center' height='55' valign='bottom'>$pylvaat</td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'>$sumrow[osasto] / $sumrow[try]</font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font color='$cmyynti'>$sumrow[myynti] $yhtiorow[valkoodi]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font color='$cmyynti'>$sumrow[kpl] kpl</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font color='$ckate'>$sumrow[kate]   $yhtiorow[valkoodi]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font color='$ckatepr'>$sumrow[katepro] %</font></font></td></tr>";
		echo "</table>";

		echo "</td>\n";

		if ($col==$maxcol) {
			echo "</tr>\n";
			$col=0;
		}

		$yht+=$sumrow['myynti'];
		$col++;
	}

	// teh‰‰n validia htmll‰‰ ja t‰ytet‰‰n tyhj‰t solut..
	$ero = $maxcol+1-$col;

	if ($ero<>$maxcol)
		echo "<td colspan='$ero' class='back'></td></tr>\n";

	echo "</table>";
}

// kursorinohjausta
$formi  = "tuomy";
$kentta = "vv";

require ("../inc/footer.inc");

?>
