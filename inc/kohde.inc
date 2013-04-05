<?php

//jos syötetään asiakkaalle uusia kohteita asiakkaan_laite_hallinta.php:stä halutaan redirecti sinne takaisin
if (isset($redirect_to)) {
	echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus='.$valittu_asiakas.'"/>';
}

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

		$query = "	SELECT group_concat(tunnus)
					FROM paikka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kohde = '{$tunnus}'";
		$result = pupe_query($query);
		$paikka_tunnukset = mysql_fetch_assoc($result);

		$query = "	UPDATE laite
					SET paikka = 0
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND paikka IN ({$paikka_tunnukset})";
		pupe_query($query);
		
		$query = "	DELETE
					FROM paikka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kohde = '{$tunnus}'";
		pupe_query($query);
	}
}
?>