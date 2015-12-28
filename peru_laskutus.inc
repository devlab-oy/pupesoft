<?php

if (!function_exists("peru_laskutus")) {
  function peru_laskutus($laskunro) {
    // Löytyykö U-laskua
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND laskunro >= '{$laskunro}'
              AND laskunro <= '{$laskunro}'
              AND tila = 'U'
              AND alatila = 'X'";
    $ulaskures = mysql_query($query) or pupe_error($query);

    while ($ulaskurow = mysql_fetch_array($ulaskures)) {
      //Poistetaan tiliöinnit
      $query = "DELETE FROM tiliointi
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND ltunnus = '{$ulaskurow["tunnus"]}'";
      $korjres = mysql_query($query) or pupe_error($query);

      //Deletoidaan U-lasku
      $query = "DELETE FROM lasku
                WHERE yhtio	= '{$kukarow["yhtio"]}'
                AND tila = 'U'
                AND alatila = 'X'
                AND tunnus = '{$ulaskurow["tunnus"]}'";
      $korjres = mysql_query($query) or pupe_error($query);

      // Löytyykö L-laskua
      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tila = 'L'
                AND alatila = 'X'
                AND laskunro = '{$ulaskurow["laskunro"]}'";
      $llaskures = mysql_query($query) or pupe_error($query);

      while ($laskurow = mysql_fetch_array($llaskures)) {
        if ($laskurow["vienti"] != "") {
          $alatila = "E";
        }
        else {
          $alatila = "D";
        }

        //Päivitetään tilausotsikko takaisin tilaan toimitettu/vientitiedot syötetty
        $query = "UPDATE lasku SET
                  alatila	= '{$alatila}',
                  laskuttaja = '',
                  laskutettu = '0000-00-00 00:00:00',
                  tapvm = '0000-00-00',
                  erpcm = '0000-00-00',
                  laskunro = 0,
                  summa = 0,
                  kate = 0,
                  arvo = 0,
                  viite = '',
                  factoringsiirtonumero = 0
                  WHERE yhtio	= '{$kukarow["yhtio"]}'
                  AND tila = 'L'
                  AND alatila	= 'X'
                  AND tunnus = '{$laskurow["tunnus"]}'";
        $korjres = mysql_query($query) or pupe_error($query);

        //Hoidetaan tapahtumat ja saldot
        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio	= '{$kukarow["yhtio"]}'
                  AND otunnus	= '{$laskurow["tunnus"]}'";
        $res = mysql_query($query) or pupe_error($query);

        while ($rivirow = mysql_fetch_array($res)) {
          //Poistetaan tapahtmat
          $query = "DELETE from tapahtuma
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND rivitunnus = '{$rivirow["tunnus"]}'";
          $korjres = mysql_query($query) or pupe_error($query);

          ///* Päivitetään saldo tilausrivin osoittamalla paikalla *///
          $query = "UPDATE tuotepaikat SET
                    saldo = saldo + {$rivirow["kpl"]}
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tuoteno = '{$rivirow["tuoteno"]}'
                    AND hyllyalue = '{$rivirow["hyllyalue"]}'
                    AND hyllynro = '{$rivirow["hyllynro"]}'
                    AND hyllytaso = '{$rivirow["hyllytaso"]}'
                    AND hyllyvali = '{$rivirow["hyllyvali"]}'";
          $korjres = mysql_query($query) or pupe_error($query);

          if (mysql_affected_rows() == 0) {
            ///* Päivitetään saldo tuotteen oletuspaikalla jos tilausrivillä ei ollut sopivaa paikkaa *///
            $query = "UPDATE tuotepaikat
                      SET saldo = saldo + {$rivirow["kpl"]}
                      WHERE yhtio = '{$kukarow["yhtio"]}'
                      AND tuoteno	= '{$rivirow["tuoteno"]}'
                      AND oletus != ''
                      LIMIT 1";
            $korjres = mysql_query($query) or pupe_error($query);
          }
        }

        //Päivitetään tilausrivit
        $query = "UPDATE tilausrivi SET
                  varattu        = kpl,
                  kpl   	       = 0,
                  laskutettu     = '',
                  laskutettuaika = '0000-00-00',
                  uusiotunnus	   = 0,
                  rivihinta			 = 0,
                  kate				   = 0
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND otunnus = '{$laskurow["tunnus"]}'";
        $korjres = mysql_query($query) or pupe_error($query);

        $query = "UPDATE tilausrivi SET
                  kommentti = ''
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND otunnus = '{$laskurow["tunnus"]}'
                  AND kommentti LIKE 'Tilaus%'";
        $korjres = mysql_query($query) or pupe_error($query);

        $query = "DELETE FROM tilausrivi
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND otunnus = '{$laskurow["tunnus"]}'
                  AND tuoteno = '{$yhtiorow["rahti_tuotenumero"]}'";
        $korjres = mysql_query($query) or pupe_error($query);
      }
    }
  }
}
