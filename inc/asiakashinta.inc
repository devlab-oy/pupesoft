<?php
	$otsikko = 'Asiakkaan sopimushinnat';
	$otsikko_nappi = 'asiakashinta';
	
	$kentat = "tunnus, ytunnus, (SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakashinta.yhtio=asiakas.yhtio and asiakashinta.ytunnus=asiakas.ytunnus) 'Nimi', asiakas_ryhma, tuoteno, ryhma, hinta, laji, alkupvm, loppupvm";
	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, laji, alkupvm, loppupvm';
?>