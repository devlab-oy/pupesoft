<?php

	$otsikko = 'Maksuehdot';
	$kentat = 'tunnus, teksti, rel_pvm, abs_pvm, kassa_relpvm, kassa_abspvm, kassa_alepros, jarjestys, tunnus, kaytossa HIDDEN_kaytossa';
	$jarjestys = 'jarjestys, teksti';
	
	if ($nayta_poistetut == "") {
		$rajauslisa = " and maksuehto.kaytossa != 'E' ";
	}
	else {
		$rajauslisa	= "";
	}
	
?>