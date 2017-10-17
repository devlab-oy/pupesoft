<?php

//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0)) {
  echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
  exit;
}

if (empty($tila)) {
  $tila = 'Muuta';
}

$laaja_toim_kasittely = ($yhtiorow['toimipaikkakasittely'] == 'L');

if ($toim == 'SIIRTOLISTA' and isset($jatka) and $laaja_toim_kasittely and $varasto == '') {
  echo "<br><font class='error'>".t("VIRHE: Lähdevarasto on valittava")."!</font><br><br>";
  unset($jatka);
}

if ($toim == 'SIIRTOLISTA' and isset($jatka) and $clearing == '') {
  echo "<br><font class='error'>".t("VIRHE: Kohdevarasto on valittava")."!</font><br><br>";
  unset($jatka);
}

if ($tila == 'Muuta' and !isset($jatka)) {

  if ($toim == "SIIRTOLISTA") {
    echo "<font class='head'>".t("Siirtolistan otsikko")."</font><hr><br>";
  }
  elseif ($toim == "VALMISTAVARASTOON") {
    echo "<font class='head'>".t("Valmistuksen otsikko")."</font><hr><br>";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    echo "<font class='head'>".t("Sisäinen työmääräys")."</font><hr><br>";
  }

  $qlisa = "";
  if ($toim == "SIIRTOTYOMAARAYS") {
    $qlisa = " LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus ";
  }

  if (!empty($kopioitava_otsikko) or $kukarow["kesken"] > 0) {

    if (!empty($kopioitava_otsikko)) {
      $squery = "SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokkaa
                 FROM lasku
                 LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
                 LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
                 WHERE lasku.tunnus = '$kopioitava_otsikko'";

      //  Tämä on tehtävä, jottemme vain päivitä vanhaa!
      $query = "UPDATE kuka
                SET kesken = 0
                WHERE session = '$session'";
      $result = pupe_query($query);

      $kukarow['kesken']   = 0;
      $tilausnumero     = 0;

    }
    elseif ($kukarow["kesken"] > 0) {
      $squery = "SELECT *, hyvaksynnanmuutos as luokka
                 FROM lasku
                 $qlisa
                 WHERE tunnus = '$kukarow[kesken]'";
    }

    $sresult = pupe_query($squery);
    $srow = mysql_fetch_assoc($sresult);

    // jos meillä on jo alatila ja tila ei muokkailla niitä!
    $alatila = "";
    $ylatila = "";

    //  Alatila setataan vain jos ei olla kopsaamassa!
    if (empty($kopioitava_otsikko) and $srow['alatila'] != '') {
      $alatila = $srow['alatila'];
    }

    if ($srow['tila'] != '') {
      $ylatila = $srow['tila'];
    }
  }

  if (!isset($srow)) {
    $query = "SELECT *, hyvaksynnanmuutos as luokka FROM lasku LIMIT 1";
    $result = pupe_query($query);
    $srow = mysql_fetch_assoc($result);

    foreach ($srow as $key => $value) {
      $srow[$key] = '';
    }
  }

  echo "<table>";

  if ($toim == "SIIRTOLISTA") {
    echo "<tr><th>".t("Valitse lähdevarasto").":</th>";
  }
  elseif ($toim == "VALMISTAVARASTOON") {
    echo "<tr><th>".t("Käytä raaka-aineita varastosta").":</th>";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    echo "<tr><th>".t("Valitse lähdevarasto").":</th>";
  }

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $vtresult = pupe_query($query);

  echo "<form method='post'>";
  echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
  echo "<input type='hidden' name='tee' value='OTSIK'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='projektilla' value='$projektilla'>";
  echo "<input type='hidden' name='tunnusnippu' value='{$srow["tunnusnippu"]}'>";
  echo "<input type='hidden' name='ylatila' value='$ylatila'>";
  echo "<input type='hidden' name='alatila' value='$alatila'>";
  echo "<td><select name='varasto' onchange='submit()' >";

  if ($toim == "SIIRTOLISTA") {
    if ($laaja_toim_kasittely) {
      echo "<option value=''>".t("Valitse")."</option>";
    }
    else {
      echo "<option value=0>".t("Siirrä kaikista")."</option>";
    }
  }
  elseif ($toim == "VALMISTAVARASTOON") {
    echo "<option value=0>".t("Käytä kaikista")."</option>";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    echo "<option value=0>".t("Käytä kaikista")."</option>";
  }

  while ($vrow = mysql_fetch_array($vtresult)) {
    $sel='';

    if ($varasto > 0 and $vrow['tunnus'] == $varasto) {
      $sel = 'selected';
    }

    if (!is_numeric($varasto) and $varasto == "" and $vrow['tunnus'] == $srow["varasto"]) {
      $sel = 'selected';
    }

    $varastomaa = '';
    if (strtoupper($vrow['maa']) != strtoupper($yhtiorow['maa'])) {
      $varastomaa = strtoupper($vrow['maa']);
    }

    echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
  }
  echo "</select></td></tr>";

  if ($toim != "SIIRTOTYOMAARAYS") {
    if ($toim == "SIIRTOLISTA") {
      echo "<tr><th>".t("Valitse kohdevarasto").":</th>";
    }
    elseif ($toim == "VALMISTAVARASTOON") {
      echo "<tr><th>".t("Valmisteiden kohdevarasto").":</th>";
    }

    echo "<td><select name='clearing' onchange='submit()' >";

    if ($toim != "VALMISTAVARASTOON") {
      echo "<option value=''>".t("Valitse")."</option>";
    }

    $query  = "SELECT *
               FROM varastopaikat
               WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
               ORDER BY tyyppi, nimitys";
    $vares = pupe_query($query);

    while ($varow = mysql_fetch_array($vares)) {

      $sel = '';

      if (isset($clearing) and $clearing > 0 and $varow['tunnus'] == $clearing) {
        $sel = 'selected';
      }

      // Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan se
      if (empty($clearing) and $varow['tunnus'] == $srow["clearing"] or
        (empty($clearing)
          and (int) $kukarow["kesken"] == 0
          and $varow['tunnus'] == $kukarow["oletus_varasto"])) {
        $sel = 'selected';
      }

      $varastomaa = '';
      if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
        $varastomaa = strtoupper($varow['maa']);
      }

      echo "<option value='$varow[tunnus]' $sel>$varow[maa] $varow[nimitys]</option>";
    }

    echo "</select></td></tr>";

    $valmistuslinja_result = t_avainsana("VALMISTUSLINJA");

    if ($toim == "VALMISTAVARASTOON" and mysql_num_rows($valmistuslinja_result) > 0) {
      echo "<tr>";
      echo "<th>".t("Valmistuslinja").":</th>";
      echo "<td colspan='3'>";

      echo "<select name='valmistuslinja'>";
      echo "<option value = ''>".t("Ei valmistuslinjaa")."</option>";

      while ($valmistuslinja_row = mysql_fetch_array($valmistuslinja_result)) {
        $sel = ($valmistuslinja_row["selite"] == $srow["kohde"]) ? "selected" : "";
        echo "<option value='{$valmistuslinja_row["selite"]}' $sel>{$valmistuslinja_row["selitetark"]}</option>";
      }
      echo "</select>";
      echo "<input type='hidden' name='vvalmistuslinja' value='{$srow["kohde"]}' />";
      echo "</td>";
      echo "</tr>";
    }

    echo "<tr><th>".t("Nimi").":</th><td colspan='3'><input type='text' size='35' name='toim_nimi' value='$srow[toim_nimi]'></td>";
    echo "<tr><th>".t("Nimitark").":</th><td colspan='3'><input type='text' size='35' name='toim_nimitark' value='$srow[toim_nimitark]'></td>";
    echo "<tr><th>".t("Osoite").":</th><td colspan='3'><input type='text' size='35' name='toim_osoite' value='$srow[toim_osoite]'></td>";
    echo "<tr><th>".t("Postino").":</th><td colspan='3'><input type='text' size='8' name='toim_postino' value='$srow[toim_postino]'></td>";
    echo "<tr><th>".t("Postitp").":</th><td colspan='3'><input type='text' size='15' name='toim_postitp' value='$srow[toim_postitp]'></td>";
    echo "<tr><th>".t("Maa").":</th><td colspan='3'>$srow[toim_maa]</td>";
  }

  if ($kukarow['kesken'] == 0) {
    $toimpp = $kerpp = date("j");
    $toimkk = $kerkk = date("n");
    $toimvv = $kervv = date("Y");
  }
  else {
    list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
    list($kervv, $kerkk, $kerpp)    = explode('-', $srow["kerayspvm"]);
    $kerpp  = substr($kerpp, 0, 2);
    $toimpp = substr($toimpp, 0, 2);
  }

  // Jos valmistuksessa käytetään tilakoodeja asetetaan keräysajankohta tulevaisuuteen.
  // Valmistus ei varaa saldoja ennenkuin työ on valmistuslinjalla.
  if ($yhtiorow['valmistuksessa_kaytetaan_tilakoodeja'] == 'K' and $toim == 'VALMISTAVARASTOON') {
    $kerpp = '1';
    $kerkk = '1';
    $kervv = '2099';

    $toimpp = '1';
    $toimkk = '1';
    $toimvv = '2099';
  }

  echo "<tr><th>".t("Toivottu keräysajankohta").": </th><td valign='middle'>
      <input type='text' name='kerpp' value='$kerpp' size='3'>
      <input type='text' name='kerkk' value='$kerkk' size='3'>
      <input type='text' name='kervv' value='$kervv' size='6'>
      <input type='hidden' name='vkerayspvm' value='".substr($srow["kerayspvm"], 0, 10)."'></td></tr>";

  if ($toim == "SIIRTOLISTA") {
    echo "<tr><th>".t("Toivottu toimitusajankohta").": </th>";
  }
  elseif ($toim == "VALMISTAVARASTOON") {
    echo "<tr><th>".t("Toivottu valmistusajankohta").": </th>";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    echo "<tr><th>".t("Toivottu toimitusajankohta").": </th>";
  }

  echo "  <td valign='middle'>
      <input type='text' name='toimpp' value='$toimpp' size='3'>
      <input type='text' name='toimkk' value='$toimkk' size='3'>
      <input type='text' name='toimvv' value='$toimvv' size='6'>
      <input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td></tr>";

  if ($toim == "SIIRTOTYOMAARAYS" or $toim == "VALMISTAVARASTOON") {
    if ($toim == "VALMISTAVARASTOON" and $yhtiorow["kerataanko_valmistukset"] == "E") {
      echo "<input type='hidden' name='eilahe' value='o'>";
    }
    else {
      if ($srow['eilahetetta'] != '') $eilah = 'CHECKED';
      echo "<tr><th>".t("Ohita keräys")."</th><td><input type='checkbox' name='eilahe' $eilah></td></tr>";
    }
  }

  echo "<tr>";

  if ($toim == "SIIRTOLISTA") {
    echo "<th>".t("Keräysprioriteetti")."</th><td>";
  }
  elseif ($toim == "VALMISTAVARASTOON") {
    echo "<th>".t("Valmistusprioriteetti")."</th><td>";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    echo "<th>".t("Asennusprioriteetti")."</th><td>";
  }

  //  Olisiko erikseen varastoluokkia?
  $tresult = t_avainsana("VARASTOLUOKKA");

  if (mysql_num_rows($tresult) == 0) {
    $tresult = t_avainsana("ASIAKASLUOKKA");
  }

  if (empty($srow['luokka'])) {
    // haetaan oletusprioriteetti
    $oletusluokka = t_avainsana("VARASTOLUOKKA", "", " and avainsana.selitetark_2 != ''", "", "", "selite");

    if (empty($oletusluokka)) {
      $oletusluokka = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selitetark_2 != ''", "", "", "selite");
    }

    $srow['luokka'] = $oletusluokka;
  }

  echo "<select name='luokka'><option value=''>".t("Oletus")."</option>";

  while ($row = mysql_fetch_array($tresult)) {
    $sel = "";
    if ($row["selite"] == $srow["luokka"]) $sel = 'selected';

    echo "<option value='$row[selite]' $sel>$row[selite] - $row[selitetark]</option>";
  }
  echo "</select>";
  echo "</td></tr>";

  if ($toim == "SIIRTOLISTA") {

    if (empty($toimitustapa) and empty($srow['toimitustapa'])) {
      $toimitustapa = 'Ei toimitustapaa';
    }

    $tt_vaihdettu = false;

    if ($varasto > 0 and $clearing > 0) {

      $kohdevarasto = $clearing;
      $lahdevarasto = $varasto;

      $ttqry = "SELECT tpa.selite AS toimitustapa
                FROM avainsana AS ana
                JOIN toimitustapa AS tpa
                ON ( ana.yhtio = tpa.yhtio AND ana.selitetark_2 = tpa.tunnus )
                WHERE ana.yhtio    = '$kukarow[yhtio]'
                AND ana.laji       = 'SIIRTOVARASTOT'
                AND ana.selite     = '$lahdevarasto'
                AND ana.selitetark = '$kohdevarasto'";
      $ttresult = pupe_query($ttqry);

      if (mysql_num_rows($ttresult) > 0) {
        $ttrow = mysql_fetch_assoc($ttresult);
        $def_toimitustapa = $ttrow['toimitustapa'];
      }
      else {
        $def_toimitustapa = 'Ei toimitustapaa';
      }

      if ($def_toimitustapa != $toimitustapa) {
        $tt_vaihdettu = true;
      }
    }

    echo "<tr>";
    echo "<th>".t("Toimitustapa")."</th><td>";

    $query = "SELECT tunnus, selite
              FROM toimitustapa
              WHERE yhtio = '$kukarow[yhtio]'
              and (sallitut_maat = '' or sallitut_maat like '%$srow[toim_maa]%')
              ORDER BY jarjestys, selite";
    $tresult = pupe_query($query);

    echo "<select name='toimitustapa'>";

    while ($row = mysql_fetch_array($tresult)) {
      $sel = "";

      if (isset($def_toimitustapa) and $def_toimitustapa == $row["selite"]) {
        $sel = 'selected';
      }

      if (!isset($def_toimitustapa) and (isset($toimitustapa) and $toimitustapa == $row["selite"])) {
        $sel = 'selected';
      }

      if ((empty($toimitustapa) and empty($def_toimitustapa)) and $row["selite"] == $srow["toimitustapa"]) $sel = 'selected';

      echo "<option value='$row[selite]' $sel>". $row['selite'] ."</option>";
    }
    echo "</select>";

    echo "</td></tr>";
  }

  echo "<tr><th>".t("Viite").":</th><td colspan='3'>
      <input type='text' size='53' name='viesti' value='$srow[viesti]'></td>";
  echo "</tr>";
  echo "<tr><th>".t("Kommentit").":</th><td colspan='3'><textarea name='comments' rows='2' cols='60'>$srow[comments]</textarea></td></tr>";
  echo "</table>";

  if ($toim == "SIIRTOTYOMAARAYS") {
    echo "<br><table>";
    echo "<tr><th colspan='4'>".t("Työmääräyksen tiedot")."</th></tr>";

    if ($srow["luvattu"] == "" or $srow["luvattu"] == "0000-00-00") {
      $luvattupp = date("d");
      $luvattukk = date("m");
      $luvattuvv = date("Y");
    }
    else {
      list($luvattuvv, $luvattukk, $luvattupp) = explode('-', $srow["luvattu"]);
    }

    echo "<tr><td>".t("Luvattu").":</td><td>
        <input type='text' name='luvattupp' size='3' value='$luvattupp'>-
        <input type='text' name='luvattukk' size='3' value='$luvattukk'>-
        <input type='text' name='luvattuvv' size='6' value='$luvattuvv'>
        </td>
        <td> ".t("Työn suorittaja").":</td><td><input type='text' name='suorittaja' size='35' maxlengtd='15' value='$srow[suorittaja]'></td></tr>";
    echo "<tr><td>".t("Työn kuvaus").":</td><td colspan='3'><textarea name='komm1' rows='4' cols='40'>$srow[komm1]</textarea></td></tr>";
    echo "<tr><td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='komm2' rows='4' cols='40'>$srow[komm2]</textarea></td></tr>";
    echo "</table><br>";
  }

  echo "<br><input type='submit' name='jatka' value='".t("Jatka")."'></form>";

  if ($tt_vaihdettu === true) {
    echo "<font class='error' style='margin-left:10px;'>", t("Toimitustapa vaihdettiin automaattisesti"), "!</font><br />";
  }

}

if (isset($jatka)) {

  if ($kukarow["kesken"] == 0) {
    $query = "INSERT into ";
    $postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

    // Jos valmistuksessa käytetään tilakoodeja, setataan valmistuksen_tila oletusarvoksi
    // OV = Odottaa Valmistusta
    if ($yhtiorow['valmistuksessa_kaytetaan_tilakoodeja'] == 'K') $postquery .= ", valmistuksen_tila = 'OV'";

    $query2 = "INSERT into ";
    $postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
  }
  else {
    // Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
    $query = "UPDATE tilausrivi
              SET kerayspvm = '$kervv-$kerkk-$kerpp'
              WHERE otunnus = '$kukarow[kesken]' and kerayspvm='$vkerayspvm' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    $query = "UPDATE tilausrivi
              SET toimaika = '$toimvv-$toimkk-$toimpp'
              WHERE otunnus = '$kukarow[kesken]' and toimaika='$vtoimaika' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    if ($yhtiorow['valmistuksien_kasittely'] == 'Y' and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON")) {
      $query = "UPDATE tilausrivi
                JOIN tilausrivin_lisatiedot
                ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                  AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
                SET tilausrivin_lisatiedot.positio = '{$valmistuslinja}'
                WHERE tilausrivi.otunnus                      = '{$kukarow['kesken']}'
                AND tilausrivin_lisatiedot.positio            = '{$vvalmistuslinja}'
                AND tilausrivi.yhtio                          = '{$kukarow['yhtio']}'";
      $result = pupe_query($query);
    }

    $query = "UPDATE ";
    $postquery = " WHERE tunnus = '$kukarow[kesken]'";

    $query2 = "UPDATE ";
    $postquery2 = " WHERE otunnus = '$kukarow[kesken]'";
  }

  $crlf = array("\r", "\n"); // poistetaan rivinvaihdot kommentista
  $comments = str_replace($crlf, " ", $comments);

  $aquery = "SELECT *
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$clearing'";
  $vtresult = pupe_query($aquery);
  $vtrow = mysql_fetch_array($vtresult);

  if ($toim_nimitark == '' and $toim_nimi == '') {
    $toim_nimitark = $vtrow['nimitark'];
  }

  if (!isset($toim_nimi) or $toim_nimi == '') {
    $toim_nimi = $vtrow['nimi'];
  }

  if (!isset($toim_osoite) or $toim_osoite == '') {
    $toim_osoite = $vtrow['osoite'];
  }

  if (!isset($toim_postino) or $toim_postino == '') {
    $toim_postino = $vtrow['postino'];
  }

  if (!isset($toim_postitp) or $toim_postitp == '') {
    $toim_postitp = $vtrow['postitp'];
  }

  if (!isset($toim_maa) or $toim_maa == '') {
    $toim_maa = $vtrow['maa'];
  }

  if (!isset($eilahe)) {
    $eilahe = '';
  }

  if (!isset($valmistuslinja)) {
    $valmistuslinja = '';
  }

  //$yhtio_toimipaikka muuttuja asetetaan ainoastaan tilauksesta_varastosiirto funkkarissa.
  if (!isset($yhtio_toimipaikka)) {
    $yhtio_toimipaikka = $kukarow['toimipaikka'];
  }

  if (!empty($luokka)) {

    $prioriteettinro = t_avainsana("VARASTOLUOKKA", "", " and avainsana.selite='{$luokka}'", "", "", "selitetark_3");

    if (!is_numeric($prioriteettinro)) {
      $prioriteettinro = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$luokka}'", "", "", "selitetark_3");
    }
  }
  else {

    // haetaan oletusprioriteetti
    $prioriteettinro = t_avainsana("VARASTOLUOKKA", "", " and avainsana.selitetark_2 != ''", "", "", "selitetark_3");

    if (empty($prioriteettinro)) {
      $prioriteettinro = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selitetark_2 != ''", "", "", "selitetark_3");
    }
  }

  // Default 9 myös kannassa, niin laitetaan tännekki 9 jos avainsanoista ei löydy prioa
  if (!is_numeric($prioriteettinro)) $prioriteettinro = 9;

  $query .= "  lasku SET
        yhtio_nimi        = '$yhtiorow[nimi]',
        yhtio_osoite      = '$yhtiorow[osoite]',
        yhtio_postino     = '$yhtiorow[postino]',
        yhtio_postitp     = '$yhtiorow[postitp]',
        yhtio_maa         = '$yhtiorow[maa]',
        yhtio_ovttunnus   = '$yhtiorow[ovttunnus]',
        yhtio_kotipaikka  = '$yhtiorow[kotipaikka]',
        yhtio_toimipaikka = '$yhtio_toimipaikka',
        clearing          = '$clearing',
        maa               = '$vtrow[maa]',
        nimi              = '$vtrow[nimitys]',
        nimitark          = '$vtrow[nimi]',
        osoite            = '$vtrow[osoite]',
        postino           = '$vtrow[postino]',
        postitp           = '$vtrow[postitp]',
        toim_maa          = '$toim_maa',
        toim_nimi         = '$toim_nimi',
        toim_nimitark     = '$toim_nimitark',
        toim_osoite       = '$toim_osoite',
        toim_postino      = '$toim_postino',
        toim_postitp      = '$toim_postitp',
        toimaika          = '".$toimvv."-".$toimkk."-".$toimpp."',
        kerayspvm         = '".$kervv."-".$kerkk."-".$kerpp."',
        comments          = '$comments',
        eilahetetta       = '$eilahe',
        viesti            = '$viesti',
        yhtio             = '$kukarow[yhtio]',
        alatila           = '$alatila',
        varasto           = '$varasto',
        hyvaksynnanmuutos = '$luokka',
        prioriteettinro   = '$prioriteettinro',
        tunnusnippu       = '$tunnusnippu',
        kohde             = '$valmistuslinja',
        toimitustapa      = '$toimitustapa'";

  if ($toim == "VALMISTAVARASTOON") {
    $query .= ",tilaustyyppi = 'W', tila='V', ytunnus='$vtrow[nimitys]', liitostunnus='999999999'";

    $tee = "";
  }
  elseif ($toim == "SIIRTOLISTA") {
    $query .= ",tilaustyyppi = 'G', tila='G', ytunnus='SIIRTO', liitostunnus='999999999'";

    // Siirtolistat aina lähettäjän rahtisopparilla
    $query .= ",kohdistettu = 'K'";

    if (isset($chn) and $chn == 'GEN') {
      $query .= ",chn = 'GEN'"; // tällä erotellaan "tulosta siirtolista"-kohdassa generoidut ja käsin tehdyt siirtolistat
    }

    if (isset($chn) and $chn == 'KIR') {
      $query .= ",chn = 'KIR'"; // tällä erotellaan kirjanpidolliset varastosiirrot
    }

    $tee = "";
  }
  elseif ($toim == "SIIRTOTYOMAARAYS") {
    $query .= ",tilaustyyppi = 'S', tila='S', ytunnus='SISAINEN', liitostunnus='999999999'";

    $tee = "";
  }

  $query .= $postquery;
  $result = pupe_query($query);
  $id = mysql_insert_id($GLOBALS["masterlink"]);

  if (!isset($nokeskupdate) and (int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE and $session != "") {
    $query = "UPDATE kuka
              SET kesken = '$id'
              WHERE yhtio = '$kukarow[yhtio]' AND
              kuka        = '$kukarow[kuka]' AND
              session     = '$session'";
    $result = pupe_query($query);

    $kukarow["kesken"] = $id;
    $tilausnumero = $id;
  }
  elseif (isset($php_cli) and $php_cli === true and strpos($_SERVER['SCRIPT_NAME'], "generoi_siirtolista.php") !== FALSE) {
    // generoidaan siirtolistoja komentoriviltä
    $kukarow["kesken"] = $id;
    $tilausnumero = $id;
  }

  if ($toim == "SIIRTOTYOMAARAYS" and (int) $kukarow["kesken"] != 0) {
    //otetaan kantaa pyörän malliin
    if ($merkki2 != 'eimenu') {
      $merkki = $merkki2;
    }

    $query2 .= "tyomaarays SET
          yhtio      = '$kukarow[yhtio]',
          kotipuh     = '$kotipuh',
          tyopuh       = '$tyopuh',
          myyjaliike     = '$myyjaliike',
          ostopvm     = '$ostopvmvv-$ostopvmkk-$ostopvmpp',
          rekno       = '$rekno',
          mittarilukema   = '$mittarilukema',
          merkki       = '$merkki',
          mallivari     = '$mallivari',
          valmnro     = '$valmnro',
          tuotu       = '$tuotuvv-$tuotukk-$tuotupp',
          luvattu     = '$luvattuvv-$luvattukk-$luvattupp',
          komm1       = '$komm1',
          komm2       = '$komm2',
          suorittaja     = '$suorittaja',
          viite       = '$viite',
          otunnus     = '$kukarow[kesken]'";
    $query2 .= $postquery2;
    $result = pupe_query($query2);
  }

  if ($toim == 'SIIRTOLISTA' and $tilausnumero != '' and $varasto != '' and $clearing != '') {
    //tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
    if ($tee == '' or $kopioitava_otsikko != 0) {
      //päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
      if ($tila == '' and !isset($jatka)) {
        $query = "UPDATE kuka
                  SET kesken=0
                  WHERE session = '$session'";
        $result = pupe_query($query);

        $kukarow['kesken']   = 0;
        $tilausnumero     = 0;
      }
    }
  }

  $valmistuslinja = "";
}
