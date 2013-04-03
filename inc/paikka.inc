<?php

//jos syצtetההn asiakkaalle uusia paikkoja asiakkaan_laite_hallinta.php:stה halutaan redirecti sinne takaisin
if(isset($redirect_to)) {
	echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$valittu_asiakas.'"/>';
}

$otsikko = 'Paikat';
$otsikko_nappi = 'Paikka';

//if ($from == "") {
//	$kentat = "tunnus, nimi, (SELECT nimi from asiakas where kohde.yhtio = asiakas.yhtio AND kohde.asiakas = asiakas.tunnus) asiakas, osoite, paikkakunta";
//}
//else {
//	$kentat = "tunnus, nimi, osoite, paikkakunta";
//}

$kentat = "tunnus, nimi, (SELECT nimi from kohde where kohde.yhtio = paikka.yhtio AND kohde.tunnus = paikka.kohde) as kohde";

$hakukentat = "tunnus, nimi, kohde";

$jarjestys = 'tunnus, nimi, kohde';
?>