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

if ($handle = opendir($path)) {

  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);
    $ext = strtoupper($path_parts['extension']);

    if ($ext == 'XML') {

      $filehandle = fopen($path.$file, "r");

      $xml = simplexml_load_file($path.$file);

      if ($xml) {

        $node = $xml->VendPackingSlip;

        $ostotilaus = (int) $node->PurchId;
        $saapumisnro = (int) $node->ReceiptsListId;

        $query = "SELECT tunnus
                  FROM lasku
                  WHERE yhtio  = '{$yhtio}'
                  AND tila     = 'K'
                  AND vanhatunnus = 0
                  AND laskunro = '{$saapumisnro}'";
        $selectres = pupe_query($query);
        $selectrow = mysql_fetch_assoc($selectres);

        $saapumistunnus = (int) $selectrow['tunnus'];

        if (isset($xml->Lines) and isset($xml->Lines->Line)) {

          # Poistetaan ostotilauksen kaikki kohdistukset saapumiselta
          # koska aineistossa on OIKEAT saapuneet ostotilauksen rivit
          $query = "UPDATE tilausrivi SET
                    uusiotunnus     = 0
                    WHERE yhtio     = '{$yhtio}'
                    AND tyyppi      = 'O'
                    AND uusiotunnus = '{$saapumistunnus}'";
          $updres = pupe_query($query);

          foreach ($xml->Lines->Line as $key => $line) {

            $rivitunnus = (int) $line->TransId;
            $tuoteno = (string) $line->ItemNumber;
            $kpl = (float) $line->ArrivedQuantity;

            if ($kpl != 0) {
              $uusiotunnuslisa = ", uusiotunnus = '{$saapumistunnus}' ";
            }
            else {
              $uusiotunnuslisa = "";
            }

            # P‰ivitet‰‰n varattu ja kohdistetaan rivi
            $query = "UPDATE tilausrivi SET
                      varattu         = '{$kpl}'
                      {$uusiotunnuslisa}
                      WHERE yhtio     = '{$yhtio}'
                      AND tyyppi      = 'O'
                      AND otunnus     = '{$ostotilaus}'
                      AND tuoteno     = '{$tuoteno}'
                      AND tunnus      = '{$rivitunnus}'";
            $updres = pupe_query($query);
          }
        }

        if (!empty($saapumistunnus)) {
          $query = "UPDATE lasku SET
                    sisviesti3   = 'ok_vie_varastoon'
                    WHERE yhtio  = '{$yhtio}'
                    AND tila     = 'K'
                    AND tunnus = '{$saapumistunnus}'";
          $updres = pupe_query($query);
        }

        rename($path.$file, $path."done/".$file);
      }
    }
  }

  closedir($handle);
}
