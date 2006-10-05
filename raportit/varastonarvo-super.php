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
	$virhe = 0; // Löydettyjen virheiden määrä

	if ($naytarivit != "") {
		$ulos  = "osasto\t";
		$ulos .= "try\t";
		$ulos .= "tuoteno\t";
		$ulos .= "nimitys\t";
		$ulos .= "saldo\t";
		$ulos .= "kehahin\t";
		$ulos .= "vararvo\r\n";
	}

	while ($row = mysql_fetch_array($result)) {

	   // tuotteen määrä varastossa nyt
	   $query = "	SELECT sum(saldo) varasto
	   			FROM tuotepaikat use index (tuote_index)
	   			WHERE yhtio = '$kukarow[yhtio]'
	   			and tuoteno = '$row[tuoteno]'";
	   $vres = mysql_query($query) or pupe_error($query);
	   $vrow = mysql_fetch_array($vres);
	   $vkpl = $vrow["varasto"];
    
	   // tuotteen muutos varastossa
	   $query = "	SELECT sum(kpl) muutos
	   			FROM tapahtuma use index (yhtio_tuote_laadittu) 
	   			WHERE yhtio = '$kukarow[yhtio]'
	   			and tuoteno = '$row[tuoteno]'
	   			and laji in ('tulo', 'laskutus', 'inventointi')
	   			and laadittu > '$vv-$kk-$pp 23:59:59'";
	   $mres = mysql_query($query) or pupe_error($query);
	   $mrow = mysql_fetch_array($mres);
	   $mkpl = $mrow["muutos"];
    
	   // paljon saldo oli
	   $saldo = $vkpl - $mkpl;
	   
	   // ei haeta keskihankintahintaa eikä tehä matikkaa jos saldo oli tuolloin nolla... säästetään tehoja!
		if ($saldo <> 0) {

			$arvo  = 0; // tuotteelta tuleva kehahin
			$arvo2 = 0; // tapahtumista tuleva kehahin
			$flag  = 0;
			
			// jos ollaan annettu tämä päivä niin ei ajeta tätä, koska nykyinen kehahin on oikein ja näin on nopeempaa! wheee!
			if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {

				// katotaan mikä oli tuotteen hinta tollon. ensiks näin, koska 2005-05-19 korjattiin yks bugi
				$query = "	SELECT hinta
							FROM tapahtuma use index (yhtio_tuote_laadittu) 
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laadittu < '$vv-$kk-$pp 23:59:59'
							and laadittu > '2005-05-19 00:00:00'
							and laji in ('tulo', 'laskutus', 'inventointi', 'Epäkurantti')
							ORDER BY laadittu desc
							LIMIT 1";
				$ares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($ares) == 1) {
					// löydettiin keskihankintahinta tapahtumista
					$arow = mysql_fetch_array($ares);
					$arvo2 = $arow["hinta"];
					$flag = 1; // löydettiin varmasti oikea hinta
				}
				else {
					// katotaan mikä oli tuotteen hinta tollon
					$query = "	SELECT hinta
								FROM tapahtuma use index (yhtio_tuote_laadittu) 
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and laadittu < '$vv-$kk-$pp 23:59:59'
								and laji in ('tulo', 'laskutus', 'inventointi')
								ORDER BY laadittu desc
								LIMIT 1";
					$ares = mysql_query($query) or pupe_error($query);
				   	                	
					if (mysql_num_rows($ares) == 1) {
						// löydettiin keskihankintahinta tapahtumista
						$arow = mysql_fetch_array($ares);
						$arvo2 = $arow["hinta"];
					}
				}
			}
			
			// katotaan oliko tuote silloin epäkurantti vai ei
			// verrataan vähän päivämääriä. onpa vittumaista PHP:ssä!
			list($vv1,$kk1,$pp1) = split("-",$row["epakurantti1pvm"]);
			list($vv2,$kk2,$pp2) = split("-",$row["epakurantti2pvm"]);
			
			$epa1 = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
			$epa2 = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
			$raja = (int) date('Ymd',mktime(0,0,0,$kk, $pp, $vv ));
			
			$tagi = "";
			
			if ($row['epakurantti1pvm'] != '0000-00-00' and $epa1 <= $raja) {
				$row['kehahin'] = $row['kehahin'] / 2;
				$arvo2 = $arvo2 / 2; // emuloidaan puoliepäkuranttia, koska niitä tapahtumia me ei haeta
				$tagi = "%";
			}
			if ($row['epakurantti2pvm'] != '0000-00-00' and $epa2 <= $raja) {
				$row['kehahin'] = 0;
				$tagi = "!";
				$arvo2 = 0; // tässä keisissä pitää aina nollata arvo.
			}
			
			$arvo = $row["kehahin"];
			
			if ($arvo2 == 0) $arvo2 = $arvo; // jos tapahtumista ei löydy yhtään lukua, luotetaan tuotteelta saatuun kehahintaan
			
			// tämä seuraava kikka on vaan sen takia, että tapahtumissa oli bugi epäkuranttien kohdalla joka korjattiin 2005-05-19
			// jossain vaiheessa tämän voi ottaa pois?? ja hinta queryyn lisätä laji in ryhmään vielä 'Epäkurantti' ja ylhäätä turha tapahtuma query pois
			if ($arvo != 0 and $flag != 1) {
				// jos tuotteen ja tapahtuman hinnat heittää yli 45 prosenttia, otetaan hinta tuotteelta
				// koska tuote on todennäköisesti puoli- tai täysepäkurantti ja hinta on tuotteella silloin oikein oikein
				if (abs($arvo-$arvo2) / abs($arvo) * 100 > 45) {
					$virhe++; // lasketaan monta "virheellistä" tuotetta
					$arvo2 = $arvo;
				}
			}
			
			// arvo kakkosessa pitäisi olla nyt oikea luku.. käytetään kuitenkin arvoa matikassa. ei ollenkaan sekavaa..
			$arvo = $arvo2;
			
			// tämän tuotteen varastonarvo historiasta
			$apu = $saldo * $arvo;
			
			// summataan varastonarvoa
			$varvo = $varvo + $apu;
			
			if ($naytarivit != "") {
	   			$ulos .= "$row[osasto]\t";
	   			$ulos .= "$row[try]\t";
	   			$ulos .= "$row[tuoteno]\t";
	   			$ulos .= "$row[nimitys]\t";
	   			$ulos .= str_replace(".",",",$saldo)."\t";
	   			$ulos .= str_replace(".",",",$arvo)."\t";
	   			$ulos .= str_replace(".",",",$apu)."\r\n";
			}
			
		} // end saldo
		
	} // end while

	if ($naytarivit != "") {

		// lähetetään meili
		$bound = uniqid(time()."_") ;

		$header  = "From: <mailer@pupesoft.com>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content = "--$bound\r\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\r\n\r\n";

		$content .= chunk_split(base64_encode($ulos));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Varastonarvo"), $content, $header);

		echo "<font class='message'>".t("Lähetetään sähköposti");
		if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui!")."<br>";
		else echo " $kukarow[eposti].<br>";
		echo "</font><br>";
	}

	if ($virhe > 0) {
		echo "<font class='message'>Käytettiin $virhe:ssa tuotteessa tämän hetkistä keskihankintahintaa, koska tapahtumista löytynyt hinta vippasi.</font><br><br>";
	}
		
	echo "<table>";
	echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
	echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
	echo "</table>";
			
}

require ("../inc/footer.inc");

?>
