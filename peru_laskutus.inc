<?php

if (!function_exists("poista_tilaus_ja_laskutus")) {
  function poista_tilaus_ja_laskutus($tunnus, $hard_delete = false) {
    global $kukarow;

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus = '{$tunnus}'
              LIMIT 1";
    $result = pupe_query($query);

    $laskurow = mysql_fetch_assoc($result);

    switch ($laskurow["tila"]) {
    case "U":
      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND laskunro = '{$laskurow["laskunro"]}'
                AND tila = 'L'
                AND alatila = 'X'
                LIMIT 1";
      $result = pupe_query($query);

      $llaskurow = mysql_fetch_assoc($result);

      poista_tilaus($llaskurow["tunnus"], $hard_delete);
      poista_laskutus($laskurow["tunnus"], $hard_delete);

      break;
    default:
      if ($laskurow["laskunro"] == 0) {
        poista_tilaus($laskurow["tunnus"], $hard_delete);
      }
      else {
        $query = "SELECT tunnus
                  FROM lasku
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND laskunro = '{$laskurow["laskunro"]}'
                  AND tila = 'U'
                  AND alatila = 'X'
                  LIMIT 1";
        $result = pupe_query($query);

        $ulaskurow = mysql_fetch_assoc($result);

        poista_tilaus($laskurow["tunnus"], $hard_delete);
        poista_laskutus($ulaskurow["tunnus"], $hard_delete);
      }
    }
  }
}

if (!function_exists("poista_tilaus")) {
  function poista_tilaus($tunnus, $hard_delete = false) {
    global $kukarow;

    // Löytyykö tilausta
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus = '{$tunnus}'
              AND (tila IN ('L', 'N', 'E') OR (tila = 'D' AND alatila IN ('L', 'N', 'E')))";
    $laskures = pupe_query($query);

    if (mysql_num_rows($laskures) == 0) return false;

    $laskurow = mysql_fetch_assoc($laskures);

    if ($hard_delete) {
      // Poistetaan tilausotsikko
      $query = "DELETE FROM lasku
                WHERE yhtio	= '{$kukarow["yhtio"]}'
                AND tunnus = '{$laskurow["tunnus"]}'
                AND (tila IN ('L', 'N', 'E') OR (tila = 'D' AND alatila IN ('L', 'N', 'E')))";
    }
    else {
      // Siirretään tilausotsikko ddddd-yhtiölle
      $query = "UPDATE lasku SET
                yhtio = 'ddddd'
                WHERE yhtio	= '{$kukarow["yhtio"]}'
                AND tunnus = '{$laskurow["tunnus"]}'
                AND (tila IN ('L', 'N', 'E') OR (tila = 'D' AND alatila IN ('L', 'N', 'E')))";
    }

    pupe_query($query);

    // Hoidetaan tapahtumat ja saldot
    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio	= '{$kukarow["yhtio"]}'
              AND otunnus	= '{$laskurow["tunnus"]}'";
    $res = pupe_query($query);

    while ($rivirow = mysql_fetch_array($res)) {
      if ($hard_delete) {
        //Poistetaan tapahtumat
        $query = "DELETE from tapahtuma
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND rivitunnus = '{$rivirow["tunnus"]}'";
      }
      else {
        $query = "UPDATE tapahtuma SET
                  yhtio = 'ddddd'
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND rivitunnus = '{$rivirow["tunnus"]}'";
      }

      pupe_query($query);

      // Päivitetään saldo tilausrivin osoittamalla paikalla
      $query = "UPDATE tuotepaikat SET
                saldo = saldo + {$rivirow["kpl"]}
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tuoteno = '{$rivirow["tuoteno"]}'
                AND hyllyalue = '{$rivirow["hyllyalue"]}'
                AND hyllynro = '{$rivirow["hyllynro"]}'
                AND hyllytaso = '{$rivirow["hyllytaso"]}'
                AND hyllyvali = '{$rivirow["hyllyvali"]}'";
      pupe_query($query);

      if (mysql_affected_rows() == 0) {
        // Päivitetään saldo tuotteen oletuspaikalla jos tilausrivillä ei ollut sopivaa paikkaa
        $query = "UPDATE tuotepaikat
                  SET saldo = saldo + {$rivirow["kpl"]}
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND tuoteno	= '{$rivirow["tuoteno"]}'
                  AND oletus != ''
                  LIMIT 1";
        pupe_query($query);
      }
    }

    if ($hard_delete) {
      // Poistetaan tilausrivit
      $query = "DELETE FROM tilausrivi
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND otunnus = '{$laskurow["tunnus"]}'";
      pupe_query($query);
    }
    else {
      // Siirretään tilausrivit ddddd-yhtiölle
      $query = "UPDATE tilausrivi SET
                yhtio = 'ddddd'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND otunnus = '{$laskurow["tunnus"]}'";
      pupe_query($query);
    }

    return true;
  }
}


if (!function_exists("poista_laskutus")) {
  function poista_laskutus($tunnus, $hard_delete = false) {
    global $kukarow, $yhtiorow;

    // Löytyykö laskua
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus = '{$tunnus}'
              AND tila = 'U'
              AND alatila = 'X'
              LIMIT 1";
    $ulaskures = pupe_query($query);

    $ulaskurow = mysql_fetch_assoc($ulaskures);

    if ($hard_delete) {
      // Poistetaan tiliöinnit
      $query = "DELETE FROM tiliointi
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND ltunnus = '{$ulaskurow["tunnus"]}'";
    }
    else {
      // Siirretään tiliöinnit ddddd-yhtiölle
      $query = "UPDATE tiliointi SET
                yhtio = 'ddddd'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND ltunnus = '{$ulaskurow["tunnus"]}'";
    }

    pupe_query($query);

    if ($hard_delete) {
      // Deletoidaan lasku
      $query = "DELETE FROM lasku
                WHERE yhtio	= '{$kukarow["yhtio"]}'
                AND tila = 'U'
                AND alatila = 'X'
                AND tunnus = '{$ulaskurow["tunnus"]}'";
    }
    else {
      // Siirretään lasku ddddd-yhtiölle
      $query = "UPDATE lasku SET
                yhtio = 'ddddd'
                WHERE yhtio	= '{$kukarow["yhtio"]}'
                AND tila = 'U'
                AND alatila = 'X'
                AND tunnus = '{$ulaskurow["tunnus"]}'";
    }

    pupe_query($query);

    // Onko maksupositioita
    if ($ulaskurow["jaksotettu"] != 0) {
      $query = "SELECT uusiotunnus
                FROM maksupositio
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND otunnus = '{$ulaskurow["jaksotettu"]}'";
      $maksupositiores = pupe_query($query);

      // Poistetaan maksuposition kautta liitetyt tilaukset ja laskut
      while ($maksupositiorow = mysql_fetch_assoc($maksupositiores)) {
        $query = "SELECT laskunro
                  FROM lasku
                  WHERE tunnus = '{$maksupositiorow["uusiotunnus"]}'
                  LIMIT 1";
        $maksupositiolaskuresult = pupe_query($query);

        $maksupositiolaskurow = mysql_fetch_assoc($maksupositiolaskuresult);

        if ($maksupositiolaskurow["laskunro"] == 0) {
          poista_tilaus($maksupositiorow["uusiotunnus"], $hard_delete);
        }
        else {
          poista_tilaus($maksupositiorow["uusiotunnus"], $hard_delete);

          $query = "SELECT tunnus
                    FROM lasku
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND laskunro = '{$maksupositiolaskurow["laskunro"]}'
                    AND tila = 'U'
                    AND alatila = 'X'
                    LIMIT 1";
          $maksupositioulaskuresult = pupe_query($query);

          if (mysql_num_rows($maksupositioulaskuresult) == 0) continue;

          $maksupositioulaskurow = mysql_fetch_assoc($maksupositioulaskuresult);

          poista_laskutus($maksupositioulaskurow["tunnus"], $hard_delete);
        }
      }
    }

    return true;
  }
}
