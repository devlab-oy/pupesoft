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
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";
require "rajapinnat/woo/woo-functions.php";
require "rajapinnat/mycashflow/mycf_toimita_tilaus.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die ("Käyttäjää ei admin löytynyt!\n");
}

$path = trim($argv[2]);
$path = rtrim($path, '/').'/';

$error_email = trim($argv[3]);
$email_array = array();

$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

$handle = opendir($path);

if ($handle === false) {
  die ("Hakemistoa {$path} ei löydy!\n");
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'OutboundDeliveryConfirmation') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('logmaster_outbound_delivery_confirmation', "Käsitellään sanoma {$file}");

  $otunnus = (int) $xml->CustPackingSlip->PickingListId;

  $tracking_code = "";

  if (isset($xml->CustPackingSlip->Tracking_code) and !empty($xml->CustPackingSlip->Tracking_code)) {
    $tracking_code = $xml->CustPackingSlip->Tracking_code;
  }

  if ($otunnus == 0) {
    pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausnumeroa ei löytynyt sanomasta {$file}");

    $email_array[] = t("Tilausnumeroa ei löytynyt sanomasta %s", "", $file);

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

    # katsotaan missä rivit ovat
    if (isset($xml->Lines)) {
      $lines = $xml->Lines;
    }
    elseif (isset($xml->CustPackingSlip->Lines)) {
      $lines = $xml->CustPackingSlip->Lines;
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivit-elementtiä ei löytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivit-elementtiä ei löytynyt sanomasta %s", "", $file);

      rename($full_filepath, $path.'error/'.$file);

      continue;
    }

    # katsotaan missä yksittäinen rivi sijaitsee
    if (isset($lines->Line->TransId)) {
      $lines = $lines->Line;
    }
    elseif (isset($lines->TransId)) {
      # rivit onkin suoraan lines elementtejä
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivin TransId-elementtiä ei löytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivin TransId-elementtiä ei löytynyt sanomasta %s", "", $file);

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

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} ".count($tilausrivit)." uniikkia tilausriviä.");
    echo "\n";

    $query = "SELECT *
              FROM asiakkaan_avainsanat
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND liitostunnus  = '{$laskurow['liitostunnus']}'
              AND laji          = 'KICK_EDI'
              AND avainsana    != ''";
    $edi_chk_res = pupe_query($query);
    $packageinfo = FALSE;

    // Special EDI case, tarkistetaan löytyykö <Packages>-segmentti
    if (mysql_num_rows($edi_chk_res) and isset($xml->CustPackingSlip->Packages)) {
      // Poistetaan keryserät ja luodaan ne uudestaan Packages-elementin tietojen perusteella
      $query = "DELETE
                FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskurow['tunnus']}'";
      pupe_query($query);
      $pklask = 1;

      foreach ($xml->CustPackingSlip->Packages->PackageId as $package) {
        $package_sscc = $package->attributes()->No;

        foreach ($package->PacItemLine as $packageitem) {
          $package_rivitunnus = $packageitem->OrdTransId;
          $package_tuoteno = $packageitem->PacItemNumber;
          $package_maara = $packageitem->PacQuantity;

          $query = "INSERT INTO kerayserat SET
                    yhtio         = '{$kukarow['yhtio']}',
                    nro           = {$laskurow['tunnus']},
                    keraysvyohyke = 0,
                    tila          = '',
                    sscc          = '{$laskurow['tunnus']}',
                    sscc_ulkoinen = '{$package_sscc}',
                    otunnus       = '{$laskurow['tunnus']}',
                    tilausrivi    = '{$package_rivitunnus}',
                    pakkaus       = '0',
                    pakkausnro    = '{$pklask}',
                    kpl           = '{$package_maara}',
                    kpl_keratty   = '{$package_maara}',
                    keratty       = '{$kukarow['kuka']}',
                    kerattyaika   = '{$toimaika}',
                    laatija       = '{$kukarow['kuka']}',
                    luontiaika    = now(),
                    muutospvm     = now(),
                    muuttaja      = '{$kukarow['kuka']}'";
          pupe_query($query);
          $packageinfo = TRUE;
        }
        $pklask++;
      }
    }

    $paivitettiin_tilausrivi_onnistuneesti = false;
    $keratty_yhteensa = 0;
    echo "\n";

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
        pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausriviä {$tilausrivin_tunnus} ei löytynyt. Sanoma {$file}");

        $tilausrivit_error[$tilausrivin_tunnus] = $data;
        continue;
      }

      $tilausrivi_row = mysql_fetch_assoc($tilausrivi_res);

      $varattuupdate = "";
      $kerattyupdate = "tilausrivi.kerattyaika = '{$toimaika}', tilausrivi.keratty = '{$kukarow['kuka']}'";

      // Verkkokaupassa etukäteen maksettu tuote!
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
        // Jos ei oo etukäteen maksettu, niin tehdään keräyspoikkeama
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

      // Jos poikkeava määrä kerätty, jätetään mahdollisesti var p/j rivejä
      // vain jos normaali myyntitilaus kyseessä
      if (in_array($yhtiorow['kerayspoikkeama_kasittely'], array('J','U')) and !$_etukateen_maksettu and $laskurow['tila'] == 'L') {

        $a = (int) ($tilausrivi_row['varattu'] * 10000);
        $b = (int) ($keratty * 10000);

        // tsekataan tarviiko riviä splittaa, eli jäikö kokonaan keräämättä
        if (empty($keratty)) {

          // jos jäi niin muutetaan rivin update sen mukaan eikä erikseen splitata
          // ei kosketa mihinkään muuhun kuin var kenttään
          pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus {$otunnus} rivi {$tilausrivin_tunnus} ({$item_number}) jäi kokonaan keräämättä!");

          $kerattyupdate = "tilausrivi.kerattyaika = '0000-00-00 00:00:00', tilausrivi.keratty = ''";
          $toimitettu_lisa = "";
          $varattuupdate = ", tilausrivi.var = '{$_var}'";

          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['varattu']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['status'] = $_var;
        }
        elseif ($a != $b) {

          // jos vain osa jäi keräämättä niin tarvii splittaa
          pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus {$otunnus} rivi {$tilausrivin_tunnus} ({$item_number}) sisältää keräyspoikkeaman, splitataan erotus var {$_var}:ksi");

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

          // kopioidaan tilausrivi poikkeavalle määrälle ja jätetään se jt/puute
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

    // tsekataan onko yhtään kerättyä riviä myyntitilauksella
    if ($keratty_yhteensa == 0 and $laskurow['tila'] == 'L') {

      // päivitetään otsikkoa (kesken, odottamaan jt rivejä) molemmissa var keisseissä
      // iltasiivo hoitaa var p keissit
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus {$otunnus} sisältää vain keräyspoikkeamia, laitetaan tilaus kesken odottamaan jälkitoimituksia");

      $query = "UPDATE lasku SET
                alatila     = 'T',
                tila        = 'N'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$laskurow[tunnus]'";
      pupe_query($query);
    }
    else {

      // Jatketaan normaalisti jos oli jotain kerättävää
      // Päivitetään saldottomat tuotteet myös toimitetuksi
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

      $_seurantakoodilisa = "";

      if (!empty($tracking_code)) {
        $_seurantakoodilisa = "rahtikirjanro = '{$tracking_code}', tulostettu = now(),";
      }

      $query  = "INSERT INTO rahtikirjat SET
                 toimitustapa   = '{$laskurow['toimitustapa']}',
                 kollit         = 1,
                 kilot          = {$tuotteiden_paino},
                 pakkaus        = '',
                 pakkauskuvaus  = '',
                 {$_seurantakoodilisa}
                 otsikkonro     = '{$otunnus}',
                 tulostuspaikka = '{$laskurow['varasto']}',
                 yhtio          = '{$kukarow['yhtio']}',
                 viesti         = ''";
      $result_rk = pupe_query($query);

      $rahtikirjantunnus = mysql_insert_id();

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

        $pupe_root_polku = dirname(dirname(dirname(__FILE__)));

        if ($packageinfo) {
          // Special EDI case, lähetetään toimitusvahvistus
          pupesoft_toimitusvahvistus($laskurow['tunnus']);
        }
        elseif (!empty($tracking_code)) {
          // seurantakoodien lähetys
          $tilausnumero = $laskurow['tunnus'];
          $seurantakoodi = $tracking_code;

          $query = "SELECT toimitusvahvistus
                    FROM asiakas
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$laskurow['liitostunnus']}'";
          $toimitusvahvistus_res = pupe_query($query);
          $toimitusvahvistus_row = mysql_fetch_assoc($toimitusvahvistus_res);

          // Lähetetään toimitusvahvistus
          if ($toimitusvahvistus_row['toimitusvahvistus'] != '') {
            pupesoft_toimitusvahvistus($tilausnumero, $rahtikirjantunnus, $seurantakoodi);
          }

          // Merkaatan woo-commerce tilaukset toimitetuiksi kauppaan
          $woo_params = array(
            "pupesoft_tunnukset" => array($tilausnumero),
            "tracking_code" => $seurantakoodi,
          );

          woo_commerce_toimita_tilaus($woo_params);

          // Merkaatan MyCashflow tilaukset toimitetuiksi kauppaan
          $mycf_params = array(
            "pupesoft_tunnukset" => array($tilausnumero),
            "tracking_code" => $seurantakoodi,
          );

          mycf_toimita_tilaus($mycf_params);

          // Jos Magento on käytössä, merkataan tilaus toimitetuksi Magentoon kun rahtikirja tulostetaan
          if ($_magento_kaytossa) {

            $query = "SELECT toimitustapa
                      FROM rahtikirjat
                      WHERE yhtio    = '{$kukarow['yhtio']}'
                      AND otsikkonro = '{$tilausnumero}'";
            $chk_res = pupe_query($query);

            if (mysql_num_rows($chk_res) > 0) {
              $chk_row = mysql_fetch_assoc($chk_res);

              $query = "SELECT *
                        FROM toimitustapa
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND selite  = '{$chk_row['toimitustapa']}'";
              $toitares = pupe_query($query);
              $toitarow = mysql_fetch_assoc($toitares);

              $query = "SELECT asiakkaan_tilausnumero, tunnus
                        FROM lasku
                        WHERE yhtio                 = '{$kukarow['yhtio']}'
                        AND tunnus                  = '{$tilausnumero}'
                        AND ohjelma_moduli          = 'MAGENTO'
                        AND asiakkaan_tilausnumero  != ''
                        AND laatija                 = 'Magento'";
              $mageres = pupe_query($query);

              while ($magerow = mysql_fetch_assoc($mageres)) {

                pupesoft_log('logmaster_tracking_code', "Päivitetään tilaus {$magerow["tunnus"]} toimitetuksi Magentoon");

                $magento_api_met = $toitarow['virallinen_selite'] != '' ? $toitarow['virallinen_selite'] : $toitarow['selite'];
                $magento_api_rak = $seurantakoodi;
                $magento_api_ord = $magerow["asiakkaan_tilausnumero"];
                $magento_api_laskutunnus = $magerow["tunnus"];

                require "magento_toimita_tilaus.php";
              }
            }
          }
        }

        if ($laskurow['alatila'] != 'X' and ($laskurow['vienti'] == 'E' or $laskurow['vienti'] == 'K')) {
          $uusialatila = viennin_lisatiedot($laskurow['tunnus']);

          // Luodaan lasku
          if ($laskurow['verkkotunnus'] == "VELOX" and $uusialatila == 'E') {

            // päivitetään laskun otsikko laskutusjonoon
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

        pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus tilauksesta {$otunnus} päivitettiin toimitetuksi");
      }

      if (count($tilausrivit_error) > 0) {
        pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} oli ".count($tilausrivit_error)." virheellistä tilausriviä.");
      }

      pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus tilauksesta {$otunnus} vastaanotettu");

      $avainsanaresult = t_avainsana("ULKJARJLAHETE");
      $avainsanarow = mysql_fetch_assoc($avainsanaresult);

      if ($avainsanarow['selite'] != '') {

        // Tulostetaan lähete
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

        pupesoft_log('logmaster_outbound_delivery_confirmation', "Lähetettiin lähete tilauksesta {$laskurow['tunnus']} osoitteeseen {$avainsanarow['selite']}");
      }
    }
  }
  else {
    // Laitetaan sähköpostia tuplakeräyksestä - ollaan yritetty merkitä kerätyksi jo käsin kerättyä tilausta
    $email_array[] = t("Pupessa jo kerätyksi merkitty tilaus %d yritettiin merkitä kerätyksi keräyssanomalla", "", $otunnus);

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Vastaanotettiin duplikaatti keräyssanoma tilaukselle {$otunnus}");
  }

  if (count($kerayspoikkeama) != 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksen %d keräyksessä on havaittu poikkeamia", "", $otunnus).":";
    $email_array[] = t("Tuoteno")." ".t("Kerätty")." ".t("Tilauksella")." ".t("Status");

    foreach ($kerayspoikkeama as $tuoteno => $_arr) {

      if (isset($_arr['status'])) {
        $_status = $_arr['status'] == "J" ? t("Jälkitoimitukseen") : t("Puuteriviksi");
        $_erotus = $_arr['tilauksella'] - $_arr['keratty'];
        $_status = "{$_status} {$_erotus}";
      }
      else {
        $_status = "";
      }

      $email_array[] = "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']} ".$_status;
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyspoikkeamia tilauksessa {$otunnus}");
  }

  if (count($tilausrivit_error) > 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksessa %d on havaittu virheellisiä rivejä", "", $otunnus).":";
    $email_array[] = t("Rivitunnus")." ".t("Tuoteno")." ".t("Kerätty");

    foreach ($tilausrivit_error as $rivitunnus => $_arr) {
      $email_array[] = "{$rivitunnus} {$_arr['item_number']} {$_arr['keratty']}";
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyksen kuittauksen sanomassa {$file} virheellisiä rivejä tilauksessa {$otunnus}");
  }

  $params = array(
    'email' => $error_email,
    'email_array' => $email_array,
    'log_name' => 'logmaster_outbound_delivery_confirmation',
  );

  logmaster_send_email($params);

  // siirretään tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);
}

closedir($handle);
