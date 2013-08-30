<?php

	$otsikko = 'Yhtiön toimipaikan avainsanat';
	$otsikko_nappi 	= 'yhtiön toimipaikan avainsana';

	if ($from == "") {
		$kentat = 'tunnus, laji, avainsana, tarkenne, tarkenne_2';
	}
	else {
		$kentat = "tunnus, laji, if(laji='toimittaja' and avainsana != '', (select nimi from toimi where yhtio='{$kukarow['yhtio']}' and tunnus = yhtion_toimipaikat_avainsanat.avainsana), avainsana) avainsana, tarkenne, tarkenne_2";
	}

	$jarjestys = 'laji, avainsana, tarkenne, tarkenne_2';

	if (!isset($yhtion_toimipaikat_avainsanat_laji)) $yhtion_toimipaikat_avainsanat_laji = '';
