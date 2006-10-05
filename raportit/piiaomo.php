<?php

	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Piirien myynnit").":</font><hr>";

	// haetaan konsernin kaikki yhtiot ja tehdään mysql lauseke muuttujaan yhtiot
	$query = "select yhtio from yhtio where konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = mysql_query($query) or pupe_error($query);

	$yhtiot = "";
	while ($rivi = mysql_fetch_array($result)) {
		$yhtiot .= "'$rivi[yhtio]',";
	}
	$yhtiot = substr($yhtiot, 0, -1); // vika pilkku pois

	// jos ei löytynyt yhtään konserniyhtiötä ni laitetaan kukarow[yhtio]
	if ($yhtiot == "") {
		$yhtiot = "'$kukarow[yhtio]'";
	}

	if ($tee != "") {
		// hehe, näin on helpompi verrata päivämääriä
		$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);

		if ($row["ero"] > 365) {
			echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
			$tee = "";
		}
	}

	if ($tee != '') {

		if ($konserni == "") {
			$yhtiot = "'$kukarow[yhtio]'";
		}

		if ($piiri != "") $piirit = " and asiakas.piiri='$piiri'";
		else $piirit = "";

		// edelliset vuodet
		$vvaa = $vva - '1';
		$vvll = $vvl - '1';

		$query = "	select yhtio.nimi yhtio, piiri,
					sum(if(tapvm >= '$vva-$kka-$ppa'  and tapvm <= '$vvl-$kkl-$ppl', arvo,0)) myyntinyt,
					sum(if(tapvm >= '$vvaa-$kka-$ppa' and tapvm <= '$vvll-$kkl-$ppl',arvo,0)) myyntied,
					round(sum(if(tapvm >= '$vva-$kka-$ppa'  and tapvm <= '$vvl-$kkl-$ppl', arvo,0)) /
					sum(if(tapvm >= '$vvaa-$kka-$ppa' and tapvm <= '$vvll-$kkl-$ppl',arvo,0)),2) myyntiind,
					sum(if(tapvm >= '$vva-$kka-$ppa'  and tapvm <= '$vvl-$kkl-$ppl', kate,0)) katenyt,
					sum(if(tapvm >= '$vvaa-$kka-$ppa' and tapvm <= '$vvll-$kkl-$ppl',kate,0)) kateed,
					round(sum(if(tapvm >= '$vva-$kka-$ppa'  and tapvm <= '$vvl-$kkl-$ppl', kate,0)) /
					sum(if(tapvm >= '$vvaa-$kka-$ppa' and tapvm <= '$vvll-$kkl-$ppl',kate,0)),2) kateind
					from lasku use index (yhtio_tila_tapvm)
					left join asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus $piirit
					left join yhtio use index (yhtio_index) on lasku.yhtio = yhtio.yhtio
					where lasku.yhtio in ($yhtiot) and
					lasku.tila = 'U' and
					lasku.alatila = 'X' and
					lasku.tapvm >= '$vvaa-$kka-$ppa' and
					lasku.tapvm <= '$vvl-$kkl-$ppl'
					group by yhtio, piiri
					order by piiri, yhtio";

// tälleeki vois fixaa arwijutut
// if(lasku.yhtio='artr', 'L', 'U')

		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><td class='back'>".t("Kausi nyt")."</td><td class='back'>$vva-$kka-$ppa - $vvl-$kkl-$ppl</td></tr>";
		echo "<tr><td class='back'>".t("Kausi ed")." </td><td class='back'>$vvaa-$kka-$ppa - $vvll-$kkl-$ppl</td></tr>";
		echo "</table><br>";

		echo "<table>";
		echo "<tr>";

		// echotaan kenttien nimet
		for ($i=0; $i < mysql_num_fields($result); $i++) echo "<th>".t(mysql_field_name($result,$i))."</th>";

		echo "</tr>\n";

		$myyntinyt = 0;
		$myyntied  = 0;
		$katenyt   = 0;
		$kateed    = 0;

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";
			echo "<td>$row[yhtio]</td>";
			echo "<td>$row[piiri]</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["myyntinyt"])."</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["myyntied"])."</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["myyntiind"])."</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["katenyt"])."</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["kateed"])."</td>";
			echo "<td align='right'>".str_replace(".", ",", $row["kateind"])."</td>";
			echo "</tr>\n";

			$myyntinyt += $row['myyntinyt'];
			$myyntied  += $row['myyntied'];
			$katenyt   += $row['katenyt'];
			$kateed    += $row['kateed'];
		}

	  	$myyntiind = $kateind = 0;
	  	if ($myyntied <> 0) $myyntiind = round($myyntinyt/$myyntied,2);
	  	if ($kateed   <> 0) $kateind   = round($katenyt/$kateed,2);

	  	echo "<tr><th colspan='2'>".t("yhteensä")."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $myyntinyt)."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $myyntied)."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $myyntiind)."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $katenyt)."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $kateed)."</th>";
	  	echo "<th align='right'>".str_replace(".", ",", $kateind)."</th></tr>\n";

		echo "</table>\n";
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table>";

	// alku defaultti viime kuun eka
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa)) $ppa = "1";

	// loppu defaultti viime kuun vika
	if (!isset($kkl)) $kkl = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vvl)) $vvl = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppl)) $ppl = date("d",mktime(0, 0, 0, date("m"), 0, date("Y"))); // viime kuun vika pvm

	echo "<input type='hidden' name='tee' value='go'>";

	$query  = "select distinct piiri from asiakas where yhtio in ($yhtiot) order by piiri";
	$result = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Näytä piiri")."</th><td colspan='3'>";
	echo "<select name='piiri'>";
	echo "<option value=''>".t("Kaikki piirit")."</option>";

	while ($rivi = mysql_fetch_array($result)) {
		echo "<option value='$rivi[piiri]'>$rivi[piiri]</option>";
	}

	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Näytä kaikki konserniyhtiöt")."</th><td colspan='3'><input type='checkbox' name='konserni'></td></tr>";

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td></tr>";
	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "</table>";
	echo "<br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	require ("../inc/footer.inc");
?>