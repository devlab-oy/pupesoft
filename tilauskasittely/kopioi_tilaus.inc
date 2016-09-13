<?php

if (!function_exists("kopioi_tilaus")) {

  // otetaan parametriksi tunnus
  function kopioi_tilaus($tunnus, $muokkaa_lasku = "", $muokkaa_rivi = "", $tapahtumapvm = "") {

    // tarvitaan yhtiörowta ja kukarowta
    global $yhtiorow, $kukarow;

    $query = "SELECT *
              FROM lasku
              WHERE tunnus = '$tunnus'
              AND yhtio    = '{$kukarow["yhtio"]}'";
    $monistares = pupe_query($query);

    $query = "SELECT *
              FROM laskun_lisatiedot
              WHERE otunnus = '$tunnus'
              AND yhtio     = '{$kukarow["yhtio"]}'";
    $lisatiedotres = pupe_query($query);

    // Katsotaan, että tilausrivi on voimassa $tapahtumapvm == sopimuksen tämä laskutuspäivä
    if ($tapahtumapvm != "") {
      // tilausrivi.kerayspvm => poikkeava alkupäivä on syötetty ja se on pienempi kuin tämä laskutuspäivä
      // tilausrivi.toimaika => poikkeava loppupäivä on syötetty ja se suurempi kuin tämä laskutuspäivä
      $lisaehto = "  AND (tilausrivi.kerayspvm = '0000-00-00' or (tilausrivi.kerayspvm != '0000-00-00' and tilausrivi.kerayspvm <= '$tapahtumapvm'))
              AND (tilausrivi.toimaika = '0000-00-00' or (tilausrivi.toimaika != '0000-00-00' and tilausrivi.toimaika >= '$tapahtumapvm'))";
    }
    else {
      $lisaehto = "";
    }

    $query = "SELECT *
              FROM tilausrivi
              WHERE otunnus = '$tunnus'
              AND yhtio     = '{$kukarow["yhtio"]}'
              $lisaehto";
    $rivires = pupe_query($query);

    if (mysql_num_rows($monistares) == 0) {
      echo "lasku not found";
      return FALSE;
    }

    if (mysql_num_rows($rivires) == 0) {
      echo "tilausrivi not found";
      return FALSE;
    }

    $monistarow = mysql_fetch_array($monistares);

    // haetaan maksuehdon tiedot tarkastuksia varten
    $apuqu = "SELECT erapvmkasin
              from maksuehto
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '$monistarow[maksuehto]'";
    $meapu = pupe_query($apuqu);
    $meapurow = mysql_fetch_assoc($meapu);

    $fields = mysql_field_name($monistares, 0);
    $values = "'".$monistarow[0]."'";

    // monistetaan kaikki paitsi tunnus
    for ($i = 1; $i < mysql_num_fields($monistares)-1; $i++) {
      $field = mysql_field_name($monistares, $i);
      $fields .= ", $field";

      //  Muokkaukset
      if (isset($muokkaa_lasku[$field]) and is_array($muokkaa_lasku[$field])) {
        if ($muokkaa_lasku[$field]["from"] != "" and $muokkaa_lasku[$field]["to"] != "") {
          $monistarow[$i] = preg_replace($muokkaa_lasku[$field]["from"], $muokkaa_lasku[$field]["to"], $monistarow[$i]);
        }
      }

      if ($field == "kate_korjattu" or
        $field == "lahetetty_ulkoiseen_varastoon") {
        $values .= ", NULL";
      }
      elseif ($field == "erpcm" and $meapurow["erapvmkasin"] != '') {
        $pp = (int) substr($monistarow[$i], 8, 2);
        $kk = (int) substr($monistarow[$i], 5, 2);
        $vv = (int) substr($monistarow[$i], 0, 4);

        // Jos on syötetty eräpäivämäärästä vain päivä, taiotaan siitä kokonainen päivämäärä
        if ($pp > 0 and $kk == 0 and $vv == 0) {
          // Jos syötetty päivä on kuluvalla kuukaudella vielä tulevaisuudessa, tehdään päivämäärä samalle kuukaudelle
          if ($pp > date("d")) {
            $uusi_erapaivamaara = date("Y-m-{$pp}");
          }
          else {
            // Muutoin päivämäärä tehdään seuraavalle kuukaudelle
            $uusi_erapaivamaara = date("Y-m-{$pp}", strtotime('+1 month'));
          }

          $values .= ", '$uusi_erapaivamaara'";
        }
        else {
          $values .= ", '$monistarow[$i]'";
        }
      }
      else {
        $values .= ", '$monistarow[$i]'";
      }
    }

    $kysely  = "INSERT into lasku ($fields) VALUES ($values)";
    $insres  = pupe_query($kysely);
    $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

    // laskun_lisatiedot
    if (mysql_num_rows($lisatiedotres) > 0) {
      $lisatiedotrow = mysql_fetch_array($lisatiedotres);

      $fields = mysql_field_name($lisatiedotres, 0);
      $values = "'".$lisatiedotrow[0]."'";

      // monistetaan kaikki paitsi tunnus
      for ($i = 1; $i < mysql_num_fields($lisatiedotres) - 1; $i++) {
        $field = mysql_field_name($lisatiedotres, $i);
        $fields .= ", $field";

        if ($field == 'otunnus') {
          $values .= ", '$utunnus'";
        }
        else {
          $values .= ", '$lisatiedotrow[$i]'";
        }
      }
    }

    $query = "INSERT INTO laskun_lisatiedot ($fields) VALUES ($values)";
    $insres = pupe_query($query);

    while ($rivirow = mysql_fetch_array($rivires)) {

      $rfields = mysql_field_name($rivires, 0);
      $rvalues = "'$monistarow[0]'";

      for ($i = 1; $i < mysql_num_fields($rivires)-1; $i++) {

        $field = mysql_field_name($rivires, $i);

        if (mysql_field_name($rivires, $i) == "otunnus") {
          $rfields .= ", $field";
          $rvalues .= ", '$utunnus'";
        }
        elseif (mysql_field_name($rivires, $i) == "kate_korjattu") {
          $rfields .= ", $field";
          $rvalues .= ", NULL";
        }
        else {

          //  Muokkaukset
          if (isset($muokkaa_rivi[$field]) and is_array($muokkaa_rivi[$field])) {
            if ($muokkaa_rivi[$field]["from"] != "" and $muokkaa_rivi[$field]["to"] != "") {
              $rivirow[$i] = preg_replace($muokkaa_rivi[$field]["from"], $muokkaa_rivi[$field]["to"], $rivirow[$i]);
            }
          }

          $rfields .= ", $field";
          $rvalues .= ", '$rivirow[$i]'";
        }
      }

      $kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
      $insres = pupe_query($kysely);
      $insid  = mysql_insert_id($GLOBALS["masterlink"]);

      // Kopioidaan tilausrivin lisatiedot
      $query = "SELECT *
                FROM tilausrivin_lisatiedot
                WHERE tilausrivitunnus = '$rivirow[tunnus]' and yhtio = '$kukarow[yhtio]'";
      $monistares2 = pupe_query($query);

      if (mysql_num_rows($monistares2) > 0) {
        $monistarow2 = mysql_fetch_array($monistares2);

        $kysely = "  INSERT INTO tilausrivin_lisatiedot SET ";

        for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
          switch (mysql_field_name($monistares2, $i)) {

          case 'tilausrivitunnus':
            $kysely .= mysql_field_name($monistares2, $i)."='$insid',";
          case 'tiliointirivitunnus':
          case 'tilausrivilinkki':
            $kysely .= "";
            break;
          default:
            $kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";
          }
        }

        $kysely  = substr($kysely, 0, -1);
        pupe_query($kysely);
      }
    }

    //Korjataan perheid:t uusilla riveillä
    $query = "SELECT perheid, min(tunnus) uusiperheid
              FROM tilausrivi
              WHERE yhtio  = '{$kukarow["yhtio"]}'
              AND otunnus  = '$utunnus'
              AND perheid != 0
              GROUP by perheid";
    $copresult = pupe_query($query);

    while ($coprivirow = mysql_fetch_array($copresult)) {
      $query = "UPDATE tilausrivi
                SET perheid = '{$coprivirow["uusiperheid"]}'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND otunnus = '$utunnus'
                AND perheid = '{$coprivirow["perheid"]}'";
      $cores = pupe_query($query);
    }

    //Korjataan perheid2:t uusilla riveillä
    $query = "SELECT perheid2, min(tunnus) uusiperheid2
              FROM tilausrivi
              WHERE yhtio   = '{$kukarow["yhtio"]}'
              AND otunnus   = '$utunnus'
              AND perheid2 != 0
              GROUP by perheid2";
    $copresult = pupe_query($query);

    while ($coprivirow = mysql_fetch_array($copresult)) {
      $query = "UPDATE tilausrivi
                SET perheid2 = '{$coprivirow["uusiperheid2"]}'
                WHERE yhtio  = '{$kukarow["yhtio"]}'
                AND otunnus  = '$utunnus'
                AND perheid2 = '{$coprivirow["perheid2"]}'";
      $cores = pupe_query($query);
    }

    return $utunnus; // palautetaan uusi lasku.tunnus
  }
}
