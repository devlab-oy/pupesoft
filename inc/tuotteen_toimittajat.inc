<?php

$otsikko     = "Tuotteen toimittajat";
$otsikko_nappi  = "tuotteen toimittaja";

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat = "tunnus, tuoteno, toim_tuoteno, (SELECT nimi from toimi where tuotteen_toimittajat.yhtio=toimi.yhtio and tuotteen_toimittajat.liitostunnus=toimi.tunnus) nimi";
$hakukentat = "tunnus, tuoteno, toim_tuoteno, nimi";

$jarjestys = 'jarjestys';
