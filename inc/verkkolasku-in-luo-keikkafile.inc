<?php

// debuggia (1)
$debug = 0;

if (!function_exists("teccom_kaytettavat_tilausrivit")) {
  function teccom_kaytettavat_tilausrivit($checkres, $lajilisa, $debug = 0) {

    global $kukarow;

    if ($debug == 1) {
      echo "<br>teccom_kaytettavat_tilausrivit<br>";
    }

    $kaytettavat_tilausrivit = $skipatut_tilausrivit = array();

    if (!is_resource($checkres) or mysql_num_rows($checkres) == 0) {
      return array($kaytettavat_tilausrivit, $skipatut_tilausrivit);
    }

    while ($tilausrivirow = mysql_fetch_assoc($checkres)) {

      if ($tilausrivirow['asn_sanomat'] == 'K') {
        // tilausrivi pit‰‰ olla jo kohdistettu
        $query = "SELECT tunnus
                  FROM asn_sanomat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = 'asn'
                  AND MATCH (tilausrivi) AGAINST ('{$tilausrivirow['tunnus']}' IN BOOLEAN MODE)
                  AND status  = 'X'";
        $chk_res = pupe_query($query);

        // tilausrivi ei saa olla jo kohdistettu
        $query = "SELECT tunnus
                  FROM asn_sanomat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = '{$lajilisa}'
                  AND status  = ''
                  AND MATCH (tilausrivi) AGAINST ('{$tilausrivirow['tunnus']}' IN BOOLEAN MODE)";
        $chk_res2 = pupe_query($query);

        if (mysql_num_rows($chk_res) != 0 and mysql_num_rows($chk_res2) == 0) {
          $kaytettavat_tilausrivit[] = $tilausrivirow['tunnus'];
        }
        else {
          // voi olla, ett‰ tuoteperheen lapsi ei ole lis‰tty ASN-tauluun
          // TƒMƒ ON PUUKOTUS
          if ($tilausrivirow['perheid'] != 0 and $tilausrivirow['perheid'] != $tilausrivirow['tunnus']) {

            // is‰ pit‰‰ olla jo kohdistettu
            $query = "SELECT tunnus
                      FROM asn_sanomat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND laji    = 'asn'
                      AND MATCH (tilausrivi) AGAINST ('{$tilausrivirow['perheid']}' IN BOOLEAN MODE)
                      AND status  = 'X'";
            $chk_res3 = pupe_query($query);

            if (mysql_num_rows($chk_res3) != 0) {
              $kaytettavat_tilausrivit[] = $tilausrivirow['tunnus'];
            }
            elseif (mysql_num_rows($chk_res) == 0 and mysql_num_rows($chk_res2) == 0) {
              $skipatut_tilausrivit[$tilausrivirow['tunnus']] = $tilausrivirow['kpl'] + $tilausrivirow['varattu'];
            }
          }
          elseif (mysql_num_rows($chk_res) == 0 and mysql_num_rows($chk_res2) == 0) {
            $skipatut_tilausrivit[$tilausrivirow['tunnus']] = $tilausrivirow['kpl'] + $tilausrivirow['varattu'];
          }
        }
      }
      else {
        // tilausrivi ei saa olla jo kohdistettu
        $query = "SELECT tunnus
                  FROM asn_sanomat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = '{$lajilisa}'
                  AND status  = 'X'
                  AND MATCH (tilausrivi) AGAINST ('{$tilausrivirow['tunnus']}' IN BOOLEAN MODE)";
        $chk_res = pupe_query($query);

        if (mysql_num_rows($chk_res) == 0) {
          $kaytettavat_tilausrivit[] = $tilausrivirow['tunnus'];
        }
        else {
          $skipatut_tilausrivit[$tilausrivirow['tunnus']] = $tilausrivirow['kpl'] + $tilausrivirow['varattu'];
        }
      }
    }

    return array($kaytettavat_tilausrivit, $skipatut_tilausrivit);
  }
}

if (!function_exists('etsi_sopivat_tilausrivit')) {
  function etsi_sopivat_tilausrivit($data, $lajilisa, $trow, $isalisa = "", $debug = 0, $_yhtion_toimipaikka = 0) {
    global $kukarow;

    if ($debug == 1) {
      echo "<br>etsi_sopivat_tilausrivit<br>";
    }

    $kaytettavat_tilausrivit = $skipatut_tilausrivit = array();

    // Jos tuoteno on tyhj‰ niin unohdetaan koko juttu
    if (trim($data['tuoteno']) == "") {
      return $kaytettavat_tilausrivit;
    }
    $tuoteno_ilman_valeja =str_replace(' ', '', $data['tuoteno']);
    $toim_tuoteno_lisa = "AND (tuotteen_toimittajat.toim_tuoteno = '{$data['tuoteno']}' or tuotteen_toimittajat.toim_tuoteno = '{$tuoteno_ilman_valeja}') ";
    // tiukka 1
    $query = "(SELECT tilausrivi.*, toimi.asn_sanomat, '1' AS orderi
               FROM tilausrivi
               JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                       AND lasku.tunnus                               = tilausrivi.otunnus
                       AND lasku.mapvm                                = '0000-00-00'
                       AND lasku.tapvm                                = '0000-00-00'
                       AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
               JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
                       AND lasku2.tunnus                              = tilausrivi.uusiotunnus
                       AND lasku2.mapvm                               = '0000-00-00'
                       AND lasku2.tapvm                               = '0000-00-00'
                       AND lasku2.yhtio_toimipaikka                   = '{$_yhtion_toimipaikka}')
               JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                       AND toimi.tunnus                               = lasku.liitostunnus
                       AND toimi.tunnus                               = '{$trow['tunnus']}'
                       AND toimi.tyyppi                              != 'P'
                       AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
               JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                               AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                               AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                               {$toim_tuoteno_lisa})
               JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
               WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
               AND tilausrivi.tyyppi                                  = 'O'
               AND tilausrivi.otunnus                                 LIKE '%{$data['ostotilausnro']}'
               AND tilausrivi.tilaajanrivinro                         = '{$data['tilaajanrivinro']}'
               AND tilausrivi.uusiotunnus                            != 0
               AND tilausrivi.kpl+tilausrivi.varattu != 0
               {$isalisa})
               UNION
               (SELECT tilausrivi.*, toimi.asn_sanomat, '2' AS orderi
               FROM tilausrivi
               JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                       AND lasku.tunnus                               = tilausrivi.otunnus
                       AND lasku.tila                                 = 'O'
                       AND lasku.alatila                              = 'A'
                       AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
               JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                       AND toimi.tunnus                               = lasku.liitostunnus
                       AND toimi.tunnus                               = '{$trow['tunnus']}'
                       AND toimi.tyyppi                              != 'P'
                       AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
               JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                               AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                               AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                               {$toim_tuoteno_lisa})
               JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
               WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
               AND tilausrivi.tyyppi                                  = 'O'
               AND tilausrivi.otunnus                                 LIKE '%{$data['ostotilausnro']}'
               AND tilausrivi.tilaajanrivinro                         = '{$data['tilaajanrivinro']}'
               AND tilausrivi.uusiotunnus                             = 0
               AND tilausrivi.kpl+tilausrivi.varattu != 0
               {$isalisa})
               ORDER BY orderi ASC, laadittu ASC";
    $kokeillaan = pupe_query($query);

    if (mysql_num_rows($kokeillaan) > 0) {

      if ($debug == 1) {
        echo "<pre>", str_replace("\t", "", $query), "</pre>";
      }

      list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = teccom_kaytettavat_tilausrivit($kokeillaan, $lajilisa, $debug);
    }

    if (count($kaytettavat_tilausrivit) == 0) {
      // tiukka 2
      $query = "(SELECT tilausrivi.*, toimi.asn_sanomat, '1' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.uusiotunnus
                         AND lasku.mapvm                                = '0000-00-00'
                         AND lasku.tapvm                                = '0000-00-00'
                         AND lasku.yhtio_toimipaikka                    = '{$_yhtion_toimipaikka}')
                 JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
                         AND lasku2.tunnus                              = tilausrivi.otunnus
                         AND lasku2.comments                            LIKE '%{$data['ostotilausnro']}%'
                         AND lasku2.mapvm                               = '0000-00-00'
                         AND lasku2.tapvm                               = '0000-00-00'
                         AND lasku2.vanhatunnus                         = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.uusiotunnus                            != 0
                 AND tilausrivi.tilaajanrivinro                         = '{$data['tilaajanrivinro']}'
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 UNION
                 (SELECT tilausrivi.*, toimi.asn_sanomat, '2' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.tila                                 = 'O'
                         AND lasku.alatila                              = 'A'
                         AND lasku.comments                             LIKE '%{$data['ostotilausnro']}%'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.tilaajanrivinro                         = '{$data['tilaajanrivinro']}'
                 AND tilausrivi.uusiotunnus                             = 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 ORDER BY orderi ASC, laadittu ASC";
      $kokeillaan = pupe_query($query);

      if (mysql_num_rows($kokeillaan) > 0) {

        if ($debug == 1) {
          echo "<pre>", str_replace("\t", "", $query), "</pre>";
        }

        list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = teccom_kaytettavat_tilausrivit($kokeillaan, $lajilisa, $debug);
      }
    }

    if (count($kaytettavat_tilausrivit) == 0) {
      // v‰ljempi 1
      $query = "(SELECT tilausrivi.*, toimi.asn_sanomat, '1' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.mapvm                                = '0000-00-00'
                         AND lasku.tapvm                                = '0000-00-00'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
                 JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
                         AND lasku2.tunnus                              = tilausrivi.uusiotunnus
                         AND lasku2.mapvm                               = '0000-00-00'
                         AND lasku2.tapvm                               = '0000-00-00'
                         AND lasku2.yhtio_toimipaikka                   = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.otunnus                                 LIKE '%{$data['ostotilausnro']}'
                 AND tilausrivi.uusiotunnus                            != 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 UNION
                 (SELECT tilausrivi.*, toimi.asn_sanomat, '2' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.tila                                 = 'O'
                         AND lasku.alatila                              = 'A'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.otunnus                                 LIKE '%{$data['ostotilausnro']}'
                 AND tilausrivi.uusiotunnus                             = 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 ORDER BY orderi ASC, laadittu ASC";
      $kokeillaan = pupe_query($query);

      if (mysql_num_rows($kokeillaan) > 0) {

        if ($debug == 1) {
          echo "<pre>", str_replace("\t", "", $query), "</pre>";
        }

        list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = teccom_kaytettavat_tilausrivit($kokeillaan, $lajilisa, $debug);
      }
    }

    if (count($kaytettavat_tilausrivit) == 0) {
      // v‰ljempi 2
      $query = "(SELECT tilausrivi.*, toimi.asn_sanomat, '1' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.uusiotunnus
                         AND lasku.mapvm                                = '0000-00-00'
                         AND lasku.tapvm                                = '0000-00-00'
                         AND lasku.yhtio_toimipaikka                    = '{$_yhtion_toimipaikka}')
                 JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
                         AND lasku2.tunnus                              = tilausrivi.otunnus
                         AND lasku2.comments                            LIKE '%{$data['ostotilausnro']}%'
                         AND lasku2.mapvm                               = '0000-00-00'
                         AND lasku2.tapvm                               = '0000-00-00'
                         AND lasku2.vanhatunnus                         = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.uusiotunnus                            != 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 UNION
                 (SELECT tilausrivi.*, toimi.asn_sanomat, '2' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.tila                                 = 'O'
                         AND lasku.alatila                              = 'A'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}'
                         AND lasku.comments                             LIKE '%{$data['ostotilausnro']}%')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.uusiotunnus                             = 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 ORDER BY orderi ASC, laadittu ASC";
      $kokeillaan = pupe_query($query);

      if (mysql_num_rows($kokeillaan) > 0) {

        if ($debug == 1) {
          echo "<pre>", str_replace("\t", "", $query), "</pre>";
        }

        list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = teccom_kaytettavat_tilausrivit($kokeillaan, $lajilisa, $debug);
      }
    }

    if (count($kaytettavat_tilausrivit) == 0) {
      // lˆys‰
      $query = "(SELECT tilausrivi.*, toimi.asn_sanomat, '1' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.mapvm                                = '0000-00-00'
                         AND lasku.tapvm                                = '0000-00-00'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
                 JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
                         AND lasku2.tunnus                              = tilausrivi.uusiotunnus
                         AND lasku2.mapvm                               = '0000-00-00'
                         AND lasku2.tapvm                               = '0000-00-00'
                         AND lasku2.yhtio_toimipaikka                   = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.uusiotunnus                            != 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 UNION
                 (SELECT tilausrivi.*, toimi.asn_sanomat, '2' AS orderi
                 FROM tilausrivi
                 JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                         AND lasku.tunnus                               = tilausrivi.otunnus
                         AND lasku.tila                                 = 'O'
                         AND lasku.alatila                              = 'A'
                         AND lasku.vanhatunnus                          = '{$_yhtion_toimipaikka}')
                 JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                         AND toimi.tunnus                               = lasku.liitostunnus
                         AND toimi.tunnus                               = '{$trow['tunnus']}'
                         AND toimi.tyyppi                              != 'P'
                         AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                 JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                 AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                 AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                 {$toim_tuoteno_lisa})
                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                 WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                 AND tilausrivi.tyyppi                                  = 'O'
                 AND tilausrivi.uusiotunnus                             = 0
                 AND tilausrivi.kpl+tilausrivi.varattu != 0
                 {$isalisa})
                 ORDER BY orderi ASC, laadittu ASC";
      $kokeillaan = pupe_query($query);

      if (mysql_num_rows($kokeillaan) > 0) {

        if ($debug == 1) {
          echo "<pre>", str_replace("\t", "", $query), "</pre>";
        }

        list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = teccom_kaytettavat_tilausrivit($kokeillaan, $lajilisa, $debug);
      }
    }

    return array($kaytettavat_tilausrivit, $skipatut_tilausrivit);
  }
}

if (!function_exists('tuoteperhe_check')) {
  function tuoteperhe_check($tunnus, $debug = 0) {
    global $kukarow;

    if ($debug == 1) {
      echo "<br>tuoteperhe_check<br>";
    }

    // tehd‰‰n tuoteperhe-satula-tapaus-checkki
    $lapsiperhesql = "SELECT
                      tuoteperhe.isatuoteno,
                      tuoteperhe.tuoteno,
                      tuote.tuoteno, tuote.try,tuote.osasto,
                      tuote.nimitys,tuote.yksikko,tuote.myyntihinta,
                      tilausrivi.yhtio, tilausrivi.tyyppi,tilausrivi.toimaika, tilausrivi.kerayspvm,
                      tilausrivi.otunnus,tilausrivi.perheid,tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.kpl,
                      tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.uusiotunnus
                      FROM tilausrivi
                      JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuoteperhe.isatuoteno AND tuoteperhe.tyyppi in ('P','')  AND tuoteperhe.ohita_kerays !='')
                      JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuoteperhe.tuoteno)
                      WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivi.tunnus  = '{$tunnus}'
                      ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
    $lapsiresult = pupe_query($lapsiperhesql);

    if (mysql_num_rows($lapsiresult) == 0) return 0;

    $lapsitieto = mysql_fetch_assoc($lapsiresult);

    // hae tuotteen ostohinta
    $laskuselect = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$lapsitieto['otunnus']}'";
    $laskures  = pupe_query($laskuselect);
    $laskurow  = mysql_fetch_assoc($laskures);
    list($hinta, , $ale, ) = alehinta_osto($laskurow, $lapsitieto, $lapsitieto["tilkpl"]);

    $query = "SELECT tunnus
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND perheid = '{$lapsitieto['tunnus']}'
              AND tuoteno = '{$lapsitieto['tuoteno']}'
              AND kpl     = '{$lapsitieto['kpl']}'
              AND varattu = '{$lapsitieto['varattu']}'
              AND tyyppi  = 'O'";
    $loytyiko_lapsi_res = pupe_query($query);

    if ($debug == 1) {
      echo "loytyiko_lapsi query: $query<br><br>";
    }

    // Jos luodaan lapsirivi t‰ss‰ kohtaa, ja is‰ on jo tuloutettu, lapsi ei saa olla tuloutettu!!
    if (mysql_num_rows($loytyiko_lapsi_res) == 0) {
      if ($debug == 1) {
        echo "Lapsirivi‰ ei lˆytynyt! Perustetaan se.<br><br>";
      }

      $saldo_varattu = $lapsitieto['varattu'] + $lapsitieto['kpl'];

      $lisainsert = "INSERT INTO tilausrivi SET
                     yhtio       = '{$lapsitieto['yhtio']}',
                     tyyppi      = '{$lapsitieto['tyyppi']}',
                     toimaika    = '{$lapsitieto['toimaika']}',
                     kerayspvm   = '{$lapsitieto['kerayspvm']}',
                     otunnus     = '{$lapsitieto['otunnus']}',
                     tuoteno     = '{$lapsitieto['tuoteno']}',
                     try         = '{$lapsitieto['try']}',
                     osasto      = '{$lapsitieto['osasto']}',
                     nimitys     = '{$lapsitieto['nimitys']}',
                     yksikko     = '{$lapsitieto['yksikko']}',
                     tilkpl      = '{$lapsitieto['tilkpl']}',
                     varattu     = '{$saldo_varattu}',
                     kpl         = '0',
                     ale1        = '{$ale['ale1']}',
                     ale2        = '{$ale['ale2']}',
                     ale3        = '{$ale['ale3']}',
                     hinta       = '{$hinta}',
                     laatija     = 'lapset',
                     kommentti   = 'Lasku: TL:{$lapsitieto['tunnus']} tuotteelle: {$lapsitieto['isatuoteno']} lis‰t‰‰n lapsituote: {$lapsitieto['tuoteno']}',
                     laadittu    =  now(),
                     hyllyalue   = '{$lapsitieto['hyllyalue']}',
                     hyllynro    = '{$lapsitieto['hyllynro']}',
                     hyllytaso   = '{$lapsitieto['hyllytaso']}',
                     hyllyvali   = '{$lapsitieto['hyllyvali']}',
                     perheid     = '{$lapsitieto['tunnus']}',
                     uusiotunnus = '{$lapsitieto['uusiotunnus']}'";
      if ($debug == 1) {
        echo "<pre>", str_replace("\t", "", $lisainsert), "</pre>";
      }
      $inskres = pupe_query($lisainsert);
    }

    // p‰ivitet‰‰n is‰
    $updateisa = "UPDATE tilausrivi SET perheid = tunnus WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}'";
    $updateres = pupe_query($updateisa);
    if ($debug == 1) {
      echo "p‰ivitet‰‰n is‰: $updateisa<br>";
    }

    return $tunnus;
  }
}

if (!function_exists('ostoilla_vai_keikoilla')) {
  function ostoilla_vai_keikoilla($ostoilla, $keikoilla, $tunnus, $row, $data, $debug = 0) {
    global $kukarow;

    if ($debug == 1) {
      echo "<br>ostoilla_vai_keikoilla<br>";
    }

    if ($row['uusiotunnus'] == 0) {

      $indx = count($ostoilla);

      // lˆytyi, ei ole keikalla
      $ostoilla[$indx]["tunnus"] = $tunnus; // tilausrivi tunnus
      $ostoilla[$indx]["hinta"] = $data["hinta"];
      $ostoilla[$indx]["kpl"] = $data["kpl"];
      $ostoilla[$indx]["laskuntunnus"] = $data["ostotilausnro"]; // laskun tunnus
      $ostoilla[$indx]["tilaajanrivinro"] = $data["tilaajanrivinro"];
      $ostoilla[$indx]["insert_id"] = $data["insert_id"];
      $ostoilla[$indx]["lisakulu"] = isset($data["lisakulu"]) ? $data["lisakulu"] : 0;
      $ostoilla[$indx]["kulu"] = isset($data["kulu"]) ? $data["kulu"] : 0;
      $ostoilla[$indx]["ale1"] = isset($data["ale1"]) ? $data["ale1"] : 0;
      $ostoilla[$indx]["ale2"] = isset($data["ale2"]) ? $data["ale2"] : 0;
      $ostoilla[$indx]["ale3"] = isset($data["ale3"]) ? $data["ale3"] : 0;
      $ostoilla[$indx]['tuoteno'] = $row['tuoteno'];
    }
    else {

      $indx = count($keikoilla);

      // lˆytyi, on jo keikalla
      $keikoilla[$indx]["tunnus"] = $tunnus;
      $keikoilla[$indx]["uusiotunnus"] = $row['uusiotunnus'];
      $keikoilla[$indx]["hinta"] = $data["hinta"];
      $keikoilla[$indx]["kpl"] = $data["kpl"];
      $keikoilla[$indx]["tilaajanrivinro"] = $data["tilaajanrivinro"];
      $keikoilla[$indx]["insert_id"] = $data["insert_id"];
      $keikoilla[$indx]["lisakulu"] = isset($data["lisakulu"]) ? $data["lisakulu"] : 0;
      $keikoilla[$indx]["kulu"] = isset($data["kulu"]) ? $data["kulu"] : 0;
      $keikoilla[$indx]["ale1"] = isset($data["ale1"]) ? $data["ale1"] : 0;
      $keikoilla[$indx]["ale2"] = isset($data["ale2"]) ? $data["ale2"] : 0;
      $keikoilla[$indx]["ale3"] = isset($data["ale3"]) ? $data["ale3"] : 0;
      $keikoilla[$indx]['tuoteno'] = $row['tuoteno'];
    }

    return array($ostoilla, $keikoilla);
  }
}

if (!function_exists('kopioi_tapahtuma')) {
  function kopioi_tapahtuma($_arr, $kappaleerotus, $kpl, $debug = 0) {
    global $kukarow;

    if ($debug == 1) {
      echo "<br>kopioi_tapahtuma<br>";
    }

    foreach ($_arr as $_ktunnus => $_vid) {
      $query = "SELECT *
                FROM tapahtuma
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND laji       = 'tulo'
                AND rivitunnus = '{$_ktunnus}'";
      $tapahtuma_res = pupe_query($query);
      $tapahtuma_row = mysql_fetch_assoc($tapahtuma_res);

      // Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
      $fields = "yhtio";
      $values = "'{$kukarow['yhtio']}'";

      // Ei monisteta tunnusta
      for ($ii = 1; $ii < mysql_num_fields($tapahtuma_res) - 1; $ii++) {

        $fieldname = mysql_field_name($tapahtuma_res, $ii);

        $fields .= ", ".$fieldname;

        switch ($fieldname) {
        case 'rivitunnus':
          $values .= ", '{$_vid}'";
          break;
        case 'kpl':
          $values .= ", '{$kappaleerotus}'";
          break;
        default:
          $values .= ", '".$tapahtuma_row[$fieldname]."'";
        }
      }

      $kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
      $uusires = pupe_query($kysely);
      $tapahtuma_id = mysql_insert_id($GLOBALS["masterlink"]);

      $query = "UPDATE tapahtuma
                SET kpl = '{$kpl}'
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND laji       = 'tulo'
                AND rivitunnus = '{$_ktunnus}'";
      $upd_res = pupe_query($query);
    }
  }
}

if (!function_exists('paivita_ja_splittaa')) {
  function paivita_ja_splittaa($row, $datan_kpl, $kappaleerotus, $debug = 0) {
    global $kukarow;

    if ($debug == 1) {
      echo "<br>paivita_ja_splittaa<br>";
    }

    $poikkeukset = array(
      'tilausrivi.varattu' => ($row['kpl'] == 0 ? $kappaleerotus : 0),
      'tilausrivi.tilkpl' => $kappaleerotus,
      'tilausrivi.kpl' => ($row['kpl'] != 0 ? $kappaleerotus : 0),
      'tilausrivi.luontiaika' => $row['luontiaika'],
      'tilausrivi.hinta_alkuperainen' => round(($row['hinta_alkuperainen'] / ($row['kpl']+$row['varattu'])) * $datan_kpl, 6),
    );
    if ($debug == 1) {
      echo "splitattavalle poikkeukset: <br>";
      echo "<pre>", var_dump($row['tunnus'], $poikkeukset), "</pre>";
    }

    $tilausrivi_id = kopioi_tilausrivi($row['tunnus'], $poikkeukset);

    $splitlisa = $row['kpl'] > 0 ? " varattu = '0' " : " varattu = '{$datan_kpl}' ";
    $splitlisa2 = $row['kpl'] > 0 ? ", kpl = '{$datan_kpl}'" : ", kpl = 0 ";
    $rivihintalisa = $row['kpl'] > 0 ? ", rivihinta = '".(($row['rivihinta'] / $row['kpl']) * $datan_kpl)."'" : ", rivihinta = 0";

    $hinta_alkuperainenlisa = round(($row['hinta_alkuperainen'] / ($row['kpl']+$row['varattu'])) * $datan_kpl, 6);

    // P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
    $query = "UPDATE tilausrivi SET
              {$splitlisa}
              {$splitlisa2}
              {$rivihintalisa}
              , tilkpl = '{$datan_kpl}'
              , hinta_alkuperainen = '{$hinta_alkuperainenlisa}'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '{$row['tunnus']}'";
    if ($debug == 1) {
      echo "<pre>", str_replace("\t", "", $query), "</pre><br><br>";
    }
    $upres = pupe_query($query);
    if ($debug == 1) {
      echo "uusi tilausrivi id: $tilausrivi_id<br>";
    }

    // etsit‰‰n ja splitataan myˆs myyntitilausrivi ISƒ
    $query = "SELECT tilausrivi.tunnus
              FROM tilausrivi
              JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND tl.tilausrivilinkki > 0 AND tl.tilausrivilinkki = '{$row['tunnus']}')
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi  = 'L'";
    $myynti_rivi_res = pupe_query($query);

    $myynti_tilausrivi_id_isa = 0;

    if (mysql_num_rows($myynti_rivi_res) > 0) {
      $myynti_rivi_row_isa = mysql_fetch_assoc($myynti_rivi_res);

      $myynti_tilausrivi_id_isa = kopioi_tilausrivi($myynti_rivi_row_isa['tunnus'], $poikkeukset);

      $query = "UPDATE tilausrivin_lisatiedot SET
                tilausrivilinkki     = '{$tilausrivi_id}'
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = '{$myynti_tilausrivi_id_isa}'";
      $upres = pupe_query($query);

      $splitlisa = $row['kpl'] > 0 ? " varattu = '0' " : " varattu = '{$datan_kpl}' ";
      $splitlisa2 = $row['kpl'] > 0 ? ", kpl = '{$datan_kpl}'" : ", kpl = 0 ";
      $rivihintalisa = $row['kpl'] > 0 ? ", rivihinta = '".(($row['rivihinta'] / $row['kpl']) * $datan_kpl)."'" : ", rivihinta = 0";

      // P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
      $query = "UPDATE tilausrivi SET
                {$splitlisa}
                {$splitlisa2}
                {$rivihintalisa}
                , tilkpl = '{$datan_kpl}'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '{$myynti_rivi_row_isa['tunnus']}'";
      if ($debug == 1) {
        echo "P‰ivitet‰‰n myyntitilausrivi<br>", str_replace("\t", "", $query), "</pre><br>";
      }
      $upres = pupe_query($query);
    }

    // jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
    if ($row['kpl'] > 0) {

      $_arr = array(
        $row['tunnus'] => $tilausrivi_id,
      );

      if ($myynti_tilausrivi_id_isa != 0) $_arr[$myynti_rivi_row_isa['tunnus']] = $myynti_tilausrivi_id_isa;

      kopioi_tapahtuma($_arr, $kappaleerotus, $datan_kpl, $debug);
    }

    // katsotaan onko tuoteperheen is‰tuote
    if ($row['perheid'] == $row['tunnus']) {
      if ($debug == 1) {
        echo "$row[tuoteno] on tuoteperheen is‰tuote (perheid $row[perheid])<br><br>";
      }

      $query = "UPDATE tilausrivi SET
                perheid     = '{$tilausrivi_id}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tilausrivi_id}'";
      $upd_res = pupe_query($query);
      if ($debug == 1) {
        echo $query."<br>";
      }

      if ($myynti_tilausrivi_id_isa != 0) {
        $query = "UPDATE tilausrivi SET
                  perheid     = '{$myynti_tilausrivi_id_isa}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$myynti_tilausrivi_id_isa}'";
        $upd_res = pupe_query($query);
        if ($debug == 1) {
          echo "myynti_tilausrivi_id_isa update: $query<br>";
        }
      }

      // etsit‰‰n lapset ja splitataan ne is‰n mukaan
      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND perheid  = '{$row['perheid']}'
                AND tunnus  != '{$row['perheid']}'
                AND tyyppi   = 'O'";
      $lapsi_chk_res = pupe_query($query);
      if ($debug == 1) {
        echo "<br>Etsit‰‰n lapsia!<br>";
        echo "<br><pre>", str_replace("\t", "", $query), "</pre><br>";
      }

      if ($debug == 1) {
        while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {
          echo var_dump($lapsi_chk_row) . "<br>";
        }
        mysql_data_seek($lapsi_chk_res, 0);
      }

      while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {

        $poikkeukset = array(
          'tilausrivi.varattu' => ($row['kpl'] == 0 ? $kappaleerotus : 0),
          'tilausrivi.tilkpl' => $kappaleerotus,
          'tilausrivi.kpl' => ($row['kpl'] != 0 ? $kappaleerotus : 0),
          'tilausrivi.perheid' => $tilausrivi_id,
          'tilausrivi.luontiaika' => $lapsi_chk_row['luontiaika'],
        );
        if ($debug == 1) {
          echo "<br>splitattavalle lapselle poikkeukset: <br>";
          echo "<br><pre>", var_dump($lapsi_chk_row['tunnus'], $poikkeukset), "</pre><br>";
        }

        $lapsi_uusi_id = kopioi_tilausrivi($lapsi_chk_row['tunnus'], $poikkeukset);
        if ($debug == 1) {
          echo "uusi lapsen id: $lapsi_uusi_id<br>";
        }

        $splitlisa = $row['kpl'] > 0 ? " varattu = '0' " : " varattu = '{$datan_kpl}' ";
        $splitlisa2 = $row['kpl'] > 0 ? ", kpl = '{$datan_kpl}'" : ", kpl = 0 ";
        $rivihintalisa = $row['kpl'] > 0 ? ", rivihinta = '".(($row['rivihinta'] / $row['kpl']) * $datan_kpl)."'" : ", rivihinta = 0";

        // P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
        $query = "UPDATE tilausrivi SET
                  {$splitlisa}
                  {$splitlisa2}
                  {$rivihintalisa}
                  , tilkpl = '{$datan_kpl}'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '{$lapsi_chk_row['tunnus']}'";
        $upres = pupe_query($query);
        if ($debug == 1) {
          echo "P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰: $query<br>";
        }

        $myynti_tilausrivi_id_lapsi = 0;

        if ($myynti_tilausrivi_id_isa != 0) {
          // etsit‰‰n ja splitataan myˆs myyntitilausrivi LAPSI
          $query = "SELECT tilausrivi.tunnus
                    FROM tilausrivi
                    JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND tl.tilausrivilinkki > 0 AND tl.tilausrivilinkki = '{$lapsi_chk_row['tunnus']}')
                    WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                    AND tilausrivi.tyyppi  = 'L'";
          $myynti_rivi_res = pupe_query($query);
          $myynti_rivi_row = mysql_fetch_assoc($myynti_rivi_res);

          $poikkeukset['tilausrivi.perheid'] = $myynti_tilausrivi_id_isa;
          if ($debug == 1) {
            echo "<br>splitattavalle myyntilapselle poikkeukset: <br>";
            echo "<br><pre>", var_dump($myynti_rivi_row['tunnus'], $poikkeukset), "</pre><br><br>";
          }

          $myynti_tilausrivi_id_lapsi = kopioi_tilausrivi($myynti_rivi_row['tunnus'], $poikkeukset);

          $query = "UPDATE tilausrivin_lisatiedot SET
                    tilausrivilinkki     = '{$lapsi_uusi_id}'
                    WHERE yhtio          = '{$kukarow['yhtio']}'
                    AND tilausrivitunnus = '{$myynti_tilausrivi_id_lapsi}'";
          $upres = pupe_query($query);

          $splitlisa = $row['kpl'] > 0 ? " varattu = '0' " : " varattu = '{$datan_kpl}' ";
          $splitlisa2 = $row['kpl'] > 0 ? ", kpl = '{$datan_kpl}'" : ", kpl = 0 ";
          $rivihintalisa = $row['kpl'] > 0 ? ", rivihinta = '".(($row['rivihinta'] / $row['kpl']) * $datan_kpl)."'" : ", rivihinta = 0";

          // P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
          $query = "UPDATE tilausrivi SET
                    {$splitlisa}
                    {$splitlisa2}
                    {$rivihintalisa}
                    , tilkpl = '{$datan_kpl}'
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '{$myynti_rivi_row['tunnus']}'";
          if ($debug == 1) {
            echo "P‰ivitet‰‰n myynnin lapsiriville saapunut kappalem‰‰r‰:<br><pre>", str_replace("\t", "", $query), "</pre><br><br>";
          }
          $upres = pupe_query($query);
        }

        // jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
        if ($lapsi_chk_row['kpl'] > 0) {

          $_arr = array(
            $lapsi_chk_row['tunnus'] => $lapsi_uusi_id
          );

          if ($myynti_tilausrivi_id_lapsi != 0) $_arr[$myynti_rivi_row['tunnus']] = $myynti_tilausrivi_id_lapsi;

          kopioi_tapahtuma($_arr, $kappaleerotus, $datan_kpl, $debug);
        }
      }
    }
  }
}

// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
// Tarvitaan $trow jossa on toimittajan tiedot
// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

// Haetaan ostoreskontralaskun tiedot
$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$tunnus}'";
$osres = pupe_query($query);
$osrow = mysql_fetch_assoc($osres);

$laskun_numero = $osrow["laskunro"]; // ‰l‰ mene muuttaamaan tai poistamaan t‰t‰ muuttujaa.!!! Tai no joo... Ongelma..

$lajilisa = strtolower(substr($osrow['ebid'], 0, 3));

// Luodaan saapuminen, jos toimittajalle on m‰‰ritelty, ett‰ verkkolaskulta luodaan saapuminen tai
// manuaalisesti laskua syˆtett‰ess‰ ruksataan 'Luo saapuminen'
if (($osrow['ebid'] == "FINVOICEXML" and $trow['asn_sanomat'] == 'F') or $osrow['ebid'] == '') {
  $query  = "LOCK TABLE lasku WRITE, sanakirja WRITE, tiliointi READ";
  $result = pupe_query($query);

  // Haetaan seuraava vapaa keikkaid
  $query  = "SELECT max(laskunro) + 1 as laskunro
             FROM lasku
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tila    = 'K'";
  $result = pupe_query($query);
  $row    = mysql_fetch_assoc($result);

  $id  = $row['laskunro'];  // t‰m‰ on meid‰n keikannumero
  $keikkanumeroapu = $id; // talteen

  // Haetaan laskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
  $query = "SELECT SUM(summa) summa
            FROM tiliointi
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND ltunnus  = '{$osrow['tunnus']}'
            AND tilino   = '{$yhtiorow['alv']}'
            AND korjattu = ''";
  $alvires = pupe_query($query);
  $alvirow = mysql_fetch_assoc($alvires);

  // Ostoreskontralaskun veroton arvo
  $osrow["arvo"] = $osrow["summa"] - $alvirow["summa"];

  // Tehd‰‰n itse keikkaotsikko
  $query = "INSERT INTO lasku SET
            yhtio                   = '{$kukarow['yhtio']}',
            yhtio_toimipaikka       = '{$osrow['yhtio_toimipaikka']}',
            laskunro                = '{$id}',
            ytunnus                 = '{$trow['ytunnus']}',
            nimi                    = '{$trow['nimi']}',
            valkoodi                = '{$osrow['valkoodi']}',
            vienti                  = '{$osrow['vienti']}',
            maksu_kurssi            = '{$osrow['maksu_kurssi']}',
            vienti_kurssi= '{$osrow['vienti_kurssi']}',
            toimitusehto            = '{$trow['toimitusehto']}',
            osoite                  = '{$trow['osoite']}',
            postitp                 = '{$trow['postitp']}',
            maa                     = '{$trow['maa']}',
            maa_lahetys             = '{$trow['maa']}',
            kauppatapahtuman_luonne = '{$trow['kauppatapahtuman_luonne']}',
            kuljetusmuoto= '{$trow['kuljetusmuoto']}',
            swift                   = '{$trow['swift']}',
            liitostunnus            = '{$trow['tunnus']}',
            tila                    = 'K',
            luontiaika              = now(),
            laatija                 = 'verkkolas'";
  $result = pupe_query($query);

  $keikantunnus = mysql_insert_id($GLOBALS["masterlink"]); // t‰t‰ tarvitaan tilausrivien uusiotunnukseen
  $laskuvirhe .= "\n".t("Perustettiin saapuminen").": $id $trow[nimi]\n";

  $query  = "UNLOCK TABLE";
  $result = pupe_query($query);

  // Liitet‰‰n vastaanotettu verkkolasku uuteen saapumiseen
  // Tehd‰‰n liitosotsikko ostoreskontralaskulle
  verkkolasku_luo_liitosotsikko($tunnus, $keikantunnus);

  $laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $osrow[summa] $osrow[valkoodi]\n";
}

$kohdistettujariveja = 0;
$kauttalaskutus     = "";
$kauttalaskutus_saldoton = array();
$keikoilla  = (isset($tullaan_virhetarkistuksesta) and isset($keikoilla)) ? $keikoilla : array();
$ostoilla  = (isset($tullaan_virhetarkistuksesta) and isset($ostoilla)) ? $ostoilla : array();
$tullaan_virhetarkistuksesta = isset($tullaan_virhetarkistuksesta) ? $tullaan_virhetarkistuksesta : false;
$virhe    = "";
$virheet   = 0;

for ($i = 0; $i < count($rtuoteno); $i++) {

  // tehd‰‰n t‰‰ vaan jos meill‰ on rivinumero
  if ((!isset($rtuoteno[$i]["kauttalaskutus"]) or $rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS") and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0 and 
  ($osrow['ebid'] == "FINVOICEXML" and $trow['asn_sanomat'] == 'F')) {

    $toim_tuoteno   = $rtuoteno[$i]["tuoteno"];
    $kpl      = (float) $rtuoteno[$i]["kpl"];
    $summa      = (float) $rtuoteno[$i]["rivihinta"];

    if ($kpl != 0) {
      $hinta = round($summa / $kpl, 2);
    }
    else {
      // jos kappaleet on nolla, oletetaan 1
      $hinta = $summa;
    }

    // etsit‰‰n hyv‰‰ osumaa!
    $query = "SELECT tilausrivi.*, lasku.ketjutus
              FROM tilausrivi
              JOIN lasku on lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.liitostunnus = '$trow[tunnus]'
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              AND tilausrivi.tunnus  = '{$rtuoteno[$i]["tilaajanrivinro"]}'
              AND tilausrivi.tyyppi  = 'O'
              AND tilausrivi.kpl+tilausrivi.varattu != 0";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {

      $rivirow = mysql_fetch_assoc($result);
      $kohdistettujariveja++;

      // jos rivi lˆytyi ja sit‰ ei ole kohdistettu viel‰, kohdistetaan saapumiseen ja p‰ivitet‰‰n kpl
      if ($rivirow["uusiotunnus"] == 0) {
        $query = "UPDATE tilausrivi SET
                  uusiotunnus  = '{$keikantunnus}',
                  varattu      = '{$kpl}',
                  hinta        = '{$hinta}'
                  WHERE tunnus = '{$rivirow['tunnus']}'
                  AND yhtio    = '{$kukarow['yhtio']}'";
        $result = pupe_query($query);

        $laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("kohdistettu saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
      }
      // jos rivi lˆytyi, mutta se on jo kohdistettu.. lis‰t‰‰n uus samoilla tiedoilla
      else {
        $query = "INSERT INTO tilausrivi
                  (uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) VALUES
                  ('{$keikantunnus}', '{$hinta}', '{$rivirow['tuoteno']}', '{$rivirow['tilkpl']}', '{$kpl}', '{$rivirow['otunnus']}', '{$kukarow['yhtio']}', 'O', '{$rivirow['hyllyalue']}', '{$rivirow['hyllynro']}', '{$rivirow['hyllyvali']}', '{$rivirow['hyllytaso']}','{$rivirow['tilaajanrivinro']}')";
        $result = pupe_query($query);
        $laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("lis‰tty saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
      }
    }
    else {
      $laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("Ei voitu kohdistaa saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
    }
  }
  elseif (($osrow["ebid"] == "TECCOM-INVOICE" or ($osrow['ebid'] == 'MAVENTAXML' and $trow['asn_sanomat'] == 'M') or ($osrow['ebid'] == 'FINVOICEXML' and $trow['asn_sanomat'] == 'F')) and (!isset($rtuoteno[$i]["kauttalaskutus"]) or $rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS") and !$tullaan_virhetarkistuksesta) {
    // Onko ostolaskua ja onko kohdistamatta

    // Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
    // K = tulee asn-sanoma ja lasku
    // S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
    // L = tulee vain lasku
    // M = tulee vain maventa-lasku
    // F = tulee vain finvoice-lasku

    // ei k‰sitell‰ erolistalle siirrettyj‰ rivej‰.
    if ($rtuoteno[$i]['status'] == 'E') continue;

    $isalisa = "";

    if (isset($rtuoteno[$i]["isa_olemassa"]) and trim($rtuoteno[$i]["isa_olemassa"]) != "") {
      $isalisa = "and tilausrivi.perheid IN (".(int) $rtuoteno[$i]["isa_olemassa"];

      if (isset($rtuoteno[$i]["isat_array"]) and count($rtuoteno[$i]["isat_array"]) > 0) {
        $isalisa .= ",".implode(",", $rtuoteno[$i]["isat_array"]);
      }

      $isalisa .= ") and tilausrivi.perheid != tilausrivi.tunnus";
    }

    if ($debug == 1) {
      echo "<br>Katotaa etsi_sopivat_tilausrivit<br>";
    }

    list($kaytettavat_tilausrivit, $skipatut_tilausrivit) = etsi_sopivat_tilausrivit($rtuoteno[$i], $lajilisa, $trow, $isalisa, $debug, $_yhtion_toimipaikka);

    if ($debug == 1) {
      echo "<br>Katottiin etsi_sopivat_tilausrivit / skipatut_tilausrivit<br>";
      echo "<pre>", var_dump($kaytettavat_tilausrivit, $skipatut_tilausrivit), "</pre>";
    }

    if (count($kaytettavat_tilausrivit) > 0) {
      if ($debug == 1) {
        echo "<br>K‰ytett‰v‰t tilausrivit <pre>", var_dump($kaytettavat_tilausrivit), "</pre>";
      }

      $tilausrivi_nro = "";

      // katsotaan m‰ts‰‰kˆ ensiksi laskulla olevalla kappalem‰‰r‰ll‰
      foreach ($kaytettavat_tilausrivit as $riv) {

        $query = "SELECT *, kpl+varattu AS varattu
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$riv}'
                  AND kpl+varattu = '{$rtuoteno[$i]['kpl']}'";
        $tilausrivires = pupe_query($query);

        if (mysql_num_rows($tilausrivires) != 0) {
          $tilausrivi_nro = $riv;
          break;
        }
      }

      if ($tilausrivi_nro != "") {
        $tilausrivirow = mysql_fetch_assoc($tilausrivires);
      }
      else {
        reset($kaytettavat_tilausrivit);

        $tmp_kaytettavat_tilausrivit = $kaytettavat_tilausrivit;

        $tilausrivi_nro = array_shift($tmp_kaytettavat_tilausrivit);

        $query = "SELECT *, kpl+varattu AS varattu
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tilausrivi_nro}'";
        $tilausrivires = pupe_query($query);
        $tilausrivirow = mysql_fetch_assoc($tilausrivires);
      }

      if (empty($rtuoteno[$i]["isa_olemassa"])) {

        unset($riv);

        foreach ($kaytettavat_tilausrivit as $riv) {
          $_perheid = tuoteperhe_check($riv, $debug);

          if ($riv == $tilausrivirow['tunnus']) $tilausrivirow['perheid'] = $_perheid;
        }
      }

      if ($tilausrivirow['perheid'] == $tilausrivirow['tunnus']) {

        // etsit‰‰n lapset ja splitataan ne is‰n mukaan
        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND tyyppi   = 'O'
                  AND otunnus  = '{$tilausrivirow['otunnus']}'
                  AND perheid  = '{$tilausrivirow['perheid']}'
                  AND tunnus  != '{$tilausrivirow['perheid']}'";
        $lapsi_chk_res = pupe_query($query);

        while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {
          if ($rtuoteno[$i+1]["lapsi"] == "yes") {
            if ($debug == 1) {
              echo "<br>isalis‰ tuotteella $tilausrivirow[tuoteno] (tunnus $tilausrivirow[tunnus])<br><br>";
            }
            $rtuoteno[$i+1]["isa_olemassa"] = $tilausrivirow['tunnus'];
            $rtuoteno[$i+1]["isat_array"] = array($tilausrivirow['tunnus']);
            break;
          }
        }
      }

      // jos ei splitata, p‰ivitet‰‰n hinta ja kohdistetaan.
      if ($tilausrivirow['varattu'] == $rtuoteno[$i]["kpl"]) {

        $update_asn = "UPDATE asn_sanomat SET
                       tilausrivi       = '{$tilausrivirow['tunnus']}',
                       keikkarivinhinta = '{$tilausrivirow['hinta']}',
                       ale1             = '{$tilausrivirow['ale1']}',
                       ale2             = '{$tilausrivirow['ale2']}',
                       ale3             = '{$tilausrivirow['ale3']}',
                       tuoteno          = '{$tilausrivirow['tuoteno']}'
                       WHERE yhtio      = '{$kukarow['yhtio']}'
                       AND tunnus       = '{$rtuoteno[$i]['insert_id']}'
                       AND laji         = '{$lajilisa}'";
        pupe_query($update_asn);

        list($ostoilla, $keikoilla) = ostoilla_vai_keikoilla($ostoilla, $keikoilla, $tilausrivirow['tunnus'], $tilausrivirow, $rtuoteno[$i], $debug);
      }
      elseif ($rtuoteno[$i]["kpl"] < $tilausrivirow["varattu"]) {
        // Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kentt‰‰n

        $kappaleerotus = $tilausrivirow["varattu"] - $rtuoteno[$i]["kpl"];
        if ($debug == 1) {
          echo "KAPPALEITA!!! $kappaleerotus = $tilausrivirow[varattu] - {$rtuoteno[$i]["kpl"]}<br><br>";
        }

        paivita_ja_splittaa($tilausrivirow, $rtuoteno[$i]["kpl"], $kappaleerotus, $debug);

        $update_asn = "UPDATE asn_sanomat SET
                       tilausrivi       = '{$tilausrivirow['tunnus']}',
                       keikkarivinhinta = '{$tilausrivirow['hinta']}',
                       ale1             = '{$tilausrivirow['ale1']}',
                       ale2             = '{$tilausrivirow['ale2']}',
                       ale3             = '{$tilausrivirow['ale3']}',
                       tuoteno          = '{$tilausrivirow['tuoteno']}'
                       WHERE yhtio      = '{$kukarow['yhtio']}'
                       AND tunnus       = '{$rtuoteno[$i]['insert_id']}'
                       AND laji         = '{$lajilisa}'";
        if ($debug == 1) {
          echo "<pre>", str_replace("\t", "", $update_asn), "</pre><br><br>";
        }

        $foo = pupe_query($update_asn);

        list($ostoilla, $keikoilla) = ostoilla_vai_keikoilla($ostoilla, $keikoilla, $tilausrivirow['tunnus'], $tilausrivirow, $rtuoteno[$i], $debug);
      }
      else {
        // tuli enemm‰n kuin tilattu
        $laskuri = 0;
        $kohdistamatta = $rtuoteno[$i]["kpl"];
        $lisa_array = array();
        $split_array = array();
        if ($debug == 1) {
          echo "Tuli enemm‰n kuin tilattu. Kohdistamatta $kohdistamatta<br><br>";
        }

        // Lasketaan ensin kaikki rivit yhteen ja sen j‰lkeen katsotaan tarvitaanko lis‰rivi‰
        foreach ($kaytettavat_tilausrivit as $tilriv1) {

          if ($kohdistamatta <= 0) break;

          $query = "SELECT kpl+varattu AS varattu, perheid FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilriv1}'";
          $kpl_chk_res = pupe_query($query);
          $kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

          $laskuri += $kpl_chk_row["varattu"];
          $lisa_array[] = $tilriv1;
          $split_array[] = $tilriv1;
          $kohdistamatta -= $kpl_chk_row["varattu"];

          if ($tilriv1 == $kpl_chk_row['perheid'] and !empty($rtuoteno[$i+1]["isa_olemassa"])) {
            $rtuoteno[$i+1]["isat_array"][] = $tilriv1;
          }

          if ($debug == 1) {
            echo "Laskuri: $laskuri Kohdistamatta: $kohdistamatta<br><br>";
          }
        }
        if ($debug == 1) {
          echo "Lisa_array: <pre>", var_dump($lisa_array), "</pre>";
        }

        $ylimaaraista = $rtuoteno[$i]["kpl"] - $laskuri; // liikaa miinus tilauksella
        if ($debug == 1) {
          echo "Ylim‰‰r‰ist‰: $ylimaaraista = {$rtuoteno[$i]['kpl']} - $laskuri<br><br>";
        }

        // Katsotaan ollaanko skipattu tilausrivej‰, ennen kuin aletaan luomaan niit‰ alempana
        if (count($skipatut_tilausrivit) > 0) {

          // jos skipattiin v‰h‰nkin niin nollataan kaikki kohdistukset
          $updatequery = "UPDATE asn_sanomat SET
                          tilausrivi  = NULL,
                          tuoteno     = '{$tilausrivirow['tuoteno']}'
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tunnus  = '{$rtuoteno[$i]['insert_id']}'";
          $upd_res = pupe_query($updatequery);

          if ($debug == 1) {
            echo "<pre>$updatequery</pre>";
          }

          $virheet++;

          // teh‰‰ lapselle sama jippo ku ylemp‰n‰
          if ($tilausrivirow['tunnus'] == $tilausrivirow['perheid'] and !empty($rtuoteno[$i+1]["isa_olemassa"])) {

            $i++;

            // jos skipattiin v‰h‰nkin niin nollataan kaikki kohdistukset
            $updatequery = "UPDATE asn_sanomat SET
                            tilausrivi  = NULL
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = '{$rtuoteno[$i]['insert_id']}'";
            $upd_res = pupe_query($updatequery);

            if ($debug == 1) {
              echo "<pre>$updatequery</pre>";
            }
          }

          // Hyp‰t‰‰n yli t‰m‰ looppikerta ja menn‰‰n seuraavaan for-loopin iteraatioon (rivi ~950)
          continue;
        }

        if ($ylimaaraista > 0) {

          $query = "(SELECT DISTINCT tilausrivi.tunnus, toimi.asn_sanomat,
                     IF(tilausrivi.laskutettuaika = '0000-00-00', '9999-12-31', tilausrivi.laskutettuaika) AS laskutettuaika,
                     IF(tilausrivi.uusiotunnus = 0, ".PHP_INT_MAX.", tilausrivi.uusiotunnus) AS uusiotunnus,
                     tilausrivi.laadittu, tilausrivi.perheid, '1' jarjestys
                     FROM tilausrivi
                     JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                       AND lasku.tunnus                                     = tilausrivi.uusiotunnus
                       AND lasku.mapvm                                      = '0000-00-00'
                       AND lasku.tapvm                                      = '0000-00-00'
                       AND lasku.alatila                                   != 'X'
                       AND lasku.yhtio_toimipaikka                          = '{$_yhtion_toimipaikka}')
                     JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                             AND toimi.tunnus                               = lasku.liitostunnus
                             AND toimi.tunnus                               = '{$trow['tunnus']}'
                             AND toimi.tyyppi                              != 'P'
                             AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                     JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                     AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                     AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                     AND tuotteen_toimittajat.toim_tuoteno  = '{$rtuoteno[$i]['tuoteno']}')
                     JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                     WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                     AND tilausrivi.tyyppi                                  = 'O'
                     AND tilausrivi.uusiotunnus                             = '{$tilausrivirow['uusiotunnus']}'
                     AND tilausrivi.kpl+tilausrivi.varattu != 0
                     {$isalisa})
                     UNION
                     (SELECT DISTINCT tilausrivi.tunnus, toimi.asn_sanomat,
                     IF(tilausrivi.laskutettuaika = '0000-00-00', '9999-12-31', tilausrivi.laskutettuaika) AS laskutettuaika,
                     IF(tilausrivi.uusiotunnus = 0, ".PHP_INT_MAX.", tilausrivi.uusiotunnus) AS uusiotunnus,
                     tilausrivi.laadittu, tilausrivi.perheid, '2' jarjestys
                     FROM tilausrivi
                     JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                       AND lasku.tunnus                                     = tilausrivi.uusiotunnus
                       AND lasku.mapvm                                      = '0000-00-00'
                       AND lasku.tapvm                                      = '0000-00-00'
                       AND lasku.alatila                                   != 'X'
                       AND lasku.yhtio_toimipaikka                          = '{$_yhtion_toimipaikka}')
                     JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                             AND toimi.tunnus                               = lasku.liitostunnus
                             AND toimi.tunnus                               = '{$trow['tunnus']}'
                             AND toimi.tyyppi                              != 'P'
                             AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                     JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                     AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                     AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                     AND tuotteen_toimittajat.toim_tuoteno  = '{$rtuoteno[$i]['tuoteno']}')
                     JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                     WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                     AND tilausrivi.tyyppi                                  = 'O'
                     AND tilausrivi.uusiotunnus                            != '{$tilausrivirow['uusiotunnus']}'
                     AND tilausrivi.uusiotunnus                            != 0
                     AND tilausrivi.kpl+tilausrivi.varattu != 0
                     {$isalisa})
                     UNION
                     (SELECT DISTINCT tilausrivi.tunnus, toimi.asn_sanomat,
                     IF(tilausrivi.laskutettuaika = '0000-00-00', '9999-12-31', tilausrivi.laskutettuaika) AS laskutettuaika,
                     IF(tilausrivi.uusiotunnus = 0, ".PHP_INT_MAX.", tilausrivi.uusiotunnus) AS uusiotunnus,
                     tilausrivi.laadittu, tilausrivi.perheid, '3' jarjestys
                     FROM tilausrivi
                     JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                       AND lasku.tunnus                                     = tilausrivi.otunnus
                       AND lasku.tila                                       = 'O'
                       AND lasku.alatila                                    = 'A'
                       AND lasku.vanhatunnus                                = '{$_yhtion_toimipaikka}')
                     JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
                             AND toimi.tunnus                               = lasku.liitostunnus
                             AND toimi.tunnus                               = '{$trow['tunnus']}'
                             AND toimi.tyyppi                              != 'P'
                             AND toimi.asn_sanomat                          IN ('K', 'L', 'M', 'F'))
                     JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
                                     AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                     AND tuotteen_toimittajat.tuoteno       = tilausrivi.tuoteno
                                     AND tuotteen_toimittajat.toim_tuoteno  = '{$rtuoteno[$i]['tuoteno']}')
                     JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                     WHERE tilausrivi.yhtio                                 = '{$kukarow['yhtio']}'
                     AND tilausrivi.tyyppi                                  = 'O'
                     AND tilausrivi.uusiotunnus                             = 0
                     AND tilausrivi.kpl+tilausrivi.varattu != 0
                     {$isalisa})
                     ORDER BY jarjestys, uusiotunnus ASC, laskutettuaika ASC, laadittu ASC";
          if ($debug == 1) {
            echo "<pre>", str_replace("\t", "", $query), "</pre><br>";
          }
          $kokeillaan = pupe_query($query);

          $laskuri_tmp = $laskuri;
          $laskuri = 0;

          if ($debug == 1) {
            while ($kokeiltiin = mysql_fetch_assoc($kokeillaan)) {
              echo "<pre>" . var_dump($kokeiltiin) . "</pre><br>";
            }

            mysql_data_seek($kokeillaan, 0);
          }

          while ($kokeiltiin = mysql_fetch_assoc($kokeillaan)) {

            // tilausrivi ei saa olla jo kohdistettu
            $query = "SELECT tunnus
                      FROM asn_sanomat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND laji    = '{$lajilisa}'
                      AND MATCH (tilausrivi) AGAINST ('{$kokeiltiin['tunnus']}' IN BOOLEAN MODE)";
            $chk_res = pupe_query($query);

            if (mysql_num_rows($chk_res) != 0 or in_array($kokeiltiin['tunnus'], $kaytettavat_tilausrivit)) continue;

            if ($kohdistamatta <= 0) break;

            if ($kokeiltiin['asn_sanomat'] == 'K') {
              $query = "SELECT tunnus
                        FROM asn_sanomat
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND laji    = 'asn'
                        AND MATCH (tilausrivi) AGAINST ('{$kokeiltiin['tunnus']}' IN BOOLEAN MODE)
                        AND status  = 'X'";
              $chk_res = pupe_query($query);

              if (mysql_num_rows($chk_res) == 0) {
                if ($debug == 1) {
                  echo "3 Debug VIRHEET++: tilausrivi $tilausrivirow[tunnus] (perheid: $tilausrivirow[perheid]) tuoteno {$rtuoteno[$i]['tuoteno']}<br>";
                  echo "3 Debug Kokeiltiin $kokeiltiin[tunnus] rivi‰ jonka perheid oli $kokeiltiin[perheid] ja uusiotunnus $kokeiltiin[uusiotunnus] ja otunnus $kokeiltiin[otunnus]<br><br>";
                }
                // $virheet++;

                // TƒMƒ ON PUUKOTUS
                if ($kokeiltiin['perheid'] != 0 and $kokeiltiin['perheid'] != $kokeiltiin['tunnus']) {

                  // is‰ pit‰‰ olla jo kohdistettu
                  $query = "SELECT tunnus
                            FROM asn_sanomat
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND laji    = 'asn'
                            AND MATCH (tilausrivi) AGAINST ('{$kokeiltiin['perheid']}' IN BOOLEAN MODE)
                            AND status  = 'X'";
                  $chk_res = pupe_query($query);

                  if (mysql_num_rows($chk_res) == 0) {
                    if ($debug == 1) {
                      echo "continue1 <br><br>";
                    }
                    continue;
                  }
                }
                else {
                  if ($debug == 1) {
                    echo "continue2 <br><br>";
                  }
                  continue;
                }
              }
            }

            $query = "SELECT kpl+varattu AS varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kokeiltiin['tunnus']}'";
            $kpl_chk_res = pupe_query($query);
            $kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

            if ($debug == 1) {
              echo "kpl+varattu kokeiltiin tunnuksella:<br>";
              query_dump($query);
            }

            if ($kokeiltiin["perheid"] != 0 and $kokeiltiin["tunnus"] == $kokeiltiin["perheid"]) {
              tuoteperhe_check($kokeiltiin["tunnus"], $debug);

              if (!empty($rtuoteno[$i+1]["isa_olemassa"])) {
                $rtuoteno[$i+1]["isat_array"][] = $kokeiltiin['tunnus'];
              }
            }

            $laskuri += $kpl_chk_row["varattu"];
            $lisa_array[] = $kokeiltiin['tunnus'];
            $split_array[] = $kokeiltiin['tunnus'];
            $kohdistamatta -= $kpl_chk_row["varattu"];
            if ($debug == 1) {
              echo "laskuri: $laskuri kohdistamatta: $kohdistamatta lis‰ttiin tunnus lisa_arrayseen <-- $kokeiltiin[tunnus]<br><br>";
            }
          }

          if (mysql_num_rows($kokeillaan) > 0) {
            if ($debug == 1) {
              echo "ylimaaraista ".($ylimaaraista - $laskuri)." = {$ylimaaraista} - $laskuri<br><br>";
            }
            $ylimaaraista = $ylimaaraista - $laskuri;

            // Kokonaislaskurim‰‰r‰
            $laskuri += $laskuri_tmp;
          }
        }

        if ($ylimaaraista > 0) {
          if ($debug == 1) {
            echo "Jouduttiin haaraan ylim‰‰r‰ist‰ > 0 ($ylimaaraista > 0)<br><br>";
          }

          // Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
          $fields = "yhtio";
          $values = "'{$kukarow['yhtio']}'";

          // Ei monisteta tunnusta
          for ($ii = 1; $ii < mysql_num_fields($tilausrivires) - 1; $ii++) {

            $fieldname = mysql_field_name($tilausrivires, $ii);

            if ($fieldname == 'tunnus') continue;

            $fields .= ", ".$fieldname;

            switch ($fieldname) {
            case 'hinta_alkuperainen':
              $hinta_alkuperainenlisa = round(($tilausrivirow['hinta_alkuperainen'] / ($tilausrivirow['kpl']+$tilausrivirow['varattu'])) * $ylimaaraista, 6);
              $values .= ", '{$hinta_alkuperainenlisa}'";
              break;
            case 'tilkpl':
            case 'varattu':
              $values .= ", '{$ylimaaraista}'";
              break;
            case 'laatija':
              $values .= ", 'extraa'";
              break;
            case 'kommentti':
              $values .= ", 'Laskulla 1: {$laskun_numero} tuli {$rtuoteno[$i]['kpl']} ja tilaukselta lˆytyi {$laskuri} Tehtiin tilausrivi {$ylimaaraista} m‰‰r‰lle'";
              break;
            case 'laadittu':
              $values .= ", now()";
              break;
            case 'kpl':
            case 'rivihinta':
            case 'perheid':
              $values .= ", 0";
              break;
            case 'laskutettuaika':
              $values .= ", '0000-00-00'";
              break;
            case 'kate_korjattu':
              $values .= ", NULL";
              break;
            default:
              $values .= ", '".$tilausrivirow[$fieldname]."'";
            }
          }

          $kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
          if ($debug == 1) {
            echo "<pre>$kysely</pre>";
          }

          $uusires = pupe_query($kysely);
          $extraa = mysql_insert_id($GLOBALS["masterlink"]);
          $lisa_array[]      = $extraa;

          // Tarkistetaan onko t‰m‰ is‰rivi ($i+1 tarkoittaa sit‰ ett‰ ollaan laitettu lapsialkiolle is‰n tunnus talteen "isa_olemassa"-kentt‰‰n)
          // Otetaan is‰n splitattuneen osion tunnus talteen, ett‰ osataan seuraavalla kierroksella p‰ivitt‰‰ tuoteperhe kuntoon
          if (!empty($rtuoteno[$i+1]["isa_olemassa"])) {
            $rtuoteno[$i+1]["isa_olemassa_split"] = $extraa;
            if ($debug == 1) {
              echo "isa_olemassa_split:".$rtuoteno[$i+1]["isa_olemassa_split"] ." = " . $extraa . "<br>";
            }
          }

          // Tarkistetaan onko t‰m‰ lapsirivi
          // Jos on, laitetaan perheid kuntoon
          if (!empty($rtuoteno[$i]["isa_olemassa_split"])) {

            $query = "UPDATE tilausrivi SET
                      perheid     = {$rtuoteno[$i]["isa_olemassa_split"]}
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  IN ({$rtuoteno[$i]["isa_olemassa_split"]}, {$extraa})";
            $upd_res = pupe_query($query);

            if ($debug == 1) {
              echo "<pre>$query</pre>";
            }
          }

          $upd_tuoteno = $tilausrivirow['tuoteno'];
        }
        elseif ($ylimaaraista == 0) {

          $upd_tuoteno = $tilausrivirow['tuoteno'];
          if ($debug == 1) {
            echo "Ylim‰‰r‰ist‰ = 0<br><br>";
          }
        }
        elseif ($ylimaaraista < 0) {

          $positiivinen_arvo = abs($ylimaaraista);
          $vika_rivi = array_pop($split_array);
          // vika_rivi on se tilausrivi joka splitataan ett‰ m‰‰r‰ t‰sm‰‰

          $split_query  = "SELECT * FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$vika_rivi}'";
          $split_result  = pupe_query($split_query);
          $split_row    = mysql_fetch_assoc($split_result);
          $erotus      = ($split_row["kpl"] + $split_row['varattu']) - $positiivinen_arvo;

          $kaytetyt_tilausrivit[] = $vika_rivi;
          if ($debug == 1) {
            echo "Ylim‰‰r‰ist‰ < 0 (eli $ylimaaraista)<br><br>";
          }

          paivita_ja_splittaa($split_row, $erotus, $positiivinen_arvo, $debug);

          $upd_tuoteno = $split_row['tuoteno'];
          if ($debug == 1) {
            echo "P‰ivitet‰‰n ja kohdistetaan asn_sanomat-taulun rivi.<br><pre>", str_replace("\t", "", $query), "</pre><br><br>";
          }
        }

        $kaytetyt_tilausrivit  = array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
        $siistitty         = implode(",", $lisa_array);

        // p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
        $updatequery = "UPDATE asn_sanomat SET
                        tilausrivi  = '{$siistitty}',
                        tuoteno     = '{$upd_tuoteno}'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$rtuoteno[$i]['insert_id']}'";
        if ($debug == 1) {
          echo "<pre>$updatequery</pre>";
        }
        $upd_res = pupe_query($updatequery);

        foreach ($lisa_array as $tilriv) {

          $query = "SELECT uusiotunnus, tuoteno
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$tilriv}'";
          $uusiotunnus_res = pupe_query($query);
          $uusiotunnus_row = mysql_fetch_assoc($uusiotunnus_res);

          list($ostoilla, $keikoilla) = ostoilla_vai_keikoilla($ostoilla, $keikoilla, $tilriv, $uusiotunnus_row, $rtuoteno[$i], $debug);
        }
      }
    }
    elseif ($rtuoteno[$i]['tuoteno'] == "A-100" or $rtuoteno[$i]['tuoteno'] == "A-200") {
      continue;
    }
    else {
      if ($debug == 1) {
        echo "<br>Ei lˆytynyt rivej‰!!!! Debug tuotteelle {$rtuoteno[$i]['tuoteno']}<br><pre>", var_dump($rtuoteno[$i]), "</pre><br><br>";
      }

      // ei lˆydy tai joku muu virhe
      //echo "Virheellisi‰ kappalem‰‰ri‰ tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
      //echo "7 Debug VIRHEET++: tilausrivi $tilausrivirow[tunnus] tuoteno {$rtuoteno[$i]['tuoteno']}<br><br>";
      $virheet++;
      continue;
    }
  }
  elseif ($rtuoteno[$i]["kauttalaskutus"] == "KAUTTALASKUTUS" and $osrow['ebid'] != "TECCOM-INVOICE" and $osrow['ebid'] != "MAVENTAXML" and $trow['asn_sanomat'] != 'F') {

    $query = "SELECT *
              FROM tuote
              where yhtio = '$kukarow[yhtio]'
              and tuoteno = '".$rtuoteno[$i]["tuoteno"]."'";
    $result = pupe_query($query);
    $rivirow = mysql_fetch_assoc($result);

    if ($rivirow["tuoteno"] == "" or $rivirow["ei_saldoa"] != "") {
      // saldoton tai tuntematon tuote, vied‰‰n varastoon ja kauttalaskutetaan kuitenkin
      $rtuoteno[$i]["tuoteno"] = "1000";
    }

    $query = "INSERT into tilausrivi set
              hyllyalue       = '',
              hyllynro        = '',
              hyllyvali       = '',
              hyllytaso       = '',
              tilaajanrivinro = '".$rtuoteno[$i]["tilaajanrivinro"]."',
              laatija         = 'verkkolas',
              laadittu        = now(),
              yhtio           = '$kukarow[yhtio]',
              tuoteno         = '".$rtuoteno[$i]["tuoteno"]."',
              varattu         = '".$rtuoteno[$i]["kpl"]."',
              yksikko         = '".$rtuoteno[$i]["yksikko"]."',
              kpl             = 0,
              kpl2            = 0,
              tilkpl          = '".$rtuoteno[$i]["kpl"]."',
              jt              = 0,
              ale1            = '".$rtuoteno[$i]["ale"]."',
              alv             = '',
              netto           = '',
              hinta           = '".$rtuoteno[$i]["hinta"]."',
              rivihinta       = '".$rtuoteno[$i]["rivihinta"]."',
              kerayspvm       = '',
              otunnus         = '$keikantunnus',
              uusiotunnus     = '$keikantunnus',
              tyyppi          = 'O',
              toimaika        = now(),
              kommentti       = '".$rtuoteno[$i]["kommentti"]."',
              var             = '',
              try             = '$rivirow[try]',
              osasto          = '$rivirow[osasto]',
              perheid         = '',
              perheid2        = '',
              nimitys         = '".$rtuoteno[$i]["nimitys"]."',
              jaksotettu      = ''";
    $result = pupe_query($query);

    $kohdistettujariveja++;
    $kauttalaskutus = "kylla";
  }
}

// Jos kyseess‰ on TecCom ostolasku tai Maventa-lasku tai Finvoice-lasku
if (($osrow["ebid"] == "TECCOM-INVOICE"  and $trow['asn_sanomat'] == 'K') or ($osrow['ebid'] == 'MAVENTAXML' and $trow['asn_sanomat'] == 'M') or ($osrow['ebid'] == 'FINVOICEXML' and $trow['asn_sanomat'] == 'F')) {

  $loop_array = array();

  // Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
  // K = tulee asn-sanoma ja lasku
  // S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
  // L = tulee vain lasku
  // M = tulee vain maventa-lasku
  // F = tulee vain finvoice-lasku

  // ensin ostoilta, sitten keikalta, sitten virheet.
  // saadaan luoda keikka vain, jos tulee pelkk‰ lasku (eli toimittajan takana on asn_sanomat = 'L')
  if ($virheet == 0 and count($ostoilla) > 0 and count($keikoilla) == 0 and ($trow['asn_sanomat'] == 'L' or $trow['asn_sanomat'] == 'M' or $trow['asn_sanomat'] == 'F' or ($trow['asn_sanomat'] == 'K' and $tullaan_virhetarkistuksesta))) {

    if ($debug == 1) {
      echo "Debug: kululaskut haara, count(ostoilla) > 0 and count(keikoilla) == 0 and virheet == 0<br><br>";
    }
    $query = "SELECT kurssi
              FROM valuu
              WHERE yhtio = '$kukarow[yhtio]'
              AND nimi    = '$trow[oletus_valkoodi]'";
    $checkres = pupe_query($query);
    $row = mysql_fetch_assoc($checkres);
    $kurssi = $row["kurssi"];

    $query  = "LOCK TABLE lasku WRITE, sanakirja WRITE";
    $result = pupe_query($query);

    $query = "SELECT max(laskunro) laskunro
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              AND tila    = 'K'";
    $checkres = pupe_query($query);
    $row = mysql_fetch_assoc($checkres);
    $id = $row['laskunro'] + 1;

    $maa_lahetys = $row['maa_lahetys'] != '' ? $trow['maa_lahetys'] : $trow['maa'];

    // meill‰ on $trow tullut parametrin‰
    $query = "INSERT into lasku set
              yhtio                   = '$kukarow[yhtio]',
              yhtio_toimipaikka       = '{$osrow['yhtio_toimipaikka']}',
              laskunro                = '$id',
              ytunnus                 = '$trow[ytunnus]',
              nimi                    = '$trow[nimi]',
              valkoodi                = '$trow[oletus_valkoodi]',
              vienti                  = '$trow[oletus_vienti]',
              vienti_kurssi           = '$kurssi',
              toimitusehto            = '$trow[toimitusehto]',
              osoite                  = '$trow[osoite]',
              postitp                 = '$trow[postitp]',
              maa                     = '$trow[maa]',
              maa_lahetys             = '$maa_lahetys',
              kauppatapahtuman_luonne = '$trow[kauppatapahtuman_luonne]',
              kuljetusmuoto           = '$trow[kuljetusmuoto]',
              rahti                   = '$trow[oletus_kulupros]',
              swift                   = '$trow[swift]',
              liitostunnus            = '$trow[tunnus]',
              comments                = '".t("Saapuminen luotu laskusta")." $osrow[laskunro]',
              tila                    = 'K',
              luontiaika              = now(),
              laatija                 = '$kukarow[kuka]'";
    $insertres = pupe_query($query);
    $keikan_tunnus = mysql_insert_id($GLOBALS["masterlink"]);
    if ($debug == 1) {
      echo "<pre>", str_replace("\t", "", $query), "</pre>Keikan tunnus:$keikan_tunnus<br><br>";
      echo "<font class='message'>Liitetty saapumiseen {$id}</font><br><br>";
    }

    $query  = "UNLOCK TABLE";
    $result = pupe_query($query);

    $_uusiotunnus = $keikan_tunnus;

    $loop_array = array($ostoilla);
  }
  elseif (count($keikoilla) > 0 and $virheet == 0) {
    if ($debug == 1) {
      echo "Debug: kululaskut haara, count(keikoilla) > 0 and virheet == 0<br><br>";
    }

    // tarkistetaan onko keikalla ylim‰‰r‰isi‰ rivej‰ mit‰ ei ole laskulla
    // jos on, tehd‰‰n uusi keikka laskun rivej‰ varten
    $query = "SELECT tunnus
              FROM tilausrivi
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'";
    $tunnus_fetch_res = pupe_query($query);

    $tunnus_chk_array = array();

    while ($tunnus_fetch_row = mysql_fetch_assoc($tunnus_fetch_res)) {
      $tunnus_chk_array["{$tunnus_fetch_row['tunnus']}"] = $tunnus_fetch_row['tunnus'];
    }
    if ($debug == 1) {
      echo "N‰m‰ checkataaan: <pre>", var_dump($tunnus_chk_array), "</pre>";
      echo "Keikoilla: <pre>", var_dump($keikoilla), "</pre>";
    }

    // poistetaan arraysta laskulla olevat tilausrivitunnukset
    foreach ($keikoilla as $pointteri2) {
      if (isset($tunnus_chk_array["{$pointteri2['tunnus']}"])) {
        if ($debug == 1) {
          echo "$pointteri2[tunnus] lˆytyi, unsetataan se!<br><br>";
        }
        unset($tunnus_chk_array["{$pointteri2['tunnus']}"]);
      }
    }

    // keikalla oli muita kuin laskulla esiintyvi‰ rivej‰, tehd‰‰n uusi keikka
    if (count($tunnus_chk_array) > 0) {
      if ($debug == 1) {
        echo "Keikalla oli muita kuin laskulla esiintyvi‰ rivej‰, tehd‰‰n uusi keikka<br>";
        echo "<pre>", var_dump($tunnus_chk_array), "</pre>";
      }

      $query = "SELECT kurssi
                FROM valuu
                WHERE yhtio = '$kukarow[yhtio]'
                AND nimi    = '$trow[oletus_valkoodi]'";
      $checkres = pupe_query($query);
      $row = mysql_fetch_assoc($checkres);
      $kurssi = $row["kurssi"];

      $query  = "LOCK TABLE lasku WRITE, sanakirja WRITE";
      $result = pupe_query($query);

      $query = "SELECT max(laskunro) laskunro
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                AND tila    = 'K'";
      $checkres = pupe_query($query);
      $row = mysql_fetch_assoc($checkres);
      $id = $row['laskunro'] + 1;

      $maa_lahetys = $row['maa_lahetys'] != '' ? $trow['maa_lahetys'] : $trow['maa'];

      // meill‰ on $trow tullut parametrin‰
      $query = "INSERT into lasku set
                yhtio                   = '$kukarow[yhtio]',
                yhtio_toimipaikka       = '$osrow[yhtio_toimipaikka]',
                laskunro                = '$id',
                ytunnus                 = '$trow[ytunnus]',
                nimi                    = '$trow[nimi]',
                valkoodi                = '$trow[oletus_valkoodi]',
                vienti                  = '$trow[oletus_vienti]',
                vienti_kurssi           = '$kurssi',
                toimitusehto            = '$trow[toimitusehto]',
                osoite                  = '$trow[osoite]',
                postitp                 = '$trow[postitp]',
                maa                     = '$trow[maa]',
                maa_lahetys             = '$maa_lahetys',
                kauppatapahtuman_luonne = '$trow[kauppatapahtuman_luonne]',
                kuljetusmuoto           = '$trow[kuljetusmuoto]',
                rahti                   = '$trow[oletus_kulupros]',
                swift                   = '$trow[swift]',
                liitostunnus            = '$trow[tunnus]',
                comments                = '".t("Saapuminen luotu laskusta")." $osrow[laskunro]',
                tila                    = 'K',
                luontiaika              = now(),
                laatija                 = '$kukarow[kuka]'";
      $insertres = pupe_query($query);
      $keikan_tunnus = mysql_insert_id($GLOBALS["masterlink"]);
      if ($debug == 1) {
        echo "<pre>", str_replace("\t", "", $query), "</pre>Keikan tunnus:$keikan_tunnus<br><br>";
      }

      $query  = "UNLOCK TABLE";
      $result = pupe_query($query);

      $keikoilla[0]['uusiotunnus'] = $keikan_tunnus;
    }

    $query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keikoilla[0]['uusiotunnus']}'";
    $saapuminen_nro_res = pupe_query($query);
    $saapuminen_nro_row = mysql_fetch_assoc($saapuminen_nro_res);

    //echo "<font class='message'>Liitetty saapumiseen {$saapuminen_nro_row['laskunro']}</font><br><br>";

    reset($keikoilla);

    $_uusiotunnus = $keikoilla[0]['uusiotunnus'];

    $loop_array = array($keikoilla, $ostoilla);
  }
  else {
    $virheet++;
    echo "Laskulla oli virheit‰, emme tehneet mit‰‰n.\n";
  }

  if ($virheet == 0) {

    $laskun_kulut = array(
      'osto_kulu' => $osrow['osto_kulu'],
      'osto_kulu_alv' => $osrow['osto_kulu_alv'],
      'osto_rahti' => $osrow['osto_rahti'],
      'osto_rahti_alv' => $osrow['osto_rahti_alv'],
      'osto_rivi_kulu' => $osrow['osto_rivi_kulu'],
      'osto_rivi_kulu_alv' => $osrow['osto_rivi_kulu_alv']
    );

    // jos tullaan t‰h‰n kohtaan niin kappalem‰‰r‰t t‰sm‰‰. On jo tarkistettu rivill‰ 166
    // muutetaan hinta ja rivi on ok.
    verkkolasku_luo_liitosotsikko($tunnus, $_uusiotunnus, $laskun_kulut);

    foreach ($loop_array as $_arr) {

      foreach ($_arr as $pointteri) {

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$pointteri['tunnus']}'";
        $hinta_chk_res = pupe_query($query);
        $hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

        $update_asn = "UPDATE asn_sanomat SET
                       keikkarivinhinta = '{$hinta_chk_row['hinta']}',
                       ale1             = '{$hinta_chk_row['ale1']}',
                       ale2             = '{$hinta_chk_row['ale2']}',
                       ale3             = '{$hinta_chk_row['ale3']}',
                       status           = 'X',
                       tuoteno          = '{$hinta_chk_row['tuoteno']}'
                       WHERE yhtio      = '{$kukarow['yhtio']}'
                       AND tunnus       = '{$pointteri['insert_id']}'
                       AND laji         = '{$lajilisa}'";
        pupe_query($update_asn);

        if ($hinta_chk_row['perheid'] != 0) {

          $lapsen_lisakululisa = "";

          if (isset($pointteri["lisakulu"]) and trim($pointteri["lisakulu"]) != 0) {
            $lapsen_lisakululisa = " hinta = {$pointteri['lisakulu']} / {$pointteri['kpl']}, ";
          }

          $update = "UPDATE tilausrivi SET
                     {$lapsen_lisakululisa}
                     ale1         = 0,
                     ale2         = 0,
                     ale3         = 0,
                     erikoisale   = 0,
                     netto        = '',
                     uusiotunnus  = '{$_uusiotunnus}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$hinta_chk_row['otunnus']}'
                     AND perheid  = '{$hinta_chk_row['perheid']}'
                     AND tunnus  != '{$hinta_chk_row['perheid']}'";
          pupe_query($update);
        }

        if ($hinta_chk_row['hinta_alkuperainen'] == 0) {
          $query  = "SELECT tuote.*,
                     if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) alv,
                     if(tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) tuotekerroin
                     from tuote
                     LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$osrow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
                     where tuote.yhtio = '$kukarow[yhtio]'
                     and tuote.tuoteno = '$hinta_chk_row[tuoteno]'";
          $tuores = pupe_query($query);
          $tuorow = mysql_fetch_assoc($tuores);

          $ale_lisa_apu_kala = generoi_alekentta_php($hinta_chk_row, 'O', 'kerto');

          $ohinta = round($hinta_chk_row['hinta']*$ale_lisa_apu_kala*$tuorow['tuotekerroin']*$osrow['vienti_kurssi']*$hinta_chk_row['varattu'], 6); // tuotteen rivihinta OLETUSVALUUTASSA

          $query  = "UPDATE tilausrivi SET
                     hinta_alkuperainen = '{$ohinta}'
                     WHERE yhtio        = '{$kukarow['yhtio']}'
                     AND tunnus         = '{$hinta_chk_row['tunnus']}'";
          $resultxxx = pupe_query($query);
        }

        $ale1lisa = (isset($pointteri['ale1']) and trim($pointteri['ale1']) != "" and trim($pointteri['ale1']) != 0) ? " ale1 = '{$pointteri['ale1']}', " : " ale1 = 0, ";
        $ale2lisa = (isset($pointteri['ale2']) and trim($pointteri['ale2']) != "" and trim($pointteri['ale2']) != 0) ? " ale2 = '{$pointteri['ale2']}', " : " ale2 = 0, ";
        $ale3lisa = (isset($pointteri['ale3']) and trim($pointteri['ale3']) != "" and trim($pointteri['ale3']) != 0) ? " ale3 = '{$pointteri['ale3']}', " : " ale3 = 0, ";

        $update = "UPDATE tilausrivi SET
                   hinta       = '{$pointteri['hinta']}',
                   netto       = '',
                   erikoisale  = 0,
                   {$ale1lisa}
                   {$ale2lisa}
                   {$ale3lisa}
                   uusiotunnus = '{$_uusiotunnus}'
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus  = '{$pointteri['tunnus']}'";
        pupe_query($update);

        $update = "UPDATE asn_sanomat SET
                   toim_tuoteno = '{$pointteri['tuoteno']}'
                   WHERE yhtio  = '{$kukarow['yhtio']}'
                   AND tunnus   = '{$pointteri['insert_id']}'";
        pupe_query($update);

        if ($hinta_chk_row['suuntalava'] != 0) {

          $query = "SELECT tunnus
                    FROM suuntalavat_saapuminen
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND suuntalava = '{$hinta_chk_row['suuntalava']}'
                    AND saapuminen = '{$_uusiotunnus}'";
          $suuntalavat_saapuminen_chk_res = pupe_query($query);

          if (mysql_num_rows($suuntalavat_saapuminen_chk_res) == 0) {

            $query = "INSERT INTO suuntalavat_saapuminen SET
                      yhtio      = '{$kukarow['yhtio']}',
                      suuntalava = '{$hinta_chk_row['suuntalava']}',
                      saapuminen = '{$_uusiotunnus}',
                      laatija    = '{$kukarow['kuka']}',
                      luontiaika = now(),
                      muutospvm  = now(),
                      muuttaja   = '{$kukarow['kuka']}'";
            $suuntalava_upd_res = pupe_query($query);
          }

          $query = "SELECT GROUP_CONCAT(saapuminen) as saapumiset
                    FROM suuntalavat_saapuminen
                    WHERE yhtio     = '{$kukarow['yhtio']}'
                    AND suuntalava  = '{$hinta_chk_row['suuntalava']}'
                    AND saapuminen != '{$_uusiotunnus}'";
          $suuntalavat_saapuminen_chk_res = pupe_query($query);
          $suuntalavat_saapuminen_chk_row = mysql_fetch_assoc($suuntalavat_saapuminen_chk_res);

          if ($suuntalavat_saapuminen_chk_row['saapumiset'] != '') {

            $query = "SELECT uusiotunnus, GROUP_CONCAT(tunnus) as tunnukset
                      FROM tilausrivi
                      WHERE yhtio     = '{$kukarow['yhtio']}'
                      AND suuntalava  = '{$hinta_chk_row['suuntalava']}'
                      AND uusiotunnus IN ({$suuntalavat_saapuminen_chk_row['saapumiset']})
                      GROUP BY 1";
            $tilriv_tunnukset_res = pupe_query($query);

            while ($tilriv_tunnukset_row = mysql_fetch_assoc($tilriv_tunnukset_res)) {

              if ($tilriv_tunnukset_row['tunnukset'] != '') {
                $query = "UPDATE suuntalavat SET
                          kaytettavyys = 'L'
                          WHERE yhtio  = '{$kukarow['yhtio']}'
                          AND tunnus   = '{$hinta_chk_row['suuntalava']}'";
                $upd_kaytettavyys_res = pupe_query($query);
              }
              else {
                $query = "DELETE FROM suuntalavat_saapuminen
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND suuntalava = '{$hinta_chk_row['suuntalava']}'
                          AND saapuminen = '{$tilriv_tunnukset_row['uusiotunnus']}'";
                $del_res = pupe_query($query);
              }
            }
          }
        }
      }

      unset($pointteri);
    }

    $query = "SELECT varastopaikat.maa, count(*) kpl
              FROM lasku
              JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
              JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
                AND varastopaikat.maa    != ''
                AND varastopaikat.tunnus  = tilausrivi.varasto)
              WHERE lasku.yhtio           = '{$kukarow['yhtio']}'
              AND lasku.tunnus            = '{$_uusiotunnus}'
              GROUP BY varastopaikat.maa
              ORDER BY kpl DESC
              LIMIT 1";
    $maaresult = pupe_query($query);

    if ($maarow = mysql_fetch_array($maaresult)) {
      $maa_maara = $maarow["maa"];
    }
    elseif ($osrow['yhtio_toimipaikka'] != 0) {

      $query = "SELECT maa
                FROM yhtion_toimipaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$osrow['yhtio_toimipaikka']}'";
      $chk_maa_res = pupe_query($query);
      $chk_maa_row = mysql_fetch_assoc($chk_maa_res);

      $maa_maara = $chk_maa_row["maa"];
    }
    else {
      $maa_maara = $yhtiorow["maa"];
    }

    $query = "UPDATE lasku SET
              maa_maara   = '{$maa_maara}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$_uusiotunnus}'";
    $upd_res = pupe_query($query);

    $tee_kululaskut = "liita";
    $silent        = "SILENT";
    $keikanalatila = "";
    $laskutunnus   = $tunnus;
    $otunnus       = $keikantunnus =  $_uusiotunnus;

    require "tilauskasittely/kululaskut.inc";
  }
}

if (!function_exists('saapumisen_pyoristyserot')) {
  function saapumisen_pyoristyserot($osrow, $laskurow, $otunnus, $pyoristysero_raja = 0.01, $alv_talteen = '') {
    global $kukarow, $yhtiorow;

    // $osrow    = tavaralaskun laskun tiedot
    // $laskurow = saapumisen otsikon tiedot
    // $otunnus  = saapumisen tunnus

    $alvit = "tuote.alv";

    if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

      // Lasketaan verokanta
      if ($laskurow["arvo"] != 0) $simualv = round(100 * (($laskurow["summa"]/$laskurow["arvo"])-1), 2);
      else $simualv = 0;

      if ($laskurow["arvo"] > 0 and $simualv == 0) {
        $alvit = 0;
      }
      // Onko miell‰ oletuksista poikkeava alvi. Verokantoja ennen viimeisint‰ alv-muutosta.
      elseif (in_array($simualv, array(23, 13, 9))) {
        $alvit = $simualv;
      }
      else {
        $alvit = "if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv)";
      }

      if ($osrow["maa"] != "" and $osrow["maa"] != $yhtiorow["maa"]) {
        // tutkitaan ollaanko siell‰ alv-rekisterˆity
        $alhqur = "SELECT *
                   FROM yhtion_toimipaikat
                   WHERE yhtio     = '$kukarow[yhtio]'
                   AND maa         = '$osrow[maa]'
                   AND vat_numero != ''";
        $alhire = pupe_query($alhqur);

        // ollaan alv-rekisterˆity
        if (mysql_num_rows($alhire) == 1) {
          $alvit = "tuotteen_alv.alv";
        }
      }
    }
    else {
      $alvit = 0;
    }

    $query_ale_lisa = generoi_alekentta('O');

    $query = "SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
              (1+(if ((SELECT max(kaytetty) kaytetty
                FROM sarjanumeroseuranta
                WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
                and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
                and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, {$alvit})/100))) hinta
              FROM tilausrivi use index (uusiotunnus_index)
              JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
              LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}')
              LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '{$laskurow['maa']}')
              WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
              AND tilausrivi.uusiotunnus  = '{$otunnus}'
              AND tilausrivi.tyyppi      != 'D'";
    $result   = pupe_query($query);
    $hintarow = mysql_fetch_assoc($result);

    // Jos vanha saapuminen, vanhat pyˆristyksen nollataan, ett‰ ne eiv‰t kumuloidu
    $laskurow['pyoristys_erot'] = 0;

    if ($laskurow["rahti_etu"] != 0 and $laskurow["rahti_etu_alv"] != 0) {
      $rahti_etu = $laskurow["rahti_etu"] * ((100 + $laskurow["rahti_etu_alv"]) / 100);
    }
    else {
      $rahti_etu = $laskurow["rahti_etu"];
    }

    $valittusumma        = round($hintarow["hinta"], 2);
    $laskunsumma         = round($osrow["summa"] - $valittusumma, 2);
    $checksumma       = $osrow["summa"] - $hintarow["hinta"] - $rahti_etu - $laskurow['pyoristys_erot'] - $osrow['osto_kulut'];
    $kohdistettava_summa = round($osrow["summa"], 2);

    $pyoristys_erot_alv = 0;
    $pyoristys_erot = 0;

    $liian_iso_pyoristysero = false;

    if (abs($checksumma) <= $pyoristysero_raja) {
      $pyoristys_erot = $checksumma;
    }
    else {
      $liian_iso_pyoristysero = true;
    }

    if (isset($alv_talteen) and $alv_talteen != "" and $pyoristys_erot != 0 and ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J')) {
      $pyoristys_erot_alv = $alv_talteen;
      $pyoristys_erot = round($pyoristys_erot / (1 + $pyoristys_erot_alv / 100), $yhtiorow['hintapyoristys']);
    }

    $set_lisa = "";

    if (!$liian_iso_pyoristysero) {
      $set_lisa = "  pyoristys_erot = '{$pyoristys_erot}',
              pyoristys_erot_alv = '{$pyoristys_erot_alv}',
              kohdistettu = 'K',";

      $laskurow['kohdistettu'] = 'K';
    }

    $query   = "UPDATE lasku SET
                {$set_lisa}
                summa       = '{$hintarow['hinta']}',
                rahti_etu   = '{$laskurow['rahti_etu']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$otunnus}'";
    $hinresu = pupe_query($query);

    $laskurow['summa']         = $hintarow['hinta'];
    $laskurow['pyoristys_erot']   = $pyoristys_erot;
    $laskurow['pyoristys_erot_alv']  = $pyoristys_erot_alv;

    return $laskurow;
  }
}

$automaattituloutus = false;
$automaattijalkilaskenta = false;

if (($osrow["ebid"] == "TECCOM-INVOICE" or ($osrow['ebid'] == 'MAVENTAXML' and $trow['asn_sanomat'] == 'M') or ($osrow['ebid'] == 'FINVOICEXML' and $trow['asn_sanomat'] == 'F')) and $virheet == 0 and count($loop_array) > 0 and $trow['sahkoinen_automaattituloutus'] != 'E') {

  if ($trow['sahkoinen_automaattituloutus'] == 'K') {
    $automaattituloutus = true;
  }
  elseif ($trow['sahkoinen_automaattituloutus'] == 'J') {
    $automaattituloutus = true;
    $automaattijalkilaskenta = true;
  }

  if ($osrow['yhtio_toimipaikka'] != 0 and $trow['sahkoinen_automaattituloutus'] == '') {

    $query = "SELECT sahkoinen_automaattituloutus
              FROM yhtion_toimipaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$osrow['yhtio_toimipaikka']}'";
    $chk_toimipaikka_res = pupe_query($query);
    $chk_toimipaikka_row = mysql_fetch_assoc($chk_toimipaikka_res);

    if ($chk_toimipaikka_row['sahkoinen_automaattituloutus'] == 'K') {
      $automaattituloutus = true;
      $automaattijalkilaskenta = false;
    }
    elseif ($chk_toimipaikka_row['sahkoinen_automaattituloutus'] == 'J') {
      $automaattituloutus = true;
      $automaattijalkilaskenta = true;
    }
    else {
      $automaattituloutus = false;
      $automaattijalkilaskenta = false;
    }
  }
}

if (($osrow["ebid"] == "TECCOM-INVOICE" or ($osrow['ebid'] == 'MAVENTAXML' and $trow['asn_sanomat'] == 'M') or ($osrow['ebid'] == 'FINVOICEXML' and $trow['asn_sanomat'] == 'F')) and $virheet == 0 and count($loop_array) > 0 and $automaattituloutus) {

  $tullaan_automaattikohdistuksesta = true;
  $uusi_suuntalavan_tunnus = 0;
  $tilausrivit = array();

  unset($_arr, $pointteri);

  foreach ($loop_array as $_arr) {

    foreach ($_arr as $pointteri) {

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$pointteri['tunnus']}'";
      $tilausrivi_chk_res = pupe_query($query);
      $tilausrivi_chk_row = mysql_fetch_assoc($tilausrivi_chk_res);

      if ($tilausrivi_chk_row['suuntalava'] == 0 and $tilausrivi_chk_row['varattu'] != 0 and $yhtiorow['suuntalavat'] == 'S') {

        if ($uusi_suuntalavan_tunnus == 0) {

          $query = "SELECT vh.keraysvyohyke, keraysvyohyke.terminaalialue, COUNT(*)
                    FROM tilausrivi AS tp
                    JOIN varaston_hyllypaikat AS vh ON (vh.yhtio = tp.yhtio AND vh.hyllyalue = tp.hyllyalue AND vh.hyllynro = tp.hyllynro AND vh.hyllyvali = tp.hyllyvali AND vh.hyllytaso = tp.hyllytaso)
                    JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tp.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
                    WHERE tp.yhtio = '{$kukarow['yhtio']}'
                    AND tp.tunnus  = '{$tilausrivi_chk_row['tunnus']}'
                    GROUP BY 1,2
                    ORDER BY 3 DESC
                    LIMIT 1";
          $checkres = pupe_query($query);
          $vyohyke_row = mysql_fetch_assoc($checkres);

          // Tehd‰‰n uusi suuntalava
          $tee       = "lisaa";
          $otunnus     = $keikantunnus;
          $sscc       = $tilausrivi_chk_row['tunnus'];
          $tyyppi     = 10;
          $keraysvyohyke   = $vyohyke_row['keraysvyohyke'];
          $kaytettavyys   = "Y";
          $terminaalialue = $vyohyke_row['terminaalialue'];
          $korkeus     = 0;
          $paino       = 0;
          $alkuhyllyalue   = "";
          $alkuhyllynro   = "";
          $alkuhyllyvali   = "";
          $alkuhyllytaso   = "";
          $loppuhyllyalue = "";
          $loppuhyllynro   = "";
          $loppuhyllyvali = "";
          $loppuhyllytaso = "";
          $automaattinen_paivitys = "";
          $suuntalavat_ei_kayttoliittymaa = "KYLLA";
          $usea_keraysvyohyke = "K";

          // Palauttaa $uusi_suuntalavan_tunnus muuttujan
          require "tilauskasittely/suuntalavat.inc";
        }

        $query = "UPDATE tilausrivi SET
                  suuntalava  = '{$uusi_suuntalavan_tunnus}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tilausrivi_chk_row['tunnus']}'";
        pupe_query($query);

        $tilausrivi_chk_row['suuntalava'] = $uusi_suuntalavan_tunnus;
      }

      $tilausrivit[$tilausrivi_chk_row['suuntalava']][] = $tilausrivi_chk_row['tunnus'];
    }
  }

  $mennaanko_automaattituloutukseen = true;

  // Tarkistetaan onko ostotilauksen otsikolla tilaustyyppi avainsanoissa m‰‰ritelty ja avainsanan takana automaattituloutus estetty
  foreach ($tilausrivit as $suuntalavan_tunnus => $tilrivit) {

    foreach ($tilrivit as $tilrivi) {

      $query = "SELECT lasku.tilaustyyppi
                FROM tilausrivi
                JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilrivi}'";
      $chk_res = pupe_query($query);
      $chk_row = mysql_fetch_assoc($chk_res);

      $returni = t_avainsana("OSTOTIL_TILTYYP", '', "and selite = '{$chk_row['tilaustyyppi']}'", '', '', "selitetark_2");

      if ($returni != "") {
        $mennaanko_automaattituloutukseen = false;
        break 2;
      }
    }
  }

  if ($mennaanko_automaattituloutukseen) {

    if (!isset($rivikohtaiset_rahtikulut)) $rivikohtaiset_rahtikulut = 0;

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$otunnus}'";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    $laskuvirhe .= t("Vied‰‰n varastoon").". ($laskurow[laskunro])\n";

    $toimittajaid = $laskurow['liitostunnus'];
    $toiminto = "kalkyyli";
    $tee = "varastoon";

    $_go = true;

    foreach ($tilausrivit as $suuntalavan_tunnus => $tilrivit) {

      // Duplikaatit pois
      $tilausrivit[$suuntalavan_tunnus] = array_unique($tilausrivit[$suuntalavan_tunnus]);

      if ($yhtiorow['suuntalavat'] == 'S') {

        $query = "SELECT count(tunnus) rivit
                  FROM tilausrivi
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND suuntalava = '{$suuntalavan_tunnus}'";
        $cnt_chk_res = pupe_query($query);
        $cnt_chk_row = mysql_fetch_assoc($cnt_chk_res);

        if ($cnt_chk_row['rivit'] > count($tilausrivit[$suuntalavan_tunnus])) {

          // Ei haluta vied‰ varastoon niit‰ rivej‰, mitk‰ ei ollu t‰ss‰ laskussa mukana
          // Joten laitetaan varastoon = 0
          $query = "UPDATE tilausrivi SET
                    varastoon       = 0
                    WHERE yhtio     = '{$kukarow['yhtio']}'
                    AND suuntalava  = '{$suuntalavan_tunnus}'
                    AND kpl         = 0
                    AND varattu    != 0
                    AND tunnus      NOT IN (".implode($tilausrivit[$suuntalavan_tunnus]).")";
          pupe_query($query);
        }

        $query = "SELECT SUM(IF(kpl != 0, 1, 0)) kplrivit,
                  SUM(IF((hyllyalue != '' and hyllynro != '' and hyllyvali != '' and hyllytaso != ''), 1, 0)) hyllypaikat
                  FROM tilausrivi
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND suuntalava = '{$suuntalavan_tunnus}'
                  AND tunnus     IN (".implode($tilausrivit[$suuntalavan_tunnus]).")";
        $cnt_chk_res = pupe_query($query);
        $cnt_chk_row = mysql_fetch_assoc($cnt_chk_res);

        // Jos kaikki rivit on tuloutettu, ei haluta k‰sitell‰ sit‰ suuntalavaa
        if ($cnt_chk_row['kplrivit'] == count($tilausrivit[$suuntalavan_tunnus]) or $cnt_chk_row['hyllypaikat'] != count($tilausrivit[$suuntalavan_tunnus])) {
          continue;
        }

        // Laitetaan suuntalava valmiiksi
        $query = "UPDATE suuntalavat SET
                  tila        = 'S'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$suuntalavan_tunnus}'";
        pupe_query($query);
      }
      else {

        $query = "SELECT SUM(IF(kpl != 0, 1, 0)) kplrivit,
                  SUM(IF((hyllyalue != '' and hyllynro != '' and hyllyvali != '' and hyllytaso != ''), 1, 0)) hyllypaikat
                  FROM tilausrivi
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND uusiotunnus = '{$otunnus}'";
        $cnt_chk_res = pupe_query($query);
        $cnt_chk_row = mysql_fetch_assoc($cnt_chk_res);

        if ($cnt_chk_row['hyllypaikat'] != count($tilausrivit[$suuntalavan_tunnus])) {
          continue;
        }
      }

      $tilausrivi = 0;

      // P‰ivitet‰‰n vain kerran eturahti
      if ($_go) {

        $rahti_etu_alv = (float) $alv_talteen;

        // Laitetaan rivikohtaiset rahtikulut eturahtiin
        $laskurow['rahti_etu'] += $rivikohtaiset_rahtikulut;
        $laskurow['rahti_etu_alv'] = $rahti_etu_alv;

        $query = "UPDATE lasku SET
                  rahti_etu     = rahti_etu + $rivikohtaiset_rahtikulut,
                  rahti_etu_alv = $rahti_etu_alv
                  WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND tunnus    = '{$laskurow['tunnus']}'";
        $upd_res = pupe_query($query);

        $_go = false;
      }

      require "tilauskasittely/varastoon.inc";
    }
  }

  if ($automaattijalkilaskenta) {

    if (!isset($alv_talteen)) $alv_talteen = "";

    $laskurow = saapumisen_pyoristyserot($osrow, $laskurow, $otunnus, 1, $alv_talteen);

    if ($laskurow['kohdistettu'] == 'K') {

      // laitetaan lasku suoraan maksuvalmiiksi
      $query = "UPDATE lasku set
                tila          = 'M',
                hyvak1        = 'verkkolas',
                h1time        = now(),
                hyvak2        = '',
                hyvak3        = '',
                hyvak4        = '',
                hyvak5        = '',
                hyvaksyja_nyt = '',
                comments      = TRIM(CONCAT(comments, '\n Lasku siirrettiin suoraan maksuvalmiiksi, koska se t‰sm‰si ostotilaukseen.'))
                WHERE tunnus  = '{$tunnus}'
                AND tila      = 'H'";
      $xresult = pupe_query($query);

      list($_kaikkivarastossayhteensa, $_kaikkiliitettyyhteensa, $_kohdistus, $_kohok, $_kplvarasto, $_kplyhteensa, $_lisatiedot, $_lisok, $_llrow, $_sarjanrook, $_sarjanrot, $_uusiot, $_varastopaikat, $_varastossaarvo, $_liitettyarvo, $_varok) = tsekit($laskurow, 0, 0);

      if ($_lisok == 1 and $_kohok == 1 and $_varok == 1 and $_kplyhteensa == $_kplvarasto and $_sarjanrook == 1 and $_llrow["volasku"] == $_llrow["volasku_ok"] and $_llrow["kulasku"] == $_llrow["kulasku_ok"]) {

        $toiminto = "kaikkiok";
        $tee = "varma";

        // vied‰‰n varastoon TEHDƒƒN LOPPUKALKYYLI
        require "tilauskasittely/varastoon.inc";
      }
    }
  }
}

// t‰m‰n keikan voi vied‰ saldoille ja laskuttaa
if ($kohdistettujariveja > 0 and $kauttalaskutus == "kylla") {

  $otunnus = $keikantunnus;

  $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  $laskuvirhe .= t("Vied‰‰n kauttalaskutus varastoon").". ($laskurow[laskunro])\n";

  $tullaan_automaattikohdistuksesta = false;

  $toiminto = "kalkyyli";
  $tee = "varastoon";

  // vied‰‰n varastoon...
  require "tilauskasittely/varastoon.inc";

  // laskutetaan suoratoimitetut jtrivit jos niit‰ oli t‰ll‰ keikalla...
  $toiminto = "kaikkiok";
  $tee = "varma";

  $laskurow = saapumisen_pyoristyserot($osrow, $laskurow, $otunnus);

  // vied‰‰n varastoon TEHDƒƒN LOPPUKALKYYLI
  require "tilauskasittely/varastoon.inc";

  require "tilauskasittely/laskuta_kauttalaskutus.inc";
}
elseif ($kohdistettujariveja == 0 and $aladellaa == "") {

  if ((int) $keikkanumeroapu > 0) {
    // dellataan perustettu keikka ku ei kerran lˆytyny rivej‰.. jos ei olla asetettu ƒLƒDELLAA muuttujaaa
    $query = "DELETE from lasku WHERE yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkanumeroapu'";
    $result = pupe_query($query);
  }

  $laskuvirhe .= t("Poistettiin")." ".t("saapuminen")." $keikkanumeroapu ".t("Yht‰‰n rivi‰ ei lˆytynyt")."!\n";
}
else {
  $laskuvirhe .= t("Saapuminen k‰sitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
}
