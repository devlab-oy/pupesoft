<?php

require_once('tilauskasittely/luo_myyntitilausotsikko.inc');
$query = "  SELECT laskun_lisatiedot.sopimus_lisatietoja,
            lasku.clearing,
			tilausrivi.*
            FROM laskun_lisatiedot
			JOIN tilausrivi
			ON ( tilausrivi.yhtio = laskun_lisatiedot.yhtio
				AND tilausrivi.otunnus = laskun_lisatiedot.otunnus )
            JOIN lasku
            ON ( lasku.yhtio = laskun_lisatiedot.yhtio
                AND lasku.tunnus = laskun_lisatiedot.otunnus)
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.otunnus = '{$tilausnumero}'";
$result = pupe_query($query);

$tilausrivit = array();

while ($tilausrivi = mysql_fetch_assoc($result)) {
    $tilausrivit[] = $tilausrivi;
    //setataan muuttujat monta kertaa
    $tarjouksen_asiakkaat = $tilausrivi['sopimus_lisatietoja'];
    $clearing = $tilausrivi['clearing'];
}

$tarjouksen_asiakkaat = explode(',', $tarjouksen_asiakkaat);

$dummy_tarjouksen_tilausnumero = $tilausnumero;

foreach ($tarjouksen_asiakkaat as $tarjous_asiakas) {
    $kukarow['kesken'] = 0;
    
    $tilausnumero = luo_myyntitilausotsikko('TARJOUS', $tarjous_asiakas);

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
    
    foreach ($tilausrivit as $tilausrivi) {

        if ($tilausrivi['tunnus'] == $tilausrivi['perheid'] or $tilausrivi['perheid'] == 0) {
            //TODO netto check EXTTARJOUS EXTENNAKKO
            $trow = hae_tuote($tilausrivi['tuoteno']);
            $parametrit = array(
                'trow' => $trow,
                'laskurow' => $laskurow,
                'kpl' => ($tilausrivi['varattu'] + $tilausrivi['jt']),
                'netto' => 'N',
            );
            lisaa_rivi($parametrit);
        }
    }
    
    require_once('tilaus-valmis.inc');
}

$query = "  DELETE FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$dummy_tarjouksen_tilausnumero}'";
pupe_query($query);