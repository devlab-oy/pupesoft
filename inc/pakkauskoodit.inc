<?php

$otsikko = 'Pakkauskoodit';
$otsikko_nappi   = 'pakkauskoodi';

$kentat = "tunnus, (SELECT concat(pakkaus, ' - ', pakkauskuvaus) FROM pakkaus WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = pakkauskoodit.pakkaus) AS pakkaus, (SELECT nimi FROM rahdinkuljettajat WHERE yhtio = '{$kukarow['yhtio']}' AND koodi = pakkauskoodit.rahdinkuljettaja) AS rahdinkuljettaja, koodi";
$jarjestys = 'pakkaus, rahdinkuljettaja, koodi';
$hakukentat = "pakkaus, rahdinkuljettaja, koodi";
