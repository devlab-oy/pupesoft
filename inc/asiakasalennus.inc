<?php
	$otsikko = 'Asiakkaan alennukset';
	$otsikko_nappi = 'asiakasalennus';

	if ($from == "") {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus',(SELECT concat(dynaaminen_puu.tunnus,'<br>', dynaaminen_puu.nimi) FROM dynaaminen_puu WHERE dynaaminen_puu.tunnus=asiakasalennus.asiakas_segmentti and dynaaminen_puu.yhtio=asiakasalennus.yhtio) 'Asiakaspuu', asiakas_ryhma 'Asiakasryhmä',(select concat(selite,' <br> ',selitetark) from avainsana where laji='piiri' and selite=asiakasalennus.piiri and avainsana.yhtio=asiakasalennus.yhtio) 'Piiri', tuoteno, ryhma 'Alennusryhmä', alennus, alennuslaji, minkpl 'Minkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}
	else {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, concat_ws('<br>', tuoteno, ryhma) 'Tuoteno/Alennusryhmä', alennus, alennuslaji, minkpl 'Minkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}
	// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
	$hakukentat = "tunnus, asiakas, ytunnus,asiakas_segmentti, asiakas_ryhma,piiri, tuoteno, ryhma, alennus, alennuslaji, minkpl, alkupvm/loppupvm";

	$jarjestys = 'ytunnus, asiakas_ryhma,piiri, tuoteno, ryhma, minkpl, alkupvm, loppupvm';

	if ($nayta_eraantyneet != "") {
		$rajauslisa = " and asiakasalennus.loppupvm > '0000-00-00' and asiakasalennus.loppupvm < now() ";
	}
?>