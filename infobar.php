<?php

$info_query = "SELECT *
          FROM lasku use index (tila_index)
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila = 'N'
          AND alatila = 'F'
          AND yhtio_toimipaikka = '{$kukarow['toimipaikka']}'";
$info_res = pupe_query($info_query);
$info_kpl = mysql_num_rows($info_res);

while ($info_row = mysql_fetch_assoc($info_res)) {
  $info_query = "SELECT nimi, kuka
            FROM kuka
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND kesken   = '{$info_row['tunnus']}'
            AND kesken  != 0
            AND kuka    != '{$kukarow['kuka']}'";
  $info_result = pupe_query($info_query);

  if (mysql_num_rows($info_result) != 0) {
    $info_kpl -= 1;
  }
}

if ($info_kpl > 0) {

  echo "<div style='text-align:right; vertical-align:top; margin-right: 10px;'>";
  echo "<font class='message ok'>";
  echo t("EXT-tilauksia %d kpl", "", $info_kpl);
  echo "</font></div>";
}
