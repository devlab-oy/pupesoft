<?php

	$otsikko = 'Varaston hyllypaikat';
	$otsikko_nappi 	= 'varaston hyllypaikka';

	$kentat = "tunnus, (SELECT nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = varaston_hyllypaikat.keraysvyohyke) AS keraysvyohyke, reservipaikka, hyllyalue, hyllynro, hyllyvali, hyllytaso, leveys, korkeus, syvyys, maksimitaakka";
	$jarjestys = 'keraysvyohyke, reservipaikka, hyllyalue, hyllynro, hyllyvali, hyllytaso, leveys, korkeus, syvyys, maksimitaakka';
