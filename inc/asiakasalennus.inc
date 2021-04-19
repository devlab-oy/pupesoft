<?php
	$otsikko = 'Asiakkaan alennukset';
	$otsikko_nappi = 'asiakasalennus';
	
	if ($from == "") {
		$kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus', asiakas_ryhma 'Asiakasryhmä', tuoteno, ryhma 'Alennusryhmä', alennus, concat_ws('<br>',minkpl,maxkpl) 'Min/Maxkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}
	else {
		$kentat = "tunnus, concat_ws('<br>', tuoteno, ryhma) 'Tuoteno/Alennusryhmä', alennus, concat_ws('<br>',minkpl,maxkpl) 'Min/Maxkpl', concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
	}

	$hakukentat = "tunnus, asiakas, ytunnus, asiakas_ryhma, tuoteno, ryhma, alennus, minkpl/maxkpl, alkupvm/loppupvm";
	
	$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, minkpl, alkupvm, loppupvm';
	
	if ($nayta_eraantyneet != "") {
		$rajauslisa = " and asiakasalennus.loppupvm > '0000-00-00' and asiakasalennus.loppupvm < now() ";
	}
?>