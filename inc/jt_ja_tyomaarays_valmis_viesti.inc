<?php

/**
 * Lähettää parametrina annetun viestin sms-viestinä parametrina annetun asiakkaan gms-numeroon,
 * jos sellainen löytyy. Toistaiseksi ainut tuettu palvelu on Zoner. Lähettää myös sähköpostin, jos
 * asiakkaalta sellainen löytyy
 *
 * @param string $username Zonerin käyttäjätunnus
 * @param string $salasana Zonerin salasana
 * @param int    $tilausnumero
 * @param bool   $tyomaarays Onko kyseessä työmääräys. Jos arvoksi annetaan true, viesti
 *   työmääräyksen valmistumisesta lähtee heti.
 *
 * @return bool Jos tekstiviestin tai sähköpostin lähetys onnistui, palauttaa true
 */

function laheta_vahvistusviesti($username, $salasana, $tilausnumero, $tyomaarays = false) {
  global $yhtiorow;

  $lahetetty = false;

  $tilaus  = hae_tilaus($tilausnumero);
  $asiakas = hae_asiakas_vahvistusta_varten($tilaus['liitostunnus']);

  if (loytyy_jt_riveja($tilausnumero)) {
    $otsikko = t("Tilaus noudettavissa");

    $avainsanaviesti = t_avainsana("JTVALMISVIESTI", "", "", "", "", "selite");

    if (empty($avainsanaviesti)) {
      $viesti =
        t("Hei. Kaikki tilauksen %s tuotteet ovat noudettavissa. Terveisin %s, %s.",
          "",
          $tilausnumero,
          $yhtiorow["nimi"],
          $yhtiorow["puhelin"]);
    }
    else {
      $viesti = "{$avainsanaviesti}\n" . t("Tilausnumero") . ": {$tilausnumero}";
    }
  }
  elseif ($tyomaarays) {
    $otsikko = t("Huoltotyö valmis");

    $avainsanaviesti = t_avainsana("TYOMVALMVIEST", "", "", "", "", "selite");

    if (empty($avainsanaviesti)) {
      $viesti =
        t("Hei. Huolto- ja asennustyönne %s on valmistunut. Terveisin, %s, %s.",
          "",
          $tilausnumero,
          $yhtiorow["nimi"],
          $yhtiorow["puhelin"]);
    }
    else {
      $viesti = "{$avainsanaviesti}\n" . t("Tilausnumero") . ": {$tilausnumero}";
    }
  }

  if (empty($viesti)) {
    return false;
  }

  $gsm   = empty($tilaus["toim_puh"]) ? $asiakas["gsm"] : $tilaus["toim_puh"];
  $email = empty($tilaus["toim_email"]) ? $asiakas["email"] : $tilaus["toim_email"];

  if (!empty($gsm)) {
    $parameters = array(
      "method"   => "POST",
      "data"     => array(
        "username"   => $username,
        "password"   => $salasana,
        "numberto"   => $gsm,
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
      paivita_vahvistusviesti_lahetetty($tilausnumero);

      $lahetetty = true;
    }
  }

  if (!empty($email)) {
    $parametrit = array(
      "to"      => $email,
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
             FROM  asiakas
             WHERE yhtio  = '{$kukarow['yhtio']}'
             AND   tunnus = '{$tunnus}'";
  $result = pupe_query($query);

  $asiakas = mysql_fetch_assoc($result);

  return $asiakas;
}

function hae_tilaus($tilausnumero) {
  global $kukarow;

  $query  = "SELECT clearing,
             liitostunnus,
             toim_puh,
             toim_email
             FROM  lasku
             WHERE yhtio  = '{$kukarow['yhtio']}'
             AND   tunnus = '{$tilausnumero}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function loytyy_jt_riveja($tilausnumero) {
  global $kukarow;

  $query = "SELECT tilausrivin_lisatiedot.vanha_otunnus
            FROM tilausrivi
            INNER JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$tilausnumero}'";

  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {
    $query = "SELECT count(*) AS jt_rivien_maara
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$rivi['vanha_otunnus']}'
              AND     var = 'J'";

    $result2 = pupe_query($query);
    $result2 = mysql_fetch_assoc($result2);

    if ((int) $result2['jt_rivien_maara'] > 0) {
      return true;
    }
  }

  return false;
}

function paivita_vahvistusviesti_lahetetty($tilausnumero) {
  global $kukarow;

  $query =
    "UPDATE laskun_lisatiedot
     SET vahvistusviesti_lahetetty = now()
     WHERE yhtio = '{$kukarow['yhtio']}'
     AND otunnus = {$tilausnumero}";

  return pupe_query($query);
}

function hae_vahvistusviesti_lahetetty($tilausnumero) {
  global $kukarow;

  $query =
    "SELECT vahvistusviesti_lahetetty
     FROM laskun_lisatiedot
     WHERE yhtio = '{$kukarow['yhtio']}'
     AND otunnus = {$tilausnumero}";

  $result = pupe_query($query);

  $aika = mysql_fetch_assoc($result);
  $aika = $aika["vahvistusviesti_lahetetty"];

  if (empty($aika)) {
    return false;
  }
  else {
    return $aika;
  }
}
