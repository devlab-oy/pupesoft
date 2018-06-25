<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

$yhtio = $argv[1];

if (isset($argv[2]) and $argv[2] != '') {
  $customer_laajennus = true;
}
else {
  $customer_laajennus = false;
}

ini_set("memory_limit", "1G");

// otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$path = "/tmp/logisticar_siirto_$yhtio/";

// Sivotaan eka vanha pois
system("rm -rf $path");

// Teh‰‰n uysi dirikka
system("mkdir $path");

$vuosilisa = "";

$where_logisticar = $logisticar[$yhtio]["where"];

if ($where_logisticar["vuosi_ajo"] != "") {
  $vuosilisa = (int) $where_logisticar["vuosi_ajo"];
}

$path_nimike     = $path . "ITEM{$vuosilisa}.txt";
$path_asiakas    = $path . "CUSTOMER{$vuosilisa}.txt";
$path_toimittaja = $path . "VENDOR{$vuosilisa}.txt";
$path_varasto    = $path . "BALANCE{$vuosilisa}.txt";
$path_tapahtumat = $path . "TRANSACTION{$vuosilisa}.txt";
$path_myynti     = $path . "ORDER{$vuosilisa}.txt";

$yhtiorow = hae_yhtion_parametrit($yhtio);

// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " not in ('P') ";
}
else {
  $lisavarattu = " not in ('P','J','S') ";
}

echo "Logisticar siirto: $yhtio\n";

//testausta varten limit
$limit = "";
//$limit = "limit 200";

// Ajetaan kaikki operaatiot
nimike($limit);
asiakas($limit);
toimittaja($limit);
varasto($limit);
varastotapahtumat($limit);
myynti($limit);

//Siirret‰‰n failit logisticar palvelimelle
//siirto($path);
ftpsiirto($path);

// sambajakosiirto, ei k‰ytˆss‰ t‰ll‰ hetkell‰.
function siirto($path) {
  global $logisticar, $yhtio;

  $path_localdir    = "/mnt/logisticar_siirto/";
  $user_logisticar = $logisticar[$yhtio]["user"];
  $pass_logisticar = $logisticar[$yhtio]["pass"];
  $host_logisticar = $logisticar[$yhtio]["host"];
  $path_logisticar = $logisticar[$yhtio]["path"];

  unset($retval);
  system("mount -t cifs -o username=$user_logisticar,password=$pass_logisticar //$host_logisticar/$path_logisticar $path_localdir", $retval);

  if ($retval != 0) {
    echo "Mount failed! $retval\n";
  }
  else {

    unset($retval);
    system("cp -f $path/* $path_localdir", $retval);

    if ($retval != 0) {
      echo "Copy failed! $retval\n";
    }

    unset($retval);
    system("umount $path_localdir", $retval);

    if ($retval != 0) {
      echo "Unmount failed! $retval\n";
    }
  }
}

// ftp-siirto
function ftpsiirto($path) {
  global $logisticar, $yhtio;

  if ($handle = opendir($path)) {
    while (($file = readdir($handle)) !== FALSE) {
      if (is_file($path."/".$file)) {
        // tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
        // palautetaan $palautus ja $syy
        $ftphost  = $logisticar[$yhtio]["host"];
        $ftpuser  = $logisticar[$yhtio]["user"];
        $ftppass  = $logisticar[$yhtio]["pass"];
        $ftppath  = $logisticar[$yhtio]["path"];
        $ftpport  = "";
        $ftpfail  = "";
        $ftpsucc  = "";
        $ftpfile  = realpath($path."/".$file);
        $ftptmpr  = FALSE;

        require "inc/ftp-send.inc";
      }
    }
  }
}

function nimike($limit = '') {
  global $path_nimike, $yhtio, $logisticar;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Tuotteet ...";

  $query = "SELECT
            tuote.tuoteno      nimiketunnus,
            tuote.nimitys      nimitys,
            tuote.yksikko      yksikko,
            tuote.try        tuoteryhma,
            avainsana.selitetark  tuoteryhma_nimi,
            tuote.kustp        kustannuspaikka,
            ''            toimittajatunnus,
            '0'            varastotunnus,
            '0'            toimittajannimiketunnus,
            '1'            hintayksikko,
            if(tuote.status = 'T', '0', '1') varastoimiskoodi,
            tuote.tuotetyyppi    nimikelaji,
            kuka.kuka        ostaja,
            tuote.tuotemassa    paino,
            tuote.status      status,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm
            FROM tuote
            LEFT JOIN avainsana ON (avainsana.selite = tuote.try AND avainsana.yhtio = tuote.yhtio AND tuote.try not in ('','0'))
            LEFT JOIN kuka ON (kuka.myyja = tuote.ostajanro AND kuka.yhtio = tuote.yhtio AND kuka.myyja > 0)
            WHERE tuote.yhtio     = '$yhtio'
            AND tuote.tuotetyyppi NOT IN ('A','B')
            $limit";
  $rest = pupe_query($query);
  $rows = mysql_num_rows($rest);

  if ($rows == 0) {
    echo "Yht‰‰n tuotetta ei lˆytynyt $query\n";
    die();
  }

  $fp = fopen($path_nimike, 'w+');

  $row = 0;

  $headers = array(
    'nimiketunnus'            => null,
    'nimitys'                 => null,
    'yksikko'                 => null,
    'tuoteryhma'              => null,
    'kustannuspaikka'         => null,
    'toimittajatunnus'        => null,
    'varastotunnus'           => null,
    'toimittajannimiketunnus' => null,
    'hintayksikko'            => null,
    'varastoimiskoodi'        => null,
    'nimikelaji'              => null,
    'ostaja'                  => null,
    'paino'                   => null,
    'status'                  => null,
    'epakurantti25pvm'        => null,
    'epakurantti50pvm'        => null,
    'epakurantti75pvm'        => null,
    'epakurantti100pvm'       => null
  );

  create_headers($fp, array_keys($headers));

  while ($tuote = mysql_fetch_assoc($rest)) {

    // mones t‰m‰ on
    $row++;

    $query = "SELECT
              liitostunnus toimittajatunnus,
              toim_tuoteno toimittajannimiketunnus
              FROM tuotteen_toimittajat
              WHERE tuoteno = '{$tuote['nimiketunnus']}'
              AND yhtio     = '$yhtio'
              LIMIT 1";
    $tuot_toim_res = pupe_query($query);
    $tuot_toim_row = mysql_fetch_assoc($tuot_toim_res);

    $query = "SELECT hyllyalue, hyllynro
              FROM tuotepaikat
              WHERE tuoteno  = '{$tuote['nimiketunnus']}'
              AND oletus    != ''
              AND yhtio      = '$yhtio'
              LIMIT 1";
    $res = pupe_query($query);
    $paikka = mysql_fetch_assoc($res);

    // mik‰ varasto
    $tuote['varastotunnus']           = kuuluukovarastoon($paikka['hyllyalue'], $paikka['hyllynro']);
    $tuote['toimittajatunnus']        = $tuot_toim_row['toimittajatunnus'];
    $tuote['toimittajannimiketunnus'] = $tuot_toim_row['toimittajannimiketunnus'];

    // Siivotaan kent‰t:
    foreach ($tuote as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $tuote);
    $data = implode("\t", $data);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function asiakas($limit = '') {
  global $path_asiakas, $yhtio, $logisticar, $customer_laajennus;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Asiakkaat...";

  if ($customer_laajennus){

    $query = "SELECT
              asiakas.tunnus    asiakastunnus,
              concat_ws(' ', asiakas.nimi, asiakas.nimitark)  asiakkaannimi,
              asiakas.ryhma    asiakasryhma,
              kuka.kuka       myyjatunnus,
              if(asiakas.toim_postino !='', asiakas.toim_postino, asiakas.postino) postinumero,
              if(asiakas.toim_postitp !='', asiakas.toim_postitp, asiakas.postitp) toimituspostitp,
              if(asiakas.toim_maa !='', asiakas.toim_maa, asiakas.maa) toimitusmaa,
              if(asiakas.toim_nimitark !='', asiakas.toim_nimitark, asiakas.nimitark) nimitarkenne
              FROM asiakas
              LEFT JOIN kuka ON kuka.myyja=asiakas.myyjanro and kuka.yhtio=asiakas.yhtio and kuka.myyja > 0
              where asiakas.yhtio='$yhtio'
              $limit";

  }
  else {
    $query = "SELECT
              asiakas.tunnus    asiakastunnus,
              concat_ws(' ', asiakas.nimi, asiakas.nimitark)  asiakkaannimi,
              asiakas.ryhma    asiakasryhma,
              kuka.kuka       myyjatunnus,
              asiakas.postino  postinumero,
              asiakas.toim_postitp toimituspostitp,
              asiakas.toim_maa toimitusmaa
              FROM asiakas
              LEFT JOIN kuka ON kuka.myyja=asiakas.myyjanro and kuka.yhtio=asiakas.yhtio and kuka.myyja > 0
              where asiakas.yhtio='$yhtio'
              $limit";
  }
  $rest = pupe_query($query);

  $rows = mysql_num_rows($rest);
  $row = 0;

  if ($rows == 0) {
    echo "Yht‰‰n asiakasta ei lˆytynyt\n";
    die();
  }

  $fp = fopen($path_asiakas, 'w+');

  if ($customer_laajennus) {
    $headers = array(
      'asiakastunnus'  => null,
      'asiakkaannimi'  => null,
      'asiakasryhma'   => null,
      'myyjatunnus'    => null,
      'postinumero'    => null,
      'toimituspostitp' => null,
      'toimitusmaa'    => null,
      'nimitarkenne'   => null
    );
  }
  else {
    $headers = array(
      'asiakastunnus'  => null,
      'asiakkaannimi'  => null,
      'asiakasryhma'   => null,
      'myyjatunnus'    => null,
      'postinumero'    => null,
      'toimituspostitp' => null,
      'toimitusmaa'    => null
    );
  }

  create_headers($fp, array_keys($headers));

  while ($asiakas = mysql_fetch_assoc($rest)) {
    $row++;

    // Siivotaan kent‰t:
    foreach ($asiakas as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $asiakas);
    $data = implode("\t", $data);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function toimittaja($limit = '') {
  global $path_toimittaja, $yhtio, $logisticar;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Toimittajat...";

  $query = "SELECT
            tunnus              toimittajatunnus,
            concat_ws(' ', nimi, nimitark)  toimittajannimi
            from toimi
            where yhtio='$yhtio'
            $limit";
  $rest = pupe_query($query);

  $rows = mysql_num_rows($rest);
  $row = 0;
  if ($rows == 0) {
    echo "Yht‰‰n toimittajaa ei lˆytynyt\n";
    die();
  }

  $fp = fopen($path_toimittaja, 'w+');

  $headers = array(
    'toimittajatunnus'  => null,
    'toimittajannimi'   => null
  );

  create_headers($fp, array_keys($headers));

  while ($toimittaja = mysql_fetch_assoc($rest)) {
    $row++;

    // Siivotaan kent‰t:
    foreach ($toimittaja as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $toimittaja);
    $data = implode("\t", $data);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function varasto($limit = '') {
  global $path_varasto, $lisavarattu, $yhtio, $logisticar;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Varasto... ";

  $fp = fopen($path_varasto, 'w+');

  $query = "SELECT
            tuotepaikat.tuoteno nimiketunnus,
            sum(tuotepaikat.saldo) saldo,
            tuote.kehahin keskihinta,
            '0' tilattu,
            '0' varattu,
            varastopaikat.tunnus varastotunnus,
            (SELECT tuotteen_toimittajat.toimitusaika FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = '$yhtio' AND tuotteen_toimittajat.tuoteno = tuotepaikat.tuoteno AND tuotteen_toimittajat.toimitusaika != '' LIMIT 1) toimitusaika
            FROM tuotepaikat
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            JOIN tuote ON tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio
            WHERE tuote.ei_saldoa      = ''
            AND tuotepaikat.yhtio      = '$yhtio'
            $where_logisticar[varasto_1]
            GROUP BY 1,3,4,5,6,7
            ORDER BY 1
            $limit";
  $res = pupe_query($query);

  $rows = mysql_num_rows($res);
  $row = 0;
  if ($rows == 0) {
    echo "Yht‰‰n varastoa ei lˆytynyt\n";
    die();
  }

  $headers = array(
    'nimiketunnus',
    'saldo',
    'keskihinta',
    'tilattu',
    'varattu',
    'varastotunnus',
    'toimitusaika'
  );

  // tehd‰‰n otsikot
  create_headers($fp, $headers);

  while ($trow = mysql_fetch_assoc($res)) {
    $row++;

    $query = "SELECT
              sum(if(tilausrivi.tyyppi='O', tilausrivi.varattu, 0)) tilattu,
              sum(if((tilausrivi.tyyppi='L' or tilausrivi.tyyppi='V') and tilausrivi.var $lisavarattu, tilausrivi.varattu, 0)) varattu
              FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
              WHERE yhtio        = '$yhtio'
               and tyyppi        in ('L','V','O','G')
              and tuoteno        = '{$trow['nimiketunnus']}'
              and laskutettuaika = '0000-00-00'";
    $result = pupe_query($query);
    $ennp = mysql_fetch_assoc($result);

    $trow['tilattu'] = $ennp['tilattu'];
    $trow['varattu'] = $ennp['varattu'];

    // Siivotaan kent‰t:
    foreach ($trow as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $trow);
    $data = implode("\t", $trow);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function varastotapahtumat($limit = '') {
  global $path_tapahtumat, $yhtio, $logisticar;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Varastotapahtumat... ";

  if (! $fp = fopen($path_tapahtumat, 'w+')) {
    die("Ei voitu avata filea $path_tapahtumat");
  }

  if ($where_logisticar["paiva_ajo"] != "") {
    $pvmlisa = " and date_format(tapahtuma.laadittu, '%Y-%m-%d') >= date_sub(now(), interval 30 day) ";
  }
  elseif ($where_logisticar["vuosi_ajo"] != "") {
    $pvmlisa = " and date_format(tapahtuma.laadittu, '%Y-%m-%d') >= date_sub(now(), interval 1 year) ";
  }
  else {
    $pvmlisa = " and date_format(tapahtuma.laadittu, '%Y-%m-%d') > '0000-00-00 00:00:00' ";
  }

  $query = "SELECT
            tapahtuma.tuoteno       nimiketunnus,
            if(tapahtuma.laji = 'siirto', lasku.clearing, lasku.liitostunnus) asiakastunnus,
            if(tapahtuma.laji = 'siirto', lasku.clearing, lasku.liitostunnus) toimitusasiakas,
            date_format(tapahtuma.laadittu, '%Y-%m-%d') tapahtumapaiva,
            tapahtuma.laji             tapahtumalaji,
            (tapahtuma.kplhinta * tapahtuma.kpl * -1) myyntiarvo,
            (tapahtuma.kplhinta * tapahtuma.kpl * -1) ostoarvo,
            (tapahtuma.kpl * (tapahtuma.kplhinta - tapahtuma.hinta) * -1) kate,
            tapahtuma.kpl         tapahtumamaara,
            lasku.laskunro              laskunumero,
            kuka.kuka                  myyjatunnus,
            lasku.yhtio_toimipaikka    toimipaikka,
            varastopaikat.tunnus        varastotunnus,
            tapahtuma.laji        tapahtumatyyppi
            FROM tapahtuma
            LEFT JOIN tilausrivi USE INDEX (PRIMARY) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
            LEFT JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
            LEFT JOIN tuotepaikat USE INDEX (yhtio_tuoteno_paikka) ON (  tuotepaikat.yhtio    = tapahtuma.yhtio and
                                          tuotepaikat.tuoteno   = tapahtuma.tuoteno and
                                          tuotepaikat.hyllyvali = tapahtuma.hyllyvali and
                                          tuotepaikat.hyllytaso = tapahtuma.hyllytaso and
                                          tuotepaikat.hyllyalue = tapahtuma.hyllyalue and
                                          tuotepaikat.hyllynro  = tapahtuma.hyllynro)
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus                          = tuotepaikat.varasto)
            LEFT JOIN kuka ON kuka.tunnus=lasku.myyja and kuka.yhtio=lasku.yhtio
            WHERE tapahtuma.laji                                in ('tulo', 'laskutus', 'siirto', 'valmistus', 'kulutus')
            and tapahtuma.yhtio                                 = '$yhtio'
            $pvmlisa
            ORDER BY tapahtumapaiva, nimiketunnus ASC
            $limit";
  $res = pupe_query($query);

  $rows = mysql_num_rows($res);
  $row = 0;
  if ($rows == 0) {
    echo "Yht‰‰n varastotapahtumaa ei lˆytynyt\n";
    die();
  }

  $headers = array(
    'nimiketunnus'    => null,
    'asiakastunnus'   => null,
    'toimitusasiakas' => null,
    'tapahtumapaiva'  => null,
    'tapahtumalaji'   => null,
    'myyntiarvo'      => null,
    'ostoarvo'        => null,
    'tapahtumamaara'  => null,
    'laskunumero'     => null,
    'myyjatunnus'     => null,
    'toimipaikka'    => null,
    'varastotunnus'   => null,
    'tapahtumatyyppi' => null
  );

  // tehd‰‰n otsikot
  create_headers($fp, array_keys($headers));

  while ($trow = mysql_fetch_assoc($res)) {
    $row++;

    switch (strtolower($trow['tapahtumalaji'])) {
      // ostot
    case 'tulo':

      // 1 = saapuminen tai oston palautus
      $trow['tapahtumalaji'] = 1;
      $trow['tapahtumatyyppi'] = 'O';

      // myyntiarvo on 0
      $trow['myyntiarvo'] = 0;
      $trow['ostoarvo'] = (-1 * $trow['ostoarvo']);

      // jos kpl alle 0 niin t‰m‰ on oston palautus
      // jolloin hinta myˆs miinus
      if ($trow['tapahtumamaara'] < 0) {
        // tapahtumamaara on aina positiivinen logisticarissa
        $trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
      }

      break;

      // myynnit
    case 'laskutus':

      // 2 = otto tai myynninpalautus
      $trow['tapahtumalaji'] = 2;
      $trow['tapahtumatyyppi'] = 'L';

      // ostoarvo
      $trow['ostoarvo'] = $trow['myyntiarvo'] - $trow['kate'];

      // t‰m‰ on myynninpalautus eli myyntiarvo on negatiivinen
      if ($trow['tapahtumamaara'] < 0) {
        // tapahtumamaara on aina positiivinen logisticarissa
        $trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
      }

      break;

      // varastosiirrot
    case 'siirto':

      if ($trow['tapahtumamaara'] < 0) {
        $trow['tapahtumalaji'] = 2;
        $trow['tapahtumatyyppi'] = 'S';
        $trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
      }
      else {
        $trow['tapahtumalaji'] = 1;
        $trow['tapahtumatyyppi'] = 'S';

        $trow['toimitusasiakas'] = $trow['varastotunnus'];
        $trow['varastotunnus'] = $trow['asiakastunnus'];
      }


    }

    unset($trow['kate']);

    // Siivotaan kent‰t:
    foreach ($trow as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $trow);
    $data = implode("\t", $data);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function myynti($limit = '') {
  global $path_myynti, $yhtiorow, $yhtio, $logisticar;

  $where_logisticar = $logisticar[$yhtio]["where"];

  echo "Myynnit... ";

  if (! $fp = fopen($path_myynti, 'w+')) {
    die("Ei voitu avata filea $path_myynti");
  }

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT
            tilausrivi.tuoteno nimiketunnus,
            lasku.liitostunnus asiakastunnus,
            lasku.liitostunnus toimitusasiakas,
            if(tilausrivi.tyyppi = 'G', lasku.clearing, lasku.liitostunnus) asiakastunnus,
            if(tilausrivi.tyyppi = 'G', lasku.clearing, lasku.liitostunnus) toimitusasiakas,
            tilausrivi.toimaika toimituspaiva,
            tilausrivi.tyyppi tapahtumalaji,
            round(tilausrivi.hinta  / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) myyntiarvo,
            (tilausrivi.varattu+tilausrivi.jt) * tuote.kehahin ostoarvo,
            tilausrivi.varattu tapahtumamaara,
            lasku.tunnus tilausnro,
            kuka.kuka myyjatunnus,
            lasku.yhtio_toimipaikka  toimipaikka,
            varastopaikat.tunnus varastotunnus,
            tilausrivi.toimitettu
            FROM tilausrivi
            JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.otunnus and lasku.yhtio=tilausrivi.yhtio
            JOIN tuote ON tuote.tuoteno = tilausrivi.tuoteno and tuote.yhtio = tilausrivi.yhtio
            JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus     = tuotepaikat.varasto)
            LEFT JOIN kuka ON kuka.tunnus=lasku.myyja and kuka.yhtio=lasku.yhtio
            WHERE tilausrivi.varattu      != 0
            AND tilausrivi.tyyppi          IN ('L','O','G')
            AND tilausrivi.laskutettuaika  = '0000-00-00'
            AND tilausrivi.yhtio           = '$yhtio'
            ORDER BY tilausrivi.laadittu
            $limit";
  $res = pupe_query($query);

  $rows = mysql_num_rows($res);
  $row = 0;
  if ($rows == 0) {
    echo "Yht‰‰n myyntitapahtumaa ei lˆytynyt\n";
    die();
  }

  $headers = array(
    'nimiketunnus'    => null,
    'asiakastunnus'   => null,
    'toimitusasiakas' => null,
    'toimituspaiva'   => null,
    'tapahtumalaji'   => null,
    'myyntiarvo'      => null,
    'ostoarvo'        => null,
    'tapahtumamaara'  => null,
    'tilausnro'       => null,
    'myyjatunnus'     => null,
    'toimipaikka'    => null,
    'varastotunnus'   => null
  );

  // tehd‰‰n otsikot
  create_headers($fp, array_keys($headers));

  while ($trow = mysql_fetch_assoc($res)) {
    $row++;

    switch (strtoupper($trow['tapahtumalaji'])) {
    case 'G':
      // kirjoitetaan fileen vain avoimia siirtolistoja eli toimitettu pit‰‰ olla tyhj‰‰
      if ($trow['toimitettu'] != '') {
        continue;
      }

      // laji 3 = saapuva tilaus
      $trow['tapahtumalaji']   = '3';
      $trow['myyntiarvo']   = 0;

      $trow['toimitusasiakas'] = $trow['varastotunnus'];
      $trow['varastotunnus'] = $trow['asiakastunnus'];

      // ei haluta toimitettu-saraketta mukaan
      unset($trow['toimitettu']);

      // Siivotaan kent‰t:
      foreach ($trow as &$tk) {
        $tk = pupesoft_csvstring($tk);
      }

      $data = array_merge($headers, $trow);
      $data = implode("\t", $data);

      if (! fwrite($fp, $data . "\n")) {
        echo "Failed writing row.\n";
        die();
      }

      // laji 4 = varattu
      $trow['tapahtumalaji'] = '4';

      $trow['varastotunnus'] = $trow['toimitusasiakas'];
      $trow['toimitusasiakas'] = $trow['asiakastunnus'];

      break;
    case 'L':
      $trow['tapahtumalaji']  = '4';

      // ei haluta toimitettu-saraketta mukaan
      unset($trow['toimitettu']);
      break;
    case 'O':
      $trow['tapahtumalaji']   = '3';
      $trow['myyntiarvo']   = 0;

      // ei haluta toimitettu-saraketta mukaan
      unset($trow['toimitettu']);
      break;
    }

    // Siivotaan kent‰t:
    foreach ($trow as &$tk) {
      $tk = pupesoft_csvstring($tk);
    }

    $data = array_merge($headers, $trow);
    $data = implode("\t", $data);

    if (! fwrite($fp, $data . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  fclose($fp);
  echo "Done.\n";
}

function create_headers($fp, array $cols) {
  $data = implode("\t", $cols) . "\n";
  fwrite($fp, $data);
}
