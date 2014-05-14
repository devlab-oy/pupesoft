<?php
$otsikko = 'Laskun lisätiedot';
$otsikko_nappi = 'laskun lisätiedot';

$kentat = 'tunnus, otunnus, laskutus_nimi, laskutus_nimitark, laskutus_osoite';
$jarjestys = 'tunnus DESC';

$uusilukko = "LUKOSSA";

if (!isset($lukitse_avaimeen) or (int) $lukitse_avaimeen == 0) $rajauslisa = " and yhtio='ÄLÄNÄYTÄ' ";
