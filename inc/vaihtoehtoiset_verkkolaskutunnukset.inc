<?php

$kentat = "tunnus, (SELECT toimi.nimi FROM toimi WHERE toimi.yhtio = vaihtoehtoiset_verkkolaskutunnukset.yhtio AND toimi.tunnus = vaihtoehtoiset_verkkolaskutunnukset.toimi_tunnus) AS toimi_tunnus, kohde_sarake, arvo";
$hakukentat = "tunnus, toimi_tunnus, kohde_sarake, arvo";

$jarjestys = 'toimi_tunnus';

?>