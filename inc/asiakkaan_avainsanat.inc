<?php

	$otsikko = 'Asiakkaan avainsanat';
	$otsikko_nappi = 'asiakkaan avainsana';

	$kentat = "asiakkaan_avainsanat.tunnus, (SELECT selitetark from avainsana where asiakkaan_avainsanat.yhtio = avainsana.yhtio and asiakkaan_avainsanat.laji = avainsana.selite and avainsana.laji = 'asavainsana') laji, asiakkaan_avainsanat.avainsana, asiakkaan_avainsanat.liitostunnus HIDDEN_liitostunnus";
	$hakukentat = "asiakkaan_avainsanat.tunnus, asiakkaan_avainsanat.laji, asiakkaan_avainsanat.avainsana, asiakkaan_avainsanat.liitostunnus";

	$jarjestys = 'asiakkaan_avainsanat.laji, asiakkaan_avainsanat.avainsana';
?>