<?php

/**
 * Lähettää parametrina annetun viestin sms-viestinä parametrina annetun asiakkaan gms-numeroon.
 * Toistaiseksi ainut tuettu palvelu on Zoner.
 *
 * @param string $username Zonerin käyttäjätunnus
 * @param string $salasana Zonerin salasana
 * @param int    $asiakkaan_tunnus
 * @param string $viesti Viesti, joka halutaan lähettää. Yhden viestin maksimipituus on 160
 *   merkkiä, mutta jos annettu viesti on pidetty, se jaetaan useampaan tekstiviestiin, jolloin
 *   yksi viesti voi olla korkeintaan 153 merkkiä pitkä.
 *
 * @return array Vastauskoodi ja viesti palvelimelta
 */
function laheta_sms($username, $salasana, $asiakkaan_tunnus, $viesti) {
  global $yhtiorow;

  $asiakas = hae_asiakas_sms_viestia_varten($asiakkaan_tunnus);

  if (empty($asiakas["gsm"])) {
    return false;
  }

  $parameters = array(
    "method"   => "POST",
    "data"     => array(
      "username"   => $username,
      "password"   => $salasana,
      "numberto"   => $asiakas['gsm'],
      "numberfrom" => $yhtiorow['nimi'],
      "message"    => $viesti
    ),
    "url"      => "https://sms.zoner.fi/sms.php",
    "headers"  => array(),
    "posttype" => "form"
  );

  return pupesoft_rest($parameters);
}

function hae_asiakas_sms_viestia_varten($tunnus) {
  global $kukarow;

  $query  = "SELECT gsm,
             email
             FROM asiakas
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tunnus = '{$tunnus}'";
  $result = pupe_query($query);

  $asiakas = mysql_fetch_assoc($result);

  return $asiakas;
}
