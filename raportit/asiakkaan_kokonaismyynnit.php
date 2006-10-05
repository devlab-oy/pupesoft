<?php
///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Asiakkaan kokonaismyynnit")."</font><hr>";

if ($ytunnus!='') {
	require ("../inc/asiakashaku.inc");
}

// jos meill‰ on onnistuneesti valittu asiakas
if ($ytunnus!='') {

	echo "<table><tr>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("asnro")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th colspan='3'>".t("osoite")."</th>";
	echo "</tr><tr>";
	echo "<td>$asiakasrow[ytunnus]</td>";
	echo "<td>$asiakasrow[asiakasnro]</td>";
	echo "<td>$asiakasrow[nimi]</td>";
	echo "<td>$asiakasrow[osoite]</td>";
	echo "<td>$asiakasrow[postino]</td>";
	echo "<td>$asiakasrow[postitp]</td>";
	echo "</tr></table>";


	// hardcoodataan v‰rej‰
	//$cmyynti = "#ccccff";
	//$ckate   = "#ff9955";
	//$ckatepr = "#00dd00";
	$maxcol  = 12; // montako columnia n‰yttˆ on

	// tehd‰‰n asiakkaan ostot tuoteryhm‰st‰
	echo "<br><font class='message'>".t("Myynti vuosittain viimeiset 4 vuotta")." (<font color='$cmyynti'>".t("myynti")."</font>/<font color='$ckate'>".t("kate")."</font>/<font color='$ckatepr'>".t("kateprosentti")."</font>)</font><hr>";

	// 4 v sitten
	$ayy = date("Y")-4;

	$query  = "	select
				year(tapvm) kausi,
				sum(arvo) myynti,
				sum(kate) kate,
				round(sum(kate)/sum(arvo)*100,1) katepro
				from lasku use index (yhtio_tila_liitostunnus_tapvm)
				where yhtio='$kukarow[yhtio]' and tapvm>='$ayy-01-01'
				and liitostunnus='$asiakasid' and tila='U' and alatila='X'
				group by 1";
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
		<td valign='bottom' align='center'><img src='../pics/blue.png' height='$hmyynti' width='12' alt='myynti $sumrow[myynti] $yhtiorow[valkoodi]'></td>
		<td valign='bottom' align='center'><img src='../pics/orange.png' height='$hkate' width='12' alt='kate $sumrow[kate] $yhtiorow[valkoodi]'></td>
		<td valign='bottom' align='center'><img src='../pics/green.png' height='$hkatepro' width='12' alt='kateprosentti $sumrow[katepro] %'></td>
		</tr></table>";

		if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
		echo "<td valign='bottom' class='back'>";

		echo "<table width='60'>";
		echo "<tr><td nowrap align='center' height='55' valign='bottom'>$pylvaat</td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'>$sumrow[kausi]</font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font color='$cmyynti'>$sumrow[myynti] $yhtiorow[valkoodi]</font></font></td></tr>";
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
	$ytunnus = '';
}


echo "<br><br><form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna asiakasnumero tai osa nimest‰")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form>";



// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>
