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

	if ($yhtiorow['vauriopoytakirja'] == 'K') {
		if (!empty($kukarow['try'])) {
			$try_result = t_avainsana('try', '', " AND selite IN ({$kukarow['try']}) AND selitetark_3 = ''");

			$tuoteryhmat = array();
			while($tuoteryhma = mysql_fetch_assoc($try_result)) {
				if (in_array($tuoteryhma['selite'], explode(',', $kukarow['try']))) {
					$tuoteryhmat[] = $tuoteryhma['selite'];
				}
			}
		}

		//admin näkee kaikki tuotteet
		if (!empty($tuoteryhmat) and !stristr($kukarow['profiilit'], 'admin')) {
			$rajauslisa = "	AND tuote.try IN (".implode(',', $tuoteryhmat).")";
		}
	}

	// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa (tsekataan tuoterivi.inc)
	$poistolukko = "ON";
