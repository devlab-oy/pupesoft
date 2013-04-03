<?php

//jos syצtetההn asiakkaalle uusia laitteita asiakkaan_laite_hallinta.php:stה halutaan redirecti sinne takaisin
if(isset($redirect_to)) {
	echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$valittu_asiakas.'"/>';
}

$otsikko = 'Laitteet';
$otsikko_nappi = 'Laite';

$kentat = "tunnus, tuoteno, sarjanro, (SELECT nimi from paikka where paikka.yhtio = laite.yhtio AND paikka.tunnus = laite.paikka) as paikka, sijainti";

$hakukentat = "tunnus, tuoteno, sarjanro";

$jarjestys = "tunnus, tuoteno, sarjanro";
?>