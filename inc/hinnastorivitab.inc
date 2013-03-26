<?php

function hinnastoriviotsikot() {
	global $saatavuus;

	$out  = t('tuoteno')."\t";			// tuotenumero
	$out .= t('nimitys')."\t";			// tuotteen nimitys
	$out .= t('eankoodi')."\t";			// tuotteen eankoodi alennusryhmä kahteen merkkiin asti zeropaddattuna
	$out .= t('yksikko')."\t";			// tuotteen yksikko
	$out .= t('aleryhma')."\t"; 		// tuotteen aleryhmä
	$out .= t('hinta')."\t";	   		// tuotteen luettelohint
	$out .= t('tuotemerkki')."\t";		// tuotemerkki
	$out .= t('osasto')."\t";	   		// tuotteen osasto
	$out .= t('try')."\t";				// tuoteryhmä
	$out .= t('korvaava tuote')."\t";	// korvaava tuote
	if($saatavuus == 'kylla') {
		$out .= t('onko saatavilla')."\t";//saatavuus
	}
	$out .= "\n";

	return $out;
}

function hinnastorivi($tuote, $laskurow) {
    global $kukarow, $yhtiorow, $saatavuus;


	//korvaavat tuotteet
	$koti 		= '';
	$edellinen 	= '';
	$seuraava 	= '';
	$korvaavat	= '';

	if ($tuote["id"] > 0) {
		$query = "SELECT * from korvaavat where id='$tuote[id]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
		$korvaresult = mysql_query($query) or pupe_error($query);

		$lask = 0;
		while ($korvarow = mysql_fetch_array($korvaresult)){
			$korvaavat[$lask] = $korvarow["tuoteno"];
			if ($korvarow["tuoteno"] == $tuote["tuoteno"]) {
				$koti = $lask;
			}
			$lask++;
		}
		//tässä listan viimeinen indeksi
		$lask--;

		//edellinen ja seuraava korvaava
		if ($koti == 0) {
			$edellinen	= '';
			$seuraava	= $korvaavat[$koti+1];
		}
		elseif($koti == $lask) {
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= '';
		}
		else{
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= $korvaavat[$koti+1];
		}
	}

	$myyntihinta = tuotteen_myyntihinta($laskurow, $tuote, 1, "ei");

    // tehdään tietuetta
	$out = $tuote['tuoteno']."\t";		// tuotenumero
	$out .= t_tuotteen_avainsanat($tuote, 'nimitys')."\t";		// tuotteen nimitys
	$out .= $tuote['eankoodi']."\t";	// tuotteen eankoodi alennusryhmä kahteen merkkiin asti zeropaddattuna
	$out .= t_avainsana("Y", "", "and avainsana.selite='$tuote[yksikko]'", "", "", "selite")."\t";		// tuotteen yksikko
	$out .= $tuote['aleryhma']."\t"; 	// tuotteen aleryhmä
	$out .= $myyntihinta."\t";	   		// tuotteen luettelohint
	$out .= $tuote['tuotemerkki']."\t";	// tuotemerkki
	$out .= $tuote['osasto']."\t";	   	// tuotteen osasto
	$out .= $tuote['try']."\t";			// tuoteryhmä
	$out .= $seuraava;					// korvaava tuote
	if($saatavuus == 'kylla') {
		$out .= "\t" . hae_saatavuus($laskurow, $tuote['tuoteno']);			// saatavuus
	}
	$out .= "\n";

    return $out;
}

function hae_saatavuus($laskurow, $tuoteno) {
	global $kukarow, $yhtiorow;

	list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, "", 0, "", "", "", "", "", $laskurow["toim_maa"], '');

	if ($myytavissa > 0) {
		return t("Kyllä");
	}
	else {
		//HUOM! palauttaa vain yhden rivin
		$tulossa_query = " 	SELECT min(toimaika) paivamaara
							FROM tilausrivi
							WHERE yhtio = '{$kukarow[yhtio]}'
							AND tuoteno = '{$tuoteno}'
							AND varattu > 0
							AND tyyppi = 'O'
							AND toimaika >= curdate()";
		$tulossa_result = pupe_query($tulossa_query);
		$tulossa_row = mysql_fetch_assoc($tulossa_result);

		if (empty($tulossa_row['paivamaara'])) {
			return t("Ei");
		}
		else {
			return t("Tulossa") . ' ' . $tulossa_row['paivamaara'];
		}
	}
}
?>