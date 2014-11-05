<?php

include "inc/parametrit.inc";

if (!isset($lahdetoimipaikka)) $lahdetoimipaikka = 0;
if (!isset($kohdetoimipaikka)) $kohdetoimipaikka = 0;
if (!isset($tee)) $tee = '';
if (!isset($errors)) $errors = array();

if ($tee == 'kopioi') {

  if (empty($lahdetoimipaikka)) {
    array_push($errors, t("Valitse lähdetoimipaikka"));
  }

  if (empty($kohdetoimipaikka)) {
    array_push($errors, t("Valitse kohdetoimipaikka"));
  }

  if (!empty($lahdetoimipaikka) and !empty($kohdetoimipaikka) and $lahdetoimipaikka == $kohdetoimipaikka) {
    array_push($errors, t("Lähde- ja kohdetoimipaikka eivät saa olla samoja"));
  }

  if (empty($errors)) {

    $lahdetoimipaikka = (int) $lahdetoimipaikka;
    $kohdetoimipaikka = (int) $kohdetoimipaikka;

    $query = "DELETE FROM yhtion_toimipaikat_parametrit
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND toimipaikka = '{$kohdetoimipaikka}'";
    $res = pupe_query($query);

    $query = "SELECT *
              FROM yhtion_toimipaikat_parametrit
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND toimipaikka = '{$lahdetoimipaikka}'";
    $res = pupe_query($query);

    while ($row = mysql_fetch_assoc($res)) {

      $query = "INSERT INTO yhtion_toimipaikat_parametrit SET ";

      for ($i = 0; $i < mysql_num_fields($res) - 1; $i++) {

        switch (mysql_field_name($res, $i)) {
        case 'tunnus':
          break;
        case 'toimipaikka':
          $query .= " toimipaikka = '{$kohdetoimipaikka}',";
          break;
        case 'laatija':
          $query .= " laatija = '{$kukarow['kuka']}',";
          break;
        case 'luontiaika':
          $query .= " luontiaika = now(),";
          break;
        case 'muuttaja':
          $query .= " muuttaja = '',";
          break;
        case 'muutospvm':
          $query .= " muutospvm = '0000-00-00 00:00:00',";
          break;
        default:
          $query .= mysql_field_name($res, $i)." = '".$row[mysql_field_name($res, $i)]."',";
        }
      }

      $query = substr($query, 0, -1);
      $ins_res = pupe_query($query);
    }

    echo "<font class='ok'>", t("Toimipaikan parametrit kopioitu"), "!</font><br />";
  }

  $tee = '';
}

echo "<font class='head'>", t("Kopioi toimipaikan parametrit"), "</font><hr />";

if (!empty($errors)) {
  echo "<br /><font class='error'>";
  echo implode("<br />", $errors);
  echo "</font><br /><br />";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='kopioi' />";

echo "<table>";

$query = "SELECT DISTINCT ytp.nimi, ytp.tunnus
          FROM yhtion_toimipaikat AS ytp
          JOIN yhtion_toimipaikat_parametrit AS ytpp ON (
            ytpp.yhtio    = ytp.yhtio AND ytpp.toimipaikka = ytp.tunnus
          )
          WHERE ytp.yhtio = '{$kukarow['yhtio']}'
          ORDER BY 1";
$res = pupe_query($query);

echo "<tr>";
echo "<th>", t("Valitse lähdetoimipaikka"), "</th>";
echo "<td>";
echo "<select name='lahdetoimipaikka'>";
echo "<option value=''>", t("Valitse"), "</option>";

while ($row = mysql_fetch_assoc($res)) {
  $sel = $lahdetoimipaikka == $row['tunnus'] ? "selected" : "";
  echo "<option value='{$row['tunnus']}' {$sel}>{$row['nimi']}</option>";
}

echo "</select>";
echo "</td>";
echo "</tr>";

$_kohdetoimipaikat_arr = hae_toimipaikat();

echo "<tr>";
echo "<th>", t("Valitse kohdetoimipaikka"), "</th>";
echo "<td>";
echo "<select name='kohdetoimipaikka'>";
echo "<option value=''>", t("Valitse"), "</option>";

foreach ($_kohdetoimipaikat_arr as $row) {
  $sel = $kohdetoimipaikka == $row['tunnus'] ? "selected" : "";
  echo "<option value='{$row['tunnus']}' {$sel}>{$row['nimi']}</option>";
}

echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr><td class='back'><input type='submit' value='", t("Kopioi"), "' /></td></tr>";

echo "</table>";
echo "</form>";

include "inc/footer.inc";
