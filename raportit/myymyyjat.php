<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require '../inc/parametrit.inc';

if ($toim == "TARKKA") {
  echo "<script src='myymyyjat.js'></script>";
}

echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

// Käyttöliittymä
if ($toim == "TARKKA") {
  $muuttujat = muistista("myymyyjat", "oletus");

  if (!isset($alkukk) and $muuttujat["alkukk"]) {
    $alkukk = $muuttujat["alkukk"];
  }
  if (!isset($alkuvv) and $muuttujat["alkuvv"]) {
    $alkuvv = $muuttujat["alkuvv"];
  }
  if (!isset($kuluprosentti) and $muuttujat["kuluprosentti"]) {
    $kuluprosentti = $muuttujat["kuluprosentti"];
  }
  if (!isset($loppukk) and $muuttujat["loppukk"]) {
    $loppukk = $muuttujat["loppukk"];
  }
  if (!isset($loppuvv) and $muuttujat["loppuvv"]) {
    $loppuvv = $muuttujat["loppuvv"];
  }
  if (!isset($mul_laskumyyja) and $muuttujat["mul_laskumyyja"]) {
    $mul_laskumyyja = $muuttujat["mul_laskumyyja"];
  }
  if (!isset($mul_osasto) and $muuttujat["mul_osasto"]) {
    $mul_osasto = $muuttujat["mul_osasto"];
  }

  if (!isset($alkukk)) $alkukk = date("m");
  if (!isset($alkuvv)) $alkuvv = date("Y");
  if (!isset($loppukk)) $loppukk = date("m");
  if (!isset($loppuvv)) $loppuvv = date("Y");
}
else {
  if (!isset($alkukk)) $alkukk = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
  if (!isset($alkuvv)) $alkuvv = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
  if (!isset($loppukk)) $loppukk = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
  if (!isset($loppuvv)) $loppuvv = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
}
$tee = isset($tee) ? trim($tee) : "";

if (checkdate($alkukk, 1, $alkuvv) and checkdate($loppukk, 1, $loppuvv)) {
  // MySQL muodossa
  $pvmalku = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
  $pvmloppu = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));
}
else {
  echo "<font class='error'>".t("Päivämäärävirhe")."!</font>";
  $tee = "";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' id='myymyyjatTee' value='kaikki'>";

if ($toim == "TARKKA") {
  $classes = 'hidden';
  $noautosubmit = true;
  $monivalintalaatikot = array('laskumyyja', 'osasto');
  $tuote_lisa = "INNER JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                   AND tilausrivi.otunnus = lasku.tunnus)
                 INNER JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                   AND tuote.tuoteno = tilausrivi.tuoteno)";
  $summa_lisa = "tilausrivi.rivihinta";
  $kate_lisa = "tilausrivi.kate";
  $osasto_lisa = "tuote.osasto,";

  $osasto_result = t_avainsana("OSASTO");

  $osastojen_nimet = array();

  while ($osasto = mysql_fetch_assoc($osasto_result)) {
    $osastojen_nimet[$osasto["selite"]] = $osasto["selitetark"];
  }
}
else {
  $classes = '';
  $lisa = "";
  $tuote_lisa = "";
  $summa_lisa = "lasku.arvo";
  $kate_lisa = "lasku.kate";
  $osasto_lisa = "";
}

echo "<div id='valinnat' class='{$classes}'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Anna alkukausi (kk-vuosi)")."</th>";
echo "  <td>
    <input type='text' name='alkukk' value='$alkukk' size='2'>-
    <input type='text' name='alkuvv' value='$alkuvv' size='5'>
    </td>";
echo "</tr>";

echo "<th>".t("Anna loppukausi (kk-vuosi)")."</th>";
echo "  <td>
    <input type='text' name='loppukk' value='$loppukk' size='2'>-
    <input type='text' name='loppuvv' value='$loppuvv' size='5'>
    </td>";
echo "</tr>";

if ($toim == "TARKKA") {
  foreach ($osastojen_nimet as $osasto => $osaston_nimi) {
    echo "<tr>";
    echo "<th>";
    echo "<label for='kuluprosentti_{$osasto}'>" . t("Kuluprosentti osastolle") .
         " {$osaston_nimi}</label>";
    echo "</th>";
    echo "<td>";
    echo "<input type='number' id='kuluprosentti_{$osasto}' name='kuluprosentti[{$osasto}]' min='0'
                 max='100' value='{$kuluprosentti[$osasto]}'>";
    echo "</td>";
    echo "</tr>";
  }
}

echo "</table>";

if ($toim == "TARKKA") {
  require_once "tilauskasittely/monivalintalaatikot.inc";

  echo "<input type='button' id='tallennaNappi' value='" . t("Tallenna haku oletukseksi") . "'>";
}

echo "</div>";

echo "<table>";
echo "<tbody>";
echo "<tr>";
echo "<td class='back'><input type='submit' value='" . t("Aja raportti") . "'>";

if ($toim == "TARKKA") {
  echo "<input id='naytaValinnat' type='button' value='" . t("Näytä valinnat") . "'>";
}

echo "</td>";
echo "</tr>";
echo "</tbody>";
echo "</table>";

echo "<br>";

if ($tee == "tallenna_haku") {
  $muistettavat = array(
    "alkukk"         => $alkukk,
    "alkuvv"         => $alkuvv,
    "loppukk"        => $loppukk,
    "loppuvv"        => $loppuvv,
    "kuluprosentti"  => $kuluprosentti,
    "mul_laskumyyja" => $mul_laskumyyja,
    "mul_osasto"     => $mul_osasto
  );

  muistiin("myymyyjat", "oletus", $muistettavat);
}

if ($tee != '') {

  // myynnit
  $query = "SELECT lasku.myyja,
            kuka.nimi,
            {$osasto_lisa}
            date_format(lasku.tapvm,'%Y/%m') kausi,
            round(sum({$summa_lisa}),0) summa,
            round(sum({$kate_lisa}),0) kate
            FROM lasku use index (yhtio_tila_tapvm)
            LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
            {$tuote_lisa}
            WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            {$lisa}
            and lasku.tila    = 'L'
            and lasku.alatila = 'X'
            and lasku.tapvm   >= '$pvmalku'
            and lasku.tapvm   <= '$pvmloppu'
            GROUP BY myyja, {$osasto_lisa} nimi, kausi
            HAVING summa <> 0 OR kate <> 0
            ORDER BY myyja";
  $result = pupe_query($query);

  if (mysql_num_rows($result) < 1) {
    echo "<span class='error'>" . t("Valituilla asetuksilla ei löytynyt myyntiä") . "</span>";
    require_once "inc/footer.inc";
    exit;
  }

  $summa = array();
  $kate = array();
  $myyja_nimi = array();

  while ($row = mysql_fetch_array($result)) {
    $myyja_nimi[$row["myyja"]] = $row["nimi"];
    $summa[$row["myyja"]][$row["kausi"]] += $row["summa"];

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
      $kate[$row["myyja"]][$row["kausi"]] = $row["kate"];
    }
  }

  $sarakkeet  = 0;
  $raja     = '0000-00';
  $rajataulu   = array();

  // Piirretään headerit
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Myyjä")."</th>";

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

    if ($kk < 10) $kk = '0'.$kk;

    $rajataulu[$sarakkeet] = "$vuosi/$kk";
    $sarakkeet++;
    $raja = $vuosi."-".$kk;

    echo "<th>$vuosi/$kk</th>";
  }

  echo "<th>".t("Yhteensä")."</th>";
  echo "</tr>";

  // Piirretään itse data
  $yhteensa_summa_kausi = array();
  $yhteensa_kate_kausi = array();

  foreach ($summa as $myyja => $kausi_array) {

    echo "<tr class='aktiivi'>";
    echo "<td>$myyja_nimi[$myyja] ($myyja)</td>";

    if ($toim == "TARKKA") {
      echo "<td>" . t("Liikevaihto");

      $myyjan_osastot = array_unique($kate[$myyja]["osastot"]);

      foreach ($myyjan_osastot as $osasto) {
        if ($osastojen_nimet[$osasto]) {
          echo "<br>{$osastojen_nimet[$osasto]}";
        }
        else {
          echo "<br>" . t("Ei osastoa");
        }
      }

      echo "<br>Katteiden summa";

      echo "</td>";
    }

    $yhteensa_summa = 0;

    if ($toim == "TARKKA") {
      $yhteensa_kate = array();
    }
    else {
      $yhteensa_kate = 0;
    }

    foreach ($rajataulu as $kausi) {

      if (!isset($yhteensa_summa_kausi[$kausi])) $yhteensa_summa_kausi[$kausi] = 0;
      if (!isset($yhteensa_kate_kausi[$kausi])) $yhteensa_kate_kausi[$kausi] = 0;

      $summa = isset($kausi_array[$kausi]) ? $kausi_array[$kausi] : "";

      if ($toim != "TARKKA") {
        $kate_summa = isset($kate[$myyja][$kausi]) ? $kate[$myyja][$kausi] : "";
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

    echo "</td>";
    echo "</tr>";
  }

  // Piirretään yhteensärivi
  echo "<tr>";
  echo "<th>".t("Yhteensä summa")."<br>".t("Yhteensä kate")."<br>".t("Kateprosentti")."</th>";

  if ($toim == "TARKKA") {
    echo "<th></th>";
  }

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

    echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]<br>" .
         round($yhteensa_kate_kausi[$kausi]) . "<br>$kate_prosentti%</th>";

  }

  $kate_prosentti = round($yhteensa_kate / $yhteensa_summa * 100);
  echo "<th style='text-align:right;'>$yhteensa_summa<br>" . round($yhteensa_kate) .
       "<br>$kate_prosentti%</th>";
  echo "</tr>";
  echo "</table>";

}

require "inc/footer.inc";
