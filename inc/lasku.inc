<?php
	$otsikko = 'Laskut';
	$otsikko_nappi = 'lasku';

	$kentat = 'tunnus, laskunro, tapvm, ytunnus, nimi, summa, valkoodi';
	$jarjestys = 'laskunro DESC';

	$uusilukko = "LUKOSSA";

	$rajauslisa = " and lasku.tila = 'U' and lasku.alatila='X' and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]' ";
?>