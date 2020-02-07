<?php

$otsikko     = 'Yhteensopivuus tuotteen lisätiedot';
$otsikko_nappi   = 'Yhteensopivuus tuotteen lisätieto';

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat   = "tunnus, otsikko, arvo, jarjestys, kieli, if(status='','".t("Aktiivinen")."',if(status='L','".t("Aktiivinen")." (".t("lukittu").")','".t("Poistettu")."')) status, if(nakyvyys='','".t("Kyllä")."',if(nakyvyys='K', '".t("Kyllä")." (".t("ei otsikkoa").")', '".t("Ei")."')) nakyvyys";
$hakukentat = "tunnus, otsikko, arvo, jarjestys, kieli, yhteensopivuus_tuote_tunnus";
$jarjestys   = "jarjestys, otsikko, arvo, tunnus";
