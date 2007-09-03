<?php

	$kentat = 'tunnus, tuoteno, nimitys, myyntihinta';
	$jarjestys = 'tuoteno';
	
	if ($laji == "V") {
		$otsikko = ' Viranomaistuotteet';
		$rajauslisa = " and tuote.tuotetyyppi IN ('A','B') ";
	}
	else {
		$otsikko = 'Tuotteet';
		$rajauslisa = " and tuote.tuotetyyppi NOT IN ('A','B') ";
	}
	
?>
