<?php

	if ($alias_set == "KAYTTAJA") {
		$otsikko = 'Matkalaskukäyttäjät';
		$otsikko_nappi = "matkalaskukäyttäjä";

		$laji = "K";

		$kentat 	= "tunnus, concat_ws('<br>', concat(SUBSTRING(ytunnus,1,7),'XXXX'), tunnus) 'Henkilönumero/Tunnus',toimittajanro, concat_ws('<br>', nimi, nimitark) 'Nimi', concat_ws('<br>', postitp, maa) 'Postitp/Maa', concat_ws('<br>', tilinumero, ultilno) 'Pankkitili', oletus_valkoodi 'Valuutta', tyyppi HIDDEN_tyyppi";
		$hakukentat = "tunnus, ytunnus,toimittajanro, nimi/nimitark, postitp/maa, tilino/ultilno, oletus_valkoodi";

		$rajauslisa = " and toimi.tyyppi IN ('K') ";
	}
	else {
		$otsikko 	= "Toimittajat";
		$otsikko_nappi = "toimittaja";

		$kentat 	= "tunnus, concat_ws('<br>', if(SUBSTRING(ytunnus,7,1)='-',concat(SUBSTRING(ytunnus,1,7),'XXXX'),ytunnus), tunnus) 'Ytunnus/Tunnus',toimittajanro, concat_ws('<br>', nimi, nimitark) 'Nimi', concat_ws('<br>', postitp, maa) 'Postitp/Maa', concat_ws('<br>', tilinumero, ultilno) 'Pankkitili', oletus_valkoodi 'Valuutta', tyyppi HIDDEN_tyyppi";
		$hakukentat = "tunnus, ytunnus,toimittajanro, nimi/nimitark, postitp/maa, tilino/ultilno, oletus_valkoodi";

		$rajauslisa = " and toimi.tyyppi NOT IN ('K') ";
	}

	if ($nayta_poistetut == "") {
		$rajauslisa .= " and toimi.tyyppi != 'P' ";
	}
	else {
		$rajauslisa	.= "";
	}

	$jarjestys = 'selaus';

?>