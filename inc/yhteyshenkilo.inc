<?php

	$otsikko 		= 'Yhteyshenkilöt';
	$otsikko_nappi 	= 'yhteyshenkilö';

	if ($from == "") {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, if(tyyppi='T', (SELECT nimi from toimi where yhteyshenkilo.yhtio=toimi.yhtio and yhteyshenkilo.liitostunnus=toimi.tunnus and yhteyshenkilo.tyyppi='T'), (SELECT nimi from asiakas where yhteyshenkilo.yhtio=asiakas.yhtio and yhteyshenkilo.liitostunnus=asiakas.tunnus and yhteyshenkilo.tyyppi='A')) 'asiakas/toimittaja', nimi, titteli, concat_ws('<br>', puh, gsm, fax) 'puh/gsm/fax', email, www, nimitarkenne, osoite, postino, postitp, maa";
		$hakukentat = "tunnus, liitostunnus, nimi, titteli, puh/gsm/fax, email, www, nimitarkenne, osoite, postino, postitp, maa";
	}
	else {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, nimi, titteli, concat_ws('<br>', puh, gsm, fax) 'puh/gsm/fax', email, www, liitostunnus HIDDEN_liitostunnus, nimitarkenne, osoite, postino, postitp, maa";
		$hakukentat = "tunnus, nimi, titteli, puh/gsm/fax, email, www, liitostunnus, nimitarkenne, osoite, postino, postitp, maa";
	}

	if ($laji == "T") {
		$rajauslisa = " and yhteyshenkilo.tyyppi='T' ";
	}
	else {
		$rajauslisa = " and yhteyshenkilo.tyyppi='A' ";
	}

	$jarjestys = 'nimi';
