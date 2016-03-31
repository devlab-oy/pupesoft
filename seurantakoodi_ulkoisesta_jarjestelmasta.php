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

$yhtio = mysql_escape_string(trim($argv[1]));
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

if ($handle = opendir($path)) {

  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);
    $ext = strtoupper($path_parts['extension']);

    if ($ext == 'TC' or $ext == 'TXT') {

      $filehandle = fopen($path.$file, "r");
      $rahtikirja_hukassa = false;

      while ($tietue = fgets($filehandle)) {

        // Tyhjät rivit skipataan
        if (trim($tietue) == "") continue;

        if ($ext == 'TC') {
          list($seurantakoodi, $posten_lahetenumero, $tilausnumero) = explode(';', $tietue);
        }
        else {
          list($posten_lahetenumero, $tilausnumero, $seurantakoodi) = explode(';', $tietue);
        }

        if (trim($seurantakoodi) == '') continue;
        // Otetaan vain eka ilmentymä tilausnumerosta jos sattuu olemaan monta eroteltuna spacella
        list($tilausnumero) = explode(' ', $tilausnumero);

        $tilausnumero = (int) $tilausnumero;
        $seurantakoodi = preg_replace("/\r\n|\r|\n/", '', $seurantakoodi);

        if ($tilausnumero == 0 or trim($seurantakoodi) == '') continue;

        $query = "UPDATE rahtikirjat SET
                  rahtikirjanro  = trim(concat(rahtikirjanro, ' ', '{$seurantakoodi}')),
                  tulostettu     = now()
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tilausnumero}'";
        pupe_query($query);

        if (mysql_affected_rows() == 0) {
          $rahtikirja_hukassa = true;
          break;
        }

        $query = "SELECT SUM(kilot) kilotyht
                  FROM rahtikirjat
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tilausnumero}'";
        $kilotres = pupe_query($query);
        $kilotrow = mysql_fetch_assoc($kilotres);

        paivita_rahtikirjat_tulostetuksi_ja_toimitetuksi(array('otunnukset' => $tilausnumero, 'kilotyht' => $kilotrow['kilotyht']));

        pupesoft_log('outbound_delivery', "Tilaus {$otunnus} toimituskuittaus käsitelty.");

        $_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

        // Katsotaan onko Magento käytössä, silloin merkataan tilaus toimitetuksi Magentoon kun rahtikirja tulostetaan
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

            $query = "SELECT asiakkaan_tilausnumero
                      FROM lasku
                      WHERE yhtio                 = '{$kukarow['yhtio']}'
                      AND tunnus                  = '{$tilausnumero}'
                      AND laatija                 = 'Magento'
                      AND asiakkaan_tilausnumero != ''";
            $mageres = pupe_query($query);

            while ($magerow = mysql_fetch_assoc($mageres)) {
              $magento_api_met = $toitarow['virallinen_selite'] != '' ? $toitarow['virallinen_selite'] : $toitarow['selite'];
              $magento_api_rak = $seurantakoodi;
              $magento_api_ord = $magerow["asiakkaan_tilausnumero"];

              require "magento_toimita_tilaus.php";
            }
          }
        }
      }
      // Jos rahtikirjaa ei löydetty niin ei siirretä done-kansioon
      if (!$rahtikirja_hukassa) rename($path.$file, $path."done/".$file);
    }
  }

  closedir($handle);
}
