<?php

function piirra_myyjien_myynnit($lisa, $pvmalku, $pvmloppu, $toim, $kuluprosentti,
  $osastojen_nimet, $konserni) {
  global $kukarow, $yhtiorow;

  $valuuttakurssi = array();
  
  if ($konserni != "") {
    $query = "SELECT yhtio, valkoodi
              from yhtio
              where konserni  = '$yhtiorow[konserni]'
              and konserni   != ''";
    $result = pupe_query($query);
    
    while ($rivi = mysql_fetch_assoc($result)) {
      
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
      $summa[$row["nimi"]][$row["kausi"]][1] += round($row["summa"] * $valuuttakurssi[$valyhtio], 0);      
      
      $kate[$row["nimi"]][$row["kausi"]][1] += round($row["kate"] * $valuuttakurssi[$valyhtio], 0);
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
              round(sum(tilausrivi.tilkpl * tilausrivi.hinta* (1 - tilausrivi.ale1 / 100) * (1 - tilausrivi.ale2 / 100) * (1 - tilausrivi.ale3 / 100)), 2) summa
              FROM lasku use index (yhtio_tila_tapvm)
              left join tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
              LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
              {$tuote_lisa}
              WHERE lasku.yhtio in ($yhtiot)
              {$lisa}
              and lasku.tila   in ('N', 'L')
              and lasku.alatila != 'X'
              and lasku.luontiaika   >= '$pvmalku'
              and lasku.luontiaika   <= '$pvmloppu'
              {$q_lisa}
              HAVING summa <> 0
              {$q_lisa2}";
    $amresult = pupe_query($amquery);
      
    if (mysql_num_rows($amresult) < 1) {
      return;
    }
   
    while ($amrow = mysql_fetch_array($amresult)) {
      $myyja_nimi[$amrow["myyja"]] = $amrow["nimi"];
      $valyhtio = $amrow["yhtio"];
      $summa[$amrow["nimi"]][$amrow["kausi"]][2] += round($amrow["summa"] * $valuuttakurssi[$valyhtio], 0);      
      
    }
  }

  $sarakkeet = 0;
  $raja = '0000-00';
  $rajataulu = array();

  // Piirret‰‰n headerit
  echo "<table>";
  echo "<tr>";
  echo "<th>" . t("Myyj‰") . "</th>";

  if ($toim == "TARKKA") {
    echo "<th></th>";
  }

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
  $yhteensa_avoimet_kausi = array();

  foreach ($summa as $myyja => $kausi_array) {

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

    $yhteensa_summa = 0;
    $yhteensa_avoimet = 0;

    if ($toim == "TARKKA") {
      $yhteensa_kate = array();
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
      if (!isset($yhteensa_avoimet_kausi[$kausi])) {
        $yhteensa_avoimet_kausi[$kausi] = 0;
      }
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

      if ($konserni != "") {
        if (isset($kausi_array[$kausi][2])) {
          $avoimetsumma = $kausi_array[$kausi][2];
        }
        elseif (isset($kausi_array[$kausi][1])) {
          $avoimetsumma = "0";
        }
        else  {
          $avoimetsumma = "";
        } 
        $yhteensa_avoimet += $avoimetsumma;
        $yhteensa_avoimet_kausi[$kausi] += $avoimetsumma;
        echo "<br>&nbsp";
      }
 
      echo "</td>";
    }

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
    if ($konserni != '') echo "<br>{$yhteensa_avoimet}";

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
     
  if ($toim == "TARKKA") {
    echo "<th></th>";
  }

  $yhteensa_summa = 0;
  $yhteensa_kate = 0;
  $yhteensa_avoimet = 0;

  foreach ($rajataulu as $kausi) {
    $yhteensa_summa += $yhteensa_summa_kausi[$kausi];
    $yhteensa_kate += $yhteensa_kate_kausi[$kausi];
    $yhteensa_avoimet += $yhteensa_avoimet_kausi[$kausi];

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
      round($yhteensa_kate_kausi[$kausi]) . "<br>$kate_prosentti%<br>$yhteensa_avoimet_kausi[$kausi]</th>";
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
