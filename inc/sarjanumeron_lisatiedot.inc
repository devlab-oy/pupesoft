<?php
	$otsikko = 'Sarjanumeron lisätiedot';
	$otsikko_nappi = 'sarjanumeron lisätieto';

	$kentat = 'tunnus, Merkki, Malli';
	$jarjestys = 'Merkki, Malli';


	if ($specialtee == "tiedottuotteelta") {

		if ($liitostunnus > 0) {
			$hakliitos = $liitostunnus;
		}
		else {
			$query = "	SELECT liitostunnus
						FROM sarjanumeron_lisatiedot
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$tunnus'";
			$tuoteres = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($tuoteres);

			$hakliitos = $sarjarow["liitostunnus"];
		}

		$query = "	SELECT tuoteno
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$hakliitos'";
		$tuoteres = mysql_query($query) or pupe_error($query);
		$sarjarow = mysql_fetch_array($tuoteres);

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$sarjarow[tuoteno]'";
		$tuoteres = mysql_query($query) or pupe_error($query);
		$tuoterow = mysql_fetch_array($tuoteres);

		$vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'MERKKI' and selitetark_2 = '$tuoterow[tuotemerkki]'");
		$vrow = mysql_fetch_array($vresult);

		if ($liitostunnus > 0) {
			$query = "	INSERT INTO sarjanumeron_lisatiedot
						SET yhtio			= '$kukarow[yhtio]',
						liitostunnus		= '$liitostunnus',
						laatija 			= '$kukarow[kuka]',
						luontiaika 			= now(),
						Leveys				= '$tuoterow[tuoteleveys]',
						Pituus				= '$tuoterow[tuotepituus]',
						Varirunko			= '$tuoterow[vari]',
						Suurin_henkiloluku	= '$tuoterow[suurin_henkiloluku]',
						Runkotyyppi			= '$tuoterow[runkotyyppi]',
						Materiaali			= '$tuoterow[materiaali]',
						Koneistus			= '$tuoterow[koneistus]',
						Tyyppi				= '$tuoterow[laitetyyppi]',
						Kilpi				= '$tuoterow[kilpi]',
						Sprinkleri 			= '$tuoterow[sprinkleri]',
						Teho_kw				= '$tuoterow[teho_kw]',
						Malli				= '$tuoterow[nimitys]',
						Merkki				= '$vrow[selitetark]'";
			$lisatietores_apu = mysql_query($query) or pupe_error($query);

			$tunnus 		= mysql_insert_id();
			$uusi		 	= "";
			$liitostunnus 	= "";
		}
		else {
			$query = "	UPDATE sarjanumeron_lisatiedot
						SET yhtio			= '$kukarow[yhtio]',
						muuttaja 			= '$kukarow[kuka]',
						muutospvm 			= now(),
						Leveys				= '$tuoterow[tuoteleveys]',
						Pituus				= '$tuoterow[tuotepituus]',
						Varirunko			= '$tuoterow[vari]',
						Suurin_henkiloluku	= '$tuoterow[suurin_henkiloluku]',
						Runkotyyppi			= '$tuoterow[runkotyyppi]',
						Materiaali			= '$tuoterow[materiaali]',
						Koneistus			= '$tuoterow[koneistus]',
						Tyyppi				= '$tuoterow[laitetyyppi]',
						Kilpi				= '$tuoterow[kilpi]',
						Sprinkleri 			= '$tuoterow[sprinkleri]',
						Teho_kw				= '$tuoterow[teho_kw]',
						Malli				= '$tuoterow[nimitys]',
						Merkki				= '$vrow[selitetark]'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$tunnus'";
			$lisatietores_apu = mysql_query($query) or pupe_error($query);
		}
	}

?>