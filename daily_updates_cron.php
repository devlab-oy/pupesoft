<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

function listdir($start_dir = '.') {

  $files = array();

  if (is_dir($start_dir)) {

    $fh = opendir($start_dir);

    while (($file = readdir($fh)) !== false) {
      if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file, 0, 1) == ".") {
        continue;
      }
      $filepath = $start_dir . '/' . $file;

      if (is_dir($filepath)) {
        $files = array_merge($files, listdir($filepath));
      }
      else {
        array_push($files, $filepath);
      }
    }
    closedir($fh);
    sort($files);
  }
  else {
    $files = false;
  }

  return $files;
}


// loopattava dirikka
$dir = '/home/unikko/unikkodata';

// minne luetut tiedostot siirret‰‰n talteen
$safeplace = '/home/unikko/unikkodata/vanhat';

// k‰yd‰‰n l‰pi ensin k‰sitelt‰v‰t tiedostot
$files = listdir($dir);

/*
  #### Tiedostot t‰rkeysj‰rjestyksess‰ ylh‰‰lt‰ alas ####

  php skriptit/unikko_myyntihistoria.php Orum_konversiotestaus/paivittainen/laotsikko.txt Orum_konversiotestaus/paivittainen/larivi.txt artr orum-paivittainen
  php skriptit/unikko_myyntihistoria_lalisa.php Orum_konversiotestaus/paivittainen/lalisa.txt artr orum-paivittainen
  php skriptit/unikko_ostohistoria_keikka.php Orum_konversiotestaus/paivittainen/tuootsikko.txt Orum_konversiotestaus/paivittainen/tuorivi.txt Orum_konversiotestaus/paivittainen/vrpvk.txt artr orum-paivittainen
  php skriptit/unikko_ostohistoria_asn.php Orum_konversiotestaus/paivittainen/tulrivi.txt artr orum-paivittainen
  php skriptit/unikko_tuotteen_tapahtumat.php Orum_konversiotestaus/paivittainen/vrpvk.txt artr orum-paivittainen
  */

$_priority = array(
  'laotsikko.txt'   => 'php /home/devlab/turvata/skriptit/unikko_myyntihistoria.php /home/unikko/unikkodata/laotsikko.txt /home/unikko/unikkodata/larivi.txt artr orum-paivittainen',
  'lalisa.txt'    => 'php /home/devlab/turvata/skriptit/unikko_myyntihistoria_lalisa.php /home/unikko/unikkodata/lalisa.txt artr orum-paivittainen',
  'tuootsikko.txt'  => 'php /home/devlab/turvata/skriptit/unikko_ostohistoria_keikka.php /home/unikko/unikkodata/tuootsikko.txt /home/unikko/unikkodata/tuorivi.txt /home/unikko/unikkodata/vrpvk.txt artr orum-paivittainen',
  'tulrivi.txt'    => 'php /home/devlab/turvata/skriptit/unikko_ostohistoria_asn.php /home/unikko/unikkodata/tulrivi.txt artr orum-paivittainen',
  'vrpvk.txt'      => 'php /home/devlab/turvata/skriptit/unikko_tuotteen_tapahtumat.php /home/unikko/unikkodata/vrpvk.txt artr orum-paivittainen'
);

$_priority_checklist = array(
  'laotsikko.txt'   => false,
  'lalisa.txt'    => false,
  'tuootsikko.txt'  => false,
  'tulrivi.txt'    => false,
  'vrpvk.txt'      => false
);

$tuorivi_exists = false;
$larivi_exists = false;

foreach ($files as $file_x) {
  $path_parts = pathinfo($file_x);

  if ($path_parts['basename'] == 'tuorivi.txt') {
    $tuorivi_exists = true;
  }

  if ($path_parts['basename'] == 'larivi.txt') {
    $larivi_exists = true;
  }

  if (array_key_exists($path_parts['basename'], $_priority_checklist)) {
    $_priority_checklist[$path_parts['basename']] = true;
  }
}

if ($_priority_checklist['laotsikko.txt'] and !$larivi_exists) {
  $_priority_checklist['laotsikko.txt'] = false;
}

// jos laotsikkoa ei lˆydy, ei lueta lalisaa
if (!$_priority_checklist['laotsikko.txt']) {
  $_priority_checklist['lalisa.txt'] = false;
}

// jos vrpk ei lˆydy, ei lueta tuootsikkoa, koska se tarvitsee vrpkta
if (!$_priority_checklist['vrpvk.txt']) {
  $_priority_checklist['tuootsikko.txt'] = false;
}

// jos tuootsikko lˆytyy, mutta tuorivi ei lˆydy, ei lueta tuootsikkoa
if ($_priority_checklist['tuootsikko.txt'] and !$tuorivi_exists) {
  $_priority_checklist['tuootsikko.txt'] = false;
}

foreach ($_priority as $prio_file => $prio_script) {

  foreach ($files as $file) {
    $path_parts = pathinfo($file);

    if ($path_parts['basename'] == $prio_file and $_priority_checklist[$path_parts['basename']]) {
      // ajetaan skripta
      // ajetaan vain laskut
      if ($prio_file == 'laotsikko.txt' or $prio_file == 'lalisa.txt' or $prio_file == 'tuootsikko.txt' or $prio_file == 'tulrivi.txt' or $prio_file == 'vrpvk.txt') {
        passthru($prio_script);
      }

      // siirret‰‰n file muualle talteen
      $_newfile = date('Ymd').'_'.$path_parts['basename'];
      passthru("mv {$dir}/{$path_parts['basename']} {$safeplace}/{$_newfile}");

      if ($prio_file == 'laotsikko.txt') {
        $_newfile = date('Ymd').'_larivi.txt';
        passthru("mv {$dir}/larivi.txt {$safeplace}/{$_newfile}");
      }
      elseif ($prio_file == 'tuootsikko.txt') {
        $_newfile = date('Ymd').'_tuorivi.txt';
        passthru("mv {$dir}/tuorivi.txt {$safeplace}/{$_newfile}");
      }

      break;
    }
  }
}
