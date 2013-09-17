<?php

	if (isset($lukitse_laji) and $lukitse_laji != "") {
		$otsikko 		= 'Avainsanan kieliversiot';
		$otsikko_nappi 	= 'avainsanan kieliversio';
	}
	else {
		$otsikko 		= 'Avainsanat';
		$otsikko_nappi 	= 'avainsana';
	}

	// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
	$kentat 	= 'tunnus, kieli, laji, selite, selitetark, selitetark_2, selitetark_3, jarjestys, perhe HIDDEN_perhe';
	$hakukentat = 'tunnus, kieli, laji, selite, selitetark, selitetark_2, selitetark_3, jarjestys, perhe';

	$jarjestys = 'kieli, laji, jarjestys, selite, selitetark, selitetark_2';
