<?php

require ("../inc/parametrit.inc");


echo "<font class='head'>".t("Alennustaulukot ja asiakashinnat")."</font><hr>";

echo "<font class='message'>".t("Tarkasteltava asiakas").": $ytunnus</font><br><br>";

if ($tee == 'eposti') {

	if ($komento == '') {
		$tulostimet[] = "Alennustaulukko";
		$raportti = $lopetus;
		$toimas = $ytunnus;

		require("../inc/valitse_tulostin.inc");
	}

	$lopetus = $raportti;
	$ytunnus = $toimas;

	require('../pdflib/phppdflib.class.php');

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] = 12;
	$norm["font"] = "Courier";

	$pieni["height"] = 8;
	$pieni["font"] = "Courier";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	function alku () {
		global $yhtiorow, $firstpage, $pdf, $sivu, $rectparam, $norm, $pieni, $ytunnus, $kukarow, $kala;

		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);

		
		$query =  "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus'";
		$assresult = mysql_query($query) or pupe_error($query);
		$assrow = mysql_fetch_array($assresult);
		
		

		//Otsikko
		$pdf->draw_rectangle(830, 20,  810, 580, $firstpage, $rectparam);
		$pdf->draw_text(30,  815,  $yhtiorow["nimi"], $firstpage, $pieni);
		$pdf->draw_text(120, 815, "".t("Asiakkaan")." ($ytunnus) $assrow[nimi] $assrow[nimitark] ".t("alennustaulukko")."", $firstpage);
		$pdf->draw_text(500, 815,  "".t("Sivu").": $sivu", $firstpage, $pieni);

		if ($sivu == 1) {
			//Vasen sarake
			//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
			$pdf->draw_text(50, 729, t("Osoite", $kieli), 	$firstpage, $pieni);
			$pdf->draw_text(50, 717, $assrow["nimi"], 		$firstpage, $norm);
			$pdf->draw_text(50, 707, $assrow["nimitark"],	$firstpage, $norm);
			$pdf->draw_text(50, 697, $assrow["osoite"], 	$firstpage, $norm);
			$pdf->draw_text(50, 687, $assrow["postino"]." ".$assrow["postitp"], $firstpage, $norm);
			$pdf->draw_text(50, 677, $assrow["maa"], 		$firstpage, $norm);
			
			$kala = 630;
			
		}
		
		$pdf->draw_text(30,  $kala, t("Osasto"), 			$firstpage);
		$pdf->draw_text(90,  $kala, t("Tuoteryh"), 			$firstpage);
		$pdf->draw_text(150, $kala, t("Selite"), 			$firstpage);
		$pdf->draw_text(370, $kala, t("Aleryhm‰"), 			$firstpage);
		$pdf->draw_text(450, $kala, t("Alennusprosentti"),	$firstpage);
		
		$kala-=20;
		
	}

	function rivi ($firstpage) {
		global $pdf, $row, $tryro, $ale, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $kala;

		if (($sivu == 1 and $lask == 40) or ($sivu > 1 and $lask == 50)) {
			$sivu++;
			$firstpage = alku();
			$kala = 770;
			$lask = 1;
		}

		$pdf->draw_text(30, $kala,$row["osasto"], 		$firstpage, $norm);
		$pdf->draw_text(90, $kala,$row["try"], 			$firstpage, $norm);
		$pdf->draw_text(150, $kala,$tryro["selitetark"], $firstpage, $norm);
		$pdf->draw_text(350, $kala,sprintf('%10s',sprintf('%.2d',$row["aleryhma"])), 	$firstpage, $norm);
		$pdf->draw_text(450, $kala,sprintf('%10s',sprintf('%.2d',$ale))."%", 			$firstpage, $norm);


		$kala = $kala - 15;
		$lask++;
	}


	//tehd‰‰n eka sivu
	alku();
}


if ($lopetus != '') {
	// Jotta urlin parametrissa voisi p‰‰ss‰t‰ toisen urlin parametreineen
	$lopetus = str_replace('////','?', $lopetus);
	$lopetus = str_replace('//','&',  $lopetus);
	echo "<br><br>";
	echo "<a href='$lopetus'>".t("Palaa edelliseen n‰kym‰‰n")."</a><br>";	
}

echo "<a href='$PHP_SELF?tee=eposti&ytunnus=$ytunnus&from=$from'>".t("Tulosta alennustaulukko")."</a><br><br>";


// hardcoodataan v‰rej‰
$cmyynti = "#ccccff";
$ckate   = "#ff9955";
$ckatepr = "#00dd00";
$maxcol  = 12; // montako columnia n‰yttˆ on


// tehd‰‰n asiakkaan alennustaulukot
$query = "select * from perusalennus where yhtio='$kukarow[yhtio]' order by ryhma+0";
$result = mysql_query($query) or pupe_error($query);

$asale  = "<table>";
$asale .= "<tr><th>".t("Alennusryhm‰")."</th><th>".t("Alennusprosentti")."</th></tr>";

while ($alerow = mysql_fetch_array($result)) {

	$ryhma = $alerow['ryhma'];
	$ale   = $alerow['alennus'];

	$query = "select * from asiakasalennus where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus' and ryhma='$ryhma'";
	$asres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($asres)>0) {
		$asrow = mysql_fetch_array($asres);
		$ryhma = $asrow['ryhma'];
		$ale   = $asrow['alennus'];
	}

	if ($ale != 0.00) {
		$asale .= "<tr>
			<td><font class='info'>$ryhma	<font></td>
			<td><font class='info'>$ale		<font></td>
			</tr>";
	}
}
$asale .= "</table>";


// haetaan asiakas hintoaja
$ashin  = "<table>";
$ashin .= "<tr><th>".t("Tuotenumero")."</th><th>".t("Nimitys")."</th><th>".t("Hinta")."</th></tr>";

$query = "	select tuote.tuoteno, tuote.nimitys, asiakashinta.hinta 
			from asiakashinta, tuote 
			where asiakashinta.yhtio='$kukarow[yhtio]' 
			and ytunnus='$ytunnus' 
			and asiakashinta.yhtio=tuote.yhtio 
			and asiakashinta.tuoteno=tuote.tuoteno
			and ((asiakashinta.alkupvm <= now() and asiakashinta.loppupvm >= now()) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))";
$result = mysql_query($query) or pupe_error($query);

while ($row = mysql_fetch_array($result)) {
	$ashin .= "<tr>
		<td><font class='info'>$row[tuoteno]<font></td>
		<td><font class='info'>$row[nimitys]<font></td>
		<td><font class='info'>$row[hinta]	<font></td>
		</tr>";
}

if (mysql_num_rows($result)==0) {
	$ashin .= "<tr><td colspan='3'><font class='info'>".t("Asiakashintoja ei lˆytynyt").".</font></td></tr>";
}

$ashin .= "</table>";


$aletaulu  = "<table>";
$aletaulu .= "<tr><th>".t("Os")."</th><th>".t("Osasto")."</th><th>".t("Try")."</th><th>".t("Tuoteryhm‰")."</th><th>".t("Aleryhm‰")."</th><th>%</th></tr>";

$query = "select distinct osasto, try, aleryhma
			from tuote
			where tuote.yhtio='$kukarow[yhtio]'
			and status in ('', 'A')
			and osasto != 0
			and try != 0			
			order by osasto+0, try+0, aleryhma+0";
$result = mysql_query($query) or pupe_error($query);

while ($row = mysql_fetch_array($result)) {

	$query = "select * from asiakasalennus where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus' and ryhma='$row[aleryhma]'";
	$alere = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($alere)>0) {
		$alerow = mysql_fetch_array($alere);
		$mita   = "<font class='katepros'>".t("asiakas")."</font>";
		$ale    = $alerow['alennus'];
	}
	else {
		$query = "select * from perusalennus where yhtio='$kukarow[yhtio]' and ryhma='$row[aleryhma]'";
		$alere = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($alere)>0) {
			$alerow = mysql_fetch_array($alere);
			$mita   = "".t("Perus")."";
			$ale    = $alerow['alennus'];
		}
		else {
			$mita = "";
			$ale  = "";
		}
	}

	$query = "select selitetark from avainsana where yhtio='$kukarow[yhtio]' and laji='try' and selite='$row[try]'";
	$tryre = mysql_query($query) or pupe_error($query);
	$tryro = mysql_fetch_array($tryre);

	$query = "select selitetark from avainsana where yhtio='$kukarow[yhtio]' and laji='osasto' and selite='$row[osasto]'";
	$osare = mysql_query($query) or pupe_error($query);
	$osaro = mysql_fetch_array($osare);

	// n‰ytet‰‰n rivi vaan jos ale on olemassa ja se on erisuuri kuin nolla. Lis‰ksi tuoteryhm‰ll‰ on pakko olla nimi...
	if ($ale != "" and $ale != 0.00 and $tryro["selitetark"] != "" and $osaro["selitetark"] != "") {
		$aletaulu .= "<tr>
			<td><font class='info'>$row[osasto]<font></td>
			<td><font class='info'>$osaro[selitetark]<font></td>
			<td><font class='info'>$row[try]<font></td>
			<td><font class='info'>$tryro[selitetark]<font></td>
			<td><font class='info'>$row[aleryhma]</td>
			<td><font class='info'>$ale<font></td>
			</tr>";
	
		if ($tee == 'eposti') {
			rivi($firstpage);
		}	
	}

	
}

$aletaulu .= "</table>";



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





require ("../inc/footer.inc");

?>