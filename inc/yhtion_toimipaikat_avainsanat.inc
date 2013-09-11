<?php

	$otsikko = 'Yhtiön toimipaikan avainsanat';
	$otsikko_nappi 	= 'yhtiön toimipaikan avainsana';

	if ($from == "") {
		$kentat = 'tunnus, laji, avainsana, tarkenne';
	}
	else {
		$kentat = "tunnus, laji, if(laji='toimittaja' and avainsana != '', (select nimi from toimi where yhtio='{$kukarow['yhtio']}' and tunnus = yhtion_toimipaikat_avainsanat.avainsana), avainsana) avainsana, tarkenne";
	}

	$jarjestys = 'laji, avainsana, tarkenne';

	if (!isset($yhtion_toimipaikat_avainsanat_laji)) $yhtion_toimipaikat_avainsanat_laji = '';
