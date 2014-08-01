<?php

if (!function_exists("toimitustapatarkista")) {
  function toimitustapatarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $aputulostustapa, $apuvakkielto;

    if ((mysql_field_name($result, $i)=="rahtikirja") and ($t[$i]!='')) {
      if (!file_exists("tilauskasittely/".$t[$i])) {
        $virhe[$i] = t("Tiedosto tilauskasittely")."/$t[$i] ".t("ei löydy")."!";
      }
    }

    if ((mysql_field_name($result, $i)=="tulostustapa") and ($t[$i]!='')) {
      // otetaan arvo talteen
      $aputulostustapa = $t[$i];
      if ($t[$i]!='H' and $t[$i]!='E' and $t[$i]!='K' and $t[$i]!='L' and $t[$i]!='X') {
        $virhe[$i] = t("Virheellinen tulostustapa")."!";
      }
    }

    // jos toimitustavan nimeä ollaan muuttamassa...
    if ((mysql_field_name($result, $i)=="selite") and ($t[$i]!=$trow[$i])) {
      $toita = trim($t[$i]);
      $toita = str_replace("'", "", $toita);
      $toita = str_replace("\"", "", $toita);

      $query = "SELECT tunnus from toimitustapa where selite='$toita' and yhtio='$kukarow[yhtio]' and tunnus != '$tunnus'";
      $updre = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($updre)!=0) {
        $virhe[$i] = t("Et voi muuttaa toimitustaavan nimeä, koska")." '$toita' ".t("on jo olemassa")."!";
      }
      else {
        $query = "UPDATE toimitustapa set selite='$toita' where selite='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = mysql_query($query) or pupe_error($query);

        echo "<font class='message'>".t("Päivitettiin")." ".mysql_affected_rows()." ".t("toimitustapa");

        $query = "UPDATE asiakas set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = mysql_query($query) or pupe_error($query);

        echo ", ".mysql_affected_rows()." ".t("asiakasta");

        $query = "UPDATE asiakkaan_avainsanat set avainsana='$toita' where avainsana='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = mysql_query($query) or pupe_error($query);

        echo ", ".mysql_affected_rows()." ".t("asiakkaan avainsanaa");

        $query = "UPDATE lasku set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]' and ((tila='L' and alatila in('A','C')) or tila='N')";
        $updre = mysql_query($query) or pupe_error($query);

        echo ", ".mysql_affected_rows()." ".t("otsikkoa");

        $query = "UPDATE rahtimaksut set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = mysql_query($query) or pupe_error($query);

        echo ", ".mysql_affected_rows()." ".t("rahtimaksua");

        $query = "UPDATE rahtisopimukset set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
        $updre = mysql_query($query) or pupe_error($query);

        echo ", ".mysql_affected_rows()." rahtisopimusta";

        $query = "UPDATE rahtikirjat set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]' and tulostettu='0000-00-00 00:00:00'";
        $updre = mysql_query($query) or pupe_error($query);

        echo "ja ".mysql_affected_rows()." ".t("tulostamatonta rahtikirjaa").".</font><br><br>";
      }
    }

    if ((mysql_field_name($result, $i) == "vak_kielto")) {
      $apuvakkielto  = $t[$i];
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
        $xyresult = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($xyresult) == 0) {
          $virhe[$i] = t("Tuotetta ei löydy!")." / ".t("Tuote pitää olla saldoton!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "sallitut_alustat") {
      if (is_array($t[$i]) and count($t[$i]) > 0) {
        if (count($t[$i]) == 1 and $t[$i][0] == 'default') {
          unset($t[$i]);
          $t[$i] = '';
        }
        else {
          unset($t[$i][0]);
          $t[$i] = implode(",", $t[$i]);
        }
      }
    }
  }
}
