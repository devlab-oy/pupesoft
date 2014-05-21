<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require('../inc/parametrit.inc');

echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

// Käyttöliittymä
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
  echo "<font class='error'>".t("Päivämäärävirhe")."!</font>";
  $tee = "";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='kaikki'>";

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
echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
echo "</tr>";
echo "</table>";
echo "<br>";

if ($tee != '') {

  // myynnit
  $query = "SELECT lasku.myyja,
            kuka.nimi,
            date_format(lasku.tapvm,'%Y/%m') kausi,
            round(sum(lasku.arvo),0) summa,
            round(sum(lasku.kate),0) kate
            FROM lasku use index (yhtio_tila_tapvm)
            LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            and lasku.tila    = 'L'
            and lasku.alatila = 'X'
            and lasku.tapvm   >= '$pvmalku'
            and lasku.tapvm   <= '$pvmloppu'
            GROUP BY myyja, nimi, kausi
            HAVING summa <> 0 OR kate <> 0
            ORDER BY myyja";
  $result = pupe_query($query);

  $summa = array();
  $kate = array();
  $myyja_nimi = array();

  while ($row = mysql_fetch_array ($result)) {
    $myyja_nimi[$row["myyja"]] = $row["nimi"];
    $summa[$row["myyja"]][$row["kausi"]] = $row["summa"];
    $kate[$row["myyja"]][$row["kausi"]] = $row["kate"];
  }

  $sarakkeet  = 0;
  $raja     = '0000-00';
  $rajataulu   = array();

  // Piirretään headerit
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Myyjä")."</th>";

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

    $yhteensa_summa = 0;
    $yhteensa_kate = 0;

    foreach ($rajataulu as $kausi) {

      if (!isset($yhteensa_summa_kausi[$kausi])) $yhteensa_summa_kausi[$kausi] = 0;
      if (!isset($yhteensa_kate_kausi[$kausi])) $yhteensa_kate_kausi[$kausi] = 0;

      $summa = isset($kausi_array[$kausi]) ? $kausi_array[$kausi] : "";
      $kate_summa = isset($kate[$myyja][$kausi]) ? $kate[$myyja][$kausi] : "";

      $yhteensa_summa += $summa;
      $yhteensa_kate += $kate_summa;

      $yhteensa_summa_kausi[$kausi] += $summa;
      $yhteensa_kate_kausi[$kausi] += $kate_summa;

      echo "<td style='text-align:right;'>$summa<br>$kate_summa</td>";
    }

    echo "<td style='text-align:right;'>$yhteensa_summa<br>$yhteensa_kate</td>";
    echo "</tr>";
  }

  // Piirretään yhteensärivi
  echo "<tr>";
  echo "<th>".t("Yhteensä summa")."<br>".t("Yhteensä kate")."<br>".t("Kateprosentti")."</th>";

  $yhteensa_summa = 0;
  $yhteensa_kate = 0;

  foreach ($rajataulu as $kausi) {
    $yhteensa_summa += $yhteensa_summa_kausi[$kausi];
    $yhteensa_kate += $yhteensa_kate_kausi[$kausi];
    $kate_prosentti = round($yhteensa_kate_kausi[$kausi] / $yhteensa_summa_kausi[$kausi] * 100);
    echo "<th style='text-align:right;'>$yhteensa_summa_kausi[$kausi]<br>$yhteensa_kate_kausi[$kausi]<br>$kate_prosentti%</th>";

  }

  $kate_prosentti = round($yhteensa_kate / $yhteensa_summa * 100);
  echo "<th style='text-align:right;'>$yhteensa_summa<br>$yhteensa_kate<br>$kate_prosentti%</th>";
  echo "</tr>";
  echo "</table>";

}

require ("inc/footer.inc");
