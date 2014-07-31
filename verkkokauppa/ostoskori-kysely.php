<?php

// online kysely.. näillä infoilla pitäs onnistua
if ($_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "" and $_GET["ostoskori"] != "") {

  require "connect.inc";

  $ostoskori_user      = mysql_real_escape_string($_GET["user"]);
  $ostoskori_pass      = mysql_real_escape_string($_GET["pass"]);
  $ostoskori_yhtio    = mysql_real_escape_string($_GET["yhtio"]);
  $ostoskori_ostoskori  = mysql_real_escape_string($_GET["ostoskori"]);

  // katotaan löytyykö asiakas
  $query = "SELECT oletus_asiakas
            FROM kuka
            WHERE yhtio         = '$ostoskori_yhtio'
            AND kuka            = '$ostoskori_user'
            AND salasana        = md5('$ostoskori_pass')
            AND extranet       != ''
            AND oletus_asiakas != ''";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) == 1) {

    $kukarivi = mysql_fetch_array($result);

    // asiakas löytyi, katotaan löytyykö sille ostoskoria $ostoskori
    $query = "SELECT tilausrivi.*
              FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
              JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'B')
              WHERE lasku.yhtio      = '$ostoskori_yhtio'
              AND lasku.tila         = 'B'
              AND lasku.liitostunnus = '$kukarivi[oletus_asiakas]'
              AND lasku.alatila      = '$ostoskori_ostoskori'";
    $result = mysql_query($query) or pupe_error($query);

    while ($rivit = mysql_fetch_array($result)) {
      echo sprintf("%-20.20s", $rivit['tuoteno']);
      echo sprintf("%-10.10s", $rivit['varattu']);
      echo sprintf("%-15.15s", $rivit['hinta']);
      echo sprintf("%-35.35s", $rivit['nimitys']);
      echo "\n";

      //  Poistetaan rivi
      $query = "DELETE FROM tilausrivi
                WHERE yhtio = '$ostoskori_yhtio'
                AND tyyppi  = 'B'
                AND tunnus  = '$rivit[tunnus]'";
      $delres = mysql_query($query) or pupe_error($query);
    }
  }
}
