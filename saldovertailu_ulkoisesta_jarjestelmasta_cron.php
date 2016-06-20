<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l‰hett‰v‰‰ yhtiˆt‰!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut s‰hkˆpostiosoitetta!\n");
}

// lis‰t‰‰n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);

// Haetaan kukarow
$query = "SELECT *
          FROM kuka
          WHERE yhtio = '{$yhtio}'
          AND kuka    = 'admin'";
$kukares = pupe_query($query);

if (mysql_num_rows($kukares) != 1) {
  exit("VIRHE: Admin k‰ytt‰j‰ ei lˆydy!\n");
}

$kukarow = mysql_fetch_assoc($kukares);

$path = trim($argv[2]);
$path = substr($path, -1) != '/' ? $path.'/' : $path;

$error_email = trim($argv[3]);

if ($handle = opendir($path)) {

  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);
    $ext = isset($path_parts['extension']) ? strtoupper($path_parts['extension']) : '';

    if ($ext == 'XML') {

      $filehandle = fopen($path.$file, "r");
      $contents = fread($filehandle, filesize($path.$file));

      $xml = simplexml_load_string($contents);

      if (is_object($xml)) {

        if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType) and trim($xml->MessageHeader->MessageType) == 'StockReport') {

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

            // Etuk‰teen maksetut tilaukset, jotka ovat ker‰‰m‰tt‰ mutta tilaus jo laskutettu
            // Lasketaan ne mukaan Pupen hyllyss‰ m‰‰r‰‰n, koska saldo_myytavissa ei huomioi niit‰
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

            $body = t("Seuraavien tuotteiden saldovertailuissa on havaittu eroja").":<br><br>\n\n";

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
          }

          // siirret‰‰n tiedosto done-kansioon
          rename($path.$file, $path.'done/'.$file);

          pupesoft_log('stock_report', "Saldovertailu k‰sitelty");
        }
      }
    }
  }

  closedir($handle);
}
