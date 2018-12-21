<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tilikausi_alku" or
  mysql_field_name($result, $i) == "tilikausi_loppu" or
  mysql_field_name($result, $i) == "ostoreskontrakausi_alku" or
  mysql_field_name($result, $i) == "ostoreskontrakausi_loppu" or
  mysql_field_name($result, $i) == "myyntireskontrakausi_alku" or
  mysql_field_name($result, $i) == "myyntireskontrakausi_loppu") {
  $tyyppi = 1.5;
}

if (mysql_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maa" or mysql_field_name($result, $i) == "laskutus_maa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valkoodi") { //valuutta
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ((mysql_field_name($result, $i) == "kassa") or
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
  (mysql_field_name($result, $i) == "ostolasku_kotimaa_kulu") or
  (mysql_field_name($result, $i) == "ostolasku_kotimaa_rahti") or
  (mysql_field_name($result, $i) == "ostolasku_kotimaa_vaihto_omaisuus") or
  (mysql_field_name($result, $i) == "ostolasku_kotimaa_raaka_aine") or
  (mysql_field_name($result, $i) == "ostolasku_eu_kulu") or
  (mysql_field_name($result, $i) == "ostolasku_eu_rahti") or
  (mysql_field_name($result, $i) == "ostolasku_eu_vaihto_omaisuus") or
  (mysql_field_name($result, $i) == "ostolasku_eu_raaka_aine") or
  (mysql_field_name($result, $i) == "ostolasku_ei_eu_kulu") or
  (mysql_field_name($result, $i) == "ostolasku_ei_eu_rahti") or
  (mysql_field_name($result, $i) == "ostolasku_ei_eu_vaihto_omaisuus") or
  (mysql_field_name($result, $i) == "ostolasku_ei_eu_raaka_aine") or
  (mysql_field_name($result, $i) == "ennakkolaskun_asiakasvarasto") or
  (mysql_field_name($result, $i) == "varastonmuutos_rahti") or
  (mysql_field_name($result, $i) == "raaka_ainevarasto") or
  (mysql_field_name($result, $i) == "raaka_ainevarastonmuutos") or
  (mysql_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
  (mysql_field_name($result, $i) == "varastonmuutos_inventointi") or
  (mysql_field_name($result, $i) == "varastonmuutos_epakurantti") or
  (mysql_field_name($result, $i) == "konsernisaamiset") or
  (mysql_field_name($result, $i) == "konsernivelat") or
  (mysql_field_name($result, $i) == "konserniostovelat") or
  (mysql_field_name($result, $i) == "konsernimyyntisaamiset") or
  (mysql_field_name($result, $i) == "osto_rahti") or
  (mysql_field_name($result, $i) == "osto_kulu") or
  (mysql_field_name($result, $i) == "osto_rivi_kulu") or
  (mysql_field_name($result, $i) == "tilino_triang")) {

  if ($ulosarray[$i] != '') {
    $ulos=$ulosarray[$i];
    $jatko = 0;
  }
  else {
    $ulos = "<td nowrap valign='top'>";

    if ($trow[$i] != "") {
      $ulos .= "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>$trow[$i] - ";
    }
    else {
      $ulos .= "<input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
    }

    $query = "SELECT nimi
              FROM tili
              WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
    $vresult = pupe_query($query);

    if (mysql_num_rows($vresult) == 1) {
      $vrow = mysql_fetch_array($vresult);
      $ulos .= " $vrow[nimi]";
    }
    else {
      $query = "SELECT tilino, nimi
                FROM tili
                WHERE yhtio='$kukarow[yhtio]'";

      if (mysql_field_name($result, $i) == "kassa")                 $query .= " and nimi regexp '(kassa|k.teis)' ";
      elseif (mysql_field_name($result, $i) == "pankkikortti")           $query .= " and nimi regexp 'pankki.*kortti' ";
      elseif (mysql_field_name($result, $i) == "luottokortti")           $query .= " and nimi regexp 'luotto.*kortti' ";
      elseif (mysql_field_name($result, $i) == "kassaerotus")           $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ero' ";
      elseif (mysql_field_name($result, $i) == "kateistilitys")           $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'vara' ";
      elseif (mysql_field_name($result, $i) == "myynti")               $query .= " and nimi regexp 'myynti' ";
      elseif (mysql_field_name($result, $i) == "myynti_eu")             $query .= " and nimi regexp 'yhteis' and nimi regexp 'myyn' ";
      elseif (mysql_field_name($result, $i) == "myynti_ei_eu")           $query .= " and nimi regexp 'yhteis' and nimi regexp 'myyn' ";
      elseif (mysql_field_name($result, $i) == "myynti_marginaali")         $query .= " and nimi regexp '(k.ytet|taide)' and nimi regexp 'myyn' ";
      elseif (mysql_field_name($result, $i) == "myynti_kaanteinen")         $query .= " and nimi regexp 'nteinen' and nimi regexp 'myyn' ";
      elseif (mysql_field_name($result, $i) == "osto_marginaali")         $query .= " and nimi regexp '(k.ytet|taide)' and nimi regexp '(osto|hankin)' ";
      elseif (mysql_field_name($result, $i) == "myyntisaamiset")           $query .= " and nimi regexp 'myyn' and nimi regexp 'saam' ";
      elseif (mysql_field_name($result, $i) == "luottotappiot")           $query .= " and nimi regexp 'luotto' and nimi regexp 'tapp' ";
      elseif (mysql_field_name($result, $i) == "ostovelat")             $query .= " and nimi regexp '(velka|velat)' and nimi regexp '(osto|hankin)' ";
      elseif (mysql_field_name($result, $i) == "valuuttaero")           $query .= " and nimi regexp 'valuu' and nimi regexp 'ero' ";
      elseif (mysql_field_name($result, $i) == "myynninvaluuttaero")         $query .= " and nimi regexp 'valuu' and nimi regexp 'ero' ";
      elseif (mysql_field_name($result, $i) == "matkalla_olevat")         $query .= " and nimi regexp 'matkalla' ";
      elseif (mysql_field_name($result, $i) == "kassaale")             $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ale' ";
      elseif (mysql_field_name($result, $i) == "myynninkassaale")         $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ale' ";
      elseif (mysql_field_name($result, $i) == "muutkulut")             $query .= " and nimi regexp 'muut.*kulut' ";
      elseif (mysql_field_name($result, $i) == "pyoristys")             $query .= " and nimi regexp 'py.rist' ";
      elseif (mysql_field_name($result, $i) == "varasto")             $query .= " and nimi regexp '(varasto|tavarat)' ";
      elseif (mysql_field_name($result, $i) == "varastonmuutos")           $query .= " and nimi regexp 'varast.*muut' ";
      elseif (mysql_field_name($result, $i) == "alv")               $query .= " and nimi regexp '(alv|arvonlis)' and nimi regexp '((velka|velat)|saam)' ";
      elseif (mysql_field_name($result, $i) == "siirtosaamiset")           $query .= " and nimi regexp 'siirto' and nimi regexp 'saam' ";
      elseif (mysql_field_name($result, $i) == "siirtovelka")           $query .= " and nimi regexp 'siirto' and nimi regexp '(velka|velat)' ";
      elseif (mysql_field_name($result, $i) == "factoringsaamiset")         $query .= " and nimi regexp 'factoring' and nimi regexp 'saam' ";
      elseif (mysql_field_name($result, $i) == "selvittelytili")           $query .= " and nimi regexp 'selvittely' ";
      elseif (mysql_field_name($result, $i) == "raaka_ainevarasto")         $query .= " and nimi regexp 'raak.*aine' and nimi regexp '(varasto|tavarat)' ";
      elseif (mysql_field_name($result, $i) == "raaka_ainevarastonmuutos")     $query .= " and nimi regexp 'raak.*aine' and nimi regexp 'muut' ";
      elseif (mysql_field_name($result, $i) == "varastonmuutos_valmistuksesta")   $query .= " and nimi regexp 'kosto.*(velka|velat)' ";
      elseif (mysql_field_name($result, $i) == "konsernisaamiset")         $query .= " and nimi regexp 'konserni.*saam' ";
      elseif (mysql_field_name($result, $i) == "konsernivelat")           $query .= " and nimi regexp 'kons' and nimi regexp '(velka|velat)'  ";
      elseif (mysql_field_name($result, $i) == "konserniostovelat")         $query .= " and nimi regexp 'kons' and nimi regexp '(osto|hankin)' and nimi regexp '(velka|velat)'  ";
      elseif (mysql_field_name($result, $i) == "konsernimyyntisaamiset")       $query .= " and nimi regexp 'kons' and nimi regexp 'saam' ";
      elseif (mysql_field_name($result, $i) == "ennakkolaskun_asiakasvarasto")       $query .= " and nimi regexp 'enna' and nimi regexp 'asiakas' ";
      elseif (mysql_field_name($result, $i) == "varastonmuutos_rahti")       $query .= " and nimi regexp 'var' and nimi regexp 'muut' and nimi regexp 'rahti' ";
      else $query = "";

      if ($query != "") {

        $query .= " ORDER BY tilino";

        $vresult = pupe_query($query);

        if (mysql_num_rows($vresult) > 0) {
          $ulos .= "<table style='display:inline;'>";

          while ($vrow = mysql_fetch_array($vresult)) {
            $ulos .= "<tr><td>$vrow[tilino]</td><td>$vrow[nimi]</td></tr>";
          }

          $ulos .= "</table>";
        }
      }
    }

    $ulos .= "</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "myynti_projekti" or mysql_field_name($result, $i) == "myynti_kohde" or mysql_field_name($result, $i) == "myynti_kustp") {

  if (mysql_field_name($result, $i) == "myynti_projekti")  $kptyyppi = "P";
  if (mysql_field_name($result, $i) == "myynti_kohde")  $kptyyppi = "O";
  if (mysql_field_name($result, $i) == "myynti_kustp")  $kptyyppi = "K";

  $tpque = "SELECT tunnus, nimi, koodi
            from kustannuspaikka
            where yhtio   = '$kukarow[yhtio]'
            and tyyppi    = '$kptyyppi'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($tpque);

  $sel="";
  if ($trow[$i] == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tilikauden_tulos") {

  $tpque = "SELECT tunnus, tilino, nimi
            from tili
            where yhtio = '$kukarow[yhtio]'
            and LEFT(tilino, 1) = BINARY '2'";
  $vresult = pupe_query($tpque);

  $ulos = "<td><select name='$nimi'><option value=''>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tilino] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}
