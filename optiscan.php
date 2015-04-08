#!/usr/bin/php
<?php

/*
    T�t� on tarkoitus k�ytt�� Linux XINETD socket serverin kanssa.

    1. Tehd��n tiedosto /etc/xinetd.d/optiscan

    # description: Optsican socket server
    service optiscan
    {
       port        = 15005
       socket_type = stream
       wait        = no
       user        = apache
       server      = /var/www/html/pupesoft/optiscan.php
       disable     = no
    }

    2. Lis�t��n tiedostoon /etc/services rivi

    optiscan        15005/tcp               # Optiscan socket server

    3. Varmista, ett� XINETD k�ytynnistyy bootissa.

    ntsysv

    4. Varmista, ett� optiscan.php:ll� on execute oikeus (t�m� on hyv�t� lis�t� my�s pupesoft p�ivitys scriptiin!)

    chmod a+x /var/www/html/pupesoft/optiscan.php

    5. Uudelleenk�ynnist� XINETD

    service xinetd restart
  */

if (php_sapi_name() != 'cli') {
  die("1, Voidaan ajaa vain komentorivilt�\r\n\r\n");
}

// K�ynnistet��n outputbufferi
ob_start();

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 0);

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");

date_default_timezone_set('Europe/Helsinki');

$pupe_root_polku = dirname(__FILE__);
chdir($pupe_root_polku);

$handle = fopen("php://stdin", "r");
$lines = array();

while ($line = fgets($handle)) {
  if (trim($line) == "") {
    $lines[] = "#EOM";
    break;
  }
  $lines[] = trim($line);
}

// Jos kuoltiin timeouttiin
if (!in_array("#EOM", $lines)) {
  die("1, Timeout\r\n\r\n");
}

// Jos saatiin laitteelta enemm�n kuin yksi rivi
if (count($lines) != 2) {
  die("1, Virheellinen viesti\r\n\r\n");
}

// Erotellaan sanomasta sanoman tyyppi ja sis�lt�
preg_match("/^(.*?)\((.*?)\)/", $lines[0], $matches);

// Varmistetaan, ett� splitti onnistui
if (!isset($matches[1]) or !isset($matches[2])) {
  die("1, Virheellinen sanomamuoto\r\n\r\n");
}

$sanoma  = $matches[1];
$sisalto = $matches[2];

// Sallittuja sanomia
$sallitut_sanomat = array(
  "SignOn",
  "GetPicks",
  "PrintContainers",
  "Picked",
  "AllPicked",
  "StopAssignment",
  "SignOff",
  //"Replenishment",
  "NewContainer",
  "ChangeContainer",
);

// Virheellinen sanoma tai tyhj� sis�lt�
if (!in_array($sanoma, $sallitut_sanomat)) {
  die("1, Virheellinen sanoma\r\n\r\n");
}

// Erotellaan sanoman sis�lt� arrayseen
$sisalto = explode(",", str_replace("'", "", $sisalto));

$responseaika_alku = date("Y-m-d H:i:s");
$responseaika_alku_sek = time();

$response = "";

require 'inc/connect.inc';
require 'inc/functions.inc';

// Laitetaan mukaan logiin
$argv[] = $lines[0];

// Logitetaan ajo
cron_log();

if ($sanoma == "SignOn") {

  /**
   * Parametrit
   * 1. DateTime mm-dd-yyyy hh:mm:ss
   * 2. Device Serial Number string
   * 3. Login
   * 4. Password
   */


  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $pass = md5(trim($sisalto[3]));

  $query = "SELECT kuka.kuka, kuka.salasana
            FROM kuka
            JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
            WHERE kuka.kuka   = '{$kukarow['kuka']}'
            AND kuka.extranet = ''
            AND kuka.yhtio    = '{$kukarow['yhtio']}'
            GROUP BY 1,2";
  $result = pupe_query($query);
  $krow = mysql_fetch_assoc($result);

  if (trim($krow['kuka']) == '') {
    // K�ytt�j�nime� ei l�ytynyt
    $response = "1, K�ytt�j�� ei l�ydy\r\n\r\n";
  }
  elseif ($pass != $krow['salasana']) {
    // Salasana virheellinen
    $response = "1, Salasana virheellinen\r\n\r\n";
  }
  elseif ($pass == $krow['salasana']) {
    // Sis��nkirjaantuminen onnistui
    $response = "0, Sis��nkirjaantuminen onnistui\r\n\r\n";
  }
  else {
    // Sis��nkirjaantuminen ep�onnistui
    $response = "1, Sis��nkirjaantuminen ep�onnistui\r\n\r\n";
  }
}
elseif ($sanoma == "GetPicks") {
  // Kent�t: "Pick slot" sek� "Item number" tulee olla m��r�mittaisia. Paddataan loppuun spacella.

  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);
  $otunnukset = '';

  // Katsotaan onko k�ytt�j�ll� jo ker�yser� ker�yksess�
  // Jos on useampi, niin napataan vain yksi er� kerrallaan
  $query = "SELECT nro, min(keraysvyohyke) keraysvyohyke, GROUP_CONCAT(tilausrivi) AS tilausrivit,
            GROUP_CONCAT(distinct otunnus) AS otunnukset, min(otunnus) kerayslistatunnus
            FROM kerayserat
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND laatija        = '{$kukarow['kuka']}'
            AND ohjelma_moduli = 'OPTISCAN'
            AND tila           = 'K'
            AND keratty        = ''
            GROUP BY nro
            ORDER BY nro
            LIMIT 1";
  $result = pupe_query($query);
  $kerattavat_rivit_row = mysql_fetch_assoc($result);

  // Jos keskener�ist� keikkaa ei ole, niin tehd�� uusi er�
  if (trim($kerattavat_rivit_row['tilausrivit']) == '') {
    $query = "SELECT keraysvyohyke, oletus_varasto
              FROM kuka
              WHERE yhtio         = '{$kukarow['yhtio']}'
              AND kuka            = '{$kukarow['kuka']}'
              AND extranet        = ''
              AND keraysvyohyke  != ''
              AND oletus_varasto != ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $response = "N,,,,,,,,,,,,,1,K�ytt�j�tiedot virheelliset\r\n";
    }
    else {
      $row = mysql_fetch_assoc($result);

      $erat = tee_keraysera($row['keraysvyohyke'], $row['oletus_varasto']);

      // Ei saatu lukkoa j�rkev�ss� ajassa
      if ($erat === FALSE) {
        $response = "N,,,,,,,,,,,,,1,Ker�yserien luonnissa ruuhkaa. Yrit� pian uudelleen.\r\n";
      }

      if (isset($erat['tilaukset']) and count($erat['tilaukset']) > 0) {
        // Tallennetaan miss� t�� er� on tehty
        $ohjelma_moduli = "OPTISCAN";

        require 'inc/tallenna_keraysera.inc';

        // N�m� tilaukset tallennettin ker�yser��n
        if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

          $otunnukset = implode(",", $lisatyt_tilaukset);
          $lisatyt_tilaukset_keys = array_keys($lisatyt_tilaukset);
          $kerayslistatunnus = array_shift($lisatyt_tilaukset_keys);

          // tilaus on jo tilassa N A, p�ivitet��n nyt tilaus "ker�yslista tulostettu" eli L A
          $query = "UPDATE lasku SET
                    tila        = 'L',
                    alatila     = 'A',
                    lahetepvm   = now(),
                    hyvak3      = '{$kukarow['kuka']}',
                    h3time      = now(),
                    kerayslista = '{$kerayslistatunnus}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  in ({$otunnukset})
                    AND tila    = 'N'
                    AND alatila = 'KA'";
          pupe_query($query);

          if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
            // siirtolista on jo tilassa G J, p�ivitet��n nyt tilaus "siirtolista tulostettu" eli G A
            $query = "UPDATE lasku SET
                      alatila     = 'A',
                      lahetepvm   = now(),
                      hyvak3      = '{$kukarow['kuka']}',
                      h3time      = now(),
                      kerayslista = '{$kerayslistatunnus}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  in ({$otunnukset})
                      AND tila    = 'G'
                      AND alatila = 'KJ'";
            pupe_query($query);
          }

          // Haetaan ker�tt�v�t rivit
          $query = "SELECT min(nro) nro, min(keraysvyohyke) keraysvyohyke, GROUP_CONCAT(tilausrivi) AS tilausrivit
                    FROM kerayserat
                    WHERE yhtio        = '{$kukarow['yhtio']}'
                    AND laatija        = '{$kukarow['kuka']}'
                    AND ohjelma_moduli = 'OPTISCAN'
                    AND tila           = 'K'
                    AND nro            = '$kerayseran_numero'
                    AND keratty        = ''";
          $result = pupe_query($query);
          $kerattavat_rivit_row = mysql_fetch_assoc($result);
        }
      }

      // Vapautetaan ker�syer�n nappaamat tilaukset
      release_tee_keraysera();
    }
  }

  if (trim($kerattavat_rivit_row['tilausrivit']) != '') {
    $kpl_arr = explode(",", $kerattavat_rivit_row['tilausrivit']);
    $kpl    = count($kpl_arr);
    $n      = 1;
    
    if ($kerattavat_rivit_row['otunnukset'] != '' and $otunnukset == '') {

      // jos ker�yser� j��nyt vaiheeseen ja ker��j� ottaa sen uusiksi
      // p�ivitet��n myyntitilauksen tilat kohdalleen
      $query = "UPDATE lasku SET
                tila        = 'L',
                alatila     = 'A',
                lahetepvm   = now(),
                hyvak3      = '{$kukarow['kuka']}',
                h3time      = now(),
                kerayslista = '{$kerattavat_rivit_row['kerayslistatunnus']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  in ({$kerattavat_rivit_row['otunnukset']})
                AND tila    = 'N'
                AND alatila in ('A', 'KA')
                AND hyvak3  = ''
                AND h3time  = '0000-00-00 00:00:00'";
      pupe_query($query);

      if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
        // ja siirtolistan tilat kohdalleen
        $query = "UPDATE lasku SET
                  alatila     = 'A',
                  lahetepvm   = now(),
                  hyvak3      = '{$kukarow['kuka']}',
                  h3time      = now(),
                  kerayslista = '{$kerattavat_rivit_row['kerayslistatunnus']}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  in ({$kerattavat_rivit_row['otunnukset']})
                  AND tila    = 'G'
                  AND alatila in ('J', 'KJ')
                  AND hyvak3  = ''
                  AND h3time  = '0000-00-00 00:00:00'";
        pupe_query($query);
      }
    }

    // haetaan ker�ysvy�hykkeen takaa ker�ysj�rjestys
    $query = "SELECT keraysjarjestys
              FROM keraysvyohyke
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$kerattavat_rivit_row['keraysvyohyke']}'";
    $keraysjarjestys_res = pupe_query($query);
    $keraysjarjestys_row = mysql_fetch_assoc($keraysjarjestys_res);

    $orderby_select = $keraysjarjestys_row['keraysjarjestys'] == "V" ? ",".generoi_sorttauskentta("3") : "";
    $orderby = $keraysjarjestys_row['keraysjarjestys'] == 'P' ? "kokonaismassa" : ($keraysjarjestys_row['keraysjarjestys'] == "V" ? "sorttauskentta" : "vh.indeksi");

    $query = "SELECT keraysvyohyke.nimitys AS ker_nimitys,
              tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
              IFNULL(vh.varmistuskoodi, '00') AS varmistuskoodi,
              tilausrivi.tuoteno, ROUND(kerayserat.kpl, 0) AS varattu, tilausrivi.yksikko, tuote.nimitys,
              kerayserat.pakkausnro, kerayserat.sscc, kerayserat.tunnus AS kerayseran_tunnus,
              (tuote.tuotemassa * ROUND(kerayserat.kpl, 0)) AS kokonaismassa,
              kerayserat.nro, tuote.kerayskommentti
              {$orderby_select}
              FROM tilausrivi
              JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus AND kerayserat.nro = {$kerattavat_rivit_row['nro']})
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
              JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
              JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.tunnus  IN ({$kerattavat_rivit_row['tilausrivit']})
              ORDER BY {$orderby}";
    $rivi_result = pupe_query($query);

    while ($rivi_row = mysql_fetch_assoc($rivi_result)) {

      $rivi_row['kerayskommentti'] = str_replace(array("'", ","), "", $rivi_row['kerayskommentti']);

      $pakkauskirjain = strtoupper(chr((64+$rivi_row['pakkausnro'])));
      $tuotteen_nimitys = str_replace(array("'", ","), "", $rivi_row['nimitys']);

      $hyllypaikka = hyllyalue('', $rivi_row["hyllyalue"], 'riisuttu');
      $hyllypaikka = trim($rivi_row['hyllynro'])  != '' ? $hyllypaikka." ".$rivi_row['hyllynro']  : $hyllypaikka;
      $hyllypaikka = trim($rivi_row['hyllyvali']) != '' ? $hyllypaikka." ".$rivi_row['hyllyvali'] : $hyllypaikka;
      $hyllypaikka = trim($rivi_row['hyllytaso']) != '' ? $hyllypaikka." ".$rivi_row['hyllytaso'] : $hyllypaikka;

      $hyllypaikka = implode(" ", str_split(strtoupper(trim($hyllypaikka))));

      // W7 12 80 => W71280 => w71280 => w 7 128 0
      $rivi_row['tuoteno'] = strtoupper(str_replace(" ", "", $rivi_row['tuoteno']));

      $_tmp = str_split($rivi_row['tuoteno']);
      $_cnt = count($_tmp);
      $_arr = array();

      for ($i = 0; $i < $_cnt; $i++) {
        if ($i < $_cnt-1 and !is_numeric($_tmp[$i])) {
          array_push($_arr, $_tmp[$i], " ");
        }
        elseif ($i < $_cnt-1 and is_numeric($_tmp[$i]) and isset($_tmp[$i+1]) and !is_numeric($_tmp[$i+1])) {
          array_push($_arr, $_tmp[$i], " ");
        }
        else {
          array_push($_arr, $_tmp[$i]);
        }
      }

      $rivi_row['tuoteno'] = implode("", $_arr);
      $rivi_row['tuoteno'] = implode(" ,,, ", str_split($rivi_row['tuoteno'], 3));
      $rivi_row['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite");

      $response .= "N,";
      $response .= substr($rivi_row['ker_nimitys'], 0, 255).",";
      $response .= "{$kpl} rivi�,{$rivi_row['nro']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},\"{$rivi_row['tuoteno']}\",{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$rivi_row['kerayseran_tunnus']},{$tuotteen_nimitys},{$rivi_row['kerayskommentti']},0\r\n";

      $n++;
    }
  }

  if ($response == '') {
    $response = "N,,,,,,,,,,,,,1,Ei yht��n ker�yser��\r\n\r\n";
  }
  else {
    $response .= "\r\n";
  }
}
elseif ($sanoma == "PrintContainers") {

  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $nro = (int) trim($sisalto[3]);
  $printer_id = (int) trim($sisalto[4]);

  $query = "SELECT tunnus
            FROM kirjoittimet
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND komento   != 'EDI'
            AND jarjestys  = '{$printer_id}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $reittietikettitulostin = $row['tunnus'];

  $query = "LOCK TABLES kerayserat WRITE";
  $lock_res = pupe_query($query);

  $query = "SELECT *
            FROM kerayserat
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND nro      = {$nro}
            AND tila    != '�'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    $response = "1, Ei printattavaa\r\n\r\n";
  }

  if (trim($response) == '') {
    // Tarkistetaan, ettei ole duplikaattier� joka on jo jollain toisella tyypill� ker�yksess�
    // Sama tilaus ja sama tilausnumero, mutta eri ker�yser� ==> FAIL
    $duplikaatti = 0;
    $riveja_tot  = mysql_num_rows($result);

    while ($row = mysql_fetch_assoc($result)) {
      $query = "SELECT *
                FROM kerayserat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND nro        < {$nro}
                AND tilausrivi = '{$row['tilausrivi']}'
                AND otunnus    = '{$row['otunnus']}'";
      $dupl_res = pupe_query($query);

      if (mysql_num_rows($dupl_res) > 0) {
        // P�ivitet��n duplikaattirivi �-tilaan
        $query = "UPDATE kerayserat
                  SET tila = '�'
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND nro        = {$nro}
                  AND tilausrivi = '{$row['tilausrivi']}'
                  AND otunnus    = '{$row['otunnus']}'";
        $upd_res = pupe_query($query);

        $duplikaatti++;
      }
    }
  }

  $query = "UNLOCK TABLES";
  $lock_res = pupe_query($query);

  // Jos kaikki rivit oli duplikaatteja
  if ($duplikaatti > 0 and $riveja_tot == $duplikaatti) {
    $response = "1, Ei printattavaa\r\n\r\n";
  }

  if (trim($response) == '') {
    // Tulostetaan ker�yser�
    $kerayseran_numero = $nro;

    require "inc/tulosta_reittietiketti.inc";

    $response = "0, Tulostus onnistui\r\n\r\n";
  }
}
elseif ($sanoma == "Picked") {

  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $nro       = (int) trim($sisalto[3]);
  $row_id     = (int) trim($sisalto[4]);
  $qty       = (int) trim($sisalto[5]);
  $package     = mysql_real_escape_string(trim($sisalto[6]));
  $splitlineflag   = (int) trim($sisalto[7]);

  $query = "SELECT *
            FROM kerayserat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nro     = '{$nro}'
            AND tunnus  = '{$row_id}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  // splitataan rivi, splittauksen ensimm�inen rivi
  if ($splitlineflag == 1) {
    $query = "UPDATE kerayserat
              SET kpl   = '{$qty}',
              kpl_keratty = '{$qty}',
              keratty     = '{$kukarow['kuka']}',
              kerattyaika = now()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND nro     = '{$nro}'
              AND tunnus  = '{$row_id}'";
    $upd_res = pupe_query($query);
  }
  // 2 = 2 ... n splitattu rivi
  // 3 = viimeinen splitattu rivi
  elseif ($splitlineflag == 2 or $splitlineflag == 3) {

    $qty_kpl = $qty;

    if ($splitlineflag == 3) {

      $query = "SELECT varattu
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$row['tilausrivi']}'";
      $chk_res = pupe_query($query);
      $chk_row = mysql_fetch_assoc($chk_res);

      $query = "SELECT SUM(kpl_keratty) kappaleet
                FROM kerayserat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND nro        = '{$nro}'
                AND tilausrivi = '{$row['tilausrivi']}'";
      $sum_res = pupe_query($query);
      $sum_row = mysql_fetch_assoc($sum_res);

      if ($chk_row['varattu'] != $sum_row['kappaleet']) {
        $qty_kpl = $chkrow['varattu'] - $sum_row['kappaleet'];
      }
    }

    $fields = "yhtio";
    $values = "'{$kukarow['yhtio']}'";

    $uusi_sscc = "";

    for ($i = 0; $i < mysql_num_fields($result); $i++) {

      $fieldname = mysql_field_name($result, $i);

      if ($fieldname == 'tunnus' or $fieldname == 'yhtio') continue;

      $fields .= ", ".$fieldname;

      switch ($fieldname) {
      case 'tila':
        $values .= ", 'T'";
        break;
      case 'pakkausnro':
        $values .= ", '".(ord($package) - 64)."'";
        break;
      case 'kpl':
        $values .= ", '{$qty_kpl}'";
        break;
      case 'kpl_keratty':
        $values .= ", '{$qty}'";
        break;
      case 'sscc_ulkoinen':
        $values .= ", ''";
        break;
      case 'sscc':
        $query = "LOCK TABLES avainsana WRITE";
        $lock_res = pupe_query($query);

        $query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
        $selite_result = pupe_query($query);
        $selite_row = mysql_fetch_assoc($selite_result);

        $uusi_sscc = is_numeric($selite_row['selite']) ? (int) $selite_row['selite'] + 1 : 1;

        $query = "UPDATE avainsana SET selite = '{$uusi_sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
        $update_res = pupe_query($query);

        // poistetaan lukko
        $query = "UNLOCK TABLES";
        $unlock_res = pupe_query($query);

        $values .= ", '{$uusi_sscc}'";

        break;
      default:
        $values .= ", '".$row[$fieldname]."'";
      }
    }

    $query = "INSERT INTO kerayserat ({$fields}) VALUES ({$values})";
    $insres = pupe_query($query);

    $kerayseran_numero = $nro;

    $query = "SELECT printteri8
              FROM keraysvyohyke
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$row['keraysvyohyke']}'";
    $printteri_res = pupe_query($query);
    $printteri_row = mysql_fetch_assoc($printteri_res);

    $reittietikettitulostin = $printteri_row['printteri8'];

    require 'inc/tulosta_reittietiketti.inc';
  }
  // ei splitata rivi� eli normaali rivi, $splitlineflag == 0
  else {
    $query = "UPDATE kerayserat
              SET kpl_keratty = '{$qty}',
              keratty     = '{$kukarow['kuka']}',
              kerattyaika = now()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND nro     = '{$nro}'
              AND tunnus  = '{$row_id}'";
    $updres = pupe_query($query);
  }

  $response = "0x100";

}
elseif ($sanoma == "StopAssignment") {

  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $nro = (int) trim($sisalto[3]);

  $query = "SELECT COUNT(otunnus) AS kpl, GROUP_CONCAT(otunnus) AS otunnukset
            FROM kerayserat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nro     = '{$nro}'
            ORDER BY sscc";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  if ($row['kpl'] == 0) {
    $response = "1,Virhe ei ker�yser��\r\n\r\n";
  }
  else {

    $maara = $kerivi = $rivin_varattu = $rivin_puhdas_tuoteno = $rivin_tuoteno = $vertaus_hylly = array();

    $query = "SELECT tilausrivi, SUM(kpl) AS kpl, SUM(kpl_keratty) AS kpl_keratty
              FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tila    = 'K'
              AND nro     = '{$nro}'
              GROUP BY tilausrivi";
    $valmis_era_chk_res = pupe_query($query);

    if (mysql_num_rows($valmis_era_chk_res) == 0) {
      $response = "1,Virhe ei ker�yser��\r\n\r\n";
    }
    else {

      while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
        $kerivi[] = $valmis_era_chk_row['tilausrivi'];

        if ($valmis_era_chk_row['kpl'] != $valmis_era_chk_row['kpl_keratty']) {
          $maara[$valmis_era_chk_row['tilausrivi']] = $valmis_era_chk_row['kpl_keratty'];
        }
        else {
          $maara[$valmis_era_chk_row['tilausrivi']] = "";
        }
      }

      $query = "SELECT *
                FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tila    = 'K'
                AND nro     = '{$nro}'";
      $valmis_era_chk_res = pupe_query($query);

      $keraysera_vyohyke = 0;

      while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
        $keraysera_maara[$valmis_era_chk_row['tunnus']] = $valmis_era_chk_row['kpl_keratty'];

        $query = "SELECT tilausrivi.otunnus, tilausrivi.varattu,
                  tilausrivi.tuoteno AS puhdas_tuoteno,
                  concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
                  concat_ws('###',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka_rekla
                  FROM tilausrivi
                  WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivi.tunnus  = '{$valmis_era_chk_row['tilausrivi']}'";
        $varattu_res = pupe_query($query);
        $varattu_row = mysql_fetch_assoc($varattu_res);

        $rivin_varattu[$valmis_era_chk_row['tilausrivi']] = $varattu_row['varattu'];
        $rivin_puhdas_tuoteno[$valmis_era_chk_row['tilausrivi']] = $varattu_row['puhdas_tuoteno'];
        $rivin_tuoteno[$valmis_era_chk_row['tilausrivi']] = $varattu_row['tuoteno'];
        $vertaus_hylly[$valmis_era_chk_row['tilausrivi']] = $varattu_row['varastopaikka_rekla'];

        $keraysera_vyohyke = $valmis_era_chk_row["keraysvyohyke"];
      }

      $query = "SELECT printteri1, printteri3
                FROM keraysvyohyke
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$keraysera_vyohyke}'";
      $printteri_res = pupe_query($query);
      $printteri_row = mysql_fetch_assoc($printteri_res);

      // setataan muuttujat keraa.php:ta varten
      $tee         = "P";
      $toim        = "";
      $id          = $nro;
      $keraajanro  = "";
      $keraajalist = $kukarow['kuka'];

      // vakadr-tulostin on aina sama kuin l�hete-tulostin
      $valittu_tulostin = $vakadr_tulostin = $printteri_row['printteri1'];
      $valittu_oslapp_tulostin = $printteri_row['printteri3'];

      $lahetekpl = $vakadrkpl = $yhtiorow["oletus_lahetekpl"];
      $oslappkpl = $yhtiorow["oletus_oslappkpl"];

      $lasku_yhtio = "";
      $real_submit = "Merkkaa ker�tyksi";

      require 'tilauskasittely/keraa.php';

      $laheteprintterinimi = (isset($laheteprintterinimi) and $laheteprintterinimi != "") ? " ".preg_replace("/[^a-zA-Z������0-9]/", " ", $laheteprintterinimi) : "";
      $dokumenttiteksti = (isset($lahete_tulostus_paperille_vak) and $lahete_tulostus_paperille_vak > 1) ? "dokumenttia" : "dokumentti";

      $print_array = array();

      if (isset($lahete_tulostus_paperille) and $lahete_tulostus_paperille > 0) $print_array[] = "{$lahete_tulostus_paperille} l�hetett�";
      if (isset($lahete_tulostus_paperille_vak) and $lahete_tulostus_paperille_vak > 0) $print_array[] = "{$lahete_tulostus_paperille_vak} vak/adr {$dokumenttiteksti}";

      if (count($print_array) == 0) {
        $response = "99,L�hetteit� ei tulosteta\r\n\r\n";
      }
      else {
        $response = "99,".implode(" ja ", $print_array)." tulostuu kirjoittimelta{$laheteprintterinimi}\r\n\r\n";
      }
    }
  }
}
elseif ($sanoma == "AllPicked") {

  // Napataan kukarow ja yhtorow
  // $yhtiorow = hae_yhtion_parametrit("artr");
  // $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  // $nro = (int) trim($sisalto[3]);

  $response = "Er� valmis,0,\r\n\r\n";
}
elseif ($sanoma == "SignOff") {

  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND laatija = '{$kukarow['kuka']}' AND tila = 'K'";
  $chkres = pupe_query($query);

  if (mysql_num_rows($chkres) > 0) {
    $response = "1,Kaikki rivit ei ole ker�tty\r\n\r\n";
  }
  else {
    $response = "0,\r\n\r\n";
  }
}
elseif ($sanoma == "Replenishment") {

}
elseif ($sanoma == "NewContainer") {

  /**
   * Case1 (Normaali):
   * Kaikki edelliset rivit jotka ker�tty A:han s�ilyv�t kyseisess� alustassa, mutta nykyinen/tulevat rivit menev�t B:hen.
   *
   * Case2 (Jaa rivi):
   * Edelliset rivit + se m��r� jaetusta rivist� ovat alustassa A. Seuraavat rivit + jaetun rivin loput menev�t B:hen.
   */
  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $nro = (int) trim($sisalto[3]);
  $row_id = (int) trim($sisalto[4]);
  $printer_id = (int) trim($sisalto[5]);
  $splitlineflag = (int) trim($sisalto[6]);

  //##### TEHD��N UUSI PAKKAUSKIRJAIN
  $query = "SELECT (MAX(pakkausnro) + 1) uusi_pakkauskirjain FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}'";
  $uusi_paknro_res = pupe_query($query);
  $uusi_paknro_row = mysql_fetch_assoc($uusi_paknro_res);

  $pakkaus_kirjain = chr((64+$uusi_paknro_row['uusi_pakkauskirjain']));

  if ($splitlineflag == 0) {
    //##### TEHD��N UUSI SSCC-NUMERO
    $query = "LOCK TABLES avainsana WRITE";
    $res   = pupe_query($query);

    $query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    $uusi_sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

    $query = "UPDATE avainsana SET selite = '{$uusi_sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
    $update_res = pupe_query($query);

    // poistetaan lukko
    $query = "UNLOCK TABLES";
    $res   = pupe_query($query);
  }

  $query = "SELECT *
            FROM kerayserat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nro     = '{$nro}'
            AND tunnus  = '{$row_id}'";
  $chkres = pupe_query($query);
  $chkrow = mysql_fetch_array($chkres);

  $sscclisa = $splitlineflag == 0 ? " sscc = '{$uusi_sscc}', " : "";

  $query = "UPDATE kerayserat SET
            {$sscclisa}
            pakkausnro     = '{$uusi_paknro_row['uusi_pakkauskirjain']}'
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND tila       = 'K'
            AND pakkausnro = '{$chkrow['pakkausnro']}'
            AND nro        = '{$nro}'";
  $updres = pupe_query($query);

  $query = "SELECT tunnus
            FROM kirjoittimet
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND komento   != 'EDI'
            AND jarjestys  = '{$printer_id}'";
  $printer_chk_res = pupe_query($query);
  $printer_chk_row = mysql_fetch_assoc($printer_chk_res);

  $kerayseran_numero     = $nro;
  $reittietikettitulostin = $printer_chk_row['tunnus'];
  $uusi_pakkauskirjain   = $uusi_paknro_row['uusi_pakkauskirjain'];

  require "inc/tulosta_reittietiketti.inc";

  $response = "{$pakkaus_kirjain},0,\r\n\r\n";
}
elseif ($sanoma == "ChangeContainer") {

  /**
   * Case1 (Normaali):
   * Jos pyydet��n ker�yksen yhteydess� "Vaihda alusta", niin WMS palauttaa Vocollectille pakkauskirjaimen, joka on sallittu (sama asiakas) ja siirt�� sinne joko kyseisen rivin tai kaikki loput ker�yksess� olevat rivit.
   *
   * Case2 (Jaa rivi):
   * Ensimm�iseen laatikkoon laitetaan 5 kpl, jonka j�lkeen halutaan "vaihda alusta",
   * niin WMS ei p�ivit� A-kirjainta, vaan palauttaa Vocollectille pakkauskirjaimen, joka on sallittu (sama asiakas).
   * Toisen jaetun rivin kuittauksen kohdalla p�ivitt�� uudelle ker�ysriville pakkauskirjaimeksi aiemmin valitun pakkauskirjaimen.
   * Ei tulosteta SSCC-koodia.
   */
  // Napataan kukarow ja yhtorow
  $yhtiorow = hae_yhtion_parametrit("artr");
  $kukarow  = hae_kukarow(mysql_real_escape_string(trim($sisalto[2])), $yhtiorow["yhtio"]);

  $nro       = (int) trim($sisalto[3]);
  $row_id     = (int) trim($sisalto[4]);
  $container_id   = trim($sisalto[5]);
  $all       = trim($sisalto[6]);

  // haetaan ker�tt�v� ker�ysrivi
  $query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
  $result = pupe_query($query);
  $orig_row = mysql_fetch_assoc($result);

  // haetaan ker�tt�v�n ker�ysrivin tilauksen tiedot
  $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$orig_row['otunnus']}'";
  $laskures = pupe_query($query);
  $laskurow = mysql_fetch_assoc($laskures);

  // tehd��n pakkauskirjaimesta numero
  $pakkaus_kirjain_chk = ord($container_id) - 64;

  $query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$orig_row['nro']}' AND pakkausnro = '{$pakkaus_kirjain_chk}' ORDER BY RAND()";
  $result = pupe_query($query);

  $response = "1,Uusi valittu pakkaus ei k�y\r\n\r\n";

  while ($row = mysql_fetch_assoc($result)) {

    $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['otunnus']}'";
    $chkres = pupe_query($query);
    $chkrow = mysql_fetch_assoc($chkres);

    if ($chkrow['liitostunnus'] == $laskurow['liitostunnus']) {
      $pakkaus_kirjain = chr(64+$row['pakkausnro']);

      if ($all != '') {
        if ($all == 1) {
          $query = "UPDATE kerayserat
                    SET pakkausnro = '{$pakkaus_kirjain_chk}'
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND nro        = '{$nro}'
                    AND tila       = 'K'
                    AND pakkausnro = '{$orig_row['pakkausnro']}'
                    AND sscc       = '{$orig_row['sscc']}'";
          $updres = pupe_query($query);
        }
        else {
          $query = "UPDATE kerayserat
                    SET pakkausnro = '{$pakkaus_kirjain_chk}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND nro     = '{$nro}'
                    AND tunnus  = '{$row_id}'";
          $updres = pupe_query($query);
        }
      }

      $response = "{$pakkaus_kirjain},0,\r\n\r\n";
      break;
    }
  }
}
else {
  $response = "1, Kui s�� t�nne jouduit?\r\n\r\n";
}

$fleur = ob_get_contents();
$fleur = ($fleur != "") ? $fleur."\n" : "";

$responseaika_loppu     = date("Y-m-d H:i:s");
$responseaika_loppu_sek = time();
$responseaika_sek_error = "";

if ($responseaika_loppu_sek-$responseaika_alku_sek >= 20) {
  $responseaika_sek_error = "-----------------TIMEOUT:".sprintf("%011d", $responseaika_loppu_sek-$responseaika_alku_sek)."-----------------\n";
}

// Onko nagios monitor asennettu?
if (file_exists("/home/optiscan/optiscan.log")) {
  // Noticet veks lokista
  $fleur = str_replace("<br>", "\n", preg_replace("/Notice:.*\n(\n)?/", "", $fleur));

  file_put_contents("/home/optiscan/optiscan.log", "------------------------START------------------------\n-----------------$responseaika_alku-----------------\n$kukarow[kuka]: {$lines[0]}\npupe: ".trim($response)."\n$fleur-----------------$responseaika_loppu-----------------\n$responseaika_sek_error------------------------STOP-------------------------\n\n", FILE_APPEND);
}

ob_end_clean();

echo $response;
