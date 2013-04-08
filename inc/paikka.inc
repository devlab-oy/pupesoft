<?php

$otsikko = 'Paikat';
$otsikko_nappi = 'Paikka';

$kentat = "tunnus, nimi, (SELECT nimi from kohde where kohde.yhtio = paikka.yhtio AND kohde.tunnus = paikka.kohde) as kohde";

$hakukentat = "tunnus, nimi, kohde";

$jarjestys = 'tunnus, nimi, kohde';

if (!function_exists('echo_laite_redirect')) {

	function echo_laite_redirect($params) {
		global $palvelin2;
		//jos syצtetההn asiakkaalle uusia paikkoja asiakkaan_laite_hallinta.php:stה halutaan redirecti sinne takaisin
		if (isset($params['redirect_to']) and $params['errori'] == 0) {
			echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$params['valittu_asiakas'].'"/>';
		}
	}
}
?>