<?php

if (!function_exists("yhtiotarkista")) {
  function yhtiotarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray;

    if ((mysql_field_name($result, $i) == "kotipaikka") or
      (mysql_field_name($result, $i) == "nimi") or
      (mysql_field_name($result, $i) == "kieli") or
      (mysql_field_name($result, $i) == "ovttunnus") or
      (mysql_field_name($result, $i) == "pankkinimi1") or
      (mysql_field_name($result, $i) == "tilikausi_alku") or
      (mysql_field_name($result, $i) == "tilikausi_loppu") or
      (mysql_field_name($result, $i) == "ostoreskontrakausi_alku") or
      (mysql_field_name($result, $i) == "ostoreskontrakausi_loppu") or
      (mysql_field_name($result, $i) == "myyntireskontrakausi_alku") or
      (mysql_field_name($result, $i) == "myyntireskontrakausi_loppu")) {

      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu!");
      }
    }

    if (((mysql_field_name($result, $i) == "kassa") or
        (mysql_field_name($result, $i) == "pankkikortti") or
        (mysql_field_name($result, $i) == "luottokortti") or
        (mysql_field_name($result, $i) == "myynti") or
        (mysql_field_name($result, $i) == "myynti_eu") or
        (mysql_field_name($result, $i) == "myynti_ei_eu") or
        (mysql_field_name($result, $i) == "myynti_marginaali") or
        (mysql_field_name($result, $i) == "myynti_kaanteinen") or
        (mysql_field_name($result, $i) == "osto_marginaali") or
        (mysql_field_name($result, $i) == "myyntisaamiset") or
        (mysql_field_name($result, $i) == "luottotappiot") or
        (mysql_field_name($result, $i) == "ostovelat") or
        (mysql_field_name($result, $i) == "valuuttaero") or
        (mysql_field_name($result, $i) == "myynninvaluuttaero") or
        (mysql_field_name($result, $i) == "matkalla_olevat") or
        (mysql_field_name($result, $i) == "kassaale") or
        (mysql_field_name($result, $i) == "myynninkassaale") or
        (mysql_field_name($result, $i) == "muutkulut") or
        (mysql_field_name($result, $i) == "pyoristys") or
        (mysql_field_name($result, $i) == "varasto") or
        (mysql_field_name($result, $i) == "varastonmuutos") or
        (mysql_field_name($result, $i) == "alv") or
        (mysql_field_name($result, $i) == "siirtosaamiset") or
        (mysql_field_name($result, $i) == "siirtovelka") or
        (mysql_field_name($result, $i) == "factoringsaamiset") or
        (mysql_field_name($result, $i) == "selvittelytili") or
        (mysql_field_name($result, $i) == "raaka_ainevarasto") or
        (mysql_field_name($result, $i) == "raaka_ainevarastonmuutos") or
        (mysql_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
        (mysql_field_name($result, $i) == "varastonmuutos_inventointi") or
        (mysql_field_name($result, $i) == "varastonmuutos_epakurantti") or
        (mysql_field_name($result, $i) == "konsernisaamiset") or
        (mysql_field_name($result, $i) == "konsernivelat") or
        (mysql_field_name($result, $i) == "konserniostovelat") or
        (mysql_field_name($result, $i) == "konsernimyyntisaamiset")) and trim($t[$i]) == '') {

      $virhe[$i] = t("Tili puuttuu!");
    }

    if (((mysql_field_name($result, $i) == "kassa") or
        (mysql_field_name($result, $i) == "pankkikortti") or
        (mysql_field_name($result, $i) == "luottokortti") or
        (mysql_field_name($result, $i) == "kassaerotus") or
        (mysql_field_name($result, $i) == "kateistilitys") or
        (mysql_field_name($result, $i) == "myynti") or
        (mysql_field_name($result, $i) == "myynti_eu") or
        (mysql_field_name($result, $i) == "myynti_ei_eu") or
        (mysql_field_name($result, $i) == "myynti_marginaali") or
        (mysql_field_name($result, $i) == "myynti_kaanteinen") or
        (mysql_field_name($result, $i) == "osto_marginaali") or
        (mysql_field_name($result, $i) == "myyntisaamiset") or
        (mysql_field_name($result, $i) == "luottotappiot") or
        (mysql_field_name($result, $i) == "ostovelat") or
        (mysql_field_name($result, $i) == "valuuttaero") or
        (mysql_field_name($result, $i) == "myynninvaluuttaero") or
        (mysql_field_name($result, $i) == "matkalla_olevat") or
        (mysql_field_name($result, $i) == "kassaale") or
        (mysql_field_name($result, $i) == "myynninkassaale") or
        (mysql_field_name($result, $i) == "muutkulut") or
        (mysql_field_name($result, $i) == "pyoristys") or
        (mysql_field_name($result, $i) == "varasto") or
        (mysql_field_name($result, $i) == "varastonmuutos") or
        (mysql_field_name($result, $i) == "alv") or
        (mysql_field_name($result, $i) == "siirtosaamiset") or
        (mysql_field_name($result, $i) == "siirtovelka") or
        (mysql_field_name($result, $i) == "factoringsaamiset") or
        (mysql_field_name($result, $i) == "selvittelytili") or
        (mysql_field_name($result, $i) == "raaka_ainevarasto") or
        (mysql_field_name($result, $i) == "raaka_ainevarastonmuutos") or
        (mysql_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
        (mysql_field_name($result, $i) == "varastonmuutos_inventointi") or
        (mysql_field_name($result, $i) == "varastonmuutos_epakurantti") or
        (mysql_field_name($result, $i) == "konsernisaamiset") or
        (mysql_field_name($result, $i) == "konsernivelat") or
        (mysql_field_name($result, $i) == "konserniostovelat") or
        (mysql_field_name($result, $i) == "konsernimyyntisaamiset")) and trim($t[$i]) != '') {

      $query = "SELECT nimi
                FROM tili
                WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) != 1) {
        //Olisiko siellä nimi
        if ($t[$i] != '') {
          $query = "SELECT nimi, tilino
                    FROM tili
                    WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
                    ORDER BY tilino";
          $sresult = pupe_query($query);

          if (mysql_num_rows($sresult) > 0) {
            $ulosarray[$i] = "<td><select name='t[$i]'><option value = '' $sel>".t("Ei valintaa")."</option>";

            while ($vrow=mysql_fetch_array($sresult)) {
              $sel="";
              if ($trow[$i] == $vrow[0]) {
                $sel = "selected";
              }
              $ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
            }
            $ulosarray[$i] .= "</select></td>";
            $virhe[$i] = t("Valitse tili");
          }
        }
        if ($virhe[$i] == '') $virhe[$i] = t("Tiliä ei löydy");
      }
    }

    if (mysql_field_name($result, $i) == "ean" and strlen($t[$i]) > 9) {
      $virhe[$i] = t("Ean pitää olla max 9 merkkiä pitkä!");
    }

    if (mysql_field_name($result, $i) == "ytunnus" and strtoupper($yhtiorow["maa"]) == "FI") {
      $ytunnus = trim($t[$i]);
      require "tarkistaytunnus.inc";

      if ($ok==0) {
        $virhe[$i] = t("Virheellinen y-tunnus!")." $t[$i] -> $ytunnus";
      }
      else {
        $t[$i] = $ytunnus;
      }
    }

    if (mysql_field_name($result, $i) == "pankkiiban1" or mysql_field_name($result, $i) == "pankkiiban2" or mysql_field_name($result, $i) == "pankkiiban3") {
      if (trim($t[$i]) != "") {

        $pankkitili = tarkista_iban($t[$i]);

        if ($pankkitili == '') {
          $virhe[$i] = t("Pankkitili on virheellinen");
        }
      }
    }

    if (mysql_field_name($result, $i) == "pankkitili1" or mysql_field_name($result, $i) == "pankkitili2" or mysql_field_name($result, $i) == "pankkitili3") {

      if (strtoupper($yhtiorow['maa']) == "FI" and trim($t[$i]) != "") {

        $pankkitili = $t[$i];

        require 'pankkitilinoikeellisuus.php';

        if ($pankkitili == '') {
          $pankkitili = tarkista_iban($t[$i]);
          if ($pankkitili == '') {
            $virhe[$i] = t("Pankkitili on virheellinen");
          }
        }
      }
    }
  }
}
