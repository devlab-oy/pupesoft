<?php

if (!function_exists("yhtion_toimipaikattarkista")) {
  function yhtion_toimipaikattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray;

    static $tem_varastotoimipaikka, $tem_email, $tem_fax, $tem_kotipaikka, $tem_maa, $tem_maaind, $tem_nimi, $tem_osoite, $tem_ovtlisa, $tem_postino, $tem_postitp, $tem_puhelin, $tem_vatnumero, $tem_vatnumeroind;

    if (((mysql_field_name($result, $i) == "tilino") or
        (mysql_field_name($result, $i) == "tilino_eu") or
        (mysql_field_name($result, $i) == "tilino_ei_eu") or
        (mysql_field_name($result, $i) == "tilino_marginaali") or
        (mysql_field_name($result, $i) == "tilino_osto_marginaali") or
        (mysql_field_name($result, $i) == "toim_alv")) and trim($t[$i]) != '') {

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

    if (mysql_field_name($result, $i) == "varastotoimipaikka") {
      $tem_varastotoimipaikka = $t[$i];
    }

    if (mysql_field_name($result, $i) == "ovtlisa") {
      $tem_ovtlisa = $t[$i];
    }

    if (mysql_field_name($result, $i) == "vat_numero") {
      $tem_vatnumero = $t[$i];
      $tem_vatnumeroind = $i;
    }

    if (mysql_field_name($result, $i) == "kotipaikka") {
      $tem_kotipaikka = $t[$i];
    }

    if (mysql_field_name($result, $i) == "nimi") {
      $tem_nimi = $t[$i];
    }

    if (mysql_field_name($result, $i) == "osoite") {
      $tem_osoite = $t[$i];
    }

    if (mysql_field_name($result, $i) == "postino") {
      $tem_postino = $t[$i];
    }

    if (mysql_field_name($result, $i) == "postitp") {
      $tem_postitp = $t[$i];
    }

    if (mysql_field_name($result, $i) == "maa") {
      $tem_maa = $t[$i];
      $tem_maaind = $i;
    }

    if (mysql_field_name($result, $i) == "fax") {
      $tem_fax = $t[$i];
    }

    if (mysql_field_name($result, $i) == "puhelin") {
      $tem_puhelin = $t[$i];
    }

    if (mysql_field_name($result, $i) == "email") {
      $tem_email = $t[$i];
    }

    if (mysql_field_name($result, $i) == "tunnus") {

      if ($tem_vatnumero != "" and substr($tem_vatnumero, 0, 2) != $tem_maa) {
        $virhe[$tem_vatnumeroind] = $virhe[$i] = t("Virheellinen vat-numero!");
      }

      if ($tem_vatnumero != "") {
        $query = "SELECT selite
                  FROM avainsana
                  WHERE yhtio = '$kukarow[yhtio]'
                  and laji = 'alvulk'
                  and selitetark_2 = '$tem_maa'";
        $vresult = pupe_query($query);

        if (mysql_num_rows($vresult) == 0) {
          $virhe[$tem_vatnumeroind] = $virhe[$i] = t("Yrityksen ALV-prosentit puuttuvat tälle maalle (%s)! Käy perustamassa ensin ne avainsanoihin.", "", $tem_maa);
        }

        $query = "SELECT *
                  FROM yhtion_toimipaikat
                  WHERE yhtio = '$kukarow[yhtio]'
                  and maa = '$tem_maa'
                  and tunnus != '$tunnus'
                  and vat_numero != ''";
        $vresult = pupe_query($query);

        if (mysql_num_rows($vresult) != 0) {
          $virhe[$tem_vatnumeroind] = $virhe[$i] = t("Yksi vat tunnus per maa sallittu!");
        }

        if (empty($tem_varastotoimipaikka) and (
          $tem_email == "" or
          $tem_kotipaikka == "" or
          $tem_maa == "" or
          $tem_nimi == "" or
          $tem_osoite == "" or
          $tem_ovtlisa == "" or
          $tem_postino == "" or
          $tem_postitp == "" or
          $tem_vatnumero == "")) {

          $virhe[$tem_vatnumeroind] = $virhe[$i] = t("Kaikki tiedot on syötettävä alv-velvolliselle toimipaikalle!");
        }
      }
    }
  }
}
