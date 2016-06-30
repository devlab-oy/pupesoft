<?php

  $ulos   = "";
  $jatko   = 1; // oletetaan normaali k‰sittely
  $tyyppi = 1; // oletetaan rivin n‰kyvyys

  if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
    $tyyppi = 0;
  }

  if (mysql_field_name($result, $i) == "liitostunnus") {
    if ($liitostunnus != ''){
      $trow[$i] = $liitostunnus;
    }
    $tyyppi = 4;
  }

  if (mysql_field_name($result, $i) == "julkaisu") {

    $sela = $selb = '';
    if ($trow[$i] != '') {
      $selb = "SELECTED";
    }
    else {
      $sela = "SELECTED";
    }

    $ulos  = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' $sela>".t("Ei julkaista")."</option>";
    $ulos .= "<option value = 'o' $selb>".t("Julkaistaan")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Ensirekisterointipvm" or
    mysql_field_name($result, $i) == "Katsastuspvm" or
    mysql_field_name($result, $i) == "Arviointipvm") {
    $tyyppi = 1.5;
  }

  if (mysql_field_name($result, $i) == "Tyyppi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'TYYPPI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td><td><a href='yllapito.php?ojarj=$ojarj$ulisa&toim=$aputoim&tunnus=$tunnus&liitostunnus=$liitostunnus&uusi=$uusi&limit=$limit&laji=$laji&lopetus=$lopetus&specialtee=tiedottuotteelta'>Hae tiedot tuotteelta</a></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Runkotyyppi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'RUNKOTYYPPI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Kilpi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'KILPI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse kilpi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Merkki") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'MERKKI' ORDER BY avainsana.selitetark_2");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse merkki")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }


  if (mysql_field_name($result, $i) == "Lisatiedot") {
    $ulos = "<td><textarea cols='40' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Toimituskulut") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'TOIMITUSKULUT'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse toimituskulu")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Sijainti") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'SIJAINTI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse sijainti")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Myyja") {

    $query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
    $vresult = mysql_query($query) or pupe_error($query);

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Ei myyj‰‰")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['myyja']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Kuvatyyppi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'KUVATYYPPI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse kuvien tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Moottorin_ohjausjarjestelma") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'MOOTTORINOHJAUS'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse moottorin ohjausj‰rjestelm‰")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Koneen_merkki") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'MOOTTORINMERKKI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse koneen merkki")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Materiaali") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'MATERIAALI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse veneen materiaali")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Laatuluokitus") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'LAATU'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse veneen laatuluokitus")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "WC_ja_suihku") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'VESSA'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse WC:n / Suihkun tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Koneistus") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'KONEISTUS'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse veneen koneistus")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Liesin_Ceran_pinnalla" or
    mysql_field_name($result, $i) == "Nopeusmittari" or
    mysql_field_name($result, $i) == "Kompassi" or
    mysql_field_name($result, $i) == "Defroster" or
    mysql_field_name($result, $i) == "Mikroaaltouuni" or
    mysql_field_name($result, $i) == "Pakastin" or
    mysql_field_name($result, $i) == "TV_antenni" or
    mysql_field_name($result, $i) == "GPS" or
    mysql_field_name($result, $i) == "Satamapeite" or
    mysql_field_name($result, $i) == "Ajokuomu" or
    mysql_field_name($result, $i) == "Maasahko" or
    mysql_field_name($result, $i) == "Generaattori" or
    mysql_field_name($result, $i) == "Ankkuri" or
    mysql_field_name($result, $i) == "Keulapotkuri" or
    mysql_field_name($result, $i) == "Lamminvesijarjestelma" or
    mysql_field_name($result, $i) == "Trimmitasot" or
    mysql_field_name($result, $i) == "Makeavesijarjestelma" or
    mysql_field_name($result, $i) == "Suihku_WCssa" or
    mysql_field_name($result, $i) == "Suihku_uimatasolla" or
    mysql_field_name($result, $i) == "Sailytyspukki" or
    mysql_field_name($result, $i) == "Raitatiikkilattia" or
    mysql_field_name($result, $i) == "Myrkkymaalaus" or
    mysql_field_name($result, $i) == "Runkovalonheittimet" or
    mysql_field_name($result, $i) == "Vesi_WC" or
    mysql_field_name($result, $i) == "Septitankki_tyyppi" or
    mysql_field_name($result, $i) == "Runkoikkunat" or
    mysql_field_name($result, $i) == "Avotilan_poyta" or
    mysql_field_name($result, $i) == "Kaksoisakkujarjestelma" or
    mysql_field_name($result, $i) == "Sumutorvi" or
    mysql_field_name($result, $i) == "Keulaluukku" or
    mysql_field_name($result, $i) == "Avotilan_penkki" or
    mysql_field_name($result, $i) == "Liesituuletin" or
    mysql_field_name($result, $i) == "Tuulilasin_peite" or
    mysql_field_name($result, $i) == "Aurinkokatos" or
    mysql_field_name($result, $i) == "Avotilan_kuomu" or
    mysql_field_name($result, $i) == "Jaapalakone") {

    $esel = '';
    $ksel = '';

    if ($trow[$i] != "") {
      $ksel = "SELECTED";
    }
    else {
      $esel = "SELECTED";
    }

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' $esel>".t("Ei")."</option>";
    $ulos .= "<option value = 'o' $ksel>".t("Kyll‰")."</option>";
    $ulos .= "</td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Jaakaappi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'JAAKAAPPI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse j‰‰kaapin tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Valonheitin") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'VALONHEITIN'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse valonheittimen tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Ankkurivinssi") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'ANKKURIVINSSI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse ankkurivinssin tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Uuni") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'UUNI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse uunin tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Tyynysarja") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'TYYNYSARJA'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse tyynysarjan tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Tiikkisarja") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'TIIKKISARJA'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse tiikkisarjan tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Varirunko") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'VARIRUNKO'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse rungon v‰ri")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Pilssipumppu") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'PILSSIPUMPPU'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse pilssipumpun tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Sprinkleri") {

    $vresult = t_avainsana("SARJANUMERON_LI", "", "and selite = 'SPRINKLERI'");

    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

    while ($vrow=mysql_fetch_array($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selitetark']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Teho_hv") {

    echo "  <script language='javascript'>
        function hv_to_kw() {
          var kw = Math.round(document.mainform.Teho_hv.value/1.36*100)/100;
          document.mainform.Teho_kw.value=kw;
        }
      </script> ";

    $ulos = "<td><input type='text' name='$nimi' value='$trow[$i]'  id='Teho_hv' onKeyUp='hv_to_kw();'></td>";
    $jatko = 0;
  }
  if (mysql_field_name($result, $i) == "Teho_kw") {

    $ulos = "<td><input type='text' name='$nimi' value='$trow[$i]' id='Teho_kw'></td>";
    $jatko = 0;
  }

  if (mysql_field_name($result, $i) == "Polttoaine") {

    $ulos = "<td><select name='$nimi'>";

    $sel1 = "";
    $sel2 = "";

    if ($trow[$i] == "Bensiini") {
      $sel1 = "selected";
    }
    elseif ($trow[$i] == "Diesel") {
      $sel2 = "selected";
    }


    $ulos .= "<option value = 'Bensiini'  $sel1>".t("Bensiini")."</option>";
    $ulos .= "<option value = 'Diesel' $sel2>".t("Diesel")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
