<?php

// käytetään slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

if (!isset($pp)) $pp = date("d");
if (!isset($kk)) $kk = date("m");
if (!isset($vv)) $vv = date("Y");

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);
$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

// härski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

// piirrellään formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";

echo "<tr>";
echo "<th>Syötä tai valitse osasto:</th>";
echo "<td><input type='text' name='osasto' size='10'></td>";

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
			order by selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='osasto2'>";
echo "<option value=''>Kaikki</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($osasto == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Syötä tai valitse tuoteryhmä:</th>";
echo "<td><input type='text' name='try' size='10'></td>";

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
			order by selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='try2'>";
echo "<option value=''>Kaikki</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($try == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Syötä vvvv-kk-pp:</th>";
echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Näytetäänkö tuotteet:</th>";
echo "<td colspan='2'><input type='checkbox' name='naytarivit'> (Listaus lähetetään sähköpostiisi)</td>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='hidden' name='tee' value='tee'>";
echo "<input type='submit' value='Laske varastonarvot'>";
echo "</form>";

if ($tee == "tee") {

	$lisa = ""; /// no hacking
	if ($try != "")    $lisa .= "and try = '$try'";
	if ($osasto != "") $lisa .= "and osasto = '$osasto'";

	// haetaan halutut tuotteet
	$query  = "	SELECT tuoteno, osasto, try, nimitys, kehahin, epakurantti1pvm, epakurantti2pvm
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				and ei_saldoa = ''
				$lisa
				ORDER BY osasto, try, tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	echo "<font class='message'>".t("Löytyi"). " ";
	flush();
	echo mysql_num_rows($result)." ".t("tuotetta")."...</font><br><br>";
	flush();

	$varvo = 0; // tähän summaillaan

	if ($naytarivit != "") {
		$ulos  = "osasto\t";
		$ulos .= "try\t";
		$ulos .= "tuoteno\t";
		$ulos .= "nimitys\t";
		$ulos .= "saldo\t";
		$ulos .= "kehahin\t";
		$ulos .= "vararvo\n";
	}

	while ($row = mysql_fetch_array($result)) {

	   // tuotteen määrä varastossa nyt
	   $query = "	SELECT sum(saldo) varasto
		   			FROM tuotepaikat use index (tuote_index)
		   			WHERE yhtio = '$kukarow[yhtio]'
		   			and tuoteno = '$row[tuoteno]'";
		$vres = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vres);

		// tuotteen muutos varastossa annetun päivän jälkeen
		$query = "	SELECT sum(kpl*hinta) muutoshinta, sum(kpl) muutoskpl
		 			FROM tapahtuma use index (yhtio_tuote_laadittu)
		 			WHERE yhtio = '$kukarow[yhtio]'
		 			and tuoteno = '$row[tuoteno]'
		 			and laadittu > '$vv-$kk-$pp 23:59:59'";
		$mres = mysql_query($query) or pupe_error($query);
		$mrow = mysql_fetch_array($mres);

		// katotaan onko tuote epäkurantti nyt
		$kerroin = 1;
		if ($row['epakurantti1pvm'] != '0000-00-00') {
			$kerroin = 0.5;
		}
		if ($row['epakurantti2pvm'] != '0000-00-00') {
			$kerroin = 0;
		}

		// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
		$muutoshinta = ($vrow["varasto"] * $row["kehahin"] * $kerroin) - $mrow["muutoshinta"];

		// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
		$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

		// summataan varastonarvoa
		$varvo += $muutoshinta;

		if ($naytarivit != "" and $muutoskpl != 0) {

			// yritetään kaivaa listaan vielä sen hetkinen kehahin jos se halutaan kerran nähdä
			$kehasilloin = $row["kehahin"] * $kerroin; // nykyinen kehahin
			$kehalisa = "~"; // laitetaan about merkki failiin jos ei löydetä tapahtumista mitää

			// jos ollaan annettu tämä päivä niin ei ajeta tätä , koska nykyinen kehahin on oikein ja näin on nopeempaa! wheee!
			if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
				// katotaan mikä oli tuotteen viimeisin hinta annettuna päivänä tai sitten sitä ennen
				$query = "	SELECT hinta
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laadittu <= '$vv-$kk-$pp 23:59:59'
							and hinta <> 0
							ORDER BY laadittu desc
							LIMIT 1";
				$ares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($ares) == 1) {
					// löydettiin keskihankintahinta tapahtumista käytetään
					$arow = mysql_fetch_array($ares);
					$kehasilloin = $arow["hinta"];
					$kehalisa = "";
				}
			}

   			$ulos .= "$row[osasto]\t";
   			$ulos .= "$row[try]\t";
   			$ulos .= "$row[tuoteno]\t";
   			$ulos .= "$row[nimitys]\t";
   			$ulos .= str_replace(".",",",$muutoskpl)."\t";
   			$ulos .= "$kehalisa ".str_replace(".",",",$kehasilloin)."\t";
   			$ulos .= str_replace(".",",",$muutoshinta)."\n";
		}

	} // end while

	if ($naytarivit != "") {

		// lähetetään meili
		$bound = uniqid(time()."_") ;

		$header  = "From: <mailer@pupesoft.com>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n\n";

		$content .= chunk_split(base64_encode($ulos));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Varastonarvo"), $content, $header);

		echo "<font class='message'>".t("Lähetetään sähköposti");
		if ($boob === FALSE) echo " - ".t("Email lähetys epäonnistui!")."<br>";
		else echo " $kukarow[eposti].<br>";
		echo "</font><br>";
	}

	echo "<table>";
	echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
	echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
	echo "</table>";

}

require ("../inc/footer.inc");

?>
