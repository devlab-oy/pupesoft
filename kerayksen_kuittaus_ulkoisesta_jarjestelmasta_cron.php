<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l�hett�v�� yhti�t�!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut s�hk�postiosoitetta!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
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
  exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
}

$kukarow = mysql_fetch_assoc($kukares);

$path = trim($argv[2]);
$path = substr($path, -1) != '/' ? $path.'/' : $path;

$error_email = trim($argv[3]);

if ($handle = opendir($path)) {

  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);

    if (isset($path_parts['extension'])) {
      $ext = strtoupper($path_parts['extension']);
    }

    if (isset($ext) and $ext == 'XML') {

      $xml = simplexml_load_file($path.$file);

      if (is_object($xml)) {

        if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType) and trim($xml->MessageHeader->MessageType) == 'OutboundDeliveryConfirmation') {

          $otunnus = (int) $xml->CustPackingSlip->SalesId;

          // Fallback to pickinglist id
          if ($otunnus == 0) $otunnus = (int) $xml->CustPackingSlip->PickingListId;

          list($pp, $kk, $vv) = explode("-", $xml->CustPackingSlip->DeliveryDate);
          $toimaika = "{$vv}-{$kk}-{$pp}";
          $toimitustavan_tunnus = (int) $xml->CustPackingSlip->TransportAccount;

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$otunnus}'";
          $laskures = pupe_query($query);
          $laskurow = mysql_fetch_assoc($laskures);

          $tuotteiden_paino = 0;

          $kerayspoikkeama = array();

          foreach ($xml->CustPackingSlip->Lines as $line) {

            $tilausrivin_tunnus = (int) $line->TransId;
            $eankoodi = mysql_real_escape_string($line->ItemNumber);
            $keratty = (float) $line->DeliveredQuantity;

            $query = "SELECT *
                      FROM tilausrivi
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$tilausrivin_tunnus}'";
            $tilausrivi_res = pupe_query($query);
            $tilausrivi_row = mysql_fetch_assoc($tilausrivi_res);

            $varattuupdate = "";

            // Verkkokaupassa etuk�teen maksettu tuote!
            if ($laskurow["mapvm"] != '' and $laskurow["mapvm"] != '0000-00-00') {
              $a = (int) ($tilausrivi_row['tilkpl'] * 10000);
              $b = (int) ($keratty * 10000);

              if ($a != $b) {
                $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['tilkpl']);
                $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
              }
            }
            else {
              // Jos ei oo etuk�teen maksettu, niin tehd��b ker�yspoikkeama
              $varattuupdate = ", tilausrivi.varattu = '{$keratty}' ";
            }

            if ($laskurow["tila"] == "G") {
              $toimitettu_lisa = "";
            }
            else {
              $toimitettu_lisa = ", tilausrivi.toimitettu = '{$kukarow['kuka']}',
                                    tilausrivi.toimitettuaika = '{$toimaika} 00:00:00'";
            }

            $query = "UPDATE tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.eankoodi = '{$eankoodi}' AND tuote.tuoteno = tilausrivi.tuoteno)
                      SET tilausrivi.keratty = '{$kukarow['kuka']}',
                      tilausrivi.kerattyaika = '{$toimaika} 00:00:00'
                      {$toimitettu_lisa}
                      {$varattuupdate}
                      WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
            pupe_query($query);

            $query = "SELECT SUM(tuote.tuotemassa) paino
                      FROM tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                      WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
            $painores = pupe_query($query);
            $painorow = mysql_fetch_assoc($painores);

            $tuotteiden_paino += $painorow['paino'];
          }

          $query  = "INSERT INTO rahtikirjat SET
                     kollit         = 1,
                     kilot          = {$tuotteiden_paino},
                     pakkaus        = '',
                     pakkauskuvaus  = '',
                     rahtikirjanro  = '',
                     otsikkonro     = '{$otunnus}',
                     tulostuspaikka = '{$laskurow['varasto']}',
                     yhtio          = '{$kukarow['yhtio']}',
                     viesti         = ''";
          $result_rk = pupe_query($query);

          if ($laskurow["tila"] == "G") {
            $tilalisa = "tila = 'G', alatila = 'C'";
          }
          else {
            $tilalisa = "tila = 'L', alatila = 'D'";
          }

          $query = "UPDATE lasku SET
                    {$tilalisa}
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$laskurow['tunnus']}'";
          $upd_res = pupe_query($query);

          // Etuk�teen maksetut tilaukset pit�� muuttaa takaisin "maksettu"-tilaan
          $query = "UPDATE lasku SET
                    alatila      = 'X'
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND tunnus   = '$laskurow[tunnus]'
                    AND mapvm   != '0000-00-00'
                    AND chn      = '999'";
          $yoimresult  = pupe_query($query);

          if (count($kerayspoikkeama) != 0) {

            $body = t("Tilauksen %d ker�yksess� on havaittu poikkeamia", "", $otunnus).":<br><br>\n\n";

            $body .= t("Tuoteno")." ".t("Ker�tty")." ".t("Tilauksella")."<br>\n";

            foreach ($kerayspoikkeama as $tuoteno => $_arr) {
              $body .= "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']}<br>\n";
            }

            $params = array(
              'to' => $error_email,
              'cc' => '',
              'subject' => t("Posten ker�yspoikkeama")." - {$otunnus}",
              'ctype' => 'html',
              'body' => $body,
            );

            pupesoft_sahkoposti($params);
          }

          // siirret��n tiedosto done-kansioon
          rename($path.$file, $path.'done/'.$file);
        }
      }
    }
  }

  closedir($handle);
}
