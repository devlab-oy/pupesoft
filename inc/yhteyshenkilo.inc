<?php

	$otsikko 		= 'Yhteyshenkilöt';
	$otsikko_nappi 	= 'yhteyshenkilö';

	if ($from == "") {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, if(tyyppi='T', (SELECT nimi from toimi where yhteyshenkilo.yhtio=toimi.yhtio and yhteyshenkilo.liitostunnus=toimi.tunnus), (SELECT nimi from asiakas where yhteyshenkilo.yhtio=asiakas.yhtio and yhteyshenkilo.liitostunnus=asiakas.tunnus)) 'asiakas/toimittaja', nimi, titteli, concat_ws('<br>', puh, gsm, fax) 'puh/gsm/fax', email, www";
		$hakukentat = "tunnus, liitostunnus, nimi, titteli, puh/gsm/fax, email, www";
	}
	else {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, nimi, titteli, concat_ws('<br>', puh, gsm, fax) 'puh/gsm/fax', email, www, liitostunnus HIDDEN_liitostunnus";
		$hakukentat = "tunnus, nimi, titteli, puh/gsm/fax, email, www, liitostunnus";
	}

	$jarjestys = 'nimi';
?>