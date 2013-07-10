<?php

require_once('tilauskasittely/luo_myyntitilausotsikko.inc');
$query = "  SELECT sopimus_lisatietoja
            FROM laskun_lisatiedot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tilausnumero}'";
$result = pupe_query($query);
$tarjouksen_asiakkaat = mysql_fetch_assoc($result);
$tarjouksen_asiakkaat = explode(',', $tarjouksen_asiakkaat['sopimus_lisatietoja']);

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
    
    require_once('tilaus-valmis.inc');
}

$query = "  DELETE FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$dummy_tarjouksen_tilausnumero}'";
pupe_query($query);