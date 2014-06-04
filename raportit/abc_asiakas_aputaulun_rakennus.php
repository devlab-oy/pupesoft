<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta JA master kantaa *//
$useslave = 1;

if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiˆ!!!\n";
    die;
  }

  date_default_timezone_set('Europe/Helsinki');

  // otetaan tietokanta connect
  require ("../inc/connect.inc");
  require ("../inc/functions.inc");

  $kukarow['yhtio'] = trim($argv[1]);

  $saldottomatmukaan   = "";
  $kustannuksetyht   = "";

  if (isset($argv[2]) and trim($argv[2]) != "") {
    $saldottomatmukaan = trim($argv[2]);
  }

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  $tee = "YHTEENVETO";
}
else {
  require ("../inc/parametrit.inc");
  echo "<font class='head'>".t("ABC-Aputaulun rakennus")."<hr></font>";
}

if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));

if (!isset($kkl)) $kkl = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvl)) $vvl = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppl)) $ppl = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($abctyyppi)) $abctyyppi = "kate";

// rakennetaan tiedot
if ($tee == 'YHTEENVETO') {

  //siivotaan ensin aputaulu tyhj‰ksi
  $query = "DELETE from abc_aputaulu
            WHERE yhtio = '$kukarow[yhtio]'
            and tyyppi  IN ('AK','AP','AR','AM')";
  pupe_query($query, $masterlink);

  // katotaan halutaanko saldottomia mukaan.. default on ettei haluta
  if ($saldottomatmukaan == "") {
    $tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '') ";
  }
  else {
    $tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno) ";
  }

  //haetaan ensin koko kauden yhteisnmyynti ja ostot
  $query = "SELECT
            lasku.liitostunnus,
            sum(if(tyyppi='L', 1, 0))            rivia,
            sum(if(tyyppi='L', tilausrivi.kpl, 0))      kpl,
            sum(if(tyyppi='L', tilausrivi.rivihinta, 0))  summa,
            sum(if(tyyppi='L', tilausrivi.kate, 0))     kate
            FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
            $tuotejoin
            JOIN lasku USE INDEX (primary) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
            WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
            and tilausrivi.tyyppi         = 'L'
            and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
            and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
            GROUP BY liitostunnus";
  $res = pupe_query($query);

  $query = "SELECT liitostunnus, osasto, ryhma, myyjanro,
            sum(rivia) rivia, sum(kpl) kpl, sum(summa) summa, sum(kate) kate, sum(puutekpl) puutekpl, sum(puuterivia) puuterivia
            FROM (
              (SELECT
              lasku.liitostunnus,
              ifnull(asiakas.osasto,'#')    osasto,
              ifnull(asiakas.ryhma,'#')     ryhma,
              ifnull(asiakas.myyjanro,'#')  myyjanro,
              count(*)            rivia,
              sum(tilausrivi.kpl)        kpl,
              sum(tilausrivi.rivihinta)    summa,
              sum(tilausrivi.kate)      kate,
              0                puutekpl,
              0                puuterivia
              FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
              JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
              $tuotejoin
              LEFT JOIN asiakas USE INDEX (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
              WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
              AND tilausrivi.tyyppi         = 'L'
              AND tilausrivi.var            IN ('','H')
              AND tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
              AND tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
              GROUP BY 1,2,3,4)
              UNION
              (SELECT
              lasku.liitostunnus,
              ifnull(asiakas.osasto,'#')     osasto,
              ifnull(asiakas.ryhma,'#')      ryhma,
              ifnull(asiakas.myyjanro,'#')   myyjanro,
              0                 rivia,
              0                 kpl,
              0                 summa,
              0                 kate,
              sum(tilausrivi.tilkpl)      puutekpl,
              count(*)            puuterivia
              FROM tilausrivi
              JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
              $tuotejoin
              LEFT JOIN asiakas USE INDEX (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
              WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
              AND tilausrivi.tyyppi         = 'L'
              AND tilausrivi.var            = 'P'
              AND tilausrivi.laadittu       >= '$vva-$kka-$ppa 00:00:00'
              AND tilausrivi.laadittu       <= '$vvl-$kkl-$ppl 23:59:59'
              GROUP BY 1,2,3,4)
            ) x
            GROUP BY 1,2,3,4";
  $res = pupe_query($query);

  $kaudenmyyriviyht     = 0;

  $kate_kausiyhteensa   = 0;
  $kpl_kausiyhteensa     = 0;
  $rivia_kausiyhteensa   = 0;
  $summa_kausiyhteensa   = 0;

  $kate_sort         = array();
  $kpl_sort         = array();
  $rivia_sort       = array();
  $summa_sort       = array();
  $rowarray        = array();

  // joudutaan summaamaan loopissa, koska kokonaismyyntiin ei saa vaikuttaa tuotteet joiden kauden myynti/kate/kappaleet on alle nolla
  while ($row = mysql_fetch_assoc($res)) {

    $kate_sort[]  = $row['kate'];
    $kpl_sort[]   = $row['kpl'];
    $rivia_sort[] = $row['rivia'];
    $summa_sort[] = $row['summa'];

    // onko enemm‰n ku nolla
    if ($row["kate"] > 0) {
      $kate_kausiyhteensa += $row["kate"];
    }
    if ($row["kpl"] > 0) {
      $kpl_kausiyhteensa += $row["kpl"];
    }
    if ($row["rivia"] > 0) {
      $rivia_kausiyhteensa += $row["rivia"];
    }
    if ($row["summa"] > 0) {
      $summa_kausiyhteensa += $row["summa"];
    }

    $kaudenmyyriviyht += $row["rivia"];

    $rowarray[] = $row;
  }

   // t‰‰ on nyt hardcoodattu, eli milt‰ kirjanpidon tasolta otetaan kulut
  $sisainen_taso = "34";

  if ($kustannuksetyht == "" and $sisainen_taso != "") {
    // etsit‰‰n kirjanpidosta mitk‰ on meid‰n kulut samalta ajanjaksolta
    $query  = "SELECT sum(summa) summa
               FROM tiliointi use index (yhtio_tapvm_tilino)
               join tili use index (tili_index) on (tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino and sisainen_taso like '$sisainen_taso%')
               where tiliointi.yhtio = '$kukarow[yhtio]' and
               tiliointi.tapvm       >= '$vva-$kka-$ppa' and
               tiliointi.tapvm       <= '$vvl-$kkl-$ppl' and
               tiliointi.korjattu    = ''";
    $result = pupe_query($query);
    $kprow  = mysql_fetch_assoc($result);

    $kustannuksetyht = $kprow["summa"];
  }

  // sitten lasketaan yhden myyntirivin kulu
  if ($kaudenmyyriviyht != 0) {
    $kustapermyyrivi = $kustannuksetyht / $kaudenmyyriviyht;
  }
  else {
    $kustapermyyrivi = 0;
  }

  $abctyypit = array("kate","kpl","rivia","summa");

  foreach ($abctyypit as $abctyyppi) {

    if ($abctyyppi == "kate") {
      $abcwhat     = "kate";
      $abcchar     = "AK";
      $kausiyhteensa  =  $kate_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($kate_sort, SORT_DESC, $looparray);
    }
    elseif ($abctyyppi == "kpl") {
      $abcwhat     = "kpl";
      $abcchar     = "AP";
      $kausiyhteensa  =  $kpl_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($kpl_sort, SORT_DESC, $looparray);
    }
    elseif ($abctyyppi == "rivia") {
      $abcwhat     = "rivia";
      $abcchar     = "AR";
      $kausiyhteensa  =  $rivia_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($rivia_sort, SORT_DESC, $looparray);
    }
    else {
      $abcwhat     = "summa";
      $abcchar     = "AM";
      $kausiyhteensa  =  $summa_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($summa_sort, SORT_DESC, $looparray);
    }

    // Haetaan abc-parametrit
    list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

    $i       = 0;
    $ryhmaprossa = 0;

    foreach ($looparray as $row) {

      // katotaan onko kelvollinen tuote, elikk‰ luokitteluperuste pit‰‰ olla > 0
      if ($row["${abcwhat}"] > 0) {

        // laitetaan oikeeseen luokkaan
        $luokka = $i;

        // tuotteen osuus yhteissummasta
        if ($kausiyhteensa != 0) $tuoteprossa = ($row["${abcwhat}"] / $kausiyhteensa) * 100;
        else $tuoteprossa = 0;

        //muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;
      }
      else {
        // ei ole kelvollinen tuote laitetaan I-luokkaan
        $luokka = 3;
      }

      if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
      else $kateprosentti = 0;

      if ($row["rivia"] != 0)  $myyntieranarvo = round($row["summa"] / $row["rivia"],2);
      else $myyntieranarvo = 0;

      if ($row["rivia"] != 0)  $myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
      else $myyntieranakpl = 0;

      if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
      else $palvelutaso = 0;

      //rivin kustannus
      $kustamyy = round($kustapermyyrivi * $row["rivia"], 2);
      $kustayht = $kustamyy;

      $query = "INSERT INTO abc_aputaulu
                SET yhtio      = '$kukarow[yhtio]',
                tyyppi         = '$abcchar',
                luokka         = '$i',
                osto_rivia     = '$row[myyjanro]',
                tuoteno        = '$row[liitostunnus]',
                osasto         = '$row[osasto]',
                try            = '$row[ryhma]',
                summa          = '$row[summa]',
                kate           = '$row[kate]',
                katepros       = '$kateprosentti',
                myyntierankpl  = '$myyntieranakpl',
                myyntieranarvo = '$myyntieranarvo',
                rivia          = '$row[rivia]',
                kpl            = '$row[kpl]',
                puutekpl       = '$row[puutekpl]',
                puuterivia     = '$row[puuterivia]',
                palvelutaso    = '$palvelutaso',
                kustannus_yht  = '$kustayht',
                luokka_osasto  = '3',
                luokka_try     = '3'";
      pupe_query($query, $masterlink);

      //luokka vaihtuu
      if ($ryhmaprossa >= $ryhmaprossat[$i]) {
        $ryhmaprossa = 0;
        $i++;

        // ei menn‰ ikin‰ I-luokkaan asti
        if ($i == 3) {
          $i = 2;
        }
      }
    }

    // haetaan kaikki osastot
    $query = "SELECT distinct osasto
              FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
              WHERE yhtio = '$kukarow[yhtio]'
              AND tyyppi  = '$abcchar'
              ORDER BY osasto";
    $kaikres = pupe_query($query, $masterlink);

    // tehd‰‰n osastokohtaiset luokat
    while ($arow = mysql_fetch_assoc($kaikres)) {

      //haetaan luokan myynti yhteens‰
      $query = "SELECT
                sum(rivia) rivia,
                sum(summa) summa,
                sum(kpl)   kpl,
                sum(kate)  kate
                FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
                WHERE yhtio = '$kukarow[yhtio]'
                and tyyppi  = '$abcchar'
                and osasto  = '$arow[osasto]'
                and $abcwhat > 0";
      $resi   = pupe_query($query, $masterlink);
      $yhtrow = mysql_fetch_assoc($resi);

      //rakennetaan aliluokat
      $query = "SELECT
                rivia,
                summa,
                kate,
                kpl,
                tunnus
                FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
                WHERE yhtio = '$kukarow[yhtio]'
                and tyyppi  = '$abcchar'
                and osasto  = '$arow[osasto]'
                and $abcwhat > 0
                ORDER BY $abcwhat desc";
      $res = pupe_query($query, $masterlink);

      $i       = 0;
      $ryhmaprossa = 0;

      while ($row = mysql_fetch_assoc($res)) {

        // asiakkaan osuus yhteissummasta
        if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
        else $tuoteprossa = 0;

        // muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;

        $query = "UPDATE abc_aputaulu
                  SET luokka_osasto = '$i'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$abcchar'
                  and tunnus  = '$row[tunnus]'";
        pupe_query($query, $masterlink);

        // luokka vaihtuu
        if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
          $ryhmaprossa = 0;
          $i++;

          if ($i == 3) {
            $i = 2;
          }
        }
      }

    }

    // haetaan kaikki tryt
    $query = "SELECT distinct try
              FROM abc_aputaulu use index (yhtio_tyyppi_try)
              WHERE yhtio = '$kukarow[yhtio]'
              AND tyyppi  = '$abcchar'
              ORDER BY try";
    $kaikres = pupe_query($query, $masterlink);

    // tehd‰‰n try kohtaiset luokat
    while ($arow = mysql_fetch_assoc($kaikres)) {

      //haetaan luokan myynti yhteens‰
      $query = "SELECT
                sum(rivia) rivia,
                sum(summa) summa,
                sum(kpl) kpl,
                sum(kate) kate
                FROM abc_aputaulu use index (yhtio_tyyppi_try)
                WHERE yhtio = '$kukarow[yhtio]'
                and tyyppi  = '$abcchar'
                and try     = '$arow[try]'
                and $abcwhat > 0";
      $resi   = pupe_query($query, $masterlink);
      $yhtrow = mysql_fetch_assoc($resi);

      //rakennetaan aliluokat
      $query = "SELECT
                rivia,
                summa,
                kate,
                kpl,
                tunnus
                FROM abc_aputaulu use index (yhtio_tyyppi_try)
                WHERE yhtio = '$kukarow[yhtio]'
                and tyyppi  = '$abcchar'
                and try     = '$arow[try]'
                and $abcwhat > 0
                ORDER BY $abcwhat desc";
      $res = pupe_query($query, $masterlink);

      $i       = 0;
      $ryhmaprossa = 0;

      while ($row = mysql_fetch_assoc($res)) {

        // asiakkaan osuus yhteissummasta
        if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
        else $tuoteprossa = 0;

        // muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;

        $query = "UPDATE abc_aputaulu
                  SET luokka_try = '$i'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$abcchar'
                  and tunnus  = '$row[tunnus]'";
        pupe_query($query, $masterlink);

        // luokka vaihtuu
        if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
          $ryhmaprossa = 0;
          $i++;

          if ($i == 3) {
            $i = 2;
          }
        }
      }
    }
  }

  if (!$php_cli) {
    $query = "OPTIMIZE table abc_aputaulu";
    pupe_query($query, $masterlink);

    echo t("ABC-aputaulu rakennettu")."!<br><br>";
  }
}

if (!$php_cli) {

  // piirrell‰‰n formi
  echo "<form method='post' autocomplete='OFF'>";
  echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
  echo "<table>";

  echo "<th colspan='4'>".t("Valitse kausi").":</th>";

  echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr>";
  echo "<tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

  echo "<tr><td colspan='4' class='back'><br></td></tr>";
  echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella")."</th>";
  echo "<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";
  echo "<tr><th colspan='1'>".t("Huomioi laskennassa myˆs saldottomat tuotteet")."</th>";
  echo "<td colspan='3'><input type='checkbox' name='saldottomatmukaan' value='kylla'></td></tr>";

  echo "</table>";
  echo "<br><input type='submit' value='".t("Rakenna")."'>";
  echo "</form><br><br><br>";
  require ("../inc/footer.inc");
}
