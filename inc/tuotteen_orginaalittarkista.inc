<?php

if (mysql_field_name($result, $i) == "tuoteno") {
  $query = "SELECT tunnus
            FROM tuote
            WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
  $sresult = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($sresult) == 0) {
    $virhe[$i] = t("Virheellinen tuotenumero");
  }
}

if (mysql_field_name($result, $i) == "orig_tuoteno") {
  if (trim($t[$i]) == '') {
    $virhe[$i] = t("Tieto puuttuu");
  }
}
