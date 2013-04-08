<?php

$otsikko = 'Laitteet';
$otsikko_nappi = 'Laite';

$kentat = "tunnus, tuoteno, sarjanro, (SELECT nimi from paikka where paikka.yhtio = laite.yhtio AND paikka.tunnus = laite.paikka) as paikka, sijainti";

$hakukentat = "tunnus, tuoteno, sarjanro";

$jarjestys = "tunnus, tuoteno, sarjanro";

if (!function_exists('echo_laite_redirect')) {

	function echo_laite_redirect($params) {
		global $palvelin2;
		//jos syצtetההn asiakkaalle uusia laitteita asiakkaan_laite_hallinta.php:stה halutaan redirecti sinne takaisin
		if (isset($params['redirect_to']) and $params['errori'] == 0) {
			echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$params['valittu_asiakas'].'"/>';
		}
	}
}
?>