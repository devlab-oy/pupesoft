<?php

	if ($alias_set == "KAYTTAJA") {
		$otsikko = 'Matkalaskukäyttäjät';
		$otsikko_nappi = "matkalaskukäyttäjä";

		$laji = "K";

		$kentat 	= "tunnus, concat_ws('<br>', concat(SUBSTRING(ytunnus,1,7),'XXXX'), tunnus) 'Henkilönumero/Tunnus',toimittajanro, ifnull((select nimi from kuka where kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi), toimi.nimi) 'Nimi', concat_ws('<br>', postitp, maa) 'Postitp/Maa', concat_ws('<br>', tilinumero, ultilno) 'Pankkitili', oletus_valkoodi 'Valuutta', tyyppi HIDDEN_tyyppi";
		$hakukentat = "tunnus, ytunnus,toimittajanro, nimi/nimitark, postitp/maa, tilino/ultilno, oletus_valkoodi";

		$rajauslisa = " and (toimi.tyyppi = 'K' ";
	}
	else {
		$otsikko 	= "Toimittajat";
		$otsikko_nappi = "toimittaja";

		$kentat 	= "tunnus, concat_ws('<br>', if(SUBSTRING(ytunnus,7,1)='-',concat(SUBSTRING(ytunnus,1,7),'XXXX'),ytunnus), tunnus) 'Ytunnus/Tunnus',ovttunnus,toimittajanro, concat_ws('<br>', nimi, nimitark) 'Nimi', concat_ws('<br>', postitp, maa) 'Postitp/Maa', concat_ws('<br>', tilinumero, ultilno) 'Pankkitili', oletus_valkoodi 'Valuutta', tyyppi HIDDEN_tyyppi";
		$hakukentat = "tunnus, ytunnus,ovttunnus,toimittajanro, nimi/nimitark, postitp/maa, tilino/ultilno, oletus_valkoodi";

		$rajauslisa = " and (toimi.tyyppi != 'K' ";
	}

	if ($nayta_poistetut == "") {
		$rajauslisa .= " and toimi.tyyppi != 'P' ";
	}
	elseif ($alias_set == "KAYTTAJA") {
		$rajauslisa	.= " or (toimi.tyyppi = 'P' and ytunnus REGEXP '[0-9]{6,6}\-[a-z0-9]{4,4}') ";
	}

	$rajauslisa .= ")";

	$jarjestys = 'selaus';

?>