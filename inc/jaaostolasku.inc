<?php

function jaalasku($jaettavalasku, $vahennettavasumma_valuutassa) {

  global $yhtiorow, $kukarow, $palvelin2;

  $fields  = "";
  $values  = "";
  $tunnus  = (int) $jaettavalasku;
  $vahennettavasumma_valuutassa = (float) str_replace(",", ".", $vahennettavasumma_valuutassa);

  // Katsotaan, ett‰ lasku on hyv‰ksytt‰v‰n‰ tai valmiina maksatukseen
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$jaettavalasku'
            AND tila    in ('H', 'M', 'Q', 'Y')";
  $jaettavalaskures = pupe_query($query);

  if (mysql_num_rows($jaettavalaskures) != 1) {
    echo "<font class='error'>", t("Laskua ei voida jakaa, koska se on v‰‰r‰ss‰ tilassa"), "!<br/>";
    return FALSE;
  }

  $jaettavalaskurow = mysql_fetch_assoc($jaettavalaskures);

  if (($jaettavalaskurow['summa'] >= 0 and $vahennettavasumma_valuutassa <= 0) or ($jaettavalaskurow['summa'] <= 0 and $vahennettavasumma_valuutassa >= 0) or (abs($vahennettavasumma_valuutassa) >= abs($jaettavalaskurow['summa']))) {
    echo "<font class='error'>", t("Syˆtetty summa on virheellinen"), "! $vahennettavasumma_valuutassa<br/>";
    return false;
  }

  // Lasketaan kerroin annetusta summasta ja alkuper‰isest‰ summasta
  $kerroin = $vahennettavasumma_valuutassa / $jaettavalaskurow['summa'];

  // Katsotaan voidaanko splitata vai meneekˆ tiliˆinnit nollaan
  $query = "SELECT round(summa * $kerroin, 2) summa
            FROM tiliointi
            WHERE yhtio   = '$kukarow[yhtio]'
            AND ltunnus   = '$jaettavalaskurow[tunnus]'
            AND korjattu  = ''
            and tilino   != '$yhtiorow[pyoristys]'
            HAVING summa = 0";
  $tiliointires = pupe_query($query);

  if (mysql_num_rows($tiliointires) > 0) {
    echo "<font class='error'>", t("Laskua ei voida jakaa n‰in pieneen osaan"), "!<br/>";
    return false;
  }

  // Haetaan keikat, joihin lasku on liitetty
  $query = "SELECT laskunro, summa
            FROM lasku USE INDEX (tila_index)
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.liitostunnus = '$jaettavalaskurow[liitostunnus]'
            and lasku.tila         = 'K'
            and lasku.alatila      in ('', 'S')
            and lasku.vanhatunnus  = '$jaettavalaskurow[tunnus]'";
  $keikkares = pupe_query($query);

  $keikat   = array();
  $keikatok = TRUE;

  while ($keikkarow = mysql_fetch_assoc($keikkares)) {

    // katsotaan onko j‰lkilaskettu
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio     = '$kukarow[yhtio]'
              AND laskunro    = '$keikkarow[laskunro]'
              AND vanhatunnus = 0
              AND tila        = 'K'
              AND alatila     = 'X'
              AND kohdistettu = 'X'";
    $check_res = pupe_query($query);

    // katsotaan onko alkuper‰inen lasku liitetty kokonaan saapumiseen, jos on voidaan liitt‰‰ molemmat laskut uudestaan
    if ($jaettavalaskurow['summa'] == $keikkarow['summa']) {
      $keikat[] = $keikkarow['laskunro'];

      echo "<font class='error'>".t("Liitet‰‰n molemmat laskut uudestaan saapumiseen")." $keikkarow[laskunro]</font><br>";
    }
    else {
      if (mysql_num_rows($check_res) > 0) {
        echo "<font class='error'>".t("Laskua ei voida jakaa, koska se on liitetty vain osittain saapumiseen %s ja keikka on j‰lkilaskettu", "", $keikkarow["laskunro"])."!</font><br>";
        $keikatok = FALSE;
      }
      else {
        echo "<font class='error'>".t("Lasku oli liitetty vain osittain saapumiseen %s, joten uusia laskuja ei voitu liitt‰‰. K‰y korjaamassa keikan laskut manuaalisesti", "", $keikkarow["laskunro"]).".</font><br>";
      }
    }
  }

  if (!$keikatok) {
    return FALSE;
  }

  // Tehd‰‰n uusi lasku
  for ($i=0; $i < mysql_num_fields($jaettavalaskures)-1; $i++) {

    $fields .= mysql_field_name($jaettavalaskures, $i).",";

    switch (mysql_field_name($jaettavalaskures, $i)) {
    case 'summa':
      $values .= "'$vahennettavasumma_valuutassa',";
      break;
    case 'kasumma':
      $kasumma_uusi = $jaettavalaskurow["kasumma"] * $kerroin;
      $values .= "'$kasumma_uusi',";
      break;
    case "kate_korjattu":
    case "lahetetty_ulkoiseen_varastoon":
      $values .= "NULL,";
      break;
    default:
      $values .= "'".$jaettavalaskurow[mysql_field_name($jaettavalaskures, $i)]."',";
    }
  }

  // Vikat pilkut pois
  $fields = substr($fields, 0, -1);
  $values = substr($values, 0, -1);

  // Lis‰t‰‰n uusi lasku
  $query = "INSERT into lasku ($fields) VALUES ($values)";
  $result = pupe_query($query);
  $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

  // Lasketaan vanhalle laskulle uusi kasumma
  $kasumma_vanha = $jaettavalaskurow["kasumma"] - $kasumma_uusi;

  if ($jaettavalaskurow["vanhatunnus"] == 0) {
    $alkuperainen_summa = $jaettavalaskurow["summa"];
    $alkuperainen_tunnus = $jaettavalaskurow["tunnus"];
  }
  else {
    $alkuperainen_summa = $jaettavalaskurow["arvo"];
    $alkuperainen_tunnus = $jaettavalaskurow["vanhatunnus"];
  }

  // P‰ivitet‰‰n vanha lasku
  $query  = "UPDATE lasku SET
             summa       = $jaettavalaskurow[summa] - $vahennettavasumma_valuutassa,
             kasumma     = $kasumma_vanha,
             arvo        = $alkuperainen_summa,
             vanhatunnus = $alkuperainen_tunnus
             WHERE yhtio = '$kukarow[yhtio]'
             AND tunnus  = '$jaettavalaskurow[tunnus]'";
  $result = pupe_query($query);

  // P‰ivitet‰‰n uusi lasku
  $query  = "UPDATE lasku SET
             arvo        = $alkuperainen_summa,
             vanhatunnus = $alkuperainen_tunnus
             WHERE yhtio = '$kukarow[yhtio]'
             AND tunnus  = '$utunnus'";
  $result = pupe_query($query);

  // Lis‰t‰‰n tiliˆinnit
  $selite = t('Ostolaskun osasuoritus');

  $aputunnukset_vanha = array();
  $aputunnukset_uusi  = array();

  $query = "SELECT *
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            AND ltunnus  = '$jaettavalaskurow[tunnus]'
            AND korjattu = ''
            ORDER BY aputunnus DESC";
  $tiliointires = pupe_query($query);

  while ($tiliointirow = mysql_fetch_assoc($tiliointires)) {

    $uusi_summa = (float) round($tiliointirow["summa"] * $kerroin, 2);
    $uusi_summa_valuutassa = (float) round($tiliointirow["summa_valuutassa"] * $kerroin, 2);

    $vanha_summa = (float) $tiliointirow["summa"] - $uusi_summa;
    $vanha_summa_valuutassa = (float) $tiliointirow["summa_valuutassa"] - $uusi_summa_valuutassa;

    // ei kopioida pyˆristystili‰
    if ($tiliointirow['tilino'] != $yhtiorow['pyoristys']) {

      // tehd‰‰n uudet tiliˆnnit
      $query = "INSERT INTO tiliointi SET
                yhtio            = '$tiliointirow[yhtio]',
                ltunnus          = '$jaettavalaskurow[tunnus]',
                tilino           = '$tiliointirow[tilino]',
                kustp            = '$tiliointirow[kustp]',
                kohde            = '$tiliointirow[kohde]',
                projekti         = '$tiliointirow[projekti]',
                vero             = '$tiliointirow[vero]',
                tapvm            = '$tiliointirow[tapvm]',
                summa            = '$vanha_summa',
                summa_valuutassa = '$vanha_summa_valuutassa',
                valkoodi         = '$tiliointirow[valkoodi]',
                selite           = '$tiliointirow[selite]',
                lukko            = '$tiliointirow[lukko]',
                korjattu         = '$tiliointirow[korjattu]',
                korjausaika      = '$tiliointirow[korjausaika]',
                tosite           = '$tiliointirow[tosite]',
                aputunnus        = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $result = pupe_query($query);
      $uusi_id = mysql_insert_id($GLOBALS["masterlink"]);

      // jos aputunnus ei ole tyhj‰, otetaan se avaimeksi taulukkoon ja laitetaan uuden aputunnusrivin id sen alle
      if ($tiliointirow['aputunnus'] > 0) {
        $aputunnukset_vanha[$tiliointirow['aputunnus']] = $uusi_id;
      }

      // jos tiliˆintirivi lˆytyy avaimena taulukosta
      if (isset($aputunnukset_vanha[$tiliointirow['tunnus']])) {
        $query = "UPDATE tiliointi SET
                  aputunnus    = $uusi_id
                  WHERE tunnus = {$aputunnukset_vanha[$tiliointirow['tunnus']]}";
        $result = pupe_query($query);
      }

      $query = "INSERT INTO tiliointi SET
                yhtio            = '$tiliointirow[yhtio]',
                ltunnus          = '$utunnus',
                tilino           = '$tiliointirow[tilino]',
                kustp            = '$tiliointirow[kustp]',
                kohde            = '$tiliointirow[kohde]',
                projekti         = '$tiliointirow[projekti]',
                vero             = '$tiliointirow[vero]',
                tapvm            = '$tiliointirow[tapvm]',
                summa            = '$uusi_summa',
                summa_valuutassa = '$uusi_summa_valuutassa',
                valkoodi         = '$tiliointirow[valkoodi]',
                selite           = '$tiliointirow[selite]',
                lukko            = '$tiliointirow[lukko]',
                korjattu         = '$tiliointirow[korjattu]',
                korjausaika      = '$tiliointirow[korjausaika]',
                tosite           = '$tiliointirow[tosite]',
                aputunnus        = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $result = pupe_query($query);
      $uusi_id = mysql_insert_id($GLOBALS["masterlink"]);

      if ($tiliointirow['aputunnus'] != '') {
        $aputunnukset_uusi[$tiliointirow['aputunnus']] = $uusi_id;
      }

      // laitetaan aputunnus pointtaaman takaisin oikeeseen tiliˆintiin
      if (isset($aputunnukset_uusi[$tiliointirow['tunnus']])) {
        $query = "UPDATE tiliointi SET
                  aputunnus    = $uusi_id
                  WHERE tunnus = {$aputunnukset_uusi[$tiliointirow['tunnus']]}";
        $result = pupe_query($query);
      }
    }

    // yliviivataan vanhat tiliˆinnit
    $query = "UPDATE tiliointi SET
              korjattu    = '$kukarow[kuka]',
              korjausaika = now()
              WHERE yhtio = '$tiliointirow[yhtio]'
              AND tunnus  = '$tiliointirow[tunnus]'";
    $result = pupe_query($query);
  }

  // tarkistetaan onko pyˆristyseroja
  $query = "SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            AND ltunnus  = '$jaettavalaskurow[tunnus]'
            AND korjattu = ''";
  $result = pupe_query($query);
  $check1 = mysql_fetch_assoc($result);

  $query = "SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            AND ltunnus  = '$utunnus'
            AND korjattu = ''";
  $result = pupe_query($query);
  $check2 = mysql_fetch_assoc($result);

  if ($check1['summa'] != 0) {
    $query = "INSERT into tiliointi set
              yhtio            = '$kukarow[yhtio]',
              ltunnus          = '$jaettavalaskurow[tunnus]',
              tilino           = '$yhtiorow[pyoristys]',
              kustp            = 0,
              kohde            = 0,
              projekti         = 0,
              tapvm            = '$jaettavalaskurow[tapvm]',
              summa            = -1 * $check1[summa],
              summa_valuutassa = -1 * $check1[summa_valuutassa],
              valkoodi         = '$jaettavalaskurow[valkoodi]',
              vero             = 0,
              selite           = '".t("Pyˆristysero")."',
              lukko            = '',
              laatija          = '$kukarow[kuka]',
              laadittu         = now()";
    $laskutusres = pupe_query($query);
  }

  if ($check2['summa'] != 0) {
    $query = "INSERT into tiliointi set
              yhtio            = '$kukarow[yhtio]',
              ltunnus          = '$utunnus',
              tilino           = '$yhtiorow[pyoristys]',
              kustp            = 0,
              kohde            = 0,
              projekti         = 0,
              tapvm            = '$jaettavalaskurow[tapvm]',
              summa            = -1 * $check2[summa],
              summa_valuutassa = -1 * $check2[summa_valuutassa],
              valkoodi         = '$jaettavalaskurow[valkoodi]',
              vero             = 0,
              selite           = '".t("Pyˆristysero")."',
              lukko            = '',
              laatija          = '$kukarow[kuka]',
              laadittu         = now()";
    $laskutusres = pupe_query($query);
  }

  if (count($keikat) > 0) {
    // dellataan vanhat liitosotsikot
    $query = "UPDATE lasku SET
              tila                   = 'D'
              WHERE lasku.yhtio      = '$kukarow[yhtio]'
              and lasku.liitostunnus = '$jaettavalaskurow[liitostunnus]'
              and lasku.tila         = 'K'
              and lasku.alatila      in ('', 'S')
              and lasku.vanhatunnus  = '$jaettavalaskurow[tunnus]'";
    $keikkares = pupe_query($query);

    foreach ($keikat as $keikka) {
      $query = "SELECT *
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tila        = 'K'
                AND vanhatunnus = 0
                AND laskunro    = '$keikka'";
      $laskures = pupe_query($query);
      $laskurow = mysql_fetch_assoc($laskures);

      $tee_kululaskut = 'liita';
      $otunnus     = $laskurow['tunnus'];
      $silent     = 'jee';
      $keikanalatila   = $laskurow['alatila'];

      $laskutunnus = $jaettavalaskurow['tunnus'];
      require 'tilauskasittely/kululaskut.inc';

      $laskutunnus = $utunnus;
      require 'tilauskasittely/kululaskut.inc';
    }
  }

  // T‰ss‰ kopioidaan laskun liitetiedostot uudelle laskulle
  $query = "INSERT INTO liitetiedostot
            (yhtio, data, filename, filesize, filetype, image_bits, image_channels, image_height, image_width, jarjestys, kayttotarkoitus, kieli, laatija, liitos, liitostunnus, luontiaika, muutospvm, muuttaja, selite)
            SELECT yhtio, data, filename, filesize, filetype, image_bits, image_channels, image_height, image_width, jarjestys, kayttotarkoitus, kieli, laatija, liitos, '$utunnus', luontiaika, muutospvm, muuttaja, selite
            FROM liitetiedostot
            WHERE yhtio      = '$kukarow[yhtio]'
            AND liitostunnus = '$jaettavalaskurow[tunnus]'
            AND liitos       = 'lasku'";
  $insert_res = pupe_query($query);

  return TRUE;

}
