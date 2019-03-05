<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Tulosseuranta")."</font><hr>";

// Ehdotetaan oletuksena ehdotusta edelliselle kuuakudelle
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($ppl)) $ppl = date("d", mktime(0, 0, 0, date("m"), 0, date("Y")));
if (!isset($kkl)) $kkl = date("m", mktime(0, 0, 0, date("m"), 0, date("Y")));
if (!isset($vvl)) $vvl = date("Y", mktime(0, 0, 0, date("m"), 0, date("Y")));

// Päivämäärätarkistus
if (!checkdate($kka, $ppa, $vva)) {
  echo "<font class='error'>".t("Virheellinen alkupäivä!")."</font><br><br>";
  $tee = "";
}
else {
  $alku_pvm = date("Y-m-d", mktime(0, 0, 0, $kka, $ppa, $vva));
  $alku_pvm_tsek = date("Ymd", mktime(0, 0, 0, $kka, $ppa, $vva));
}

if (!checkdate($kkl, $ppl, $vvl)) {
  echo "<font class='error'>".t("Virheellinen loppupäivä!")."</font><br><br>";
  $tee = "";
}
else {
  $loppu_pvm = date("Y-m-d", mktime(0, 0, 0, $kkl, $ppl, $vvl));
}

if ($tee != "" and $alku_pvm > $loppu_pvm) {
  echo "<font class='error'>".t("Virheelliset kaudet!")." $alku_pvm > $loppu_pvm</font><br><br>";
  $tee = "";
}
else {
  // Haetaan ko. ajan tilikausi
  $query = "SELECT tilikausi_alku
            FROM tilikaudet
            WHERE tilikaudet.yhtio = '{$kukarow['yhtio']}'
            AND tilikausi_alku     <= '$alku_pvm'
            AND tilikausi_loppu    >= '$loppu_pvm'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo "<font class='error'>".t("Valitulle ajanjaksolle ei löydy tilikautta!")." $alku_pvm - $loppu_pvm</font><br><br>";
    $tee = "";
  }
  else {
    $row = mysql_fetch_assoc($result);
    $tilikausi_alku = $row["tilikausi_alku"];
  }
}

// Käyttöliittymä
echo "<form method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='raportoi'>";

echo "<table>";

echo "<tr>";
echo "<th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td>";
echo "<input type='text' name='ppa' value='$ppa' size='5'>";
echo "<input type='text' name='kka' value='$kka' size='5'>";
echo "<input type='text' name='vva' value='$vva' size='5'>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td>";
echo "<input type='text' name='ppl' value='$ppl' size='5'>";
echo "<input type='text' name='kkl' value='$kkl' size='5'>";
echo "<input type='text' name='vvl' value='$vvl' size='5'>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Kustannuspaikka")."</th>";
echo "<td>";

$monivalintalaatikot = array("KUSTP");
$noautosubmit = TRUE;
$piirra_otsikot = FALSE;
$lisa = "";
$lisa_haku_kustp = "";

require "tilauskasittely/monivalintalaatikot.inc";

echo "</td>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='submit' value='".t("Aja raportti")."'>";

echo "</form>";

// Tehdään raportti
if ($tee == "raportoi") {

  // Tähän arrayseen kerätään raportti
  $tulosseuranta = array();
  $include_20 = " and taso.taso != '20' ";
  $include_3600 = " and tili.tilino != '3600' ";

  echo "<br><br>";

  if ($alku_pvm_tsek < 20190101) {
    // Katsotaan halutaanko hakea myynti/varastonmuutos myynnin puolelta
    $query = "SELECT *
              FROM taso
              WHERE yhtio         = '{$kukarow["yhtio"]}'
              AND tyyppi          = 'B'
              AND summattava_taso LIKE '%nettokate%'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 0) {

      // Haetaan myynti/kate tilausriveiltä
      $query = "SELECT tuote.yhtio, sum(if(tilausrivi.laskutettuaika >= '$alku_pvm'
                AND tilausrivi.laskutettuaika  <= '$loppu_pvm', tilausrivi.rivihinta, 0)) myyntinyt, sum(if(tilausrivi.laskutettuaika >= '$alku_pvm'
                AND tilausrivi.laskutettuaika  <= '$loppu_pvm', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokatenyt
                FROM lasku use index (yhtio_tila_tapvm)
                JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
                JOIN tilausrivi use index (uusiotunnus_index) ON (tilausrivi.yhtio=lasku.yhtio
                AND tilausrivi.uusiotunnus=lasku.tunnus
                AND tilausrivi.tyyppi='L')
                JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio
                and tuote.tuoteno=tilausrivi.tuoteno)
                JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio
                AND asiakas.tunnus             = lasku.liitostunnus
                AND asiakas.myynninseuranta    = '' )
                LEFT JOIN toimitustapa ON (lasku.yhtio=toimitustapa.yhtio
                and lasku.toimitustapa=toimitustapa.selite)
                WHERE lasku.yhtio              = '{$kukarow["yhtio"]}'
                AND lasku.tila                 = 'U'
                AND lasku.alatila              = 'X'
                AND lasku.tapvm                >= '$alku_pvm'
                AND lasku.tapvm                <= '$loppu_pvm'
                AND tuote.myynninseuranta      = ''
                AND tilausrivi.tuoteno        != 'MAKSUERÄ'
                $lisa_haku_kustp";
      $result = pupe_query($query);
      $row = mysql_fetch_assoc($result);

      $tulosseuranta["myynti"]["summa"] = $row["myyntinyt"];
      $tulosseuranta["myynti"]["nimi"]  = "Myynti";
      $tulosseuranta["nettokate"]["summa"] = $row["nettokatenyt"];
      $tulosseuranta["nettokate"]["nimi"]  = "Nettokate";
    }
  }
  else {
    $include_20 = "";
    $include_3600 = "";
  }

  // Haetaan kaikki tulosseurannan tasot sekä katsotaan löytyykö niille kirjauksia
  $query = "SELECT taso.taso,
            taso.nimi,
            taso.summattava_taso,
            taso.kerroin,
            taso.jakaja,
            taso.kumulatiivinen,
            round(ifnull(sum(tiliointi.summa) * -1, taso.oletusarvo)) summa,
            group_concat(distinct concat('\'', tili.tilino, '\'')) tilit
            FROM taso
            LEFT JOIN tili ON (tili.yhtio = taso.yhtio
              AND tili.tulosseuranta_taso = taso.taso
              {$include_3600})
            LEFT JOIN tiliointi ON (tiliointi.yhtio = tili.yhtio
              AND tiliointi.tilino        = tili.tilino
              AND tiliointi.tapvm         >= '$alku_pvm'
              AND tiliointi.tapvm         <= '$loppu_pvm'
              AND tiliointi.korjattu      = ''
              $lisa)
            WHERE taso.yhtio              = '{$kukarow['yhtio']}'
            AND taso.tyyppi               = 'B'
            {$include_20}
            GROUP BY taso.taso, taso.nimi, taso.summattava_taso, taso.kerroin, taso.jakaja, taso.kumulatiivinen
            ORDER BY taso.taso+1";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {

    // Jos taso tulee olla kumulatiivinen, haetaan tason kumulatiivinen summa kirjanpidosta
    if ($row["kumulatiivinen"] == "X" and $row["tilit"] != "") {
      $query = "SELECT round(ifnull(sum(tiliointi.summa) * -1, 0)) summa
                FROM tiliointi
                WHERE tiliointi.yhtio  = '{$kukarow["yhtio"]}'
                AND tiliointi.tilino   in ({$row["tilit"]})
                AND tiliointi.tapvm    >= '$tilikausi_alku'
                AND tiliointi.tapvm    < '$alku_pvm'
                AND tiliointi.korjattu = ''
                $lisa";
      $kumulatiivinen_result = pupe_query($query);
      $kumulatiivinen_row = mysql_fetch_assoc($kumulatiivinen_result);
      $row["summa"] += $kumulatiivinen_row["summa"];
    }

    $tulosseuranta[$row["taso"]]["summa"]           = $row["summa"];
    $tulosseuranta[$row["taso"]]["nimi"]            = $row["nimi"];
    $tulosseuranta[$row["taso"]]["summattava_taso"] = $row["summattava_taso"];
    $tulosseuranta[$row["taso"]]["kerroin"]         = $row["kerroin"];
    $tulosseuranta[$row["taso"]]["jakaja"]          = $row["jakaja"];
  }

  if (count($tulosseuranta) > 0) {

    echo "<table>";

    echo "<tr>";
    echo "<th>".t("Nimi")."</th>";
    echo "<th style='text-align:right;'>".t("Summa")."</th>";
    echo "</tr>";

    foreach ($tulosseuranta as $taso => $row) {

      $summa = $row["summa"];

      // Piilotetaan nettokate-solu
      if ($include_3600 != "" and strtolower($row["nimi"]) == strtolower("nettokate")) {
        continue;
      }

      // Jos tasoon halutaan summata mukaan muita tasoja
      foreach (explode(",", $row["summattava_taso"]) as $summattava_taso) {
        $summattava_taso = trim($summattava_taso);
        if ($summattava_taso != "") {
          $summa += $tulosseuranta[$summattava_taso]["summa"];
        }
      }

      // Kerrotaan luku jos halutaan
      if ($row["kerroin"] != 0) {
        $summa *= $row["kerroin"];
      }

      // Jaetaan luku jos halutaan
      if ($row["jakaja"] != 0) {
        $summa /= $row["jakaja"];
      }

      // Pyöristetään summasta desimaalit pois
      $summa = round($summa);

      // Laitetaan summa alkuperäiseen arrayseen mukaan, jos tätä halutaan summailla vielä seuraavilla tasoilla
      $tulosseuranta[$taso]["summa"] = $summa;

      // Jos meillä on summattava taso, laitetaan speciaali riviväri
      $rivi_class = ($row["summattava_taso"] != "") ? "spec" : "";

      echo "<tr class='aktiivi $rivi_class'>";
      echo "<td>{$row["nimi"]}</td>";
      echo "<td style='text-align:right;'>{$summa}</td>";
      echo "</tr>";
    }

    echo "</table>";

  }
  else {
    echo "<font class='error'>".t("Ei löytynyt yhtään tulosseuranta-tasoa!")."</font>";
  }
}

require "inc/footer.inc";
