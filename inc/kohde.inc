<?php

$otsikko = 'Kohteet';
$otsikko_nappi = 'Kohde';

if ($from == "") {
	$kentat = "tunnus, nimi, (SELECT nimi from asiakas where kohde.yhtio = asiakas.yhtio AND kohde.asiakas = asiakas.tunnus) asiakas, osoite, paikkakunta";
}
else {
	$kentat = "tunnus, nimi, osoite, paikkakunta";
}

$hakukentat = "tunnus, nimi, asiakas, osoite, paikkakunta";

$jarjestys = 'asiakas, nimi';

if (!function_exists('kohde_poista_relaatiot')) {

	function kohde_poista_relaatiot($toim, $tunnus) {
		//kun kohde poistetaan, poistetaan myös kohteen paikat
		//TODO poistetaanko paikkojen laitteet myös vai nollataanko laitteden relaatio?

		global $kukarow, $yhtiorow;

		$query = "	SELECT group_concat(tunnus) as paikka_tunnukset
					FROM paikka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kohde = '{$tunnus}'";
		$result = pupe_query($query);
		$paikka_tunnukset = mysql_fetch_assoc($result);

		if (!empty($paikka_tunnukset['paikka_tunnukset'])) {
			$query = "	UPDATE laite
						SET paikka = 0
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND paikka IN ({$paikka_tunnukset['paikka_tunnukset']})";
			pupe_query($query);

			$query = "	DELETE
						FROM paikka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kohde = '{$tunnus}'";
			pupe_query($query);
		}
	}
}

if (!function_exists('echo_laite_redirect')) {

	function echo_laite_redirect($params) {
		global $palvelin2;
		//jos syötetään asiakkaalle uusia kohteita asiakkaan_laite_hallinta.php:stä halutaan redirecti sinne takaisin
		if (isset($params['redirect_to']) and $params['errori'] == 0) {
			echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$params['valittu_asiakas'].'"/>';
		}
	}
}
?>