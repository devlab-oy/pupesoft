<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("ABC-Analyysi")."<hr></font>";

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

if ($ed == 'on')	$chk = "CHECKED";
else				$chk = "";

// piirrellään formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";

echo "<tr>";
echo "<th>".t("Syötä tai valitse osasto").":</th>";
echo "<td><input type='text' name='osasto' size='10'></td>";

$query = "	SELECT distinct avainsana.selite, ".avain('select')."
			FROM avainsana
			".avain('join','OSASTO_')."
			WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='osasto2'>";
echo "<option value=''>".t("Osasto")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($osasto == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
echo "<td><input type='text' name='try' size='10'></td>";

$query = "	SELECT distinct avainsana.selite, ".avain('select')."
			FROM avainsana
			".avain('join','TRY_')."
			WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='try2'>";
echo "<option value=''>".t("Tuoteryhmä")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($try == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Edellinen vuosi + tämä vuosi")."</th>";
echo "<td><input type='checkbox' name='ed' $chk></td>";
echo "<td>".t("(muuten vain tämä vuosi)")."</td>";
echo "</tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";


// jos kaikki tarvittavat tiedot löytyy mennään queryyn
if ($osasto != "" and $try != "") {

	if ($order == "")	$order = "summa";
	if ($sort == "")	$sort  = "desc";

	if ($ed == "on")	$vuosi = date('Y')-1;
	else 				$vuosi = date('Y');

	$query = "	SELECT
				tilausrivi.tuoteno 								tuoteno,
				tuote.myyntihinta 								hinta,
				tuote.kehahin									kehahin,
				sum(kpl)										kpl,
				sum(rivihinta)									summa,
				sum(kate)										kate,
				round(sum(kate)/sum(rivihinta)*100,2)			katepros
				FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika), tuote use index (tuoteno_index)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
				tuote.yhtio = tilausrivi.yhtio and
				tuote.tuoteno = tilausrivi.tuoteno and
				tuote.epakurantti1pvm = '0000-00-00' and
				tuote.epakurantti2pvm = '0000-00-00' and
				tilausrivi.tyyppi = 'L' and
				tilausrivi.osasto = '$osasto' and
				tilausrivi.try = '$try' and
				tilausrivi.laskutettuaika >= '$vuosi-01-01'
				GROUP BY tuoteno, hinta, kehahin
	   			ORDER BY $order $sort";

	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th nowrap>#</th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=tuoteno&sort=asc&ed=$ed'>".t("Tuoteno")."</a></th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=hinta&sort=desc&ed=$ed'>".t("Hinta")." $yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap>".t("Saldo")."</th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=kpl&sort=desc&ed=$ed'>".t("Myyty kpl")."</a></th>";
	echo "<th nowrap>".t("Vararvo")."</th>";
	echo "<th nowrap>".t("Kum.vararvo")."</th>";
	echo "<th nowrap>".t("Kum.varvo%")."</th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=kate&sort=desc&ed=$ed'>".t("Kate")." $yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap>".t("Kum.kate")."</th>";
	echo "<th nowrap>".t("Kum.kate%")."</th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=summa&sort=desc&ed=$ed'>".t("Myynti")." $yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap>".t("Kum.myynti")."</th>";
	echo "<th nowrap>".t("Kum.myynti%")."</th>";
	echo "<th nowrap><a href='?osasto=$osasto&try=$try&order=katepros&sort=desc&ed=$ed'>".t("Kate")." %</a></th>";
	echo "</tr>\n";

	$totsumma   = 0;
	$totkate    = 0;
	$totvararvo = 0;
	$kumsumma   = 0;
	$kumkate    = 0;
	$kumvararvo = 0;
	$i          = 0;

	while($row = mysql_fetch_array($res)) {
		$totsumma  	+= $row["summa"];
		$totkate    += $row["kate"];

		// uuh tän joutuu tekee eka kaikille tuotteille!
		$query  = "select round(sum(saldo)*$row[kehahin],2) vararvo, sum(saldo) saldo from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$tuores = mysql_query($query) or pupe_error($query);
		$tuorow = mysql_fetch_array($tuores);

		// että saadaan total varaston arvo tälle ajolle
		$totvararvo += $tuorow["vararvo"];
	}

	if (mysql_num_rows($res) > 0) {
		mysql_data_seek($res,0); // kelataan alkuun..
	}

	while($row = mysql_fetch_array($res)) {

		$query  = "select round(sum(saldo)*$row[kehahin],2) vararvo, sum(saldo) saldo from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$tuores = mysql_query($query) or pupe_error($query);
		$tuorow = mysql_fetch_array($tuores);

		$i++;
		$kumsumma   += $row["summa"];
		$kumkate    += $row["kate"];
		$kumvararvo += $tuorow["vararvo"];

		if ($totvararvo<>0)	$vararvopro = round($kumvararvo/$totvararvo*100,1);
		if ($totkate<>0)	$katepro    = round($kumkate/$totkate*100,1);
		if ($totsumma<>0)	$summapro   = round($kumsumma/$totsumma*100,1);

		echo "<tr>";
		echo "<td>$i</td>";
		echo "<td>$row[tuoteno]</td>";
		echo "<td>".str_replace(".",",",$row['hinta'])."</td>";
		echo "<td>".str_replace(".",",",$tuorow['saldo'])."</td>";
		echo "<td>".str_replace(".",",",$row['kpl'])."</td>";
		echo "<td>".str_replace(".",",",$tuorow['vararvo'])."</td>";
		echo "<td>".str_replace(".",",",$kumvararvo)."</td>";
		echo "<td>".str_replace(".",",",$vararvopro)."</td>";
		echo "<td>".str_replace(".",",",$row['kate'])."</td>";
		echo "<td>".str_replace(".",",",$kumkate)."</td>";
		echo "<td>".str_replace(".",",",$katepro)."</td>";
		echo "<td>".str_replace(".",",",$row['summa'])."</td>";
		echo "<td>".str_replace(".",",",$kumsumma)."</td>";
		echo "<td>".str_replace(".",",",$summapro)."</td>";
		echo "<td>".str_replace(".",",",$row['katepros'])."</td>";
		echo "</tr>\n";
	}

	echo "</table>";
}
else {
	echo "<font class='error'>".t("Osasto ja tuoteryhmä on syötettävä")."!</font><br><br>";
}

require ("../inc/footer.inc");

?>