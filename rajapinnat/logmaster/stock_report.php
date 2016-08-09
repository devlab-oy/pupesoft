<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut sähköpostiosoitetta!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

$path = trim($argv[2]);
$error_email = trim($argv[3]);

$path = rtrim($path, '/').'/';
$handle = opendir($path);

if ($handle === false) {
  exit;
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'StockReport') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('stock_report', "Käsitellään sanoma {$file}");

  // tuki vain yhdelle Posten-varastolle
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio              = '{$kukarow['yhtio']}'
            AND ulkoinen_jarjestelma = 'P'
            LIMIT 1";
  $varastores = pupe_query($query);
  $varastorow = mysql_fetch_assoc($varastores);

  $luontiaika = $xml->InvCounting->TransDate;

  unset($xml->InvCounting->TransDate);

  $saldoeroja = array();

  foreach ($xml->InvCounting->Line as $line) {
    $eankoodi = $line->ItemNumber;
    $kpl = (float) $line->Quantity;

    $query = "SELECT tuoteno, nimitys
              FROM tuote
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND eankoodi = '{$eankoodi}'";
    $tuoteres = pupe_query($query);
    $tuoterow = mysql_fetch_assoc($tuoteres);

    list($saldo, $hyllyssa, $myytavissa, $devnull) = saldo_myytavissa($tuoterow["tuoteno"], "KAIKKI", $varastorow['tunnus']);

    // Etukäteen maksetut tilaukset, jotka ovat keräämättä mutta tilaus jo laskutettu
    // Lasketaan ne mukaan Pupen hyllyssä määrään, koska saldo_myytavissa ei huomioi niitä
    $query = "SELECT ifnull(sum(tilausrivi.kpl), 0) AS keraamatta
              FROM tilausrivi
              INNER JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                AND lasku.tunnus          = tilausrivi.otunnus
                AND lasku.mapvm          != '0000-00-00'
                AND lasku.chn             = '999')
              WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi       = 'L'
              AND tilausrivi.var         != 'P'
              AND tilausrivi.keratty      = ''
              AND tilausrivi.kerattyaika  = '0000-00-00 00:00:00'
              AND tilausrivi.tuoteno      = '{$tuoterow['tuoteno']}'";
    $ker_result = pupe_query($query);
    $ker_rivi = mysql_fetch_assoc($ker_result);

    $hyllyssa += $ker_rivi['keraamatta'];

    // Vertailukonversio
    $a = (int) $kpl * 10000;
    $b = (int) $hyllyssa * 10000;

    if ($a != $b) {
      $saldoeroja[$tuoterow['tuoteno']]['posten'] = $kpl;
      $saldoeroja[$tuoterow['tuoteno']]['pupe'] = $hyllyssa;
      $saldoeroja[$tuoterow['tuoteno']]['nimitys'] = $tuoterow['nimitys'];
    }
  }

  if (count($saldoeroja) > 0) {
    $body  = t("Seuraavien tuotteiden saldovertailuissa on havaittu eroja").":<br><br>\n\n";
    $body .= t("Tuoteno").";".t("Nimitys").";".t("Posten").";".t("Pupe")."<br>\n";

    foreach ($saldoeroja as $tuoteno => $_arr) {
      $body .= "{$tuoteno};{$_arr['nimitys']};{$_arr['posten']};{$_arr['pupe']}<br>\n";
    }

    $params = array(
      'to' => $error_email,
      'cc' => '',
      'subject' => t("Posten saldovertailu")." - {$luontiaika}",
      'ctype' => 'html',
      'body' => $body,
    );

    pupesoft_sahkoposti($params);

    pupesoft_log('stock_report', "Saldovertailussa eroja, lähetetään sähköposti {$error_email}");
  }

  // siirretään tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);

  pupesoft_log('stock_report', "Saldovertailu käsitelty");
}

closedir($handle);
