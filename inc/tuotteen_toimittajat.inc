<?php

	$kentat = "tunnus, tuoteno, toimittaja as ytunnus, (SELECT nimi from toimi where tuotteen_toimittajat.yhtio=toimi.yhtio and tuotteen_toimittajat.liitostunnus=toimi.tunnus) nimi";
	$hakukentat = "tunnus, tuoteno, toimittaja, nimi";
	
	$jarjestys = 'tuoteno';
	
	$otsikko 	= "Tuotteen toimittajat";
	$otsikko_nappi = "tuotteen toimittaja";

?>
