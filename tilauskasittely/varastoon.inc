<?php

// tarvitaan $laskurow array, jossa on meidän keikan otsikko
$debug = 0; // 0 = ei echoilla debug koodia, 1 = ehchoillaa infoa, 2 = echoillaan infoa plus queryt

# Muuttuja kun viedään tilausrivi kerrallaan.
$varastoon_tilausrivirajaus = "";
if (!isset($tullaan_automaattikohdistuksesta)) $tullaan_automaattikohdistuksesta = false;

if ($toiminto == "kalkyyli" and $tee == "" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--

      function toggleAll(toggleBox) {

        var currForm = toggleBox.form;
        var isChecked = toggleBox.checked;
        var nimi = toggleBox.name;

        for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
          if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
            currForm.elements[elementIdx].checked = isChecked;
          }
        }
      }

      function WriteText(sarake) {
        var count = document.riviformi.elements.length;
        var sarakenimi = sarake+'_kaikki';

        for (j=0; j<count; j++) {
          var element = document.riviformi.elements[j];

          if (element.name.substring(0,3) == sarake) {
            element.value = document.getElementById(sarakenimi).value;
          }
        }
      }

      //-->
      </script>";
}

$varastoon_msg        = "";
$jalkilaskenta_debug_text = "";

if ($tee == "" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  $varastoon_msg .= "<form name='riviformi' method='post'>";
  $varastoon_msg .= "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

  if ($toiminto == "kaikkiok") {
    $varastoon_msg .= "<input type='hidden' name='toiminto' value='kaikkiok'>";
  }
  else {
    $varastoon_msg .= "<input type='hidden' name='toiminto' value='kalkyyli'>";
  }

  $varastoon_msg .= "<input type='hidden' name='tee' value='eitullia'>";
  $varastoon_msg .= "<input type='hidden' name='otunnus' value='$otunnus'>";
}

if ($kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {

  $tv_kaytossa = tarkista_onko_taric_veroperusteet_kaytossa();

  $varastoon_msg .= "  <table><tr>
            <th valign='top'>".t("Varastoon")."</th>
            <th valign='top'>".t("Varastoonvientipäivä")."</th>";

  if ($toiminto == "kalkyyli" and ($tee == '' or $tee == 'varastoon') and $yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {
    $varastoon_msg .= "<th valign='top'>".t("Saapuminen")."</th>";
    $varastoon_msg .= "<th valign='top'>".t("Suuntalava")."</th>";
  }

  $varastoon_msg .= "  <th valign='top'>".t("Tuoteno")."</th>
            <th valign='top'>".t("Määrä")." ".t("sis").".<br>".t("Määrä")." ".t("ulk").".</th>";
  $varastoon_msg .= "  <th valign='top'>".t("Ostohinta")." ".t("ulk").".<br>".t("Ostohinta")." ".t("sis").".</th>
            <th valign='top'>".t("Alennukset")."</th>
            <th valign='top'>".t("Osuus")."<br>".t("rahdista")."<br>".t("ja")." ".t("kuluista")."</th>
            <th valign='top'>".t("Rahti")."<br>".t("ja")." ".t("kulut")."</th>";

  if ($tv_kaytossa) $varastoon_msg .= "<th valign='top'>".t("Tulli")."</th>";

  $varastoon_msg .= "<th valign='top'>".t("Ostohinta")."+<br>".t("Rahti")." ".t("ja")." ".t("kulut")."+<br>".t("Tulli")."</th>
            <th valign='top'>".t("Rivihinta")."</th>
            <th valign='top'>".t("Vanha")." ".t("kehahin")."<br>".t("Uusi")." ".t("kehahin")."</th>";

  if ($toiminto == "kalkyyli" and $tee == "") {
    $varastoon_msg .= "<th valign='top'>".t("Vanha")." ".t("saldo")."<br>".t("Uusi")." ".t("saldo")."</th>";

    if ($yhtiorow['toimipaikkakasittely'] == 'L' and $laskurow['yhtio_toimipaikka'] != 0) {
      $varastoon_msg .= "<th valign='top'>".t("Toimipaikan vanha saldo")."<br>".t("Toimipaikan uusi saldo")."</th>";
    }
  }

  $varastoon_msg .= "</tr>";
}

$ale_query_select_lisa = generoi_alekentta_select('yhteen', 'O');

if (($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) {
  // virallinen laskenta, heetaan ihan kaikki rivit uudestaan (varattu=0) kaikki pitää olla viety varastoon jo ennen tätä..
  $query = "SELECT tilausrivi.*, tilausrivi.kpl varattu, {$ale_query_select_lisa} alennuksetyht, tilausrivin_lisatiedot.poikkeava_tulliprosentti, tuote.tullinimike1, tuote.tullinimike2
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
            LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
            LEFT JOIN tilausrivin_lisatiedot ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN tuote ON ( tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno )
            WHERE tilausrivi.uusiotunnus  = '$laskurow[tunnus]'
            and tilausrivi.varattu        = 0
            and tilausrivi.kpl           != 0
            and tilausrivi.yhtio          = '$kukarow[yhtio]'
            and  tilausrivi.tyyppi        = 'O'
            ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.kpl DESC, tilausrivi.laskutettuaika";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon") {

  $uusiotunnuslisa = "tilausrivi.uusiotunnus = '{$laskurow['tunnus']}'";
  $suuntalavajoin = "";
  $runkotunnukset = "";

  # Jos tilausrivi ei ole nolla, viedään vain yksi rivi varastoon.
  if ($tilausrivi > 0) {
    $varastoon_tilausrivirajaus = "AND tilausrivi.tunnus = $tilausrivi";
  }

  if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus) and $suuntalavan_tunnus > 0) {

    $query = "SELECT GROUP_CONCAT(saapuminen) keikkatunnus
              FROM suuntalavat_saapuminen
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND suuntalava = '{$suuntalavan_tunnus}'";
    $uusiotunnus_chk_res = pupe_query($query);
    $uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

    $uusiotunnuslisa = "tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})";

    $suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava AND suuntalavat.tila = 'S' AND suuntalavat.tunnus = '{$suuntalavan_tunnus}')
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus) ";
  }

  // Satula-Runko case
  $query = "SELECT tuoteperhe.isatuoteno, tilausrivi.uusiotunnus,
            tuoteperhe.tuoteno,
            tuote.tuoteno, tuote.try,tuote.osasto,
            tuote.nimitys,tuote.yksikko,tuote.myyntihinta,
            tilausrivi.yhtio, tilausrivi.tyyppi,tilausrivi.toimaika, tilausrivi.kerayspvm,
            tilausrivi.otunnus,tilausrivi.perheid,tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.kpl,
            tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.hyllyvali, tilausrivi.toimitettuaika,
            tilausrivi.suuntalava
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
            JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuoteperhe.isatuoteno AND tuoteperhe.tyyppi in ('P','')  AND tuoteperhe.ohita_kerays != '')
            JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuoteperhe.tuoteno)
            WHERE {$uusiotunnuslisa}
            and tilausrivi.varattu   <> 0
            and tilausrivi.yhtio     = '{$kukarow['yhtio']}'
            and tilausrivi.tyyppi    = 'O'
            and tilausrivi.varastoon = 1
            $varastoon_tilausrivirajaus
            ORDER BY tilausrivi.perheid, tilausrivi.tuoteno, tilausrivi.varattu DESC, tilausrivi.laskutettuaika";
  $chk_res = pupe_query($query);

  if (mysql_num_rows($chk_res) > 0) {

    while ($chk_row = mysql_fetch_assoc($chk_res)) {

      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND perheid  = '{$chk_row['tunnus']}'
                AND tuoteno  = '{$chk_row['tuoteno']}'
                AND kpl      = '{$chk_row['kpl']}'
                AND varattu  = '{$chk_row['varattu']}'
                AND tyyppi   = 'O'
                AND tunnus  != '{$chk_row['tunnus']}'";
      $loytyiko_lapsi_res = pupe_query($query);

      if (mysql_num_rows($loytyiko_lapsi_res) == 0) {

        list($chk_hinta,,$chk_ale,) = alehinta_osto($laskurow, $chk_row, $chk_row["varattu"]);

        $lisainsert = "INSERT INTO tilausrivi SET
             yhtio       = '{$chk_row['yhtio']}',
             tyyppi      = '{$chk_row['tyyppi']}',
             toimaika    = '{$chk_row['toimaika']}',
             kerayspvm   = '{$chk_row['kerayspvm']}',
             otunnus     = '{$chk_row['otunnus']}',
             tuoteno     = '{$chk_row['tuoteno']}',
             try         = '{$chk_row['try']}',
             osasto      = '{$chk_row['osasto']}',
             nimitys     = '{$chk_row['nimitys']}',
             yksikko     = '{$chk_row['yksikko']}',
             tilkpl      = '{$chk_row['tilkpl']}',
             varattu     = '{$chk_row['varattu']}',
             kpl         = '{$chk_row['kpl']}',
             ale1        = '{$chk_ale['ale1']}',
             ale2        = '{$chk_ale['ale2']}',
             ale3        = '{$chk_ale['ale3']}',
             hinta       = '{$chk_hinta}',
             laatija     = 'tuloutus',
             kommentti   = '',
             laadittu    =  now(),
             hyllyalue   = '{$chk_row['hyllyalue']}',
             hyllynro    = '{$chk_row['hyllynro']}',
             hyllytaso   = '{$chk_row['hyllytaso']}',
             hyllyvali   = '{$chk_row['hyllyvali']}',
             perheid     = '{$chk_row['tunnus']}',
             suuntalava  = '{$chk_row['suuntalava']}',
             uusiotunnus = '{$chk_row['uusiotunnus']}'";
        $inskres = pupe_query($lisainsert);

        if (!empty($varastoon_tilausrivirajaus)) {
          $runkoinsert = mysql_insert_id();
          $runkotunnukset .= $runkoinsert . ",";
        }

        // päivitetään isä
        $updateisa = "UPDATE tilausrivi SET perheid = tunnus WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$chk_row['tunnus']}'";
        $updateres = pupe_query($updateisa);
      }
      else {

        $loytyiko_lapsi_row = mysql_fetch_assoc($loytyiko_lapsi_res);

        $query = "UPDATE tilausrivi SET
                  uusiotunnus = '{$chk_row['uusiotunnus']}',
                  suuntalava  = '{$chk_row['suuntalava']}',
                  hyllyalue   = '{$chk_row['hyllyalue']}',
                  hyllynro    = '{$chk_row['hyllynro']}',
                  hyllytaso   = '{$chk_row['hyllytaso']}',
                  hyllyvali   = '{$chk_row['hyllyvali']}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$loytyiko_lapsi_row['tunnus']}'";
        $updres = pupe_query($query);

        if (!empty($varastoon_tilausrivirajaus)) {
          $runkotunnukset .= $loytyiko_lapsi_row['tunnus'] . ",";
        }
      }

      $query = "SELECT *
                FROM tuotepaikat
                WHERE yhtio   = '{$kukarow['yhtio']}'
                AND tuoteno   = '{$chk_row['tuoteno']}'
                AND hyllyalue = '{$chk_row['hyllyalue']}'
                AND hyllynro  = '{$chk_row['hyllynro']}'
                AND hyllytaso = '{$chk_row['hyllytaso']}'
                AND hyllyvali = '{$chk_row['hyllyvali']}'
                LIMIT 1";
      $paikka_chk_res = pupe_query($query);

      if (mysql_num_rows($paikka_chk_res) == 0) {

        $oletuspaikka_lapsi = '';

        // tarkistetaan onko lapsituotteella oletuspaikkaa
        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND tuoteno  = '{$chk_row['tuoteno']}'
                  AND oletus  != ''
                  LIMIT 1";
        $paikka_chk_res2 = pupe_query($query);

        if (mysql_num_rows($paikka_chk_res2) == 0) {
          $oletuspaikka_lapsi = 'X';
        }

        // lisätään paikka
        $query = "INSERT INTO tuotepaikat SET
                  yhtio      = '{$kukarow['yhtio']}',
                  tuoteno    = '{$chk_row['tuoteno']}',
                  oletus     = '{$oletuspaikka_lapsi}',
                  hyllyalue  = '{$chk_row['hyllyalue']}',
                  hyllynro   = '{$chk_row['hyllynro']}',
                  hyllytaso  = '{$chk_row['hyllytaso']}',
                  hyllyvali  = '{$chk_row['hyllyvali']}',
                  tyyppi     = '',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now()";
        $result = pupe_query($query);

        // Varastoonvientipäivä
        if ($chk_row["toimitettuaika"] == "0000-00-00 00:00:00") {
          $varastoonvientipva = date("Y-m-d H:i:s");
        }
        else {
          $varastoonvientipva = $chk_row["toimitettuaika"];
        }

        // tehdään tapahtuma
        $query = "INSERT into tapahtuma set
                  yhtio     = '{$kukarow['yhtio']}',
                  tuoteno   = '{$chk_row['tuoteno']}',
                  kpl       = '0',
                  kplhinta  = '0',
                  hinta     = '0',
                  laji      = 'uusipaikka',
                  hyllyalue = '{$chk_row['hyllyalue']}',
                  hyllynro  = '{$chk_row['hyllynro']}',
                  hyllytaso = '{$chk_row['hyllytaso']}',
                  hyllyvali = '{$chk_row['hyllyvali']}',
                  selite    = '".t("Lisättiin tuotepaikka")." {$chk_row['hyllyalue']} {$chk_row['hyllynro']} {$chk_row['hyllyvali']} {$chk_row['hyllytaso']}',
                  laatija   = '{$kukarow['kuka']}',
                  laadittu  = date_sub('{$varastoonvientipva}', interval 1 SECOND)";
        $result = pupe_query($query);
      }

      // Katsotaan onko tämä tuotepaikka isätuotteen oletuspaikka
      $query = "SELECT *
                FROM tuotepaikat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND tuoteno    = '{$chk_row['isatuoteno']}'
                AND hyllyalue  = '{$chk_row['hyllyalue']}'
                AND hyllynro   = '{$chk_row['hyllynro']}'
                AND hyllytaso  = '{$chk_row['hyllytaso']}'
                AND hyllyvali  = '{$chk_row['hyllyvali']}'
                AND oletus    != ''
                LIMIT 1";
      $paikka_chk_res = pupe_query($query);

      if (mysql_num_rows($paikka_chk_res) == 1) {

        $query = "UPDATE tuotepaikat SET
                  oletus      = ''
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$chk_row['tuoteno']}'";
        $paikka_chk_res = pupe_query($query);

        $query = "UPDATE tuotepaikat SET
                  oletus        = 'X'
                  WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND tuoteno   = '{$chk_row['tuoteno']}'
                  AND hyllyalue = '{$chk_row['hyllyalue']}'
                  AND hyllynro  = '{$chk_row['hyllynro']}'
                  AND hyllytaso = '{$chk_row['hyllytaso']}'
                  AND hyllyvali = '{$chk_row['hyllyvali']}'";
        $paikka_chk_res = pupe_query($query);
      }
    }
  }

  if (!empty($varastoon_tilausrivirajaus) and $runkotunnukset != "") {
    $runkotunnukset = substr($runkotunnukset, 0, -1);
    $varastoon_tilausrivirajaus = "AND tilausrivi.tunnus in ($tilausrivi,$runkotunnukset)";
  }

  // tavallinen laskenta.. VARATTU PITÄÄ OLLA != 0 ja varastoon-kenttä 1!
  // jos varastoon-kentässä on 0 niin tätä riviä ei haluttu vielä viedä varastoon
  $query = "SELECT tilausrivi.*, {$ale_query_select_lisa} alennuksetyht, tilausrivin_lisatiedot.poikkeava_tulliprosentti, tuote.tullinimike1, tuote.tullinimike2
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
            LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
            LEFT JOIN tilausrivin_lisatiedot ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN tuote ON ( tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno )
            {$suuntalavajoin}
            WHERE {$uusiotunnuslisa}
            and tilausrivi.varattu   <> 0
            and tilausrivi.yhtio     = '$kukarow[yhtio]'
            and tilausrivi.tyyppi    = 'O'
            and tilausrivi.varastoon = 1
            $varastoon_tilausrivirajaus
            ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.varattu DESC, tilausrivi.laskutettuaika";
}
elseif ($toiminto == "kalkyyli" and $tee == "") {

  $uusiotunnuslisa = "tilausrivi.uusiotunnus = '{$laskurow['tunnus']}'";
  $suuntalavajoin = "";

  if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

    $query = "SELECT GROUP_CONCAT(saapuminen) keikkatunnus
              FROM suuntalavat_saapuminen
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND suuntalava = '{$suuntalavan_tunnus}'";
    $uusiotunnus_chk_res = pupe_query($query);
    $uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

    $uusiotunnuslisa = "tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})";

    $suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava AND suuntalavat.tila = 'S' AND suuntalavat.tunnus = '{$suuntalavan_tunnus}')
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus) ";
  }

  // Näytetään ruudulla
  $query = "SELECT tilausrivi.*, {$ale_query_select_lisa} alennuksetyht, tilausrivin_lisatiedot.poikkeava_tulliprosentti, tuote.tullinimike1, tuote.tullinimike2
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
            LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
            LEFT JOIN tilausrivin_lisatiedot ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN tuote ON ( tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno )
            {$suuntalavajoin}
            WHERE {$uusiotunnuslisa}
            and tilausrivi.varattu <> 0
            and tilausrivi.yhtio   = '$kukarow[yhtio]'
            and tilausrivi.tyyppi  = 'O'
            ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.varattu DESC, tilausrivi.laskutettuaika";
}

$presult = pupe_query($query);

// jos meillä on valuuttakeikka, lasketaan keskikurssi kaikille vaihto-omaisuuslaskuille ja otetaan valuutta mukaan
$query  = "SELECT max(valkoodi) valkoodi, sum(summa) summa, sum(arvo) arvo, ifnull(round(sum(summa * vienti_kurssi) / sum(summa), 6), 0) vientikurssi
           FROM lasku
           WHERE yhtio     = '$kukarow[yhtio]'
           and tila        = 'K'
           and alatila     in ('','S')
           and vanhatunnus <> 0
           and laskunro    = '$laskurow[laskunro]'
           and vienti      in ('C','F','I','J','K','L')";
$result = pupe_query($query);
$kurssirow = mysql_fetch_assoc($result);

if ($laskurow["vienti_kurssi"] != 1 and $kurssirow["vientikurssi"] != 0) {
  $laskurow["vienti_kurssi"] = $kurssirow["vientikurssi"];
}
$laskurow["valkoodi"] = $kurssirow["valkoodi"];

// Lasketaan verokanta
if ($kurssirow["arvo"] != 0) $simualv = round(100 * (($kurssirow["summa"]/$kurssirow["arvo"])-1),2);
else $simualv = 0;

// Onko miellä oletuksista poikkeava alvi. Verokantoja ennen viimeisintä alv-muutosta.
if (in_array($simualv, array(23, 13, 9))) {
  $simualv = (float) $simualv;
}
else {
  $simualv = 0;
}

// tsekataan vielä
if ($laskurow["vienti_kurssi"] == 0) {
  echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");

  if ($kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
    exit;
  }
}

if ((($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) and ($yhtiorow["jalkilaskenta_kuluperuste"] == "" or ($yhtiorow["jalkilaskenta_kuluperuste"] == "PX" and $laskurow["saldo_maksettu"] != 0))) {
  // Jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu summa OLETUSVALUUTASSA on laskurow.saldo_maksettu)
  // jalkilaskenta_kuluperuste == "PX" --> Virallisen varastonarvon laskentaan käytetään kululaskujen summaa, mikäli kululaskut on syötetty, muuten kulusummaa/kuluprosenttia

  $rahtikulut = $laskurow['saldo_maksettu'] + round(($laskurow['rahti_etu'] + $laskurow['pyoristys_erot']) * $laskurow['vienti_kurssi'], 6);
  $rahtitapa  = t("Virallinen");
}
else {
  // muuten rahtikulut valuutassa laskettuna kuluprosentin mukaan (laskurow.rahti on kuluprosentti, laskurow.summa on kohdistettujen rivien summa)
  // jalkilaskenta_kuluperuste == "KP" --> Virallisen varastonarvon laskentaan käytetään saapumiselle syötettyä kulusummaa/kuluprosenttia

  // jos ollaan annettu tälle batchille kulusumma, käytetäänkin vaan sitä!
  if ($laskurow["rahti_huolinta"] != 0) {
    $rahtikulut = $laskurow["rahti_huolinta"] + (($laskurow['pyoristys_erot'] + $laskurow['rahti_etu']) * $laskurow['vienti_kurssi']);
    $rahtitapa  = t("Kulusumma");
  }
  else {
    $query_ale_lisa = generoi_alekentta('O');

    if ($toiminto == "kaikkiok") {
      $maara_peruste = "and tilausrivi.varattu = 0 and tilausrivi.kpl != 0";
    }
    else {
      $maara_peruste = "and tilausrivi.varattu != 0";
    }

    // lasketaan nyt varastoon vietävien rivien summa yhteensä
    $query    = "SELECT sum((tilausrivi.varattu + tilausrivi.kpl) * if(tuotteen_toimittajat.tuotekerroin <= 0 or tuotteen_toimittajat.tuotekerroin is null, 1, tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa}) summa
                 FROM tilausrivi use index (uusiotunnus_index)
                 LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '{$laskurow["liitostunnus"]}' and tuotteen_toimittajat.yhtio = tilausrivi.yhtio and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno)
                 WHERE tilausrivi.uusiotunnus = '$otunnus'
                 AND tilausrivi.yhtio         = '{$kukarow["yhtio"]}'
                 AND  tilausrivi.tyyppi       = 'O'
                 AND tilausrivi.varastoon     = 1
                 $maara_peruste";
    $result   = pupe_query($query);
    $hintarow = mysql_fetch_assoc($result);

    $laskurow['summa'] = $hintarow['summa'];

    // lasketaan itse rahtikulut
    // Lasketaan kuluprosentti rivien summasta + pyöristyseroista, ja lisätään lopuksi pyöristysero + rahtietu rahtikuluihin mukaan
    $rahtikulut = ($laskurow['summa'] + $laskurow['pyoristys_erot']) * $laskurow['vienti_kurssi'];
    $rahtikulut =  $rahtikulut * ($laskurow['rahti'] / 100);
    $rahtikulut += ($laskurow['rahti_etu'] + $laskurow['pyoristys_erot']) * $laskurow['vienti_kurssi'];
    $rahtikulut = round($rahtikulut, 6);
    $rahtitapa  = t("Kuluprosentti") ." ". $laskurow['rahti']. "%";
  }
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<br>";
  echo "<font class='message'>".t("Saapuminen")." $laskurow[laskunro] $laskurow[nimi]</font><br>";
  if ($laskurow['rahti_etu'] <> 0) {
    echo "<font class='message'>".t("Eturahti").": ". $laskurow['rahti_etu'] * $laskurow['vienti_kurssi'] ." $yhtiorow[valkoodi] </font><br>";
  }
  if ($laskurow['pyoristys_erot'] <> 0) {
    echo "<font class='message'>".t("Pyöristyserot").": ". $laskurow['pyoristys_erot'] * $laskurow['vienti_kurssi'] ." $yhtiorow[valkoodi] </font><br>";
  }
  echo "<font class='message'>".t("Rahdin ja kulujen määrä yhteensä").": $rahtikulut $yhtiorow[valkoodi] (".t("Peruste").": $rahtitapa)<br><hr></font>";
}

$summa        = 0;
$jtrivit      = array();
$jtrivit_paikat   = array();
$jtvarastot      = array();
$rivia         = 0;
$korjattavat_valmistukset = array();
$korjattavat_valmistukset_ind = 0;
$suoralisa   = "";

if ($yhtiorow['automaattinen_jt_toimitus'] == 'S') {
  $suoralisa = "AND tilausrivin_lisatiedot.suoraan_laskutukseen = 'o'";
}

// Oletusvarastoonvientipäivä
$ovpppa = date("d");
$ovpkka = date("m");
$ovpvva = date("Y");

//Avoimen kauden tiedot
list($tkavv,$tkakk,$tkapp) = explode("-", $yhtiorow["tilikausi_alku"]);
list($tklvv,$tklkk,$tklpp) = explode("-", $yhtiorow["tilikausi_loppu"]);

$tilikausi_alku  = (int) date('Ymd',mktime(0,0,0, $tkakk, $tkapp, $tkavv));
$tilikausi_loppu = (int) date('Ymd',mktime(0,0,0, $tklkk, $tklpp, $tklvv));

// jos kyseessä on ihan virallinen varastonarvonlaskenta, tehdään näin
if ($toiminto == "kaikkiok" and $tee == "varma" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<font class='message'>".t("Suoritetaan jälkilaskentaa %s tuotteelle", "", mysql_num_rows($presult)).":<br></font>";
}
elseif ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<font class='message'>".t("Viedään varastoon %s tuotetta", "", mysql_num_rows($presult)).":<br></font>";
}

if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  require_once ('inc/ProgressBar.class.php');

  $bar = new ProgressBar();

  $bar->initialize(mysql_num_rows($presult)); // print the empty bar
}

while ($tilrivirow = mysql_fetch_assoc($presult)) {

  $hankintakulut = "";

  if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
    $bar->increase(); //calls the bar with every processed element
  }

  // lasketaan keskihankintahinta, tarvitaan tuotteen saldo ja tuotteen tiedot
  $query  = "SELECT sum(saldo) saldo
             FROM tuotepaikat
             WHERE yhtio = '$kukarow[yhtio]'
             AND tuoteno = '$tilrivirow[tuoteno]'";
  $salres = pupe_query($query);
  $salrow = mysql_fetch_assoc($salres);

  $query  = "SELECT tuote.*,
             if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) alv,
             if(tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) tuotekerroin
             from tuote
             LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
             where tuote.yhtio = '$kukarow[yhtio]'
             and tuote.tuoteno = '$tilrivirow[tuoteno]'";
  $tuores = pupe_query($query);
  $tuorow = mysql_fetch_assoc($tuores);

  if ((float) $tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin'] = 1;

  // jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
  if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
    if ($simualv > 0) {
      $alvit = 1 + $simualv / 100;
    }
    else {
      $alvit = 1 + $tuorow["alv"] / 100;
    }
  }
  else {
    $alvit = 1;
  }

  $ale_lisa_apu_kala = generoi_alekentta_php($tilrivirow, 'O', 'kerto');

  // lasketaan rivin hinta
  $osuus_apu_hinta = round($tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*$ale_lisa_apu_kala*$tuorow['tuotekerroin'], $yhtiorow["hintapyoristys"]);

  // jos keikan arvo on muuta kuin nolla ja rivin hinta on pienempi kuin keikan hinta, lasketaan rahtiosuus. muuten nollaa
  if ($laskurow['summa'] != 0 and $osuus_apu_hinta <= $laskurow["summa"] + 0.01) {
    // rivin hinta
    $osuus   = $osuus_apu_hinta / $laskurow['summa'];  // kuinka paljon tämä rivi on koko keikasta
    $rahtios = $osuus*$rahtikulut;            // lasketaan sama osuus rahtikuluista tälle riville
  }
  else {
    $rahtios = 0;
    $osuus   = 0;
  }

  if ($tilrivirow['varattu'] < 0) {
    $rahtios = $rahtios * -1;
  }

  // tämä on miinusta hyvitysrivillä
  $ohinta = round($tilrivirow['hinta']*$ale_lisa_apu_kala*$tuorow['tuotekerroin']*$laskurow['vienti_kurssi']*$tilrivirow['varattu'], 6); // tuotteen rivihinta OLETUSVALUUTASSA

  // ennen varastoon vientiä tallennetaan alkuperäinen ostohinta talteen
  if ($toiminto == "kalkyyli" and $tee == "varastoon" and $tilrivirow['hinta_alkuperainen'] == 0) {
    $query  = "UPDATE tilausrivi SET
               hinta_alkuperainen = '{$ohinta}'
               WHERE yhtio        = '{$kukarow['yhtio']}'
               AND tunnus         = '{$tilrivirow['tunnus']}'";
    $resultxxx = pupe_query($query);
  }

  $ohinta = round($ohinta+$rahtios, 6); // tuotteen rivihinta rahteineen OLETUSVALUUTASSA

  // Osuus kululaskuista # Osuus eturahdista
  $hankintakulut .= round($laskurow["saldo_maksettu"] * $osuus, 2)."#".round($laskurow['rahti_etu'] * $laskurow['vienti_kurssi'] * $osuus, 6);

  $tulliprossa   = 0;
  $aputullimaara = 0;
  $tv_nimrow     = "";

  if ($tv_kaytossa and $laskurow["maa"] != $yhtiorow["maa"]) {
    // Tulliprosentti
    $oletus_tulliprosentti = hae_oletus_tulliprosentti($laskurow, $tilrivirow);

    //jos ostotilausrivin lisätietoihin on tallennettu poikkeava_tullipronsetti niin käytetään sitä. Muuten käytetään taric_veroperusteet oletusta.
    if (!empty($tilrivirow['poikkeava_tulliprosentti']) and $tilrivirow['poikkeava_tulliprosentti'] != (int) $oletus_tulliprosentti) {
      $tulliprossa = $tilrivirow['poikkeava_tulliprosentti'];
    }
    else {
      $tulliprossa = $oletus_tulliprosentti;
    }

    $aputullimaara = $ohinta * ($tulliprossa/100);
    $ohinta = $ohinta * (1 + ($tulliprossa/100));
    $tv_nimrow["maara"] = $tv_nimrow["maara"] * 1; // tehään numero, että lista näyttää kivemmalta
  }

  // Tulli% # Aputullimäärä
  $hankintakulut .= "#".sprintf('%.02f',$tulliprossa)."#".sprintf('%.02f',$aputullimaara);
  $kuluteksti = "";

  // lisätään riville extra kulu, jos sellanen oli annettu
  if ($tilrivirow["kate"] != 0) {
    $kuluteksti = "($ohinta + $tilrivirow[kate])";
    $ohinta = $ohinta + $tilrivirow['kate'];
  }

  // Varastoonvientipäivä
  if ($tilrivirow["toimitettuaika"] == "0000-00-00 00:00:00") {
    $varastoonvientipva = date("Y-m-d H:i:s");
  }
  else {
    $varastoonvientipva = $tilrivirow["toimitettuaika"];
  }

  // Tsekataan, että varastoonvientipäivä on avoimella kaudella
  //Avoimen kauden tiedot
  list($tkvpvv,$tkvpkk,$tkvppp) = explode("-", substr($varastoonvientipva,0,10));

  $varastoonvientipva_tsek  = (int) date('Ymd',mktime(0,0,0, $tkvpkk, $tkvppp, $tkvpvv));

  if ($tee == "varastoon" and ($varastoonvientipva_tsek < $tilikausi_alku or $varastoonvientipva_tsek > $tilikausi_loppu)) {

    if ($tv_kaytossa) $vcspan = 10;
    else $vcspan = 8;

    $varastoon_msg .= "  <tr>";
    $varastoon_msg .= "<td></td>";
    $varastoon_msg .= "<td>".tv1dateconv($varastoonvientipva)."</td>";
    $varastoon_msg .= "<td>$tilrivirow[tuoteno]</td>";
    $varastoon_msg .= "<td colspan='$vcspan'>".t("VIRHE: Varastoonvientipäivä ei kuulu avoimeen kauteen. Riviä ei viety varastoon.")."</td>";
    $varastoon_msg .= "</tr>";
    continue;
  }

  // Rivinlisäkulu
  $hankintakulut .= "#$tilrivirow[kate]";

  $rivihin = round($ohinta, $yhtiorow['hintapyoristys']);  // tilausrivin rivihinta talteen

  // tämä muuttaa hinnan plussaksi hyvityskeikalla (jolloin kehahin matikka menee oikein)
  $ohinta  = round($ohinta / $tilrivirow['varattu'], 6); // yhden tuotteen hinta kaikkine kuluineen

  //Keskihankintahintaa ylläpidetään vain jos tuotteella ei ole sarjanumeroseurantaa tai marginaaliverostusta
  if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
    //Laitetaan kehahintamuuttujaan tämän keikan ostohinta jotta tapahtumat näyttäisivät fiksuja lukuja
    $kehahin = $ohinta;
  }
  else {
    if ($salrow['saldo'] + $tilrivirow['varattu'] != 0 and $salrow['saldo'] != 0 and $salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu'] != 0) {
      // kehahin matikka, tuotteella pitää olla saldoa ennen ja jälkeen että edes tehdään matikkaa, sekä jakolaskun osoittaja pitää olla positiivinen
      $kehahin = round(($salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu']) / ($salrow['saldo'] + $tilrivirow['varattu']), 6);

      if ($debug >= 1) {
        $varastoon_msg .= "<font class='message'>Tuote: $tuorow[tuoteno] Matikka: ($salrow[saldo] * $tuorow[kehahin] + $ohinta * $tilrivirow[varattu]) / ($salrow[saldo] + $tilrivirow[varattu]) = $kehahin</font><br>";
      }
    }
    else {
      // Jos jäädään saldossa tämänkin tulon jälkeen miinukselle tai nollille, laitetaan kehahin suoraan tämän keikan hinnasta
      $kehahin = round($ohinta, 6);

      if ($debug >= 1) {
        $varastoon_msg .= "<font class='message'>Tuote: $tuorow[tuoteno] Saldo jäi nollaan tai miinukselle. Kehahin = ostohinta = $kehahin</font><br>";
      }
    }
  }

  $summa += round($ohinta*$tilrivirow['varattu'], 2);

  if ($toiminto == "kaikkiok" and $tee == "varma") {
    // tarvitaan
    // $tuoteno =  korjattava tuote
    // $pvm = mihin päivään asti korjataan
    // $uusihinta = mikä on tuon pvm:n oikea ostohinta
    // $rivitunnus = mikä on tapahtuman tehneen rivin tunnus

    $tuoteno  = $tilrivirow["tuoteno"];
    $uusihinta  = $ohinta;
    $pvm    = $tilrivirow["laskutettuaika"]; // koska tämä tuote oli viety varastoon
    $rivitunnus = $tilrivirow["tunnus"];     // rivin tunnus, tällä löydetään varmasti oikea tapahtuma

    $uusikehahin = jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus);
    $uusikehahin = sprintf('%.6f', $uusikehahin);

    if ($hankintakulut != '') {
      $query = "SELECT hankintakulut FROM tilausrivin_lisatiedot WHERE yhtio = '$kukarow[yhtio]' AND tilausrivitunnus = '$tilrivirow[tunnus]'";
      $resxxxx = pupe_query($query);

      if (mysql_num_rows($resxxxx) == 0) {
        $query = "INSERT INTO tilausrivin_lisatiedot SET hankintakulut = '$hankintakulut', yhtio = '$kukarow[yhtio]', tilausrivitunnus = '$tilrivirow[tunnus]'";
        $hankresxxx = pupe_query($query);
      }
      else {
        $query = "UPDATE tilausrivin_lisatiedot SET hankintakulut = '$hankintakulut' WHERE yhtio = '$kukarow[yhtio]' AND tilausrivitunnus = '$tilrivirow[tunnus]'";
        $hankresxxx = pupe_query($query);
      }
    }
  }
  elseif ($toiminto == "kalkyyli" and $tee == "varastoon") {

    // päivitetään tilausrivin rivihintaa
    $query  = "UPDATE tilausrivi set
               rivihinta   = '$rivihin'
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$tilrivirow[tunnus]'";
    $resultxxx = pupe_query($query);

    if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
      $kehalis = "";
    }
    else {
      $kehalis = " kehahin = '$kehahin', ";
    }

    // päivitetään tuote
    $query = "UPDATE tuote set
              $kehalis
              vihahin     = round('$ohinta','$yhtiorow[hintapyoristys]'),
              vihapvm     = '$varastoonvientipva'
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$tilrivirow[tuoteno]'";
    $result = pupe_query($query);

    if ($tuorow["sarjanumeroseuranta"] == "E" or $tuorow["sarjanumeroseuranta"] == "F" or $tuorow["sarjanumeroseuranta"] == "G") {

      $query = "SELECT era_kpl, tunnus
                FROM sarjanumeroseuranta
                WHERE yhtio          = '$kukarow[yhtio]'
                and tuoteno          = '$tilrivirow[tuoteno]'
                and ostorivitunnus   = '$tilrivirow[tunnus]'
                and myyntirivitunnus = 0";
      $sarjares = pupe_query($query);

      while($sarjarow = mysql_fetch_assoc($sarjares)) {
        if ($sarjarow["era_kpl"] < 0) {
          $query = "UPDATE sarjanumeroseuranta
                    SET era_kpl = 0
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = $sarjarow[tunnus]";
          $lisa_res = pupe_query($query);
        }
      }
    }

    // muuten tässä ollaan sitten viemässä kamaa ihan oikeesti saldoille.. tehdään siihen liittyvät hommat.
    $uusikehahin = "";

    if ($tilrivirow["tunnus"] != $tilrivirow["perheid2"] and $tilrivirow["perheid2"] > 0) {
      $lisavartlisa = " ".t("Saldo on varattu.");
    }
    else {
      $lisavartlisa = "";
    }

    // lisätään tapahtuma
    $query = "INSERT into tapahtuma set
              yhtio      = '$kukarow[yhtio]',
              tuoteno    = '$tilrivirow[tuoteno]',
              kpl        = '$tilrivirow[varattu]',
              hinta      = '$kehahin',
              kplhinta   = '$ohinta',
              laji       = 'tulo',
              hyllyalue  = '$tilrivirow[hyllyalue]',
              hyllynro   = '$tilrivirow[hyllynro]',
              hyllytaso  = '$tilrivirow[hyllytaso]',
              hyllyvali  = '$tilrivirow[hyllyvali]',
              selite     = '$laskurow[nimi] $laskurow[nimitark], ".t("Tilaus").": $tilrivirow[otunnus], ".t("Ostohinta").": $ohinta $lisavartlisa',
              laatija    = '$kukarow[kuka]',
              rivitunnus = '$tilrivirow[tunnus]',
              laadittu   = '$varastoonvientipva'";
    $result = pupe_query($query);
    $tapahtumaid = mysql_insert_id();

    // Viedään koko suuntalava varastoon. Eli kaikille suuntalavalla oleville tuotteille päivitetään sama tuotepaikka
    if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus) and trim($koko_suuntalava) == 'X') {

      $query = "SELECT *
                FROM tuotepaikat
                WHERE yhtio   = '{$kukarow['yhtio']}'
                AND tuoteno   = '{$tilrivirow['tuoteno']}'
                AND hyllyalue = '{$tilrivirow['hyllyalue']}'
                AND hyllynro  = '{$tilrivirow['hyllynro']}'
                AND hyllytaso = '{$tilrivirow['hyllytaso']}'
                AND hyllyvali = '{$tilrivirow['hyllyvali']}'
                LIMIT 1";
      $paikka_chk_res = pupe_query($query);

      if (mysql_num_rows($paikka_chk_res) == 0) {
        // lisätään paikka
        $query = "INSERT INTO tuotepaikat SET
                  yhtio         = '{$kukarow['yhtio']}',
                  tuoteno       = '{$tilrivirow['tuoteno']}',
                  oletus        = '',
                     saldo      = '{$tilrivirow['varattu']}',
                  saldo_varattu = if({$tilrivirow['tunnus']} != {$tilrivirow['perheid2']} and {$tilrivirow['perheid2']} > 0, {$tilrivirow['varattu']}, 0),
                     saldoaika  = now(),
                  hyllyalue     = '{$tilrivirow['hyllyalue']}',
                  hyllynro      = '{$tilrivirow['hyllynro']}',
                  hyllytaso     = '{$tilrivirow['hyllytaso']}',
                  hyllyvali     = '{$tilrivirow['hyllyvali']}',
                  tyyppi        = 'S',
                  laatija       = '{$kukarow['kuka']}',
                  luontiaika    = now()";
        $result = pupe_query($query);

        // tehdään tapahtuma
        $query = "INSERT into tapahtuma set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tilrivirow[tuoteno]',
                  kpl       = '0',
                  kplhinta  = '0',
                  hinta     = '0',
                  laji      = 'uusipaikka',
                  hyllyalue = '{$tilrivirow['hyllyalue']}',
                  hyllynro  = '{$tilrivirow['hyllynro']}',
                  hyllytaso = '{$tilrivirow['hyllytaso']}',
                  hyllyvali = '{$tilrivirow['hyllyvali']}',
                  selite    = '".t("Lisättiin tuotepaikka")." {$tilrivirow['hyllyalue']} {$tilrivirow['hyllynro']} {$tilrivirow['hyllyvali']} {$tilrivirow['hyllytaso']}',
                  laatija   = '$kukarow[kuka]',
                  laadittu  = date_sub('$varastoonvientipva', interval 1 SECOND)";
        $result = pupe_query($query);
      }
      else {
        // päivitetään saldo tuoterivillä olevalle paikalle
        $query = "UPDATE tuotepaikat SET
                  saldo         = saldo+{$tilrivirow['varattu']},
                  saldo_varattu = saldo_varattu + if({$tilrivirow['tunnus']} != {$tilrivirow['perheid2']} and {$tilrivirow['perheid2']} > 0, {$tilrivirow['varattu']}, 0),
                  saldoaika     = now(),
                  tyyppi        = 'S'
                  WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND tuoteno   = '{$tilrivirow['tuoteno']}'
                  AND hyllyalue = '{$tilrivirow['hyllyalue']}'
                  AND hyllynro  = '{$tilrivirow['hyllynro']}'
                  AND hyllytaso = '{$tilrivirow['hyllytaso']}'
                  AND hyllyvali = '{$tilrivirow['hyllyvali']}'
                  LIMIT 1";
        $result = pupe_query($query);
      }
    }
    else {
      // päivitetään saldo tuoterivillä olevalle paikalle
      $query = "UPDATE tuotepaikat set
                saldo         = saldo+$tilrivirow[varattu],
                saldo_varattu = saldo_varattu + if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
                saldoaika     = now()
                where yhtio   = '$kukarow[yhtio]' and
                tuoteno       = '$tilrivirow[tuoteno]' and
                hyllyalue     = '$tilrivirow[hyllyalue]' and
                hyllynro      = '$tilrivirow[hyllynro]' and
                hyllytaso     = '$tilrivirow[hyllytaso]' and
                hyllyvali     = '$tilrivirow[hyllyvali]'
                limit 1";
      $result = pupe_query($query);
    }

    $jtvarastot[$tilrivirow["tunnus"]] = array($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]);
    $millepakallemeni = "";

    if (mysql_affected_rows() == 0) {
      $varastoon_msg .= "<font class='error'>".t("Varastopaikka")." $tilrivirow[hyllyalue]-$tilrivirow[hyllynro]-$tilrivirow[hyllytaso]-$tilrivirow[hyllyvali] ".t("ei löydy vaikka se on syötetty tilausriville! Koitetaan päivittää oletustapaikkaa!")."</font>";

      $query = "UPDATE tuotepaikat
                SET saldo        = saldo+$tilrivirow[varattu],
                saldo_varattu  = saldo_varattu + if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
                saldoaika      = now()
                WHERE yhtio    = '$kukarow[yhtio]' and
                tuoteno        = '$tilrivirow[tuoteno]' and
                oletus        != ''
                LIMIT 1";
      $result = pupe_query($query);

      $query = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
                FROM tuotepaikat
                WHERE yhtio  = '$kukarow[yhtio]' and
                    tuoteno  = '$tilrivirow[tuoteno]' and
                    oletus  != ''
                    LIMIT 1";
      $olpares = pupe_query($query);
      $olparow = mysql_fetch_assoc($olpares);

      $jtvarastot[$tilrivirow["tunnus"]] = array($olparow["hyllyalue"], $olparow["hyllynro"]);
      $millepakallemeni = " hyllyalue = '$olparow[hyllyalue]', hyllynro = '$olparow[hyllynro]', hyllyvali = '$olparow[hyllyvali]', hyllytaso = '$olparow[hyllytaso]' ";

         if (mysql_affected_rows() == 0) {

           $varastoon_msg .= "<br><font class='error'>".t("Tuotteella")." $tilrivirow[tuoteno] ".t("ei ole oletuspaikkaa")."!!!</font><br><br>";

        // haetaan firman eka varasto, tökätään kama sinne ja tehdään siitä oletuspaikka
        $query = "SELECT alkuhyllyalue, alkuhyllynro
                  FROM varastopaikat
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = ''
                  ORDER BY alkuhyllyalue, alkuhyllynro
                  LIMIT 1";
        $result = pupe_query($query);

        if (mysql_num_rows($result) != 1) {
          // haetaan firman eka varasto, tökätään kama sinne ja tehdään siitä oletuspaikka
          $query = "SELECT alkuhyllyalue, alkuhyllynro
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
                    ORDER BY alkuhyllyalue, alkuhyllynro
                    LIMIT 1";
          $result = pupe_query($query);
        }

        if (mysql_num_rows($result) == 1) {
          $hyllyrow =  mysql_fetch_assoc($result);
        }
        else {
          $hyllyrow = array();
          $hyllyrow["alkuhyllyalue"] = "A";
          $hyllyrow["alkuhyllynro"]  = "0";
        }

           $varastoon_msg .= "<br><font class='error'>".t("Tehtiin oletuspaikka")." $tilrivirow[tuoteno]: $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0</font><br><br>";

        // lisätään paikka
        $query = "INSERT INTO tuotepaikat set
                  yhtio         = '$kukarow[yhtio]',
                  tuoteno       = '$tilrivirow[tuoteno]',
                  oletus        = 'X',
                     saldo      = '$tilrivirow[varattu]',
                  saldo_varattu = if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
                     saldoaika  = now(),
                  hyllyalue     = '$hyllyrow[alkuhyllyalue]',
                  hyllynro      = '$hyllyrow[alkuhyllynro]',
                  hyllytaso     = '0',
                  hyllyvali     = '0'";
        $result = pupe_query($query);

        $jtvarastot[$tilrivirow["tunnus"]] = array($hyllyrow["alkuhyllyalue"], $hyllyrow["alkuhyllynro"]);
        $millepakallemeni = "hyllyalue = '$hyllyrow[alkuhyllyalue]', hyllynro = '$hyllyrow[alkuhyllynro]', hyllyvali = '0', hyllytaso = '0' ";

        // tehdään tapahtuma
        $query = "INSERT into tapahtuma set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tilrivirow[tuoteno]',
                  kpl       = '0',
                  kplhinta  = '0',
                  hinta     = '0',
                  laji      = 'uusipaikka',
                  hyllyalue = '$hyllyrow[alkuhyllyalue]',
                  hyllynro  = '$hyllyrow[alkuhyllynro]',
                  hyllytaso = '0',
                  hyllyvali = '0',
                  selite    = '".t("Lisättiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
                  laatija   = '$kukarow[kuka]',
                  laadittu  = date_sub('$varastoonvientipva', interval 1 SECOND)";
        $result = pupe_query($query);
         }
      else {
        $varastoon_msg .= " <font class='error'>".t("Huh, se onnistui!")."</font><br>";
      }
    }

    // Saldo ei mennytkään ostorivin osoittamalle paikalle, päivitetään riville ja tapahtumalle kuitenkin paikat jolle saldo loppupeleissä meni
    if ($millepakallemeni != "") {
      $query  = "UPDATE tapahtuma set
                 $millepakallemeni
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$tapahtumaid'";
      $varastoon_result = pupe_query($query);

      $millepakallemeni = " ,".$millepakallemeni;
    }

    // päivitetään tilaus saapuneeksi varastoon
    // päivitetään myös tehdaslisävarusteet saapuneiksi
    // päivitetään riville paikat jolle saldo loppupeleissä meni (jos meni ok niin $millepakallemeni on tyhjä)

    // and (tunnus = '$tilrivirow[tunnus]' or perheid2 = '$tilrivirow[tunnus]')";
    $query  = "UPDATE tilausrivi set
               kpl             = varattu,
               varattu         = 0,
               laskutettuaika  = '$varastoonvientipva',
               laskutettu      = '$kukarow[kuka]'
               {$millepakallemeni}
               WHERE yhtio     = '$kukarow[yhtio]'
               and otunnus     = '$tilrivirow[otunnus]'
               and varattu    != 0
               and tunnus      = '$tilrivirow[tunnus]'";
    $varastoon_result = pupe_query($query);

    $rivia++;

    //  Kohdistetaan vain oikeille asiakkaille näissä caseissa
    if ($yhtiorow["automaattinen_jt_toimitus"] != "") {
      //Tutkitaan löytyykö JT-rivi joka mäppäytyy tähän ostoriviin
         $query = "SELECT ifnull(if((tilausrivi.perheid != 0 and tilausrivi.tunnus != tilausrivi.perheid), tilausrivi.perheid, tilausrivi.tunnus), 0) jtrivi
                   FROM tilausrivin_lisatiedot
                   JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
                   WHERE tilausrivin_lisatiedot.yhtio          = '$kukarow[yhtio]'
                   AND tilausrivin_lisatiedot.tilausrivilinkki = '$tilrivirow[tunnus]'
                   $suoralisa";
      $varastoon_result = pupe_query($query);

      while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
        // Mitkä suoratoimitukset tuloutettiin tällä keikalla
        $jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
        // Katotaan mille paikalle nää meni, jotta suoratoimitus voidaan laukasta tältä paikalta
        $jtrivit_paikat[$varastoon_row["jtrivi"]] = $tapahtumaid;
      }
    }
  }

  // näytetään ruudulla
  $varastoon_msg .= "  <tr>";

  if ($tilrivirow["toimitettuaika"] == "0000-00-00 00:00:00") {
    $vpppa = $ovpppa;
    $vpkka = $ovpkka;
    $vpvva = $ovpvva;
  }
  else {
    list($vpvva, $vpkka, $vpppa) = explode("-", substr($tilrivirow["toimitettuaika"], 0, 10));
  }

  if ($toiminto == "kalkyyli" and $tee == "") {
    $chk = "";
    if ($tilrivirow["varastoon"]) {
      $chk = "CHECKED";
    }

    $varastoon_msg .= "<td align='center' valign='top'><input type='checkbox' name='varastoonko[]' value='$tilrivirow[tunnus]' $chk>";

    if (!$tilrivirow['varastoon']) {
      $varastoon_msg .= "<br /><img src='../pics/lullacons/alert.png' alt='".t("Huomio")."' title='".t("HUOM: Riviä ei viedä automaattisesti varastoon.")."' />";
    }

    $varastoon_msg .= "</td>";

    if ($yhtiorow["varastoonvientipaiva"] == "K") {
      $varastoon_msg .= "<td valign='top' nowrap>
                <input type='text' name='ppa[$tilrivirow[tunnus]]' value='$vpppa' size='3'>
                <input type='text' name='kka[$tilrivirow[tunnus]]' value='$vpkka' size='3'>
                <input type='text' name='vva[$tilrivirow[tunnus]]' value='$vpvva' size='5'></td>";
    }
    else {
      $varastoon_msg .= "<td valign='top' nowrap>".tv1dateconv("$vpvva-$vpkka-$vpppa")."</td>";
    }

    if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

      $query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilrivirow['uusiotunnus']}'";
      $saapuminen_chk_res = pupe_query($query);
      $saapuminen_chk_row = mysql_fetch_assoc($saapuminen_chk_res);

      $varastoon_msg .= "<td>{$saapuminen_chk_row['laskunro']}</td>";

      $query = "SELECT suuntalavat.sscc
                FROM suuntalavat
                JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$tilrivirow['uusiotunnus']}')
                WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
                AND suuntalavat.tunnus  = '{$suuntalavan_tunnus}'";
      $sscc_res = pupe_query($query);
      $sscc_row = mysql_fetch_assoc($sscc_res);

      // näytetään listauksessa SSCC-koodi ja otetaan mukaan suuntalavan tunnus
      $varastoon_msg .= "<td>{$sscc_row['sscc']}</td>";
    }
  }
  else {
    if ($tilrivirow["varastoon"]) {
      $varastoon_msg .= "<td align='center' valign='top'>x</td>";
    }
    else {
      $varastoon_msg .= "<td></td>";
    }

    $varastoon_msg .= "<td valign='top' nowrap>".tv1dateconv("$vpvva-$vpkka-$vpppa")."</td>";

    if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

      $query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilrivirow['uusiotunnus']}'";
      $saapuminen_chk_res = pupe_query($query);
      $saapuminen_chk_row = mysql_fetch_assoc($saapuminen_chk_res);

      $varastoon_msg .= "<td>{$saapuminen_chk_row['laskunro']}</td>";

      $query = "SELECT suuntalavat.sscc
                FROM suuntalavat
                JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$tilrivirow['uusiotunnus']}')
                WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
                AND suuntalavat.tunnus  = '{$suuntalavan_tunnus}'";
      $sscc_res = pupe_query($query);
      $sscc_row = mysql_fetch_assoc($sscc_res);

      // näytetään listauksessa SSCC-koodi ja otetaan mukaan suuntalavan tunnus
      $varastoon_msg .= "<td>{$sscc_row['sscc']}</td>";
    }
  }

  $varastoon_msg .= "  <td valign='top'>$tilrivirow[tuoteno]</td>
            <td align='right' valign='top'>$tilrivirow[varattu]<br>".sprintf('%.2f', $tilrivirow['varattu']*$tuorow['tuotekerroin'])."</td>
            <td align='right' valign='top'>".sprintf('%.'.$yhtiorow['hintapyoristys'].'f', $tilrivirow['hinta'])."<br>".sprintf('%.'.$yhtiorow['hintapyoristys'].'f', $tilrivirow['hinta']*$tuorow['tuotekerroin'])."</td>
            <td align='right' valign='top'>".$tilrivirow["alennuksetyht"]."%</td>
            <td align='right' valign='top'>".sprintf('%.6f', $osuus)."</td>
            <td align='right' valign='top'>".sprintf('%.6f', $osuus*$rahtikulut)."</td>";

  if ($tv_kaytossa) {
    // Tulliprosentti
    $oletus_tulliprosentti = hae_oletus_tulliprosentti($laskurow, $tilrivirow);

    //jos ostotilausrivin lisätietoihin on tallennettu poikkeava_tullipronsetti niin käytetään sitä. Muuten käytetään taric_veroperusteet oletusta.
    if (!empty($tilrivirow['poikkeava_tulliprosentti']) and $tilrivirow['poikkeava_tulliprosentti'] != (int) $oletus_tulliprosentti) {
      $tulliprosentti = $tilrivirow['poikkeava_tulliprosentti'];
    }
    else {
      $tulliprosentti = $oletus_tulliprosentti;
    }

    if (empty($tee)) {
      $varastoon_msg .= "<input type='hidden' name='rivi_oletus_tulliprosentti[{$tilrivirow['tunnus']}]' value='".(float) $oletus_tulliprosentti."' />";
      $varastoon_msg .= "<td align='right' valign='top'><input type='text' name='poikkeavat_tulliprosentit[{$tilrivirow['tunnus']}]' value='".(float) $tulliprosentti."' size='5' style='text-align:right'/> {$tv_nimrow['rahayksikko']}</td>";
    }
    else {
      $varastoon_msg .= "<td align='right' valign='top'>".(float) $tulliprosentti." {$tv_nimrow['rahayksikko']}</td>";
    }
  }

  $varastoon_msg .= "<td align='right' valign='top'>".sprintf('%.6f', $ohinta)."</td>
            <td align='right' valign='top'>".sprintf('%.6f', $ohinta*$tilrivirow['varattu']) . " $kuluteksti</td>";

  if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
    $varastoon_msg .= "  <td align='right'></td>";
  }
  else {
    if (!isset($uusikehahin) or $uusikehahin == "") {

      $varastoon_fontti = "";
      if ($kehahin <= 0) {
        $varastoon_fontti = "error";
      }

      $varastoon_msg .= "  <td align='right'>$tuorow[kehahin]<br><font class='$varastoon_fontti'>~$kehahin</font></td>";
    }
    else {
      $varastoon_msg .= "  <td align='right'>$tuorow[kehahin]<br>$uusikehahin</td>";
    }
  }

  if ($toiminto == "kalkyyli" and $tee == "") {

    list($varastoon_saldo, $dummy1, $dummy2, $dummy3) = saldo_myytavissa($tuorow["tuoteno"]);
    $varastoon_saldo_uusi = $varastoon_saldo + $tilrivirow["varattu"];

    $varastoon_fontti = "";
    $varastoon_saldo_viesti = "";

    if ($varastoon_saldo_uusi <= 0) {
      $varastoon_fontti = "error";
      $varastoon_saldo_viesti = "<br>".t("HUOM: Saldo jää negatiiviseksi!");
    }

    $varastoon_msg .= "<td align='right'>$varastoon_saldo<br><font class='$varastoon_fontti'>$varastoon_saldo_uusi$varastoon_saldo_viesti</font></td>";

    if ($yhtiorow['toimipaikkakasittely'] == 'L' and $laskurow['yhtio_toimipaikka'] != 0) {

      $varastoon_msg .= "<td align='right'>";

      $toimipaikan_saldo = 0;

      $query  = "SELECT tunnus
                 FROM varastopaikat
                 WHERE yhtio      = '{$kukarow['yhtio']}'
                 AND tyyppi      != 'P'
                 AND toimipaikka  = '{$laskurow['yhtio_toimipaikka']}'";
      $vares = pupe_query($query);

      while ($varow = mysql_fetch_assoc($vares)) {

        list($varastoon_saldo, $dummy1, $dummy2, $dummy3) = saldo_myytavissa($tuorow["tuoteno"], '', $varow['tunnus']);

        $toimipaikan_saldo += $varastoon_saldo;
      }

      $varastoon_saldo_uusi = $toimipaikan_saldo + $tilrivirow["varattu"];

      $varastoon_fontti = "";
      $varastoon_saldo_viesti = "";

      if ($varastoon_saldo_uusi <= 0) {
        $varastoon_fontti = "error";
        $varastoon_saldo_viesti = "<br>".t("HUOM: Saldo jää negatiiviseksi!");
      }

      $varastoon_msg .= "{$toimipaikan_saldo}<br><font class='{$varastoon_fontti}'>{$varastoon_saldo_uusi}{$varastoon_saldo_viesti}</font>";

      $varastoon_msg .= "</td>";
    }
  }

  $varastoon_msg .= "</tr>";

  if ($mobiili_keikka == "yes") {
    echo "<font class='message'>".t("Vietiin tuotetta")." $tilrivirow[tuoteno] ".t("varastoon").": $tilrivirow[varattu] ".t("kappaletta").".</font><br>";
  }
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<br><br>";
  echo $varastoon_msg;
  echo $jalkilaskenta_debug_text;
}

// jos kyseessä on ihan virallinen varastonarvonlaskenta, tehdään näin
if ($toiminto == "kaikkiok" and $tee == "varma") {

  $rivia = mysql_num_rows($presult); // kaunistelua vaan loppua varten... tää tehdään elsessä affected rowssilla..

  // laitetaan kohdistettu X kun lasku on viety varastoon...
  // laitetaan MAPVM kenttään tämä päivä niin tiedetään koska viety varastoon
  // MAPVM pävittyy jos keikka loppulasketaan uudestaan ja se nolllataan jos/kun keikka avataan (avaa_keikka.php)
  // Päivitetään samalla saapumisotsikolle vaihto-omaisuuslaskun valuutta kuntoon
  $query = "UPDATE lasku set
            kohdistettu   = 'X',
            alatila       = 'X',
            mapvm         = now(),
            vienti_kurssi = '$laskurow[vienti_kurssi]',
            valkoodi      = '$laskurow[valkoodi]',
            maksu_kurssi  = '$laskurow[maksu_kurssi]'
            WHERE tunnus  = '$laskurow[tunnus]'
            and yhtio     = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  // haetaan keikan vaihto-omaisuuslaskut (kotimaa, eu tai ei-eu)
  $query = "SELECT vanhatunnus
            FROM lasku
            WHERE yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and laskunro    = '$laskurow[laskunro]'
            and vanhatunnus <> 0
            and vienti      in ('C','F','I','J','K','L')";
  $result = pupe_query($query);

  // jos on, haetaan liitetyn laskun tiedot
  if (mysql_num_rows($result) >= 1) {

    while ($llrow = mysql_fetch_assoc($result)) {

      //Tämä on ostoreskontran lasku
      $query   = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$llrow[vanhatunnus]'";
      $llres   = pupe_query($query);
      $kulurow = mysql_fetch_assoc($llres);

      // tehdään vielä kirjapitoa...
      // päivitetään tilaus saapuneeksi varastoon kirjanpitoon
      $query = "SELECT tunnus, summa, kustp, kohde, projekti
                FROM tiliointi
                WHERE yhtio  = '$kukarow[yhtio]'
                and ltunnus  = '$kulurow[tunnus]'
                and tilino   = '$yhtiorow[matkalla_olevat]'
                and korjattu = ''";
      $result1 = pupe_query($query);

      if (mysql_num_rows($result1) > 0) { // Tiliointi löytyi
        while ($tiliointirow = mysql_fetch_assoc($result1)) {

          // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
          list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["matkalla_olevat"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

          // Kumotaan, matkallaolevat
          $query = "INSERT into tiliointi set
                    yhtio    = '$kukarow[yhtio]',
                    ltunnus  = '$kulurow[tunnus]',
                    tilino   = '$yhtiorow[matkalla_olevat]',
                    kustp    = '{$kustp_ins}',
                    kohde    = '{$kohde_ins}',
                    projekti = '{$projekti_ins}',
                    tapvm    = now(),
                    summa    = $tiliointirow[summa] * -1,
                    selite   = '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
                    lukko    = '',
                    laatija  = '$kukarow[kuka]',
                    laadittu = now()";
          $result2 = pupe_query($query);

          $varastotili = $yhtiorow["varasto"];

          if ($kulurow["vienti"] == 'J' or $kulurow["vienti"] == 'K' or $kulurow["vienti"] == 'L') {
            $varastotili = $yhtiorow["raaka_ainevarasto"];
          }

          // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
          list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastotili, $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

          // Kirjataan varastonarvo
          $query = "INSERT into tiliointi set
                    yhtio    = '$kukarow[yhtio]',
                    ltunnus  = '$kulurow[tunnus]',
                    tilino   = '$varastotili',
                    kustp    = '{$kustp_ins}',
                    kohde    = '{$kohde_ins}',
                    projekti = '{$projekti_ins}',
                    tapvm    = now(),
                    summa    = '$tiliointirow[summa]',
                    selite   = '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
                    lukko    = '',
                    laatija  = '$kukarow[kuka]',
                    laadittu = now()";
          $result3 = pupe_query($query);
        }
      }
      else {
        if (!$tullaan_automaattikohdistuksesta) echo "<font class='error'>".t("En löytänyt matkallaolevat kirjausta!")." $kulurow[tunnus] $yhtiorow[matkalla_olevat]</font><br>";
      }

      // tarkistetaan onko lasku Teccom-lasku ja onko laskulla asn_sanomat -taulussa rivejä, joita ei ole käsitelty
      if ($kulurow['ebid'] == 'TECCOM-INVOICE') {
        // katotaa onko käsittelemättömiä rivejä
        $query = "SELECT toimittajanro
                  FROM toimi
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$kulurow[liitostunnus]'";
        $toimittajanrores = pupe_query($query);
        $toimittajanrorow = mysql_fetch_assoc($toimittajanrores);

        $query = "SELECT status
                  FROM asn_sanomat
                  WHERE yhtio          = '$kukarow[yhtio]'
                  AND toimittajanumero = '$toimittajanrorow[toimittajanro]'
                  AND asn_numero       = '$kulurow[laskunro]'
                  AND status           = ''";
        $asnsres = pupe_query($query);

        if (mysql_num_rows($asnsres) > 0) {
          $query = "UPDATE asn_sanomat SET
                    status               = 'X'
                    WHERE yhtio          = '$kukarow[yhtio]'
                    AND toimittajanumero = '$toimittajanrorow[toimittajanro]'
                    AND asn_numero       = '$kulurow[laskunro]'
                    AND status           = ''";
          $asnsres = pupe_query($query);
        }
      }
    }
  }
  else {
    if ($yhtiorow['jalkilaskenta_kuluperuste'] != 'VS') {
      echo "<font class='error'>".t("HUOM: Saapumiseen ei ole liitetty yhtään vaihto-omaisuuslaskua!")."<br><br></font>";
    }
  }
}

if ($toiminto == "kalkyyli" and $tee == "") {
  if ($tv_kaytossa) $cspani = 11;
  else $cspani = 10;

  if ($yhtiorow['toimipaikkakasittely'] == 'L') {
    $cspani++;
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $cspani++;
  }

  if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) $cspani += 2;

  echo "<tr><td align='center' class='tumma'><input type='checkbox' name='var' onclick='toggleAll(this);'></td>";

  if ($yhtiorow["varastoonvientipaiva"] == "K") {
    echo "<td valign='top' class='tumma' nowrap>
              <input type='text' name='ppa_kaikki' id='ppa_kaikki' value='' onKeyUp='WriteText(\"ppa\");' size='3'>
              <input type='text' name='kka_kaikki' id='kka_kaikki' value='' onKeyUp='WriteText(\"kka\");' size='3'>
              <input type='text' name='vva_kaikki' id='vva_kaikki' value='' onKeyUp='WriteText(\"vva\");' size='5'></td>";
  }

  echo "<td colspan='$cspani' class='tumma'>&larr; ".t("Ruksaa kaikki");

  if ($yhtiorow["varastoonvientipaiva"] == "K") {
    echo " / ".t("Muuta kaikki päivämäärät");

    echo ". ".t("HUOM").":<img src='../pics/lullacons/alert.png' alt='",t("Huomio"),"' title='",t("HUOM: Riviä ei viedä automaattisesti varastoon."),"' /> = ",t("Riviä ei viedä automaattisesti varastoon");
    echo "<br />",t("HUOM: Jos muutit varastoonvietäviä määriä, muista päivittää rivit ennen varastoonvientiä");
  }
  else {
    echo ". ".t("HUOM").":<img src='../pics/lullacons/alert.png' alt='",t("Huomio"),"' title='",t("HUOM: Riviä ei viedä automaattisesti varastoon."),"' /> = ",t("Riviä ei viedä automaattisesti varastoon");
    echo "<br />",t("HUOM: Jos muutit varastoonvietäviä määriä, muista päivittää rivit ennen varastoonvientiä");
  }

  echo "</td></tr>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<tr>";
}

if ($tee == "") {

  if (!isset($suuntalavan_tunnus)) $suuntalavan_tunnus = "";

  echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

  if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

    if (trim($suuntalavanhyllypaikka) != '') {
      echo "<input type='hidden' name='suuntalavanhyllypaikka' value='{$suuntalavanhyllypaikka}' />";
    }
    else {
      echo "<input type='hidden' name='suuntalavanhyllyalue' value='{$suuntalavanhyllyalue}' />";
      echo "<input type='hidden' name='suuntalavanhyllynro'  value='{$suuntalavanhyllynro}' />";
      echo "<input type='hidden' name='suuntalavanhyllyvali' value='{$suuntalavanhyllyvali}' />";
      echo "<input type='hidden' name='suuntalavanhyllytaso' value='{$suuntalavanhyllytaso}' />";
    }
    echo "<input type='hidden' name='koko_suuntalava' value='X' />";
    echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";
  }

  echo "<td class='back' colspan='2'><input type='submit' value='".t("Päivitä")."'></td>";
}
elseif ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<td class='back' colspan='2'></td>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  if ($tv_kaytossa) $cspani = 7;
  else $cspani = 6;

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $cspani++;
  }

  if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) $cspani += 2;

  echo "<td colspan='$cspani' class='back' align='right'>".t("Yhteensä").":</th><td align='right'>".sprintf("%.2f", $summa)."</td>";
}

if ($tv_kaytossa and $tee == "") {
  echo "<th><input type='submit' value='".t("Päivitä")."'></th>";
}
elseif ($tv_kaytossa and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<th></th>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "</tr>";
  echo "</table></form><br>";
}

// Jos varastoonvienti vaikuttaa tehtyihin valmistuksiin, niin jälkilasketaan myös ne
if ($toiminto == "kaikkiok" and $tee == "varma" and count($korjattavat_valmistukset) > 0) {
  $rekurkoko = count($korjattavat_valmistukset);

  if (!$tullaan_automaattikohdistuksesta) {
    echo "<table>";

    echo "<tr>";
    echo "<th colspan='6'>".t("Korjataan valmistukset joihin tämä varastoonvienti vaikuttaa")."</td>";
    echo "</tr>";
  }


  // Tässä on rekursiivista toimintaa $korjattavat_valmistukset-array voi kasvaa matkan varrella
  for ($korjattavat_valmistukset_ind=0; $korjattavat_valmistukset_ind < $rekurkoko; $korjattavat_valmistukset_ind++) {
    jalkilaske_valmistus($korjattavat_valmistukset[$korjattavat_valmistukset_ind]);

    $rekurkoko = count($korjattavat_valmistukset);
  }

  if (!$tullaan_automaattikohdistuksesta) {
    echo "</table>";
  }
}

if ($toiminto == "kaikkiok" and $tee == "varma" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<br><font class='message'>".t("Vietiin saapuminen")." $laskurow[laskunro] ".t("varastoon")."! ".t("Virallinen varastonarvo laskettu").": $rivia ".t("riviä").".</font><br><br>";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon" and $kauttalaskutus == '') {
  if ($mobiili_keikka != "yes" and !$tullaan_automaattikohdistuksesta) {
    echo "<font class='message'>".t("Vietiin saapuminen")." $laskurow[laskunro] ".t("varastoon").": $rivia ".t("riviä").".</font><br><br>";
  }

  if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

    $query = "SELECT GROUP_CONCAT(saapuminen) keikkatunnus
              FROM suuntalavat_saapuminen
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND suuntalava = '{$suuntalavan_tunnus}'";
    $uusiotunnus_chk_res = pupe_query($query);
    $uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

    # Ei merkata lavaa puretuksi jos viedään rivi kerrallaan
    if ($tilausrivi == 0) {

      // päivitetään suuntalava puretuksi
      $query = "UPDATE tilausrivi
                JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
                JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
                 SET suuntalavat.tila = 'P'
                WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
                AND tilausrivi.uusiotunnus  IN ({$uusiotunnus_chk_row['keikkatunnus']})
                AND tilausrivi.varastoon    = 1
                AND tilausrivi.kpl         != 0
                AND tilausrivi.tyyppi       = 'O'
                AND tilausrivi.suuntalava   = '{$suuntalavan_tunnus}'";
      $varastoon_suuntalava_result = pupe_query($query);
    }

    // AINA EI SIIS OSUTA TÄHÄN, JOS JÄMÄRIVEJÄ EI OLE
    // JOS JÄMÄRIVEJÄ (= SPLITTAANTUNEET) JÄÄ SUUNTALAVALLE
    // JÄMÄRIVEILTÄ päivitetään varastoon-kenttä, jotta tuotteet menevät seuraavalla kerralla varastoon.
    $query = "UPDATE tilausrivi
              JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
               SET tilausrivi.varastoon = 1,
              suuntalavat.tila            = 'S'
              WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
              AND tilausrivi.uusiotunnus  IN ({$uusiotunnus_chk_row['keikkatunnus']})
              AND tilausrivi.varastoon    = 0
              AND tilausrivi.kpl          = 0
              AND tilausrivi.varattu     != 0
              AND tilausrivi.tyyppi       = 'O'
              AND tilausrivi.suuntalava   = '{$suuntalavan_tunnus}'";
    $varastoon_suuntalava_result = pupe_query($query);

    // Jos suuntalava on purettu ja rivi virty normaalisti hyllyyn niin nollataan linkki suuntalavalle
    // jos ollaan viety koko lava purkamatta varastoon niin silloin säilytetään tilausrivin linkki suuntalavalle
    if ($yhtiorow['suuntalavat'] == 'S' and (!isset($koko_suuntalava) or trim($koko_suuntalava) != 'X')) {

      // päivitetään suuntalava puretuksi
      $query = "UPDATE tilausrivi
                JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
                JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
                 SET tilausrivi.suuntalava = 0
                WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
                AND tilausrivi.uusiotunnus  IN ({$uusiotunnus_chk_row['keikkatunnus']})
                AND tilausrivi.varastoon    = 1
                AND tilausrivi.kpl         != 0
                AND tilausrivi.tyyppi       = 'O'
                AND tilausrivi.suuntalava   = '{$suuntalavan_tunnus}'";
      $varastoon_suuntalava_result = pupe_query($query);
    }
  }

  //  Halutaanko toimittaa jälkkäreitä automaattisesti ja saako käyttäjä ylipäätään käsitellä jälkkäreitä..
  $query = "SELECT paivitys
            FROM oikeu
            WHERE yhtio = '$kukarow[yhtio]'
            and kuka    = '$kukarow[kuka]'
            and nimi    = 'tilauskasittely/jtselaus.php'
            and alanimi = ''";
  $jtoikeudetres = pupe_query($query);

  if ((mysql_num_rows($jtoikeudetres) > 0 and $yhtiorow["automaattinen_jt_toimitus"] != "") or
    in_array($yhtiorow["automaattinen_jt_toimitus"], array('J', 'A', 'U'))) {

    $jtoikeudetrow = mysql_fetch_assoc($jtoikeudetres);
    $mista = 'varastoon.inc';
    $varastosta = array();

    foreach ($jtvarastot as $varasto) {
      $v = kuuluukovarastoon($varasto[0], $varasto[1]);

      if ($v > 0 and !in_array($v, $varastosta)) {
        $varastosta[$v] = $v;
      }
    }

    // Jos JT-rivejä saa toimittaa vain, mikäli keräyspäivä on vanhempi kuin tämä päivä
    if ($yhtiorow['automaattinen_jt_toimitus'] == "A") {
      $jt_huomioi_pvm = "X";
    }
    else {
      $jt_huomioi_pvm = "";
    }

    if (in_array($yhtiorow["automaattinen_jt_toimitus"], array('K','T','J','A','S','V','U')) and count($jtrivit) > 0) {
      jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, $jtrivit, $jtrivit_paikat, "vakisin", "JATKA", '', '', $jt_huomioi_pvm, 'TULOUTUS');
    }

    if (in_array($yhtiorow["automaattinen_jt_toimitus"], array('K','T','J','A','U'))) {
      jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, "", "", "tosi_automaaginen", "JATKA", '', '', $jt_huomioi_pvm);
    }

    //  Laitetaan tavarat liikkeelle jos sallitaan!
    if (($jtoikeudetrow["paivitys"] == 1 and in_array($yhtiorow["automaattinen_jt_toimitus"], array('T','S','V'))) or in_array($yhtiorow["automaattinen_jt_toimitus"], array('J', 'A', 'U'))) {
      jt_toimita("", "", "", "", "", "dummy", "TOIMITA", '', '', $jt_huomioi_pvm);
    }
  }
}

if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '' and !$tullaan_automaattikohdistuksesta) {
  echo "<br><hr>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='toiminto' value=''>";
  echo "<input type='hidden' name='toimittajaid' value='$laskurow[liitostunnus]'>";
  echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
  echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
  echo "<input type='submit' value='".t("Takaisin saapumisen valintaan")."'>";
  echo "</form>&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='toiminto' value=''>";
  echo "<input type='hidden' name='toimittajaid' value=''>";
  echo "<input type='hidden' name='ytunnus' value=''>";
  echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
  echo "<input type='submit' value='".t("Takaisin toimittajan valintaan")."'>";
  echo "</form>";
}
