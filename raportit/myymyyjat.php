<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require '../inc/parametrit.inc';

if ($toim == "TARKKA") {
  echo "<script src='myymyyjat.js'></script>";
}

echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

// K‰yttˆliittym‰
if (!isset($alkukk)) $alkukk = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
if (!isset($alkuvv)) $alkuvv = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
if (!isset($loppukk)) $loppukk = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($loppuvv)) $loppuvv = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
$tee = isset($tee) ? trim($tee) : "";

if (checkdate($alkukk, 1, $alkuvv) and checkdate($loppukk, 1, $loppuvv)) {
  // MySQL muodossa
  $pvmalku = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
  $pvmloppu = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));
}
else {
  echo "<font class='error'>".t("P‰iv‰m‰‰r‰virhe")."!</font>";
  $tee = "";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='kaikki'>";

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
echo "</table>";

if ($toim == "TARKKA") {
  require_once "tilauskasittely/monivalintalaatikot.inc";
}

echo "</div>";

echo "<table>";
echo "<tbody>";
echo "<tr>";
echo "<td class='back'><input type='submit' value='" . t("Aja raportti") . "'>";

if ($toim == "TARKKA") {
  echo "<input id='naytaValinnat' type='button' value='" . t("N‰yt‰ valinnat") . "'>";
}

echo "</td>";
echo "</tr>";
echo "</tbody>";
echo "</table>";

echo "<br>";

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
    echo "<span class='error'>" . t("Valitulla myyj‰ll‰ ei ole yht‰‰n myynti‰") . "</span>";
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
      $kate[$row["myyja"]][$row["kausi"]][$row["osasto"]] += $row["kate"];
    }
    else {
      $kate[$row["myyja"]][$row["kausi"]] = $row["kate"];
    }
  }

  $sarakkeet  = 0;
  $raja     = '0000-00';
  $rajataulu   = array();

  // Piirret‰‰n headerit
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Myyj‰")."</th>";

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

  echo "<th>".t("Yhteens‰")."</th>";
  echo "</tr>";

  // Piirret‰‰n itse data
  $yhteensa_summa_kausi = array();
  $yhteensa_kate_kausi = array();

  foreach ($summa as $myyja => $kausi_array) {

    echo "<tr class='aktiivi'>";
    echo "<td>$myyja_nimi[$myyja] ($myyja)</td>";

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
          foreach ($katteet[$kausi] as $osasto => $osaston_kate) {
            $yhteensa_kate[$osasto] += $osaston_kate;
            $yhteensa_kate_kausi[$kausi] += $osaston_kate;
            echo "<br>{$osaston_kate}";
          }
        }
      }
      else {
        echo "<br>{$kate_summa}";
      }

      echo "</td>";
    }

    echo "<td style='text-align:right;'>{$yhteensa_summa}";

    if ($toim == "TARKKA") {
      foreach ($yhteensa_kate as $osaston_kate) {
        echo "<br>{$osaston_kate}";
      }
    }
    else {
      echo "<br>{$yhteensa_kate}";
    }

    echo "</td>";
    echo "</tr>";
  }

  // Piirret‰‰n yhteens‰rivi
  echo "<tr>";
  echo "<th>".t("Yhteens‰ summa")."<br>".t("Yhteens‰ kate")."<br>".t("Kateprosentti")."</th>";

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

    echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]<br>$yhteensa_kate_kausi[$kausi]<br>$kate_prosentti%</th>";

  }

  $kate_prosentti = round($yhteensa_kate / $yhteensa_summa * 100);
  echo "<th style='text-align:right;'>$yhteensa_summa<br>$yhteensa_kate<br>$kate_prosentti%</th>";
  echo "</tr>";
  echo "</table>";

}

require "inc/footer.inc";
