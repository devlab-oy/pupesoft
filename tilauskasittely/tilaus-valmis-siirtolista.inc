<?php

require_once 'tilauskasittely/luo_myyntitilausotsikko.inc';

if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
  $tvs_tila     = "V";
  $tvs_tyyppi    = "'V','L'";
  $tvs_tyyppi_2  = ",'W','M'";
}
elseif ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") {
  $tvs_tila     = "G";
  $tvs_tyyppi    = "'G'";
  $tvs_tyyppi_2  = "";
}
elseif ($toim == "SIIRTOTYOMAARAYS") {
  $tvs_tila     = "S";
  $tvs_tyyppi    = "'G'";
  $tvs_tyyppi_2  = "";
}

if ($yhtiorow["kerataanko_saldottomat"] == '') {
  $lisasalker = " and tuote.ei_saldoa = '' ";
}
else {
  $lisasalker = " ";
}

$ohjausmerkkilisa = '';

// jos kyseess‰ on generoitu siirtolista ja ohjausmerkki on tyhj‰‰, niin pistet‰‰n ohjausmerkiksi
// varastot‰ydennys
if ($laskurow['chn'] == "GEN" and trim($laskurow['ohjausmerkki']) == "") {
  $ohjausmerkkilisa = ", ohjausmerkki = 'Varastot‰ydennys'";
  $laskurow["ohjausmerkki"] = "Varastot‰ydennys";
}

$laskurow['ohjausmerkki'] = paivita_ohjausmerkki($laskurow);

// jos siirtolistan varasto on setattu ja on laaja toimipaikkak‰sittely,
// tutkitaan eroaako varaston toimipaikka tilauksen toimipaikasta. Jos, haetaan $yhtiorow uusiksi.
if ($yhtiorow['toimipaikkakasittely'] == 'L') {
  $_varasto = $laskurow["varasto"];

  if ($_varasto == 0) {
    //haetaan kaikkien tilausrivien varastopaikat
    $query = "SELECT tilausrivi.hyllyalue,
              tilausrivi.hyllynro
              FROM tilausrivi
              WHERE tilausrivi.otunnus  = '$laskurow[tunnus]'
              AND tilausrivi.yhtio      = '$kukarow[yhtio]'
              AND tilausrivi.hyllyalue != ''
              AND tilausrivi.tyyppi    != 'D'
              LIMIT 1";
    $_row = mysql_fetch_assoc(pupe_query($query));

    // Mihin varastoon
    $_varasto = kuuluukovarastoon($_row["hyllyalue"], $_row["hyllynro"]);
  }

  if ($_varasto != 0) {
    $query = "SELECT toimipaikka
              FROM varastopaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = {$_varasto}";
    $_tp_res = pupe_query($query);
    $_tp_row = mysql_fetch_assoc($_tp_res);

    if ($_tp_row['toimipaikka'] != $laskurow['yhtio_toimipaikka']) {
      $kukarow['toimipaikka'] = $_tp_row['toimipaikka'];
      $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
    }
  }
}

// p‰ivitet‰‰n vanhatunnus laskulle
$query  = "UPDATE lasku SET
           vanhatunnus     = tunnus
           $ohjausmerkkilisa
           WHERE yhtio     = '$kukarow[yhtio]'
           AND tunnus      = '$laskurow[tunnus]'
           AND vanhatunnus = 0";
$oldtur = pupe_query($query);

//p‰ivitet‰‰n tilaus valmisaika, kun painetaan ekan kerran tilaus valmiiksi
if ($laskurow['h1time'] == "0000-00-00 00:00:00") {
  $query = "UPDATE lasku
            SET h1time = now(),
            hyvak1      = '$kukarow[kuka]'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$laskurow[tunnus]'";
  $result = pupe_query($query);

  $laskurow['h1time'] = date('Y-m-d H:i:s');
  $laskurow['hyvak1'] = $kukarow["kuka"];
}

//otetaan varmuuden vuoksi alkuper‰isen otsikon tunnus
$alkuplaskutunnus = $laskurow["tunnus"];

// katotaan ihan aluksi, onko t‰m‰ jo ker‰tty...
$query = "SELECT tilausrivi.*
          from tilausrivi use index (yhtio_otunnus)
          join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
          where tilausrivi.yhtio  = '$kukarow[yhtio]'
          and tilausrivi.otunnus  = '$laskurow[tunnus]'
          and tilausrivi.tyyppi  != 'D'
          and tilausrivi.keratty != ''
          $lisasalker";
$keres = pupe_query($query);

// jos yksikin rivi on ker‰tty (l‰hetett‰ ei tulosteta uudestaan)...
// tai jos ollaan ruksattu suoraan valmiiksi (sis‰iset tyˆm‰‰r‰ykset)
if (mysql_num_rows($keres) > 0 or $laskurow["eilahetetta"] != '') {

  //...niin p‰ivitet‰‰n ker‰‰m‰ttˆm‰t rivit ker‰tyksi aktiiviselle k‰ytt‰j‰lle
  $query = "UPDATE tilausrivi use index (yhtio_otunnus)
            SET keratty = '$kukarow[kuka]', kerattyaika=now()
            where yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'
            and keratty = ''
            and var     not in ('P','J','O','S')";
  $result = pupe_query($query);

  if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
    echo "<font class='message'>".t("Valmistuksella oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, valmistusnumero on")." $laskurow[tunnus].</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Siirtolistalla oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, siirtolistanumero on")." $laskurow[tunnus].</font><br><br>";
  }

  if ($laskurow["eilahetetta"] != '') {
    $query  = "UPDATE lasku
               set alatila = 'C'
               where yhtio = '$kukarow[yhtio]'
               and tunnus  = '$laskurow[tunnus]'";
    $varresult = pupe_query($query);
  }
}
else {

  //laitetaan lista tulostusjonoon
  if ($tulostetaan != 'OK') {
    ///* Ensin on pakko splitata l‰hete varastoittain *///
    //tyhjennet‰‰n laskun varastokentt‰, siell‰ oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon l‰hete kuuluu laittaa
    $laskurow['varasto'] = 0;

    //Toimitustavan hetier‰ ja vaihtoehtoinen vak toimitustapa tarvitaan
    $query = "SELECT tulostustapa,
              vaihtoehtoinen_vak_toimitustapa
              FROM toimitustapa
              WHERE yhtio='{$kukarow["yhtio"]}'
              AND selite='{$laskurow["toimitustapa"]}'";
    $result = pupe_query($query);
    $totarowx = mysql_fetch_array($result);

    // Maksuehdon jv tieto tarvitaan
    $query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $result = pupe_query($query);
    $maehrowx = mysql_fetch_array($result);

    if ($yhtiorow['kerayserat'] == 'K') {
      $kerayserat_select = " vh.keraysvyohyke, ";
      $kerayserat_join = "LEFT JOIN varaston_hyllypaikat AS vh
                          ON (vh.yhtio = tilausrivi.yhtio
                          AND vh.hyllyalue = tilausrivi.hyllyalue
                          AND vh.hyllynro = tilausrivi.hyllynro
                          AND vh.hyllyvali = tilausrivi.hyllyvali
                          AND vh.hyllytaso = tilausrivi.hyllytaso) ";
      $sorttaus = " vh.keraysvyohyke, sorttauskentta ";
    }
    else {
      $kerayserat_select = "";
      $kerayserat_join = "";
      $sorttaus = " sorttauskentta ";
    }

    $vakit_eri_tilaukselle = $totarowx["vaihtoehtoinen_vak_toimitustapa"] != "";

    if ($vakit_eri_tilaukselle) {
      $tuote_select = "tuote.vakkoodi AS vakkoodi,";
      $tuote_join = "JOIN tuote ON (tuote.tuoteno = tilausrivi.tuoteno
                         AND tuote.yhtio = tilausrivi.yhtio)";
      $sorttaus = "tuote.vakkoodi, {$sorttaus}";
    }
    else {
      $tuote_select = "";
      $tuote_join = "";
    }

    //haetaan kaikkien tilausrivien varastopaikat
    $query = "SELECT tilausrivi.otunnus,
              tilausrivi.tunnus,
              tilausrivi.hyllyalue,
              tilausrivi.hyllynro,
              tilausrivi.hyllyvali,
              tilausrivi.hyllytaso,
              $kerayserat_select
              {$tuote_select}
              concat( rpad(upper(tilausrivi.hyllyalue), 5, '0'),
                      lpad(upper(tilausrivi.hyllynro), 5, '0'),
                      lpad(upper(tilausrivi.hyllyvali), 5, '0'),
                      lpad(upper(tilausrivi.hyllytaso), 5, '0')
              ) sorttauskentta
              FROM tilausrivi
              $kerayserat_join
              {$tuote_join}
              WHERE tilausrivi.otunnus  = '$laskurow[tunnus]'
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.hyllyalue != ''
              and tilausrivi.tyyppi    != 'D'
              ORDER BY $sorttaus";
    $result = pupe_query($query);

    //t‰ss‰ muuttujassa on sit kaikki tunnukset splitin j‰lkeen
    $tvs_tilausnumerot   = array();
    $tvs_tilausnumerot[] = $laskurow["tunnus"];

    //mihin varastoon l‰hete kuuluu. aluksi x
    $varasto  = $edvarasto = $tulostusalue = $edtulostusalue = $keraysvyohyke = $edkeraysvyohyke = "x";
    unset($laskusplitres);

    if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and is_string($toimitustavan_lahto)) {
      $toimitustavan_lahto = unserialize(urldecode($toimitustavan_lahto));
    }

    $splitattu_vakiksi = false;

    while ($row = mysql_fetch_assoc($result)) {

      // Mihin varastoon
      $varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

      if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and isset($toimitustavan_lahto[$varasto]) and $toimitustavan_lahto[$varasto] > 0) {
        $toimitustavan_lahto_arvo = $toimitustavan_lahto[$varasto];
      }
      else {
        $toimitustavan_lahto_arvo = 0;
      }
      if ($yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
        $varasto_row = hae_varasto($varasto);
        $varasto_toimipaikka = $varasto_row['toimipaikka'];
      }
      else {
        $varasto_toimipaikka = $laskurow['yhtio_toimipaikka'];
      }

      // Mik‰ tulostusalue
      $tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

      if ($yhtiorow['kerayserat'] == 'K') {
        // Mik‰ ker‰ysvyˆhyke
        $keraysvyohyke = $row['keraysvyohyke'];
      }

      // Intrastat-tiedot
      $laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

      //varasto vaihtuu, splitataan tilaus, paitsi jos spliauskielto on trigattu
      if  (
        (
          (
            $varasto != $edvarasto
            and $edvarasto != 'x'
            and $edvarasto != ''
          )
          or (
            $toim == "SIIRTOLISTA"
            and $tulostusalue != $edtulostusalue
            and $edtulostusalue != 'x'
            and $edtulostusalue != ''
          )
          or (
            $keraysvyohyke != $edkeraysvyohyke
            and $edkeraysvyohyke != 'x'
            and $edkeraysvyohyke != ''
          )
        )
        and (
          $maehrowx["jv"] == ''
          or $totarowx["tulostustapa"] != 'H'
        )
        and (   $laskurow["splittauskielto"] != "E"
          and $toim != "SIIRTOTYOMAARAYS"
          and $toim != "VALMISTAASIAKKAALLE"
          and $toim != "VALMISTAVARASTOON"
        ) or ($vakit_eri_tilaukselle and $row["vakkoodi"] != "" and !$splitattu_vakiksi)
      ) {
        //Koska ollaan t‰‰ll‰‰ olaan juuri splittaamassa tilaus, eli halutaan tulostaa kaikkien splittauksessa syntyvien varastosiirojen siirtolistat
        $splitattiin = TRUE;

        if (!is_resource($laskusplitres)) {
          // Jotta saadaan lasku kopsattua kivasti jos se splittaantuu
          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$laskurow[tunnus]'";
          $laskusplitres = pupe_query($query);
        }

        $fields = "yhtio";
        $values = "'$kukarow[yhtio]'";

        if ($vakit_eri_tilaukselle and $row["vakkoodi"] != "") {
          $splitattu_vakiksi = true;
          $split_toimitustapa = $totarowx["vaihtoehtoinen_vak_toimitustapa"];
        }

        // Ei monisteta tunnusta
        for ($i=1; $i < mysql_num_fields($laskusplitres)-1; $i++) {

          $fieldname = mysql_field_name($laskusplitres, $i);

          $fields .= ", ".$fieldname;

          switch ($fieldname) {
          case 'vanhatunnus':
            $values .= ", '$alkuplaskutunnus'";
            break;
          case 'yhtio_toimipaikka':
            $values .= ", '$varasto_toimipaikka'";
            break;
          case 'varasto':
            $values .= ", '$varasto'";
            break;
          case 'tulostusalue':
            $values .= ", '$tulostusalue'";
            break;
          case 'toimitustavan_lahto':
            $values .= ", '$toimitustavan_lahto_arvo'";
            break;
          case 'toimitustapa':
            if (isset($split_toimitustapa)) {
              $values .= ", '$split_toimitustapa'";
            }
            else {
              $values .= ", '" . $laskurow[$fieldname] . "'";
            }
            break;
          case 'kate_korjattu':
          case 'lahetetty_ulkoiseen_varastoon':
            $values .= ", NULL";
            break;
          default:
            $values .= ", '".$laskurow[$fieldname]."'";
          }
        }

        $kysely  = "INSERT into lasku ($fields) VALUES ($values)";
        $uusires = pupe_query($kysely);

        $laskurow['tunnus'] = (string) mysql_insert_id($GLOBALS["masterlink"]);
        $tvs_tilausnumerot[] = $laskurow["tunnus"];

        $query = "SELECT *
                  FROM laskun_lisatiedot
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$laskurow[tunnus]'";
        $lisatiedot_result = pupe_query($query);

        if (mysql_num_rows($lisatiedot_result) == 1) {

          $lisatiedot_row = mysql_fetch_array($lisatiedot_result);

          $fields = "yhtio";
          $values = "'$kukarow[yhtio]'";

          // Ei monisteta tunnusta
          for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

            $fieldname = mysql_field_name($lisatiedot_result, $i);

            $fields .= ", ".$fieldname;

            switch ($fieldname) {
            case 'otunnus':
              $values .= ", '$laskurow[tunnus]'";
              break;
            default:
              $values .= ", '".$lisatiedot_row[$fieldname]."'";
            }
          }

          $query = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
          $lisatiedot_result = pupe_query($query);
        }
      }
      else {
        //p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
        $query  ="UPDATE lasku SET
                  varasto                          = '$varasto',
                  tulostusalue                     = '$tulostusalue',
                  toimitustavan_lahto              = '$toimitustavan_lahto_arvo',
                  yhtio_toimipaikka                = '$varasto_toimipaikka',
                  ultilno                          = '$laskurow[ultilno]',
                  maa_maara                        = '$laskurow[maa_maara]',
                  maa_lahetys                      = '$laskurow[maa_lahetys]',
                  kauppatapahtuman_luonne          = '$laskurow[kauppatapahtuman_luonne]',
                  kuljetusmuoto                    = '$laskurow[kuljetusmuoto]',
                  sisamaan_kuljetus                = '$laskurow[sisamaan_kuljetus]',
                  sisamaan_kuljetusmuoto           = '$laskurow[sisamaan_kuljetusmuoto]',
                  sisamaan_kuljetus_kansallisuus   = '$laskurow[sisamaan_kuljetus_kansallisuus]',
                  kontti                           = '$laskurow[kontti]',
                  aktiivinen_kuljetus              = '$laskurow[aktiivinen_kuljetus]',
                  aktiivinen_kuljetus_kansallisuus = '$laskurow[aktiivinen_kuljetus_kansallisuus]',
                  poistumistoimipaikka             = '$laskurow[poistumistoimipaikka]',
                  poistumistoimipaikka_koodi       = '$laskurow[poistumistoimipaikka_koodi]',
                  bruttopaino                      = '$laskurow[bruttopaino]',
                  lisattava_era                    = '$laskurow[lisattava_era]',
                  vahennettava_era                 = '$laskurow[vahennettava_era]'
                  where yhtio                      = '$kukarow[yhtio]'
                  and tunnus                       = '$laskurow[tunnus]'";
        $varresult = pupe_query($query);

        $laskurow["varasto"] = $varasto;
      }

      //edellinen varasto talteen
      $edvarasto = $varasto;

      // edellinen tulostusalue talteen
      $edtulostusalue = $tulostusalue;

      // edellinen keraysvyohyke
      $edkeraysvyohyke = $keraysvyohyke;

      //jos tilausrivin laskunumero on eri
      if ($row['otunnus'] != $laskurow['tunnus']) {
        $query = "UPDATE tilausrivi set otunnus='$laskurow[tunnus]' where tunnus='$row[tunnus]' and yhtio='$kukarow[yhtio]'";
        $ohno  = pupe_query($query);
      }
    }

    $query = "SELECT tunnus, sisviesti2 from lasku where yhtio = '$kukarow[yhtio]' and vanhatunnus = '$alkuplaskutunnus' and alatila = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 1) {
      $tilauksia = 1;
      $montakotilausta = mysql_num_rows($result);
      $numerot = implode(', ', $tvs_tilausnumerot);

      $splitatut = array();
      while ($row = mysql_fetch_array($result)) {
        $upviesti = $row["sisviesti2"]." ".t("Tilaus jakaantunut")." $montakotilausta ".t("tilaukseksi").": ".$numerot." ".t("Tilaus").": ".$tilauksia."/".$montakotilausta;
        $query = "UPDATE lasku set sisviesti2 = '$upviesti' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]'";
        $upresult = pupe_query($query);
        $splitatut[] = $row['tunnus'];
        $tilauksia++;
      }
    }
    else {
      $splitatut = NULL;
    }

    // tyhjennet‰‰n muuttujat
    $varasto   = '';
    $edvarasto = '';

    //jos ei splitattu, laitetaan $splitattuihin luodun laskun tunnus p‰ivityst‰ varten
    if (!isset($splitatut)) {
      $splitatut = array($laskurow['tunnus']);
    }

    if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
      foreach ($splitatut as $tunnus) {
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tunnus}'
                  AND (toimitustavan_lahto = 0 OR chn = 'GEN')";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 1) {
          $lahtorow = mysql_fetch_assoc($result);

          // Chn on GEN, eli generoitu siirtolista, siirret‰‰n aina yhden p‰iv‰n eteenp‰in
          $eteenpain = ($lahtorow["chn"] == "GEN") ? 1 : 0;

          $lahdot = seuraavat_lahtoajat($lahtorow["toimitustapa"], $lahtorow["prioriteettinro"], $lahtorow["varasto"], 0, $eteenpain);

          if ($lahdot !== FALSE) {

            // Otetaan eka l‰htˆ
            $valittu_lahto = array_shift($lahdot);

            $query = "UPDATE lasku
                      SET toimitustavan_lahto = '{$valittu_lahto['tunnus']}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$tunnus}'";
            pupe_query($query);
          }
        }
      }
    }

    // loopataan kaikki yhdest‰ tilauksesta syntyneet l‰hetteet
    foreach ($tvs_tilausnumerot as $tvs_tilausnumero) {

      //luetaan laskun tiedot uudestaan t‰ss‰, per splitti
      $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tvs_tilausnumero'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_array($result);

      // T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰
      $query = "SELECT tuote.tuoteno
                FROM tilausrivi, tuote
                WHERE otunnus         = '$laskurow[tunnus]'
                and var               in ('','H')
                and (varattu > 0 or tilkpl = 9999.99)
                $lisasalker
                and tuote.yhtio       = '$kukarow[yhtio]'
                and tilausrivi.tyyppi in ($tvs_tyyppi$tvs_tyyppi_2)
                and tuote.tuoteno     = tilausrivi.tuoteno
                and tilausrivi.yhtio  = tuote.yhtio";
      $cresult = pupe_query($query);

      if (mysql_num_rows($cresult) == 0) {
        // Mit‰‰n ker‰tt‰v‰‰ ei ooo, mutta jt:t‰ voi olla
        $query = "SELECT count(*) riveja, sum(if(var='J', 1, 0)) jteet
                  FROM tilausrivi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  in ($tvs_tyyppi)
                  and otunnus = '$laskurow[tunnus]'";
        $tresult = pupe_query($query);
        $rivirow = mysql_fetch_array($tresult);

        //Tilauskella on pelk‰st‰‰n jt-rivej‰
        if ($rivirow["riveja"] > 0 and $rivirow["jteet"] > 0) {
          //p‰ivtet‰‰n tilaus tilaan 'odottaa jt-rivej‰'
          $query = "UPDATE lasku
                    SET tila='$tvs_tila', alatila='T'
                    WHERE yhtio='$kukarow[yhtio]'
                    AND tunnus='$laskurow[tunnus]'";
          $yresult = pupe_query($query);
          echo "<font class='message'>";
          echo t("Siirtolista siirrettiin j‰lkitoimitukseksi odottamaan tuotteita") . ' ';
          echo $laskurow['tunnus'];
          echo "</font><br /><br />";
        }
        else {
          // Mit‰‰n j‰rkevi‰ rivi‰ ei ollut, mit‰tˆid‰‰n lista
          $query = "UPDATE tilausrivi
                    SET tyyppi='D'
                    WHERE yhtio='$kukarow[yhtio]'
                    AND otunnus='$kukarow[kesken]'";
          $result = pupe_query($query);

          $kommentti = $kukarow['nimi'] . " " . "(" . $kukarow['kuka'] . ")" . " ";
          $kommentti .= t("mit‰tˆi siirtolistan") . " " . date("d.m.y @ G:i:s");
          $query = "UPDATE lasku
                    SET tila='D', alatila='$laskurow[tila]', comments='$kommentti'
                    WHERE yhtio='$kukarow[yhtio]'
                    AND tunnus='$kukarow[kesken]'";
          $result = pupe_query($query);
          echo "<font class='message'>";
          echo t("Siirtolista mit‰tˆitiin, koska sill‰ ei ollut mit‰‰n ker‰tt‰v‰‰") . " ";
          echo $laskurow['tunnus'];
          echo "</font><br /><br />";
        }
        if (($laskutunnus = array_search($laskurow['tunnus'], $splitatut)) !== false) {
          unset($splitatut[$laskutunnus]);
        }
      }
      else {
        // Jos yhtiˆn parametreiss‰ on ker‰yser‰t A tarkistetaan asiakkaan takaa
        if ($yhtiorow['kerayserat'] == 'A') {
          // Haetaan kerayseratieto asiakkaan takaa
          $query = "SELECT kerayserat
                    FROM asiakas
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$laskurow['liitostunnus']}'";
          $asrow = mysql_fetch_assoc(pupe_query($query));
        }

        // Poistetaan vanhat ker‰yser‰t kun palautetaan tilaus takaisin tulostusjonoon,
        // jottei ker‰yseri‰ luotaisi joka kerta vaan lis‰‰ ja lis‰‰,
        // kun palautetaan tilaus takaisin tulostusjonoon
        if ($yhtiorow['kerayserat'] == 'K'
          or $yhtiorow['kerayserat'] == 'P'
          or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A')) {

          $query = "DELETE FROM kerayserat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND otunnus = '{$laskurow['tunnus']}'";
          pupe_query($query);
        }

        //Laitetaan lista tulostusjonoon
        $query = "UPDATE lasku
                  SET alatila = 'J'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$laskurow[tunnus]'
                  and tila    = '$tvs_tila'";
        $result = pupe_query($query);

        if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
          echo "<font class='message'>";
          echo t("Valmistus siirretty tulostusjonoon") . " " . $laskurow['tunnus'];
          echo "</font><br><br>";

          if ($toim == "VALMISTAASIAKKAALLE" and trim($laskurow['tilausvahvistus']) != "") {
            //
            // LƒHETETƒƒN TILAUSVAHVISTUS
            //
            // tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
            $params_tilausvahvistus = array(
              'tee'            => $tee,
              'toim'            => $toim,
              'kieli'            => $kieli,
              'komento'          => $komento,
              'laskurow'          => $laskurow,
              'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
              'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
            );

            laheta_tilausvahvistus($params_tilausvahvistus);
          }
        }
        elseif ($yhtiorow["varastosiirto_tilausvahvistus"] == "K") {

          // pakotetaan tilausvanvistus PDF muodossa omaan s‰hkˆpostiin, ilman hintoja.
          $laskurow["tilausvahvistus"] = "3OP";

          // LƒHETETƒƒN TILAUSVAHVISTUS
          $params_tilausvahvistus = array(
            'tee'            => $tee,
            'toim'            => $toim,
            'kieli'            => $kieli,
            'komento'          => $komento,
            'laskurow'          => $laskurow,
            'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
            'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
          );

          laheta_tilausvahvistus($params_tilausvahvistus);

          echo "<font class='message'>";
          echo t("Siirtolista siirretty tulostusjonoon") . " " . $laskurow['tunnus'];
          echo "<br /><br />";
        }
      }
    }

    // tilaus ei en‰‰ kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);

    // L‰hetteet tulostetaan automaattisesti
    if (   ($toim == "MYYNTITILI" and $yhtiorow["myyntitilin_tulostustapa"] == "A")
      or ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow["valmistuksen_tulostustapa"] == "A")
      or ($toim == "VALMISTAVARASTOON" and $yhtiorow["valmistuksen_tulostustapa"] == "A")
      or ($toim == "SIIRTOLISTA" and $yhtiorow["siirtolistan_tulostustapa"] == "A")
    ) {
      //TODO: Otetaan t‰ss‰ vain alkuper‰inen tilaus ja tulostetaan se.. Jos tilaus splittaantui, muut j‰‰v‰t ker‰yslistajonoon.
      $query = "SELECT * FROM lasku where yhtio = '{$kukarow["yhtio"]}' and tunnus = '$alkuplaskutunnus'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      $tee = "valmis";
      $tulostetaan = "OK";
    }
  }

  if ($tulostetaan == "OK") {

    //Haetaan uusien jaon yhteydess‰ syntyneiden tilausten tiedot, jotta saadaan tulostettu siirtolistat
    if (isset($splitattiin) and $splitattiin) {
      $query = "SELECT *
                FROM lasku
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND vanhatunnus = '{$laskurow['tunnus']}'";
      $hresult = pupe_query($query);
    }
    //Haetaan yksitt‰isen tilausrivin tiedot uudestaan, jotta yksitt‰isen siirtolistan tulostus tukee myˆs useamman tapausta (siirtolistan jakotapaus)
    else {
      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      $hresult = pupe_query($query);
    }

    while ($laskurow = mysql_fetch_array($hresult)) {
      //Tulostetaan lista
      $query = "SELECT tuote.tuoteno, tilausrivi.*
                FROM tilausrivi use index (yhtio_otunnus), tuote use index (tuoteno_index)
                WHERE otunnus         = '$laskurow[tunnus]'
                $lisasalker
                and tuote.yhtio       = '$kukarow[yhtio]'
                and tilausrivi.tyyppi in ($tvs_tyyppi)
                and tilausrivi.var    in ('','H')
                and tuote.tuoteno     = tilausrivi.tuoteno
                and tilausrivi.yhtio  = tuote.yhtio";
      $cresult = pupe_query($query);

      if (mysql_num_rows($cresult) > 0) {
        //ker‰tt‰v‰‰ on

        //katotaan speciaalirivit joilla tilkpl = 999999999 and varattu = 0
        //t‰llˆin halutaan siirt‰‰ kaikki mit‰ hyllyss‰ on t‰ll‰ hetkell‰
        while ($crow = mysql_fetch_array($cresult)) {
          if ($crow['varattu'] == 0 and $crow['tilkpl'] == 9999.99) {
            //siirret‰‰n kaikki mit‰ paikalla on

            $vapaana = 0;

            $query = "SELECT saldo
                      from tuotepaikat
                      where tuoteno = '$crow[tuoteno]'
                      and yhtio     = '$kukarow[yhtio]'
                      and hyllyalue = '$crow[hyllyalue]'
                      and hyllynro  = '$crow[hyllynro]'
                      and hyllyvali = '$crow[hyllyvali]'
                      and hyllytaso = '$crow[hyllytaso]'";
            $alkuresult = pupe_query($query);
            $alkurow = mysql_fetch_array($alkuresult);

            //ennakkopoistot
            $query = "SELECT sum(varattu) keratty
                      FROM tilausrivi
                      WHERE tyyppi  in ('L','G','V')
                      and yhtio     = '$kukarow[yhtio]'
                      and tuoteno   = '$crow[tuoteno]'
                      and varattu   > 0
                      and hyllyalue = '$crow[hyllyalue]'
                      and hyllynro  = '$crow[hyllynro]'
                      and hyllyvali = '$crow[hyllyvali]'
                      and hyllytaso = '$crow[hyllytaso]'";
            $varatutresult = pupe_query($query);
            $varatutrow = mysql_fetch_array($varatutresult);

            $vapaana = $alkurow["saldo"] - $varatutrow["keratty"];

            if ($vapaana <= 0) {
              $vapaana = 0;
            }

            $query = "UPDATE tilausrivi set tilkpl='$vapaana', varattu='$vapaana'
                      where tunnus='$crow[tunnus]'
                      and yhtio='$kukarow[yhtio]'";
            $res = pupe_query($query);
          }
        }

        $laskurow['pakkaamo'] = pakkaamo($laskurow["tunnus"], "yes", $ei_pakkaamoa);

        if ($yhtiorow['tulosta_valmistus_tulosteet'] == '') {
          // Tulostetaan ker‰yslista
          require_once "tulosta_lahete_kerayslista.inc";

          // Haetaan asiakkaan tiedot
          $query = "SELECT luokka,
                    puhelin,
                    if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro,
                    asiakasnro as asiakasnro_aito,
                    kerayserat,
                    kieli,
                    asiakasviivakoodi
                    FROM asiakas
                    WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
          $riresult = pupe_query($query);
          $asrow = mysql_fetch_array($riresult);

          $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);

          if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
          else $pjat_sortlisa = "";

          // Ker‰yslistan rivit
          if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

            $sorttauskentta = str_replace("as sorttauskentta", "", $sorttauskentta);

            $querysel = "  tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys,
                    sum(tilausrivi.tilkpl) tilkpl,
                    sum(tilausrivi.varattu) varattu,
                    sum(tilausrivi.jt) jt,
                    group_concat(tilausrivi.tunnus) tunnus,
                    min($sorttauskentta) as sorttauskentta, ";

            $lisa = "  and tilausrivi.tyyppi in ($tvs_tyyppi)
                  GROUP BY tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys, tuotetyyppi, myyntihinta_maara, tuote.sarjanumeroseuranta";
          }
          else {
            $querysel = " tilausrivi.*, $sorttauskentta,";
            $lisa = " ";
          }

          $query = "SELECT
                    $querysel
                    if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi,
                    if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                    tuote.sarjanumeroseuranta,
                    tuote.eankoodi
                    FROM tilausrivi use index (yhtio_otunnus)
                    JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                    WHERE tilausrivi.otunnus  = '$laskurow[tunnus]'
                    and tilausrivi.yhtio      = '$kukarow[yhtio]'
                    and tilausrivi.var        in ('','H')
                    and tilausrivi.tyyppi    != 'D'
                    $lisasalker
                    $lisa
                    ORDER BY $pjat_sortlisa sorttauskentta $yhtiorow[kerayslistan_jarjestys_suunta], tilausrivi.tunnus";
          $riresult = pupe_query($query);

          $tilausnumeroita = $laskurow["tunnus"];

          //generoidaan rivinumerot
          $rivinumerot = array();

          $kal = 1;

          while ($row = mysql_fetch_array($riresult)) {
            $rivinumerot[$row["tunnus"]] = $kal;
            $kal++;
          }

          mysql_data_seek($riresult, 0);

          if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
            $tyyppi = "";
          }
          elseif ($toim == "MYYNTITILI") {
            $tyyppi = "MYYNTITILI";
          }
          else {
            $tyyppi = "SIIRTOLISTA";
          }

          $params_kerayslista = array(
            'asrow'             => $asrow,
            'boldi'             => $boldi,
            'iso'               => $iso,
            'kala'              => 0,
            'kieli'             => $kieli,
            'komento'      => '',
            'laskurow'          => $laskurow,
            'norm'              => $norm,
            'page'              => '',
            'paino'             => 0,
            'pdf'               => NULL,
            'perheid'           => 0,
            'perheid2'          => 0,
            'pieni'             => $pieni,
            'pieni_boldi'       => $pieni_boldi,
            'rectparam'         => $rectparam,
            'rivinkorkeus'      => $rivinkorkeus,
            'rivinumerot'      => $rivinumerot,
            'row'               => NULL,
            'sivu'              => 1,
            'tee'               => $tee,
            'thispage'      => NULL,
            'tilausnumeroita'   => $tilausnumeroita,
            'toim'              => $toim,
            'tots'              => 0,
            'tyyppi'        => $tyyppi,
            'kerayslistatyyppi'  => '');

          // Aloitellaan ker‰yslistan teko
          $params_kerayslista = alku_kerayslista($params_kerayslista);

          while ($row = mysql_fetch_assoc($riresult)) {
            $params_kerayslista["row"] = $row;
            $params_kerayslista = rivi_kerayslista($params_kerayslista);
          }

          $params_kerayslista["tots"] = 1;
          $params_kerayslista = loppu_kerayslista($params_kerayslista);

          //tulostetaan failit ja valitaan sopivat printterit
          if ($laskurow["varasto"] == '') {
            $query = "SELECT *
                      FROM varastopaikat
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND tyyppi  != 'P'
                      ORDER BY alkuhyllyalue,alkuhyllynro
                      LIMIT 1";
          }
          else {
            $query = "SELECT *
                      FROM varastopaikat
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$laskurow[varasto]'
                      ORDER BY alkuhyllyalue,alkuhyllynro";
          }
          $prires = pupe_query($query);

          if (mysql_num_rows($prires) > 0) {
            $prirow = mysql_fetch_array($prires);

            if (!isset($valittu_tulostin) or $valittu_tulostin == "oletukselle") {
              $apuprintteri = $prirow['printteri0']; // ker‰yslistaprintteri
            }
            else {
              $apuprintteri = $valittu_tulostin;
            }

            //haetaan l‰hetteen tulostuskomento
            $query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
            $kirres= pupe_query($query);
            $kirrow= mysql_fetch_array($kirres);
            $komento=$kirrow['komento'];
          }

          if ($valittu_tulostin == "-88") {
            $komento = "-88";
          }

          //tulostetaan sivu
          $params_kerayslista["komento"] = $komento;
          print_pdf_kerayslista($params_kerayslista);
        }

        // jos halutaan MY÷S l‰hete
        if ($yhtiorow["lahetteen_tulostustapa"] == "L") {
          $params = array(
            'laskurow'          => $laskurow,
            'sellahetetyyppi'       => "",
            'extranet_tilausvahvistus'   => "",
            'naytetaanko_rivihinta'    => "",
            'tee'            => "",
            'toim'            => $toim,
            'komento'           => $komento,
            'lahetekpl'          => "",
            'kieli'           => ""
          );

          pupesoft_tulosta_lahete($params);
        }

        if ($returnvalue != 0) {
          echo "<font class='error'>$komento".t("Ker‰yslistan tulostus ep‰onnistui")."! ".t("Tilaus $laskurow[tunnus]")." ".t("siirrettiin tulostusjonoon").".
              ".t("K‰y tulostamassa l‰hete")." <a href='lahetteen_tulostusjono.php'>".t("tulostusjonosta")."</a>.</font><br>";
          $virheellinen = "X"; //Merkataan ep‰onnistuneeksi
        }

        if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

          // katotaan miten halutaan sortattavan
          // haetaan asiakkaan tietojen takaa sorttaustiedot
          $order_sorttaus = '';

          $asiakas_apu_query = "SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
                                FROM asiakas
                                WHERE yhtio = '$kukarow[yhtio]'
                                and tunnus  = '$laskurow[liitostunnus]'";
          $asiakas_apu_res = pupe_query($asiakas_apu_query);

          if (mysql_num_rows($asiakas_apu_res) == 1) {
            $asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
            $sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
            $order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
          }
          else {
            $sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
            $order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
          }

          if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
          else $pjat_sortlisa = "";

          $query = "SELECT tilausrivi.*,
                    $sorttauskentta,
                    if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi,
                    tuote.valmistuslinja,
                    tuote.sarjanumeroseuranta
                    FROM tilausrivi use index (yhtio_otunnus)
                    JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                    WHERE tilausrivi.otunnus  = '$laskurow[tunnus]'
                    and tilausrivi.yhtio      = '$kukarow[yhtio]'
                    and tilausrivi.var        in ('','H')
                    and tilausrivi.tyyppi    != 'D'
                    ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, if(tilausrivi.tyyppi = 'V', '2', '1'), tilausrivi.tunnus";
          $result = pupe_query($query);

          require_once "tulosta_valmistus.inc";

          $tilausnumeroita = $laskurow["tunnus"];

          //generoidaan rivinumerot
          $rivinumerot = array();

          $kal = 1;

          while ($row = mysql_fetch_array($result)) {
            $rivinumerot[$row["tunnus"]] = $kal;
            $kal++;
          }

          mysql_data_seek($result, 0);

          unset($pdf);
          unset($page);

          $sivu  = 1;
          $paino = 0;
          $_rivityyppi = "";

          // Aloitellaan l‰hetteen teko
          $page[$sivu] = alku_valm();

          while ($row = mysql_fetch_assoc($result)) {
            $row['ed_tyyppi'] = $_rivityyppi;

            rivi_valm($page[$sivu]);

            $_rivityyppi = $row['tyyppi'];
          }

          loppu_valm($page[$sivu], 1);

          //tulostetaan failit ja valitaan sopivat printterit
          if ($laskurow["varasto"] == '') {
            $query = "SELECT *
                      FROM varastopaikat
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND tyyppi  != 'P'
                      ORDER BY alkuhyllyalue,alkuhyllynro
                      LIMIT 1";
          }
          else {
            $query = "SELECT *
                      FROM varastopaikat
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$laskurow[varasto]'
                      ORDER BY alkuhyllyalue,alkuhyllynro";
          }
          $prires = pupe_query($query);

          if (mysql_num_rows($prires) > 0) {
            $prirow = mysql_fetch_array($prires);

            if (!isset($valittu_tulostin) or $valittu_tulostin == "oletukselle") {
              $apuprintteri = $prirow['printteri0']; // ker‰yslistaprintteri
            }
            else {
              $apuprintteri = $valittu_tulostin;
            }

            //haetaan l‰hetteen tulostuskomento
            $query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
            $kirres= pupe_query($query);
            $kirrow= mysql_fetch_array($kirres);
            $komento=$kirrow['komento'];
          }

          print_pdf_valm($komento);
        }

        // jos meill‰ oli l‰hetteen tulostusongelmia
        if ($virheellinen == 'X') {
          // merkataan l‰hete tulostusjonoon
          $query  ="UPDATE lasku set
                    alatila='J'
                    where yhtio='$kukarow[yhtio]'
                    and tunnus = '$laskurow[tunnus]'
                    and tila='$tvs_tila'
                    and alatila=''";
          $result = pupe_query($query);
        }
        else {
          if ($yhtiorow["kerataanko_valmistukset"] == "E" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON")) {
            $query = "UPDATE lasku
                      SET alatila='C', lahetepvm=now()
                      where yhtio='$kukarow[yhtio]'
                      and tunnus  = '$laskurow[tunnus]'
                      and tila='$tvs_tila'
                      and alatila in ('','J')";
            $result = pupe_query($query);
          }
          else {
            $query = "UPDATE lasku
                      SET alatila='A', lahetepvm=now()
                      where yhtio='$kukarow[yhtio]'
                      and tunnus  = '$laskurow[tunnus]'
                      and tila='$tvs_tila'
                      and alatila in ('','J')";
            $result = pupe_query($query);
          }
        }
      }
      else {
        if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
          echo t("Valmistuksella ei ollut mit‰‰n ker‰tt‰v‰‰")."!<br><br>";
          $eikeralatila = "C";
        }
        else {
          echo t("Siirtolistalla ei ollut mit‰‰n ker‰tt‰v‰‰")."!<br><br>";
          $eikeralatila = "E";
        }

        $query = "UPDATE lasku
                  SET alatila = '$eikeralatila', lahetepvm = now()
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$laskurow[tunnus]'
                  and tila    = '$tvs_tila'
                  and alatila in ('','J')";
        $result = pupe_query($query);
      }
    }
  }

  // ollaan k‰sitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
  if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $projektilla > 0) {

    $aika=date("d.m.y @ G:i:s", time());
    echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

    if ($projektilla > 0) {
      $tilausnumero = $laskurow["tunnusnippu"];

      //  Hyp‰t‰‰n takaisin otsikolle
      echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
      exit;
    }
  }
}
