<?php

$otsikko = 'Huoltosyklit';
$otsikko_nappi = 'Huoltosykli';

$kentat = "concat_ws('-', tyyppi, koko, olosuhde) as tyyppi, toimenpide, huoltovali, pakollisuus";

$hakukentat = "tyyppi, toimenpide, huoltovali, pakollisuus";

$jarjestys = "tyyppi, toimenpide, huoltovali, pakollisuus";
?>