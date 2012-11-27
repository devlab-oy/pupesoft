<?php

	$kentat = "tunnus, tuoteno, nimitys, round(myyntihinta,$yhtiorow[hintapyoristys]) myyntihinta, status HIDDEN_status";
	$hakukentat = "tunnus, tuoteno, nimitys,myyntihinta";

	$jarjestys = 'tuoteno';

	if ($laji == "V") {
		$otsikko = 'Viranomaistuotteet';
		$rajauslisa = " and tuote.tuotetyyppi IN ('A','B') ";
	}
	elseif ($laji == "K") {
		$otsikko = 'Palvelutuotteet';
		$rajauslisa = " and tuote.tuotetyyppi = 'K' ";
    }
    else {
		$otsikko = 'Tuotteet';
		$rajauslisa = " and tuote.tuotetyyppi NOT IN ('A','B') ";
	}

	if ($nayta_poistetut == "") {
		$rajauslisa .= " and tuote.status != 'P' ";
	}
	else {
		$rajauslisa	.= "";
	}


	// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa (tsekataan tuoterivi.inc)
	$poistolukko = "ON";

?>