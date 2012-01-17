<?php

$otsikko = 'Toimittajahinta';
$otsikko_nappi = 'toimittajahinta';

$kentat 	= "tunnus, tuoteno, ryhma, (SELECT nimi from toimi where toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.toimittaja=toimi.tunnus) toimittaja, hinta, laji, minkpl, maxkpl, alkupvm, loppupvm";

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$hakukentat = "tunnus, tuoteno, ryhma,  toimittaja, hinta, laji, minkpl, maxkpl, alkupvm, loppupvm";

$jarjestys = 'tuoteno, ryhma, toimittaja, hinta, laji, minkpl, maxkpl, alkupvm, loppupvm';

?>