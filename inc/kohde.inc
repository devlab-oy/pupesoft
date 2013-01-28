<?php

	$otsikko = 'Kohteet';
	$otsikko_nappi 	= 'Kohde';

    if ($from == ""){
        $kentat = "tunnus, nimi, (SELECT nimi from asiakas where kohde.yhtio = asiakas.yhtio AND kohde.asiakas = asiakas.tunnus) asiakas, osoite, paikkakunta";
    }
    else {
        $kentat = "tunnus, nimi, osoite, paikkakunta";
    }
    
    $hakukentat = "tunnus, nimi, asiakas, osoite, paikkakunta";
    
	$jarjestys = 'asiakas, nimi';

?>