<?php
///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Asiakkaan perustiedot")."</font><hr>";

echo "<form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna asiakasnumero tai osa nimest‰")."</th>";
echo "<td><input type='text' name='ytunnus'></td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'>";
echo "</tr></table>";
echo "</form>";

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

	// tehd‰‰n asiakkaan ostot kausittain, sek‰ pylv‰‰t niihin...
	echo "<br><font class='message'>".t("Myynti kausittain viimeiset 24 kk")." (<font class='myynti'>".t("myynti")."</font>/<font class='kate'>".t("kate")."</font>/<font class='katepros'>".t("kateprosentti")."</font>)</font>";
	echo "<hr>";

	// 24 kk sitten
	$ayy = date("Y-m-01",mktime(0, 0, 0, date("m")-24, date("d"), date("Y")));

	$query  = "	select date_format(tapvm,'%Y/%m') kausi,
				round(sum(arvo),0) myynti,
				round(sum(kate),0) kate,
				round(sum(kate)/sum(arvo)*100,1) katepro
				from lasku use index (yhtio_tila_liitostunnus_tapvm)
				where yhtio='$kukarow[yhtio]'
				and liitostunnus='$asiakasid'
				and tila='U'
				and tapvm>='$ayy'
				group by 1
				having myynti<>0 or kate<>0";
	$result = mysql_query($query) or pupe_error($query);

	// otetaan suurin myynti talteen
	$maxeur=0;
	while ($sumrow = mysql_fetch_array($result)) {
		if ($sumrow['myynti']>$maxeur) $maxeur=$sumrow['myynti'];
		if ($sumrow['kate']>$maxeur)   $maxeur=$sumrow['kate'];
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
		<td valign='bottom' align='center'><img src='../pics/blue.png' height='$hmyynti' width='12' alt='".t("myynti")." $sumrow[myynti]'></td>
		<td valign='bottom' align='center'><img src='../pics/orange.png' height='$hkate' width='12' alt='".t("kate")." $sumrow[kate]'></td>
		<td valign='bottom' align='center'><img src='../pics/green.png' height='$hkatepro' width='12' alt='".t("katepro")." $sumrow[katepro] %'></td>
		</tr></table>";

		if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
		echo "<td valign='bottom' class='back'>";

		echo "<table width='60'>";
		echo "<tr><td nowrap align='center' height='55' valign='bottom'>$pylvaat</td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'>$sumrow[kausi]</font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='myynti'>$sumrow[myynti]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='kate'>$sumrow[kate]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='katepros'>$sumrow[katepro] %</font></font></td></tr>";
		echo "</table>";

		echo "</td>\n";

		if ($col==$maxcol) {
			echo "</tr>\n";
			$col=0;
		}

		$col++;
	}

	// teh‰‰n validia htmll‰‰ ja t‰ytet‰‰n tyhj‰t solut..
	$ero = $maxcol+1-$col;

	if ($ero<>$maxcol)
		echo "<td colspan='$ero' class='back'></td></tr>\n";

	echo "</table>";


	// tehd‰‰n asiakkaan ostot tuoteryhmitt‰in... vikat 12 kk
	echo "<br><font class='message'>".t("Myynti osastoittain tuoteryhmitt‰in viimeiset 12 kk")." (<font class='myynti'>".t("myynti")."</font>/<font class='kate'>".t("kate")."</font>/<font class='katepros'>".t("kateprosentti")."</font>)</font>";
	echo "<hr>";

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<br>".t("N‰yt‰/piilota osastojen ja tuoteryhmien nimet.");
	echo "<input type ='hidden' name='ytunnus' value='$ytunnus'>";

	if ($nimet == 'nayta') {
		$sel = "CHECKED";
	}
	else {
		$sel = "";
	}

	echo "<input type='checkbox' name='nimet' value='nayta' onClick='submit()' $sel>";
	echo "</form>";



	// alkukuukauden tiedot 12 kk sitten
	$ayy = date("Y-m-01",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));

	$query = "	select osasto, try, round(sum(rivihinta),0) myynti, round(sum(tilausrivi.kate),0) kate, round(sum(kpl),0) kpl, round(sum(tilausrivi.kate)/sum(rivihinta)*100,1) katepro
				from lasku use index (yhtio_tila_liitostunnus_tapvm), tilausrivi use index (uusiotunnus_index)
				where lasku.yhtio='$kukarow[yhtio]'
				and lasku.liitostunnus='$asiakasid'
				and lasku.tila='U'
				and lasku.alatila='X'
				and lasku.tapvm>='$ayy'
				and tilausrivi.yhtio=lasku.yhtio
				and tilausrivi.uusiotunnus=lasku.tunnus
				group by 1,2
				having myynti<>0 or kate<>0
				order by osasto+0,try+0";
	$result = mysql_query($query) or pupe_error($query);

	$col=1;
	echo "<table>\n";

	if ($nimet == 'nayta') {
		$maxcol = $maxcol/2;
	}


	while ($sumrow = mysql_fetch_array($result)) {

		if ($col==1) echo "<tr>\n";

		if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';

		echo "<td valign='bottom' class='back'>";

		$query = "	select selite, selitetark
					from avainsana
					where yhtio	= '$kukarow[yhtio]'
					and laji	= 'try'
					and selite	= '$sumrow[try]'";
		$avainresult = mysql_query($query) or pupe_error($query);
		$tryrow = mysql_fetch_array($avainresult);

		$query = "	select selite, selitetark
					from avainsana
					where yhtio	= '$kukarow[yhtio]'
					and laji	= 'osasto'
					and selite	= '$sumrow[osasto]'";
		$avainresult = mysql_query($query) or pupe_error($query);
		$osastorow = mysql_fetch_array($avainresult);

		if ($nimet == 'nayta') {
			$ostry = $osastorow["selitetark"]."<br>".$tryrow["selitetark"];
		}
		else {
			$ostry = $sumrow["osasto"]."/".$sumrow["try"];
		}


		echo "<table width='100%'>";
		echo "<tr><th nowrap align='right'><a href='tuorymyynnit.php?ytunnus=$ytunnus&try=$sumrow[try]&osasto=$sumrow[osasto]'><font class='info'>$ostry</font></a></th></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='myynti'>$sumrow[myynti] $yhtiorow[valkoodi]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='myynti'>$sumrow[kpl] ".t("kpl")."</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='kate'>$sumrow[kate]   $yhtiorow[valkoodi]</font></font></td></tr>";
		echo "<tr><td nowrap align='right'><font class='info'><font class='katepros'>$sumrow[katepro] %</font></font></td></tr>";
		echo "</table>";

		echo "</td>\n";

		if ($col==$maxcol) {
			echo "</tr>\n";
			$col=0;
		}

		$col++;
	}

	// teh‰‰n validia htmll‰‰ ja t‰ytet‰‰n tyhj‰t solut..
	$ero = $maxcol+1-$col;

	if ($ero<>$maxcol)
		echo "<td colspan='$ero' class='back'></td></tr>\n";

	echo "</table>";

	echo "<br><font class='message'>".t("Asiakkaan alennusryhm‰t, alennustaulukko ja alennushinnat")."</font><hr>";

	if ($asale!='') {
		// tehd‰‰n asiakkaan alennustaulukot
		$query = "select * from perusalennus where yhtio='$kukarow[yhtio]' order by ryhma";
		$result = mysql_query($query) or pupe_error($query);

		$asale  = "<table>";
		$asale .= "<tr><th>".t("Ytunnus")."/<br>".t("AS-Ryhm‰")."</th><th>".t("Tuoteno")."/<br>".t("Aleryhm‰")."</th><th>".t("Prosentti")."</th><th>".t("Alkupvm")."</th><th>".t("Loppupvm")."</th><th>".t("Tyyppi")."</th></tr>";

		while ($alerow = mysql_fetch_array($result)) {

			$mita  = "Perus";
			$ryhma = $alerow['ryhma'];
			$ale   = $alerow['alennus'];
			$showytunnus = 'PERUS';

			

			if ($ale != 0.00) {
				$asale .= "<tr>
					<td><font class='info'>$showytunnus	<font></td>
					<td><font class='info'>$ryhma	<font></td>
					<td><font class='info'>$ale		<font></td>
					<td><font class='info'>----------<font></td>
					<td><font class='info'>----------<font></td>
					<td><font class='info'>$mita	<font></td>
					</tr>";
			}
		}
		
		$query = "select * from asiakasalennus where yhtio='$kukarow[yhtio]' and (ytunnus='$ytunnus' or asiakas_ryhma = '$asiakasrow[ryhma]') order by asiakas_ryhma, ytunnus, ryhma, tuoteno";
		$asres = mysql_query($query) or pupe_error($query);

		while ($asrow = mysql_fetch_array($asres)) {
			$mita  = "<font class='katepros'>".t("Asiakas")."</font>";
			
			if ($asrow['asiakas_ryhma'] != '') {
				$showytunnus = "(RY) ".$asrow['asiakas_ryhma'];
			}
			else {
				$showytunnus = $asrow['ytunnus'];
			}
			
			if ($asrow['ryhma'] != '') {
				$showryhma = "(RY) ".$asrow['ryhma'];
			}
			else {
				$showryhma = $asrow['tuoteno'];
			}
			
			$ryhma = $asrow['ryhma'];
			$ale   = $asrow['alennus'];
			
			$asale .= "<tr>
				<td><font class='info'>$showytunnus	<font></td>
				<td><font class='info'>$showryhma	<font></td>
				<td><font class='info'>$ale		<font></td>
				<td><font class='info'>$asrow[alkupvm]<font></td>
				<td><font class='info'>$asrow[loppupvm]<font></td>
				<td><font class='info'>$mita	<font></td>
				</tr>";
		}
		
		$asale .= "</table>";
	}
	else {
		$asale = "<a href='$PHP_SELF?ytunnus=$ytunnus&asale=kylla'>".t("N‰yt‰ aletaulukko")."</a>";
	}

	if ($ashin!='') {
		// haetaan asiakas hintoaja
		$ashin  = "<table>";
		$ashin .= "<tr><th>".t("Ytunnus")."/<br>".t("AS-Ryhm‰")."</th><th>".t("Tuoteno")."/<br>".t("Aleryhm‰")."</th><th>".t("Hinta")."</th><th>".t("Alkupvm")."</th><th>".t("Loppupvm")."</th></tr>";

		$query = "select * from asiakashinta where yhtio='$kukarow[yhtio]' and (ytunnus='$ytunnus' or asiakas_ryhma = '$asiakasrow[ryhma]') order by asiakas_ryhma, ytunnus, ryhma, tuoteno";
		$asres = mysql_query($query) or pupe_error($query);

		while ($asrow = mysql_fetch_array($asres)) {

			if ($asrow['asiakas_ryhma'] != '') {
				$showytunnus = "(RY) ".$asrow['asiakas_ryhma'];
			}
			else {
				$showytunnus = $asrow['ytunnus'];
			}

			if ($asrow['ryhma'] != '') {
				$showryhma = "(RY) ".$asrow['ryhma'];
			}
			else {
				$showryhma = $asrow['tuoteno'];
			}

			$ryhma = $asrow['ryhma'];
			$hinta   = $asrow['hinta'];

			$ashin .= "<tr>
				<td><font class='info'>$showytunnus	<font></td>
				<td><font class='info'>$showryhma	<font></td>
				<td><font class='info'>$hinta		<font></td>
				<td><font class='info'>$asrow[alkupvm]<font></td>
				<td><font class='info'>$asrow[loppupvm]<font></td>
				</tr>";
		}

		$ashin .= "</table>";
	}
	else {
		$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&ashin=kylla'>".t("N‰yt‰ asiakashinnat")."</a>";
	}

	if ($aletaulu!='') {
		// tehd‰‰n asiakkaan alennustaulukko...
		$aletaulu  = "<table>";
		$aletaulu .= "<tr><th>".t("Os")."</th><th>".t("Osasto")."</th><th>".t("Tryno")."</th><th>".t("Tuoteryhm‰")."</th><th>".t("Ytunnus")."/<br>".t("AS-Ryhm‰")."</th><th>".t("Tuoteno")."/<br>".t("Aleryhm‰")."</th><th>".t("Prosentti")."</th><th>".t("Alkupvm")."</th><th>".t("Loppupvm")."</th><th>".t("Tyyppi")."</th></tr>";

		$query = "(select osasto, try, aleryhma, asiakasalennus.tuoteno, ryhma, ytunnus, asiakas_ryhma, alennus, alkupvm, loppupvm
					from asiakasalennus, tuote
					where asiakasalennus.yhtio = tuote.yhtio
					and asiakasalennus.yhtio='$kukarow[yhtio]'
					and asiakasalennus.ytunnus = '$ytunnus'
					and if(asiakasalennus.tuoteno != '', asiakasalennus.tuoteno = tuote.tuoteno, asiakasalennus.ryhma = tuote.aleryhma)
					and status in ('', 'A')
					and osasto != 0
					and try != 0
					group by 1,2,3,4,5,6,7,8,9,10)
					UNION
					(select osasto, try, aleryhma, asiakasalennus.tuoteno, ryhma, ytunnus, asiakas_ryhma, alennus, alkupvm, loppupvm
					from asiakasalennus, tuote
					where asiakasalennus.yhtio = tuote.yhtio
					and asiakasalennus.yhtio='$kukarow[yhtio]'
					and asiakasalennus.asiakas_ryhma = '$asiakasrow[ryhma]'
					and if(asiakasalennus.tuoteno != '', asiakasalennus.tuoteno = tuote.tuoteno, asiakasalennus.ryhma = tuote.aleryhma)
					and status in ('', 'A')
					and osasto != 0
					and try != 0
					group by 1,2,3,4,5,6,7,8,9,10)
					UNION
					(select osasto, try, aleryhma, '1' as tuoteno, ryhma, '1' as ytunnus, '1' as asiakas_ryhma, alennus, 'perus' as alkupvm, '1' as loppupvm
					from perusalennus, tuote
					where 
					perusalennus.yhtio = tuote.yhtio
					and perusalennus.ryhma = tuote.aleryhma
					and perusalennus.yhtio = '$kukarow[yhtio]' 
					and alennus > 0
					and status in ('', 'A')
					and osasto != 0
					and try != 0
					group by 1,2,3,4,5,6,7,8,9,10)
					order by osasto+0, try+0, aleryhma";
		$result = mysql_query($query) or pupe_error($query);
		
		while ($alerow = mysql_fetch_array($result)) {
			if ($alerow['alkupvm'] == 'perus') {
					$mita   = "Perus";
					$alerow['ytunnus'] = '';
					$ale    = $alerow['alennus'];
					$alerow['alkupvm'] = '----------';
					$alerow['loppupvm'] = '----------';
					
			}
			else {
				$mita   = "<font class='katepros'>".t("Asiakas")."</font>";
				$ale    = $alerow['alennus'];
				if ($alerow['ytunnus'] == '') {
					$alerow['ytunnus'] = "(RY) ".$alerow['asiakas_ryhma'];
				}
				if ($alerow['tuoteno'] == '') {
					$alerow['aleryhma'] = "(RY) ".$alerow['aleryhma'];
				}
				else {
					$alerow['aleryhma'] = $alerow['tuoteno'];
				}
			}

			$query = "select selitetark from avainsana where yhtio='$kukarow[yhtio]' and laji='try' and selite='$alerow[try]'";
			$tryre = mysql_query($query) or pupe_error($query);
			$tryro = mysql_fetch_array($tryre);

			$query = "select selitetark from avainsana where yhtio='$kukarow[yhtio]' and laji='osasto' and selite='$alerow[osasto]'";
			$osare = mysql_query($query) or pupe_error($query);
			$osaro = mysql_fetch_array($osare);
			
			// n‰ytet‰‰n rivi vaan jos ale on olemassa ja se on erisuuri kuin nolla. Lis‰ksi tuoteryhm‰ll‰ on pakko olla nimi...
			if ($ale != "" and $ale != 0.00) {
				$aletaulu .= "<tr>
					<td><font class='info'>$alerow[osasto]<font></td>
					<td><font class='info'>$osaro[selitetark]<font></td>
					<td><font class='info'>$alerow[try]<font></td>
					<td><font class='info'>$tryro[selitetark]<font></td>
					<td><font class='info'>$alerow[ytunnus]</td>
					<td><font class='info'>$alerow[aleryhma]</td>
					<td><font class='info'>$ale<font></td>
					<td><font class='info'>$alerow[alkupvm]</td>
					<td><font class='info'>$alerow[loppupvm]</td>
					<td><font class='info'>$mita<font></td>
					</tr>";
			}
		}

		$aletaulu .= "</table>";
	}
	else {
		$aletaulu = "<a href='$PHP_SELF?ytunnus=$ytunnus&aletaulu=kylla'>".t("N‰yt‰ alennustaulukot")."</a>";
	}

	// piirret‰‰n ryhmist‰ ja hinnoista taulukko..
	echo "<table><tr>
			<td valign='top' class='back'>$asale</td>
			<td class='back'></td>
			<td valign='top' class='back'>$aletaulu</td>
			<td class='back'></td>
			<td valign='top' class='back'>$ashin</td>
		</tr></table>";

}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>