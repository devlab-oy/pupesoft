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

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

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

$email = isset($argv[3]) ? trim($argv[3]) : "";

if ($handle = opendir($path)) {

  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);
    $ext = strtoupper($path_parts['extension']);

    if ($ext == 'XML') {

      $filehandle = fopen($path.$file, "r");

      $xml = simplexml_load_file($path.$file);

      pupesoft_log('inbound_delivery_confirmation', "Käsitellään sanoma {$file}");

      if (!is_object($xml)) {
        pupesoft_log('inbound_delivery_confirmation', "Virheellinen XML sanoma {$file}");

        continue;
      }

      $message_type = "";

      if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType)) {
        $message_type = trim($xml->MessageHeader->MessageType);
      }

      if ($message_type != 'InboundDeliveryConfirmation') {
        pupesoft_log('inbound_delivery_confirmation', "Tuntematon sanomatyyppi {$message_type} sanomassa {$file}");

        continue;
      }

      $node = $xml->VendPackingSlip;

      $ostotilaus = (int) $node->PurchId;
      $saapumisnro = (int) $node->ReceiptsListId;

      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio  = '{$yhtio}'
                AND tila     = 'K'
                AND vanhatunnus = 0
                AND laskunro = '{$saapumisnro}'
                AND sisviesti3 = 'ei_vie_varastoon'";
      $selectres = pupe_query($query);
      $selectrow = mysql_fetch_assoc($selectres);

      $saapumistunnus = (int) $selectrow['tunnus'];

      if ($saapumistunnus == 0) {
        pupesoft_log('inbound_delivery_confirmation', "Kuittausta odottavaa saapumista ei löydy saapumisnumerolla {$saapumisnro} sanomassa {$file}");

        continue;
      }

      if (!isset($xml->Lines) or !isset($xml->Lines->Line)) {
        pupesoft_log('inbound_delivery_confirmation', "Sanomassa {$file} ei ollut rivejä");

        continue;
      }

      $tilausrivit = array();

      # Poistetaan ostotilauksen kaikki kohdistukset saapumiselta
      # koska aineistossa on OIKEAT saapuneet ostotilauksen rivit
      $query = "UPDATE tilausrivi SET
                uusiotunnus     = 0
                WHERE yhtio     = '{$yhtio}'
                AND tyyppi      = 'O'
                AND kpl         = 0
                AND uusiotunnus = '{$saapumistunnus}'";
      $updres = pupe_query($query);

      # Loopataan rivit tilausrivit-arrayseen
      # koska Pupesoftin tilausrivi voi tulla monella aineiston rivillä
      foreach ($xml->Lines->Line as $key => $line) {

        $rivitunnus = (int) $line->TransId;
        $tuoteno    = (string) $line->ItemNumber;
        $kpl        = (float) $line->ArrivedQuantity;

        if (!isset($tilausrivit[$rivitunnus])) {
          $tilausrivit[$rivitunnus] = array(
            'tuoteno' => $tuoteno,
            'kpl'     => $kpl
          );
        }
        else {
          $tilausrivit[$rivitunnus]['kpl'] += $kpl;
        }
      }

      foreach ($tilausrivit as $rivitunnus => $data) {

        $tuoteno = $data['tuoteno'];
        $kpl     = $data['kpl'];

        # Jos sanomassa on kappaleita ja tiedetään saapuminen
        # Kohdistetaan tämä rivi saapumiseen
        # Aiemmin ollaan poistettu kaikki tämän saapumisen kohdistukset
        if ($kpl != 0 and $saapumistunnus != 0) {
          $uusiotunnuslisa = ", uusiotunnus = '{$saapumistunnus}' ";
        }
        else {
          $uusiotunnuslisa = "";
        }

        # Päivitetään varattu ja kohdistetaan rivi
        $query = "UPDATE tilausrivi SET
                  varattu         = '{$kpl}'
                  {$uusiotunnuslisa}
                  WHERE yhtio     = '{$yhtio}'
                  AND tyyppi      = 'O'
                  AND kpl         = 0
                  AND otunnus     = '{$ostotilaus}'
                  AND tuoteno     = '{$tuoteno}'
                  AND tunnus      = '{$rivitunnus}'";
        $updres = pupe_query($query);
      }

      if (count($tilausrivit) > 0) {
        $query = "UPDATE lasku SET
                  sisviesti3   = 'ok_vie_varastoon'
                  WHERE yhtio  = '{$yhtio}'
                  AND tila     = 'K'
                  AND tunnus = '{$saapumistunnus}'";
        $updres = pupe_query($query);

        if (!empty($email)) {
          $body = t("Käsitellyn sanoman tiedostonimi: %s", "", $file)."<br><br>\n\n";
          $body .= t("Saapumiselle %d on kuitattu seuraavia tuotteita", "", $saapumisnro).":<br><br>\n\n";
          $body .= t("Tuoteno")." ".t("Kappaleita")."<br>\n";

          foreach ($tilausrivit as $rivitunnus => $data) {
            $body .= "{$data['tuoteno']} {$data['kpl']}<br>\n";
          }

          $params = array(
            'to' => $email,
            'cc' => '',
            'subject' => t("Saapumisen kuittaus")." - {$saapumisnro}",
            'ctype' => 'html',
            'body' => $body,
          );

          if (pupesoft_sahkoposti($params)) {
            pupesoft_log('inbound_delivery_confirmation', "Kuittaus saapumisesta {$saapumisnro} lähetetty onnistuneesti sähköpostiin {$email}");
          }
          else {
            pupesoft_log('inbound_delivery_confirmation', "Kuittaus saapumisesta {$saapumisnro} lähettäminen epäonnistui sähköpostiin {$email}");
          }
        }
      }
      else {
        pupesoft_log('inbound_delivery_confirmation', "Päivitettäviä tilausrivejä ei ollut sanomalla {$file}");
      }

      pupesoft_log('inbound_delivery_confirmation', "Saapumiskuittaus saapumiselta {$saapumisnro} vastaanotettu");

      rename($path.$file, $path."done/".$file);
    }
  }

  closedir($handle);
}
