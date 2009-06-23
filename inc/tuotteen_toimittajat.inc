<?php

	$kentat = "tunnus, tuoteno, toimittaja as ytunnus, (SELECT nimi from toimi where tuotteen_toimittajat.yhtio=toimi.yhtio and tuotteen_toimittajat.liitostunnus=toimi.tunnus) toimittaja";
	$hakukentat = "tunnus, tuoteno, toimittaja";
	
	$jarjestys = 'tuoteno';
	
	$otsikko 	= "Tuotteen toimittajat";
	$otsikko_nappi = "tuotteen toimittaja";

?>
