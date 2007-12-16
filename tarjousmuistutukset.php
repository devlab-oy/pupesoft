#!/usr/bin/php
<?php


require ("inc/connect.inc");
require ("inc/functions.inc");

function tee_viesti() {
	global $row;
	
	$viesti = sprintf("%-8s", $row["tarjous"]);
	$viesti .= sprintf("%-5s", $row["seuranta"]);
	$viesti .= "$row[nimi] $row[nimitark], $row[asiakkaan_kohde]\n";
	if($row["yhteyshenkilo_tekninen"] > 0) {
		$query = "	SELECT * 
					FROM yhteyshenkilo 
					WHERE yhtio = '$row[yhtio]' and tunnus = '$row[yhteyshenkilo_tekninen]'";
		$result = mysql_query($query) or pupe_error($query);					
		$yrow = mysql_fetch_array($result);
		$viesti .= sprintf("%-14s", "Yhteyshenkilö:")." $yrow[nimi]\n";
		$viesti .= sprintf("%-14s", "Gsm:")." $yrow[gsm]\n";
		$viesti .= sprintf("%-14s", "Puh:")." $yrow[puh]\n";
		$viesti .= sprintf("%-14s", "email:")." $yrow[email]\n";
	}
	$viesti .= "\n";
	
	return $viesti;
}

function mailaa() {
	global $row, $edlaatija, $viesti_1, $viesti_2, $viesti_3, $viesti_4;
	
	if($viesti_4.$viesti_3.$viesti_2.$viesti_1 != "") {
		$query = "	SELECT * 
					FROM kuka
					WHERE yhtio = '$row[yhtio]' and kuka = '$edlaatija'";
		$result = mysql_query($query) or pupe_error($query);
		$yrow = mysql_fetch_array($result);

		$viesti = "\n\nTässä olisi vähän listaa kelle pitäis ensiviikolla soitella!\n\n$viesti_1\n$viesti_2\n$viesti_3\n\n\n$viesti_4";

		$viesti_1 = $viesti_2 = $viesti_3 = $viesti_4 = "";

		//echo "\n\nLähetettiin $yrow[eposti]:\n$viesti\n";		
	}
	else {
		return true;
	}
	
	return mail($yrow["eposti"], "Tarjousmuistutus!", $viesti);
}


//	Haetaan käyttäjittäin umpeutuneet tai kohta umpeutuvat tarjoukset
$query = "	SELECT versio.*, if(versio.tunnusnippu>0, versio.tunnusnippu, versio.tunnus) tarjous, versio.laatija laatija,
				laskun_lisatiedot.seuranta, laskun_lisatiedot.yhteyshenkilo_tekninen, 
				asiakkaan_kohde.kohde asiakkaan_kohde,
				DATEDIFF(now(), versio.luontiaika) kulunut,
				DATEDIFF(versio.luontiaika, date_sub(now(), INTERVAL laskun_lisatiedot.tarjouksen_voimaika day)) pva
			FROM lasku
			JOIN lasku versio ON versio.yhtio=lasku.yhtio and versio.tunnus=if(lasku.tunnusnippu>0,(select max(l.tunnus) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tila='T'), lasku.tunnus)
			LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = versio.yhtio and laskun_lisatiedot.otunnus=versio.tunnus			
			LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde			
			WHERE lasku.tila = 'T' and lasku.alatila NOT IN ('B','T','X')
			ORDER BY versio.laatija, pva";
$result = mysql_query($query) or pupe_error($query);					

$edlaatija = "";

while($row=mysql_fetch_array($result)) {
	
	//	Lähetetään maili
	if($row["laatija"] != $edlaatija and $edlaatija != "") {
		mailaa();
	}
	
	//	Jos meidän 1. kontakti asiakkaalle sijoittuu seuraavalle viikolle
	if($row["kulunut"] >= 9 and $row["kulunut"] <= 14) {
		if($viesti_1 == "") {
			$viesti_1 = "Ensiviikolla 1. kontaktit asiakkaisiin\n";
		}
		$viesti_1 .= tee_viesti();
	}
	//	Jos meidän 2. kontakti asiakkaalle sijoittuu seuraavalle viikolle
	elseif($row["kulunut"] >= 40 and $row["kulunut"] <= 45) {
		if($viesti_2 == "") {
			$viesti_2 = "Ensiviikolla 2. kontaktit asiakkaisiin\n";
		}
		$viesti_2 .= tee_viesti();
	}
	//	 Jos viimeinen voitelu sattuu seuraavalle kuukaudelle

	if($row["pva"] >= 0 and $row["pva"] <= 6) {
		if($viesti_3 == "") {
			$viesti_3 = "Seuraavien asiakkaiden tarjoukset umpeutuvat ensiviikolla\n";
		}
		$viesti_3 .= tee_viesti();
	}
	elseif($row["pva"] < 0) {
		if($viesti_4 == "") {
			$viesti_4 = "SEURAAVAT TARJOUKSET OVAT JO UMPEUTUNEET JA KÄSITTELEMÄTTÄ!!!\n";
		}
		$viesti_4 .= tee_viesti();		
	}
	
	$edlaatija = $row["laatija"];
	$edrow = $row;
}

$row = $edrow;
mailaa();


?>
