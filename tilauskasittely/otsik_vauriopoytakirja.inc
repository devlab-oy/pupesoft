<?php

//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or $kukarow["kesken"] != 0)) {
  echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
  exit;
}

// Etsitään asiakasta
if (($nimi != '' and !isset($jatka) and $asiakasid == 0 and !isset($vaihdatoim_asiakas)) or ($tnimi != '' and !isset($jatka) and isset($vaihdatoim_asiakas))) {

  //tehdään asiakashaku yhteensopivuus
  $ytunnus = $nimi;
  $muutparametrit = 0;
  $kutsuja = "otsik.inc";

  if (isset($vaihdatoim_asiakas) and $tnimi != "") {
    $ytunnus = $tnimi;
    $osoite = $tosoite;
    $postitp = $tpostitp;
    $postino = $tpostino;
    $tila = "vaihdatoim_asiakas";
    $muutparametrit = $asiakasid;
    $kutsuja = "";
  }

  $asiakasid = "";
  $ahlopetus = "tilauskasittely/tilaus_myynti.php////toim=$toim//tee=$tee//tilausnumero=$tilausnumero//mista=$mista//tila=$tila//from=ASIAKASYLLAPITO";
  $lause = "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

  require "inc/asiakashaku.inc";

  if ($asiakasid == '' and $monta > 1) {
    //Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
    $tila = 'EI_NAYTETA';
  }
}

//Pakollisten kenttien tarkastus
if (isset($jatka)) {
  $pakolliset_errors = array();
  if (empty($tyo_alku_paiva) or empty($tyo_alku_aika)) {
    $pakolliset_errors['tyo_alku'] = t('Työ alkoi puuttuu');
  }
  if (empty($tyo_loppu_paiva) or empty($tyo_loppu_aika)) {
    $pakolliset_errors['tyo_loppu'] = t('Työ päättyi puuttuu');
  }
  if (empty($ohjausmerkki)) {
    $pakolliset_errors['kaupunki_kunta'] = t('Kaupunki').'/'.t('kunta puuttuu');
  }
  if (empty($kohde)) {
    $pakolliset_errors['paikka_osoite'] = t('Paikka').'/'.t('osoite puuttuu');
  }
  if (empty($jalleenmyyja)) {
    $pakolliset_errors['verkostoalue'] = t('Verkostoalue puuttuu');
  }
  if (empty($tyomaarays_viite)) {
    $pakolliset_errors['tla'] = t('TLA puuttuu');
  }
  if (empty($vikakoodi)) {
    $pakolliset_errors['vaurio_kohde'] = t('Vaurion kohde puuttuu');
  }
  if (empty($merkki)) {
    $pakolliset_errors['vaurio_kohde_tyyppi'] = t('Vaurion kohteen tyyppi puuttuu');
  }
  if (empty($takuunumero)) {
    $pakolliset_errors['tikettinumero'] = t('Operaattorin tikettinumero puuttuu');
  }
  if (empty($prioriteetti)) {
    $pakolliset_errors['prioriteetti'] = t('Kiireellisyys puuttuu');
  }
  if (!isset($kotipuh)) {
    $pakolliset_errors['kotipuh'] = t('Tieto puuttuu');
  }
  if (empty($comments)) {
    $pakolliset_errors['comments'] = t('Tieto puuttuu');
  }
  if (empty($toim_nimi)) {
    $pakolliset_errors['nimi'] = t('Nimi puuttuu');
  }
  if (empty($toim_osoite)) {
    $pakolliset_errors['osoite'] = t('Osoite puuttuu');
  }
  if (empty($toim_postino)) {
    $pakolliset_errors['postino'] = t('Postinumero puuttuu');
  }
  if (empty($toim_postitp)) {
    $pakolliset_errors['postitp'] = t('Postitoimipaikka puuttuu');
  }
  if (empty($sisamaan_kuljetus)) {
    $pakolliset_errors['gsm'] = t('Matkapuhelin puuttuu');
  }
  if (empty($kirjoitin)) {
    $pakolliset_errors['ytunnus'] = t('Ytunnus puuttuu');
  }
  if (!isset($hyvaksy)) {
    $pakolliset_errors['hyvaksy'] = t('Tieto puuttuu');
  }
  if (!isset($mallivari)) {
    $pakolliset_errors['mallivari'] = t('Tieto puuttuu');
  }
  //pakollinen tieto jos kaapelinäyttö (mallivari) on suoritettu
  if (!empty($mallivari) and empty($tilausyhteyshenkilo)) {
    $pakolliset_errors['nayttaja'] = t('Näyttäjä puuttuu');
  }
  if (!empty($mallivari) and empty($kuljetus)) {
    $pakolliset_errors['kuljetus'] = t('Näyttäjän yhteystiedot puuttuu');
  }
  if (empty($sisviesti1)) {
    $pakolliset_errors['sisviesti1'] = t('Tieto puuttuu');
  }
  if (empty($suorittaja)) {
    $pakolliset_errors['suorittaja'] = t('Selvityksen antaja puuttuu');
  }
  if (empty($noutaja)) {
    $pakolliset_errors['suorittaja_puhelin'] = t('Selvityksen antajan puhelin puuttuu');
  }
  if (empty($huolitsija)) {
    $pakolliset_errors['huolitsija'] = t('Selvitykseestä vastaava puuttuu');
  }
  if (empty($jakelu)) {
    $pakolliset_errors['jakelu'] = t('Selvityksestä vastaavat puhelin puuttuu');
  }

  if (!empty($pakolliset_errors)) {
    unset($jatka);
    $tila = '';
  }
}

//tyomaarays.viite as tyomaarays_viite, selectoidaan erikseen koska resultista löytyy 2 viite kenttää: tyomaarays.viite ja lasku.viite. mysql driveri yliajaa tyomaarays.viiteen lasku.viitteellä
//kts. n rivi. 2717 Dynaaminen osio
$squery = "SELECT asiakas.*,
           tyomaarays.viite as tyomaarays_viite,
           tyomaarays.*,
           laskun_lisatiedot.*,
           lasku.*,
           lasku.hyvaksynnanmuutos as luokka
           FROM lasku
           JOIN asiakas
           ON ( asiakas.yhtio = lasku.yhtio
             AND asiakas.tunnus            = lasku.liitostunnus )
           LEFT JOIN laskun_lisatiedot
           ON ( laskun_lisatiedot.yhtio = lasku.yhtio
             AND laskun_lisatiedot.otunnus = lasku.tunnus )
           LEFT JOIN tyomaarays
           ON ( tyomaarays.yhtio = lasku.yhtio
             AND tyomaarays.otunnus        = lasku.tunnus )
           WHERE lasku.tunnus              = '{$kukarow['kesken']}'";
$srow_from = "LASKU";

$sresult = pupe_query($squery);
$srow = mysql_fetch_assoc($sresult);

if ($tila == "" and !isset($jatka)) {
  foreach ($_REQUEST as $key => $request_value) {
    if (array_key_exists($key, $srow)) {
      if (is_array($request_value)) {
        $srow[$key] = implode('#', $request_value);
      }
      else {
        $srow[$key] = $request_value;
      }
    }
  }
  if (!empty($srow)) {
    $aiheuttaja = array(
      'nimi'     => $srow['toim_nimi'],
      'nimitark'   => $srow['toim_nimitark'],
      'osoite'   => $srow['toim_osoite'],
      'postino'   => $srow['toim_postino'],
      'postitp'   => $srow['toim_postitp'],
      'maa'     => $srow['toim_maa'],
      'puhelin'   => $srow['aktiivinen_kuljetus'],
      'gsm'     => $srow['sisamaan_kuljetus'],
      'ytunnus'   => $srow['kirjoitin'],
    );
    $aiheuttaja_laskutus = array(
      'nimi'     => $srow['laskutus_nimi'],
      'nimitark'   => $srow['laskutus_nimitark'],
      'osoite'   => $srow['laskutus_osoite'],
      'postino'   => $srow['laskutus_postino'],
      'postitp'   => $srow['laskutus_postitp'],
      'maa'     => $srow['laskutus_maa'],
      'puhelin'   => $srow['rekisteilmo_rekisterinumero'],
      'gsm'     => $srow['rekisteilmo_paakayttokunta'],
      'ytunnus'   => $srow['rekisteilmo_kieli'],
    );


    $tyo_alku_array = explode(' ', $srow['tyo_alku']);
    if (count($tyo_alku_array) > 1) {
      $tyo_alku_array['paiva'] = date('d.m.Y', strtotime($tyo_alku_array[0]));
      $tyo_alku_array['aika'] = $tyo_alku_array[1];
    }

    $valmis_array = explode(' ', $srow['tyo_loppu']);
    if (count($valmis_array) > 1) {
      $valmis_array['paiva'] = date('d.m.Y', strtotime($valmis_array[0]));
      $valmis_array['aika'] = $valmis_array[1];
    }

    echo "<form method='post' autocomplete='off' name='paaformi' enctype='multipart/form-data'>";

    echo "<input type='hidden' name='tee' value='OTSIK'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='toim_kuka' value='{$toim_kuka}'>";
    echo "<input type='hidden' name='vauriopoytakirjan_tila' value='{$vauriopoytakirjan_tila}' />";
    echo "<input type='hidden' name='projektilla' value='$projektilla'>";
    echo "<input type='hidden' name='from' value='$from'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
    echo "<input type='hidden' name='mista' value='$mista'>";
    echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
    echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
    echo "  <input type='hidden' name='asiakasid' value='$asiakasid'>
      <input type='hidden' name='ylatila'              value = '$ylatila'>
      <input type='hidden' name='alatila'              value = '$alatila'>
      <input type='hidden' name='otsikolta'              value = 'YES'>
      <input type='hidden' name='laskutusvkopv'            value = '$srow[laskutusvkopv]'>
      <input type='hidden' name='ovttunnus'              value = '$srow[ovttunnus]'>
      <input type='hidden' name='toim_ovttunnus'            value = '$srow[toim_ovttunnus]'>
      <input type='hidden' name='kolm_ovttunnus'            value = '$srow[kolm_ovttunnus]'>
      <input type='hidden' name='chn'                value = '$srow[chn]'>
      <input type='hidden' name='verkkotunnus'            value = '$srow[verkkotunnus]'>
      <input type='hidden' name='ytunnus'              value = '$srow[ytunnus]'>
      <input type='hidden' name='kolm_maa'              value = '$srow[kolm_maa]'>
      <input type='hidden' name='tunnusnippu'            value = '$srow[tunnusnippu]'>
      <input type='hidden' name='projekti'             value = '$srow[projekti]'>
      <input type='hidden' name='jaksotettu'              value = '$srow[jaksotettu]'>
      <input type='hidden' name='tunnusnippu_tarjous'        value = '$srow[tunnusnippu_tarjous]'>
      <input type='hidden' name='kauppatapahtuman_luonne'      value = '$srow[kauppatapahtuman_luonne]'>
      <input type='hidden' name='asentaja'              value = '$asentaja'>
      <input type='hidden' name='tyojono'              value = '$tyojono'>
      <input type='hidden' name='liitostunnus'            value = '$srow[liitostunnus]'>";
    echo "<input type='hidden' ACCESSKEY='J' name='jatka' value='".t("Jatka")."' />";

    vauriopoytakirja_yleiset_kentat();

    $dynaamiset_kentat = hae_dynaamiset_kentat();

    $onko_liitetiedostoja = onko_liitetiedostoja($srow['tunnus']);

    echo "<table class='vauriopoytakirja_otsikko'>";

    echo "<tr>";
    echo "<th colspan='4'>".t('Vauriopöytäkirja')."</th>";
    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Työ alkoi');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='tyo_alku_paiva' value='{$tyo_alku_array['paiva']}' /> <input type='text' name='tyo_alku_aika' value='{$tyo_alku_array['aika']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['tyo_alku'].'</font>';
    echo "</td>";

    echo "<td>";
    echo t('Työ päättyi');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='tyo_loppu_paiva' value='{$valmis_array['paiva']}' /> <input type='text' name='tyo_loppu_aika' value='{$valmis_array['aika']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['tyo_loppu'].'</font>';
    echo "</td>";

    echo "</tr>";
    echo "</table>";

    echo "<table class='vauriopoytakirja_otsikko'>";

    echo "<tr>";
    echo "<th colspan='4'>".t('Tapahtumapaikka')."</th>";
    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Kaupunki').'/'.t('Kunta');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='ohjausmerkki' value='{$srow['ohjausmerkki']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['kaupunki_kunta'].'</font>';
    echo "</td>";

    echo "<td rowspan='2' align='center'>";
    echo t('Verkostoalue');
    echo "<br/>";
    echo "<input type='text' name='jalleenmyyja' value='{$srow['jalleenmyyja']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['verkostoalue'].'</font>';
    echo "</td>";

    echo "<td rowspan='2' align='center'>";
    echo t('TLA');
    echo "<br/>";
    echo "<input type='text' name='tyomaarays_viite' value='{$srow['tyomaarays_viite']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['tla'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Paikka').'/'.t('osoite');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='kohde' value='{$srow['kohde']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['paikka_osoite'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "<tr>";
    echo "<th colspan='4'>".t('Vaurio tapahtui').":</th>";
    echo "</tr>";

    echo "<tr>";

    $vaurion_kohde = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'vikakoodi');
    $vaurion_kohde = $vaurion_kohde[0];
    $radio_values = explode("#", $vaurion_kohde['selitetark_3']);

    echo "<td>";
    echo "<table>";
    foreach ($radio_values as $i => $value) {
      if (($i + 1) % 2 == 1)
        echo "<tr>";

      $selec = ($srow[$vaurion_kohde['selite']] == $value) ? "CHECKED" : "";
      echo "<td><input type='radio' name='{$vaurion_kohde['selite']}' value='$value' $selec>$value</input>";

      if (($i + 1) % 2 == 0)
        echo "</tr>";
    }
    echo "</table>";
    echo '<br/><font class="error">'.$pakolliset_errors['vaurio_kohde'].'</font>';
    echo "</td>";

    $kaapelin_tyyppi = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'merkki');
    $kaapelin_tyyppi = $kaapelin_tyyppi[0];
    $checkbox_arvot = explode("#", $kaapelin_tyyppi['selitetark_3']);

    echo "<td>";
    echo "<table>";
    foreach ($checkbox_arvot as $i => $checkbox) {
      if (($i + 1) % 3 == 1)
        echo "<tr>";

      $valinnat = explode('#', $srow[$kaapelin_tyyppi['selite']]);
      $selec = (in_array(str_replace(" ", "", $checkbox), $valinnat)) ? "CHECKED" : "";
      echo "<td><input type='checkbox' name='{$kaapelin_tyyppi['selite']}[]' value='".str_replace(" ", "", $checkbox)."' $selec>$checkbox</td>";

      if (($i + 1) % 3 == 0)
        echo "</tr>"; // Joka kolmanella rivillä break
    }
    echo "</table>";
    echo '<br/><font class="error">'.$pakolliset_errors['vaurio_kohde_tyyppi'].'</font>';
    echo "</td>";

    echo "<td align='center'>";
    echo t('Operaattorin tiketti numero');
    echo "<br/>";
    echo "<input type='text' name='takuunumero' value='{$srow['takuunumero']}' readonly />";
    echo '<br/><font class="error">'.$pakolliset_errors['tikettinumero'].'</font>';
    echo "</td>";

    $kiireellisyys = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'prioriteetti');
    $kiireellisyys = $kiireellisyys[0];
    $radio_values = explode("#", $kiireellisyys['selitetark_3']);

    echo "<td>";
    echo t('Kiireellisyys');
    echo "<table>";
    foreach ($radio_values as $i => $value) {
      if (($i + 1) % 2 == 1)
        echo "<tr>";

      $selec = ($srow[$kiireellisyys['selite']] == $value) ? "CHECKED" : "";
      echo "<td><input type='radio' name='{$kiireellisyys['selite']}' value='$value' $selec>$value</input>";

      if (($i + 1) % 2 == 0)
        echo "</tr>";
    }
    echo "</table>";
    echo '<br/><font class="error">'.$pakolliset_errors['prioriteetti'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "</table>";

    echo "<table class='vauriopoytakirja_otsikko'>";

    echo "<tr>";
    echo "<th>".t('SAP-numero').":</th>";
    echo "<td>";
    echo "<input type='text' name='sap_numero' value='{$srow['sap_numero']}' />";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t('Ilmoitettu poliisiviranoimaiselle').":</th>";
    echo "<td>";
    if ($srow['kotipuh'] == 1) {
      $checked1 = "CHECKED";
      $checked2 = "";
    }
    else {
      $checked1 = "";
      $checked2 = "CHECKED";
    }
    echo "<input type='radio' name='kotipuh' value='1' {$checked1} /> ".t('Kyllä')." <input type='radio' name='kotipuh' value='0' {$checked2} /> ".t('Ei');
    echo '<br/><font class="error">'.$pakolliset_errors['kotipuh'].'</font>';
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t('Vahinkotapahtuma').":</th>";
    echo "<td>";
    if ($onko_liitetiedostoja) {
      $checked1 = "CHECKED";
    }
    else {
      $checked1 = "";
    }
    echo "<input type='checkbox' disabled {$checked1} /> ".t('Valokuvia saatavilla');
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2'>";
    echo "<textarea class='vahinkotapahtuma_textarea' name='comments' />";
    echo $srow['comments'];
    echo "</textarea>";
    if (!empty($pakolliset_errors['comments'])) {
      echo '<br/><font class="error">'.$pakolliset_errors['comments'].'</font>';
    }
    echo "</td>";
    echo "</tr>";

    echo "</table>";

    aiheuttaja($aiheuttaja);
    aiheuttaja($aiheuttaja_laskutus, 'laskutusosoite');

    echo "<table class='vauriopoytakirja_otsikko'>";

    echo "<tr>";

    echo "<td>";
    echo t('Vakuutusyhtiö');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='myyjaliike' value='{$srow['myyjaliike']}' />";
    echo "</td>";

    echo "<td>";
    echo t('Auton tai koneen rekisterinumero');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='mittarilukema' value='{$srow['mittarilukema']}' />";
    echo "</td>";

    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Aiheuttaja kieltäytyy korvausvastuusta');
    echo "</td>";

    echo "<td colspan='3'>";
    if ($srow['hyvaksy'] == 1) {
      $checked1 = "CHECKED";
      $checked2 = "";
    }
    else {
      $checked1 = "";
      $checked2 = "CHECKED";
    }
    echo "<input type='radio' name='hyvaksy' value='1' {$checked1}/> ".t('Kyllä')."<input type='radio' name='hyvaksy' value='0' {$checked2}/> ".t('Ei');
    echo '<br/><font class="error">'.$pakolliset_errors['hyvaksy'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Kaapelinäyttö suoritettu');
    echo "</td>";

    echo "<td>";
    if ($srow['mallivari'] == 1) {
      $checked1 = "CHECKED";
      $checked2 = "";
    }
    else {
      $checked1 = "";
      $checked2 = "CHECKED";
    }
    echo "<input type='radio' name='mallivari' value='1' {$checked1}/> ".t('Kyllä')."<input type='radio' name='mallivari' value='0' {$checked2}/> ".t('Ei');
    echo '<br/><font class="error">'.$pakolliset_errors['mallivari'].'</font>';
    echo "</td>";

    echo "<td>";
    echo t('Näyttöpohjakirja lähetetty').'('.t('pakollinen').')';
    echo "</td>";

    echo "<td>";
    if ($srow['valmnro'] == 1) {
      $checked1 = "CHECKED";
    }
    else {
      $checked1 = "";
    }
    echo "<input type='checkbox' name='valmnro' {$checked1}/>";
    echo "</td>";

    echo "</tr>";

    echo "<tr>";

    echo "<td>";
    echo t('Karttakaivuu');
    echo "</td>";

    echo "<td>";
    if ($srow['tilno'] == 1) {
      $checked1 = "CHECKED";
    }
    else {
      $checked1 = "";
    }
    echo "<input type='checkbox' name='tilno' {$checked1}/>";
    echo "</td>";

    echo "<td>";
    echo t('Näyttäjä');
    echo "<br/>";
    echo t('Yhteystiedot');
    echo "</td>";

    echo "<td>";
    echo "<input type='text' name='tilausyhteyshenkilo' value='{$srow['tilausyhteyshenkilo']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['nayttaja'].'</font>';
    echo "<br/>";
    echo "<input type='text' name='kuljetus' value='{$srow['kuljetus']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['kuljetus'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "</table>";

    echo "<table class='vauriopoytakirja_otsikko'>";

    echo "<tr>";
    echo "<th colspan='4'>".t('Lisätietoja TSF:lle')."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='4'>";
    echo "<textarea class='vahinkotapahtuma_textarea' name='sisviesti1'>";
    echo $srow['sisviesti1'];
    echo "</textarea>";
    echo '<br/><font class="error">'.$pakolliset_errors['sisviesti1'].'</font>';
    echo "</td>";
    echo "</tr>";

    echo "<tr>";

    echo "<th>".t('Selvityksen antaja')."</th>";

    echo "<td>";
    echo "<input type='text' name='suorittaja' value='{$srow['suorittaja']}' readonly />";
    echo '<br/><font class="error">'.$pakolliset_errors['suorittaja'].'</font>';
    echo "</td>";

    echo "<th>".t('Puhelin')."</th>";

    echo "<td>";
    echo "<input type='text' name='noutaja' value='{$srow['noutaja']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['suorittaja_puhelin'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "<tr>";
    echo "<th colspan='4'>".t('Lisätietoja')."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='4'>";
    echo "<textarea class='vahinkotapahtuma_textarea' name='sisviesti2'>";
    echo $srow['sisviesti2'];
    echo "</textarea>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";

    echo "<th>".t('Selvityksestä vastaava')."</th>";

    echo "<td>";
    echo "<input type='text' name='huolitsija' value='{$srow['huolitsija']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['huolitsija'].'</font>';
    echo "</td>";

    echo "<th>".t('Puhelin')."</th>";

    echo "<td>";
    echo "<input type='text' name='jakelu' value='{$srow['jakelu']}' />";
    echo '<br/><font class="error">'.$pakolliset_errors['jakelu'].'</font>';
    echo "</td>";

    echo "</tr>";

    echo "<tr>";

    echo "<td class='back' valign='top'>";
    echo "<input type='submit' value='".t('Jatka')."' />";
    echo "</td>";

    echo "</tr>";

    echo "</table>";

    echo "</form>";

    echo "<table>";

    echo "<tr>";
    $lopetus_temp = lopetus($lopetus, '', true);
    if ($toim_kuka == 'urakoitsija') {
      echo "<td class='back' valign='top'>";
      echo "<form name='tlepaamaan' method='post' action='{$lopetus_temp}'>";
      echo "<input type='hidden' name='tee' value='siirra_tarkastajalle' />";
      echo "<input type='hidden' name='tyomaarays_tunnus' value='{$tilausnumero}' />";
      echo "<input type='submit' value='".t("Siirrä tarkastajalle")."' />";
      echo "</form>";
      echo "</td>";
    }
    else {
      if ($srow['tyostatus'] == '2') {
        echo "  <td class='back' valign='top'>
              <form name='tlepaamaan' method='post' action='{$lopetus_temp}'>
                <input type='hidden' name='tee' value='anna_laskutuslupa' />
                <input type='hidden' name='tyomaarays_tunnus' value='{$tilausnumero}' />
                <input type='submit' value='".t("Anna laskutus lupa")."' />
              </form>
            </td>";

        echo "  <td class='back' valign='top'>
              <form name='tlepaamaan' method='post' action='{$lopetus_temp}'>
                <input type='hidden' name='tee' value='siirra_urakoitsijalle' />
                <input type='hidden' name='tyomaarays_tunnus' value='{$tilausnumero}' />
                <input type='submit' value='".t("Siirrä takaisin urakoitsijalle")."' />
              </form>
            </td>";
      }
      else if ($srow['tyostatus'] == '3') {
          echo "  <td class='back' valign='top'>
              <form name='tlepaamaan' method='post' action='{$lopetus_temp}'>
                <input type='hidden' name='tee' value='merkitse_maksetuksi' />
                <input type='hidden' name='tyomaarays_tunnus' value='{$tilausnumero}' />
                <input type='submit' value='".t("Merkitse maksetuksi")."' />
              </form>
            </td>";
        }
      else {
        echo "  <td class='back' valign='top'>
              <form name='tlepaamaan' method='post' action='{$lopetus_temp}'>
                <input type='hidden' name='tee' value='' />
                <input type='hidden' name='tyomaarays_tunnus' value='{$tilausnumero}' />
                <input type='submit' value='".t("Palaa selaus näkymään")."' />
              </form>
            </td>";
      }
    }

    echo "</tr>";
    echo "</table>";
  }
  else {
    asiakkaan_valinta_formi();
  }
}

if (isset($jatka)) {
  // liitostunnusfixaus
  if ((int)$liitostunnus == 0)
    $liitostunnus = $asiakasid;

  $asiakas = hae_asiakas($liitostunnus);
  if ($liitostunnus != "") {
    $chn = $asiakas['chn'];
    if ($chn == '') {
      $chn = '100';
    }
  }

  $yhtioalv_tilino = $yhtiorow["alv"];

  if (!isset($clearing)) {
    $clearing = $laskurow['clearing'];
  }

  $query = "";
  if ($kukarow['kesken'] == 0) {
    $insert = true;
    $query .= "  INSERT INTO lasku
          SET laatija = '{$kukarow['kesken']}',
          luontiaika = NOW(),";
    $alatila = "";
  }
  else {
    $insert = false;
    $query .= "  UPDATE lasku
          SET muuttaja = '{$kukarow['kesken']}',
          muutospvm = NOW(),";
  }

  $query .= "  aktiivinen_kuljetus          = '{$aktiivinen_kuljetus}',
        sisamaan_kuljetus          = '{$sisamaan_kuljetus}',
        alatila               = '$alatila',
        alv                 = '$alv',
        alv_tili              = '$yhtioalv_tilino',
        asiakkaan_tilausnumero        = '$asiakkaan_tilausnumero',
        chn                  = '$chn',
        clearing              = '$clearing',
        comments               = '$comments',
        eilahetetta             = '$eilahe',
        lahetetyyppi             = '$lahetetyyppi',
        laskutyyppi              = '{$laskutyyppi}',
        erikoisale               = '$erikoisale',
        erpcm                = '$erpcmvv-$erpcmkk-$erpcmpp',
        huolitsija               = '$huolitsija',
        hyvaksynnanmuutos           = '$luokka',
        prioriteettinro            = '$prioriteettinro',
        jakelu                 = '$jakelu',
        jaksotettu              = '$jaksotettu',
        jtkielto              = '$jtkielto',
        kassalipas              = '$kassalipas',
        kauppatapahtuman_luonne        = '$kauppatapahtuman_luonne',
        kerayspvm               = '$kervv-$kerkk-$kerpp',
        keraysvko               = '$keraysvko',
        ketjutus               = '$ketjutus',
        kirjoitin              = '$kirjoitin',
        kohde                = '$kohde',
        kohdistettu             = '$maksaja',
        kolmikantakauppa          = '{$kolmikantakauppa}',
        kuljetus               = '$kuljetus',
        kuljetusmuoto            = '',
        label                = '{$label}',
        laskutusvkopv             = '$laskutusvkopv',
        liitostunnus            = '$liitostunnus',
        maksuehto               = '$maksuehto',
        maksuteksti             = '$maksuteksti',
        myyja                 = '$myyja',
        allekirjoittaja            = '$allekirjoittaja',
        noutaja                = '$noutaja',
        ohjausmerkki            = '$ohjausmerkki',
        olmapvm                = '$voimaika_vv-$voimaika_kk-$voimaika_pp',
        osatoimitus              = '$osatoimitus',
        ostotilauksen_kasittely       = '{$ostotilauksen_kasittely}',
        ovttunnus               = '$ovttunnus',
        piiri                 = '$piiri',
        rahtivapaa              = '$rahtivapaa',
        sisainen               = '$sisainen',
        sisviesti1               = '$sisviesti1',
        sisviesti2              = '$sisviesti2',
        sisviesti3              = '$sisviesti3',
        splittauskielto            = '$splittauskielto',
        tilaustyyppi            = '$tilaustyyppi',
        tilausvahvistus           = '$tilausvahvistus',
        tilausyhteyshenkilo          = '$tilausyhteyshenkilo',
        toimaika               = '$toimvv-$toimkk-$toimpp',
        toimitusehto             = '$toimitusehto',
        toimitustapa             = '$toimitustapa',
        toimvko                 = '$toimvko',
        toim_maa               = '$toim_maa',
        toim_nimi               = '$toim_nimi',
        toim_nimitark             = '$toim_nimitark',
        toim_osoite             = '$toim_osoite',
        toim_ovttunnus             = '$toim_ovttunnus',
        toim_postino             = '$toim_postino',
        toim_postitp             = '$toim_postitp',
        tunnusnippu              = '$tunnusnippu',
        varasto               = '$varasto',
        verkkotunnus            = '$verkkotunnus',
        vienti                 = '$vienti',
        vienti_kurssi            = '$kurssi',
        viesti                 = '$viesti',
        viikorkopros             = '$yhtiorow[viivastyskorko]',
        yhtio                 = '$kukarow[yhtio]',
        yhtio_kotipaikka          = '$yhtiokotipaikka',
        yhtio_maa              = '$yhtiomaa',
        yhtio_nimi              = '$yhtionimi',
        yhtio_osoite            = '$yhtioosoite',
        yhtio_ovttunnus            = '$yhtioovttunnus',
        yhtio_postino            = '$yhtiopostino',
        yhtio_postitp            = '$yhtiopostitp',
        yhtio_toimipaikka          = '$yhtiotoimipaikka',
        ytunnus                = '$ytunnus'";

  if (!$insert) {
    $query .= "  WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnumero}'";
  }

  pupe_query($query);

  $id = mysql_insert_id();

  if ($id != 0) {
    $query = "UPDATE kuka
              SET kesken = '{$id}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kuka    = '{$kukarow['kuka']}'
              AND session = '{$session}'";
    pupe_query($query);

    $kukarow["kesken"] = $id;
    $tilausnumero = $id;
  }

  if ((int)$kukarow["kesken"] != 0) {
    $tyo_alku = date('Y-m-d', strtotime($tyo_alku_paiva)).' '.$tyo_alku_aika;
    $tyo_loppu = date('Y-m-d', strtotime($tyo_loppu_paiva)).' '.$tyo_loppu_aika;

    if ($valmnro != '') {
      $valmnro = 1;
    }
    else {
      $valmnro = 0;
    }

    if ($tilno != '') {
      $tilno = 1;
    }
    else {
      $tilno = 0;
    }

    //Dynaamisen osion checkbox toiminnallisuutta varten. Kaikki tyomääräyksen requestista tulevat kentät
    //pitää käydä läpi ja katsoa onko array (checkbox), paitsi date ja datetime tyyppiset kentät
    $dynaamiset_kentat = array(
      'kotipuh'     => $kotipuh,
      'tyopuh'     => $tyopuh,
      'myyjaliike'   => $myyjaliike,
      'rekno'       => $rekno,
      'mittarilukema'   => $mittarilukema,
      'merkki'     => $merkki,
      'mallivari'     => $mallivari,
      'valmnro'     => $valmnro,
      'komm1'       => $komm1,
      'komm2'       => $komm2,
      'suorittaja'   => $suorittaja,
      'viite'       => $viite,
      'prioriteetti'   => $prioriteetti,
      'jalleenmyyja'   => $jalleenmyyja,
      'tehdas'     => $tehdas,
      'tyoaika'     => $tyoaika,
      'vikakoodi'     => $vikakoodi,
      'takuunumero'   => $takuunumero,
      'tyokoodi'     => $tyokoodi,
      'hyvaksy'     => $hyvaksy,
    );

    foreach ($dynaamiset_kentat as $kentan_nimi => $dynaaminen_kentta) {
      if (is_array($dynaaminen_kentta)) {
        ${$kentan_nimi} = implode('#', $dynaaminen_kentta);
      }
    }

    if ($insert) {
      $query = "INSERT INTO tyomaarays
                SET laatija = '{$kukarow['kuka']}',
                luontiaika = NOW(),";
    }
    else {
      $query = "UPDATE tyomaarays
                SET ";
    }

    $query .= "  yhtio      = '$kukarow[yhtio]',
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
          valmis      = '$valmis',
          tyo_alku    = '$tyo_alku',
          tyo_loppu    = '$tyo_loppu',
          komm1       = '$komm1',
          komm2       = '$komm2',
          suorittaja     = '$suorittaja',
          viite       = '$tyomaarays_viite',
          prioriteetti  = '$prioriteetti',
          jalleenmyyja   = '$jalleenmyyja',
          tehdas      = '$tehdas',
          tyoaika      = '$tyoaika',
          tilno      = '$tilno',
          vikakoodi    = '$vikakoodi',
          takuunumero    = '$takuunumero',
          sap_numero    = '$sap_numero',
          tyokoodi    = '$tyokoodi',
          hyvaksy      = '$hyvaksy',
          lisakuluprosentti = '{$yhtiorow['lisakuluprosentti']}',
          tyojono      = '{$tyojono}',
          tyostatus    = '{$tyostatus}',";

    if ($insert) {
      $query .= " otunnus = '{$kukarow['kesken']}'";
    }
    else {
      $query = substr($query, 0, -1);
      $query .= " WHERE otunnus = '{$kukarow['kesken']}'";
    }

    pupe_query($query);

    $ylatila = 'A';

    if ($insert) {
      $query = "INSERT INTO laskun_lisatiedot
                SET laatija = '{$kukarow['kuka']}',
                luontiaika = NOW(),";
    }
    else {
      $query = "UPDATE laskun_lisatiedot
                SET ";
    }

    $query .= "  laskutus_nimi = '{$laskutus_nimi}',
          laskutus_nimitark = '{$laskutus_nimitark}',
          laskutus_osoite = '{$laskutus_osoite}',
          laskutus_postino = '{$laskutus_postino}',
          laskutus_postitp = '{$laskutus_postitp}',
          laskutus_maa = '{$laskutus_maa}',
          rekisteilmo_rekisterinumero = '{$rekisteilmo_rekisterinumero}',
          rekisteilmo_paakayttokunta = '{$rekisteilmo_paakayttokunta}',
          rekisteilmo_kieli = '{$rekisteilmo_kieli}'";

    if ($insert) {
      $query .= " otunnus = '{$kukarow['kesken']}'";
    }
    else {
      $query .= " WHERE otunnus = '{$kukarow['kesken']}'";
    }

    pupe_query($query);
  }

  $tee = "";
}

// Palutetaan puhtaat kukarow ja yhtiörow
$kukarow = hae_kukarow($kukarow['kuka'], $kukarow['yhtio']);
$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

unset($alv);

function asiakkaan_valinta_formi() {
  global $lopetus, $ruutulimit, $toim, $projektilla, $from, $tilausnumero, $mista, $orig_tila, $orig_alatila, $srow;

  echo "<form method='post' autocomplete='off' name='paaformi' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='OTSIK'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='projektilla' value='$projektilla'>";
  echo "<input type='hidden' name='from' value='$from'>";
  echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
  echo "<input type='hidden' name='mista' value='$mista'>";
  echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
  echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";

  echo "<table style='border-collapse:collapse;'>";

  echo "<tr>";
  echo "<th colspan='2' align='left' valign='top'>".t("Ostaja").":</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td valign='top'>".t("Nimi").": </td>";
  echo "<td>";
  echo "<input type='text' name='nimi' size='35' value='{$srow['nimi']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td></td>";
  echo "<td>";
  echo "<input type='text' name='nimitark' size='35' value='{$srow['nimitark']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td valign='top'>".t("Osoite").": </td>";
  echo "<td>";
  echo "<input type='text' name='osoite' size='35' value='{$srow['osoite']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td valign='top'>".t("Postitp").": </td>";
  echo "<td>";
  echo "<input type='text' name='postino' size='10' value='{$srow['postino']}' /> <input type='text' name='postitp' size='21' value='{$srow['postitp']}' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='".t("Hae")."' />";

  echo "</form>";
}

function aiheuttaja($params, $tyyppi = 'aiheuttaja') {
  global $pakolliset_errors;

  if ($tyyppi == 'aiheuttaja') {
    $otsikko = t('Aiheuttaja');
  }
  else {
    $otsikko = t('Laskutusosoite');
  }
  echo "<table class='vauriopoytakirja_otsikko'>";

  echo "<tr>";
  echo "<th colspan='4'>{$otsikko}</th>";
  echo "</tr>";

  if ($tyyppi == 'aiheuttaja') {
    echo "<tr>";
    echo "<td colspan='4'>";
    if ($params['nimi'] == '') {
      $checked = "CHECKED";
    }
    echo "<input type='checkbox' {$checked} disabled /> ".t('Aiheuttaja tuntematon');
    echo "</td>";
    echo "</tr>";
  }

  $aiheuttaja_nimet = array(
    'nimi'     => array(
      'aiheuttaja'   => 'toim_nimi',
      'laskutusosoite' => 'laskutus_nimi',
    ),
    'nimitark'   => array(
      'aiheuttaja'   => 'toim_nimitark',
      'laskutusosoite' => 'laskutus_nimitark',
    ),
    'osoite'   => array(
      'aiheuttaja'   => 'toim_osoite',
      'laskutusosoite' => 'laskutus_osoite',
    ),
    'postino'   => array(
      'aiheuttaja'   => 'toim_postino',
      'laskutusosoite' => 'laskutus_postino',
    ),
    'postitp'   => array(
      'aiheuttaja'   => 'toim_postitp',
      'laskutusosoite' => 'laskutus_postitp',
    ),
    'maa'     => array(
      'aiheuttaja'   => 'toim_maa',
      'laskutusosoite' => 'laskutus_maa',
    ),
    'puhelin'   => array(
      'aiheuttaja'   => 'aktiivinen_kuljetus',
      'laskutusosoite' => 'rekisteilmo_rekisterinumero',
    ),
    'gsm'     => array(
      'aiheuttaja'   => 'sisamaan_kuljetus',
      'laskutusosoite' => 'rekisteilmo_paakayttokunta',
    ),
    'ytunnus'   => array(
      'aiheuttaja'   => 'kirjoitin',
      'laskutusosoite' => 'rekisteilmo_kieli',
    ),
  );

  echo "<tr>";
  echo "<td>";
  echo t('Nimi');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['nimi'][$tyyppi]}' value='{$params['nimi']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['nimi'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['nimi'].'</font>';
  }
  echo "</td>";

  echo "<td>";
  echo t('Puhelinnumero');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['puhelin'][$tyyppi]}' value='{$params['puhelin']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['puhelin'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['puhelin'].'</font>';
  }
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo t('Osoite');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['osoite'][$tyyppi]}' value='{$params['osoite']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['osoite'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['osoite'].'</font>';
  }
  echo "</td>";

  echo "<td>";
  echo t('Matkapuhelin');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['gsm'][$tyyppi]}' value='{$params['gsm']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['gsm'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['gsm'].'</font>';
  }
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo t('Postilokero');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['nimitark'][$tyyppi]}' value='{$params['nimitark']}'/>";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['postilokero'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['postilokero'].'</font>';
  }
  echo "</td>";

  echo "<td>";
  echo t('Y-Tunnus');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['ytunnus'][$tyyppi]}' value='{$params['ytunnus']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['ytunnus'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['ytunnus'].'</font>';
  }
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo t('Postinumero');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['postino'][$tyyppi]}' value='{$params['postino']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['postino'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['postino'].'</font>';
  }
  echo "</td>";

  echo "<td>";
  echo t('Postitoimipaikka');
  echo "</td>";

  echo "<td>";
  echo "<input type='text' name='{$aiheuttaja_nimet['postitp'][$tyyppi]}' value='{$params['postitp']}' />";
  if ($tyyppi == 'aiheuttaja' and !empty($pakolliset_errors['postitp'])) {
    echo '<br/><font class="error">'.$pakolliset_errors['postitp'].'</font>';
  }
  echo "</td>";
  echo "</tr>";

  echo "</table>";
}

function vauriopoytakirja_yleiset_kentat() {
  global $kukarow, $yhtiorow, $srow;
  $yhtion_oletusalv = hae_yhtion_oletusalv();

  if (isset($srow)) {
    if ($srow['alv'] == 0) {
      $sel_veroton = 'selected';
      $sel_verollinen = '';
    }
    if ($srow['alv'] == $yhtion_oletusalv) {
      $sel_verollinen = 'selected';
      $sel_veroton = '';
    }
  }

  $tyostatukset = hae_tyomaarayksen_statukset();

  echo "<table class='vauriopoytakirja_otsikko'>";

  echo "<tr>";
  echo "<th colspan='4'>".t('Yleiset tiedot')."</th>";
  echo "</tr>";

  echo "<tr>";

  echo "<td>";
  echo t('ALV');
  echo "</td>";

  echo "<td>";
  echo "<select name='alv'>";
  echo "<option value='{$yhtion_oletusalv}' {$sel_verollinen}>".t("Verollinen myynti")."</option>";
  echo "<option value='0' {$sel_veroton}>".t("Veroton myynti")."</option>";
  echo "</select>";
  echo "</td>";

  echo "<td>";
  echo t('Tila');
  echo "</td>";

  echo "<td>";
  echo "<select name='tyostatus'>";
  $sel = "";
  foreach ($tyostatukset as $tyostatus) {
    if ($tyostatus['selite'] == $srow['tyostatus']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$tyostatus['selite']}' {$sel}>{$tyostatus['selitetark']}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";

  echo "</tr>";

  echo "<tr>";

  echo "<td>";
  echo t('Toimintavarmuus');
  echo "</td>";

  echo "<td>";
  //@TODO function tyomaarays_toimintavarmuus($kiireellisyys, $paivat); pitäisi ehkä toimia ajaxilla
  echo '-';
  echo "</td>";

  echo "<td>";
  //@TODO function function tyomaarays_palautusvarmuus($palautus_pvm); pitäisi ehkä toimia ajaxilla
  echo '-';
  echo "</td>";

  echo "<td>";
  echo t('ALV');
  echo "</td>";

  echo "</tr>";

  echo "</table>";
}

function hae_dynaamiset_kentat() {
  global $kukarow, $yhtiorow;

  $dynaamiset_kentat_result = t_avainsana("TYOM_TYOKENTAT", "", "and avainsana.selitetark != ''");

  $dynaamiset_kentat = array();
  while ($dynaaminen_kentta = mysql_fetch_assoc($dynaamiset_kentat_result)) {
    $dynaamiset_kentat[] = $dynaaminen_kentta;
  }

  return $dynaamiset_kentat;
}
