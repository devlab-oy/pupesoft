<?php

function jaalasku($jaettavalaskures, $vahennettavasumma_valuutassa) {
	global kukarow;

	$fields='';
	$values='';

	if(mysql_num_rows($jaettavalaskures) == 1) {

		$jaettavalaskurow = mysql_fetch_array($monistares);
		$vahennettavasumma = round($vahennettavasumma_valuutassa*$jaettavalaskurow['vienti_kurssi'],2);

		// Tehdään uusi lasku
		for($i=1; $i < mysql_num_fields($jaettavalaskures)-1; $i++) {

			$fields .= ", ".mysql_field_name($jaettavalaskures,$i);

			switch (mysql_field_name($jaettavalaskures,$i)) {
				case 'summa_valuutassa':
					$values .= ", '".$vahennettavasumma_valuutassa."'";
					break;
				case 'summa':
					$values .= ", '".$vahennettavasumma."'";
					break;
				case 'kasumma':
				case 'kasumma_valuutassa':
					$values .= ", '0.00'";
					break;
				case 'vanhatunnus' :
					$values .= ", '".$jaettavalaskurow['tunnus']."'";
					break;
				default:
					$values .= ", '".$jaettavalaskurow[$i]."'";
			}
		}

		$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
		$insres  = mysql_query($kysely) or pupe_error($kysely);
		$utunnus = mysql_insert_id();

		//Päivitetään vanha lasku

		$kysely  = "UPDATE lasku SET summa = $jaettavalaskurow['summa'] - $vahennettavasumma,
				summa_valuutassa = $jaettavalaskurow['summa_valuutassa'] - $vahennettavasumma,
				kasumma = 0.00,
				kasumaa_valuutassa = 0.00";
		$insres  = mysql_query($kysely) or pupe_error($kysely);

		//Lisätään tiliöinnit
		//Vähennetään alkuperäisen laskun ostovelkaa ja siirretään se selvittelytilille
		$selite = t('Ostolaskun osasuoritus');
		$query = "INSERT into tiliointi set
				yhtio ='$kukarow[yhtio]',
				ltunnus = '$jaettavalaskurow[tunnus]',
				tilino = '$yhtiorow[ostovelat]',
				tapvm = '$jaettavalaskurow[tapvm]',
				summa = '$vahennettavasumma',
				summa_valuutassa = '$vahennettavasumma_valuutassa',
				valkoodi = '$jaettavalaskurow[valkoodi]',
				selite = '$selite',
				lukko = '1',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
		$query = "INSERT into tiliointi set
				yhtio ='$kukarow[yhtio]',
				ltunnus = '$jaettavalaskurow[tunnus]',
				tilino = '$yhtiorow[selvittely]',
				tapvm = '$jaettavalaskurow[tapvm]',
				summa = $vahennettavasumma * -1,
				summa_valuutassa = $vahennettavasumma_valuutassa * -1,
				valkoodi = '$jaettavalaskurow[valkoodi]',
				selite = '$selite',
				lukko = '1',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
		// Uuteen laskuun samat summat mutta toisin etumerkein
				$query = "INSERT into tiliointi set
				yhtio ='$kukarow[yhtio]',
				ltunnus = '$utunnus',
				tilino = '$yhtiorow[selvittely]',
				tapvm = '$jaettavalaskurow[tapvm]',
				summa = '$vahennettavasumma',
				summa_valuutassa = '$vahennettavasumma_valuutassa',
				valkoodi = '$jaettavalaskurow[valkoodi]',
				selite = '$selite',
				lukko = '1',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
		$query = "INSERT into tiliointi set
				yhtio ='$kukarow[yhtio]',
				ltunnus = '$utunnus',
				tilino = '$yhtiorow[ostovelat]',
				tapvm = '$jaettavalaskurow[tapvm]',
				summa = $vahennettavasumma * -1,
				summa_valuutassa = $vahennettavasumma_valuutassa * -1,
				valkoodi = '$jaettavalaskurow[valkoodi]',
				selite = '$selite',
				lukko = '1',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
		// Tässä pitäisi hoitaa mahdolliset keikkajutut
		//
		//
		
		return ($utunnus);
	}
	else {
		return ('Jaettava lasku ei löydy');
	}
}
