<?php
	
	$otsikko 	= 'Asiakkaat';
	$kentat 	= "tunnus, ytunnus, concat_ws('<br>', nimi, toim_nimi) 'Nimi/Toim_nimi', concat_ws('<br>', postitp, toim_postitp) 'Postitp/Toim_postitp', concat_ws('<br>', ovttunnus, toim_ovttunnus) 'Ovttunnus/Toim_ovttunnus' ";
	$hakukentat = "tunnus, ytunnus, nimi/toim_nimi, postitp/toim_postitp, ovttunnus/toim_ovttunnus";
	$jarjestys 	= 'selaus, nimi';
	
	
	if ($laji == "H") {
		$rajauslisa = " and asiakas.laji = 'H' ";
	}
	elseif ($laji == "Y") {
		$rajauslisa = " and asiakas.laji = '' ";
	}
	elseif ($laji == "P") {
		$rajauslisa = " and asiakas.laji = 'P' ";
	}
	else {
		$rajauslisa	= "";
	}
	
	
	
?>