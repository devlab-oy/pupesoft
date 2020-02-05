<?php

if (mysql_field_name($result, $i) == "tuoteno") {
  $query = "SELECT tunnus
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$t[$i]'";
  $sresult = pupe_query($query);

  if (mysql_num_rows($sresult) == 0) {
    $virhe[$i] = t("Virheellinen tuotenumero");
  }
}
