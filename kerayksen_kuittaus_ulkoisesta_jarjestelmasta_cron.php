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
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die ("K‰ytt‰j‰‰ ei admin lˆytynyt!\n");
}

$path = trim($argv[2]);
$path = rtrim($path, '/').'/';

$error_email = trim($argv[3]);

$hhv = empty($argv[4]) ? false : true;

$handle = opendir($path);

if ($handle === false) {
  die ("Hakemistoa {$path} ei lˆydy!\n");
}

while (false !== ($file = readdir($handle))) {
  if (is_dir($path.$file)) {
    continue;
  }

  $path_parts = pathinfo($file);

  if (empty($path_parts['extension']) or strtoupper($path_parts['extension']) != 'XML') {
    continue;
  }

  $xml = simplexml_load_file($path.$file);

  pupesoft_log('outbound_delivery', "K‰sitell‰‰n sanoma {$file}");

  if (!is_object($xml)) {
    pupesoft_log('outbound_delivery', "Virheellinen XML sanoma {$file}");

    continue;
  }

  $message_type = "";

  if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType)) {
    $message_type = trim($xml->MessageHeader->MessageType);
  }

  if ($message_type != 'OutboundDeliveryConfirmation') {
    pupesoft_log('outbound_delivery', "Tuntematon sanomatyyppi {$message_type} sanomassa {$file}");

    continue;
  }

  $otunnus = (int) $xml->CustPackingSlip->SalesId;

  // Fallback to pickinglist id
  if ($otunnus == 0) {
    $otunnus = (int) $xml->CustPackingSlip->PickingListId;
  }

  if ($otunnus == 0) {
    pupesoft_log('outbound_delivery', "Tilausnumeroa ei lˆytynyt sanomasta {$file}");

    continue;
  }

  if (isset($xml->CustPackingSlip->DeliveryDate)) {
    #<DeliveryDate>20-04-2016</DeliveryDate>
    $delivery_date = $xml->CustPackingSlip->DeliveryDate;
    $toimaika = date("Y-m-d 00:00:00", strtotime($delivery_date));
  }
  elseif (isset($xml->CustPackingSlip->Deliverydate)) {
    #HHV-case
    #<Deliverydate>2016-04-20T12:34:56</Deliverydate>
    $delivery_date = $xml->CustPackingSlip->Deliverydate;
    $toimaika = date("Y-m-d H:i:s", strtotime($delivery_date));
  }
  else {
    $toimaika = '0000-00-00 00:00:00';
  }

  $toimitustavan_tunnus = (int) $xml->CustPackingSlip->TransportAccount;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$otunnus}'
            AND tila    IN ('L', 'V', 'G', 'S')
            AND alatila = 'A'";
  $laskures = pupe_query($query);

  $tuotteiden_paino = 0;
  $kerayspoikkeama = $tilausrivit = array();

  if (mysql_num_rows($laskures) > 0) {
    $laskurow = mysql_fetch_assoc($laskures);

    if ($hhv) {
      $lines = $xml->Lines->Line;
    }
    else {
      $lines = $xml->CustPackingSlip->Lines;
    }

    foreach ($lines as $line) {
      $tilausrivin_tunnus = (int) $line->TransId;

      if (!isset($tilausrivit[$tilausrivin_tunnus])) {
        $tilausrivit[$tilausrivin_tunnus] = array(
          'eankoodi' => mysql_real_escape_string($line->ItemNumber),
          'keratty'  => (float) $line->DeliveredQuantity
        );
      }
      else {
        $tilausrivit[$tilausrivin_tunnus]['keratty'] += (float) $line->DeliveredQuantity;
      }
    }

    $paivitettiin_tilausrivi_onnistuneesti = false;

    foreach ($tilausrivit as $tilausrivin_tunnus => $data) {

      $eankoodi = $data['eankoodi'];
      $keratty  = $data['keratty'];

      if ($hhv) {
        $tuotelisa = "AND tuote.tuoteno = '{$eankoodi}'";
      }
      else {
        $tuotelisa = "AND tuote.eankoodi = '{$eankoodi}'";
      }

      $query = "SELECT tilausrivi.*
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'
                AND tilausrivi.tunnus != 0
                AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
                AND tilausrivi.keratty = ''
                AND tilausrivi.toimitettu = ''";
      $tilausrivi_res = pupe_query($query);

      if (mysql_num_rows($tilausrivi_res) != 1) {
        pupesoft_log('outbound_delivery', "Tilausrivi‰ {$tilausrivin_tunnus} ei lˆytynyt. Sanoma {$file}");

        continue;
      }

      $tilausrivi_row = mysql_fetch_assoc($tilausrivi_res);

      $varattuupdate = "";

      // Verkkokaupassa etuk‰teen maksettu tuote!
      if ($laskurow["mapvm"] != '' and $laskurow["mapvm"] != '0000-00-00') {
        $a = (int) ($tilausrivi_row['tilkpl'] * 10000);
        $b = (int) ($keratty * 10000);

        if ($a != $b) {
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['tilkpl']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
        }
      }
      else {
        // Jos ei oo etuk‰teen maksettu, niin tehd‰‰b ker‰yspoikkeama
        $varattuupdate = ", tilausrivi.varattu = '{$keratty}' ";
      }

      if ($laskurow["tila"] == "V" or $laskurow["tila"] == "S" or ($laskurow["tila"] == "G" and $laskurow["tilaustyyppi"] != 'M')) {
        $toimitettu_lisa = "";
      }
      else {
        $toimitettu_lisa = ", tilausrivi.toimitettu = '{$kukarow['kuka']}',
                              tilausrivi.toimitettuaika = '{$toimaika}'";
      }

      $query = "UPDATE tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                SET tilausrivi.keratty = '{$kukarow['kuka']}',
                tilausrivi.kerattyaika = '{$toimaika}'
                {$toimitettu_lisa}
                {$varattuupdate}
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
      pupe_query($query);

      $paivitettiin_tilausrivi_onnistuneesti = true;

      $query = "SELECT SUM(tuote.tuotemassa) paino
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
      $painores = pupe_query($query);
      $painorow = mysql_fetch_assoc($painores);

      $tuotteiden_paino += $painorow['paino'];
    }

    # P‰ivitet‰‰n saldottomat tuotteet myˆs toimitetuksi
    $query = "UPDATE tilausrivi
              JOIN tuote ON (
                tuote.yhtio = tilausrivi.yhtio AND
                tuote.tuoteno = tilausrivi.tuoteno AND
                tuote.ei_saldoa != ''
              )
              SET tilausrivi.keratty = '{$kukarow['kuka']}',
              tilausrivi.kerattyaika = '{$toimaika}',
              tilausrivi.toimitettu = '{$kukarow['kuka']}',
              tilausrivi.toimitettuaika = '{$toimaika}'
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus  = '{$otunnus}'";
    pupe_query($query);

    $query  = "INSERT INTO rahtikirjat SET
               toimitustapa   = '{$laskurow['toimitustapa']}',
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

    if ($paivitettiin_tilausrivi_onnistuneesti) {

      if ($laskurow["tila"] == "G") {
        if ($laskurow["tilaustyyppi"] != 'M') {
          $tilalisa = "tila = 'G', alatila = 'C'";
        }
        else {
          $tilalisa = "tila = 'G', alatila = 'D'";
        }

      }
      elseif ($laskurow["tila"] == "V") {
        $tilalisa = "tila = 'V', alatila = 'C'";
      }
      elseif ($laskurow["tila"] == "S") {
        $tilalisa = "tila = 'S', alatila = 'C'";
      }
      else {
        $tilalisa = "tila = 'L', alatila = 'D'";
      }

      $query = "UPDATE lasku SET
                {$tilalisa}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      $upd_res = pupe_query($query);

      paivita_rahtikirjat_tulostetuksi_ja_toimitetuksi(array('otunnukset' => $laskurow['tunnus'], 'kilotyht' => $tuotteiden_paino));

      pupesoft_log('outbound_delivery', "Ker‰yskuittaus tilauksesta {$otunnus} p‰ivitettiin toimitetuksi");
    }

    pupesoft_log('outbound_delivery', "Ker‰yskuittaus tilauksesta {$otunnus} vastaanotettu");

    $avainsanaresult = t_avainsana("ULKJARJLAHETE");
    $avainsanarow = mysql_fetch_assoc($avainsanaresult);

    if ($avainsanarow['selite'] != '') {

      // Tulostetaan l‰hete
      $params = array(
        'laskurow'                 => $laskurow,
        'sellahetetyyppi'          => "",
        'extranet_tilausvahvistus' => "",
        'naytetaanko_rivihinta'    => "",
        'tee'                      => "",
        'toim'                     => "",
        'komento'                  => "asiakasemail{$avainsanarow['selite']}",
        'lahetekpl'                => "",
        'kieli'                    => ""
      );

      pupesoft_tulosta_lahete($params);

      pupesoft_log('outbound_delivery', "L‰hetettiin l‰hete tilauksesta {$laskurow['tunnus']} osoitteeseen {$avainsanarow['selite']}");
    }
  }
  else {
    // Laitetaan s‰hkˆpostia tuplaker‰yksest‰ - ollaan yritetty merkit‰ ker‰tyksi jo k‰sin ker‰tty‰ tilausta
    // Laitetaan s‰hkˆposti admin osoitteeseen siin‰ tapauksessa,
    // jos talhal tai alert email osoitteita ei ole kumpaakaan setattu
    $error_email = $yhtiorow["admin_email"];

    if (isset($yhtiorow["talhal_email"]) and $yhtiorow["talhal_email"] != "") {
      $error_email = $yhtiorow["talhal_email"];
    }
    elseif (isset($yhtiorow["alert_email"]) and $yhtiorow["alert_email"] != "") {
      $error_email = $yhtiorow["alert_email"];
    }

    $body = t("Pupessa jo ker‰tyksi merkitty tilaus %d yritettiin merkit‰ ker‰tyksi ker‰yssanomalla", "", $otunnus);

    pupesoft_log('outbound_delivery', "Vastaanotettiin duplikaatti ker‰yssanoma tilaukselle {$otunnus}");

    $params = array(
      "to"      => $error_email,
      "subject" => t("Mahdollinen tuplaker‰yksen yritys ulkoisesta j‰rjestelm‰st‰", "", ""),
      "ctype"   => "text",
      "body"    => $body
    );

    pupesoft_sahkoposti($params);
  }

  if (count($kerayspoikkeama) != 0) {
    $body = t("Tilauksen %d ker‰yksess‰ on havaittu poikkeamia", "", $otunnus).":<br><br>\n\n";
    $body .= t("Tuoteno")." ".t("Ker‰tty")." ".t("Tilauksella")."<br>\n";

    foreach ($kerayspoikkeama as $tuoteno => $_arr) {
      $body .= "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']}<br>\n";
    }

    $params = array(
      'to'      => $error_email,
      'cc'      => '',
      'subject' => t("Posten ker‰yspoikkeama")." - {$otunnus}",
      'ctype'   => 'html',
      'body'    => $body,
    );

    pupesoft_sahkoposti($params);

    pupesoft_log('outbound_delivery', "Ker‰yspoikkeamia tilauksessa {$otunnus}");
  }

  // siirret‰‰n tiedosto done-kansioon
  rename($path.$file, $path.'done/'.$file);
}

closedir($handle);
