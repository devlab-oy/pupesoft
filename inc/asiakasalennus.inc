<?php
	$otsikko = 'Asiakkaan alennukset';
	$otsikko_nappi = 'asiakasalennus';
	
	$kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus', asiakas_ryhma, tuoteno, ryhma, alennus, alkupvm, loppupvm";
	$hakukentat = "tunnus, asiakas, ytunnus, asiakas_ryhma, tuoteno, ryhma, alennus, laji, alkupvm, loppupvm";
	
	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, alkupvm, loppupvm';
?>