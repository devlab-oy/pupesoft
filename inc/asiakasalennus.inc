<?php
	$otsikko = 'Asiakkaan alennukset';
	$otsikko_nappi = 'asiakasalennus';
	
	$kentat = "tunnus, ytunnus, (SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.ytunnus=asiakas.ytunnus) 'Nimi', asiakas_ryhma, tuoteno, ryhma, alennus, alkupvm, loppupvm";
	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, alkupvm, loppupvm';
?>