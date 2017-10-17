<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Uuden yrityksen ohjattu perustaminen").":</font><hr>";

$error = 0;

if ($tila == 'parametrit') {
  // Tee yritys täällä
  if ($yhtio == '') {
    echo "<font class='error'>".t("Yritykselle on annettava tunnus")."</font><br>";
    $error = 1;
  }

  if ($nimi == '') {
    echo "<font class='error'>".t("Yritykselle on annettava nimi")."</font><br>";
    $error = 1;
  }

  if ($valuutta == '') {
    echo "<font class='error'>".t("Valuutta on annettava")."</font><br>";
    $error = 1;
  }

  $query = "SELECT nimi
            from yhtio
            where yhtio = '$yhtio'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $uusiyhtiorow = mysql_fetch_array($result);

    echo "<font class='error'>".t("Tunnus $yhtio on jo käytössä (".$uusiyhtiorow['nimi'].")")."</font><br>";
    $error = 1;
  }

  if ($error == 0) {
    // Tehdään yhtiö
    $query = "INSERT into yhtio
              SET yhtio  = '$yhtio',
              nimi       = '$nimi',
              laatija    = '$kukarow[kuka]',
              luontiaika = now()";
    $result = pupe_query($query);

    // Tehdään parametrit
    $query = "INSERT into yhtion_parametrit
              SET yhtio  = '$yhtio',
              laatija    = '$kukarow[kuka]',
              luontiaika = now()";
    $result = pupe_query($query);

    // Tehdään haluttu valuutta
    $query = "INSERT into valuu
              SET yhtio  = '$yhtio',
              nimi       = '$valuutta',
              kurssi     = 1,
              jarjestys  = 1,
              laatija    = '$kukarow[kuka]',
              luontiaika = now()";
    $result = pupe_query($query);
  }
  else {
    unset($tila);
  }
}

if ($tila == 'ulkonako') {
  if ($fromyhtio != '') {
    $query = "SELECT * from yhtio where yhtio='$fromyhtio'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
      $error = 1;
    }

    $query = "SELECT tunnus
              FROM yhtio
              WHERE yhtio = '$yhtio'";
    $yht_res = pupe_query($query);
    $yht_row = mysql_fetch_assoc($yht_res);

    if ($error == 0) {
      $row = mysql_fetch_assoc($result);

      $query = "  UPDATE yhtio SET ";

      $alakopsaa = array(  "tunnus",
        "yhtio",
        "konserni",
        "nimi",
        "laatija",
        "luontiaika",
        "muuttaja",
        "muutospvm");

      foreach ($row as $ind => $val) {

        if (!in_array($ind, $alakopsaa)) {
          $query .= "$ind = '".mysql_real_escape_string($val)."',";
        }
      }

      $query = substr($query, 0, -1);

      $query .= "  WHERE tunnus = '$yht_row[tunnus]'
            AND yhtio = '$yhtio'";
      $result = pupe_query($query);
    }

    $query = "SELECT * from yhtion_parametrit where yhtio='$fromyhtio'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
      $error = 1;
    }

    $query = "SELECT tunnus
              FROM yhtion_parametrit
              WHERE yhtio = '$yhtio'";
    $yht_res = pupe_query($query);
    $yht_row = mysql_fetch_assoc($yht_res);

    if ($error == 0) {
      $row = mysql_fetch_assoc($result);
      $query = "  UPDATE yhtion_parametrit SET ";

      $alakopsaa = array("tunnus",
        "yhtio",
        "finvoice_senderpartyid",
        "finvoice_senderintermediator",
        "verkkotunnus_vas",
        "verkkotunnus_lah",
        "verkkosala_vas",
        "verkkosala_lah",
        "lasku_tulostin",
        "logo",
        "lasku_logo",
        "lasku_logo_positio",
        "lasku_logo_koko",
        "laatija",
        "luontiaika",
        "muutospvm",
        "muuttaja",
        "css",
        "css_classic",
        "css_extranet",
        "css_verkkokauppa");

      foreach ($row as $ind => $val) {
        if (!in_array($ind, $alakopsaa)) {
          $query .= "$ind = '".mysql_real_escape_string($val)."',";
        }
      }

      $query = substr($query, 0, -1);

      $query .= " WHERE tunnus = '$yht_row[tunnus]'
                  AND yhtio = '$yhtio'";
      $result = pupe_query($query);
    }
  }
}

if ($tila == 'perusta') {
  if ($fromyhtio != '') {
    $query = "SELECT css,
              css_classic,
              css_extranet,
              css_verkkokauppa
              from yhtion_parametrit
              where yhtio = '$fromyhtio'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
      $error = 1;
    }

    if ($error == 0) {
      $uusiyhtiorow = mysql_fetch_array($result);

      $query = "SELECT tunnus
                FROM yhtion_parametrit
                WHERE yhtio = '$yhtio'";
      $yht_res = pupe_query($query);
      $yht_row = mysql_fetch_assoc($yht_res);

      $query = "UPDATE yhtion_parametrit SET
                css              = '$uusiyhtiorow[css]',
                css_classic      = '$uusiyhtiorow[css_classic]',
                css_extranet     = '$uusiyhtiorow[css_extranet]',
                css_verkkokauppa = '$uusiyhtiorow[css_verkkokauppa]'
                WHERE tunnus     = '$yht_row[tunnus]'
                AND yhtio        = '$yhtio'";
      $result = pupe_query($query);
    }
  }
}

if ($tila == 'menut') {
  if ($fromyhtio != '') {
    $query = "INSERT into oikeu (sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,yhtio,laatija,luontiaika,muutospvm,muuttaja)
              SELECT sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,'$yhtio','{$kukarow['kuka']}',now(),now(),'{$kukarow['kuka']}'
              FROM oikeu
              WHERE yhtio  = '$fromyhtio'
              and profiili = ''
              and kuka     = ''";
    $result = pupe_query($query);
  }
}

if ($tila == 'profiilit') {
  if (is_array($profiilit)) {
    foreach ($profiilit as $prof) {
      $query = "SELECT *
                FROM oikeu
                WHERE yhtio  = '$fromyhtio'
                and kuka     = '$prof'
                and profiili = '$prof'";
      $pres = pupe_query($query);

      while ($trow = mysql_fetch_array($pres)) {
        $query = "INSERT into oikeu
                  SET
                  kuka       = '$trow[kuka]',
                  sovellus   = '$trow[sovellus]',
                  nimi       = '$trow[nimi]',
                  alanimi    = '$trow[alanimi]',
                  paivitys   = '$trow[paivitys]',
                  nimitys    = '$trow[nimitys]',
                  jarjestys  = '$trow[jarjestys]',
                  jarjestys2 = '$trow[jarjestys2]',
                  profiili   = '$trow[profiili]',
                  yhtio      = '$yhtio',
                  hidden     = '$trow[hidden]',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now(),
                  muutospvm  = now(),
                  muuttaja   = '{$kukarow['kuka']}'";
        $rresult = pupe_query($query);
      }
    }
  }
}

if ($tila == 'kayttaja') {

  //Tehdään käyttäjä
  $profile = '';
  if (is_array($profiilit)) {
    if (count($profiilit) > 0) {
      foreach ($profiilit as $prof) {
        $profile .= $prof.",";
      }
      $profile = substr($profile, 0, -1);
    }
  }

  $query = "SELECT salasana, nimi FROM kuka WHERE kuka='$kuka' limit 1";
  $pres = pupe_query($query);

  if (mysql_num_rows($pres) > 0) {
    $krow = mysql_fetch_array($pres);
    $salasana = $krow['salasana'];
    $nimi = $krow['nimi'];
    echo "<font class='message'>$krow[nimi] ".t("Käyttäjä löytyi muistakin yrityksistä. Tietoja kopioitiin!"), "<br></font>";
  }
  else $salasana = md5($salasana);

  $query = "INSERT into kuka SET
            yhtio     = '$yhtio',
            nimi      = '$nimi',
            salasana  = '$salasana',
            kuka      = '$kuka',
            profiilit = '$profile'";
  $result = pupe_query($query);
  $kuka_id = mysql_insert_id($GLOBALS["masterlink"]);

  //Insertoidaan ainakin oikeudet käyttäjähallintaan
  $query = "INSERT into oikeu
            SET
            kuka       = '$kuka',
            user_id    = '{$kuka_id}',
            sovellus   = 'Käyttäjät ja valikot',
            nimi       = 'suoja.php',
            alanimi    = '',
            paivitys   = '1',
            nimitys    = 'Käyttöoikeudet',
            jarjestys  = '30',
            jarjestys2 = '',
            lukittu    = '1',
            yhtio      = '$yhtio',
            hidden     = ''";
  $rresult = pupe_query($query);

  // Oikeudet
  if (is_array($profiilit)) {
    foreach ($profiilit as $prof) {

      $query = "SELECT *
                FROM oikeu
                WHERE yhtio='$yhtio' and kuka='$prof' and profiili='$prof'";
      $pres = pupe_query($query);

      while ($trow = mysql_fetch_array($pres)) {
        //joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
        //voi olla jossain toisessa profiilissa
        //jos oikeus on jo jostaan toisesta profiilista katotaan oisko eroja päivitysoikeus kohassa
        $query = "SELECT yhtio
                  FROM oikeu
                  WHERE kuka   = '$kuka'
                  AND sovellus = '{$trow['sovellus']}'
                  AND nimi     = '{$trow['nimi']}'
                  AND alanimi  = '{$trow['alanimi']}'
                  AND yhtio    = '$yhtio'";
        $tarkesult = pupe_query($query);
        $tarkesultrow = mysql_fetch_assoc($tarkesult);

        if (mysql_num_rows($tarkesult) == 0) {
          $query = "INSERT into oikeu
                    SET
                    kuka       = '$kuka',
                    user_id    = '{$kuka_id}',
                    sovellus   = '{$trow['sovellus']}',
                    nimi       = '{$trow['nimi']}',
                    alanimi    = '{$trow['alanimi']}',
                    paivitys   = '{$trow['paivitys']}',
                    nimitys    = '{$trow['nimitys']}',
                    jarjestys  = '{$trow['jarjestys']}',
                    jarjestys2 = '{$trow['jarjestys2']}',
                    yhtio      = '$yhtio',
                    hidden     = '{$trow['hidden']}',
                    laatija    = '{$kukarow['kuka']}',
                    luontiaika = now(),
                    muutospvm  = now(),
                    muuttaja   = '{$kukarow['kuka']}'";
          $rresult = pupe_query($query);
        }
        elseif ($trow["paivitys"] == '1' and $tarkesultrow["paivitys"] != '1') {
          $query = "UPDATE oikeu
                    SET
                    paivitys       = '1',
                    muutospvm      = now(),
                    muuttaja       = '{$kukarow['kuka']}'
                    WHERE kuka     = '$kuka'
                    AND sovellus   = '{$trow['sovellus']}'
                    AND nimi       = '{$trow['nimi']}'
                    AND alanimi    = '{$trow['alanimi']}'
                    AND nimitys    = '{$trow['nimitys']}'
                    AND jarjestys  = '{$trow['jarjestys']}'
                    AND jarjestys2 = '{$trow['jarjestys2']}'
                    AND hidden     = '{$trow['hidden']}'
                    AND yhtio      = '$yhtio'";
          $rresult = pupe_query($query);
        }
      }
    }
  }
}

if ($tila == 'tili') {
  if ($fromyhtio != '') {
    $query = "SELECT * FROM tili where yhtio='$fromyhtio'";
    $kukar = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {
      $query = "INSERT into tili (nimi, sisainen_taso, tilino, ulkoinen_taso, alv_taso, kustp, kohde, projekti, toimijaliitos, yhtio)
                values ('$row[nimi]','$row[sisainen_taso]','$row[tilino]','$row[ulkoinen_taso]', '$row[alv_taso]', '$row[kustp]','$row[kohde]','$row[projekti]','$row[toimijaliitos]','$yhtio')";
      $upres = pupe_query($query);
    }

    $query = "SELECT * FROM taso where yhtio='$fromyhtio'";
    $kukar = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {
      $query = "INSERT into taso (tyyppi, summattava_taso, taso, nimi, yhtio)
                values ('$row[tyyppi]','$row[summattava_taso]','$row[taso]','$row[nimi]','$yhtio')";
      $upres = pupe_query($query);
    }
  }
}

if ($tila == 'avainsana') {
  if (is_array($avainsanat) and $eimitaan=='') {
    foreach ($avainsanat as $avain) {
      $query = "SELECT *
                FROM avainsana
                WHERE yhtio = '$fromyhtio' and laji = '$avain'";
      $pres = pupe_query($query);
      while ($trow = mysql_fetch_array($pres)) {
        $query = "INSERT into avainsana
                  SET
                  jarjestys    = '$trow[jarjestys]',
                  laji         = '$trow[laji]',
                  laatija      = '$kukarow[kuka]',
                  luontiaika   =  now(),
                  selite       = '$trow[selite]',
                  selitetark   = '$trow[selitetark]',
                  selitetark_2 = '$trow[selitetark_2]',
                  selitetark_3 = '$trow[selitetark_3]',
                  kieli        = '$trow[kieli]',
                  nakyvyys     = '$trow[nakyvyys]',
                  yhtio        = '$yhtio'";
        $rresult = pupe_query($query);
      }
    }
  }
}

if ($tila == 'kirjoitin') {
  if ($fromyhtio != '') {
    $query = "SELECT * FROM kirjoittimet where yhtio='$fromyhtio'";
    $kukar = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {
      $query = "INSERT INTO kirjoittimet SET
                yhtio         = '$yhtio',
                kirjoitin     = '$row[kirjoitin]',
                komento       = '$row[komento]',
                merkisto      = '$row[merkisto]',
                nimi          = '$row[nimi]',
                osoite        = '$row[osoite]',
                postino       = '$row[postino]',
                postitp       = '$row[postitp]',
                puhelin       = '$row[puhelin]',
                yhteyshenkilo = '$row[yhteyshenkilo]',
                ip            = '$row[ip]',
                laatija       = '$kukarow[kuka]',
                luontiaika    = now()";
      $upres = pupe_query($query);
    }
  }
}

if ($tila == 'maksuehto') {
  if ($fromyhtio != '') {
    $query = "SELECT * FROM maksuehto where yhtio='$fromyhtio'";
    $kukar = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {

      if (is_null($row['abs_pvm'])) {
        $abs_pvm = "abs_pvm = null,";
      }
      else {
        $abs_pvm = "abs_pvm = '{$row['abs_pvm']}',";
      }

      if (is_null($row['kassa_abspvm'])) {
        $kassa_abspvm = "kassa_abspvm = null,";
      }
      else {
        $kassa_abspvm = "kassa_abspvm = '{$row['kassa_abspvm']}',";
      }

      $query = "INSERT INTO maksuehto SET
                yhtio              = '$yhtio',
                teksti             = '$row[teksti]',
                rel_pvm            = '$row[rel_pvm]',
                {$abs_pvm}
                kassa_relpvm       = '$row[kassa_relpvm]',
                {$kassa_abspvm}
                kassa_alepros      = '$row[kassa_alepros]',
                jv                 = '$row[jv]',
                kateinen           = '$row[kateinen]',
                factoring_id       = '$row[factoring_id]',
                directdebit_id     = '$row[directdebit_id]',
                pankkiyhteystiedot = '$row[pankkiyhteystiedot]',
                itsetulostus       = '$row[itsetulostus]',
                jaksotettu         = '$row[jaksotettu]',
                erapvmkasin        = '$row[erapvmkasin]',
                sallitut_maat      = '$row[sallitut_maat]',
                kaytossa           = '$row[kaytossa]',
                jarjestys          = '$row[jarjestys]',
                laatija            = '$kukarow[kuka]',
                luontiaika         = now()";
      $upres = pupe_query($query);
    }
  }
}

if ($tila == 'toimitustapa') {
  if ($fromyhtio != '') {
    $query = "SELECT * FROM toimitustapa where yhtio='$fromyhtio'";
    $kukar = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {
      $query = "INSERT INTO toimitustapa SET
                yhtio                            = '$yhtio',
                selite                           = '$row[selite]',
                tulostustapa                     = '$row[tulostustapa]',
                rahtikirja                       = '$row[rahtikirja]',
                osoitelappu                      = '$row[osoitelappu]',
                rahdinkuljettaja                 = '$row[rahdinkuljettaja]',
                sopimusnro                       = '$row[sopimusnro]',
                jvkulu                           = '$row[jvkulu]',
                jvkielto                         = '$row[jvkielto]',
                vak_kielto                       = '$row[vak_kielto]',
                nouto                            = '$row[nouto]',
                lauantai                         = '$row[lauantai]',
                merahti                          = '$row[merahti]',
                extranet                         = '$row[extranet]',
                ei_pakkaamoa                     = '$row[ei_pakkaamoa]',
                kuluprosentti                    = '$row[kuluprosentti]',
                toim_nimi                        = '$row[toim_nimi]',
                toim_nimitark                    = '$row[toim_nimitark]',
                toim_osoite                      = '$row[toim_osoite]',
                toim_postino                     = '$row[toim_postino]',
                toim_postitp                     = '$row[toim_postitp]',
                toim_maa                         = '$row[toim_maa]',
                maa_maara                        = '$row[maa_maara]',
                sisamaan_kuljetus                = '$row[sisamaan_kuljetus]',
                sisamaan_kuljetus_kansallisuus   = '$row[sisamaan_kuljetus_kansallisuus]',
                sisamaan_kuljetusmuoto           = '$row[sisamaan_kuljetusmuoto]',
                kontti                           = '$row[kontti]',
                aktiivinen_kuljetus              = '$row[aktiivinen_kuljetus]',
                aktiivinen_kuljetus_kansallisuus = '$row[aktiivinen_kuljetus_kansallisuus]',
                kauppatapahtuman_luonne          = '$row[kauppatapahtuman_luonne]',
                kuljetusmuoto                    = '$row[kuljetusmuoto]',
                poistumistoimipaikka_koodi       = '$row[poistumistoimipaikka_koodi]',
                ulkomaanlisa                     = '$row[ulkomaanlisa]',
                sallitut_maat                    = '$row[sallitut_maat]',
                virallinen_selite                = '$row[virallinen_selite]',
                jarjestys                        = '$row[jarjestys]',
                laatija                          = '$kukarow[kuka]',
                luontiaika                       = now()";
      $upres = pupe_query($query);
    }
  }
}

if ($tila == 'varasto') {
  if ($varasto != '') {
    $varasto = mysql_real_escape_string($varasto);

    $query = "SELECT * FROM kirjoittimet where yhtio = '$yhtio' LIMIT 1";
    $kirjoitin_res = pupe_query($query);
    $kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

    $query = "INSERT INTO varastopaikat SET
              yhtio                            = '$yhtio',
              alkuhyllyalue                    = 'A00',
              alkuhyllynro                     = '00',
              loppuhyllyalue                   = 'A99',
              loppuhyllynro                    = '99',
              printteri0                       = '$kirjoitin_row[tunnus]',
              printteri1                       = '$kirjoitin_row[tunnus]',
              printteri2                       = '$kirjoitin_row[tunnus]',
              printteri3                       = '$kirjoitin_row[tunnus]',
              printteri4                       = '$kirjoitin_row[tunnus]',
              printteri5                       = '$kirjoitin_row[tunnus]',
              printteri6                       = '$kirjoitin_row[tunnus]',
              printteri7                       = '$kirjoitin_row[tunnus]',
              nimitys                          = '$varasto',
              tyyppi                           = '',
              nimi                             = '$varasto',
              nimitark                         = '',
              osoite                           = '',
              postino                          = '',
              postitp                          = '',
              maa                              = 'FI',
              maa_maara                        = '',
              sisamaan_kuljetus                = '',
              sisamaan_kuljetus_kansallisuus   = '',
              sisamaan_kuljetusmuoto           = 0,
              kontti                           = 0,
              aktiivinen_kuljetus              = '',
              aktiivinen_kuljetus_kansallisuus = '',
              kauppatapahtuman_luonne          = 0,
              kuljetusmuoto                    = 0,
              poistumistoimipaikka_koodi       = '',
              sallitut_maat                    = '',
              laatija                          = '$kukarow[kuka]',
              luontiaika                       = now()";
    $upres = pupe_query($query);
  }
  unset($tila);
  unset($yhtio);
  unset($nimi);
  unset($valuutta);
}

// Käyttöliittymä
if (isset($tila)) {
  $query = "SELECT nimi
            from yhtio
            where yhtio = '$yhtio'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Perustettava yritys on kadonnut")."!</font><br>";
    exit;
  }

  $uusiyhtiorow = mysql_fetch_array($result);

  echo "<br><table>
      <tr><th>".t("Yhtiö")."</th><th>".t("Nimi")."</th></tr>
      <tr><td>$yhtio</td><td>$uusiyhtiorow[nimi]</td></tr>
      </table><br><br>";
}

if ($tila == 'parametrit') {
  // yritysvalinta
  $query = "SELECT yhtio, nimi
            FROM yhtio
            WHERE yhtio != '$yhtio'";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='ulkonako'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan tiedot ja parametrit?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow = mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Valitse')."'></td></tr></table></form>";
}

if ($tila == 'ulkonako') {
  // yritysvalinta
  $query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='perusta'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan ulkonäkö?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow=mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Valitse')."'></td></tr></table></form>";
}

if ($tila == 'perusta') {
  // yritysvalinta
  $query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='menut'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan menut?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow=mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
}

if ($tila == 'menut') {
  // profiilit
  $query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$fromyhtio' and profiili != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<form method='post'>
        <input type='hidden' name = 'tila' value='profiilit'>
        <input type='hidden' name = 'yhtio' value='$yhtio'>
        <input type='hidden' name = 'fromyhtio' value='$fromyhtio'>
        <table>
        <tr><th>".t("Mitkä profiilit kopioidaan?").":</th><td></td></tr>";

    while ($profiilirow=mysql_fetch_array($result)) {
      echo "<tr><td>$profiilirow[profiili]</td><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]' checked></td></tr>";
    }

    echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
  }
  else {
    $tila = 'profiilit';
  }
}

if ($tila == 'profiilit') {
  // käyttäjät
  $query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$yhtio' and profiili != ''";
  $result = pupe_query($query);

  if (!isset($kuka)) {
    $kuka = $kukarow['kuka'];
    $nimi = $kukarow['nimi'];
  }

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='kayttaja'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <input type='hidden' name = 'fromyhtio' value='$fromyhtio'>
      <table>
      <tr><th>".t("Anna käyttäjätunnus").":</th><td><input type='text' name = 'kuka' value='$kuka'></td></tr>
      <tr><th>".t("Nimi").":</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
      <tr><th>".t("Salasana")."</th><td><input type='text' name = 'salasana' value='$salasana'></td></tr>
      <tr><th>".t("Profiilit")."</th><td></td></tr>";

  while ($profiilirow = mysql_fetch_array($result)) {
    echo "<th>$profiilirow[profiili]</th><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]'></td></tr>";
  }

  echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";

  echo "<br>HUOM: Uudelle käyttäjälle lisätään aina käyttöoikeudet uuden yrityksen käyttöoikeuksien hallintaan.";
}

if ($tila == 'kayttaja') {
  // tilit ja tasot
  $query = "SELECT distinct tili.yhtio, yhtio.nimi FROM tili, yhtio WHERE tili.yhtio=yhtio.yhtio";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='tili'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan tilikartta?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow = mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
}

if ($tila == 'tili') {
  // avainsanat
  $query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='avainsana'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan avainsanat?").":</th><td><select name='fromyhtio'>";

  while ($uusiyhtiorow=mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><td><INPUT type='checkbox' name='eimitaan' value='x'>".t("Avainsanoja ei kopioida")."</td><td></td</tr>";
  echo "<tr><td>".t("Mitkä avainsanatyypit kopioidaan")."</td><td></td></tr>";
  echo "  <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='Y'>".t("Yksikko")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRY'>".t("Tuoteryhmä")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='OSASTO'>".t("Tuoteosasto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TUOTEMERKKI'>".t("Tuotemerkki")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='S' >".t("Tuotteen status")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TUOTEULK'>".t("Tuotteiden avainsanojen laji")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='VARASTOLUOKKA'>".t("Varastoluokka")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='SARJANUMERON_LI'>".t("Sarjanumeron lisätieto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='PARAMETRI'>".t("Tuotteen parametri")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TARRATYYPPI'>".t("Tuotteen tarratyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASLUOKKA'>".t("Asiakasluokka")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASOSASTO'>".t("Asiakasosasto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASRYHMA'>".t("Asiakasryhma")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASTILA'>".t("Asiakastila")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='PIIRI'>".t("Asiakkaan piiri")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKT_EMAIL'>".t("Laskutustiedot autom. sähköpostitukseen")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKT_EMAIL_SOP'>".t("Laskutustiedot autom. sähköpostitukseen (Sopimus)")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASAVAINSANA'>".t("Asiakkaan avainsanojen laji")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='EXTASAVAINSANA'>".t("Extranet-asiakkaan avainsanojen laji")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TV'>".t("Tilausvahvistus")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='LAHETETYYPPI'>".t("Lähetetyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KALETAPA'>".t("CRM yhteydenottotapa")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='MYSQLALIAS'>".t("Tietokantasarakkeen nimialias")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMITUSTAPA_OS'>".t("Toimitustapa ostolle (kuljetus)")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='KUKAASEMA'>".t("Käytäjän asema")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ALVULK'>".t("Ulkomaan ALV%")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='ALV'>".t("ALV%")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='SEURANTA'>".t("Tilauksen seurantaluokka")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TOIMEHTO'>".t("Toimitusehto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='HENKILO_OSASTO'>".t("Henkilöosasto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='RAHTIKIRJA'>".t("Rahtikirjatyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KERAYSLISTA'>".t("Keräyslista")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMTAPAKV'>".t("Toimitustavan kieliversio")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='KVERITTELY'>".t("Kulunvalvonnan erittely")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOJONO'>".t("Työmääräysten työjono")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOSTATUS'>".t("Työmääräysten työstatus")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOLINJA'>".t("Työmääräysten työlinja")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KT'>".t("Kauppatapahtuman luonne")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TULLI'>".t("Poistumistoimipaikka")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KM'>".t("Kuljetusmuoto")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='C'>".t("CHN tietue")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKUKUVAUS'>".t("Maksuposition kuvaus")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KARHUVIESTI'>".t("Maksukehotusviesti")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='MAKSUEHTOKV'>".t("Maksuehdon kieliversio")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='CRM_ROOLI'>".t("Yhteyshenkilön rooli")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='CRM_SUORAMARKKI'>".t("Yhteyshenkilön suoramarkkinointitiedot")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='VAKIOVIESTI'>".t("Laskun vakioviesti")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='VAKIOVIESTI_TIL'>".t("Tilausvahvistuksen vakioviesti")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LITETY'>".t("Liitetiedostotyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TIL-LITETY'>".t("Tilauksen liitetiedostotyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='JAKELULISTA'>".t("Email jakelulista")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LUETTELO'>".t("Luettelotyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRIVITYYPPI'>".t("Tilausrivin tyyppi")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='LASKUTUS_SAATE'>".t("Laskun sähköpostisaatekirje asiakkaalle")."</td></tr>
      <tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TV_LISATIETO'>".t("Tilausvahvistuksen lisätiedot")."</td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
}

if ($tila == 'avainsana') {
  //tulostimet
  $query = "SELECT distinct kirjoittimet.yhtio, yhtio.nimi FROM kirjoittimet, yhtio WHERE kirjoittimet.yhtio=yhtio.yhtio";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='kirjoitin'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan kirjoittimet?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow=mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
}

if ($tila == 'kirjoitin') {
  //maksuehdot
  $query = "SELECT distinct maksuehto.yhtio, yhtio.nimi FROM maksuehto, yhtio WHERE maksuehto.yhtio=yhtio.yhtio";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='maksuehto'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan maksuehdot?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow=mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
}

if ($tila == 'maksuehto') {
  //toimitustavat
  $query = "SELECT distinct toimitustapa.yhtio, yhtio.nimi FROM toimitustapa, yhtio WHERE toimitustapa.yhtio=yhtio.yhtio";
  $result = pupe_query($query);

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='toimitustapa'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Miltä yritykseltä kopioidaan toimitustavat?").":</th><td><select name='fromyhtio'>
      <option value=''>".t("Ei kopioida")."</option>";

  while ($uusiyhtiorow = mysql_fetch_array($result)) {

    $selli = "";
    if ($fromyhtio == $uusiyhtiorow["yhtio"]) $selli = "SELECTED";

    echo "<option value='$uusiyhtiorow[yhtio]' $selli>$uusiyhtiorow[nimi]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
}

if ($tila == 'toimitustapa') {
  //varasto
  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='varasto'>
      <input type='hidden' name = 'yhtio' value='$yhtio'>
      <table>
      <tr><th>".t("Anna varaston nimi")."</th><td><input type='text' name = 'varasto' value='$varasto'></td></tr>
      <tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr>
      </table>
      </form>";
}

if (!isset($tila)) {
  if (!isset($valuutta)) $valuutta = 'EUR';

  echo "<form method='post'>
      <input type='hidden' name = 'tila' value='parametrit'>
      <table>
      <tr><th>".t("Anna uuden yrityksen tunnus")."</th><td><input type='text' name = 'yhtio' value='$yhtio' size='10' maxlength='5'></td></tr>
      <tr><th>".t("Anna uuden yrityksen nimi")."</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
      <tr><th>".t("Anna uuden yrityksen oletusvaluutta")."</th><td><input type='text' name = 'valuutta' value='$valuutta' maxlength='3'></td></tr>
      <tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr>
      </table>
      </form>";
}

require "inc/footer.inc";
