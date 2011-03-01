<?php
	$otsikko = 'Laskut';
	$otsikko_nappi = 'lasku';
	
	$uusilukko = "LUKOSSA";
	
	if ($laji == "T") {	
		$kentat = "tunnus, tunnus 'tilaus', laskunro, tapvm, ytunnus, nimi, summa, valkoodi";
		$hakukentat = "tunnus, tunnus, laskunro, tapvm, ytunnus, nimi, summa, valkoodi";
		
		$jarjestys = 'laskunro DESC';
		
		$rajauslisa = " and lasku.tila = 'L' and lasku.alatila='X' and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]' ";
	}
	else {		
		$kentat = 'tunnus, laskunro, tapvm, ytunnus, nimi, summa, valkoodi';
		$jarjestys = 'laskunro DESC';
		
		$rajauslisa = " and lasku.tila = 'U' and lasku.alatila='X' and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]' ";
	}

?>