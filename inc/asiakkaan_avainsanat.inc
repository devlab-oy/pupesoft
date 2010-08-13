<?php

	$otsikko 		= 'Asiakkaan avainsanat';
	$otsikko_nappi 	= 'asiakkaan avainsana';

	// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
	$kentat = "asiakkaan_avainsanat.tunnus, (SELECT selitetark from avainsana where asiakkaan_avainsanat.yhtio = avainsana.yhtio and asiakkaan_avainsanat.laji = avainsana.selite and avainsana.laji = 'asavainsana' LIMIT 1) laji, asiakkaan_avainsanat.kieli, asiakkaan_avainsanat.avainsana, asiakkaan_avainsanat.liitostunnus HIDDEN_liitostunnus";
	$hakukentat = "asiakkaan_avainsanat.tunnus, asiakkaan_avainsanat.laji, asiakkaan_avainsanat.kieli, asiakkaan_avainsanat.avainsana, asiakkaan_avainsanat.liitostunnus";

	$jarjestys = 'asiakkaan_avainsanat.laji, asiakkaan_avainsanat.avainsana';
?>