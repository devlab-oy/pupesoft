<?php

$otsikko = 'Toimittaja alennukset';
$otsikko_nappi = 'toimittaja alennus';

$kentat 	= "tunnus, tuoteno, ryhma, (SELECT nimi from toimi where toimittajaalennus.yhtio=toimi.yhtio and toimittajaalennus.toimittaja=toimi.tunnus) toimittaja, alennus, alennuslaji, minkpl, monikerta, alkupvm, loppupvm";

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$hakukentat = "tunnus, tuoteno, ryhma,  toimittaja, alennus, alennuslaji, minkpl, monikerta, alkupvm, loppupvm";

$jarjestys = 'tuoteno, ryhma, toimittaja, alennus, alennuslaji, minkpl, monikerta, alkupvm, loppupvm';

?>