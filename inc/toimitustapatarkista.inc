<?php

if (!function_exists("toimitustapatarkista")) {
  function toimitustapatarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $aputulostustapa, $apuvakkielto, $apurahtikirja;

    if (mysql_field_name($result, $i) == "rahtikirja" and $t[$i] != '') {

      // otetaan arvo talteen
      $apurahtikirja = $t[$i];
      if (!file_exists("tilauskasittely/".$t[$i])) {
        $virhe[$i] = t("Tiedosto tilauskasittely")."/$t[$i] ".t("ei löydy")."!";
      }

      $apurahtikirja = $t[$i];
    }

    if (mysql_field_name($result, $i) == "tulostustapa" and $t[$i] != '') {
      // otetaan arvo talteen
      $aputulostustapa = $t[$i];

      if ($t[$i] != 'H' and $t[$i] != 'E' and $t[$i] != 'K' and $t[$i] != 'L' and $t[$i] != 'X') {
        $virhe[$i] = t("Virheellinen tulostustapa")."!";
      }
    }

    if (mysql_field_name($result, $i) == "uudet_pakkaustiedot") {

      if ($aputulostustapa == 'L' and strpos($apurahtikirja, 'rahtikirja_unifaun') !== FALSE and $t[$i] != 'K') {
        $virhe[$i] = t("Virhe")."! ". t("Koontierätulostus ei toimi Unifaun Online rahtikirjan kanssa ilman koontirahtikirjan pakkaustietoja").".";
      }
    }

    // jos toimitustavan nimeä ollaan muuttamassa...
    if (mysql_field_name($result, $i) == "selite" and !empty($tunnus) and $t[$i] != $trow[$i]) {
      $toita = trim($t[$i]);
      $toita = str_replace("'", "", $toita);
      $toita = str_replace("\"", "", $toita);

      $query = "SELECT tunnus from toimitustapa where selite='$toita' and yhtio='$kukarow[yhtio]' and tunnus != '$tunnus'";
      $updre = pupe_query($query);

      if (mysql_num_rows($updre) > 0) {
        $virhe[$i] = t("Et voi muuttaa toimitustaavan nimeä, koska")." '$toita' ".t("on jo olemassa")."!";
      }
      else {
        $query = "UPDATE toimitustapa set selite='$toita' where selite='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = pupe_query($query);

        echo "<font class='message'>".t("Päivitettiin")." ".mysql_affected_rows()." ".t("toimitustapa");

        $query = "UPDATE toimitustapa
                  SET vak_kielto='{$toita}'
                  WHERE vak_kielto='{$trow[$i]}'
                  AND yhtio='{$kukarow["yhtio"]}'";
        pupe_query($query);

        echo ", " . mysql_affected_rows() . " " . t("VAK-käsittelyä");

        $query = "UPDATE toimitustapa
                  SET vaihtoehtoinen_vak_toimitustapa='{$toita}'
                  WHERE vaihtoehtoinen_vak_toimitustapa='{$trow[$i]}'
                  AND yhtio='{$kukarow["yhtio"]}'";
        pupe_query($query);

        echo ", " . mysql_affected_rows() . " " . t("vaihtoehtoista VAK-toimitustapaa");

        $query = "UPDATE asiakas set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("asiakasta");

        $query = "UPDATE asiakkaan_avainsanat set avainsana='$toita' where avainsana='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("asiakkaan avainsanaa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'L' and alatila != 'X'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("myyntitilaus otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'N'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("myyntitilaus kesken otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'G' and alatila NOT IN ('V', 'X')";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("siirtolista otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'E'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("ennakkotilaus otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'T' and alatila in ('','A','B')";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("tarjous otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'V' and alatila != 'V'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("valmistus otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'A'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("työmääräys otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'R' and alatila in ('','A')";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("projekti otsikkoa");

        $query = "UPDATE lasku set
                  toimitustapa='$toita'
                  where toimitustapa='$trow[$i]'
                  and yhtio='$kukarow[yhtio]'
                  and tila = 'C'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("reklamaatio otsikkoa");

        $query = "UPDATE rahtimaksut set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." ".t("rahtimaksua");

        $query = "UPDATE rahtisopimukset set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = pupe_query($query);

        echo ", ".mysql_affected_rows()." rahtisopimusta";

        $query = "UPDATE rahtikirjat set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]' and tulostettu='0000-00-00 00:00:00'";
        $updre = pupe_query($query);

        echo "ja ".mysql_affected_rows()." ".t("tulostamatonta rahtikirjaa").".</font><br><br>";
      }
    }

    if (mysql_field_name($result, $i) == "vak_kielto" and !empty($tunnus) and $t[$i] != "") {
      $query = "SELECT count(*) AS count
                FROM toimitustapa
                WHERE yhtio                         = '{$kukarow["yhtio"]}'
                AND vaihtoehtoinen_vak_toimitustapa = '{$trow["selite"]}'";
      $tt_count = pupe_query($query);
      $tt_count = mysql_fetch_assoc($tt_count);
      $tt_count = $tt_count["count"];

      if ($tt_count > 0) {
        $virhe[$i] = t("Et voi asettaa toimitustavalle VAK-kieltoa, sillä muut toimitustavat " .
          "käyttävät tätä toimitustapaa vaihtoehtoisena VAK-toimitustapana");
      }
    }

    if (mysql_field_name($result, $i) == "erilliskasiteltavakulu" or mysql_field_name($result, $i) == "lisakulu" or mysql_field_name($result, $i) == "lisakulu_summa") {
      if (trim($t[$i]) == '') $t[$i] = 0;
      if (!is_numeric(trim($t[$i]))) {
        $virhe[$i] = t("Kulu pitää olla numeerinen!");
      }
    }

    if (mysql_field_name($result, $i) == "rahti_tuotenumero" or
      mysql_field_name($result, $i) == "kuljetusvakuutus_tuotenumero") {

      $tuote = mysql_real_escape_string(trim($t[$i]));

      if ($tuote != "") {
        // katotaan löytyykö tällänen saldoton tuote
        $query = "SELECT tunnus
                  FROM tuote
                  WHERE yhtio    = '$kukarow[yhtio]'
                  AND tuoteno    = '$tuote'
                  AND tuoteno   != ''
                  AND ei_saldoa != ''";
        $xyresult = pupe_query($query);

        if (mysql_num_rows($xyresult) == 0) {
          $virhe[$i] = t("Tuotetta ei löydy!")." / ".t("Tuote pitää olla saldoton!");
        }
      }
    }
  }
}
