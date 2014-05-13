<?php

namespace PDF\Tyolista;

$filepath = dirname(__FILE__);
if (file_exists($filepath.'/../inc/parametrit.inc')) {
  require_once($filepath.'/../inc/parametrit.inc');
  require_once($filepath.'/../inc/tyojono2_functions.inc');
}
else {
  require_once($filepath.'/parametrit.inc');
  require_once($filepath.'/tyojono2_functions.inc');
}

function hae_tyolistat($lasku_tunnukset, $multi = false) {
  if (!empty($lasku_tunnukset)) {
    if ($multi === true) {
      $tyomaarays_rivit = array();
      foreach ($lasku_tunnukset as $tunnus) {
        $tyomaarays_rivit[] = \PDF\Tyolista\pdf_hae_tyomaarayksien_rivit($tunnus);
      }
    }
    else {
      $tyomaarays_rivit = \PDF\Tyolista\pdf_hae_tyomaarayksien_rivit($lasku_tunnukset);
    }

    $filepath = kirjoita_json_tiedosto($tyomaarays_rivit, "tyolista_".uniqid()."");
    
    if (!empty($filepath)) {
      $pdf_tiedosto = aja_ruby($filepath, 'tyolista_pdf');
      
      return $pdf_tiedosto;
    }
    else {
      return false;
    }
  }
}

function pdf_hae_tyomaarayksien_rivit($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  if (empty($lasku_tunnukset)) {
    return false;
  }

  //Työlista halutaan tulostaa per kohde. Käyttöliittymä on suunniteltu niin,
  //että tähän metodiin tulee vain yhden kohteen työmääräyksiä. Sen takia LIMIT 1
  $query = "  SELECT lasku.*,
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
        AND lasku.tunnus IN (".implode(",", $lasku_tunnukset).")
        LIMIT 1";

  $result = pupe_query($query);

  $tyomaarays = mysql_fetch_assoc($result);

  $tyomaarays['rivit'] = \PDF\Tyolista\hae_tyolistan_rivit($lasku_tunnukset);
  $tyomaarays['kohde'] = \PDF\Tyolista\hae_tyomaarayksen_kohde($tyomaarays['laite_tunnus']);
  $tyomaarays['asiakas'] = \PDF\Tyolista\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
  $tyomaarays['yhtio'] = $yhtiorow;
  $tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());

  return $tyomaarays;
}

function hae_tyolistan_rivit($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT tilausrivi.*,
        tilausrivin_lisatiedot.*,
        toimenpiteen_tyyppi.selite as toimenpiteen_tyyppi,
        huoltosyklit_laitteet.huoltovali as toimenpiteen_huoltovali
        FROM tilausrivi
        JOIN tilausrivin_lisatiedot
        ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
          AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
          AND tilausrivin_lisatiedot.asiakkaan_positio != 0 )
        JOIN tuotteen_avainsanat as toimenpiteen_tyyppi
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
        JOIN huoltosykli
        ON ( huoltosykli.yhtio = tilausrivi.yhtio
          AND huoltosykli.toimenpide = tilausrivi.tuoteno
          AND huoltosykli.olosuhde = paikka.olosuhde
          AND huoltosykli.koko = sammutin_koko.selite
          AND huoltosykli.tyyppi = sammutin_tyyppi.selite )
        JOIN huoltosyklit_laitteet
        ON ( huoltosyklit_laitteet.yhtio = huoltosykli.yhtio
          AND huoltosyklit_laitteet.huoltosykli_tunnus = huoltosykli.tunnus
          AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
        WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
        AND tilausrivi.otunnus IN (".implode(",", $lasku_tunnukset).")
        AND tilausrivi.var != 'P'";

  $result = pupe_query($query);

  $rivit = array();
  while ($rivi = mysql_fetch_assoc($result)) {
    if ($rivi['toimenpiteen_tyyppi'] == 'koeponnistus') {
      $rivi['koeponnistus'] = 'X';
      $rivi['huolto'] = ' ';
      $rivi['tarkastus'] = ' ';
    }
    else if ($rivi['toimenpiteen_tyyppi'] == 'huolto') {
      $rivi['koeponnistus'] = ' ';
      $rivi['huolto'] = 'X';
      $rivi['tarkastus'] = ' ';
    }
    else if ($rivi['toimenpiteen_tyyppi'] == 'tarkastus') {
      $rivi['koeponnistus'] = ' ';
      $rivi['huolto'] = ' ';
      $rivi['tarkastus'] = 'X';
    }

    $rivi['laite'] = \PDF\Tyolista\hae_rivin_laite($rivi['asiakkaan_positio']);
    $rivit[] = $rivi;
  }

  return $rivit;
}

function hae_rivin_laite($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT laite.*,
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

  if (isset($laite['viimeinen_painekoe']['koeponnistus']) and $laite['viimeinen_painekoe']['koeponnistus'] != '') {
    $laite['viimeinen_painekoe'] = date('my', strtotime($laite['viimeinen_painekoe']['koeponnistus']));
  }
  else {
    $laite['viimeinen_painekoe'] = '—';
  }

  return $laite;
}

function hae_tyomaarayksen_asiakas($asiakas_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT *
        FROM asiakas
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tunnus = '{$asiakas_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_tyomaarayksen_kohde($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT kohde.*
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
