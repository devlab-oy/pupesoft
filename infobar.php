<?php

$query = "SELECT *
          FROM lasku use index (tila_index)
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila = 'N'
          AND alatila = 'F'
          AND myyja = '{$kukarow['kuka']}'";
$res = pupe_query($query);
$kpl = mysql_num_rows($res);

echo "<div style='text-align:right; vertical-align:top;'>";
echo "<font class='message'>";
echo t("EXT-tilauksia %d kpl", "", $kpl);
echo "</font></div>";
