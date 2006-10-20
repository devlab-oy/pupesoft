<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo")."</font><hr>";

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

// piirrellään formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";

echo "<tr>";
echo "<th>".t("Syötä tai valitse osasto").":</th>";
echo "<td><input type='text' name='osasto' size='10'></td>";

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
			order by selite+0";
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

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
			order by selite+0";
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

if ($merkki != '') {
	$chk = 'checked';
}

echo "<tr>";
echo "<th colspan = '3'>".t("Tai aja tuotemerkeittäin (HUOM! kumoaa muut valinnat)").": <input type='checkbox' name='merkki' $chk></th>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='hidden' name='tee' value='tee'>";
echo "<input type='submit' value='".t("Laske varastonarvot")."'>";
echo "</form>";

if ($tee == "tee") {

	if ($merkki == '') {

		$lisa = ""; /// no hacking

		if ($osasto != "") {
			$lisa .= "and tuote.osasto = '$osasto'";
		}

		if ($try != "") {
			$lisa .= "and tuote.try = '$try'";
		}

		//varaston arvo
		$query = "	SELECT sum(tuotepaikat.saldo*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2)) varasto
					FROM tuotepaikat, tuote
					WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
					and tuote.yhtio 		  = tuotepaikat.yhtio
					and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
					and tuote.ei_saldoa 	  = ''
					and tuotepaikat.saldo    <> 0
					and tuote.epakurantti2pvm = '0000-00-00'
					$lisa";

		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$varvo  = $row["varasto"];

		// kuus kuukautta taakseppäin kuun eka päivä
		$kausi = date("Y-m-d", mktime(0, 0, 0, date("m")-12, 1, date("Y")));

		// tuotteen varastonarvon muutos
		$query  = "	SELECT date_format(laadittu, '%Y-%m') kausi, sum(kpl*hinta) muutos, date_format(laadittu, '%Y') yy, date_format(laadittu, '%m') mm
					FROM tuote use index (osasto_try_index)
					JOIN tapahtuma use index (yhtio_tuote_laadittu)
					ON tapahtuma.yhtio = tuote.yhtio
					and tapahtuma.tuoteno = tuote.tuoteno
					and tapahtuma.laadittu > '$kausi 00:00:00'
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.ei_saldoa = ''
					$lisa
					group by kausi
					order by kausi desc";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Pvm")."</th><th>".t("Varastonarvo")."</th><th>".t("Tarkkuus")."</th></tr>";

		echo "<tr><td>".date("Y-m-d")."</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td><td>virallinen</td></tr>";

		while ($row = mysql_fetch_array($result)) {
			$varvo = $varvo - $row["muutos"];
			$apukausi = date("Y-m-d", mktime(0, 0, 0, $row["mm"], 0, $row["yy"]));
			echo "<tr><td>$apukausi</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td><td>arvio</td></tr>";
		}

		echo "</table>";
	}

	if ($merkki != '') {

		//varaston arvo
		$query = "	SELECT tuotemerkki, sum(tuotepaikat.saldo*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2)) varasto
					FROM tuotepaikat, tuote
					WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
					and tuote.yhtio 		  = tuotepaikat.yhtio
					and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
					and tuote.ei_saldoa 	  = ''
					and tuotepaikat.saldo    <> 0
					and tuote.epakurantti2pvm = '0000-00-00'
					group by 1
					order by 1";
		$result = mysql_query($query) or pupe_error($query);

		$varvo = '';

		// 12 kuukautta taakseppäin kuun eka päivä
		$kausi = date("Y-m-d", mktime(0, 0, 0, date("m")-12, 1, date("Y")));

		echo "<table>";
		echo "<tr><th>Pvm<br>".t("Tarkkuus")."</th><th>".date("Y-m-d")."<br>".t("virallinen")."</th>";

		for ($i = 0; $i < 13; $i ++) {
			echo "<th>".date("Y-m-d", mktime(0, 0, 0, date("m")-$i, 1, date("Y")))."<br>".t("arvio")."</th>";
		}

		echo "</tr>";

		while ($row = mysql_fetch_array($result)) {

			$varvo  = $row["varasto"];

			echo "<tr><td>$row[tuotemerkki]</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td>";

			for ($i = 0; $i < 13; $i ++) {
		        //tuotteen varastonarvon muutos
		        $query  = "	SELECT sum(kpl*hinta) muutos
	 	        			FROM tuote
	 	           			JOIN tapahtuma
	 	           			ON tapahtuma.yhtio = tuote.yhtio
	 	           			and tapahtuma.tuoteno = tuote.tuoteno
							and tapahtuma.laadittu < '".date("Y-m-d", mktime(0, 0, 0, date("m")-$i+1, 1, date("Y")))." 00:00:00'
							and tapahtuma.laadittu > '".date("Y-m-d", mktime(0, 0, 0, date("m")-$i,   1, date("Y")))." 00:00:00'
	 	           			WHERE tuote.yhtio = '$kukarow[yhtio]'
	 	           			and tuotemerkki = '$row[tuotemerkki]'
							and tuote.ei_saldoa = ''";

	 			$result2 = mysql_query($query) or pupe_error($query);
				$alarow    = mysql_fetch_array($result2);

				$varvo = $varvo - $alarow["muutos"];
				echo "<td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td>";
	    	}
			echo "</tr>";
		}
		echo "</table>";
	}

}

require ("../inc/footer.inc");

?>