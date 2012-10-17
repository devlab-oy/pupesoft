<?php
	$otsikko = 'Pakkaamo';
	$kentat = "tunnus, nimi, lokero, (select nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = pakkaamo.varasto AND varasto_status != 'P') varasto, prio, pakkaamon_prio";
	$jarjestys = 'nimi, lokero, varasto, prio';
?>