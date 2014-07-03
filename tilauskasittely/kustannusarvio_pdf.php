<?php

namespace PDF\Kustannusarvio;

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../inc/parametrit.inc')) {
  require_once($filepath . '/../inc/parametrit.inc');
}
else {
  require_once($filepath . '/parametrit.inc');
}

function hae_kustannusarvio($asiakas, $alku, $loppu) {
  if (!empty($asiakas)) {
    $huollot = \PDF\Kustannusarvio\hae_huollot($asiakas, $alku, $loppu);
    $filepath = kirjoita_json_tiedosto($huollot, "kustannusarvio_" . uniqid() . "");
    $pdf_tiedosto = aja_ruby($filepath, 'kustannusarvio_pdf');
    return $pdf_tiedosto;
  }
}

function hae_huollot($asiakas, $alku, $loppu) {
  global $kukarow, $yhtiorow;

  if (empty($asiakas) or empty($alku) or empty($loppu)) {
    return false;
  }

  $alku = date('Y-m-d', strtotime($alku));
  $loppu = date('Y-m-d', strtotime($loppu));

  $query = "SELECT kohde.tunnus AS kohde_tunnus,
            kohde.nimi AS kohde_nimi,
            asiakas.nimi AS asiakas_nimi,
            CAST(SUM(tilausrivi.tilkpl) AS UNSIGNED) AS toimenpide_kpl,
            CAST(SUM(CASE WHEN toimenpidetuote_tyyppi.selite = 'tarkastus' THEN tilausrivi.tilkpl ELSE 0 END) AS UNSIGNED) AS tarkastus_kpl,
            CAST(SUM(CASE WHEN toimenpidetuote_tyyppi.selite = 'huolto' THEN tilausrivi.tilkpl ELSE 0 END) AS UNSIGNED) AS huolto_kpl,
            CAST(SUM(CASE WHEN toimenpidetuote_tyyppi.selite = 'koeponnistus' THEN tilausrivi.tilkpl ELSE 0 END) AS UNSIGNED) AS koeponnistus_kpl,
            CAST(SUM(tilausrivi.hinta) AS DECIMAL(10,2)) AS hinta
            FROM   lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
              AND tilausrivi.tyyppi = 'L' )
            JOIN tuotteen_avainsanat AS toimenpidetuote_tyyppi
            ON ( toimenpidetuote_tyyppi.yhtio = tilausrivi.yhtio
              AND toimenpidetuote_tyyppi.tuoteno = tilausrivi.tuoteno
              AND toimenpidetuote_tyyppi.laji = 'tyomaarayksen_ryhmittely' )
            JOIN tilausrivin_lisatiedot AS tl
            ON ( tl.yhtio = tilausrivi.yhtio
              AND tl.tilausrivitunnus = tilausrivi.tunnus )
            JOIN laite
            ON ( laite.yhtio = tl.yhtio
              AND laite.tunnus = tl.asiakkaan_positio )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas )
            WHERE  lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.liitostunnus = {$asiakas}
            AND lasku.toimaika >= '{$alku}'
            AND lasku.toimaika <= '{$loppu}'
            GROUP  BY kohde.tunnus,
            kohde.nimi";

  $result = pupe_query($query);

  $kohde_rivit = array();

  $kohde_rivit['alku'] = date('j.n.Y', strtotime($alku));
  $kohde_rivit['loppu'] = date('j.n.Y', strtotime($loppu));
  $kohde_rivit['logo'] = base64_encode(hae_yhtion_lasku_logo());
  $kohde_rivit['asiakas'] = hae_asiakas($asiakas);
  $kohde_rivit['yhtio'] = hae_yhtion_parametrit($kukarow['yhtio']);

  while ($kohde_rivi = mysql_fetch_assoc($result)) {
    $kohde_rivit['rivit'][] = $kohde_rivi;
  }

  $total_hinta = 0;
  foreach ($kohde_rivit['rivit'] as $rivi) {
    $total_hinta = $total_hinta + $rivi['hinta'];
  }

  $kohde_rivit['total_hinta'] = number_format((float) $total_hinta, 2, '.', '');

  return $kohde_rivit;
}
