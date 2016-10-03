<?php

//tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
if ($tee == '') {
  //päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
  if ($tila == '' and !isset($jatka)) {
    $query = "UPDATE kuka
              SET kesken=0
              WHERE session = '$session'";
    $result = pupe_query($query);

    $kukarow['kesken']   = 0;
    $tilausnumero     = 0;
    $otstoimaika      = '';
  }
}

//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
  echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
  exit;
}

if (!isset($nimi)) $nimi = "";
if (!isset($ytunnus)) $ytunnus = "";
if (!isset($tunnus)) $tunnus = "";
if (!isset($alatila)) $alatila = "";
if (!isset($ylatila)) $ylatila = "";
if (!isset($toimittajaid)) $toimittajaid = 0;

///* Etsitään toimittajaa *///
if ($nimi == '' and $ytunnus == '' and $tila == '' and !isset($jatka)) {
  $tila = 'Muuta';
}
elseif ($nimi != '' and (($tila == '' or $tila == 'vaihdaasiakas') and !isset($jatka))) {
  //tehdään asiakas- ja toimittajahaku yhteensopivuus
  $ytunnus = $nimi;

  $lause = "<font class='head'>".t("Valitse toimittaja").":</font><hr><br>";
  require "../inc/kevyt_toimittajahaku.inc";

  if ($ytunnus == '' and $monta > 1) {
    //Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
    $tila = '';
  }
  elseif ($ytunnus == '' and $monta < 1) {
    //yhtään asiakasta ei löytynyt, näytetään otsikko
    $tila = 'Muuta';
  }
  else {
    //oikea asiakas on löytynyt
    $tunnus = $toimittajaid;
    $tila = 'Muuta';
  }
}
elseif ($ytunnus != '' and (($kukarow['kesken'] == 0 and $tila == '') or $tila == 'vaihdaasiakas') and !isset($jatka)) {
  $tunnus = $toimittajaid;
  $tila = 'Muuta';
}

if (isset($jatka) and !checkdate($toimkk, $toimpp, $toimvv)) {
  echo "<font class='error'>".t("VIRHE: Syötetty toimituspäivä on virheellinen")."!</font><br><br>";
  $tee = "otsik_ostotilaus";
  unset($jatka);
  $tila = "Muuta";
}

if ($tila == "Muuta" or $tila == "vaihdaasiakas") {
  if ($toim == "HAAMU") {
    echo "<font class='head'>".t("Työ/tarvikeoston otsikko").":</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Ostotilauksen otsikko").":</font><hr>";
  }

  if (($tunnus != "") or ($kukarow["kesken"] != 0)) {
    if ($tunnus != "") {
      $squery = "SELECT *, tunnus liitostunnus,
                 '' alatila,
                 '' tila,
                 '' valkoodi,
                 '' verkkotunnus,
                 '' toimaika,
                 '' tilausyhteyshenkilo,
                 '' viesti,
                 '' sisviesti1,
                 '' comments,
                 '' erikoisale
                 FROM toimi
                 WHERE tunnus = '{$tunnus}'";
    }
    else {
      $squery = "SELECT *
                 FROM lasku
                 WHERE tunnus = '{$kukarow['kesken']}'";
    }
    $sresult = pupe_query($squery);
    $srow = mysql_fetch_array($sresult);

    if ($tunnus != "") {
      $srow['toim_nimi'] = "";
      $srow['toim_nimitark'] = "";
      $srow['toim_osoite'] = "";
      $srow['toim_postino'] = "";
      $srow['toim_postitp'] = "";
      $srow['toim_maa'] = "";
    }

    if ($srow["liitostunnus"] > 0 and $toimittajaid == 0) $toimittajaid = $srow["liitostunnus"];
  }
  else {
    $srow = array(
      'nimi' => '',
      'nimitark' => '',
      'osoite' => '',
      'postino' => '',
      'postitp' => '',
      'maa' => '',
      'alatila' => '',
      'tila' => '',
      'valkoodi' => '',
      'oletus_valkoodi' => '',
      'ovttunnus' => '',
      'verkkotunnus' => '',
      'liitostunnus' => '',
      'ytunnus' => '',
    );
  }

  // jos meillä on jo alatila ja tila ei muokkailla niitä!
  if ($alatila == '' and $srow['alatila'] != '') {
    $alatila = $srow['alatila'];
  }

  if ($ylatila == '' and $srow['tila'] != '') {
    $ylatila = $srow['tila'];
  }

  //laskun valuuttakoodi
  $valkoodi = $srow["valkoodi"];

  if ($valkoodi == '') {
    $valkoodi = $srow["oletus_valkoodi"] != '' ? $srow["oletus_valkoodi"] : $yhtiorow["valkoodi"];
  }

  echo "<form method='post' autocomplete='off'>
      <input type='hidden' name='toim'         value = '$toim'>
      <input type='hidden' name='lopetus'       value = '$lopetus'>
      <input type='hidden' name='tilausnumero'     value = '$tilausnumero'>
      <input type='hidden' name='toim_nimitykset'   value = '$toim_nimitykset'>
      <input type='hidden' name='toim_tuoteno'    value = '$toim_tuoteno'>
      <input type='hidden' name='naytetaankolukitut'   value = '$naytetaankolukitut'>
      <input type='hidden' name='tee'         value = 'MUUOTAOSTIKKOA'>
      <input type='hidden' name='valkoodi'       value = '$valkoodi'>
      <input type='hidden' name='ylatila'       value = '$ylatila'>
      <input type='hidden' name='ovttunnus'       value = '{$srow['ovttunnus']}'>
      <input type='hidden' name='verkkotunnus'     value = '{$srow['verkkotunnus']}'>
      <input type='hidden' name='maa'         value = '{$srow['maa']}'>
      <input type='hidden' name='liitostunnus'     value = '{$srow['liitostunnus']}'>
      <input type='hidden' name='ytunnus'       value = '{$srow['ytunnus']}'>
      <input type='hidden' name='alatila'       value = '$alatila'>";

  echo "<table>";
  echo "<tr><td colspan='2'>";
  echo "<table>
      <tr><th colspan='2' align='left' valign='top'>&nbsp;".t("Toimittajan tiedot").":</td></tr>
      <tr><td valign='top'>".t("Nimi").": </td><td><input type='text' name='nimi' size='35' maxlength='31' value='$srow[nimi]'></td></tr>
      <tr><td></td><td><input type='text' name='nimitark' size='35' maxlength='31'  value='$srow[nimitark]'></td></tr>
      <tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='osoite' size='35' maxlength='31' value='$srow[osoite]'></td></tr>
      <tr>
        <td valign='top'>".t("Postitp").": </td><td><input type='text' name='postino' size='7' maxlength='15' value='$srow[postino]'>
        <input type='text' name='postitp' size='21' maxlength='20' value='$srow[postitp]'></td></tr>";

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $maaresult = pupe_query($query);

  echo "</td></tr>";
  echo "<tr><td valign='top'>".t("Maa").": </td><td><select name='maa' ".js_alasvetoMaxWidth($nimi, 250).">";

  echo "<option value = ''></option>";

  while ($maarow = mysql_fetch_assoc($maaresult)) {
    $sel="";
    if (strtoupper($srow["maa"]) == strtoupper($maarow["koodi"])) {
      $sel = "selected";
    }

    echo "<option value = '".strtoupper($maarow["koodi"])."' $sel>".t($maarow["nimi"])."</option>";
  }

  echo "</select></td></tr></table></td>";

  if ($toimittajaid == 0 or $tila == "vaihdaasiakas") {
    echo "</tr></table><br>
          <input type = 'hidden' name = 'tila' value = 'vaihdaasiakas'>
          <input type='submit' class='hae_btn' value='".t("Hae")."'>
          </form>";

    $formi  = "paaformi";
    $kentta = "nimi";
  }
  else {

    echo "<td colspan = '2'><table>";
    echo "<tr><th colspan='2' align='left' valign='top'>&nbsp; ".t("Toimitusosoite").":</td></tr>";
    echo "<tr><td valign='top'> ".t("Nimi").": </td><td><input type='text' name='tnimi' size='35' maxlength='31' value='$srow[toim_nimi]'></td></tr>";
    echo "<tr><td> </td><td><input type='text' name='tnimitark' size='35' maxlength='31'  value='$srow[toim_nimitark]'></td></tr>";
    echo "<tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='tosoite' size='35' maxlength='31' value='$srow[toim_osoite]'></td></tr>";
    echo "<tr><td valign='top'>".t("Postitp").": </td><td><input type='text' name='tpostino' size='8' maxlength='15' value='$srow[toim_postino]'>";
    echo "<input type='text' name='tpostitp' size='21' maxlength='20' value='$srow[toim_postitp]'></td></tr>";

    mysql_data_seek($maaresult, 0);

    echo "<tr><td valign='top'>".t("Maa").": </td><td><select name='tmaa'>";

    echo "<option value = ''></option>";

    while ($maarow = mysql_fetch_assoc($maaresult)) {
      $sel="";
      if (strtoupper($srow["toim_maa"]) == strtoupper($maarow["koodi"])) {
        $sel = "selected";
      }

      echo "<option value = '".strtoupper($maarow["koodi"])."' $sel>".t($maarow["nimi"])."</option>";
    }

    echo "</select></td></tr>";
    echo "</table>";
    echo "</td></tr>";

    echo "<tr><td colspan='4' class='back'><br></td></tr>";
    echo "<tr><th colspan='4'>".t("Tilauksen tiedot").":</th></tr>";

    if ($kukarow['kesken'] == 0 and (!in_array($yhtiorow['ostotilaukseen_toimittajan_toimaika'], array("2","3")) or empty($srow["oletus_toimaika"]))) {
      $toimpp = $kerpp = date("j");
      $toimkk = $kerkk = date("n");
      $toimvv = $kervv = date("Y");
    }
    elseif ($kukarow['kesken'] == 0 and in_array($yhtiorow['ostotilaukseen_toimittajan_toimaika'], array("2","3"))) {
      $toimittajan_toimaika = date('Y-m-d', time() + $srow["oletus_toimaika"] * 24 * 60 * 60);

      list($toimvv, $toimkk, $toimpp) = explode('-', $toimittajan_toimaika);
      list($kervv, $kerkk, $kerpp)    = explode('-', $toimittajan_toimaika);

    }
    else {
      list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
      list($kervv, $kerkk, $kerpp)    = explode('-', $srow["kerayspvm"]);
      $kerpp = substr($kerpp, 0, 2);
      $toimpp = substr($toimpp, 0, 2);
    }

    //voidaan tarvita
    if ($toimvv == '') {
      $toimpp = date("j");
      $toimkk = date("n");
      $toimvv = date("Y");
    }
    if ($kervv == '') {
      $kerpp = date("j");
      $kerkk = date("n");
      $kervv = date("Y");
    }

    echo "<tr><td>".t("Toimitusajankohta").": </td><td valign='middle'>
        <input type='text' name='toimpp' value='$toimpp' size='3'>
        <input type='text' name='toimkk' value='$toimkk' size='3'>
        <input type='text' name='toimvv' value='$toimvv' size='6'>
        <input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td>";

    $query = "SELECT DISTINCT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
              FROM kuka
              JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%tilaus_osto.php'
              WHERE kuka.yhtio    = '$kukarow[yhtio]'
              AND kuka.aktiivinen = 1
              and kuka.extranet   = ''
              ORDER BY kuka.nimi";
    $yresult = pupe_query($query);

    echo "<td>".t("Ostaja").":</td>";
    echo "<td><select name='myyja'>";

    while ($row = mysql_fetch_array($yresult)) {
      $sel = "";
      if ($srow['myyja'] == '' or $srow['myyja'] == 0) {
        if ($row['nimi'] == $kukarow['nimi']) {
          $sel = 'selected';
        }
      }
      else {
        if ($row['tunnus'] == $srow['myyja']) {
          $sel = 'selected';
        }
      }
      echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
    }
    echo "</select></td></tr>";

    $selectoitu_varasto_toimipaikka = 0;

    $query  = "SELECT *
               FROM varastopaikat
               WHERE yhtio  = '$kukarow[yhtio]'
               AND tyyppi  != 'P'
               ORDER BY tyyppi, nimitys";
    $vares = pupe_query($query);

    while ($varow = mysql_fetch_array($vares)) {
      if (   $varow["tyyppi"] == ''
        or $kukarow["varasto"] == $varow["tunnus"]
        or $varow['tunnus']==$srow["varasto"]
        or $kukarow['oletus_ostovarasto'] == $varow['tunnus']) {

        // Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan se
        if (    $varow['tunnus']==$srow["varasto"]
          or (    $kukarow['oletus_ostovarasto'] == $varow['tunnus']
            and $srow['varasto'] == '')
          or (    $varow['tunnus'] == $kukarow['varasto']
            and $srow['varasto'] == ''
            and $kukarow['oletus_ostovarasto'] == 0)) {
          $selectoitu_varasto_toimipaikka = $varow['toimipaikka'];
          break;
        }
      }
    }

    $yresult = hae_yhtion_toimipaikat($kukarow['yhtio']);

    if (mysql_num_rows($yresult) > 0) {

      echo "<tr><td></td><td></td><td>".t("Tilaava toimipiste").":</td>";
      echo "<td valign='middle'>";
      echo "<select name='toimipiste'>";
      echo "<option value=''";

      if ($srow['vanhatunnus'] == 0) {
        echo 'selected';
      }

      echo ">";
      echo t("Ei poikkeavaa toimipistettä");
      echo "</option>";

      while ($row = mysql_fetch_array($yresult)) {
        $sel = "";

        if (    $srow["vanhatunnus"] == $row["tunnus"]
          or (!isset($srow['vanhatunnus']) and $kukarow['toimipaikka'] == $row['tunnus'])) {
          $sel = "selected";
        }

        if (    $sel == ''
          and $srow['vanhatunnus'] == 0
          and $selectoitu_varasto_toimipaikka == $row['tunnus']) {
          $sel = "selected";
        }

        echo "<option value='$row[tunnus]' $sel>$row[nimi] ($row[ovtlisa])</option>";
      }

      echo "</select></td></tr>";
    }

    if ($toim != "HAAMU") {
      echo "<tr><td>", t("Tilaustyyppi"), ":</td>";
      echo "<td><select name='tilaustyyppi'>";

      $ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

      if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {
        while ($ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res)) {
          $sel = $srow['tilaustyyppi'] == $ostotil_tiltyyp_row['selite'] ? " selected" : "";
          echo "<option value='{$ostotil_tiltyyp_row['selite']}'{$sel}>{$ostotil_tiltyyp_row['selitetark']}</option>";
        }
      }
      else {

        $sel = array($srow['tilaustyyppi'] => "selected") + array(1 => '', 2 => '');

        echo "<option value='2' {$sel[2]}>", t("Normaalitilaus"), "</option>";
        echo "<option value='1' {$sel[1]}>", t("Pikalähetys"), "</option>";
      }

      echo "</select></td>";

      echo "<td>";
      echo t("Erikoisalennus") . ':';
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='erikoisale' value='{$srow['erikoisale']}'>";
      echo "</td>";
    }

    echo "<tr><td>".t("Kuljetus").": </td><td valign='middle'>";

    $toimtapares = t_avainsana("TOIMITUSTAPA_OS");

    if (mysql_num_rows($toimtapares) > 0) {

      echo "<select name = 'kuljetus'>";

      while ($toimtaparow  = mysql_fetch_array($toimtapares)) {
        $sel = $srow['kuljetus'] == $toimtaparow['selite'] ? "selected" : "";
        echo "<option value='{$toimtaparow['selite']}' {$sel}>", trim("{$toimtaparow['selite']} {$toimtaparow['selitetark']}"), "</option>";
      }

      echo "</select>";
    }
    else {
      echo "<input type='text' name='kuljetus' value='$srow[kuljetus]' size='25'>";
    }
    echo "</td>";


    echo "<td>".t("Toimitusehto").": </td><td valign='middle'>
        <input type='text' name='maksaja' value='$srow[toimitusehto]' size='25'></td></tr>";

    echo "<tr><td>".t("Huolitsija").": </td><td valign='middle'>
        <input type='text' name='huolitsija' value='$srow[huolitsija]' size='25'></td>";
    echo "<td>".t("Maksuehto").": </td><td valign='middle'>
        <input type='text' name='maksuteksti' value='$srow[maksuteksti]' size='25'></td></tr>";

    echo "<tr><td>".t("Jakelu").": </td><td valign='middle'>
        <input type='text' name='jakelu' value='$srow[jakelu]' size='25'></td>";

    echo "<td>".t("Tilaa Varastoon").":</td>";
    echo "<td><select name='varasto'><option value='0'>".t("Oletus")."</option>";

    foreach (sallitut_varastot(array("toim" => "OSTO")) as $varasto) {
      $sel = '';

      // Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan se
      if (
        $varasto['tunnus'] == $srow["varasto"] or
        ($kukarow['oletus_ostovarasto'] == $varasto['tunnus'] and $srow['varasto'] == '') or
        ($varasto['tunnus'] == $kukarow['varasto'] and $srow['varasto'] == '' and $kukarow['oletus_ostovarasto'] == 0)
      ) {
        $sel = ' selected';
      }

      $tyyppi = $varasto['tyyppi'] != '' ? " ({$varasto['tyyppi']})" : '';

      echo "<option value='{$varasto[tunnus]}'{$sel}>{$varasto[nimitys]}{$tyyppi}</option>";
    }

    echo "</select></td></tr>";

    echo "<tr><td colspan='4' class='back'><br></td></tr>";
    echo "<tr><th colspan='4'>".t("Tilauksen kommentit").":</th></tr>";

    echo "<tr><td>".t("Tilausyhteyshenkilö").":</td>";

    $yhteysquery = "SELECT *
                    FROM yhteyshenkilo
                    where yhtio              = '$kukarow[yhtio]'
                    and liitostunnus         = '$srow[liitostunnus]'
                    and tyyppi               = 'T'
                    and tilausyhteyshenkilo != ''";
    $yhteysresult = pupe_query($yhteysquery);

    if (mysql_num_rows($yhteysresult) > 0) {
      echo "<td colspan = '3'><select name='seltilausyhteyshenkilo'>";
      echo "<option value = ''>".t("Ei valintaa")."</option>";
      while ($yhteysrow = mysql_fetch_array($yhteysresult)) {
        $sela = "";
        if ($yhteysrow["nimi"] == $srow["tilausyhteyshenkilo"] or ($kukarow["kesken"] == 0 and $yhteysrow["oletusyhteyshenkilo"] != "")) {
          $sela = "SELECTED";
          $srow["tilausyhteyshenkilo"] = "";
        }
        echo "<option value = '$yhteysrow[nimi]' $sela>$yhteysrow[nimi]</option>";

      }
      echo "</select>";
      echo "<input type='text' size='30' name='tilausyhteyshenkilo' value='$srow[tilausyhteyshenkilo]'> (".t("Ostotilaus").")</td></tr>";
    }
    else {
      echo "<td colspan = '3'><input type='text' size='43' name='tilausyhteyshenkilo' value='$srow[tilausyhteyshenkilo]'> (".t("Ostotilaus").")</td></tr>";
    }

    if ($yhtiorow['lomakkeiden_allekirjoitus'] == 'X') {
      echo "<tr>";
      echo "<td>".t("Allekirjoittaja")."</td>";
      echo "<td colspan = '3'><select name='allekirjoittaja'>";

      $query = "SELECT tunnus, kuka, nimi
                FROM kuka
                WHERE yhtio   = '$kukarow[yhtio]'
                AND nimi     != ''
                AND extranet  = ''
                ORDER BY nimi";
      $yresult = pupe_query($query);

      echo "<option value=''>".t("Valitse allekirjoittaja")."</option>";

      while ($row = mysql_fetch_assoc($yresult)) {
        $sel = "";
        if ($row['tunnus'] == $srow['allekirjoittaja']) {
          $sel = 'selected';
        }
        echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
      }
      echo "</select></td></tr>";
    }

    echo "<tr><td>".t("Tilausviite").":</td><td colspan='3'>
      <input type='text' size='61' name='viesti' value='$srow[viesti]'> (".t("Ostotilaus").")</td>";
    echo "</tr>";
    echo "<tr><td>".t("Viesti").":</td><td colspan='3'><textarea name='sisviesti1' rows='2' cols='60'>$srow[sisviesti1]</textarea> (".t("Ostotilaus").")</td></tr>";
    echo "<tr><td>".t("Kommentit").":</td><td colspan='3'><textarea name='comments' rows='2' cols='60'>$srow[comments]</textarea> (".t("Sisäinen").")</td></tr>";
    echo "<tr><td>".t("Ohjausmerkki").":</td><td colspan='3'><input type='text' size='61' name='ohjausmerkki' value='$srow[ohjausmerkki]'>(".t("Sähköinen ostotilaus").")</td></tr>";
    echo "</table>";
    echo "<input type='hidden' name='from' value='$from'><br>";
    echo "<br><br>";
    echo "<input type='submit' name='jatka' value='".t("Jatka")."'></form>";

    echo "<script LANGUAGE='JavaScript'>
        window.document.otsikko.tila.focus();
        </script>";

    echo "&nbsp;&nbsp;";

    $query = "SELECT tunnus
              FROM tilausrivi
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND otunnus     = '{$tilausnumero}'
              AND uusiotunnus > 0
              LIMIT 1";
    $vresult = pupe_query($query);

    if (mysql_num_rows($vresult) == 0) {
      echo "<td class='back'>
          <form method='post' autocomplete='off'>
          <input type='hidden' name='toim'         value = '$toim'>
          <input type='hidden' name='lopetus'       value = '$lopetus'>
          <input type='hidden' name='tilausnumero'     value = '$tilausnumero'>
          <input type='hidden' name='toim_nimitykset'   value = '$toim_nimitykset'>
          <input type='hidden' name='toim_tuoteno'    value = '$toim_tuoteno'>
          <input type='hidden' name='naytetaankolukitut'   value = '$naytetaankolukitut'>
          <input type='hidden' name='tila'         value = 'vaihdaasiakas'>
          <input type='hidden' name='tee'         value = 'MUUOTAOSTIKKOA'>
          <input type='submit' ACCESSKEY='A' value='".t("Vaihda toimittaja")."'></form>
          </td>";
    }
  }
}

if (isset($jatka)) {

  if ($kukarow["kesken"] == 0) {
    $query = "INSERT into ";

    $vquery = "SELECT nimi, kurssi, tunnus
               FROM valuu
               WHERE yhtio = '$kukarow[yhtio]'
               and nimi    = '$valkoodi'";
    $vresult = pupe_query($vquery);
    $vrow = mysql_fetch_array($vresult);

    $postquery = ", vienti_kurssi = '$vrow[kurssi]', laatija = '$kukarow[kuka]', luontiaika=now()";
  }
  else {
    // Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
    $query = "UPDATE tilausrivi
              SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
              WHERE otunnus = '$kukarow[kesken]'
              and kerayspvm = '$vkerayspvm'
              and yhtio     = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $query = "UPDATE tilausrivi
              SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
              WHERE otunnus = '$kukarow[kesken]'
              and toimaika  = '$vtoimaika'
              and yhtio     = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $query = "UPDATE ";
    $postquery = " WHERE tunnus = '$kukarow[kesken]'";
  }

  // katotaan ollaanko valittu tietty varasto johon kamat tilataan
  if ($varasto > 0) {
    $edique = "SELECT *
               FROM varastopaikat
               WHERE yhtio = '$kukarow[yhtio]'
               AND tunnus  = '$varasto'";
    $vares = pupe_query($edique);
    $varow = mysql_fetch_array($vares);

    if ($yhtiorow['pakollinen_varasto'] == 'K' and isset($toimipiste) and (trim($toimipiste) == '' or $toimipiste == 0) and $varow['toimipaikka'] != 0) {
      $toimipiste = $varow['toimipaikka'];

      echo "<font class='message'>", t("Tilaava toimipiste haettiin varaston takaa"), ".</font><br /><br />";
    }

    if ($varow["nimi"] != "" and $varow["osoite"] != "") {
      $tnimi     = $varow["nimi"];
      $tnimitark = $varow["nimitark"];
      $tosoite   = $varow["osoite"];
      $tpostino  = $varow["postino"];
      $tpostitp  = $varow["postitp"];
      $tmaa       = $varow["maa"];

      echo "<font class='message'>", t("Toimitusosoitteen tiedot haettiin varaston %s takaa", "", $varow["nimitys"]), ".</font><br /><br />";
    }
  }

  // katotaan ollaanko valittu tilaajaksi joku muu toimipiste
  if ($toimipiste > 0 and strlen(trim($tnimi)) == 0) {
    $edique = "SELECT *
               FROM yhtion_toimipaikat
               WHERE yhtio = '$kukarow[yhtio]'
               AND tunnus  = '$toimipiste'";
    $ediotr = pupe_query($edique);
    $edioro = mysql_fetch_array($ediotr);

    // laitetaan toimipisteen tiedot toimitusosoitteeseen jos siellä ei vielä muuta ole
    if ($edioro["nimi"] != "" and $edioro["osoite"] != "") {
      $tnimi       = $edioro["nimi"];
      $tnimitark   = $edioro["nimitark"];
      $tosoite     = $edioro["osoite"];
      $tpostino    = $edioro["postino"];
      $tpostitp    = $edioro["postitp"];
      $tmaa      = $edioro["maa"];
    }
  }

  // varmistellaan, että meillä menee jotain toimitusosoite kenttään
  if (strlen(trim($tnimi)) == 0) {
    $tnimi     = $yhtiorow["nimi"];
    $tnimitark = $yhtiorow["nimitark"];
    $tosoite   = $yhtiorow["osoite"];
    $tpostino  = $yhtiorow["postino"];
    $tpostitp  = $yhtiorow["postitp"];
    $tmaa       = $yhtiorow["maa"];
  }

  if ($tilausyhteyshenkilo == '' and $seltilausyhteyshenkilo != '') {
    $tilausyhteyshenkilo = $seltilausyhteyshenkilo;
  }

  $crlf = array("\r", "\n"); // poistetaan rivinvaihdot kommentista
  $comments = str_replace($crlf, " ", $comments);

  if ($toim == "HAAMU") {
    $tilaustyyppi = "O";
  }

  //Erikoisalennuksen desimaalipilkku
  $erikoisale = (float) str_replace(",", ".", $erikoisale);

  $query .= "  lasku SET
        alatila       = '$alatila',
        comments       = '$comments',
        huolitsija       = '$huolitsija',
        jakelu         = '$jakelu',
        kerayspvm       = '".$kervv."-".$kerkk."-".$kerpp."',
        kuljetus       = '$kuljetus',
        liitostunnus    = '$liitostunnus',
        maa         = '$maa',
        maksuteksti     = '$maksuteksti',
        myyja         = '$myyja',
        allekirjoittaja    = '$allekirjoittaja',
        nimi         = '$nimi',
        nimitark       = '$nimitark',
        ohjausmerkki     = '$ohjausmerkki',
        osoite         = '$osoite',
        ovttunnus       = '$ovttunnus',
        postino       = '$postino',
        postitp       = '$postitp',
        sisviesti1      = '$sisviesti1',
        tila         = 'O',
        tilaustyyppi    = '$tilaustyyppi',
        tilausyhteyshenkilo  = '$tilausyhteyshenkilo',
        toimaika       = '".$toimvv."-".$toimkk."-".$toimpp."',
        toimitusehto     = '$maksaja',
        toimitustapa     = '$toimitustapa',
        toim_maa       = '$tmaa',
        toim_nimi       = '$tnimi',
        toim_nimitark     = '$tnimitark',
        toim_osoite     = '$tosoite',
        toim_ovttunnus     = '$toim_ovttunnus',
        toim_postino     = '$tpostino',
        toim_postitp     = '$tpostitp',
        valkoodi       = '$valkoodi',
        vanhatunnus      = '$toimipiste',
        varasto       = '$varasto',
        verkkotunnus    = '$verkkotunnus',
        viesti         = '$viesti',
        viikorkopros     = '$yhtiorow[viivastyskorko]',
        yhtio         = '$kukarow[yhtio]',
        ytunnus        = '$ytunnus',
        erikoisale      = '$erikoisale'";
  $query .= $postquery;
  $result = pupe_query($query);
  $id = mysql_insert_id($GLOBALS["masterlink"]);

  // Jos erikoisalennus muuttuu
  if ($laskurow["erikoisale"] != $erikoisale) {
    // päivitetään erikoisale tilausriveille
    $erikoisale_query_otunnus = (int) $kukarow['kesken'] != 0 ? $kukarow['kesken'] : $id;

    $query_erikoisale = "UPDATE tilausrivi
                         SET erikoisale = '{$erikoisale}'
                         WHERE yhtio      = '{$kukarow['yhtio']}'
                         AND otunnus      = '{$erikoisale_query_otunnus}'
                         AND netto       != 'N'
                         AND tyyppi      != 'D'
                         AND uusiotunnus  = 0";
    $upd_erikoisale_res = pupe_query($query_erikoisale);
  }

  if ((int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE and $session != "") {
    $query = "UPDATE kuka
              SET kesken = '$id'
              WHERE yhtio = '$kukarow[yhtio]' AND
              kuka        = '$kukarow[kuka]' AND
              session     = '$session'";
    $result = pupe_query($query);

    $kukarow["kesken"] = $id;
    $tilausnumero = $id;
    $otstoimaika = $toimvv."-".$toimkk."-".$toimpp;
  }

  //mennään tilaukselle
  $tee = "Y";
}
