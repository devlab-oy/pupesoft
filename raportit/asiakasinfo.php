<?php

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

if ($tee == 'eposti') {

	if ($komento == '') {
		$tulostimet[] = "Alennustaulukko";
		$toimas = $ytunnus;
		require("../inc/valitse_tulostin.inc");
	}
	
	$ytunnus = $toimas;

	require('pdflib/phppdflib.class.php');

	// defaultteja
	$lask = 1;
	$sivu = 1;

	function alku () {
		global $yhtiorow, $firstpage, $pdf, $sivu, $rectparam, $norm, $pieni, $ytunnus, $kukarow, $kala, $tid, $otsikkotid;
		
		if(!isset($pdf)) {
			//PDF parametrit
			$pdf = new pdffile;
			$pdf->enable('template');	
			
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
			$rectparam["width"] = 0.3;
			
			$norm["height"] = 12;
			$norm["font"] = "Courier";

			$norm_bold["height"] = 12;
			$norm_bold["font"] = "Courier-Bold";

			$pieni["height"] = 8;
			$pieni["font"] = "Courier";
			
			$query =  "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus'";
			$assresult = mysql_query($query) or pupe_error($query);
			$assrow = mysql_fetch_array($assresult);
			
			// Tehd‰‰n firstpage
			$firstpage = $pdf->new_page("a4");
			
			//	Tehd‰‰n headertemplate
			$tid=$pdf->template->create();
			$pdf->template->rectangle($tid, 20, 20,  0, 580, $rectparam);
			$pdf->template->text($tid, 30,  5, $yhtiorow["nimi"], $pieni);
			$pdf->template->text($tid, 120, 5, t("Asiakkaan")." ($ytunnus) $assrow[nimi] $assrow[nimitark] ".t("alennustaulukko"));
			$pdf->template->text($tid, 500, 5, t("Sivu").": $sivu", $pieni);
			$pdf->template->place($tid, $firstpage, 0, 800);
			
			//	Tehd‰‰n otsikkoheader
			$otsikkotid=$pdf->template->create();			
			$pdf->template->text($otsikkotid, 30,  0, t("Osasto"), $norm_bold);
			$pdf->template->text($otsikkotid, 130, 0, t("Tuoteryh"), $norm_bold);
			$pdf->template->text($otsikkotid, 250, 0, t("Selite"), $norm_bold);
			$pdf->template->text($otsikkotid, 420, 0, t("Aleryhm‰"), $norm_bold);
			$pdf->template->text($otsikkotid, 520, 0, t("Alennus"), $norm_bold);
			$pdf->template->place($otsikkotid, $firstpage, 0, 675, $norm_bold);
			$kala = 660;			
			
			//	Asiakastiedot
			//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
			$pdf->draw_text(50, 759, t("Osoite", $kieli), 	$firstpage, $pieni);
			$pdf->draw_text(50, 747, $assrow["nimi"], 		$firstpage, $norm);
			$pdf->draw_text(50, 737, $assrow["nimitark"],	$firstpage, $norm);
			$pdf->draw_text(50, 727, $assrow["osoite"], 	$firstpage, $norm);
			$pdf->draw_text(50, 717, $assrow["postino"]." ".$assrow["postitp"], $firstpage, $norm);
			$pdf->draw_text(50, 707, $assrow["maa"], 		$firstpage, $norm);
		}
		else {
			
			//	Liitet‰‰n vaan valmiit templatet uudelle sivulle
			$firstpage = $pdf->new_page("a4");
			$pdf->template->place($tid, $firstpage, 0, 800);
			$pdf->template->place($otsikkotid, $firstpage, 0, 770);
		}
	}

	function rivi ($firstpage, $osasto, $try, $nimi, $ryhma, $ale) {
		global $pdf, $kala, $sivu, $lask, $rectparam, $norm, $pieni;

		if (($sivu == 1 and $lask == 43) or ($sivu > 1 and $lask == 50)) {
			$sivu++;
			$firstpage = alku();
			$kala = 770;
			$lask = 1;
		}

		$pdf->draw_text(30,  $kala, $osasto, 									$firstpage, $norm);
		$pdf->draw_text(130, $kala, substr($try,0,16), 										$firstpage, $norm);
		$pdf->draw_text(250, $kala, $nimi, 										$firstpage, $norm);
		$pdf->draw_text(410, $kala, sprintf('%10s',sprintf('%.2d',$ryhma)), 	$firstpage, $norm);
		$pdf->draw_text(490, $kala, sprintf('%10s',sprintf('%.2d',$ale))."%", 	$firstpage, $norm);


		$kala = $kala - 15;
		$lask++;
	}

	//tehd‰‰n eka sivu
	alku();
}

echo "<font class='head'>".t("Asiakkaan perustiedot")."</font><hr><br><br>";

//	Jos tullaan muualta ei anneta valita uutta asiakasta
if($lopetus=="") {
	echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
	echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
	echo "<table><tr>";
	echo "<th>".t("Anna asiakasnumero tai osa nimest‰")."</th>";
	echo "<td><input type='text' name='ytunnus'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'>";
	echo "</tr></table>";
	echo "</form><br><br>";	
}

if ($ytunnus!='') {
	require ("../inc/asiakashaku.inc");
}

// jos meill‰ on onnistuneesti valittu asiakas
if ($ytunnus!='') {

	echo "<table><tr>";
	echo "<th valign='top'>".t("ytunnus")."</th>";
	echo "<th valign='top'>".t("asnro")."</th>";
	echo "<th valign='top'>".t("nimi")."<br>".t("osoite")."</th>";
	echo "<th valign='top'>".t("toim_nimi")."<br>".t("toim_osoite")."</th>";
	echo "</tr><tr>";
	echo "<td valign='top'>$asiakasrow[ytunnus]</td>";
	echo "<td valign='top'>$asiakasrow[asiakasnro]</td>";
	echo "<td valign='top'>$asiakasrow[nimi]<br>$asiakasrow[osoite]<br>$asiakasrow[postino] $asiakasrow[postitp]</td>";
	echo "<td valign='top'>$asiakasrow[toim_nimi]<br>$asiakasrow[toim_osoite]<br>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td>";
	echo "</tr></table><br><br>";


	// hardcoodataan v‰rej‰
	//$cmyynti = "#ccccff";
	//$ckate   = "#ff9955";
	//$ckatepr = "#00dd00";
	$maxcol  = 12; // montako columnia n‰yttˆ on

	if($lopetus == "") {
		if($rajaus=="MYYNTI") $sel["M"] = "checked";
		elseif($rajaus=="ALENNUKSET") $sel["A"] = "checked";		
		else $sel["K"] = "checked";
		
		echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
		echo "<input type = 'hidden' name = 'ytunnus' value = '$ytunnus'>";		
		echo "<input type = 'hidden' name = 'asiakasid' value = '$asiakasid'>";				
		echo "<table><tr>";
		echo "<th>".t("Rajaa n‰kym‰‰")."</th></tr>";
		echo "<tr><td><input type='radio' name='rajaus' value='' onclick='submit()' $sel[K]>".t("Kaikki")." <input type='radio' name='rajaus' value='MYYNTI' onclick='submit()' $sel[M]>".t("Myynti")." <input type='radio' name='rajaus' value='ALENNUKSET' onclick='submit()' $sel[A]>".t("Alennukset")."</td></tr>";
		echo "</table>";
		echo "</form>";	
	}

	if($rajaus=="" or $rajaus=="MYYNTI") {
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
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
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

			$query = "	select avainsana.selite, ".avain('select')."
						from avainsana
						".avain('join','TRY_')."
						where avainsana.yhtio	= '$kukarow[yhtio]'
						and avainsana.laji	= 'try'
						and avainsana.selite	= '$sumrow[try]'";
			$avainresult = mysql_query($query) or pupe_error($query);
			$tryrow = mysql_fetch_array($avainresult);

			$query = "	select avainsana.selite, ".avain('select')."
						from avainsana
						".avain('join','OSASTO_')."
						where avainsana.yhtio	= '$kukarow[yhtio]'
						and avainsana.laji	= 'osasto'
						and avainsana.selite	= '$sumrow[osasto]'";
			$avainresult = mysql_query($query) or pupe_error($query);
			$osastorow = mysql_fetch_array($avainresult);

			if ($nimet == 'nayta') {
				$ostry = $osastorow["selitetark"]."<br>".$tryrow["selitetark"];
			}
			else {
				$ostry = $sumrow["osasto"]."/".$sumrow["try"];
			}


			echo "<table width='100%'>";
			echo "<tr><th nowrap align='right'><a href='tuorymyynnit.php?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&try=$sumrow[try]&osasto=$sumrow[osasto]'><font class='info'>$ostry</font></a></th></tr>";
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

		echo "</table><br>";

		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'raportit/myyntiseuranta.php'
					and alanimi = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			// P‰iv‰m‰‰r‰t rappareita varten
			$kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$kkl = date("m");
			$vvl = date("Y");
			$ppl = date("d");

			$asiakasid		= $asiakasrow["tunnus"];
			$asiakas		= $ytunnus;
			$tee			= "go";
			$tuoteosasto2	= "kaikki";
			$yhtiot[]		= $kukarow["yhtio"];
			
			//	Huijataan myyntiseurantaa
			if($lopetus == "") $lopetus = "block";
			
			echo "<br><font class='message'>".t("Myyntiseuranta")."</font>";
			echo "<hr>";
			require("myyntiseuranta.php");
			
			if($lopetus == "block") $lopetus = "";
			
			//	Korjataan ytunnus
			$ytunnus 	= $asiakas;
		}
	}

	if($rajaus == "" or $rajaus == "ALENNUKSET") {
		echo "<br><font class='message'>".t("Asiakkaan alennusryhm‰t, alennustaulukko ja alennushinnat")."</font><hr>";
		echo "<br><a href='$PHP_SELF?tee=eposti&ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&lopetus=$lopetus'>".t("Tulosta alennustaulukko")."</a><br><br>";
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

				if ($tee == 'eposti') {
					rivi($firstpage);
				}
			}

			$asale .= "</table>";
		}
		else {
			$asale = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&lopetus=$lopetus'>".t("N‰yt‰ aletaulukko")."</a>";
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
			$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&lopetus=$lopetus'>".t("N‰yt‰ asiakashinnat")."</a>";
		}

		if ($aletaulu!='' or $tee == 'eposti') {
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

				$query = "	select avainsana.selite, ".avain('select')."
							from avainsana
							".avain('join','TRY_')."
							where avainsana.yhtio	= '$kukarow[yhtio]'
							and avainsana.laji	= 'try'
							and avainsana.selite	= '$alerow[try]'";
				$tryre = mysql_query($query) or pupe_error($query);
				$tryro = mysql_fetch_array($tryre);

				$query = "	select avainsana.selite, ".avain('select')."
							from avainsana
							".avain('join','OSASTO_')."
							where avainsana.yhtio	= '$kukarow[yhtio]'
							and avainsana.laji	= 'osasto'
							and avainsana.selite	= '$alerow[osasto]'";
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

					if ($tee == 'eposti') {
						rivi ($firstpage, $osaro["selitetark"], $tryro["selitetark"], $alerow["ytunnus"], $alerow["aleryhma"], $ale);
					}
				}
			}

			$aletaulu .= "</table>";
		}
		else {
			$aletaulu = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&lopetus=$lopetus'>".t("N‰yt‰ alennustaulukot")."</a>";
		}

		// piirret‰‰n ryhmist‰ ja hinnoista taulukko..
		echo "<table><tr>
				<td valign='top' class='back'>$asale</td>
				<td class='back'></td>
				<td valign='top' class='back'>$aletaulu</td>
				<td class='back'></td>
				<td valign='top' class='back'>$ashin</td>
			</tr></table>";

		if ($tee == 'eposti') {
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/Aletaulukko-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("".t("PDF kirjoitus ep‰onnistui")." $pdffilenimi");
			fclose($fh);

			// itse print komento...
			if ($komento["Alennustaulukko"] == 'email') {
				$liite = $pdffilenimi;
				$kutsu = "Alennustaulukko";

				require("../inc/sahkoposti.inc");
			}
			elseif ($komento["Alennustaulukko"] != '' and $komento["Alennustaulukko"] != 'edi') {
				$line = exec("$komento[Alennustaulukko] $pdffilenimi");
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}		
	}	
}


if ($lopetus != '') {
	// Jotta urlin parametrissa voisi p‰‰ss‰t‰ toisen urlin parametreineen
	$lopetus = str_replace('////','?', $lopetus);
	$lopetus = str_replace('//','&',  $lopetus);
	echo "<br><br>";
	echo "<a href='$lopetus'>".t("Palaa edelliseen n‰kym‰‰n")."</a><br>";	
}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>