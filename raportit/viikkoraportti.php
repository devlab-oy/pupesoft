#!/usr/bin/php
<?php

if ($argc == 0) die ("<br><br>Tätä scriptiä voi ajaa vain komentoriviltä!");

require ("../inc/connect.inc");

// hmm.. jännää
$kukarow['yhtio']=addslashes(trim($argv[1]));
$pomomail=addslashes(trim($argv[2]));
$pomomail2=addslashes(trim($argv[3]));

$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
$yhtiores = mysql_query($query) or die($query);

if (mysql_num_rows($yhtiores)==1) {
	$yhtiorow = mysql_fetch_array($yhtiores);
}
else {
	die ("Yhtiö $kukarow[yhtio] ei löydy!");
}

echo "Viikkoraportti\n--------------\n\n";

$query = "select distinct myyjanro from asiakas where yhtio='$kukarow[yhtio]' and myyjanro > 0";
$myyre = mysql_query($query) or die($query);	

echo "Myyjät haettu...";

$query = "select distinct tuotemerkki from tuote where yhtio='$kukarow[yhtio]' order by 1";
$merre = mysql_query($query) or die($query);	

echo "Merkit haettu... Alotellaan.\n";
echo "--------------------------------------------\n";

while ($myyjarow = mysql_fetch_array ($myyre)) {

	$query = "select ytunnus, nimi from asiakas where yhtio='$kukarow[yhtio]' and myyjanro='$myyjarow[myyjanro]'";
	$asire = mysql_query($query) or die($query);	

	// merkit kelataan kalkuun	
	mysql_data_seek ($merre, 0);

	$sivu = "asiakas\tnimi\t";
	$sivu .= "Yhteensä tämä\tYhteensä edel\t";
	while ($merkkirow = mysql_fetch_array ($merre)) {
	
		$sivu .= "$merkkirow[tuotemerkki] tämä\t$merkkirow[tuotemerkki] edel\t";
	}
	
	$sivu .= "\n";
	
	while ($asiakasrow = mysql_fetch_array ($asire)) {
	
		// merkit kelataan kalkuun	
		mysql_data_seek ($merre, 0);

		$sivu .= "$asiakasrow[ytunnus]\t$asiakasrow[nimi]\t";
		
		$query = "SELECT 
				sum(if(lasku.tapvm >= DATE_SUB(now(),INTERVAL 1 YEAR),tilausrivi.rivihinta,0)) ceur,
				sum(if(lasku.tapvm < DATE_SUB(now(),INTERVAL 1 YEAR),tilausrivi.rivihinta,0)) eeur					
				FROM lasku use index (yhtio_tila_ytunnus_tapvm), tilausrivi use index (uusiotunnus_index), tuote use index (tuoteno_index)
				WHERE lasku.yhtio ='$kukarow[yhtio]' and
				lasku.tila='U' and
				lasku.alatila='X' and
				lasku.ytunnus='$asiakasrow[ytunnus]' and
				lasku.tapvm>date_sub(now(),INTERVAL 2 YEAR) and 
				tilausrivi.yhtio=lasku.yhtio and
				tilausrivi.uusiotunnus=lasku.tunnus and
				tuote.yhtio=lasku.yhtio and
				tuote.tuoteno=tilausrivi.tuoteno";

		$sumsumres = mysql_query($query) or die($query);
		$sumsumrow = mysql_fetch_array ($sumsumres);

		$sumsumrow['ceur'] = str_replace('.',',',$sumsumrow['ceur']);
		$sumsumrow['eeur'] = str_replace('.',',',$sumsumrow['eeur']);
		$sivu .= "$sumsumrow[ceur]\t$sumsumrow[eeur]\t";
		
		while ($merkkirow = mysql_fetch_array ($merre)) {

			$query = "SELECT 
					sum(if(lasku.tapvm >= DATE_SUB(now(),INTERVAL 1 YEAR),tilausrivi.rivihinta,0)) ceur,
					sum(if(lasku.tapvm < DATE_SUB(now(),INTERVAL 1 YEAR),tilausrivi.rivihinta,0)) eeur					
					FROM lasku use index (yhtio_tila_ytunnus_tapvm), tilausrivi use index (uusiotunnus_index), tuote use index (tuoteno_index)
					WHERE lasku.yhtio ='$kukarow[yhtio]' and
					lasku.tila='U' and
					lasku.alatila='X' and
					lasku.ytunnus='$asiakasrow[ytunnus]' and
					lasku.tapvm>date_sub(now(),INTERVAL 2 YEAR) and 
					tilausrivi.yhtio=lasku.yhtio and
					tilausrivi.uusiotunnus=lasku.tunnus and
					tuote.yhtio=lasku.yhtio and
					tuote.tuoteno=tilausrivi.tuoteno and
					tuote.tuotemerkki='$merkkirow[tuotemerkki]'";

			$sumres = mysql_query($query) or die($query);
			$sumrow = mysql_fetch_array ($sumres);

			$sumrow['ceur'] = str_replace('.',',',$sumrow['ceur']);
			$sumrow['eeur'] = str_replace('.',',',$sumrow['eeur']);
			$sivu .= "$sumrow[ceur]\t$sumrow[eeur]\t";
		}

		$sivu .= "\n";
	}

	$query = "select eposti, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja='$myyjarow[myyjanro]'";
	$kukre = mysql_query($query) or die($query);
	$kukro = mysql_fetch_array ($kukre);

	if ($kukro["eposti"] == "") {
	        echo "Myyjällä $kukro[nimi] ($myyjarow[myyjanro]) ei ole sähköpostiosoitetta!\n";
	}
	else {
	        echo "Lähetetään meili $kukro[eposti].\n";
			$nyt = date('d.m.y');
	        $bound = uniqid(time()."_") ;

	        $header   = "From: <$yhtiorow[postittaja_email]>\r\n";
	        $header  .= "MIME-Version: 1.0\r\n" ;
	        $header  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

	        $content  = "--$bound\r\n";

	        $content .= "Content-Type: application/vnd.ms-excel; name=\"Excel-viikkoraportti$nyt.xls\"\r\n" ;
	        $content .= "Content-Transfer-Encoding: base64\r\n" ;
	        $content .= "Content-Disposition: attachment; filename=\"Excel-viikkoraportti$nyt.xls\"\r\n\r\n";

	        $content .= chunk_split(base64_encode($sivu));
	        $content .= "\r\n" ;

	        $content .= "--$bound\r\n";

	        $content .= "Content-Type: text/x-comma-separated-values; name=\"OpenOffice-viikkoraportti$nyt.csv\"\r\n" ;
	        $content .= "Content-Transfer-Encoding: base64\r\n" ;
	        $content .= "Content-Disposition: attachment; filename=\"OpenOffice-viikkoraportti$nyt.csv\"\r\n\r\n";

	        $content .= chunk_split(base64_encode($sivu));
	        $content .= "\r\n" ;

	        $content .= "--$bound\r\n";

	        $boob = mail($kukro["eposti"],  "Viikkoraportti $kukro[nimi] ".date("d.m.Y"), $content, $header);
			if ($pomomail != '') {
				$boob = mail($pomomail,  "Viikkoraportti $kukro[nimi] ".date("d.m.Y"), $content, $header);
			}
			if ($pomomail2 != '') {
				$boob = mail($pomomail2,  "Viikkoraportti $kukro[nimi] ".date("d.m.Y"), $content, $header);
			}
	}
	
}

?>