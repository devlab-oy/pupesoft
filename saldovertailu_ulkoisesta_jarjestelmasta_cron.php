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
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require("inc/connect.inc");
require("inc/functions.inc");

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
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
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
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

          // siirretään tiedosto done-kansioon
          rename($path.$file, $path.'done/'.$file);
        }
      }
    }
  }

  closedir($handle);
}
