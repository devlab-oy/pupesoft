<?php

	$otsikko = 'Asiakkaan alennukset';
	$otsikko_nappi = 'asiakasalennus';

	if ($from == "") {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "asiakasalennus.tunnus,
		concat_ws('<br>', asiakasalennus.asiakas, (SELECT asiakas.nimi FROM asiakas WHERE asiakasalennus.yhtio = asiakas.yhtio AND asiakasalennus.asiakas = asiakas.tunnus)) Asiakas,
		asiakasalennus.ytunnus 'Ytunnus',
#		concat_ws('<br>', asiakasalennus.ytunnus, (SELECT group_concat(distinct asiakas.nimi separator '<br>') FROM asiakas WHERE asiakasalennus.yhtio = asiakas.yhtio and asiakasalennus.ytunnus = asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus',
		(SELECT concat(dynaaminen_puu.tunnus, '<br>', dynaaminen_puu.nimi) FROM dynaaminen_puu WHERE dynaaminen_puu.tunnus = asiakasalennus.asiakas_segmentti AND dynaaminen_puu.yhtio = asiakasalennus.yhtio) 'Asiakaspuu',
		asiakasalennus.asiakas_ryhma 'Asiakasryhmä',
		(SELECT concat(avainsana.selite, '<br>', avainsana.selitetark) FROM avainsana WHERE avainsana.laji = 'piiri' AND avainsana.selite = asiakasalennus.piiri AND avainsana.yhtio = asiakasalennus.yhtio) 'Piiri',
		asiakasalennus.tuoteno,
		asiakasalennus.ryhma 'Alennusryhmä',
		asiakasalennus.alennus,
		asiakasalennus.alennuslaji,
		asiakasalennus.minkpl 'Minkpl',
		concat_ws('<br>', asiakasalennus.alkupvm, asiakasalennus.loppupvm) 'Alku/Loppu'";
	}
	else {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "asiakasalennus.tunnus,
		concat_ws('<br>', asiakasalennus.tuoteno, asiakasalennus.ryhma) 'Tuoteno/Alennusryhmä',
		asiakasalennus.alennus,
		asiakasalennus.alennuslaji,
		asiakasalennus.minkpl 'Minkpl',
		concat_ws('<br>', asiakasalennus.alkupvm, asiakasalennus.loppupvm) 'Alku/Loppu'";
	}
	// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
	$hakukentat = "tunnus, asiakas, ytunnus,asiakas_segmentti, asiakas_ryhma,piiri, tuoteno, ryhma, alennus, alennuslaji, minkpl, alkupvm/loppupvm";

	$jarjestys = "asiakasalennus.ytunnus, asiakasalennus.asiakas_ryhma, asiakasalennus.piiri, asiakasalennus.tuoteno, asiakasalennus.ryhma, asiakasalennus.minkpl, asiakasalennus.alkupvm, asiakasalennus.loppupvm";

	if ($nayta_eraantyneet != "") {
		$rajauslisa = " and asiakasalennus.loppupvm > '0000-00-00' and asiakasalennus.loppupvm < now() ";
	}
