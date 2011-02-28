<?php
	$otsikko 		= 'Asiakkaan sopimushinnat';
	$otsikko_nappi 	= 'asiakashinta';

	if ($from == "") {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where asiakashinta.yhtio=asiakas.yhtio and asiakashinta.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakashinta.yhtio=asiakas.yhtio and asiakashinta.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus',(SELECT concat(dynaaminen_puu.tunnus,'<br>', dynaaminen_puu.nimi) FROM dynaaminen_puu WHERE dynaaminen_puu.tunnus=asiakashinta.asiakas_segmentti and dynaaminen_puu.yhtio=asiakashinta.yhtio) 'Asiakaspuu', asiakas_ryhma 'Asiakasryhmä',(select concat(selite,' <br> ',selitetark) from avainsana where laji='piiri' and selite=asiakashinta.piiri and avainsana.yhtio=asiakashinta.yhtio) 'Piiri', tuoteno, ryhma 'Alennusryhmä', hinta, laji, concat_ws('<br>',minkpl,maxkpl) 'Min/Maxkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}
	else {
		// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
		$kentat = "tunnus, concat_ws('<br>', tuoteno, ryhma) 'Tuoteno/Alennusryhmä', hinta, laji, concat_ws('<br>',minkpl,maxkpl) 'Min/Maxkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}

	// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
	$hakukentat = "tunnus, asiakas, ytunnus, asiakas_segmentti,asiakas_ryhma,piiri, tuoteno, ryhma, hinta, laji, minkpl/maxkpl, alkupvm/loppupvm";

	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, laji, minkpl, alkupvm, loppupvm';

	if ($nayta_eraantyneet != "") {
		$rajauslisa = " and asiakashinta.loppupvm > '0000-00-00' and asiakashinta.loppupvm < now() ";
	}
?>