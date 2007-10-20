#!/usr/bin/php
<?php


require ("inc/connect.inc");
require ("inc/functions.inc");

//	Haetaan käyttäjittäin umpeutuneet tai kohta umpeutuvat tarjoukset
$query = "	SELECT lasku.*, lasku.tunnusnippu tarjous, laskun_lisatiedot.seuranta, kuka.nimi tekija, kuka.eposti,
			if(date_add(lasku.luontiaika, interval yhtion_parametrit.tarjouksen_voimaika day) >= now(), 'Aktiiviset', 'Umpeutuneet') voimassa, 
			yhteyshenkilo_tekninen.nimi yhteyshenkilo, yhteyshenkilo_tekninen.gsm yhteyshenkilo_gms, yhteyshenkilo_tekninen.puh yhteyshenkilo_puh, yhteyshenkilo_tekninen.email yhteyshenkilo_email,
			asiakkaan_kohde.kohde asiakkaan_kohde,
			(select max(tunnus) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu) viimeisin,
			DATEDIFF(lasku.luontiaika, date_sub(now(), INTERVAL yhtion_parametrit.tarjouksen_voimaika day)) pva
			FROM lasku
			JOIN yhtion_parametrit ON lasku.yhtio = yhtion_parametrit.yhtio
			JOIN kuka ON kuka.yhtio = lasku.yhtio and kuka.kuka=lasku.laatija
			LEFT JOIN laskun_lisatiedot ON lasku.yhtio = laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus			
			LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus
			LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde			
			WHERE tila = 'T' and alatila NOT IN ('B','T','X')
			HAVING lasku.tunnus=viimeisin and pva < 21 and pva > -300
			ORDER BY lasku.laatija, voimassa, pva";
$result = mysql_query($query) or pupe_error($query);
while($row=mysql_fetch_array($result)) {
	if($row["laatija"] != $edlaatija) {
		
		if($viesti != "") {
			$viesti .= "\n\nSoitteles asiakkaalle tai käy sulkemassa tarjous!\n\nNiinku olis jo!\n";
			//mail("tuomas.koponen@oss-solutions.fi", "Tarjousmuistutus!", $viesti);			
			echo $viesti;
		}
		
		$viesti = "$row[tekija], sinulla on kesken seuraavat tarjoukset\n";
		$edtila = "";
	}
	
	if($row["voimassa"] != $edtila) {
		$viesti .= "\n\n".$row["voimassa"]." tilaukset:\n\n";
	}

	$viesti .= sprintf("%-8s", $row["tarjous"]);
	$viesti .= sprintf("%-5s", $row["seuranta"]);
	$viesti .= "$row[nimi] $row[nimitark], $row[asiakkaan_kohde] ($row[pva])\n";
	if($row[yhteyshenkilo] != "") {
		$viesti .= sprintf("%-14s", "Yhteyshenkilö:")." $row[yhteyshenkilo]\n";
		$viesti .= sprintf("%-14s", "Gsm:")." $row[yhteyshenkilo_gsm]\n";
		$viesti .= sprintf("%-14s", "Puh:")." $row[yhteyshenkilo_puh]\n";
		$viesti .= sprintf("%-14s", "email:")." $row[yhteyshenkilo_email]\n";
	}	
	$viesti .= "\n";
	
	$edlaatija = $row["laatija"];
	$edtila = $row["voimassa"];	
}



		

?>