<?php

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

if ($tee == 'eposti') {

	if ($komento == '') {
		$tulostimet[] = "Alennustaulukko";
		$toimas = $ytunnus;
		require("../inc/valitse_tulostin.inc");
	}
	
	$ytunnus = $toimas;

	require('pdflib/phppdflib.class.php');

	// defaultteja
	$sivu = 1;

	function alku () {
		global $yhtiorow, $firstpage, $pdf, $sivu, $rectparam, $norm, $norm_bold, $pieni, $ytunnus, $kukarow, $kala, $tid, $otsikkotid;
		
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
			$pdf->template->text($tid, 170, 5, "$assrow[nimi] $assrow[nimitark] ($ytunnus) ".t("alennustaulukko"));
			$pdf->template->text($tid, 500, 5, t("Sivu").": $sivu", $pieni);
			$pdf->template->place($tid, $firstpage, 0, 800);
			
			//	Tehd‰‰n otsikkoheader
			$otsikkotid=$pdf->template->create();
			$pdf->template->text($otsikkotid, 30,  20, t("Osasto"), $norm_bold);						
			$pdf->template->text($otsikkotid, 30,   0, t("Tuoteryhm‰")."/".t("Tuotenumero"), $norm_bold);
			$pdf->template->text($otsikkotid, 330,  0, t("Aleryhm‰"), $norm_bold);
			$pdf->template->text($otsikkotid, 520,  0, t("Alennus"), $norm_bold);
			$pdf->template->place($otsikkotid, $firstpage, 0, 665, $norm_bold);
			$kala = 650;			
			
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
			$pdf->template->place($otsikkotid, $firstpage, 0, 760);
		}
	}

	function rivi ($firstpage, $osasto, $try, $tuote, $ryhma, $ale) {
		global $pdf, $kala, $sivu, $rectparam, $norm, $norm_bold, $pieni;
		
		static $edosasto;

		if ($kala < 40) {
			$sivu++;
			$firstpage = alku();
			$kala = 760;
		}
		
		//	Vaihdetaan osastoa
		if($osasto != $edosasto) {
			$kala -= 15;						
			$pdf->draw_text(30,  $kala, $osasto, 									$firstpage, $norm_bold);
			$kala -= 25;			
		}
		
		$edosasto = $osasto;
		
		if($tuote == " - ") {
			$pdf->draw_text(30,  $kala, $try, 										$firstpage, $norm);
		}
		else {
			$pdf->draw_text(60, $kala, $tuote, 									$firstpage, $norm);
		}
		$pdf->draw_text(310, $kala, sprintf('%10s',$ryhma), 	$firstpage, $norm);
		$pdf->draw_text(490, $kala, sprintf('%10s',sprintf('%.2d',$ale))."%", 	$firstpage, $norm);

		$kala -= 15;
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
	
	if($tee != "eposti") {
		if(include('Spreadsheet/Excel/Writer.php')) {
			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook_ale = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$worksheet =& $workbook_ale->addWorksheet(t('Asiakkaan alennukset'));

			$format_bold =& $workbook_ale->addFormat();
			$format_bold->setBold();

			$format_percent =& $workbook_ale->addFormat();
			$format_percent->setNumFormat('0.00%');

			$format_date =& $workbook_ale->addFormat();
			$format_date->setNumFormat('YYYY-MM-DD');

			$format_curr =& $workbook_ale->addFormat();
			$format_curr->setNumFormat('0.00');

			$excelrivi = 0;
		}		
	}
	
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
			<td style='vertical-align: bottom; text-align: center;'><img src='../pics/blue.png' height='$hmyynti' width='12' alt='".t("myynti")." $sumrow[myynti]'></td>
			<td style='vertical-align: bottom; text-align: center;'><img src='../pics/orange.png' height='$hkate' width='12' alt='".t("kate")." $sumrow[kate]'></td>
			<td style='vertical-align: bottom; text-align: center;'><img src='../pics/green.png' height='$hkatepro' width='12' alt='".t("katepro")." $sumrow[katepro] %'></td>
			</tr></table>";

			if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
			echo "<td class='back' style='vertical-align: bottom;'>";

			echo "<table width='60'>";
			echo "<tr><td nowrap align='center' height='55' style='vertical-align: bottom;'>$pylvaat</td></tr>";
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
						and avainsana.laji	= 'TRY'
						and avainsana.selite	= '$sumrow[try]'";
			$avainresult = mysql_query($query) or pupe_error($query);
			$tryrow = mysql_fetch_array($avainresult);

			$query = "	select avainsana.selite, ".avain('select')."
						from avainsana
						".avain('join','OSASTO_')."
						where avainsana.yhtio	= '$kukarow[yhtio]'
						and avainsana.laji	= 'OSASTO'
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
			
			$otee = $tee;
			$oasiakasrow = $asiakasrow;
			
			$asiakasid		= $asiakasrow["tunnus"];
			$asiakas		= $ytunnus;
			$tee			= "go";
			$tuoteosasto2	= "kaikki";
			$yhtiot[]		= $kukarow["yhtio"];
			$jarjestys[1]	= "on";
			
			//	Huijataan myyntiseurantaa
			if($lopetus == "") $lopetus = "block";
			

			require("myyntiseuranta.php");
			
			if($lopetus == "block") $lopetus = "";
			
			//	Korjataan ytunnus ja tee
			$ytunnus 	= $asiakas;
			$tee 		= $otee;
			$asiakasrow = $oasiakasrow;
		}
	}
	
	$yhdistetty_array = array();
	if($rajaus == "" or $rajaus == "ALENNUKSET") {
		
		if($rajaus == "") {
			echo "<a href='#' name='alennukset'></a>";
		}
		
		echo "<br><font class='message'>".t("Asiakkaan alennusryhm‰t, alennustaulukko ja alennushinnat")."</font><hr>";
		
		if($kukarow["extranet"] == "") {
			$sela = $selb = "";
			if($rajattunakyma != "JOO") {
				$sela = "checked";
			}
			else {
				$selb = "checked";
			}
			echo "<form method='post' action='$PHP_SELF#alennukset'>";
			echo "<input type='hidden' name='asiakasid' value = '$asiakasid'>";
			echo "<input type='hidden' name='ytunnus' value = '$ytunnus'>";
			echo "<input type='hidden' name='rajaus' value = '$rajaus'>";
			echo "<input type='hidden' name='asale' value = '$asale'>";						
			echo "<input type='hidden' name='ashin' value = '$ashin'>";						
			echo "<input type='hidden' name='aletaul' value = '$aletaul'>";
			echo "<input type='hidden' name='yhdistetty' value = '$yhdistetty'>";						
			echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='' $sela> ".t("Normaalin‰kym‰"); 
			echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='JOO' $selb> ".t("Extranetn‰kym‰");
			echo "</form><br>";
		}
		
		echo "<br><a href='$PHP_SELF?tee=eposti&ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Tulosta alennustaulukko")."</a><br><br>";
		
		if ($asale!='' or $aletaulu!='' or $yhdistetty != "" or $tee == "eposti") {
			
			$taulu  = "";
			
			if($aletaulu != "" or $tee == "eposti") {
				$tuotejoin = "	LEFT JOIN tuote ON tuote.yhtio=perusalennus.yhtio and tuote.aleryhma=perusalennus.ryhma and osasto != 0 and try != 0";
				$tuotegroup = "GROUP BY try, osasto, aleryhma";
				$tuotecols = ", osasto, try";
				$order = "osasto+0, try+0, alennusryhm‰+0, tuoteno, prio";
			}
			else {
				$tuotejoin = "";
				$tuotegroup = "";
				$tuotecols = "";
				$order = "alennusryhm‰+0 , prio , asiakasryhm‰";				
			}
			
			$query = "
						/*	Asiakasalennus ytunnuksella	tuoteno*/	
						(							
							SELECT '1' prio,
								asiakasalennus.alennus,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi, 
								'' asiakasryhm‰,
								'' asiakasryhm‰_nimi,
								asiakasalennus.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakasale' tyyppi $tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma						
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							LEFT JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno
							WHERE asiakasalennus.yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus' and asiakas_ryhma='' and asiakasalennus.tuoteno!=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						)
						UNION
						/*	Asiakasalennus ytunnuksella	 ryhma*/	
						(							
							SELECT '2' prio,
								asiakasalennus.alennus,
								'' tuoteno, 
								'' tuoteno_nimi,
								'' asiakasryhm‰,
								'' asiakasryhm‰_nimi,
								asiakasalennus.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakasale' tyyppi $tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma						
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus' and asiakas_ryhma='' and asiakasalennus.tuoteno=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							$tuotegroup
						)
						UNION

						/*	Asiakasalennus Ryhm‰ll‰	 tuote */
						(

							SELECT '3' prio,
								asiakasalennus.alennus,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm‰,
								avainsana.selitetark asiakasryhm‰_nimi,
								asiakasalennus.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakasryhm‰ale' tyyppi $tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma						
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							LEFT JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno
							WHERE asiakasalennus.yhtio='$kukarow[yhtio]' and asiakas_ryhma = '$asiakasrow[ryhma]' and asiakas_ryhma != '' and ytunnus='' and asiakasalennus.tuoteno!=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						)
						UNION
						/*	Asiakasalennus Ryhm‰ll‰	ryhma */
						(

							SELECT '4' prio,
								asiakasalennus.alennus,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm‰,
								avainsana.selitetark asiakasryhm‰_nimi,
								asiakasalennus.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakasryhm‰ale' tyyppi $tuotecols			
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma						
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio='$kukarow[yhtio]' and asiakas_ryhma = '$asiakasrow[ryhma]' and asiakas_ryhma != '' and ytunnus='' and asiakasalennus.tuoteno=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							$tuotegroup
						)
							UNION
						/*	Perusalennus	*/
						(	
							SELECT '5' prio,
								alennus,
								'' tuoteno,
								'' tuoteno_nimi,
								'' asiakasryhm‰,
								'' asiakasryhm‰_nimi,
								ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								'' alkupvm,
								'' loppupvm,
								'perusale' tyyppi $tuotecols
							FROM perusalennus
							$tuotejoin
							WHERE perusalennus.yhtio='{$kukarow["yhtio"]}' and alennus>0
							$tuotegroup
						)	
						ORDER BY $order";
			$asres = mysql_query($query) or pupe_error($query);

			if($aletaulu != "" or $tee == "eposti") {
				$ulos  = "<table><caption><font class='message'>".t("Alennustaulukot")."</font></caption>";
				if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("osasto", "try", "alennusryhm‰", "tuoteno", "alennus", "alkupvm", "loppuvm");
					$otsik_spread = array("osasto", "osasto_nimi", "try",  "try_nimi", "alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "alkupvm", "loppuvm");					
				}
				else {
					$otsik = array("osasto", "try", "alennusryhm‰",  "tuoteno", "asiakasryhm‰", "alennus", "alkupvm", "loppuvm", "tyyppi");
					$otsik_spread = array("osasto", "osasto_nimi", "try",  "try_nimi", "alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "asiakasryhm‰",  "asiakasryhm‰_nimi", "alennus", "alkupvm", "loppuvm");
				}
			}
			elseif($yhdistetty != "") {
				$ulos  = "<table><caption><font class='message'>".t("Alennustaulukot")."</font></caption>";
				if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "osasto", "try", "alennus", "alkupvm", "loppuvm");					
				}
				else {
					$otsik = array("alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "asiakasryhm‰",  "asiakasryhm‰_nimi", "osasto", "try", "alennus", "alkupvm", "loppuvm", "tyyppi");
				}
			}
			else {
				$ulos  = "<table><caption><font class='message'>".t("Aletaulukko")."</font></caption>";
				if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm‰", "tuoteno", "alennus", "alkupvm", "loppuvm");
					$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "alennus", "alkupvm", "loppuvm");
				}
				else {
					$otsik = array("alennusryhm‰",  "tuoteno", "asiakasryhm‰", "alennus", "alkupvm", "loppuvm", "tyyppi");
					$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "asiakasryhm‰", "alennus", "alkupvm", "loppuvm");
				}
			}
			
			// Duusataan otsikot
			if($yhdistetty == "") {
				$ulos  .= "<tr>";
				foreach($otsik as $o) {
					$ulos .= "<th>".ucfirst($o)."</th>";
				}
				$ulos  .= "</tr>";				
			}

			if(isset($workbook_ale) and $yhdistetty == "") {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, ucfirst($value), $format_bold);
				}
				$excelrivi++;
			}									
			
			unset($edryhma);
			unset($edryhma2);
			$osastot 	= array();
			$tryt 		= array();			
			
			//	Haetaan osastot ja avainsanat muistiin
			$query = "	SELECT * FROM avainsana WHERE yhtio = '{$kukarow["yhtio"]}' and laji = 'OSASTO'";
			$tryres = mysql_query($query) or pupe_error($query);
			while($tryrow = mysql_fetch_array($tryres)) {
				$osastot[$tryrow["selite"]] = $tryrow["selitetark"];
			}

			$query = "	SELECT * FROM avainsana WHERE yhtio = '{$kukarow["yhtio"]}' and laji = 'TRY'";
			$tryres = mysql_query($query) or pupe_error($query);
			while($tryrow = mysql_fetch_array($tryres)) {
				$tryt[$tryrow["selite"]] = $tryrow["selitetark"];
			}
			
			while ($asrow = mysql_fetch_array($asres)) {
				
				//	Suodatetaan extranetk‰ytt‰jilta muut aleprossat
				if((($kukarow["extranet"] != "" or $tee == "eposti"  or $yhdistetty != "" or $rajattunakyma == "JOO") and ($edryhma != $asrow["alennusryhm‰"] or $edtuoteno != $asrow["tuoteno"])) or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == ""  and $rajattunakyma != "JOO")) {
					
					$edryhma 	= $asrow["alennusryhm‰"];
					$edtuoteno 	= $asrow["tuoteno"];
															
					if(isset($workbook_ale) and $yhdistetty == "") {
						foreach($otsik_spread as $key => $value) {
							if($value == "osasto_nimi") {
								$worksheet->write($excelrivi, $key, $osastot[$asrow["osasto"]]);
							}
							elseif($value == "try_nimi") {
								$worksheet->write($excelrivi, $key, $tryt[$asrow["try"]]);
							}
							else {
								$worksheet->write($excelrivi, $key, $asrow[$value]);
							}
						}
						$excelrivi++;
					}									

					$dada = array();
					$ulos .= "<tr>";

					foreach($otsik as $o) {

						if($yhdistetty != "") {
							$dada[$o] = $asrow[$o];
						}
						else {
							//	Kaunistetaan ulostusta..
							if($asrow[$o."_nimi"] != "") {
								$arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
							}
							elseif($o == "osasto" and $osastot[$asrow[$o]]) {
								$arvo = $asrow[$o]." - ".$osastot[$asrow[$o]];
								$osasto = $arvo;
							}
							elseif($o == "try" and $tryt[$asrow[$o]] != "") {
								$arvo = $asrow[$o]." - ".$tryt[$asrow[$o]];
								$try = $arvo;
							}
							else {
								$arvo = $asrow[$o];
							}
							
							$ulos .= "<td><font class='info'>$arvo<font></td>";
						}
					}
					$ulos .= "</tr>";						

					if($yhdistetty != "") {
						$yhdistetty_array[] = $dada;
					}

					if ($tee == 'eposti') {
						rivi($firstpage, $osasto, $try, $asrow["tuoteno"]." - ".$asrow["tuoteno_nimi"], $asrow["alennusryhm‰"], $asrow["alennus"]);
					}
				}
			}
			
			$ulos .= "</table>";
			
			//	Liitet‰‰n ulostus oikeaan muuttujaan
			if($aletaulu != "") {
				$aletaulu = $ulos;
				$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennukset")."</a>";
			}
			elseif($asale != "") {
				$asale = $ulos;
				$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennustaulukot")."</a>";
			}
			else {
				$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennustaulukot")."</a>";
				$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennukset")."</a>";
				$ulos = "";				
			}
		}
		else {
			$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennukset")."</a>";
			$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ alennustaulukot")."</a>";
		}
		
		
		if ($ashin!='' or $yhdistetty != "") {
			// haetaan asiakashintoja			
			$ashin  = "<table><caption><font class='message'>".t("Asiakashinnat")."</font></caption>";
			if($yhdistetty != "") {
				if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppuvm");
				}
				else {
					$otsik = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppuvm", "tyyppi");
				}				
			}
			else {
				if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm‰", "tuoteno", "hinta", "alkupvm", "loppuvm");
					$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppuvm");
				}
				else {
					$otsik = array("alennusryhm‰", "tuoteno", "asiakasryhm‰", "hinta", "alkupvm", "loppuvm", "tyyppi");
					$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppuvm");
				}
			}
			
			// Duusataan otsikot
			if(isset($workbook_ale) and $yhdistetty == "") {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, ucfirst($value), $format_bold);
				}
				$excelrivi++;
			}									
				
			if($hdistetty == "") {
				$ashin  .= "<tr>";
				foreach($otsik as $o) {
					$ashin .= "<th>".ucfirst($o)."</th>";
				}
				$ashin  .= "</tr>";				
			}

			$query = "	
						(
							SELECT '1' prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm‰,
								avainsana.selitetark asiakasryhm‰_nimi,
								asiakashinta.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakashinta' tyyppi
							FROM asiakashinta
							LEFT JOIN tuote ON asiakashinta.yhtio=tuote.yhtio and asiakashinta.tuoteno=tuote.tuoteno
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							WHERE asiakashinta.yhtio='$kukarow[yhtio]' and ytunnus = '$asiakasrow[ytunnus]' and ytunnus!=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						)
						UNION
						(
							SELECT '2' prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm‰,
								avainsana.selitetark asiakasryhm‰_nimi,
								asiakashinta.ryhma alennusryhm‰,
								perusalennus.selite alennusryhm‰_nimi,
								if(alkupvm='0000-00-00','',alkupvm) alkupvm,
								if(loppupvm='0000-00-00','',loppupvm) loppupvm,
								'asiakasryhm‰hinta' tyyppi
							FROM asiakashinta
							LEFT JOIN tuote ON asiakashinta.yhtio=tuote.yhtio and asiakashinta.tuoteno=tuote.tuoteno
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and laji='ASIAKASRYHMA'
							WHERE asiakashinta.yhtio='$kukarow[yhtio]' and asiakas_ryhma = '$asiakasrow[ryhma]' and asiakas_ryhma!=''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))							
						)
						ORDER BY alennusryhm‰, tuoteno, prio";
			$asres = mysql_query($query) or pupe_error($query);

			while ($asrow = mysql_fetch_array($asres)) {
				
				//	Suodatetaan extranetk‰ytt‰jilta muut hinnat
				if((($kukarow["extranet"] != "" or $tee == "eposti" or $yhdistetty != "" or $rajattunakyma == "JOO") and ($edryhma != $asrow["alennusryhm‰"] or $edtuoteno != $asrow["tuoteno"])) or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == "" or $rajattunakyma != "JOO") ) {
					$edryhma 	= $asrow["alennusryhm‰"];
					$edtuoteno 	= $asrow["tuoteno"];
					
					if(isset($workbook_ale) and $yhdistetty == "") {
						foreach($otsik_spread as $key => $value) {
							$worksheet->write($excelrivi, $key, $asrow[$value]);
						}
						$excelrivi++;
					}									

					$dada = array();
					$ashin .= "<tr>";
					foreach($otsik as $o) {
						
						if($yhdistetty != "") {
							$dada[$o] = $asrow[$o];
						}
						else {
							//	Kaunistetaan ulostusta..
							if($asrow[$o."_nimi"] != "") {
								$arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
							}
							else {
								$arvo = $asrow[$o];							
							}

							$ashin .= "<td><font class='info'>$arvo<font></td>";	
						}
					}
					$ashin .= "</tr>";						
					
					if($yhdistetty != "") {
						$yhdistetty_array[] = $dada;
					}
				}
			}
			
			$ashin .= "</table>";
			
			if($yhdistetty != "") {
				$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ asikashinnat")."</a>";
			}
		}
		else {
			$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("N‰yt‰ asikashinnat")."</a>";				
		}
		
		if ($yhdistetty!='') {

			// tehd‰‰n yhdistetty alennustaulukko...
			$yhdistetty  = "<table><caption><font class='message'>".t("Yhdistetty alennustaulukko")."</font></caption>";
			
			if($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
				$otsik = array("alennusryhm‰",  "tuoteno", "alennus", "hinta", "alkupvm", "loppuvm");
				$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "hinta", "alkupvm", "loppuvm");
			}
			else {
				$otsik = array("alennusryhm‰",  "tuoteno", "asiakasryhm‰", "alennus", "hinta", "alkupvm", "loppuvm", "tyyppi");
				$otsik_spread = array("alennusryhm‰", "alennusryhm‰_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "hinta", "alkupvm", "loppuvm");
			}
			
			// Duusataan otsikot
			if(isset($workbook_ale)) {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, ucfirst($value), $format_bold);
				}
				$excelrivi++;
			}									

			$yhdistetty  .= "<tr>";
			foreach($otsik as $o) {
				$yhdistetty .= "<th>".ucfirst($o)."</th>";
			}
			$yhdistetty  .= "</tr>";				
						
			foreach($yhdistetty_array as $key => $value) {
				
				if(isset($workbook_ale)) {
					foreach($otsik_spread as $key => $xvalue) {
						$worksheet->write($excelrivi, $key, $value[$xvalue]);
					}
					$excelrivi++;
				}

				$yhdistetty .= "<tr>";
				foreach($otsik as $o) {			
					//	Kaunistetaan ulostusta..
					if($value[$o."_nimi"] != "") {
						$arvo = $value[$o]." - ".$value[$o."_nimi"];
					}
					else {
						$arvo = $value[$o];							
					}

					$yhdistetty .= "<td><font class='info'>$arvo<font></td>";
				}
				
				$yhdistetty .= "</tr>";				
			} 
			$yhdistetty .= "</table>";
		}
		else {
			$yhdistetty = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&yhdistetty=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Yhdistetty alennustaulukko")."</a>";
		}

		// piirret‰‰n ryhmist‰ ja hinnoista taulukko..
		echo "<table><tr>
				<td valign='top' class='back'>$asale</td>
				<td class='back'></td>
				<td valign='top' class='back'>$aletaulu</td>
				<td class='back'></td>
				<td valign='top' class='back'>$ashin</td>
				<td class='back'></td>				
				<td valign='top' class='back'>$yhdistetty</td>
			</tr></table>";
		
		if(isset($workbook_ale) and $excelrivi>1) {
			$workbook_ale->close();
			
			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Alennustaulukko.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
			
		}
		
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
				$kutsu = "Alennustaulukko - ".trim($asiakasrow["nimi"]." ".$asiakasrow["nimitark"]);

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
