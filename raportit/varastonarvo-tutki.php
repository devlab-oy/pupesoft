<?php

// käytetään slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvon tarkastelua")."</font><hr>";

// tutkaillaan saadut muuttujat
$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

$pp1 	= sprintf("%02d", trim($pp1));
$kk1 	= sprintf("%02d", trim($kk1));
$vv1 	= sprintf("%04d", trim($vv1));

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

// härski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";
if ($pp1 == "00" or $kk1 == "00" or $vv1 == "0000") $tee = $pp1 = $kk1 = $vv1 = "";

// piirrellään formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Syötä alku pp-kk-vvvv").":</th>";
echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
echo "</tr>";
echo "<tr>";
echo "<th>".t("Syötä loppu pp-kk-vvvv").":</th>";
echo "<td><input type='text' name='pp1' size='5' value='$pp1'><input type='text' name='kk1' size='5' value='$kk1'><input type='text' name='vv1' size='7' value='$vv1'></td>";
echo "</tr>";
echo "</table>";

echo "<br>";
echo "<input type='hidden' name='tee' value='tee'>";
echo "<input type='submit' value='".t("Tarkastele")."'>";
echo "</form>";
echo "<br><br>";

if ($tee == "tee") {

	// haetaan halutut varastotaphtumat
	$query  = "	SELECT laji, count(*) kpl, round(sum(hinta*kpl),2) logistiikka
				FROM tapahtuma, tuote
				WHERE tapahtuma.yhtio = '$kukarow[yhtio]' and
					laadittu >= '$vv-$kk-$pp 00:00:00' and
					laadittu <= '$vv1-$kk1-$pp1 23:59:59' and
					tapahtuma.yhtio=tuote.yhtio and
					tapahtuma.tuoteno=tuote.tuoteno and
					ei_saldoa=''
				GROUP BY laji";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("laji")."</th>";
	echo "<th>".t("kpl")."</th>";
	echo "<th>".t("logistiikka")."</th>";
	echo "<th>".t("kirjanpito")."</th>";
	echo "<th>".t("ero")."</th>";
	echo "</tr>";

	$automaatit = 0;

	while ($trow = mysql_fetch_array ($result)) {
		echo "<tr>";
		echo "<td>$trow[laji]</td>";
		echo "<td align='right'>$trow[kpl]</td>";
		echo "<td align='right'>$trow[logistiikka]</td>";

		//Etsitään vastaavat kirjapidon viennit
		$lvalinta = '';

		if ($trow['laji'] == 'laskutus') $lvalinta = "tila = 'U' and alatila = 'X' and selite not like 'Varastoontulo%'";
		if ($trow['laji'] == 'Inventointi') $lvalinta = "tila = 'X' and selite like '%inventoi%'";
		if ($trow['laji'] == 'Epäkurantti') $lvalinta = "tila = 'X' and selite like '%epäkura%'";
		if ($trow['laji'] == 'tulo') $lvalinta = " ((tila in ('H', 'M', 'P', 'Q', 'Y') and vienti not in ('A', 'D', 'G', '')) or (tila = 'U' and alatila = 'X' and selite like 'Varastoontulo%'))";

		if ($lvalinta != '') {
			$query  = "	SELECT sum(tiliointi.summa) summa 
						FROM tiliointi use index (yhtio_tilino_tapvm), lasku
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and 
						tiliointi.tapvm >= '$vv-$kk-$pp' and
						tiliointi.tapvm <= '$vv1-$kk1-$pp1' and
						tiliointi.yhtio = lasku. yhtio and
						tiliointi.ltunnus = lasku.tunnus and
						tiliointi.tilino = '$yhtiorow[varasto]' and
						tiliointi.korjattu = '' 
						and $lvalinta";
			$lresult = mysql_query($query) or pupe_error($query);			
			$lrow = mysql_fetch_array ($lresult);

			echo "<td align='right'>$lrow[summa]</td>";
			echo "<td align='right'>".round($trow["logistiikka"] - $lrow["summa"], 2)."</td>";

			$automaatit += $lrow["summa"];
		}
		else {
			echo "<td></td><td></td>";
		}
		echo "</tr>";
	}
	echo "</table>";

	$query  = "	SELECT sum(tiliointi.summa) summa FROM tiliointi
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
				tiliointi.tapvm >= '$vv-$kk-$pp' and
				tiliointi.tapvm <= '$vv1-$kk1-$pp1' and
				tiliointi.korjattu = '' and
				tiliointi.tilino = '$yhtiorow[varasto]'";
	$lresult = mysql_query($query) or pupe_error($query);
	$lrow = mysql_fetch_array ($lresult);

	echo "<br><font class='message'>".t("Samalta ajanjaksolta varastonarvoon vaikuttavat käsiviennit ovat").": ";
	echo round($lrow["summa"] - $automaatit, 2);
	echo "</font>";

/*
	echo "<br><font class='message'>Tulojen lähempi tarkastelu</font><br>";
	echo "<table>";

	$query  = "	SELECT laskutettuaika, count(*) kpl, sum(hinta*kpl) tavara,
				sum(rivihinta-hinta*kpl) kulut, sum(rivihinta) kokonaiskulu
				FROM tilausrivi
				WHERE yhtio = '$kukarow[yhtio]' and laskutettuaika >= '$vv-$kk-$pp' and
						laskutettuaika <= '$vv1-$kk1-$pp1' and tyyppi='O'
				GROUP BY 1";
	$result = mysql_query($query) or pupe_error($query);
	echo "<table>";
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "<th>kirjanpito</th></tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$trow[$i]</td>";
		}
		echo "<th>kirjanpito</th></tr>";
	}
	echo "</table>";
*/

}

require ("../inc/footer.inc");

?>
