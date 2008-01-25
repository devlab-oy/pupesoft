<?php

	$kentat 	= "tunnus, concat_ws('<br>', ytunnus, tunnus) 'Ytunnus/Tunnus', concat_ws('<br>', nimi, nimitark) 'Nimi', concat_ws('<br>', postitp, maa) 'Postitp/Maa', concat_ws('<br>', tilinumero, ultilno) 'Pankkitili', oletus_valkoodi 'Valuutta'";
	$hakukentat = "tunnus, ytunnus, nimi/nimitark, postitp/maa, tilino/ultilno, oletus_valkoodi";


	if ($alias_set == "KAYTTAJA") {
		$otsikko = ' Käyttäjätiedot';
		$rajauslisa = " and toimi.tyyppi IN ('K') ";
	}
	else {
		$otsikko 	= "Toimittajat";
		$rajauslisa = " and toimi.tyyppi NOT IN ('K') ";
	}
	
	
	$jarjestys = 'selaus';
?>
