<?php

	$otsikko = 'Rahdinkuljettajat';
	$otsikko_nappi 	= 'rahdinkuljettaja';

	$kentat = "tunnus, koodi, nimi, IF(jalleenmyyjanro != 0, jalleenmyyjanro, '') AS jalleenmyyjanro, neutraali";
	$jarjestys = 'koodi, nimi, jalleenmyyjanro, neutraali';