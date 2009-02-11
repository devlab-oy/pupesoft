<?php
	$otsikko = 'Pakkaamo';
	$kentat = "tunnus, nimi, lokero, (select nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = pakkaamo.varasto) varasto, prio";
	$jarjestys = 'nimi, lokero, varasto, prio';
?>