<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta JA master kantaa *//
$useslave = 1;

ini_set("memory_limit", "5G");

if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiˆ!!!\n";
    die;
  }

  date_default_timezone_set('Europe/Helsinki');

  // otetaan tietokanta connect
  require "../inc/connect.inc";
  require "../inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $kukarow['yhtio'] = trim($argv[1]);

  $abclaji      = "";
  $saldottomatmukaan  = "";
  $kustannuksetyht    = "";

  if (isset($argv[2]) and trim($argv[2]) != "") {
    $abclaji = trim($argv[2]);
  }

  if (isset($argv[3]) and trim($argv[3]) != "") {
    $saldottomatmukaan = trim($argv[3]);
  }

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  $tee = "YHTEENVETO";
}
else {
  require "../inc/parametrit.inc";
  echo "<font class='head'>".t("ABC-Aputaulun rakennus")."<hr></font>";
}

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));

if (!isset($kkl)) $kkl = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvl)) $vvl = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppl)) $ppl = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($abclaji)) $abclaji = "";

// rakennetaan tiedot
if ($tee == 'YHTEENVETO') {

  // katotaan halutaanko saldottomia mukaan.. default on ett‰ EI haluta
  if (!isset($saldottomatmukaan) or $saldottomatmukaan == "") {
    $tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '' AND tuote.myynninseuranta = '') ";
  }
  else {
    $tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '') ";
  }

  if ($abclaji == "kulutus") {

    // siivotaan ensin aputaulu tyhj‰ksi
    $query = "DELETE from abc_aputaulu
              WHERE yhtio = '$kukarow[yhtio]'
              and tyyppi  = 'TV'";
    pupe_query($query, $GLOBALS["masterlink"]);

    $query = "SELECT
              tuote.tuoteno,
              tuote.try,
              tuote.osasto,
              tuote.tuotemerkki,
              tuote.nimitys,
              tuote.luontiaika,
              tuote.myyjanro,
              tuote.ostajanro,
              tuote.malli,
              tuote.mallitarkenne,
              tuote.vihapvm,
              tuote.status,
              tuote.epakurantti100pvm,
              tuote.epakurantti75pvm,
              tuote.epakurantti50pvm,
              tuote.epakurantti25pvm,
              tuote.kehahin,
              0 kate,
              0 osto_rivia,
              0 osto_kpl,
              0 osto_summa,
              0 osto_kerrat,
              sum((SELECT sum(-1*kpl*hinta) from tapahtuma where tapahtuma.yhtio=tilausrivi.yhtio and tapahtuma.laji='kulutus' and tapahtuma.rivitunnus=tilausrivi.tunnus)) summa,
              count(*) rivia,
              sum(tilausrivi.kpl) kpl,
              count(DISTINCT tilausrivi.otunnus)-1 kerrat
              FROM tilausrivi USE INDEX (yhtio_tyyppi_toimitettuaika)
              $tuotejoin
              JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
              WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
              AND tilausrivi.tyyppi         = 'V'
              AND tilausrivi.toimitettuaika >= '$vva-$kka-$ppa 00:00:00'
              AND tilausrivi.toimitettuaika <= '$vvl-$kkl-$ppl 23:59:59'
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22";
    $rivires = pupe_query($query);

    $abctyypit = array("kulutus");
  }
  else {
    // siivotaan ensin aputaulu tyhj‰ksi
    $query = "DELETE from abc_aputaulu
              WHERE yhtio = '$kukarow[yhtio]'
              and tyyppi  in ('TK','TP','TR','TM')";
    pupe_query($query, $GLOBALS["masterlink"]);

    $query = "SELECT
              tuote.tuoteno,
              tuote.try,
              tuote.osasto,
              tuote.tuotemerkki,
              tuote.nimitys,
              tuote.luontiaika,
              tuote.myyjanro,
              tuote.ostajanro,
              tuote.malli,
              tuote.mallitarkenne,
              tuote.vihapvm,
              tuote.status,
              tuote.epakurantti100pvm,
              tuote.epakurantti75pvm,
              tuote.epakurantti50pvm,
              tuote.epakurantti25pvm,
              tuote.kehahin,
              sum(if(tilausrivi.tyyppi='L', tilausrivi.rivihinta, 0)) summa,
              sum(if(tilausrivi.tyyppi='L', tilausrivi.kate, 0)) kate,
              sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), 1, 0)) rivia,
              sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), tilausrivi.kpl, 0)) kpl,
              sum(if(tilausrivi.tyyppi='O', 1, 0)) osto_rivia,
              sum(if(tilausrivi.tyyppi='O', tilausrivi.kpl, 0)) osto_kpl,
              sum(if(tilausrivi.tyyppi='O', tilausrivi.rivihinta, 0)) osto_summa,
              count(DISTINCT if(tilausrivi.tyyppi='O', tilausrivi.otunnus, 0))-1 osto_kerrat,
              count(DISTINCT if(tilausrivi.tyyppi='L', tilausrivi.otunnus, 0))-1 kerrat
              FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
              $tuotejoin
              JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
              JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.myynninseuranta = '')
              WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
              AND tilausrivi.tyyppi         in ('L','O')
              AND tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
              AND tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17";
    $rivires = pupe_query($query);

    $abctyypit = array("kate", "kpl", "rivia", "summa");
  }

  //kokokauden kokonaismyynti
  $kaudenmyyriviyht   = 0;
  $kaudenostriviyht   = 0;

  // kokokauden kokonaismyynti
  $kate_kausiyhteensa   = 0;
  $kpl_kausiyhteensa     = 0;
  $rivia_kausiyhteensa   = 0;
  $summa_kausiyhteensa   = 0;

  $kate_sort     = array();
  $kpl_sort     = array();
  $rivia_sort   = array();
  $summa_sort   = array();
  $rowarray    = array();
  $myydyttuotteet = "";

  // K‰yd‰‰n l‰pi kaikki tuotteet joilla on tapahtumia
  while ($row = mysql_fetch_assoc($rivires)) {

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

    $kaudenostriviyht += $row["osto_rivia"];
    $kaudenmyyriviyht += $row["rivia"];

    $kate_sort[]  = $row['kate'];
    $kpl_sort[]   = $row['kpl'];
    $rivia_sort[] = $row['rivia'];
    $summa_sort[] = $row['summa'];

    // Varastonarvo
    $query = "SELECT sum(saldo) saldo
              FROM tuotepaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'";
    $saldores = pupe_query($query);
    $saldorow = mysql_fetch_assoc($saldores);

    $row['saldo'] = (float) $saldorow['saldo'];

    if      ($row['epakurantti100pvm'] != '0000-00-00') $row['vararvo'] = 0;
    elseif ($row['epakurantti75pvm']  != '0000-00-00') $row['vararvo'] = round($saldorow['saldo'] * $row['kehahin'] * 0.25, 2);
    elseif ($row['epakurantti50pvm']  != '0000-00-00') $row['vararvo'] = round($saldorow['saldo'] * $row['kehahin'] * 0.5, 2);
    elseif ($row['epakurantti25pvm']  != '0000-00-00') $row['vararvo'] = round($saldorow['saldo'] * $row['kehahin'] * 0.75, 2);
    else $row['vararvo'] = round($saldorow['saldo'] * $row['kehahin'], 2);

    if ($abclaji == "") {
      $query = "SELECT
                sum(tilkpl)  puutekpl,
                count(*)  puuterivia
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laadittu)
                WHERE yhtio  = '$kukarow[yhtio]'
                AND tyyppi   = 'L'
                AND tuoteno  = '{$row['tuoteno']}'
                and var      = 'P'
                and laadittu >= '$vva-$kka-$ppa 00:00:00'
                and laadittu <= '$vvl-$kkl-$ppl 23:59:59'";
      $puuteres = pupe_query($query);
      $puuterow = mysql_fetch_assoc($puuteres);

      $row['puutekpl']   = (float) $puuterow['puutekpl'];
      $row['puuterivia'] = (float) $puuterow['puuterivia'];
    }
    else {
      $row['puutekpl']   = 0;
      $row['puuterivia'] = 0;
    }

    $rowarray[] = $row;

    $myydyttuotteet .= "'{$row['tuoteno']}',";
  }

  $myydyttuotteet = substr($myydyttuotteet, 0, -1);

  if ($abclaji == "" and $myydyttuotteet != "") {
    // K‰yd‰‰n l‰pi kaikki tuotteet joilla on saldoa mutta ei laskutusta/ostoja... ne kuuluu myˆs I-luokkaan
    $query = "SELECT
              tuote.tuoteno,
              tuote.try,
              tuote.osasto,
              tuote.tuotemerkki,
              tuote.nimitys,
              tuote.luontiaika,
              tuote.myyjanro,
              tuote.ostajanro,
              tuote.malli,
              tuote.mallitarkenne,
              tuote.vihapvm,
              tuote.status,
              tuote.epakurantti100pvm,
              tuote.epakurantti75pvm,
              tuote.epakurantti50pvm,
              tuote.epakurantti25pvm,
              tuote.kehahin,
              0 summa,
              0 kate,
              0 rivia,
              0 kpl,
              0 osto_rivia,
              0 osto_kpl,
              0 osto_summa,
              0 osto_kerrat,
              0 kerrat,
              sum(tuotepaikat.saldo) saldo,
              sum(tuotepaikat.saldo) * if(tuote.epakurantti100pvm = '0000-00-00',if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0) vararvo
              FROM tuotepaikat
              JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno AND tuote.myynninseuranta = '')
              WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
              AND tuotepaikat.tuoteno NOT IN ($myydyttuotteet)
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26
              HAVING saldo > 0";
    $rivires = pupe_query($query);

    while ($row = mysql_fetch_assoc($rivires)) {
      $kate_sort[]  = $row['kate'];
      $kpl_sort[]   = $row['kpl'];
      $rivia_sort[] = $row['rivia'];
      $summa_sort[] = $row['summa'];

      $query = "SELECT
                sum(tilkpl)  puutekpl,
                count(*)  puuterivia
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laadittu)
                WHERE yhtio  = '$kukarow[yhtio]'
                AND tyyppi   = 'L'
                AND tuoteno  = '{$row['tuoteno']}'
                and var      = 'P'
                and laadittu >= '$vva-$kka-$ppa 00:00:00'
                and laadittu <= '$vvl-$kkl-$ppl 23:59:59'";
      $puuteres = pupe_query($query);
      $puuterow = mysql_fetch_assoc($puuteres);

      $row['puutekpl']   = (float) $puuterow['puutekpl'];
      $row['puuterivia'] = (float) $puuterow['puuterivia'];

      $rowarray[]   = $row;
    }
  }

  // t‰‰ on nyt hardcoodattu, eli milt‰ kirjanpidon tasolta otetaan kulut
  $sisainen_taso = "34";

  if ($kustannuksetyht == "" and $sisainen_taso != "") {
    // etsit‰‰n kirjanpidosta mitk‰ on meid‰n kulut samalta ajanjaksolta
    $query  = "SELECT sum(summa) summa
               FROM tiliointi use index (yhtio_tapvm_tilino)
               JOIN tili use index (tili_index) on (tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino and sisainen_taso like '$sisainen_taso%')
               WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
               AND tiliointi.tapvm    >= '$vva-$kka-$ppa'
               AND tiliointi.tapvm    <= '$vvl-$kkl-$ppl'
               AND tiliointi.korjattu = ''";
    $result = pupe_query($query);
    $kprow  = mysql_fetch_assoc($result);
    $kustannuksetyht = $kprow["summa"];
  }

  // paljonko on rivej‰ kaikenkaikkiaan
  $rivityht = $kaudenmyyriviyht + $kaudenostriviyht;

  if ($rivityht != 0) {
    // lasketaan myynti- ja ostorivien osuus kokonaisriveist‰
    $myynti_osuus = $kaudenmyyriviyht / $rivityht;
    $osto_osuus   = $kaudenostriviyht / $rivityht;
  }
  else {
    $myynti_osuus = 0;
    $osto_osuus   = 0;
  }

  // lasketaan myynnin ja oston kustannusten osuus kokonaiskustannuksista
  $myynninkustayht = $kustannuksetyht * $myynti_osuus;
  $ostojenkustayht = $kustannuksetyht * $osto_osuus;

  // sitten lasketaan viel‰ yhden myyntirivin kulu
  if ($kaudenmyyriviyht != 0) {
    $kustapermyyrivi = $myynninkustayht / $kaudenmyyriviyht;
  }
  else {
    $kustapermyyrivi = 0;
  }

  // ja lasketaan yhden ostorivin kulu
  if ($kaudenostriviyht != 0) {
    $kustaperostrivi = $ostojenkustayht / $kaudenostriviyht;
  }
  else {
    $kustaperostrivi = 0;
  }

  foreach ($abctyypit as $abctyyppi) {

    if ($abctyyppi == "kate") {
      $abcwhat     = "kate";
      $abcchar     = "TK";
      $kausiyhteensa  = $kate_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($kate_sort, SORT_DESC, $looparray);
    }
    elseif ($abctyyppi == "kpl") {
      $abcwhat     = "kpl";
      $abcchar     = "TP";
      $kausiyhteensa  = $kpl_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($kpl_sort, SORT_DESC, $looparray);
    }
    elseif ($abctyyppi == "rivia") {
      $abcwhat     = "rivia";
      $abcchar     = "TR";
      $kausiyhteensa  = $rivia_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($rivia_sort, SORT_DESC, $looparray);
    }
    elseif ($abctyyppi == "kulutus") {
      $abcwhat     = "kpl";
      $abcchar     = "TV";
      $kausiyhteensa  = $kpl_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($kpl_sort, SORT_DESC, $looparray);
    }
    else {
      $abcwhat     = "summa";
      $abcchar     = "TM";
      $kausiyhteensa  = $summa_kausiyhteensa;
      $looparray     = $rowarray;
      array_multisort($summa_sort, SORT_DESC, $looparray);
    }

    // Haetaan abc-parametrit
    list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

    $i_luokka = count($ryhmaprossat)-1;

    $i        = 0;
    $ryhmaprossa  = 0;

    foreach ($looparray as $row) {

      // ensimm‰inen tulo
      $query = "SELECT laadittu tulopvm
                FROM tapahtuma USE INDEX (yhtio_laji_tuoteno)
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$row[tuoteno]'
                AND laji    = 'tulo'
                AND selite  not like '%alkusaldo%'
                ORDER BY laadittu
                LIMIT 1";
      $insres = pupe_query($query);
      $tulorow = mysql_fetch_assoc($insres);

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
        $luokka = $i_luokka;
      }

      if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100, 2);
      else $kateprosentti = 0;

      if ($row["vararvo"] != 0) $kiertonopeus  = round(($row["summa"] - $row["kate"]) / $row["vararvo"], 2);
      else $kiertonopeus = 0;

      if ($row["rivia"] != 0) $myyntieranarvo = round($row["summa"] / $row["rivia"], 2);
      else $myyntieranarvo = 0;

      if ($row["rivia"] != 0) $myyntieranakpl = round($row["kpl"] / $row["rivia"], 2);
      else $myyntieranakpl = 0;

      if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100), 2);
      else $palvelutaso = 0;

      if ($row["osto_rivia"] != 0) $ostoeranarvo = round($row["osto_summa"] / $row["osto_rivia"], 2);
      else $ostoeranarvo = 0;

      if ($row["osto_rivia"] != 0) $ostoeranakpl = round($row["osto_kpl"] / $row["osto_rivia"], 2);
      else $ostoeranakpl = 0;

      $query = "INSERT INTO abc_aputaulu
                SET yhtio      = '$kukarow[yhtio]',
                tyyppi             = '$abcchar',
                luokka             = '$luokka',
                tuoteno            = '$row[tuoteno]',
                nimitys            = '$row[nimitys]',
                osasto             = '$row[osasto]',
                tuotemerkki        = '$row[tuotemerkki]',
                try                = '$row[try]',
                tulopvm            = '$tulorow[tulopvm]',
                summa              = '$row[summa]',
                kate               = '$row[kate]',
                katepros           = '$kateprosentti',
                vararvo            = '$row[vararvo]',
                varaston_kiertonop = '$kiertonopeus',
                myyntierankpl      = '$myyntieranakpl',
                myyntieranarvo     = '$myyntieranarvo',
                rivia              = '$row[rivia]',
                kpl                = '$row[kpl]',
                puutekpl           = '$row[puutekpl]',
                puuterivia         = '$row[puuterivia]',
                palvelutaso        = '$palvelutaso',
                osto_rivia         = '$row[osto_rivia]',
                osto_kpl           = '$row[osto_kpl]',
                ostoerankpl        = '$ostoeranakpl',
                ostoeranarvo       = '$ostoeranarvo',
                osto_summa         = '$row[osto_summa]',
                osto_kerrat        = '$row[osto_kerrat]',
                kerrat             = '$row[kerrat]',
                tuote_luontiaika   = '$row[luontiaika]',
                myyjanro           = '$row[myyjanro]',
                ostajanro          = '$row[ostajanro]',
                malli              = '$row[malli]',
                mallitarkenne      = '$row[mallitarkenne]',
                saapumispvm        = '$row[vihapvm]',
                saldo              = '$row[saldo]',
                status             = '$row[status]',
                kustannus          = round($row[rivia] * $kustapermyyrivi, 2),
                kustannus_osto     = round($row[osto_rivia] * $kustaperostrivi, 2),
                kustannus_yht      = round(($row[osto_rivia] * $kustaperostrivi) + ($row[rivia] * $kustapermyyrivi), 2),
                luokka_osasto      = '$i_luokka',
                luokka_try         = '$i_luokka',
                luokka_tuotemerkki = '$i_luokka'";
      pupe_query($query, $GLOBALS["masterlink"]);

      // luokka vaihtuu
      if ($ryhmaprossa >= $ryhmaprossat[$i]) {
        $ryhmaprossa = 0;
        $i++;

        // ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
        if ($i == $i_luokka) {
          $i = $i_luokka-1;
        }
      }
    }

    // haetaan kaikki osastot
    $query = "SELECT DISTINCT osasto
              FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
              WHERE yhtio = '$kukarow[yhtio]'
              and tyyppi  = '$abcchar'
              order by osasto";
    $kaikres = pupe_query($query, $GLOBALS["masterlink"]);

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
      $resi   = pupe_query($query, $GLOBALS["masterlink"]);
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
      $res = pupe_query($query, $GLOBALS["masterlink"]);

      $i       = 0;
      $ryhmaprossa = 0;

      while ($row = mysql_fetch_assoc($res)) {

        // tuotteen osuus yhteissummasta
        if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
        else $tuoteprossa = 0;

        //muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;

        $query = "UPDATE abc_aputaulu
                  SET luokka_osasto = '$i'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$abcchar'
                  and tunnus  = '$row[tunnus]'";
        pupe_query($query, $GLOBALS["masterlink"]);

        //luokka vaihtuu
        if (round($ryhmaprossa, 2) >= $ryhmaprossat[$i]) {
          $ryhmaprossa = 0;
          $i++;

          // ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
          if ($i == $i_luokka) {
            $i = $i_luokka-1;
          }
        }
      }
    }

    // haetaan kaikki tryt
    $query = "SELECT DISTINCT try
              FROM abc_aputaulu use index (yhtio_tyyppi_try)
              WHERE yhtio = '$kukarow[yhtio]'
              and tyyppi  = '$abcchar'
              order by try";
    $kaikres = pupe_query($query, $GLOBALS["masterlink"]);

    // tehd‰‰n try kohtaiset luokat
    while ($arow = mysql_fetch_assoc($kaikres)) {

      //haetaan luokan myynti yhteens‰
      $query = "SELECT
                sum(rivia) rivia,
                sum(summa) summa,
                sum(kpl)   kpl,
                sum(kate)  kate
                FROM abc_aputaulu use index (yhtio_tyyppi_try)
                WHERE yhtio = '$kukarow[yhtio]'
                and tyyppi  = '$abcchar'
                and try     = '$arow[try]'
                and $abcwhat > 0";
      $resi   = pupe_query($query, $GLOBALS["masterlink"]);
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
      $res = pupe_query($query, $GLOBALS["masterlink"]);

      $i       = 0;
      $ryhmaprossa = 0;

      while ($row = mysql_fetch_assoc($res)) {

        // tuotteen osuus yhteissummasta
        if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
        else $tuoteprossa = 0;

        //muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;

        $query = "UPDATE abc_aputaulu
                  SET luokka_try = '$i'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$abcchar'
                  and tunnus  = '$row[tunnus]'";
        pupe_query($query, $GLOBALS["masterlink"]);

        //luokka vaihtuu
        if (round($ryhmaprossa, 2) >= $ryhmaprossat[$i]) {
          $ryhmaprossa = 0;
          $i++;

          // ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
          if ($i == $i_luokka) {
            $i = $i_luokka-1;
          }
        }
      }
    }

    // haetaan kaikki tuotemerkit
    $query = "SELECT DISTINCT tuotemerkki
              FROM abc_aputaulu USE INDEX (yhtio_tyyppi_tuotemerkki)
              WHERE yhtio = '$kukarow[yhtio]'
              AND tyyppi  = '$abcchar'
              ORDER BY tuotemerkki";
    $kaikres = pupe_query($query, $GLOBALS["masterlink"]);

    // tehd‰‰n try kohtaiset luokat
    while ($arow = mysql_fetch_assoc($kaikres)) {

      //haetaan luokan myynti yhteens‰
      $query = "SELECT
                sum(rivia) rivia,
                sum(summa) summa,
                sum(kpl)   kpl,
                sum(kate)  kate
                FROM abc_aputaulu use index (yhtio_tyyppi_tuotemerkki)
                WHERE yhtio     = '$kukarow[yhtio]'
                and tyyppi      = '$abcchar'
                and tuotemerkki = '$arow[tuotemerkki]'
                and $abcwhat > 0";
      $resi   = pupe_query($query, $GLOBALS["masterlink"]);
      $yhtrow = mysql_fetch_assoc($resi);

      //rakennetaan aliluokat
      $query = "SELECT
                rivia,
                summa,
                kate,
                kpl,
                tunnus
                FROM abc_aputaulu use index (yhtio_tyyppi_tuotemerkki)
                WHERE yhtio     = '$kukarow[yhtio]'
                and tyyppi      = '$abcchar'
                and tuotemerkki = '$arow[tuotemerkki]'
                and $abcwhat > 0
                ORDER BY $abcwhat desc";
      $res = pupe_query($query, $GLOBALS["masterlink"]);

      $i       = 0;
      $ryhmaprossa = 0;

      while ($row = mysql_fetch_assoc($res)) {

        // tuotteen osuus yhteissummasta
        if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
        else $tuoteprossa = 0;

        //muodostetaan ABC-luokka ryhm‰prossan mukaan
        $ryhmaprossa += $tuoteprossa;

        $query = "UPDATE abc_aputaulu
                  SET luokka_tuotemerkki = '$i'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$abcchar'
                  and tunnus  = '$row[tunnus]'";
        pupe_query($query, $GLOBALS["masterlink"]);

        //luokka vaihtuu
        if (round($ryhmaprossa, 2) >= $ryhmaprossat[$i]) {
          $ryhmaprossa = 0;
          $i++;

          // ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
          if ($i == $i_luokka) {
            $i = $i_luokka-1;
          }
        }
      }
    }
  }

  if (!$php_cli) {
    $query = "OPTIMIZE table abc_aputaulu";
    pupe_query($query, $GLOBALS["masterlink"]);

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

  echo "<tr><th>".t("ABC-luokkien laskentatapa")."</th>";
  echo "<td colspan='3'><select name='abclaji'>";
  echo "<option value='myynti'>".t("Myynnin mukaan")."</option>";
  echo "<option value='kulutus'>".t("Kulutuksen mukaan")."</option>";
  echo "</select></td></tr>";

  echo "<tr><td colspan='4' class='back'><br></td></tr>";
  echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella")."</th>";
  echo "<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";
  echo "<tr><th colspan='1'>".t("Huomioi laskennassa myˆs saldottomat tuotteet")."</th>";
  echo "<td colspan='3'><input type='checkbox' name='saldottomatmukaan' value='kylla'></td></tr>";

  echo "</table>";
  echo "<br><input type='submit' value='".t("Rakenna")."'>";
  echo "</form><br><br><br>";

  require "inc/footer.inc";
}
