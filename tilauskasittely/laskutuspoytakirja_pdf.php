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
    $tyomaaraykset = \PDF\Laskutuspoytakirja\pdf_hae_tyomaaraykset($lasku_tunnukset);

    sorttaa($tyomaaraykset);

    die();
    foreach ($tyomaaraykset as $key1 => $value1) {

      foreach ($value1['rivit'] as $key2 => $value2) {
        $a1 = $value2;
        $a2 = array(
            "tuoteno" => "1",
            "nimitys" => "x",
            "kpl"     => 1,
            "hinta"   => 1,
            "ale1"    => 1
        );
        $uusi_rivi = array_intersect_key($a1, $a2);
        $uudet_rivit[] = $uusi_rivi;
      }

      $groups = array();

      $full_total = 0;

      foreach ($uudet_rivit as $index => $uusi_rivi) {

        $full_total += $uusi_rivi['hinta'];
        $tuoteno = $uusi_rivi['tuoteno'];

        if (!isset($groups[$tuoteno])) {
          $count = 1;
          $groups[$tuoteno] = 1;
        }
        else {
          $count += 1;
          unset($uudet_rivit[$index - 1]);
        }

        if (isset($uudet_rivit[$index])) {
          $uudet_rivit[$index]['kpl'] = $count;
          $uudet_rivit[$index]['hinta'] = number_format($uudet_rivit[$index]['hinta'], 2);
          $total = $uudet_rivit[$index]['kpl'] * $uudet_rivit[$index]['hinta'];
          $total = $total - ( ( $total / 100 ) * $uudet_rivit[$index]['ale1'] );
          $uudet_rivit[$index]['total'] = number_format($total, 2);
        }
      }
      $uudet_rivit = array_values($uudet_rivit);
      usort($uudet_rivit, "\PDF\Laskutuspoytakirja\kpl_sort");
      $tyomaaraykset[$key1]['full_total'] = number_format($full_total, 2);
      $tyomaaraykset[$key1]['rivit'] = $uudet_rivit;
      $filepath = kirjoita_json_tiedosto($tyomaaraykset[$key1], "laskutuspoytakirja_{$tyomaaraykset[$key1]['tunnus']}");
      $pdf_tiedosto = aja_ruby($filepath, 'laskutuspoytakirja_pdf');
    }
  }
  return $pdf_tiedosto;
}

function sorttaa($tyomaarays) {
  global $kukarow, $yhtiorow;

  $laskutuspoytakirja_rivit = array();
  //SELECT DISTINCT tuoteno...
  $unique_tuotenos = array_unique(array_column($tyomaarays['rivit'], 'tuoteno'));

  foreach ($unique_tuotenos as $tuoteno) {
    $rivit = search_array_key_for_value_recursive($tyomaarays['rivit'], 'tuoteno', $tuoteno);
    
    $params = array(
        'direction' => 'y',
        'key'       => 'hinta'
    );
    $hinta = sum_array($rivit, $params);
    $params = array(
        'direction' => 'y',
        'key'       => 'tilkpl'
    );
    $kpl = sum_array($rivit, $params);
    $laskutuspoytakirja_rivit[$tuoteno] = array(
        'tuoteno' => $tuoteno,
        'nimitys' => $rivit[0]['nimitys'],
        'hinta' => $hinta,
        'kpl' => $kpl,
    );
  }
  
  return $laskutuspoytakirja_rivit;
}

function kpl_sort($a, $b) {
  if ($a['kpl'] < $b['kpl']) {
    return 1;
  }
  else if ($a['kpl'] > $b['kpl']) {
    return -1;
  }
  else {
    return 0;
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
            GROUP BY lasku.tunnus";

  $result = pupe_query($query);

  $tyomaarays = false;
  $rivit = array();
  while ($tyomaarays_temp = mysql_fetch_assoc($result)) {
    if (!$tyomaarays) {
      $tyomaarays['kohde'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_kohde($tyomaarays_temp['laite_tunnus']);
      $tyomaarays['asiakas'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_asiakas($tyomaarays_temp['liitostunnus']);
      $tyomaarays['yhtio'] = $yhtiorow;
      $tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());
      $tyomaarays['tyomaarays'] = $tyomaarays_temp;
    }

    $tyomaaraysrivit_temp = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays_temp['tunnus']);
    foreach ($tyomaaraysrivit_temp as $rivi_temp) {
      $rivit[] = $rivi_temp;
    }
  }

  $tyomaarays['rivit'] = $rivit;

  return $tyomaarays;



  $tyomaaraykset = array();
  while ($tyomaarays = mysql_fetch_assoc($result)) {
    //vaikka ty?m??r?yksen kaikki tiedot saisi yll? olevasta queryst?,
    //haetaan ne silti erillisill? queryill?,
    //koska ruby:lle p?ss?tt?v?st? arrayst? halutaan multidimensional array.
    //T?m? sen takia ett? pdf koodiin ei tarvitsisi koskea.
    if (isset($tyomaaraykset[$tyomaarays['kohde_tunnus']])) {
      $tyomaaraysrivit_temp = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      foreach ($tyomaaraysrivit_temp as $rivi_temp) {
        $tyomaaraykset[$tyomaarays['kohde_tunnus']]['rivit'][] = $rivi_temp;
      }
    }
    else {
      $tyomaarays['rivit'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      $tyomaarays['kohde'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_kohde($tyomaarays['laite_tunnus']);
      $tyomaarays['asiakas'] = \PDF\Laskutuspoytakirja\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
      $tyomaarays['yhtio'] = $yhtiorow;
      $tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());
      $tyomaaraykset[$tyomaarays['kohde_tunnus']] = $tyomaarays;
    }
  }

  return $tyomaaraykset;
}

function hae_tyomaarayksen_rivit($lasku_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT tilausrivi.*,
            tilausrivin_lisatiedot.*,
            tilausrivin_lisatiedot.asiakkaan_positio AS laite_tunnus,
            toimenpiteen_tyyppi.selite AS toimenpiteen_tyyppi,
            huoltosyklit_laitteet.huoltovali AS toimenpiteen_huoltovali,
            poikkeamarivi.tunnus AS poikkeamarivi_tunnus
            FROM tilausrivi
            JOIN tyomaarays
            ON ( tyomaarays.yhtio = tilausrivi.yhtio
              AND tyomaarays.otunnus = tilausrivi.otunnus
              AND tyomaarays.tyostatus != 'K')
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.asiakkaan_positio != 0 )
            LEFT JOIN tilausrivi AS poikkeamarivi
            ON ( poikkeamarivi.yhtio = tilausrivin_lisatiedot.yhtio
              AND poikkeamarivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki )
            LEFT JOIN tuotteen_avainsanat AS toimenpiteen_tyyppi
            ON ( toimenpiteen_tyyppi.yhtio = tilausrivi.yhtio
              AND toimenpiteen_tyyppi.tuoteno = tilausrivi.tuoteno
              AND toimenpiteen_tyyppi.laji = 'tyomaarayksen_ryhmittely' )
            JOIN laite
            ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
              AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN tuote AS laite_tuote
            ON ( laite_tuote.yhtio = laite.yhtio
              AND laite_tuote.tuoteno = laite.tuoteno )
            JOIN tuotteen_avainsanat AS sammutin_koko
            ON ( sammutin_koko.yhtio = laite_tuote.yhtio
              AND sammutin_koko.tuoteno = laite_tuote.tuoteno
              AND sammutin_koko.laji = 'sammutin_koko' )
            JOIN tuotteen_avainsanat AS sammutin_tyyppi
            ON ( sammutin_tyyppi.yhtio = laite_tuote.yhtio
              AND sammutin_tyyppi.tuoteno = laite_tuote.tuoteno
              AND sammutin_tyyppi.laji = 'sammutin_tyyppi' )
            LEFT JOIN huoltosykli
            ON ( huoltosykli.yhtio = tilausrivi.yhtio
              AND huoltosykli.toimenpide = tilausrivi.tuoteno
              AND huoltosykli.olosuhde = paikka.olosuhde
              AND huoltosykli.koko = sammutin_koko.selite
              AND huoltosykli.tyyppi = sammutin_tyyppi.selite )
            LEFT JOIN huoltosyklit_laitteet
            ON ( huoltosyklit_laitteet.yhtio = huoltosykli.yhtio
              AND huoltosyklit_laitteet.huoltosykli_tunnus = huoltosykli.tunnus
              AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$lasku_tunnus}'
            AND tilausrivi.var != 'P'";

  $result = pupe_query($query);

  $rivit = array();
  while ($rivi = mysql_fetch_assoc($result)) {

    $rivi['laite'] = \PDF\Laskutuspoytakirja\hae_rivin_laite($rivi['asiakkaan_positio']);

    if (search_array_key_for_value_recursive($rivi['sisaltyvat_tyot'], 'toimenpiteen_tyyppi', 'tarkastus')) {
      $rivi['tarkastus'] = date('my', strtotime($rivi['toimitettuaika']));
    }

    if (search_array_key_for_value_recursive($rivi['sisaltyvat_tyot'], 'toimenpiteen_tyyppi', 'huolto')) {
      $rivi['huolto'] = date('my', strtotime($rivi['toimitettuaika']));
    }

    $rivit[] = $rivi;
  }

  return $rivit;
}

function hae_rivin_laite($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT laite.*,
            concat_ws(' ', tuote.nimitys, tuote.tuoteno) as nimitys,
            sammutin_koko.selite as sammutin_koko,
            sammutin_tyyppi.selite as sammutin_tyyppi
            FROM laite
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = laite.tuoteno )
            JOIN tuotteen_avainsanat as sammutin_koko
            ON ( sammutin_koko.yhtio = tuote.yhtio
              AND sammutin_koko.tuoteno = tuote.tuoteno
              AND sammutin_koko.laji = 'sammutin_koko' )
            JOIN tuotteen_avainsanat as sammutin_tyyppi
            ON ( sammutin_tyyppi.yhtio = tuote.yhtio
              AND sammutin_tyyppi.tuoteno = tuote.tuoteno
              AND sammutin_tyyppi.laji = 'sammutin_tyyppi' )
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);

  $laite = mysql_fetch_assoc($result);

  return $laite;
}

function hae_tyomaarayksen_asiakas($asiakas_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$asiakas_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_tyomaarayksen_kohde($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT kohde.*
            FROM kohde
            JOIN paikka
            ON ( paikka.yhtio = kohde.yhtio
              AND paikka.kohde = kohde.tunnus )
            JOIN laite
            ON ( laite.yhtio = kohde.yhtio
              AND laite.paikka = paikka.tunnus
              AND laite.tunnus = '{$laite_tunnus}')
            WHERE kohde.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}
