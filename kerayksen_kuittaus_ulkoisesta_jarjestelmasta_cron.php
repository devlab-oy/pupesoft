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

            # Verkkokaupassa etukäteen maksettu tuote!
            if ($laskurow["mapvm"] != '' and $laskurow["mapvm"] != '0000-00-00') {
              $a = (int) ($tilausrivi_row['tilkpl'] * 10000);
              $b = (int) ($keratty * 10000);

              if ($a != $b) {
                $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['tilkpl']);
                $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
              }
            }
            else {
              // Jos ei oo etukäteen maksettu, niin tehdääb keräyspoikkeama
              $varattuupdate = ", tilausrivi.varattu = '{$keratty}' ";
            }

            $query = "UPDATE tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.eankoodi = '{$eankoodi}' AND tuote.tuoteno = tilausrivi.tuoteno)
                      SET tilausrivi.keratty = '{$kukarow['kuka']}',
                      tilausrivi.kerattyaika    = '{$toimaika} 00:00:00',
                      tilausrivi.toimitettu     = '{$kukarow['kuka']}',
                      tilausrivi.toimitettuaika = '{$toimaika} 00:00:00'
                      {$varattuupdate}
                      WHERE tilausrivi.yhtio    = '{$kukarow['yhtio']}'
                      AND tilausrivi.tunnus     = '{$tilausrivin_tunnus}'";
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

          $query = "UPDATE lasku SET
                    tila        = 'L',
                    alatila     = 'D'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$laskurow['tunnus']}'";
          $upd_res = pupe_query($query);

          if (count($kerayspoikkeama) != 0) {

            $body = t("Tilauksen %d keräyksessä on havaittu poikkeamia", "", $otunnus).":<br><br>\n\n";

            $body .= t("Tuoteno")." ".t("Kerätty")." ".t("Tilauksella")."<br>\n";

            foreach ($kerayspoikkeama as $tuoteno => $_arr) {
              $body .= "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']}<br>\n";
            }

            $params = array(
              'to' => $error_email,
              'cc' => '',
              'subject' => t("Posten keräyspoikkeama")." - {$otunnus}",
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
