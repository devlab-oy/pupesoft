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
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

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
$email_array = array();

$handle = opendir($path);

if ($handle === false) {
  die ("Hakemistoa {$path} ei lˆydy!\n");
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'OutboundDeliveryConfirmation') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('logmaster_outbound_delivery_confirmation', "K‰sitell‰‰n sanoma {$file}");

  $otunnus = (int) $xml->CustPackingSlip->PickingListId;

  if ($otunnus == 0) {
    pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausnumeroa ei lˆytynyt sanomasta {$file}");

    $email_array[] = t("Tilausnumeroa ei lˆytynyt sanomasta %s", "", $file);

    rename($full_filepath, $path.'error/'.$file);

    continue;
  }

  if (isset($xml->CustPackingSlip->DeliveryDate)) {
    //<DeliveryDate>20-04-2016</DeliveryDate>
    $delivery_date = $xml->CustPackingSlip->DeliveryDate;
    $toimaika = date("Y-m-d 00:00:00", strtotime($delivery_date));
  }
  elseif (isset($xml->CustPackingSlip->Deliverydate)) {
    //HHV-case
    //<Deliverydate>2016-04-20T12:34:56</Deliverydate>
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
            AND alatila IN ('A', 'E', 'J')";
  $laskures = pupe_query($query);

  $tuotteiden_paino = 0;
  $kerayspoikkeama = $tilausrivit = $tilausrivit_error = array();

  if (mysql_num_rows($laskures) > 0) {
    $laskurow = mysql_fetch_assoc($laskures);

    # katsotaan miss‰ rivit ovat
    if (isset($xml->Lines)) {
      $lines = $xml->Lines;
    }
    elseif (isset($xml->CustPackingSlip->Lines)) {
      $lines = $xml->CustPackingSlip->Lines;
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivit-elementti‰ ei lˆytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivit-elementti‰ ei lˆytynyt sanomasta %s", "", $file);

      rename($full_filepath, $path.'error/'.$file);

      continue;
    }

    # katsotaan miss‰ yksitt‰inen rivi sijaitsee
    if (isset($lines->Line->TransId)) {
      $lines = $lines->Line;
    }
    elseif (isset($lines->TransId)) {
      # rivit onkin suoraan lines elementtej‰
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivin TransId-elementti‰ ei lˆytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivin TransId-elementti‰ ei lˆytynyt sanomasta %s", "", $file);

      rename($full_filepath, $path.'error/'.$file);

      continue;
    }

    foreach ($lines as $line) {

      $tilausrivin_tunnus = (int) $line->TransId;

      if (!isset($tilausrivit[$tilausrivin_tunnus])) {
        $tilausrivit[$tilausrivin_tunnus] = array(
          'item_number' => mysql_real_escape_string($line->ItemNumber),
          'keratty'     => (float) $line->DeliveredQuantity,
        );
      }
      else {
        $tilausrivit[$tilausrivin_tunnus]['keratty'] += (float) $line->DeliveredQuantity;
      }
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} ".count($tilausrivit)." uniikkia tilausrivi‰.");

    $paivitettiin_tilausrivi_onnistuneesti = false;
    $keratty_yhteensa = 0;

    // tsekataan mahollinen var arvo
    $_var = $yhtiorow['kerayspoikkeama_kasittely'] == 'J' ? "J" : "P";

    foreach ($tilausrivit as $tilausrivin_tunnus => $data) {

      $item_number = $data['item_number'];
      $keratty     = $data['keratty'];

      $logmaster_itemnumberfield = logmaster_field('ItemNumber');
      $tuotelisa = "AND tuote.{$logmaster_itemnumberfield} = '{$item_number}'";

      $query = "SELECT tilausrivi.*
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus      = '{$tilausrivin_tunnus}'
                AND tilausrivi.tunnus     != 0
                AND tilausrivi.otunnus     = '{$laskurow['tunnus']}'
                AND tilausrivi.keratty     = ''
                AND tilausrivi.toimitettu  = ''";
      $tilausrivi_res = pupe_query($query);

      if (mysql_num_rows($tilausrivi_res) != 1) {
        pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausrivi‰ {$tilausrivin_tunnus} ei lˆytynyt. Sanoma {$file}");

        $tilausrivit_error[$tilausrivin_tunnus] = $data;
        continue;
      }

      $tilausrivi_row = mysql_fetch_assoc($tilausrivi_res);

      $varattuupdate = "";
      $kerattyupdate = "tilausrivi.kerattyaika = '{$toimaika}', tilausrivi.keratty = '{$kukarow['kuka']}'";

      // Verkkokaupassa etuk‰teen maksettu tuote!
      if ($laskurow["mapvm"] != '' and $laskurow["mapvm"] != '0000-00-00') {
        $a = (int) ($tilausrivi_row['tilkpl'] * 10000);
        $b = (int) ($keratty * 10000);

        if ($a != $b) {
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['tilkpl']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
        }

        $keratty_yhteensa += $tilausrivi_row['tilkpl'];
        $_etukateen_maksettu = true;
      }
      else {
        // Jos ei oo etuk‰teen maksettu, niin tehd‰‰n ker‰yspoikkeama
        $varattuupdate = ", tilausrivi.varattu = '{$keratty}'";
        $keratty_yhteensa += $keratty;
        $_etukateen_maksettu = false;
      }

      if ($laskurow["tila"] == "V" or $laskurow["tila"] == "S" or $laskurow["tila"] == "G") {
        $toimitettu_lisa = "";
      }
      else {
        $toimitettu_lisa = ", tilausrivi.toimitettu = '{$kukarow['kuka']}',
                              tilausrivi.toimitettuaika = '{$toimaika}'";
      }

      // Jos poikkeava m‰‰r‰ ker‰tty, j‰tet‰‰n mahdollisesti var p/j rivej‰
      // vain jos normaali myyntitilaus kyseess‰
      if (in_array($yhtiorow['kerayspoikkeama_kasittely'], array('J','U')) and !$_etukateen_maksettu and $laskurow['tila'] == 'L') {

        $a = (int) ($tilausrivi_row['varattu'] * 10000);
        $b = (int) ($keratty * 10000);

        // tsekataan tarviiko rivi‰ splittaa, eli j‰ikˆ kokonaan ker‰‰m‰tt‰
        if (empty($keratty)) {

          // jos j‰i niin muutetaan rivin update sen mukaan eik‰ erikseen splitata
          // ei kosketa mihink‰‰n muuhun kuin var kentt‰‰n
          pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yskuittaus {$otunnus} rivi {$tilausrivin_tunnus} ({$item_number}) j‰i kokonaan ker‰‰m‰tt‰!");

          $kerattyupdate = "tilausrivi.kerattyaika = '0000-00-00 00:00:00', tilausrivi.keratty = ''";
          $toimitettu_lisa = "";
          $varattuupdate = ", tilausrivi.var = '{$_var}'";

          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['varattu']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['status'] = $_var;
        }
        elseif ($a != $b) {

          // jos vain osa j‰i ker‰‰m‰tt‰ niin tarvii splittaa
          pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yskuittaus {$otunnus} rivi {$tilausrivin_tunnus} ({$item_number}) sis‰lt‰‰ ker‰yspoikkeaman, splitataan erotus var {$_var}:ksi");

          $_varaus = 0;
          $_poikkeama = $tilausrivi_row['varattu'] - $keratty;

          // varaako jt:t saldoa?
          if ($yhtiorow['kerayspoikkeama_kasittely'] == 'J' and $yhtiorow['varaako_jt_saldoa'] == 'K') {
            $_varaus = $_poikkeama;
            $_poikkeama = 0;
          }
          elseif ($yhtiorow['kerayspoikkeama_kasittely'] == 'U') {
            $_poikkeama = 0;
          }

          // kopioidaan tilausrivi poikkeavalle m‰‰r‰lle ja j‰tet‰‰n se jt/puute
          $poikkeuskentat = array("tilausrivi.varattu"=> $_varaus, "tilausrivi.jt"=> $_poikkeama, "tilausrivi.var" => $_var);
          kopioi_tilausrivi($tilausrivin_tunnus, $poikkeuskentat);

          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['varattu']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['status'] = $_var;
        }
      }

      $query = "UPDATE tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                SET
                {$kerattyupdate}
                {$toimitettu_lisa}
                {$varattuupdate}
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
      pupe_query($query);

      $paivitettiin_tilausrivi_onnistuneesti = true;

      $query = "SELECT SUM(tilausrivi.varattu * tuote.tuotemassa) paino
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'
                AND tilausrivi.var not in ('J','P')";
      $painores = pupe_query($query);
      $painorow = mysql_fetch_assoc($painores);

      $tuotteiden_paino += $painorow['paino'];
    }

    // tsekataan onko yht‰‰n ker‰tty‰ rivi‰ myyntitilauksella
    if ($keratty_yhteensa == 0 and $laskurow['tila'] == 'L') {

      // p‰ivitet‰‰n otsikkoa (kesken, odottamaan jt rivej‰) molemmissa var keisseiss‰
      // iltasiivo hoitaa var p keissit
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yskuittaus {$otunnus} sis‰lt‰‰ vain ker‰yspoikkeamia, laitetaan tilaus kesken odottamaan j‰lkitoimituksia");

      $query = "UPDATE lasku SET
                alatila     = 'T',
                tila        = 'N'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$laskurow[tunnus]'";
      pupe_query($query);
    }
    else {

      // Jatketaan normaalisti jos oli jotain ker‰tt‰v‰‰
      // P‰ivitet‰‰n saldottomat tuotteet myˆs toimitetuksi
      $query = "UPDATE tilausrivi
                JOIN tuote ON (
                  tuote.yhtio              = tilausrivi.yhtio AND
                  tuote.tuoteno            = tilausrivi.tuoteno AND
                  tuote.ei_saldoa         != ''
                )
                SET tilausrivi.keratty = '{$kukarow['kuka']}',
                tilausrivi.kerattyaika     = '{$toimaika}',
                tilausrivi.toimitettu      = '{$kukarow['kuka']}',
                tilausrivi.toimitettuaika  = '{$toimaika}'
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus     = '{$otunnus}'";
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

        if ($laskurow['alatila'] != 'X' and ($laskurow['vienti'] == 'E' or $laskurow['vienti'] == 'K')) {
          $uusialatila = viennin_lisatiedot($laskurow['tunnus']);

          // Luodaan lasku
          if ($laskurow['verkkotunnus'] == "VELOX" and $uusialatila == 'E') {

            // p‰ivitet‰‰n laskun otsikko laskutusjonoon
            $query = "UPDATE lasku
                      set alatila = 'D'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$laskurow['tunnus']}'";
            $result = pupe_query($query);

            // Laskutetaan tilaus
            $laskutettavat    = $laskurow['tunnus'];
            $tee              = "TARKISTA";
            $laskutakaikki    = "KYLLA";
            $silent           = "KYLLA";
            $velox_laskutus   = "KYLLA";
            $force_web        = True;
            $pupe_root_polku  = dirname(dirname(dirname(__FILE__)));

            require "tilauskasittely/verkkolasku.php";
          }
        }

        pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yskuittaus tilauksesta {$otunnus} p‰ivitettiin toimitetuksi");
      }

      if (count($tilausrivit_error) > 0) {
        pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} oli ".count($tilausrivit_error)." virheellist‰ tilausrivi‰.");
      }

      pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yskuittaus tilauksesta {$otunnus} vastaanotettu");

      $avainsanaresult = t_avainsana("ULKJARJLAHETE");
      $avainsanarow = mysql_fetch_assoc($avainsanaresult);

      if ($avainsanarow['selite'] != '') {

        // Tulostetaan l‰hete
        $params = array(
          'extranet_tilausvahvistus' => "",
          'kieli'                    => "",
          'komento'                  => "asiakasemail{$avainsanarow['selite']}",
          'lahetekpl'                => "",
          'laskurow'                 => $laskurow,
          'naytetaanko_rivihinta'    => "",
          'sellahetetyyppi'          => "",
          'tee'                      => "",
          'toim'                     => "",
        );

        pupesoft_tulosta_lahete($params);

        pupesoft_log('logmaster_outbound_delivery_confirmation', "L‰hetettiin l‰hete tilauksesta {$laskurow['tunnus']} osoitteeseen {$avainsanarow['selite']}");
      }
    }
  }
  else {
    // Laitetaan s‰hkˆpostia tuplaker‰yksest‰ - ollaan yritetty merkit‰ ker‰tyksi jo k‰sin ker‰tty‰ tilausta
    $email_array[] = t("Pupessa jo ker‰tyksi merkitty tilaus %d yritettiin merkit‰ ker‰tyksi ker‰yssanomalla", "", $otunnus);

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Vastaanotettiin duplikaatti ker‰yssanoma tilaukselle {$otunnus}");
  }

  if (count($kerayspoikkeama) != 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksen %d ker‰yksess‰ on havaittu poikkeamia", "", $otunnus).":";
    $email_array[] = t("Tuoteno")." ".t("Ker‰tty")." ".t("Tilauksella")." ".t("Status");

    foreach ($kerayspoikkeama as $tuoteno => $_arr) {

      if (isset($_arr['status'])) {
        $_status = $_arr['status'] == "J" ? t("J‰lkitoimitukseen") : t("Puuteriviksi");
        $_erotus = $_arr['tilauksella'] - $_arr['keratty'];
        $_status = "{$_status} {$_erotus}";
      }
      else {
        $_status = "";
      }

      $email_array[] = "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']} ".$_status;
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yspoikkeamia tilauksessa {$otunnus}");
  }

  if (count($tilausrivit_error) > 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksessa %d on havaittu virheellisi‰ rivej‰", "", $otunnus).":";
    $email_array[] = t("Rivitunnus")." ".t("Tuoteno")." ".t("Ker‰tty");

    foreach ($tilausrivit_error as $rivitunnus => $_arr) {
      $email_array[] = "{$rivitunnus} {$_arr['item_number']} {$_arr['keratty']}";
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Ker‰yksen kuittauksen sanomassa {$file} virheellisi‰ rivej‰ tilauksessa {$otunnus}");
  }

  $params = array(
    'email' => $error_email,
    'email_array' => $email_array,
    'log_name' => 'logmaster_outbound_delivery_confirmation',
  );

  logmaster_send_email($params);

  // siirret‰‰n tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);
}

closedir($handle);
