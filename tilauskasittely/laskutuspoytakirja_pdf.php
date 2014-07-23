<?php

namespace PDF\Laskutuspoytakirja;

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../inc/parametrit.inc')) {
  require_once($filepath . '/../inc/parametrit.inc');
  require_once($filepath . '/../inc/tyojono2_functions.inc');
}
else {
  require_once($filepath . '/parametrit.inc');
  require_once($filepath . '/tyojono2_functions.inc');
}

function hae_laskutuspoytakirja($lasku_tunnukset) {
  if (!empty($lasku_tunnukset)) {
    $tyomaarays = \PDF\Laskutuspoytakirja\pdf_hae_tyomaaraykset($lasku_tunnukset);

    $params = array(
        'direction' => 'y',
        'key'       => 'hinta_yhteensa'
    );
    $tyomaarays['kaikki_yhteensa'] = sum_array($tyomaarays['rivit'], $params);

    $params = array(
        'direction' => 'y',
        'key'       => 'alv_maara_yhteensa'
    );
    $tyomaarays['alv_maara_yhteensa'] = (float) number_format(sum_array($tyomaarays['rivit'], $params), 2);
    $tyomaarays['alv_prosentti'] = $tyomaarays['rivit'][0]['alv_prosentti'];

    $filepath = kirjoita_json_tiedosto($tyomaarays, "laskutuspoytakirja_{$tyomaarays['tunnus']}");
    $pdf_tiedosto = aja_ruby($filepath, 'laskutuspoytakirja_pdf');

    return $pdf_tiedosto;
  }
}

function pdf_hae_tyomaaraykset($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  if (empty($lasku_tunnukset)) {
    return false;
  }

  if (is_array($lasku_tunnukset)) {
    $lasku_tunnukset = implode(",", $lasku_tunnukset);
  }

  //queryyn joinataan tauluja kohteeseen saakka, koska tarkastusp?yt?kirjat halutaan tulostaa per kohde mutta ty?m??r?ykset on laite per ty?m??r?in
  //Käyttöliittymästä tulee vain yhden kohteen lasku.tunnuksia
  $query = "SELECT lasku.*,
            kohde.tunnus as kohde_tunnus,
            laite.tunnus as laite_tunnus
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
              AND tilausrivi.var != 'P')
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN laite
            ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
              AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus IN({$lasku_tunnukset})
            LIMIT 1";

  $result = pupe_query($query);

  $tyomaarays = mysql_fetch_assoc($result);
  $tyomaarays['kohde'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_kohde($tyomaarays['kohde_tunnus']);
  $tyomaarays['asiakas'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
  $tyomaarays['yhtio'] = $yhtiorow;
  $tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());
  $tyomaarays['rivit'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_rivit($lasku_tunnukset);

  return $tyomaarays;
}

function hae_tyomaarayksen_rivit($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  $alekentta = generoi_alekentta('M', 'tilausrivi', 'ei');
  $query = "SELECT laite.tila AS laite_tila,
            IF(laite.tunnus IS NULL, 'muu', 'laite') AS onko_laite,
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.tilkpl,
            tilausrivi.hinta,
            tilausrivi.alv,
            tilausrivi.hinta * tilausrivi.tilkpl AS alentamaton_rivihinta,
            {$alekentta} * tilausrivi.hinta * tilausrivi.tilkpl AS rivihinta,
            ({$alekentta} * tilausrivi.hinta * tilausrivi.tilkpl) * (tilausrivi.alv / 100) AS alv_maara
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            LEFT JOIN laite
            ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
              AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.var != 'P'
            AND tilausrivi.otunnus IN ({$lasku_tunnukset})";

  $result = pupe_query($query);

  $rivit = array();
  while ($rivi = mysql_fetch_assoc($result)) {
    if ($rivi['onko_laite'] == 'laite' and !in_array($rivi['laite_tila'], array('N','V'))) {
      continue;
    }
    $rivit[] = $rivi;
  }

  $unique_tuotenos = array_unique(array_column($rivit, 'tuoteno'));

  $laskutuspoytakirja_rivit = array();
  foreach ($unique_tuotenos as $tuoteno) {
    $rivit_temp = search_array_key_for_value_recursive($rivit, 'tuoteno', $tuoteno);

    $params = array(
        'direction' => 'y',
        'key'       => 'rivihinta'
    );
    $rivihinta_yhteensa = sum_array($rivit_temp, $params);

    $params = array(
        'direction' => 'y',
        'key'       => 'tilkpl'
    );
    $kpl_yhteensa = sum_array($rivit_temp, $params);

    $params = array(
        'direction' => 'y',
        'key'       => 'alv_maara'
    );
    $alv_maara_yhteensa = sum_array($rivit_temp, $params);

    $params = array(
        'direction' => 'y',
        'key'       => 'alentamaton_rivihinta'
    );
    $alentamaton_rivihinta = sum_array($rivit_temp, $params);

    if ($rivihinta_yhteensa != 0 and $alentamaton_rivihinta != 0 and $rivihinta_yhteensa != $alentamaton_rivihinta) {
      $alennusprosentti = number_format($rivihinta_yhteensa / $alentamaton_rivihinta, 2);
    }
    else {
      $alennusprosentti = 0.00;
    }

    $laskutuspoytakirja_rivit[] = array(
        'tuoteno'            => $tuoteno,
        'nimitys'            => $rivit_temp[0]['nimitys'],
        'hinta'              => $rivit_temp[0]['hinta'],
        'hinta_yhteensa'     => $rivihinta_yhteensa,
        'alv_maara_yhteensa' => $alv_maara_yhteensa,
        'kpl'                => $kpl_yhteensa,
        'alennusprosentti'   => $alennusprosentti,
        'alv_prosentti'      => $rivit_temp[0]['alv'],
    );
  }

  $kpl_sort = array();
  foreach ($laskutuspoytakirja_rivit as $key => $row) {
    $kpl_sort[$key] = $row['kpl'];
  }

  array_multisort($kpl_sort, SORT_DESC, $laskutuspoytakirja_rivit);

  return $laskutuspoytakirja_rivit;
}

function hae_tyomaarayksen_asiakas($asiakas_tunnus) {
  global $kukarow, $yhtiorow;

  if (empty($asiakas_tunnus)) {
    return array();
  }

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$asiakas_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_tyomaarayksen_kohde($kohde_tunnus) {
  global $kukarow, $yhtiorow;

  if (empty($kohde_tunnus)) {
    return array();
  }

  $query = "SELECT kohde.*
            FROM kohde
            WHERE kohde.yhtio = '{$kukarow['yhtio']}'
            AND kohde.tunnus = '{$kohde_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}
