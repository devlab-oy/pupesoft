<?php

namespace PDF\Tarkastuspoytakirja;

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../inc/parametrit.inc')) {
  require_once($filepath . '/../inc/parametrit.inc');
  require_once($filepath . '/../inc/tyojono2_functions.inc');
}
else {
  require_once($filepath . '/parametrit.inc');
  require_once($filepath . '/tyojono2_functions.inc');
}

function hae_tarkastuspoytakirjat($lasku_tunnukset) {
  $pdf_tiedostot = array();
  if (!empty($lasku_tunnukset)) {
    $tyomaaraykset = \PDF\Tarkastuspoytakirja\pdf_hae_tyomaaraykset($lasku_tunnukset);

    foreach ($tyomaaraykset as $tyomaarays) {
      $filepath = kirjoita_json_tiedosto($tyomaarays, "tyomaarays_{$tyomaarays['tunnus']}");

      //ajettavan tiedoston nimi on tarkastuspoytakirja_pdf.rb
      $pdf_tiedostot[] = aja_ruby($filepath, 'tarkastuspoytakirja_pdf');
    }
  }

  return $pdf_tiedostot;
}

function pdf_hae_tyomaaraykset($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  if (empty($lasku_tunnukset)) {
    return false;
  }

  if (is_array($lasku_tunnukset)) {
    $lasku_tunnukset = implode(",", $lasku_tunnukset);
  }

  //queryyn joinataan tauluja kohteeseen saakka, koska tarkastuspöytäkirjat halutaan tulostaa per kohde mutta työmääräykset on laite per työmääräin
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
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              )
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
            AND lasku.tunnus IN({$lasku_tunnukset})";

  $result = pupe_query($query);

  $tyomaaraykset = array();
  while ($tyomaarays = mysql_fetch_assoc($result)) {
    //vaikka työmääräyksen kaikki tiedot saisi yllä olevasta querystä,
    //haetaan ne silti erillisillä queryillä,
    //koska ruby:lle pässättävästä arraystä halutaan multidimensional array.
    //Tämä sen takia että pdf koodiin ei tarvitsisi koskea.
    if (isset($tyomaaraykset[$tyomaarays['kohde_tunnus']])) {
      $tyomaaraysrivit_temp = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      foreach ($tyomaaraysrivit_temp as $rivi_temp) {
        $tyomaaraykset[$tyomaarays['kohde_tunnus']]['rivit'][] = $rivi_temp;
      }
    }
    else {
      $tyomaarays['rivit'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      $tyomaarays['kohde'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_kohde($tyomaarays['laite_tunnus']);
      $tyomaarays['asiakas'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
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
              AND laite_tuote.tuoteno = laite.tuoteno
              AND laite_tuote.tuotetyyppi = ''
              AND laite_tuote.tuoteno != 'MUISTUTUS')
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
    if (!empty($rivi['poikkeamarivi_tunnus'])) {
      $rivi['poikkeus'] = 'X';
    }
    else {
      //Lisätään tyhjä space, jotta pdf-tulostus toimii
      $rivi['poikkeus'] = ' ';
    }

    if ($rivi['toimenpiteen_tyyppi'] == 'koeponnistus') {
      $rivi['koeponnistus'] = date('my', strtotime($rivi['toimitettuaika']));
      $rivi['huolto'] = ' ';
      $rivi['tarkastus'] = ' ';
    }
    else if ($rivi['toimenpiteen_tyyppi'] == 'huolto') {
      $rivi['koeponnistus'] = ' ';
      $rivi['huolto'] = date('my', strtotime($rivi['toimitettuaika']));
      $rivi['tarkastus'] = ' ';
    }
    else if ($rivi['toimenpiteen_tyyppi'] == 'tarkastus') {
      $rivi['koeponnistus'] = ' ';
      $rivi['huolto'] = ' ';
      $rivi['tarkastus'] = date('my', strtotime($rivi['toimitettuaika']));
    }

    $rivi['laite'] = \PDF\Tarkastuspoytakirja\hae_rivin_laite($rivi['asiakkaan_positio']);
    $rivi['sisaltyvat_tyot'] = \PDF\Tarkastuspoytakirja\hae_riviin_sisaltyvat_tyot($rivi['laite_tunnus'], $rivi['tuoteno']);

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
            paikka.nimi AS paikka_nimi,
            concat_ws(' ', tuote.nimitys, tuote.tuoteno) as nimitys,
            palo_luokka.selite AS palo_luokka,
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
            LEFT JOIN tuotteen_avainsanat AS palo_luokka
            ON (palo_luokka.yhtio = tuote.yhtio
              AND palo_luokka.tuoteno = tuote.tuoteno
              AND palo_luokka.laji = 'palo_luokka')
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);

  $laite = mysql_fetch_assoc($result);

  $laite['viimeinen_painekoe'] = hae_laitteen_viimeiset_tapahtumat($laite['tunnus']);
  $laite['viimeinen_painekoe'] = date('my', strtotime($laite['viimeinen_painekoe']['koeponnistus']));

  return $laite;
}

function hae_riviin_sisaltyvat_tyot($laite_tunnus, $toimenpide_tuoteno) {
  global $kukarow, $yhtiorow;

  if (empty($laite_tunnus) or empty($toimenpide_tuoteno)) {
    return false;
  }

  $query = "SELECT toimenpide_tuote.*,
            ta.selite AS toimenpiteen_tyyppi,
            ta.selitetark AS toimenpiteen_jarjestys
            FROM laite
            JOIN huoltosyklit_laitteet AS hl
            ON ( hl.yhtio = laite.yhtio
              AND hl.laite_tunnus = laite.tunnus )
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = hl.yhtio
              AND huoltosykli.tunnus = hl.huoltosykli_tunnus )
            JOIN tuote AS toimenpide_tuote
            ON ( toimenpide_tuote.yhtio = huoltosykli.yhtio
              AND toimenpide_tuote.tuoteno = huoltosykli.toimenpide )
            JOIN tuotteen_avainsanat AS ta
            ON ( ta.yhtio = toimenpide_tuote.yhtio
              AND ta.tuoteno = toimenpide_tuote.tuoteno
              AND ta.selitetark > (  SELECT ta1.selitetark
                          FROM tuote
                          JOIN tuotteen_avainsanat AS ta1
                          ON ( ta1.yhtio = tuote.yhtio
                            AND ta1.tuoteno = tuote.tuoteno )
                          WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                          AND tuote.tuoteno = '{$toimenpide_tuoteno}') )
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = {$laite_tunnus}
            ORDER BY toimenpiteen_jarjestys ASC";
  $result = pupe_query($query);

  $toimenpide_tuotteet = array();
  while ($toimenpide_tuote = mysql_fetch_assoc($result)) {
    $toimenpide_tuotteet[] = $toimenpide_tuote;
  }

  return $toimenpide_tuotteet;
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
