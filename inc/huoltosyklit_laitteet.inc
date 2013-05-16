<?php

$kentat = "tunnus,
(
	SELECT concat_ws(' - ', laite.tuoteno, laite.sarjanro)
	FROM laite
	WHERE laite.yhtio = huoltosyklit_laitteet.yhtio
	AND laite.tunnus = huoltosyklit_laitteet.laite_tunnus
) AS laite,
(
	SELECT concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko, avainsana.selitetark)
	FROM huoltosykli
	JOIN tuote
	ON ( tuote.yhtio = huoltosykli.yhtio
		AND tuote.tuoteno = huoltosykli.toimenpide )
	JOIN avainsana
	ON ( avainsana.yhtio = huoltosykli.yhtio
           AND avainsana.selite = huoltosykli.olosuhde
           AND avainsana.laji = 'OLOSUHDE' )
	WHERE huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
	AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus
) AS huoltosykli,
huoltovali, pakollisuus";
$hakukentat = "tunnus, laite_tunnus, huoltosykli_tunnus, huoltovali, pakollisuus";

$jarjestys = 'laite_tunnus';
