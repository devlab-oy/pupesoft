<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

if (@include("../inc/parametrit.inc"));
elseif (@include("parametrit.inc"));
else exit;

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

$alennuslaji = "";

if ($yhtiorow['myynnin_alekentat'] > 1) {
	$alennuslaji = "alennuslaji";
}

//	Haardkoodataan exranetrajaus vain alennukseen
if ($kukarow["extranet"] != "") {

	$rajaus = "ALENNUKSET";

	//Haetaan asiakkaan tunnuksella
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$asiakasrow = mysql_fetch_array($result);
		$ytunnus = $asiakasrow["ytunnus"];
		$asiakasid = $asiakasrow["tunnus"];
	}
	else {
		echo t("<font class='error'>VIRHE: K�ytt�j�tiedoissasi on virhe! Ota yhteys j�rjestelm�n yll�pit�j��n.")."</font><br><br>";
		exit;
	}
}

if ($tee == 'eposti') {

	if ($kukarow["extranet"] == "") {
		if ($komento == '') {
			$tulostimet[] = "Alennustaulukko";
			$toimas = $ytunnus;
			require("inc/valitse_tulostin.inc");
		}

		$ytunnus = $toimas;
	}
	else {
		$komento["Alennustaulukko"] = "email";
	}

	require('pdflib/phppdflib.class.php');

	function alku () {
		global $yhtiorow, $firstpage, $pdf, $rectparam, $norm, $norm_bold, $pieni, $ytunnus, $asiakasid, $kukarow, $kala, $tid, $otsikkotid;

		static $sivu;
		$sivu++;

		if (!isset($pdf)) {

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
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$asiakasid'";
			$assresult = mysql_query($query) or pupe_error($query);
			$assrow = mysql_fetch_array($assresult);

			// Tehd��n firstpage
			$firstpage = $pdf->new_page("a4");

			//	Tehd��n headertemplate
			$tid=$pdf->template->create();
			$pdf->template->rectangle($tid, 20, 20,  0, 580, $rectparam);
			$pdf->template->text($tid, 30,  5, $yhtiorow["nimi"], $pieni);
			$pdf->template->text($tid, 170, 5, "$assrow[nimi] $assrow[nimitark] ($ytunnus) ".t("alennustaulukko"));
			$pdf->template->place($tid, $firstpage, 0, 800);

			//	Tehd��n otsikkoheader
			$otsikkotid=$pdf->template->create();
			$pdf->template->text($otsikkotid, 30,  20, t("Osasto"), $norm_bold);
			$pdf->template->text($otsikkotid, 30,   0, t("Tuoteryhm�")."/".t("Tuotenumero"), $norm_bold);
			$pdf->template->text($otsikkotid, 330,  0, t("Aleryhm�"), $norm_bold);
			$pdf->template->text($otsikkotid, 450,  0, t("Alennus"), $norm_bold);
			if ($yhtiorow['myynnin_alekentat'] > 1) $pdf->template->text($otsikkotid, 520,  0, t("Alelaji"), $norm_bold);
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

			//	Liitet��n vaan valmiit templatet uudelle sivulle
			$firstpage = $pdf->new_page("a4");
			$pdf->template->place($tid, $firstpage, 0, 800);
			$pdf->template->place($otsikkotid, $firstpage, 0, 760);
		}

		$pdf->draw_text(520, 805, t("Sivu").": $sivu", $firstpage, $norm);

	}

	function rivi ($firstpage, $osasto, $try, $tuote, $ryhma, $ale, $alelaji) {
		global $pdf, $kala, $rectparam, $norm, $norm_bold, $pieni;

		static $edosasto;

		if ($kala < 80) {
			$firstpage = alku();
			$kala = 760;
		}

		//	Vaihdetaan osastoa
		if ($osasto != $edosasto) {
			$kala -= 15;
			$pdf->draw_text(30,  $kala, $osasto, 							$firstpage, $norm_bold);
			$kala -= 25;
		}

		$edosasto = $osasto;

		if ($tuote == " - ") {
			$pdf->draw_text(30,  $kala, $try, 								$firstpage, $norm);
		}
		else {
			$pdf->draw_text(60, $kala, $tuote, 								$firstpage, $norm);
		}
		$pdf->draw_text(310, $kala, sprintf('%10s',$ryhma), 	$firstpage, $norm);
		$pdf->draw_text(420, $kala, sprintf('%10s',sprintf('%.2d',$ale))."%", 	$firstpage, $norm);
		if ($yhtiorow['myynnin_alekentat'] > 1 and trim($alelaji) != '') $pdf->draw_text(500, $kala, sprintf('%10s',t("Ale").$alelaji), 	$firstpage, $norm);

		$kala -= 15;
	}

	//tehd��n eka sivu
	alku();
}

echo "<font class='head'>".t("Asiakkaan perustiedot")."</font><hr><br><br>";

if ($kukarow["extranet"] == "" and $lopetus == "") {
	//	Jos tullaan muualta ei anneta valita uutta asiakasta
	echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
	echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
	echo "<table><tr>";
	echo "<th>".t("Anna asiakasnumero tai osa nimest�")."</th>";
	echo "<td><input type='text' name='ytunnus'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'>";
	echo "</tr></table>";
	echo "</form><br><br>";
}

if ($kukarow["extranet"] == "" and $ytunnus != '') {
	require ("inc/asiakashaku.inc");
}

// jos meill� on onnistuneesti valittu asiakas
if ($asiakasid > 0) {

	// KAUTTALASKUTUSKIKKARE
	if ($GLOBALS['eta_yhtio'] != '' and ($GLOBALS['koti_yhtio'] != $kukarow['yhtio'] or $asiakasrow['osasto'] != '6')) {
		unset($GLOBALS['eta_yhtio']);
	}

	if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
		$asiakas_yhtio = $GLOBALS['eta_yhtio'];

		// Toisen firman asiakastiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$asiakas_yhtio}'
					AND ytunnus = '{$asiakasrow['ytunnus']}'
					AND toim_ovttunnus = '{$asiakasrow['toim_ovttunnus']}'";
		$asiakas_tunnus_res = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_assoc($asiakas_tunnus_res);
	}
	else {
		$asiakas_yhtio = $kukarow['yhtio'];
	}

	// haetaan asiakkaan segmentit
	$query = "	SELECT group_concat(parent.tunnus) tunnukset
   				FROM dynaaminen_puu AS node
   				JOIN dynaaminen_puu AS parent ON (node.lft BETWEEN parent.lft AND parent.rgt)
   				JOIN puun_alkio on (puun_alkio.yhtio = node.yhtio and puun_alkio.puun_tunnus = node.tunnus)
   				JOIN asiakas on (asiakas.yhtio = node.yhtio and asiakas.tunnus = puun_alkio.liitos)
   				WHERE asiakas.tunnus = '$asiakasrow[tunnus]'
				AND asiakas.yhtio = '$kukarow[yhtio]'
   				AND node.laji = parent.laji =  puun_alkio.laji = 'asiakas'
   				AND parent.lft > 1
   				HAVING tunnukset IS NOT NULL";
	$almight = mysql_query($query) or pupe_error($query);

	$alehi_assegmenttirow = mysql_fetch_assoc($almight);

	if ($alehi_assegmenttirow["tunnukset"] == "") {
		$alehi_assegmenttirow["tunnukset"] = 0;
	}

	if ($tee != "eposti") {
		if (@include('Spreadsheet/Excel/Writer.php')) {
			//keksit��n failille joku varmasti uniikki nimi:
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
	echo "<th valign='top'>".t("Ytunnus")."</th>";
	echo "<th valign='top'>".t("Asiakasnumero")."</th>";
	echo "<th valign='top'>".t("Nimi")."<br>".t("Osoite")."</th>";
	echo "<th valign='top'>".t("Toimitusasiakas")."<br>".t("Toimitusosoite")."</th>";
	echo "</tr><tr>";
	echo "<td valign='top'>$asiakasrow[ytunnus]</td>";
	echo "<td valign='top'>$asiakasrow[asiakasnro]</td>";
	echo "<td valign='top'>$asiakasrow[nimi]<br>$asiakasrow[osoite]<br>$asiakasrow[postino] $asiakasrow[postitp]</td>";
	echo "<td valign='top'>$asiakasrow[toim_nimi]<br>$asiakasrow[toim_osoite]<br>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td>";
	echo "</tr></table><br><br>";

	// hardcoodataan v�rej�
	// $cmyynti = "#ccccff";
	// $ckate   = "#ff9955";
	// $ckatepr = "#00dd00";
	$maxcol  = 12; // montako columnia n�ytt� on

	if ($lopetus == "" and $kukarow["extranet"] == "") {
		if ($rajaus=="MYYNTI") $sel["M"] = "checked";
		elseif ($rajaus=="ALENNUKSET") $sel["A"] = "checked";
		else $sel["K"] = "checked";

		echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
		echo "<input type = 'hidden' name = 'ytunnus' value = '$ytunnus'>";
		echo "<input type = 'hidden' name = 'asiakasid' value = '$asiakasid'>";
		echo "<table><tr>";
		echo "<th>".t("Rajaa n�kym��")."</th></tr>";
		echo "<tr><td><input type='radio' name='rajaus' value='' onclick='submit()' $sel[K]>".t("Kaikki")." <input type='radio' name='rajaus' value='MYYNTI' onclick='submit()' $sel[M]>".t("Myynti")." <input type='radio' name='rajaus' value='ALENNUKSET' onclick='submit()' $sel[A]>".t("Alennukset")."</td></tr>";
		echo "</table>";
		echo "</form>";
	}

	if (($rajaus == "" or $rajaus == "MYYNTI") and $asiakas_yhtio == $kukarow["yhtio"]) {
		// tehd��n asiakkaan ostot kausittain, sek� pylv��t niihin...
		echo "<br><font class='message'>".t("Myynti kausittain viimeiset 24 kk")." (<font class='myynti'>".t("myynti")."</font>/<font class='kate'>".t("kate")."</font>/<font class='katepros'>".t("kateprosentti")."</font>)</font>";
		echo "<hr>";

		// 24 kk sitten
		$ayy = date("Y-m-01",mktime(0, 0, 0, date("m")-24, date("d"), date("Y")));

		$query  = "	SELECT date_format(tapvm,'%Y/%m') kausi,
					round(sum(arvo),0) myynti,
					round(sum(kate),0) kate,
					round(sum(kate)/sum(arvo)*100,1) katepro
					from lasku use index (yhtio_tila_liitostunnus_tapvm)
					where yhtio = '$kukarow[yhtio]'
					and liitostunnus = '$asiakasid'
					and tila = 'U'
					and tapvm >= '$ayy'
					group by 1
					having myynti <> 0 or kate <> 0";
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

			// lasketaan pylv�iden korkeus
			if ($maxeur>0) {
				$hmyynti  = round(50*$sumrow['myynti']/$maxeur,0);
				$hkate    = round(50*$sumrow['kate']/$maxeur,0);
				$hkatepro = round($sumrow['katepro']/2,0);
				if ($hkatepro>60) $hkatepro = 60;
			}
			else {
				$hmyynti = $hkate = $hkatepro = 0;
			}

			$pylvaat = "<table style='padding:0px;margin:0px;'><tr>
			<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='".$palvelin2."pics/blue.png' height='$hmyynti' width='12' alt='".t("myynti")." $sumrow[myynti]'></td>
			<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='".$palvelin2."pics/orange.png' height='$hkate' width='12' alt='".t("kate")." $sumrow[kate]'></td>
			<td style='padding:0px;margin:0px;vertical-align:bottom;'><img src='".$palvelin2."pics/green.png' height='$hkatepro' width='12' alt='".t("katepro")." $sumrow[katepro] %'></td>
			</tr></table>";

			if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';
			echo "<td class='back' style='vertical-align: bottom;'>";

			echo "<table width='60'>";
			echo "<tr><td nowrap align='center' style='padding:0px;margin:0px;vertical-align:bottom;height:55px;'>$pylvaat</td></tr>";
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

		// teh��n validia htmll�� ja t�ytet��n tyhj�t solut..
		$ero = $maxcol+1-$col;

		if ($ero<>$maxcol)
			echo "<td colspan='$ero' class='back'></td></tr>\n";

		echo "</table>";


		// tehd��n asiakkaan ostot tuoteryhmitt�in... vikat 12 kk
		echo "<br><font class='message'>".t("Myynti osastoittain tuoteryhmitt�in viimeiset 12 kk")." (<font class='myynti'>".t("myynti")."</font>/<font class='kate'>".t("kate")."</font>/<font class='katepros'>".t("kateprosentti")."</font>)</font>";
		echo "<hr>";

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
		echo "<br>".t("N�yt�/piilota osastojen ja tuoteryhmien nimet.");
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

		$query = "	SELECT osasto, try, round(sum(rivihinta),0) myynti, round(sum(tilausrivi.kate),0) kate, round(sum(kpl),0) kpl, round(sum(tilausrivi.kate)/sum(rivihinta)*100,1) katepro
					from lasku use index (yhtio_tila_liitostunnus_tapvm), tilausrivi use index (uusiotunnus_index)
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.liitostunnus = '$asiakasid'
					and lasku.tila = 'U'
					and lasku.alatila = 'X'
					and lasku.tapvm >= '$ayy'
					and tilausrivi.yhtio = lasku.yhtio
					and tilausrivi.uusiotunnus = lasku.tunnus
					group by 1,2
					having myynti <> 0 or kate <> 0
					order by osasto+0, try+0";
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

			// tehd��n avainsana query
			$avainresult = t_avainsana("TRY", $kukarow['kieli'], "and avainsana.selite ='$sumrow[try]'");
			$tryrow = mysql_fetch_array($avainresult);

			// tehd��n avainsana query
			$avainresult = t_avainsana("OSASTO", $kukarow['kieli'], "and avainsana.selite ='$sumrow[osasto]'");
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

		// teh��n validia htmll�� ja t�ytet��n tyhj�t solut..
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

			$otee = $tee;
			$oasiakasrow = $asiakasrow;
			$orajaus = $rajaus;

			$_POST["fuuli"]	= "on";
			$tee 			= "go";
			$ajotapa 		= "lasku";
			$ajotapanlisa 	= "summattuna";
			$yhtiot[]		= $kukarow["yhtio"];
			$asiakasosasto 	= "";
			$asiakasosasto2 = "";
			$asiakasryhma 	= "";
			$asiakasryhma2 	= "";
			$jarjestys[1]	= "on";
			$asiakasid		= $asiakasrow["tunnus"];
			$asiakas		= $ytunnus;
			$rajaus			= array();
			$toimittaja 	= "";
			$kka 			= date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$vva 			= date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$ppa 			= date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$kkl 			= date("m");
			$vvl 			= date("Y");
			$ppl 			= date("d");

			//	Huijataan myyntiseurantaa
			if ($lopetus == "") $lopetus = "block";

			require("myyntiseuranta.php");

			if ($lopetus == "block") $lopetus = "";

			//	Korjataan ytunnus ja tee
			$ytunnus 	= $asiakas;
			$tee 		= $otee;
			$asiakasrow = $oasiakasrow;
			$rajaus 	= $orajaus;
		}
	}

	$yhdistetty_array = array();

	if ($rajaus == "" or $rajaus == "ALENNUKSET") {

		if ($rajaus == "") {
			echo "<a href='#' name='alennukset'></a>";
		}

		echo "<br><font class='message'>".t("Asiakkaan alennusryhm�t, alennustaulukko ja alennushinnat")."</font><hr>";

		if ($kukarow["extranet"] == "") {
			$sela = $selb = "";
			if ($rajattunakyma != "JOO") {
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
			echo "<input type='hidden' name='aletaulu' value = '$aletaulu'>";
			echo "<input type='hidden' name='yhdistetty' value = '$yhdistetty'>";
			echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='' $sela> ".t("Normaalin�kym�");
			echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='JOO' $selb> ".t("Extranetn�kym�");
			echo "</form><br>";
		}

		echo "<br><a href='$PHP_SELF?tee=eposti&ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Tulosta alennustaulukko")."</a><br><br>";

		if ($asale != '' or $aletaulu != '' or $yhdistetty != "" or $tee == "eposti") {

			$taulu  = "";

			if ($aletaulu != "" or $tee == "eposti") {
				$tuotejoin	= " JOIN tuote ON tuote.yhtio=perusalennus.yhtio and tuote.aleryhma=perusalennus.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E' ";
				$tuotewhere = " and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
				$tuotegroup = " GROUP BY tuote.try, tuote.osasto, tuote.aleryhma ";
				$tuotecols 	= ", tuote.osasto, tuote.try";
				$order 		= " osasto+0, try+0, alennusryhm�+0, tuoteno, alennuslaji, prio ";
			}
			else {
				$tuotejoin  = "";
				$tuotewhere	= "";
				$tuotegroup	= "";
				$tuotecols 	= "";
				$order 		= "alennusryhm�+0, alennuslaji, prio, asiakasryhm�";
			}

			$query = "	/*	5. asiakas.tunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus) */
						(	SELECT 1 prio,
								asiakasalennus.alennus,
								asiakasalennus.tuoteno,
								asiakasalennus.alennuslaji,
								tuote.nimitys tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas / tuote' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.asiakas = '$asiakasrow[tunnus]'
							and asiakasalennus.asiakas > 0
							and asiakasalennus.tuoteno != ''
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/*	5. asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus) */
						(	SELECT 2 prio,
								asiakasalennus.alennus,
								asiakasalennus.tuoteno,
								asiakasalennus.alennuslaji,
								tuote.nimitys tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Ytunnus / tuote' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.ytunnus = '$asiakasrow[ytunnus]'
							and asiakasalennus.ytunnus != ''
							and asiakasalennus.tuoteno != ''
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 6. asiakas.tunnus tuote.aleryhm� aleprosentti (asiakkaan tuotealeryhm�n alennus) */
						(	SELECT 3 prio,
								asiakasalennus.alennus,
								asiakasalennus.alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas / aleryhm�' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.asiakas = '$asiakasrow[tunnus]'
							and asiakasalennus.asiakas > 0
							and asiakasalennus.ryhma != ''
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						UNION
						/* 6. asiakas.ytunnus tuote.aleryhm� aleprosentti (asiakkaan tuotealeryhm�n alennus) */
						(	SELECT 4 prio,
								asiakasalennus.alennus,
								asiakasalennus.alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Ytunnus / aleryhm�' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.ytunnus = '$asiakasrow[ytunnus]'
							and asiakasalennus.ytunnus != ''
							and asiakasalennus.ryhma != ''
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						UNION
						/* 7. asiakas.segmentti tuote.tuoteno aleprosentti (asiakassegmentin tuotteen alennus) */
						(
							SELECT 5 prio,
									asiakasalennus.alennus,
									asiakasalennus.tuoteno,
									asiakasalennus.alennuslaji,
									tuote.nimitys tuoteno_nimi,
									'' asiakasryhm�,
									'' asiakasryhm�_nimi,
									asiakasalennus.ryhma alennusryhm�,
									perusalennus.selite alennusryhm�_nimi,
									if (alkupvm='0000-00-00','',alkupvm) alkupvm,
									if (loppupvm='0000-00-00','',loppupvm) loppupvm,
									'Asiakas-segmentti / tuote' tyyppi
									$tuotecols
								FROM asiakasalennus
								LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
								LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
								JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
								WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
								and asiakasalennus.asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
								and asiakasalennus.asiakas_segmentti != ''
								and asiakasalennus.tuoteno != ''
								and asiakasalennus.ytunnus = ''
								and asiakasalennus.asiakas = 0
								and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
								and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
								and asiakasalennus.alennus >= 0
								and asiakasalennus.alennus <= 100
								and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 8. asiakas.ryhm� tuote.tuoteno aleprosentti (asiakasaleryhm�n tuotteen alennus) */
						(	SELECT 6 prio,
								asiakasalennus.alennus,
								asiakasalennus.tuoteno,
								asiakasalennus.alennuslaji,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakasryhm� / tuote' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.asiakas_ryhma = '$asiakasrow[ryhma]'
							and asiakasalennus.asiakas_ryhma != ''
							and asiakasalennus.tuoteno != ''
							and asiakasalennus.ytunnus = ''
							and asiakasalennus.asiakas = 0
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 9. asiakas.piiri tuote.tuoteno aleprosentti (asiakaspiirin tuotteen alennus) */
						(	SELECT 7 prio,
								asiakasalennus.alennus,
								asiakasalennus.tuoteno,
								asiakasalennus.alennuslaji,
								tuote.nimitys tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakaspiiri / tuote' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakasalennus.yhtio and tuote.tuoteno=asiakasalennus.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.piiri = '$asiakasrow[piiri]'
							and asiakasalennus.piiri != ''
							and asiakasalennus.tuoteno != ''
							and asiakasalennus.ytunnus = ''
							and asiakasalennus.asiakas = 0
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 10. asiakas.segmentti tuote.aleryhm� aleprosentti (asiakassegmentin tuotealeryhm�n alennus) */
						(	SELECT 8 prio,
								asiakasalennus.alennus,
								asiakasalennus.alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas-segmentti / aleryhm�' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakasalennus.asiakas_segmentti != ''
							and asiakasalennus.ryhma != ''
							and asiakasalennus.ytunnus = ''
							and asiakasalennus.asiakas = 0
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						UNION
						/* 11. asiakas.ryhm� tuote.aleryhm� aleprosentti (asiakasaleryhm�n tuotealeryhm�n alennus) */
						(	SELECT 9 prio,
								asiakasalennus.alennus,
								asiakasalennus.alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakasryhm� / aleryhm�' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.asiakas_ryhma = '$asiakasrow[ryhma]'
							and asiakasalennus.asiakas_ryhma != ''
							and asiakasalennus.ryhma != ''
							and asiakasalennus.ytunnus = ''
							and asiakasalennus.asiakas = 0
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						UNION
						/* 12. asiakas.piiri tuote.aleryhm� aleprosentti (asiakaspiirin tuotealeryhm�n alennus) */
						(	SELECT 10 prio,
								asiakasalennus.alennus,
								asiakasalennus.alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakasalennus.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakaspiiri / aleryhm�' tyyppi
								$tuotecols
							FROM asiakasalennus
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakasalennus.yhtio and perusalennus.ryhma=asiakasalennus.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakasalennus.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							$tuotejoin
							WHERE asiakasalennus.yhtio = '$asiakas_yhtio'
							and asiakasalennus.piiri = '$asiakasrow[piiri]'
							and asiakasalennus.piiri != ''
							and asiakasalennus.ryhma != ''
							and asiakasalennus.ytunnus = ''
							and asiakasalennus.asiakas = 0
							and (asiakasalennus.minkpl <= '1' or asiakasalennus.minkpl = 0)
							and ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31',asiakasalennus.loppupvm) >= current_date) or (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))
							and asiakasalennus.alennus >= 0
							and asiakasalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						UNION
						/* 13. tuote.aleryhm� aleprosentti (tuotealeryhm�n perusalennus) */
						(	SELECT 11 prio,
								alennus,
								'' alennuslaji,
								'' tuoteno,
								'' tuoteno_nimi,
								'' asiakasryhm�,
								'' asiakasryhm�_nimi,
								ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								'' alkupvm,
								'' loppupvm,
								'Perusalennus' tyyppi
								$tuotecols
							FROM perusalennus
							$tuotejoin
							WHERE perusalennus.yhtio = '$asiakas_yhtio'
							and perusalennus.ryhma != ''
							# Huom. perualennus > 0
							and perusalennus.alennus > 0
							and perusalennus.alennus <= 100
							$tuotewhere
							$tuotegroup
						)
						ORDER BY $order";
			$asres = mysql_query($query) or pupe_error($query);

			if ($aletaulu != "" or $tee == "eposti") {
				$ulos  = "<table><caption><font class='message'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmitt�in")."</font></caption>";
				if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("osasto", "try", "alennusryhm�", "tuoteno", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
					$otsik_spread = array("osasto", "osasto_nimi", "try",  "try_nimi", "alennusryhm�", "alennusryhm�_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
				}
				else {
					$otsik = array("osasto", "try", "alennusryhm�", "tuoteno", "asiakasryhm�", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
					$otsik_spread = array("osasto", "osasto_nimi", "try", "try_nimi", "alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "asiakasryhm�", "asiakasryhm�_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
				}
			}
			elseif ($yhdistetty != "") {
				$ulos  = "<table><caption><font class='message'>".t("Yhdistetty alennustaulukko")."</font></caption>";
				if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm�", "alennusryhm�_nimi",  "tuoteno", "tuoteno_nimi", "osasto", "try", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
				}
				else {
					$otsik = array("alennusryhm�", "alennusryhm�_nimi",  "tuoteno", "tuoteno_nimi", "asiakasryhm�",  "asiakasryhm�_nimi", "osasto", "try", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
				}
			}
			else {
				$ulos  = "<table><caption><font class='message'>".t("Alennustaulukko")."</font></caption>";
				if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm�", "tuoteno", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
					$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
				}
				else {
					$otsik = array("alennusryhm�",  "tuoteno", "asiakasryhm�", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
					$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "asiakasryhm�", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
				}
			}

			// Otsikot
			if ($yhdistetty == "") {
				$ulos  .= "<tr>";
				foreach ($otsik as $o) {
					$ulos .= "<th>".t(ucfirst($o))."</th>";
				}
				$ulos  .= "</tr>";
			}

			if (isset($workbook_ale) and $yhdistetty == "") {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, t(ucfirst($value)), $format_bold);
				}
				$excelrivi++;
			}

			unset($edryhma);
			unset($edryhma2);
			$osastot	= array();
			$tryt		= array();

			//	Haetaan osastot ja avainsanat muistiin
			// tehd��n avainsana query
			$tryres = t_avainsana("OSASTO");

			while($tryrow = mysql_fetch_array($tryres)) {
				$osastot[$tryrow["selite"]] = $tryrow["selitetark"];
			}

			// tehd��n avainsana query
			$tryres = t_avainsana("TRY");

			while($tryrow = mysql_fetch_array($tryres)) {
				$tryt[$tryrow["selite"]] = $tryrow["selitetark"];
			}

			while ($asrow = mysql_fetch_array($asres)) {

				$tyhja = 0;
				//	Onko perusalessa tyhj� ryhm�?
				if ($asrow["prio"] == 5) {
					$query = "	SELECT tuote.tunnus
								FROM tuote
								WHERE tuote.yhtio = '$asiakas_yhtio'
								and tuote.aleryhma = '$asrow[alennusryhm�]'
								and tuote.hinnastoon != 'E'
								and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
								and tuote.osasto != 0
								and tuote.try != 0
								LIMIT 1";
					$testres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($testres) == 0) {
						$tyhja = 1;
					}
				}

				//	Suodatetaan extranetk�ytt�jilta muut aleprossat
				if ((((($kukarow["extranet"] != "" or $tee == "eposti"  or $yhdistetty != "" or $rajattunakyma == "JOO") and ($edtry != $asrow["try"] or $edryhma != $asrow["alennusryhm�"] or $edtuoteno != $asrow["tuoteno"])) or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == ""  and $rajattunakyma != "JOO"))) and $tyhja == 0) {

					$edryhma 	= $asrow["alennusryhm�"];
					$edtry 		= $asrow["try"];
					$edtuoteno 	= $asrow["tuoteno"];

					if (isset($workbook_ale) and $yhdistetty == "") {
						foreach ($otsik_spread as $key => $value) {
							if ($value == "osasto_nimi") {
								$worksheet->write($excelrivi, $key, $osastot[$asrow["osasto"]]);
							}
							elseif ($value == "try_nimi") {
								$worksheet->write($excelrivi, $key, $tryt[$asrow["try"]]);
							}
							else {
								$worksheet->write($excelrivi, $key, $asrow[$value]);
							}
						}
						$excelrivi++;
					}

					$dada = array();
					if ($yhdistetty == "") $ulos .= "<tr>";

					foreach ($otsik as $o) {

						if ($yhdistetty != "") {
							$dada[$o] = $asrow[$o];
						}
						else {
							//	Kaunistetaan tulostusta..
							if ($asrow[$o."_nimi"] != "" and $asrow[$o] != $asrow[$o."_nimi"]) {
								$arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
							}
							elseif ($o == "osasto" and $osastot[$asrow[$o]]) {
								$arvo = $asrow[$o]." - ".$osastot[$asrow[$o]];
								$osasto = $arvo;
							}
							elseif ($o == "try" and $tryt[$asrow[$o]] != "") {
								$arvo = $asrow[$o]." - ".$tryt[$asrow[$o]];
								$try = $arvo;
							}
							else {
								$arvo = $asrow[$o];
							}

							$align = "";

							if ($o == "alennus") {
								$align = " align='right' ";
							}

							if ($o == "alkupvm" or $o == "loppupvm") {
								$arvo = tv1dateconv($arvo);
							}

							$ulos .= "<td $align>$arvo</td>";
						}
					}

					if ($yhdistetty == "") $ulos .= "</tr>";

					if ($yhdistetty != "") {
						$yhdistetty_array[] = $dada;
					}

					if ($tee == 'eposti') {
						rivi($firstpage, $osasto, $try, $asrow["tuoteno"]." - ".$asrow["tuoteno_nimi"], $asrow["alennusryhm�"], $asrow["alennus"], $asrow["alennuslaji"]);
					}
				}
			}

			$ulos .= "</table>";

			//	Liitet��n ulostus oikeaan muuttujaan
			if ($aletaulu != "") {
				$aletaulu = $ulos;
				$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
			}
			elseif ($asale != "") {
				$asale = $ulos;
				$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmitt�in")."</a>";
			}
			else {
				$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmitt�in")."</a>";
				$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
				$ulos = "";
			}
		}
		else {
			$asale 		= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
			$aletaulu 	= "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmitt�in")."</a>";
		}


		if ($ashin != "" or $yhdistetty != "") {
			// haetaan asiakashintoja
			$ashin  = "<table><caption><font class='message'>".t("Asiakashinnat")."</font></caption>";

			if ($yhdistetty != "") {
				if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
				}
				else {
					$otsik = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm", "tyyppi");
				}
			}
			else {
				if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
					$otsik = array("alennusryhm�", "tuoteno", "hinta", "alkupvm", "loppupvm");
					$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
				}
				else {
					$otsik = array("alennusryhm�", "tuoteno", "asiakasryhm�", "hinta", "alkupvm", "loppupvm", "tyyppi");
					$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
				}
			}

			// Otsikot
			if (isset($workbook_ale) and $yhdistetty == "") {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, t(ucfirst($value)), $format_bold);
				}
				$excelrivi++;
			}

			if ($yhdistetty == "") {
				$ashin  .= "<tr>";
				foreach ($otsik as $o) {
					$ashin .= "<th>".t(ucfirst($o))."</th>";
				}
				$ashin  .= "</tr>";
			}

			$query = "	/* 2B. asiakas.tunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhti�n valuutassa */
						(	SELECT 1 prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas / tuote' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas = '$asiakasrow[tunnus]'
							and asiakashinta.asiakas > 0
							and asiakashinta.tuoteno != ''
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 2B. asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhti�n valuutassa */
						(	SELECT 2 prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Ytunnus / tuote' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.ytunnus = '$asiakasrow[ytunnus]'
							and asiakashinta.ytunnus > 0
							and asiakashinta.tuoteno != ''
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 3B. asiakas.tunnus tuote.aleryhm� nettohinta (asiakkaan tuotealeryhm�n hinta) yhti�n valuutassa */
						(	SELECT 3 prio,
								hinta,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas / aleryhm�' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.aleryhma=asiakashinta.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas = '$asiakasrow[tunnus]'
							and asiakashinta.asiakas > 0
							and asiakashinta.ryhma != ''
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 3B. asiakas.ytunnus tuote.aleryhm� nettohinta (asiakkaan tuotealeryhm�n hinta) yhti�n valuutassa */
						(	SELECT 4 prio,
								hinta,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Ytunnus / aleryhm�' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.aleryhma=asiakashinta.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.ytunnus = '$asiakasrow[ytunnus]'
							and asiakashinta.ytunnus != ''
							and asiakashinta.ryhma != ''
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 4B. asiakas.segmentti tuote.tuoteno nettohinta (asiakassegmentin tuotteen hinta) yhti�n valuutassa */
						(	SELECT 5 prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas-segmentti / tuote' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakashinta.asiakas_segmentti !=''
							and asiakashinta.tuoteno != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 5B. asiakas.ryhm� tuote.tuoteno nettohinta (asiakasaleryhm�n tuotteen hinta) yhti�n valuutassa */
						(	SELECT 6 prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakasryhm� / tuote' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas_ryhma = '$asiakasrow[ryhma]'
							and asiakashinta.asiakas_ryhma != ''
							and asiakashinta.tuoteno != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 6B. asiakas.piiri tuote.tuoteno nettohinta (asiakaspiirin tuotteen hinta) yhti�n valuutassa */
						(	SELECT 7 prio,
								hinta,
								tuote.tuoteno,
								tuote.nimitys tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakaspiiri / tuote' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.piiri = '$asiakasrow[piiri]'
							and asiakashinta.piiri != ''
							and asiakashinta.tuoteno != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 7B. asiakas.segmentti tuote.aleryhma nettohinta (asiakassegmentin tuotealeryhm�n hinta) yhti�n valuutassa */
						(	SELECT 8 prio,
								hinta,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakas-segmentti / aleryhm�' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.aleryhma=asiakashinta.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakashinta.asiakas_segmentti != ''
							and asiakashinta.ryhma != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 8B. asiakas.ryhm� tuote.aleryhm� nettohinta (asiakasaleryhm�n tuotealeryhm�n hinta) yhti�n valuutassa */
						(	SELECT 9 prio,
								hinta,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakasryhm� / aleryhm�' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.aleryhma=asiakashinta.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.asiakas_ryhma = '$asiakasrow[ryhma]'
							and asiakashinta.asiakas_ryhma != ''
							and asiakashinta.ryhma != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						UNION
						/* 9B. asiakas.piiri tuote.aleryhma nettohinta (asiakaspiirin tuotealeryhm�n hinta) yhti�n valuutassa */
						(	SELECT 10 prio,
								hinta,
								'' tuoteno,
								'' tuoteno_nimi,
								asiakas_ryhma asiakasryhm�,
								avainsana.selitetark asiakasryhm�_nimi,
								asiakashinta.ryhma alennusryhm�,
								perusalennus.selite alennusryhm�_nimi,
								if (alkupvm='0000-00-00','',alkupvm) alkupvm,
								if (loppupvm='0000-00-00','',loppupvm) loppupvm,
								'Asiakaspiiri / aleryhm�' tyyppi
							FROM asiakashinta
							LEFT JOIN perusalennus ON perusalennus.yhtio=asiakashinta.yhtio and perusalennus.ryhma=asiakashinta.ryhma
							LEFT JOIN avainsana ON avainsana.yhtio=asiakashinta.yhtio and avainsana.selite=asiakas_ryhma and avainsana.laji='ASIAKASRYHMA'
							JOIN tuote ON tuote.yhtio=asiakashinta.yhtio and tuote.aleryhma=asiakashinta.ryhma and tuote.osasto != 0 and tuote.try != 0 and tuote.hinnastoon != 'E'
							WHERE asiakashinta.yhtio = '$asiakas_yhtio'
							and asiakashinta.piiri = '$asiakasrow[piiri]'
							and asiakashinta.piiri != ''
							and asiakashinta.ryhma != ''
							and asiakashinta.ytunnus = ''
							and asiakashinta.asiakas = 0
							and asiakashinta.valkoodi in ('$yhtiorow[valkoodi]','')
							and (asiakashinta.minkpl <= '1' or asiakashinta.minkpl = 0)
							and ((asiakashinta.alkupvm <= current_date and if (asiakashinta.loppupvm = '0000-00-00','9999-12-31',asiakashinta.loppupvm) >= current_date) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))
							and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
						)
						ORDER BY alennusryhm�+0, tuoteno, prio, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)";
			$asres = mysql_query($query) or pupe_error($query);

			while ($asrow = mysql_fetch_array($asres)) {

				//	Suodatetaan extranetk�ytt�jilta muut hinnat
				if (($kukarow["extranet"] != "" or $tee == "eposti" or $yhdistetty != "" or $rajattunakyma == "JOO") or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == "" or $rajattunakyma != "JOO") ) {
					if ($edryhma != $asrow["alennusryhm�"] or $edtuoteno != $asrow["tuoteno"] or $edasryhma != $asrow["asiakasryhm�"]) {
						$edryhma 	= $asrow["alennusryhm�"];
						$edtuoteno 	= $asrow["tuoteno"];
						$edasryhma  = $asrow["asiakasryhm�"];

						if (isset($workbook_ale) and $yhdistetty == "") {
							foreach($otsik_spread as $key => $value) {
								$worksheet->write($excelrivi, $key, $asrow[$value]);
							}
							$excelrivi++;
						}

						$dada = array();
						if ($yhdistetty == "") $ashin .= "<tr>";

						foreach($otsik as $o) {

							if ($yhdistetty != "") {
								$dada[$o] = $asrow[$o];
							}
							else {
								//	Kaunistetaan tulostusta..
								if ($asrow[$o."_nimi"] != "" and $asrow[$o] != $asrow[$o."_nimi"]) {
									$arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
								}
								elseif ($o == "osasto" and $osastot[$asrow[$o]]) {
									$arvo = $asrow[$o]." - ".$osastot[$asrow[$o]];
									$osasto = $arvo;
								}
								elseif ($o == "try" and $tryt[$asrow[$o]] != "") {
									$arvo = $asrow[$o]." - ".$tryt[$asrow[$o]];
									$try = $arvo;
								}
								else {
									$arvo = $asrow[$o];
								}

								$align = "";

								if ($o == "hinta") {
									$align = " align='right' ";
								}

								if ($o == "alkupvm" or $o == "loppupvm") {
									$arvo = tv1dateconv($arvo);
								}

								$ashin .= "<td $align>$arvo</td>";
							}
						}
						if ($yhdistetty == "") $ashin .= "</tr>";

						if ($yhdistetty != "") {
							$yhdistetty_array[] = $dada;
						}
					}
				}
			}

			$ashin .= "</table>";

			if ($yhdistetty != "") {
				$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Asikashinnat")."</a>";
			}
		}
		else {
			$ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Asikashinnat")."</a>";
		}

		if ($yhdistetty != '') {

			// tehd��n yhdistetty alennustaulukko...
			$yhdistetty  = "<table><caption><font class='message'>".t("Yhdistetty alennustaulukko")."</font></caption>";

			if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
				$otsik = array("alennusryhm�",  "tuoteno", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
				$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
			}
			else {
				$otsik = array("alennusryhm�",  "tuoteno", "asiakasryhm�", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm", "tyyppi");
				$otsik_spread = array("alennusryhm�", "alennusryhm�_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
			}

			// Otsikot
			if (isset($workbook_ale)) {
				foreach($otsik_spread as $key => $value) {
					$worksheet->write($excelrivi, $key, t(ucfirst($value)), $format_bold);
				}
				$excelrivi++;
			}

			$yhdistetty  .= "<tr>";
			foreach($otsik as $o) {
				$yhdistetty .= "<th>".t(ucfirst($o))."</th>";
			}
			$yhdistetty  .= "</tr>";

			foreach ($yhdistetty_array as $key => $value) {

				if (isset($workbook_ale)) {
					foreach($otsik_spread as $key => $xvalue) {
						$worksheet->write($excelrivi, $key, $value[$xvalue]);
					}
					$excelrivi++;
				}

				$yhdistetty .= "<tr>";

				foreach ($otsik as $o) {
					//	Kaunistetaan tulostusta..
					if ($value[$o."_nimi"] != "" and $value[$o] != $value[$o."_nimi"]) {
						$arvo = $value[$o]." - ".$value[$o."_nimi"];
					}
					elseif ($o == "osasto" and $osastot[$value[$o]]) {
						$arvo = $value[$o]." - ".$osastot[$value[$o]];
						$osasto = $arvo;
					}
					elseif ($o == "try" and $tryt[$value[$o]] != "") {
						$arvo = $value[$o]." - ".$tryt[$value[$o]];
						$try = $arvo;
					}
					else {
						$arvo = $value[$o];
					}

					$align = "";

					if ($o == "alennus") {
						$align = " align='right' ";
					}

					if ($o == "alkupvm" or $o == "loppupvm") {
						$arvo = tv1dateconv($arvo);
					}

					$yhdistetty .= "<td $align>$arvo</td>";
				}

				$yhdistetty .= "</tr>";
			}
			$yhdistetty .= "</table>";
		}
		else {
			$yhdistetty = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&yhdistetty=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Yhdistetty alennustaulukko")."</a>";
		}

		// piirret��n ryhmist� ja hinnoista taulukko..
		echo "<table><tr>
				<td valign='top' class='back'>$asale</td>
				<td class='back'></td>
				<td valign='top' class='back'>$aletaulu</td>
				<td class='back'></td>
				<td valign='top' class='back'>$ashin</td>
				<td class='back'></td>
				<td valign='top' class='back'>$yhdistetty</td>
			</tr></table><br>";

		if (isset($workbook_ale) and $excelrivi>1) {
			$workbook_ale->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Alennustaulukko.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br><br>";

		}

		if ($tee == 'eposti') {
			//keksit��n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/Aletaulukko-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("".t("PDF kirjoitus ep�onnistui")." $pdffilenimi");
			fclose($fh);

			// itse print komento...
			if ($komento["Alennustaulukko"] == 'email' or $kukarow["extranet"] != "") {
				$liite = $pdffilenimi;
				$kutsu = "Alennustaulukko - ".trim($asiakasrow["nimi"]." ".$asiakasrow["nimitark"]);

				if ($kukarow["extranet"] != "") {
					require("sahkoposti.inc");
				}
				else {
					require("inc/sahkoposti.inc");
				}

				echo "<br><br>".t("Alennustaulukko l�hetet��n osoitteeseen")." $kukarow[eposti]...<br>";
			}
			elseif ($komento["Alennustaulukko"] != '' and $komento["Alennustaulukko"] != 'edi') {
				$line = exec("$komento[Alennustaulukko] $pdffilenimi");

				echo "<br><br>".t("Tulostetaan alennustaulukko $kukarow[eposti]")."...<br>";
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}
	}
}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

if (@include("inc/footer.inc"));
elseif (@include("footer.inc"));
else exit;

?>