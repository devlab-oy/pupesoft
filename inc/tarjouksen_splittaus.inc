<?php

require_once('tilauskasittely/luo_myyntitilausotsikko.inc');
$query = "  SELECT laskun_lisatiedot.sopimus_lisatietoja,
			tilausrivi.*
            FROM laskun_lisatiedot
			JOIN tilausrivi
			ON ( tilausrivi.yhtio = laskun_lisatiedot.yhtio
				AND tilausrivi.otunnus = laskun_lisatiedot.otunnus )
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.otunnus = '{$tilausnumero}'";
$result = pupe_query($query);

$tilausrivit = array();

while($tilausrivi = mysql_fetch_assoc($result)) {
	$tilausrivit[] = $tilausrivi;
	$tarjouksen_asiakkaat = $tilausrivi['sopimus_lisatietoja'];
}

$tarjouksen_asiakkaat = explode(',', $tarjouksen_asiakkaat);

$dummy_tarjouksen_tilausnumero = $tilausnumero;

foreach ($tarjouksen_asiakkaat as $tarjous_asiakas) {
    $kukarow['kesken'] = 0;
    $tilausnumero = luo_myyntitilausotsikko('TARJOUS', $tarjous_asiakas);

	foreach ($tilausrivit as $tilausrivi) {
		$tilausrivi['otunnus'] = $tilausnumero;
		unset($tilausrivi['tunnus']);
		unset($tilausrivi['laadittu']);
		unset($tilausrivi['sopimus_lisatietoja']);
		$copy_query = "	INSERT INTO
						tilausrivi (".implode(", ", array_keys($tilausrivi)).", laadittu)
						VALUES('".implode("', '", array_values($tilausrivi)). "', now())";
		pupe_query($copy_query);
	}
    
    $query = "  SELECT *
                FROM lasku
                JOIN laskun_lisatiedot
                ON ( laskun_lisatiedot.yhtio = lasku.yhtio
                    AND laskun_lisatiedot.otunnus = lasku.tunnus )
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tunnus = '{$tilausnumero}'";
    $result = pupe_query($query);
    
    $laskurow = mysql_fetch_assoc($result);
    
    $kukarow['kesken'] = $tilausnumero;
    
    require_once('tilaus-valmis.inc');
}

$query = "  DELETE FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$dummy_tarjouksen_tilausnumero}'";
pupe_query($query);