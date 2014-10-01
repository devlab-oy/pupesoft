<?php

/**
 * Lähettää parametrina annetun viestin sms-viestinä parametrina annetun asiakkaan gms-numeroon.
 * Toistaiseksi ainut tuettu palvelu on Zoner.
 *
 * @param string $username Zonerin käyttäjätunnus
 * @param string $salasana Zonerin salasana
 * @param int    $tilausnumero
 * @param bool   $kerattu Onko kaikki tilauksen rivit jo kerätty
 *
 * @return array Vastauskoodi ja viesti palvelimelta
 */
function laheta_sms($username, $salasana, $tilausnumero, $kerattu = false) {
  global $yhtiorow;

  $tilaus  = hae_tilaus($tilausnumero);
  $asiakas = hae_asiakas_sms_viestia_varten($tilaus['liitostunnus']);

  if (empty($asiakas["gsm"])) {
    return false;
  }

  if ($tilaus["clearing"] == "JT-TILAUS") {
    $viesti = t("Kaikki tilauksen ") .
      $tilausnumero .
      t(" osat ovat noudettavissa, terveisin ") .
      $yhtiorow["nimi"];
  }
  elseif (loytyy_tyomaarays($tilausnumero) or $kerattu) {
    $viesti = t("Huoltotyösi on valmis, terveisin ") . $yhtiorow["nimi"];
  }

  if (empty($viesti)) {
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

function hae_tilaus($tilausnumero) {
  global $kukarow;

  $query  = "SELECT *
             FROM lasku
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tunnus = '{$tilausnumero}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function loytyy_tyomaarays($tilausnumero) {
  global $kukarow;

  $query  = "SELECT COUNT(*) AS maara
             FROM tyomaarays
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND otunnus = '{$tilausnumero}'";
  $result = pupe_query($query);
  $result = mysql_fetch_assoc($result);

  return (int) $result["maara"] > 0;
}
