<?php

function piirra_myyjien_myynnit($lisa, $pvmalku, $pvmloppu, $toim, $kuluprosentti,
  $osastojen_nimet, $konserni) {
  global $kukarow, $yhtiorow;

  $valuuttakurssi = array();
  $yhtionimi = array();

  if ($konserni != "") {
    $query = "SELECT yhtio, valkoodi, nimi
              from yhtio
              where konserni  = '$yhtiorow[konserni]'
              and konserni   != ''";
    $result = pupe_query($query);

    while ($rivi = mysql_fetch_assoc($result)) {
      $yhtionimi[$rivi["yhtio"]] = $rivi["nimi"];
      $valquery = "SELECT yhtio, nimi, kurssi
                FROM valuu
                WHERE valuu.yhtio = '$yhtiorow[yhtio]'
                AND valuu.nimi    = '$rivi[valkoodi]'";
      $valresult = pupe_query($valquery);
      $valrow = mysql_fetch_array($valresult);

      $valuuttakurssi[$rivi["yhtio"]] = $valrow["kurssi"];
      $yhtiot .= "'{$rivi["yhtio"]}'".",";
    }
    if ($yhtiot != "") $yhtiot = substr($yhtiot, 0, -1);
  }
  else {
    $yhtiot = "'{$kukarow["yhtio"]}'";
  }

  if ($toim == "TARKKA") {
    $tuote_lisa = "INNER JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                     AND tilausrivi.otunnus = lasku.tunnus)
                   INNER JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                     AND tuote.tuoteno = tilausrivi.tuoteno)";
    $summa_lisa = "tilausrivi.rivihinta";
    $kate_lisa = "tilausrivi.kate";
    $osasto_lisa = "tuote.osasto,";
  }
  else {
    $tuote_lisa = "";
    $summa_lisa = "lasku.arvo";
    $kate_lisa = "lasku.kate";
    $osasto_lisa = "";
  }

  $q_lisa = $konserni == '' ? "GROUP BY myyja, {$osasto_lisa} nimi, kausi"
                           : "GROUP BY {$osasto_lisa} nimi, yhtio, kausi";
  $q_lisa2 = $konserni == '' ? "ORDER BY myyja, {$osasto_lisa} kausi" : "ORDER BY kuka.nimi, yhtio, kausi";

  $summa_lisa2 = "round(sum({$summa_lisa}),0) summa,";

  // myynnit
  $query = "SELECT lasku.yhtio,
            lasku.myyja,
            kuka.nimi,
            lasku.valkoodi,
            {$osasto_lisa}
            date_format(lasku.tapvm,'%Y/%m') kausi,
            $summa_lisa2
            round(sum({$kate_lisa}),0) kate
            FROM lasku use index (yhtio_tila_tapvm)
            LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
            {$tuote_lisa}
            WHERE lasku.yhtio in ($yhtiot)
            {$lisa}
            and lasku.tila    = 'L'
            and lasku.alatila = 'X'
            and lasku.tapvm   >= '$pvmalku'
            and lasku.tapvm   <= '$pvmloppu'
            {$q_lisa}
            HAVING summa <> 0 OR kate <> 0
            {$q_lisa2}";
  $result = pupe_query($query);

  if (mysql_num_rows($result) < 1) {
    return;
  }

  $summa = array();
  $kate = array();
  $myyja_nimi = array();
  $avoimet = array();

  while ($row = mysql_fetch_array($result)) {
    if ($konserni == "") {
      $myyja_nimi[$row["myyja"]] = $row["nimi"];
      $summa[$row["myyja"]][$row["kausi"]][1] += $row["summa"];

      if ($toim == "TARKKA") {
        if ($kuluprosentti[$row["osasto"]] != 0) {
          $kerroin = (float) $kuluprosentti[$row["osasto"]] / 100;

          $kate[$row["myyja"]][$row["kausi"]][$row["osasto"]] += ($row["kate"] -
            $kerroin * $row["kate"]);
        }
        else {
          $kate[$row["myyja"]][$row["kausi"]][$row["osasto"]] += $row["kate"];
        }
        $kate[$row["myyja"]]["osastot"][] = $row["osasto"];
      }
      else {
        $kate[$row["myyja"]][$row["kausi"]][1] = $row["kate"];
      }
    }
    else {
      $myyja_nimi[$row["myyja"]] = $row["nimi"];
      $valyhtio = $row["yhtio"];
      $summa[$row["nimi"]][$row["kausi"]][$row["yhtio"]] += round($row["summa"] * $valuuttakurssi[$valyhtio], 0);

      $kate[$row["nimi"]][$row["kausi"]][$row["yhtio"]] += round($row["kate"] * $valuuttakurssi[$valyhtio], 0);
    }
  }

  // Avoimet myynnit, kun ajetaan konsernilukuja
  if ($konserni != "") {
    $amquery = "SELECT lasku.yhtio,
              lasku.myyja,
              kuka.nimi,
              lasku.valkoodi,
              {$osasto_lisa}
              date_format(lasku.luontiaika,'%Y/%m') kausi,
              round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt)* (1 - tilausrivi.ale1 / 100) * (1 - tilausrivi.ale2 / 100) * (1 - tilausrivi.ale3 / 100)), 2) summa
              FROM lasku use index (yhtio_tila_tapvm)
              left join tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus = lasku.tunnus  and tilausrivi.tyyppi != 'D')
              LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
              {$tuote_lisa}
              WHERE lasku.yhtio in ($yhtiot)
              {$lisa}
              and lasku.tila   in ('N', 'L')
              and lasku.alatila != 'X'
              {$q_lisa}
              HAVING summa <> 0
              {$q_lisa2}";
    $amresult = pupe_query($amquery);

    if (mysql_num_rows($amresult) < 1) {
      return;
    }

    while ($amrow = mysql_fetch_array($amresult)) {
      $valyhtio = $amrow["yhtio"];
      $avoimet[$amrow["nimi"]] += round($amrow["summa"] * $valuuttakurssi[$valyhtio], 0);

    }
  }

  $sarakkeet = 0;
  $raja = '0000-00';
  $rajataulu = array();

  // Piirret‰‰n headerit
  echo "<table>";
  echo "<tr>";
  echo "<th>" . t("Myyj‰") . "</th>";

  #if ($toim == "TARKKA") {
    echo "<th></th>";
  #}

  while ($raja < substr($pvmloppu, 0, 7)) {

    $vuosi = substr($pvmalku, 0, 4);
    $kk = substr($pvmalku, 5, 2);
    $kk += $sarakkeet;

    if ($kk > 12) {
      $vuosi++;
      $kk -= 12;
    }

    if ($kk < 10) {
      $kk = '0' . $kk;
    }

    $rajataulu[$sarakkeet] = "$vuosi/$kk";
    $sarakkeet++;
    $raja = $vuosi . "-" . $kk;

    echo "<th>$vuosi/$kk</th>";
  }

  echo "<th>" . t("Yhteens‰") . "</th>";
  echo "</tr>";

  // Piirret‰‰n itse data
  $yhteensa_summa_kausi = array();
  $yhteensa_kate_kausi = array();
  $yhteensa_avoimet = 0;

  foreach ($summa as $myyja => $kausi_array) {

    $yhteensa_summa_myyja = array();
    $yhteensa_kate_myyja = array();
    $yhteensa_myyja_summa = 0;
    $yhteensa_myyja_kate = 0;

    echo "<tr class='aktiivi'>";
    echo "<td>$myyja_nimi[$myyja] ($myyja)</td>";

    if ($toim == "TARKKA") {
      echo "<td>" . t("Liikevaihto");

      $myyjan_osastot = array_unique($kate[$myyja]["osastot"]);

      foreach ($myyjan_osastot as $osasto) {
        if ($osastojen_nimet[$osasto]) {
          echo "<br>{$osastojen_nimet[$osasto]} " . t("kate");
        }
        else {
          echo "<br>" . t("Ei osastoa");
        }
      }

      echo "<br>" . t("Katteiden summa");

      echo "</td>";
    }
    elseif ($konserni == '') {
      echo "<td>" . t("Myynti");

      echo "<br>" . t("Kate");

      echo "</td>";
    }
    else {
      echo "<td style='white-space:nowrap'>" ;
      foreach ($yhtionimi as $yht => $yht_nimi) {
        echo "{$yht_nimi} " . t("Myynti") . "<br>";
        echo "{$yht_nimi} " . t("Kate") . "<br>";
      }
      echo t("Myynti yht.") . "<br>";
      echo t("Kate yht.") . "<br>";
      echo "" . t("Avoimet myynnit");

      echo "</td>";
    }


    $yhteensa_summa = 0;

    if ($toim == "TARKKA") {
      $yhteensa_kate = array();
    }
    elseif ($konserni != '') {
      $yhtio_summa = array();
      $yhtio_kate = array();
    }
    else {
      $yhteensa_kate = 0;
    }

    foreach ($rajataulu as $kausi) {

        if (!isset($yhteensa_summa_kausi[$kausi])) {
          $yhteensa_summa_kausi[$kausi] = 0;
        }
        if (!isset($yhteensa_kate_kausi[$kausi])) {
          $yhteensa_kate_kausi[$kausi] = 0;
        }

      if ($konserni == '') {
        $summa = isset($kausi_array[$kausi][1]) ? $kausi_array[$kausi][1] : "";

        if ($toim != "TARKKA") {
          $kate_summa = isset($kate[$myyja][$kausi][1]) ? $kate[$myyja][$kausi][1] : "";
          $yhteensa_kate += $kate_summa;
          $yhteensa_kate_kausi[$kausi] += $kate_summa;
        }

        $yhteensa_summa += $summa;

        $yhteensa_summa_kausi[$kausi] += $summa;

        echo "<td style='text-align:right;'>{$summa}";

        if ($toim == "TARKKA") {
          $katteet = $kate[$myyja];

          if ($katteet[$kausi]) {
            foreach ($myyjan_osastot as $osasto) {
              if ($katteet[$kausi][$osasto]) {
                $yhteensa_kate[$osasto] += $katteet[$kausi][$osasto];
                $yhteensa_kate_kausi[$kausi] += $katteet[$kausi][$osasto];

                echo "<br>" . round($katteet[$kausi][$osasto]);
              }
              else {
                echo "<br>";
              }
            }

            echo "<br><strong style='font-weight:bold'>" . round(array_sum($katteet[$kausi])) .
              "</strong>";
          }
        }
        else {
          echo "<br>{$kate_summa}";
        }
      }
      else  {
        echo "<td style='text-align:right;'nowrap>";
        foreach ($yhtionimi as $yht => $yht_nimi) {

          $summa = isset($kausi_array[$kausi][$yht]) ? $kausi_array[$kausi][$yht] : "";
          $kate_summa = isset($kate[$myyja][$kausi][$yht]) ? $kate[$myyja][$kausi][$yht] : "";

          $yhtio_summa[$yht] += $summa;
          $yhtio_kate[$yht] += $kate_summa;

          $yhteensa_summa_kausi[$kausi] += $summa;
          $yhteensa_kate_kausi[$kausi] += $kate_summa;
          $yhteensa_summa_myyja[$kausi] += $summa;
          $yhteensa_kate_myyja[$kausi] += $kate_summa;
          echo "{$summa}<br>";
          echo "{$kate_summa}<br>";

        }
        $yhteensa_myyja_summa += $yhteensa_summa_myyja[$kausi];
        $yhteensa_myyja_kate += $yhteensa_kate_myyja[$kausi];
        echo "{$yhteensa_summa_myyja[$kausi]}<br>";
        echo "{$yhteensa_kate_myyja[$kausi]}<br><br>";
      }
      echo "</td>";
    }
    if ($konserni == '') {
      echo "<td style='text-align:right;'>{$yhteensa_summa}";

      if ($toim == "TARKKA") {
        ksort($yhteensa_kate);

        foreach ($yhteensa_kate as $osaston_kate) {
          echo "<br>" . round($osaston_kate);
        }

        echo "<br><strong style='font-weight:bold'>" . round(array_sum($yhteensa_kate)) .
          "</strong>";
      }
      else {
        echo "<br>{$yhteensa_kate}";
      }
    }
    else {

      echo "<td style='text-align:right;'>";
      foreach ($yhtionimi as $yht => $yht_nimi) {
        echo "{$yhtio_summa[$yht]}<br>";
        echo "{$yhtio_kate[$yht]}<br>";
      }
    }
    if ($konserni != '') {

      if (isset($avoimet[$myyja])) {
        $avoimetsumma = $avoimet[$myyja];
      }
      else  {
        $avoimetsumma = 0;
      }

      echo "{$yhteensa_myyja_summa}<br>";
      echo "{$yhteensa_myyja_kate}<br>";
      echo "{$avoimetsumma}";
      $yhteensa_avoimet += $avoimetsumma;
    }

    echo "</td>";
    echo "</tr>";
  }

  // Piirret‰‰n yhteens‰rivi
  echo "<tr>";
  if ($konserni == '') {
    echo "<th>" . t("Yhteens‰ summa") . "<br>" . t("Yhteens‰ kate") . "<br>" . t("Kateprosentti") ."</th>";
  }
  else {
    echo "<th>" . t("Yhteens‰ summa") . "<br>" . t("Yhteens‰ kate") . "<br>" . t("Kateprosentti") . "<br>" . t("Yht. avoimet myynnit") ."</th>";
  }

  #if ($toim == "TARKKA") {
    echo "<th></th>";
  #}

  $yhteensa_summa = 0;
  $yhteensa_kate = 0;

  foreach ($rajataulu as $kausi) {
    $yhteensa_summa += $yhteensa_summa_kausi[$kausi];
    $yhteensa_kate += $yhteensa_kate_kausi[$kausi];

    if ($yhteensa_summa_kausi[$kausi] != 0) {
      $kate_prosentti = round($yhteensa_kate_kausi[$kausi] / $yhteensa_summa_kausi[$kausi] * 100);
    }
    else {
      $kate_prosentti = 0;
    }

    if ($konserni == '') {
      echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]<br>" .
      round($yhteensa_kate_kausi[$kausi]) . "<br>$kate_prosentti%</th>";
    }
    else {
      echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]<br>" .
      round($yhteensa_kate_kausi[$kausi]) . "<br>$kate_prosentti%<br>&nbsp</th>";
    }
  }

  $kate_prosentti = round($yhteensa_kate / $yhteensa_summa * 100);
  if ($konserni == '') {
    echo "<th style='text-align:right;'>$yhteensa_summa<br>" . round($yhteensa_kate) . "<br>$kate_prosentti%</th>";
  }
  else {
    echo "<th style='text-align:right;'>$yhteensa_summa<br>" . round($yhteensa_kate) . "<br>$kate_prosentti%<br>$yhteensa_avoimet</th>";
  }
  echo "</tr>";
  echo "</table>";
}
function hae_osastojen_nimet() {
  $osasto_result = t_avainsana("OSASTO");

  $osastojen_nimet = array();

  while ($osasto = mysql_fetch_assoc($osasto_result)) {
    $osastojen_nimet[$osasto["selite"]] = $osasto["selitetark"];
  }

  return $osastojen_nimet;
}
