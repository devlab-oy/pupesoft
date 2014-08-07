<?php

$query = "SELECT *
          FROM lasku use index (tila_index)
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila = 'N'
          AND alatila = 'F'
          AND yhtio_toimipaikka = '{$kukarow['toimipaikka']}'";
$res = pupe_query($query);
$kpl = mysql_num_rows($res);

while ($row = mysql_fetch_assoc($res)) {
  $query = "SELECT nimi, kuka
            FROM kuka
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND kesken   = '{$row['tunnus']}'
            AND kesken  != 0
            AND kuka    != '{$kukarow['kuka']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    $kpl -= 1;
  }
}

if ($kpl > 0) {

  echo "<div style='text-align:right; vertical-align:top; margin-right: 10px;'>";
  echo "<font class='message ok'>";
  echo t("EXT-tilauksia %d kpl", "", $kpl);
  echo "</font></div>";
}
