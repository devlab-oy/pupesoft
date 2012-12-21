<?php

$otsikko 		= 'Lähdöt';
$otsikko_nappi 	= 'lähtö';

$kentat 	= "tunnus, pvm, (select selite from toimitustapa where lahdot.yhtio=toimitustapa.yhtio and lahdot.liitostunnus=toimitustapa.tunnus) toimitustapa, asiakasluokka, (select nimitys from varastopaikat where lahdot.yhtio=varastopaikat.yhtio and lahdot.varasto=varastopaikat.tunnus) varasto, if(lahdon_viikonpvm = 0,'Sunnuntai',if(lahdon_viikonpvm = 1,'Maanantai',if(lahdon_viikonpvm = 2,'Tiistai',if(lahdon_viikonpvm = 3,'Keskiviikko',if(lahdon_viikonpvm = 4,'Torstai',if(lahdon_viikonpvm = 5,'Perjantai','Lauantai')))))) as 'Viikonpäivä', lahdon_kellonaika as 'Lähdön kellonaika', terminaalialue, aktiivi HIDDEN_aktiivi";
$hakukentat = "tunnus, pvm, liitostunnus, asiakasluokka, varasto, lahdon_viikonpvm, lahdon_kellonaika, terminaalialue, aktiivi HIDDEN_aktiivi";

$jarjestys 	= "pvm, varasto, lahdon_viikonpvm, terminaalialue, lahdon_kellonaika";

if ($nayta_poistetut == "") {
	$rajauslisa .= " and lahdot.aktiivi != 'E' ";
}
else {
	$rajauslisa	.= "";
}

?>