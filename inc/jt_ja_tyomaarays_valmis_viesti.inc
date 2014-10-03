<?php

/**
 * Lähettää parametrina annetun viestin sms-viestinä parametrina annetun asiakkaan gms-numeroon,
 * jos sellainen löytyy. Toistaiseksi ainut tuettu palvelu on Zoner. Lähettää myös sähköpostin, jos
 * asiakkaalta sellainen löytyy
 *
 * @param string $username Zonerin käyttäjätunnus
 * @param string $salasana Zonerin salasana
 * @param int    $tilausnumero
 * @param bool   $kerattu Onko kaikki tilauksen rivit jo kerätty
 *
 * @return bool Jos tekstiviestin tai sähköpostin lähetys onnistui, palauttaa true
 */
function laheta_vahvistusviesti($username, $salasana, $tilausnumero, $kerattu = false) {
  global $yhtiorow;

  $lahetetty = false;

  $tilaus  = hae_tilaus($tilausnumero);
  $asiakas = hae_asiakas_vahvistusta_varten($tilaus['liitostunnus']);

  if ($tilaus["clearing"] == "JT-TILAUS") {
    $otsikko = t("Tilaus noudettavissa");
    $viesti  = t("Kaikki tilauksen ") .
      $tilausnumero .
      t(" osat ovat noudettavissa, terveisin ") .
      $yhtiorow["nimi"];
  }
  elseif (loytyy_tyomaarays($tilausnumero) or $kerattu) {
    $otsikko = t("Huoltotyö valmis");
    $viesti  = t("Huoltotyösi on valmis, terveisin ") . $yhtiorow["nimi"];
  }

  if (empty($viesti)) {
    return false;
  }

  if (!empty($asiakas["gsm"])) {
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

    $vastaus = pupesoft_rest($parameters);
    $vastaus = $vastaus[1];
    $vastaus = explode(" ", $vastaus);
    $vastaus = $vastaus[0];

    if ($vastaus == "OK") {
      $lahetetty = true;
    }
  }

  if (!empty($asiakas["email"])) {
    $parametrit = array(
      "to"      => $asiakas["email"],
      "cc"      => "",
      "subject" => $otsikko,
      "body"    => $viesti
    );

    if (pupesoft_sahkoposti($parametrit)) {
      $lahetetty = true;
    }
  }

  return $lahetetty;
}

function hae_asiakas_vahvistusta_varten($tunnus) {
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
