<?php

$kentat = "tunnus, koodi, nimi";
$hakukentat = "tunnus,koodi, nimi";

$jarjestys = 'koodi';

if ($laji == "V") {
	$otsikko = 'Viranomaistuotteet';
	$rajauslisa = " and tuote.tuotetyyppi IN ('A','B') ";
} else {
	$otsikko = 'Kasvidata';
	$rajauslisa = "";
}

if ($nayta_poistetut == "") {
	$rajauslisa .= " and tuote.status != 'P' ";
} else {
	$rajauslisa .= "";
}

// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa (tsekataan tuoterivi.inc)
;

?>
