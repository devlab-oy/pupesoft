<?php
	$otsikko = 'Asiakkaan sopimushinnat';
	$otsikko_nappi = 'asiakashinta';
	
	$kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where asiakashinta.yhtio=asiakas.yhtio and asiakashinta.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakashinta.yhtio=asiakas.yhtio and asiakashinta.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus', asiakas_ryhma, tuoteno, ryhma, hinta, laji, alkupvm, loppupvm";
	$hakukentat = "tunnus, asiakas, ytunnus, asiakas_ryhma, tuoteno, ryhma, hinta, laji, alkupvm, loppupvm";
	
	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, laji, alkupvm, loppupvm';
?>