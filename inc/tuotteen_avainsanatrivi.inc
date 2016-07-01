<?php

$ulos = "";
$jatko = 1; // oletetaan normaali k‰sittely
$tyyppi = 1; // oletetaan rivin n‰kyvyys

//yhtiˆt‰ ei n‰ytet‰
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "laji") {

  $sel = array($trow[$i] => "SELECTED");

  $tem_laji = $trow[$i];

  $ulos = "<td><select name='$nimi' onchange='submit();'>";
  $ulos .= "<option value='nimitys' $sel[nimitys]>".t("Tuotteen nimitys")."</option>";
  $ulos .= "<option value='lyhytkuvaus' $sel[lyhytkuvaus]>".t("Tuotteen lyhytkuvaus")."</option>";
  $ulos .= "<option value='kuvaus' $sel[kuvaus]>".t("Tuotteen kuvaus")."</option>";
  $ulos .= "<option value='mainosteksti' $sel[mainosteksti]>".t("Tuotteen mainosteksti")."</option>";
  $ulos .= "<option value='tarratyyppi' $sel[tarratyyppi]>".t("Tuotteen tarratyyppi")."</option>";
  $ulos .= "<option value='sistoimittaja' $sel[sistoimittaja]>".t("Tuotteen sis‰inen toimittaja")."</option>";
  $ulos .= "<option value='oletusvalinta' $sel[oletusvalinta]>".t("Tuotteen tilauksen oletusvalinta")."</option>";
  $ulos .= "<option value='osasto' $sel[osasto]>".t("Tuotteen osasto")."</option>";
  $ulos .= "<option value='try' $sel[try]>".t("Tuotteen tuoteryhm‰")."</option>";
  $ulos .= "<option value='ps_ala_try' $sel[ps_ala_try]>" . t("PupeShop alaryhm‰") . "</option>";
  $ulos .= "<option value='ei_edi_ostotilaukseen' {$sel['ei_edi_ostotilaukseen']}>".t("Tuotetta ei lis‰t‰ EDI-ostotilaukselle")."</option>";
  $ulos .= "<option value='hammastus' $sel[hammastus]>".t("Tuotteen hammastus")."</option>";
  $ulos .= "<option value='laatuluokka' {$sel['laatuluokka']}>".t("Tuotteen laatuluokka")."</option>";
  $ulos .= "<option value='synkronointi' {$sel['synkronointi']}>".t("Tuotteen synkronointi")."</option>";
  $ulos .= "<option value='toimpalautus' {$sel['toimpalautus']}>".t("Palautus toimittajalle")."</option>";
  $ulos .= "<option value='varastopalautus' {$sel['varastopalautus']}>".t("Palautus sallittuihin varastoihin")."</option>";
  $ulos .= "<option value='hinnastoryhmittely' {$sel['hinnastoryhmittely']}>".t("hinnastoryhmittely")."</option>";
  $ulos .= "<option value='magento_attribute_set_id' {$sel['magento_attribute_set_id']}>".t("Magento attribute set ID")."</option>";

  // Tuotteiden avainsanojen laji
  // N‰m‰ on dynaamisia ja k‰ytet‰‰n ainoastaan raporteissa/erikoistapauksissa, johon erikseen hardcoodattu sovittu arvo.
  $sresult = t_avainsana("TUOTEULK");

  while ($srow = mysql_fetch_array($sresult)) {
    $ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
  }

  // Tuotteen parametri.
  // K‰ytet‰‰n "tuote-export" -raportissa, "monivalintalaatikot" -listauksessa
  // sek‰ "myyntier‰t ja tuotetetiedot" -n‰kym‰ss‰ (jos se on enabloitu myyntitilaukselle)
  $vresult = t_avainsana("PARAMETRI");

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $ulos .= "<option value='parametri_$vrow[selite]' ".$sel["parametri_".$vrow["selite"]].">".t("Tuotteen parametri").": $vrow[selitetark]</option>";
  }

  // Tuotteen lis‰tieto.
  // K‰ytet‰‰n ainoastaan "hae ja selaa tuotteita" -n‰kym‰ss‰.
  $lresult = t_avainsana("LISATIETO");

  while ($lrow = mysql_fetch_assoc($lresult)) {
    $ulos .= "<option value='lisatieto_$lrow[selite]' ".$sel["lisatieto_".$lrow["selite"]].">".t("Tuotteen lis‰tieto").": $lrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
  if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }
  elseif (isset($alasveto[$i])) {
    $ulos = "<td>".$alasveto[$i]."</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "selite") {

  if (strtolower($tem_laji) == "elinkaari") {

    $vresult = t_avainsana("ELINKAARI");

    if (mysql_num_rows($vresult) > 0) {
      $ulos = "<td><select name='{$nimi}'>";
      $ulos .= "<option value=''>".t("Ei elinkaarityyppi‰")."</option>";

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $sel = $trow[$i] == $vrow['selite'] ? "selected" : "";
        $ulos .= "<option value='{$vrow['selite']}' {$sel}>{$vrow['selitetark']}</option>";
      }

      $ulos .= "</select></td>";
    }

    $jatko = 0;
  }
  elseif ($tem_laji == "tarratyyppi") {

    $vresult = t_avainsana("TARRATYYPPI");

    if (mysql_num_rows($vresult) > 0) {
      $sel = array($trow[$i] => "SELECTED");
      $ulos = "<td><select name='$nimi'>";
      $ulos .= "<option value=''>".t("Ei tilaustuotetarraa")."</option>";

      while ($vrow = mysql_fetch_array($vresult)) {
        $ulos .= "<option value='$vrow[selite]' ".$sel[$vrow["selite"]].">$vrow[selitetark]</option>";
      }
      $ulos .= "</select></td>";
    }

    $jatko = 0;
  }
  elseif ($tem_laji == "toimpalautus") {
    $sel = "";

    if ($trow[$i] != "") {
      $sel = "SELECTED";
    }

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value=' '>".t("Tuote voidaan reklamaatiolla merkata suoraan toimittajalle palautettavaksi")."</option>";
    $ulos .= "<option value='EI' $sel>".t("Tuotetta EI voida merkata suoraan toimittajalle palautettavaksi")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
  elseif ($tem_laji == "varastopalautus") {
    $sel = array_fill_keys(array($trow[$i]), " selected") + array('P' => '', 'L' => '');

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value='K' {$sel['K']}>".t("Tuote voidaan reklamaatiolla merkata sallittuihin varastoihin palautettavaksi")."</option>";
    $ulos .= "<option value='E' {$sel['E']}>".t("Tuotetta EI voida merkata sallittuihin varastoihin palautettavaksi")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
  elseif ($tem_laji == "oletusvalinta") {
    $sel = array($trow[$i] => "SELECTED");


    $ulos = "<td>
          <select name='$nimi'>
            <option value=''>".t("Ei oletustoimintoa")."</option>";

    $olquery = "SELECT toimi.tunnus, toimi.nimi
                FROM tuotteen_toimittajat
                JOIN toimi ON toimi.yhtio=tuotteen_toimittajat.yhtio and toimi.tunnus=tuotteen_toimittajat.liitostunnus
                WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]' and liitostunnus > 0";
    $oltarkres = pupe_query($olquery);

    if (mysql_num_rows($oltarkres) > 0) {
      while ($olrow = mysql_fetch_array($oltarkres)) {
        $ulos .= "
          <option value='!!!{$olrow["tunnus"]}' ".$sel["!!!{$olrow["tunnus"]}"].">$olrow[nimi] --> ".t("Tilataan asiakkaalle")."</option>
          <option value='°°°{$olrow["tunnus"]}' ".$sel["°°°{$olrow["tunnus"]}"].">$olrow[nimi] --> ".t("Tilataan varastoon")."</option>";
      }
    }

    $olquery = "SELECT tunnus
                FROM tuoteperhe
                WHERE yhtio    = '$kukarow[yhtio]'
                AND isatuoteno = '$trow[tuoteno]'
                AND tyyppi     = 'R'
                LIMIT 1";
    $oltarkres = pupe_query($olquery);

    if (mysql_num_rows($oltarkres) > 0) {
      while ($olrow = mysql_fetch_array($oltarkres)) {
        $ulos .= "<option value='°°V' ".$sel["°°V"].">".t("Valmistetaan varastoon (oletuspaikka)")."</option>";
      }
    }

    $ulos .= "</select></td>";
  }
  elseif (strtolower($tem_laji) == "magento_attribute_set_id") {
    $ulos = "<td><input type='text' name='$nimi' value='{$trow[$i]}'></input></td>";
  }
  else {
    $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
  }

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "selitetark") {
  if (strtolower($tem_laji) == "magento_attribute_set_id") {
    $tyyppi = 0;
  }
  else {
    $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
  }
}

if (mysql_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'status') {
  if (substr($trow['laji'], 0, 10) == 'lisatieto_') {
    $sel = array_fill_keys(array($trow[$i]), " selected") + array('P' => '', 'L' => '');

    $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value=''>".t("Aktiivinen")."</option>";
    $ulos .= "<option value='L'{$sel['L']}>".t("Aktiivinen")." (".t("lukittu").")</option>";
    $ulos .= "<option value='P'{$sel['P']}>".t("Poistettu")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}

if (mysql_field_name($result, $i) == 'nakyvyys') {
  if (substr($trow['laji'], 0, 10) == 'lisatieto_') {
    $sel = array($trow[$i] => " selected") + array('o' => '', 'K' => '', 'P' => '');

    $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value=''>".t("Kyll‰")."</option>";
    $ulos .= "<option value='K'{$sel['K']}>".t("Kyll‰")." (".t("ei otsikkoa").")</option>";
    $ulos .= "<option value='P'{$sel['P']}>".t("Kyll‰")." (".t("N‰ytet‰‰n vain tuotekortilla").")</option>";
    $ulos .= "<option value='o'{$sel['o']}>".t("Ei")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}

if (mysql_field_name($result, $i) == 'selite' && $trow[3] == 'ps_ala_try') {
  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 300) . ">";

  $ps_ala_tryresult = t_avainsana('PS_ALA_TRY');

  while ($ps_ala_tryrow = mysql_fetch_assoc($ps_ala_tryresult)) {
    $sel = $ps_ala_tryrow["selitetark"] == $trow[$i] ? " selected" : "";

    $ulos .= "<option{$sel} value='{$ps_ala_tryrow["selitetark"]}'>
                {$ps_ala_tryrow["selitetark"]} - {$ps_ala_tryrow["selitetark_2"]}
              </option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'jarjestys') {
  if (strtolower($tem_laji) == "magento_attribute_set_id") {
    $tyyppi = 0;
  }
}
