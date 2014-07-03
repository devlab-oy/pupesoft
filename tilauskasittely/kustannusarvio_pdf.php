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

function hae_huollot($asiakas, $start, $end) {
  global $kukarow, $yhtiorow;

  if (empty($asiakas) or empty($start) or empty($end)) {
    return false;
  }

  $huoltovalit = huoltovali_options();

  $query = "SELECT laite.tunnus AS laite_tunnus,
            kohde.tunnus AS kohde_tunnus,
            kohde.nimi AS kohde_nimi,
            huoltosyklit_laitteet.viimeinen_tapahtuma,
            huoltosyklit_laitteet.huoltovali AS huoltovali,
            huoltosykli.toimenpide AS toimenpide_tuote,
            toimenpide_tuote.myyntihinta AS myyntihinta,
            tuotteen_avainsanat.selite AS toimenpide_tuotteen_tyyppi
            FROM   laite
            JOIN huoltosyklit_laitteet
            ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
              AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = laite.yhtio
              AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
            JOIN tuote AS toimenpide_tuote
            ON ( toimenpide_tuote.yhtio = huoltosykli.yhtio
              AND toimenpide_tuote.tuoteno = huoltosykli.toimenpide )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas
              AND asiakas.tunnus = {$asiakas})
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            LEFT JOIN tuotteen_avainsanat
            ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
              AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
              AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
            WHERE  laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tila IN ('N', 'V', 'K')/*Normaali, varalaite, kateissa*/
            AND ( laite.omistaja = '' OR laite.omistaja IS NULL )
            AND DATE_ADD( huoltosyklit_laitteet.viimeinen_tapahtuma, INTERVAL huoltosyklit_laitteet.huoltovali DAY) BETWEEN '{$start}' AND '{$end}'
            ORDER BY laite_tunnus ASC,
            tuotteen_avainsanat.selitetark ASC";

  $result = pupe_query($query);

  $laitteet = array();
  while ($laite = mysql_fetch_assoc($result)) {
    $huoltovali = search_array_key_for_value_recursive($huoltovalit, days, $laite['huoltovali']);
    $huoltovali = $huoltovali[0];
    $laite['seuraava_tapahtuma'] = date('Y-m-d', strtotime("{$laite['viimeinen_tapahtuma']} + {$huoltovali['years']} years"));
    $laite['myyntihinta'] = (float) $laite['myyntihinta'];

    //Resultti groupataan laite_tunnukset ja seuraavan tapahtuman mukaan, jotta eripäivinä
    //tapahtuvat tapahtumat osataan erottaa toisistaan.
    //Esim. jos koeponnistus 2014-01-01 ja tarkastus 2014-02-01 niin koeponnistus ei saa yliajaa tarkastusta
    if (empty($laitteet[$laite['laite_tunnus']][$laite['seuraava_tapahtuma']])) {
      $laitteet[$laite['laite_tunnus']][$laite['seuraava_tapahtuma']] = $laite;
    }
  }

  $kohde_rivit = array();
  $kohde_rivit['total_hinta'] = 0;
  foreach ($laitteet as $laitteen_huollot) {
    foreach ($laitteen_huollot as $laitteen_huolto) {
      if (!isset($kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']])) {
      if ($laitteen_huolto['toimenpide_tuotteen_tyyppi'] == 'koeponnistus') {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['koeponnistus_kpl'] = 1;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['huolto_kpl'] = 0;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['tarkastus_kpl'] = 0;
      }
      else if ($laitteen_huolto['toimenpide_tuotteen_tyyppi'] == 'huolto') {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['koeponnistus_kpl'] = 0;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['huolto_kpl'] = 1;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['tarkastus_kpl'] = 0;
      }
      else {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['koeponnistus_kpl'] = 0;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['huolto_kpl'] = 0;
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['tarkastus_kpl'] = 1;
      }
      $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['hinta'] = $laitteen_huolto['myyntihinta'];
      $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['kohde_nimi'] = $laitteen_huolto['kohde_nimi'];
      $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['toimenpide_kpl'] = 1;
    }
    else {
      if ($laitteen_huolto['toimenpide_tuotteen_tyyppi'] == 'koeponnistus') {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['koeponnistus_kpl']++;
      }
      else if ($laitteen_huolto['toimenpide_tuotteen_tyyppi'] == 'huolto') {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['huolto_kpl']++;
      }
      else {
        $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['tarkastus_kpl']++;
      }
      $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['toimenpide_kpl']++;
      $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['hinta'] = $kohde_rivit['rivit'][$laitteen_huolto['kohde_tunnus']]['hinta'] + $laitteen_huolto['myyntihinta'];
    }
    $kohde_rivit['total_hinta'] = $kohde_rivit['total_hinta'] + $laitteen_huolto['myyntihinta'];
    }
  }

  $kohde_rivit['alku'] = date('j.n.Y', strtotime($start));
  $kohde_rivit['loppu'] = date('j.n.Y', strtotime($end));
  $kohde_rivit['logo'] = base64_encode(hae_yhtion_lasku_logo());
  $kohde_rivit['asiakas'] = hae_asiakas($asiakas);
  $kohde_rivit['yhtio'] = hae_yhtion_parametrit($kukarow['yhtio']);

  return $kohde_rivit;
}
