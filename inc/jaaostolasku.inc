<?php

function jaalasku($jaettavalasku, $vahennettavasumma_valuutassa) {

	global $yhtiorow, $kukarow;

	$fields	= "";
	$values	= "";
	$tunnus	= (int) $jaettavalasku;
	$vahennettavasumma_valuutassa = (float) $vahennettavasumma_valuutassa;

	// Katsotaan, että lasku on hyväksyttävänä tai valmiina maksatukseen
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$jaettavalasku'
				AND tila in ('H', 'M')";
	$jaettavalaskures = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($jaettavalaskures) == 1) {

		$jaettavalaskurow = mysql_fetch_array($jaettavalaskures);
		$vahennettavasumma = round($vahennettavasumma_valuutassa * $jaettavalaskurow['vienti_kurssi'], 2);

		// Tehdään uusi lasku
		for ($i=1; $i < mysql_num_fields($jaettavalaskures)-1; $i++) {

			$fields .= mysql_field_name($jaettavalaskures, $i).",";

			switch (mysql_field_name($jaettavalaskures, $i)) {
				case 'summa_valuutassa':
					$values .= "'$vahennettavasumma_valuutassa',";
					break;
				case 'summa':
					$values .= "'$vahennettavasumma',";
					break;
				case 'arvo':
					$values .= "'$jaettavalaskurow[summa]',";
					break;
				case 'arvo_valuutassa':
					$values .= "'$jaettavalaskurow[summa_valuutassa]',";
					break;
				case 'kasumma':
				case 'kasumma_valuutassa':
					$values .= "'0.00',";
					break;
				case 'vanhatunnus' :
					$values .= "'$jaettavalaskurow[tunnus]',";
					break;
				default:
					$values .= "'$jaettavalaskurow[$i]',";
			}
		}

		// Vikat pilkut pois
		$fields = substr($fields, 0, -1);
		$values = substr($values, 0, -1);

		// Lisätään uusi lasku
		$query = "INSERT into lasku ($fields) VALUES ($values)";
		$result = mysql_query($query) or pupe_error($query);
		$utunnus = mysql_insert_id();

		// Päivitetään vanha lasku
		$query  = "		UPDATE lasku SET
						summa = $jaettavalaskurow[summa] - $vahennettavasumma,
						summa_valuutassa = $jaettavalaskurow[summa_valuutassa] - $vahennettavasumma_valuutassa,
						arvo = $jaettavalaskurow[summa],
						arvo_valuutassa = $jaettavalaskurow[summa_valuutassa],
						kasumma = 0.00,
						kasumaa_valuutassa = 0.00";
		$result = mysql_query($query) or pupe_error($query);

		// Lisätään tiliöinnit
		// Vähennetään alkuperäisen laskun ostovelkaa ja siirretään se selvittelytilille
		$selite = t('Ostolaskun osasuoritus');

		$query = "	INSERT INTO tiliointi SET
					yhtio 				= '$kukarow[yhtio]',
					ltunnus				= '$jaettavalaskurow[tunnus]',
					tilino				= '$yhtiorow[ostovelat]',
					tapvm				= '$jaettavalaskurow[tapvm]',
					summa				= '$vahennettavasumma',
					summa_valuutassa	= '$vahennettavasumma_valuutassa',
					valkoodi			= '$jaettavalaskurow[valkoodi]',
					selite				= '$selite',
					lukko				= '1',
					laatija				= '$kukarow[kuka]',
					laadittu			= now()";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	INSERT INTO tiliointi SET
					yhtio				= '$kukarow[yhtio]',
					ltunnus				= '$jaettavalaskurow[tunnus]',
					tilino				= '$yhtiorow[selvittely]',
					tapvm				= '$jaettavalaskurow[tapvm]',
					summa				= $vahennettavasumma * -1,
					summa_valuutassa	= $vahennettavasumma_valuutassa * -1,
					valkoodi			= '$jaettavalaskurow[valkoodi]',
					selite				= '$selite',
					lukko				= '1',
					laatija				= '$kukarow[kuka]',
					laadittu			= now()";
		$result = mysql_query($query) or pupe_error($query);

		// Uuteen laskuun samat summat mutta toisin etumerkein
		$query = "	INSERT INTO tiliointi SET
					yhtio				='$kukarow[yhtio]',
					ltunnus				= '$utunnus',
					tilino				= '$yhtiorow[selvittely]',
					tapvm				= '$jaettavalaskurow[tapvm]',
					summa				= '$vahennettavasumma',
					summa_valuutassa	= '$vahennettavasumma_valuutassa',
					valkoodi			= '$jaettavalaskurow[valkoodi]',
					selite				= '$selite',
					lukko				= '1',
					laatija				= '$kukarow[kuka]',
					laadittu			= now()";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	INSERT INTO tiliointi SET
					yhtio				= '$kukarow[yhtio]',
					ltunnus 			= '$utunnus',
					tilino				= '$yhtiorow[ostovelat]',
					tapvm				= '$jaettavalaskurow[tapvm]',
					summa				= $vahennettavasumma * -1,
					summa_valuutassa	= $vahennettavasumma_valuutassa * -1,
					valkoodi			= '$jaettavalaskurow[valkoodi]',
					selite				= '$selite',
					lukko				= '1',
					laatija				= '$kukarow[kuka]',
					laadittu			= now()";
		$result = mysql_query($query) or pupe_error($query);

		// Tässä hoidetaan keikkojen liitokset

		// Tässä hoidetaan laskujen liitetiedostot

		return ($utunnus);
	}
	else {
		return ('Jaettava lasku ei löydy');
	}

}