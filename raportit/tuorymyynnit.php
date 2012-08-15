<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Asiakkaan ostot tuoteryhm‰st‰")."</font><hr>";

if ($try=='' or $ytunnus=='') {
	echo "<form name=asiakas method='post' autocomplete='off'>";
	echo "<table><tr>";
	echo "<th>".t("Anna asiakasnumero tai osa nimest‰")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
	echo "<td class='back'></td>";
	echo "</tr><tr>";
	echo "<th>".t("Anna osasto")."</th>";
	echo "<td><input type='text' name='osasto' value='$osasto'></td>";
	echo "<td class='back'></td>";
	echo "</tr><tr>";
	echo "<th>".t("Anna tuoteryhm‰")."</th>";
	echo "<td><input type='text' name='try' value='$try'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

if ($ytunnus!='') {
	require ("inc/asiakashaku.inc");
}

// jos meill‰ on onnistuneesti valittu asiakas
if ($ytunnus!='' and $try!='') {

	echo "<table><tr>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("asnro")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th colspan='3'>".t("osoite")."</th>";
	echo "</tr><tr>";
	echo "<td>$asiakasrow[ytunnus]</td>";
	echo "<td>$asiakasrow[asiakasnro]</td>";
	echo "<td>$asiakasrow[nimi]<br>$asiakasrow[toim_nimi]</td>";
	echo "<td>$asiakasrow[osoite]<br>$asiakasrow[toim_osoite]</td>";
	echo "<td>$asiakasrow[postino]<br>$asiakasrow[toim_postino]</td>";
	echo "<td>$asiakasrow[postitp]<br>$asiakasrow[toim_postitp]</td>";
	echo "</tr></table>";


	// hardcoodataan v‰rej‰
	$cmyynti = "#ccccff";
	$ckate   = "#ff9955";
	$ckatepr = "#00dd00";
	$maxcol  = 12; // montako columnia n‰yttˆ on


	$katteet_naytetaan = ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $kukarow["naytetaan_katteet_tilauksella"] == "") ? true : false;

	// tehd‰‰n asiakkaan ostot tuoteryhm‰st‰
	echo "<br><font class='message'>".t("Osasto")." $osasto ".t("tuoteryhm‰")." $try ".t("myynti kausittain viimeiset 24 kk")." (<font color='$cmyynti'>".t("myynti")."</font>";

	if ($katteet_naytetaan) echo "/<font color='$ckate'>".t("kate")."</font>";
	if ($katteet_naytetaan) echo "/<font color='$ckatepr'>".t("kateprosentti")."</font>";

	echo ")</font><hr>";

	// alkukuukauden tiedot 24 kk sitten
	$ayy = date("Y-m-01",mktime(0, 0, 0, date("m")-24, date("d"), date("Y")));

	$query  = "	SELECT
				date_format(lasku.tapvm,'%Y/%m') kausi,
				round(sum(rivihinta),0) myynti,
				round(sum(tilausrivi.kate),0) kate,
				round(sum(tilausrivi.kpl),0) kpl,
				round(sum(tilausrivi.kate)/sum(rivihinta)*100,1) katepro
				from lasku use index (yhtio_tila_liitostunnus_tapvm), tilausrivi use index (uusiotunnus_index)
				where lasku.yhtio	= '$kukarow[yhtio]'
				and lasku.tila			= 'U'
				and lasku.alatila		= 'X'
				and lasku.tapvm			>= '$ayy'
				and lasku.liitostunnus	= '$asiakasid'
				and tilausrivi.try		= '$try'
				and tilausrivi.osasto	= '$osasto'
				and tilausrivi.uusiotunnus = lasku.tunnus
				and tilausrivi.yhtio = lasku.yhtio
				group by 1
				having myynti <> 0 or kate <> 0";
	$result = mysql_query($query) or pupe_error($query);

	// otetaan suurin myynti talteen
	$maxeur=0;

	while ($sumrow = mysql_fetch_array($result)) {
		if ($sumrow['myynti'] > $maxeur) $maxeur = $sumrow['myynti'];
		if ($katteet_naytetaan and $sumrow['kate'] > $maxeur) $maxeur = $sumrow['kate'];
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

		$pylvaat = "<table style='padding:0px;margin:0px;'><tr>
		<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='../pics/blue.png' height='$hmyynti' width='12' alt='".t("myynti")." $sumrow[myynti] $yhtiorow[valkoodi]'></td>";

		if ($katteet_naytetaan) $pylvaat .= "<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='../pics/orange.png' height='$hkate' width='12' alt='".t("kate")." $sumrow[kate] $yhtiorow[valkoodi]'></td>";
		if ($katteet_naytetaan) $pylvaat .= "<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='../pics/green.png' height='$hkatepro' width='12' alt='".t("kateprosentti")." $sumrow[katepro] %'></td>";

		$pylvaat .= "</tr></table>";

		if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
		echo "<td valign='bottom' class='back'>";

		echo "<table width='60'>";
		echo "<tr><td nowrap align='center' style='padding:0px;margin:0px;vertical-align:bottom;height:55px;'>$pylvaat</td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'>$sumrow[kausi]</font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font>$sumrow[myynti] $yhtiorow[valkoodi]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font>$sumrow[kpl] ".t("kpl")."</font></font></td></tr>";
		if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='info'><font>$sumrow[kate]   $yhtiorow[valkoodi]</font></font></td></tr>";
		if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='info'><font>$sumrow[katepro] %</font></font></td></tr>";
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

	echo "<br><form action='asiakasinfo.php?lopetus=$lopetus' method='post'>";
	echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
	echo "<input type='submit' value='".t("Asiakkaan perustiedot")."'>";
}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>
