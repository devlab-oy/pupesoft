<?php

namespace PDF\Poikkeamaraportti;

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../inc/parametrit.inc')) {
  require_once($filepath . '/../inc/parametrit.inc');
}
else {
  require_once($filepath . '/parametrit.inc');
}

function hae_poikkeamaraportit($lasku_tunnukset) {
  $pdf_tiedostot = array();
  if (!empty($lasku_tunnukset)) {
    $tyomaaraykset = \PDF\Poikkeamaraportti\pdf_hae_tyomaarays($lasku_tunnukset);

    foreach ($tyomaaraykset as $tyomaarays) {
      $filepath = kirjoita_json_tiedosto($tyomaarays, "poikkeamaraportti_{$tyomaarays['tunnus']}");

      $pdf_tiedostot[] = aja_ruby($filepath, 'poikkeamaraportti_pdf');
    }
  }

  return $pdf_tiedostot;
}

function pdf_hae_tyomaarays($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  if (empty($lasku_tunnukset)) {
    return false;
  }
  
  //queryyn joinataan tauluja kohteeseen saakka, koska tarkastuspöytäkirjat halutaan tulostaa per kohde mutta työmääräykset on laite per työmääräin
  $query = "  SELECT lasku.*,
        kohde.tunnus as kohde_tunnus,
        laite.tunnus as laite_tunnus,
        kuka.nimi as tyon_suorittaja
        FROM lasku
        JOIN tyomaarays
        ON ( tyomaarays.yhtio = lasku.yhtio
          AND tyomaarays.otunnus = lasku.tunnus )
        LEFT JOIN kuka
        ON ( kuka.yhtio = tyomaarays.yhtio
          AND kuka.kuka = tyomaarays.tyojono )
        JOIN tilausrivi
        ON ( tilausrivi.yhtio = lasku.yhtio
          AND tilausrivi.otunnus = lasku.tunnus )
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
        AND lasku.tunnus IN('".implode("','", $lasku_tunnukset)."')
        GROUP BY lasku.tunnus";

  $result = pupe_query($query);

  $tyomaaraykset = array();
  while ($tyomaarays = mysql_fetch_assoc($result)) {
    //vaikka työmääräyksen kaikki tiedot saisi yllä olevasta querystä,
    //haetaan ne silti erillisillä queryillä,
    //koska ruby:lle pässättävästä arraystä halutaan multidimensional array.
    //Tämä sen takia että pdf koodiin ei tarvitsisi koskea.
    if (isset($tyomaaraykset[$tyomaarays['kohde_tunnus']])) {
      $tyomaaraysrivit_temp = \PDF\Poikkeamaraportti\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      foreach ($tyomaaraysrivit_temp as $rivi_temp) {
        $tyomaaraykset[$tyomaarays['kohde_tunnus']]['rivit'][] = $rivi_temp;
      }
    }
    else {
      $tyomaarays['rivit'] = \PDF\Poikkeamaraportti\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
      $tyomaarays['kohde'] = \PDF\Poikkeamaraportti\hae_tyomaarayksen_kohde($tyomaarays['laite_tunnus']);
      $tyomaarays['asiakas'] = \PDF\Poikkeamaraportti\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
      $tyomaarays['yhtio'] = $yhtiorow;
      $tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());
      //tyomääräys otsikolle voidaan asettaa toimitettuaika ekalta riviltä, koska työmääräys pitää sisällään toistaiseksi aina vain yhden rivin
      $tyomaarays['toimitettuaika'] = $tyomaarays['rivit'][0]['toimitettuaika'];
      $tyomaaraykset[$tyomaarays['kohde_tunnus']] = $tyomaarays;
    }
  }

  return $tyomaaraykset;
}

function hae_tyomaarayksen_rivit($lasku_tunnus) {
  global $kukarow, $yhtiorow;

  //tilausrivin_lisatiedot.asiakkaan_positio != 0, koska queryllä ei haluta hakea
  //työmääräykselle lisättyjä laite rivejä. haemme ainoastaan toimenpide rivit, jotka liittyvät johonkin laitteeseen (asiakkaan_positio)

  //jos tilausrivilinkki == 0 tämä tarkoittaa, että kyseessä ei ole ollut laitteen vaihto
  $query = "  SELECT tilausrivi.*,
        tilausrivin_lisatiedot.asiakkaan_positio,
        tilausrivin_lisatiedot.tilausrivilinkki,
        IF(poistettu_laite.tuoteno IS NOT NULL,
          CONCAT_WS(' ".t("Laite").": ', CONCAT_WS(' -> ', CONCAT('".t("Toimenpide").": ', poikkeus_rivi.nimitys), tilausrivi.nimitys), CONCAT_WS(' -> ', poistettu_laite.tuoteno, laite.tuoteno)),
          CONCAT_WS(' -> ', poikkeus_rivi.nimitys, tilausrivi.nimitys)
        ) AS poikkeus_teksti
        FROM tilausrivi
        JOIN tilausrivin_lisatiedot
        ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
          AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
          AND tilausrivin_lisatiedot.asiakkaan_positio != 0 )
        LEFT JOIN tilausrivi as poikkeus_rivi
        ON ( poikkeus_rivi.yhtio = tilausrivin_lisatiedot.yhtio
          AND poikkeus_rivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki )
        LEFT JOIN tilausrivin_lisatiedot as poikkeus_toimenpide_tilausrivi_lisatiedot
        ON ( poikkeus_toimenpide_tilausrivi_lisatiedot.yhtio = poikkeus_rivi.yhtio
          AND poikkeus_toimenpide_tilausrivi_lisatiedot.tilausrivitunnus = poikkeus_rivi.tunnus )
        LEFT JOIN laite as poistettu_laite
        ON ( poistettu_laite.yhtio = poikkeus_toimenpide_tilausrivi_lisatiedot.yhtio
          AND poistettu_laite.tunnus = poikkeus_toimenpide_tilausrivi_lisatiedot.asiakkaan_positio )
        LEFT JOIN laite
        ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
          AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
        WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
        AND tilausrivi.otunnus = '{$lasku_tunnus}'
        AND tilausrivi.var != 'P'";
  $result = pupe_query($query);

  $rivit = array();
  while ($rivi = mysql_fetch_assoc($result)) {

    $rivi['laite'] = \PDF\Poikkeamaraportti\hae_rivin_laite($rivi['asiakkaan_positio']);
    $rivi['poikkeama'] = \PDF\Poikkeamaraportti\hae_rivin_poikkeama($rivi['tilausrivilinkki']);
    $rivit[] = $rivi;
  }

  return $rivit;
}


function hae_rivin_poikkeama($tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT *
        FROM tilausrivi
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tunnus = '{$tilausrivi_tunnus}'
        AND var = 'P'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_rivin_laite($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT *
        FROM laite
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
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
