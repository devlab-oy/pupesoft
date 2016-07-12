<?php

  // Päivitetään tiedot tietokantaan
  if ($tee == 'toimitusehdot' and $tilausnumero > 0 and (isset($PAIVIT) or isset($VALMIS))) {

    if ($rekisteilmo_laminointi == "") {
      $rekisteilmo_laminointi = "E";
    }
    if ($rekisteilmo_suoramarkkinointi == "") {
      $rekisteilmo_suoramarkkinointi = "E";
    }

    $query = "UPDATE laskun_lisatiedot
            SET vakuutushak_vakuutusyhtio       = '$vakuutushak_vakuutusyhtio',
            vakuutushak_alkamispaiva        = '$vva-$kka-$ppa',
            vakuutushak_kaskolaji           = '$vakuutushak_kaskolaji',
            vakuutushak_maksuerat           = '$vakuutushak_maksuerat',
            vakuutushak_perusomavastuu        = '$vakuutushak_perusomavastuu',
            vakuutushak_runko_takila_purjeet    = '$vakuutushak_runko_takila_purjeet',
            vakuutushak_moottori          = '$vakuutushak_moottori',
            vakuutushak_varusteet           = '$vakuutushak_varusteet',
            vakuutushak_yhteensa          = '$vakuutushak_yhteensa',
            rekisteilmo_rekisterinumero       = '$rekisteilmo_rekisterinumero',
            rekisteilmo_paakayttokunta        = '$rekisteilmo_paakayttokunta',
            rekisteilmo_kieli             = '$rekisteilmo_kieli',
            rekisteilmo_tyyppi            = '$rekisteilmo_tyyppi',
            rekisteilmo_omistajienlkm         = '$rekisteilmo_omistajienlkm',
            rekisteilmo_omistajankotikunta      = '$rekisteilmo_omistajankotikunta',
            rekisteilmo_lisatietoja         = '$rekisteilmo_lisatietoja',
            rekisteilmo_laminointi          = '$rekisteilmo_laminointi',
            rekisteilmo_suoramarkkinointi       = '$rekisteilmo_suoramarkkinointi',
            rekisteilmo_veneen_nimi         = '$rekisteilmo_veneen_nimi',
            rekisteilmo_omistaja          = '$rekisteilmo_omistaja',
            rahlaskelma_hetu_tarkistus        = '$rahlaskelma_hetu_tarkistus',
            rahlaskelma_hetu_tarkastaja       = '$rahlaskelma_hetu_tarkastaja',
            rahlaskelma_hetu_asiakirjanro     = '$rahlaskelma_hetu_asiakirjanro',
            rahlaskelma_hetu_asiakirjamyontaja    = '$rahlaskelma_hetu_asiakirjamyontaja',
            rahlaskelma_hetu_kolm_tarkistus     = '$rahlaskelma_hetu_kolm_tarkistus',
            rahlaskelma_hetu_kolm_asiakirjanro    = '$rahlaskelma_hetu_kolm_asiakirjanro',
            rahlaskelma_hetu_kolm_asiakirjamyontaja = '$rahlaskelma_hetu_kolm_asiakirjamyontaja',
            rahlaskelma_hetu_kolm_tarkastaja    = '$rahlaskelma_hetu_kolm_tarkastaja',
            rahlaskelma_takuukirja          = '$rahlaskelma_takuukirja',
            rahlaskelma_huoltokirja         = '$rahlaskelma_huoltokirja',
            rahlaskelma_kayttoohjeet        = '$rahlaskelma_kayttoohjeet',
            rahlaskelma_opastus           = '$rahlaskelma_opastus',
            rahlaskelma_kuntotestitodistus      = '$rahlaskelma_kuntotestitodistus',
            rahlaskelma_kayttotarkoitus       = '$rahlaskelma_kayttotarkoitus'
            WHERE yhtio  = '$kukarow[yhtio]'
            and otunnus = '$tilausnumero'";
    $res = mysql_query($query) or pupe_error($query);

    if (isset($VALMIS)) {
      $tee = "";

      if ($lopetus != '') {
        lopetus($lopetus, "META");
      }
    }
  }

  if ($tee == 'toimitusehdot') {
  echo "<form method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='tee' value='toimitusehdot'>";

  // Ostajan tiedot
  echo "<table>";

  echo "<tr>
    <th>".t("Toimitusaika")."</th>
    <td><input type='text' name='toimpp' value='$toimpp' size='3'>-
        <input type='text' name='toimkk' value='$toimkk' size='3'>-
        <input type='text' name='toimvv' value='$toimvv' size='6'></td>
    </tr>";

    echo "<tr>
      <th>".t("Toimitustapa")."</th>";

   // Lukitaan rahtikirjaan vaikuttavat tiedot jos/kun rahtikirja on tulostettu
   $query = "SELECT *
             FROM rahtikirjat
             WHERE yhtio     = '$kukarow[yhtio]'
             AND otsikkonro  = '$tilausnumero'
             AND tulostettu != '0000-00-00 00:00:00'
             LIMIT 1";
   $rakre_chkres = pupe_query($query);

   $state_chk = "";

   if (mysql_num_rows($rakre_chkres) > 0) {
     $state_chk = 'disabled';
   }

   $_varasto = hae_varasto($laskurow['varasto']);

   $params = array(
     'asiakas_tunnus'      => $laskurow['liitostunnus'],
     'lasku_toimipaikka'   => $laskurow['yhtio_toimipaikka'],
     'varasto_toimipaikka' => $_varasto['toimipaikka'],
     'kohdevarasto'        => $laskurow['clearing'],
     'lahdevarasto'        => $laskurow['varasto'],
   );

   $toimitustavat = hae_toimitustavat($params);

   echo "<td><select name='toimitustapa' {$state_chk}>";

   foreach ($toimitustavat as $toimitustapa) {

     if (!empty($toimitustapa['sallitut_maat']) and !stristr($toimitustapa['sallitut_maat'], $laskurow['toim_maa'])) {
       continue;
     }

     if (in_array($toimitustapa['extranet'], array('', 'M'))
       or $toimitustapa['selite'] == $laskurow['toimitustapa']
       or $toimitustapa['selite'] == $asiakasrow['toimitustapa']) {

       $sel = "";
       if ($toimitustapa["selite"] == $laskurow["toimitustapa"]) {
         $sel = 'selected';
         $tm_toimitustaparow   = $toimitustapa;
         $toimitustavan_tunnus = $toimitustapa['tunnus'];
       }

       echo "<option id='toimitustapa_$toimitustapa[tunnus]' value='$toimitustapa[selite]' $sel>";
       echo t_tunnus_avainsanat($toimitustapa, "selite", "TOIMTAPAKV");
       echo "</option>";
     }
   }

  echo "</select></td>";
  echo "</tr>";

  if ($lisrow["rahlaskelma_takuukirja"] != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<tr>
    <th>".t("Ostajalle annettu takuukirja")."</th>
    <td><input type='checkbox' name='rahlaskelma_takuukirja' value='X' $sel></td>
    </tr>";


  if ($lisrow["rahlaskelma_huoltokirja"] != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<tr>
    <th>".t("Ostajalle annettu huoltokirja")."</th>
    <td><input type='checkbox' name='rahlaskelma_huoltokirja' value='X' $sel></td>
    </tr>";

  if ($lisrow["rahlaskelma_kayttoohjeet"] != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<tr>
    <th>".t("Ostajalle annettu käyttöohjeet")."</th>
    <td><input type='checkbox' name='rahlaskelma_kayttoohjeet' value='X' $sel></td>
    </tr>";

  if ($lisrow["rahlaskelma_opastus"] != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<tr>
    <th>".t("Ostajalle annettu suullinen opastus")."</th>
    <td><input type='checkbox' name='rahlaskelma_opastus' value='X' $sel></td>
    </tr>";

  if ($lisrow["rahlaskelma_kuntotestitodistus"] != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<tr>
    <th>".t("Ostajalle annettu kuntotestitodistus")."</th>
    <td><input type='checkbox' name='rahlaskelma_kuntotestitodistus' value='X' $sel></td></tr>";



    echo "<tr><td class='back'><br><br></td></tr>";

    echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='PAIVIT' value='".t("Tallenna")."'></td></tr>";
    echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='VALMIS' value='".t("Tallenna ja siirry takaisin tilaukselle")."'></td></tr>";
    echo "</form>";

    echo "<form name='valmis' action='tulostakopio.php' method='post' name='tulostaform_vakhak' id='tulostaform_vakhak' class='multisubmit'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='otunnus' value='$tilausnumero'>";


    echo "<tr><th>".t("Näytä lomake").":</th>";

    echo "<td>";
    echo "<select name='toim'>";
    echo "<option value='TARJOUS'>Tarjous</value>";
    echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
    echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
    echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
    echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
    echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</value>";
    echo "</select></td>";
    echo "<td><input type='submit' value='".t("Näytä")."' onClick=\"js_openFormInNewWindow('tulostaform_vakhak', 'tulosta_vakhak'); return false;\"></td>";
    echo "</form></table>";
  }
