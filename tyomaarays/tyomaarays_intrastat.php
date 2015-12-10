<?php

require '../inc/parametrit.inc';

enable_ajax();

if (isset($livesearch_tee) and $livesearch_tee == "TULLINIMIKEHAKU") {
  livesearch_tullinimikehaku();
  exit;
}

if (!isset($otunnus)) $otunnus = '';
if (!isset($tee)) $tee = '';

echo "<font class='head'>",t("Työmääräys intrastat"),"</font><hr><br>";

echo "<form method='post' action=''>";
echo "<input type='hidden' name='tee' value='etsi' />";
echo "<table>";
echo "<tr>";
echo "<th>",t("Työmääräys"),"</th>";
echo "<td><input type='text' name='otunnus' value='{$otunnus}' /></td>";
echo "<td class='back'><input type='submit' value='",t("Etsi"),"' /></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

if ($tee == 'tee_intrastat' and !empty($otunnus)) {

  $otunnus = (int) $otunnus;
  $bruttopaino = (float) str_replace(',', '.', $bruttopaino);
  $tulliarvo = (float) str_replace(',', '.', $tulliarvo);

  $query = "UPDATE lasku SET
            maa_lahetys = '{$maa_lahetys}',
            maa_maara = '{$maa_maara}',
            maa_alkupera = '{$maa_alkupera}',
            kauppatapahtuman_luonne = '{$kauppatapahtuman_luonne}',
            kuljetusmuoto = '{$kuljetusmuoto}',
            bruttopaino = '{$bruttopaino}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila = 'A'
            AND tunnus = '{$otunnus}'";
  $updres = pupe_query($query);

  $query = "UPDATE tyomaarays SET
            tullikoodi = '{$tullikoodi}',
            tulliarvo = '{$tulliarvo}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$otunnus}'";
  $updres = pupe_query($query);

  $tee = 'etsi';
}

if ($tee == 'etsi') {

  echo "<br><br>";

  $etsilisa = "";

  if (!empty($otunnus)) {
    $otunnus = (int) $otunnus;

    $etsilisa = "AND lasku.tunnus = '{$otunnus}'";
  }

  $query = "SELECT *,
            CONCAT_WS('<br>', lasku.nimi, lasku.nimitark) AS nimi,
            kuka1.nimi AS laatija
            FROM lasku
            JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila = 'A'
            {$etsilisa}";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>",t("Työmääräys"),"</th>";
    echo "<th>",t("Nimi"),"</th>";
    echo "<th>",t("Tuotekoodi"),"</th>";
    echo "<th>",t("Laatija"),"</th>";
    echo "<th class='back'></th>";
    echo "</tr>";
  }

  while ($row = mysql_fetch_assoc($res)) {
    echo "<tr>";
    echo "<td>{$row['tunnus']}</td>";
    echo "<td>{$row['nimi']}</td>";
    echo "<td>{$row['koodi']}</td>";
    echo "<td>{$row['laatija']}</td>";
    echo "<td class='back'>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='tee' value='syota_intrastat' />";
    echo "<input type='hidden' name='otunnus' value='{$row['otunnus']}' />";
    echo "<input type='submit' value='",t("Tuonti intrastat"),"' />";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
  }

  if (mysql_num_rows($res) > 0) {
    echo "</table>";
  }
  else {
    echo "<font class='message'>",t("Yhtään työmääräystä ei löytynyt"),".</font>";
  }
}

if ($tee == 'syota_intrastat' and !empty($otunnus)) {
  $otunnus = (int) $otunnus;

  $query = "SELECT *,
            CONCAT_WS('<br>', lasku.nimi, lasku.nimitark) AS nimi
            FROM lasku
            JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila = 'A'
            AND lasku.tunnus = '{$otunnus}'";
  $res = pupe_query($query);
  $row = mysql_fetch_assoc($res);

  echo "<br><br>";
  echo $row['nimi'];
  echo "<br><br>";

  echo "<form method='post' action='' name='paaformi'>";
  echo "<input type='hidden' name='otunnus' value='{$otunnus}' />";
  echo "<input type='hidden' name='tee' value='tee_intrastat' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>",t("Tullinimike"),"</th>";
  echo "<td>";
  echo "<br>";
  echo livesearch_kentta("paaformi", 'TULLINIMIKEHAKU', 'tullikoodi', 140, $row['tullikoodi'], 'EISUBMIT', '', '', 'ei_break_all');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Tulliarvo"),"</th>";
  echo "<td>";
  echo "<br>";
  echo "<input type='text' name='tulliarvo' value='{$row['tulliarvo']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Lähetysmaa"),"</th>";
  echo "<td>";
  echo "<select name='maa_lahetys'>";

  $query = "SELECT DISTINCT koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);

  echo "<option value=''>",t("Valitse"),"</option>";

  while ($maarow = mysql_fetch_assoc($result)) {
    $sel = $maarow['koodi'] == $row["maa_lahetys"] ? 'selected' : '';
    echo "<option value='{$maarow['koodi']}' {$sel}>{$maarow['nimi']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Määrämaa"),"</th>";
  echo "<td>";
  echo "<select name='maa_maara'>";

  $query = "SELECT DISTINCT koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);

  echo "<option value=''>",t("Valitse"),"</option>";

  if (empty($row["maa_maara"])) {
    $query = "SELECT varastopaikat.maa, count(*) kpl
              FROM lasku
              JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
                AND varastopaikat.tunnus = lasku.varasto)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus  = '{$row['tunnus']}'
              GROUP BY varastopaikat.maa
              ORDER BY kpl DESC
              LIMIT 1";
    $maaresult = pupe_query($query);

    if ($maarow = mysql_fetch_assoc($maaresult)) {
      $row["maa_maara"] = $maarow["maa"];
    }
    else {
      $row["maa_maara"] = $yhtiorow["maa"];
    }
  }

  while ($maarow = mysql_fetch_assoc($result)) {
    $sel = $maarow['koodi'] == $row["maa_maara"] ? 'selected' : '';
    echo "<option value='{$maarow['koodi']}' {$sel}>{$maarow['nimi']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Alkuperämaa"),"</th>";
  echo "<td>";
  echo "<select name='maa_alkupera'>";

  $query = "SELECT DISTINCT koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);

  echo "<option value=''>",t("Valitse"),"</option>";

  if (empty($row["maa_alkupera"])) {
    $query = "SELECT varastopaikat.maa, count(*) kpl
              FROM lasku
              JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
                AND varastopaikat.tunnus = lasku.varasto)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus  = '{$row['tunnus']}'
              GROUP BY varastopaikat.maa
              ORDER BY kpl DESC
              LIMIT 1";
    $maaresult = pupe_query($query);

    if ($maarow = mysql_fetch_assoc($maaresult)) {
      $row["maa_alkupera"] = $maarow["maa"];
    }
    else {
      $row["maa_alkupera"] = $yhtiorow["maa"];
    }
  }

  while ($maarow = mysql_fetch_assoc($result)) {
    $sel = $maarow['koodi'] == $row["maa_alkupera"] ? 'selected' : '';
    echo "<option value='{$maarow['koodi']}' {$sel}>{$maarow['nimi']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Kuljetusmuoto"),"</th>";
  echo "<td>";
  echo "<select name='kuljetusmuoto'>";
  echo "<option value=''>",t("Valitse"),"</option>";

  $result = t_avainsana("KM");

  while ($kmrow = mysql_fetch_assoc($result)) {
    $sel = $kmrow["selite"] == $row["kuljetusmuoto"] ? 'selected' : '';
    echo "<option value='{$kmrow['selite']}' {$sel}>{$kmrow['selitetark']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Kauppatapahtuman luonne"),"</th>";
  echo "<td>";
  echo "<select name='kauppatapahtuman_luonne'>";
  echo "<option value=''>",t("Valitse"),"</option>";

  $result = t_avainsana("KT");

  while ($ktrow = mysql_fetch_assoc($result)) {
    $sel = $ktrow["selite"] == $row["kauppatapahtuman_luonne"] ? 'selected' : '';
    echo "<option value='{$ktrow['selite']}' {$sel}>{$ktrow['selitetark']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Bruttopaino"),"</th>";
  echo "<td>";
  echo "<input type='text' name='bruttopaino' value='{$row['bruttopaino']}' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "<input type='submit' value='",t("Päivitä"),"' />";
  echo "</form>";
}

echo "<br><br>";

require "inc/footer.inc";
