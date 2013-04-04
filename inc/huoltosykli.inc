<?php

$otsikko = 'Huoltosyklit';
$otsikko_nappi = 'Huoltosykli';

$kentat = "tunnus, concat_ws(' - ', tyyppi, koko, (
		SELECT selitetark
		FROM avainsana
		WHERE avainsana.yhtio = huoltosykli.yhtio
		AND avainsana.selite = huoltosykli.olosuhde
		AND avainsana.laji = 'OLOSUHDE')
	) as tyyppi,
	(
		SELECT nimitys
		FROM tuote
		WHERE tuote.yhtio = huoltosykli.yhtio
		AND tuote.tuoteno = huoltosykli.toimenpide
	) as toimenpide,
	huoltovali,
	pakollisuus";

$hakukentat = "tunnus, tyyppi, toimenpide, huoltovali, pakollisuus";

$jarjestys = "tyyppi, toimenpide, huoltovali, pakollisuus";
?>