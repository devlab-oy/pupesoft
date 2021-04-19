<?php
	$otsikko = 'Hinnastot';
	$otsikko_nappi = 'hinnastohinta';

	$kentat = 'tunnus, tuoteno, hinta, laji, valkoodi, maa, alkupvm, loppupvm';
	$jarjestys = 'tuoteno, hinta, laji, valkoodi, alkupvm, loppupvm';
	
	if ($nayta_eraantyneet != "") {
		$rajauslisa = " and hinnasto.loppupvm > '0000-00-00' and hinnasto.loppupvm < now() ";
	}
?>