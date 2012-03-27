<?php

	$otsikko = 'Pakkauskoodit';
	$otsikko_nappi 	= 'pakkauskoodi';

	$kentat = "tunnus, pakkaus, (SELECT nimi FROM rahdinkuljettajat WHERE yhtio = '{$kukarow['yhtio']}' AND koodi = pakkauskoodit.rahdinkuljettaja) AS rahdinkuljettaja, koodi";
	$jarjestys = 'pakkaus, rahdinkuljettaja, koodi';